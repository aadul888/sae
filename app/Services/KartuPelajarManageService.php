<?php

namespace App\Services;

use App\Models\KartuPelajarPembayaran;
use App\Models\KartuPelajarPengaturan;
use App\Models\KartuPelajarPengeluaran;
use App\Models\KartuPelajarRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class KartuPelajarManageService
{
    /**
     * Kompilasi data komprehensif untuk halaman manajemen kartu pelajar (6 tab interaktif).
     */
    public function getManageIndexData(Request $request, array $sekolahInfo): array
    {
        $tab = $request->get('tab', 'keuangan');
        if ($tab === 'siswa') {
            $tab = 'berhasil-cetak';
        }
        $settings = KartuPelajarPengaturan::getSettings();

        $q = trim($request->get('q', ''));
        $rombel = trim($request->get('rombel', ''));
        $statusBayar = trim($request->get('status_pembayaran', ''));
        $statusCetak = trim($request->get('status_cetak', ''));
        $perPageVal = $request->get('perPage', '15');
        $perPage = in_array((int) $perPageVal, [10, 15, 25, 50, 100], true) ? (int) $perPageVal : 15;
        $sort = in_array($request->get('sort'), ['nama', 'nisn', 'nama_rombel', 'status_pembayaran', 'status_cetak', 'nominal', 'tanggal_bayar', 'created_at', 'tanggal', 'status', 'jenis_request'], true) ? $request->get('sort') : 'nama';
        $sortDir = strtolower($request->get('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        // Base query dari peserta_didik yang sudah berhasil dicetak (Tab Berhasil Cetak)
        $query = DB::table('peserta_didik as pd')
            ->leftJoin('kartu_pelajar_pembayaran as kp', 'pd.peserta_didik_id', '=', 'kp.peserta_didik_id')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->select(
                'pd.peserta_didik_id',
                'pd.nama',
                'pd.nisn',
                'pd.nipd',
                'pd.nama_rombel',
                'pd.rombongan_belajar_id',
                'pd.nomor_telepon_seluler',
                'pd.nomor_telepon_rumah',
                'pdm.foto_path',
                'kp.id as pembayaran_id',
                DB::raw("COALESCE(kp.status_pembayaran, 'belum_bayar') as status_pembayaran"),
                DB::raw("COALESCE(kp.nominal, " . (int)$settings->biaya_standar . ") as nominal"),
                'kp.metode_pembayaran',
                'kp.tanggal_bayar',
                DB::raw("COALESCE(kp.status_cetak, 'belum_cetak') as status_cetak"),
                'kp.tanggal_cetak',
                'kp.nomor_wa',
                'kp.wa_terkirim_at',
                'kp.catatan',
                DB::raw("COALESCE(kp.klaim_ganti_kartu, 0) as klaim_ganti_kartu"),
                DB::raw("COALESCE(kp.klaim_stiker, 0) as klaim_stiker")
            );

        $query->where(function ($sq) {
            $sq->where('kp.status_cetak', 'sudah_cetak');
            if (Schema::hasTable('kartu_pelajar_request')) {
                $sq->orWhereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('kartu_pelajar_request as kpr')
                        ->whereColumn('kpr.peserta_didik_id', 'pd.peserta_didik_id')
                        ->where('kpr.status', 'selesai');
                });
            }
        });

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('pd.nama', 'LIKE', "%{$q}%")
                    ->orWhere('pd.nisn', 'LIKE', "%{$q}%")
                    ->orWhere('pd.nipd', 'LIKE', "%{$q}%");
            });
        }

        if ($rombel !== '') {
            $query->where('pd.nama_rombel', $rombel);
        }

        if ($statusBayar === 'lunas') {
            $query->where('kp.status_pembayaran', 'lunas');
        } elseif ($statusBayar === 'belum_bayar') {
            $query->where(function ($sq) {
                $sq->whereNull('kp.status_pembayaran')
                    ->orWhere('kp.status_pembayaran', 'belum_bayar');
            });
        }

        $orderColumn = match ($sort) {
            'nisn' => 'pd.nisn',
            'nama_rombel' => 'pd.nama_rombel',
            'status_pembayaran' => 'status_pembayaran',
            'status_cetak' => 'status_cetak',
            'nominal' => 'nominal',
            'tanggal_bayar' => 'kp.tanggal_bayar',
            default => 'pd.nama',
        };

        $totalFiltered = (clone $query)->count();
        $lastPage = max(1, (int) ceil($totalFiltered / $perPage));
        $currentPage = (int) $request->get('page', 1);
        if ($currentPage > $lastPage || $currentPage < 1) {
            $currentPage = 1;
        }

        $students = $query->orderBy($orderColumn, $sortDir)
            ->orderBy('pd.nama', 'asc')
            ->paginate($perPage, ['*'], 'page', $currentPage)
            ->withQueryString();

        $students->getCollection()->transform(function ($item) {
            $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
            $rawWa = $item->nomor_wa ?: ($item->nomor_telepon_seluler ?: $item->nomor_telepon_rumah);
            $item->wa_clean = KartuPelajarPembayaran::normalizePhoneNumber($rawWa);
            return $item;
        });

        // Hitung statistik live tab Berhasil Cetak
        $statScope = DB::table('peserta_didik as pd')
            ->leftJoin('kartu_pelajar_pembayaran as kp', 'pd.peserta_didik_id', '=', 'kp.peserta_didik_id');

        $statScope->where(function ($sq) {
            $sq->where('kp.status_cetak', 'sudah_cetak');
            if (Schema::hasTable('kartu_pelajar_request')) {
                $sq->orWhereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('kartu_pelajar_request as kpr')
                        ->whereColumn('kpr.peserta_didik_id', 'pd.peserta_didik_id')
                        ->where('kpr.status', 'selesai');
                });
            }
        });

        if ($q !== '') {
            $statScope->where(function ($sub) use ($q) {
                $sub->where('pd.nama', 'LIKE', "%{$q}%")
                    ->orWhere('pd.nisn', 'LIKE', "%{$q}%")
                    ->orWhere('pd.nipd', 'LIKE', "%{$q}%");
            });
        }

        if ($rombel !== '') {
            $statScope->where('pd.nama_rombel', $rombel);
        }

        if ($statusBayar === 'lunas') {
            $statScope->where('kp.status_pembayaran', 'lunas');
        } elseif ($statusBayar === 'belum_bayar') {
            $statScope->where(function ($sq) {
                $sq->whereNull('kp.status_pembayaran')
                    ->orWhere('kp.status_pembayaran', 'belum_bayar');
            });
        }

        $totalSiswa = (clone $statScope)->count();
        $totalLunas = (clone $statScope)->where('kp.status_pembayaran', 'lunas')->count();
        $totalBelumBayar = (clone $statScope)->where(function ($sq) {
            $sq->whereNull('kp.status_pembayaran')
                ->orWhere('kp.status_pembayaran', 'belum_bayar');
        })->count();
        $totalDanaTerkumpul = (clone $statScope)->where('kp.status_pembayaran', 'lunas')->sum('kp.nominal');

        $totalKlaimKartu = Schema::hasTable('kartu_pelajar_garansi_log')
            ? DB::table('kartu_pelajar_garansi_log')->where('jenis_klaim', 'ganti_kartu_full')->count()
            : 0;
        $totalKlaimStiker = Schema::hasTable('kartu_pelajar_garansi_log')
            ? DB::table('kartu_pelajar_garansi_log')->where('jenis_klaim', 'cetak_stiker')->count()
            : 0;

        $stats = [
            'total_siswa' => $totalSiswa,
            'total_selesai_cetak' => $totalSiswa,
            'total_lunas' => $totalLunas,
            'total_belum_bayar' => $totalBelumBayar,
            'total_dana' => $totalDanaTerkumpul,
            'total_sudah_cetak' => $totalSiswa,
            'total_belum_cetak' => $totalBelumBayar,
            'total_klaim_kartu' => $totalKlaimKartu,
            'total_klaim_stiker' => $totalKlaimStiker,
        ];

        // Daftar Rombel
        $rombelList = DB::table('rombongan_belajar')
            ->where('jenis_rombel', '1')
            ->orderBy('tingkat_pendidikan_id', 'asc')
            ->orderBy('nama', 'asc')
            ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id')
            ->get();

        // Riwayat Log Garansi
        $garansiLogs = null;
        if ($tab === 'garansi') {
            $logQuery = DB::table('kartu_pelajar_garansi_log as kgl')
                ->leftJoin('peserta_didik as pd', 'kgl.peserta_didik_id', '=', 'pd.peserta_didik_id')
                ->select('kgl.*', 'pd.nama as nama_siswa', 'pd.nama_rombel as rombel_siswa');

            if ($q !== '') {
                $logQuery->where(function ($sub) use ($q) {
                    $sub->where('pd.nama', 'LIKE', "%{$q}%")
                        ->orWhere('kgl.nisn', 'LIKE', "%{$q}%")
                        ->orWhere('kgl.alasan', 'LIKE', "%{$q}%")
                        ->orWhere('kgl.petugas', 'LIKE', "%{$q}%");
                });
            }

            $garansiLogs = $logQuery->orderByDesc('kgl.tanggal_klaim')->paginate(15, ['*'], 'garansi_page')->withQueryString();
        }

        // Keuangan Kartu Pelajar
        $totalPemasukan = DB::table('kartu_pelajar_pembayaran')->where('status_pembayaran', 'lunas')->sum('nominal');
        $countPemasukan = DB::table('kartu_pelajar_pembayaran')->where('status_pembayaran', 'lunas')->count();
        $totalPengeluaran = Schema::hasTable('kartu_pelajar_pengeluaran') ? DB::table('kartu_pelajar_pengeluaran')->sum('nominal') : 0;
        $countPengeluaran = Schema::hasTable('kartu_pelajar_pengeluaran') ? DB::table('kartu_pelajar_pengeluaran')->count() : 0;
        $saldoBersih = $totalPemasukan - $totalPengeluaran;

        $totalSiswaGlobal = DB::table('peserta_didik')->count();
        $siswaBelumBayarGlobal = max(0, $totalSiswaGlobal - $countPemasukan);
        $estimasiPiutang = $siswaBelumBayarGlobal * (int) ($settings->biaya_standar ?? 25000);

        $keuangan = [
            'total_pemasukan'        => (int) $totalPemasukan,
            'total_transaksi_masuk'  => $countPemasukan,
            'total_pengeluaran'      => (int) $totalPengeluaran,
            'total_transaksi_keluar' => $countPengeluaran,
            'saldo_bersih'           => (int) $saldoBersih,
            'estimasi_piutang'       => (int) $estimasiPiutang,
            'siswa_belum_bayar'      => $siswaBelumBayarGlobal,
        ];

        $pengeluaranList = null;
        $recentPemasukan = null;
        $pemasukanList = null;
        $bQ = '';
        $bPerPage = 10;
        $bSort = null;
        $bDir = 'desc';
        $chartKeuangan = [
            'labels'          => [],
            'pemasukan'       => [],
            'pengeluaran'     => [],
            'kategori_labels' => [],
            'kategori_values' => [],
        ];

        if ($tab === 'keuangan') {
            $bayarQuery = DB::table('kartu_pelajar_pembayaran as kp')
                ->join('peserta_didik as pd', 'kp.peserta_didik_id', '=', 'pd.peserta_didik_id')
                ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
                ->where('kp.status_pembayaran', 'lunas')
                ->select(
                    'kp.*',
                    'pd.nama',
                    'pd.nama_rombel',
                    'pd.nisn',
                    'pd.nipd',
                    'pd.nomor_telepon_seluler',
                    'pd.nomor_telepon_rumah',
                    'pdm.foto_path'
                );

            if ($q !== '') {
                $bayarQuery->where(function ($sub) use ($q) {
                    $sub->where('pd.nama', 'LIKE', "%{$q}%")
                        ->orWhere('pd.nisn', 'LIKE', "%{$q}%")
                        ->orWhere('pd.nama_rombel', 'LIKE', "%{$q}%")
                        ->orWhere('kp.metode_pembayaran', 'LIKE', "%{$q}%");
                });
            }

            if ($rombel !== '') {
                $bayarQuery->where('pd.nama_rombel', $rombel);
            }

            if (in_array($sort, ['nama', 'nisn', 'nama_rombel', 'nominal', 'tanggal_bayar'], true)) {
                $sortColumn = in_array($sort, ['nama', 'nisn', 'nama_rombel'], true) ? 'pd.' . $sort : 'kp.' . $sort;
                $bayarQuery->orderBy($sortColumn, $sortDir);
            } else {
                $bayarQuery->orderByDesc('kp.tanggal_bayar')->orderByDesc('kp.updated_at');
            }

            $pemasukanList = $bayarQuery->paginate($perPage)->withQueryString();
            $recentPemasukan = $pemasukanList;

            if (Schema::hasTable('kartu_pelajar_pengeluaran')) {
                $bQ = trim((string) $request->get('belanja_q', ''));
                $bPerPage = in_array((int) $request->get('belanja_per_page'), [10, 15, 25, 50, 100], true) ? (int) $request->get('belanja_per_page') : 10;
                $bSort = in_array($request->get('belanja_sort'), ['tanggal', 'nominal', 'judul', 'kategori'], true) ? $request->get('belanja_sort') : null;
                $bDir = strtolower((string) $request->get('belanja_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

                $pQuery = DB::table('kartu_pelajar_pengeluaran');
                if ($bQ !== '') {
                    $pQuery->where(function ($sub) use ($bQ) {
                        $sub->where('judul', 'LIKE', "%{$bQ}%")
                            ->orWhere('kategori', 'LIKE', "%{$bQ}%")
                            ->orWhere('keterangan', 'LIKE', "%{$bQ}%")
                            ->orWhere('petugas', 'LIKE', "%{$bQ}%");
                    });
                }

                if ($bSort) {
                    $pQuery->orderBy($bSort, $bDir)->orderByDesc('id');
                } else {
                    $pQuery->orderByDesc('tanggal')->orderByDesc('id');
                }

                $pengeluaranList = $pQuery->paginate($bPerPage, ['*'], 'belanja_page')->withQueryString();
            }

            for ($i = 5; $i >= 0; $i--) {
                $mDate = now()->subMonths($i);
                $mLabel = $mDate->translatedFormat('M Y');
                $chartKeuangan['labels'][] = $mLabel;

                $masukBulan = DB::table('kartu_pelajar_pembayaran')
                    ->where('status_pembayaran', 'lunas')
                    ->whereYear('tanggal_bayar', $mDate->year)
                    ->whereMonth('tanggal_bayar', $mDate->month)
                    ->sum('nominal');
                $chartKeuangan['pemasukan'][] = (int) $masukBulan;

                $keluarBulan = Schema::hasTable('kartu_pelajar_pengeluaran')
                    ? DB::table('kartu_pelajar_pengeluaran')
                        ->whereYear('tanggal', $mDate->year)
                        ->whereMonth('tanggal', $mDate->month)
                        ->sum('nominal')
                    : 0;
                $chartKeuangan['pengeluaran'][] = (int) $keluarBulan;
            }

            if (Schema::hasTable('kartu_pelajar_pengeluaran')) {
                $kategoriMapping = KartuPelajarPengeluaran::getKategoriList();
                $katSums = DB::table('kartu_pelajar_pengeluaran')
                    ->select('kategori', DB::raw('SUM(nominal) as total'))
                    ->groupBy('kategori')
                    ->pluck('total', 'kategori');

                foreach ($kategoriMapping as $kKey => $kLabel) {
                    $val = (int) ($katSums[$kKey] ?? 0);
                    if ($val > 0) {
                        $chartKeuangan['kategori_labels'][] = $kLabel;
                        $chartKeuangan['kategori_values'][] = $val;
                    }
                }
            }
        }

        $kategoriList = KartuPelajarPengeluaran::getKategoriList();

        // Tab Request Cetak
        $requestList = null;
        $requestStats = ['menunggu' => 0, 'diproses' => 0, 'selesai' => 0, 'ditolak' => 0];
        $reqStatus = trim((string) $request->get('req_status', ''));
        if ($tab === 'request' && Schema::hasTable('kartu_pelajar_request')) {
            $requestHasMetodePembayaran = Schema::hasColumn('kartu_pelajar_request', 'metode_pembayaran');
            $statQuery = DB::table('kartu_pelajar_request as r')
                ->join('peserta_didik as pd', 'r.peserta_didik_id', '=', 'pd.peserta_didik_id');
            if ($rombel !== '') {
                $statQuery->where('pd.nama_rombel', $rombel);
            }
            foreach ($statQuery->select('r.status', DB::raw('COUNT(*) as total'))->groupBy('r.status')->pluck('total', 'r.status') as $st => $n) {
                $requestStats[$st] = (int) $n;
            }

            $rq = DB::table('kartu_pelajar_request as r')
                ->join('peserta_didik as pd', 'r.peserta_didik_id', '=', 'pd.peserta_didik_id')
                ->leftJoin('kartu_pelajar_pembayaran as kp', 'r.peserta_didik_id', '=', 'kp.peserta_didik_id')
                ->leftJoin('peserta_didik_meta as pdm', 'r.peserta_didik_id', '=', 'pdm.peserta_didik_id')
                ->select(
                    'r.*',
                    'pd.nama',
                    'pd.nama_rombel',
                    'pd.nipd',
                    DB::raw("COALESCE(r.nisn, pd.nisn) as nisn"),
                    'pdm.foto_path',
                    DB::raw($requestHasMetodePembayaran ? "COALESCE(r.metode_pembayaran, kp.metode_pembayaran) as metode_pembayaran_aktif" : "kp.metode_pembayaran as metode_pembayaran_aktif"),
                    DB::raw("COALESCE(kp.status_pembayaran, 'belum_bayar') as status_pembayaran_aktif")
                );
            if (isset(KartuPelajarRequest::STATUS[$reqStatus])) {
                $rq->where('r.status', $reqStatus);
            }
            if ($rombel !== '') {
                $rq->where('pd.nama_rombel', $rombel);
            }
            if ($statusBayar === 'lunas') {
                $rq->where('kp.status_pembayaran', 'lunas');
            } elseif ($statusBayar === 'belum_bayar') {
                $rq->where(function ($sq) {
                    $sq->whereNull('kp.status_pembayaran')
                        ->orWhere('kp.status_pembayaran', 'belum_bayar');
                });
            }
            if ($q !== '') {
                $rq->where(function ($sub) use ($q) {
                    $sub->where('pd.nama', 'LIKE', "%{$q}%")
                        ->orWhere('r.nisn', 'LIKE', "%{$q}%")
                        ->orWhere('pd.nipd', 'LIKE', "%{$q}%")
                        ->orWhere('r.alasan', 'LIKE', "%{$q}%");
                });
            }

            $reqSort = in_array($sort, ['nama', 'nisn', 'nama_rombel', 'created_at', 'tanggal', 'status', 'status_pembayaran', 'jenis_request'], true)
                ? $sort
                : null;

            if ($reqSort) {
                $rqCol = match ($reqSort) {
                    'nama' => 'pd.nama',
                    'nisn' => 'r.nisn',
                    'nama_rombel' => 'pd.nama_rombel',
                    'tanggal', 'created_at' => 'r.created_at',
                    'status_pembayaran' => 'status_pembayaran_aktif',
                    'status' => 'r.status',
                    'jenis_request' => 'r.jenis_request',
                    default => 'pd.nama',
                };
                $rq->orderBy($rqCol, $sortDir);
            } else {
                $rq->orderByRaw("FIELD(r.status, 'menunggu','diproses','selesai','ditolak')")
                    ->orderByDesc('r.created_at');
            }
            $rq->orderBy('pd.nama', 'asc');

            $totalRequestFiltered = (clone $rq)->count();
            $lastRequestPage = max(1, (int) ceil($totalRequestFiltered / $perPage));
            $currentRequestPage = (int) $request->get('request_page', 1);
            if ($currentRequestPage > $lastRequestPage || $currentRequestPage < 1) {
                $currentRequestPage = 1;
            }

            $requestList = $rq->paginate($perPage, ['*'], 'request_page', $currentRequestPage)
                ->withQueryString();

            $requestList->getCollection()->transform(function ($item) {
                $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
                return $item;
            });
        }

        return compact(
            'students',
            'stats',
            'rombelList',
            'q',
            'rombel',
            'statusBayar',
            'statusCetak',
            'perPage',
            'sort',
            'sortDir',
            'sekolahInfo',
            'tab',
            'settings',
            'garansiLogs',
            'keuangan',
            'pengeluaranList',
            'recentPemasukan',
            'pemasukanList',
            'bQ',
            'bPerPage',
            'bSort',
            'bDir',
            'chartKeuangan',
            'kategoriList',
            'requestList',
            'requestStats',
            'reqStatus'
        );
    }

    /**
     * Update Catatan Pembayaran Kartu Pelajar (Single).
     */
    public function updatePembayaran(array $data, mixed $user, array $sekolahInfo): array
    {
        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $data['peserta_didik_id'])->first();
        if (!$pd) {
            return ['error' => 'not_found', 'message' => 'Peserta didik tidak ditemukan.'];
        }

        $statusBayar = $data['status_pembayaran'];
        $statusCetak = $data['status_cetak'] ?? 'belum_cetak';
        $nominal = !empty($data['nominal']) ? (int) $data['nominal'] : 25000;
        $tanggalBayar = $statusBayar === 'lunas' ? ($data['tanggal_bayar'] ?? now()->toDateString()) : null;
        $tanggalCetak = $statusCetak === 'sudah_cetak' ? now() : null;

        $record = KartuPelajarPembayaran::updateOrCreate(
            ['peserta_didik_id' => $pd->peserta_didik_id],
            [
                'nisn'              => $pd->nisn,
                'status_pembayaran' => $statusBayar,
                'nominal'           => $nominal,
                'metode_pembayaran' => $statusBayar === 'lunas' ? ($data['metode_pembayaran'] ?? 'tunai') : null,
                'tanggal_bayar'     => $tanggalBayar,
                'status_cetak'      => $statusCetak,
                'tanggal_cetak'     => $tanggalCetak,
                'nomor_wa'          => !empty($data['nomor_wa']) ? KartuPelajarPembayaran::normalizePhoneNumber($data['nomor_wa']) : null,
                'catatan'           => $data['catatan'] ?? null,
                'created_by'        => is_array($user) ? ($user['nama'] ?? 'Admin') : ($user->nama ?? 'Admin'),
            ]
        );

        if (Schema::hasColumn('kartu_pelajar_request', 'metode_pembayaran')) {
            KartuPelajarRequest::where('peserta_didik_id', $pd->peserta_didik_id)
                ->whereIn('status', ['menunggu', 'diproses'])
                ->update(['metode_pembayaran' => $record->metode_pembayaran]);
        }

        $targetWa = $record->nomor_wa ?: ($pd->nomor_telepon_seluler ?: $pd->nomor_telepon_rumah);
        $cleanWa = KartuPelajarPembayaran::normalizePhoneNumber($targetWa);

        $waData = [
            'nama' => $pd->nama,
            'nisn' => $pd->nisn,
            'rombel' => $pd->nama_rombel ?: '-',
            'status_pembayaran' => $record->status_pembayaran,
            'nominal' => $record->nominal,
            'metode_pembayaran' => $record->metode_pembayaran,
            'tanggal_bayar' => $record->tanggal_bayar,
            'status_cetak' => $record->status_cetak,
            'klaim_ganti_kartu' => $record->klaim_ganti_kartu ?? 0,
            'klaim_stiker' => $record->klaim_stiker ?? 0,
            'verify_url' => route('kartu-pelajar.verify-short', ['nisn' => $pd->nisn]),
        ];

        $pesanWa = KartuPelajarPembayaran::buildWhatsAppMessage($waData, $sekolahInfo);
        $waUrl = $cleanWa ? ('https://wa.me/' . $cleanWa . '?text=' . rawurlencode($pesanWa)) : null;

        return [
            'success' => true,
            'message' => "Data pembayaran kartu pelajar untuk {$pd->nama} berhasil disimpan.",
            'data' => $record,
            'wa_clean' => $cleanWa,
            'wa_message' => $pesanWa,
            'wa_url' => $waUrl,
        ];
    }

    /**
     * Toggle Status Cetak Kartu Pelajar.
     */
    public function toggleCetak(string $pesertaDidikId): array
    {
        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $pesertaDidikId)->first();
        if (!$pd) {
            return ['error' => 'not_found', 'message' => 'Peserta didik tidak ditemukan.'];
        }

        $record = KartuPelajarPembayaran::firstOrNew(['peserta_didik_id' => $pd->peserta_didik_id]);
        $record->nisn = $pd->nisn;

        $newStatus = ($record->status_cetak === 'sudah_cetak') ? 'belum_cetak' : 'sudah_cetak';
        $record->status_cetak = $newStatus;
        $record->tanggal_cetak = ($newStatus === 'sudah_cetak') ? now() : null;
        $record->save();

        return [
            'success' => true,
            'message' => "Status cetak kartu pelajar {$pd->nama} berhasil diubah menjadi: " . ($newStatus === 'sudah_cetak' ? 'Sudah Dicetak' : 'Belum Dicetak'),
            'status_cetak' => $newStatus,
            'tanggal_cetak' => $record->tanggal_cetak ? $record->tanggal_cetak->translatedFormat('d/m/Y H:i') : '-',
        ];
    }

    /**
     * Eksekusi aksi massal pembayaran / cetak kartu pelajar.
     */
    public function bulkAction(array $data): array
    {
        $settings = KartuPelajarPengaturan::getSettings();
        $action = $data['action'];
        $ids = $data['ids'];
        $count = 0;

        $statusBayar = $data['bulk_status_pembayaran'] ?? ($data['status_pembayaran'] ?? null);
        $nominal = !empty($data['bulk_nominal']) ? (int) $data['bulk_nominal'] : (!empty($data['nominal']) ? (int) $data['nominal'] : null);
        $metodeBayar = $data['bulk_metode_pembayaran'] ?? ($data['metode_pembayaran'] ?? 'tunai');
        $tanggalBayar = $data['bulk_tanggal_bayar'] ?? ($data['tanggal_bayar'] ?? now()->toDateString());
        $statusCetak = $data['bulk_status_cetak'] ?? ($data['status_cetak'] ?? null);
        $tanggalCetak = $data['bulk_tanggal_cetak'] ?? ($data['tanggal_cetak'] ?? now());
        $catatan = !empty($data['bulk_catatan']) ? $data['bulk_catatan'] : ($data['catatan'] ?? null);

        foreach ($ids as $pdId) {
            $pd = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
            if (!$pd) continue;

            $record = KartuPelajarPembayaran::firstOrNew(['peserta_didik_id' => $pd->peserta_didik_id]);
            $record->nisn = $pd->nisn;

            if ($action === 'set_lunas' || $statusBayar === 'lunas') {
                $record->status_pembayaran = 'lunas';
                $record->nominal = $nominal ?: ($record->nominal ?: (int) ($settings->biaya_standar ?? 25000));
                $record->metode_pembayaran = $metodeBayar;
                $record->tanggal_bayar = $tanggalBayar;
            } elseif ($action === 'set_belum_bayar' || $statusBayar === 'belum_bayar') {
                $record->status_pembayaran = 'belum_bayar';
                $record->tanggal_bayar = null;
            }

            if ($action === 'set_sudah_cetak' || $statusCetak === 'sudah_cetak') {
                $record->status_cetak = 'sudah_cetak';
                $record->tanggal_cetak = $tanggalCetak ? \Carbon\Carbon::parse($tanggalCetak) : now();
                if (empty($record->nominal)) {
                    $record->nominal = (int) ($settings->biaya_standar ?? 25000);
                }
            } elseif ($action === 'set_belum_cetak' || $statusCetak === 'belum_cetak') {
                $record->status_cetak = 'belum_cetak';
                $record->tanggal_cetak = null;
            }

            if ($catatan !== null && trim($catatan) !== '') {
                $record->catatan = trim($catatan);
            }

            $record->save();

            if (Schema::hasColumn('kartu_pelajar_request', 'metode_pembayaran')) {
                KartuPelajarRequest::where('peserta_didik_id', $pd->peserta_didik_id)
                    ->whereIn('status', ['menunggu', 'diproses'])
                    ->update(['metode_pembayaran' => $record->metode_pembayaran]);
            }

            $count++;
        }

        $labelAction = match ($action) {
            'set_lunas'       => 'ditandai Lunas',
            'set_belum_bayar' => 'ditandai Belum Bayar',
            'set_sudah_cetak' => 'ditandai Sudah Dicetak',
            'set_belum_cetak' => 'ditandai Belum Dicetak',
            default           => 'berhasil diperbarui data pembayarannya',
        };

        return [
            'success' => true,
            'message' => "Sebanyak {$count} peserta didik {$labelAction}.",
        ];
    }

    /**
     * Dapatkan data WhatsApp & QR Code.
     */
    public function getWaData(string $nisn, array $sekolahInfo): ?array
    {
        $pd = DB::table('peserta_didik')->where('nisn', $nisn)->first();
        if (!$pd) {
            return null;
        }

        $record = KartuPelajarPembayaran::where('peserta_didik_id', $pd->peserta_didik_id)->first();
        $rawPhone = $record?->nomor_wa ?: ($pd->nomor_telepon_seluler ?: $pd->nomor_telepon_rumah);
        $cleanPhone = KartuPelajarPembayaran::normalizePhoneNumber($rawPhone);
        $verifyUrl = route('kartu-pelajar.verify-short', ['nisn' => $pd->nisn]);

        $dataPayload = [
            'nama' => $pd->nama,
            'nisn' => $pd->nisn,
            'rombel' => $pd->nama_rombel ?: '-',
            'status_pembayaran' => $record?->status_pembayaran ?? 'belum_bayar',
            'nominal' => $record?->nominal ?? 25000,
            'metode_pembayaran' => $record?->metode_pembayaran ?? 'tunai',
            'tanggal_bayar' => $record?->tanggal_bayar,
            'status_cetak' => $record?->status_cetak ?? 'belum_cetak',
            'klaim_ganti_kartu' => $record?->klaim_ganti_kartu ?? 0,
            'klaim_stiker' => $record?->klaim_stiker ?? 0,
            'verify_url' => $verifyUrl,
        ];

        $pesan = KartuPelajarPembayaran::buildWhatsAppMessage($dataPayload, $sekolahInfo);
        $qrSvg = QrCodeService::generateSvg($verifyUrl, 160, 1);
        $qrDataUri = QrCodeService::generateDataUri($verifyUrl, 160, 1);

        return [
            'success' => true,
            'siswa' => [
                'peserta_didik_id' => $pd->peserta_didik_id,
                'nama' => $pd->nama,
                'nisn' => $pd->nisn,
                'rombel' => $pd->nama_rombel,
                'phone_raw' => $rawPhone,
                'phone_clean' => $cleanPhone,
            ],
            'pembayaran' => $record,
            'message' => $pesan,
            'verify_url' => $verifyUrl,
            'qr_svg' => $qrSvg,
            'qr_data_uri' => $qrDataUri,
            'wa_url' => $cleanPhone ? ('https://wa.me/' . $cleanPhone . '?text=' . rawurlencode($pesan)) : null,
        ];
    }

    /**
     * Log waktu pengiriman pesan WhatsApp.
     */
    public function logWa(string $pesertaDidikId, ?string $nomorWa): array
    {
        $record = KartuPelajarPembayaran::firstOrNew(['peserta_didik_id' => $pesertaDidikId]);
        if (!empty($nomorWa)) {
            $record->nomor_wa = KartuPelajarPembayaran::normalizePhoneNumber($nomorWa);
        }
        $record->wa_terkirim_at = now();
        $record->save();

        return [
            'success' => true,
            'message' => 'Status pengiriman WhatsApp berhasil diperbarui.',
            'wa_terkirim_at' => $record->wa_terkirim_at->translatedFormat('d/m/Y H:i'),
        ];
    }

    /**
     * Update Pengaturan Kartu Pelajar.
     */
    public function updatePengaturan(Request $request, mixed $user): array
    {
        $subtab = $request->input('subtab', 'harga');
        $settings = KartuPelajarPengaturan::getSettings();
        $userName = is_array($user) ? ($user['nama'] ?? 'Admin') : ($user->nama ?? 'Admin');

        if ($subtab === 'harga') {
            $request->validate([
                'biaya_standar'        => 'required|numeric|min:0',
                'bank_nama'            => 'nullable|string|max:100',
                'bank_rekening'        => 'nullable|string|max:100',
                'bank_atas_nama'       => 'nullable|string|max:150',
                'instruksi_pembayaran' => 'nullable|string|max:2000',
                'qris_image'           => 'nullable|file|mimes:png,jpg,jpeg,webp|max:5120',
            ]);

            $settings->biaya_standar = (int) $request->biaya_standar;
            $settings->bank_nama = trim((string) $request->bank_nama);
            $settings->bank_rekening = trim((string) $request->bank_rekening);
            $settings->bank_atas_nama = trim((string) $request->bank_atas_nama);
            $settings->instruksi_pembayaran = trim((string) $request->instruksi_pembayaran);

            if ($request->hasFile('qris_image')) {
                if ($settings->qris_image_path && Storage::disk('public')->exists($settings->qris_image_path)) {
                    Storage::disk('public')->delete($settings->qris_image_path);
                }
                $ext = $request->file('qris_image')->getClientOriginalExtension();
                $path = $request->file('qris_image')->storeAs('qris_kartu', 'qris_pembayaran_' . time() . '.' . $ext, 'public');
                $settings->qris_image_path = $path;
            } elseif ($request->boolean('hapus_qris')) {
                if ($settings->qris_image_path && Storage::disk('public')->exists($settings->qris_image_path)) {
                    Storage::disk('public')->delete($settings->qris_image_path);
                }
                $settings->qris_image_path = null;
            }

            $settings->updated_by = $userName;
            $settings->save();

            return [
                'tab' => 'harga',
                'message' => 'Pengaturan harga standar, rekening pembayaran, dan gambar QRIS berhasil diperbarui.',
            ];
        }

        if ($subtab === 'whatsapp') {
            if ($request->boolean('reset_template')) {
                $settings->template_wa = KartuPelajarPengaturan::getDefaultWaTemplate();
            } else {
                $request->validate([
                    'template_wa' => 'required|string',
                ]);
                $settings->template_wa = trim($request->template_wa);
            }

            $settings->updated_by = $userName;
            $settings->save();

            return [
                'tab' => 'whatsapp',
                'message' => 'Template pesan otomatis WhatsApp berhasil diperbarui.',
            ];
        }

        if ($subtab === 'garansi') {
            $request->validate([
                'kuota_ganti_kartu_full' => 'required|integer|min:0|max:10',
                'kuota_ganti_stiker'     => 'required|integer|min:0|max:20',
                'ketentuan_garansi'      => 'nullable|string|max:3000',
            ]);

            $settings->kuota_ganti_kartu_full = (int) $request->kuota_ganti_kartu_full;
            $settings->kuota_ganti_stiker = (int) $request->kuota_ganti_stiker;
            $settings->ketentuan_garansi = trim((string) $request->ketentuan_garansi);
            $settings->updated_by = $userName;
            $settings->save();

            return [
                'tab' => 'garansi',
                'message' => 'Ketentuan dan kuota garansi kartu pelajar berhasil diperbarui.',
            ];
        }

        return ['tab' => null, 'message' => null];
    }

    /**
     * Catat Klaim Garansi Kartu Pelajar Siswa.
     */
    public function klaimGaransi(string $pesertaDidikId, string $jenisKlaim, string $alasan, mixed $user): array
    {
        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $pesertaDidikId)->first();
        if (!$pd) {
            return ['error' => 'not_found', 'message' => 'Peserta didik tidak ditemukan.', 'status_code' => 404];
        }

        $settings = KartuPelajarPengaturan::getSettings();
        $record = KartuPelajarPembayaran::firstOrNew(['peserta_didik_id' => $pd->peserta_didik_id]);
        $record->nisn = $pd->nisn;

        // 1. Validasi: Garansi resmi hanya berlaku selama berstatus Peserta Didik Aktif
        $isAktif = DB::table('peserta_didik')->where('peserta_didik_id', $pd->peserta_didik_id)->exists()
            && (!Schema::hasTable('peserta_didik_tidak_aktif') || !DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $pd->peserta_didik_id)->exists());

        if (!$isAktif) {
            return [
                'error' => 'invalid_state',
                'message' => "Garansi kartu pelajar tidak berlaku. Fasilitas garansi hanya berlaku selama siswa berstatus Peserta Didik Aktif di sekolah.",
                'status_code' => 422,
            ];
        }

        // 2. Validasi: Garansi resmi otomatis aktif hanya jika status kartu sudah dicetak
        if ($record->status_cetak !== 'sudah_cetak') {
            return [
                'error' => 'invalid_state',
                'message' => "Garansi kartu pelajar belum berlaku karena kartu fisik belum dicetak (Status masih Belum Dicetak).",
                'status_code' => 422,
            ];
        }

        $userName = is_array($user) ? ($user['nama'] ?? 'Admin') : ($user->nama ?? 'Admin');

        if ($jenisKlaim === 'ganti_kartu_full') {
            $maxKuota = $settings->kuota_ganti_kartu_full ?? 1;
            if ($record->klaim_ganti_kartu >= $maxKuota) {
                return [
                    'error' => 'quota_exceeded',
                    'message' => "Kuota garansi penggantian kartu penuh untuk {$pd->nama} sudah habis (maksimal {$maxKuota} kali).",
                    'status_code' => 422,
                ];
            }
            $record->klaim_ganti_kartu += 1;
            $record->status_cetak = 'sudah_cetak';
            $record->tanggal_cetak = now();
            $labelKlaim = "1x Ganti Kartu Fisik Full (PVC/RFID & Stiker Baru)";
        } else {
            $maxKuota = $settings->kuota_ganti_stiker ?? 3;
            if ($record->klaim_stiker >= $maxKuota) {
                return [
                    'error' => 'quota_exceeded',
                    'message' => "Kuota garansi cetak stiker kartu untuk {$pd->nama} sudah habis (maksimal {$maxKuota} kali).",
                    'status_code' => 422,
                ];
            }
            $record->klaim_stiker += 1;
            $labelKlaim = "Cetak Ulang Stiker Kartu Pelajar";
        }

        $record->save();

        DB::table('kartu_pelajar_garansi_log')->insert([
            'peserta_didik_id' => $pd->peserta_didik_id,
            'nisn'             => $pd->nisn,
            'jenis_klaim'      => $jenisKlaim,
            'alasan'           => trim($alasan),
            'tanggal_klaim'    => now(),
            'petugas'          => $userName,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return [
            'success' => true,
            'message' => "Klaim garansi [{$labelKlaim}] untuk {$pd->nama} berhasil dicatat.",
            'klaim_ganti_kartu' => $record->klaim_ganti_kartu,
            'klaim_stiker' => $record->klaim_stiker,
        ];
    }
}
