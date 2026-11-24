<?php

namespace Tests\Feature;

use App\Models\AdminAktivitas;
use App\Models\VervalPdResidu;
use App\Services\VervalPdResiduService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Uji ubah sel Residu Verval PD (centang ✓/✗) + pencatatan riwayat.
 * Perubahan berkala harus tetap terekam walau data berbalik arah.
 */
class VervalPdResiduCellTest extends TestCase
{
    use DatabaseTransactions;

    private function makeRow(): VervalPdResidu
    {
        return VervalPdResidu::create([
            'import_batch' => 'test-batch',
            'nisn' => '9990001111',
            'nama' => 'Uji Residu',
            'rombel_sekolah' => '✓',
            'nama_banding' => '✓',
            'desa_kelurahan' => '✗',
            'imported_by' => 'tester',
        ]);
    }

    public function test_cell_update_persists_and_logs_history(): void
    {
        $row = $this->makeRow();
        $service = app(VervalPdResiduService::class);
        $user = ['nama' => 'Administrator Uji'];

        $res = $service->updateCell($row->id, 'desa_kelurahan', '✓', $user);
        $this->assertSame('success', $res['status']);
        $this->assertSame('✓', $row->fresh()->desa_kelurahan);

        $log = AdminAktivitas::where('modul', 'Verval PD Residu')
            ->where('aktivitas', 'like', '[UPDATE]%')
            ->latest('id')->first();

        $this->assertNotNull($log, 'Riwayat perubahan tidak tercatat.');
        $this->assertStringContainsString('Desa/Kelurahan', $log->aktivitas);
        $this->assertStringContainsString('Tidak Sesuai', $log->keterangan);
        $this->assertStringContainsString('Sesuai', $log->keterangan);
    }

    public function test_reversal_is_logged_separately(): void
    {
        $row = $this->makeRow();
        $service = app(VervalPdResiduService::class);
        $user = ['nama' => 'Administrator Uji'];

        // ✓ → ✗ (bulan berikutnya jadi tidak sesuai lagi)
        $service->updateCell($row->id, 'nama_banding', '✗', $user);
        // ✗ → ✓ (kembali sesuai)
        $service->updateCell($row->id, 'nama_banding', '✓', $user);

        $logs = AdminAktivitas::where('modul', 'Verval PD Residu')
            ->where('aktivitas', 'like', '%Nama (Dukcapil)%')
            ->where('aktivitas', 'like', '%9990001111%')
            ->orderBy('id')->get();

        $this->assertCount(2, $logs, 'Perubahan berbalik arah harus tercatat dua kali.');
        $this->assertStringContainsString('Sesuai (✓) → Tidak Sesuai (✗)', $logs[0]->keterangan);
        $this->assertStringContainsString('Tidak Sesuai (✗) → Sesuai (✓)', $logs[1]->keterangan);
    }

    public function test_unknown_column_rejected(): void
    {
        $row = $this->makeRow();
        $res = app(VervalPdResiduService::class)->updateCell($row->id, 'nisn', '999', ['nama' => 'x']);
        $this->assertSame('error', $res['status']);
        $this->assertSame(422, $res['status_code']);
    }
}
