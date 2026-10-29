<?php

namespace Tests\Feature;

use App\Models\PerpusAnggota;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Kartu anggota perpustakaan bergaya kartu pelajar digital:
 * sisi depan/belakang, level literasi, QR, dan barcode.
 * Data uji dibuat sintetis di dalam transaksi lalu di-rollback.
 */
class PerpusKartuAnggotaTest extends TestCase
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

    private function anggotaUji(): PerpusAnggota
    {
        return PerpusAnggota::create([
            'nomor_anggota' => 'UJI-'.Str::upper(Str::random(6)),
            'token_kartu' => Str::random(32),
            'tipe' => 'umum',
            'nama' => 'Anggota Uji Kartu',
            'nisn_nip' => null,
            'rombel_atau_unit' => 'Unit Uji',
            'password' => Hash::make('uji'),
            'is_active' => true,
            'poin' => 60,
            'tgl_daftar' => now()->toDateString(),
        ]);
    }

    public function test_kartu_digital_dashboard_menampilkan_depan_belakang_qr_dan_level(): void
    {
        DB::beginTransaction();

        try {
            $anggota = $this->anggotaUji();

            $html = $this->withSession($this->adminSession())
                ->get("/dashboard/perpustakaan/anggota/{$anggota->id}/kartu")
                ->assertOk()
                ->getContent();

            $this->assertStringContainsString('kp-card-front', $html);
            $this->assertStringContainsString('kp-card-back', $html);
            $this->assertStringContainsString('pk-theme', $html);
            $this->assertStringContainsString('Kartu Anggota Perpustakaan', $html);
            $this->assertStringContainsString($anggota->nomor_anggota, $html);
            $this->assertStringContainsString('Pembaca', $html); // level literasi untuk 60 poin
            $this->assertStringContainsString('<svg', $html); // QR + barcode inline
        } finally {
            DB::rollBack();
        }
    }

    public function test_cetak_massal_membuat_satu_lembar_per_lima_anggota(): void
    {
        DB::beginTransaction();

        try {
            $anggota = $this->anggotaUji();

            $html = $this->withSession($this->adminSession())
                ->get('/dashboard/perpustakaan/anggota/kartu/cetak?ids[]='.$anggota->id)
                ->assertOk()
                ->getContent();

            $this->assertStringContainsString('kp-a4-sheet', $html);
            $this->assertStringContainsString('kp-row-front', $html);
            $this->assertStringContainsString('kp-row-back', $html);
            $this->assertStringContainsString($anggota->nomor_anggota, $html);
        } finally {
            DB::rollBack();
        }
    }

    public function test_member_dapat_melihat_kartunya_sendiri(): void
    {
        DB::beginTransaction();

        try {
            $anggota = $this->anggotaUji();

            $html = $this->withSession(['perpus_anggota_id' => $anggota->id])
                ->get('/perpustakaan/kartu')
                ->assertOk()
                ->getContent();

            $this->assertStringContainsString('kp-card-front', $html);
            $this->assertStringContainsString($anggota->nomor_anggota, $html);
        } finally {
            DB::rollBack();
        }
    }

    public function test_member_belum_masuk_tidak_bisa_melihat_kartu(): void
    {
        $this->get('/perpustakaan/kartu')->assertRedirect();
    }
}
