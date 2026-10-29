<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Pemeriksaan keberadaan tabel yang di-cache dalam memori proses.
 *
 * `Schema::hasTable()` menjalankan query ke `information_schema` pada setiap
 * pemanggilan. Aplikasi ini memanggilnya 467 kali, sebagian di dalam service
 * statistik dashboard yang dieksekusi per request, sehingga menambah beban
 * query berulang untuk informasi yang tidak berubah selama satu request.
 *
 * Helper ini menyimpan hasil per koneksi selama proses berjalan, sehingga
 * 467 panggilan tersebut menyusut menjadi sebanyak jumlah tabel unik.
 *
 * Catatan: cache hanya bertahan selama satu proses PHP (per request pada
 * PHP-FPM). Tidak perlu di-invalidate saat request berjalan karena struktur
 * tabel tidak berubah di tengah request. Untuk proses panjang (queue worker,
 * artisan) sediakan `flush()`.
 */
final class Table
{
    /** @var array<string, bool> */
    private static array $cache = [];

    /**
     * Apakah tabel ada? Hasil di-cache per proses.
     */
    public static function exists(string $table): bool
    {
        if (!array_key_exists($table, self::$cache)) {
            self::$cache[$table] = Schema::hasTable($table);
        }

        return self::$cache[$table];
    }

    /**
     * Apakah SEMUA tabel yang diberikan ada?
     */
    public static function existsAll(string ...$tables): bool
    {
        foreach ($tables as $table) {
            if (!self::exists($table)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Apakah kolom tertentu ada pada tabel tertentu? Hasil di-cache per proses.
     */
    public static function hasColumn(string $table, string $column): bool
    {
        $key = '__col__' . $table . '.' . $column;

        if (!array_key_exists($key, self::$cache)) {
            self::$cache[$key] = self::exists($table) && Schema::hasColumn($table, $column);
        }

        return self::$cache[$key];
    }

    /**
     * Apakah SALAH SATU tabel ada?
     */
    public static function existsAny(string ...$tables): bool
    {
        foreach ($tables as $table) {
            if (self::exists($table)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kosongkan cache. Panggil setelah migrasi atau pada proses panjang.
     */
    public static function flush(): void
    {
        self::$cache = [];
    }
}
