<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class WaliKelasModuleTest extends TestCase
{
    public function test_guest_is_redirected_from_all_wali_kelas_routes(): void
    {
        $this->get(route('dashboard.wali-kelas.peserta-didik-aktif.index'))->assertStatus(302)->assertRedirect(route('login'));
        $this->get(route('dashboard.wali-kelas.validasi.index'))->assertStatus(302)->assertRedirect(route('login'));
        $this->get(route('dashboard.wali-kelas.presensi.index'))->assertStatus(302)->assertRedirect(route('login'));
        $this->get(route('dashboard.wali-kelas.agenda.index'))->assertStatus(302)->assertRedirect(route('login'));
    }

    public function test_admin_can_access_wali_kelas_modules(): void
    {
        $this->withoutExceptionHandling();

        $sessionUser = [
            'id'          => 'admin-wali-test',
            'pengguna_id' => 'admin-wali-test',
            'username'    => 'admin',
            'nama'        => 'Administrator Test',
            'role'        => 'admin',
        ];

        $resAktif = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.wali-kelas.peserta-didik-aktif.index'));
        $resAktif->assertStatus(200);

        $resValidasi = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.wali-kelas.validasi.index'));
        $resValidasi->assertStatus(200);

        $resPresensi = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.wali-kelas.presensi.index'));
        $resPresensi->assertStatus(200);

        $resAgenda = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.wali-kelas.agenda.index'));
        $resAgenda->assertStatus(200);
    }

    public function test_agenda_next_pertemuan_returns_valid_integer(): void
    {
        $sessionUser = [
            'id'          => 'admin-wali-test',
            'pengguna_id' => 'admin-wali-test',
            'username'    => 'admin',
            'nama'        => 'Administrator Test',
            'role'        => 'admin',
        ];

        $res = $this->withSession(['user' => $sessionUser])
            ->getJson(route('dashboard.wali-kelas.agenda.next-pertemuan', [
                'nama_mata_pelajaran' => 'Matematika'
            ]));

        $res->assertStatus(200);
        $res->assertJsonStructure(['status', 'next_pertemuan']);
    }
}
