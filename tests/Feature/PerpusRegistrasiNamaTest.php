<?php

namespace Tests\Feature;

use App\Models\PerpusAnggota;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Registrasi anggota perpustakaan lewat nama (selain NISN/NIP):
 * pencarian kandidat master data + pendaftaran dari kandidat terpilih.
 * Data uji dibuat sintetis di dalam transaksi lalu di-rollback.
 */
class PerpusRegistrasiNamaTest extends TestCase
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

    /** Buat peserta didik sintetis, kembalikan id-nya. */
    private function pesertaDidikUji(string $nama): string
    {
        $id = 'UJI-PD-' . Str::upper(Str::random(8));

        DB::table('peserta_didik')->insert([
            'peserta_didik_id' => $id,
            'nama' => $nama,
            'nisn' => null,
            'nama_rombel' => 'Rombel Uji',
            'jenis_kelamin' => 'L',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    public function test_cek_master_berdasarkan_nama_mengembalikan_kandidat_sesuai_tipe(): void
    {
        DB::beginTransaction();

        try {
            $tanda = 'Nama' . Str::upper(Str::random(6));
            $idPd = $this->pesertaDidikUji('Siswa ' . $tanda . ' Satu');
            $this->pesertaDidikUji('Siswa ' . $tanda . ' Dua');

            DB::table('gtk')->insert([
                'ptk_id' => 'UJI-GTK-' . Str::upper(Str::random(8)),
                'nama' => 'Guru ' . $tanda . ' Satu',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $json = $this->withSession($this->adminSession())
                ->getJson("/dashboard/perpustakaan/anggota/cek-master?q={$tanda}&tipe=siswa")
                ->assertOk()
                ->json();

            $this->assertTrue($json['ok']);
            $this->assertSame('siswa', $json['tipe']);
            $this->assertCount(2, $json['hasil']);

            foreach ($json['hasil'] as $kandidat) {
                $this->assertSame('siswa', $kandidat['tipe']);
                $this->assertStringContainsString($tanda, $kandidat['nama']);
                $this->assertTrue($kandidat['bisa_pilih']);
                $this->assertFalse($kandidat['sudah_terdaftar']);
            }

            $this->assertContains($idPd, array_column($json['hasil'], 'master_id'));
        } finally {
            DB::rollBack();
        }
    }

    public function test_registrasi_lewat_kandidat_nama_mendaftarkan_anggota(): void
    {
        DB::beginTransaction();

        try {
            $nama = 'Kandidat ' . Str::upper(Str::random(6));
            $idPd = $this->pesertaDidikUji($nama);

            $this->withSession($this->adminSession())
                ->post('/dashboard/perpustakaan/anggota', [
                    'tipe' => 'siswa',
                    'master_tipe' => 'siswa',
                    'master_id' => $idPd,
                    'nisn_nip' => '',
                ])
                ->assertRedirect(route('dashboard.perpustakaan.anggota.index'));

            $anggota = PerpusAnggota::where('siswa_id', $idPd)->first();
            $this->assertNotNull($anggota);
            $this->assertSame('siswa', $anggota->tipe);
            $this->assertSame($nama, $anggota->nama);
            $this->assertSame('Rombel Uji', $anggota->rombel_atau_unit);
        } finally {
            DB::rollBack();
        }
    }

    public function test_nama_kurang_dari_tiga_huruf_ditolak(): void
    {
        $this->withSession($this->adminSession())
            ->getJson('/dashboard/perpustakaan/anggota/cek-master?q=ab&tipe=siswa')
            ->assertStatus(200)
            ->assertJson(['ok' => false]);
    }
}
