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
}
