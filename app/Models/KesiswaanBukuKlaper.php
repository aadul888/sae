<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KesiswaanBukuKlaper extends Model
{
    use HasFactory;

    protected $table = 'kesiswaan_buku_klaper';

    protected $fillable = [
        'peserta_didik_id',
        'nomor_klaper',
        'nomor_induk',
        'huruf_abjad',
        'tahun_masuk',
        'status_klaper',
        'keterangan',
    ];

    public function siswa()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }
}
