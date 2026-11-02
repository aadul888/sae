<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Resolusi PENANGGUNG JAWAB (PJ) baris data Sarpras/Laboran dari JURUSAN.
 *
 * Aturan:
 *  - Baris ber-jurusan → PJ = Laboran (tugas tambahan kode `LABORAN`) jurusan ybs.
 *  - Baris umum (jurusan kosong) → PJ = null = tanggung jawab bersama
 *    (Administrasi Sarpras, Teknisi & IT).
 *
 * Kode jurusan dapat berupa INDUK (mis. `26010`) atau ANAK (mis. `26010475`)
 * dan penugasan dapat memuat multi-nilai (koma/JSON), sehingga pencocokan
 * memakai relasi awalan dua arah — sama seperti LaboranDokumenService.
 */
final class PenanggungJawab
{
    /** Cache peta jurusan→ptk_id Laboran per proses. */
    private static ?array $petaLaboran = null;

    /**
     * ptk_id Laboran penanggung jawab jurusan; null bila tak ada.
     */
    public static function untukJurusan(?string $jurusanId): ?string
    {
        $jurusanId = trim((string) $jurusanId);
        if ($jurusanId === '') {
            return null;
        }

        $peta = self::petaLaboran();

        foreach ($peta as $ptkId => $jurusanList) {
            foreach ($jurusanList as $token) {
                if ($token === $jurusanId
                    || str_starts_with($jurusanId, $token)
                    || str_starts_with($token, $jurusanId)) {
                    return $ptkId;
                }
            }
        }

        return null;
    }

    /**
     * Nama PJ dari ptk_id (GTK) atau pengguna_id (akun manual); null bila kosong.
     */
    public static function nama(?string $id): ?string
    {
        $id = trim((string) $id);
        if ($id === '') {
            return null;
        }

        if (Table::exists('gtk')) {
            $nama = DB::table('gtk')->where('ptk_id', $id)->value('nama');
            if ($nama) {
                return $nama;
            }
        }

        if (Table::exists('pengguna')) {
            return DB::table('pengguna')->where('pengguna_id', $id)->value('nama');
        }

        return null;
    }

    /**
     * Peta `ptk_id` Laboran → daftar token jurusan yang ditugaskan.
     *
     * @return array<string, array<int, string>>
     */
    private static function petaLaboran(): array
    {
        if (self::$petaLaboran !== null) {
            return self::$petaLaboran;
        }

        self::$petaLaboran = [];

        if (!Table::existsAll('ptk_tugas_tambahan', 'ref_tugas_tambahan')) {
            return self::$petaLaboran;
        }

        $rows = DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->where('rtt.kode', 'LABORAN')
            ->where('ptt.is_active', true)
            ->orderByDesc('ptt.tmt_tugas')
            ->orderByDesc('ptt.id')
            ->get(['ptt.ptk_id', 'ptt.jurusan_id']);

        foreach ($rows as $row) {
            $ptkId = trim((string) $row->ptk_id);
            if ($ptkId === '') {
                continue;
            }
            $tokens = self::pecahJurusan($row->jurusan_id);
            if (empty($tokens)) {
                continue;
            }
            // Laboran pertama (baris paling baru) menang bila dobel.
            if (!isset(self::$petaLaboran[$ptkId])) {
                self::$petaLaboran[$ptkId] = $tokens;
            }
        }

        return self::$petaLaboran;
    }

    /**
     * Pecah kolom `jurusan_id` penugasan (bisa koma atau JSON) jadi token.
     *
     * @return array<int, string>
     */
    private static function pecahJurusan(?string $raw): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }

        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('trim', $decoded), fn ($v) => $v !== ''));
            }
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw)), fn ($v) => $v !== ''));
    }

    /**
     * Peta `jurusan_id` => nama PJ (Laboran jurusan ybs); null bila tak ada.
     * Dipakai UI form agar kolom "Penanggung Jawab" terisi otomatis.
     *
     * @return array<string, string|null>
     */
    public static function namaPerJurusan(): array
    {
        if (!Table::exists('jurusan_meta')) {
            return [];
        }

        $out = [];
        foreach (DB::table('jurusan_meta')->pluck('jurusan_id') as $jurusanId) {
            $jurusanId = (string) $jurusanId;
            $out[$jurusanId] = self::nama(self::untukJurusan($jurusanId));
        }

        return $out;
    }

    /** Kosongkan cache (dipakai pengujian / setelah ubah penugasan). */
    public static function flush(): void
    {
        self::$petaLaboran = null;
    }
}
