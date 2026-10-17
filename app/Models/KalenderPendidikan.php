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

    protected static function booted()
    {
        static::saving(function ($event) {
            if (empty($event->mode_presensi)) {
                $isLibur = $event->libur_pd || str_contains((string) $event->tipe, 'libur');
                $isDaring = $event->tipe === 'pembelajaran_daring';
                $event->mode_presensi = $isLibur ? 'libur' : ($isDaring ? 'daring' : 'luring');
            } elseif ($event->libur_pd && $event->mode_presensi !== 'libur') {
                $event->mode_presensi = 'libur';
            }
        });
    }

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
     * Accessor nama agenda / nama kegiatan
     */
    public function getNamaAgendaAttribute(): string
    {
        return $this->attributes['nama_kegiatan'] ?? ($this->attributes['nama_agenda'] ?? '');
    }

    /**
     * Helper nama hari Indonesia dari Carbon instance
     */
    public static function getNamaHariIndo(\Carbon\Carbon $date): string
    {
        return match ($date->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
            default => '',
        };
    }

    /**
     * Cek apakah suatu tanggal adalah hari libur untuk role tertentu (pd, guru, tendik, all)
     * Mengintegrasikan Skema Hari Sekolah (JadwalPengaturan) dan tabel kalender_pendidikan.
     */
    public static function isLibur(string $date, string $role = 'pd'): bool
    {
        $dCarbon = \Carbon\Carbon::parse($date);

        // 1. Cek apakah hari ini bukan hari aktif belajar sesuai Skema Hari Sekolah (5 Hari vs 6 Hari)
        if (class_exists(JadwalPengaturan::class) && !JadwalPengaturan::isHariAktif($dCarbon)) {
            return true;
        }

        // 2. Cek agenda libur resmi di tabel kalender_pendidikan
        $column = match (strtolower($role)) {
            'guru' => 'libur_guru',
            'tendik' => 'libur_tendik',
            'gtk' => null,
            'all', 'semua' => null,
            default => 'libur_pd',
        };

        return static::where('tanggal_mulai', '<=', $date)
            ->where('tanggal_selesai', '>=', $date)
            ->where(function ($q) use ($column, $role) {
                if ($column) {
                    $q->where($column, true)
                      ->orWhere('mode_presensi', 'libur')
                      ->orWhere('tipe', 'like', '%libur%');
                } elseif (strtolower($role) === 'gtk') {
                    $q->where('libur_guru', true)
                      ->orWhere('libur_tendik', true)
                      ->orWhere('mode_presensi', 'libur')
                      ->orWhere('tipe', 'like', '%libur%');
                } else {
                    $q->where('libur_pd', true)
                      ->orWhere('libur_guru', true)
                      ->orWhere('libur_tendik', true)
                      ->orWhere('mode_presensi', 'libur')
                      ->orWhere('tipe', 'like', '%libur%');
                }
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
     * Mengintegrasikan Skema Hari Sekolah (JadwalPengaturan) dan Kalender Pendidikan.
     */
    public static function getStatusHari(string $date, string $role = 'pd'): array
    {
        $dCarbon = \Carbon\Carbon::parse($date);
        $namaHari = self::getNamaHariIndo($dCarbon);
        $isHariAktifSekolah = class_exists(JadwalPengaturan::class)
            ? JadwalPengaturan::isHariAktif($dCarbon)
            : !in_array($dCarbon->dayOfWeekIso, [6, 7], true);

        // 1. Cek apakah ada agenda libur pada tanggal tersebut dari database kalender
        $column = match (strtolower($role)) {
            'guru' => 'libur_guru',
            'tendik' => 'libur_tendik',
            'gtk' => null,
            'all', 'semua' => null,
            default => 'libur_pd',
        };

        $liburAgenda = static::where('tanggal_mulai', '<=', $date)
            ->where('tanggal_selesai', '>=', $date)
            ->where(function ($q) use ($column, $role) {
                if ($column) {
                    $q->where($column, true)
                      ->orWhere('mode_presensi', 'libur')
                      ->orWhere('tipe', 'like', '%libur%');
                } elseif (strtolower($role) === 'gtk') {
                    $q->where('libur_guru', true)
                      ->orWhere('libur_tendik', true)
                      ->orWhere('mode_presensi', 'libur')
                      ->orWhere('tipe', 'like', '%libur%');
                } else {
                    $q->where('libur_pd', true)
                      ->orWhere('libur_guru', true)
                      ->orWhere('libur_tendik', true)
                      ->orWhere('mode_presensi', 'libur')
                      ->orWhere('tipe', 'like', '%libur%');
                }
            })
            ->orderBy('id', 'desc')
            ->first();

        if ($liburAgenda) {
            $namaKeg = $liburAgenda->nama_kegiatan ?: ($liburAgenda->nama_agenda ?: 'Hari Libur');
            return [
                'mode'        => 'libur',
                'is_libur'    => true,
                'is_weekend'  => !$isHariAktifSekolah,
                'label'       => 'Hari Libur Sekolah',
                'badge'       => self::MODE_PRESENSI_BADGES['libur'] ?? '<span class="badge badge-danger"><i class="fas fa-umbrella-beach me-1"></i> Libur</span>',
                'agenda'      => $liburAgenda,
                'nama_agenda' => $namaKeg,
                'keterangan'  => $liburAgenda->keterangan ?: 'Libur Sekolah / KBM Reguler Ditiadakan',
            ];
        }

        // 2. Ambil agenda umum lainnya jika ada (misal: kegiatan sekolah, asesmen, daring)
        $agenda = static::where('tanggal_mulai', '<=', $date)
            ->where('tanggal_selesai', '>=', $date)
            ->orderBy('id', 'desc')
            ->first();

        // 3. Jika bukan hari aktif sesuai skema hari sekolah dan tidak ada agenda sekolah khusus
        if (!$isHariAktifSekolah) {
            // Jika ada agenda sekolah yang berlangsung di akhir pekan (misal Lomba / Upacara Hari Besar)
            if ($agenda && ($agenda->mode_presensi === 'daring' || $agenda->tipe === 'pembelajaran_daring')) {
                return [
                    'mode'        => 'daring',
                    'is_libur'    => false,
                    'is_weekend'  => true,
                    'label'       => 'Pembelajaran Daring (PJJ)',
                    'badge'       => self::MODE_PRESENSI_BADGES['daring'] ?? '<span class="badge badge-info"><i class="fas fa-laptop-code me-1"></i> Daring</span>',
                    'agenda'      => $agenda,
                    'nama_agenda' => $agenda->nama_kegiatan ?: ($agenda->nama_agenda ?: ''),
                    'keterangan'  => $agenda->keterangan ?? null,
                ];
            }

            $skema = class_exists(JadwalPengaturan::class) ? (JadwalPengaturan::getSettings()->skema_hari ?? '5_hari') : '5_hari';
            $skemaLabel = ($skema === '6_hari') ? '6 Hari Sekolah (Senin - Sabtu)' : '5 Hari Sekolah (Senin - Jumat)';

            return [
                'mode'        => 'libur',
                'is_libur'    => true,
                'is_weekend'  => true,
                'label'       => "Libur Akhir Pekan ({$namaHari})",
                'badge'       => '<span class="badge badge-secondary" style="background: rgba(148,163,184,0.18); color: #64748b; border: 1px solid rgba(148,163,184,0.3);"><i class="fas fa-bed me-1"></i> Libur Akhir Pekan</span>',
                'agenda'      => $agenda,
                'nama_agenda' => $agenda ? ($agenda->nama_kegiatan ?: "Libur Akhir Pekan ({$namaHari})") : "Libur Akhir Pekan ({$namaHari})",
                'keterangan'  => $agenda ? ($agenda->keterangan ?: "Kegiatan pada akhir pekan: {$agenda->nama_kegiatan}") : "Bukan hari operasional sekolah ({$skemaLabel})",
            ];
        }

        // 4. Hari aktif sekolah tanpa agenda: Efektif Luring Normal
        if (!$agenda) {
            return [
                'mode'        => 'luring',
                'is_libur'    => false,
                'is_weekend'  => false,
                'label'       => 'Hari Efektif (Luring)',
                'badge'       => self::MODE_PRESENSI_BADGES['luring'] ?? '<span class="badge badge-success"><i class="fas fa-school me-1"></i> Luring</span>',
                'agenda'      => null,
                'nama_agenda' => null,
                'keterangan'  => null,
            ];
        }

        // 5. Hari aktif sekolah dengan agenda daring
        if ($agenda->mode_presensi === 'daring' || $agenda->tipe === 'pembelajaran_daring') {
            return [
                'mode'        => 'daring',
                'is_libur'    => false,
                'is_weekend'  => false,
                'label'       => 'Pembelajaran Daring (PJJ)',
                'badge'       => self::MODE_PRESENSI_BADGES['daring'] ?? '<span class="badge badge-info"><i class="fas fa-laptop-code me-1"></i> Daring (PJJ)</span>',
                'agenda'      => $agenda,
                'nama_agenda' => $agenda->nama_kegiatan ?: ($agenda->nama_agenda ?: ''),
                'keterangan'  => $agenda->keterangan ?? null,
            ];
        }

        // 6. Default hari aktif dengan agenda sekolah khusus
        return [
            'mode'        => 'luring',
            'is_libur'    => false,
            'is_weekend'  => false,
            'label'       => 'Hari Efektif (Luring)',
            'badge'       => self::MODE_PRESENSI_BADGES['luring'] ?? '<span class="badge badge-success"><i class="fas fa-school me-1"></i> Luring</span>',
            'agenda'      => $agenda,
            'nama_agenda' => $agenda->nama_kegiatan ?: ($agenda->nama_agenda ?: ''),
            'keterangan'  => $agenda->keterangan ?? null,
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
     * memperhitungkan konfigurasi skema hari aktif sekolah (JadwalPengaturan) dan libur kalender.
     */
    public static function hitungHariEfektif(string $startDate, string $endDate, string $role = 'pd'): int
    {
        if ($startDate > $endDate) return 0;

        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);

        $column = match (strtolower($role)) {
            'guru' => 'libur_guru',
            'tendik' => 'libur_tendik',
            'gtk' => null,
            default => 'libur_pd',
        };

        // Ambil seluruh agenda libur dalam rentang ini dalam 1 query
        $liburAgendas = static::betweenDates($startDate, $endDate)
            ->where(function ($q) use ($column, $role) {
                if ($column) {
                    $q->where($column, true)
                        ->orWhere('mode_presensi', 'libur')
                        ->orWhere('tipe', 'like', '%libur%');
                } elseif (strtolower($role) === 'gtk') {
                    $q->where('libur_guru', true)
                        ->orWhere('libur_tendik', true)
                        ->orWhere('mode_presensi', 'libur')
                        ->orWhere('tipe', 'like', '%libur%');
                } else {
                    $q->where('libur_pd', true)
                        ->orWhere('libur_guru', true)
                        ->orWhere('libur_tendik', true)
                        ->orWhere('mode_presensi', 'libur')
                        ->orWhere('tipe', 'like', '%libur%');
                }
            })
            ->select('tanggal_mulai', 'tanggal_selesai')
            ->get();

        $count = 0;
        $curr = $start->copy();

        while ($curr->lte($end)) {
            // 1. Cek apakah hari kerja mingguan aktif (Senin - Jumat / Sabtu) sesuai Skema Hari Sekolah
            $isAktif = class_exists(JadwalPengaturan::class)
                ? JadwalPengaturan::isHariAktif($curr)
                : (class_exists(PresensiPengaturan::class) ? PresensiPengaturan::getPengaturan()->isHariAktif($curr) : !in_array($curr->dayOfWeekIso, [6, 7], true));

            if ($isAktif) {
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

    /**
     * Dapatkan data matriks kalender akademik visual (Bulan, Semester, Tahun)
     * beserta rekap agenda dan keterangan per bulan.
     */
    public static function getAcademicCalendarData(
        string $tahunAjaran,
        string $semester = 'semua',
        ?string $bulan = null,
        string $scope = 'semester',
        string $tipe = '',
        string $modePresensi = ''
    ): array {
        $parts = explode('/', $tahunAjaran);
        $now = now();
        $currYear = (int) $now->year;
        $startYear = isset($parts[0]) && is_numeric($parts[0]) ? (int) $parts[0] : $currYear;
        $endYear = isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : $startYear + 1;

        $allAcademicMonths = [
            ['year' => $startYear, 'month' => 7,  'month_str' => '07', 'name' => 'Juli',      'semester' => '1'],
            ['year' => $startYear, 'month' => 8,  'month_str' => '08', 'name' => 'Agustus',   'semester' => '1'],
            ['year' => $startYear, 'month' => 9,  'month_str' => '09', 'name' => 'September', 'semester' => '1'],
            ['year' => $startYear, 'month' => 10, 'month_str' => '10', 'name' => 'Oktober',   'semester' => '1'],
            ['year' => $startYear, 'month' => 11, 'month_str' => '11', 'name' => 'November',  'semester' => '1'],
            ['year' => $startYear, 'month' => 12, 'month_str' => '12', 'name' => 'Desember',  'semester' => '1'],
            ['year' => $endYear,   'month' => 1,  'month_str' => '01', 'name' => 'Januari',   'semester' => '2'],
            ['year' => $endYear,   'month' => 2,  'month_str' => '02', 'name' => 'Februari',  'semester' => '2'],
            ['year' => $endYear,   'month' => 3,  'month_str' => '03', 'name' => 'Maret',     'semester' => '2'],
            ['year' => $endYear,   'month' => 4,  'month_str' => '04', 'name' => 'April',     'semester' => '2'],
            ['year' => $endYear,   'month' => 5,  'month_str' => '05', 'name' => 'Mei',       'semester' => '2'],
            ['year' => $endYear,   'month' => 6,  'month_str' => '06', 'name' => 'Juni',      'semester' => '2'],
        ];

        // Validasi Scope
        if (!in_array($scope, ['bulan', 'semester', 'tahun'], true)) {
            $scope = (!empty($bulan)) ? 'bulan' : (($semester !== 'semua') ? 'semester' : 'tahun');
        }

        // Tentukan daftar bulan yang akan dirender
        $targetMonths = [];
        $selectedMonthObj = null;

        if ($scope === 'bulan') {
            $targetMonthNum = !empty($bulan) ? (int) $bulan : (int) $now->month;
            foreach ($allAcademicMonths as $m) {
                if ($m['month'] === $targetMonthNum) {
                    $selectedMonthObj = $m;
                    $targetMonths = [$m];
                    break;
                }
            }
            if (empty($targetMonths)) {
                $selectedMonthObj = $allAcademicMonths[0];
                $targetMonths = [$allAcademicMonths[0]];
            }
        } elseif ($scope === 'semester') {
            $targetSemester = $semester;
            if ($targetSemester === 'semua' || empty($targetSemester)) {
                $targetSemester = ($now->month >= 7) ? '1' : '2';
            }
            $targetMonths = array_values(array_filter($allAcademicMonths, function ($m) use ($targetSemester) {
                return $m['semester'] === $targetSemester;
            }));
        } else {
            // scope === 'tahun'
            $targetMonths = $allAcademicMonths;
        }

        // Ambil semua event kalender dalam 1 tahun ajaran penuh
        $eventsQuery = static::betweenDates("{$startYear}-07-01", "{$endYear}-06-30");
        if ($tipe !== '') {
            $eventsQuery->where('tipe', $tipe);
        }
        if ($modePresensi !== '') {
            $eventsQuery->where('mode_presensi', $modePresensi);
        }
        $allEvents = $eventsQuery->orderBy('tanggal_mulai')->get();

        // Petakan event per hari Y-m-d
        $eventsByDate = [];
        foreach ($allEvents as $ev) {
            $curD = $ev->tanggal_mulai->copy();
            $endD = $ev->tanggal_selesai->copy();
            while ($curD->lte($endD)) {
                $dStr = $curD->format('Y-m-d');
                $eventsByDate[$dStr][] = $ev;
                $curD->addDay();
            }
        }

        $todayStr = now()->toDateString();
        $monthsData = [];

        foreach ($targetMonths as $mInfo) {
            $y = $mInfo['year'];
            $m = $mInfo['month'];
            $startCarbon = \Carbon\Carbon::createFromDate($y, $m, 1);
            $daysInMonth = $startCarbon->daysInMonth;
            $firstDayIso = $startCarbon->dayOfWeekIso; // 1 = Sen, 7 = Min
            $monthStartStr = $startCarbon->format('Y-m-d');
            $monthEndStr = $startCarbon->copy()->endOfMonth()->format('Y-m-d');

            // Agenda yang bersinggungan dengan bulan ini
            $monthEvents = $allEvents->filter(function ($ev) use ($monthStartStr, $monthEndStr) {
                $evStart = $ev->tanggal_mulai->format('Y-m-d');
                $evEnd = $ev->tanggal_selesai->format('Y-m-d');
                return ($evStart <= $monthEndStr && $evEnd >= $monthStartStr);
            })->values();

            // Bangun grid minggu
            $weeks = [];
            $curWeek = [];

            // Padding hari sebelum tgl 1
            for ($pad = 1; $pad < $firstDayIso; $pad++) {
                $curWeek[] = null;
            }

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dateStr = sprintf('%04d-%02d-%02d', $y, $m, $d);
                $dCarbon = \Carbon\Carbon::createFromDate($y, $m, $d);
                $dayOfWeek = $dCarbon->dayOfWeekIso;
                $dayEvs = $eventsByDate[$dateStr] ?? [];

                $primaryEvent = null;
                if (!empty($dayEvs)) {
                    $primaryEvent = collect($dayEvs)->sortBy(function ($e) {
                        if ($e->libur_pd || $e->mode_presensi === 'libur') return 1;
                        if ($e->tipe === 'ujian_asesmen') return 2;
                        if ($e->mode_presensi === 'daring') return 3;
                        return 4;
                    })->first();
                }

                $isSunday = ($dayOfWeek === 7);
                $isSaturday = ($dayOfWeek === 6);
                $isHariAktif = class_exists(JadwalPengaturan::class) ? JadwalPengaturan::isHariAktif($dCarbon) : (!$isSunday && !$isSaturday);

                $curWeek[] = [
                    'day'           => $d,
                    'date'          => $dateStr,
                    'is_today'      => ($dateStr === $todayStr),
                    'is_sunday'     => $isSunday,
                    'is_saturday'   => $isSaturday,
                    'is_weekend'    => !$isHariAktif,
                    'is_hari_aktif' => $isHariAktif,
                    'day_of_week'   => $dayOfWeek,
                    'events'        => $dayEvs,
                    'primary_event' => $primaryEvent,
                ];

                if (count($curWeek) === 7) {
                    $weeks[] = $curWeek;
                    $curWeek = [];
                }
            }

            if (!empty($curWeek)) {
                while (count($curWeek) < 7) {
                    $curWeek[] = null;
                }
                $weeks[] = $curWeek;
            }

            $monthsData[] = [
                'year'               => $y,
                'month'              => $m,
                'month_str'          => $mInfo['month_str'],
                'name'               => $mInfo['name'],
                'full_label'         => "{$mInfo['name']} {$y}",
                'semester'           => $mInfo['semester'],
                'days_in_month'      => $daysInMonth,
                'weeks'              => $weeks,
                'events'             => $monthEvents,
                'events_count'       => $monthEvents->count(),
                'start_date'         => $monthStartStr,
                'end_date'           => $monthEndStr,
                'heb_count'          => static::hitungHariEfektif($monthStartStr, $monthEndStr, 'pd'),
            ];
        }

        return [
            'scope'               => $scope,
            'months'              => $monthsData,
            'selected_month'      => $selectedMonthObj,
            'all_academic_months' => $allAcademicMonths,
            'start_year'          => $startYear,
            'end_year'            => $endYear,
            'tahun_ajaran'        => $tahunAjaran,
        ];
    }
}
