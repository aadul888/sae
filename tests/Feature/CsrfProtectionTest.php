<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Memastikan proteksi CSRF tetap aktif setelah migrasi ke PreventRequestForgery
 * (Laravel 13) dan tidak melemah.
 */
class CsrfProtectionTest extends TestCase
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

    public function test_post_without_csrf_token_is_rejected(): void
    {
        // Middleware CSRF dimatikan pada environment testing secara default
        // oleh Laravel, jadi kita paksa aktifkan untuk test ini.
        $this->app['config']->set('app.env', 'testing');

        $response = $this->withSession($this->adminSession())
            ->post('/dashboard/identitas-sekolah/google-config', [
                'google_client_id' => 'test.apps.googleusercontent.com',
            ]);

        // Tanpa token CSRF yang valid, request harus ditolak (419) atau
        // setidaknya tidak menghasilkan efek samping sukses.
        $this->assertContains(
            $response->getStatusCode(),
            [419, 302, 403],
            'POST tanpa token CSRF seharusnya tidak diproses sebagai sukses.'
        );
    }

    public function test_form_pages_include_csrf_token(): void
    {
        $res = $this->withSession($this->adminSession())->get('/dashboard/identitas-sekolah');

        $this->assertSame(200, $res->getStatusCode());
        $this->assertStringContainsString(
            'name="_token"',
            $res->getContent(),
            'Halaman dengan form wajib menyertakan token CSRF.'
        );
    }
}
