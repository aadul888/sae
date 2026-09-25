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

    /**
     * Kirim notifikasi transaksi presensi ke murid dan wali kelas secara terpadu,
     * serta trigger event realtime untuk Web Push PWA perangkat.
     */
    public static function kirimNotifikasiPresensi(
        string $pesertaDidikId,
        string $rombelId,
        string $judulMurid,
        string $pesanMurid,
        string $judulWali,
        string $pesanWali,
        string $tipe = 'info',
        string $icon = 'fa-solid fa-calendar-check',
        ?string $urlMurid = null,
        ?string $urlWali = null
    ): void {
        try {
            // 1. Notifikasi ke Murid
            $userMurid = User::where('peserta_didik_id', $pesertaDidikId)->first();
            self::create([
                'pengguna_id'      => $userMurid?->pengguna_id,
                'peserta_didik_id' => $pesertaDidikId,
                'kategori'         => 'presensi',
                'judul'            => $judulMurid,
                'pesan'            => $pesanMurid,
                'tipe'             => $tipe,
                'icon'             => $icon,
                'url'              => $urlMurid ?: route('dashboard.peserta-didik.presensi.index'),
                'is_read'          => false,
            ]);

            // 2. Cari Wali Kelas dari Rombel
            $waliPtkId = \Illuminate\Support\Facades\DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('rtt.kode', 'WALI_KELAS')
                ->where('ptt.is_active', true)
                ->where('ptt.rombel_id', $rombelId)
                ->value('ptt.ptk_id');

            if (!$waliPtkId) {
                $waliPtkId = \Illuminate\Support\Facades\DB::table('rombongan_belajar')
                    ->where('rombongan_belajar_id', $rombelId)
                    ->value('ptk_id');
            }

            $waliUser = $waliPtkId
                ? User::where('ptk_id', $waliPtkId)->first()
                : null;

            if ($waliUser) {
                self::create([
                    'pengguna_id'      => $waliUser->pengguna_id,
                    'peserta_didik_id' => null,
                    'kategori'         => 'presensi',
                    'judul'            => $judulWali,
                    'pesan'            => $pesanWali,
                    'tipe'             => $tipe,
                    'icon'             => $icon,
                    'url'              => $urlWali ?: route('dashboard.wali-kelas.presensi.index'),
                    'is_read'          => false,
                ]);
            }

            // 3. Broadcast Realtime Event agar PWA & Dashboard menerima ke perangkat
            \App\Services\RealtimeService::trigger('presensi.recorded', [
                'peserta_didik_id' => $pesertaDidikId,
                'murid_user_id'    => $userMurid?->pengguna_id,
                'wali_user_id'     => $waliUser?->pengguna_id,
                'judul_murid'      => $judulMurid,
                'pesan_murid'      => $pesanMurid,
                'judul_wali'       => $judulWali,
                'pesan_wali'       => $pesanWali,
                'url_murid'        => $urlMurid ?: route('dashboard.peserta-didik.presensi.index'),
                'url_wali'         => $urlWali ?: route('dashboard.wali-kelas.presensi.index'),
                'timestamp'        => time(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal kirim notifikasi presensi: ' . $e->getMessage());
        }
    }
}
