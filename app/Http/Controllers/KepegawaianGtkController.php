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
use Carbon\Carbon;

class KepegawaianGtkController extends Controller
{
    /**
     * Tampilkan Halaman Utama Administrasi Kepegawaian GTK
     */
    public function index(Request $request)
    {
        $user = session('user');
        $tab = $request->query('tab', 'berkas'); // 'berkas', 'kgb', 'cuti', 'spt'
        $search = $request->query('q', '');
        $jenisFilter = $request->query('jenis', 'all');

        // Master GTK untuk dropdown / referensi
        $allGtk = Gtk::orderBy('nama', 'asc')->get(['ptk_id', 'nama', 'nuptk', 'nik', 'nip', 'jenis_ptk_id_str']);

        // 1. Tab Berkas Digital GTK
        $gtkQuery = Gtk::with(['berkas' => function ($q) {
            $q->orderBy('created_at', 'desc');
        }]);

        if ($search && $tab === 'berkas') {
            $gtkQuery->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nuptk', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        if ($jenisFilter === 'guru') {
            $gtkQuery->where('jenis_ptk_id_str', 'like', '%guru%');
        } elseif ($jenisFilter === 'tendik') {
            $gtkQuery->where('jenis_ptk_id_str', 'not like', '%guru%');
        }

        $gtkBerkasList = $gtkQuery->orderBy('nama', 'asc')->paginate(15)->withQueryString();

        // 2. Tab KGB Tracker
        $kgbQuery = GtkKgbTracker::with('gtk');
        if ($search && $tab === 'kgb') {
            $kgbQuery->whereHas('gtk', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            })->orWhere('nomor_sk_terakhir', 'like', "%{$search}%");
        }
        $kgbList = $kgbQuery->orderBy('tmt_baru_target', 'asc')->paginate(15)->withQueryString();

        // Hitung KGB jatuh tempo dalam 90 hari ke depan
        $today = Carbon::today();
        $in90Days = Carbon::today()->addDays(90);
        $kgbJatuhTempoCount = GtkKgbTracker::whereBetween('tmt_baru_target', [$today, $in90Days])
            ->where('status_usulan', '!=', 'terbit_sk')
            ->count();

        // 3. Tab Cuti & Tugas Dinas
        $cutiQuery = GtkCutiIzin::with('gtk');
        if ($search && $tab === 'cuti') {
            $cutiQuery->whereHas('gtk', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            })->orWhere('keperluan', 'like', "%{$search}%");
        }
        $cutiList = $cutiQuery->orderBy('tanggal_mulai', 'desc')->paginate(15)->withQueryString();

        // 4. Tab Surat Perintah Tugas (SPT)
        $sptQuery = DB::table('gtk_spt');
        if ($search && $tab === 'spt') {
            $sptQuery->where(function ($q) use ($search) {
                $q->where('nomor_spt', 'like', "%{$search}%")
                  ->orWhere('nama_kegiatan', 'like', "%{$search}%")
                  ->orWhere('lokasi_tujuan', 'like', "%{$search}%");
            });
        }
        $sptList = $sptQuery->orderBy('tanggal_berangkat', 'desc')->paginate(15)->withQueryString();

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
            'jenisFilter',
            'allGtk',
            'gtkBerkasList',
            'kgbList',
            'cutiList',
            'sptList',
            'kgbJatuhTempoCount',
            'jenisBerkasOptions'
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

        $berkas = GtkBerkas::create([
            'ptk_id' => $request->ptk_id,
            'jenis_dokumen' => $request->jenis_dokumen,
            'judul_dokumen' => $request->judul_dokumen,
            'file_path' => $filePath,
            'keterangan' => $request->keterangan,
            'created_by' => session('user')['nama'] ?? 'Tendik Kepegawaian',
        ]);

        return redirect()->route('dashboard.kepegawaian.index', ['tab' => 'berkas'])
            ->with('success', 'Berkas digital GTK berhasil diunggah.');
    }

    /**
     * Hapus Berkas Digital GTK
     */
    public function deleteBerkas($id)
    {
        $berkas = GtkBerkas::findOrFail($id);

        // Hapus file fisik
        if ($berkas->file_path && Storage::disk('public')->exists($berkas->file_path)) {
            Storage::disk('public')->delete($berkas->file_path);
        }

        $berkas->delete();

        return redirect()->route('dashboard.kepegawaian.index', ['tab' => 'berkas'])
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
     * Simpan Pengajuan Cuti / Surat Tugas Dinas Luar
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

        $cuti = GtkCutiIzin::create([
            'ptk_id' => $request->ptk_id,
            'jenis' => $request->jenis,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'jumlah_hari' => $durasi,
            'keperluan' => $request->keperluan,
            'file_pendukung' => $filePath,
            'status' => 'disetujui_kepsek', // Dibuat oleh staf kepegawaian langsung disetujui / aktif
            'created_by' => session('user')['nama'] ?? 'Tendik Kepegawaian',
        ]);

        return redirect()->route('dashboard.kepegawaian.index', ['tab' => 'cuti'])
            ->with('success', "Data {$request->jenis} GTK berhasil disimpan.");
    }

    /**
     * Cetak Surat Cuti / Surat Perintah Tugas (SPT) Resmi Format A4 Ber-Kop Surat
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

    /**
     * Simpan Surat Perintah Tugas (SPT) GTK
     */
    public function storeSpt(Request $request)
    {
        $request->validate([
            'nama_kegiatan' => 'required|string|max:255',
            'lokasi_tujuan' => 'required|string|max:255',
            'tanggal_berangkat' => 'required|date',
            'tanggal_kembali' => 'required|date|after_or_equal:tanggal_berangkat',
            'beban_anggaran' => 'required|string|max:50',
            'daftar_ptk_id' => 'required|array|min:1',
            'dasar_penugasan' => 'nullable|string|max:1000',
        ], [
            'nama_kegiatan.required' => 'Nama kegiatan penugasan wajib diisi.',
            'lokasi_tujuan.required' => 'Lokasi tujuan penugasan wajib diisi.',
            'daftar_ptk_id.required' => 'Pilih minimal satu GTK yang ditugaskan.',
        ]);

        $start = Carbon::parse($request->tanggal_berangkat);
        $end = Carbon::parse($request->tanggal_kembali);
        $lamaHari = $start->diffInDays($end) + 1;

        // Auto generate nomor SPT
        $year = date('Y');
        $countThisYear = DB::table('gtk_spt')->whereYear('tanggal_berangkat', $year)->count() + 1;
        $nomorSpt = sprintf('800/SPT/%s/%03d', $year, $countThisYear);

        $kepsek = Gtk::where(function ($q) {
            $q->where('jenis_ptk_id_str', 'like', '%kepala sekolah%')
              ->orWhere('jabatan_ptk', 'like', '%kepala sekolah%');
        })->first();

        DB::table('gtk_spt')->insert([
            'nomor_spt' => $nomorSpt,
            'dasar_penugasan' => $request->dasar_penugasan,
            'nama_kegiatan' => $request->nama_kegiatan,
            'lokasi_tujuan' => $request->lokasi_tujuan,
            'tanggal_berangkat' => $request->tanggal_berangkat,
            'tanggal_kembali' => $request->tanggal_kembali,
            'lama_hari' => $lamaHari,
            'beban_anggaran' => $request->beban_anggaran,
            'pejabat_penandatangan_ptk_id' => $kepsek?->ptk_id,
            'pejabat_nama' => $kepsek?->nama ?? 'Kepala Sekolah',
            'pejabat_jabatan' => 'Kepala Sekolah',
            'daftar_ptk_id' => json_encode($request->daftar_ptk_id, JSON_UNESCAPED_UNICODE),
            'status' => 'disetujui',
            'created_by' => session('user')['nama'] ?? 'Tendik Kepegawaian',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.kepegawaian.index', ['tab' => 'spt'])
            ->with('success', "Surat Perintah Tugas (SPT) {$nomorSpt} berhasil diterbitkan.");
    }

    /**
     * Hapus Surat Perintah Tugas (SPT)
     */
    public function deleteSpt($id)
    {
        DB::table('gtk_spt')->where('id', $id)->delete();

        return redirect()->route('dashboard.kepegawaian.index', ['tab' => 'spt'])
            ->with('success', 'Surat Perintah Tugas berhasil dihapus.');
    }

    /**
     * Cetak Surat Perintah Tugas (SPT) Resmi Format A4 Ber-KOP
     */
    public function cetakSpt($id)
    {
        $spt = DB::table('gtk_spt')->where('id', $id)->first();
        if (!$spt) {
            abort(404, 'Data SPT tidak ditemukan.');
        }

        $ptkIds = json_decode($spt->daftar_ptk_id ?? '[]', true) ?: [];
        $gtkList = Gtk::whereIn('ptk_id', $ptkIds)->orderBy('nama', 'asc')->get();

        $sekolah = Sekolah::first();
        $sekolahMeta = \App\Models\SekolahMeta::first();
        $kepsek = Gtk::where(function ($q) {
            $q->where('jenis_ptk_id_str', 'like', '%kepala sekolah%')
              ->orWhere('jabatan_ptk', 'like', '%kepala sekolah%');
        })->first();

        return view('dashboard.kepegawaian.cetak-spt', compact(
            'spt',
            'gtkList',
            'sekolah',
            'sekolahMeta',
            'kepsek'
        ));
    }
}
