<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KedisiplinanPemanggilanWali extends Model
{
    use HasFactory;

    protected $table = 'kedisiplinan_pemanggilan_wali';

    protected $fillable = [
        'peserta_didik_id',
        'nomor_surat',
        'tanggal_surat',
        'tanggal_hadir',
        'jam_hadir',
        'tempat',
        'alasan',
        'menghadap_ke',
        'status',
        'catatan_hasil',
        'doc_id',
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
        'tanggal_hadir' => 'date',
    ];

    public function siswa()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }
}
