<!DOCTYPE html>
<html>

<head>
    <title>Laporan Penjualan</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10pt;
        }

        h2,
        h4 {
            text-align: center;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px;
            text-align: left;
            font-size: 9pt;
        }

        th {
            background-color: #f2f2f2;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .badge {
            padding: 2px 5px;
            color: white;
            border-radius: 3px;
            font-size: 8pt;
        }

        .bg-green {
            background-color: green;
        }

        .bg-gray {
            background-color: gray;
        }
    </style>
</head>

<body>
    <h2>LAPORAN TRANSAKSI PENJUALAN</h2>
    <h4>Periode: {{ date('d M Y', strtotime($startDate)) }} - {{ date('d M Y', strtotime($endDate)) }}</h4>

    <table>
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 15%">Tanggal</th>
                <th style="width: 15%">No. Resi</th>
                <th style="width: 20%">Nama Pelanggan</th>
                <th style="width: 15%">Status</th>
                <th style="width: 15%">Metode</th>
                <th style="width: 15%">Total (IDR)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $index => $order)
                <tr>
                    <td style="text-align: center">{{ $index + 1 }}</td>
                    <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $order->invoice ?? $order->no_resi }}</td>
                    <td>{{ $order->receiver_name }}</td>
                    <td style="text-align: center;">
                        {{ strtoupper($order->status) }}
                    </td>
                    <td>Transfer</td>
                    <td class="text-right">{{ number_format($order->total_amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center">Tidak ada data transaksi pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right"><strong>Total Pendapatan (*Status = Done):</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($totalRevenue, 0, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>
</body>

</html>
