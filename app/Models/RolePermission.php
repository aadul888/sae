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
     * In-memory static cache per HTTP request untuk optimasi performa RBAC
     */
    protected static array $runtimeRolePermissionsCache = [];
    protected static array $runtimeDutyPermissionsCache = [];
    protected static ?bool $runtimeAdminExistsCache = null;
    protected static ?bool $runtimeHasTableRolePermissions = null;
    protected static ?bool $runtimeHasTableDuties = null;

    /**
     * Bersihkan static runtime cache (berguna saat pengujian atau setelah mutasi hak akses)
     */
    public static function clearRuntimeCache(): void
    {
        self::$runtimeRolePermissionsCache = [];
        self::$runtimeDutyPermissionsCache = [];
        self::$runtimeAdminExistsCache = null;
        self::$runtimeHasTableRolePermissions = null;
        self::$runtimeHasTableDuties = null;
    }

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

            'Tendik: Kepala TAS' => [
                'menu_kepala_tas' => [
                    'label' => 'Dashboard & Manajemen Kepala TAS',
                    'icon' => 'fa-user-tie',
                    'roles' => ['admin', 'tendik'],
                ],
            ],

            'Tendik: Persuratan' => [
                'menu_persuratan' => [
                    'label' => 'Dashboard & Modul Persuratan',
                    'icon' => 'fa-envelope-open-text',
                    'roles' => ['admin', 'tendik'],
                ],
                'menu_surat_masuk' => [
                    'label' => 'Buku Agenda Surat Masuk',
                    'icon' => 'fa-inbox',
                    'roles' => ['admin', 'tendik'],
                ],
                'menu_surat_keluar' => [
                    'label' => 'Buku Agenda Surat Keluar',
                    'icon' => 'fa-paper-plane',
                    'roles' => ['admin', 'tendik'],
                ],
                'menu_pengaturan_persuratan' => [
                    'label' => 'Pengaturan & Arsip HDD Persuratan',
                    'icon' => 'fa-sliders',
                    'roles' => ['admin', 'tendik'],
                ],
            ],

            'Tendik: Kesiswaan' => [
                'menu_kesiswaan' => [
                    'label' => 'Administrasi Kesiswaan Terpadu',
                    'icon' => 'fa-user-graduate',
                    'roles' => ['admin', 'tendik'],
                ],
                'menu_kesiswaan_peserta_didik' => [
                    'label' => 'Kesiswaan: Data Peserta Didik',
                    'icon' => 'fa-users',
                    'roles' => ['admin', 'tendik'],
                ],
                'menu_kesiswaan_administrasi' => [
                    'label' => 'Kesiswaan: Administrasi (Klaper, Mutasi, Kelulusan)',
                    'icon' => 'fa-book-bookmark',
                    'roles' => ['admin', 'tendik'],
                ],
                'menu_kesiswaan_kedisiplinan' => [
                    'label' => 'Kesiswaan: Kedisiplinan & Tata Tertib',
                    'icon' => 'fa-shield-halved',
                    'roles' => ['admin', 'tendik'],
                ],
                'menu_kesiswaan_kegiatan' => [
                    'label' => 'Kesiswaan: Kegiatan Siswa & OSIS/Ekskul',
                    'icon' => 'fa-people-group',
                    'roles' => ['admin', 'tendik'],
                ],
                'menu_kesiswaan_prestasi' => [
                    'label' => 'Kesiswaan: Prestasi Peserta Didik',
                    'icon' => 'fa-trophy',
                    'roles' => ['admin', 'tendik'],
                ],
            ],

            'Tendik: Kepegawaian' => [
                'menu_kepegawaian' => [
                    'label' => 'Dashboard Kepegawaian GTK',
                    'icon' => 'fa-gauge-high',
                    'roles' => ['admin', 'tendik'],
                ],
                'menu_kepegawaian_guru' => [
                    'label' => 'Kepegawaian: Pegawai Guru',
                    'icon' => 'fa-chalkboard-user',
                    'roles' => ['admin', 'tendik'],
                ],
                'menu_kepegawaian_tendik' => [
                    'label' => 'Kepegawaian: Pegawai Tendik',
                    'icon' => 'fa-id-badge',
                    'roles' => ['admin', 'tendik'],
                ],
                'menu_kepegawaian_kgb' => [
                    'label' => 'Kepegawaian: KGB Tracker',
                    'icon' => 'fa-business-time',
                    'roles' => ['admin', 'tendik'],
                ],
                'menu_kepegawaian_cuti' => [
                    'label' => 'Kepegawaian: Cuti & Izin',
                    'icon' => 'fa-plane-departure',
                    'roles' => ['admin', 'tendik'],
                ],
            ],

            'Tendik: Keuangan' => [
                'menu_keuangan' => [
                    'label' => 'Administrasi Keuangan & Komite',
                    'icon' => 'fa-wallet',
                    'roles' => ['admin', 'tendik'],
                ],
            ],

            'Tendik: Sarpras & Aset' => [
                'menu_sarpras' => [
                    'label' => 'Sarana & Prasarana Sekolah',
                    'icon' => 'fa-building',
                    'roles' => ['admin', 'tendik'],
                ],
                'menu_inventaris' => [
                    'label' => 'Inventaris Sarpras',
                    'icon' => 'fa-boxes-stacked',
                    'roles' => ['admin', 'tendik'],
                ],
            ],

            'Tendik: Laboratorium' => [
                'menu_laboran' => [
                    'label' => 'Laboratorium & Praktik',
                    'icon' => 'fa-flask',
                    'roles' => ['admin', 'tendik'],
                ],
            ],

            'Tendik: Perpustakaan' => [
                'menu_perpustakaan' => [
                    'label' => 'Perpustakaan & Buku Digital',
                    'icon' => 'fa-book-open',
                    'roles' => ['admin', 'tendik'],
                ],
            ],

            'Tendik: Teknisi IT' => [
                'menu_teknisi' => [
                    'label' => 'Teknisi IT & Infrastruktur',
                    'icon' => 'fa-network-wired',
                    'roles' => ['admin', 'tendik'],
                ],
            ],

            'Tendik: Keamanan & Tamu' => [
                'menu_keamanan' => [
                    'label' => 'Keamanan & Pos Satpam',
                    'icon' => 'fa-shield-halved',
                    'roles' => ['admin', 'tendik'],
                ],
            ],

            'Tendik: Fasilitas & Penjaga' => [
                'menu_penjaga' => [
                    'label' => 'Fasilitas & Penjaga Sekolah',
                    'icon' => 'fa-broom',
                    'roles' => ['admin', 'tendik'],
                ],
            ],

            'Tendik: Piket Sekolah' => [
                'menu_piket' => [
                    'label' => 'Petugas / Guru Piket',
                    'icon' => 'fa-clipboard-user',
                    'roles' => ['admin', 'guru', 'tendik'],
                ],
            ],

            'Tendik: Kinerja & Aktivitas' => [
                'menu_aktivitas_tendik' => [
                    'label' => 'Aktivitas Harian Tendik',
                    'icon' => 'fa-list-check',
                    'roles' => ['admin', 'tendik'],
                ],
                'menu_laporan_tendik' => [
                    'label' => 'Laporan Kinerja Tendik',
                    'icon' => 'fa-file-signature',
                    'roles' => ['admin', 'tendik'],
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

        // 1. Kumpulkan seluruh berkas rute, controller, dan view secara rekursif
        $dirsToScan = [
            base_path('routes'),
            app_path('Http/Controllers'),
            resource_path('views/partials'),
            resource_path('views/dashboard'),
        ];
        $filesToScan = [];
        foreach ($dirsToScan as $dir) {
            if (!is_dir($dir)) continue;
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if ($file->isFile() && in_array(strtolower($file->getExtension()), ['php'])) {
                    $filesToScan[] = $file->getPathname();
                }
            }
        }

        foreach ($filesToScan as $filePath) {
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
     * Cek apakah suatu modul diizinkan untuk peran tertentu di tabel role_permissions (tanpa tugas tambahan)
     */
    public static function canRoleAccess(string $role, string $permissionKey, string $action = 'read'): bool
    {
        if ($role === 'admin') return true;
        if (self::$runtimeHasTableRolePermissions === null) {
            self::$runtimeHasTableRolePermissions = Schema::hasTable('role_permissions');
        }
        if (!self::$runtimeHasTableRolePermissions) return false;

        if (!isset(self::$runtimeRolePermissionsCache[$role])) {
            self::$runtimeRolePermissionsCache[$role] = self::where('role', $role)->get()->keyBy('permission_key');
        }
        $row = self::$runtimeRolePermissionsCache[$role]->get($permissionKey);
        if (!$row && in_array($permissionKey, ['menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan'], true)) {
            $row = self::$runtimeRolePermissionsCache[$role]->get('menu_persuratan');
        }
        if (!$row && in_array($permissionKey, ['menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi'], true)) {
            $row = self::$runtimeRolePermissionsCache[$role]->get('menu_kesiswaan');
        }
        if (!$row && $permissionKey === 'menu_kesiswaan') {
            foreach (['menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi'] as $sk) {
                $sr = self::$runtimeRolePermissionsCache[$role]->get($sk);
                if ($sr && $sr->is_allowed && $sr->can_read) {
                    $row = $sr;
                    break;
                }
            }
        }
        if (!$row && $permissionKey === 'menu_persuratan') {
            foreach (['menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan'] as $sk) {
                $sr = self::$runtimeRolePermissionsCache[$role]->get($sk);
                if ($sr && $sr->is_allowed && $sr->can_read) {
                    $row = $sr;
                    break;
                }
            }
        }
        if (!$row || !$row->is_allowed || !$row->can_read) {
            return false;
        }

        $action = strtolower(trim($action));
        $actionCol = 'can_' . $action;
        if (!in_array($actionCol, ['can_create', 'can_read', 'can_update', 'can_delete'])) {
            $actionCol = 'can_read';
        }

        if ($actionCol === 'can_read') {
            return (bool) $row->can_read;
        }

        return (bool) ($row->can_read && $row->{$actionCol});
    }

    /**
     * Cek apakah permission key merupakan modul spesifik penugasan tugas tambahan
     */
    public static function isDutySpecificPermission(string $key): bool
    {
        return in_array($key, [
            'menu_kepala_tas',
            'menu_persuratan',
            'menu_surat_masuk',
            'menu_surat_keluar',
            'menu_pengaturan_persuratan',
            'menu_kesiswaan',
            'menu_kesiswaan_peserta_didik',
            'menu_kesiswaan_administrasi',
            'menu_kesiswaan_kedisiplinan',
            'menu_kesiswaan_kegiatan',
            'menu_kesiswaan_prestasi',
            'menu_kepegawaian',
            'menu_sarpras',
            'menu_laboran',
            'menu_perpustakaan',
            'menu_teknisi',
            'menu_keamanan',
            'menu_penjaga',
            'menu_piket',
            'menu_wali_kelas_aktif',
            'menu_wali_kelas_tidak_aktif',
            'menu_wali_kelas_presensi',
        ], true);
    }

    /**
     * Cek apakah user atau role memiliki izin terhadap suatu permission_key
     */
    public static function canAccess($userOrRole, string $permissionKey, string $action = 'read'): bool
    {
        // Jika tabel belum ada (sebelum migrasi selesai), fallback allow default admin
        if (self::$runtimeHasTableRolePermissions === null) {
            self::$runtimeHasTableRolePermissions = Schema::hasTable('role_permissions');
        }
        if (!self::$runtimeHasTableRolePermissions) {
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

        // Khusus Administrator:
        if ($role === 'admin') {
            if (in_array($permissionKey, ['menu_dashboard', 'menu_hak_akses'])) {
                return true;
            }
            if (self::$runtimeAdminExistsCache === null) {
                self::$runtimeAdminExistsCache = self::where('role', 'admin')->exists();
            }
            if (self::$runtimeAdminExistsCache) {
                if (!isset(self::$runtimeRolePermissionsCache['admin'])) {
                    self::$runtimeRolePermissionsCache['admin'] = self::where('role', 'admin')->get()->keyBy('permission_key');
                }
                $adminRow = self::$runtimeRolePermissionsCache['admin']->get($permissionKey);
                if (!$adminRow && in_array($permissionKey, ['menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan'], true)) {
                    $adminRow = self::$runtimeRolePermissionsCache['admin']->get('menu_persuratan');
                }
                if (!$adminRow && in_array($permissionKey, ['menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi'], true)) {
                    $adminRow = self::$runtimeRolePermissionsCache['admin']->get('menu_kesiswaan');
                }
                if (!$adminRow && $permissionKey === 'menu_kesiswaan') {
                    foreach (['menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi'] as $sk) {
                        $sr = self::$runtimeRolePermissionsCache['admin']->get($sk);
                        if ($sr && $sr->is_allowed && $sr->can_read) {
                            $adminRow = $sr;
                            break;
                        }
                    }
                }
                if (!$adminRow && $permissionKey === 'menu_persuratan') {
                    foreach (['menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan'] as $sk) {
                        $sr = self::$runtimeRolePermissionsCache['admin']->get($sk);
                        if ($sr && $sr->is_allowed && $sr->can_read) {
                            $adminRow = $sr;
                            break;
                        }
                    }
                }
                if ($adminRow && $adminRow->is_allowed && $adminRow->can_read) {
                    return $actionCol === 'can_read' ? true : (bool) $adminRow->{$actionCol};
                }
                return false;
            }
            return true;
        }

        // Cache keberadaan tabel tugas tambahan
        if (self::$runtimeHasTableDuties === null) {
            self::$runtimeHasTableDuties = Schema::hasTable('ptk_tugas_tambahan') && Schema::hasTable('ref_tugas_tambahan');
        }
        $hasDutyTables = self::$runtimeHasTableDuties;

        // Khusus Peserta Didik yang ditunjuk sebagai Koordinator Kelas (Membantu tugas Wali Kelas)
        if ($role === 'peserta_didik' && self::isKoordinator($user)) {
            if (in_array($permissionKey, [
                'menu_wali_kelas_aktif',
                'menu_wali_kelas_presensi',
            ], true)) {
                return in_array($actionCol, ['can_read', 'can_create', 'can_update']);
            }
        }

        // 1. Periksa izin peran di database (role_permissions)
        if (!isset(self::$runtimeRolePermissionsCache[$role])) {
            self::$runtimeRolePermissionsCache[$role] = self::where('role', $role)->get()->keyBy('permission_key');
        }
        $row = self::$runtimeRolePermissionsCache[$role]->get($permissionKey);
        if (!$row && in_array($permissionKey, ['menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan'], true)) {
            $row = self::$runtimeRolePermissionsCache[$role]->get('menu_persuratan');
        }
        if (!$row && in_array($permissionKey, ['menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi'], true)) {
            $row = self::$runtimeRolePermissionsCache[$role]->get('menu_kesiswaan');
        }
        if (!$row && $permissionKey === 'menu_kesiswaan') {
            foreach (['menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi'] as $sk) {
                $sr = self::$runtimeRolePermissionsCache[$role]->get($sk);
                if ($sr && $sr->is_allowed && $sr->can_read) {
                    $row = $sr;
                    break;
                }
            }
        }
        if (!$row && $permissionKey === 'menu_persuratan') {
            foreach (['menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan'] as $sk) {
                $sr = self::$runtimeRolePermissionsCache[$role]->get($sk);
                if ($sr && $sr->is_allowed && $sr->can_read) {
                    $row = $sr;
                    break;
                }
            }
        }

        if ($row) {
            if (!$row->is_allowed || !$row->can_read) {
                return false;
            }

            // Jika modul spesifik tugas tambahan (misal laboran, persuratan, dsb.),
            // hanya personel yang memegang tugas tambahan tersebut yang boleh mengakses
            if (in_array($role, ['guru', 'tendik']) && $hasDutyTables && self::isDutySpecificPermission($permissionKey)) {
                $userId = is_array($user) ? ($user['id'] ?? ($user['pengguna_id'] ?? null)) : ($user->id ?? ($user->pengguna_id ?? null));
                $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
                if (!self::hasDutyPermission($userId, $ptkId, $permissionKey, $action)) {
                    return false;
                }
            }

            if ($actionCol === 'can_read') {
                return (bool) $row->can_read;
            }

            return (bool) ($row->can_read && $row->{$actionCol});
        }

        // 2. Jika modul TIDAK ADA di role_permissions untuk peran user:
        // Cek kasus khusus: Guru yang memegang tugas Tendik (misal Kepala TAS / Kepala Lab)
        if ($role === 'guru' && $hasDutyTables && self::isDutySpecificPermission($permissionKey)) {
            $userId = is_array($user) ? ($user['id'] ?? ($user['pengguna_id'] ?? null)) : ($user->id ?? ($user->pengguna_id ?? null));
            $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
            if (self::canRoleAccess('tendik', $permissionKey, $action) && self::hasDutyPermission($userId, $ptkId, $permissionKey, $action)) {
                return true;
            }
        }

        // JIKA MODUL BELUM DITAMBAHKAN OLEH ADMIN KE PERAN INI, MAKA AKSES DITOLAK!
        return false;
    }

    /**
     * Periksa apakah pengguna memiliki tugas tambahan aktif yang membuka modul tertentu
     */
    public static function hasDutyPermission(mixed $userId, ?string $ptkId, string $permissionKey, string $action = 'read'): bool
    {
        try {
            $cacheKey = ($userId ?: 'null') . '|' . ($ptkId ?: 'null');

            if (!isset(self::$runtimeDutyPermissionsCache[$cacheKey])) {
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
                    self::$runtimeDutyPermissionsCache[$cacheKey] = [];
                    return false;
                }

                $allowedKeys = [];

                // 1. Cek granted_permissions JSON dari tabel ref_tugas_tambahan
                $records = $query->select('rtt.kode', 'rtt.kelompok', 'rtt.bidang', 'rtt.granted_permissions', 'ptt.rombel_id')->get();
                foreach ($records as $rec) {
                    $raw = $rec->granted_permissions;
                    $perms = is_string($raw) ? json_decode($raw, true) : $raw;
                    if (is_array($perms)) {
                        foreach ($perms as $p) {
                            $allowedKeys[$p] = true;
                        }
                    }

                    // 2. Pemetaan bawaan berdasarkan kode tugas tambahan
                    $kode = $rec->kode;
                    if ($kode === 'WALI_KELAS') {
                        foreach (['menu_wali_kelas_aktif', 'menu_wali_kelas_tidak_aktif', 'menu_wali_kelas_presensi', 'menu_peserta_didik_aktif', 'menu_presensi_peserta_didik', 'menu_agenda_kbm', 'menu_berkas_peserta_didik'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif (in_array($kode, ['KEPALA_SEKOLAH', 'WAKA_KURIKULUM'], true)) {
                        foreach (['menu_rombel', 'menu_pembelajaran', 'menu_jadwal_kbm', 'menu_kompetensi_keahlian', 'menu_presensi_mengajar', 'menu_agenda_kbm'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'WAKA_KESISWAAN') {
                        foreach (['menu_kesiswaan', 'menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi', 'menu_peserta_didik_aktif', 'menu_peserta_didik_tidak_aktif', 'menu_e_izin'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'PEMBINA_OSIS') {
                        foreach (['menu_kesiswaan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi', 'menu_pengumuman', 'menu_kesiswaan_kedisiplinan'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'PEMBINA_EKSKUL') {
                        foreach (['menu_kesiswaan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi', 'menu_pengumuman'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif (in_array($kode, ['WAKA_SARPRAS', 'KEPALA_LAB', 'KEPALA_BENGKEL'], true)) {
                        foreach (['menu_sarpras', 'menu_inventaris'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif (in_array($kode, ['KEPALA_PERPUSTAKAAN', 'PUSTAKAWAN'], true)) {
                        foreach (['menu_perpustakaan'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'LABORAN') {
                        foreach (['menu_laboran'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif (in_array($kode, ['TEKNISI_IT', 'TEKNISI_GEDUNG', 'TEKNISI_LAPANGAN'], true)) {
                        foreach (['menu_teknisi'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'PETUGAS_KEAMANAN' || $kode === 'SATPAM') {
                        foreach (['menu_keamanan'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif (in_array($kode, ['PENJAGA_SEKOLAH', 'PESURUH'], true)) {
                        foreach (['menu_penjaga'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'GURU_PIKET') {
                        foreach (['menu_piket', 'menu_presensi_mengajar', 'menu_agenda_kbm', 'menu_e_izin', 'menu_riwayat_rfid'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'OPERATOR_DAPODIK') {
                        foreach (['menu_dapodik', 'menu_peserta_didik_aktif', 'menu_guru_aktif', 'menu_tendik_aktif', 'menu_rombel', 'menu_pembelajaran'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'KEPALA_TAS') {
                        foreach (['menu_kepala_tas', 'menu_persuratan', 'menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan', 'menu_kesiswaan', 'menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi', 'menu_kepegawaian', 'menu_keuangan', 'menu_sarpras', 'menu_aktivitas_tendik', 'menu_laporan_tendik', 'menu_laboran', 'menu_perpustakaan', 'menu_teknisi', 'menu_keamanan', 'menu_penjaga', 'menu_piket', 'menu_inventaris'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'STAF_PERSURATAN') {
                        foreach (['menu_persuratan', 'menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'STAF_KEPEGAWAIAN') {
                        foreach (['menu_kepegawaian', 'menu_tendik_aktif', 'menu_guru_aktif', 'menu_guru_tidak_aktif', 'menu_tendik_tidak_aktif', 'menu_rfid'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'STAF_KESISWAAN') {
                        foreach (['menu_kesiswaan', 'menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi', 'menu_peserta_didik_aktif', 'menu_peserta_didik_tidak_aktif', 'menu_berkas_peserta_didik', 'menu_perubahan_data'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'STAF_SARPRAS') {
                        foreach (['menu_sarpras', 'menu_inventaris', 'menu_rombel'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    }

                    // Tendik umum: Aktivitas & Laporan Kinerja
                    if (in_array($kode, [
                        'KEPALA_TAS', 'STAF_PERSURATAN', 'STAF_KESISWAAN', 'STAF_KEPEGAWAIAN',
                        'STAF_SARPRAS', 'LABORAN', 'PUSTAKAWAN', 'TEKNISI_IT', 'SATPAM', 'PENJAGA_SEKOLAH'
                    ], true)) {
                        $allowedKeys['menu_aktivitas_tendik'] = true;
                        $allowedKeys['menu_laporan_tendik'] = true;
                    }
                }

                // Wariskan izin menu_persuratan ke sub-modul persuratan jika belum terdefinisi secara terpisah
                if (!empty($allowedKeys['menu_persuratan'])) {
                    if (!isset($allowedKeys['menu_surat_masuk'])) $allowedKeys['menu_surat_masuk'] = true;
                    if (!isset($allowedKeys['menu_surat_keluar'])) $allowedKeys['menu_surat_keluar'] = true;
                    if (!isset($allowedKeys['menu_pengaturan_persuratan'])) $allowedKeys['menu_pengaturan_persuratan'] = true;
                }

                // Wariskan izin menu_kesiswaan ke sub-modul kesiswaan jika belum terdefinisi secara terpisah
                if (!empty($allowedKeys['menu_kesiswaan'])) {
                    if (!isset($allowedKeys['menu_kesiswaan_peserta_didik'])) $allowedKeys['menu_kesiswaan_peserta_didik'] = true;
                    if (!isset($allowedKeys['menu_kesiswaan_administrasi'])) $allowedKeys['menu_kesiswaan_administrasi'] = true;
                    if (!isset($allowedKeys['menu_kesiswaan_kedisiplinan'])) $allowedKeys['menu_kesiswaan_kedisiplinan'] = true;
                    if (!isset($allowedKeys['menu_kesiswaan_kegiatan'])) $allowedKeys['menu_kesiswaan_kegiatan'] = true;
                    if (!isset($allowedKeys['menu_kesiswaan_prestasi'])) $allowedKeys['menu_kesiswaan_prestasi'] = true;
                }

                self::$runtimeDutyPermissionsCache[$cacheKey] = $allowedKeys;
            }

            $action = strtolower(trim($action));
            if (isset(self::$runtimeDutyPermissionsCache[$cacheKey][$permissionKey])) {
                return in_array($action, ['read', 'create', 'update', 'delete']);
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
        $addedCount = 0;
        $now = now();

        // Kumpulkan permission_key yang sudah ada per role
        $existingByRole = self::select('role', 'permission_key')
            ->get()
            ->groupBy('role')
            ->map(function ($items) {
                return $items->pluck('permission_key')->flip()->toArray();
            })
            ->toArray();

        foreach ($allPermissions as $groupName => $items) {
            foreach ($items as $permKey => $config) {
                $targetRoles = array_unique(array_merge(['admin'], $config['roles'] ?? []));

                foreach ($targetRoles as $role) {
                    if (!isset($existingByRole[$role][$permKey])) {
                        $isDef = self::isDefaultAllowed($role, $permKey);
                        self::create([
                            'role' => $role,
                            'permission_key' => $permKey,
                            'is_allowed' => ($role === 'admin') ? true : $isDef,
                            'can_create' => ($role === 'admin') ? true : ($isDef && in_array($role, ['guru', 'tendik'])),
                            'can_read' => ($role === 'admin') ? true : $isDef,
                            'can_update' => ($role === 'admin') ? true : ($isDef && in_array($role, ['guru', 'tendik'])),
                            'can_delete' => ($role === 'admin') ? true : false,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                        $existingByRole[$role][$permKey] = true;
                        $addedCount++;
                    }
                }
            }
        }

        // Sinkronkan juga tugas tambahan otomatis jika tabelnya ada
        if (Schema::hasTable('ref_tugas_tambahan')) {
            $kesiswaanAll = [
                'menu_kesiswaan',
                'menu_kesiswaan_peserta_didik',
                'menu_kesiswaan_administrasi',
                'menu_kesiswaan_kedisiplinan',
                'menu_kesiswaan_kegiatan',
                'menu_kesiswaan_prestasi',
            ];

            foreach (['WAKA_KESISWAAN', 'STAF_KESISWAAN', 'KEPALA_TAS'] as $kode) {
                $ref = \App\Models\RefTugasTambahan::where('kode', $kode)->first();
                if ($ref) {
                    $current = is_array($ref->granted_permissions) ? $ref->granted_permissions : (json_decode($ref->granted_permissions, true) ?: []);
                    $merged = array_values(array_unique(array_merge($current, $kesiswaanAll)));
                    if (count($merged) !== count($current)) {
                        $ref->update(['granted_permissions' => $merged]);
                    }
                }
            }

            foreach (['PEMBINA_OSIS', 'PEMBINA_EKSKUL'] as $kode) {
                $ref = \App\Models\RefTugasTambahan::where('kode', $kode)->first();
                if ($ref) {
                    $current = is_array($ref->granted_permissions) ? $ref->granted_permissions : (json_decode($ref->granted_permissions, true) ?: []);
                    $merged = array_values(array_unique(array_merge($current, ['menu_kesiswaan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi'])));
                    if (count($merged) !== count($current)) {
                        $ref->update(['granted_permissions' => $merged]);
                    }
                }
            }

            $persuratanAll = ['menu_persuratan', 'menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan'];
            foreach (['STAF_PERSURATAN', 'KEPALA_TAS'] as $kode) {
                $ref = \App\Models\RefTugasTambahan::where('kode', $kode)->first();
                if ($ref) {
                    $current = is_array($ref->granted_permissions) ? $ref->granted_permissions : (json_decode($ref->granted_permissions, true) ?: []);
                    $merged = array_values(array_unique(array_merge($current, $persuratanAll)));
                    if (count($merged) !== count($current)) {
                        $ref->update(['granted_permissions' => $merged]);
                    }
                }
            }
        }

        self::clearRuntimeCache();

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
                'menu_presensi_mengajar',
                'menu_agenda_kbm',
                'menu_presensi_peserta_didik',
                'menu_kepala_tas',
                'menu_aktivitas_tendik',
                'menu_laporan_tendik',
                'menu_persuratan',
                'menu_surat_masuk',
                'menu_surat_keluar',
                'menu_pengaturan_persuratan',
                'menu_kesiswaan',
                'menu_kepegawaian',
                'menu_keuangan',
                'menu_sarpras',
                'menu_laboran',
                'menu_perpustakaan',
                'menu_teknisi',
                'menu_keamanan',
                'menu_penjaga',
                'menu_piket',
                'menu_inventaris',
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
                'menu_kepala_tas',
                'menu_aktivitas_tendik',
                'menu_laporan_tendik',
                'menu_tendik_aktif',
                'menu_guru_aktif',
                'menu_peserta_didik_aktif',
                'menu_formulir',
                'menu_rfid',
                'menu_e_izin',
                'menu_persuratan',
                'menu_surat_masuk',
                'menu_surat_keluar',
                'menu_pengaturan_persuratan',
                'menu_kesiswaan',
                'menu_kesiswaan_peserta_didik',
                'menu_kesiswaan_administrasi',
                'menu_kesiswaan_kedisiplinan',
                'menu_kesiswaan_kegiatan',
                'menu_kesiswaan_prestasi',
                'menu_kepegawaian',
                'menu_keuangan',
                'menu_sarpras',
                'menu_laboran',
                'menu_perpustakaan',
                'menu_teknisi',
                'menu_keamanan',
                'menu_penjaga',
                'menu_piket',
                'menu_inventaris',
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
