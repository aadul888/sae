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
     * Ambil peran kanonik pengguna dari sesi.
     *
     * Nilai `role` di sesi bisa tidak kanonik: sesi lama mungkin menyimpan
     * string peran mentah (mis. "Peserta Didik"), bukan "peserta_didik".
     * Karena itu nilainya SELALU dinormalisasi lewat pemetaan yang sama,
     * dengan 'peran_id_str' sebagai cadangan. Logika sama dengan match `$role`
     * di dash-sidebar & DashboardController::checkAuth().
     */
    protected function sessionRole(): string
    {
        $user = $this->sessionUser();

        $rawRole = (string) (is_array($user)
            ? ($user['role'] ?? '')
            : ($user->role ?? ''));
        $rawPeran = (string) (is_array($user)
            ? ($user['peran_id_str'] ?? '')
            : ($user->peran_id_str ?? ''));

        $resolved = self::normalizeRole($rawRole !== '' ? $rawRole : $rawPeran);

        // Tulis balik ke sesi agar pemakaian lain ($user['role'] mentah) ikut
        // konsisten tanpa harus diubah satu per satu.
        if (is_array($user) && ($user['role'] ?? null) !== $resolved && session()->has('user')) {
            $user['role'] = $resolved;
            session(['user' => $user]);
        }

        return $resolved;
    }

    /**
     * Petakan nilai peran apa pun (kanonik atau mentah) ke salah satu peran baku.
     */
    public static function normalizeRole(?string $role): string
    {
        $role = strtolower(trim((string) $role));

        return match (true) {
            str_contains($role, 'admin'),
            str_contains($role, 'operator'),
            str_contains($role, 'dinas'),
            str_contains($role, 'yayasan') => 'admin',
            str_contains($role, 'orang tua'),
            str_contains($role, 'orang_tua'),
            str_contains($role, 'wali murid'),
            str_contains($role, 'wali_murid') => 'orang_tua',
            str_contains($role, 'tendik'),
            str_contains($role, 'tenaga kependidikan'),
            str_contains($role, 'tata usaha') => 'tendik',
            str_contains($role, 'guru'),
            str_contains($role, 'pendidik'),
            str_contains($role, 'ptk') => 'guru',
            default => 'peserta_didik',
        };
    }
}
