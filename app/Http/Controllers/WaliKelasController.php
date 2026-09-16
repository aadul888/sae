<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\RolePermission;
use App\Models\PesertaDidikMeta;

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
                    'ptk_id_str'
                )
                ->get();

            $selectedId = $request->get('rombel_id', $rombelList->first()?->rombongan_belajar_id ?? '');
            $activeRombel = $rombelList->firstWhere('rombongan_belajar_id', $selectedId) ?: $rombelList->first();

            return [
                'isAdmin'      => true,
                'isGuru'       => false,
                'rombelList'   => $rombelList,
                'activeRombel' => $activeRombel,
                'waliNama'     => $activeRombel?->ptk_id_str ?: 'Administrator',
            ];
        }

        // Pengguna Peran Guru (Wali Kelas)
        $activeRombel = RolePermission::getWaliKelasRombelInfo($user);
        $waliNama = is_array($user) ? ($user['name'] ?? ($user['nama'] ?? 'Guru')) : ($user->name ?? ($user->nama ?? 'Guru'));

        return [
            'isAdmin'      => false,
            'isGuru'       => true,
            'rombelList'   => $activeRombel ? collect([$activeRombel]) : collect(),
            'activeRombel' => $activeRombel,
            'waliNama'     => $activeRombel?->ptk_id_str ?: $waliNama,
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
        $list = $query->paginate($perPage)->withQueryString();

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
        $list = $query->paginate($perPage)->withQueryString();

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
     * API JSON Detail Peserta Didik Aktif (Dibatasi per kelas binaan)
     */
    public function showPesertaDidik(string $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::isWaliKelasOrAdmin($user)) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $student = DB::table('peserta_didik')->where('peserta_didik_id', $id)->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        // Keamanan Ketat: Jika guru, verifikasi siswa berada di kelas binaan miliknya
        if ($role === 'guru') {
            $myRombel = RolePermission::getWaliKelasRombelInfo($user);
            $myRombelId = $myRombel?->rombongan_belajar_id;
            $myRombelName = $myRombel?->nama;

            $isMatch = ($myRombelId && $student->rombongan_belajar_id === $myRombelId)
                    || ($myRombelName && $student->nama_rombel === $myRombelName);

            if (!$isMatch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki wewenang mengakses data siswa di luar kelas binaan Anda.'
                ], 403);
            }
        }

        $meta = PesertaDidikMeta::where('peserta_didik_id', $student->peserta_didik_id)->first();
        $student->foto_url = $meta?->foto_url;
        $student->formatted_foto_size = $meta?->formatted_foto_size;

        return response()->json([
            'success' => true,
            'data'    => $student,
        ]);
    }

    /**
     * API JSON Detail Peserta Didik Tidak Aktif (Dibatasi per kelas binaan)
     */
    public function showPesertaDidikTidakAktif(string $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::isWaliKelasOrAdmin($user)) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $student = DB::table('peserta_didik_tidak_aktif')->where('id', $id)->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        // Keamanan Ketat: Jika guru, verifikasi riwayat rombel siswa
        if ($role === 'guru') {
            $myRombel = RolePermission::getWaliKelasRombelInfo($user);
            $myRombelId = $myRombel?->rombongan_belajar_id;
            $myRombelName = $myRombel?->nama;

            $isMatch = ($myRombelId && $student->rombongan_belajar_id === $myRombelId)
                    || ($myRombelName && $student->nama_rombel_terakhir === $myRombelName);

            if (!$isMatch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki wewenang mengakses data siswa di luar kelas binaan Anda.'
                ], 403);
            }
        }

        $student->foto_url = !empty($student->foto_path) ? asset('storage/' . ltrim($student->foto_path, '/')) : null;

        return response()->json([
            'success' => true,
            'data'    => $student,
        ]);
    }
}
