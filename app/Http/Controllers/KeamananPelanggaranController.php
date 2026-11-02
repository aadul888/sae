<?php

namespace App\Http\Controllers;

use App\Models\KedisiplinanPelanggaran;
use App\Models\KedisiplinanTataTertib;
use App\Models\PesertaDidik;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KeamananPelanggaranController extends Controller
{
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
        $query = KedisiplinanPelanggaran::with(['siswa.rombonganBelajar', 'aturan'])
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

        return view('dashboard.keamanan.pelanggaran', compact(
            'pelanggaranList',
            'tataTertibList',
            'q',
            'kategori',
            'tanggal',
            'perPage',
            'totalHariIni',
            'totalBulanIni',
            'canCreate'
        ));
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'peserta_didik_id' => 'required|exists:peserta_didik,peserta_didik_id',
            'tata_tertib_id'   => 'required|exists:kedisiplinan_tata_tertib,id',
            'tanggal_kejadian' => 'required|date',
            'tempat_kejadian'  => 'required|string|max:255',
            'keterangan'       => 'nullable|string|max:1000',
            'foto_bukti'       => 'nullable|image|max:5120|mimes:jpg,jpeg,png,webp',
        ]);

        $tatib = KedisiplinanTataTertib::findOrFail($validated['tata_tertib_id']);
        $user = session('user');
        $namaPelapor = ($user['nama'] ?? 'Petugas Satpam') . ' (Pos Keamanan)';

        $fotoPath = null;
        if ($request->hasFile('foto_bukti')) {
            $file = $request->file('foto_bukti');
            $dir = public_path('uploads/keamanan/pelanggaran');
            if (!file_exists($dir)) {
                @mkdir($dir, 0755, true);
            }
            $filename = 'satpam_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($dir, $filename);
            $fotoPath = 'uploads/keamanan/pelanggaran/' . $filename;
        }

        $pelanggaran = KedisiplinanPelanggaran::create([
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

        return redirect()->route('dashboard.keamanan.pelanggaran.index')
            ->with('success', "Pelanggaran siswa berhasil dicatat (+{$tatib->bobot_poin} poin). Data langsung tersinkron ke Tim Kesiswaan.");
    }
}
