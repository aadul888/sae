<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;

class SemesterHelper
{
    /**
     * Dapatkan semester_id terbesar/terakhir dari tabel rombongan_belajar
     */
    public static function getActiveSemesterId()
    {
        return Cache::remember('active_semester_id', 3600, function () {
            if (!Schema::hasTable('rombongan_belajar')) {
                return null;
            }
            return DB::table('rombongan_belajar')->max('semester_id');
        });
    }

    /**
     * Parse semester_id (misal 20251) menjadi string label (misal 2025/2026 Ganjil)
     */
    public static function getActiveSemesterLabel(): string
    {
        $id = self::getActiveSemesterId();

        if (!$id || strlen((string)$id) < 5) {
            return 'Periode Aktif Belum Tersedia';
        }

        $idStr = (string)$id;
        $year = substr($idStr, 0, 4);
        $sem = substr($idStr, 4, 1);
        $nextYear = (int)$year + 1;

        $semLabel = match ($sem) {
            '1' => 'Ganjil',
            '2' => 'Genap',
            '3' => 'Pendek',
            default => 'SMT ' . $sem
        };

        return $year . '/' . $nextYear . ' - ' . $semLabel;
    }

    /**
     * Clear cache semester
     */
    public static function clearCache()
    {
        Cache::forget('active_semester_id');
    }
}
