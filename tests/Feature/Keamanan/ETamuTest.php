<?php

namespace Tests\Feature\Keamanan;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ETamuTest extends TestCase
{
    public function test_public_form_can_be_accessed(): void
    {
        $response = $this->get(route('tamu.public'));
        $response->assertStatus(200);
        $response->assertSee('Buku Tamu Digital');
    }

    public function test_api_check_student_by_parent_nik_or_nisn(): void
    {
        // 1. Cek via NIK Orang Tua jika ada data di peserta_didik_identitas
        $sampleIdentitas = DB::table('peserta_didik_identitas')
            ->whereNotNull('nik_ayah')
            ->first();

        if ($sampleIdentitas) {
            $response = $this->get(route('tamu.check-student', ['nik_ortu' => $sampleIdentitas->nik_ayah]));
            $response->assertStatus(200);
            $response->assertJson([
                'success' => true,
                'found' => true,
            ]);
        }

        // 2. Cek via NISN Siswa
        $samplePd = DB::table('peserta_didik')->whereNotNull('nisn')->first();
        if ($samplePd) {
            $response = $this->get(route('tamu.check-student', ['nisn_siswa' => $samplePd->nisn]));
            $response->assertStatus(200);
            $response->assertJson([
                'success' => true,
                'found' => true,
            ]);
        }
    }

    public function test_guest_can_submit_mandiri_and_see_ticket(): void
    {
        $payload = [
            'nama_tamu' => 'Tamu Percobaan E-Tamu',
            'jenis_tamu' => 'orang_tua',
            'nik_tamu' => '3200000000000001',
            'nisn_siswa' => '9999999999',
            'nama_siswa' => 'Siswa Test',
            'rombel_siswa' => 'X Test 1',
            'nomor_kontak' => '08999999999',
            'tujuan_bertemu' => 'Wali Kelas',
            'keperluan' => 'Konsultasi Siswa',
            'nomor_polisi_kendaraan' => 'B 1234 TEST',
        ];

        $response = $this->post(route('tamu.submit'), $payload);
        $latest = DB::table('keamanan_buku_tamu')->where('nama_tamu', 'Tamu Percobaan E-Tamu')->latest('id')->first();
        $this->assertNotNull($latest);
        $this->assertEquals(1, $latest->is_mandiri);
        $this->assertEquals('orang_tua', $latest->jenis_tamu);

        $response->assertRedirect(route('tamu.success', $latest->id));

        $ticketResponse = $this->get(route('tamu.success', $latest->id));
        $ticketResponse->assertStatus(200);
        $ticketResponse->assertSee('Tamu Percobaan E-Tamu');
        $ticketResponse->assertSee('Siswa Test');

        // Clean up
        DB::table('keamanan_buku_tamu')->where('id', $latest->id)->delete();
    }
}
