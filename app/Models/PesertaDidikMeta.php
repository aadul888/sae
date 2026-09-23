<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesertaDidikMeta extends Model
{
    protected $table = 'peserta_didik_meta';

    protected $fillable = [
        'peserta_didik_id',
        'nisn',
        'rfid_uid',
        'rfid_registered_at',
        'foto_path',
        'foto_size',
        'foto_width',
        'foto_height',
        'is_koordinator',
        'jabatan_koordinator',
        'koordinator_tmt',
    ];

    protected $casts = [
        'is_koordinator' => 'boolean',
        'koordinator_tmt' => 'datetime',
        'rfid_registered_at' => 'datetime',
    ];

    protected $appends = [
        'foto_url',
        'formatted_foto_size',
    ];

    /**
     * Dapatkan URL publik dari pasfoto peserta didik dengan cache-busting
     */
    public function getFotoUrlAttribute(): ?string
    {
        if (empty($this->foto_path)) {
            return null;
        }

        $cleanPath = ltrim($this->foto_path, '/');
        $fullPath = storage_path('app/public/' . $cleanPath);
        $version = file_exists($fullPath) ? filemtime($fullPath) : null;

        $url = asset('storage/' . $cleanPath);
        return $version ? ($url . '?v=' . $version) : $url;
    }

    /**
     * Dapatkan format ukuran file yang mudah dibaca (KB / MB)
     */
    public function getFormattedFotoSizeAttribute(): ?string
    {
        if (empty($this->foto_size)) {
            return null;
        }

        $bytes = (int) $this->foto_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 0) . ' KB';
        }

        return $bytes . ' B';
    }
}
