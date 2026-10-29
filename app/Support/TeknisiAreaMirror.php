<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Cermin master Sarana Prasarana (`sarpras_ruang`) ke `teknisi_area_master`.
 *
 * Area master adalah data bersama (bukan milik satu akun): satu ruang = satu
 * baris, dikunci lewat `ruang_id`. Kolom yang berasal dari master (nama, zona,
 * gedung, lantai, petugas) selalu disegarkan agar pergantian penanggung jawab
 * langsung terlihat; suntingan petugas (kategori, frekuensi, status,
 * keterangan) tidak ditimpa.
 *
 * Dipanggil oleh halaman area master, area tugas, dan pemeliharaan supaya
 * ketiganya memakai sumber turunan yang sama pada request yang sama.
 */
final class TeknisiAreaMirror
{
    public static function sync(): void
    {
        if (!Table::existsAll('sarpras_ruang', 'teknisi_area_master')) {
            return;
        }

        // Seluruh ruang master tercermin, bukan hanya yang PJ-nya berperan
        // teknisi (PJ ruang umumnya guru/wali kelas).
        $ruang = DB::table('sarpras_ruang as r')
            ->leftJoin('gtk as g', 'r.penanggung_jawab_ptk_id', '=', 'g.ptk_id')
            ->leftJoin('pengguna as p', 'r.penanggung_jawab_ptk_id', '=', 'p.pengguna_id')
            ->orderBy('r.nama_ruang')
            ->get([
                'r.id', 'r.nama_ruang', 'r.gedung', 'r.lantai', 'r.kondisi', 'r.keterangan',
                'r.penanggung_jawab_ptk_id',
                DB::raw('COALESCE(g.nama, p.nama) as pj_nama'),
            ]);

        if ($ruang->isEmpty()) {
            return;
        }

        $ada = DB::table('teknisi_area_master')
            ->whereIn('ruang_id', $ruang->pluck('id')->all())
            ->get(['id', 'ruang_id', 'nama_area', 'gedung', 'lantai', 'petugas_ptk_id', 'petugas_nama'])
            ->keyBy('ruang_id');

        // Segarkan baris yang sudah ada: pergantian PJ di master Sarpras harus
        // langsung menggeser petugas pada baris cermin.
        foreach ($ruang as $r) {
            $row = $ada->get($r->id);
            if (!$row) {
                continue;
            }

            $pjBaru   = $r->penanggung_jawab_ptk_id ?: null;
            $namaBaru = $r->pj_nama ?: null;

            if ((string) $row->petugas_ptk_id === (string) $pjBaru
                && (string) $row->petugas_nama === (string) $namaBaru
                && (string) $row->nama_area === (string) $r->nama_ruang
                && (string) $row->gedung === (string) $r->gedung
                && (string) $row->lantai === (string) $r->lantai) {
                continue;
            }

            DB::table('teknisi_area_master')->where('id', $row->id)->update([
                'nama_area'      => $r->nama_ruang,
                'zona'           => $r->gedung,
                'gedung'         => $r->gedung,
                'lantai'         => $r->lantai,
                'petugas_ptk_id' => $pjBaru,
                'petugas_nama'   => $namaBaru,
                'updated_at'     => now(),
            ]);
        }

        $baru = [];
        $urut = self::nomorTerakhir('teknisi_area_master', 'kode_area', 'AR');
        foreach ($ruang as $r) {
            if ($ada->has($r->id)) {
                continue;
            }
            $urut++;
            $baru[] = [
                'kode_area'       => sprintf('AR/%s/%04d', now()->format('Ym'), $urut),
                'nama_area'       => $r->nama_ruang,
                'zona'            => $r->gedung,
                'gedung'          => $r->gedung,
                'lantai'          => $r->lantai,
                'ruang_id'        => $r->id,
                'kategori'        => self::kategoriDariRuang((string) $r->nama_ruang),
                'frekuensi'       => 'harian',
                // Petugas mengikuti penanggung jawab ruang di master Sarpras.
                'petugas_ptk_id'  => $r->penanggung_jawab_ptk_id ?: null,
                'petugas_nama'    => $r->pj_nama ?: null,
                'status'          => $r->kondisi === 'baik' ? 'aktif' : 'nonaktif',
                'keterangan'      => $r->keterangan,
                // Tabel bersama: tanpa `user_id` agar tidak terkunci ke akun
                // yang kebetulan membuka halaman lebih dulu.
                'user_id'         => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        if (!empty($baru)) {
            DB::table('teknisi_area_master')->insert($baru);
        }
    }

    /**
     * Nomor urut terakhir dokumen berprefix `PREFIX/YYYYMM/NNNN` bulan ini.
     */
    private static function nomorTerakhir(string $table, string $column, string $prefix): int
    {
        $last = DB::table($table)
            ->where($column, 'like', "{$prefix}/" . now()->format('Ym') . '/%')
            ->orderByDesc('id')
            ->value($column);

        if (!$last) {
            return 0;
        }

        $parts = explode('/', (string) $last);

        return (int) end($parts);
    }

    /**
     * Tebak kategori area dari nama ruang master (`administrasi`, `laboratorium`, ...).
     */
    private static function kategoriDariRuang(string $nama): string
    {
        $n = mb_strtolower($nama);

        return match (true) {
            str_contains($n, 'wc'), str_contains($n, 'jamban'), str_contains($n, 'kantin'),
            str_contains($n, 'koperasi') => 'fasilitas_umum',
            str_contains($n, 'lab'), str_contains($n, 'bengkel'),
            str_contains($n, 'studio'), str_contains($n, 'greenhouse') => 'laboratorium',
            str_contains($n, 'aula'), str_contains($n, 'lapangan'),
            str_contains($n, 'gor'), str_contains($n, 'lahan'),
            str_contains($n, 'kebun') => 'lapangan',
            str_contains($n, 'teras'), str_contains($n, 'halaman') => 'teras_halaman',
            str_contains($n, 'rapat'), str_contains($n, 'guru'),
            str_contains($n, 'kepala'), str_contains($n, 'tata usaha'),
            str_contains($n, 'tu'), str_contains($n, 'perpustakaan') => 'administrasi',
            str_contains($n, 'operator'), str_contains($n, 'bendahara'),
            str_contains($n, 'wakasek'), str_contains($n, 'humas') => 'perkantoran',
            default => 'lainnya',
        };
    }
}
