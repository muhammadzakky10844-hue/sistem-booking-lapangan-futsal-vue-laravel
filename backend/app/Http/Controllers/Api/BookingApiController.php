<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exports\BookingsExport;
use App\Models\{Booking, Lapangan};
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class BookingApiController extends Controller
{
    // GET /api/booking/jadwal?lapangan_id=&tanggal=
    public function jadwal(Request $request)
    {
        $request->validate([
            'lapangan_id' => 'required|exists:lapangans,lapangan_id',
            'tanggal'     => 'required|date',
        ]);

        $bookings = Booking::where('lapangan_id', $request->lapangan_id)
            ->where('tanggal_booking', $request->tanggal)
            ->whereIn('status', ['menunggu', 'terkonfirmasi'])
            ->get(['jam_mulai', 'jam_selesai']);

        return response()->json($bookings);
    }

    // POST /api/booking
    public function store(Request $request)
    {
        $request->validate([
            'nama_penyewa'    => 'required|string|max:255',
            'no_hp'           => 'required|string|max:20',
            'alamat'          => 'nullable|string|max:255',
            'lapangan_id'     => 'required|exists:lapangans,lapangan_id',
            'tanggal_booking' => 'required|date',
            'jam_mulai'       => 'required|date_format:H:i',
            'jam_selesai'     => 'required|date_format:H:i|after:jam_mulai',
        ]);

        // Cek bentrok jadwal (slot yang menempel tetap diizinkan)
        $cek = Booking::where('lapangan_id', $request->lapangan_id)
            ->where('tanggal_booking', $request->tanggal_booking)
            ->whereIn('status', ['menunggu', 'terkonfirmasi'])
            ->where(function ($query) use ($request) {
                // Overlap hanya jika existing_start < new_end DAN existing_end > new_start
                $query->where('jam_mulai', '<', $request->jam_selesai)
                    ->where('jam_selesai', '>', $request->jam_mulai);
            })->exists();

        if ($cek) {
            return response()->json(['message' => 'Jadwal sudah dibooking pada jam tersebut.'], 422);
        }

        $lapangan = Lapangan::findOrFail($request->lapangan_id);
        $lama_jam = (strtotime($request->jam_selesai) - strtotime($request->jam_mulai)) / 3600;
        $total    = $lama_jam * $lapangan->harga_per_jam;

        $booking = Booking::create([
            'nama_penyewa'    => $request->nama_penyewa,
            'no_hp'           => $request->no_hp,
            'alamat'          => $request->alamat,
            'lapangan_id'     => $request->lapangan_id,
            'tanggal_booking' => $request->tanggal_booking,
            'jam_mulai'       => $request->jam_mulai,
            'jam_selesai'     => $request->jam_selesai,
            'total_harga'     => $total,
            'status'          => 'menunggu',
        ]);

        return response()->json([
            'message' => 'Booking berhasil dibuat.',
            'booking' => $booking,
        ], 201);
    }

    // GET /api/booking/{id}/detail
    public function detail($id)
    {
        $booking = Booking::with(['lapangan', 'pembayaran'])->findOrFail($id);
        return response()->json($booking);
    }

    // ─── Admin ───────────────────────────────────────────────────────────────

    public function adminIndex(Request $request)
    {
        return response()->json($this->buildAdminQuery($request)->get());
    }

    public function exportExcel(Request $request)
    {
        $bookings = $this->buildAdminQuery($request)->get();
        $fileName = 'laporan-booking-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new BookingsExport($bookings), $fileName);
    }

    public function exportPdf(Request $request)
    {
        $bookings = $this->buildAdminQuery($request)->get();

        $pdf = Pdf::loadView('exports.bookings', [
            'bookings' => $bookings,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-booking-' . now()->format('Ymd-His') . '.pdf');
    }

    public function konfirmasi(Request $request)
    {
        $request->validate(['booking_id' => 'required|exists:bookings,booking_id']);
        $booking = Booking::findOrFail($request->booking_id);
        $booking->update(['status' => 'terkonfirmasi']);
        return response()->json(['message' => 'Booking berhasil dikonfirmasi.']);
    }

    public function batal(Request $request)
    {
        $request->validate(['booking_id' => 'required|exists:bookings,booking_id']);
        $booking = Booking::findOrFail($request->booking_id);
        $booking->update(['status' => 'batal']);
        return response()->json(['message' => 'Booking berhasil dibatalkan.']);
    }

    public function selesai(Request $request)
    {
        $request->validate(['booking_id' => 'required|exists:bookings,booking_id']);
        $booking = Booking::findOrFail($request->booking_id);
        $booking->update(['status' => 'selesai']);
        return response()->json(['message' => 'Booking ditandai selesai.']);
    }

    private function buildAdminQuery(Request $request)
    {
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

        return $query;
    }
}
