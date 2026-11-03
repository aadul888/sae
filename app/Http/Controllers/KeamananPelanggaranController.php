<?php

namespace App\Http\Controllers;

use App\Models\KedisiplinanPelanggaran;
use App\Models\KedisiplinanTataTertib;
use App\Models\PesertaDidik;
use App\Models\RolePermission;
use App\Services\ImageOptimizerService;
use App\Services\KeamananImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KeamananPelanggaranController extends Controller
{
    use \App\Http\Controllers\Concerns\CetakKeamanan;

    /** Unduh template Excel import Pelanggaran. */
    public function templateExcel(KeamananImportService $service)
    {
        $path = $service->generateTemplateFile('pelanggaran');

        return response()->download($path, 'Format_Import_Pelanggaran_Siswa.xlsx')->deleteFileAfterSend(true);
    }

    /** Import Pelanggaran Siswa via Excel. */
    public function importExcel(Request $request, KeamananImportService $service)
    {
        $request->validate(['file' => 'required|file|max:10240']);

        try {
            $hasil = $service->import('pelanggaran', $request->file('file'));
            $pesan = "{$hasil['imported']} pelanggaran berhasil diimpor" . ($hasil['skipped'] > 0 ? ", {$hasil['skipped']} baris dilewati (siswa tidak ditemukan)." : '.');

            return response()->json(['status' => 'success', 'message' => $pesan]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function index(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'satpam';

        $canAccess = RolePermission::canAccess($user, 'menu_keamanan', 'read')
            || in_array($role, ['admin', 'tendik', 'satpam', 'kesiswaan']);

        if (!$canAccess) {
            return redirect()->route('dashboard.' . $role)->with('error', 'Akses ke menu pelanggaran ditolak.');
        }

        $canCreate = RolePermission::canAccess($user, 'menu_keamanan', 'create')
            || in_array($role, ['admin', 'satpam', 'tendik']);

        $q = trim($request->get('q', ''));
        $kategori = trim($request->get('kategori', ''));
        $tanggal = trim($request->get('tanggal', ''));
        $perPage = (int) $request->get('perPage', 15);

        // Pelanggaran yang dicatat satpam/pos gerbang
        $query = KedisiplinanPelanggaran::with(['siswa', 'aturan'])
            ->where(function ($b) {
                $b->where('pelapor_nama', 'like', '%Satpam%')
                    ->orWhere('pelapor_nama', 'like', '%Keamanan%')
                    ->orWhere('tempat_kejadian', 'like', '%Gerbang%')
                    ->orWhere('tempat_kejadian', 'like', '%Pos%')
                    ->orWhere('tempat_kejadian', 'like', '%Luar%');
            })
            ->latest('tanggal_kejadian')
            ->latest('id');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->whereHas('siswa', function ($s) use ($q) {
                    $s->where('nama', 'like', "%{$q}%")
                        ->orWhere('nisn', 'like', "%{$q}%");
                })->orWhereHas('aturan', function ($a) use ($q) {
                    $a->where('nama_aturan', 'like', "%{$q}%")
                        ->orWhere('kode', 'like', "%{$q}%");
                })->orWhere('keterangan', 'like', "%{$q}%");
            });
        }

        if ($tanggal !== '') {
            $query->whereDate('tanggal_kejadian', $tanggal);
        }

        if ($kategori !== '') {
            $query->whereHas('aturan', function ($a) use ($kategori) {
                $a->where('kategori', $kategori);
            });
        }

        $pelanggaranList = $query->paginate($perPage)->withQueryString();

        // Aturan tata tertib untuk dropdown form
        $tataTertibList = KedisiplinanTataTertib::where('is_active', true)
            ->orderBy('bobot_poin')
            ->get();

        // Ringkasan statistik satpam
        $today = date('Y-m-d');
        $totalHariIni = KedisiplinanPelanggaran::whereDate('tanggal_kejadian', $today)
            ->where(function ($b) {
                $b->where('pelapor_nama', 'like', '%Satpam%')
                    ->orWhere('tempat_kejadian', 'like', '%Gerbang%');
            })->count();

        $totalBulanIni = KedisiplinanPelanggaran::whereMonth('tanggal_kejadian', date('m'))
            ->whereYear('tanggal_kejadian', date('Y'))
            ->where(function ($b) {
                $b->where('pelapor_nama', 'like', '%Satpam%')
                    ->orWhere('tempat_kejadian', 'like', '%Gerbang%');
            })->count();

        $totalSemua = KedisiplinanPelanggaran::where(function ($b) {
            $b->where('pelapor_nama', 'like', '%Satpam%')
                ->orWhere('pelapor_nama', 'like', '%Keamanan%')
                ->orWhere('tempat_kejadian', 'like', '%Gerbang%')
                ->orWhere('tempat_kejadian', 'like', '%Pos%')
                ->orWhere('tempat_kejadian', 'like', '%Luar%');
        })->count();

        return view('dashboard.keamanan.pelanggaran', compact(
            'pelanggaranList',
            'tataTertibList',
            'q',
            'kategori',
            'tanggal',
            'perPage',
            'totalHariIni',
            'totalBulanIni',
            'totalSemua',
            'canCreate'
        ));
    }

    /** Halaman cetak Pelanggaran Siswa (ikut filter aktif halaman). */
    public function cetak(Request $request)
    {
        $q = trim($request->get('q', ''));
        $kategori = trim($request->get('kategori', ''));
        $tanggal = trim($request->get('tanggal', ''));

        $query = KedisiplinanPelanggaran::with(['siswa', 'aturan'])
            ->where(function ($b) {
                $b->where('pelapor_nama', 'like', '%Satpam%')
                    ->orWhere('pelapor_nama', 'like', '%Keamanan%')
                    ->orWhere('tempat_kejadian', 'like', '%Gerbang%')
                    ->orWhere('tempat_kejadian', 'like', '%Pos%')
                    ->orWhere('tempat_kejadian', 'like', '%Luar%');
            })
            ->latest('tanggal_kejadian')
            ->latest('id');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->whereHas('siswa', function ($s) use ($q) {
                    $s->where('nama', 'like', "%{$q}%")
                        ->orWhere('nisn', 'like', "%{$q}%");
                })->orWhereHas('aturan', function ($a) use ($q) {
                    $a->where('nama_aturan', 'like', "%{$q}%")
                        ->orWhere('kode', 'like', "%{$q}%");
                })->orWhere('keterangan', 'like', "%{$q}%");
            });
        }
        $periode = $this->periodeDariRequest($request);

        if ($periode) {
            $query->whereDate('tanggal_kejadian', '>=', $periode['start'])
                ->whereDate('tanggal_kejadian', '<=', $periode['end']);
        } elseif ($tanggal !== '') {
            $query->whereDate('tanggal_kejadian', $tanggal);
        }
        if ($kategori !== '') {
            $query->whereHas('aturan', function ($a) use ($kategori) {
                $a->where('kategori', $kategori);
            });
        }

        $rows = $query->limit(1000)->get()->map(function ($p) {
            $p->nama_siswa = $p->siswa->nama ?? null;
            $p->nisn = $p->siswa->nisn ?? null;
            return $p;
        });

        $filterInfo = ($periode ? 'Periode: ' . $periode['label'] : 'Tanggal: ' . ($tanggal ?: 'Semua'))
            . ' • Kategori: ' . ($kategori ?: 'Semua')
            . ($q ? " • Pencarian: \"{$q}\"" : '');

        return $this->renderCetak('pelanggaran', 'Laporan Pelanggaran Siswa (Pos Gerbang)', $filterInfo, $rows);
    }

    public function searchSiswa(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $siswa = DB::table('peserta_didik as pd')
            ->leftJoin('anggota_rombel as ar', 'pd.peserta_didik_id', '=', 'ar.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->select('pd.peserta_didik_id', 'pd.nama', 'pd.nisn', 'rb.nama as rombel_nama')
            ->where(function ($b) use ($q) {
                $b->where('pd.nama', 'like', "%{$q}%")
                    ->orWhere('pd.nisn', 'like', "%{$q}%");
            })
            ->limit(15)
            ->get();

        return response()->json($siswa);
    }

    public function searchTatib(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $items = KedisiplinanTataTertib::where('is_active', true)
            ->where(function ($b) use ($q) {
                $b->where('nama_aturan', 'like', "%{$q}%")
                    ->orWhere('kode', 'like', "%{$q}%")
                    ->orWhere('kategori', 'like', "%{$q}%");
            })
            ->limit(15)
            ->get(['id', 'kode', 'nama_aturan', 'bobot_poin', 'tingkat', 'kategori']);

        return response()->json($items);
    }

    public function store(Request $request)
    {
        // Support single or multiple tata_tertib_ids
        if ($request->filled('tata_tertib_id') && !$request->has('tata_tertib_ids')) {
            $request->merge(['tata_tertib_ids' => [$request->tata_tertib_id]]);
        }

        $validated = $request->validate([
            'peserta_didik_id'  => 'required|exists:peserta_didik,peserta_didik_id',
            'tata_tertib_ids'   => 'required|array|min:1',
            'tata_tertib_ids.*' => 'required|exists:kedisiplinan_tata_tertib,id',
            'tanggal_kejadian'  => 'required|date',
            'tempat_kejadian'   => 'required|string|max:255',
            'keterangan'        => 'nullable|string|max:1000',
            'foto_bukti'        => 'nullable|image|max:5120|mimes:jpg,jpeg,png,webp',
        ]);

        $user = session('user');
        $namaPelapor = ($user['nama'] ?? 'Petugas Satpam') . ' (Pos Keamanan)';

        $fotoPath = null;
        if ($request->hasFile('foto_bukti')) {
            $hasil = app(ImageOptimizerService::class)->optimizeAndSaveImage(
                $request->file('foto_bukti'),
                public_path('uploads/keamanan/pelanggaran'),
                'satpam'
            );
            $fotoPath = 'uploads/keamanan/pelanggaran/' . $hasil['filename'];
        }

        $totalPoin = 0;
        $count = 0;

        foreach ($validated['tata_tertib_ids'] as $tatibId) {
            $tatib = KedisiplinanTataTertib::find($tatibId);
            if (!$tatib) continue;

            KedisiplinanPelanggaran::create([
                'peserta_didik_id'     => $validated['peserta_didik_id'],
                'tata_tertib_id'       => $tatib->id,
                'tanggal_kejadian'     => $validated['tanggal_kejadian'],
                'tempat_kejadian'      => $validated['tempat_kejadian'],
                'poin'                 => $tatib->bobot_poin,
                'keterangan'           => $validated['keterangan'] ?? 'Dicatat di Pos Satpam / Gerbang Sekolah',
                'pelapor_ptk_id'       => $user['ptk_id'] ?? null,
                'pelapor_nama'         => $namaPelapor,
                'foto_bukti'           => $fotoPath,
                'status_tindak_lanjut' => 'pending',
            ]);

            $totalPoin += (int) $tatib->bobot_poin;
            $count++;
        }

        return redirect()->route('dashboard.keamanan.pelanggaran.index')
            ->with('success', "{$count} pelanggaran siswa berhasil dicatat (total +{$totalPoin} poin). Tersinkron ke Tim Kesiswaan.");
    }
}
