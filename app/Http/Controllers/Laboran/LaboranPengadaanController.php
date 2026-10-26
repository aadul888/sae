<?php

namespace App\Http\Controllers\Laboran;

use App\Support\LaboranScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Modul Daftar Usulan Pengadaan Alat & Bahan Praktik.
 *
 * Alur: susun usulan (header + item) -> ajukan -> verifikasi -> disetujui/ditolak.
 * Sumber item dapat ditarik dari modul Analisis Kebutuhan.
 */
class LaboranPengadaanController extends LaboranBaseController
{
    public const STATUS = [
        'draf'        => 'Draf',
        'diajukan'    => 'Diajukan',
        'diverifikasi' => 'Diverifikasi',
        'disetujui'   => 'Disetujui',
        'ditolak'     => 'Ditolak',
        'direalisasi' => 'Direalisasi',
    ];

    public const URGENSI = [
        'rendah' => 'Rendah',
        'sedang' => 'Sedang',
        'tinggi' => 'Tinggi',
        'kritis' => 'Kritis',
    ];

    public const JENIS_PENGADAAN = [
        'semua'             => 'Semua (Alat & Bahan)',
        'alat'              => 'Alat Praktik',
        'bahan_habis_pakai' => 'Bahan Habis Pakai',
    ];

    public const SUMBER_DANA = [
        'BOS'     => 'Dana BOS',
        'DAK'     => 'Dana DAK',
        'Komite'  => 'Komite Sekolah',
        'Yayasan' => 'Yayasan',
        'Hibah'   => 'Hibah / Bantuan',
    ];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $search       = trim((string) $request->query('q', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $urgensiFilter = trim((string) $request->query('urgensi', ''));
        $jenisFilter  = trim((string) $request->query('jenis', ''));

        $query = DB::table('laboran_usulan_pengadaan as u')
            ->leftJoin('jurusan_meta as jm', 'u.jurusan_id', '=', 'jm.jurusan_id')
            ->select('u.*', 'jm.nama_jurusan', 'jm.singkatan');

        LaboranScope::apply($query, 'u.jurusan_id', $context);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('u.nomor_usulan', 'like', "%{$search}%")
                  ->orWhere('u.pemohon_nama', 'like', "%{$search}%")
                  ->orWhere('u.justifikasi', 'like', "%{$search}%");
            });
        }
        if ($statusFilter !== '')  $query->where('u.status', $statusFilter);
        if ($urgensiFilter !== '') $query->where('u.urgensi', $urgensiFilter);
        if ($jenisFilter !== '' && array_key_exists($jenisFilter, self::JENIS_PENGADAAN)) {
            $query->where('u.jenis', $jenisFilter);
        }

        $statBase = DB::table('laboran_usulan_pengadaan');
        LaboranScope::apply($statBase, 'jurusan_id', $context);

        $statTotal     = (clone $statBase)->count();
        $statDiajukan  = (clone $statBase)->whereIn('status', ['diajukan', 'diverifikasi'])->count();
        $statDisetujui = (clone $statBase)->whereIn('status', ['disetujui', 'direalisasi'])->count();
        $statBiaya     = (clone $statBase)->whereIn('status', ['disetujui', 'direalisasi'])->sum('total_estimasi_biaya');

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $list = $query->orderByRaw("CASE u.status
                WHEN 'diajukan' THEN 1
                WHEN 'diverifikasi' THEN 2
                WHEN 'disetujui' THEN 3
                WHEN 'direalisasi' THEN 4
                WHEN 'ditolak' THEN 5
                ELSE 6 END ASC")
            ->orderByDesc('u.tanggal_usulan')
            ->orderByDesc('u.id')
            ->paginate($perPage)
            ->withQueryString();

        // Kebutuhan yang belum masuk usulan (untuk penarikan otomatis)
        $kebutuhanTersedia = DB::table('laboran_kebutuhan as k')
            ->whereIn('k.status', ['diverifikasi', 'disetujui'])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('laboran_usulan_pengadaan_item as ui')
                    ->whereColumn('ui.kebutuhan_id', 'k.id');
            })
            ->select('k.id', 'k.nama_item', 'k.jenis', 'k.spesifikasi', 'k.satuan', 'k.selisih', 'k.prioritas', 'k.jurusan_id');
        LaboranScope::apply($kebutuhanTersedia, 'k.jurusan_id', $context);
        $kebutuhanTersedia = $kebutuhanTersedia->orderBy('k.nama_item')->limit(200)->get();

        return view('dashboard.laboran.pengadaan', array_merge(
            compact('list', 'search', 'statusFilter', 'urgensiFilter', 'jenisFilter', 'perPage',
                'statTotal', 'statDiajukan', 'statDisetujui', 'statBiaya', 'kebutuhanTersedia'),
            $this->abilities(),
            [
                'context'        => $context,
                'scopeBadge'     => LaboranScope::badge($context),
                'statusList'     => self::STATUS,
                'urgensiList'    => self::URGENSI,
                'jenisList'      => self::JENIS_PENGADAAN,
                'sumberDanaList' => self::SUMBER_DANA,
                'jurusanOptions' => LaboranScope::jurusanOptions($context),
            ]
        ));
    }

    public function show(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $usulan = $this->findScoped('laboran_usulan_pengadaan', $id, $context);
        if (!$usulan) {
            abort(404, 'Usulan pengadaan tidak ditemukan atau di luar cakupan jurusan Anda.');
        }

        $items = DB::table('laboran_usulan_pengadaan_item')
            ->where('usulan_id', $id)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        $jurusan = $usulan->jurusan_id
            ? DB::table('jurusan_meta')->where('jurusan_id', $usulan->jurusan_id)->first()
            : null;

        return view('dashboard.laboran.pengadaan-detail', array_merge(
            compact('usulan', 'items', 'jurusan'),
            $this->abilities(),
            [
                'context'        => $context,
                'scopeBadge'     => LaboranScope::badge($context),
                'statusList'     => self::STATUS,
                'urgensiList'    => self::URGENSI,
                'sumberDanaList' => self::SUMBER_DANA,
            ]
        ));
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $request->validate([
            'tahun_ajaran'   => 'nullable|string|max:20',
            'semester'       => 'nullable|string|max:10',
            'tanggal_usulan' => 'required|date',
            'jenis'          => 'nullable|in:semua,alat,bahan_habis_pakai',
            'pemohon_nama'   => 'nullable|string|max:150',
            'sumber_dana'    => 'nullable|string|max:40',
            'urgensi'        => 'required|in:rendah,sedang,tinggi,kritis',
            'status'         => 'nullable|in:draf,diajukan,diverifikasi,disetujui,ditolak,direalisasi',
            'justifikasi'    => 'nullable|string|max:2000',
            'jurusan_id'     => 'nullable|string|max:50',
            'kebutuhan_ids'  => 'nullable|array',
            'kebutuhan_ids.*' => 'integer',
        ]);

        $id = DB::table('laboran_usulan_pengadaan')->insertGetId([
            'jurusan_id'     => LaboranScope::jurusanForWrite($data['jurusan_id'] ?? null, $context),
            'nomor_usulan'   => $this->generateNomor('laboran_usulan_pengadaan', 'nomor_usulan', 'USL'),
            'tahun_ajaran'   => $data['tahun_ajaran'] ?? null,
            'semester'       => $data['semester'] ?? null,
            'jenis'          => $data['jenis'] ?? 'semua',
            'tanggal_usulan' => $data['tanggal_usulan'],
            'pemohon_ptk_id' => $context['ptk_id'],
            'pemohon_nama'   => $data['pemohon_nama'] ?? $context['nama'],
            'sumber_dana'    => $data['sumber_dana'] ?? null,
            'urgensi'        => $data['urgensi'],
            'status'         => $data['status'] ?? 'draf',
            'justifikasi'    => $data['justifikasi'] ?? null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // Tarik item dari analisis kebutuhan yang dipilih
        $kebutuhanIds = $data['kebutuhan_ids'] ?? [];
        if (!empty($kebutuhanIds)) {
            $this->tarikKebutuhan($id, $kebutuhanIds, $context);
        }

        $this->rekapUsulan($id);

        return $this->respond($request, true, 'Usulan pengadaan berhasil dibuat.', 200, [
            'redirect' => route('dashboard.laboran.pengadaan.show', $id),
            'id'       => $id,
        ]);
    }

    public function update(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_usulan_pengadaan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Usulan tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $data = $request->validate([
            'tahun_ajaran'       => 'nullable|string|max:20',
            'semester'           => 'nullable|string|max:10',
            'tanggal_usulan'     => 'required|date',
            'jenis'              => 'nullable|in:semua,alat,bahan_habis_pakai',
            'pemohon_nama'       => 'nullable|string|max:150',
            'sumber_dana'        => 'nullable|string|max:40',
            'urgensi'            => 'required|in:rendah,sedang,tinggi,kritis',
            'status'             => 'nullable|in:draf,diajukan,diverifikasi,disetujui,ditolak,direalisasi',
            'justifikasi'        => 'nullable|string|max:2000',
            'catatan_verifikasi' => 'nullable|string|max:2000',
        ]);

        $update = [
            'tahun_ajaran'       => $data['tahun_ajaran'] ?? null,
            'semester'           => $data['semester'] ?? null,
            'jenis'              => $data['jenis'] ?? $row->jenis ?? 'semua',
            'tanggal_usulan'     => $data['tanggal_usulan'],
            'pemohon_nama'       => $data['pemohon_nama'] ?? $row->pemohon_nama,
            'sumber_dana'        => $data['sumber_dana'] ?? $row->sumber_dana,
            'urgensi'            => $data['urgensi'],
            'status'             => $data['status'] ?? $row->status,
            'justifikasi'        => $data['justifikasi'] ?? null,
            'catatan_verifikasi' => $data['catatan_verifikasi'] ?? null,
            'updated_at'         => now(),
        ];

        // Catat jejak verifikasi saat status berubah ke tahap keputusan
        if (in_array($update['status'], ['diverifikasi', 'disetujui', 'ditolak'], true)
            && $update['status'] !== $row->status) {
            $update['diverifikasi_oleh_ptk_id'] = $context['ptk_id'];
            $update['tanggal_verifikasi'] = now();
        }

        DB::table('laboran_usulan_pengadaan')->where('id', $id)->update($update);

        return $this->respond($request, true, 'Usulan pengadaan berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_usulan_pengadaan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Usulan tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        DB::table('laboran_usulan_pengadaan_item')->where('usulan_id', $id)->delete();
        DB::table('laboran_usulan_pengadaan')->where('id', $id)->delete();

        return $this->respond($request, true, 'Usulan pengadaan berhasil dihapus.');
    }

    // =========================================================================
    // ITEM USULAN
    // =========================================================================

    public function tambahItem(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $usulan = $this->findScoped('laboran_usulan_pengadaan', $id, $context);
        if (!$usulan) {
            return $this->respond($request, false, 'Usulan tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $data = $request->validate([
            'nama_item'    => 'required|string|max:255',
            'jenis'        => 'required|in:alat,bahan_habis_pakai',
            'spesifikasi'  => 'nullable|string|max:1000',
            'jumlah'       => 'required|numeric|min:0.01',
            'satuan'       => 'required|string|max:30',
            'harga_satuan' => 'nullable|numeric|min:0',
            'prioritas'    => 'nullable|in:rendah,sedang,tinggi,kritis',
            'keterangan'   => 'nullable|string|max:500',
            'kebutuhan_id' => 'nullable|integer',
        ]);

        $jumlah = (float) $data['jumlah'];
        $harga  = (float) ($data['harga_satuan'] ?? 0);

        $urutan = (int) DB::table('laboran_usulan_pengadaan_item')->where('usulan_id', $id)->max('urutan');

        DB::table('laboran_usulan_pengadaan_item')->insert([
            'usulan_id'    => $id,
            'kebutuhan_id' => $data['kebutuhan_id'] ?? null,
            'nama_item'    => trim($data['nama_item']),
            'jenis'        => $data['jenis'],
            'spesifikasi'  => $data['spesifikasi'] ?? null,
            'jumlah'       => $jumlah,
            'satuan'       => $data['satuan'],
            'harga_satuan' => $harga,
            'subtotal'     => $jumlah * $harga,
            'prioritas'    => $data['prioritas'] ?? 'sedang',
            'keterangan'   => $data['keterangan'] ?? null,
            'urutan'       => $urutan + 1,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $this->rekapUsulan($id);

        return $this->respond($request, true, 'Item usulan berhasil ditambahkan.');
    }

    public function updateItem(Request $request, $id, $itemId)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $usulan = $this->findScoped('laboran_usulan_pengadaan', $id, $context);
        if (!$usulan) {
            return $this->respond($request, false, 'Usulan tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $data = $request->validate([
            'nama_item'    => 'required|string|max:255',
            'jenis'        => 'required|in:alat,bahan_habis_pakai',
            'spesifikasi'  => 'nullable|string|max:1000',
            'jumlah'       => 'required|numeric|min:0.01',
            'satuan'       => 'required|string|max:30',
            'harga_satuan' => 'nullable|numeric|min:0',
            'prioritas'    => 'nullable|in:rendah,sedang,tinggi,kritis',
            'keterangan'   => 'nullable|string|max:500',
        ]);

        $jumlah = (float) $data['jumlah'];
        $harga  = (float) ($data['harga_satuan'] ?? 0);

        DB::table('laboran_usulan_pengadaan_item')
            ->where('usulan_id', $id)
            ->where('id', $itemId)
            ->update([
                'nama_item'    => trim($data['nama_item']),
                'jenis'        => $data['jenis'],
                'spesifikasi'  => $data['spesifikasi'] ?? null,
                'jumlah'       => $jumlah,
                'satuan'       => $data['satuan'],
                'harga_satuan' => $harga,
                'subtotal'     => $jumlah * $harga,
                'prioritas'    => $data['prioritas'] ?? 'sedang',
                'keterangan'   => $data['keterangan'] ?? null,
                'updated_at'   => now(),
            ]);

        $this->rekapUsulan($id);

        return $this->respond($request, true, 'Item usulan berhasil diperbarui.');
    }

    public function hapusItem(Request $request, $id, $itemId)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $usulan = $this->findScoped('laboran_usulan_pengadaan', $id, $context);
        if (!$usulan) {
            return $this->respond($request, false, 'Usulan tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        DB::table('laboran_usulan_pengadaan_item')
            ->where('usulan_id', $id)
            ->where('id', $itemId)
            ->delete();

        $this->rekapUsulan($id);

        return $this->respond($request, true, 'Item usulan berhasil dihapus.');
    }

    /**
     * Tarik item dari analisis kebutuhan ke usulan.
     */
    protected function tarikKebutuhan($usulanId, array $kebutuhanIds, array $context): void
    {
        $q = DB::table('laboran_kebutuhan')->whereIn('id', $kebutuhanIds);
        LaboranScope::apply($q, 'jurusan_id', $context);
        $rows = $q->get();

        $urutan = (int) DB::table('laboran_usulan_pengadaan_item')->where('usulan_id', $usulanId)->max('urutan');

        foreach ($rows as $k) {
            $jumlah = (float) $k->selisih > 0 ? (float) $k->selisih : (float) $k->jumlah_kebutuhan;

            DB::table('laboran_usulan_pengadaan_item')->insert([
                'usulan_id'    => $usulanId,
                'kebutuhan_id' => $k->id,
                'nama_item'    => $k->nama_item,
                'jenis'        => $k->jenis,
                'spesifikasi'  => $k->spesifikasi,
                'jumlah'       => max(0.01, $jumlah),
                'satuan'       => $k->satuan,
                'harga_satuan' => 0,
                'subtotal'     => 0,
                'prioritas'    => $k->prioritas,
                'keterangan'   => 'Ditarik dari analisis kebutuhan',
                'urutan'       => $urutan++,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }

    /**
     * Hitung ulang total item & estimasi biaya.
     */
    protected function rekapUsulan($id): void
    {
        $items = DB::table('laboran_usulan_pengadaan_item')->where('usulan_id', $id)->get();

        DB::table('laboran_usulan_pengadaan')->where('id', $id)->update([
            'total_item'           => $items->count(),
            'total_estimasi_biaya' => $items->sum('subtotal'),
            'updated_at'           => now(),
        ]);
    }
}
