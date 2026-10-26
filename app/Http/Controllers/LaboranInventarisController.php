<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
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
            'kode_item'         => 'required|string|max:50|unique:laboran_bahan_alat,kode_item',
            'nama_item'         => 'required|string|max:255',
            'jenis'             => 'required|in:alat,bahan_habis_pakai',
            'stok_total'        => 'required|numeric|min:0.01',
            'satuan'            => 'required|string|max:30',
            'kondisi'           => 'required|in:baik,rusak_ringan,rusak_berat,kedaluwarsa',
            'spesifikasi'       => 'nullable|string|max:1000',
            'lokasi_lemari_rak' => 'nullable|string|max:100',
            'tgl_kedaluwarsa'   => 'nullable|date',
            'jurusan_id'        => 'nullable|string|max:50',
        ], [
            'kode_item.unique' => 'Kode item sudah digunakan.',
        ]);

        DB::table('laboran_bahan_alat')->insert([
            'jurusan_id'        => LaboranScope::jurusanForWrite($request->jurusan_id, $context),
            'ruang_lab_nama'    => $request->ruang_lab_nama,
            'kode_item'         => strtoupper(trim($request->kode_item)),
            'nama_item'         => trim($request->nama_item),
            'jenis'             => $request->jenis,
            'spesifikasi'       => $request->spesifikasi,
            'stok_total'        => $request->stok_total,
            'stok_tersedia'     => $request->stok_total,
            'satuan'            => $request->satuan,
            'kondisi'           => $request->kondisi,
            'tgl_kedaluwarsa'   => $request->tgl_kedaluwarsa,
            'lokasi_lemari_rak' => $request->lokasi_lemari_rak,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Alat/bahan lab berhasil ditambahkan.']);
        }

        return redirect()->route('dashboard.laboran.inventaris.index')->with('success', 'Alat/bahan lab berhasil ditambahkan.');
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
            'kode_item'         => 'required|string|max:50|unique:laboran_bahan_alat,kode_item,' . $id,
            'nama_item'         => 'required|string|max:255',
            'jenis'             => 'required|in:alat,bahan_habis_pakai',
            'stok_total'        => 'required|numeric|min:0.01',
            'stok_tersedia'     => 'required|numeric|min:0',
            'satuan'            => 'required|string|max:30',
            'kondisi'           => 'required|in:baik,rusak_ringan,rusak_berat,kedaluwarsa',
            'spesifikasi'       => 'nullable|string|max:1000',
            'lokasi_lemari_rak' => 'nullable|string|max:100',
            'tgl_kedaluwarsa'   => 'nullable|date',
            'jurusan_id'        => 'nullable|string|max:50',
        ]);

        DB::table('laboran_bahan_alat')->where('id', $id)->update([
            'jurusan_id'        => LaboranScope::jurusanForWrite($request->jurusan_id, $context),
            'ruang_lab_nama'    => $request->ruang_lab_nama,
            'kode_item'         => strtoupper(trim($request->kode_item)),
            'nama_item'         => trim($request->nama_item),
            'jenis'             => $request->jenis,
            'spesifikasi'       => $request->spesifikasi,
            'stok_total'        => $request->stok_total,
            'stok_tersedia'     => $request->stok_tersedia,
            'satuan'            => $request->satuan,
            'kondisi'           => $request->kondisi,
            'tgl_kedaluwarsa'   => $request->tgl_kedaluwarsa,
            'lokasi_lemari_rak' => $request->lokasi_lemari_rak,
            'updated_at'        => now(),
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
     * Download format berkas Excel untuk import inventaris laboratorium (Point 7).
     */
    public function templateExcel()
    {
        $headers = [
            'kode_item',
            'nama_item',
            'jenis',
            'kategori',
            'spesifikasi',
            'stok_total',
            'satuan',
            'kondisi',
            'ruang_lab_nama',
            'lokasi_lemari_rak',
            'tgl_kedaluwarsa',
        ];

        $rows = [
            [
                'ITM-LAB-001',
                'Mikroskop Binokuler Digital',
                'alat',
                'Optik',
                'Perbesaran 1000x, kamera USB 5MP',
                '5',
                'unit',
                'baik',
                'Lab Biologi',
                'Lemari A-01',
                '',
            ],
            [
                'ITM-LAB-002',
                'Alkohol 70% 1 Liter',
                'bahan_habis_pakai',
                'Bahan Kimia',
                'Kemasan botol 1000 ml teknis',
                '20',
                'botol',
                'baik',
                'Lab Kimia',
                'Rak Bahan C-03',
                '2028-12-31',
            ],
            [
                'ITM-LAB-003',
                'Kabel UTP Cat 6 (Roll 305m)',
                'bahan_habis_pakai',
                'Jaringan',
                'Belden Original UTP Cat6 305 Meter',
                '3',
                'roll',
                'baik',
                'Lab Komputer 1',
                'Gudang TKJ',
                '',
            ],
        ];

        $meta = [
            'title'    => 'Format Import Inventaris Alat & Bahan Laboratorium',
            'subtitle' => 'Sistem Aplikasi Edukasi (SAE) — Diunduh ' . now()->translatedFormat('d F Y H:i'),
        ];

        $tempPath = storage_path('app/temp_template_inventaris_' . uniqid() . '.xlsx');
        \App\Services\SimpleXlsxExporter::export($tempPath, $meta, $headers, $rows, [
            'sheetName' => 'Inventaris Lab',
        ]);

        return response()->download($tempPath, 'Format_Import_Inventaris_Laboratorium.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * Import inventaris alat & bahan via Excel (.xlsx, .xls, .csv) (Point 7).
     */
    public function importExcel(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $context = LaboranScope::resolve();
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'], true)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Format berkas tidak didukung. Harap unggah file .xlsx, .xls, atau .csv.',
            ], 422);
        }

        try {
            $rows = \App\Services\SimpleXlsxReader::readFile($file->getRealPath());
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal membaca berkas: ' . $e->getMessage(),
            ], 422);
        }

        if (empty($rows)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Berkas Excel kosong atau tidak memiliki data baris.',
            ], 422);
        }

        $imported = 0;
        $updated = 0;
        $jurusanId = LaboranScope::jurusanForWrite($request->input('jurusan_id'), $context);

        foreach ($rows as $r) {
            $normalized = [];
            foreach ($r as $k => $v) {
                $cleanKey = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', (string) $k))));
                $normalized[$cleanKey] = is_string($v) ? trim($v) : $v;
            }

            $nama = $normalized['nama_item'] ?? ($normalized['nama_barang'] ?? ($normalized['nama'] ?? ''));
            if (!$nama) {
                continue;
            }

            $kode = strtoupper((string) ($normalized['kode_item'] ?? ($normalized['kode'] ?? '')));
            if (!$kode) {
                $kode = 'ITM-' . strtoupper(substr(uniqid(), -6));
            }

            $rawJenis = strtolower((string) ($normalized['jenis'] ?? 'alat'));
            $jenis = in_array($rawJenis, ['bahan', 'bahan_habis_pakai', 'habis_pakai', 'bhp'], true) ? 'bahan_habis_pakai' : 'alat';

            $total = (float) ($normalized['stok_total'] ?? ($normalized['stok'] ?? ($normalized['jumlah'] ?? 1)));
            $tersedia = isset($normalized['stok_tersedia']) ? (float) $normalized['stok_tersedia'] : $total;
            $satuan = (string) ($normalized['satuan'] ?? 'unit');

            $rawKondisi = strtolower((string) ($normalized['kondisi'] ?? 'baik'));
            $kondisi = match ($rawKondisi) {
                'rusak_ringan', 'ringan' => 'rusak_ringan',
                'rusak_berat', 'berat'   => 'rusak_berat',
                'kedaluwarsa', 'expired' => 'kedaluwarsa',
                default                  => 'baik',
            };

            $ruang = (string) ($normalized['ruang_lab_nama'] ?? ($normalized['lokasi'] ?? ($normalized['ruangan'] ?? 'Lab Utama')));
            $spesifikasi = (string) ($normalized['spesifikasi'] ?? '');
            $rak = (string) ($normalized['lokasi_lemari_rak'] ?? ($normalized['rak'] ?? ($normalized['lemari'] ?? '')));
            $kadaluwarsa = (string) ($normalized['tgl_kedaluwarsa'] ?? ($normalized['kedaluwarsa'] ?? ''));
            $tglVal = null;
            if ($kadaluwarsa) {
                try { $tglVal = \Carbon\Carbon::parse($kadaluwarsa)->toDateString(); } catch (\Throwable $e) {}
            }

            $existing = DB::table('laboran_bahan_alat')->where('kode_item', $kode)->first();

            if ($existing) {
                DB::table('laboran_bahan_alat')->where('id', $existing->id)->update([
                    'nama_item'         => $nama,
                    'jenis'             => $jenis,
                    'stok_total'        => $total,
                    'stok_tersedia'     => $tersedia,
                    'satuan'            => $satuan,
                    'kondisi'           => $kondisi,
                    'ruang_lab_nama'    => $ruang,
                    'spesifikasi'       => $spesifikasi ?: $existing->spesifikasi,
                    'lokasi_lemari_rak' => $rak ?: $existing->lokasi_lemari_rak,
                    'tgl_kedaluwarsa'   => $tglVal ?: $existing->tgl_kedaluwarsa,
                    'updated_at'        => now(),
                ]);
                $updated++;
            } else {
                DB::table('laboran_bahan_alat')->insert([
                    'jurusan_id'        => $jurusanId,
                    'ruang_lab_nama'    => $ruang,
                    'kode_item'         => $kode,
                    'nama_item'         => $nama,
                    'jenis'             => $jenis,
                    'spesifikasi'       => $spesifikasi ?: null,
                    'stok_total'        => max(0.01, $total),
                    'stok_tersedia'     => max(0, $tersedia),
                    'satuan'            => $satuan,
                    'kondisi'           => $kondisi,
                    'tgl_kedaluwarsa'   => $tglVal,
                    'lokasi_lemari_rak' => $rak ?: null,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
                $imported++;
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Import selesai: {$imported} item baru ditambahkan, {$updated} item diperbarui.",
            'count'   => $imported + $updated,
        ]);
    }
}
