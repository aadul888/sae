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

        $perPageVal = $request->get('perPage', $request->get('per_page', '25'));
        $perPage = in_array($perPageVal, ['10', '15', '25', '50', '100']) ? (int)$perPageVal : 25;

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
                  ->orWhere('nipd', 'like', "%{$q}%")
                  ->orWhere('nik', 'like', "%{$q}%");
            });
        }
        if ($rombel !== '') {
            $aktifQuery->where('nama_rombel', $rombel);
        }
        if ($gender !== '') {
            $aktifQuery->where('jenis_kelamin', $gender);
        }
        $aktifList = $aktifQuery->orderBy('nama', 'asc')->paginate($perPage, ['*'], 'aktif_page')->withQueryString();

        $aktifPdIds = $aktifList->pluck('peserta_didik_id')->filter()->values()->all();
        $metaMapAktif = collect();
        if (!empty($aktifPdIds) && Schema::hasTable('peserta_didik_meta')) {
            $metaMapAktif = \App\Models\PesertaDidikMeta::whereIn('peserta_didik_id', $aktifPdIds)->get()->keyBy('peserta_didik_id');
        }
        foreach ($aktifList as $item) {
            $item->foto_url = $metaMapAktif[$item->peserta_didik_id]?->foto_url ?? null;
        }

        // 3. Tab: Peserta Didik Tidak Aktif (Mutasi / DO / Berhenti)
        $tidakAktifQuery = DB::table('peserta_didik_tidak_aktif')
            ->select('id', 'peserta_didik_id', 'nama', 'nisn', 'nipd', 'nik', 'jenis_kelamin', 'nama_rombel_terakhir as rombel_terakhir', 'alasan_keluar', 'tanggal_keluar', 'foto_path');

        if ($q !== '' && $activeTab === 'tidak_aktif') {
            $tidakAktifQuery->where(function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%")
                  ->orWhere('nisn', 'like', "%{$q}%")
                  ->orWhere('nipd', 'like', "%{$q}%")
                  ->orWhere('nik', 'like', "%{$q}%")
                  ->orWhere('alasan_keluar', 'like', "%{$q}%");
            });
        }
        $tidakAktifList = $tidakAktifQuery->orderBy('tanggal_keluar', 'desc')->paginate($perPage, ['*'], 'tidak_aktif_page')->withQueryString();
        $taPdIds = $tidakAktifList->pluck('peserta_didik_id')->filter()->values()->all();
        $metaMapTa = collect();
        if (!empty($taPdIds) && Schema::hasTable('peserta_didik_meta')) {
            $metaMapTa = \App\Models\PesertaDidikMeta::whereIn('peserta_didik_id', $taPdIds)->get()->keyBy('peserta_didik_id');
        }
        foreach ($tidakAktifList as $item) {
            $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : ($metaMapTa[$item->peserta_didik_id]?->foto_url ?? null);
        }

        // 4. Tab: Alumni
        $alumniQuery = DB::table('peserta_didik_tidak_aktif')
            ->where(function ($b) {
                $b->where('status_keluar', 'Alumni')
                  ->orWhere('alasan_keluar', 'like', '%Lulus%')
                  ->orWhere('alasan_keluar', 'like', '%Tamat%');
            })
            ->select('id', 'peserta_didik_id', 'nama', 'nisn', 'nipd', 'nik', 'jenis_kelamin', 'nama_rombel_terakhir as rombel_terakhir', 'alasan_keluar', 'tanggal_keluar', 'tahun_lulus', 'foto_path');

        if ($q !== '' && $activeTab === 'alumni') {
            $alumniQuery->where(function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%")
                  ->orWhere('nisn', 'like', "%{$q}%")
                  ->orWhere('nipd', 'like', "%{$q}%")
                  ->orWhere('nik', 'like', "%{$q}%")
                  ->orWhere('nama_rombel_terakhir', 'like', "%{$q}%");
            });
        }
        if ($tahunLulus !== '') {
            $alumniQuery->where(function ($b) use ($tahunLulus) {
                $b->where('tahun_lulus', $tahunLulus)
                  ->orWhereYear('tanggal_keluar', $tahunLulus);
            });
        }
        $alumniList = $alumniQuery->orderBy('tanggal_keluar', 'desc')->paginate($perPage, ['*'], 'alumni_page')->withQueryString();
        $alumniPdIds = $alumniList->pluck('peserta_didik_id')->filter()->values()->all();
        $metaMapAlumni = collect();
        if (!empty($alumniPdIds) && Schema::hasTable('peserta_didik_meta')) {
            $metaMapAlumni = \App\Models\PesertaDidikMeta::whereIn('peserta_didik_id', $alumniPdIds)->get()->keyBy('peserta_didik_id');
        }
        foreach ($alumniList as $item) {
            $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : ($metaMapAlumni[$item->peserta_didik_id]?->foto_url ?? null);
        }

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
                'pd.nik',
                'rb.nama as rombel_nama',
                'kbv.id as berkas_id',
                'kbv.akta_kelahiran',
                'kbv.kartu_keluarga',
                'kbv.ijazah_smp',
                'kbv.ktp_orang_tua',
                'kbv.kip_pip',
                'kbv.catatan_verifikasi',
                'kbv.verified_by',
                'kbv.verified_at'
            );

        if ($q !== '' && $activeTab === 'berkas') {
            $berkasQuery->where(function ($b) use ($q) {
                $b->where('pd.nama', 'like', "%{$q}%")
                  ->orWhere('pd.nisn', 'like', "%{$q}%")
                  ->orWhere('pd.nipd', 'like', "%{$q}%")
                  ->orWhere('pd.nik', 'like', "%{$q}%");
            });
        }
        $berkasList = $berkasQuery->orderBy('pd.nama', 'asc')->paginate($perPage, ['*'], 'berkas_page')->withQueryString();
        $berkasPdIds = $berkasList->pluck('peserta_didik_id')->filter()->values()->all();
        $metaMapBerkas = collect();
        if (!empty($berkasPdIds) && Schema::hasTable('peserta_didik_meta')) {
            $metaMapBerkas = \App\Models\PesertaDidikMeta::whereIn('peserta_didik_id', $berkasPdIds)->get()->keyBy('peserta_didik_id');
        }
        foreach ($berkasList as $item) {
            $item->foto_url = $metaMapBerkas[$item->peserta_didik_id]?->foto_url ?? null;
        }

        // 6. Tab: Usulan Perubahan Data Siswa
        $usulanQuery = SiswaUsulanPerubahan::with('siswa')->orderBy('created_at', 'desc');
        if ($q !== '' && $activeTab === 'usulan') {
            $usulanQuery->whereHas('siswa', function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%")
                  ->orWhere('nisn', 'like', "%{$q}%")
                  ->orWhere('nipd', 'like', "%{$q}%")
                  ->orWhere('nik', 'like', "%{$q}%");
            })->orWhere('kolom_perubahan', 'like', "%{$q}%")
              ->orWhere('alasan', 'like', "%{$q}%");
        }
        $usulanList = $usulanQuery->paginate($perPage, ['*'], 'usulan_page')->withQueryString();
        $usulanPdIds = $usulanList->pluck('peserta_didik_id')->filter()->values()->all();
        $metaMapUsulan = collect();
        if (!empty($usulanPdIds) && Schema::hasTable('peserta_didik_meta')) {
            $metaMapUsulan = \App\Models\PesertaDidikMeta::whereIn('peserta_didik_id', $usulanPdIds)->get()->keyBy('peserta_didik_id');
        }
        foreach ($usulanList as $item) {
            $item->foto_url = $metaMapUsulan[$item->peserta_didik_id]?->foto_url ?? null;
        }

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
            'perPage',
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

    /**
     * Detail lengkap biodata peserta didik (JSON) untuk Modal Biodata.
     */
    public function show($id)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'read') && !RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'read')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $pesertaDidik = DB::table('peserta_didik')
            ->where('peserta_didik_id', $id)
            ->orWhere('nisn', $id)
            ->orWhere('nipd', $id)
            ->first();

        // Jika tidak ada di tabel peserta_didik aktif, cari di peserta_didik_tidak_aktif
        if (!$pesertaDidik && Schema::hasTable('peserta_didik_tidak_aktif')) {
            $pesertaDidik = DB::table('peserta_didik_tidak_aktif')
                ->where('peserta_didik_id', $id)
                ->orWhere('nisn', $id)
                ->orWhere('nipd', $id)
                ->first();
        }

        if (!$pesertaDidik) {
            return response()->json(['status' => 'error', 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        $anggota = null;
        if (Schema::hasTable('anggota_rombel')) {
            $anggota = DB::table('anggota_rombel')
                ->where('peserta_didik_id', $pesertaDidik->peserta_didik_id)
                ->first();
        }

        $meta = null;
        if (Schema::hasTable('peserta_didik_meta')) {
            $meta = \App\Models\PesertaDidikMeta::where('peserta_didik_id', $pesertaDidik->peserta_didik_id)->first();
        }

        $rombelId = $pesertaDidik->rombongan_belajar_id ?? ($anggota->rombongan_belajar_id ?? null);
        $pembelajaran = collect();
        if (!empty($rombelId) && Schema::hasTable('pembelajaran')) {
            $pembelajaran = DB::table('pembelajaran')
                ->leftJoin('gtk', 'pembelajaran.ptk_id', '=', 'gtk.ptk_id')
                ->where('pembelajaran.rombongan_belajar_id', $rombelId)
                ->select(
                    'pembelajaran.pembelajaran_id',
                    'pembelajaran.nama_mata_pelajaran',
                    'pembelajaran.mata_pelajaran_id_str',
                    'pembelajaran.jam_mengajar_per_minggu',
                    'pembelajaran.status_di_kurikulum_str',
                    'gtk.nama as nama_guru',
                    'gtk.nuptk',
                    'gtk.nip'
                )
                ->orderBy('pembelajaran.nama_mata_pelajaran', 'asc')
                ->get();
        }

        $fotoUrl = !empty($pesertaDidik->foto_path) 
            ? asset('storage/' . ltrim($pesertaDidik->foto_path, '/')) 
            : ($meta?->foto_url ?? null);

        return response()->json([
            'status'       => 'success',
            'data'         => $pesertaDidik,
            'anggota'      => $anggota,
            'meta'         => $meta,
            'foto_url'     => $fotoUrl,
            'foto_size'    => $meta?->formatted_foto_size,
            'pembelajaran' => $pembelajaran,
            'total_mapel'  => $pembelajaran->count(),
            'total_jam'    => $pembelajaran->sum(fn($p) => (int) ($p->jam_mengajar_per_minggu ?? 0)),
        ]);
    }
}
