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

                if (RolePermission::canAccess($currentUser, 'menu_kesiswaan', 'read') && !RolePermission::canAccess($currentUser, 'menu_kepegawaian', 'read')) {
                    $footerUrl = route('dashboard.kesiswaan.index');
                    $footerText = 'Buka Administrasi Kesiswaan';
                } elseif (RolePermission::canAccess($currentUser, 'menu_kepegawaian', 'read') && !RolePermission::canAccess($currentUser, 'menu_kesiswaan', 'read')) {
                    $footerUrl = route('dashboard.kepegawaian.index');
                    $footerText = 'Buka Kepegawaian GTK & KGB';
                } elseif (RolePermission::canAccess($currentUser, 'menu_persuratan', 'read') && !RolePermission::canAccess($currentUser, 'menu_kesiswaan', 'read')) {
                    $footerUrl = route('dashboard.persuratan.index');
                    $footerText = 'Buka Persuratan & Disposisi';
                }
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

        // Cek Jadwal Mengajar Guru Hari Ini (Hanya dari mode yang resmi diberlakukan)
        if (Schema::hasTable('jadwal_kbm')) {
            try {
                $isJadwalDiberlakukan = \App\Models\JadwalPengaturan::isDiberlakukan();
                $modeAktif = \App\Models\JadwalPengaturan::getModeAktif();

                if ($isJadwalDiberlakukan && $modeAktif) {
                    $jadwalHariIni = JadwalKbm::where('is_active', true)
                        ->where('sumber', $modeAktif)
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
     * Peringatan & Pengingat Dinamis untuk Tendik (Disesuaikan Per Bidang Tugas Terkait)
     */
    protected static function getTendikDynamicReminders($currentUser, ?string $ptkId, int $existingDbCount): Collection
    {
        $list = collect();

        $userId = (string) (is_array($currentUser)
            ? ($currentUser['pengguna_id'] ?? ($currentUser['id'] ?? ''))
            : ($currentUser->pengguna_id ?? ($currentUser->id ?? '')));

        // Ambil daftar kode tugas tambahan aktif personil
        $duties = [];
        if (Schema::hasTable('ptk_tugas_tambahan') && Schema::hasTable('ref_tugas_tambahan')) {
            try {
                $duties = \DB::table('ptk_tugas_tambahan as ptt')
                    ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                    ->where('ptt.is_active', true)
                    ->where('rtt.is_active', true)
                    ->where(function ($q) use ($userId, $ptkId) {
                        if ($userId) $q->where('ptt.user_id', $userId);
                        if ($ptkId) $q->orWhere('ptt.ptk_id', $ptkId);
                    })
                    ->pluck('rtt.kode')
                    ->toArray();
            } catch (\Throwable $e) {}
        }

        $isKepalaTas = in_array('KEPALA_TAS', $duties, true);
        $isPersuratan = $isKepalaTas || in_array('STAF_PERSURATAN', $duties, true) || (empty($duties) && RolePermission::canAccess($currentUser, 'menu_persuratan', 'read'));
        $isKesiswaan = $isKepalaTas || in_array('STAF_KESISWAAN', $duties, true) || RolePermission::canAccess($currentUser, 'menu_kesiswaan', 'read');
        $isKepegawaian = $isKepalaTas || in_array('STAF_KEPEGAWAIAN', $duties, true) || RolePermission::canAccess($currentUser, 'menu_kepegawaian', 'read');
        $isGuruPiket = in_array('GURU_PIKET', $duties, true);

        // 1. BIDANG PERSURATAN: Notifikasi surat masuk & disposisi
        if ($isPersuratan && Schema::hasTable('persuratan')) {
            try {
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

                if (Schema::hasTable('persuratan_disposisi') && $ptkId) {
                    $disposisiSaya = \App\Models\PersuratanDisposisi::where('ptk_id_tujuan', $ptkId)
                        ->where('status', 'menunggu')
                        ->count();

                    if ($disposisiSaya > 0) {
                        $list->push((object) [
                            'id'         => 'dyn_tendik_disposisi_saya',
                            'judul'      => 'Disposisi Surat Masuk Untuk Anda',
                            'pesan'      => "Ada {$disposisiSaya} instruksi lembar disposisi pimpinan yang ditujukan kepada Anda.",
                            'kategori'   => 'persuratan',
                            'tipe'       => 'info',
                            'icon'       => 'fas fa-file-signature',
                            'url'        => route('dashboard.persuratan.index'),
                            'created_at' => now(),
                            'time_diff'  => 'Baru',
                            'is_read'    => false,
                            'is_dynamic' => true,
                        ]);
                    }
                }
            } catch (\Throwable $e) {}
        }

        // 2. BIDANG KESISWAAN: Notifikasi berkas fisik siswa baru & buku klaper
        if ($isKesiswaan) {
            try {
                // Notifikasi berkas fisik yang belum lengkap
                if (Schema::hasTable('kesiswaan_berkas_verifikasi')) {
                    $unverifiedBerkas = \App\Models\KesiswaanBerkasVerifikasi::where(function ($q) {
                        $q->where('akta_kelahiran', false)
                          ->orWhere('kartu_keluarga', false)
                          ->orWhere('ijazah_smp', false);
                    })->count();

                    if ($unverifiedBerkas > 0) {
                        $list->push((object) [
                            'id'         => 'dyn_tendik_berkas_unverified',
                            'judul'      => 'Verifikasi Berkas Siswa Baru',
                            'pesan'      => "Terdapat {$unverifiedBerkas} siswa baru yang berkas persyaratannya belum lengkap terverifikasi.",
                            'kategori'   => 'kesiswaan',
                            'tipe'       => 'warning',
                            'icon'       => 'fas fa-folder-open',
                            'url'        => route('dashboard.kesiswaan.index', ['tab' => 'berkas']),
                            'created_at' => now(),
                            'time_diff'  => 'Perlu Verifikasi',
                            'is_read'    => false,
                            'is_dynamic' => true,
                        ]);
                    }
                }

                // Notifikasi siswa aktif yang belum tercatat di buku klaper
                if (Schema::hasTable('peserta_didik') && Schema::hasTable('kesiswaan_buku_klaper')) {
                    $unregisteredKlaper = \App\Models\PesertaDidik::whereDoesntHave('klaper')->count();
                    if ($unregisteredKlaper > 0) {
                        $list->push((object) [
                            'id'         => 'dyn_tendik_klaper_unsynced',
                            'judul'      => 'Sinkronisasi Buku Klaper',
                            'pesan'      => "Ada {$unregisteredKlaper} siswa aktif belum terdaftar pada Buku Klaper sekolah.",
                            'kategori'   => 'kesiswaan',
                            'tipe'       => 'info',
                            'icon'       => 'fas fa-address-book',
                            'url'        => route('dashboard.kesiswaan.index', ['tab' => 'klaper']),
                            'created_at' => now(),
                            'time_diff'  => 'Belum Sinkron',
                            'is_read'    => false,
                            'is_dynamic' => true,
                        ]);
                    }
                }
            } catch (\Throwable $e) {}
        }

        // 3. BIDANG KEPEGAWAIAN GTK: Notifikasi KGB & Cuti
        if ($isKepegawaian) {
            try {
                // Notifikasi KGB jatuh tempo dalam 90 hari
                if (Schema::hasTable('gtk_kgb_tracker')) {
                    $today = now()->toDateString();
                    $in90Days = now()->addDays(90)->toDateString();
                    $kgbJatuhTempo = \App\Models\GtkKgbTracker::whereBetween('tmt_baru_target', [$today, $in90Days])
                        ->where('status_usulan', '!=', 'terbit_sk')
                        ->count();

                    if ($kgbJatuhTempo > 0) {
                        $list->push((object) [
                            'id'         => 'dyn_tendik_kgb_due',
                            'judul'      => 'Kenaikan Gaji Berkala (KGB) Jatuh Tempo',
                            'pesan'      => "Terdapat {$kgbJatuhTempo} GTK yang akan jatuh tempo KGB dalam 90 hari ke depan.",
                            'kategori'   => 'kepegawaian',
                            'tipe'       => 'warning',
                            'icon'       => 'fas fa-business-time',
                            'url'        => route('dashboard.kepegawaian.index', ['tab' => 'kgb']),
                            'created_at' => now(),
                            'time_diff'  => 'Segera Usulkan',
                            'is_read'    => false,
                            'is_dynamic' => true,
                        ]);
                    }
                }

                // Notifikasi permohonan cuti / tugas dinas yang diajukan
                if (Schema::hasTable('gtk_cuti_izin')) {
                    $pendingCuti = \App\Models\GtkCutiIzin::where('status', 'diajukan')->count();
                    if ($pendingCuti > 0) {
                        $list->push((object) [
                            'id'         => 'dyn_tendik_cuti_pending',
                            'judul'      => 'Permohonan Cuti / Izin GTK',
                            'pesan'      => "Terdapat {$pendingCuti} permohonan cuti GTK yang sedang menunggu proses verifikasi.",
                            'kategori'   => 'kepegawaian',
                            'tipe'       => 'info',
                            'icon'       => 'fas fa-plane-departure',
                            'url'        => route('dashboard.kepegawaian.index', ['tab' => 'cuti']),
                            'created_at' => now(),
                            'time_diff'  => 'Menunggu',
                            'is_read'    => false,
                            'is_dynamic' => true,
                        ]);
                    }
                }
            } catch (\Throwable $e) {}
        }

        // 4. BIDANG GURU PIKET: Notifikasi izin keluar masuk siswa
        if ($isGuruPiket && Schema::hasTable('presensi_izin')) {
            try {
                $pendingIzinToday = \App\Models\PresensiIzin::whereDate('tanggal_mulai', now()->toDateString())
                    ->where('status', 'menunggu')
                    ->count();

                if ($pendingIzinToday > 0) {
                    $list->push((object) [
                        'id'         => 'dyn_tendik_piket_izin',
                        'judul'      => 'e-Izin Keluar Masuk Siswa',
                        'pesan'      => "Ada {$pendingIzinToday} siswa mengajukan izin hari ini yang memerlukan konfirmasi guru piket.",
                        'kategori'   => 'piket',
                        'tipe'       => 'warning',
                        'icon'       => 'fas fa-person-walking-dashed-line-arrow-right',
                        'url'        => route('dashboard.peserta-didik.izin.index'),
                        'created_at' => now(),
                        'time_diff'  => 'Hari Ini',
                        'is_read'    => false,
                        'is_dynamic' => true,
                    ]);
                }
            } catch (\Throwable $e) {}
        }

        // Jika tidak ada notifikasi DB atau aktivitas dinamis, tampilkan pesan ramah baku
        if ($list->isEmpty() && $existingDbCount === 0) {
            $list->push((object) [
                'id'         => 'dyn_tendik_standby',
                'judul'      => 'Aktivitas Hari Ini',
                'pesan'      => 'Belum ada aktivitas baru pada bidang tugas Anda.',
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
