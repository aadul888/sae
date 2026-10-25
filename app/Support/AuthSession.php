<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Jembatan autentikasi antara sesi lama dan guard Laravel.
 *
 * Latar belakang: aplikasi ini sejak awal menyimpan data pengguna pada
 * `session('user')` sebagai array (353 titik pemakaian). Guard Laravel
 * (`Auth::user()`) tidak pernah diisi, sehingga fitur bawaan framework
 * seperti `auth` middleware, `@auth` di Blade, rate limiting per pengguna,
 * dan Sanctum tidak dapat bekerja optimal.
 *
 * Strategi migrasi bertahap (tanpa memutus alur yang berjalan):
 *  1. Setiap login kini memanggil `Auth::login($user)` SEKALIGUS mengisi
 *     `session('user')` lewat helper ini.
 *  2. Kode lama tetap berjalan karena `session('user')` masih tersedia.
 *  3. Kode baru sebaiknya memakai `Auth::user()` / `auth()->user()`.
 *  4. Setelah seluruh pemakaian lama dimigrasi, `session('user')` dapat
 *     dihapus dari helper ini tanpa mengubah pemanggil.
 */
final class AuthSession
{
    /**
     * Masuk sebagai pengguna: isi guard Laravel dan sesi aplikasi.
     *
     * @param  array<string, mixed>  $userData  Data siap pakai untuk view & controller.
     */
    public static function login(User $user, array $userData, bool $remember = false): void
    {
        // 1. Guard Laravel (sumber kebenaran baru).
        Auth::login($user, $remember);

        // 2. Sesi aplikasi (kompatibilitas kode lama).
        session(['user' => $userData]);
    }

    /**
     * Keluar: bersihkan guard dan sesi aplikasi.
     */
    public static function logout(): void
    {
        Auth::logout();
        session()->forget('user');
    }

    /**
     * Pengguna aktif dari guard Laravel.
     *
     * Mengembalikan model User bila guard sudah terisi; bila belum (mis. sesi
     * lama yang dibuat sebelum migrasi), fallback ke sesi aplikasi.
     */
    public static function user(): ?User
    {
        $authUser = Auth::user();
        if ($authUser instanceof User) {
            return $authUser;
        }

        // Fallback: bangun model dari sesi lama tanpa query tambahan bila mungkin.
        $sessionUser = session('user');
        if (!$sessionUser) {
            return null;
        }

        $id = is_array($sessionUser)
            ? ($sessionUser['pengguna_id'] ?? ($sessionUser['id'] ?? null))
            : ($sessionUser->pengguna_id ?? ($sessionUser->id ?? null));

        return $id ? User::find($id) : null;
    }

    /**
     * Apakah pengguna sudah terautentikasi (guard atau sesi lama)?
     */
    public static function check(): bool
    {
        return Auth::check() || session()->has('user');
    }
}
