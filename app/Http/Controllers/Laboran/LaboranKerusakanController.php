<?php

namespace App\Http\Controllers\Laboran;

use App\Models\Sekolah;
use App\Models\SekolahMeta;
use App\Support\LaboranScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Modul Pengelolaan Berita Acara Kerusakan Alat.
 *
 * Alur: lapor kerusakan -> berita acara -> verifikasi -> tindak lanjut
 * (perbaikan / usulan penggantian).
 */
class LaboranKerusakanController extends LaboranBaseController
{
    public const TINGKAT = [
        'ringan' => 'Rusak Ringan',
        'sedang' => 'Rusak Sedang',
        'berat'  => 'Rusak Berat',
        'total'  => 'Rusak Total',
    ];

    public const STATUS = [
        'draf'        => 'Draf',
        'diajukan'    => 'Diajukan',
        'diverifikasi' => 'Diverifikasi',
        'disetujui'   => 'Disetujui',
        'selesai'     => 'Selesai',
    ];

    public const PELAKU_TIPE = [
        'siswa'           => 'Peserta Didik',
        'guru'            => 'Guru',
        'tendik'          => 'Tenaga Kependidikan',
        'eksternal'       => 'Pihak Eksternal',
        'tidak_diketahui' => 'Tidak Diketahui',
    ];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $search        = trim((string) $request->query('q', ''));
        $tingkatFilter = trim((string) $request->query('tingkat', ''));
        $statusFilter  = trim((string) $request->query('status', ''));

        $query = DB::table('laboran_berita_acara_kerusakan as ba')
            ->leftJoin('jurusan_meta as jm', 'ba.jurusan_id', '=', 'jm.jurusan_id')
            ->select('ba.*', 'jm.nama_jurusan', 'jm.singkatan');

        LaboranScope::apply($query, 'ba.jurusan_id', $context);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ba.nomor_ba', 'like', "%{$search}%")
                  ->orWhere('ba.nama_alat', 'like', "%{$search}%")
                  ->orWhere('ba.kronologi', 'like', "%{$search}%")
                  ->orWhere('ba.pelaku_nama', 'like', "%{$search}%");
            });
        }
        if ($tingkatFilter !== '') $query->where('ba.tingkat_kerusakan', $tingkatFilter);
        if ($statusFilter !== '')  $query->where('ba.status', $statusFilter);

        $statBase = DB::table('laboran_berita_acara_kerusakan');
        LaboranScope::apply($statBase, 'jurusan_id', $context);

        $statTotal   = (clone $statBase)->count();
        $statDiajukan = (clone $statBase)->whereIn('status', ['diajukan', 'diverifikasi'])->count();
        $statBerat   = (clone $statBase)->whereIn('tingkat_kerusakan', ['berat', 'total'])->count();
        $statBiaya   = (clone $statBase)->sum('estimasi_biaya');

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $list = $query->orderByRaw("CASE ba.status
                WHEN 'diajukan' THEN 1
                WHEN 'diverifikasi' THEN 2
                WHEN 'disetujui' THEN 3
                WHEN 'draf' THEN 4
                ELSE 5 END ASC")
            ->orderByDesc('ba.tanggal_kejadian')
            ->orderByDesc('ba.id')
            ->paginate($perPage)
            ->withQueryString();

        return view('dashboard.laboran.kerusakan', array_merge(
            compact('list', 'search', 'tingkatFilter', 'statusFilter', 'perPage',
                'statTotal', 'statDiajukan', 'statBerat', 'statBiaya'),
            $this->abilities(),
            [
                'context'        => $context,
                'scopeBadge'     => LaboranScope::badge($context),
                'itemOptions'    => $this->itemOptions($context),
                'lokasiOptions'  => $this->lokasiOptions(),
                'tingkatList'    => self::TINGKAT,
                'statusList'     => self::STATUS,
                'pelakuList'     => self::PELAKU_TIPE,
                'jurusanOptions' => LaboranScope::jurusanOptions($context),
            ]
        ));
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $this->validated($request);

        $item = $this->resolveItem($data['item_id'] ?? null, $context);

        $id = DB::table('laboran_berita_acara_kerusakan')->insertGetId([
            'jurusan_id'               => LaboranScope::jurusanForWrite($data['jurusan_id'] ?? null, $context),
            'ruang_lab_nama'           => $data['ruang_lab_nama'] ?? ($item->ruang_lab_nama ?? null),
            'nomor_ba'                 => $this->generateNomor('laboran_berita_acara_kerusakan', 'nomor_ba', 'BAK'),
            'tanggal_kejadian'         => $data['tanggal_kejadian'],
            'tanggal_ba'               => $data['tanggal_ba'] ?? now()->toDateString(),
            'item_id'                  => $item->id ?? null,
            'nama_alat'                => ($data['nama_alat'] ?? '') ?: ($item->nama_item ?? ''),
            'kode_alat'                => $data['kode_alat'] ?? ($item->kode_item ?? null),
            'jumlah_rusak'             => $data['jumlah_rusak'] ?? 1,
            'satuan'                   => $data['satuan'] ?? ($item->satuan ?? 'unit'),
            'tingkat_kerusakan'        => $data['tingkat_kerusakan'],
            'kronologi'                => $data['kronologi'] ?? null,
            'penyebab'                 => $data['penyebab'] ?? null,
            'pelaku_tipe'              => $data['pelaku_tipe'] ?? 'tidak_diketahui',
            'pelaku_id'                => $data['pelaku_id'] ?? null,
            'pelaku_nama'              => $data['pelaku_nama'] ?? null,
            'saksi_1_nama'             => $data['saksi_1_nama'] ?? null,
            'saksi_2_nama'             => $data['saksi_2_nama'] ?? null,
            'tindakan_lanjut'          => $data['tindakan_lanjut'] ?? null,
            'estimasi_biaya'           => $data['estimasi_biaya'] ?? null,
            'perlu_usulan_penggantian' => (bool) ($data['perlu_usulan_penggantian'] ?? false),
            'status'                   => $data['status'] ?? 'draf',
            'pelapor_ptk_id'           => $context['ptk_id'],
            'pelapor_nama'             => $context['nama'],
            'created_at'               => now(),
            'updated_at'               => now(),
        ]);

        // Sinkronkan kondisi inventaris sesuai tingkat kerusakan
        if ($item) {
            $kondisi = match ($data['tingkat_kerusakan']) {
                'ringan' => 'rusak_ringan',
                'sedang', 'berat', 'total' => 'rusak_berat',
                default => null,
            };
            if ($kondisi) {
                DB::table('laboran_bahan_alat')->where('id', $item->id)->update([
                    'kondisi'    => $kondisi,
                    'updated_at' => now(),
                ]);
            }
        }

        return $this->respond($request, true, 'Berita acara kerusakan berhasil dibuat.', 200, [
            'redirect' => route('dashboard.laboran.kerusakan.cetak', $id),
            'id'       => $id,
        ]);
    }

    public function update(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_berita_acara_kerusakan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Berita acara tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $data = $this->validated($request);

        $update = [
            'ruang_lab_nama'           => $data['ruang_lab_nama'] ?? $row->ruang_lab_nama,
            'tanggal_kejadian'         => $data['tanggal_kejadian'],
            'tanggal_ba'               => $data['tanggal_ba'] ?? $row->tanggal_ba,
            'nama_alat'                => ($data['nama_alat'] ?? '') ?: $row->nama_alat,
            'kode_alat'                => $data['kode_alat'] ?? $row->kode_alat,
            'jumlah_rusak'             => $data['jumlah_rusak'] ?? $row->jumlah_rusak,
            'satuan'                   => $data['satuan'] ?? $row->satuan,
            'tingkat_kerusakan'        => $data['tingkat_kerusakan'],
            'kronologi'                => $data['kronologi'] ?? null,
            'penyebab'                 => $data['penyebab'] ?? null,
            'pelaku_tipe'              => $data['pelaku_tipe'] ?? $row->pelaku_tipe,
            'pelaku_id'                => $data['pelaku_id'] ?? null,
            'pelaku_nama'              => $data['pelaku_nama'] ?? null,
            'saksi_1_nama'             => $data['saksi_1_nama'] ?? null,
            'saksi_2_nama'             => $data['saksi_2_nama'] ?? null,
            'tindakan_lanjut'          => $data['tindakan_lanjut'] ?? null,
            'estimasi_biaya'           => $data['estimasi_biaya'] ?? null,
            'perlu_usulan_penggantian' => (bool) ($data['perlu_usulan_penggantian'] ?? false),
            'status'                   => $data['status'] ?? $row->status,
            'updated_at'               => now(),
        ];

        if (in_array($update['status'], ['diverifikasi', 'disetujui', 'selesai'], true)
            && $update['status'] !== $row->status) {
            $update['verifikator_ptk_id'] = $context['ptk_id'];
            $update['tanggal_verifikasi'] = now();
            $update['catatan_verifikasi'] = $data['catatan_verifikasi'] ?? null;
        }

        DB::table('laboran_berita_acara_kerusakan')->where('id', $id)->update($update);

        return $this->respond($request, true, 'Berita acara kerusakan berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_berita_acara_kerusakan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Berita acara tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        DB::table('laboran_berita_acara_kerusakan')->where('id', $id)->delete();

        return $this->respond($request, true, 'Berita acara kerusakan berhasil dihapus.');
    }

    /**
     * Cetak Berita Acara Kerusakan (dokumen resmi A4).
     */
    public function cetak(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $ba = $this->findScoped('laboran_berita_acara_kerusakan', $id, $context);
        if (!$ba) {
            abort(404, 'Berita acara tidak ditemukan atau di luar cakupan jurusan Anda.');
        }

        $jurusan = $ba->jurusan_id
            ? DB::table('jurusan_meta')->where('jurusan_id', $ba->jurusan_id)->first()
            : null;

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

        $orientasi = strtolower((string) $request->query('orientasi', 'portrait'));
        if (!in_array($orientasi, ['portrait', 'landscape'], true)) {
            $orientasi = 'portrait';
        }

        return view('dashboard.laboran.kerusakan-cetak', compact(
            'ba', 'jurusan', 'sekolah', 'sekolahMeta', 'kepalaSekolah', 'kepalaLab', 'orientasi'
        ) + [
            'tingkatList' => self::TINGKAT,
            'statusList'  => self::STATUS,
            'pelakuList'  => self::PELAKU_TIPE,
        ]);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'item_id'                  => 'nullable|integer|exists:laboran_bahan_alat,id',
            'nama_alat'                => 'nullable|string|max:255',
            'kode_alat'                => 'nullable|string|max:50',
            'ruang_lab_nama'           => 'nullable|string|max:100',
            'tanggal_kejadian'         => 'required|date',
            'tanggal_ba'               => 'nullable|date',
            'jumlah_rusak'             => 'nullable|numeric|min:0.01',
            'satuan'                   => 'nullable|string|max:30',
            'tingkat_kerusakan'        => 'required|in:ringan,sedang,berat,total',
            'kronologi'                => 'nullable|string|max:3000',
            'penyebab'                 => 'nullable|string|max:2000',
            'pelaku_tipe'              => 'nullable|in:siswa,guru,tendik,eksternal,tidak_diketahui',
            'pelaku_id'                => 'nullable|string|max:50',
            'pelaku_nama'              => 'nullable|string|max:150',
            'saksi_1_nama'             => 'nullable|string|max:150',
            'saksi_2_nama'             => 'nullable|string|max:150',
            'tindakan_lanjut'          => 'nullable|string|max:2000',
            'estimasi_biaya'           => 'nullable|numeric|min:0',
            'perlu_usulan_penggantian' => 'nullable|boolean',
            'status'                   => 'nullable|in:draf,diajukan,diverifikasi,disetujui,selesai',
            'catatan_verifikasi'       => 'nullable|string|max:1000',
            'jurusan_id'               => 'nullable|string|max:50',
        ], [
            'tanggal_kejadian.required'  => 'Tanggal kejadian wajib diisi.',
            'tingkat_kerusakan.required' => 'Tingkat kerusakan wajib dipilih.',
        ]);
    }

    protected function resolveItem($itemId, array $context)
    {
        if (!$itemId) {
            return null;
        }

        $item = DB::table('laboran_bahan_alat')->where('id', $itemId)->first();
        if (!$item) {
            return null;
        }

        return LaboranScope::allows($item->jurusan_id ?? null, $context) ? $item : null;
    }
}
