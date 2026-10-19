<?php

namespace App\Http\Controllers;

use App\Models\JurusanMeta;
use App\Models\KartuPelajarPembayaran;
use App\Models\KartuPelajarPengaturan;
use App\Models\KartuPelajarPengeluaran;
use App\Models\KartuPelajarRequest;
use App\Models\PesertaDidikMeta;
use App\Models\RolePermission;
use App\Models\SekolahMeta;
use App\Services\BarcodeService;
use App\Services\QrCodeService;
use App\Support\SemesterHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class KartuPelajarController extends Controller
{
    /**
     * Halaman Manajemen Kartu Pelajar, Pembayaran, Status Cetak & Notifikasi WhatsApp (Admin Only)
     */
    public function manageIndex(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'read')) {
            abort(403, 'Akses ditolak: Menu ini khusus untuk Administrator.');
        }

        $tab = $request->get('tab', 'keuangan'); // 'keuangan', 'request', 'berhasil-cetak', 'harga', 'whatsapp', 'garansi'
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

        // Filter tab Berhasil Cetak: HANYA menampilkan peserta didik yang sudah selesai cetak
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

        // Cegah halaman kosong akibat perubahan filter / perPage saat berada di page > 1
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

        // Format foto URL dan nomor WA
        $students->getCollection()->transform(function ($item) {
            $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
            $rawWa = $item->nomor_wa ?: ($item->nomor_telepon_seluler ?: $item->nomor_telepon_rumah);
            $item->wa_clean = KartuPelajarPembayaran::normalizePhoneNumber($rawWa);
            return $item;
        });

        // Hitung statistik live sesuai filter yang aktif di datatable Berhasil Cetak
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

        // Daftar Rombel untuk Filter
        $rombelList = DB::table('rombongan_belajar')
            ->where('jenis_rombel', '1')
            ->orderBy('tingkat_pendidikan_id', 'asc')
            ->orderBy('nama', 'asc')
            ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id')
            ->get();

        // Riwayat Log Garansi jika tab garansi aktif
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

        // Data Catatan Keuangan Kartu Pelajar (Pemasukan Siswa, Pengeluaran Bahan/Alat, Saldo Bersih, Piutang)
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
        $chartKeuangan = [
            'labels'          => [],
            'pemasukan'       => [],
            'pengeluaran'     => [],
            'kategori_labels' => [],
            'kategori_values' => [],
        ];

        if ($tab === 'keuangan') {
            if (Schema::hasTable('kartu_pelajar_pengeluaran')) {
                $pQuery = DB::table('kartu_pelajar_pengeluaran');
                if ($q !== '') {
                    $pQuery->where(function ($sub) use ($q) {
                        $sub->where('judul', 'LIKE', "%{$q}%")
                            ->orWhere('kategori', 'LIKE', "%{$q}%")
                            ->orWhere('keterangan', 'LIKE', "%{$q}%")
                            ->orWhere('petugas', 'LIKE', "%{$q}%");
                    });
                }
                $pengeluaranList = $pQuery->orderByDesc('tanggal')->orderByDesc('id')->paginate(10, ['*'], 'pengeluaran_page')->withQueryString();
            }

            $recentPemasukan = DB::table('kartu_pelajar_pembayaran as kp')
                ->join('peserta_didik as pd', 'kp.peserta_didik_id', '=', 'pd.peserta_didik_id')
                ->where('kp.status_pembayaran', 'lunas')
                ->select('kp.*', 'pd.nama', 'pd.nama_rombel', 'pd.nisn')
                ->orderByDesc('kp.tanggal_bayar')
                ->orderByDesc('kp.updated_at')
                ->limit(10)
                ->get();

            // Time series 6 bulan terakhir untuk chart bar pemasukan vs pengeluaran
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

            // Kategori Pengeluaran untuk Chart Doughnut
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
        $sekolahInfo = $this->getSekolahInfo();

        // Tab Request Cetak Kartu Pelajar
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

        return view('dashboard.kartu-pelajar.index', compact(
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
            'chartKeuangan',
            'kategoriList',
            'requestList',
            'requestStats',
            'reqStatus'
        ));
    }

    /**
     * Update Catatan Pembayaran Kartu Pelajar (Single)
     */
    public function updatePembayaran(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'update')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'peserta_didik_id'   => 'required|string',
            'status_pembayaran'  => 'required|in:belum_bayar,lunas',
            'nominal'            => 'nullable|numeric|min:0',
            'metode_pembayaran'  => 'nullable|string|max:50',
            'tanggal_bayar'      => 'nullable|date',
            'status_cetak'       => 'nullable|in:belum_cetak,sudah_cetak',
            'nomor_wa'           => 'nullable|string|max:30',
            'catatan'            => 'nullable|string|max:500',
        ]);

        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $request->peserta_didik_id)->first();
        if (!$pd) {
            return response()->json(['success' => false, 'message' => 'Peserta didik tidak ditemukan.'], 404);
        }

        $statusBayar = $request->status_pembayaran;
        $statusCetak = $request->status_cetak ?: 'belum_cetak';
        $nominal = $request->filled('nominal') ? (int) $request->nominal : 25000;
        $tanggalBayar = $statusBayar === 'lunas' ? ($request->tanggal_bayar ?: now()->toDateString()) : null;
        $tanggalCetak = $statusCetak === 'sudah_cetak' ? now() : null;

        $record = KartuPelajarPembayaran::updateOrCreate(
            ['peserta_didik_id' => $pd->peserta_didik_id],
            [
                'nisn'              => $pd->nisn,
                'status_pembayaran' => $statusBayar,
                'nominal'           => $nominal,
                'metode_pembayaran' => $statusBayar === 'lunas' ? ($request->metode_pembayaran ?: 'tunai') : null,
                'tanggal_bayar'     => $tanggalBayar,
                'status_cetak'      => $statusCetak,
                'tanggal_cetak'     => $tanggalCetak,
                'nomor_wa'          => $request->nomor_wa ? KartuPelajarPembayaran::normalizePhoneNumber($request->nomor_wa) : null,
                'catatan'           => $request->catatan,
                'created_by'        => is_array($user) ? ($user['nama'] ?? 'Admin') : ($user->nama ?? 'Admin'),
            ]
        );

        if (Schema::hasColumn('kartu_pelajar_request', 'metode_pembayaran')) {
            KartuPelajarRequest::where('peserta_didik_id', $pd->peserta_didik_id)
                ->whereIn('status', ['menunggu', 'diproses'])
                ->update(['metode_pembayaran' => $record->metode_pembayaran]);
        }

        $sekolahInfo = $this->getSekolahInfo();
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

        return response()->json([
            'success' => true,
            'message' => "Data pembayaran kartu pelajar untuk {$pd->nama} berhasil disimpan.",
            'data' => $record,
            'wa_clean' => $cleanWa,
            'wa_message' => $pesanWa,
            'wa_url' => $waUrl,
        ]);
    }

    /**
     * Toggle Status Cetak Kartu Pelajar (Sudah Cetak / Belum Cetak)
     */
    public function toggleCetak(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'update')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'peserta_didik_id' => 'required|string',
        ]);

        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $request->peserta_didik_id)->first();
        if (!$pd) {
            return response()->json(['success' => false, 'message' => 'Peserta didik tidak ditemukan.'], 404);
        }

        $record = KartuPelajarPembayaran::firstOrNew(['peserta_didik_id' => $pd->peserta_didik_id]);
        $record->nisn = $pd->nisn;

        $newStatus = ($record->status_cetak === 'sudah_cetak') ? 'belum_cetak' : 'sudah_cetak';
        $record->status_cetak = $newStatus;
        $record->tanggal_cetak = ($newStatus === 'sudah_cetak') ? now() : null;
        $record->save();

        return response()->json([
            'success' => true,
            'message' => "Status cetak kartu pelajar {$pd->nama} berhasil diubah menjadi: " . ($newStatus === 'sudah_cetak' ? 'Sudah Dicetak' : 'Belum Dicetak'),
            'status_cetak' => $newStatus,
            'tanggal_cetak' => $record->tanggal_cetak ? $record->tanggal_cetak->translatedFormat('d/m/Y H:i') : '-',
        ]);
    }

    /**
     * Aksi Massal (Bulk Action): Tandai Lunas atau Tandai Cetak
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'update')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'action'                 => 'required|in:set_lunas,set_belum_bayar,set_sudah_cetak,set_belum_cetak,custom_bulk',
            'ids'                    => 'required|array|min:1',
            'status_pembayaran'      => 'nullable|in:lunas,belum_bayar,keep',
            'bulk_status_pembayaran' => 'nullable|in:lunas,belum_bayar,keep',
            'nominal'                => 'nullable|numeric|min:0',
            'bulk_nominal'           => 'nullable|numeric|min:0',
            'metode_pembayaran'      => 'nullable|string|max:50',
            'bulk_metode_pembayaran' => 'nullable|string|max:50',
            'tanggal_bayar'          => 'nullable|date',
            'bulk_tanggal_bayar'     => 'nullable|date',
            'status_cetak'           => 'nullable|in:sudah_cetak,belum_cetak,keep',
            'bulk_status_cetak'      => 'nullable|in:sudah_cetak,belum_cetak,keep',
            'tanggal_cetak'          => 'nullable|date',
            'bulk_tanggal_cetak'     => 'nullable|date',
            'catatan'                => 'nullable|string|max:500',
            'bulk_catatan'           => 'nullable|string|max:500',
        ]);

        $settings = KartuPelajarPengaturan::getSettings();
        $action = $request->action;
        $ids = $request->ids;
        $count = 0;

        $statusBayar = $request->bulk_status_pembayaran ?: $request->status_pembayaran;
        $nominal = $request->filled('bulk_nominal') ? (int) $request->bulk_nominal : ($request->filled('nominal') ? (int) $request->nominal : null);
        $metodeBayar = $request->bulk_metode_pembayaran ?: ($request->metode_pembayaran ?: 'tunai');
        $tanggalBayar = $request->bulk_tanggal_bayar ?: ($request->tanggal_bayar ?: now()->toDateString());
        $statusCetak = $request->bulk_status_cetak ?: $request->status_cetak;
        $tanggalCetak = $request->bulk_tanggal_cetak ?: ($request->tanggal_cetak ?: now());
        $catatan = $request->filled('bulk_catatan') ? $request->bulk_catatan : $request->catatan;

        foreach ($ids as $pdId) {
            $pd = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
            if (!$pd) continue;

            $record = KartuPelajarPembayaran::firstOrNew(['peserta_didik_id' => $pd->peserta_didik_id]);
            $record->nisn = $pd->nisn;

            // Update status pembayaran jika ada perubahan
            if ($action === 'set_lunas' || $statusBayar === 'lunas') {
                $record->status_pembayaran = 'lunas';
                $record->nominal = $nominal ?: ($record->nominal ?: (int) ($settings->biaya_standar ?? 25000));
                $record->metode_pembayaran = $metodeBayar;
                $record->tanggal_bayar = $tanggalBayar;
            } elseif ($action === 'set_belum_bayar' || $statusBayar === 'belum_bayar') {
                $record->status_pembayaran = 'belum_bayar';
                $record->tanggal_bayar = null;
            }

            // Update status cetak fisik jika ada perubahan
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

            // Catatan khusus
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

        return response()->json([
            'success' => true,
            'message' => "Sebanyak {$count} peserta didik {$labelAction}.",
        ]);
    }

    /**
     * Dapatkan data WhatsApp & QR Code untuk modal kirim pesan WhatsApp
     */
    public function getWaData(Request $request, string $nisn): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'read')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $pd = DB::table('peserta_didik')->where('nisn', $nisn)->first();
        if (!$pd) {
            return response()->json(['success' => false, 'message' => 'Peserta didik tidak ditemukan.'], 404);
        }

        $record = KartuPelajarPembayaran::where('peserta_didik_id', $pd->peserta_didik_id)->first();
        $sekolahInfo = $this->getSekolahInfo();

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

        return response()->json([
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
        ]);
    }

    /**
     * Log waktu pengiriman pesan WhatsApp ke riwayat pembayaran
     */
    public function logWa(Request $request): JsonResponse
    {
        $request->validate([
            'peserta_didik_id' => 'required|string',
            'nomor_wa'         => 'nullable|string',
        ]);

        $record = KartuPelajarPembayaran::firstOrNew(['peserta_didik_id' => $request->peserta_didik_id]);
        if ($request->filled('nomor_wa')) {
            $record->nomor_wa = KartuPelajarPembayaran::normalizePhoneNumber($request->nomor_wa);
        }
        $record->wa_terkirim_at = now();
        $record->save();

        return response()->json([
            'success' => true,
            'message' => 'Status pengiriman WhatsApp berhasil diperbarui.',
            'wa_terkirim_at' => $record->wa_terkirim_at->translatedFormat('d/m/Y H:i'),
        ]);
    }

    /**
     * Download langsung file QR Code Kartu Pelajar (SVG)
     */
    public function downloadQrSvg(string $nisn)
    {
        $verifyUrl = route('kartu-pelajar.verify-short', ['nisn' => $nisn]);
        $svg = QrCodeService::generateSvg($verifyUrl, 300, 2);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="QR_Kartu_Pelajar_' . $nisn . '.svg"',
        ]);
    }

    /**
     * Update Pengaturan Kartu Pelajar (Harga & QRIS, Template WhatsApp, Kuota Garansi)
     */
    public function updatePengaturan(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'update')) {
            abort(403, 'Akses ditolak.');
        }

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

            // Handle QRIS Image Upload
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

            return redirect()->route('dashboard.kartu-pelajar.index', ['tab' => 'harga'])
                ->with('success', 'Pengaturan harga standar, rekening pembayaran, dan gambar QRIS berhasil diperbarui.');
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

            return redirect()->route('dashboard.kartu-pelajar.index', ['tab' => 'whatsapp'])
                ->with('success', 'Template pesan otomatis WhatsApp berhasil diperbarui.');
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

            return redirect()->route('dashboard.kartu-pelajar.index', ['tab' => 'garansi'])
                ->with('success', 'Ketentuan dan kuota garansi kartu pelajar berhasil diperbarui.');
        }

        return redirect()->route('dashboard.kartu-pelajar.index');
    }

    /**
     * Catat Klaim Garansi Kartu Pelajar Siswa
     */
    public function klaimGaransi(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'update')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'peserta_didik_id' => 'required|string',
            'jenis_klaim'      => 'required|in:ganti_kartu_full,cetak_stiker',
            'alasan'           => 'required|string|max:500',
        ]);

        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $request->peserta_didik_id)->first();
        if (!$pd) {
            return response()->json(['success' => false, 'message' => 'Peserta didik tidak ditemukan.'], 404);
        }

        $settings = KartuPelajarPengaturan::getSettings();
        $record = KartuPelajarPembayaran::firstOrNew(['peserta_didik_id' => $pd->peserta_didik_id]);
        $record->nisn = $pd->nisn;

        // 1. Validasi: Garansi resmi hanya berlaku selama berstatus Peserta Didik Aktif
        $isAktif = DB::table('peserta_didik')->where('peserta_didik_id', $pd->peserta_didik_id)->exists()
            && (!Schema::hasTable('peserta_didik_tidak_aktif') || !DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $pd->peserta_didik_id)->exists());

        if (!$isAktif) {
            return response()->json([
                'success' => false,
                'message' => "Garansi kartu pelajar tidak berlaku. Fasilitas garansi hanya berlaku selama siswa berstatus Peserta Didik Aktif di sekolah."
            ], 422);
        }

        // 2. Validasi: Garansi resmi otomatis aktif hanya jika status kartu sudah dicetak
        if ($record->status_cetak !== 'sudah_cetak') {
            return response()->json([
                'success' => false,
                'message' => "Garansi kartu pelajar belum berlaku karena kartu fisik belum dicetak (Status masih Belum Dicetak)."
            ], 422);
        }

        $jenisKlaim = $request->jenis_klaim;
        $userName = is_array($user) ? ($user['nama'] ?? 'Admin') : ($user->nama ?? 'Admin');

        if ($jenisKlaim === 'ganti_kartu_full') {
            $maxKuota = $settings->kuota_ganti_kartu_full ?? 1;
            if ($record->klaim_ganti_kartu >= $maxKuota) {
                return response()->json([
                    'success' => false,
                    'message' => "Kuota garansi penggantian kartu penuh untuk {$pd->nama} sudah habis (maksimal {$maxKuota} kali)."
                ], 422);
            }
            $record->klaim_ganti_kartu += 1;
            $record->status_cetak = 'sudah_cetak';
            $record->tanggal_cetak = now();
            $labelKlaim = "1x Ganti Kartu Fisik Full (PVC/RFID & Stiker Baru)";
        } else {
            $maxKuota = $settings->kuota_ganti_stiker ?? 3;
            if ($record->klaim_stiker >= $maxKuota) {
                return response()->json([
                    'success' => false,
                    'message' => "Kuota garansi cetak stiker kartu untuk {$pd->nama} sudah habis (maksimal {$maxKuota} kali)."
                ], 422);
            }
            $record->klaim_stiker += 1;
            $labelKlaim = "Cetak Ulang Stiker Kartu Pelajar";
        }

        $record->save();

        // Catat ke tabel log garansi
        DB::table('kartu_pelajar_garansi_log')->insert([
            'peserta_didik_id' => $pd->peserta_didik_id,
            'nisn'             => $pd->nisn,
            'jenis_klaim'      => $jenisKlaim,
            'alasan'           => trim($request->alasan),
            'tanggal_klaim'    => now(),
            'petugas'          => $userName,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Klaim garansi [{$labelKlaim}] untuk {$pd->nama} berhasil dicatat.",
            'klaim_ganti_kartu' => $record->klaim_ganti_kartu,
            'klaim_stiker' => $record->klaim_stiker,
        ]);
    }

    /**
     * Halaman Publik Verifikasi Resmi Kartu Pelajar (Direct scan dari QR code)
     */
    public function verify(Request $request, string $nisn)
    {
        $nisn = trim($nisn);
        $card = $this->loadStudentCardData($nisn);

        if (!$card) {
            return view('kartu-pelajar.verifikasi-notfound', [
                'nisn' => $nisn,
                'sekolah' => $this->getSekolahInfo(),
            ]);
        }

        return view('kartu-pelajar.verifikasi', [
            'card' => $card,
            'verifiedAt' => now()->translatedFormat('d F Y, H:i:s') . ' WIB',
        ]);
    }

    /**
     * Data JSON / Preview untuk Modal Kartu Pelajar Interaktif
     */
    public function preview(Request $request, string $nisn)
    {
        $nisn = trim($nisn);
        $user = session('user');
        if ($user) {
            $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
            $userNisn = is_array($user) ? ($user['nisn'] ?? '') : ($user->nisn ?? '');

            // Jika peserta didik atau orang tua, hanya boleh melihat pratinjau kartu miliknya sendiri (kecuali Koordinator Kelas untuk kelas binaannya)
            if (in_array($role, ['peserta_didik', 'orang_tua'], true) && $userNisn !== $nisn) {
                $isKoord = ($role === 'peserta_didik') && \App\Models\RolePermission::isKoordinator($user);
                if (!$isKoord) {
                    return response()->json(['success' => false, 'message' => 'Akses ditolak: Anda hanya berhak melihat kartu milik Anda sendiri.'], 403);
                }
            }

            // Jika guru atau tendik, harus Administrator atau Wali Kelas
            if (in_array($role, ['guru', 'tendik'], true) && !\App\Models\RolePermission::isWaliKelasOrAdmin($user)) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak: Hanya Administrator dan Wali Kelas yang berwenang membuka kartu pelajar.'], 403);
            }
        }

        $card = $this->loadStudentCardData($nisn);

        if (!$card) {
            return response()->json(['success' => false, 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'card' => $card,
                'html' => view('kartu-pelajar.template', ['card' => $card, 'isPrint' => false])->render(),
            ]);
        }

        return view('kartu-pelajar.single-preview', ['card' => $card]);
    }

    /**
     * Cetak Kartu Pelajar Tunggal
     */
    public function printSingle(Request $request, string $nisn)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $nisn = trim($nisn);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $userNisn = is_array($user) ? ($user['nisn'] ?? '') : ($user->nisn ?? '');

        // Peserta didik hanya berwenang mencetak kartu miliknya sendiri (kecuali Koordinator Kelas)
        if ($role === 'peserta_didik') {
            $isKoord = \App\Models\RolePermission::isKoordinator($user);
            if ($userNisn !== $nisn && !$isKoord) {
                abort(403, 'Akses ditolak: Anda hanya dapat mencetak kartu pelajar milik Anda sendiri.');
            }
        } elseif (!\App\Models\RolePermission::isWaliKelasOrAdmin($user)) {
            abort(403, 'Akses ditolak: Hanya Administrator dan Wali Kelas yang memiliki hak akses cetak kartu pelajar.');
        }

        $card = $this->loadStudentCardData($nisn);

        if (!$card) {
            abort(404, 'Data peserta didik tidak ditemukan.');
        }

        return view('kartu-pelajar.cetak-single', [
            'card' => $card,
        ]);
    }

    /**
     * Cetak Masal Kartu Pelajar per Rombongan Belajar (Rombel)
     */
    public function printRombel(Request $request, string $rombelId)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        if (!\App\Models\RolePermission::isWaliKelasOrAdmin($user, $rombelId) && !\App\Models\RolePermission::isWaliKelasOrAdmin($user)) {
            abort(403, 'Akses ditolak: Hanya Administrator dan Wali Kelas yang diizinkan mencetak kartu pelajar masal.');
        }

        $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $rombelId)->first();
        if (!$rombel) {
            // Cek jika yang dikirim adalah nama rombel
            $rombel = DB::table('rombongan_belajar')->where('nama', $rombelId)->first();
        }

        if (!$rombel) {
            abort(404, 'Rombongan belajar tidak ditemukan.');
        }

        $students = DB::table('peserta_didik')
            ->where('rombongan_belajar_id', $rombel->rombongan_belajar_id)
            ->orderBy('nama', 'asc')
            ->get();

        if ($students->isEmpty()) {
            // Cek berdasarkan nama rombel
            $students = DB::table('peserta_didik')
                ->where('nama_rombel', $rombel->nama)
                ->orderBy('nama', 'asc')
                ->get();
        }

        $sekolahInfo = $this->getSekolahInfo();
        $jurusanMeta = $this->getJurusanMeta($rombel->jurusan_id);

        $cards = [];
        $pdIds = $students->pluck('peserta_didik_id')->all();
        $photos = [];
        if (Schema::hasTable('peserta_didik_meta')) {
            $photos = PesertaDidikMeta::whereIn('peserta_didik_id', $pdIds)
                ->get()
                ->keyBy('peserta_didik_id');
        }

        $semesterLabel = SemesterHelper::getActiveSemesterLabel();

        foreach ($students as $pd) {
            $photoMeta = $photos[$pd->peserta_didik_id] ?? null;
            $verifyUrl = route('kartu-pelajar.verify-short', ['nisn' => $pd->nisn]);

            $cards[] = [
                'pd' => $pd,
                'nisn' => $pd->nisn,
                'nama' => $pd->nama,
                'rombel' => $pd->nama_rombel ?: $rombel->nama,
                'jurusan' => $jurusanMeta?->nama_jurusan ?: ($rombel->jurusan_id_str ?? 'Reguler'),
                'jurusan_logo_url' => $jurusanMeta?->logo_url,
                'foto_url' => $photoMeta?->foto_url,
                'tahun_pelajaran' => 'TP. ' . $semesterLabel,
                'status' => 'AKTIF',
                'qr_code_svg' => QrCodeService::generateSvg($verifyUrl, 100, 0),
                'barcode_svg' => BarcodeService::generateCode128Svg($pd->nisn ?: '0000000000', 36, 2),
                'verify_url' => $verifyUrl,
                'sekolah' => $sekolahInfo,
            ];
        }

        return view('kartu-pelajar.cetak-rombel', [
            'rombel' => $rombel,
            'cards' => $cards,
            'sekolah' => $sekolahInfo,
        ]);
    }

    /**
     * Dapatkan data lengkap satu peserta didik untuk kartu pelajar
     */
    private function loadStudentCardData(string $nisn): ?array
    {
        if (!Schema::hasTable('peserta_didik')) {
            return null;
        }

        $pd = DB::table('peserta_didik')
            ->where('nisn', $nisn)
            ->first();

        if (!$pd) {
            return null;
        }

        // Ambil rombel
        $rombel = null;
        if (!empty($pd->rombongan_belajar_id)) {
            $rombel = DB::table('rombongan_belajar')
                ->where('rombongan_belajar_id', $pd->rombongan_belajar_id)
                ->first();
        }
        if (!$rombel && !empty($pd->nama_rombel)) {
            $rombel = DB::table('rombongan_belajar')
                ->where('nama', $pd->nama_rombel)
                ->first();
        }

        // Ambil jurusan & logo jurusan
        $jurusanId = $rombel?->jurusan_id;
        $jurusanMeta = $this->getJurusanMeta($jurusanId);

        // Ambil foto siswa
        $fotoUrl = null;
        if (Schema::hasTable('peserta_didik_meta')) {
            $meta = PesertaDidikMeta::where('peserta_didik_id', $pd->peserta_didik_id)->first();
            $fotoUrl = $meta?->foto_url;
        }

        $sekolahInfo = $this->getSekolahInfo();
        $verifyUrl = route('kartu-pelajar.verify-short', ['nisn' => $pd->nisn]);
        $semesterLabel = SemesterHelper::getActiveSemesterLabel();

        return [
            'pd' => $pd,
            'nisn' => $pd->nisn,
            'nipd' => $pd->nipd ?? '-',
            'nama' => $pd->nama,
            'rombel' => $pd->nama_rombel ?: ($rombel?->nama ?? '-'),
            'jurusan' => $jurusanMeta?->nama_jurusan ?: ($rombel?->jurusan_id_str ?? 'Umum'),
            'jurusan_logo_url' => $jurusanMeta?->logo_url,
            'foto_url' => $fotoUrl,
            'tahun_pelajaran' => 'TP. ' . $semesterLabel,
            'status' => 'AKTIF',
            'qr_code_svg' => QrCodeService::generateSvg($verifyUrl, 120, 0),
            'barcode_svg' => BarcodeService::generateCode128Svg($pd->nisn ?: '0000000000', 36, 2),
            'verify_url' => $verifyUrl,
            'sekolah' => $sekolahInfo,
        ];
    }

    /**
     * Dapatkan metadata jurusan
     */
    private function getJurusanMeta(?string $jurusanId): ?JurusanMeta
    {
        if (empty($jurusanId) || !Schema::hasTable('jurusan_meta')) {
            return null;
        }

        $meta = JurusanMeta::where('jurusan_id', $jurusanId)->first();
        if (!$meta) {
            // Coba cari jika id jurusan memiliki prefix
            $meta = JurusanMeta::where('jurusan_id', 'LIKE', substr($jurusanId, 0, 5) . '%')->first();
        }

        return $meta;
    }

    /**
     * Dapatkan informasi dan logo sekolah
     */
    private function getSekolahInfo(): array
    {
        $sekolah = null;
        if (Schema::hasTable('sekolah')) {
            $sekolah = DB::table('sekolah')->first();
        }

        $sekolahMeta = null;
        if (Schema::hasTable('sekolah_meta')) {
            $sekolahMeta = SekolahMeta::first();
        }

        $alamatLengkap = collect([
            $sekolah?->alamat_jalan,
            $sekolah?->desa_kelurahan,
            $sekolah?->kecamatan,
            $sekolah?->kabupaten_kota,
        ])->filter()->implode(', ');

        return [
            'nama' => $sekolah?->nama ?: 'SMK NEGERI 1 PAGELARAN',
            'npsn' => $sekolah?->npsn ?: '20252031',
            'alamat' => $alamatLengkap ?: 'Jl. Raya Pasirpari, Sindangkerta, Kec. Pagelaran, Kab. Cianjur',
            'telepon' => $sekolah?->nomor_telepon ?: '-',
            'email' => $sekolah?->email ?: 'info@sekolah.sch.id',
            'website' => $sekolah?->website ?: 'https://smkn1pagelaran.sch.id',
            'logo_url' => $sekolahMeta?->logo_url ?: asset('img/logo-icon.png'),
            'kop_url' => $sekolahMeta?->kop_url,
        ];
    }

    /**
     * Simpan Catatan Pengeluaran Baru (Alat / Bahan Cetak Kartu Pelajar)
     */
    public function storePengeluaran(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'create')) {
            abort(403, 'Akses ditolak.');
        }

        // Normalisasi format nominal (membersihkan Rp, titik ribuan, koma, spasi)
        if ($request->has('nominal')) {
            $rawNominal = preg_replace('/[^0-9]/', '', (string) $request->nominal);
            $request->merge([
                'nominal' => $rawNominal !== '' ? (int) $rawNominal : null,
            ]);
        }

        // Normalisasi jumlah / kuantitas jika ada
        if ($request->has('jumlah')) {
            $rawJumlah = preg_replace('/[^0-9]/', '', (string) $request->jumlah);
            $request->merge([
                'jumlah' => $rawJumlah !== '' ? max(1, (int) $rawJumlah) : 1,
            ]);
        }

        $request->validate([
            'judul'           => 'required|string|max:255',
            'kategori'        => 'required|string|max:50',
            'jumlah'          => 'nullable|integer|min:1|max:10000000',
            'nominal'         => 'required|numeric|min:1',
            'tanggal'         => 'required|date',
            'keterangan'      => 'nullable|string|max:1000',
            'bukti_nota'      => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
        ], [
            'judul.required'    => 'Uraian / judul pembelanjaan wajib diisi.',
            'kategori.required' => 'Kategori pengeluaran wajib dipilih.',
            'nominal.required'  => 'Nominal pengeluaran wajib diisi.',
            'nominal.numeric'   => 'Nominal pengeluaran harus berupa angka.',
            'nominal.min'       => 'Nominal pengeluaran minimal Rp 1.',
            'tanggal.required'  => 'Tanggal pengeluaran wajib diisi.',
            'bukti_nota.mimes'  => 'Bukti nota harus berupa berkas gambar (JPG, PNG, WebP) atau PDF.',
            'bukti_nota.max'    => 'Ukuran berkas bukti nota maksimal 5 MB.',
        ]);

        $userName = is_array($user) ? ($user['nama'] ?? 'Admin') : ($user->nama ?? 'Admin');
        $buktiPath = null;
        if ($request->hasFile('bukti_nota')) {
            $ext = $request->file('bukti_nota')->getClientOriginalExtension();
            $buktiPath = $request->file('bukti_nota')->storeAs('pengeluaran_kartu', 'nota_' . time() . '_' . uniqid() . '.' . $ext, 'public');
        }

        $jumlah = max(1, (int) ($request->jumlah ?: 1));
        $hargaSatuan = (int) $request->nominal;
        $totalNominal = $hargaSatuan * $jumlah;

        $payload = [
            'judul'           => trim($request->judul),
            'kategori'        => $request->kategori,
            'nominal'         => $totalNominal,
            'tanggal'         => $request->tanggal,
            'keterangan'      => trim((string) $request->keterangan),
            'bukti_nota_path' => $buktiPath,
            'petugas'         => $userName,
        ];

        if (Schema::hasColumn('kartu_pelajar_pengeluaran', 'jumlah')) {
            $payload['jumlah'] = $jumlah;
        }
        if (Schema::hasColumn('kartu_pelajar_pengeluaran', 'harga_satuan')) {
            $payload['harga_satuan'] = $hargaSatuan;
        }

        KartuPelajarPengeluaran::create($payload);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Catatan pengeluaran berhasil disimpan (Total: Rp ' . number_format($totalNominal, 0, ',', '.') . ').',
            ]);
        }

        return redirect()->route('dashboard.kartu-pelajar.index', ['tab' => 'keuangan'])
            ->with('success', 'Catatan pengeluaran berhasil disimpan.');
    }

    /**
     * Hapus Catatan Pengeluaran
     */
    public function destroyPengeluaran(Request $request, int $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'delete')) {
            abort(403, 'Akses ditolak.');
        }

        $item = KartuPelajarPengeluaran::findOrFail($id);
        if ($item->bukti_nota_path && Storage::disk('public')->exists($item->bukti_nota_path)) {
            Storage::disk('public')->delete($item->bukti_nota_path);
        }
        $item->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Catatan pengeluaran berhasil dihapus.',
            ]);
        }

        return redirect()->route('dashboard.kartu-pelajar.index', ['tab' => 'keuangan'])
            ->with('success', 'Catatan pengeluaran berhasil dihapus.');
    }

    /**
     * Cetak Laporan Keuangan Resmi Kartu Pelajar (Pemasukan, Pengeluaran & Saldo)
     */
    public function printLaporanKeuangan(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'read')) {
            abort(403, 'Akses ditolak.');
        }

        $dari = $request->get('dari');
        $sampai = $request->get('sampai');

        $pemasukanQuery = DB::table('kartu_pelajar_pembayaran as kp')
            ->join('peserta_didik as pd', 'kp.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->where('kp.status_pembayaran', 'lunas')
            ->select('kp.*', 'pd.nama', 'pd.nisn', 'pd.nama_rombel');

        $pengeluaranQuery = DB::table('kartu_pelajar_pengeluaran');

        if ($dari) {
            $pemasukanQuery->whereDate('kp.tanggal_bayar', '>=', $dari);
            $pengeluaranQuery->whereDate('tanggal', '>=', $dari);
        }
        if ($sampai) {
            $pemasukanQuery->whereDate('kp.tanggal_bayar', '<=', $sampai);
            $pengeluaranQuery->whereDate('tanggal', '<=', $sampai);
        }

        $pemasukanList = $pemasukanQuery->orderBy('kp.tanggal_bayar', 'asc')->get();
        $pengeluaranList = $pengeluaranQuery->orderBy('tanggal', 'asc')->get();

        $totalPemasukan = $pemasukanList->sum('nominal');
        $totalPengeluaran = $pengeluaranList->sum('nominal');
        $saldoBersih = $totalPemasukan - $totalPengeluaran;

        $sekolahInfo = $this->getSekolahInfo();
        $kategoriList = KartuPelajarPengeluaran::getKategoriList();

        return view('kartu-pelajar.cetak-keuangan', compact(
            'pemasukanList',
            'pengeluaranList',
            'totalPemasukan',
            'totalPengeluaran',
            'saldoBersih',
            'sekolahInfo',
            'kategoriList',
            'dari',
            'sampai'
        ));
    }

    private function canKartuPelajar(string $perm): bool
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        return $role === 'admin' || RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', $perm);
    }

    /**
     * Autocomplete peserta didik untuk form request cetak
     */
    public function cariSiswa(Request $request): JsonResponse
    {
        if (!$this->canKartuPelajar('read')) {
            return response()->json([], 403);
        }

        $q = trim((string) $request->get('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json(
            DB::table('peserta_didik as pd')
                ->leftJoin('kartu_pelajar_pembayaran as kp', 'pd.peserta_didik_id', '=', 'kp.peserta_didik_id')
                ->where(function ($s) use ($q) {
                    $s->where('pd.nama', 'LIKE', "%{$q}%")
                        ->orWhere('pd.nisn', 'LIKE', "%{$q}%")
                        ->orWhere('pd.nipd', 'LIKE', "%{$q}%");
                })
                ->orderBy('pd.nama')
                ->limit(15)
                ->get([
                    'pd.peserta_didik_id',
                    'pd.nama',
                    'pd.nisn',
                    'pd.nama_rombel',
                    DB::raw("COALESCE(kp.status_pembayaran, 'belum_bayar') as status_pembayaran"),
                    'kp.metode_pembayaran',
                ])
        );
    }

    /**
     * Simpan request cetak kartu pelajar
     */
    public function storeRequest(Request $request)
    {
        if (!$this->canKartuPelajar('create')) {
            abort(403, 'Akses ditolak.');
        }

        $data = $request->validate([
            'peserta_didik_id'   => 'required|string|exists:peserta_didik,peserta_didik_id',
            'jenis_request'      => 'required|in:' . implode(',', array_keys(KartuPelajarRequest::JENIS)),
            'status_pembayaran'  => 'nullable|in:belum_bayar,lunas',
            'metode_pembayaran'  => 'nullable|in:' . implode(',', array_keys(KartuPelajarRequest::METODE_PEMBAYARAN)),
            'alasan'             => 'nullable|string|max:500',
        ], [
            'peserta_didik_id.required' => 'Peserta didik wajib dipilih.',
            'peserta_didik_id.exists'   => 'Peserta didik tidak ditemukan.',
            'jenis_request.required'    => 'Jenis request wajib dipilih.',
            'jenis_request.in'          => 'Pilihan jenis request tidak valid.',
            'status_pembayaran.in'      => 'Status pembayaran tidak valid.',
            'metode_pembayaran.in'      => 'Metode pembayaran tidak valid.',
        ]);

        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $data['peserta_didik_id'])->first();
        $user = session('user');

        // Cek duplicate request: sama tanggal, sama status, sama peserta_didik
        $existingRequest = KartuPelajarRequest::where('peserta_didik_id', $data['peserta_didik_id'])
            ->when(
                Schema::hasColumn('kartu_pelajar_request', 'request_date'),
                fn($query) => $query->where('request_date', now()->toDateString()),
                fn($query) => $query->whereDate('created_at', now()->toDateString())
            )
            ->where('status', 'menunggu')
            ->first();

        if ($existingRequest) {
            $msg = "Peserta didik {$pd->nama} sudah memiliki request antrean berstatus Menunggu pada tanggal hari ini.";
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->route('dashboard.kartu-pelajar.index', ['tab' => 'request'])
                ->with('error', $msg);
        }

        // Sinkronisasi data pembayaran peserta didik
        $settings = KartuPelajarPengaturan::getSettings();
        $record = KartuPelajarPembayaran::firstOrNew(['peserta_didik_id' => $pd->peserta_didik_id]);
        $record->nisn = $pd->nisn;

        $hasPaymentChange = false;
        if (!empty($data['status_pembayaran'])) {
            $record->status_pembayaran = $data['status_pembayaran'];
            if ($data['status_pembayaran'] === 'lunas') {
                if (empty($record->tanggal_bayar)) {
                    $record->tanggal_bayar = now()->toDateString();
                }
            } else {
                $record->tanggal_bayar = null;
            }
            $hasPaymentChange = true;
        }

        if (!empty($data['metode_pembayaran'])) {
            $record->metode_pembayaran = $data['metode_pembayaran'];
            $hasPaymentChange = true;
        }

        if ($hasPaymentChange) {
            if (empty($record->nominal)) {
                $record->nominal = (int) ($settings->biaya_standar ?? 25000);
            }
            $record->save();
        }

        $metodeFinal = !empty($data['metode_pembayaran'])
            ? $data['metode_pembayaran']
            : ($record->metode_pembayaran ?: null);

        $payload = [
            'peserta_didik_id' => $pd->peserta_didik_id,
            'nisn'             => $pd->nisn,
            'jenis_request'    => $data['jenis_request'],
            'alasan'           => trim((string) ($data['alasan'] ?? '')),
            'status'           => 'menunggu',
            'diminta_oleh'     => is_array($user) ? ($user['nama'] ?? 'Admin') : ($user->nama ?? 'Admin'),
        ];
        if (Schema::hasColumn('kartu_pelajar_request', 'metode_pembayaran')) {
            $payload['metode_pembayaran'] = $metodeFinal;
        }
        if (Schema::hasColumn('kartu_pelajar_request', 'request_date')) {
            $payload['request_date'] = now()->toDateString();
        }

        KartuPelajarRequest::create($payload);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => "Request cetak kartu untuk {$pd->nama} berhasil ditambahkan."]);
        }
        return redirect()->route('dashboard.kartu-pelajar.index', ['tab' => 'request'])
            ->with('success', "Request cetak kartu untuk {$pd->nama} berhasil ditambahkan.");
    }

    /**
     * Ubah status request. Status 'selesai' otomatis menandai kartu sudah dicetak.
     */
    public function updateStatusRequest(Request $request, int $id)
    {
        if (!$this->canKartuPelajar('update')) {
            abort(403, 'Akses ditolak.');
        }

        $data = $request->validate([
            'status'        => 'required|in:' . implode(',', array_keys(KartuPelajarRequest::STATUS)),
            'catatan_admin' => 'nullable|string|max:500',
        ], [
            'status.required' => 'Status request wajib dipilih.',
            'status.in'       => 'Pilihan status request tidak valid.',
        ]);

        $item = KartuPelajarRequest::findOrFail($id);

        // Validasi cegah request ganda pada tanggal yang sama dengan status yang sama
        $itemDate = $item->request_date ?? ($item->created_at ? $item->created_at->toDateString() : now()->toDateString());
        $isDuplicate = KartuPelajarRequest::where('id', '!=', $item->id)
            ->where('peserta_didik_id', $item->peserta_didik_id)
            ->where('status', $data['status'])
            ->when(
                Schema::hasColumn('kartu_pelajar_request', 'request_date'),
                fn($query) => $query->where('request_date', $itemDate),
                fn($query) => $query->whereDate('created_at', $itemDate)
            )
            ->exists();

        if ($isDuplicate) {
            $msg = "Sudah ada request lain untuk siswa ini pada tanggal tersebut dengan status " . (KartuPelajarRequest::STATUS[$data['status']] ?? $data['status']) . ".";
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->route('dashboard.kartu-pelajar.index', ['tab' => 'request'])->with('error', $msg);
        }

        $item->status = $data['status'];
        if (array_key_exists('catatan_admin', $data)) {
            $item->catatan_admin = $data['catatan_admin'];
        }
        $item->selesai_at = $data['status'] === 'selesai' ? now() : null;
        $item->save();

        if ($data['status'] === 'selesai') {
            $record = KartuPelajarPembayaran::firstOrNew(['peserta_didik_id' => $item->peserta_didik_id]);
            $record->nisn = $item->nisn;
            $record->status_cetak = 'sudah_cetak';
            $record->tanggal_cetak = $record->tanggal_cetak ?: now();
            $record->save();
        } else {
            $hasOtherSelesai = KartuPelajarRequest::where('peserta_didik_id', $item->peserta_didik_id)
                ->where('id', '!=', $item->id)
                ->where('status', 'selesai')
                ->exists();
            if (!$hasOtherSelesai) {
                $record = KartuPelajarPembayaran::where('peserta_didik_id', $item->peserta_didik_id)->first();
                if ($record && $record->status_cetak === 'sudah_cetak') {
                    $record->status_cetak = 'belum_cetak';
                    $record->save();
                }
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Status request diperbarui menjadi ' . KartuPelajarRequest::STATUS[$data['status']] . '.']);
        }
        return redirect()->route('dashboard.kartu-pelajar.index', ['tab' => 'request'])
            ->with('success', 'Status request diperbarui menjadi ' . KartuPelajarRequest::STATUS[$data['status']] . '.');
    }

    public function destroyRequest(Request $request, int $id)
    {
        if (!$this->canKartuPelajar('delete')) {
            abort(403, 'Akses ditolak.');
        }

        $item = KartuPelajarRequest::findOrFail($id);
        $wasSelesai = ($item->status === 'selesai');
        $pdId = $item->peserta_didik_id;
        $item->delete();

        if ($wasSelesai) {
            $hasOtherSelesai = KartuPelajarRequest::where('peserta_didik_id', $pdId)
                ->where('status', 'selesai')
                ->exists();
            if (!$hasOtherSelesai) {
                $record = KartuPelajarPembayaran::where('peserta_didik_id', $pdId)->first();
                if ($record && $record->status_cetak === 'sudah_cetak') {
                    $record->status_cetak = 'belum_cetak';
                    $record->save();
                }
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Request cetak dihapus.']);
        }
        return redirect()->route('dashboard.kartu-pelajar.index', ['tab' => 'request'])
            ->with('success', 'Request cetak dihapus.');
    }

    /**
     * Cetak Lembar Pengambilan / Serah Terima Kartu Pelajar Per Tanggal Cetak
     */
    public function printPengambilan(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'read')) {
            abort(403, 'Akses ditolak.');
        }

        // Ambil daftar tanggal cetak yang tersedia untuk dropdown filter
        $tanggalCetakList = DB::table('kartu_pelajar_pembayaran')
            ->where('status_cetak', 'sudah_cetak')
            ->whereNotNull('tanggal_cetak')
            ->select(DB::raw('DATE(tanggal_cetak) as tgl'), DB::raw('COUNT(*) as total'))
            ->groupBy('tgl')
            ->orderByDesc('tgl')
            ->get();

        $latestDate = $tanggalCetakList->first()?->tgl ?: now()->toDateString();
        $tanggalCetak = $request->get('tanggal_cetak', $latestDate);
        $rombel = $request->get('rombel');

        $query = DB::table('kartu_pelajar_pembayaran as kp')
            ->join('peserta_didik as pd', 'kp.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->where('kp.status_cetak', 'sudah_cetak');

        if ($tanggalCetak) {
            $query->whereDate('kp.tanggal_cetak', $tanggalCetak);
        }

        if ($rombel) {
            $query->where('pd.nama_rombel', $rombel);
        }

        $students = $query->orderBy('pd.nama_rombel', 'asc')
            ->orderBy('pd.nama', 'asc')
            ->select('kp.*', 'pd.nama', 'pd.nisn', 'pd.nipd', 'pd.nama_rombel')
            ->get();

        $sekolahInfo = $this->getSekolahInfo();
        $sekolah = Schema::hasTable('sekolah') ? DB::table('sekolah')->first() : null;
        $sekolahMeta = Schema::hasTable('sekolah_meta') ? SekolahMeta::first() : null;

        // Ambil data Wali Kelas jika rombel spesifik dipilih
        $waliGtk = null;
        if ($rombel) {
            $activeRombelRow = DB::table('rombongan_belajar')->where('nama', $rombel)->first();
            if ($activeRombelRow) {
                if (!empty($activeRombelRow->ptk_id)) {
                    $waliGtk = DB::table('gtk')->where('ptk_id', $activeRombelRow->ptk_id)->first();
                }
                if (!$waliGtk && !empty($activeRombelRow->ptk_id_str)) {
                    $waliGtk = DB::table('gtk')->where('nama', $activeRombelRow->ptk_id_str)->first();
                }
            }
        }

        $rombelList = DB::table('rombongan_belajar')
            ->where('jenis_rombel', '1')
            ->orderBy('tingkat_pendidikan_id', 'asc')
            ->orderBy('nama', 'asc')
            ->pluck('nama');

        return view('kartu-pelajar.cetak-pengambilan', compact(
            'students',
            'tanggalCetak',
            'rombel',
            'sekolahInfo',
            'sekolah',
            'sekolahMeta',
            'waliGtk',
            'rombelList',
            'tanggalCetakList'
        ));
    }
}
