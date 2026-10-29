<?php

namespace App\Services;

use App\Models\Pengumuman;
use App\Models\RolePermission;
use App\Models\TendikIndikatorKinerja;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\Table;
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
        if (Table::exists('ptk_tugas_tambahan') && Table::exists('ref_tugas_tambahan')) {
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
        if (Table::exists('gtk')) {
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
        if (Table::exists('ptk_tugas_tambahan') && Table::exists('ref_tugas_tambahan')) {
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

        if (Table::exists('tendik_aktivitas')) {
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
        if (Table::exists('pengumuman')) {
            $pengumumanList = Pengumuman::forUserRole('tendik')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
        }

        $totalSuratMasuk = Table::exists('persuratan') ? DB::table('persuratan')->where('jenis_surat', 'masuk')->count() : 0;
        $totalSuratKeluar = Table::exists('persuratan') ? DB::table('persuratan')->where('jenis_surat', 'keluar')->count() : 0;
        $totalSuratKeterangan = Table::exists('surat_keterangan_pd') ? DB::table('surat_keterangan_pd')->count() : 0;
        $totalPersuratan = $totalSuratMasuk + $totalSuratKeluar;
        $hddStatus = PersuratanHddService::checkStatus();

        $noSuratMasukTerakhir = Table::exists('persuratan') ? (DB::table('persuratan')->where('jenis_surat', 'masuk')->orderByDesc('id')->value('nomor_surat') ?: '-') : '-';
        $noSuratKeluarTerakhir = Table::exists('persuratan') ? (DB::table('persuratan')->where('jenis_surat', 'keluar')->orderByDesc('id')->value('nomor_surat') ?: '-') : '-';

        $totalSiswa = Table::exists('peserta_didik') ? DB::table('peserta_didik')->count() : 0;
        $siswaLaki = Table::exists('peserta_didik') ? DB::table('peserta_didik')->where('jenis_kelamin', 'L')->count() : 0;
        $siswaPerempuan = Table::exists('peserta_didik') ? DB::table('peserta_didik')->where('jenis_kelamin', 'P')->count() : 0;
        $siswaBerfoto = Table::exists('peserta_didik_meta') ? DB::table('peserta_didik_meta')->whereNotNull('foto_path')->count() : 0;
        $totalRombel = Table::exists('rombongan_belajar') ? DB::table('rombongan_belajar')->count() : 0;

        $totalGuru = Table::exists('gtk') ? DB::table('gtk')->where('jenis_ptk_id_str', 'like', '%guru%')->count() : 0;
        $totalTendik = Table::exists('gtk') ? DB::table('gtk')->where('jenis_ptk_id_str', 'not like', '%guru%')->count() : 0;
        $gtkTugasCount = Table::exists('ptk_tugas_tambahan') ? DB::table('ptk_tugas_tambahan')->where('is_active', true)->distinct('ptk_id')->count('ptk_id') : 0;
        $totalJamKbm = Table::exists('pembelajaran') ? (int) (DB::table('pembelajaran')->sum('jam_mengajar_per_minggu') ?: 0) : 0;

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
            'total_ruangan'      => Table::exists('rombongan_belajar') ? (int) DB::table('rombongan_belajar')->distinct('id_ruang_str')->count('id_ruang_str') : 0,
            'jam_praktik'        => Table::exists('pembelajaran') ? (int) (DB::table('pembelajaran')->where(function ($q) {
                $q->where('nama_mata_pelajaran', 'like', '%praktik%')
                    ->orWhere('nama_mata_pelajaran', 'like', '%kejuruan%');
            })->sum('jam_mengajar_per_minggu') ?: 0) : 0,
            'total_pengguna'     => Table::exists('pengguna') ? DB::table('pengguna')->count() : 0,
        ];

        $stafTas = collect();
        if (Table::exists('gtk')) {
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
        if (Table::exists('persuratan')) {
            $persuratanList = DB::table('persuratan')->orderByDesc('id')->limit(8)->get();
            $persuratanTerbaru = $persuratanList->take(5);
        }

        $rombelRekap = collect();
        if (Table::exists('rombongan_belajar')) {
            $rombelRekap = DB::table('rombongan_belajar as rb')
                ->leftJoin('anggota_rombel as ar', 'rb.rombongan_belajar_id', '=', 'ar.rombongan_belajar_id')
                ->select('rb.nama', 'rb.tingkat_pendidikan_id_str as tingkat', 'rb.jurusan_id_str as jurusan', DB::raw('COUNT(ar.peserta_didik_id) as total_siswa'))
                ->groupBy('rb.rombongan_belajar_id', 'rb.nama', 'rb.tingkat_pendidikan_id_str', 'rb.jurusan_id_str')
                ->orderBy('rb.nama')
                ->limit(8)
                ->get();
        }

        $siswaTerbaru = collect();
        if (Table::exists('peserta_didik')) {
            $siswaTerbaru = DB::table('peserta_didik')->select('nama', 'nisn', 'nama_rombel', 'jenis_kelamin')->orderByDesc('peserta_didik_id')->limit(6)->get();
        }

        $gtkTugasList = collect();
        if (Table::exists('ptk_tugas_tambahan') && Table::exists('ref_tugas_tambahan')) {
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
        if (Table::exists('gtk')) {
            $gtkList = DB::table('gtk')->select('nama', 'nip', 'jenis_ptk_id_str', 'status_kepegawaian_id_str')->orderBy('nama')->limit(6)->get();
        }

        $ruangList = collect();
        if (Table::exists('rombongan_belajar')) {
            $ruangList = DB::table('rombongan_belajar')->select('nama as nama_rombel', 'id_ruang_str as ruang')->whereNotNull('id_ruang_str')->distinct()->orderBy('nama_rombel')->limit(8)->get();
        }

        $jadwalLab = collect();
        if (Table::exists('pembelajaran') && Table::exists('rombongan_belajar')) {
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

        // Ringkasan administrasi persuratan terbaru (real dari tabel persuratan)
        $administrasi_tugas = [];
        if (Table::exists('persuratan')) {
            $administrasi_tugas = DB::table('persuratan')
                ->select('nomor_surat', 'jenis_surat', 'perihal', 'pengirim_asal', 'tujuan_penerima', 'tanggal_surat', 'status')
                ->orderByDesc('tanggal_surat')
                ->orderByDesc('id')
                ->limit(6)
                ->get()
                ->map(function ($row) {
                    return [
                        'nomor'    => $row->nomor_surat ?: '-',
                        'kategori' => ucfirst(str_replace('_', ' ', $row->jenis_surat ?: 'lainnya')),
                        'perihal'  => $row->perihal ?: '-',
                        'pengirim' => $row->pengirim_asal ?: ($row->tujuan_penerima ?: '-'),
                        'tgl'      => $row->tanggal_surat ? \Illuminate\Support\Carbon::parse($row->tanggal_surat)->translatedFormat('d M Y') : '-',
                        'status'   => ucfirst(str_replace('_', ' ', $row->status ?: '-')),
                    ];
                })
                ->all();
        }

        $today = date('Y-m-d');
        $piketStats = [
            'total_guru'   => $totalGuru ?: 0,
            'guru_hadir'   => 0,
            'jurnal_terisi' => 0,
            'izin_hari_ini' => 0,
            'izin_menunggu' => 0,
        ];
        $recentIzinSiswa = collect();
        $recentAgendaKbm = collect();
        $recentPresensiGuru = collect();

        if (Table::exists('presensi_mengajar')) {
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
        } elseif (Table::exists('presensi_harian')) {
            $piketStats['guru_hadir'] = DB::table('presensi_harian')
                ->where('tanggal', $today)
                ->where('status', 'hadir')
                ->count();
        }

        if (Table::exists('agenda_kbm')) {
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

        if (Table::exists('presensi_izin')) {
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

        // ---- Statistik ringkas Teknisi IT (real dari teknisi_work_order) ----
        $teknisiStats = [
            'wo_total'    => 0,
            'wo_antrean'  => 0,
            'wo_proses'   => 0,
            'wo_selesai'  => 0,
            'wo_menunggu_part' => 0,
        ];
        if (Table::exists('teknisi_work_order')) {
            $teknisiStats['wo_total']   = (int) DB::table('teknisi_work_order')->count();
            $teknisiStats['wo_antrean'] = (int) DB::table('teknisi_work_order')->where('status', 'antrean')->count();
            $teknisiStats['wo_proses']  = (int) DB::table('teknisi_work_order')->where('status', 'proses')->count();
            $teknisiStats['wo_selesai'] = (int) DB::table('teknisi_work_order')->where('status', 'selesai')->count();
            $teknisiStats['wo_menunggu_part'] = (int) DB::table('teknisi_work_order')->where('status', 'menunggu_sparepart')->count();
        }

        // ---- Statistik ringkas Keamanan (real) ----
        $keamananStats = [
            'tamu_hari_ini'   => 0,
            'tamu_di_lokasi'  => 0,
            'patroli_hari_ini' => 0,
            'insiden_aktif'   => 0,
        ];
        if (Table::exists('keamanan_buku_tamu')) {
            $keamananStats['tamu_hari_ini'] = (int) DB::table('keamanan_buku_tamu')->whereDate('tanggal', $today)->count();
            $keamananStats['tamu_di_lokasi'] = (int) DB::table('keamanan_buku_tamu')->where('status', 'berada_di_lokasi')->count();
        }
        if (Table::exists('keamanan_patroli')) {
            $keamananStats['patroli_hari_ini'] = (int) DB::table('keamanan_patroli')->whereDate('tanggal', $today)->count();
        }
        if (Table::exists('keamanan_insiden')) {
            $keamananStats['insiden_aktif'] = (int) DB::table('keamanan_insiden')->where('status_penyelesaian', 'dalam_penanganan')->count();
        }

        // ---- Statistik ringkas Penjaga (real) ----
        $penjagaStats = [
            'checklist_hari_ini' => 0,
            'area_perlu_tindakan' => 0,
            'ronda_hari_ini'     => 0,
            'temuan_malam'       => 0,
        ];
        if (Table::exists('kebersihan_checklist')) {
            $penjagaStats['checklist_hari_ini'] = (int) DB::table('kebersihan_checklist')->whereDate('tanggal', $today)->count();
            $penjagaStats['area_perlu_tindakan'] = (int) DB::table('kebersihan_checklist')
                ->where(function ($q) {
                    $q->where('kondisi_kebersihan', 'perlu_tindakan')
                      ->orWhere('ketersediaan_air_sabun', 'air_mati');
                })->count();
        }
        if (Table::exists('penjaga_malam_log')) {
            $penjagaStats['ronda_hari_ini'] = (int) DB::table('penjaga_malam_log')->whereDate('tanggal', $today)->count();
            $penjagaStats['temuan_malam'] = (int) DB::table('penjaga_malam_log')->where('situasi_keamanan', '<>', 'kondusif')->count();
        }

        // ---- Statistik ringkas Perpustakaan (real) ----
        $perpusStats = [
            'total_judul'        => 0,
            'total_eksemplar'    => 0,
            'kunjungan_hari_ini' => 0,
            'pinjam_aktif'       => 0,
        ];
        if (Table::exists('perpus_koleksi_buku')) {
            $perpusStats['total_judul'] = (int) DB::table('perpus_koleksi_buku')->count();
            $perpusStats['total_eksemplar'] = (int) (DB::table('perpus_koleksi_buku')->sum('jumlah_eksemplar') ?: 0);
        }
        if (Table::exists('perpus_kunjungan')) {
            $perpusStats['kunjungan_hari_ini'] = (int) DB::table('perpus_kunjungan')->whereDate('tanggal', $today)->count();
        }
        if (Table::exists('perpus_sirkulasi')) {
            $perpusStats['pinjam_aktif'] = (int) DB::table('perpus_sirkulasi')->where('status', 'dipinjam')->count();
        }

        // ---- Statistik ringkas Laboran (real) ----
        $laboranStats = [
            'total_ruang_lab' => 0,
            'item_total'      => 0,
            'item_baik'       => 0,
            'item_rusak'      => 0,
        ];
        if (Table::exists('laboran_bahan_alat')) {
            $laboranStats['total_ruang_lab'] = (int) DB::table('laboran_bahan_alat')
                ->whereNotNull('ruang_lab_nama')->where('ruang_lab_nama', '<>', '')
                ->distinct('ruang_lab_nama')->count('ruang_lab_nama');
            $laboranStats['item_total'] = (int) DB::table('laboran_bahan_alat')->count();
            $laboranStats['item_baik']  = (int) (DB::table('laboran_bahan_alat')->sum('jumlah_baik') ?: 0);
            $laboranStats['item_rusak'] = (int) ((DB::table('laboran_bahan_alat')->sum('jumlah_rusak_ringan') ?: 0)
                + (DB::table('laboran_bahan_alat')->sum('jumlah_rusak_berat') ?: 0));
        }

        $kepegawaianCharts = [];
        if (Table::exists('gtk')) {
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

        $kepegawaianAktivitasMultiPeriode = KepegawaianStatsService::buildMultiPeriodeData($totalTendik ?: 0);

        $chartsUmum = [
            'populasi' => [
                'Peserta Didik' => Table::exists('peserta_didik') ? DB::table('peserta_didik')->count() : 0,
                'Guru Pendidik' => Table::exists('gtk') ? DB::table('gtk')->where('jenis_ptk_id_str', 'LIKE', '%Guru%')->count() : 0,
                'Tenaga Tendik' => Table::exists('gtk') ? DB::table('gtk')->where('jenis_ptk_id_str', 'NOT LIKE', '%Guru%')->count() : 0,
                'Rombel Belajar' => Table::exists('rombongan_belajar') ? DB::table('rombongan_belajar')->count() : 0,
            ],
            'siswaTingkat' => Table::exists('peserta_didik')
                ? DB::table('peserta_didik')
                    ->selectRaw('COALESCE(tingkat_pendidikan_id, "Lainnya") as tingkat, count(*) as total')
                    ->groupBy('tingkat')
                    ->orderBy('tingkat')
                    ->pluck('total', 'tingkat')
                    ->toArray()
                : [],
            'genderSiswa' => Table::exists('peserta_didik')
                ? DB::table('peserta_didik')
                    ->selectRaw('jenis_kelamin, count(*) as total')
                    ->whereIn('jenis_kelamin', ['L', 'P'])
                    ->groupBy('jenis_kelamin')
                    ->pluck('total', 'jenis_kelamin')
                    ->toArray()
                : [],
            'jurusanRombel' => Table::exists('rombongan_belajar')
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
            'pendaftaran' => Table::exists('peserta_didik')
                ? DB::table('peserta_didik')->selectRaw('COALESCE(jenis_pendaftaran_id_str, "Reguler") as jenis, count(*) as total')->groupBy('jenis')->pluck('total', 'jenis')->toArray()
                : [],
            'jurusan' => $chartsUmum['jurusanRombel'],
        ];

        $jenisCounts = [];
        if (Table::exists('persuratan')) {
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
        if (Table::exists('surat_keterangan_pd')) {
            $ketCount = DB::table('surat_keterangan_pd')->count();
            if ($ketCount > 0) {
                $jenisCounts['Surat Keterangan'] = ($jenisCounts['Surat Keterangan'] ?? 0) + $ketCount;
            }
        }
        if (Table::exists('gtk_spt')) {
            $sptCount = DB::table('gtk_spt')->count();
            if ($sptCount > 0) {
                $jenisCounts['Surat Tugas (SPT)'] = ($jenisCounts['Surat Tugas (SPT)'] ?? 0) + $sptCount;
            }
        }

        $statusCounts = [];
        if (Table::exists('persuratan')) {
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

        // Dashboard induk Sarpras: agregasi lintas modul (ruang, aset,
        // peminjaman, teknisi, laboran, penjaga, keamanan).
        $sarprasDashboard = SarprasDashboardService::build($user);
        $sarprasCharts = $sarprasDashboard['charts'];

        // ---- Chart Laboran: agregasi real dari tabel laboran_* ----
        $laboranCharts = [
            'sebaranUnit'     => [],
            'statusAlat'      => [],
            'kategoriBahan'   => [],
        ];
        if (Table::exists('laboran_bahan_alat')) {
            $laboranCharts['sebaranUnit'] = DB::table('laboran_bahan_alat')
                ->selectRaw('COALESCE(NULLIF(ruang_lab_nama, ""), "Tanpa Ruang") as unit, COUNT(*) as total')
                ->groupBy('unit')
                ->orderByDesc('total')
                ->limit(8)
                ->pluck('total', 'unit')
                ->map(fn ($v) => (int) $v)
                ->toArray();

            $laboranCharts['statusAlat'] = [
                'Kondisi Baik'    => (int) (DB::table('laboran_bahan_alat')->sum('jumlah_baik') ?: 0),
                'Rusak Ringan'    => (int) (DB::table('laboran_bahan_alat')->sum('jumlah_rusak_ringan') ?: 0),
                'Rusak Berat'     => (int) (DB::table('laboran_bahan_alat')->sum('jumlah_rusak_berat') ?: 0),
                'Kedaluwarsa'     => (int) (DB::table('laboran_bahan_alat')->sum('jumlah_kedaluwarsa') ?: 0),
            ];

            $laboranCharts['kategoriBahan'] = DB::table('laboran_bahan_alat')
                ->selectRaw('COALESCE(NULLIF(jenis, ""), "Lainnya") as jenis, COUNT(*) as total')
                ->groupBy('jenis')
                ->orderByDesc('total')
                ->limit(8)
                ->pluck('total', 'jenis')
                ->map(fn ($v) => (int) $v)
                ->toArray();
        }

        // ---- Chart Perpustakaan: perpus_koleksi_buku / perpus_sirkulasi / perpus_kunjungan ----
        $perpustakaanCharts = [
            'kategoriBuku'      => [],
            'pengunjungTingkat' => [],
            'statusSirkulasi'   => [],
        ];
        if (Table::exists('perpus_koleksi_buku')) {
            $perpustakaanCharts['kategoriBuku'] = DB::table('perpus_koleksi_buku')
                ->selectRaw('COALESCE(NULLIF(kategori, ""), "Umum") as kategori, COUNT(*) as total')
                ->groupBy('kategori')
                ->orderByDesc('total')
                ->pluck('total', 'kategori')
                ->map(fn ($v) => (int) $v)
                ->toArray();

            $tersedia = (int) (DB::table('perpus_koleksi_buku')->sum('eksemplar_tersedia') ?: 0);
            $total    = (int) (DB::table('perpus_koleksi_buku')->sum('jumlah_eksemplar') ?: 0);
            $perpustakaanCharts['statusSirkulasi'] = [
                'Tersedia di Rak' => $tersedia,
                'Dipinjam'        => max(0, $total - $tersedia),
            ];
        }
        if (Table::exists('perpus_kunjungan')) {
            $perpustakaanCharts['pengunjungTingkat'] = DB::table('perpus_kunjungan')
                ->selectRaw('COALESCE(NULLIF(rombel_atau_unit, ""), "Lainnya") as unit, COUNT(*) as total')
                ->groupBy('unit')
                ->orderByDesc('total')
                ->limit(8)
                ->pluck('total', 'unit')
                ->map(fn ($v) => (int) $v)
                ->toArray();
        }

        // ---- Chart Keamanan: keamanan_buku_tamu / keamanan_patroli ----
        $keamananCharts = [
            'kategoriTamu'     => [],
            'zonaPatroli'      => [],
            'kondisiKeamanan'  => [],
        ];
        if (Table::exists('keamanan_buku_tamu')) {
            $keamananCharts['kategoriTamu'] = DB::table('keamanan_buku_tamu')
                ->selectRaw('COALESCE(NULLIF(keperluan, ""), "Lainnya") as keperluan, COUNT(*) as total')
                ->groupBy('keperluan')
                ->orderByDesc('total')
                ->limit(8)
                ->pluck('total', 'keperluan')
                ->map(fn ($v) => (int) $v)
                ->toArray();
        }
        if (Table::exists('keamanan_patroli')) {
            $keamananCharts['zonaPatroli'] = DB::table('keamanan_patroli')
                ->selectRaw('COALESCE(NULLIF(rute_zona, ""), "Lainnya") as zona, COUNT(*) as total')
                ->groupBy('zona')
                ->orderByDesc('total')
                ->limit(8)
                ->pluck('total', 'zona')
                ->map(fn ($v) => (int) $v)
                ->toArray();

            $keamananCharts['kondisiKeamanan'] = DB::table('keamanan_patroli')
                ->selectRaw('COALESCE(NULLIF(kondisi_lingkungan, ""), "lainnya") as kondisi, COUNT(*) as total')
                ->groupBy('kondisi')
                ->orderByDesc('total')
                ->pluck('total', 'kondisi')
                ->map(fn ($v) => (int) $v)
                ->toArray();
        }

        // ---- Chart Penjaga: kebersihan_checklist / penjaga_malam_log ----
        $penjagaCharts = [
            'areaKebersihan'      => [],
            'statusKontrolMalam'  => [],
            'kondisiFasilitas'    => [],
        ];
        if (Table::exists('kebersihan_checklist')) {
            $penjagaCharts['areaKebersihan'] = DB::table('kebersihan_checklist')
                ->selectRaw('COALESCE(NULLIF(area_zona, ""), "Lainnya") as area, COUNT(*) as total')
                ->groupBy('area')
                ->orderByDesc('total')
                ->limit(8)
                ->pluck('total', 'area')
                ->map(fn ($v) => (int) $v)
                ->toArray();

            $penjagaCharts['kondisiFasilitas'] = DB::table('kebersihan_checklist')
                ->selectRaw('COALESCE(NULLIF(kondisi_kebersihan, ""), "lainnya") as kondisi, COUNT(*) as total')
                ->groupBy('kondisi')
                ->orderByDesc('total')
                ->pluck('total', 'kondisi')
                ->map(fn ($v) => (int) $v)
                ->toArray();
        }
        if (Table::exists('penjaga_malam_log')) {
            $penjagaCharts['statusKontrolMalam'] = DB::table('penjaga_malam_log')
                ->selectRaw('COALESCE(NULLIF(status_pintu_jendela, ""), "lainnya") as status, COUNT(*) as total')
                ->groupBy('status')
                ->orderByDesc('total')
                ->pluck('total', 'status')
                ->map(fn ($v) => (int) $v)
                ->toArray();
        }

        // ---- Chart Piket: presensi_izin / agenda_kbm ----
        $piketCharts = [
            'alasanIzin'         => [],
            'kategoriTerlambat'  => [],
            'keterisianJurnal'   => [],
        ];
        if (Table::exists('presensi_izin')) {
            $piketCharts['alasanIzin'] = DB::table('presensi_izin')
                ->selectRaw('COALESCE(NULLIF(alasan, ""), "Lainnya") as alasan, COUNT(*) as total')
                ->groupBy('alasan')
                ->orderByDesc('total')
                ->limit(8)
                ->pluck('total', 'alasan')
                ->map(fn ($v) => (int) $v)
                ->toArray();

            $piketCharts['kategoriTerlambat'] = DB::table('presensi_izin')
                ->selectRaw('COALESCE(NULLIF(jenis, ""), "lainnya") as jenis, COUNT(*) as total')
                ->groupBy('jenis')
                ->orderByDesc('total')
                ->pluck('total', 'jenis')
                ->map(fn ($v) => (int) $v)
                ->toArray();
        }
        if (Table::exists('agenda_kbm')) {
            $todayPiket = date('Y-m-d');
            $terisi = DB::table('agenda_kbm')->whereDate('tanggal', $todayPiket)->count();
            $piketCharts['keterisianJurnal'] = [
                'Jurnal Terisi'   => (int) $terisi,
                'Belum Dilaporkan' => 0,
            ];
        }

        // ---- Chart Teknisi: teknisi_work_order / teknisi_pemeliharaan ----
        $teknisiCharts = [
            'kategoriPerbaikan' => [],
            'statusWO'          => [],
            'sebaranPerangkat'  => [],
        ];
        if (Table::exists('teknisi_work_order')) {
            $teknisiCharts['kategoriPerbaikan'] = DB::table('teknisi_work_order')
                ->selectRaw('COALESCE(NULLIF(kategori_perbaikan, ""), "lainnya") as kategori, COUNT(*) as total')
                ->groupBy('kategori')
                ->orderByDesc('total')
                ->limit(8)
                ->pluck('total', 'kategori')
                ->map(fn ($v) => (int) $v)
                ->toArray();

            $teknisiCharts['statusWO'] = DB::table('teknisi_work_order')
                ->selectRaw('COALESCE(NULLIF(status, ""), "lainnya") as status, COUNT(*) as total')
                ->groupBy('status')
                ->orderByDesc('total')
                ->pluck('total', 'status')
                ->map(fn ($v) => (int) $v)
                ->toArray();

            $teknisiCharts['sebaranPerangkat'] = DB::table('teknisi_work_order')
                ->selectRaw('COALESCE(NULLIF(lokasi_unit, ""), "Lainnya") as lokasi, COUNT(*) as total')
                ->groupBy('lokasi')
                ->orderByDesc('total')
                ->limit(8)
                ->pluck('total', 'lokasi')
                ->map(fn ($v) => (int) $v)
                ->toArray();
        }

        // ---- Chart Kepala TAS: distribusi tendik per tugas tambahan + status kinerja ----
        $kepalaTasCharts = [
            'distribusiTendik' => [],
            'statusKinerja'    => [],
            'komposisiGtk'     => [
                'Guru Pendidik' => Table::exists('gtk') ? DB::table('gtk')->where('jenis_ptk_id_str', 'LIKE', '%Guru%')->count() : 0,
                'Tenaga Tendik' => Table::exists('gtk') ? DB::table('gtk')->where('jenis_ptk_id_str', 'NOT LIKE', '%Guru%')->count() : 0,
            ],
        ];
        if (Table::exists('ptk_tugas_tambahan') && Table::exists('ref_tugas_tambahan')) {
            $kepalaTasCharts['distribusiTendik'] = DB::table('ptk_tugas_tambahan as ptt')
                ->leftJoin('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('ptt.is_active', true)
                ->selectRaw('COALESCE(NULLIF(rtt.nama, ""), "Lainnya") as tugas, COUNT(DISTINCT ptt.ptk_id) as total')
                ->groupBy('tugas')
                ->orderByDesc('total')
                ->limit(10)
                ->pluck('total', 'tugas')
                ->map(fn ($v) => (int) $v)
                ->toArray();
        }
        if (Table::exists('tendik_indikator_kinerja')) {
            $kpiAll = TendikIndikatorKinerja::where('is_active', true)->get();
            $pctByIndikator = $kpiAll->mapWithKeys(function ($ind) {
                $realisasi = Table::exists('tendik_aktivitas')
                    ? DB::table('tendik_aktivitas')
                        ->where('indikator_id', $ind->id)
                        ->where('status', 'selesai')
                        ->count()
                    : 0;
                $target = max(1, (int) $ind->target_kuantitas);
                return [$ind->id => (int) round(($realisasi / $target) * 100)];
            });

            $kepalaTasCharts['statusKinerja'] = [
                'Tercapai / Lebih'   => $pctByIndikator->filter(fn ($p) => $p >= 100)->count(),
                'Sedang Berjalan'    => $pctByIndikator->filter(fn ($p) => $p > 0 && $p < 100)->count(),
                'Belum Ada Realisasi'=> $pctByIndikator->filter(fn ($p) => $p === 0)->count(),
            ];
        }

        $kepegawaianIndikatorList = collect();
        if (Table::exists('tendik_indikator_kinerja')) {
            $kepegawaianIndikatorList = TendikIndikatorKinerja::where('bidang', 'kepegawaian')
                ->where('is_active', true)
                ->orderBy('urutan')
                ->get()
                ->map(function ($ind) {
                    $realisasi = 0;
                    if (Table::exists('tendik_aktivitas')) {
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
            $bidangMultiPeriode[$dKey] = KepegawaianStatsService::buildMultiPeriodeData($totalTendik ?: 0, $dbBidang);

            if (Table::exists('tendik_indikator_kinerja')) {
                $bidangIndikatorList[$dKey] = TendikIndikatorKinerja::where('bidang', $dbBidang)
                    ->where('is_active', true)
                    ->orderBy('urutan')
                    ->get()
                    ->map(function ($ind) use ($dbBidang) {
                        $realisasi = 0;
                        if (Table::exists('tendik_aktivitas')) {
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
                                $realisasi = Table::exists('persuratan') ? DB::table('persuratan')->where('jenis_surat', 'masuk')->count() : 0;
                            } elseif (str_contains($sasaranLower, 'surat keluar')) {
                                $realisasi = Table::exists('persuratan') ? DB::table('persuratan')->where('jenis_surat', 'keluar')->count() : 0;
                            } elseif (str_contains($sasaranLower, 'kearsipan') || str_contains($sasaranLower, 'sk dinas') || str_contains($sasaranLower, 'surat tugas')) {
                                $realisasi = (Table::exists('persuratan') ? DB::table('persuratan')->whereIn('jenis_surat', ['keputusan', 'tugas'])->count() : 0) + (Table::exists('gtk_spt') ? DB::table('gtk_spt')->count() : 0);
                            } elseif (str_contains($sasaranLower, 'keterangan aktif') || str_contains($sasaranLower, 'legalisir')) {
                                $realisasi = Table::exists('surat_keterangan_pd') ? DB::table('surat_keterangan_pd')->count() : 0;
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

        // Pecah hasil agregasi dashboard Sarpras menjadi variabel view
        // agar partial `dashboard.tendik.section-sarpras` tetap ringkas.
        $sarprasRingkasan       = $sarprasDashboard['ringkasan'];
        $sarprasDaftar          = $sarprasDashboard['daftar'];
        $sarprasLintasBidang    = $sarprasDashboard['lintasBidang'];
        $sarprasPenanggungJawab = $sarprasDashboard['penanggungJawab'];
        $sarprasPeringatan      = $sarprasDashboard['peringatan'];

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
            'sarprasDashboard',
            'sarprasRingkasan',
            'sarprasDaftar',
            'sarprasLintasBidang',
            'sarprasPenanggungJawab',
            'sarprasPeringatan',
            'laboranCharts',
            'perpustakaanCharts',
            'keamananCharts',
            'penjagaCharts',
            'piketCharts',
            'teknisiCharts',
            'kepalaTasCharts',
            'bidangMultiPeriode',
            'bidangIndikatorList',
            'teknisiStats',
            'keamananStats',
            'penjagaStats',
            'perpusStats',
            'laboranStats'
        );
    }
}
