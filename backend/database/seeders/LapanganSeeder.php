<?php

namespace Database\Seeders;

use App\Models\Lapangan;
use Illuminate\Database\Seeder;

class LapanganSeeder extends Seeder
{
    public function run(): void
    {
        $vinyl    = 'lapangan futsal 2.jpg';
        $sintetis = 'lapangan futsal 3.jpg';

        $lapangans = [
            ['nomor_lapangan' => 'A1', 'harga_per_jam' => 150000, 'deskripsi' => 'Lapangan futsal premium kelas A, rumput sintetis kualitas tinggi, pencahayaan LED, AC.',            'status' => 'tersedia',  'gambar' => $sintetis],
            ['nomor_lapangan' => 'A2', 'harga_per_jam' => 150000, 'deskripsi' => 'Lapangan futsal premium kelas A, lantai vinyl anti-slip, tribun penonton, pencahayaan terang.',     'status' => 'tersedia',  'gambar' => $vinyl],
            ['nomor_lapangan' => 'A3', 'harga_per_jam' => 150000, 'deskripsi' => 'Lapangan futsal premium kelas A, rumput sintetis baru, ruang ganti eksklusif, parkir luas.',        'status' => 'tersedia',  'gambar' => $sintetis],
            ['nomor_lapangan' => 'A4', 'harga_per_jam' => 150000, 'deskripsi' => 'Lapangan futsal premium kelas A, lantai vinyl, gawang standar futsal, full AC.',                    'status' => 'tersedia',  'gambar' => $vinyl],
            ['nomor_lapangan' => 'A5', 'harga_per_jam' => 150000, 'deskripsi' => 'Lapangan futsal premium kelas A, rumput sintetis FIFA approved, tersedia loker dan shower.',        'status' => 'perbaikan', 'gambar' => $sintetis],
            ['nomor_lapangan' => 'B1', 'harga_per_jam' => 100000, 'deskripsi' => 'Lapangan futsal standar kelas B, lantai vinyl, kondisi baik dan siap digunakan, cocok latihan.',   'status' => 'tersedia',  'gambar' => $vinyl],
            ['nomor_lapangan' => 'B2', 'harga_per_jam' => 100000, 'deskripsi' => 'Lapangan futsal standar kelas B, lantai vinyl, pencahayaan cukup, harga terjangkau.',              'status' => 'tersedia',  'gambar' => $vinyl],
            ['nomor_lapangan' => 'B3', 'harga_per_jam' => 100000, 'deskripsi' => 'Lapangan futsal standar kelas B, rumput sintetis, lokasi strategis dan mudah dijangkau.',          'status' => 'tersedia',  'gambar' => $sintetis],
            ['nomor_lapangan' => 'B4', 'harga_per_jam' => 100000, 'deskripsi' => 'Lapangan futsal standar kelas B, rumput sintetis, tersedia area parkir, cocok turnamen kecil.',    'status' => 'tersedia',  'gambar' => $sintetis],
            ['nomor_lapangan' => 'B5', 'harga_per_jam' => 100000, 'deskripsi' => 'Lapangan futsal standar kelas B, lantai vinyl anti-slip, pencahayaan memadai, ruang istirahat.',   'status' => 'tersedia',  'gambar' => $vinyl],
        ];

        foreach ($lapangans as $data) {
            Lapangan::create($data);
        }
    }
}
