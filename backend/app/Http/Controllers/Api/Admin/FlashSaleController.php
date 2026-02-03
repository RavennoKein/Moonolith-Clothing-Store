<?php

namespace App\Http\Controllers\Api\Admin;

use Carbon\Carbon;
use App\Models\Item;
use App\Models\FlashSale;
use Illuminate\Http\Request;
use App\Models\FlashSaleItem;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class FlashSaleController extends Controller
{

    public function availableItems()
    {
        $items = Item::query()
            ->where('status_produk', 'aktif')
            ->whereDoesntHave('flashSaleItems', function ($q) {
                $q->whereHas('flashSale', function ($fs) {
                    $fs->where('status', 'aktif');
                });
            })
            ->select('id', 'name', 'price')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    public function index()
    {
        $flashSales = FlashSale::withCount('items')
            ->orderByDesc('start_time')
            ->get()
            ->map(function ($fs) {
                return [
                    'id' => $fs->id,
                    'name' => $fs->name,
                    'start_time' => optional($fs->start_time)->format('Y-m-d H:i'),
                    'end_time' => optional($fs->end_time)->format('Y-m-d H:i'),
                    'status' => $fs->status,
                    'items_count' => $fs->items_count,
                    'description' =>$fs->description,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'active' => $flashSales->firstWhere('status', 'active'),
                'history' => $flashSales->filter(fn($fs) => $fs['status'] !== 'active')->values(),
            ]
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'startTime' => 'nullable|date',
            'endTime' => 'nullable|date|after:startTime',
            'isPermanent' => 'boolean',

            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.discount_price' => 'required|integer|min:1',
            'items.*.flash_sale_stock' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated) {

            $hasActive = FlashSale::where('status', 'active')->exists();
            if ($hasActive) {
                abort(422, 'Masih ada flash sale yang sedang aktif');
            }

            $flashSale = FlashSale::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'start_time' => $validated['startTime'],
                'end_time' => $validated['endTime'],
                'status' => 'scheduled',
            ]);

            foreach ($validated['items'] as $row) {
                $item = Item::with('variants')->findOrFail($row['item_id']);

                $totalStock = $item->variants->sum('stock');

                if ($totalStock === 0) {
                    abort(422, "{$item->name} tidak memiliki stok");
                }

                if ($row['flash_sale_stock'] > $totalStock) {
                    abort(422, "Stok flash sale {$item->name} melebihi stok tersedia ({$totalStock})");
                }

                $locked = FlashSaleItem::where('item_id', $row['item_id'])
                    ->whereHas('flashSale', fn($q) => $q->where('status', 'active'))
                    ->exists();

                if ($locked) {
                    abort(422, "{$item->name} sudah digunakan dalam flash sale lain");
                }

                FlashSaleItem::create([
                    'flash_sale_id' => $flashSale->id,
                    'item_id' => $row['item_id'],
                    'discount_price' => $row['discount_price'],
                    'flash_sale_stock' => $row['flash_sale_stock'],
                    'sold_count' => 0,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Flash sale berhasil dibuat',
            ], 201);
        });
    }

    public function show($id)
    {
        $flashSale = FlashSale::with([
            'items.item'
        ])->findOrFail($id);

        $items = $flashSale->items->map(function ($fsi) {
            $originalPrice = $fsi->item->price;
            $discountPrice = $fsi->discount_price;

            $discountPercentage = $originalPrice > 0
                ? round((($originalPrice - $discountPrice) / $originalPrice) * 100)
                : 0;

            return [
                'item_id' => $fsi->item_id,
                'name' => $fsi->item->name,
                'original_price' => $originalPrice,
                'discount_price' => $discountPrice,
                'discount_percentage' => $discountPercentage,
                'flash_sale_stock' => $fsi->flash_sale_stock,
                'sold_count' => $fsi->sold_count,
                'remaining_stock' => max(
                    $fsi->flash_sale_stock - $fsi->sold_count,
                    0
                ),
                'status' => ($fsi->flash_sale_stock - $fsi->sold_count) <= 0
                    ? 'sold_out'
                    : 'available',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $flashSale->id,
                'name' => $flashSale->name,
                'description' => $flashSale->description,
                'start_time' => optional($flashSale->start_time)->format('Y-m-d H:i'),
                'end_time' => optional($flashSale->end_time)->format('Y-m-d H:i'),
                'status' => $flashSale->status,
                'items_count' => $flashSale->items->count(),
                'items' => $items,
            ]
        ]);
    }

}
