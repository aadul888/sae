<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Periode Asesmen CBT (mis. PTS Ganjil 2026/2027). Lihat §14.7 RENCANA_EKOSISTEM.
 * Menautkan kertas ujian ke satu periode agar admin dapat "menjalankan asesmen".
 * Pelaksanaan serentak (attempt/jawaban) belum ada — menunggu fondasi §10.
 */
class CbtPeriodeAsesmen extends Model
{
    protected $table = 'cbt_periode_asesmen';

    protected $fillable = [
        'nama',
        'tipe',
        'tahun_ajaran',
        'semester',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'keterangan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public const TIPE = ['ulangan', 'uts', 'uas', 'pat', 'latihan'];

    public const STATUS = ['draft', 'aktif', 'selesai'];

    public const SEMESTER = ['ganjil', 'genap'];

    /** Label singkat tipe (untuk tampilan). */
    public const TIPE_LABEL = [
        'ulangan' => 'Ulangan',
        'uts' => 'PTS / UTS',
        'uas' => 'PAS / UAS',
        'pat' => 'PAT',
        'latihan' => 'Latihan',
    ];
}
