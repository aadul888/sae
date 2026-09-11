<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IdentitasSekolahController extends Controller
{
    private function checkAuth()
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }
        return null;
    }

    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $sekolah = null;
        if (Schema::hasTable('sekolah')) {
            $sekolah = DB::table('sekolah')->first();
        }

        $settings = null;
        if (Schema::hasTable('settings')) {
            $settings = DB::table('settings')->where('id', 1)->first();
        }

        $stats = [
            'total_pd' => Schema::hasTable('peserta_didik') ? DB::table('peserta_didik')->count() : 0,
            'total_gtk' => Schema::hasTable('gtk') ? DB::table('gtk')->count() : 0,
            'total_rombel' => Schema::hasTable('rombongan_belajar') ? DB::table('rombongan_belajar')->count() : 0,
            'total_pembelajaran' => Schema::hasTable('pembelajaran') ? DB::table('pembelajaran')->count() : 0,
            'total_pengguna' => Schema::hasTable('pengguna') ? DB::table('pengguna')->count() : 0,
        ];

        $sekolahMeta = null;
        if (Schema::hasTable('sekolah_meta')) {
            $sekolahMeta = \App\Models\SekolahMeta::first();
        }

        return view('dashboard.identitas-sekolah', compact('sekolah', 'settings', 'stats', 'sekolahMeta'));
    }

    public function update(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return back()->with('error', 'Hanya administrator yang dapat memperbarui identitas sekolah.');
        }

        $request->validate([
            'nama' => 'nullable|string|max:200',
            'npsn' => 'nullable|string|max:20',
            'nss' => 'nullable|string|max:50',
            'bentuk_pendidikan' => 'nullable|string|max:100',
            'status_sekolah' => 'nullable|string|max:100',
            'alamat_jalan' => 'nullable|string',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'dusun' => 'nullable|string|max:100',
            'desa_kelurahan' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'kabupaten_kota' => 'nullable|string|max:100',
            'provinsi' => 'nullable|string|max:100',
            'kode_pos' => 'nullable|string|max:10',
            'lintang' => 'nullable|string|max:50',
            'bujur' => 'nullable|string|max:50',
            'nomor_telepon' => 'nullable|string|max:50',
            'nomor_fax' => 'nullable|string|max:50',
            'email' => 'nullable|string|email|max:100',
            'website' => 'nullable|string|max:200',
        ]);

        if (Schema::hasTable('sekolah')) {
            $current = DB::table('sekolah')->first();
            $data = [
                'nama' => $request->input('nama', $current->nama ?? null),
                'npsn' => $request->input('npsn', $current->npsn ?? null),
                'nss' => $request->input('nss', $current->nss ?? null),
                'bentuk_pendidikan_id_str' => $request->input('bentuk_pendidikan', $current->bentuk_pendidikan_id_str ?? null),
                'status_sekolah_str' => $request->input('status_sekolah', $current->status_sekolah_str ?? null),
                'alamat_jalan' => $request->input('alamat_jalan', $current->alamat_jalan ?? null),
                'rt' => $request->input('rt', $current->rt ?? null),
                'rw' => $request->input('rw', $current->rw ?? null),
                'dusun' => $request->input('dusun', $current->dusun ?? null),
                'desa_kelurahan' => $request->input('desa_kelurahan', $current->desa_kelurahan ?? null),
                'kecamatan' => $request->input('kecamatan', $current->kecamatan ?? null),
                'kabupaten_kota' => $request->input('kabupaten_kota', $current->kabupaten_kota ?? null),
                'provinsi' => $request->input('provinsi', $current->provinsi ?? null),
                'kode_pos' => $request->input('kode_pos', $current->kode_pos ?? null),
                'lintang' => $request->input('lintang', $current->lintang ?? null),
                'bujur' => $request->input('bujur', $current->bujur ?? null),
                'nomor_telepon' => $request->input('nomor_telepon', $current->nomor_telepon ?? null),
                'nomor_fax' => $request->input('nomor_fax', $current->nomor_fax ?? null),
                'email' => $request->input('email', $current->email ?? null),
                'website' => $request->input('website', $current->website ?? null),
                'updated_at' => now(),
            ];

            if ($current) {
                DB::table('sekolah')->where('sekolah_id', $current->sekolah_id)->update($data);
            } else {
                $data['sekolah_id'] = 'SCH-' . uniqid();
                $data['created_at'] = now();
                DB::table('sekolah')->insert($data);
            }
        }

        return back()->with('success', 'Identitas Sekolah berhasil disimpan.');
    }

    /**
     * Unggah dan auto-kompresi logo sekolah (khusus format PNG transparan)
     */
    public function uploadLogo(Request $request, \App\Services\ImageOptimizerService $optimizer)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Sesi login telah berakhir.'], 401);
        }

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!\App\Models\RolePermission::canAccess($role, 'menu_pengaturan')) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk mengunggah logo sekolah.'], 403);
        }

        $request->validate([
            'logo' => 'required|file|mimes:png|max:5120',
        ], [
            'logo.required' => 'Silakan pilih file logo terlebih dahulu.',
            'logo.mimes' => 'Format file logo sekolah wajib berupa PNG (.png) untuk kebutuhan kartu pelajar dan kop digital.',
            'logo.max' => 'Ukuran file logo sekolah maksimal 5 MB sebelum dikompresi.',
        ]);

        try {
            $sekolah = Schema::hasTable('sekolah') ? DB::table('sekolah')->first() : null;
            $meta = \App\Models\SekolahMeta::getActiveMeta($sekolah?->sekolah_id, $sekolah?->npsn);

            // Hapus file fisik logo lama jika sudah pernah ada
            if (!empty($meta->logo_path)) {
                $optimizer->deleteFile($meta->logo_path);
            }

            // Simpan dan kompresi PNG baru secara lossless dengan alpha channel
            $result = $optimizer->optimizeAndSavePng(
                $request->file('logo'),
                \App\Services\ImageOptimizerService::ASSET_DIR_SEKOLAH,
                'logo_sekolah_' . ($sekolah->npsn ?? 'main') . '_' . time(),
                1000 // Resolusi optimal untuk logo resmi & background kartu pelajar
            );

            $meta->logo_path = $result['path'];
            $meta->logo_size = $result['size'];
            $meta->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Logo sekolah berhasil disimpan dan dioptimasi secara persisten.',
                'logo_url' => $meta->logo_url,
                'logo_size' => $meta->formatted_logo_size,
                'dimensions' => $result['width'] . ' × ' . $result['height'] . ' px',
                'savings_percent' => $result['savings_percent'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengunggah logo sekolah: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hapus logo sekolah
     */
    public function deleteLogo(Request $request, \App\Services\ImageOptimizerService $optimizer)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Sesi login telah berakhir.'], 401);
        }

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!\App\Models\RolePermission::canAccess($role, 'menu_pengaturan')) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menghapus logo sekolah.'], 403);
        }

        try {
            $meta = \App\Models\SekolahMeta::first();
            if ($meta && !empty($meta->logo_path)) {
                $optimizer->deleteFile($meta->logo_path);
                $meta->logo_path = null;
                $meta->logo_size = null;
                $meta->save();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Logo sekolah berhasil dihapus.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus logo sekolah: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unggah kop surat resmi sekolah (Format PNG Lossless)
     */
    public function uploadKop(Request $request, \App\Services\ImageOptimizerService $optimizer)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Sesi login telah berakhir.'], 401);
        }

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!\App\Models\RolePermission::canAccess($role, 'menu_pengaturan')) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk mengunggah kop sekolah.'], 403);
        }

        $request->validate([
            'kop' => 'required|file|mimes:png|max:6144',
        ], [
            'kop.required' => 'Silakan pilih berkas kop surat terlebih dahulu.',
            'kop.mimes' => 'Format berkas kop surat sekolah wajib berupa PNG (.png) untuk kebutuhan surat dinas, rapor, dan kartu digital.',
            'kop.max' => 'Ukuran berkas kop sekolah maksimal 6 MB sebelum dikompresi.',
        ]);

        try {
            $sekolah = Schema::hasTable('sekolah') ? DB::table('sekolah')->first() : null;
            $meta = \App\Models\SekolahMeta::getActiveMeta($sekolah?->sekolah_id, $sekolah?->npsn);

            // Hapus file fisik kop lama jika sudah pernah ada
            if (!empty($meta->kop_path)) {
                $optimizer->deleteFile($meta->kop_path);
            }

            // Simpan dan kompresi PNG kop surat (lebar resolusi hingga 1800px untuk cetak A4 tajam)
            $result = $optimizer->optimizeAndSavePng(
                $request->file('kop'),
                \App\Services\ImageOptimizerService::ASSET_DIR_SEKOLAH,
                'kop_sekolah_' . ($sekolah->npsn ?? 'main') . '_' . time(),
                1800 // Resolusi optimal kop surat standar A4 cetak
            );

            $meta->kop_path = $result['path'];
            $meta->kop_size = $result['size'];
            $meta->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Kop surat sekolah berhasil disimpan dan dioptimasi secara persisten.',
                'kop_url' => $meta->kop_url,
                'kop_size' => $meta->formatted_kop_size,
                'dimensions' => $result['width'] . ' × ' . $result['height'] . ' px',
                'savings_percent' => $result['savings_percent'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengunggah kop sekolah: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hapus kop surat sekolah
     */
    public function deleteKop(Request $request, \App\Services\ImageOptimizerService $optimizer)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Sesi login telah berakhir.'], 401);
        }

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!\App\Models\RolePermission::canAccess($role, 'menu_pengaturan')) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menghapus kop sekolah.'], 403);
        }

        try {
            $meta = \App\Models\SekolahMeta::first();
            if ($meta && !empty($meta->kop_path)) {
                $optimizer->deleteFile($meta->kop_path);
                $meta->kop_path = null;
                $meta->kop_size = null;
                $meta->save();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Kop surat sekolah berhasil dihapus.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus kop sekolah: ' . $e->getMessage(),
            ], 500);
        }
    }
}
