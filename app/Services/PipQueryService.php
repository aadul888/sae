<?php

namespace App\Services;

use App\Models\PipKuotaRombel;
use App\Models\PipPengaturan;
use App\Models\PipPenggunaanDana;
use App\Models\PipUsulan;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use App\Support\Table;
/**
 * Service khusus query dan agregasi metrik data modul PIP.
 * ponytail: static-query-builder; upgrade ke Cached Repository saat volume data sekolah melebihi 20.000 siswa.
 */
class PipQueryService
{
    /**
     * Kumpulkan seluruh data view untuk halaman index PIP berdasarkan tab aktif.
     */
    public static function getIndexData(array $ctx, Request $request): array
    {
        $user = $ctx['user'];
        $role = $ctx['role'];
        $isAdmin = $ctx['isAdmin'];
        $rombelList = $ctx['rombelList'];
        $activeRombel = $ctx['activeRombel'];
        $activeTahun = $ctx['activeTahun'];

        $tab = $request->get('tab', 'dashboard');
        if ($tab === 'riwayat_alumni') {
            $tab = 'riwayat';
        }
        if (!$isAdmin && in_array($tab, ['kuota', 'pengaturan', 'verifikasi'], true)) {
            $tab = 'dashboard';
        }
        if (!in_array($tab, ['dashboard', 'usulan', 'kuota', 'riwayat', 'pengaturan', 'verifikasi'], true)) {
            $tab = 'dashboard';
        }

        $pipPengaturan = PipPengaturan::getSettings($activeTahun);
        $pipStatusInfo = PipPengaturan::getStatusInfo($activeTahun);
        $isWaliLocked  = !$isAdmin && $pipStatusInfo['is_closed'];

        $q = trim((string) $request->get('q', ''));
        $statusVerif = trim((string) $request->get('status_verifikasi', ''));
        $faseFilter = trim((string) $request->get('fase', ''));
        $statusPerubahanFilter = trim((string) $request->get('status_perubahan', ''));
        $jenisSkFilter = trim((string) $request->get('jenis_sk', ''));
        $statusPipFilter = trim((string) $request->get('status_pip', 'penerima'));
        if (!in_array($statusPipFilter, ['penerima', 'belum', 'semua'], true)) {
            $statusPipFilter = 'penerima';
        }
        $perPageVal = $request->get('perPage', '15');
        $perPage = in_array((int) $perPageVal, [10, 15, 25, 50, 100], true) ? (int) $perPageVal : 15;

        $hasUsulanTable = Table::exists('pip_usulan');
        $hasKuotaTable = Table::exists('pip_kuota_rombel');
        $hasRiwayatTable = Table::exists('pip_riwayat');

        $tahunSkFilter = trim((string) $request->get('tahun_sk', ''));
        if (!preg_match('/^\d{4}$/', $tahunSkFilter)) {
            $tahunSkFilter = '';
        }

        // Opsi filter tahap memakai nilai tahap_id apa adanya
        $riwayatTahapOpsi = [];
        $riwayatTahunSkList = [];
        if ($hasRiwayatTable) {
            $rawTahaps = DB::table('pip_riwayat')
                ->select(['tahap_id', 'tahap'])
                ->distinct()
                ->get();

            $riwayatTahapOpsi = $rawTahaps->map(function ($row) {
                $id = trim((string) ($row->tahap_id ?: ''));
                if ($id === '') {
                    if (preg_match('/(?:tahap\s*)?(\d+)/i', (string) $row->tahap, $m)) {
                        $id = $m[1];
                    }
                }
                if (str_contains($id, '-')) {
                    $id = trim(explode('-', $id)[0]);
                }
                $cleanNumber = preg_replace('/^tahap\s*/i', '', $id);
                return $cleanNumber !== '' ? $cleanNumber : null;
            })
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL)
            ->values()
            ->map(fn($num) => [
                'id'    => (string) $num,
                'label' => 'Tahap ' . $num,
            ])
            ->all();

            $riwayatTahunSkList = DB::table('pip_riwayat')
                ->whereNotNull('tanggal_sk')
                ->where('tanggal_sk', '!=', '')
                ->pluck('tanggal_sk')
                ->map(fn($v) => preg_match('/(\d{4})/', (string) $v, $m) ? $m[1] : null)
                ->filter()
                ->unique()
                ->sortDesc()
                ->values()
                ->all();
        }

        // 0. Data Tab: Dashboard Overview PIP
        $dashboardData = null;
        if ($tab === 'dashboard') {
            $dashboardData = self::buildDashboardData($isAdmin, $activeRombel, $activeTahun, $rombelList, $hasUsulanTable, $hasKuotaTable, $hasRiwayatTable);
        }

        // 1. Data Tab: Usulan PIP
        $usulanList = null;
        if ($hasUsulanTable) {
            $usulanQuery = DB::table('pip_usulan as u')
                ->join('peserta_didik as pd', 'u.peserta_didik_id', '=', 'pd.peserta_didik_id')
                ->leftJoin('rombongan_belajar as rb', 'u.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->leftJoin('peserta_didik_meta as pdm', 'u.peserta_didik_id', '=', 'pdm.peserta_didik_id')
                ->select(
                    'u.*',
                    'pd.nama as nama_pd',
                    'pd.nisn',
                    'pd.nipd',
                    'pd.jenis_kelamin',
                    'rb.nama as nama_rombel',
                    'pdm.foto_path'
                )
                ->where('u.tahun', $activeTahun);

            if (!$isAdmin && $activeRombel) {
                $usulanQuery->where('u.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
            } elseif ($isAdmin && $activeRombel) {
                $usulanQuery->where('u.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
            }

            if ($statusVerif !== '' && isset(PipUsulan::STATUS_VERIFIKASI[$statusVerif])) {
                $usulanQuery->where('u.status_verifikasi', $statusVerif);
            }

            if ($faseFilter !== '' && in_array($faseFilter, ['fase_1', 'fase_2'], true)) {
                $usulanQuery->where('u.fase', $faseFilter);
            }

            if ($statusPerubahanFilter === 'diubah') {
                $usulanQuery->whereNotNull('u.diubah_at');
            } elseif ($statusPerubahanFilter === 'asli') {
                $usulanQuery->whereNull('u.diubah_at');
            }

            if ($q !== '') {
                $usulanQuery->where(function ($sub) use ($q) {
                    $sub->where('pd.nama', 'LIKE', "%{$q}%")
                        ->orWhere('pd.nisn', 'LIKE', "%{$q}%")
                        ->orWhere('pd.nipd', 'LIKE', "%{$q}%")
                        ->orWhere('u.keterangan', 'LIKE', "%{$q}%")
                        ->orWhere('u.alasan_usulan', 'LIKE', "%{$q}%")
                        ->orWhere('u.perubahan_terakhir', 'LIKE', "%{$q}%")
                        ->orWhere('u.diubah_oleh', 'LIKE', "%{$q}%");
                });
            }

            $usulanList = $usulanQuery
                ->orderBy('u.rombongan_belajar_id', 'asc')
                ->orderBy('u.prioritas', 'asc')
                ->orderBy('pd.nama', 'asc')
                ->paginate($perPage, ['*'], 'usulan_page')
                ->withQueryString();

            $usulanList->getCollection()->transform(function ($item) {
                $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
                $item->alasan_list = is_string($item->alasan_usulan) ? (json_decode($item->alasan_usulan, true) ?: []) : ($item->alasan_usulan ?: []);
                $item->riwayat_perubahan_list = is_string($item->riwayat_perubahan) ? (json_decode($item->riwayat_perubahan, true) ?: []) : ($item->riwayat_perubahan ?: []);
                return $item;
            });
        } else {
            $usulanList = new LengthAwarePaginator(
                collect(),
                0,
                $perPage,
                1,
                ['path' => $request->url(), 'pageName' => 'usulan_page', 'query' => $request->query()]
            );
        }

        // Statistik Usulan
        $totalUsulan = 0;
        $totalBelumDiajukan = 0;
        $totalSudahDiajukan = 0;
        $totalDitolak = 0;
        $totalUsulanDiubah = 0;

        if ($hasUsulanTable) {
            $statScope = DB::table('pip_usulan')->where('tahun', $activeTahun);
            if (!$isAdmin && $activeRombel) {
                $statScope->where('rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
            } elseif ($isAdmin && $activeRombel) {
                $statScope->where('rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
            }

            $totalUsulan = (clone $statScope)->count();
            $totalBelumDiajukan = (clone $statScope)->where('status_verifikasi', 'belum_diajukan')->count();
            $totalSudahDiajukan = (clone $statScope)->where('status_verifikasi', 'sudah_diajukan')->count();
            $totalDitolak = (clone $statScope)->where('status_verifikasi', 'ditolak')->count();
            $totalUsulanDiubah = (clone $statScope)->whereNotNull('diubah_at')->count();
        }

        // Kuota info rombel aktif / sekolah
        $kuotaKelas = 5;
        $terpakaiKelas = 0;
        $sisaKuotaKelas = 5;
        if ($activeRombel) {
            $kuotaKelas = PipKuotaRombel::getKuotaForRombel($activeRombel->rombongan_belajar_id, $activeTahun);
            if ($hasUsulanTable) {
                $terpakaiKelas = DB::table('pip_usulan')
                    ->where('rombongan_belajar_id', $activeRombel->rombongan_belajar_id)
                    ->where('tahun', $activeTahun)
                    ->count();
            }
            $sisaKuotaKelas = max(0, $kuotaKelas - $terpakaiKelas);
        }

        // Pagu total sekolah
        $totalPaguSekolah = $rombelList->count() * 5;
        if ($hasKuotaTable) {
            $targetPaguStored = PipKuotaRombel::getTargetTotalSekolah($activeTahun);
            $totalPaguSekolah = $targetPaguStored ?? (int) DB::table('pip_kuota_rombel')
                ->where('tahun', $activeTahun)
                ->where('rombongan_belajar_id', '!=', '__TOTAL__')
                ->sum('kuota');
            if ($totalPaguSekolah === 0 && $rombelList->isNotEmpty()) {
                $totalPaguSekolah = $rombelList->count() * 5;
            }
        }

        $stats = [
            'total_usulan'        => $totalUsulan,
            'belum_diajukan'      => $totalBelumDiajukan,
            'sudah_diajukan'      => $totalSudahDiajukan,
            'ditolak'             => $totalDitolak,
            'total_usulan_diubah' => $totalUsulanDiubah,
            'kuota_kelas'         => $kuotaKelas,
            'terpakai_kelas'      => $terpakaiKelas,
            'sisa_kuota_kelas'    => $sisaKuotaKelas,
            'total_pagu_sekolah'  => $totalPaguSekolah,
        ];

        // 2. Data Tab: Kuota per Rombel (Admin Only)
        $kuotaList = null;
        $kuotaStats = [
            'target_total'     => $totalPaguSekolah,
            'total_teralokasi' => 0,
            'selisih'          => 0,
            'status_selisih'   => 'pas',
            'selisih_label'    => 'Pas',
            'selisih_text'     => 'Alokasi kuota sesuai 100% target sekolah',
            'total_terpakai'   => 0,
            'total_sisa'       => 0,
            'total_rombel'     => $rombelList->count(),
            'rombel_penuh'     => 0,
            'rombel_tersedia'  => $rombelList->count(),
        ];

        if ($isAdmin && $tab === 'kuota') {
            $allRombelData = $rombelList;
            $existingKuota = [];
            $existingCount = [];

            if ($hasKuotaTable) {
                $existingKuota = DB::table('pip_kuota_rombel')
                    ->where('tahun', $activeTahun)
                    ->where('rombongan_belajar_id', '!=', '__TOTAL__')
                    ->pluck('kuota', 'rombongan_belajar_id')
                    ->all();
            }

            if ($hasUsulanTable) {
                $existingCount = DB::table('pip_usulan')
                    ->where('tahun', $activeTahun)
                    ->select('rombongan_belajar_id', DB::raw('COUNT(*) as total'))
                    ->groupBy('rombongan_belajar_id')
                    ->pluck('total', 'rombongan_belajar_id')
                    ->all();
            }

            $studentCounts = [];
            $totalSiswaSekolah = 0;
            if (Table::exists('peserta_didik')) {
                $studentCounts = DB::table('peserta_didik')
                    ->select('rombongan_belajar_id', DB::raw('count(*) as total'))
                    ->groupBy('rombongan_belajar_id')
                    ->pluck('total', 'rombongan_belajar_id')
                    ->all();
                foreach ($allRombelData as $r) {
                    $totalSiswaSekolah += (int) ($studentCounts[$r->rombongan_belajar_id] ?? 0);
                }
            }

            $mapped = $allRombelData->map(function ($r) use ($existingKuota, $existingCount, $studentCounts, $totalSiswaSekolah) {
                $k = (int) ($existingKuota[$r->rombongan_belajar_id] ?? 5);
                $used = (int) ($existingCount[$r->rombongan_belajar_id] ?? 0);
                $jmlSiswa = (int) ($studentCounts[$r->rombongan_belajar_id] ?? 0);
                $persen = $totalSiswaSekolah > 0 ? round(($jmlSiswa / $totalSiswaSekolah) * 100, 1) : 0;

                $r->kuota = $k;
                $r->terpakai = $used;
                $r->sisa = max(0, $k - $used);
                $r->jumlah_siswa = $jmlSiswa;
                $r->persen_siswa = $persen;
                return $r;
            });

            $totalTeralokasi = (int) $mapped->sum('kuota');
            $targetSekolah = ($hasKuotaTable && isset($targetPaguStored)) ? $targetPaguStored : $totalTeralokasi;
            $selisih = $totalTeralokasi - $targetSekolah;

            $statusSelisih = 'pas';
            $selisihLabel = '0 (Pas)';
            $selisihText = 'Alokasi kuota pas sesuai target pagu sekolah (100%).';

            if ($selisih > 0) {
                $statusSelisih = 'kelebihan';
                $selisihLabel = '+' . $selisih . ' Siswa';
                $selisihText = "Kelebihan {$selisih} siswa dari target pagu sekolah ({$targetSekolah} siswa).";
            } elseif ($selisih < 0) {
                $statusSelisih = 'kurang';
                $selisihLabel = $selisih . ' Siswa';
                $selisihText = 'Kurang ' . abs($selisih) . " siswa untuk memenuhi target pagu sekolah ({$targetSekolah} siswa).";
            }

            $kuotaStats = [
                'target_total'     => $targetSekolah,
                'total_teralokasi' => $totalTeralokasi,
                'selisih'          => $selisih,
                'status_selisih'   => $statusSelisih,
                'selisih_label'    => $selisihLabel,
                'selisih_text'     => $selisihText,
                'total_terpakai'   => (int) $mapped->sum('terpakai'),
                'total_sisa'       => max(0, $targetSekolah - (int) $mapped->sum('terpakai')),
                'total_rombel'     => $mapped->count(),
                'rombel_penuh'     => $mapped->where('sisa', '<=', 0)->count(),
                'rombel_tersedia'  => $mapped->where('sisa', '>', 0)->count(),
            ];

            if ($q !== '') {
                $filtered = $mapped->filter(function ($r) use ($q) {
                    return stripos($r->nama, $q) !== false || stripos($r->tingkat_pendidikan_id_str, $q) !== false;
                })->values();
            } else {
                $filtered = $mapped->values();
            }

            $currentPage = max(1, (int) $request->get('kuota_page', 1));
            $totalRows = $filtered->count();
            $slice = $filtered->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $kuotaList = new LengthAwarePaginator(
                $slice,
                $totalRows,
                $perPage,
                $currentPage,
                [
                    'path'     => $request->url(),
                    'pageName' => 'kuota_page',
                    'query'    => $request->query(),
                ]
            );
        }

        // 3. Data Tab: Riwayat Penerima PIP (Siswa Aktif)
        $riwayatList = null;
        $riwayatTahapList = [];
        $riwayatStats = [
            'total_siswa'     => 0,
            'total_pemberian' => 0,
            'total_nominasi'  => 0,
            'total_nominal'   => 0,
        ];

        // 4. Data Tab: Riwayat Penerima PIP (Kelas Lain & Alumni - Admin Only)
        $riwayatAlumniList = null;
        $riwayatAlumniTahapList = [];
        $riwayatAlumniStats = [
            'total_siswa'     => 0,
            'total_pemberian' => 0,
            'total_nominasi'  => 0,
            'total_nominal'   => 0,
        ];
        $alumniStatusList = [];
        $statusKeluarFilter = trim((string) $request->get('status_keluar', ''));

        if ($tab === 'riwayat') {
            if ($hasRiwayatTable) {
                $activeStudentsScope = DB::table('peserta_didik as pd')
                    ->join('rombongan_belajar as rb', function ($j) {
                        $j->on('pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                          ->where('rb.jenis_rombel', '=', '1');
                    });

                if (!$isAdmin && $activeRombel) {
                    $activeStudentsScope->where('pd.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
                } elseif ($isAdmin && $activeRombel) {
                    $activeStudentsScope->where('pd.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
                }

                $totalSiswaAktif = (clone $activeStudentsScope)->count();

                $penerimaUnikCount = (clone $activeStudentsScope)
                    ->whereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('pip_riwayat as r')
                            ->where(function ($match) {
                                $match->whereColumn('r.peserta_didik_id', 'pd.peserta_didik_id')
                                      ->orWhere(function ($orMatch) {
                                          $orMatch->whereNull('r.peserta_didik_id')
                                                  ->whereNotNull('r.nisn')
                                                  ->where('r.nisn', '!=', '')
                                                  ->whereColumn('r.nisn', 'pd.nisn');
                                      });
                            });
                    })
                    ->count();

                $totalBelumMenerima = max(0, $totalSiswaAktif - $penerimaUnikCount);

                $baseRiwayatScope = DB::table('pip_riwayat as r')
                    ->leftJoin('peserta_didik as pd', function ($j) {
                        $j->on('r.peserta_didik_id', '=', 'pd.peserta_didik_id')
                          ->orWhere(function ($q) {
                              $q->whereNull('r.peserta_didik_id')
                                ->whereNotNull('r.nisn')
                                ->where('r.nisn', '!=', '')
                                ->whereColumn('r.nisn', 'pd.nisn');
                          });
                    })
                    ->leftJoin('rombongan_belajar as rb', function ($j) {
                        $j->on('pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                          ->where('rb.jenis_rombel', '=', '1');
                    });

                $hasPdta = Table::exists('peserta_didik_tidak_aktif');
                if ($hasPdta) {
                    $baseRiwayatScope->leftJoin('peserta_didik_tidak_aktif as pdta', function ($j) {
                        $j->on('r.peserta_didik_id', '=', 'pdta.peserta_didik_id')
                          ->orWhere(function ($q) {
                              $q->whereNull('r.peserta_didik_id')
                                ->whereNotNull('r.nisn')
                                ->where('r.nisn', '!=', '')
                                ->whereColumn('r.nisn', 'pdta.nisn');
                          });
                    });
                }

                if (!$isAdmin && $activeRombel) {
                    $baseRiwayatScope->where('pd.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
                } elseif ($isAdmin && $activeRombel) {
                    $baseRiwayatScope->where('pd.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
                }

                $statusSiswaFilter = trim((string) $request->get('status_siswa', ''));
                if ($statusSiswaFilter === 'aktif') {
                    $baseRiwayatScope->whereNotNull('rb.rombongan_belajar_id');
                } elseif ($statusSiswaFilter === 'alumni') {
                    $baseRiwayatScope->whereNull('rb.rombongan_belajar_id');
                }

                $hasJenisSk = Schema::hasColumn('pip_riwayat', 'jenis_sk');
                $tahapFilter = trim((string) $request->get('tahap_id', ''));
                if ($tahapFilter !== '') {
                    $baseRiwayatScope->where(function ($sub) use ($tahapFilter) {
                        $sub->where('r.tahap_id', $tahapFilter)
                            ->orWhere('r.tahap', $tahapFilter)
                            ->orWhere('r.tahap', 'LIKE', "%Tahap {$tahapFilter}%")
                            ->orWhere('r.tahap', 'LIKE', "{$tahapFilter} - %");
                    });
                }
                if ($tahunSkFilter !== '') {
                    $baseRiwayatScope->where(function ($s) use ($tahunSkFilter) {
                        $s->where('r.tanggal_sk', 'like', $tahunSkFilter . '%')
                          ->orWhere('r.tanggal_sk', 'like', '%' . $tahunSkFilter);
                    });
                }
                if ($hasJenisSk && $jenisSkFilter !== '' && in_array($jenisSkFilter, ['pemberian', 'nominasi'], true)) {
                    $baseRiwayatScope->where('r.jenis_sk', $jenisSkFilter);
                }
                if ($q !== '') {
                    $baseRiwayatScope->where(function ($sub) use ($q, $hasPdta) {
                        $sub->where('r.nama_pd', 'LIKE', "%{$q}%")
                            ->orWhere('r.nisn', 'LIKE', "%{$q}%")
                            ->orWhere('r.nomor_sk', 'LIKE', "%{$q}%")
                            ->orWhere('r.rombel', 'LIKE', "%{$q}%")
                            ->orWhere('pd.nama', 'LIKE', "%{$q}%")
                            ->orWhere('pd.nama_rombel', 'LIKE', "%{$q}%")
                            ->orWhere('rb.nama', 'LIKE', "%{$q}%")
                            ->orWhere('r.nama_ibu_kandung', 'LIKE', "%{$q}%");
                        if ($hasPdta) {
                            $sub->orWhere('pdta.nama', 'LIKE', "%{$q}%")
                                ->orWhere('pdta.nisn', 'LIKE', "%{$q}%")
                                ->orWhere('pdta.status_keluar', 'LIKE', "%{$q}%")
                                ->orWhere('pdta.nama_rombel_terakhir', 'LIKE', "%{$q}%");
                        }
                    });
                }

                $totalPemberian = $hasJenisSk
                    ? (clone $baseRiwayatScope)->where('r.jenis_sk', 'pemberian')->count()
                    : (clone $baseRiwayatScope)->count();
                $totalNominasi = $hasJenisSk
                    ? (clone $baseRiwayatScope)->where('r.jenis_sk', 'nominasi')->count()
                    : 0;
                $totalNominal = (float) (clone $baseRiwayatScope)->sum('r.nominal');
                $totalTransaksi = (clone $baseRiwayatScope)->count();

                $totalSiswaAktifPenerima = (clone $baseRiwayatScope)->whereNotNull('rb.rombongan_belajar_id')->count();
                $totalAlumniPenerima = (clone $baseRiwayatScope)->whereNull('rb.rombongan_belajar_id')->count();
                $totalRiwayatPenerimaUnik = (clone $baseRiwayatScope)
                    ->select(DB::raw("COUNT(DISTINCT COALESCE(NULLIF(r.nisn, ''), CAST(r.peserta_didik_id AS CHAR))) as c"))
                    ->value('c') ?? 0;

                $riwayatStats = [
                    'total_siswa_aktif'          => $totalSiswaAktif,
                    'total_penerima_unik'        => $totalRiwayatPenerimaUnik,
                    'total_siswa_aktif_penerima' => $totalSiswaAktifPenerima,
                    'total_belum'                => $totalBelumMenerima,
                    'total_alumni_penerima'      => $totalAlumniPenerima,
                    'total_siswa'                => $totalTransaksi,
                    'total_riwayat'              => $totalTransaksi,
                    'total_pemberian'            => $totalPemberian,
                    'total_nominasi'             => $totalNominasi,
                    'total_nominal'              => $totalNominal,
                ];

                $riwayatTahapList = $riwayatTahapOpsi;

                $hasUsulan = Table::exists('pip_usulan');

                if ($statusPipFilter === 'belum') {
                    $belumQuery = (clone $activeStudentsScope)
                        ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id');

                    if ($hasUsulan) {
                        $belumQuery->leftJoin('pip_usulan as u', function ($j) use ($activeTahun) {
                            $j->on('pd.peserta_didik_id', '=', 'u.peserta_didik_id')
                              ->where('u.tahun', '=', $activeTahun);
                        });
                    }

                    $belumQuery->whereNotExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('pip_riwayat as r')
                            ->where(function ($match) {
                                $match->whereColumn('r.peserta_didik_id', 'pd.peserta_didik_id')
                                      ->orWhere(function ($orMatch) {
                                          $orMatch->whereNull('r.peserta_didik_id')
                                                  ->whereNotNull('r.nisn')
                                                  ->where('r.nisn', '!=', '')
                                                  ->whereColumn('r.nisn', 'pd.nisn');
                                      });
                            });
                    });

                    $belumQuery->select(
                        'pd.peserta_didik_id',
                        'pd.nama as nama_pd',
                        'pd.nama as nama_pd_aktif',
                        'pd.nisn',
                        'pd.nisn as nisn_pd_aktif',
                        'pd.nipd',
                        'pd.nipd as nipd_pd_aktif',
                        'pd.nama_ibu as nama_ibu_kandung',
                        'pd.nama_rombel as nama_rombel_aktif',
                        'pd.rombongan_belajar_id as rombel_id_aktif',
                        'rb.nama as nama_kelas_aktif',
                        'pdm.foto_path',
                        $hasUsulan ? 'u.id as usulan_id' : DB::raw('NULL as usulan_id'),
                        $hasUsulan ? 'u.status_verifikasi as status_usulan' : DB::raw('NULL as status_usulan'),
                        $hasUsulan ? 'u.prioritas as prioritas_usulan' : DB::raw('NULL as prioritas_usulan'),
                        $hasUsulan ? 'u.keterangan as keterangan_usulan' : DB::raw('NULL as keterangan_usulan'),
                        DB::raw('NULL as id'),
                        DB::raw('NULL as nomor_sk'),
                        DB::raw('NULL as jenis_sk'),
                        DB::raw('NULL as tahap'),
                        DB::raw('NULL as tanggal_sk'),
                        DB::raw('0 as nominal'),
                        DB::raw('NULL as status_cair'),
                        DB::raw('NULL as tanggal_cair'),
                        DB::raw('NULL as tanggal_aktivasi'),
                        DB::raw('NULL as rombel'),
                        DB::raw('NULL as tahun'),
                        DB::raw("'belum_menerima' as status_pip_record")
                    );

                    if ($q !== '') {
                        $belumQuery->where(function ($sub) use ($q) {
                            $sub->where('pd.nama', 'LIKE', "%{$q}%")
                                ->orWhere('pd.nisn', 'LIKE', "%{$q}%")
                                ->orWhere('pd.nipd', 'LIKE', "%{$q}%")
                                ->orWhere('pd.nama_rombel', 'LIKE', "%{$q}%")
                                ->orWhere('rb.nama', 'LIKE', "%{$q}%")
                                ->orWhere('pd.nama_ibu', 'LIKE', "%{$q}%");
                        });
                    }

                    $riwayatList = $belumQuery
                        ->orderBy('rb.nama', 'asc')
                        ->orderBy('pd.nama', 'asc')
                        ->paginate($perPage, ['*'], 'riwayat_page')
                        ->withQueryString();
                } elseif ($statusPipFilter === 'semua') {
                    $qPenerima = (clone $baseRiwayatScope)
                        ->leftJoin('peserta_didik_meta as pdm', 'r.peserta_didik_id', '=', 'pdm.peserta_didik_id');

                    if ($hasUsulan) {
                        $qPenerima->leftJoin('pip_usulan as u', function ($j) use ($activeTahun) {
                            $j->on('pd.peserta_didik_id', '=', 'u.peserta_didik_id')
                              ->where('u.tahun', '=', $activeTahun);
                        });
                    }

                    $qPenerima->select(
                        'pd.peserta_didik_id',
                        'r.nama_pd',
                        'pd.nama as nama_pd_aktif',
                        $hasPdta ? 'pdta.nama as nama_pd_alumni' : DB::raw('NULL as nama_pd_alumni'),
                        'r.nisn',
                        'pd.nisn as nisn_pd_aktif',
                        'pd.nipd',
                        'pd.nipd as nipd_pd_aktif',
                        'r.nama_ibu_kandung',
                        'pd.nama_rombel as nama_rombel_aktif',
                        'pd.rombongan_belajar_id as rombel_id_aktif',
                        'rb.nama as nama_kelas_aktif',
                        $hasPdta ? 'pdta.status_keluar' : DB::raw('NULL as status_keluar'),
                        $hasPdta ? 'pdta.nama_rombel_terakhir' : DB::raw('NULL as nama_rombel_terakhir'),
                        $hasPdta ? DB::raw('COALESCE(pdta.foto_path, pdm.foto_path) as foto_path') : 'pdm.foto_path',
                        $hasUsulan ? 'u.id as usulan_id' : DB::raw('NULL as usulan_id'),
                        $hasUsulan ? 'u.status_verifikasi as status_usulan' : DB::raw('NULL as status_usulan'),
                        $hasUsulan ? 'u.prioritas as prioritas_usulan' : DB::raw('NULL as prioritas_usulan'),
                        $hasUsulan ? 'u.keterangan as keterangan_usulan' : DB::raw('NULL as keterangan_usulan'),
                        'r.id',
                        'r.nomor_sk',
                        'r.jenis_sk',
                        'r.tahap',
                        'r.tanggal_sk',
                        'r.nominal',
                        'r.status_cair',
                        'r.tanggal_cair',
                        'r.tanggal_aktivasi',
                        'r.rombel',
                        'r.tahun',
                        DB::raw("CASE
                            WHEN rb.rombongan_belajar_id IS NOT NULL THEN 'aktif'
                            WHEN " . ($hasPdta ? "pdta.status_keluar IS NOT NULL" : "0=1") . " THEN 'alumni'
                            ELSE 'riwayat_lain'
                        END as status_siswa"),
                        DB::raw("'penerima' as status_pip_record")
                    );

                    $qBelum = (clone $activeStudentsScope)
                        ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id');

                    if ($hasUsulan) {
                        $qBelum->leftJoin('pip_usulan as u', function ($j) use ($activeTahun) {
                            $j->on('pd.peserta_didik_id', '=', 'u.peserta_didik_id')
                              ->where('u.tahun', '=', $activeTahun);
                        });
                    }

                    $qBelum->whereNotExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('pip_riwayat as r')
                            ->where(function ($match) {
                                $match->whereColumn('r.peserta_didik_id', 'pd.peserta_didik_id')
                                      ->orWhere(function ($orMatch) {
                                          $orMatch->whereNull('r.peserta_didik_id')
                                                  ->whereNotNull('r.nisn')
                                                  ->where('r.nisn', '!=', '')
                                                  ->whereColumn('r.nisn', 'pd.nisn');
                                      });
                            });
                    });

                    $qBelum->select(
                        'pd.peserta_didik_id',
                        'pd.nama as nama_pd',
                        'pd.nama as nama_pd_aktif',
                        DB::raw('NULL as nama_pd_alumni'),
                        'pd.nisn',
                        'pd.nisn as nisn_pd_aktif',
                        'pd.nipd',
                        'pd.nipd as nipd_pd_aktif',
                        'pd.nama_ibu as nama_ibu_kandung',
                        'pd.nama_rombel as nama_rombel_aktif',
                        'pd.rombongan_belajar_id as rombel_id_aktif',
                        'rb.nama as nama_kelas_aktif',
                        DB::raw('NULL as status_keluar'),
                        DB::raw('NULL as nama_rombel_terakhir'),
                        'pdm.foto_path',
                        $hasUsulan ? 'u.id as usulan_id' : DB::raw('NULL as usulan_id'),
                        $hasUsulan ? 'u.status_verifikasi as status_usulan' : DB::raw('NULL as status_usulan'),
                        $hasUsulan ? 'u.prioritas as prioritas_usulan' : DB::raw('NULL as prioritas_usulan'),
                        $hasUsulan ? 'u.keterangan as keterangan_usulan' : DB::raw('NULL as keterangan_usulan'),
                        DB::raw('NULL as id'),
                        DB::raw('NULL as nomor_sk'),
                        DB::raw('NULL as jenis_sk'),
                        DB::raw('NULL as tahap'),
                        DB::raw('NULL as tanggal_sk'),
                        DB::raw('0 as nominal'),
                        DB::raw('NULL as status_cair'),
                        DB::raw('NULL as tanggal_cair'),
                        DB::raw('NULL as tanggal_aktivasi'),
                        DB::raw('NULL as rombel'),
                        DB::raw('NULL as tahun'),
                        DB::raw("'aktif' as status_siswa"),
                        DB::raw("'belum_menerima' as status_pip_record")
                    );

                    $union = $qPenerima->unionAll($qBelum);
                    $combinedQuery = DB::query()->fromSub($union, 'u_all');

                    if ($q !== '') {
                        $combinedQuery->where(function ($sub) use ($q) {
                            $sub->where('u_all.nama_pd', 'LIKE', "%{$q}%")
                                ->orWhere('u_all.nama_pd_aktif', 'LIKE', "%{$q}%")
                                ->orWhere('u_all.nisn', 'LIKE', "%{$q}%")
                                ->orWhere('u_all.nipd', 'LIKE', "%{$q}%")
                                ->orWhere('u_all.nomor_sk', 'LIKE', "%{$q}%")
                                ->orWhere('u_all.nama_kelas_aktif', 'LIKE', "%{$q}%")
                                ->orWhere('u_all.nama_ibu_kandung', 'LIKE', "%{$q}%");
                        });
                    }

                    $riwayatList = $combinedQuery
                        ->orderBy('u_all.status_pip_record', 'asc')
                        ->orderBy('u_all.nama_pd_aktif', 'asc')
                        ->paginate($perPage, ['*'], 'riwayat_page')
                        ->withQueryString();
                } else {
                    $riwayatQuery = (clone $baseRiwayatScope)
                        ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id');

                    if ($hasUsulan) {
                        $riwayatQuery->leftJoin('pip_usulan as u', function ($j) use ($activeTahun) {
                            $j->on('pd.peserta_didik_id', '=', 'u.peserta_didik_id')
                              ->where('u.tahun', '=', $activeTahun);
                        });
                    }

                    $riwayatQuery->select(
                        'r.*',
                        'pd.nama as nama_pd_aktif',
                        $hasPdta ? 'pdta.nama as nama_pd_alumni' : DB::raw('NULL as nama_pd_alumni'),
                        'pd.nisn as nisn_pd_aktif',
                        $hasPdta ? 'pdta.nisn as nisn_pd_alumni' : DB::raw('NULL as nisn_pd_alumni'),
                        'pd.nipd as nipd_pd_aktif',
                        'pd.nama_rombel as nama_rombel_aktif',
                        'pd.rombongan_belajar_id as rombel_id_aktif',
                        'rb.nama as nama_kelas_aktif',
                        $hasPdta ? 'pdta.status_keluar' : DB::raw('NULL as status_keluar'),
                        $hasPdta ? 'pdta.tahun_lulus' : DB::raw('NULL as tahun_lulus'),
                        $hasPdta ? 'pdta.nama_rombel_terakhir' : DB::raw('NULL as nama_rombel_terakhir'),
                        $hasPdta ? DB::raw('COALESCE(pdta.foto_path, pdm.foto_path) as foto_path') : 'pdm.foto_path',
                        $hasUsulan ? 'u.id as usulan_id' : DB::raw('NULL as usulan_id'),
                        $hasUsulan ? 'u.status_verifikasi as status_usulan' : DB::raw('NULL as status_usulan'),
                        $hasUsulan ? 'u.prioritas as prioritas_usulan' : DB::raw('NULL as prioritas_usulan'),
                        $hasUsulan ? 'u.keterangan as keterangan_usulan' : DB::raw('NULL as keterangan_usulan'),
                        DB::raw("CASE
                            WHEN rb.rombongan_belajar_id IS NOT NULL THEN 'aktif'
                            WHEN " . ($hasPdta ? "pdta.status_keluar IS NOT NULL" : "0=1") . " THEN 'alumni'
                            ELSE 'riwayat_lain'
                        END as status_siswa"),
                        DB::raw("'penerima' as status_pip_record")
                    );

                    $riwayatList = $riwayatQuery
                        ->orderByDesc('r.tahun')
                        ->orderByDesc('r.id')
                        ->paginate($perPage, ['*'], 'riwayat_page')
                        ->withQueryString();
                }

                $riwayatList->getCollection()->transform(function ($item) {
                    $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
                    $item->is_belum_menerima = (($item->status_pip_record ?? '') === 'belum_menerima');
                    return $item;
                });
            } else {
                $riwayatList = new LengthAwarePaginator(
                    collect(),
                    0,
                    $perPage,
                    1,
                    ['path' => $request->url(), 'pageName' => 'riwayat_page', 'query' => $request->query()]
                );
            }
        } elseif ($tab === 'riwayat_alumni' && $isAdmin) {
            // Deprecated: riwayat alumni telah digabungkan ke dalam tab 'riwayat'
        }

        $verifikasiList = null;
        $verifikasiPending = 0;
        if ($isAdmin && Table::exists('pip_penggunaan_dana')) {
            $verifikasiPending = PipPenggunaanDana::where('status', 'diajukan')->count();
        }
        $statusLaporanFilter = trim((string) $request->get('status_laporan', ''));
        if (!in_array($statusLaporanFilter, ['diajukan', 'diverifikasi', 'ditolak'], true)) {
            $statusLaporanFilter = '';
        }
        $verifikasiStats = ['total' => 0, 'diajukan' => 0, 'diverifikasi' => 0, 'ditolak' => 0];

        if ($isAdmin && $tab === 'verifikasi' && Table::exists('pip_penggunaan_dana')) {
            $verifQuery = PipPenggunaanDana::query()->with('bukti');

            if ($statusLaporanFilter !== '') {
                $verifQuery->where('status', $statusLaporanFilter);
            }
            if ($q !== '') {
                $like = '%' . $q . '%';
                $verifQuery->whereIn('peserta_didik_id', function ($sub) use ($like) {
                    $sub->select('peserta_didik_id')
                        ->from('peserta_didik')
                        ->where(function ($w) use ($like) {
                            $w->where('nama', 'like', $like)->orWhere('nisn', 'like', $like);
                        });
                });
            }

            $grouped = (clone $verifQuery)->reorder()
                ->select('status', DB::raw('COUNT(*) as jml'))
                ->groupBy('status')
                ->pluck('jml', 'status')
                ->toArray();
            foreach (['diajukan', 'diverifikasi', 'ditolak'] as $statusKey) {
                $verifikasiStats[$statusKey] = (int) ($grouped[$statusKey] ?? 0);
            }
            $verifikasiStats['total'] = array_sum($verifikasiStats);

            $verifikasiList = $verifQuery
                ->orderByRaw("CASE status WHEN 'diajukan' THEN 0 WHEN 'ditolak' THEN 1 ELSE 2 END")
                ->orderByDesc('id')
                ->paginate($perPage, ['*'], 'page')
                ->withQueryString();

            $pdIds = $verifikasiList->pluck('peserta_didik_id')->filter()->unique()->all();
            $pdMap = $pdIds
                ? DB::table('peserta_didik')->whereIn('peserta_didik_id', $pdIds)->get(['peserta_didik_id', 'nama', 'nisn'])->keyBy('peserta_didik_id')
                : collect();

            $verifikasiList->getCollection()->transform(function ($lap) use ($pdMap) {
                $pd = $pdMap[$lap->peserta_didik_id] ?? null;
                $lap->nama_peserta = $pd->nama ?? '-';
                $lap->nisn_peserta = $pd->nisn ?? '';
                $lap->rincian_list = is_array($lap->rincian) ? $lap->rincian : [];
                return $lap;
            });
        }

        if (!$riwayatList) {
            $riwayatList = new LengthAwarePaginator(
                collect(),
                0,
                $perPage,
                1,
                ['path' => $request->url(), 'pageName' => 'riwayat_page', 'query' => $request->query()]
            );
        }
        if (!$riwayatAlumniList) {
            $riwayatAlumniList = new LengthAwarePaginator(
                collect(),
                0,
                $perPage,
                1,
                ['path' => $request->url(), 'pageName' => 'alumni_page', 'query' => $request->query()]
            );
        }

        $kriteriaAlasan = PipUsulan::KRITERIA_ALASAN;
        $statusVerifikasiList = PipUsulan::STATUS_VERIFIKASI;

        return compact(
            'isAdmin',
            'user',
            'role',
            'activeRombel',
            'rombelList',
            'activeTahun',
            'tab',
            'q',
            'statusVerif',
            'faseFilter',
            'statusPerubahanFilter',
            'perPage',
            'usulanList',
            'stats',
            'kuotaList',
            'kuotaStats',
            'riwayatList',
            'riwayatTahapList',
            'riwayatTahunSkList',
            'tahunSkFilter',
            'riwayatStats',
            'riwayatAlumniList',
            'riwayatAlumniTahapList',
            'riwayatAlumniStats',
            'alumniStatusList',
            'statusKeluarFilter',
            'jenisSkFilter',
            'statusPipFilter',
            'kriteriaAlasan',
            'statusVerifikasiList',
            'dashboardData',
            'pipPengaturan',
            'pipStatusInfo',
            'isWaliLocked',
            'verifikasiList',
            'verifikasiStats',
            'verifikasiPending',
            'statusLaporanFilter'
        );
    }

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
        return PipDashboardStatsService::buildDashboardData(
            $isAdmin,
            $activeRombel,
            $activeTahun,
            $rombelList,
            $hasUsulanTable,
            $hasKuotaTable,
            $hasRiwayatTable
        );
    }
}
