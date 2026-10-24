<?php

namespace Tests\Feature;

use App\Models\RolePermission;
use Tests\TestCase;

class RolePermissionDictionaryTest extends TestCase
{
    public function test_base_permission_dictionary_contract_is_stable(): void
    {
        $permissions = RolePermission::getBasePermissions();
        $keys = collect($permissions)->flatMap(fn (array $items) => array_keys($items));

        $this->assertNotEmpty($permissions);
        $this->assertGreaterThan(100, $keys->count());
        $this->assertSame($keys->count(), $keys->unique()->count());
        $this->assertContains('menu_dashboard', $keys->all());
        $this->assertContains('menu_persuratan', $keys->all());
        $this->assertContains('menu_jadwal_kbm', $keys->all());

        foreach ($permissions as $group => $items) {
            $this->assertNotSame('', trim($group));
            foreach ($items as $key => $config) {
                $this->assertArrayHasKey('label', $config, $key);
                $this->assertArrayHasKey('icon', $config, $key);
                $this->assertArrayHasKey('roles', $config, $key);
                $this->assertIsArray($config['roles'], $key);
            }
        }
    }
}
