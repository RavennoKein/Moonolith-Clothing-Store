<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\Item;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PublicItemController extends Controller
{
    public function index()
    {
        $items = Item::with('variants', 'category')
            ->where('status_produk','!=', 'non_aktif')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'image_url' => $item->image_url
                    ? asset('storage/' . $item->image_url)
                    : null,
                'price' => (int) $item->price,
                'total_stock' => $item->totalStock(),
                'gender' => $item->gender,
                'status_produk' => $item->status_produk,
                'created_at' => $item->created_at,
                'category' => $item->category ? [
                    'id' => $item->category->id,
                    'name' => $item->category->name,
                ] : null,
                'variants' => $item->variants->map(function ($variant) {
                    return [
                        'size' => $variant->size,
                        'color' => $variant->color,
                        'stock' => $variant->stock
                    ];
                }),
            ])
        ]);
    }

    public function show($id)
    {
        $now = now();

        $item = Item::with([
            'variants',
            'category',
            'flashSaleItems' => function ($query) use ($now) {
                $query->whereHas('flashSale', function ($q) use ($now) {
                    $q->where('status', 'active')
                        ->where('start_time', '<=', $now)
                        ->where('end_time', '>=', $now);
                })->with('flashSale');
            }
        ])->where('status_produk','!=', 'aktif')
            ->findOrFail($id);

        $sizes = $item->variants->groupBy('size')->map(function ($variants, $size) {
            return [
                'size' => $size,
                'total_stock' => $variants->sum('stock'),
                'colors' => $variants->map(fn($v) => [
                    'id' => $v->id,
                    'color' => $v->color,
                    'stock' => $v->stock
                ])->values()
            ];
        })->values();

        $flashSaleItem = $item->flashSaleItems->first();
        $isFlashSaleValid = $flashSaleItem && ($flashSaleItem->flash_sale_stock > $flashSaleItem->sold_count);

        $relatedItems = Item::where('category_id', $item->category_id)
            ->where('id', '!=', $item->id)
            ->where('status_produk', 'aktif')
            ->inRandomOrder()
            ->limit(5)
            ->get();

        $formattedRelated = $relatedItems->map(function ($relItem) use ($now) {
            $relFlash = $relItem->flashSaleItems()
                ->whereHas('flashSale', function ($q) use ($now) {
                    $q->where('status', 'active')->where('start_time', '<=', $now)->where('end_time', '>=', $now);
                })->first();

            return [
                'id' => $relItem->id,
                'name' => $relItem->name,
                'image_url' => $relItem->image_url ? asset('storage/' . $relItem->image_url) : null,
                'price' => $relItem->price,
                'flash_sale' => $relFlash ? [
                    'discount_price' => $relFlash->discount_price,
                    'discount_percentage' => round((($relItem->price - $relFlash->discount_price) / $relItem->price) * 100)
                ] : null
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $item->id,
                'name' => $item->name,
                'category_name' => $item->category->name,
                'image_url' => $item->image_url ? asset('storage/' . $item->image_url) : null,
                'price' => $item->price,
                'gender' => $item->gender,
                'description' => $item->description,
                'total_stock' => $item->totalStock(),
                'sizes' => $sizes,
                'bahan' => $item->bahan,
                'tingkat_ketebalan' => $item->tingkat_ketebalan,
                'flash_sale' => $isFlashSaleValid ? [
                    'discount_price' => $flashSaleItem->discount_price,
                    'remaining_stock' => max($flashSaleItem->flash_sale_stock - $flashSaleItem->sold_count, 0),
                    'discount_percentage' => round((($item->price - $flashSaleItem->discount_price) / $item->price) * 100)
                ] : null,
                'related_products' => $formattedRelated
            ]
        ]);
    }
}
