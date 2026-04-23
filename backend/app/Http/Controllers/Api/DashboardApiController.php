<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Lapangan;
use App\Models\Pembayaran;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardApiController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'periode' => 'nullable|in:7,14,30',
        ]);

        $periodDays = (int) ($validated['periode'] ?? 7);

        return response()->json($this->buildDashboardPayload($periodDays));
    }

    public function exportPdf(Request $request)
    {
        $validated = $request->validate([
            'periode' => 'nullable|in:7,14,30',
        ]);

        $periodDays = (int) ($validated['periode'] ?? 7);
        $stats = $this->buildDashboardPayload($periodDays);

        $pdf = Pdf::loadView('exports.dashboard_summary', [
            'stats' => $stats,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('laporan-dashboard-' . now()->format('Ymd-His') . '.pdf');
    }

    private function buildDashboardPayload(int $periodDays): array
    {

        $bookingStats = [
            'total' => Booking::count(),
            'menunggu' => Booking::where('status', 'menunggu')->count(),
            'terkonfirmasi' => Booking::where('status', 'terkonfirmasi')->count(),
            'selesai' => Booking::where('status', 'selesai')->count(),
            'batal' => Booking::where('status', 'batal')->count(),
        ];

        $pembayaranStats = [
            'total' => Pembayaran::count(),
            'menunggu' => Pembayaran::where('status_verifikasi', 'menunggu')->count(),
            'diterima' => Pembayaran::where('status_verifikasi', 'diterima')->count(),
            'ditolak' => Pembayaran::where('status_verifikasi', 'ditolak')->count(),
        ];

        $totalPendapatan = (int) Pembayaran::query()
            ->join('bookings', 'bookings.booking_id', '=', 'pembayarans.booking_id')
            ->where('pembayarans.status_verifikasi', 'diterima')
            ->sum('bookings.total_harga');

        $startDate = Carbon::today()->subDays($periodDays - 1);
        $endDate = Carbon::today();

        $dailyRevenueRaw = Pembayaran::query()
            ->join('bookings', 'bookings.booking_id', '=', 'pembayarans.booking_id')
            ->selectRaw("DATE(COALESCE(pembayarans.paid_at, pembayarans.updated_at, pembayarans.created_at)) as tanggal, SUM(bookings.total_harga) as total")
            ->where('pembayarans.status_verifikasi', 'diterima')
            ->whereBetween(
                DB::raw("DATE(COALESCE(pembayarans.paid_at, pembayarans.updated_at, pembayarans.created_at))"),
                [$startDate->toDateString(), $endDate->toDateString()]
            )
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');

        $revenueLabels = [];
        $revenueValues = [];

        for ($i = 0; $i < $periodDays; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dateKey = $date->toDateString();

            $revenueLabels[] = $date->format('d M');
            $revenueValues[] = (int) ($dailyRevenueRaw[$dateKey] ?? 0);
        }

        return [
            'lapangan' => [
                'total'     => Lapangan::count(),
                'tersedia'  => Lapangan::where('status', 'tersedia')->count(),
                'perbaikan' => Lapangan::where('status', 'perbaikan')->count(),
            ],
            'booking' => $bookingStats,
            'pembayaran' => $pembayaranStats,
            'total_pendapatan' => $totalPendapatan,
            'charts' => [
                'pendapatan_harian' => [
                    'periode_hari' => $periodDays,
                    'labels' => $revenueLabels,
                    'values' => $revenueValues,
                ],
                'status_booking' => [
                    'labels' => ['Menunggu', 'Terkonfirmasi', 'Selesai', 'Batal'],
                    'values' => [
                        $bookingStats['menunggu'],
                        $bookingStats['terkonfirmasi'],
                        $bookingStats['selesai'],
                        $bookingStats['batal'],
                    ],
                ],
                'status_pembayaran' => [
                    'labels' => ['Pending', 'Berhasil', 'Gagal'],
                    'values' => [
                        $pembayaranStats['menunggu'],
                        $pembayaranStats['diterima'],
                        $pembayaranStats['ditolak'],
                    ],
                ],
            ],
            'booking_terbaru'  => Booking::with('lapangan')->latest()->take(5)->get(),
        ];
    }
}
