<?php

namespace App\Http\Controllers;

use App\Models\NotifikasiTransaksi;
use App\Models\PresensiHarian;
use App\Services\QrCodeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PesertaDidikPresensiController extends Controller
{
    /**
     * Resolusi Data Siswa dari Sesi Login
     */
    protected function getSiswaFromSession()
    {
        $user = session('user');
        if (!$user) {
            return null;
        }

        $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
        $nisn = is_array($user) ? ($user['nisn'] ?? null) : ($user->nisn ?? null);

        if ($pdId) {
            return DB::table('peserta_didik as pd')
                ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
                ->leftJoin('gtk as wali', 'rb.ptk_id', '=', 'wali.ptk_id')
                ->where('pd.peserta_didik_id', $pdId)
                ->select(
                    'pd.*',
                    'rb.nama as nama_rombel',
                    'rb.tingkat_pendidikan_id',
                    'rb.jurusan_id_str',
                    'pdm.foto_path',
                    'pdm.rfid_uid',
                    'wali.nama as wali_nama',
                    'wali.nip as wali_nip',
                    'wali.nuptk as wali_nuptk'
                )
                ->first();
        } elseif ($nisn) {
            return DB::table('peserta_didik as pd')
                ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
                ->leftJoin('gtk as wali', 'rb.ptk_id', '=', 'wali.ptk_id')
                ->where('pd.nisn', $nisn)
                ->select(
                    'pd.*',
                    'rb.nama as nama_rombel',
                    'rb.tingkat_pendidikan_id',
                    'rb.jurusan_id_str',
                    'pdm.foto_path',
                    'pdm.rfid_uid',
                    'wali.nama as wali_nama',
                    'wali.nip as wali_nip',
                    'wali.nuptk as wali_nuptk'
                )
                ->first();
        }

        return null;
    }

    /**
     * Hitung rentang tanggal awal & akhir berdasarkan parameter periode
     */
    protected function resolveDateRange($periodeTipe, $bulan, $tahun, $semester, $tahunAjaran)
    {
        $startDate = null;
        $endDate = null;
        $labelPeriode = '';

        if ($periodeTipe === 'semester') {
            // Format tahun ajaran misal "2026/2027"
            $parts = explode('/', $tahunAjaran ?: date('Y') . '/' . (date('Y') + 1));
            $y1 = (int) ($parts[0] ?? date('Y'));
            $y2 = (int) ($parts[1] ?? ($y1 + 1));

            if ($semester == '1') {
                $startDate = "{$y1}-07-01";
                $endDate = "{$y1}-12-31";
                $labelPeriode = "Semester Ganjil {$y1}/{$y2} (Juli - Desember {$y1})";
            } else {
                $startDate = "{$y2}-01-01";
                $endDate = "{$y2}-06-30";
                $labelPeriode = "Semester Genap {$y1}/{$y2} (Januari - Juni {$y2})";
            }
        } elseif ($periodeTipe === 'tahun') {
            if (str_contains($tahunAjaran, '/')) {
                $parts = explode('/', $tahunAjaran);
                $y1 = (int) $parts[0];
                $y2 = (int) ($parts[1] ?? ($y1 + 1));
                $startDate = "{$y1}-07-01";
                $endDate = "{$y2}-06-30";
                $labelPeriode = "Tahun Ajaran {$y1}/{$y2} (Juli {$y1} - Juni {$y2})";
            } else {
                $y = (int) ($tahun ?: date('Y'));
                $startDate = "{$y}-01-01";
                $endDate = "{$y}-12-31";
                $labelPeriode = "Tahun Kalender {$y}";
            }
        } else {
            // Mode Bulan (Default)
            $b = str_pad((string) ($bulan ?: date('m')), 2, '0', STR_PAD_LEFT);
            $y = (int) ($tahun ?: date('Y'));
            $startCarbon = Carbon::createFromDate($y, (int) $b, 1)->startOfMonth();
            $endCarbon = Carbon::createFromDate($y, (int) $b, 1)->endOfMonth();

            $startDate = $startCarbon->toDateString();
            $endDate = $endCarbon->toDateString();
            $labelPeriode = "Bulan " . $startCarbon->translatedFormat('F Y');
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
            'label' => $labelPeriode,
        ];
    }

    /**
     * Tampilan Utama Modul Riwayat Presensi Peserta Didik
     */
    public function index(Request $request)
    {
        $siswa = $this->getSiswaFromSession();
        if (!$siswa) {
            return redirect()->route('dashboard.peserta-didik')->with('error', 'Biodata peserta didik tidak ditemukan.');
        }

        $siswa->foto_url = !empty($siswa->foto_path) ? asset('storage/' . ltrim($siswa->foto_path, '/')) : null;

        // Dynamic QR Token untuk Scan Cepat
        $timestamp = time();
        $secretKey = config('app.key') ?: 'sae_secret_salt';
        $qrPayload = 'SAE-QR:' . $siswa->nisn . ':' . $timestamp . ':' . substr(hash('sha256', $siswa->nisn . $timestamp . $secretKey), 0, 8);
        $dynamicQrUri = QrCodeService::generateDataUri($qrPayload, 180, 1);

        // Parameter Filter Periode
        $periodeTipe = $request->input('periode', 'bulan'); // 'bulan' | 'semester' | 'tahun'
        $bulan = $request->input('bulan', date('m'));
        $tahun = $request->input('tahun', date('Y'));
        $semester = $request->input('semester', (date('n') >= 7 ? '1' : '2'));
        
        $currentYear = (int) date('Y');
        $currentTa = (date('n') >= 7) 
            ? "{$currentYear}/" . ($currentYear + 1) 
            : ($currentYear - 1) . "/{$currentYear}";
        $tahunAjaran = $request->input('tahun_ajaran', $currentTa);

        $range = $this->resolveDateRange($periodeTipe, $bulan, $tahun, $semester, $tahunAjaran);

        // Query Data Presensi
        $query = PresensiHarian::where('peserta_didik_id', $siswa->peserta_didik_id)
            ->whereBetween('tanggal', [$range['start'], $range['end']]);

        // Rekapitulasi Statistik
        $allRecords = (clone $query)->get();
        $countHadir = $allRecords->where('status', 'H')->count();
        $countTerlambat = $allRecords->where('status', 'T')->count();
        $countIzin = $allRecords->where('status', 'I')->count();
        $countSakit = $allRecords->where('status', 'S')->count();
        $countDispen = $allRecords->where('status', 'D')->count();
        $countAlpha = $allRecords->where('status', 'A')->count();
        $totalRecord = $allRecords->count();
        $totalHadirFisik = $countHadir + $countTerlambat;
        $totalMenitTerlambat = $allRecords->where('status', 'T')->sum('menit_terlambat');

        $persenKehadiran = $totalRecord > 0 
            ? round((($totalHadirFisik + $countDispen) / $totalRecord) * 100, 1) 
            : 100.0;

        $stats = [
            'hadir' => $countHadir,
            'terlambat' => $countTerlambat,
            'izin' => $countIzin,
            'sakit' => $countSakit,
            'dispen' => $countDispen,
            'alpha' => $countAlpha,
            'total' => $totalRecord,
            'total_hadir' => $totalHadirFisik,
            'menit_terlambat' => $totalMenitTerlambat,
            'persen' => $persenKehadiran,
        ];

        // Filter Live Search & Per Page
        $q = trim((string) $request->input('q', ''));
        if ($q !== '') {
            $query->where(function ($sq) use ($q) {
                $sq->where('keterangan', 'like', "%{$q}%")
                   ->orWhere('status', 'like', "%{$q}%")
                   ->orWhere('metode_masuk', 'like', "%{$q}%")
                   ->orWhere('tanggal', 'like', "%{$q}%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100])) {
            $perPage = 15;
        }

        // Paginasi Data
        $logs = (clone $query)->orderBy('tanggal', 'desc')->paginate($perPage)->withQueryString();

        // RBAC Permissions
        $userSession = session('user');
        $userRole = is_array($userSession) ? ($userSession['role'] ?? '') : ($userSession->role ?? '');
        $canCreate = \App\Models\RolePermission::canAccess($userSession ?: $userRole, 'menu_riwayat_rfid', 'create') || $userRole === 'peserta_didik';
        $canRead = \App\Models\RolePermission::canAccess($userSession ?: $userRole, 'menu_riwayat_rfid', 'read') || $userRole === 'peserta_didik';

        return view('dashboard.peserta-didik-presensi', compact(
            'siswa',
            'dynamicQrUri',
            'periodeTipe',
            'bulan',
            'tahun',
            'semester',
            'tahunAjaran',
            'range',
            'stats',
            'logs',
            'q',
            'perPage',
            'canCreate',
            'canRead'
        ));
    }

    /**
     * Cetak Laporan Rekapitulasi Presensi Peserta Didik (Resmi Ber-Kop Sekolah)
     */
    public function cetak(Request $request)
    {
        $siswa = $this->getSiswaFromSession();
        if (!$siswa) {
            return redirect()->route('dashboard.peserta-didik')->with('error', 'Biodata peserta didik tidak ditemukan.');
        }

        // Parameter Filter Periode
        $periodeTipe = $request->input('periode', 'bulan');
        $bulan = $request->input('bulan', date('m'));
        $tahun = $request->input('tahun', date('Y'));
        $semester = $request->input('semester', (date('n') >= 7 ? '1' : '2'));

        $currentYear = (int) date('Y');
        $currentTa = (date('n') >= 7) 
            ? "{$currentYear}/" . ($currentYear + 1) 
            : ($currentYear - 1) . "/{$currentYear}";
        $tahunAjaran = $request->input('tahun_ajaran', $currentTa);

        $range = $this->resolveDateRange($periodeTipe, $bulan, $tahun, $semester, $tahunAjaran);

        // Ambil Data Profil Sekolah & Kop Surat Resmi
        $sekolah = DB::table('sekolah')->first();
        $sekolahMeta = \App\Models\SekolahMeta::first();

        // Ambil Kepala Sekolah
        $kepsek = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->select('nama', 'nip')
            ->first();

        // Ambil Seluruh Baris Presensi
        $logs = PresensiHarian::where('peserta_didik_id', $siswa->peserta_didik_id)
            ->whereBetween('tanggal', [$range['start'], $range['end']])
            ->orderBy('tanggal', 'asc')
            ->get();

        // Rekapitulasi Statistik
        $countHadir = $logs->where('status', 'H')->count();
        $countTerlambat = $logs->where('status', 'T')->count();
        $countIzin = $logs->where('status', 'I')->count();
        $countSakit = $logs->where('status', 'S')->count();
        $countDispen = $logs->where('status', 'D')->count();
        $countAlpha = $logs->where('status', 'A')->count();
        $totalRecord = $logs->count();
        $totalHadirFisik = $countHadir + $countTerlambat;
        $totalMenitTerlambat = $logs->where('status', 'T')->sum('menit_terlambat');

        $persenKehadiran = $totalRecord > 0 
            ? round((($totalHadirFisik + $countDispen) / $totalRecord) * 100, 1) 
            : 100.0;

        $stats = [
            'hadir' => $countHadir,
            'terlambat' => $countTerlambat,
            'izin' => $countIzin,
            'sakit' => $countSakit,
            'dispen' => $countDispen,
            'alpha' => $countAlpha,
            'total' => $totalRecord,
            'total_hadir' => $totalHadirFisik,
            'menit_terlambat' => $totalMenitTerlambat,
            'persen' => $persenKehadiran,
        ];

        return view('dashboard.peserta-didik-presensi-cetak', compact(
            'siswa',
            'sekolah',
            'sekolahMeta',
            'kepsek',
            'periodeTipe',
            'bulan',
            'tahun',
            'semester',
            'tahunAjaran',
            'range',
            'stats',
            'logs'
        ));
    }

    /**
     * Tandai Semua Notifikasi Transaksi Pengguna Menjadi Sudah Dibaca (AJAX)
     */
    public function markAllTransactionsRead(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Silakan login terlebih dahulu.'], 401);
        }

        $userId = (string) (is_array($user) ? ($user['pengguna_id'] ?? ($user['id'] ?? '')) : ($user->pengguna_id ?? ($user->id ?? '')));
        $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);

        NotifikasiTransaksi::forUser($userId, $pdId)->unread()->update(['is_read' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Semua notifikasi transaksi telah ditandai sudah dibaca.',
            'unread_count' => 0,
        ]);
    }

    /**
     * Tandai Satu Notifikasi Transaksi Menjadi Sudah Dibaca (AJAX)
     */
    public function markTransactionRead(Request $request, $id)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Silakan login terlebih dahulu.'], 401);
        }

        $userId = (string) (is_array($user) ? ($user['pengguna_id'] ?? ($user['id'] ?? '')) : ($user->pengguna_id ?? ($user->id ?? '')));
        $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);

        $notif = NotifikasiTransaksi::forUser($userId, $pdId)->where('id', $id)->first();
        if ($notif) {
            $notif->is_read = true;
            $notif->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notifikasi ditandai dibaca.',
        ]);
    }
}
