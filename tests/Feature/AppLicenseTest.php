<?php

namespace Tests\Feature;

use App\Support\AppLicense;
use Tests\TestCase;

/**
 * Verifikasi lisensi bertanda-tangan di sisi klien (sae-smk): tanda-tangan sah
 * diterima, yang diubah ditolak, dan token malah bentuk ditolak.
 */
class AppLicenseTest extends TestCase
{
    private function sign(array $payload): array
    {
        $pair = sodium_crypto_sign_keypair();
        $secret = sodium_crypto_sign_secretkey($pair);
        $public = sodium_crypto_sign_publickey($pair);

        $json = json_encode($payload);
        $signature = sodium_crypto_sign_detached($json, $secret);
        $b64 = fn ($v) => rtrim(strtr(base64_encode($v), '+/', '-_'), '=');

        return [$b64($json) . '.' . $b64($signature), bin2hex($public)];
    }

    public function test_verifies_valid_token(): void
    {
        [$token, $publicKey] = $this->sign([
            'app_code' => 'SAE-SMK',
            'npsn' => '20252031',
            'entitlements' => ['core', 'absensi'],
            'status' => 'active',
            'exp' => now()->addYear()->toIso8601String(),
        ]);

        $this->assertTrue(AppLicense::verify($token, $publicKey));
    }

    public function test_rejects_tampered_token(): void
    {
        [$token, $publicKey] = $this->sign([
            'app_code' => 'SAE-SMK',
            'npsn' => '20252031',
            'entitlements' => ['core'],
            'status' => 'active',
        ]);

        [$payload, $sig] = explode('.', $token);
        $tampered = $payload . '.' . substr($sig, 0, -3) . 'XYZ';

        $this->assertFalse(AppLicense::verify($tampered, $publicKey));
    }

    public function test_rejects_wrong_public_key(): void
    {
        [$token] = $this->sign(['app_code' => 'SAE-SMK', 'status' => 'active']);
        [, $otherPublic] = $this->sign(['x' => 1]);

        $this->assertFalse(AppLicense::verify($token, $otherPublic));
    }

    public function test_rejects_malformed_token(): void
    {
        $this->assertFalse(AppLicense::verify('not-a-token', str_repeat('0', 64)));
    }

    public function test_env_pin_overrides_core_supplied_key(): void
    {
        [$token, $attackerKey] = $this->sign(['app_code' => 'SAE-SMK', 'status' => 'active']);

        // Kunci disematkan di config = kunci sah. Kunci dari "pusat" palsu diabaikan.
        [$legitToken, $legitKey] = $this->sign(['app_code' => 'SAE-SMK', 'status' => 'active']);
        config(['services.sae_core.license_public_key' => $legitKey]);

        $resolved = \App\Support\AppLicense::resolvePublicKey($attackerKey);
        $this->assertSame(strtolower($legitKey), $resolved, 'Kunci tertanam harus dipakai, bukan dari respons.');

        // Token penyerang tak lolos verifikasi dengan kunci tepercaya.
        $this->assertFalse(\App\Support\AppLicense::verify($token, $resolved));
        // Token sah lolos dengan kunci tepercaya.
        $this->assertTrue(\App\Support\AppLicense::verify($legitToken, $resolved));
    }

    public function test_tofu_pins_when_no_env_key(): void
    {
        config(['services.sae_core.license_public_key' => null]);

        // Belum ada pin → pakai kunci yang dikirim (first-run).
        [$token, $key] = $this->sign(['app_code' => 'SAE-SMK', 'status' => 'active']);
        $this->assertSame($key, \App\Support\AppLicense::resolvePublicKey($key));
    }
}
