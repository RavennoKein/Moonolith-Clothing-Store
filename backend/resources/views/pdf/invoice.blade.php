<!DOCTYPE html>
<html>

<head>
    <title>Invoice {{ $order->invoice }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10pt;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .total-row td {
            font-weight: bold;
        }

        .status {
            padding: 5px;
            color: white;
            background: #333;
            display: inline-block;
            border-radius: 4px;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>INVOICE PEMBELIAN</h2>
        <p>No: {{ $order->invoice }}</p>
        <p>Tanggal: {{ $order->created_at->format('d M Y') }}</p>
    </div>

    <div style="margin-bottom: 20px;">
        <strong>Penerima:</strong><br>
        {{ $order->receiver_name }}<br>
        {{ $order->receiver_phone }}<br>
        {{ $order->address . ', ' . $order->city . ', ' . $order->province }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Produk</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->item->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right">Subtotal</td>
                <td>Rp {{ number_format($order->total_amount - $order->shipping_cost, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="3" class="text-right">Ongkos Kirim</td>
                <td>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="3" class="text-right">Grand Total</td>
                <td>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div style="text-align: center; margin-top: 30px;">
        <span class="status">Status: {{ strtoupper($order->status) }}</span>
    </div>
</body>

</html>
