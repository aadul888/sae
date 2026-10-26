<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;

class SarprasAsetController extends Controller
{
    use ChecksAuthenticatedSession;

    
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'create') || RolePermission::canAccess($user ?: $role, 'menu_inventaris', 'create') || RolePermission::canAccess($user ?: $role, 'menu_laboran', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'read') || RolePermission::canAccess($user ?: $role, 'menu_inventaris', 'read') || RolePermission::canAccess($user ?: $role, 'menu_laboran', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'update') || RolePermission::canAccess($user ?: $role, 'menu_inventaris', 'update') || RolePermission::canAccess($user ?: $role, 'menu_laboran', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'delete') || RolePermission::canAccess($user ?: $role, 'menu_inventaris', 'delete') || RolePermission::canAccess($user ?: $role, 'menu_laboran', 'delete');

        $tab = $request->query('tab', 'sarpras');
        if (!in_array($tab, ['sarpras', 'laboran'], true)) {
            $tab = 'sarpras';
        }

        $search         = trim($request->query('q', ''));
        $kategoriFilter = trim($request->query('kategori', ''));
        $kondisiFilter  = trim($request->query('kondisi', ''));
        $ruangFilter    = trim($request->query('ruang_id', ''));
        $jurusanFilter  = trim($request->query('jurusan_id', ''));
        $jenisFilter    = trim($request->query('jenis', ''));
        $labFilter      = trim($request->query('lab', ''));

        $jurusanList = DB::table('jurusan_meta')
            ->orderBy('nama_jurusan', 'asc')
            ->get(['jurusan_id', 'nama_jurusan', 'singkatan']);

        // Stats Sarpras
        $statTotal     = (int) (DB::table('sarpras_aset')->sum('jumlah') ?: 0);
        $statTersedia  = (int) (DB::table('sarpras_aset')->where('status_ketersediaan', 'tersedia')->sum('jumlah') ?: 0);
        $statDipinjam  = (int) (DB::table('sarpras_aset')->where('status_ketersediaan', 'dipinjam')->sum('jumlah') ?: 0);
        $statPerbaikan = (int) (DB::table('sarpras_aset')->whereIn('status_ketersediaan', ['perbaikan', 'rusak_berat'])->sum('jumlah') ?: 0);

        // Stats Laboran
        $statLabTotal  = (int) DB::table('laboran_bahan_alat')->count();
        $statLabAlat   = (int) DB::table('laboran_bahan_alat')->where('jenis', 'alat')->count();
        $statLabBahan  = (int) DB::table('laboran_bahan_alat')->where('jenis', 'bahan_habis_pakai')->count();
        $statLabKritis = (int) DB::table('laboran_bahan_alat')
            ->where(function ($q) {
                $q->where('stok_tersedia', '<=', 2)
                  ->orWhere('kondisi', '<>', 'baik');
            })->count();

        $kategoriList = ['Elektronik', 'Mebel/Furnitur', 'Kendaraan', 'Alat Peraga', 'Mesin', 'Lainnya'];
        $ruangList = DB::table('sarpras_ruang')->orderBy('nama_ruang', 'asc')->get(['id', 'nama_ruang', 'gedung']);

        $daftarLab = DB::table('sarpras_ruang')
            ->orderBy('nama_ruang', 'asc')
            ->pluck('nama_ruang')
            ->toArray();
        $existingLabs = DB::table('laboran_bahan_alat')
            ->whereNotNull('ruang_lab_nama')
            ->distinct()
            ->pluck('ruang_lab_nama')
            ->toArray();
        if (!empty($existingLabs)) {
            $daftarLab = array_values(array_unique(array_merge($daftarLab, $existingLabs)));
        }

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 15;

        $list = collect();
        $labList = collect();

        if ($tab === 'sarpras') {
            $query = DB::table('sarpras_aset as sa')
                ->leftJoin('sarpras_ruang as sr', 'sa.ruang_id', '=', 'sr.id')
                ->leftJoin('jurusan_meta as jm', 'sa.jurusan_id', '=', 'jm.jurusan_id')
                ->select('sa.*', 'sr.nama_ruang', 'sr.gedung', 'jm.nama_jurusan', 'jm.singkatan as jurusan_singkatan');

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('sa.kode_aset', 'like', "%{$search}%")
                      ->orWhere('sa.nama_barang', 'like', "%{$search}%")
                      ->orWhere('sa.merk_tipe', 'like', "%{$search}%")
                      ->orWhere('sr.nama_ruang', 'like', "%{$search}%")
                      ->orWhere('jm.nama_jurusan', 'like', "%{$search}%")
                      ->orWhere('jm.singkatan', 'like', "%{$search}%");
                });
            }

            if ($kategoriFilter !== '') {
                $query->where('sa.kategori', $kategoriFilter);
            }

            if ($kondisiFilter !== '') {
                $query->where('sa.kondisi', $kondisiFilter);
            }

            if ($ruangFilter !== '') {
                $query->where('sa.ruang_id', $ruangFilter);
            }

            if ($jurusanFilter !== '') {
                if ($jurusanFilter === 'umum') {
                    $query->whereNull('sa.jurusan_id');
                } else {
                    $query->where('sa.jurusan_id', $jurusanFilter);
                }
            }

            $list = $query->orderBy('sa.created_at', 'desc')->paginate($perPage)->withQueryString();
        } else {
            // Tab 2: Inventaris & Alat Praktik Jurusan (di-input Laboran)
            $labQuery = DB::table('laboran_bahan_alat as lba')
                ->leftJoin('jurusan_meta as jm', 'lba.jurusan_id', '=', 'jm.jurusan_id')
                ->select('lba.*', 'jm.nama_jurusan', 'jm.singkatan as jurusan_singkatan');

            if ($search !== '') {
                $labQuery->where(function ($q) use ($search) {
                    $q->where('lba.kode_item', 'like', "%{$search}%")
                      ->orWhere('lba.nama_item', 'like', "%{$search}%")
                      ->orWhere('lba.spesifikasi', 'like', "%{$search}%")
                      ->orWhere('lba.ruang_lab_nama', 'like', "%{$search}%")
                      ->orWhere('jm.nama_jurusan', 'like', "%{$search}%")
                      ->orWhere('jm.singkatan', 'like', "%{$search}%");
                });
            }

            if ($jurusanFilter !== '') {
                $labQuery->where('lba.jurusan_id', $jurusanFilter);
            }

            if ($jenisFilter !== '') {
                $labQuery->where('lba.jenis', $jenisFilter);
            }

            if ($kondisiFilter !== '') {
                $labQuery->where('lba.kondisi', $kondisiFilter);
            }

            if ($labFilter !== '') {
                $labQuery->where('lba.ruang_lab_nama', $labFilter);
            }

            $labList = $labQuery->orderBy('lba.nama_item', 'asc')->paginate($perPage)->withQueryString();
        }

        return view('dashboard.sarpras.aset', compact(
            'tab',
            'list',
            'labList',
            'search',
            'kategoriFilter',
            'kondisiFilter',
            'ruangFilter',
            'jurusanFilter',
            'jenisFilter',
            'labFilter',
            'kategoriList',
            'ruangList',
            'jurusanList',
            'daftarLab',
            'statTotal',
            'statTersedia',
            'statDipinjam',
            'statPerbaikan',
            'statLabTotal',
            'statLabAlat',
            'statLabBahan',
            'statLabKritis',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete',
            'perPage'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_aset' => 'required|string|max:50|unique:sarpras_aset,kode_aset',
            'nama_barang' => 'required|string|max:255',
            'kategori' => 'required|string|max:100',
            'merk_tipe' => 'nullable|string|max:100',
            'tahun_perolehan' => 'nullable|integer|min:1990|max:' . (date('Y') + 1),
            'sumber_dana' => 'required|string|max:50',
            'harga_perolehan' => 'nullable|numeric|min:0',
            'kondisi' => 'required|in:baik,rusak_ringan,rusak_berat',
            'ruang_id' => 'nullable|exists:sarpras_ruang,id',
            'jurusan_id' => 'nullable|string|max:50',
            'jumlah' => 'required|integer|min:1',
            'satuan' => 'required|string|max:30',
            'foto' => 'nullable|image|max:3072',
        ], [
            'kode_aset.unique' => 'Kode aset sudah digunakan.',
            'nama_barang.required' => 'Nama barang wajib diisi.',
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('sarpras/aset', 'public');
        }

        DB::table('sarpras_aset')->insert([
            'kode_aset' => strtoupper(trim($request->kode_aset)),
            'nama_barang' => trim($request->nama_barang),
            'kategori' => $request->kategori,
            'merk_tipe' => $request->merk_tipe,
            'no_seri_pabrik' => $request->no_seri_pabrik,
            'tahun_perolehan' => $request->tahun_perolehan,
            'sumber_dana' => $request->sumber_dana,
            'harga_perolehan' => $request->harga_perolehan ?? 0,
            'kondisi' => $request->kondisi,
            'ruang_id' => $request->ruang_id,
            'jurusan_id' => $request->filled('jurusan_id') ? trim($request->jurusan_id) : null,
            'jumlah' => $request->jumlah,
            'satuan' => $request->satuan,
            'status_ketersediaan' => 'tersedia',
            'foto' => $fotoPath,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Barang aset berhasil ditambahkan.']);
        }

        return redirect()->route('dashboard.sarpras.aset.index')->with('success', 'Barang aset berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'kode_aset' => 'required|string|max:50|unique:sarpras_aset,kode_aset,' . $id,
            'nama_barang' => 'required|string|max:255',
            'kategori' => 'required|string|max:100',
            'merk_tipe' => 'nullable|string|max:100',
            'tahun_perolehan' => 'nullable|integer|min:1990|max:' . (date('Y') + 1),
            'sumber_dana' => 'required|string|max:50',
            'harga_perolehan' => 'nullable|numeric|min:0',
            'kondisi' => 'required|in:baik,rusak_ringan,rusak_berat',
            'ruang_id' => 'nullable|exists:sarpras_ruang,id',
            'jurusan_id' => 'nullable|string|max:50',
            'jumlah' => 'required|integer|min:1',
            'satuan' => 'required|string|max:30',
            'status_ketersediaan' => 'required|in:tersedia,dipinjam,perbaikan,dihapuskan',
            'foto' => 'nullable|image|max:3072',
        ]);

        $data = [
            'kode_aset' => strtoupper(trim($request->kode_aset)),
            'nama_barang' => trim($request->nama_barang),
            'kategori' => $request->kategori,
            'merk_tipe' => $request->merk_tipe,
            'no_seri_pabrik' => $request->no_seri_pabrik,
            'tahun_perolehan' => $request->tahun_perolehan,
            'sumber_dana' => $request->sumber_dana,
            'harga_perolehan' => $request->harga_perolehan ?? 0,
            'kondisi' => $request->kondisi,
            'ruang_id' => $request->ruang_id,
            'jurusan_id' => $request->filled('jurusan_id') ? trim($request->jurusan_id) : null,
            'jumlah' => $request->jumlah,
            'satuan' => $request->satuan,
            'status_ketersediaan' => $request->status_ketersediaan,
            'updated_at' => now(),
        ];

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('sarpras/aset', 'public');
        }

        DB::table('sarpras_aset')->where('id', $id)->update($data);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Data aset berhasil diperbarui.']);
        }

        return redirect()->route('dashboard.sarpras.aset.index')->with('success', 'Data aset berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $aset = DB::table('sarpras_aset')->where('id', $id)->first();
        if ($aset && $aset->foto) {
            Storage::disk('public')->delete($aset->foto);
        }

        DB::table('sarpras_aset')->where('id', $id)->delete();

        return response()->json(['status' => 'success', 'message' => 'Data aset berhasil dihapus.']);
    }

    /**
     * Download format template Excel untuk import aset sarpras (Point 7).
     */
    public function templateExcel()
    {
        $headers = [
            'kode_aset',
            'nama_barang',
            'kategori',
            'merk_tipe',
            'no_seri_pabrik',
            'tahun_perolehan',
            'sumber_dana',
            'harga_perolehan',
            'kondisi',
            'nama_ruang',
            'jurusan',
            'jumlah',
            'satuan',
        ];

        $rows = [
            [
                'AST-SRP-001',
                'Proyektor Epson EB-X500',
                'Elektronik',
                'Epson EB-X500 3600 Lumens',
                'SN-EPS-9921',
                '2024',
                'BOS',
                '6500000',
                'baik',
                'Ruang Guru',
                '',
                '2',
                'unit',
            ],
            [
                'AST-SRP-002',
                'Genset Silent 5000 Watt',
                'Mesin & Utilitas',
                'Honda EU5000i',
                'SN-HND-5542',
                '2023',
                'DAK',
                '18000000',
                'baik',
                'Ruang Genset',
                '',
                '1',
                'unit',
            ],
            [
                'AST-SRP-003',
                'Server Rack 24U & Switch Gigabit',
                'Elektronik',
                'Cisco Catalyst 2960',
                'SN-CSC-1102',
                '2024',
                'BOS',
                '14500000',
                'baik',
                'Lab Komputer 1',
                'TKJ',
                '2',
                'unit',
            ],
            [
                'AST-SRP-004',
                'Kursi Siswa Kayu Jati',
                'Mebel',
                'Standar Kemdikbud',
                '',
                '2022',
                'Komite',
                '250000',
                'baik',
                'Kelas X-1',
                '',
                '36',
                'unit',
            ],
        ];

        $meta = [
            'title'    => 'Format Import Sarpras & Aset Sekolah',
            'subtitle' => 'Sistem Aplikasi Edukasi (SAE) — Diunduh ' . now()->translatedFormat('d F Y H:i'),
        ];

        $tempPath = storage_path('app/temp_template_aset_' . uniqid() . '.xlsx');
        \App\Services\SimpleXlsxExporter::export($tempPath, $meta, $headers, $rows, [
            'sheetName' => 'Aset Sarpras',
        ]);

        return response()->download($tempPath, 'Format_Import_Aset_Sarpras.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * Import aset sarpras via Excel (.xlsx, .xls, .csv) (Point 7).
     */
    public function importExcel(Request $request)
    {
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

        $ruangMap = DB::table('sarpras_ruang')->pluck('id', 'nama_ruang')->toArray();

        $jurusanMap = [];
        $jRows = DB::table('jurusan_meta')->get(['jurusan_id', 'nama_jurusan', 'singkatan']);
        foreach ($jRows as $jr) {
            $jurusanMap[strtolower(trim((string) $jr->jurusan_id))] = (string) $jr->jurusan_id;
            if ($jr->singkatan) {
                $jurusanMap[strtolower(trim((string) $jr->singkatan))] = (string) $jr->jurusan_id;
            }
            if ($jr->nama_jurusan) {
                $jurusanMap[strtolower(trim((string) $jr->nama_jurusan))] = (string) $jr->jurusan_id;
            }
        }

        $imported = 0;
        $updated = 0;

        foreach ($rows as $r) {
            $normalized = [];
            foreach ($r as $k => $v) {
                $cleanKey = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', (string) $k))));
                $normalized[$cleanKey] = is_string($v) ? trim($v) : $v;
            }

            $nama = $normalized['nama_barang'] ?? ($normalized['nama_aset'] ?? ($normalized['nama'] ?? ''));
            if (!$nama) {
                continue;
            }

            $kode = strtoupper((string) ($normalized['kode_aset'] ?? ($normalized['kode'] ?? '')));
            if (!$kode) {
                $kode = 'AST-' . strtoupper(substr(uniqid(), -6));
            }

            $kategori = (string) ($normalized['kategori'] ?? 'Lainnya');
            $merkTipe = (string) ($normalized['merk_tipe'] ?? ($normalized['merk'] ?? ''));
            $noSeri   = (string) ($normalized['no_seri_pabrik'] ?? ($normalized['no_seri'] ?? ''));
            $tahun    = !empty($normalized['tahun_perolehan']) ? (int) $normalized['tahun_perolehan'] : (!empty($normalized['tahun_pengadaan']) ? (int) $normalized['tahun_pengadaan'] : (int) date('Y'));
            $sumberDana = (string) ($normalized['sumber_dana'] ?? 'BOS');
            $harga    = (float) ($normalized['harga_perolehan'] ?? ($normalized['harga'] ?? 0));
            $jumlah   = max(1, (int) ($normalized['jumlah'] ?? 1));
            $satuan   = (string) ($normalized['satuan'] ?? 'unit');

            $rawKondisi = strtolower((string) ($normalized['kondisi'] ?? 'baik'));
            $kondisi = match ($rawKondisi) {
                'rusak_ringan', 'ringan' => 'rusak_ringan',
                'rusak_berat', 'berat'   => 'rusak_berat',
                default                  => 'baik',
            };

            $namaRuang = (string) ($normalized['nama_ruang'] ?? ($normalized['lokasi_ruang'] ?? ($normalized['ruang'] ?? '')));
            $ruangId   = null;
            if ($namaRuang !== '') {
                $ruangId = $ruangMap[$namaRuang] ?? null;
                if (!$ruangId) {
                    // Cari parsial
                    foreach ($ruangMap as $rNama => $rId) {
                        if (stripos($rNama, $namaRuang) !== false || stripos($namaRuang, $rNama) !== false) {
                            $ruangId = $rId;
                            break;
                        }
                    }
                }
            }

            $rawJurusan = strtolower(trim((string) ($normalized['jurusan'] ?? ($normalized['jurusan_id'] ?? ($normalized['program_keahlian'] ?? '')))));
            $jurusanId  = $rawJurusan !== '' ? ($jurusanMap[$rawJurusan] ?? null) : null;

            $existing = DB::table('sarpras_aset')->where('kode_aset', $kode)->first();

            if ($existing) {
                DB::table('sarpras_aset')->where('id', $existing->id)->update([
                    'nama_barang'         => $nama,
                    'kategori'            => $kategori,
                    'merk_tipe'           => $merkTipe ?: $existing->merk_tipe,
                    'no_seri_pabrik'      => $noSeri ?: $existing->no_seri_pabrik,
                    'tahun_perolehan'     => $tahun ?: $existing->tahun_perolehan,
                    'sumber_dana'         => $sumberDana,
                    'harga_perolehan'     => $harga ?: $existing->harga_perolehan,
                    'kondisi'             => $kondisi,
                    'ruang_id'            => $ruangId ?: $existing->ruang_id,
                    'jurusan_id'          => $jurusanId ?: $existing->jurusan_id,
                    'jumlah'              => $jumlah,
                    'satuan'              => $satuan,
                    'updated_at'          => now(),
                ]);
                $updated++;
            } else {
                DB::table('sarpras_aset')->insert([
                    'kode_aset'           => $kode,
                    'nama_barang'         => $nama,
                    'kategori'            => $kategori,
                    'merk_tipe'           => $merkTipe ?: null,
                    'no_seri_pabrik'      => $noSeri ?: null,
                    'tahun_perolehan'     => $tahun,
                    'sumber_dana'         => $sumberDana,
                    'harga_perolehan'     => $harga,
                    'kondisi'             => $kondisi,
                    'ruang_id'            => $ruangId,
                    'jurusan_id'          => $jurusanId,
                    'jumlah'              => $jumlah,
                    'satuan'              => $satuan,
                    'status_ketersediaan' => 'tersedia',
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
                $imported++;
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Import selesai: {$imported} aset baru ditambahkan, {$updated} aset diperbarui.",
            'count'   => $imported + $updated,
        ]);
    }
}
