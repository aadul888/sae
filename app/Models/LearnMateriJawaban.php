<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Jawaban kuis materi E-Learning + skor auto-nilai. Lihat §14 RENCANA_EKOSISTEM.
 */
class LearnMateriJawaban extends Model
{
    protected $table = 'learn_materi_jawaban';

    protected $fillable = [
        'materi_id',
        'peserta_didik_id',
        'jawaban',
        'skor',
        'benar',
        'total_soal',
        'submitted_at',
    ];

    protected $casts = [
        'jawaban' => 'array',
        'skor' => 'float',
        'benar' => 'integer',
        'total_soal' => 'integer',
        'submitted_at' => 'datetime',
    ];
}
