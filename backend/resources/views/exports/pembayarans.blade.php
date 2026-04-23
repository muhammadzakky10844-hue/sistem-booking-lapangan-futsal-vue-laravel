<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Pembayaran</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h1 { margin: 0 0 8px; font-size: 18px; }
        p.meta { margin: 0 0 12px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        thead th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Laporan Pembayaran</h1>
    <p class="meta">Dicetak pada: {{ $generatedAt->format('d-m-Y H:i') }} | Total data: {{ $pembayarans->count() }}</p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Booking</th>
                <th>Penyewa</th>
                <th>Metode</th>
                <th>Status Verifikasi</th>
                <th>Status Gateway</th>
                <th>Referensi</th>
                <th>Waktu</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pembayarans as $pembayaran)
                <tr>
                    <td>{{ $pembayaran->pembayaran_id }}</td>
                    <td>#{{ $pembayaran->booking_id }} (Lapangan {{ $pembayaran->booking->lapangan->nomor_lapangan ?? '-' }})</td>
                    <td>{{ $pembayaran->booking->nama_penyewa ?? '-' }}</td>
                    <td>{{ $pembayaran->metode_pembayaran ?? '-' }}</td>
                    <td>{{ ucfirst((string) $pembayaran->status_verifikasi) }}</td>
                    <td>{{ $pembayaran->gateway_transaction_status ?? '-' }}</td>
                    <td>{{ $pembayaran->gateway_order_id ?? ($pembayaran->gateway_transaction_id ?? '-') }}</td>
                    <td>{{ optional($pembayaran->paid_at ?? $pembayaran->updated_at ?? $pembayaran->created_at)->format('d-m-Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Belum ada data pembayaran.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
