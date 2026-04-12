<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $token = $this->resolveToken($request);
            if (!$token) {
                return response()->json(['message' => 'Token tidak ditemukan. Silakan login terlebih dahulu.'], 401);
            }

            auth()->shouldUse('admin');
            $admin = JWTAuth::setToken($token)->authenticate();
            if (!$admin || !($admin instanceof Admin)) {
                return response()->json(['message' => 'Unauthorized. Silakan login terlebih dahulu.'], 401);
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'Token sudah kadaluarsa. Silakan login ulang.'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'Token tidak valid.'], 401);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token tidak ditemukan. Silakan login terlebih dahulu.'], 401);
        }

        return $next($request);
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
