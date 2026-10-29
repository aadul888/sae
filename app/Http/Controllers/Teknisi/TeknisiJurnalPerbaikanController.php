<?php

namespace App\Http\Controllers\Teknisi;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Indikator Tupoksi #5 — Pengarsipan & Laporan Perbaikan Sarana Prasarana.
 *
 * Bukti fisik: jurnal kegiatan perbaikan dan kebutuhan sarana prasarana.
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
        'listrik'    => 'Listrik',
        'air_plumbing' => 'Air & Plumbing',
        'bangunan'   => 'Bangunan',
        'furnitur'   => 'Furnitur',
        'jaringan_it' => 'Jaringan & IT',
        'sanitasi'   => 'Sanitasi',
        'kendaraan'  => 'Kendaraan',
        'lainnya'    => 'Lainnya',
    ];

    public const STATUS = [
        'draf'          => 'Draf',
        'selesai'       => 'Selesai',
        'terverifikasi' => 'Terverifikasi',
    ];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $search       = trim((string) $request->query('q', ''));
        $jenisFilter  = trim((string) $request->query('jenis', ''));
        $kategoriFilter = trim((string) $request->query('kategori', ''));
        $dari   = trim((string) $request->query('dari', ''));
        $sampai = trim((string) $request->query('sampai', ''));
        if ($dari === '' && $sampai === '') {
            $dari   = now()->startOfMonth()->toDateString();
            $sampai = now()->endOfMonth()->toDateString();
        }

        $query = DB::table('teknisi_jurnal_perbaikan as j')->select('j.*');
        $this->scopeRuangPj($query, $context, 'ruang_id', 'j', true);
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('j.lokasi_unit', 'like', "%{$search}%")
                  ->orWhere('j.uraian_kegiatan', 'like', "%{$search}%")
                  ->orWhere('j.kebutuhan_sarana', 'like', "%{$search}%");
            });
        }
        if ($jenisFilter !== '')    $query->where('j.jenis_pekerjaan', $jenisFilter);
        if ($kategoriFilter !== '') $query->where('j.kategori_sarana', $kategoriFilter);
        if ($dari !== '')   $query->where('j.tanggal', '>=', $dari);
        if ($sampai !== '') $query->where('j.tanggal', '<=', $sampai);

        $statBase = DB::table('teknisi_jurnal_perbaikan');
        $this->scopeRuangPj($statBase, $context, 'ruang_id', null, true);
        if ($dari !== '')   $statBase->where('tanggal', '>=', $dari);
        if ($sampai !== '') $statBase->where('tanggal', '<=', $sampai);
        $statTotal     = (clone $statBase)->count();
        $statPerbaikan = (clone $statBase)->where('jenis_pekerjaan', 'perbaikan')->count();
        $statKebutuhan = (clone $statBase)->where('jenis_pekerjaan', 'kebutuhan')->count();
        $statSelesai   = (clone $statBase)->whereIn('status', ['selesai', 'terverifikasi'])->count();

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $list = $query->orderByDesc('j.tanggal')->orderByDesc('j.id')
            ->paginate($perPage)->withQueryString();

        return view('dashboard.teknisi.jurnal-perbaikan', array_merge(
            compact('list', 'search', 'jenisFilter', 'kategoriFilter', 'dari', 'sampai', 'perPage',
                'statTotal', 'statPerbaikan', 'statKebutuhan', 'statSelesai'),
            $this->abilities(),
            [
                'jenisList'     => self::JENIS,
                'kategoriList'  => self::KATEGORI,
                'statusList'    => self::STATUS,
                'lokasiOptions' => $this->lokasiOptions($context),
                'sources'       => \App\Support\TeknisiModuleRegistry::sources(),
            ]
        ));
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $this->validated($request);
        $ruangId = $data['ruang_id'] ?? $this->ruangIdDariNama($data['lokasi_unit'] ?? null);
        $pj = $this->pjUntukRuang($ruangId ? (int) $ruangId : null, $context);

        DB::table('teknisi_jurnal_perbaikan')->insert([
            'nomor_jurnal'      => $this->generateNomor('teknisi_jurnal_perbaikan', 'nomor_jurnal', 'JP'),
            'tanggal'           => $data['tanggal'],
            'lokasi_unit'       => trim($data['lokasi_unit']),
            'aset_id'           => $data['aset_id'] ?? null,
            'ruang_id'          => $ruangId,
            'jenis_pekerjaan'   => $data['jenis_pekerjaan'],
            'kategori_sarana'   => $data['kategori_sarana'] ?? null,
            'uraian_kegiatan'   => trim($data['uraian_kegiatan']),
            'hasil'             => $data['hasil'] ?? null,
            'kebutuhan_sarana'  => $data['kebutuhan_sarana'] ?? null,
            'kendala'           => $data['kendala'] ?? null,
            'tindak_lanjut'     => $data['tindak_lanjut'] ?? null,
            'estimasi_biaya'    => $data['estimasi_biaya'] ?? 0,
            'realisasi_biaya'   => $data['realisasi_biaya'] ?? null,
            'foto_sebelum'      => $data['foto_sebelum'] ?? null,
            'foto_sesudah'      => $data['foto_sesudah'] ?? null,
            'dokumentasi_paths' => !empty($data['dokumentasi_paths'])
                ? json_encode(array_values($data['dokumentasi_paths'])) : null,
            'petugas_ptk_id'    => $pj['ptk_id'],
            'petugas_nama'      => $pj['nama'],
            'user_id'           => $context['user_id'],
            'status'            => $data['status'] ?? 'selesai',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return $this->respond($request, true, 'Jurnal perbaikan berhasil dicatat.');
    }

    public function update(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findRow('teknisi_jurnal_perbaikan', $id);
        if (!$row) return $this->respond($request, false, 'Data tidak ditemukan.', 404);
        if (!$this->ownsRowRuang($row, $context)) {
            return $this->respond($request, false, 'Anda hanya dapat mengubah data pada ruang yang menjadi tanggung jawab Anda.', 403);
        }

        $data = $this->validated($request);
        $ruangId = $data['ruang_id'] ?? $this->ruangIdDariNama($data['lokasi_unit'] ?? null);

        DB::table('teknisi_jurnal_perbaikan')->where('id', $id)->update([
            'tanggal'           => $data['tanggal'],
            'lokasi_unit'       => trim($data['lokasi_unit']),
            'aset_id'           => $data['aset_id'] ?? null,
            'ruang_id'          => $ruangId,
            'jenis_pekerjaan'   => $data['jenis_pekerjaan'],
            'kategori_sarana'   => $data['kategori_sarana'] ?? null,
            'uraian_kegiatan'   => trim($data['uraian_kegiatan']),
            'hasil'             => $data['hasil'] ?? null,
            'kebutuhan_sarana'  => $data['kebutuhan_sarana'] ?? null,
            'kendala'           => $data['kendala'] ?? null,
            'tindak_lanjut'     => $data['tindak_lanjut'] ?? null,
            'estimasi_biaya'    => $data['estimasi_biaya'] ?? 0,
            'realisasi_biaya'   => $data['realisasi_biaya'] ?? null,
            'foto_sebelum'      => $data['foto_sebelum'] ?? $row->foto_sebelum,
            'foto_sesudah'      => $data['foto_sesudah'] ?? $row->foto_sesudah,
            'dokumentasi_paths' => !empty($data['dokumentasi_paths'])
                ? json_encode(array_values($data['dokumentasi_paths'])) : $row->dokumentasi_paths,
            'status'            => $data['status'] ?? $row->status,
            'updated_at'        => now(),
        ]);

        return $this->respond($request, true, 'Jurnal perbaikan berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findRow('teknisi_jurnal_perbaikan', $id);
        if (!$row) return $this->respond($request, false, 'Data tidak ditemukan.', 404);
        if (!$this->ownsRowRuang($row, $context)) {
            return $this->respond($request, false, 'Anda hanya dapat menghapus data pada ruang yang menjadi tanggung jawab Anda.', 403);
        }

        DB::table('teknisi_jurnal_perbaikan')->where('id', $id)->delete();

        return $this->respond($request, true, 'Jurnal perbaikan berhasil dihapus.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'tanggal'           => 'required|date',
            'lokasi_unit'       => 'required|string|max:150',
            'aset_id'           => 'nullable|integer',
            'ruang_id'          => 'nullable|integer',
            'jenis_pekerjaan'   => 'required|in:perbaikan,penggantian,pengecekan,kebutuhan',
            'kategori_sarana'   => 'nullable|in:listrik,air_plumbing,bangunan,furnitur,jaringan_it,sanitasi,kendaraan,lainnya',
            'uraian_kegiatan'   => 'required|string|max:2000',
            'hasil'             => 'nullable|string|max:1000',
            'kebutuhan_sarana'  => 'nullable|string|max:1000',
            'kendala'           => 'nullable|string|max:1000',
            'tindak_lanjut'     => 'nullable|string|max:1000',
            'estimasi_biaya'    => 'nullable|numeric|min:0',
            'realisasi_biaya'   => 'nullable|numeric|min:0',
            'foto_sebelum'      => 'nullable|string|max:255',
            'foto_sesudah'      => 'nullable|string|max:255',
            'dokumentasi_paths' => 'nullable|array',
            'dokumentasi_paths.*' => 'string|max:255',
            'status'            => 'nullable|in:draf,selesai,terverifikasi',
        ], [
            'tanggal.required'         => 'Tanggal wajib diisi.',
            'lokasi_unit.required'     => 'Lokasi/unit wajib diisi.',
            'jenis_pekerjaan.required' => 'Jenis pekerjaan wajib dipilih.',
            'uraian_kegiatan.required' => 'Uraian kegiatan wajib diisi.',
        ]);
    }
}
