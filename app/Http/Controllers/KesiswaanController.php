<?php

namespace App\Http\Controllers;

use App\Models\KesiswaanBerkasVerifikasi;
use App\Models\KesiswaanBukuKlaper;
use App\Models\KesiswaanKelulusan;
use App\Models\KesiswaanMutasi;
use App\Models\Persuratan;
use App\Models\PesertaDidik;
use App\Models\PesertaDidikTidakAktif;
use App\Models\RolePermission;
use App\Services\PersuratanHddService;
use App\Services\QrCodeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;

class KesiswaanController extends Controller
{
    use ChecksAuthenticatedSession;

    
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
            $mutasiQuery->where(function ($query) use ($q) {
                $query->where('nama_siswa', 'like', "%{$q}%")
                    ->orWhere('nisn_siswa', 'like', "%{$q}%")
                    ->orWhere('nipd_siswa', 'like', "%{$q}%")
                    ->orWhere('nomor_surat_mutasi', 'like', "%{$q}%")
                    ->orWhere('nomor_surat_dapodik', 'like', "%{$q}%")
                    ->orWhere('sekolah_tujuan_asal', 'like', "%{$q}%")
                    ->orWhere('alasan', 'like', "%{$q}%")
                    ->orWhere('jenis_mutasi', 'like', "%{$q}%")
                    ->orWhereHas('siswa', function ($b) use ($q) {
                        $b->where('nama', 'like', "%{$q}%")
                          ->orWhere('nisn', 'like', "%{$q}%");
                    })
                    ->orWhereIn('peserta_didik_id', function ($sub) use ($q) {
                        $sub->select('peserta_didik_id')
                            ->from('peserta_didik_tidak_aktif')
                            ->where('nama', 'like', "%{$q}%")
                            ->orWhere('nisn', 'like', "%{$q}%");
                    });
            });
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

        $nextNomorSuratKeluar = PersuratanHddService::generateNomorSuratKeluar('TU.01');

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
            'nextNomorSuratKeluar',
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
    /**
     * Catat Mutasi/Keluar Siswa (Tahap 1: Pengajuan Surat Bersedia Menerima / Permohonan).
     */
    public function storeMutasi(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'create')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'peserta_didik_id'        => 'required|string',
            'jenis_mutasi'            => 'required|string|max:50',
            'tanggal_mutasi'          => 'required|date',
            'nomor_surat_penerimaan'  => 'required|string|max:190',
            'alasan'                  => 'required|string|max:255',
            'sekolah_tujuan_asal'     => 'nullable|string|max:190',
            'file_surat_penerimaan'   => 'required|file|mimes:pdf|max:5120',
            'catatan'                 => 'nullable|string',
        ], [
            'nomor_surat_penerimaan.required' => 'Nomor surat permohonan / dari sekolah tujuan wajib diinputkan.',
            'file_surat_penerimaan.required'  => 'Berkas surat penerimaan / permohonan wajib diunggah.',
            'file_surat_penerimaan.mimes'     => 'Berkas berkas wajib berformat PDF.',
        ]);

        // Cari siswa di tabel aktif atau tabel tidak aktif
        $siswa = PesertaDidik::where('peserta_didik_id', $validated['peserta_didik_id'])->first();
        if (!$siswa) {
            $siswa = PesertaDidikTidakAktif::where('peserta_didik_id', $validated['peserta_didik_id'])->first();
        }
        if (!$siswa) {
            return response()->json(['status' => 'error', 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        // Simpan Berkas Surat 1 (Surat Bersedia Menerima / Rekomendasi / Permohonan) - WAJIB PDF
        $ext = $request->file('file_surat_penerimaan')->getClientOriginalExtension();
        $originalName = $request->file('file_surat_penerimaan')->getClientOriginalName();
        $fileSize = $request->file('file_surat_penerimaan')->getSize();
        $filename = 'surat-penerimaan-' . substr(md5($validated['peserta_didik_id'] . microtime()), 0, 12) . '.' . $ext;
        $pathSuratPenerimaan = $request->file('file_surat_penerimaan')->storeAs('mutasi/surat-penerimaan', $filename, 'public');

        $userName = is_array($user) ? ($user['nama'] ?? 'Staf Kesiswaan') : ($user->nama ?? 'Staf Kesiswaan');

        // Otomatis masukkan ke Modul Persuratan (Surat Masuk)
        $suratMasukId = null;
        try {
            $suratMasuk = Persuratan::create([
                'nomor_surat'        => $validated['nomor_surat_penerimaan'],
                'kode_indeks'        => 'TU.01',
                'jenis_surat'        => 'masuk',
                'perihal'            => "Surat Penerimaan/Permohonan Mutasi Siswa: {$siswa->nama} ({$validated['jenis_mutasi']})",
                'pengirim_asal'      => $validated['sekolah_tujuan_asal'] ?: 'Sekolah Tujuan / Orang Tua Siswa',
                'tujuan_penerima'    => 'Kepala Sekolah / Kesiswaan',
                'tanggal_surat'      => $validated['tanggal_mutasi'],
                'tanggal_diterima'   => now()->toDateString(),
                'status'             => 'diproses',
                'file_path'          => $pathSuratPenerimaan,
                'file_size'          => $fileSize,
                'file_name_original' => $originalName,
                'keterangan'         => "Otomatis dibuat dari alur mutasi kesiswaan. Alasan: {$validated['alasan']}",
                'created_by'         => $userName,
            ]);
            $suratMasukId = $suratMasuk->id;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Gagal auto-insert surat masuk mutasi: " . $e->getMessage());
        }

        $mutasi = KesiswaanMutasi::create([
            'peserta_didik_id'       => $siswa->peserta_didik_id,
            'nama_siswa'             => $siswa->nama,
            'nisn_siswa'             => $siswa->nisn ?? null,
            'nipd_siswa'             => $siswa->nipd ?? null,
            'rombel_terakhir'        => $siswa->nama_rombel ?? ($siswa->nama_rombel_terakhir ?? null),
            'jenis_mutasi'           => $validated['jenis_mutasi'],
            'tanggal_mutasi'         => $validated['tanggal_mutasi'],
            'alasan'                 => $validated['alasan'],
            'sekolah_tujuan_asal'    => $validated['sekolah_tujuan_asal'] ?? null,
            'nomor_surat_penerimaan' => $validated['nomor_surat_penerimaan'],
            'nomor_surat_mutasi'     => null, // Diisi saat surat mutasi Dapodik diunggah (Tahap 2)
            'file_berkas'            => $pathSuratPenerimaan,
            'file_surat_penerimaan'  => $pathSuratPenerimaan,
            'surat_masuk_id'         => $suratMasukId,
            'status_proses'          => 'menunggu_dapodik',
            'catatan'                => $validated['catatan'] ?? null,
            'created_by'             => $userName,
        ]);

        KesiswaanBukuKlaper::where('peserta_didik_id', $siswa->peserta_didik_id)
            ->update(['status_klaper' => 'mutasi_keluar']);

        return response()->json([
            'status' => 'success',
            'message' => "Catatan mutasi a.n {$siswa->nama} berhasil disimpan dan otomatis dicatat di Surat Masuk persuratan.",
            'mutasi_id' => $mutasi->id,
        ]);
    }

    /**
     * Unggah / Update Berkas Surat Mutasi Resmi dari Dapodik (Tahap 2).
     * Otomatis menggenerate nomor surat keluar persuratan & mencatatnya di modul persuratan.
     */
    public function uploadSuratDapodik(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'create')
            && !RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'file_surat_mutasi'   => 'required|file|mimes:pdf|max:5120',
            'nomor_surat_dapodik' => 'nullable|string|max:190',
        ], [
            'file_surat_mutasi.required' => 'Berkas surat mutasi resmi Dapodik wajib diunggah.',
            'file_surat_mutasi.mimes'    => 'Berkas surat mutasi wajib berformat PDF.',
        ]);

        $mutasi = KesiswaanMutasi::findOrFail($id);

        if ($mutasi->file_surat_mutasi && Storage::disk('public')->exists($mutasi->file_surat_mutasi)) {
            Storage::disk('public')->delete($mutasi->file_surat_mutasi);
        }

        $ext = $request->file('file_surat_mutasi')->getClientOriginalExtension();
        $originalName = $request->file('file_surat_mutasi')->getClientOriginalName();
        $fileSize = $request->file('file_surat_mutasi')->getSize();
        $filename = 'surat-dapodik-' . substr(md5($mutasi->id . microtime()), 0, 12) . '.' . $ext;
        $path = $request->file('file_surat_mutasi')->storeAs('mutasi/surat-dapodik', $filename, 'public');

        $userName = is_array($user) ? ($user['nama'] ?? 'Staf Kesiswaan') : ($user->nama ?? 'Staf Kesiswaan');

        // Penomoran surat mutasi mengikuti sistem penomoran Surat Keluar di modul persuratan (Indeks Persuratan: TU.01)
        if (empty($mutasi->nomor_surat_mutasi)) {
            $nomorSuratKeluar = PersuratanHddService::generateNomorSuratKeluar('TU.01');
            PersuratanHddService::incrementNomorCounter('keluar');
            $mutasi->nomor_surat_mutasi = $nomorSuratKeluar;
            if (empty($mutasi->nomor_surat_dapodik)) {
                $mutasi->nomor_surat_dapodik = $nomorSuratKeluar;
            }
        }

        // Otomatis masukkan / perbarui pencatatan di Surat Keluar modul persuratan
        $suratKeluarId = $mutasi->surat_keluar_id;
        try {
            $siswaNama = $mutasi->nama_siswa ?: ($mutasi->siswa?->nama ?: 'Peserta Didik');
            if ($suratKeluarId && ($existingSk = Persuratan::find($suratKeluarId))) {
                $existingSk->update([
                    'nomor_surat'        => $mutasi->nomor_surat_mutasi,
                    'kode_indeks'        => 'TU.01',
                    'tujuan_penerima'    => $mutasi->sekolah_tujuan_asal ?: 'Sekolah Tujuan / Dinas Pendidikan',
                    'file_path'          => $path,
                    'file_size'          => $fileSize,
                    'file_name_original' => $originalName,
                    'status'             => 'selesai',
                    'updated_at'         => now(),
                ]);
            } else {
                $suratKeluar = Persuratan::create([
                    'nomor_surat'        => $mutasi->nomor_surat_mutasi,
                    'kode_indeks'        => 'TU.01',
                    'jenis_surat'        => 'keluar',
                    'perihal'            => "Surat Mutasi / Pindah Siswa: {$siswaNama} ({$mutasi->jenis_mutasi})",
                    'pengirim_asal'      => 'Kepala Sekolah / Kesiswaan',
                    'tujuan_penerima'    => $mutasi->sekolah_tujuan_asal ?: 'Sekolah Tujuan / Dinas Pendidikan',
                    'tanggal_surat'      => $mutasi->tanggal_mutasi ?: now()->toDateString(),
                    'tanggal_diterima'   => null,
                    'status'             => 'selesai',
                    'file_path'          => $path,
                    'file_size'          => $fileSize,
                    'file_name_original' => $originalName,
                    'keterangan'         => "Otomatis tercatat dari alur mutasi kesiswaan resmi Dapodik. No Surat: {$mutasi->nomor_surat_mutasi}",
                    'created_by'         => $userName,
                ]);
                $suratKeluarId = $suratKeluar->id;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Gagal auto-insert surat keluar mutasi: " . $e->getMessage());
        }

        $mutasi->file_surat_mutasi = $path;
        $mutasi->surat_keluar_id = $suratKeluarId;
        if (!empty($validated['nomor_surat_dapodik'])) {
            $mutasi->nomor_surat_dapodik = $validated['nomor_surat_dapodik'];
        } elseif (empty($mutasi->nomor_surat_dapodik)) {
            $mutasi->nomor_surat_dapodik = $mutasi->nomor_surat_mutasi;
        }
        $mutasi->status_proses = 'selesai';
        $mutasi->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Surat mutasi Dapodik berhasil diunggah, nomor surat keluar resmi diterbitkan, dan otomatis dicatat di Surat Keluar.',
            'nomor_surat_mutasi' => $mutasi->nomor_surat_mutasi,
            'file_url' => route('dashboard.kesiswaan.administrasi.mutasi.berkas', ['id' => $mutasi->id, 'jenis' => 'dapodik']),
            'cetak_url' => route('dashboard.kesiswaan.administrasi.mutasi.cetak', $mutasi->id),
        ]);
    }

    /**
     * Ganti / Update langsung salah satu berkas mutasi (penerimaan atau dapodik) via modal review dokumen.
     */
    public function updateBerkasMutasi(Request $request, $id, string $jenis)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update')
            && !RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'create')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'file_dokumen' => 'required|file|mimes:pdf|max:5120',
        ], [
            'file_dokumen.required' => 'Berkas PDF wajib dipilih.',
            'file_dokumen.mimes'    => 'Berkas wajib berformat PDF.',
        ]);

        $mutasi = KesiswaanMutasi::findOrFail($id);
        $file = $request->file('file_dokumen');
        $ext = $file->getClientOriginalExtension();
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();

        if ($jenis === 'dapodik') {
            if ($mutasi->file_surat_mutasi && Storage::disk('public')->exists($mutasi->file_surat_mutasi)) {
                Storage::disk('public')->delete($mutasi->file_surat_mutasi);
            }
            $filename = 'surat-dapodik-' . substr(md5($mutasi->id . microtime()), 0, 12) . '.' . $ext;
            $path = $file->storeAs('mutasi/surat-dapodik', $filename, 'public');
            $mutasi->file_surat_mutasi = $path;
            $mutasi->status_proses = 'selesai';

            // Jika belum punya nomor surat keluar, generate
            if (empty($mutasi->nomor_surat_mutasi)) {
                $mutasi->nomor_surat_mutasi = PersuratanHddService::generateNomorSuratKeluar('KS.02.23');
                PersuratanHddService::incrementNomorCounter('keluar');
            }

            // Sync ke surat keluar jika ada
            if ($mutasi->surat_keluar_id && ($sk = Persuratan::find($mutasi->surat_keluar_id))) {
                $sk->update([
                    'file_path' => $path,
                    'file_size' => $fileSize,
                    'file_name_original' => $originalName,
                ]);
            }
        } else {
            if ($mutasi->file_surat_penerimaan && Storage::disk('public')->exists($mutasi->file_surat_penerimaan)) {
                Storage::disk('public')->delete($mutasi->file_surat_penerimaan);
            }
            $filename = 'surat-penerimaan-' . substr(md5($mutasi->id . microtime()), 0, 12) . '.' . $ext;
            $path = $file->storeAs('mutasi/surat-penerimaan', $filename, 'public');
            $mutasi->file_surat_penerimaan = $path;
            $mutasi->file_berkas = $path;

            // Sync ke surat masuk jika ada
            if ($mutasi->surat_masuk_id && ($sm = Persuratan::find($mutasi->surat_masuk_id))) {
                $sm->update([
                    'file_path' => $path,
                    'file_size' => $fileSize,
                    'file_name_original' => $originalName,
                ]);
            }
        }

        $mutasi->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Berkas dokumen PDF berhasil diperbarui.',
            'file_url' => route('dashboard.kesiswaan.administrasi.mutasi.berkas', ['id' => $mutasi->id, 'jenis' => $jenis]),
        ]);
    }

    /**
     * Update data mutasi kesiswaan (data form & berkas S-1 / S-2).
     */
    public function updateMutasi(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'jenis_mutasi'           => 'required|string|max:50',
            'tanggal_mutasi'         => 'required|date',
            'nomor_surat_penerimaan' => 'required|string|max:190',
            'sekolah_tujuan_asal'    => 'nullable|string|max:190',
            'alasan'                 => 'required|string|max:255',
            'catatan'                => 'nullable|string',
            'file_surat_penerimaan'  => 'nullable|file|mimes:pdf|max:5120',
            'file_surat_mutasi'      => 'nullable|file|mimes:pdf|max:5120',
        ], [
            'file_surat_penerimaan.mimes' => 'Berkas Surat Penerimaan (S-1) wajib berformat PDF.',
            'file_surat_mutasi.mimes'     => 'Berkas Surat Mutasi Dapodik (S-2) wajib berformat PDF.',
        ]);

        $mutasi = KesiswaanMutasi::findOrFail($id);

        $mutasi->jenis_mutasi           = $validated['jenis_mutasi'];
        $mutasi->tanggal_mutasi         = $validated['tanggal_mutasi'];
        $mutasi->nomor_surat_penerimaan = $validated['nomor_surat_penerimaan'];
        $mutasi->sekolah_tujuan_asal    = $validated['sekolah_tujuan_asal'] ?? null;
        $mutasi->alasan                 = $validated['alasan'];
        $mutasi->catatan                = $validated['catatan'] ?? null;

        // Update berkas S-1 jika ada file baru diunggah
        if ($request->hasFile('file_surat_penerimaan') && $request->file('file_surat_penerimaan')->isValid()) {
            if ($mutasi->file_surat_penerimaan && Storage::disk('public')->exists($mutasi->file_surat_penerimaan)) {
                Storage::disk('public')->delete($mutasi->file_surat_penerimaan);
            }
            $file1 = $request->file('file_surat_penerimaan');
            $ext1 = $file1->getClientOriginalExtension();
            $filename1 = 'surat-penerimaan-' . substr(md5($mutasi->id . microtime()), 0, 12) . '.' . $ext1;
            $path1 = $file1->storeAs('mutasi/surat-penerimaan', $filename1, 'public');
            $mutasi->file_surat_penerimaan = $path1;
            $mutasi->file_berkas = $path1;

            if ($mutasi->surat_masuk_id && ($sm = Persuratan::find($mutasi->surat_masuk_id))) {
                $sm->update([
                    'nomor_surat'        => $validated['nomor_surat_penerimaan'],
                    'pengirim_asal'      => $validated['sekolah_tujuan_asal'] ?: 'Sekolah Tujuan / Orang Tua Siswa',
                    'tanggal_surat'      => $validated['tanggal_mutasi'],
                    'file_path'          => $path1,
                    'file_size'          => $file1->getSize(),
                    'file_name_original' => $file1->getClientOriginalName(),
                ]);
            }
        } elseif ($mutasi->surat_masuk_id && ($sm = Persuratan::find($mutasi->surat_masuk_id))) {
            $sm->update([
                'nomor_surat'   => $validated['nomor_surat_penerimaan'],
                'pengirim_asal' => $validated['sekolah_tujuan_asal'] ?: 'Sekolah Tujuan / Orang Tua Siswa',
                'tanggal_surat' => $validated['tanggal_mutasi'],
            ]);
        }

        // Update berkas S-2 jika ada file baru diunggah
        if ($request->hasFile('file_surat_mutasi') && $request->file('file_surat_mutasi')->isValid()) {
            if ($mutasi->file_surat_mutasi && Storage::disk('public')->exists($mutasi->file_surat_mutasi)) {
                Storage::disk('public')->delete($mutasi->file_surat_mutasi);
            }
            $file2 = $request->file('file_surat_mutasi');
            $ext2 = $file2->getClientOriginalExtension();
            $filename2 = 'surat-dapodik-' . substr(md5($mutasi->id . microtime()), 0, 12) . '.' . $ext2;
            $path2 = $file2->storeAs('mutasi/surat-dapodik', $filename2, 'public');
            $mutasi->file_surat_mutasi = $path2;
            $mutasi->status_proses = 'selesai';

            if (empty($mutasi->nomor_surat_mutasi)) {
                $mutasi->nomor_surat_mutasi = PersuratanHddService::generateNomorSuratKeluar('TU.01');
                PersuratanHddService::incrementNomorCounter('keluar');
            }

            if ($mutasi->surat_keluar_id && ($sk = Persuratan::find($mutasi->surat_keluar_id))) {
                $sk->update([
                    'nomor_surat'        => $mutasi->nomor_surat_mutasi,
                    'tujuan_penerima'    => $validated['sekolah_tujuan_asal'] ?: 'Sekolah Tujuan / Dinas Pendidikan',
                    'tanggal_surat'      => $validated['tanggal_mutasi'],
                    'file_path'          => $path2,
                    'file_size'          => $file2->getSize(),
                    'file_name_original' => $file2->getClientOriginalName(),
                ]);
            }
        } elseif ($mutasi->surat_keluar_id && ($sk = Persuratan::find($mutasi->surat_keluar_id))) {
            $sk->update([
                'tujuan_penerima' => $validated['sekolah_tujuan_asal'] ?: 'Sekolah Tujuan / Dinas Pendidikan',
                'tanggal_surat'   => $validated['tanggal_mutasi'],
            ]);
        }

        $mutasi->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Data mutasi siswa & berkas terkait berhasil diperbarui.',
        ]);
    }

    /**
     * Hapus riwayat mutasi siswa, berkas fisik, dan data persuratan terkait.
     */
    public function destroyMutasi($id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'delete')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $mutasi = KesiswaanMutasi::findOrFail($id);
        $namaSiswa = $mutasi->nama_siswa ?: 'Peserta Didik';

        // Hapus file fisik
        if ($mutasi->file_surat_penerimaan && Storage::disk('public')->exists($mutasi->file_surat_penerimaan)) {
            Storage::disk('public')->delete($mutasi->file_surat_penerimaan);
        }
        if ($mutasi->file_surat_mutasi && Storage::disk('public')->exists($mutasi->file_surat_mutasi)) {
            Storage::disk('public')->delete($mutasi->file_surat_mutasi);
        }

        // Hapus surat masuk & surat keluar terhubung jika ada
        if ($mutasi->surat_masuk_id) {
            Persuratan::where('id', $mutasi->surat_masuk_id)->delete();
        }
        if ($mutasi->surat_keluar_id) {
            Persuratan::where('id', $mutasi->surat_keluar_id)->delete();
        }

        // Kembalikan status buku klaper menjadi aktif jika masih ada di tabel siswa aktif
        $masihAktif = PesertaDidik::where('peserta_didik_id', $mutasi->peserta_didik_id)->exists();
        if ($masihAktif) {
            KesiswaanBukuKlaper::where('peserta_didik_id', $mutasi->peserta_didik_id)
                ->update(['status_klaper' => 'aktif']);
        }

        $mutasi->delete();

        return response()->json([
            'status'  => 'success',
            'message' => "Riwayat mutasi siswa a.n {$namaSiswa} berhasil dihapus.",
        ]);
    }
    public function viewBerkasMutasi(Request $request, $id, string $jenis)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'read')) {
            abort(403, 'Akses ditolak.');
        }

        $mutasi = KesiswaanMutasi::findOrFail($id);
        $field = ($jenis === 'dapodik') ? 'file_surat_mutasi' : 'file_surat_penerimaan';
        $path = $mutasi->$field ?: ($jenis !== 'dapodik' ? $mutasi->file_berkas : null);

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'Berkas dokumen tidak ditemukan di server.');
        }

        $fullPath = Storage::disk('public')->path($path);
        $mime = Storage::disk('public')->mimeType($path) ?: 'application/pdf';

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($fullPath) . '"',
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

        $rombelNama = $anggotaRombel?->rombel_nama ?: ($siswa->nama_rombel ?? ($mutasi->rombel_terakhir ?: 'Kelas X / XI / XII'));
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
