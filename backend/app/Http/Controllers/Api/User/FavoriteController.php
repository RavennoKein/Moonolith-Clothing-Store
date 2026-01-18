<?php

namespace App\Http\Controllers\Api\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    public function toggle(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,id'
        ]);

        $user = Auth::user();
        $itemId = $request->item_id;

        $attached = $user->favorites()->toggle($itemId);

        $isFavorited = count($attached['attached']) > 0;

        return response()->json([
            'success' => true,
            'is_favorited' => $isFavorited,
            'message' => $isFavorited ? 'Ditambahkan ke favorit' : 'Dihapus dari favorit'
        ]);
    }

    public function checkStatus($itemId)
    {
        $isFavorited = false;
        if (Auth::check()) {
            $isFavorited = Auth::user()->favorites()->where('item_id', $itemId)->exists();
        }

        return response()->json([
            'success' => true,
            'is_favorited' => $isFavorited
        ]);
    }

    public function index()
    {
        $user = Auth::user();

        $favorites = $user->favorites()->with('category')->get();

        $favorites->transform(function ($item) {
            $item->image_url = $item->image_url ? asset('storage/' . $item->image_url) : null;
            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => $favorites
        ]);
    }
}