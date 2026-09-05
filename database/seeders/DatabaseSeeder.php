<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with default accounts.
     */
    public function run(): void
    {
        // 1. Administrator
        User::updateOrCreate(
            ['username' => 'admin@sae.id'],
            [
                'pengguna_id' => 'seed-admin-1',
                'nama' => 'Administrator',
                'username' => 'admin@sae.id',
                'peran_id_str' => 'Administrator',
                'password' => Hash::make('Admin543!'),
                'ptk_id' => null,
                'peserta_didik_id' => null,
            ]
        );

        // 2. Guru dan Tendik
        User::updateOrCreate(
            ['username' => 'gtk@sae.id'],
            [
                'pengguna_id' => 'seed-gtk-1',
                'nama' => 'Guru dan Tendik',
                'username' => 'gtk@sae.id',
                'peran_id_str' => 'PTK',
                'password' => Hash::make('Geteka543!'),
                'ptk_id' => null,
                'peserta_didik_id' => null,
            ]
        );

        // 3. Siswa
        User::updateOrCreate(
            ['username' => 'siswa@sae.id'],
            [
                'pengguna_id' => 'seed-siswa-1',
                'nama' => 'Siswa',
                'username' => 'siswa@sae.id',
                'peran_id_str' => 'Peserta Didik',
                'password' => Hash::make('Siswa543!'),
                'ptk_id' => null,
                'peserta_didik_id' => null,
            ]
        );

        // Default setting website & API Key untuk feeder (Single Row id=1)
        DB::table('settings')->updateOrInsert(
            ['id' => 1],
            [
                'app_name' => 'SAE - Sistem Aplikasi Edukasi',
                'site_name' => 'SAE - Sistem Aplikasi Edukasi',
                'api_key' => 'sae_secret_live_key_2026',
                'dapodik_url' => 'http://localhost:5774',
                'maintenance_status' => 0,
                'updated_at' => now(),
                'created_at' => now()
            ]
        );
    }
}

