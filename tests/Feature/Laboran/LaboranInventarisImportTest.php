<?php

namespace Tests\Feature\Laboran;

use App\Http\Controllers\LaboranInventarisController;
use App\Http\Controllers\SarprasAsetController;
use App\Services\SimpleXlsxReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * L. Inventaris: import Excel, kode item otomatis, dan rincian kondisi per jumlah.
 */
class LaboranInventarisImportTest extends LaboranTestCase
{
    public function test_import_excel_inventaris_dan_aset(): void
    {
        $session = $this->adminSession();

        // 1. Template download inventaris & aset
        $this->withSession($session)->get(route('dashboard.laboran.inventaris.template'))->assertStatus(200);
        $this->withSession($session)->get(route('dashboard.sarpras.aset.template'))->assertStatus(200);

        // 2. Import inventaris dengan CSV dummy
        $tempFile = $this->makeCsv(
            "kode_item,nama_item,jenis,stok_total,satuan,kondisi,ruang_lab_nama\n"
            . "IMP-001,Obeng Set Uji,alat,5,set,baik,Lab Komputer 1\n"
        );

        $this->withSession($session)
            ->post(route('dashboard.laboran.inventaris.import'), [
                'file' => new UploadedFile($tempFile, 'import.csv', 'text/csv', null, true),
            ])
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertNotNull(DB::table('laboran_bahan_alat')->where('kode_item', 'IMP-001')->first());

        @unlink($tempFile);
    }

    /**
     * REGRESI: template resmi (.xlsx) harus dapat dibaca kembali oleh import.
     * Sebelumnya header hanya dikenali untuk kolom Dapodik, sehingga template
     * SAE (inventaris/aset) gagal diparse dan unggahan menggantung tanpa pesan.
     */
    public function test_template_inventaris_dapat_dibaca_kembali_oleh_reader(): void
    {
        $rows = SimpleXlsxReader::readFile(
            $this->templateFilePath(new LaboranInventarisController())
        );

        $this->assertNotEmpty($rows, 'Template inventaris harus menghasilkan baris data.');
        $this->assertArrayHasKey('nama_item', $rows[0], 'Header nama_item tidak terdeteksi pada template.');
        $this->assertSame('Mikroskop Binokuler Digital', $rows[0]['nama_item']);
        $this->assertSame('5', (string) $rows[0]['stok_total']);
    }

    public function test_template_aset_dapat_dibaca_kembali_oleh_reader(): void
    {
        $rows = SimpleXlsxReader::readFile(
            $this->templateFilePath(new SarprasAsetController())
        );

        $this->assertNotEmpty($rows, 'Template aset harus menghasilkan baris data.');
        $this->assertArrayHasKey('nama_barang', $rows[0], 'Header nama_barang tidak terdeteksi pada template.');
        $this->assertSame('Proyektor Epson EB-X500', $rows[0]['nama_barang']);
    }

    public function test_import_inventaris_membuat_kode_otomatis_dan_rincian_kondisi(): void
    {
        $session = $this->adminSession();

        // Kode item KOSONG + rincian kondisi 10 unit (5 baik, 3 rusak ringan, 2 rusak berat)
        $tempFile = $this->makeCsv(
            "kode_item,nama_item,jenis,stok_total,satuan,kondisi,jumlah_baik,jumlah_rusak_ringan,jumlah_rusak_berat,jumlah_kedaluwarsa,ruang_lab_nama\n"
            . ",Traktor Uji Otomatis,alat,10,unit,baik,5,3,2,0,Lahan Praktik ATPH\n"
        );

        $this->withSession($session)
            ->post(route('dashboard.laboran.inventaris.import'), [
                'file' => new UploadedFile($tempFile, 'import.csv', 'text/csv', null, true),
            ])
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $row = DB::table('laboran_bahan_alat')->where('nama_item', 'Traktor Uji Otomatis')->first();
        $this->assertNotNull($row, 'Item hasil import tidak tersimpan.');

        // Kode otomatis harus terisi (tidak boleh kosong)
        $this->assertNotEmpty($row->kode_item, 'Kode item wajib dibuat otomatis saat dikosongkan.');
        $this->assertMatchesRegularExpression('/^ALT-\d{6}-\d{4}$/', $row->kode_item);

        // Rincian kondisi tersimpan sesuai jumlah
        $this->assertEquals(5, (float) $row->jumlah_baik);
        $this->assertEquals(3, (float) $row->jumlah_rusak_ringan);
        $this->assertEquals(2, (float) $row->jumlah_rusak_berat);
        $this->assertEquals(0, (float) $row->jumlah_kedaluwarsa);
        $this->assertSame('baik', $row->kondisi, 'Kondisi dominan harus mengikuti jumlah terbanyak.');

        @unlink($tempFile);
    }

    public function test_simpan_inventaris_tanpa_kode_membuat_kode_otomatis(): void
    {
        $this->withSession($this->adminSession())->postJson(route('dashboard.laboran.inventaris.store'), [
            'ruang_lab_nama'      => 'Lab Komputer 1',
            'kode_item'           => '',
            'nama_item'           => 'Item Tanpa Kode Uji',
            'jenis'               => 'alat',
            'stok_total'          => 10,
            'satuan'              => 'unit',
            'kondisi'             => 'baik',
            'jumlah_baik'         => 6,
            'jumlah_rusak_ringan' => 2,
            'jumlah_rusak_berat'  => 2,
        ])->assertStatus(200);

        $row = DB::table('laboran_bahan_alat')->where('nama_item', 'Item Tanpa Kode Uji')->first();
        $this->assertNotNull($row);
        $this->assertNotEmpty($row->kode_item);
        $this->assertMatchesRegularExpression('/^ALT-\d{6}-\d{4}$/', $row->kode_item);
        $this->assertEquals(6, (float) $row->jumlah_baik);
        $this->assertEquals(2, (float) $row->jumlah_rusak_ringan);
        $this->assertEquals(2, (float) $row->jumlah_rusak_berat);
    }

    /**
     * Data pengadaan: tanggal & tahun perolehan tersimpan, dan tahun
     * diturunkan otomatis dari tanggal bila tidak diisi.
     */
    public function test_inventaris_menyimpan_tanggal_dan_tahun_pengadaan(): void
    {
        $session = $this->adminSession();

        // Tahun diturunkan dari tanggal
        $this->withSession($session)->postJson(route('dashboard.laboran.inventaris.store'), [
            'ruang_lab_nama'    => 'Lab Komputer 1',
            'kode_item'         => 'UJI-PGD-' . uniqid(),
            'nama_item'         => 'Item Uji Pengadaan A',
            'jenis'             => 'alat',
            'stok_total'        => 3,
            'satuan'            => 'unit',
            'kondisi'           => 'baik',
            'tanggal_pengadaan' => '2024-03-15',
        ])->assertStatus(200);

        $a = DB::table('laboran_bahan_alat')->where('nama_item', 'Item Uji Pengadaan A')->first();
        $this->assertNotNull($a);
        $this->assertSame('2024-03-15', $a->tanggal_pengadaan);
        $this->assertSame(2024, (int) $a->tahun_pengadaan, 'Tahun harus diturunkan dari tanggal pengadaan.');

        // Tahun eksplisit dipakai apa adanya
        $this->withSession($session)->postJson(route('dashboard.laboran.inventaris.store'), [
            'ruang_lab_nama'    => 'Lab Komputer 1',
            'kode_item'         => 'UJI-PGD-' . uniqid(),
            'nama_item'         => 'Item Uji Pengadaan B',
            'jenis'             => 'alat',
            'stok_total'        => 1,
            'satuan'            => 'unit',
            'kondisi'           => 'baik',
            'tahun_pengadaan'   => 2021,
        ])->assertStatus(200);

        $b = DB::table('laboran_bahan_alat')->where('nama_item', 'Item Uji Pengadaan B')->first();
        $this->assertSame(2021, (int) $b->tahun_pengadaan);
    }

    /**
     * Tulis konten CSV ke berkas sementara dan kembalikan path-nya.
     */
    private function makeCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'inv') . '.csv';
        file_put_contents($path, $content);

        return $path;
    }
}
