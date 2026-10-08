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
     * Kirim notifikasi transaksi presensi ke murid, orang tua, dan wali kelas secara terpadu,
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
        ?string $urlWali = null,
        ?string $judulOrtu = null,
        ?string $pesanOrtu = null,
        ?string $actorUserId = null
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

            if ($waliUser && (!$actorUserId || $waliUser->pengguna_id !== $actorUserId)) {
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

            // 3. Broadcast Realtime Event agar PWA, Mobile, & Dashboard Orang Tua menerima push
            \App\Services\RealtimeService::trigger('presensi.recorded', [
                'peserta_didik_id' => $pesertaDidikId,
                'murid_user_id'    => $userMurid?->pengguna_id,
                'wali_user_id'     => $waliUser?->pengguna_id,
                'actor_user_id'    => $actorUserId,
                'judul_murid'      => $judulMurid,
                'pesan_murid'      => $pesanMurid,
                'judul_wali'       => $judulWali,
                'pesan_wali'       => $pesanWali,
                'judul_ortu'       => $judulOrtu ?: $judulMurid,
                'pesan_ortu'       => $pesanOrtu ?: str_replace(['Anda', 'anda'], ['Putra/putri Anda', 'putra/putri Anda'], $pesanMurid),
                'url_murid'        => $urlMurid ?: route('dashboard.peserta-didik.presensi.index'),
                'url_wali'         => $urlWali ?: route('dashboard.wali-kelas.presensi.index'),
                'url_ortu'         => route('dashboard.orang-tua'),
                'timestamp'        => time(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal kirim notifikasi presensi: ' . $e->getMessage());
        }
    }

    /**
     * Kirim notifikasi realtime perizinan siswa (e-Izin keluar, checkout gerbang, checkin kembali, surat izin)
     */
    public static function kirimNotifikasiIzin(
        string $pesertaDidikId,
        ?string $rombelId,
        string $judul,
        string $pesan,
        string $tipe = 'warning',
        string $icon = 'fa-solid fa-ticket',
        ?string $url = null,
        ?string $actorUserId = null
    ): void {
        try {
            $userMurid = User::where('peserta_didik_id', $pesertaDidikId)->first();
            self::create([
                'pengguna_id'      => $userMurid?->pengguna_id,
                'peserta_didik_id' => $pesertaDidikId,
                'kategori'         => 'izin',
                'judul'            => $judul,
                'pesan'            => $pesan,
                'tipe'             => $tipe,
                'icon'             => $icon,
                'url'              => $url ?: route('dashboard.peserta-didik.izin.index'),
                'is_read'          => false,
            ]);

            \App\Services\RealtimeService::trigger('izin.recorded', [
                'peserta_didik_id' => $pesertaDidikId,
                'murid_user_id'    => $userMurid?->pengguna_id,
                'actor_user_id'    => $actorUserId,
                'judul_murid'      => $judul,
                'pesan_murid'      => $pesan,
                'judul_ortu'       => $judul,
                'pesan_ortu'       => $pesan,
                'url_murid'        => route('dashboard.peserta-didik.izin.index'),
                'url_ortu'         => route('dashboard.orang-tua'),
                'timestamp'        => time(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal kirim notifikasi izin: ' . $e->getMessage());
        }
    }

    /**
     * Kirim notifikasi saat siswa mengajukan surat izin/sakit baru ke Wali Kelas
     */
    public static function kirimPengajuanIzinSiswa(
        string $pesertaDidikId,
        string $rombelId,
        string $siswaNama,
        string $jenisLabel,
        string $rentangTgl,
        ?string $actorUserId = null
    ): void {
        try {
            $userMurid = User::where('peserta_didik_id', $pesertaDidikId)->first();

            // 1. Notifikasi ke Murid
            self::create([
                'pengguna_id'      => $userMurid?->pengguna_id,
                'peserta_didik_id' => $pesertaDidikId,
                'kategori'         => 'izin',
                'judul'            => "Surat {$jenisLabel} Terkirim",
                'pesan'            => "Permohonan surat {$jenisLabel} ({$rentangTgl}) berhasil dikirim ke Wali Kelas.",
                'tipe'             => 'info',
                'icon'             => 'fa-solid fa-paper-plane',
                'url'              => route('dashboard.peserta-didik.izin.index'),
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
                    'kategori'         => 'izin',
                    'judul'            => "Pengajuan Izin: {$siswaNama}",
                    'pesan'            => "{$siswaNama} mengajukan surat {$jenisLabel} ({$rentangTgl}).",
                    'tipe'             => 'warning',
                    'icon'             => 'fa-solid fa-envelope-open-text',
                    'url'              => route('dashboard.wali-kelas.presensi.index'),
                    'is_read'          => false,
                ]);
            }

            // 3. Trigger Realtime Event
            \App\Services\RealtimeService::trigger('izin.recorded', [
                'peserta_didik_id' => $pesertaDidikId,
                'murid_user_id'    => $userMurid?->pengguna_id,
                'wali_user_id'     => $waliUser?->pengguna_id,
                'actor_user_id'    => $actorUserId,
                'judul_murid'      => "Surat {$jenisLabel} Terkirim",
                'pesan_murid'      => "Permohonan surat {$jenisLabel} ({$rentangTgl}) terkirim ke Wali Kelas.",
                'judul_wali'       => "Pengajuan Izin: {$siswaNama}",
                'pesan_wali'       => "{$siswaNama} mengajukan surat {$jenisLabel} ({$rentangTgl}).",
                'judul_ortu'       => "Pengajuan Izin: {$siswaNama}",
                'pesan_ortu'       => "Surat {$jenisLabel} {$siswaNama} ({$rentangTgl}) telah diajukan ke sekolah.",
                'url_murid'        => route('dashboard.peserta-didik.izin.index'),
                'url_wali'         => route('dashboard.wali-kelas.presensi.index'),
                'url_ortu'         => route('dashboard.orang-tua'),
                'timestamp'        => time(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal kirim notifikasi pengajuan izin: ' . $e->getMessage());
        }
    }

    /**
     * Kirim notifikasi realtime saat Wali Kelas memverifikasi (setujui/tolak) izin siswa
     */
    public static function kirimNotifikasiIzinVerifikasi(
        string $pesertaDidikId,
        string $rombelId,
        string $judulMurid,
        string $pesanMurid,
        string $judulOrtu,
        string $pesanOrtu,
        string $tipe = 'info',
        string $icon = 'fa-solid fa-circle-check',
        ?string $actorUserId = null
    ): void {
        try {
            $userMurid = User::where('peserta_didik_id', $pesertaDidikId)->first();

            self::create([
                'pengguna_id'      => $userMurid?->pengguna_id,
                'peserta_didik_id' => $pesertaDidikId,
                'kategori'         => 'izin',
                'judul'            => $judulMurid,
                'pesan'            => $pesanMurid,
                'tipe'             => $tipe,
                'icon'             => $icon,
                'url'              => route('dashboard.peserta-didik.izin.index'),
                'is_read'          => false,
            ]);

            \App\Services\RealtimeService::trigger('izin.recorded', [
                'peserta_didik_id' => $pesertaDidikId,
                'murid_user_id'    => $userMurid?->pengguna_id,
                'actor_user_id'    => $actorUserId,
                'judul_murid'      => $judulMurid,
                'pesan_murid'      => $pesanMurid,
                'judul_ortu'       => $judulOrtu,
                'pesan_ortu'       => $pesanOrtu,
                'url_murid'        => route('dashboard.peserta-didik.izin.index'),
                'url_ortu'         => route('dashboard.orang-tua'),
                'timestamp'        => time(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal kirim notifikasi verifikasi izin: ' . $e->getMessage());
        }
    }
}
