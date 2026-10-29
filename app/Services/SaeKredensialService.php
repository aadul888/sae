<?php

namespace App\Services;

use App\Models\User;
use App\Support\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * SaeKredensialService
 * -------------------------------------------------------------------------
 * Sumber kebenaran tunggal untuk verifikasi kredensial akun SAE
 * (`tabel pengguna`). Dipakai oleh:
 *
 *  1. `AuthController::login()`  — pintu masuk portal SAE.
 *  2. `PerpusPublicController::login()` — login manual anggota perpustakaan,
 *     yang WAJIB memakai username/password yang sama dengan login SAE.
 *
 * Dengan menyatukan logika di sini, aturan pencarian akun (username, NIP,
 * NUPTK, NIK, NISN, NIPD, email) dan aturan verifikasi password (hash bcrypt,
 * password kosong bawaan Dapodik, serta hash legacy md5/sha1) tidak mungkin
 * berbeda antara kedua pintu masuk.
 */
class SaeKredensialService
{
    /** Kolom identitas GTK yang boleh dipakai untuk mencari akun. */
    private const KOLOM_GTK = ['nip', 'nuptk', 'nik', 'email'];

    /** Kolom identitas peserta didik yang boleh dipakai untuk mencari akun. */
    private const KOLOM_PD = ['nisn', 'nik', 'nipd', 'email'];

    /**
     * Cari akun SAE berdasarkan username, atau identitas GTK/peserta didik.
     *
     * Pencocokan identitas dilakukan lewat subquery agar tetap satu query dan
     * tidak membocorkan akun lain (semua kondisi berada di dalam closure).
     */
    public static function cariUser(?string $identifier): ?User
    {
        $identifier = trim((string) $identifier);
        if ($identifier === '') {
            return null;
        }

        $query = User::where('username', $identifier);

        // --- Cocokkan lewat identitas GTK (NIP / NUPTK / NIK / email) ---
        if (Table::exists('gtk')) {
            $kolom = array_values(array_filter(
                self::KOLOM_GTK,
                fn (string $c) => Schema::hasColumn('gtk', $c)
            ));

            if (!empty($kolom)) {
                $query->orWhereIn('ptk_id', function ($sub) use ($identifier, $kolom) {
                    $sub->select('ptk_id')->from('gtk')
                        ->where(function ($w) use ($identifier, $kolom) {
                            foreach ($kolom as $kolomGtk) {
                                $w->orWhere($kolomGtk, $identifier);
                            }
                        });
                });
            }
        }

        // --- Cocokkan lewat identitas peserta didik (NISN / NIK / NIPD / email) ---
        if (Table::exists('peserta_didik')) {
            $kolom = array_values(array_filter(
                self::KOLOM_PD,
                fn (string $c) => Schema::hasColumn('peserta_didik', $c)
            ));

            if (!empty($kolom)) {
                $query->orWhereIn('peserta_didik_id', function ($sub) use ($identifier, $kolom) {
                    $sub->select('peserta_didik_id')->from('peserta_didik')
                        ->where(function ($w) use ($identifier, $kolom) {
                            foreach ($kolom as $kolomPd) {
                                $w->orWhere($kolomPd, $identifier);
                            }
                        });
                });
            }
        }

        return $query->first();
    }

    /**
     * Verifikasi password dengan aturan yang sama seperti login SAE:
     *  - password kosong di database (data Dapodik) => izinkan 'Sae12345!'
     *    atau identitas itu sendiri;
     *  - hash modern => password_verify();
     *  - hash legacy (md5/sha1/plain) => bandingkan apa adanya.
     */
    public static function cekPassword(User $user, string $identifier, ?string $password): bool
    {
        $password = (string) $password;
        $identifier = trim($identifier);
        $dbPassword = (string) ($user->password ?? '');

        if ($dbPassword === '') {
            return $password === 'Sae12345!' || ($identifier !== '' && $password === $identifier);
        }

        if (password_verify($password, $dbPassword)) {
            return true;
        }

        if (Hash::needsRehash($dbPassword)) {
            return $password === $dbPassword
                || md5($password) === $dbPassword
                || sha1($password) === $dbPassword;
        }

        return false;
    }

    /**
     * Autentikasi lengkap: cari akun lalu verifikasi password.
     * Mengembalikan `null` bila akun tidak ada ATAU password salah — pemanggil
     * tidak perlu (dan tidak boleh) membedakan keduanya ke pengguna.
     */
    public static function autentikasi(?string $identifier, ?string $password): ?User
    {
        $user = self::cariUser($identifier);
        if (!$user) {
            return null;
        }

        return self::cekPassword($user, (string) $identifier, $password) ? $user : null;
    }
}
