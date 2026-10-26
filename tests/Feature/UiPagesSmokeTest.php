<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UiPagesSmokeTest extends TestCase
{
    private function adminSession(): array
    {
        $admin = DB::table('pengguna')->where('peran_id_str', 'Administrator')->first();

        return ['user' => [
            'id' => $admin->pengguna_id,
            'pengguna_id' => $admin->pengguna_id,
            'name' => $admin->nama,
            'nama' => $admin->nama,
            'username' => $admin->username,
            'role' => 'admin',
            'peran_id_str' => $admin->peran_id_str,
        ]];
    }

    public function test_public_and_auth_pages_render(): void
    {
        foreach (['/', '/login', '/offline'] as $path) {
            $res = $this->get($path);
            $this->assertSame(200, $res->getStatusCode(), "Halaman publik {$path} gagal.");
        }
    }

    public function test_admin_pages_render(): void
    {
        $session = $this->adminSession();

        $paths = [
            '/dashboard/admin',
            '/dashboard/identitas-sekolah',
            '/dashboard/presensi/kelas',
            '/dashboard/kesiswaan/kedisiplinan',
            '/dashboard/persuratan/keluar',
            '/dashboard/persuratan/masuk',
            '/dashboard/laboran/dashboard',
            '/dashboard/laboran/jadwal',
            '/dashboard/laboran/kebutuhan',
            '/dashboard/laboran/perawatan',
            '/dashboard/laboran/pemeriksaan',
            '/dashboard/laboran/pengadaan',
            '/dashboard/laboran/kerusakan',
            '/dashboard/laboran/peminjaman-alat',
            '/dashboard/laboran/laporan',
            '/dashboard/laboran/laporan-lengkap',
            '/dashboard/laboran/administrasi',
            '/dashboard/laboran/permintaan',
            '/dashboard/laboran/arsip',
            '/dashboard/laboran/jurnal',
            '/dashboard/laboran/kebersihan',
        ];

        foreach ($paths as $path) {
            $res = $this->withSession($session)->get($path);
            $this->assertSame(200, $res->getStatusCode(), "Halaman admin {$path} gagal.");
        }
    }

    public function test_pages_have_no_broken_css_var_syntax(): void
    {
        $session = $this->adminSession();

        $paths = [
            '/',
            '/login',
            '/dashboard/admin',
            '/dashboard/identitas-sekolah',
            '/dashboard/presensi/kelas',
        ];

        foreach ($paths as $path) {
            $html = $this->withSession($session)->get($path)->getContent();

            // Pola rusak: var(--x)(...) atau var(--x), 0.875rem tanpa var(
            $this->assertDoesNotMatchRegularExpression(
                '/var\(--[a-z0-9-]+\)\s*[\(,]\s*[0-9]/i',
                $html,
                "Sintaks CSS rusak ditemukan di {$path}."
            );
        }
    }
}
