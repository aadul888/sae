<?php

namespace Tests\Feature;

use App\Services\CoreControlService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Kontrol pusat sae-core: peran yang ditutup admin pusat harus menutup akses
 * portal di sisi klien — termasuk saat permintaan lewat HTTP nyata (bukan
 * hanya pemanggilan service langsung), lewat middleware EnforceCoreAccess.
 */
class CoreAccessEnforcementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Bersihkan state kontrol antar-test agar tak bocor ke test lain.
        Cache::forget('sae_core_control_state');
        Cache::forget('sae_core_control_last_good');

        $ref = new \ReflectionClass(CoreControlService::class);
        $prop = $ref->getProperty('state');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    private function blockedState(array $roles): array
    {
        return [
            'reachable' => true,
            'mode' => 'limited',
            'features' => [],
            'roles' => $roles,
            'dapodik_enabled' => true,
            'show_ads' => false,
            'is_donor' => false,
            'update_enabled' => true,
            'latest_version' => '1.0.5',
            'update_available' => false,
        ];
    }

    private function primeState(array $state): void
    {
        Cache::put('sae_core_control_state', $state, 300);

        // Reset memori statik CoreControlService agar membaca cache baru.
        $ref = new \ReflectionClass(CoreControlService::class);
        $prop = $ref->getProperty('state');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    public function test_blocked_role_is_logged_out_on_any_page(): void
    {
        $this->primeState($this->blockedState(['guru']));

        $this->withSession(['user' => [
            'id' => 'guru-core-test',
            'pengguna_id' => 'guru-core-test',
            'username' => 'guru1',
            'nama' => 'Guru Ditutup',
            'role' => 'guru',
        ]]);

        $res = $this->get('/dashboard/guru');

        $res->assertRedirect(route('login'));
        $this->assertNull(session('user'), 'Sesi peran yang ditutup harus diputus.');
    }

    public function test_allowed_role_still_reaches_page(): void
    {
        $this->primeState($this->blockedState(['guru']));

        $this->withSession(['user' => [
            'id' => 'admin-core-test',
            'pengguna_id' => 'admin-core-test',
            'username' => 'admin',
            'nama' => 'Administrator',
            'role' => 'admin',
        ]]);

        $res = $this->get('/dashboard/admin');

        $res->assertStatus(200);
        $this->assertNotNull(session('user'));
    }

    public function test_fail_open_when_core_unreachable(): void
    {
        $this->primeState([
            'reachable' => false,
            'mode' => 'full',
            'features' => [],
            'roles' => [],
            'dapodik_enabled' => true,
            'show_ads' => false,
            'is_donor' => false,
            'update_enabled' => true,
            'latest_version' => null,
            'update_available' => false,
        ]);

        $this->withSession(['user' => [
            'id' => 'guru-open-test',
            'pengguna_id' => 'guru-open-test',
            'username' => 'guru1',
            'nama' => 'Guru Bebas',
            'role' => 'guru',
        ]]);

        $res = $this->get('/dashboard/guru');

        $res->assertStatus(200);
        $this->assertNotNull(session('user'));
    }

    public function test_last_good_rule_survives_core_outage(): void
    {
        // Tidak ada state segar; hanya hasil terakhir yang berhasil.
        Cache::forget('sae_core_control_state');
        Cache::put('sae_core_control_last_good', $this->blockedState(['guru']), 3600);

        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response('', 500),
        ]);

        $ref = new \ReflectionClass(CoreControlService::class);
        $prop = $ref->getProperty('state');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $this->withSession(['user' => [
            'id' => 'guru-sticky-test',
            'pengguna_id' => 'guru-sticky-test',
            'username' => 'guru1',
            'nama' => 'Guru Ditutup',
            'role' => 'guru',
        ]]);

        $res = $this->get('/dashboard/guru');

        // Pusat gagal, tapi aturan terakhir tetap berlaku — tidak fail-open.
        $res->assertRedirect(route('login'));
        $this->assertNull(session('user'));
    }

    public function test_core_url_can_be_set_per_install(): void
    {
        // Bawaan: tanpa pengaturan DB, memakai .env / bawaan.
        $this->assertNull(\App\Support\CoreApiKey::storedCoreUrl());

        \App\Support\CoreApiKey::saveCoreUrl('https://core.sekolah-ku.sch.id/');

        // Tersimpan tanpa garis miring di ujung & dipakai oleh coreUrl().
        $this->assertSame('https://core.sekolah-ku.sch.id', \App\Support\CoreApiKey::storedCoreUrl());
        $this->assertSame('https://core.sekolah-ku.sch.id', \App\Support\CoreApiKey::coreUrl());

        // Dikosongkan → kembali ke bawaan (.env/default), bukan hardcoded.
        \App\Support\CoreApiKey::saveCoreUrl(null);
        $this->assertNull(\App\Support\CoreApiKey::storedCoreUrl());
        $this->assertSame(
            rtrim((string) (config('services.sae_core.url') ?: 'https://appsae.com'), '/'),
            \App\Support\CoreApiKey::coreUrl()
        );
    }
}
