<?php

namespace Tests\Feature\Pengguna;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Admin tidak lagi memiliki section "Halaman Peran" di sidebar.
 * Admin masuk ke halaman peran lewat menu Pengguna ("Masuk sebagai"),
 * lalu dapat kembali ke peran admin.
 */
class ImpersonasiPenggunaTest extends TestCase
{
    private function admin(): ?User
    {
        return User::where('peran_id_str', 'Administrator')->first();
    }

    private function sesiAdmin(User $admin): array
    {
        return [
            'id' => $admin->pengguna_id,
            'pengguna_id' => $admin->pengguna_id,
            'name' => $admin->nama,
            'nama' => $admin->nama,
            'username' => $admin->username,
            'role' => 'admin',
            'peran_id_str' => $admin->peran_id_str,
        ];
    }

    public function test_sidebar_admin_tidak_memuat_halaman_peran(): void
    {
        $admin = $this->admin();
        if (!$admin) {
            $this->markTestSkipped('Tidak ada akun Administrator di basis data.');
        }

        $res = $this->withSession(['user' => $this->sesiAdmin($admin)])->get('/dashboard/admin');
        $res->assertStatus(200);
        $res->assertDontSee('Halaman Peran', false);
    }

    public function test_admin_bisa_masuk_sebagai_pengguna_dan_kembali(): void
    {
        $admin = $this->admin();
        if (!$admin) {
            $this->markTestSkipped('Tidak ada akun Administrator di basis data.');
        }

        $target = User::where('pengguna_id', '!=', $admin->pengguna_id)->first();
        if (!$target) {
            $this->markTestSkipped('Tidak ada pengguna target selain admin.');
        }

        $res = $this->withSession(['user' => $this->sesiAdmin($admin)])
            ->post('/dashboard/pengguna/' . $target->pengguna_id . '/masuk-sebagai');

        $res->assertRedirect(route('dashboard.' . $target->role));
        $this->assertSame($target->pengguna_id, session('user.pengguna_id'));
        $this->assertSame($admin->pengguna_id, session('impersonator'));

        // Kembali ke admin
        $back = $this->post('/dashboard/kembali-ke-admin');
        $back->assertRedirect(route('dashboard.admin'));
        $this->assertSame($admin->pengguna_id, session('user.pengguna_id'));
        $this->assertNull(session('impersonator'));
    }

    public function test_non_admin_tidak_boleh_masuk_sebagai(): void
    {
        $admin = $this->admin();
        $target = User::where('peran_id_str', '!=', 'Administrator')->first();

        if (!$admin || !$target) {
            $this->markTestSkipped('Butuh akun admin dan non-admin di basis data.');
        }

        $nonAdmin = [
            'pengguna_id' => $target->pengguna_id,
            'id' => $target->pengguna_id,
            'nama' => $target->nama,
            'name' => $target->nama,
            'role' => $target->role,
            'peran_id_str' => $target->peran_id_str,
        ];

        $res = $this->withSession(['user' => $nonAdmin])
            ->post('/dashboard/pengguna/' . $admin->pengguna_id . '/masuk-sebagai');

        $res->assertStatus(403);
    }
}
