<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeknisiPemeliharaan extends Model
{
    use HasFactory;

    protected $table = 'teknisi_pemeliharaan';

    protected $fillable = [
        'ruang_id',
        'kode_pemeliharaan',
        'nama_kegiatan',
        'kategori',
        'lokasi_aset',
        'frekuensi',
        'tgl_jadwal',
        'tgl_realisasi',
        'penanggung_jawab',
        'status',
        'biaya',
        'catatan_hasil',
        'user_id',
    ];

    protected $casts = [
        'tgl_jadwal' => 'date',
        'tgl_realisasi' => 'date',
        'biaya' => 'decimal:2',
    ];
}
