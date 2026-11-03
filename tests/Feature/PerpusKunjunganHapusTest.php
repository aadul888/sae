<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Hapus catatan Buku Kunjungan perpustakaan.
 * Data uji dibuat sintetis di dalam transaksi lalu di-rollback.
 */
class PerpusKunjunganHapusTest extends TestCase
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

    public function test_hapus_kunjungan_menghapus_baris(): void
    {
        DB::beginTransaction();

        try {
            $id = DB::table('perpus_kunjungan')->insertGetId([
                'tanggal' => now()->toDateString(),
                'jam_kunjung' => '08:30:00',
                'pengunjung_tipe' => 'siswa',
                'nama' => 'Uji Hapus Kunjungan',
                'keperluan' => 'Membaca',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->withSession($this->adminSession())
                ->delete("/dashboard/perpustakaan/kunjungan/{$id}")
                ->assertRedirect(route('dashboard.perpustakaan.kunjungan'));

            $this->assertNull(DB::table('perpus_kunjungan')->where('id', $id)->first());
        } finally {
            DB::rollBack();
        }
    }
}
