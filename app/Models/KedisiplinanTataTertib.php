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
}
