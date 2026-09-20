<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KedisiplinanPembinaan extends Model
{
    use HasFactory;

    protected $table = 'kedisiplinan_pembinaan';

    protected $fillable = [
        'peserta_didik_id',
        'pelanggaran_id',
        'tanggal_pembinaan',
        'guru_bk_ptk_id',
        'wali_kelas_ptk_id',
        'bentuk_pembinaan',
        'hasil_pembinaan',
        'status',
        'surat_perjanjian_file',
    ];

    protected $casts = [
        'tanggal_pembinaan' => 'date',
    ];

    public function siswa()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }

    public function pelanggaran()
    {
        return $this->belongsTo(KedisiplinanPelanggaran::class, 'pelanggaran_id');
    }

    public function guruBk()
    {
        return $this->belongsTo(Gtk::class, 'guru_bk_ptk_id', 'ptk_id');
    }

    public function waliKelas()
    {
        return $this->belongsTo(Gtk::class, 'wali_kelas_ptk_id', 'ptk_id');
    }
}
