<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JurusanMeta extends Model
{
    protected $table = 'jurusan_meta';

    protected $fillable = [
        'jurusan_id',
        'nama_jurusan',
        'singkatan',
        'logo_path',
        'logo_size',
    ];

    protected $appends = [
        'logo_url',
        'formatted_logo_size',
    ];

    /**
     * Dapatkan URL publik dari logo jurusan
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo_path)) {
            return null;
        }

        return asset('storage/' . ltrim($this->logo_path, '/'));
    }

    /**
     * Dapatkan format ukuran file yang mudah dibaca (KB / MB)
     */
    public function getFormattedLogoSizeAttribute(): ?string
    {
        if (empty($this->logo_size)) {
            return null;
        }

        $bytes = (int) $this->logo_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 0) . ' KB';
        }

        return $bytes . ' B';
    }
}
