<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use App\Support\Table;
final class WaliKelasAccessHelper
{
    /**
     * Cek apakah pengguna saat ini adalah Peserta Didik yang ditunjuk sebagai Koordinator Kelas.
     */
    public static function isKoordinator(mixed $userOrRole = null): bool
    {
        $user = $userOrRole ?: session('user');
        if (!$user) return false;

        if (is_array($user) && isset($user['is_koordinator'])) {
            return (bool) $user['is_koordinator'];
        }

        $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
        if (!$pdId) return false;

        if (Table::exists('peserta_didik_meta')) {
            return (bool) DB::table('peserta_didik_meta')
                ->where('peserta_didik_id', $pdId)
                ->where('is_koordinator', true)
                ->exists();
        }

        return false;
    }

    /**
     * Cek apakah pengguna saat ini adalah Wali Kelas atau Administrator (atau Koordinator Kelas).
     */
    public static function isWaliKelasOrAdmin(mixed $userOrRole = null, ?string $rombelNameOrId = null): bool
    {
        $user = $userOrRole ?: session('user');
        if (!$user) return false;

        $role = is_string($user) ? $user : (is_array($user) ? ($user['role'] ?? '') : ($user->role ?? ''));
        if ($role === 'admin') {
            return true;
        }

        if ($role === 'peserta_didik') {
            if (self::isKoordinator($user)) {
                if (!$rombelNameOrId) return true;

                $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
                if ($pdId && Table::exists('peserta_didik')) {
                    $pd = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
                    if ($pd && ($pd->rombongan_belajar_id === $rombelNameOrId || strcasecmp(trim((string)$pd->nama_rombel), trim((string)$rombelNameOrId)) === 0)) {
                        return true;
                    }
                }
            }
            return false;
        }

        $userId = is_array($user) ? ($user['id'] ?? ($user['pengguna_id'] ?? null)) : ($user->id ?? ($user->pengguna_id ?? null));
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

        if (!$userId && !$ptkId) {
            return false;
        }

        if (Table::exists('ptk_tugas_tambahan') && Table::exists('ref_tugas_tambahan')) {
            $waliQuery = DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('rtt.kode', 'WALI_KELAS')
                ->where('ptt.is_active', true)
                ->where('rtt.is_active', true)
                ->where(function ($q) use ($userId, $ptkId) {
                    if ($userId) $q->where('ptt.user_id', (string) $userId);
                    if ($ptkId) $q->orWhere('ptt.ptk_id', $ptkId);
                });

            if ($rombelNameOrId) {
                $waliQuery->where(function ($q) use ($rombelNameOrId) {
                    $q->where('ptt.rombel_id', $rombelNameOrId);
                    $q->orWhereIn('ptt.rombel_id', function ($sub) use ($rombelNameOrId) {
                        $sub->select('rombongan_belajar_id')->from('rombongan_belajar')->where('nama', $rombelNameOrId);
                    });
                });
            }

            if ($waliQuery->exists()) {
                return true;
            }
        }

        if ($ptkId && Table::exists('rombongan_belajar')) {
            $rombelQuery = DB::table('rombongan_belajar')->where('ptk_id', $ptkId);
            if ($rombelNameOrId) {
                $rombelQuery->where(function ($q) use ($rombelNameOrId) {
                    $q->where('rombongan_belajar_id', $rombelNameOrId)->orWhere('nama', $rombelNameOrId);
                });
            }
            if ($rombelQuery->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dapatkan nama rombel yang diampu oleh Wali Kelas atau Koordinator Kelas saat ini.
     */
    public static function getWaliKelasRombel(mixed $userOrRole = null): ?string
    {
        $user = $userOrRole ?: session('user');
        if (!$user) return null;

        $role = is_string($user) ? $user : (is_array($user) ? ($user['role'] ?? '') : ($user->role ?? ''));

        if ($role === 'peserta_didik' && self::isKoordinator($user)) {
            $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
            if ($pdId && Table::exists('peserta_didik')) {
                return DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->value('nama_rombel');
            }
        }

        $userId = is_array($user) ? ($user['id'] ?? ($user['pengguna_id'] ?? null)) : ($user->id ?? ($user->pengguna_id ?? null));
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        if (!$userId && !$ptkId) return null;

        if (Table::exists('ptk_tugas_tambahan') && Table::exists('ref_tugas_tambahan')) {
            $rombelId = DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('rtt.kode', 'WALI_KELAS')
                ->where('ptt.is_active', true)
                ->where('rtt.is_active', true)
                ->where(function ($q) use ($userId, $ptkId) {
                    if ($userId) $q->where('ptt.user_id', (string) $userId);
                    if ($ptkId) $q->orWhere('ptt.ptk_id', $ptkId);
                })
                ->value('ptt.rombel_id');

            if ($rombelId) {
                $nama = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $rombelId)->value('nama');
                return $nama ?: $rombelId;
            }
        }

        if ($ptkId && Table::exists('rombongan_belajar')) {
            return DB::table('rombongan_belajar')->where('ptk_id', $ptkId)->value('nama');
        }

        return null;
    }

    /**
     * Dapatkan data rombel lengkap yang diampu oleh Wali Kelas atau Koordinator Kelas saat ini.
     */
    public static function getWaliKelasRombelInfo(mixed $userOrRole = null): ?object
    {
        $user = $userOrRole ?: session('user');
        if (!$user) return null;

        $role = is_string($user) ? $user : (is_array($user) ? ($user['role'] ?? '') : ($user->role ?? ''));

        if ($role === 'peserta_didik' && self::isKoordinator($user)) {
            $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
            if ($pdId && Table::exists('peserta_didik')) {
                $pd = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
                if ($pd && $pd->rombongan_belajar_id && Table::exists('rombongan_belajar')) {
                    $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $pd->rombongan_belajar_id)->first();
                    if ($rombel) return $rombel;
                }
            }
        }

        $userId = is_array($user) ? ($user['id'] ?? ($user['pengguna_id'] ?? null)) : ($user->id ?? ($user->pengguna_id ?? null));
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        if (!$userId && !$ptkId) return null;

        if (Table::exists('ptk_tugas_tambahan') && Table::exists('ref_tugas_tambahan')) {
            $rombelId = DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('rtt.kode', 'WALI_KELAS')
                ->where('ptt.is_active', true)
                ->where('rtt.is_active', true)
                ->where(function ($q) use ($userId, $ptkId) {
                    if ($userId) $q->where('ptt.user_id', (string) $userId);
                    if ($ptkId) $q->orWhere('ptt.ptk_id', $ptkId);
                })
                ->value('ptt.rombel_id');

            if ($rombelId && Table::exists('rombongan_belajar')) {
                $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $rombelId)->first();
                if ($rombel) return $rombel;
            }
        }

        if ($ptkId && Table::exists('rombongan_belajar')) {
            return DB::table('rombongan_belajar')->where('ptk_id', $ptkId)->first();
        }

        return null;
    }
}
