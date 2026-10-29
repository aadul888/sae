<?php

namespace Tests\Feature\Teknisi;

use App\Models\RolePermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Teknisi (pemegang tugas tambahan TEKNISI_IT) pada modul Sarpras:
 *  - Ruang & Gedung (menu_sarpras_ruang) dan Inventaris & Aset
 *    (menu_inventaris): hanya baca + ubah.
 *  - Tambah & hapus tetap milik pengelola Sarpras (menu_sarpras) dan Laboran,
 *    sehingga tombol/menucreate di UI tidak muncul untuk teknisi.
 */
class TeknisiSarprasCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_teknisi_hanya_boleh_baca_dan_ubah_ruang_dan_aset(): void
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
            foreach (['read', 'update'] as $action) {
                $this->assertTrue(
                    RolePermission::canAccess($user, $key, $action),
                    "Teknisi harus boleh '{$action}' pada {$key}."
                );
            }
            foreach (['create', 'delete'] as $action) {
                $this->assertFalse(
                    RolePermission::canAccess($user, $key, $action),
                    "Teknisi TIDAK boleh '{$action}' pada {$key} (khusus pengelola Sarpras)."
                );
            }
        }
    }
}
