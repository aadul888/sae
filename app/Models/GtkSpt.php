<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GtkSpt extends Model
{
    use HasFactory;

    protected $table = 'gtk_spt';

    protected $fillable = [
        'nomor_spt',
        'dasar_penugasan',
        'nama_kegiatan',
        'lokasi_tujuan',
        'tanggal_berangkat',
        'tanggal_kembali',
        'lama_hari',
        'beban_anggaran',
        'pejabat_penandatangan_ptk_id',
        'pejabat_nama',
        'pejabat_jabatan',
        'daftar_ptk_id',
        'status',
        'file_lampiran',
        'created_by',
    ];

    protected $casts = [
        'tanggal_berangkat' => 'date:Y-m-d',
        'tanggal_kembali'   => 'date:Y-m-d',
        'daftar_ptk_id'     => 'array',
    ];

    public function persuratan()
    {
        return $this->belongsTo(Persuratan::class, 'nomor_spt', 'nomor_surat');
    }
}
