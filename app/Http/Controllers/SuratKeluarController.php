<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\RolePermission;
use App\Models\Persuratan;
use App\Models\SuratKeteranganPd;
use App\Models\PesertaDidik;
use App\Models\Gtk;
use App\Models\Sekolah;
use Carbon\Carbon;
use App\Services\PersuratanHddService;
use App\Services\QrCodeService;

class SuratKeluarController extends Controller
{
    private function checkAuth()
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }
        return null;
    }

    /**
     * Tampilan Buku Agenda Surat Keluar & Surat Keterangan Siswa & Surat Perintah Tugas (SPT)
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_keluar', 'read')) {
            abort(403, 'Akses ke modul surat keluar tidak diizinkan.');
        }

        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_surat_keluar', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_surat_keluar', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_surat_keluar', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_surat_keluar', 'delete');

        // Statistik Surat Keluar
        $curMonth = date('Y-m');
        $stats = [
            'total'           => Persuratan::where('jenis_surat', 'keluar')->count(),
            'surat_keterangan'=> SuratKeteranganPd::count(),
            'spt'             => DB::table('gtk_spt')->count(),
            'bulan_ini'       => Persuratan::where('jenis_surat', 'keluar')->where('tanggal_surat', 'like', "{$curMonth}%")->count(),
            'selesai'         => Persuratan::where('jenis_surat', 'keluar')->where('status', 'selesai')->count(),
        ];

        $q          = trim($request->get('q', ''));
        $tab        = $request->get('tab', 'keluar'); // 'keluar', 'keterangan', atau 'spt'
        $status     = $request->get('status', '');
        $kodeIndeks = $request->get('kode_indeks', '');
        $sort       = $request->get('sort', 'tanggal_surat');
        $sortDir    = strtolower($request->get('sort_dir', $request->get('dir', 'desc'))) === 'asc' ? 'asc' : 'desc';
        $perPageVal = $request->get('perPage', $request->get('per_page', '25'));
        $perPage    = in_array($perPageVal, ['10', '15', '25', '50', '100']) ? (int)$perPageVal : 25;

        // Master GTK untuk dropdown / referensi SPT
        $allGtk = Gtk::orderBy('nama', 'asc')->get(['ptk_id', 'nama', 'nuptk', 'nik', 'nip', 'jenis_ptk_id_str']);

        // Query berdasarkan Tab
        if ($tab === 'spt') {
            $sptQuery = DB::table('gtk_spt');
            if ($q !== '') {
                $sptQuery->where(function ($b) use ($q) {
                    $b->where('nomor_spt', 'like', "%{$q}%")
                      ->orWhere('nama_kegiatan', 'like', "%{$q}%")
                      ->orWhere('lokasi_tujuan', 'like', "%{$q}%")
                      ->orWhere('dasar_penugasan', 'like', "%{$q}%");
                });
            }
            $items = $sptQuery->orderBy('tanggal_berangkat', 'desc')->paginate($perPage)->withQueryString();
        } else {
            $query = Persuratan::where('jenis_surat', 'keluar');

            if ($tab === 'keterangan') {
                $query->whereIn('nomor_surat', SuratKeteranganPd::pluck('nomor_surat'));
            }

            if ($q !== '') {
                $query->where(function ($b) use ($q) {
                    $b->where('nomor_surat', 'like', "%{$q}%")
                      ->orWhere('perihal', 'like', "%{$q}%")
                      ->orWhere('tujuan_penerima', 'like', "%{$q}%")
                      ->orWhere('keterangan', 'like', "%{$q}%")
                      ->orWhere('kode_indeks', 'like', "%{$q}%");
                });
            }

            if ($status !== '') {
                $query->where('status', $status);
            }

            if ($kodeIndeks !== '') {
                $query->where('kode_indeks', $kodeIndeks);
            }

            $allowedSorts = ['nomor_surat', 'tanggal_surat', 'status', 'created_at', 'tujuan_penerima'];
            if (in_array($sort, $allowedSorts)) {
                $query->orderBy($sort, $sortDir);
            } else {
                $query->orderBy('tanggal_surat', 'desc');
            }

            $items = $query->paginate($perPage)->withQueryString();
        }

        // Master Indeks Klasifikasi Surat
        $indeksList = DB::table('ref_indeks_surat')->where('is_active', true)->orderBy('kode')->get();

        // Cek status HDD
        $hddStatus = PersuratanHddService::checkStatus();

        // Format nomor surat saran untuk form
        $suggestedNumber = PersuratanHddService::generateNomorSuratKeluar('KPG.11.01');
        $suggestedKetNumber = PersuratanHddService::generateNomorSuratKeterangan('KS.02.23');

        return view('dashboard.persuratan.keluar', compact(
            'stats',
            'items',
            'allGtk',
            'q',
            'tab',
            'status',
            'kodeIndeks',
            'sort',
            'sortDir',
            'perPage',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete',
            'indeksList',
            'hddStatus',
            'suggestedNumber',
            'suggestedKetNumber'
        ));
    }

    /**
     * AJAX Helper: Generate Preview Nomor Surat Otomatis Berdasarkan Indeks
     */
    public function getNextNumber(Request $request): JsonResponse
    {
        $type = $request->get('type', 'keluar');
        $kodeIndeks = trim($request->get('kode_indeks', ($type === 'keterangan' ? 'KS.02.23' : 'KPG.11.01')));
        if ($type === 'keterangan') {
            $nomor = PersuratanHddService::generateNomorSuratKeterangan($kodeIndeks);
        } else {
            $nomor = PersuratanHddService::generateNomorSuratKeluar($kodeIndeks);
        }

        return response()->json([
            'success' => true,
            'nomor_surat' => $nomor,
            'kode_indeks' => $kodeIndeks,
            'type' => $type,
        ]);
    }

    /**
     * AJAX Live Search Siswa dari Database Dapodik
     */
    public function searchSiswa(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $siswa = DB::table('peserta_didik as pd')
            ->leftJoin('anggota_rombel as ar', 'pd.peserta_didik_id', '=', 'ar.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where(function ($b) use ($q) {
                $b->where('pd.nama', 'like', "%{$q}%")
                  ->orWhere('pd.nisn', 'like', "%{$q}%")
                  ->orWhere('pd.nipd', 'like', "%{$q}%");
            })
            ->select(
                'pd.peserta_didik_id',
                'pd.nama',
                'pd.nisn',
                'pd.nipd',
                'pd.tempat_lahir',
                'pd.tanggal_lahir',
                'pd.nama_ayah',
                'pd.nama_ibu',
                'rb.nama as rombel_nama',
                'rb.jurusan_id_str'
            )
            ->distinct()
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'results' => $siswa,
        ]);
    }

    /**
     * Catat Surat Keluar Umum Baru + Simpan Berkas Fisik ke HDD
     */
    public function store(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_keluar', 'create')) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'kode_indeks' => 'required|string|max:50',
            'perihal' => 'required|string|max:255',
            'tujuan_penerima' => 'required|string|max:200',
            'tanggal_surat' => 'required|date',
            'nomor_surat' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:draf,selesai,diarsipkan',
            'keterangan' => 'nullable|string|max:1000',
            'file_arsip' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600',
        ]);

        // Jika nomor surat tidak diisi manual, generate otomatis dari sistem
        $nomorSurat = trim($request->nomor_surat);
        $isAutoNumber = empty($nomorSurat);

        if ($isAutoNumber) {
            $nomorSurat = PersuratanHddService::generateNomorSuratKeluar($request->kode_indeks);
        }

        $filePath = null;
        $fileSize = null;
        $fileNameOriginal = null;

        if ($request->hasFile('file_arsip')) {
            $uploaded = PersuratanHddService::storeFile($request->file('file_arsip'), 'keluar');
            $filePath = $uploaded['relative_path'];
            $fileSize = $uploaded['file_size'];
            $fileNameOriginal = $uploaded['file_name_original'];
        }

        $userName = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Petugas')) : ($user->nama ?? ($user->name ?? 'Petugas'));

        $persuratan = Persuratan::create([
            'nomor_surat' => $nomorSurat,
            'kode_indeks' => $request->kode_indeks,
            'jenis_surat' => 'keluar',
            'perihal' => trim($request->perihal),
            'pengirim_asal' => 'SMK SAE',
            'tujuan_penerima' => trim($request->tujuan_penerima),
            'tanggal_surat' => $request->tanggal_surat,
            'tanggal_diterima' => null,
            'status' => $request->status ?: 'selesai',
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'file_name_original' => $fileNameOriginal,
            'keterangan' => $request->keterangan ? trim($request->keterangan) : null,
            'created_by' => $userName,
        ]);

        // Update counter penomoran otomatis
        if ($isAutoNumber) {
            PersuratanHddService::incrementNomorCounter('keluar');
        }

        return redirect()->route('persuratan.keluar.index')->with('success', "Surat keluar nomor {$persuratan->nomor_surat} berhasil dicatat.");
    }

    /**
     * Tampilkan Detail Surat Keluar (JSON untuk Modal)
     */
    public function show($id): JsonResponse
    {
        $surat = Persuratan::where('jenis_surat', 'keluar')->findOrFail($id);
        $suratKet = SuratKeteranganPd::where('nomor_surat', $surat->nomor_surat)->first();

        return response()->json([
            'success' => true,
            'data' => $surat,
            'surat_keterangan' => $suratKet,
            'has_file' => !empty($surat->file_path),
            'file_url' => !empty($surat->file_path) ? route('persuratan.dokumen.view', $surat->id) : null,
            'file_download_url' => !empty($surat->file_path) ? route('persuratan.dokumen.download', $surat->id) : null,
        ]);
    }

    /**
     * Update Surat Keluar
     */
    public function update(Request $request, $id)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_keluar', 'update')) {
            abort(403, 'Akses ditolak.');
        }

        $surat = Persuratan::where('jenis_surat', 'keluar')->findOrFail($id);

        $request->validate([
            'nomor_surat' => 'required|string|max:100',
            'kode_indeks' => 'required|string|max:50',
            'perihal' => 'required|string|max:255',
            'tujuan_penerima' => 'required|string|max:200',
            'tanggal_surat' => 'required|date',
            'status' => 'nullable|string|in:draf,selesai,diarsipkan',
            'keterangan' => 'nullable|string|max:1000',
            'file_arsip' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600',
        ]);

        $data = [
            'nomor_surat' => trim($request->nomor_surat),
            'kode_indeks' => $request->kode_indeks,
            'perihal' => trim($request->perihal),
            'tujuan_penerima' => trim($request->tujuan_penerima),
            'tanggal_surat' => $request->tanggal_surat,
            'status' => $request->status ?: $surat->status,
            'keterangan' => $request->keterangan ? trim($request->keterangan) : null,
        ];

        if ($request->hasFile('file_arsip')) {
            if ($surat->file_path) {
                PersuratanHddService::deleteFile($surat->file_path);
            }

            $uploaded = PersuratanHddService::storeFile($request->file('file_arsip'), 'keluar');
            $data['file_path'] = $uploaded['relative_path'];
            $data['file_size'] = $uploaded['file_size'];
            $data['file_name_original'] = $uploaded['file_name_original'];
        }

        $surat->update($data);

        return redirect()->route('persuratan.keluar.index')->with('success', 'Data surat keluar berhasil diperbarui.');
    }

    /**
     * Hapus Surat Keluar + Hapus Berkas Fisik di HDD
     */
    public function destroy($id)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_keluar', 'delete')) {
            abort(403, 'Akses ditolak.');
        }

        $surat = Persuratan::where('jenis_surat', 'keluar')->findOrFail($id);

        // Hapus berkas fisik di HDD
        if ($surat->file_path) {
            PersuratanHddService::deleteFile($surat->file_path);
        }

        // Hapus surat keterangan jika ada
        SuratKeteranganPd::where('nomor_surat', $surat->nomor_surat)->delete();

        $surat->delete();

        return redirect()->route('persuratan.keluar.index')->with('success', 'Surat keluar beserta arsip dokumennya berhasil dihapus.');
    }

    /**
     * Pembuatan Otomatis Surat Keterangan Siswa Aktif
     */
    public function suratKeteranganStore(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_keluar', 'create')) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'peserta_didik_id' => 'required|string|exists:peserta_didik,peserta_didik_id',
            'kode_indeks' => 'nullable|string|max:50',
            'keperluan' => 'required|string|max:255',
            'tanggal_surat' => 'nullable|date',
            'nomor_surat' => 'nullable|string|max:100',
        ]);

        $siswa = PesertaDidik::findOrFail($request->peserta_didik_id);
        $kodeIndeks = trim($request->get('kode_indeks', 'KS.02.23')) ?: 'KS.02.23';

        // Otomatis tentukan nomor surat mengikuti indeks
        $nomorSurat = trim($request->nomor_surat);
        $isAuto = empty($nomorSurat);
        if ($isAuto) {
            $nomorSurat = PersuratanHddService::generateNomorSuratKeterangan($kodeIndeks);
        }

        // Cari Kepala Sekolah
        $kepsek = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        // Generate UUID unik untuk verifikasi dokumen via QR Code publik
        $docId = 'DOC-' . strtoupper(Str::random(10));
        $qrVerifyUrl = url('/v/doc/' . $docId);

        $userName = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Petugas')) : ($user->nama ?? ($user->name ?? 'Petugas'));

        $suratKet = SuratKeteranganPd::create([
            'nomor_surat' => $nomorSurat,
            'peserta_didik_id' => $siswa->peserta_didik_id,
            'jenis_surat' => 'siswa_aktif',
            'keperluan' => trim($request->keperluan),
            'tanggal_surat' => $request->tanggal_surat ?: date('Y-m-d'),
            'penandatangan_ptk_id' => $kepsek?->ptk_id,
            'penandatangan_nama' => $kepsek?->nama ?: 'Kepala Sekolah',
            'penandatangan_jabatan' => 'Kepala Sekolah',
            'doc_id' => $docId,
            'qrcode_url' => $qrVerifyUrl,
            'created_by' => $userName,
        ]);

        // Catat otomatis ke buku agenda surat keluar
        Persuratan::create([
            'nomor_surat' => $nomorSurat,
            'kode_indeks' => $kodeIndeks,
            'jenis_surat' => 'keluar',
            'perihal' => "Surat Keterangan Siswa Aktif: {$siswa->nama} (NISN: {$siswa->nisn})",
            'pengirim_asal' => 'SMK SAE',
            'tujuan_penerima' => trim($request->keperluan),
            'tanggal_surat' => $suratKet->tanggal_surat,
            'status' => 'selesai',
            'keterangan' => "Penerbitan Surat Keterangan Aktif Siswa a.n. {$siswa->nama} untuk keperluan: {$request->keperluan}. Indeks: {$kodeIndeks}. Doc ID: {$docId}",
            'created_by' => $userName,
        ]);

        if ($isAuto) {
            PersuratanHddService::incrementNomorCounter('keterangan');
        }

        return redirect()->route('persuratan.keluar.index', ['tab' => 'keterangan'])
            ->with('success', "Surat Keterangan Aktif Siswa nomor {$nomorSurat} berhasil diterbitkan.")
            ->with('cetak_id', $suratKet->id);
    }

    /**
     * Cetak Lembar Resmi Surat Keterangan Siswa Aktif Ber-Kop Sekolah + QR Code
     */
    public function suratKeteranganCetak($id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_keluar', 'read')) {
            abort(403, 'Akses ditolak.');
        }

        $suratKet = SuratKeteranganPd::findOrFail($id);
        $siswa = PesertaDidik::where('peserta_didik_id', $suratKet->peserta_didik_id)->firstOrFail();
        $sekolah = DB::table('sekolah')->first();
        $sekolahMeta = \App\Models\SekolahMeta::first();

        $anggotaRombel = DB::table('anggota_rombel as ar')
            ->join('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('ar.peserta_didik_id', $siswa->peserta_didik_id)
            ->select('rb.nama as rombel_nama', 'rb.tingkat_pendidikan_id_str', 'rb.jurusan_id_str')
            ->first();

        $rombelNama = $anggotaRombel?->rombel_nama ?: 'Kelas X / XI / XII';
        $jurusanNama = $anggotaRombel?->jurusan_id_str ?: 'Semua Program Keahlian';

        $curYear = (int) date('Y');
        $tahunAjaran = (date('n') >= 7) ? "{$curYear}/" . ($curYear + 1) : ($curYear - 1) . "/{$curYear}";

        $kepsek = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        // QR Code verifikasi publik
        $qrVerifyUrl = url('/v/doc/' . $suratKet->doc_id);
        $qrUri = QrCodeService::generateDataUri($qrVerifyUrl, 140, 1);

        $orientasi = 'portrait';

        return view('dashboard.persuratan.cetak-surat-keterangan', compact(
            'suratKet',
            'siswa',
            'sekolah',
            'sekolahMeta',
            'orientasi',
            'rombelNama',
            'jurusanNama',
            'tahunAjaran',
            'kepsek',
            'qrUri'
        ));
    }

    /**
     * Render / Preview Berkas Dokumen dari HDD
     */
    public function viewDokumen($id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_masuk', 'read') && !RolePermission::canAccess($user ?: $role, 'menu_surat_keluar', 'read') && !RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'read')) {
            abort(403, 'Akses ditolak.');
        }

        $surat = Persuratan::findOrFail($id);
        if (empty($surat->file_path)) {
            abort(404, 'Dokumen surat ini tidak memiliki lampiran berkas.');
        }

        return PersuratanHddService::streamFile($surat->file_path);
    }

    /**
     * Unduh Berkas Dokumen dari HDD
     */
    public function downloadDokumen($id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_masuk', 'read') && !RolePermission::canAccess($user ?: $role, 'menu_surat_keluar', 'read') && !RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'read')) {
            abort(403, 'Akses ditolak.');
        }

        $surat = Persuratan::findOrFail($id);
        if (empty($surat->file_path)) {
            abort(404, 'Dokumen surat ini tidak memiliki lampiran berkas.');
        }

        return PersuratanHddService::downloadFile($surat->file_path, $surat->file_name_original);
    }

    /**
     * Simpan Surat Perintah Tugas (SPT) GTK
     */
    public function storeSpt(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_keluar', 'create')) {
            abort(403, 'Akses penerbitan SPT tidak diizinkan.');
        }

        $request->validate([
            'nama_kegiatan' => 'required|string|max:255',
            'lokasi_tujuan' => 'required|string|max:255',
            'tanggal_berangkat' => 'required|date',
            'tanggal_kembali' => 'required|date|after_or_equal:tanggal_berangkat',
            'beban_anggaran' => 'required|string|max:50',
            'daftar_ptk_id' => 'required|array|min:1',
            'dasar_penugasan' => 'nullable|string|max:1000',
        ], [
            'nama_kegiatan.required' => 'Nama kegiatan penugasan wajib diisi.',
            'lokasi_tujuan.required' => 'Lokasi tujuan penugasan wajib diisi.',
            'daftar_ptk_id.required' => 'Pilih minimal satu GTK yang ditugaskan.',
        ]);

        $start = Carbon::parse($request->tanggal_berangkat);
        $end = Carbon::parse($request->tanggal_kembali);
        $lamaHari = $start->diffInDays($end) + 1;

        // Auto generate nomor SPT
        $year = date('Y');
        $countThisYear = DB::table('gtk_spt')->whereYear('tanggal_berangkat', $year)->count() + 1;
        $nomorSpt = sprintf('800/SPT/%s/%03d', $year, $countThisYear);

        $kepsek = Gtk::where(function ($q) {
            $q->where('jenis_ptk_id_str', 'like', '%kepala sekolah%')
              ->orWhere('jabatan_ptk', 'like', '%kepala sekolah%');
        })->first();

        DB::table('gtk_spt')->insert([
            'nomor_spt' => $nomorSpt,
            'dasar_penugasan' => $request->dasar_penugasan,
            'nama_kegiatan' => $request->nama_kegiatan,
            'lokasi_tujuan' => $request->lokasi_tujuan,
            'tanggal_berangkat' => $request->tanggal_berangkat,
            'tanggal_kembali' => $request->tanggal_kembali,
            'lama_hari' => $lamaHari,
            'beban_anggaran' => $request->beban_anggaran,
            'pejabat_penandatangan_ptk_id' => $kepsek?->ptk_id,
            'pejabat_nama' => $kepsek?->nama ?? 'Kepala Sekolah',
            'pejabat_jabatan' => 'Kepala Sekolah',
            'daftar_ptk_id' => json_encode($request->daftar_ptk_id, JSON_UNESCAPED_UNICODE),
            'status' => 'disetujui',
            'created_by' => session('user')['nama'] ?? 'Tendik Persuratan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.persuratan.keluar.index', ['tab' => 'spt'])
            ->with('success', "Surat Perintah Tugas (SPT) {$nomorSpt} berhasil diterbitkan.");
    }

    /**
     * Hapus Surat Perintah Tugas (SPT)
     */
    public function deleteSpt($id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_keluar', 'delete')) {
            abort(403, 'Akses penghapusan SPT tidak diizinkan.');
        }

        DB::table('gtk_spt')->where('id', $id)->delete();

        return redirect()->route('dashboard.persuratan.keluar.index', ['tab' => 'spt'])
            ->with('success', 'Surat Perintah Tugas berhasil dihapus.');
    }

    /**
     * Cetak Surat Perintah Tugas (SPT) Resmi Format A4 Ber-KOP
     */
    public function cetakSpt($id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_keluar', 'read')) {
            abort(403, 'Akses pencetakan SPT tidak diizinkan.');
        }

        $spt = DB::table('gtk_spt')->where('id', $id)->first();
        if (!$spt) {
            abort(404, 'Data SPT tidak ditemukan.');
        }

        $ptkIds = json_decode($spt->daftar_ptk_id ?? '[]', true) ?: [];
        $gtkList = Gtk::whereIn('ptk_id', $ptkIds)->orderBy('nama', 'asc')->get();

        $sekolah = Sekolah::first();
        $sekolahMeta = \App\Models\SekolahMeta::first();
        $kepsek = Gtk::where(function ($q) {
            $q->where('jenis_ptk_id_str', 'like', '%kepala sekolah%')
              ->orWhere('jabatan_ptk', 'like', '%kepala sekolah%');
        })->first();

        return view('dashboard.persuratan.cetak-spt', compact(
            'spt',
            'gtkList',
            'sekolah',
            'sekolahMeta',
            'kepsek'
        ));
    }
}
