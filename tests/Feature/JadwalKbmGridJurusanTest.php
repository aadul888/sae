<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Matriks grid Jadwal KBM: pengelompokan kolom per jurusan + sel yang mampu
 * memuat lebih dari satu jadwal tanpa saling menimpa.
 *
 * Memakai database operasional (tanpa RefreshDatabase), jadi seluruh data uji
 * (`jadwal_kbm`, `pembelajaran`, `rombongan_belajar`) dibuat dengan awalan UJI-
 * dan dihapus pada tearDown.
 */
class JadwalKbmGridJurusanTest extends TestCase
{
    /** @var array<int,string> */
    private array $rombelIds = [];

    /** @var array<int,string> */
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
            'id'           => $admin->pengguna_id,
            'pengguna_id'  => $admin->pengguna_id,
            'name'         => $admin->nama,
            'nama'         => $admin->nama,
            'username'     => $admin->username,
            'role'         => 'admin',
            'peran_id_str' => $admin->peran_id_str,
        ]];
    }

    /**
     * Buat rombel sementara pada jurusan tertentu.
     */
    private function buatRombel(string $nama, string $tingkat, string $jurusanId, string $jurusanNama): string
    {
        $id = (string) Str::uuid();
        DB::table('rombongan_belajar')->insert([
            'rombongan_belajar_id'   => $id,
            'nama'                   => $nama,
            'tingkat_pendidikan_id'  => $tingkat,
            'jenis_rombel'           => 1,
            'jenis_rombel_str'       => 'Kelas',
            'jurusan_id'             => $jurusanId,
            'jurusan_id_str'         => $jurusanNama,
        ]);
        $this->rombelIds[] = $id;

        return $id;
    }

    private function buatPembelajaran(string $rombelId, string $suffix): string
    {
        $id = 'UJI-' . $this->tag . '-' . $suffix;
        DB::table('pembelajaran')->insert([
            'pembelajaran_id'         => $id,
            'rombongan_belajar_id'    => $rombelId,
            'nama_mata_pelajaran'     => 'Mapel ' . $suffix . ' ' . $this->tag,
            'mata_pelajaran_id_str'   => 'Mapel ' . $suffix,
            'jam_mengajar_per_minggu' => 4,
        ]);
        $this->pembelajaranIds[] = $id;

        return $id;
    }

    private function jadwalkan(string $rombelId, string $pembelajaranId, string $mapel, int $jamMulai, int $jamSelesai): void
    {
        DB::table('jadwal_kbm')->insert([
            'rombongan_belajar_id' => $rombelId,
            'pembelajaran_id'      => $pembelajaranId,
            'nama_mata_pelajaran'  => $mapel,
            'hari'                 => 'Senin',
            'jam_ke_mulai'         => $jamMulai,
            'jam_ke_selesai'       => $jamSelesai,
            'jam_mulai'            => '07:15:00',
            'jam_selesai'          => '08:45:00',
            'sumber'               => 'otomatis',
            'is_active'            => true,
        ]);
    }

    public function test_grid_mengelompokkan_kolom_per_jurusan(): void
    {
        $rombelA = $this->buatRombel('UJI-' . $this->tag . ' A', '10', '9990001', 'Jurusan Uji Satu');
        $rombelB = $this->buatRombel('UJI-' . $this->tag . ' B', '10', '9990002', 'Jurusan Uji Dua');

        $this->jadwalkan($rombelA, $this->buatPembelajaran($rombelA, 'A'), 'Mapel A', 1, 2);
        $this->jadwalkan($rombelB, $this->buatPembelajaran($rombelB, 'B'), 'Mapel B', 1, 2);

        $response = $this->withSession($this->adminSession())
            ->get(route('dashboard.jadwal-kbm.index', ['view_mode' => 'grid', 'hari' => 'Senin']));

        $response->assertOk();

        // Baris grup jurusan harus muncul dan memuat singkatan/kategori jurusan uji.
        $html = $response->getContent();
        $this->assertStringContainsString('jadwal-group-row', $html, 'Baris grup kolom per jurusan harus dirender.');
        $this->assertStringContainsString('Jurusan Uji Satu', $html);
        $this->assertStringContainsString('Jurusan Uji Dua', $html);
    }

    public function test_filter_jurusan_menyaring_kolom_kelas(): void
    {
        $rombelA = $this->buatRombel('UJI-' . $this->tag . ' FA', '10', '9990011', 'Jurusan Saring Satu');
        $rombelB = $this->buatRombel('UJI-' . $this->tag . ' FB', '10', '9990012', 'Jurusan Saring Dua');

        $pembA = $this->buatPembelajaran($rombelA, 'FA');
        $pembB = $this->buatPembelajaran($rombelB, 'FB');
        $this->jadwalkan($rombelA, $pembA, 'Mapel FA', 1, 2);
        $this->jadwalkan($rombelB, $pembB, 'Mapel FB', 3, 4);

        // Hanya jurusan pertama yang diminta -> kolom kelas jurusan kedua hilang.
        $response = $this->withSession($this->adminSession())
            ->get(route('dashboard.jadwal-kbm.index', [
                'view_mode' => 'grid',
                'hari'      => 'Senin',
                'jurusan'   => '9990011',
            ]));

        $response->assertOk();
        $html = $response->getContent();

        // Batasi pemeriksaan pada tabel matriks grid (nama kelas juga muncul di
        // dropdown rombel pada modal, jadi tidak boleh diperiksa global).
        $gridStart = strpos($html, 'gridMatrixContainer');
        $gridEnd = $gridStart === false ? false : strpos($html, '</table>', $gridStart);
        $this->assertNotFalse($gridStart, 'Tabel matriks grid harus ada.');
        $gridHtml = substr($html, $gridStart, $gridEnd - $gridStart);

        $this->assertStringContainsString('UJI-' . $this->tag . ' FA', $gridHtml, 'Kelas pada jurusan terpilih harus tampil.');
        $this->assertStringNotContainsString('UJI-' . $this->tag . ' FB', $gridHtml, 'Kelas jurusan lain harus tersaring dari grid.');
    }

    public function test_dua_jadwal_pada_sel_yang_sama_dirender_berderet(): void
    {
        $rombel = $this->buatRombel('UJI-' . $this->tag . ' TT', '10', '9990021', 'Jurusan Tumpuk');

        $pemb1 = $this->buatPembelajaran($rombel, 'TT1');
        $pemb2 = $this->buatPembelajaran($rombel, 'TT2');

        // Dua jadwal pada jam 1-2 (team teaching / dua guru pada jam sama).
        $this->jadwalkan($rombel, $pemb1, 'Mapel Berderet Satu', 1, 2);
        $this->jadwalkan($rombel, $pemb2, 'Mapel Berderet Dua', 1, 2);

        $response = $this->withSession($this->adminSession())
            ->get(route('dashboard.jadwal-kbm.index', ['view_mode' => 'grid', 'hari' => 'Senin']));

        $response->assertOk();
        $html = $response->getContent();

        // Kedua jadwal harus muncul (tidak ada yang tertimpa), di dalam penampung berderet.
        $this->assertStringContainsString('jadwal-cell-stack', $html, 'Sel harus memakai penampung berderet.');
        $this->assertStringContainsString('Mapel Berderet Satu', $html, 'Jadwal pertama harus tetap tampil.');
        $this->assertStringContainsString('Mapel Berderet Dua', $html, 'Jadwal kedua tidak boleh hilang tertimpa.');
    }
}
