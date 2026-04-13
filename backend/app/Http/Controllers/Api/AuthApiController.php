<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthApiController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email'    => 'required|email',
                'password' => 'required|string',
            ]);

            // Self-healing for first deploys where startup migration did not run.
            if (!Schema::hasTable('admins')) {
                Schema::create('admins', function (Blueprint $table) {
                    $table->id();
                    $table->string('nama');
                    $table->string('email')->unique();
                    $table->string('password');
                    $table->timestamps();
                ]);

                DB::table('admins')->insert([
                    'nama' => 'Admin',
                    'email' => 'admin@gmail.com',
                    'password' => Hash::make('zakkyadmin123'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $admin = Admin::where('email', $request->email)->first();

            if (!$admin || !Hash::check($request->password, $admin->password)) {
                return response()->json(['message' => 'Email atau password salah.'], 401);
            }

            if (!is_string(config('jwt.secret')) || trim((string) config('jwt.secret')) === '') {
                return response()->json([
                    'message' => 'Konfigurasi JWT_SECRET belum valid di server.',
                ], 500);
            }

            $token = JWTAuth::fromUser($admin);

            return response()->json([
                'message' => 'Login berhasil.',
                'token'   => $token,
                'admin'   => [
                    'id'    => $admin->id,
                    'nama'  => $admin->nama,
                    'email' => $admin->email,
                ],
            ]);
        } catch (JWTException $e) {
            Log::error('JWT login error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal membuat token login. Periksa konfigurasi JWT di server.'], 500);
        } catch (\Throwable $e) {
            Log::error('Auth login error', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Terjadi kesalahan server saat login.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $token = $this->resolveToken($request);
            if (!$token) {
                return response()->json(['message' => 'Token tidak ditemukan. Silakan login terlebih dahulu.'], 401);
            }

            auth()->shouldUse('admin');
            JWTAuth::setToken($token)->invalidate();
            return response()->json(['message' => 'Logout berhasil.']);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token tidak valid atau sudah logout.'], 401);
        }
    }

    public function me(Request $request)
    {
        try {
            $token = $this->resolveToken($request);
            if (!$token) {
                return response()->json(['authenticated' => false], 401);
            }

            auth()->shouldUse('admin');
            $admin = JWTAuth::setToken($token)->authenticate();
            if (!$admin) {
                return response()->json(['authenticated' => false], 401);
            }

            return response()->json([
                'authenticated' => true,
                'admin' => [
                    'id'    => $admin->id,
                    'nama'  => $admin->nama,
                    'email' => $admin->email,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['authenticated' => false], 401);
        }
    }

    private function resolveToken(Request $request): ?string
    {
        $bearer = $request->bearerToken();
        if (is_string($bearer) && $bearer !== '') {
            return $bearer;
        }

        $fallback = $request->header('X-Auth-Token')
            ?: $request->header('X-Admin-Token')
            ?: $request->input('token');

        return is_string($fallback) && $fallback !== '' ? $fallback : null;
    }
}
