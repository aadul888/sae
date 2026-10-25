<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Smoke test pasca-upgrade Laravel 13.
 * Memastikan modul-modul utama tetap dapat dirender setelah upgrade framework.
 */
class PostUpgradeSmokeTest extends TestCase
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

    public function test_laravel_version_is_13(): void
    {
        $this->assertSame('13.38.0', app()->version());
        $this->assertSame(13, (int) explode('.', app()->version())[0]);
    }

    public function test_public_pages_render(): void
    {
        foreach (['/', '/login', '/offline', '/install'] as $path) {
            $res = $this->get($path);
            $this->assertContains(
                $res->getStatusCode(),
                [200, 302],
                "Halaman {$path} gagal dirender (status {$res->getStatusCode()})."
            );
        }
    }

    public function test_core_modules_render(): void
    {
        $session = $this->adminSession();

        $paths = [
            '/dashboard/admin',
            '/dashboard/identitas-sekolah',
            '/dashboard/presensi/kelas',
            '/dashboard/kesiswaan/kedisiplinan',
            '/dashboard/persuratan/keluar',
            '/dashboard/persuratan/masuk',
            '/dashboard/tarik-data',
        ];

        foreach ($paths as $path) {
            $res = $this->withSession($session)->get($path);
            $this->assertSame(200, $res->getStatusCode(), "Modul {$path} gagal dirender.");
        }
    }

    public function test_database_and_query_builder_work(): void
    {
        // Query builder dasar
        $this->assertGreaterThan(0, DB::table('pengguna')->count());

        // Eloquent
        $this->assertGreaterThan(0, \App\Models\User::count());

        // Transaksi
        $result = DB::transaction(fn () => DB::table('pengguna')->count());
        $this->assertGreaterThan(0, $result);
    }

    public function test_session_and_cache_drivers_work(): void
    {
        session(['sae_upgrade_probe' => 'ok']);
        $this->assertSame('ok', session('sae_upgrade_probe'));

        cache()->put('sae_upgrade_probe', 'ok', 60);
        $this->assertSame('ok', cache()->get('sae_upgrade_probe'));
        cache()->forget('sae_upgrade_probe');
    }

    public function test_csrf_middleware_uses_prevent_request_forgery(): void
    {
        $middleware = new \App\Http\Middleware\VerifyCsrfToken(app(), app('encrypter'));

        $this->assertInstanceOf(
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
            $middleware,
            'Middleware CSRF harus mewarisi PreventRequestForgery (Laravel 13).'
        );
    }

    public function test_no_deprecated_csrf_class_references(): void
    {
        $files = [
            app_path('Http/Kernel.php'),
            app_path('Http/Middleware/VerifyCsrfToken.php'),
        ];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $this->assertStringNotContainsString(
                'Illuminate\Foundation\Http\Middleware\VerifyCsrfToken',
                $content,
                basename($file) . ' masih merujuk class CSRF lama yang deprecated.'
            );
        }
    }
}
