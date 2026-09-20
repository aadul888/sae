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

    public function pelanggaran()
    {
        return $this->hasMany(KedisiplinanPelanggaran::class, 'tata_tertib_id');
    }
}
