<?php

namespace Tests\Feature;

use App\Models\PesertaDidik;
use App\Models\PesertaDidikBerkas;
use App\Services\KesiswaanQueryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Urutan baku daftar validasi berkas = FIFO waktu unggah terakhir.
 *
 * Siswa yang konsisten (tidak mengganti berkas) tetap di atas; siswa yang
 * mengganti/mengunggah berkas baru turun ke bawah. Ini kunci agar petugas
 * memvalidasi dokumen lama lebih dulu.
 */
class BerkasUrutanUnggahTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<int, string> */
    private array $pdIds = [];

    protected function tearDown(): void
    {
        PesertaDidik::whereIn('peserta_didik_id', $this->pdIds)->delete();
        PesertaDidikBerkas::whereIn('peserta_didik_id', $this->pdIds)->delete();
        parent::tearDown();
    }

    private function buatSiswa(string $nama): string
    {
        $pdId = 'PD-ORD-' . random_int(100000, 999999);
        $this->pdIds[] = $pdId;

        PesertaDidik::create([
            'peserta_didik_id' => $pdId,
            'nama' => $nama,
            'nisn' => '777' . random_int(1000000, 9999999),
            'jenis_kelamin' => 'L',
            'nama_rombel' => 'X-ORD',
        ]);

        return $pdId;
    }

    private function unggah(string $pdId, string $uploadedAt): void
    {
        PesertaDidikBerkas::create([
            'peserta_didik_id' => $pdId,
            'jenis_berkas' => 'kartu_keluarga',
            'nama_berkas' => 'Kartu Keluarga (KK)',
            'file_path' => 'berkas_siswa/' . $pdId . '/kk.pdf',
            'file_name' => 'kk.pdf',
            'status' => 'menunggu',
            'uploaded_at' => $uploadedAt,
        ]);
    }

    private function urutanBerkas(): array
    {
        $request = new Request(['perPage' => '100', 'rombel' => 'X-ORD']);
        $list = app(KesiswaanQueryService::class)->getBerkasTab($request);

        return collect($list->items())
            ->filter(fn ($r) => in_array($r->peserta_didik_id, $this->pdIds, true))
            ->pluck('peserta_didik_id')
            ->all();
    }

    public function test_siswa_yang_mengganti_berkas_turun_ke_bawah(): void
    {
        // X unggah tanggal 1, Y unggah tanggal 2 → X di atas (unggah lebih awal).
        $x = $this->buatSiswa('Siswa X');
        $y = $this->buatSiswa('Siswa Y');
        $this->unggah($x, '2026-11-01 08:00:00');
        $this->unggah($y, '2026-11-02 08:00:00');

        $urutan = $this->urutanBerkas();
        $this->assertLessThan(
            array_search($y, $urutan, true),
            array_search($x, $urutan, true),
            'Awalnya X (unggah tanggal 1) harus di atas Y (tanggal 2).'
        );

        // X mengganti berkas di tanggal 3 → X turun, Y jadi paling atas.
        PesertaDidikBerkas::where('peserta_didik_id', $x)
            ->where('jenis_berkas', 'kartu_keluarga')
            ->update(['uploaded_at' => '2026-11-03 08:00:00']);

        $urutan = $this->urutanBerkas();
        $this->assertLessThan(
            array_search($x, $urutan, true),
            array_search($y, $urutan, true),
            'Setelah X ganti berkas, Y (konsisten) harus di atas X.'
        );
    }

    public function test_siswa_tanpa_berkas_berada_paling_bawah(): void
    {
        $ada = $this->buatSiswa('Siswa Ada Berkas');
        $kosong = $this->buatSiswa('Siswa Tanpa Berkas');
        $this->unggah($ada, '2026-11-01 08:00:00');

        $urutan = $this->urutanBerkas();
        $this->assertLessThan(
            array_search($kosong, $urutan, true),
            array_search($ada, $urutan, true),
            'Siswa tanpa berkas harus di bawah siswa yang sudah mengunggah.'
        );
    }
}
