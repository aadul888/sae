<?php

namespace App\Http\Controllers;

use App\Models\KesiswaanPrestasi;
use App\Models\PesertaDidik;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrestasiSiswaController extends Controller
{
    private function checkAuth()
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }
        return null;
    }

    /**
     * Tampilkan Halaman Kluster Prestasi Siswa (Akademik, Nonakademik, Rekap).
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'delete');

        $activeTab = $request->get('tab', 'akademik'); // akademik, nonakademik, rekap
        $q = trim($request->get('q', ''));
        $tingkat = trim($request->get('tingkat', ''));
        $tahun = trim($request->get('tahun', ''));

        // 1. Statistik Prestasi
        $totalAkademik = KesiswaanPrestasi::where('kategori', 'akademik')->count();
        $totalNonakademik = KesiswaanPrestasi::where('kategori', 'nonakademik')->count();
        $totalNasional = KesiswaanPrestasi::whereIn('tingkat', ['nasional', 'internasional'])->count();
        $totalJuara1 = KesiswaanPrestasi::where('peringkat', 'juara_1')->count();

        $stats = [
            'total_akademik'    => $totalAkademik,
            'total_nonakademik' => $totalNonakademik,
            'total_nasional'    => $totalNasional,
            'total_juara_1'     => $totalJuara1,
        ];

        // 2. Tab: Prestasi Akademik
        $akademikQuery = KesiswaanPrestasi::with(['siswa', 'pembimbing'])
            ->where('kategori', 'akademik')
            ->orderBy('tanggal_prestasi', 'desc');
        if ($q !== '' && $activeTab === 'akademik') {
            $akademikQuery->where(function ($b) use ($q) {
                $b->where('bidang_lomba', 'like', "%{$q}%")
                  ->orWhere('nama_event', 'like', "%{$q}%")
                  ->orWhereHas('siswa', fn($sq) => $sq->where('nama', 'like', "%{$q}%"));
            });
        }
        if ($tingkat !== '' && $activeTab === 'akademik') {
            $akademikQuery->where('tingkat', $tingkat);
        }
        $akademikList = $akademikQuery->paginate(25, ['*'], 'akademik_page')->withQueryString();

        // 3. Tab: Prestasi Nonakademik
        $nonakademikQuery = KesiswaanPrestasi::with(['siswa', 'pembimbing'])
            ->where('kategori', 'nonakademik')
            ->orderBy('tanggal_prestasi', 'desc');
        if ($q !== '' && $activeTab === 'nonakademik') {
            $nonakademikQuery->where(function ($b) use ($q) {
                $b->where('bidang_lomba', 'like', "%{$q}%")
                  ->orWhere('nama_event', 'like', "%{$q}%")
                  ->orWhereHas('siswa', fn($sq) => $sq->where('nama', 'like', "%{$q}%"));
            });
        }
        if ($tingkat !== '' && $activeTab === 'nonakademik') {
            $nonakademikQuery->where('tingkat', $tingkat);
        }
        $nonakademikList = $nonakademikQuery->paginate(25, ['*'], 'nonakademik_page')->withQueryString();

        // 4. Tab: Rekapitulasi Prestasi (Berdasarkan Tingkat & Peringkat)
        $rekapPerTingkat = DB::table('kesiswaan_prestasi')
            ->select('tingkat', DB::raw('count(*) as total'))
            ->groupBy('tingkat')
            ->get();

        $rekapPerJuara = DB::table('kesiswaan_prestasi')
            ->select('peringkat', DB::raw('count(*) as total'))
            ->groupBy('peringkat')
            ->get();

        $topSiswa = DB::table('kesiswaan_prestasi as kp')
            ->join('peserta_didik as pd', 'kp.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->select('pd.nama', 'pd.nisn', DB::raw('count(kp.id) as total_prestasi'))
            ->groupBy('pd.nama', 'pd.nisn')
            ->orderByDesc('total_prestasi')
            ->limit(10)
            ->get();

        // Master Siswa & Pembimbing untuk modal
        $siswaList = PesertaDidik::orderBy('nama')->limit(300)->get(['peserta_didik_id', 'nama', 'nisn', 'nipd']);
        $pembimbingList = DB::table('gtk')->orderBy('nama')->get(['ptk_id', 'nama']);

        return view('dashboard.kesiswaan.prestasi', compact(
            'stats',
            'activeTab',
            'q',
            'tingkat',
            'tahun',
            'akademikList',
            'nonakademikList',
            'rekapPerTingkat',
            'rekapPerJuara',
            'topSiswa',
            'siswaList',
            'pembimbingList',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete'
        ));
    }

    /**
     * Simpan Data Prestasi Siswa.
     */
    public function storePrestasi(Request $request)
    {
        $validated = $request->validate([
            'peserta_didik_id'  => 'required|string',
            'kategori'          => 'required|string|in:akademik,nonakademik',
            'bidang_lomba'      => 'required|string|max:190',
            'nama_event'        => 'required|string|max:255',
            'penyelenggara'     => 'nullable|string|max:190',
            'tingkat'           => 'required|string|in:sekolah,kecamatan,kabupaten_kota,provinsi,nasional,internasional',
            'peringkat'         => 'required|string|in:juara_1,juara_2,juara_3,harapan_1,harapan_2,harapan_3,finalis,peserta',
            'tanggal_prestasi'  => 'required|date',
            'pembimbing_ptk_id' => 'nullable|string',
            'pembimbing_nama'   => 'nullable|string|max:190',
            'nomor_piagam'      => 'nullable|string|max:100',
            'catatan'           => 'nullable|string',
            'sertifikat_file'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $filePath = null;
        if ($request->hasFile('sertifikat_file')) {
            $file = $request->file('sertifikat_file');
            $fileName = 'prestasi_' . time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/prestasi'), $fileName);
            $filePath = '/uploads/prestasi/' . $fileName;
        }

        $prestasi = KesiswaanPrestasi::create([
            'peserta_didik_id'  => $validated['peserta_didik_id'],
            'kategori'          => $validated['kategori'],
            'bidang_lomba'      => $validated['bidang_lomba'],
            'nama_event'        => $validated['nama_event'],
            'penyelenggara'     => $validated['penyelenggara'] ?? null,
            'tingkat'           => $validated['tingkat'],
            'peringkat'         => $validated['peringkat'],
            'tanggal_prestasi'  => $validated['tanggal_prestasi'],
            'pembimbing_ptk_id' => $validated['pembimbing_ptk_id'] ?? null,
            'pembimbing_nama'   => $validated['pembimbing_nama'] ?? null,
            'nomor_piagam'      => $validated['nomor_piagam'] ?? null,
            'catatan'           => $validated['catatan'] ?? null,
            'sertifikat_file'   => $filePath,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Prestasi '{$prestasi->bidang_lomba}' berhasil dicatat.",
            'data' => $prestasi,
        ]);
    }

    /**
     * Hapus Data Prestasi Siswa.
     */
    public function destroyPrestasi($id)
    {
        $prestasi = KesiswaanPrestasi::findOrFail($id);
        $prestasi->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data prestasi berhasil dihapus.',
        ]);
    }

    /**
     * Cetak Laporan Rekapitulasi Prestasi Siswa Resmi Ber-Kop Sekolah.
     */
    public function cetakLaporan(Request $request)
    {
        $kategori = $request->get('kategori', '');
        $tingkat = $request->get('tingkat', '');
        $tahun = $request->get('tahun', date('Y'));

        $query = KesiswaanPrestasi::with(['siswa', 'pembimbing'])->orderBy('tanggal_prestasi', 'desc');
        if ($kategori !== '') $query->where('kategori', $kategori);
        if ($tingkat !== '') $query->where('tingkat', $tingkat);
        if ($tahun !== '') $query->whereYear('tanggal_prestasi', $tahun);

        $list = $query->get();
        $sekolah = DB::table('sekolah')->first();
        $sekolahMeta = \App\Models\SekolahMeta::first();

        $kepsek = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                    ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        return view('dashboard.kesiswaan.cetak-rekap-prestasi', compact(
            'list',
            'sekolah',
            'sekolahMeta',
            'kepsek',
            'kategori',
            'tingkat',
            'tahun'
        ));
    }
}
