<?php

namespace App\Services;

use App\Models\RolePermission;
use App\Support\Table;
use Illuminate\Support\Facades\DB;

/**
 * Agregator data Dashboard Induk Sarana & Prasarana (bidang=sarpras).
 *
 * Dashboard ini berperan sebagai "kendali pusat" seluruh urusan sarana
 * prasarana sekolah dan menjadi induk bagi bidang turunan yang memakai
 * master ruang/aset yang sama:
 *   - Laboratorium (inventaris alat, kebutuhan, pengadaan, peminjaman, kerusakan)
 *   - Teknisi & IT (work order perbaikan, pemeliharaan preventif)
 *   - Fasilitas & Penjaga (checklist kebersihan, jaga malam/ronda)
 *   - Keamanan (patroli & insiden yang menyangkut aset/fasilitas)
 *
 * Semua angka dihitung dari tabel nyata dan dibungkus `Table::exists()`
 * agar dashboard tetap tampil walau sebagian modul belum dimigrasi.
 *
 * ponytail: kalkulator statis per-request; tambahkan cache per-bidang bila
 * trafik dashboard melebihi 100 req/detik.
 */
final class SarprasDashboardService
{
    /** Status pekerjaan laboran yang masih menunggu keputusan Sarpras. */
    private const LAB_PENDING = ['draf', 'diajukan', 'diverifikasi'];

    /**
     * Susun seluruh data dashboard induk sarpras.
     *
     * @return array{
     *   ringkasan: array<string, mixed>,
     *   charts: array<string, mixed>,
     *   daftar: array<string, mixed>,
     *   lintasBidang: array<int, array<string, mixed>>,
     *   penanggungJawab: array<int, mixed>,
     *   peringatan: array<int, array<string, mixed>>
     * }
     */
    public static function build(mixed $user = null): array
    {
        $user = $user ?: session('user');

        $ringkasan = self::ringkasan();
        $charts    = self::charts();
        $daftar    = self::daftar();
        $lintas    = self::lintasBidang($user, $ringkasan);
        $pj        = self::penanggungJawab();

        // Skor kelayakan gabungan: proporsi ruang & unit aset berkondisi baik.
        $totalFisik = ($ringkasan['ruang_total'] ?? 0) + ($ringkasan['aset_unit'] ?? 0);
        $baikFisik  = ($ringkasan['ruang_baik'] ?? 0) + ($ringkasan['aset_baik_unit'] ?? 0);
        $ringkasan['skor_kelayakan'] = $totalFisik > 0
            ? (int) round(($baikFisik / $totalFisik) * 100)
            : 0;
        $ringkasan['persen_ruang_baik'] = ($ringkasan['ruang_total'] ?? 0) > 0
            ? (int) round((($ringkasan['ruang_baik'] ?? 0) / $ringkasan['ruang_total']) * 100)
            : 0;
        $ringkasan['persen_aset_baik'] = ($ringkasan['aset_unit'] ?? 0) > 0
            ? (int) round((($ringkasan['aset_baik_unit'] ?? 0) / $ringkasan['aset_unit']) * 100)
            : 0;

        return [
            'ringkasan'       => $ringkasan,
            'charts'          => $charts,
            'daftar'          => $daftar,
            'lintasBidang'    => $lintas,
            'penanggungJawab' => $pj,
            'peringatan'      => self::peringatan($ringkasan, $daftar),
        ];
    }

    /**
     * Angka kunci (KPI) lintas modul.
     */
    private static function ringkasan(): array
    {
        $r = [
            // Master ruang & gedung
            'ruang_total'        => 0,
            'ruang_baik'         => 0,
            'ruang_rusak_ringan' => 0,
            'ruang_rusak_berat'  => 0,
            'ruang_tanpa_pj'     => 0,
            'gedung_total'       => 0,
            // Inventaris aset
            'aset_jenis'         => 0,
            'aset_unit'          => 0,
            'aset_baik_unit'     => 0,
            'aset_rusak_unit'    => 0,
            'aset_perbaikan'     => 0,
            'aset_dipinjam'      => 0,
            'aset_tanpa_ruang'   => 0,
            'aset_nilai'         => 0.0,
            // Peminjaman sarpras
            'pinjam_total'       => 0,
            'pinjam_aktif'       => 0,
            'pinjam_terlambat'   => 0,
            'pinjam_bulan_ini'   => 0,
            // Teknisi & IT
            'wo_total'           => 0,
            'wo_antrean'         => 0,
            'wo_proses'          => 0,
            'wo_darurat'         => 0,
            'wo_selesai_bulan'   => 0,
            'pemeliharaan_total' => 0,
            'pemeliharaan_jatuh_tempo' => 0,
            // Laboratorium (turunan)
            'lab_item'           => 0,
            'lab_kebutuhan_pending' => 0,
            'lab_pengadaan_pending' => 0,
            'lab_peminjaman_aktif'  => 0,
            'lab_kerusakan_terbuka' => 0,
            // Fasilitas & penjaga (turunan)
            'kebersihan_total'   => 0,
            'kebersihan_masalah' => 0,
            'ronda_total'        => 0,
            'ronda_temuan'       => 0,
            // Keamanan (turunan)
            'insiden_aktif'      => 0,
        ];

        if (Table::exists('sarpras_ruang')) {
            $r['ruang_total'] = (int) DB::table('sarpras_ruang')->count();
            $r['ruang_baik']  = (int) DB::table('sarpras_ruang')->where('kondisi', 'baik')->count();
            $r['ruang_rusak_ringan'] = (int) DB::table('sarpras_ruang')->where('kondisi', 'rusak_ringan')->count();
            $r['ruang_rusak_berat']  = (int) DB::table('sarpras_ruang')->where('kondisi', 'rusak_berat')->count();
            $r['ruang_tanpa_pj'] = (int) DB::table('sarpras_ruang')
                ->where(function ($q) {
                    $q->whereNull('penanggung_jawab_ptk_id')->orWhere('penanggung_jawab_ptk_id', '');
                })
                ->count();
            $r['gedung_total'] = (int) DB::table('sarpras_ruang')
                ->whereNotNull('gedung')
                ->where('gedung', '<>', '')
                ->distinct()
                ->count('gedung');
        }

        if (Table::exists('sarpras_aset')) {
            $r['aset_jenis'] = (int) DB::table('sarpras_aset')->count();
            $r['aset_unit']  = (int) (DB::table('sarpras_aset')->sum('jumlah') ?: 0);
            $r['aset_baik_unit'] = (int) (DB::table('sarpras_aset')->where('kondisi', 'baik')->sum('jumlah') ?: 0);
            $r['aset_rusak_unit'] = (int) (DB::table('sarpras_aset')
                ->whereIn('kondisi', ['rusak_ringan', 'rusak_berat'])
                ->sum('jumlah') ?: 0);
            $r['aset_perbaikan'] = (int) DB::table('sarpras_aset')
                ->where('status_ketersediaan', 'perbaikan')
                ->count();
            $r['aset_dipinjam'] = (int) DB::table('sarpras_aset')
                ->where('status_ketersediaan', 'dipinjam')
                ->count();
            $r['aset_tanpa_ruang'] = (int) DB::table('sarpras_aset')->whereNull('ruang_id')->count();
            $r['aset_nilai'] = (float) (DB::table('sarpras_aset')
                ->sum(DB::raw('COALESCE(harga_perolehan, 0) * COALESCE(jumlah, 1)')) ?: 0);
        }

        // Peminjaman terpadu: modul lama `sarpras_peminjaman` dan modul
        // terunifikasi `laboran_peminjaman` dihitung bersama agar angka
        // dashboard induk mencerminkan transaksi nyata.
        $pinjamSumber = [];
        if (Table::exists('sarpras_peminjaman')) {
            $pinjamSumber[] = ['table' => 'sarpras_peminjaman', 'tgl' => 'tanggal_pinjam', 'kembali' => 'tanggal_kembali_aktual'];
        }
        if (Table::exists('laboran_peminjaman')) {
            $pinjamSumber[] = ['table' => 'laboran_peminjaman', 'tgl' => 'tanggal_pinjam', 'kembali' => 'tanggal_kembali_aktual'];
        }

        foreach ($pinjamSumber as $src) {
            $r['pinjam_total'] += (int) DB::table($src['table'])->count();
            $r['pinjam_aktif'] += (int) DB::table($src['table'])
                ->whereIn('status', ['dipinjam', 'terlambat'])
                ->count();
            $r['pinjam_terlambat'] += (int) DB::table($src['table'])
                ->where('status', 'dipinjam')
                ->whereDate('tanggal_kembali_rencana', '<', now()->toDateString())
                ->count();
            $r['pinjam_bulan_ini'] += (int) DB::table($src['table'])
                ->whereYear($src['tgl'], now()->year)
                ->whereMonth($src['tgl'], now()->month)
                ->count();
        }

        if (Table::exists('teknisi_work_order')) {
            $r['wo_total']   = (int) DB::table('teknisi_work_order')->count();
            $r['wo_antrean'] = (int) DB::table('teknisi_work_order')->where('status', 'antrean')->count();
            $r['wo_proses']  = (int) DB::table('teknisi_work_order')->where('status', 'proses')->count();
            $r['wo_darurat'] = (int) DB::table('teknisi_work_order')
                ->where('tingkat_urgensi', 'darurat')
                ->whereIn('status', ['antrean', 'proses', 'menunggu_sparepart'])
                ->count();
            $r['wo_selesai_bulan'] = (int) DB::table('teknisi_work_order')
                ->where('status', 'selesai')
                ->whereYear('tanggal', now()->year)
                ->whereMonth('tanggal', now()->month)
                ->count();
        }

        if (Table::exists('teknisi_pemeliharaan')) {
            $r['pemeliharaan_total'] = (int) DB::table('teknisi_pemeliharaan')->count();
            $r['pemeliharaan_jatuh_tempo'] = (int) DB::table('teknisi_pemeliharaan')
                ->whereIn('status', ['terjadwal', 'tertunda'])
                ->whereDate('tgl_jadwal', '<=', now()->addDays(14)->toDateString())
                ->count();
        }

        if (Table::exists('laboran_bahan_alat')) {
            $r['lab_item'] = (int) DB::table('laboran_bahan_alat')->count();
        }
        if (Table::exists('laboran_kebutuhan')) {
            $r['lab_kebutuhan_pending'] = (int) DB::table('laboran_kebutuhan')
                ->whereIn('status', self::LAB_PENDING)
                ->count();
        }
        if (Table::exists('laboran_usulan_pengadaan')) {
            $r['lab_pengadaan_pending'] = (int) DB::table('laboran_usulan_pengadaan')
                ->whereIn('status', self::LAB_PENDING)
                ->count();
        }
        if (Table::exists('laboran_peminjaman')) {
            $r['lab_peminjaman_aktif'] = (int) DB::table('laboran_peminjaman')
                ->whereIn('status', ['diajukan', 'disetujui', 'dipinjam', 'terlambat'])
                ->count();
        }
        if (Table::exists('laboran_berita_acara_kerusakan')) {
            $r['lab_kerusakan_terbuka'] = (int) DB::table('laboran_berita_acara_kerusakan')
                ->whereIn('status', self::LAB_PENDING)
                ->count();
        }

        if (Table::exists('kebersihan_checklist')) {
            $r['kebersihan_total'] = (int) DB::table('kebersihan_checklist')->count();
            $r['kebersihan_masalah'] = (int) DB::table('kebersihan_checklist')
                ->where(function ($q) {
                    $q->where('kondisi_kebersihan', 'perlu_tindakan')
                        ->orWhere('ketersediaan_air_sabun', 'air_mati');
                })
                ->count();
        }
        if (Table::exists('penjaga_malam_log')) {
            $r['ronda_total'] = (int) DB::table('penjaga_malam_log')->count();
            $r['ronda_temuan'] = (int) DB::table('penjaga_malam_log')
                ->where('situasi_keamanan', '<>', 'kondusif')
                ->count();
        }
        if (Table::exists('keamanan_insiden')) {
            $r['insiden_aktif'] = (int) DB::table('keamanan_insiden')
                ->where('status_penyelesaian', 'dalam_penanganan')
                ->count();
        }

        return $r;
    }

    /**
     * Dataset grafik dashboard induk.
     */
    private static function charts(): array
    {
        $charts = [
            'kondisiRuang'      => [],
            'kondisiAset'       => [],
            'kategoriAset'      => [],
            'gedung'            => [],
            'statusWorkOrder'   => [],
            'trenPeminjaman'    => ['labels' => [], 'pinjam' => [], 'kembali' => []],
        ];

        if (Table::exists('sarpras_ruang')) {
            $charts['kondisiRuang'] = [
                'Baik'         => (int) DB::table('sarpras_ruang')->where('kondisi', 'baik')->count(),
                'Rusak Ringan' => (int) DB::table('sarpras_ruang')->where('kondisi', 'rusak_ringan')->count(),
                'Rusak Berat'  => (int) DB::table('sarpras_ruang')->where('kondisi', 'rusak_berat')->count(),
            ];

            $charts['gedung'] = DB::table('sarpras_ruang')
                ->selectRaw('COALESCE(gedung, "Tanpa Gedung") as gedung, COUNT(*) as total')
                ->groupBy('gedung')
                ->orderByDesc('total')
                ->limit(8)
                ->pluck('total', 'gedung')
                ->map(fn ($v) => (int) $v)
                ->toArray();
        }

        if (Table::exists('sarpras_aset')) {
            $charts['kondisiAset'] = [
                'Baik'         => (int) (DB::table('sarpras_aset')->where('kondisi', 'baik')->sum('jumlah') ?: 0),
                'Rusak Ringan' => (int) (DB::table('sarpras_aset')->where('kondisi', 'rusak_ringan')->sum('jumlah') ?: 0),
                'Rusak Berat'  => (int) (DB::table('sarpras_aset')->where('kondisi', 'rusak_berat')->sum('jumlah') ?: 0),
            ];

            $charts['kategoriAset'] = DB::table('sarpras_aset')
                ->selectRaw('COALESCE(kategori, "Umum") as kategori, COUNT(*) as total')
                ->groupBy('kategori')
                ->orderByDesc('total')
                ->limit(6)
                ->pluck('total', 'kategori')
                ->map(fn ($v) => (int) $v)
                ->toArray();
        }

        if (Table::exists('sarpras_peminjaman') || Table::exists('laboran_peminjaman')) {
            $charts['trenPeminjaman'] = self::trenPeminjaman();
        }

        if (Table::exists('teknisi_work_order')) {
            $label = [
                'antrean'            => 'Antrean',
                'proses'             => 'Proses',
                'menunggu_sparepart' => 'Menunggu Part',
                'selesai'            => 'Selesai',
            ];
            $raw = DB::table('teknisi_work_order')
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            $statusWo = [];
            foreach ($label as $key => $lbl) {
                $statusWo[$lbl] = (int) ($raw[$key] ?? 0);
            }
            $charts['statusWorkOrder'] = $statusWo;
        }

        return $charts;
    }

    /**
     * Tren peminjaman sarpras 6 bulan terakhir (dihitung di PHP agar
     * tidak bergantung pada fungsi tanggal spesifik MySQL).
     */
    private static function trenPeminjaman(): array
    {
        $awal = now()->subMonths(5)->startOfMonth();

        $rows = collect();
        foreach (['sarpras_peminjaman', 'laboran_peminjaman'] as $table) {
            if (!Table::exists($table)) {
                continue;
            }
            $rows = $rows->merge(
                DB::table($table)
                    ->where('tanggal_pinjam', '>=', $awal->toDateString())
                    ->select('tanggal_pinjam', 'tanggal_kembali_aktual')
                    ->get()
            );
        }

        $labels  = [];
        $pinjam  = [];
        $kembali = [];
        $bucket  = [];

        for ($i = 0; $i < 6; $i++) {
            $bulan = $awal->copy()->addMonths($i);
            $key = $bulan->format('Y-m');
            $labels[] = $bulan->translatedFormat('M Y');
            $bucket[$key] = ['pinjam' => 0, 'kembali' => 0];
        }

        foreach ($rows as $row) {
            $keyPinjam = $row->tanggal_pinjam ? substr((string) $row->tanggal_pinjam, 0, 7) : null;
            if ($keyPinjam && isset($bucket[$keyPinjam])) {
                $bucket[$keyPinjam]['pinjam']++;
            }
            $keyKembali = $row->tanggal_kembali_aktual ? substr((string) $row->tanggal_kembali_aktual, 0, 7) : null;
            if ($keyKembali && isset($bucket[$keyKembali])) {
                $bucket[$keyKembali]['kembali']++;
            }
        }

        foreach ($bucket as $data) {
            $pinjam[]  = $data['pinjam'];
            $kembali[] = $data['kembali'];
        }

        return ['labels' => $labels, 'pinjam' => $pinjam, 'kembali' => $kembali];
    }

    /**
     * Daftar baris yang butuh tindakan (tabel ringkas dashboard).
     */
    private static function daftar(): array
    {
        $daftar = [
            'ruangBermasalah'   => [],
            'asetPerbaikan'     => [],
            'woTerbaru'         => [],
            'peminjamanAktif'   => [],
            'pengadaanMenunggu' => [],
            'kebersihanTemuan'  => [],
            'insidenTerbaru'    => [],
        ];

        if (Table::exists('sarpras_ruang')) {
            $daftar['ruangBermasalah'] = DB::table('sarpras_ruang as sr')
                ->leftJoin('gtk', 'sr.penanggung_jawab_ptk_id', '=', 'gtk.ptk_id')
                ->whereIn('sr.kondisi', ['rusak_ringan', 'rusak_berat'])
                ->select('sr.kode_ruang', 'sr.nama_ruang', 'sr.gedung', 'sr.kondisi', 'sr.keterangan', 'gtk.nama as pj_nama')
                ->orderByRaw("CASE sr.kondisi WHEN 'rusak_berat' THEN 1 ELSE 2 END")
                ->orderBy('sr.nama_ruang')
                ->limit(8)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }

        if (Table::exists('sarpras_aset')) {
            $daftar['asetPerbaikan'] = DB::table('sarpras_aset as sa')
                ->leftJoin('sarpras_ruang as sr', 'sa.ruang_id', '=', 'sr.id')
                ->where(function ($q) {
                    $q->where('sa.status_ketersediaan', 'perbaikan')
                        ->orWhereIn('sa.kondisi', ['rusak_ringan', 'rusak_berat']);
                })
                ->select('sa.kode_aset', 'sa.nama_barang', 'sa.kategori', 'sa.kondisi', 'sa.jumlah', 'sa.status_ketersediaan', 'sr.nama_ruang')
                ->orderBy('sa.nama_barang')
                ->limit(8)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }

        if (Table::exists('teknisi_work_order')) {
            $daftar['woTerbaru'] = DB::table('teknisi_work_order')
                ->select('nomor_wo', 'tanggal', 'lokasi_unit', 'kategori_perbaikan', 'tingkat_urgensi', 'status', 'pelapor_nama')
                ->orderByDesc('tanggal')
                ->orderByDesc('id')
                ->limit(8)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }

        if (Table::exists('sarpras_peminjaman')) {
            $daftar['peminjamanAktif'] = DB::table('sarpras_peminjaman as sp')
                ->leftJoin('sarpras_aset as sa', 'sp.aset_id', '=', 'sa.id')
                ->whereIn('sp.status', ['dipinjam', 'terlambat'])
                ->select(
                    'sp.nomor_pinjam',
                    'sp.peminjam_nama',
                    'sp.peminjam_tipe',
                    'sp.tanggal_pinjam',
                    'sp.tanggal_kembali_rencana',
                    'sp.status',
                    'sa.nama_barang'
                )
                ->orderBy('sp.tanggal_kembali_rencana')
                ->limit(8)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }

        if (Table::exists('laboran_peminjaman')) {
            $labPinjam = DB::table('laboran_peminjaman')
                ->whereIn('status', ['diajukan', 'disetujui', 'dipinjam', 'terlambat'])
                ->select(
                    'nomor_pinjam',
                    'peminjam_nama',
                    'peminjam_tipe',
                    'tanggal_pinjam',
                    'tanggal_kembali_rencana',
                    'status',
                    'ruang_lab_nama'
                )
                ->orderBy('tanggal_kembali_rencana')
                ->limit(8)
                ->get()
                ->map(function ($row) {
                    $arr = (array) $row;
                    $arr['nama_barang'] = $arr['ruang_lab_nama'] ?? null;

                    return $arr;
                })
                ->all();

            // Gabungkan, urutkan ulang berdasarkan tenggat, batasi 8 baris.
            $gabung = collect($daftar['peminjamanAktif'])
                ->merge($labPinjam)
                ->sortBy('tanggal_kembali_rencana')
                ->take(8)
                ->values()
                ->all();

            $daftar['peminjamanAktif'] = $gabung;
        }

        if (Table::exists('laboran_usulan_pengadaan')) {
            $daftar['pengadaanMenunggu'] = DB::table('laboran_usulan_pengadaan as u')
                ->leftJoin('jurusan_meta as jm', 'u.jurusan_id', '=', 'jm.jurusan_id')
                ->whereIn('u.status', self::LAB_PENDING)
                ->select('u.nomor_usulan', 'u.pemohon_nama', 'u.jenis', 'u.total_item', 'u.total_estimasi_biaya', 'u.urgensi', 'u.status', 'u.tanggal_usulan', 'jm.singkatan as jurusan')
                ->orderByRaw("CASE u.urgensi WHEN 'kritis' THEN 1 WHEN 'tinggi' THEN 2 ELSE 3 END")
                ->orderByDesc('u.tanggal_usulan')
                ->limit(8)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }

        if (Table::exists('kebersihan_checklist')) {
            $daftar['kebersihanTemuan'] = DB::table('kebersihan_checklist')
                ->where(function ($q) {
                    $q->where('kondisi_kebersihan', 'perlu_tindakan')
                        ->orWhere('ketersediaan_air_sabun', 'air_mati');
                })
                ->select('tanggal', 'shift', 'area_zona', 'kondisi_kebersihan', 'ketersediaan_air_sabun', 'catatan_temuan')
                ->orderByDesc('tanggal')
                ->limit(8)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }

        if (Table::exists('keamanan_insiden')) {
            $daftar['insidenTerbaru'] = DB::table('keamanan_insiden')
                ->select('nomor_laporan', 'tanggal', 'judul_insiden', 'lokasi_kejadian', 'tingkat_urgensi', 'status_penyelesaian')
                ->orderByDesc('tanggal')
                ->orderByDesc('id')
                ->limit(6)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }

        return $daftar;
    }

    /**
     * Kartu kendali modul turunan (laboran, teknisi, penjaga, keamanan).
     *
     * `count` selalu ditampilkan sebagai beban kerja lintas bidang; tautan
     * hanya aktif bila pengguna memegang izin modul tersebut.
     */
    private static function lintasBidang(mixed $user, array $ringkasan): array
    {
        $definisi = [
            [
                'key'   => 'lab_inventaris',
                'label' => 'Inventaris Lab',
                'desc'  => 'Alat & bahan praktik per jurusan',
                'icon'  => 'fa-vials',
                'color' => '#06b6d4',
                'route' => 'dashboard.laboran.inventaris.index',
                'perm'  => 'menu_laboran',
                'count' => $ringkasan['lab_item'] ?? 0,
                'satuan' => 'item',
            ],
            [
                'key'   => 'lab_kebutuhan',
                'label' => 'Analisis Kebutuhan',
                'desc'  => 'Kekurangan alat/bahan menunggu keputusan',
                'icon'  => 'fa-clipboard-list',
                'color' => '#f59e0b',
                'route' => 'dashboard.laboran.kebutuhan.index',
                'perm'  => 'menu_laboran',
                'count' => $ringkasan['lab_kebutuhan_pending'] ?? 0,
                'satuan' => 'usulan',
            ],
            [
                'key'   => 'lab_pengadaan',
                'label' => 'Usulan Pengadaan',
                'desc'  => 'Pengadaan alat/bahan lab menunggu verifikasi',
                'icon'  => 'fa-file-invoice-dollar',
                'color' => '#8b5cf6',
                'route' => 'dashboard.laboran.pengadaan.index',
                'perm'  => 'menu_laboran',
                'count' => $ringkasan['lab_pengadaan_pending'] ?? 0,
                'satuan' => 'usulan',
            ],
            [
                'key'   => 'lab_peminjaman',
                'label' => 'Peminjaman Alat',
                'desc'  => 'Peminjaman alat laboratorium berjalan',
                'icon'  => 'fa-hand-holding',
                'color' => '#0ea5e9',
                'route' => 'dashboard.laboran.peminjaman-alat.index',
                'perm'  => 'menu_laboran',
                'count' => $ringkasan['lab_peminjaman_aktif'] ?? 0,
                'satuan' => 'transaksi',
            ],
            [
                'key'   => 'lab_kerusakan',
                'label' => 'BA Kerusakan',
                'desc'  => 'Berita acara kerusakan alat terbuka',
                'icon'  => 'fa-triangle-exclamation',
                'color' => '#ef4444',
                'route' => 'dashboard.laboran.kerusakan.index',
                'perm'  => 'menu_laboran',
                'count' => $ringkasan['lab_kerusakan_terbuka'] ?? 0,
                'satuan' => 'berita acara',
            ],
            [
                'key'   => 'teknisi_wo',
                'label' => 'Work Order Teknisi',
                'desc'  => 'Tiket perbaikan gedung, listrik, AC & IT',
                'icon'  => 'fa-wrench',
                'color' => '#6366f1',
                'route' => 'dashboard.teknisi.work-order',
                'perm'  => 'menu_teknisi',
                'count' => ($ringkasan['wo_antrean'] ?? 0) + ($ringkasan['wo_proses'] ?? 0),
                'satuan' => 'tiket',
            ],
            [
                'key'   => 'teknisi_pemeliharaan',
                'label' => 'Pemeliharaan Preventif',
                'desc'  => 'Jadwal perawatan berkala aset & fasilitas',
                'icon'  => 'fa-calendar-check',
                'color' => '#14b8a6',
                'route' => 'dashboard.teknisi.pemeliharaan',
                'perm'  => 'menu_teknisi',
                'count' => $ringkasan['pemeliharaan_jatuh_tempo'] ?? 0,
                'satuan' => 'jadwal',
            ],
            [
                'key'   => 'penjaga_kebersihan',
                'label' => 'Kebersihan Fasilitas',
                'desc'  => 'Checklist zona yang perlu tindakan',
                'icon'  => 'fa-broom',
                'color' => '#84cc16',
                'route' => 'dashboard.penjaga.kebersihan.index',
                'perm'  => 'menu_penjaga',
                'count' => $ringkasan['kebersihan_masalah'] ?? 0,
                'satuan' => 'temuan',
            ],
            [
                'key'   => 'penjaga_ronda',
                'label' => 'Jaga Malam & Ronda',
                'desc'  => 'Log kontrol malam dengan temuan',
                'icon'  => 'fa-moon',
                'color' => '#3b82f6',
                'route' => 'dashboard.penjaga.ronda.index',
                'perm'  => 'menu_penjaga',
                'count' => $ringkasan['ronda_temuan'] ?? 0,
                'satuan' => 'temuan',
            ],
            [
                'key'   => 'keamanan_patroli',
                'label' => 'Insiden Keamanan',
                'desc'  => 'Laporan insiden fasilitas dalam penanganan',
                'icon'  => 'fa-shield-halved',
                'color' => '#ef4444',
                'route' => 'dashboard.keamanan.patroli.index',
                'perm'  => 'menu_keamanan',
                'count' => $ringkasan['insiden_aktif'] ?? 0,
                'satuan' => 'laporan',
            ],
        ];

        $hasil = [];
        foreach ($definisi as $item) {
            $item['allowed'] = RolePermission::canAccess($user ?: 'tendik', $item['perm']);
            $hasil[] = $item;
        }

        return $hasil;
    }

    /**
     * Penanggung jawab ruang (GTK) beserta jumlah ruang yang diampu.
     */
    private static function penanggungJawab(): array
    {
        if (!Table::exists('sarpras_ruang') || !Table::exists('gtk')) {
            return [];
        }

        return DB::table('sarpras_ruang as sr')
            ->join('gtk', 'sr.penanggung_jawab_ptk_id', '=', 'gtk.ptk_id')
            ->select('gtk.nama', 'gtk.nip', 'gtk.jabatan_ptk_id_str as jabatan', DB::raw('COUNT(sr.id) as jumlah_ruang'))
            ->groupBy('gtk.ptk_id', 'gtk.nama', 'gtk.nip', 'gtk.jabatan_ptk_id_str')
            ->orderByDesc('jumlah_ruang')
            ->limit(8)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * Peringatan operasional yang harus ditindaklanjuti staf Sarpras.
     *
     * @return array<int, array{tone: string, icon: string, text: string, count: int}>
     */
    private static function peringatan(array $r, array $daftar): array
    {
        $hasil = [];

        if (($r['ruang_rusak_berat'] ?? 0) > 0) {
            $hasil[] = [
                'tone'  => 'danger',
                'icon'  => 'fa-building-circle-exclamation',
                'text'  => 'Ruang berkondisi rusak berat perlu prioritas perbaikan',
                'count' => (int) $r['ruang_rusak_berat'],
            ];
        }
        if (($r['wo_darurat'] ?? 0) > 0) {
            $hasil[] = [
                'tone'  => 'danger',
                'icon'  => 'fa-tower-broadcast',
                'text'  => 'Work order darurat belum tuntas ditangani teknisi',
                'count' => (int) $r['wo_darurat'],
            ];
        }
        if (($r['pinjam_terlambat'] ?? 0) > 0) {
            $hasil[] = [
                'tone'  => 'warning',
                'icon'  => 'fa-hourglass-end',
                'text'  => 'Peminjaman aset melewati tenggat pengembalian',
                'count' => (int) $r['pinjam_terlambat'],
            ];
        }
        if (($r['aset_perbaikan'] ?? 0) > 0) {
            $hasil[] = [
                'tone'  => 'warning',
                'icon'  => 'fa-screwdriver-wrench',
                'text'  => 'Aset berstatus perbaikan belum kembali ke peredaran',
                'count' => (int) $r['aset_perbaikan'],
            ];
        }
        if (($r['ruang_tanpa_pj'] ?? 0) > 0) {
            $hasil[] = [
                'tone'  => 'info',
                'icon'  => 'fa-user-slash',
                'text'  => 'Ruang belum memiliki penanggung jawab',
                'count' => (int) $r['ruang_tanpa_pj'],
            ];
        }
        if (($r['aset_tanpa_ruang'] ?? 0) > 0) {
            $hasil[] = [
                'tone'  => 'info',
                'icon'  => 'fa-location-dot',
                'text'  => 'Aset belum ditempatkan pada ruang tertentu',
                'count' => (int) $r['aset_tanpa_ruang'],
            ];
        }
        if (($r['lab_pengadaan_pending'] ?? 0) > 0) {
            $hasil[] = [
                'tone'  => 'info',
                'icon'  => 'fa-file-circle-exclamation',
                'text'  => 'Usulan pengadaan laboratorium menunggu verifikasi Sarpras',
                'count' => (int) $r['lab_pengadaan_pending'],
            ];
        }
        if (($r['kebersihan_masalah'] ?? 0) > 0) {
            $hasil[] = [
                'tone'  => 'warning',
                'icon'  => 'fa-broom',
                'text'  => 'Temuan kebersihan/sanitasi memerlukan tindak lanjut',
                'count' => (int) $r['kebersihan_masalah'],
            ];
        }

        return $hasil;
    }
}
