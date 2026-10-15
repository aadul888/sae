<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PipRiwayat extends Model
{
    protected $table = 'pip_riwayat';

    protected $fillable = [
        'peserta_didik_id',
        'nisn',
        'nama_pd',
        'kelas',
        'rombel',
        'rombongan_belajar_id',
        'nama_ibu_kandung',
        'nama_ayah',
        'tanggal_lahir',
        'tempat_lahir',
        'jenis_kelamin',
        'nominal',
        'nomor_sk',
        'tanggal_sk',
        'tahap',
        'tahun',
        'status_cair',
        'tanggal_cair',
        'no_rekening',
        'no_kip',
        'no_kks',
        'no_kps',
        'virtual_acc',
        'keterangan_pencairan',
        'raw_data',
    ];

    protected $casts = [
        'nominal'  => 'float',
        'raw_data' => 'array',
    ];

    public function pesertaDidik()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }
}
