<?php

namespace App\Http\Controllers\Api\Admin;

use Midtrans\Config;
use App\Models\Order;
use Midtrans\Transaction;
use App\Models\ItemVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');
        $sortKey = $request->input('sort_key', 'id');
        $sortOrder = $request->input('sort_order', 'desc');

        $sortMap = [
            'no_resi' => 'invoice',
            'tanggal_beli' => 'created_at',
            'total_harga' => 'total_amount',
            'name' => 'receiver_name',
            'id' => 'id',
            'status' => 'status'
        ];

        $dbSortKey = $sortMap[$sortKey] ?? 'created_at';

        $query = Order::with(['items.item', 'items.variant']);

        if (!in_array($user->role, ['admin', 'super'])) {
            $query->where('user_id', $user->id);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice', 'like', "%{$search}%")
                    ->orWhere('receiver_name', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $query->orderBy($dbSortKey, $sortOrder);

        $orders = $query->paginate($perPage);

        $formattedData = $orders->getCollection()->map(function ($order) {
            return [
                'id' => $order->id,
                'no_resi' => $order->invoice,
                'name' => $order->receiver_name, // Atau $order->user->name jika ingin nama akun
                'address' => $order->address,
                'tanggal_beli' => $order->created_at->format('d M Y'),
                'status' => ucfirst($order->status),
                'total_harga' => $order->total_amount,
                'daftar_barang' => $order->items->map(function ($detail) {
                    return [
                        'name' => $detail->item->name ?? 'Produk Dihapus',
                        'qty' => $detail->quantity,
                        'price' => $detail->price,
                        'variant' => $detail->variant->name ?? '-'
                    ];
                })
            ];
        });

        $orders->setCollection($formattedData);

        return response()->json($orders);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:delivered,done'
        ]);

        if ($request->status === 'delivered' && $order->status !== 'processing') {
            return response()->json([
                'success' => false,
                'message' => 'Order harus berstatus PROCESSING terlebih dahulu'
            ], 422);
        }

        if ($request->status === 'done' && $order->status !== 'delivered') {
            return response()->json([
                'success' => false,
                'message' => 'Order harus berstatus DELIVERED terlebih dahulu'
            ], 422);
        }

        $order->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status order berhasil diperbarui',
            'status' => $order->status
        ]);
    }

    public function cancel(Order $order)
    {
        $user = auth()->user();

        if ($order->user_id !== $user->id) {
            abort(403, 'Tidak punya akses ke order ini');
        }

        if (in_array($order->status, ['delivered', 'done'])) {
            abort(422, 'Order yang sudah dikirim/selesai tidak dapat dibatalkan');
        }

        if ($order->status === 'cancelled') {
            abort(422, 'Order sudah dibatalkan sebelumnya');
        }

        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = false;
        Config::$isSanitized = true;
        Config::$is3ds = true;

        try {
            $midtransStatus = Transaction::status($order->invoice);

            if ($midtransStatus->transaction_status == 'pending' || $midtransStatus->transaction_status == 'capture') {
                $response = Transaction::cancel($order->invoice);
            }
        } catch (\Exception $e) {

            if ($e->getCode() != 404) {
                \Log::error('Gagal cancel Midtrans: ' . $e->getMessage());
            }
        }

        return DB::transaction(function () use ($order) {
            $order->load(['items.variant', 'items.item.flashSaleItems.flashSale']);

            foreach ($order->items as $orderItem) {
                $variant = ItemVariant::lockForUpdate()->find($orderItem->variant_id);

                if ($variant) {
                    $variant->increment('stock', $orderItem->quantity);
                }

                $flashSaleItem = $orderItem->item
                    ->flashSaleItems
                    ->first(fn($fsi) => $fsi->flashSale?->status === 'active');

                if ($flashSaleItem) {
                    if ($flashSaleItem->sold_count >= $orderItem->quantity) {
                        $flashSaleItem->decrement('sold_count', $orderItem->quantity);
                    } else {
                        $flashSaleItem->update(['sold_count' => 0]);
                    }
                }
            }

            $order->update([
                'status' => 'cancelled'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibatalkan dan stok telah dikembalikan.'
            ]);
        });
    }

    public function showByInvoice($invoice)
    {
        $order = Order::with(['items.item', 'items.variant'])
            ->where('invoice', $invoice)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice ' . $invoice . ' tidak ditemukan di database.'
            ], 404);
        }

        if ($order->status == 'pending' && empty($order->snap_token)) {

            \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
            \Midtrans\Config::$isProduction = false;
            \Midtrans\Config::$isSanitized = true;
            \Midtrans\Config::$is3ds = true;

            $params = [
                'transaction_details' => [
                    'order_id' => $order->invoice,
                    'gross_amount' => (int) $order->total_price,
                ],
                'customer_details' => [
                    'first_name' => $order->name,
                    'phone' => $order->phone,
                ],
            ];

            try {
                $snapToken = \Midtrans\Snap::getSnapToken($params);

                $order->snap_token = $snapToken;
                $order->save();

            } catch (\Exception $e) {
            }
        }

        $formattedData = [
            'id' => $order->id,
            'invoice' => $order->invoice,
            'status' => $order->status,
            'total_price' => $order->total_amount,
            'shipping_cost' => $order->shipping_cost,
            'snap_token' => $order->snap_token ?? null,
            'name' => $order->receiver_name,
            'phone' => $order->receiver_phone,
            'address' => $order->address . ', ' . $order->city . ', ' . $order->province,
            'items' => $order->items->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'qty' => $detail->quantity,
                    'price' => $detail->price,
                    'product_name' => $detail->item ? $detail->item->name : 'Produk tidak tersedia',
                    'product_image' => $detail->item ? asset('storage/' . $detail->item->image_url) : null,
                    'variant' => $detail->variant ? $detail->variant->name : '-',
                ];
            })
        ];

        return response()->json([
            'status' => 'success',
            'data' => $formattedData
        ]);
    }

}
