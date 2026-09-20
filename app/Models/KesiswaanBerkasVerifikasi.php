<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KesiswaanBerkasVerifikasi extends Model
{
    use HasFactory;

    protected $table = 'kesiswaan_berkas_verifikasi';

    protected $fillable = [
        'peserta_didik_id',
        'akta_kelahiran',
        'kartu_keluarga',
        'ijazah_smp',
        'ktp_orang_tua',
        'kip_pip',
        'catatan_verifikasi',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'akta_kelahiran' => 'boolean',
        'kartu_keluarga' => 'boolean',
        'ijazah_smp' => 'boolean',
        'ktp_orang_tua' => 'boolean',
        'kip_pip' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function siswa()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }
}
