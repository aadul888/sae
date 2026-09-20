<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KegiatanOrganisasiAnggota extends Model
{
    use HasFactory;

    protected $table = 'kegiatan_organisasi_anggota';

    protected $fillable = [
        'organisasi_id',
        'peserta_didik_id',
        'jabatan',
        'sk_pengangkatan',
    ];

    public function organisasi()
    {
        return $this->belongsTo(KegiatanOrganisasi::class, 'organisasi_id');
    }

    public function siswa()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }
}
