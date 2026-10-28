<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Pemberlakuan jadwal per pekan (lapis 2 dari 3).
 *
 * Satu baris = satu pekan dalam satu semester. Pola jadwal tetap berada di
 * `jadwal_kbm`; tabel ini hanya menentukan pekan mana yang sudah boleh
 * diberlakukan (terlihat oleh guru/siswa) dan kapan rentang tanggalnya.
 */
class JadwalKbmPekan extends Model
{
    protected $table = 'jadwal_kbm_pekan';

    protected $fillable = [
        'tahun_ajaran',
        'semester',
        'pekan_ke',
        'tanggal_mulai',
        'tanggal_selesai',
        'sumber',
        'status',
        'is_efektif',
        'catatan',
        'diberlakukan_pada',
        'diberlakukan_oleh',
    ];

    protected $casts = [
        'pekan_ke'           => 'integer',
        'is_efektif'         => 'boolean',
        'tanggal_mulai'      => 'date',
        'tanggal_selesai'    => 'date',
        'diberlakukan_pada'  => 'datetime',
    ];

    public const STATUS_LIST = ['draft', 'aktif', 'libur', 'ujian'];

    /** Status yang dianggap "sudah diberlakukan" (terlihat oleh guru/siswa). */
    public const STATUS_BERLAKU = ['aktif', 'libur', 'ujian'];

    public const STATUS_LABELS = [
        'draft' => 'Draft',
        'aktif' => 'Aktif',
        'libur' => 'Libur',
        'ujian' => 'Ujian / Asesmen',
    ];

    public const STATUS_BADGES = [
        'draft' => 'background: rgba(245,158,11,0.15); color: var(--warning); border: 1px solid rgba(245,158,11,0.3);',
        'aktif' => 'background: rgba(16,185,129,0.15); color: var(--success); border: 1px solid rgba(16,185,129,0.3);',
        'libur' => 'background: rgba(148,163,184,0.18); color: var(--text-muted); border: 1px solid var(--border-color);',
        'ujian' => 'background: rgba(139,92,246,0.15); color: var(--purple); border: 1px solid rgba(139,92,246,0.3);',
    ];

    /** Label ringkas rentang tanggal, mis. "13 – 18 Okt 2026". */
    public function getRentangLabelAttribute(): string
    {
        $mulai = $this->tanggal_mulai;
        $selesai = $this->tanggal_selesai;

        if (!$mulai || !$selesai) {
            return '-';
        }

        if ($mulai->month === $selesai->month && $mulai->year === $selesai->year) {
            return $mulai->translatedFormat('d') . ' – ' . $selesai->translatedFormat('d M Y');
        }

        return $mulai->translatedFormat('d M') . ' – ' . $selesai->translatedFormat('d M Y');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUS_BADGES[$this->status] ?? self::STATUS_BADGES['draft'];
    }

    /** Apakah pekan ini sudah boleh dipakai sebagai dasar cetak/jadwal berlaku. */
    public function isBerlaku(): bool
    {
        return in_array($this->status, self::STATUS_BERLAKU, true);
    }

    /** Sumber efektif pekan: fallback ke sumber global bila kolom kosong. */
    public function resolveSumber(string $fallback = 'otomatis'): string
    {
        $sumber = trim((string) $this->sumber);

        return $sumber !== '' ? $sumber : $fallback;
    }

    /**
     * Cek apakah sebuah tanggal berada di dalam rentang pekan ini.
     */
    public function mencakupTanggal(string $tanggal): bool
    {
        return $this->tanggal_mulai->toDateString() <= $tanggal
            && $this->tanggal_selesai->toDateString() >= $tanggal;
    }

    public function scopePeriode($query, string $tahunAjaran, string $semester)
    {
        return $query->where('tahun_ajaran', $tahunAjaran)->where('semester', (string) $semester);
    }

    public function scopeBerlaku($query)
    {
        return $query->whereIn('status', self::STATUS_BERLAKU);
    }

    public function scopeMencakupTanggal($query, string $tanggal)
    {
        return $query->where('tanggal_mulai', '<=', $tanggal)->where('tanggal_selesai', '>=', $tanggal);
    }

    /** Carbon rentang untuk keperluan iterasi harian. */
    public function tanggalMulaiCarbon(): ?Carbon
    {
        return $this->tanggal_mulai ? $this->tanggal_mulai->copy() : null;
    }
}
