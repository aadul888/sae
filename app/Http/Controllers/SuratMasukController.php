<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\RolePermission;
use App\Models\Persuratan;
use App\Models\PersuratanDisposisi;
use App\Services\PersuratanHddService;

class SuratMasukController extends Controller
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
     * Tampilan Buku Agenda Surat Masuk
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_masuk', 'read')) {
            abort(403, 'Akses ke modul surat masuk tidak diizinkan.');
        }

        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_surat_masuk', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_surat_masuk', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_surat_masuk', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_surat_masuk', 'delete');

        // Statistik Surat Masuk
        $stats = [
            'total'    => Persuratan::where('jenis_surat', 'masuk')->count(),
            'menunggu' => Persuratan::where('jenis_surat', 'masuk')->where('status', 'menunggu_disposisi')->count(),
            'proses'   => Persuratan::where('jenis_surat', 'masuk')->where('status', 'diproses')->count(),
            'selesai'  => Persuratan::where('jenis_surat', 'masuk')->where('status', 'selesai')->count(),
        ];

        $q          = trim($request->get('q', ''));
        $status     = $request->get('status', '');
        $sort       = $request->get('sort', 'tanggal_surat');
        $sortDir    = strtolower($request->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPageVal = $request->get('per_page', '25');
        $perPage    = in_array($perPageVal, ['10', '25', '50', '100']) ? (int)$perPageVal : 25;

        $query = Persuratan::where('jenis_surat', 'masuk');

        if ($q !== '') {
            $query->where(function ($b) use ($q) {
                $b->where('nomor_surat', 'like', "%{$q}%")
                  ->orWhere('perihal', 'like', "%{$q}%")
                  ->orWhere('pengirim_asal', 'like', "%{$q}%")
                  ->orWhere('keterangan', 'like', "%{$q}%")
                  ->orWhere('kode_indeks', 'like', "%{$q}%");
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $allowedSorts = ['nomor_surat', 'tanggal_surat', 'tanggal_diterima', 'status', 'created_at', 'pengirim_asal'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $sortDir);
        } else {
            $query->orderBy('tanggal_surat', 'desc');
        }

        $items = $query->paginate($perPage)->withQueryString();

        // Data PTK untuk tujuan disposisi
        $ptkList = DB::table('gtk')
            ->select('ptk_id', 'nama', 'jenis_ptk_id_str', 'jabatan_ptk_id_str')
            ->orderBy('nama')
            ->get();

        // Master Indeks Klasifikasi Surat
        $indeksList = DB::table('ref_indeks_surat')->where('is_active', true)->orderBy('kode')->get();

        // Cek status HDD
        $hddStatus = PersuratanHddService::checkStatus();

        return view('dashboard.persuratan.masuk', compact(
            'stats',
            'items',
            'q',
            'status',
            'sort',
            'sortDir',
            'perPage',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete',
            'ptkList',
            'indeksList',
            'hddStatus'
        ));
    }

    /**
     * Catat Surat Masuk Baru + Simpan Berkas Fisik ke Harddisk
     */
    public function store(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_masuk', 'create')) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'nomor_surat' => 'required|string|max:100',
            'perihal' => 'required|string|max:255',
            'pengirim_asal' => 'required|string|max:200',
            'tanggal_surat' => 'required|date',
            'tanggal_diterima' => 'nullable|date',
            'kode_indeks' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:menunggu_disposisi,diproses,selesai,diarsipkan',
            'keterangan' => 'nullable|string|max:1000',
            'file_arsip' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600', // max 25MB
        ]);

        $filePath = null;
        $fileSize = null;
        $fileNameOriginal = null;

        if ($request->hasFile('file_arsip')) {
            $uploaded = PersuratanHddService::storeFile($request->file('file_arsip'), 'masuk');
            $filePath = $uploaded['relative_path'];
            $fileSize = $uploaded['file_size'];
            $fileNameOriginal = $uploaded['file_name_original'];
        }

        $userName = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Petugas')) : ($user->nama ?? ($user->name ?? 'Petugas'));

        $persuratan = Persuratan::create([
            'nomor_surat' => trim($request->nomor_surat),
            'kode_indeks' => $request->kode_indeks ?: null,
            'jenis_surat' => 'masuk',
            'perihal' => trim($request->perihal),
            'pengirim_asal' => trim($request->pengirim_asal),
            'tujuan_penerima' => 'SMK SAE',
            'tanggal_surat' => $request->tanggal_surat,
            'tanggal_diterima' => $request->tanggal_diterima ?: date('Y-m-d'),
            'status' => $request->status ?: 'menunggu_disposisi',
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'file_name_original' => $fileNameOriginal,
            'keterangan' => $request->keterangan ? trim($request->keterangan) : null,
            'created_by' => $userName,
        ]);

        return redirect()->route('persuratan.masuk.index')->with('success', "Surat masuk nomor {$persuratan->nomor_surat} berhasil dicatat.");
    }

    /**
     * Tampilkan Detail Surat Masuk & Riwayat Disposisi (JSON untuk Modal)
     */
    public function show($id): JsonResponse
    {
        $surat = Persuratan::where('jenis_surat', 'masuk')->findOrFail($id);
        $disposisi = PersuratanDisposisi::where('persuratan_id', $id)->orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'data' => $surat,
            'disposisi' => $disposisi,
            'has_file' => !empty($surat->file_path),
            'file_url' => !empty($surat->file_path) ? route('persuratan.dokumen.view', $surat->id) : null,
            'file_download_url' => !empty($surat->file_path) ? route('persuratan.dokumen.download', $surat->id) : null,
        ]);
    }

    /**
     * Update Data Surat Masuk + Ganti Berkas di HDD jika Ada
     */
    public function update(Request $request, $id)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_masuk', 'update')) {
            abort(403, 'Akses ditolak.');
        }

        $surat = Persuratan::where('jenis_surat', 'masuk')->findOrFail($id);

        $request->validate([
            'nomor_surat' => 'required|string|max:100',
            'perihal' => 'required|string|max:255',
            'pengirim_asal' => 'required|string|max:200',
            'tanggal_surat' => 'required|date',
            'tanggal_diterima' => 'nullable|date',
            'kode_indeks' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:menunggu_disposisi,diproses,selesai,diarsipkan',
            'keterangan' => 'nullable|string|max:1000',
            'file_arsip' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600',
        ]);

        $data = [
            'nomor_surat' => trim($request->nomor_surat),
            'kode_indeks' => $request->kode_indeks ?: null,
            'perihal' => trim($request->perihal),
            'pengirim_asal' => trim($request->pengirim_asal),
            'tanggal_surat' => $request->tanggal_surat,
            'tanggal_diterima' => $request->tanggal_diterima ?: $surat->tanggal_diterima,
            'status' => $request->status ?: $surat->status,
            'keterangan' => $request->keterangan ? trim($request->keterangan) : null,
        ];

        if ($request->hasFile('file_arsip')) {
            // Hapus file lama jika ada
            if ($surat->file_path) {
                PersuratanHddService::deleteFile($surat->file_path);
            }

            $uploaded = PersuratanHddService::storeFile($request->file('file_arsip'), 'masuk');
            $data['file_path'] = $uploaded['relative_path'];
            $data['file_size'] = $uploaded['file_size'];
            $data['file_name_original'] = $uploaded['file_name_original'];
        }

        $surat->update($data);

        return redirect()->route('persuratan.masuk.index')->with('success', 'Data surat masuk berhasil diperbarui.');
    }

    /**
     * Hapus Surat Masuk + Hapus Berkas Fisik di HDD
     */
    public function destroy($id)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_masuk', 'delete')) {
            abort(403, 'Akses ditolak.');
        }

        $surat = Persuratan::where('jenis_surat', 'masuk')->findOrFail($id);

        // Hapus berkas fisik di HDD
        if ($surat->file_path) {
            PersuratanHddService::deleteFile($surat->file_path);
        }

        // Hapus disposisi terkait
        PersuratanDisposisi::where('persuratan_id', $id)->delete();
        $surat->delete();

        return redirect()->route('persuratan.masuk.index')->with('success', 'Surat masuk beserta arsip dokumennya berhasil dihapus.');
    }

    /**
     * Buat atau Tambahkan Lembar Disposisi Surat Masuk
     */
    public function disposisi(Request $request, $id)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_surat_masuk', 'update')) {
            abort(403, 'Akses ditolak.');
        }

        $surat = Persuratan::where('jenis_surat', 'masuk')->findOrFail($id);

        $request->validate([
            'disposisi_dari' => 'nullable|string|max:100',
            'disposisi_ke' => 'required|string|max:100',
            'ptk_id_tujuan' => 'nullable|string|max:100',
            'instruksi' => 'required|string|max:100',
            'catatan' => 'nullable|string|max:1000',
            'tanggal_disposisi' => 'nullable|date',
            'status' => 'nullable|string|in:menunggu,diproses,selesai',
        ]);

        PersuratanDisposisi::create([
            'persuratan_id' => $id,
            'disposisi_dari' => $request->disposisi_dari ?: 'Kepala Sekolah',
            'disposisi_ke' => trim($request->disposisi_ke),
            'ptk_id_tujuan' => $request->ptk_id_tujuan ?: null,
            'instruksi' => trim($request->instruksi),
            'catatan' => $request->catatan ? trim($request->catatan) : null,
            'tanggal_disposisi' => $request->tanggal_disposisi ?: date('Y-m-d'),
            'status' => $request->status ?: 'diproses',
        ]);

        // Update status surat ke diproses
        $surat->update(['status' => 'diproses']);

        return redirect()->route('persuratan.masuk.index')->with('success', 'Lembar disposisi surat masuk berhasil disimpan.');
    }

    /**
     * Cetak Lembar Disposisi Resmi Ber-Kop Sekolah
     */
    public function cetakDisposisi($id)
    {
        $surat = Persuratan::where('jenis_surat', 'masuk')->findOrFail($id);
        $disposisi = PersuratanDisposisi::where('persuratan_id', $id)->orderByDesc('id')->get();
        $sekolah = DB::table('sekolah')->first();
        $sekolahMeta = \App\Models\SekolahMeta::first();

        $kepsek = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        return view('dashboard.persuratan.cetak-disposisi', compact(
            'surat',
            'disposisi',
            'sekolah',
            'sekolahMeta',
            'kepsek'
        ));
    }

    /**
     * Render / Preview Berkas Dokumen dari HDD Komputer
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
     * Unduh Berkas Dokumen dari HDD Komputer
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
}
