<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Booking;
use Illuminate\Http\Request;

class PembayaranApiController extends Controller
{
    // POST /api/pembayaran/upload (public — penyewa upload bukti)
    public function upload(Request $request)
    {
        $request->validate([
            'booking_id'        => 'required|exists:bookings,booking_id',
            'metode_pembayaran' => 'required|string|max:255',
            'bukti_pembayaran'  => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $file     = $request->file('bukti_pembayaran');
        $namaFile = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('bukti_pembayaran'), $namaFile);

        // Hapus file lama jika ada
        $existing = Pembayaran::where('booking_id', $request->booking_id)->first();
        if ($existing && $existing->bukti_pembayaran) {
            $oldPath = public_path('bukti_pembayaran/' . $existing->bukti_pembayaran);
            if (file_exists($oldPath)) unlink($oldPath);
        }

        $pembayaran = Pembayaran::updateOrCreate(
            ['booking_id' => $request->booking_id],
            [
                'metode_pembayaran' => $request->metode_pembayaran,
                'bukti_pembayaran'  => $namaFile,
                'status_verifikasi' => 'menunggu',
            ]
        );

        return response()->json(['message' => 'Bukti pembayaran berhasil diupload.', 'pembayaran' => $pembayaran], 201);
    }

    // ─── Admin ───────────────────────────────────────────────────────────────

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

        return response()->json($query->get());
    }

    public function terima(Request $request)
    {
        $request->validate(['pembayaran_id' => 'required|exists:pembayarans,pembayaran_id']);
        $pembayaran = Pembayaran::findOrFail($request->pembayaran_id);
        $pembayaran->update(['status_verifikasi' => 'diterima']);
        $pembayaran->booking->update(['status' => 'terkonfirmasi']);
        return response()->json(['message' => 'Pembayaran berhasil diterima.']);
    }

    public function tolak(Request $request)
    {
        $request->validate(['pembayaran_id' => 'required|exists:pembayarans,pembayaran_id']);
        $pembayaran = Pembayaran::findOrFail($request->pembayaran_id);
        $pembayaran->update(['status_verifikasi' => 'ditolak']);
        $pembayaran->booking->update(['status' => 'menunggu']);
        return response()->json(['message' => 'Pembayaran ditolak.']);
    }
}
