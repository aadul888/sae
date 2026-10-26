<?php

namespace App\Http\Controllers\Laboran;

use App\Support\LaboranScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Modul Peminjaman Alat Khusus Jurusan / Laboran.
 *
 * Alur: ajukan pinjam -> setujui -> serah terima (dipinjam) ->
 *       pengembalian (cek kondisi) -> selesai.
 * Stok tersedia pada inventaris otomatis berkurang/bertambah.
 */
class LaboranPeminjamanController extends LaboranBaseController
{
    public const STATUS = [
        'diajukan'     => 'Diajukan',
        'disetujui'    => 'Disetujui',
        'dipinjam'     => 'Dipinjam',
        'dikembalikan' => 'Dikembalikan',
        'terlambat'    => 'Terlambat',
        'ditolak'      => 'Ditolak',
    ];

    public const PEMINJAM_TIPE = [
        'siswa'  => 'Peserta Didik',
        'guru'   => 'Guru',
        'tendik' => 'Tenaga Kependidikan',
        'umum'   => 'Umum / Eksternal',
    ];

    public const KONDISI = [
        'baik'         => 'Baik',
        'rusak_ringan' => 'Rusak Ringan',
        'rusak_berat'  => 'Rusak Berat',
        'hilang'       => 'Hilang',
    ];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $search        = trim((string) $request->query('q', ''));
        $statusFilter  = trim((string) $request->query('status', ''));
        $tipeFilter    = trim((string) $request->query('tipe', ''));
        $overdueOnly   = $request->boolean('overdue');

        $query = DB::table('laboran_peminjaman as p')
            ->leftJoin('jurusan_meta as jm', 'p.jurusan_id', '=', 'jm.jurusan_id')
            ->select('p.*', 'jm.nama_jurusan', 'jm.singkatan');

        LaboranScope::apply($query, 'p.jurusan_id', $context);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('p.nomor_pinjam', 'like', "%{$search}%")
                  ->orWhere('p.peminjam_nama', 'like', "%{$search}%")
                  ->orWhere('p.keperluan', 'like', "%{$search}%")
                  ->orWhere('p.peminjam_kelas', 'like', "%{$search}%");
            });
        }
        if ($statusFilter !== '') $query->where('p.status', $statusFilter);
        if ($tipeFilter !== '')   $query->where('p.peminjam_tipe', $tipeFilter);

        if ($overdueOnly) {
            $query->whereIn('p.status', ['dipinjam', 'terlambat'])
                  ->whereNotNull('p.tanggal_kembali_rencana')
                  ->where('p.tanggal_kembali_rencana', '<', now()->toDateString());
        }

        $statBase = DB::table('laboran_peminjaman');
        LaboranScope::apply($statBase, 'jurusan_id', $context);

        $statTotal     = (clone $statBase)->count();
        $statDiajukan  = (clone $statBase)->where('status', 'diajukan')->count();
        $statDipinjam  = (clone $statBase)->whereIn('status', ['dipinjam', 'terlambat'])->count();
        $statTerlambat = (clone $statBase)->whereIn('status', ['dipinjam', 'terlambat'])
            ->whereNotNull('tanggal_kembali_rencana')
            ->where('tanggal_kembali_rencana', '<', now()->toDateString())
            ->count();

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $list = $query->orderByRaw("CASE p.status
                WHEN 'diajukan' THEN 1
                WHEN 'terlambat' THEN 2
                WHEN 'dipinjam' THEN 3
                WHEN 'disetujui' THEN 4
                ELSE 5 END ASC")
            ->orderByDesc('p.tanggal_pinjam')
            ->orderByDesc('p.id')
            ->paginate($perPage)
            ->withQueryString();

        return view('dashboard.laboran.peminjaman', array_merge(
            compact('list', 'search', 'statusFilter', 'tipeFilter', 'overdueOnly', 'perPage',
                'statTotal', 'statDiajukan', 'statDipinjam', 'statTerlambat'),
            $this->abilities(),
            [
                'context'        => $context,
                'scopeBadge'     => LaboranScope::badge($context),
                'itemOptions'    => $this->itemOptions($context, 'alat'),
                'lokasiOptions'  => $this->lokasiOptions(),
                'statusList'     => self::STATUS,
                'tipeList'       => self::PEMINJAM_TIPE,
                'kondisiList'    => self::KONDISI,
                'jurusanOptions' => LaboranScope::jurusanOptions($context),
            ]
        ));
    }

    public function show(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $peminjaman = $this->findScoped('laboran_peminjaman', $id, $context);
        if (!$peminjaman) {
            abort(404, 'Data peminjaman tidak ditemukan atau di luar cakupan jurusan Anda.');
        }

        $items = DB::table('laboran_peminjaman_item')
            ->where('peminjaman_id', $id)
            ->orderBy('id')
            ->get();

        $jurusan = $peminjaman->jurusan_id
            ? DB::table('jurusan_meta')->where('jurusan_id', $peminjaman->jurusan_id)->first()
            : null;

        return view('dashboard.laboran.peminjaman-detail', array_merge(
            compact('peminjaman', 'items', 'jurusan'),
            $this->abilities(),
            [
                'context'     => $context,
                'scopeBadge'  => LaboranScope::badge($context),
                'statusList'  => self::STATUS,
                'tipeList'    => self::PEMINJAM_TIPE,
                'kondisiList' => self::KONDISI,
                'itemOptions' => $this->itemOptions($context, 'alat'),
            ]
        ));
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $request->validate([
            'peminjam_tipe'           => 'required|in:siswa,guru,tendik,umum',
            'peminjam_id'             => 'nullable|string|max:50',
            'peminjam_nama'           => 'required|string|max:150',
            'peminjam_kelas'          => 'nullable|string|max:100',
            'keperluan'               => 'required|string|max:500',
            'ruang_lab_nama'          => 'nullable|string|max:100',
            'tanggal_pinjam'          => 'required|date',
            'tanggal_kembali_rencana' => 'required|date|after_or_equal:tanggal_pinjam',
            'catatan'                 => 'nullable|string|max:1000',
            'jurusan_id'              => 'nullable|string|max:50',
            'item_ids'                => 'required|array|min:1',
            'item_ids.*'              => 'integer|exists:laboran_bahan_alat,id',
            'item_jumlah'             => 'nullable|array',
            'item_jumlah.*'           => 'numeric|min:0.01',
        ], [
            'item_ids.required'                 => 'Pilih minimal satu alat yang akan dipinjam.',
            'tanggal_kembali_rencana.after_or_equal' => 'Tanggal kembali tidak boleh sebelum tanggal pinjam.',
        ]);

        // Validasi item berada dalam cakupan & stok cukup
        $items = DB::table('laboran_bahan_alat')->whereIn('id', $data['item_ids'])->get();

        if ($items->isEmpty()) {
            return $this->respond($request, false, 'Alat yang dipilih tidak ditemukan.', 422);
        }

        $jumlahMap = $data['item_jumlah'] ?? [];
        foreach ($items as $item) {
            if (!LaboranScope::allows($item->jurusan_id ?? null, $context)) {
                return $this->respond($request, false, 'Alat "' . $item->nama_item . '" berada di luar cakupan jurusan Anda.', 422);
            }

            $minta = (float) ($jumlahMap[$item->id] ?? 1);
            if ($minta > (float) $item->stok_tersedia) {
                return $this->respond($request, false,
                    'Stok "' . $item->nama_item . '" tidak cukup. Tersedia: ' . $item->stok_tersedia . ' ' . $item->satuan . '.', 422);
            }
        }

        $id = DB::table('laboran_peminjaman')->insertGetId([
            'jurusan_id'              => LaboranScope::jurusanForWrite($data['jurusan_id'] ?? null, $context),
            'ruang_lab_nama'          => $data['ruang_lab_nama'] ?? null,
            'nomor_pinjam'            => $this->generateNomor('laboran_peminjaman', 'nomor_pinjam', 'PJM'),
            'peminjam_tipe'           => $data['peminjam_tipe'],
            'peminjam_id'             => $data['peminjam_id'] ?? null,
            'peminjam_nama'           => trim($data['peminjam_nama']),
            'peminjam_kelas'          => $data['peminjam_kelas'] ?? null,
            'keperluan'               => trim($data['keperluan']),
            'tanggal_pinjam'          => $data['tanggal_pinjam'],
            'tanggal_kembali_rencana' => $data['tanggal_kembali_rencana'],
            'jumlah_item'             => $items->count(),
            'status'                  => 'diajukan',
            'petugas_ptk_id'          => $context['ptk_id'],
            'catatan'                 => $data['catatan'] ?? null,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);

        foreach ($items as $item) {
            $minta = (float) ($jumlahMap[$item->id] ?? 1);

            DB::table('laboran_peminjaman_item')->insert([
                'peminjaman_id' => $id,
                'item_id'       => $item->id,
                'nama_item'     => $item->nama_item,
                'kode_item'     => $item->kode_item,
                'jumlah'        => $minta,
                'satuan'        => $item->satuan,
                'kondisi_pinjam' => $item->kondisi,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        return $this->respond($request, true, 'Pengajuan peminjaman alat berhasil dibuat.', 200, [
            'redirect' => route('dashboard.laboran.peminjaman-alat.show', $id),
            'id'       => $id,
        ]);
    }

    /**
     * Ubah status peminjaman (setujui / tolak / serah terima).
     */
    public function updateStatus(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_peminjaman', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Data peminjaman tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $data = $request->validate([
            'status' => 'required|in:diajukan,disetujui,dipinjam,dikembalikan,terlambat,ditolak',
            'catatan' => 'nullable|string|max:1000',
        ]);

        $baru = $data['status'];
        $lama = $row->status;

        // Serah terima: kurangi stok tersedia
        if ($baru === 'dipinjam' && !in_array($lama, ['dipinjam', 'terlambat'], true)) {
            $items = DB::table('laboran_peminjaman_item')->where('peminjaman_id', $id)->get();

            foreach ($items as $it) {
                if (!$it->item_id) continue;

                $stok = (float) DB::table('laboran_bahan_alat')->where('id', $it->item_id)->value('stok_tersedia');
                $sisa = max(0, $stok - (float) $it->jumlah);

                DB::table('laboran_bahan_alat')->where('id', $it->item_id)->update([
                    'stok_tersedia' => $sisa,
                    'updated_at'    => now(),
                ]);
            }
        }

        // Pengembalian: kembalikan stok & catat kondisi
        if ($baru === 'dikembalikan' && !in_array($lama, ['dikembalikan'], true)) {
            $items = DB::table('laboran_peminjaman_item')->where('peminjaman_id', $id)->get();

            foreach ($items as $it) {
                if (!$it->item_id) continue;

                $stok = (float) DB::table('laboran_bahan_alat')->where('id', $it->item_id)->value('stok_tersedia');

                DB::table('laboran_bahan_alat')->where('id', $it->item_id)->update([
                    'stok_tersedia' => $stok + (float) $it->jumlah,
                    'updated_at'    => now(),
                ]);
            }
        }

        $update = [
            'status'     => $baru,
            'updated_at' => now(),
        ];

        if ($baru === 'disetujui') {
            $update['disetujui_oleh_ptk_id'] = $context['ptk_id'];
        }

        if ($baru === 'dikembalikan') {
            $update['tanggal_kembali_aktual'] = now()->toDateString();
        }

        if (!empty($data['catatan'])) {
            $update['catatan'] = $data['catatan'];
        }

        DB::table('laboran_peminjaman')->where('id', $id)->update($update);

        return $this->respond($request, true, 'Status peminjaman berhasil diperbarui menjadi ' . (self::STATUS[$baru] ?? $baru) . '.');
    }

    /**
     * Proses pengembalian dengan pencatatan kondisi per item.
     */
    public function kembalikan(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_peminjaman', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Data peminjaman tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        if ($row->status === 'dikembalikan') {
            return $this->respond($request, false, 'Peminjaman ini sudah dikembalikan.', 422);
        }

        $data = $request->validate([
            'kondisi_kembali'   => 'required|in:baik,rusak_ringan,rusak_berat,hilang',
            'catatan_pengembalian' => 'nullable|string|max:1000',
        ]);

        $items = DB::table('laboran_peminjaman_item')->where('peminjaman_id', $id)->get();

        foreach ($items as $it) {
            if (!$it->item_id) continue;

            // Kembalikan stok hanya bila alat tidak hilang
            if ($data['kondisi_kembali'] !== 'hilang') {
                $stok = (float) DB::table('laboran_bahan_alat')->where('id', $it->item_id)->value('stok_tersedia');

                DB::table('laboran_bahan_alat')->where('id', $it->item_id)->update([
                    'stok_tersedia' => $stok + (float) $it->jumlah,
                    'updated_at'    => now(),
                ]);
            }

            DB::table('laboran_peminjaman_item')->where('id', $it->id)->update([
                'kondisi_kembali' => $data['kondisi_kembali'],
                'updated_at'      => now(),
            ]);

            // Sesuaikan kondisi inventaris bila kembali dalam keadaan rusak
            if (in_array($data['kondisi_kembali'], ['rusak_ringan', 'rusak_berat'], true)) {
                DB::table('laboran_bahan_alat')->where('id', $it->item_id)->update([
                    'kondisi'    => $data['kondisi_kembali'],
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('laboran_peminjaman')->where('id', $id)->update([
            'status'                => 'dikembalikan',
            'tanggal_kembali_aktual' => now()->toDateString(),
            'kondisi_kembali'       => $data['kondisi_kembali'],
            'catatan_pengembalian'  => $data['catatan_pengembalian'] ?? null,
            'petugas_ptk_id'        => $context['ptk_id'],
            'updated_at'            => now(),
        ]);

        return $this->respond($request, true, 'Pengembalian alat berhasil dicatat.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_peminjaman', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Data peminjaman tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        // Kembalikan stok bila masih dipinjam sebelum dihapus
        if (in_array($row->status, ['dipinjam', 'terlambat'], true)) {
            $items = DB::table('laboran_peminjaman_item')->where('peminjaman_id', $id)->get();
            foreach ($items as $it) {
                if (!$it->item_id) continue;
                $stok = (float) DB::table('laboran_bahan_alat')->where('id', $it->item_id)->value('stok_tersedia');
                DB::table('laboran_bahan_alat')->where('id', $it->item_id)->update([
                    'stok_tersedia' => $stok + (float) $it->jumlah,
                    'updated_at'    => now(),
                ]);
            }
        }

        DB::table('laboran_peminjaman_item')->where('peminjaman_id', $id)->delete();
        DB::table('laboran_peminjaman')->where('id', $id)->delete();

        return $this->respond($request, true, 'Data peminjaman berhasil dihapus.');
    }

    /**
     * Tandai peminjaman yang melewati tenggat sebagai terlambat.
     */
    public function tandaiTerlambat(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $q = DB::table('laboran_peminjaman')
            ->where('status', 'dipinjam')
            ->whereNotNull('tanggal_kembali_rencana')
            ->where('tanggal_kembali_rencana', '<', now()->toDateString());
        LaboranScope::apply($q, 'jurusan_id', $context);

        $jumlah = $q->update(['status' => 'terlambat', 'updated_at' => now()]);

        return $this->respond($request, true, $jumlah . ' peminjaman ditandai terlambat.');
    }
}
