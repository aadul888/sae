<?php

namespace App\Http\Controllers\Laboran;

use App\Models\Sekolah;
use App\Models\SekolahMeta;
use App\Support\LaboranScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Modul BON / FORM PERMINTAAN BAHAN & ALAT PRAKTIK PEMBELAJARAN.
 *
 * Indikator Tupoksi #2 — bukti fisik: Bon/Form Permintaan Bahan & Alat Praktik.
 *
 * Alur prosedural:
 *   Guru mengajukan permintaan -> Laboran memeriksa ketersediaan ->
 *   menyiapkan alat/bahan -> serah terima -> selesai.
 *   Stok bahan habis pakai otomatis berkurang saat diserahkan.
 */
class LaboranPermintaanController extends LaboranBaseController
{
    public const STATUS = [
        'diajukan'   => 'Diajukan',
        'disiapkan'  => 'Sedang Disiapkan',
        'diserahkan' => 'Diserahkan',
        'selesai'    => 'Selesai',
        'ditolak'    => 'Ditolak',
    ];

    public const KETERSEDIAAN = [
        'menunggu'   => 'Menunggu Pemeriksaan',
        'tersedia'   => 'Tersedia',
        'kurang'     => 'Kurang',
        'tidak_ada'  => 'Tidak Ada',
        'diserahkan' => 'Diserahkan',
    ];

    public const JENIS = [
        'alat'              => 'Alat',
        'bahan_habis_pakai' => 'Bahan Habis Pakai',
    ];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $search        = trim((string) $request->query('q', ''));
        $statusFilter  = trim((string) $request->query('status', ''));
        $tanggalFrom   = trim((string) $request->query('dari', ''));
        $tanggalTo     = trim((string) $request->query('sampai', ''));

        $query = DB::table('laboran_permintaan as p')
            ->leftJoin('jurusan_meta as jm', 'p.jurusan_id', '=', 'jm.jurusan_id')
            ->select('p.*', 'jm.nama_jurusan', 'jm.singkatan');

        LaboranScope::apply($query, 'p.jurusan_id', $context);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('p.nomor_permintaan', 'like', "%{$search}%")
                  ->orWhere('p.pemohon_nama', 'like', "%{$search}%")
                  ->orWhere('p.mata_pelajaran', 'like', "%{$search}%")
                  ->orWhere('p.rombel_nama', 'like', "%{$search}%")
                  ->orWhere('p.keperluan', 'like', "%{$search}%");
            });
        }
        if ($statusFilter !== '') $query->where('p.status', $statusFilter);
        if ($tanggalFrom !== '')  $query->where('p.tanggal_permintaan', '>=', $tanggalFrom);
        if ($tanggalTo !== '')    $query->where('p.tanggal_permintaan', '<=', $tanggalTo);

        $statBase = DB::table('laboran_permintaan');
        LaboranScope::apply($statBase, 'jurusan_id', $context);

        $statTotal      = (clone $statBase)->count();
        $statDiajukan   = (clone $statBase)->where('status', 'diajukan')->count();
        $statDiserahkan = (clone $statBase)->whereIn('status', ['diserahkan', 'selesai'])->count();
        $statBulanIni   = (clone $statBase)
            ->whereBetween('tanggal_permintaan', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $list = $query->orderByRaw("CASE p.status
                WHEN 'diajukan' THEN 1
                WHEN 'disiapkan' THEN 2
                WHEN 'diserahkan' THEN 3
                WHEN 'ditolak' THEN 4
                ELSE 5 END ASC")
            ->orderByDesc('p.tanggal_permintaan')
            ->orderByDesc('p.id')
            ->paginate($perPage)
            ->withQueryString();

        return view('dashboard.laboran.permintaan', array_merge(
            compact('list', 'search', 'statusFilter', 'tanggalFrom', 'tanggalTo', 'perPage',
                'statTotal', 'statDiajukan', 'statDiserahkan', 'statBulanIni'),
            $this->abilities(),
            [
                'context'        => $context,
                'scopeBadge'     => LaboranScope::badge($context),
                'itemOptions'    => $this->itemOptions($context),
                'lokasiOptions'  => $this->lokasiOptions(),
                'statusList'     => self::STATUS,
                'ketersediaanList' => self::KETERSEDIAAN,
                'jenisList'      => self::JENIS,
                'jurusanOptions' => LaboranScope::jurusanOptions($context),
            ]
        ));
    }

    public function show(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $permintaan = $this->findScoped('laboran_permintaan', $id, $context);
        if (!$permintaan) {
            abort(404, 'Permintaan tidak ditemukan atau di luar cakupan jurusan Anda.');
        }

        $items = DB::table('laboran_permintaan_item')
            ->where('permintaan_id', $id)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        $jurusan = $permintaan->jurusan_id
            ? DB::table('jurusan_meta')->where('jurusan_id', $permintaan->jurusan_id)->first()
            : null;

        return view('dashboard.laboran.permintaan-detail', array_merge(
            compact('permintaan', 'items', 'jurusan'),
            $this->abilities(),
            [
                'context'          => $context,
                'scopeBadge'       => LaboranScope::badge($context),
                'statusList'       => self::STATUS,
                'ketersediaanList' => self::KETERSEDIAAN,
                'jenisList'        => self::JENIS,
                'itemOptions'      => $this->itemOptions($context),
            ]
        ));
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $request->validate([
            'tanggal_permintaan'  => 'required|date',
            'tanggal_dibutuhkan'  => 'nullable|date',
            'pemohon_nama'        => 'required|string|max:150',
            'pemohon_ptk_id'      => 'nullable|string|max:50',
            'rombel_id'           => 'nullable|string|max:50',
            'mata_pelajaran'      => 'nullable|string|max:255',
            'ruang_lab_nama'      => 'nullable|string|max:100',
            'keperluan'           => 'nullable|string|max:500',
            'catatan_pemohon'     => 'nullable|string|max:1000',
            'status'              => 'nullable|in:diajukan,disiapkan,diserahkan,selesai,ditolak',
            'jurusan_id'          => 'nullable|string|max:50',
            'item_ids'            => 'required|array|min:1',
            'item_ids.*'          => 'integer',
            'item_jumlah'         => 'nullable|array',
            'item_jumlah.*'       => 'numeric|min:0.01',
        ], [
            'item_ids.required' => 'Pilih minimal satu alat/bahan yang diminta.',
        ]);

        $rombelNama = null;
        if (!empty($data['rombel_id'])) {
            $rombelNama = DB::table('rombongan_belajar')
                ->where('rombongan_belajar_id', $data['rombel_id'])
                ->value('nama');
        }

        $id = DB::table('laboran_permintaan')->insertGetId([
            'jurusan_id'         => LaboranScope::jurusanForWrite($data['jurusan_id'] ?? null, $context),
            'nomor_permintaan'   => $this->generateNomor('laboran_permintaan', 'nomor_permintaan', 'PMT'),
            'tanggal_permintaan' => $data['tanggal_permintaan'],
            'tanggal_dibutuhkan' => $data['tanggal_dibutuhkan'] ?? null,
            'pemohon_ptk_id'     => $data['pemohon_ptk_id'] ?? null,
            'pemohon_nama'       => trim($data['pemohon_nama']),
            'rombel_id'          => $data['rombel_id'] ?? null,
            'rombel_nama'        => $rombelNama,
            'mata_pelajaran'     => $data['mata_pelajaran'] ?? null,
            'ruang_lab_nama'     => $data['ruang_lab_nama'] ?? null,
            'keperluan'          => $data['keperluan'] ?? null,
            'catatan_pemohon'    => $data['catatan_pemohon'] ?? null,
            'status'             => $data['status'] ?? 'diajukan',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        // Salin item yang diminta
        $jumlahMap = $data['item_jumlah'] ?? [];
        $items = DB::table('laboran_bahan_alat')
            ->whereIn('id', $data['item_ids'])
            ->get();

        $urutan = 0;
        foreach ($items as $item) {
            if (!LaboranScope::allows($item->jurusan_id ?? null, $context)) {
                continue;
            }

            DB::table('laboran_permintaan_item')->insert([
                'permintaan_id'       => $id,
                'item_id'             => $item->id,
                'nama_item'           => $item->nama_item,
                'kode_item'           => $item->kode_item,
                'jenis'               => $item->jenis,
                'jumlah_diminta'      => (float) ($jumlahMap[$item->id] ?? 1),
                'satuan'              => $item->satuan,
                'status_ketersediaan' => 'menunggu',
                'urutan'              => $urutan++,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }

        $this->rekapPermintaan($id);

        return $this->respond($request, true, 'Form permintaan berhasil dibuat.', 200, [
            'redirect' => route('dashboard.laboran.permintaan.show', $id),
            'id'       => $id,
        ]);
    }

    public function update(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_permintaan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Permintaan tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $data = $request->validate([
            'tanggal_permintaan' => 'required|date',
            'tanggal_dibutuhkan' => 'nullable|date',
            'pemohon_nama'       => 'required|string|max:150',
            'mata_pelajaran'     => 'nullable|string|max:255',
            'ruang_lab_nama'     => 'nullable|string|max:100',
            'keperluan'          => 'nullable|string|max:500',
            'catatan_pemohon'    => 'nullable|string|max:1000',
            'catatan_petugas'    => 'nullable|string|max:1000',
            'status'             => 'nullable|in:diajukan,disiapkan,diserahkan,selesai,ditolak',
        ]);

        $update = [
            'tanggal_permintaan' => $data['tanggal_permintaan'],
            'tanggal_dibutuhkan' => $data['tanggal_dibutuhkan'] ?? null,
            'pemohon_nama'       => trim($data['pemohon_nama']),
            'mata_pelajaran'     => $data['mata_pelajaran'] ?? null,
            'ruang_lab_nama'     => $data['ruang_lab_nama'] ?? null,
            'keperluan'          => $data['keperluan'] ?? null,
            'catatan_pemohon'    => $data['catatan_pemohon'] ?? null,
            'catatan_petugas'    => $data['catatan_petugas'] ?? null,
            'status'             => $data['status'] ?? $row->status,
            'updated_at'         => now(),
        ];

        if (($update['status'] ?? '') === 'disiapkan' && $row->status !== 'disiapkan') {
            $update['tanggal_disiapkan'] = now();
            $update['petugas_ptk_id'] = $context['ptk_id'];
            $update['petugas_nama'] = $context['nama'];
        }

        DB::table('laboran_permintaan')->where('id', $id)->update($update);

        return $this->respond($request, true, 'Form permintaan berhasil diperbarui.');
    }

    /**
     * Periksa ketersediaan item permintaan (otomatis dari stok).
     */
    public function periksaKetersediaan(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_permintaan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Permintaan tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $items = DB::table('laboran_permintaan_item')->where('permintaan_id', $id)->get();

        foreach ($items as $it) {
            $stok = $it->item_id
                ? (float) DB::table('laboran_bahan_alat')->where('id', $it->item_id)->value('stok_tersedia')
                : 0;

            $status = 'tidak_ada';
            if ($stok <= 0) {
                $status = 'tidak_ada';
            } elseif ($stok >= (float) $it->jumlah_diminta) {
                $status = 'tersedia';
            } else {
                $status = 'kurang';
            }

            DB::table('laboran_permintaan_item')->where('id', $it->id)->update([
                'status_ketersediaan' => $status,
                'updated_at'          => now(),
            ]);
        }

        return $this->respond($request, true, 'Pemeriksaan ketersediaan selesai.');
    }

    /**
     * Serah terima: stok bahan habis pakai berkurang, alat tidak mengurangi stok.
     */
    public function serahkan(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_permintaan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Permintaan tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        if (in_array($row->status, ['diserahkan', 'selesai'], true)) {
            return $this->respond($request, false, 'Permintaan ini sudah diserahkan.', 422);
        }

        $data = $request->validate([
            'item_diberikan'   => 'nullable|array',
            'item_diberikan.*' => 'numeric|min:0',
            'catatan_petugas'  => 'nullable|string|max:1000',
        ]);

        $diberikan = $data['item_diberikan'] ?? [];
        $items = DB::table('laboran_permintaan_item')->where('permintaan_id', $id)->get();

        foreach ($items as $it) {
            $jumlah = (float) ($diberikan[$it->id] ?? $it->jumlah_diminta);

            DB::table('laboran_permintaan_item')->where('id', $it->id)->update([
                'jumlah_diberikan'    => $jumlah,
                'status_ketersediaan' => 'diserahkan',
                'updated_at'          => now(),
            ]);

            // Bahan habis pakai mengurangi stok; alat hanya dipinjam (tidak mengurangi)
            if ($it->item_id && $it->jenis === 'bahan_habis_pakai' && $jumlah > 0) {
                $stok = (float) DB::table('laboran_bahan_alat')->where('id', $it->item_id)->value('stok_tersedia');
                $sisa = max(0, $stok - $jumlah);

                DB::table('laboran_bahan_alat')->where('id', $it->item_id)->update([
                    'stok_tersedia' => $sisa,
                    'updated_at'    => now(),
                ]);
            }
        }

        DB::table('laboran_permintaan')->where('id', $id)->update([
            'status'             => 'diserahkan',
            'tanggal_diserahkan' => now(),
            'petugas_ptk_id'     => $context['ptk_id'],
            'petugas_nama'       => $context['nama'],
            'catatan_petugas'    => $data['catatan_petugas'] ?? $row->catatan_petugas,
            'updated_at'         => now(),
        ]);

        return $this->respond($request, true, 'Serah terima berhasil dicatat.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_permintaan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Permintaan tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        DB::table('laboran_permintaan_item')->where('permintaan_id', $id)->delete();
        DB::table('laboran_permintaan')->where('id', $id)->delete();

        return $this->respond($request, true, 'Form permintaan berhasil dihapus.');
    }

    /**
     * Cetak Bon/Form Permintaan (dokumen resmi).
     */
    public function cetak(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $permintaan = $this->findScoped('laboran_permintaan', $id, $context);
        if (!$permintaan) {
            abort(404, 'Permintaan tidak ditemukan atau di luar cakupan jurusan Anda.');
        }

        $items = DB::table('laboran_permintaan_item')
            ->where('permintaan_id', $id)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        $jurusan = $permintaan->jurusan_id
            ? DB::table('jurusan_meta')->where('jurusan_id', $permintaan->jurusan_id)->first()
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

        return view('dashboard.laboran.permintaan-cetak', compact(
            'permintaan', 'items', 'jurusan', 'sekolah', 'sekolahMeta',
            'kepalaSekolah', 'kepalaLab', 'orientasi'
        ) + [
            'statusList'       => self::STATUS,
            'ketersediaanList' => self::KETERSEDIAAN,
            'jenisList'        => self::JENIS,
        ]);
    }

    protected function rekapPermintaan($id): void
    {
        $count = DB::table('laboran_permintaan_item')->where('permintaan_id', $id)->count();

        DB::table('laboran_permintaan')->where('id', $id)->update([
            'total_item' => $count,
            'updated_at' => now(),
        ]);
    }
}
