<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RolePermission extends Model
{
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
     * Definisi seluruh daftar menu & fitur yang dapat dikonfigurasi hak aksesnya,
     * dikelompokkan berdasarkan cluster modul sistem.
     */
    public static function getAvailablePermissions(): array
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
                    'icon' => 'fa-laptop-code',
                    'roles' => ['admin', 'guru', 'tendik'],
                ],
                'menu_rombel' => [
                    'label' => 'Rombongan Belajar (Rombel)',
                    'icon' => 'fa-school',
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
                    'roles' => ['admin', 'guru', 'tendik', 'peserta_didik'],
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
                'menu_agenda' => [
                    'label' => 'Agenda Sekolah',
                    'icon' => 'fa-calendar-days',
                    'roles' => ['admin', 'guru', 'tendik', 'peserta_didik'],
                ],
                'menu_buku_tamu' => [
                    'label' => 'Buku Tamu',
                    'icon' => 'fa-address-book',
                    'roles' => ['admin', 'tendik'],
                ],
                'menu_inventaris' => [
                    'label' => 'Inventaris Barang',
                    'icon' => 'fa-boxes-stacked',
                    'roles' => ['admin', 'tendik'],
                ],
                'menu_kelulusan' => [
                    'label' => 'Kelulusan Siswa',
                    'icon' => 'fa-graduation-cap',
                    'roles' => ['admin', 'guru', 'tendik', 'peserta_didik'],
                ],
            ],

            'Akademik Guru' => [
                'menu_presensi_mengajar' => [
                    'label' => 'Presensi Mengajar',
                    'icon' => 'fa-calendar-check',
                    'roles' => ['guru'],
                ],
                'menu_agenda_kbm' => [
                    'label' => 'Jurnal & Agenda KBM',
                    'icon' => 'fa-book-open-reader',
                    'roles' => ['guru'],
                ],
                'menu_penilaian' => [
                    'label' => 'Penilaian Siswa',
                    'icon' => 'fa-graduation-cap',
                    'roles' => ['guru'],
                ],
                'menu_presensi_peserta_didik' => [
                    'label' => 'Presensi Kelas Siswa',
                    'icon' => 'fa-users-viewfinder',
                    'roles' => ['guru'],
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

        // 1. Evaluasi izin dasar peran (Role-based)
        $allowedByRole = false;
        $row = self::where('role', $role)->where('permission_key', $permissionKey)->first();
        if ($row) {
            $allowedByRole = (bool) ($row->is_allowed && $row->can_read);
        } elseif ($role === 'admin') {
            $allowedByRole = true;
        } else {
            $allowedByRole = self::isDefaultAllowed($role, $permissionKey);
        }

        if ($allowedByRole) {
            return true;
        }

        // 2. Evaluasi izin dari tugas tambahan aktif (Duty-based)
        if (in_array($role, ['guru', 'tendik']) && Schema::hasTable('ptk_tugas_tambahan') && Schema::hasTable('ref_tugas_tambahan')) {
            $userId = is_array($userOrRole) ? ($userOrRole['id'] ?? ($userOrRole['pengguna_id'] ?? null)) : ($userOrRole->id ?? ($userOrRole->pengguna_id ?? null));
            $ptkId = is_array($userOrRole) ? ($userOrRole['ptk_id'] ?? null) : ($userOrRole->ptk_id ?? null);

            if ($userId || $ptkId) {
                return self::hasDutyPermission($userId, $ptkId, $permissionKey);
            }
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

            $grantedJsonList = $query->pluck('rtt.granted_permissions');
            foreach ($grantedJsonList as $raw) {
                $perms = is_string($raw) ? json_decode($raw, true) : $raw;
                if (is_array($perms) && in_array($permissionKey, $perms, true)) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }

    /**
     * Sinkronisasi seluruh modul & fitur ke tabel role_permissions secara otomatis.
     * Jika ada modul baru yang didaftarkan pada sistem, akan otomatis ditambahkan ke database.
     */
    public static function syncAvailablePermissions(): int
    {
        if (!Schema::hasTable('role_permissions')) {
            return 0;
        }

        $allPermissions = self::getAvailablePermissions();
        $addedCount = 0;
        $now = now();

        foreach ($allPermissions as $groupName => $items) {
            foreach ($items as $permKey => $config) {
                $roles = $config['roles'] ?? ['admin'];
                foreach ($roles as $role) {
                    $exists = self::where('role', $role)->where('permission_key', $permKey)->exists();
                    if (!$exists) {
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
                        $addedCount++;
                    }
                }
            }
        }

        return $addedCount;
    }

    /**
     * Daftar izin default bawaan sistem untuk tiap role jika belum dikustomisasi
     */
    public static function isDefaultAllowed(string $role, string $permissionKey): bool
    {
        $defaults = [
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
    public static function can(string $role, string $permissionKey, string $action): bool
    {
        if (!Schema::hasTable('role_permissions')) {
            return $role === 'admin';
        }

        $actionCol = 'can_' . strtolower($action);
        if (!in_array($actionCol, ['can_create', 'can_read', 'can_update', 'can_delete'])) {
            return self::canAccess($role, $permissionKey);
        }

        $row = self::where('role', $role)->where('permission_key', $permissionKey)->first();
        if ($row) {
            // Menu harus aktif (is_allowed) dan kolom aksi terkait bernilai true
            return (bool) ($row->is_allowed && $row->{$actionCol});
        }

        if ($role === 'admin') {
            return true;
        }

        // Bawaan jika belum tercatat di DB
        if (self::isDefaultAllowed($role, $permissionKey)) {
            if ($actionCol === 'can_read') return true;
            if (in_array($role, ['guru', 'tendik']) && in_array($actionCol, ['can_create', 'can_update'])) return true;
        }

        return false;
    }
}
