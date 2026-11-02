<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Sinkron master Sarpras (`sarpras_ruang`) ke jadwal Teknisi.
 *
 * Satu ruang yang punya penanggung jawab → satu baris Jadwal Area Tugas dan
 * satu baris Pemeliharaan Preventif, dikunci `ruang_id`, dengan petugas = PJ
 * ruang. Ganti PJ di Ruang & Gedung → petugas baris ikut bergeser pada
 * pemanggilan berikutnya. Ruang tanpa PJ tidak dicermin.
 *
 * Idempoten & murah: hanya menyentuh tabel yang diminta lewat `syncArea()` /
 * `syncPemeliharaan()`. Dipanggil dari halaman Teknisi terkait dan dari
 * penyimpanan PJ ruang di Sarpras.
 */
final class TeknisiRuangSync
{
    /** Ruang ber-PJ beserta nama PJ (dari GTK atau pengguna manual). */
    private static function ruangPj()
    {
        if (!Table::exists('sarpras_ruang')) {
            return collect();
        }

        return DB::table('sarpras_ruang as r')
            ->leftJoin('gtk as g', 'r.penanggung_jawab_ptk_id', '=', 'g.ptk_id')
            ->leftJoin('pengguna as p', 'r.penanggung_jawab_ptk_id', '=', 'p.pengguna_id')
            ->whereNotNull('r.penanggung_jawab_ptk_id')
            ->where('r.penanggung_jawab_ptk_id', '<>', '')
            ->orderBy('r.nama_ruang')
            ->get([
                'r.id', 'r.nama_ruang', 'r.gedung', 'r.penanggung_jawab_ptk_id',
                DB::raw('COALESCE(g.nama, p.nama) as pj_nama'),
            ]);
    }

    private static function potong(?string $teks, int $panjang): ?string
    {
        $teks = trim((string) $teks);

        return $teks === '' ? null : mb_substr($teks, 0, $panjang);
    }

    /**
     * Nomor urut dokumen bulan ini: `<prefix><YYYYMM><sep><NNNN>`.
     *
     * `$prefix` sudah memuat pemisah sebelum periode, mis. `AT/` atau `PM-`.
     */
    private static function nomor(string $table, string $column, string $prefix, string $sep = '/'): string
    {
        $periode = now()->format('Ym');
        $like = $prefix . $periode . $sep . '%';
        $last = DB::table($table)->where($column, 'like', $like)->orderByDesc('id')->value($column);

        $urut = 1;
        if ($last) {
            $bagian = explode($sep, (string) $last);
            $urut = ((int) end($bagian)) + 1;
        }

        $nomor = sprintf('%s%s%s%04d', $prefix, $periode, $sep, $urut);

        $guard = 0;
        while (DB::table($table)->where($column, $nomor)->exists() && $guard < 50) {
            $urut++;
            $nomor = sprintf('%s%s%s%04d', $prefix, $periode, $sep, $urut);
            $guard++;
        }

        return $nomor;
    }

    /** Sinkron satu baris Jadwal Area Tugas per ruang ber-PJ. */
    public static function syncArea(): void
    {
        if (!Table::exists('teknisi_area_tugas')) {
            return;
        }

        $ruang = self::ruangPj();
        $ids = $ruang->pluck('id')->map(fn ($v) => (int) $v)->all();

        // Buang baris cermin (tanpa `user_id`) yang ruangnya tak lagi ber-PJ.
        $stale = DB::table('teknisi_area_tugas')->whereNull('user_id')->whereNotNull('ruang_id');
        if (!empty($ids)) {
            $stale->whereNotIn('ruang_id', $ids);
        }
        $stale->delete();

        if ($ruang->isEmpty()) {
            return;
        }

        $ada = DB::table('teknisi_area_tugas')
            ->whereIn('ruang_id', $ids)
            ->orderBy('id')
            ->get(['id', 'ruang_id', 'petugas_ptk_id', 'petugas_nama'])
            ->groupBy('ruang_id')
            ->map(fn ($rows) => $rows->first());

        foreach ($ruang as $r) {
            $nama = self::potong($r->pj_nama, 150);
            if ($nama === null) {
                continue; // tak bisa jadi petugas tanpa nama
            }

            $row = $ada->get($r->id);
            if ($row) {
                if ((string) $row->petugas_ptk_id !== (string) $r->penanggung_jawab_ptk_id
                    || (string) $row->petugas_nama !== $nama) {
                    DB::table('teknisi_area_tugas')->where('id', $row->id)->update([
                        'petugas_ptk_id' => $r->penanggung_jawab_ptk_id,
                        'petugas_nama'   => $nama,
                        'updated_at'     => now(),
                    ]);
                }
                continue;
            }

            DB::table('teknisi_area_tugas')->insert([
                'ruang_id'        => $r->id,
                'nomor_jadwal'    => self::nomor('teknisi_area_tugas', 'nomor_jadwal', 'AT'),
                'area'            => self::potong($r->nama_ruang, 150) ?? 'Ruang',
                'zona'            => self::potong($r->gedung, 80),
                'periode_jadwal'  => 'harian',
                'petugas_ptk_id'  => $r->penanggung_jawab_ptk_id,
                'petugas_nama'    => $nama,
                'shift'           => 'pagi',
                'jenis_tugas'     => 'tukang_kebersihan',
                'status'          => 'aktif',
                'user_id'         => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    /** Sinkron satu baris Pemeliharaan Preventif per ruang ber-PJ. */
    public static function syncPemeliharaan(): void
    {
        if (!Table::exists('teknisi_pemeliharaan')) {
            return;
        }

        $ruang = self::ruangPj();
        $ids = $ruang->pluck('id')->map(fn ($v) => (int) $v)->all();

        $stale = DB::table('teknisi_pemeliharaan')->whereNull('user_id')->whereNotNull('ruang_id');
        if (!empty($ids)) {
            $stale->whereNotIn('ruang_id', $ids);
        }
        $stale->delete();

        if ($ruang->isEmpty()) {
            return;
        }

        $ada = DB::table('teknisi_pemeliharaan')
            ->whereIn('ruang_id', $ids)
            ->orderBy('id')
            ->get(['id', 'ruang_id', 'penanggung_jawab'])
            ->groupBy('ruang_id')
            ->map(fn ($rows) => $rows->first());

        foreach ($ruang as $r) {
            $nama = self::potong($r->pj_nama, 100);
            $lokasi = self::potong($r->nama_ruang, 100);
            if ($nama === null || $lokasi === null) {
                continue;
            }

            $row = $ada->get($r->id);
            if ($row) {
                if ((string) $row->penanggung_jawab !== $nama) {
                    DB::table('teknisi_pemeliharaan')->where('id', $row->id)->update([
                        'penanggung_jawab' => $nama,
                        'updated_at'       => now(),
                    ]);
                }
                continue;
            }

            DB::table('teknisi_pemeliharaan')->insert([
                'ruang_id'          => $r->id,
                'kode_pemeliharaan' => self::nomor('teknisi_pemeliharaan', 'kode_pemeliharaan', 'PM-', '-'),
                'nama_kegiatan'     => 'Pemeliharaan Preventif ' . $lokasi,
                'kategori'          => 'bangunan',
                'lokasi_aset'       => $lokasi,
                'frekuensi'         => 'bulanan',
                'tgl_jadwal'        => now()->toDateString(),
                'penanggung_jawab'  => $nama,
                'biaya'             => 0,
                'status'            => 'terjadwal',
                'user_id'           => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }
}
