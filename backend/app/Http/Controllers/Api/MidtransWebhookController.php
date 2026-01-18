<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('MIDTRANS WEBHOOK', $payload);

        $orderId = $payload['order_id'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;

        if (!$orderId) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $order = Order::where('invoice', $orderId)->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $payment = Payment::where('order_id', $order->id)->first();
        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        if (
            $transactionStatus === 'capture' ||
            $transactionStatus === 'settlement'
        ) {
            $payment->update([
                'payment_status' => 'paid',
                'raw_response' => json_encode($payload)
            ]);

            $order->update([
                'status' => 'processing'
            ]);
        }

        elseif ($transactionStatus === 'pending') {
            $payment->update([
                'payment_status' => 'pending',
                'raw_response' => json_encode($payload)
            ]);
        }

        elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
            $payment->update([
                'payment_status' => 'failed',
                'raw_response' => json_encode($payload)
            ]);

            $order->update([
                'status' => 'cancelled'
            ]);
        }

        return response()->json(['message' => 'OK']);
    }
}
