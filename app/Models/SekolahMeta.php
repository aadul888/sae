<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SekolahMeta extends Model
{
    protected $table = 'sekolah_meta';

    protected $fillable = [
        'sekolah_id',
        'npsn',
        'logo_path',
        'logo_size',
        'kop_path',
        'kop_size',
    ];

    protected $appends = [
        'logo_url',
        'formatted_logo_size',
        'kop_url',
        'formatted_kop_size',
    ];

    /**
     * Dapatkan URL publik dari logo sekolah
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo_path)) {
            return null;
        }

        return asset('storage/' . ltrim($this->logo_path, '/'));
    }

    /**
     * Dapatkan format ukuran logo sekolah (KB / MB)
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

    /**
     * Dapatkan URL publik dari kop surat sekolah
     */
    public function getKopUrlAttribute(): ?string
    {
        if (empty($this->kop_path)) {
            return null;
        }

        return asset('storage/' . ltrim($this->kop_path, '/'));
    }

    /**
     * Dapatkan format ukuran kop sekolah
     */
    public function getFormattedKopSizeAttribute(): ?string
    {
        if (empty($this->kop_size)) {
            return null;
        }

        $bytes = (int) $this->kop_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 0) . ' KB';
        }

        return $bytes . ' B';
    }

    /**
     * Ambil atau inisialisasi metadata sekolah (singleton-like record)
     */
    public static function getActiveMeta(?string $sekolahId = null, ?string $npsn = null): self
    {
        $query = self::query();
        if ($sekolahId) {
            $query->where('sekolah_id', $sekolahId);
        } elseif ($npsn) {
            $query->where('npsn', $npsn);
        }

        $meta = $query->first();
        if (!$meta) {
            $meta = self::first();
        }

        if (!$meta) {
            $meta = new self();
            $meta->sekolah_id = $sekolahId;
            $meta->npsn = $npsn;
            $meta->save();
        } else {
            if ($sekolahId && empty($meta->sekolah_id)) {
                $meta->sekolah_id = $sekolahId;
                $meta->save();
            }
            if ($npsn && empty($meta->npsn)) {
                $meta->npsn = $npsn;
                $meta->save();
            }
        }

        return $meta;
    }
}
