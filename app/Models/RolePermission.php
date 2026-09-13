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
                'menu_presensi_peserta_didik' => [
                    'label' => 'Presensi Kelas',
                    'icon' => 'fa-users-viewfinder',
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
                    'label' => 'Agenda Sekolah',
                    'icon' => 'fa-calendar-days',
                    'roles' => ['admin', 'tendik'],
                ],
            ],

            'Portal Peserta Didik' => [
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
     * Otomatis mendeteksi modul menu baru yang dibuat di sistem
     * dengan memindai berkas partials/dash-sidebar.blade.php dan routes/web.php
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

        $filesToScan = [
            resource_path('views/partials/dash-sidebar.blade.php'),
            base_path('routes/web.php'),
        ];

        foreach ($filesToScan as $filePath) {
            if (!file_exists($filePath)) continue;
            $content = file_get_contents($filePath);
            if (!$content) continue;

            preg_match_all('/can\(\s*[\'"](menu_[a-zA-Z0-9_]+)[\'"]\s*\)/', $content, $m1);
            preg_match_all('/permission:(menu_[a-zA-Z0-9_]+)/', $content, $m2);

            $foundKeys = array_unique(array_merge($m1[1] ?? [], $m2[1] ?? []));
            foreach ($foundKeys as $key) {
                if (!isset($knownKeys[$key]) && !isset($discovered[$key])) {
                    $cleanName = str_replace(['menu_', '_'], ['', ' '], $key);
                    $label = ucwords($cleanName);

                    $group = 'Modul Sistem Baru';
                    $icon = 'fa-cube';
                    if (str_contains($key, 'guru')) {
                        $group = 'Administrasi Guru';
                        $icon = 'fa-chalkboard-user';
                    } elseif (str_contains($key, 'tendik')) {
                        $group = 'Administrasi Tendik';
                        $icon = 'fa-id-badge';
                    } elseif (str_contains($key, 'peserta_didik') || str_contains($key, 'siswa')) {
                        $group = 'Portal Peserta Didik';
                        $icon = 'fa-user-graduate';
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
     * Dapatkan definisi konfigurasi untuk permission tertentu
     */
    public static function getPermissionConfig(string $key): ?array
    {
        $all = self::getAvailablePermissions();
        foreach ($all as $items) {
            if (isset($items[$key])) {
                return $items[$key];
            }
        }
        return null;
    }

    /**
     * Cek apakah pengguna saat ini adalah Wali Kelas atau Administrator
     */
    public static function isWaliKelasOrAdmin(mixed $userOrRole = null, ?string $rombelNameOrId = null): bool
    {
        $user = $userOrRole ?: session('user');
        if (!$user) return false;

        $role = is_string($user) ? $user : ($user['role'] ?? ($user->role ?? ''));
        if ($role === 'admin') {
            return true;
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
     * Dapatkan nama rombel yang diampu oleh Wali Kelas saat ini
     */
    public static function getWaliKelasRombel(mixed $userOrRole = null): ?string
    {
        $user = $userOrRole ?: session('user');
        if (!$user) return null;
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
     * Cek apakah role / user tertentu diizinkan mengakses permission tertentu.
     * Mendukung evaluasi gabungan (Role Dasar + Izin Tugas Tambahan Aktif).
     */
    public static function canAccess(mixed $userOrRole, string $permissionKey): bool
    {
        // Jika tabel belum ada (sebelum migrasi selesai), fallback allow default admin
        if (!Schema::hasTable('role_permissions')) {
            return $userOrRole === 'admin' || (is_object($userOrRole) && ($userOrRole->role ?? '') === 'admin');
        }

        $role = is_string($userOrRole) ? $userOrRole : ($userOrRole['role'] ?? ($userOrRole->role ?? 'peserta_didik'));

        // 1. Otoritas Utama: Izin peran tersimpan di database
        $row = self::where('role', $role)->where('permission_key', $permissionKey)->first();
        if ($row) {
            return (bool) ($row->is_allowed && $row->can_read);
        }

        // 2. Evaluasi izin dari tugas tambahan aktif (Duty-based) untuk Guru & Tendik
        if (in_array($role, ['guru', 'tendik']) && Schema::hasTable('ptk_tugas_tambahan') && Schema::hasTable('ref_tugas_tambahan')) {
            $userId = is_array($userOrRole) ? ($userOrRole['id'] ?? ($userOrRole['pengguna_id'] ?? null)) : ($userOrRole->id ?? ($userOrRole->pengguna_id ?? null));
            $ptkId = is_array($userOrRole) ? ($userOrRole['ptk_id'] ?? null) : ($userOrRole->ptk_id ?? null);

            if ($userId || $ptkId) {
                if (self::hasDutyPermission($userId, $ptkId, $permissionKey)) {
                    return true;
                }
            }
        }

        // 3. Fallback HANYA jika peran belum pernah dikonfigurasi sama sekali di database
        if (!self::where('role', $role)->exists()) {
            return self::isDefaultAllowed($role, $permissionKey);
        }

        return false;
    }

    /**
     * Periksa apakah pengguna memiliki tugas tambahan aktif yang membuka modul tertentu
     */
    public static function hasDutyPermission(mixed $userId, ?string $ptkId, string $permissionKey): bool
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

            // 1. Cek granted_permissions JSON dari tabel ref_tugas_tambahan jika tersedia
            $grantedJsonList = $query->pluck('rtt.granted_permissions');
            foreach ($grantedJsonList as $raw) {
                $perms = is_string($raw) ? json_decode($raw, true) : $raw;
                if (is_array($perms) && in_array($permissionKey, $perms, true)) {
                    return true;
                }
            }

            // 2. Pemetaan bawaan berdasarkan kode tugas tambahan
            $duties = $query->select('rtt.kode', 'rtt.kelompok', 'rtt.bidang', 'ptt.rombel_id')->get();
            foreach ($duties as $d) {
                // Modul wali kelas
                if ($d->kode === 'WALI_KELAS') {
                    if (in_array($permissionKey, [
                        'menu_peserta_didik_aktif',
                        'menu_presensi_peserta_didik',
                        'menu_penilaian',
                        'menu_agenda_kbm',
                        'menu_berkas_peserta_didik',
                    ], true)) {
                        return true;
                    }
                }

                // Modul kepala sekolah / waka kurikulum
                if (in_array($d->kode, ['KEPALA_SEKOLAH', 'WAKA_KURIKULUM'], true)) {
                    if (in_array($permissionKey, [
                        'menu_rombel',
                        'menu_pembelajaran',
                        'menu_kompetensi_keahlian',
                        'menu_presensi_mengajar',
                        'menu_agenda_kbm',
                        'menu_penilaian',
                    ], true)) {
                        return true;
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
                        return true;
                    }
                }

                // Modul sarpras
                if (in_array($d->kode, ['WAKA_SARPRAS', 'KEPALA_LAB', 'KEPALA_BENGKEL'], true)) {
                    if ($permissionKey === 'menu_inventaris') {
                        return true;
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
                'menu_peserta_didik_aktif',
                'menu_guru_aktif',
                'menu_tendik_aktif',
                'menu_berkas_peserta_didik',
                'menu_perubahan_data',
                'menu_peserta_didik_tidak_aktif',
                'menu_guru_tidak_aktif',
                'menu_tendik_tidak_aktif',
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
                'menu_pengumuman',
                'menu_peserta_didik_aktif',
                'menu_guru_aktif',
                'menu_rombel',
                'menu_pembelajaran',
            ],
            'tendik' => [
                'menu_dashboard',
                'menu_tendik_aktif',
                'menu_guru_aktif',
                'menu_peserta_didik_aktif',
                'menu_buku_tamu',
                'menu_inventaris',
                'menu_agenda',
                'menu_pengumuman',
            ],
            'peserta_didik' => [
                'menu_dashboard',
                'menu_riwayat_rfid',
                'menu_jadwal_pelajaran',
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
        $user = $userOrRole ?: session('user');
        if (!$user) return false;
        $role = is_string($user) ? $user : ($user['role'] ?? ($user->role ?? 'peserta_didik'));

        if (!Schema::hasTable('role_permissions')) {
            return $role === 'admin';
        }

        $actionCol = 'can_' . strtolower($action);
        if (!in_array($actionCol, ['can_create', 'can_read', 'can_update', 'can_delete'])) {
            return self::canAccess($user, $permissionKey);
        }

        $row = self::where('role', $role)->where('permission_key', $permissionKey)->first();
        if ($row) {
            // Menu harus aktif (is_allowed) dan can_read aktif dan aksi terkait bernilai true
            return (bool) ($row->is_allowed && $row->can_read && $row->{$actionCol});
        }

        // Bawaan HANYA jika peran belum pernah dikonfigurasi sama sekali di database
        if (!self::where('role', $role)->exists() && self::isDefaultAllowed($role, $permissionKey)) {
            if ($actionCol === 'can_read') return true;
            if ($role === 'admin') return true;
            if (in_array($role, ['guru', 'tendik']) && in_array($actionCol, ['can_create', 'can_update'])) return true;
        }

        return false;
    }
}
