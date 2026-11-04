<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StorageFallbackTest extends TestCase
{
    public function test_serves_existing_public_storage_file(): void
    {
        $testDir = storage_path('app/public/test_assets');
        if (!File::isDirectory($testDir)) {
            File::makeDirectory($testDir, 0755, true);
        }

        $filePath = $testDir . '/sample.png';
        // 1x1 transparent PNG binary
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        File::put($filePath, $png);

        $response = $this->get('/storage/test_assets/sample.png');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');

        // Cleanup
        File::delete($filePath);
        @rmdir($testDir);
    }

    public function test_returns_404_for_non_existent_file(): void
    {
        $response = $this->get('/storage/non_existent_folder/fake.png');
        $response->assertStatus(404);
    }

    public function test_blocks_directory_traversal(): void
    {
        $response = $this->get('/storage/../../.env');
        $response->assertStatus(404);
    }

    public function test_blocks_php_and_hidden_files(): void
    {
        $response = $this->get('/storage/.gitignore');
        $response->assertStatus(404);
    }
}
