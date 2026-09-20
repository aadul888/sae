<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KesiswaanKelulusan extends Model
{
    use HasFactory;

    protected $table = 'kesiswaan_kelulusan';

    protected $fillable = [
        'peserta_didik_id',
        'tahun_ajaran',
        'nomor_peserta_ujian',
        'nomor_ijazah',
        'nomor_skl',
        'status_kelulusan',
        'tanggal_lulus',
        'keterangan',
        'file_skl',
        'doc_id',
    ];

    protected $casts = [
        'tanggal_lulus' => 'date',
    ];

    public function siswa()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }
}
