<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Uji Hapus Buku Katalog Perpustakaan.
 */
class PerpusKoleksiHapusTest extends TestCase
{
    private function adminSession(): array
    {
        $admin = DB::table('pengguna')->where('peran_id_str', 'Administrator')->first();

        return ['user' => [
            'id' => $admin->pengguna_id,
            'pengguna_id' => $admin->pengguna_id,
            'name' => $admin->nama,
            'nama' => $admin->nama,
            'username' => $admin->username,
            'role' => 'admin',
            'peran_id_str' => $admin->peran_id_str,
        ]];
    }

    public function test_hapus_buku_berhasil_menghapus_dari_katalog(): void
    {
        DB::beginTransaction();

        try {
            $id = DB::table('perpus_koleksi_buku')->insertGetId([
                'kode_buku' => 'BK-TEST-DEL-01',
                'judul' => 'Buku Uji Hapus Katalog',
                'jumlah_eksemplar' => 5,
                'eksemplar_tersedia' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $res = $this->withSession($this->adminSession())
                ->delete(route('dashboard.perpustakaan.koleksi.destroy', $id));

            $res->assertRedirect(route('dashboard.perpustakaan.koleksi.index'));
            $res->assertSessionHas('success');

            $this->assertNull(DB::table('perpus_koleksi_buku')->where('id', $id)->first());
        } finally {
            DB::rollBack();
        }
    }

    public function test_hapus_buku_yang_sedang_dipinjam_ditolak(): void
    {
        DB::beginTransaction();

        try {
            $id = DB::table('perpus_koleksi_buku')->insertGetId([
                'kode_buku' => 'BK-TEST-DEL-PINJAM',
                'judul' => 'Buku Uji Sedang Dipinjam',
                'jumlah_eksemplar' => 3,
                'eksemplar_tersedia' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('perpus_sirkulasi')->insert([
                'kode_transaksi' => 'TRX-TEST-DEL-01',
                'buku_id' => $id,
                'peminjam_tipe' => 'siswa',
                'peminjam_id' => 1,
                'peminjam_nama' => 'Peminjam Uji',
                'tgl_pinjam' => now()->toDateString(),
                'tgl_jatuh_tempo' => now()->addDays(7)->toDateString(),
                'status' => 'dipinjam',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $res = $this->withSession($this->adminSession())
                ->delete(route('dashboard.perpustakaan.koleksi.destroy', $id));

            $res->assertRedirect();
            $res->assertSessionHas('error');

            $this->assertNotNull(DB::table('perpus_koleksi_buku')->where('id', $id)->first());
        } finally {
            DB::rollBack();
        }
    }
}
