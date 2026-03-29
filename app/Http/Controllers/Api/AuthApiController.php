<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthApiController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json(['message' => 'Email atau password salah.'], 401);
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
    }

    public function logout(Request $request)
    {
        try {
            $token = $this->resolveToken($request);
            if (!$token) {
                return response()->json(['message' => 'Token tidak ditemukan. Silakan login terlebih dahulu.'], 401);
            }

            auth()->shouldUse('admin');
            auth('admin')->setToken($token)->logout();
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
            $admin = auth('admin')->setToken($token)->user();
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
