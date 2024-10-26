<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // dd(Auth::user());
        if (!Auth::guard('sanctum')->check()) {
            return response()->json([
                'message' => 'Anda harus login untuk mengakses halaman ini.'
            ], Response::HTTP_UNAUTHORIZED); // Kode 401
        }

        return $next($request);
    }
}
