<?php

namespace App\Http\Controllers\Laboran;

use App\Services\LaboranPeminjamanImportService;
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
                'itemOptions'    => $this->itemOptions($context, 'alat', true),
                'asetOptions'    => $this->asetOptions($context),
                'lokasiOptions'  => $this->lokasiOptions(),
                'statusList'     => self::STATUS,
                'tipeList'       => self::PEMINJAM_TIPE,
                'kondisiList'    => self::KONDISI,
                'jurusanOptions' => LaboranScope::jurusanOptions($context),
            ]
        ));
    }

    // =========================================================================
    // CETAK DOKUMEN A4 (Ringkasan / Lengkap)
    // =========================================================================

    /**
     * Dokumen cetak A4 Kartu Peminjaman Alat.
     *
     * Mode `ringkasan` → rekap sirkulasi per status + daftar peminjaman aktif.
     * Mode `lengkap`   → rekap + rincian seluruh peminjaman sesuai filter.
     */
    public function cetak(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) {
            return $res;
        }

        $search       = trim((string) $request->query('q', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $tipeFilter   = trim((string) $request->query('tipe', ''));
        $overdueOnly  = $request->boolean('overdue');

        $mode      = $this->modeCetak($request, 'ringkasan');
        $orientasi = $this->orientasiCetak($request, 'landscape');

        $query = DB::table('laboran_peminjaman')->orderByRaw("CASE status
                WHEN 'diajukan' THEN 1
                WHEN 'terlambat' THEN 2
                WHEN 'dipinjam' THEN 3
                WHEN 'disetujui' THEN 4
                ELSE 5 END ASC")
            ->orderByDesc('tanggal_pinjam')
            ->orderByDesc('id');

        LaboranScope::apply($query, 'jurusan_id', $context);

        if ($search !== '') {
            $query->where(function ($qb) use ($search) {
                $qb->where('nomor_pinjam', 'like', "%{$search}%")
                   ->orWhere('peminjam_nama', 'like', "%{$search}%")
                   ->orWhere('keperluan', 'like', "%{$search}%")
                   ->orWhere('peminjam_kelas', 'like', "%{$search}%");
            });
        }
        if ($statusFilter !== '') $query->where('status', $statusFilter);
        if ($tipeFilter !== '')   $query->where('peminjam_tipe', $tipeFilter);

        if ($overdueOnly) {
            $query->whereIn('status', ['dipinjam', 'terlambat'])
                  ->whereNotNull('tanggal_kembali_rencana')
                  ->where('tanggal_kembali_rencana', '<', now()->toDateString());
        }

        $items = $query->get();

        $rekapStatus = $items->groupBy('status')->map(function ($g, $s) {
            return (object) [
                'status' => $s,
                'label'  => self::STATUS[$s] ?? ucfirst((string) $s),
                'jumlah' => $g->count(),
                'item'   => (int) $g->sum('jumlah_item'),
            ];
        })->sortByDesc('jumlah')->values();

        $rincian = collect();
        if ($mode === 'lengkap' && $items->isNotEmpty()) {
            $rincian = DB::table('laboran_peminjaman_item as i')
                ->join('laboran_peminjaman as p', 'i.peminjaman_id', '=', 'p.id')
                ->whereIn('i.peminjaman_id', $items->pluck('id')->all())
                ->orderBy('p.tanggal_pinjam')
                ->orderBy('i.id')
                ->get([
                    'p.nomor_pinjam', 'p.tanggal_pinjam', 'p.peminjam_nama',
                    'i.nama_item', 'i.kode_item', 'i.jumlah', 'i.satuan',
                    'i.kondisi_pinjam', 'i.kondisi_kembali', 'i.keterangan',
                ]);
        }

        $totalTerlambat = $items->whereIn('status', ['dipinjam', 'terlambat'])
            ->filter(fn ($r) => $r->tanggal_kembali_rencana
                && $r->tanggal_kembali_rencana < now()->toDateString())
            ->count();

        $filterLabel = [];
        if ($statusFilter !== '') $filterLabel[] = 'Status: ' . (self::STATUS[$statusFilter] ?? $statusFilter);
        if ($tipeFilter !== '')   $filterLabel[] = 'Peminjam: ' . (self::PEMINJAM_TIPE[$tipeFilter] ?? $tipeFilter);
        if ($overdueOnly)         $filterLabel[] = 'Hanya yang melewati tenggat';
        if ($search !== '')       $filterLabel[] = 'Pencarian: "' . $search . '"';

        return view('dashboard.laboran.peminjaman-cetak', array_merge(
            [
                'items'          => $items,
                'rekapStatus'    => $rekapStatus,
                'rincian'        => $rincian,
                'totalTerlambat' => $totalTerlambat,
                'filterLabel'    => $filterLabel,
                'statusList'     => self::STATUS,
                'tipeList'       => self::PEMINJAM_TIPE,
                'kondisiList'    => self::KONDISI,
                'backUrl'        => route('dashboard.laboran.peminjaman-alat.index'),
            ],
            $this->dokumenCetak('peminjaman', $context, [
                'mode'         => $mode,
                'orientasi'    => $orientasi,
                'jumlah_baris' => $items->count(),
                'filter'       => [
                    'q'       => $search,
                    'status'  => $statusFilter,
                    'tipe'    => $tipeFilter,
                    'overdue' => $overdueOnly ? '1' : '',
                ],
            ])
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
            'peminjam_nama'           => 'nullable|string|max:150',
            'peminjam_kelas'          => 'nullable|string|max:100',
            'keperluan'               => 'required|string|max:500',
            'ruang_lab_nama'          => 'nullable|string|max:100',
            'tanggal_pinjam'          => 'required|date',
            'tanggal_kembali_rencana' => 'required|date|after_or_equal:tanggal_pinjam',
            'catatan'                 => 'nullable|string|max:1000',
            'jurusan_id'              => 'nullable|string|max:50',
            'item_ids'                => 'nullable|array',
            'item_ids.*'              => 'integer',
            'item_jumlah'             => 'nullable|array',
            'item_jumlah.*'           => 'integer|min:1',
            'aset_ids'                => 'nullable|array',
            'aset_ids.*'              => 'integer',
            'aset_jumlah'             => 'nullable|array',
            'aset_jumlah.*'           => 'integer|min:1',
        ], [
            'tanggal_kembali_rencana.after_or_equal' => 'Tanggal kembali tidak boleh sebelum tanggal pinjam.',
            'item_jumlah.*.integer' => 'Jumlah alat harus berupa bilangan bulat.',
            'item_jumlah.*.min'     => 'Jumlah alat minimal 1.',
            'aset_jumlah.*.integer' => 'Jumlah aset harus berupa bilangan bulat.',
            'aset_jumlah.*.min'     => 'Jumlah aset minimal 1.',
        ]);

        // Otomatisasi data peminjam berdasarkan ID terpilih
        $peminjamNama = trim((string) ($data['peminjam_nama'] ?? ''));
        $peminjamKelas = trim((string) ($data['peminjam_kelas'] ?? ''));
        $peminjamId = trim((string) ($data['peminjam_id'] ?? ''));

        if ($data['peminjam_tipe'] === 'siswa' && $peminjamId !== '') {
            $siswa = DB::table('peserta_didik as pd')
                ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->where('pd.peserta_didik_id', $peminjamId)
                ->first(['pd.nama', 'rb.nama as rombel_nama']);
            if ($siswa) {
                $peminjamNama = $siswa->nama;
                $peminjamKelas = $siswa->rombel_nama ?: $peminjamKelas;
            }
        } elseif (in_array($data['peminjam_tipe'], ['guru', 'tendik'], true) && $peminjamId !== '') {
            $gtk = DB::table('gtk')->where('ptk_id', $peminjamId)->first(['nama', 'nip', 'jenis_ptk_id_str']);
            if ($gtk) {
                $peminjamNama = $gtk->nama;
                $peminjamKelas = $gtk->nip ? 'NIP. ' . $gtk->nip : ($gtk->jenis_ptk_id_str ?: $peminjamKelas);
            }
        }

        if ($peminjamNama === '') {
            return $this->respond($request, false, 'Nama peminjam wajib diisi atau dipilih dari daftar.', 422);
        }

        $labItemIds = $data['item_ids'] ?? [];
        $asetIds    = $data['aset_ids'] ?? [];

        if (empty($labItemIds) && empty($asetIds)) {
            return $this->respond($request, false, 'Pilih minimal satu alat yang akan dipinjam.', 422);
        }

        // Validasi item inventaris lab (hanya alat milik jurusan laboran)
        $labItems = collect();
        if (!empty($labItemIds)) {
            $labItems = DB::table('laboran_bahan_alat')->whereIn('id', $labItemIds)->get();
            $jumlahMap = $data['item_jumlah'] ?? [];
            foreach ($labItems as $item) {
                if (!LaboranScope::allows($item->jurusan_id ?? null, $context)) {
                    return $this->respond($request, false, 'Alat "' . $item->nama_item . '" berada di luar cakupan jurusan Anda.', 422);
                }

                $minta = (int) ($jumlahMap[$item->id] ?? 1);
                if ($minta > (int) $item->stok_tersedia) {
                    return $this->respond($request, false,
                        'Stok "' . $item->nama_item . '" tidak cukup. Tersedia: ' . (int) $item->stok_tersedia . ' ' . $item->satuan . '.', 422);
                }
            }
        }

        // Validasi aset Sarpras (seluruh aset dalam cakupan ditampilkan, tetap dijaga cakupan jurusan)
        $asetItems = collect();
        if (!empty($asetIds)) {
            $asetItems = DB::table('sarpras_aset')->whereIn('id', $asetIds)->get();
            $asetJumlahMap = $data['aset_jumlah'] ?? [];
            $dipinjamMap = $this->unitDipinjamAset($asetItems->pluck('id')->all());
            foreach ($asetItems as $aset) {
                if (!$this->asetDalamCakupan($aset->jurusan_id ?? null, $context)) {
                    return $this->respond($request, false, 'Aset "' . $aset->nama_barang . '" berada di luar cakupan jurusan Anda.', 422);
                }

                if (in_array($aset->status_ketersediaan ?? 'tersedia', ['dipinjam', 'hilang'], true)) {
                    return $this->respond($request, false,
                        'Aset "' . $aset->nama_barang . '" sedang tidak tersedia untuk dipinjam.', 422);
                }

                $minta = (int) ($asetJumlahMap[$aset->id] ?? 1);
                $tersedia = $this->unitTersediaAset($aset, $dipinjamMap);
                if ($tersedia <= 0) {
                    return $this->respond($request, false,
                        'Aset "' . $aset->nama_barang . '" tidak memiliki unit tersedia untuk dipinjam.', 422);
                }

                if ($minta > $tersedia) {
                    $satuan = $aset->satuan ?: 'unit';
                    return $this->respond($request, false,
                        'Jumlah "' . $aset->nama_barang . '" melebihi unit tersedia (tersisa '
                        . rtrim(rtrim(number_format($tersedia, 2, '.', ''), '0'), '.') . ' ' . $satuan . ').', 422);
                }
            }
        }

        $totalItem = $labItems->count() + $asetItems->count();

        $id = DB::table('laboran_peminjaman')->insertGetId([
            'jurusan_id'              => LaboranScope::jurusanForWrite($data['jurusan_id'] ?? null, $context),
            'ruang_lab_nama'          => $data['ruang_lab_nama'] ?? null,
            'nomor_pinjam'            => $this->generateNomor('laboran_peminjaman', 'nomor_pinjam', 'PJM'),
            'peminjam_tipe'           => $data['peminjam_tipe'],
            'peminjam_id'             => $peminjamId !== '' ? $peminjamId : null,
            'peminjam_nama'           => $peminjamNama,
            'peminjam_kelas'          => $peminjamKelas !== '' ? $peminjamKelas : null,
            'keperluan'               => trim($data['keperluan']),
            'tanggal_pinjam'          => $data['tanggal_pinjam'],
            'tanggal_kembali_rencana' => $data['tanggal_kembali_rencana'],
            'jumlah_item'             => $totalItem,
            'status'                  => 'diajukan',
            'petugas_ptk_id'          => $context['ptk_id'],
            'catatan'                 => $data['catatan'] ?? null,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);

        $jumlahMap = $data['item_jumlah'] ?? [];
        foreach ($labItems as $item) {
            $minta = (int) ($jumlahMap[$item->id] ?? 1);

            DB::table('laboran_peminjaman_item')->insert([
                'peminjaman_id'  => $id,
                'item_id'        => $item->id,
                'sumber'         => 'inventaris',
                'aset_id'        => null,
                'nama_item'      => $item->nama_item,
                'kode_item'      => $item->kode_item,
                'jumlah'         => $minta,
                'satuan'         => $item->satuan,
                'kondisi_pinjam' => $item->kondisi,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        $asetJumlahMap = $data['aset_jumlah'] ?? [];
        foreach ($asetItems as $aset) {
            $minta = (int) ($asetJumlahMap[$aset->id] ?? 1);

            DB::table('laboran_peminjaman_item')->insert([
                'peminjaman_id'  => $id,
                'item_id'        => null,
                'sumber'         => 'aset',
                'aset_id'        => $aset->id,
                'nama_item'      => $aset->nama_barang,
                'kode_item'      => $aset->kode_aset,
                'jumlah'         => $minta,
                'satuan'         => $aset->satuan ?: 'unit',
                'kondisi_pinjam' => $aset->kondisi,
                'created_at'     => now(),
                'updated_at'     => now(),
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
        $asetTersentuh = [];

        // Serah terima: kurangi stok tersedia
        if ($baru === 'dipinjam' && !in_array($lama, ['dipinjam', 'terlambat'], true)) {
            $items = DB::table('laboran_peminjaman_item')->where('peminjaman_id', $id)->get();

            foreach ($items as $it) {
                if ($it->item_id) {
                    $stok = (float) DB::table('laboran_bahan_alat')->where('id', $it->item_id)->value('stok_tersedia');
                    $sisa = max(0, $stok - (float) $it->jumlah);

                    DB::table('laboran_bahan_alat')->where('id', $it->item_id)->update([
                        'stok_tersedia' => $sisa,
                        'updated_at'    => now(),
                    ]);
                } elseif (!empty($it->aset_id)) {
                    $asetTersentuh[] = (int) $it->aset_id;
                }
            }
        }

        // Pengembalian: kembalikan stok & catat kondisi
        if ($baru === 'dikembalikan' && !in_array($lama, ['dikembalikan'], true)) {
            $items = DB::table('laboran_peminjaman_item')->where('peminjaman_id', $id)->get();

            foreach ($items as $it) {
                if ($it->item_id) {
                    $stok = (float) DB::table('laboran_bahan_alat')->where('id', $it->item_id)->value('stok_tersedia');

                    DB::table('laboran_bahan_alat')->where('id', $it->item_id)->update([
                        'stok_tersedia' => $stok + (float) $it->jumlah,
                        'updated_at'    => now(),
                    ]);
                } elseif (!empty($it->aset_id)) {
                    $asetTersentuh[] = (int) $it->aset_id;
                }
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

        // Sinkronkan status aset setelah status header berubah, agar unit yang
        // ditahan/dilepas tercermin pada ketersediaan aset.
        foreach (array_unique($asetTersentuh) as $asetId) {
            $this->syncStatusAset($asetId);
        }

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
        $asetTersentuh = [];

        foreach ($items as $it) {
            if ($it->item_id) {
                // Kembalikan stok hanya bila alat tidak hilang
                if ($data['kondisi_kembali'] !== 'hilang') {
                    $stok = (float) DB::table('laboran_bahan_alat')->where('id', $it->item_id)->value('stok_tersedia');

                    DB::table('laboran_bahan_alat')->where('id', $it->item_id)->update([
                        'stok_tersedia' => $stok + (float) $it->jumlah,
                        'updated_at'    => now(),
                    ]);
                }

                // Sesuaikan kondisi inventaris bila kembali dalam keadaan rusak
                if (in_array($data['kondisi_kembali'], ['rusak_ringan', 'rusak_berat'], true)) {
                    DB::table('laboran_bahan_alat')->where('id', $it->item_id)->update([
                        'kondisi'    => $data['kondisi_kembali'],
                        'updated_at' => now(),
                    ]);
                }
            } elseif (!empty($it->aset_id)) {
                $asetTersentuh[] = (int) $it->aset_id;
                DB::table('sarpras_aset')->where('id', $it->aset_id)->update([
                    'status_ketersediaan' => $data['kondisi_kembali'] === 'hilang' ? 'hilang' : 'tersedia',
                    'kondisi'             => in_array($data['kondisi_kembali'], ['rusak_ringan', 'rusak_berat'], true) ? $data['kondisi_kembali'] : 'baik',
                    'updated_at'          => now(),
                ]);
            }

            DB::table('laboran_peminjaman_item')->where('id', $it->id)->update([
                'kondisi_kembali' => $data['kondisi_kembali'],
                'updated_at'      => now(),
            ]);
        }

        DB::table('laboran_peminjaman')->where('id', $id)->update([
            'status'                => 'dikembalikan',
            'tanggal_kembali_aktual' => now()->toDateString(),
            'kondisi_kembali'       => $data['kondisi_kembali'],
            'catatan_pengembalian'  => $data['catatan_pengembalian'] ?? null,
            'petugas_ptk_id'        => $context['ptk_id'],
            'updated_at'            => now(),
        ]);

        // Unit sudah dilepas: hitung ulang ketersediaan aset (aset "hilang"
        // tetap hilang sesuai kondisi pengembalian).
        foreach (array_unique($asetTersentuh) as $asetId) {
            if ($data['kondisi_kembali'] !== 'hilang') {
                $this->syncStatusAset($asetId);
            }
        }

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
            $asetTersentuh = [];
            foreach ($items as $it) {
                if ($it->item_id) {
                    $stok = (float) DB::table('laboran_bahan_alat')->where('id', $it->item_id)->value('stok_tersedia');
                    DB::table('laboran_bahan_alat')->where('id', $it->item_id)->update([
                        'stok_tersedia' => $stok + (float) $it->jumlah,
                        'updated_at'    => now(),
                    ]);
                } elseif (!empty($it->aset_id)) {
                    $asetTersentuh[] = (int) $it->aset_id;
                }
            }
        }

        DB::table('laboran_peminjaman_item')->where('peminjaman_id', $id)->delete();
        DB::table('laboran_peminjaman')->where('id', $id)->delete();

        // Baris item sudah dihapus, jadi unit otomatis lepas: hitung ulang.
        foreach (array_unique($asetTersentuh ?? []) as $asetId) {
            $this->syncStatusAset($asetId);
        }

        return $this->respond($request, true, 'Data peminjaman berhasil dihapus.');
    }

    /**
     * Jumlah unit aset yang sedang dipinjam pada peminjaman aktif.
     *
     * @param  array<int,int|string>  $asetIds
     * @return array<int,float>  aset_id => total unit dipinjam
     */
    protected function unitDipinjamAset(array $asetIds): array
    {
        if (empty($asetIds)) {
            return [];
        }

        return DB::table('laboran_peminjaman_item as lpi')
            ->join('laboran_peminjaman as lp', 'lpi.peminjaman_id', '=', 'lp.id')
            ->whereIn('lpi.aset_id', $asetIds)
            ->whereIn('lp.status', $this->statusMenahanUnit())
            ->groupBy('lpi.aset_id')
            ->selectRaw('lpi.aset_id, SUM(lpi.jumlah) as total')
            ->pluck('total', 'lpi.aset_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /**
     * Status peminjaman yang unitnya masih dianggap terpakai (belum kembali).
     *
     * @return array<int,string>
     */
    protected function statusMenahanUnit(): array
    {
        return ['disetujui', 'dipinjam', 'terlambat'];
    }

    /**
     * Hitung unit aset yang masih dapat dipinjam.
     *
     * Aset Sarpras memuat banyak unit (kolom `jumlah`), sehingga peminjaman
     * sebagian unit TIDAK boleh mengunci seluruh aset. Unit tersedia =
     * jumlah - rusak ringan - rusak berat - unit yang sedang dipinjam.
     * Nilai `jumlah` yang tidak diisi (null/0) dianggap tidak diketahui dan
     * hanya mengandalkan `status_ketersediaan`.
     */
    protected function unitTersediaAset(object $aset, array $dipinjamMap): float
    {
        $jumlah = (float) ($aset->jumlah ?? 0);
        if ($jumlah <= 0) {
            return 0;
        }

        $rusak = (float) ($aset->jumlah_rusak_ringan ?? 0) + (float) ($aset->jumlah_rusak_berat ?? 0);
        $dipakai = (float) ($dipinjamMap[$aset->id] ?? 0);

        return max(0, $jumlah - $rusak - $dipakai);
    }

    /**
     * Daftar aset Sarpras dalam cakupan jurusan (untuk pilihan alat pinjam).
     *
     * Aset Sarpras dipakai sebagai sumber alat yang dapat dipinjam selain
     * inventaris laboratorium. Seluruh aset dalam cakupan ditampilkan, beserta
     * jumlah unit yang masih tersedia agar peminjaman sebagian tetap terbuka.
     */
    protected function asetOptions(array $context)
    {
        $q = DB::table('sarpras_aset as sa')
            ->leftJoin('sarpras_ruang as sr', 'sa.ruang_id', '=', 'sr.id')
            ->select(
                'sa.id',
                'sa.kode_aset',
                'sa.nama_barang',
                'sa.kategori',
                'sa.merk_tipe',
                'sa.kondisi',
                'sa.jumlah',
                'sa.jumlah_rusak_ringan',
                'sa.jumlah_rusak_berat',
                'sa.satuan',
                'sa.status_ketersediaan',
                'sa.jurusan_id',
                'sr.nama_ruang as ruang_nama'
            )
            ->orderBy('sa.nama_barang');

        // Aset milik jurusan lain disembunyikan; aset tanpa jurusan (umum sekolah)
        // tetap tampil karena dapat dipinjam lintas jurusan.
        if (!$context['unrestricted']) {
            $ids = $context['jurusan_ids'];
            $q->where(function ($w) use ($ids) {
                $w->whereNull('sa.jurusan_id')->orWhere('sa.jurusan_id', '');
                if (!empty($ids)) {
                    $w->orWhereIn('sa.jurusan_id', $ids);
                }
            });
        }

        $rows = $q->get();
        $dipinjamMap = $this->unitDipinjamAset($rows->pluck('id')->all());

        foreach ($rows as $row) {
            $row->unit_dipinjam = (float) ($dipinjamMap[$row->id] ?? 0);
            $row->unit_tersedia = $this->unitTersediaAset($row, $dipinjamMap);
        }

        return $rows;
    }

    /**
     * Apakah aset berada dalam cakupan jurusan pengguna?
     * Aset tanpa jurusan dianggap milik umum sekolah sehingga boleh dipinjam.
     */
    protected function asetDalamCakupan(?string $asetJurusanId, array $context): bool
    {
        if ($context['unrestricted']) {
            return true;
        }

        if ($asetJurusanId === null || trim($asetJurusanId) === '') {
            return true;
        }

        return in_array($asetJurusanId, $context['jurusan_ids'], true);
    }

    /**
     * Sinkronkan `status_ketersediaan` aset setelah unit dipinjam/dikembalikan.
     *
     * Status hanya menjadi `dipinjam` bila seluruh unit habis terpakai, agar
     * peminjaman sebagian tidak mengunci aset untuk peminjam lain. Status
     * `hilang` tidak ditimpa.
     */
    protected function syncStatusAset(int $asetId): void
    {
        $aset = DB::table('sarpras_aset')->where('id', $asetId)->first();
        if (!$aset) {
            return;
        }

        if (($aset->status_ketersediaan ?? '') === 'hilang') {
            return;
        }

        $dipinjamMap = $this->unitDipinjamAset([$asetId]);
        $tersedia = $this->unitTersediaAset($aset, $dipinjamMap);

        DB::table('sarpras_aset')->where('id', $asetId)->update([
            'status_ketersediaan' => $tersedia > 0 ? 'tersedia' : 'dipinjam',
            'updated_at'          => now(),
        ]);
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

    /**
     * Unduh format berkas Excel untuk import peminjaman alat.
     */
    public function templateExcel(?LaboranPeminjamanImportService $service = null)
    {
        $service = $service ?? app(LaboranPeminjamanImportService::class);
        $path = $service->generateTemplateFile();

        return response()
            ->download($path, 'Format_Import_Peminjaman_Alat.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * Import peminjaman alat via Excel (.xlsx, .xls, .csv).
     *
     * Satu baris = satu alat. Baris dengan nomor_pinjam yang sama digabung
     * menjadi satu dokumen peminjaman (multi item).
     */
    public function importExcel(Request $request, LaboranPeminjamanImportService $service)
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

        $pesan = "Import selesai: {$hasil['imported']} peminjaman baru ditambahkan, {$hasil['updated']} diperbarui.";
        if (!empty($hasil['skipped'])) {
            $pesan .= " {$hasil['skipped']} baris dilewati (data tidak lengkap / di luar cakupan jurusan).";
        }

        return response()->json([
            'status'  => 'success',
            'message' => $pesan,
            'count'   => $hasil['imported'] + $hasil['updated'],
            'skipped' => $hasil['skipped'] ?? 0,
        ]);
    }
}
