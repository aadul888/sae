<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PresensiMengajarDanKelasPiketTest extends TestCase
{
    private function getAdminUser()
    {
        return [
            'id' => '1',
            'pengguna_id' => '1',
            'nama' => 'Admin SAE',
            'username' => 'opsmakpal@gmail.com',
            'role' => 'admin',
            'peran_id_str' => 'Administrator',
            'ptk_id' => null,
        ];
    }

    public function test_presensi_mengajar_renders_tabs_and_table_container()
    {
        $admin = $this->getAdminUser();

        $response = $this->withSession(['user' => $admin])
            ->get(route('dashboard.presensi-mengajar.index'));

        $response->assertStatus(200);
        $response->assertSee('pm-tab-btn');
        $response->assertSee('tabPaneJadwal');
        $response->assertSee('tabPaneRiwayat');
        $response->assertSee('id="tableDataContainer"', false);
        $response->assertSee('name="rombongan_belajar_id"', false);
        $response->assertSee('name="filter_ptk_id"', false);
    }

    public function test_presensi_kelas_renders_monitoring_and_detail_tabs()
    {
        $admin = $this->getAdminUser();

        $response = $this->withSession(['user' => $admin])
            ->get(route('dashboard.presensi.kelas'));

        $response->assertStatus(200);
        $response->assertSee('pk-tab-btn');
        $response->assertSee('tabPaneMonitoring');
        $response->assertSee('tabPaneDetail');
        $response->assertSee('Monitoring Presensi Seluruh Kelas');
        $response->assertSee('tableMonitoringKelas');
        $response->assertSee('Otomatis (RFID/QR)');
        $response->assertSee('Manual (Wali/Guru)');
    }

    public function test_guru_piket_can_access_presensi_mengajar_and_kelas()
    {
        $guru = [
            'id' => 'guru-piket-test',
            'pengguna_id' => 'guru-piket-test',
            'nama' => 'Guru Piket Test',
            'username' => 'gurupiket@test.sch.id',
            'role' => 'guru',
            'peran_id_str' => 'Guru',
            'ptk_id' => 'ptk-piket-test',
        ];

        // Access presensi mengajar
        $responseMengajar = $this->withSession(['user' => $guru])
            ->get(route('dashboard.presensi-mengajar.index'));
        $responseMengajar->assertStatus(200);
        $responseMengajar->assertSee('tabPaneJadwal');
        $responseMengajar->assertSee('tabPaneRiwayat');

        // Access presensi kelas
        $responseKelas = $this->withSession(['user' => $guru])
            ->get(route('dashboard.presensi.kelas'));
        $responseKelas->assertStatus(200);
        $responseKelas->assertSee('tabPaneMonitoring');
        $responseKelas->assertSee('tabPaneDetail');
    }

    public function test_monitoring_filter_by_status_and_tingkat()
    {
        $admin = $this->getAdminUser();

        $response = $this->withSession(['user' => $admin])
            ->get(route('dashboard.presensi.kelas', [
                'tab' => 'monitoring',
                'monitor_tingkat' => '10',
                'monitor_status' => 'belum',
            ]));

        $response->assertStatus(200);
        $response->assertSee('tableMonitoringKelas');
    }
}
