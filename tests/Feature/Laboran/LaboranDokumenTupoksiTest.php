<?php

namespace Tests\Feature\Laboran;

use App\Http\Controllers\Laboran\LaboranJurnalController;
use Illuminate\Support\Facades\DB;

/**
 * M. Dokumen & kegiatan pendukung TUPOKSI laboran:
 *    jurnal kegiatan, kebersihan, dan arsip dokumen.
 */
class LaboranDokumenTupoksiTest extends LaboranTestCase
{
    public function test_halaman_tupoksi_dapat_diakses(): void
    {
        $session = $this->adminSession();

        foreach ([
            'dashboard.laboran.arsip.index',
            'dashboard.laboran.jurnal.index',
            'dashboard.laboran.kebersihan.index',
        ] as $name) {
            $res = $this->withSession($session)->get(route($name));
            $this->assertSame(200, $res->getStatusCode(), "Halaman {$name} gagal dimuat.");
        }
    }

    public function test_jurnal_menghitung_durasi_dan_kategori_tupoksi(): void
    {
        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jurnal.store'), [
                'tanggal'     => now()->toDateString(),
                'kegiatan'    => 'Menyiapkan alat praktik',
                'kategori'    => 'penyiapan',
                'jam_mulai'   => '07:00',
                'jam_selesai' => '09:00',
                'status'      => 'selesai',
            ])
            ->assertStatus(200);

        $row = DB::table('laboran_jurnal')->latest('id')->first();
        $this->assertNotNull($row);
        $this->assertSame(120, (int) $row->durasi_menit, 'Durasi harus 120 menit (07:00-09:00).');
        $this->assertSame('penyiapan', $row->kategori);

        // Kategori terpetakan ke indikator tupoksi
        $kategori = LaboranJurnalController::KATEGORI;
        $this->assertSame(2, $kategori['penyiapan'][1], 'Kategori penyiapan harus terpetakan ke Tupoksi #2.');
        $this->assertSame(1, $kategori['administrasi'][1]);
        $this->assertSame(9, $kategori['kebersihan'][1]);
    }

    public function test_kebersihan_menyimpan_dokumentasi(): void
    {
        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.kebersihan.store'), [
                'tanggal_kegiatan'  => now()->toDateString(),
                'area'              => 'Lahan Praktik ATPH',
                'jenis_kegiatan'    => 'harian',
                'uraian_kegiatan'   => 'Membersihkan gulma',
                'kondisi_akhir'     => 'bersih',
                'jumlah_petugas'    => 3,
                'dokumentasi_paths' => ['storage/lab/foto1.jpg', 'storage/lab/foto2.jpg'],
            ])
            ->assertStatus(200);

        $row = DB::table('laboran_kebersihan')->latest('id')->first();
        $this->assertNotNull($row);
        $this->assertStringStartsWith('KBR/', $row->nomor_kegiatan);

        $paths = json_decode($row->dokumentasi_paths, true);
        $this->assertCount(2, $paths);
        $this->assertContains('storage/lab/foto1.jpg', $paths);
    }

    public function test_arsip_massal_menarik_dokumen_dari_modul_lain(): void
    {
        // Buat 1 BA kerusakan sebagai sumber arsip
        DB::table('laboran_berita_acara_kerusakan')->insert([
            'jurusan_id'        => self::JUR_ATPH,
            'nomor_ba'          => 'BAK/ARSIP/' . uniqid(),
            'nama_alat'         => 'Alat Arsip Uji',
            'tanggal_kejadian'  => now()->toDateString(),
            'tanggal_ba'        => now()->toDateString(),
            'tingkat_kerusakan' => 'ringan',
            'jumlah_rusak'      => 1,
            'satuan'            => 'unit',
            'status'            => 'diajukan',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.arsip.arsipkan-massal'), [
                'kategori' => 'berita_acara_kerusakan',
            ])
            ->assertStatus(200);

        $this->assertGreaterThan(0, DB::table('laboran_arsip')
            ->where('kategori', 'berita_acara_kerusakan')
            ->count());

        $arsip = DB::table('laboran_arsip')->where('kategori', 'berita_acara_kerusakan')->first();
        $this->assertStringStartsWith('ARS/', $arsip->kode_arsip);
        $this->assertSame('laboran_berita_acara_kerusakan', $arsip->sumber_tabel);
    }
}
