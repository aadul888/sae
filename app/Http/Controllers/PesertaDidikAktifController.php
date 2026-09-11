<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PesertaDidikAktifController extends Controller
{
    private const SORTABLE = ['nama', 'nisn', 'nipd', 'jenis_kelamin', 'nama_rombel', 'tingkat_pendidikan_id'];

    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        // Proteksi ketat: peran peserta_didik tidak boleh mengakses modul Peserta Didik Aktif
        if ($role === 'peserta_didik' || !\App\Models\RolePermission::canAccess($user, 'menu_peserta_didik_aktif')) {
            return redirect()->route('dashboard.' . ($role ?: 'peserta_didik'))->with('error', 'Akses ke menu Peserta Didik Aktif dinonaktifkan.');
        }

        // Cek wewenang unggah foto & cetak kartu masal (hanya Admin & Wali Kelas)
        $canManageStudentPhotos = \App\Models\RolePermission::isWaliKelasOrAdmin($user);
        $waliRombel = \App\Models\RolePermission::getWaliKelasRombel($user);

        $q       = trim($request->get('q', ''));
        $rombel  = trim($request->get('rombel', ''));
        $gender  = trim($request->get('gender', ''));
        $perPage = (int) $request->get('perPage', 15);
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

        $allResults = $baseQuery->get();
        $total = $allResults->count();

        $summary = [
            'total' => $total,
            'laki' => $allResults->where('jenis_kelamin', 'L')->count(),
            'perempuan' => $allResults->where('jenis_kelamin', 'P')->count(),
            'rombel' => $allResults->pluck('nama_rombel')->filter()->unique()->count(),
        ];

        $sorted = $allResults->sortBy(function ($item) use ($sort) {
            return $item->{$sort} ?? '';
        }, SORT_REGULAR, $sortDir === 'desc');

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
            'waliRombel'
        ));
    }

    /**
     * Detail peserta didik via JSON untuk modal
     */
    public function show(Request $request, string|int $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role === 'peserta_didik' || !\App\Models\RolePermission::canAccess($user, 'menu_peserta_didik_aktif')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak oleh Administrator.'], 403);
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
        if (!\App\Models\RolePermission::isWaliKelasOrAdmin($user)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Hanya Administrator dan Wali Kelas yang memiliki izin untuk mengunggah pasfoto peserta didik.'
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
        if (!\App\Models\RolePermission::isWaliKelasOrAdmin($user)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Hanya Administrator dan Wali Kelas yang memiliki izin untuk menghapus pasfoto peserta didik.'
            ], 403);
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
        if (!\App\Models\RolePermission::isWaliKelasOrAdmin($user)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Hanya Administrator dan Wali Kelas yang diizinkan mengakses data rombel kelas.'
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
        if (!\App\Models\RolePermission::isWaliKelasOrAdmin($user)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Hanya Administrator dan Wali Kelas yang memiliki izin untuk mengunggah pasfoto masal.'
            ], 403);
        }

        // Jika upload single item dalam async loop
        if ($request->hasFile('foto') && $request->filled('peserta_didik_id')) {
            return $this->uploadFoto($request, $optimizer);
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
}
