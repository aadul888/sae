<?php

namespace Tests\Feature;

use App\Models\PesertaDidik;
use App\Models\PesertaDidikBerkas;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Hapus dokumen peserta didik hanya boleh oleh Administrator.
 * Non-admin (guru, wali kelas, peserta didik) harus ditolak 403.
 */
class BerkasHapusAdminOnlyTest extends TestCase
{
    use DatabaseTransactions;

    private function buatBerkas(): PesertaDidikBerkas
    {
        $pdId = 'PD-DEL-' . time() . '-' . random_int(100, 999);
        PesertaDidik::create([
            'peserta_didik_id' => $pdId,
            'nama' => 'Uji Hapus',
            'nisn' => '888' . random_int(1000000, 9999999),
            'jenis_kelamin' => 'L',
            'nama_rombel' => 'X-A',
        ]);

        return PesertaDidikBerkas::create([
            'peserta_didik_id' => $pdId,
            'jenis_berkas' => 'kartu_keluarga',
            'nama_berkas' => 'Kartu Keluarga (KK) (Asli)',
            'file_path' => '',
            'file_name' => 'kk.pdf',
            'status' => 'valid',
        ]);
    }

    public function test_guru_tidak_bisa_hapus_dokumen(): void
    {
        $berkas = $this->buatBerkas();

        $res = $this->withSession(['user' => ['role' => 'guru', 'nama' => 'Guru Uji']])
            ->deleteJson(route('dashboard.berkas.destroy', $berkas->id));

        $res->assertStatus(403);
        $this->assertDatabaseHas('peserta_didik_berkas', ['id' => $berkas->id]);
    }

    public function test_admin_bisa_hapus_dokumen(): void
    {
        $berkas = $this->buatBerkas();

        $res = $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Administrator Uji']])
            ->deleteJson(route('dashboard.berkas.destroy', $berkas->id));

        $res->assertStatus(200);
        $res->assertJson(['status' => 'success']);
        $this->assertDatabaseMissing('peserta_didik_berkas', ['id' => $berkas->id]);
    }

    public function test_berkas_detail_menandai_is_admin(): void
    {
        $berkas = $this->buatBerkas();

        $admin = $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Admin']])
            ->getJson(route('dashboard.kesiswaan.peserta-didik.berkas.detail', $berkas->peserta_didik_id));
        $admin->assertStatus(200)->assertJson(['is_admin' => true]);
    }
}
