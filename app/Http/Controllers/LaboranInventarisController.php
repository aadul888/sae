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
     * Buat kode item otomatis yang unik.
     * Format: {PREFIX}-{YYYYMM}-{NNNN}, mis. ALAT-202610-0001.
     */
    protected function generateKodeItem(?string $namaItem, ?string $jenis): string
    {
        $prefix = $jenis === 'bahan_habis_pakai' ? 'BHP' : 'ALT';
        $periode = now()->format('Ym');
        $base = $prefix . '-' . $periode . '-';

        $last = DB::table('laboran_bahan_alat')
            ->where('kode_item', 'like', $base . '%')
            ->orderByDesc('kode_item')
            ->value('kode_item');

        $seq = 1;
        if ($last) {
            $tail = substr($last, strlen($base));
            $seq = (int) $tail + 1;
        }

        // Jaminan unik (bila ada bentrok karena data lama / race condition)
        $candidate = $base . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        $guard = 0;
        while (DB::table('laboran_bahan_alat')->where('kode_item', $candidate)->exists() && $guard < 9999) {
            $seq++;
            $candidate = $base . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            $guard++;
        }

        return $candidate;
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
            'jumlah_baik',
            'jumlah_rusak_ringan',
            'jumlah_rusak_berat',
            'jumlah_kedaluwarsa',
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
                '4',
                '1',
                '0',
                '0',
                'Lab Biologi',
                'Lemari A-01',
                '',
            ],
            [
                '',
                'Traktor Tangan Mini',
                'alat',
                'Mesin Pertanian',
                'Kapasitas 8 PK, roda dua',
                '10',
                'unit',
                'baik',
                '5',
                '3',
                '2',
                '0',
                'Lahan Praktik ATPH',
                'Gudang Alsintan',
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
                '20',
                '0',
                '0',
                '0',
                'Lab Kimia',
                'Rak Bahan C-03',
                '2028-12-31',
            ],
        ];

        $meta = [
            'title'    => 'Format Import Inventaris Alat & Bahan Laboratorium',
            'subtitle' => 'Sistem Aplikasi Edukasi (SAE) — Diunduh ' . now()->translatedFormat('d F Y H:i'),
            'fields'   => [
                'Kode Item'      => 'Boleh dikosongkan — sistem akan membuat kode otomatis',
                'Rincian Kondisi' => 'jumlah_baik + jumlah_rusak_ringan + jumlah_rusak_berat + jumlah_kedaluwarsa = stok_total',
            ],
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

            $kode = strtoupper(trim((string) ($normalized['kode_item'] ?? ($normalized['kode'] ?? ''))));

            $rawJenis = strtolower((string) ($normalized['jenis'] ?? 'alat'));
            $jenis = in_array($rawJenis, ['bahan', 'bahan_habis_pakai', 'habis_pakai', 'bhp'], true) ? 'bahan_habis_pakai' : 'alat';

            // Kode kosong -> buat otomatis (tidak lagi memakai uniqid acak).
            if ($kode === '') {
                $kode = $this->generateKodeItem($nama, $jenis);
            }

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

            // Rincian kondisi per jumlah (opsional). Bila kosong, seluruh stok
            // dianggap sesuai kolom `kondisi`.
            $jBaik = (float) ($normalized['jumlah_baik'] ?? ($normalized['baik'] ?? 0));
            $jRr   = (float) ($normalized['jumlah_rusak_ringan'] ?? ($normalized['rusak_ringan'] ?? 0));
            $jRb   = (float) ($normalized['jumlah_rusak_berat'] ?? ($normalized['rusak_berat'] ?? 0));
            $jKad  = (float) ($normalized['jumlah_kedaluwarsa'] ?? ($normalized['kedaluwarsa_jml'] ?? 0));

            $rincian = ['baik' => 0.0, 'rusak_ringan' => 0.0, 'rusak_berat' => 0.0, 'kedaluwarsa' => 0.0];
            if (($jBaik + $jRr + $jRb + $jKad) > 0) {
                $rincian = ['baik' => $jBaik, 'rusak_ringan' => $jRr, 'rusak_berat' => $jRb, 'kedaluwarsa' => $jKad];

                // Kondisi dominan mengikuti jumlah terbanyak
                $dominan = 'baik';
                $max = $jBaik;
                if ($jRr > $max) { $dominan = 'rusak_ringan'; $max = $jRr; }
                if ($jRb >= $max && $jRb > 0) { $dominan = 'rusak_berat'; $max = $jRb; }
                if ($jKad > $max) { $dominan = 'kedaluwarsa'; }
                $kondisi = $dominan;
            } else {
                $rincian[$kondisi] = $total;
            }

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
                    'nama_item'           => $nama,
                    'jenis'               => $jenis,
                    'stok_total'          => $total,
                    'stok_tersedia'       => $tersedia,
                    'jumlah_baik'         => $rincian['baik'],
                    'jumlah_rusak_ringan' => $rincian['rusak_ringan'],
                    'jumlah_rusak_berat'  => $rincian['rusak_berat'],
                    'jumlah_kedaluwarsa'  => $rincian['kedaluwarsa'],
                    'satuan'              => $satuan,
                    'kondisi'             => $kondisi,
                    'ruang_lab_nama'      => $ruang,
                    'spesifikasi'         => $spesifikasi ?: $existing->spesifikasi,
                    'lokasi_lemari_rak'   => $rak ?: $existing->lokasi_lemari_rak,
                    'tgl_kedaluwarsa'     => $tglVal ?: $existing->tgl_kedaluwarsa,
                    'updated_at'          => now(),
                ]);
                $updated++;
            } else {
                DB::table('laboran_bahan_alat')->insert([
                    'jurusan_id'          => $jurusanId,
                    'ruang_lab_nama'      => $ruang,
                    'kode_item'           => $kode,
                    'nama_item'           => $nama,
                    'jenis'               => $jenis,
                    'spesifikasi'         => $spesifikasi ?: null,
                    'stok_total'          => max(0.01, $total),
                    'stok_tersedia'       => max(0, $tersedia),
                    'jumlah_baik'         => $rincian['baik'],
                    'jumlah_rusak_ringan' => $rincian['rusak_ringan'],
                    'jumlah_rusak_berat'  => $rincian['rusak_berat'],
                    'jumlah_kedaluwarsa'  => $rincian['kedaluwarsa'],
                    'satuan'              => $satuan,
                    'kondisi'             => $kondisi,
                    'tgl_kedaluwarsa'     => $tglVal,
                    'lokasi_lemari_rak'   => $rak ?: null,
                    'created_at'          => now(),
                    'updated_at'          => now(),
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
