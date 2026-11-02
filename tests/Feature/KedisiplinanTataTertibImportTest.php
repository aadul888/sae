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
        KedisiplinanTataTertib::whereIn('kode', ['TEST01', 'TEST02', 'TEST03', 'KER01', 'HD01', 'PR02'])
            ->orWhere('nama_aturan', 'like', 'Aturan Uji Otomatis%')
            ->delete();
        parent::tearDown();
    }

    public function test_template_roundtrip_import_with_tingkat()
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
            'tingkat' => 'ringan',
            'kategori' => 'kerapian',
            'bobot_poin' => 5,
        ]);

        $this->assertDatabaseHas('kedisiplinan_tata_tertib', [
            'kode' => 'HD02',
            'tingkat' => 'sedang',
            'kategori' => 'kehadiran',
            'bobot_poin' => 20,
        ]);

        $this->assertDatabaseHas('kedisiplinan_tata_tertib', [
            'kode' => 'LB01',
            'tingkat' => 'berat',
            'kategori' => 'larangan_berat',
            'bobot_poin' => 50,
        ]);

        @unlink($tempPath);
    }

    public function test_can_import_tata_tertib_with_explicit_tingkat()
    {
        $csvContent = implode("\n", [
            'kode,tingkat,kategori,nama_aturan,bobot_poin,sanksi_rekomendasi',
            'TEST01,ringan,kerapian,Aturan Uji Kerapian Ringan,5,Teguran lisan',
            'TEST02,sedang,perilaku,Aturan Uji Perilaku Sedang,25,Panggilan orang tua',
            'TEST03,berat,larangan_berat,Aturan Uji Pelanggaran Berat,80,Skorsing',
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
            'tingkat' => 'ringan',
        ]);

        $this->assertDatabaseHas('kedisiplinan_tata_tertib', [
            'kode' => 'TEST02',
            'tingkat' => 'sedang',
        ]);

        $this->assertDatabaseHas('kedisiplinan_tata_tertib', [
            'kode' => 'TEST03',
            'tingkat' => 'berat',
        ]);
    }

    public function test_view_tata_tertib_tab_shows_tingkat_badges_and_filter()
    {
        $response = $this->withSession($this->session)
            ->get(route('dashboard.kesiswaan.kedisiplinan.index', ['tab' => 'tata_tertib']));

        $response->assertStatus(200);
        $response->assertSee('btnOpenImportTatibModal');
        $response->assertSee('modalImportTataTertib');
        $response->assertSee('filterTingkat');
        $response->assertSee('RINGAN');
        $response->assertSee('SEDANG');
        $response->assertSee('BERAT');
        $response->assertSee('periode-nav-mobile');
        $response->assertSee('periode-mobile-select');
    }

    public function test_get_next_kode_endpoint()
    {
        $response = $this->withSession($this->session)
            ->get(route('dashboard.kesiswaan.kedisiplinan.tatib.next_kode', ['kategori' => 'kerapian']));

        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'kode']);
        $this->assertStringStartsWith('KER', $response->json('kode'));
    }

    public function test_store_tatib_generates_kode_automatically()
    {
        $response = $this->withSession($this->session)
            ->post(route('dashboard.kesiswaan.kedisiplinan.tatib.store'), [
                'kategori'           => 'perilaku',
                'nama_aturan'        => 'Aturan Uji Otomatis Perilaku',
                'bobot_poin'         => 12,
                'sanksi_rekomendasi' => 'Peringatan guru BK',
            ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data['kode']);
        $this->assertStringStartsWith('PR', $data['kode']);
        $this->assertEquals('ringan', $data['tingkat']);

        $this->assertDatabaseHas('kedisiplinan_tata_tertib', [
            'id'          => $data['id'],
            'nama_aturan' => 'Aturan Uji Otomatis Perilaku',
            'kode'        => $data['kode'],
        ]);
    }

    public function test_import_generates_kode_when_column_empty()
    {
        $csvContent = implode("\n", [
            'kode,tingkat,kategori,nama_aturan,bobot_poin,sanksi_rekomendasi',
            ',ringan,kerapian,Aturan Uji Otomatis Import Tanpa Kode,5,Teguran lisan',
        ]);

        $file = UploadedFile::fake()->createWithContent('tatib_nokode.csv', $csvContent);

        $response = $this->withSession($this->session)
            ->post(route('dashboard.kesiswaan.kedisiplinan.tatib.import'), [
                'file_excel' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $saved = KedisiplinanTataTertib::where('nama_aturan', 'Aturan Uji Otomatis Import Tanpa Kode')->first();
        $this->assertNotNull($saved);
        $this->assertStringStartsWith('KER', $saved->kode);
    }
}
