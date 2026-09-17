<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class RealtimeService
{
    private const CACHE_KEY = 'sae_realtime_events';
    private const MAX_EVENTS = 50;

    /**
     * Broadcast event data to realtime subscribers.
     *
     * ponytail: cache-based ring buffer capped at 50 events. Upgrade to Redis pub/sub or Laravel Reverb if concurrent connections exceed 500.
     */
    public static function trigger(string $eventName, array $data = [], ?string $channel = null): array
    {
        $event = [
            'id'        => (string) (int) (microtime(true) * 1000),
            'event'     => $eventName,
            'channel'   => $channel ?? 'global',
            'data'      => $data,
            'timestamp' => time(),
        ];

        $events = Cache::get(self::CACHE_KEY, []);
        if (!is_array($events)) {
            $events = [];
        }

        $events[] = $event;
        if (count($events) > self::MAX_EVENTS) {
            $events = array_slice($events, -self::MAX_EVENTS);
        }

        Cache::put(self::CACHE_KEY, $events, 180); // 3 menit TTL

        return $event;
    }

    /**
     * Retrieve events emitted after a specific event ID.
     */
    public static function getEventsSince(?string $lastId = null): array
    {
        $events = Cache::get(self::CACHE_KEY, []);
        if (!is_array($events) || empty($events)) {
            return [];
        }

        if (!$lastId) {
            return array_slice($events, -5);
        }

        $filtered = [];
        $found = false;

        foreach ($events as $ev) {
            if ($found) {
                $filtered[] = $ev;
            } elseif (($ev['id'] ?? '') === $lastId) {
                $found = true;
            }
        }

        if (!$found) {
            foreach ($events as $ev) {
                if (($ev['id'] ?? '') > $lastId) {
                    $filtered[] = $ev;
                }
            }
        }

        return $filtered;
    }
}
