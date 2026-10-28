<?php

namespace App\Http\Controllers\Laboran;

use App\Models\Sekolah;
use App\Models\SekolahMeta;
use App\Services\LaboranKebersihanImportService;
use App\Support\LaboranScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Modul KEBERSIHAN LINGKUNGAN PRODUKTIF JURUSAN.
 *
 * Indikator Tupoksi #9 — bukti fisik: Dokumentasi Kegiatan.
 * Mencatat kegiatan kebersihan area praktik beserta dokumentasi foto.
 */
class LaboranKebersihanController extends LaboranBaseController
{
    public const JENIS = [
        'harian'     => 'Harian',
        'mingguan'   => 'Mingguan',
        'bulanan'    => 'Bulanan',
        'insidental' => 'Insidental',
    ];

    public const KONDISI = [
        'bersih'         => 'Bersih',
        'cukup'          => 'Cukup Bersih',
        'kotor'          => 'Kotor',
        'perlu_tindakan' => 'Perlu Tindakan',
    ];

    public const STATUS = [
        'draf'    => 'Draf',
        'selesai' => 'Selesai',
    ];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $search       = trim((string) $request->query('q', ''));
        $jenisFilter  = trim((string) $request->query('jenis', ''));
        $kondisiFilter = trim((string) $request->query('kondisi', ''));
        $dari         = trim((string) $request->query('dari', ''));
        $sampai       = trim((string) $request->query('sampai', ''));

        if ($dari === '' && $sampai === '') {
            $dari   = now()->startOfMonth()->toDateString();
            $sampai = now()->endOfMonth()->toDateString();
        }

        $query = DB::table('laboran_kebersihan as k')
            ->leftJoin('jurusan_meta as jm', 'k.jurusan_id', '=', 'jm.jurusan_id')
            ->select('k.*', 'jm.nama_jurusan', 'jm.singkatan');

        LaboranScope::apply($query, 'k.jurusan_id', $context);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('k.area', 'like', "%{$search}%")
                  ->orWhere('k.uraian_kegiatan', 'like', "%{$search}%")
                  ->orWhere('k.nama_petugas', 'like', "%{$search}%")
                  ->orWhere('k.temuan', 'like', "%{$search}%");
            });
        }
        if ($jenisFilter !== '')   $query->where('k.jenis_kegiatan', $jenisFilter);
        if ($kondisiFilter !== '') $query->where('k.kondisi_akhir', $kondisiFilter);
        if ($dari !== '')   $query->where('k.tanggal_kegiatan', '>=', $dari);
        if ($sampai !== '') $query->where('k.tanggal_kegiatan', '<=', $sampai);

        $statBase = DB::table('laboran_kebersihan');
        LaboranScope::apply($statBase, 'jurusan_id', $context);
        if ($dari !== '')   $statBase->where('tanggal_kegiatan', '>=', $dari);
        if ($sampai !== '') $statBase->where('tanggal_kegiatan', '<=', $sampai);

        $statTotal      = (clone $statBase)->count();
        $statBersih     = (clone $statBase)->where('kondisi_akhir', 'bersih')->count();
        $statTindakan   = (clone $statBase)->whereIn('kondisi_akhir', ['kotor', 'perlu_tindakan'])->count();
        $statDokumentasi = (clone $statBase)->whereNotNull('dokumentasi_paths')->count();

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $list = $query->orderByDesc('k.tanggal_kegiatan')
            ->orderByDesc('k.id')
            ->paginate($perPage)
            ->withQueryString();

        return view('dashboard.laboran.kebersihan', array_merge(
            compact('list', 'search', 'jenisFilter', 'kondisiFilter', 'dari', 'sampai', 'perPage',
                'statTotal', 'statBersih', 'statTindakan', 'statDokumentasi'),
            $this->abilities(),
            [
                'context'        => $context,
                'scopeBadge'     => LaboranScope::badge($context),
                'lokasiOptions'  => $this->lokasiOptions(),
                'jenisList'      => self::JENIS,
                'kondisiList'    => self::KONDISI,
                'statusList'     => self::STATUS,
                'jurusanOptions' => LaboranScope::jurusanOptions($context),
            ]
        ));
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $this->validated($request);

        DB::table('laboran_kebersihan')->insert([
            'jurusan_id'         => LaboranScope::jurusanForWrite($data['jurusan_id'] ?? null, $context),
            'nomor_kegiatan'     => $this->generateNomor('laboran_kebersihan', 'nomor_kegiatan', 'KBR'),
            'tanggal_kegiatan'   => $data['tanggal_kegiatan'],
            'area'               => trim($data['area']),
            'ruang_lab_nama'     => $data['ruang_lab_nama'] ?? null,
            'jenis_kegiatan'     => $data['jenis_kegiatan'] ?? 'harian',
            'uraian_kegiatan'    => $data['uraian_kegiatan'] ?? null,
            'peralatan_digunakan' => $data['peralatan_digunakan'] ?? null,
            'bahan_digunakan'    => $data['bahan_digunakan'] ?? null,
            'jumlah_petugas'     => $data['jumlah_petugas'] ?? 1,
            'nama_petugas'       => $data['nama_petugas'] ?? null,
            'kondisi_akhir'      => $data['kondisi_akhir'] ?? 'bersih',
            'temuan'             => $data['temuan'] ?? null,
            'tindak_lanjut'      => $data['tindak_lanjut'] ?? null,
            'foto_sebelum'       => $data['foto_sebelum'] ?? null,
            'foto_sesudah'       => $data['foto_sesudah'] ?? null,
            'dokumentasi_paths'  => !empty($data['dokumentasi_paths'])
                ? json_encode(array_values($data['dokumentasi_paths']))
                : null,
            'petugas_ptk_id'     => $context['ptk_id'],
            'petugas_nama'       => $context['nama'],
            'status'             => $data['status'] ?? 'selesai',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        return $this->respond($request, true, 'Catatan kegiatan kebersihan berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_kebersihan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Data tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $data = $this->validated($request);

        DB::table('laboran_kebersihan')->where('id', $id)->update([
            'tanggal_kegiatan'    => $data['tanggal_kegiatan'],
            'area'                => trim($data['area']),
            'ruang_lab_nama'      => $data['ruang_lab_nama'] ?? null,
            'jenis_kegiatan'      => $data['jenis_kegiatan'] ?? $row->jenis_kegiatan,
            'uraian_kegiatan'     => $data['uraian_kegiatan'] ?? null,
            'peralatan_digunakan' => $data['peralatan_digunakan'] ?? null,
            'bahan_digunakan'     => $data['bahan_digunakan'] ?? null,
            'jumlah_petugas'      => $data['jumlah_petugas'] ?? 1,
            'nama_petugas'        => $data['nama_petugas'] ?? null,
            'kondisi_akhir'       => $data['kondisi_akhir'] ?? $row->kondisi_akhir,
            'temuan'              => $data['temuan'] ?? null,
            'tindak_lanjut'       => $data['tindak_lanjut'] ?? null,
            'foto_sebelum'        => $data['foto_sebelum'] ?? $row->foto_sebelum,
            'foto_sesudah'        => $data['foto_sesudah'] ?? $row->foto_sesudah,
            'dokumentasi_paths'   => !empty($data['dokumentasi_paths'])
                ? json_encode(array_values($data['dokumentasi_paths']))
                : $row->dokumentasi_paths,
            'status'              => $data['status'] ?? $row->status,
            'updated_at'          => now(),
        ]);

        return $this->respond($request, true, 'Catatan kebersihan berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_kebersihan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Data tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        DB::table('laboran_kebersihan')->where('id', $id)->delete();

        return $this->respond($request, true, 'Catatan kebersihan berhasil dihapus.');
    }

    /**
     * Cetak dokumentasi kegiatan kebersihan (bukti fisik supervisi).
     */
    public function cetak(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $dari   = (string) $request->query('dari', now()->startOfMonth()->toDateString());
        $sampai = (string) $request->query('sampai', now()->endOfMonth()->toDateString());

        $query = DB::table('laboran_kebersihan')->whereBetween('tanggal_kegiatan', [$dari, $sampai]);
        LaboranScope::apply($query, 'jurusan_id', $context);
        $items = $query->orderBy('tanggal_kegiatan')->get();

        $jurusan = null;
        if ($context['jurusan_id']) {
            $jurusan = DB::table('jurusan_meta')->where('jurusan_id', $context['jurusan_id'])->first();
        }

        $sekolah     = Sekolah::first();
        $sekolahMeta = SekolahMeta::first();

        $kepalaSekolah = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        $kepalaLab = DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->join('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
            ->where('rtt.kode', 'KEPALA_LAB')
            ->where('ptt.is_active', true)
            ->select('gtk.nama', 'gtk.nip', 'gtk.nuptk')
            ->first();

        $petugas = [
            'nama' => $context['nama'] ?: 'Petugas Laboran',
            'nip'  => DB::table('gtk')->where('ptk_id', $context['ptk_id'])->value('nip') ?: '—',
        ];

        $orientasi = strtolower((string) $request->query('orientasi', 'portrait'));
        if (!in_array($orientasi, ['portrait', 'landscape'], true)) {
            $orientasi = 'portrait';
        }

        $range = [
            'start' => $dari,
            'end'   => $sampai,
            'label' => Carbon::parse($dari)->translatedFormat('d F Y') . ' s.d. ' . Carbon::parse($sampai)->translatedFormat('d F Y'),
        ];

        return view('dashboard.laboran.kebersihan-cetak', compact(
            'items', 'jurusan', 'sekolah', 'sekolahMeta', 'kepalaSekolah',
            'kepalaLab', 'petugas', 'orientasi', 'range'
        ) + [
            'jenisList'   => self::JENIS,
            'kondisiList' => self::KONDISI,
        ]);
    }

    /**
     * Unduh format berkas Excel untuk import kegiatan kebersihan.
     */
    public function templateExcel(?LaboranKebersihanImportService $service = null)
    {
        $service = $service ?? app(LaboranKebersihanImportService::class);
        $path = $service->generateTemplateFile();

        return response()
            ->download($path, 'Format_Import_Kegiatan_Kebersihan.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * Import kegiatan kebersihan via Excel (.xlsx, .xls, .csv).
     */
    public function importExcel(Request $request, LaboranKebersihanImportService $service)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        try {
            $hasil = $service->import(
                $request->file('file'),
                $context,
                $request->input('jurusan_id')
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Import selesai: {$hasil['imported']} kegiatan baru ditambahkan, {$hasil['updated']} diperbarui.",
            'count'   => $hasil['imported'] + $hasil['updated'],
        ]);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'tanggal_kegiatan'    => 'required|date',
            'area'                => 'required|string|max:150',
            'ruang_lab_nama'      => 'nullable|string|max:100',
            'jenis_kegiatan'      => 'nullable|in:harian,mingguan,bulanan,insidental',
            'uraian_kegiatan'     => 'nullable|string|max:2000',
            'peralatan_digunakan' => 'nullable|string|max:1000',
            'bahan_digunakan'     => 'nullable|string|max:1000',
            'jumlah_petugas'      => 'nullable|integer|min:1|max:500',
            'nama_petugas'        => 'nullable|string|max:2000',
            'kondisi_akhir'       => 'nullable|in:bersih,cukup,kotor,perlu_tindakan',
            'temuan'              => 'nullable|string|max:1000',
            'tindak_lanjut'       => 'nullable|string|max:1000',
            'foto_sebelum'        => 'nullable|string|max:255',
            'foto_sesudah'        => 'nullable|string|max:255',
            'dokumentasi_paths'   => 'nullable|array',
            'dokumentasi_paths.*' => 'string|max:255',
            'status'              => 'nullable|in:draf,selesai',
            'jurusan_id'          => 'nullable|string|max:50',
        ], [
            'tanggal_kegiatan.required' => 'Tanggal kegiatan wajib diisi.',
            'area.required'             => 'Area/lokasi kebersihan wajib diisi.',
        ]);
    }
}
