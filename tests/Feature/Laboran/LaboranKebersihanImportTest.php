<?php

namespace Tests\Feature\Laboran;

use App\Http\Controllers\Laboran\LaboranKebersihanController;
use App\Services\SimpleXlsxReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Import Excel modul Kebersihan Lingkungan Produktif Jurusan.
 */
class LaboranKebersihanImportTest extends LaboranTestCase
{
    private function makeCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'kbr') . '.csv';
        file_put_contents($path, $content);

        return $path;
    }

    public function test_template_kebersihan_dapat_dibaca_kembali_oleh_reader(): void
    {
        $rows = SimpleXlsxReader::readFile(
            $this->templateFilePath(new LaboranKebersihanController())
        );

        $this->assertNotEmpty($rows, 'Template kebersihan harus menghasilkan baris data.');
        $this->assertArrayHasKey('area', $rows[0], 'Header area tidak terdeteksi pada template.');
        $this->assertSame('Lahan Praktik ATPH', $rows[0]['area']);
        $this->assertSame('harian', $rows[0]['jenis_kegiatan']);
    }

    public function test_tombol_import_tersedia_di_halaman_kebersihan(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.kebersihan.index'));

        $res->assertStatus(200);
        $res->assertSee('btnOpenImportKebersihan', false);
        $res->assertSee(route('dashboard.laboran.kebersihan.import'), false);
    }

    public function test_import_kebersihan_membuat_nomor_otomatis_dan_dokumentasi(): void
    {
        $csv = "tanggal_kegiatan,area,ruang_lab_nama,jenis_kegiatan,uraian_kegiatan,"
            . "jumlah_petugas,nama_petugas,kondisi_akhir,temuan,dokumentasi_paths,status\n"
            . "2026-10-01,Lahan Praktik ATPH,Lahan Praktik ATPH,harian,Membersihkan gulma,"
            . "5,Budi; Andi,bersih,-,storage/lab/foto1.jpg|storage/lab/foto2.jpg,selesai\n";

        $file = $this->makeCsv($csv);

        $this->withSession($this->adminSession())
            ->post(route('dashboard.laboran.kebersihan.import'), [
                'file' => new UploadedFile($file, 'kebersihan.csv', 'text/csv', null, true),
            ])
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $row = DB::table('laboran_kebersihan')->where('area', 'Lahan Praktik ATPH')->first();
        $this->assertNotNull($row, 'Catatan kebersihan hasil import tidak tersimpan.');
        $this->assertMatchesRegularExpression('#^KBR/\d{6}/\d{4}$#', $row->nomor_kegiatan);
        $this->assertSame('2026-10-01', $row->tanggal_kegiatan);
        $this->assertSame(5, (int) $row->jumlah_petugas);
        $this->assertSame('bersih', $row->kondisi_akhir);

        $paths = json_decode($row->dokumentasi_paths, true);
        $this->assertCount(2, $paths);
        $this->assertContains('storage/lab/foto1.jpg', $paths);

        @unlink($file);
    }

    public function test_import_kebersihan_memakai_nomor_dari_berkas_untuk_update(): void
    {
        DB::table('laboran_kebersihan')->insert([
            'jurusan_id'       => self::JUR_ATPH,
            'nomor_kegiatan'   => 'KBR/UJI/0001',
            'tanggal_kegiatan' => '2026-10-01',
            'area'             => 'Bengkel Otomotif',
            'jenis_kegiatan'   => 'harian',
            'kondisi_akhir'    => 'kotor',
            'jumlah_petugas'   => 1,
            'status'           => 'draf',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $csv = "nomor_kegiatan,tanggal_kegiatan,area,jenis_kegiatan,kondisi_akhir,jumlah_petugas,status\n"
            . "KBR/UJI/0001,2026-10-05,Bengkel Otomotif,mingguan,bersih,4,selesai\n";

        $file = $this->makeCsv($csv);

        $this->withSession($this->adminSession())
            ->post(route('dashboard.laboran.kebersihan.import'), [
                'file' => new UploadedFile($file, 'kebersihan.csv', 'text/csv', null, true),
            ])
            ->assertStatus(200);

        $this->assertSame(1, DB::table('laboran_kebersihan')->where('nomor_kegiatan', 'KBR/UJI/0001')->count());

        $row = DB::table('laboran_kebersihan')->where('nomor_kegiatan', 'KBR/UJI/0001')->first();
        $this->assertSame('2026-10-05', $row->tanggal_kegiatan);
        $this->assertSame('mingguan', $row->jenis_kegiatan);
        $this->assertSame('bersih', $row->kondisi_akhir);
        $this->assertSame(4, (int) $row->jumlah_petugas);
        $this->assertSame('selesai', $row->status);

        @unlink($file);
    }

    public function test_import_kebersihan_menolak_berkas_kosong(): void
    {
        $file = $this->makeCsv("area,tanggal_kegiatan\n");

        $this->withSession($this->adminSession())
            ->post(route('dashboard.laboran.kebersihan.import'), [
                'file' => new UploadedFile($file, 'kosong.csv', 'text/csv', null, true),
            ])
            ->assertStatus(422);

        @unlink($file);
    }
}
