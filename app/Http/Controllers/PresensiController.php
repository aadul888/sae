<?php

namespace App\Http\Controllers;

use App\Models\KalenderPendidikan;
use App\Models\PesertaDidikMeta;
use App\Models\PresensiHarian;
use App\Models\PresensiIzin;
use App\Models\PresensiMapel;
use App\Models\PresensiPengaturan;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\PresensiKelasService;
use App\Services\PresensiKioskService;
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
        // Otomatis tandai pulang cepat untuk siswa yang tidak tap pulang di hari sebelumnya
        PresensiHarian::autoCloseUncheckedOut();

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? 'guru') : ($user->role ?? 'guru');

        $tanggal = $request->input('tanggal', now()->toDateString());
        $pengaturan = PresensiPengaturan::getPengaturan();

        // Cek status hari (Luring, Daring, Libur) dari Kalender Pendidikan
        $statusHari = KalenderPendidikan::getStatusHari($tanggal, 'pd');
        $isLiburKalender = $statusHari['mode'] === 'libur';
        $isDaringKalender = $statusHari['mode'] === 'daring';
        $agendaHariIni = $statusHari['agenda'];

        // Ambil daftar jurusan / kompetensi keahlian untuk pengaturan
        $jurusanList = PresensiPengaturan::getJurusanList();

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

        $sortLog    = $request->input('sort', 'jam_masuk');
        $sortLogDir = $request->input('sort_dir', 'desc');
        $allowedLogSorts = ['nama_siswa', 'nama_rombel', 'jam_masuk', 'jam_pulang', 'status'];
        if (!in_array($sortLog, $allowedLogSorts, true)) {
            $sortLog = 'jam_masuk';
        }
        if (!in_array($sortLogDir, ['asc', 'desc'], true)) {
            $sortLogDir = 'desc';
        }

        $logs = $logsQuery->orderBy('ph.' . $sortLog, $sortLogDir)->paginate($perPageLog)->withQueryString();

        // Format foto URL untuk siswa di logs
        $logs->getCollection()->transform(function ($item) {
            $item->foto_profil_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
            $item->foto_masuk_url = !empty($item->foto_masuk) ? asset('storage/' . ltrim($item->foto_masuk, '/')) : null;
            $item->foto_pulang_url = !empty($item->foto_pulang) ? asset('storage/' . ltrim($item->foto_pulang, '/')) : null;
            return $item;
        });

        // Daftar Siswa & GTK untuk Manajemen Kartu RFID
        $rfidKategori = $request->input('rfid_kategori', 'siswa'); // 'siswa' atau 'gtk'
        $rfidSearch = trim($request->input('rfid_search', ''));
        $rfidFilter = $request->input('rfid_status', '');
        $rfidRombel = $request->input('rfid_rombel', '');

        $perPageRfid = (int) $request->input('perPageRfid', $request->input('perPage', 15));
        if (!in_array($perPageRfid, [10, 15, 25, 50, 100])) {
            $perPageRfid = 15;
        }

        if ($rfidKategori === 'gtk') {
            $gtkRfidQuery = DB::table('pengguna as p')
                ->leftJoin('gtk as g', 'p.ptk_id', '=', 'g.ptk_id')
                ->where(function ($q) {
                    $q->where('p.peran_id_str', 'like', '%guru%')
                        ->orWhere('p.peran_id_str', 'like', '%tendik%')
                        ->orWhere('p.peran_id_str', 'like', '%admin%')
                        ->orWhere('p.peran_id_str', 'like', '%operator%')
                        ->orWhereNotNull('p.ptk_id');
                })
                ->where(function ($q) {
                    $q->where('p.peran_id_str', 'not like', '%peserta didik%')
                        ->orWhereNull('p.peran_id_str');
                })
                ->whereNull('p.peserta_didik_id')
                ->select(
                    'p.pengguna_id',
                    'p.nama',
                    'p.username',
                    'p.peran_id_str',
                    'p.rfid_uid',
                    'p.rfid_registered_at',
                    'p.foto_path',
                    'g.nip',
                    'g.nuptk',
                    'g.jenis_ptk_id_str'
                );

            if (!empty($rfidSearch)) {
                $gtkRfidQuery->where(function ($q) use ($rfidSearch) {
                    $q->where('p.nama', 'like', "%{$rfidSearch}%")
                        ->orWhere('p.username', 'like', "%{$rfidSearch}%")
                        ->orWhere('p.rfid_uid', 'like', "%{$rfidSearch}%")
                        ->orWhere('g.nip', 'like', "%{$rfidSearch}%")
                        ->orWhere('g.nuptk', 'like', "%{$rfidSearch}%");
                });
            }

            if ($rfidFilter === 'terdaftar') {
                $gtkRfidQuery->whereNotNull('p.rfid_uid')->where('p.rfid_uid', '!=', '');
            } elseif ($rfidFilter === 'belum') {
                $gtkRfidQuery->where(function ($q) {
                    $q->whereNull('p.rfid_uid')->orWhere('p.rfid_uid', '');
                });
            }

            $gtkRfidList = $gtkRfidQuery->orderBy('p.nama', 'asc')
                ->paginate($perPageRfid, ['*'], 'rfid_page')
                ->appends(['tab' => 'rfid', 'rfid_kategori' => 'gtk'])
                ->withQueryString();

            $siswaRfidList = null;
        } else {
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

            if (!empty($rfidRombel)) {
                $siswaRfidQuery->where('pd.rombongan_belajar_id', $rfidRombel);
            }

            $siswaRfidList = $siswaRfidQuery->orderBy('pd.nama', 'asc')
                ->paginate($perPageRfid, ['*'], 'rfid_page')
                ->appends(['tab' => 'rfid', 'rfid_kategori' => 'siswa'])
                ->withQueryString();

            $gtkRfidList = null;
        }

        $activeTab = $request->input('tab', 'log');
        if ($request->filled('rfid_search') || $request->filled('rfid_status') || $request->filled('rfid_rombel') || $request->has('rfid_page') || $request->filled('rfid_kategori')) {
            $activeTab = 'rfid';
        }

        // Pengajuan Izin Menunggu Verifikasi
        $izinPending = PresensiIzin::where('status', 'menunggu')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Informasi Satuan Pendidikan & Titik Koordinat Efektif
        $sekolah = PresensiPengaturan::getSekolah();
        $effectiveLat = $pengaturan->getEffectiveLatitude();
        $effectiveLon = $pengaturan->getEffectiveLongitude();
        $effectiveRadius = $pengaturan->getEffectiveRadius();

        return view('dashboard.presensi.index', compact(
            'pengaturan',
            'sekolah',
            'effectiveLat',
            'effectiveLon',
            'effectiveRadius',
            'tanggal',
            'activeTab',
            'statusHari',
            'isLiburKalender',
            'isDaringKalender',
            'agendaHariIni',
            'jurusanList',
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
            'sortLog',
            'sortLogDir',
            'rfidKategori',
            'rfidRombel',
            'siswaRfidList',
            'gtkRfidList',
            'izinPending',
            'perPageLog',
            'perPageRfid'
        ));
    }

    /**
     * Halaman Publik: Verifikasi Kode Akses Terminal Kiosk
     */
    public function kioskAuth(Request $request)
    {
        $isUserAllowed = false;
        if (session()->has('user')) {
            $user = \App\Models\User::find(session('user.id'));
            if ($user && \App\Models\RolePermission::canAccess($user, 'menu_rfid', 'read')) {
                $isUserAllowed = true;
            }
        }
        $isKioskAllowed = session('kiosk_access_granted') === true;

        if ($isUserAllowed || $isKioskAllowed) {
            return redirect()->route('presensi.scan');
        }

        $sekolah = PresensiPengaturan::getSekolah();
        return view('dashboard.presensi.kiosk-auth', compact('sekolah'));
    }

    /**
     * Endpoint API: Buka Akses Terminal Kiosk dengan Kode Akses atau Tap Kartu RFID Guru/Tendik
     */
    public function kioskUnlock(Request $request)
    {
        $request->validate([
            'kode_akses' => 'required|string|max:64',
        ]);

        $res = PresensiKioskService::unlock((string) $request->input('kode_akses'));

        return response()->json($res, $res['code'] ?? 200);
    }

    /**
     * API: Update Cepat Kode Akses Terminal Kiosk oleh Admin
     */
    public function updateKodeAkses(Request $request)
    {
        $request->validate([
            'kode_akses' => 'required|string|min:3|max:50',
        ]);

        $newCode = str_replace(' ', '', strtoupper(trim($request->input('kode_akses'))));
        $pengaturan = PresensiPengaturan::getPengaturan();
        $pengaturan->update(['kode_akses' => $newCode]);

        return response()->json([
            'status' => 'success',
            'message' => "Kode akses terminal kiosk berhasil diperbarui menjadi [{$newCode}] dan telah tersimpan di database.",
            'kode_akses' => $newCode,
        ]);
    }

    /**
     * Kunci Kembali Terminal Kiosk (Keluar dari Mode Publik)
     */
    public function kioskLock(Request $request)
    {
        session()->forget(['kiosk_access_granted', 'kiosk_login_at']);
        session()->save();

        if ($request->expectsJson() || $request->wantsJson() || $request->isJson() || $request->header('Sec-Fetch-Mode') === 'cors') {
            return response()->json([
                'status'  => 'success',
                'message' => 'Terminal presensi telah dikunci.',
            ]);
        }

        $redirectTo = $request->query('redirect', route('presensi.kiosk.auth'));
        return redirect($redirectTo)->with('info', 'Terminal presensi telah dikunci.');
    }

    /**
     * Layar Penuh Kiosk Terminal Pemindai Presensi (Scanner Station)
     */
    public function scanKiosk(Request $request)
    {
        $isUserAllowed = false;
        if (session()->has('user')) {
            $user = \App\Models\User::find(session('user.id'));
            if ($user && \App\Models\RolePermission::canAccess($user, 'menu_rfid', 'read')) {
                $isUserAllowed = true;
            }
        }
        $isKioskAllowed = session('kiosk_access_granted') === true;

        if (!$isUserAllowed && !$isKioskAllowed) {
            return redirect()->route('presensi.kiosk.auth');
        }

        $viewData = PresensiKioskService::getScanKioskData($isKioskAllowed, $isUserAllowed);

        return response()
            ->view('dashboard.presensi.scan', $viewData)
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    /**
     * API Endpoint: Proses Pemindaian Tap RFID / QR Code / Barcode
     */
    public function processScan(Request $request)
    {
        $isUserAllowed = false;
        if (session()->has('user')) {
            $user = \App\Models\User::find(session('user.id'));
            if ($user && \App\Models\RolePermission::canAccess($user, 'menu_rfid', 'read')) {
                $isUserAllowed = true;
            }
        }
        $isKioskAllowed = session('kiosk_access_granted') === true;

        if (!$isUserAllowed && !$isKioskAllowed) {
            return response()->json([
                'status' => 'error',
                'title' => 'Akses Ditolak',
                'message' => 'Sesi terminal telah berakhir atau belum terautentikasi. Silakan masukkan kode akses.',
            ], 403);
        }

        $request->validate([
            'identifier' => 'required|string|max:255',
            'mode' => 'nullable|string|in:auto,masuk,pulang',
            'snapshot' => 'nullable|string', // Base64 data URI snapshot webcam
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $res = PresensiKioskService::processScan($request);

        return response()->json($res, $res['code'] ?? 200);
    }

    /**
     * API Endpoint: 6 Data Presensi Terkini untuk Live Monitor Kiosk
     */
    public function getLiveLog(Request $request)
    {
        $res = PresensiKioskService::getLiveLog($request);

        return response()->json($res);
    }

    /**
     * Dasbor Presensi Kelas untuk Guru Mapel & Wali Kelas
     */
    public function kelas(Request $request)
    {
        $viewData = PresensiKelasService::getKelasData($request);

        return view('dashboard.presensi.kelas', $viewData);
    }

    /**
     * API: Update Cepat Status Kehadiran Siswa per Mapel (Guru KBM)
     */
    public function updateStatusKelas(Request $request)
    {
        $request->validate([
            'peserta_didik_id'     => 'required|string',
            'tanggal'              => 'required|date',
            'status'               => 'required|string|in:H,T,I,S,A,reset,batal',
            'pembelajaran_id'      => 'nullable|string',
            'rombongan_belajar_id' => 'nullable|string',
            'jam_ke'               => 'nullable|string|max:20',
            'keterangan'           => 'nullable|string|max:500',
        ]);

        $res = PresensiKelasService::updateStatusKelas($request);

        return response()->json($res, $res['code'] ?? 200);
    }

    /**
     * API: Tandai Seluruh Siswa di Rombel Hadir pada Mapel KBM (Hadir Semua)
     */
    public function hadirSemuaRombel(Request $request)
    {
        $request->validate([
            'rombongan_belajar_id' => 'required|string',
            'tanggal'              => 'required|date',
            'pembelajaran_id'      => 'nullable|string',
            'jam_ke'               => 'nullable|string|max:20',
        ]);

        $res = PresensiKelasService::hadirSemuaRombel($request);

        return response()->json($res, $res['code'] ?? 200);
    }

    /**
     * API: Reset / Kosongkan Presensi Mapel KBM di Kelas (Guru Mapel)
     */
    public function resetPresensiKelas(Request $request)
    {
        $request->validate([
            'rombongan_belajar_id' => 'required|string',
            'tanggal'              => 'required|date',
            'pembelajaran_id'      => 'nullable|string',
        ]);

        $res = PresensiKelasService::resetPresensiKelas($request);

        return response()->json($res, $res['code'] ?? 200);
    }

    /**
     * Alias backward-compatible untuk fungsi lama
     */
    public function tandaiAlphaRombel(Request $request)
    {
        return $this->hadirSemuaRombel($request);
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
     * API: Pasangkan / Perbarui Kartu RFID Siswa atau Guru & Tendik (GTK)
     */
    public function assignRfid(Request $request)
    {
        $request->validate([
            'peserta_didik_id' => 'nullable|string',
            'pengguna_id'      => 'nullable|string',
            'rfid_uid'         => 'required|string|max:64',
        ]);

        $pdId = $request->input('peserta_didik_id');
        $penggunaId = $request->input('pengguna_id');
        $rfidUid = strtoupper(trim($request->input('rfid_uid')));

        if (!$pdId && !$penggunaId) {
            return response()->json([
                'status' => 'error',
                'message' => 'ID peserta didik atau ID pengguna wajib disertakan.',
            ], 422);
        }

        // 1. Cek duplikasi di tabel pengguna (Guru, Tendik, Admin)
        $duplicateUserQuery = User::where('rfid_uid', $rfidUid);
        if ($penggunaId) {
            $duplicateUserQuery->where('pengguna_id', '!=', $penggunaId);
        }
        $duplicateUser = $duplicateUserQuery->first();
        if ($duplicateUser) {
            return response()->json([
                'status' => 'error',
                'message' => "Kartu RFID UID [{$rfidUid}] sudah digunakan oleh {$duplicateUser->nama} ({$duplicateUser->username}).",
            ], 422);
        }

        // 2. Cek duplikasi di tabel peserta_didik_meta (Siswa)
        $duplicateSiswaQuery = PesertaDidikMeta::where('rfid_uid', $rfidUid);
        if ($pdId) {
            $duplicateSiswaQuery->where('peserta_didik_id', '!=', $pdId);
        }
        $duplicateSiswa = $duplicateSiswaQuery->first();
        if ($duplicateSiswa) {
            $siswaLain = DB::table('peserta_didik')->where('peserta_didik_id', $duplicateSiswa->peserta_didik_id)->first();
            $namaLain = $siswaLain ? $siswaLain->nama : 'Peserta Didik Lain';
            return response()->json([
                'status' => 'error',
                'message' => "Kartu RFID UID [{$rfidUid}] sudah digunakan oleh peserta didik {$namaLain} ({$duplicateSiswa->nisn}).",
            ], 422);
        }

        // Kasus: Pendaftaran untuk Akun Pengguna GTK (Guru / Tendik / Admin)
        if ($penggunaId) {
            $targetUser = User::findOrFail($penggunaId);
            $targetUser->rfid_uid = $rfidUid;
            $targetUser->rfid_registered_at = now();
            $targetUser->save();

            return response()->json([
                'status' => 'success',
                'message' => "Kartu RFID [{$rfidUid}] berhasil didaftarkan untuk {$targetUser->nama} (" . ucfirst($targetUser->role) . ").",
            ]);
        }

        // Kasus: Pendaftaran untuk Peserta Didik
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
            'jurusan_aktif' => 'nullable|array',
            'jurusan_aktif.*' => 'string',
            'toleransi_terlambat_menit' => 'required|integer|min:0|max:120',
            'require_camera' => 'nullable|boolean',
            'allow_rfid' => 'nullable|boolean',
            'allow_qr' => 'nullable|boolean',
            'require_location' => 'nullable|boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius_meter' => 'nullable|integer|min:10|max:50000',
            'kode_akses' => 'nullable|string|min:3|max:50',
        ]);

        $pengaturan = PresensiPengaturan::getPengaturan();
        $pengaturan->update([
            'jam_masuk_mulai' => $request->input('jam_masuk_mulai') . ':00',
            'jam_masuk_selesai' => $request->input('jam_masuk_selesai') . ':00',
            'jam_masuk_toleransi' => $request->input('jam_masuk_toleransi') . ':00',
            'jam_pulang_mulai' => $request->input('jam_pulang_mulai') . ':00',
            'jam_pulang_selesai' => $request->input('jam_pulang_selesai') . ':00',
            'hari_aktif' => $request->input('hari_aktif'),
            'jurusan_aktif' => $request->input('jurusan_aktif'),
            'toleransi_terlambat_menit' => (int) $request->input('toleransi_terlambat_menit'),
            'require_camera' => $request->boolean('require_camera'),
            'allow_rfid' => $request->boolean('allow_rfid'),
            'allow_qr' => $request->boolean('allow_qr'),
            'require_location' => $request->boolean('require_location'),
            'latitude' => $request->filled('latitude') ? (float) $request->input('latitude') : null,
            'longitude' => $request->filled('longitude') ? (float) $request->input('longitude') : null,
            'radius_meter' => (int) $request->input('radius_meter', 100),
            'kode_akses' => $request->filled('kode_akses') ? str_replace(' ', '', strtoupper(trim($request->input('kode_akses')))) : ($pengaturan->kode_akses ?: 'SAE123'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Konfigurasi jadwal, aturan, dan kode akses terminal presensi berhasil disimpan.',
        ]);
    }

    /**
     * Ekspor / Cetak Rekapitulasi Presensi (Format PDF Cetak Standar / CSV)
     */
    public function export(Request $request)
    {
        return PresensiKelasService::export($request);
    }

    /**
     * Helper: Simpan Snapshot Foto Webcam dari Base64 String
     */
    protected function saveSnapshotImage(string $base64Data, string $identifier, Carbon $now): string
    {
        return PresensiKioskService::saveSnapshotImage($base64Data, $identifier, $now);
    }

    /**
     * Helper: Format Data Siswa untuk Respon JSON Kiosk/Scan
     */
    protected function formatSiswaResponseData($siswa, PresensiHarian $presensi): array
    {
        return PresensiKioskService::formatSiswaResponseData($siswa, $presensi);
    }
}
