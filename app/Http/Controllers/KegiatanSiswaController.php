<?php

namespace App\Http\Controllers;

use App\Models\KegiatanAgenda;
use App\Models\KegiatanEkskul;
use App\Models\KegiatanEkskulAnggota;
use App\Models\KegiatanOrganisasi;
use App\Models\KegiatanOrganisasiAnggota;
use App\Models\PesertaDidik;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KegiatanSiswaController extends Controller
{
    private function checkAuth()
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }
        return null;
    }

    /**
     * Tampilkan Halaman Kluster Kegiatan Siswa (OSIS, Organisasi, Ekstrakurikuler, Agenda).
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_agenda', 'create') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_agenda', 'read') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_agenda', 'update') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_agenda', 'delete') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'delete');

        $activeTab = $request->get('tab', 'osis'); // osis, organisasi, ekskul, agenda
        $q = trim($request->get('q', ''));
        $kategori = trim($request->get('kategori', ''));

        // 1. Statistik Ringkas
        $totalOsis = KegiatanOrganisasi::where('jenis', 'osis')->count();
        $totalOrganisasi = KegiatanOrganisasi::where('jenis', '<>', 'osis')->count();
        $totalEkskul = KegiatanEkskul::where('is_active', true)->count();
        $totalAgenda = KegiatanAgenda::count();

        $stats = [
            'total_osis'       => $totalOsis,
            'total_organisasi' => $totalOrganisasi,
            'total_ekskul'     => $totalEkskul,
            'total_agenda'     => $totalAgenda,
        ];

        // 2. Tab: OSIS
        $osis = KegiatanOrganisasi::with(['ketua', 'pembina', 'anggota.siswa'])
            ->where('jenis', 'osis')
            ->orderBy('masa_bakti', 'desc')
            ->first();

        // 3. Tab: Organisasi Lain (MPK, Pramuka, PMR, Rohis, Paskibra)
        $organisasiQuery = KegiatanOrganisasi::with(['ketua', 'pembina', 'anggota'])
            ->where('jenis', '<>', 'osis')
            ->orderBy('nama_organisasi');
        if ($q !== '' && $activeTab === 'organisasi') {
            $organisasiQuery->where('nama_organisasi', 'like', "%{$q}%");
        }
        $organisasiList = $organisasiQuery->get();

        // 4. Tab: Ekstrakurikuler
        $ekskulQuery = KegiatanEkskul::with(['pembina', 'anggota.siswa'])
            ->orderBy('kategori')->orderBy('nama_ekskul');
        if ($q !== '' && $activeTab === 'ekskul') {
            $ekskulQuery->where('nama_ekskul', 'like', "%{$q}%")
                ->orWhere('kategori', 'like', "%{$q}%");
        }
        if ($kategori !== '') {
            $ekskulQuery->where('kategori', $kategori);
        }
        $ekskulList = $ekskulQuery->get();

        $perPageVal = $request->input('perPage', $request->input('per_page', 25));
        $perPage = in_array((int)$perPageVal, [10, 15, 25, 50, 100], true) ? (int)$perPageVal : 25;

        // 5. Tab: Agenda Kegiatan Siswa
        $agendaQuery = KegiatanAgenda::with(['organisasi', 'ekskul'])
            ->orderBy('tanggal_mulai', 'desc');
        if ($q !== '' && $activeTab === 'agenda') {
            $agendaQuery->where('judul_kegiatan', 'like', "%{$q}%")
                ->orWhere('tempat', 'like', "%{$q}%");
        }
        $agendaList = $agendaQuery->paginate($perPage, ['*'], 'agenda_page')->withQueryString();

        // Master Siswa & GTK untuk modal
        $siswaList = PesertaDidik::orderBy('nama')->limit(300)->get(['peserta_didik_id', 'nama', 'nisn', 'nipd']);
        $pembinaList = DB::table('gtk')->orderBy('nama')->get(['ptk_id', 'nama']);

        return view('dashboard.kesiswaan.kegiatan', compact(
            'stats',
            'activeTab',
            'q',
            'kategori',
            'perPage',
            'osis',
            'organisasiList',
            'ekskulList',
            'agendaList',
            'siswaList',
            'pembinaList',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete'
        ));
    }

    /**
     * Simpan Data Organisasi Kesiswaan Baru (OSIS, MPK, Pramuka, dll).
     */
    public function storeOrganisasi(Request $request)
    {
        $validated = $request->validate([
            'jenis'                  => 'required|string|in:osis,mpk,pramuka,pmr,rohis,paskibra,lainnya',
            'nama_organisasi'        => 'required|string|max:190',
            'masa_bakti'             => 'required|string|max:30',
            'ketua_peserta_didik_id' => 'nullable|string',
            'pembina_ptk_id'         => 'nullable|string',
            'visi_misi'              => 'nullable|string',
            'logo_path'              => 'nullable|image|max:2048',
        ]);

        $filePath = null;
        if ($request->hasFile('logo_path')) {
            $file = $request->file('logo_path');
            $fileName = 'org_' . time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/organisasi'), $fileName);
            $filePath = '/uploads/organisasi/' . $fileName;
        }

        $org = KegiatanOrganisasi::create([
            'jenis'                  => $validated['jenis'],
            'nama_organisasi'        => $validated['nama_organisasi'],
            'masa_bakti'             => $validated['masa_bakti'],
            'ketua_peserta_didik_id' => $validated['ketua_peserta_didik_id'] ?? null,
            'pembina_ptk_id'         => $validated['pembina_ptk_id'] ?? null,
            'visi_misi'              => $validated['visi_misi'] ?? null,
            'logo_path'              => $filePath,
            'is_active'              => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Organisasi {$org->nama_organisasi} berhasil disimpan.",
            'data' => $org,
        ]);
    }

    /**
     * Tambah Anggota Pengurus Organisasi.
     */
    public function storeAnggotaOrganisasi(Request $request)
    {
        $validated = $request->validate([
            'organisasi_id'    => 'required|exists:kegiatan_organisasi,id',
            'peserta_didik_id' => 'required|string',
            'jabatan'          => 'required|string|max:100',
        ]);

        $anggota = KegiatanOrganisasiAnggota::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Anggota pengurus berhasil ditambahkan.',
            'data' => $anggota,
        ]);
    }

    /**
     * Simpan Data Ekstrakurikuler Baru.
     */
    public function storeEkskul(Request $request)
    {
        $validated = $request->validate([
            'nama_ekskul'    => 'required|string|max:190',
            'kategori'       => 'required|string|in:olahraga,seni,keagamaan,bela_diri,sains,teknologi',
            'pembina_ptk_id' => 'nullable|string',
            'pelatih_nama'   => 'nullable|string|max:190',
            'jadwal_hari'    => 'nullable|string|max:50',
            'jam_mulai'      => 'nullable',
            'jam_selesai'    => 'nullable',
            'tempat'         => 'nullable|string|max:190',
            'deskripsi'      => 'nullable|string',
        ]);

        $ekskul = KegiatanEkskul::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => "Ekstrakurikuler {$ekskul->nama_ekskul} berhasil ditambahkan.",
            'data' => $ekskul,
        ]);
    }

    /**
     * Tambah Anggota Peserta Ekstrakurikuler.
     */
    public function storeAnggotaEkskul(Request $request)
    {
        $validated = $request->validate([
            'ekskul_id'        => 'required|exists:kegiatan_ekskul,id',
            'peserta_didik_id' => 'required|string',
            'nomor_anggota'    => 'nullable|string|max:50',
        ]);

        $anggota = KegiatanEkskulAnggota::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Siswa berhasil didaftarkan ke ekstrakurikuler.',
            'data' => $anggota,
        ]);
    }

    /**
     * Simpan Agenda Kegiatan Siswa.
     */
    public function storeAgenda(Request $request)
    {
        $validated = $request->validate([
            'judul_kegiatan'   => 'required|string|max:255',
            'jenis_kegiatan'   => 'required|string|in:internal,eksternal,lomba,upacara,bakti_sosial',
            'tanggal_mulai'    => 'required|date',
            'tanggal_selesai'  => 'nullable|date|after_or_equal:tanggal_mulai',
            'tempat'           => 'nullable|string|max:190',
            'penanggung_jawab' => 'nullable|string|max:190',
            'anggaran'         => 'nullable|numeric|min:0',
            'status'           => 'required|string|in:rencana,berlangsung,selesai,dibatalkan',
            'organisasi_id'    => 'nullable|exists:kegiatan_organisasi,id',
            'ekskul_id'        => 'nullable|exists:kegiatan_ekskul,id',
        ]);

        $agenda = KegiatanAgenda::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => "Agenda kegiatan '{$agenda->judul_kegiatan}' berhasil disimpan.",
            'data' => $agenda,
        ]);
    }
}
