<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KalenderPendidikan extends Model
{
    use HasFactory;

    protected $table = 'kalender_pendidikan';

    protected $fillable = [
        'nama_kegiatan',
        'tipe',
        'mode_presensi',
        'tanggal_mulai',
        'tanggal_selesai',
        'libur_pd',
        'libur_guru',
        'libur_tendik',
        'warna',
        'keterangan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_mulai'   => 'date:Y-m-d',
        'tanggal_selesai' => 'date:Y-m-d',
        'libur_pd'        => 'boolean',
        'libur_guru'      => 'boolean',
        'libur_tendik'    => 'boolean',
    ];

    public const TIPE_LABELS = [
        'hari_efektif'         => 'Hari Efektif Belajar',
        'pembelajaran_daring'  => 'Pembelajaran Daring (PJJ)',
        'kegiatan_sekolah'     => 'Kegiatan Sekolah / Upacara',
        'ujian_asesmen'        => 'Ujian & Asesmen',
        'libur_nasional'       => 'Libur Nasional',
        'libur_semester'       => 'Libur Semester / Akhir Tahun',
        'libur_khusus'         => 'Libur Khusus Satuan Pendidikan',
    ];

    public const MODE_PRESENSI_LABELS = [
        'luring' => 'Luring (Efektif Sekolah)',
        'daring' => 'Daring (PJJ / Rumah)',
        'libur'  => 'Libur (Bebas Presensi)',
    ];

    public const MODE_PRESENSI_BADGES = [
        'luring' => '<span class="badge badge-success"><i class="fas fa-school me-1"></i> Luring</span>',
        'daring' => '<span class="badge badge-primary"><i class="fas fa-laptop-house me-1"></i> Daring (PJJ)</span>',
        'libur'  => '<span class="badge badge-danger"><i class="fas fa-umbrella-beach me-1"></i> Libur</span>',
    ];

    public function getTipeLabelAttribute(): string
    {
        return self::TIPE_LABELS[$this->tipe] ?? ucwords(str_replace('_', ' ', $this->tipe));
    }

    public function getModePresensiLabelAttribute(): string
    {
        $mode = $this->mode_presensi ?: ($this->libur_pd ? 'libur' : 'luring');
        return self::MODE_PRESENSI_LABELS[$mode] ?? ucwords($mode);
    }

    public function getModePresensiBadgeAttribute(): string
    {
        $mode = $this->mode_presensi ?: ($this->libur_pd ? 'libur' : 'luring');
        return self::MODE_PRESENSI_BADGES[$mode] ?? '<span class="badge">' . e($mode) . '</span>';
    }

    /**
     * Cek apakah suatu tanggal adalah hari libur untuk role tertentu (pd, guru, tendik)
     */
    public static function isLibur(string $date, string $role = 'pd'): bool
    {
        $column = match (strtolower($role)) {
            'guru' => 'libur_guru',
            'tendik' => 'libur_tendik',
            default => 'libur_pd',
        };

        return static::where('tanggal_mulai', '<=', $date)
            ->where('tanggal_selesai', '>=', $date)
            ->where(function ($q) use ($column) {
                $q->where($column, true)
                    ->orWhere('mode_presensi', 'libur');
            })
            ->exists();
    }

    /**
     * Cek apakah suatu tanggal dijadwalkan Pembelajaran Daring (PJJ)
     */
    public static function isDaring(string $date): bool
    {
        return static::where('tanggal_mulai', '<=', $date)
            ->where('tanggal_selesai', '>=', $date)
            ->where(function ($q) {
                $q->where('mode_presensi', 'daring')
                    ->orWhere('tipe', 'pembelajaran_daring');
            })
            ->exists();
    }

    /**
     * Evaluasi kondisi presensi pada tanggal tertentu (luring, daring, libur)
     */
    public static function getStatusHari(string $date, string $role = 'pd'): array
    {
        // 1. Cek agenda aktif pada tanggal tersebut
        $agenda = static::where('tanggal_mulai', '<=', $date)
            ->where('tanggal_selesai', '>=', $date)
            ->orderBy('id', 'desc')
            ->first();

        if (!$agenda) {
            return [
                'mode' => 'luring',
                'label' => 'Hari Efektif (Luring)',
                'badge' => self::MODE_PRESENSI_BADGES['luring'],
                'agenda' => null,
            ];
        }

        // 2. Evaluasi apakah libur
        $liburCol = match (strtolower($role)) {
            'guru' => $agenda->libur_guru,
            'tendik' => $agenda->libur_tendik,
            default => $agenda->libur_pd,
        };

        if ($liburCol || $agenda->mode_presensi === 'libur') {
            return [
                'mode' => 'libur',
                'label' => 'Hari Libur Sekolah',
                'badge' => self::MODE_PRESENSI_BADGES['libur'],
                'agenda' => $agenda,
            ];
        }

        // 3. Evaluasi apakah daring
        if ($agenda->mode_presensi === 'daring' || $agenda->tipe === 'pembelajaran_daring') {
            return [
                'mode' => 'daring',
                'label' => 'Pembelajaran Daring (PJJ)',
                'badge' => self::MODE_PRESENSI_BADGES['daring'],
                'agenda' => $agenda,
            ];
        }

        // 4. Default: Luring
        return [
            'mode' => 'luring',
            'label' => 'Hari Efektif (Luring)',
            'badge' => self::MODE_PRESENSI_BADGES['luring'],
            'agenda' => $agenda,
        ];
    }

    /**
     * Ambil seluruh agenda/kegiatan aktif pada rentang tanggal
     */
    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('tanggal_mulai', [$startDate, $endDate])
                ->orWhereBetween('tanggal_selesai', [$startDate, $endDate])
                ->orWhere(function ($sub) use ($startDate, $endDate) {
                    $sub->where('tanggal_mulai', '<=', $startDate)
                        ->where('tanggal_selesai', '>=', $endDate);
                });
        });
    }
}
