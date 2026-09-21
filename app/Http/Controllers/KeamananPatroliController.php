<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RolePermission;

class KeamananPatroliController extends Controller
{
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
        $search = $request->get('search', '');
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
        $daftarSiswa = DB::table('peserta_didik')->select('peserta_didik_id', 'nama', 'nisn')->orderBy('nama')->limit(300)->get();
        $daftarGtk = DB::table('gtk')->select('ptk_id', 'nama', 'nip')->orderBy('nama')->get();

        return view('dashboard.keamanan.patroli', compact(
            'patroliList', 'insidenList', 'tab', 'search', 'tanggal', 'perPage',
            'daftarRuang', 'daftarSiswa', 'daftarGtk',
            'canCreate', 'canRead', 'canUpdate', 'canDelete', 'stats'
        ));
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

    public function storeInsiden(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jam_kejadian' => 'required',
            'lokasi_kejadian' => 'required|string|max:255',
            'judul_insiden' => 'required|string|max:255',
            'kronologi' => 'required|string',
        ]);

        $user = session('user');
        $ptkId = $user['ptk_id'] ?? null;
        $nomorLaporan = 'INS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        DB::table('keamanan_insiden')->insert([
            'nomor_laporan' => $nomorLaporan,
            'tanggal' => $request->tanggal,
            'jam_kejadian' => $request->jam_kejadian,
            'lokasi_kejadian' => $request->lokasi_kejadian,
            'judul_insiden' => $request->judul_insiden,
            'kronologi' => $request->kronologi,
            'pihak_terlibat' => $request->pihak_terlibat,
            'tingkat_urgensi' => $request->tingkat_urgensi ?? 'sedang',
            'tindakan_diambil' => $request->tindakan_diambil,
            'status_penyelesaian' => $request->status_penyelesaian ?? 'dalam_penanganan',
            'petugas_satpam_ptk_id' => $ptkId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.keamanan.patroli.index', ['tab' => 'insiden'])->with('success', 'Laporan insiden berhasil dicatat (' . $nomorLaporan . ').');
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
