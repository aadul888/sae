<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Peta / Denah Sekolah (Sarpras) — akses + render halaman.
 * Lihat .agents/docs/RENCANA_PETA_DENAH_SARPRAS.md
 */
class SarprasPetaTest extends TestCase
{
    private function adminSession(): array
    {
        return ['user' => [
            'id'          => 'admin-peta-test',
            'pengguna_id' => 'admin-peta-test',
            'username'    => 'admin',
            'nama'        => 'Administrator Test',
            'role'        => 'admin',
        ]];
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get(route('dashboard.sarpras.peta.index'))
            ->assertStatus(302)
            ->assertRedirect(route('login'));
    }

    public function test_admin_dapat_membuka_peta_sekolah(): void
    {
        $response = $this->withSession($this->adminSession())
            ->get(route('dashboard.sarpras.peta.index'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.sarpras.peta');
        $response->assertViewHas('areas');
        $response->assertViewHas('stat');
        $response->assertViewHas('statuses');
        $response->assertViewHas('mapData');
    }

    public function test_stat_area_sesuai_jumlah_seed(): void
    {
        $response = $this->withSession($this->adminSession())
            ->get(route('dashboard.sarpras.peta.index'));

        $stat = $response->viewData('stat');
        $statuses = $response->viewData('statuses');

        // Seeder mengisi 78 area (lihat SarprasPetaSeeder).
        $this->assertSame(78, $stat['total']);

        // Total = jumlah seluruh status (5 kategori, tak saling tumpang tindih).
        $jumlahStatus = 0;
        foreach (array_keys($statuses) as $k) {
            $jumlahStatus += $stat[$k];
        }
        $this->assertSame($stat['total'], $jumlahStatus, 'Jumlah tiap status harus = total area.');

        // Rincian resmi peta revisi 1 (setelah koreksi RPK & KM/WC tidak bertanda merah).
        $this->assertSame(9, $stat['revit_2025']);
        $this->assertSame(15, $stat['rusak_2026']);
        $this->assertSame(2, $stat['siap_bangun']);
        $this->assertSame(3, $stat['lahan_kosong']);
    }

    public function test_ruang_tersedia_mengembalikan_json(): void
    {
        $response = $this->withSession($this->adminSession())
            ->get(route('dashboard.sarpras.peta.ruang-tersedia'));

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }
}
