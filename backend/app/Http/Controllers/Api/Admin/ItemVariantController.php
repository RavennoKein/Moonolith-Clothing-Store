<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Item;
use App\Models\ItemVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ItemVariantController extends Controller
{

    public function restock(Request $request, Item $item)
    {
        $validated = $request->validate([
            'size' => 'required|string|max:10',
            'variants' => 'required|array|min:1',
            'variants.*.color' => 'required|string|max:50',
            'variants.*.stock' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($item, $validated) {
            foreach ($validated['variants'] as $variant) {

                $size = strtoupper($validated['size']);
                $color = strtolower($variant['color']);
                $stock = (int) $variant['stock'];

                $existing = ItemVariant::where('item_id', $item->id)
                    ->where('size', $size)
                    ->where('color', $color)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    $existing->increment('stock', $stock);
                } else {
                    ItemVariant::create([
                        'item_id' => $item->id,
                        'size' => $size,
                        'color' => $color,
                        'stock' => $stock,
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Stok berhasil diperbarui',
        ]);
    }



}
