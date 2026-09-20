<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\RolePermission;
use App\Services\PersuratanHddService;

class PersuratanSettingController extends Controller
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
     * Tampilan utama Pengaturan Sistem Persuratan & Master Indeks
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        // Hak akses: admin dan tendik persuratan
        if (!RolePermission::canAccess($user ?: $role, 'menu_pengaturan_persuratan', 'read')) {
            abort(403, 'Akses ke pengaturan persuratan tidak diizinkan.');
        }

        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_pengaturan_persuratan', 'update');

        $setting = DB::table('persuratan_settings')->first();
        if (!$setting) {
            $id = DB::table('persuratan_settings')->insertGetId([
                'hdd_path' => storage_path('app/arsip_persuratan'),
                'is_hdd_active' => true,
                'format_nomor_surat_keluar' => '{nomor}/{kode_indeks}-{sekolah_kode}',
                'format_nomor_surat_keterangan' => '{nomor}/{kode_indeks}-{sekolah_kode}',
                'nomor_terakhir_surat_keluar' => 0,
                'nomor_terakhir_surat_keterangan' => 0,
                'tahun_terakhir' => (int) date('Y'),
                'sekolah_kode' => 'SMKN1PGL',
                'auto_subfolder' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $setting = DB::table('persuratan_settings')->where('id', $id)->first();
        }

        // Cek status HDD live
        $hddStatus = PersuratanHddService::checkStatus();

        // Master Indeks Klasifikasi Surat
        $qIndeks = trim($request->get('q_indeks', ''));
        $kategoriIndeks = $request->get('kategori', '');

        $indeksQuery = DB::table('ref_indeks_surat');
        if ($qIndeks !== '') {
            $indeksQuery->where(function ($b) use ($qIndeks) {
                $b->where('kode', 'like', "%{$qIndeks}%")
                  ->orWhere('judul', 'like', "%{$qIndeks}%")
                  ->orWhere('keterangan', 'like', "%{$qIndeks}%");
            });
        }
        if ($kategoriIndeks !== '') {
            $indeksQuery->where('kategori', $kategoriIndeks);
        }

        $perPageVal = $request->get('perPage', $request->get('per_page', '20'));
        $perPage = in_array($perPageVal, ['10', '15', '20', '25', '50', '100']) ? (int)$perPageVal : 20;

        $indeksList = $indeksQuery->orderBy('kode')->paginate($perPage)->withQueryString();
        $kategoriList = DB::table('ref_indeks_surat')->distinct()->pluck('kategori');

        // Ringkasan Statistik Berkas di HDD
        $totalSuratMasuk = DB::table('persuratan')->where('jenis_surat', 'masuk')->count();
        $totalSuratKeluar = DB::table('persuratan')->where('jenis_surat', 'keluar')->count();
        $totalSuratKet = DB::table('surat_keterangan_pd')->count();
        $totalBerkasTercatat = DB::table('persuratan')->whereNotNull('file_path')->count();

        $tab = $request->get('tab', 'referensi');
        if (!in_array($tab, ['referensi', 'pengaturan'])) {
            $tab = 'referensi';
        }

        // Live preview penomoran otomatis
        $previewSuratKeluar = PersuratanHddService::generateNomorSuratKeluar('KPG.11.01');
        $previewSuratKet = PersuratanHddService::generateNomorSuratKeterangan('KS.02.23');

        return view('dashboard.persuratan.pengaturan', compact(
            'setting',
            'hddStatus',
            'indeksList',
            'kategoriList',
            'qIndeks',
            'kategoriIndeks',
            'perPage',
            'canUpdate',
            'totalSuratMasuk',
            'totalSuratKeluar',
            'totalSuratKet',
            'totalBerkasTercatat',
            'tab',
            'previewSuratKeluar',
            'previewSuratKet'
        ));
    }

    /**
     * Update Pengaturan Persuratan (Path HDD & Format Penomoran)
     */
    public function updateSettings(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_pengaturan_persuratan', 'update')) {
            abort(403, 'Anda tidak memiliki hak akses mengubah pengaturan persuratan.');
        }

        $request->validate([
            'hdd_path' => 'required|string|max:255',
            'format_nomor_surat_keluar' => 'required|string|max:150',
            'format_nomor_surat_keterangan' => 'required|string|max:150',
            'sekolah_kode' => 'required|string|max:50',
            'nomor_terakhir_surat_keluar' => 'nullable|integer|min:0',
            'nomor_terakhir_surat_keterangan' => 'nullable|integer|min:0',
        ]);

        $hddPath = rtrim(trim($request->hdd_path), '/\\');

        // Pastikan folder dapat diakses atau dibuat
        if (!is_dir($hddPath)) {
            try {
                @mkdir($hddPath, 0755, true);
            } catch (\Throwable $e) {
                return redirect()->back()->with('error', 'Gagal membuat folder di path harddisk: ' . $e->getMessage());
            }
        }

        $setting = DB::table('persuratan_settings')->first();
        $data = [
            'hdd_path' => $hddPath,
            'is_hdd_active' => $request->boolean('is_hdd_active', true),
            'format_nomor_surat_keluar' => trim($request->format_nomor_surat_keluar),
            'format_nomor_surat_keterangan' => trim($request->format_nomor_surat_keterangan),
            'sekolah_kode' => trim($request->sekolah_kode),
            'auto_subfolder' => $request->boolean('auto_subfolder', true),
            'updated_at' => now(),
        ];

        if ($request->filled('nomor_terakhir_surat_keluar')) {
            $data['nomor_terakhir_surat_keluar'] = (int) $request->nomor_terakhir_surat_keluar;
        }
        if ($request->filled('nomor_terakhir_surat_keterangan')) {
            $data['nomor_terakhir_surat_keterangan'] = (int) $request->nomor_terakhir_surat_keterangan;
        }

        if ($setting) {
            DB::table('persuratan_settings')->where('id', $setting->id)->update($data);
        } else {
            DB::table('persuratan_settings')->insert(array_merge($data, ['created_at' => now()]));
        }

        // Siapkan struktur folder otomatis
        PersuratanHddService::ensureDirectories();

        $targetTab = $request->get('tab', 'pengaturan');
        if (!in_array($targetTab, ['referensi', 'pengaturan'])) {
            $targetTab = 'referensi';
        }

        return redirect()->route('dashboard.persuratan.pengaturan.index', ['tab' => $targetTab])
            ->with('success', 'Pengaturan persuratan, harddisk, dan format penomoran berhasil disimpan.');
    }

    /**
     * AJAX Test Koneksi & Kapasitas Harddisk
     */
    public function testHdd(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::canAccess($user ?: $role, 'menu_pengaturan_persuratan', 'read')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $customPath = $request->get('path');
        if ($customPath) {
            $path = rtrim(trim($customPath), '/\\');
            $exists = is_dir($path);
            $writable = false;

            if ($exists) {
                $writable = is_writable($path);
            } else {
                try {
                    @mkdir($path, 0755, true);
                    $exists = is_dir($path);
                    $writable = $exists && is_writable($path);
                } catch (\Throwable $e) {
                    $exists = false;
                    $writable = false;
                }
            }

            $freeBytes = $exists ? (@disk_free_space($path) ?: 0) : 0;
            $totalBytes = $exists ? (@disk_total_space($path) ?: 0) : 0;

            return response()->json([
                'success' => $exists && $writable,
                'path' => $path,
                'exists' => $exists,
                'writable' => $writable,
                'free_formatted' => PersuratanHddService::formatBytes($freeBytes),
                'total_formatted' => PersuratanHddService::formatBytes($totalBytes),
                'message' => ($exists && $writable)
                    ? 'Koneksi ke direktori harddisk BERHASIL dan siap tulis.'
                    : 'Direktori harddisk tidak dapat diakses atau tidak memiliki izin tulis.',
            ]);
        }

        $status = PersuratanHddService::checkStatus();
        return response()->json([
            'success' => $status['is_ready'],
            'data' => $status,
            'message' => $status['is_ready']
                ? 'Koneksi ke direktori harddisk BERHASIL dan siap tulis.'
                : 'Direktori harddisk tidak dapat diakses atau tidak memiliki izin tulis.',
        ]);
    }

    /**
     * Simpan Master Indeks Klasifikasi Surat Baru
     */
    public function storeIndeks(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_pengaturan_persuratan', 'create')) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'kode' => 'required|string|max:50|unique:ref_indeks_surat,kode',
            'judul' => 'required|string|max:200',
            'kategori' => 'required|string|max:50',
            'keterangan' => 'nullable|string|max:500',
        ]);

        DB::table('ref_indeks_surat')->insert([
            'kode' => trim($request->kode),
            'judul' => trim($request->judul),
            'kategori' => trim($request->kategori),
            'keterangan' => trim($request->keterangan),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.persuratan.pengaturan.index', ['tab' => 'referensi'])->with('success', 'Kode indeks klasifikasi surat berhasil ditambahkan.');
    }

    /**
     * Update Master Indeks Klasifikasi Surat
     */
    public function updateIndeks(Request $request, $id)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_pengaturan_persuratan', 'update')) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'kode' => "required|string|max:50|unique:ref_indeks_surat,kode,{$id}",
            'judul' => 'required|string|max:200',
            'kategori' => 'required|string|max:50',
            'keterangan' => 'nullable|string|max:500',
        ]);

        DB::table('ref_indeks_surat')->where('id', $id)->update([
            'kode' => trim($request->kode),
            'judul' => trim($request->judul),
            'kategori' => trim($request->kategori),
            'keterangan' => trim($request->keterangan),
            'is_active' => $request->boolean('is_active', true),
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.persuratan.pengaturan.index', ['tab' => 'referensi'])->with('success', 'Kode indeks klasifikasi surat berhasil diperbarui.');
    }

    /**
     * Hapus Master Indeks Klasifikasi Surat
     */
    public function destroyIndeks($id)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_pengaturan_persuratan', 'delete')) {
            abort(403, 'Akses ditolak.');
        }

        DB::table('ref_indeks_surat')->where('id', $id)->delete();

        return redirect()->route('dashboard.persuratan.pengaturan.index', ['tab' => 'referensi'])->with('success', 'Kode indeks surat berhasil dihapus.');
    }
}
