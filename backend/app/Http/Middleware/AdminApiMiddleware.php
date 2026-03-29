<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminApiMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('admin_id')) {
            return response()->json(['message' => 'Unauthorized. Silakan login terlebih dahulu.'], 401);
        }
        return $next($request);
    }
}
