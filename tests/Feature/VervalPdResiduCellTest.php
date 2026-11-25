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

    public function test_status_proses_update_persists_and_logs_history(): void
    {
        $row = $this->makeRow();
        $this->assertSame('belum', $row->fresh()->status_proses);

        $service = app(VervalPdResiduService::class);
        $user = ['nama' => 'Administrator Uji'];

        $res = $service->updateStatus($row->id, VervalPdResiduService::STATUS_PROSES, $user);
        $this->assertSame('success', $res['status']);
        $this->assertSame('proses', $row->fresh()->status_proses);

        $service->updateStatus($row->id, VervalPdResiduService::STATUS_SELESAI, $user);
        $this->assertSame('selesai', $row->fresh()->status_proses);

        $log = AdminAktivitas::where('modul', 'Verval PD Residu')
            ->where('aktivitas', 'like', '%Status Proses%')
            ->latest('id')->first();

        $this->assertNotNull($log, 'Riwayat perubahan status tidak tercatat.');
        $this->assertStringContainsString('Sedang Proses', $log->keterangan);
        $this->assertStringContainsString('Selesai', $log->keterangan);
    }

    public function test_status_proses_invalid_rejected(): void
    {
        $row = $this->makeRow();
        $res = app(VervalPdResiduService::class)->updateStatus($row->id, 'ngawur', ['nama' => 'x']);
        $this->assertSame('error', $res['status']);
        $this->assertSame(422, $res['status_code']);
    }

    public function test_catatan_update_persists_and_logs_history(): void
    {
        $row = $this->makeRow();
        $service = app(VervalPdResiduService::class);
        $user = ['nama' => 'Administrator Uji'];

        $res = $service->updateCatatan($row->id, 'NIK tidak sesuai KK', $user);
        $this->assertSame('success', $res['status']);
        $this->assertSame('NIK tidak sesuai KK', $row->fresh()->catatan);

        $log = AdminAktivitas::where('modul', 'Verval PD Residu')
            ->where('aktivitas', 'like', '%Catatan Masalah%')
            ->latest('id')->first();

        $this->assertNotNull($log, 'Riwayat perubahan catatan tidak tercatat.');
    }

    public function test_cetak_report_renders(): void
    {
        $this->makeRow();
        $admin = ['nama' => 'Administrator Uji', 'role' => 'admin'];

        $res = $this->withSession(['user' => $admin])->get('/dashboard/verval-pd/residu/cetak');

        $res->assertStatus(200);
        $res->assertSee('LAPORAN RESIDU VERVAL PD');
        $res->assertSee('Catatan Masalah');
    }

    public function test_cetak_only_selected_rows(): void
    {
        $keep = $this->makeRow();
        VervalPdResidu::create([
            'import_batch' => 'test-batch',
            'nisn' => '8880002222',
            'nama' => 'Baris Lain',
            'imported_by' => 'tester',
        ]);

        $admin = ['nama' => 'Administrator Uji', 'role' => 'admin'];
        $res = $this->withSession(['user' => $admin])
            ->get('/dashboard/verval-pd/residu/cetak?ids[]=' . $keep->id);

        $res->assertStatus(200);
        $res->assertSee('Uji Residu');
        $res->assertDontSee('Baris Lain');
    }

    public function test_reset_all_konfirmasi_returns_to_belum(): void
    {
        $pdId = 'pd-reset-' . uniqid();
        \Illuminate\Support\Facades\DB::table('peserta_didik_identitas')->insert([
            'peserta_didik_id' => $pdId,
            'nisn' => '7770001111',
            'nama' => 'Siswa Reset',
            'status_konfirmasi' => 'sesuai',
            'dikonfirmasi_pada' => now(),
            'dikonfirmasi_oleh' => 'Tester',
        ]);

        $res = app(\App\Services\KesiswaanBerkasService::class)
            ->resetAllKonfirmasiIdentitas(['nama' => 'Administrator Uji'], 'Verval PD Berkas');

        $this->assertSame('success', $res['status']);
        $this->assertGreaterThanOrEqual(1, $res['affected']);

        $row = \Illuminate\Support\Facades\DB::table('peserta_didik_identitas')
            ->where('peserta_didik_id', $pdId)->first();
        $this->assertSame('belum_konfirmasi', $row->status_konfirmasi);
        $this->assertNull($row->dikonfirmasi_pada);

        $log = AdminAktivitas::where('modul', 'Verval PD Berkas')
            ->where('aktivitas', 'like', '%Reset Semua Konfirmasi%')
            ->latest('id')->first();
        $this->assertNotNull($log, 'Riwayat reset konfirmasi tidak tercatat.');
    }

    public function test_reset_all_konfirmasi_requires_admin(): void
    {
        // Non-admin diblokir middleware permission (redirect / 403), bukan sukses.
        $res = $this->withSession(['user' => ['nama' => 'Guru', 'role' => 'guru']])
            ->post('/dashboard/verval-pd/konfirmasi/reset-all');

        $this->assertContains($res->getStatusCode(), [302, 403]);
    }
}
