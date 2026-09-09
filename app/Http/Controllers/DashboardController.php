<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private function checkAuth($allowedRole = null)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $userRole = is_array($user) ? ($user['role'] ?? null) : ($user->role ?? null);

        if (!$userRole) {
            // Attempt to resolve role from peran_id_str or default to admin/peserta_didik
            $peran = is_array($user) ? ($user['peran_id_str'] ?? '') : ($user->peran_id_str ?? '');
            $peranLower = strtolower($peran);
            if (str_contains($peranLower, 'admin')) {
                $userRole = 'admin';
            } elseif (str_contains($peranLower, 'tendik') || str_contains($peranLower, 'tenaga kependidikan') || str_contains($peranLower, 'tata usaha')) {
                $userRole = 'tendik';
            } elseif (str_contains($peranLower, 'guru') || str_contains($peranLower, 'pendidik') || str_contains($peranLower, 'ptk')) {
                $userRole = 'guru';
            } else {
                $userRole = 'peserta_didik';
            }

            if (is_array($user)) {
                $user['role'] = $userRole;
                session(['user' => $user]);
            }
        }

        if ($allowedRole && $userRole !== $allowedRole) {
            return redirect()->route('dashboard.' . $userRole);
        }
        return null;
    }

    public function admin()
    {
        if ($res = $this->checkAuth('admin')) return $res;

        $stats = [
            'total_peserta_didik'    => 1248,
            'total_guru'     => 78,
            'total_tendik'   => 24,
            'total_kelas'    => 36,
            'presensi_today' => 96.4,
            'rfid_taps'      => 1184,
            'sync_dapodik'   => 'Hari ini, 08:30 WIB'
        ];

        $recent_logs = [
            ['time' => '08:45', 'user' => 'Budi Santoso, S.Pd.', 'action' => 'Input Nilai Sumatif Kelas XII RPL 1', 'status' => 'success'],
            ['time' => '08:30', 'user' => 'Sistem Sync', 'action' => 'Sinkronisasi Data Peserta Didik Dapodik', 'status' => 'info'],
            ['time' => '08:12', 'user' => 'Ahmad Dahlan', 'action' => 'Validasi Berkas Ijazah Peserta Didik', 'status' => 'success'],
            ['time' => '07:30', 'user' => 'Gateway RFID #01', 'action' => 'Presensi Masuk Gerbang Utama (950 Tap)', 'status' => 'warning'],
        ];

        return view('dashboard.admin', compact('stats', 'recent_logs'));
    }

    public function guru()
    {
        if ($res = $this->checkAuth('guru')) return $res;

        $stats = [
            'total_jam_ajar'  => 24,
            'kelas_diampu'    => 5,
            'total_peserta_didik'     => 175,
            'presensi_masuk'  => '06:45 WIB',
            'status_presensi' => 'Hadir Tepat Waktu'
        ];

        $jadwal_hari_ini = [
            ['jam' => '07:30 - 09:00', 'kelas' => 'XII RPL 1', 'mapel' => 'Pemrograman Web & Mobile', 'ruang' => 'Lab Komputer 2', 'status' => 'Berlangsung'],
            ['jam' => '09:15 - 10:45', 'kelas' => 'XII RPL 2', 'mapel' => 'Basis Data Lanjut', 'ruang' => 'Lab Komputer 1', 'status' => 'Mendatang'],
            ['jam' => '11:00 - 12:30', 'kelas' => 'XI RPL 1', 'mapel' => 'Pemrograman Berorientasi Objek', 'ruang' => 'Lab Komputer 3', 'status' => 'Mendatang'],
        ];

        return view('dashboard.guru', compact('stats', 'jadwal_hari_ini'));
    }

    public function tendik()
    {
        if ($res = $this->checkAuth('tendik')) return $res;

        $stats = [
            'total_surat_masuk'  => 14,
            'total_surat_keluar' => 8,
            'agenda_sekolah'     => 5,
            'buku_tamu_hari_ini' => 12,
            'presensi_masuk'     => '06:50 WIB',
            'status_presensi'    => 'Hadir Tepat Waktu'
        ];

        $administrasi_tugas = [
            ['nomor' => 'SRT/2026/09/012', 'kategori' => 'Surat Masuk', 'perihal' => 'Undangan Sosialisasi Kurikulum Dinas Pendidikan', 'pengirim' => 'Disdik Jabar', 'tgl' => '08 Sep 2026', 'status' => 'Sudah Didisposisi'],
            ['nomor' => 'SRT/2026/09/011', 'kategori' => 'Surat Keluar', 'perihal' => 'Pemberitahuan Ujian Tengah Semester Ganjil', 'pengirim' => 'Bagian Kurikulum', 'tgl' => '07 Sep 2026', 'status' => 'Selesai Dicetak'],
            ['nomor' => 'SRT/2026/09/010', 'kategori' => 'Surat Keterangan', 'perihal' => 'Keterangan Aktif Sekolah Siswa (NISN: 008123456)', 'pengirim' => 'Tata Usaha', 'tgl' => '07 Sep 2026', 'status' => 'Menunggu TTD'],
            ['nomor' => 'INV/2026/09/004', 'kategori' => 'Inventaris TU', 'perihal' => 'Pengadaan Kertas & ATK Kantor Bulan September', 'pengirim' => 'Staf Sarpras', 'tgl' => '06 Sep 2026', 'status' => 'Proses Verifikasi'],
        ];

        return view('dashboard.tendik', compact('stats', 'administrasi_tugas'));
    }

    public function pesertaDidik()
    {
        if ($res = $this->checkAuth('peserta_didik')) return $res;

        $stats = [
            'presensi_bulan_ini' => 98.2,
            'hadir_hari'         => 22,
            'izin_hari'          => 1,
            'sakit_hari'         => 0,
            'alpa_hari'          => 0,
            'poin_prestasi'      => 45,
            'poin_pelanggaran'   => 0
        ];

        $presensi_terakhir = [
            ['tanggal' => '05 Sep 2026', 'jam_masuk' => '06:42 WIB', 'jam_pulang' => '--:--', 'status' => 'Hadir (Tap RFID)', 'badge' => 'success'],
            ['tanggal' => '04 Sep 2026', 'jam_masuk' => '06:40 WIB', 'jam_pulang' => '15:30 WIB', 'status' => 'Hadir', 'badge' => 'success'],
            ['tanggal' => '03 Sep 2026', 'jam_masuk' => '06:48 WIB', 'jam_pulang' => '15:35 WIB', 'status' => 'Hadir', 'badge' => 'success'],
            ['tanggal' => '02 Sep 2026', 'jam_masuk' => '06:38 WIB', 'jam_pulang' => '15:30 WIB', 'status' => 'Hadir', 'badge' => 'success'],
            ['tanggal' => '01 Sep 2026', 'jam_masuk' => '06:50 WIB', 'jam_pulang' => '15:32 WIB', 'status' => 'Hadir', 'badge' => 'success'],
        ];

        $jadwal_pelajaran = [
            ['jam' => '07:30 - 09:00', 'mapel' => 'Pemrograman Web & Mobile', 'guru' => 'Budi Santoso, S.Pd.', 'ruang' => 'Lab Komputer 2'],
            ['jam' => '09:15 - 10:45', 'mapel' => 'Bahasa Inggris Lanjut', 'guru' => 'Siti Nurhaliza, M.Pd.', 'ruang' => 'Ruang 12'],
            ['jam' => '11:00 - 12:30', 'mapel' => 'Pendidikan Pancasila', 'guru' => 'Drs. Hendro Wibowo', 'ruang' => 'Ruang 12'],
        ];

        return view('dashboard.peserta-didik', compact('stats', 'presensi_terakhir', 'jadwal_pelajaran'));
    }
}
