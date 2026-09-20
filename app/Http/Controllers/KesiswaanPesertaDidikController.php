<?php

namespace App\Http\Controllers;

use App\Models\KesiswaanBerkasVerifikasi;
use App\Models\PesertaDidik;
use App\Models\RolePermission;
use App\Models\SiswaUsulanPerubahan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KesiswaanPesertaDidikController extends Controller
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
     * Tampilkan Halaman Kluster Peserta Didik (Aktif, Tidak Aktif, Alumni, Berkas, Usulan Perubahan).
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        // RBAC: Gunakan permission menu_peserta_didik_aktif atau menu_kesiswaan
        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'create') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'read') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'update') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'delete') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'delete');

        $activeTab = $request->get('tab', 'aktif'); // aktif, tidak_aktif, alumni, berkas, usulan
        $q = trim($request->get('q', ''));
        $rombel = trim($request->get('rombel', ''));
        $gender = trim($request->get('gender', ''));
        $tahunLulus = trim($request->get('tahun_lulus', ''));

        // 1. Statistik Ringkas
        $totalAktif = PesertaDidik::count();
        $totalTidakAktif = DB::table('peserta_didik_tidak_aktif')->count();
        $totalAlumni = DB::table('peserta_didik_tidak_aktif')
            ->where(function ($b) {
                $b->where('alasan_keluar', 'like', '%Lulus%')
                  ->orWhere('alasan_keluar', 'like', '%Tamat%');
            })->count();
        $totalBerkasLengkap = KesiswaanBerkasVerifikasi::where('akta_kelahiran', true)
            ->where('kartu_keluarga', true)
            ->where('ijazah_smp', true)
            ->count();
        $totalUsulanMenunggu = SiswaUsulanPerubahan::where('status', 'menunggu')->count();

        $stats = [
            'total_aktif'       => $totalAktif,
            'total_tidak_aktif' => $totalTidakAktif,
            'total_alumni'      => $totalAlumni,
            'berkas_lengkap'    => $totalBerkasLengkap,
            'usulan_menunggu'   => $totalUsulanMenunggu,
        ];

        // Daftar rombel untuk filter
        $filterRombel = DB::table('peserta_didik')
            ->whereNotNull('nama_rombel')
            ->where('nama_rombel', '<>', '')
            ->distinct()
            ->pluck('nama_rombel')
            ->sort()
            ->values();

        // 2. Tab: Peserta Didik Aktif
        $aktifQuery = DB::table('peserta_didik')
            ->select('peserta_didik_id', 'nama', 'nisn', 'nipd', 'nik', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'nama_rombel', 'nama_ayah', 'nama_ibu');

        if ($q !== '' && $activeTab === 'aktif') {
            $aktifQuery->where(function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%")
                  ->orWhere('nisn', 'like', "%{$q}%")
                  ->orWhere('nipd', 'like', "%{$q}%");
            });
        }
        if ($rombel !== '') {
            $aktifQuery->where('nama_rombel', $rombel);
        }
        if ($gender !== '') {
            $aktifQuery->where('jenis_kelamin', $gender);
        }
        $aktifList = $aktifQuery->orderBy('nama', 'asc')->paginate(25, ['*'], 'aktif_page')->withQueryString();

        // 3. Tab: Peserta Didik Tidak Aktif (Mutasi / DO / Berhenti)
        $tidakAktifQuery = DB::table('peserta_didik_tidak_aktif')
            ->select('id', 'peserta_didik_id', 'nama', 'nisn', 'nipd', 'jenis_kelamin', 'rombel_terakhir', 'alasan_keluar', 'tanggal_keluar', 'sekolah_tujuan');

        if ($q !== '' && $activeTab === 'tidak_aktif') {
            $tidakAktifQuery->where(function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%")
                  ->orWhere('nisn', 'like', "%{$q}%")
                  ->orWhere('alasan_keluar', 'like', "%{$q}%");
            });
        }
        $tidakAktifList = $tidakAktifQuery->orderBy('tanggal_keluar', 'desc')->paginate(25, ['*'], 'tidak_aktif_page')->withQueryString();

        // 4. Tab: Alumni
        $alumniQuery = DB::table('peserta_didik_tidak_aktif')
            ->where(function ($b) {
                $b->where('alasan_keluar', 'like', '%Lulus%')
                  ->orWhere('alasan_keluar', 'like', '%Tamat%');
            })
            ->select('id', 'peserta_didik_id', 'nama', 'nisn', 'nipd', 'jenis_kelamin', 'rombel_terakhir', 'alasan_keluar', 'tanggal_keluar');

        if ($q !== '' && $activeTab === 'alumni') {
            $alumniQuery->where(function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%")
                  ->orWhere('nisn', 'like', "%{$q}%")
                  ->orWhere('rombel_terakhir', 'like', "%{$q}%");
            });
        }
        if ($tahunLulus !== '') {
            $alumniQuery->whereYear('tanggal_keluar', $tahunLulus);
        }
        $alumniList = $alumniQuery->orderBy('tanggal_keluar', 'desc')->paginate(25, ['*'], 'alumni_page')->withQueryString();

        // 5. Tab: Verifikasi Berkas Fisik
        $berkasQuery = DB::table('peserta_didik as pd')
            ->leftJoin('kesiswaan_berkas_verifikasi as kbv', 'pd.peserta_didik_id', '=', 'kbv.peserta_didik_id')
            ->leftJoin('anggota_rombel as ar', 'pd.peserta_didik_id', '=', 'ar.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->select(
                'pd.peserta_didik_id',
                'pd.nama',
                'pd.nisn',
                'pd.nipd',
                'rb.nama as rombel_nama',
                'kbv.id as berkas_id',
                'kbv.akta_kelahiran',
                'kbv.kartu_keluarga',
                'kbv.ijazah_smp',
                'kbv.ktp_orang_tua',
                'kbv.kip_pip',
                'kbv.verified_by',
                'kbv.verified_at'
            );

        if ($q !== '' && $activeTab === 'berkas') {
            $berkasQuery->where(function ($b) use ($q) {
                $b->where('pd.nama', 'like', "%{$q}%")
                  ->orWhere('pd.nisn', 'like', "%{$q}%");
            });
        }
        $berkasList = $berkasQuery->orderBy('pd.nama', 'asc')->paginate(25, ['*'], 'berkas_page')->withQueryString();

        // 6. Tab: Usulan Perubahan Data Siswa
        $usulanQuery = SiswaUsulanPerubahan::with('siswa')->orderBy('created_at', 'desc');
        if ($q !== '' && $activeTab === 'usulan') {
            $usulanQuery->whereHas('siswa', function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%")
                  ->orWhere('nisn', 'like', "%{$q}%");
            })->orWhere('kolom_perubahan', 'like', "%{$q}%")
              ->orWhere('alasan', 'like', "%{$q}%");
        }
        $usulanList = $usulanQuery->paginate(25, ['*'], 'usulan_page')->withQueryString();

        // Daftar siswa aktif untuk modal usulan
        $siswaList = PesertaDidik::orderBy('nama')
            ->limit(300)
            ->get(['peserta_didik_id', 'nama', 'nisn', 'nipd']);

        return view('dashboard.kesiswaan.peserta-didik', compact(
            'stats',
            'activeTab',
            'q',
            'rombel',
            'gender',
            'tahunLulus',
            'filterRombel',
            'aktifList',
            'tidakAktifList',
            'alumniList',
            'berkasList',
            'usulanList',
            'siswaList',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete'
        ));
    }

    /**
     * Simpan Usulan Perubahan Data Siswa.
     */
    public function storeUsulan(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $validated = $request->validate([
            'peserta_didik_id' => 'required|string',
            'kolom_perubahan'  => 'required|string|max:100',
            'nilai_lama'       => 'nullable|string',
            'nilai_baru'       => 'required|string',
            'alasan'           => 'required|string|max:255',
            'berkas_bukti'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $filePath = null;
        if ($request->hasFile('berkas_bukti')) {
            $file = $request->file('berkas_bukti');
            $fileName = 'usulan_' . time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/usulan_siswa'), $fileName);
            $filePath = '/uploads/usulan_siswa/' . $fileName;
        }

        $userName = is_array($user) ? ($user['nama'] ?? 'Pengguna') : ($user->nama ?? 'Pengguna');

        $usulan = SiswaUsulanPerubahan::create([
            'peserta_didik_id'   => $validated['peserta_didik_id'],
            'kolom_perubahan'    => $validated['kolom_perubahan'],
            'nilai_lama'         => $validated['nilai_lama'] ?? null,
            'nilai_baru'         => $validated['nilai_baru'],
            'alasan'             => $validated['alasan'],
            'berkas_bukti'       => $filePath,
            'status'             => 'menunggu',
            'created_by'         => $userName,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Usulan perubahan data siswa berhasil dikirim dan menunggu verifikasi.',
            'data' => $usulan,
        ]);
    }

    /**
     * Verifikasi Usulan Perubahan Data (Setujui / Tolak).
     */
    public function verifikasiUsulan(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update') && !RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'status'             => 'required|string|in:disetujui,ditolak',
            'catatan_verifikasi' => 'nullable|string',
        ]);

        $usulan = SiswaUsulanPerubahan::findOrFail($id);
        $userName = is_array($user) ? ($user['nama'] ?? 'Verifikator') : ($user->nama ?? 'Verifikator');

        $usulan->status = $validated['status'];
        $usulan->catatan_verifikasi = $validated['catatan_verifikasi'] ?? null;
        $usulan->verified_by = $userName;
        $usulan->verified_at = now();
        $usulan->save();

        // Jika disetujui, update data peserta didik secara otomatis
        if ($validated['status'] === 'disetujui' && Schema::hasColumn('peserta_didik', $usulan->kolom_perubahan)) {
            DB::table('peserta_didik')
                ->where('peserta_didik_id', $usulan->peserta_didik_id)
                ->update([$usulan->kolom_perubahan => $usulan->nilai_baru]);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Usulan perubahan data status diubah menjadi: {$validated['status']}.",
            'data' => $usulan,
        ]);
    }

    /**
     * Update Checklist Verifikasi Berkas Fisik Siswa Baru.
     */
    public function updateBerkas(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update') && !RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $berkas = KesiswaanBerkasVerifikasi::firstOrNew(['peserta_didik_id' => $id]);

        $berkas->akta_kelahiran = $request->boolean('akta_kelahiran');
        $berkas->kartu_keluarga = $request->boolean('kartu_keluarga');
        $berkas->ijazah_smp     = $request->boolean('ijazah_smp');
        $berkas->ktp_orang_tua  = $request->boolean('ktp_orang_tua');
        $berkas->kip_pip        = $request->boolean('kip_pip');

        $userName = is_array($user) ? ($user['nama'] ?? 'Staf Kesiswaan') : ($user->nama ?? 'Staf Kesiswaan');
        $berkas->verified_by = $userName;
        $berkas->verified_at = now();
        $berkas->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Verifikasi kelengkapan berkas berhasil diperbarui.',
            'data' => $berkas,
        ]);
    }
}
