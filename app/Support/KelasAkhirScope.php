<?php

namespace App\Support;

/**
 * Batasan "kelas akhir" (tingkat XII) untuk seluruh modul Buku Induk.
 *
 * Dapodik menyimpan tingkat kelas pada `peserta_didik.tingkat_pendidikan_id`
 * dan/atau `rombongan_belajar.tingkat_pendidikan_id` (bisa '12' atau 'XII').
 * Satu sumber aturan agar tab Data Peserta Didik, tab Buku Induk, dan cetak
 * konsisten hanya menampilkan kelas akhir.
 */
class KelasAkhirScope
{
    public const TINGKAT = ['12', 'XII'];

    /**
     * Terapkan filter kelas akhir.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string|null  $rb  Alias join rombel, null bila query tidak meng-join rombel.
     */
    public static function apply($query, string $pd = 'pd', ?string $rb = 'rb')
    {
        if ($rb === null) {
            return $query->whereIn($pd . '.tingkat_pendidikan_id', self::TINGKAT);
        }

        return $query->where(function ($w) use ($pd, $rb) {
            $w->whereIn($pd . '.tingkat_pendidikan_id', self::TINGKAT)
              ->orWhereIn($rb . '.tingkat_pendidikan_id', self::TINGKAT);
        });
    }
}
