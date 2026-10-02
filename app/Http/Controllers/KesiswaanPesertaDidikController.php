<?php

namespace App\Http\Controllers;

use App\Models\AdminAktivitas;
use App\Models\KesiswaanBerkasVerifikasi;
use App\Models\PesertaDidik;
use App\Models\PesertaDidikBerkas;
use App\Models\PesertaDidikMeta;
use App\Models\RolePermission;
use App\Models\SiswaUsulanPerubahan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KesiswaanPesertaDidikController extends Controller
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
     * Tampilkan Halaman Kluster Peserta Didik (Aktif, Tidak Aktif, Alumni, Berkas, Usulan Perubahan).
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        // RBAC: Gunakan permission menu_peserta_didik_aktif atau menu_kesiswaan
        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'create') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'read') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'update') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'delete') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'delete');

        $activeTab = $request->get('tab', 'aktif'); // aktif, tidak_aktif, alumni, berkas, usulan
        $q = trim($request->get('q', ''));
        $rombel = trim($request->get('rombel', ''));
        $gender = trim($request->get('gender', ''));
        $tahunLulus = trim($request->get('tahun_lulus', ''));

        $perPageVal = $request->get('perPage', $request->get('per_page', '25'));
        $perPage = in_array($perPageVal, ['10', '15', '25', '50', '100']) ? (int)$perPageVal : 25;

        // 1. Statistik Ringkas
        $totalAktif = PesertaDidik::count();
        $totalTidakAktif = DB::table('peserta_didik_tidak_aktif')
            ->where(function ($b) {
                $b->where('status_keluar', '<>', 'Alumni')
                  ->orWhereNull('status_keluar');
            })
            ->where('alasan_keluar', 'not like', '%Lulus%')
            ->where('alasan_keluar', 'not like', '%Tamat%')
            ->count();
        $totalAlumni = DB::table('peserta_didik_tidak_aktif')
            ->where(function ($b) {
                $b->where('status_keluar', 'Alumni')
                  ->orWhere('alasan_keluar', 'like', '%Lulus%')
                  ->orWhere('alasan_keluar', 'like', '%Tamat%');
            })->count();
        $totalBerkasLengkap = KesiswaanBerkasVerifikasi::where('akta_kelahiran', true)
            ->where('kartu_keluarga', true)
            ->where('ijazah_smp', true)
            ->count();
        $totalUsulanMenunggu = SiswaUsulanPerubahan::where('status', 'menunggu')->count();

        $stats = [
            'total_aktif'       => $totalAktif,
            'total_tidak_aktif' => $totalTidakAktif,
            'total_alumni'      => $totalAlumni,
            'berkas_lengkap'    => $totalBerkasLengkap,
            'usulan_menunggu'   => $totalUsulanMenunggu,
        ];

        // Daftar rombel untuk filter
        $filterRombel = DB::table('peserta_didik')
            ->whereNotNull('nama_rombel')
            ->where('nama_rombel', '<>', '')
            ->distinct()
            ->pluck('nama_rombel')
            ->sort()
            ->values();

        // 2. Tab: Peserta Didik Aktif
        $aktifQuery = DB::table('peserta_didik')
            ->select('peserta_didik_id', 'nama', 'nisn', 'nipd', 'nik', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'nama_rombel', 'nama_ayah', 'nama_ibu');

        if ($q !== '' && $activeTab === 'aktif') {
            $aktifQuery->where(function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%")
                  ->orWhere('nisn', 'like', "%{$q}%")
                  ->orWhere('nipd', 'like', "%{$q}%")
                  ->orWhere('nik', 'like', "%{$q}%");
            });
        }
        if ($rombel !== '') {
            $aktifQuery->where('nama_rombel', $rombel);
        }
        if ($gender !== '') {
            $aktifQuery->where('jenis_kelamin', $gender);
        }
        $aktifList = $aktifQuery->orderBy('nama', 'asc')->paginate($perPage, ['*'], 'aktif_page')->withQueryString();

        $aktifPdIds = $aktifList->pluck('peserta_didik_id')->filter()->values()->all();
        $metaMapAktif = collect();
        if (!empty($aktifPdIds) && Schema::hasTable('peserta_didik_meta')) {
            $metaMapAktif = \App\Models\PesertaDidikMeta::whereIn('peserta_didik_id', $aktifPdIds)->get()->keyBy('peserta_didik_id');
        }
        foreach ($aktifList as $item) {
            $item->foto_url = $metaMapAktif[$item->peserta_didik_id]?->foto_url ?? null;
        }

        // 3. Tab: Peserta Didik Tidak Aktif (Mutasi / DO / Berhenti - Eksklusif Non-Alumni)
        $tidakAktifQuery = DB::table('peserta_didik_tidak_aktif')
            ->where(function ($b) {
                $b->where('status_keluar', '<>', 'Alumni')
                  ->orWhereNull('status_keluar');
            })
            ->where('alasan_keluar', 'not like', '%Lulus%')
            ->where('alasan_keluar', 'not like', '%Tamat%')
            ->select('id', 'peserta_didik_id', 'nama', 'nisn', 'nipd', 'nik', 'jenis_kelamin', 'nama_rombel_terakhir as rombel_terakhir', 'alasan_keluar', 'tanggal_keluar', 'status_keluar', 'foto_path');

        if ($q !== '' && $activeTab === 'tidak_aktif') {
            $tidakAktifQuery->where(function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%")
                  ->orWhere('nisn', 'like', "%{$q}%")
                  ->orWhere('nipd', 'like', "%{$q}%")
                  ->orWhere('nik', 'like', "%{$q}%")
                  ->orWhere('alasan_keluar', 'like', "%{$q}%");
            });
        }
        $tidakAktifList = $tidakAktifQuery->orderBy('tanggal_keluar', 'desc')->paginate($perPage, ['*'], 'tidak_aktif_page')->withQueryString();
        $taPdIds = $tidakAktifList->pluck('peserta_didik_id')->filter()->values()->all();
        $metaMapTa = collect();
        if (!empty($taPdIds) && Schema::hasTable('peserta_didik_meta')) {
            $metaMapTa = \App\Models\PesertaDidikMeta::whereIn('peserta_didik_id', $taPdIds)->get()->keyBy('peserta_didik_id');
        }
        foreach ($tidakAktifList as $item) {
            $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : ($metaMapTa[$item->peserta_didik_id]?->foto_url ?? null);
        }

        // 4. Tab: Alumni
        $alumniQuery = DB::table('peserta_didik_tidak_aktif')
            ->where(function ($b) {
                $b->where('status_keluar', 'Alumni')
                  ->orWhere('alasan_keluar', 'like', '%Lulus%')
                  ->orWhere('alasan_keluar', 'like', '%Tamat%');
            })
            ->select('id', 'peserta_didik_id', 'nama', 'nisn', 'nipd', 'nik', 'jenis_kelamin', 'nama_rombel_terakhir as rombel_terakhir', 'alasan_keluar', 'tanggal_keluar', 'tahun_lulus', 'foto_path');

        if ($q !== '' && $activeTab === 'alumni') {
            $alumniQuery->where(function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%")
                  ->orWhere('nisn', 'like', "%{$q}%")
                  ->orWhere('nipd', 'like', "%{$q}%")
                  ->orWhere('nik', 'like', "%{$q}%")
                  ->orWhere('nama_rombel_terakhir', 'like', "%{$q}%");
            });
        }
        if ($tahunLulus !== '') {
            $alumniQuery->where(function ($b) use ($tahunLulus) {
                $b->where('tahun_lulus', $tahunLulus)
                  ->orWhereYear('tanggal_keluar', $tahunLulus);
            });
        }
        $alumniList = $alumniQuery->orderBy('tanggal_keluar', 'desc')->paginate($perPage, ['*'], 'alumni_page')->withQueryString();
        $alumniPdIds = $alumniList->pluck('peserta_didik_id')->filter()->values()->all();
        $metaMapAlumni = collect();
        if (!empty($alumniPdIds) && Schema::hasTable('peserta_didik_meta')) {
            $metaMapAlumni = \App\Models\PesertaDidikMeta::whereIn('peserta_didik_id', $alumniPdIds)->get()->keyBy('peserta_didik_id');
        }
        foreach ($alumniList as $item) {
            $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : ($metaMapAlumni[$item->peserta_didik_id]?->foto_url ?? null);
        }

        // 5. Tab: Verifikasi Berkas Fisik & Digital
        $berkasQuery = DB::table('peserta_didik as pd')
            ->leftJoin('kesiswaan_berkas_verifikasi as kbv', 'pd.peserta_didik_id', '=', 'kbv.peserta_didik_id')
            ->select(
                'pd.peserta_didik_id',
                'pd.nama',
                'pd.nisn',
                'pd.nipd',
                'pd.nik',
                'pd.nama_rombel as rombel_nama',
                'kbv.id as berkas_id',
                'kbv.akta_kelahiran',
                'kbv.kartu_keluarga',
                'kbv.ijazah_smp',
                'kbv.ktp_orang_tua',
                'kbv.kip_pip',
                'kbv.catatan_verifikasi',
                'kbv.verified_by',
                'kbv.verified_at'
            );

        if ($q !== '' && $activeTab === 'berkas') {
            $berkasQuery->where(function ($b) use ($q) {
                $b->where('pd.nama', 'like', "%{$q}%")
                  ->orWhere('pd.nisn', 'like', "%{$q}%")
                  ->orWhere('pd.nipd', 'like', "%{$q}%")
                  ->orWhere('pd.nik', 'like', "%{$q}%");
            });
        }
        if ($rombel !== '' && $activeTab === 'berkas') {
            $berkasQuery->where('pd.nama_rombel', $rombel);
        }
        $berkasList = $berkasQuery->orderBy('pd.nama', 'asc')->paginate($perPage, ['*'], 'berkas_page')->withQueryString();
        $berkasPdIds = $berkasList->pluck('peserta_didik_id')->filter()->values()->all();
        $metaMapBerkas = collect();
        if (!empty($berkasPdIds) && Schema::hasTable('peserta_didik_meta')) {
            $metaMapBerkas = \App\Models\PesertaDidikMeta::whereIn('peserta_didik_id', $berkasPdIds)->get()->keyBy('peserta_didik_id');
        }
        $uploadedBerkasMap = collect();
        if (!empty($berkasPdIds) && Schema::hasTable('peserta_didik_berkas')) {
            $uploadedBerkasMap = PesertaDidikBerkas::whereIn('peserta_didik_id', $berkasPdIds)
                ->get()
                ->groupBy('peserta_didik_id');
        }
        foreach ($berkasList as $item) {
            $item->foto_url = $metaMapBerkas[$item->peserta_didik_id]?->foto_url ?? null;
            $studentFiles = $uploadedBerkasMap[$item->peserta_didik_id] ?? collect();
            $item->files = $studentFiles->keyBy('jenis_berkas');
            $item->total_uploaded = $studentFiles->count();
            $item->valid_count = $studentFiles->where('status', 'valid')->count();
            $item->tidak_valid_count = $studentFiles->where('status', 'tidak_valid')->count();
            $item->menunggu_count = $studentFiles->where('status', 'menunggu')->count();
        }

        // 6. Tab: Usulan Perubahan Data Siswa
        $usulanQuery = SiswaUsulanPerubahan::with('siswa')->orderBy('created_at', 'desc');
        if ($q !== '' && $activeTab === 'usulan') {
            $usulanQuery->whereHas('siswa', function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%")
                  ->orWhere('nisn', 'like', "%{$q}%")
                  ->orWhere('nipd', 'like', "%{$q}%")
                  ->orWhere('nik', 'like', "%{$q}%");
            })->orWhere('kolom_perubahan', 'like', "%{$q}%")
              ->orWhere('alasan', 'like', "%{$q}%");
        }
        $usulanList = $usulanQuery->paginate($perPage, ['*'], 'usulan_page')->withQueryString();
        $usulanPdIds = $usulanList->pluck('peserta_didik_id')->filter()->values()->all();
        $metaMapUsulan = collect();
        if (!empty($usulanPdIds) && Schema::hasTable('peserta_didik_meta')) {
            $metaMapUsulan = \App\Models\PesertaDidikMeta::whereIn('peserta_didik_id', $usulanPdIds)->get()->keyBy('peserta_didik_id');
        }
        $prereqMapUsulan = collect();
        if (!empty($usulanPdIds) && Schema::hasTable('peserta_didik_berkas')) {
            $prereqMapUsulan = PesertaDidikBerkas::whereIn('peserta_didik_id', $usulanPdIds)
                ->whereIn('jenis_berkas', ['kartu_keluarga', 'ijazah_smp'])
                ->get()
                ->groupBy('peserta_didik_id');
        }
        foreach ($usulanList as $item) {
            $item->foto_url = $metaMapUsulan[$item->peserta_didik_id]?->foto_url ?? null;
            $bList = $prereqMapUsulan[$item->peserta_didik_id] ?? collect();
            $item->kk_status = $bList->firstWhere('jenis_berkas', 'kartu_keluarga')?->status ?? 'belum_unggah';
            $item->ijazah_status = $bList->firstWhere('jenis_berkas', 'ijazah_smp')?->status ?? 'belum_unggah';
        }

        return view('dashboard.kesiswaan.peserta-didik', compact(
            'stats',
            'activeTab',
            'q',
            'rombel',
            'gender',
            'tahunLulus',
            'perPage',
            'filterRombel',
            'aktifList',
            'tidakAktifList',
            'alumniList',
            'berkasList',
            'usulanList',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete'
        ));
    }

    /**
     * Simpan Usulan Perubahan Data Siswa.
     */
    public function storeUsulan(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $validated = $request->validate([
            'peserta_didik_id' => 'required|string',
            'kolom_perubahan'  => 'required|string|max:100',
            'nilai_lama'       => 'nullable|string',
            'nilai_baru'       => 'required|string',
            'alasan'           => 'required|string|max:255',
            'berkas_bukti'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $filePath = null;
        if ($request->hasFile('berkas_bukti')) {
            $file = $request->file('berkas_bukti');
            $fileName = 'usulan_' . time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/usulan_siswa'), $fileName);
            $filePath = '/uploads/usulan_siswa/' . $fileName;
        }

        $userName = is_array($user) ? ($user['nama'] ?? 'Pengguna') : ($user->nama ?? 'Pengguna');

        $usulan = SiswaUsulanPerubahan::create([
            'peserta_didik_id'   => $validated['peserta_didik_id'],
            'kolom_perubahan'    => $validated['kolom_perubahan'],
            'nilai_lama'         => $validated['nilai_lama'] ?? null,
            'nilai_baru'         => $validated['nilai_baru'],
            'alasan'             => $validated['alasan'],
            'berkas_bukti'       => $filePath,
            'status'             => 'menunggu',
            'created_by'         => $userName,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Usulan perubahan data siswa berhasil dikirim dan menunggu verifikasi.',
            'data' => $usulan,
        ]);
    }

    /**
     * Ambil data detail lengkap satu usulan perubahan (JSON) untuk Modal Pengelolaan Usulan
     */
    public function getUsulanDetail($id)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canAccess = in_array($role, ['admin', 'tendik', 'guru'], true)
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_peserta_didik', 'read')
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'read')
            || RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'read');

        if (!$canAccess) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $usulan = SiswaUsulanPerubahan::with('siswa')->findOrFail($id);
        $pd = $usulan->siswa;

        // Foto siswa
        $meta = null;
        if (Schema::hasTable('peserta_didik_meta') && $pd) {
            $meta = PesertaDidikMeta::where('peserta_didik_id', $pd->peserta_didik_id)->first();
        }

        // Berkas wajib KK & Ijazah siswa
        $berkas = collect();
        if (Schema::hasTable('peserta_didik_berkas') && $pd) {
            $berkas = PesertaDidikBerkas::where('peserta_didik_id', $pd->peserta_didik_id)
                ->whereIn('jenis_berkas', ['kartu_keluarga', 'ijazah_smp', 'akta_kelahiran'])
                ->get()
                ->keyBy('jenis_berkas');
        }

        $kk = $berkas->get('kartu_keluarga');
        $ijazah = $berkas->get('ijazah_smp');
        $akta = $berkas->get('akta_kelahiran');

        // Peta label nama kolom yang ramah pengguna
        $kolomLabels = [
            'nama' => 'Nama Lengkap Siswa',
            'jenis_kelamin' => 'Jenis Kelamin',
            'nik' => 'Nomor Induk Kependudukan (NIK)',
            'no_kk' => 'Nomor Kartu Keluarga (KK)',
            'no_registrasi_akta_lahir' => 'No. Registrasi Akta Lahir',
            'kewarganegaraan' => 'Kewarganegaraan',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'agama_id' => 'Agama & Kepercayaan',
            'alamat_jalan' => 'Alamat Jalan / Tempat Tinggal',
            'rt' => 'RT',
            'rw' => 'RW',
            'desa_kelurahan' => 'Desa / Kelurahan',
            'kecamatan' => 'Kecamatan',
            'kabupaten_kota' => 'Kabupaten / Kota',
            'provinsi' => 'Provinsi',
            'kode_pos' => 'Kode Pos',
            'tempat_tinggal_id' => 'Status Tempat Tinggal',
            'transportasi_id' => 'Moda Transportasi',
            'anak_keberapa' => 'Anak Ke-berapa (di KK)',
            'nama_ayah' => 'Nama Ayah Kandung',
            'nik_ayah' => 'NIK Ayah',
            'tahun_lahir_ayah' => 'Tahun Lahir Ayah',
            'pendidikan_ayah_id' => 'Pendidikan Ayah',
            'pekerjaan_ayah_id' => 'Pekerjaan Ayah',
            'penghasilan_ayah_id' => 'Penghasilan Ayah',
            'nama_ibu' => 'Nama Ibu Kandung',
            'nik_ibu' => 'NIK Ibu',
            'tahun_lahir_ibu' => 'Tahun Lahir Ibu',
            'pendidikan_ibu_id' => 'Pendidikan Ibu',
            'pekerjaan_ibu_id' => 'Pekerjaan Ibu',
            'penghasilan_ibu_id' => 'Penghasilan Ibu',
            'nama_wali' => 'Nama Wali',
            'nik_wali' => 'NIK Wali',
            'nomor_telepon_rumah' => 'Nomor Telepon Rumah',
            'nomor_telepon_seluler' => 'No. HP / WhatsApp Siswa',
            'email' => 'Email Siswa',
            'sekolah_asal' => 'Sekolah Asal',
            'tinggi_badan' => 'Tinggi Badan (cm)',
            'berat_badan' => 'Berat Badan (kg)',
            'lingkar_kepala' => 'Lingkar Kepala (cm)',
            'jarak_rumah_sekolah' => 'Jarak Rumah ke Sekolah',
            'jarak_rumah_sekolah_km' => 'Jarak Rumah (km)',
            'waktu_tempuh_jam' => 'Waktu Tempuh (Jam)',
            'waktu_tempuh_menit' => 'Waktu Tempuh (Menit)',
            'jumlah_saudara_kandung' => 'Jumlah Saudara Kandung',
        ];

        // Rekomendasi alasan penolakan usulan data (chip buttons)
        $rekomendasiPenolakan = [
            'Data yang diusulkan tidak sesuai dengan dokumen resmi Kartu Keluarga (KK).',
            'Data nama / tempat / tanggal lahir tidak cocok dengan Ijazah SMP atau Akta Kelahiran.',
            'Lampiran berkas bukti buram, terpotong, atau tidak terbaca dengan jelas.',
            'Perubahan nama atau identitas pokok wajib menyertakan Akta Kelahiran / Penetapan Pengadilan.',
            'Format penulisan NIK atau No. KK tidak valid (harus 16 digit terdaftar di Dukcapil).',
            'Pengajuan usulan dibatalkan atas permintaan siswa atau orang tua.',
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $usulan->id,
                'peserta_didik_id' => $usulan->peserta_didik_id,
                'nama_siswa' => $pd?->nama ?: 'Siswa #' . $usulan->peserta_didik_id,
                'nisn' => $pd?->nisn ?: '-',
                'nipd' => $pd?->nipd ?: '-',
                'rombel' => $pd?->nama_rombel ?: '-',
                'foto_url' => $meta?->foto_url ?? null,
                'kolom_perubahan' => $usulan->kolom_perubahan,
                'kolom_label' => $kolomLabels[$usulan->kolom_perubahan] ?? ucwords(str_replace('_', ' ', $usulan->kolom_perubahan)),
                'nilai_lama' => $usulan->nilai_lama ?: '(Belum Terisi / Kosong)',
                'nilai_baru' => $usulan->nilai_baru,
                'alasan' => $usulan->alasan ?: 'Penyesuaian formulir identitas mandiri.',
                'status' => $usulan->status,
                'catatan_verifikasi' => $usulan->catatan_verifikasi,
                'verified_by' => $usulan->verified_by,
                'verified_at' => $usulan->verified_at ? $usulan->verified_at->translatedFormat('d F Y, H:i') : null,
                'created_at' => $usulan->created_at ? $usulan->created_at->translatedFormat('d F Y, H:i') : null,
                'berkas_bukti_url' => !empty($usulan->berkas_bukti) ? asset('storage/' . ltrim($usulan->berkas_bukti, '/')) : null,
                'kk' => [
                    'exists' => !empty($kk),
                    'status' => $kk?->status ?? 'belum_unggah',
                    'file_url' => $kk ? route('dashboard.berkas.preview', $kk->id) : null,
                    'file_name' => $kk?->file_name,
                ],
                'ijazah' => [
                    'exists' => !empty($ijazah),
                    'status' => $ijazah?->status ?? 'belum_unggah',
                    'file_url' => $ijazah ? route('dashboard.berkas.preview', $ijazah->id) : null,
                    'file_name' => $ijazah?->file_name,
                ],
                'akta' => [
                    'exists' => !empty($akta),
                    'status' => $akta?->status ?? 'belum_unggah',
                    'file_url' => $akta ? route('dashboard.berkas.preview', $akta->id) : null,
                    'file_name' => $akta?->file_name,
                ],
            ],
            'rekomendasi_penolakan' => $rekomendasiPenolakan,
        ]);
    }

    /**
     * Verifikasi Usulan Perubahan Data (Setujui / Tolak).
     * Jika ditolak, wajib menyertakan alasan penolakan.
     */
    public function verifikasiUsulan(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canUpdate = in_array($role, ['admin', 'tendik'], true)
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_peserta_didik', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'update');

        if (!$canUpdate) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'status'             => 'required|string|in:disetujui,ditolak',
            'catatan_verifikasi' => 'nullable|string',
        ]);

        $status = $validated['status'];
        $catatan = trim((string)($validated['catatan_verifikasi'] ?? ''));

        if ($status === 'ditolak' && empty($catatan)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Alasan atau catatan penolakan wajib diisi jika usulan perubahan data ditolak.'
            ], 422);
        }

        $usulan = SiswaUsulanPerubahan::findOrFail($id);
        $userName = is_array($user) ? ($user['nama'] ?? 'Verifikator') : ($user->nama ?? 'Verifikator');

        $usulan->status = $status;
        $usulan->catatan_verifikasi = $catatan ?: null;
        $usulan->verified_by = $userName;
        $usulan->verified_at = now();
        $usulan->save();

        // Jika disetujui, update data peserta didik secara otomatis
        if ($status === 'disetujui' && Schema::hasColumn('peserta_didik', $usulan->kolom_perubahan)) {
            DB::table('peserta_didik')
                ->where('peserta_didik_id', $usulan->peserta_didik_id)
                ->update([$usulan->kolom_perubahan => $usulan->nilai_baru]);
        }

        // Sinkronisasi status dan nilai ke tabel aman peserta_didik_identitas
        if (Schema::hasTable('peserta_didik_identitas')) {
            $identitasUpdate = [];
            if ($status === 'disetujui' && Schema::hasColumn('peserta_didik_identitas', $usulan->kolom_perubahan)) {
                $identitasUpdate[$usulan->kolom_perubahan] = $usulan->nilai_baru;
            }

            $remainingPending = SiswaUsulanPerubahan::where('peserta_didik_id', $usulan->peserta_didik_id)
                ->where('status', 'menunggu')
                ->where('id', '!=', $usulan->id)
                ->count();

            if ($remainingPending === 0 && $status === 'disetujui') {
                $identitasUpdate['status_konfirmasi'] = 'diverifikasi';
                $identitasUpdate['catatan_kesiswaan'] = 'Seluruh usulan perubahan data siswa telah disetujui & diverifikasi oleh Tim Kesiswaan.';
            } elseif ($status === 'ditolak') {
                $identitasUpdate['catatan_kesiswaan'] = "Usulan perubahan kolom {$usulan->kolom_perubahan} ditolak: " . ($catatan ?: 'Dokumen pendukung tidak sesuai.');
            }

            if (!empty($identitasUpdate)) {
                DB::table('peserta_didik_identitas')
                    ->where('peserta_didik_id', $usulan->peserta_didik_id)
                    ->update($identitasUpdate);
            }
        }

        if (class_exists(AdminAktivitas::class)) {
            $pd = PesertaDidik::where('peserta_didik_id', $usulan->peserta_didik_id)->first();
            $labelStatus = $status === 'disetujui' ? 'Disetujui' : 'Ditolak';
            AdminAktivitas::record(
                "Verifikasi Usulan Data Siswa: {$pd?->nama} ({$usulan->kolom_perubahan}) [{$labelStatus}]",
                'Kesiswaan',
                $status === 'ditolak' ? "Alasan Penolakan: {$catatan}" : "Usulan disetujui dan data siswa diperbarui.",
                $status === 'disetujui' ? 'success' : 'warning'
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => "Usulan perubahan data berhasil " . ($status === 'disetujui' ? 'disetujui' : 'ditolak') . ".",
            'data' => $usulan,
        ]);
    }

    /**
     * Tandai Usulan Perubahan Data telah Di-update / Disinkronkan ke Aplikasi Dapodik
     */
    public function markDapodikUpdated(Request $request, $id)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canUpdate = in_array($role, ['admin', 'tendik'], true)
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_peserta_didik', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'update');

        if (!$canUpdate) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $usulan = SiswaUsulanPerubahan::findOrFail($id);
        $userName = is_array($user) ? ($user['nama'] ?? 'Operator Dapodik') : ($user->nama ?? 'Operator Dapodik');

        $usulan->status = 'sudah_ke_dapodik';
        $usulan->catatan_verifikasi = ($usulan->catatan_verifikasi ? $usulan->catatan_verifikasi . ' | ' : '') . "Telah di-update ke Aplikasi Dapodik oleh {$userName} pada " . now()->translatedFormat('d M Y, H:i');
        $usulan->save();

        if (Schema::hasTable('peserta_didik_identitas')) {
            $remaining = SiswaUsulanPerubahan::where('peserta_didik_id', $usulan->peserta_didik_id)
                ->whereIn('status', ['menunggu', 'disetujui'])
                ->count();

            if ($remaining === 0) {
                DB::table('peserta_didik_identitas')
                    ->where('peserta_didik_id', $usulan->peserta_didik_id)
                    ->update([
                        'status_konfirmasi' => 'disinkronkan_dapodik',
                        'catatan_kesiswaan' => 'Seluruh usulan perubahan data siswa telah selesai di-input ke aplikasi Dapodik.',
                    ]);
            }
        }

        if (class_exists(AdminAktivitas::class)) {
            $pd = PesertaDidik::where('peserta_didik_id', $usulan->peserta_didik_id)->first();
            AdminAktivitas::record(
                "Update Dapodik Selesai: {$pd?->nama} (Kolom {$usulan->kolom_perubahan})",
                'Kesiswaan',
                "Perubahan data {$usulan->kolom_perubahan} berhasil ditandai selesai di-input ke Dapodik.",
                'success'
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil! Data perubahan ditandai telah selesai di-update ke aplikasi Dapodik.',
            'data' => $usulan,
        ]);
    }

    /**
     * Update Checklist Verifikasi Berkas Fisik Siswa Baru.
     */
    public function updateBerkas(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update') && !RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'update')) {
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

    /**
     * Ambil data berkas digital lengkap per siswa (JSON) untuk Modal Verifikasi Berkas Kesiswaan.
     */
    public function getBerkasDetail($id)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canAccess = in_array($role, ['admin', 'tendik', 'guru'], true)
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_peserta_didik', 'read')
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'read')
            || RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'read');

        if (!$canAccess) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $pd = PesertaDidik::where('peserta_didik_id', $id)->first();
        if (!$pd && Schema::hasTable('peserta_didik_tidak_aktif')) {
            $pd = DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->first();
        }

        if (!$pd) {
            return response()->json(['status' => 'error', 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        $slots = PesertaDidikBerkas::getJenisBerkasOptions();
        $berkas = PesertaDidikBerkas::where('peserta_didik_id', $id)->get()->keyBy('jenis_berkas');
        $rekomendasi = PesertaDidikBerkas::getRekomendasiPenolakanOptions();

        $items = [];
        foreach ($slots as $k => $slot) {
            $b = $berkas->get($k);
            $items[] = [
                'jenis_berkas' => $k,
                'label' => $slot['label'],
                'deskripsi' => $slot['deskripsi'],
                'icon' => $slot['icon'],
                'wajib' => $slot['wajib'],
                'is_uploaded' => !empty($b) && !empty($b->file_path),
                'berkas_id' => $b?->id,
                'file_name' => $b?->file_name,
                'file_size' => $b?->formatted_file_size,
                'file_url' => (!empty($b) && !empty($b->file_path)) ? route('dashboard.berkas.preview', $b->id) : null,
                'status' => $b?->status ?? 'belum_unggah',
                'catatan_penolakan' => $b?->catatan_penolakan,
                'rekomendasi_penolakan' => $b?->rekomendasi_penolakan,
                'verified_by' => $b?->verified_by,
                'verified_at' => $b?->verified_at ? $b->verified_at->translatedFormat('d M Y, H:i') : null,
                'uploaded_at' => $b?->created_at ? $b->created_at->translatedFormat('d M Y, H:i') : null,
            ];
        }

        return response()->json([
            'status' => 'success',
            'siswa' => [
                'peserta_didik_id' => $pd->peserta_didik_id,
                'nama' => $pd->nama,
                'nisn' => $pd->nisn ?? '-',
                'nipd' => $pd->nipd ?? '-',
                'rombel' => $pd->nama_rombel ?? '-',
            ],
            'items' => $items,
            'rekomendasi_penolakan' => $rekomendasi,
        ]);
    }

    /**
     * Verifikasi Kebenaran Berkas Siswa (Hanya 2 Status: valid atau tidak_valid).
     * Jika tidak_valid, wajib menyertakan catatan/alasan penolakan.
     */
    public function verifikasiBerkasItem(Request $request, $id)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canUpdate = in_array($role, ['admin', 'tendik'], true)
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_peserta_didik', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'update');

        if (!$canUpdate) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'jenis_berkas' => 'required|string',
            'status' => 'required|in:valid,tidak_valid', // HANYA 2 STATUS
            'catatan_penolakan' => 'nullable|string',
            'rekomendasi_penolakan' => 'nullable|string',
        ]);

        $status = $request->input('status');
        $catatan = trim((string)$request->input('catatan_penolakan', ''));
        $rekomendasi = trim((string)$request->input('rekomendasi_penolakan', ''));

        if ($status === 'tidak_valid' && empty($catatan) && empty($rekomendasi)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Untuk status tidak valid / tidak sesuai, wajib memberikan catatan atau memilih rekomendasi alasan penolakan.'
            ], 422);
        }

        $berkas = PesertaDidikBerkas::firstOrNew([
            'peserta_didik_id' => $id,
            'jenis_berkas' => $request->input('jenis_berkas'),
        ]);

        if (!$berkas->exists) {
            $slots = PesertaDidikBerkas::getJenisBerkasOptions();
            $berkas->nama_berkas = $slots[$request->input('jenis_berkas')]['label'] ?? 'Dokumen Siswa';
            $berkas->file_path = '';
            $berkas->file_name = '(Verifikasi Fisik / Manual)';
            $berkas->mime_type = 'application/pdf';
        }

        $userName = is_array($user) ? ($user['nama'] ?? 'Staf Kesiswaan') : ($user->nama ?? 'Staf Kesiswaan');

        $berkas->status = $status;
        $berkas->verified_by = $userName;
        $berkas->verified_at = now();
        $berkas->catatan_penolakan = $status === 'tidak_valid' ? ($catatan ?: $rekomendasi) : null;
        $berkas->rekomendasi_penolakan = $status === 'tidak_valid' ? $rekomendasi : null;
        $berkas->save();

        // Sinkronisasi status checklist ke tabel kesiswaan_berkas_verifikasi jika sesuai kolom
        $coreColumns = ['akta_kelahiran', 'kartu_keluarga', 'ijazah_smp', 'ktp_orang_tua', 'kip_pip'];
        if (in_array($berkas->jenis_berkas, $coreColumns, true)) {
            $kbv = KesiswaanBerkasVerifikasi::firstOrNew(['peserta_didik_id' => $id]);
            $kbv->{$berkas->jenis_berkas} = ($status === 'valid');
            $kbv->verified_by = $userName;
            $kbv->verified_at = now();
            $kbv->save();
        }

        if (class_exists(AdminAktivitas::class)) {
            $pd = PesertaDidik::where('peserta_didik_id', $id)->first();
            $statusLabel = $status === 'valid' ? 'Valid (Sesuai)' : 'Tidak Valid (Tidak Sesuai)';
            AdminAktivitas::record(
                "Verifikasi Berkas Siswa: {$pd?->nama} - {$berkas->nama_berkas} ({$statusLabel})",
                'Kesiswaan',
                $status === 'tidak_valid' ? "Penolakan: " . ($berkas->catatan_penolakan ?: '-') : "Dokumen diverifikasi valid dan sesuai persyaratan.",
                $status === 'valid' ? 'success' : 'warning'
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => "Berkas \"{$berkas->nama_berkas}\" berhasil divalidasi sebagai: " . ($status === 'valid' ? 'Valid / Sesuai' : 'Tidak Valid / Tidak Sesuai') . '.',
            'data' => $berkas,
        ]);
    }

    /**
     * Detail lengkap biodata peserta didik (JSON) untuk Modal Biodata.
     */
    public function show($id)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'read') && !RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'read')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $pesertaDidik = DB::table('peserta_didik')
            ->where('peserta_didik_id', $id)
            ->orWhere('nisn', $id)
            ->orWhere('nipd', $id)
            ->first();

        // Jika tidak ada di tabel peserta_didik aktif, cari di peserta_didik_tidak_aktif
        if (!$pesertaDidik && Schema::hasTable('peserta_didik_tidak_aktif')) {
            $pesertaDidik = DB::table('peserta_didik_tidak_aktif')
                ->where('peserta_didik_id', $id)
                ->orWhere('nisn', $id)
                ->orWhere('nipd', $id)
                ->first();
        }

        if (!$pesertaDidik) {
            return response()->json(['status' => 'error', 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        $anggota = null;
        if (Schema::hasTable('anggota_rombel')) {
            $anggota = DB::table('anggota_rombel')
                ->where('peserta_didik_id', $pesertaDidik->peserta_didik_id)
                ->first();
        }

        $meta = null;
        if (Schema::hasTable('peserta_didik_meta')) {
            $meta = \App\Models\PesertaDidikMeta::where('peserta_didik_id', $pesertaDidik->peserta_didik_id)->first();
        }

        $rombelId = $pesertaDidik->rombongan_belajar_id ?? ($anggota->rombongan_belajar_id ?? null);
        $pembelajaran = collect();
        if (!empty($rombelId) && Schema::hasTable('pembelajaran')) {
            $pembelajaran = DB::table('pembelajaran')
                ->leftJoin('gtk', 'pembelajaran.ptk_id', '=', 'gtk.ptk_id')
                ->where('pembelajaran.rombongan_belajar_id', $rombelId)
                ->select(
                    'pembelajaran.pembelajaran_id',
                    'pembelajaran.nama_mata_pelajaran',
                    'pembelajaran.mata_pelajaran_id_str',
                    'pembelajaran.jam_mengajar_per_minggu',
                    'pembelajaran.status_di_kurikulum_str',
                    'gtk.nama as nama_guru',
                    'gtk.nuptk',
                    'gtk.nip'
                )
                ->orderBy('pembelajaran.nama_mata_pelajaran', 'asc')
                ->get();
        }

        $fotoUrl = !empty($pesertaDidik->foto_path) 
            ? asset('storage/' . ltrim($pesertaDidik->foto_path, '/')) 
            : ($meta?->foto_url ?? null);

        return response()->json([
            'status'       => 'success',
            'data'         => $pesertaDidik,
            'anggota'      => $anggota,
            'meta'         => $meta,
            'foto_url'     => $fotoUrl,
            'foto_size'    => $meta?->formatted_foto_size,
            'pembelajaran' => $pembelajaran,
            'total_mapel'  => $pembelajaran->count(),
            'total_jam'    => $pembelajaran->sum(fn($p) => (int) ($p->jam_mengajar_per_minggu ?? 0)),
        ]);
    }
}
