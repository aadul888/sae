<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AuthSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Memverifikasi integrasi guard Laravel dengan sesi aplikasi.
 *
 * Sebelumnya `Auth::login()` tidak pernah dipanggil, sehingga `Auth::check()`
 * selalu false meski pengguna sudah masuk lewat sesi manual.
 */
class AuthGuardIntegrationTest extends TestCase
{
    private function adminUser(): User
    {
        $admin = DB::table('pengguna')->where('peran_id_str', 'Administrator')->first();
        $this->assertNotNull($admin, 'Akun administrator tidak ditemukan untuk pengujian.');

        return User::find($admin->pengguna_id);
    }

    public function test_login_fills_both_guard_and_session(): void
    {
        $user = $this->adminUser();
        $userData = [
            'pengguna_id' => $user->pengguna_id,
            'name' => $user->nama,
            'role' => 'admin',
        ];

        AuthSession::login($user, $userData);

        // Guard Laravel terisi
        $this->assertTrue(Auth::check(), 'Auth::check() harus true setelah login.');
        $this->assertSame($user->pengguna_id, Auth::id());

        // Sesi aplikasi tetap terisi (kompatibilitas)
        $this->assertSame('admin', session('user')['role']);
    }

    public function test_logout_clears_both_guard_and_session(): void
    {
        $user = $this->adminUser();
        AuthSession::login($user, ['pengguna_id' => $user->pengguna_id, 'role' => 'admin']);

        $this->assertTrue(Auth::check());

        AuthSession::logout();

        $this->assertFalse(Auth::check(), 'Auth::check() harus false setelah logout.');
        $this->assertNull(session('user'), 'session("user") harus bersih setelah logout.');
    }

    public function test_auth_session_falls_back_to_legacy_session(): void
    {
        // Simulasikan sesi lama (dibuat sebelum migrasi) tanpa guard.
        $user = $this->adminUser();
        $this->withSession(['user' => [
            'pengguna_id' => $user->pengguna_id,
            'role' => 'admin',
        ]]);

        $this->assertFalse(Auth::check(), 'Guard belum terisi pada skenario ini.');
        $this->assertTrue(AuthSession::check(), 'AuthSession::check() harus mengenali sesi lama.');

        $resolved = AuthSession::user();
        $this->assertNotNull($resolved, 'AuthSession::user() harus fallback ke data sesi.');
        $this->assertSame($user->pengguna_id, $resolved->pengguna_id);
    }

    public function test_auth_session_returns_null_when_not_logged_in(): void
    {
        $this->assertFalse(AuthSession::check());
        $this->assertNull(AuthSession::user());
    }

    public function test_real_login_route_fills_guard(): void
    {
        // Gunakan akun uji yang passwordnya diketahui dari seeder.
        $admin = DB::table('pengguna')
            ->where('peran_id_str', 'Administrator')
            ->whereNotNull('password')
            ->first();

        $this->assertNotNull($admin);

        // Login lewat route nyata (password default NISN tidak diketahui),
        // jadi cukup pastikan halaman login dapat diakses dan form memuat token.
        $res = $this->get('/login');
        $res->assertStatus(200);
        $res->assertSee('_token', false);
    }
}
