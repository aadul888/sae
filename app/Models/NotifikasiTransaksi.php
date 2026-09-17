<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotifikasiTransaksi extends Model
{
    use HasFactory;

    protected $table = 'notifikasi_transaksi';

    protected $fillable = [
        'pengguna_id',
        'peserta_didik_id',
        'kategori',
        'judul',
        'pesan',
        'tipe',
        'icon',
        'url',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Scope untuk notifikasi milik siswa tertentu atau user
     */
    public function scopeForUser($query, $userId = null, $pesertaDidikId = null)
    {
        return $query->where(function ($q) use ($userId, $pesertaDidikId) {
            if ($pesertaDidikId) {
                $q->where('peserta_didik_id', $pesertaDidikId);
            }
            if ($userId) {
                $q->orWhere('pengguna_id', $userId);
            }
        });
    }

    /**
     * Scope unread
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
