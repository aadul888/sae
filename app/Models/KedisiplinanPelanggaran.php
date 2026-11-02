<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KedisiplinanPelanggaran extends Model
{
    use HasFactory;

    protected $table = 'kedisiplinan_pelanggaran';

    protected $fillable = [
        'peserta_didik_id',
        'tata_tertib_id',
        'tanggal_kejadian',
        'tempat_kejadian',
        'poin',
        'keterangan',
        'pelapor_ptk_id',
        'pelapor_nama',
        'foto_bukti',
        'status_tindak_lanjut',
        'sanggah_status',
        'sanggah_jenis',
        'sanggah_keterangan',
        'sanggah_poin_potong',
        'sanggah_validator',
        'sanggah_tanggal',
        'sanggah_bukti',
    ];

    protected $casts = [
        'tanggal_kejadian'    => 'date',
        'poin'                => 'integer',
        'sanggah_poin_potong' => 'integer',
        'sanggah_tanggal'     => 'date',
    ];

    public function getPoinNettoAttribute(): int
    {
        return max(0, (int) $this->poin - (int) ($this->sanggah_poin_potong ?? 0));
    }

    public function siswa()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }

    public function aturan()
    {
        return $this->belongsTo(KedisiplinanTataTertib::class, 'tata_tertib_id');
    }

    public function pembinaan()
    {
        return $this->hasOne(KedisiplinanPembinaan::class, 'pelanggaran_id');
    }
}
