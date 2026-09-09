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
                    'roles' => ['admin', 'guru', 'peserta_didik'],
                ],
                'menu_pengguna' => [
                    'label' => 'Manajemen Pengguna',
                    'desc' => 'Melihat dan mengelola akun admin, guru/tendik, serta peserta didik',
                    'icon' => 'fa-users-gear',
                    'roles' => ['admin'],
                ],
                'menu_guru' => [
                    'label' => 'Data Guru & Tendik',
                    'desc' => 'Melihat data kepegawaian PTK dan biodata GTK',
                    'icon' => 'fa-users',
                    'roles' => ['admin'],
                ],
                'menu_peserta_didik' => [
                    'label' => 'Data Peserta Didik & Kelas',
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
                'menu_peserta_didik_aktif' => [
                    'label' => 'Manajemen Data — Peserta Didik Aktif',
                    'desc' => 'Direktori data peserta didik aktif bersumber dari Dapodik',
                    'icon' => 'fa-user-graduate',
                    'roles' => ['admin', 'guru'],
                ],
                'menu_guru_aktif' => [
                    'label' => 'Manajemen Data — Guru Aktif',
                    'desc' => 'Direktori pendidik dan guru aktif',
                    'icon' => 'fa-chalkboard-user',
                    'roles' => ['admin', 'guru'],
                ],
                'menu_tendik_aktif' => [
                    'label' => 'Manajemen Data — Tendik Aktif',
                    'desc' => 'Direktori tenaga kependidikan (TU, laboran, pustakawan, staf) aktif',
                    'icon' => 'fa-id-badge',
                    'roles' => ['admin', 'guru'],
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
                    'roles' => ['admin', 'guru', 'peserta_didik'],
                ],
                'menu_pengaturan' => [
                    'label' => 'Pengaturan Sistem',
                    'desc' => 'Konfigurasi identitas sekolah dan parameter aplikasi',
                    'icon' => 'fa-sliders',
                    'roles' => ['admin'],
                ],
                'menu_kompetensi_keahlian' => [
                    'label' => 'Master Data — Kompetensi Keahlian',
                    'desc' => 'Mengelola kode dan nama kompetensi keahlian sekolah',
                    'icon' => 'fa-laptop-code',
                    'roles' => ['admin'],
                ],
                'menu_rombel' => [
                    'label' => 'Master Data — Rombel',
                    'desc' => 'Daftar rombongan belajar dan rincian peserta didik kelas',
                    'icon' => 'fa-school',
                    'roles' => ['admin', 'guru'],
                ],
                'menu_pembelajaran' => [
                    'label' => 'Master Data — Pembelajaran',
                    'desc' => 'Mata pelajaran dan alokasi jam pembelajaran kurikulum',
                    'icon' => 'fa-book-bookmark',
                    'roles' => ['admin', 'guru'],
                ],

                // Manajemen Data
                'menu_berkas_peserta_didik' => [
                    'label' => 'Manajemen Data — Berkas Peserta Didik',
                    'desc' => 'Pengelolaan dokumen ijazah, KK, akta lahir, dan berkas peserta didik',
                    'icon' => 'fa-folder-open',
                    'roles' => ['admin', 'guru'],
                ],
                'menu_perubahan_data' => [
                    'label' => 'Manajemen Data — Perubahan Data Peserta Didik',
                    'desc' => 'Pengajuan dan verifikasi permohonan pembaruan biodata peserta didik',
                    'icon' => 'fa-user-pen',
                    'roles' => ['admin', 'guru', 'peserta_didik'],
                ],
                'menu_peserta_didik_tidak_aktif' => [
                    'label' => 'Manajemen Data — Peserta Didik Tidak Aktif',
                    'desc' => 'Arsip data peserta didik mutasi keluar, DO, atau nonaktif',
                    'icon' => 'fa-user-xmark',
                    'roles' => ['admin'],
                ],
                'menu_guru_tidak_aktif' => [
                    'label' => 'Manajemen Data — Guru Tidak Aktif',
                    'desc' => 'Arsip data pendidik dan guru purna/mutasi',
                    'icon' => 'fa-user-slash',
                    'roles' => ['admin'],
                ],
                'menu_tendik_tidak_aktif' => [
                    'label' => 'Manajemen Data — Tendik Tidak Aktif',
                    'desc' => 'Arsip data tenaga kependidikan purna/mutasi',
                    'icon' => 'fa-id-badge',
                    'roles' => ['admin'],
                ],

                // Layanan Digital
                'menu_e_izin' => [
                    'label' => 'Layanan Digital — E-Izin',
                    'desc' => 'Pengajuan dan persetujuan izin/sakit peserta didik secara digital',
                    'icon' => 'fa-file-signature',
                    'roles' => ['admin', 'guru', 'peserta_didik'],
                ],
                'menu_poin' => [
                    'label' => 'Layanan Digital — Poin & Pelanggaran',
                    'desc' => 'Pencatatan poin prestasi dan tata tertib pelanggaran peserta didik',
                    'icon' => 'fa-star-half-stroke',
                    'roles' => ['admin', 'guru'],
                ],
                'menu_agenda' => [
                    'label' => 'Layanan Digital — Agenda Sekolah',
                    'desc' => 'Jadwal kegiatan sekolah dan kalender akademik',
                    'icon' => 'fa-calendar-days',
                    'roles' => ['admin', 'guru', 'peserta_didik'],
                ],
                'menu_buku_tamu' => [
                    'label' => 'Layanan Digital — Buku Tamu',
                    'desc' => 'Pencatatan kunjungan tamu dinas, wali peserta didik, dan umum',
                    'icon' => 'fa-address-book',
                    'roles' => ['admin'],
                ],
                'menu_inventaris' => [
                    'label' => 'Layanan Digital — Inventaris',
                    'desc' => 'Pengelolaan sarana prasarana dan inventaris barang sekolah',
                    'icon' => 'fa-boxes-stacked',
                    'roles' => ['admin'],
                ],
                'menu_kelulusan' => [
                    'label' => 'Layanan Digital — Kelulusan',
                    'desc' => 'Pusat pengumuman kelulusan dan cetak SKL peserta didik',
                    'icon' => 'fa-graduation-cap',
                    'roles' => ['admin', 'guru', 'peserta_didik'],
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
                    'label' => 'Penilaian Peserta Didik (Guru)',
                    'desc' => 'Input nilai tugas, ulangan harian, dan rapor peserta didik',
                    'icon' => 'fa-graduation-cap',
                    'roles' => ['guru'],
                ],
                'menu_presensi_peserta_didik' => [
                    'label' => 'Presensi Kelas Peserta Didik (Guru)',
                    'desc' => 'Input status hadir/sakit/izin/alfa peserta didik dalam rombel',
                    'icon' => 'fa-users-viewfinder',
                    'roles' => ['guru'],
                ],

                // Menu Khusus Peserta Didik
                'menu_riwayat_rfid' => [
                    'label' => 'Riwayat Presensi RFID (Peserta Didik)',
                    'desc' => 'Melihat catatan log tap kehadiran masuk/pulang harian',
                    'icon' => 'fa-id-card-clip',
                    'roles' => ['peserta_didik'],
                ],
                'menu_jadwal_pelajaran' => [
                    'label' => 'Jadwal Pelajaran (Peserta Didik)',
                    'desc' => 'Melihat kalender mata pelajaran per semester',
                    'icon' => 'fa-calendar-days',
                    'roles' => ['peserta_didik'],
                ],
                'menu_rapor' => [
                    'label' => 'Transkrip & Rapor (Peserta Didik)',
                    'desc' => 'Melihat capaian nilai akademik dan rapor digital',
                    'icon' => 'fa-file-lines',
                    'roles' => ['peserta_didik'],
                ],
                'menu_validasi_berkas' => [
                    'label' => 'Validasi Berkas & Ijazah (Peserta Didik)',
                    'desc' => 'Pemeriksaan status berkas biodata kependidikan',
                    'icon' => 'fa-folder-open',
                    'roles' => ['peserta_didik'],
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

        return $role === 'admin';
    }
}
