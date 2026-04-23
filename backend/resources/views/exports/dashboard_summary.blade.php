<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Ringkasan Dashboard</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { margin: 0 0 8px; font-size: 18px; }
        h2 { margin: 16px 0 8px; font-size: 14px; }
        p.meta { margin: 0 0 12px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        thead th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Laporan Ringkasan Dashboard</h1>
    <p class="meta">Dicetak pada: {{ $generatedAt->format('d-m-Y H:i') }} | Periode pendapatan: {{ $stats['charts']['pendapatan_harian']['periode_hari'] ?? '-' }} hari</p>

    <h2>Ringkasan Utama</h2>
    <table>
        <tbody>
            <tr>
                <th>Total Lapangan</th>
                <td>{{ $stats['lapangan']['total'] ?? 0 }}</td>
                <th>Total Booking</th>
                <td>{{ $stats['booking']['total'] ?? 0 }}</td>
            </tr>
            <tr>
                <th>Total Pembayaran</th>
                <td>{{ $stats['pembayaran']['total'] ?? 0 }}</td>
                <th>Total Pendapatan</th>
                <td>Rp {{ number_format((int) ($stats['total_pendapatan'] ?? 0), 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Status Booking</h2>
    <table>
        <thead>
            <tr>
                <th>Menunggu</th>
                <th>Terkonfirmasi</th>
                <th>Selesai</th>
                <th>Batal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $stats['booking']['menunggu'] ?? 0 }}</td>
                <td>{{ $stats['booking']['terkonfirmasi'] ?? 0 }}</td>
                <td>{{ $stats['booking']['selesai'] ?? 0 }}</td>
                <td>{{ $stats['booking']['batal'] ?? 0 }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Status Verifikasi Pembayaran</h2>
    <table>
        <thead>
            <tr>
                <th>Menunggu</th>
                <th>Diterima</th>
                <th>Ditolak</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $stats['pembayaran']['menunggu'] ?? 0 }}</td>
                <td>{{ $stats['pembayaran']['diterima'] ?? 0 }}</td>
                <td>{{ $stats['pembayaran']['ditolak'] ?? 0 }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Booking Terbaru</h2>
    <table>
        <thead>
            <tr>
                <th>Penyewa</th>
                <th>Lapangan</th>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($stats['booking_terbaru'] ?? []) as $booking)
                <tr>
                    <td>{{ $booking['nama_penyewa'] ?? '-' }}</td>
                    <td>Lapangan {{ $booking['lapangan']['nomor_lapangan'] ?? '-' }}</td>
                    <td>{{ $booking['tanggal_booking'] ?? '-' }}</td>
                    <td>{{ isset($booking['jam_mulai']) ? substr((string) $booking['jam_mulai'], 0, 5) : '-' }} - {{ isset($booking['jam_selesai']) ? substr((string) $booking['jam_selesai'], 0, 5) : '-' }}</td>
                    <td>Rp {{ number_format((int) ($booking['total_harga'] ?? 0), 0, ',', '.') }}</td>
                    <td>{{ ucfirst((string) ($booking['status'] ?? '-')) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Belum ada data booking terbaru.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
