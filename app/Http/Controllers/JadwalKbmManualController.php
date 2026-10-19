<?php

namespace App\Http\Controllers;

use App\Models\JadwalKbm;
use App\Models\JadwalPengaturan;
use App\Models\KalenderPendidikan;
use App\Models\RolePermission;
use App\Services\RealtimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class JadwalKbmManualController extends Controller
{
    /**
     * Resolusi konteks pengguna dan rombel yang berhak diakses
     */
    private function resolveUserContext(Request $request, mixed $user): array
    {
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $isAdmin = ($role === 'admin');

        // Master Rombel Reguler
        $rombelQuery = DB::table('rombongan_belajar')
            ->where(function ($w) {
                $w->where('jenis_rombel', 1)
                    ->orWhere('jenis_rombel_str', 'Kelas')
                    ->orWhere('jenis_rombel_str', 'LIKE', '%reguler%');
            })
            ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id', 'jurusan_id_str', 'ptk_id', 'ptk_id_str')
            ->orderBy('tingkat_pendidikan_id', 'asc')
            ->orderBy('nama', 'asc');

        if ($isAdmin) {
            $rombelList = $rombelQuery->get();
            $selectedId = $request->get('rombel_id') ?: ($rombelList->first()?->rombongan_belajar_id ?? null);
            $activeRombel = $rombelList->firstWhere('rombongan_belajar_id', $selectedId) ?: $rombelList->first();

            return [
                'isAdmin'        => true,
                'isLockedRombel' => false,
                'rombelList'     => $rombelList,
                'activeRombel'   => $activeRombel,
            ];
        }

        // Pengguna Wali Kelas (Guru)
        $waliRombel = RolePermission::getWaliKelasRombelInfo($user);

        // Jika Pengguna adalah Guru (Wali Kelas maupun Guru Mapel)
        if ($role === 'guru') {
            $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
            $allRombels = $rombelQuery->get();

            // Rombel di mana guru mengampu mata pelajaran (pembelajaran)
            $guruRombelIds = [];
            if ($ptkId && Schema::hasTable('pembelajaran')) {
                $guruRombelIds = DB::table('pembelajaran')
                    ->where('ptk_id', $ptkId)
                    ->whereNotNull('rombongan_belajar_id')
                    ->distinct()
                    ->pluck('rombongan_belajar_id')
                    ->toArray();
            }

            if ($waliRombel && !in_array($waliRombel->rombongan_belajar_id, $guruRombelIds, true)) {
                $guruRombelIds[] = $waliRombel->rombongan_belajar_id;
            }

            // Prioritaskan kelas ampu / binaan guru, atau seluruh kelas jika belum ada pembelajaran
            $rombelList = !empty($guruRombelIds)
                ? $allRombels->whereIn('rombongan_belajar_id', $guruRombelIds)->values()
                : $allRombels;

            $selectedId = $request->get('rombel_id')
                ?: ($waliRombel?->rombongan_belajar_id ?: ($rombelList->first()?->rombongan_belajar_id ?? null));

            $activeRombel = $rombelList->firstWhere('rombongan_belajar_id', $selectedId)
                ?: ($allRombels->firstWhere('rombongan_belajar_id', $selectedId) ?: $rombelList->first());

            return [
                'isAdmin'        => false,
                'isLockedRombel' => false,
                'rombelList'     => $rombelList,
                'activeRombel'   => $activeRombel,
            ];
        }

        // Pengguna Koordinator Kelas (Peserta Didik)
        $activeRombel = $waliRombel;
        if (!$activeRombel && $role === 'peserta_didik') {
            $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
            if ($pdId && Schema::hasTable('peserta_didik')) {
                $pd = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
                if ($pd && !empty($pd->rombongan_belajar_id)) {
                    $activeRombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $pd->rombongan_belajar_id)->first();
                }
            }
        }

        return [
            'isAdmin'        => false,
            'isLockedRombel' => true,
            'rombelList'     => $activeRombel ? collect([$activeRombel]) : collect(),
            'activeRombel'   => $activeRombel,
        ];
    }

    /**
     * Halaman Utama Input Jadwal KBM Manual
     */
    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        // Hak akses: Admin, Guru (termasuk Guru Mapel), Wali Kelas, atau Siswa Koordinator
        $isAdmin = ($role === 'admin');
        $isGuru = ($role === 'guru');
        $isKoordinator = RolePermission::isKoordinator($user);
        $canAccessJadwal = RolePermission::canAccess($user ?: $role, 'menu_jadwal_kbm', 'read');
        $canAccessWaliJadwal = RolePermission::canAccess($user ?: $role, 'menu_wali_kelas_jadwal', 'read');
        $canAccessJadwalGuru = RolePermission::canAccess($user ?: $role, 'menu_jadwal_pelajaran_guru', 'read');

        $hasAccess = $isAdmin || $isGuru || $canAccessJadwal || $canAccessWaliJadwal || $canAccessJadwalGuru || ($isKoordinator && $canAccessWaliJadwal);
        if (!$hasAccess) {
            return redirect()->route('dashboard.' . ($role ?: 'peserta_didik'))
                ->with('error', 'Anda tidak memiliki wewenang untuk mengakses modul input Jadwal KBM Manual.');
        }

        $context = $this->resolveUserContext($request, $user);
        $activeRombel = $context['activeRombel'];
        $rombelList = $context['rombelList'];
        $isLockedRombel = $context['isLockedRombel'];
        $isAdmin = $context['isAdmin'];

        // Integrasi Kalender Pendidikan & Hari Efektif Belajar
        $currentYear = (int) date('Y');
        $tahunAjaranAktif = (date('n') >= 7) ? "{$currentYear}/" . ($currentYear + 1) : ($currentYear - 1) . "/{$currentYear}";
        $semesterAktif = (date('n') >= 7) ? '1' : '2';
        $tanggalHariIni = now()->toDateString();
        $statusHariIni = KalenderPendidikan::getStatusHari($tanggalHariIni, 'all');
        $agendaHariIni = KalenderPendidikan::whereDate('tanggal_mulai', '<=', $tanggalHariIni)
            ->whereDate('tanggal_selesai', '>=', $tanggalHariIni)
            ->first();

        if (!$activeRombel) {
            return view('dashboard.jadwal-kbm-manual', [
                'activeRombel'      => null,
                'rombelList'        => collect(),
                'isLockedRombel'    => $isLockedRombel,
                'isAdmin'           => $isAdmin,
                'isDiberlakukan'    => JadwalPengaturan::isDiberlakukan('manual'),
                'statusJadwal'      => JadwalPengaturan::isDiberlakukan('manual') ? 'aktif' : 'draft',
                'isManualAktif'     => JadwalPengaturan::isDiberlakukan('manual'),
                'isOtomatisAktif'   => JadwalPengaturan::isDiberlakukan('otomatis'),
                'modePemberlakuan'  => JadwalPengaturan::getModePemberlakuan(),
                'tahunAjaranAktif'  => $tahunAjaranAktif,
                'semesterAktif'     => $semesterAktif,
                'tanggalHariIni'    => $tanggalHariIni,
                'statusHariIni'     => $statusHariIni,
                'agendaHariIni'     => $agendaHariIni,
                'errorNotice'       => 'Data rombongan belajar binaan Anda tidak ditemukan atau belum ditetapkan oleh Admin.',
            ]);
        }

        $rombelId = $activeRombel->rombongan_belajar_id;

        // 1. Ambil daftar mata pelajaran & guru pengampu (termasuk kelas pilihan yang terafiliasi)
        $targetRombelIds = [$rombelId];
        $pilihanRombelQuery = DB::table('rombongan_belajar')->where('jenis_rombel', '!=', 1);

        if (!empty($activeRombel->id_ruang)) {
            $pilihanRombelQuery->where(function ($q) use ($activeRombel) {
                $q->where('id_ruang', $activeRombel->id_ruang)
                  ->orWhere('nama', trim($activeRombel->nama))
                  ->orWhere('nama', trim($activeRombel->nama) . ' 1')
                  ->orWhere('nama', 'LIKE', trim($activeRombel->nama) . '%');
            });
        } else {
            $cleanName = trim($activeRombel->nama);
            $pilihanRombelQuery->where(function ($q) use ($cleanName) {
                $q->where('nama', $cleanName)
                  ->orWhere('nama', $cleanName . ' 1')
                  ->orWhere('nama', 'LIKE', $cleanName . '%');
            });
        }
        $pilihanRombelIds = $pilihanRombelQuery->pluck('rombongan_belajar_id')->toArray();
        $targetRombelIds = array_unique(array_merge($targetRombelIds, $pilihanRombelIds));

        // Ambil data pembelajaran
        $pembelajaranList = DB::table('pembelajaran as p')
            ->leftJoin('gtk as g', 'p.ptk_id', '=', 'g.ptk_id')
            ->leftJoin('rombongan_belajar as r', 'p.rombongan_belajar_id', '=', 'r.rombongan_belajar_id')
            ->whereIn('p.rombongan_belajar_id', $targetRombelIds)
            ->select(
                'p.pembelajaran_id',
                'p.mata_pelajaran_id',
                'p.rombongan_belajar_id',
                DB::raw('COALESCE(p.nama_mata_pelajaran, p.mata_pelajaran_id_str, "-") as nama_mata_pelajaran'),
                'p.jam_mengajar_per_minggu',
                'p.ptk_id',
                'g.nama as nama_guru',
                'g.nip as nip_guru',
                'r.jenis_rombel'
            )
            ->orderBy('r.jenis_rombel', 'asc')
            ->orderBy('p.nama_mata_pelajaran', 'asc')
            ->get();

        // 2. Ambil seluruh jadwal manual yang telah terinput untuk rombel ini
        $existingSchedules = DB::table('jadwal_kbm as j')
            ->leftJoin('gtk as g', 'j.ptk_id', '=', 'g.ptk_id')
            ->where('j.rombongan_belajar_id', $rombelId)
            ->where('j.sumber', 'manual')
            ->select(
                'j.*',
                'g.nama as nama_guru',
                'g.nip as nip_guru'
            )
            ->get();

        // Hitung total JP terjadwal per pembelajaran
        $scheduledJpByPembelajaran = [];
        $totalScheduledJp = 0;
        foreach ($existingSchedules as $s) {
            $jp = max(1, (int) $s->jam_ke_selesai - (int) $s->jam_ke_mulai + 1);
            $isRoutine = in_array($s->mata_pelajaran_id, ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'], true);

            if (!$isRoutine) {
                $totalScheduledJp += $jp;
                $key = $s->pembelajaran_id ?: ($s->ptk_id . '_' . $s->mata_pelajaran_id);
                $scheduledJpByPembelajaran[$key] = ($scheduledJpByPembelajaran[$key] ?? 0) + $jp;
            }
        }

        // Petakan status pemenuhan ke pembelajaranList
        $pembelajaranData = $pembelajaranList->map(function ($item) use ($rombelId, $scheduledJpByPembelajaran) {
            $key = $item->pembelajaran_id;
            $scheduled = $scheduledJpByPembelajaran[$key] ?? 0;
            $target = (int) ($item->jam_mengajar_per_minggu ?? 2);
            $remaining = max(0, $target - $scheduled);

            $item->is_pilihan = ($item->rombongan_belajar_id !== $rombelId);
            $item->target_jp = $target;
            $item->scheduled_jp = $scheduled;
            $item->remaining_jp = $remaining;
            $item->is_complete = ($scheduled >= $target);

            return $item;
        });

        // 3. Matriks Slot Mingguan (Hari x Jam)
        $pengaturan = JadwalPengaturan::getSettings();
        $skemaHari = $pengaturan->skema_hari ?? '5_hari';
        $baseHariList = ($skemaHari === '6_hari')
            ? ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']
            : ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        $dailySlotCounts = JadwalPengaturan::getDailySlotCounts();
        $hariAktif = array_values(array_filter($baseHariList, function ($h) use ($dailySlotCounts) {
            return ($dailySlotCounts[$h] ?? 0) > 0;
        }));
        if (empty($hariAktif)) {
            $hariAktif = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        }

        // Matriks Jadwal Rombel Ini
        $scheduleMatrix = [];
        $occupiedMatrix = [];
        foreach ($existingSchedules as $s) {
            $h = $s->hari;
            $startK = (int) $s->jam_ke_mulai;
            $endK = (int) $s->jam_ke_selesai;

            $scheduleMatrix[$h][$startK] = $s;
            for ($k = $startK + 1; $k <= $endK; $k++) {
                $occupiedMatrix[$h][$k] = $s->id;
            }
        }

        // Ambil data slot waktu per hari
        $timeSlotsByDay = [];
        foreach ($hariAktif as $h) {
            $timeSlotsByDay[$h] = JadwalPengaturan::getSlots($h);
        }

        // Ambil Kegiatan Rutin (Upacara, Pembiasaan, Istirahat)
        $routinesMap = [];
        if (!empty($pengaturan->upacara['aktif'])) {
            $upHari = $pengaturan->upacara['hari'] ?? 'Senin';
            $upJam = (int) ($pengaturan->upacara['jam_ke'] ?? 1);
            $routinesMap[$upHari][$upJam] = (object) [
                'nama' => $pengaturan->upacara['nama'] ?? 'Upacara Bendera',
                'tipe' => 'UPACARA',
                'icon' => 'fa-flag',
                'durasi' => ($pengaturan->upacara['durasi_menit'] ?? 45) . ' Menit',
            ];
        }

        if (!empty($pengaturan->pembiasaan['aktif'])) {
            $pemHari = $pengaturan->pembiasaan['hari'] ?? 'Jumat';
            $pemJam = (int) ($pengaturan->pembiasaan['jam_ke'] ?? 1);
            $routinesMap[$pemHari][$pemJam] = (object) [
                'nama' => $pengaturan->pembiasaan['nama'] ?? 'Pembiasaan',
                'tipe' => 'PEMBIASAAN',
                'icon' => 'fa-hands-praying',
                'durasi' => ($pengaturan->pembiasaan['durasi_menit'] ?? 40) . ' Menit',
            ];
        }

        if (!empty($pengaturan->istirahat) && is_array($pengaturan->istirahat)) {
            foreach ($pengaturan->istirahat as $ist) {
                if (!empty($ist['aktif']) && !empty($ist['jam_ke'])) {
                    $istJam = (int) $ist['jam_ke'];
                    $istNama = $ist['nama'] ?? 'Istirahat';
                    $durasi = ($ist['durasi_menit'] ?? 30) . ' Menit';
                    foreach ($hariAktif as $h) {
                        $routinesMap[$h][$istJam] = (object) [
                            'nama' => $istNama,
                            'tipe' => 'ISTIRAHAT',
                            'icon' => 'fa-mug-hot',
                            'durasi' => $durasi,
                        ];
                    }
                }
            }
        }

        // 4. Kalkulasi Statistik & Target JP Kelas
        $tingkatId = (string) ($activeRombel->tingkat_pendidikan_id ?? '10');
        $tingkatClean = in_array($tingkatId, ['10', '11', '12']) ? $tingkatId : '10';
        $targetJpTingkat = (int) ($pengaturan->jp_tingkat[$tingkatClean] ?? 48);
        $totalTargetJpPembelajaran = $pembelajaranData->sum('target_jp');
        $targetJpFinal = $totalTargetJpPembelajaran > 0 ? $totalTargetJpPembelajaran : $targetJpTingkat;

        $sisaJpFinal = max(0, $targetJpFinal - $totalScheduledJp);
        $persenSelesai = $targetJpFinal > 0 ? min(100, round(($totalScheduledJp / $targetJpFinal) * 100)) : 0;

        $summary = [
            'target_jp'       => $targetJpFinal,
            'total_terjadwal' => $totalScheduledJp,
            'sisa_jp'         => $sisaJpFinal,
            'persen_selesai'  => $persenSelesai,
            'total_mapel'     => $pembelajaranData->count(),
            'mapel_lengkap'   => $pembelajaranData->where('is_complete', true)->count(),
        ];

        // Status Pemberlakuan Jadwal Aktif (Eksklusif: Otomatis vs Manual)
        $isManualAktif   = JadwalPengaturan::isDiberlakukan('manual');
        $isOtomatisAktif = JadwalPengaturan::isDiberlakukan('otomatis');
        $modePemberlakuan = JadwalPengaturan::getModePemberlakuan();
        $isDiberlakukan  = $isManualAktif;
        $statusJadwal    = $isManualAktif ? 'aktif' : 'draft';

        $currentPtkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        $isGuru = ($role === 'guru');
        $isWaliKelas = RolePermission::getWaliKelasRombelInfo($user)?->rombongan_belajar_id === ($activeRombel?->rombongan_belajar_id ?? null);

        $hasOtomatisSchedule = false;
        if ($activeRombel) {
            $hasOtomatisSchedule = DB::table('jadwal_kbm')
                ->where('rombongan_belajar_id', $activeRombel->rombongan_belajar_id)
                ->where('sumber', 'otomatis')
                ->where('is_active', true)
                ->whereNotIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'])
                ->exists();
        }

        return view('dashboard.jadwal-kbm-manual', compact(
            'activeRombel',
            'rombelList',
            'isLockedRombel',
            'isAdmin',
            'isGuru',
            'isWaliKelas',
            'hasOtomatisSchedule',
            'currentPtkId',
            'role',
            'pembelajaranData',
            'summary',
            'hariAktif',
            'timeSlotsByDay',
            'scheduleMatrix',
            'occupiedMatrix',
            'routinesMap',
            'pengaturan',
            'isDiberlakukan',
            'statusJadwal',
            'isManualAktif',
            'isOtomatisAktif',
            'modePemberlakuan',
            'tahunAjaranAktif',
            'semesterAktif',
            'tanggalHariIni',
            'statusHariIni',
            'agendaHariIni'
        ));
    }

    /**
     * API: Simpan Slot Jadwal Manual Baru
     */
    public function storeSlot(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Sesi login telah berakhir.'], 401);
        }
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $validated = $request->validate([
            'rombongan_belajar_id' => 'required|string|max:50',
            'pembelajaran_id'      => 'required|string|max:50',
            'hari'                 => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'jam_ke_mulai'         => 'required|integer|min:1|max:20',
            'jam_ke_selesai'       => 'required|integer|min:1|max:20|gte:jam_ke_mulai',
            'ruangan'              => 'nullable|string|max:50',
            'keterangan'           => 'nullable|string|max:255',
        ]);

        // Ambil Data Pembelajaran
        $pembelajaran = DB::table('pembelajaran')->where('pembelajaran_id', $validated['pembelajaran_id'])->first();
        if (!$pembelajaran) {
            return response()->json(['success' => false, 'message' => 'Data pembelajaran mata pelajaran tidak ditemukan.'], 422);
        }

        $ptkId = $pembelajaran->ptk_id;
        $mapelId = $pembelajaran->mata_pelajaran_id;
        $namaMapel = $pembelajaran->nama_mata_pelajaran ?: $pembelajaran->mata_pelajaran_id_str;

        // Proteksi Otoritas: Admin, Wali Kelas, Guru Pengampu Mapel, atau Koordinator Kelas
        if ($role === 'peserta_didik') {
            $context = $this->resolveUserContext($request, $user);
            $userRombelId = $context['activeRombel']?->rombongan_belajar_id;

            if (!$userRombelId || $userRombelId !== $validated['rombongan_belajar_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Koordinator kelas hanya memiliki wewenang mengelola jadwal untuk kelas sendiri.',
                ], 403);
            }
        } elseif ($role === 'guru') {
            $sessionPtkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
            $waliRombel = RolePermission::getWaliKelasRombelInfo($user);
            $isWali = $waliRombel && ($waliRombel->rombongan_belajar_id === $validated['rombongan_belajar_id']);
            $canManageAll = RolePermission::canAccess($user, 'menu_jadwal_kbm', 'create') || RolePermission::canAccess($user, 'menu_jadwal_kbm', 'update');
            $isOwnMapel = $sessionPtkId && ($pembelajaran->ptk_id === $sessionPtkId);

            if (!$isWali && !$canManageAll && !$isOwnMapel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda hanya berhak menginput slot untuk mata pelajaran yang Anda ampu atau kelas binaan Anda.',
                ], 403);
            }
        }

        $setting = JadwalPengaturan::getSettings();

        // 1. Validasi Hari Aktif Belajar (Sesuai Skema Hari Sekolah: 5 Hari vs 6 Hari)
        $hariAktif = JadwalPengaturan::getHariAktifList();
        if (!in_array($validated['hari'], $hariAktif, true)) {
            $skema = ($setting->skema_hari === '6_hari') ? '6 Hari Sekolah (Senin - Sabtu)' : '5 Hari Sekolah (Senin - Jumat)';
            return response()->json([
                'success' => false,
                'message' => "Hari {$validated['hari']} bukan hari operasional KBM sesuai Skema Jadwal Induk ({$skema}).",
            ], 422);
        }

        // 2. Validasi Batas Kuota JP Harian dari Jadwal Pengaturan
        $dailyCounts = JadwalPengaturan::getDailySlotCounts();
        $maxJpHari = $dailyCounts[$validated['hari']] ?? (int) $setting->total_slot_jp;
        if ((int) $validated['jam_ke_selesai'] > $maxJpHari) {
            return response()->json([
                'success' => false,
                'message' => "Jam ke-{$validated['jam_ke_selesai']} melebihi batas kuota JP hari {$validated['hari']} ({$maxJpHari} JP) pada Pengaturan Jadwal Induk.",
            ], 422);
        }

        // 3. Validasi Batas Maksimal JP per Sesi Mengajar
        $durasiSesi = (int) $validated['jam_ke_selesai'] - (int) $validated['jam_ke_mulai'] + 1;
        $maxJpPerSesi = max(2, (int) ($setting->max_jp_per_sesi ?? 3));
        if ($durasiSesi > $maxJpPerSesi) {
            return response()->json([
                'success' => false,
                'message' => "Durasi sesi mengajar ({$durasiSesi} JP) melebihi batas maksimal ({$maxJpPerSesi} JP per sesi) pada Pengaturan Jadwal Induk.",
            ], 422);
        }

        // 4. Proteksi Kegiatan Rutin Sekolah (Upacara, Istirahat, Pembiasaan)
        $routineConflict = JadwalKbm::where('rombongan_belajar_id', $validated['rombongan_belajar_id'])
            ->where('hari', $validated['hari'])
            ->whereIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'])
            ->where('jam_ke_mulai', '<=', (int) $validated['jam_ke_selesai'])
            ->where('jam_ke_selesai', '>=', (int) $validated['jam_ke_mulai'])
            ->first();

        if ($routineConflict) {
            return response()->json([
                'success' => false,
                'message' => "Slot waktu tersebut bertepatan dengan kegiatan rutin sekolah '{$routineConflict->nama_mata_pelajaran}' (Jam ke-{$routineConflict->jam_ke_mulai}) pada Pengaturan Jadwal Induk.",
            ], 422);
        }

        // Hitung jam mulai dan selesai berdasarkan pengaturan slot
        $slots = JadwalPengaturan::getSlots($validated['hari']);
        $startSlot = $slots[(int) $validated['jam_ke_mulai']] ?? null;
        $endSlot = $slots[(int) $validated['jam_ke_selesai']] ?? null;

        $jamMulai = !empty($startSlot['jam_mulai']) ? $startSlot['jam_mulai'] : (!empty($startSlot['mulai']) ? (strlen($startSlot['mulai']) === 5 ? $startSlot['mulai'] . ':00' : $startSlot['mulai']) : '06:30:00');
        $jamSelesai = !empty($endSlot['jam_selesai']) ? $endSlot['jam_selesai'] : (!empty($endSlot['selesai']) ? (strlen($endSlot['selesai']) === 5 ? $endSlot['selesai'] . ':00' : $endSlot['selesai']) : '07:15:00');

        $overwrite = $request->boolean('overwrite', false);

        // Validasi Anti-Bentrok Komprehensif (Khusus Sumber Manual)
        $conflict = JadwalKbm::checkConflict(
            $ptkId,
            $validated['rombongan_belajar_id'],
            $validated['hari'],
            $jamMulai,
            $jamSelesai,
            null,
            $validated['ruangan'] ?? null,
            (int) $validated['jam_ke_mulai'],
            (int) $validated['jam_ke_selesai'],
            'manual'
        );

        if ($conflict['has_conflict']) {
            if ($conflict['type'] === 'rombel') {
                if ($overwrite) {
                    // Timpa / Hapus jadwal manual lama di kelas ini yang bertabrakan pada jam tersebut (kecualikan kegiatan rutin)
                    JadwalKbm::where('rombongan_belajar_id', $validated['rombongan_belajar_id'])
                        ->where('hari', $validated['hari'])
                        ->where('sumber', 'manual')
                        ->where('is_active', true)
                        ->whereNotIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'])
                        ->where(function ($q) use ($jamMulai, $jamSelesai) {
                            $q->where('jam_mulai', '<', $jamSelesai)
                              ->where('jam_selesai', '>', $jamMulai);
                        })
                        ->delete();
                } else {
                    return response()->json([
                        'success'           => false,
                        'can_overwrite'     => true,
                        'conflict_type'     => 'rombel',
                        'conflicting_mapel' => $conflict['conflict_with']->nama_mata_pelajaran ?? 'Pelajaran Lain',
                        'message'           => $conflict['message'],
                        'conflict'          => $conflict,
                    ], 422);
                }
            } else {
                return response()->json([
                    'success'       => false,
                    'can_overwrite' => false,
                    'conflict_type' => $conflict['type'] ?? 'unknown',
                    'message'       => $conflict['message'],
                    'conflict'      => $conflict,
                ], 422);
            }
        }

        $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $validated['rombongan_belajar_id'])->first();

        $jadwal = JadwalKbm::create([
            'rombongan_belajar_id' => $validated['rombongan_belajar_id'],
            'pembelajaran_id'      => $validated['pembelajaran_id'],
            'ptk_id'               => $ptkId,
            'mata_pelajaran_id'    => $mapelId,
            'nama_mata_pelajaran'  => $namaMapel,
            'hari'                 => $validated['hari'],
            'jam_ke_mulai'         => $validated['jam_ke_mulai'],
            'jam_ke_selesai'       => $validated['jam_ke_selesai'],
            'jam_mulai'            => $jamMulai,
            'jam_selesai'          => $jamSelesai,
            'ruangan'              => !empty($validated['ruangan']) ? $validated['ruangan'] : ($rombel->nama ?? null),
            'semester_id'          => $rombel->semester_id ?? null,
            'sumber'               => 'manual',
            'is_active'            => true,
            'keterangan'           => $validated['keterangan'] ?? null,
        ]);

        RealtimeService::trigger('jadwal.changed', [
            'action' => 'created_manual',
            'id'     => $jadwal->id,
            'rombel' => $jadwal->rombongan_belajar_id,
            'hari'   => $jadwal->hari,
        ]);

        $overwriteText = $overwrite ? ' (mengganti jadwal sebelumnya di jam tersebut)' : '';

        return response()->json([
            'success' => true,
            'message' => "Mata pelajaran '{$namaMapel}' berhasil dijadwalkan pada hari {$jadwal->hari} (Jam ke-{$jadwal->jam_ke_mulai} s/d {$jadwal->jam_ke_selesai}){$overwriteText}!",
            'data'    => $jadwal,
        ]);
    }

    /**
     * API: Perbarui Slot Jadwal
     */
    public function updateSlot(Request $request, $id)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Sesi login telah berakhir.'], 401);
        }
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $jadwal = JadwalKbm::findOrFail($id);

        // Proteksi Kegiatan Rutin (Upacara, Istirahat, Pembiasaan)
        $isRoutine = in_array($jadwal->mata_pelajaran_id, ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'], true)
            || str_starts_with($jadwal->pembelajaran_id ?? '', 'ROUTINE_')
            || in_array(strtolower(trim($jadwal->nama_mata_pelajaran)), ['upacara', 'upacara bendera', 'istirahat', 'pembiasaan'], true);

        if ($isRoutine) {
            return response()->json([
                'success' => false,
                'message' => "Kegiatan rutin '{$jadwal->nama_mata_pelajaran}' tidak dapat diubah secara manual karena ditentukan dari Pengaturan Jam & Bel KBM.",
            ], 422);
        }

        $validated = $request->validate([
            'pembelajaran_id' => 'required|string|max:50',
            'hari'            => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'jam_ke_mulai'    => 'required|integer|min:1|max:20',
            'jam_ke_selesai'  => 'required|integer|min:1|max:20|gte:jam_ke_mulai',
            'ruangan'         => 'nullable|string|max:50',
            'keterangan'      => 'nullable|string|max:255',
        ]);

        $pembelajaran = DB::table('pembelajaran')->where('pembelajaran_id', $validated['pembelajaran_id'])->first();
        if (!$pembelajaran) {
            return response()->json(['success' => false, 'message' => 'Data pembelajaran tidak ditemukan.'], 422);
        }

        $ptkId = $pembelajaran->ptk_id;
        $mapelId = $pembelajaran->mata_pelajaran_id;
        $namaMapel = $pembelajaran->nama_mata_pelajaran ?: $pembelajaran->mata_pelajaran_id_str;

        // Proteksi Otoritas: Admin, Wali Kelas, Guru Pengampu Mapel, atau Koordinator Kelas
        if ($role === 'peserta_didik') {
            $context = $this->resolveUserContext($request, $user);
            $userRombelId = $context['activeRombel']?->rombongan_belajar_id;

            if (!$userRombelId || $userRombelId !== $jadwal->rombongan_belajar_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki wewenang mengubah jadwal kelas lain.',
                ], 403);
            }
        } elseif ($role === 'guru') {
            $sessionPtkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
            $waliRombel = RolePermission::getWaliKelasRombelInfo($user);
            $isWali = $waliRombel && ($waliRombel->rombongan_belajar_id === $jadwal->rombongan_belajar_id);
            $canManageAll = RolePermission::canAccess($user, 'menu_jadwal_kbm', 'update');
            $isOwnMapel = $sessionPtkId && ($jadwal->ptk_id === $sessionPtkId || $pembelajaran->ptk_id === $sessionPtkId);

            if (!$isWali && !$canManageAll && !$isOwnMapel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda hanya berhak mengubah slot untuk mata pelajaran yang Anda ampu atau kelas binaan Anda.',
                ], 403);
            }
        }

        $setting = JadwalPengaturan::getSettings();

        // 1. Validasi Hari Aktif Belajar (Sesuai Skema Hari Sekolah: 5 Hari vs 6 Hari)
        $hariAktif = JadwalPengaturan::getHariAktifList();
        if (!in_array($validated['hari'], $hariAktif, true)) {
            $skema = ($setting->skema_hari === '6_hari') ? '6 Hari Sekolah (Senin - Sabtu)' : '5 Hari Sekolah (Senin - Jumat)';
            return response()->json([
                'success' => false,
                'message' => "Hari {$validated['hari']} bukan hari operasional KBM sesuai Skema Jadwal Induk ({$skema}).",
            ], 422);
        }

        // 2. Validasi Batas Kuota JP Harian dari Jadwal Pengaturan
        $dailyCounts = JadwalPengaturan::getDailySlotCounts();
        $maxJpHari = $dailyCounts[$validated['hari']] ?? (int) $setting->total_slot_jp;
        if ((int) $validated['jam_ke_selesai'] > $maxJpHari) {
            return response()->json([
                'success' => false,
                'message' => "Jam ke-{$validated['jam_ke_selesai']} melebihi batas kuota JP hari {$validated['hari']} ({$maxJpHari} JP) pada Pengaturan Jadwal Induk.",
            ], 422);
        }

        // 3. Validasi Batas Maksimal JP per Sesi Mengajar
        $durasiSesi = (int) $validated['jam_ke_selesai'] - (int) $validated['jam_ke_mulai'] + 1;
        $maxJpPerSesi = max(2, (int) ($setting->max_jp_per_sesi ?? 3));
        if ($durasiSesi > $maxJpPerSesi) {
            return response()->json([
                'success' => false,
                'message' => "Durasi sesi mengajar ({$durasiSesi} JP) melebihi batas maksimal ({$maxJpPerSesi} JP per sesi) pada Pengaturan Jadwal Induk.",
            ], 422);
        }

        // 4. Proteksi Kegiatan Rutin Sekolah (Upacara, Istirahat, Pembiasaan)
        $routineConflict = JadwalKbm::where('rombongan_belajar_id', $jadwal->rombongan_belajar_id)
            ->where('id', '!=', $id)
            ->where('hari', $validated['hari'])
            ->whereIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'])
            ->where('jam_ke_mulai', '<=', (int) $validated['jam_ke_selesai'])
            ->where('jam_ke_selesai', '>=', (int) $validated['jam_ke_mulai'])
            ->first();

        if ($routineConflict) {
            return response()->json([
                'success' => false,
                'message' => "Slot waktu tersebut bertepatan dengan kegiatan rutin sekolah '{$routineConflict->nama_mata_pelajaran}' (Jam ke-{$routineConflict->jam_ke_mulai}) pada Pengaturan Jadwal Induk.",
            ], 422);
        }

        $slots = JadwalPengaturan::getSlots($validated['hari']);
        $startSlot = $slots[(int) $validated['jam_ke_mulai']] ?? null;
        $endSlot = $slots[(int) $validated['jam_ke_selesai']] ?? null;

        $jamMulai = !empty($startSlot['jam_mulai']) ? $startSlot['jam_mulai'] : (!empty($startSlot['mulai']) ? (strlen($startSlot['mulai']) === 5 ? $startSlot['mulai'] . ':00' : $startSlot['mulai']) : '06:30:00');
        $jamSelesai = !empty($endSlot['jam_selesai']) ? $endSlot['jam_selesai'] : (!empty($endSlot['selesai']) ? (strlen($endSlot['selesai']) === 5 ? $endSlot['selesai'] . ':00' : $endSlot['selesai']) : '07:15:00');

        $overwrite = $request->boolean('overwrite', false);

        $conflict = JadwalKbm::checkConflict(
            $ptkId,
            $jadwal->rombongan_belajar_id,
            $validated['hari'],
            $jamMulai,
            $jamSelesai,
            $id,
            $validated['ruangan'] ?? null,
            (int) $validated['jam_ke_mulai'],
            (int) $validated['jam_ke_selesai'],
            'manual'
        );

        if ($conflict['has_conflict']) {
            if ($conflict['type'] === 'rombel') {
                if ($overwrite) {
                    // Hapus slot kelas lain (selain $id) yang tertimpa khusus manual (jangan hapus kegiatan rutin)
                    JadwalKbm::where('rombongan_belajar_id', $jadwal->rombongan_belajar_id)
                        ->where('id', '!=', $id)
                        ->where('hari', $validated['hari'])
                        ->where('sumber', 'manual')
                        ->where('is_active', true)
                        ->whereNotIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'])
                        ->where(function ($q) use ($jamMulai, $jamSelesai) {
                            $q->where('jam_mulai', '<', $jamSelesai)
                              ->where('jam_selesai', '>', $jamMulai);
                        })
                        ->delete();
                } else {
                    return response()->json([
                        'success'           => false,
                        'can_overwrite'     => true,
                        'conflict_type'     => 'rombel',
                        'conflicting_mapel' => $conflict['conflict_with']->nama_mata_pelajaran ?? 'Pelajaran Lain',
                        'message'           => $conflict['message'],
                        'conflict'          => $conflict,
                    ], 422);
                }
            } else {
                return response()->json([
                    'success'       => false,
                    'can_overwrite' => false,
                    'conflict_type' => $conflict['type'] ?? 'unknown',
                    'message'       => $conflict['message'],
                    'conflict'      => $conflict,
                ], 422);
            }
        }

        $jadwal->update([
            'pembelajaran_id'     => $validated['pembelajaran_id'],
            'ptk_id'              => $ptkId,
            'mata_pelajaran_id'   => $mapelId,
            'nama_mata_pelajaran' => $namaMapel,
            'hari'                => $validated['hari'],
            'jam_ke_mulai'        => $validated['jam_ke_mulai'],
            'jam_ke_selesai'      => $validated['jam_ke_selesai'],
            'jam_mulai'           => $jamMulai,
            'jam_selesai'         => $jamSelesai,
            'ruangan'             => $validated['ruangan'] ?? null,
            'keterangan'          => $validated['keterangan'] ?? null,
        ]);

        RealtimeService::trigger('jadwal.changed', [
            'action' => 'updated_manual',
            'id'     => $jadwal->id,
            'rombel' => $jadwal->rombongan_belajar_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Slot jadwal berhasil diperbarui!",
            'data'    => $jadwal,
        ]);
    }

    /**
     * API: Hapus Slot Jadwal
     */
    public function deleteSlot(Request $request, $id)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Sesi login telah berakhir.'], 401);
        }
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $jadwal = JadwalKbm::findOrFail($id);

        // Proteksi Kegiatan Rutin (Upacara, Istirahat, Pembiasaan)
        $isRoutine = in_array($jadwal->mata_pelajaran_id, ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'], true)
            || str_starts_with($jadwal->pembelajaran_id ?? '', 'ROUTINE_')
            || in_array(strtolower(trim($jadwal->nama_mata_pelajaran)), ['upacara', 'upacara bendera', 'istirahat', 'pembiasaan'], true);

        if ($isRoutine) {
            return response()->json([
                'success' => false,
                'message' => "Kegiatan rutin '{$jadwal->nama_mata_pelajaran}' tidak dapat dihapus karena ditentukan dari Pengaturan Jam & Bel KBM.",
            ], 422);
        }

        if ($role === 'peserta_didik') {
            $context = $this->resolveUserContext($request, $user);
            $userRombelId = $context['activeRombel']?->rombongan_belajar_id;

            if (!$userRombelId || $userRombelId !== $jadwal->rombongan_belajar_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki wewenang menghapus jadwal kelas lain.',
                ], 403);
            }
        } elseif ($role === 'guru') {
            $sessionPtkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
            $waliRombel = RolePermission::getWaliKelasRombelInfo($user);
            $isWali = $waliRombel && ($waliRombel->rombongan_belajar_id === $jadwal->rombongan_belajar_id);
            $canManageAll = RolePermission::canAccess($user, 'menu_jadwal_kbm', 'delete');
            $isOwnMapel = $sessionPtkId && ($jadwal->ptk_id === $sessionPtkId);

            if (!$isWali && !$canManageAll && !$isOwnMapel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda hanya berhak menghapus slot untuk mata pelajaran yang Anda ampu atau kelas binaan Anda.',
                ], 403);
            }
        }

        $namaMapel = $jadwal->nama_mata_pelajaran;
        $hari = $jadwal->hari;
        $jamKe = $jadwal->jam_ke_mulai;

        $jadwal->delete();

        RealtimeService::trigger('jadwal.changed', [
            'action' => 'deleted_manual',
            'id'     => $id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Jadwal '{$namaMapel}' pada {$hari} (Jam ke-{$jamKe}) berhasil dihapus.",
        ]);
    }

    /**
     * API: Reset / Kosongkan Seluruh Jadwal Kelas Binaan
     */
    public function clearRombel(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Sesi login telah berakhir.'], 401);
        }
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $rombelId = $request->input('rombongan_belajar_id');
        if (!$rombelId) {
            return response()->json(['success' => false, 'message' => 'ID Rombongan belajar diperlukan.'], 422);
        }

        if ($role !== 'admin') {
            $waliRombel = RolePermission::getWaliKelasRombelInfo($user);
            $isWali = $waliRombel && ($waliRombel->rombongan_belajar_id === $rombelId);
            $canManageAll = RolePermission::canAccess($user, 'menu_jadwal_kbm', 'delete');

            if (!$isWali && !$canManageAll) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya Administrator atau Wali Kelas yang dapat mengosongkan seluruh jadwal kelas ini.',
                ], 403);
            }
        }

        // Hapus hanya jadwal mapel manual rombel ini (jangan hapus jadwal otomatis atau kegiatan rutin)
        $deleted = DB::table('jadwal_kbm')
            ->where('rombongan_belajar_id', $rombelId)
            ->where('sumber', 'manual')
            ->whereNotIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'])
            ->delete();

        RealtimeService::trigger('jadwal.changed', [
            'action' => 'cleared_manual',
            'rombel' => $rombelId,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Berhasil mereset {$deleted} jadwal pelajaran pada kelas ini.",
        ]);
    }

    /**
     * API: Salin dan penuhi seluruh baris JP kelas dari Jadwal Induk (Otomatis)
     */
    public function copyFromOtomatis(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Sesi login telah berakhir.'], 401);
        }
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $rombelId = $request->input('rombongan_belajar_id');
        if (!$rombelId) {
            return response()->json(['success' => false, 'message' => 'ID Rombongan belajar diperlukan.'], 422);
        }

        if ($role === 'peserta_didik') {
            return response()->json(['success' => false, 'message' => 'Hanya Guru atau Administrator yang dapat menyalin jadwal induk.'], 403);
        }

        if ($role === 'guru') {
            $waliRombel = RolePermission::getWaliKelasRombelInfo($user);
            $isWali = $waliRombel && ($waliRombel->rombongan_belajar_id === $rombelId);
            $canManageAll = RolePermission::canAccess($user, 'menu_jadwal_kbm', 'create') || RolePermission::canAccess($user, 'menu_jadwal_kbm', 'update');
            $sessionPtkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
            $isGuruInRombel = $sessionPtkId && DB::table('pembelajaran')->where('ptk_id', $sessionPtkId)->where('rombongan_belajar_id', $rombelId)->exists();

            if (!$isWali && !$canManageAll && !$isGuruInRombel) {
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki wewenang mengelola jadwal kelas ini.'], 403);
            }
        }

        $otomatisList = DB::table('jadwal_kbm')
            ->where('rombongan_belajar_id', $rombelId)
            ->where('sumber', 'otomatis')
            ->where('is_active', true)
            ->get();

        if ($otomatisList->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Belum ada jadwal induk (otomatis) untuk kelas ini yang dapat disalin.'], 422);
        }

        // Hapus jadwal manual lama (kecualikan upacara/pembiasaan/istirahat agar tetap tersinkron)
        DB::table('jadwal_kbm')
            ->where('rombongan_belajar_id', $rombelId)
            ->where('sumber', 'manual')
            ->whereNotIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'])
            ->delete();

        $inserted = 0;
        foreach ($otomatisList as $item) {
            $isRoutine = in_array($item->mata_pelajaran_id, ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'], true)
                || str_starts_with($item->pembelajaran_id ?? '', 'ROUTINE_');
            if ($isRoutine) {
                continue;
            }

            DB::table('jadwal_kbm')->insert([
                'rombongan_belajar_id' => $item->rombongan_belajar_id,
                'pembelajaran_id'      => $item->pembelajaran_id,
                'ptk_id'               => $item->ptk_id,
                'mata_pelajaran_id'    => $item->mata_pelajaran_id,
                'nama_mata_pelajaran'  => $item->nama_mata_pelajaran,
                'hari'                 => $item->hari,
                'jam_ke_mulai'         => $item->jam_ke_mulai,
                'jam_ke_selesai'       => $item->jam_ke_selesai,
                'jam_mulai'            => $item->jam_mulai,
                'jam_selesai'          => $item->jam_selesai,
                'ruangan'              => $item->ruangan,
                'semester_id'          => $item->semester_id,
                'sumber'               => 'manual',
                'is_active'            => true,
                'keterangan'           => $item->keterangan,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
            $inserted++;
        }

        RealtimeService::trigger('jadwal.changed', [
            'action' => 'copied_from_otomatis',
            'rombel' => $rombelId,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Sebanyak {$inserted} slot pelajaran dari Jadwal Induk berhasil disalin ke Jadwal Manual kelas ini sehingga baris JP terisi padat!",
        ]);
    }
}
