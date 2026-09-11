<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesertaDidikMeta extends Model
{
    protected $table = 'peserta_didik_meta';

    protected $fillable = [
        'peserta_didik_id',
        'nisn',
        'foto_path',
        'foto_size',
        'foto_width',
        'foto_height',
    ];

    protected $appends = [
        'foto_url',
        'formatted_foto_size',
    ];

    /**
     * Dapatkan URL publik dari pasfoto peserta didik
     */
    public function getFotoUrlAttribute(): ?string
    {
        if (empty($this->foto_path)) {
            return null;
        }

        return asset('storage/' . ltrim($this->foto_path, '/'));
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
