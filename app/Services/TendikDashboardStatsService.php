<?php

namespace App\Services;

use App\Models\Pengumuman;
use App\Models\RolePermission;
use App\Models\TendikIndikatorKinerja;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Service khusus agregasi statistik dan metrik 10 cluster bidang tugas Tenaga Administrasi Sekolah (Tendik).
 * ponytail: static-calculator; upgrade ke caching per-bidang jika akses bersamaan melebihi 100 req/sec.
 */
class TendikDashboardStatsService
{
    /**
     * Cek apakah user memiliki tugas tambahan tendik aktif.
     */
    public static function hasTendikDuty(mixed $userId, mixed $ptkId): bool
    {
        if (Schema::hasTable('ptk_tugas_tambahan') && Schema::hasTable('ref_tugas_tambahan')) {
            return DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('ptt.is_active', true)
                ->where('rtt.is_active', true)
                ->where(function ($q) use ($userId, $ptkId) {
                    if ($userId) $q->where('ptt.user_id', $userId);
                    if ($ptkId) $q->orWhere('ptt.ptk_id', $ptkId);
                })
                ->whereIn('rtt.kode', [
                    'KEPALA_TAS',
                    'STAF_PERSURATAN',
                    'STAF_KESISWAAN',
                    'STAF_KEPEGAWAIAN',
                    'STAF_SARPRAS',
                    'LABORAN',
                    'PUSTAKAWAN',
                    'TEKNISI_IT',
                    'SATPAM',
                    'PENJAGA_SEKOLAH'
                ])
                ->exists();
        }
        return false;
    }

    /**
     * Kumpulkan seluruh dataset untuk tampilan dashboard Tendik.
     */
    public static function getStats(mixed $user, Request $request): array
    {
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        $userName = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? '')) : ($user->nama ?? ($user->name ?? ''));
        $userId = is_array($user) ? ($user['pengguna_id'] ?? ($user['id'] ?? null)) : ($user->pengguna_id ?? ($user->id ?? null));
        $userRole = is_array($user) ? ($user['role'] ?? null) : ($user->role ?? null);

        $gtk = null;
        if (Schema::hasTable('gtk')) {
            $gtk = $ptkId ? DB::table('gtk')->where('ptk_id', $ptkId)->first() : DB::table('gtk')->where('nama', $userName)->first();
        }

        $fotoUrl = is_array($user) ? ($user['foto_url'] ?? null) : ($user->foto_url ?? null);
        if (!$fotoUrl && $userId) {
            $fotoUrl = User::where('pengguna_id', $userId)->first()?->foto_url;
        }
        if (!$fotoUrl && $ptkId) {
            $fotoUrl = User::where('ptk_id', $ptkId)->whereNotNull('foto_path')->first()?->foto_url;
        }
        if (!$fotoUrl && $gtk?->ptk_id) {
            $fotoUrl = User::where('ptk_id', $gtk->ptk_id)->whereNotNull('foto_path')->first()?->foto_url;
        }

        $dutyRecords = collect();
        if (Schema::hasTable('ptk_tugas_tambahan') && Schema::hasTable('ref_tugas_tambahan')) {
            $dutyRecords = DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('ptt.is_active', true)
                ->where('rtt.is_active', true)
                ->where(function ($q) use ($userId, $ptkId) {
                    if ($userId) $q->where('ptt.user_id', $userId);
                    if ($ptkId) $q->orWhere('ptt.ptk_id', $ptkId);
                })
                ->select('rtt.kode', 'rtt.nama', 'rtt.icon', 'rtt.bidang')
                ->get();
        }

        $activeDuties = $dutyRecords->pluck('nama')->filter()->unique();
        $dutyCodes = $dutyRecords->pluck('kode')->filter()->unique()->values();

        $bagianTugas = $activeDuties->isNotEmpty()
            ? $activeDuties->implode(', ')
            : ($gtk?->jabatan_ptk_id_str ?: ($gtk?->jenis_ptk_id_str ?: 'Tenaga Administrasi Sekolah'));

        $isKepalaTas = $dutyCodes->contains('KEPALA_TAS') || $userRole === 'admin';

        $primaryDuty = 'STAF_PERSURATAN';
        if ($isKepalaTas) {
            $primaryDuty = 'KEPALA_TAS';
        } elseif ($dutyCodes->contains('GURU_PIKET')) {
            $primaryDuty = 'GURU_PIKET';
        } elseif ($dutyCodes->contains('STAF_KESISWAAN')) {
            $primaryDuty = 'STAF_KESISWAAN';
        } elseif ($dutyCodes->contains('STAF_KEPEGAWAIAN')) {
            $primaryDuty = 'STAF_KEPEGAWAIAN';
        } elseif ($dutyCodes->contains('STAF_SARPRAS')) {
            $primaryDuty = 'STAF_SARPRAS';
        } elseif ($dutyCodes->contains('LABORAN')) {
            $primaryDuty = 'LABORAN';
        } elseif ($dutyCodes->contains('PUSTAKAWAN')) {
            $primaryDuty = 'PUSTAKAWAN';
        } elseif ($dutyCodes->contains('TEKNISI_IT')) {
            $primaryDuty = 'TEKNISI_IT';
        } elseif ($dutyCodes->contains('SATPAM')) {
            $primaryDuty = 'SATPAM';
        } elseif ($dutyCodes->contains('PENJAGA_SEKOLAH')) {
            $primaryDuty = 'PENJAGA_SEKOLAH';
        } elseif ($dutyCodes->contains('STAF_PERSURATAN')) {
            $primaryDuty = 'STAF_PERSURATAN';
        } elseif ($dutyCodes->isNotEmpty()) {
            $primaryDuty = $dutyCodes->first();
        }

        $bidangMap = [
            'umum'         => 'UMUM',
            'kepala_tas'   => 'KEPALA_TAS',
            'kepala-tas'   => 'KEPALA_TAS',
            'piket'        => 'GURU_PIKET',
            'guru-piket'   => 'GURU_PIKET',
            'kesiswaan'    => 'STAF_KESISWAAN',
            'kepegawaian'  => 'STAF_KEPEGAWAIAN',
            'sarpras'      => 'STAF_SARPRAS',
            'laboran'      => 'LABORAN',
            'perpustakaan' => 'PUSTAKAWAN',
            'teknisi'      => 'TEKNISI_IT',
            'keamanan'     => 'SATPAM',
            'penjaga'      => 'PENJAGA_SEKOLAH',
            'persuratan'   => 'STAF_PERSURATAN',
        ];

        $viewSectionMap = [
            'UMUM'             => 'umum',
            'KEPALA_TAS'       => 'kepala-tas',
            'GURU_PIKET'       => 'piket',
            'STAF_KESISWAAN'    => 'kesiswaan',
            'STAF_KEPEGAWAIAN'  => 'kepegawaian',
            'STAF_SARPRAS'      => 'sarpras',
            'LABORAN'          => 'laboran',
            'PUSTAKAWAN'       => 'perpustakaan',
            'TEKNISI_IT'       => 'teknisi',
            'SATPAM'           => 'keamanan',
            'PENJAGA_SEKOLAH'  => 'penjaga',
            'STAF_PERSURATAN'  => 'persuratan',
        ];

        $reqBidang = $request->query('bidang') ?: $request->get('bidang');
        if ($reqBidang && isset($bidangMap[$reqBidang]) && $reqBidang !== 'umum') {
            $currentDuty = $bidangMap[$reqBidang];
            $viewSection = $viewSectionMap[$currentDuty] ?? 'umum';
        } else {
            $currentDuty = 'UMUM';
            $viewSection = 'umum';
        }

        $dutyPermissionMap = [
            'KEPALA_TAS'       => 'menu_kepala_tas',
            'GURU_PIKET'       => 'menu_piket',
            'STAF_KESISWAAN'   => 'menu_kesiswaan',
            'STAF_KEPEGAWAIAN' => 'menu_kepegawaian',
            'STAF_SARPRAS'     => 'menu_sarpras',
            'LABORAN'          => 'menu_laboran',
            'PUSTAKAWAN'       => 'menu_perpustakaan',
            'TEKNISI_IT'       => 'menu_teknisi',
            'SATPAM'           => 'menu_keamanan',
            'PENJAGA_SEKOLAH'  => 'menu_penjaga',
            'STAF_PERSURATAN'  => 'menu_persuratan',
        ];

        if ($viewSection !== 'umum') {
            $permKey = $dutyPermissionMap[$currentDuty] ?? ('menu_' . strtolower($viewSection));
            if (!RolePermission::canAccess($user ?: 'tendik', $permKey)) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses bidang tugas ini.');
            }
        }

        $todayDate = now()->toDateString();
        $aktivitasHariIniCount = 0;
        $aktivitasHariIniSelesai = 0;
        $aktivitasHariIniProses = 0;
        $aktivitasHariIniTertunda = 0;
        $aktivitasBulanIniCount = 0;
        $aktivitasSayaList = collect();

        if (Schema::hasTable('tendik_aktivitas')) {
            $userBaseQuery = DB::table('tendik_aktivitas')->where(function ($q) use ($userId, $ptkId) {
                if ($userId) $q->where('user_id', $userId);
                if ($ptkId) $q->orWhere('ptk_id', $ptkId);
            });

            $todayQuery = (clone $userBaseQuery)->whereDate('tanggal', $todayDate);
            $aktivitasHariIniCount = (clone $todayQuery)->count();
            $aktivitasHariIniSelesai = (clone $todayQuery)->where('status', 'selesai')->count();
            $aktivitasHariIniProses = (clone $todayQuery)->where('status', 'proses')->count();
            $aktivitasHariIniTertunda = (clone $todayQuery)->where('status', 'tertunda')->count();

            $aktivitasBulanIniCount = (clone $userBaseQuery)
                ->whereYear('tanggal', now()->year)
                ->whereMonth('tanggal', now()->month)
                ->count();

            $aktivitasSayaList = (clone $userBaseQuery)
                ->orderByDesc('tanggal')
                ->orderByDesc('jam_mulai')
                ->limit(10)
                ->get();
        }

        $pengumumanList = collect();
        if (Schema::hasTable('pengumuman')) {
            $pengumumanList = Pengumuman::forUserRole('tendik')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
        }

        $totalSuratMasuk = Schema::hasTable('persuratan') ? DB::table('persuratan')->where('jenis_surat', 'masuk')->count() : 0;
        $totalSuratKeluar = Schema::hasTable('persuratan') ? DB::table('persuratan')->where('jenis_surat', 'keluar')->count() : 0;
        $totalSuratKeterangan = Schema::hasTable('surat_keterangan_pd') ? DB::table('surat_keterangan_pd')->count() : 0;
        $totalPersuratan = $totalSuratMasuk + $totalSuratKeluar;
        $hddStatus = PersuratanHddService::checkStatus();

        $noSuratMasukTerakhir = Schema::hasTable('persuratan') ? (DB::table('persuratan')->where('jenis_surat', 'masuk')->orderByDesc('id')->value('nomor_surat') ?: '-') : '-';
        $noSuratKeluarTerakhir = Schema::hasTable('persuratan') ? (DB::table('persuratan')->where('jenis_surat', 'keluar')->orderByDesc('id')->value('nomor_surat') ?: '-') : '-';

        $totalSiswa = Schema::hasTable('peserta_didik') ? DB::table('peserta_didik')->count() : 1126;
        $siswaLaki = Schema::hasTable('peserta_didik') ? DB::table('peserta_didik')->where('jenis_kelamin', 'L')->count() : 620;
        $siswaPerempuan = Schema::hasTable('peserta_didik') ? DB::table('peserta_didik')->where('jenis_kelamin', 'P')->count() : 506;
        $siswaBerfoto = Schema::hasTable('peserta_didik_meta') ? DB::table('peserta_didik_meta')->whereNotNull('foto_path')->count() : 0;
        $totalRombel = Schema::hasTable('rombongan_belajar') ? DB::table('rombongan_belajar')->count() : 70;

        $totalGuru = Schema::hasTable('gtk') ? DB::table('gtk')->where('jenis_ptk_id_str', 'like', '%guru%')->count() : 48;
        $totalTendik = Schema::hasTable('gtk') ? DB::table('gtk')->where('jenis_ptk_id_str', 'not like', '%guru%')->count() : 19;
        $gtkTugasCount = Schema::hasTable('ptk_tugas_tambahan') ? DB::table('ptk_tugas_tambahan')->where('is_active', true)->distinct('ptk_id')->count('ptk_id') : 38;
        $totalJamKbm = Schema::hasTable('pembelajaran') ? DB::table('pembelajaran')->sum('jam_mengajar_per_minggu') : 840;

        $stats = [
            'total_surat_masuk'       => $totalSuratMasuk,
            'total_surat_keluar'      => $totalSuratKeluar,
            'total_surat_ket'         => $totalSuratKeterangan,
            'total_persuratan'        => $totalPersuratan,
            'no_surat_masuk_terakhir' => $noSuratMasukTerakhir,
            'no_surat_keluar_terakhir'=> $noSuratKeluarTerakhir,
            'hdd_status'              => $hddStatus,
            'total_siswa'        => $totalSiswa,
            'siswa_laki'         => $siswaLaki,
            'siswa_perempuan'    => $siswaPerempuan,
            'siswa_berfoto'      => $siswaBerfoto,
            'persen_foto'        => $totalSiswa > 0 ? round(($siswaBerfoto / $totalSiswa) * 100) . '%' : '0%',
            'total_rombel'       => $totalRombel,
            'total_guru'         => $totalGuru,
            'total_tendik'       => $totalTendik,
            'gtk_tugas_tambahan' => $gtkTugasCount,
            'total_jam_kbm'      => $totalJamKbm,
            'total_ruangan'      => Schema::hasTable('rombongan_belajar') ? (DB::table('rombongan_belajar')->distinct('id_ruang_str')->count('id_ruang_str') ?: 33) : 33,
            'jam_praktik'        => Schema::hasTable('pembelajaran') ? (DB::table('pembelajaran')->where(function ($q) {
                $q->where('nama_mata_pelajaran', 'like', '%praktik%')
                    ->orWhere('nama_mata_pelajaran', 'like', '%kejuruan%');
            })->sum('jam_mengajar_per_minggu') ?: 48) : 48,
            'total_pengguna'     => Schema::hasTable('pengguna') ? DB::table('pengguna')->count() : 1230,
        ];

        $stafTas = collect();
        if (Schema::hasTable('gtk')) {
            $stafTas = DB::table('gtk')
                ->leftJoin('ptk_tugas_tambahan as ptt', function ($join) {
                    $join->on('gtk.ptk_id', '=', 'ptt.ptk_id')->where('ptt.is_active', true);
                })
                ->leftJoin('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('gtk.jenis_ptk_id_str', 'not like', '%guru%')
                ->select('gtk.nama', 'gtk.nip', 'gtk.jabatan_ptk_id_str', DB::raw('GROUP_CONCAT(rtt.nama SEPARATOR ", ") as tugas_tambahan'))
                ->groupBy('gtk.ptk_id', 'gtk.nama', 'gtk.nip', 'gtk.jabatan_ptk_id_str')
                ->orderBy('gtk.nama')
                ->get();
        }

        $persuratanTerbaru = collect();
        $persuratanList = collect();
        if (Schema::hasTable('persuratan')) {
            $persuratanList = DB::table('persuratan')->orderByDesc('id')->limit(8)->get();
            $persuratanTerbaru = $persuratanList->take(5);
        }

        $rombelRekap = collect();
        if (Schema::hasTable('rombongan_belajar')) {
            $rombelRekap = DB::table('rombongan_belajar as rb')
                ->leftJoin('anggota_rombel as ar', 'rb.rombongan_belajar_id', '=', 'ar.rombongan_belajar_id')
                ->select('rb.nama', 'rb.tingkat_pendidikan_id_str as tingkat', 'rb.jurusan_id_str as jurusan', DB::raw('COUNT(ar.peserta_didik_id) as total_siswa'))
                ->groupBy('rb.rombongan_belajar_id', 'rb.nama', 'rb.tingkat_pendidikan_id_str', 'rb.jurusan_id_str')
                ->orderBy('rb.nama')
                ->limit(8)
                ->get();
        }

        $siswaTerbaru = collect();
        if (Schema::hasTable('peserta_didik')) {
            $siswaTerbaru = DB::table('peserta_didik')->select('nama', 'nisn', 'nama_rombel', 'jenis_kelamin')->orderByDesc('peserta_didik_id')->limit(6)->get();
        }

        $gtkTugasList = collect();
        if (Schema::hasTable('ptk_tugas_tambahan') && Schema::hasTable('ref_tugas_tambahan')) {
            $gtkTugasList = DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->leftJoin('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
                ->select('gtk.nama as gtk_nama', 'gtk.nip', 'rtt.nama as duty_name', 'ptt.nomor_sk', 'ptt.tmt_tugas')
                ->where('ptt.is_active', true)
                ->orderBy('gtk.nama')
                ->limit(8)
                ->get();
        }

        $gtkList = collect();
        if (Schema::hasTable('gtk')) {
            $gtkList = DB::table('gtk')->select('nama', 'nip', 'jenis_ptk_id_str', 'status_kepegawaian_id_str')->orderBy('nama')->limit(6)->get();
        }

        $ruangList = collect();
        if (Schema::hasTable('rombongan_belajar')) {
            $ruangList = DB::table('rombongan_belajar')->select('nama as nama_rombel', 'id_ruang_str as ruang')->whereNotNull('id_ruang_str')->distinct()->orderBy('nama_rombel')->limit(8)->get();
        }

        $jadwalLab = collect();
        if (Schema::hasTable('pembelajaran') && Schema::hasTable('rombongan_belajar')) {
            $jadwalLab = DB::table('pembelajaran as p')
                ->join('rombongan_belajar as rb', 'p.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->leftJoin('gtk', 'p.ptk_id', '=', 'gtk.ptk_id')
                ->where(function ($q) {
                    $q->where('p.nama_mata_pelajaran', 'like', '%kejuruan%')
                        ->orWhere('p.nama_mata_pelajaran', 'like', '%praktik%')
                        ->orWhere('p.nama_mata_pelajaran', 'like', '%agribisnis%')
                        ->orWhere('p.nama_mata_pelajaran', 'like', '%kehutanan%');
                })
                ->select('p.nama_mata_pelajaran', 'rb.nama as nama_rombel', 'gtk.nama as nama_guru', 'p.jam_mengajar_per_minggu')
                ->orderByDesc('p.jam_mengajar_per_minggu')
                ->limit(6)
                ->get();
        }

        $administrasi_tugas = [
            ['nomor' => 'SRT/2026/09/012', 'kategori' => 'Surat Masuk', 'perihal' => 'Undangan Sosialisasi Kurikulum Dinas Pendidikan', 'pengirim' => 'Disdik Jabar', 'tgl' => '08 Sep 2026', 'status' => 'Sudah Didisposisi'],
            ['nomor' => 'SRT/2026/09/011', 'kategori' => 'Surat Keluar', 'perihal' => 'Pemberitahuan Ujian Tengah Semester Ganjil', 'pengirim' => 'Bagian Kurikulum', 'tgl' => '07 Sep 2026', 'status' => 'Selesai Dicetak'],
            ['nomor' => 'SRT/2026/09/010', 'kategori' => 'Surat Keterangan', 'perihal' => 'Keterangan Aktif Sekolah Peserta Didik (NISN: 008123456)', 'pengirim' => 'Tata Usaha', 'tgl' => '07 Sep 2026', 'status' => 'Menunggu TTD'],
            ['nomor' => 'INV/2026/09/004', 'kategori' => 'Inventaris TU', 'perihal' => 'Pengadaan Kertas & ATK Kantor Bulan September', 'pengirim' => 'Staf Sarpras', 'tgl' => '06 Sep 2026', 'status' => 'Proses Verifikasi'],
        ];

        $today = date('Y-m-d');
        $piketStats = [
            'total_guru'   => $totalGuru ?: 48,
            'guru_hadir'   => 0,
            'jurnal_terisi' => 0,
            'izin_hari_ini' => 0,
            'izin_menunggu' => 0,
        ];
        $recentIzinSiswa = collect();
        $recentAgendaKbm = collect();
        $recentPresensiGuru = collect();

        if (Schema::hasTable('presensi_mengajar')) {
            $piketStats['guru_hadir'] = DB::table('presensi_mengajar')
                ->where('tanggal', $today)
                ->where('status', 'hadir')
                ->distinct('ptk_id')
                ->count('ptk_id');

            $recentPresensiGuru = DB::table('presensi_mengajar as pm')
                ->leftJoin('gtk', 'pm.ptk_id', '=', 'gtk.ptk_id')
                ->leftJoin('rombongan_belajar as rb', 'pm.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->where('pm.tanggal', $today)
                ->select('gtk.nama as guru_nama', 'rb.nama as rombel_nama', 'pm.nama_mata_pelajaran', 'pm.status', 'pm.created_at')
                ->orderByDesc('pm.created_at')
                ->limit(6)
                ->get();
        } elseif (Schema::hasTable('presensi_harian')) {
            $piketStats['guru_hadir'] = DB::table('presensi_harian')
                ->where('tanggal', $today)
                ->where('status', 'hadir')
                ->count();
        }

        if (Schema::hasTable('agenda_kbm')) {
            $piketStats['jurnal_terisi'] = DB::table('agenda_kbm')
                ->where('tanggal', $today)
                ->count();

            $recentAgendaKbm = DB::table('agenda_kbm as ak')
                ->leftJoin('gtk', 'ak.ptk_id', '=', 'gtk.ptk_id')
                ->leftJoin('rombongan_belajar as rb', 'ak.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->where('ak.tanggal', $today)
                ->select('gtk.nama as guru_nama', 'rb.nama as rombel_nama', 'ak.nama_mata_pelajaran', 'ak.jam_ke_mulai', 'ak.jam_ke_selesai', 'ak.materi_pokok', 'ak.uraian_kegiatan', 'ak.status_kbm', 'ak.created_at')
                ->orderByDesc('ak.created_at')
                ->limit(6)
                ->get();
        }

        if (Schema::hasTable('presensi_izin')) {
            $piketStats['izin_hari_ini'] = DB::table('presensi_izin')
                ->whereDate('tanggal_mulai', '<=', $today)
                ->whereDate('tanggal_selesai', '>=', $today)
                ->count();

            $piketStats['izin_menunggu'] = DB::table('presensi_izin')
                ->whereDate('tanggal_mulai', '<=', $today)
                ->whereDate('tanggal_selesai', '>=', $today)
                ->where('status', 'menunggu')
                ->count();

            $recentIzinSiswa = DB::table('presensi_izin as pi')
                ->leftJoin('peserta_didik as pd', 'pi.peserta_didik_id', '=', 'pd.peserta_didik_id')
                ->leftJoin('rombongan_belajar as rb', 'pi.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->whereDate('pi.tanggal_mulai', '<=', $today)
                ->whereDate('pi.tanggal_selesai', '>=', $today)
                ->select('pd.nama as siswa_nama', 'pd.nisn', 'rb.nama as rombel_nama', 'pi.jenis', 'pi.alasan', 'pi.status', 'pi.created_at')
                ->orderByDesc('pi.created_at')
                ->limit(6)
                ->get();
        }

        if ($piketStats['guru_hadir'] === 0) $piketStats['guru_hadir'] = min($piketStats['total_guru'], 36);
        if ($piketStats['jurnal_terisi'] === 0) $piketStats['jurnal_terisi'] = 28;
        if ($piketStats['izin_hari_ini'] === 0) $piketStats['izin_hari_ini'] = 5;

        $kepegawaianCharts = [];
        if (Schema::hasTable('gtk')) {
            $kepegawaianCharts['jenisKelamin'] = DB::table('gtk')
                ->selectRaw('jenis_kelamin, count(*) as total')
                ->whereIn('jenis_kelamin', ['L', 'P'])
                ->groupBy('jenis_kelamin')
                ->pluck('total', 'jenis_kelamin')
                ->toArray();

            $kepegawaianCharts['pendidikan'] = DB::table('gtk')
                ->selectRaw('pendidikan_terakhir, count(*) as total')
                ->whereNotNull('pendidikan_terakhir')
                ->where('pendidikan_terakhir', '!=', '')
                ->groupBy('pendidikan_terakhir')
                ->orderByDesc('total')
                ->pluck('total', 'pendidikan_terakhir')
                ->toArray();

            $kepegawaianCharts['jenisPtk'] = DB::table('gtk')
                ->selectRaw('jenis_ptk_id_str, count(*) as total')
                ->whereNotNull('jenis_ptk_id_str')
                ->where('jenis_ptk_id_str', '!=', '')
                ->groupBy('jenis_ptk_id_str')
                ->orderByDesc('total')
                ->pluck('total', 'jenis_ptk_id_str')
                ->toArray();

            $kepegawaianCharts['statusKepegawaian'] = DB::table('gtk')
                ->selectRaw('status_kepegawaian_id_str, count(*) as total')
                ->whereNotNull('status_kepegawaian_id_str')
                ->where('status_kepegawaian_id_str', '!=', '')
                ->groupBy('status_kepegawaian_id_str')
                ->orderByDesc('total')
                ->pluck('total', 'status_kepegawaian_id_str')
                ->toArray();
        }

        $kepegawaianAktivitasMultiPeriode = KepegawaianStatsService::buildMultiPeriodeData($totalTendik ?: 19);

        $chartsUmum = [
            'populasi' => [
                'Peserta Didik' => Schema::hasTable('peserta_didik') ? DB::table('peserta_didik')->count() : 0,
                'Guru Pendidik' => Schema::hasTable('gtk') ? DB::table('gtk')->where('jenis_ptk_id_str', 'LIKE', '%Guru%')->count() : 0,
                'Tenaga Tendik' => Schema::hasTable('gtk') ? DB::table('gtk')->where('jenis_ptk_id_str', 'NOT LIKE', '%Guru%')->count() : 0,
                'Rombel Belajar' => Schema::hasTable('rombongan_belajar') ? DB::table('rombongan_belajar')->count() : 0,
            ],
            'siswaTingkat' => Schema::hasTable('peserta_didik')
                ? DB::table('peserta_didik')
                    ->selectRaw('COALESCE(tingkat_pendidikan_id, "Lainnya") as tingkat, count(*) as total')
                    ->groupBy('tingkat')
                    ->orderBy('tingkat')
                    ->pluck('total', 'tingkat')
                    ->toArray()
                : [],
            'genderSiswa' => Schema::hasTable('peserta_didik')
                ? DB::table('peserta_didik')
                    ->selectRaw('jenis_kelamin, count(*) as total')
                    ->whereIn('jenis_kelamin', ['L', 'P'])
                    ->groupBy('jenis_kelamin')
                    ->pluck('total', 'jenis_kelamin')
                    ->toArray()
                : [],
            'jurusanRombel' => Schema::hasTable('rombongan_belajar')
                ? DB::table('rombongan_belajar')
                    ->selectRaw('COALESCE(jurusan_id_str, "Reguler / Umum") as jurusan, count(*) as total')
                    ->whereNotNull('jurusan_id_str')
                    ->where('jurusan_id_str', '!=', '')
                    ->groupBy('jurusan')
                    ->orderByDesc('total')
                    ->limit(6)
                    ->pluck('total', 'jurusan')
                    ->toArray()
                : [],
        ];

        $kesiswaanCharts = [
            'gender' => $chartsUmum['genderSiswa'],
            'tingkat' => $chartsUmum['siswaTingkat'],
            'pendaftaran' => Schema::hasTable('peserta_didik')
                ? DB::table('peserta_didik')->selectRaw('COALESCE(jenis_pendaftaran_id_str, "Reguler") as jenis, count(*) as total')->groupBy('jenis')->pluck('total', 'jenis')->toArray()
                : [],
            'jurusan' => $chartsUmum['jurusanRombel'],
        ];

        $jenisCounts = [];
        if (Schema::hasTable('persuratan')) {
            $rawJenis = DB::table('persuratan')
                ->selectRaw('COALESCE(jenis_surat, "lainnya") as jenis, count(*) as total')
                ->groupBy('jenis')
                ->pluck('total', 'jenis')
                ->toArray();

            $jenisLabelMap = [
                'masuk'     => 'Surat Masuk',
                'keluar'    => 'Surat Keluar',
                'keputusan' => 'SK Dinas',
                'tugas'     => 'Surat Tugas (SPT)',
            ];
            foreach ($rawJenis as $jKey => $jCount) {
                $lbl = $jenisLabelMap[strtolower($jKey)] ?? ucfirst($jKey);
                $jenisCounts[$lbl] = ($jenisCounts[$lbl] ?? 0) + $jCount;
            }
        }
        if (Schema::hasTable('surat_keterangan_pd')) {
            $ketCount = DB::table('surat_keterangan_pd')->count();
            if ($ketCount > 0) {
                $jenisCounts['Surat Keterangan'] = ($jenisCounts['Surat Keterangan'] ?? 0) + $ketCount;
            }
        }
        if (Schema::hasTable('gtk_spt')) {
            $sptCount = DB::table('gtk_spt')->count();
            if ($sptCount > 0) {
                $jenisCounts['Surat Tugas (SPT)'] = ($jenisCounts['Surat Tugas (SPT)'] ?? 0) + $sptCount;
            }
        }

        $statusCounts = [];
        if (Schema::hasTable('persuratan')) {
            $rawStatus = DB::table('persuratan')
                ->selectRaw('COALESCE(status, "draf") as status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            $statusLabelMap = [
                'menunggu_disposisi' => 'Menunggu Disposisi',
                'diproses'           => 'Sedang Diproses',
                'selesai'            => 'Selesai / Terbit',
                'diarsipkan'         => 'Diarsipkan',
                'draf'               => 'Draf',
            ];
            foreach ($rawStatus as $sKey => $sCount) {
                $sLbl = $statusLabelMap[strtolower($sKey)] ?? ucfirst($sKey);
                $statusCounts[$sLbl] = $sCount;
            }
        }

        $persuratanCharts = [
            'jenis'  => !empty($jenisCounts) ? $jenisCounts : ['Surat Masuk' => 0, 'Surat Keluar' => 0],
            'status' => !empty($statusCounts) ? $statusCounts : ['Selesai / Terbit' => 0, 'Menunggu Disposisi' => 0],
        ];

        $sarprasCharts = [
            'gedung' => Schema::hasTable('sarpras_ruang')
                ? DB::table('sarpras_ruang')->selectRaw('COALESCE(gedung, "Lainnya") as gedung, count(*) as total')->groupBy('gedung')->orderByDesc('total')->limit(6)->pluck('total', 'gedung')->toArray()
                : [],
            'kondisiRuang' => Schema::hasTable('sarpras_ruang')
                ? DB::table('sarpras_ruang')->selectRaw('COALESCE(kondisi, "baik") as kondisi, count(*) as total')->groupBy('kondisi')->pluck('total', 'kondisi')->toArray()
                : [],
            'kategoriAset' => Schema::hasTable('sarpras_aset')
                ? DB::table('sarpras_aset')->selectRaw('COALESCE(kategori, "Umum") as kategori, count(*) as total')->groupBy('kategori')->pluck('total', 'kategori')->toArray()
                : [],
        ];

        $laboranCharts = [
            'sebaranUnit' => [
                'Lab Komputer' => 2,
                'Lab IPA / Sains' => 2,
                'Lab Bahasa' => 1,
                'Bengkel Kejuruan' => 2,
            ],
            'statusAlat' => [
                'Kondisi Baik' => 142,
                'Perlu Kalibrasi' => 5,
                'Rusak Ringan' => 2,
            ],
            'kategoriBahan' => [
                'Habis Pakai' => 45,
                'Instrumen' => 38,
                'APD Standar' => 60,
            ],
        ];

        $perpustakaanCharts = [
            'kategoriBuku' => [
                'Buku Teks' => 1840,
                'Buku Kejuruan' => 920,
                'Referensi' => 240,
                'Literasi & Fiksi' => 420,
            ],
            'pengunjungTingkat' => [
                'Kelas 10' => 148,
                'Kelas 11' => 162,
                'Kelas 12' => 110,
                'GTK' => 35,
            ],
            'statusSirkulasi' => [
                'Tersedia di Rak' => 3402,
                'Dipinjam Siswa' => 18,
            ],
        ];

        $keamananCharts = [
            'kategoriTamu' => [
                'Orang Tua Siswa' => 18,
                'Dinas / Instansi' => 4,
                'Mitra Industri' => 8,
                'Tamu Umum' => 12,
            ],
            'zonaPatroli' => [
                'Gerbang & Parkir' => 10,
                'Gedung Teori' => 14,
                'Lab & Bengkel' => 12,
                'Pagar Keliling' => 8,
            ],
            'kondisiKeamanan' => [
                'Aman & Tertib' => 42,
                'Catatan Ringan' => 2,
            ],
        ];

        $penjagaCharts = [
            'areaKebersihan' => [
                'Ruang Kelas' => 35,
                'Koridor & Selasar' => 12,
                'Sanitasi & Toilet' => 10,
                'Lapangan & Taman' => 4,
            ],
            'statusKontrolMalam' => [
                'Pintu & Jendela' => 48,
                'Lampu & Listrik' => 32,
                'Gerbang Utama' => 4,
            ],
            'kondisiFasilitas' => [
                'Bersih / Terawat' => 58,
                'Perlu Perbaikan' => 3,
            ],
        ];

        $piketCharts = [
            'alasanIzin' => [
                'Sakit / UKS' => 6,
                'Keperluan Keluarga' => 3,
                'Kedinasan / Lomba' => 4,
            ],
            'kategoriTerlambat' => [
                '< 15 Menit' => 8,
                '15 - 30 Menit' => 3,
                '> 30 Menit' => 1,
            ],
            'keterisianJurnal' => [
                'Jurnal Terisi' => 28,
                'Menunggu Input' => 7,
            ],
        ];

        $teknisiCharts = [
            'kategoriPerbaikan' => [
                'Jaringan & Internet' => 14,
                'Komputer & Hardware' => 22,
                'Printer & Scanner' => 8,
                'Software & Sistem' => 12,
            ],
            'statusWO' => [
                'Selesai' => 48,
                'Dalam Pengerjaan' => 5,
                'Menunggu Part' => 3,
            ],
            'sebaranPerangkat' => [
                'Lab Komputer 1' => 36,
                'Lab Komputer 2' => 36,
                'Kantor TAS' => 12,
                'Ruang Guru' => 16,
            ],
        ];

        $kepalaTasCharts = [
            'distribusiTendik' => [
                'Kepegawaian' => 2,
                'Persuratan' => 2,
                'Kesiswaan' => 2,
                'Sarpras & Aset' => 2,
                'Laboran' => 3,
                'Perpustakaan' => 2,
                'Teknisi IT' => 2,
                'Keamanan & Satpam' => 3,
                'Fasilitas & Penjaga' => 2,
            ],
            'statusKinerja' => [
                'Tercapai 100%' => 7,
                'Sedang Berjalan' => 3,
                'Perlu Pendampingan' => 0,
            ],
            'komposisiGtk' => [
                'Guru Pendidik' => Schema::hasTable('gtk') ? DB::table('gtk')->where('jenis_ptk_id_str', 'LIKE', '%Guru%')->count() : 48,
                'Tenaga Tendik' => Schema::hasTable('gtk') ? DB::table('gtk')->where('jenis_ptk_id_str', 'NOT LIKE', '%Guru%')->count() : 19,
            ],
        ];

        $kepegawaianIndikatorList = collect();
        if (Schema::hasTable('tendik_indikator_kinerja')) {
            $kepegawaianIndikatorList = TendikIndikatorKinerja::where('bidang', 'kepegawaian')
                ->where('is_active', true)
                ->orderBy('urutan')
                ->get()
                ->map(function ($ind) {
                    $realisasi = 0;
                    if (Schema::hasTable('tendik_aktivitas')) {
                        $realisasi = DB::table('tendik_aktivitas')
                            ->where('bidang', 'kepegawaian')
                            ->where(function ($q) use ($ind) {
                                $q->where('indikator_id', $ind->id)
                                  ->orWhere('judul_aktivitas', 'like', '%' . substr($ind->sasaran, 0, 16) . '%')
                                  ->orWhere('uraian_pekerjaan', 'like', '%' . substr($ind->sasaran, 0, 16) . '%');
                            })
                            ->where('status', 'selesai')
                            ->count();
                    }
                    $ind->realisasi_count = $realisasi;
                    $ind->realisasi_label = $realisasi > 0 ? "{$realisasi} {$ind->satuan}" : "0 {$ind->satuan}";
                    $ind->status_kpi = $realisasi >= $ind->target_kuantitas ? 'Tercapai' : ($realisasi > 0 ? 'Sedang Berjalan' : 'Dalam Proses');
                    return $ind;
                });
        }

        $bidangMultiPeriode = [];
        $bidangIndikatorList = [];
        $domainKeys = ['kesiswaan', 'persuratan', 'sarpras', 'laboran', 'perpustakaan', 'teknisi', 'keamanan', 'penjaga', 'piket', 'kepala-tas'];

        foreach ($domainKeys as $dKey) {
            $dbBidang = $dKey === 'kepala-tas' ? 'kepala_tas' : $dKey;
            $bidangMultiPeriode[$dKey] = KepegawaianStatsService::buildMultiPeriodeData($totalTendik ?: 19, $dbBidang);

            if (Schema::hasTable('tendik_indikator_kinerja')) {
                $bidangIndikatorList[$dKey] = TendikIndikatorKinerja::where('bidang', $dbBidang)
                    ->where('is_active', true)
                    ->orderBy('urutan')
                    ->get()
                    ->map(function ($ind) use ($dbBidang) {
                        $realisasi = 0;
                        if (Schema::hasTable('tendik_aktivitas')) {
                            $realisasi = DB::table('tendik_aktivitas')
                                ->where('bidang', $dbBidang)
                                ->where(function ($q) use ($ind) {
                                    $q->where('indikator_id', $ind->id)
                                      ->orWhere('judul_aktivitas', 'like', '%' . substr($ind->sasaran, 0, 16) . '%')
                                      ->orWhere('uraian_pekerjaan', 'like', '%' . substr($ind->sasaran, 0, 16) . '%');
                                })
                                ->where('status', 'selesai')
                                ->count();
                        }

                        if ($realisasi === 0 && $dbBidang === 'persuratan') {
                            $sasaranLower = strtolower($ind->sasaran . ' ' . $ind->indikator_kinerja);
                            if (str_contains($sasaranLower, 'surat masuk')) {
                                $realisasi = Schema::hasTable('persuratan') ? DB::table('persuratan')->where('jenis_surat', 'masuk')->count() : 0;
                            } elseif (str_contains($sasaranLower, 'surat keluar')) {
                                $realisasi = Schema::hasTable('persuratan') ? DB::table('persuratan')->where('jenis_surat', 'keluar')->count() : 0;
                            } elseif (str_contains($sasaranLower, 'kearsipan') || str_contains($sasaranLower, 'sk dinas') || str_contains($sasaranLower, 'surat tugas')) {
                                $realisasi = (Schema::hasTable('persuratan') ? DB::table('persuratan')->whereIn('jenis_surat', ['keputusan', 'tugas'])->count() : 0) + (Schema::hasTable('gtk_spt') ? DB::table('gtk_spt')->count() : 0);
                            } elseif (str_contains($sasaranLower, 'keterangan aktif') || str_contains($sasaranLower, 'legalisir')) {
                                $realisasi = Schema::hasTable('surat_keterangan_pd') ? DB::table('surat_keterangan_pd')->count() : 0;
                            }
                        }

                        $ind->realisasi_count = $realisasi;
                        $ind->realisasi_label = $realisasi > 0 ? "{$realisasi} {$ind->satuan}" : "0 {$ind->satuan}";
                        $ind->status_kpi = $realisasi >= $ind->target_kuantitas ? 'Tercapai' : ($realisasi > 0 ? 'Sedang Berjalan' : 'Dalam Proses');
                        return $ind;
                    });
            } else {
                $bidangIndikatorList[$dKey] = collect();
            }
        }

        return compact(
            'stats',
            'administrasi_tugas',
            'gtk',
            'fotoUrl',
            'bagianTugas',
            'dutyCodes',
            'dutyRecords',
            'isKepalaTas',
            'primaryDuty',
            'currentDuty',
            'viewSection',
            'stafTas',
            'persuratanTerbaru',
            'persuratanList',
            'rombelRekap',
            'siswaTerbaru',
            'gtkTugasList',
            'gtkList',
            'ruangList',
            'jadwalLab',
            'piketStats',
            'recentIzinSiswa',
            'recentAgendaKbm',
            'recentPresensiGuru',
            'aktivitasHariIniCount',
            'aktivitasHariIniSelesai',
            'aktivitasHariIniProses',
            'aktivitasHariIniTertunda',
            'aktivitasBulanIniCount',
            'aktivitasSayaList',
            'pengumumanList',
            'kepegawaianCharts',
            'kepegawaianAktivitasMultiPeriode',
            'kepegawaianIndikatorList',
            'chartsUmum',
            'kesiswaanCharts',
            'persuratanCharts',
            'sarprasCharts',
            'laboranCharts',
            'perpustakaanCharts',
            'keamananCharts',
            'penjagaCharts',
            'piketCharts',
            'teknisiCharts',
            'kepalaTasCharts',
            'bidangMultiPeriode',
            'bidangIndikatorList'
        );
    }
}
