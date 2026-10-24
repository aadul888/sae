<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Service khusus rekapitulasi dan pemrosesan validasi kelengkapan data & berkas siswa per rombel binaan.
 * ponytail: static-calculator; upgrade ke Redis caching jika akses validasi serentak melebihi 100 req/sec.
 */
class WaliKelasValidasiService
{
    /**
     * Kumpulkan seluruh dataset validasi data peserta didik untuk rombel aktif.
     */
    public static function getValidasiData(array $ctx, Request $request, bool $canUpdate): array
    {
        $activeRombel = $ctx['activeRombel'];

        $q          = trim($request->get('q', ''));
        $konfirmasi = trim((string) $request->get('konfirmasi', ''));
        $berkas     = trim((string) $request->get('berkas', ''));
        $gender     = trim($request->get('gender', ''));
        $perPage    = (int) $request->get('perPage', 15);
        $sort       = $request->get('sort', ($konfirmasi === 'sudah_konfirmasi' ? 'dikonfirmasi_pada' : 'nama'));
        $sortDir    = $request->get('sort_dir', ($konfirmasi === 'sudah_konfirmasi' && $sort === 'dikonfirmasi_pada' ? 'desc' : 'asc')) === 'desc' ? 'desc' : 'asc';

        if (!$activeRombel) {
            return [
                'hasRombel'    => false,
                'isAdmin'      => $ctx['isAdmin'],
                'rombelList'   => $ctx['rombelList'],
                'activeRombel' => null,
                'waliNama'     => $ctx['waliNama'],
                'list'         => collect(),
                'total'        => 0,
                'summary'      => [
                    'total'            => 0,
                    'sudah_konfirmasi' => 0,
                    'belum_konfirmasi' => 0,
                    'sesuai'           => 0,
                    'perlu_perbaikan'  => 0,
                    'berkas_lengkap'   => 0,
                    'berkas_menunggu'  => 0,
                    'berkas_kurang'    => 0,
                ],
                'q'            => $q,
                'konfirmasi'   => $konfirmasi,
                'berkas'       => $berkas,
                'gender'       => $gender,
                'perPage'      => $perPage,
                'sort'         => $sort,
                'sortDir'      => $sortDir,
                'canUpdate'    => $canUpdate,
            ];
        }

        $targetRombelId = $activeRombel->rombongan_belajar_id ?? null;
        $targetRombelName = $activeRombel->nama ?? null;

        $baseQuery = DB::table('peserta_didik as pd')
            ->where(function ($w) use ($targetRombelId, $targetRombelName) {
                if ($targetRombelId) {
                    $w->where('pd.rombongan_belajar_id', $targetRombelId);
                }
                if ($targetRombelName) {
                    $w->orWhere('pd.nama_rombel', $targetRombelName);
                }
            });

        $allPdIds = (clone $baseQuery)->pluck('pd.peserta_didik_id')->all();
        $totalSiswa = count($allPdIds);

        $identitasAll = Schema::hasTable('peserta_didik_identitas') && !empty($allPdIds)
            ? DB::table('peserta_didik_identitas')->whereIn('peserta_didik_id', $allPdIds)->get()->keyBy('peserta_didik_id')
            : collect();

        $berkasAll = Schema::hasTable('peserta_didik_berkas') && !empty($allPdIds)
            ? DB::table('peserta_didik_berkas')->whereIn('peserta_didik_id', $allPdIds)->get()->groupBy('peserta_didik_id')
            : collect();

        $fisikAll = Schema::hasTable('kesiswaan_berkas_verifikasi') && !empty($allPdIds)
            ? DB::table('kesiswaan_berkas_verifikasi')->whereIn('peserta_didik_id', $allPdIds)->get()->keyBy('peserta_didik_id')
            : collect();

        $wajibKeys = ['akta_kelahiran', 'kartu_keluarga', 'ijazah_smp', 'ktp_orang_tua'];

        $statSudahKonfirmasi = 0;
        $statBelumKonfirmasi = 0;
        $statSesuai          = 0;
        $statPerluPerbaikan  = 0;
        $statBerkasLengkap   = 0;
        $statBerkasMenunggu  = 0;
        $statBerkasKurang    = 0;

        foreach ($allPdIds as $idSiswa) {
            $idRec = $identitasAll->get($idSiswa);
            $stKonf = $idRec?->status_konfirmasi ?? 'belum_konfirmasi';
            $isSudah = in_array($stKonf, ['sesuai', 'perlu_perbaikan', 'diverifikasi', 'disinkronkan_dapodik'], true);

            if ($isSudah) {
                $statSudahKonfirmasi++;
                if (in_array($stKonf, ['sesuai', 'diverifikasi', 'disinkronkan_dapodik'], true)) {
                    $statSesuai++;
                } elseif ($stKonf === 'perlu_perbaikan') {
                    $statPerluPerbaikan++;
                }
            } else {
                $statBelumKonfirmasi++;
            }

            $sB = $berkasAll->get($idSiswa) ?? collect();
            $sF = $fisikAll->get($idSiswa);

            $valCount = 0;
            $menungguExist = false;
            foreach ($wajibKeys as $wk) {
                $bf = $sB->firstWhere('jenis_berkas', $wk);
                $isVal = ($bf?->status === 'valid') || !empty($sF?->{$wk});
                if ($isVal) {
                    $valCount++;
                } elseif ($bf?->status === 'menunggu') {
                    $menungguExist = true;
                }
            }

            if ($valCount === count($wajibKeys)) {
                $statBerkasLengkap++;
            } elseif ($menungguExist) {
                $statBerkasMenunggu++;
            } else {
                $statBerkasKurang++;
            }
        }

        $summary = [
            'total'            => $totalSiswa,
            'sudah_konfirmasi' => $statSudahKonfirmasi,
            'belum_konfirmasi' => $statBelumKonfirmasi,
            'sesuai'           => $statSesuai,
            'perlu_perbaikan'  => $statPerluPerbaikan,
            'berkas_lengkap'   => $statBerkasLengkap,
            'berkas_menunggu'  => $statBerkasMenunggu,
            'berkas_kurang'    => $statBerkasKurang,
        ];

        $query = (clone $baseQuery)
            ->leftJoin('peserta_didik_identitas as pdi', 'pd.peserta_didik_id', '=', 'pdi.peserta_didik_id')
            ->select(
                'pd.peserta_didik_id',
                'pd.nama',
                'pd.nisn',
                'pd.nipd',
                'pd.nik',
                'pd.jenis_kelamin',
                'pd.nama_rombel',
                'pd.tempat_lahir',
                'pd.tanggal_lahir',
                'pdi.status_konfirmasi',
                'pdi.dikonfirmasi_pada',
                'pdi.dikonfirmasi_oleh',
                'pdi.catatan_siswa'
            );

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('pd.nama', 'like', "%{$q}%")
                  ->orWhere('pd.nisn', 'like', "%{$q}%")
                  ->orWhere('pd.nipd', 'like', "%{$q}%")
                  ->orWhere('pd.nik', 'like', "%{$q}%");
            });
        }

        if ($gender !== '') {
            $query->where('pd.jenis_kelamin', $gender);
        }

        if ($konfirmasi === 'sudah_konfirmasi') {
            $query->whereNotNull('pdi.status_konfirmasi')
                  ->where('pdi.status_konfirmasi', '!=', 'belum_konfirmasi');
        } elseif ($konfirmasi === 'belum_konfirmasi') {
            $query->where(function ($w) {
                $w->whereNull('pdi.status_konfirmasi')
                  ->orWhere('pdi.status_konfirmasi', 'belum_konfirmasi');
            });
        } elseif (in_array($konfirmasi, ['sesuai', 'perlu_perbaikan'], true)) {
            $query->where('pdi.status_konfirmasi', $konfirmasi);
        } elseif ($konfirmasi === 'diverifikasi') {
            $query->whereIn('pdi.status_konfirmasi', ['diverifikasi', 'disinkronkan_dapodik']);
        }

        if ($berkas === 'menunggu') {
            $query->whereExists(function ($sq) {
                $sq->select(DB::raw(1))
                   ->from('peserta_didik_berkas as pdb')
                   ->whereColumn('pdb.peserta_didik_id', 'pd.peserta_didik_id')
                   ->where('pdb.status', 'menunggu');
            });
        } elseif ($berkas === 'tidak_valid') {
            $query->whereExists(function ($sq) {
                $sq->select(DB::raw(1))
                   ->from('peserta_didik_berkas as pdb')
                   ->whereColumn('pdb.peserta_didik_id', 'pd.peserta_didik_id')
                   ->where('pdb.status', 'tidak_valid');
            });
        } elseif ($berkas === 'lengkap') {
            foreach ($wajibKeys as $wk) {
                $query->where(function ($w) use ($wk) {
                    $w->whereExists(function ($sq) use ($wk) {
                        $sq->select(DB::raw(1))
                           ->from('peserta_didik_berkas as pdb')
                           ->whereColumn('pdb.peserta_didik_id', 'pd.peserta_didik_id')
                           ->where('pdb.jenis_berkas', $wk)
                           ->where('pdb.status', 'valid');
                    })->orWhereExists(function ($sq) use ($wk) {
                        $sq->select(DB::raw(1))
                           ->from('kesiswaan_berkas_verifikasi as kbv')
                           ->whereColumn('kbv.peserta_didik_id', 'pd.peserta_didik_id')
                           ->where("kbv.{$wk}", 1);
                    });
                });
            }
        } elseif ($berkas === 'belum_lengkap') {
            $query->where(function ($w) use ($wajibKeys) {
                foreach ($wajibKeys as $wk) {
                    $w->orWhere(function ($sq) use ($wk) {
                        $sq->whereNotExists(function ($sub) use ($wk) {
                            $sub->select(DB::raw(1))
                                ->from('peserta_didik_berkas as pdb')
                                ->whereColumn('pdb.peserta_didik_id', 'pd.peserta_didik_id')
                                ->where('pdb.jenis_berkas', $wk)
                                ->where('pdb.status', 'valid');
                        })->whereNotExists(function ($sub) use ($wk) {
                            $sub->select(DB::raw(1))
                                ->from('kesiswaan_berkas_verifikasi as kbv')
                                ->whereColumn('kbv.peserta_didik_id', 'pd.peserta_didik_id')
                                ->where("kbv.{$wk}", 1);
                        });
                    });
                }
            });
        }

        $sortCol = match ($sort) {
            'nisn' => 'pd.nisn',
            'nipd' => 'pd.nipd',
            'jenis_kelamin' => 'pd.jenis_kelamin',
            'status_konfirmasi' => 'pdi.status_konfirmasi',
            'dikonfirmasi_pada' => 'pdi.dikonfirmasi_pada',
            default => 'pd.nama',
        };

        if ($sort === 'dikonfirmasi_pada') {
            $query->orderByRaw("pdi.dikonfirmasi_pada IS NULL ASC")
                  ->orderBy('pdi.dikonfirmasi_pada', $sortDir)
                  ->orderBy('pd.nama', 'asc');
        } else {
            $query->orderBy($sortCol, $sortDir);
        }

        $list = $query->paginate($perPage)->withQueryString();

        $pagePdIds = $list->pluck('peserta_didik_id')->all();
        $metaMap = Schema::hasTable('peserta_didik_meta') && !empty($pagePdIds)
            ? DB::table('peserta_didik_meta')->whereIn('peserta_didik_id', $pagePdIds)->get()->keyBy('peserta_didik_id')
            : collect();

        $berkasMap = Schema::hasTable('peserta_didik_berkas') && !empty($pagePdIds)
            ? DB::table('peserta_didik_berkas')->whereIn('peserta_didik_id', $pagePdIds)->get()->groupBy('peserta_didik_id')
            : collect();

        $fisikMap = Schema::hasTable('kesiswaan_berkas_verifikasi') && !empty($pagePdIds)
            ? DB::table('kesiswaan_berkas_verifikasi')->whereIn('peserta_didik_id', $pagePdIds)->get()->keyBy('peserta_didik_id')
            : collect();

        foreach ($list as $item) {
            $m = $metaMap->get($item->peserta_didik_id);
            $item->foto_url = !empty($m?->foto_path) ? asset('storage/' . ltrim($m->foto_path, '/')) : ($m?->foto_url ?? null);

            $sFiles = $berkasMap->get($item->peserta_didik_id) ?? collect();
            $sFisik = $fisikMap->get($item->peserta_didik_id);

            $item->berkas_items = $sFiles->keyBy('jenis_berkas');
            $item->fisik_verif  = $sFisik;

            $validCount = 0;
            $menungguCount = 0;
            $tidakValidCount = 0;
            $uploadedCount = 0;

            foreach ($wajibKeys as $wk) {
                $bf = $item->berkas_items->get($wk);
                $isFisikValid = !empty($sFisik?->{$wk});
                $isDigitalValid = ($bf?->status === 'valid');

                if ($isDigitalValid || $isFisikValid) {
                    $validCount++;
                } elseif ($bf?->status === 'menunggu') {
                    $menungguCount++;
                } elseif ($bf?->status === 'tidak_valid') {
                    $tidakValidCount++;
                }

                if ($bf && !empty($bf->file_path)) {
                    $uploadedCount++;
                }
            }

            $item->total_wajib = count($wajibKeys);
            $item->valid_wajib_count = $validCount;
            $item->menunggu_count = $menungguCount;
            $item->tidak_valid_count = $tidakValidCount;
            $item->uploaded_count = $uploadedCount;
            $item->is_berkas_lengkap = ($validCount === count($wajibKeys));
        }

        return [
            'hasRombel'    => true,
            'isAdmin'      => $ctx['isAdmin'],
            'rombelList'   => $ctx['rombelList'],
            'activeRombel' => $activeRombel,
            'waliNama'     => $ctx['waliNama'],
            'list'         => $list,
            'total'        => $list->total(),
            'summary'      => $summary,
            'q'            => $q,
            'konfirmasi'   => $konfirmasi,
            'berkas'       => $berkas,
            'gender'       => $gender,
            'perPage'      => $perPage,
            'sort'         => $sort,
            'sortDir'      => $sortDir,
            'canUpdate'    => $canUpdate,
        ];
    }
}
