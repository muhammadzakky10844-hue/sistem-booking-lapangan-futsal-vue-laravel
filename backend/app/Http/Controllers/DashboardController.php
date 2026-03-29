<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Lapangan;
use App\Models\Pembayaran;

class DashboardController extends Controller
{
    public function index()
    {
        $totalLapangan   = Lapangan::count();
        $lapanganTersedia = Lapangan::where('status', 'tersedia')->count();
        $lapanganPerbaikan = Lapangan::where('status', 'perbaikan')->count();

        $totalBooking    = Booking::count();
        $bookingMenunggu = Booking::where('status', 'menunggu')->count();
        $bookingTerkonfirmasi = Booking::where('status', 'terkonfirmasi')->count();
        $bookingSelesai  = Booking::where('status', 'selesai')->count();
        $bookingBatal    = Booking::where('status', 'batal')->count();

        $totalPembayaran = Pembayaran::count();
        $pembayaranMenunggu = Pembayaran::where('status_verifikasi', 'menunggu')->count();
        $pembayaranDiterima = Pembayaran::where('status_verifikasi', 'diterima')->count();
        $pembayaranDitolak  = Pembayaran::where('status_verifikasi', 'ditolak')->count();

        $totalPendapatan = Booking::where('status', 'selesai')->sum('total_harga');

        $bookingTerbaru = Booking::with('lapangan')->latest()->take(5)->get();

        return view('admin.dashboard', compact(
            'totalLapangan', 'lapanganTersedia', 'lapanganPerbaikan',
            'totalBooking', 'bookingMenunggu', 'bookingTerkonfirmasi', 'bookingSelesai', 'bookingBatal',
            'totalPembayaran', 'pembayaranMenunggu', 'pembayaranDiterima', 'pembayaranDitolak',
            'totalPendapatan', 'bookingTerbaru'
        ));
    }
}
