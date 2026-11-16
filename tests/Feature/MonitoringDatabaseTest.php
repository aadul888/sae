<?php

namespace Tests\Feature;

use App\Models\AdminAktivitas;
use Tests\TestCase;

class MonitoringDatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_access_monitoring_page_default_today(): void
    {
        $today = now()->toDateString();

        $response = $this->withSession([
            'user' => [
                'id' => 1,
                'username' => 'admin_test',
                'nama' => 'Administrator Test',
                'role' => 'admin',
            ],
        ])->get(route('dashboard.aktivitas-database.index'));

        $response->assertStatus(200);
        $response->assertSee('Monitoring');
        $response->assertSee('LIVE MONITORING AKTIF');
        $response->assertSee('chartHourlyAktivitas');
        $response->assertSee('chartCrudDistribution');
        $response->assertSee('chartBidangTraffic');
        $response->assertSee('chartMenuTraffic');
        $response->assertSee('chartFiturTraffic');
        $response->assertSee('monitoringChartPayload');
        $response->assertViewHas('startDate', $today);
        $response->assertViewHas('endDate', $today);
        $response->assertViewHas('range', 'today');
        $response->assertViewHas('chartData');
    }

    public function test_live_endpoint_returns_json_and_chart_datasets(): void
    {
        $response = $this->withSession([
            'user' => [
                'id' => 1,
                'username' => 'admin_test',
                'nama' => 'Administrator Test',
                'role' => 'admin',
            ],
        ])->getJson(route('dashboard.aktivitas-database.live'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'stats' => [
                'total',
                'today',
                'create',
                'read',
                'update',
                'delete',
            ],
            'charts' => [
                'hourly' => ['labels', 'create', 'update', 'delete', 'read'],
                'crud' => ['labels', 'data'],
                'bidang' => ['labels', 'data'],
                'menu' => ['labels', 'data'],
                'fitur' => ['labels', 'data'],
                'seven_days' => ['labels', 'data'],
            ],
            'latest_id',
            'latest_logs',
        ]);
    }

    public function test_show_endpoint_returns_json_detail(): void
    {
        $log = AdminAktivitas::create([
            'admin_name' => 'Admin Unit Test',
            'admin_username' => 'admin_unit',
            'ip_address' => '127.0.0.1',
            'modul' => 'Unit Test',
            'aktivitas' => 'Testing Monitoring Endpoint',
            'keterangan' => 'Unit test payload sample',
            'tipe' => 'create',
        ]);

        $response = $this->withSession([
            'user' => [
                'id' => 1,
                'username' => 'admin_test',
                'nama' => 'Administrator Test',
                'role' => 'admin',
            ],
        ])->getJson(route('dashboard.aktivitas-database.show', $log->id));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'id' => $log->id,
                'modul' => 'Unit Test',
                'aktivitas' => 'Testing Monitoring Endpoint',
            ],
        ]);

        $this->assertArrayHasKey('konteks', $response->json('data'));
        $this->assertArrayHasKey('bidang', $response->json('data.konteks'));
        $this->assertArrayHasKey('menu', $response->json('data.konteks'));
        $this->assertArrayHasKey('fitur', $response->json('data.konteks'));
        $this->assertArrayHasKey('bagian', $response->json('data.konteks'));

        $log->delete();
    }

    public function test_admin_can_filter_monitoring_by_menu(): void
    {
        $response = $this->withSession([
            'user' => [
                'id' => 1,
                'username' => 'admin_test',
                'nama' => 'Administrator Test',
                'role' => 'admin',
            ],
        ])->get(route('dashboard.aktivitas-database.index', ['menu' => 'Kesiswaan', 'all_time' => 1]));

        $response->assertStatus(200);
        $response->assertViewHas('menu', 'Kesiswaan');
        $response->assertSee('Semua Menu');

        // Filter harus benar-benar menyaring: hanya modul anggota grup Kesiswaan.
        $list = $response->viewData('list');
        foreach ($list as $row) {
            $this->assertContains($row->modul, \App\Support\MonitoringMenu::modulesFor('Kesiswaan'));
        }
    }

    public function test_menu_filter_does_not_leak_other_groups(): void
    {
        $response = $this->withSession([
            'user' => [
                'id' => 1,
                'username' => 'admin_test',
                'nama' => 'Administrator Test',
                'role' => 'admin',
            ],
        ])->get(route('dashboard.aktivitas-database.index', ['menu' => 'Perpustakaan', 'all_time' => 1]));

        $response->assertStatus(200);
        // Grup tanpa data harus kosong, bukan menampilkan seluruh baris.
        $this->assertSame(0, $response->viewData('list')->total());
    }

    public function test_every_database_write_is_logged_automatically(): void
    {
        $row = \App\Models\Pengumuman::query()->first();
        if (!$row) {
            $this->markTestSkipped('Tidak ada data pengumuman untuk diuji.');
        }

        $before = AdminAktivitas::where('aktivitas', 'like', '[UPDATE] pengumuman%')->count();

        $row->touch();

        $after = AdminAktivitas::where('aktivitas', 'like', '[UPDATE] pengumuman%')->count();
        $this->assertGreaterThan($before, $after, 'Perubahan model harus tercatat otomatis tanpa AdminAktivitas::record().');

        $log = AdminAktivitas::where('aktivitas', 'like', '[UPDATE] pengumuman%')->latest('id')->first();
        $this->assertSame('Layanan Digital', \App\Support\MonitoringMenu::groupFor($log->modul));
    }

    public function test_delete_endpoint_removes_log(): void
    {
        $log = AdminAktivitas::create([
            'admin_name' => 'Admin Unit Test',
            'admin_username' => 'admin_unit',
            'ip_address' => '127.0.0.1',
            'modul' => 'Unit Test',
            'aktivitas' => 'Testing Delete Endpoint',
            'keterangan' => 'Unit test to be deleted',
            'tipe' => 'delete',
        ]);

        $response = $this->withSession([
            'user' => [
                'id' => 1,
                'username' => 'admin_test',
                'nama' => 'Administrator Test',
                'role' => 'admin',
            ],
        ])->deleteJson(route('dashboard.aktivitas-database.destroy', $log->id));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);

        $this->assertNull(AdminAktivitas::find($log->id));
    }

    public function test_monitoring_view_complies_with_sae_design_tokens(): void
    {
        $path = resource_path('views/dashboard/aktivitas-database.blade.php');
        $this->assertFileExists($path);

        $content = file_get_contents($path);

        // font-size mentah (misal font-size: 14px atau 0.875rem tanpa var(--text-*))
        preg_match_all('/font-size:\s*[0-9.]+rem/i', $content, $fontMatches);
        $this->assertCount(0, $fontMatches[0], 'View monitoring mengandung font-size mentah.');

        // border-radius mentah (misal border-radius: 8px tanpa var(--radius-*))
        preg_match_all('/border-radius:\s*[0-9]+px/i', $content, $radiusMatches);
        $this->assertCount(0, $radiusMatches[0], 'View monitoring mengandung border-radius mentah.');
    }
}
