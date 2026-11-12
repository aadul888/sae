<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PipPenggunaanDana extends Model
{
    protected $table = 'pip_penggunaan_dana';

    protected $fillable = [
        'peserta_didik_id',
        'pip_riwayat_id',
        'jenis_sk',
        'tahap_id',
        'tahap',
        'nominal',
        'rincian',
        'pernyataan',
        'catatan',
        'dilaporkan_oleh',
        'peran',
        'status',
        'catatan_verifikasi',
        'diverifikasi_oleh',
        'diverifikasi_pada',
    ];

    protected $casts = [
        'nominal'    => 'float',
        'rincian'    => 'array',
        'pernyataan' => 'array',
        'diverifikasi_pada' => 'datetime',
    ];

    public function bukti()
    {
        return $this->hasMany(PipPenggunaanBukti::class, 'penggunaan_dana_id');
    }
}
