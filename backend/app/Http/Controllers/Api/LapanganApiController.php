<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Log, Schema};
use App\Http\Controllers\Controller;
use App\Models\{Booking, Lapangan};
use Database\Seeders\LapanganSeeder;
use Throwable;

class LapanganApiController extends Controller
{
    // Public: list lapangan tersedia (untuk halaman home penyewa)
    public function index()
    {
        if (Lapangan::count() === 0) {
            (new LapanganSeeder())->run();
        }

        try {
            $lapangans = Lapangan::where('status', 'tersedia')
                ->orderBy('nomor_lapangan')
                ->get();
        } catch (Throwable $e) {
            Log::error('Gagal mengambil data lapangan.', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([]);
        }

        $today = now()->toDateString();

        $canReadBookings = false;
        try {
            $canReadBookings = Schema::hasTable('bookings');
        } catch (Throwable $e) {
            Log::warning('Tidak bisa cek tabel bookings.', [
                'message' => $e->getMessage(),
            ]);
        }

        $lapangans->each(function ($lap) use ($today, $canReadBookings) {
            $bookings = collect();

            if ($canReadBookings) {
                try {
                    $bookings = Booking::where('lapangan_id', $lap->lapangan_id)
                        ->where('tanggal_booking', $today)
                        ->whereIn('status', ['menunggu', 'terkonfirmasi'])
                        ->get(['jam_mulai', 'jam_selesai']);
                } catch (Throwable $e) {
                    Log::warning('Gagal membaca booking untuk lapangan.', [
                        'lapangan_id' => $lap->lapangan_id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            // Tandai tiap slot per jam (07:00-22:00) apakah terbooked atau tidak
            $slots = [];
            for ($h = 7; $h < 22; $h++) {
                $startMin = $h * 60;
                $endMin   = ($h + 1) * 60;
                $startStr = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
                $booked   = $bookings->contains(function ($b) use ($startMin, $endMin) {
                    $bStart = (int) explode(':', $b->jam_mulai)[0] * 60 + (int) explode(':', $b->jam_mulai)[1];
                    $bEnd   = (int) explode(':', $b->jam_selesai)[0] * 60 + (int) explode(':', $b->jam_selesai)[1];
                    return $startMin < $bEnd && $endMin > $bStart;
                });
                $slots[] = ['jam' => $startStr, 'terisi' => $booked];
            }

            $bookedCount = collect($slots)->where('terisi', true)->count();
            $lap->slots_hari_ini = $slots;
            $lap->is_full_today  = $bookedCount >= 15; // 15 slot = semua jam penuh
        });

        return response()->json($lapangans);
    }

    // Admin: list semua lapangan dengan filter
    public function adminIndex(Request $request)
    {
        $query = Lapangan::query();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('nomor_lapangan', 'like', '%' . $request->search . '%');
        }
        $lapangans = $query->orderBy('nomor_lapangan')->get();
        return response()->json($lapangans);
    }

    public function show($id)
    {
        $lapangan = Lapangan::findOrFail($id);
        return response()->json($lapangan);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nomor_lapangan' => 'required|string|max:255|unique:lapangans,nomor_lapangan',
            'harga_per_jam'  => 'required|integer|min:1',
            'deskripsi'      => 'nullable|string',
            'gambar'         => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'status'         => 'required|in:tersedia,perbaikan',
        ]);

        $namaGambar = null;
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $namaGambar = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('images/lapangan'), $namaGambar);
        }

        $lapangan = Lapangan::create([
            'nomor_lapangan' => $request->nomor_lapangan,
            'harga_per_jam'  => $request->harga_per_jam,
            'deskripsi'      => $request->deskripsi,
            'gambar'         => $namaGambar,
            'status'         => $request->status,
        ]);

        return response()->json(['message' => 'Lapangan berhasil ditambahkan.', 'lapangan' => $lapangan], 201);
    }

    public function update(Request $request, $id)
    {
        $lapangan = Lapangan::findOrFail($id);

        $request->validate([
            'nomor_lapangan' => 'required|string|max:255|unique:lapangans,nomor_lapangan,' . $id . ',lapangan_id',
            'harga_per_jam'  => 'required|integer|min:1',
            'deskripsi'      => 'nullable|string',
            'gambar'         => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'status'         => 'required|in:tersedia,perbaikan',
        ]);

        $namaGambar = $lapangan->gambar;
        if ($request->hasFile('gambar')) {
            if ($namaGambar && file_exists(public_path('images/lapangan/' . $namaGambar))) {
                unlink(public_path('images/lapangan/' . $namaGambar));
            }
            $file = $request->file('gambar');
            $namaGambar = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('images/lapangan'), $namaGambar);
        }

        $lapangan->update([
            'nomor_lapangan' => $request->nomor_lapangan,
            'harga_per_jam'  => $request->harga_per_jam,
            'deskripsi'      => $request->deskripsi,
            'gambar'         => $namaGambar,
            'status'         => $request->status,
        ]);

        return response()->json(['message' => 'Lapangan berhasil diupdate.', 'lapangan' => $lapangan]);
    }

    public function destroy($id)
    {
        $lapangan = Lapangan::findOrFail($id);
        if ($lapangan->gambar && file_exists(public_path('images/lapangan/' . $lapangan->gambar))) {
            unlink(public_path('images/lapangan/' . $lapangan->gambar));
        }
        $lapangan->delete();
        return response()->json(['message' => 'Lapangan berhasil dihapus.']);
    }
}
