<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat revisi denah — satu baris per perubahan (bukan per tahun ajaran).
 * `snapshot_geometri` menyimpan cuplikan seluruh area saat revisi dibuat.
 */
class SarprasPetaRevisi extends Model
{
    protected $table = 'sarpras_peta_revisi';

    public $timestamps = false;

    protected $fillable = [
        'peta_id',
        'revisi_ke',
        'snapshot_geometri',
        'ringkasan_perubahan',
        'diubah_oleh',
        'catatan',
        'created_at',
    ];

    protected $casts = [
        'snapshot_geometri' => 'array',
        'revisi_ke'         => 'integer',
        'created_at'        => 'datetime',
    ];

    public function peta(): BelongsTo
    {
        return $this->belongsTo(SarprasPeta::class, 'peta_id');
    }
}
