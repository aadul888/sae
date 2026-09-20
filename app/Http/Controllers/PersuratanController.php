<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\RolePermission;
use App\Models\Persuratan;

class PersuratanController extends Controller
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
     * Tampilkan halaman utama modul Persuratan & Arsip Digital.
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        // Evaluasi granular hak akses CRUD
        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'delete');

        // Statistik real-time dari database
        $stats = [
            'total'     => Persuratan::count(),
            'masuk'     => Persuratan::where('jenis_surat', 'masuk')->count(),
            'keluar'    => Persuratan::where('jenis_surat', 'keluar')->count(),
            'pending'   => Persuratan::where('status', 'menunggu_disposisi')->count(),
        ];

        $q           = trim($request->get('q', ''));
        $status      = $request->get('status', '');
        $jenisSurat  = $request->get('jenis_surat', '');
        $sort        = $request->get('sort', 'tanggal_surat');
        $sortDir     = strtolower($request->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPageVal  = $request->get('per_page', '25');
        $perPage     = in_array($perPageVal, ['10', '25', '50', '100']) ? (int)$perPageVal : 25;

        $query = Persuratan::query();

        if ($q !== '') {
            $query->where(function ($b) use ($q) {
                $b->where('nomor_surat', 'like', "%{$q}%")
                  ->orWhere('perihal', 'like', "%{$q}%")
                  ->orWhere('pengirim_asal', 'like', "%{$q}%")
                  ->orWhere('tujuan_penerima', 'like', "%{$q}%");
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($jenisSurat !== '') {
            $query->where('jenis_surat', $jenisSurat);
        }

        $allowedSorts = ['nomor_surat', 'jenis_surat', 'tanggal_surat', 'status', 'created_at'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $sortDir);
        } else {
            $query->orderBy('tanggal_surat', 'desc');
        }

        $items = $query->paginate($perPage)->withQueryString();

        $siswaList = \App\Models\PesertaDidik::orderBy('nama')
            ->limit(300)
            ->get(['peserta_didik_id', 'nama', 'nisn', 'nipd']);

        return view('dashboard.persuratan', compact(
            'stats',
            'items',
            'q',
            'status',
            'jenisSurat',
            'sort',
            'sortDir',
            'perPage',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete',
            'siswaList'
        ));
    }

    /**
     * Simpan surat baru (Create).
     */
    public function store(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'create')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menambah data surat.'], 403);
            }
            return back()->with('error', 'Akses ditolak: Anda tidak memiliki hak akses untuk menambah data.');
        }

        $validated = $request->validate([
            'nomor_surat'      => 'required|string|max:190',
            'jenis_surat'      => 'required|string|in:masuk,keluar,disposisi,keputusan,tugas',
            'perihal'          => 'required|string|max:255',
            'pengirim_asal'    => 'nullable|string|max:190',
            'tujuan_penerima'  => 'nullable|string|max:190',
            'tanggal_surat'    => 'required|date',
            'tanggal_diterima'  => 'nullable|date',
            'status'           => 'required|string|in:draf,menunggu_disposisi,diproses,selesai,diarsipkan',
            'keterangan'       => 'nullable|string',
        ]);

        $userName = is_array($user)
            ? ($user['nama'] ?? ($user['name'] ?? 'Staf'))
            : ($user->nama ?? ($user->name ?? 'Staf'));

        $validated['created_by'] = $userName;

        $item = Persuratan::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Surat berhasil dicatat ke sistem persuratan.',
                'data' => $item,
            ]);
        }

        return back()->with('success', "Surat nomor {$item->nomor_surat} berhasil disimpan.");
    }

    /**
     * Tampilkan detail surat (Read / Detail).
     */
    public function show(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'read')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $item = Persuratan::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $item,
            'status_badge' => $item->status_badge,
            'jenis_badge' => $item->jenis_badge,
        ]);
    }

    /**
     * Perbarui data surat (Update).
     */
    public function update(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'update')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk mengubah data surat.'], 403);
            }
            return back()->with('error', 'Akses ditolak: Anda tidak memiliki hak akses untuk mengubah data.');
        }

        $item = Persuratan::findOrFail($id);

        $validated = $request->validate([
            'nomor_surat'      => 'required|string|max:190',
            'jenis_surat'      => 'required|string|in:masuk,keluar,disposisi,keputusan,tugas',
            'perihal'          => 'required|string|max:255',
            'pengirim_asal'    => 'nullable|string|max:190',
            'tujuan_penerima'  => 'nullable|string|max:190',
            'tanggal_surat'    => 'required|date',
            'tanggal_diterima'  => 'nullable|date',
            'status'           => 'required|string|in:draf,menunggu_disposisi,diproses,selesai,diarsipkan',
            'keterangan'       => 'nullable|string',
        ]);

        $item->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Data surat berhasil diperbarui.',
                'data' => $item,
            ]);
        }

        return back()->with('success', "Surat nomor {$item->nomor_surat} berhasil diperbarui.");
    }

    /**
     * Hapus arsip surat (Delete).
     */
    public function destroy(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'delete')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menghapus arsip surat.'], 403);
            }
            return back()->with('error', 'Akses ditolak: Anda tidak memiliki hak akses untuk menghapus arsip surat.');
        }

        $item = Persuratan::findOrFail($id);
        $nomor = $item->nomor_surat;
        $item->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Arsip surat nomor {$nomor} berhasil dihapus.",
            ]);
        }

        return back()->with('success', "Arsip surat nomor {$nomor} berhasil dihapus.");
    }

    /**
     * Simpan lembar disposisi surat masuk.
     */
    public function disposisi(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $surat = Persuratan::findOrFail($id);

        $validated = $request->validate([
            'disposisi_dari'      => 'nullable|string|max:190',
            'disposisi_ke'        => 'nullable|string|max:190',
            'instruksi'           => 'required|string|max:190',
            'catatan'             => 'nullable|string',
            'tanggal_disposisi'   => 'required|date',
            'update_status_surat' => 'nullable|string|in:diproses,selesai,menunggu_disposisi',
        ]);

        $disp = \App\Models\PersuratanDisposisi::create([
            'persuratan_id'     => $surat->id,
            'disposisi_dari'    => $validated['disposisi_dari'] ?: 'Kepala Sekolah',
            'disposisi_ke'      => $validated['disposisi_ke'],
            'instruksi'         => $validated['instruksi'],
            'catatan'           => $validated['catatan'],
            'tanggal_disposisi' => $validated['tanggal_disposisi'],
            'status'            => 'diproses',
        ]);

        if (!empty($validated['update_status_surat'])) {
            $surat->update(['status' => $validated['update_status_surat']]);
        } else {
            $surat->update(['status' => 'diproses']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Lembar disposisi berhasil disimpan.',
            'data' => $disp,
        ]);
    }

    /**
     * Cetak Lembar Disposisi Resmi ber-Kop Surat Sekolah (Format A4 Jadwal KBM).
     */
    public function cetakDisposisi(Request $request, $id)
    {
        $surat = Persuratan::findOrFail($id);
        $sekolah = \Illuminate\Support\Facades\DB::table('sekolah')->first();
        $sekolahMeta = \App\Models\SekolahMeta::first();

        $orientasi = in_array(strtolower($request->get('orientasi', 'portrait')), ['portrait', 'landscape'])
            ? strtolower($request->get('orientasi', 'portrait'))
            : 'portrait';

        // Ambil data disposisi terakhir jika ada
        $disposisi = \App\Models\PersuratanDisposisi::where('persuratan_id', $surat->id)->latest()->first();

        $disposisiTujuan = $disposisi ? explode(',', $disposisi->disposisi_ke) : [];
        $disposisiTujuan = array_map('trim', $disposisiTujuan);
        $disposisiTujuanLain = $disposisi?->disposisi_ke;
        $disposisiInstruksi = $disposisi?->instruksi ?: 'Tanggapi / Tindak Lanjuti';
        $disposisiCatatan = $disposisi?->catatan;

        $kepsek = \Illuminate\Support\Facades\DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                    ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        $kepalaTas = \Illuminate\Support\Facades\DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->join('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
            ->where('rtt.kode', 'KEPALA_TAS')
            ->where('ptt.is_active', true)
            ->select('gtk.nama', 'gtk.nip', 'gtk.nuptk')
            ->first();

        return view('dashboard.persuratan.cetak-disposisi', compact(
            'surat',
            'sekolah',
            'sekolahMeta',
            'orientasi',
            'disposisi',
            'disposisiTujuan',
            'disposisiTujuanLain',
            'disposisiInstruksi',
            'disposisiCatatan',
            'kepsek',
            'kepalaTas'
        ));
    }

    /**
     * Terbitkan Surat Keterangan Peserta Didik (Siswa Aktif).
     */
    public function suratKeteranganStore(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'create')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'peserta_didik_id' => 'required|string',
            'keperluan'        => 'required|string|max:255',
            'tanggal_surat'    => 'required|date',
            'jenis_surat'      => 'nullable|string|in:siswa_aktif,kelakuan_baik,rekomendasi,bebas_pustaka',
        ]);

        $siswa = \App\Models\PesertaDidik::where('peserta_didik_id', $validated['peserta_didik_id'])->firstOrFail();

        // Hitung nomor urut agenda
        $countYear = \App\Models\SuratKeteranganPd::whereYear('tanggal_surat', date('Y', strtotime($validated['tanggal_surat'])))->count() + 1;
        $noAgendaFormatted = str_pad($countYear, 3, '0', STR_PAD_LEFT);
        $tahun = date('Y', strtotime($validated['tanggal_surat']));
        $nomorSurat = "421.5/{$noAgendaFormatted}/SMK-PGL/{$tahun}";

        $docId = 'SAE-KET-' . strtoupper(substr(md5($siswa->peserta_didik_id . time()), 0, 10));

        $userName = is_array($user)
            ? ($user['nama'] ?? ($user['name'] ?? 'Staf Persuratan'))
            : ($user->nama ?? ($user->name ?? 'Staf Persuratan'));

        $item = \App\Models\SuratKeteranganPd::create([
            'nomor_surat'           => $nomorSurat,
            'peserta_didik_id'      => $siswa->peserta_didik_id,
            'jenis_surat'           => $validated['jenis_surat'] ?: 'siswa_aktif',
            'keperluan'             => $validated['keperluan'],
            'tanggal_surat'         => $validated['tanggal_surat'],
            'penandatangan_jabatan' => 'Kepala Sekolah',
            'doc_id'                => $docId,
            'created_by'            => $userName,
        ]);

        // Simpan juga sebagai arsip surat keluar di tabel persuratan
        Persuratan::create([
            'nomor_surat'     => $nomorSurat,
            'jenis_surat'     => 'keluar',
            'perihal'         => "Surat Keterangan Siswa Aktif a.n {$siswa->nama}",
            'pengirim_asal'   => 'Kepala Sekolah',
            'tujuan_penerima' => $siswa->nama,
            'tanggal_surat'   => $validated['tanggal_surat'],
            'status'          => 'selesai',
            'keterangan'      => "Keperluan: {$validated['keperluan']} (Doc ID: {$docId})",
            'created_by'      => $userName,
        ]);

        return response()->json([
            'status'    => 'success',
            'message'   => "Surat keterangan untuk {$siswa->nama} berhasil diterbitkan.",
            'surat_id'  => $item->id,
            'cetak_url' => route('dashboard.persuratan.keterangan.cetak', $item->id),
        ]);
    }

    /**
     * Cetak Surat Keterangan Peserta Didik Resmi Ber-Kop Surat Sekolah.
     */
    public function suratKeteranganCetak(Request $request, $id)
    {
        $suratKet = \App\Models\SuratKeteranganPd::findOrFail($id);
        $siswa = \App\Models\PesertaDidik::where('peserta_didik_id', $suratKet->peserta_didik_id)->firstOrFail();
        $sekolah = \Illuminate\Support\Facades\DB::table('sekolah')->first();
        $sekolahMeta = \App\Models\SekolahMeta::first();

        $orientasi = in_array(strtolower($request->get('orientasi', 'portrait')), ['portrait', 'landscape'])
            ? strtolower($request->get('orientasi', 'portrait'))
            : 'portrait';

        // Cari rombel & jurusan aktif siswa
        $anggotaRombel = \Illuminate\Support\Facades\DB::table('anggota_rombel as ar')
            ->join('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('ar.peserta_didik_id', $siswa->peserta_didik_id)
            ->select('rb.nama as rombel_nama', 'rb.tingkat_pendidikan_id_str', 'rb.jurusan_id_str')
            ->first();

        $rombelNama = $anggotaRombel?->rombel_nama ?: 'Kelas X / XI / XII';
        $jurusanNama = $anggotaRombel?->jurusan_id_str ?: 'Semua Program Keahlian';

        $curYear = (int) date('Y');
        $tahunAjaran = (date('n') >= 7) ? "{$curYear}/" . ($curYear + 1) : ($curYear - 1) . "/{$curYear}";

        $kepsek = \Illuminate\Support\Facades\DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                    ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        $qrVerifyUrl = url('/v/doc/' . $suratKet->doc_id);
        $qrUri = \App\Services\QrCodeService::generateDataUri($qrVerifyUrl, 140, 1);

        return view('dashboard.persuratan.cetak-surat-keterangan', compact(
            'suratKet',
            'siswa',
            'sekolah',
            'sekolahMeta',
            'orientasi',
            'rombelNama',
            'jurusanNama',
            'tahunAjaran',
            'kepsek',
            'qrUri'
        ));
    }
}