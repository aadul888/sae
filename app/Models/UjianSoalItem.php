<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Relasi ujian ↔ soal (kertas ujian). Lihat .agents/docs/RENCANA_EKOSISTEM.md §14.7.
 */
class UjianSoalItem extends Model
{
    protected $table = 'ujian_soal_item';

    protected $fillable = [
        'ujian_id',
        'soal_id',
        'urutan',
    ];

    protected $casts = [
        'urutan' => 'integer',
    ];

    public function soal()
    {
        return $this->belongsTo(UjianSoal::class, 'soal_id');
    }
}
