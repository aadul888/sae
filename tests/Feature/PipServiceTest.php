<?php

namespace Tests\Feature;

use App\Models\PipPengaturan;
use App\Models\User;
use App\Services\PipService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PipServiceTest extends TestCase
{
    public function test_pip_service_helpers_parse_nominal_and_tanggal(): void
    {
        $this->assertNull(PipService::parseNominal(''));
        $this->assertEquals(250000.0, PipService::parseNominal('250000'));
        $this->assertEquals(1800000.0, PipService::parseNominal('Rp 1.800.000'));

        $this->assertSame('', PipService::parseTanggal(''));
        $this->assertSame('2026-05-15', PipService::parseTanggal('2026-05-15'));
        // Excel serial 45123 -> date string
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', PipService::parseTanggal(45123));
    }

    public function test_pip_service_ensure_schema_creates_tables_and_columns(): void
    {
        PipService::ensureSchema();

        $this->assertTrue(Schema::hasTable('pip_kuota_rombel'));
        $this->assertTrue(Schema::hasTable('pip_usulan'));
        $this->assertTrue(Schema::hasTable('pip_riwayat'));
        $this->assertTrue(Schema::hasTable('pip_pengaturan'));
    }

    public function test_guest_cannot_access_pip_dashboard(): void
    {
        $response = $this->get(route('dashboard.pip.index'));
        $response->assertStatus(302);
    }

    public function test_admin_can_access_pip_dashboard(): void
    {
        $sessionUser = [
            'id'          => 'admin-pip-test',
            'pengguna_id' => 'admin-pip-test',
            'username'    => 'admin',
            'nama'        => 'Administrator Test',
            'role'        => 'admin',
        ];

        $response = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.pip.index'));

        $response->assertStatus(200);
        $response->assertViewHas('pipPengaturan');
        $response->assertViewHas('dashboardData');
    }
}
