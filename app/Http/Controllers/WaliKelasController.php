<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\RolePermission;
use App\Models\PesertaDidikMeta;
use App\Models\PresensiHarian;
use App\Models\PresensiPengaturan;
use App\Models\KalenderPendidikan;
use Carbon\Carbon;

class WaliKelasController extends Controller
{
    private const SORTABLE_AKTIF = ['nama', 'nisn', 'nipd', 'jenis_kelamin', 'tanggal_lahir'];
    private const SORTABLE_TIDAK_AKTIF = ['nama', 'nisn', 'nipd', 'jenis_kelamin', 'status_keluar', 'tahun_lulus'];

    /**
     * Resolusi konteks Rombel Binaan Wali Kelas
     */
    private function resolveWaliKelasContext(Request $request, mixed $user): array
    {
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $isAdmin = ($role === 'admin');

        if ($isAdmin) {
            $rombelList = DB::table('rombongan_belajar')
                ->where('jenis_rombel', '1')
                ->orderBy('tingkat_pendidikan_id', 'asc')
                ->orderBy('nama', 'asc')
                ->select(
                    'rombongan_belajar_id',
                    'nama',
                    'tingkat_pendidikan_id_str',
                    'jurusan_id_str',
                    'ptk_id',
                    'ptk_id_str'
                )
                ->get();

            $selectedId = $request->get('rombel_id', $rombelList->first()?->rombongan_belajar_id ?? '');
            $activeRombel = $rombelList->firstWhere('rombongan_belajar_id', $selectedId) ?: $rombelList->first();

            $waliNama = $activeRombel?->ptk_id_str ?: 'Administrator';
            $waliGtk = null;
            if ($activeRombel?->ptk_id) {
                $waliGtk = DB::table('gtk')->where('ptk_id', $activeRombel->ptk_id)->first();
            }
            if (!$waliGtk && $activeRombel?->ptk_id_str) {
                $waliGtk = DB::table('gtk')->where('nama', $activeRombel->ptk_id_str)->first();
            }
            $waliNip = $waliGtk?->nip ?: ($waliGtk?->nuptk ?: '—');

            return [
                'isAdmin'      => true,
                'isGuru'       => false,
                'rombelList'   => $rombelList,
                'activeRombel' => $activeRombel,
                'waliNama'     => $waliNama,
                'waliNip'      => $waliNip,
            ];
        }

        // Pengguna Peran Guru (Wali Kelas)
        $activeRombel = RolePermission::getWaliKelasRombelInfo($user);
        $waliNama = is_array($user) ? ($user['name'] ?? ($user['nama'] ?? 'Guru')) : ($user->name ?? ($user->nama ?? 'Guru'));

        $waliGtk = null;
        if (!empty($activeRombel?->ptk_id)) {
            $waliGtk = DB::table('gtk')->where('ptk_id', $activeRombel->ptk_id)->first();
        }
        $userPtkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        if (!$waliGtk && $userPtkId) {
            $waliGtk = DB::table('gtk')->where('ptk_id', $userPtkId)->first();
        }
        if (!$waliGtk && $activeRombel?->ptk_id_str) {
            $waliGtk = DB::table('gtk')->where('nama', $activeRombel->ptk_id_str)->first();
        }
        $waliNip = $waliGtk?->nip ?: ($waliGtk?->nuptk ?: (is_array($user) ? ($user['nip'] ?? ($user['nuptk'] ?? '—')) : ($user->nip ?? ($user->nuptk ?? '—'))));

        return [
            'isAdmin'      => false,
            'isGuru'       => true,
            'rombelList'   => $activeRombel ? collect([$activeRombel]) : collect(),
            'activeRombel' => $activeRombel,
            'waliNama'     => $activeRombel?->ptk_id_str ?: $waliNama,
            'waliNip'      => $waliNip,
        ];
    }

    /**
     * Tampilan Peserta Didik Aktif Kelas Binaan
     */
    public function pesertaDidikAktif(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::isWaliKelasOrAdmin($user) || !RolePermission::canAccess($user, 'menu_wali_kelas_aktif')) {
            return redirect()->route('dashboard.' . ($role ?: 'guru'))->with('error', 'Akses ke modul Wali Kelas tidak diizinkan.');
        }

        $canCreate = RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'create');
        $canRead   = RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'read');
        $canUpdate = RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'update');
        $canDelete = RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'delete');

        $ctx = $this->resolveWaliKelasContext($request, $user);
        $activeRombel = $ctx['activeRombel'];

        $q       = trim($request->get('q', ''));
        $gender  = trim($request->get('gender', ''));
        $perPage = (int) $request->get('perPage', 15);
        $sort    = in_array($request->get('sort'), self::SORTABLE_AKTIF, true) ? $request->get('sort') : 'nama';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        // Jika guru tidak memiliki rombel binaan aktif
        if (!$activeRombel) {
            return view('dashboard.wali-kelas-aktif', [
                'hasRombel'    => false,
                'isAdmin'      => $ctx['isAdmin'],
                'rombelList'   => $ctx['rombelList'],
                'activeRombel' => null,
                'waliNama'     => $ctx['waliNama'],
                'list'         => collect(),
                'total'        => 0,
                'summary'      => ['total' => 0, 'laki' => 0, 'perempuan' => 0, 'berfoto' => 0],
                'q'            => $q,
                'gender'       => $gender,
                'perPage'      => $perPage,
                'sort'         => $sort,
                'sortDir'      => $sortDir,
                'canCreate'    => $canCreate,
                'canRead'      => $canRead,
                'canUpdate'    => $canUpdate,
                'canDelete'    => $canDelete,
            ]);
        }

        $targetRombelId = $activeRombel->rombongan_belajar_id ?? null;
        $targetRombelName = $activeRombel->nama ?? null;

        $baseQuery = DB::table('peserta_didik as pd')
            ->where(function ($w) use ($targetRombelId, $targetRombelName) {
                if ($targetRombelId) {
                    $w->where('pd.rombongan_belajar_id', $targetRombelId);
                }
                if ($targetRombelName) {
                    $w->orWhere('pd.nama_rombel', $targetRombelName);
                }
            });

        // Hitung Statistik Kelas Binaan
        $totalAll = (clone $baseQuery)->count();
        $totalLaki = (clone $baseQuery)->where('pd.jenis_kelamin', 'L')->count();
        $totalPerempuan = (clone $baseQuery)->where('pd.jenis_kelamin', 'P')->count();

        // Hitung siswa yang sudah memiliki foto
        $activePdIds = (clone $baseQuery)->pluck('pd.peserta_didik_id')->toArray();
        $totalBerfoto = 0;
        if (!empty($activePdIds) && Schema::hasTable('peserta_didik_meta')) {
            $totalBerfoto = DB::table('peserta_didik_meta')
                ->whereIn('peserta_didik_id', $activePdIds)
                ->whereNotNull('foto_path')
                ->where('foto_path', '<>', '')
                ->count();
        }

        $summary = [
            'total'     => $totalAll,
            'laki'      => $totalLaki,
            'perempuan' => $totalPerempuan,
            'berfoto'   => $totalBerfoto,
        ];

        // Query tabel dengan filter
        $query = clone $baseQuery;
        $query->select(
            'pd.peserta_didik_id',
            'pd.nama',
            'pd.nisn',
            'pd.nipd',
            'pd.nik',
            'pd.jenis_kelamin',
            'pd.tempat_lahir',
            'pd.tanggal_lahir',
            'pd.agama_id_str as agama',
            'pd.nama_rombel',
            'pd.rombongan_belajar_id',
            'pd.tingkat_pendidikan_id',
            'pd.nama_ayah',
            'pd.nama_ibu',
            'pd.nama_wali',
            'pd.alamat_jalan',
            'pd.nomor_telepon_seluler as no_hp',
            'pd.email'
        );

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('pd.nama', 'LIKE', "%{$q}%")
                    ->orWhere('pd.nisn', 'LIKE', "%{$q}%")
                    ->orWhere('pd.nipd', 'LIKE', "%{$q}%");
            });
        }

        if ($gender !== '') {
            $query->where('pd.jenis_kelamin', $gender);
        }

        $query->orderBy($sort, $sortDir);
        $list = $query->paginate($perPage)->appends($request->query());

        // Hubungkan metadata foto persisten
        if (Schema::hasTable('peserta_didik_meta') && $list->isNotEmpty()) {
            $pageIds = $list->pluck('peserta_didik_id')->toArray();
            $metaMap = PesertaDidikMeta::whereIn('peserta_didik_id', $pageIds)
                ->get()
                ->keyBy('peserta_didik_id');

            foreach ($list as $item) {
                $meta = $metaMap->get($item->peserta_didik_id);
                $item->foto_url = $meta?->foto_url;
            }
        }

        return view('dashboard.wali-kelas-aktif', [
            'hasRombel'    => true,
            'isAdmin'      => $ctx['isAdmin'],
            'rombelList'   => $ctx['rombelList'],
            'activeRombel' => $activeRombel,
            'waliNama'     => $ctx['waliNama'],
            'list'         => $list,
            'total'        => $list->total(),
            'summary'      => $summary,
            'q'            => $q,
            'gender'       => $gender,
            'perPage'      => $perPage,
            'sort'         => $sort,
            'sortDir'      => $sortDir,
            'canCreate'    => $canCreate,
            'canRead'      => $canRead,
            'canUpdate'    => $canUpdate,
            'canDelete'    => $canDelete,
        ]);
    }

    /**
     * Tampilan Peserta Didik Tidak Aktif (Alumni / Mutasi Kelas Terkait)
     */
    public function pesertaDidikTidakAktif(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::isWaliKelasOrAdmin($user) || !RolePermission::canAccess($user, 'menu_wali_kelas_tidak_aktif')) {
            return redirect()->route('dashboard.' . ($role ?: 'guru'))->with('error', 'Akses ke modul Wali Kelas tidak diizinkan.');
        }

        $canCreate = RolePermission::canAccess($user, 'menu_wali_kelas_tidak_aktif', 'create');
        $canRead   = RolePermission::canAccess($user, 'menu_wali_kelas_tidak_aktif', 'read');
        $canUpdate = RolePermission::canAccess($user, 'menu_wali_kelas_tidak_aktif', 'update');
        $canDelete = RolePermission::canAccess($user, 'menu_wali_kelas_tidak_aktif', 'delete');

        $ctx = $this->resolveWaliKelasContext($request, $user);
        $activeRombel = $ctx['activeRombel'];

        $q       = trim($request->get('q', ''));
        $status  = trim($request->get('status', ''));
        $tahun   = trim($request->get('tahun', ''));
        $perPage = (int) $request->get('perPage', 15);
        $sort    = in_array($request->get('sort'), self::SORTABLE_TIDAK_AKTIF, true) ? $request->get('sort') : 'nama';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        if (!$activeRombel || !Schema::hasTable('peserta_didik_tidak_aktif')) {
            return view('dashboard.wali-kelas-tidak-aktif', [
                'hasRombel'    => (bool) $activeRombel,
                'isAdmin'      => $ctx['isAdmin'],
                'rombelList'   => $ctx['rombelList'],
                'activeRombel' => $activeRombel,
                'waliNama'     => $ctx['waliNama'],
                'list'         => collect(),
                'total'        => 0,
                'summary'      => ['total' => 0, 'alumni' => 0, 'mutasi' => 0, 'berfoto' => 0],
                'filterTahun'  => collect(),
                'q'            => $q,
                'status'       => $status,
                'tahun'        => $tahun,
                'perPage'      => $perPage,
                'sort'         => $sort,
                'sortDir'      => $sortDir,
                'canCreate'    => $canCreate,
                'canRead'      => $canRead,
                'canUpdate'    => $canUpdate,
                'canDelete'    => $canDelete,
            ]);
        }

        $targetRombelId = $activeRombel->rombongan_belajar_id ?? null;
        $targetRombelName = $activeRombel->nama ?? null;

        // Siswa aktif tidak boleh muncul di tabel tidak aktif
        $activePdIds = Schema::hasTable('peserta_didik')
            ? DB::table('peserta_didik')->pluck('peserta_didik_id')->filter()->all()
            : [];

        $baseQuery = DB::table('peserta_didik_tidak_aktif')
            ->where(function ($w) use ($targetRombelId, $targetRombelName) {
                if ($targetRombelId) {
                    $w->where('rombongan_belajar_id', $targetRombelId);
                }
                if ($targetRombelName) {
                    $w->orWhere('nama_rombel_terakhir', $targetRombelName);
                }
            });

        if (!empty($activePdIds)) {
            $baseQuery->whereNotIn('peserta_didik_id', $activePdIds);
        }

        // Summary counts
        $totalAll    = (clone $baseQuery)->count();
        $totalAlumni = (clone $baseQuery)->where('status_keluar', 'Alumni')->count();
        $totalMutasi = (clone $baseQuery)->where('status_keluar', '<>', 'Alumni')->count();
        $totalBerfoto = (clone $baseQuery)->whereNotNull('foto_path')->where('foto_path', '<>', '')->count();

        $summary = [
            'total'   => $totalAll,
            'alumni'  => $totalAlumni,
            'mutasi'  => $totalMutasi,
            'berfoto' => $totalBerfoto,
        ];

        // Daftar tahun keluar
        $filterTahun = (clone $baseQuery)
            ->whereNotNull('tahun_lulus')
            ->where('tahun_lulus', '<>', '')
            ->distinct()
            ->pluck('tahun_lulus')
            ->sortDesc()
            ->values();

        $query = clone $baseQuery;

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'LIKE', "%{$q}%")
                    ->orWhere('nisn', 'LIKE', "%{$q}%")
                    ->orWhere('nipd', 'LIKE', "%{$q}%")
                    ->orWhere('nik', 'LIKE', "%{$q}%");
            });
        }

        if ($status !== '') {
            if ($status === 'Alumni') {
                $query->where('status_keluar', 'Alumni');
            } else {
                $query->where('status_keluar', '<>', 'Alumni');
            }
        }

        if ($tahun !== '') {
            $query->where('tahun_lulus', $tahun);
        }

        $query->orderBy($sort, $sortDir);
        $list = $query->paginate($perPage)->appends($request->query());

        foreach ($list as $item) {
            $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
        }

        return view('dashboard.wali-kelas-tidak-aktif', [
            'hasRombel'    => true,
            'isAdmin'      => $ctx['isAdmin'],
            'rombelList'   => $ctx['rombelList'],
            'activeRombel' => $activeRombel,
            'waliNama'     => $ctx['waliNama'],
            'list'         => $list,
            'total'        => $list->total(),
            'summary'      => $summary,
            'filterTahun'  => $filterTahun,
            'q'            => $q,
            'status'       => $status,
            'tahun'        => $tahun,
            'perPage'      => $perPage,
            'sort'         => $sort,
            'sortDir'      => $sortDir,
            'canCreate'    => $canCreate,
            'canRead'      => $canRead,
            'canUpdate'    => $canUpdate,
            'canDelete'    => $canDelete,
        ]);
    }

    /**
     * API JSON Detail Peserta Didik Aktif (Lengkap seperti di Admin, Dibatasi per kelas binaan)
     */
    public function showPesertaDidik(string $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'success' => false, 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::isWaliKelasOrAdmin($user)) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $student = DB::table('peserta_didik')
            ->where('peserta_didik_id', $id)
            ->orWhere('nisn', $id)
            ->orWhere('nipd', $id)
            ->first();

        if (!$student) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        // Keamanan Ketat: Jika guru, verifikasi siswa berada di kelas binaan miliknya
        if ($role === 'guru') {
            $myRombel = RolePermission::getWaliKelasRombelInfo($user);
            $myRombelId = $myRombel?->rombongan_belajar_id;
            $myRombelName = $myRombel?->nama;

            $studentRombelId = $student->rombongan_belajar_id;
            if (!$studentRombelId && Schema::hasTable('anggota_rombel')) {
                $studentRombelId = DB::table('anggota_rombel')
                    ->where('peserta_didik_id', $student->peserta_didik_id)
                    ->value('rombongan_belajar_id');
            }

            $isMatch = false;
            if ($myRombelId && $studentRombelId && $studentRombelId === $myRombelId) {
                $isMatch = true;
            } elseif ($myRombelName && !empty($student->nama_rombel) && strcasecmp(trim($student->nama_rombel), trim($myRombelName)) === 0) {
                $isMatch = true;
            } elseif ($myRombelId && Schema::hasTable('anggota_rombel')) {
                $isMatch = DB::table('anggota_rombel')
                    ->where('rombongan_belajar_id', $myRombelId)
                    ->where('peserta_didik_id', $student->peserta_didik_id)
                    ->exists();
            }

            if (!$isMatch) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Anda tidak memiliki wewenang mengakses data peserta didik di luar kelas binaan Anda.'
                ], 403);
            }
        }

        $anggota = null;
        if (Schema::hasTable('anggota_rombel')) {
            $anggota = DB::table('anggota_rombel')
                ->where('peserta_didik_id', $student->peserta_didik_id)
                ->first();
        }

        $meta = null;
        if (Schema::hasTable('peserta_didik_meta')) {
            $meta = PesertaDidikMeta::where('peserta_didik_id', $student->peserta_didik_id)->first();
        }

        $rombelId = $student->rombongan_belajar_id ?? ($anggota->rombongan_belajar_id ?? null);
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

        $student->foto_url = $meta?->foto_url;
        $student->formatted_foto_size = $meta?->formatted_foto_size;

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => $student,
            'anggota' => $anggota,
            'meta' => $meta,
            'foto_url' => $meta?->foto_url,
            'foto_size' => $meta?->formatted_foto_size,
            'pembelajaran' => $pembelajaran,
            'total_mapel' => $pembelajaran->count(),
            'total_jam' => $pembelajaran->sum(fn($p) => (int) ($p->jam_mengajar_per_minggu ?? 0)),
        ]);
    }

    /**
     * API JSON Detail Peserta Didik Tidak Aktif (Dibatasi per kelas binaan)
     */
    public function showPesertaDidikTidakAktif(string $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'success' => false, 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::isWaliKelasOrAdmin($user)) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $student = DB::table('peserta_didik_tidak_aktif')
            ->where('id', $id)
            ->orWhere('peserta_didik_id', $id)
            ->first();

        if (!$student) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        // Keamanan Ketat: Jika guru, verifikasi riwayat rombel siswa
        if ($role === 'guru') {
            $myRombel = RolePermission::getWaliKelasRombelInfo($user);
            $myRombelId = $myRombel?->rombongan_belajar_id;
            $myRombelName = $myRombel?->nama;

            $isMatch = ($myRombelId && !empty($student->rombongan_belajar_id) && $student->rombongan_belajar_id === $myRombelId)
                    || ($myRombelName && !empty($student->nama_rombel_terakhir) && strcasecmp(trim($student->nama_rombel_terakhir), trim($myRombelName)) === 0);

            if (!$isMatch) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Anda tidak memiliki wewenang mengakses data peserta didik di luar kelas binaan Anda.'
                ], 403);
            }
        }

        $student->foto_url = !empty($student->foto_path) ? asset('storage/' . ltrim($student->foto_path, '/')) : null;

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => $student,
        ]);
    }

    /**
     * Tampilan Presensi Kelas Binaan (Wali Kelas)
     */
    public function presensi(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::isWaliKelasOrAdmin($user) || !RolePermission::canAccess($user, 'menu_wali_kelas_presensi')) {
            return redirect()->route('dashboard.' . ($role ?: 'guru'))->with('error', 'Akses ke modul Presensi Kelas tidak diizinkan.');
        }

        $canUpdate = RolePermission::canAccess($user, 'menu_wali_kelas_presensi', 'update');
        $ctx = $this->resolveWaliKelasContext($request, $user);
        $activeRombel = $ctx['activeRombel'];

        $tanggal = $request->get('tanggal', now()->toDateString());
        $q = trim($request->get('q', ''));
        $statusFilter = trim($request->get('status_filter', ''));
        $perPage = (int) $request->get('perPage', 15);
        $sort    = in_array($request->get('sort'), ['nama', 'nisn', 'status', 'jam_masuk'], true) ? $request->get('sort') : 'nama';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $statusHari = KalenderPendidikan::getStatusHari($tanggal, 'pd');
        $pengaturan = PresensiPengaturan::getPengaturan();

        if (!$activeRombel) {
            return view('dashboard.wali-kelas.presensi', [
                'hasRombel'    => false,
                'isAdmin'      => $ctx['isAdmin'],
                'rombelList'   => $ctx['rombelList'],
                'activeRombel' => null,
                'waliNama'     => $ctx['waliNama'],
                'tanggal'      => $tanggal,
                'statusHari'   => $statusHari,
                'pengaturan'   => $pengaturan,
                'list'         => new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage),
                'total'        => 0,
                'totalSiswa'   => 0,
                'summary'      => [
                    'total' => 0, 'hadir' => 0, 'terlambat' => 0, 'izin' => 0,
                    'sakit' => 0, 'alpha' => 0, 'pulang' => 0, 'belum' => 0, 'persen' => 0
                ],
                'q'            => $q,
                'statusFilter' => $statusFilter,
                'perPage'      => $perPage,
                'sort'         => $sort,
                'sortDir'      => $sortDir,
                'canUpdate'    => $canUpdate,
            ]);
        }

        $targetRombelId = $activeRombel->rombongan_belajar_id ?? null;

        // Query Siswa di Rombel Terpilih beserta Status Presensi Harian
        $query = DB::table('peserta_didik as pd')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->leftJoin('presensi_harian as ph', function ($join) use ($tanggal) {
                $join->on('pd.peserta_didik_id', '=', 'ph.peserta_didik_id')
                    ->where('ph.tanggal', '=', $tanggal);
            })
            ->where('pd.rombongan_belajar_id', $targetRombelId)
            ->select(
                'pd.peserta_didik_id',
                'pd.nama',
                'pd.nisn',
                'pd.nipd',
                'pd.jenis_kelamin',
                'pdm.foto_path',
                'pdm.rfid_uid',
                'ph.id as presensi_id',
                'ph.status',
                'ph.jam_masuk',
                'ph.jam_pulang',
                'ph.menit_terlambat',
                'ph.status_ketepatan_masuk',
                'ph.status_ketepatan_pulang',
                'ph.metode_masuk',
                'ph.metode_pulang',
                'ph.keterangan',
                'ph.verified_by'
            );

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('pd.nama', 'like', "%{$q}%")
                    ->orWhere('pd.nisn', 'like', "%{$q}%")
                    ->orWhere('pd.nipd', 'like', "%{$q}%");
            });
        }

        if ($statusFilter !== '') {
            if ($statusFilter === 'pulang') {
                $query->whereNotNull('ph.jam_pulang');
            } elseif ($statusFilter === 'belum') {
                $query->whereNull('ph.id');
            } else {
                $query->where('ph.status', $statusFilter);
            }
        }

        if ($sort === 'status') {
            $query->orderBy('ph.status', $sortDir)->orderBy('pd.nama', 'asc');
        } elseif ($sort === 'jam_masuk') {
            $query->orderBy('ph.jam_masuk', $sortDir)->orderBy('pd.nama', 'asc');
        } elseif ($sort === 'nisn') {
            $query->orderBy('pd.nisn', $sortDir)->orderBy('pd.nama', 'asc');
        } else {
            $query->orderBy('pd.nama', $sortDir);
        }

        $list = $query->paginate($perPage)->appends($request->query());

        // Hitung Summary Keseluruhan Kelas Hari Ini
        $totalSiswa = DB::table('peserta_didik')->where('rombongan_belajar_id', $targetRombelId)->count();
        $allPresensiHariIni = DB::table('presensi_harian')
            ->where('rombongan_belajar_id', $targetRombelId)
            ->where('tanggal', $tanggal)
            ->get();

        $countHadir     = $allPresensiHariIni->where('status', 'H')->count();
        $countTerlambat = $allPresensiHariIni->where('status', 'T')->count();
        $countIzin      = $allPresensiHariIni->where('status', 'I')->count();
        $countSakit     = $allPresensiHariIni->where('status', 'S')->count();
        $countAlpha     = $allPresensiHariIni->where('status', 'A')->count();
        $countPulang    = $allPresensiHariIni->whereNotNull('jam_pulang')->count();
        $countRecorded  = $allPresensiHariIni->count();
        $countBelum     = max(0, $totalSiswa - $countRecorded);
        $totalKehadiran = $countHadir + $countTerlambat;
        $persenHadir    = $totalSiswa > 0 ? round(($totalKehadiran / $totalSiswa) * 100, 1) : 0;

        $summary = [
            'total'     => $totalSiswa,
            'hadir'     => $countHadir,
            'terlambat' => $countTerlambat,
            'izin'      => $countIzin,
            'sakit'     => $countSakit,
            'alpha'     => $countAlpha,
            'pulang'    => $countPulang,
            'belum'     => $countBelum,
            'persen'    => $persenHadir,
        ];

        // Format Siswa & Evaluasi Kunci (1x Saja & Proteksi Mandiri RFID/QR)
        $list->getCollection()->transform(function ($item) {
            $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
            $item->status_label = PresensiHarian::STATUS_LABELS[$item->status] ?? ($item->status ?: 'Belum');
            $item->status_badge = PresensiHarian::STATUS_BADGES[$item->status] ?? '<span class="badge badge-outline"><i class="fas fa-circle-question me-1"></i> Belum</span>';

            // 1. Cek apakah presensi dilakukan secara mandiri via RFID/QR
            $item->is_mandiri = !empty($item->presensi_id) && in_array($item->metode_masuk, ['rfid', 'qr', 'kiosk'], true);

            // 2. Cek apakah presensi dicatat manual oleh wali kelas
            $item->is_manual = !empty($item->presensi_id) && ($item->metode_masuk === 'manual');

            // 3. Evaluasi Kunci Tindakan Manual
            if ($item->is_mandiri) {
                $item->is_locked = true;
                $item->lock_reason = "Presensi Mandiri (" . strtoupper($item->metode_masuk) . ")";
                $item->can_pulang = false; // Presensi mandiri tidak boleh diintervensi wali kelas
            } elseif ($item->is_manual) {
                if (in_array($item->status, ['S', 'I', 'A'], true)) {
                    $item->is_locked = true;
                    $item->lock_reason = "Manual Tercatat ({$item->status_label}) — Final";
                    $item->can_pulang = false;
                } elseif (in_array($item->status, ['H', 'T'], true)) {
                    if (!empty($item->jam_pulang)) {
                        $item->is_locked = true;
                        $item->lock_reason = "Selesai (Masuk & Pulang Tercatat)";
                        $item->can_pulang = false;
                    } else {
                        // Belum pulang: hanya tombol Pulang yang aktif
                        $item->is_locked = false;
                        $item->lock_reason = "Menunggu Pulang";
                        $item->can_pulang = true;
                    }
                } else {
                    $item->is_locked = true;
                    $item->lock_reason = "Manual Final";
                    $item->can_pulang = false;
                }
            } else {
                // Belum absen sama sekali: tombol Masuk, Terlambat, Izin, Sakit aktif
                $item->is_locked = false;
                $item->lock_reason = null;
                $item->can_pulang = false;
            }

            return $item;
        });

        $allSiswa = DB::table('peserta_didik')
            ->where('rombongan_belajar_id', $targetRombelId)
            ->orderBy('nama', 'asc')
            ->select('peserta_didik_id', 'nama', 'nisn')
            ->get();

        return view('dashboard.wali-kelas.presensi', [
            'hasRombel'    => true,
            'isAdmin'      => $ctx['isAdmin'],
            'rombelList'   => $ctx['rombelList'],
            'activeRombel' => $activeRombel,
            'waliNama'     => $ctx['waliNama'],
            'tanggal'      => $tanggal,
            'statusHari'   => $statusHari,
            'pengaturan'   => $pengaturan,
            'list'         => $list,
            'allSiswa'     => $allSiswa,
            'total'        => $list->total(),
            'totalSiswa'   => $totalSiswa,
            'summary'      => $summary,
            'q'            => $q,
            'statusFilter' => $statusFilter,
            'perPage'      => $perPage,
            'sort'         => $sort,
            'sortDir'      => $sortDir,
            'canUpdate'    => $canUpdate,
        ]);

    }

    /**
     * API Simpan Presensi Manual oleh Wali Kelas
     * Aturan:
     * 1. Presensi mandiri (RFID/QR) TIDAK DAPAT diubah.
     * 2. Presensi manual HANYA BISA dicatat 1 KALI per peserta didik (bersifat final).
     */
    public function simpanPresensiManual(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Sesi login telah berakhir.'], 401);
        }

        if (!RolePermission::isWaliKelasOrAdmin($user) || !RolePermission::canAccess($user, 'menu_wali_kelas_presensi', 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses mencatat presensi kelas.'], 403);
        }

        $request->validate([
            'peserta_didik_id' => 'required|string',
            'tanggal'          => 'required|date',
            'action'           => 'required|string|in:masuk,terlambat,sakit,izin,pulang',
            'keterangan'       => 'nullable|string|max:255',
        ]);

        $pdId    = $request->input('peserta_didik_id');
        $tanggal = $request->input('tanggal');
        $action  = $request->input('action');
        $ket     = trim($request->input('keterangan', ''));

        $ctx = $this->resolveWaliKelasContext($request, $user);
        $activeRombel = $ctx['activeRombel'];

        if (!$activeRombel) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki rombel binaan aktif.'], 422);
        }

        // Cari siswa dan verifikasi rombel
        $siswa = DB::table('peserta_didik')
            ->where('peserta_didik_id', $pdId)
            ->first();

        if (!$siswa) {
            return response()->json(['status' => 'error', 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        if ($siswa->rombongan_belajar_id !== $activeRombel->rombongan_belajar_id && !$ctx['isAdmin']) {
            return response()->json(['status' => 'error', 'message' => 'Peserta didik ini berada di luar rombel binaan Anda.'], 403);
        }

        // Periksa Kalender Pendidikan
        $statusHari = KalenderPendidikan::getStatusHari($tanggal, 'pd');
        if ($statusHari['mode'] === 'libur') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Hari ini adalah hari libur sekolah resmi (' . ($statusHari['agenda']?->nama_kegiatan ?? 'Kalender Pendidikan') . '). Presensi tidak dapat dilakukan.',
            ], 422);
        }

        $presensi = PresensiHarian::where('peserta_didik_id', $pdId)
            ->where('tanggal', $tanggal)
            ->first();

        // ATURAN 1: Wali kelas TIDAK BISA mengubah presensi mandiri (RFID/QR)
        if ($presensi && in_array($presensi->metode_masuk, ['rfid', 'qr', 'kiosk'], true)) {
            $metodeStr = strtoupper($presensi->metode_masuk);
            return response()->json([
                'status'  => 'error',
                'message' => "Peserta didik {$siswa->nama} telah melakukan presensi secara mandiri melalui {$metodeStr}. Presensi mandiri tidak dapat diubah oleh Wali Kelas.",
            ], 403);
        }

        // ATURAN 2: Presensi manual hanya bisa dilakukan 1x (tidak dapat diubah)
        if ($presensi && $presensi->metode_masuk === 'manual') {
            if ($action === 'pulang') {
                if (!empty($presensi->jam_pulang)) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => "Presensi kepulangan untuk {$siswa->nama} sudah tercatat pada pukul {$presensi->jam_pulang} WIB dan tidak dapat diubah.",
                    ], 422);
                }

                $presensi->jam_pulang = now()->format('H:i:s');
                $presensi->metode_pulang = 'manual';
                $presensi->status_ketepatan_pulang = 'tepat_waktu';
                $presensi->verified_by = $ctx['waliNama'];
                if ($ket !== '') {
                    $presensi->keterangan = ($presensi->keterangan ? $presensi->keterangan . '; ' : '') . $ket;
                }
                $presensi->save();

                return response()->json([
                    'status'  => 'success',
                    'message' => "Presensi kepulangan manual untuk {$siswa->nama} berhasil dicatat ({$presensi->jam_pulang} WIB).",
                ]);
            }

            // Mencoba mengubah status masuk/izin/sakit yang sudah pernah dicatat manual
            return response()->json([
                'status'  => 'error',
                'message' => "Presensi manual untuk {$siswa->nama} sudah pernah dicatat hari ini ({$presensi->status_label}) dan bersifat final (hanya 1x per hari).",
            ], 422);
        }

        // Kasus presensi belum ada:
        if ($action === 'pulang') {
            return response()->json([
                'status'  => 'error',
                'message' => "Peserta didik {$siswa->nama} belum memiliki catatan kehadiran masuk hari ini.",
            ], 422);
        }

        $statusBaru = match ($action) {
            'masuk'     => 'H',
            'terlambat' => 'T',
            'sakit'     => 'S',
            'izin'      => 'I',
        };

        $currentTime = now()->format('H:i:s');
        $menitTerlambat = ($action === 'terlambat') ? 15 : 0;

        $presensi = PresensiHarian::create([
            'peserta_didik_id'       => $siswa->peserta_didik_id,
            'nisn'                   => $siswa->nisn,
            'rombongan_belajar_id'   => $siswa->rombongan_belajar_id,
            'tanggal'                => $tanggal,
            'status'                 => $statusBaru,
            'jam_masuk'              => in_array($statusBaru, ['H', 'T']) ? $currentTime : null,
            'menit_terlambat'        => $menitTerlambat,
            'status_ketepatan_masuk' => ($statusBaru === 'T' ? 'terlambat' : ($statusBaru === 'H' ? 'tepat_waktu' : null)),
            'metode_masuk'           => 'manual',
            'keterangan'             => $ket ?: "Dicatat manual oleh Wali Kelas: {$ctx['waliNama']}",
            'verified_by'            => $ctx['waliNama'],
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => "Presensi manual ({$presensi->status_label}) untuk {$siswa->nama} berhasil dicatat dan telah dikunci.",
        ]);
    }

    /**
     * Unduh Laporan Presensi PDF Multi-Periode
     * Tipe yang didukung:
     * 1. 'siswa'    -> Rekap Kartu Presensi Individual Siswa
     * 2. 'hari'     -> Rekap Lembar Kehadiran Harian Rombel
     * 3. 'bulan'    -> Matriks Presensi Bulanan (Tanggal 1 s.d. 31)
     * 4. 'semester' -> Rekapitulasi Presensi Per Semester
     * 5. 'tahun'    -> Rekapitulasi Presensi Per Tahun Ajaran
     */
    public function downloadPdf(Request $request, string $tipe)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');

        if (!RolePermission::isWaliKelasOrAdmin($user) || !RolePermission::canAccess($user, 'menu_wali_kelas_presensi', 'read')) {
            abort(403, 'Akses unduh laporan presensi tidak diizinkan.');
        }

        $ctx = $this->resolveWaliKelasContext($request, $user);
        $activeRombel = $ctx['activeRombel'];

        if (!$activeRombel) {
            return back()->with('error', 'Rombongan belajar tidak ditemukan.');
        }

        $sekolah = PresensiPengaturan::getSekolah();
        $targetRombelId = $activeRombel->rombongan_belajar_id;

        $tipe = strtolower($tipe);
        if (!in_array($tipe, ['siswa', 'hari', 'bulan', 'semester', 'tahun'], true)) {
            $tipe = 'hari';
        }

        $kepalaSekolah = Schema::hasTable('gtk')
            ? DB::table('gtk')->where(function ($w) {
                $w->where('jenis_ptk_id_str', 'LIKE', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'LIKE', '%Kepala Sekolah%');
            })->first()
            : null;

        $sekolahMeta = Schema::hasTable('sekolah_meta')
            ? \App\Models\SekolahMeta::first()
            : null;

        $viewData = [
            'tipe'          => $tipe,
            'sekolah'       => $sekolah,
            'sekolahMeta'   => $sekolahMeta,
            'activeRombel'  => $activeRombel,
            'waliNama'      => $ctx['waliNama'],
            'waliNip'       => $ctx['waliNip'] ?? '—',
            'kepalaSekolah' => $kepalaSekolah,
            'generatedAt'   => now()->translatedFormat('d F Y, H:i') . ' WIB',
        ];


        $paper = 'A4';
        $orientation = 'portrait';
        $safeRombel = str_replace(['/', '\\', ' '], '_', $activeRombel->nama);

        switch ($tipe) {
            case 'siswa':
                $siswaId = $request->get('siswa_id');
                $siswa = null;
                if ($siswaId) {
                    $siswa = DB::table('peserta_didik')->where('peserta_didik_id', $siswaId)->first();
                }
                if (!$siswa) {
                    $siswa = DB::table('peserta_didik')->where('rombongan_belajar_id', $targetRombelId)->orderBy('nama', 'asc')->first();
                }
                if (!$siswa) abort(404, 'Data peserta didik tidak ditemukan.');

                $bulan = $request->get('bulan', now()->format('Y-m'));
                $logs = PresensiHarian::where('peserta_didik_id', $siswa->peserta_didik_id)
                    ->where('tanggal', 'like', "{$bulan}%")
                    ->orderBy('tanggal', 'asc')
                    ->get();

                $stats = [
                    'total'     => $logs->count(),
                    'hadir'     => $logs->where('status', 'H')->count(),
                    'terlambat' => $logs->where('status', 'T')->count(),
                    'izin'      => $logs->where('status', 'I')->count(),
                    'sakit'     => $logs->where('status', 'S')->count(),
                    'alpha'     => $logs->where('status', 'A')->count(),
                ];
                $stats['persen'] = $stats['total'] > 0 ? round((($stats['hadir'] + $stats['terlambat']) / $stats['total']) * 100, 1) : 0;

                $viewData['siswa'] = $siswa;
                $viewData['bulan'] = $bulan;
                $viewData['bulanLabel'] = Carbon::parse($bulan . '-01')->translatedFormat('F Y');
                $viewData['logs'] = $logs;
                $viewData['stats'] = $stats;
                $fileName = "Rekap_Presensi_Peserta_Didik_{$siswa->nisn}_{$bulan}.pdf";
                break;

            case 'hari':
                $tanggal = $request->get('tanggal', now()->toDateString());
                $statusHari = KalenderPendidikan::getStatusHari($tanggal, 'pd');

                $siswaList = DB::table('peserta_didik as pd')
                    ->leftJoin('presensi_harian as ph', function ($join) use ($tanggal) {
                        $join->on('pd.peserta_didik_id', '=', 'ph.peserta_didik_id')
                            ->where('ph.tanggal', '=', $tanggal);
                    })
                    ->where('pd.rombongan_belajar_id', $targetRombelId)
                    ->select(
                        'pd.peserta_didik_id', 'pd.nama', 'pd.nisn', 'pd.nipd', 'pd.jenis_kelamin',
                        'ph.status', 'ph.jam_masuk', 'ph.jam_pulang', 'ph.menit_terlambat', 'ph.metode_masuk', 'ph.keterangan'
                    )
                    ->orderBy('pd.nama', 'asc')
                    ->get();

                $viewData['tanggal'] = $tanggal;
                $viewData['tanggalLabel'] = Carbon::parse($tanggal)->translatedFormat('l, d F Y');
                $viewData['statusHari'] = $statusHari;
                $viewData['siswaList'] = $siswaList;
                $fileName = "Presensi_Harian_{$safeRombel}_{$tanggal}.pdf";
                break;

            case 'bulan':
                $bulan = $request->get('bulan', now()->format('Y-m'));
                $carbonBulan = Carbon::parse($bulan . '-01');
                $daysInMonth = $carbonBulan->daysInMonth;

                $siswaList = DB::table('peserta_didik')
                    ->where('rombongan_belajar_id', $targetRombelId)
                    ->orderBy('nama', 'asc')
                    ->get();

                $logsBulan = DB::table('presensi_harian')
                    ->where('rombongan_belajar_id', $targetRombelId)
                    ->where('tanggal', 'like', "{$bulan}%")
                    ->get()
                    ->groupBy('peserta_didik_id');

                $matrix = [];
                foreach ($siswaList as $s) {
                    $sLogs = $logsBulan->get($s->peserta_didik_id, collect());
                    $daysMap = [];
                    for ($d = 1; $d <= $daysInMonth; $d++) {
                        $tglStr = sprintf('%s-%02d', $bulan, $d);
                        $log = $sLogs->firstWhere('tanggal', $tglStr);
                        $daysMap[$d] = $log ? $log->status : '-';
                    }
                    $h = $sLogs->where('status', 'H')->count();
                    $t = $sLogs->where('status', 'T')->count();
                    $i = $sLogs->where('status', 'I')->count();
                    $sakit = $sLogs->where('status', 'S')->count();
                    $a = $sLogs->where('status', 'A')->count();
                    $total = $sLogs->count();
                    $persen = $total > 0 ? round((($h + $t) / $total) * 100, 1) : 0;

                    $matrix[] = [
                        'siswa'   => $s,
                        'days'    => $daysMap,
                        'rekap'   => ['h' => $h, 't' => $t, 'i' => $i, 's' => $sakit, 'a' => $a, 'persen' => $persen],
                    ];
                }

                $viewData['bulan'] = $bulan;
                $viewData['bulanLabel'] = $carbonBulan->translatedFormat('F Y');
                $viewData['daysInMonth'] = $daysInMonth;
                $viewData['matrix'] = $matrix;
                $orientation = 'landscape';
                $fileName = "Rekap_Bulanan_{$safeRombel}_{$bulan}.pdf";
                break;

            case 'semester':
                $semester = (int) $request->get('semester', (now()->month >= 7 ? 1 : 2));
                $tahun = (int) $request->get('tahun', now()->year);
                $bulanList = ($semester === 1)
                    ? [7, 8, 9, 10, 11, 12]
                    : [1, 2, 3, 4, 5, 6];

                $tahunAkademik = ($semester === 1)
                    ? "{$tahun}/" . ($tahun + 1)
                    : ($tahun - 1) . "/{$tahun}";

                $siswaList = DB::table('peserta_didik')
                    ->where('rombongan_belajar_id', $targetRombelId)
                    ->orderBy('nama', 'asc')
                    ->get();

                $logsSemester = DB::table('presensi_harian')
                    ->where('rombongan_belajar_id', $targetRombelId)
                    ->where(function ($q) use ($bulanList, $tahun, $semester) {
                        foreach ($bulanList as $b) {
                            $y = ($semester === 2 && $b >= 7) ? ($tahun - 1) : $tahun;
                            $ym = sprintf('%04d-%02d', $y, $b);
                            $q->orWhere('tanggal', 'like', "{$ym}%");
                        }
                    })
                    ->get()
                    ->groupBy('peserta_didik_id');

                $rekapSemester = [];
                foreach ($siswaList as $s) {
                    $sLogs = $logsSemester->get($s->peserta_didik_id, collect());
                    $h = $sLogs->where('status', 'H')->count();
                    $t = $sLogs->where('status', 'T')->count();
                    $i = $sLogs->where('status', 'I')->count();
                    $sakit = $sLogs->where('status', 'S')->count();
                    $a = $sLogs->where('status', 'A')->count();
                    $total = $sLogs->count();
                    $persen = $total > 0 ? round((($h + $t) / $total) * 100, 1) : 0;

                    $rekapSemester[] = [
                        'siswa' => $s,
                        'h'     => $h,
                        't'     => $t,
                        'i'     => $i,
                        's'     => $sakit,
                        'a'     => $a,
                        'total' => $total,
                        'persen'=> $persen,
                    ];
                }

                $viewData['semester'] = $semester;
                $viewData['tahunAkademik'] = $tahunAkademik;
                $viewData['rekapSemester'] = $rekapSemester;
                $orientation = 'landscape';
                $fileName = "Rekap_Semester_{$semester}_{$safeRombel}.pdf";
                break;

            case 'tahun':
                $tahun = (int) ($request->get('tahun_ajaran') ?: $request->get('tahun', (now()->month >= 7 ? now()->year : now()->year - 1)));
                $tahunAjaran = "{$tahun}/" . ($tahun + 1);

                $startDate = "{$tahun}-07-01";
                $endDate   = ($tahun + 1) . "-06-30";

                $siswaList = DB::table('peserta_didik')
                    ->where('rombongan_belajar_id', $targetRombelId)
                    ->orderBy('nama', 'asc')
                    ->get();

                $logsTahunQuery = DB::table('presensi_harian')
                    ->where('rombongan_belajar_id', $targetRombelId);
                if (DB::table('presensi_harian')->where('rombongan_belajar_id', $targetRombelId)->whereBetween('tanggal', [$startDate, $endDate])->exists()) {
                    $logsTahunQuery->whereBetween('tanggal', [$startDate, $endDate]);
                }
                $logsTahun = $logsTahunQuery->get()->groupBy('peserta_didik_id');


                $rekapTahun = [];
                foreach ($siswaList as $s) {
                    $sLogs = $logsTahun->get($s->peserta_didik_id, collect());
                    $h = $sLogs->where('status', 'H')->count();
                    $t = $sLogs->where('status', 'T')->count();
                    $i = $sLogs->where('status', 'I')->count();
                    $sakit = $sLogs->where('status', 'S')->count();
                    $a = $sLogs->where('status', 'A')->count();
                    $total = $sLogs->count();
                    $persen = $total > 0 ? round((($h + $t) / $total) * 100, 1) : 0;

                    $rekapTahun[] = [
                        'siswa' => $s,
                        'h'     => $h,
                        't'     => $t,
                        'i'     => $i,
                        's'     => $sakit,
                        'a'     => $a,
                        'total' => $total,
                        'persen'=> $persen,
                    ];
                }

                $viewData['tahunAjaran'] = $tahunAjaran;
                $viewData['rekapTahun'] = $rekapTahun;
                $orientation = 'landscape';
                $fileName = "Rekap_Tahunan_{$safeRombel}.pdf";
                break;
        }

        // Render langsung ke view cetak resmi (ringan, ramah printer fisik, dan bisa Save as PDF via browser)
        return view('dashboard.wali-kelas.pdf-rekap', $viewData);
    }
}
