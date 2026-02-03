<?php

namespace App\Http\Controllers\Api\User;

use Midtrans\Snap;
use App\Models\Cart;
use Midtrans\Config;
use App\Models\Order;
use App\Models\Payment;
use App\Models\OrderItem;
use App\Models\ItemVariant;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\ShippingService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class CheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'payment_method' => 'required|string',
            'full_name' => 'required|string',
            'phone_number' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'province' => 'required|string',
            'postal_code' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.variant_id' => 'required_with:items|exists:item_variants,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
        ]);

        try {
            return DB::transaction(function () use ($request, $user) {
                $checkoutItems = collect([]);
                $isDirectBuy = false;

                if ($request->has('items') && count($request->items) > 0) {
                    $isDirectBuy = true;

                    foreach ($request->items as $reqItem) {
                        $variant = ItemVariant::with('item')->find($reqItem['variant_id']);

                        $tempItem = new \stdClass();
                        $tempItem->variant = $variant;
                        $tempItem->variant_id = $variant->id;
                        $tempItem->quantity = $reqItem['quantity'];

                        $checkoutItems->push($tempItem);
                    }

                } else {
                    $cart = Cart::with('items.variant.item')->where('user_id', $user->id)->firstOrFail();

                    if ($cart->items->isEmpty()) {
                        abort(422, 'Keranjang belanja kosong');
                    }

                    $checkoutItems = $cart->items;
                }

                $subtotal = 0;
                foreach ($checkoutItems as $ci) {
                    $item = $ci->variant->item;

                    $flash = $item->flashSaleItems()
                        ->whereHas('flashSale', fn($q) => $q->where('status', 'active'))
                        ->first();

                    $price = $flash ? $flash->discount_price : $item->price;
                    $subtotal += $price * $ci->quantity;
                }

                $shippingCost = ShippingService::calculate($request->city);
                $totalAmount = $subtotal + $shippingCost;

                $order = Order::create([
                    'user_id' => $user->id,
                    'invoice' => 'INV-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                    'total_amount' => $totalAmount,
                    'status' => 'pending',
                    'receiver_name' => $request->full_name,
                    'receiver_phone' => $request->phone_number,
                    'address' => $request->address,
                    'city' => $request->city,
                    'province' => $request->province,
                    'postal_code' => $request->postal_code,
                    'shipping_cost' => $shippingCost,
                ]);

                foreach ($checkoutItems as $ci) {
                    $variant = ItemVariant::lockForUpdate()->find($ci->variant_id);

                    if (!$variant) {
                        throw new \Exception("Varian produk tidak ditemukan.");
                    }

                    if ($variant->stock < $ci->quantity) {
                        throw new \Exception("Stok untuk {$variant->item->name} ({$variant->name}) tidak mencukupi. Sisa: {$variant->stock}");
                    }

                    $item = $variant->item;
                    $flash = $item->flashSaleItems()
                        ->whereHas('flashSale', fn($q) => $q->where('status', 'active'))
                        ->first();

                    $finalPrice = $variant->item->price;

                    if ($flash) {
                        if ($flash->flash_sale_stock > 0 && ($flash->sold_count + $ci->quantity > $flash->flash_sale_stock)) {
                            throw new \Exception("Kuota Flash Sale untuk {$item->name} sudah habis.");
                        }

                        $flash->increment('sold_count', $ci->quantity);
                        $finalPrice = $flash->discount_price;
                    }

                    $variant->decrement('stock', $ci->quantity);

                    OrderItem::create([
                        'order_id' => $order->id,
                        'item_id' => $item->id,
                        'quantity' => $ci->quantity,
                        'price' => $finalPrice,
                        'variant_id' => $ci->variant_id
                    ]);
                }

                Payment::create([
                    'order_id' => $order->id,
                    'payment_gateway' => 'midtrans',
                    'transaction_id' => $order->invoice,
                    'payment_method' => $request->payment_method,
                    'amount' => $totalAmount,
                    'payment_status' => 'pending',
                ]);

                Config::$serverKey = config('services.midtrans.server_key');
                Config::$isProduction = false;
                Config::$isSanitized = true;
                Config::$is3ds = true;

                $enabledPayments = [$request->payment_method];
                if ($request->payment_method === 'qris') {
                    $enabledPayments = ['gopay', 'shopeepay', 'other_qris'];
                }

                $snapParams = [
                    'transaction_details' => [
                        'order_id' => $order->invoice,
                        'gross_amount' => (int) $totalAmount,
                    ],
                    'customer_details' => [
                        'first_name' => $request->full_name,
                        'phone' => $request->phone_number,
                        'shipping_address' => [
                            'address' => $request->address,
                            'city' => $request->city,
                            'postal_code' => $request->postal_code,
                            'country_code' => 'IDN'
                        ]
                    ],
                    'enabled_payments' => $enabledPayments,
                    'callbacks' => [
                        'finish' => 'http://localhost:5173/invoice/' . $order->invoice,
                    ]
                ];

                $snapToken = Snap::getSnapToken($snapParams);
                $order->snap_token = $snapToken;
                $order->save();

                if (!$isDirectBuy) {
                    $cart = Cart::where('user_id', $user->id)->first();
                    if ($cart) {
                        $cart->items()->delete();
                    }
                }

                return response()->json([
                    'success' => true,
                    'invoice' => $order->invoice,
                    'snap_token' => $snapToken,
                ]);
            });

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function checkShipping(Request $request)
    {
        $request->validate(['city' => 'required|string']);
        $cost = ShippingService::calculate($request->city);
        return response()->json(['cost' => $cost]);
    }
}