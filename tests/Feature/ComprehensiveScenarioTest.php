<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ComprehensiveScenarioTest extends TestCase
{
    /**
     * 1. Test Homepage Elements & Live Check NISN Form
     */
    public function test_homepage_elements_and_merged_portal_button()
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        // Memastikan tombol gabungan "Masuk Portal" ada
        $response->assertSee('Masuk Portal');

        // Memastikan form Live Check dengan validasi ketat 10 digit ada
        $response->assertSee('nisnCounterBadge');
        $response->assertSee('0 / 10 digit');
        $response->assertSee('nisnInput');
    }

    /**
     * 2. Test Live Check Direct Verification Page for 0102015638
     */
    public function test_live_check_direct_page_without_physical_card()
    {
        $response = $this->get('/v/0102015638');
        $response->assertStatus(200);

        // Memastikan data otentikasi muncul
        $response->assertSee('0102015638');
        $response->assertSee('A SETIA DEWI');

        // Memastikan kotak fisik kartu pelajar TIDAK muncul lagi
        $response->assertDontSee('Bentuk Fisik Kartu Pelajar Digital');
        $response->assertDontSee('card-preview-section');
    }

    /**
     * 3. Test Login Page (No Demo Accounts Box)
     */
    public function test_login_page_clean_no_demo_accounts()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertDontSee('Coba Akses Cepat (Demo)');
        $response->assertDontSee('fillDemo');
    }

    /**
     * 4. Test Login Admin (opsmakpal@gmail.com / Admin116@)
     */
    public function test_login_admin()
    {
        $response = $this->post('/login', [
            'username' => 'opsmakpal@gmail.com',
            'password' => 'Admin116@',
        ]);

        $response->assertRedirect(route('dashboard.admin'));
        $response->assertSessionHas('user');
        $this->assertEquals('admin', session('user.role'));
    }

    /**
     * 5. Test Login Guru (abdulazis75@guru.smk.belajar.id / Aadul1992!@#)
     */
    public function test_login_guru()
    {
        $response = $this->post('/login', [
            'username' => 'abdulazis75@guru.smk.belajar.id',
            'password' => 'Aadul1992!@#',
        ]);

        $response->assertRedirect(route('dashboard.guru'));
        $response->assertSessionHas('user');
        $this->assertEquals('guru', session('user.role'));
    }

    /**
     * 6. Test Login Tendik (ahmad.23599@admin.smk.belajar.id / DAPOsmk65#)
     */
    public function test_login_tendik()
    {
        $response = $this->post('/login', [
            'username' => 'ahmad.23599@admin.smk.belajar.id',
            'password' => 'DAPOsmk65#',
        ]);

        $response->assertRedirect(route('dashboard.tendik'));
        $response->assertSessionHas('user');
        $this->assertEquals('tendik', session('user.role'));
    }

    /**
     * 7. Test Peserta Didik Default Password Flow (0102015638 / 0102015638)
     */
    public function test_peserta_didik_forced_password_update_flow()
    {
        // Pastikan password peserta didik 0102015638 adalah default NISN
        $u = User::where('username', '0102015638')->first();
        $this->assertNotNull($u);
        $u->password = Hash::make('0102015638');
        $u->save();

        // Step A: Login dengan NISN & password default NISN
        $loginAttempt = $this->post('/login', [
            'username' => '0102015638',
            'password' => '0102015638',
        ]);

        // Harus terintersepsi dan dialihkan ke pembaruan password wajib
        $loginAttempt->assertRedirect(route('auth.force-update-password'));
        $loginAttempt->assertSessionHas('force_update_password');
        $loginAttempt->assertSessionMissing('user'); // Belum boleh masuk dashboard!

        // Step B: Buka halaman form update password wajib
        $forcePage = $this->withSession([
            'force_update_password' => session('force_update_password')
        ])->get(route('auth.force-update-password'));

        $forcePage->assertStatus(200);
        $forcePage->assertSee('Pembaruan Password Wajib');
        $forcePage->assertSee('A SETIA DEWI');
        $forcePage->assertSee('0102015638');
        $forcePage->assertSee('Parameter Keamanan Password');

        // Step C: Coba masukkan password tidak sesuai kriteria
        $invalidAttempt = $this->withSession([
            'force_update_password' => session('force_update_password')
        ])->post(route('auth.force-update-password.post'), [
            'password' => 'salah 123',
            'password_confirmation' => 'salah 123',
        ]);
        $invalidAttempt->assertSessionHasErrors(['password']);

        // Step D: Masukkan password baru yang kuat dan valid (Dewi#2026!)
        $validAttempt = $this->withSession([
            'force_update_password' => session('force_update_password')
        ])->post(route('auth.force-update-password.post'), [
            'password' => 'Dewi#2026!',
            'password_confirmation' => 'Dewi#2026!',
        ]);

        // Harus dialihkan kembali ke form login dengan pesan sukses
        $validAttempt->assertRedirect(route('login'));
        $validAttempt->assertSessionHas('success');
        $validAttempt->assertSessionMissing('force_update_password');

        // Step E: Coba login dengan password lama (NISN) -> harus gagal
        $oldPassLogin = $this->post('/login', [
            'username' => '0102015638',
            'password' => '0102015638',
        ]);
        $oldPassLogin->assertSessionHas('error');

        // Step F: Login dengan password baru yang valid -> sukses masuk portal
        $newPassLogin = $this->post('/login', [
            'username' => '0102015638',
            'password' => 'Dewi#2026!',
        ]);
        $newPassLogin->assertRedirect(route('dashboard.peserta_didik'));
        $newPassLogin->assertSessionHas('user');
        $this->assertEquals('peserta_didik', session('user.role'));

        // Reset kembali ke NISN agar user dapat mencobanya secara langsung kapan saja
        $u->refresh();
        $u->password = Hash::make('0102015638');
        $u->save();
    }

    /**
     * 8. Test Update Service checkUpdate returns valid structure
     */
    public function test_update_service_check_update()
    {
        $updateService = app(\App\Services\UpdateService::class);
        $status = $updateService->checkUpdate();

        $this->assertArrayHasKey('current_version', $status);
        $this->assertArrayHasKey('has_git', $status);
        $this->assertArrayHasKey('updates_available', $status);
        $this->assertArrayHasKey('git_commit', $status);
    }
}
