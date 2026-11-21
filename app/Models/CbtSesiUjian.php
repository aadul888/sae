<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Sesi/jadwal ujian CBT per periode asesmen (mapel + tanggal + jam + kuota).
 * Lihat §14.7 RENCANA_EKOSISTEM. Pelaksanaan serentak belum ada — menunggu §10.
 */
class CbtSesiUjian extends Model
{
    protected $table = 'cbt_sesi_ujian';

    protected $fillable = [
        'periode_id',
        'pembelajaran_id',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'kuota',
        'ruang',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'kuota' => 'integer',
    ];

    public function periode()
    {
        return $this->belongsTo(CbtPeriodeAsesmen::class, 'periode_id');
    }
}
