<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Formulir extends Model
{
    use HasFactory;

    protected $table = 'formulir';

    protected $fillable = [
        'judul',
        'slug',
        'deskripsi',
        'is_active',
        'is_public',
        'auth_required',
        'target_peran',
        'target_rombel_id',
        'target_tingkat',
        'limit_one_response',
        'tanggal_mulai',
        'tanggal_selesai',
        'skema',
        'pengaturan',
        'created_by',
        'pengguna_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'auth_required' => 'boolean',
        'limit_one_response' => 'boolean',
        'tanggal_mulai' => 'datetime',
        'tanggal_selesai' => 'datetime',
        'skema' => 'array',
        'pengaturan' => 'array',
    ];

    public function respon(): HasMany
    {
        return $this->hasMany(FormulirRespon::class, 'formulir_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id', 'pengguna_id');
    }

    public function getPublicUrlAttribute(): string
    {
        return url('/f/' . $this->slug);
    }

    public function isScheduleOpen(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();

        if ($this->tanggal_mulai && $now->lt($this->tanggal_mulai)) {
            return false;
        }

        if ($this->tanggal_selesai && $now->gt($this->tanggal_selesai)) {
            return false;
        }

        return true;
    }

    public function getScheduleStatusLabel(): string
    {
        if (!$this->is_active) {
            return 'Nonaktif';
        }

        $now = Carbon::now();
        if ($this->tanggal_mulai && $now->lt($this->tanggal_mulai)) {
            return 'Belum Dimulai';
        }
        if ($this->tanggal_selesai && $now->gt($this->tanggal_selesai)) {
            return 'Sudah Ditutup';
        }

        return 'Aktif';
    }

    public function hasUserResponded($user = null, ?string $ip = null): bool
    {
        if (!$this->limit_one_response) {
            return false;
        }

        if ($user) {
            $penggunaId = is_array($user) ? ($user['pengguna_id'] ?? null) : ($user->pengguna_id ?? null);
            $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
            $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

            $query = $this->respon()->where(function ($q) use ($penggunaId, $pdId, $ptkId) {
                $hasCondition = false;
                if ($penggunaId) {
                    $q->where('pengguna_id', $penggunaId);
                    $hasCondition = true;
                }
                if ($pdId) {
                    if ($hasCondition) {
                        $q->orWhere('peserta_didik_id', $pdId);
                    } else {
                        $q->where('peserta_didik_id', $pdId);
                        $hasCondition = true;
                    }
                }
                if ($ptkId) {
                    if ($hasCondition) {
                        $q->orWhere('ptk_id', $ptkId);
                    } else {
                        $q->where('ptk_id', $ptkId);
                        $hasCondition = true;
                    }
                }
            });

            return $query->exists();
        }

        // Jika anonim/tamu tanpa user, periksa via IP jika ada
        if ($ip && !$this->auth_required) {
            return $this->respon()->where('ip_address', $ip)->exists();
        }

        return false;
    }
}
