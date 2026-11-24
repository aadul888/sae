<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Bentuk bangunan / blok / lahan dalam sebuah denah.
 *
 * Geometri hybrid: `bentuk = rect` memakai x/y/w/h/rotate; `bentuk = poly`
 * memakai kolom `poligon` (JSON titik). Data kondisi & kategori ruang TIDAK
 * disimpan di sini sebagai otoritas — warna di-join dari {@see SarprasRuang}
 * lewat `ruang_id`.
 */
class SarprasPetaArea extends Model
{
    protected $table = 'sarpras_peta_area';

    protected $fillable = [
        'peta_id',
        'parent_area_id',
        'ruang_id',
        'kode_area',
        'nama_area',
        'kategori',
        'zona',
        'bentuk',
        'x',
        'y',
        'w',
        'h',
        'rotate',
        'poligon',
        'label_x',
        'label_y',
        'warna_override',
        'highlight',
        'status_visual',
        'map_coordinates',
        'luas_area',
        'keterangan',
    ];

    protected $casts = [
        'poligon'         => 'array',
        'map_coordinates' => 'array',
        'x'         => 'float',
        'y'         => 'float',
        'w'         => 'float',
        'h'         => 'float',
        'rotate'    => 'float',
        'label_x'   => 'float',
        'label_y'   => 'float',
        'luas_area' => 'float',
    ];

    public function peta(): BelongsTo
    {
        return $this->belongsTo(SarprasPeta::class, 'peta_id');
    }

    /**
     * Data ruang (kondisi, PJ, dimensi) di-join dari `sarpras_ruang`.
     * Tidak ada model SarprasRuang; modul ini memakai query builder.
     */
    public function ruangData(): ?object
    {
        return $this->ruang_id
            ? DB::table('sarpras_ruang')->where('id', $this->ruang_id)->first()
            : null;
    }

    /** Atribut `points` untuk tag <polygon>. */
    public function getPointsAttrAttribute(): string
    {
        $titik = is_array($this->poligon) ? $this->poligon : [];

        return collect($titik)->map(fn ($p) => ($p[0] ?? 0) . ',' . ($p[1] ?? 0))->implode(' ');
    }
}
