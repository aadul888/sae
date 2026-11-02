<?php

namespace Tests\Feature;

use App\Models\RolePermission;
use Tests\TestCase;

class EIzinModuleTest extends TestCase
{
    public function test_e_izin_in_permission_dictionary(): void
    {
        $permissions = RolePermission::getBasePermissions();
        $keys = collect($permissions)->flatMap(fn (array $items) => array_keys($items));

        $this->assertContains('menu_e_izin', $keys->all());
    }

    public function test_guest_is_redirected_from_e_izin(): void
    {
        $this->get(route('dashboard.e-izin.index'))
            ->assertStatus(302)
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_access_e_izin_index(): void
    {
        $sessionUser = [
            'id'          => 'admin-test',
            'pengguna_id' => 'admin-test',
            'username'    => 'admin',
            'nama'        => 'Administrator Test',
            'role'        => 'admin',
        ];

        $res = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.e-izin.index'));

        $res->assertStatus(200);
        $res->assertSee('e-Izin');
    }

    public function test_peserta_didik_can_access_e_izin_index(): void
    {
        $sessionUser = [
            'id'               => 'siswa-test-1',
            'pengguna_id'      => 'siswa-test-1',
            'username'         => '0051234567',
            'nama'             => 'Siswa Test',
            'role'             => 'peserta_didik',
            'peserta_didik_id' => 'pd-test-uuid',
        ];

        $res = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.e-izin.index'));

        $res->assertStatus(200);
        $res->assertSee('e-Izin');
    }

    public function test_wali_kelas_can_access_e_izin_index(): void
    {
        $sessionUser = [
            'id'          => 'wali-test-1',
            'pengguna_id' => 'wali-test-1',
            'username'    => 'guruwali',
            'nama'        => 'Guru Wali Test',
            'role'        => 'guru',
            'ptk_id'      => 'ptk-wali-uuid',
        ];

        $res = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.e-izin.index'));

        $res->assertStatus(200);
        $res->assertSee('e-Izin');
    }

    public function test_orang_tua_dashboard_loads_without_error(): void
    {
        $sessionUser = [
            'id'          => 'ortu-test-1',
            'pengguna_id' => 'ortu-test-1',
            'username'    => 'ortutest',
            'nama'        => 'Orang Tua Test',
            'role'        => 'orang_tua',
        ];

        $res = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.orang_tua'));

        $res->assertStatus(200);
    }

    public function test_admin_can_search_siswa(): void
    {
        $sessionUser = [
            'id'          => 'admin-test',
            'pengguna_id' => 'admin-test',
            'username'    => 'admin',
            'nama'        => 'Administrator Test',
            'role'        => 'admin',
        ];

        $res = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.e-izin.search-siswa', ['q' => 'test']));

        $res->assertStatus(200);
        $this->assertIsArray($res->json());
    }

    public function test_scan_tiket_not_found(): void
    {
        $sessionUser = [
            'id'          => 'admin-test',
            'pengguna_id' => 'admin-test',
            'username'    => 'admin',
            'nama'        => 'Administrator Test',
            'role'        => 'admin',
        ];

        $res = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.e-izin.scan-tiket', ['keyword' => 'NONEXISTENT123']));

        $res->assertStatus(404);
        $this->assertSame('error', $res->json('status'));
    }

    public function test_store_validation_fails_on_empty_request(): void
    {
        $sessionUser = [
            'id'          => 'admin-test',
            'pengguna_id' => 'admin-test',
            'username'    => 'admin',
            'nama'        => 'Administrator Test',
            'role'        => 'admin',
        ];

        $res = $this->withSession(['user' => $sessionUser])
            ->post(route('dashboard.e-izin.store'), [], ['Accept' => 'application/json']);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['jenis_izin', 'alasan', 'jam_izin_keluar']);
    }

    public function test_kiosk_view_loads_successfully(): void
    {
        $res = $this->get(route('kiosk.e-izin'));
        $res->assertStatus(200);
        $res->assertSee('Pos Gerbang e-Izin');
        $res->assertSee('KIOSK VERIFIKASI');
        $res->assertSee('kioskActivityChart');
        $res->assertSee('videoPreview');
        $res->assertSee('btnToggleFullscreen');

        $resAlt = $this->get(route('e-izin.kiosk'));
        $resAlt->assertStatus(200);
    }

    public function test_kiosk_live_log_returns_json(): void
    {
        $res = $this->get(route('e-izin.kiosk.live-log'));
        $res->assertStatus(200);
        $res->assertJsonStructure([
            'status',
            'stats' => ['total', 'di_luar', 'kembali', 'pulang'],
            'logs',
        ]);
    }

    public function test_kiosk_scan_handles_empty_input(): void
    {
        $res = $this->postJson(route('e-izin.kiosk.scan'), ['raw_input' => '']);
        $res->assertStatus(422);
        $res->assertJsonFragment(['status' => 'error', 'title' => 'Input Kosong']);
    }

    public function test_kiosk_scan_handles_unregistered_code(): void
    {
        $res = $this->postJson(route('e-izin.kiosk.scan'), ['raw_input' => 'TIDAK-TERDAFTAR-9999']);
        $res->assertStatus(404);
        $res->assertJsonFragment(['status' => 'error', 'title' => 'Identitas Tidak Dikenali']);
    }

    public function test_sop_flow_siswa_wali_piket_satpam(): void
    {
        $siswa = \Illuminate\Support\Facades\DB::table('peserta_didik')->first();
        if (!$siswa) {
            $this->markTestSkipped('Data peserta didik kosong.');
        }

        // 1. Siswa mengajukan izin keluar sebentar tanpa mengisi rencana kembali
        $sessionSiswa = [
            'id'               => 'test-pd-' . $siswa->peserta_didik_id,
            'pengguna_id'      => 'test-pd-' . $siswa->peserta_didik_id,
            'username'         => $siswa->nisn ?: 'siswa-test',
            'nama'             => $siswa->nama,
            'role'             => 'peserta_didik',
            'peserta_didik_id' => $siswa->peserta_didik_id,
        ];

        $resStore = $this->withSession(['user' => $sessionSiswa])
            ->postJson(route('dashboard.e-izin.store'), [
                'peserta_didik_id' => $siswa->peserta_didik_id,
                'jenis_izin'       => 'keluar_sebentar',
                'jam_izin_keluar'  => '10:00',
                'alasan'           => 'Keperluan fotokopi modul KBM di depan sekolah',
            ]);

        $resStore->assertStatus(200);

        // Ambil tiket yang baru dibuat
        $tiket = \Illuminate\Support\Facades\DB::table('peserta_didik_izin_keluar')
            ->where('peserta_didik_id', $siswa->peserta_didik_id)
            ->latest('id')
            ->first();

        $this->assertNotNull($tiket);
        $this->assertSame('menunggu_wali', $tiket->status);
        $this->assertSame('menunggu', $tiket->wali_status);
        $this->assertSame('10:10:00', $tiket->jam_rencana_kembali, 'Default 10 menit wajib diaplikasikan');

        // 2. Guru Piket mencoba approve SEBELUM Wali Kelas menyetujui -> Wajib Ditolak SOP
        $sessionPiket = [
            'id'          => 'piket-test-1',
            'pengguna_id' => 'piket-test-1',
            'username'    => 'gurupiket',
            'nama'        => 'Guru Piket Sekolah',
            'role'        => 'guru',
            'ptk_id'      => 'ptk-piket-uuid',
        ];

        $resPiketPremature = $this->withSession(['user' => $sessionPiket])
            ->postJson(route('dashboard.e-izin.approve-piket', $tiket->id));
        $resPiketPremature->assertStatus(400);

        // 3. Security Satpam mencoba checkout SEBELUM disetujui piket -> Wajib Ditolak SOP
        $sessionSatpam = [
            'id'          => 'satpam-test-1',
            'pengguna_id' => 'satpam-test-1',
            'username'    => 'satpam',
            'nama'        => 'Petugas Security',
            'role'        => 'security',
        ];

        $resSatpamPremature = $this->withSession(['user' => $sessionSatpam])
            ->postJson(route('dashboard.e-izin.checkout-security', $tiket->id));
        $resSatpamPremature->assertStatus(400);

        // 4. Wali Kelas menyetujui tiket
        $sessionWali = [
            'id'          => 'wali-test-1',
            'pengguna_id' => 'wali-test-1',
            'username'    => 'wali1',
            'nama'        => 'Guru Wali Kelas',
            'role'        => 'guru',
            'ptk_id'      => $tiket->wali_kelas_ptk_id ?: 'ptk-wali-uuid',
        ];

        $resWaliApprove = $this->withSession(['user' => $sessionWali])
            ->postJson(route('dashboard.e-izin.approve-wali', $tiket->id), ['catatan' => 'Diizinkan fotokopi']);
        $resWaliApprove->assertStatus(200);

        $tiketAfterWali = \Illuminate\Support\Facades\DB::table('peserta_didik_izin_keluar')->where('id', $tiket->id)->first();
        $this->assertSame('menunggu_piket', $tiketAfterWali->status);
        $this->assertSame('disetujui', $tiketAfterWali->wali_status);

        // 5. Guru Piket menyetujui tiket
        $resPiketApprove = $this->withSession(['user' => $sessionPiket])
            ->postJson(route('dashboard.e-izin.approve-piket', $tiket->id), ['catatan' => 'ACC Piket']);
        $resPiketApprove->assertStatus(200);

        $tiketAfterPiket = \Illuminate\Support\Facades\DB::table('peserta_didik_izin_keluar')->where('id', $tiket->id)->first();
        $this->assertSame('menunggu_satpam', $tiketAfterPiket->status);
        $this->assertSame('disetujui', $tiketAfterPiket->piket_status);

        // 6. Security Gerbang verifikasi keluar (Checkout via Kiosk Scan)
        $resKioskCheckout = $this->postJson(route('e-izin.kiosk.scan'), ['raw_input' => $tiket->nomor_tiket]);
        $resKioskCheckout->assertStatus(200);
        $resKioskCheckout->assertJsonFragment(['action' => 'checkout']);

        $tiketAfterCheckout = \Illuminate\Support\Facades\DB::table('peserta_didik_izin_keluar')->where('id', $tiket->id)->first();
        $this->assertSame('di_luar', $tiketAfterCheckout->status);
        $this->assertNotNull($tiketAfterCheckout->satpam_checkout_at);

        // 7. Security Gerbang verifikasi kembali masuk (Checkin via Kiosk Scan)
        $resKioskCheckin = $this->postJson(route('e-izin.kiosk.scan'), ['raw_input' => $tiket->nomor_tiket]);
        $resKioskCheckin->assertStatus(200);
        $resKioskCheckin->assertJsonFragment(['action' => 'checkin']);

        $tiketAfterCheckin = \Illuminate\Support\Facades\DB::table('peserta_didik_izin_keluar')->where('id', $tiket->id)->first();
        $this->assertSame('kembali', $tiketAfterCheckin->status);
        $this->assertNotNull($tiketAfterCheckin->satpam_checkin_at);

        // 8. Cek Detail Pass Digital (JSON dengan QR & Barcode SVG)
        $resDetail = $this->withSession(['user' => $sessionSiswa])
            ->getJson(route('dashboard.e-izin.show', $tiket->id));
        $resDetail->assertStatus(200);
        $this->assertNotEmpty($resDetail->json('qr_svg'));
        $this->assertNotEmpty($resDetail->json('barcode_svg'));

        // 9. Cetak Fisik Slip (Tanpa Kop Surat Instansi)
        $resCetak = $this->withSession(['user' => $sessionSiswa])
            ->get(route('dashboard.e-izin.cetak', $tiket->id));
        $resCetak->assertStatus(200);
        $resCetak->assertSee('SLIP e-IZIN PESERTA DIDIK');
        $resCetak->assertDontSee('NPSN:');

        // Cleanup
        \Illuminate\Support\Facades\DB::table('peserta_didik_izin_keluar')->where('id', $tiket->id)->delete();
    }

    public function test_home_page_contains_e_izin_in_service_menu(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $res->assertSee(route('kiosk.e-izin'), false);
        $res->assertSee('Terminal Kiosk e-Izin');
        $res->assertSee('e-Izin &amp; Pos Gerbang', false);
    }
}
