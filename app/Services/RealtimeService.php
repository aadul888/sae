<?php

namespace App\Services;

use App\Models\RolePermission;
use Illuminate\Support\Facades\Cache;

class RealtimeService
{
    private const CACHE_KEY = 'sae_realtime_events';
    private const LOCK_KEY  = 'sae_realtime_lock';
    private const MAX_EVENTS = 50;

    /**
     * Broadcast event data to realtime subscribers.
     *
     * Penerima (audience) diambil dari `$data['_audience']` bila ada; jika tidak,
     * diturunkan otomatis dari payload notifikasi pribadi (`murid_user_id`,
     * `wali_user_id`, `ortu_user_id`, `peserta_didik_id`). Entri yang didukung:
     * `user:<id pengguna>`, `pd:<id peserta didik>` (menjangkau akun siswa maupun
     * orang tua dari anak yang sama), `role:<peran>`, `perm:<nama menu>`, dan
     * pengecualian `!role:<peran>`. `null` berarti semua pengguna.
     *
     * ponytail: cache-based ring buffer capped at 50 events. Upgrade to Redis pub/sub or Laravel Reverb if concurrent connections exceed 500.
     */
    public static function trigger(string $eventName, array $data = [], ?string $channel = null): array
    {
        $audience = self::pullAudience($data);

        $event = [
            'id'        => self::nextId(),
            'event'     => $eventName,
            'channel'   => $channel ?? 'global',
            'audience'  => $audience,
            'data'      => $data,
            'timestamp' => time(),
        ];

        // Kunci singkat: menulis ke satu key cache bersamaan (read-modify-write)
        // tanpa kunci membuat salah satu event hilang.
        $lock = null;
        try {
            $lock = Cache::lock(self::LOCK_KEY, 5);
            $lock->block(1);
        } catch (\Throwable) {
            $lock = null; // driver cache tanpa dukungan lock: lanjut tanpa penguncian
        }

        try {
            $events = Cache::get(self::CACHE_KEY, []);
            if (!is_array($events)) {
                $events = [];
            }

            $events[] = $event;
            if (count($events) > self::MAX_EVENTS) {
                $events = array_slice($events, -self::MAX_EVENTS);
            }

            Cache::put(self::CACHE_KEY, $events, 180); // 3 menit TTL
        } finally {
            if ($lock !== null) {
                $lock->release();
            }
        }

        return $event;
    }

    /**
     * Retrieve events emitted after a specific event ID, sudah disaring untuk penerima.
     *
     * @param  array{user_id?: mixed, role?: string|null}|null  $viewer  null = ambil dari sesi (bila ada).
     */
    public static function getEventsSince(?string $lastId = null, ?array $viewer = null): array
    {
        $events = Cache::get(self::CACHE_KEY, []);
        if (!is_array($events) || empty($events)) {
            return [];
        }

        if (!$lastId) {
            $slice = array_slice($events, -5);
        } else {
            $slice = [];
            $found = false;

            foreach ($events as $ev) {
                if ($found) {
                    $slice[] = $ev;
                } elseif (($ev['id'] ?? '') === $lastId) {
                    $found = true;
                }
            }

            if (!$found) {
                $slice = [];
                $lastMs = self::idMillis($lastId);
                foreach ($events as $ev) {
                    if (self::idMillis((string) ($ev['id'] ?? '')) > $lastMs) {
                        $slice[] = $ev;
                    }
                }
            }
        }

        $viewer ??= self::currentViewer();
        if ($viewer === null) {
            return $slice; // tanpa identitas (job internal, test, tamu): tidak disaring
        }

        return array_values(array_filter(
            $slice,
            static fn (array $ev): bool => self::isVisible($ev, $viewer)
        ));
    }

    /**
     * Penerima aktif dari sesi aplikasi.
     */
    private static function currentViewer(): ?array
    {
        $user = session('user');
        if (!$user) {
            return null;
        }

        $id = is_array($user)
            ? ($user['pengguna_id'] ?? ($user['id'] ?? null))
            : ($user->pengguna_id ?? ($user->id ?? null));
        $role = is_array($user) ? ($user['role'] ?? null) : ($user->role ?? null);

        if ($id === null && $role === null) {
            return null;
        }

        return [
            'user_id' => $id,
            'role'    => $role !== null ? strtolower((string) $role) : null,
            'pd_id'   => is_array($user)
                ? ($user['peserta_didik_id'] ?? null)
                : ($user->peserta_didik_id ?? null),
        ];
    }

    /**
     * Apakah event boleh dilihat oleh penerima ini.
     */
    private static function isVisible(array $event, array $viewer): bool
    {
        $audience = $event['audience'] ?? null;
        if (!is_array($audience) || $audience === []) {
            return true; // publik
        }

        $allowed = false;
        $adaAturanIzinkan = false;

        foreach ($audience as $rule) {
            $rule = (string) $rule;
            if ($rule === '') {
                continue;
            }

            $dilarang = $rule[0] === '!';
            $cocok = self::ruleMatches($dilarang ? substr($rule, 1) : $rule, $viewer);

            if ($dilarang) {
                if ($cocok) {
                    return false;
                }
            } else {
                $adaAturanIzinkan = true;
                if ($cocok) {
                    $allowed = true;
                }
            }
        }

        return !$adaAturanIzinkan || $allowed;
    }

    private static function ruleMatches(string $rule, array $viewer): bool
    {
        [$jenis, $nilai] = array_pad(explode(':', $rule, 2), 2, '');
        $nilai = strtolower(trim($nilai));

        return match ($jenis) {
            'user' => $viewer['user_id'] !== null && (string) $viewer['user_id'] === $nilai,
            'pd'   => !empty($viewer['pd_id']) && strtolower((string) $viewer['pd_id']) === $nilai,
            'role' => $viewer['role'] !== null && $viewer['role'] === $nilai,
            'perm' => $viewer['role'] !== null && RolePermission::canAccess($viewer['role'], $nilai, 'read'),
            default => false,
        };
    }

    /**
     * Ambil audience eksplisit, atau turunkan dari payload notifikasi pribadi.
     */
    private static function pullAudience(array &$data): ?array
    {
        $audience = $data['_audience'] ?? null;
        unset($data['_audience']);

        if (!is_array($audience)) {
            // Pemetaan payload notifikasi pribadi -> aturan penerima.
            $peta = [
                'murid_user_id'     => 'user:',
                'wali_user_id'      => 'user:',
                'ortu_user_id'      => 'user:',
                'peserta_didik_id'  => 'pd:',
            ];

            $audience = [];
            foreach ($peta as $key => $prefix) {
                if (!empty($data[$key])) {
                    $audience[] = $prefix . $data[$key];
                }
            }
        }

        $audience = array_values(array_unique(array_filter(
            $audience,
            static fn ($v) => $v !== null && $v !== ''
        )));

        return $audience ?: null;
    }

    /**
     * ID unik: milidetik + sufiks acak, supaya dua event pada milidetik yang sama tidak bentrok.
     */
    private static function nextId(): string
    {
        return (int) (microtime(true) * 1000) . '-' . bin2hex(random_bytes(3));
    }

    /**
     * Bagian waktu (milidetik) dari ID, dipakai untuk membandingkan urutan event.
     */
    private static function idMillis(string $id): int
    {
        return (int) explode('-', $id, 2)[0];
    }
}
