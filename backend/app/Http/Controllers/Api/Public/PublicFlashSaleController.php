<?php

namespace App\Http\Controllers\Api\Public;

use Carbon\Carbon;
use App\Models\FlashSale;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PublicFlashSaleController extends Controller
{
    public function active()
    {
        $flashSale = FlashSale::where('status', 'active')
            ->withCount('items')
            ->first();

        if ($flashSale) {
            $flashSale->start_time = Carbon::parse($flashSale->start_time)->timezone('Asia/Jakarta')->toDateTimeString();
            $flashSale->end_time = Carbon::parse($flashSale->end_time)->timezone('Asia/Jakarta')->toDateTimeString();
        }

        return response()->json([
            'success' => true,
            'data' => $flashSale
        ]);
    }

    public function show($id)
    {
        $flashSale = FlashSale::with(['items.item'])->findOrFail($id);

        $items = $flashSale->items->map(function ($fsi) {
            $original = $fsi->item->price;
            $discount = $fsi->discount_price;

            return [
                'id' => $fsi->item_id,
                'name' => $fsi->item->name,
                'image_url' => $fsi->item->image_url
                    ? asset(path: 'storage/' . $fsi->item->image_url)
                    : null,
                'original_price' => $original,
                'discount_price' => $discount,
                'discount_percentage' => round((($original - $discount) / $original) * 100),
                'soldOut' => ($fsi->flash_sale_stock - $fsi->sold_count) <= 0,
                'flash_sale_stock' => $fsi->flash_sale_stock,
                'sold_count' => $fsi->sold_count,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $flashSale->id,
                'name' => $flashSale->name,
                'start_time' => Carbon::parse($flashSale->start_time)->timezone('Asia/Jakarta')->toDateTimeString(),
                'end_time' => Carbon::parse($flashSale->end_time)->timezone('Asia/Jakarta')->toDateTimeString(),
                'items' => $items
            ]
        ]);
    }
}
