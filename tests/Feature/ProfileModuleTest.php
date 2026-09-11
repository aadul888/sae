<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProfileModuleTest extends TestCase
{
    public function test_guest_cannot_access_profile(): void
    {
        $response = $this->get(route('dashboard.profile'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_profile_page(): void
    {
        $user = User::where('username', 'opsmakpal@gmail.com')->first() ?? User::first();
        $this->assertNotNull($user, 'User must exist');

        $sessionData = [
            'id' => $user->pengguna_id,
            'pengguna_id' => $user->pengguna_id,
            'name' => $user->name ?? $user->nama,
            'nama' => $user->nama,
            'username' => $user->username,
            'role' => $user->role,
            'foto_url' => null,
        ];

        $response = $this->withSession(['user' => $sessionData])
            ->get(route('dashboard.profile'));

        $response->assertStatus(200);
        $response->assertSee('Data Informasi Akun');
        $response->assertSee('Keamanan &amp; Ubah Kata Sandi', false);
        $response->assertSee('Ketentuan Pembuatan Kata Sandi');
        $response->assertSee('Perbarui Kata Sandi');
    }

    public function test_header_contains_notif_and_user_dropdown_with_required_items(): void
    {
        $user = User::where('username', 'opsmakpal@gmail.com')->first() ?? User::first();
        $this->assertNotNull($user, 'User must exist');

        $sessionData = [
            'id' => $user->pengguna_id,
            'pengguna_id' => $user->pengguna_id,
            'name' => $user->name ?? $user->nama,
            'nama' => $user->nama,
            'username' => $user->username,
            'role' => $user->role,
            'foto_url' => null,
        ];

        $response = $this->withSession(['user' => $sessionData])
            ->get(route('dashboard.admin'));

        $response->assertStatus(200);
        // Cek bahwa 2 komponen ada di header
        $response->assertSee('id="notifBellBtn"', false);
        $response->assertSee('id="userMenuBtn"', false);

        // Cek bahwa di dalam user dropdown ada Profil, Mode Gelap/Terang, dan Logout
        $response->assertSee(route('dashboard.profile'));
        $response->assertSee('id="dropdownThemeToggle"', false);
        $response->assertSee('id="headerLogoutForm"', false);
    }

    public function test_sidebar_displays_user_photo_when_available(): void
    {
        $user = User::where('username', 'opsmakpal@gmail.com')->first() ?? User::first();
        $this->assertNotNull($user, 'User must exist');

        $sessionData = [
            'id' => $user->pengguna_id,
            'pengguna_id' => $user->pengguna_id,
            'name' => $user->name ?? $user->nama,
            'nama' => $user->nama,
            'username' => $user->username,
            'role' => $user->role,
            'foto_url' => 'https://example.com/avatar.jpg',
        ];

        $response = $this->withSession(['user' => $sessionData])
            ->get(route('dashboard.admin'));

        $response->assertStatus(200);
        $response->assertSee('dash-sidebar-avatar-img', false);
        $response->assertSee('https://example.com/avatar.jpg', false);
    }

    public function test_update_contact_info(): void
    {
        $user = User::where('username', 'opsmakpal@gmail.com')->first() ?? User::first();
        $this->assertNotNull($user, 'User must exist');

        $originalHp = $user->no_hp;
        $originalAlamat = $user->alamat;

        $sessionData = [
            'id' => $user->pengguna_id,
            'pengguna_id' => $user->pengguna_id,
            'name' => $user->name ?? $user->nama,
            'nama' => $user->nama,
            'username' => $user->username,
            'role' => $user->role,
        ];

        $response = $this->withSession(['user' => $sessionData])
            ->put(route('dashboard.profile.contact'), [
                'no_hp' => '081234567899',
                'alamat' => 'Jl. Pendidikan No. 123',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals('081234567899', $user->no_hp);
        $this->assertEquals('Jl. Pendidikan No. 123', $user->alamat);

        // Kembalikan data original
        $user->no_hp = $originalHp;
        $user->alamat = $originalAlamat;
        $user->save();
    }

    public function test_update_password_rejects_wrong_current_password(): void
    {
        $user = User::where('username', 'opsmakpal@gmail.com')->first() ?? User::first();
        $this->assertNotNull($user, 'User must exist');

        $sessionData = [
            'id' => $user->pengguna_id,
            'pengguna_id' => $user->pengguna_id,
            'name' => $user->name ?? $user->nama,
            'nama' => $user->nama,
            'username' => $user->username,
            'role' => $user->role,
        ];

        $response = $this->withSession(['user' => $sessionData])
            ->put(route('dashboard.profile.password'), [
                'current_password' => 'WrongPassword123!',
                'password' => 'NewSecret123!@#',
                'password_confirmation' => 'NewSecret123!@#',
            ]);

        $response->assertSessionHasErrors(['current_password']);
    }

    public function test_update_password_rejects_weak_password(): void
    {
        $user = User::where('username', 'opsmakpal@gmail.com')->first() ?? User::first();
        $this->assertNotNull($user, 'User must exist');

        $sessionData = [
            'id' => $user->pengguna_id,
            'pengguna_id' => $user->pengguna_id,
            'name' => $user->name ?? $user->nama,
            'nama' => $user->nama,
            'username' => $user->username,
            'role' => $user->role,
        ];

        // Tidak ada simbol khusus, ada spasi
        $response = $this->withSession(['user' => $sessionData])
            ->put(route('dashboard.profile.password'), [
                'current_password' => 'Admin116@',
                'password' => 'pass word',
                'password_confirmation' => 'pass word',
            ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_update_password_success(): void
    {
        $tempUser = User::create([
            'pengguna_id' => (string) Str::uuid(),
            'username' => 'testuser_' . time(),
            'nama' => 'Test Profile User',
            'peran_id_str' => 'Guru',
            'password' => Hash::make('OldSecret123!@#'),
        ]);

        $sessionData = [
            'id' => $tempUser->pengguna_id,
            'pengguna_id' => $tempUser->pengguna_id,
            'name' => $tempUser->name,
            'nama' => $tempUser->nama,
            'username' => $tempUser->username,
            'role' => $tempUser->role,
        ];

        $response = $this->withSession(['user' => $sessionData])
            ->put(route('dashboard.profile.password'), [
                'current_password' => 'OldSecret123!@#',
                'password' => 'NewSecret123!@#',
                'password_confirmation' => 'NewSecret123!@#',
            ]);

        $response->assertSessionHas('success');

        $tempUser->refresh();
        $this->assertTrue(Hash::check('NewSecret123!@#', $tempUser->password));
        $this->assertNotNull($tempUser->password_updated_at);

        // Clean up
        $tempUser->delete();
    }
}
