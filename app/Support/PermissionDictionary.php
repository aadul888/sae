<?php

namespace App\Support;

final class PermissionDictionary
{
    /**
     * Definisi dasar modul & fitur bawaan sistem yang dikelompokkan berdasarkan cluster modul.
     */
    public static function base(): array
    {
        return [
            'Menu Utama' => [
                'menu_dashboard' => [
                    'label' => 'Dashboard Utama',
                    'icon' => 'fa-gauge-high',
                    'roles' => ['admin', 'guru', 'tendik', 'peserta_didik', 'orang_tua'],
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
                    'roles' => ['admin'],
                ],
                'menu_pembelajaran' => [
                    'label' => 'Pembelajaran',
                    'icon' => 'fa-book-bookmark',
                    'roles' => ['admin'],
                ],
                'menu_jadwal_kbm' => [
                    'label' => 'Jadwal KBM',
                    'icon' => 'fa-calendar-alt',
                    'roles' => ['admin'],
                ],
                'menu_kalender_pendidikan' => [
                    'label' => 'Kalender Pendidikan',
                    'icon' => 'fa-calendar-days',
                    'roles' => ['admin'],
                ],
            ],

            'Manajemen Data' => [
                'menu_peserta_didik_aktif' => [
                    'label' => 'Peserta Didik Aktif',
                    'icon' => 'fa-user-graduate',
                    'roles' => ['admin'],
                ],
                'menu_guru_aktif' => [
                    'label' => 'Guru Aktif',
                    'icon' => 'fa-chalkboard-user',
                    'roles' => ['admin'],
                ],
                'menu_tendik_aktif' => [
                    'label' => 'Tendik Aktif',
                    'icon' => 'fa-id-badge',
                    'roles' => ['admin'],
                ],
                'menu_peserta_didik_tidak_aktif' => [
                    'label' => 'Peserta Didik Tidak Aktif',
                    'icon' => 'fa-user-xmark',
                    'roles' => ['admin'],
                ],
                'menu_guru_tidak_aktif' => [
                    'label' => 'Guru Tidak Aktif',
                    'icon' => 'fa-user-slash',
                    'roles' => ['admin'],
                ],
                'menu_tendik_tidak_aktif' => [
                    'label' => 'Tendik Tidak Aktif',
                    'icon' => 'fa-id-badge',
                    'roles' => ['admin'],
                ],
            ],

            'Layanan Digital' => [
                'menu_formulir' => [
                    'label' => 'Formulir & Survei',
                    'icon' => 'fa-clipboard-list',
                    'roles' => ['admin'],
                ],
                'menu_pengumuman' => [
                    'label' => 'Pengumuman & Broadcast',
                    'icon' => 'fa-bullhorn',
                    'roles' => ['admin'],
                ],
                'menu_rfid' => [
                    'label' => 'RFID & Presensi Realtime',
                    'icon' => 'fa-id-card',
                    'roles' => ['admin'],
                ],
                'menu_kartu_pelajar' => [
                    'label' => 'Kartu Pelajar & Pembayaran',
                    'icon' => 'fa-address-card',
                    'roles' => ['admin'],
                ],
                'menu_pip' => [
                    'label' => 'Usulan & Riwayat PIP',
                    'icon' => 'fa-hand-holding-dollar',
                    'roles' => ['admin'],
                ],
                'menu_poin' => [
                    'label' => 'Poin & Kedisiplinan',
                    'icon' => 'fa-award',
                    'roles' => ['admin'],
                ],
                'menu_kelulusan' => [
                    'label' => 'Kelulusan & Legalisir SKL',
                    'icon' => 'fa-graduation-cap',
                    'roles' => ['admin'],
                ],
                'menu_buku_tamu' => [
                    'label' => 'Buku Tamu Digital',
                    'icon' => 'fa-book-open-reader',
                    'roles' => ['admin'],
                ],
                'menu_agenda' => [
                    'label' => 'Agenda Sekolah',
                    'icon' => 'fa-calendar-days',
                    'roles' => ['admin'],
                ],
            ],

            'Administrasi Guru' => [
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
                'menu_presensi_peserta_didik' => [
                    'label' => 'Presensi Peserta Didik (KBM)',
                    'icon' => 'fa-clipboard-user',
                    'roles' => ['guru'],
                ],
            ],

            'Wali Kelas' => [
                'menu_wali_kelas' => [
                    'label' => 'Dashboard Wali Kelas',
                    'icon' => 'fa-gauge-high',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_wali_kelas_aktif' => [
                    'label' => 'Peserta Didik Aktif (Wali Kelas)',
                    'icon' => 'fa-user-graduate',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_wali_kelas_tidak_aktif' => [
                    'label' => 'Peserta Didik Tidak Aktif (Wali Kelas)',
                    'icon' => 'fa-user-xmark',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_wali_kelas_presensi' => [
                    'label' => 'Presensi Kelas (Wali Kelas)',
                    'icon' => 'fa-clipboard-user',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_wali_kelas_jadwal' => [
                    'label' => 'Input Jadwal Kelas (Wali Kelas / Koordinator)',
                    'icon' => 'fa-calendar-plus',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_wali_kelas_pip' => [
                    'label' => 'Usulan PIP (Wali Kelas)',
                    'icon' => 'fa-hand-holding-dollar',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_wali_kelas_validasi' => [
                    'label' => 'Validasi Data (Wali Kelas)',
                    'icon' => 'fa-user-check',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_wali_kelas_agenda' => [
                    'label' => 'Agenda Kelas (Wali Kelas / Koordinator)',
                    'icon' => 'fa-book-open-reader',
                    'roles' => ['tugas_tambahan'],
                ],
            ],

            'Tendik: Kepala TAS' => [
                'menu_kepala_tas' => [
                    'label' => 'Dashboard & Manajemen Kepala TAS',
                    'icon' => 'fa-user-tie',
                    'roles' => ['tugas_tambahan'],
                ],
            ],

            'Tendik: Persuratan' => [
                'menu_persuratan' => [
                    'label' => 'Dashboard & Modul Persuratan',
                    'icon' => 'fa-envelope-open-text',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_surat_masuk' => [
                    'label' => 'Buku Agenda Surat Masuk',
                    'icon' => 'fa-inbox',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_surat_keluar' => [
                    'label' => 'Buku Agenda Surat Keluar',
                    'icon' => 'fa-paper-plane',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_pengaturan_persuratan' => [
                    'label' => 'Pengaturan & Arsip HDD Persuratan',
                    'icon' => 'fa-sliders',
                    'roles' => ['tugas_tambahan'],
                ],
            ],

            'Tendik: Kesiswaan' => [
                'menu_kesiswaan' => [
                    'label' => 'Administrasi Kesiswaan Terpadu',
                    'icon' => 'fa-user-graduate',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_kesiswaan_peserta_didik' => [
                    'label' => 'Kesiswaan: Data Peserta Didik',
                    'icon' => 'fa-users',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_kesiswaan_administrasi' => [
                    'label' => 'Kesiswaan: Administrasi (Klaper, Mutasi, Kelulusan)',
                    'icon' => 'fa-book-bookmark',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_buku_induk' => [
                    'label' => 'Kesiswaan: Pengelolaan Buku Induk Siswa',
                    'icon' => 'fa-address-book',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_kesiswaan_kedisiplinan' => [
                    'label' => 'Kesiswaan: Kedisiplinan & Tata Tertib',
                    'icon' => 'fa-shield-halved',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_kesiswaan_kegiatan' => [
                    'label' => 'Kesiswaan: Kegiatan Siswa & OSIS/Ekskul',
                    'icon' => 'fa-people-group',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_kesiswaan_prestasi' => [
                    'label' => 'Kesiswaan: Prestasi Peserta Didik',
                    'icon' => 'fa-trophy',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_berkas_peserta_didik' => [
                    'label' => 'Kesiswaan: Berkas Peserta Didik',
                    'icon' => 'fa-folder-open',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_perubahan_data' => [
                    'label' => 'Kesiswaan: Perubahan Data Siswa',
                    'icon' => 'fa-user-pen',
                    'roles' => ['tugas_tambahan'],
                ],
            ],

            'Tendik: Kepegawaian' => [
                'menu_kepegawaian' => [
                    'label' => 'Dashboard Kepegawaian GTK',
                    'icon' => 'fa-gauge-high',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_kepegawaian_guru' => [
                    'label' => 'Kepegawaian: Pegawai',
                    'icon' => 'fa-users',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_kepegawaian_tendik' => [
                    'label' => 'Kepegawaian: Berkas Pegawai',
                    'icon' => 'fa-folder-open',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_kepegawaian_kgb' => [
                    'label' => 'Kepegawaian: KGB Tracker',
                    'icon' => 'fa-business-time',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_kepegawaian_cuti' => [
                    'label' => 'Kepegawaian: Cuti & Izin',
                    'icon' => 'fa-plane-departure',
                    'roles' => ['tugas_tambahan'],
                ],
            ],

            'Tendik: Keuangan' => [
                'menu_keuangan' => [
                    'label' => 'Administrasi Keuangan & Komite',
                    'icon' => 'fa-wallet',
                    'roles' => ['tugas_tambahan'],
                ],
            ],

            'Tendik: Sarpras & Aset' => [
                'menu_sarpras' => [
                    'label' => 'Dashboard Sarana & Prasarana',
                    'icon' => 'fa-building',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_sarpras_ruang' => [
                    'label' => 'Sarpras: Ruang & Gedung',
                    'icon' => 'fa-door-open',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_inventaris' => [
                    'label' => 'Sarpras: Inventaris & Aset',
                    'icon' => 'fa-boxes-stacked',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_sarpras_peminjaman' => [
                    'label' => 'Sarpras: Peminjaman Sarpras',
                    'icon' => 'fa-hand-holding',
                    'roles' => ['tugas_tambahan'],
                ],
            ],

            'Tendik: Laboratorium' => [
                'menu_laboran' => [
                    'label' => 'Dashboard Laboratorium',
                    'icon' => 'fa-flask',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_laboran_inventaris' => [
                    'label' => 'Laboratorium: Alat & Bahan Lab',
                    'icon' => 'fa-vials',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_laboran_jadwal' => [
                    'label' => 'Laboratorium: Jadwal & Pemakaian Lab',
                    'icon' => 'fa-calendar-days',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_laboran_aset' => [
                    'label' => 'Laboratorium: Inventaris & Aset Lab',
                    'icon' => 'fa-boxes-stacked',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_laboran_peminjaman' => [
                    'label' => 'Laboratorium: Peminjaman Alat & Sarpras',
                    'icon' => 'fa-hand-holding',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_laboran_kebutuhan' => [
                    'label' => 'Laboratorium: Analisis & Kebutuhan Alat/Bahan',
                    'icon' => 'fa-clipboard-list',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_laboran_peserta_didik' => [
                    'label' => 'Laboratorium: Data Peserta Didik Jurusan',
                    'icon' => 'fa-user-graduate',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_laboran_perawatan' => [
                    'label' => 'Laboratorium: Perawatan & K3 Alat',
                    'icon' => 'fa-screwdriver-wrench',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_laboran_pemeriksaan' => [
                    'label' => 'Laboratorium: Pemeriksaan Ceklist Alat',
                    'icon' => 'fa-list-check',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_laboran_pengadaan' => [
                    'label' => 'Laboratorium: Usulan Pengadaan',
                    'icon' => 'fa-file-invoice-dollar',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_laboran_kerusakan' => [
                    'label' => 'Laboratorium: Berita Acara Kerusakan',
                    'icon' => 'fa-triangle-exclamation',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_laboran_laporan' => [
                    'label' => 'Laboratorium: Laporan Penggunaan & Kerusakan',
                    'icon' => 'fa-file-lines',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_laboran_arsip' => [
                    'label' => 'Laboratorium: Arsip Dokumen (Ordner)',
                    'icon' => 'fa-box-archive',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_laboran_jurnal' => [
                    'label' => 'Laboratorium: Jurnal Kegiatan Harian',
                    'icon' => 'fa-book',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_laboran_kebersihan' => [
                    'label' => 'Laboratorium: Kebersihan Lingkungan Produktif',
                    'icon' => 'fa-broom',
                    'roles' => ['tugas_tambahan'],
                ],
            ],

            'Tendik: Perpustakaan' => [
                'menu_perpustakaan' => [
                    'label' => 'Dashboard Perpustakaan',
                    'icon' => 'fa-book-open',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_perpustakaan_koleksi' => [
                    'label' => 'Perpustakaan: Katalog & Koleksi Buku',
                    'icon' => 'fa-book',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_perpustakaan_sirkulasi' => [
                    'label' => 'Perpustakaan: Sirkulasi & Denda',
                    'icon' => 'fa-exchange-alt',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_perpustakaan_kunjungan' => [
                    'label' => 'Perpustakaan: Buku Kunjungan Harian',
                    'icon' => 'fa-user-check',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_perpustakaan_laporan' => [
                    'label' => 'Perpustakaan: Laporan & Rekapitulasi',
                    'icon' => 'fa-file-lines',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_perpustakaan_anggota' => [
                    'label' => 'Perpustakaan: Registrasi Anggota & Kartu Digital',
                    'icon' => 'fa-id-card',
                    'roles' => ['tugas_tambahan'],
                ],
            ],

            'Tendik: Teknisi IT' => [
                'menu_teknisi' => [
                    'label' => 'Dashboard Teknisi & IT',
                    'icon' => 'fa-network-wired',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_teknisi_wo' => [
                    'label' => 'Teknisi: Work Order & Perbaikan',
                    'icon' => 'fa-wrench',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_teknisi_pemeliharaan' => [
                    'label' => 'Teknisi: Jadwal Pemeliharaan PM',
                    'icon' => 'fa-calendar-check',
                    'roles' => ['tugas_tambahan'],
                ],
            ],

            'Tendik: Keamanan & Tamu' => [
                'menu_keamanan' => [
                    'label' => 'Dashboard Keamanan & Satpam',
                    'icon' => 'fa-shield-halved',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_keamanan_izin' => [
                    'label' => 'Keamanan: Verifikasi e-Izin Gerbang',
                    'icon' => 'fa-shield-alt',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_keamanan_presensi' => [
                    'label' => 'Keamanan: Monitoring Presensi Siswa',
                    'icon' => 'fa-id-card-alt',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_keamanan_buku_tamu' => [
                    'label' => 'Keamanan: Buku Tamu Pos Depan',
                    'icon' => 'fa-book-open',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_keamanan_patroli' => [
                    'label' => 'Keamanan: Patroli & Log Insiden',
                    'icon' => 'fa-shield-virus',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_keamanan_scan' => [
                    'label' => 'Keamanan: Pos Scanner RFID',
                    'icon' => 'fa-qrcode',
                    'roles' => ['tugas_tambahan'],
                ],
            ],

            'Tendik: Fasilitas & Penjaga' => [
                'menu_penjaga' => [
                    'label' => 'Dashboard Fasilitas & Penjaga',
                    'icon' => 'fa-broom',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_penjaga_kebersihan' => [
                    'label' => 'Penjaga: Checklist Kebersihan',
                    'icon' => 'fa-clipboard-check',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_penjaga_ronda' => [
                    'label' => 'Penjaga: Buku Jaga & Ronda',
                    'icon' => 'fa-moon',
                    'roles' => ['tugas_tambahan'],
                ],
            ],

            'Tendik: Piket Sekolah' => [
                'menu_piket' => [
                    'label' => 'Dashboard Petugas / Guru Piket',
                    'icon' => 'fa-clipboard-user',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_piket_presensi_guru' => [
                    'label' => 'Piket: Presensi Guru Mengajar',
                    'icon' => 'fa-calendar-check',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_piket_presensi_kelas' => [
                    'label' => 'Piket: Presensi Kelas',
                    'icon' => 'fa-users-viewfinder',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_piket_agenda' => [
                    'label' => 'Piket: Jurnal & Agenda KBM',
                    'icon' => 'fa-book-open-reader',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_piket_izin' => [
                    'label' => 'Piket: e-Izin Keluar Masuk Siswa',
                    'icon' => 'fa-ticket-alt',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_e_izin' => [
                    'label' => 'Piket: Modul e-Izin Siswa',
                    'icon' => 'fa-file-signature',
                    'roles' => ['tugas_tambahan'],
                ],
                'menu_piket_jurnal' => [
                    'label' => 'Piket: Jurnal Guru Piket',
                    'icon' => 'fa-clipboard-list',
                    'roles' => ['tugas_tambahan'],
                ],
            ],

            'Tendik: Kinerja & Aktivitas' => [
                'menu_target_capaian' => [
                    'label' => 'Target & Capaian Pekerjaan',
                    'icon' => 'fa-bullseye',
                    'roles' => ['tendik'],
                ],
                'menu_aktivitas_tendik' => [
                    'label' => 'Aktivitas Harian Tendik',
                    'icon' => 'fa-list-check',
                    'roles' => ['tendik'],
                ],
                'menu_laporan_tendik' => [
                    'label' => 'Laporan Kinerja Tendik',
                    'icon' => 'fa-file-signature',
                    'roles' => ['tendik'],
                ],
            ],

            'Portal Peserta Didik' => [
                'menu_identitas_siswa' => [
                    'label' => 'Identitas Lengkap Siswa (Dapodik)',
                    'icon' => 'fa-id-card',
                    'roles' => ['peserta_didik', 'orang_tua'],
                ],
                'menu_surat_izin_pd' => [
                    'label' => 'Surat Izin & Sakit (Peserta Didik)',
                    'icon' => 'fa-envelope-open-text',
                    'roles' => ['peserta_didik', 'orang_tua'],
                ],
                'menu_riwayat_rfid' => [
                    'label' => 'Riwayat Presensi RFID',
                    'icon' => 'fa-id-card-clip',
                    'roles' => ['peserta_didik', 'orang_tua'],
                ],
                'menu_jadwal_pelajaran' => [
                    'label' => 'Jadwal Pelajaran & Mengajar',
                    'icon' => 'fa-calendar-days',
                    'roles' => ['guru', 'peserta_didik', 'orang_tua'],
                ],
                'menu_validasi_berkas' => [
                    'label' => 'Validasi Berkas',
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
}
