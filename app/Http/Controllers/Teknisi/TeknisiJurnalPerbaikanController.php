<?php

namespace App\Http\Controllers\Teknisi;

use App\Models\Sekolah;
use App\Models\SekolahMeta;
use App\Models\TeknisiWorkOrder;
use App\Support\PeriodeLaporan;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Indikator Tupoksi #5 — Jurnal Perbaikan Sarana Prasarana.
 *
 * Bukan lagi entri manual: jurnal ini adalah REKAP BACA-SAJA dari Work Order
 * yang sudah berstatus `selesai`. Dengan begitu arsip perbaikan otomatis
 * sinkron dengan tiket yang benar-benar dikerjakan.
 */
class TeknisiJurnalPerbaikanController extends TeknisiBaseController
{
    public const JENIS = [
        'perbaikan'   => 'Perbaikan',
        'penggantian' => 'Penggantian',
        'pengecekan'  => 'Pengecekan',
        'kebutuhan'   => 'Kebutuhan Sarana',
    ];

    public const KATEGORI = [
        'listrik'      => 'Listrik',
        'air_plumbing' => 'Air & Plumbing',
        'bangunan'     => 'Bangunan',
        'furnitur'     => 'Furnitur',
        'jaringan_it'  => 'Jaringan & IT',
        'sanitasi'     => 'Sanitasi',
        'kendaraan'    => 'Kendaraan',
        'lainnya'      => 'Lainnya',
    ];

    /** Peta kategori Work Order -> kategori jurnal (label seragam). */
    public const KATEGORI_WO = [
        'bangunan_fisik'        => 'Bangunan',
        'kelistrikan'           => 'Listrik',
        'sanitasi_plumbing'     => 'Air & Plumbing',
        'kebersihan_lingkungan' => 'Kebersihan Lingkungan',
        'ac_pendingin'          => 'AC & Pendingin',
        'komputer'              => 'Jaringan & IT',
        'internet_jaringan'     => 'Jaringan & IT',
        'audio_bel'             => 'Audio & Bel',
    ];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $search   = trim((string) $request->query('q', ''));
        $kategori = trim((string) $request->query('kategori', ''));
        $dari     = trim((string) $request->query('dari', ''));
        $sampai   = trim((string) $request->query('sampai', ''));
        if ($dari === '' && $sampai === '') {
            $dari   = now()->startOfMonth()->toDateString();
            $sampai = now()->endOfMonth()->toDateString();
        }

        $query = TeknisiWorkOrder::query()->where('status', 'selesai');
        $this->scopeOwned($query->getQuery(), $context, null, 'teknisi_ptk_id');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('lokasi_unit', 'like', "%{$search}%")
                  ->orWhere('deskripsi_kerusakan', 'like', "%{$search}%")
                  ->orWhere('tindakan_perbaikan', 'like', "%{$search}%");
            });
        }
        if ($kategori !== '') {
            $map = array_keys(array_filter(self::KATEGORI_WO, fn ($v) => $v === $kategori));
            $query->whereIn('kategori_perbaikan', $map ?: [$kategori]);
        }
        $dateCol = 'tgl_selesai';
        if ($dari !== '')   $query->where($dateCol, '>=', $dari);
        if ($sampai !== '') $query->where($dateCol, '<=', $sampai);

        $statBase = TeknisiWorkOrder::query()->where('status', 'selesai');
        $this->scopeOwned($statBase->getQuery(), $context, null, 'teknisi_ptk_id');
        if ($dari !== '')   $statBase->where($dateCol, '>=', $dari);
        if ($sampai !== '') $statBase->where($dateCol, '<=', $sampai);
        $statTotal     = (clone $statBase)->count();
        $statKategori  = (clone $statBase)->distinct()->count('kategori_perbaikan');
        $statDarurat   = (clone $statBase)->where('tingkat_urgensi', 'darurat')->count();
        $statBulanIni  = TeknisiWorkOrder::query()->where('status', 'selesai')
            ->whereYear('tgl_selesai', now()->year)->whereMonth('tgl_selesai', now()->month)->count();

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 15;

        $list = $query->orderByDesc($dateCol)->orderByDesc('id')
            ->paginate($perPage)->withQueryString();

        return view('dashboard.teknisi.jurnal-perbaikan', array_merge(
            compact('list', 'search', 'kategori', 'dari', 'sampai', 'perPage',
                'statTotal', 'statKategori', 'statDarurat', 'statBulanIni'),
            $this->abilities(),
            [
                'jenisList'    => self::JENIS,
                'kategoriList' => self::KATEGORI,
                'kategoriWo'   => self::KATEGORI_WO,
            ]
        ));
    }

    /** Cetak rekap jurnal dari Work Order selesai. */
    public function cetak(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $dari   = trim((string) $request->query('dari', ''));
        $sampai = trim((string) $request->query('sampai', ''));

        if ($request->filled('periode')) {
            $range = PeriodeLaporan::resolve(
                (string) $request->query('periode'),
                (int) $request->query('bulan', date('n')),
                (int) $request->query('tahun', date('Y')),
                (int) $request->query('triwulan', (int) ceil(date('n') / 3)),
                (string) $request->query('semester', date('n') >= 7 ? '1' : '2'),
                (string) $request->query('tahun_ajaran', ''),
                $request->query('tanggal')
            );
            $dari   = $range['start'];
            $sampai = $range['end'];
        } else {
            $range = [
                'tipe'    => 'kustom',
                'start'   => $dari,
                'end'     => $sampai,
                'label'   => ($dari !== '' || $sampai !== '')
                    ? Carbon::parse($dari ?: '1900-01-01')->translatedFormat('d F Y') . ' s.d. ' . Carbon::parse($sampai ?: now()->toDateString())->translatedFormat('d F Y')
                    : 'Seluruh Periode',
                'singkat' => ($dari !== '' || $sampai !== '') ? $dari . ' - ' . $sampai : 'Seluruh Periode',
            ];
        }

        $query = TeknisiWorkOrder::query()->where('status', 'selesai');
        $this->scopeOwned($query->getQuery(), $context, null, 'teknisi_ptk_id');
        if ($dari !== '')   $query->where('tgl_selesai', '>=', $dari);
        if ($sampai !== '') $query->where('tgl_selesai', '<=', $sampai);

        $rows = $query->orderByDesc('tgl_selesai')->orderByDesc('id')->get();

        $fieldMap = [
            ['Tanggal Selesai', 'tgl_selesai'],
            ['Lokasi / Unit', 'lokasi_unit'],
            ['Kategori', 'kategori_perbaikan'],
            ['Deskripsi Kerusakan', 'deskripsi_kerusakan'],
            ['Tindakan Perbaikan', 'tindakan_perbaikan'],
            ['Biaya Sparepart', 'estimasi_biaya_part'],
            ['Pelapor', 'pelapor_nama'],
        ];
        $types = ['tgl_selesai' => 'date', 'estimasi_biaya_part' => 'decimal'];

        $items = $rows->map(function ($row) use ($fieldMap) {
            $fields = [];
            foreach ($fieldMap as [$label, $key]) {
                $val = $row->{$key} ?? null;
                if ($key === 'tgl_selesai' && $val) {
                    $val = Carbon::parse($val)->translatedFormat('d F Y');
                } elseif ($key === 'kategori_perbaikan') {
                    $val = self::KATEGORI_WO[$val] ?? $val;
                } elseif ($key === 'estimasi_biaya_part') {
                    $val = $val === null || $val === '' ? '—' : 'Rp ' . number_format((float) $val, 0, ',', '.');
                }
                $fields[] = ['label' => $label, 'value' => ($val === null || $val === '') ? '—' : (string) $val];
            }

            return (object) ['nomor' => $row->nomor_wo, 'fields' => $fields];
        });

        $dokumen = app(\App\Services\LaboranDokumenService::class);

        return view('dashboard.teknisi.laporan-cetak', [
            'modul'       => 'jurnal-perbaikan',
            'title'       => 'REKAP JURNAL PERBAIKAN SARANA PRASARANA',
            'items'       => $items,
            'rows'        => $rows,
            'types'       => $types,
            'fieldMap'    => $fieldMap,
            'range'       => $range,
            'mode'        => 'lengkap',
            'orientasi'   => 'landscape',
            'cetakUrl'    => fn (array $p = []) => route('dashboard.teknisi.jurnal-perbaikan.cetak', $p),
            'teknisiTtd'  => [
                'petugas'       => $dokumen->petugasTendik($context),
                'kepalaTas'     => $dokumen->jabatan('KEPALA_TAS'),
                'kepalaSekolah' => $dokumen->kepalaSekolah(),
            ],
            'sekolah'     => Sekolah::first(),
            'sekolahMeta' => SekolahMeta::first(),
            'dicetakPada' => now(),
        ]);
    }
}
