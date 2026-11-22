<?php

namespace Tests\Feature;

use App\Support\AppLicense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Gerbang lisensi (allows): pembekuan pusat harus LANGSUNG memblokir
 * (sebelumnya bug: suspended ikut masuk masa tenggang → tak berpengaruh),
 * kedaluwarsa hanya boleh selama masa tenggang, dan tanpa lisensi = fail-open.
 *
 * Memakai DB operasional + pemulihan nilai settings (tanpa RefreshDatabase).
 */
class AppLicenseGateTest extends TestCase
{
    private array $backup = [];

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasColumn('settings', 'license_token')) {
            $this->markTestSkipped('Kolom lisensi belum ada.');
        }
        $row = DB::table('settings')->where('id', 1)->first();
        $this->backup = $row ? (array) $row : [];
    }

    protected function tearDown(): void
    {
        if ($this->backup) {
            DB::table('settings')->where('id', 1)->update([
                'license_token' => $this->backup['license_token'] ?? null,
                'license_public_key' => $this->backup['license_public_key'] ?? null,
                'license_grace_days' => $this->backup['license_grace_days'] ?? 14,
            ]);
        }
        AppLicense::flush();
        parent::tearDown();
    }

    /** Rakit token bertanda-tangan + patok kunci publiknya. */
    private function store(array $payload): string
    {
        $pair = sodium_crypto_sign_keypair();
        $secret = sodium_crypto_sign_secretkey($pair);
        $public = sodium_crypto_sign_publickey($pair);

        $json = json_encode($payload);
        $sig = sodium_crypto_sign_detached($json, $secret);
        $b64 = fn ($v) => rtrim(strtr(base64_encode($v), '+/', '-_'), '=');
        $token = $b64($json) . '.' . $b64($sig);

        DB::table('settings')->where('id', 1)->update([
            'license_token' => $token,
            'license_public_key' => bin2hex($public),
            'license_grace_days' => 14,
        ]);
        AppLicense::flush();

        return bin2hex($public);
    }

    private function expires(string $when): array
    {
        return [
            'app_code' => 'SAE-SMK',
            'npsn' => '20252031',
            'entitlements' => ['core', 'update'],
            'status' => 'active',
            'exp' => $when,
        ];
    }

    public function test_no_license_is_fail_open(): void
    {
        DB::table('settings')->where('id', 1)->update(['license_token' => null, 'license_public_key' => null]);
        AppLicense::flush();

        $this->assertTrue(AppLicense::allows('update'), 'Tanpa lisensi harus fail-open.');
    }

    public function test_active_license_allows(): void
    {
        $this->store($this->expires(now()->addYear()->toIso8601String()));

        $this->assertTrue(AppLicense::allows('update'));
    }

    public function test_suspended_license_blocks_immediately(): void
    {
        $payload = $this->expires(now()->addYear()->toIso8601String());
        $payload['status'] = 'suspended';
        $this->store($payload);

        $this->assertFalse(AppLicense::allows('update'), 'Pembekuan pusat harus langsung memblokir.');
    }

    public function test_expired_within_grace_allows(): void
    {
        // Lewat 3 hari, tenggang 14 hari → masih boleh.
        $this->store($this->expires(now()->subDays(3)->toIso8601String()));

        $this->assertTrue(AppLicense::allows('update'));
    }

    public function test_expired_beyond_grace_blocks(): void
    {
        // Lewat 30 hari, tenggang 14 hari → ditolak.
        $this->store($this->expires(now()->subDays(30)->toIso8601String()));

        $this->assertFalse(AppLicense::allows('update'));
    }
}
