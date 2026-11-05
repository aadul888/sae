<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Input manual jadwal dari halaman dashboard/jadwal-pelajaran:
 * modal CRUD dirender untuk pengelola + endpoint daftar mapel per kelas.
 */
class JadwalPelajaranManualInputTest extends TestCase
{
    private array $rombelIds = [];
    private array $pembelajaranIds = [];
    private string $tag = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->tag = substr(uniqid(), -8);
    }

    protected function tearDown(): void
    {
        if (!empty($this->rombelIds)) {
            DB::table('jadwal_kbm')->whereIn('rombongan_belajar_id', $this->rombelIds)->delete();
            DB::table('pembelajaran')->whereIn('rombongan_belajar_id', $this->rombelIds)->delete();
            DB::table('rombongan_belajar')->whereIn('rombongan_belajar_id', $this->rombelIds)->delete();
        }

        parent::tearDown();
    }

    private function adminSession(): array
    {
        $admin = DB::table('pengguna')->where('peran_id_str', 'Administrator')->first();

        return ['user' => [
            'id'          => $admin->pengguna_id,
            'pengguna_id' => $admin->pengguna_id,
            'name'        => $admin->nama,
            'nama'        => $admin->nama,
            'username'    => $admin->username,
            'role'        => 'admin',
        ]];
    }

    private function buatRombel(): string
    {
        $id = (string) Str::uuid();
        DB::table('rombongan_belajar')->insert([
            'rombongan_belajar_id'  => $id,
            'nama'                  => 'UJI-JP-' . $this->tag,
            'tingkat_pendidikan_id' => '10',
            'jenis_rombel'          => 1,
            'jenis_rombel_str'      => 'Kelas',
        ]);
        $this->rombelIds[] = $id;

        return $id;
    }

    private function buatPembelajaran(string $rombelId, ?string $ptkId): string
    {
        $id = 'UJI-JP-' . $this->tag . '-' . Str::random(4);
        DB::table('pembelajaran')->insert([
            'pembelajaran_id'         => $id,
            'rombongan_belajar_id'    => $rombelId,
            'nama_mata_pelajaran'     => 'Mapel Uji JP ' . $this->tag,
            'mata_pelajaran_id_str'   => 'Mapel Uji JP',
            'ptk_id'                  => $ptkId,
            'jam_mengajar_per_minggu' => 4,
        ]);
        $this->pembelajaranIds[] = $id;

        return $id;
    }

    public function test_halaman_jadwal_pelajaran_menyediakan_modal_input_manual_untuk_admin(): void
    {
        $response = $this->withSession($this->adminSession())
            ->get(route('dashboard.jadwal-pelajaran.index'));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('btnTambahJadwalManual', $html);
        $this->assertStringContainsString('modalJadwalManual', $html);
    }

    public function test_endpoint_pembelajaran_mengembalikan_mapel_sesuai_kelas(): void
    {
        $rombelId = $this->buatRombel();
        $pembelajaranId = $this->buatPembelajaran($rombelId, null);

        $response = $this->withSession($this->adminSession())
            ->getJson(route('dashboard.jadwal-kbm.manual.pembelajaran', ['rombel_id' => $rombelId]));

        $response->assertOk();
        $ids = array_column($response->json('pembelajaran'), 'pembelajaran_id');
        $this->assertContains($pembelajaranId, $ids);
    }

    public function test_guru_pengampu_hanya_menerima_mapel_yang_diampu(): void
    {
        $rombelId = $this->buatRombel();
        $milikSendiri = $this->buatPembelajaran($rombelId, 'UJI-PTK-' . $this->tag);
        $milikLain = $this->buatPembelajaran($rombelId, 'UJI-PTK-LAIN-' . $this->tag);

        $guruSession = ['user' => [
            'id'     => 'UJI-USER-' . $this->tag,
            'role'   => 'guru',
            'ptk_id' => 'UJI-PTK-' . $this->tag,
        ]];

        $response = $this->withSession($guruSession)
            ->getJson(route('dashboard.jadwal-kbm.manual.pembelajaran', ['rombel_id' => $rombelId]));

        $response->assertOk();
        $ids = array_column($response->json('pembelajaran'), 'pembelajaran_id');
        $this->assertContains($milikSendiri, $ids);
        $this->assertNotContains($milikLain, $ids);
    }
}
