<?php

namespace App\Http\Controllers\Teknisi;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Indikator Tupoksi #2 — Kebersihan Lingkungan & Ruang Kerja.
 *
 * Bukti fisik: kebersihan ruang kepala sekolah, wakasek kurikulum, operator,
 * bendahara, ruang rapat, dapur & WC, teras/halaman, ruang TU, guru, wakasek
 * kesiswaan, humas/hubin, jamban, koperasi, lapangan upacara, gor, aula
 * balarea, sarana prasarana, dan jamban siswa.
 */
class TeknisiKebersihanRuangController extends TeknisiBaseController
{
    public const KATEGORI = [
        'administrasi'   => 'Ruang Administrasi (Kepsek, Wakasek, Operator, Bendahara)',
        'perkantoran'    => 'Ruang Perkantoran (TU, Guru, Humas/Hubin)',
        'fasilitas_umum' => 'Fasilitas Umum (Dapur, WC, Jamban, Koperasi)',
        'teras_halaman'  => 'Teras & Halaman',
        'lapangan'       => 'Lapangan Upacara, Gor, Aula/Balarea',
        'lainnya'        => 'Lainnya',
    ];

    public const KONDISI = [
        'sangat_bersih'  => 'Sangat Bersih',
        'bersih'         => 'Bersih',
        'cukup'          => 'Cukup Bersih',
        'kotor'          => 'Kotor',
        'perlu_tindakan' => 'Perlu Tindakan',
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

        $search        = trim((string) $request->query('q', ''));
        $kategoriFilter = trim((string) $request->query('kategori', ''));
        $kondisiFilter = trim((string) $request->query('kondisi', ''));
        $dari   = trim((string) $request->query('dari', ''));
        $sampai = trim((string) $request->query('sampai', ''));
        if ($dari === '' && $sampai === '') {
            $dari   = now()->startOfMonth()->toDateString();
            $sampai = now()->endOfMonth()->toDateString();
        }

        $query = DB::table('teknisi_kebersihan_ruang as k')->select('k.*');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('k.ruang_nama', 'like', "%{$search}%")
                  ->orWhere('k.uraian_kegiatan', 'like', "%{$search}%")
                  ->orWhere('k.temuan', 'like', "%{$search}%");
            });
        }
        if ($kategoriFilter !== '') $query->where('k.kategori_ruang', $kategoriFilter);
        if ($kondisiFilter !== '')  $query->where('k.kondisi', $kondisiFilter);
        if ($dari !== '')   $query->where('k.tanggal_kegiatan', '>=', $dari);
        if ($sampai !== '') $query->where('k.tanggal_kegiatan', '<=', $sampai);

        $statBase = DB::table('teknisi_kebersihan_ruang');
        if ($dari !== '')   $statBase->where('tanggal_kegiatan', '>=', $dari);
        if ($sampai !== '') $statBase->where('tanggal_kegiatan', '<=', $sampai);
        $statTotal      = (clone $statBase)->count();
        $statBersih     = (clone $statBase)->whereIn('kondisi', ['sangat_bersih', 'bersih'])->count();
        $statTindakan   = (clone $statBase)->whereIn('kondisi', ['kotor', 'perlu_tindakan'])->count();
        $statDokumentasi = (clone $statBase)->whereNotNull('dokumentasi_paths')->count();

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $list = $query->orderByDesc('k.tanggal_kegiatan')->orderByDesc('k.id')
            ->paginate($perPage)->withQueryString();

        return view('dashboard.teknisi.kebersihan-ruang', array_merge(
            compact('list', 'search', 'kategoriFilter', 'kondisiFilter', 'dari', 'sampai', 'perPage',
                'statTotal', 'statBersih', 'statTindakan', 'statDokumentasi'),
            $this->abilities(),
            [
                'kategoriList'  => self::KATEGORI,
                'kondisiList'   => self::KONDISI,
                'statusList'    => self::STATUS,
                'lokasiOptions' => $this->lokasiOptions(),
            ]
        ));
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $this->validated($request);

        DB::table('teknisi_kebersihan_ruang')->insert([
            'nomor_kegiatan'     => $this->generateNomor('teknisi_kebersihan_ruang', 'nomor_kegiatan', 'KBR'),
            'tanggal_kegiatan'   => $data['tanggal_kegiatan'],
            'ruang_nama'         => trim($data['ruang_nama']),
            'kategori_ruang'     => $data['kategori_ruang'] ?? 'perkantoran',
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

        return $this->respond($request, true, 'Catatan kebersihan ruang berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findRow('teknisi_kebersihan_ruang', $id);
        if (!$row) return $this->respond($request, false, 'Data tidak ditemukan.', 404);

        $data = $this->validated($request);

        DB::table('teknisi_kebersihan_ruang')->where('id', $id)->update([
            'tanggal_kegiatan'   => $data['tanggal_kegiatan'],
            'ruang_nama'         => trim($data['ruang_nama']),
            'kategori_ruang'     => $data['kategori_ruang'] ?? $row->kategori_ruang,
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

        return $this->respond($request, true, 'Catatan kebersihan ruang berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        DB::table('teknisi_kebersihan_ruang')->where('id', $id)->delete();

        return $this->respond($request, true, 'Catatan kebersihan ruang berhasil dihapus.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'tanggal_kegiatan'   => 'required|date',
            'ruang_nama'         => 'required|string|max:150',
            'kategori_ruang'     => 'nullable|in:administrasi,perkantoran,fasilitas_umum,teras_halaman,lapangan,lainnya',
            'kondisi'            => 'nullable|in:sangat_bersih,bersih,cukup,kotor,perlu_tindakan',
            'uraian_kegiatan'    => 'nullable|string|max:2000',
            'peralatan_digunakan' => 'nullable|string|max:1000',
            'temuan'             => 'nullable|string|max:1000',
            'tindak_lanjut'      => 'nullable|string|max:1000',
            'foto_sebelum'       => 'nullable|string|max:255',
            'foto_sesudah'       => 'nullable|string|max:255',
            'dokumentasi_paths'  => 'nullable|array',
            'dokumentasi_paths.*' => 'string|max:255',
            'status'             => 'nullable|in:draf,selesai,terverifikasi',
        ], [
            'tanggal_kegiatan.required' => 'Tanggal kegiatan wajib diisi.',
            'ruang_nama.required'       => 'Nama ruang/area wajib diisi.',
        ]);
    }
}
