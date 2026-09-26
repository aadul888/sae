<?php

namespace App\Http\Controllers;

use App\Models\Gtk;
use App\Models\GtkBerkas;
use App\Models\GtkKgbTracker;
use App\Models\GtkCutiIzin;
use App\Models\Sekolah;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class KepegawaianGtkController extends Controller
{
    /**
     * Tampilkan Halaman Utama Administrasi Kepegawaian GTK (Pegawai, Berkas Pegawai, KGB, Cuti)
     */
    public function index(Request $request)
    {
        $user = session('user');
        $tab = $request->query('tab', 'pegawai');
        if ($tab === 'guru') {
            $tab = 'pegawai';
        }
        if ($tab === 'tendik') {
            $tab = 'berkas';
        }

        $search = trim($request->query('q', ''));
        $filterJenis = $request->query('jenis_ptk', '');
        $filterStatus = $request->query('status', '');
        $filterGender = $request->query('gender', '');

        $perPageVal = $request->query('perPage', $request->query('per_page', '25'));
        $perPage = in_array($perPageVal, ['10', '15', '25', '50', '100']) ? (int)$perPageVal : 25;

        // Master GTK untuk dropdown / referensi
        $allGtk = Gtk::orderBy('nama', 'asc')->get(['ptk_id', 'nama', 'nuptk', 'nik', 'nip', 'jenis_ptk_id_str']);

        // Referensi Filter Dropdown
        $jenisPtkList = Gtk::whereNotNull('jenis_ptk_id_str')->where('jenis_ptk_id_str', '!=', '')->distinct()->pluck('jenis_ptk_id_str')->sort()->values();
        $statusKepegawaianList = Gtk::whereNotNull('status_kepegawaian_id_str')->where('status_kepegawaian_id_str', '!=', '')->distinct()->pluck('status_kepegawaian_id_str')->sort()->values();

        // 1. Tab Pegawai (Seluruh Tenaga Pendidik & Kependidikan)
        $pegawaiQuery = Gtk::query();
        if ($filterJenis) {
            $pegawaiQuery->where('jenis_ptk_id_str', $filterJenis);
        }
        if ($filterStatus) {
            $pegawaiQuery->where('status_kepegawaian_id_str', $filterStatus);
        }
        if ($filterGender) {
            $pegawaiQuery->where('jenis_kelamin', $filterGender);
        }
        if ($search && $tab === 'pegawai') {
            $pegawaiQuery->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nuptk', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        $pegawaiList = $pegawaiQuery->orderBy('nama', 'asc')->paginate($perPage)->withQueryString();

        // 2. Tab Berkas Pegawai (Pengarsipan Dokumen Fisik & Digital GTK)
        $berkasQuery = Gtk::with(['berkas' => function ($q) {
            $q->orderBy('created_at', 'desc');
        }]);
        if ($filterJenis) {
            $berkasQuery->where('jenis_ptk_id_str', $filterJenis);
        }
        if ($filterStatus) {
            $berkasQuery->where('status_kepegawaian_id_str', $filterStatus);
        }
        if ($search && $tab === 'berkas') {
            $berkasQuery->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nuptk', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            });
        }
        $berkasList = $berkasQuery->orderBy('nama', 'asc')->paginate($perPage)->withQueryString();

        // Alias untuk backward compatibility
        $guruList = $pegawaiList;
        $tendikList = $berkasList;

        // 3. Tab KGB Tracker
        $kgbQuery = GtkKgbTracker::with('gtk');
        if ($search && $tab === 'kgb') {
            $kgbQuery->where(function ($b) use ($search) {
                $b->whereHas('gtk', function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('nip', 'like', "%{$search}%");
                })->orWhere('nomor_sk_terakhir', 'like', "%{$search}%");
            });
        }
        $kgbList = $kgbQuery->orderBy('tmt_baru_target', 'asc')->paginate($perPage)->withQueryString();

        // Hitung KGB jatuh tempo dalam 90 hari ke depan
        $today = Carbon::today();
        $in90Days = Carbon::today()->addDays(90);
        $kgbJatuhTempoCount = GtkKgbTracker::whereBetween('tmt_baru_target', [$today, $in90Days])
            ->where('status_usulan', '!=', 'terbit_sk')
            ->count();

        // 4. Tab Cuti & Tugas Dinas
        $cutiQuery = GtkCutiIzin::with('gtk');
        if ($search && $tab === 'cuti') {
            $cutiQuery->where(function ($b) use ($search) {
                $b->whereHas('gtk', function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%");
                })->orWhere('keperluan', 'like', "%{$search}%");
            });
        }
        $cutiList = $cutiQuery->orderBy('tanggal_mulai', 'desc')->paginate($perPage)->withQueryString();

        // Statistik Ringkasan Kepegawaian
        $stats = [
            'total_guru'       => Gtk::where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%guru%')
                  ->orWhere('jenis_ptk_id_str', 'like', '%kepala sekolah%')
                  ->orWhere('jenis_ptk_id_str', 'like', '%pendidik%');
            })->count(),
            'total_tendik'     => Gtk::where('jenis_ptk_id_str', 'not like', '%guru%')
                ->where('jenis_ptk_id_str', 'not like', '%kepala sekolah%')
                ->where('jenis_ptk_id_str', 'not like', '%pendidik%')
                ->count(),
            'kgb_jatuh_tempo'  => $kgbJatuhTempoCount,
            'cuti_aktif'       => GtkCutiIzin::where('tanggal_selesai', '>=', date('Y-m-d'))->count(),
        ];

        // Jenis berkas standar
        $jenisBerkasOptions = [
            'sk_pengangkatan' => 'SK Pengangkatan Pertama',
            'sk_pembagian_tugas' => 'SK Pembagian Tugas Mengajar / TAS',
            'kgb' => 'SK Kenaikan Gaji Berkala (KGB)',
            'ijazah' => 'Ijazah & Transkrip Pendidikan',
            'sertifikat' => 'Sertifikat Pendidik / Pelatihan',
            'ktp_kk' => 'KTP / Kartu Keluarga',
            'lainnya' => 'Dokumen / Piagam Pendukung Lainnya'
        ];

        return view('dashboard.kepegawaian', compact(
            'tab',
            'search',
            'perPage',
            'allGtk',
            'pegawaiList',
            'berkasList',
            'guruList',
            'tendikList',
            'kgbList',
            'cutiList',
            'stats',
            'kgbJatuhTempoCount',
            'jenisBerkasOptions',
            'jenisPtkList',
            'statusKepegawaianList',
            'filterJenis',
            'filterStatus',
            'filterGender'
        ));
    }

    /**
     * Upload Berkas Digital GTK
     */
    public function uploadBerkas(Request $request)
    {
        $request->validate([
            'ptk_id' => 'required|exists:gtk,ptk_id',
            'jenis_dokumen' => 'required|string|max:50',
            'judul_dokumen' => 'required|string|max:200',
            'file_berkas' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // Maks 5MB
            'keterangan' => 'nullable|string|max:500',
        ]);

        $file = $request->file('file_berkas');
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $filePath = $file->storeAs('gtk_berkas', $fileName, 'public');

        GtkBerkas::create([
            'ptk_id' => $request->ptk_id,
            'jenis_dokumen' => $request->jenis_dokumen,
            'judul_dokumen' => $request->judul_dokumen,
            'file_path' => $filePath,
            'keterangan' => $request->keterangan,
            'created_by' => session('user')['nama'] ?? 'Tendik Kepegawaian',
        ]);

        $redirectTab = $request->input('tab_redirect', 'berkas');

        return redirect()->route('dashboard.kepegawaian.index', ['tab' => $redirectTab])
            ->with('success', 'Berkas digital GTK berhasil diunggah.');
    }

    /**
     * Hapus Berkas Digital GTK
     */
    public function deleteBerkas(Request $request, $id)
    {
        $berkas = GtkBerkas::findOrFail($id);

        // Hapus file fisik
        if ($berkas->file_path && Storage::disk('public')->exists($berkas->file_path)) {
            Storage::disk('public')->delete($berkas->file_path);
        }

        $berkas->delete();

        $redirectTab = $request->input('tab_redirect', 'berkas');

        return redirect()->route('dashboard.kepegawaian.index', ['tab' => $redirectTab])
            ->with('success', 'Berkas digital berhasil dihapus.');
    }

    /**
     * Simpan / Perbarui Tracker KGB GTK
     */
    public function storeKgb(Request $request)
    {
        $request->validate([
            'ptk_id' => 'required|exists:gtk,ptk_id',
            'gaji_pokok_lama' => 'nullable|numeric',
            'gaji_pokok_baru' => 'nullable|numeric',
            'tmt_lama' => 'required|date',
            'nomor_sk_terakhir' => 'nullable|string|max:100',
            'tgl_sk_terakhir' => 'nullable|date',
            'tmt_baru_target' => 'required|date',
            'mkg_tahun' => 'nullable|integer|min:0',
            'mkg_bulan' => 'nullable|integer|min:0|max:11',
            'status_usulan' => 'required|in:belum_waktunya,siap_diajukan,diproses,terbit_sk',
            'catatan' => 'nullable|string|max:500',
        ]);

        GtkKgbTracker::updateOrCreate(
            ['ptk_id' => $request->ptk_id],
            [
                'gaji_pokok_lama' => $request->gaji_pokok_lama ?? 0,
                'gaji_pokok_baru' => $request->gaji_pokok_baru ?? 0,
                'tmt_lama' => $request->tmt_lama,
                'nomor_sk_terakhir' => $request->nomor_sk_terakhir,
                'tgl_sk_terakhir' => $request->tgl_sk_terakhir,
                'tmt_baru_target' => $request->tmt_baru_target,
                'mkg_tahun' => $request->mkg_tahun ?? 0,
                'mkg_bulan' => $request->mkg_bulan ?? 0,
                'status_usulan' => $request->status_usulan,
                'catatan' => $request->catatan,
            ]
        );

        return redirect()->route('dashboard.kepegawaian.index', ['tab' => 'kgb'])
            ->with('success', 'Data Kenaikan Gaji Berkala (KGB) berhasil disimpan.');
    }

    /**
     * Simpan Pengajuan Cuti / Izin GTK
     */
    public function storeCuti(Request $request)
    {
        $request->validate([
            'ptk_id' => 'required|exists:gtk,ptk_id',
            'jenis' => 'required|string|max:50',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keperluan' => 'required|string|max:500',
            'surat_pendukung' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $filePath = null;
        if ($request->hasFile('surat_pendukung')) {
            $file = $request->file('surat_pendukung');
            $fileName = time() . '_cuti_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $filePath = $file->storeAs('gtk_cuti', $fileName, 'public');
        }

        // Hitung durasi hari
        $start = Carbon::parse($request->tanggal_mulai);
        $end = Carbon::parse($request->tanggal_selesai);
        $durasi = $start->diffInDays($end) + 1;

        GtkCutiIzin::create([
            'ptk_id' => $request->ptk_id,
            'jenis' => $request->jenis,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'jumlah_hari' => $durasi,
            'keperluan' => $request->keperluan,
            'file_pendukung' => $filePath,
            'status' => 'disetujui_kepsek',
            'created_by' => session('user')['nama'] ?? 'Tendik Kepegawaian',
        ]);

        return redirect()->route('dashboard.kepegawaian.index', ['tab' => 'cuti'])
            ->with('success', "Data {$request->jenis} GTK berhasil disimpan.");
    }

    /**
     * Cetak Surat Cuti Resmi Format A4 Ber-Kop Surat
     */
    public function cetakCuti($id)
    {
        $cuti = GtkCutiIzin::with('gtk')->findOrFail($id);
        $sekolah = Sekolah::first();
        $sekolahMeta = \App\Models\SekolahMeta::first();

        // Kepala sekolah untuk penandatangan
        $kepsek = Gtk::where(function ($q) {
            $q->where('jenis_ptk_id_str', 'like', '%kepala sekolah%')
              ->orWhere('jabatan_ptk', 'like', '%kepala sekolah%');
        })->first();

        return view('dashboard.kepegawaian.cetak-cuti', compact(
            'cuti',
            'sekolah',
            'sekolahMeta',
            'kepsek'
        ));
    }
}
