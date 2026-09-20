<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratKeteranganPd extends Model
{
    use HasFactory;

    protected $table = 'surat_keterangan_pd';

    protected $fillable = [
        'nomor_surat',
        'peserta_didik_id',
        'jenis_surat',
        'keperluan',
        'tanggal_surat',
        'penandatangan_ptk_id',
        'penandatangan_nama',
        'penandatangan_jabatan',
        'doc_id',
        'qrcode_url',
        'created_by',
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
    ];

    public function siswa()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }
}
