<?php

namespace App\Services;

use App\Models\AgendaKbm;
use App\Models\JadwalKbm;
use App\Models\JadwalPengaturan;
use App\Models\KalenderPendidikan;
use App\Models\PresensiMengajar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\Table;
/**
 * Service khusus pengelolaan agenda KBM kelas binaan (pencatatan jurnal, pertemuan, cetak, verifikasi).
 * ponytail: static-orchestrator; upgrade ke asynchronous job jika cetak rekapitulasi semester massal > 50 rombel.
 */
class WaliKelasAgendaService
{
    /**
     * Dapatkan nama hari bahasa Indonesia dari tanggal Y-m-d.
     */
    public static function getIndoDayName(?string $date = null): string
    {
        $d = $date ? \Carbon\Carbon::parse($date) : \Carbon\Carbon::now();
        return match ($d->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            default => 'Minggu',
        };
    }

    /**
     * Kumpulkan seluruh dataset untuk tampilan halaman Agenda Kelas.
     */
    public static function getAgendaData(array $ctx, Request $request, array $permissions): array
    {
        $activeRombel = $ctx['activeRombel'];

        $tab            = $request->get('tab', 'jadwal');
        $tanggal        = $request->get('tanggal', date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            $tanggal = date('Y-m-d');
        }
        $hari           = self::getIndoDayName($tanggal);

        $q              = trim($request->get('q', ''));
        $filterPtk      = trim($request->get('ptk_id', ''));
        $filterMapel    = trim($request->get('nama_mata_pelajaran', ''));
        $filterStatus   = trim($request->get('status_kbm', ''));
        $tanggalMulai   = $request->get('tanggal_mulai', '');
        $tanggalSelesai = $request->get('tanggal_selesai', '');
        $perPage        = (int) $request->get('perPage', 15);
        $perPage        = in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 15;
        $sort           = $request->get('sort', 'tanggal');
        $sortDir        = strtolower($request->get('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (!$activeRombel) {
            return [
                'hasRombel'        => false,
                'isAdmin'          => $ctx['isAdmin'],
                'isPiket'          => $ctx['isPiket'],
                'isKoordinator'    => $ctx['isKoordinator'],
                'isGuru'           => $ctx['isGuru'],
                'canSwitchRombel'  => $ctx['canSwitchRombel'],
                'rombelList'       => $ctx['rombelList'],
                'activeRombel'     => null,
                'waliNama'         => $ctx['waliNama'],
                'waliNip'          => $ctx['waliNip'],
                'tab'              => $tab,
                'tanggal'          => $tanggal,
                'hari'             => $hari,
                'dailySlots'       => collect(),
                'dailySummary'     => ['total_slots' => 0, 'filled_slots' => 0, 'empty_slots' => 0, 'pct' => 0],
                'statusKalender'   => KalenderPendidikan::getStatusHari($tanggal, 'pd'),
                'agendaKalender'   => null,
                'list'             => new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage),
                'total'            => 0,
                'summary'          => ['total' => 0, 'terlaksana' => 0, 'sebagian' => 0, 'tertunda' => 0, 'digantikan' => 0],
                'jadwalList'       => collect(),
                'pembelajaranList' => collect(),
                'guruMapelList'    => collect(),
                'mapelList'        => collect(),
                'q'                => $q,
                'filterPtk'        => $filterPtk,
                'filterMapel'      => $filterMapel,
                'filterStatus'     => $filterStatus,
                'tanggalMulai'     => $tanggalMulai,
                'tanggalSelesai'   => $tanggalSelesai,
                'perPage'          => $perPage,
                'sort'             => $sort,
                'sortDir'          => $sortDir,
                'canCreate'        => $permissions['canCreate'],
                'canUpdate'        => $permissions['canUpdate'],
                'canDelete'        => $permissions['canDelete'],
            ];
        }

        $statusKalender = KalenderPendidikan::getStatusHari($tanggal, 'pd');
        $agendaKalender = KalenderPendidikan::whereDate('tanggal_mulai', '<=', $tanggal)
            ->whereDate('tanggal_selesai', '>=', $tanggal)
            ->first();

        $targetRombelId = $activeRombel->rombongan_belajar_id;

        // 1. DATA JADWAL KBM HARIAN UNTUK TANGGAL TERPILIH
        $modeAktif = class_exists(JadwalPengaturan::class) ? JadwalPengaturan::getModeAktif() : null;
        $dailyJadwalQuery = JadwalKbm::where('rombongan_belajar_id', $targetRombelId)
            ->where('is_active', true)
            ->where('hari', $hari);

        if ($modeAktif) {
            $dailyJadwalQuery->where('sumber', $modeAktif);
        }

        $dailyJadwalQuery->whereNotIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT', 'LITERASI'])
            ->whereNotNull('ptk_id')
            ->where('ptk_id', '!=', '');

        $excludedKeywords = ['istirahat', 'upacara', 'pembiasaan', 'literasi'];
        $dailyJadwalQuery->where(function ($w) use ($excludedKeywords) {
            foreach ($excludedKeywords as $kw) {
                $w->whereRaw('LOWER(TRIM(nama_mata_pelajaran)) NOT LIKE ?', ["%{$kw}%"]);
            }
        });

        $rawDailySlots = $dailyJadwalQuery->orderBy('jam_ke_mulai', 'asc')->get();

        $dailyPtkIds = $rawDailySlots->pluck('ptk_id')->filter()->unique()->all();
        $gtkMapDaily = !empty($dailyPtkIds)
            ? DB::table('gtk')->whereIn('ptk_id', $dailyPtkIds)->pluck('nama', 'ptk_id')->all()
            : [];

        $dailySlots = $rawDailySlots->filter(function ($s) use ($gtkMapDaily) {
            $nama = $gtkMapDaily[$s->ptk_id] ?? ($s->nama_ptk ?? null);
            return !empty($s->ptk_id) && !empty($nama) && $nama !== '-';
        })->values();

        $agendaHariIni = AgendaKbm::where('rombongan_belajar_id', $targetRombelId)
            ->whereDate('tanggal', $tanggal)
            ->leftJoin('gtk as g', 'agenda_kbm.ptk_id', '=', 'g.ptk_id')
            ->select(
                'agenda_kbm.*',
                'g.nama as nama_guru',
                'g.nip as nip_guru',
                'g.nuptk as nuptk_guru'
            )
            ->get();

        foreach ($dailySlots as $s) {
            $s->nama_guru = $gtkMapDaily[$s->ptk_id] ?? ($s->nama_ptk ?? '-');

            $matched = $agendaHariIni->first(function ($a) use ($s) {
                if (!empty($a->jadwal_kbm_id) && (int) $a->jadwal_kbm_id === (int) $s->id) {
                    return true;
                }
                if ($a->ptk_id === $s->ptk_id && (int) $a->jam_ke_mulai === (int) $s->jam_ke_mulai) {
                    return true;
                }
                if (strcasecmp(trim($a->nama_mata_pelajaran), trim($s->nama_mata_pelajaran)) === 0 && (int) $a->jam_ke_mulai === (int) $s->jam_ke_mulai) {
                    return true;
                }
                return false;
            });

            $s->agenda = $matched;
            $s->is_terisi = !empty($matched);

            $jamMulaiWaktu = !empty($s->jam_mulai) ? substr($s->jam_mulai, 0, 5) : '';
            $jamSelesaiWaktu = !empty($s->jam_selesai) ? substr($s->jam_selesai, 0, 5) : '';
            $s->jam_waktu_range = ($jamMulaiWaktu && $jamSelesaiWaktu) ? "{$jamMulaiWaktu} - {$jamSelesaiWaktu}" : '';
            $s->durasi_jp = max(1, ((int) ($s->jam_ke_selesai ?? 1)) - ((int) ($s->jam_ke_mulai ?? 1)) + 1);

            $s->next_pertemuan = AgendaKbm::getNextPertemuanKe($targetRombelId, $s->pembelajaran_id);
        }

        $totalDailySlots = $dailySlots->count();
        $totalDailyTerisi = $dailySlots->where('is_terisi', true)->count();
        $totalDailyBelum = max(0, $totalDailySlots - $totalDailyTerisi);
        $persenDaily = $totalDailySlots > 0 ? round(($totalDailyTerisi / $totalDailySlots) * 100) : 0;

        $dailySummary = [
            'total_slots' => $totalDailySlots,
            'terisi'      => $totalDailyTerisi,
            'belum'       => $totalDailyBelum,
            'persen'      => $persenDaily,
        ];

        // Riwayat
        $query = AgendaKbm::where('agenda_kbm.rombongan_belajar_id', $activeRombel->rombongan_belajar_id)
            ->leftJoin('gtk as g', 'agenda_kbm.ptk_id', '=', 'g.ptk_id')
            ->select(
                'agenda_kbm.*',
                'g.nama as nama_guru',
                'g.nip as nip_guru'
            );

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('agenda_kbm.nama_mata_pelajaran', 'like', "%{$q}%")
                  ->orWhere('agenda_kbm.materi_pokok', 'like', "%{$q}%")
                  ->orWhere('agenda_kbm.uraian_kegiatan', 'like', "%{$q}%")
                  ->orWhere('g.nama', 'like', "%{$q}%");
            });
        }

        if ($filterPtk !== '') {
            $query->where('agenda_kbm.ptk_id', $filterPtk);
        }
        if ($filterMapel !== '') {
            $query->where('agenda_kbm.nama_mata_pelajaran', $filterMapel);
        }
        if ($filterStatus !== '') {
            $query->where('agenda_kbm.status_kbm', $filterStatus);
        }
        if ($tanggalMulai !== '') {
            $query->whereDate('agenda_kbm.tanggal', '>=', $tanggalMulai);
        }
        if ($tanggalSelesai !== '') {
            $query->whereDate('agenda_kbm.tanggal', '<=', $tanggalSelesai);
        }

        $summaryQuery = AgendaKbm::where('agenda_kbm.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
        $summary = [
            'total'      => (clone $summaryQuery)->count(),
            'terlaksana' => (clone $summaryQuery)->where('status_kbm', 'Terlaksana')->count(),
            'sebagian'   => (clone $summaryQuery)->where('status_kbm', 'Sebagian')->count(),
            'tertunda'   => (clone $summaryQuery)->where('status_kbm', 'Tertunda')->count(),
            'digantikan' => (clone $summaryQuery)->where('status_kbm', 'Digantikan')->count(),
        ];

        $sortCol = match ($sort) {
            'mapel'     => 'agenda_kbm.nama_mata_pelajaran',
            'guru'      => 'g.nama',
            'pertemuan' => 'agenda_kbm.pertemuan_ke',
            'jam'       => 'agenda_kbm.jam_ke_mulai',
            default     => 'agenda_kbm.tanggal',
        };

        $list = $query->orderBy($sortCol, $sortDir)
            ->orderBy('agenda_kbm.jam_ke_mulai', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        $jadwalModalQuery = JadwalKbm::where('rombongan_belajar_id', $targetRombelId)
            ->where('is_active', true)
            ->whereNotIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT', 'LITERASI'])
            ->whereNotNull('ptk_id')
            ->where('ptk_id', '!=', '');

        if ($modeAktif) {
            $jadwalModalQuery->where('sumber', $modeAktif);
        }

        $jadwalModalQuery->where(function ($w) use ($excludedKeywords) {
            foreach ($excludedKeywords as $kw) {
                $w->whereRaw('LOWER(TRIM(nama_mata_pelajaran)) NOT LIKE ?', ["%{$kw}%"]);
            }
        });

        $jadwalRaw = $jadwalModalQuery->orderBy('hari')->orderBy('jam_ke_mulai')->get();

        $allPtkIds = $jadwalRaw->pluck('ptk_id')->filter()->unique()->all();
        $gtkMap = !empty($allPtkIds)
            ? DB::table('gtk')->whereIn('ptk_id', $allPtkIds)->pluck('nama', 'ptk_id')->all()
            : [];

        $jadwalList = $jadwalRaw->filter(function ($j) use ($gtkMap) {
            $nama = $gtkMap[$j->ptk_id] ?? ($j->nama_ptk ?? null);
            return !empty($j->ptk_id) && !empty($nama) && $nama !== '-';
        })->map(function ($j) use ($gtkMap) {
            $jamMulaiWaktu = !empty($j->jam_mulai) ? substr($j->jam_mulai, 0, 5) : '';
            $jamSelesaiWaktu = !empty($j->jam_selesai) ? substr($j->jam_selesai, 0, 5) : '';
            $jamWaktuRange = ($jamMulaiWaktu && $jamSelesaiWaktu) ? "{$jamMulaiWaktu} - {$jamSelesaiWaktu}" : '';
            $durasiJp = max(1, ((int) ($j->jam_ke_selesai ?? 1)) - ((int) ($j->jam_ke_mulai ?? 1)) + 1);

            return [
                'id'                   => $j->id,
                'hari'                 => $j->hari,
                'jam_ke_mulai'         => (int) $j->jam_ke_mulai,
                'jam_ke_selesai'       => (int) $j->jam_ke_selesai,
                'jam_mulai'            => $jamMulaiWaktu,
                'jam_selesai'          => $jamSelesaiWaktu,
                'jam_waktu_range'      => $jamWaktuRange,
                'durasi_jp'            => $durasiJp,
                'nama_mata_pelajaran'  => $j->nama_mata_pelajaran,
                'pembelajaran_id'      => $j->pembelajaran_id,
                'mata_pelajaran_id'    => $j->mata_pelajaran_id,
                'ptk_id'               => $j->ptk_id,
                'nama_guru'            => $gtkMap[$j->ptk_id] ?? ($j->nama_ptk ?? '-'),
            ];
        })->values();

        $pembelajaranList = collect();
        if (Table::exists('pembelajaran')) {
            $pembelajaranList = DB::table('pembelajaran as p')
                ->leftJoin('gtk as g', 'p.ptk_id', '=', 'g.ptk_id')
                ->where('p.rombongan_belajar_id', $activeRombel->rombongan_belajar_id)
                ->whereNotNull('p.ptk_id')
                ->where('p.ptk_id', '!=', '')
                ->whereNotNull('g.nama')
                ->where('g.nama', '!=', '-')
                ->select(
                    'p.pembelajaran_id',
                    'p.mata_pelajaran_id',
                    'p.nama_mata_pelajaran',
                    'p.ptk_id',
                    'g.nama as nama_guru'
                )
                ->orderBy('p.nama_mata_pelajaran', 'asc')
                ->get();
        }

        $guruMapelList = $pembelajaranList->unique('ptk_id')->values();
        if ($guruMapelList->isEmpty()) {
            $guruMapelList = $jadwalList->unique('ptk_id')->map(fn($j) => (object)[
                'ptk_id' => $j['ptk_id'],
                'nama_guru' => $j['nama_guru'],
            ])->values();
        }

        $mapelList = $pembelajaranList->pluck('nama_mata_pelajaran')->filter()->unique()->values();
        if ($mapelList->isEmpty()) {
            $mapelList = $jadwalList->pluck('nama_mata_pelajaran')->filter()->unique()->values();
        }

        return [
            'hasRombel'        => true,
            'isAdmin'          => $ctx['isAdmin'],
            'isPiket'          => $ctx['isPiket'],
            'isKoordinator'    => $ctx['isKoordinator'],
            'isGuru'           => $ctx['isGuru'],
            'canSwitchRombel'  => $ctx['canSwitchRombel'],
            'rombelList'       => $ctx['rombelList'],
            'activeRombel'     => $activeRombel,
            'waliNama'         => $ctx['waliNama'],
            'waliNip'          => $ctx['waliNip'],
            'tab'              => $tab,
            'tanggal'          => $tanggal,
            'hari'             => $hari,
            'dailySlots'       => $dailySlots,
            'dailySummary'     => $dailySummary,
            'statusKalender'   => $statusKalender,
            'agendaKalender'   => $agendaKalender,
            'list'             => $list,
            'total'            => $list->total(),
            'summary'          => $summary,
            'jadwalList'       => $jadwalList,
            'pembelajaranList' => $pembelajaranList,
            'guruMapelList'    => $guruMapelList,
            'mapelList'        => $mapelList,
            'q'                => $q,
            'filterPtk'        => $filterPtk,
            'filterMapel'      => $filterMapel,
            'filterStatus'     => $filterStatus,
            'tanggalMulai'     => $tanggalMulai,
            'tanggalSelesai'   => $tanggalSelesai,
            'perPage'          => $perPage,
            'sort'             => $sort,
            'sortDir'          => $sortDir,
            'canCreate'        => $permissions['canCreate'],
            'canUpdate'        => $permissions['canUpdate'],
            'canDelete'        => $permissions['canDelete'],
        ];
    }

    /**
     * Simpan data Agenda Kelas Binaan Baru.
     */
    public static function store(array $ctx, array $validated, mixed $user): array
    {
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $creatorName = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Pengguna')) : ($user->nama ?? ($user->name ?? 'Pengguna'));
        $userPtkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        $activeRombel = $ctx['activeRombel'];

        $hari = !empty($validated['hari']) ? $validated['hari'] : self::getIndoDayName($validated['tanggal']);

        if ($userPtkId && $userPtkId === $validated['ptk_id']) {
            $createdBy = 'Guru Mapel: ' . $creatorName;
        } elseif ($ctx['isKoordinator']) {
            $createdBy = 'Koordinator Kelas: ' . $creatorName;
        } elseif ($ctx['isPiket']) {
            $prefixPiket = ($role === 'guru') ? 'Guru Piket' : 'Petugas Piket KBM';
            $createdBy = "{$prefixPiket}: {$creatorName}";
        } elseif ($role === 'guru' && $ctx['isWaliKelas']) {
            $createdBy = 'Wali Kelas: ' . $creatorName;
        } elseif ($ctx['isAdmin']) {
            $createdBy = 'Admin: ' . $creatorName;
        } else {
            $createdBy = ucfirst($role) . ': ' . $creatorName;
        }

        $presensiId = PresensiMengajar::where('ptk_id', $validated['ptk_id'])
            ->where('rombongan_belajar_id', $activeRombel->rombongan_belajar_id)
            ->where('tanggal', $validated['tanggal'])
            ->value('id');

        $agenda = AgendaKbm::create([
            'presensi_mengajar_id' => $presensiId,
            'jadwal_kbm_id'        => $validated['jadwal_kbm_id'] ?? null,
            'ptk_id'               => $validated['ptk_id'],
            'rombongan_belajar_id' => $activeRombel->rombongan_belajar_id,
            'pembelajaran_id'      => $validated['pembelajaran_id'] ?? null,
            'mata_pelajaran_id'    => $validated['mata_pelajaran_id'] ?? null,
            'nama_mata_pelajaran'  => $validated['nama_mata_pelajaran'],
            'tanggal'              => $validated['tanggal'],
            'hari'                 => $hari,
            'jam_ke_mulai'         => $validated['jam_ke_mulai'],
            'jam_ke_selesai'       => $validated['jam_ke_selesai'],
            'pertemuan_ke'         => $validated['pertemuan_ke'],
            'materi_pokok'         => $validated['materi_pokok'],
            'uraian_kegiatan'      => $validated['uraian_kegiatan'],
            'penugasan'            => $validated['penugasan'] ?? null,
            'status_kbm'           => $validated['status_kbm'],
            'hambatan_catatan'     => $validated['hambatan_catatan'] ?? null,
            'created_by'           => $createdBy,
        ]);

        return [
            'status'  => 'success',
            'message' => 'Agenda kelas berhasil dicatat dan disinkronkan dengan jurnal guru mapel.',
            'data'    => $agenda,
        ];
    }

    /**
     * Detail Agenda Kelas (Read).
     */
    public static function show(string $id): ?array
    {
        $agenda = AgendaKbm::leftJoin('gtk as g', 'agenda_kbm.ptk_id', '=', 'g.ptk_id')
            ->leftJoin('rombongan_belajar as rb', 'agenda_kbm.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('agenda_kbm.id', $id)
            ->select(
                'agenda_kbm.*',
                'g.nama as nama_guru',
                'g.nip as nip_guru',
                'rb.nama as nama_rombel'
            )
            ->first();

        if (!$agenda) {
            return null;
        }

        return [
            'id'                   => $agenda->id,
            'jadwal_kbm_id'        => $agenda->jadwal_kbm_id,
            'ptk_id'               => $agenda->ptk_id,
            'nama_guru'            => $agenda->nama_guru ?: '-',
            'nip_guru'             => $agenda->nip_guru ?: '-',
            'rombongan_belajar_id' => $agenda->rombongan_belajar_id,
            'nama_rombel'          => $agenda->nama_rombel ?: '-',
            'pembelajaran_id'      => $agenda->pembelajaran_id,
            'mata_pelajaran_id'    => $agenda->mata_pelajaran_id,
            'nama_mata_pelajaran'  => $agenda->nama_mata_pelajaran,
            'tanggal'              => $agenda->tanggal ? $agenda->tanggal->format('Y-m-d') : null,
            'tanggal_formatted'    => $agenda->tanggal ? $agenda->tanggal->translatedFormat('d F Y') : '-',
            'hari'                 => $agenda->hari,
            'jam_ke_mulai'         => (int) $agenda->jam_ke_mulai,
            'jam_ke_selesai'       => (int) $agenda->jam_ke_selesai,
            'pertemuan_ke'         => (int) $agenda->pertemuan_ke,
            'materi_pokok'         => $agenda->materi_pokok,
            'uraian_kegiatan'      => $agenda->uraian_kegiatan,
            'penugasan'            => $agenda->penugasan,
            'status_kbm'           => $agenda->status_kbm,
            'status_badge'         => $agenda->status_badge,
            'hambatan_catatan'     => $agenda->hambatan_catatan,
            'created_by'           => $agenda->created_by ?: 'Guru Pengampu',
        ];
    }

    /**
     * Update data Agenda Kelas Binaan.
     */
    public static function update(string $id, array $validated): AgendaKbm
    {
        $agenda = AgendaKbm::findOrFail($id);
        $hari = !empty($validated['hari']) ? $validated['hari'] : self::getIndoDayName($validated['tanggal']);

        $agenda->update([
            'jadwal_kbm_id'        => $validated['jadwal_kbm_id'] ?? $agenda->jadwal_kbm_id,
            'ptk_id'               => $validated['ptk_id'],
            'nama_mata_pelajaran'  => $validated['nama_mata_pelajaran'],
            'pembelajaran_id'      => $validated['pembelajaran_id'] ?? $agenda->pembelajaran_id,
            'mata_pelajaran_id'    => $validated['mata_pelajaran_id'] ?? $agenda->mata_pelajaran_id,
            'tanggal'              => $validated['tanggal'],
            'hari'                 => $hari,
            'jam_ke_mulai'         => $validated['jam_ke_mulai'],
            'jam_ke_selesai'       => $validated['jam_ke_selesai'],
            'pertemuan_ke'         => $validated['pertemuan_ke'],
            'materi_pokok'         => $validated['materi_pokok'],
            'uraian_kegiatan'      => $validated['uraian_kegiatan'],
            'penugasan'            => $validated['penugasan'] ?? null,
            'status_kbm'           => $validated['status_kbm'],
            'hambatan_catatan'     => $validated['hambatan_catatan'] ?? null,
        ]);

        return $agenda;
    }

    /**
     * Hitung nomor pertemuan berikutnya untuk mapel rombel aktif.
     */
    public static function getNextPertemuan(string $rombelId, ?string $pembelajaranId, ?string $namaMapel): int
    {
        $query = AgendaKbm::where('rombongan_belajar_id', $rombelId);
        if ($pembelajaranId) {
            $query->where('pembelajaran_id', $pembelajaranId);
        } elseif ($namaMapel) {
            $query->where('nama_mata_pelajaran', $namaMapel);
        }
        $max = $query->max('pertemuan_ke');
        return ($max ? (int) $max : 0) + 1;
    }

    /**
     * Ambil data untuk cetak agenda kelas binaan.
     */
    public static function getCetakData(array $ctx, Request $request): array
    {
        $activeRombel = $ctx['activeRombel'];

        $query = AgendaKbm::where('agenda_kbm.rombongan_belajar_id', $activeRombel->rombongan_belajar_id)
            ->leftJoin('gtk as g', 'agenda_kbm.ptk_id', '=', 'g.ptk_id')
            ->select(
                'agenda_kbm.*',
                'g.nama as nama_guru',
                'g.nip as nip_guru'
            );

        if ($request->filled('ptk_id')) {
            $query->where('agenda_kbm.ptk_id', $request->ptk_id);
        }
        if ($request->filled('nama_mata_pelajaran')) {
            $query->where('agenda_kbm.nama_mata_pelajaran', $request->nama_mata_pelajaran);
        }
        if ($request->filled('status_kbm')) {
            $query->where('agenda_kbm.status_kbm', $request->status_kbm);
        }
        if ($request->filled('tanggal')) {
            $query->whereDate('agenda_kbm.tanggal', $request->tanggal);
        }
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('agenda_kbm.tanggal', '>=', $request->tanggal_mulai);
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('agenda_kbm.tanggal', '<=', $request->tanggal_selesai);
        }

        $items = $query->orderBy('agenda_kbm.tanggal', 'asc')
            ->orderBy('agenda_kbm.jam_ke_mulai', 'asc')
            ->get();

        $sekolah = DB::table('sekolah')->first();
        $kepsek = DB::table('gtk')
            ->where(function ($w) {
                $w->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        return [
            'items'        => $items,
            'activeRombel' => $activeRombel,
            'waliNama'     => $ctx['waliNama'],
            'waliNip'      => $ctx['waliNip'],
            'sekolah'      => $sekolah,
            'kepsek'       => $kepsek,
        ];
    }
}
