<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tugas E-Learning. Lihat .agents/docs/RENCANA_EKOSISTEM.md §14.
 */
class LearnTugas extends Model
{
    protected $table = 'learn_tugas';

    protected $fillable = [
        'pembelajaran_id',
        'judul',
        'deskripsi',
        'deadline',
        'bobot',
        'status',
        'created_by',
    ];

    protected $casts = [
        'deadline' => 'datetime',
    ];

    public function pengumpulan()
    {
        return $this->hasMany(LearnPengumpulan::class, 'tugas_id');
    }
}
