<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KedisiplinanTataTertib extends Model
{
    use HasFactory;

    protected $table = 'kedisiplinan_tata_tertib';

    protected $fillable = [
        'kode',
        'tingkat',
        'kategori',
        'nama_aturan',
        'bobot_poin',
        'sanksi_rekomendasi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'bobot_poin' => 'integer',
    ];

    public function getTingkatAttribute($val): string
    {
        if (!empty($val)) {
            return strtolower($val);
        }
        if ($this->kategori === 'larangan_berat' || (int) $this->bobot_poin >= 35) {
            return 'berat';
        }
        if ((int) $this->bobot_poin >= 15) {
            return 'sedang';
        }
        return 'ringan';
    }

    public function pelanggaran()
    {
        return $this->hasMany(KedisiplinanPelanggaran::class, 'tata_tertib_id');
    }

    /**
     * Buat kode aturan otomatis berdasarkan kategori.
     * kerapian -> KERxx, kehadiran -> HDxx, perilaku -> PRxx, larangan_berat -> LBxx, default -> TTxx
     */
    public static function generateKode(?string $kategori = null): string
    {
        $prefix = match (strtolower((string) $kategori)) {
            'kerapian'       => 'KER',
            'kehadiran'      => 'HD',
            'perilaku'       => 'PR',
            'larangan_berat' => 'LB',
            default          => 'TT',
        };

        $existing = static::where('kode', 'like', $prefix . '%')->pluck('kode');
        $maxNum = 0;

        foreach ($existing as $kode) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/i', (string) $kode, $matches)) {
                $maxNum = max($maxNum, (int) $matches[1]);
            }
        }

        $nextNum = $maxNum + 1;
        $padLength = max(2, strlen((string) $nextNum));

        do {
            $candidate = $prefix . str_pad((string) $nextNum, $padLength, '0', STR_PAD_LEFT);
            $nextNum++;
        } while (static::where('kode', $candidate)->exists());

        return $candidate;
    }
}
