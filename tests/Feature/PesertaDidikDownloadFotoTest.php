<?php

namespace Tests\Feature;

use App\Models\PesertaDidikMeta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PesertaDidikDownloadFotoTest extends TestCase
{
    public function test_guest_cannot_download_foto(): void
    {
        $response = $this->get('/dashboard/manajemen-data/peserta-didik-aktif/test-id/download-foto');
        $this->assertTrue(in_array($response->getStatusCode(), [302, 401, 403]));
    }

    public function test_admin_gets_404_if_foto_not_found(): void
    {
        $sessionUser = [
            'id'          => 'admin-test',
            'pengguna_id' => 'admin-test',
            'username'    => 'admin',
            'nama'        => 'Administrator Test',
            'role'        => 'admin',
        ];

        // Buat atau pastikan peserta didik dummy ada
        $pdId = 'test-pd-no-foto-' . uniqid();
        DB::table('peserta_didik')->insert([
            'peserta_didik_id' => $pdId,
            'nama'             => 'Siswa Uji Coba',
            'nisn'             => '1234567890',
            'jenis_kelamin'    => 'L',
        ]);

        try {
            $response = $this->withSession(['user' => $sessionUser])
                ->get('/dashboard/manajemen-data/peserta-didik-aktif/' . $pdId . '/download-foto');

            $response->assertStatus(404);
        } finally {
            DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->delete();
        }
    }

    public function test_admin_can_download_existing_foto(): void
    {
        $sessionUser = [
            'id'          => 'admin-test',
            'pengguna_id' => 'admin-test',
            'username'    => 'admin',
            'nama'        => 'Administrator Test',
            'role'        => 'admin',
        ];

        $pdId = 'test-pd-with-foto-' . uniqid();
        $nisn = '9988776655';
        $nama = 'Budi Santoso';

        DB::table('peserta_didik')->insert([
            'peserta_didik_id' => $pdId,
            'nama'             => $nama,
            'nisn'             => $nisn,
            'jenis_kelamin'    => 'L',
        ]);

        // Siapkan dummy file gambar di storage
        $relPath = 'assets/peserta-didik/foto/test_' . $nisn . '.png';
        $fullPath = storage_path('app/public/' . $relPath);
        File::ensureDirectoryExists(dirname($fullPath));
        File::put($fullPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

        PesertaDidikMeta::create([
            'peserta_didik_id' => $pdId,
            'nisn'             => $nisn,
            'foto_path'        => $relPath,
            'foto_size'        => 68,
            'foto_width'       => 1,
            'foto_height'      => 1,
        ]);

        try {
            $response = $this->withSession(['user' => $sessionUser])
                ->get('/dashboard/manajemen-data/peserta-didik-aktif/' . $pdId . '/download-foto');

            $response->assertStatus(200);
            $response->assertHeader('content-type', 'image/png');
            $this->assertStringContainsString('Pasfoto_' . $nisn . '_Budi_Santoso.png', $response->headers->get('content-disposition'));
        } finally {
            DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->delete();
            PesertaDidikMeta::where('peserta_didik_id', $pdId)->delete();
            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }
        }
    }
}
