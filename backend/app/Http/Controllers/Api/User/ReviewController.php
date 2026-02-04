<?php

namespace App\Http\Controllers\Api\User;

use App\Models\Order;
use App\Models\Review;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'item_id' => 'required|exists:items,id',
            'order_id' => 'required|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000'
        ]);

        $order = Order::where('id', $request->order_id)
            ->where('user_id', $user->id)
            ->where('status', 'done')
            ->firstOrFail();

        $hasItem = OrderItem::where('order_id', $order->id)
            ->where('item_id', $request->item_id)
            ->exists();

        if (!$hasItem) {
            abort(403, 'Item tidak ada di pesanan ini');
        }

        $alreadyReviewed = Review::where('order_id', $order->id)
            ->where('item_id', $request->item_id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyReviewed) {
            abort(422, 'Produk ini sudah Anda review');
        }

        $review = Review::create([
            'item_id' => $request->item_id,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review berhasil dikirim',
            'data' => $review
        ]);
    }


}
