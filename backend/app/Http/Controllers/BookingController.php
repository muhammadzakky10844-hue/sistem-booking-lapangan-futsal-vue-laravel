<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Booking, Lapangan};
use App\Models\Pembayaran;

class BookingController extends Controller
{
    public function pembayaranForm($booking_id)
    {
        $booking = Booking::with('lapangan')->findOrFail($booking_id);
        return view('booking.pembayaran', compact('booking'));
    }

    public function home(){
        $lapangans = Lapangan::where('status', 'tersedia')->get();
        return view('booking.home', compact('lapangans'));
    }

    public function create(Request $request){
        $lapangans = Lapangan::where('status', 'tersedia')->get();
        $selectedLapangan = $request->lapangan_id
            ? Lapangan::where('lapangan_id', $request->lapangan_id)->where('status', 'tersedia')->first()
            : null;
        return view('booking.create', compact('lapangans', 'selectedLapangan'));
    }

    public function store(Request $request){
        $request->validate([
            'nama_penyewa' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20',
            'alamat' => 'string|max:255',
            'lapangan_id' => 'required|exists:lapangans,lapangan_id',
            'tanggal_booking' => 'required|date',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
        ]);

        $cek = Booking::where('lapangan_id', $request->lapangan_id)
        ->where('tanggal_booking', $request->tanggal_booking)
        ->where(function($query) use ($request) {
            $query->whereBetween('jam_mulai', [$request->jam_mulai, $request->jam_selesai])
              ->orWhereBetween('jam_selesai', [$request->jam_mulai, $request->jam_selesai])
              ->orWhere(function($q) use ($request) {
                  $q->where('jam_mulai', '<=', $request->jam_mulai)
                    ->where('jam_selesai', '>=', $request->jam_selesai);
              });
    })->exists();

    if($cek){
        return back()->withInput()->with('error', 'Jadwal sudah dibooking !');
    }

    $lapangan = Lapangan::find($request->lapangan_id);

    $jam_mulai = strtotime($request->jam_mulai);
    $jam_selesai = strtotime($request->jam_selesai);

    $lama_jam = ($jam_selesai - $jam_mulai) / 3600;
    $total = $lama_jam  * $lapangan->harga_per_jam;

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

        return redirect()->route('pembayaran.form', ['booking_id' => $booking->booking_id])
            ->with('success', 'Booking berhasil dibuat! Silakan lakukan pembayaran.');
    }

    public function getJadwal(Request $request){
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
}
