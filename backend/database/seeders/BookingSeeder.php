<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Lapangan;
use App\Models\Pembayaran;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $penyewas = [
            ['nama' => 'Zakky',  'no_hp' => '081234567890', 'alamat' => 'Jl. Merdeka No. 1, Jakarta'],
            ['nama' => 'Apis',   'no_hp' => '082345678901', 'alamat' => 'Jl. Sudirman No. 12, Bandung'],
            ['nama' => 'Padil',  'no_hp' => '083456789012', 'alamat' => 'Jl. Diponegoro No. 5, Surabaya'],
            ['nama' => 'Nabil',  'no_hp' => '084567890123', 'alamat' => 'Jl. Ahmad Yani No. 8, Bekasi'],
            ['nama' => 'Afdal',  'no_hp' => '085678901234', 'alamat' => 'Jl. Gatot Subroto No. 3, Depok'],
            ['nama' => 'Arjun',  'no_hp' => '086789012345', 'alamat' => 'Jl. Hayam Wuruk No. 7, Bogor'],
            ['nama' => 'Angga',  'no_hp' => '087890123456', 'alamat' => 'Jl. Veteran No. 20, Tangerang'],
        ];

        $lapangans = Lapangan::all()->keyBy('nomor_lapangan');

        $bookings = [
            // Zakky - selesai
            ['penyewa' => 0, 'lap' => 'A1', 'tgl' => '2026-02-10', 'mulai' => '08:00', 'selesai' => '10:00', 'jam' => 2, 'status' => 'selesai', 'bayar' => ['metode' => 'Dana', 'verifikasi' => 'diterima']],
            // Apis - selesai
            ['penyewa' => 1, 'lap' => 'B2', 'tgl' => '2026-02-12', 'mulai' => '10:00', 'selesai' => '12:00', 'jam' => 2, 'status' => 'selesai', 'bayar' => ['metode' => 'GoPay', 'verifikasi' => 'diterima']],
            // Padil - terkonfirmasi
            ['penyewa' => 2, 'lap' => 'A3', 'tgl' => '2026-03-08', 'mulai' => '13:00', 'selesai' => '15:00', 'jam' => 2, 'status' => 'terkonfirmasi', 'bayar' => ['metode' => 'Transfer Bank', 'verifikasi' => 'diterima']],
            // Nabil - terkonfirmasi
            ['penyewa' => 3, 'lap' => 'B1', 'tgl' => '2026-03-09', 'mulai' => '15:00', 'selesai' => '17:00', 'jam' => 2, 'status' => 'terkonfirmasi', 'bayar' => ['metode' => 'OVO', 'verifikasi' => 'diterima']],
            // Afdal - menunggu
            ['penyewa' => 4, 'lap' => 'A2', 'tgl' => '2026-03-10', 'mulai' => '09:00', 'selesai' => '11:00', 'jam' => 2, 'status' => 'menunggu', 'bayar' => ['metode' => 'Dana', 'verifikasi' => 'menunggu']],
            // Arjun - menunggu
            ['penyewa' => 5, 'lap' => 'B3', 'tgl' => '2026-03-10', 'mulai' => '14:00', 'selesai' => '16:00', 'jam' => 2, 'status' => 'menunggu', 'bayar' => ['metode' => 'GoPay', 'verifikasi' => 'menunggu']],
            // Angga - batal
            ['penyewa' => 6, 'lap' => 'B4', 'tgl' => '2026-02-20', 'mulai' => '17:00', 'selesai' => '19:00', 'jam' => 2, 'status' => 'batal', 'bayar' => ['metode' => 'Tunai', 'verifikasi' => 'ditolak']],
            // Zakky - menunggu (booking ke-2)
            ['penyewa' => 0, 'lap' => 'B5', 'tgl' => '2026-03-11', 'mulai' => '19:00', 'selesai' => '21:00', 'jam' => 2, 'status' => 'menunggu', 'bayar' => ['metode' => 'Dana', 'verifikasi' => 'menunggu']],
            // Apis - selesai (booking ke-2)
            ['penyewa' => 1, 'lap' => 'A4', 'tgl' => '2026-02-25', 'mulai' => '08:00', 'selesai' => '09:00', 'jam' => 1, 'status' => 'selesai', 'bayar' => ['metode' => 'Transfer Bank', 'verifikasi' => 'diterima']],
            // Nabil - batal
            ['penyewa' => 3, 'lap' => 'A1', 'tgl' => '2026-02-18', 'mulai' => '20:00', 'selesai' => '22:00', 'jam' => 2, 'status' => 'batal', 'bayar' => ['metode' => 'OVO', 'verifikasi' => 'ditolak']],
            // Padil - selesai
            ['penyewa' => 2, 'lap' => 'B2', 'tgl' => '2026-02-28', 'mulai' => '16:00', 'selesai' => '18:00', 'jam' => 2, 'status' => 'selesai', 'bayar' => ['metode' => 'Dana', 'verifikasi' => 'diterima']],
            // Afdal - terkonfirmasi
            ['penyewa' => 4, 'lap' => 'B4', 'tgl' => '2026-03-12', 'mulai' => '10:00', 'selesai' => '12:00', 'jam' => 2, 'status' => 'terkonfirmasi', 'bayar' => ['metode' => 'GoPay', 'verifikasi' => 'diterima']],
            // Arjun - selesai
            ['penyewa' => 5, 'lap' => 'A2', 'tgl' => '2026-02-15', 'mulai' => '07:00', 'selesai' => '09:00', 'jam' => 2, 'status' => 'selesai', 'bayar' => ['metode' => 'Transfer Bank', 'verifikasi' => 'diterima']],
            // Angga - menunggu
            ['penyewa' => 6, 'lap' => 'A3', 'tgl' => '2026-03-13', 'mulai' => '13:00', 'selesai' => '14:00', 'jam' => 1, 'status' => 'menunggu', 'bayar' => ['metode' => 'Tunai', 'verifikasi' => 'menunggu']],
            // ── CONTOH LAPANGAN FULL HARI INI (A1, 07:00-22:00) ──
            ['penyewa' => 0, 'lap' => 'A1', 'tgl' => Carbon::today()->toDateString(), 'mulai' => '07:00', 'selesai' => '22:00', 'jam' => 15, 'status' => 'terkonfirmasi', 'bayar' => ['metode' => 'Transfer Bank', 'verifikasi' => 'diterima']],
        ];

        foreach ($bookings as $item) {
            $p     = $penyewas[$item['penyewa']];
            $lap   = $lapangans[$item['lap']];
            $total = $lap->harga_per_jam * $item['jam'];

            $booking = Booking::create([
                'nama_penyewa'   => $p['nama'],
                'no_hp'          => $p['no_hp'],
                'alamat'         => $p['alamat'],
                'lapangan_id'    => $lap->lapangan_id,
                'tanggal_booking'=> $item['tgl'],
                'jam_mulai'      => $item['mulai'],
                'jam_selesai'    => $item['selesai'],
                'total_harga'    => $total,
                'status'         => $item['status'],
            ]);

            Pembayaran::create([
                'booking_id'        => $booking->booking_id,
                'metode_pembayaran' => $item['bayar']['metode'],
                'bukti_pembayaran'  => null,
                'status_verifikasi' => $item['bayar']['verifikasi'],
            ]);
        }
    }
}
