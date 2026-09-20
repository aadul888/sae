<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Persuratan;
use App\Models\User;
use App\Models\RolePermission;
use App\Services\SystemNotificationService;

class PersuratanDutyModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RolePermission::syncAvailablePermissions();
    }

    public function test_guest_cannot_access_persuratan(): void
    {
        $response = $this->get(route('dashboard.persuratan.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_access_persuratan_dashboard(): void
    {
        $admin = [
            'id' => 'admin-test-01',
            'pengguna_id' => 'admin-test-01',
            'username' => 'admin',
            'nama' => 'Administrator Sistem',
            'role' => 'admin',
        ];

        $response = $this->withSession(['user' => $admin])
            ->get(route('dashboard.persuratan.index'));

        $response->assertStatus(200);
        $response->assertSee('Persuratan &amp; Arsip Digital', false);
    }

    public function test_tendik_can_access_persuratan(): void
    {
        $tendik = [
            'id' => 'tendik-test-01',
            'pengguna_id' => 'tendik-test-01',
            'username' => 'staf.arsip',
            'nama' => 'Staf Tata Usaha & Arsip',
            'role' => 'tendik',
            'ptk_id' => 'ptk-tendik-01',
        ];

        $response = $this->withSession(['user' => $tendik])
            ->get(route('dashboard.persuratan.index'));

        $response->assertStatus(200);
        $response->assertSee('Persuratan &amp; Arsip Digital', false);
    }

    public function test_crud_lifecycle_persuratan(): void
    {
        $admin = [
            'id' => 'admin-crud-01',
            'pengguna_id' => 'admin-crud-01',
            'username' => 'admin',
            'nama' => 'Super Admin',
            'role' => 'admin',
        ];

        // 1. Store (Create)
        $payload = [
            'nomor_surat'     => 'TEST/001/SMK/IX/2026',
            'jenis_surat'     => 'masuk',
            'perihal'         => 'Surat Uji Coba Integrasi Modul Tendik',
            'pengirim_asal'   => 'Dinas Pendidikan Jawa Barat',
            'tujuan_penerima' => 'Kepala Sekolah',
            'tanggal_surat'   => '2026-09-19',
            'status'          => 'menunggu_disposisi',
            'keterangan'      => 'Disposisikan segera untuk pengujian.',
        ];

        $storeRes = $this->withSession(['user' => $admin])
            ->postJson(route('dashboard.persuratan.store'), $payload);

        $storeRes->assertStatus(200);
        $storeRes->assertJson(['status' => 'success']);

        $item = Persuratan::where('nomor_surat', 'TEST/001/SMK/IX/2026')->first();
        $this->assertNotNull($item);
        $this->assertEquals('Surat Uji Coba Integrasi Modul Tendik', $item->perihal);

        // 2. Show (Read)
        $showRes = $this->withSession(['user' => $admin])
            ->getJson(route('dashboard.persuratan.show', $item->id));

        $showRes->assertStatus(200);
        $showRes->assertJsonPath('data.nomor_surat', 'TEST/001/SMK/IX/2026');

        // 3. Update
        $updateRes = $this->withSession(['user' => $admin])
            ->putJson(route('dashboard.persuratan.update', $item->id), array_merge($payload, [
                'status' => 'diproses',
                'perihal' => 'Surat Uji Coba (Telah Diproses)',
            ]));

        $updateRes->assertStatus(200);
        $item->refresh();
        $this->assertEquals('diproses', $item->status);
        $this->assertEquals('Surat Uji Coba (Telah Diproses)', $item->perihal);

        // 4. Destroy (Delete)
        $deleteRes = $this->withSession(['user' => $admin])
            ->deleteJson(route('dashboard.persuratan.destroy', $item->id));

        $deleteRes->assertStatus(200);
        $this->assertNull(Persuratan::find($item->id));
    }

    public function test_system_notification_service_detects_pending_surat(): void
    {
        // Pastikan ada setidaknya 1 surat menunggu_disposisi
        Persuratan::create([
            'nomor_surat'     => 'NOTIF/TEST/001',
            'jenis_surat'     => 'masuk',
            'perihal'         => 'Surat Notifikasi Pengujian',
            'pengirim_asal'   => 'Kemdikbud',
            'tujuan_penerima' => 'Kepala Sekolah',
            'tanggal_surat'   => '2026-09-19',
            'status'          => 'menunggu_disposisi',
        ]);

        $tendik = [
            'id' => 'tendik-notif-01',
            'pengguna_id' => 'tendik-notif-01',
            'username' => 'staf.arsiparis',
            'nama' => 'Staf Arsiparis',
            'role' => 'tendik',
        ];

        $notifData = SystemNotificationService::getSystemNotifications($tendik);
        $hasPendingSurat = collect($notifData['items'])->contains(function ($item) {
            return ($item->kategori ?? '') === 'persuratan';
        });

        $this->assertTrue($hasPendingSurat, 'SystemNotificationService harus mendeteksi pending surat untuk tendik.');

        // Clean up
        Persuratan::where('nomor_surat', 'NOTIF/TEST/001')->delete();
    }
}
