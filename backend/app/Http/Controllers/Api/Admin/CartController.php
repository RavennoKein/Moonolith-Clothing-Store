<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ItemVariant;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CartController extends Controller
{

    public function validateCart()
    {
        $user = auth()->user();

        $cart = Cart::with([
            'items.variant.item.flashSaleItems.flashSale'
        ])
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            abort(422, 'Keranjang masih kosong');
        }

        $total = 0;
        $validatedItems = [];

        foreach ($cart->items as $cartItem) {
            $variant = $cartItem->variant;
            $item = $variant->item;

            if ($cartItem->quantity > $variant->stock) {
                abort(422, "Stok {$item->name} ({$variant->size} / {$variant->color}) tidak mencukupi");
            }

            $activeFlashSaleItem = $item->flashSaleItems
                ->first(fn($fsi) => $fsi->flashSale?->status === 'active');

            if ($activeFlashSaleItem) {
                $remaining = $activeFlashSaleItem->flash_sale_stock - $activeFlashSaleItem->sold_count;

                if ($cartItem->quantity > $remaining) {
                    abort(422, "Stok flash sale {$item->name} tidak mencukupi");
                }

                $price = $activeFlashSaleItem->discount_price;
            } else {
                $price = $item->price;
            }

            $subtotal = $price * $cartItem->quantity;
            $total += $subtotal;

            $validatedItems[] = [
                'cart_item_id' => $cartItem->id,
                'item_id' => $item->id,
                'variant_id' => $variant->id,
                'name' => $item->name,
                'variant' => "{$variant->size} / {$variant->color}",
                'price' => $price,
                'quantity' => $cartItem->quantity,
                'subtotal' => $subtotal,
                'is_flashsale' => (bool) $activeFlashSaleItem
            ];
        }

        return response()->json([
            'success' => true,
            'total' => $total,
            'items' => $validatedItems
        ]);
    }

    public function show()
    {
        $user = auth()->user();

        $cart = Cart::with([
            'items.variant.item.flashSaleItems.flashSale'
        ])->where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json([
                'success' => true,
                'data' => [
                    'cart_id' => null,
                    'items' => [],
                    'total_quantity' => 0,
                    'total_price' => 0,
                ]
            ]);
        }

        $items = [];
        $totalQty = 0;
        $totalPrice = 0;

        foreach ($cart->items as $cartItem) {
            $variant = $cartItem->variant;
            $item = $variant->item;

            $flashSaleItem = $item->flashSaleItems()
                ->whereHas('flashSale', fn($q) => $q->where('status', 'active'))
                ->first();

            $price = $item->price;
            $flashPrice = null;
            $isFlashSale = false;

            if ($flashSaleItem) {
                $price = $flashSaleItem->discount_price;
                $flashPrice = $flashSaleItem->discount_price;
                $isFlashSale = true;
            }

            $subtotal = $price * $cartItem->quantity;

            $items[] = [
                'cart_item_id' => $cartItem->id,
                'variant_id' => $variant->id,
                'item_id' => $item->id,
                'name' => $item->name,
                'image' => $item->image_url
                    ? asset('storage/' . $item->image_url)
                    : null,
                'size' => $variant->size,
                'color' => $variant->color,
                'price' => $item->price,
                'flash_price' => $flashPrice,
                'is_flashsale' => $isFlashSale,
                'quantity' => $cartItem->quantity,
                'stock' => $variant->stock,
                'subtotal' => $subtotal,
                'status' => $variant->stock >= $cartItem->quantity
                    ? 'available'
                    : 'out_of_stock',
            ];

            $totalQty += $cartItem->quantity;
            $totalPrice += $subtotal;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'cart_id' => $cart->id,
                'items' => $items,
                'total_quantity' => $totalQty,
                'total_price' => $totalPrice,
            ]
        ]);
    }


    public function add(Request $request)
    {
        $validated = $request->validate([
            'variant_id' => 'required|exists:item_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $user = auth()->user();

        $cart = Cart::firstOrCreate(
            ['user_id' => $user->id],
            ['tanggal_dibuat' => now()]
        );

        $variant = ItemVariant::findOrFail($validated['variant_id']);

        if ($validated['quantity'] > $variant->stock) {
            abort(422, 'Stok tidak mencukupi');
        }

        $item = $cart->items()
            ->where('variant_id', $variant->id)
            ->first();

        if ($item) {
            $newQty = $item->quantity + $validated['quantity'];

            if ($newQty > $variant->stock) {
                abort(422, 'Jumlah melebihi stok tersedia');
            }

            $item->update(['quantity' => $newQty]);
        } else {
            $cart->items()->create([
                'variant_id' => $variant->id,
                'quantity' => $validated['quantity'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Produk ditambahkan ke keranjang',
        ]);
    }

    public function updateItem(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $user = auth()->user();

        $cartItem = CartItem::with([
            'cart',
            'variant.item.flashSaleItems.flashSale'
        ])->findOrFail($id);

        if ($cartItem->cart->user_id !== $user->id) {
            abort(403, 'Unauthorized cart access');
        }

        $variant = $cartItem->variant;
        $item = $variant->item;
        $newQty = $request->quantity;

        if ($newQty > $variant->stock) {
            abort(422, 'Jumlah melebihi stok tersedia');
        }

        $flashSaleItem = $item->flashSaleItems()
            ->whereHas('flashSale', fn($q) => $q->where('status', 'active'))
            ->first();

        if ($flashSaleItem) {
            $remainingFlashStock =
                $flashSaleItem->flash_sale_stock - $flashSaleItem->sold_count;

            if ($newQty > $remainingFlashStock) {
                abort(422, 'Jumlah melebihi stok flash sale');
            }
        }

        $cartItem->update([
            'quantity' => $newQty
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quantity berhasil diperbarui'
        ]);
    }

    public function removeItem($id)
    {
        $user = auth()->user();

        $cartItem = CartItem::with('cart')->findOrFail($id);

        if ($cartItem->cart->user_id !== $user->id) {
            abort(403, 'Unauthorized cart access');
        }

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil dihapus dari keranjang'
        ]);
    }


}
