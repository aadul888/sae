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
        $respGuru->assertStatus(200);

        // 3. Uji akses peran Peserta Didik
        $respPd = $this->withSession(['user' => [
            'id'          => 'test-pd',
            'pengguna_id' => 'test-pd',
            'role'        => 'peserta_didik',
        ]])->get(route('dashboard.jadwal-kbm.index'));
        $respPd->assertStatus(200);

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
}
