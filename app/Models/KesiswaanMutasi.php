<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KesiswaanMutasi extends Model
{
    use HasFactory;

    protected $table = 'kesiswaan_mutasi';

    protected $fillable = [
        'peserta_didik_id',
        'jenis_mutasi',
        'tanggal_mutasi',
        'alasan',
        'sekolah_tujuan_asal',
        'nomor_surat_mutasi',
        'file_berkas',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_mutasi' => 'date',
    ];

    public function siswa()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }
}
