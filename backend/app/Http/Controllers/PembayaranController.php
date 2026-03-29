<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pembayaran;
use App\Models\Booking;

class PembayaranController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'booking_id'          => 'required|exists:bookings,booking_id',
            'metode_pembayaran'   => 'required|string|max:255',
            'bukti_pembayaran'    => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $file      = $request->file('bukti_pembayaran');
        $nama_file = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('bukti_pembayaran'), $nama_file);

        Pembayaran::create([
            'booking_id'        => $request->booking_id,
            'metode_pembayaran' => $request->metode_pembayaran,
            'bukti_pembayaran'  => $nama_file,
            'status_verifikasi' => 'menunggu',
        ]);

        return back()->with('success', 'Bukti pembayaran berhasil diupload!');
    }

    public function terima(Request $request)
    {
        $pembayaran = Pembayaran::findOrFail($request->pembayaran_id);
        $pembayaran->update(['status_verifikasi' => 'diterima']);

        // Update status booking jadi terkonfirmasi
        $pembayaran->booking->update(['status' => 'terkonfirmasi']);

        return back()->with('success', 'Pembayaran berhasil diterima!');
    }

    public function tolak(Request $request)
    {
        $pembayaran = Pembayaran::findOrFail($request->pembayaran_id);
        $pembayaran->update(['status_verifikasi' => 'ditolak']);

        // Kembalikan status booking ke menunggu
        $pembayaran->booking->update(['status' => 'menunggu']);

        return back()->with('success', 'Pembayaran ditolak!');
    }

    public function index(Request $request)
    {
        $query = Pembayaran::with('booking.lapangan')->latest();

        if ($request->filled('status')) {
            $query->where('status_verifikasi', $request->status);
        }
        if ($request->filled('search')) {
            $query->whereHas('booking', function ($q) use ($request) {
                $q->where('nama_penyewa', 'like', '%' . $request->search . '%');
            });
        }

        $pembayarans = $query->get();
        return view('admin.pembayaran.index', compact('pembayarans'));
    }
}
