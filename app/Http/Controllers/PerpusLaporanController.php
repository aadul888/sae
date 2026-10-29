<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RolePermission;

/**
 * Laporan & Rekapitulasi Perpustakaan Sekolah.
 * Gabungan: koleksi, sirkulasi/denda, dan kunjungan dalam satu dokumen cetak.
 */
class PerpusLaporanController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'tendik';

        $canRead = RolePermission::canAccess($user, 'menu_perpustakaan', 'read')
            || RolePermission::canAccess($user, 'menu_perpustakaan_laporan', 'read');

        if (!$canRead && !in_array($role, ['admin', 'tendik', 'guru', 'pustakawan'])) {
            return redirect()->route('dashboard.' . $role)->withError('Anda tidak memiliki hak akses ke Laporan Perpustakaan.');
        }

        $data = $this->buildData($request);

        return view('dashboard.perpus.laporan', $data);
    }

    public function cetak(Request $request)
    {
        $data = $this->buildData($request);
        $data['kepalaSekolah'] = DB::table('gtk')
            ->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
            ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%')
            ->first();
        $data['pustakawan'] = $this->penanggungJawab();

        return view('dashboard.perpus.laporan-cetak', $data);
    }

    /** Laporan per Tahun Ajaran dengan rincian per bulan + siap cetak A3. */
    public function tahunAjaran(Request $request)
    {
        $data = $this->buildTahunan($request);
        return view('dashboard.perpus.laporan-tahunan', $data);
    }

    public function cetakTahunAjaran(Request $request)
    {
        $data = $this->buildTahunan($request);
        $data['kepalaSekolah'] = DB::table('gtk')
            ->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
            ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%')
            ->first();
        $data['pustakawan'] = $this->penanggungJawab();
        return view('dashboard.perpus.laporan-tahunan-cetak', $data);
    }

    private function penanggungJawab()
    {
        return DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->join('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
            ->where('rtt.kode', 'PUSTAKAWAN')
            ->where('ptt.is_active', true)
            ->select('gtk.nama', 'gtk.nip')
            ->first();
    }

    /** Rekap per bulan sepanjang satu tahun ajaran (Juli–Juni). */
    private function buildTahunan(Request $request): array
    {
        $ta = trim((string) $request->query('ta', '')); // contoh: 2025/2026
        if (!preg_match('#^(\d{4})/(\d{4})$#', $ta, $m)) {
            // Default: tahun ajaran berjalan (Jul tahun kalender ini bila sudah Juli, jika belum pakai tahun sebelumnya)
            $y = (int) date('n') >= 7 ? (int) date('Y') : (int) date('Y') - 1;
            $ta = $y . '/' . ($y + 1);
        }
        $startYear = (int) substr($ta, 0, 4);
        $start = $startYear . '-07-01';
        $end = ($startYear + 1) . '-06-30';

        $bulan = [];
        $cursor = \Carbon\Carbon::parse($start)->startOfMonth();
        $last = \Carbon\Carbon::parse($end)->startOfMonth();
        while ($cursor->lte($last)) {
            $bStart = $cursor->copy()->startOfMonth()->toDateString();
            $bEnd = $cursor->copy()->endOfMonth()->toDateString();

            $pinjam = (int) DB::table('perpus_sirkulasi')->whereBetween('tgl_pinjam', [$bStart, $bEnd])->count();
            $kembali = (int) DB::table('perpus_sirkulasi')->whereBetween('tgl_kembali', [$bStart, $bEnd])->count();
            $denda = (float) (DB::table('perpus_sirkulasi')->where('status_denda', 'lunas')->whereBetween('tgl_pinjam', [$bStart, $bEnd])->sum('denda') ?: 0);
            $kunjungan = (int) DB::table('perpus_kunjungan')->whereBetween('tanggal', [$bStart, $bEnd])->count();
            $anggotaBaru = (int) DB::table('perpus_anggota')->whereBetween('tgl_daftar', [$bStart, $bEnd])->count();
            $bukuBaru = (int) DB::table('perpus_koleksi_buku')->whereBetween('tgl_pembelian', [$bStart, $bEnd])->count();
            $biayaAset = (float) (DB::table('perpus_koleksi_buku')->whereBetween('tgl_pembelian', [$bStart, $bEnd])->sum('harga_perolehan') ?: 0);

            $bulan[] = [
                'label' => $cursor->translatedFormat('F Y'),
                'bulan' => $cursor->format('Y-m'),
                'pinjam' => $pinjam,
                'kembali' => $kembali,
                'denda' => $denda,
                'kunjungan' => $kunjungan,
                'anggota_baru' => $anggotaBaru,
                'buku_baru' => $bukuBaru,
                'biaya_aset' => $biayaAset,
            ];

            $cursor->addMonth();
        }

        $totals = [
            'pinjam' => array_sum(array_column($bulan, 'pinjam')),
            'kembali' => array_sum(array_column($bulan, 'kembali')),
            'denda' => array_sum(array_column($bulan, 'denda')),
            'kunjungan' => array_sum(array_column($bulan, 'kunjungan')),
            'anggota_baru' => array_sum(array_column($bulan, 'anggota_baru')),
            'buku_baru' => array_sum(array_column($bulan, 'buku_baru')),
            'biaya_aset' => array_sum(array_column($bulan, 'biaya_aset')),
        ];

        $taOptions = [];
        $thisYear = (int) date('n') >= 7 ? (int) date('Y') : (int) date('Y') - 1;
        for ($y = $thisYear; $y >= $thisYear - 4; $y--) {
            $taOptions[] = $y . '/' . ($y + 1);
        }

        return [
            'ta' => $ta,
            'taOptions' => $taOptions,
            'periodeLabel' => \Carbon\Carbon::parse($start)->translatedFormat('d F Y') . ' — ' . \Carbon\Carbon::parse($end)->translatedFormat('d F Y'),
            'bulan' => $bulan,
            'totals' => $totals,
        ];
    }

    /** Agregasi rekap koleksi, sirkulasi, dan kunjungan untuk rentang tanggal. */
    private function buildData(Request $request): array
    {
        $dari = $request->query('dari', date('Y-m-01'));
        $sampai = $request->query('sampai', date('Y-m-d'));
        $kategori = trim((string) $request->query('kategori', ''));

        // Rekap koleksi per kategori
        $koleksiQuery = DB::table('perpus_koleksi_buku')
            ->selectRaw('COALESCE(NULLIF(kategori, ""), "Umum") as kategori, COUNT(*) as judul, SUM(jumlah_eksemplar) as eksemplar, SUM(eksemplar_tersedia) as tersedia')
            ->groupBy('kategori')
            ->orderByDesc('judul');
        if ($kategori !== '') {
            $koleksiQuery->where('kategori', $kategori);
        }
        $rekapKoleksi = $koleksiQuery->get();

        $totalJudul = (int) $rekapKoleksi->sum('judul');
        $totalEksemplar = (int) $rekapKoleksi->sum('eksemplar');
        $totalTersedia = (int) $rekapKoleksi->sum('tersedia');

        // Sirkulasi dalam rentang tanggal
        $sirkBase = fn() => DB::table('perpus_sirkulasi')
            ->whereBetween('tgl_pinjam', [$dari, $sampai]);

        $totalPinjam = (int) $sirkBase()->count();
        $totalKembali = (int) $sirkBase()->where('status', 'kembali')->count();
        $masihDipinjam = (int) $sirkBase()->where('status', 'dipinjam')->count();
        $terlambat = (int) DB::table('perpus_sirkulasi')
            ->where('status', 'dipinjam')
            ->where('tgl_jatuh_tempo', '<', date('Y-m-d'))
            ->count();
        $dendaTerkumpul = (float) (DB::table('perpus_sirkulasi')
            ->where('status_denda', 'lunas')
            ->whereBetween('tgl_pinjam', [$dari, $sampai])
            ->sum('denda') ?: 0);

        $rekapSirkulasi = DB::table('perpus_sirkulasi as s')
            ->leftJoin('perpus_koleksi_buku as b', 's.buku_id', '=', 'b.id')
            ->select('s.kode_transaksi', 's.peminjam_nama', 's.peminjam_tipe', 's.tgl_pinjam', 's.tgl_jatuh_tempo', 's.tgl_kembali', 's.status', 's.denda', 's.status_denda', 'b.judul as judul_buku')
            ->whereBetween('s.tgl_pinjam', [$dari, $sampai])
            ->orderByDesc('s.tgl_pinjam')
            ->limit(200)
            ->get();

        // Kunjungan dalam rentang tanggal
        $totalKunjungan = (int) DB::table('perpus_kunjungan')->whereBetween('tanggal', [$dari, $sampai])->count();
        $rekapKunjunganKeperluan = DB::table('perpus_kunjungan')
            ->selectRaw('COALESCE(NULLIF(keperluan, ""), "Lainnya") as keperluan, COUNT(*) as total')
            ->whereBetween('tanggal', [$dari, $sampai])
            ->groupBy('keperluan')
            ->orderByDesc('total')
            ->get();
        $rekapKunjunganUnit = DB::table('perpus_kunjungan')
            ->selectRaw('COALESCE(NULLIF(rombel_atau_unit, ""), "Lainnya") as unit, COUNT(*) as total')
            ->whereBetween('tanggal', [$dari, $sampai])
            ->groupBy('unit')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Buku paling sering dipinjam dalam rentang
        $bukuPopuler = DB::table('perpus_sirkulasi as s')
            ->join('perpus_koleksi_buku as b', 's.buku_id', '=', 'b.id')
            ->selectRaw('b.judul, COUNT(*) as total')
            ->whereBetween('s.tgl_pinjam', [$dari, $sampai])
            ->groupBy('b.judul')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $kategoriOptions = DB::table('perpus_koleksi_buku')
            ->distinct()->pluck('kategori')->filter()->sort()->values()->all();

        return [
            'dari' => $dari,
            'sampai' => $sampai,
            'kategori' => $kategori,
            'kategoriOptions' => $kategoriOptions,
            'rekapKoleksi' => $rekapKoleksi,
            'totalJudul' => $totalJudul,
            'totalEksemplar' => $totalEksemplar,
            'totalTersedia' => $totalTersedia,
            'totalPinjam' => $totalPinjam,
            'totalKembali' => $totalKembali,
            'masihDipinjam' => $masihDipinjam,
            'terlambat' => $terlambat,
            'dendaTerkumpul' => $dendaTerkumpul,
            'rekapSirkulasi' => $rekapSirkulasi,
            'totalKunjungan' => $totalKunjungan,
            'rekapKunjunganKeperluan' => $rekapKunjunganKeperluan,
            'rekapKunjunganUnit' => $rekapKunjunganUnit,
            'bukuPopuler' => $bukuPopuler,
        ];
    }
}
