<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiswaUsulanPerubahan extends Model
{
    use HasFactory;

    protected $table = 'siswa_usulan_perubahan';

    protected $fillable = [
        'peserta_didik_id',
        'kolom_perubahan',
        'nilai_lama',
        'nilai_baru',
        'alasan',
        'berkas_bukti',
        'status',
        'catatan_verifikasi',
        'created_by',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function siswa()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }
}
