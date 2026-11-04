<?php

namespace Tests\Feature;

use App\Services\KartuPegawaiPrintService;
use App\Support\Table;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KartuPegawaiTest extends TestCase
{
    protected ?int $createdNonAsnId = null;

    protected function tearDown(): void
    {
        if ($this->createdNonAsnId && Table::exists('gtk_non_asn')) {
            DB::table('gtk_non_asn')->where('id', $this->createdNonAsnId)->delete();
        }
        parent::tearDown();
    }

    public function test_sekolah_info_returns_valid_structure(): void
    {
        $service = app(KartuPegawaiPrintService::class);
        $info = $service->getSekolahInfo();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('nama', $info);
        $this->assertArrayHasKey('npsn', $info);
        $this->assertArrayHasKey('alamat', $info);
        $this->assertArrayHasKey('logo_url', $info);
        $this->assertNotEmpty($info['nama']);
    }

    public function test_load_employee_card_data_for_dapodik_gtk(): void
    {
        $gtk = DB::table('gtk')->first();
        if (!$gtk) {
            $this->markTestSkipped('Data GTK Dapodik kosong.');
        }

        $service = app(KartuPegawaiPrintService::class);
        $card = $service->loadEmployeeCardData($gtk->ptk_id);

        $this->assertNotNull($card);
        $this->assertEquals($gtk->ptk_id, $card['identifier']);
        $this->assertEquals($gtk->nama, $card['nama_dasar']);
        $this->assertEquals('dapodik', $card['sumber']);
        $this->assertNotEmpty($card['qr_code_svg']);
        $this->assertNotEmpty($card['barcode_svg']);
        $this->assertArrayHasKey('verify_url', $card);
    }

    public function test_load_employee_card_data_for_gtk_non_asn(): void
    {
        if (!Table::exists('gtk_non_asn')) {
            $this->markTestSkipped('Tabel gtk_non_asn belum ada.');
        }

        $this->createdNonAsnId = DB::table('gtk_non_asn')->insertGetId([
            'kode' => 'NONASN-TEST-99',
            'nama' => 'Tester Non ASN',
            'gelar_depan' => 'Dr.',
            'gelar_belakang' => 'M.Kom.',
            'jenis_kelamin' => 'L',
            'nik' => '3203998877660001',
            'jenis_ptk' => 'Tenaga Administrasi Sekolah',
            'jabatan' => 'Staff IT',
            'status' => 'aktif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(KartuPegawaiPrintService::class);

        // Test by prefixed ID nonasn:{id}
        $card = $service->loadEmployeeCardData('nonasn:' . $this->createdNonAsnId);
        $this->assertNotNull($card);
        $this->assertEquals('non_asn', $card['sumber']);
        $this->assertEquals('Tester Non ASN', $card['nama_dasar']);
        $this->assertEquals('Dr. Tester Non ASN, M.Kom.', $card['nama']);

        // Test by kode
        $cardByKode = $service->loadEmployeeCardData('NONASN-TEST-99');
        $this->assertNotNull($cardByKode);
        $this->assertEquals($card['identifier'], $cardByKode['identifier']);
    }

    public function test_load_employee_card_data_returns_null_for_non_existent(): void
    {
        $service = app(KartuPegawaiPrintService::class);
        $card = $service->loadEmployeeCardData('invalid-non-existent-id-xyz');
        $this->assertNull($card);
    }

    public function test_public_verification_endpoints(): void
    {
        $gtk = DB::table('gtk')->first();
        if (!$gtk) {
            $this->markTestSkipped('Data GTK Dapodik kosong.');
        }

        // 1. Valid ID short URL /v/p/{id}
        $resShort = $this->get('/v/p/' . $gtk->ptk_id);
        $resShort->assertOk();
        $resShort->assertSee('Verifikasi Resmi Kartu Pegawai Digital SAE');
        $resShort->assertSee($gtk->nama);

        // 2. Valid ID canonical URL /verifikasi-pegawai/{id}
        $resCanonical = $this->get('/verifikasi-pegawai/' . $gtk->ptk_id);
        $resCanonical->assertOk();
        $resCanonical->assertSee('KARTU PEGAWAI VALID');

        // 3. Invalid ID
        $resNotFound = $this->get('/v/p/tidak-ada-id-ini');
        $resNotFound->assertOk();
        $resNotFound->assertSee('DATA KARTU TIDAK DITEMUKAN');
    }

    public function test_preview_api_requires_login(): void
    {
        $res = $this->getJson('/kartu-pegawai/preview/any-id');
        $res->assertStatus(401);
    }

    public function test_preview_api_and_print_single_for_admin(): void
    {
        $gtk = DB::table('gtk')->first();
        if (!$gtk) {
            $this->markTestSkipped('Data GTK Dapodik kosong.');
        }

        $adminSession = [
            'user' => [
                'id' => 1,
                'nama' => 'Administrator Test',
                'username' => 'admin',
                'role' => 'admin',
            ],
        ];

        // 1. Preview API
        $previewRes = $this->withSession($adminSession)->getJson('/kartu-pegawai/preview/' . $gtk->ptk_id);
        $previewRes->assertOk();
        $previewRes->assertJson([
            'success' => true,
        ]);
        $this->assertNotEmpty($previewRes->json('html'));
        $this->assertEquals($gtk->nama, $previewRes->json('card.nama_dasar'));

        // 2. Print Single
        $printRes = $this->withSession($adminSession)->get(route('dashboard.kartu-pegawai.cetak-single', $gtk->ptk_id));
        $printRes->assertOk();
        $printRes->assertSee('KARTU TANDA PEGAWAI');
        $printRes->assertSee($gtk->nama);
    }

    public function test_preview_api_allows_self_and_denies_unauthorized_user(): void
    {
        $gtkList = DB::table('gtk')->limit(2)->get();
        if ($gtkList->count() < 2) {
            $this->markTestSkipped('Dibutuhkan minimal 2 data GTK untuk pengujian isolasi.');
        }

        $guru1 = $gtkList[0];
        $guru2 = $gtkList[1];

        // Guru 1 session
        $guru1Session = [
            'user' => [
                'id' => 101,
                'pengguna_id' => 'user-guru-1',
                'nama' => $guru1->nama,
                'username' => 'guru1',
                'role' => 'guru',
                'ptk_id' => $guru1->ptk_id,
            ],
        ];

        // 1. Guru 1 opens own card -> OK (200)
        $selfRes = $this->withSession($guru1Session)->getJson('/kartu-pegawai/preview/' . $guru1->ptk_id);
        $selfRes->assertOk();
        $selfRes->assertJson(['success' => true]);

        // 2. Guru 1 opens Guru 2 card -> Forbidden (403)
        $otherRes = $this->withSession($guru1Session)->getJson('/kartu-pegawai/preview/' . $guru2->ptk_id);
        $otherRes->assertStatus(403);
    }
}
