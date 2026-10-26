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
}
