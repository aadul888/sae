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

    /**
     * Hitung jumlah Hari Efektif Belajar dalam rentang tanggal tertentu,
     * memperhitungkan konfigurasi hari kerja mingguan (PresensiPengaturan) dan libur kalender.
     */
    public static function hitungHariEfektif(string $startDate, string $endDate, string $role = 'pd'): int
    {
        if ($startDate > $endDate) return 0;

        $pengaturan = PresensiPengaturan::getPengaturan();
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);

        $column = match (strtolower($role)) {
            'guru' => 'libur_guru',
            'tendik' => 'libur_tendik',
            default => 'libur_pd',
        };

        // Ambil seluruh agenda libur dalam rentang ini dalam 1 query
        $liburAgendas = static::betweenDates($startDate, $endDate)
            ->where(function ($q) use ($column) {
                $q->where($column, true)
                    ->orWhere('mode_presensi', 'libur');
            })
            ->select('tanggal_mulai', 'tanggal_selesai')
            ->get();

        $count = 0;
        $curr = $start->copy();

        while ($curr->lte($end)) {
            // 1. Cek apakah hari kerja mingguan aktif (Senin - Jumat / Sabtu)
            if ($pengaturan->isHariAktif($curr)) {
                $dateStr = $curr->format('Y-m-d');
                $isLibur = false;
                foreach ($liburAgendas as $ag) {
                    $agMulai = is_string($ag->tanggal_mulai) ? substr($ag->tanggal_mulai, 0, 10) : $ag->tanggal_mulai->format('Y-m-d');
                    $agSelesai = is_string($ag->tanggal_selesai) ? substr($ag->tanggal_selesai, 0, 10) : $ag->tanggal_selesai->format('Y-m-d');
                    if ($agMulai <= $dateStr && $agSelesai >= $dateStr) {
                        $isLibur = true;
                        break;
                    }
                }
                if (!$isLibur) {
                    $count++;
                }
            }
            $curr->addDay();
        }

        return $count;
    }

    /**
     * Hitung jumlah Hari Efektif Belajar yang SUDAH BERJALAN (dibatasi hingga tanggal hari ini / limitDate)
     * Digunakan sebagai pembagi (denominator) persentase kehadiran yang akurat.
     */
    public static function hitungHariEfektifBerjalan(string $startDate, string $endDate, ?string $limitDate = null, string $role = 'pd'): int
    {
        $limit = $limitDate ?: now()->toDateString();
        if ($startDate > $limit) {
            return 0;
        }

        $effectiveEnd = min($endDate, $limit);
        return static::hitungHariEfektif($startDate, $effectiveEnd, $role);
    }

    /**
     * Resolusi rentang tanggal akademik berdasarkan Tahun Ajaran, Semester, atau Bulan
     */
    public static function resolvePeriodeDates(?string $tahunAjaran = null, ?string $semester = null, ?string $bulan = null, ?string $tahun = null): array
    {
        $now = now();
        $currYear = (int) $now->year;
        $defaultTa = ($now->month >= 7) ? "{$currYear}/" . ($currYear + 1) : ($currYear - 1) . "/{$currYear}";
        $ta = $tahunAjaran ?: $defaultTa;

        $parts = explode('/', $ta);
        $startYear = isset($parts[0]) && is_numeric($parts[0]) ? (int) $parts[0] : $currYear;
        $endYear = isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : $startYear + 1;

        // Default 1 Tahun Ajaran penuh (2 Semester)
        $startDate = "{$startYear}-07-01";
        $endDate = "{$endYear}-06-30";
        $label = "Tahun Pelajaran {$ta}";

        if ($semester === '1') {
            $startDate = "{$startYear}-07-01";
            $endDate = "{$startYear}-12-31";
            $label = "Semester Ganjil (1) TP {$ta}";
        } elseif ($semester === '2') {
            $startDate = "{$endYear}-01-01";
            $endDate = "{$endYear}-06-30";
            $label = "Semester Genap (2) TP {$ta}";
        }

        if (!empty($bulan)) {
            $b = (int) $bulan;
            // Jika bulan 7..12 gunakan startYear, jika bulan 1..6 gunakan endYear
            $calYear = ($b >= 7) ? $startYear : $endYear;
            if (!empty($tahun) && is_numeric($tahun)) {
                $calYear = (int) $tahun;
            }
            $startCarbon = \Carbon\Carbon::createFromDate($calYear, $b, 1)->startOfMonth();
            $endCarbon = $startCarbon->copy()->endOfMonth();
            $startDate = $startCarbon->toDateString();
            $endDate = $endCarbon->toDateString();
            $label = "Bulan " . $startCarbon->translatedFormat('F Y');
        }

        return [
            'start'        => $startDate,
            'end'          => $endDate,
            'tahun_ajaran' => $ta,
            'semester'     => $semester ?: 'semua',
            'label'        => $label,
        ];
    }
}
