<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Item;
use App\Models\ItemVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::with(['variants', 'flashSaleItems.flashSale'])
            ->orderByDesc('created_at')
            ->get();

        $data = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'price' => (int) $item->price,
                'category' => $item->category,
                'status_produk' => $item->status_produk,
                'created_at' => $item->created_at,
                'is_flashsale' => $item->isOnActiveFlashSale(),
                'variants' => $item->variants->map(function ($v) {
                    return [
                        'size' => $v->size,
                        'color' => $v->color,
                        'stock' => (int) $v->stock,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|integer|min:1',
            'category_id' => 'required|exists:categories,id',
            'bahan' => 'nullable|string|max:100',
            'tingkat_ketebalan' => 'nullable|in:tipis,sedang,tebal',
            'status_produk' => 'required|in:aktif,non_aktif,terbatas',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'gender' => 'nullable|in:men,women,kids,unisex',

            'variants' => 'nullable|array',
            'variants.*.color' => 'required_with:variants|string|max:50',
            'variants.*.size' => 'required_with:variants|string|max:10',
            'variants.*.stock' => 'required_with:variants|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated, $request) {

            $imagePath = null;

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('items', 'public');
            }

            $item = Item::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'category_id' => $validated['category_id'],
                'bahan' => $validated['bahan'] ?? null,
                'tingkat_ketebalan' => $validated['tingkat_ketebalan'] ?? null,
                'status_produk' => $validated['status_produk'],
                'image_url' => $imagePath,
                'gender' => $validated['gender'] ?? 'unisex',
            ]);

            if (!empty($validated['variants'])) {
                foreach ($validated['variants'] as $variant) {
                    ItemVariant::create([
                        'item_id' => $item->id,
                        'color' => strtolower($variant['color']),
                        'size' => strtoupper($variant['size']),
                        'stock' => $variant['stock'],
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil dibuat',
                'data' => $this->transformItemDetail($item->load('variants')),
            ], 201);
        });
    }

    public function update(Request $request, Item $item)
    {
        $this->ensureNotInActiveFlashSale($item);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|integer|min:1',
            'category_id' => 'required|exists:categories,id',
            'bahan' => 'nullable|string|max:100',
            'tingkat_ketebalan' => 'nullable|in:tipis,sedang,tebal',
            'status_produk' => 'required|in:aktif,non_aktif,terbatas',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'gender' => 'nullable|in:men,women,kids,unisex',
        ]);

        return DB::transaction(function () use ($validated, $request, $item) {

            $oldImagePath = $item->image_url;
            $newImagePath = null;

            if ($request->hasFile('image')) {
                $newImagePath = $request->file('image')->store('items', 'public');
            }

            $item->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'category_id' => $validated['category_id'],
                'bahan' => $validated['bahan'] ?? null,
                'tingkat_ketebalan' => $validated['tingkat_ketebalan'] ?? null,
                'status_produk' => $validated['status_produk'],
                'image_url' => $newImagePath ?? $oldImagePath,
                'gender' => $validated['gender'] ?? 'unisex',
            ]);

            if ($newImagePath && $oldImagePath) {
                Storage::disk('public')->delete($oldImagePath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil diperbarui',
                'data' => $this->transformItemDetail($item->fresh('variants')),
            ]);
        });
    }

    public function destroy(Item $item)
    {
        $this->ensureNotInActiveFlashSale($item);

        if ($item->image_url) {
            Storage::disk('public')->delete($item->image_url);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil dihapus',
        ]);
    }

    private function transformItemDetail(Item $item): array
    {
        $sizes = [];
        $totalStock = 0;

        foreach ($item->variants as $variant) {
            $size = $variant->size;
            $color = $variant->color;
            $stock = (int) $variant->stock;

            if (!isset($sizes[$size])) {
                $sizes[$size] = [
                    'size' => $size,
                    'total_stock' => 0,
                    'colors' => [],
                ];
            }

            $sizes[$size]['colors'][] = [
                'color' => $color,
                'stock' => $stock,
            ];

            $sizes[$size]['total_stock'] += $stock;
            $totalStock += $stock;
        }

        return [
            'id' => $item->id,
            'name' => $item->name,
            'category' => [
                'id' => $item->category->id,
                'name' => $item->category->name,
            ],
            'price' => (int) $item->price,
            'status_produk' => $item->status_produk,
            'bahan' => $item->bahan,
            'tingkat_ketebalan' => $item->tingkat_ketebalan,
            'description' => $item->description,
            'image_url' => $item->image_url
                ? asset('storage/' . $item->image_url)
                : null,
            'gender' => $item->gender,
            'total_stock' => $totalStock,
            'sizes' => array_values($sizes),
            'is_locked' => $item->isOnActiveFlashSale(),
            'lock_reason' => $item->isOnActiveFlashSale() ? 'FLASHSALE_ACTIVE' : null,
        ];
    }

    public function show($id)
    {
        $item = Item::with('variants', 'category')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->transformItemDetail($item),
        ]);
    }

    private function ensureNotInActiveFlashSale(Item $item)
    {
        if ($item->isOnActiveFlashSale()) {
            abort(response()->json([
                'success' => false,
                'code' => 'ITEM_LOCKED_BY_FLASHSALE',
                'message' => 'Item sedang digunakan dalam flash sale aktif',
            ], 423));
        }
    }
}
