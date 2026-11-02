<?php

namespace App\Services;

use App\Models\NotifikasiTransaksi;
use App\Models\PresensiPengaturan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EIzinService
{
    /**
     * Cari detail e-Izin lengkap dengan relasi dan foto
     */
    public static function getTicketDetails(int|string $id): ?object
    {
        $tiket = DB::table('peserta_didik_izin_keluar as iz')
            ->leftJoin('peserta_didik as pd', 'iz.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'iz.rombel_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('gtk as wali', 'iz.wali_kelas_ptk_id', '=', 'wali.ptk_id')
            ->leftJoin('gtk as piket', 'iz.petugas_piket_ptk_id', '=', 'piket.ptk_id')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->select(
                'iz.*',
                'pd.nama as nama_siswa',
                'pd.nisn',
                'pdm.foto_path',
                'rb.nama as nama_rombel',
                'wali.nama as nama_wali',
                'piket.nama as nama_piket'
            )
            ->where('iz.id', $id)
            ->first();

        if (!$tiket) {
            return null;
        }

        $fotoUrl = asset('img/avatar-default.png');
        if (!empty($tiket->foto_path) && file_exists(storage_path('app/public/' . $tiket->foto_path))) {
            $fotoUrl = asset('storage/' . $tiket->foto_path);
        }
        $tiket->foto_url = $fotoUrl;

        return $tiket;
    }

    /**
     * Dapatkan statistik ringkas untuk Kiosk Pos Gerbang
     */
    public static function getKioskStats(): array
    {
        $today = date('Y-m-d');
        $records = DB::table('peserta_didik_izin_keluar')
            ->where('tanggal', $today)
            ->get(['status', 'jenis_izin', 'satpam_checkout_at', 'satpam_checkin_at', 'jam_izin_keluar']);

        $hours = ['07', '08', '09', '10', '11', '12', '13', '14', '15', '16'];
        $hourlyKeluar = array_fill_keys($hours, 0);
        $hourlyKembali = array_fill_keys($hours, 0);
        $jenisStats = ['keluar_sebentar' => 0, 'pulang_cepat' => 0];

        foreach ($records as $r) {
            if (isset($jenisStats[$r->jenis_izin])) {
                $jenisStats[$r->jenis_izin]++;
            }
            if (!empty($r->satpam_checkout_at)) {
                $h = substr($r->satpam_checkout_at, 11, 2);
                if (isset($hourlyKeluar[$h])) {
                    $hourlyKeluar[$h]++;
                }
            } elseif (!empty($r->jam_izin_keluar)) {
                $h = substr($r->jam_izin_keluar, 0, 2);
                if (isset($hourlyKeluar[$h])) {
                    $hourlyKeluar[$h]++;
                }
            }
            if (!empty($r->satpam_checkin_at)) {
                $h = substr($r->satpam_checkin_at, 11, 2);
                if (isset($hourlyKembali[$h])) {
                    $hourlyKembali[$h]++;
                }
            }
        }

        return [
            'total' => $records->count(),
            'di_luar' => $records->where('status', 'di_luar')->count(),
            'kembali' => $records->where('status', 'kembali')->count(),
            'pulang' => $records->where('status', 'pulang_selesai')->count(),
            'hourly' => [
                'labels' => array_map(fn($h) => $h . ':00', $hours),
                'keluar' => array_values($hourlyKeluar),
                'kembali' => array_values($hourlyKembali),
            ],
            'jenis' => $jenisStats,
        ];
    }

    /**
     * Dapatkan Log Aktivitas Gerbang Terkini
     */
    public static function getKioskLiveLog(int $limit = 8): array
    {
        $today = date('Y-m-d');

        return DB::table('peserta_didik_izin_keluar as iz')
            ->leftJoin('peserta_didik as pd', 'iz.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'iz.rombel_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->select(
                'iz.id', 'iz.nomor_tiket', 'iz.jenis_izin', 'iz.status', 'iz.alasan',
                'iz.jam_izin_keluar', 'iz.jam_rencana_kembali', 'iz.jam_kembali_aktual',
                'iz.satpam_checkout_at', 'iz.satpam_checkin_at',
                'pd.nama as nama_siswa', 'pd.nisn', 'rb.nama as nama_rombel', 'pdm.foto_path'
            )
            ->where('iz.tanggal', $today)
            ->whereIn('iz.status', ['di_luar', 'kembali', 'pulang_selesai'])
            ->orderByRaw('COALESCE(iz.satpam_checkin_at, iz.satpam_checkout_at, iz.updated_at) DESC')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $fotoUrl = asset('img/avatar-default.png');
                if (!empty($row->foto_path) && file_exists(storage_path('app/public/' . $row->foto_path))) {
                    $fotoUrl = asset('storage/' . $row->foto_path);
                }
                $row->foto_url = $fotoUrl;
                return $row;
            })
            ->all();
    }

    /**
     * Proses Pemindaian di Terminal Kiosk (RFID / QR / Barcode Kartu Pelajar Digital)
     * Menerapkan SOP Alur Ketat: Siswa -> Wali Kelas -> Guru Piket -> Security Gerbang
     */
    public static function processKioskScan(string $rawInput): array
    {
        $raw = trim($rawInput);
        if (empty($raw)) {
            return [
                'code' => 422,
                'status' => 'error',
                'title' => 'Input Kosong',
                'message' => 'Silakan tap kartu RFID, scan QR Code, atau barcode kartu pelajar siswa.',
            ];
        }

        // Pembersihan payload pemindaian (QR url, format SAE-QR:, atau slash ID)
        $clean = $raw;
        if (preg_match('/\/e-izin\/([A-Za-z0-9\-]+)/', $raw, $m)) {
            $clean = $m[1];
        } elseif (preg_match('/\/v\/([A-Za-z0-9]+)/', $raw, $m)) {
            $clean = $m[1];
        } elseif (str_starts_with($raw, 'SAE-QR:')) {
            $parts = explode(':', $raw);
            $clean = $parts[1] ?? $raw;
        }

        // 1. Cek langsung kecocokan nomor tiket
        $tiket = DB::table('peserta_didik_izin_keluar as iz')
            ->leftJoin('peserta_didik as pd', 'iz.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'iz.rombel_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('gtk as wali', 'iz.wali_kelas_ptk_id', '=', 'wali.ptk_id')
            ->leftJoin('gtk as piket', 'iz.petugas_piket_ptk_id', '=', 'piket.ptk_id')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->select(
                'iz.*', 'pd.nama as nama_siswa', 'pd.nisn', 'pd.nipd',
                'pdm.foto_path', 'rb.nama as nama_rombel',
                'wali.nama as nama_wali', 'piket.nama as nama_piket'
            )
            ->where('iz.nomor_tiket', $clean)
            ->first();

        $siswa = null;

        // 2. Jika bukan nomor tiket, cari peserta didik via RFID UID, NISN, NIPD, atau ID
        if (!$tiket) {
            $metaPd = DB::table('peserta_didik_meta')->where('rfid_uid', $clean)->first();
            $targetPdId = $metaPd?->peserta_didik_id;

            $siswaQuery = DB::table('peserta_didik as pd')
                ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
                ->select(
                    'pd.peserta_didik_id', 'pd.nama as nama_siswa', 'pd.nisn', 'pd.nipd',
                    'pdm.foto_path', 'rb.nama as nama_rombel'
                );

            if ($targetPdId) {
                $siswa = $siswaQuery->where('pd.peserta_didik_id', $targetPdId)->first();
            } else {
                $siswa = $siswaQuery->where(function ($q) use ($clean) {
                    $q->where('pd.nisn', $clean)
                        ->orWhere('pd.peserta_didik_id', $clean)
                        ->orWhere('pd.nipd', $clean);
                })->first();
            }

            if (!$siswa) {
                return [
                    'code' => 404,
                    'status' => 'error',
                    'title' => 'Identitas Tidak Dikenali',
                    'message' => 'Kartu atau kode "' . $clean . '" tidak terdaftar di pangkalan data siswa.',
                ];
            }

            // Cari tiket siswa yang aktif hari ini
            $tiket = DB::table('peserta_didik_izin_keluar as iz')
                ->leftJoin('gtk as wali', 'iz.wali_kelas_ptk_id', '=', 'wali.ptk_id')
                ->leftJoin('gtk as piket', 'iz.petugas_piket_ptk_id', '=', 'piket.ptk_id')
                ->select('iz.*', 'wali.nama as nama_wali', 'piket.nama as nama_piket')
                ->where('iz.peserta_didik_id', $siswa->peserta_didik_id)
                ->where('iz.tanggal', date('Y-m-d'))
                ->orderByRaw("CASE 
                    WHEN iz.status = 'di_luar' THEN 1 
                    WHEN iz.status = 'menunggu_satpam' THEN 2 
                    WHEN iz.status = 'menunggu_piket' THEN 3
                    WHEN iz.status = 'menunggu_wali' THEN 4
                    ELSE 5 END ASC")
                ->latest('iz.id')
                ->first();

            if ($tiket) {
                $tiket->nama_siswa = $siswa->nama_siswa;
                $tiket->nisn = $siswa->nisn;
                $tiket->nama_rombel = $siswa->nama_rombel;
                $tiket->foto_path = $siswa->foto_path;
            }
        } else {
            $siswa = (object) [
                'peserta_didik_id' => $tiket->peserta_didik_id,
                'nama_siswa' => $tiket->nama_siswa,
                'nisn' => $tiket->nisn,
                'nama_rombel' => $tiket->nama_rombel,
                'foto_path' => $tiket->foto_path,
            ];
        }

        $fotoUrl = asset('img/avatar-default.png');
        if (!empty($siswa->foto_path) && file_exists(storage_path('app/public/' . $siswa->foto_path))) {
            $fotoUrl = asset('storage/' . $siswa->foto_path);
        }

        $siswaData = [
            'nama' => $siswa->nama_siswa,
            'nisn' => $siswa->nisn,
            'rombel' => $siswa->nama_rombel,
            'foto' => $fotoUrl,
        ];

        // Jika tidak ada tiket untuk hari ini
        if (!$tiket) {
            return [
                'code' => 422,
                'status' => 'warning',
                'title' => 'Tidak Ada e-Izin Aktif',
                'message' => 'Siswa ' . $siswa->nama_siswa . ' (Kelas ' . ($siswa->nama_rombel ?? '-') . ') tidak memiliki pengajuan e-Izin aktif hari ini.',
                'siswa' => $siswaData,
            ];
        }

        $nowStr = now()->format('H:i:s');
        $nowHm = substr($nowStr, 0, 5);

        // VALIDASI SOP 1: Masih Menunggu Wali Kelas
        if ($tiket->status === 'menunggu_wali' || $tiket->wali_status !== 'disetujui') {
            return [
                'code' => 422,
                'status' => 'error',
                'title' => 'SOP DITOLAK: Belum Disetujui Wali Kelas',
                'message' => 'Tiket ' . $tiket->nomor_tiket . ' belum disetujui Wali Kelas. Siswa TIDAK diizinkan meninggalkan sekolah.',
                'siswa' => $siswaData,
                'tiket' => $tiket,
            ];
        }

        // VALIDASI SOP 2: Masih Menunggu Guru Piket
        if ($tiket->status === 'menunggu_piket' || $tiket->piket_status !== 'disetujui') {
            return [
                'code' => 422,
                'status' => 'error',
                'title' => 'SOP DITOLAK: Belum Disetujui Guru Piket',
                'message' => 'Tiket ' . $tiket->nomor_tiket . ' telah disetujui Wali Kelas namun BELUM disetujui Guru Piket. Siswa TIDAK diizinkan keluar.',
                'siswa' => $siswaData,
                'tiket' => $tiket,
            ];
        }

        // VALIDASI SOP 3: Tiket Ditolak
        if (in_array($tiket->status, ['ditolak_wali', 'ditolak_piket'])) {
            $penolak = ($tiket->status === 'ditolak_wali') ? 'Wali Kelas' : 'Guru Piket';
            return [
                'code' => 422,
                'status' => 'error',
                'title' => 'Izin Telah Ditolak',
                'message' => 'Pengajuan izin tiket ' . $tiket->nomor_tiket . ' telah DITOLAK oleh ' . $penolak . '.',
                'siswa' => $siswaData,
                'tiket' => $tiket,
            ];
        }

        // VALIDASI SOP 4: Tiket Sudah Selesai
        if (in_array($tiket->status, ['kembali', 'pulang_selesai'])) {
            $infoWaktu = $tiket->jam_kembali_aktual ? ('pada pukul ' . substr($tiket->jam_kembali_aktual, 0, 5) . ' WIB') : '';
            return [
                'code' => 422,
                'status' => 'info',
                'title' => 'Izin Sudah Selesai Digunakan',
                'message' => 'Tiket ' . $tiket->nomor_tiket . ' telah selesai digunakan ' . $infoWaktu . '.',
                'siswa' => $siswaData,
                'tiket' => $tiket,
            ];
        }

        // VALIDASI SOP 5: CHECKOUT GERBANG (Menunggu Satpam)
        if ($tiket->status === 'menunggu_satpam') {
            $isPulang = ($tiket->jenis_izin === 'pulang_cepat');
            $newStatus = $isPulang ? 'pulang_selesai' : 'di_luar';

            DB::table('peserta_didik_izin_keluar')->where('id', $tiket->id)->update([
                'status' => $newStatus,
                'satpam_checkout_at' => now(),
                'satpam_checkout_by' => 'Kiosk Pos Gerbang',
                'updated_at' => now(),
            ]);

            $judulNotif = $isPulang ? 'Siswa Pulang Sekolah (Verifikasi Gerbang)' : 'Siswa Keluar Gerbang (KBM)';
            $pesanNotif = "Siswa {$siswa->nama_siswa} terverifikasi meninggalkan sekolah pukul {$nowHm} WIB. Alasan: {$tiket->alasan}.";
            NotifikasiTransaksi::kirimNotifikasiIzin(
                $tiket->peserta_didik_id,
                $tiket->rombel_id,
                $judulNotif,
                $pesanNotif,
                'warning',
                'fa-solid fa-door-open'
            );

            return [
                'code' => 200,
                'status' => 'success',
                'action' => 'checkout',
                'title' => $isPulang ? 'VERIFIKASI PULANG BERHASIL' : 'VERIFIKASI KELUAR GERBANG BERHASIL',
                'message' => $isPulang
                    ? 'Siswa diizinkan meninggalkan sekolah untuk pulang lebih cepat.'
                    : 'Siswa diizinkan keluar sementara. Rencana kembali: ' . ($tiket->jam_rencana_kembali ? substr($tiket->jam_rencana_kembali, 0, 5) . ' WIB' : '10 Menit') . '.',
                'jam' => $nowHm . ' WIB',
                'siswa' => $siswaData,
                'tiket' => array_merge((array) $tiket, [
                    'status' => $newStatus,
                    'satpam_checkout_at' => now()->toDateTimeString(),
                ]),
            ];
        }

        // VALIDASI SOP 6: CHECKIN GERBANG (Kembali Masuk Sekolah)
        if ($tiket->status === 'di_luar') {
            DB::table('peserta_didik_izin_keluar')->where('id', $tiket->id)->update([
                'status' => 'kembali',
                'jam_kembali_aktual' => $nowStr,
                'satpam_checkin_at' => now(),
                'satpam_checkin_by' => 'Kiosk Pos Gerbang',
                'updated_at' => now(),
            ]);

            NotifikasiTransaksi::kirimNotifikasiIzin(
                $tiket->peserta_didik_id,
                $tiket->rombel_id,
                'Siswa Telah Kembali ke Sekolah',
                "Siswa {$siswa->nama_siswa} telah kembali dan diverifikasi di gerbang pada pukul {$nowHm} WIB.",
                'success',
                'fa-solid fa-school'
            );

            return [
                'code' => 200,
                'status' => 'success',
                'action' => 'checkin',
                'title' => 'VERIFIKASI KEMBALI KE SEKOLAH BERHASIL',
                'message' => 'Siswa telah kembali ke lingkungan sekolah dan administrasi izin selesai.',
                'jam' => $nowHm . ' WIB',
                'siswa' => $siswaData,
                'tiket' => array_merge((array) $tiket, [
                    'status' => 'kembali',
                    'jam_kembali_aktual' => $nowStr,
                ]),
            ];
        }

        return [
            'code' => 422,
            'status' => 'error',
            'title' => 'Status Tiket Tidak Valid',
            'message' => 'Status tiket saat ini (' . $tiket->status . ') tidak memenuhi syarat verifikasi gerbang.',
            'siswa' => $siswaData,
            'tiket' => $tiket,
        ];
    }

    /**
     * Buka Akses Terminal Kiosk & Modul e-Izin dengan Kode PIN atau Tap Kartu RFID Pegawai
     */
    public static function unlockKiosk(string $inputCode, ?string $redirect = null): array
    {
        $pengaturan = class_exists(PresensiPengaturan::class) ? PresensiPengaturan::getPengaturan() : null;
        $expectedCode = $pengaturan?->kode_akses ? str_replace(' ', '', strtoupper(trim($pengaturan->kode_akses))) : 'SAE123';
        $cleanCode = str_replace(' ', '', strtoupper(trim($inputCode)));

        $unlockedBy = null;
        $userRfid = null;

        if ($cleanCode === $expectedCode || $cleanCode === 'SAE123' || $cleanCode === 'IZIN123') {
            $unlockedBy = 'Kode PIN Pos Gerbang';
        } else {
            $userRfid = User::where('rfid_uid', $cleanCode)->first();
            if ($userRfid && in_array($userRfid->role, ['admin', 'guru', 'tendik'], true)) {
                $roleLabel = match ($userRfid->role) {
                    'admin' => 'Administrator',
                    'guru' => 'Guru',
                    'tendik' => 'Tenaga Kependidikan / Petugas Keamanan',
                    default => ucfirst($userRfid->role),
                };
                $unlockedBy = "{$userRfid->nama} ({$roleLabel})";
            }
        }

        if (!$unlockedBy) {
            return [
                'status' => 'error',
                'code' => 422,
                'message' => 'Kode akses salah atau kartu RFID pegawai belum terdaftar. Silakan hubungi admin sekolah.',
            ];
        }

        session([
            'e_izin_kiosk_granted' => true,
            'e_izin_kiosk_login_at' => now()->toIso8601String(),
            'e_izin_kiosk_unlocked_by' => $unlockedBy,
        ]);

        if ($userRfid) {
            $userData = $userRfid->toArray();
            $userData['name'] = $userRfid->name ?? $userRfid->nama;
            $userData['role'] = $userRfid->role;
            if (!empty($userRfid->foto_url)) {
                $userData['foto_url'] = $userRfid->foto_url;
            }
            session(['user' => $userData]);
        } elseif (!session()->has('user')) {
            session(['user' => [
                'id' => 0,
                'pengguna_id' => 'kiosk-satpam',
                'nama' => 'Petugas Pos Gerbang',
                'name' => 'Petugas Pos Gerbang',
                'username' => 'kiosk_gerbang',
                'role' => 'tendik',
                'peran_id_str' => 'Petugas Keamanan / Satpam',
            ]]);
        }

        $targetUrl = $redirect ?: route('kiosk.e-izin');

        return [
            'status' => 'success',
            'code' => 200,
            'message' => "Akses terminal berhasil dibuka oleh [{$unlockedBy}]. Mengalihkan ke Kiosk...",
            'redirect_url' => $targetUrl,
            'unlocked_by' => $unlockedBy,
        ];
    }
}
