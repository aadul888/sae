<?php

namespace Tests\Feature\Pengguna;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Penugasan tugas tambahan untuk PENGUNA MANUAL (di luar Dapodik).
 *
 * Alur UI: Dashboard > Manajemen Pengguna > tab Tugas Tambahan > Tambah Penugasan.
 * Form kini memakai pencarian personel (live search) sehingga pengguna yang
 * ditambahkan manual tanpa `ptk_id` tetap dapat diberi penugasan.
 */
class PersonelManualPenugasanTest extends TestCase
{
    use DatabaseTransactions;

    private array $adminSession = [
        'user' => [
            'id'           => 'test-admin',
            'pengguna_id'  => 'test-admin',
            'name'         => 'Admin Uji',
            'nama'         => 'Admin Uji',
            'username'     => 'admin_uji',
            'role'         => 'admin',
            'peran_id_str' => 'Administrator',
            'ptk_id'       => null,
        ],
    ];

    public function test_pengguna_manual_dapat_diberi_penugasan_dan_muncul_di_pencarian(): void
    {
        $tugas = DB::table('ref_tugas_tambahan')->where('kode', 'TEKNISI_IT')->first()
            ?? DB::table('ref_tugas_tambahan')->where('is_active', true)->orderBy('id')->first();
        $this->assertNotNull($tugas, 'Referensi tugas tambahan harus tersedia.');

        $userId = (string) Str::uuid();
        DB::table('pengguna')->insert([
            'pengguna_id'  => $userId,
            'username'     => 'petugas_manual_uji_' . Str::random(8),
            'nama'         => 'Petugas Manual Uji',
            'peran_id_str' => 'Tendik',
            'password'     => bcrypt('secret'),
        ]);

        // 1. Pengguna manual muncul pada pencarian personel (type=personel).
        $this->withSession($this->adminSession)
            ->getJson(route('dashboard.people.search', ['q' => 'Petugas Manual Uji', 'type' => 'personel']))
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $userId, 'nama' => 'Petugas Manual Uji']);

        // 2. Penugasan disimpan dengan target `pengguna_id` (bukan `ptk_id`).
        $this->withSession($this->adminSession)
            ->postJson(route('dashboard.pengguna.tugas-tambahan.store'), [
                'ptk_id'            => $userId,
                'tugas_tambahan_id' => $tugas->id,
            ])
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $duty = DB::table('ptk_tugas_tambahan')->where('user_id', $userId)->first();
        $this->assertNotNull($duty, 'Penugasan pengguna manual harus tersimpan.');
        $this->assertNull($duty->ptk_id, 'Pengguna manual tidak boleh memiliki ptk_id.');
        $this->assertSame((int) $tugas->id, (int) $duty->tugas_tambahan_id);
    }
}
