<?php

namespace App\Services;

use App\Models\JurusanMeta;
use App\Models\PesertaDidikMeta;
use App\Models\RolePermission;
use App\Models\SekolahMeta;
use App\Models\User;
use App\Support\SemesterHelper;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


use App\Support\Table;

class PesertaDidikQueryService
{
    public const SORTABLE = [
        'nama',
        'nisn',
        'nipd',
        'jenis_kelamin',
        'nama_rombel',
        'tingkat_pendidikan_id',
        'sekolah_asal',
    ];

    /**
     * Kompilasi data untuk halaman index Peserta Didik Aktif.
     */
    public function getIndexData(Request $request, mixed $user): array
    {
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canCreate = RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'create') || RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'create');
        $canRead   = RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'read') || RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'read');
        $canUpdate = RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'update') || RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'update');
        $canDelete = RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'delete') || RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'delete');

        $isAdmin = ($role === 'admin');
        $isWaliOrAdmin = RolePermission::isWaliKelasOrAdmin($user);
        $canManageStudentPhotos = $canUpdate || $canCreate || $isAdmin || $isWaliOrAdmin;
        $waliRombel = RolePermission::getWaliKelasRombel($user);

        $q       = trim($request->get('q', ''));
        $rombel  = trim($request->get('rombel', ''));
        $gender  = trim($request->get('gender', ''));
        $perPage = (int) ($request->get('perPage') ?? $request->get('per_page') ?? $request->get('perPageSelect') ?? 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }
        $sort    = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'nama';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        if (!Table::exists('peserta_didik')) {
            return [
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
                'canManageStudentPhotos' => $canManageStudentPhotos,
                'waliRombel' => $waliRombel,
                'canCreate' => $canCreate,
                'canRead' => $canRead,
                'canUpdate' => $canUpdate,
                'canDelete' => $canDelete,
                'isWaliOrAdmin' => $isWaliOrAdmin,
                'isAdmin' => $isAdmin,
            ];
        }

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

        $pdIds = $itemsForPage->pluck('peserta_didik_id')->filter()->values()->all();
        $metaMap = collect();
        if (!empty($pdIds) && Table::exists('peserta_didik_meta')) {
            $metaMap = PesertaDidikMeta::whereIn('peserta_didik_id', $pdIds)
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

        $list = new LengthAwarePaginator(
            $itemsForPage,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return compact(
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
        );
    }

    /**
     * Dapatkan detail peserta didik via JSON untuk modal.
     */
    public function getShowData(string|int $id): ?array
    {
        $pesertaDidik = DB::table('peserta_didik')
            ->where('peserta_didik_id', $id)
            ->orWhere('nisn', $id)
            ->orWhere('nipd', $id)
            ->first();

        if (!$pesertaDidik) {
            return null;
        }

        $anggota = null;
        if (Table::exists('anggota_rombel')) {
            $anggota = DB::table('anggota_rombel')
                ->where('peserta_didik_id', $pesertaDidik->peserta_didik_id)
                ->first();
        }

        $meta = null;
        if (Table::exists('peserta_didik_meta')) {
            $meta = PesertaDidikMeta::where('peserta_didik_id', $pesertaDidik->peserta_didik_id)->first();
        }

        $rombelId = $pesertaDidik->rombongan_belajar_id ?? ($anggota->rombongan_belajar_id ?? null);
        $pembelajaran = collect();
        if (!empty($rombelId) && Table::exists('pembelajaran')) {
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

        return [
            'status' => 'success',
            'data' => $pesertaDidik,
            'anggota' => $anggota,
            'meta' => $meta,
            'foto_url' => $meta?->foto_url,
            'foto_size' => $meta?->formatted_foto_size,
            'pembelajaran' => $pembelajaran,
            'total_mapel' => $pembelajaran->count(),
            'total_jam' => $pembelajaran->sum(fn($p) => (int) ($p->jam_mengajar_per_minggu ?? 0)),
        ];
    }

    /**
     * Dapatkan daftar anggota rombel untuk manajemen foto batch.
     */
    public function getRombelMembers(string $rombelName): array
    {
        if (!Table::exists('peserta_didik')) {
            return ['status' => 'error', 'message' => 'Tabel peserta didik belum tersedia.', 'status_code' => 500];
        }

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

        $pdIds = $students->pluck('peserta_didik_id')->filter()->toArray();
        $metas = collect();
        if (Table::exists('peserta_didik_meta') && !empty($pdIds)) {
            $metas = PesertaDidikMeta::whereIn('peserta_didik_id', $pdIds)->get()->keyBy('peserta_didik_id');
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

        return [
            'status' => 'success',
            'rombel' => $rombelName,
            'total' => $mapped->count(),
            'total_with_foto' => $totalWithFoto,
            'total_without_foto' => $mapped->count() - $totalWithFoto,
            'data' => $mapped,
        ];
    }

    /**
     * Reset password akun peserta didik ke default (NISN).
     */
    public function resetPassword(string $pdId): array
    {
        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
        if (!$pd) {
            return ['status' => 'error', 'message' => 'Data peserta didik tidak ditemukan.', 'status_code' => 404];
        }

        $defaultPassword = trim((string) ($pd->nisn ?: ($pd->nipd ?: ($pd->nik ?: 'Sae12345!'))));
        $username = trim((string) ($pd->nisn ?: ($pd->nipd ?: ($pd->nik ?: $pd->peserta_didik_id))));

        $account = User::where('peserta_didik_id', $pdId)->first();
        if (!$account && !empty($pd->nisn)) {
            $account = User::where('username', $pd->nisn)->first();
        }

        if ($account) {
            $account->password = Hash::make($defaultPassword);
            $account->password_updated_at = null;
            if (!empty($account->raw_data)) {
                $raw = json_decode($account->raw_data, true) ?: [];
                unset($raw['password_updated_at'], $raw['is_password_updated']);
                $account->raw_data = json_encode($raw, JSON_UNESCAPED_UNICODE);
            }
            $account->save();
        } else {
            User::create([
                'pengguna_id'         => (string) Str::uuid(),
                'sekolah_id'          => $pd->sekolah_id ?? null,
                'username'            => $username,
                'nama'                => $pd->nama,
                'peran_id_str'        => 'Peserta Didik',
                'password'            => Hash::make($defaultPassword),
                'peserta_didik_id'    => $pd->peserta_didik_id,
                'password_updated_at' => null,
            ]);
        }

        return [
            'status'  => 'success',
            'message' => "Password untuk {$pd->nama} berhasil di-reset ke default ({$defaultPassword}). Peserta didik dapat login menggunakan NISN dan akan diminta membuat password baru saat aktivasi.",
            'nisn'    => $defaultPassword,
            'nama'    => $pd->nama,
        ];
    }

    /**
     * Tunjuk atau cabut wewenang peserta didik sebagai Koordinator Kelas.
     */
    public function toggleKoordinator(string $pdId, ?string $jabatan): array
    {
        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
        if (!$pd) {
            return ['status' => 'error', 'message' => 'Data peserta didik tidak ditemukan.', 'status_code' => 404];
        }

        $meta = PesertaDidikMeta::firstOrNew(['peserta_didik_id' => $pdId]);
        if (!$meta->exists) {
            $meta->nisn = $pd->nisn;
        }

        $newStatus = !$meta->is_koordinator;
        $meta->is_koordinator = $newStatus;
        $meta->jabatan_koordinator = $newStatus ? ($jabatan ?: 'Koordinator Kelas') : null;
        $meta->koordinator_tmt = $newStatus ? now() : null;
        $meta->save();

        $rombelNama = $pd->nama_rombel ?: 'Kelas';
        $actionStr = $newStatus ? "ditunjuk sebagai Koordinator Kelas ({$rombelNama})" : "status Koordinator Kelas dicabut";

        return [
            'status'         => 'success',
            'is_koordinator' => $newStatus,
            'jabatan'        => $meta->jabatan_koordinator,
            'message'        => "Peserta Didik {$pd->nama} berhasil {$actionStr}. " . ($newStatus ? "Peserta didik kini memiliki hak akses membantu tugas Wali Kelas (presensi kelas)." : ""),
            'nama'           => $pd->nama,
        ];
    }

    /**
     * Dapatkan data cetak peserta didik per rombel.
     */
    public function getPrintRombelData(string $rombelId, Request $request): array
    {
        $rombelName = trim(urldecode($rombelId));

        $rombel = null;
        if (Table::exists('rombongan_belajar')) {
            $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $rombelName)->first();
            if (!$rombel) {
                $rombel = DB::table('rombongan_belajar')->where('nama', $rombelName)->first();
            }
        }

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
            return ['error' => 'not_found'];
        }

        $sekolah = Table::exists('sekolah') ? DB::table('sekolah')->first() : null;
        $sekolahMeta = Table::exists('sekolah_meta') ? SekolahMeta::first() : null;

        $jurusanMeta = null;
        if (!empty($rombel->jurusan_id) && Table::exists('jurusan_meta')) {
            $jurusanMeta = JurusanMeta::where('jurusan_id', $rombel->jurusan_id)->first();
            if (!$jurusanMeta) {
                $jurusanMeta = JurusanMeta::where('jurusan_id', 'LIKE', substr($rombel->jurusan_id, 0, 5) . '%')->first();
            }
        }
        $jurusanNama = $jurusanMeta?->nama_jurusan ?: ($rombel->jurusan_id_str ?? 'Reguler');

        $waliNama = $rombel->ptk_id_str ?? null;
        $waliNip = null;
        if (empty($waliNama) && Table::exists('pengguna_tugas_tambahan')) {
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
        if (!empty($waliNama) && Table::exists('gtk')) {
            $waliGtk = DB::table('gtk')->where('nama', $waliNama)->first();
            $waliNip = $waliGtk?->nip ?: null;
        }

        $kepalaSekolah = Table::exists('gtk')
            ? DB::table('gtk')->where(function ($w) {
                $w->where('jenis_ptk_id_str', 'LIKE', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'LIKE', '%Kepala Sekolah%');
            })->first()
            : null;

        $semesterLabel = SemesterHelper::getActiveSemesterLabel();
        $orientasi = $request->get('orientasi', 'landscape');
        if (!in_array($orientasi, ['portrait', 'landscape'], true)) {
            $orientasi = 'landscape';
        }

        $totalSiswa = $students->count();
        $totalL = $students->where('jenis_kelamin', 'L')->count();
        $totalP = $students->where('jenis_kelamin', 'P')->count();
        $generatedAt = now()->translatedFormat('d F Y, H:i') . ' WIB';
        $titimangsa = ($sekolah?->kabupaten_kota ? str_replace(['Kabupaten ', 'Kota '], '', $sekolah->kabupaten_kota) : 'Cianjur') . ', ' . now()->translatedFormat('d F Y');

        return compact(
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
        );
    }
}
