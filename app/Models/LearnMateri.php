<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Materi E-Learning. Lihat .agents/docs/RENCANA_EKOSISTEM.md §14.
 */
class LearnMateri extends Model
{
    protected $table = 'learn_materi';

    protected $fillable = [
        'pembelajaran_id',
        'judul',
        'deskripsi',
        'isi_html',
        'tipe',
        'url_embed',
        'file_path',
        'urutan',
        'status',
        'created_by',
    ];

    public function scopeTerbit($query)
    {
        return $query->where('status', 'terbit');
    }

    public function soal()
    {
        return $this->hasMany(LearnMateriSoal::class, 'materi_id')->orderBy('urutan')->orderBy('id');
    }
}
