<?php

namespace App\Http\Controllers;

use App\Models\KalenderPendidikan;
use App\Models\PesertaDidikMeta;
use App\Models\PresensiHarian;
use App\Models\PresensiIzin;
use App\Models\PresensiPengaturan;
use App\Models\RolePermission;
use App\Services\QrCodeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PresensiController extends Controller
{
    /**
     * Dashboard Utama Manajemen Presensi (Admin, Tendik, Guru)
     */
    public function index(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? 'guru') : ($user->role ?? 'guru');

        $tanggal = $request->input('tanggal', now()->toDateString());
        $pengaturan = PresensiPengaturan::getPengaturan();

        // Cek status hari libur dari Kalender Pendidikan
        $isLiburKalender = KalenderPendidikan::isLibur($tanggal, 'pd');
        $agendaHariIni = null;
        if ($isLiburKalender) {
            $agendaHariIni = KalenderPendidikan::where('tanggal_mulai', '<=', $tanggal)
                ->where('tanggal_selesai', '>=', $tanggal)
                ->where('libur_pd', true)
                ->first();
        }

        // Statistik Presensi Hari Ini / Tanggal Terpilih
        $totalSiswa = DB::table('peserta_didik')->count();
        $countHadir = PresensiHarian::where('tanggal', $tanggal)->where('status', 'H')->count();
        $countTerlambat = PresensiHarian::where('tanggal', $tanggal)->where('status', 'T')->count();
        $countIzin = PresensiHarian::where('tanggal', $tanggal)->where('status', 'I')->count();
        $countSakit = PresensiHarian::where('tanggal', $tanggal)->where('status', 'S')->count();
        $countDispen = PresensiHarian::where('tanggal', $tanggal)->where('status', 'D')->count();
        $countAlpha = PresensiHarian::where('tanggal', $tanggal)->where('status', 'A')->count();

        $totalTercatat = $countHadir + $countTerlambat + $countIzin + $countSakit + $countDispen + $countAlpha;
        $countBelumAbsen = max(0, $totalSiswa - $totalTercatat);

        $persenHadir = $totalSiswa > 0 ? round((($countHadir + $countTerlambat) / $totalSiswa) * 100, 1) : 0;

        // Daftar Rombel Aktif untuk Filter
        $rombelList = DB::table('rombongan_belajar')
            ->where('jenis_rombel', '1') // reguler
            ->orderBy('tingkat_pendidikan_id', 'asc')
            ->orderBy('nama', 'asc')
            ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id')
            ->get();

        // Query Log Presensi dengan Filter
        $rombelFilter = $request->input('rombel_id');
        $statusFilter = $request->input('status');
        $search = trim($request->input('search', ''));

        $logsQuery = DB::table('presensi_harian as ph')
            ->join('peserta_didik as pd', 'ph.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'ph.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->where('ph.tanggal', $tanggal)
            ->select(
                'ph.*',
                'pd.nama as nama_siswa',
                'pd.nisn as nisn_siswa',
                'pd.jenis_kelamin',
                'rb.nama as nama_rombel',
                'pdm.foto_path'
            );

        if (!empty($rombelFilter)) {
            $logsQuery->where('ph.rombongan_belajar_id', $rombelFilter);
        }

        if (!empty($statusFilter)) {
            $logsQuery->where('ph.status', $statusFilter);
        }

        if (!empty($search)) {
            $logsQuery->where(function ($q) use ($search) {
                $q->where('pd.nama', 'like', "%{$search}%")
                    ->orWhere('pd.nisn', 'like', "%{$search}%");
            });
        }

        $perPageLog = (int) $request->input('perPageLog', $request->input('perPage', 15));
        if (!in_array($perPageLog, [10, 15, 25, 50, 100])) {
            $perPageLog = 15;
        }

        $logs = $logsQuery->orderBy('ph.jam_masuk', 'desc')->paginate($perPageLog)->withQueryString();

        // Format foto URL untuk siswa di logs
        $logs->getCollection()->transform(function ($item) {
            $item->foto_profil_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
            $item->foto_masuk_url = !empty($item->foto_masuk) ? asset('storage/' . ltrim($item->foto_masuk, '/')) : null;
            $item->foto_pulang_url = !empty($item->foto_pulang) ? asset('storage/' . ltrim($item->foto_pulang, '/')) : null;
            return $item;
        });

        // Daftar Siswa untuk Manajemen Kartu RFID
        $rfidSearch = trim($request->input('rfid_search', ''));
        $rfidFilter = $request->input('rfid_status', '');

        $siswaRfidQuery = DB::table('peserta_didik as pd')
            ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->select(
                'pd.peserta_didik_id',
                'pd.nama',
                'pd.nisn',
                'pd.nipd',
                'rb.nama as nama_rombel',
                'pdm.rfid_uid',
                'pdm.rfid_registered_at',
                'pdm.foto_path'
            );

        if (!empty($rfidSearch)) {
            $siswaRfidQuery->where(function ($q) use ($rfidSearch) {
                $q->where('pd.nama', 'like', "%{$rfidSearch}%")
                    ->orWhere('pd.nisn', 'like', "%{$rfidSearch}%")
                    ->orWhere('pdm.rfid_uid', 'like', "%{$rfidSearch}%");
            });
        }

        if ($rfidFilter === 'terdaftar') {
            $siswaRfidQuery->whereNotNull('pdm.rfid_uid')->where('pdm.rfid_uid', '!=', '');
        } elseif ($rfidFilter === 'belum') {
            $siswaRfidQuery->where(function ($q) {
                $q->whereNull('pdm.rfid_uid')->orWhere('pdm.rfid_uid', '');
            });
        }

        $activeTab = $request->input('tab', 'log');
        if ($request->filled('rfid_search') || $request->filled('rfid_status') || $request->has('rfid_page')) {
            $activeTab = 'rfid';
        }

        $perPageRfid = (int) $request->input('perPageRfid', $request->input('perPage', 15));
        if (!in_array($perPageRfid, [10, 15, 25, 50, 100])) {
            $perPageRfid = 15;
        }

        $siswaRfidList = $siswaRfidQuery->orderBy('pd.nama', 'asc')
            ->paginate($perPageRfid, ['*'], 'rfid_page')
            ->appends(['tab' => 'rfid'])
            ->withQueryString();

        // Pengajuan Izin Menunggu Verifikasi
        $izinPending = PresensiIzin::where('status', 'menunggu')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard.presensi.index', compact(
            'pengaturan',
            'tanggal',
            'activeTab',
            'isLiburKalender',
            'agendaHariIni',
            'totalSiswa',
            'countHadir',
            'countTerlambat',
            'countIzin',
            'countSakit',
            'countDispen',
            'countAlpha',
            'countBelumAbsen',
            'persenHadir',
            'rombelList',
            'logs',
            'siswaRfidList',
            'izinPending',
            'perPageLog',
            'perPageRfid'
        ));
    }

    /**
     * Layar Penuh Kiosk Terminal Pemindai Presensi (Scanner Station)
     */
    public function scanKiosk(Request $request)
    {
        $pengaturan = PresensiPengaturan::getPengaturan();
        $today = now()->toDateString();
        $isLibur = KalenderPendidikan::isLibur($today, 'pd');

        $agendaLibur = null;
        if ($isLibur) {
            $agendaLibur = KalenderPendidikan::where('tanggal_mulai', '<=', $today)
                ->where('tanggal_selesai', '>=', $today)
                ->where('libur_pd', true)
                ->first();
        }

        // Recent Scans (5 scan terakhir)
        $recentScans = DB::table('presensi_harian as ph')
            ->join('peserta_didik as pd', 'ph.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'ph.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->where('ph.tanggal', $today)
            ->select(
                'ph.id',
                'ph.status',
                'ph.jam_masuk',
                'ph.jam_pulang',
                'ph.menit_terlambat',
                'ph.foto_masuk',
                'ph.foto_pulang',
                'pd.nama',
                'pd.nisn',
                'rb.nama as rombel',
                'pdm.foto_path'
            )
            ->orderBy('ph.updated_at', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($s) {
                $s->foto_url = !empty($s->foto_path) ? asset('storage/' . ltrim($s->foto_path, '/')) : null;
                $s->snapshot_url = !empty($s->foto_masuk) ? asset('storage/' . ltrim($s->foto_masuk, '/')) : null;
                return $s;
            });

        return view('dashboard.presensi.scan', compact('pengaturan', 'isLibur', 'agendaLibur', 'recentScans'));
    }

    /**
     * API Endpoint: Proses Pemindaian Tap RFID / QR Code / NISN
     */
    public function processScan(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string|max:255',
            'mode' => 'nullable|string|in:auto,masuk,pulang',
            'snapshot' => 'nullable|string', // Base64 data URI snapshot webcam
        ]);

        $rawIdentifier = trim($request->input('identifier'));
        $mode = $request->input('mode', 'auto');
        $snapshotBase64 = $request->input('snapshot');

        $now = Carbon::now();
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        $pengaturan = PresensiPengaturan::getPengaturan();

        // 1. Cek Kalender Pendidikan (Hari Libur)
        if (KalenderPendidikan::isLibur($today, 'pd')) {
            $agenda = KalenderPendidikan::where('tanggal_mulai', '<=', $today)
                ->where('tanggal_selesai', '>=', $today)
                ->where('libur_pd', true)
                ->first();

            return response()->json([
                'status' => 'warning',
                'title' => 'Hari Libur Akademik',
                'message' => 'Hari ini adalah hari libur sekolah: ' . ($agenda ? $agenda->nama_kegiatan : 'Libur Kalender Pendidikan') . '. Presensi dinonaktifkan.',
                'speech_text' => 'Hari ini adalah hari libur sekolah. Presensi tidak aktif.',
            ], 422);
        }

        // 2. Cek apakah hari ini termasuk hari aktif belajar
        if (!$pengaturan->isHariAktif($now)) {
            return response()->json([
                'status' => 'warning',
                'title' => 'Bukan Hari Belajar',
                'message' => 'Hari ini bukan merupakan hari aktif belajar di jadwal sekolah.',
                'speech_text' => 'Hari ini bukan jadwal hari aktif sekolah.',
            ], 422);
        }

        // 3. Resolusi Siswa dari Identifier (RFID UID, NISN, QR Payload)
        $cleanId = $rawIdentifier;

        // Parse jika QR berupa URL verifikasi kartu /v/{nisn}
        if (preg_match('/\/v\/([0-9a-zA-Z]+)/', $rawIdentifier, $match)) {
            $cleanId = $match[1];
        } elseif (str_starts_with($rawIdentifier, 'SAE-QR:')) {
            // Token format dinamis: SAE-QR:{nisn}:{timestamp}:{hash}
            $parts = explode(':', $rawIdentifier);
            if (count($parts) >= 2) {
                $cleanId = $parts[1];
            }
        }

        // Cari siswa via RFID UID pada tabel peserta_didik_meta
        $metaSiswa = PesertaDidikMeta::where('rfid_uid', $cleanId)->first();
        $pdId = $metaSiswa ? $metaSiswa->peserta_didik_id : null;

        // Jika tidak ketemu via RFID, cari via NISN atau peserta_didik_id
        $siswaQuery = DB::table('peserta_didik as pd')
            ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->select(
                'pd.peserta_didik_id',
                'pd.nama',
                'pd.nisn',
                'pd.nipd',
                'pd.rombongan_belajar_id',
                'rb.nama as nama_rombel',
                'pdm.foto_path'
            );

        if ($pdId) {
            $siswa = $siswaQuery->where('pd.peserta_didik_id', $pdId)->first();
        } else {
            $siswa = $siswaQuery->where(function ($q) use ($cleanId) {
                $q->where('pd.nisn', $cleanId)
                    ->orWhere('pd.peserta_didik_id', $cleanId)
                    ->orWhere('pd.nipd', $cleanId);
            })->first();
        }

        if (!$siswa) {
            return response()->json([
                'status' => 'error',
                'title' => 'Siswa Tidak Ditemukan',
                'message' => "Kartu RFID atau QR [{$cleanId}] belum terdaftar dalam sistem database sekolah.",
                'speech_text' => 'Kartu atau kode tidak terdaftar.',
            ], 404);
        }

        // 4. Deteksi Metode Pemindai
        $metodeScan = 'manual';
        if ($metaSiswa && $metaSiswa->rfid_uid === $cleanId) {
            $metodeScan = 'rfid';
        } elseif (strlen($cleanId) >= 10 && ctype_digit($cleanId)) {
            $metodeScan = 'qr_code';
        }

        // 5. Cek Record Presensi Hari Ini
        $presensi = PresensiHarian::firstOrNew([
            'peserta_didik_id' => $siswa->peserta_didik_id,
            'tanggal' => $today,
        ]);

        $isNewRecord = !$presensi->exists;
        $presensi->nisn = $siswa->nisn;
        $presensi->rombongan_belajar_id = $siswa->rombongan_belajar_id;

        // 6. Simpan Foto Snapshot Live Kamera jika ada
        $snapshotPath = null;
        if (!empty($snapshotBase64) && str_starts_with($snapshotBase64, 'data:image/')) {
            try {
                $snapshotPath = $this->saveSnapshotImage($snapshotBase64, $siswa->nisn ?: $siswa->peserta_didik_id, $now);
            } catch (\Throwable $e) {
                // Ignore snapshot error to prevent blocking attendance
            }
        }

        // 7. Logika Penentuan Masuk vs Pulang
        $actionType = 'masuk';
        $speechGreeting = '';
        $messageDetail = '';

        $jamPulangMulai = $pengaturan->jam_pulang_mulai ?: '14:30:00';
        $jamMasukSelesai = $pengaturan->jam_masuk_selesai ?: '07:15:00';

        // Jika mode dipaksakan 'pulang' atau sudah ada jam_masuk dan sekarang >= jam_pulang_mulai
        if ($mode === 'pulang' || (!empty($presensi->jam_masuk) && $currentTime >= $jamPulangMulai)) {
            $actionType = 'pulang';

            // Cek apakah sudah pernah scan pulang
            if (!empty($presensi->jam_pulang)) {
                return response()->json([
                    'status' => 'info',
                    'title' => 'Sudah Presensi Pulang',
                    'message' => "{$siswa->nama} sudah tercatat presensi pulang pada pukul {$presensi->jam_pulang} WIB.",
                    'speech_text' => "{$siswa->nama}, Anda sudah melakukan presensi pulang sebelumnya.",
                    'data' => $this->formatSiswaResponseData($siswa, $presensi),
                ]);
            }

            $presensi->jam_pulang = $currentTime;
            $presensi->metode_pulang = $metodeScan;
            if ($snapshotPath) {
                $presensi->foto_pulang = $snapshotPath;
            }

            // Status ketepatan pulang
            if ($currentTime < $jamPulangMulai) {
                $presensi->status_ketepatan_pulang = 'pulang_cepat';
                $messageDetail = "Presensi pulang berhasil (Pulang Cepat pada {$currentTime} WIB).";
                $speechGreeting = "Sampai jumpa {$siswa->nama}, presensi pulang berhasil.";
            } else {
                $presensi->status_ketepatan_pulang = 'tepat_waktu';
                $messageDetail = "Presensi pulang tepat waktu pada {$currentTime} WIB.";
                $speechGreeting = "Sampai jumpa {$siswa->nama}, selamat beristirahat.";
            }

            $presensi->save();
        } else {
            // Mode Presensi Masuk
            $actionType = 'masuk';

            if (!empty($presensi->jam_masuk)) {
                return response()->json([
                    'status' => 'info',
                    'title' => 'Sudah Presensi Masuk',
                    'message' => "{$siswa->nama} sudah tercatat presensi masuk pada pukul {$presensi->jam_masuk} WIB.",
                    'speech_text' => "{$siswa->nama}, Anda sudah presensi masuk pagi ini.",
                    'data' => $this->formatSiswaResponseData($siswa, $presensi),
                ]);
            }

            $presensi->jam_masuk = $currentTime;
            $presensi->metode_masuk = $metodeScan;
            if ($snapshotPath) {
                $presensi->foto_masuk = $snapshotPath;
            }

            // Hitung menit keterlambatan
            $timeMasukTarget = Carbon::parse($today . ' ' . $jamMasukSelesai);
            $timeNow = Carbon::parse($today . ' ' . $currentTime);

            if ($timeNow->gt($timeMasukTarget)) {
                $diffMinutes = $timeMasukTarget->diffInMinutes($timeNow);
                if ($diffMinutes > $pengaturan->toleransi_terlambat_menit) {
                    $presensi->status = 'T';
                    $presensi->status_ketepatan_masuk = 'terlambat';
                    $presensi->menit_terlambat = $diffMinutes;
                    $messageDetail = "Presensi masuk tercatat: Terlambat {$diffMinutes} menit ({$currentTime} WIB).";
                    $speechGreeting = "Selamat pagi {$siswa->nama}, presensi masuk berhasil. Anda terlambat {$diffMinutes} menit.";
                } else {
                    $presensi->status = 'H';
                    $presensi->status_ketepatan_masuk = 'tepat_waktu';
                    $presensi->menit_terlambat = 0;
                    $messageDetail = "Presensi masuk tepat waktu ({$currentTime} WIB).";
                    $speechGreeting = "Selamat pagi {$siswa->nama}, presensi masuk berhasil tepat waktu.";
                }
            } else {
                $presensi->status = 'H';
                $presensi->status_ketepatan_masuk = 'tepat_waktu';
                $presensi->menit_terlambat = 0;
                $messageDetail = "Presensi masuk tepat waktu ({$currentTime} WIB).";
                $speechGreeting = "Selamat pagi {$siswa->nama}, presensi masuk berhasil tepat waktu.";
            }

            $presensi->save();
        }

        return response()->json([
            'status' => 'success',
            'action' => $actionType,
            'title' => 'Presensi Berhasil',
            'message' => $messageDetail,
            'speech_text' => $speechGreeting,
            'data' => $this->formatSiswaResponseData($siswa, $presensi),
        ]);
    }

    /**
     * API Endpoint: 6 Data Presensi Terkini untuk Live Monitor Kiosk
     */
    public function getLiveLog(Request $request)
    {
        $today = now()->toDateString();
        $recent = DB::table('presensi_harian as ph')
            ->join('peserta_didik as pd', 'ph.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'ph.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->where('ph.tanggal', $today)
            ->select(
                'ph.id',
                'ph.status',
                'ph.jam_masuk',
                'ph.jam_pulang',
                'ph.menit_terlambat',
                'ph.foto_masuk',
                'ph.foto_pulang',
                'pd.nama',
                'pd.nisn',
                'rb.nama as rombel',
                'pdm.foto_path'
            )
            ->orderBy('ph.updated_at', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($s) {
                $s->foto_url = !empty($s->foto_path) ? asset('storage/' . ltrim($s->foto_path, '/')) : null;
                $s->snapshot_url = !empty($s->foto_masuk) ? asset('storage/' . ltrim($s->foto_masuk, '/')) : null;
                $s->status_label = PresensiHarian::STATUS_LABELS[$s->status] ?? $s->status;
                return $s;
            });

        // Summary Real-Time
        $totalSiswa = DB::table('peserta_didik')->count();
        $countHadir = PresensiHarian::where('tanggal', $today)->whereIn('status', ['H', 'T'])->count();

        return response()->json([
            'status' => 'success',
            'recent' => $recent,
            'stats' => [
                'total_siswa' => $totalSiswa,
                'hadir' => $countHadir,
                'persen' => $totalSiswa > 0 ? round(($countHadir / $totalSiswa) * 100, 1) : 0,
            ],
        ]);
    }

    /**
     * Dasbor Presensi Kelas untuk Guru & Wali Kelas
     */
    public function kelas(Request $request)
    {
        $user = session('user');
        $userId = is_array($user) ? ($user['id'] ?? ($user['pengguna_id'] ?? null)) : ($user->id ?? ($user->pengguna_id ?? null));
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

        // Ambil daftar rombel yang diampu sebagai Wali Kelas jika ada
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

        // Daftar Seluruh Rombel
        $rombelList = DB::table('rombongan_belajar')
            ->where('jenis_rombel', '1')
            ->orderBy('tingkat_pendidikan_id', 'asc')
            ->orderBy('nama', 'asc')
            ->select('rombongan_belajar_id', 'nama')
            ->get();

        $selectedRombelId = $request->input('rombel_id', $waliRombel ?: ($rombelList->first()?->rombongan_belajar_id ?? ''));
        $tanggal = $request->input('tanggal', now()->toDateString());

        // Ambil Siswa di Rombel Terpilih
        $siswaList = collect();
        $selectedRombel = null;

        if ($selectedRombelId) {
            $selectedRombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $selectedRombelId)->first();

            $siswaList = DB::table('peserta_didik as pd')
                ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
                ->leftJoin('presensi_harian as ph', function ($join) use ($tanggal) {
                    $join->on('pd.peserta_didik_id', '=', 'ph.peserta_didik_id')
                        ->where('ph.tanggal', '=', $tanggal);
                })
                ->where('pd.rombongan_belajar_id', $selectedRombelId)
                ->select(
                    'pd.peserta_didik_id',
                    'pd.nama',
                    'pd.nisn',
                    'pd.jenis_kelamin',
                    'pdm.foto_path',
                    'pdm.rfid_uid',
                    'ph.id as presensi_id',
                    'ph.status as status_presensi',
                    'ph.jam_masuk',
                    'ph.jam_pulang',
                    'ph.menit_terlambat',
                    'ph.keterangan',
                    'ph.lampiran_dokumen',
                    'ph.foto_masuk'
                )
                ->orderBy('pd.nama', 'asc')
                ->get()
                ->map(function ($item) {
                    $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
                    $item->lampiran_url = !empty($item->lampiran_dokumen) ? asset('storage/' . ltrim($item->lampiran_dokumen, '/')) : null;
                    $item->snapshot_url = !empty($item->foto_masuk) ? asset('storage/' . ltrim($item->foto_masuk, '/')) : null;
                    return $item;
                });
        }

        // Rekap kelas hari ini
        $rekap = [
            'total' => $siswaList->count(),
            'hadir' => $siswaList->where('status_presensi', 'H')->count(),
            'terlambat' => $siswaList->where('status_presensi', 'T')->count(),
            'izin' => $siswaList->where('status_presensi', 'I')->count(),
            'sakit' => $siswaList->where('status_presensi', 'S')->count(),
            'dispen' => $siswaList->where('status_presensi', 'D')->count(),
            'alpha' => $siswaList->where('status_presensi', 'A')->count(),
            'belum' => $siswaList->whereNull('status_presensi')->count(),
        ];

        return view('dashboard.presensi.kelas', compact(
            'rombelList',
            'selectedRombelId',
            'selectedRombel',
            'tanggal',
            'siswaList',
            'rekap',
            'waliRombel'
        ));
    }

    /**
     * API: Update Cepat Status Kehadiran Siswa per Kelas
     */
    public function updateStatusKelas(Request $request)
    {
        $request->validate([
            'peserta_didik_id' => 'required|string',
            'tanggal' => 'required|date',
            'status' => 'required|string|in:H,T,I,S,A,D',
            'keterangan' => 'nullable|string|max:500',
            'lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
        ]);

        $user = session('user');
        $verifiedBy = is_array($user) ? ($user['nama'] ?? 'Guru') : ($user->nama ?? 'Guru');

        $pdId = $request->input('peserta_didik_id');
        $tanggal = $request->input('tanggal');
        $status = $request->input('status');
        $keterangan = $request->input('keterangan');

        $siswa = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
        if (!$siswa) {
            return response()->json(['status' => 'error', 'message' => 'Siswa tidak ditemukan.'], 404);
        }

        $presensi = PresensiHarian::firstOrNew([
            'peserta_didik_id' => $pdId,
            'tanggal' => $tanggal,
        ]);

        $presensi->nisn = $siswa->nisn;
        $presensi->rombongan_belajar_id = $siswa->rombongan_belajar_id;
        $presensi->status = $status;
        $presensi->keterangan = $keterangan;
        $presensi->verified_by = $verifiedBy;
        $presensi->metode_masuk = 'manual';

        if ($status === 'H' && empty($presensi->jam_masuk)) {
            $presensi->jam_masuk = '07:00:00';
            $presensi->status_ketepatan_masuk = 'tepat_waktu';
        }

        // Upload lampiran jika ada
        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');
            $ext = $file->getClientOriginalExtension();
            $fileName = 'surat_' . ($siswa->nisn ?: $pdId) . '_' . date('Ymd_His') . '.' . $ext;
            $path = $file->storeAs('presensi/surat', $fileName, 'public');
            $presensi->lampiran_dokumen = $path;
        }

        $presensi->save();

        return response()->json([
            'status' => 'success',
            'message' => "Status presensi {$siswa->nama} berhasil diubah menjadi " . (PresensiHarian::STATUS_LABELS[$status] ?? $status),
            'badge' => PresensiHarian::STATUS_BADGES[$status] ?? $status,
        ]);
    }

    /**
     * API: Tandai Siswa yang Belum Hadir di Rombel sebagai Alpha
     */
    public function tandaiAlphaRombel(Request $request)
    {
        $request->validate([
            'rombongan_belajar_id' => 'required|string',
            'tanggal' => 'required|date',
        ]);

        $user = session('user');
        $verifiedBy = is_array($user) ? ($user['nama'] ?? 'Guru') : ($user->nama ?? 'Guru');

        $rombelId = $request->input('rombongan_belajar_id');
        $tanggal = $request->input('tanggal');

        // Ambil semua siswa di rombel
        $siswaList = DB::table('peserta_didik')->where('rombongan_belajar_id', $rombelId)->get();

        $markedCount = 0;
        foreach ($siswaList as $siswa) {
            $exists = PresensiHarian::where('peserta_didik_id', $siswa->peserta_didik_id)
                ->where('tanggal', $tanggal)
                ->exists();

            if (!$exists) {
                PresensiHarian::create([
                    'peserta_didik_id' => $siswa->peserta_didik_id,
                    'nisn' => $siswa->nisn,
                    'rombongan_belajar_id' => $rombelId,
                    'tanggal' => $tanggal,
                    'status' => 'A',
                    'metode_masuk' => 'manual',
                    'keterangan' => 'Tanpa keterangan hingga batas waktu presensi harian',
                    'verified_by' => $verifiedBy,
                ]);
                $markedCount++;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Sebanyak {$markedCount} peserta didik yang belum absen berhasil ditandai sebagai Alpha.",
            'count' => $markedCount,
        ]);
    }

    /**
     * Portal Riwayat Presensi Peserta Didik (Siswa)
     */
    public function riwayatSaya(Request $request)
    {
        $user = session('user');
        $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
        $nisn = is_array($user) ? ($user['nisn'] ?? null) : ($user->nisn ?? null);

        $siswa = null;
        if ($pdId) {
            $siswa = DB::table('peserta_didik as pd')
                ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
                ->where('pd.peserta_didik_id', $pdId)
                ->select('pd.*', 'rb.nama as nama_rombel', 'pdm.foto_path', 'pdm.rfid_uid')
                ->first();
        } elseif ($nisn) {
            $siswa = DB::table('peserta_didik as pd')
                ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
                ->where('pd.nisn', $nisn)
                ->select('pd.*', 'rb.nama as nama_rombel', 'pdm.foto_path', 'pdm.rfid_uid')
                ->first();
        }

        if (!$siswa) {
            return redirect()->route('dashboard.peserta-didik')->with('error', 'Biodata peserta didik tidak ditemukan.');
        }

        $siswa->foto_url = !empty($siswa->foto_path) ? asset('storage/' . ltrim($siswa->foto_path, '/')) : null;

        // Dynamic QR Token untuk Presensi Anti-Joki (Berisi SAE-QR:{nisn}:{timestamp}:{hash})
        $timestamp = time();
        $secretKey = config('app.key') ?: 'sae_secret_salt';
        $qrPayload = 'SAE-QR:' . $siswa->nisn . ':' . $timestamp . ':' . substr(hash('sha256', $siswa->nisn . $timestamp . $secretKey), 0, 8);
        $dynamicQrUri = QrCodeService::generateDataUri($qrPayload, 200, 1);

        // Statistik Presensi Bulan Ini
        $bulanIni = now()->format('Y-m');
        $riwayatBulanIni = PresensiHarian::where('peserta_didik_id', $siswa->peserta_didik_id)
            ->where('tanggal', 'like', "{$bulanIni}%")
            ->get();

        $stats = [
            'hadir' => $riwayatBulanIni->where('status', 'H')->count(),
            'terlambat' => $riwayatBulanIni->where('status', 'T')->count(),
            'izin' => $riwayatBulanIni->where('status', 'I')->count(),
            'sakit' => $riwayatBulanIni->where('status', 'S')->count(),
            'alpha' => $riwayatBulanIni->where('status', 'A')->count(),
            'total_kehadiran' => $riwayatBulanIni->count(),
        ];

        // Riwayat Log Presensi 30 Hari Terakhir
        $logs = PresensiHarian::where('peserta_didik_id', $siswa->peserta_didik_id)
            ->orderBy('tanggal', 'desc')
            ->paginate(15);

        // Riwayat Pengajuan Izin
        $daftarIzin = PresensiIzin::where('peserta_didik_id', $siswa->peserta_didik_id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard.presensi.riwayat-saya', compact('siswa', 'dynamicQrUri', 'stats', 'logs', 'daftarIzin'));
    }

    /**
     * API: Pengajuan Izin / Sakit Mandiri
     */
    public function pengajuanIzin(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'jenis' => 'required|string|in:izin,sakit,dispen',
            'alasan' => 'required|string|max:1000',
            'lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
        ]);

        $user = session('user');
        $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
        $nisn = is_array($user) ? ($user['nisn'] ?? null) : ($user->nisn ?? null);

        $siswa = DB::table('peserta_didik')
            ->where('peserta_didik_id', $pdId)
            ->orWhere('nisn', $nisn)
            ->first();

        if (!$siswa) {
            return response()->json(['status' => 'error', 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        $path = null;
        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');
            $ext = $file->getClientOriginalExtension();
            $fileName = 'izin_' . ($siswa->nisn ?: $siswa->peserta_didik_id) . '_' . date('Ymd_His') . '.' . $ext;
            $path = $file->storeAs('presensi/surat', $fileName, 'public');
        }

        $izin = PresensiIzin::create([
            'peserta_didik_id' => $siswa->peserta_didik_id,
            'nisn' => $siswa->nisn,
            'rombongan_belajar_id' => $siswa->rombongan_belajar_id,
            'tanggal_mulai' => $request->input('tanggal_mulai'),
            'tanggal_selesai' => $request->input('tanggal_selesai'),
            'jenis' => $request->input('jenis'),
            'alasan' => $request->input('alasan'),
            'lampiran_path' => $path,
            'status' => 'menunggu',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan surat ' . $izin->jenis_label . ' berhasil dikirim dan menunggu validasi Wali Kelas.',
        ]);
    }

    /**
     * API: Verifikasi Pengajuan Izin (Approve/Reject)
     */
    public function verifikasiIzin(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:disetujui,ditolak',
            'catatan' => 'nullable|string|max:500',
        ]);

        $user = session('user');
        $verifiedBy = is_array($user) ? ($user['nama'] ?? 'Guru') : ($user->nama ?? 'Guru');

        $izin = PresensiIzin::findOrFail($id);
        $izin->status = $request->input('status');
        $izin->disetujui_oleh = $verifiedBy;
        $izin->catatan_petugas = $request->input('catatan');
        $izin->save();

        if ($izin->status === 'disetujui') {
            $izin->applyToDailyAttendance($verifiedBy);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Pengajuan izin berhasil diperbarui menjadi: {$izin->status}.",
        ]);
    }

    /**
     * API: Pasangkan / Perbarui Kartu RFID Siswa
     */
    public function assignRfid(Request $request)
    {
        $request->validate([
            'peserta_didik_id' => 'required|string',
            'rfid_uid' => 'required|string|max:64',
        ]);

        $pdId = $request->input('peserta_didik_id');
        $rfidUid = trim($request->input('rfid_uid'));

        // Cek duplikasi kartu RFID pada siswa lain
        $duplicate = PesertaDidikMeta::where('rfid_uid', $rfidUid)
            ->where('peserta_didik_id', '!=', $pdId)
            ->first();

        if ($duplicate) {
            $siswaLain = DB::table('peserta_didik')->where('peserta_didik_id', $duplicate->peserta_didik_id)->first();
            $namaLain = $siswaLain ? $siswaLain->nama : 'Siswa Lain';
            return response()->json([
                'status' => 'error',
                'message' => "Kartu RFID UID [{$rfidUid}] sudah digunakan oleh {$namaLain} ({$duplicate->nisn}). Silakan gunakan kartu lain atau lepaskan kartu terlebih dahulu.",
            ], 422);
        }

        $meta = PesertaDidikMeta::firstOrNew(['peserta_didik_id' => $pdId]);
        $siswa = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();

        $meta->nisn = $siswa ? $siswa->nisn : $meta->nisn;
        $meta->rfid_uid = $rfidUid;
        $meta->rfid_registered_at = now();
        $meta->save();

        return response()->json([
            'status' => 'success',
            'message' => "Kartu RFID [{$rfidUid}] berhasil didaftarkan untuk {$siswa->nama}.",
        ]);
    }

    /**
     * API: Update Konfigurasi Jam Kerja Presensi
     */
    public function updatePengaturan(Request $request)
    {
        $request->validate([
            'jam_masuk_mulai' => 'required|date_format:H:i',
            'jam_masuk_selesai' => 'required|date_format:H:i',
            'jam_masuk_toleransi' => 'required|date_format:H:i',
            'jam_pulang_mulai' => 'required|date_format:H:i',
            'jam_pulang_selesai' => 'required|date_format:H:i',
            'hari_aktif' => 'required|array|min:1',
            'toleransi_terlambat_menit' => 'required|integer|min:0|max:120',
            'require_camera' => 'nullable|boolean',
            'allow_rfid' => 'nullable|boolean',
            'allow_qr' => 'nullable|boolean',
        ]);

        $pengaturan = PresensiPengaturan::getPengaturan();
        $pengaturan->update([
            'jam_masuk_mulai' => $request->input('jam_masuk_mulai') . ':00',
            'jam_masuk_selesai' => $request->input('jam_masuk_selesai') . ':00',
            'jam_masuk_toleransi' => $request->input('jam_masuk_toleransi') . ':00',
            'jam_pulang_mulai' => $request->input('jam_pulang_mulai') . ':00',
            'jam_pulang_selesai' => $request->input('jam_pulang_selesai') . ':00',
            'hari_aktif' => $request->input('hari_aktif'),
            'toleransi_terlambat_menit' => (int) $request->input('toleransi_terlambat_menit'),
            'require_camera' => $request->boolean('require_camera'),
            'allow_rfid' => $request->boolean('allow_rfid'),
            'allow_qr' => $request->boolean('allow_qr'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Konfigurasi jadwal dan aturan presensi sekolah berhasil disimpan.',
        ]);
    }

    /**
     * Ekspor Rekapitulasi Presensi (CSV Format)
     */
    public function export(Request $request)
    {
        $rombelId = $request->input('rombel_id');
        $bulan = $request->input('bulan', now()->format('Y-m'));

        $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $rombelId)->first();
        $namaRombel = $rombel ? str_replace([' ', '/'], '_', $rombel->nama) : 'Semua_Rombel';

        $siswaList = DB::table('peserta_didik')
            ->when($rombelId, fn($q) => $q->where('rombongan_belajar_id', $rombelId))
            ->orderBy('nama', 'asc')
            ->get();

        $csvFileName = "Rekap_Presensi_{$namaRombel}_{$bulan}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$csvFileName}\"",
        ];

        $callback = function () use ($siswaList, $bulan) {
            $file = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['No', 'NISN', 'Nama Peserta Didik', 'Hadir (H)', 'Terlambat (T)', 'Izin (I)', 'Sakit (S)', 'Dispen (D)', 'Alpha (A)', 'Total Kehadiran (%)']);

            $no = 1;
            foreach ($siswaList as $s) {
                $logs = PresensiHarian::where('peserta_didik_id', $s->peserta_didik_id)
                    ->where('tanggal', 'like', "{$bulan}%")
                    ->get();

                $h = $logs->where('status', 'H')->count();
                $t = $logs->where('status', 'T')->count();
                $i = $logs->where('status', 'I')->count();
                $sakit = $logs->where('status', 'S')->count();
                $d = $logs->where('status', 'D')->count();
                $a = $logs->where('status', 'A')->count();
                $totalHadir = $h + $t;
                $totalSesi = $logs->count();
                $persen = $totalSesi > 0 ? round(($totalHadir / $totalSesi) * 100, 1) . '%' : '0%';

                fputcsv($file, [$no++, $s->nisn, $s->nama, $h, $t, $i, $sakit, $d, $a, $persen]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Helper: Simpan Snapshot Foto Webcam dari Base64 String
     */
    protected function saveSnapshotImage(string $base64Data, string $identifier, Carbon $now): string
    {
        // Format base64: data:image/jpeg;base64,...
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
            $data = substr($base64Data, strpos($base64Data, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, etc.
            if (!in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                $type = 'jpg';
            }
            $data = base64_decode($data);
            if ($data === false) {
                throw new \Exception('Gagal mendecode base64 image');
            }
        } else {
            throw new \Exception('Data URI tidak valid');
        }

        $folder = 'presensi/foto/' . $now->format('Y-m');
        $fileName = 'snap_' . $identifier . '_' . $now->format('Ymd_His') . '_' . Str::random(4) . '.' . $type;
        $fullPath = $folder . '/' . $fileName;

        Storage::disk('public')->put($fullPath, $data);

        return $fullPath;
    }

    /**
     * Helper: Format Data Siswa untuk Respon JSON Kiosk/Scan
     */
    protected function formatSiswaResponseData($siswa, PresensiHarian $presensi): array
    {
        return [
            'peserta_didik_id' => $siswa->peserta_didik_id,
            'nama' => $siswa->nama,
            'nisn' => $siswa->nisn,
            'rombel' => $siswa->nama_rombel ?? '-',
            'foto_url' => !empty($siswa->foto_path) ? asset('storage/' . ltrim($siswa->foto_path, '/')) : null,
            'jam_masuk' => $presensi->jam_masuk ? substr($presensi->jam_masuk, 0, 5) : null,
            'jam_pulang' => $presensi->jam_pulang ? substr($presensi->jam_pulang, 0, 5) : null,
            'status' => $presensi->status,
            'status_label' => $presensi->status_label,
            'menit_terlambat' => $presensi->menit_terlambat,
            'foto_masuk_url' => $presensi->foto_masuk_url,
            'foto_pulang_url' => $presensi->foto_pulang_url,
        ];
    }
}
