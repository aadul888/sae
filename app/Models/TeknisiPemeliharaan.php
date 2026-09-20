<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeknisiPemeliharaan extends Model
{
    use HasFactory;

    protected $table = 'teknisi_pemeliharaan';

    protected $fillable = [
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
    ];

    protected $casts = [
        'tgl_jadwal' => 'date',
        'tgl_realisasi' => 'date',
        'biaya' => 'decimal:2',
    ];
}
