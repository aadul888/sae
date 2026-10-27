<?php

namespace Tests\Feature\Laboran;

use Illuminate\Support\Facades\DB;

/**
 * I. Laporan Lengkap (penggunaan & kerusakan) untuk semua tipe periode.
 */
class LaboranLaporanTest extends LaboranTestCase
{
    public function test_laporan_lengkap_merender_semua_periode(): void
    {
        $queries = [
            ['periode' => 'bulan', 'bulan' => 10, 'tahun' => 2026],
            ['periode' => 'triwulan', 'triwulan' => 4, 'tahun' => 2026],
            ['periode' => 'semester', 'semester' => '1', 'tahun_ajaran' => '2026/2027'],
            ['periode' => 'tahun', 'tahun_ajaran' => '2026/2027'],
        ];

        foreach ($queries as $q) {
            $res = $this->withSession($this->adminSession())
                ->get(route('dashboard.laboran.laporan-lengkap.index', $q));

            $res->assertStatus(200);
            $res->assertViewHas('range');
            $res->assertViewHas('rekapLokasi');
            $res->assertViewHas('rekapTingkat');
        }
    }

    public function test_cetak_laporan_lengkap_menghasilkan_dokumen(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.laporan-lengkap.cetak', [
                'periode' => 'bulan', 'bulan' => 10, 'tahun' => 2026,
            ]));

        $res->assertStatus(200);
        $res->assertSee('LAPORAN PENGGUNAAN', false);
        $res->assertSee('Rekapitulasi Kerusakan Alat', false);
    }

    /**
     * Pengujian hitungan kerusakan memakai periode Februari 2027 yang bebas
     * data nyata (baris produksi mencatat kejadian Okt/Nov 2026), sehingga
     * snapshot cleanup tidak memengaruhi asersi.
     */
    public function test_laporan_menghitung_kerusakan_dalam_periode(): void
    {
        $this->seedItems();

        DB::table('laboran_berita_acara_kerusakan')->insert([
            [
                'jurusan_id'        => self::JUR_ATPH,
                'nomor_ba'          => 'BAK/TEST/0001',
                'nama_alat'         => 'Cangkul Uji',
                'tanggal_kejadian'  => '2027-02-15',
                'tanggal_ba'        => '2027-02-15',
                'tingkat_kerusakan' => 'berat',
                'jumlah_rusak'      => 1,
                'satuan'            => 'unit',
                'estimasi_biaya'    => 500000,
                'status'            => 'diajukan',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'jurusan_id'        => self::JUR_ATPH,
                'nomor_ba'          => 'BAK/TEST/0002',
                'nama_alat'         => 'Cangkul Uji',
                'tanggal_kejadian'  => '2027-03-20',
                'tanggal_ba'        => '2027-03-20',
                'tingkat_kerusakan' => 'ringan',
                'jumlah_rusak'      => 1,
                'satuan'            => 'unit',
                'estimasi_biaya'    => 0,
                'status'            => 'diajukan',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ]);

        $februari = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.laporan-lengkap.index', ['periode' => 'bulan', 'bulan' => 2, 'tahun' => 2027]));

        $this->assertSame(1, $februari->viewData('totalKerusakan'));
        $this->assertSame(1, $februari->viewData('totalRusakBerat'));

        $triwulan1 = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.laporan-lengkap.index', ['periode' => 'triwulan', 'triwulan' => 1, 'tahun' => 2027]));

        $this->assertSame(2, $triwulan1->viewData('totalKerusakan'));
    }
}
