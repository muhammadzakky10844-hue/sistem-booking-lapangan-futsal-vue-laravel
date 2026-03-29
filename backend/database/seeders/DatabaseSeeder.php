<?php

namespace Database\Seeders;

use App\Models\Admin;
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
        Admin::create([
            'nama'     => 'Admin',
            'email'    => 'admin@gmail.com',
            'password' => Hash::make('zakkyadmin123'),
        ]);

        $this->call([
            LapanganSeeder::class,
            BookingSeeder::class,
        ]);
    }
}
