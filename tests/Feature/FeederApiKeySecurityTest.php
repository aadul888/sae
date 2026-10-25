<?php

namespace Tests\Feature;

use App\Support\FeederApiKey;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class FeederApiKeySecurityTest extends TestCase
{
    /** Kunci lama yang bocor di repositori tidak boleh lagi menjadi nilai bawaan. */
    private const LEAKED_KEYS = [
        'SAE_20260422090959_d6be672e5e92c802adeca5d8a4eccf0c',
        'sae_secret_live_key_2026',
    ];

    public function test_config_has_no_baked_in_api_key(): void
    {
        $configured = Config::get('services.sae_core.api_key');

        foreach (self::LEAKED_KEYS as $leaked) {
            $this->assertNotSame($leaked, $configured, 'Kunci bocor masih terpasang sebagai nilai bawaan config.');
        }
    }

    public function test_source_files_contain_no_leaked_key(): void
    {
        $paths = [
            'config/services.php',
            'app/Services/MonitoringReporterService.php',
            'app/Http/Controllers/DapodikController.php',
            'app/Http/Controllers/Api/FeederReceiverController.php',
            'database/seeders/DatabaseSeeder.php',
            'database/migrations/2024_01_01_000001_create_dapodik_tables.php',
        ];

        foreach ($paths as $relative) {
            $full = base_path($relative);
            $this->assertFileExists($full);

            $contents = file_get_contents($full);
            foreach (self::LEAKED_KEYS as $leaked) {
                $this->assertStringNotContainsString(
                    $leaked,
                    $contents,
                    "Kunci bocor masih ditemukan pada {$relative}."
                );
            }
        }
    }

    public function test_stored_key_comes_from_database(): void
    {
        $stored = FeederApiKey::stored();

        $this->assertNotNull($stored, 'Kunci feeder tidak tersedia di tabel settings.');
        $this->assertStringStartsWith('SAE_', $stored);
        $this->assertGreaterThanOrEqual(36, strlen($stored));
    }

    public function test_receive_endpoint_rejects_invalid_and_missing_key(): void
    {
        $invalid = ['type' => 'auth_check'];

        $this->postJson('/api/receive-data', $invalid, ['X-API-Key' => 'kunci-palsu'])
            ->assertStatus(401)
            ->assertJsonPath('success', false);

        $this->postJson('/api/receive-data', $invalid)
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_receive_endpoint_accepts_stored_key(): void
    {
        $stored = FeederApiKey::stored();
        $this->assertNotNull($stored);

        $this->postJson('/api/receive-data', ['type' => 'auth_check'], ['X-API-Key' => $stored])
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
