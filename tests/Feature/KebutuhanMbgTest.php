<?php

namespace Tests\Feature;

use App\Services\MbgService;
use Tests\TestCase;

class KebutuhanMbgTest extends TestCase
{
    public function test_guest_cannot_access_mbg(): void
    {
        $response = $this->get(route('dashboard.kebutuhan-mbg.index'));
        $response->assertStatus(302);
    }

    public function test_admin_can_access_mbg_and_view_tabs(): void
    {
        $sessionUser = [
            'id' => 'admin-mbg-test',
            'pengguna_id' => 'admin-mbg-test',
            'username' => 'admin',
            'nama' => 'Administrator Test',
            'role' => 'admin',
        ];

        $response = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.kebutuhan-mbg.index'));

        $response->assertStatus(200);
        $response->assertSee('Data Kebutuhan MBG');
        $response->assertSee('Guru &amp; Tendik', false);
        $response->assertSee('Kelas 10');
        $response->assertSee('Kelas 11');
        $response->assertSee('Kelas 12');

        // Test Tab Kelas 10
        $tabResponse = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.kebutuhan-mbg.index', ['tab' => 'kelas_10']));
        $tabResponse->assertStatus(200);
    }

    public function test_mbg_service_returns_correct_stats(): void
    {
        $stats = MbgService::getStats();

        $this->assertArrayHasKey('total_penerima', $stats);
        $this->assertArrayHasKey('total_siswa', $stats);
        $this->assertArrayHasKey('kelas_10', $stats);
        $this->assertArrayHasKey('kelas_11', $stats);
        $this->assertArrayHasKey('kelas_12', $stats);
        $this->assertArrayHasKey('gtk_total', $stats);
        $this->assertGreaterThan(0, $stats['total_penerima']);
    }

    public function test_admin_can_export_mbg_excel(): void
    {
        $sessionUser = [
            'id' => 'admin-mbg-test',
            'pengguna_id' => 'admin-mbg-test',
            'username' => 'admin',
            'nama' => 'Administrator Test',
            'role' => 'admin',
        ];

        $response = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.kebutuhan-mbg.export'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
