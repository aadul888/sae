<?php

namespace Tests\Feature\Keamanan;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ETamuTest extends TestCase
{
    public function test_public_portal_and_mandiri_form_can_be_accessed(): void
    {
        // 1. Halaman Publik e-Tamu: Statistik, QR Code & Daftar Tamu Hari Ini
        $portalResponse = $this->get(route('tamu.public'));
        $portalResponse->assertStatus(200);
        $portalResponse->assertSee('Buku Tamu Digital');
        $portalResponse->assertSee('INFO TAMU');
        $portalResponse->assertSee('Pindai QR Tamu Mandiri');
        $portalResponse->assertSee('Daftar Kunjungan Tamu Hari Ini');
        $portalResponse->assertSee('kpi-grid');
        $portalResponse->assertSee(route('tamu.form'));

        // 2. Halaman Formulir Tamu Mandiri (Akses Scan QR / Tombol Direct)
        $formResponse = $this->get(route('tamu.form'));
        $formResponse->assertStatus(200);
        $formResponse->assertSee('Formulir Tamu Mandiri');
        $formResponse->assertSee('formPublicTamu');
        $formResponse->assertSee('tamu-mandiri.js');

        $aliasResponse = $this->get('/buku-tamu/mandiri');
        $aliasResponse->assertStatus(200);

        // 3. Home page has e-tamu service card
        $homeResponse = $this->get('/');
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee('Buku Tamu (e-Tamu)');
    }

    public function test_public_portal_renders_for_logged_in_user_without_error(): void
    {
        $response = $this->withSession(['user' => ['id' => 1, 'nama' => 'User Satpam']])
            ->get(route('tamu.public'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard Pos Satpam');
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
        $ticketResponse->assertSee('KODE TIKET VISITOR');
        $ticketResponse->assertSee('#VIS-');

        // Clean up
        DB::table('keamanan_buku_tamu')->where('id', $latest->id)->delete();
    }
}
