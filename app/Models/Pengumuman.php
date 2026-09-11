<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Pengumuman extends Model
{
    protected $table = 'pengumuman';

    protected $fillable = [
        'judul',
        'isi',
        'target',
        'target_peran',
        'is_active',
        'penulis_nama',
        'dibaca_pengguna',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'dibaca_pengguna' => 'array',
    ];

    /**
     * Scope pengumuman aktif
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pengumuman untuk portal publik (teks berjalan)
     */
    public function scopeForPublic(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereIn('target', ['publik', 'semua'])
            ->orderByDesc('created_at');
    }

    /**
     * Scope pengumuman untuk notifikasi pengguna di lonceng header
     */
    public function scopeForUserRole(Builder $query, string $role = 'semua'): Builder
    {
        return $query->where('is_active', true)
            ->whereIn('target', ['pengguna', 'semua'])
            ->where(function ($q) use ($role) {
                $q->where('target_peran', 'semua')
                    ->orWhere('target_peran', $role);
            })
            ->orderByDesc('created_at');
    }

    public function sudahDibacaOleh(?string $userId): bool
    {
        return $userId !== null && in_array((string) $userId, array_map('strval', $this->dibaca_pengguna ?? []), true);
    }

    public function tandaiDibacaOleh(?string $userId): bool
    {
        if (!$userId) {
            return false;
        }

        $readers = array_map('strval', $this->dibaca_pengguna ?? []);
        $userIdStr = (string) $userId;

        if (!in_array($userIdStr, $readers, true)) {
            $readers[] = $userIdStr;
            $this->forceFill(['dibaca_pengguna' => array_values($readers)])->save();
            return true;
        }

        return false;
    }

    public function getJumlahPembacaAttribute(): int
    {
        return is_array($this->dibaca_pengguna) ? count($this->dibaca_pengguna) : 0;
    }
}
