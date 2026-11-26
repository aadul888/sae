<?php

namespace Tests\Feature;

use App\Models\PesertaDidik;
use App\Models\SiswaUsulanPerubahan;
use App\Services\KesiswaanQueryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Urutan default daftar usulan perubahan data (tab Perubahan Verval PD):
 * Menunggu -> Ditolak -> Disetujui -> Sudah ke Dapodik.
 * Di dalam grup yang sama, siswa dengan usulan terbaru tampil lebih dulu.
 */
class UsulanPerubahanUrutanTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<int, string> */
    private array $pdIds = [];

    protected function tearDown(): void
    {
        SiswaUsulanPerubahan::whereIn('peserta_didik_id', $this->pdIds)->delete();
        PesertaDidik::whereIn('peserta_didik_id', $this->pdIds)->delete();
        parent::tearDown();
    }

    private function buatSiswa(string $nama): string
    {
        $pdId = 'PD-ORDU-' . random_int(100000, 999999);
        $this->pdIds[] = $pdId;

        PesertaDidik::create([
            'peserta_didik_id' => $pdId,
            'nama' => $nama,
            'nisn' => '666' . random_int(1000000, 9999999),
            'jenis_kelamin' => 'L',
            'nama_rombel' => 'X-ORDU',
        ]);

        return $pdId;
    }

    private function usulan(string $pdId, string $status): void
    {
        SiswaUsulanPerubahan::create([
            'peserta_didik_id' => $pdId,
            'kolom_perubahan' => 'nama_wali',
            'nilai_lama' => '-',
            'nilai_baru' => 'Wali ' . $status,
            'status' => $status,
        ]);
    }

    public function test_urutan_default_mengikuti_prioritas_status(): void
    {
        $menunggu = $this->buatSiswa('Siswa Menunggu');
        $ditolak = $this->buatSiswa('Siswa Ditolak');
        $disetujui = $this->buatSiswa('Siswa Disetujui');
        $dapodik = $this->buatSiswa('Siswa Dapodik');

        $this->usulan($menunggu, 'menunggu');
        $this->usulan($ditolak, 'ditolak');
        $this->usulan($disetujui, 'disetujui');
        $this->usulan($dapodik, 'sudah_ke_dapodik');

        $request = new Request(['perPage' => '100', 'rombel' => 'X-ORDU']);
        $list = app(KesiswaanQueryService::class)->getUsulanTab($request);

        $urutan = collect($list->items())
            ->filter(fn ($r) => in_array($r->peserta_didik_id, $this->pdIds, true))
            ->pluck('peserta_didik_id')
            ->all();

        $this->assertSame([$menunggu, $ditolak, $disetujui, $dapodik], $urutan);
    }
}
