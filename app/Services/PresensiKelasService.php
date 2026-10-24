<?php

namespace App\Services;

use App\Models\JadwalPengaturan;
use App\Models\KalenderPendidikan;
use App\Models\NotifikasiTransaksi;
use App\Models\PresensiHarian;
use App\Models\PresensiMapel;
use App\Models\PresensiPengaturan;
use App\Models\RolePermission;
use App\Models\SekolahMeta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Service operasional Presensi Kelas (monitoring KBM harian, update massal, reset, dan cetak ekspor).
 * ponytail: static-orchestrator; upgrade ke streaming export generator jika rekap sekolah melibatkan > 5.000 siswa.
 */
class PresensiKelasService
{
    /**
     * Kumpulkan seluruh dataset untuk tampilan halaman Presensi Kelas.
     */
    public static function getKelasData(Request $request): array
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $userId = is_array($user) ? ($user['id'] ?? ($user['pengguna_id'] ?? null)) : ($user->id ?? ($user->pengguna_id ?? null));
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        $isAdmin = in_array($role, ['admin', 'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan'], true);
        $isPiket = $isAdmin || RolePermission::canAccess($user, 'menu_piket') || RolePermission::canAccess($user, 'menu_piket_presensi_kelas');
        $canViewAllClasses = $isAdmin || $isPiket;

        $waliRombel = null;
        if ($ptkId || $userId) {
            $duty = DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('rtt.kode', 'WALI_KELAS')
                ->where('ptt.is_active', true)
                ->where(function ($q) use ($userId, $ptkId) {
                    if ($userId) $q->where('ptt.user_id', $userId);
                    if ($ptkId) $q->orWhere('ptt.ptk_id', $ptkId);
                })
                ->select('ptt.rombel_id')
                ->first();

            if ($duty && $duty->rombel_id) {
                $waliRombel = $duty->rombel_id;
            }
        }

        $hariIni = match (now()->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        };
        $nowHi = now()->format('H:i');
        $modeAktif = JadwalPengaturan::getModeAktif();

        $activeJadwal = null;
        if ($ptkId && Schema::hasTable('jadwal_kbm')) {
            $jq = DB::table('jadwal_kbm')
                ->where('is_active', true)
                ->where('ptk_id', $ptkId)
                ->where('hari', $hariIni);
            if ($modeAktif) {
                $jq->where('sumber', $modeAktif);
            }
            $todaySchedules = $jq->orderBy('jam_ke_mulai', 'asc')->get();

            foreach ($todaySchedules as $j) {
                if (!empty($j->jam_mulai) && !empty($j->jam_selesai)) {
                    $jm = substr($j->jam_mulai, 0, 5);
                    $js = substr($j->jam_selesai, 0, 5);
                    if ($nowHi >= $jm && $nowHi <= $js) {
                        $activeJadwal = $j;
                        break;
                    }
                }
            }

            if (!$activeJadwal) {
                foreach ($todaySchedules as $j) {
                    if (!empty($j->jam_selesai) && substr($j->jam_selesai, 0, 5) >= $nowHi) {
                        $activeJadwal = $j;
                        break;
                    }
                }
            }

            if (!$activeJadwal && $todaySchedules->isNotEmpty()) {
                $activeJadwal = $todaySchedules->first();
            }
        }

        $rombelQuery = DB::table('rombongan_belajar');

        if (!$canViewAllClasses && $ptkId) {
            $pembelajaranRombelIds = DB::table('pembelajaran')
                ->where('ptk_id', $ptkId)
                ->pluck('rombongan_belajar_id')
                ->filter()
                ->unique()
                ->toArray();

            $jadwalRombelIds = DB::table('jadwal_kbm')
                ->where('ptk_id', $ptkId)
                ->pluck('rombongan_belajar_id')
                ->filter()
                ->unique()
                ->toArray();

            $guruRombelIds = array_values(array_unique(array_filter(array_merge(
                $pembelajaranRombelIds,
                $jadwalRombelIds
            ))));

            $rombelQuery->whereIn('rombongan_belajar_id', $guruRombelIds);
        } else {
            $rombelQuery->where('jenis_rombel', '1');
        }

        $rombelList = $rombelQuery
            ->orderBy('tingkat_pendidikan_id', 'asc')
            ->orderBy('nama', 'asc')
            ->select('rombongan_belajar_id', 'nama')
            ->get();

        $validRombelIds = $rombelList->pluck('rombongan_belajar_id')->toArray();
        $requestedRombelId = $request->input('rombel_id');

        if ($requestedRombelId && in_array($requestedRombelId, $validRombelIds, true)) {
            $selectedRombelId = $requestedRombelId;
        } elseif ($activeJadwal && in_array($activeJadwal->rombongan_belajar_id, $validRombelIds, true)) {
            $selectedRombelId = $activeJadwal->rombongan_belajar_id;
        } else {
            $selectedRombelId = $rombelList->first()?->rombongan_belajar_id ?? '';
        }

        $tanggal = $request->input('tanggal', now()->toDateString());

        $defaultJamKe = '';
        if ($activeJadwal && ($selectedRombelId === $activeJadwal->rombongan_belajar_id)) {
            $defaultJamKe = ($activeJadwal->jam_ke_mulai == $activeJadwal->jam_ke_selesai)
                ? (string) $activeJadwal->jam_ke_mulai
                : "{$activeJadwal->jam_ke_mulai}-{$activeJadwal->jam_ke_selesai}";
        }
        $jamKe = $request->has('jam_ke') ? $request->input('jam_ke', '') : $defaultJamKe;

        $pembelajaranList = collect();
        if ($selectedRombelId) {
            $pQuery = DB::table('pembelajaran as p')
                ->where('p.rombongan_belajar_id', $selectedRombelId);

            if (!$canViewAllClasses && $ptkId) {
                $pQuery->where('p.ptk_id', $ptkId);
            }

            $pembelajaranList = $pQuery->select(
                    'p.pembelajaran_id',
                    'p.nama_mata_pelajaran',
                    'p.ptk_id',
                    'p.jam_mengajar_per_minggu'
                )
                ->orderBy('p.nama_mata_pelajaran', 'asc')
                ->get();
        }

        $validPembelajaranIds = $pembelajaranList->pluck('pembelajaran_id')->toArray();
        $requestedPemId = $request->input('pembelajaran_id');

        if ($requestedPemId && in_array($requestedPemId, $validPembelajaranIds, true)) {
            $selectedPembelajaranId = $requestedPemId;
        } else {
            $defaultPembelajaranId = null;
            if ($activeJadwal && ($selectedRombelId === $activeJadwal->rombongan_belajar_id)) {
                $matchPem = $pembelajaranList->firstWhere('pembelajaran_id', $activeJadwal->pembelajaran_id)
                    ?: $pembelajaranList->firstWhere('nama_mata_pelajaran', $activeJadwal->nama_mata_pelajaran);
                if ($matchPem) {
                    $defaultPembelajaranId = $matchPem->pembelajaran_id;
                }
            }
            if (!$defaultPembelajaranId && $pembelajaranList->isNotEmpty()) {
                $defaultPembelajaranId = $pembelajaranList->first()->pembelajaran_id;
            }
            $selectedPembelajaranId = $defaultPembelajaranId;
        }

        $selectedPembelajaran = $pembelajaranList->firstWhere('pembelajaran_id', $selectedPembelajaranId);

        $siswaList = collect();
        $selectedRombel = null;
        $totalSiswa = 0;
        $sort = $request->input('sort', 'nama');
        $sortDir = strtolower($request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = $request->input('per_page', 25);

        $rekap = [
            'total'     => 0,
            'hadir'     => 0,
            'terlambat' => 0,
            'izin'      => 0,
            'sakit'     => 0,
            'alpha'     => 0,
            'belum'     => 0,
            'persen'    => 0,
        ];

        if ($selectedRombelId) {
            $selectedRombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $selectedRombelId)->first();

            $baseQuery = DB::table('peserta_didik as pd')
                ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
                ->leftJoin('presensi_harian as ph', function ($join) use ($tanggal) {
                    $join->on('pd.peserta_didik_id', '=', 'ph.peserta_didik_id')
                        ->where('ph.tanggal', '=', $tanggal);
                })
                ->leftJoin('presensi_mapel as pm', function ($join) use ($tanggal, $selectedPembelajaranId, $selectedRombelId) {
                    $join->on('pd.peserta_didik_id', '=', 'pm.peserta_didik_id')
                        ->where('pm.tanggal', '=', $tanggal);
                    if ($selectedPembelajaranId) {
                        $join->where('pm.pembelajaran_id', '=', $selectedPembelajaranId);
                    } else {
                        $join->where('pm.rombongan_belajar_id', '=', $selectedRombelId);
                    }
                })
                ->where('pd.rombongan_belajar_id', $selectedRombelId);

            if ($request->filled('q')) {
                $q = trim($request->input('q'));
                $baseQuery->where(function ($w) use ($q) {
                    $w->where('pd.nama', 'like', "%{$q}%")
                      ->orWhere('pd.nisn', 'like', "%{$q}%");
                });
            }

            $allStatuses = (clone $baseQuery)->pluck('pm.status');
            $countHadir = $allStatuses->filter(fn($s) => $s === 'H')->count();
            $countTerlambat = $allStatuses->filter(fn($s) => $s === 'T')->count();
            $totalSiswa = $allStatuses->count();
            $totalKehadiran = $countHadir + $countTerlambat;

            $rekap = [
                'total'     => $totalSiswa,
                'hadir'     => $countHadir,
                'terlambat' => $countTerlambat,
                'izin'      => $allStatuses->filter(fn($s) => $s === 'I')->count(),
                'sakit'     => $allStatuses->filter(fn($s) => $s === 'S')->count(),
                'alpha'     => $allStatuses->filter(fn($s) => $s === 'A')->count(),
                'belum'     => $allStatuses->filter(fn($s) => empty($s))->count(),
                'persen'    => $totalSiswa > 0 ? round(($totalKehadiran / $totalSiswa) * 100, 1) : 0,
            ];

            $sortCol = match ($sort) {
                'nisn' => 'pd.nisn',
                'gerbang' => 'ph.status',
                'status' => 'pm.status',
                default => 'pd.nama',
            };

            $perPageVal = ($perPage === 'all' || (int) $perPage === -1) ? 500 : (int) $perPage;
            if ($perPageVal <= 0) $perPageVal = 25;

            $siswaList = $baseQuery
                ->select(
                    'pd.peserta_didik_id',
                    'pd.nama',
                    'pd.nisn',
                    'pd.jenis_kelamin',
                    'pdm.foto_path',
                    'pdm.rfid_uid',
                    'ph.id as gerbang_presensi_id',
                    'ph.status as gerbang_status',
                    'ph.jam_masuk as gerbang_jam_masuk',
                    'ph.jam_pulang as gerbang_jam_pulang',
                    'ph.menit_terlambat as gerbang_menit_terlambat',
                    'ph.status_ketepatan_pulang as gerbang_status_pulang',
                    'ph.keterangan as gerbang_keterangan',
                    'pm.id as mapel_presensi_id',
                    'pm.status as mapel_status',
                    'pm.jam_ke as mapel_jam_ke',
                    'pm.keterangan as mapel_keterangan',
                    'pm.agenda_kelas_id'
                )
                ->orderBy($sortCol, $sortDir)
                ->paginate($perPageVal)
                ->appends($request->query());

            $siswaList->getCollection()->transform(function ($item) {
                $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
                return $item;
            });
        }

        $allRegularRombel = DB::table('rombongan_belajar')
            ->where('jenis_rombel', '1')
            ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id', 'ptk_id_str', 'jurusan_id_str')
            ->orderBy('tingkat_pendidikan_id', 'asc')
            ->orderBy('nama', 'asc')
            ->get();

        $studentCounts = DB::table('peserta_didik')
            ->select('rombongan_belajar_id', DB::raw('COUNT(*) as total_siswa'))
            ->groupBy('rombongan_belajar_id')
            ->pluck('total_siswa', 'rombongan_belajar_id');

        $harianStats = DB::table('presensi_harian')
            ->where('tanggal', $tanggal)
            ->select(
                'rombongan_belajar_id',
                DB::raw('COUNT(*) as total_record'),
                DB::raw("SUM(CASE WHEN status IN ('H', 'T') THEN 1 ELSE 0 END) as total_hadir"),
                DB::raw("SUM(CASE WHEN status = 'T' THEN 1 ELSE 0 END) as total_terlambat"),
                DB::raw("SUM(CASE WHEN status = 'I' THEN 1 ELSE 0 END) as total_izin"),
                DB::raw("SUM(CASE WHEN status = 'S' THEN 1 ELSE 0 END) as total_sakit"),
                DB::raw("SUM(CASE WHEN status = 'A' THEN 1 ELSE 0 END) as total_alpha"),
                DB::raw("SUM(CASE WHEN status = 'D' THEN 1 ELSE 0 END) as total_dispen"),
                DB::raw("SUM(CASE WHEN metode_masuk IN ('rfid', 'qr', 'kiosk', 'kamera') THEN 1 ELSE 0 END) as total_otomatis"),
                DB::raw("SUM(CASE WHEN metode_masuk = 'manual' THEN 1 ELSE 0 END) as total_manual"),
                DB::raw("MIN(jam_masuk) as scan_pertama"),
                DB::raw("MAX(jam_masuk) as scan_terakhir")
            )
            ->groupBy('rombongan_belajar_id')
            ->get()
            ->keyBy('rombongan_belajar_id');

        $mapelStats = DB::table('presensi_mapel')
            ->where('tanggal', $tanggal)
            ->select(
                'rombongan_belajar_id',
                DB::raw('COUNT(*) as total_record'),
                DB::raw('COUNT(DISTINCT pembelajaran_id) as total_mapel_tercatat'),
                DB::raw("SUM(CASE WHEN status IN ('H', 'T') THEN 1 ELSE 0 END) as total_hadir"),
                DB::raw("SUM(CASE WHEN status = 'I' THEN 1 ELSE 0 END) as total_izin"),
                DB::raw("SUM(CASE WHEN status = 'S' THEN 1 ELSE 0 END) as total_sakit"),
                DB::raw("SUM(CASE WHEN status = 'A' THEN 1 ELSE 0 END) as total_alpha"),
                DB::raw("MAX(updated_at) as mapel_terakhir")
            )
            ->groupBy('rombongan_belajar_id')
            ->get()
            ->keyBy('rombongan_belajar_id');

        $monitoringList = $allRegularRombel->map(function ($r) use ($studentCounts, $harianStats, $mapelStats) {
            $rId = $r->rombongan_belajar_id;
            $totSiswa = (int) ($studentCounts[$rId] ?? 0);
            $hs = $harianStats->get($rId);
            $ms = $mapelStats->get($rId);

            $hasHarian = $hs && $hs->total_record > 0;
            $hasMapel = $ms && $ms->total_record > 0;
            $sudahPresensi = $hasHarian || $hasMapel;

            $hasOtomatis = $hs && $hs->total_otomatis > 0;
            $hasManual = ($hs && $hs->total_manual > 0) || $hasMapel;

            if ($hasOtomatis && $hasManual) {
                $metode = 'kombinasi';
                $metodeLabel = 'Otomatis & Manual';
            } elseif ($hasOtomatis) {
                $metode = 'otomatis';
                $metodeLabel = 'Otomatis (RFID/QR)';
            } elseif ($hasManual) {
                $metode = 'manual';
                $metodeLabel = $hasHarian ? 'Manual (Wali Kelas)' : 'Manual (Guru Mapel)';
            } else {
                $metode = 'belum';
                $metodeLabel = 'Belum Ada';
            }

            if ($hasHarian) {
                $hadir = (int) $hs->total_hadir;
                $terlambat = (int) $hs->total_terlambat;
                $izin = (int) $hs->total_izin;
                $sakit = (int) $hs->total_sakit;
                $alpha = (int) $hs->total_alpha;
                $recorded = (int) $hs->total_record;
            } elseif ($hasMapel) {
                $hadir = (int) $ms->total_hadir;
                $terlambat = 0;
                $izin = (int) $ms->total_izin;
                $sakit = (int) $ms->total_sakit;
                $alpha = (int) $ms->total_alpha;
                $recorded = (int) $ms->total_record;
            } else {
                $hadir = 0;
                $terlambat = 0;
                $izin = 0;
                $sakit = 0;
                $alpha = 0;
                $recorded = 0;
            }

            $belum = max(0, $totSiswa - $recorded);
            $persen = $totSiswa > 0 ? round(($hadir / $totSiswa) * 100, 1) : 0;

            return (object) [
                'rombongan_belajar_id' => $rId,
                'nama'                 => $r->nama,
                'tingkat_pendidikan_id'=> $r->tingkat_pendidikan_id,
                'wali_kelas'           => $r->ptk_id_str ?: '-',
                'jurusan'              => $r->jurusan_id_str ?: '-',
                'total_siswa'          => $totSiswa,
                'sudah_presensi'       => $sudahPresensi,
                'metode'               => $metode,
                'metode_label'         => $metodeLabel,
                'total_otomatis'       => (int) ($hs?->total_otomatis ?? 0),
                'total_manual'         => (int) (($hs?->total_manual ?? 0) + ($ms?->total_record ?? 0)),
                'hadir'                => $hadir,
                'terlambat'            => $terlambat,
                'izin'                 => $izin,
                'sakit'                => $sakit,
                'alpha'                => $alpha,
                'belum'                => $belum,
                'persen'               => $persen,
                'waktu_pertama'        => $hs?->scan_pertama ? substr($hs->scan_pertama, 0, 5) : null,
                'waktu_terakhir'       => $hs?->scan_terakhir ? substr($hs->scan_terakhir, 0, 5) : null,
                'total_mapel_tercatat' => (int) ($ms?->total_mapel_tercatat ?? 0),
            ];
        });

        $filteredMonitoring = $monitoringList;
        if ($request->filled('monitor_tingkat')) {
            $filteredMonitoring = $filteredMonitoring->where('tingkat_pendidikan_id', $request->monitor_tingkat);
        }
        if ($request->filled('monitor_status')) {
            $mStatus = $request->monitor_status;
            if ($mStatus === 'sudah') {
                $filteredMonitoring = $filteredMonitoring->where('sudah_presensi', true);
            } elseif ($mStatus === 'belum') {
                $filteredMonitoring = $filteredMonitoring->where('sudah_presensi', false);
            }
        }
        if ($request->filled('monitor_metode')) {
            $mMetode = $request->monitor_metode;
            $filteredMonitoring = $filteredMonitoring->where('metode', $mMetode);
        }
        if ($request->filled('monitor_q')) {
            $mq = strtolower(trim($request->monitor_q));
            $filteredMonitoring = $filteredMonitoring->filter(function ($item) use ($mq) {
                return str_contains(strtolower($item->nama), $mq) || str_contains(strtolower($item->wali_kelas), $mq);
            });
        }

        $monitoringStats = [
            'total_rombel'        => $monitoringList->count(),
            'sudah_presensi'      => $monitoringList->where('sudah_presensi', true)->count(),
            'belum_presensi'      => $monitoringList->where('sudah_presensi', false)->count(),
            'total_otomatis'      => $monitoringList->whereIn('metode', ['otomatis', 'kombinasi'])->count(),
            'total_manual'        => $monitoringList->whereIn('metode', ['manual', 'kombinasi'])->count(),
            'total_siswa_sekolah' => $monitoringList->sum('total_siswa'),
            'total_hadir_sekolah' => $monitoringList->sum('hadir'),
            'total_izin_sekolah'  => $monitoringList->sum('izin'),
            'total_sakit_sekolah' => $monitoringList->sum('sakit'),
            'total_alpha_sekolah' => $monitoringList->sum('alpha'),
        ];

        $activeTab = $request->input('tab');
        if (!$activeTab) {
            $activeTab = ($isPiket || $isAdmin) ? 'monitoring' : 'detail';
        }

        return compact(
            'rombelList',
            'selectedRombelId',
            'selectedRombel',
            'pembelajaranList',
            'selectedPembelajaranId',
            'selectedPembelajaran',
            'tanggal',
            'jamKe',
            'siswaList',
            'rekap',
            'waliRombel',
            'sort',
            'sortDir',
            'perPage',
            'monitoringList',
            'filteredMonitoring',
            'monitoringStats',
            'activeTab',
            'isPiket',
            'canViewAllClasses',
            'isAdmin'
        );
    }

    /**
     * Update status presensi siswa per mapel.
     */
    public static function updateStatusKelas(Request $request): array
    {
        $pdId = $request->input('peserta_didik_id');
        $tanggal = $request->input('tanggal');
        $status = strtoupper($request->input('status'));
        $pembelajaranId = $request->input('pembelajaran_id');
        $rombelId = $request->input('rombongan_belajar_id');
        $jamKe = $request->input('jam_ke');
        $keterangan = $request->input('keterangan');

        $siswa = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
        if (!$siswa) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Peserta didik tidak ditemukan.'];
        }

        $statusKalender = KalenderPendidikan::getStatusHari($tanggal, 'pd');
        $isHariAktif = class_exists(JadwalPengaturan::class) ? JadwalPengaturan::isHariAktif(Carbon::parse($tanggal)) : true;

        if (!in_array($status, ['RESET', 'BATAL'], true) && (($statusKalender['mode'] ?? '') === 'libur' || ($statusKalender['is_libur'] ?? false) || !$isHariAktif)) {
            $alasan = $statusKalender['nama_agenda'] ?: 'Hari Libur / Non-Aktif Belajar';
            return [
                'status'  => 'error',
                'code'    => 422,
                'message' => "Presensi mapel tidak dapat dicatat karena tanggal {$tanggal} adalah {$alasan}.",
            ];
        }

        $targetRombelId = $rombelId ?: $siswa->rombongan_belajar_id;

        $pembelajaran = null;
        if ($pembelajaranId) {
            $pembelajaran = DB::table('pembelajaran')->where('pembelajaran_id', $pembelajaranId)->first();
        }

        $matchCondition = [
            'peserta_didik_id' => $pdId,
            'tanggal'          => $tanggal,
        ];
        if ($pembelajaranId) {
            $matchCondition['pembelajaran_id'] = $pembelajaranId;
        } else {
            $matchCondition['rombongan_belajar_id'] = $targetRombelId;
        }

        if (in_array($status, ['RESET', 'BATAL'], true)) {
            PresensiMapel::where($matchCondition)->delete();

            return [
                'status'  => 'success',
                'code'    => 200,
                'message' => "Presensi mapel {$siswa->nama} berhasil dibatalkan / dikosongkan.",
                'badge'   => '<span class="badge badge-outline" style="color: var(--text-muted); font-size: 0.74rem;">Belum</span>',
            ];
        }

        $user = session('user');
        $verifiedBy = is_array($user) ? ($user['nama'] ?? 'Guru Mapel') : ($user->nama ?? 'Guru Mapel');
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        $actorUserId = is_array($user) ? ($user['pengguna_id'] ?? ($user['id'] ?? null)) : ($user->pengguna_id ?? ($user->id ?? null));

        PresensiMapel::updateOrCreate(
            $matchCondition,
            [
                'nisn'                 => $siswa->nisn,
                'rombongan_belajar_id' => $targetRombelId,
                'pembelajaran_id'      => $pembelajaranId ?: null,
                'ptk_id'               => $pembelajaran?->ptk_id ?: $ptkId,
                'mata_pelajaran_id'    => $pembelajaran?->mata_pelajaran_id,
                'nama_mata_pelajaran'  => $pembelajaran?->nama_mata_pelajaran,
                'jam_ke'               => $jamKe,
                'status'               => $status,
                'keterangan'           => $keterangan,
                'created_by'           => $verifiedBy,
            ]
        );

        $mapelNama = $pembelajaran?->nama_mata_pelajaran ?: 'Mata Pelajaran';
        $statusLabel = PresensiMapel::STATUS_LABELS[$status] ?? $status;
        $notifTipe = ($status === 'H') ? 'success' : (($status === 'A') ? 'danger' : 'warning');
        $jamInfo = $jamKe ? " (Jam {$jamKe})" : '';

        NotifikasiTransaksi::kirimNotifikasiPresensi(
            $siswa->peserta_didik_id,
            $targetRombelId,
            "Presensi Mapel: {$mapelNama}",
            "Kehadiran pada {$mapelNama}{$jamInfo} dicatat {$statusLabel}.",
            "Mapel: {$siswa->nama}",
            "{$siswa->nama} dicatat {$statusLabel} pada {$mapelNama}{$jamInfo}.",
            $notifTipe,
            'fa-solid fa-chalkboard-user',
            null,
            null,
            "Presensi Mapel: {$siswa->nama}",
            "{$siswa->nama} dicatat {$statusLabel} pada mapel {$mapelNama}.",
            $actorUserId
        );

        return [
            'status'  => 'success',
            'code'    => 200,
            'message' => "Status presensi {$siswa->nama} pada mapel {$mapelNama} diubah ke {$statusLabel}",
            'badge'   => PresensiMapel::STATUS_BADGES[$status] ?? $status,
        ];
    }

    /**
     * Tandai seluruh siswa di rombel hadir pada mapel KBM.
     */
    public static function hadirSemuaRombel(Request $request): array
    {
        $rombelId = $request->input('rombongan_belajar_id');
        $tanggal = $request->input('tanggal');
        $pembelajaranId = $request->input('pembelajaran_id');
        $jamKe = $request->input('jam_ke');

        $user = session('user');
        $verifiedBy = is_array($user) ? ($user['nama'] ?? 'Guru Mapel') : ($user->nama ?? 'Guru Mapel');
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

        $pembelajaran = null;
        if ($pembelajaranId) {
            $pembelajaran = DB::table('pembelajaran')->where('pembelajaran_id', $pembelajaranId)->first();
        }

        $mapelNama = $pembelajaran?->nama_mata_pelajaran ?: 'Mata Pelajaran';
        $tglStr = Carbon::parse($tanggal)->format('d/m/Y');

        $siswaList = DB::table('peserta_didik')->where('rombongan_belajar_id', $rombelId)->get();
        $markedCount = 0;

        foreach ($siswaList as $siswa) {
            $matchCondition = [
                'peserta_didik_id' => $siswa->peserta_didik_id,
                'tanggal'          => $tanggal,
            ];
            if ($pembelajaranId) {
                $matchCondition['pembelajaran_id'] = $pembelajaranId;
            } else {
                $matchCondition['rombongan_belajar_id'] = $rombelId;
            }

            PresensiMapel::updateOrCreate(
                $matchCondition,
                [
                    'nisn'                 => $siswa->nisn,
                    'rombongan_belajar_id' => $rombelId,
                    'pembelajaran_id'      => $pembelajaranId ?: null,
                    'ptk_id'               => $pembelajaran?->ptk_id ?: $ptkId,
                    'mata_pelajaran_id'    => $pembelajaran?->mata_pelajaran_id,
                    'nama_mata_pelajaran'  => $pembelajaran?->nama_mata_pelajaran,
                    'tanggal'              => $tanggal,
                    'status'               => 'H',
                    'jam_ke'               => $jamKe,
                    'created_by'           => $verifiedBy,
                ]
            );

            NotifikasiTransaksi::kirimNotifikasiPresensi(
                $siswa->peserta_didik_id,
                $rombelId,
                "Presensi Mapel: {$mapelNama} (Hadir)",
                "Kehadiran Anda pada mata pelajaran {$mapelNama} ({$tglStr}) dicatat Hadir.",
                "Presensi Siswa: {$siswa->nama}",
                "Siswa {$siswa->nama} dicatat Hadir pada mata pelajaran {$mapelNama} ({$tglStr}).",
                'success',
                'fa-solid fa-check-double'
            );

            $markedCount++;
        }

        return [
            'status'  => 'success',
            'code'    => 200,
            'message' => "Berhasil menandai Hadir untuk seluruh peserta didik ({$markedCount} siswa) pada mapel ini.",
            'count'   => $markedCount,
        ];
    }

    /**
     * Reset / kosongkan presensi mapel KBM di kelas.
     */
    public static function resetPresensiKelas(Request $request): array
    {
        $rombelId = $request->input('rombongan_belajar_id');
        $tanggal = $request->input('tanggal');
        $pembelajaranId = $request->input('pembelajaran_id');

        $query = PresensiMapel::where('rombongan_belajar_id', $rombelId)
            ->where('tanggal', $tanggal);

        if ($pembelajaranId) {
            $query->where('pembelajaran_id', $pembelajaranId);
        }

        $deleted = $query->delete();

        return [
            'status'  => 'success',
            'code'    => 200,
            'message' => "Presensi mapel di kelas ini berhasil dikosongkan ({$deleted} catatan direset) untuk dapat diulangi.",
        ];
    }

    /**
     * Siapkan view data atau streamed CSV untuk ekspor presensi bulanan.
     */
    public static function export(Request $request)
    {
        $rombelId = $request->input('rombel_id');
        $bulan = $request->input('bulan', now()->format('Y-m'));
        $format = $request->input('format', 'pdf');

        $rombel = $rombelId ? DB::table('rombongan_belajar')->where('rombongan_belajar_id', $rombelId)->first() : null;
        $namaRombel = $rombel ? $rombel->nama : 'Semua Kelas / Seluruh Sekolah';

        $siswaList = DB::table('peserta_didik as pd')
            ->when($rombelId, fn($q) => $q->where('pd.rombongan_belajar_id', $rombelId))
            ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->select('pd.*', 'rb.nama as nama_rombel')
            ->orderBy('rb.nama', 'asc')
            ->orderBy('pd.nama', 'asc')
            ->get();

        $carbonBulan = Carbon::parse($bulan . '-01');
        $bulanLabel = $carbonBulan->translatedFormat('F Y');

        $logsBulan = PresensiHarian::where('tanggal', 'like', "{$bulan}%")
            ->when($rombelId, fn($q) => $q->where('rombongan_belajar_id', $rombelId))
            ->get()
            ->groupBy('peserta_didik_id');

        $startDate = $carbonBulan->copy()->startOfMonth()->toDateString();
        $endDate = $carbonBulan->copy()->endOfMonth()->toDateString();
        $hariEfektif = KalenderPendidikan::hitungHariEfektif($startDate, $endDate, 'pd');
        $hariEfektifBerjalan = KalenderPendidikan::hitungHariEfektifBerjalan($startDate, $endDate, now()->toDateString(), 'pd');

        $items = [];
        $sumH = $sumT = $sumI = $sumS = $sumD = $sumA = 0;

        foreach ($siswaList as $s) {
            $logs = $logsBulan->get($s->peserta_didik_id, collect());
            $h = $logs->where('status', 'H')->count();
            $t = $logs->where('status', 'T')->count();
            $i = $logs->where('status', 'I')->count();
            $sakit = $logs->where('status', 'S')->count();
            $d = $logs->where('status', 'D')->count();
            $a = $logs->where('status', 'A')->count();
            $totalHadir = $h + $t;
            $totalRecord = $logs->count();

            $deno = $hariEfektifBerjalan > 0 ? $hariEfektifBerjalan : ($totalRecord > 0 ? $totalRecord : 1);
            $persen = min(100.0, round((($totalHadir + $d) / $deno) * 100, 1));

            $sumH += $h;
            $sumT += $t;
            $sumI += $i;
            $sumS += $sakit;
            $sumD += $d;
            $sumA += $a;

            $items[] = [
                'siswa'        => $s,
                'h'            => $h,
                't'            => $t,
                'i'            => $i,
                's'            => $sakit,
                'd'            => $d,
                'a'            => $a,
                'total_hadir'  => $totalHadir,
                'total_record' => $totalRecord,
                'persen'       => $persen,
            ];
        }

        if ($format === 'csv') {
            $csvFileName = 'Rekap_Presensi_' . str_replace([' ', '/'], '_', $namaRombel) . "_{$bulan}.csv";
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$csvFileName}\"",
            ];
            $callback = function () use ($items) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($file, ['No', 'NISN', 'Nama Peserta Didik', 'Kelas', 'Hadir (H)', 'Terlambat (T)', 'Izin (I)', 'Sakit (S)', 'Dispen (D)', 'Alpha (A)', 'Total Hadir', 'Total Kehadiran (%)']);
                $no = 1;
                foreach ($items as $it) {
                    fputcsv($file, [
                        $no++,
                        $it['siswa']->nisn ?? '-',
                        $it['siswa']->nama,
                        $it['siswa']->nama_rombel ?? '-',
                        $it['h'],
                        $it['t'],
                        $it['i'],
                        $it['s'],
                        $it['d'],
                        $it['a'],
                        $it['total_hadir'],
                        $it['persen'] . '%',
                    ]);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        }

        $sekolah = PresensiPengaturan::getSekolah();
        $sekolahMeta = Schema::hasTable('sekolah_meta') ? SekolahMeta::first() : null;
        $kepsek = Schema::hasTable('gtk')
            ? DB::table('gtk')->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })->first()
            : null;

        $waliKelas = null;
        if ($rombel && !empty($rombel->ptk_id)) {
            $waliKelas = DB::table('gtk')->where('ptk_id', $rombel->ptk_id)->first();
        }

        $avgPersen = count($items) > 0 ? round(array_sum(array_column($items, 'persen')) / count($items), 1) : 0;
        $tanggalCetak = now()->translatedFormat('d F Y');

        return view('dashboard.presensi.cetak-bulanan', compact(
            'rombel',
            'namaRombel',
            'bulan',
            'bulanLabel',
            'items',
            'hariEfektif',
            'hariEfektifBerjalan',
            'sumH',
            'sumT',
            'sumI',
            'sumS',
            'sumD',
            'sumA',
            'avgPersen',
            'sekolah',
            'sekolahMeta',
            'kepsek',
            'waliKelas',
            'tanggalCetak'
        ));
    }
}
