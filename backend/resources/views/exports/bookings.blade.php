<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Booking</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { margin: 0 0 8px; font-size: 18px; }
        p.meta { margin: 0 0 12px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        thead th { background: #f3f4f6; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Laporan Booking</h1>
    <p class="meta">Dicetak pada: {{ $generatedAt->format('d-m-Y H:i') }} | Total data: {{ $bookings->count() }}</p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Penyewa</th>
                <th>Lapangan</th>
                <th>Tanggal</th>
                <th>Jam</th>
                <th class="text-right">Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bookings as $booking)
                <tr>
                    <td>{{ $booking->booking_id }}</td>
                    <td>{{ $booking->nama_penyewa }}<br><small>{{ $booking->no_hp }}</small></td>
                    <td>Lapangan {{ $booking->lapangan->nomor_lapangan ?? '-' }}</td>
                    <td>{{ $booking->tanggal_booking }}</td>
                    <td>{{ substr((string) $booking->jam_mulai, 0, 5) }} - {{ substr((string) $booking->jam_selesai, 0, 5) }}</td>
                    <td class="text-right">Rp {{ number_format((int) $booking->total_harga, 0, ',', '.') }}</td>
                    <td>{{ ucfirst((string) $booking->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Belum ada data booking.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
