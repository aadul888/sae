<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RolePermission;
use App\Services\KeamananImportService;

class KeamananPatroliController extends Controller
{
    use \App\Http\Controllers\Concerns\CetakKeamanan;

    /** Unduh template Excel import Patroli. */
    public function templateExcel(KeamananImportService $service)
    {
        $path = $service->generateTemplateFile('patroli');

        return response()->download($path, 'Format_Import_Patroli.xlsx')->deleteFileAfterSend(true);
    }

    /** Import Log Patroli via Excel. */
    public function importExcel(Request $request, KeamananImportService $service)
    {
        $request->validate(['file' => 'required|file|max:10240']);

        try {
            $hasil = $service->import('patroli', $request->file('file'));
            $pesan = "{$hasil['imported']} log patroli berhasil diimpor" . ($hasil['skipped'] > 0 ? ", {$hasil['skipped']} baris dilewati." : '.');

            return response()->json(['status' => 'success', 'message' => $pesan]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function index(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'tendik';

        $canCreate = RolePermission::canAccess($user, 'menu_keamanan', 'create');
        $canRead = RolePermission::canAccess($user, 'menu_keamanan', 'read');
        $canUpdate = RolePermission::canAccess($user, 'menu_keamanan', 'update');
        $canDelete = RolePermission::canAccess($user, 'menu_keamanan', 'delete');

        if (!$canRead && !in_array($role, ['admin', 'tendik', 'satpam'])) {
            return redirect()->route('dashboard.' . $role)->with('error', 'Anda tidak memiliki hak akses ke Patroli & Insiden.');
        }

        $tab = $request->get('tab', 'patroli');
        $search = $request->get('q', $request->get('search', ''));
        $tanggal = $request->get('tanggal', '');
        $perPage = (int) $request->get('perPage', 15);

        // Tab 1: Patroli
        $queryPatroli = DB::table('keamanan_patroli as kp')
            ->leftJoin('gtk', 'kp.petugas_satpam_ptk_id', '=', 'gtk.ptk_id')
            ->select('kp.*', 'gtk.nama as nama_petugas');

        if ($tanggal) {
            $queryPatroli->where('kp.tanggal', $tanggal);
        }
        if (!empty($search) && $tab === 'patroli') {
            $queryPatroli->where(function ($q) use ($search) {
                $q->where('kp.rute_zona', 'like', "%{$search}%")
                    ->orWhere('kp.catatan_temuan', 'like', "%{$search}%")
                    ->orWhere('gtk.nama', 'like', "%{$search}%");
            });
        }
        $patroliList = $queryPatroli->orderBy('kp.tanggal', 'desc')->orderBy('kp.jam_patroli', 'desc')->paginate($perPage, ['*'], 'patroli_page');

        // Tab 2: Insiden
        $queryInsiden = DB::table('keamanan_insiden as ki')
            ->leftJoin('gtk', 'ki.petugas_satpam_ptk_id', '=', 'gtk.ptk_id')
            ->select('ki.*', 'gtk.nama as nama_petugas');

        if ($tanggal) {
            $queryInsiden->where('ki.tanggal', $tanggal);
        }
        if (!empty($search) && $tab === 'insiden') {
            $queryInsiden->where(function ($q) use ($search) {
                $q->where('ki.nomor_laporan', 'like', "%{$search}%")
                    ->orWhere('ki.judul_insiden', 'like', "%{$search}%")
                    ->orWhere('ki.lokasi_kejadian', 'like', "%{$search}%")
                    ->orWhere('ki.kronologi', 'like', "%{$search}%");
            });
        }
        $insidenList = $queryInsiden->orderBy('ki.tanggal', 'desc')->orderBy('ki.jam_kejadian', 'desc')->paginate($perPage, ['*'], 'insiden_page');

        $stats = [
            'total_patroli' => DB::table('keamanan_patroli')->count(),
            'patroli_hari_ini' => DB::table('keamanan_patroli')->where('tanggal', date('Y-m-d'))->count(),
            'total_insiden' => DB::table('keamanan_insiden')->count(),
            'insiden_aktif' => DB::table('keamanan_insiden')->where('status_penyelesaian', 'dalam_penanganan')->count(),
        ];

        $daftarRuang = DB::table('sarpras_ruang')
            ->select('id', 'kode_ruang', 'nama_ruang', 'gedung', 'lantai')
            ->orderBy('gedung', 'asc')
            ->orderBy('nama_ruang', 'asc')
            ->get();

        $daftarTatib = DB::table('kedisiplinan_tata_tertib')
            ->where('is_active', true)
            ->orderBy('bobot_poin')
            ->get();

        return view('dashboard.keamanan.patroli', compact(
            'patroliList', 'insidenList', 'tab', 'search', 'tanggal', 'perPage',
            'daftarRuang', 'daftarTatib',
            'canCreate', 'canRead', 'canUpdate', 'canDelete', 'stats'
        ));
    }

    /** Halaman cetak Log Patroli (ikut filter aktif halaman). */
    public function cetak(Request $request)
    {
        $search = $request->get('q', $request->get('search', ''));
        $tanggal = $request->get('tanggal', '');

        $query = DB::table('keamanan_patroli as kp')
            ->leftJoin('gtk', 'kp.petugas_satpam_ptk_id', '=', 'gtk.ptk_id')
            ->select('kp.*', 'gtk.nama as nama_petugas');

        $periode = $this->periodeDariRequest($request);

        if ($periode) {
            $query->whereBetween('kp.tanggal', [$periode['start'], $periode['end']]);
        } elseif ($tanggal) {
            $query->where('kp.tanggal', $tanggal);
        }
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('kp.rute_zona', 'like', "%{$search}%")
                    ->orWhere('kp.catatan_temuan', 'like', "%{$search}%")
                    ->orWhere('gtk.nama', 'like', "%{$search}%");
            });
        }

        $rows = $query->orderBy('kp.tanggal', 'desc')->orderBy('kp.jam_patroli', 'desc')->limit(1000)->get();

        $filterInfo = ($periode ? 'Periode: ' . $periode['label'] : 'Tanggal: ' . ($tanggal ?: 'Semua'))
            . ($search ? " • Pencarian: \"{$search}\"" : '');

        return $this->renderCetak('patroli', 'Laporan Log Patroli Keliling', $filterInfo, $rows);
    }

    /** Halaman cetak Laporan Insiden (ikut filter aktif halaman). */
    public function cetakInsiden(Request $request)
    {
        $search = $request->get('q', $request->get('search', ''));
        $tanggal = $request->get('tanggal', '');

        $query = DB::table('keamanan_insiden as ki')
            ->leftJoin('gtk', 'ki.petugas_satpam_ptk_id', '=', 'gtk.ptk_id')
            ->select('ki.*', 'gtk.nama as nama_petugas');

        $periode = $this->periodeDariRequest($request);

        if ($periode) {
            $query->whereBetween('ki.tanggal', [$periode['start'], $periode['end']]);
        } elseif ($tanggal) {
            $query->where('ki.tanggal', $tanggal);
        }
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('ki.nomor_laporan', 'like', "%{$search}%")
                    ->orWhere('ki.judul_insiden', 'like', "%{$search}%")
                    ->orWhere('ki.lokasi_kejadian', 'like', "%{$search}%")
                    ->orWhere('ki.kronologi', 'like', "%{$search}%");
            });
        }

        $rows = $query->orderBy('ki.tanggal', 'desc')->orderBy('ki.jam_kejadian', 'desc')->limit(1000)->get();

        $filterInfo = ($periode ? 'Periode: ' . $periode['label'] : 'Tanggal: ' . ($tanggal ?: 'Semua'))
            . ($search ? " • Pencarian: \"{$search}\"" : '');

        return $this->renderCetak('insiden', 'Laporan Insiden / Kejadian Khusus', $filterInfo, $rows);
    }

    public function storePatroli(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jam_patroli' => 'required',
            'rute_zona' => 'required|string|max:100',
            'kondisi_lingkungan' => 'required|string',
        ]);

        $user = session('user');
        $ptkId = $user['ptk_id'] ?? null;

        DB::table('keamanan_patroli')->insert([
            'tanggal' => $request->tanggal,
            'jam_patroli' => $request->jam_patroli,
            'rute_zona' => $request->rute_zona,
            'kondisi_lingkungan' => $request->kondisi_lingkungan,
            'catatan_temuan' => $request->catatan_temuan,
            'petugas_satpam_ptk_id' => $ptkId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.keamanan.patroli.index', ['tab' => 'patroli'])->with('success', 'Log patroli berhasil ditambahkan.');
    }

    public function updatePatroli(Request $request, $id)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jam_patroli' => 'required',
            'rute_zona' => 'required|string|max:100',
            'kondisi_lingkungan' => 'required|string',
        ]);

        DB::table('keamanan_patroli')->where('id', $id)->update([
            'tanggal' => $request->tanggal,
            'jam_patroli' => $request->jam_patroli,
            'rute_zona' => $request->rute_zona,
            'kondisi_lingkungan' => $request->kondisi_lingkungan,
            'catatan_temuan' => $request->catatan_temuan,
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.keamanan.patroli.index', ['tab' => 'patroli'])->with('success', 'Log patroli berhasil diperbarui.');
    }

    public function destroyPatroli($id)
    {
        DB::table('keamanan_patroli')->where('id', $id)->delete();
        return redirect()->route('dashboard.keamanan.patroli.index', ['tab' => 'patroli'])->with('success', 'Log patroli berhasil dihapus.');
    }

    /** Unduh template Excel import Insiden. */
    public function templateInsiden(KeamananImportService $service)
    {
        $path = $service->generateTemplateFile('insiden');

        return response()->download($path, 'Format_Import_Insiden.xlsx')->deleteFileAfterSend(true);
    }

    /** Import Laporan Insiden via Excel. */
    public function importInsiden(Request $request, KeamananImportService $service)
    {
        $request->validate(['file' => 'required|file|max:10240']);

        try {
            $hasil = $service->import('insiden', $request->file('file'));
            $pesan = "{$hasil['imported']} laporan insiden berhasil diimpor" . ($hasil['skipped'] > 0 ? ", {$hasil['skipped']} baris dilewati." : '.');

            return response()->json(['status' => 'success', 'message' => $pesan]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function storeInsiden(Request $request)
    {
        $request->validate([
            'tanggal'          => 'required|date',
            'jam_kejadian'     => 'required',
            'lokasi_kejadian'  => 'required|string|max:255',
            'judul_insiden'    => 'required|string|max:255',
            'kronologi'        => 'required|string',
            'kategori_insiden' => 'nullable|string|max:50',
            'peserta_didik_id' => 'nullable|exists:peserta_didik,peserta_didik_id',
            'tata_tertib_id'   => 'nullable|exists:kedisiplinan_tata_tertib,id',
        ]);

        $user = session('user');
        $ptkId = $user['ptk_id'] ?? null;
        $nomorLaporan = 'INS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        $pelanggaranId = null;
        $kategoriInsiden = $request->kategori_insiden ?: ($request->filled('peserta_didik_id') ? 'siswa_kbm' : 'umum');

        // Jika insiden melibatkan siswa luar saat KBM / tata tertib, otomatis rekam pelanggaran poin
        if ($request->filled('peserta_didik_id') && $request->filled('tata_tertib_id')) {
            $tatib = DB::table('kedisiplinan_tata_tertib')->where('id', $request->tata_tertib_id)->first();
            $namaPelapor = ($user['nama'] ?? 'Petugas Satpam') . ' (Patroli Keamanan)';

            $pelanggaranId = DB::table('kedisiplinan_pelanggaran')->insertGetId([
                'peserta_didik_id'     => $request->peserta_didik_id,
                'tata_tertib_id'       => $tatib->id,
                'tanggal_kejadian'     => $request->tanggal,
                'tempat_kejadian'      => $request->lokasi_kejadian,
                'poin'                 => $tatib ? $tatib->bobot_poin : 10,
                'keterangan'           => 'Temuan Patroli Keamanan: ' . $request->judul_insiden . ' - ' . $request->kronologi,
                'pelapor_ptk_id'       => $ptkId,
                'pelapor_nama'         => $namaPelapor,
                'status_tindak_lanjut' => 'pending',
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }

        DB::table('keamanan_insiden')->insert([
            'nomor_laporan'         => $nomorLaporan,
            'tanggal'               => $request->tanggal,
            'jam_kejadian'          => $request->jam_kejadian,
            'lokasi_kejadian'       => $request->lokasi_kejadian,
            'judul_insiden'         => $request->judul_insiden,
            'kategori_insiden'      => $kategoriInsiden,
            'kronologi'             => $request->kronologi,
            'pihak_terlibat'        => $request->pihak_terlibat,
            'peserta_didik_id'      => $request->peserta_didik_id,
            'tata_tertib_id'        => $request->tata_tertib_id,
            'pelanggaran_id'        => $pelanggaranId,
            'tingkat_urgensi'       => $request->tingkat_urgensi ?? 'sedang',
            'tindakan_diambil'      => $request->tindakan_diambil,
            'status_penyelesaian'   => $request->status_penyelesaian ?? 'dalam_penanganan',
            'petugas_satpam_ptk_id' => $ptkId,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        $msg = 'Laporan insiden berhasil dicatat (' . $nomorLaporan . ').';
        if ($pelanggaranId) {
            $msg .= ' Pelanggaran disiplin siswa telah otomatis diteruskan ke Tim Kesiswaan.';
        }

        return redirect()->route('dashboard.keamanan.patroli.index', ['tab' => 'insiden'])->with('success', $msg);
    }

    public function updateInsiden(Request $request, $id)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jam_kejadian' => 'required',
            'lokasi_kejadian' => 'required|string|max:255',
            'judul_insiden' => 'required|string|max:255',
            'kronologi' => 'required|string',
        ]);

        DB::table('keamanan_insiden')->where('id', $id)->update([
            'tanggal' => $request->tanggal,
            'jam_kejadian' => $request->jam_kejadian,
            'lokasi_kejadian' => $request->lokasi_kejadian,
            'judul_insiden' => $request->judul_insiden,
            'kronologi' => $request->kronologi,
            'pihak_terlibat' => $request->pihak_terlibat,
            'tingkat_urgensi' => $request->tingkat_urgensi ?? 'sedang',
            'tindakan_diambil' => $request->tindakan_diambil,
            'status_penyelesaian' => $request->status_penyelesaian ?? 'dalam_penanganan',
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.keamanan.patroli.index', ['tab' => 'insiden'])->with('success', 'Laporan insiden berhasil diperbarui.');
    }

    public function destroyInsiden($id)
    {
        DB::table('keamanan_insiden')->where('id', $id)->delete();
        return redirect()->route('dashboard.keamanan.patroli.index', ['tab' => 'insiden'])->with('success', 'Laporan insiden berhasil dihapus.');
    }
}
