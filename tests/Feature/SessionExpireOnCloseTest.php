<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Sesi "mode browser": cookie sesi dibuat sebagai *session cookie* (tanpa masa
 * berlaku) sehingga menutup browser = keluar, sedangkan selama browser masih
 * terbuka sesi tidak kedaluwarsa (aktivitas memperbarui aktivitas sesi).
 *
 * PWA tidak terpengaruh karena memakai cookie remember 90 hari yang dipulihkan
 * middleware RestoreRememberedSession (lihat PwaRememberSessionTest).
 */
class SessionExpireOnCloseTest extends TestCase
{
    public function test_cookie_sesi_tanpa_masa_berlaku_saat_expire_on_close(): void
    {
        config(['session.expire_on_close' => true, 'session.lifetime' => 10080]);

        $res = $this->get('/');

        $name = config('session.cookie');
        $cookie = collect($res->headers->getCookies())
            ->first(fn ($c) => $c->getName() === $name);

        $this->assertNotNull($cookie, 'Cookie sesi harus dikirim.');
        // 0 = session cookie: hilang saat browser ditutup.
        $this->assertSame(0, $cookie->getExpiresTime());
    }

    public function test_cookie_sesi_punya_masa_berlaku_saat_tidak_expire_on_close(): void
    {
        config(['session.expire_on_close' => false, 'session.lifetime' => 120]);

        $res = $this->get('/');

        $name = config('session.cookie');
        $cookie = collect($res->headers->getCookies())
            ->first(fn ($c) => $c->getName() === $name);

        $this->assertNotNull($cookie);
        $this->assertSame(120 * 60, $cookie->getExpiresTime() - time());
    }
}
