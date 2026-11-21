<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Sesi PWA: login dari mode standalone memakai remember token, dan
 * `session('user')` dipulihkan dari guard saat sesi server hilang.
 *
 * Memakai akun admin tetap seperti ComprehensiveScenarioTest, dibungkus
 * DatabaseTransactions agar perubahan remember_token di rollback.
 */
class PwaRememberSessionTest extends TestCase
{
    use DatabaseTransactions;

    private const USERNAME = 'opsmakpal@gmail.com';
    private const PASSWORD = 'Admin116@';

    public function test_login_pwa_membuat_remember_token(): void
    {
        $user = User::where('username', self::USERNAME)->first();
        if (!$user) {
            $this->markTestSkipped('Akun uji tidak ada di basis data.');
        }

        $res = $this->post('/login', [
            'login_type' => 'umum',
            'username' => self::USERNAME,
            'password' => self::PASSWORD,
            'pwa' => '1',
        ]);

        $res->assertRedirect(route('dashboard.admin'));
        $this->assertAuthenticatedAs($user->fresh());
        $this->assertNotEmpty($user->fresh()->remember_token);
    }

    public function test_login_biasa_tidak_membuat_remember_token(): void
    {
        $user = User::where('username', self::USERNAME)->first();
        if (!$user) {
            $this->markTestSkipped('Akun uji tidak ada di basis data.');
        }

        $sebelum = $user->remember_token;

        $this->post('/login', [
            'login_type' => 'umum',
            'username' => self::USERNAME,
            'password' => self::PASSWORD,
        ])->assertRedirect(route('dashboard.admin'));

        $this->assertSame($sebelum, $user->fresh()->remember_token);
    }

    public function test_session_user_dipulihkan_dari_guard(): void
    {
        $user = User::where('username', self::USERNAME)->first();
        if (!$user) {
            $this->markTestSkipped('Akun uji tidak ada di basis data.');
        }

        // Simulasi: guard terisi (mis. dari cookie remember), sesi aplikasi kosong.
        $res = $this->actingAs($user)->get('/dashboard/admin');

        $res->assertStatus(200);
        $this->assertSame($user->pengguna_id, session('user.pengguna_id'));
    }
}
