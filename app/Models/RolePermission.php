<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class RolePermission extends Model
{
    protected $table = 'role_permissions';

    protected $fillable = [
        'role',
        'permission_key',
        'is_allowed',
    ];

    protected $casts = [
        'is_allowed' => 'boolean',
    ];

    /**
     * Definisi seluruh daftar menu & fitur yang dapat dikonfigurasi hak aksesnya
     */
    public static function getAvailablePermissions(): array
    {
        return [
            'Menu Navigasi' => [
                'menu_dashboard' => [
                    'label' => 'Dashboard Utama',
                    'desc' => 'Mengakses dashboard ringkasan statistik masing-masing role',
                    'icon' => 'fa-gauge-high',
                    'roles' => ['admin', 'guru', 'siswa'],
                ],
                'menu_pengguna' => [
                    'label' => 'Manajemen Pengguna',
                    'desc' => 'Melihat dan mengelola akun admin, guru/tendik, serta siswa',
                    'icon' => 'fa-users-gear',
                    'roles' => ['admin'],
                ],
                'menu_guru' => [
                    'label' => 'Data Guru & Tendik',
                    'desc' => 'Melihat data kepegawaian PTK dan biodata GTK',
                    'icon' => 'fa-users',
                    'roles' => ['admin'],
                ],
                'menu_siswa' => [
                    'label' => 'Data Siswa & Kelas',
                    'desc' => 'Melihat direktori peserta didik dan rombel kelas',
                    'icon' => 'fa-user-graduate',
                    'roles' => ['admin'],
                ],
                'menu_rfid' => [
                    'label' => 'RFID & Presensi Realtime',
                    'desc' => 'Monitoring absensi tap kartu RFID dan status kehadiran',
                    'icon' => 'fa-id-card',
                    'roles' => ['admin'],
                ],
                'menu_dapodik' => [
                    'label' => 'Tarik Data Dapodik',
                    'desc' => 'Sinkronisasi web service data lokal Dapodikdasmen',
                    'icon' => 'fa-cloud-arrow-down',
                    'roles' => ['admin'],
                ],
                'menu_update' => [
                    'label' => 'Update Sistem',
                    'desc' => 'Deteksi dan eksekusi pembaruan source code & database',
                    'icon' => 'fa-arrows-rotate',
                    'roles' => ['admin'],
                ],
                'menu_hak_akses' => [
                    'label' => 'Pengaturan Hak Akses (Modul Baru)',
                    'desc' => 'Mengonfigurasi hak akses menu dan fitur tiap peran pengguna',
                    'icon' => 'fa-shield-halved',
                    'roles' => ['admin'],
                ],
                'menu_pengumuman' => [
                    'label' => 'Pengumuman & Info',
                    'desc' => 'Pusat informasi dan broadcast sekolah',
                    'icon' => 'fa-bullhorn',
                    'roles' => ['admin', 'guru', 'siswa'],
                ],
                'menu_pengaturan' => [
                    'label' => 'Pengaturan Sistem',
                    'desc' => 'Konfigurasi identitas sekolah dan parameter aplikasi',
                    'icon' => 'fa-sliders',
                    'roles' => ['admin'],
                ],

                // Menu Khusus Guru
                'menu_presensi_mengajar' => [
                    'label' => 'Presensi Mengajar (Guru)',
                    'desc' => 'Pencatatan kehadiran mengajar di kelas',
                    'icon' => 'fa-calendar-check',
                    'roles' => ['guru'],
                ],
                'menu_agenda_kbm' => [
                    'label' => 'Jurnal & Agenda KBM (Guru)',
                    'desc' => 'Pencatatan materi pelajaran dan ketercapaian kompetensi',
                    'icon' => 'fa-book-open-reader',
                    'roles' => ['guru'],
                ],
                'menu_penilaian' => [
                    'label' => 'Penilaian Siswa (Guru)',
                    'desc' => 'Input nilai tugas, ulangan harian, dan rapor siswa',
                    'icon' => 'fa-graduation-cap',
                    'roles' => ['guru'],
                ],
                'menu_presensi_siswa' => [
                    'label' => 'Presensi Kelas Siswa (Guru)',
                    'desc' => 'Input status hadir/sakit/izin/alfa siswa dalam rombel',
                    'icon' => 'fa-users-viewfinder',
                    'roles' => ['guru'],
                ],

                // Menu Khusus Siswa
                'menu_riwayat_rfid' => [
                    'label' => 'Riwayat Presensi RFID (Siswa)',
                    'desc' => 'Melihat catatan log tap kehadiran masuk/pulang harian',
                    'icon' => 'fa-id-card-clip',
                    'roles' => ['siswa'],
                ],
                'menu_jadwal_pelajaran' => [
                    'label' => 'Jadwal Pelajaran (Siswa)',
                    'desc' => 'Melihat kalender mata pelajaran per semester',
                    'icon' => 'fa-calendar-days',
                    'roles' => ['siswa'],
                ],
                'menu_rapor' => [
                    'label' => 'Transkrip & Rapor (Siswa)',
                    'desc' => 'Melihat capaian nilai akademik dan rapor digital',
                    'icon' => 'fa-file-lines',
                    'roles' => ['siswa'],
                ],
                'menu_validasi_berkas' => [
                    'label' => 'Validasi Berkas & Ijazah (Siswa)',
                    'desc' => 'Pemeriksaan status berkas biodata kependidikan',
                    'icon' => 'fa-folder-open',
                    'roles' => ['siswa'],
                ],
            ],

            'Fitur Operasional' => [
                'fitur_pengguna_edit' => [
                    'label' => 'Edit Data Pengguna',
                    'desc' => 'Mengubah identitas nama, kontak, dan alamat pengguna',
                    'icon' => 'fa-user-pen',
                    'roles' => ['admin'],
                ],
                'fitur_pengguna_hapus' => [
                    'label' => 'Hapus Akun Pengguna',
                    'desc' => 'Menghapus data akun login pengguna secara permanen',
                    'icon' => 'fa-user-xmark',
                    'roles' => ['admin'],
                ],
                'fitur_pengguna_reset' => [
                    'label' => 'Reset Password Pengguna',
                    'desc' => 'Mengembalikan password default pada akun pengguna',
                    'icon' => 'fa-key',
                    'roles' => ['admin'],
                ],
                'fitur_dapodik_sync' => [
                    'label' => 'Generate & Ubah Web Service Dapodik',
                    'desc' => 'Mengubah URL atau Token Web Service integrasi Dapodik',
                    'icon' => 'fa-link',
                    'roles' => ['admin'],
                ],
                'fitur_system_update' => [
                    'label' => 'Eksekusi Update Sistem',
                    'desc' => 'Menjalankan tombol pasang update pada sistem',
                    'icon' => 'fa-download',
                    'roles' => ['admin'],
                ],
            ],
        ];
    }

    /**
     * Cek apakah role tertentu diizinkan mengakses permission tertentu
     */
    public static function canAccess(string $role, string $permissionKey): bool
    {
        // Jika tabel belum ada (sebelum migrasi selesai), fallback allow default admin
        if (!Schema::hasTable('role_permissions')) {
            return $role === 'admin';
        }

        $row = self::where('role', $role)->where('permission_key', $permissionKey)->first();
        if ($row) {
            return (bool) $row->is_allowed;
        }

        // Jika belum tercatat, default admin = true, role lain = false kecuali ada di defaults
        return $role === 'admin';
    }
}
