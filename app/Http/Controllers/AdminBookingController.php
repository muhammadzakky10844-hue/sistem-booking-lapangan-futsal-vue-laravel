<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Lapangan;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    public function index(Request $request){
        $query = Booking::with('lapangan')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('lapangan_id')) {
            $query->where('lapangan_id', $request->lapangan_id);
        }
        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal_booking', $request->tanggal);
        }
        if ($request->filled('search')) {
            $query->where('nama_penyewa', 'like', '%' . $request->search . '%');
        }

        $bookings  = $query->get();
        $lapangans = Lapangan::orderBy('nomor_lapangan')->get();
        return view('admin.bookings.index', compact('bookings', 'lapangans'));
    }

    public function konfirmasi(Request $request){
        $booking = Booking::findOrFail($request->booking_id);

        $booking->update([
            'status' => 'terkonfirmasi'
        ]);
        return back()->with('success', 'Booking berhasil dikonfirmasi!');
    }

    public function batal(Request $request){
        $booking = Booking::findOrFail($request->booking_id);

        $booking->update([
            'status' => 'batal'
        ]);
        return back()->with('success', 'Booking berhasil dibatalkan!');
    }

    public function selesai(Request $request){
        $booking = Booking::findOrFail($request->booking_id);

        $booking->update([
            'status' => 'selesai'
        ]);
        return back()->with('success', 'Booking telah diselesaikan!');
    }
}
