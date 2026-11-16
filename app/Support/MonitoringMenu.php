<?php

namespace App\Support;

/**
 * Sumber kebenaran tunggal pemetaan menu sidebar -> modul log aktivitas.
 * Dipakai oleh filter Monitoring dan accessor konteks AdminAktivitas
 * supaya nama yang tampil di filter selalu sama dengan sidebar.
 */
final class MonitoringMenu
{
    /**
     * Grup menu sidebar beserta modul log yang menjadi anggotanya.
     * Urutan mengikuti urutan sidebar.
     */
    public static function groups(): array
    {
        return [
            'Utama' => [
                'icon' => 'fa-gauge-high',
                'color' => 'var(--primary, #3b82f6)',
                'bg' => 'rgba(59, 130, 246, 0.12)',
                'modul' => ['Dapodik', 'Dashboard'],
            ],
            'Master Data' => [
                'icon' => 'fa-database',
                'color' => 'var(--accent, #06b6d4)',
                'bg' => 'rgba(6, 182, 212, 0.12)',
                'modul' => ['Master Data', 'Kompetensi Keahlian', 'Rombel', 'Pembelajaran', 'Kalender Pendidikan'],
            ],
            'Manajemen Data' => [
                'icon' => 'fa-users',
                'color' => 'var(--primary, #3b82f6)',
                'bg' => 'rgba(59, 130, 246, 0.12)',
                'modul' => ['Manajemen Data', 'Guru', 'Tendik', 'GTK Non-ASN'],
            ],
            'Akademik' => [
                'icon' => 'fa-chalkboard-user',
                'color' => 'var(--accent, #06b6d4)',
                'bg' => 'rgba(6, 182, 212, 0.12)',
                'modul' => ['Akademik', 'Jadwal', 'Presensi Mengajar', 'Agenda KBM'],
            ],
            'Kepegawaian' => [
                'icon' => 'fa-id-badge',
                'color' => 'var(--warning, #f59e0b)',
                'bg' => 'rgba(245, 158, 11, 0.12)',
                'modul' => ['Kepegawaian', 'KGB', 'Cuti', 'Berkas Pegawai'],
            ],
            'Persuratan' => [
                'icon' => 'fa-envelope-open-text',
                'color' => 'var(--accent, #06b6d4)',
                'bg' => 'rgba(6, 182, 212, 0.12)',
                'modul' => ['Persuratan', 'Surat Masuk', 'Surat Keluar'],
            ],
            'Kesiswaan' => [
                'icon' => 'fa-user-graduate',
                'color' => 'var(--primary, #3b82f6)',
                'bg' => 'rgba(59, 130, 246, 0.12)',
                'modul' => ['Kesiswaan', 'Siswa', 'Buku Induk', 'Kedisiplinan', 'Kegiatan Siswa', 'Prestasi', 'Mutasi'],
            ],
            'Sarpras' => [
                'icon' => 'fa-building',
                'color' => 'var(--secondary, #64748b)',
                'bg' => 'rgba(100, 116, 139, 0.12)',
                'modul' => ['Sarpras', 'Ruang', 'KIR', 'Aset', 'Persediaan', 'Peminjaman'],
            ],
            'Perpustakaan' => [
                'icon' => 'fa-book',
                'color' => 'var(--warning, #f59e0b)',
                'bg' => 'rgba(245, 158, 11, 0.12)',
                'modul' => ['Perpustakaan', 'Koleksi', 'Sirkulasi', 'Kunjungan'],
            ],
            'Laboran' => [
                'icon' => 'fa-flask',
                'color' => 'var(--accent, #06b6d4)',
                'bg' => 'rgba(6, 182, 212, 0.12)',
                'modul' => ['Laboran', 'Laboratorium', 'BHP', 'Inventaris Lab'],
            ],
            'Teknisi' => [
                'icon' => 'fa-screwdriver-wrench',
                'color' => 'var(--secondary, #64748b)',
                'bg' => 'rgba(100, 116, 139, 0.12)',
                'modul' => ['Teknisi', 'Work Order', 'Mutasi Aset'],
            ],
            'Keamanan' => [
                'icon' => 'fa-shield-halved',
                'color' => 'var(--success, #10b981)',
                'bg' => 'rgba(16, 185, 129, 0.12)',
                'modul' => ['Keamanan', 'Buku Tamu', 'Izin Keluar', 'Pelanggaran', 'Patroli', 'Ronda'],
            ],
            'Piket' => [
                'icon' => 'fa-clipboard-check',
                'color' => 'var(--secondary, #64748b)',
                'bg' => 'rgba(100, 116, 139, 0.12)',
                'modul' => ['Piket', 'Jurnal Piket'],
            ],
            'Layanan Digital' => [
                'icon' => 'fa-mobile-screen-button',
                'color' => 'var(--warning, #f59e0b)',
                'bg' => 'rgba(245, 158, 11, 0.12)',
                'modul' => ['Formulir', 'Pengumuman', 'Presensi', 'RFID', 'Kartu Pelajar', 'E-Izin'],
            ],
            'Sistem' => [
                'icon' => 'fa-gears',
                'color' => 'var(--secondary, #64748b)',
                'bg' => 'rgba(100, 116, 139, 0.12)',
                'modul' => ['Sistem', 'Autentikasi', 'Hak Akses', 'Pengguna', 'Backup', 'Pembaruan Sistem'],
            ],
        ];
    }

    /** Daftar nama grup untuk dropdown filter. */
    public static function names(): array
    {
        return array_keys(self::groups());
    }

    /** Modul yang termasuk dalam satu grup; kosong bila grup tidak dikenal. */
    public static function modulesFor(?string $group): array
    {
        if ($group === null || $group === '') {
            return [];
        }
        return self::groups()[$group]['modul'] ?? [];
    }

    /** Grup pemilik sebuah nilai modul log (fallback: Sistem). */
    public static function groupFor(?string $modul): string
    {
        $modul = trim((string) $modul);
        foreach (self::groups() as $name => $meta) {
            if (in_array($modul, $meta['modul'], true)) {
                return $name;
            }
        }
        return 'Sistem';
    }

    /** Metadata visual grup (ikon/warna) untuk badge. */
    public static function meta(?string $modul): array
    {
        $name = self::groupFor($modul);
        return ['name' => $name] + self::groups()[$name];
    }

    /**
     * Turunkan grup menu dari nama route aktif, mis. `dashboard.kesiswaan.administrasi`.
     * Dipakai logger otomatis agar modul yang tercatat selalu selaras sidebar.
     */
    public static function groupForRoute(?string $routeName): ?string
    {
        if (!$routeName) {
            return null;
        }

        $map = [
            'dashboard.kesiswaan' => 'Kesiswaan',
            'dashboard.peserta-didik' => 'Kesiswaan',
            'dashboard.buku-induk' => 'Kesiswaan',
            'dashboard.kepegawaian' => 'Kepegawaian',
            'dashboard.guru-aktif' => 'Manajemen Data',
            'dashboard.tendik-aktif' => 'Manajemen Data',
            'dashboard.gtk-non-asn' => 'Manajemen Data',
            'dashboard.peserta-didik-aktif' => 'Manajemen Data',
            'dashboard.persuratan' => 'Persuratan',
            'dashboard.sarpras' => 'Sarpras',
            'dashboard.perpustakaan' => 'Perpustakaan',
            'dashboard.laboran' => 'Laboran',
            'dashboard.teknisi' => 'Teknisi',
            'dashboard.keamanan' => 'Keamanan',
            'dashboard.piket' => 'Piket',
            'dashboard.penjaga' => 'Keamanan',
            'dashboard.formulir' => 'Layanan Digital',
            'dashboard.pengumuman' => 'Layanan Digital',
            'dashboard.presensi' => 'Layanan Digital',
            'dashboard.presensi-mengajar' => 'Akademik',
            'dashboard.agenda-kbm' => 'Akademik',
            'dashboard.jadwal' => 'Akademik',
            'dashboard.rombel' => 'Master Data',
            'dashboard.pembelajaran' => 'Master Data',
            'dashboard.kompetensi-keahlian' => 'Master Data',
            'dashboard.kalender-pendidikan' => 'Master Data',
            'dashboard.dapodik' => 'Utama',
        ];

        foreach ($map as $prefix => $group) {
            if (str_starts_with($routeName, $prefix)) {
                return $group;
            }
        }

        return null;
    }
}
