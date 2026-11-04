<?php

namespace Tests\Feature;

use Tests\TestCase;

class SidebarRoleAccessTest extends TestCase
{
    public function test_admin_sidebar_shows_authorized_menus(): void
    {
        $sessionUser = [
            'id' => 'admin-sb-test',
            'pengguna_id' => 'admin-sb-test',
            'username' => 'admin',
            'nama' => 'Administrator',
            'role' => 'admin',
        ];

        session(['user' => $sessionUser]);
        $html = view('partials.dash-sidebar')->render();

        $this->assertStringContainsString('Data Kebutuhan MBG', $html);
        $this->assertStringContainsString('e-Izin Siswa', $html);
        $this->assertStringContainsString('Layanan Digital', $html);
    }

    public function test_regular_guru_sidebar_does_not_show_unauthorized_menus(): void
    {
        $sessionUser = [
            'id' => 'guru-sb-test',
            'pengguna_id' => 'guru-sb-test',
            'username' => 'guru1',
            'nama' => 'Guru Reguler',
            'role' => 'guru',
            'ptk_id' => 'ptk-plain-999',
        ];

        session(['user' => $sessionUser]);
        $html = view('partials.dash-sidebar')->render();

        $this->assertStringNotContainsString('Data Kebutuhan MBG', $html);
        $this->assertStringNotContainsString('e-Izin Siswa', $html);
        $this->assertStringNotContainsString('Layanan Digital', $html);
    }

    public function test_regular_tendik_sidebar_does_not_show_unauthorized_menus(): void
    {
        $sessionUser = [
            'id' => 'tendik-sb-test',
            'pengguna_id' => 'tendik-sb-test',
            'username' => 'tendik1',
            'nama' => 'Tendik Reguler',
            'role' => 'tendik',
            'ptk_id' => 'ptk-tendik-999',
        ];

        session(['user' => $sessionUser]);
        $html = view('partials.dash-sidebar')->render();

        $this->assertStringNotContainsString('Data Kebutuhan MBG', $html);
        $this->assertStringNotContainsString('e-Izin Siswa', $html);
        $this->assertStringNotContainsString('Layanan Digital', $html);
    }

    public function test_peserta_didik_sidebar_does_not_show_mbg_or_layanan_digital(): void
    {
        $sessionUser = [
            'id' => 'pd-sb-test',
            'pengguna_id' => 'pd-sb-test',
            'username' => '0012345678',
            'nama' => 'Siswa Reguler',
            'role' => 'peserta_didik',
            'peserta_didik_id' => 'pd-test-uuid',
        ];

        session(['user' => $sessionUser]);
        $html = view('partials.dash-sidebar')->render();

        $this->assertStringNotContainsString('Data Kebutuhan MBG', $html);
        $this->assertStringNotContainsString('Layanan Digital', $html);
        $this->assertStringContainsString('e-Izin Keluar &amp; Pulang', $html);
    }

    public function test_orang_tua_sidebar_does_not_show_mbg_or_layanan_digital(): void
    {
        $sessionUser = [
            'id' => 'ortu-sb-test',
            'pengguna_id' => 'ortu-sb-test',
            'username' => 'ortu1',
            'nama' => 'Orang Tua Reguler',
            'role' => 'orang_tua',
        ];

        session(['user' => $sessionUser]);
        $html = view('partials.dash-sidebar')->render();

        $this->assertStringNotContainsString('Data Kebutuhan MBG', $html);
        $this->assertStringNotContainsString('Layanan Digital', $html);
        $this->assertStringNotContainsString('e-Izin Siswa', $html);
    }

    public function test_mobile_bottom_nav_respects_permissions(): void
    {
        $sessionUser = [
            'id' => 'pd-mbn-test',
            'pengguna_id' => 'pd-mbn-test',
            'username' => '0012345678',
            'nama' => 'Siswa Reguler',
            'role' => 'peserta_didik',
            'peserta_didik_id' => 'pd-test-uuid',
        ];

        session(['user' => $sessionUser]);
        $html = view('partials.mobile-bottom-nav')->render();

        $this->assertStringContainsString('Identitas', $html);
        $this->assertStringContainsString('Berkas', $html);
        $this->assertStringContainsString('Presensi', $html);

        $sessionOrtu = [
            'id' => 'ortu-mbn-test',
            'pengguna_id' => 'ortu-mbn-test',
            'username' => 'ortu1',
            'nama' => 'Orang Tua',
            'role' => 'orang_tua',
        ];

        session(['user' => $sessionOrtu]);
        $htmlOrtu = view('partials.mobile-bottom-nav')->render();

        $this->assertStringContainsString('Kehadiran', $htmlOrtu);
        $this->assertStringContainsString('Jadwal', $htmlOrtu);
        $this->assertStringContainsString('e-Izin', $htmlOrtu);
    }
}
