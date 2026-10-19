<?php

namespace App\Http\Controllers;

use App\Models\AdminAktivitas;
use App\Models\KesiswaanBerkasVerifikasi;
use App\Models\PesertaDidik;
use App\Models\PesertaDidikBerkas;
use App\Models\PesertaDidikMeta;
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
        $status = trim($request->get('status', ''));
        $tahunLulus = trim($request->get('tahun_lulus', ''));

        $sort = in_array($request->get('sort'), ['nama', 'nisn', 'nipd', 'jenis_kelamin', 'nama_rombel', 'tingkat_pendidikan_id', 'nama_rombel_terakhir', 'tingkat_pendidikan_terakhir', 'tahun_lulus', 'status_keluar', 'tanggal_keluar'], true) ? $request->get('sort') : 'nama';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $perPageVal = $request->get('perPage', $request->get('per_page', '25'));
        $perPage = in_array($perPageVal, ['10', '15', '25', '50', '100']) ? (int)$perPageVal : 25;

        // ID peserta didik aktif untuk pemisah tidak aktif
        $activePdIds = Schema::hasTable('peserta_didik')
            ? DB::table('peserta_didik')->pluck('peserta_didik_id')->filter()->all()
            : [];

        $baseTidakAktif = DB::table('peserta_didik_tidak_aktif');
        if (!empty($activePdIds)) {
            $baseTidakAktif->whereNotIn('peserta_didik_id', $activePdIds);
        }

        // 1. Statistik Ringkas Disesuaikan per Tab
        $totalAktif = PesertaDidik::count();
        $aktifLaki = PesertaDidik::where('jenis_kelamin', 'L')->count();
        $aktifPerempuan = PesertaDidik::where('jenis_kelamin', 'P')->count();
        $totalRombelAktif = PesertaDidik::whereNotNull('nama_rombel')->where('nama_rombel', '<>', '')->distinct()->count('nama_rombel');
        $totalKoordinator = Schema::hasTable('peserta_didik_meta') ? PesertaDidikMeta::where('is_koordinator', true)->count() : 0;

        $taMutasi = (clone $baseTidakAktif)->where('status_keluar', 'Mutasi')->count();
        $taDikeluarkan = (clone $baseTidakAktif)->where('status_keluar', 'Dikeluarkan')->count();
        $taMengundurkan = (clone $baseTidakAktif)->where('status_keluar', 'Mengundurkan Diri')->count();
        $taLainnya = (clone $baseTidakAktif)->whereNotIn('status_keluar', ['Alumni', 'Mutasi', 'Dikeluarkan', 'Mengundurkan Diri'])->count();
        $totalTidakAktif = $taMutasi + $taDikeluarkan + $taMengundurkan + $taLainnya;

        $totalAlumni = (clone $baseTidakAktif)->where('status_keluar', 'Alumni')->count();
        $alumniLaki = (clone $baseTidakAktif)->where('status_keluar', 'Alumni')->where('jenis_kelamin', 'L')->count();
        $alumniPerempuan = (clone $baseTidakAktif)->where('status_keluar', 'Alumni')->where('jenis_kelamin', 'P')->count();
        $alumniTahunCount = (clone $baseTidakAktif)->where('status_keluar', 'Alumni')->whereNotNull('tahun_lulus')->where('tahun_lulus', '<>', '')->distinct()->count('tahun_lulus');

        $totalBerkasLengkap = KesiswaanBerkasVerifikasi::where('akta_kelahiran', true)
            ->where('kartu_keluarga', true)
            ->where('ijazah_smp', true)
            ->count();
        $berkasMenungguCount = Schema::hasTable('peserta_didik_berkas')
            ? DB::table('peserta_didik_berkas')->where('status', 'menunggu')->distinct()->count('peserta_didik_id')
            : 0;
        $berkasDitolakCount = Schema::hasTable('peserta_didik_berkas')
            ? DB::table('peserta_didik_berkas')->where('status', 'tidak_valid')->distinct()->count('peserta_didik_id')
            : 0;
        $berkasBelumLengkap = max(0, $totalAktif - $totalBerkasLengkap);

        $totalUsulan = SiswaUsulanPerubahan::distinct()->count('peserta_didik_id');
        $usulanMenunggu = SiswaUsulanPerubahan::where('status', 'menunggu')->distinct()->count('peserta_didik_id');
        $usulanDisetujui = SiswaUsulanPerubahan::where('status', 'disetujui')->distinct()->count('peserta_didik_id');
        $usulanDapodik = SiswaUsulanPerubahan::where('status', 'sudah_ke_dapodik')->distinct()->count('peserta_didik_id');
        $usulanDitolak = SiswaUsulanPerubahan::where('status', 'ditolak')->distinct()->count('peserta_didik_id');

        $stats = [
            // Tab Aktif
            'total_aktif'          => $totalAktif,
            'aktif_laki'           => $aktifLaki,
            'aktif_perempuan'      => $aktifPerempuan,
            'total_rombel'         => $totalRombelAktif,
            'total_koordinator'    => $totalKoordinator,

            // Tab Tidak Aktif
            'total_tidak_aktif'        => $totalTidakAktif,
            'ta_mutasi'                => $taMutasi,
            'ta_dikeluarkan'           => $taDikeluarkan,
            'ta_mengundurkan'          => $taMengundurkan,
            'ta_mengundurkan_lainnya'  => $taMengundurkan + $taLainnya,
            'ta_lainnya'               => $taLainnya,

            // Tab Berkas
            'berkas_total_siswa'   => $totalAktif,
            'berkas_lengkap'       => $totalBerkasLengkap,
            'berkas_menunggu'      => $berkasMenungguCount,
            'berkas_ditolak'       => $berkasDitolakCount,
            'berkas_belum_lengkap' => $berkasBelumLengkap,

            // Tab Usulan
            'usulan_total'         => $totalUsulan,
            'usulan_menunggu'      => $usulanMenunggu,
            'usulan_disetujui'     => $usulanDisetujui,
            'usulan_dapodik'       => $usulanDapodik,
            'usulan_ditolak'       => $usulanDitolak,

            // Tab Alumni
            'total_alumni'         => $totalAlumni,
            'alumni_laki'          => $alumniLaki,
            'alumni_perempuan'     => $alumniPerempuan,
            'alumni_angkatan'      => $alumniTahunCount,
        ];

        // Daftar rombel aktif untuk filter
        $filterRombel = DB::table('peserta_didik')
            ->whereNotNull('nama_rombel')
            ->where('nama_rombel', '<>', '')
            ->distinct()
            ->pluck('nama_rombel')
            ->sort()
            ->values();

        // Daftar rombel tidak aktif untuk filter
        $filterRombelTidakAktif = (clone $baseTidakAktif)
            ->whereNotNull('nama_rombel_terakhir')
            ->where('nama_rombel_terakhir', '<>', '')
            ->distinct()
            ->pluck('nama_rombel_terakhir')
            ->sort()
            ->values();

        // 2. Tab: Peserta Didik Aktif (Format & Data Selaras Penuh dengan Modul Administrator)
        $aktifQuery = DB::table('peserta_didik')
            ->select(
                'peserta_didik_id',
                'nama',
                'nisn',
                'nipd',
                'nik',
                'jenis_kelamin',
                'tempat_lahir',
                'tanggal_lahir',
                'agama_id_str as agama',
                'nama_rombel',
                'rombongan_belajar_id',
                'tingkat_pendidikan_id',
                'kurikulum_id_str as kurikulum',
                'nama_ayah',
                'nama_ibu',
                'nama_wali',
                'email',
                'alamat_jalan',
                'nomor_telepon_seluler as no_hp'
            );

        if ($q !== '' && $activeTab === 'aktif') {
            $aktifQuery->where(function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%")
                  ->orWhere('nisn', 'like', "%{$q}%")
                  ->orWhere('nipd', 'like', "%{$q}%")
                  ->orWhere('nik', 'like', "%{$q}%")
                  ->orWhere('nama_rombel', 'like', "%{$q}%");
            });
        }
        if ($rombel !== '' && $activeTab === 'aktif') {
            $aktifQuery->where('nama_rombel', $rombel);
        }
        if ($gender !== '' && $activeTab === 'aktif') {
            $aktifQuery->where('jenis_kelamin', $gender);
        }

        // Urutan
        $sortAktif = in_array($sort, ['nama', 'nisn', 'nipd', 'jenis_kelamin', 'nama_rombel', 'tingkat_pendidikan_id'], true) ? $sort : 'nama';
        if ($sortAktif === 'nama') {
            $aktifQuery->orderByRaw("LOWER(TRIM(nama)) {$sortDir}")->orderBy('nisn', 'asc');
        } else {
            $aktifQuery->orderBy($sortAktif, $sortDir)->orderByRaw("LOWER(TRIM(nama)) asc");
        }
        $aktifList = $aktifQuery->paginate($perPage, ['*'], 'aktif_page')->withQueryString();

        $aktifPdIds = $aktifList->pluck('peserta_didik_id')->filter()->values()->all();
        $metaMapAktif = collect();
        if (!empty($aktifPdIds) && Schema::hasTable('peserta_didik_meta')) {
            $metaMapAktif = \App\Models\PesertaDidikMeta::whereIn('peserta_didik_id', $aktifPdIds)->get()->keyBy('peserta_didik_id');
        }
        foreach ($aktifList as $item) {
            $meta = $metaMapAktif[$item->peserta_didik_id] ?? null;
            $item->foto_url = $meta?->foto_url;
            $item->foto_size = $meta?->formatted_foto_size;
            $item->is_koordinator = (bool) ($meta?->is_koordinator ?? false);
            $item->jabatan_koordinator = $meta?->jabatan_koordinator ?? 'Koordinator Kelas';
        }

        // 3. Tab: Peserta Didik Tidak Aktif (Selaras Penuh dengan Modul Administrator)
        $tidakAktifQuery = (clone $baseTidakAktif)
            ->select(
                'id',
                'peserta_didik_id',
                'nama',
                'nisn',
                'nipd',
                'nik',
                'jenis_kelamin',
                'tempat_lahir',
                'tanggal_lahir',
                'nama_rombel_terakhir as rombel_terakhir',
                'tingkat_pendidikan_terakhir',
                'alasan_keluar',
                'tanggal_keluar',
                'status_keluar',
                'tahun_lulus',
                'nomor_telepon_seluler',
                'alamat_jalan',
                'foto_path'
            );

        if ($q !== '' && $activeTab === 'tidak_aktif') {
            $tidakAktifQuery->where(function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%")
                  ->orWhere('nisn', 'like', "%{$q}%")
                  ->orWhere('nipd', 'like', "%{$q}%")
                  ->orWhere('nik', 'like', "%{$q}%")
                  ->orWhere('nama_rombel_terakhir', 'like', "%{$q}%")
                  ->orWhere('alasan_keluar', 'like', "%{$q}%");
            });
        }
        if ($status !== '' && $activeTab === 'tidak_aktif') {
            $tidakAktifQuery->where('status_keluar', $status);
        }
        if ($rombel !== '' && $activeTab === 'tidak_aktif') {
            $tidakAktifQuery->where('nama_rombel_terakhir', $rombel);
        }
        if ($gender !== '' && $activeTab === 'tidak_aktif') {
            $tidakAktifQuery->where('jenis_kelamin', $gender);
        }

        $sortTa = in_array($sort, ['nama', 'nisn', 'nipd', 'jenis_kelamin', 'nama_rombel_terakhir', 'tingkat_pendidikan_terakhir', 'tahun_lulus', 'status_keluar', 'tanggal_keluar'], true) ? $sort : 'tanggal_keluar';
        if ($sortTa === 'nama') {
            $tidakAktifQuery->orderByRaw("LOWER(TRIM(nama)) {$sortDir}")->orderBy('nisn', 'asc');
        } else {
            $tidakAktifQuery->orderBy($sortTa, $sortDir)->orderByRaw("LOWER(TRIM(nama)) asc");
        }
        $tidakAktifList = $tidakAktifQuery->paginate($perPage, ['*'], 'tidak_aktif_page')->withQueryString();
        $taPdIds = $tidakAktifList->pluck('peserta_didik_id')->filter()->values()->all();
        $metaMapTa = collect();
        if (!empty($taPdIds) && Schema::hasTable('peserta_didik_meta')) {
            $metaMapTa = \App\Models\PesertaDidikMeta::whereIn('peserta_didik_id', $taPdIds)->get()->keyBy('peserta_didik_id');
        }
        foreach ($tidakAktifList as $item) {
            $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : ($metaMapTa[$item->peserta_didik_id]?->foto_url ?? null);
        }

        // 4. Tab: Alumni
        $alumniQuery = (clone $baseTidakAktif)
            ->where(function ($b) {
                $b->where('status_keluar', 'Alumni')
                  ->orWhere('alasan_keluar', 'like', '%Lulus%')
                  ->orWhere('alasan_keluar', 'like', '%Tamat%');
            })
            ->select(
                'id',
                'peserta_didik_id',
                'nama',
                'nisn',
                'nipd',
                'nik',
                'jenis_kelamin',
                'nama_rombel_terakhir as rombel_terakhir',
                'alasan_keluar',
                'tanggal_keluar',
                'tahun_lulus',
                'nomor_telepon_seluler',
                'alamat_jalan',
                'foto_path'
            );

        if ($q !== '' && $activeTab === 'alumni') {
            $alumniQuery->where(function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%")
                  ->orWhere('nisn', 'like', "%{$q}%")
                  ->orWhere('nipd', 'like', "%{$q}%")
                  ->orWhere('nik', 'like', "%{$q}%")
                  ->orWhere('nama_rombel_terakhir', 'like', "%{$q}%");
            });
        }
        if ($rombel !== '' && $activeTab === 'alumni') {
            $alumniQuery->where('nama_rombel_terakhir', $rombel);
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

        // 5. Tab: Verifikasi Berkas Fisik & Digital (Dengan Filter Rombel & Status)
        $berkasQuery = DB::table('peserta_didik as pd')
            ->leftJoin('kesiswaan_berkas_verifikasi as kbv', 'pd.peserta_didik_id', '=', 'kbv.peserta_didik_id')
            ->select(
                'pd.peserta_didik_id',
                'pd.nama',
                'pd.nisn',
                'pd.nipd',
                'pd.nik',
                'pd.nama_rombel as rombel_nama',
                'pd.jenis_kelamin',
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
        if ($rombel !== '' && $activeTab === 'berkas') {
            $berkasQuery->where('pd.nama_rombel', $rombel);
        }
        if ($status !== '' && $activeTab === 'berkas') {
            if ($status === 'lengkap') {
                $berkasQuery->where('kbv.akta_kelahiran', true)
                            ->where('kbv.kartu_keluarga', true)
                            ->where('kbv.ijazah_smp', true);
            } elseif ($status === 'belum_lengkap') {
                $berkasQuery->where(function ($sq) {
                    $sq->whereNull('kbv.id')
                       ->orWhere('kbv.akta_kelahiran', false)
                       ->orWhere('kbv.kartu_keluarga', false)
                       ->orWhere('kbv.ijazah_smp', false);
                });
            } elseif ($status === 'menunggu') {
                $berkasQuery->whereExists(function ($sq) {
                    $sq->select(DB::raw(1))
                       ->from('peserta_didik_berkas')
                       ->whereColumn('peserta_didik_berkas.peserta_didik_id', 'pd.peserta_didik_id')
                       ->where('peserta_didik_berkas.status', 'menunggu');
                });
            } elseif ($status === 'tidak_valid') {
                $berkasQuery->whereExists(function ($sq) {
                    $sq->select(DB::raw(1))
                       ->from('peserta_didik_berkas')
                       ->whereColumn('peserta_didik_berkas.peserta_didik_id', 'pd.peserta_didik_id')
                       ->where('peserta_didik_berkas.status', 'tidak_valid');
                });
            }
        }

        $hasCustomSortBerkas = ($activeTab === 'berkas' && $request->has('sort'));
        if ($hasCustomSortBerkas) {
            $sortBerkas = in_array($sort, ['nama', 'nisn', 'nipd', 'nama_rombel'], true) ? $sort : 'nama';
            if ($sortBerkas === 'nama') {
                $berkasQuery->orderByRaw("LOWER(TRIM(pd.nama)) {$sortDir}");
            } elseif ($sortBerkas === 'nisn') {
                $berkasQuery->orderBy('pd.nisn', $sortDir);
            } elseif ($sortBerkas === 'nama_rombel') {
                $berkasQuery->orderBy('pd.nama_rombel', $sortDir);
            }
        } else {
            // Default Validasi Berkas:
            // 1. Prioritas Status: Menunggu Validasi (paling awal) -> Ada Ditolak -> Belum Lengkap -> Lengkap & Valid (terakhir)
            // 2. Berkas terlama/terdahulu melakukan upload dokumen (FIFO)
            $berkasQuery->orderByRaw("CASE
                WHEN EXISTS (
                    SELECT 1 FROM peserta_didik_berkas pdb
                    WHERE pdb.peserta_didik_id = pd.peserta_didik_id
                      AND pdb.status = 'menunggu'
                ) THEN 1
                WHEN EXISTS (
                    SELECT 1 FROM peserta_didik_berkas pdb
                    WHERE pdb.peserta_didik_id = pd.peserta_didik_id
                      AND pdb.status = 'tidak_valid'
                ) THEN 2
                WHEN (COALESCE(kbv.akta_kelahiran, 0) = 0 OR COALESCE(kbv.kartu_keluarga, 0) = 0 OR COALESCE(kbv.ijazah_smp, 0) = 0) THEN 3
                ELSE 4
            END ASC")
            ->orderByRaw("(
                SELECT MIN(pdb.created_at)
                FROM peserta_didik_berkas pdb
                WHERE pdb.peserta_didik_id = pd.peserta_didik_id
                  AND pdb.file_path IS NOT NULL
                  AND pdb.file_path <> ''
            ) IS NULL ASC")
            ->orderByRaw("(
                SELECT MIN(pdb.created_at)
                FROM peserta_didik_berkas pdb
                WHERE pdb.peserta_didik_id = pd.peserta_didik_id
                  AND pdb.file_path IS NOT NULL
                  AND pdb.file_path <> ''
            ) ASC")
            ->orderBy('pd.nama', 'asc');
        }
        $berkasList = $berkasQuery->paginate($perPage, ['*'], 'berkas_page')->withQueryString();

        $berkasPdIds = $berkasList->pluck('peserta_didik_id')->filter()->values()->all();
        $metaMapBerkas = collect();
        if (!empty($berkasPdIds) && Schema::hasTable('peserta_didik_meta')) {
            $metaMapBerkas = \App\Models\PesertaDidikMeta::whereIn('peserta_didik_id', $berkasPdIds)->get()->keyBy('peserta_didik_id');
        }
        $uploadedBerkasMap = collect();
        if (!empty($berkasPdIds) && Schema::hasTable('peserta_didik_berkas')) {
            $uploadedBerkasMap = PesertaDidikBerkas::whereIn('peserta_didik_id', $berkasPdIds)
                ->get()
                ->groupBy('peserta_didik_id');
        }
        foreach ($berkasList as $item) {
            $item->foto_url = $metaMapBerkas[$item->peserta_didik_id]?->foto_url ?? null;
            $studentFiles = $uploadedBerkasMap[$item->peserta_didik_id] ?? collect();
            $item->files = $studentFiles->keyBy('jenis_berkas');
            $item->total_uploaded = $studentFiles->count();
            $item->valid_count = $studentFiles->where('status', 'valid')->count();
            $item->tidak_valid_count = $studentFiles->where('status', 'tidak_valid')->count();
            $item->menunggu_count = $studentFiles->where('status', 'menunggu')->count();
        }

        // 6. Tab: Usulan Perubahan Data Siswa (Khusus 1 Baris per Peserta Didik)
        $usulanPdQuery = SiswaUsulanPerubahan::query()
            ->select(
                'peserta_didik_id',
                DB::raw('MAX(created_at) as latest_created_at'),
                DB::raw("MIN(CASE status
                    WHEN 'menunggu' THEN 1
                    WHEN 'disetujui' THEN 2
                    WHEN 'ditolak' THEN 3
                    WHEN 'sudah_ke_dapodik' THEN 4
                    ELSE 5
                END) as priority_rank")
            )
            ->groupBy('peserta_didik_id');

        if ($q !== '' && $activeTab === 'usulan') {
            $usulanPdQuery->where(function ($sub) use ($q) {
                $sub->whereHas('siswa', function ($b) use ($q) {
                    $b->where('nama', 'like', "%{$q}%")
                      ->orWhere('nisn', 'like', "%{$q}%")
                      ->orWhere('nipd', 'like', "%{$q}%")
                      ->orWhere('nik', 'like', "%{$q}%");
                })->orWhere('kolom_perubahan', 'like', "%{$q}%")
                  ->orWhere('alasan', 'like', "%{$q}%");
            });
        }
        if ($rombel !== '' && $activeTab === 'usulan') {
            $usulanPdQuery->whereHas('siswa', function ($sq) use ($rombel) {
                $sq->where('nama_rombel', $rombel);
            });
        }
        if ($status !== '' && $activeTab === 'usulan') {
            $usulanPdQuery->where('status', $status);
        }

        // Urutan: Prioritas yang menunggu review terlebih dahulu
        $hasCustomSortUsulan = ($activeTab === 'usulan' && $request->has('sort'));
        if ($hasCustomSortUsulan) {
            $usulanList = $usulanPdQuery->orderBy('latest_created_at', $sortDir)
                ->paginate($perPage, ['*'], 'usulan_page')
                ->withQueryString();
        } else {
            $usulanList = $usulanPdQuery->orderBy('priority_rank', 'asc')
                ->orderBy('latest_created_at', 'desc')
                ->paginate($perPage, ['*'], 'usulan_page')
                ->withQueryString();
        }

        $usulanPdIds = $usulanList->pluck('peserta_didik_id')->filter()->values()->all();
        $siswaMapUsulan = collect();
        if (!empty($usulanPdIds)) {
            $siswaMapUsulan = PesertaDidik::whereIn('peserta_didik_id', $usulanPdIds)->get()->keyBy('peserta_didik_id');
            if (Schema::hasTable('peserta_didik_tidak_aktif')) {
                $missingIds = array_diff($usulanPdIds, $siswaMapUsulan->keys()->all());
                if (!empty($missingIds)) {
                    $taSiswa = DB::table('peserta_didik_tidak_aktif')->whereIn('peserta_didik_id', $missingIds)->get()->keyBy('peserta_didik_id');
                    $siswaMapUsulan = $siswaMapUsulan->merge($taSiswa);
                }
            }
        }

        $allProposalsMap = collect();
        if (!empty($usulanPdIds)) {
            $allProposalsMap = SiswaUsulanPerubahan::whereIn('peserta_didik_id', $usulanPdIds)
                ->orderBy('created_at', 'asc')
                ->get()
                ->groupBy('peserta_didik_id');
        }

        $metaMapUsulan = collect();
        if (!empty($usulanPdIds) && Schema::hasTable('peserta_didik_meta')) {
            $metaMapUsulan = PesertaDidikMeta::whereIn('peserta_didik_id', $usulanPdIds)->get()->keyBy('peserta_didik_id');
        }

        $prereqMapUsulan = collect();
        if (!empty($usulanPdIds) && Schema::hasTable('peserta_didik_berkas')) {
            $prereqMapUsulan = PesertaDidikBerkas::whereIn('peserta_didik_id', $usulanPdIds)
                ->whereIn('jenis_berkas', ['kartu_keluarga', 'ijazah_smp', 'akta_kelahiran'])
                ->get()
                ->groupBy('peserta_didik_id');
        }

        $kolomLabelsMap = [
            'nama' => 'Nama Lengkap',
            'jenis_kelamin' => 'Jenis Kelamin',
            'nik' => 'NIK',
            'no_kk' => 'No. KK',
            'no_registrasi_akta_lahir' => 'No. Akta Lahir',
            'kewarganegaraan' => 'Kewarganegaraan',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'agama_id' => 'Agama',
            'alamat_jalan' => 'Alamat Jalan',
            'rt' => 'RT',
            'rw' => 'RW',
            'desa_kelurahan' => 'Desa / Kelurahan',
            'kecamatan' => 'Kecamatan',
            'kabupaten_kota' => 'Kabupaten / Kota',
            'provinsi' => 'Provinsi',
            'kode_pos' => 'Kode Pos',
            'tempat_tinggal_id' => 'Tempat Tinggal',
            'transportasi_id' => 'Moda Transportasi',
            'anak_keberapa' => 'Anak Keberapa',
            'nama_ayah' => 'Nama Ayah Kandung',
            'nik_ayah' => 'NIK Ayah',
            'tahun_lahir_ayah' => 'Tahun Lahir Ayah',
            'pendidikan_ayah_id' => 'Pendidikan Ayah',
            'pekerjaan_ayah_id' => 'Pekerjaan Ayah',
            'penghasilan_ayah_id' => 'Penghasilan Ayah',
            'nama_ibu' => 'Nama Ibu Kandung',
            'nik_ibu' => 'NIK Ibu',
            'tahun_lahir_ibu' => 'Tahun Lahir Ibu',
            'pendidikan_ibu_id' => 'Pendidikan Ibu',
            'pekerjaan_ibu_id' => 'Pekerjaan Ibu',
            'penghasilan_ibu_id' => 'Penghasilan Ibu',
            'nama_wali' => 'Nama Wali',
            'nik_wali' => 'NIK Wali',
            'nomor_telepon_rumah' => 'Telepon Rumah',
            'nomor_telepon_seluler' => 'No. HP / WA',
            'email' => 'Email',
            'sekolah_asal' => 'Sekolah Asal',
            'tinggi_badan' => 'Tinggi Badan',
            'berat_badan' => 'Berat Badan',
            'lingkar_kepala' => 'Lingkar Kepala',
            'jarak_rumah_sekolah' => 'Jarak ke Sekolah',
            'jarak_rumah_sekolah_km' => 'Jarak (km)',
            'waktu_tempuh_jam' => 'Waktu Tempuh (Jam)',
            'waktu_tempuh_menit' => 'Waktu Tempuh (Menit)',
            'jumlah_saudara_kandung' => 'Saudara Kandung',
        ];

        $usulanList->getCollection()->transform(function ($item) use ($siswaMapUsulan, $allProposalsMap, $metaMapUsulan, $prereqMapUsulan, $kolomLabelsMap) {
            $pdId = $item->peserta_didik_id;
            $siswa = $siswaMapUsulan->get($pdId);
            $items = $allProposalsMap->get($pdId, collect());
            $bList = $prereqMapUsulan->get($pdId, collect());

            $total = $items->count();
            $menungguCount = $items->where('status', 'menunggu')->count();
            $disetujuiCount = $items->where('status', 'disetujui')->count();
            $dapodikCount = $items->where('status', 'sudah_ke_dapodik')->count();
            $ditolakCount = $items->where('status', 'ditolak')->count();

            $statusAgregat = 'menunggu';
            if ($menungguCount > 0) {
                $statusAgregat = 'menunggu';
            } elseif ($disetujuiCount > 0) {
                $statusAgregat = 'disetujui';
            } elseif ($ditolakCount === $total && $total > 0) {
                $statusAgregat = 'ditolak';
            } elseif ($dapodikCount === $total && $total > 0) {
                $statusAgregat = 'sudah_ke_dapodik';
            }

            $kolomList = $items->map(function ($u) use ($kolomLabelsMap) {
                return [
                    'id' => $u->id,
                    'kolom' => $u->kolom_perubahan,
                    'label' => $kolomLabelsMap[$u->kolom_perubahan] ?? ucwords(str_replace('_', ' ', $u->kolom_perubahan)),
                    'nilai_lama' => $u->nilai_lama ?: '(Kosong)',
                    'nilai_baru' => $u->nilai_baru,
                    'alasan' => $u->alasan,
                    'status' => $u->status,
                    'berkas_bukti' => $u->berkas_bukti,
                ];
            })->all();

            $item->siswa = $siswa;
            $item->foto_url = $metaMapUsulan->get($pdId)?->foto_url ?? null;
            $item->items = $items;
            $item->kolom_list = $kolomList;
            $item->total_usulan = $total;
            $item->menunggu_count = $menungguCount;
            $item->disetujui_count = $disetujuiCount;
            $item->dapodik_count = $dapodikCount;
            $item->ditolak_count = $ditolakCount;
            $item->status = $statusAgregat;
            $item->alasan = $items->first()?->alasan ?: 'Penyesuaian formulir identitas mandiri.';
            $item->kk_status = $bList->firstWhere('jenis_berkas', 'kartu_keluarga')?->status ?? 'belum_unggah';
            $item->ijazah_status = $bList->firstWhere('jenis_berkas', 'ijazah_smp')?->status ?? 'belum_unggah';
            $item->akta_status = $bList->firstWhere('jenis_berkas', 'akta_kelahiran')?->status ?? 'belum_unggah';
            $item->berkas_bukti_path = $items->first(fn($u) => !empty($u->berkas_bukti))?->berkas_bukti;

            return $item;
        });

        return view('dashboard.kesiswaan.peserta-didik', compact(
            'stats',
            'activeTab',
            'q',
            'rombel',
            'gender',
            'status',
            'tahunLulus',
            'sort',
            'sortDir',
            'perPage',
            'filterRombel',
            'filterRombelTidakAktif',
            'aktifList',
            'tidakAktifList',
            'alumniList',
            'berkasList',
            'usulanList',
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
     * Ambil data detail lengkap usulan perubahan siswa (JSON) untuk Modal Formulir Pengelolaan Usulan
     * Mendukung pemanggilan berdasarkan peserta_didik_id atau id usulan tunggal
     */
    public function getUsulanDetail($id)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canAccess = in_array($role, ['admin', 'tendik', 'guru'], true)
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_peserta_didik', 'read')
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'read')
            || RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'read')
            || RolePermission::canAccess($user ?: $role, 'menu_wali_kelas_validasi', 'read')
            || RolePermission::canAccess($user ?: $role, 'menu_wali_kelas', 'read')
            || RolePermission::isWaliKelasOrAdmin($user);

        if (!$canAccess) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        // Cari siswa dan daftar usulannya
        $pd = PesertaDidik::where('peserta_didik_id', $id)->first();
        if (!$pd && Schema::hasTable('peserta_didik_tidak_aktif')) {
            $pd = DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->first();
        }

        if ($pd) {
            $pdId = $pd->peserta_didik_id;
            $usulanList = SiswaUsulanPerubahan::where('peserta_didik_id', $pdId)
                ->orderBy('created_at', 'asc')
                ->get();
        } else {
            $single = SiswaUsulanPerubahan::with('siswa')->find($id);
            if (!$single) {
                return response()->json(['status' => 'error', 'message' => 'Data usulan atau peserta didik tidak ditemukan.'], 404);
            }
            $pd = $single->siswa;
            $pdId = $single->peserta_didik_id;
            $usulanList = SiswaUsulanPerubahan::where('peserta_didik_id', $pdId)
                ->orderBy('created_at', 'asc')
                ->get();
        }

        // Foto siswa
        $meta = null;
        if (Schema::hasTable('peserta_didik_meta') && $pd) {
            $meta = PesertaDidikMeta::where('peserta_didik_id', $pdId)->first();
        }

        // Berkas wajib KK, Ijazah, dan Akta siswa
        $berkas = collect();
        if (Schema::hasTable('peserta_didik_berkas') && $pd) {
            $berkas = PesertaDidikBerkas::where('peserta_didik_id', $pdId)
                ->whereIn('jenis_berkas', ['kartu_keluarga', 'ijazah_smp', 'akta_kelahiran'])
                ->get()
                ->keyBy('jenis_berkas');
        }

        $kk = $berkas->get('kartu_keluarga');
        $ijazah = $berkas->get('ijazah_smp');
        $akta = $berkas->get('akta_kelahiran');

        // Peta label nama kolom yang ramah pengguna
        $kolomLabels = [
            'nama' => 'Nama Lengkap Siswa',
            'jenis_kelamin' => 'Jenis Kelamin',
            'nik' => 'Nomor Induk Kependudukan (NIK)',
            'no_kk' => 'Nomor Kartu Keluarga (KK)',
            'no_registrasi_akta_lahir' => 'No. Registrasi Akta Lahir',
            'kewarganegaraan' => 'Kewarganegaraan',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'agama_id' => 'Agama & Kepercayaan',
            'alamat_jalan' => 'Alamat Jalan / Tempat Tinggal',
            'rt' => 'RT',
            'rw' => 'RW',
            'desa_kelurahan' => 'Desa / Kelurahan',
            'kecamatan' => 'Kecamatan',
            'kabupaten_kota' => 'Kabupaten / Kota',
            'provinsi' => 'Provinsi',
            'kode_pos' => 'Kode Pos',
            'tempat_tinggal_id' => 'Status Tempat Tinggal',
            'transportasi_id' => 'Moda Transportasi',
            'anak_keberapa' => 'Anak Ke-berapa (di KK)',
            'nama_ayah' => 'Nama Ayah Kandung',
            'nik_ayah' => 'NIK Ayah',
            'tahun_lahir_ayah' => 'Tahun Lahir Ayah',
            'pendidikan_ayah_id' => 'Pendidikan Ayah',
            'pekerjaan_ayah_id' => 'Pekerjaan Ayah',
            'penghasilan_ayah_id' => 'Penghasilan Ayah',
            'nama_ibu' => 'Nama Ibu Kandung',
            'nik_ibu' => 'NIK Ibu',
            'tahun_lahir_ibu' => 'Tahun Lahir Ibu',
            'pendidikan_ibu_id' => 'Pendidikan Ibu',
            'pekerjaan_ibu_id' => 'Pekerjaan Ibu',
            'penghasilan_ibu_id' => 'Penghasilan Ibu',
            'nama_wali' => 'Nama Wali',
            'nik_wali' => 'NIK Wali',
            'nomor_telepon_rumah' => 'Nomor Telepon Rumah',
            'nomor_telepon_seluler' => 'No. HP / WhatsApp Siswa',
            'email' => 'Email Siswa',
            'sekolah_asal' => 'Sekolah Asal',
            'tinggi_badan' => 'Tinggi Badan (cm)',
            'berat_badan' => 'Berat Badan (kg)',
            'lingkar_kepala' => 'Lingkar Kepala (cm)',
            'jarak_rumah_sekolah' => 'Jarak Rumah ke Sekolah',
            'jarak_rumah_sekolah_km' => 'Jarak Rumah (km)',
            'waktu_tempuh_jam' => 'Waktu Tempuh (Jam)',
            'waktu_tempuh_menit' => 'Waktu Tempuh (Menit)',
            'jumlah_saudara_kandung' => 'Jumlah Saudara Kandung',
        ];

        // Rekomendasi alasan penolakan usulan data (chip buttons)
        $rekomendasiPenolakan = [
            'Data yang diusulkan tidak sesuai dengan dokumen resmi Kartu Keluarga (KK).',
            'Data nama / tempat / tanggal lahir tidak cocok dengan Ijazah SMP atau Akta Kelahiran.',
            'Lampiran berkas bukti buram, terpotong, atau tidak terbaca dengan jelas.',
            'Perubahan nama atau identitas pokok wajib menyertakan Akta Kelahiran / Penetapan Pengadilan.',
            'Format penulisan NIK atau No. KK tidak valid (harus 16 digit terdaftar di Dukcapil).',
            'Pengajuan usulan dibatalkan atas permintaan siswa atau orang tua.',
        ];

        $itemsData = [];
        foreach ($usulanList as $u) {
            $itemsData[] = [
                'id' => $u->id,
                'kolom_perubahan' => $u->kolom_perubahan,
                'kolom_label' => $kolomLabels[$u->kolom_perubahan] ?? ucwords(str_replace('_', ' ', $u->kolom_perubahan)),
                'nilai_lama' => $u->nilai_lama ?: '(Belum Terisi / Kosong)',
                'nilai_baru' => $u->nilai_baru,
                'alasan' => $u->alasan ?: 'Penyesuaian formulir identitas mandiri.',
                'status' => $u->status,
                'catatan_verifikasi' => $u->catatan_verifikasi,
                'verified_by' => $u->verified_by,
                'verified_at' => $u->verified_at ? $u->verified_at->translatedFormat('d F Y, H:i') : null,
                'created_at' => $u->created_at ? $u->created_at->translatedFormat('d F Y, H:i') : null,
                'berkas_bukti_url' => !empty($u->berkas_bukti) ? asset('storage/' . ltrim($u->berkas_bukti, '/')) : null,
            ];
        }

        $menungguCount = $usulanList->where('status', 'menunggu')->count();
        $disetujuiCount = $usulanList->where('status', 'disetujui')->count();
        $ditolakCount = $usulanList->where('status', 'ditolak')->count();
        $dapodikCount = $usulanList->where('status', 'sudah_ke_dapodik')->count();

        $statusAgregat = 'menunggu';
        if ($menungguCount > 0) {
            $statusAgregat = 'menunggu';
        } elseif ($disetujuiCount > 0) {
            $statusAgregat = 'disetujui';
        } elseif ($ditolakCount === count($itemsData) && count($itemsData) > 0) {
            $statusAgregat = 'ditolak';
        } elseif ($dapodikCount === count($itemsData) && count($itemsData) > 0) {
            $statusAgregat = 'sudah_ke_dapodik';
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $usulanList->first()?->id,
                'peserta_didik_id' => $pdId,
                'nama_siswa' => $pd?->nama ?: 'Siswa #' . $pdId,
                'nisn' => $pd?->nisn ?: '-',
                'nipd' => $pd?->nipd ?: '-',
                'rombel' => $pd?->nama_rombel ?: '-',
                'foto_url' => $meta?->foto_url ?? null,
                'total_usulan' => count($itemsData),
                'menunggu_count' => $menungguCount,
                'disetujui_count' => $disetujuiCount,
                'ditolak_count' => $ditolakCount,
                'dapodik_count' => $dapodikCount,
                'status' => $statusAgregat,
                'items' => $itemsData,
                // Kompatibilitas atribut tunggal
                'kolom_perubahan' => $usulanList->first()?->kolom_perubahan,
                'kolom_label' => $kolomLabels[$usulanList->first()?->kolom_perubahan ?? ''] ?? '',
                'nilai_lama' => $usulanList->first()?->nilai_lama ?: '(Belum Terisi / Kosong)',
                'nilai_baru' => $usulanList->first()?->nilai_baru,
                'alasan' => $usulanList->first()?->alasan ?: 'Penyesuaian formulir identitas mandiri.',
                'catatan_verifikasi' => $usulanList->first()?->catatan_verifikasi,
                'verified_by' => $usulanList->first()?->verified_by,
                'verified_at' => $usulanList->first()?->verified_at ? $usulanList->first()->verified_at->translatedFormat('d F Y, H:i') : null,
                'created_at' => $usulanList->first()?->created_at ? $usulanList->first()->created_at->translatedFormat('d F Y, H:i') : null,
                'berkas_bukti_url' => !empty($usulanList->first()?->berkas_bukti) ? asset('storage/' . ltrim($usulanList->first()->berkas_bukti, '/')) : null,
                'kk' => [
                    'exists' => !empty($kk),
                    'status' => $kk?->status ?? 'belum_unggah',
                    'file_url' => $kk ? route('dashboard.berkas.preview', $kk->id) : null,
                    'file_name' => $kk?->file_name,
                ],
                'ijazah' => [
                    'exists' => !empty($ijazah),
                    'status' => $ijazah?->status ?? 'belum_unggah',
                    'file_url' => $ijazah ? route('dashboard.berkas.preview', $ijazah->id) : null,
                    'file_name' => $ijazah?->file_name,
                ],
                'akta' => [
                    'exists' => !empty($akta),
                    'status' => $akta?->status ?? 'belum_unggah',
                    'file_url' => $akta ? route('dashboard.berkas.preview', $akta->id) : null,
                    'file_name' => $akta?->file_name,
                ],
            ],
            'rekomendasi_penolakan' => $rekomendasiPenolakan,
        ]);
    }

    /**
     * Verifikasi Usulan Perubahan Data Siswa (Setujui / Tolak).
     * Mendukung verifikasi per siswa (seluruh usulan) atau per ID usulan.
     */
    public function verifikasiUsulan(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canUpdate = in_array($role, ['admin', 'tendik'], true)
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_peserta_didik', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'update');

        if (!$canUpdate) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'status'             => 'required|string|in:disetujui,ditolak',
            'catatan_verifikasi' => 'nullable|string',
            'usulan_ids'         => 'nullable|array',
            'usulan_ids.*'       => 'integer',
        ]);

        $status = $validated['status'];
        $catatan = trim((string)($validated['catatan_verifikasi'] ?? ''));

        if ($status === 'ditolak' && empty($catatan)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Alasan atau catatan penolakan wajib diisi jika usulan perubahan data ditolak.'
            ], 422);
        }

        $userName = is_array($user) ? ($user['nama'] ?? 'Verifikator') : ($user->nama ?? 'Verifikator');

        // Cek apakah $id adalah peserta_didik_id atau ID usulan tunggal
        $isPd = PesertaDidik::where('peserta_didik_id', $id)->exists()
            || (Schema::hasTable('peserta_didik_tidak_aktif') && DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->exists());

        if ($isPd) {
            $query = SiswaUsulanPerubahan::where('peserta_didik_id', $id);
            if (!empty($validated['usulan_ids'])) {
                $query->whereIn('id', $validated['usulan_ids']);
            }
            $usulanList = $query->get();
            $pdId = $id;
        } else {
            $single = SiswaUsulanPerubahan::findOrFail($id);
            $usulanList = collect([$single]);
            $pdId = $single->peserta_didik_id;
        }

        $pdUpdates = [];
        $identitasUpdates = [];

        foreach ($usulanList as $usulan) {
            $usulan->status = $status;
            $usulan->catatan_verifikasi = $catatan ?: null;
            $usulan->verified_by = $userName;
            $usulan->verified_at = now();
            $usulan->save();

            if ($status === 'disetujui') {
                if (Schema::hasColumn('peserta_didik', $usulan->kolom_perubahan)) {
                    $pdUpdates[$usulan->kolom_perubahan] = $usulan->nilai_baru;
                }
                if (Schema::hasTable('peserta_didik_identitas') && Schema::hasColumn('peserta_didik_identitas', $usulan->kolom_perubahan)) {
                    $identitasUpdates[$usulan->kolom_perubahan] = $usulan->nilai_baru;
                }
            }
        }

        if (!empty($pdUpdates)) {
            DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->update($pdUpdates);
        }

        if (Schema::hasTable('peserta_didik_identitas')) {
            $remainingPending = SiswaUsulanPerubahan::where('peserta_didik_id', $pdId)
                ->where('status', 'menunggu')
                ->count();

            if ($remainingPending === 0 && $status === 'disetujui') {
                $identitasUpdates['status_konfirmasi'] = 'diverifikasi';
                $identitasUpdates['catatan_kesiswaan'] = 'Seluruh usulan perubahan data siswa telah disetujui & diverifikasi oleh Tim Kesiswaan.';
            } elseif ($status === 'ditolak') {
                $identitasUpdates['catatan_kesiswaan'] = "Usulan perubahan data ditolak: " . ($catatan ?: 'Dokumen pendukung tidak sesuai.');
            }

            if (!empty($identitasUpdates)) {
                DB::table('peserta_didik_identitas')->where('peserta_didik_id', $pdId)->update($identitasUpdates);
            }
        }

        if (class_exists(AdminAktivitas::class)) {
            $pd = PesertaDidik::where('peserta_didik_id', $pdId)->first();
            $labelStatus = $status === 'disetujui' ? 'Disetujui' : 'Ditolak';
            $count = count($usulanList);
            AdminAktivitas::record(
                "Verifikasi Usulan Data Siswa: {$pd?->nama} ({$count} butir usulan) [{$labelStatus}]",
                'Kesiswaan',
                $status === 'ditolak' ? "Alasan Penolakan: {$catatan}" : "Usulan perubahan data disetujui dan data siswa diperbarui.",
                $status === 'disetujui' ? 'success' : 'warning'
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => "Usulan perubahan data (" . count($usulanList) . " butir) berhasil " . ($status === 'disetujui' ? 'disetujui' : 'ditolak') . ".",
            'data' => $usulanList,
        ]);
    }

    /**
     * Tandai Usulan Perubahan Data telah Di-update / Disinkronkan ke Aplikasi Dapodik
     */
    public function markDapodikUpdated(Request $request, $id)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canUpdate = in_array($role, ['admin', 'tendik'], true)
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_peserta_didik', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'update');

        if (!$canUpdate) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $userName = is_array($user) ? ($user['nama'] ?? 'Operator Dapodik') : ($user->nama ?? 'Operator Dapodik');

        $isPd = PesertaDidik::where('peserta_didik_id', $id)->exists()
            || (Schema::hasTable('peserta_didik_tidak_aktif') && DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->exists());

        if ($isPd) {
            $usulanList = SiswaUsulanPerubahan::where('peserta_didik_id', $id)
                ->where('status', 'disetujui')
                ->get();
            $pdId = $id;
        } else {
            $single = SiswaUsulanPerubahan::findOrFail($id);
            $usulanList = collect([$single]);
            $pdId = $single->peserta_didik_id;
        }

        foreach ($usulanList as $usulan) {
            $usulan->status = 'sudah_ke_dapodik';
            $usulan->catatan_verifikasi = ($usulan->catatan_verifikasi ? $usulan->catatan_verifikasi . ' | ' : '') . "Telah di-update ke Aplikasi Dapodik oleh {$userName} pada " . now()->translatedFormat('d M Y, H:i');
            $usulan->save();
        }

        if (Schema::hasTable('peserta_didik_identitas')) {
            $remaining = SiswaUsulanPerubahan::where('peserta_didik_id', $pdId)
                ->whereIn('status', ['menunggu', 'disetujui'])
                ->count();

            if ($remaining === 0) {
                DB::table('peserta_didik_identitas')
                    ->where('peserta_didik_id', $pdId)
                    ->update([
                        'status_konfirmasi' => 'disinkronkan_dapodik',
                        'catatan_kesiswaan' => 'Seluruh usulan perubahan data siswa telah selesai di-input ke aplikasi Dapodik.',
                    ]);
            }
        }

        if (class_exists(AdminAktivitas::class)) {
            $pd = PesertaDidik::where('peserta_didik_id', $pdId)->first();
            AdminAktivitas::record(
                "Update Dapodik Selesai: {$pd?->nama}",
                'Kesiswaan',
                "Perubahan data siswa berhasil ditandai selesai di-input ke Dapodik.",
                'success'
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil! Data perubahan ditandai telah selesai di-update ke aplikasi Dapodik.',
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
     * Ambil data berkas digital lengkap per siswa (JSON) untuk Modal Verifikasi Berkas Kesiswaan.
     */
    public function getBerkasDetail($id)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canAccess = in_array($role, ['admin', 'tendik', 'guru'], true)
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_peserta_didik', 'read')
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'read')
            || RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'read')
            || RolePermission::canAccess($user ?: $role, 'menu_wali_kelas_validasi', 'read')
            || RolePermission::canAccess($user ?: $role, 'menu_wali_kelas', 'read')
            || RolePermission::isWaliKelasOrAdmin($user);

        if (!$canAccess) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $pd = PesertaDidik::where('peserta_didik_id', $id)->first();
        if (!$pd && Schema::hasTable('peserta_didik_tidak_aktif')) {
            $pd = DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->first();
        }

        if (!$pd) {
            return response()->json(['status' => 'error', 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        $slots = PesertaDidikBerkas::getJenisBerkasOptions();
        $berkas = PesertaDidikBerkas::where('peserta_didik_id', $id)->get()->keyBy('jenis_berkas');
        $rekomendasi = PesertaDidikBerkas::getRekomendasiPenolakanOptions();

        $items = [];
        foreach ($slots as $k => $slot) {
            $b = $berkas->get($k);
            $items[] = [
                'jenis_berkas' => $k,
                'label' => $slot['label'],
                'deskripsi' => $slot['deskripsi'],
                'icon' => $slot['icon'],
                'wajib' => $slot['wajib'],
                'is_uploaded' => !empty($b) && !empty($b->file_path),
                'berkas_id' => $b?->id,
                'file_name' => $b?->file_name,
                'file_size' => $b?->formatted_file_size,
                'file_url' => (!empty($b) && !empty($b->file_path)) ? route('dashboard.berkas.preview', $b->id) : null,
                'status' => $b?->status ?? 'belum_unggah',
                'catatan_penolakan' => $b?->catatan_penolakan,
                'rekomendasi_penolakan' => $b?->rekomendasi_penolakan,
                'verified_by' => $b?->verified_by,
                'verified_at' => $b?->verified_at ? $b->verified_at->translatedFormat('d M Y, H:i') : null,
                'uploaded_at' => $b?->created_at ? $b->created_at->translatedFormat('d M Y, H:i') : null,
            ];
        }

        return response()->json([
            'status' => 'success',
            'siswa' => [
                'peserta_didik_id' => $pd->peserta_didik_id,
                'nama' => $pd->nama,
                'nisn' => $pd->nisn ?? '-',
                'nipd' => $pd->nipd ?? '-',
                'rombel' => $pd->nama_rombel ?? '-',
            ],
            'items' => $items,
            'rekomendasi_penolakan' => $rekomendasi,
        ]);
    }

    /**
     * Verifikasi Kebenaran Berkas Siswa (Hanya 2 Status: valid atau tidak_valid).
     * Jika tidak_valid, wajib menyertakan catatan/alasan penolakan.
     */
    public function verifikasiBerkasItem(Request $request, $id)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canUpdate = in_array($role, ['admin', 'tendik', 'guru'], true)
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_peserta_didik', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_wali_kelas_validasi', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_wali_kelas', 'update')
            || RolePermission::isWaliKelasOrAdmin($user);

        if (!$canUpdate) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'jenis_berkas' => 'required|string',
            'status' => 'required|in:valid,tidak_valid', // HANYA 2 STATUS
            'catatan_penolakan' => 'nullable|string',
            'rekomendasi_penolakan' => 'nullable|string',
        ]);

        $status = $request->input('status');
        $catatan = trim((string)$request->input('catatan_penolakan', ''));
        $rekomendasi = trim((string)$request->input('rekomendasi_penolakan', ''));

        if ($status === 'tidak_valid' && empty($catatan) && empty($rekomendasi)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Untuk status tidak valid / tidak sesuai, wajib memberikan catatan atau memilih rekomendasi alasan penolakan.'
            ], 422);
        }

        $berkas = PesertaDidikBerkas::firstOrNew([
            'peserta_didik_id' => $id,
            'jenis_berkas' => $request->input('jenis_berkas'),
        ]);

        if (!$berkas->exists) {
            $slots = PesertaDidikBerkas::getJenisBerkasOptions();
            $berkas->nama_berkas = $slots[$request->input('jenis_berkas')]['label'] ?? 'Dokumen Siswa';
            $berkas->file_path = '';
            $berkas->file_name = '(Verifikasi Fisik / Manual)';
            $berkas->mime_type = 'application/pdf';
        }

        $userName = is_array($user) ? ($user['nama'] ?? 'Staf Kesiswaan') : ($user->nama ?? 'Staf Kesiswaan');

        $berkas->status = $status;
        $berkas->verified_by = $userName;
        $berkas->verified_at = now();
        $berkas->catatan_penolakan = $status === 'tidak_valid' ? ($catatan ?: $rekomendasi) : null;
        $berkas->rekomendasi_penolakan = $status === 'tidak_valid' ? $rekomendasi : null;
        $berkas->save();

        // Sinkronisasi status checklist ke tabel kesiswaan_berkas_verifikasi jika sesuai kolom
        $coreColumns = ['akta_kelahiran', 'kartu_keluarga', 'ijazah_smp', 'ktp_orang_tua', 'kip_pip'];
        if (in_array($berkas->jenis_berkas, $coreColumns, true)) {
            $kbv = KesiswaanBerkasVerifikasi::firstOrNew(['peserta_didik_id' => $id]);
            $kbv->{$berkas->jenis_berkas} = ($status === 'valid');
            $kbv->verified_by = $userName;
            $kbv->verified_at = now();
            $kbv->save();
        }

        if (class_exists(AdminAktivitas::class)) {
            $pd = PesertaDidik::where('peserta_didik_id', $id)->first();
            $statusLabel = $status === 'valid' ? 'Valid (Sesuai)' : 'Tidak Valid (Tidak Sesuai)';
            AdminAktivitas::record(
                "Verifikasi Berkas Siswa: {$pd?->nama} - {$berkas->nama_berkas} ({$statusLabel})",
                'Kesiswaan',
                $status === 'tidak_valid' ? "Penolakan: " . ($berkas->catatan_penolakan ?: '-') : "Dokumen diverifikasi valid dan sesuai persyaratan.",
                $status === 'valid' ? 'success' : 'warning'
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => "Berkas \"{$berkas->nama_berkas}\" berhasil divalidasi sebagai: " . ($status === 'valid' ? 'Valid / Sesuai' : 'Tidak Valid / Tidak Sesuai') . '.',
            'data' => $berkas,
        ]);
    }

    /**
     * Upload berkas siswa langsung oleh Tendik / Staf Kesiswaan (otomatis berstatus 'valid').
     */
    public function uploadBerkasTendik(Request $request, $id)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canUpdate = in_array($role, ['admin', 'tendik', 'guru'], true)
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_peserta_didik', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_wali_kelas_validasi', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_wali_kelas', 'update')
            || RolePermission::isWaliKelasOrAdmin($user);

        if (!$canUpdate) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'jenis_berkas' => 'required|string',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'file.required' => 'Pilih file dokumen yang ingin diunggah.',
            'file.mimes' => 'Format file harus PDF, JPG, JPEG, atau PNG.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $pd = PesertaDidik::where('peserta_didik_id', $id)->first();
        if (!$pd && Schema::hasTable('peserta_didik_tidak_aktif')) {
            $pd = DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->first();
        }

        if (!$pd) {
            return response()->json(['status' => 'error', 'message' => 'Peserta didik tidak ditemukan.'], 404);
        }

        $file = $request->file('file');
        $jenisBerkas = $request->input('jenis_berkas');
        $slots = PesertaDidikBerkas::getJenisBerkasOptions();
        $namaBerkas = $slots[$jenisBerkas]['label'] ?? 'Dokumen Siswa';

        // Hapus file lama jika ada
        $existing = PesertaDidikBerkas::where('peserta_didik_id', $pd->peserta_didik_id)
            ->where('jenis_berkas', $jenisBerkas)
            ->first();

        if ($existing && !empty($existing->file_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($existing->file_path);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = $jenisBerkas . '_' . time() . '.' . $extension;
        $directory = 'berkas_siswa/' . $pd->peserta_didik_id;
        $savedPath = $file->storeAs($directory, $fileName, 'public');

        $userName = is_array($user) ? ($user['nama'] ?? 'Staf Kesiswaan') : ($user->nama ?? 'Staf Kesiswaan');

        // Otomatis tersimpan dan LANGSUNG VALID
        $berkas = PesertaDidikBerkas::updateOrCreate(
            [
                'peserta_didik_id' => $pd->peserta_didik_id,
                'jenis_berkas' => $jenisBerkas,
            ],
            [
                'nama_berkas' => $namaBerkas,
                'file_path' => $savedPath,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'status' => 'valid', // OTOMATIS VALID
                'catatan_penolakan' => null,
                'rekomendasi_penolakan' => null,
                'verified_by' => $userName . ' (Tendik)',
                'verified_at' => now(),
                'uploaded_by' => $userName . ' (Tendik Kesiswaan)',
            ]
        );

        // Sinkronisasi status checklist ke tabel kesiswaan_berkas_verifikasi jika kolom utama
        $coreColumns = ['akta_kelahiran', 'kartu_keluarga', 'ijazah_smp', 'ktp_orang_tua', 'kip_pip'];
        if (in_array($berkas->jenis_berkas, $coreColumns, true)) {
            $kbv = KesiswaanBerkasVerifikasi::firstOrNew(['peserta_didik_id' => $id]);
            $kbv->{$berkas->jenis_berkas} = true;
            $kbv->verified_by = $userName;
            $kbv->verified_at = now();
            $kbv->save();
        }

        if (class_exists(AdminAktivitas::class)) {
            AdminAktivitas::record(
                "Upload Berkas Tendik: {$pd->nama} - {$berkas->nama_berkas} (Valid)",
                'Kesiswaan',
                "Dokumen diunggah langsung oleh {$userName} dan otomatis diverifikasi valid.",
                'success'
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => "Berkas \"{$berkas->nama_berkas}\" berhasil diunggah dan otomatis diverifikasi VALID.",
            'data' => [
                'berkas_id' => $berkas->id,
                'file_name' => $berkas->file_name,
                'file_size' => $berkas->formatted_file_size,
                'file_url' => route('dashboard.berkas.preview', $berkas->id),
                'status' => 'valid',
                'verified_by' => $berkas->verified_by,
                'verified_at' => $berkas->verified_at ? $berkas->verified_at->translatedFormat('d M Y, H:i') : null,
                'uploaded_at' => $berkas->created_at ? $berkas->created_at->translatedFormat('d M Y, H:i') : null,
            ]
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
