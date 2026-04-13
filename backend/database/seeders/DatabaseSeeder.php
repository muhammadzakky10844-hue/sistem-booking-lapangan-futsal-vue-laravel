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
        Admin::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'nama'     => 'Admin',
                'password' => Hash::make('zakkyadmin123'),
            ]
        );

        // Keep production seed idempotent and lightweight.
        if (app()->environment(['local', 'testing'])) {
            $this->call([
                LapanganSeeder::class,
                BookingSeeder::class,
            ]);
        }
    }
}
