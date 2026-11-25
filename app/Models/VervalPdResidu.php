<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris hasil import berkas Residu Verval PD.
 * Kolom perbandingan (nisn_banding, nik_dukcapil, dst.) menyimpan
 * nilai/centang apa adanya dari berkas, lalu dirender hijau/merah di UI.
 */
class VervalPdResidu extends Model
{
    protected $table = 'verval_pd_residu';

    protected $fillable = [
        'import_batch',
        'nisn',
        'nik',
        'nama',
        'rombel_sekolah',
        'nisn_banding',
        'nik_dukcapil',
        'nik_ganda',
        'nama_banding',
        'tempat_lahir',
        'tanggal_lahir',
        'ibu_kandung',
        'jenis_kelamin',
        'desa_kelurahan',
        'status_proses',
        'catatan',
        'imported_by',
    ];
}
