<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{AuthApiController, BookingApiController, DashboardApiController, LapanganApiController, MediaApiController, PembayaranApiController};

// Deployment fallback: ensure admin provider/guard exists even if server config/auth.php is outdated.
if (!config()->has('auth.providers.admins')) {
    config()->set('auth.providers.admins', [
        'driver' => 'eloquent',
        'model' => \App\Models\Admin::class,
    ]);
}

if (!config()->has('auth.guards.admin')) {
    config()->set('auth.guards.admin', [
        'driver' => 'jwt',
        'provider' => 'admins',
    ]);
}

// ─── Public Routes ───────────────────────────────────────────────────────────
Route::get('/lapangan',            [LapanganApiController::class, 'index']);
Route::post('/booking',            [BookingApiController::class, 'store']);
Route::get('/booking/jadwal',      [BookingApiController::class, 'jadwal']);
Route::get('/booking/{id}/detail', [BookingApiController::class, 'detail']);
Route::post('/pembayaran/upload',  [PembayaranApiController::class, 'upload']);
Route::get('/media/lapangan/{filename}', [MediaApiController::class, 'lapangan'])->where('filename', '.*');
Route::get('/media/bukti/{filename}',    [MediaApiController::class, 'bukti'])->where('filename', '.*');
Route::get('/media/image/{filename}',    [MediaApiController::class, 'image'])->where('filename', '.*');

// Auth
Route::post('/login',  [AuthApiController::class, 'login']);
Route::post('/logout', [AuthApiController::class, 'logout']);
Route::get('/me',      [AuthApiController::class, 'me']);

// ─── Admin Routes (session protected) ─────────────────────────────────────────
Route::middleware('admin.jwt')->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardApiController::class, 'index']);

    // Lapangan
    Route::get('/lapangan',           [LapanganApiController::class, 'adminIndex']);
    Route::post('/lapangan',          [LapanganApiController::class, 'store']);
    Route::get('/lapangan/{id}',      [LapanganApiController::class, 'show']);
    Route::post('/lapangan/{id}',     [LapanganApiController::class, 'update']);
    Route::delete('/lapangan/{id}',   [LapanganApiController::class, 'destroy']);

    // Booking
    Route::get('/booking',            [BookingApiController::class, 'adminIndex']);
    Route::post('/booking/konfirmasi',[BookingApiController::class, 'konfirmasi']);
    Route::post('/booking/batal',     [BookingApiController::class, 'batal']);
    Route::post('/booking/selesai',   [BookingApiController::class, 'selesai']);

    // Pembayaran
    Route::get('/pembayaran',         [PembayaranApiController::class, 'index']);
    Route::post('/pembayaran/terima', [PembayaranApiController::class, 'terima']);
    Route::post('/pembayaran/tolak',  [PembayaranApiController::class, 'tolak']);
});
