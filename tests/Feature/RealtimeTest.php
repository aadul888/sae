<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RealtimeService;
use Tests\TestCase;

class RealtimeTest extends TestCase
{
    public function test_realtime_service_triggers_and_retrieves_events(): void
    {
        $event = RealtimeService::trigger('test.event', ['sample' => 'data123']);

        $this->assertNotEmpty($event['id']);
        $this->assertEquals('test.event', $event['event']);

        $events = RealtimeService::getEventsSince();
        $this->assertNotEmpty($events);

        $lastItem = end($events);
        $this->assertEquals('test.event', $lastItem['event']);
        $this->assertEquals('data123', $lastItem['data']['sample']);
    }

    public function test_guest_cannot_access_realtime_poll(): void
    {
        $response = $this->getJson(route('dashboard.realtime.poll'));
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_poll_realtime_events(): void
    {
        $user = User::first();
        $this->assertNotNull($user);

        $sessionData = [
            'id'          => $user->pengguna_id,
            'pengguna_id' => $user->pengguna_id,
            'role'        => $user->role ?? 'admin',
        ];

        RealtimeService::trigger('pengumuman.created', ['judul' => 'Uji Coba Realtime']);

        $response = $this->withSession(['user' => $sessionData])
            ->getJson(route('dashboard.realtime.poll'));

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $this->assertNotEmpty($response->json('events'));
    }

    public function test_dashboard_layout_includes_realtime_script(): void
    {
        $user = User::first();
        $this->assertNotNull($user);

        $sessionData = [
            'id'          => $user->pengguna_id,
            'pengguna_id' => $user->pengguna_id,
            'role'        => 'admin',
        ];

        $response = $this->withSession(['user' => $sessionData])
            ->get(route('dashboard.admin'));

        $response->assertStatus(200);
        $response->assertSee('sae-realtime.js');
    }

    public function test_mutation_broadcasts_data_changed_event(): void
    {
        $user = User::first();
        $this->assertNotNull($user);

        $sessionData = [
            'id'          => $user->pengguna_id,
            'pengguna_id' => $user->pengguna_id,
            'role'        => 'admin',
        ];

        // Trigger mutasi data via POST /dashboard/pengumuman
        $response = $this->withSession(['user' => $sessionData])
            ->post(route('dashboard.pengumuman.store'), [
                'judul'        => 'Pengumuman Uji Broadcast',
                'isi'          => 'Isi pengumuman uji broadcast realtime',
                'target'       => 'semua',
                'target_peran' => 'semua',
                'is_active'    => '1',
            ]);

        $response->assertSessionHas('success');

        $events = RealtimeService::getEventsSince();
        $hasDataChangedOrPengumuman = collect($events)->contains(function ($ev) {
            return in_array($ev['event'], ['data.changed', 'pengumuman.created'], true);
        });

        $this->assertTrue($hasDataChangedOrPengumuman, 'Event mutasi data harus di-broadcast');

        // Bersihkan data uji agar tidak mencemari database operasional
        \App\Models\Pengumuman::where('judul', 'Pengumuman Uji Broadcast')->delete();
    }

    public function test_jadwal_kbm_accessible_by_roles_and_broadcasts(): void
    {
        $admin = User::first();
        $this->assertNotNull($admin);

        // 1. Uji akses peran Admin
        $respAdmin = $this->withSession(['user' => [
            'id'          => $admin->pengguna_id,
            'pengguna_id' => $admin->pengguna_id,
            'role'        => 'admin',
        ]])->get(route('dashboard.jadwal-kbm.index'));
        $respAdmin->assertStatus(200);
        $respAdmin->assertSee('gridMatrixContainer');

        // 2. Uji akses peran Guru
        $respGuru = $this->withSession(['user' => [
            'id'          => 'test-guru',
            'pengguna_id' => 'test-guru',
            'role'        => 'guru',
        ]])->get(route('dashboard.jadwal-kbm.index'));
        $respGuru->assertStatus(302);

        // 3. Uji akses peran Peserta Didik
        $respPd = $this->withSession(['user' => [
            'id'          => 'test-pd',
            'pengguna_id' => 'test-pd',
            'role'        => 'peserta_didik',
        ]])->get(route('dashboard.jadwal-kbm.index'));
        $respPd->assertStatus(302);

        // 4. Uji mutasi jadwal men-trigger event jadwal.changed
        $ptkId = \Illuminate\Support\Facades\DB::table('gtk')->value('ptk_id');
        $this->assertNotNull($ptkId, 'Harus ada data GTK');

        $respPref = $this->withSession(['user' => [
            'id'          => $admin->pengguna_id,
            'pengguna_id' => $admin->pengguna_id,
            'role'        => 'admin',
        ]])->postJson(route('dashboard.jadwal-kbm.simpan-guru-preferensi'), [
            'ptk_id'   => $ptkId,
            'hari_off' => ['Sabtu'],
        ]);

        $respPref->assertJsonPath('success', true);

        $events = RealtimeService::getEventsSince();
        $hasJadwalEvent = collect($events)->contains(function ($ev) {
            return $ev['event'] === 'jadwal.changed';
        });

        $this->assertTrue($hasJadwalEvent, 'Event jadwal.changed harus ter-trigger di RealtimeService');
    }

    public function test_audience_membatasi_event_hanya_untuk_pengguna_tertentu(): void
    {
        $nama = 'test.audience.' . uniqid();
        RealtimeService::trigger($nama, ['pesan' => 'rahasia'], null);

        // Audience implisit dari payload notifikasi pribadi.
        RealtimeService::trigger($nama . '.pribadi', ['murid_user_id' => 4242, 'pesan' => 'untuk murid']);

        $pemilik = collect(RealtimeService::getEventsSince(null, ['user_id' => 4242, 'role' => 'peserta_didik']))
            ->firstWhere('event', $nama . '.pribadi');
        $this->assertNotNull($pemilik, 'Pemilik event harus menerima notifikasi pribadinya.');

        $orangLain = collect(RealtimeService::getEventsSince(null, ['user_id' => 777, 'role' => 'guru']))
            ->firstWhere('event', $nama . '.pribadi');
        $this->assertNull($orangLain, 'Pengguna lain tidak boleh menerima notifikasi pribadi.');

        // Event tanpa audience tetap publik.
        $publik = collect(RealtimeService::getEventsSince(null, ['user_id' => 777, 'role' => 'guru']))
            ->firstWhere('event', $nama);
        $this->assertNotNull($publik, 'Event tanpa audience harus tetap terkirim ke semua pengguna.');
    }

    public function test_audience_pengecualian_peran_menyembunyikan_event_dari_siswa(): void
    {
        $nama = 'test.audience.deny.' . uniqid();
        RealtimeService::trigger($nama, [
            '_audience' => ['!role:peserta_didik'],
            'nama'      => 'Siswa Uji',
            'nisn'      => '1234567',
        ]);

        $siswa = collect(RealtimeService::getEventsSince(null, ['user_id' => 1, 'role' => 'peserta_didik']))
            ->firstWhere('event', $nama);
        $this->assertNull($siswa, 'Peran yang dikecualikan tidak boleh menerima event berisi data pribadi.');

        $operator = collect(RealtimeService::getEventsSince(null, ['user_id' => 2, 'role' => 'tendik']))
            ->firstWhere('event', $nama);
        $this->assertNotNull($operator, 'Peran lain tetap menerima event kiosk.');
    }

    public function test_audience_peserta_didik_menjangkau_akun_siswa_dan_orang_tua(): void
    {
        $nama = 'test.audience.pd.' . uniqid();
        RealtimeService::trigger($nama, ['peserta_didik_id' => 5150, 'pesan' => 'presensi tercatat']);

        foreach (['peserta_didik' => 11, 'orang_tua' => 22] as $role => $userId) {
            $event = collect(RealtimeService::getEventsSince(null, [
                'user_id' => $userId,
                'role'    => $role,
                'pd_id'   => 5150,
            ]))->firstWhere('event', $nama);

            $this->assertNotNull($event, "Akun {$role} dari siswa terkait harus menerima event.");
        }

        $bukanTerkait = collect(RealtimeService::getEventsSince(null, [
            'user_id' => 33,
            'role'    => 'peserta_didik',
            'pd_id'   => 9999,
        ]))->firstWhere('event', $nama);
        $this->assertNull($bukanTerkait, 'Siswa lain tidak boleh menerima event.');
    }

    public function test_audience_tidak_ikut_terkirim_ke_klien(): void
    {
        $nama = 'test.audience.payload.' . uniqid();
        $event = RealtimeService::trigger($nama, [
            '_audience' => ['user:99'],
            'pesan'     => 'halo',
        ]);

        $this->assertArrayNotHasKey('_audience', $event['data']);
        $this->assertEquals('halo', $event['data']['pesan']);
        $this->assertEquals(['user:99'], $event['audience']);
    }

    public function test_id_event_unik_walau_dipicu_pada_milidetik_sama(): void
    {
        $ids = [];
        for ($i = 0; $i < 25; $i++) {
            $ids[] = RealtimeService::trigger('test.id.unik')['id'];
        }

        $this->assertCount(25, array_unique($ids), 'Setiap event harus punya ID unik.');
    }

    protected function tearDown(): void
    {
        \App\Models\Pengumuman::where('judul', 'LIKE', '%Uji Broadcast%')->delete();
        parent::tearDown();
    }
}
