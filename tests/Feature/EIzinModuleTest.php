<?php

namespace Tests\Feature;

use App\Models\RolePermission;
use Tests\TestCase;

class EIzinModuleTest extends TestCase
{
    public function test_e_izin_in_permission_dictionary(): void
    {
        $permissions = RolePermission::getBasePermissions();
        $keys = collect($permissions)->flatMap(fn (array $items) => array_keys($items));

        $this->assertContains('menu_e_izin', $keys->all());
    }

    public function test_guest_is_redirected_from_e_izin(): void
    {
        $this->get(route('dashboard.e-izin.index'))
            ->assertStatus(302)
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_access_e_izin_index(): void
    {
        $sessionUser = [
            'id'          => 'admin-test',
            'pengguna_id' => 'admin-test',
            'username'    => 'admin',
            'nama'        => 'Administrator Test',
            'role'        => 'admin',
        ];

        $res = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.e-izin.index'));

        $res->assertStatus(200);
        $res->assertSee('e-Izin');
    }

    public function test_peserta_didik_can_access_e_izin_index(): void
    {
        $sessionUser = [
            'id'               => 'siswa-test-1',
            'pengguna_id'      => 'siswa-test-1',
            'username'         => '0051234567',
            'nama'             => 'Siswa Test',
            'role'             => 'peserta_didik',
            'peserta_didik_id' => 'pd-test-uuid',
        ];

        $res = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.e-izin.index'));

        $res->assertStatus(200);
        $res->assertSee('e-Izin');
    }

    public function test_wali_kelas_can_access_e_izin_index(): void
    {
        $sessionUser = [
            'id'          => 'wali-test-1',
            'pengguna_id' => 'wali-test-1',
            'username'    => 'guruwali',
            'nama'        => 'Guru Wali Test',
            'role'        => 'guru',
            'ptk_id'      => 'ptk-wali-uuid',
        ];

        $res = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.e-izin.index'));

        $res->assertStatus(200);
        $res->assertSee('e-Izin');
    }

    public function test_admin_can_search_siswa(): void
    {
        $sessionUser = [
            'id'          => 'admin-test',
            'pengguna_id' => 'admin-test',
            'username'    => 'admin',
            'nama'        => 'Administrator Test',
            'role'        => 'admin',
        ];

        $res = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.e-izin.search-siswa', ['q' => 'test']));

        $res->assertStatus(200);
        $this->assertIsArray($res->json());
    }

    public function test_scan_tiket_not_found(): void
    {
        $sessionUser = [
            'id'          => 'admin-test',
            'pengguna_id' => 'admin-test',
            'username'    => 'admin',
            'nama'        => 'Administrator Test',
            'role'        => 'admin',
        ];

        $res = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.e-izin.scan-tiket', ['keyword' => 'NONEXISTENT123']));

        $res->assertStatus(404);
        $this->assertSame('error', $res->json('status'));
    }

    public function test_store_validation_fails_on_empty_request(): void
    {
        $sessionUser = [
            'id'          => 'admin-test',
            'pengguna_id' => 'admin-test',
            'username'    => 'admin',
            'nama'        => 'Administrator Test',
            'role'        => 'admin',
        ];

        $res = $this->withSession(['user' => $sessionUser])
            ->post(route('dashboard.e-izin.store'), [], ['Accept' => 'application/json']);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['jenis_izin', 'alasan', 'jam_izin_keluar']);
    }
}
