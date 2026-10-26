<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Resolusi cakupan (scope) data Laboratorium per JURUSAN.
 *
 * Aturan bisnis:
 *  - Setiap Laboran ditugaskan pada SATU jurusan (kolom `ptk_tugas_tambahan.jurusan_id`).
 *  - Laboran hanya boleh melihat & mengubah data milik jurusannya sendiri.
 *  - Administrator (superadmin) melihat seluruh jurusan.
 *  - Kepala Laboratorium (KEPALA_LAB) boleh lintas jurusan karena bersifat koordinatif.
 *  - Bila penugasan Laboran belum memiliki `jurusan_id`, sistem TIDAK mengunci data
 *    (fallback permisif) agar operasional tidak terhenti sebelum data penugasan dilengkapi.
 *
 * Hasil resolusi di-cache per request untuk menghindari query berulang.
 */
final class LaboranScope
{
    /** @var array<string, mixed> */
    private static array $cache = [];

    /**
     * Resolusi konteks laboran untuk pengguna saat ini.
     *
     * @return array{
     *   is_admin: bool,
     *   is_laboran: bool,
     *   is_kepala_lab: bool,
     *   unrestricted: bool,
     *   jurusan_id: ?string,
     *   jurusan_nama: ?string,
     *   jurusan_ids: array<int, string>,
     *   ptk_id: ?string,
     *   user_id: ?string,
     *   nama: string
     * }
     */
    public static function resolve(mixed $user = null): array
    {
        $user = $user ?: session('user');

        $userId = is_array($user)
            ? ($user['pengguna_id'] ?? ($user['id'] ?? null))
            : ($user->pengguna_id ?? ($user->id ?? null));
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        $role  = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $nama  = is_array($user)
            ? ($user['nama'] ?? ($user['name'] ?? ''))
            : ($user->nama ?? ($user->name ?? ''));

        $cacheKey = ($userId ?: '-') . '|' . ($ptkId ?: '-') . '|' . $role;
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $isAdmin = $role === 'admin';

        $duties = collect();
        if (Table::exists('ptk_tugas_tambahan') && Table::exists('ref_tugas_tambahan')) {
            $duties = DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('ptt.is_active', true)
                ->where('rtt.is_active', true)
                ->where(function ($q) use ($userId, $ptkId) {
                    if ($userId) {
                        $q->where('ptt.user_id', $userId);
                    }
                    if ($ptkId) {
                        $q->orWhere('ptt.ptk_id', $ptkId);
                    }
                })
                ->whereIn('rtt.kode', ['LABORAN', 'KEPALA_LAB', 'KAPROG'])
                ->select('rtt.kode', 'ptt.jurusan_id', 'ptt.ptk_id', 'ptt.user_id')
                ->get();
        }

        $isLaboran   = $duties->contains('kode', 'LABORAN');
        $isKepalaLab = $duties->contains('kode', 'KEPALA_LAB');

        // Jurusan yang ditugaskan pada baris LABORAN atau KAPROG (bisa multi-jurusan dipisah koma atau JSON)
        $jurusanIds = $duties->whereIn('kode', ['LABORAN', 'KAPROG'])
            ->flatMap(function ($d) {
                if (empty($d->jurusan_id)) {
                    return [];
                }
                $raw = trim((string) $d->jurusan_id);
                if (str_starts_with($raw, '[')) {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                }
                return array_map('trim', explode(',', $raw));
            })
            ->filter(fn($v) => !empty($v))
            ->unique()
            ->values()
            ->all();

        $jurusanId = $jurusanIds[0] ?? null;

        // Superadmin & Kepala Lab tidak dibatasi jurusan
        $unrestricted = $isAdmin || $isKepalaLab;

        // Fallback permisif: penugasan laboran belum punya jurusan
        if (!$unrestricted && $isLaboran && empty($jurusanIds)) {
            $unrestricted = true;
        }

        $jurusanNama = null;
        if (!empty($jurusanIds) && Table::exists('jurusan_meta')) {
            $namaList = DB::table('jurusan_meta')
                ->whereIn('jurusan_id', $jurusanIds)
                ->pluck('nama_jurusan')
                ->filter()
                ->all();
            if (!empty($namaList)) {
                $jurusanNama = implode(', ', $namaList);
            }
        }
        if (!$jurusanNama && $jurusanId) {
            $jurusanNama = DB::table('rombongan_belajar')->where('jurusan_id', $jurusanId)->value('jurusan_id_str');
        }

        $context = [
            'is_admin'      => $isAdmin,
            'is_laboran'    => $isLaboran,
            'is_kepala_lab' => $isKepalaLab,
            'unrestricted'  => $unrestricted,
            'jurusan_id'    => $jurusanId,
            'jurusan_nama'  => $jurusanNama,
            'jurusan_ids'   => $jurusanIds,
            'ptk_id'        => $ptkId,
            'user_id'       => $userId,
            'nama'          => $nama,
        ];

        self::$cache[$cacheKey] = $context;

        return $context;
    }

    /**
     * Terapkan pembatasan jurusan pada query builder.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $column  Nama kolom jurusan pada tabel yang di-query
     * @return \Illuminate\Database\Query\Builder
     */
    public static function apply($query, string $column = 'jurusan_id', ?array $context = null)
    {
        $context = $context ?: self::resolve();

        if ($context['unrestricted']) {
            return $query;
        }

        $ids = $context['jurusan_ids'];

        if (empty($ids)) {
            // Tidak ada jurusan -> tidak ada data yang boleh diakses
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $ids);
    }

    /**
     * Apakah pengguna boleh mengakses baris dengan jurusan tertentu?
     */
    public static function allows(?string $jurusanId, ?array $context = null): bool
    {
        $context = $context ?: self::resolve();

        if ($context['unrestricted']) {
            return true;
        }

        if (empty($jurusanId)) {
            return false;
        }

        return in_array($jurusanId, $context['jurusan_ids'], true);
    }

    /**
     * Jurusan yang harus dipakai saat menyimpan data baru.
     * Laboran terkunci -> selalu jurusan miliknya. Selain itu -> ikut input.
     */
    public static function jurusanForWrite(?string $inputJurusanId, ?array $context = null): ?string
    {
        $context = $context ?: self::resolve();

        if ($context['unrestricted']) {
            return $inputJurusanId ?: $context['jurusan_id'];
        }

        if (!empty($inputJurusanId) && in_array($inputJurusanId, $context['jurusan_ids'], true)) {
            return $inputJurusanId;
        }

        return $context['jurusan_id'] ?? ($context['jurusan_ids'][0] ?? null);
    }

    /**
     * Daftar jurusan untuk dropdown (dibatasi sesuai hak akses).
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public static function jurusanOptions(?array $context = null)
    {
        $context = $context ?: self::resolve();

        if (!Table::exists('jurusan_meta')) {
            return collect();
        }

        $query = DB::table('jurusan_meta')->select('jurusan_id', 'nama_jurusan', 'singkatan')->orderBy('nama_jurusan');

        if (!$context['unrestricted']) {
            $query->whereIn('jurusan_id', $context['jurusan_ids'] ?: ['__none__']);
        }

        return $query->get();
    }

    /**
     * Ringkasan untuk ditampilkan di UI (badge cakupan data).
     */
    public static function badge(?array $context = null): array
    {
        $context = $context ?: self::resolve();

        if ($context['is_admin']) {
            return ['label' => 'Superadmin — Semua Jurusan', 'tone' => 'primary', 'icon' => 'fa-user-shield'];
        }

        if ($context['is_kepala_lab']) {
            return ['label' => 'Kepala Laboratorium — Lintas Jurusan', 'tone' => 'info', 'icon' => 'fa-sitemap'];
        }

        if ($context['unrestricted']) {
            return ['label' => 'Penugasan Jurusan Belum Ditetapkan', 'tone' => 'warning', 'icon' => 'fa-triangle-exclamation'];
        }

        return [
            'label' => 'Jurusan: ' . ($context['jurusan_nama'] ?: $context['jurusan_id']),
            'tone'  => 'success',
            'icon'  => 'fa-graduation-cap',
        ];
    }

    /**
     * Bersihkan cache (dipakai pada pengujian).
     */
    public static function flush(): void
    {
        self::$cache = [];
    }
}
