<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class FeederApiKey
{
    /**
     * Bentuk kunci API feeder: "SAE_" + 32 karakter heksadesimal acak.
     * Dibuat acak per instalasi agar tidak ada kunci bawaan yang diketahui publik.
     */
    public static function generate(): string
    {
        return 'SAE_' . bin2hex(random_bytes(16));
    }

    /**
     * Baca kunci tersimpan tanpa efek samping.
     * Dipakai jalur validasi permintaan agar tidak pernah menghasilkan kunci baru.
     */
    public static function stored(): ?string
    {
        if (!Schema::hasTable('settings')) {
            return null;
        }

        $key = DB::table('settings')->where('id', 1)->value('api_key');

        return is_string($key) && $key !== '' ? $key : null;
    }

    /**
     * Ambil kunci untuk ditampilkan ke admin, membuat otomatis bila belum ada
     * (lazy init) supaya instalasi tanpa seeder tetap memiliki kunci privat.
     */
    public static function ensure(): string
    {
        $existing = self::stored();
        if ($existing !== null) {
            return $existing;
        }

        $newKey = self::generate();
        DB::table('settings')->updateOrInsert(
            ['id' => 1],
            ['api_key' => $newKey, 'updated_at' => now()]
        );

        return $newKey;
    }
}
