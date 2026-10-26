<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Services\LaboranInventarisImportService;
use App\Support\LaboranScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;

/**
 * Inventaris Alat & Bahan Laboratorium (per JURUSAN).
 *
 * Seluruh data dibatasi cakupan jurusan pengguna melalui App\Support\LaboranScope:
 *  - Laboran hanya melihat & mengubah item milik jurusannya.
 *  - Administrator / Kepala Lab dapat lintas jurusan.
 */
class LaboranInventarisController extends Controller
{
    use ChecksAuthenticatedSession;

    public const JENIS = [
        'alat'              => 'Alat',
        'bahan_habis_pakai' => 'Bahan Habis Pakai',
    ];

    public const KONDISI = [
        'baik'         => 'Baik',
        'rusak_ringan' => 'Rusak Ringan',
        'rusak_berat'  => 'Rusak Berat',
        'kedaluwarsa'  => 'Kedaluwarsa',
    ];

    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $context = LaboranScope::resolve($user);

        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_laboran', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_laboran', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_laboran', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_laboran', 'delete');

        $search        = trim($request->query('q', ''));
        $labFilter     = trim($request->query('lab', ''));
        $jenisFilter   = trim($request->query('jenis', ''));
        $kondisiFilter = trim($request->query('kondisi', ''));
        $jurusanFilter = trim($request->query('jurusan', ''));

        $query = DB::table('laboran_bahan_alat as lba')
            ->leftJoin('jurusan_meta as jm', 'lba.jurusan_id', '=', 'jm.jurusan_id')
            ->select('lba.*', 'jm.nama_jurusan', 'jm.singkatan');

        LaboranScope::apply($query, 'lba.jurusan_id', $context);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('lba.kode_item', 'like', "%{$search}%")
                  ->orWhere('lba.nama_item', 'like', "%{$search}%")
                  ->orWhere('lba.spesifikasi', 'like', "%{$search}%")
                  ->orWhere('lba.lokasi_lemari_rak', 'like', "%{$search}%");
            });
        }

        if ($labFilter !== '') {
            $query->where('lba.ruang_lab_nama', $labFilter);
        }

        if ($jenisFilter !== '') {
            $query->where('lba.jenis', $jenisFilter);
        }

        if ($kondisiFilter !== '') {
            $query->where('lba.kondisi', $kondisiFilter);
        }

        if ($jurusanFilter !== '' && $context['unrestricted']) {
            $query->where('lba.jurusan_id', $jurusanFilter);
        }

        // Statistik (dalam cakupan jurusan)
        $statBase = DB::table('laboran_bahan_alat');
        LaboranScope::apply($statBase, 'jurusan_id', $context);

        $statTotal  = (clone $statBase)->count();
        $statAlat   = (clone $statBase)->where('jenis', 'alat')->count();
        $statBahan  = (clone $statBase)->where('jenis', 'bahan_habis_pakai')->count();
        $statKritis = (clone $statBase)
            ->where(function ($q) {
                $q->where('stok_tersedia', '<=', 2)
                  ->orWhere('kondisi', '!=', 'baik');
            })->count();

        // Master lokasi dari Sarpras & Aset
        $daftarRuangSarpras = DB::table('sarpras_ruang')
            ->orderBy('gedung', 'asc')
            ->orderBy('nama_ruang', 'asc')
            ->get(['id', 'kode_ruang', 'nama_ruang', 'gedung', 'lantai', 'kondisi']);

        $daftarLab = $daftarRuangSarpras->pluck('nama_ruang')->toArray();

        // Gabungkan dengan lokasi yang sudah tercatat pada inventaris (dalam cakupan)
        $existingQuery = DB::table('laboran_bahan_alat')->whereNotNull('ruang_lab_nama');
        LaboranScope::apply($existingQuery, 'jurusan_id', $context);
        $existingLabs = $existingQuery->distinct()->pluck('ruang_lab_nama')->toArray();

        if (!empty($existingLabs)) {
            $daftarLab = array_values(array_unique(array_merge($daftarLab, $existingLabs)));
        }

        // Referensi aset Sarpras (untuk salin data)
        $daftarAsetSarpras = DB::table('sarpras_aset as sa')
            ->leftJoin('sarpras_ruang as sr', 'sa.ruang_id', '=', 'sr.id')
            ->select('sa.id', 'sa.kode_aset', 'sa.nama_barang', 'sa.kategori', 'sa.merk_tipe', 'sa.kondisi', 'sa.jumlah', 'sa.satuan', 'sr.nama_ruang as ruang_nama')
            ->orderBy('sa.nama_barang', 'asc')
            ->get();

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $list = $query->orderBy('lba.ruang_lab_nama', 'asc')
            ->orderBy('lba.nama_item', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        return view('dashboard.laboran.inventaris', [
            'list'               => $list,
            'search'             => $search,
            'labFilter'          => $labFilter,
            'jenisFilter'        => $jenisFilter,
            'kondisiFilter'      => $kondisiFilter,
            'jurusanFilter'      => $jurusanFilter,
            'daftarLab'          => $daftarLab,
            'daftarRuangSarpras' => $daftarRuangSarpras,
            'daftarAsetSarpras'  => $daftarAsetSarpras,
            'statTotal'          => $statTotal,
            'statAlat'           => $statAlat,
            'statBahan'          => $statBahan,
            'statKritis'         => $statKritis,
            'canCreate'          => $canCreate,
            'canRead'            => $canRead,
            'canUpdate'          => $canUpdate,
            'canDelete'          => $canDelete,
            'perPage'            => $perPage,
            'context'            => $context,
            'scopeBadge'         => LaboranScope::badge($context),
            'jurusanOptions'     => LaboranScope::jurusanOptions($context),
            'jenisList'          => self::JENIS,
            'kondisiList'        => self::KONDISI,
        ]);
    }

    public function store(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $context = LaboranScope::resolve();

        $request->validate([
            'ruang_lab_nama'    => 'required|string|max:100',
            // Kode item OPSIONAL: bila kosong dibuat otomatis oleh sistem.
            'kode_item'         => 'nullable|string|max:50|unique:laboran_bahan_alat,kode_item',
            'nama_item'         => 'required|string|max:255',
            'jenis'             => 'required|in:alat,bahan_habis_pakai',
            'stok_total'        => 'required|numeric|min:0.01',
            'satuan'            => 'required|string|max:30',
            'kondisi'           => 'required|in:baik,rusak_ringan,rusak_berat,kedaluwarsa',
            'jumlah_baik'         => 'nullable|numeric|min:0',
            'jumlah_rusak_ringan' => 'nullable|numeric|min:0',
            'jumlah_rusak_berat'  => 'nullable|numeric|min:0',
            'jumlah_kedaluwarsa'  => 'nullable|numeric|min:0',
            'spesifikasi'       => 'nullable|string|max:1000',
            'lokasi_lemari_rak' => 'nullable|string|max:100',
            'tgl_kedaluwarsa'   => 'nullable|date',
            'tanggal_pengadaan' => 'nullable|date',
            'tahun_pengadaan'   => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'jurusan_id'        => 'nullable|string|max:50',
        ], [
            'kode_item.unique' => 'Kode item sudah digunakan.',
        ]);

        $kode = strtoupper(trim((string) $request->kode_item));
        if ($kode === '') {
            $kode = $this->generateKodeItem($request->nama_item, $request->jenis);
        }

        $stokTotal = (float) $request->stok_total;
        $rincian = $this->resolveRincianKondisi($request, $stokTotal, $request->kondisi);

        DB::table('laboran_bahan_alat')->insert([
            'jurusan_id'          => LaboranScope::jurusanForWrite($request->jurusan_id, $context),
            'ruang_lab_nama'      => $request->ruang_lab_nama,
            'kode_item'           => $kode,
            'nama_item'           => trim($request->nama_item),
            'jenis'               => $request->jenis,
            'spesifikasi'         => $request->spesifikasi,
            'stok_total'          => $stokTotal,
            'stok_tersedia'       => $stokTotal,
            'jumlah_baik'         => $rincian['baik'],
            'jumlah_rusak_ringan' => $rincian['rusak_ringan'],
            'jumlah_rusak_berat'  => $rincian['rusak_berat'],
            'jumlah_kedaluwarsa'  => $rincian['kedaluwarsa'],
            'satuan'              => $request->satuan,
            'kondisi'             => $rincian['kondisi'],
            'tgl_kedaluwarsa'     => $request->tgl_kedaluwarsa,
            'lokasi_lemari_rak'   => $request->lokasi_lemari_rak,
            'tanggal_pengadaan'   => $request->tanggal_pengadaan,
            'tahun_pengadaan'     => $request->tahun_pengadaan ?: ($request->tanggal_pengadaan ? (int) date('Y', strtotime($request->tanggal_pengadaan)) : null),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $msg = 'Alat/bahan lab berhasil ditambahkan.' . ($kode !== '' ? " Kode item: {$kode}." : '');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $msg, 'kode_item' => $kode]);
        }

        return redirect()->route('dashboard.laboran.inventaris.index')->with('success', $msg);
    }

    /**
     * Buat kode item otomatis yang unik (delegasi ke service import).
     */
    protected function generateKodeItem(?string $namaItem, ?string $jenis): string
    {
        return app(LaboranInventarisImportService::class)->generateKodeItem($jenis);
    }

    /**
     * Tentukan rincian jumlah per kondisi.
     * Bila rincian tidak diisi, seluruh stok dianggap sesuai `kondisi` yang dipilih.
     *
     * @return array{baik: float, rusak_ringan: float, rusak_berat: float, kedaluwarsa: float, kondisi: string}
     */
    protected function resolveRincianKondisi(Request $request, float $stokTotal, ?string $kondisiDefault): array
    {
        $baik  = (float) ($request->input('jumlah_baik') ?? 0);
        $rr    = (float) ($request->input('jumlah_rusak_ringan') ?? 0);
        $rb    = (float) ($request->input('jumlah_rusak_berat') ?? 0);
        $kad   = (float) ($request->input('jumlah_kedaluwarsa') ?? 0);
        $sum   = $baik + $rr + $rb + $kad;

        // Tidak ada rincian -> seluruh stok mengikuti kondisi default.
        if ($sum <= 0) {
            $kondisi = in_array($kondisiDefault, ['baik', 'rusak_ringan', 'rusak_berat', 'kedaluwarsa'], true)
                ? $kondisiDefault
                : 'baik';

            $rincian = ['baik' => 0.0, 'rusak_ringan' => 0.0, 'rusak_berat' => 0.0, 'kedaluwarsa' => 0.0];
            $rincian[$kondisi] = $stokTotal;

            return $rincian + ['kondisi' => $kondisi];
        }

        // Kondisi dominan dihitung dari jumlah terbanyak (rusak berat diprioritaskan bila seri).
        $kondisi = 'baik';
        $max = $baik;
        if ($rr > $max) { $kondisi = 'rusak_ringan'; $max = $rr; }
        if ($rb >= $max && $rb > 0) { $kondisi = 'rusak_berat'; $max = $rb; }
        if ($kad > $max) { $kondisi = 'kedaluwarsa'; }

        return [
            'baik'          => $baik,
            'rusak_ringan'  => $rr,
            'rusak_berat'   => $rb,
            'kedaluwarsa'   => $kad,
            'kondisi'       => $kondisi,
        ];
    }

    public function update(Request $request, $id)
    {
        if ($res = $this->checkAuth()) return $res;

        $context = LaboranScope::resolve();
        $row = DB::table('laboran_bahan_alat')->where('id', $id)->first();

        if (!$row || !LaboranScope::allows($row->jurusan_id ?? null, $context)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Data tidak ditemukan atau di luar cakupan jurusan Anda.',
                ], 404);
            }
            return redirect()->route('dashboard.laboran.inventaris.index')
                ->with('error', 'Data tidak ditemukan atau di luar cakupan jurusan Anda.');
        }

        $request->validate([
            'ruang_lab_nama'    => 'required|string|max:100',
            // Kode item OPSIONAL: bila dikosongkan akan dibuat otomatis.
            'kode_item'         => 'nullable|string|max:50|unique:laboran_bahan_alat,kode_item,' . $id,
            'nama_item'         => 'required|string|max:255',
            'jenis'             => 'required|in:alat,bahan_habis_pakai',
            'stok_total'        => 'required|numeric|min:0.01',
            'stok_tersedia'     => 'required|numeric|min:0',
            'satuan'            => 'required|string|max:30',
            'kondisi'           => 'required|in:baik,rusak_ringan,rusak_berat,kedaluwarsa',
            'jumlah_baik'         => 'nullable|numeric|min:0',
            'jumlah_rusak_ringan' => 'nullable|numeric|min:0',
            'jumlah_rusak_berat'  => 'nullable|numeric|min:0',
            'jumlah_kedaluwarsa'  => 'nullable|numeric|min:0',
            'spesifikasi'       => 'nullable|string|max:1000',
            'lokasi_lemari_rak' => 'nullable|string|max:100',
            'tgl_kedaluwarsa'   => 'nullable|date',
            'tanggal_pengadaan' => 'nullable|date',
            'tahun_pengadaan'   => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'jurusan_id'        => 'nullable|string|max:50',
        ]);

        $kode = strtoupper(trim((string) $request->kode_item));
        if ($kode === '') {
            $kode = $row->kode_item ?: $this->generateKodeItem($request->nama_item, $request->jenis);
        }

        $stokTotal = (float) $request->stok_total;
        $rincian = $this->resolveRincianKondisi($request, $stokTotal, $request->kondisi);

        DB::table('laboran_bahan_alat')->where('id', $id)->update([
            'jurusan_id'          => LaboranScope::jurusanForWrite($request->jurusan_id, $context),
            'ruang_lab_nama'      => $request->ruang_lab_nama,
            'kode_item'           => $kode,
            'nama_item'           => trim($request->nama_item),
            'jenis'               => $request->jenis,
            'spesifikasi'         => $request->spesifikasi,
            'stok_total'          => $stokTotal,
            'stok_tersedia'       => $request->stok_tersedia,
            'jumlah_baik'         => $rincian['baik'],
            'jumlah_rusak_ringan' => $rincian['rusak_ringan'],
            'jumlah_rusak_berat'  => $rincian['rusak_berat'],
            'jumlah_kedaluwarsa'  => $rincian['kedaluwarsa'],
            'satuan'              => $request->satuan,
            'kondisi'             => $rincian['kondisi'],
            'tgl_kedaluwarsa'     => $request->tgl_kedaluwarsa,
            'lokasi_lemari_rak'   => $request->lokasi_lemari_rak,
            'tanggal_pengadaan'   => $request->tanggal_pengadaan,
            'tahun_pengadaan'     => $request->tahun_pengadaan ?: ($request->tanggal_pengadaan ? (int) date('Y', strtotime($request->tanggal_pengadaan)) : null),
            'updated_at'          => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Data alat/bahan lab berhasil diperbarui.']);
        }

        return redirect()->route('dashboard.laboran.inventaris.index')->with('success', 'Data alat/bahan lab berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        if ($res = $this->checkAuth()) return $res;

        $context = LaboranScope::resolve();
        $row = DB::table('laboran_bahan_alat')->where('id', $id)->first();

        if (!$row || !LaboranScope::allows($row->jurusan_id ?? null, $context)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data tidak ditemukan atau di luar cakupan jurusan Anda.',
            ], 404);
        }

        // Cegah hapus bila item masih dipinjam
        $dipinjam = DB::table('laboran_peminjaman_item as pi')
            ->join('laboran_peminjaman as p', 'pi.peminjaman_id', '=', 'p.id')
            ->where('pi.item_id', $id)
            ->whereIn('p.status', ['diajukan', 'disetujui', 'dipinjam', 'terlambat'])
            ->exists();

        if ($dipinjam) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Item tidak dapat dihapus karena masih tercatat dalam peminjaman aktif.',
            ], 422);
        }

        DB::table('laboran_bahan_alat')->where('id', $id)->delete();

        return response()->json(['status' => 'success', 'message' => 'Data alat/bahan berhasil dihapus.']);
    }

    /**
     * Unduh format berkas Excel untuk import inventaris laboratorium.
     */
    public function templateExcel(?LaboranInventarisImportService $service = null)
    {
        $service = $service ?? app(LaboranInventarisImportService::class);
        $path = $service->generateTemplateFile();

        return response()
            ->download($path, 'Format_Import_Inventaris_Laboratorium.xlsx')
            ->deleteFileAfterSend(true);
    }

    /**
     * Import inventaris alat & bahan via Excel (.xlsx, .xls, .csv).
     */
    public function importExcel(Request $request, LaboranInventarisImportService $service)
    {
        if ($res = $this->checkAuth()) return $res;

        $context = LaboranScope::resolve();

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

        return response()->json([
            'status'  => 'success',
            'message' => "Import selesai: {$hasil['imported']} item baru ditambahkan, {$hasil['updated']} item diperbarui.",
            'count'   => $hasil['imported'] + $hasil['updated'],
        ]);
    }
}
