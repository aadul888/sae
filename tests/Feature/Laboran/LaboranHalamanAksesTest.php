<?php

namespace Tests\Feature\Laboran;

use Illuminate\Support\Facades\DB;

/**
 * B. Halaman modul dapat diakses & proteksi tamu.
 */
class LaboranHalamanAksesTest extends LaboranTestCase
{
    public function test_semua_halaman_modul_dapat_diakses(): void
    {
        $session = $this->adminSession();

        $routes = [
            'dashboard.laboran.dashboard',
            'dashboard.laboran.kebutuhan.index',
            'dashboard.laboran.perawatan.index',
            'dashboard.laboran.pemeriksaan.index',
            'dashboard.laboran.pengadaan.index',
            'dashboard.laboran.kerusakan.index',
            'dashboard.laboran.peminjaman-alat.index',
            'dashboard.laboran.laporan-lengkap.index',
        ];

        foreach ($routes as $name) {
            $res = $this->withSession($session)->get(route($name));
            $this->assertSame(200, $res->getStatusCode(), "Halaman {$name} gagal dimuat.");
        }
    }

    public function test_tamu_ditolak_dari_modul_laboran(): void
    {
        foreach ([
            'dashboard.laboran.kebutuhan.index',
            'dashboard.laboran.perawatan.index',
            'dashboard.laboran.laporan-lengkap.index',
        ] as $name) {
            $this->get(route($name))->assertStatus(302);
        }
    }

    /**
     * Menu Bahan Habis Pakai juga dapat diakses pengelola Sarpras (menu_sarpras),
     * dengan cakupan data lintas jurusan.
     */
    public function test_staf_sarpras_dapat_akses_bahan_habis_pakai(): void
    {
        $duty = DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->where('rtt.kode', 'STAF_SARPRAS')
            ->where('ptt.is_active', true)
            ->select('ptt.*')
            ->first();

        if (!$duty) {
            $this->markTestSkipped('Baris tugas tambahan STAF_SARPRAS tidak tersedia.');
        }

        $p = DB::table('pengguna')->where('pengguna_id', $duty->user_id)->first();
        $this->assertNotNull($p, 'Data pengguna STAF_SARPRAS tidak ditemukan.');

        $session = ['user' => [
            'id'           => $p->pengguna_id,
            'pengguna_id'  => $p->pengguna_id,
            'name'         => $p->nama,
            'nama'         => $p->nama,
            'username'     => $p->username,
            'role'         => 'tendik',
            'peran_id_str' => $p->peran_id_str,
            'ptk_id'       => $duty->ptk_id,
        ]];

        $this->withSession($session)
            ->get(route('dashboard.laboran.bhp.index'))
            ->assertStatus(200);

        // Cakupan lintas jurusan: pengelola Sarpras tidak terkunci jurusan.
        $scope = \App\Support\LaboranScope::resolve($session['user']);
        $this->assertTrue($scope['unrestricted'], 'Pengelola Sarpras harus melihat BHP lintas jurusan.');
    }
}
