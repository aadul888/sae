<?php

namespace App\Http\Controllers;

use App\Models\KedisiplinanPelanggaran;
use App\Models\KedisiplinanPembinaan;
use App\Models\KedisiplinanPemanggilanWali;
use App\Models\KedisiplinanTataTertib;
use App\Models\PesertaDidik;
use App\Models\RolePermission;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KedisiplinanController extends Controller
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
     * Tampilkan Halaman Kluster Kedisiplinan Siswa (7 Tab).
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_kedisiplinan', 'create') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'create') || RolePermission::canAccess($user ?: $role, 'menu_poin', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_kedisiplinan', 'read') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'read') || RolePermission::canAccess($user ?: $role, 'menu_poin', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_kedisiplinan', 'update') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update') || RolePermission::canAccess($user ?: $role, 'menu_poin', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_kedisiplinan', 'delete') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'delete') || RolePermission::canAccess($user ?: $role, 'menu_poin', 'delete');

        $activeTab = $request->get('tab', 'riwayat'); // tata_tertib, poin, riwayat, pembinaan, pemanggilan, tindak_lanjut, rekap
        $q = trim($request->get('q', ''));
        $kategori = trim($request->get('kategori', ''));
        $status = trim($request->get('status', ''));

        // 1. Statistik Ringkas Kedisiplinan
        $totalAturan = KedisiplinanTataTertib::where('is_active', true)->count();
        $totalPelanggaran = KedisiplinanPelanggaran::count();
        $totalPembinaan = KedisiplinanPembinaan::count();
        $totalPanggilan = KedisiplinanPemanggilanWali::count();
        $totalPending = KedisiplinanPelanggaran::where('status_tindak_lanjut', 'pending')->count();

        $stats = [
            'total_aturan'      => $totalAturan,
            'total_pelanggaran' => $totalPelanggaran,
            'total_pembinaan'   => $totalPembinaan,
            'total_panggilan'   => $totalPanggilan,
            'total_pending'     => $totalPending,
        ];

        // 2. Tab: Tata Tertib
        $tataTertibQuery = KedisiplinanTataTertib::orderBy('kategori')->orderBy('kode');
        if ($q !== '' && $activeTab === 'tata_tertib') {
            $tataTertibQuery->where('nama_aturan', 'like', "%{$q}%")
                ->orWhere('kode', 'like', "%{$q}%");
        }
        $tataTertibList = $tataTertibQuery->get();

        // 3. Tab: Riwayat Pelanggaran
        $riwayatQuery = KedisiplinanPelanggaran::with(['siswa', 'aturan'])
            ->orderBy('tanggal_kejadian', 'desc');
        if ($q !== '' && in_array($activeTab, ['poin', 'riwayat'])) {
            $riwayatQuery->whereHas('siswa', function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%")
                  ->orWhere('nisn', 'like', "%{$q}%");
            })->orWhere('keterangan', 'like', "%{$q}%");
        }
        if ($status !== '') {
            $riwayatQuery->where('status_tindak_lanjut', $status);
        }

        $perPageVal = $request->input('perPage', $request->input('per_page', 25));
        $perPage = in_array((int)$perPageVal, [10, 15, 25, 50, 100], true) ? (int)$perPageVal : 25;

        $riwayatList = $riwayatQuery->paginate($perPage, ['*'], 'riwayat_page')->withQueryString();

        // 4. Tab: Sesi Pembinaan Konseling
        $pembinaanQuery = KedisiplinanPembinaan::with(['siswa', 'pelanggaran.aturan', 'guruBk', 'waliKelas'])
            ->orderBy('tanggal_pembinaan', 'desc');
        if ($q !== '' && $activeTab === 'pembinaan') {
            $pembinaanQuery->whereHas('siswa', function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%");
            })->orWhere('hasil_pembinaan', 'like', "%{$q}%");
        }
        $pembinaanList = $pembinaanQuery->paginate($perPage, ['*'], 'pembinaan_page')->withQueryString();

        // 5. Tab: Pemanggilan Wali Murid
        $pemanggilanQuery = KedisiplinanPemanggilanWali::with('siswa')
            ->orderBy('tanggal_surat', 'desc');
        if ($q !== '' && $activeTab === 'pemanggilan') {
            $pemanggilanQuery->whereHas('siswa', function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%");
            })->orWhere('nomor_surat', 'like', "%{$q}%");
        }
        $pemanggilanList = $pemanggilanQuery->paginate($perPage, ['*'], 'pemanggilan_page')->withQueryString();

        // 6. Tab: Rekapitulasi Poin Siswa (Total akumulasi poin per siswa)
        $rekapSiswa = DB::table('kedisiplinan_pelanggaran as kp')
            ->join('peserta_didik as pd', 'kp.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('anggota_rombel as ar', 'pd.peserta_didik_id', '=', 'ar.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->select(
                'pd.peserta_didik_id',
                'pd.nama',
                'pd.nisn',
                'rb.nama as rombel_nama',
                DB::raw('SUM(kp.poin) as total_poin'),
                DB::raw('COUNT(kp.id) as total_kasus')
            )
            ->groupBy('pd.peserta_didik_id', 'pd.nama', 'pd.nisn', 'rb.nama')
            ->orderByDesc('total_poin')
            ->limit(100)
            ->get();

        // Data master untuk modal
        $siswaList = PesertaDidik::orderBy('nama')->limit(300)->get(['peserta_didik_id', 'nama', 'nisn', 'nipd']);
        $guruList = DB::table('gtk')->orderBy('nama')->get(['ptk_id', 'nama']);

        return view('dashboard.kesiswaan.kedisiplinan', compact(
            'stats',
            'activeTab',
            'q',
            'kategori',
            'status',
            'perPage',
            'tataTertibList',
            'riwayatList',
            'pembinaanList',
            'pemanggilanList',
            'rekapSiswa',
            'siswaList',
            'guruList',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete'
        ));
    }

    /**
     * Simpan Aturan Tata Tertib Baru.
     */
    public function storeTataTertib(Request $request)
    {
        $validated = $request->validate([
            'kode'               => 'required|string|max:50|unique:kedisiplinan_tata_tertib,kode',
            'kategori'           => 'required|string|in:kerapian,kehadiran,perilaku,larangan_berat',
            'nama_aturan'        => 'required|string|max:255',
            'bobot_poin'         => 'required|integer|min:1',
            'sanksi_rekomendasi' => 'nullable|string|max:255',
        ]);

        $item = KedisiplinanTataTertib::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => "Aturan tata tertib {$item->kode} berhasil ditambahkan.",
            'data' => $item,
        ]);
    }

    /**
     * Simpan Entri Pelanggaran Poin Siswa Baru.
     */
    public function storePoin(Request $request)
    {
        $user = session('user');

        $validated = $request->validate([
            'peserta_didik_id' => 'required|string',
            'tata_tertib_id'   => 'required|exists:kedisiplinan_tata_tertib,id',
            'tanggal_kejadian' => 'required|date',
            'tempat_kejadian'  => 'nullable|string|max:190',
            'keterangan'       => 'nullable|string',
            'foto_bukti'       => 'nullable|image|max:3072',
        ]);

        $aturan = KedisiplinanTataTertib::findOrFail($validated['tata_tertib_id']);
        $siswa = PesertaDidik::where('peserta_didik_id', $validated['peserta_didik_id'])->firstOrFail();

        $filePath = null;
        if ($request->hasFile('foto_bukti')) {
            $file = $request->file('foto_bukti');
            $fileName = 'pelanggaran_' . time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/kedisiplinan'), $fileName);
            $filePath = '/uploads/kedisiplinan/' . $fileName;
        }

        $pelaporNama = is_array($user) ? ($user['nama'] ?? 'Guru/Staf') : ($user->nama ?? 'Guru/Staf');
        $pelaporPtk = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

        $pelanggaran = KedisiplinanPelanggaran::create([
            'peserta_didik_id'     => $siswa->peserta_didik_id,
            'tata_tertib_id'       => $aturan->id,
            'tanggal_kejadian'     => $validated['tanggal_kejadian'],
            'tempat_kejadian'      => $validated['tempat_kejadian'] ?? null,
            'poin'                 => $aturan->bobot_poin,
            'keterangan'           => $validated['keterangan'] ?? null,
            'pelapor_ptk_id'       => $pelaporPtk,
            'pelapor_nama'         => $pelaporNama,
            'foto_bukti'           => $filePath,
            'status_tindak_lanjut' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Pelanggaran siswa {$siswa->nama} ({$aturan->bobot_poin} poin) berhasil dicatat.",
            'data' => $pelanggaran,
        ]);
    }

    /**
     * Simpan Sesi Pembinaan Siswa (Konseling BK / Wali Kelas).
     */
    public function storePembinaan(Request $request)
    {
        $validated = $request->validate([
            'peserta_didik_id'      => 'required|string',
            'pelanggaran_id'        => 'nullable|exists:kedisiplinan_pelanggaran,id',
            'tanggal_pembinaan'     => 'required|date',
            'guru_bk_ptk_id'        => 'nullable|string',
            'wali_kelas_ptk_id'     => 'nullable|string',
            'bentuk_pembinaan'      => 'required|string|max:190',
            'hasil_pembinaan'       => 'required|string',
            'status'                => 'required|string|in:proses,selesai',
            'surat_perjanjian_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $filePath = null;
        if ($request->hasFile('surat_perjanjian_file')) {
            $file = $request->file('surat_perjanjian_file');
            $fileName = 'perjanjian_' . time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/kedisiplinan'), $fileName);
            $filePath = '/uploads/kedisiplinan/' . $fileName;
        }

        $pembinaan = KedisiplinanPembinaan::create([
            'peserta_didik_id'      => $validated['peserta_didik_id'],
            'pelanggaran_id'        => $validated['pelanggaran_id'] ?? null,
            'tanggal_pembinaan'     => $validated['tanggal_pembinaan'],
            'guru_bk_ptk_id'        => $validated['guru_bk_ptk_id'] ?? null,
            'wali_kelas_ptk_id'     => $validated['wali_kelas_ptk_id'] ?? null,
            'bentuk_pembinaan'      => $validated['bentuk_pembinaan'],
            'hasil_pembinaan'       => $validated['hasil_pembinaan'],
            'status'                => $validated['status'],
            'surat_perjanjian_file' => $filePath,
        ]);

        if (!empty($validated['pelanggaran_id'])) {
            KedisiplinanPelanggaran::where('id', $validated['pelanggaran_id'])
                ->update(['status_tindak_lanjut' => $validated['status'] === 'selesai' ? 'selesai' : 'proses']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Catatan sesi pembinaan siswa berhasil disimpan.',
            'data' => $pembinaan,
        ]);
    }

    /**
     * Terbitkan Surat Pemanggilan Orang Tua / Wali Murid.
     */
    public function storePemanggilanWali(Request $request)
    {
        $validated = $request->validate([
            'peserta_didik_id' => 'required|string',
            'tanggal_surat'    => 'required|date',
            'tanggal_hadir'    => 'required|date',
            'jam_hadir'        => 'required',
            'tempat'           => 'required|string|max:190',
            'alasan'           => 'required|string|max:255',
            'menghadap_ke'     => 'required|string|max:190',
        ]);

        $siswa = PesertaDidik::where('peserta_didik_id', $validated['peserta_didik_id'])->firstOrFail();

        $count = KedisiplinanPemanggilanWali::whereYear('tanggal_surat', date('Y', strtotime($validated['tanggal_surat'])))->count() + 1;
        $nomorSurat = "421.5/" . str_pad($count, 3, '0', STR_PAD_LEFT) . "/SMK-PGL-ORTU/" . date('Y');
        $docId = 'SAE-PGL-' . strtoupper(substr(md5($siswa->peserta_didik_id . time()), 0, 10));

        $panggilan = KedisiplinanPemanggilanWali::create([
            'peserta_didik_id' => $siswa->peserta_didik_id,
            'nomor_surat'      => $nomorSurat,
            'tanggal_surat'    => $validated['tanggal_surat'],
            'tanggal_hadir'    => $validated['tanggal_hadir'],
            'jam_hadir'        => $validated['jam_hadir'],
            'tempat'           => $validated['tempat'],
            'alasan'           => $validated['alasan'],
            'menghadap_ke'     => $validated['menghadap_ke'],
            'status'           => 'diterbitkan',
            'doc_id'           => $docId,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Surat pemanggilan orang tua a.n {$siswa->nama} berhasil diterbitkan.",
            'data' => $panggilan,
            'cetak_url' => route('dashboard.kesiswaan.kedisiplinan.panggilan.cetak', $panggilan->id),
        ]);
    }

    /**
     * Cetak Surat Pemanggilan Orang Tua / Wali Format Resmi Ber-Kop Sekolah.
     */
    public function cetakSuratPanggilan(Request $request, $id)
    {
        $panggilan = KedisiplinanPemanggilanWali::with('siswa')->findOrFail($id);
        $siswa = $panggilan->siswa;
        $sekolah = DB::table('sekolah')->first();
        $sekolahMeta = \App\Models\SekolahMeta::first();

        $anggotaRombel = DB::table('anggota_rombel as ar')
            ->join('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('ar.peserta_didik_id', $siswa->peserta_didik_id)
            ->select('rb.nama as rombel_nama')
            ->first();

        $rombelNama = $anggotaRombel?->rombel_nama ?: 'Kelas X / XI / XII';

        $kepsek = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                    ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        $docId = $panggilan->doc_id ?: ('SAE-PGL-' . strtoupper(substr(md5($siswa->peserta_didik_id . $panggilan->id), 0, 10)));
        $qrVerifyUrl = url('/v/doc/' . $docId);
        $qrUri = QrCodeService::generateDataUri($qrVerifyUrl, 130, 1);

        return view('dashboard.kesiswaan.cetak-panggilan-wali', compact(
            'panggilan',
            'siswa',
            'sekolah',
            'sekolahMeta',
            'rombelNama',
            'kepsek',
            'qrUri',
            'docId'
        ));
    }
}
