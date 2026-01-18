<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class CashflowController extends Controller
{

    public function cashflow(Request $request)
    {
        $type = $request->query('type', 'monthly');

        if ($type === 'daily') {
            $data = Order::select(
                DB::raw('DATE(orders.created_at) as period'),
                DB::raw('SUM(orders.total_amount) as amount'),
                DB::raw('SUM(order_items.quantity) as sold')
            )
                ->join('order_items', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.status', 'done')
                ->whereBetween('orders.created_at', [
                    '2026-01-13 00:00:00',
                    '2026-01-19 23:59:59'
                ])
                ->groupBy(DB::raw('DATE(orders.created_at)'))
                ->orderBy(DB::raw('DATE(orders.created_at)'))
                ->get()
                ->map(function ($row) {
                    $row->label = \Carbon\Carbon::parse($row->period)->locale('id')->translatedFormat('l');
                    return $row;
                });

        } else {
            $data = Order::select(
                DB::raw('DATE_FORMAT(orders.created_at, "%Y-%m") as period'),
                DB::raw('SUM(orders.total_amount) as amount'),
                DB::raw('SUM(order_items.quantity) as sold')
            )
                ->join('order_items', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.status', 'done')
                ->whereYear('orders.created_at', now()->year)
                ->groupBy(DB::raw('DATE_FORMAT(orders.created_at, "%Y-%m")'))
                ->orderBy(DB::raw('DATE_FORMAT(orders.created_at, "%Y-%m")'))
                ->get()
                ->map(function ($row) {
                    $row->label = \Carbon\Carbon::createFromFormat('Y-m', $row->period)->locale('id')->translatedFormat('F');
                    return $row;
                });

        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

}
