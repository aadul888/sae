<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PresensiHarian extends Model
{
    use HasFactory;

    protected $table = 'presensi_harian';

    protected $fillable = [
        'peserta_didik_id',
        'nisn',
        'rombongan_belajar_id',
        'tanggal',
        'status',
        'jam_masuk',
        'jam_pulang',
        'menit_terlambat',
        'status_ketepatan_masuk',
        'status_ketepatan_pulang',
        'metode_masuk',
        'metode_pulang',
        'foto_masuk',
        'foto_pulang',
        'keterangan',
        'lampiran_dokumen',
        'verified_by',
        'device_info',
        'latitude',
        'longitude',
        'jarak_meter',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'menit_terlambat' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'jarak_meter' => 'float',
    ];

    protected $appends = [
        'status_label',
        'status_badge',
        'foto_masuk_url',
        'foto_pulang_url',
    ];

    public const STATUS_LABELS = [
        'H' => 'Hadir Tepat Waktu',
        'T' => 'Terlambat',
        'I' => 'Izin',
        'S' => 'Sakit',
        'A' => 'Alpha',
        'D' => 'Dispensasi',
    ];

    public const STATUS_BADGES = [
        'H' => '<span class="badge badge-success"><i class="fas fa-check-circle me-1"></i> Hadir</span>',
        'T' => '<span class="badge badge-warning"><i class="fas fa-clock me-1"></i> Terlambat</span>',
        'I' => '<span class="badge badge-primary"><i class="fas fa-file-signature me-1"></i> Izin</span>',
        'S' => '<span class="badge badge-purple" style="background: rgba(139,92,246,0.15); color: #8b5cf6;"><i class="fas fa-notes-medical me-1"></i> Sakit</span>',
        'A' => '<span class="badge badge-danger"><i class="fas fa-times-circle me-1"></i> Alpha</span>',
        'D' => '<span class="badge badge-info"><i class="fas fa-award me-1"></i> Dispen</span>',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ($this->status ?? '-');
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUS_BADGES[$this->status] ?? ('<span class="badge">' . ($this->status ?? '-') . '</span>');
    }

    public function getFotoMasukUrlAttribute(): ?string
    {
        if (empty($this->foto_masuk)) {
            return null;
        }
        return asset('storage/' . ltrim($this->foto_masuk, '/'));
    }

    public function getFotoPulangUrlAttribute(): ?string
    {
        if (empty($this->foto_pulang)) {
            return null;
        }
        return asset('storage/' . ltrim($this->foto_pulang, '/'));
    }

    /**
     * Relasi ke data Peserta Didik Dapodik
     */
    public function pesertaDidik()
    {
        return $this->belongsTo(DB::table('peserta_didik'), 'peserta_didik_id', 'peserta_didik_id');
    }

    /**
     * Scope untuk presensi hari ini
     */
    public function scopeToday($query)
    {
        return $query->where('tanggal', now()->toDateString());
    }

    /**
     * Scope berdasarkan rombel
     */
    public function scopeByRombel($query, string $rombelId)
    {
        return $query->where('rombongan_belajar_id', $rombelId);
    }

    /**
     * Otomatisasi Pulang Cepat:
     * Siswa yang hadir/terlambat di hari-hari sebelumnya (tanggal < hari ini)
     * namun tidak melakukan absensi pulang, otomatis ditandai sebagai 'pulang_cepat'.
     */
    public static function autoCloseUncheckedOut(): int
    {
        $today = now()->toDateString();

        return (int) self::where('tanggal', '<', $today)
            ->whereIn('status', ['H', 'T'])
            ->whereNull('jam_pulang')
            ->where(function ($q) {
                $q->whereNull('status_ketepatan_pulang')
                  ->orWhere('status_ketepatan_pulang', '!=', 'pulang_cepat');
            })
            ->update([
                'status_ketepatan_pulang' => 'pulang_cepat',
                'metode_pulang' => 'otomatis',
                'keterangan' => DB::raw("IF(keterangan IS NULL OR keterangan = '', 'Pulang Cepat (Tidak tap pulang)', keterangan)"),
                'updated_at' => now(),
            ]);
    }
}
