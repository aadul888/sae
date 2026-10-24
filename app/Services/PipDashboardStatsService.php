<?php

namespace App\Services;

use App\Models\PipKuotaRombel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Service khusus kalkulasi dan agregasi statistik tab Dashboard Overview PIP.
 * ponytail: static-calculator; upgrade ke Redis cached snapshot jika pemanggilan dashboard melebihi 100 req/sec.
 */
class PipDashboardStatsService
{
    /**
     * Agregasi metrik untuk tab Dashboard Overview PIP.
     */
    public static function buildDashboardData(
        bool $isAdmin,
        $activeRombel,
        string $activeTahun,
        $rombelList,
        bool $hasUsulanTable,
        bool $hasKuotaTable,
        bool $hasRiwayatTable
    ): array {
        $hasPdTable = Schema::hasTable('peserta_didik');
        $hasJenisSk = $hasRiwayatTable && Schema::hasColumn('pip_riwayat', 'jenis_sk');

        // Siswa aktif
        $totalSiswaAktif = 0;
        $totalSiswaL = 0;
        $totalSiswaP = 0;
        $siswaPerTingkat = [];

        if ($hasPdTable) {
            $pdScope = DB::table('peserta_didik as pd')
                ->join('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->where('rb.jenis_rombel', '1');

            if (!$isAdmin && $activeRombel) {
                $pdScope->where('pd.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
            }

            $totalSiswaAktif = (clone $pdScope)->count();
            $totalSiswaL     = (clone $pdScope)->where('pd.jenis_kelamin', 'L')->count();
            $totalSiswaP     = (clone $pdScope)->where('pd.jenis_kelamin', 'P')->count();

            if ($isAdmin) {
                $siswaPerTingkat = DB::table('peserta_didik as pd')
                    ->join('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                    ->where('rb.jenis_rombel', '1')
                    ->select('rb.tingkat_pendidikan_id', DB::raw('count(pd.peserta_didik_id) as n'))
                    ->groupBy('rb.tingkat_pendidikan_id')
                    ->orderBy('rb.tingkat_pendidikan_id')
                    ->pluck('n', 'tingkat_pendidikan_id')
                    ->all();
            }
        }

        // Usulan
        $totalUsulan   = 0;
        $belumDiajukan = 0;
        $sudahDiajukan = 0;
        $ditolak       = 0;
        $usulanPerRombel = [];
        $usulanPerTingkat = [];
        $alasanDistribusi = [];
        $usulanTerbaru = collect();
        $totalUsulanDiubah = 0;

        if ($hasUsulanTable) {
            $uScope = DB::table('pip_usulan')->where('tahun', $activeTahun);
            if (!$isAdmin && $activeRombel) {
                $uScope->where('rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
            }

            $totalUsulan   = (clone $uScope)->count();
            $belumDiajukan = (clone $uScope)->where('status_verifikasi', 'belum_diajukan')->count();
            $sudahDiajukan = (clone $uScope)->where('status_verifikasi', 'sudah_diajukan')->count();
            $ditolak       = (clone $uScope)->where('status_verifikasi', 'ditolak')->count();
            $totalUsulanDiubah = (clone $uScope)->whereNotNull('diubah_at')->count();

            $recentQuery = DB::table('pip_usulan as u')
                ->join('peserta_didik as pd', 'u.peserta_didik_id', '=', 'pd.peserta_didik_id')
                ->leftJoin('rombongan_belajar as rb', 'u.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
                ->where('u.tahun', $activeTahun)
                ->select(
                    'u.*',
                    'pd.nama as nama_pd',
                    'pd.nisn',
                    'rb.nama as nama_rombel',
                    'pdm.foto_path'
                );
            if (!$isAdmin && $activeRombel) {
                $recentQuery->where('u.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
            }
            $usulanTerbaru = $recentQuery->orderByDesc('u.id')->limit(6)->get();
            $usulanTerbaru->transform(function ($item) {
                $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
                $item->alasan_list = is_string($item->alasan_usulan) ? (json_decode($item->alasan_usulan, true) ?: []) : ($item->alasan_usulan ?: []);
                $item->riwayat_perubahan_list = is_string($item->riwayat_perubahan) ? (json_decode($item->riwayat_perubahan, true) ?: []) : ($item->riwayat_perubahan ?: []);
                return $item;
            });

            $alasanScope = DB::table('pip_usulan')->where('tahun', $activeTahun)->whereNotNull('alasan_usulan');
            if (!$isAdmin && $activeRombel) {
                $alasanScope->where('rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
            }
            $allAlasan = $alasanScope->pluck('alasan_usulan')->all();
            foreach ($allAlasan as $raw) {
                $list = is_string($raw) ? (json_decode($raw, true) ?: []) : (array) $raw;
                foreach ($list as $a) {
                    $a = trim((string) $a);
                    if ($a !== '') {
                        $alasanDistribusi[$a] = ($alasanDistribusi[$a] ?? 0) + 1;
                    }
                }
            }
            arsort($alasanDistribusi);

            if ($isAdmin) {
                $usulanPerRombel = DB::table('pip_usulan as u')
                    ->join('rombongan_belajar as rb', 'u.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                    ->where('u.tahun', $activeTahun)
                    ->select('rb.nama as nama_rombel', DB::raw('count(u.id) as n'), 'u.rombongan_belajar_id')
                    ->groupBy('u.rombongan_belajar_id', 'rb.nama')
                    ->orderByDesc('n')
                    ->limit(15)
                    ->get()
                    ->all();

                $usulanPerTingkat = DB::table('pip_usulan as u')
                    ->join('rombongan_belajar as rb', 'u.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                    ->where('u.tahun', $activeTahun)
                    ->select('rb.tingkat_pendidikan_id', DB::raw('count(u.id) as n'))
                    ->groupBy('rb.tingkat_pendidikan_id')
                    ->orderBy('rb.tingkat_pendidikan_id')
                    ->pluck('n', 'tingkat_pendidikan_id')
                    ->all();
            }
        }

        // Kuota
        $paguSekolah   = $rombelList->count() * 5;
        $totalKuota    = $paguSekolah;
        $sisaKuota     = max(0, $paguSekolah - $totalUsulan);
        $pctTerpakai   = $paguSekolah > 0 ? min(100, round($totalUsulan / $paguSekolah * 100, 1)) : 0;

        if ($hasKuotaTable) {
            $stored = PipKuotaRombel::getTargetTotalSekolah($activeTahun);
            if ($stored !== null) {
                $paguSekolah = $stored;
                $totalKuota  = $stored;
            } else {
                $sumKuota = DB::table('pip_kuota_rombel')
                    ->where('tahun', $activeTahun)
                    ->where('rombongan_belajar_id', '!=', '__TOTAL__')
                    ->sum('kuota');
                if ($sumKuota > 0) {
                    $paguSekolah = (int) $sumKuota;
                    $totalKuota  = (int) $sumKuota;
                }
            }
            $sisaKuota   = max(0, $paguSekolah - $totalUsulan);
            $pctTerpakai = $paguSekolah > 0 ? min(100, round($totalUsulan / $paguSekolah * 100, 1)) : 0;
        }

        $kuotaKelasBinaan   = 5;
        $terpakaiKelasBinaan = 0;
        if ($activeRombel) {
            $kuotaKelasBinaan = PipKuotaRombel::getKuotaForRombel($activeRombel->rombongan_belajar_id, $activeTahun);
            if ($hasUsulanTable) {
                $terpakaiKelasBinaan = DB::table('pip_usulan')
                    ->where('rombongan_belajar_id', $activeRombel->rombongan_belajar_id)
                    ->where('tahun', $activeTahun)
                    ->count();
            }
        }

        // Riwayat
        $totalRiwayatAktif    = 0;
        $totalRiwayatPemberian = 0;
        $totalRiwayatNominasi  = 0;
        $totalNominalCair      = 0;
        $riwayatPerTahun       = [];

        if ($hasRiwayatTable) {
            $rBase = DB::table('pip_riwayat as r')
                ->join('peserta_didik as pd', function ($j) {
                    $j->on('r.peserta_didik_id', '=', 'pd.peserta_didik_id')
                      ->orWhere(function ($q) {
                          $q->whereNull('r.peserta_didik_id')
                            ->whereNotNull('r.nisn')->where('r.nisn', '!=', '')
                            ->whereColumn('r.nisn', 'pd.nisn');
                      });
                })
                ->join('rombongan_belajar as rb', function ($j) {
                    $j->on('pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                      ->where('rb.jenis_rombel', '=', '1');
                });

            if (!$isAdmin && $activeRombel) {
                $rBase->where('pd.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
            }

            $totalRiwayatAktif = (clone $rBase)->count();
            $totalRiwayatPemberian = $hasJenisSk ? (clone $rBase)->where('r.jenis_sk', 'pemberian')->count() : $totalRiwayatAktif;
            $totalRiwayatNominasi  = $hasJenisSk ? (clone $rBase)->where('r.jenis_sk', 'nominasi')->count()  : 0;
            $totalNominalCair = (float) (clone $rBase)->sum('r.nominal');

            if ($isAdmin) {
                $riwayatPerTahun = DB::table('pip_riwayat')
                    ->whereNotNull('tahun')
                    ->where('tahun', '!=', '')
                    ->select('tahun', DB::raw('count(*) as n'), DB::raw('sum(nominal) as total_nominal'))
                    ->groupBy('tahun')
                    ->orderBy('tahun')
                    ->get()
                    ->all();
            }
        }

        // Wali Kelas: status penerima siswa kelas binaan
        $waliSudahPip = 0;
        $waliBelumPip = 0;
        if (!$isAdmin && $activeRombel && $hasPdTable) {
            $waliPdScope = DB::table('peserta_didik')
                ->where('rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
            $totalWaliPd = (clone $waliPdScope)->count();
            if ($hasRiwayatTable) {
                $waliSudahPip = (clone $waliPdScope)
                    ->whereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('pip_riwayat as r')
                            ->where(function ($match) {
                                $match->whereColumn('r.peserta_didik_id', 'peserta_didik.peserta_didik_id')
                                      ->orWhere(function ($orMatch) {
                                          $orMatch->whereNull('r.peserta_didik_id')
                                                  ->whereNotNull('r.nisn')
                                                  ->where('r.nisn', '!=', '')
                                                  ->whereColumn('r.nisn', 'peserta_didik.nisn');
                                      });
                            });
                    })
                    ->count();
            }
            $waliBelumPip = max(0, $totalWaliPd - $waliSudahPip);
        }

        // Rombel summary (admin)
        $rombelSummary = collect();
        $rombelPenuhCount = 0;
        $rombelBelumUsulCount = 0;
        $rombelTersediaCount = 0;

        if ($isAdmin && $hasUsulanTable) {
            $existingKuota = $hasKuotaTable
                ? DB::table('pip_kuota_rombel')->where('tahun', $activeTahun)->where('rombongan_belajar_id', '!=', '__TOTAL__')->pluck('kuota', 'rombongan_belajar_id')->all()
                : [];
            $existingCount = DB::table('pip_usulan')->where('tahun', $activeTahun)->select('rombongan_belajar_id', DB::raw('COUNT(*) as n'))->groupBy('rombongan_belajar_id')->pluck('n', 'rombongan_belajar_id')->all();
            $studentCounts = $hasPdTable
                ? DB::table('peserta_didik')->select('rombongan_belajar_id', DB::raw('count(*) as n'))->groupBy('rombongan_belajar_id')->pluck('n', 'rombongan_belajar_id')->all()
                : [];

            $rombelSummary = $rombelList->map(function ($r) use ($existingKuota, $existingCount, $studentCounts) {
                $kuota  = (int) ($existingKuota[$r->rombongan_belajar_id] ?? 5);
                $used   = (int) ($existingCount[$r->rombongan_belajar_id] ?? 0);
                $siswa  = (int) ($studentCounts[$r->rombongan_belajar_id] ?? 0);
                $isAwal = in_array((string) ($r->tingkat_pendidikan_id ?? ''), ['10', '10.0', 'X'], true) || str_starts_with(strtoupper($r->nama), 'X ');

                $r->kuota                  = $kuota;
                $r->terpakai               = $used;
                $r->sisa                   = max(0, $kuota - $used);
                $r->jumlah_siswa           = $siswa;
                $r->pct                    = $kuota > 0 ? min(100, round($used / $kuota * 100)) : 0;
                $r->kategori_tingkat       = $isAwal ? 'kelas_awal' : 'kelas_berjalan';
                $r->kategori_tingkat_label = $isAwal ? 'Kelas Awal (X)' : 'Kelas Berjalan (' . ($r->tingkat_pendidikan_id_str ?: 'XI/XII') . ')';
                return $r;
            })->sortByDesc('terpakai')->values();

            $rombelPenuhCount = $rombelSummary->where('sisa', '<=', 0)->where('terpakai', '>', 0)->count();
            $rombelBelumUsulCount = $rombelSummary->where('terpakai', 0)->count();
            $rombelTersediaCount = $rombelSummary->where('sisa', '>', 0)->count();
            $rombelAwalCount = $rombelSummary->where('kategori_tingkat', 'kelas_awal')->count();
            $rombelBerjalanCount = $rombelSummary->where('kategori_tingkat', 'kelas_berjalan')->count();
        }

        return [
            'total_siswa_aktif'       => $totalSiswaAktif,
            'total_siswa_l'           => $totalSiswaL,
            'total_siswa_p'           => $totalSiswaP,
            'siswa_per_tingkat'       => $siswaPerTingkat,
            'wali_sudah_pip'          => $waliSudahPip,
            'wali_belum_pip'          => $waliBelumPip,
            'total_usulan'            => $totalUsulan,
            'belum_diajukan'          => $belumDiajukan,
            'sudah_diajukan'          => $sudahDiajukan,
            'ditolak'                 => $ditolak,
            'total_usulan_diubah'     => $totalUsulanDiubah,
            'usulan_per_rombel'       => $usulanPerRombel,
            'usulan_per_tingkat'      => $usulanPerTingkat,
            'alasan_distribusi'       => $alasanDistribusi,
            'usulan_terbaru'          => $usulanTerbaru,
            'pagu_sekolah'            => $paguSekolah,
            'total_kuota'             => $totalKuota,
            'sisa_kuota'              => $sisaKuota,
            'pct_terpakai'            => $pctTerpakai,
            'kuota_kelas'             => $kuotaKelasBinaan,
            'terpakai_kelas'          => $terpakaiKelasBinaan,
            'sisa_kuota_kelas'        => max(0, $kuotaKelasBinaan - $terpakaiKelasBinaan),
            'total_riwayat_aktif'     => $totalRiwayatAktif,
            'total_pemberian'         => $totalRiwayatPemberian,
            'total_nominasi'          => $totalRiwayatNominasi,
            'total_nominal_cair'      => $totalNominalCair,
            'riwayat_per_tahun'       => $riwayatPerTahun,
            'rombel_summary'          => $rombelSummary,
            'rombel_penuh_count'      => $rombelPenuhCount,
            'rombel_belum_usul_count' => $rombelBelumUsulCount,
            'rombel_tersedia_count'   => $rombelTersediaCount,
            'rombel_awal_count'       => $rombelAwalCount ?? 0,
            'rombel_berjalan_count'   => $rombelBerjalanCount ?? 0,
        ];
    }
}
