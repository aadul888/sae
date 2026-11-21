<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Soal kuis di dalam materi E-Learning (auto-nilai). Lihat §14 RENCANA_EKOSISTEM.
 */
class LearnMateriSoal extends Model
{
    protected $table = 'learn_materi_soal';

    protected $fillable = [
        'materi_id',
        'tipe',
        'pertanyaan',
        'opsi',
        'kunci',
        'bobot',
        'urutan',
    ];

    protected $casts = [
        'opsi' => 'array',
        'bobot' => 'integer',
        'urutan' => 'integer',
    ];
}
