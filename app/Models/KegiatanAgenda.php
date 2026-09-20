<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KegiatanAgenda extends Model
{
    use HasFactory;

    protected $table = 'kegiatan_agenda';

    protected $fillable = [
        'organisasi_id',
        'ekskul_id',
        'judul_kegiatan',
        'jenis_kegiatan',
        'tanggal_mulai',
        'tanggal_selesai',
        'tempat',
        'penanggung_jawab',
        'anggaran',
        'status',
        'laporan_kegiatan_file',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'anggaran' => 'decimal:2',
    ];

    public function organisasi()
    {
        return $this->belongsTo(KegiatanOrganisasi::class, 'organisasi_id');
    }

    public function ekskul()
    {
        return $this->belongsTo(KegiatanEkskul::class, 'ekskul_id');
    }
}
