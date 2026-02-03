<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Item; 

class MostBuyController extends Controller
{
    public function index()
    {
        $topItems = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', ['processing', 'delivered', 'done'])
            ->select('item_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('item_id')
            ->orderByDesc('total_sold')
            ->limit(20)
            ->get();

        if ($topItems->isEmpty()) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $topItemIds = $topItems->pluck('item_id')->toArray();
        $soldMap = $topItems->pluck('total_sold', 'item_id')->toArray();
        $now = now();

        $items = Item::with([
            'category',
            'variants',
            'flashSaleItems' => function ($query) use ($now) {
                $query->whereHas('flashSale', function ($q) use ($now) {
                    $q->where('status', 'active')
                        ->where('start_time', '<=', $now)
                        ->where('end_time', '>=', $now);
                });
            }
        ])
            ->whereIn('id', $topItemIds)
            ->where('status_produk', '!=', 'non_aktif')
            ->get();

        $items = $items->sortByDesc(fn($item) => $soldMap[$item->id] ?? 0)->values();

        return response()->json([
            'success' => true,
            'data' => $items->map(function ($item) use ($soldMap) {
                $flashSaleItem = $item->flashSaleItems->first();

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'image_url' => $item->image_url
                        ? asset('storage/' . $item->image_url)
                        : null,
                    'price' => (int) $item->price,
                    'status_produk' => $item->status_produk,
                    'total_sold' => (int) ($soldMap[$item->id] ?? 0),
                    'total_stock' => $item->totalStock(),
                    'gender' => $item->gender,
                    'category' => $item->category ? [
                        'id' => $item->category->id,
                        'name' => $item->category->name,
                    ] : null,
                    'variants' => $item->variants->map(fn($v) => [
                        'id' => $v->id,
                        'size' => $v->size,
                        'color' => $v->color,
                        'stock' => $v->stock,
                    ]),
                    'flash_sale' => $flashSaleItem ? [
                        'discount_price' => $flashSaleItem->discount_price,
                        'discount_percentage' => round((($item->price - $flashSaleItem->discount_price) / $item->price) * 100)
                    ] : null,
                ];
            })
        ]);
    }
}