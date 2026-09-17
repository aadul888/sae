<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RolePermission extends Model
{
    use HasFactory;

    protected $table = 'role_permissions';

    protected $fillable = [
        'role',
        'permission_key',
        'is_allowed',
        'can_create',
        'can_read',
        'can_update',
        'can_delete',
    ];

    protected $casts = [
        'is_allowed' => 'boolean',
        'can_create' => 'boolean',
        'can_read' => 'boolean',
        'can_update' => 'boolean',
        'can_delete' => 'boolean',
    ];

    /**
     * Definisi dasar modul & fitur bawaan sistem yang dikelompokkan berdasarkan cluster modul.
     */
    public static function getBasePermissions(): array
    {
        return [
            'Menu Utama' => [
                'menu_dashboard' => [
                    'label' => 'Dashboard Utama',
                    'icon' => 'fa-gauge-high',
                    'roles' => ['admin', 'guru', 'tendik', 'peserta_didik'],
                ],
                'menu_dapodik' => [
                    'label' => 'Tarik Data Dapodik',
                    'icon' => 'fa-cloud-arrow-down',
                    'roles' => ['admin'],
                ],
            ],

            'Master Data' => [
                'menu_kompetensi_keahlian' => [
                    'label' => 'Kompetensi Keahlian',
                    'icon' => 'fa-layer-group',
                    'roles' => ['admin'],
                ],
                'menu_rombel' => [
                    'label' => 'Rombongan Belajar',
                    'icon' => 'fa-chalkboard-user',
                    'roles' => ['admin', 'guru', 'tendik'],
                ],
                'menu_pembelajaran' => [
                    'label' => 'Pembelajaran',
                    'icon' => 'fa-book-bookmark',
                    'roles' => ['admin', 'guru'],
                ],
                'menu_jadwal_kbm' => [
                    'label' => 'Jadwal KBM',
                    'icon' => 'fa-calendar-alt',
                    'roles' => ['admin', 'guru', 'tendik', 'peserta_didik'],
                ],
                'menu_kalender_pendidikan' => [
                    'label' => 'Kalender Pendidikan',
                    'icon' => 'fa-calendar-days',
                    'roles' => ['admin', 'guru', 'tendik'],
                ],
            ],

            'Manajemen Data' => [
                'menu_peserta_didik_aktif' => [
                    'label' => 'Peserta Didik Aktif',
                    'icon' => 'fa-user-graduate',
                    'roles' => ['admin', 'guru', 'tendik'],
                ],
                'menu_guru_aktif' => [
                    'label' => 'Guru Aktif',
                    'icon' => 'fa-chalkboard-user',
                    'roles' => ['admin', 'guru', 'tendik'],
                ],
                'menu_tendik_aktif' => [
                    'label' => 'Tendik Aktif',
                    'icon' => 'fa-id-badge',
                    'roles' => ['admin', 'guru', 'tendik'],
                ],
                'menu_berkas_peserta_didik' => [
                    'label' => 'Berkas Peserta Didik',
                    'icon' => 'fa-folder-open',
                    'roles' => ['admin', 'guru', 'tendik'],
                ],
                'menu_perubahan_data' => [
                    'label' => 'Perubahan Data',
                    'icon' => 'fa-user-pen',
                    'roles' => ['admin', 'guru', 'tendik'],
                ],
                'menu_peserta_didik_tidak_aktif' => [
                    'label' => 'Peserta Didik Tidak Aktif',
                    'icon' => 'fa-user-xmark',
                    'roles' => ['admin', 'tendik'],
                ],
                'menu_guru_tidak_aktif' => [
                    'label' => 'Guru Tidak Aktif',
                    'icon' => 'fa-user-slash',
                    'roles' => ['admin', 'tendik'],
                ],
                'menu_tendik_tidak_aktif' => [
                    'label' => 'Tendik Tidak Aktif',
                    'icon' => 'fa-id-badge',
                    'roles' => ['admin', 'tendik'],
                ],
            ],

            'Layanan Digital' => [
                'menu_formulir' => [
                    'label' => 'Formulir & Survei',
                    'icon' => 'fa-clipboard-list',
                    'roles' => ['admin', 'guru', 'tendik', 'peserta_didik'],
                ],
                'menu_pengumuman' => [
                    'label' => 'Pengumuman & Broadcast',
                    'icon' => 'fa-bullhorn',
                    'roles' => ['admin', 'guru', 'tendik', 'peserta_didik'],
                ],
                'menu_rfid' => [
                    'label' => 'RFID & Presensi Realtime',
                    'icon' => 'fa-id-card',
                    'roles' => ['admin', 'guru', 'tendik'],
                ],
                'menu_e_izin' => [
                    'label' => 'E-Izin',
                    'icon' => 'fa-file-signature',
                    'roles' => ['admin', 'guru', 'tendik', 'peserta_didik'],
                ],
                'menu_poin' => [
                    'label' => 'Poin & Pelanggaran',
                    'icon' => 'fa-star-half-stroke',
                    'roles' => ['admin', 'guru', 'tendik'],
                ],
                'menu_kelulusan' => [
                    'label' => 'Kelulusan Peserta Didik',
                    'icon' => 'fa-graduation-cap',
                    'roles' => ['admin', 'guru', 'tendik', 'peserta_didik'],
                ],
            ],

            'Administrasi Guru' => [
                'menu_presensi_mengajar' => [
                    'label' => 'Presensi Mengajar',
                    'icon' => 'fa-calendar-check',
                    'roles' => ['admin', 'guru'],
                ],
                'menu_agenda_kbm' => [
                    'label' => 'Jurnal & Agenda KBM',
                    'icon' => 'fa-book-open-reader',
                    'roles' => ['admin', 'guru'],
                ],
                'menu_penilaian' => [
                    'label' => 'Penilaian Peserta Didik',
                    'icon' => 'fa-graduation-cap',
                    'roles' => ['admin', 'guru'],
                ],
            ],

            'Wali Kelas' => [
                'menu_wali_kelas_aktif' => [
                    'label' => 'Peserta Didik Aktif (Wali Kelas)',
                    'icon' => 'fa-user-graduate',
                    'roles' => ['admin', 'guru'],
                ],
                'menu_wali_kelas_tidak_aktif' => [
                    'label' => 'Peserta Didik Tidak Aktif (Wali Kelas)',
                    'icon' => 'fa-user-xmark',
                    'roles' => ['admin', 'guru'],
                ],
                'menu_wali_kelas_presensi' => [
                    'label' => 'Presensi Kelas (Wali Kelas)',
                    'icon' => 'fa-clipboard-user',
                    'roles' => ['admin', 'guru'],
                ],
            ],

            'Administrasi Tendik' => [
                'menu_buku_tamu' => [
                    'label' => 'Buku Tamu',
                    'icon' => 'fa-address-book',
                    'roles' => ['admin', 'tendik'],
                ],
                'menu_inventaris' => [
                    'label' => 'Inventaris Sarpras',
                    'icon' => 'fa-boxes-stacked',
                    'roles' => ['admin', 'tendik'],
                ],
                'menu_agenda' => [
                    'label' => 'Agenda Kelas',
                    'icon' => 'fa-clipboard-list',
                    'roles' => ['admin', 'guru', 'tendik'],
                ],
            ],

            'Portal Peserta Didik' => [
                'menu_surat_izin_pd' => [
                    'label' => 'Surat Izin & Sakit (Peserta Didik)',
                    'icon' => 'fa-envelope-open-text',
                    'roles' => ['peserta_didik', 'admin'],
                ],
                'menu_riwayat_rfid' => [
                    'label' => 'Riwayat Presensi RFID',
                    'icon' => 'fa-id-card-clip',
                    'roles' => ['peserta_didik'],
                ],
                'menu_jadwal_pelajaran' => [
                    'label' => 'Jadwal Pelajaran',
                    'icon' => 'fa-calendar-days',
                    'roles' => ['peserta_didik'],
                ],
                'menu_rapor' => [
                    'label' => 'Transkrip & Rapor',
                    'icon' => 'fa-file-lines',
                    'roles' => ['peserta_didik'],
                ],
                'menu_validasi_berkas' => [
                    'label' => 'Validasi Berkas & Ijazah',
                    'icon' => 'fa-folder-open',
                    'roles' => ['peserta_didik'],
                ],
            ],

            'Sistem & Pengaturan' => [
                'menu_pengguna' => [
                    'label' => 'Manajemen Pengguna',
                    'icon' => 'fa-users-gear',
                    'roles' => ['admin'],
                ],
                'menu_hak_akses' => [
                    'label' => 'Pengaturan Hak Akses',
                    'icon' => 'fa-shield-halved',
                    'roles' => ['admin'],
                ],
                'menu_pengaturan' => [
                    'label' => 'Identitas Sekolah & Pengaturan',
                    'icon' => 'fa-sliders',
                    'roles' => ['admin'],
                ],
                'menu_maintenance' => [
                    'label' => 'Arsip & Maintenance',
                    'icon' => 'fa-server',
                    'roles' => ['admin'],
                ],
                'menu_update' => [
                    'label' => 'Update Sistem',
                    'icon' => 'fa-arrows-rotate',
                    'roles' => ['admin'],
                ],
            ],

            'Fitur Operasional' => [
                'fitur_pengguna_edit' => [
                    'label' => 'Edit Data Pengguna',
                    'icon' => 'fa-user-pen',
                    'roles' => ['admin'],
                ],
                'fitur_pengguna_hapus' => [
                    'label' => 'Hapus Akun Pengguna',
                    'icon' => 'fa-user-xmark',
                    'roles' => ['admin'],
                ],
                'fitur_pengguna_reset' => [
                    'label' => 'Reset Password Pengguna',
                    'icon' => 'fa-key',
                    'roles' => ['admin'],
                ],
                'fitur_dapodik_sync' => [
                    'label' => 'Generate & Ubah Web Service Dapodik',
                    'icon' => 'fa-link',
                    'roles' => ['admin'],
                ],
                'fitur_system_update' => [
                    'label' => 'Eksekusi Update Sistem',
                    'icon' => 'fa-download',
                    'roles' => ['admin'],
                ],
            ],
        ];
    }

    /**
     * Otomatis mendeteksi modul menu & fitur baru yang dibuat di sistem
     * dengan memindai berkas route, controller, blade view, dan rekaman database.
     */
    public static function discoverSystemModules(array $baseConfigs = []): array
    {
        $discovered = [];
        $knownKeys = [];
        foreach ($baseConfigs as $group => $items) {
            foreach (array_keys($items) as $k) {
                $knownKeys[$k] = true;
            }
        }

        // 1. Kumpulkan seluruh berkas rute, controller, dan view untuk pemindaian otomatis
        $filesToScan = array_merge(
            glob(base_path('routes/*.php')) ?: [],
            glob(app_path('Http/Controllers/*.php')) ?: [],
            glob(resource_path('views/partials/*.blade.php')) ?: [],
            glob(resource_path('views/dashboard/*.blade.php')) ?: []
        );

        foreach ($filesToScan as $filePath) {
            if (!file_exists($filePath)) continue;
            $content = @file_get_contents($filePath);
            if (!$content) continue;

            preg_match_all('/can\(\s*[\'"]((?:menu_|fitur_)[a-zA-Z0-9_]+)[\'"]\s*\)/', $content, $m1);
            preg_match_all('/permission:((?:menu_|fitur_)[a-zA-Z0-9_]+)/', $content, $m2);
            preg_match_all('/canAccess\([^,]+,\s*[\'"]((?:menu_|fitur_)[a-zA-Z0-9_]+)[\'"]/', $content, $m3);

            $foundKeys = array_unique(array_merge($m1[1] ?? [], $m2[1] ?? [], $m3[1] ?? []));
            foreach ($foundKeys as $key) {
                if (!isset($knownKeys[$key]) && !isset($discovered[$key])) {
                    $cleanName = str_replace(['menu_', 'fitur_', '_'], ['', '', ' '], $key);
                    $label = ucwords($cleanName);

                    $group = str_starts_with($key, 'fitur_') ? 'Fitur Operasional' : 'Modul Sistem Baru';
                    $icon = str_starts_with($key, 'fitur_') ? 'fa-screwdriver-wrench' : 'fa-cube';

                    if (str_contains($key, 'guru')) {
                        $group = 'Administrasi Guru';
                        $icon = 'fa-chalkboard-user';
                    } elseif (str_contains($key, 'tendik')) {
                        $group = 'Administrasi Tendik';
                        $icon = 'fa-id-badge';
                    } elseif (str_contains($key, 'peserta_didik') || str_contains($key, 'siswa') || str_contains($key, 'santri')) {
                        $group = 'Portal Peserta Didik';
                        $icon = 'fa-user-graduate';
                    } elseif (str_contains($key, 'perpustakaan') || str_contains($key, 'buku')) {
                        $group = 'Layanan Digital';
                        $icon = 'fa-book';
                    } elseif (str_contains($key, 'keuangan') || str_contains($key, 'spp') || str_contains($key, 'bayar')) {
                        $group = 'Layanan Digital';
                        $icon = 'fa-wallet';
                    } elseif (str_contains($key, 'bk') || str_contains($key, 'konseling')) {
                        $group = 'Layanan Digital';
                        $icon = 'fa-user-nurse';
                    }

                    $discovered[$key] = [
                        'label' => $label,
                        'icon' => $icon,
                        'group' => $group,
                        'roles' => ['admin'],
                    ];
                }
            }
        }

        // 2. Deteksi modul yang tersimpan di tabel role_permissions database (agar modul kustom/mendatang selalu terbaca)
        if (Schema::hasTable('role_permissions')) {
            $dbKeys = DB::table('role_permissions')->distinct()->pluck('permission_key')->toArray();
            foreach ($dbKeys as $key) {
                if (!isset($knownKeys[$key]) && !isset($discovered[$key])) {
                    $cleanName = str_replace(['menu_', 'fitur_', '_'], ['', '', ' '], $key);
                    $isFitur = str_starts_with($key, 'fitur_');
                    $discovered[$key] = [
                        'label' => ucwords($cleanName),
                        'icon' => $isFitur ? 'fa-screwdriver-wrench' : 'fa-cube',
                        'group' => $isFitur ? 'Fitur Operasional' : 'Modul Sistem Baru',
                        'roles' => ['admin'],
                    ];
                }
            }
        }

        return $discovered;
    }

    /**
     * Dapatkan seluruh permission sistem (Base + Otomatis Terdeteksi)
     */
    public static function getAvailablePermissions(): array
    {
        $configs = self::getBasePermissions();
        $discovered = self::discoverSystemModules($configs);

        foreach ($discovered as $key => $meta) {
            $grp = $meta['group'] ?? 'Modul Sistem Baru';
            $configs[$grp][$key] = [
                'label' => $meta['label'],
                'icon' => $meta['icon'],
                'roles' => $meta['roles'] ?? ['admin'],
            ];
        }

        return $configs;
    }

    /**
     * Dapatkan semua modul sistem yang valid (khusus menu_)
     */
    public static function getAllSystemModules(): array
    {
        $all = self::getAvailablePermissions();
        $modules = [];
        foreach ($all as $group => $items) {
            foreach ($items as $key => $conf) {
                if (str_starts_with($key, 'menu_')) {
                    $modules[$key] = [
                        'key' => $key,
                        'label' => $conf['label'],
                        'icon' => $conf['icon'],
                        'group' => $group,
                    ];
                }
            }
        }
        return $modules;
    }

    /**
     * Dapatkan definisi konfigurasi untuk permission tertentu (Mendukung fallback dinamis masa depan)
     */
    public static function getPermissionConfig(string $key): ?array
    {
        $all = self::getAvailablePermissions();
        foreach ($all as $group => $items) {
            if (isset($items[$key])) {
                $item = $items[$key];
                $item['group'] = $group;
                return $item;
            }
        }

        // Fallback dinamis jika modul baru belum terdaftar secara statis
        if (!empty($key)) {
            $isFitur = str_starts_with($key, 'fitur_');
            $cleanName = str_replace(['menu_', 'fitur_', '_'], ['', '', ' '], $key);
            return [
                'label' => ucwords($cleanName),
                'icon' => $isFitur ? 'fa-screwdriver-wrench' : 'fa-cube',
                'group' => $isFitur ? 'Fitur Operasional' : 'Modul Sistem Baru',
                'roles' => ['admin'],
            ];
        }

        return null;
    }

    /**
     * Cek apakah pengguna saat ini adalah Peserta Didik yang ditunjuk sebagai Koordinator Kelas
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

        if (Schema::hasTable('peserta_didik_meta')) {
            return (bool) DB::table('peserta_didik_meta')
                ->where('peserta_didik_id', $pdId)
                ->where('is_koordinator', true)
                ->exists();
        }

        return false;
    }

    /**
     * Cek apakah pengguna saat ini adalah Wali Kelas atau Administrator (atau Koordinator Kelas)
     */
    public static function isWaliKelasOrAdmin(mixed $userOrRole = null, ?string $rombelNameOrId = null): bool
    {
        $user = $userOrRole ?: session('user');
        if (!$user) return false;

        $role = is_string($user) ? $user : (is_array($user) ? ($user['role'] ?? '') : ($user->role ?? ''));
        if ($role === 'admin') {
            return true;
        }

        // Jika peserta didik ditunjuk sebagai Koordinator Kelas
        if ($role === 'peserta_didik') {
            if (self::isKoordinator($user)) {
                if (!$rombelNameOrId) return true;

                $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
                if ($pdId && Schema::hasTable('peserta_didik')) {
                    $pd = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
                    if ($pd && ($pd->rombongan_belajar_id === $rombelNameOrId || strcasecmp(trim((string)$pd->nama_rombel), trim((string)$rombelNameOrId)) === 0)) {
                        return true;
                    }
                }
            }
            return false;
        }

        if ($role !== 'guru') {
            return false;
        }

        $userId = is_array($user) ? ($user['id'] ?? ($user['pengguna_id'] ?? null)) : ($user->id ?? ($user->pengguna_id ?? null));
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

        if (!$userId && !$ptkId) {
            return false;
        }

        // Cek di ptk_tugas_tambahan
        if (Schema::hasTable('ptk_tugas_tambahan') && Schema::hasTable('ref_tugas_tambahan')) {
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

        // Fallback: cek langsung di tabel rombongan_belajar jika ptk_id cocok
        if ($ptkId && Schema::hasTable('rombongan_belajar')) {
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
     * Dapatkan nama rombel yang diampu oleh Wali Kelas atau Koordinator Kelas saat ini
     */
    public static function getWaliKelasRombel(mixed $userOrRole = null): ?string
    {
        $user = $userOrRole ?: session('user');
        if (!$user) return null;

        $role = is_string($user) ? $user : (is_array($user) ? ($user['role'] ?? '') : ($user->role ?? ''));

        // Jika Koordinator Kelas (Peserta Didik)
        if ($role === 'peserta_didik' && self::isKoordinator($user)) {
            $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
            if ($pdId && Schema::hasTable('peserta_didik')) {
                return DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->value('nama_rombel');
            }
        }

        $userId = is_array($user) ? ($user['id'] ?? ($user['pengguna_id'] ?? null)) : ($user->id ?? ($user->pengguna_id ?? null));
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        if (!$userId && !$ptkId) return null;

        if (Schema::hasTable('ptk_tugas_tambahan') && Schema::hasTable('ref_tugas_tambahan')) {
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

        if ($ptkId && Schema::hasTable('rombongan_belajar')) {
            return DB::table('rombongan_belajar')->where('ptk_id', $ptkId)->value('nama');
        }

        return null;
    }

    /**
     * Dapatkan data rombel lengkap yang diampu oleh Wali Kelas atau Koordinator Kelas saat ini
     */
    public static function getWaliKelasRombelInfo(mixed $userOrRole = null): ?object
    {
        $user = $userOrRole ?: session('user');
        if (!$user) return null;

        $role = is_string($user) ? $user : (is_array($user) ? ($user['role'] ?? '') : ($user->role ?? ''));

        // Jika Koordinator Kelas (Peserta Didik)
        if ($role === 'peserta_didik' && self::isKoordinator($user)) {
            $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
            if ($pdId && Schema::hasTable('peserta_didik')) {
                $pd = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
                if ($pd && $pd->rombongan_belajar_id && Schema::hasTable('rombongan_belajar')) {
                    $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $pd->rombongan_belajar_id)->first();
                    if ($rombel) return $rombel;
                }
            }
        }

        $userId = is_array($user) ? ($user['id'] ?? ($user['pengguna_id'] ?? null)) : ($user->id ?? ($user->pengguna_id ?? null));
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        if (!$userId && !$ptkId) return null;

        if (Schema::hasTable('ptk_tugas_tambahan') && Schema::hasTable('ref_tugas_tambahan')) {
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

            if ($rombelId && Schema::hasTable('rombongan_belajar')) {
                $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $rombelId)->first();
                if ($rombel) return $rombel;
            }
        }

        if ($ptkId && Schema::hasTable('rombongan_belajar')) {
            return DB::table('rombongan_belajar')->where('ptk_id', $ptkId)->first();
        }

        return null;
    }

    /**
     * Cek apakah role / user tertentu diizinkan mengakses permission tertentu dengan aksi CRUD spesifik.
     * Mendukung evaluasi gabungan (Role Dasar + Izin Tugas Tambahan Aktif).
     *
     * @param mixed $userOrRole Objek User, array sesi, atau string peran ('admin', 'guru', 'tendik', 'peserta_didik')
     * @param string $permissionKey Key modul (contoh: 'menu_peserta_didik_aktif')
     * @param string $action Aksi operasional: 'read' (default), 'create', 'update', 'delete'
     */
    public static function canAccess(mixed $userOrRole, string $permissionKey, string $action = 'read'): bool
    {
        // Jika tabel belum ada (sebelum migrasi selesai), fallback allow default admin
        if (!Schema::hasTable('role_permissions')) {
            $r = is_string($userOrRole) ? $userOrRole : (is_array($userOrRole) ? ($userOrRole['role'] ?? '') : ($userOrRole->role ?? ''));
            return $r === 'admin';
        }

        $user = $userOrRole ?: session('user');
        $role = is_string($user) ? $user : (is_array($user) ? ($user['role'] ?? 'peserta_didik') : ($user->role ?? 'peserta_didik'));

        $action = strtolower(trim($action));
        $actionCol = 'can_' . $action;
        if (!in_array($actionCol, ['can_create', 'can_read', 'can_update', 'can_delete'])) {
            $actionCol = 'can_read';
        }

        // Khusus Peserta Didik yang ditunjuk sebagai Koordinator Kelas (Membantu tugas Wali Kelas)
        if ($role === 'peserta_didik' && self::isKoordinator($user)) {
            if (in_array($permissionKey, [
                'menu_wali_kelas_aktif',
                'menu_wali_kelas_presensi',
            ], true)) {
                return in_array($actionCol, ['can_read', 'can_create', 'can_update']);
            }
        }

        // 1. Otoritas Utama: Izin peran tersimpan di database
        $row = self::where('role', $role)->where('permission_key', $permissionKey)->first();
        if ($row) {
            // Jika modul dinonaktifkan (is_allowed false), seluruh aksi ditolak
            if (!$row->is_allowed) {
                return false;
            }

            // Jika mengecek izin baca (read)
            if ($actionCol === 'can_read') {
                return (bool) $row->can_read;
            }

            // Jika mengecek izin mutasi (create, update, delete):
            // Wajib memenuhi can_read true dan kolom aksi spesifik true
            return (bool) ($row->can_read && $row->{$actionCol});
        }

        // 2. Evaluasi izin dari tugas tambahan aktif (Duty-based) untuk Guru & Tendik
        // Hanya dievaluasi jika modul ini belum pernah dimatikan secara eksplisit di database
        if (in_array($role, ['guru', 'tendik']) && Schema::hasTable('ptk_tugas_tambahan') && Schema::hasTable('ref_tugas_tambahan')) {
            $userId = is_array($user) ? ($user['id'] ?? ($user['pengguna_id'] ?? null)) : ($user->id ?? ($user->pengguna_id ?? null));
            $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

            if ($userId || $ptkId) {
                if (self::hasDutyPermission($userId, $ptkId, $permissionKey, $action)) {
                    return true;
                }
            }
        }

        // 3. Khusus Administrator: selalu diizinkan mengakses modul baru yang belum pernah dimatikan eksplisit di database
        if ($role === 'admin') {
            return true;
        }

        // 4. Fallback HANYA jika peran belum pernah dikonfigurasi sama sekali di database
        if (!self::where('role', $role)->exists()) {
            if (!self::isDefaultAllowed($role, $permissionKey)) {
                return false;
            }
            if ($actionCol === 'can_read') return true;
            if (in_array($role, ['guru', 'tendik']) && in_array($actionCol, ['can_create', 'can_update'])) return true;
            return false;
        }

        return false;
    }

    /**
     * Periksa apakah pengguna memiliki tugas tambahan aktif yang membuka modul tertentu
     */
    public static function hasDutyPermission(mixed $userId, ?string $ptkId, string $permissionKey, string $action = 'read'): bool
    {
        try {
            $query = DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('ptt.is_active', true)
                ->where('rtt.is_active', true);

            if ($userId && $ptkId) {
                $query->where(function ($q) use ($userId, $ptkId) {
                    $q->where('ptt.user_id', (string) $userId)->orWhere('ptt.ptk_id', $ptkId);
                });
            } elseif ($userId) {
                $query->where('ptt.user_id', (string) $userId);
            } elseif ($ptkId) {
                $query->where('ptt.ptk_id', $ptkId);
            } else {
                return false;
            }

            $action = strtolower(trim($action));

            // 1. Cek granted_permissions JSON dari tabel ref_tugas_tambahan jika tersedia
            $grantedJsonList = $query->pluck('rtt.granted_permissions');
            foreach ($grantedJsonList as $raw) {
                $perms = is_string($raw) ? json_decode($raw, true) : $raw;
                if (is_array($perms) && in_array($permissionKey, $perms, true)) {
                    return in_array($action, ['read', 'create', 'update']);
                }
            }

            // 2. Pemetaan bawaan berdasarkan kode tugas tambahan
            $duties = $query->select('rtt.kode', 'rtt.kelompok', 'rtt.bidang', 'ptt.rombel_id')->get();
            foreach ($duties as $d) {
                // Modul wali kelas
                if ($d->kode === 'WALI_KELAS') {
                    if (in_array($permissionKey, [
                        'menu_wali_kelas_aktif',
                        'menu_wali_kelas_tidak_aktif',
                        'menu_wali_kelas_presensi',
                        'menu_peserta_didik_aktif',
                        'menu_presensi_peserta_didik',
                        'menu_penilaian',
                        'menu_agenda_kbm',
                        'menu_berkas_peserta_didik',
                    ], true)) {
                        return in_array($action, ['read', 'create', 'update']);
                    }
                }

                // Modul kepala sekolah / waka kurikulum
                if (in_array($d->kode, ['KEPALA_SEKOLAH', 'WAKA_KURIKULUM'], true)) {
                    if (in_array($permissionKey, [
                        'menu_rombel',
                        'menu_pembelajaran',
                        'menu_jadwal_kbm',
                        'menu_kompetensi_keahlian',
                        'menu_presensi_mengajar',
                        'menu_agenda_kbm',
                        'menu_penilaian',
                    ], true)) {
                        return in_array($action, ['read', 'create', 'update']);
                    }
                }

                // Modul kesiswaan
                if (in_array($d->kode, ['WAKA_KESISWAAN', 'PEMBINA_OSIS'], true)) {
                    if (in_array($permissionKey, [
                        'menu_peserta_didik_aktif',
                        'menu_peserta_didik_tidak_aktif',
                        'menu_poin',
                        'menu_e_izin',
                    ], true)) {
                        return in_array($action, ['read', 'create', 'update']);
                    }
                }

                // Modul sarpras
                if (in_array($d->kode, ['WAKA_SARPRAS', 'KEPALA_LAB', 'KEPALA_BENGKEL'], true)) {
                    if ($permissionKey === 'menu_inventaris') {
                        return in_array($action, ['read', 'create', 'update']);
                    }
                }
            }

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Sinkronisasi modul & fitur ke tabel role_permissions.
     * Otomatis mendaftarkan modul baru ke database dan menjaga kustomisasi hak akses admin.
     */
    public static function syncAvailablePermissions(): int
    {
        if (!Schema::hasTable('role_permissions')) {
            return 0;
        }

        $allPermissions = self::getAvailablePermissions();
        $existingSystemKeys = self::distinct()->pluck('permission_key')->all();
        $isBrandNewDatabase = empty($existingSystemKeys);
        $addedCount = 0;
        $now = now();

        foreach ($allPermissions as $groupName => $items) {
            foreach ($items as $permKey => $config) {
                // Jika database baru kosong ATAU permKey ini modul baru yang belum pernah ada di database sama sekali
                $isNewModule = !in_array($permKey, $existingSystemKeys, true);
                if ($isBrandNewDatabase || $isNewModule) {
                    $roles = $config['roles'] ?? ['admin'];
                    foreach ($roles as $role) {
                        $isDef = self::isDefaultAllowed($role, $permKey);
                        self::updateOrCreate(
                            ['role' => $role, 'permission_key' => $permKey],
                            [
                                'is_allowed' => ($role === 'admin') ? true : $isDef,
                                'can_create' => ($role === 'admin') ? true : ($isDef && in_array($role, ['guru', 'tendik'])),
                                'can_read' => ($role === 'admin') ? true : $isDef,
                                'can_update' => ($role === 'admin') ? true : ($isDef && in_array($role, ['guru', 'tendik'])),
                                'can_delete' => ($role === 'admin') ? true : false,
                                'updated_at' => $now,
                            ]
                        );
                        $addedCount++;
                    }
                }
            }
        }

        return $addedCount;
    }

    /**
     * Daftar izin default bawaan sistem untuk tiap role jika belum dikonfigurasi
     */
    public static function isDefaultAllowed(string $role, string $permissionKey): bool
    {
        $defaults = [
            'admin' => [
                'menu_dashboard',
                'menu_dapodik',
                'menu_kompetensi_keahlian',
                'menu_rombel',
                'menu_pembelajaran',
                'menu_jadwal_kbm',
                'menu_kalender_pendidikan',
                'menu_peserta_didik_aktif',
                'menu_guru_aktif',
                'menu_tendik_aktif',
                'menu_berkas_peserta_didik',
                'menu_perubahan_data',
                'menu_peserta_didik_tidak_aktif',
                'menu_guru_tidak_aktif',
                'menu_tendik_tidak_aktif',
                'menu_formulir',
                'menu_pengumuman',
                'menu_rfid',
                'menu_e_izin',
                'menu_poin',
                'menu_kelulusan',
                'menu_presensi_mengajar',
                'menu_agenda_kbm',
                'menu_penilaian',
                'menu_presensi_peserta_didik',
                'menu_buku_tamu',
                'menu_inventaris',
                'menu_agenda',
                'menu_pengguna',
                'menu_hak_akses',
                'menu_pengaturan',
                'menu_maintenance',
                'menu_update',
            ],
            'guru' => [
                'menu_dashboard',
                'menu_presensi_mengajar',
                'menu_agenda_kbm',
                'menu_penilaian',
                'menu_presensi_peserta_didik',
                'menu_formulir',
                'menu_rfid',
                'menu_e_izin',
                'menu_pengumuman',
                'menu_peserta_didik_aktif',
                'menu_guru_aktif',
                'menu_rombel',
                'menu_pembelajaran',
                'menu_jadwal_kbm',
                'menu_kalender_pendidikan',
            ],
            'tendik' => [
                'menu_dashboard',
                'menu_tendik_aktif',
                'menu_guru_aktif',
                'menu_peserta_didik_aktif',
                'menu_formulir',
                'menu_rfid',
                'menu_e_izin',
                'menu_buku_tamu',
                'menu_inventaris',
                'menu_agenda',
                'menu_pengumuman',
                'menu_jadwal_kbm',
                'menu_kalender_pendidikan',
            ],
            'peserta_didik' => [
                'menu_dashboard',
                'menu_riwayat_rfid',
                'menu_formulir',
                'menu_e_izin',
                'menu_jadwal_pelajaran',
                'menu_jadwal_kbm',
                'menu_rapor',
                'menu_validasi_berkas',
                'menu_pengumuman',
            ],
        ];

        return in_array($permissionKey, $defaults[$role] ?? [], true);
    }

    /**
     * Cek izin aksi CRUD spesifik (create, read, update, delete) untuk menu tertentu
     */
    public static function can(mixed $userOrRole, string $permissionKey, string $action): bool
    {
        return self::canAccess($userOrRole, $permissionKey, $action);
    }
}
