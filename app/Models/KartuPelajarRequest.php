<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KartuPelajarRequest extends Model
{
    protected $table = 'kartu_pelajar_request';

    protected $fillable = [
        'peserta_didik_id',
        'nisn',
        'jenis_request',
        'alasan',
        'status',
        'catatan_admin',
        'diminta_oleh',
        'selesai_at',
    ];

    protected $casts = [
        'selesai_at' => 'datetime',
    ];

    public const JENIS = [
        'cetak_baru'        => 'Cetak Kartu Baru',
        'ganti_kartu_full'  => 'Ganti Kartu Penuh (Garansi)',
        'cetak_stiker'      => 'Cetak Ulang Stiker (Garansi)',
    ];

    public const STATUS = [
        'menunggu' => 'Menunggu',
        'diproses' => 'Diproses',
        'selesai'  => 'Selesai',
        'ditolak'  => 'Ditolak',
    ];
}
