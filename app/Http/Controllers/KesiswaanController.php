<?php

namespace App\Http\Controllers;

use App\Models\KesiswaanBerkasVerifikasi;
use App\Models\KesiswaanBukuKlaper;
use App\Models\KesiswaanKelulusan;
use App\Models\KesiswaanMutasi;
use App\Models\PesertaDidik;
use App\Models\RolePermission;
use App\Services\QrCodeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KesiswaanController extends Controller
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
     * Tampilkan Halaman Kluster Administrasi Kesiswaan (Buku Klaper, Mutasi, Kelulusan).
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'delete');

        $activeTab = $request->get('tab', 'klaper'); // klaper, mutasi, kelulusan
        $q = trim($request->get('q', ''));
        $abjad = strtoupper(trim($request->get('abjad', '')));
        $tahunMasuk = $request->get('tahun_masuk', '');
        $tahunAjaran = $request->get('tahun_ajaran', '');

        // 1. Statistik Administrasi Kesiswaan
        $totalSiswaAktif = PesertaDidik::count();
        $totalKlaperTercatat = KesiswaanBukuKlaper::count();
        $totalMutasi = KesiswaanMutasi::count();
        $totalLulus = KesiswaanKelulusan::where('status_kelulusan', 'lulus')->count();

        $stats = [
            'total_aktif'    => $totalSiswaAktif,
            'total_klaper'   => $totalKlaperTercatat,
            'total_mutasi'   => $totalMutasi,
            'total_lulus'    => $totalLulus,
        ];

        // 2. Data Buku Klaper
        $klaperQuery = DB::table('peserta_didik as pd')
            ->leftJoin('kesiswaan_buku_klaper as kbk', 'pd.peserta_didik_id', '=', 'kbk.peserta_didik_id')
            ->leftJoin('anggota_rombel as ar', 'pd.peserta_didik_id', '=', 'ar.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->select(
                'pd.peserta_didik_id',
                'pd.nama',
                'pd.nisn',
                'pd.nipd',
                'pd.jenis_kelamin',
                'pd.tempat_lahir',
                'pd.tanggal_lahir',
                'rb.nama as rombel_nama',
                'kbk.id as klaper_id',
                'kbk.nomor_klaper',
                'kbk.nomor_induk',
                'kbk.huruf_abjad',
                'kbk.tahun_masuk',
                'kbk.status_klaper'
            );

        if ($q !== '' && $activeTab === 'klaper') {
            $klaperQuery->where(function ($b) use ($q) {
                $b->where('pd.nama', 'like', "%{$q}%")
                  ->orWhere('pd.nisn', 'like', "%{$q}%")
                  ->orWhere('pd.nipd', 'like', "%{$q}%")
                  ->orWhere('kbk.nomor_klaper', 'like', "%{$q}%")
                  ->orWhere('kbk.nomor_induk', 'like', "%{$q}%");
            });
        }

        if ($abjad !== '') {
            $klaperQuery->where('pd.nama', 'like', "{$abjad}%");
        }

        if ($tahunMasuk !== '') {
            $klaperQuery->where('kbk.tahun_masuk', $tahunMasuk);
        }

        $perPageVal = $request->input('perPage', $request->input('per_page', 25));
        $perPage = in_array((int)$perPageVal, [10, 15, 25, 50, 100], true) ? (int)$perPageVal : 25;

        $klaperList = $klaperQuery->orderBy('pd.nama', 'asc')->paginate($perPage, ['*'], 'klaper_page')->withQueryString();

        // 3. Data Mutasi Siswa
        $mutasiQuery = KesiswaanMutasi::with('siswa')->orderBy('tanggal_mutasi', 'desc');
        if ($q !== '' && $activeTab === 'mutasi') {
            $mutasiQuery->whereHas('siswa', function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%")
                  ->orWhere('nisn', 'like', "%{$q}%");
            })->orWhere('nomor_surat_mutasi', 'like', "%{$q}%")
              ->orWhere('sekolah_tujuan_asal', 'like', "%{$q}%");
        }
        $mutasiList = $mutasiQuery->paginate($perPage, ['*'], 'mutasi_page')->withQueryString();

        // 4. Data Kelulusan Siswa
        $kelulusanQuery = KesiswaanKelulusan::with('siswa')->orderBy('tahun_ajaran', 'desc')->orderBy('created_at', 'desc');
        if ($q !== '' && $activeTab === 'kelulusan') {
            $kelulusanQuery->whereHas('siswa', function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%")
                  ->orWhere('nisn', 'like', "%{$q}%");
            })->orWhere('nomor_peserta_ujian', 'like', "%{$q}%")
              ->orWhere('nomor_ijazah', 'like', "%{$q}%")
              ->orWhere('nomor_skl', 'like', "%{$q}%");
        }
        if ($tahunAjaran !== '') {
            $kelulusanQuery->where('tahun_ajaran', $tahunAjaran);
        }
        $kelulusanList = $kelulusanQuery->paginate($perPage, ['*'], 'kelulusan_page')->withQueryString();

        // Siswa aktif untuk modal pilihan
        $siswaList = PesertaDidik::orderBy('nama')
            ->limit(300)
            ->get(['peserta_didik_id', 'nama', 'nisn', 'nipd']);

        return view('dashboard.kesiswaan.administrasi', compact(
            'stats',
            'activeTab',
            'q',
            'abjad',
            'tahunMasuk',
            'tahunAjaran',
            'perPage',
            'klaperList',
            'mutasiList',
            'kelulusanList',
            'siswaList',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete'
        ));
    }

    /**
     * Sinkronisasi Otomatis Buku Klaper dari Data Pokok Siswa Dapodik.
     */
    public function syncKlaper(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'create')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $students = PesertaDidik::orderBy('nama', 'asc')->get();
        $syncedCount = 0;

        foreach ($students as $index => $st) {
            $firstLetter = strtoupper(substr($st->nama, 0, 1));
            $noKlaper = $firstLetter . '-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);

            $klaper = KesiswaanBukuKlaper::firstOrNew(['peserta_didik_id' => $st->peserta_didik_id]);
            if (!$klaper->exists) {
                $klaper->nomor_klaper = $noKlaper;
                $klaper->nomor_induk  = $st->nipd ?: ($st->nisn ?: str_pad($index + 1, 4, '0', STR_PAD_LEFT));
                $klaper->huruf_abjad  = $firstLetter;
                $klaper->tahun_masuk  = date('Y');
                $klaper->status_klaper = 'aktif';
                $klaper->save();
                $syncedCount++;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Berhasil menyinkronkan {$syncedCount} siswa ke Buku Klaper.",
        ]);
    }

    /**
     * Simpan / Update Catatan Nomor Klaper Siswa.
     */
    public function updateKlaper(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'nomor_klaper' => 'nullable|string|max:50',
            'nomor_induk'  => 'nullable|string|max:50',
            'tahun_masuk'  => 'nullable|integer',
            'status_klaper' => 'required|string|in:aktif,lulus,mutasi_keluar,do',
            'keterangan'   => 'nullable|string',
        ]);

        $klaper = KesiswaanBukuKlaper::findOrFail($id);
        $klaper->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Data Buku Klaper berhasil diperbarui.',
            'data' => $klaper,
        ]);
    }

    /**
     * Catat Mutasi Siswa (Masuk, Keluar, DO).
     */
    public function storeMutasi(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'create')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'peserta_didik_id'    => 'required|string',
            'jenis_mutasi'        => 'required|string|in:masuk,keluar,do,meninggal',
            'tanggal_mutasi'      => 'required|date',
            'alasan'              => 'required|string|max:255',
            'sekolah_tujuan_asal' => 'nullable|string|max:190',
            'catatan'             => 'nullable|string',
        ]);

        $siswa = PesertaDidik::where('peserta_didik_id', $validated['peserta_didik_id'])->firstOrFail();

        $countMutasi = KesiswaanMutasi::whereYear('tanggal_mutasi', date('Y', strtotime($validated['tanggal_mutasi'])))->count() + 1;
        $nomorSurat = "421.5/" . str_pad($countMutasi, 3, '0', STR_PAD_LEFT) . "/SMK-MUTASI/" . date('Y', strtotime($validated['tanggal_mutasi']));

        $userName = is_array($user) ? ($user['nama'] ?? 'Staf Kesiswaan') : ($user->nama ?? 'Staf Kesiswaan');

        $mutasi = KesiswaanMutasi::create([
            'peserta_didik_id'    => $siswa->peserta_didik_id,
            'jenis_mutasi'        => $validated['jenis_mutasi'],
            'tanggal_mutasi'      => $validated['tanggal_mutasi'],
            'alasan'              => $validated['alasan'],
            'sekolah_tujuan_asal' => $validated['sekolah_tujuan_asal'],
            'nomor_surat_mutasi'  => $nomorSurat,
            'catatan'             => $validated['catatan'],
            'created_by'          => $userName,
        ]);

        if (in_array($validated['jenis_mutasi'], ['keluar', 'do', 'meninggal'])) {
            KesiswaanBukuKlaper::where('peserta_didik_id', $siswa->peserta_didik_id)
                ->update(['status_klaper' => 'mutasi_keluar']);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Mutasi siswa a.n {$siswa->nama} berhasil dicatat.",
            'mutasi_id' => $mutasi->id,
            'cetak_url' => route('dashboard.kesiswaan.mutasi.cetak', $mutasi->id),
        ]);
    }

    /**
     * Cetak Surat Keterangan Pindah / Mutasi Siswa Resmi Ber-Kop Sekolah.
     */
    public function cetakMutasi(Request $request, $id)
    {
        $mutasi = KesiswaanMutasi::with('siswa')->findOrFail($id);
        $siswa = $mutasi->siswa;
        $sekolah = DB::table('sekolah')->first();
        $sekolahMeta = \App\Models\SekolahMeta::first();

        $orientasi = in_array(strtolower($request->get('orientasi', 'portrait')), ['portrait', 'landscape'])
            ? strtolower($request->get('orientasi', 'portrait'))
            : 'portrait';

        $anggotaRombel = DB::table('anggota_rombel as ar')
            ->join('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('ar.peserta_didik_id', $siswa->peserta_didik_id)
            ->select('rb.nama as rombel_nama', 'rb.tingkat_pendidikan_id_str', 'rb.jurusan_id_str')
            ->first();

        $rombelNama = $anggotaRombel?->rombel_nama ?: 'Kelas X / XI / XII';
        $jurusanNama = $anggotaRombel?->jurusan_id_str ?: 'Semua Program Keahlian';

        $curYear = (int) date('Y');
        $tahunAjaran = (date('n') >= 7) ? "{$curYear}/" . ($curYear + 1) : ($curYear - 1) . "/{$curYear}";

        $kepsek = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                    ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        $docId = 'SAE-MUT-' . strtoupper(substr(md5($siswa->peserta_didik_id . $mutasi->id), 0, 10));
        $qrVerifyUrl = url('/v/doc/' . $docId);
        $qrUri = QrCodeService::generateDataUri($qrVerifyUrl, 140, 1);

        return view('dashboard.kesiswaan.cetak-mutasi', compact(
            'mutasi',
            'siswa',
            'sekolah',
            'sekolahMeta',
            'orientasi',
            'rombelNama',
            'jurusanNama',
            'tahunAjaran',
            'kepsek',
            'qrUri',
            'docId'
        ));
    }

    /**
     * Simpan / Perbarui Data Kelulusan Siswa.
     */
    public function storeKelulusan(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'create')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'peserta_didik_id'    => 'required|string',
            'tahun_ajaran'        => 'required|string|max:20',
            'nomor_peserta_ujian' => 'nullable|string|max:50',
            'nomor_ijazah'        => 'nullable|string|max:50',
            'status_kelulusan'    => 'required|string|in:lulus,tidak_lulus,ditunda',
            'tanggal_lulus'       => 'nullable|date',
            'keterangan'          => 'nullable|string',
        ]);

        $siswa = PesertaDidik::where('peserta_didik_id', $validated['peserta_didik_id'])->firstOrFail();

        $kelulusan = KesiswaanKelulusan::firstOrNew(['peserta_didik_id' => $siswa->peserta_didik_id]);

        $countSkl = KesiswaanKelulusan::where('tahun_ajaran', $validated['tahun_ajaran'])->count() + 1;
        $nomorSkl = "421.5/" . str_pad($countSkl, 3, '0', STR_PAD_LEFT) . "/SMK-SKL/" . date('Y');
        $docId = 'SAE-SKL-' . strtoupper(substr(md5($siswa->peserta_didik_id . $validated['tahun_ajaran']), 0, 10));

        $kelulusan->tahun_ajaran        = $validated['tahun_ajaran'];
        $kelulusan->nomor_peserta_ujian = $validated['nomor_peserta_ujian'] ?? null;
        $kelulusan->nomor_ijazah        = $validated['nomor_ijazah'] ?? null;
        $kelulusan->nomor_skl           = $kelulusan->nomor_skl ?: $nomorSkl;
        $kelulusan->status_kelulusan    = $validated['status_kelulusan'];
        $kelulusan->tanggal_lulus       = $validated['tanggal_lulus'] ?: date('Y-m-d');
        $kelulusan->keterangan          = $validated['keterangan'] ?? null;
        $kelulusan->doc_id              = $kelulusan->doc_id ?: $docId;
        $kelulusan->save();

        if ($validated['status_kelulusan'] === 'lulus') {
            KesiswaanBukuKlaper::where('peserta_didik_id', $siswa->peserta_didik_id)
                ->update(['status_klaper' => 'lulus']);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Data kelulusan siswa {$siswa->nama} berhasil disimpan.",
            'data' => $kelulusan,
            'cetak_url' => route('dashboard.kesiswaan.kelulusan.cetak', $kelulusan->id),
        ]);
    }

    /**
     * Cetak Surat Keterangan Lulus (SKL) Resmi Ber-Kop Sekolah & QR Code Verifikasi.
     */
    public function cetakSkl(Request $request, $id)
    {
        $kelulusan = KesiswaanKelulusan::with('siswa')->findOrFail($id);
        $siswa = $kelulusan->siswa;
        $sekolah = DB::table('sekolah')->first();
        $sekolahMeta = \App\Models\SekolahMeta::first();

        $anggotaRombel = DB::table('anggota_rombel as ar')
            ->join('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('ar.peserta_didik_id', $siswa->peserta_didik_id)
            ->select('rb.nama as rombel_nama', 'rb.jurusan_id_str')
            ->first();

        $rombelNama = $anggotaRombel?->rombel_nama ?: 'Kelas XII';
        $jurusanNama = $anggotaRombel?->jurusan_id_str ?: 'Kompetensi Keahlian';

        $kepsek = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                    ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        $docId = $kelulusan->doc_id ?: ('SAE-SKL-' . strtoupper(substr(md5($siswa->peserta_didik_id), 0, 10)));
        $qrVerifyUrl = url('/v/doc/' . $docId);
        $qrUri = QrCodeService::generateDataUri($qrVerifyUrl, 140, 1);

        return view('dashboard.kesiswaan.cetak-skl', compact(
            'kelulusan',
            'siswa',
            'sekolah',
            'sekolahMeta',
            'rombelNama',
            'jurusanNama',
            'kepsek',
            'qrUri',
            'docId'
        ));
    }
}
