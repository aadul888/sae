<?php

namespace App\Services;

use App\Models\AgendaKbm;
use App\Models\JadwalKbm;
use App\Models\KalenderPendidikan;
use App\Models\NotifikasiTransaksi;
use App\Models\PresensiHarian;
use App\Models\PresensiIzin;
use App\Models\PresensiMengajar;
use App\Models\PresensiPengaturan;
use App\Models\RolePermission;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class SystemNotificationService
{
    /**
     * Cache hasil per request agar tidak query berulang dalam 1 lifecycle
     */
    protected static ?array $requestCache = null;

    /**
     * Ambil seluruh notifikasi sistem & aktivitas personal untuk pengguna aktif
     *
     * @param mixed $currentUser Objek user atau array session
     * @return array [ 'items' => Collection, 'unread_count' => int, 'footer_url' => string, 'footer_text' => string ]
     */
    public static function getSystemNotifications($currentUser): array
    {
        if (!$currentUser) {
            return [
                'items' => collect(),
                'unread_count' => 0,
                'footer_url' => route('dashboard.admin'),
                'footer_text' => 'Buka Dashboard',
            ];
        }

        $userId = (string) (is_array($currentUser)
            ? ($currentUser['pengguna_id'] ?? ($currentUser['id'] ?? ''))
            : ($currentUser->pengguna_id ?? ($currentUser->id ?? '')));

        $cacheKey = $userId ?: 'guest';
        if (isset(static::$requestCache[$cacheKey])) {
            return static::$requestCache[$cacheKey];
        }

        $role = (string) (is_array($currentUser)
            ? ($currentUser['role'] ?? 'peserta_didik')
            : ($currentUser->role ?? 'peserta_didik'));

        $ptkId = is_array($currentUser) ? ($currentUser['ptk_id'] ?? null) : ($currentUser->ptk_id ?? null);
        $pdId = is_array($currentUser) ? ($currentUser['peserta_didik_id'] ?? null) : ($currentUser->peserta_didik_id ?? null);

        $items = collect();

        // 1. Ambil Notifikasi Tersimpan dari Database (Tabel notifikasi_transaksi)
        if (Schema::hasTable('notifikasi_transaksi')) {
            try {
                $dbItems = NotifikasiTransaksi::forUser($userId, $pdId)
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get()
                    ->map(function ($notif) {
                        return (object) [
                            'id'         => (string) $notif->id,
                            'judul'      => $notif->judul,
                            'pesan'      => $notif->pesan,
                            'kategori'   => $notif->kategori ?: 'sistem',
                            'tipe'       => $notif->tipe ?: 'info',
                            'icon'       => $notif->icon ?: 'fas fa-bell',
                            'url'        => $notif->url ?: '#',
                            'created_at' => $notif->created_at ?: now(),
                            'time_diff'  => $notif->created_at ? $notif->created_at->diffForHumans(null, true) : 'Baru saja',
                            'is_read'    => (bool) $notif->is_read,
                            'is_dynamic' => false,
                        ];
                    });

                $items = $items->merge($dbItems);
            } catch (\Throwable $e) {
                // Ignore query error
            }
        }

        // 2. Evaluasi Notifikasi & Peringatan Realtime Sesuai Role
        switch ($role) {
            case 'guru':
                $dynamicItems = static::getGuruDynamicReminders($currentUser, $ptkId);
                $items = $dynamicItems->merge($items);
                $footerUrl = route('dashboard.presensi-mengajar.index');
                $footerText = 'Presensi Mengajar & Agenda KBM';
                break;

            case 'tendik':
                $dynamicItems = static::getTendikDynamicReminders($currentUser, $ptkId, $items->count());
                $items = $dynamicItems->merge($items);
                $footerUrl = route('dashboard.tendik');
                $footerText = 'Buka Dashboard Tendik';
                break;

            case 'peserta_didik':
                $dynamicItems = static::getPesertaDidikDynamicReminders($currentUser, $pdId);
                $items = $dynamicItems->merge($items);
                $footerUrl = route('dashboard.peserta-didik.presensi.index');
                $footerText = 'Buka Riwayat Presensi & Izin';
                break;

            case 'admin':
            default:
                $dynamicItems = static::getAdminDynamicReminders($currentUser);
                $items = $dynamicItems->merge($items);
                $footerUrl = route('dashboard.admin');
                $footerText = 'Buka Dashboard Admin';
                break;
        }

        // Deduplikasi dan limit 10 item terbaru
        $uniqueList = $items->unique(function ($item) {
            return $item->judul . '|' . substr($item->pesan, 0, 30);
        })->take(10)->values();

        // Hitung unread count
        $unreadCount = $uniqueList->filter(fn($n) => !$n->is_read)->count();

        $result = [
            'items'        => $uniqueList,
            'unread_count' => $unreadCount,
            'footer_url'   => $footerUrl,
            'footer_text'  => $footerText,
        ];

        static::$requestCache[$cacheKey] = $result;
        return $result;
    }

    /**
     * Peringatan & Pengingat Dinamis untuk Guru
     */
    protected static function getGuruDynamicReminders($currentUser, ?string $ptkId): Collection
    {
        $list = collect();
        if (!$ptkId) return $list;

        $today = now()->toDateString();
        $hariIni = match (now()->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        };

        // Cek apakah hari ini adalah hari libur sekolah
        $isLibur = false;
        if (class_exists(KalenderPendidikan::class)) {
            try {
                $statusHari = KalenderPendidikan::getStatusHariIni();
                $isLibur = ($statusHari['mode'] ?? '') === 'libur' || ($statusHari['is_libur'] ?? false);
            } catch (\Throwable $e) {}
        }

        if ($isLibur) {
            $list->push((object) [
                'id'         => 'dyn_guru_libur',
                'judul'      => 'Hari Libur Akademik',
                'pesan'      => 'Hari ini terdaftar sebagai hari libur sekolah. KBM reguler tidak aktif.',
                'kategori'   => 'jadwal',
                'tipe'       => 'info',
                'icon'       => 'fas fa-umbrella-beach',
                'url'        => route('dashboard.kalender-pendidikan.index'),
                'created_at' => now(),
                'time_diff'  => 'Hari Ini',
                'is_read'    => true,
                'is_dynamic' => true,
            ]);
            return $list;
        }

        // Cek Jadwal Mengajar Guru Hari Ini
        if (Schema::hasTable('jadwal_kbm')) {
            try {
                $jadwalHariIni = JadwalKbm::where('is_active', true)
                    ->where('ptk_id', $ptkId)
                    ->where('hari', $hariIni)
                    ->excludePkl()
                    ->get();

                $totalKelas = $jadwalHariIni->count();

                if ($totalKelas === 0) {
                    $list->push((object) [
                        'id'         => 'dyn_guru_no_schedule',
                        'judul'      => 'Jadwal Mengajar Hari Ini',
                        'pesan'      => "Tidak ada jadwal tatap muka/KBM untuk Anda pada hari {$hariIni}.",
                        'kategori'   => 'jadwal',
                        'tipe'       => 'info',
                        'icon'       => 'fas fa-calendar-check',
                        'url'        => route('dashboard.agenda-kbm.index'),
                        'created_at' => now(),
                        'time_diff'  => 'Hari Ini',
                        'is_read'    => true,
                        'is_dynamic' => true,
                    ]);
                } else {
                    // Cek Presensi Mengajar Terisi Hari Ini
                    $presensiDone = Schema::hasTable('presensi_mengajar')
                        ? PresensiMengajar::where('ptk_id', $ptkId)->whereDate('tanggal', $today)->count()
                        : 0;

                    if ($presensiDone === 0) {
                        $list->push((object) [
                            'id'         => 'dyn_guru_presensi_empty',
                            'judul'      => 'Pengingat Presensi Mengajar',
                            'pesan'      => "Anda memiliki {$totalKelas} kelas hari ini dan belum mencatat presensi mengajar.",
                            'kategori'   => 'presensi_mengajar',
                            'tipe'       => 'warning',
                            'icon'       => 'fas fa-chalkboard-user',
                            'url'        => route('dashboard.presensi-mengajar.index'),
                            'created_at' => now(),
                            'time_diff'  => 'Hari Ini',
                            'is_read'    => false,
                            'is_dynamic' => true,
                        ]);
                    } elseif ($presensiDone < $totalKelas) {
                        $list->push((object) [
                            'id'         => 'dyn_guru_presensi_partial',
                            'judul'      => 'Presensi Mengajar Belum Lengkap',
                            'pesan'      => "Baru {$presensiDone} dari {$totalKelas} kelas hari ini yang telah dipresensi.",
                            'kategori'   => 'presensi_mengajar',
                            'tipe'       => 'warning',
                            'icon'       => 'fas fa-chalkboard-user',
                            'url'        => route('dashboard.presensi-mengajar.index'),
                            'created_at' => now(),
                            'time_diff'  => 'Hari Ini',
                            'is_read'    => false,
                            'is_dynamic' => true,
                        ]);
                    } else {
                        $list->push((object) [
                            'id'         => 'dyn_guru_presensi_complete',
                            'judul'      => 'Presensi Mengajar Lengkap',
                            'pesan'      => "Seluruh {$totalKelas} kelas jadwal mengajar hari ini telah dipresensi.",
                            'kategori'   => 'presensi_mengajar',
                            'tipe'       => 'success',
                            'icon'       => 'fas fa-circle-check',
                            'url'        => route('dashboard.presensi-mengajar.index'),
                            'created_at' => now(),
                            'time_diff'  => 'Hari Ini',
                            'is_read'    => true,
                            'is_dynamic' => true,
                        ]);
                    }

                    // Cek Agenda KBM Terisi Hari Ini
                    $agendaDone = Schema::hasTable('agenda_kbm')
                        ? AgendaKbm::where('ptk_id', $ptkId)->whereDate('tanggal', $today)->count()
                        : 0;

                    if ($agendaDone < $totalKelas) {
                        $list->push((object) [
                            'id'         => 'dyn_guru_agenda_warning',
                            'judul'      => 'Pengingat Jurnal & Agenda KBM',
                            'pesan'      => "Jurnal materi KBM untuk kelas hari ini belum lengkap ({$agendaDone}/{$totalKelas} terisi).",
                            'kategori'   => 'agenda_kbm',
                            'tipe'       => 'warning',
                            'icon'       => 'fas fa-book-open',
                            'url'        => route('dashboard.agenda-kbm.index'),
                            'created_at' => now(),
                            'time_diff'  => 'Hari Ini',
                            'is_read'    => false,
                            'is_dynamic' => true,
                        ]);
                    } else {
                        $list->push((object) [
                            'id'         => 'dyn_guru_agenda_complete',
                            'judul'      => 'Jurnal & Agenda KBM Lengkap',
                            'pesan'      => 'Seluruh catatan jurnal & agenda materi KBM hari ini telah tercatat.',
                            'kategori'   => 'agenda_kbm',
                            'tipe'       => 'success',
                            'icon'       => 'fas fa-check-double',
                            'url'        => route('dashboard.agenda-kbm.index'),
                            'created_at' => now(),
                            'time_diff'  => 'Hari Ini',
                            'is_read'    => true,
                            'is_dynamic' => true,
                        ]);
                    }
                }
            } catch (\Throwable $e) {}
        }

        // Cek Pengajuan Izin Siswa jika Guru berperan sebagai Wali Kelas
        if (class_exists(RolePermission::class) && Schema::hasTable('presensi_izin')) {
            try {
                $rombelId = RolePermission::getWaliKelasRombelId($currentUser);
                if ($rombelId) {
                    $pendingIzinCount = PresensiIzin::where('rombongan_belajar_id', $rombelId)
                        ->where('status', 'menunggu')
                        ->count();

                    if ($pendingIzinCount > 0) {
                        $list->push((object) [
                            'id'         => 'dyn_guru_wali_pending_izin',
                            'judul'      => 'Permohonan Izin Siswa Menunggu',
                            'pesan'      => "Ada {$pendingIzinCount} permohonan surat izin/sakit siswa di kelas Anda yang menunggu verifikasi.",
                            'kategori'   => 'wali_kelas',
                            'tipe'       => 'warning',
                            'icon'       => 'fas fa-envelope-open-text',
                            'url'        => route('dashboard.wali-kelas.presensi.index'),
                            'created_at' => now(),
                            'time_diff'  => 'Perlu Respon',
                            'is_read'    => false,
                            'is_dynamic' => true,
                        ]);
                    }
                }
            } catch (\Throwable $e) {}
        }

        return $list;
    }

    /**
     * Peringatan & Pengingat Dinamis untuk Tendik
     */
    protected static function getTendikDynamicReminders($currentUser, ?string $ptkId, int $existingDbCount): Collection
    {
        $list = collect();

        // 1. Cek aktivitas Persuratan jika pengguna memiliki hak akses / penugasan Persuratan
        try {
            if (RolePermission::canAccess($currentUser, 'menu_persuratan', 'read') && Schema::hasTable('persuratan')) {
                $pendingSurat = \App\Models\Persuratan::where('status', 'menunggu_disposisi')->count();
                if ($pendingSurat > 0) {
                    $list->push((object) [
                        'id'         => 'dyn_tendik_pending_surat',
                        'judul'      => 'Surat Menunggu Disposisi',
                        'pesan'      => "Terdapat {$pendingSurat} berkas surat yang sedang menunggu telaah atau tindak lanjut disposisi.",
                        'kategori'   => 'persuratan',
                        'tipe'       => 'warning',
                        'icon'       => 'fas fa-envelope-open-text',
                        'url'        => route('dashboard.persuratan.index', ['status' => 'menunggu_disposisi']),
                        'created_at' => now(),
                        'time_diff'  => 'Perlu Tindakan',
                        'is_read'    => false,
                        'is_dynamic' => true,
                    ]);
                }
            }
        } catch (\Throwable $e) {}

        // Jika tidak ada notifikasi DB atau aktivitas dinamis, tampilkan pesan ramah baku
        if ($list->isEmpty() && $existingDbCount === 0) {
            $list->push((object) [
                'id'         => 'dyn_tendik_standby',
                'judul'      => 'Aktivitas Hari Ini',
                'pesan'      => 'Belum ada aktivitas hari ini untuk dikerjakan.',
                'kategori'   => 'tendik',
                'tipe'       => 'info',
                'icon'       => 'fas fa-circle-check',
                'url'        => route('dashboard.tendik'),
                'created_at' => now(),
                'time_diff'  => 'Hari Ini',
                'is_read'    => true,
                'is_dynamic' => true,
            ]);
        }

        return $list;
    }

    /**
     * Peringatan & Pengingat Dinamis untuk Peserta Didik
     */
    protected static function getPesertaDidikDynamicReminders($currentUser, ?string $pdId): Collection
    {
        $list = collect();
        if (!$pdId) return $list;

        $today = now()->toDateString();

        // 1. Cek Presensi Harian Siswa Hari Ini
        if (Schema::hasTable('presensi_harian')) {
            try {
                $presensiHariIni = PresensiHarian::where('peserta_didik_id', $pdId)
                    ->whereDate('tanggal', $today)
                    ->first();

                if ($presensiHariIni) {
                    if ($presensiHariIni->jam_masuk) {
                        $isTepat = ($presensiHariIni->status_ketepatan_masuk ?? 'tepat_waktu') === 'tepat_waktu';
                        $jamFormatted = substr($presensiHariIni->jam_masuk, 0, 5) . ' WIB';

                        $list->push((object) [
                            'id'         => 'dyn_pd_presensi_masuk',
                            'judul'      => $isTepat ? 'Presensi Masuk Tepat Waktu' : 'Presensi Masuk Terlambat',
                            'pesan'      => "Kehadiran masuk tercatat pukul {$jamFormatted} (" . ($isTepat ? 'Tepat Waktu' : "Terlambat {$presensiHariIni->menit_terlambat} mnt") . ').',
                            'kategori'   => 'presensi',
                            'tipe'       => $isTepat ? 'success' : 'warning',
                            'icon'       => 'fas fa-calendar-check',
                            'url'        => route('dashboard.peserta-didik.presensi.index'),
                            'created_at' => Carbon::parse($today . ' ' . $presensiHariIni->jam_masuk),
                            'time_diff'  => 'Hari Ini',
                            'is_read'    => true,
                            'is_dynamic' => true,
                        ]);
                    }

                    if ($presensiHariIni->jam_pulang) {
                        $jamPulangFormatted = substr($presensiHariIni->jam_pulang, 0, 5) . ' WIB';
                        $list->push((object) [
                            'id'         => 'dyn_pd_presensi_pulang',
                            'judul'      => 'Presensi Pulang Tercatat',
                            'pesan'      => "Presensi kepulangan sekolah tercatat pada pukul {$jamPulangFormatted}.",
                            'kategori'   => 'presensi',
                            'tipe'       => 'success',
                            'icon'       => 'fas fa-person-walking-arrow-right',
                            'url'        => route('dashboard.peserta-didik.presensi.index'),
                            'created_at' => Carbon::parse($today . ' ' . $presensiHariIni->jam_pulang),
                            'time_diff'  => 'Hari Ini',
                            'is_read'    => true,
                            'is_dynamic' => true,
                        ]);
                    }
                } else {
                    // Cek jika hari aktif belajar dan belum absen masuk
                    $isHariAktif = true;
                    if (class_exists(PresensiPengaturan::class)) {
                        try {
                            $isHariAktif = PresensiPengaturan::isHariAktif(now());
                        } catch (\Throwable $e) {}
                    }

                    if ($isHariAktif && now()->format('H:i') >= '06:00' && now()->format('H:i') <= '12:00') {
                        $list->push((object) [
                            'id'         => 'dyn_pd_presensi_reminder',
                            'judul'      => 'Pengingat Presensi Gerbang',
                            'pesan'      => 'Anda belum melakukan pemindaian presensi masuk sekolah hari ini.',
                            'kategori'   => 'presensi',
                            'tipe'       => 'warning',
                            'icon'       => 'fas fa-fingerprint',
                            'url'        => route('dashboard.peserta-didik.presensi.index'),
                            'created_at' => now(),
                            'time_diff'  => 'Pagi Ini',
                            'is_read'    => false,
                            'is_dynamic' => true,
                        ]);
                    }
                }
            } catch (\Throwable $e) {}
        }

        // 2. Cek Surat Izin yang Diajukan Siswa
        if (Schema::hasTable('presensi_izin')) {
            try {
                $recentIzins = PresensiIzin::where('peserta_didik_id', $pdId)
                    ->orderBy('created_at', 'desc')
                    ->take(2)
                    ->get();

                foreach ($recentIzins as $iz) {
                    $jenisStr = ucfirst($iz->jenis ?: 'izin');
                    $tglMulai = Carbon::parse($iz->tanggal_mulai)->translatedFormat('d M');

                    if ($iz->status === 'menunggu') {
                        $list->push((object) [
                            'id'         => 'dyn_pd_izin_menunggu_' . $iz->id,
                            'judul'      => "Surat {$jenisStr} Menunggu Verifikasi",
                            'pesan'      => "Permohonan surat {$iz->jenis} periode {$tglMulai} sedang dalam proses verifikasi Wali Kelas.",
                            'kategori'   => 'izin',
                            'tipe'       => 'info',
                            'icon'       => 'fas fa-hourglass-half',
                            'url'        => route('dashboard.peserta-didik.izin.index'),
                            'created_at' => $iz->created_at ?: now(),
                            'time_diff'  => $iz->created_at ? $iz->created_at->diffForHumans(null, true) : 'Menunggu',
                            'is_read'    => false,
                            'is_dynamic' => true,
                        ]);
                    } elseif ($iz->status === 'disetujui' && $iz->updated_at && $iz->updated_at->diffInDays(now()) <= 5) {
                        $list->push((object) [
                            'id'         => 'dyn_pd_izin_acc_' . $iz->id,
                            'judul'      => "Surat {$jenisStr} Disetujui",
                            'pesan'      => "Permohonan surat {$iz->jenis} periode {$tglMulai} telah disetujui oleh Wali Kelas.",
                            'kategori'   => 'izin',
                            'tipe'       => 'success',
                            'icon'       => 'fas fa-circle-check',
                            'url'        => route('dashboard.peserta-didik.izin.index'),
                            'created_at' => $iz->updated_at,
                            'time_diff'  => $iz->updated_at->diffForHumans(null, true),
                            'is_read'    => true,
                            'is_dynamic' => true,
                        ]);
                    } elseif ($iz->status === 'ditolak' && $iz->updated_at && $iz->updated_at->diffInDays(now()) <= 5) {
                        $list->push((object) [
                            'id'         => 'dyn_pd_izin_rej_' . $iz->id,
                            'judul'      => "Surat {$jenisStr} Ditolak",
                            'pesan'      => "Permohonan surat {$iz->jenis} ditolak: " . ($iz->catatan_petugas ?: 'Silakan hubungi Wali Kelas.'),
                            'kategori'   => 'izin',
                            'tipe'       => 'danger',
                            'icon'       => 'fas fa-circle-xmark',
                            'url'        => route('dashboard.peserta-didik.izin.index'),
                            'created_at' => $iz->updated_at,
                            'time_diff'  => $iz->updated_at->diffForHumans(null, true),
                            'is_read'    => false,
                            'is_dynamic' => true,
                        ]);
                    }
                }
            } catch (\Throwable $e) {}
        }

        return $list;
    }

    /**
     * Peringatan & Pengingat Dinamis untuk Administrator
     */
    protected static function getAdminDynamicReminders($currentUser): Collection
    {
        $list = collect();

        // Cek jika ada surat izin yang menunggu verifikasi global
        if (Schema::hasTable('presensi_izin')) {
            try {
                $pendingCount = PresensiIzin::where('status', 'menunggu')->count();
                if ($pendingCount > 0) {
                    $list->push((object) [
                        'id'         => 'dyn_admin_pending_izin',
                        'judul'      => 'Pengajuan Izin Siswa',
                        'pesan'      => "Terdapat {$pendingCount} permohonan surat izin/sakit siswa di sekolah yang menunggu verifikasi.",
                        'kategori'   => 'presensi',
                        'tipe'       => 'warning',
                        'icon'       => 'fas fa-file-signature',
                        'url'        => route('dashboard.presensi.index'),
                        'created_at' => now(),
                        'time_diff'  => 'Perlu Tindakan',
                        'is_read'    => false,
                        'is_dynamic' => true,
                    ]);
                }
            } catch (\Throwable $e) {}
        }

        // Cek arsip surat dinas yang menunggu disposisi
        if (Schema::hasTable('persuratan')) {
            try {
                $pendingSurat = \App\Models\Persuratan::where('status', 'menunggu_disposisi')->count();
                if ($pendingSurat > 0) {
                    $list->push((object) [
                        'id'         => 'dyn_admin_pending_surat',
                        'judul'      => 'Disposisi Surat Masuk',
                        'pesan'      => "Terdapat {$pendingSurat} surat dinas baru yang perlu ditelaah dan diberikan lembar disposisi.",
                        'kategori'   => 'persuratan',
                        'tipe'       => 'warning',
                        'icon'       => 'fas fa-envelope-open-text',
                        'url'        => route('dashboard.persuratan.index', ['status' => 'menunggu_disposisi']),
                        'created_at' => now(),
                        'time_diff'  => 'Perlu Respon',
                        'is_read'    => false,
                        'is_dynamic' => true,
                    ]);
                }
            } catch (\Throwable $e) {}
        }

        // Status Sistem SAE
        $list->push((object) [
            'id'         => 'dyn_admin_status',
            'judul'      => 'Status Sistem SAE',
            'pesan'      => 'Layanan basis data dan modul aplikasi SAE berjalan stabil.',
            'kategori'   => 'sistem',
            'tipe'       => 'success',
            'icon'       => 'fas fa-shield-halved',
            'url'        => route('dashboard.admin'),
            'created_at' => now(),
            'time_diff'  => 'Aktif',
            'is_read'    => true,
            'is_dynamic' => true,
        ]);

        return $list;
    }
}
