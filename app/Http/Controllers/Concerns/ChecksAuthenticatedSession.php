<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;

/**
 * Pemeriksaan sesi pengguna untuk controller dashboard.
 *
 * Sebelumnya method `checkAuth()` yang isinya persis sama disalin ke 20
 * controller. Trait ini menjadi satu-satunya sumber implementasi agar
 * perubahan aturan autentikasi cukup dilakukan di satu tempat.
 *
 * Catatan: aplikasi ini memakai sesi manual (`session('user')`) yang diisi
 * oleh AuthController, bukan guard Laravel. Bila autentikasi kelak dimigrasi
 * ke guard, cukup ubah implementasi di trait ini.
 */
trait ChecksAuthenticatedSession
{
    /**
     * Pastikan pengguna sudah masuk. Kembalikan response redirect bila belum.
     */
    protected function checkAuth(): ?RedirectResponse
    {
        if (!session()->has('user')) {
            return redirect()->route('login');
        }

        return null;
    }

    /**
     * Ambil data pengguna dari sesi (array maupun objek).
     */
    protected function sessionUser(): mixed
    {
        return session('user');
    }

    /**
     * Ambil peran pengguna dari sesi.
     */
    protected function sessionRole(): string
    {
        $user = $this->sessionUser();

        $role = is_array($user)
            ? ($user['role'] ?? '')
            : ($user->role ?? '');

        return (string) $role;
    }
}
