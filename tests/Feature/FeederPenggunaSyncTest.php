<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\FeederReceiverController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Regresi: sinkronisasi pengguna Dapodik gagal 1062 Duplicate entry PRIMARY
 * karena akun existing ber-`sumber_data = 'manual'` dikecualikan dari daftar
 * existing, lalu di-insert ulang.
 */
class FeederPenggunaSyncTest extends TestCase
{
    public function test_pengguna_manual_existing_diperbarui_bukan_insert_ulang(): void
    {
        if (!Schema::hasTable('pengguna')) {
            $this->markTestSkipped('Tabel pengguna tidak tersedia.');
        }

        $id = (string) Str::uuid();
        $username = 'uji-feeder-' . substr($id, 0, 8) . '@example.test';

        DB::table('pengguna')->insert([
            'pengguna_id' => $id,
            'username' => $username,
            'nama' => 'Nama Lama',
            'peran_id_str' => 'Operator Sekolah',
            'password' => Hash::make('lama'),
            'ptk_id' => null,
            'peserta_didik_id' => null,
            'sumber_data' => 'manual',
            'raw_data' => json_encode(['lama' => true]),
        ]);

        $baruId = (string) Str::uuid();

        try {
            $method = new ReflectionMethod(FeederReceiverController::class, 'processPengguna');
            $method->invoke(app(FeederReceiverController::class), [
                [
                    'pengguna_id' => $id,
                    'username' => $username,
                    'nama' => 'Nama Baru',
                    'peran_id_str' => 'Operator Sekolah',
                    'ptk_id' => null,
                    'peserta_didik_id' => null,
                ],
                // Username sengaja menabrak username akun lama → wajib diganti, bukan 1062.
                [
                    'pengguna_id' => $baruId,
                    'username' => $username,
                    'nama' => 'Akun Baru',
                    'peran_id_str' => 'Guru',
                    'ptk_id' => null,
                    'peserta_didik_id' => null,
                ],
            ]);

            $lama = DB::table('pengguna')->where('pengguna_id', $id)->first();
            $this->assertNotNull($lama, 'Baris existing hilang.');
            $this->assertSame('Nama Baru', $lama->nama);
            $this->assertSame('dapodik', $lama->sumber_data);

            $baru = DB::table('pengguna')->where('pengguna_id', $baruId)->first();
            $this->assertNotNull($baru, 'Akun baru gagal dibuat.');
            $this->assertNotSame($username, $baru->username, 'Username duplikat lolos ke insert.');
        } finally {
            DB::table('pengguna')->whereIn('pengguna_id', [$id, $baruId])->delete();
        }
    }
}
