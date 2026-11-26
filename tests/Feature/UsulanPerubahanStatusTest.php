<?php

namespace Tests\Feature;

use App\Models\PesertaDidik;
use App\Models\SiswaUsulanPerubahan;
use App\Services\KesiswaanUsulanService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Status agregat + urutan butir usulan perubahan data (Verval PD).
 *
 * - 1 field ditolak menandai seluruh pengajuan 'ditolak' (walau ada yang disetujui).
 * - Setelah field disetujui diinput ke Dapodik, status jadi 'sudah_ke_dapodik'
 *   (selesai), bukan balik ke 'menunggu'.
 * - Butir usulan disusun mengikuti urutan bagian formulir identitas agar operator
 *   tidak bolak-balik antar-bagian saat input ke Dapodik.
 */
class UsulanPerubahanStatusTest extends TestCase
{
    use DatabaseTransactions;

    private string $pdId = '';

    protected function tearDown(): void
    {
        if ($this->pdId !== '') {
            SiswaUsulanPerubahan::where('peserta_didik_id', $this->pdId)->delete();
            PesertaDidik::where('peserta_didik_id', $this->pdId)->delete();
        }
        parent::tearDown();
    }

    private function buatSiswa(): string
    {
        $this->pdId = 'PD-STAT-' . random_int(100000, 999999);
        PesertaDidik::create([
            'peserta_didik_id' => $this->pdId,
            'nama' => 'Uji Status',
            'nisn' => '777' . random_int(1000000, 9999999),
            'jenis_kelamin' => 'L',
            'nama_rombel' => 'X-STAT',
        ]);

        return $this->pdId;
    }

    private function usulan(string $kolom, string $status): void
    {
        SiswaUsulanPerubahan::create([
            'peserta_didik_id' => $this->pdId,
            'kolom_perubahan' => $kolom,
            'nilai_lama' => '-',
            'nilai_baru' => 'baru',
            'status' => $status,
        ]);
    }

    public function test_agregat_status_aturan_prioritas(): void
    {
        $f = [SiswaUsulanPerubahan::class, 'agregatStatus'];

        $this->assertSame('menunggu', $f(['menunggu', 'disetujui']), 'Ada menunggu → menunggu.');
        $this->assertSame('ditolak', $f(['ditolak', 'disetujui']), '1 ditolak + disetujui mentah → ditolak.');
        $this->assertSame('disetujui', $f(['disetujui', 'disetujui']), 'Semua disetujui → siap Dapodik.');
        $this->assertSame('ditolak', $f(['ditolak']), 'Ditolak saja (pra-Dapodik) → ditolak.');
        $this->assertSame('sudah_ke_dapodik', $f(['sudah_ke_dapodik', 'sudah_ke_dapodik']), 'Semua ke Dapodik → selesai.');
        $this->assertSame(
            'sudah_ke_dapodik',
            $f(['sudah_ke_dapodik', 'sudah_ke_dapodik', 'ditolak']),
            '4 disetujui masuk Dapodik + 1 ditolak → selesai (bukan menunggu).'
        );
    }

    public function test_status_ditolak_lalu_selesai_setelah_masuk_dapodik(): void
    {
        $this->buatSiswa();
        $this->usulan('nama', 'disetujui');
        $this->usulan('tempat_lahir', 'disetujui');
        $this->usulan('nik', 'ditolak');

        $svc = app(KesiswaanUsulanService::class);

        $sebelum = $svc->getUsulanDetail($this->pdId);
        $this->assertSame('ditolak', $sebelum['data']['status'], '1 ditolak + disetujui → ditolak dulu.');

        $svc->markDapodikUpdated($this->pdId, ['nama' => 'Admin Uji']);

        $sesudah = $svc->getUsulanDetail($this->pdId);
        $this->assertSame(
            'sudah_ke_dapodik',
            $sesudah['data']['status'],
            'Setelah disetujui masuk Dapodik → selesai, bukan menunggu lagi.'
        );
    }

    public function test_butir_usulan_diurutkan_ikut_formulir(): void
    {
        $this->buatSiswa();
        // Sengaja dibuat tidak berurutan (Ibu sebelum Ayah, kontak sebelum pribadi).
        $this->usulan('penghasilan_ibu_id', 'menunggu');
        $this->usulan('nama_ayah', 'menunggu');
        $this->usulan('nomor_telepon_seluler', 'menunggu');
        $this->usulan('nama', 'menunggu');
        $this->usulan('tempat_lahir', 'menunggu');

        $res = app(KesiswaanUsulanService::class)->getUsulanDetail($this->pdId);
        $urutan = array_column($res['data']['items'], 'kolom_perubahan');

        $this->assertSame(
            ['nama', 'tempat_lahir', 'nama_ayah', 'penghasilan_ibu_id', 'nomor_telepon_seluler'],
            $urutan
        );
    }
}
