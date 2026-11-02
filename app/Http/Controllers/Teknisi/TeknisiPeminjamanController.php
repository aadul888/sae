<?php

namespace App\Http\Controllers\Teknisi;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Peminjaman TEKNISI/SARPRAS — halaman sendiri, TERPISAH dari peminjaman
 * jurusan (Laboran). Sumber: RUANG (`sarpras_ruang`) + ASET (`sarpras_aset`).
 *
 * Cakupan data mengikuti PENANGGUNG JAWAB ruang (PJ), sama seperti jadwal
 * area tugas & pemeliharaan Teknisi. Koordinator (admin, KEPALA_TAS,
 * WAKA_SARPRAS, STAF_SARPRAS) melihat seluruh data.
 *
 * Tidak menyentuh sama sekali tabel `laboran_peminjaman` (milik jurusan).
 */
class TeknisiPeminjamanController extends TeknisiBaseController
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

        $search      = trim((string) $request->query('q', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $tipeFilter  = trim((string) $request->query('tipe', ''));
        $overdueOnly = $request->boolean('overdue');

        $query = DB::table('teknisi_peminjaman as p')->select('p.*');
        $this->scopeRuangPj($query, $context, 'p');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('p.nomor_pinjam', 'like', "%{$search}%")
                  ->orWhere('p.peminjam_nama', 'like', "%{$search}%")
                  ->orWhere('p.keperluan', 'like', "%{$search}%")
                  ->orWhere('p.lokasi', 'like', "%{$search}%");
            });
        }
        if ($statusFilter !== '') $query->where('p.status', $statusFilter);
        if ($tipeFilter !== '')   $query->where('p.peminjam_tipe', $tipeFilter);

        if ($overdueOnly) {
            $query->whereIn('p.status', ['dipinjam', 'terlambat'])
                  ->whereNotNull('p.tanggal_kembali_rencana')
                  ->where('p.tanggal_kembali_rencana', '<', now()->toDateString());
        }

        $statBase = fn () => $this->scopedQuery(DB::table('teknisi_peminjaman'), $context);

        $statTotal     = $statBase()->count();
        $statDiajukan  = $statBase()->where('status', 'diajukan')->count();
        $statDipinjam  = $statBase()->whereIn('status', ['dipinjam', 'terlambat'])->count();
        $statTerlambat = $statBase()->whereIn('status', ['dipinjam', 'terlambat'])
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

        return view('dashboard.teknisi.peminjaman', array_merge(
            compact('list', 'search', 'statusFilter', 'tipeFilter', 'overdueOnly', 'perPage',
                'statTotal', 'statDiajukan', 'statDipinjam', 'statTerlambat'),
            $this->abilities(),
            [
                'statusList'   => self::STATUS,
                'tipeList'     => self::PEMINJAM_TIPE,
                'kondisiList'  => self::KONDISI,
                'ruangOptions' => $this->ruangOptions($context),
                'asetOptions'  => $this->asetOptions($context),
            ]
        ));
    }

    public function show(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $peminjaman = $this->findScoped($id, $context);
        if (!$peminjaman) {
            abort(404, 'Data peminjaman tidak ditemukan atau di luar cakupan ruang Anda.');
        }

        $items = DB::table('teknisi_peminjaman_item')
            ->where('peminjaman_id', $id)
            ->orderBy('id')
            ->get();

        return view('dashboard.teknisi.peminjaman-detail', array_merge(
            compact('peminjaman', 'items'),
            $this->abilities(),
            [
                'statusList'  => self::STATUS,
                'tipeList'    => self::PEMINJAM_TIPE,
                'kondisiList' => self::KONDISI,
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
            'lokasi'                  => 'nullable|string|max:150',
            'tanggal_pinjam'          => 'required|date',
            'tanggal_kembali_rencana' => 'required|date|after_or_equal:tanggal_pinjam',
            'catatan'                 => 'nullable|string|max:1000',
            'ruang_ids'               => 'nullable|array',
            'ruang_ids.*'             => 'integer',
            'aset_ids'                => 'nullable|array',
            'aset_ids.*'              => 'integer',
            'aset_jumlah'             => 'nullable|array',
            'aset_jumlah.*'           => 'integer|min:1',
        ], [
            'tanggal_kembali_rencana.after_or_equal' => 'Tanggal kembali tidak boleh sebelum tanggal pinjam.',
            'aset_jumlah.*.min'     => 'Jumlah aset minimal 1.',
        ]);

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

        $ruangIds = array_values(array_unique($data['ruang_ids'] ?? []));
        $asetIds  = $data['aset_ids'] ?? [];

        if (empty($ruangIds) && empty($asetIds)) {
            return $this->respond($request, false, 'Pilih minimal satu ruang atau aset yang akan dipinjam.', 422);
        }

        // Validasi ruang dalam cakupan PJ (koordinator: bebas).
        $pjIds = $this->ruangMilikPj($context);
        $ruangTerpilih = collect();
        if (!empty($ruangIds)) {
            $ruangTerpilih = DB::table('sarpras_ruang')->whereIn('id', $ruangIds)->get();
            foreach ($ruangTerpilih as $r) {
                if (is_array($pjIds) && !in_array((int) $r->id, $pjIds, true)) {
                    return $this->respond($request, false, 'Ruang "' . $r->nama_ruang . '" di luar tanggung jawab Anda.', 422);
                }
            }
        }

        // Validasi aset + ketersediaan unit.
        $asetItems = collect();
        if (!empty($asetIds)) {
            $asetItems = DB::table('sarpras_aset')->whereIn('id', $asetIds)->get();
            $dipinjamMap = $this->unitDipinjamAset($asetItems->pluck('id')->all());
            $asetJumlahMap = $data['aset_jumlah'] ?? [];
            foreach ($asetItems as $aset) {
                if (is_array($pjIds) && $aset->ruang_id && !in_array((int) $aset->ruang_id, $pjIds, true)) {
                    return $this->respond($request, false, 'Aset "' . $aset->nama_barang . '" di luar tanggung jawab Anda.', 422);
                }
                if (in_array($aset->status_ketersediaan ?? 'tersedia', ['dipinjam', 'hilang'], true)) {
                    return $this->respond($request, false, 'Aset "' . $aset->nama_barang . '" sedang tidak tersedia untuk dipinjam.', 422);
                }
                $minta = (int) ($asetJumlahMap[$aset->id] ?? 1);
                $tersedia = $this->unitTersediaAset($aset, $dipinjamMap);
                if ($tersedia <= 0) {
                    return $this->respond($request, false, 'Aset "' . $aset->nama_barang . '" tidak memiliki unit tersedia.', 422);
                }
                if ($minta > $tersedia) {
                    return $this->respond($request, false, 'Jumlah "' . $aset->nama_barang . '" melebihi unit tersedia.', 422);
                }
            }
        }

        $pj = $this->pjUntukRuang((int) ($ruangTerpilih->first()->id ?? 0) ?: null, $context);

        $totalItem = $ruangTerpilih->count() + $asetItems->count();

        $id = DB::table('teknisi_peminjaman')->insertGetId([
            'nomor_pinjam'            => $this->generateNomor('teknisi_peminjaman', 'nomor_pinjam', 'PJM'),
            'peminjam_tipe'           => $data['peminjam_tipe'],
            'peminjam_id'             => $peminjamId !== '' ? $peminjamId : null,
            'peminjam_nama'           => $peminjamNama,
            'peminjam_kelas'          => $peminjamKelas !== '' ? $peminjamKelas : null,
            'keperluan'               => trim($data['keperluan']),
            'lokasi'                  => $data['lokasi'] ?? null,
            'tanggal_pinjam'          => $data['tanggal_pinjam'],
            'tanggal_kembali_rencana' => $data['tanggal_kembali_rencana'],
            'jumlah_item'             => $totalItem,
            'status'                  => 'diajukan',
            'petugas_ptk_id'          => $pj['ptk_id'],
            'petugas_nama'            => $pj['nama'],
            'user_id'                 => $context['user_id'],
            'catatan'                 => $data['catatan'] ?? null,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);

        foreach ($ruangTerpilih as $ruang) {
            DB::table('teknisi_peminjaman_item')->insert([
                'peminjaman_id'  => $id,
                'sumber'         => 'ruang',
                'ruang_id'       => $ruang->id,
                'nama_item'      => $ruang->nama_ruang,
                'kode_item'      => $ruang->kode_ruang,
                'jumlah'         => 1,
                'satuan'         => 'ruang',
                'kondisi_pinjam' => $ruang->kondisi ?? 'baik',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        $asetJumlahMap = $data['aset_jumlah'] ?? [];
        foreach ($asetItems as $aset) {
            DB::table('teknisi_peminjaman_item')->insert([
                'peminjaman_id'  => $id,
                'sumber'         => 'aset',
                'aset_id'        => $aset->id,
                'nama_item'      => $aset->nama_barang,
                'kode_item'      => $aset->kode_aset,
                'jumlah'         => (int) ($asetJumlahMap[$aset->id] ?? 1),
                'satuan'         => $aset->satuan ?: 'unit',
                'kondisi_pinjam' => $aset->kondisi,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        return $this->respond($request, true, 'Pengajuan peminjaman berhasil dibuat.', 200, [
            'redirect' => route('dashboard.teknisi.peminjaman.show', $id),
            'id'       => $id,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped($id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Data peminjaman tidak ditemukan atau di luar cakupan ruang Anda.', 404);
        }

        $data = $request->validate([
            'status'  => 'required|in:diajukan,disetujui,dipinjam,dikembalikan,terlambat,ditolak',
            'catatan' => 'nullable|string|max:1000',
        ]);

        $baru = $data['status'];
        $lama = $row->status;
        $asetTersentuh = [];

        if ($baru === 'dipinjam' && !in_array($lama, ['dipinjam', 'terlambat'], true)) {
            foreach (DB::table('teknisi_peminjaman_item')->where('peminjaman_id', $id)->get() as $it) {
                if (!empty($it->aset_id)) {
                    $asetTersentuh[] = (int) $it->aset_id;
                }
            }
        }

        if ($baru === 'dikembalikan' && $lama !== 'dikembalikan') {
            foreach (DB::table('teknisi_peminjaman_item')->where('peminjaman_id', $id)->get() as $it) {
                if (!empty($it->aset_id)) {
                    $asetTersentuh[] = (int) $it->aset_id;
                }
            }
        }

        $update = ['status' => $baru, 'updated_at' => now()];

        if ($baru === 'disetujui') {
            $update['disetujui_oleh_ptk_id'] = $context['ptk_id'];
        }
        if ($baru === 'dikembalikan') {
            $update['tanggal_kembali_aktual'] = now()->toDateString();
        }
        if (!empty($data['catatan'])) {
            $update['catatan'] = $data['catatan'];
        }

        DB::table('teknisi_peminjaman')->where('id', $id)->update($update);

        foreach (array_unique($asetTersentuh) as $asetId) {
            $this->syncStatusAset($asetId);
        }

        return $this->respond($request, true, 'Status peminjaman berhasil diperbarui menjadi ' . (self::STATUS[$baru] ?? $baru) . '.');
    }

    public function kembalikan(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped($id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Data peminjaman tidak ditemukan atau di luar cakupan ruang Anda.', 404);
        }

        if ($row->status === 'dikembalikan') {
            return $this->respond($request, false, 'Peminjaman ini sudah dikembalikan.', 422);
        }

        $data = $request->validate([
            'kondisi_kembali'      => 'required|in:baik,rusak_ringan,rusak_berat,hilang',
            'catatan_pengembalian' => 'nullable|string|max:1000',
        ]);

        $items = DB::table('teknisi_peminjaman_item')->where('peminjaman_id', $id)->get();
        $asetTersentuh = [];

        foreach ($items as $it) {
            if (!empty($it->aset_id)) {
                $asetTersentuh[] = (int) $it->aset_id;
                DB::table('sarpras_aset')->where('id', $it->aset_id)->update([
                    'status_ketersediaan' => $data['kondisi_kembali'] === 'hilang' ? 'hilang' : 'tersedia',
                    'kondisi'             => in_array($data['kondisi_kembali'], ['rusak_ringan', 'rusak_berat'], true) ? $data['kondisi_kembali'] : 'baik',
                    'updated_at'          => now(),
                ]);
            }

            DB::table('teknisi_peminjaman_item')->where('id', $it->id)->update([
                'kondisi_kembali' => $data['kondisi_kembali'],
                'updated_at'      => now(),
            ]);
        }

        DB::table('teknisi_peminjaman')->where('id', $id)->update([
            'status'                 => 'dikembalikan',
            'tanggal_kembali_aktual' => now()->toDateString(),
            'kondisi_kembali'        => $data['kondisi_kembali'],
            'catatan_pengembalian'   => $data['catatan_pengembalian'] ?? null,
            'petugas_ptk_id'         => $context['ptk_id'] ?: $row->petugas_ptk_id,
            'updated_at'             => now(),
        ]);

        foreach (array_unique($asetTersentuh) as $asetId) {
            if ($data['kondisi_kembali'] !== 'hilang') {
                $this->syncStatusAset($asetId);
            }
        }

        return $this->respond($request, true, 'Pengembalian berhasil dicatat.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped($id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Data peminjaman tidak ditemukan atau di luar cakupan ruang Anda.', 404);
        }

        $asetTersentuh = [];
        if (in_array($row->status, ['dipinjam', 'terlambat'], true)) {
            foreach (DB::table('teknisi_peminjaman_item')->where('peminjaman_id', $id)->get() as $it) {
                if (!empty($it->aset_id)) {
                    $asetTersentuh[] = (int) $it->aset_id;
                }
            }
        }

        DB::table('teknisi_peminjaman_item')->where('peminjaman_id', $id)->delete();
        DB::table('teknisi_peminjaman')->where('id', $id)->delete();

        foreach (array_unique($asetTersentuh) as $asetId) {
            $this->syncStatusAset($asetId);
        }

        return $this->respond($request, true, 'Data peminjaman berhasil dihapus.');
    }

    public function tandaiTerlambat(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $q = DB::table('teknisi_peminjaman')
            ->where('status', 'dipinjam')
            ->whereNotNull('tanggal_kembali_rencana')
            ->where('tanggal_kembali_rencana', '<', now()->toDateString());
        $this->scopeRuangPj($q, $context);

        $jumlah = $q->update(['status' => 'terlambat', 'updated_at' => now()]);

        return $this->respond($request, true, $jumlah . ' peminjaman ditandai terlambat.');
    }

    // =====================================================================
    // Cakupan (scope) berbasis PJ ruang
    // =====================================================================

    /**
     * Batasi query peminjaman ke cakupan PJ ruang pengguna.
     *
     * Baris tampil bila SALAH SATU item-nya adalah ruang yang di-PJ-kan
     * pengguna, ATAU aset yang berlokasi di ruang tersebut. Koordinator
     * melihat seluruh data (tidak dibatasi).
     */
    protected function scopeRuangPj($query, array $context, ?string $alias = null)
    {
        $pjIds = $this->ruangMilikPj($context);
        if ($pjIds === null) {
            return $query; // koordinator
        }

        if (empty($pjIds)) {
            // Teknisi tanpa ruang PJ tidak melihat data apa pun.
            return $query->whereRaw('1 = 0');
        }

        $outer = ($alias ? $alias . '.' : 'teknisi_peminjaman.') . 'id';

        return $query->whereExists(function ($sub) use ($pjIds, $outer) {
            $sub->selectRaw('1')
                ->from('teknisi_peminjaman_item as tpi')
                ->whereColumn('tpi.peminjaman_id', $outer)
                ->where(function ($w) use ($pjIds) {
                    $w->whereIn('tpi.ruang_id', $pjIds)
                      ->orWhereIn('tpi.aset_id', function ($q) use ($pjIds) {
                          $q->select('id')->from('sarpras_aset')->whereIn('ruang_id', $pjIds);
                      });
                });
        });
    }

    protected function scopedQuery($query, array $context)
    {
        $this->scopeRuangPj($query, $context);

        return $query;
    }

    protected function findScoped($id, array $context): ?object
    {
        $query = DB::table('teknisi_peminjaman as p')->select('p.*')->where('p.id', $id);
        $this->scopeRuangPj($query, $context, 'p');

        return $query->first();
    }

    // =====================================================================
    // Opsi sumber + sinkron ketersediaan aset
    // =====================================================================

    /** Ruang master dalam cakupan PJ (koordinator: semua ruang). */
    protected function ruangOptions(array $context)
    {
        if (!\App\Support\Table::exists('sarpras_ruang')) {
            return collect();
        }

        $q = DB::table('sarpras_ruang')
            ->select('id', 'kode_ruang', 'nama_ruang', 'gedung', 'kondisi')
            ->orderBy('nama_ruang');

        $pjIds = $this->ruangMilikPj($context);
        if (is_array($pjIds)) {
            $q->whereIn('id', $pjIds);
        }

        return $q->get();
    }

    /** Aset inventaris dalam cakupan PJ ruang tempat aset berlokasi. */
    protected function asetOptions(array $context)
    {
        if (!\App\Support\Table::exists('sarpras_aset')) {
            return collect();
        }

        $q = DB::table('sarpras_aset as sa')
            ->leftJoin('sarpras_ruang as sr', 'sa.ruang_id', '=', 'sr.id')
            ->select(
                'sa.id', 'sa.kode_aset', 'sa.nama_barang', 'sa.kategori', 'sa.merk_tipe',
                'sa.kondisi', 'sa.jumlah', 'sa.jumlah_rusak_ringan', 'sa.jumlah_rusak_berat',
                'sa.satuan', 'sa.status_ketersediaan', 'sa.ruang_id',
                'sr.nama_ruang as ruang_nama'
            )
            ->orderBy('sa.nama_barang');

        $pjIds = $this->ruangMilikPj($context);
        if (is_array($pjIds)) {
            $q->whereIn('sa.ruang_id', $pjIds);
        }

        $rows = $q->get();
        $dipinjamMap = $this->unitDipinjamAset($rows->pluck('id')->all());

        foreach ($rows as $row) {
            $row->unit_dipinjam = (float) ($dipinjamMap[$row->id] ?? 0);
            $row->unit_tersedia = $this->unitTersediaAset($row, $dipinjamMap);
        }

        return $rows;
    }

    /** Total unit aset yang sedang ditahan peminjaman aktif (teknisi). */
    protected function unitDipinjamAset(array $asetIds): array
    {
        if (empty($asetIds)) {
            return [];
        }

        return DB::table('teknisi_peminjaman_item as tpi')
            ->join('teknisi_peminjaman as tp', 'tpi.peminjaman_id', '=', 'tp.id')
            ->whereIn('tpi.aset_id', $asetIds)
            ->whereIn('tp.status', ['disetujui', 'dipinjam', 'terlambat'])
            ->groupBy('tpi.aset_id')
            ->selectRaw('tpi.aset_id, SUM(tpi.jumlah) as total')
            ->pluck('total', 'tpi.aset_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

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

    protected function syncStatusAset(int $asetId): void
    {
        $aset = DB::table('sarpras_aset')->where('id', $asetId)->first();
        if (!$aset) {
            return;
        }
        if (($aset->status_ketersediaan ?? '') === 'hilang') {
            return;
        }

        $tersedia = $this->unitTersediaAset($aset, $this->unitDipinjamAset([$asetId]));

        DB::table('sarpras_aset')->where('id', $asetId)->update([
            'status_ketersediaan' => $tersedia > 0 ? 'tersedia' : 'dipinjam',
            'updated_at'          => now(),
        ]);
    }
}
