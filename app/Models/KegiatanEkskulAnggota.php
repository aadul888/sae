<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KegiatanEkskulAnggota extends Model
{
    use HasFactory;

    protected $table = 'kegiatan_ekskul_anggota';

    protected $fillable = [
        'ekskul_id',
        'peserta_didik_id',
        'nomor_anggota',
        'status',
    ];

    public function ekskul()
    {
        return $this->belongsTo(KegiatanEkskul::class, 'ekskul_id');
    }

    public function siswa()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }
}
