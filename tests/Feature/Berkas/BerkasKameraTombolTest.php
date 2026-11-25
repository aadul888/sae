<?php

namespace Tests\Feature\Berkas;

use App\Models\User;
use App\Support\AuthSession;
use Tests\TestCase;

/**
 * Halaman berkas peserta didik harus menampilkan tombol "Ambil Foto -> PDF"
 * untuk siswa/wali (baik login langsung maupun impersonasi admin).
 */
class BerkasKameraTombolTest extends TestCase
{
    private function siswa(): ?User
    {
        return User::where('peran_id_str', 'Peserta Didik')
            ->whereNotNull('peserta_didik_id')
            ->first();
    }

    public function test_siswa_melihat_tombol_kamera(): void
    {
        $u = $this->siswa();
        if (!$u) {
            $this->markTestSkipped('Tidak ada pengguna Peserta Didik.');
        }

        $data = AuthSession::dataFor($u);
        $this->assertSame('peserta_didik', $data['role'], 'dataFor role bukan peserta_didik');

        $res = $this->withSession(['user' => $data])->get('/dashboard/berkas');
        $res->assertStatus(200);
        $res->assertSee('btn-camera-pdf', false);
        $res->assertSee('Ambil Foto', false);
    }

    public function test_siswa_impersonasi_melihat_tombol_kamera(): void
    {
        $u = $this->siswa();
        if (!$u) {
            $this->markTestSkipped('Tidak ada pengguna Peserta Didik.');
        }

        $data = AuthSession::dataFor($u);
        $res = $this->withSession([
            'user' => $data,
            'impersonator' => 'admin-x',
        ])->get('/dashboard/berkas');

        $res->assertStatus(200);
        $res->assertSee('btn-camera-pdf', false);
    }

    /**
     * Regresi: sesi lama / hasil pemulihan remember-token bisa tanpa key 'role'.
     * Peran harus tetap diturunkan dari 'peran_id_str' agar tombol kamera muncul.
     */
    public function test_siswa_tanpa_key_role_tetap_melihat_tombol_kamera(): void
    {
        $u = $this->siswa();
        if (!$u) {
            $this->markTestSkipped('Tidak ada pengguna Peserta Didik.');
        }

        $data = AuthSession::dataFor($u);
        unset($data['role']); // simulasikan sesi tanpa key role

        $res = $this->withSession(['user' => $data])->get('/dashboard/berkas');

        $res->assertStatus(200);
        $res->assertSee('btn-camera-pdf', false);
        $res->assertSee('Ambil Foto', false);
        $res->assertDontSee('Mode Pengelola', false);
    }
}
