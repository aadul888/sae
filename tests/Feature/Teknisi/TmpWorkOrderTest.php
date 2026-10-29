<?php

namespace Tests\Feature\Teknisi;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TmpWorkOrderTest extends TestCase
{
    protected function adminSession(): array
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
            'ptk_id' => $admin->ptk_id ?? null,
        ]];
    }

    public function test_wo_index_admin(): void
    {
        $this->withSession($this->adminSession())
            ->withoutExceptionHandling()
            ->get(route('dashboard.teknisi.work-order'))
            ->assertStatus(200);
    }

    public function test_wo_store_admin(): void
    {
        $this->withSession($this->adminSession())
            ->withoutExceptionHandling()
            ->post(route('dashboard.teknisi.work-order.store'), [
                'tanggal' => now()->toDateString(),
                'lokasi_unit' => 'Ruang Uji Tmp',
                'kategori_perbaikan' => 'bangunan_fisik',
                'deskripsi_kerusakan' => 'Uji coba tmp',
                'tingkat_urgensi' => 'normal',
            ])
            ->assertRedirect();
    }
}
