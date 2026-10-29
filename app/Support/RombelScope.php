<?php

namespace App\Support;

/**
 * Batasan rombel "reguler" (kelas) untuk seluruh sistem.
 *
 * Dapodik menaruh 3 jenis baris di tabel `rombongan_belajar`:
 *   jenis_rombel 1  = Kelas               → rombel reguler (yang dipakai SAE)
 *   jenis_rombel 16 = Matapelajaran Pilihan → rombel bayangan, 1 per kelas
 *   jenis_rombel 51 = Ekstrakurikuler      → bukan kelas
 *
 * Tanpa filter, jumlah rombel membengkak (mis. 35 kelas + 35 mapil + 9 ekskul = 79)
 * dan daftar kelas di dropdown/statistik ikut menampilkan rombel bayangan.
 */
class RombelScope
{
    public const JENIS_REGULER = '1';
    public const JENIS_MATPEL = '16';
    public const JENIS_EKSKUL = '51';

    /**
     * Terapkan filter "hanya rombel reguler" pada query / sub-query.
     *
     * @param  string  $prefix  Awalan kolom, mis. 'rb.' untuk query dengan join.
     */
    public static function reguler($query, string $prefix = '')
    {
        return $query->where(function ($w) use ($prefix) {
            $w->where($prefix . 'jenis_rombel', self::JENIS_REGULER)
                ->orWhere(function ($s) use ($prefix) {
                    // Cadangan: data lama/feeder lain yang jenis_rombel kosong
                    // tapi labelnya jelas kelas.
                    $s->where(fn($n) => $n->whereNull($prefix . 'jenis_rombel')
                        ->orWhere($prefix . 'jenis_rombel', ''))
                        ->where($prefix . 'jenis_rombel_str', 'LIKE', '%Kelas%')
                        ->where($prefix . 'jenis_rombel_str', 'NOT LIKE', '%Pilihan%')
                        ->where($prefix . 'jenis_rombel_str', 'NOT LIKE', '%Ekstrakurikuler%');
                });
        });
    }

    /**
     * Query dasar `rombongan_belajar` yang sudah dibatasi ke rombel reguler.
     */
    public static function query(): \Illuminate\Database\Query\Builder
    {
        return self::reguler(\Illuminate\Support\Facades\DB::table('rombongan_belajar'));
    }

    /**
     * Jumlah rombel reguler.
     */
    public static function count(): int
    {
        if (!Table::exists('rombongan_belajar')) {
            return 0;
        }

        return self::query()->count();
    }
}
