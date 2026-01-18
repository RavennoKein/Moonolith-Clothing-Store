<?php

namespace App\Http\Controllers\Api\Admin;

use Carbon\Carbon;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.   
     */
    public function index()
    {
        $totalItems = Item::count();

        $itemsSold = OrderItem::whereHas('order', function ($query) {
            $query->where('status', 'done');
        })->sum('quantity');

        $itemsShipped = OrderItem::whereHas('order', function ($query) {
            $query->where('status', 'delivered');
        })->sum('quantity');

        $monthlyEarnings = Order::where('status', 'done')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('total_amount');

        $recentPurchases = Order::with('user')
            ->whereIn('status', ['done', 'delivered', 'cancelled'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($order) {
                return [
                    'receipt' => $order->invoice,
                    'name' => $order->user->name,
                    'address' => $order->address,
                    'date' => $order->created_at->format('d F Y'),
                    'status' => ucfirst($order->status),
                ];
            });


        return response()->json([
            'success'=> true,
            'data' => [
                'totalItems' => $totalItems,
                'itemsSold' => $itemsSold,
                'itemsShipped' => $itemsShipped,
                'monthlyEarnings' => $monthlyEarnings,
                'recentPurchases' => $recentPurchases
            ]
        ]);
    }
}
