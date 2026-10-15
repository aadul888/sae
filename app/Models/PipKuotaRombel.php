<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class PipKuotaRombel extends Model
{
    protected $table = 'pip_kuota_rombel';

    protected $fillable = [
        'rombongan_belajar_id',
        'tahun',
        'kuota',
        'catatan',
    ];

    /**
     * Dapatkan kuota usulan untuk rombel tertentu pada tahun aktif (default 5 jika belum diatur)
     */
    public static function getKuotaForRombel(string $rombelId, ?string $tahun = null): int
    {
        $tahun = $tahun ?: (string) (date('Y'));
        if (!Schema::hasTable('pip_kuota_rombel')) {
            return 5;
        }

        $record = self::where('rombongan_belajar_id', $rombelId)
            ->where('tahun', $tahun)
            ->first();

        return $record ? (int) $record->kuota : 5;
    }

    /**
     * Dapatkan target total pagu kuota sekolah untuk tahun aktif
     */
    public static function getTargetTotalSekolah(?string $tahun = null): ?int
    {
        $tahun = $tahun ?: (string) (date('Y'));
        if (!Schema::hasTable('pip_kuota_rombel')) {
            return null;
        }

        $record = self::where('rombongan_belajar_id', '__TOTAL__')
            ->where('tahun', $tahun)
            ->first();

        return $record ? (int) $record->kuota : null;
    }

    /**
     * Simpan target total pagu kuota sekolah untuk tahun aktif
     */
    public static function setTargetTotalSekolah(int $target, ?string $tahun = null): void
    {
        $tahun = $tahun ?: (string) (date('Y'));
        self::updateOrCreate(
            ['rombongan_belajar_id' => '__TOTAL__', 'tahun' => $tahun],
            ['kuota' => max(0, $target)]
        );
    }
}
