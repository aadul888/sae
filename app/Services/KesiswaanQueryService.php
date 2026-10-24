<?php

namespace App\Services;

use App\Models\KesiswaanBerkasVerifikasi;
use App\Models\PesertaDidik;
use App\Models\PesertaDidikBerkas;
use App\Models\PesertaDidikMeta;
use App\Models\RolePermission;
use App\Models\SiswaUsulanPerubahan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KesiswaanQueryService
{
    /**
     * Kompilasi data untuk halaman index kesiswaan peserta didik (5 tab: aktif, tidak_aktif, alumni, berkas, usulan).
     */
    public function getIndexData(Request $request, mixed $user): array
    {
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'create') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'read') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'update') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'delete') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'delete');

        $activeTab = $request->get('tab', 'aktif');
        $q = trim($request->get('q', ''));
        $rombel = trim($request->get('rombel', ''));
        $gender = trim($request->get('gender', ''));
        $status = trim($request->get('status', ''));
        $tahunLulus = trim($request->get('tahun_lulus', ''));

        $sort = in_array($request->get('sort'), ['nama', 'nisn', 'nipd', 'jenis_kelamin', 'nama_rombel', 'tingkat_pendidikan_id', 'nama_rombel_terakhir', 'tingkat_pendidikan_terakhir', 'tahun_lulus', 'status_keluar', 'tanggal_keluar'], true) ? $request->get('sort') : 'nama';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $perPageVal = $request->get('perPage', $request->get('per_page', '25'));
        $perPage = in_array($perPageVal, ['10', '15', '25', '50', '100']) ? (int)$perPageVal : 25;

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
            'total_aktif'          => $totalAktif,
            'aktif_laki'           => $aktifLaki,
            'aktif_perempuan'      => $aktifPerempuan,
            'total_rombel'         => $totalRombelAktif,
            'total_koordinator'    => $totalKoordinator,
            'total_tidak_aktif'        => $totalTidakAktif,
            'ta_mutasi'                => $taMutasi,
            'ta_dikeluarkan'           => $taDikeluarkan,
            'ta_mengundurkan'          => $taMengundurkan,
            'ta_mengundurkan_lainnya'  => $taMengundurkan + $taLainnya,
            'ta_lainnya'               => $taLainnya,
            'berkas_total_siswa'   => $totalAktif,
            'berkas_lengkap'       => $totalBerkasLengkap,
            'berkas_menunggu'      => $berkasMenungguCount,
            'berkas_ditolak'       => $berkasDitolakCount,
            'berkas_belum_lengkap' => $berkasBelumLengkap,
            'usulan_total'         => $totalUsulan,
            'usulan_menunggu'      => $usulanMenunggu,
            'usulan_disetujui'     => $usulanDisetujui,
            'usulan_dapodik'       => $usulanDapodik,
            'usulan_ditolak'       => $usulanDitolak,
            'total_alumni'         => $totalAlumni,
            'alumni_laki'          => $alumniLaki,
            'alumni_perempuan'     => $alumniPerempuan,
            'alumni_angkatan'      => $alumniTahunCount,
        ];

        $filterRombel = DB::table('peserta_didik')
            ->whereNotNull('nama_rombel')
            ->where('nama_rombel', '<>', '')
            ->distinct()
            ->pluck('nama_rombel')
            ->sort()
            ->values();

        $filterRombelTidakAktif = (clone $baseTidakAktif)
            ->whereNotNull('nama_rombel_terakhir')
            ->where('nama_rombel_terakhir', '<>', '')
            ->distinct()
            ->pluck('nama_rombel_terakhir')
            ->sort()
            ->values();

        // 2. Tab: Peserta Didik Aktif
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
            $metaMapAktif = PesertaDidikMeta::whereIn('peserta_didik_id', $aktifPdIds)->get()->keyBy('peserta_didik_id');
        }
        foreach ($aktifList as $item) {
            $meta = $metaMapAktif[$item->peserta_didik_id] ?? null;
            $item->foto_url = $meta?->foto_url;
            $item->foto_size = $meta?->formatted_foto_size;
            $item->is_koordinator = (bool) ($meta?->is_koordinator ?? false);
            $item->jabatan_koordinator = $meta?->jabatan_koordinator ?? 'Koordinator Kelas';
        }

        // 3. Tab: Peserta Didik Tidak Aktif
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
            $metaMapTa = PesertaDidikMeta::whereIn('peserta_didik_id', $taPdIds)->get()->keyBy('peserta_didik_id');
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
            $metaMapAlumni = PesertaDidikMeta::whereIn('peserta_didik_id', $alumniPdIds)->get()->keyBy('peserta_didik_id');
        }
        foreach ($alumniList as $item) {
            $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : ($metaMapAlumni[$item->peserta_didik_id]?->foto_url ?? null);
        }

        // 5. Tab: Verifikasi Berkas Fisik & Digital
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
            $metaMapBerkas = PesertaDidikMeta::whereIn('peserta_didik_id', $berkasPdIds)->get()->keyBy('peserta_didik_id');
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

        // 6. Tab: Usulan Perubahan Data Siswa
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

        return compact(
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
        );
    }

    /**
     * Detail lengkap biodata peserta didik (JSON) untuk Modal Biodata.
     */
    public function getShowData(string|int $id): ?array
    {
        $pesertaDidik = DB::table('peserta_didik')
            ->where('peserta_didik_id', $id)
            ->orWhere('nisn', $id)
            ->orWhere('nipd', $id)
            ->first();

        if (!$pesertaDidik && Schema::hasTable('peserta_didik_tidak_aktif')) {
            $pesertaDidik = DB::table('peserta_didik_tidak_aktif')
                ->where('peserta_didik_id', $id)
                ->orWhere('nisn', $id)
                ->orWhere('nipd', $id)
                ->first();
        }

        if (!$pesertaDidik) {
            return null;
        }

        $anggota = null;
        if (Schema::hasTable('anggota_rombel')) {
            $anggota = DB::table('anggota_rombel')
                ->where('peserta_didik_id', $pesertaDidik->peserta_didik_id)
                ->first();
        }

        $meta = null;
        if (Schema::hasTable('peserta_didik_meta')) {
            $meta = PesertaDidikMeta::where('peserta_didik_id', $pesertaDidik->peserta_didik_id)->first();
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

        return [
            'status'       => 'success',
            'data'         => $pesertaDidik,
            'anggota'      => $anggota,
            'meta'         => $meta,
            'foto_url'     => $fotoUrl,
            'foto_size'    => $meta?->formatted_foto_size,
            'pembelajaran' => $pembelajaran,
            'total_mapel'  => $pembelajaran->count(),
            'total_jam'    => $pembelajaran->sum(fn($p) => (int) ($p->jam_mengajar_per_minggu ?? 0)),
        ];
    }
}
