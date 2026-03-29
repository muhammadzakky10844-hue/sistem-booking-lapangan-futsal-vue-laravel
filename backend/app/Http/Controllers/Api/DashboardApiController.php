<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Lapangan;
use App\Models\Pembayaran;

class DashboardApiController extends Controller
{
    public function index()
    {
        return response()->json([
            'lapangan' => [
                'total'     => Lapangan::count(),
                'tersedia'  => Lapangan::where('status', 'tersedia')->count(),
                'perbaikan' => Lapangan::where('status', 'perbaikan')->count(),
            ],
            'booking' => [
                'total'        => Booking::count(),
                'menunggu'     => Booking::where('status', 'menunggu')->count(),
                'terkonfirmasi'=> Booking::where('status', 'terkonfirmasi')->count(),
                'selesai'      => Booking::where('status', 'selesai')->count(),
                'batal'        => Booking::where('status', 'batal')->count(),
            ],
            'pembayaran' => [
                'total'    => Pembayaran::count(),
                'menunggu' => Pembayaran::where('status_verifikasi', 'menunggu')->count(),
                'diterima' => Pembayaran::where('status_verifikasi', 'diterima')->count(),
                'ditolak'  => Pembayaran::where('status_verifikasi', 'ditolak')->count(),
            ],
            'total_pendapatan' => Booking::where('status', 'selesai')->sum('total_harga'),
            'booking_terbaru'  => Booking::with('lapangan')->latest()->take(5)->get(),
        ]);
    }
}
