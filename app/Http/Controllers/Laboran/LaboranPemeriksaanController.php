<?php

namespace App\Http\Controllers\Laboran;

use App\Support\LaboranScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Modul Pemeriksaan / Daftar Ceklist Alat & Bahan.
 *
 * Alur: buat pemeriksaan -> isi ceklist per item -> finalisasi (hitung rekap).
 */
class LaboranPemeriksaanController extends LaboranBaseController
{
    public const JENIS = [
        'harian'          => 'Harian',
        'mingguan'        => 'Mingguan',
        'bulanan'         => 'Bulanan',
        'semester'        => 'Semester',
        'sebelum_pakai'   => 'Sebelum Pakai',
        'sesudah_pakai'   => 'Sesudah Pakai',
    ];

    public const HASIL = [
        'sesuai'       => 'Sesuai',
        'tidak_sesuai' => 'Tidak Sesuai',
        'tidak_ada'    => 'Tidak Ada',
    ];

    public const STATUS = [
        'draf'  => 'Draf',
        'final' => 'Final',
    ];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $search       = trim((string) $request->query('q', ''));
        $jenisFilter  = trim((string) $request->query('jenis', ''));
        $statusFilter = trim((string) $request->query('status', ''));

        $query = DB::table('laboran_pemeriksaan as p')
            ->leftJoin('jurusan_meta as jm', 'p.jurusan_id', '=', 'jm.jurusan_id')
            ->select('p.*', 'jm.nama_jurusan', 'jm.singkatan');

        LaboranScope::apply($query, 'p.jurusan_id', $context);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('p.nomor_pemeriksaan', 'like', "%{$search}%")
                  ->orWhere('p.ruang_lab_nama', 'like', "%{$search}%")
                  ->orWhere('p.petugas_nama', 'like', "%{$search}%")
                  ->orWhere('p.kesimpulan', 'like', "%{$search}%");
            });
        }
        if ($jenisFilter !== '')  $query->where('p.jenis', $jenisFilter);
        if ($statusFilter !== '') $query->where('p.status', $statusFilter);

        $statBase = DB::table('laboran_pemeriksaan');
        LaboranScope::apply($statBase, 'jurusan_id', $context);

        $statTotal  = (clone $statBase)->count();
        $statDraf   = (clone $statBase)->where('status', 'draf')->count();
        $statFinal  = (clone $statBase)->where('status', 'final')->count();
        $statBulan  = (clone $statBase)->where('tanggal_pemeriksaan', '>=', now()->startOfMonth()->toDateString())->count();

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $list = $query->orderByDesc('p.tanggal_pemeriksaan')
            ->orderByDesc('p.id')
            ->paginate($perPage)
            ->withQueryString();

        return view('dashboard.laboran.pemeriksaan', array_merge(
            compact('list', 'search', 'jenisFilter', 'statusFilter', 'perPage',
                'statTotal', 'statDraf', 'statFinal', 'statBulan'),
            $this->abilities(),
            [
                'context'       => $context,
                'scopeBadge'    => LaboranScope::badge($context),
                'lokasiOptions' => $this->lokasiOptions(),
                'itemOptions'   => $this->itemOptions($context),
                'jenisList'     => self::JENIS,
                'hasilList'     => self::HASIL,
                'statusList'    => self::STATUS,
                'jurusanOptions' => LaboranScope::jurusanOptions($context),
            ]
        ));
    }

    /**
     * Halaman detail / pengisian ceklist.
     */
    public function show(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $pemeriksaan = $this->findScoped('laboran_pemeriksaan', $id, $context);
        if (!$pemeriksaan) {
            abort(404, 'Data pemeriksaan tidak ditemukan atau di luar cakupan jurusan Anda.');
        }

        $items = DB::table('laboran_pemeriksaan_item')
            ->where('pemeriksaan_id', $id)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        $jurusan = $pemeriksaan->jurusan_id
            ? DB::table('jurusan_meta')->where('jurusan_id', $pemeriksaan->jurusan_id)->first()
            : null;

        return view('dashboard.laboran.pemeriksaan-detail', array_merge(
            compact('pemeriksaan', 'items', 'jurusan'),
            $this->abilities(),
            [
                'context'    => $context,
                'scopeBadge' => LaboranScope::badge($context),
                'hasilList'  => self::HASIL,
                'jenisList'  => self::JENIS,
                'statusList' => self::STATUS,
                'itemOptions' => $this->itemOptions($context),
            ]
        ));
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $request->validate([
            'tanggal_pemeriksaan' => 'required|date',
            'jenis'               => 'required|in:harian,mingguan,bulanan,semester,sebelum_pakai,sesudah_pakai',
            'ruang_lab_nama'      => 'nullable|string|max:100',
            'petugas_nama'        => 'nullable|string|max:150',
            'kesimpulan'          => 'nullable|string|max:2000',
            'rekomendasi'         => 'nullable|string|max:2000',
            'status'              => 'nullable|in:draf,final',
            'jurusan_id'          => 'nullable|string|max:50',
            'item_ids'            => 'nullable|array',
            'item_ids.*'          => 'integer|exists:laboran_bahan_alat,id',
        ]);

        $id = DB::table('laboran_pemeriksaan')->insertGetId([
            'jurusan_id'           => LaboranScope::jurusanForWrite($data['jurusan_id'] ?? null, $context),
            'ruang_lab_nama'       => $data['ruang_lab_nama'] ?? null,
            'nomor_pemeriksaan'    => $this->generateNomor('laboran_pemeriksaan', 'nomor_pemeriksaan', 'PRK'),
            'tanggal_pemeriksaan'  => $data['tanggal_pemeriksaan'],
            'jenis'                => $data['jenis'],
            'petugas_ptk_id'       => $context['ptk_id'],
            'petugas_nama'         => $data['petugas_nama'] ?? $context['nama'],
            'kesimpulan'           => $data['kesimpulan'] ?? null,
            'rekomendasi'          => $data['rekomendasi'] ?? null,
            'status'               => $data['status'] ?? 'draf',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        // Isi ceklist awal dari item yang dipilih (atau semua item dalam cakupan)
        $itemIds = $data['item_ids'] ?? [];

        $itemQuery = DB::table('laboran_bahan_alat');
        LaboranScope::apply($itemQuery, 'jurusan_id', $context);
        if (!empty($itemIds)) {
            $itemQuery->whereIn('id', $itemIds);
        }
        $items = $itemQuery->orderBy('nama_item')->get();

        $urutan = 0;
        foreach ($items as $item) {
            DB::table('laboran_pemeriksaan_item')->insert([
                'pemeriksaan_id'   => $id,
                'item_id'          => $item->id,
                'nama_item'        => $item->nama_item,
                'kode_item'        => $item->kode_item,
                'satuan'           => $item->satuan,
                'kondisi_standar'  => 'Kondisi baik, lengkap, dan berfungsi normal',
                'hasil'            => 'sesuai',
                'jumlah_diperiksa' => $item->stok_total,
                'urutan'           => $urutan++,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        $this->rekapPemeriksaan($id);

        return $this->respond($request, true, 'Pemeriksaan dibuat dengan ' . $items->count() . ' item ceklist.', 200, [
            'redirect' => route('dashboard.laboran.pemeriksaan.show', $id),
            'id'       => $id,
        ]);
    }

    public function update(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_pemeriksaan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Data pemeriksaan tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $data = $request->validate([
            'tanggal_pemeriksaan' => 'required|date',
            'jenis'               => 'required|in:harian,mingguan,bulanan,semester,sebelum_pakai,sesudah_pakai',
            'ruang_lab_nama'      => 'nullable|string|max:100',
            'petugas_nama'        => 'nullable|string|max:150',
            'kesimpulan'          => 'nullable|string|max:2000',
            'rekomendasi'         => 'nullable|string|max:2000',
            'status'              => 'nullable|in:draf,final',
        ]);

        DB::table('laboran_pemeriksaan')->where('id', $id)->update([
            'tanggal_pemeriksaan' => $data['tanggal_pemeriksaan'],
            'jenis'               => $data['jenis'],
            'ruang_lab_nama'      => $data['ruang_lab_nama'] ?? null,
            'petugas_nama'        => $data['petugas_nama'] ?? $row->petugas_nama,
            'kesimpulan'          => $data['kesimpulan'] ?? null,
            'rekomendasi'         => $data['rekomendasi'] ?? null,
            'status'              => $data['status'] ?? $row->status,
            'updated_at'          => now(),
        ]);

        return $this->respond($request, true, 'Data pemeriksaan berhasil diperbarui.');
    }

    /**
     * Simpan hasil ceklist per item (AJAX dari halaman detail).
     */
    public function simpanItem(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $pemeriksaan = $this->findScoped('laboran_pemeriksaan', $id, $context);
        if (!$pemeriksaan) {
            return $this->respond($request, false, 'Data pemeriksaan tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $data = $request->validate([
            'items'                  => 'required|array|min:1',
            'items.*.id'             => 'required|integer',
            'items.*.hasil'          => 'required|in:sesuai,tidak_sesuai,tidak_ada',
            'items.*.jumlah_diperiksa' => 'nullable|numeric|min:0',
            'items.*.keterangan'     => 'nullable|string|max:500',
            'items.*.tindak_lanjut'  => 'nullable|string|max:500',
        ]);

        // Pastikan item milik pemeriksaan ini
        $validIds = DB::table('laboran_pemeriksaan_item')
            ->where('pemeriksaan_id', $id)
            ->pluck('id')
            ->all();

        foreach ($data['items'] as $it) {
            if (!in_array((int) $it['id'], array_map('intval', $validIds), true)) {
                continue;
            }

            DB::table('laboran_pemeriksaan_item')->where('id', $it['id'])->update([
                'hasil'            => $it['hasil'],
                'jumlah_diperiksa' => $it['jumlah_diperiksa'] ?? null,
                'keterangan'       => $it['keterangan'] ?? null,
                'tindak_lanjut'    => $it['tindak_lanjut'] ?? null,
                'updated_at'       => now(),
            ]);
        }

        $this->rekapPemeriksaan($id);

        $rekap = DB::table('laboran_pemeriksaan')->where('id', $id)->first();

        return $this->respond($request, true, 'Hasil pemeriksaan berhasil disimpan.', 200, [
            'rekap' => [
                'total_item'        => (int) $rekap->total_item,
                'total_sesuai'      => (int) $rekap->total_sesuai,
                'total_tidak_sesuai' => (int) $rekap->total_tidak_sesuai,
                'total_tidak_ada'   => (int) $rekap->total_tidak_ada,
            ],
        ]);
    }

    /**
     * Tambah item ceklist baru ke pemeriksaan yang sudah ada.
     */
    public function tambahItem(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $pemeriksaan = $this->findScoped('laboran_pemeriksaan', $id, $context);
        if (!$pemeriksaan) {
            return $this->respond($request, false, 'Data pemeriksaan tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $data = $request->validate([
            'item_id'          => 'nullable|integer|exists:laboran_bahan_alat,id',
            'nama_item'        => 'nullable|string|max:255',
            'kode_item'        => 'nullable|string|max:50',
            'satuan'           => 'nullable|string|max:30',
            'kondisi_standar'  => 'nullable|string|max:500',
            'jumlah_diperiksa' => 'nullable|numeric|min:0',
        ]);

        $item = null;
        if (!empty($data['item_id'])) {
            $item = DB::table('laboran_bahan_alat')->where('id', $data['item_id'])->first();
            if ($item && !LaboranScope::allows($item->jurusan_id ?? null, $context)) {
                $item = null;
            }
        }

        $namaItem = trim((string) ($data['nama_item'] ?? '')) ?: ($item->nama_item ?? '');
        if ($namaItem === '') {
            return $this->respond($request, false, 'Nama item wajib diisi.', 422);
        }

        $urutan = (int) DB::table('laboran_pemeriksaan_item')->where('pemeriksaan_id', $id)->max('urutan');

        DB::table('laboran_pemeriksaan_item')->insert([
            'pemeriksaan_id'   => $id,
            'item_id'          => $item->id ?? null,
            'nama_item'        => $namaItem,
            'kode_item'        => $data['kode_item'] ?? ($item->kode_item ?? null),
            'satuan'           => $data['satuan'] ?? ($item->satuan ?? null),
            'kondisi_standar'  => $data['kondisi_standar'] ?? 'Kondisi baik, lengkap, dan berfungsi normal',
            'hasil'            => 'sesuai',
            'jumlah_diperiksa' => $data['jumlah_diperiksa'] ?? ($item->stok_total ?? null),
            'urutan'           => $urutan + 1,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $this->rekapPemeriksaan($id);

        return $this->respond($request, true, 'Item ceklist berhasil ditambahkan.');
    }

    public function hapusItem(Request $request, $id, $itemId)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $pemeriksaan = $this->findScoped('laboran_pemeriksaan', $id, $context);
        if (!$pemeriksaan) {
            return $this->respond($request, false, 'Data pemeriksaan tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        DB::table('laboran_pemeriksaan_item')
            ->where('pemeriksaan_id', $id)
            ->where('id', $itemId)
            ->delete();

        $this->rekapPemeriksaan($id);

        return $this->respond($request, true, 'Item ceklist berhasil dihapus.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_pemeriksaan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Data pemeriksaan tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        DB::table('laboran_pemeriksaan_item')->where('pemeriksaan_id', $id)->delete();
        DB::table('laboran_pemeriksaan')->where('id', $id)->delete();

        return $this->respond($request, true, 'Data pemeriksaan berhasil dihapus.');
    }

    /**
     * Hitung ulang rekap hasil ceklist.
     */
    protected function rekapPemeriksaan($id): void
    {
        $items = DB::table('laboran_pemeriksaan_item')->where('pemeriksaan_id', $id)->get();

        DB::table('laboran_pemeriksaan')->where('id', $id)->update([
            'total_item'         => $items->count(),
            'total_sesuai'       => $items->where('hasil', 'sesuai')->count(),
            'total_tidak_sesuai' => $items->where('hasil', 'tidak_sesuai')->count(),
            'total_tidak_ada'    => $items->where('hasil', 'tidak_ada')->count(),
            'updated_at'         => now(),
        ]);
    }
}
