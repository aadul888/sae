<?php

namespace App\Http\Controllers\Teknisi;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Indikator Tupoksi #3 — Kebersihan Lingkungan Ruang Rapat.
 *
 * Bukti fisik: kebersihan teras, halaman, dan ruang rapat (sebelum, saat,
 * dan sesudah kegiatan rapat).
 */
class TeknisiKebersihanRapatController extends TeknisiBaseController
{
    public const AREA = [
        'teras'       => 'Teras',
        'halaman'     => 'Halaman',
        'ruang_rapat' => 'Ruang Rapat',
    ];

    public const TAHAP = [
        'sebelum_acara' => 'Sebelum Acara',
        'saat_acara'    => 'Saat Acara',
        'sesudah_acara' => 'Sesudah Acara',
    ];

    public const KONDISI = [
        'sangat_bersih'  => 'Sangat Bersih',
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

        $search        = trim((string) $request->query('q', ''));
        $areaFilter    = trim((string) $request->query('area', ''));
        $tahapFilter   = trim((string) $request->query('tahap', ''));
        $dari   = trim((string) $request->query('dari', ''));
        $sampai = trim((string) $request->query('sampai', ''));
        if ($dari === '' && $sampai === '') {
            $dari   = now()->startOfMonth()->toDateString();
            $sampai = now()->endOfMonth()->toDateString();
        }

        $query = DB::table('teknisi_kebersihan_rapat as k')->select('k.*');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('k.lokasi', 'like', "%{$search}%")
                  ->orWhere('k.nama_rapat', 'like', "%{$search}%")
                  ->orWhere('k.uraian_kegiatan', 'like', "%{$search}%");
            });
        }
        if ($areaFilter !== '')  $query->where('k.area', $areaFilter);
        if ($tahapFilter !== '') $query->where('k.tahap', $tahapFilter);
        if ($dari !== '')   $query->where('k.tanggal_kegiatan', '>=', $dari);
        if ($sampai !== '') $query->where('k.tanggal_kegiatan', '<=', $sampai);

        $statBase = DB::table('teknisi_kebersihan_rapat');
        if ($dari !== '')   $statBase->where('tanggal_kegiatan', '>=', $dari);
        if ($sampai !== '') $statBase->where('tanggal_kegiatan', '<=', $sampai);
        $statTotal    = (clone $statBase)->count();
        $statBersih   = (clone $statBase)->whereIn('kondisi', ['sangat_bersih', 'bersih'])->count();
        $statTindakan = (clone $statBase)->whereIn('kondisi', ['kotor', 'perlu_tindakan'])->count();
        $statRapat    = (clone $statBase)->where('area', 'ruang_rapat')->count();

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $list = $query->orderByDesc('k.tanggal_kegiatan')->orderByDesc('k.id')
            ->paginate($perPage)->withQueryString();

        return view('dashboard.teknisi.kebersihan-rapat', array_merge(
            compact('list', 'search', 'areaFilter', 'tahapFilter', 'dari', 'sampai', 'perPage',
                'statTotal', 'statBersih', 'statTindakan', 'statRapat'),
            $this->abilities(),
            [
                'areaList'    => self::AREA,
                'tahapList'   => self::TAHAP,
                'kondisiList' => self::KONDISI,
                'statusList'  => self::STATUS,
            ]
        ));
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $this->validated($request);

        DB::table('teknisi_kebersihan_rapat')->insert([
            'nomor_kegiatan'     => $this->generateNomor('teknisi_kebersihan_rapat', 'nomor_kegiatan', 'KRP'),
            'tanggal_kegiatan'   => $data['tanggal_kegiatan'],
            'lokasi'             => $data['lokasi'] ?? 'Ruang Rapat',
            'nama_rapat'         => $data['nama_rapat'] ?? null,
            'area'               => $data['area'] ?? 'ruang_rapat',
            'tahap'              => $data['tahap'] ?? 'sebelum_acara',
            'kondisi'            => $data['kondisi'] ?? 'bersih',
            'uraian_kegiatan'    => $data['uraian_kegiatan'] ?? null,
            'peralatan_digunakan' => $data['peralatan_digunakan'] ?? null,
            'temuan'             => $data['temuan'] ?? null,
            'tindak_lanjut'      => $data['tindak_lanjut'] ?? null,
            'foto_sebelum'       => $data['foto_sebelum'] ?? null,
            'foto_sesudah'       => $data['foto_sesudah'] ?? null,
            'dokumentasi_paths'  => !empty($data['dokumentasi_paths'])
                ? json_encode(array_values($data['dokumentasi_paths'])) : null,
            'petugas_ptk_id'     => $context['ptk_id'],
            'petugas_nama'       => $context['nama'],
            'status'             => $data['status'] ?? 'selesai',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        return $this->respond($request, true, 'Catatan kebersihan ruang rapat berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findRow('teknisi_kebersihan_rapat', $id);
        if (!$row) return $this->respond($request, false, 'Data tidak ditemukan.', 404);

        $data = $this->validated($request);

        DB::table('teknisi_kebersihan_rapat')->where('id', $id)->update([
            'tanggal_kegiatan'   => $data['tanggal_kegiatan'],
            'lokasi'             => $data['lokasi'] ?? $row->lokasi,
            'nama_rapat'         => $data['nama_rapat'] ?? null,
            'area'               => $data['area'] ?? $row->area,
            'tahap'              => $data['tahap'] ?? $row->tahap,
            'kondisi'            => $data['kondisi'] ?? $row->kondisi,
            'uraian_kegiatan'    => $data['uraian_kegiatan'] ?? null,
            'peralatan_digunakan' => $data['peralatan_digunakan'] ?? null,
            'temuan'             => $data['temuan'] ?? null,
            'tindak_lanjut'      => $data['tindak_lanjut'] ?? null,
            'foto_sebelum'       => $data['foto_sebelum'] ?? $row->foto_sebelum,
            'foto_sesudah'       => $data['foto_sesudah'] ?? $row->foto_sesudah,
            'dokumentasi_paths'  => !empty($data['dokumentasi_paths'])
                ? json_encode(array_values($data['dokumentasi_paths'])) : $row->dokumentasi_paths,
            'status'             => $data['status'] ?? $row->status,
            'updated_at'         => now(),
        ]);

        return $this->respond($request, true, 'Catatan kebersihan ruang rapat berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        DB::table('teknisi_kebersihan_rapat')->where('id', $id)->delete();

        return $this->respond($request, true, 'Catatan kebersihan ruang rapat berhasil dihapus.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'tanggal_kegiatan'   => 'required|date',
            'lokasi'             => 'nullable|string|max:150',
            'nama_rapat'         => 'nullable|string|max:200',
            'area'               => 'nullable|in:teras,halaman,ruang_rapat',
            'tahap'              => 'nullable|in:sebelum_acara,saat_acara,sesudah_acara',
            'kondisi'            => 'nullable|in:sangat_bersih,bersih,cukup,kotor,perlu_tindakan',
            'uraian_kegiatan'    => 'nullable|string|max:2000',
            'peralatan_digunakan' => 'nullable|string|max:1000',
            'temuan'             => 'nullable|string|max:1000',
            'tindak_lanjut'      => 'nullable|string|max:1000',
            'foto_sebelum'       => 'nullable|string|max:255',
            'foto_sesudah'       => 'nullable|string|max:255',
            'dokumentasi_paths'  => 'nullable|array',
            'dokumentasi_paths.*' => 'string|max:255',
            'status'             => 'nullable|in:draf,selesai',
        ], [
            'tanggal_kegiatan.required' => 'Tanggal kegiatan wajib diisi.',
        ]);
    }
}
