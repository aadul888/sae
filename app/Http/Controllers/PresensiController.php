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

            $siswaRfidList = $siswaRfidQuery->orderBy('pd.nama', 'asc')
                ->paginate($perPageRfid, ['*'], 'rfid_page')
                ->appends(['tab' => 'rfid', 'rfid_kategori' => 'siswa'])
                ->withQueryString();

            $gtkRfidList = null;
        }

        $activeTab = $request->input('tab', 'log');
        if ($request->filled('rfid_search') || $request->filled('rfid_status') || $request->has('rfid_page') || $request->filled('rfid_kategori')) {
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

        $pengaturan = PresensiPengaturan::getPengaturan();
        $expectedCode = str_replace(' ', '', strtoupper(trim($pengaturan->kode_akses ?: 'SAE123')));
        $inputCode = str_replace(' ', '', strtoupper(trim($request->input('kode_akses'))));

        $unlockedBy = null;

        // 1. Cek apakah cocok dengan Kode Akses PIN resmi
        if ($inputCode === $expectedCode) {
            $unlockedBy = 'Kode Akses PIN Terminal';
        } else {
            // 2. Cek apakah cocok dengan kartu RFID Guru, Tendik, atau Admin
            $userRfid = User::where('rfid_uid', $inputCode)->first();
            if ($userRfid && in_array($userRfid->role, ['admin', 'guru', 'tendik'], true)) {
                $roleLabel = match ($userRfid->role) {
                    'admin' => 'Administrator',
                    'guru' => 'Guru',
                    'tendik' => 'Tenaga Kependidikan',
                    default => ucfirst($userRfid->role)
                };
                $unlockedBy = "{$userRfid->nama} ({$roleLabel})";
            }
        }

        if (!$unlockedBy) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode akses salah atau kartu RFID belum terdaftar untuk Guru / Tendik. Silakan periksa kembali.',
            ], 422);
        }

        session([
            'kiosk_access_granted' => true,
            'kiosk_login_at'       => now()->toIso8601String(),
            'kiosk_unlocked_by'    => $unlockedBy,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Akses terminal berhasil dibuka oleh [{$unlockedBy}]. Mengalihkan ke scanner...",
            'redirect_url' => route('presensi.scan'),
            'unlocked_by'  => $unlockedBy,
        ]);
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

        // Jika belum login admin dan belum memasukkan kode akses, arahkan ke layar input kode akses
        if (!$isUserAllowed && !$isKioskAllowed) {
            return redirect()->route('presensi.kiosk.auth');
        }

        $isKioskSession = $isKioskAllowed && !$isUserAllowed;
        $pengaturan = PresensiPengaturan::getPengaturan();
        $today = now()->toDateString();
        $statusHari = KalenderPendidikan::getStatusHari($today, 'pd');
        $isLibur = $statusHari['mode'] === 'libur';
        $agendaLibur = $statusHari['agenda'];
        $isHariAktif = $pengaturan->isHariAktif(now());

        // Recent Scans (6 scan terakhir)
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

        $schoolLat = $pengaturan->getEffectiveLatitude();
        $schoolLon = $pengaturan->getEffectiveLongitude();
        $schoolRadius = $pengaturan->getEffectiveRadius();
        $requireLocation = (bool) $pengaturan->require_location;

        return response()
            ->view('dashboard.presensi.scan', compact(
                'pengaturan',
                'isLibur',
                'agendaLibur',
                'statusHari',
                'isHariAktif',
                'recentScans',
                'schoolLat',
                'schoolLon',
                'schoolRadius',
                'requireLocation',
                'isKioskSession'
            ))
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

        $rawIdentifier = trim($request->input('identifier'));
        $mode = $request->input('mode', 'auto');
        $snapshotBase64 = $request->input('snapshot');

        $now = Carbon::now();
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        $pengaturan = PresensiPengaturan::getPengaturan();

        // 1. Cek Kalender Pendidikan (Kondisi Libur & Daring)
        $statusHari = KalenderPendidikan::getStatusHari($today, 'pd');
        if ($statusHari['mode'] === 'libur') {
            $agenda = $statusHari['agenda'];
            return response()->json([
                'status' => 'warning',
                'title' => 'Hari Libur Akademik',
                'message' => 'Hari ini adalah hari libur sekolah: ' . ($agenda ? $agenda->nama_kegiatan : 'Libur Kalender Pendidikan') . '. Presensi dinonaktifkan.',
                'speech_text' => 'Hari libur sekolah.',
            ], 422);
        }

        if ($statusHari['mode'] === 'daring') {
            $agenda = $statusHari['agenda'];
            return response()->json([
                'status' => 'warning',
                'title' => 'Pembelajaran Daring (PJJ)',
                'message' => 'Hari ini dijadwalkan Pembelajaran Daring (PJJ): ' . ($agenda ? $agenda->nama_kegiatan : 'Belajar di Rumah') . '. Terminal scanner gerbang dinonaktifkan.',
                'speech_text' => 'Pembelajaran daring.',
            ], 422);
        }

        // 2. Cek Geolokasi GPS (Jika diwajibkan oleh pengaturan)
        $userLat = $request->filled('latitude') ? (float) $request->input('latitude') : null;
        $userLon = $request->filled('longitude') ? (float) $request->input('longitude') : null;
        $locCheck = $pengaturan->checkLocationRadius($userLat, $userLon);

        if ($pengaturan->require_location && !$locCheck['allowed']) {
            return response()->json([
                'status' => 'error',
                'title' => 'Di Luar Radius Sekolah',
                'message' => $locCheck['message'],
                'speech_text' => 'Di luar radius sekolah.',
                'data' => [
                    'distance_meter' => $locCheck['distance_meter'],
                    'radius_meter' => $locCheck['radius_meter'],
                ],
            ], 422);
        }

        // 2. Cek apakah hari ini termasuk hari aktif belajar
        if (!$pengaturan->isHariAktif($now)) {
            return response()->json([
                'status' => 'warning',
                'title' => 'Bukan Hari Belajar',
                'message' => 'Hari ini bukan merupakan hari aktif belajar di jadwal sekolah.',
                'speech_text' => 'Bukan hari aktif sekolah.',
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
                'title' => 'Peserta Didik Tidak Ditemukan',
                'message' => "Kartu RFID atau QR [{$cleanId}] belum terdaftar dalam sistem database sekolah.",
                'speech_text' => 'Tidak terdaftar.',
            ], 404);
        }

        // 4. Cek Pembatasan Kompetensi Keahlian (Jurusan) yang Diizinkan Presensi
        $rb = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $siswa->rombongan_belajar_id)->first();
        if ($rb && !$pengaturan->isJurusanAktif($rb->jurusan_id)) {
            return response()->json([
                'status' => 'warning',
                'title' => 'Jurusan Tidak Aktif Presensi',
                'message' => 'Kompetensi Keahlian ' . ($rb->jurusan_id_str ?: 'peserta didik') . ' saat ini tidak dijadwalkan untuk presensi gerbang (misal: sedang PKL/Prakerin).',
                'speech_text' => 'Jurusan tidak aktif.',
            ], 422);
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

        // 6. Simpan Foto Snapshot Live Kamera — dilakukan SETELAH validasi duplikasi
        // agar foto tidak tersimpan ganda saat siswa scan ulang
        // (snapshotBase64 disimpan sementara, path baru dibuat saat benar-benar dibutuhkan)
        $hasSnapshot = !empty($snapshotBase64) && str_starts_with($snapshotBase64, 'data:image/');

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
                    'speech_text' => "{$siswa->nama}, sudah pulang.",
                    'data' => $this->formatSiswaResponseData($siswa, $presensi),
                ]);
            }

            $presensi->jam_pulang = $currentTime;
            $presensi->metode_pulang = $metodeScan;

            // Simpan snapshot HANYA saat presensi pulang baru dicatat
            if ($hasSnapshot) {
                try {
                    $snapshotPath = $this->saveSnapshotImage($snapshotBase64, $siswa->nisn ?: $siswa->peserta_didik_id, $now);
                } catch (\Throwable $e) {
                    // Snapshot error tidak memblokir pencatatan presensi
                }
            }
            if ($snapshotPath) {
                $presensi->foto_pulang = $snapshotPath;
            }

            // Status ketepatan pulang
            if ($currentTime < $jamPulangMulai) {
                $presensi->status_ketepatan_pulang = 'pulang_cepat';
                $messageDetail = "Presensi pulang berhasil (Pulang Cepat pada {$currentTime} WIB).";
                $speechGreeting = "Sampai jumpa {$siswa->nama}.";
            } else {
                $presensi->status_ketepatan_pulang = 'tepat_waktu';
                $messageDetail = "Presensi pulang tepat waktu pada {$currentTime} WIB.";
                $speechGreeting = "Sampai jumpa {$siswa->nama}.";
            }

            if ($userLat !== null && $userLon !== null) {
                $presensi->latitude = $userLat;
                $presensi->longitude = $userLon;
                $presensi->jarak_meter = $locCheck['distance_meter'];
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
                    'speech_text' => "{$siswa->nama}, sudah masuk.",
                    'data' => $this->formatSiswaResponseData($siswa, $presensi),
                ]);
            }

            $presensi->jam_masuk = $currentTime;
            $presensi->metode_masuk = $metodeScan;

            // Simpan snapshot HANYA saat presensi masuk baru dicatat
            if ($hasSnapshot) {
                try {
                    $snapshotPath = $this->saveSnapshotImage($snapshotBase64, $siswa->nisn ?: $siswa->peserta_didik_id, $now);
                } catch (\Throwable $e) {
                    // Snapshot error tidak memblokir pencatatan presensi
                }
            }
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
                    $speechGreeting = "{$siswa->nama}, terlambat {$diffMinutes} menit.";
                } else {
                    $presensi->status = 'H';
                    $presensi->status_ketepatan_masuk = 'tepat_waktu';
                    $presensi->menit_terlambat = 0;
                    $messageDetail = "Presensi masuk tepat waktu ({$currentTime} WIB).";
                    $speechGreeting = "Selamat pagi {$siswa->nama}.";
                }
            } else {
                $presensi->status = 'H';
                $presensi->status_ketepatan_masuk = 'tepat_waktu';
                $presensi->menit_terlambat = 0;
                $messageDetail = "Presensi masuk tepat waktu ({$currentTime} WIB).";
                $speechGreeting = "Selamat pagi {$siswa->nama}.";
            }

            if ($userLat !== null && $userLon !== null) {
                $presensi->latitude = $userLat;
                $presensi->longitude = $userLon;
                $presensi->jarak_meter = $locCheck['distance_meter'];
            }

            $presensi->save();
        }

        // Catat notifikasi transaksi personal untuk peserta didik
        try {
            $userPengguna = \App\Models\User::where('peserta_didik_id', $siswa->peserta_didik_id)->first();
            $jamStr = substr($actionType === 'pulang' ? $presensi->jam_pulang : $presensi->jam_masuk, 0, 5);
            $notifJudul = $actionType === 'pulang' ? 'Presensi Pulang Tercatat' : ($presensi->status === 'T' ? 'Presensi Masuk (Terlambat)' : 'Presensi Masuk Berhasil');
            $notifTipe = $actionType === 'pulang' ? 'info' : ($presensi->status === 'T' ? 'warning' : 'success');
            $notifIcon = $actionType === 'pulang' ? 'fa-solid fa-door-open' : ($presensi->status === 'T' ? 'fa-solid fa-clock-rotate-left' : 'fa-solid fa-calendar-check');
            $pesanNotif = $actionType === 'pulang'
                ? "Presensi pulang Anda berhasil dicatat pada pukul {$jamStr} WIB."
                : ($presensi->status === 'T'
                    ? "Presensi masuk Anda tercatat pada pukul {$jamStr} WIB (Terlambat {$presensi->menit_terlambat} menit)."
                    : "Presensi masuk Anda tercatat tepat waktu pada pukul {$jamStr} WIB.");

            \App\Models\NotifikasiTransaksi::create([
                'pengguna_id' => $userPengguna ? $userPengguna->pengguna_id : null,
                'peserta_didik_id' => $siswa->peserta_didik_id,
                'kategori' => 'presensi',
                'judul' => $notifJudul,
                'pesan' => $pesanNotif,
                'tipe' => $notifTipe,
                'icon' => $notifIcon,
                'url' => route('dashboard.peserta-didik.presensi.index'),
                'is_read' => false,
            ]);
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::warning('Gagal membuat notifikasi transaksi presensi: ' . $th->getMessage());
        }

        $formattedData = $this->formatSiswaResponseData($siswa, $presensi);

        \App\Services\RealtimeService::trigger('presensi.scanned', [
            'nama'            => $siswa->nama,
            'nisn'            => $siswa->nisn,
            'rombel'          => $siswa->nama_rombel ?? '-',
            'foto_url'        => $formattedData['foto_url'] ?? null,
            'jam'             => substr($actionType === 'pulang' ? $presensi->jam_pulang : $presensi->jam_masuk, 0, 5),
            'status'          => $presensi->status,
            'status_label'    => $formattedData['status_label'] ?? 'Hadir',
            'action'          => $actionType,
            'menit_terlambat' => $presensi->menit_terlambat ?? 0,
        ]);

        return response()->json([
            'status' => 'success',
            'action' => $actionType,
            'title' => 'Presensi Berhasil',
            'message' => $messageDetail,
            'speech_text' => $speechGreeting,
            'data' => $formattedData,
        ]);
    }

    /**
     * API Endpoint: 6 Data Presensi Terkini untuk Live Monitor Kiosk
     */
    public function getLiveLog(Request $request)
    {
        PresensiHarian::autoCloseUncheckedOut();
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
     * Dasbor Presensi Kelas untuk Guru Mapel & Wali Kelas
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
        $jamKe = $request->input('jam_ke', '');

        // Daftar Pembelajaran / Mata Pelajaran di Rombel Terpilih
        $pembelajaranList = collect();
        if ($selectedRombelId) {
            $pembelajaranList = DB::table('pembelajaran as p')
                ->leftJoin('gtk as g', 'p.ptk_id', '=', 'g.ptk_id')
                ->where('p.rombongan_belajar_id', $selectedRombelId)
                ->select(
                    'p.pembelajaran_id',
                    'p.nama_mata_pelajaran',
                    'p.ptk_id',
                    'p.jam_mengajar_per_minggu',
                    'g.nama as nama_guru'
                )
                ->orderBy('p.nama_mata_pelajaran', 'asc')
                ->get();
        }

        // Default mapel terpilih: prioritaskan mapel yang diampu oleh PTK jika guru login
        $defaultPembelajaranId = null;
        if ($ptkId) {
            $guruPembelajaran = $pembelajaranList->firstWhere('ptk_id', $ptkId);
            if ($guruPembelajaran) {
                $defaultPembelajaranId = $guruPembelajaran->pembelajaran_id;
            }
        }
        if (!$defaultPembelajaranId && $pembelajaranList->isNotEmpty()) {
            $defaultPembelajaranId = $pembelajaranList->first()->pembelajaran_id;
        }

        $selectedPembelajaranId = $request->input('pembelajaran_id', $defaultPembelajaranId);
        $selectedPembelajaran = $pembelajaranList->firstWhere('pembelajaran_id', $selectedPembelajaranId);

        // Ambil Siswa di Rombel Terpilih
        $siswaList = collect();
        $selectedRombel = null;

        if ($selectedRombelId) {
            $selectedRombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $selectedRombelId)->first();

            $siswaList = DB::table('peserta_didik as pd')
                ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
                // Konteks Presensi Gerbang / Harian Sekolah (READ-ONLY)
                ->leftJoin('presensi_harian as ph', function ($join) use ($tanggal) {
                    $join->on('pd.peserta_didik_id', '=', 'ph.peserta_didik_id')
                        ->where('ph.tanggal', '=', $tanggal);
                })
                // Presensi Mapel Terpisah untuk Guru KBM
                ->leftJoin('presensi_mapel as pm', function ($join) use ($tanggal, $selectedPembelajaranId, $selectedRombelId) {
                    $join->on('pd.peserta_didik_id', '=', 'pm.peserta_didik_id')
                        ->where('pm.tanggal', '=', $tanggal);
                    if ($selectedPembelajaranId) {
                        $join->where('pm.pembelajaran_id', '=', $selectedPembelajaranId);
                    } else {
                        $join->where('pm.rombongan_belajar_id', '=', $selectedRombelId);
                    }
                })
                ->where('pd.rombongan_belajar_id', $selectedRombelId)
                ->select(
                    'pd.peserta_didik_id',
                    'pd.nama',
                    'pd.nisn',
                    'pd.jenis_kelamin',
                    'pdm.foto_path',
                    'pdm.rfid_uid',
                    // Data Presensi Gerbang (Sekolah)
                    'ph.id as gerbang_presensi_id',
                    'ph.status as gerbang_status',
                    'ph.jam_masuk as gerbang_jam_masuk',
                    'ph.jam_pulang as gerbang_jam_pulang',
                    'ph.menit_terlambat as gerbang_menit_terlambat',
                    'ph.status_ketepatan_pulang as gerbang_status_pulang',
                    'ph.keterangan as gerbang_keterangan',
                    // Data Presensi Mapel
                    'pm.id as mapel_presensi_id',
                    'pm.status as mapel_status',
                    'pm.jam_ke as mapel_jam_ke',
                    'pm.keterangan as mapel_keterangan',
                    'pm.agenda_kelas_id'
                )
                ->orderBy('pd.nama', 'asc')
                ->get()
                ->map(function ($item) {
                    $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
                    return $item;
                });
        }

        // Rekap presensi mapel kelas hari ini
        $rekap = [
            'total' => $siswaList->count(),
            'hadir' => $siswaList->where('mapel_status', 'H')->count(),
            'terlambat' => $siswaList->where('mapel_status', 'T')->count(),
            'izin' => $siswaList->where('mapel_status', 'I')->count(),
            'sakit' => $siswaList->where('mapel_status', 'S')->count(),
            'alpha' => $siswaList->where('mapel_status', 'A')->count(),
            'belum' => $siswaList->whereNull('mapel_status')->count(),
        ];

        return view('dashboard.presensi.kelas', compact(
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
            'waliRombel'
        ));
    }

    /**
     * API: Update Cepat Status Kehadiran Siswa per Mapel (Guru KBM)
     */
    public function updateStatusKelas(Request $request)
    {
        $request->validate([
            'peserta_didik_id' => 'required|string',
            'tanggal' => 'required|date',
            'status' => 'required|string|in:H,T,I,S,A',
            'pembelajaran_id' => 'nullable|string',
            'rombongan_belajar_id' => 'nullable|string',
            'jam_ke' => 'nullable|string|max:20',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $user = session('user');
        $verifiedBy = is_array($user) ? ($user['nama'] ?? 'Guru Mapel') : ($user->nama ?? 'Guru Mapel');
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

        $pdId = $request->input('peserta_didik_id');
        $tanggal = $request->input('tanggal');
        $status = $request->input('status');
        $pembelajaranId = $request->input('pembelajaran_id');
        $rombelId = $request->input('rombongan_belajar_id');
        $jamKe = $request->input('jam_ke');
        $keterangan = $request->input('keterangan');

        $siswa = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
        if (!$siswa) {
            return response()->json(['status' => 'error', 'message' => 'Peserta didik tidak ditemukan.'], 404);
        }

        $pembelajaran = null;
        if ($pembelajaranId) {
            $pembelajaran = DB::table('pembelajaran')->where('pembelajaran_id', $pembelajaranId)->first();
        }

        $matchCondition = [
            'peserta_didik_id' => $pdId,
            'tanggal' => $tanggal,
        ];
        if ($pembelajaranId) {
            $matchCondition['pembelajaran_id'] = $pembelajaranId;
        } else {
            $matchCondition['rombongan_belajar_id'] = $rombelId ?: $siswa->rombongan_belajar_id;
        }

        $presensiMapel = PresensiMapel::updateOrCreate(
            $matchCondition,
            [
                'nisn' => $siswa->nisn,
                'rombongan_belajar_id' => $rombelId ?: ($pembelajaran?->rombongan_belajar_id ?: $siswa->rombongan_belajar_id),
                'pembelajaran_id' => $pembelajaranId ?: null,
                'ptk_id' => $pembelajaran?->ptk_id ?: $ptkId,
                'mata_pelajaran_id' => $pembelajaran?->mata_pelajaran_id,
                'nama_mata_pelajaran' => $pembelajaran?->nama_mata_pelajaran,
                'jam_ke' => $jamKe,
                'status' => $status,
                'keterangan' => $keterangan,
                'created_by' => $verifiedBy,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => "Status presensi {$siswa->nama} pada mapel " . ($pembelajaran?->nama_mata_pelajaran ?: 'ini') . " diubah ke " . (PresensiMapel::STATUS_LABELS[$status] ?? $status),
            'badge' => PresensiMapel::STATUS_BADGES[$status] ?? $status,
        ]);
    }

    /**
     * API: Tandai Siswa yang Belum Dicatat di Mapel sebagai Alpha
     */
    public function tandaiAlphaRombel(Request $request)
    {
        $request->validate([
            'rombongan_belajar_id' => 'required|string',
            'tanggal' => 'required|date',
            'pembelajaran_id' => 'nullable|string',
        ]);

        $user = session('user');
        $verifiedBy = is_array($user) ? ($user['nama'] ?? 'Guru Mapel') : ($user->nama ?? 'Guru Mapel');
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

        $rombelId = $request->input('rombongan_belajar_id');
        $tanggal = $request->input('tanggal');
        $pembelajaranId = $request->input('pembelajaran_id');

        $pembelajaran = null;
        if ($pembelajaranId) {
            $pembelajaran = DB::table('pembelajaran')->where('pembelajaran_id', $pembelajaranId)->first();
        }

        // Ambil semua siswa di rombel
        $siswaList = DB::table('peserta_didik')->where('rombongan_belajar_id', $rombelId)->get();

        $markedCount = 0;
        foreach ($siswaList as $siswa) {
            $query = PresensiMapel::where('peserta_didik_id', $siswa->peserta_didik_id)
                ->where('tanggal', $tanggal);
            if ($pembelajaranId) {
                $query->where('pembelajaran_id', $pembelajaranId);
            } else {
                $query->where('rombongan_belajar_id', $rombelId);
            }
            $exists = $query->exists();

            if (!$exists) {
                PresensiMapel::create([
                    'peserta_didik_id' => $siswa->peserta_didik_id,
                    'nisn' => $siswa->nisn,
                    'rombongan_belajar_id' => $rombelId,
                    'pembelajaran_id' => $pembelajaranId ?: null,
                    'ptk_id' => $pembelajaran?->ptk_id ?: $ptkId,
                    'mata_pelajaran_id' => $pembelajaran?->mata_pelajaran_id,
                    'nama_mata_pelajaran' => $pembelajaran?->nama_mata_pelajaran,
                    'tanggal' => $tanggal,
                    'status' => 'A',
                    'keterangan' => 'Alpha pada jam pelajaran',
                    'created_by' => $verifiedBy,
                ]);
                $markedCount++;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Sebanyak {$markedCount} peserta didik yang belum absen di mapel ini berhasil ditandai sebagai Alpha.",
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
            'latitude' => $presensi->latitude ? (float) $presensi->latitude : null,
            'longitude' => $presensi->longitude ? (float) $presensi->longitude : null,
            'jarak_meter' => $presensi->jarak_meter !== null ? (float) $presensi->jarak_meter : null,
        ];
    }
}
