<?php

namespace App\Services;

use App\Models\PerpusAnggota;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * PerpustakaanIdentitasService
 * -------------------------------------------------------------------------
 * Sumber kebenaran tunggal untuk menjawab: "transaksi sirkulasi/kunjungan ini
 * milik anggota yang mana?"
 *
 * LATAR BELAKANG MASALAH YANG DIPERBAIKI
 * --------------------------------------
 * Sebelumnya `perpus_sirkulasi.peminjam_id` diisi campur-aduk:
 *   - `peserta_didik_id` / `ptk_id` (dari PeopleSearchController), atau
 *   - `'ID-' . substr(md5(nama), 0, 8)` bila diketik manual.
 * Sementara portal anggota mencari dengan `perpus_anggota.id`, sehingga
 * riwayat tidak pernah cocok (data hilang) DAN berisiko menampilkan riwayat
 * milik orang lain karena pemakaian `orWhere` tanpa grouping.
 *
 * SOLUSI
 * ------
 *  - `perpus_sirkulasi.anggota_id` & `perpus_kunjungan.anggota_id` menjadi
 *    kolom kanonik (foreign key logis ke `perpus_anggota.id`).
 *  - `peminjam_id` lama tetap dipertahankan sebagai jejak eksternal.
 *  - Semua query riwayat WAJIB lewat service ini agar ter-scope ke satu anggota.
 */
class PerpustakaanIdentitasService
{
    /** Kolom kanonik tersedia? (aman untuk instalasi lama) */
    public static function hasAnggotaColumn(string $table): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, 'anggota_id');
    }

    /**
     * Cari anggota berdasarkan identitas apa pun: nomor anggota, token kartu,
     * NISN/NIP, id peserta didik, atau id PTK.
     */
    public static function findAnggota(?string $identifier): ?PerpusAnggota
    {
        $identifier = trim((string) $identifier);
        if ($identifier === '') {
            return null;
        }

        return PerpusAnggota::query()
            ->where(function ($q) use ($identifier) {
                $q->where('nomor_anggota', $identifier)
                    ->orWhere('token_kartu', $identifier)
                    ->orWhere('nisn_nip', $identifier)
                    ->orWhere('siswa_id', $identifier)
                    ->orWhere('ptk_id', $identifier)
                    ->orWhere('email', $identifier);
            })
            ->first();
    }

    /**
     * Tentukan anggota dari data sesi SAE yang sedang login.
     * Dipakai untuk auto-login & auto-provision.
     */
    public static function resolveFromSaeUser($user): ?PerpusAnggota
    {
        if (!$user) {
            return null;
        }

        $get = static function (string $key) use ($user) {
            return is_array($user) ? ($user[$key] ?? null) : ($user->{$key} ?? null);
        };

        $candidates = array_values(array_filter([
            $get('nisn'),
            $get('nip'),
            $get('peserta_didik_id'),
            $get('ptk_id'),
            $get('username'),
        ], fn($v) => is_string($v) && trim($v) !== ''));

        if (empty($candidates)) {
            return null;
        }

        foreach ($candidates as $candidate) {
            if ($member = self::findAnggota($candidate)) {
                return $member;
            }
        }

        return null;
    }

    /**
     * Auto-provision anggota perpustakaan dari master data sekolah saat anggota
     * pertama kali masuk lewat gerbang. Hanya untuk peserta didik & GTK.
     *
     * @return PerpusAnggota|null null bila identitas tidak ada di master data.
     */
    public static function autoProvisionFromSaeUser($user): ?PerpusAnggota
    {
        if (!$user) {
            return null;
        }

        $get = static function (string $key) use ($user) {
            return is_array($user) ? ($user[$key] ?? null) : ($user->{$key} ?? null);
        };

        $role = (string) $get('role');
        if (!in_array($role, ['peserta_didik', 'guru', 'tendik', 'tugas_tambahan', 'pustakawan'], true)) {
            return null;
        }

        $nisn = trim((string) $get('nisn'));
        $nip = trim((string) $get('nip'));
        $pdId = trim((string) $get('peserta_didik_id'));
        $ptkId = trim((string) $get('ptk_id'));
        $nama = trim((string) ($get('nama') ?? $get('name') ?? ''));

        $isSiswa = $role === 'peserta_didik';

        // --- Peserta didik ---
        if ($isSiswa && ($nisn !== '' || $pdId !== '')) {
            if (!Schema::hasTable('peserta_didik')) {
                return null;
            }
            $pd = DB::table('peserta_didik')
                ->when($pdId !== '', fn($q) => $q->where('peserta_didik_id', $pdId))
                ->when($pdId === '' && $nisn !== '', fn($q) => $q->where('nisn', $nisn))
                ->first();

            if (!$pd) {
                return null;
            }

            $existing = PerpusAnggota::where('siswa_id', $pd->peserta_didik_id)->first()
                ?? ($nisn !== '' ? PerpusAnggota::where('nisn_nip', $nisn)->first() : null);

            if ($existing) {
                return $existing;
            }

            return PerpusAnggota::create([
                'nomor_anggota'    => PerpusAnggota::generateNomor('siswa'),
                'token_kartu'      => Str::random(32),
                'tipe'             => 'siswa',
                'siswa_id'         => $pd->peserta_didik_id,
                'nisn_nip'         => $pd->nisn ?? ($nisn ?: null),
                'nama'             => $pd->nama ?? ($nama ?: 'Peserta Didik'),
                'rombel_atau_unit' => $pd->nama_rombel ?? null,
                'jenis_kelamin'    => $pd->jenis_kelamin ?? null,
                'is_active'        => true,
                'tgl_daftar'       => now()->toDateString(),
                'auto_provisioned' => true,
            ]);
        }

        // --- GTK ---
        if (!$isSiswa && ($nip !== '' || $ptkId !== '')) {
            if (!Schema::hasTable('gtk')) {
                return null;
            }
            $gtk = null;
            if ($ptkId !== '') {
                $gtk = DB::table('gtk')->where('ptk_id', $ptkId)->first();
            }
            if (!$gtk && $nip !== '' && Schema::hasColumn('gtk', 'nip')) {
                $gtk = DB::table('gtk')->where('nip', $nip)->first();
            }
            if (!$gtk) {
                return null;
            }

            $existing = PerpusAnggota::where('ptk_id', $gtk->ptk_id)->first()
                ?? ($nip !== '' ? PerpusAnggota::where('nisn_nip', $nip)->first() : null);

            if ($existing) {
                return $existing;
            }

            return PerpusAnggota::create([
                'nomor_anggota' => PerpusAnggota::generateNomor('gtk'),
                'token_kartu'   => Str::random(32),
                'tipe'          => 'gtk',
                'ptk_id'        => $gtk->ptk_id,
                'nisn_nip'      => $gtk->nip ?? ($nip ?: null),
                'nama'          => $gtk->nama ?? ($nama ?: 'GTK'),
                'is_active'     => true,
                'tgl_daftar'    => now()->toDateString(),
                'auto_provisioned' => true,
            ]);
        }

        return null;
    }

    /**
     * Tautkan transaksi lama ke anggota kanonik.
     * Dipakai oleh perintah `perpus:tautkan-anggota` (mode dry-run / apply).
     *
     * @return array{diperiksa:int, cocok:int, gagal:int}
     */
    public static function backfill(bool $apply = false, int $limit = 0): array
    {
        $stats = ['diperiksa' => 0, 'cocok' => 0, 'gagal' => 0];

        if (!self::hasAnggotaColumn('perpus_sirkulasi')) {
            return $stats;
        }

        $query = DB::table('perpus_sirkulasi')
            ->select('id', 'peminjam_id', 'peminjam_nama', 'peminjam_tipe')
            ->whereNull('anggota_id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        foreach ($query->get() as $row) {
            $stats['diperiksa']++;

            $anggota = self::findAnggota($row->peminjam_id);

            // Fallback: cocokkan nama unik (hanya bila tepat satu kandidat).
            if (!$anggota && $row->peminjam_nama !== null && trim($row->peminjam_nama) !== '') {
                $kandidat = PerpusAnggota::where('nama', trim($row->peminjam_nama))->limit(2)->get();
                if ($kandidat->count() === 1) {
                    $anggota = $kandidat->first();
                }
            }

            if (!$anggota) {
                $stats['gagal']++;
                continue;
            }

            $stats['cocok']++;

            if ($apply) {
                DB::table('perpus_sirkulasi')->where('id', $row->id)->update([
                    'anggota_id' => $anggota->id,
                    'updated_at' => now(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Bangun query riwayat sirkulasi yang TERISOLASI untuk satu anggota.
     *
     * Semua kondisi dibungkus dalam satu closure agar tidak ada `orWhere` yang
     * meloloskan filter (bug privasi pada implementasi sebelumnya).
     */
    public static function riwayatQuery(PerpusAnggota $anggota)
    {
        $query = DB::table('perpus_sirkulasi as s')
            ->leftJoin('perpus_koleksi_buku as b', 's.buku_id', '=', 'b.id')
            ->select('s.*', 'b.judul as judul_buku', 'b.penulis as penulis_buku', 'b.cover_buku');

        if (self::hasAnggotaColumn('perpus_sirkulasi')) {
            return $query->where('s.anggota_id', $anggota->id);
        }

        // Fallback instalasi lama: cocokkan identitas secara ketat & ter-scope.
        $ids = array_values(array_filter([
            $anggota->siswa_id,
            $anggota->ptk_id,
            $anggota->nisn_nip,
            $anggota->nomor_anggota,
        ], fn($v) => is_string($v) && trim($v) !== ''));

        return $query->where(function ($q) use ($ids, $anggota) {
            if (!empty($ids)) {
                $q->whereIn('s.peminjam_id', $ids);
            }
            $q->orWhere(function ($q2) use ($anggota) {
                $q2->whereNull('s.anggota_id')
                    ->where('s.peminjam_nama', $anggota->nama)
                    ->where('s.peminjam_tipe', $anggota->tipe === 'siswa' ? 'siswa' : ($anggota->tipe === 'gtk' ? 'guru' : 'umum'));
            });
        });
    }

    /**
     * Ringkasan pinjaman aktif + tunggakan anggota.
     */
    public static function ringkasanPinjaman(PerpusAnggota $anggota): array
    {
        $base = self::riwayatQuery($anggota);

        $aktif = (clone $base)->where('s.status', 'dipinjam')->get();
        $terlambat = $aktif->filter(function ($row) {
            return $row->tgl_jatuh_tempo && \Carbon\Carbon::parse($row->tgl_jatuh_tempo)->isPast();
        });

        $jatuhTempoTerdekat = $aktif
            ->filter(fn($r) => $r->tgl_jatuh_tempo && !\Carbon\Carbon::parse($r->tgl_jatuh_tempo)->isPast())
            ->sortBy('tgl_jatuh_tempo')
            ->first();

        return [
            'aktif'               => $aktif,
            'jumlah_aktif'        => $aktif->count(),
            'jumlah_terlambat'    => $terlambat->count(),
            'jatuh_tempo_terdekat' => $jatuhTempoTerdekat,
            'denda_belum_lunas'   => (float) (clone $base)->where('s.status_denda', 'belum_lunas')->sum('s.denda'),
            'total_denda'         => (float) (clone $base)->sum('s.denda'),
            'total_riwayat'       => (clone $base)->count(),
            'total_kembali'       => (clone $base)->where('s.status', 'kembali')->count(),
        ];
    }
}
