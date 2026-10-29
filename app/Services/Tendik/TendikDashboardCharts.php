<?php

namespace App\Services\Tendik;

use App\Models\TendikIndikatorKinerja;
use App\Services\KepegawaianStatsService;
use App\Support\Table;
use Illuminate\Support\Facades\DB;

/**
 * Chart & indikator kinerja per bidang tugas dashboard Tendik.
 * ponytail: query agregat inline; ubah ke view/rollup bila data >100k baris.
 */
class TendikDashboardCharts
{
    private static function groupedCount(string $table, string $field, string $alias, string $fallback = 'Lainnya', int $limit = 8): array
    {
        if (!Table::exists($table)) {
            return [];
        }
        return DB::table($table)
            ->selectRaw("COALESCE(NULLIF({$field}, \"\"), ?) as `{$alias}`, COUNT(*) as total", [$fallback])
            ->groupBy($alias)
            ->orderByDesc('total')
            ->limit($limit)
            ->pluck('total', $alias)
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }

    public static function kepegawaian(): array
    {
        if (!Table::exists('gtk')) {
            return [];
        }
        $group = fn (string $col) => DB::table('gtk')
            ->selectRaw("{$col}, count(*) as total")
            ->whereNotNull($col)->where($col, '!=', '')
            ->groupBy($col)->orderByDesc('total')->pluck('total', $col)->toArray();

        return [
            'jenisKelamin'        => DB::table('gtk')->selectRaw('jenis_kelamin, count(*) as total')->whereIn('jenis_kelamin', ['L', 'P'])->groupBy('jenis_kelamin')->pluck('total', 'jenis_kelamin')->toArray(),
            'pendidikan'          => $group('pendidikan_terakhir'),
            'jenisPtk'            => $group('jenis_ptk_id_str'),
            'statusKepegawaian'   => $group('status_kepegawaian_id_str'),
        ];
    }

    public static function umum(): array
    {
        return [
            'populasi' => [
                'Peserta Didik'  => Table::exists('peserta_didik') ? DB::table('peserta_didik')->count() : 0,
                'Guru Pendidik'  => Table::exists('gtk') ? DB::table('gtk')->where('jenis_ptk_id_str', 'LIKE', '%Guru%')->count() : 0,
                'Tenaga Tendik'  => Table::exists('gtk') ? DB::table('gtk')->where('jenis_ptk_id_str', 'NOT LIKE', '%Guru%')->count() : 0,
                'Rombel Belajar' => Table::exists('rombongan_belajar') ? DB::table('rombongan_belajar')->count() : 0,
            ],
            'siswaTingkat' => Table::exists('peserta_didik')
                ? DB::table('peserta_didik')->selectRaw('COALESCE(tingkat_pendidikan_id, "Lainnya") as tingkat, count(*) as total')->groupBy('tingkat')->orderBy('tingkat')->pluck('total', 'tingkat')->toArray()
                : [],
            'genderSiswa' => Table::exists('peserta_didik')
                ? DB::table('peserta_didik')->selectRaw('jenis_kelamin, count(*) as total')->whereIn('jenis_kelamin', ['L', 'P'])->groupBy('jenis_kelamin')->pluck('total', 'jenis_kelamin')->toArray()
                : [],
            'jurusanRombel' => Table::exists('rombongan_belajar')
                ? DB::table('rombongan_belajar')->selectRaw('COALESCE(jurusan_id_str, "Reguler / Umum") as jurusan, count(*) as total')->whereNotNull('jurusan_id_str')->where('jurusan_id_str', '!=', '')->groupBy('jurusan')->orderByDesc('total')->limit(6)->pluck('total', 'jurusan')->toArray()
                : [],
        ];
    }

    public static function kesiswaan(array $chartsUmum): array
    {
        return [
            'gender'      => $chartsUmum['genderSiswa'],
            'tingkat'     => $chartsUmum['siswaTingkat'],
            'pendaftaran' => Table::exists('peserta_didik')
                ? DB::table('peserta_didik')->selectRaw('COALESCE(jenis_pendaftaran_id_str, "Reguler") as jenis, count(*) as total')->groupBy('jenis')->pluck('total', 'jenis')->toArray()
                : [],
            'jurusan'     => $chartsUmum['jurusanRombel'],
        ];
    }

    public static function persuratan(): array
    {
        $jenisCounts = [];
        if (Table::exists('persuratan')) {
            $labelMap = ['masuk' => 'Surat Masuk', 'keluar' => 'Surat Keluar', 'keputusan' => 'SK Dinas', 'tugas' => 'Surat Tugas (SPT)'];
            foreach (DB::table('persuratan')->selectRaw('COALESCE(jenis_surat, "lainnya") as jenis, count(*) as total')->groupBy('jenis')->pluck('total', 'jenis')->toArray() as $k => $c) {
                $lbl = $labelMap[strtolower($k)] ?? ucfirst($k);
                $jenisCounts[$lbl] = ($jenisCounts[$lbl] ?? 0) + $c;
            }
        }
        foreach (['surat_keterangan_pd' => 'Surat Keterangan', 'gtk_spt' => 'Surat Tugas (SPT)'] as $tbl => $label) {
            if (Table::exists($tbl) && ($n = DB::table($tbl)->count()) > 0) {
                $jenisCounts[$label] = ($jenisCounts[$label] ?? 0) + $n;
            }
        }

        $statusCounts = [];
        if (Table::exists('persuratan')) {
            $labelMap = [
                'menunggu_disposisi' => 'Menunggu Disposisi',
                'diproses'           => 'Sedang Diproses',
                'selesai'            => 'Selesai / Terbit',
                'diarsipkan'         => 'Diarsipkan',
                'draf'               => 'Draf',
            ];
            foreach (DB::table('persuratan')->selectRaw('COALESCE(status, "draf") as status, count(*) as total')->groupBy('status')->pluck('total', 'status')->toArray() as $k => $c) {
                $statusCounts[$labelMap[strtolower($k)] ?? ucfirst($k)] = $c;
            }
        }

        return [
            'jenis'  => $jenisCounts ?: ['Surat Masuk' => 0, 'Surat Keluar' => 0],
            'status' => $statusCounts ?: ['Selesai / Terbit' => 0, 'Menunggu Disposisi' => 0],
        ];
    }

    public static function laboran(): array
    {
        if (!Table::exists('laboran_bahan_alat')) {
            return ['sebaranUnit' => [], 'statusAlat' => [], 'kategoriBahan' => []];
        }
        $t = DB::table('laboran_bahan_alat');

        return [
            'sebaranUnit'   => self::groupedCount('laboran_bahan_alat', 'ruang_lab_nama', 'unit', 'Tanpa Ruang'),
            'statusAlat'    => [
                'Kondisi Baik' => (int) (DB::table('laboran_bahan_alat')->sum('jumlah_baik') ?: 0),
                'Rusak Ringan' => (int) (DB::table('laboran_bahan_alat')->sum('jumlah_rusak_ringan') ?: 0),
                'Rusak Berat'  => (int) (DB::table('laboran_bahan_alat')->sum('jumlah_rusak_berat') ?: 0),
                'Kedaluwarsa'  => (int) (DB::table('laboran_bahan_alat')->sum('jumlah_kedaluwarsa') ?: 0),
            ],
            'kategoriBahan' => self::groupedCount('laboran_bahan_alat', 'jenis', 'jenis', 'Lainnya'),
        ];
    }

    public static function perpustakaan(): array
    {
        $out = ['kategoriBuku' => [], 'pengunjungTingkat' => [], 'statusSirkulasi' => []];
        if (Table::exists('perpus_koleksi_buku')) {
            $out['kategoriBuku'] = self::groupedCount('perpus_koleksi_buku', 'kategori', 'kategori', 'Umum');
            $tersedia = (int) (DB::table('perpus_koleksi_buku')->sum('eksemplar_tersedia') ?: 0);
            $total = (int) (DB::table('perpus_koleksi_buku')->sum('jumlah_eksemplar') ?: 0);
            $out['statusSirkulasi'] = ['Tersedia di Rak' => $tersedia, 'Dipinjam' => max(0, $total - $tersedia)];
        }
        $out['pengunjungTingkat'] = self::groupedCount('perpus_kunjungan', 'rombel_atau_unit', 'unit', 'Lainnya');
        return $out;
    }

    public static function keamanan(): array
    {
        return [
            'kategoriTamu'    => self::groupedCount('keamanan_buku_tamu', 'keperluan', 'keperluan', 'Lainnya'),
            'zonaPatroli'     => self::groupedCount('keamanan_patroli', 'rute_zona', 'zona', 'Lainnya'),
            'kondisiKeamanan' => self::groupedCount('keamanan_patroli', 'kondisi_lingkungan', 'kondisi', 'lainnya'),
        ];
    }

    public static function penjaga(): array
    {
        return [
            'areaKebersihan'     => self::groupedCount('kebersihan_checklist', 'area_zona', 'area', 'Lainnya'),
            'statusKontrolMalam' => self::groupedCount('penjaga_malam_log', 'status_pintu_jendela', 'status', 'lainnya'),
            'kondisiFasilitas'   => self::groupedCount('kebersihan_checklist', 'kondisi_kebersihan', 'kondisi', 'lainnya'),
        ];
    }

    public static function piket(): array
    {
        $out = [
            'alasanIzin'        => self::groupedCount('presensi_izin', 'alasan', 'alasan', 'Lainnya'),
            'kategoriTerlambat' => self::groupedCount('presensi_izin', 'jenis', 'jenis', 'lainnya'),
            'keterisianJurnal'  => [],
        ];
        if (Table::exists('agenda_kbm')) {
            $out['keterisianJurnal'] = [
                'Jurnal Terisi'    => (int) DB::table('agenda_kbm')->whereDate('tanggal', date('Y-m-d'))->count(),
                'Belum Dilaporkan' => 0,
            ];
        }
        return $out;
    }

    public static function teknisi(): array
    {
        return [
            'kategoriPerbaikan' => self::groupedCount('teknisi_work_order', 'kategori_perbaikan', 'kategori', 'lainnya'),
            'statusWO'          => self::groupedCount('teknisi_work_order', 'status', 'status', 'lainnya', 50),
            'sebaranPerangkat'  => self::groupedCount('teknisi_work_order', 'lokasi_unit', 'lokasi', 'Lainnya'),
        ];
    }

    public static function kepalaTas(): array
    {
        $out = [
            'distribusiTendik' => [],
            'statusKinerja'    => [],
            'komposisiGtk'     => [
                'Guru Pendidik' => Table::exists('gtk') ? DB::table('gtk')->where('jenis_ptk_id_str', 'LIKE', '%Guru%')->count() : 0,
                'Tenaga Tendik' => Table::exists('gtk') ? DB::table('gtk')->where('jenis_ptk_id_str', 'NOT LIKE', '%Guru%')->count() : 0,
            ],
        ];

        if (Table::exists('ptk_tugas_tambahan') && Table::exists('ref_tugas_tambahan')) {
            $out['distribusiTendik'] = DB::table('ptk_tugas_tambahan as ptt')
                ->leftJoin('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('ptt.is_active', true)
                ->selectRaw('COALESCE(NULLIF(rtt.nama, ""), "Lainnya") as tugas, COUNT(DISTINCT ptt.ptk_id) as total')
                ->groupBy('tugas')
                ->orderByDesc('total')
                ->limit(10)
                ->pluck('total', 'tugas')
                ->map(fn ($v) => (int) $v)
                ->toArray();
        }

        if (Table::exists('tendik_indikator_kinerja')) {
            $pct = TendikIndikatorKinerja::where('is_active', true)->get()->mapWithKeys(function ($ind) {
                $realisasi = Table::exists('tendik_aktivitas')
                    ? DB::table('tendik_aktivitas')->where('indikator_id', $ind->id)->where('status', 'selesai')->count()
                    : 0;
                return [$ind->id => (int) round(($realisasi / max(1, (int) $ind->target_kuantitas)) * 100)];
            });

            $out['statusKinerja'] = [
                'Tercapai / Lebih'    => $pct->filter(fn ($p) => $p >= 100)->count(),
                'Sedang Berjalan'     => $pct->filter(fn ($p) => $p > 0 && $p < 100)->count(),
                'Belum Ada Realisasi' => $pct->filter(fn ($p) => $p === 0)->count(),
            ];
        }

        return $out;
    }

    /** @return array{0:\Illuminate\Support\Collection,1:array,2:array} [indikatorKepegawaian, multiPeriode, indikatorPerBidang] */
    public static function indikator(int $totalTendik): array
    {
        $kepegawaianIndikatorList = collect();
        $bidangMultiPeriode = [];
        $bidangIndikatorList = [];
        $domainKeys = ['kesiswaan', 'persuratan', 'sarpras', 'laboran', 'perpustakaan', 'teknisi', 'keamanan', 'penjaga', 'piket', 'kepala-tas'];

        if (!Table::exists('tendik_indikator_kinerja')) {
            foreach ($domainKeys as $dKey) {
                $bidangMultiPeriode[$dKey] = KepegawaianStatsService::buildMultiPeriodeData($totalTendik ?: 0, $dKey === 'kepala-tas' ? 'kepala_tas' : $dKey);
                $bidangIndikatorList[$dKey] = collect();
            }
            return [$kepegawaianIndikatorList, $bidangMultiPeriode, $bidangIndikatorList];
        }

        foreach ($domainKeys as $dKey) {
            $dbBidang = $dKey === 'kepala-tas' ? 'kepala_tas' : $dKey;
            $bidangMultiPeriode[$dKey] = KepegawaianStatsService::buildMultiPeriodeData($totalTendik ?: 0, $dbBidang);
            $bidangIndikatorList[$dKey] = TendikIndikatorKinerja::where('bidang', $dbBidang)
                ->where('is_active', true)
                ->orderBy('urutan')
                ->get()
                ->map(fn ($ind) => self::hitungIndikator($ind, $dbBidang));
        }

        $kepegawaianIndikatorList = TendikIndikatorKinerja::where('bidang', 'kepegawaian')
            ->where('is_active', true)
            ->orderBy('urutan')
            ->get()
            ->map(fn ($ind) => self::hitungIndikator($ind, 'kepegawaian'));

        return [$kepegawaianIndikatorList, $bidangMultiPeriode, $bidangIndikatorList];
    }

    private static function hitungIndikator($ind, string $bidang)
    {
        $realisasi = 0;
        if (Table::exists('tendik_aktivitas')) {
            $realisasi = DB::table('tendik_aktivitas')
                ->where('bidang', $bidang)
                ->where(function ($q) use ($ind) {
                    $q->where('indikator_id', $ind->id)
                        ->orWhere('judul_aktivitas', 'like', '%' . substr($ind->sasaran, 0, 16) . '%')
                        ->orWhere('uraian_pekerjaan', 'like', '%' . substr($ind->sasaran, 0, 16) . '%');
                })
                ->where('status', 'selesai')
                ->count();
        }

        if ($realisasi === 0 && $bidang === 'persuratan') {
            $s = strtolower($ind->sasaran . ' ' . $ind->indikator_kinerja);
            if (str_contains($s, 'surat masuk')) {
                $realisasi = Table::exists('persuratan') ? DB::table('persuratan')->where('jenis_surat', 'masuk')->count() : 0;
            } elseif (str_contains($s, 'surat keluar')) {
                $realisasi = Table::exists('persuratan') ? DB::table('persuratan')->where('jenis_surat', 'keluar')->count() : 0;
            } elseif (str_contains($s, 'kearsipan') || str_contains($s, 'sk dinas') || str_contains($s, 'surat tugas')) {
                $realisasi = (Table::exists('persuratan') ? DB::table('persuratan')->whereIn('jenis_surat', ['keputusan', 'tugas'])->count() : 0)
                    + (Table::exists('gtk_spt') ? DB::table('gtk_spt')->count() : 0);
            } elseif (str_contains($s, 'keterangan aktif') || str_contains($s, 'legalisir')) {
                $realisasi = Table::exists('surat_keterangan_pd') ? DB::table('surat_keterangan_pd')->count() : 0;
            }
        }

        $ind->realisasi_count = $realisasi;
        $ind->realisasi_label = $realisasi > 0 ? "{$realisasi} {$ind->satuan}" : "0 {$ind->satuan}";
        $ind->status_kpi = $realisasi >= $ind->target_kuantitas ? 'Tercapai' : ($realisasi > 0 ? 'Sedang Berjalan' : 'Dalam Proses');
        return $ind;
    }
}
