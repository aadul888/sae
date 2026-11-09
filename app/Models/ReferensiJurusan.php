<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferensiJurusan extends Model
{
    protected $table = 'referensi_jurusan';

    protected $fillable = [
        'kode_bidang',
        'nama_bidang',
        'kode_program',
        'nama_program',
        'kode_konsentrasi',
        'nama_konsentrasi',
        'durasi',
        'kurikulum',
        'bidang_k13',
    ];

    protected $casts = [
        'durasi' => 'integer',
    ];

    public function scopeSearch($query, ?string $q)
    {
        if (empty($q)) {
            return $query;
        }

        return $query->where(function ($w) use ($q) {
            $w->where('kode_konsentrasi', 'like', "%{$q}%")
                ->orWhere('nama_konsentrasi', 'like', "%{$q}%")
                ->orWhere('kode_program', 'like', "%{$q}%")
                ->orWhere('nama_program', 'like', "%{$q}%")
                ->orWhere('kode_bidang', 'like', "%{$q}%")
                ->orWhere('nama_bidang', 'like', "%{$q}%")
                ->orWhere('bidang_k13', 'like', "%{$q}%");
        });
    }

    public function scopeBidang($query, ?string $bidang)
    {
        if (empty($bidang)) {
            return $query;
        }

        return $query->where('kode_bidang', $bidang)
            ->orWhere('nama_bidang', $bidang);
    }

    public function scopeDurasi($query, $durasi)
    {
        if (empty($durasi)) {
            return $query;
        }

        return $query->where('durasi', (int) $durasi);
    }
}
