<?php

namespace App\Http\Controllers;

use App\Models\KesiswaanBerkasVerifikasi;
use App\Models\KesiswaanBukuKlaper;
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
     * Tampilkan Halaman Utama Administrasi Kesiswaan (Buku Klaper, Mutasi, Berkas).
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

        $activeTab = $request->get('tab', 'klaper'); // klaper, mutasi, berkas
        $q = trim($request->get('q', ''));
        $abjad = strtoupper(trim($request->get('abjad', '')));
        $tahunMasuk = $request->get('tahun_masuk', '');

        // 1. Statistik Kesiswaan
        $totalSiswaAktif = PesertaDidik::count();
        $totalKlaperTercatat = KesiswaanBukuKlaper::count();
        $totalMutasi = KesiswaanMutasi::count();
        $totalBerkasLengkap = KesiswaanBerkasVerifikasi::where('akta_kelahiran', true)
            ->where('kartu_keluarga', true)
            ->where('ijazah_smp', true)
            ->count();

        $stats = [
            'total_aktif'    => $totalSiswaAktif,
            'total_klaper'   => $totalKlaperTercatat,
            'total_mutasi'   => $totalMutasi,
            'berkas_lengkap' => $totalBerkasLengkap,
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

        if ($q !== '') {
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

        $klaperList = $klaperQuery->orderBy('pd.nama', 'asc')->paginate(25)->withQueryString();

        // 3. Data Mutasi Siswa
        $mutasiQuery = KesiswaanMutasi::with('siswa')->orderBy('tanggal_mutasi', 'desc');
        $mutasiList = $mutasiQuery->paginate(20)->withQueryString();

        // 4. Data Berkas Verifikasi Siswa Baru
        $berkasQuery = DB::table('peserta_didik as pd')
            ->leftJoin('kesiswaan_berkas_verifikasi as kbv', 'pd.peserta_didik_id', '=', 'kbv.peserta_didik_id')
            ->leftJoin('anggota_rombel as ar', 'pd.peserta_didik_id', '=', 'ar.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->select(
                'pd.peserta_didik_id',
                'pd.nama',
                'pd.nisn',
                'pd.nipd',
                'rb.nama as rombel_nama',
                'kbv.id as berkas_id',
                'kbv.akta_kelahiran',
                'kbv.kartu_keluarga',
                'kbv.ijazah_smp',
                'kbv.ktp_orang_tua',
                'kbv.kip_pip',
                'kbv.verified_by',
                'kbv.verified_at'
            );

        if ($q !== '' && $activeTab === 'berkas') {
            $berkasQuery->where(function ($b) use ($q) {
                $b->where('pd.nama', 'like', "%{$q}%")
                  ->orWhere('pd.nisn', 'like', "%{$q}%");
            });
        }

        $berkasList = $berkasQuery->orderBy('pd.nama', 'asc')->paginate(25)->withQueryString();

        $siswaList = PesertaDidik::orderBy('nama')
            ->limit(300)
            ->get(['peserta_didik_id', 'nama', 'nisn', 'nipd']);

        return view('dashboard.kesiswaan', compact(
            'stats',
            'activeTab',
            'q',
            'abjad',
            'tahunMasuk',
            'klaperList',
            'mutasiList',
            'berkasList',
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

        // Hitung nomor surat mutasi
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

        // Perbarui status klaper jika mutasi keluar atau DO
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
     * Update Checklist Verifikasi Berkas Fisik Siswa Baru.
     */
    public function updateBerkas(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $berkas = KesiswaanBerkasVerifikasi::firstOrNew(['peserta_didik_id' => $id]);

        $berkas->akta_kelahiran = $request->boolean('akta_kelahiran');
        $berkas->kartu_keluarga = $request->boolean('kartu_keluarga');
        $berkas->ijazah_smp     = $request->boolean('ijazah_smp');
        $berkas->ktp_orang_tua  = $request->boolean('ktp_orang_tua');
        $berkas->kip_pip        = $request->boolean('kip_pip');

        $userName = is_array($user) ? ($user['nama'] ?? 'Staf Kesiswaan') : ($user->nama ?? 'Staf Kesiswaan');
        $berkas->verified_by = $userName;
        $berkas->verified_at = now();
        $berkas->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Verifikasi kelengkapan berkas berhasil diperbarui.',
            'data' => $berkas,
        ]);
    }
}
