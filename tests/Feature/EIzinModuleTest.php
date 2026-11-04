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
        $rombel = \Illuminate\Support\Facades\DB::table('rombongan_belajar')->whereNotNull('ptk_id')->first();
        $ptkId = $rombel?->ptk_id ?? 'ptk-wali-uuid';
        if (!$rombel) {
            \Illuminate\Support\Facades\DB::table('rombongan_belajar')->insert([
                'rombongan_belajar_id' => 'rombel-test-uuid',
                'nama' => 'XII RPL 1 Test',
                'tingkat_pendidikan_id' => 12,
                'semester_id' => '20261',
                'ptk_id' => $ptkId,
            ]);
        }

        $sessionUser = [
            'id'          => 'wali-test-1',
            'pengguna_id' => 'wali-test-1',
            'username'    => 'guruwali',
            'nama'        => 'Guru Wali Test',
            'role'        => 'guru',
            'ptk_id'      => $ptkId,
        ];

        $res = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.e-izin.index'));

        $res->assertStatus(200);
        $res->assertSee('e-Izin');
    }

    public function test_guru_without_duty_cannot_access_e_izin_index(): void
    {
        $sessionUser = [
            'id'          => 'guru-plain-test-1',
            'pengguna_id' => 'guru-plain-test-1',
            'username'    => 'guruplain',
            'nama'        => 'Guru Reguler Test',
            'role'        => 'guru',
            'ptk_id'      => 'ptk-non-existent-99999',
        ];

        $res = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.e-izin.index'));

        $res->assertStatus(302);
        $res->assertRedirect(route('dashboard.guru'));
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

    public function test_guest_is_redirected_to_kiosk_auth(): void
    {
        $res = $this->get(route('kiosk.e-izin'));
        $res->assertStatus(302);
        $res->assertRedirect(route('kiosk.e-izin.auth'));
    }

    public function test_kiosk_auth_page_loads_successfully(): void
    {
        $res = $this->get(route('kiosk.e-izin.auth'));
        $res->assertStatus(200);
        $res->assertSee('Otorisasi Terminal');
        $res->assertSee('Pos Gerbang e-Izin');
        $res->assertSee('inputKodeAkses');
    }

    public function test_kiosk_unlock_fails_on_invalid_code(): void
    {
        $res = $this->postJson(route('e-izin.kiosk.unlock'), ['kode_akses' => 'WRONG-PIN-999']);
        $res->assertStatus(422);
        $res->assertJsonFragment(['status' => 'error']);
    }

    public function test_kiosk_unlock_succeeds_with_pin(): void
    {
        $res = $this->postJson(route('e-izin.kiosk.unlock'), ['kode_akses' => 'SAE123']);
        $res->assertStatus(200);
        $res->assertJsonFragment(['status' => 'success']);
        $res->assertSessionHas('e_izin_kiosk_granted', true);
    }

    public function test_kiosk_unlock_succeeds_with_staff_rfid(): void
    {
        // Temukan atau buat staff dengan rfid_uid
        $staff = \App\Models\User::whereNotNull('rfid_uid')->where('rfid_uid', '!=', '')->first();
        if (!$staff) {
            $staff = \App\Models\User::first();
            if ($staff) {
                $staff->rfid_uid = 'RFID-STAFF-TEST-9988';
                $staff->save();
            }
        }

        if (!$staff) {
            $this->markTestSkipped('Tidak ada user staff.');
        }

        $res = $this->postJson(route('e-izin.kiosk.unlock'), ['kode_akses' => $staff->rfid_uid]);
        $res->assertStatus(200);
        $res->assertJsonFragment(['status' => 'success']);
        $res->assertSessionHas('e_izin_kiosk_granted', true);
        $res->assertSessionHas('user');
    }

    public function test_kiosk_lock_clears_session_and_redirects(): void
    {
        $res = $this->withSession(['e_izin_kiosk_granted' => true])
            ->get(route('e-izin.kiosk.lock'));

        $res->assertStatus(302);
        $res->assertRedirect(route('kiosk.e-izin.auth'));
        $res->assertSessionMissing('e_izin_kiosk_granted');
    }

    public function test_kiosk_view_loads_successfully(): void
    {
        $res = $this->withSession(['e_izin_kiosk_granted' => true])
            ->get(route('kiosk.e-izin'));
        $res->assertStatus(200);
        $res->assertSee('Pos Gerbang e-Izin');
        $res->assertSee('KIOSK VERIFIKASI');
        $res->assertSee('kioskActivityChart');
        $res->assertSee('videoPreview');
        $res->assertSee('btnToggleFullscreen');
        $res->assertSee('btnKioskLock');

        $resAlt = $this->withSession(['e_izin_kiosk_granted' => true])
            ->get(route('e-izin.kiosk'));
        $resAlt->assertStatus(200);
    }

    public function test_kiosk_live_log_returns_json(): void
    {
        $res = $this->withSession(['e_izin_kiosk_granted' => true])
            ->get(route('e-izin.kiosk.live-log'));
        $res->assertStatus(200);
        $res->assertJsonStructure([
            'status',
            'stats' => ['total', 'di_luar', 'kembali', 'pulang'],
            'logs',
        ]);
    }

    public function test_kiosk_scan_handles_empty_input(): void
    {
        $res = $this->withSession(['e_izin_kiosk_granted' => true])
            ->postJson(route('e-izin.kiosk.scan'), ['raw_input' => '']);
        $res->assertStatus(422);
        $res->assertJsonFragment(['status' => 'error', 'title' => 'Input Kosong']);
    }

    public function test_kiosk_scan_handles_unregistered_code(): void
    {
        $res = $this->withSession(['e_izin_kiosk_granted' => true])
            ->postJson(route('e-izin.kiosk.scan'), ['raw_input' => 'TIDAK-TERDAFTAR-9999']);
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
        $resKioskCheckout = $this->withSession(['e_izin_kiosk_granted' => true])
            ->postJson(route('e-izin.kiosk.scan'), ['raw_input' => $tiket->nomor_tiket]);
        $resKioskCheckout->assertStatus(200);
        $resKioskCheckout->assertJsonFragment(['action' => 'checkout']);

        $tiketAfterCheckout = \Illuminate\Support\Facades\DB::table('peserta_didik_izin_keluar')->where('id', $tiket->id)->first();
        $this->assertSame('di_luar', $tiketAfterCheckout->status);
        $this->assertNotNull($tiketAfterCheckout->satpam_checkout_at);

        // 7. Security Gerbang verifikasi kembali masuk (Checkin via Kiosk Scan)
        $resKioskCheckin = $this->withSession(['e_izin_kiosk_granted' => true])
            ->postJson(route('e-izin.kiosk.scan'), ['raw_input' => $tiket->nomor_tiket]);
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
        $res->assertSee(route('presensi.scan'), false);
        $res->assertSee(route('perpustakaan.index'), false);
        $res->assertSee(route('kiosk.e-izin'), false);
        $res->assertSee(route('tamu.public'), false);
        $res->assertSee('Terminal Kiosk e-Izin');
        $res->assertSee('Buku Tamu (e-Tamu)');
        $res->assertSee('e-Izin &amp; Pos Gerbang', false);
    }

    public function test_public_e_izin_portal_page_loads_with_layout_app(): void
    {
        $res = $this->get(route('e-izin.public'));
        $res->assertStatus(200);
        $res->assertSee('Layanan e-Izin');
        $res->assertSee('Pos Gerbang Digital');
        $res->assertSee('Lacak Tiket e-Izin');
        $res->assertSee('formCekTiket');
        $res->assertSee('Alur Terpadu 4 Tahap e-Izin');
        $res->assertSee('e-izin-publik.js');
    }

    public function test_public_cek_tiket_handles_validation_and_not_found(): void
    {
        // 1. Keyword kosong
        $resEmpty = $this->postJson(route('e-izin.cek-tiket'), ['keyword' => '']);
        $resEmpty->assertStatus(422);
        $resEmpty->assertJsonFragment(['status' => 'error']);

        // 2. Keyword tidak ditemukan
        $resNotFound = $this->postJson(route('e-izin.cek-tiket'), ['keyword' => 'NON-EXISTENT-TIKET-9999']);
        $resNotFound->assertStatus(404);
        $resNotFound->assertJsonFragment(['status' => 'error']);
    }

    public function test_public_cek_tiket_finds_active_ticket(): void
    {
        $siswa = \Illuminate\Support\Facades\DB::table('peserta_didik')->first();
        if (!$siswa) {
            $this->markTestSkipped('Data peserta didik kosong.');
        }

        // Buat dummy tiket
        $nomorTiket = 'IZN-' . date('Ymd') . '-TEST';
        $id = \Illuminate\Support\Facades\DB::table('peserta_didik_izin_keluar')->insertGetId([
            'peserta_didik_id' => $siswa->peserta_didik_id,
            'rombel_id' => $siswa->rombongan_belajar_id,
            'nomor_tiket' => $nomorTiket,
            'jenis_izin' => 'keluar_sebentar',
            'alasan' => 'Uji publik lacak tiket',
            'tanggal' => date('Y-m-d'),
            'jam_izin_keluar' => '09:00:00',
            'jam_rencana_kembali' => '09:30:00',
            'status' => 'disetujui',
            'wali_status' => 'disetujui',
            'piket_status' => 'disetujui',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Cek via nomor tiket
        $resTiket = $this->postJson(route('e-izin.cek-tiket'), ['keyword' => $nomorTiket]);
        $resTiket->assertStatus(200);
        $resTiket->assertJsonFragment(['status' => 'success', 'nomor_tiket' => $nomorTiket]);
        $this->assertNotEmpty($resTiket->json('qr_svg'));

        // Cek via NISN jika siswa punya NISN
        if (!empty($siswa->nisn)) {
            $resNisn = $this->postJson(route('e-izin.cek-tiket'), ['keyword' => $siswa->nisn]);
            $resNisn->assertStatus(200);
            $resNisn->assertJsonFragment(['status' => 'success']);
        }

        // Cleanup
        \Illuminate\Support\Facades\DB::table('peserta_didik_izin_keluar')->where('id', $id)->delete();
    }
}
