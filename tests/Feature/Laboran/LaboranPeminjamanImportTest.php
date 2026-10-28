<?php

namespace Tests\Feature\Laboran;

use App\Http\Controllers\Laboran\LaboranPeminjamanController;
use App\Services\SimpleXlsxReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Import Excel modul Peminjaman Alat (satu baris = satu item, digabung per nomor_pinjam).
 */
class LaboranPeminjamanImportTest extends LaboranTestCase
{
    private function makeCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pjm') . '.csv';
        file_put_contents($path, $content);

        return $path;
    }

    public function test_template_peminjaman_dapat_dibaca_kembali_oleh_reader(): void
    {
        $rows = SimpleXlsxReader::readFile(
            $this->templateFilePath(new LaboranPeminjamanController())
        );

        $this->assertNotEmpty($rows, 'Template peminjaman harus menghasilkan baris data.');
        $this->assertArrayHasKey('nomor_pinjam', $rows[0], 'Header nomor_pinjam tidak terdeteksi pada template.');
        $this->assertSame('Budi Santoso', $rows[0]['peminjam_nama']);
        $this->assertSame('Switch Cisco Catalyst 2960', $rows[0]['nama_item']);
    }

    public function test_tombol_import_tersedia_di_halaman_peminjaman(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.peminjaman-alat.index'));

        $res->assertStatus(200);
        $res->assertSee('btnOpenImportPeminjaman', false);
        $res->assertSee(route('dashboard.laboran.peminjaman-alat.import'), false);
    }

    public function test_import_peminjaman_menggabungkan_baris_per_nomor_pinjam(): void
    {
        [, $itemId] = $this->seedItems();
        $kode = DB::table('laboran_bahan_alat')->where('id', $itemId)->value('kode_item');

        $csv = "nomor_pinjam,peminjam_tipe,peminjam_nama,peminjam_kelas,keperluan,ruang_lab_nama,"
            . "tanggal_pinjam,tanggal_kembali_rencana,status,kode_item,nama_item,jumlah,satuan\n"
            . "PJM-UJI-01,siswa,Budi Import,XI TKJ 1,Praktik jaringan,Lab Komputer 1,"
            . "2026-10-01,2026-10-04,dipinjam,{$kode},Switch Cisco Uji,2,unit\n"
            . "PJM-UJI-01,siswa,Budi Import,XI TKJ 1,Praktik jaringan,Lab Komputer 1,"
            . "2026-10-01,2026-10-04,dipinjam,,Kabel UTP Patch,4,buah\n";

        $file = $this->makeCsv($csv);

        $this->withSession($this->adminSession())
            ->post(route('dashboard.laboran.peminjaman-alat.import'), [
                'file' => new UploadedFile($file, 'peminjaman.csv', 'text/csv', null, true),
            ])
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $pinjam = DB::table('laboran_peminjaman')->where('nomor_pinjam', 'PJM-UJI-01')->first();
        $this->assertNotNull($pinjam, 'Dokumen peminjaman hasil import tidak tersimpan.');
        $this->assertSame('dipinjam', $pinjam->status);
        $this->assertSame('Budi Import', $pinjam->peminjam_nama);
        $this->assertSame(2, (int) $pinjam->jumlah_item, 'Dua baris dengan nomor sama harus menjadi satu dokumen.');

        $items = DB::table('laboran_peminjaman_item')->where('peminjaman_id', $pinjam->id)->get();
        $this->assertCount(2, $items);

        // Baris dengan kode_item cocok harus tertaut ke inventaris
        $tertaut = $items->firstWhere('item_id', $itemId);
        $this->assertNotNull($tertaut, 'Item dengan kode inventaris valid harus tertaut ke laboran_bahan_alat.');

        // Status dipinjam -> stok tersedia berkurang
        $this->assertEquals(3, (float) DB::table('laboran_bahan_alat')->where('id', $itemId)->value('stok_tersedia'));

        @unlink($file);
    }

    public function test_import_peminjaman_membuat_nomor_otomatis(): void
    {
        $csv = "peminjam_tipe,peminjam_nama,keperluan,tanggal_pinjam,tanggal_kembali_rencana,status,nama_item,jumlah\n"
            . "guru,Siti Import,Persiapan praktik,2026-10-02,2026-10-09,diajukan,Mikroskop Uji,1\n";

        $file = $this->makeCsv($csv);

        $this->withSession($this->adminSession())
            ->post(route('dashboard.laboran.peminjaman-alat.import'), [
                'file' => new UploadedFile($file, 'peminjaman.csv', 'text/csv', null, true),
            ])
            ->assertStatus(200);

        $row = DB::table('laboran_peminjaman')->where('peminjam_nama', 'Siti Import')->first();
        $this->assertNotNull($row);
        $this->assertMatchesRegularExpression('#^PJM/\d{6}/\d{4}$#', $row->nomor_pinjam);

        // Item tanpa kode tetap tercatat sebagai item bebas
        $item = DB::table('laboran_peminjaman_item')->where('peminjaman_id', $row->id)->first();
        $this->assertNotNull($item);
        $this->assertNull($item->item_id);
        $this->assertSame('Mikroskop Uji', $item->nama_item);

        @unlink($file);
    }

    public function test_import_peminjaman_menolak_berkas_kosong(): void
    {
        $file = $this->makeCsv("peminjam_nama,nama_item\n");

        $this->withSession($this->adminSession())
            ->post(route('dashboard.laboran.peminjaman-alat.import'), [
                'file' => new UploadedFile($file, 'kosong.csv', 'text/csv', null, true),
            ])
            ->assertStatus(422);

        @unlink($file);
    }
}
