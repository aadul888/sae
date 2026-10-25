<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\RolePermission;
use App\Models\Persuratan;
use App\Models\SuratKeteranganPd;
use App\Models\PesertaDidik;
use App\Models\Gtk;
use App\Models\Sekolah;
use App\Services\PersuratanHddService;
use App\Services\SuratKeluarService;
use App\Services\SuratKeteranganService;
use App\Services\SuratSptService;
use App\Services\SuratTemplateService;
use App\Services\SuratAgendaEkspedisiService;

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
     * Tampilan Buku Agenda Surat Keluar (Terkoordinir: Surat Umum, SKET, SPT, Autocrat, & Ekspedisi)
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

        $viewData = SuratKeluarService::buildIndexData($request);
        $allGtk = collect();

        return view('dashboard.persuratan.keluar', array_merge($viewData, compact(
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete',
            'allGtk'
        )));
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
            'kode_indeks'     => 'required|string|max:50',
            'perihal'         => 'required|string|max:255',
            'tujuan_penerima' => 'required|string|max:200',
            'tanggal_surat'   => 'required|date',
            'nomor_surat'     => 'nullable|string|max:100',
            'sarpras_aset_id' => 'nullable|integer|exists:sarpras_aset,id',
            'status'          => 'nullable|string|in:draf,selesai,diarsipkan',
            'keterangan'      => 'nullable|string|max:1000',
            'file_arsip'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600',
        ]);

        $persuratan = SuratKeluarService::store($request, $user);

        return redirect()->route('dashboard.persuratan.keluar.index')->with('success', "Surat keluar nomor {$persuratan->nomor_surat} berhasil dicatat.");
    }

    /**
     * Tampilkan Detail Surat Keluar (JSON untuk Modal)
     */
    public function show($id): JsonResponse
    {
        $surat = Persuratan::where('jenis_surat', 'keluar')->findOrFail($id);
        $suratKet = SuratKeteranganPd::where('nomor_surat', $surat->nomor_surat)->first();

        return response()->json([
            'success'           => true,
            'data'              => $surat,
            'surat_keterangan'  => $suratKet,
            'has_file'          => !empty($surat->file_path),
            'file_url'          => !empty($surat->file_path) ? route('dashboard.persuratan.dokumen.view', $surat->id) : null,
            'file_download_url' => !empty($surat->file_path) ? route('dashboard.persuratan.dokumen.download', $surat->id) : null,
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
            'nomor_surat'     => 'required|string|max:100',
            'kode_indeks'     => 'required|string|max:50',
            'perihal'         => 'required|string|max:255',
            'tujuan_penerima' => 'required|string|max:200',
            'tanggal_surat'   => 'required|date',
            'sarpras_aset_id' => 'nullable|integer|exists:sarpras_aset,id',
            'status'          => 'nullable|string|in:draf,selesai,diarsipkan',
            'keterangan'      => 'nullable|string|max:1000',
            'file_arsip'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600',
        ]);

        SuratKeluarService::update($request, $surat);

        return redirect()->route('dashboard.persuratan.keluar.index')->with('success', 'Data surat keluar berhasil diperbarui.');
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
        SuratKeluarService::destroy($surat);

        return redirect()->route('dashboard.persuratan.keluar.index')->with('success', 'Surat keluar beserta arsip dokumennya berhasil dihapus.');
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
            'jenis_surat'      => 'nullable|string|in:siswa_aktif,kelakuan_baik,panggilan_ortu,rekomendasi',
            'kode_indeks'      => 'nullable|string|max:50',
            'keperluan'        => 'required|string|max:255',
            'tanggal_surat'    => 'nullable|date',
            'nomor_surat'      => 'nullable|string|max:100',
            'tanggal_agenda'   => 'nullable|date',
            'waktu_agenda'     => 'nullable|string|max:50',
            'tempat_agenda'    => 'nullable|string|max:100',
            'menghadap_agenda' => 'nullable|string|max:100',
            'catatan_khusus'   => 'nullable|string|max:1000',
        ]);

        $res = SuratKeteranganService::store($request, $user);

        return redirect()->route('dashboard.persuratan.keluar.index')
            ->with('success', "{$res['jenisLabel']} nomor {$res['nomorSurat']} berhasil diterbitkan.")
            ->with('cetak_id', $res['suratKet']->id);
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

        $data = SuratKeteranganService::buildCetakData($id);

        return view('dashboard.persuratan.cetak-surat-keterangan', $data);
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
            'nama_kegiatan'     => 'required|string|max:255',
            'lokasi_tujuan'     => 'required|string|max:255',
            'tanggal_berangkat' => 'required|date',
            'tanggal_kembali'   => 'required|date|after_or_equal:tanggal_berangkat',
            'beban_anggaran'    => 'required|string|max:50',
            'daftar_ptk_id'     => 'required|array|min:1',
            'dasar_penugasan'   => 'nullable|string|max:1000',
        ], [
            'nama_kegiatan.required' => 'Nama kegiatan penugasan wajib diisi.',
            'lokasi_tujuan.required' => 'Lokasi tujuan penugasan wajib diisi.',
            'daftar_ptk_id.required' => 'Pilih minimal satu GTK yang ditugaskan.',
        ]);

        $res = SuratSptService::store($request, $user);

        return redirect()->route('dashboard.persuratan.keluar.index')
            ->with('success', "Surat Perintah Tugas (SPT) {$res['nomorSpt']} berhasil diterbitkan.");
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

        SuratSptService::delete($id);

        return redirect()->route('dashboard.persuratan.keluar.index')
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

        $data = SuratSptService::buildCetakData($id);

        return view('dashboard.persuratan.cetak-spt', $data);
    }

    /**
     * Simpan Surat Keluar dari Template (Autocrat-Style)
     */
    public function storeTemplate(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_keluar', 'create')) {
            abort(403, 'Akses ditolak: Anda tidak memiliki hak akses menerbitkan surat.');
        }

        $request->validate([
            'template_type'         => 'required|string|in:undangan,pemberitahuan,pengantar,rekomendasi,kustom',
            'perihal'               => 'required|string|max:255',
            'tujuan_penerima'       => 'required|string|max:255',
            'tujuan_lokasi'         => 'nullable|string|max:255',
            'tanggal_surat'         => 'required|date',
            'lampiran'              => 'nullable|string|max:100',
            'salam_pembuka'         => 'nullable|string|max:100',
            'paragraf_pembuka'      => 'nullable|string',
            'isi_surat'             => 'nullable|string',
            'paragraf_penutup'      => 'nullable|string',
            'tembusan'              => 'nullable|string',
            'nomor_surat'           => 'nullable|string|max:100',
            'kode_indeks'           => 'nullable|string|max:50',
            'hari_tanggal_kegiatan' => 'nullable|string|max:100',
            'waktu_kegiatan'        => 'nullable|string|max:100',
            'tempat_kegiatan'       => 'nullable|string|max:200',
            'agenda_kegiatan'       => 'nullable|string|max:255',
        ]);

        $res = SuratTemplateService::store($request, $user);

        return redirect()->route('dashboard.persuratan.keluar.index', ['tab' => 'template'])
            ->with('success', "Surat keluar nomor {$res['nomorSurat']} berhasil dibuat dari template.")
            ->with('cetak_template_id', $res['templateDoc']->id);
    }

    /**
     * Cetak Lembar Surat Template Resmi Format A4 Ber-Kop (Autocrat Locked Layout)
     */
    public function cetakTemplate($id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_keluar', 'read')) {
            abort(403, 'Akses ditolak.');
        }

        $data = SuratTemplateService::buildCetakData($id);

        return view('dashboard.persuratan.cetak-template', $data);
    }

    /**
     * Hapus Surat Keluar dari Template
     */
    public function deleteTemplate($id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_keluar', 'delete')) {
            abort(403, 'Akses ditolak.');
        }

        SuratTemplateService::delete($id);

        return redirect()->route('dashboard.persuratan.keluar.index', ['tab' => 'template'])
            ->with('success', 'Dokumen surat template berhasil dihapus.');
    }

    /**
     * Cetak Buku Agenda Surat Keluar Resmi (Format Landscape A4 Kedinasan)
     */
    public function cetakAgenda(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_keluar', 'read') && !RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'read')) {
            abort(403, 'Akses pencetakan buku agenda surat keluar tidak diizinkan.');
        }

        $data = SuratAgendaEkspedisiService::buildAgendaData($request, 'keluar');

        return view('dashboard.persuratan.cetak-agenda', $data);
    }

    /**
     * Cetak Lembar Ekspedisi / Tanda Terima Pengiriman Surat Keluar (Single atau Batch)
     */
    public function cetakEkspedisi(Request $request, $id = null)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_keluar', 'read') && !RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'read')) {
            abort(403, 'Akses cetak lembar ekspedisi tidak diizinkan.');
        }

        $data = SuratAgendaEkspedisiService::buildEkspedisiData($request, $id);

        return view('dashboard.persuratan.cetak-ekspedisi', $data);
    }
}
