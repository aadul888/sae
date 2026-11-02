<?php

namespace Tests\Feature;

use App\Models\KedisiplinanTataTertib;
use App\Services\SimpleXlsxReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KedisiplinanTataTertibImportTest extends TestCase
{
    protected array $session = [];

    protected function setUp(): void
    {
        parent::setUp();

        $admin = DB::table('pengguna')->where('peran_id_str', 'Administrator')->first()
            ?? DB::table('pengguna')->first();

        $this->session = ['user' => [
            'id'           => $admin->pengguna_id,
            'pengguna_id'  => $admin->pengguna_id,
            'name'         => $admin->nama,
            'nama'         => $admin->nama,
            'username'     => $admin->username,
            'role'         => 'admin',
            'peran_id_str' => $admin->peran_id_str,
            'ptk_id'       => $admin->ptk_id ?? null,
        ]];
    }

    protected function tearDown(): void
    {
        KedisiplinanTataTertib::whereIn('kode', ['TEST01', 'TEST02', 'KER01', 'HD01'])->delete();
        parent::tearDown();
    }

    public function test_template_roundtrip_import()
    {
        // 1. Download template file
        $response = $this->withSession($this->session)
            ->get(route('dashboard.kesiswaan.kedisiplinan.tatib.template'));
        $response->assertStatus(200);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_tpl_') . '.xlsx';
        file_put_contents($tempPath, $response->streamedContent());

        // 2. Upload template back into import endpoint
        $file = new UploadedFile($tempPath, 'Template_Import_Tata_Tertib_SAE.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $importResponse = $this->withSession($this->session)
            ->post(route('dashboard.kesiswaan.kedisiplinan.tatib.import'), [
                'file_excel' => $file,
            ]);

        $importResponse->assertStatus(200);
        $importResponse->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('kedisiplinan_tata_tertib', [
            'kode' => 'KER01',
            'kategori' => 'kerapian',
            'bobot_poin' => 5,
        ]);

        @unlink($tempPath);
    }

    public function test_can_import_tata_tertib_from_csv()
    {
        $csvContent = implode("\n", [
            'kode,kategori,nama_aturan,bobot_poin,sanksi_rekomendasi',
            'TEST01,kerapian,Aturan Uji Kerapian,5,Teguran lisan',
            'TEST02,larangan_berat,Aturan Uji Pelanggaran Berat,75,Panggilan orang tua',
        ]);

        $file = UploadedFile::fake()->createWithContent('tatib.csv', $csvContent);

        $response = $this->withSession($this->session)
            ->post(route('dashboard.kesiswaan.kedisiplinan.tatib.import'), [
                'file_excel' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('kedisiplinan_tata_tertib', [
            'kode' => 'TEST01',
            'kategori' => 'kerapian',
            'bobot_poin' => 5,
        ]);

        $this->assertDatabaseHas('kedisiplinan_tata_tertib', [
            'kode' => 'TEST02',
            'kategori' => 'larangan_berat',
            'bobot_poin' => 75,
        ]);
    }

    public function test_view_tata_tertib_tab_shows_import_button()
    {
        $response = $this->withSession($this->session)
            ->get(route('dashboard.kesiswaan.kedisiplinan.index', ['tab' => 'tata_tertib']));

        $response->assertStatus(200);
        $response->assertSee('btnOpenImportTatibModal');
        $response->assertSee('modalImportTataTertib');
    }
}
