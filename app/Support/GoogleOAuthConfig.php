<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kredensial Google OAuth (Belajar.id).
 *
 * Sumber utama: tabel `settings` (diatur dari halaman Identitas Sekolah) —
 * tanpa menyentuh .env. Bila kosong, jatuh ke config/env lama agar instalasi
 * yang sudah terisi .env tetap berjalan.
 */
final class GoogleOAuthConfig
{
    /** @return array{client_id:string,client_secret:string,redirect_uri:string} */
    public static function all(): array
    {
        $row = self::settingsRow();

        $id = self::pick($row->google_client_id ?? null, config('services.google.client_id'));
        $secret = self::pick($row->google_client_secret ?? null, config('services.google.client_secret'));
        $redirect = self::pick($row->google_redirect_uri ?? null, config('services.google.redirect_uri'));

        return [
            'client_id' => self::clean($id),
            'client_secret' => self::clean($secret),
            'redirect_uri' => self::clean($redirect),
        ];
    }

    public static function clientId(): string
    {
        return self::all()['client_id'];
    }

    public static function clientSecret(): string
    {
        return self::all()['client_secret'];
    }

    public static function redirectUri(): string
    {
        $configured = self::all()['redirect_uri'];

        return $configured !== '' ? $configured : route('auth.google.callback');
    }

    public static function isConfigured(): bool
    {
        $c = self::all();

        return $c['client_id'] !== '' && $c['client_secret'] !== '';
    }

    private static function settingsRow(): ?object
    {
        try {
            if (Table::exists('settings') && Schema::hasColumn('settings', 'google_client_id')) {
                return DB::table('settings')->where('id', 1)->first();
            }
        } catch (\Throwable) {
        }

        return null;
    }

    private static function pick(?string $dbValue, $fallback): string
    {
        $dbValue = is_string($dbValue) ? trim($dbValue) : '';
        if ($dbValue !== '') {
            return $dbValue;
        }

        return is_string($fallback) ? trim($fallback) : '';
    }

    private static function clean(string $value): string
    {
        return trim($value, " \"'\t\n\r");
    }
}
