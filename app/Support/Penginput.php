<?php

namespace App\Support;

/**
 * Jejak penginput baris data (audit): siapa yang mengisi/mengunggah.
 *
 * Berbeda dari `PenanggungJawab` (PJ per jurusan) — kolom jejak ini menandai
 * AKUN yang melakukan input, mengikuti pola tabel modul Teknisi
 * (`petugas_ptk_id`, `petugas_nama`, `user_id`).
 */
final class Penginput
{
    /**
     * Identitas penginput dari sesi saat ini.
     *
     * @return array{petugas_ptk_id: ?string, petugas_nama: ?string, user_id: ?string}
     */
    public static function dariSesi(?array $context = null): array
    {
        if ($context !== null) {
            return [
                'petugas_ptk_id' => $context['ptk_id'] ?? null,
                'petugas_nama'   => $context['nama'] ?? null,
                'user_id'        => $context['user_id'] ?? null,
            ];
        }

        $user = session('user');
        if (!$user) {
            return ['petugas_ptk_id' => null, 'petugas_nama' => null, 'user_id' => null];
        }

        return [
            'petugas_ptk_id' => is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null),
            'petugas_nama'   => is_array($user)
                ? ($user['nama'] ?? ($user['name'] ?? null))
                : ($user->nama ?? ($user->name ?? null)),
            'user_id'        => is_array($user)
                ? ($user['pengguna_id'] ?? ($user['id'] ?? null))
                : ($user->pengguna_id ?? ($user->id ?? null)),
        ];
    }
}
