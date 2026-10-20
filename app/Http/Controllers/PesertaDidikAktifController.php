<?php

namespace App\Http\Controllers;

use App\Models\PesertaDidikMeta;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\SimpleXlsxExporter;
use App\Services\SimpleXlsxReader;
use App\Support\SemesterHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ZipArchive;

class PesertaDidikAktifController extends Controller
{
    private const SORTABLE = ['nama', 'nisn', 'nipd', 'jenis_kelamin', 'nama_rombel', 'tingkat_pendidikan_id'];

    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        // Proteksi: peserta didik reguler tidak boleh mengakses, kecuali jika ditunjuk sebagai Koordinator Kelas
        $isKoordinator = ($role === 'peserta_didik') && \App\Models\RolePermission::isKoordinator($user);
        $hasMenuAccess = \App\Models\RolePermission::canAccess($user, 'menu_peserta_didik_aktif') || \App\Models\RolePermission::canAccess($user, 'menu_wali_kelas_aktif');

        if (($role === 'peserta_didik' && !$isKoordinator) || !$hasMenuAccess) {
            return redirect()->route('dashboard.' . ($role ?: 'peserta_didik'))->with('error', 'Akses ke menu Peserta Didik Aktif dinonaktifkan.');
        }

        // Hak akses granular CRUD modul Peserta Didik Aktif
        $canCreate = \App\Models\RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'create') || \App\Models\RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'create');
        $canRead   = \App\Models\RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'read') || \App\Models\RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'read');
        $canUpdate = \App\Models\RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'update') || \App\Models\RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'update');
        $canDelete = \App\Models\RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'delete') || \App\Models\RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'delete');

        // Cek wewenang unggah foto & kelola: Admin, Wali Kelas, Koordinator Kelas, atau peran dengan hak update/create
        $isAdmin = ($role === 'admin');
        $isWaliOrAdmin = \App\Models\RolePermission::isWaliKelasOrAdmin($user);
        $canManageStudentPhotos = $canUpdate || $canCreate || $isAdmin || $isWaliOrAdmin;
        $waliRombel = \App\Models\RolePermission::getWaliKelasRombel($user);

        $q       = trim($request->get('q', ''));
        $rombel  = trim($request->get('rombel', ''));
        $gender  = trim($request->get('gender', ''));
        $perPage = (int) ($request->get('perPage') ?? $request->get('per_page') ?? $request->get('perPageSelect') ?? 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }
        $sort    = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'nama';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        if (!Schema::hasTable('peserta_didik')) {
            return view('dashboard.peserta-didik-aktif', [
                'list' => collect(),
                'total' => 0,
                'summary' => ['total' => 0, 'laki' => 0, 'perempuan' => 0, 'rombel' => 0],
                'filterRombel' => collect(),
                'q' => $q,
                'rombel' => $rombel,
                'gender' => $gender,
                'perPage' => $perPage,
                'sort' => $sort,
                'sortDir' => $sortDir,
            ]);
        }

        // Daftar rombel untuk filter
        $filterRombel = DB::table('peserta_didik')
            ->whereNotNull('nama_rombel')
            ->where('nama_rombel', '<>', '')
            ->distinct()
            ->pluck('nama_rombel')
            ->sort()
            ->values();

        $baseQuery = DB::table('peserta_didik')
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
                'tinggi_badan',
                'berat_badan',
                'anak_keberapa',
                'kebutuhan_khusus',
                'sekolah_asal',
                'tanggal_masuk_sekolah',
                'jenis_pendaftaran_id_str as jenis_pendaftaran',
                'email',
                'alamat_jalan',
                'nomor_telepon_seluler as no_hp'
            );

        if ($q !== '') {
            $baseQuery->where(function ($sub) use ($q) {
                $sub->where('nama', 'LIKE', "%{$q}%")
                    ->orWhere('nisn', 'LIKE', "%{$q}%")
                    ->orWhere('nipd', 'LIKE', "%{$q}%")
                    ->orWhere('nik', 'LIKE', "%{$q}%")
                    ->orWhere('nama_rombel', 'LIKE', "%{$q}%");
            });
        }

        if ($rombel !== '') {
            $baseQuery->where('nama_rombel', $rombel);
        }

        if ($gender !== '') {
            $baseQuery->where('jenis_kelamin', $gender);
        }

        // Urutkan di tingkat database (case-insensitive & trimmed)
        if ($sort === 'nama') {
            $baseQuery->orderByRaw("LOWER(TRIM(nama)) {$sortDir}")->orderBy('nisn', 'asc');
        } else {
            $baseQuery->orderBy($sort, $sortDir)->orderByRaw("LOWER(TRIM(nama)) asc");
        }

        $allResults = $baseQuery->get();
        $total = $allResults->count();

        $summary = [
            'total' => $total,
            'laki' => $allResults->where('jenis_kelamin', 'L')->count(),
            'perempuan' => $allResults->where('jenis_kelamin', 'P')->count(),
            'rombel' => $allResults->pluck('nama_rombel')->filter()->unique()->count(),
        ];

        // Penyusunan abjad presisi tingkat koleksi (kebal perbedaan huruf kapital / non-kapital Dapodik)
        $sorted = $allResults->sortBy(function ($item) use ($sort) {
            $val = $item->{$sort} ?? '';
            if (is_string($val)) {
                return mb_strtolower(trim(preg_replace('/\s+/', ' ', $val)), 'UTF-8');
            }
            return $val;
        }, SORT_NATURAL | SORT_FLAG_CASE, $sortDir === 'desc');

        $currentPage = (int) $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $itemsForPage = $sorted->slice($offset, $perPage)->values();

        // Ambil info foto persisten dari peserta_didik_meta
        $pdIds = $itemsForPage->pluck('peserta_didik_id')->filter()->values()->all();
        $metaMap = collect();
        if (!empty($pdIds) && Schema::hasTable('peserta_didik_meta')) {
            $metaMap = \App\Models\PesertaDidikMeta::whereIn('peserta_didik_id', $pdIds)
                ->get()
                ->keyBy('peserta_didik_id');
        }

        foreach ($itemsForPage as $item) {
            $meta = $metaMap[$item->peserta_didik_id] ?? null;
            $item->foto_url = $meta?->foto_url;
            $item->foto_size = $meta?->formatted_foto_size;
            $item->foto_width = $meta?->foto_width;
            $item->foto_height = $meta?->foto_height;
            $item->is_koordinator = (bool) ($meta?->is_koordinator ?? false);
            $item->jabatan_koordinator = $meta?->jabatan_koordinator ?? 'Koordinator Kelas';
        }

        $list = new \Illuminate\Pagination\LengthAwarePaginator(
            $itemsForPage,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('dashboard.peserta-didik-aktif', compact(
            'list',
            'total',
            'summary',
            'filterRombel',
            'q',
            'rombel',
            'gender',
            'perPage',
            'sort',
            'sortDir',
            'canManageStudentPhotos',
            'waliRombel',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete',
            'isWaliOrAdmin',
            'isAdmin'
        ));
    }

    /**
     * Detail peserta didik via JSON untuk modal (Khusus Admin pada Manajemen Data)
     */
    public function show(Request $request, string|int $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Hanya Administrator yang memiliki wewenang melihat data lengkap peserta didik.'], 403);
        }

        $pesertaDidik = DB::table('peserta_didik')
            ->where('peserta_didik_id', $id)
            ->orWhere('nisn', $id)
            ->orWhere('nipd', $id)
            ->first();

        if (!$pesertaDidik) {
            return response()->json(['status' => 'error', 'message' => 'Peserta Didik tidak ditemukan'], 404);
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

        return response()->json([
            'status' => 'success',
            'data' => $pesertaDidik,
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
     * Unggah dan auto-kompresi pasfoto peserta didik (khusus format PNG)
     */
    public function uploadFoto(Request $request, \App\Services\ImageOptimizerService $optimizer)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $canUpdate = \App\Models\RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'update') || \App\Models\RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'update');
        $canCreate = \App\Models\RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'create') || \App\Models\RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'create');
        $isAdmin = ($role === 'admin');
        $isWaliOrAdmin = \App\Models\RolePermission::isWaliKelasOrAdmin($user);

        if ((!$canUpdate && !$canCreate) && !$isAdmin && !$isWaliOrAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk mengunggah pasfoto peserta didik.'
            ], 403);
        }

        $request->validate([
            'peserta_didik_id' => 'required|string|max:50',
            'foto' => 'required|file|mimes:png|max:5120',
        ], [
            'peserta_didik_id.required' => 'ID peserta didik wajib disertakan.',
            'foto.required' => 'File foto wajib diunggah.',
            'foto.mimes' => 'Format foto harus berupa PNG (.png) untuk kebutuhan kartu pelajar digital.',
            'foto.max' => 'Ukuran file foto maksimal adalah 5 MB.',
        ]);

        $pdId = $request->input('peserta_didik_id');
        $pesertaDidik = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
        if (!$pesertaDidik) {
            return response()->json(['status' => 'error', 'message' => 'Data peserta didik tidak ditemukan di database.'], 404);
        }

        // Jika bukan Administrator murni, pastikan Wali Kelas / Koordinator hanya mengedit siswa di kelas perwaliannya
        if (!$isAdmin && $isWaliOrAdmin) {
            $waliRombelInfo = \App\Models\RolePermission::getWaliKelasRombelInfo($user);
            $waliRombelName = $waliRombelInfo?->nama ?: \App\Models\RolePermission::getWaliKelasRombel($user);
            $waliRombelId = $waliRombelInfo?->rombongan_belajar_id ?? null;

            $isMatch = false;
            if ($waliRombelId && !empty($pesertaDidik->rombongan_belajar_id) && $pesertaDidik->rombongan_belajar_id === $waliRombelId) {
                $isMatch = true;
            } elseif ($waliRombelName && !empty($pesertaDidik->nama_rombel) && strcasecmp(trim($pesertaDidik->nama_rombel), trim($waliRombelName)) === 0) {
                $isMatch = true;
            } elseif (!$waliRombelName && !$waliRombelId) {
                $isMatch = true;
            }

            if (!$isMatch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak: Anda hanya berwenang mengubah pasfoto peserta didik di kelas binaan Anda (' . ($waliRombelName ?: 'Kelas Binaan') . ').'
                ], 403);
            }
        }

        try {
            $meta = \App\Models\PesertaDidikMeta::firstOrNew(['peserta_didik_id' => $pdId]);

            // Hapus file lama jika ada
            if ($meta->foto_path) {
                $optimizer->deleteFile($meta->foto_path);
            }

            // Optimasi dan simpan PNG ke assets/peserta-didik/foto
            $prefix = 'foto_' . ($pesertaDidik->nisn ?: preg_replace('/[^a-zA-Z0-9_-]/', '', $pdId));
            $result = $optimizer->optimizeAndSavePng(
                $request->file('foto'),
                \App\Services\ImageOptimizerService::ASSET_DIR_FOTO_PESERTA_DIDIK,
                $prefix,
                1000
            );

            // Simpan metadata ke tabel peserta_didik_meta
            $meta->nisn = $pesertaDidik->nisn;
            $meta->foto_path = $result['path'];
            $meta->foto_size = $result['size'];
            $meta->foto_width = $result['width'];
            $meta->foto_height = $result['height'];
            $meta->save();

            // Sinkronkan ke tabel pengguna agar konsisten global
            DB::table('pengguna')->where('peserta_didik_id', $pdId)->update(['foto_path' => $result['path']]);

            // Jika sesi yang sedang login adalah siswa ini, perbarui session aktif seketika
            $sessUser = session('user');
            if (is_array($sessUser) && (($sessUser['peserta_didik_id'] ?? null) === $pdId || ($sessUser['pengguna_id'] ?? null) === $pdId)) {
                $sessUser['foto_path'] = $result['path'];
                $sessUser['foto_url'] = $meta->foto_url;
                session(['user' => $sessUser]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Foto peserta didik berhasil disimpan dan dikompresi.',
                'data' => [
                    'foto_url' => $meta->foto_url,
                    'foto_size' => $meta->formatted_foto_size,
                    'width' => $result['width'],
                    'height' => $result['height'],
                    'savings' => $result['savings_percent'],
                    'peserta_didik_id' => $pdId,
                    'nama' => $pesertaDidik->nama,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses foto: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hapus pasfoto peserta didik
     */
    public function deleteFoto(Request $request, string|int $id, \App\Services\ImageOptimizerService $optimizer)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $canDelete = \App\Models\RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'delete') || \App\Models\RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'delete');
        $isAdmin = ($role === 'admin');
        $isWaliOrAdmin = \App\Models\RolePermission::isWaliKelasOrAdmin($user);

        if (!$canDelete && !$isAdmin && !$isWaliOrAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk menghapus pasfoto peserta didik.'
            ], 403);
        }

        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $id)->first();
        if (!$isAdmin && $isWaliOrAdmin && $pd) {
            $waliRombelInfo = \App\Models\RolePermission::getWaliKelasRombelInfo($user);
            $waliRombelName = $waliRombelInfo?->nama ?: \App\Models\RolePermission::getWaliKelasRombel($user);
            $waliRombelId = $waliRombelInfo?->rombongan_belajar_id ?? null;

            $isMatch = false;
            if ($waliRombelId && !empty($pd->rombongan_belajar_id) && $pd->rombongan_belajar_id === $waliRombelId) {
                $isMatch = true;
            } elseif ($waliRombelName && !empty($pd->nama_rombel) && strcasecmp(trim($pd->nama_rombel), trim($waliRombelName)) === 0) {
                $isMatch = true;
            } elseif (!$waliRombelName && !$waliRombelId) {
                $isMatch = true;
            }

            if (!$isMatch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak: Anda hanya berwenang menghapus pasfoto peserta didik di kelas binaan Anda (' . ($waliRombelName ?: 'Kelas Binaan') . ').'
                ], 403);
            }
        }

        $meta = \App\Models\PesertaDidikMeta::where('peserta_didik_id', $id)->first();
        if (!$meta || empty($meta->foto_path)) {
            return response()->json(['status' => 'error', 'message' => 'Foto peserta didik tidak ditemukan.'], 404);
        }

        $optimizer->deleteFile($meta->foto_path);
        $meta->foto_path = null;
        $meta->foto_size = null;
        $meta->foto_width = null;
        $meta->foto_height = null;
        $meta->save();

        // Sinkronkan ke tabel pengguna
        DB::table('pengguna')->where('peserta_didik_id', $id)->update(['foto_path' => null]);

        // Perbarui sesi aktif jika siswa ini sedang login
        $sessUser = session('user');
        if (is_array($sessUser) && (($sessUser['peserta_didik_id'] ?? null) === $id || ($sessUser['pengguna_id'] ?? null) === $id)) {
            $sessUser['foto_path'] = null;
            $sessUser['foto_url'] = null;
            session(['user' => $sessUser]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Foto peserta didik berhasil dihapus.',
            'data' => [
                'peserta_didik_id' => $id,
            ],
        ]);
    }

    /**
     * Mengambil seluruh peserta didik dalam rombel kelas yang dipilih (36-52 siswa)
     */
    public function getRombelMembers(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Hanya Administrator yang diizinkan mengakses data rombel kelas di menu Manajemen Data.'
            ], 403);
        }

        $rombelName = trim($request->get('rombel', ''));
        if (!$rombelName) {
            return response()->json(['status' => 'error', 'message' => 'Rombel kelas wajib dipilih.'], 422);
        }

        if (!Schema::hasTable('peserta_didik')) {
            return response()->json(['status' => 'error', 'message' => 'Tabel peserta didik belum tersedia.'], 500);
        }

        // Ambil data siswa di rombel ini
        $students = DB::table('peserta_didik')
            ->where('nama_rombel', $rombelName)
            ->select(
                'peserta_didik_id',
                'nama',
                'nisn',
                'nipd',
                'nik',
                'jenis_kelamin',
                'nama_rombel'
            )
            ->orderBy('nama', 'asc')
            ->get();

        // Ambil metadata foto untuk seluruh siswa di kelas ini
        $pdIds = $students->pluck('peserta_didik_id')->filter()->toArray();
        $metas = collect();
        if (Schema::hasTable('peserta_didik_meta') && !empty($pdIds)) {
            $metas = \App\Models\PesertaDidikMeta::whereIn('peserta_didik_id', $pdIds)->get()->keyBy('peserta_didik_id');
        }

        $totalWithFoto = 0;
        $mapped = $students->map(function ($s, $idx) use ($metas, &$totalWithFoto) {
            $m = $metas[$s->peserta_didik_id] ?? null;
            $hasFoto = !empty($m?->foto_path);
            if ($hasFoto) $totalWithFoto++;

            return [
                'no_urut' => $idx + 1,
                'peserta_didik_id' => $s->peserta_didik_id,
                'nama' => $s->nama,
                'nisn' => $s->nisn ?: '',
                'nipd' => $s->nipd ?: '',
                'nik' => $s->nik ?: '',
                'jenis_kelamin' => $s->jenis_kelamin ?: '-',
                'nama_rombel' => $s->nama_rombel,
                'has_foto' => $hasFoto,
                'foto_url' => $m?->foto_url,
                'foto_size' => $m?->formatted_foto_size,
            ];
        });

        return response()->json([
            'status' => 'success',
            'rombel' => $rombelName,
            'total' => $mapped->count(),
            'total_with_foto' => $totalWithFoto,
            'total_without_foto' => $mapped->count() - $totalWithFoto,
            'data' => $mapped,
        ]);
    }

    /**
     * Menerima upload foto masal atau satu per satu secara batch dari antrean async klien
     */
    public function bulkUploadFoto(Request $request, \App\Services\ImageOptimizerService $optimizer)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $canUpdate = \App\Models\RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'update') || \App\Models\RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'update');
        $canCreate = \App\Models\RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'create') || \App\Models\RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'create');
        $isAdmin = ($role === 'admin');
        $isWaliOrAdmin = \App\Models\RolePermission::isWaliKelasOrAdmin($user);

        if ((!$canUpdate && !$canCreate) && !$isAdmin && !$isWaliOrAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk mengunggah pasfoto masal.'
            ], 403);
        }

        // Jika upload single item dalam async loop
        if ($request->hasFile('foto') && $request->filled('peserta_didik_id')) {
            return $this->uploadFoto($request, $optimizer);
        }

        if ($request->hasFile('zip')) {
            return $this->bulkUploadFotoZip($request, $optimizer);
        }

        // Jika upload batch multipart (files + mappings)
        $files = $request->file('files', []);
        $mappingsRaw = $request->input('mappings', '[]');
        $mappings = json_decode($mappingsRaw, true) ?: [];

        if (empty($files) || !is_array($files)) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada berkas foto yang dikirim.'], 422);
        }

        $successCount = 0;
        $failedCount = 0;
        $results = [];

        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();
            $pdId = $mappings[$originalName] ?? null;

            if (!$pdId) {
                // Coba cocokkan langsung dari nama file jika tanpa ekstensi berupa NISN
                $cleanName = pathinfo($originalName, PATHINFO_FILENAME);
                $foundStudent = DB::table('peserta_didik')
                    ->where('nisn', $cleanName)
                    ->orWhere('nipd', $cleanName)
                    ->orWhere('peserta_didik_id', $cleanName)
                    ->first();
                $pdId = $foundStudent?->peserta_didik_id;
            }

            if (!$pdId) {
                $failedCount++;
                $results[] = [
                    'filename' => $originalName,
                    'status' => 'error',
                    'message' => 'Tidak dapat menemukan data peserta didik untuk berkas ini.',
                ];
                continue;
            }

            $student = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
            if (!$student) {
                $failedCount++;
                $results[] = [
                    'filename' => $originalName,
                    'status' => 'error',
                    'message' => "Peserta didik ID {$pdId} tidak ditemukan.",
                ];
                continue;
            }

            try {
                $meta = \App\Models\PesertaDidikMeta::firstOrNew(['peserta_didik_id' => $pdId]);
                if ($meta->foto_path) {
                    $optimizer->deleteFile($meta->foto_path);
                }

                $prefix = 'foto_' . ($student->nisn ?: preg_replace('/[^a-zA-Z0-9_-]/', '', $pdId));
                $optResult = $optimizer->optimizeAndSavePng(
                    $file,
                    \App\Services\ImageOptimizerService::ASSET_DIR_FOTO_PESERTA_DIDIK,
                    $prefix,
                    1000
                );

                $meta->nisn = $student->nisn;
                $meta->foto_path = $optResult['path'];
                $meta->foto_size = $optResult['size'];
                $meta->foto_width = $optResult['width'];
                $meta->foto_height = $optResult['height'];
                $meta->save();

                // Sinkronkan ke tabel pengguna
                DB::table('pengguna')->where('peserta_didik_id', $pdId)->update(['foto_path' => $optResult['path']]);

                $successCount++;
                $results[] = [
                    'peserta_didik_id' => $pdId,
                    'nama' => $student->nama,
                    'nisn' => $student->nisn,
                    'filename' => $originalName,
                    'status' => 'success',
                    'foto_url' => $meta->foto_url,
                    'foto_size' => $meta->formatted_foto_size,
                    'savings' => $optResult['savings_percent'],
                ];
            } catch (\Throwable $e) {
                $failedCount++;
                $results[] = [
                    'filename' => $originalName,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Proses masal selesai: {$successCount} foto berhasil disimpan, {$failedCount} gagal.",
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'data' => $results,
        ]);
    }

    private function bulkUploadFotoZip(Request $request, \App\Services\ImageOptimizerService $optimizer)
    {
        $request->validate([
            'zip' => 'required|file|mimes:zip|max:51200',
        ], [
            'zip.required' => 'File ZIP wajib diunggah.',
            'zip.mimes' => 'Format arsip harus berupa ZIP (.zip).',
            'zip.max' => 'Ukuran file ZIP maksimal adalah 50 MB.',
        ]);

        if (!class_exists(ZipArchive::class)) {
            return response()->json(['status' => 'error', 'message' => 'Ekstensi PHP ZipArchive belum aktif di server.'], 500);
        }

        $zipFile = $request->file('zip');
        $zip = new ZipArchive();
        if ($zip->open($zipFile->getRealPath()) !== true) {
            return response()->json(['status' => 'error', 'message' => 'File ZIP tidak dapat dibuka atau rusak.'], 422);
        }

        $rombel = trim($request->input('rombel', ''));
        // Jika parameter rombel tidak dikirim, coba tebak dari nama file ZIP (contoh: "X KHT 1.zip")
        if (!$rombel) {
            $zipBaseName = pathinfo($zipFile->getClientOriginalName(), PATHINFO_FILENAME);
            $foundRombel = DB::table('peserta_didik')
                ->where('nama_rombel', $zipBaseName)
                ->value('nama_rombel');
            if ($foundRombel) {
                $rombel = $foundRombel;
            }
        }

        $rombelStudents = collect();
        if ($rombel) {
            $rombelStudents = DB::table('peserta_didik')
                ->where('nama_rombel', $rombel)
                ->select('peserta_didik_id', 'nama', 'nisn', 'nipd', 'nik', 'nama_rombel')
                ->orderBy('nama', 'asc')
                ->get();
        }

        $tempDir = storage_path('app/temp/foto_zip_' . uniqid('', true));
        File::makeDirectory($tempDir, 0755, true, true);

        $successCount = 0;
        $failedCount = 0;
        $results = [];

        $allowedExts = ['png', 'jpg', 'jpeg'];

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                $basename = basename(str_replace('\\', '/', $entry));

                // Abaikan direktori, berkas kosong, berkas tersembunyi, atau file sampah Mac
                if ($basename === '' || str_ends_with($entry, '/') || str_starts_with($basename, '._') || str_contains($entry, '__MACOSX')) {
                    continue;
                }

                $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExts, true)) {
                    continue;
                }

                $rawName = pathinfo($basename, PATHINFO_FILENAME);
                $cleanName = preg_replace('/[^0-9a-zA-Z]/', '', $rawName);
                $numbersOnly = preg_replace('/[^0-9]/', '', $rawName);

                $student = null;
                $matchedVia = '';

                // A. Pencocokan jika ada rombel (Nomor Urut Absen / NISN / NIPD / Nama)
                if ($rombelStudents->isNotEmpty()) {
                    // 1. Cocokkan nomor urut absen (contoh: "1", "01", "29", "1_nama", "01-nama", dsb.)
                    if (preg_match('/^0*(\d{1,2})(?:[_\s\.\-].*)?$/', $rawName, $m)) {
                        $noUrut = (int) $m[1];
                        if ($noUrut >= 1 && $noUrut <= $rombelStudents->count()) {
                            $student = $rombelStudents->get($noUrut - 1);
                            $matchedVia = "No. Urut {$noUrut}";
                        }
                    }

                    // 2. Cocokkan NISN / NIPD / NIK peserta didik di rombel ini
                    if (!$student) {
                        foreach ($rombelStudents as $s) {
                            $sNisn = (string) ($s->nisn ?? '');
                            $sNipd = (string) ($s->nipd ?? '');
                            $sNik  = (string) ($s->nik ?? '');

                            if ($sNisn !== '' && ($rawName === $sNisn || $cleanName === $sNisn || (strlen($numbersOnly) >= 5 && $numbersOnly === $sNisn))) {
                                $student = $s;
                                $matchedVia = "NISN";
                                break;
                            }
                            if ($sNipd !== '' && ($rawName === $sNipd || $cleanName === $sNipd || (strlen($numbersOnly) >= 5 && $numbersOnly === $sNipd))) {
                                $student = $s;
                                $matchedVia = "NIPD";
                                break;
                            }
                            if ($sNik !== '' && ($rawName === $sNik || $cleanName === $sNik)) {
                                $student = $s;
                                $matchedVia = "NIK";
                                break;
                            }
                        }
                    }

                    // 3. Cocokkan Nama Lengkap di rombel ini
                    if (!$student) {
                        $cleanRaw = strtolower(preg_replace('/[^a-z0-9]/', '', $rawName));
                        if (strlen($cleanRaw) >= 4) {
                            foreach ($rombelStudents as $s) {
                                $cleanNama = strtolower(preg_replace('/[^a-z0-9]/', '', $s->nama ?? ''));
                                if (strlen($cleanNama) >= 4 && (str_contains($cleanRaw, $cleanNama) || str_contains($cleanNama, $cleanRaw))) {
                                    $student = $s;
                                    $matchedVia = "Nama";
                                    break;
                                }
                            }
                        }
                    }
                }

                // B. Fallback pencarian global di seluruh tabel peserta_didik
                if (!$student) {
                    $found = DB::table('peserta_didik')
                        ->where(function ($q) use ($rawName, $cleanName, $numbersOnly) {
                            $q->where('nisn', $rawName)
                              ->orWhere('nisn', $cleanName)
                              ->orWhere('nipd', $rawName)
                              ->orWhere('nipd', $cleanName)
                              ->orWhere('peserta_didik_id', $rawName)
                              ->orWhere('peserta_didik_id', $cleanName);
                            if (strlen($numbersOnly) >= 5) {
                                $q->orWhere('nisn', $numbersOnly)
                                  ->orWhere('nipd', $numbersOnly)
                                  ->orWhere('peserta_didik_id', $numbersOnly);
                            }
                        })
                        ->first();

                    if ($found) {
                        $student = $found;
                        $matchedVia = "NISN/NIPD Global";
                    }
                }

                if (!$student) {
                    $failedCount++;
                    $results[] = [
                        'filename' => $basename,
                        'status' => 'error',
                        'message' => "Tidak ditemukan peserta didik yang cocok untuk berkas \"{$basename}\"." . ($rombel ? " (Rombel: {$rombel})" : "")
                    ];
                    continue;
                }

                $stream = $zip->getStream($entry);
                if (!$stream) {
                    $failedCount++;
                    $results[] = ['filename' => $basename, 'status' => 'error', 'message' => 'Berkas gambar di dalam ZIP tidak dapat dibaca.'];
                    continue;
                }

                $tmpExt = in_array($ext, ['jpg', 'jpeg'], true) ? 'jpg' : 'png';
                $tmpPath = $tempDir . DIRECTORY_SEPARATOR . uniqid('foto_', true) . '.' . $tmpExt;
                file_put_contents($tmpPath, stream_get_contents($stream));
                fclose($stream);

                try {
                    // Konversi JPG/JPEG ke PNG jika perlu
                    if (in_array($ext, ['jpg', 'jpeg'], true)) {
                        $srcImg = @imagecreatefromjpeg($tmpPath);
                        if (!$srcImg) {
                            throw new \RuntimeException("Berkas gambar JPG tidak valid atau rusak.");
                        }
                        $pngPath = $tempDir . DIRECTORY_SEPARATOR . uniqid('foto_', true) . '.png';
                        imagepng($srcImg, $pngPath);
                        imagedestroy($srcImg);
                        @unlink($tmpPath);
                        $tmpPath = $pngPath;
                        $basename = pathinfo($basename, PATHINFO_FILENAME) . '.png';
                    }
                    $uploaded = new UploadedFile($tmpPath, $basename, 'image/png', null, true);
                    $meta = \App\Models\PesertaDidikMeta::firstOrNew(['peserta_didik_id' => $student->peserta_didik_id]);
                    if ($meta->foto_path) {
                        $optimizer->deleteFile($meta->foto_path);
                    }

                    $prefix = 'foto_' . ($student->nisn ?: preg_replace('/[^a-zA-Z0-9_-]/', '', $student->peserta_didik_id));
                    $optResult = $optimizer->optimizeAndSavePng(
                        $uploaded,
                        \App\Services\ImageOptimizerService::ASSET_DIR_FOTO_PESERTA_DIDIK,
                        $prefix,
                        1000
                    );

                    $meta->nisn = $student->nisn;
                    $meta->foto_path = $optResult['path'];
                    $meta->foto_size = $optResult['size'];
                    $meta->foto_width = $optResult['width'];
                    $meta->foto_height = $optResult['height'];
                    $meta->save();

                    // Sinkronkan ke tabel pengguna
                    DB::table('pengguna')->where('peserta_didik_id', $student->peserta_didik_id)->update(['foto_path' => $optResult['path']]);

                    $successCount++;
                    $results[] = [
                        'peserta_didik_id' => $student->peserta_didik_id,
                        'nama' => $student->nama,
                        'nisn' => $student->nisn,
                        'filename' => $basename,
                        'matched_by' => $matchedVia,
                        'status' => 'success',
                        'foto_url' => $meta->foto_url,
                        'foto_size' => $meta->formatted_foto_size,
                        'savings' => $optResult['savings_percent'],
                    ];
                } catch (\Throwable $e) {
                    $failedCount++;
                    $results[] = ['filename' => $basename, 'status' => 'error', 'message' => $e->getMessage()];
                }
            }
        } finally {
            $zip->close();
            File::deleteDirectory($tempDir);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Proses ZIP selesai: {$successCount} foto berhasil disimpan, {$failedCount} gagal.",
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'data' => $results,
        ]);
    }

    /**
     * Reset password akun peserta didik ke default (NISN)
     */
    public function resetPassword(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        // Role berizin update pada menu_peserta_didik_aktif, menu_wali_kelas_aktif, atau Wali Kelas / Koordinator Kelas
        $canAccessUpdate = \App\Models\RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'update')
            || \App\Models\RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'update')
            || \App\Models\RolePermission::isWaliKelasOrAdmin($user);

        if (!$canAccessUpdate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk mereset password akun peserta didik.',
            ], 403);
        }

        $pdId = $request->input('peserta_didik_id');
        if (!$pdId) {
            return response()->json(['status' => 'error', 'message' => 'ID Peserta Didik wajib disertakan.'], 400);
        }

        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
        if (!$pd) {
            return response()->json(['status' => 'error', 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        $defaultPassword = trim((string) ($pd->nisn ?: ($pd->nipd ?: ($pd->nik ?: 'Sae12345!'))));
        $username = trim((string) ($pd->nisn ?: ($pd->nipd ?: ($pd->nik ?: $pd->peserta_didik_id))));

        // Cari akun pengguna di tabel pengguna
        $account = \App\Models\User::where('peserta_didik_id', $pdId)->first();
        if (!$account && !empty($pd->nisn)) {
            $account = \App\Models\User::where('username', $pd->nisn)->first();
        }

        if ($account) {
            $account->password = \Illuminate\Support\Facades\Hash::make($defaultPassword);
            $account->password_updated_at = null;
            if (!empty($account->raw_data)) {
                $raw = json_decode($account->raw_data, true) ?: [];
                unset($raw['password_updated_at'], $raw['is_password_updated']);
                $account->raw_data = json_encode($raw, JSON_UNESCAPED_UNICODE);
            }
            $account->save();
        } else {
            // Buat akun baru jika belum pernah terdaftar
            \App\Models\User::create([
                'pengguna_id'         => (string) \Illuminate\Support\Str::uuid(),
                'sekolah_id'          => $pd->sekolah_id ?? null,
                'username'            => $username,
                'nama'                => $pd->nama,
                'peran_id_str'        => 'Peserta Didik',
                'password'            => \Illuminate\Support\Facades\Hash::make($defaultPassword),
                'peserta_didik_id'    => $pd->peserta_didik_id,
                'password_updated_at' => null,
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Password untuk {$pd->nama} berhasil di-reset ke default ({$defaultPassword}). Peserta didik dapat login menggunakan NISN dan akan diminta membuat password baru saat aktivasi.",
            'nisn'    => $defaultPassword,
            'nama'    => $pd->nama,
        ]);
    }

    /**
     * Tunjuk atau cabut wewenang peserta didik sebagai Koordinator Kelas
     */
    public function toggleKoordinator(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $canAccessUpdate = \App\Models\RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'update')
            || \App\Models\RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'update')
            || \App\Models\RolePermission::isWaliKelasOrAdmin($user);

        if (!$canAccessUpdate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk menunjuk Koordinator Kelas.',
            ], 403);
        }

        $pdId = $request->input('peserta_didik_id');
        if (!$pdId) {
            return response()->json(['status' => 'error', 'message' => 'ID Peserta Didik wajib disertakan.'], 400);
        }

        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
        if (!$pd) {
            return response()->json(['status' => 'error', 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        $meta = \App\Models\PesertaDidikMeta::firstOrNew(['peserta_didik_id' => $pdId]);
        if (!$meta->exists) {
            $meta->nisn = $pd->nisn;
        }

        $newStatus = !$meta->is_koordinator;
        $meta->is_koordinator = $newStatus;
        $meta->jabatan_koordinator = $newStatus ? ($request->input('jabatan') ?: 'Koordinator Kelas') : null;
        $meta->koordinator_tmt = $newStatus ? now() : null;
        $meta->save();

        $rombelNama = $pd->nama_rombel ?: 'Kelas';
        $actionStr = $newStatus ? "ditunjuk sebagai Koordinator Kelas ({$rombelNama})" : "status Koordinator Kelas dicabut";

        return response()->json([
            'status'         => 'success',
            'is_koordinator' => $newStatus,
            'jabatan'        => $meta->jabatan_koordinator,
            'message'        => "Peserta Didik {$pd->nama} berhasil {$actionStr}. " . ($newStatus ? "Peserta didik kini memiliki hak akses membantu tugas Wali Kelas (presensi kelas)." : ""),
            'nama'           => $pd->nama,
        ]);
    }

    /**
     * Cetak Data Peserta Didik per Rombongan Belajar (Kelas)
     */
    public function printRombel(Request $request, string $rombelId)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role === 'peserta_didik' || !\App\Models\RolePermission::canAccess($user, 'menu_peserta_didik_aktif')) {
            abort(403, 'Akses ke menu Peserta Didik Aktif dinonaktifkan.');
        }

        $rombelName = trim(urldecode($rombelId));

        // Cari rombel di rombongan_belajar
        $rombel = null;
        if (Schema::hasTable('rombongan_belajar')) {
            $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $rombelName)->first();
            if (!$rombel) {
                $rombel = DB::table('rombongan_belajar')->where('nama', $rombelName)->first();
            }
        }

        // Query peserta didik
        $studentsQuery = DB::table('peserta_didik')
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
                'alamat_jalan',
                'nomor_telepon_seluler as no_hp'
            );

        if ($rombel) {
            $studentsQuery->where(function ($w) use ($rombel) {
                if (!empty($rombel->rombongan_belajar_id)) {
                    $w->where('rombongan_belajar_id', $rombel->rombongan_belajar_id);
                }
                $w->orWhere('nama_rombel', $rombel->nama);
            });
        } else {
            $studentsQuery->where('nama_rombel', $rombelName);
            $rombel = (object)[
                'nama' => $rombelName,
                'rombongan_belajar_id' => null,
                'tingkat_pendidikan_id_str' => null,
                'jurusan_id_str' => null,
                'ptk_id_str' => null,
                'jurusan_id' => null,
            ];
        }

        $students = $studentsQuery->orderBy('nama', 'asc')->get();

        if ($students->isEmpty() && empty($rombel->nama)) {
            abort(404, 'Data rombongan belajar atau peserta didik tidak ditemukan.');
        }

        // Ambil info sekolah & kop
        $sekolah = Schema::hasTable('sekolah') ? DB::table('sekolah')->first() : null;
        $sekolahMeta = Schema::hasTable('sekolah_meta') ? \App\Models\SekolahMeta::first() : null;

        // Ambil info jurusan
        $jurusanMeta = null;
        if (!empty($rombel->jurusan_id) && Schema::hasTable('jurusan_meta')) {
            $jurusanMeta = \App\Models\JurusanMeta::where('jurusan_id', $rombel->jurusan_id)->first();
            if (!$jurusanMeta) {
                $jurusanMeta = \App\Models\JurusanMeta::where('jurusan_id', 'LIKE', substr($rombel->jurusan_id, 0, 5) . '%')->first();
            }
        }
        $jurusanNama = $jurusanMeta?->nama_jurusan ?: ($rombel->jurusan_id_str ?? 'Reguler');

        // Ambil info wali kelas
        $waliNama = $rombel->ptk_id_str ?? null;
        $waliNip = null;
        if (empty($waliNama) && Schema::hasTable('pengguna_tugas_tambahan')) {
            $waliRecord = DB::table('pengguna_tugas_tambahan')
                ->where('jabatan', 'wali_kelas')
                ->where(function ($w) use ($rombel) {
                    if (!empty($rombel->rombongan_belajar_id)) {
                        $w->where('rombel_id', $rombel->rombongan_belajar_id);
                    }
                    $w->orWhere('rombel_nama', $rombel->nama);
                })
                ->first();
            if ($waliRecord) {
                $waliNama = $waliRecord->nama_pegawai ?? ($waliRecord->ptk_id_str ?? null);
            }
        }
        if (!empty($waliNama) && Schema::hasTable('gtk')) {
            $waliGtk = DB::table('gtk')->where('nama', $waliNama)->first();
            $waliNip = $waliGtk?->nip ?: null;
        }

        // Ambil kepala sekolah
        $kepalaSekolah = Schema::hasTable('gtk')
            ? DB::table('gtk')->where(function ($w) {
                $w->where('jenis_ptk_id_str', 'LIKE', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'LIKE', '%Kepala Sekolah%');
            })->first()
            : null;

        $semesterLabel = \App\Support\SemesterHelper::getActiveSemesterLabel();
        $orientasi = $request->get('orientasi', 'landscape');
        if (!in_array($orientasi, ['portrait', 'landscape'], true)) {
            $orientasi = 'landscape';
        }

        $totalSiswa = $students->count();
        $totalL = $students->where('jenis_kelamin', 'L')->count();
        $totalP = $students->where('jenis_kelamin', 'P')->count();
        $generatedAt = now()->translatedFormat('d F Y, H:i') . ' WIB';
        $titimangsa = ($sekolah?->kabupaten_kota ? str_replace(['Kabupaten ', 'Kota '], '', $sekolah->kabupaten_kota) : 'Cianjur') . ', ' . now()->translatedFormat('d F Y');

        return view('dashboard.peserta-didik-aktif-cetak', compact(
            'rombel',
            'students',
            'sekolah',
            'sekolahMeta',
            'jurusanNama',
            'waliNama',
            'waliNip',
            'kepalaSekolah',
            'semesterLabel',
            'orientasi',
            'totalSiswa',
            'totalL',
            'totalP',
            'generatedAt',
            'titimangsa'
        ));
    }

    /**
     * Unduh berkas template Microsoft Excel (.xlsx) untuk import peserta didik aktif sementara
     */
    public function downloadTemplate(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $canRead = RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'read')
            || RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'read');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!$canRead && $role !== 'admin') {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang mengunduh berkas template.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'tpl_pd_') . '.xlsx';
        $meta = [
            'title' => 'TEMPLATE IMPORT PESERTA DIDIK AKTIF (SEMENTARA)',
            'subtitle' => 'Sistem Aplikasi Edukasi (SAE) — Pangkalan Data & Verifikasi Kartu Pelajar Digital',
            'fields' => [
                'Petunjuk' => 'Kolom tanda (*) wajib diisi. NISN 10 digit. L/P: L untuk Laki-laki, P untuk Perempuan. Tanggal: YYYY-MM-DD.',
            ],
        ];

        $headers = [
            'NISN',
            'Nama Lengkap',
            'Jenis Kelamin',
            'Rombel Kelas',
            'NIPD',
            'Tempat Lahir',
            'Tanggal Lahir',
            'NIK',
        ];

        $sampleRows = [
            ['0081234561', 'Ahmad Rizki Pratama', 'L', 'X PPLG 1', '2425001', 'Cianjur', '2008-05-17', '3203010101080001'],
            ['0081234562', 'Siti Nur Aisyah', 'P', 'X PPLG 1', '2425002', 'Bandung', '2008-08-20', '3203014101080002'],
            ['0081234563', 'Budi Santoso', 'L', 'XI TJKT 2', '2324015', 'Jakarta', '2007-11-12', '3203010101070003'],
        ];

        SimpleXlsxExporter::export($tempPath, [], $headers, $sampleRows, [
            'sheetName' => 'Peserta Didik Aktif',
            'rawTable' => true,
            'headerBg' => '#4338CA',
        ]);

        return response()->download($tempPath, 'Template_Import_Peserta_Didik_Aktif.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Import berkas Excel / CSV untuk penambahan peserta didik aktif sementara
     * Data utama yang wajib: NISN, Nama Lengkap, Jenis Kelamin (L/P), Rombel Kelas.
     * Cukup sesuai data yang ditampilkan pada halaman validasi / cek kartu pelajar.
     */
    public function importExcel(Request $request): JsonResponse
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $canCreate = RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'create')
            || RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'create');
        $isAdmin = ($role === 'admin');

        if (!$canCreate && !$isAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk menambah / mengimpor data peserta didik.'
            ], 403);
        }

        $request->validate([
            'file_excel' => 'required|file|max:20480',
        ], [
            'file_excel.required' => 'Berkas Excel wajib dipilih.',
            'file_excel.max' => 'Ukuran berkas maksimal adalah 20 MB.',
        ]);

        $file = $request->file('file_excel');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Format berkas tidak didukung. Unggah berkas Excel (.xlsx, .xls) atau CSV (.csv).'
            ], 422);
        }

        $path = $file->getRealPath();

        try {
            $rows = SimpleXlsxReader::readFile($path);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membaca berkas: ' . $e->getMessage()
            ], 422);
        }

        if (empty($rows)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Berkas Excel kosong atau baris data tidak terdeteksi.'
            ], 422);
        }

        if (!Schema::hasTable('peserta_didik')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tabel peserta didik belum tersedia di database.'
            ], 500);
        }

        $sekolah = Schema::hasTable('sekolah') ? DB::table('sekolah')->first() : null;
        $sekolahId = $sekolah?->sekolah_id ?? null;
        $activeSemesterId = SemesterHelper::getActiveSemesterId();

        $rombelMap = collect();
        if (Schema::hasTable('rombongan_belajar')) {
            $rombelMap = DB::table('rombongan_belajar')->get()->keyBy(function ($r) {
                return strtolower(trim($r->nama));
            });
        }

        // Helper ekstraksi kolom cerdas & kebal variasi penamaan header / posisi kolom
        $extractCol = function (array $row, array $keywords, ?int $fallbackIndex = null): string {
            // 1. Cek exact match
            foreach ($keywords as $kw) {
                if (isset($row[$kw]) && trim((string)$row[$kw]) !== '') {
                    return trim((string)$row[$kw]);
                }
            }
            // 2. Cek loose match (mengabaikan simbol, kurung, bintang, spasi)
            foreach ($row as $k => $v) {
                $cleanK = preg_replace('/[^a-z0-9]/', '', strtolower((string)$k));
                foreach ($keywords as $kw) {
                    $cleanKw = preg_replace('/[^a-z0-9]/', '', strtolower($kw));
                    if ($cleanK === $cleanKw && trim((string)$v) !== '') {
                        return trim((string)$v);
                    }
                }
            }
            // 3. Cek starts with keyword
            foreach ($row as $k => $v) {
                $cleanK = preg_replace('/[^a-z0-9]/', '', strtolower((string)$k));
                foreach ($keywords as $kw) {
                    $cleanKw = preg_replace('/[^a-z0-9]/', '', strtolower($kw));
                    if (str_starts_with($cleanK, $cleanKw) && trim((string)$v) !== '') {
                        return trim((string)$v);
                    }
                }
            }
            // 4. Fallback ke indeks posisi kolom jika ada
            if ($fallbackIndex !== null) {
                $vals = array_values($row);
                if (isset($vals[$fallbackIndex]) && trim((string)$vals[$fallbackIndex]) !== '') {
                    return trim((string)$vals[$fallbackIndex]);
                }
            }
            return '';
        };

        $importedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($rows as $idx => $row) {
            $rowNum = $idx + 2;

            // 1. Ekstrak NISN
            $nisnRaw = $extractCol($row, ['nisn', 'nomor_induk_siswa_nasional', 'no_nisn', 'nomor_nisn', 'nisn_*', 'nisn___'], 0);
            $nisn = preg_replace('/[^0-9]/', '', $nisnRaw);

            // 2. Ekstrak Nama Lengkap
            $nama = $extractCol($row, ['nama_lengkap', 'nama', 'nama_siswa', 'nama_peserta_didik', 'nama_pd', 'nama_lengkap_*', 'nama_lengkap___'], 1);

            // 3. Ekstrak Jenis Kelamin
            $jkRaw = strtoupper($extractCol($row, ['jenis_kelamin', 'l_p', 'jk', 'gender', 'kelamin', 'sex', 'l_p_*', 'l_p___'], 2));
            $jk = str_starts_with($jkRaw, 'P') ? 'P' : 'L';

            // 4. Ekstrak Rombel
            $rombel = $extractCol($row, ['rombel_kelas', 'nama_rombel', 'rombel', 'kelas', 'rombongan_belajar', 'rombel_kelas_*', 'rombel_kelas___'], 3);

            // 5. Ekstrak NIPD
            $nipd = $extractCol($row, ['nipd', 'no_induk', 'nis', 'nomor_induk', 'nipd_*'], 4);

            // 6. Ekstrak Tempat Lahir
            $tempatLahir = $extractCol($row, ['tempat_lahir', 'tmp_lahir', 'tempat'], 5);

            // 7. Ekstrak Tanggal Lahir
            $tglLahirRaw = $extractCol($row, ['tanggal_lahir', 'tgl_lahir', 'tgl'], 6);
            $tglLahir = $this->parseTanggal($tglLahirRaw);

            // 8. Ekstrak NIK
            $nikRaw = $extractCol($row, ['nik', 'no_nik', 'nik_ktp'], 7);
            $nik = preg_replace('/[^0-9]/', '', $nikRaw);

            if ($nama === '' && $nisn === '') {
                continue;
            }

            if ($nisn === '') {
                $skippedCount++;
                $errors[] = "Baris {$rowNum}: NISN tidak boleh kosong (Nama: " . ($nama ?: 'Tanpa Nama') . ").";
                continue;
            }

            if ($nama === '') {
                $skippedCount++;
                $errors[] = "Baris {$rowNum}: Nama lengkap tidak boleh kosong (NISN: {$nisn}).";
                continue;
            }

            if ($rombel === '') {
                $skippedCount++;
                $errors[] = "Baris {$rowNum}: Rombel/Kelas tidak boleh kosong ({$nama} - NISN: {$nisn}).";
                continue;
            }

            // Hubungkan ke rombongan_belajar
            $rombelKey = strtolower($rombel);
            $rombelRecord = $rombelMap[$rombelKey] ?? null;
            $rombelId = $rombelRecord?->rombongan_belajar_id ?? null;
            $tingkat = $rombelRecord?->tingkat_pendidikan_id ?? null;
            $kurikulum = $rombelRecord?->kurikulum_id_str ?? 'Kurikulum Merdeka';

            if (!$tingkat) {
                if (preg_match('/^(?:kelas\s*)?(?:12|xii)\b/i', $rombel)) {
                    $tingkat = '12';
                } elseif (preg_match('/^(?:kelas\s*)?(?:11|xi)\b/i', $rombel)) {
                    $tingkat = '11';
                } elseif (preg_match('/^(?:kelas\s*)?(?:10|x)\b/i', $rombel)) {
                    $tingkat = '10';
                } else {
                    $tingkat = '10';
                }
            }

            try {
                $existing = DB::table('peserta_didik')->where('nisn', $nisn)->first();

                if ($existing) {
                    $pdId = $existing->peserta_didik_id;
                    $updatePayload = [
                        'nama' => $nama,
                        'nama_rombel' => $rombel,
                        'updated_at' => now(),
                    ];
                    if ($jk) $updatePayload['jenis_kelamin'] = $jk;
                    if ($nipd !== '') $updatePayload['nipd'] = $nipd;
                    if ($tempatLahir !== '') $updatePayload['tempat_lahir'] = $tempatLahir;
                    if ($tglLahir) $updatePayload['tanggal_lahir'] = $tglLahir;
                    if ($nik !== '') $updatePayload['nik'] = $nik;
                    if ($rombelId) $updatePayload['rombongan_belajar_id'] = $rombelId;
                    if ($tingkat) $updatePayload['tingkat_pendidikan_id'] = $tingkat;
                    if ($kurikulum && empty($existing->kurikulum_id_str)) $updatePayload['kurikulum_id_str'] = $kurikulum;

                    DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->update($updatePayload);
                    $updatedCount++;
                } else {
                    $pdId = (string) Str::uuid();
                    $insertPayload = [
                        'peserta_didik_id' => $pdId,
                        'registrasi_id' => (string) Str::uuid(),
                        'nama' => $nama,
                        'nisn' => $nisn,
                        'nipd' => $nipd ?: null,
                        'nik' => $nik ?: null,
                        'jenis_kelamin' => $jk,
                        'tempat_lahir' => $tempatLahir ?: null,
                        'tanggal_lahir' => $tglLahir ?: null,
                        'nama_rombel' => $rombel,
                        'rombongan_belajar_id' => $rombelId,
                        'tingkat_pendidikan_id' => $tingkat,
                        'kurikulum_id_str' => $kurikulum,
                        'jenis_pendaftaran_id_str' => 'Siswa Baru',
                        'tanggal_masuk_sekolah' => now()->format('Y-m-d'),
                        'semester_id' => $activeSemesterId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    DB::table('peserta_didik')->insert($insertPayload);
                    $importedCount++;
                }

                // Metadata foto kartu
                if (Schema::hasTable('peserta_didik_meta')) {
                    try {
                        PesertaDidikMeta::firstOrCreate(
                            ['peserta_didik_id' => $pdId],
                            ['nisn' => $nisn]
                        );
                    } catch (\Throwable $metaErr) {}
                }

                // Akun login
                if (Schema::hasTable('pengguna')) {
                    try {
                        $hasUser = DB::table('pengguna')
                            ->where('peserta_didik_id', $pdId)
                            ->orWhere('username', $nisn)
                            ->exists();

                        if (!$hasUser) {
                            User::create([
                                'pengguna_id' => (string) Str::uuid(),
                                'sekolah_id' => $sekolahId,
                                'username' => $nisn,
                                'nama' => $nama,
                                'peran_id_str' => 'Peserta Didik',
                                'password' => Hash::make($nisn),
                                'peserta_didik_id' => $pdId,
                                'password_updated_at' => null,
                            ]);
                        }
                    } catch (\Throwable $userErr) {}
                }

                // Bersihkan dari tabel tidak aktif
                if (Schema::hasTable('peserta_didik_tidak_aktif')) {
                    try {
                        DB::table('peserta_didik_tidak_aktif')
                            ->where('peserta_didik_id', $pdId)
                            ->orWhere('nisn', $nisn)
                            ->delete();
                    } catch (\Throwable $taErr) {}
                }
            } catch (\Throwable $err) {
                $skippedCount++;
                $errors[] = "Baris {$rowNum} ({$nama}): " . $err->getMessage();
            }
        }

        $totalProcessed = $importedCount + $updatedCount;

        if ($totalProcessed === 0) {
            $errDetail = !empty($errors) ? implode(' ', array_slice($errors, 0, 3)) : 'Pastikan berkas Excel memiliki baris data dengan kolom NISN, Nama Lengkap, dan Rombel yang terisi.';
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada data peserta didik yang berhasil diimpor. ' . $errDetail,
                'imported_count' => 0,
                'updated_count' => 0,
                'skipped_count' => $skippedCount,
                'errors' => array_slice($errors, 0, 10),
            ], 422);
        }

        $message = "Import berhasil diproses: {$importedCount} peserta didik baru ditambahkan, {$updatedCount} data diperbarui.";
        if ($skippedCount > 0) {
            $message .= " ({$skippedCount} baris dilewati).";
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'imported_count' => $importedCount,
            'updated_count' => $updatedCount,
            'skipped_count' => $skippedCount,
            'total_processed' => $totalProcessed,
            'errors' => array_slice($errors, 0, 10),
        ]);
    }

    /**
     * Parsing tanggal dari Excel (serial number, format Y-m-d, D-M-Y, atau text)
     */
    private function parseTanggal(mixed $value): ?string
    {
        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }

        if (is_numeric($s) && (float) $s > 20000 && (float) $s < 80000) {
            return gmdate('Y-m-d', (int) (((float) $s - 25569) * 86400));
        }

        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $s)) {
            $parts = explode('-', $s);
            return sprintf('%04d-%02d-%02d', $parts[0], $parts[1], $parts[2]);
        }

        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $s, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        $time = strtotime($s);
        if ($time !== false && $time > 0) {
            $year = (int) date('Y', $time);
            if ($year >= 1950 && $year <= 2100) {
                return date('Y-m-d', $time);
            }
        }

        return null;
    }
}
