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
     *
     * Sesi lama / hasil pemulihan (remember token) bisa saja tidak memuat key
     * 'role'; turunkan dari 'peran_id_str' agar tidak salah dikenali (mis. siswa
     * dianggap staf). Logika sama dengan DashboardController::checkAuth().
     */
    protected function sessionRole(): string
    {
        $user = $this->sessionUser();

        $role = (string) (is_array($user)
            ? ($user['role'] ?? '')
            : ($user->role ?? ''));

        if ($role !== '') {
            return $role;
        }

        $peran = strtolower(trim((string) (is_array($user)
            ? ($user['peran_id_str'] ?? '')
            : ($user->peran_id_str ?? ''))));

        $resolved = match (true) {
            str_contains($peran, 'admin'),
            str_contains($peran, 'operator'),
            str_contains($peran, 'dinas'),
            str_contains($peran, 'yayasan') => 'admin',
            str_contains($peran, 'orang tua'),
            str_contains($peran, 'orang_tua'),
            str_contains($peran, 'wali') => 'orang_tua',
            str_contains($peran, 'tendik'),
            str_contains($peran, 'tenaga kependidikan'),
            str_contains($peran, 'tata usaha') => 'tendik',
            str_contains($peran, 'guru'),
            str_contains($peran, 'pendidik'),
            str_contains($peran, 'ptk') => 'guru',
            default => 'peserta_didik',
        };

        // Tulis balik ke sesi agar pemakaian lain ($user['role'] mentah) ikut
        // konsisten tanpa harus diubah satu per satu.
        if (is_array($user) && session()->has('user')) {
            $user['role'] = $resolved;
            session(['user' => $user]);
        }

        return $resolved;
    }
}
