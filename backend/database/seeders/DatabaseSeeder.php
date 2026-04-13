<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Lapangan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'nama'     => 'Admin',
                'password' => Hash::make('zakkyadmin123'),
            ]
        );

        if (Lapangan::count() === 0) {
            $this->call([
                LapanganSeeder::class,
            ]);
        }

        if (Booking::count() === 0) {
            $this->call([
                BookingSeeder::class,
            ]);
        }
    }
}
