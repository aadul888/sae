<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengumpulan tugas E-Learning. Lihat .agents/docs/RENCANA_EKOSISTEM.md §14.
 */
class LearnPengumpulan extends Model
{
    protected $table = 'learn_pengumpulan';

    protected $fillable = [
        'tugas_id',
        'peserta_didik_id',
        'jawaban_teks',
        'link_url',
        'nilai',
        'dinilai_oleh',
        'submitted_at',
    ];

    protected $casts = [
        'nilai' => 'float',
        'submitted_at' => 'datetime',
    ];

    public function tugas()
    {
        return $this->belongsTo(LearnTugas::class, 'tugas_id');
    }
}
