<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaComplianceTest extends TestCase
{
    /**
     * Test that manifest.webmanifest exists and is valid W3C JSON.
     */
    public function test_manifest_webmanifest_is_valid(): void
    {
        $manifestPath = public_path('manifest.webmanifest');
        $this->assertFileExists($manifestPath);

        $content = file_get_contents($manifestPath);
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertEquals('SAE - Sistem Aplikasi Edukasi', $data['name']);
        $this->assertEquals('SAE', $data['short_name']);
        $this->assertEquals('standalone', $data['display']);
        $this->assertEquals('/', $data['scope']);
        $this->assertNotEmpty($data['icons']);

        // Check required sizes for PWA compliance (192, 512, maskable)
        $sizes = array_column($data['icons'], 'sizes');
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);

        $purposes = array_column($data['icons'], 'purpose');
        $this->assertContains('maskable', $purposes);
    }

    /**
     * Test that core PWA files and icon assets exist.
     */
    public function test_pwa_assets_and_service_worker_exist(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('js/pwa.js'));
        $this->assertFileExists(public_path('img/icons/icon-192x192.png'));
        $this->assertFileExists(public_path('img/icons/icon-512x512.png'));
        $this->assertFileExists(public_path('img/icons/icon-maskable-512x512.png'));
        $this->assertFileExists(public_path('img/icons/apple-touch-icon.png'));

        $swContent = file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString('/offline', $swContent);
        $this->assertStringContainsString('sae-pwa-', $swContent);
        $this->assertStringContainsString('\/api\/', $swContent);
    }

    /**
     * Test that the offline route returns 200 and proper content.
     */
    public function test_offline_page_renders(): void
    {
        $response = $this->get('/offline');
        $response->assertStatus(200);
        $response->assertSee('Koneksi Terputus');
        $response->assertSee('Coba Muat Ulang');
    }

    /**
     * Test that layouts contain the PWA manifest link.
     */
    public function test_home_page_contains_manifest_link(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('manifest.webmanifest');
        $response->assertSee('apple-touch-icon');
        $response->assertSee('pwa.js');
    }
}
