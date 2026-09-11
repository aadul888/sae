<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class PasswordUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Setup user Repan Maulana dengan default password NISN
        $u = User::where('username', '0095591334')->first();
        if ($u) {
            $u->password = Hash::make('0095591334');
            $u->password_updated_at = null;
            $u->raw_data = null;
            $u->save();
        }
    }

    public function test_nisn_form_exists_on_homepage()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('nisnCounterBadge');
        $response->assertSee('0 / 10 digit');
        $response->assertSee('nisnInput');
    }

    public function test_login_with_default_nisn_password_redirects_to_force_update()
    {
        $response = $this->post('/login', [
            'username' => '0095591334',
            'password' => '0095591334',
        ]);

        $response->assertRedirect(route('auth.force-update-password'));
        $response->assertSessionHas('force_update_password');
        $response->assertSessionMissing('user'); // Belum boleh masuk portal!
    }

    public function test_force_update_page_displays_student_info_and_live_parameters()
    {
        $response = $this->withSession([
            'force_update_password' => [
                'pengguna_id' => '000acd1a-3c31-11e5-93af-5b5991457f3c',
                'nama' => 'Repan Maulana',
                'nisn' => '0095591334',
                'username' => '0095591334',
            ]
        ])->get(route('auth.force-update-password'));

        $response->assertStatus(200);
        $response->assertSee('Pembaruan Password Wajib');
        $response->assertSee('Repan Maulana');
        $response->assertSee('0095591334');
        $response->assertSee('Parameter Keamanan Password');
        $response->assertSee('8 - 15 karakter');
        $response->assertSee('A-z');
        $response->assertSee('0-9');
        $response->assertSee('!@#');
    }

    public function test_force_update_rejects_invalid_password()
    {
        $response = $this->withSession([
            'force_update_password' => [
                'pengguna_id' => '000acd1a-3c31-11e5-93af-5b5991457f3c',
                'nama' => 'Repan Maulana',
                'nisn' => '0095591334',
                'username' => '0095591334',
            ]
        ])->post(route('auth.force-update-password.post'), [
            'password' => '12345', // Kurang dari 8
            'password_confirmation' => '12345',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_force_update_rejects_password_with_spaces()
    {
        $response = $this->withSession([
            'force_update_password' => [
                'pengguna_id' => '000acd1a-3c31-11e5-93af-5b5991457f3c',
                'nama' => 'Repan Maulana',
                'nisn' => '0095591334',
                'username' => '0095591334',
            ]
        ])->post(route('auth.force-update-password.post'), [
            'password' => 'Siswa 2026!#', // Mengandung spasi
            'password_confirmation' => 'Siswa 2026!#',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_force_update_success_redirects_to_login_and_allows_entry()
    {
        $user = User::where('username', '0095591334')->first();

        $response = $this->withSession([
            'force_update_password' => [
                'pengguna_id' => $user->pengguna_id,
                'nama' => $user->nama,
                'nisn' => '0095591334',
                'username' => '0095591334',
            ]
        ])->post(route('auth.force-update-password.post'), [
            'password' => 'Siswa#2026!',
            'password_confirmation' => 'Siswa#2026!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');
        $response->assertSessionMissing('force_update_password');

        // Pastikan password baru tersimpan di database
        $user->refresh();
        $this->assertTrue(Hash::check('Siswa#2026!', $user->password));

        // Sekarang login dengan password baru
        $loginResponse = $this->post('/login', [
            'username' => '0095591334',
            'password' => 'Siswa#2026!',
        ]);

        $loginResponse->assertRedirect(route('dashboard.peserta_didik'));
        $loginResponse->assertSessionHas('user');
    }
}
