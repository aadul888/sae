<?php

namespace App\Support;

/**
 * Rumus tunggal Skor Kualitas Data peserta didik, dipakai bersama oleh
 * Wali Kelas — Validasi Data, beranda (Progres per Rombel), dan Duty Dashboard.
 *
 * Skor per peserta didik = rata-rata(konfirmasi, berkas):
 *  - Konfirmasi : sesuai/diverifikasi/disinkronkan_dapodik = 100,
 *                 perlu_perbaikan = 50, selainnya (belum) = 0.
 *  - Berkas     : KK + Ijazah. Tiap jenis: valid = 50, menunggu = 25
 *                 (sudah diunggah, menunggu verifikasi), tidak_valid = 25
 *                 (ditolak namun berkas ada), belum/absen = 0.
 *
 * Karena dirata-rata, nilai > 0 begitu ada SATU komponen yang terisi
 * (konfirmasi terisi, atau berkas sudah diunggah/diverifikasi).
 */
final class KualitasData
{
    /** Skor konfirmasi identitas (0..100). */
    public static function skorKonfirmasi(?string $statusKonfirmasi): float
    {
        $s = strtolower(trim((string) $statusKonfirmasi));

        return match ($s) {
            'sesuai', 'diverifikasi', 'disinkronkan_dapodik' => 100.0,
            'perlu_perbaikan' => 50.0,
            default => 0.0,
        };
    }

    /** Skor satu jenis berkas (0..50). `$fisik` = checklist verifikasi fisik. */
    public static function skorBerkas(?string $status, bool $fisik = false): float
    {
        return match (self::kategoriBerkas($status, $fisik)) {
            'valid' => 50.0,
            'menunggu', 'tidak_valid' => 25.0,
            default => 0.0,
        };
    }

    /**
     * Kategori tampilan satu jenis berkas: valid | menunggu | tidak_valid | belum.
     * Verifikasi fisik (checklist) dianggap valid.
     */
    public static function kategoriBerkas(?string $status, bool $fisik = false): string
    {
        if ($fisik) {
            return 'valid';
        }

        $s = strtolower(trim((string) $status));

        return in_array($s, ['valid', 'menunggu', 'tidak_valid'], true) ? $s : 'belum';
    }

    /**
     * Skor kualitas peserta didik (0..100) = rata-rata(konfirmasi, KK + Ijazah).
     */
    public static function skorPesertaDidik(
        ?string $statusKonfirmasi,
        ?string $statusKk,
        bool $kkFisik = false,
        ?string $statusIjazah = null,
        bool $ijazahFisik = false
    ): float {
        $konfirmasi = self::skorKonfirmasi($statusKonfirmasi);
        $berkas = self::skorBerkas($statusKk, $kkFisik) + self::skorBerkas($statusIjazah, $ijazahFisik);

        return ($konfirmasi + $berkas) / 2;
    }
}
