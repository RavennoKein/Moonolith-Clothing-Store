<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\Item;
use App\Models\Review;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PublicItemController extends Controller
{
    public function index()
    {
        $items = Item::with('variants', 'category')
            ->where('status_produk', '!=', 'non_aktif')
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
        ])->where('status_produk', '!=', 'non_aktif')
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
            ->where('status_produk', '!=', 'non_aktif')
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
                'created_at' => $relItem->created_at,
                'total_stock' => $relItem->totalStock(),
                'flash_sale' => $relFlash ? [
                    'discount_price' => $relFlash->discount_price,
                    'discount_percentage' => round((($relItem->price - $relFlash->discount_price) / $relItem->price) * 100),
                    'flash_sale_stock' => $relFlash->flash_sale_stock,
                    'sold_count' => $relFlash->sold_count,
                    'remaining' => max($relFlash->flash_sale_stock - $relFlash->sold_count, 0)
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
                    'flash_sale_stock' => $flashSaleItem->flash_sale_stock,
                    'sold_count' => $flashSaleItem->sold_count,
                    'discount_percentage' => round((($item->price - $flashSaleItem->discount_price) / $item->price) * 100)
                ] : null,
                'related_products' => $formattedRelated
            ]
        ]);
    }

    public function ratingSummary($itemId)
    {
        $item = Item::findOrFail($itemId);

        $total = $item->reviews()->count();

        $breakdown = Review::where('item_id', $itemId)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating');

        $formatted = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = $breakdown[$i] ?? 0;
            $formatted[$i] = [
                'count' => $count,
                'percent' => $total > 0 ? round(($count / $total) * 100) : 0
            ];
        }

        return response()->json([
            'average_rating' => round($item->reviews()->avg('rating') ?? 0, 1),
            'total_reviews' => $total,
            'breakdown' => $formatted
        ]);
    }

    public function reviews($itemId)
    {
        $reviews = Review::with('user:id,name')
            ->where('item_id', $itemId)
            ->latest()
            ->paginate(5);

        return response()->json([
            'success' => true,
            'data' => $reviews->items(),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total()
            ]
        ]);
    }

}
