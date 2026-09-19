<?php

namespace Tests\Feature;

use App\Models\PresensiPengaturan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KioskPresensiTest extends TestCase
{
    protected string $originalKodeAkses;

    protected function setUp(): void
    {
        parent::setUp();
        // Simpan kode akses operasional pengguna agar tidak terhapus saat test berjalan
        $this->originalKodeAkses = PresensiPengaturan::getPengaturan()->kode_akses ?: 'SAE123';
        $pengaturan = PresensiPengaturan::getPengaturan();
        $pengaturan->kode_akses = 'SAE123';
        $pengaturan->save();
    }

    public function test_guest_without_access_code_redirects_to_kiosk_auth(): void
    {
        // Akses langsung ke /scan tanpa sesi kode akses
        $response = $this->get(route('presensi.scan'));
        $response->assertRedirect(route('presensi.kiosk.auth'));
    }

    public function test_kiosk_auth_page_renders_cleanly(): void
    {
        $response = $this->get(route('presensi.kiosk.auth'));
        $response->assertStatus(200);
        $response->assertSee('Otorisasi Terminal');
        $response->assertSee('KODE AKSES');
    }

    public function test_kiosk_unlock_rejects_wrong_code(): void
    {
        $response = $this->postJson(route('presensi.kiosk.unlock'), [
            'kode_akses' => 'WRONG_CODE_999',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('status', 'error');
        $this->assertFalse(session('kiosk_access_granted') === true);
    }

    public function test_kiosk_unlock_accepts_valid_code(): void
    {
        $response = $this->postJson(route('presensi.kiosk.unlock'), [
            'kode_akses' => 'SAE123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertSessionHas('kiosk_access_granted', true);
    }

    public function test_kiosk_scan_page_accessible_with_granted_session_and_manual_input_removed(): void
    {
        $response = $this->withSession(['kiosk_access_granted' => true])
            ->get(route('presensi.scan'));

        $response->assertStatus(200);
        $response->assertSee('Terminal Scanner Presensi');
        // Pastikan form manual scan sudah dihilangkan total
        $response->assertDontSee('formManualScan');
        $response->assertDontSee('manualScanInput');
        $response->assertDontSee('Ketik NISN atau scan manual...');
        // Tombol kunci terminal harus ada
        $response->assertSee('btnKioskLock');
    }

    public function test_kiosk_lock_clears_session_and_redirects(): void
    {
        $response = $this->withSession(['kiosk_access_granted' => true])
            ->get(route('presensi.kiosk.lock'));

        $response->assertRedirect(route('presensi.kiosk.auth'));
        $this->assertNull(session('kiosk_access_granted'));
    }

    public function test_admin_can_update_kiosk_access_code(): void
    {
        $admin = User::where('peran_id_str', 'LIKE', '%admin%')->orWhere('username', 'admin')->first();
        if (!$admin) {
            $admin = User::first();
        }
        $this->assertNotNull($admin);

        $sessionData = [
            'id'          => $admin->pengguna_id,
            'pengguna_id' => $admin->pengguna_id,
            'role'        => 'admin',
        ];

        $pengaturan = PresensiPengaturan::getPengaturan();

        $response = $this->withSession(['user' => $sessionData])
            ->postJson(route('dashboard.presensi.pengaturan.update'), [
                'jam_masuk_mulai'           => substr($pengaturan->jam_masuk_mulai, 0, 5),
                'jam_masuk_selesai'         => substr($pengaturan->jam_masuk_selesai, 0, 5),
                'jam_masuk_toleransi'       => substr($pengaturan->jam_masuk_toleransi, 0, 5),
                'jam_pulang_mulai'          => substr($pengaturan->jam_pulang_mulai, 0, 5),
                'jam_pulang_selesai'        => substr($pengaturan->jam_pulang_selesai, 0, 5),
                'hari_aktif'                => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
                'toleransi_terlambat_menit' => 0,
                'require_camera'            => true,
                'allow_rfid'                => true,
                'allow_qr'                  => true,
                'kode_akses'                => 'KODELOKAL789',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        // Pastikan kode akses ter-update di database
        $updated = PresensiPengaturan::getPengaturan();
        $this->assertEquals('KODELOKAL789', $updated->kode_akses);

        // Uji coba unlock dengan kode baru (case-insensitive & whitespace tolerant)
        $unlockResp = $this->postJson(route('presensi.kiosk.unlock'), [
            'kode_akses' => '  kodelokal789  ',
        ]);
        $unlockResp->assertStatus(200);
        $unlockResp->assertJsonPath('status', 'success');

        // Kembalikan ke kode default agar tidak mengubah DB operasional
        $updated->update(['kode_akses' => 'SAE123']);
    }

    public function test_admin_can_update_access_code_directly_via_ajax(): void
    {
        $admin = User::where('peran_id_str', 'LIKE', '%admin%')->orWhere('username', 'admin')->first();
        if (!$admin) {
            $admin = User::first();
        }

        $sessionData = [
            'id'          => $admin->pengguna_id,
            'pengguna_id' => $admin->pengguna_id,
            'role'        => 'admin',
        ];

        $response = $this->withSession(['user' => $sessionData])
            ->postJson(route('dashboard.presensi.pengaturan.kode-akses'), [
                'kode_akses' => 'SMK2026',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('kode_akses', 'SMK2026');

        $this->assertEquals('SMK2026', PresensiPengaturan::getPengaturan()->kode_akses);

        // Reset kembali ke SAE123
        PresensiPengaturan::getPengaturan()->update(['kode_akses' => 'SAE123']);
    }

    public function test_kiosk_unlock_accepts_registered_guru_rfid_card(): void
    {
        // Cari user Guru / Tendik (memiliki ptk_id atau peran PTK)
        $guru = User::whereNotNull('ptk_id')->first()
            ?? User::where('peran_id_str', 'LIKE', '%ptk%')->first();

        $this->assertNotNull($guru, 'Harus ada setidaknya 1 akun GTK di database');
        $this->assertContains($guru->role, ['guru', 'tendik', 'admin']);

        $testRfid = 'GTK_RFID_TAP_999';
        $guru->update(['rfid_uid' => $testRfid]);

        $response = $this->postJson(route('presensi.kiosk.unlock'), [
            'kode_akses' => $testRfid,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $this->assertTrue(session('kiosk_access_granted') === true);
        $this->assertStringContainsString($guru->nama, session('kiosk_unlocked_by'));

        // Cleanup
        $guru->update(['rfid_uid' => null]);
    }

    public function test_kiosk_unlock_rejects_student_rfid_card(): void
    {
        // Cari siswa
        $siswa = User::whereNotNull('peserta_didik_id')->first();
        if ($siswa) {
            $siswaRfid = 'SISWA_RFID_001';
            $siswa->update(['rfid_uid' => $siswaRfid]);

            $response = $this->postJson(route('presensi.kiosk.unlock'), [
                'kode_akses' => $siswaRfid,
            ]);

            $response->assertStatus(422);
            $response->assertJsonPath('status', 'error');
            $this->assertFalse(session('kiosk_access_granted') === true);

            $siswa->update(['rfid_uid' => null]);
        }
    }

    public function test_assign_rfid_to_gtk_and_prevent_cross_duplicates(): void
    {
        $admin = User::where('peran_id_str', 'LIKE', '%admin%')->orWhere('username', 'admin')->first();
        if (!$admin) {
            $admin = User::first();
        }

        $gtk = User::whereNull('peserta_didik_id')->where('pengguna_id', '!=', $admin->pengguna_id)->first();
        if (!$gtk) {
            $gtk = $admin;
        }

        $sessionData = [
            'id'          => $admin->pengguna_id,
            'pengguna_id' => $admin->pengguna_id,
            'role'        => 'admin',
        ];

        $uid = 'RFID_CROSS_TEST_888';

        // 1. Assign RFID ke GTK
        $response = $this->withSession(['user' => $sessionData])
            ->postJson(route('dashboard.presensi.rfid.assign'), [
                'pengguna_id' => $gtk->pengguna_id,
                'rfid_uid'    => $uid,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $this->assertEquals($uid, User::find($gtk->pengguna_id)->rfid_uid);

        // 2. Coba daftarkan UID yang sama ke GTK lain -> harus ditolak (422)
        $gtkOther = User::whereNull('peserta_didik_id')
            ->whereNotIn('pengguna_id', [$gtk->pengguna_id])
            ->first();

        if ($gtkOther) {
            $dupResponse = $this->withSession(['user' => $sessionData])
                ->postJson(route('dashboard.presensi.rfid.assign'), [
                    'pengguna_id' => $gtkOther->pengguna_id,
                    'rfid_uid'    => $uid,
                ]);

            $dupResponse->assertStatus(422);
            $dupResponse->assertJsonPath('status', 'error');
        }

        // Cleanup
        $gtk->update(['rfid_uid' => null]);
    }

    protected function tearDown(): void
    {
        // Kembalikan ke kode akses operasional asli pengguna
        PresensiPengaturan::getPengaturan()->update(['kode_akses' => $this->originalKodeAkses]);
        parent::tearDown();
    }
}
