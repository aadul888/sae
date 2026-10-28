<?php

namespace Tests\Feature;

use App\Services\JadwalPengaturanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Dropdown "Mata Pelajaran & Guru Pengampu" pada modal Isi Jadwal harus
 * menyembunyikan mapel yang kuota JJM-nya sudah terpakai penuh.
 *
 * Perhitungan kuota: `pembelajaran.jam_mengajar_per_minggu` dikurangi total JP
 * yang sudah terjadwal aktif pada `jadwal_kbm` (sum jam_ke_selesai - jam_ke_mulai + 1).
 *
 * Pengujian memakai ROMBEL SEMENTARA + PEMBELAJARAN SEMENTARA agar tidak
 * bergantung data nyata, lalu seluruh barisnya dihapus di tearDown.
 */
class JadwalKbmSisaJpTest extends TestCase
{
    private string $rombelId = '';
    private string $mapelPenuhId = '';
    private string $mapelSisaId = '';
    private string $mapelTanpaKuotaId = '';

    protected function tearDown(): void
    {
        DB::table('jadwal_kbm')->whereIn('pembelajaran_id', [
            $this->mapelPenuhId,
            $this->mapelSisaId,
            $this->mapelTanpaKuotaId,
        ])->delete();

        DB::table('pembelajaran')->whereIn('pembelajaran_id', [
            $this->mapelPenuhId,
            $this->mapelSisaId,
            $this->mapelTanpaKuotaId,
        ])->delete();

        if ($this->rombelId !== '') {
            DB::table('rombongan_belajar')->where('rombongan_belajar_id', $this->rombelId)->delete();
        }

        parent::tearDown();
    }

    /**
     * Siapkan rombel + tiga pembelajaran dengan kondisi kuota berbeda.
     */
    private function seedFixtures(): void
    {
        $tag = uniqid();
        $this->rombelId = (string) \Illuminate\Support\Str::uuid();

        DB::table('rombongan_belajar')->insert([
            'rombongan_belajar_id' => $this->rombelId,
            'nama'                 => 'UJI-SISAJP-' . $tag,
            'jenis_rombel'         => 1,
        ]);

        $buat = function (string $suffix, int $kuota) use ($tag): string {
            $id = 'UJI-' . $tag . '-' . $suffix;
            DB::table('pembelajaran')->insert([
                'pembelajaran_id'        => $id,
                'rombongan_belajar_id'   => $this->rombelId,
                'nama_mata_pelajaran'    => 'Mapel ' . $suffix . ' ' . $tag,
                'mata_pelajaran_id_str'  => 'Mapel ' . $suffix,
                'jam_mengajar_per_minggu' => $kuota,
            ]);

            return $id;
        };

        $this->mapelPenuhId      = $buat('PENUH', 4);
        $this->mapelSisaId       = $buat('SISA', 4);
        $this->mapelTanpaKuotaId = $buat('NOL', 0);

        // Mapel SISA baru terpakai 1 JP dari 4.
        $this->jadwalkan($this->mapelSisaId, 1, 1);
        // Mapel PENUH terpakai tepat 4 JP (2 baris @ 2 JP).
        $this->jadwalkan($this->mapelPenuhId, 1, 2);
        $this->jadwalkan($this->mapelPenuhId, 3, 4);
    }

    private function jadwalkan(string $pembelajaranId, int $jamKeMulai, int $jamKeSelesai): void
    {
        DB::table('jadwal_kbm')->insert([
            'rombongan_belajar_id'  => $this->rombelId,
            'pembelajaran_id'       => $pembelajaranId,
            'nama_mata_pelajaran'   => 'Mapel Uji',
            'hari'                  => 'Senin',
            'jam_ke_mulai'          => $jamKeMulai,
            'jam_ke_selesai'        => $jamKeSelesai,
            'jam_mulai'             => '07:15:00',
            'jam_selesai'           => '08:45:00',
            'is_active'             => true,
        ]);
    }

    private function ambil(): \Illuminate\Support\Collection
    {
        $res = app(JadwalPengaturanService::class)->getPembelajaranByRombel(
            Request::create('/uji', 'GET', ['rombel_id' => $this->rombelId])
        );

        return collect($res['pembelajaran'])->keyBy('pembelajaran_id');
    }

    public function test_mapel_dengan_kuota_terpenuhi_ditandai_penuh(): void
    {
        $this->seedFixtures();

        $items = $this->ambil();

        $penuh = $items[$this->mapelPenuhId];
        $this->assertSame(4, $penuh->terjadwal_jp, 'JP terjadwal harus dijumlahkan per baris.');
        $this->assertSame(0, $penuh->sisa_jp);
        $this->assertTrue($penuh->sudah_penuh);
    }

    public function test_mapel_dengan_sisa_kuota_melaporkan_sisa_jp(): void
    {
        $this->seedFixtures();

        $items = $this->ambil();

        $sisa = $items[$this->mapelSisaId];
        $this->assertSame(1, $sisa->terjadwal_jp);
        $this->assertSame(3, $sisa->sisa_jp, 'Sisa JP = kuota 4 dikurangi 1 JP terpakai.');
        $this->assertFalse($sisa->sudah_penuh);
    }

    public function test_kuota_nol_tidak_dianggap_penuh(): void
    {
        $this->seedFixtures();

        $items = $this->ambil();

        $nol = $items[$this->mapelTanpaKuotaId];
        $this->assertNull($nol->sisa_jp, 'JJM 0 berarti belum diisi Dapodik, bukan penuh.');
        $this->assertFalse($nol->sudah_penuh);
    }

    public function test_mapel_hanya_dihitung_pada_jadwal_aktif(): void
    {
        $this->seedFixtures();

        // Baris non-aktif tidak boleh menambah hitungan JP terpakai.
        DB::table('jadwal_kbm')->insert([
            'rombongan_belajar_id'  => $this->rombelId,
            'pembelajaran_id'       => $this->mapelSisaId,
            'nama_mata_pelajaran'   => 'Mapel Uji Nonaktif',
            'hari'                  => 'Selasa',
            'jam_ke_mulai'          => 1,
            'jam_ke_selesai'        => 4,
            'jam_mulai'             => '07:15:00',
            'jam_selesai'           => '10:00:00',
            'is_active'             => false,
        ]);

        $items = $this->ambil();

        $this->assertSame(1, $items[$this->mapelSisaId]->terjadwal_jp);
        $this->assertSame(3, $items[$this->mapelSisaId]->sisa_jp);
    }
}
