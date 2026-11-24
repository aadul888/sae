<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Denah kerja per tingkat (tapak / lantai). Menyimpan kondisi terkini;
 * riwayat perubahan ada di {@see SarprasPetaRevisi}.
 *
 * Lihat .agents/docs/RENCANA_PETA_DENAH_SARPRAS.md
 */
class SarprasPeta extends Model
{
    protected $table = 'sarpras_peta';

    protected $fillable = [
        'sekolah_id',
        'parent_peta_id',
        'nama',
        'tingkat',
        'skala',
        'luas_tanah',
        'view_box',
        'meter_per_unit',
        'latar_path',
        'latar_sumber',
        'latar_atribusi',
        'latar_opacity',
        'view_state',
        'revisi_ke',
        'file_svg',
        'is_aktif',
    ];

    protected $casts = [
        'luas_tanah'     => 'float',
        'meter_per_unit' => 'float',
        'latar_opacity'  => 'integer',
        'view_state'     => 'array',
        'revisi_ke'      => 'integer',
        'is_aktif'       => 'boolean',
    ];

    protected $appends = ['latar_url'];

    public function areas(): HasMany
    {
        return $this->hasMany(SarprasPetaArea::class, 'peta_id');
    }

    public function lantai(): HasMany
    {
        return $this->hasMany(SarprasPeta::class, 'parent_peta_id');
    }

    public function tapak(): BelongsTo
    {
        return $this->belongsTo(SarprasPeta::class, 'parent_peta_id');
    }

    public function revisi(): HasMany
    {
        return $this->hasMany(SarprasPetaRevisi::class, 'peta_id')->orderByDesc('revisi_ke');
    }

    public function getLatarUrlAttribute(): ?string
    {
        return $this->latar_path ? asset('storage/' . ltrim($this->latar_path, '/')) : null;
    }
}
