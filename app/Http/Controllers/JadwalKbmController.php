<?php

namespace App\Http\Controllers;

use App\Models\JadwalKbm;
use App\Models\JadwalPengaturan;
use App\Models\RolePermission;
use App\Services\AutoSchedulerService;
use App\Services\RealtimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class JadwalKbmController extends Controller
{
    private const SORTABLE = ['hari', 'nama_rombel', 'nama_guru', 'nama_mata_pelajaran', 'jam_mulai', 'jam_ke_mulai'];

    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_jadwal_kbm', 'read')) {
            return redirect()->route('dashboard.' . ($role ?: 'peserta_didik'))->with('error', 'Akses ke menu Jadwal KBM dinonaktifkan.');
        }

        $canCreate = RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'create');
        $canRead   = RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'read');
        $canUpdate = RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'update');
        $canDelete = RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'delete');

        $viewMode   = $request->get('view_mode', 'grid');
        $selectedHari = $request->get('hari') ?: 'Senin';
        $tingkat    = trim($request->get('tingkat', ''));
        $hari       = trim($request->get('hari', ''));
        $rombelId   = trim($request->get('rombel_id', ''));
        $guruId     = trim($request->get('ptk_id', ''));
        $q          = trim($request->get('q', ''));
        $perPage    = (int) $request->get('perPage', 15);
        $sort       = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'hari';
        $sortDir    = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        // Auto-select rombel untuk peserta didik jika belum memilih filter
        if ($role === 'peserta_didik' && $rombelId === '') {
            $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
            if ($pdId) {
                $pdRow = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
                if ($pdRow && !empty($pdRow->rombongan_belajar_id)) {
                    $rombelId = $pdRow->rombongan_belajar_id;
                    if ($tingkat === '' && !empty($pdRow->tingkat_pendidikan_id)) {
                        $tingkat = (string) $pdRow->tingkat_pendidikan_id;
                    }
                }
            }
        }

        // Query Utama untuk Mode Tabel
        $query = DB::table('jadwal_kbm as j')
            ->leftJoin('rombongan_belajar as r', 'j.rombongan_belajar_id', '=', 'r.rombongan_belajar_id')
            ->leftJoin('gtk as g', 'j.ptk_id', '=', 'g.ptk_id')
            ->select(
                'j.*',
                'r.nama as nama_rombel',
                'g.nama as nama_guru',
                'g.nip as nip_guru'
            );

        if ($hari !== '') {
            $query->where('j.hari', $hari);
        }

        if ($rombelId !== '') {
            $query->where('j.rombongan_belajar_id', $rombelId);
        }

        if ($guruId !== '') {
            $query->where('j.ptk_id', $guruId);
        }

        // Saring mapel PKL jika role guru (presensi & KBM PKL via ePKL terpisah)
        if ($role === 'guru') {
            $query->where(function ($w) {
                $w->whereNull('j.nama_mata_pelajaran')
                  ->orWhere(function ($sub) {
                      $sub->where('j.nama_mata_pelajaran', 'NOT LIKE', 'PKL%')
                          ->where('j.nama_mata_pelajaran', 'NOT LIKE', '% PKL%')
                          ->where('j.nama_mata_pelajaran', 'NOT LIKE', '%PRAKTIK KERJA%')
                          ->where('j.nama_mata_pelajaran', 'NOT LIKE', '%PRAKTEK KERJA%');
                  });
            });
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('j.nama_mata_pelajaran', 'LIKE', "%{$q}%")
                    ->orWhere('r.nama', 'LIKE', "%{$q}%")
                    ->orWhere('g.nama', 'LIKE', "%{$q}%")
                    ->orWhere('j.ruangan', 'LIKE', "%{$q}%");
            });
        }

        // Custom Order By Hari
        if ($sort === 'hari') {
            $query->orderByRaw("FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu') {$sortDir}")
                ->orderBy('j.jam_mulai', 'asc');
        } elseif ($sort === 'nama_rombel') {
            $query->orderBy('r.nama', $sortDir)->orderBy('j.jam_mulai', 'asc');
        } elseif ($sort === 'nama_guru') {
            $query->orderBy('g.nama', $sortDir)->orderBy('j.jam_mulai', 'asc');
        } else {
            $query->orderBy("j.{$sort}", $sortDir);
        }

        $list = $query->paginate($perPage)->appends($request->query());

        // Master Filter Lists: Hanya Kelas Reguler
        $rombelQuery = DB::table('rombongan_belajar')
            ->where(function ($w) {
                $w->where('jenis_rombel', 1)
                    ->orWhere('jenis_rombel_str', 'Kelas')
                    ->orWhere('jenis_rombel_str', 'LIKE', '%reguler%');
            })
            ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id')
            ->orderBy('tingkat_pendidikan_id', 'asc')
            ->orderBy('nama', 'asc');

        $rombelList = $rombelQuery->get();

        // Rombel Khusus Tampilan Grid (Hanya Kelas Reguler, bisa difilter per tingkat)
        $gridRombelsQuery = DB::table('rombongan_belajar')
            ->where(function ($w) {
                $w->where('jenis_rombel', 1)
                    ->orWhere('jenis_rombel_str', 'Kelas')
                    ->orWhere('jenis_rombel_str', 'LIKE', '%reguler%');
            })
            ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id')
            ->orderBy('tingkat_pendidikan_id', 'asc')
            ->orderBy('nama', 'asc');
        if ($tingkat !== '') {
            $gridRombelsQuery->where('tingkat_pendidikan_id', $tingkat);
        }
        $gridRombels = $gridRombelsQuery->get();

        $guruList = DB::table('gtk')
            ->select('ptk_id', 'nama', 'nip')
            ->whereNotNull('nama')
            ->orderBy('nama', 'asc')
            ->get();

        // Data Matriks Grid untuk Hari Terpilih
        $daySchedulesQuery = DB::table('jadwal_kbm as j')
            ->leftJoin('gtk as g', 'j.ptk_id', '=', 'g.ptk_id')
            ->where('j.hari', $selectedHari)
            ->where('j.is_active', true)
            ->select(
                'j.*',
                'g.nama as nama_guru',
                'g.nip as nip_guru'
            );

        if ($role === 'guru') {
            $daySchedulesQuery->where(function ($w) {
                $w->whereNull('j.nama_mata_pelajaran')
                  ->orWhere(function ($sub) {
                      $sub->where('j.nama_mata_pelajaran', 'NOT LIKE', 'PKL%')
                          ->where('j.nama_mata_pelajaran', 'NOT LIKE', '% PKL%')
                          ->where('j.nama_mata_pelajaran', 'NOT LIKE', '%PRAKTIK KERJA%')
                          ->where('j.nama_mata_pelajaran', 'NOT LIKE', '%PRAKTEK KERJA%');
                  });
            });
        }

        $daySchedules = $daySchedulesQuery->get();

        $gridMatrix = [];
        $gridOccupied = [];
        foreach ($daySchedules as $item) {
            $rId = $item->rombongan_belajar_id;
            $startK = (int) $item->jam_ke_mulai;
            $endK = (int) $item->jam_ke_selesai;

            $gridMatrix[$rId][$startK] = $item;
            for ($k = $startK + 1; $k <= $endK; $k++) {
                $gridOccupied[$rId][$k] = $item->id;
            }
        }

        // Hitung total jadwal aktif per hari untuk badge di Tab Hari
        $countPerHari = DB::table('jadwal_kbm')
            ->where('is_active', true)
            ->select('hari', DB::raw('COUNT(*) as total'))
            ->groupBy('hari')
            ->pluck('total', 'hari')
            ->toArray();

        // Statistik Ringkasan
        $summary = [
            'total_jadwal' => DB::table('jadwal_kbm')->count(),
            'total_rombel' => DB::table('jadwal_kbm')->distinct('rombongan_belajar_id')->count('rombongan_belajar_id'),
            'total_guru'   => DB::table('jadwal_kbm')->whereNotNull('ptk_id')->distinct('ptk_id')->count('ptk_id'),
            'total_jp'     => DB::table('jadwal_kbm')->sum(DB::raw('GREATEST(1, jam_ke_selesai - jam_ke_mulai + 1)')),
        ];

        // Ambil konfigurasi slot dinamis (menyesuaikan hari yang sedang dibuka)
        $pengaturan = JadwalPengaturan::getSettings();
        $dailySlotCounts = JadwalPengaturan::getDailySlotCounts();
        $timeSlots  = JadwalPengaturan::getSlots($selectedHari);

        return view('dashboard.jadwal-kbm', compact(
            'list',
            'summary',
            'rombelList',
            'gridRombels',
            'guruList',
            'pengaturan',
            'dailySlotCounts',
            'hari',
            'selectedHari',
            'tingkat',
            'viewMode',
            'timeSlots',
            'gridMatrix',
            'gridOccupied',
            'countPerHari',
            'rombelId',
            'guruId',
            'q',
            'perPage',
            'sort',
            'sortDir',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete'
        ));
    }

    /**
     * API: Simpan Pengaturan Slot Jam KBM & Durasi per JP
     */
    public function simpanPengaturan(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'update')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin mengubah pengaturan jadwal.'], 403);
        }

        $validated = $request->validate([
            'jam_mulai_kbm'          => 'required|string',
            'durasi_per_jp'          => 'required|integer|min:20|max:90',
            'total_slot_jp'          => 'nullable|integer|min:0|max:16',
            'slot_harian'            => 'nullable|array',
            'slot_harian.*.total_jp' => 'nullable|integer|min:0|max:16',
            'jp_tingkat'             => 'nullable|array',
            'hari_aktif'             => 'nullable|array',
            'istirahat'              => 'nullable|array',
            'upacara'                => 'nullable|array',
            'pembiasaan'             => 'nullable|array',
        ]);

        $jamMulai = strlen($validated['jam_mulai_kbm']) === 5 ? "{$validated['jam_mulai_kbm']}:00" : $validated['jam_mulai_kbm'];

        $slotHarian = [];
        $maxDailySlot = 0;
        if ($request->has('slot_harian') && is_array($request->input('slot_harian'))) {
            foreach ($request->input('slot_harian') as $dh => $val) {
                $rawJp = $val['total_jp'] ?? null;
                $jpVal = ($rawJp === '' || $rawJp === null) ? 0 : max(0, min(16, (int) $rawJp));
                $slotHarian[$dh] = ['total_jp' => $jpVal];
                if ($jpVal > $maxDailySlot) {
                    $maxDailySlot = $jpVal;
                }
            }
        }

        $totalSlotJp = (int) ($validated['total_slot_jp'] ?? ($maxDailySlot > 0 ? $maxDailySlot : 10));

        // Alokasi Target JP per Tingkat SMK (X=50, XI=48, XII=46)
        $jpTingkat = [
            '10' => max(20, min(80, (int) ($request->input('jp_tingkat.10', 50)))),
            '11' => max(20, min(80, (int) ($request->input('jp_tingkat.11', 48)))),
            '12' => max(20, min(80, (int) ($request->input('jp_tingkat.12', 46)))),
        ];

        $upacaraConfig = [
            'aktif'        => !empty($request->input('upacara.aktif')),
            'hari'         => $request->input('upacara.hari', 'Senin'),
            'jam_ke'       => (int) $request->input('upacara.jam_ke', 1),
            'durasi_menit' => (int) $request->input('upacara.durasi_menit', 45),
            'nama'         => 'Upacara Bendera',
        ];

        $pembiasaanConfig = [
            'aktif'        => !empty($request->input('pembiasaan.aktif')),
            'hari'         => $request->input('pembiasaan.hari', 'Jumat'),
            'jam_ke'       => (int) $request->input('pembiasaan.jam_ke', 1),
            'durasi_menit' => (int) $request->input('pembiasaan.durasi_menit', 40),
            'nama'         => $request->input('pembiasaan.nama') ?: 'Pembiasaan',
        ];

        $istirahatConfig = [];
        if ($request->has('istirahat') && is_array($request->input('istirahat'))) {
            foreach ($request->input('istirahat') as $ist) {
                $istirahatConfig[] = [
                    'aktif'        => !empty($ist['aktif']),
                    'jam_ke'       => max(1, (int) ($ist['jam_ke'] ?? 5)),
                    'durasi_menit' => max(5, min(90, (int) ($ist['durasi_menit'] ?? 30))),
                    'nama'         => !empty($ist['nama']) ? $ist['nama'] : 'Istirahat',
                ];
            }
        }

        $pengaturan = JadwalPengaturan::getSettings();
        $pengaturan->update([
            'jam_mulai_kbm' => $jamMulai,
            'durasi_per_jp' => $validated['durasi_per_jp'],
            'total_slot_jp' => $totalSlotJp,
            'slot_harian'   => $slotHarian,
            'jp_tingkat'    => $jpTingkat,
            'hari_aktif'    => $request->input('hari_aktif', []),
            'istirahat'     => $istirahatConfig,
            'upacara'       => $upacaraConfig,
            'pembiasaan'    => $pembiasaanConfig,
        ]);

        // Sinkronkan otomatis kegiatan rutin ke jadwal seluruh rombel reguler
        JadwalPengaturan::syncRoutineActivities();

        RealtimeService::trigger('jadwal.changed', [
            'action' => 'pengaturan_updated',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan jam pelajaran & slot harian berhasil disimpan!',
            'data'    => $pengaturan,
        ]);
    }

    /**
     * Tombol Sakti: Auto-Generate Jadwal KBM untuk Seluruh Hari Tanpa Bentrok
     */
    public function autoGenerate(Request $request, AutoSchedulerService $scheduler)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'create')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin auto-generate jadwal.'], 403);
        }

        $options = [
            'clear_existing'        => $request->boolean('clear_existing', true),
            'tingkat'               => $request->input('tingkat') ?: null,
            'rombongan_belajar_ids' => $request->input('rombongan_belajar_ids', []),
            'hari_aktif'            => $request->input('hari_aktif'),
            'max_jp_per_sesi'       => (int) $request->input('max_jp_per_sesi', 3),
        ];

        $result = $scheduler->generate($options);

        if (!empty($result['success'])) {
            RealtimeService::trigger('jadwal.changed', [
                'action'          => 'auto_generated',
                'total_generated' => $result['total_generated'] ?? 0,
            ]);
        }

        return response()->json($result);
    }

    /**
     * API: Mengambil daftar pembelajaran (Mapel & Guru) untuk rombel tertentu (termasuk mapel pilihan yang terafiliasi)
     */
    public function getPembelajaranByRombel(Request $request)
    {
        $rombelId = $request->get('rombel_id');
        if (!$rombelId) {
            return response()->json(['pembelajaran' => []]);
        }

        $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $rombelId)->first();
        $targetRombelIds = [$rombelId];

        if ($rombel) {
            $cleanName = trim($rombel->nama);
            $pilihanRombelIds = DB::table('rombongan_belajar')
                ->where('jenis_rombel', '!=', 1)
                ->where(function ($q) use ($cleanName) {
                    $q->where('nama', $cleanName)
                        ->orWhere('nama', $cleanName . ' 1')
                        ->orWhere('nama', 'LIKE', $cleanName . '%');
                })
                ->pluck('rombongan_belajar_id')
                ->toArray();
            $targetRombelIds = array_unique(array_merge($targetRombelIds, $pilihanRombelIds));
        }

        $items = DB::table('pembelajaran as p')
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
                'r.jenis_rombel'
            )
            ->orderBy('r.jenis_rombel', 'asc')
            ->orderBy('p.nama_mata_pelajaran', 'asc')
            ->get()
            ->map(function ($item) use ($rombelId) {
                $isPilihan = ($item->rombongan_belajar_id !== $rombelId);
                if ($isPilihan) {
                    $item->nama_mata_pelajaran .= ' [Pilihan]';
                }
                return $item;
            });

        return response()->json(['pembelajaran' => $items]);
    }

    /**
     * API: Live Check Bentrok Jadwal
     */
    public function checkConflictApi(Request $request)
    {
        $ptkId        = $request->get('ptk_id');
        $rombelId     = $request->get('rombongan_belajar_id');
        $hari         = $request->get('hari');
        $jamMulai     = $request->get('jam_mulai');
        $jamSelesai   = $request->get('jam_selesai');
        $excludeId    = $request->get('exclude_id');
        $ruangan      = $request->get('ruangan');
        $jamKeMulai   = (int) $request->get('jam_ke_mulai', 1);
        $jamKeSelesai = (int) $request->get('jam_ke_selesai', 1);

        if (!$hari || !$jamMulai || !$jamSelesai) {
            return response()->json(['has_conflict' => false, 'message' => 'Lengkapi jam dan hari terlebih dahulu.']);
        }

        $result = JadwalKbm::checkConflict($ptkId, $rombelId, $hari, $jamMulai, $jamSelesai, $excludeId, $ruangan, $jamKeMulai, $jamKeSelesai);
        return response()->json($result);
    }

    /**
     * API: Ambil Preferensi Ketersediaan Guru (Off-Days & Jam Berhalangan)
     */
    public function getGuruPreferensi(Request $request)
    {
        $ptkId = $request->get('ptk_id');
        if (!$ptkId) {
            return response()->json(['success' => false, 'message' => 'PTK ID diperlukan.'], 422);
        }

        $guru = DB::table('gtk')->where('ptk_id', $ptkId)->first();
        if (!$guru) {
            return response()->json(['success' => false, 'message' => 'Guru tidak ditemukan.'], 404);
        }

        $preferensi = \App\Models\JadwalGuruPreferensi::where('ptk_id', $ptkId)->first();

        return response()->json([
            'success' => true,
            'guru' => [
                'ptk_id' => $guru->ptk_id,
                'nama'   => $guru->nama,
                'nip'    => $guru->nip,
            ],
            'preferensi' => $preferensi ? [
                'hari_off'        => $preferensi->hari_off ?? [],
                'jam_unavailable' => $preferensi->jam_unavailable ?? [],
                'max_jp_per_hari' => $preferensi->max_jp_per_hari,
                'keterangan'      => $preferensi->keterangan,
            ] : [
                'hari_off'        => [],
                'jam_unavailable' => [],
                'max_jp_per_hari' => null,
                'keterangan'      => '',
            ],
        ]);
    }

    /**
     * API: Simpan Preferensi Ketersediaan Guru
     */
    public function simpanGuruPreferensi(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'update')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin mengubah preferensi guru.'], 403);
        }

        $validated = $request->validate([
            'ptk_id'          => 'required|string',
            'hari_off'        => 'nullable|array',
            'jam_unavailable' => 'nullable|array',
            'max_jp_per_hari' => 'nullable|integer|min:1|max:16',
            'keterangan'      => 'nullable|string|max:255',
        ]);

        $pref = \App\Models\JadwalGuruPreferensi::updateOrCreate(
            ['ptk_id' => $validated['ptk_id']],
            [
                'hari_off'        => $validated['hari_off'] ?? [],
                'jam_unavailable' => $validated['jam_unavailable'] ?? [],
                'max_jp_per_hari' => $validated['max_jp_per_hari'] ?? null,
                'keterangan'      => $validated['keterangan'] ?? null,
            ]
        );

        RealtimeService::trigger('jadwal.changed', [
            'action' => 'preferensi_updated',
            'ptk_id' => $validated['ptk_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Preferensi ketersediaan guru berhasil disimpan!',
            'data'    => $pref,
        ]);
    }

    /**
     * Cetak Dokumen: Jadwal Induk Sekolah (Matriks Besar Seluruh Rombel)
     */
    public function cetakInduk(Request $request)
    {
        $sekolah = DB::table('sekolah')->first();
        $pengaturan = JadwalPengaturan::getSettings();
        $slots = JadwalPengaturan::getSlots();
        $hariList = $request->get('hari') ? [$request->get('hari')] : ($pengaturan->hari_aktif ?? ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']);

        $rombels = DB::table('rombongan_belajar')
            ->where(function ($w) {
                $w->where('jenis_rombel', 1)
                    ->orWhere('jenis_rombel_str', 'Kelas')
                    ->orWhere('jenis_rombel_str', 'LIKE', '%reguler%');
            })
            ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id')
            ->orderBy('tingkat_pendidikan_id', 'asc')
            ->orderBy('nama', 'asc')
            ->get();

        $allSchedules = DB::table('jadwal_kbm as j')
            ->leftJoin('gtk as g', 'j.ptk_id', '=', 'g.ptk_id')
            ->where('j.is_active', true)
            ->select('j.*', 'g.nama as nama_guru')
            ->get();

        $matrix = []; // [rombelId][hari][slot] = schedule
        $occupied = []; // [rombelId][hari][slot] = true
        foreach ($allSchedules as $s) {
            $rId = $s->rombongan_belajar_id;
            $h = $s->hari;
            $startK = (int) $s->jam_ke_mulai;
            $endK = (int) $s->jam_ke_selesai;

            $matrix[$rId][$h][$startK] = $s;
            for ($k = $startK + 1; $k <= $endK; $k++) {
                $occupied[$rId][$h][$k] = true;
            }
        }

        return view('dashboard.jadwal-kbm-cetak-induk', compact(
            'sekolah',
            'pengaturan',
            'slots',
            'hariList',
            'rombels',
            'matrix',
            'occupied'
        ));
    }

    /**
     * Cetak Dokumen: Jadwal Mengajar per Guru
     */
    public function cetakGuru(Request $request)
    {
        $sekolah = DB::table('sekolah')->first();
        $pengaturan = JadwalPengaturan::getSettings();
        $slots = JadwalPengaturan::getSlots();
        $ptkId = $request->get('ptk_id');

        $guruQuery = DB::table('gtk')
            ->whereNotNull('nama')
            ->orderBy('nama', 'asc');

        if ($ptkId) {
            $guruQuery->where('ptk_id', $ptkId);
        } else {
            $activePtkIds = DB::table('jadwal_kbm')
                ->where('is_active', true)
                ->whereNotNull('ptk_id')
                ->distinct()
                ->pluck('ptk_id');
            $guruQuery->whereIn('ptk_id', $activePtkIds);
        }

        $guruList = $guruQuery->get();

        $schedules = DB::table('jadwal_kbm as j')
            ->leftJoin('rombongan_belajar as r', 'j.rombongan_belajar_id', '=', 'r.rombongan_belajar_id')
            ->where('j.is_active', true)
            ->where(function ($w) {
                $w->whereNull('j.nama_mata_pelajaran')
                  ->orWhere(function ($sub) {
                      $sub->where('j.nama_mata_pelajaran', 'NOT LIKE', 'PKL%')
                          ->where('j.nama_mata_pelajaran', 'NOT LIKE', '% PKL%')
                          ->where('j.nama_mata_pelajaran', 'NOT LIKE', '%PRAKTIK KERJA%')
                          ->where('j.nama_mata_pelajaran', 'NOT LIKE', '%PRAKTEK KERJA%');
                  });
            })
            ->select('j.*', 'r.nama as nama_rombel')
            ->orderByRaw("FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu')")
            ->orderBy('j.jam_ke_mulai', 'asc')
            ->get()
            ->groupBy('ptk_id');

        return view('dashboard.jadwal-kbm-cetak-guru', compact(
            'sekolah',
            'pengaturan',
            'slots',
            'guruList',
            'schedules',
            'ptkId'
        ));
    }

    /**
     * Cetak Dokumen: Jadwal Pelajaran per Rombel / Kelas
     */
    public function cetakRombel(Request $request)
    {
        $sekolah = DB::table('sekolah')->first();
        $pengaturan = JadwalPengaturan::getSettings();
        $slots = JadwalPengaturan::getSlots();
        $rombelId = $request->get('rombel_id');

        $rombelQuery = DB::table('rombongan_belajar as r')
            ->leftJoin('gtk as w', 'r.ptk_id', '=', 'w.ptk_id')
            ->where(function ($w) {
                $w->where('r.jenis_rombel', 1)
                    ->orWhere('r.jenis_rombel_str', 'Kelas')
                    ->orWhere('r.jenis_rombel_str', 'LIKE', '%reguler%');
            })
            ->select('r.*', 'w.nama as nama_wali_kelas', 'w.nip as nip_wali_kelas')
            ->orderBy('r.tingkat_pendidikan_id', 'asc')
            ->orderBy('r.nama', 'asc');

        if ($rombelId) {
            $rombelQuery->where('r.rombongan_belajar_id', $rombelId);
        } else {
            $activeRombelIds = DB::table('jadwal_kbm')
                ->where('is_active', true)
                ->distinct()
                ->pluck('rombongan_belajar_id');
            $rombelQuery->whereIn('r.rombongan_belajar_id', $activeRombelIds);
        }

        $rombelList = $rombelQuery->get();
        $hariList = $pengaturan->hari_aktif ?? ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        $allSchedules = DB::table('jadwal_kbm as j')
            ->leftJoin('gtk as g', 'j.ptk_id', '=', 'g.ptk_id')
            ->where('j.is_active', true)
            ->select('j.*', 'g.nama as nama_guru')
            ->get();

        $matrix = []; // [rombelId][hari][slot] = schedule
        $occupied = [];
        foreach ($allSchedules as $s) {
            $rId = $s->rombongan_belajar_id;
            $h = $s->hari;
            $startK = (int) $s->jam_ke_mulai;
            $endK = (int) $s->jam_ke_selesai;

            $matrix[$rId][$h][$startK] = $s;
            for ($k = $startK + 1; $k <= $endK; $k++) {
                $occupied[$rId][$h][$k] = true;
            }
        }

        return view('dashboard.jadwal-kbm-cetak-rombel', compact(
            'sekolah',
            'pengaturan',
            'slots',
            'hariList',
            'rombelList',
            'matrix',
            'occupied',
            'rombelId'
        ));
    }

    /**
     * Simpan Jadwal KBM Baru
     */
    public function store(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'create')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin menambah jadwal.'], 403);
        }

        $validated = $request->validate([
            'rombongan_belajar_id' => 'required|string|max:50',
            'pembelajaran_id'      => 'required|string|max:50',
            'hari'                 => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'jam_ke_mulai'         => 'required|integer|min:1|max:20',
            'jam_ke_selesai'       => 'required|integer|min:1|max:20|gte:jam_ke_mulai',
            'jam_mulai'            => 'required|string',
            'jam_selesai'          => 'required|string',
            'ruangan'              => 'nullable|string|max:50',
            'keterangan'           => 'nullable|string|max:255',
        ]);

        // Detail Pembelajaran
        $pembelajaran = DB::table('pembelajaran')->where('pembelajaran_id', $validated['pembelajaran_id'])->first();
        if (!$pembelajaran) {
            return response()->json(['success' => false, 'message' => 'Data pembelajaran tidak valid.'], 422);
        }

        $ptkId = $pembelajaran->ptk_id;
        $mapelId = $pembelajaran->mata_pelajaran_id;
        $namaMapel = $pembelajaran->nama_mata_pelajaran ?: $pembelajaran->mata_pelajaran_id_str;

        // Validasi Anti-Bentrok Lengkap
        $conflict = JadwalKbm::checkConflict(
            $ptkId,
            $validated['rombongan_belajar_id'],
            $validated['hari'],
            $validated['jam_mulai'],
            $validated['jam_selesai'],
            null,
            $validated['ruangan'] ?? null,
            (int) $validated['jam_ke_mulai'],
            (int) $validated['jam_ke_selesai']
        );

        if ($conflict['has_conflict']) {
            return response()->json([
                'success' => false,
                'message' => $conflict['message'],
                'conflict' => $conflict,
            ], 422);
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
            'jam_mulai'            => strlen($validated['jam_mulai']) === 5 ? "{$validated['jam_mulai']}:00" : $validated['jam_mulai'],
            'jam_selesai'          => strlen($validated['jam_selesai']) === 5 ? "{$validated['jam_selesai']}:00" : $validated['jam_selesai'],
            'ruangan'              => $validated['ruangan'] ?? null,
            'semester_id'          => $rombel->semester_id ?? null,
            'is_active'            => true,
            'keterangan'           => $validated['keterangan'] ?? null,
        ]);

        RealtimeService::trigger('jadwal.changed', [
            'action' => 'created',
            'id'     => $jadwal->id,
            'rombel' => $jadwal->rombongan_belajar_id,
            'hari'   => $jadwal->hari,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal KBM berhasil ditambahkan!',
            'data'    => $jadwal,
        ]);
    }

    /**
     * Perbarui Jadwal KBM
     */
    public function update(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'update')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin mengubah jadwal.'], 403);
        }

        $jadwal = JadwalKbm::findOrFail($id);

        $validated = $request->validate([
            'rombongan_belajar_id' => 'required|string|max:50',
            'pembelajaran_id'      => 'required|string|max:50',
            'hari'                 => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'jam_ke_mulai'         => 'required|integer|min:1|max:20',
            'jam_ke_selesai'       => 'required|integer|min:1|max:20|gte:jam_ke_mulai',
            'jam_mulai'            => 'required|string',
            'jam_selesai'          => 'required|string',
            'ruangan'              => 'nullable|string|max:50',
            'keterangan'           => 'nullable|string|max:255',
        ]);

        $pembelajaran = DB::table('pembelajaran')->where('pembelajaran_id', $validated['pembelajaran_id'])->first();
        if (!$pembelajaran) {
            return response()->json(['success' => false, 'message' => 'Data pembelajaran tidak valid.'], 422);
        }

        $ptkId = $pembelajaran->ptk_id;
        $mapelId = $pembelajaran->mata_pelajaran_id;
        $namaMapel = $pembelajaran->nama_mata_pelajaran ?: $pembelajaran->mata_pelajaran_id_str;

        // Validasi Anti-Bentrok Lengkap
        $conflict = JadwalKbm::checkConflict(
            $ptkId,
            $validated['rombongan_belajar_id'],
            $validated['hari'],
            $validated['jam_mulai'],
            $validated['jam_selesai'],
            $id,
            $validated['ruangan'] ?? null,
            (int) $validated['jam_ke_mulai'],
            (int) $validated['jam_ke_selesai']
        );

        if ($conflict['has_conflict']) {
            return response()->json([
                'success' => false,
                'message' => $conflict['message'],
                'conflict' => $conflict,
            ], 422);
        }

        $jadwal->update([
            'rombongan_belajar_id' => $validated['rombongan_belajar_id'],
            'pembelajaran_id'      => $validated['pembelajaran_id'],
            'ptk_id'               => $ptkId,
            'mata_pelajaran_id'    => $mapelId,
            'nama_mata_pelajaran'  => $namaMapel,
            'hari'                 => $validated['hari'],
            'jam_ke_mulai'         => $validated['jam_ke_mulai'],
            'jam_ke_selesai'       => $validated['jam_ke_selesai'],
            'jam_mulai'            => strlen($validated['jam_mulai']) === 5 ? "{$validated['jam_mulai']}:00" : $validated['jam_mulai'],
            'jam_selesai'          => strlen($validated['jam_selesai']) === 5 ? "{$validated['jam_selesai']}:00" : $validated['jam_selesai'],
            'ruangan'              => $validated['ruangan'] ?? null,
            'keterangan'           => $validated['keterangan'] ?? null,
        ]);

        RealtimeService::trigger('jadwal.changed', [
            'action' => 'updated',
            'id'     => $jadwal->id,
            'rombel' => $jadwal->rombongan_belajar_id,
            'hari'   => $jadwal->hari,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal KBM berhasil diperbarui!',
            'data'    => $jadwal,
        ]);
    }

    /**
     * Hapus Jadwal KBM
     */
    public function destroy($id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'delete')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin menghapus jadwal.'], 403);
        }

        $jadwal = JadwalKbm::findOrFail($id);
        $jadwal->delete();

        RealtimeService::trigger('jadwal.changed', [
            'action' => 'deleted',
            'id'     => $id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal KBM berhasil dihapus.',
        ]);
    }
}
