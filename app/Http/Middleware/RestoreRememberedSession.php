<?php

namespace App\Http\Middleware;

use App\Support\AuthSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pulihkan `session('user')` dari cookie "ingat saya" (remember token).
 *
 * Aplikasi SAE membaca identitas pengguna dari `session('user')`. Bila sesi
 * server kedaluwarsa (mis. PWA dibuka lagi beberapa hari kemudian) namun cookie
 * remember masih valid, guard Laravel memulihkan pengguna lewat recaller —
 * tetapi `session('user')` tetap kosong sehingga pengguna terlempar ke login.
 *
 * Middleware ini mengisi ulang `session('user')` dari `Auth::user()` agar
 * pengguna langsung kembali ke berandanya tanpa login lagi.
 *
 * Hanya berlaku bila cookie remember ada. Cookie itu dibuat hanya saat login
 * dari mode PWA (lihat AuthController), sehingga browser biasa tidak terpengaruh.
 */
class RestoreRememberedSession
{
    public function handle(Request $request, Closure $next): Response
    {
        // Sudah ada sesi aplikasi → tidak ada yang perlu dipulihkan.
        if (session()->has('user')) {
            return $next($request);
        }

        // Guard memulihkan pengguna dari cookie remember (bila ada & valid).
        $user = Auth::user();

        if ($user instanceof \App\Models\User) {
            session(['user' => AuthSession::dataFor($user)]);
        }

        return $next($request);
    }
}
