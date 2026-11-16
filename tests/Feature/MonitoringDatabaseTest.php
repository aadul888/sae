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
                'modules' => ['labels', 'data'],
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

    public function test_admin_can_filter_monitoring_by_bidang(): void
    {
        $response = $this->withSession([
            'user' => [
                'id' => 1,
                'username' => 'admin_test',
                'nama' => 'Administrator Test',
                'role' => 'admin',
            ],
        ])->get(route('dashboard.aktivitas-database.index', ['bidang' => 'Kesiswaan', 'all_time' => 1]));

        $response->assertStatus(200);
        $response->assertViewHas('bidang', 'Kesiswaan');
        $response->assertSee('Semua Bidang');
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
