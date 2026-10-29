<?php

namespace Tests\Feature\Teknisi;

use App\Models\RolePermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Teknisi (pemegang tugas tambahan TEKNISI_IT) berhak CRUD penuh pada
 * Sarpras: Ruang & Gedung (menu_sarpras_ruang) dan Inventaris & Aset
 * (menu_inventaris) — setara pengelola Sarpras
 * (lihat migrasi 2026_10_30_110000_set_full_crud_for_teknisi_sarpras_modules).
 */
class TeknisiSarprasCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_teknisi_dapat_tambah_ubah_hapus_ruang_dan_aset(): void
    {
        $duty = DB::table('ref_tugas_tambahan')->where('kode', 'TEKNISI_IT')->first();
        if (!$duty) {
            $this->markTestSkipped('Referensi tugas tambahan TEKNISI_IT tidak tersedia.');
        }

        $userId = (string) Str::uuid();
        DB::table('pengguna')->insert([
            'pengguna_id'  => $userId,
            'username'     => 'teknisi_sarpras_' . Str::random(8),
            'nama'         => 'Teknisi Sarpras Uji',
            'peran_id_str' => 'Tenaga Administrasi',
            'password'     => bcrypt('secret'),
        ]);
        DB::table('ptk_tugas_tambahan')->insert([
            'user_id'           => $userId,
            'tugas_tambahan_id' => $duty->id,
            'is_active'         => true,
        ]);

        RolePermission::clearRuntimeCache();

        $user = ['id' => $userId, 'pengguna_id' => $userId, 'role' => 'tendik', 'ptk_id' => null];

        foreach (['menu_sarpras_ruang', 'menu_inventaris'] as $key) {
            foreach (['read', 'create', 'update', 'delete'] as $action) {
                $this->assertTrue(
                    RolePermission::canAccess($user, $key, $action),
                    "Teknisi harus boleh '{$action}' pada {$key}."
                );
            }
        }
    }
}
