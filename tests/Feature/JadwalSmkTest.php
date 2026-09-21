<?php

namespace Tests\Feature;

use App\Models\JadwalKbm;
use App\Models\JadwalPengaturan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JadwalSmkTest extends TestCase
{
    public function test_jp_tingkat_defaults_smk(): void
    {
        $jpTingkat = JadwalPengaturan::getJpTingkat();
        $this->assertIsArray($jpTingkat);
        $this->assertEquals(50, (int) ($jpTingkat['10'] ?? 0));
        $this->assertEquals(48, (int) ($jpTingkat['11'] ?? 0));
        $this->assertEquals(46, (int) ($jpTingkat['12'] ?? 0));
    }

    public function test_is_mapel_pkl_detection(): void
    {
        $this->assertTrue(JadwalKbm::isMapelPkl('PKL MTK'));
        $this->assertTrue(JadwalKbm::isMapelPkl('PKL KK'));
        $this->assertTrue(JadwalKbm::isMapelPkl('Praktik Kerja Lapangan'));
        $this->assertTrue(JadwalKbm::isMapelPkl('Praktek Kerja Industri (PKL)'));
        $this->assertFalse(JadwalKbm::isMapelPkl('Matematika'));
        $this->assertFalse(JadwalKbm::isMapelPkl('Bahasa Indonesia'));
        $this->assertFalse(JadwalKbm::isMapelPkl(null));
    }

    public function test_scope_exclude_pkl_sql(): void
    {
        $sql = JadwalKbm::query()->excludePkl()->toSql();
        $this->assertStringContainsString('nama_mata_pelajaran', $sql);
        $this->assertStringContainsString('NOT LIKE', $sql);
    }

    public function test_run_auto_scheduler_simulation(): void
    {
        $service = new \App\Services\AutoSchedulerService();
        $res = $service->generate([
            'clear_existing' => true,
            'max_jp_per_sesi' => 3,
        ]);

        $this->assertTrue($res['success']);
        $this->assertGreaterThan(1500, $res['total_jp']);
        $this->assertLessThan(50, $res['unallocated']);
        $this->assertEquals(35, $res['total_rombel']);
    }

    public function test_run_auto_scheduler_simulation_with_9_jp(): void
    {
        $service = new \App\Services\AutoSchedulerService();
        $res = $service->generate([
            'clear_existing' => true,
            'max_jp_per_sesi' => 9,
        ]);

        $this->assertTrue($res['success']);
        $this->assertGreaterThan(1500, $res['total_jp']);
        $this->assertLessThan(50, $res['unallocated']);
        $this->assertEquals(35, $res['total_rombel']);
    }
}
