<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Models\PerpusAnggota;
use App\Services\QrCodeService;
use App\Services\SimpleXlsxExporter;
use App\Services\SimpleXlsxReader;
use App\Support\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Import katalog buku (Excel/CSV) & input cepat via scan barcode.
 * Header yang dikenali: kode_buku, barcode, isbn, judul, penulis, penerbit, tahun_terbit,
 * klasifikasi_ddc, kategori, jumlah_eksemplar, lokasi_rak, tgl_pembelian, harga_perolehan, sumber_dana.
 */
class PerpusImportController extends Controller
{
    private const HEADER_ALIAS = [
        'kode_buku' => ['kode', 'kode_buku', 'no_inventaris', 'nomor_inventaris', 'inventaris'],
        'barcode' => ['barcode', 'qrcode', 'qr', 'kode_barcode'],
        'isbn' => ['isbn'],
        'judul' => ['judul', 'judul_buku', 'title', 'nama_buku'],
        'penulis' => ['penulis', 'pengarang', 'author'],
        'penerbit' => ['penerbit', 'publisher'],
        'tahun_terbit' => ['tahun', 'tahun_terbit', 'year'],
        'klasifikasi_ddc' => ['ddc', 'klasifikasi', 'klasifikasi_ddc', 'no_ddc'],
        'kategori' => ['kategori', 'kategori_buku', 'jenis'],
        'jumlah_eksemplar' => ['jumlah', 'jumlah_eksemplar', 'eksemplar', 'qty'],
        'lokasi_rak' => ['rak', 'lokasi', 'lokasi_rak', 'tempat'],
        'tgl_pembelian' => ['tgl_pembelian', 'tanggal_pembelian', 'tgl_beli', 'tgl_perolehan', 'tanggal_perolehan'],
        'harga_perolehan' => ['harga', 'harga_perolehan', 'harga_beli', 'nilai', 'harga_aset'],
        'sumber_dana' => ['sumber', 'sumber_dana'],
    ];

    public function form()
    {
        if ($r = $this->guard()) return $r;
        return view('dashboard.perpus.import');
    }

    /** Contoh template Excel untuk diisi pustakawan. */
    public function template()
    {
        if ($r = $this->guard()) return $r;

        $headers = ['kode_buku', 'barcode', 'isbn', 'judul', 'penulis', 'penerbit', 'tahun_terbit', 'klasifikasi_ddc', 'kategori', 'jumlah_eksemplar', 'lokasi_rak', 'tgl_pembelian', 'harga_perolehan', 'sumber_dana'];
        $rows = [[
            'BK-0001', '9786020123456', '978-602-012-345-6', 'Buku Contoh', 'Penulis Contoh', 'Penerbit Contoh',
            '2024', '510', 'Buku Pelajaran', '3', 'Rak A1', '2024-07-15', '85000', 'BOS',
        ]];

        $path = storage_path('app/template_import_katalog_perpus_' . now()->format('YmdHis') . '.xlsx');
        $ok = SimpleXlsxExporter::export(
            $path,
            ['title' => 'Template Import Katalog Perpustakaan', 'subtitle' => 'Isi mulai baris 2. Hapus baris contoh bila tidak dipakai.'],
            $headers,
            $rows,
            ['sheetName' => 'Katalog']
        );

        if ($ok && file_exists($path)) {
            return response()->download($path)->deleteFileAfterSend(true);
        }

        // Fallback CSV murni
        $csv = implode(',', $headers) . "\n" . implode(',', array_map(fn($v) => '"' . $v . '"', $rows[0])) . "\n";
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_import_katalog_perpus.csv"',
        ]);
    }

    public function import(Request $request)
    {
        if ($r = $this->guard()) return $r;

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:8192',
        ]);

        try {
            $rows = SimpleXlsxReader::readFile($request->file('file')->getRealPath());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca berkas: ' . $e->getMessage());
        }

        if (empty($rows)) {
            return back()->with('error', 'Berkas kosong atau format tidak dikenali.');
        }

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $mapped = $this->mapRow($row);

            if ($mapped['judul'] === '' || $mapped['kode_buku'] === '') {
                $skipped++;
                continue;
            }

            $kode = $mapped['kode_buku'];
            // kode_buku unik; pakai barcode sebagai kode bila kode kosong sudah difilter di atas
            $existing = DB::table('perpus_koleksi_buku')->where('kode_buku', $kode)->first();
            $eksemplar = max(1, (int) ($mapped['jumlah_eksemplar'] ?: 1));

            $payload = [
                'barcode' => $mapped['barcode'] ?: null,
                'isbn' => $mapped['isbn'] ?: null,
                'judul' => $mapped['judul'],
                'penulis' => $mapped['penulis'] ?: null,
                'penerbit' => $mapped['penerbit'] ?: null,
                'tahun_terbit' => $mapped['tahun_terbit'] ?: null,
                'klasifikasi_ddc' => $mapped['klasifikasi_ddc'] ?: null,
                'kategori' => $mapped['kategori'] ?: 'Umum',
                'jumlah_eksemplar' => $eksemplar,
                'lokasi_rak' => $mapped['lokasi_rak'] ?: null,
                'tgl_pembelian' => $this->normalizeDate($mapped['tgl_pembelian']),
                'harga_perolehan' => $this->normalizeMoney($mapped['harga_perolehan']),
                'sumber_dana' => $mapped['sumber_dana'] ?: null,
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('perpus_koleksi_buku')->where('id', $existing->id)->update($payload);
                $updated++;
            } else {
                $payload['kode_buku'] = $kode;
                $payload['eksemplar_tersedia'] = $eksemplar;
                $payload['created_at'] = now();
                DB::table('perpus_koleksi_buku')->insert($payload);
                $inserted++;
            }
        }

        return redirect()->route('dashboard.perpustakaan.koleksi.index')
            ->with('success', "Import selesai: {$inserted} buku baru, {$updated} diperbarui, {$skipped} dilewati.");
    }

    /** Input cepat satu buku via scan barcode / QR. */
    public function storeScan(Request $request)
    {
        if ($r = $this->guard()) return $r;

        $request->validate([
            'barcode' => 'required|string|max:80',
            'judul' => 'required|string|max:255',
            'jumlah_eksemplar' => 'nullable|integer|min:1',
        ]);

        $barcode = trim($request->barcode);
        if (DB::table('perpus_koleksi_buku')->where('barcode', $barcode)->exists()) {
            return response()->json(['success' => false, 'message' => 'Barcode sudah terdaftar pada buku lain.'], 422);
        }

        $eksemplar = max(1, (int) ($request->jumlah_eksemplar ?: 1));

        $id = DB::table('perpus_koleksi_buku')->insertGetId([
            'kode_buku' => $request->kode_buku ?: ('BK-' . strtoupper(Str::random(6))),
            'barcode' => $barcode,
            'isbn' => $request->isbn,
            'judul' => $request->judul,
            'penulis' => $request->penulis,
            'penerbit' => $request->penerbit,
            'tahun_terbit' => $request->tahun_terbit ?: null,
            'klasifikasi_ddc' => $request->klasifikasi_ddc,
            'kategori' => $request->kategori ?: 'Umum',
            'jumlah_eksemplar' => $eksemplar,
            'eksemplar_tersedia' => $eksemplar,
            'lokasi_rak' => $request->lokasi_rak,
            'tgl_pembelian' => $this->normalizeDate($request->tgl_pembelian),
            'harga_perolehan' => $this->normalizeMoney($request->harga_perolehan),
            'sumber_dana' => $request->sumber_dana,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'id' => $id,
            'barcode' => $barcode,
            'judul' => $request->judul,
            'message' => 'Buku baru berhasil dicatat dari barcode.',
        ]);
    }

    private function mapRow(array $row): array
    {
        $out = array_fill_keys(array_keys(self::HEADER_ALIAS), '');
        // Normalisasi kunci baris
        $norm = [];
        foreach ($row as $k => $v) {
            $key = strtolower(trim(preg_replace('/[^a-z0-9_]/i', '_', (string) $k), '_'));
            $key = preg_replace('/_+/', '_', $key);
            $norm[$key] = trim((string) $v);
        }

        foreach (self::HEADER_ALIAS as $target => $aliases) {
            foreach ($aliases as $alias) {
                if (!empty($norm[$alias])) {
                    $out[$target] = $norm[$alias];
                    break;
                }
            }
        }

        return $out;
    }

    private function normalizeDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') return null;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return $value;

        // Format Excel serial number
        if (is_numeric($value) && (int) $value > 20000) {
            $days = (int) $value;
            return gmdate('Y-m-d', ((int) $days - 25569) * 86400);
        }

        $ts = strtotime(str_replace('/', '-', $value));
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function normalizeMoney(?string $value): float
    {
        $value = (string) $value;
        // Buang pemisah ribuan, samakan desimal
        $clean = preg_replace('/[^0-9,.\-]/', '', $value);
        if ($clean === '') return 0.0;
        if (substr_count($clean, ',') === 1 && substr_count($clean, '.') > 0) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (substr_count($clean, ',') === 1 && substr_count($clean, '.') === 0) {
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace(',', '', $clean);
        }
        return (float) $clean;
    }

    private function guard()
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $can = RolePermission::canAccess($user ?: 'tendik', 'menu_perpustakaan', 'read')
            || RolePermission::canAccess($user ?: 'tendik', 'menu_perpustakaan_koleksi', 'read');

        if (!$can && !in_array($role, ['admin', 'tendik', 'pustakawan'], true)) {
            return redirect()->route('dashboard.' . $role)->with('error', 'Anda tidak memiliki hak akses ke Katalog Perpustakaan.');
        }
        return null;
    }
}
