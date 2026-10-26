<?php

namespace App\Http\Controllers\Laboran;

use App\Support\LaboranScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard Laboratorium per Jurusan.
 *
 * Menyajikan ringkasan informatif: kondisi inventaris, kebutuhan, perawatan
 * jatuh tempo, peminjaman aktif, kerusakan, pemeriksaan, dan pengadaan —
 * seluruhnya dibatasi pada cakupan jurusan pengguna.
 */
class LaboranDashboardController extends LaboranBaseController
{
    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $jurusanId = $context['jurusan_id'];

        // ---------------------------------------------------------------
        // Identitas jurusan
        // ---------------------------------------------------------------
        $jurusan = null;
        if ($jurusanId) {
            $jurusan = DB::table('jurusan_meta')->where('jurusan_id', $jurusanId)->first();
        }
        if (!$jurusan && $context['unrestricted']) {
            $jurusan = DB::table('jurusan_meta')->orderBy('nama_jurusan')->first();
        }

        // ---------------------------------------------------------------
        // 1. Inventaris
        // ---------------------------------------------------------------
        $invBase = DB::table('laboran_bahan_alat');
        LaboranScope::apply($invBase, 'jurusan_id', $context);
        $inventaris = $invBase->get();

        $totalItem    = $inventaris->count();
        $totalAlat    = $inventaris->where('jenis', 'alat')->count();
        $totalBahan   = $inventaris->where('jenis', 'bahan_habis_pakai')->count();
        $kondisiBaik  = $inventaris->where('kondisi', 'baik')->count();
        $kondisiRusak = $inventaris->whereIn('kondisi', ['rusak_ringan', 'rusak_berat'])->count();
        $kondisiKedaluwarsa = $inventaris->where('kondisi', 'kedaluwarsa')->count();

        $persenLaik = $totalItem > 0 ? round(($kondisiBaik / $totalItem) * 100, 1) : 0;

        // Stok menipis
        $stokMenipis = $inventaris->filter(function ($it) {
            $tersedia = (float) $it->stok_tersedia;
            $total    = (float) $it->stok_total;

            return $tersedia <= 0 || ($total > 0 && ($tersedia / max($total, 1)) <= 0.25);
        })->take(8)->values();

        // ---------------------------------------------------------------
        // 2. Praktik / jadwal
        // ---------------------------------------------------------------
        $jadwalBase = DB::table('laboran_jadwal_penggunaan');
        LaboranScope::apply($jadwalBase, 'jurusan_id', $context);

        $jadwalHariIni = (clone $jadwalBase)->where('tanggal', now()->toDateString())->count();
        $jadwalMinggu  = (clone $jadwalBase)
            ->whereBetween('tanggal', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])
            ->count();
        $jadwalBulan   = (clone $jadwalBase)
            ->whereBetween('tanggal', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();
        $jadwalTotal   = (clone $jadwalBase)->count();

        $jadwalTerdekat = (clone $jadwalBase)
            ->where('tanggal', '>=', now()->toDateString())
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->limit(6)
            ->get();

        // ---------------------------------------------------------------
        // 3. Peminjaman
        // ---------------------------------------------------------------
        $pinjamBase = DB::table('laboran_peminjaman');
        LaboranScope::apply($pinjamBase, 'jurusan_id', $context);

        $pinjamAktif = (clone $pinjamBase)->whereIn('status', ['dipinjam', 'terlambat'])->count();
        $pinjamMenunggu = (clone $pinjamBase)->where('status', 'diajukan')->count();
        $pinjamTerlambat = (clone $pinjamBase)
            ->whereIn('status', ['dipinjam', 'terlambat'])
            ->whereNotNull('tanggal_kembali_rencana')
            ->where('tanggal_kembali_rencana', '<', now()->toDateString())
            ->count();
        $pinjamTotal = (clone $pinjamBase)->count();

        $pinjamTerbaru = (clone $pinjamBase)
            ->whereIn('status', ['diajukan', 'dipinjam', 'terlambat'])
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        // ---------------------------------------------------------------
        // 4. Kerusakan
        // ---------------------------------------------------------------
        $rusakBase = DB::table('laboran_berita_acara_kerusakan');
        LaboranScope::apply($rusakBase, 'jurusan_id', $context);

        $rusakTotal    = (clone $rusakBase)->count();
        $rusakBerat    = (clone $rusakBase)->whereIn('tingkat_kerusakan', ['berat', 'total'])->count();
        $rusakBulanIni = (clone $rusakBase)
            ->whereBetween('tanggal_kejadian', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();
        $rusakEstimasi = (float) (clone $rusakBase)->sum('estimasi_biaya');

        $rusakTerbaru = (clone $rusakBase)->orderByDesc('tanggal_kejadian')->limit(6)->get();

        // ---------------------------------------------------------------
        // 5. Perawatan
        // ---------------------------------------------------------------
        $rawatBase = DB::table('laboran_perawatan');
        LaboranScope::apply($rawatBase, 'jurusan_id', $context);

        $rawatTotal      = (clone $rawatBase)->count();
        $rawatDijadwalkan = (clone $rawatBase)->where('status', 'dijadwalkan')->count();
        $rawatProses     = (clone $rawatBase)->where('status', 'proses')->count();
        $rawatSelesai    = (clone $rawatBase)->where('status', 'selesai')->count();

        $kartuBase = DB::table('laboran_kartu_perawatan');
        LaboranScope::apply($kartuBase, 'jurusan_id', $context);
        $kartuAktif = (clone $kartuBase)->where('status', 'aktif')->count();
        $kartuJatuhTempo = (clone $kartuBase)
            ->where('status', 'aktif')
            ->whereNotNull('jadwal_berikutnya')
            ->where('jadwal_berikutnya', '<=', now()->addDays(7)->toDateString())
            ->orderBy('jadwal_berikutnya')
            ->limit(6)
            ->get();

        // ---------------------------------------------------------------
        // 6. Pemeriksaan & Kebutuhan & Pengadaan
        // ---------------------------------------------------------------
        $periksaBase = DB::table('laboran_pemeriksaan');
        LaboranScope::apply($periksaBase, 'jurusan_id', $context);
        $periksaTotal = (clone $periksaBase)->count();
        $periksaBulanIni = (clone $periksaBase)
            ->whereBetween('tanggal_pemeriksaan', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();
        $periksaTerakhir = (clone $periksaBase)->orderByDesc('tanggal_pemeriksaan')->first();

        $kebutuhanBase = DB::table('laboran_kebutuhan');
        LaboranScope::apply($kebutuhanBase, 'jurusan_id', $context);
        $kebutuhanTotal   = (clone $kebutuhanBase)->count();
        $kebutuhanKritis  = (clone $kebutuhanBase)->where('prioritas', 'kritis')->count();
        $kebutuhanDraf    = (clone $kebutuhanBase)->where('status', 'draf')->count();

        $kebutuhanPrioritas = (clone $kebutuhanBase)
            ->whereIn('prioritas', ['tinggi', 'kritis'])
            ->orderByRaw("CASE prioritas WHEN 'kritis' THEN 1 ELSE 2 END")
            ->orderByDesc('selisih')
            ->limit(6)
            ->get();

        $pengadaanBase = DB::table('laboran_usulan_pengadaan');
        LaboranScope::apply($pengadaanBase, 'jurusan_id', $context);
        $pengadaanTotal     = (clone $pengadaanBase)->count();
        $pengadaanDiajukan  = (clone $pengadaanBase)->whereIn('status', ['diajukan', 'diverifikasi'])->count();
        $pengadaanDisetujui = (clone $pengadaanBase)->whereIn('status', ['disetujui', 'direalisasi'])->count();
        $pengadaanNilai     = (float) (clone $pengadaanBase)->sum('total_estimasi_biaya');

        // ---------------------------------------------------------------
        // 7. Tren 6 bulan terakhir (praktik & kerusakan)
        // ---------------------------------------------------------------
        $trenBulanan = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->copy()->subMonths($i);
            $awal = $m->copy()->startOfMonth()->toDateString();
            $akhir = $m->copy()->endOfMonth()->toDateString();

            $qJ = DB::table('laboran_jadwal_penggunaan')->whereBetween('tanggal', [$awal, $akhir]);
            LaboranScope::apply($qJ, 'jurusan_id', $context);

            $qR = DB::table('laboran_berita_acara_kerusakan')->whereBetween('tanggal_kejadian', [$awal, $akhir]);
            LaboranScope::apply($qR, 'jurusan_id', $context);

            $trenBulanan[] = [
                'label'   => $m->translatedFormat('M'),
                'praktik' => $qJ->count(),
                'rusak'   => $qR->count(),
            ];
        }

        $maxTren = max(1, max(array_column($trenBulanan, 'praktik') ?: [1]));

        // ---------------------------------------------------------------
        // 8. Sebaran per lokasi
        // ---------------------------------------------------------------
        $qLokasi = DB::table('laboran_bahan_alat')
            ->select('ruang_lab_nama', DB::raw('COUNT(*) as total'))
            ->whereNotNull('ruang_lab_nama')
            ->groupBy('ruang_lab_nama')
            ->orderByDesc('total')
            ->limit(6);
        LaboranScope::apply($qLokasi, 'jurusan_id', $context);
        $sebaranLokasi = $qLokasi->get();

        return view('dashboard.laboran.dashboard', array_merge(
            compact(
                'jurusan',
                'totalItem', 'totalAlat', 'totalBahan', 'kondisiBaik', 'kondisiRusak',
                'kondisiKedaluwarsa', 'persenLaik', 'stokMenipis',
                'jadwalHariIni', 'jadwalMinggu', 'jadwalBulan', 'jadwalTotal', 'jadwalTerdekat',
                'pinjamAktif', 'pinjamMenunggu', 'pinjamTerlambat', 'pinjamTotal', 'pinjamTerbaru',
                'rusakTotal', 'rusakBerat', 'rusakBulanIni', 'rusakEstimasi', 'rusakTerbaru',
                'rawatTotal', 'rawatDijadwalkan', 'rawatProses', 'rawatSelesai', 'kartuAktif', 'kartuJatuhTempo',
                'periksaTotal', 'periksaBulanIni', 'periksaTerakhir',
                'kebutuhanTotal', 'kebutuhanKritis', 'kebutuhanDraf', 'kebutuhanPrioritas',
                'pengadaanTotal', 'pengadaanDiajukan', 'pengadaanDisetujui', 'pengadaanNilai',
                'trenBulanan', 'maxTren', 'sebaranLokasi'
            ),
            $this->abilities(),
            [
                'context'    => $context,
                'scopeBadge' => LaboranScope::badge($context),
            ]
        ));
    }
}
