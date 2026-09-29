<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAktivitas extends Model
{
    protected $table = 'admin_aktivitas';

    protected $fillable = [
        'admin_name',
        'admin_username',
        'modul',
        'aktivitas',
        'keterangan',
        'ip_address',
        'tipe',
    ];

    /**
     * Helper cepat untuk mencatat aktivitas administrator
     */
    public static function record(string $aktivitas, string $modul = 'Sistem', ?string $keterangan = null, string $tipe = 'success'): self
    {
        $sessionUser = session('user');
        $adminName = is_array($sessionUser) ? ($sessionUser['name'] ?? ($sessionUser['nama'] ?? 'Administrator')) : ($sessionUser->name ?? ($sessionUser->nama ?? 'Administrator'));
        $adminUsername = is_array($sessionUser) ? ($sessionUser['username'] ?? null) : ($sessionUser->username ?? null);

        return self::create([
            'admin_name' => $adminName,
            'admin_username' => $adminUsername,
            'modul' => $modul,
            'aktivitas' => $aktivitas,
            'keterangan' => $keterangan,
            'ip_address' => request()->ip(),
            'tipe' => $tipe,
        ]);
    }
}
