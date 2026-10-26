<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;
use App\Models\AdminAktivitas;
use App\Models\KesiswaanBukuKlaper;
use App\Models\PesertaDidik;
use App\Models\PesertaDidikIdentitas;
use App\Models\PesertaDidikMeta;
use App\Models\RolePermission;
use App\Models\Sekolah;
use App\Models\SekolahMeta;
use App\Models\TendikAktivitas;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BukuIndukController extends Controller
{
    use ChecksAuthenticatedSession;

    /**
     * Tampilan Utama Pengelolaan Buku Induk Peserta Didik
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_buku_induk', 'create') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_buku_induk', 'read') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_buku_induk', 'update') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_buku_induk', 'delete') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'delete');

        // Parameter Filter & Pencarian
        $q           = trim($request->input('q', ''));
        $rombelId    = $request->input('rombel_id', '');
        $tahunMasuk  = $request->input('tahun_masuk', '');
        $status      = $request->input('status', 'aktif'); // aktif, lulus, mutasi_keluar, do, all
        $kelengkapan = $request->input('kelengkapan', 'all'); // lengkap, belum_lengkap, all
        $perPageVal  = $request->input('perPage', $request->input('per_page', 25));
        $perPage     = in_array((int) $perPageVal, [10, 15, 25, 50, 100], true) ? (int) $perPageVal : 25;

        // Sorting
        $sort    = $request->input('sort', 'nama');
        $sortDir = strtolower($request->input('sort_dir', $request->input('dir', 'asc'))) === 'desc' ? 'desc' : 'asc';
        $allowedSorts = ['nama', 'nisn', 'nipd', 'rombel_nama', 'tahun_masuk', 'status_klaper', 'tanggal_lahir'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'nama';
        }

        // Statistik Ringkasan Buku Induk
        $totalSiswa = DB::table('peserta_didik')->count();
        $totalAktif = DB::table('peserta_didik')
            ->where(function ($w) {
                $w->whereNull('status_keaktifan_id_str')
                  ->orWhere('status_keaktifan_id_str', 'LIKE', '%aktif%')
                  ->orWhere('status_keaktifan_id_str', '1');
            })
            ->count();
        $totalLulus = DB::table('kesiswaan_buku_klaper')->where('status_klaper', 'lulus')->count();
        $totalMutasi = DB::table('kesiswaan_buku_klaper')->whereIn('status_klaper', ['mutasi_keluar', 'do'])->count();
        $totalIdentitasTerisi = DB::table('peserta_didik_identitas')
            ->whereNotNull('nik')
            ->where('nik', '!=', '')
            ->count();

        $stats = [
            'total_siswa'     => $totalSiswa,
            'total_aktif'     => $totalAktif,
            'total_lulus'     => $totalLulus,
            'total_mutasi'    => $totalMutasi,
            'total_lengkap'   => $totalIdentitasTerisi,
        ];

        // Query Dasar Siswa dengan Left Join
        $query = DB::table('peserta_didik as pd')
            ->leftJoin('peserta_didik_identitas as pdi', 'pd.peserta_didik_id', '=', 'pdi.peserta_didik_id')
            ->leftJoin('kesiswaan_buku_klaper as kbk', 'pd.peserta_didik_id', '=', 'kbk.peserta_didik_id')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->leftJoin('anggota_rombel as ar', 'pd.peserta_didik_id', '=', 'ar.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->select([
                'pd.peserta_didik_id',
                'pd.nama',
                'pd.nisn',
                'pd.nipd',
                'pd.nik',
                'pd.jenis_kelamin',
                'pd.tempat_lahir',
                'pd.tanggal_lahir',
                'pd.nama_ayah',
                'pd.nama_ibu',
                'pd.nama_wali',
                'pd.alamat_jalan',
                'rb.rombongan_belajar_id',
                'rb.nama as rombel_nama',
                'pdm.foto_url',
                'kbk.id as klaper_id',
                'kbk.nomor_klaper',
                'kbk.nomor_induk',
                'kbk.tahun_masuk',
                'kbk.status_klaper',
                'pdi.id as identitas_id',
                'pdi.no_kk',
                'pdi.no_registrasi_akta_lahir',
                'pdi.status_konfirmasi',
                'pdi.dikonfirmasi_pada',
            ]);

        // Filter Status Keaktifan / Klaper
        if ($status !== '' && $status !== 'all') {
            if ($status === 'aktif') {
                $query->where(function ($w) {
                    $w->whereNull('kbk.status_klaper')
                      ->orWhere('kbk.status_klaper', 'aktif');
                });
            } else {
                $query->where('kbk.status_klaper', $status);
            }
        }

        // Filter Rombel
        if (!empty($rombelId)) {
            $query->where('rb.rombongan_belajar_id', $rombelId);
        }

        // Filter Angkatan / Tahun Masuk
        if (!empty($tahunMasuk)) {
            $query->where('kbk.tahun_masuk', (int) $tahunMasuk);
        }

        // Filter Kelengkapan Data
        if ($kelengkapan === 'lengkap') {
            $query->whereNotNull('pdi.nik')
                  ->where('pdi.nik', '!=', '')
                  ->whereNotNull('pdi.no_kk')
                  ->where('pdi.no_kk', '!=', '');
        } elseif ($kelengkapan === 'belum_lengkap') {
            $query->where(function ($w) {
                $w->whereNull('pdi.nik')
                  ->orWhere('pdi.nik', '')
                  ->orWhereNull('pdi.no_kk')
                  ->orWhere('pdi.no_kk', '');
            });
        }

        // Pencarian Kata Kunci
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('pd.nama', 'like', "%{$q}%")
                  ->orWhere('pd.nisn', 'like', "%{$q}%")
                  ->orWhere('pd.nipd', 'like', "%{$q}%")
                  ->orWhere('pd.nik', 'like', "%{$q}%")
                  ->orWhere('kbk.nomor_induk', 'like', "%{$q}%")
                  ->orWhere('kbk.nomor_klaper', 'like', "%{$q}%")
                  ->orWhere('pdi.no_kk', 'like', "%{$q}%");
            });
        }

        // Eksekusi Sorting & Pagination
        if ($sort === 'rombel_nama') {
            $query->orderBy('rb.nama', $sortDir);
        } elseif ($sort === 'tahun_masuk') {
            $query->orderBy('kbk.tahun_masuk', $sortDir);
        } elseif ($sort === 'status_klaper') {
            $query->orderBy('kbk.status_klaper', $sortDir);
        } else {
            $query->orderBy('pd.' . $sort, $sortDir);
        }

        $items = $query->paginate($perPage)->withQueryString();

        // Master List untuk Filter Dropdown
        $rombelList = DB::table('rombongan_belajar')
            ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id')
            ->orderBy('tingkat_pendidikan_id')
            ->orderBy('nama')
            ->get();

        $tahunMasukList = DB::table('kesiswaan_buku_klaper')
            ->whereNotNull('tahun_masuk')
            ->select('tahun_masuk')
            ->distinct()
            ->orderBy('tahun_masuk', 'desc')
            ->pluck('tahun_masuk');

        if ($tahunMasukList->isEmpty()) {
            $curY = (int) date('Y');
            $tahunMasukList = collect([$curY, $curY - 1, $curY - 2]);
        }

        return view('dashboard.kesiswaan.buku-induk.index', compact(
            'stats',
            'items',
            'q',
            'rombelId',
            'tahunMasuk',
            'status',
            'kelengkapan',
            'perPage',
            'sort',
            'sortDir',
            'rombelList',
            'tahunMasukList',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete'
        ));
    }

    /**
     * Detail Data Siswa untuk Modal Form Buku Induk (JSON)
     */
    public function show($id): JsonResponse
    {
        if ($res = $this->checkAuth()) {
            return response()->json(['status' => 'error', 'message' => 'Sesi berakhir.'], 401);
        }

        $pd = PesertaDidik::where('peserta_didik_id', $id)->first();
        if (!$pd && Schema::hasTable('peserta_didik_tidak_aktif')) {
            $pd = DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->first();
        }

        if (!$pd) {
            return response()->json(['status' => 'error', 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        // Pastikan record identitas terinisialisasi
        $identitas = PesertaDidikIdentitas::getOrCreateFromPd($pd);

        // Record Buku Klaper
        $klaper = KesiswaanBukuKlaper::where('peserta_didik_id', $pd->peserta_didik_id)->first();

        // Foto Siswa
        $meta = PesertaDidikMeta::where('peserta_didik_id', $pd->peserta_didik_id)->first();

        // Rombel Siswa
        $rombel = DB::table('anggota_rombel as ar')
            ->join('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('ar.peserta_didik_id', $pd->peserta_didik_id)
            ->select('rb.nama as rombel_nama', 'rb.tingkat_pendidikan_id')
            ->first();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'peserta_didik_id' => $pd->peserta_didik_id,
                'nama'             => $pd->nama,
                'nisn'             => $pd->nisn,
                'nipd'             => $klaper?->nomor_induk ?: ($pd->nipd ?: $identitas->nipd),
                'nomor_klaper'     => $klaper?->nomor_klaper ?? '',
                'nik'              => $identitas->nik ?: $pd->nik,
                'no_kk'            => $identitas->no_kk,
                'no_registrasi_akta_lahir' => $identitas->no_registrasi_akta_lahir,
                'kewarganegaraan'  => $identitas->kewarganegaraan ?: 'WNI',
                'tempat_lahir'     => $identitas->tempat_lahir ?: $pd->tempat_lahir,
                'tanggal_lahir'    => $identitas->tanggal_lahir ? $identitas->tanggal_lahir->format('Y-m-d') : ($pd->tanggal_lahir ? substr($pd->tanggal_lahir, 0, 10) : ''),
                'jenis_kelamin'    => $identitas->jenis_kelamin ?: $pd->jenis_kelamin,
                'agama_id'         => $identitas->agama_id ?: ($pd->agama_id ?? '1'),
                'agama_id_str'     => $identitas->agama_id_str ?: ($pd->agama_id_str ?? 'Islam'),
                'anak_keberapa'    => $identitas->anak_keberapa ?? 1,
                'jumlah_saudara_kandung' => $identitas->jumlah_saudara_kandung ?? null,
                'tinggi_badan'     => $identitas->tinggi_badan ?: $pd->tinggi_badan,
                'berat_badan'      => $identitas->berat_badan ?: $pd->berat_badan,
                'lingkar_kepala'   => $identitas->lingkar_kepala ?? null,
                'golongan_darah'   => $identitas->golongan_darah ?? '-',
                'penyakit_pernah_diderita' => $identitas->penyakit_pernah_diderita ?? '',
                'kelainan_jasmani' => $identitas->kelainan_jasmani ?? 'Tidak ada',
                'bahasa_sehari_hari' => $identitas->bahasa_sehari_hari ?? 'Indonesia / Sunda',
                'alamat_jalan'     => $identitas->alamat_jalan ?: $pd->alamat_jalan,
                'rt'               => $identitas->rt,
                'rw'               => $identitas->rw,
                'nama_dusun'       => $identitas->nama_dusun,
                'desa_kelurahan'   => $identitas->desa_kelurahan,
                'kecamatan'        => $identitas->kecamatan,
                'kabupaten_kota'   => $identitas->kabupaten_kota,
                'provinsi'         => $identitas->provinsi,
                'kode_pos'         => $identitas->kode_pos,
                'tempat_tinggal_str' => $identitas->tempat_tinggal_str ?: 'Bersama orang tua',
                'transportasi_str' => $identitas->transportasi_str ?: 'Jalan kaki',
                'jarak_rumah_sekolah' => $identitas->jarak_rumah_sekolah,
                'waktu_tempuh_menit' => $identitas->waktu_tempuh_menit,

                // Orang Tua
                'status_hidup_ayah' => $identitas->status_hidup_ayah ?? '1',
                'nama_ayah'        => $identitas->nama_ayah ?: $pd->nama_ayah,
                'nik_ayah'         => $identitas->nik_ayah,
                'tahun_lahir_ayah' => $identitas->tahun_lahir_ayah,
                'pendidikan_ayah_str' => $identitas->pendidikan_ayah_str,
                'pekerjaan_ayah_str' => $identitas->pekerjaan_ayah_str ?: $pd->pekerjaan_ayah_id_str,
                'penghasilan_ayah_str' => $identitas->penghasilan_ayah_str,

                'status_hidup_ibu' => $identitas->status_hidup_ibu ?? '1',
                'nama_ibu'         => $identitas->nama_ibu ?: $pd->nama_ibu,
                'nik_ibu'          => $identitas->nik_ibu,
                'tahun_lahir_ibu'  => $identitas->tahun_lahir_ibu,
                'pendidikan_ibu_str' => $identitas->pendidikan_ibu_str,
                'pekerjaan_ibu_str' => $identitas->pekerjaan_ibu_str ?: $pd->pekerjaan_ibu_id_str,
                'penghasilan_ibu_str' => $identitas->penghasilan_ibu_str,

                'mempunyai_wali'   => (bool) $identitas->mempunyai_wali,
                'nama_wali'        => $identitas->nama_wali ?: $pd->nama_wali,
                'nik_wali'         => $identitas->nik_wali,
                'hubungan_wali'    => $identitas->hubungan_wali,
                'pekerjaan_wali_str' => $identitas->pekerjaan_wali_str ?: $pd->pekerjaan_wali_id_str,

                // Kontak
                'nomor_telepon_rumah' => $identitas->nomor_telepon_rumah,
                'nomor_telepon_seluler' => $identitas->nomor_telepon_seluler ?: $pd->nomor_telepon_seluler,
                'email'            => $identitas->email ?: $pd->email,

                // Registrasi Masuk & Sekolah Asal
                'sekolah_asal'     => $identitas->sekolah_asal ?: $pd->sekolah_asal,
                'tanggal_masuk_sekolah' => $identitas->tanggal_masuk_sekolah ? $identitas->tanggal_masuk_sekolah->format('Y-m-d') : ($pd->tanggal_masuk_sekolah ? substr($pd->tanggal_masuk_sekolah, 0, 10) : ''),
                'tahun_masuk'      => $klaper?->tahun_masuk ?: date('Y'),
                'status_klaper'    => $klaper?->status_klaper ?: 'aktif',
                'keterangan_klaper' => $klaper?->keterangan ?? '',

                // Metadata
                'rombel_nama'      => $rombel?->rombel_nama ?? '-',
                'foto_url'         => $meta?->foto_url ?? null,
                'catatan_kesiswaan' => $identitas->catatan_kesiswaan,
            ]
        ]);
    }

    /**
     * Simpan Perubahan Data Buku Induk Siswa
     */
    public function update(Request $request, $id): JsonResponse
    {
        if ($res = $this->checkAuth()) {
            return response()->json(['status' => 'error', 'message' => 'Sesi berakhir.'], 401);
        }

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_buku_induk', 'update') && !RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $pd = PesertaDidik::where('peserta_didik_id', $id)->first();
        if (!$pd && Schema::hasTable('peserta_didik_tidak_aktif')) {
            $pd = DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->first();
        }

        if (!$pd) {
            return response()->json(['status' => 'error', 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'nama'                     => 'required|string|max:150',
            'nisn'                     => 'nullable|string|max:15',
            'nipd'                     => 'nullable|string|max:30',
            'nomor_klaper'             => 'nullable|string|max:30',
            'nik'                      => 'nullable|string|max:20',
            'no_kk'                    => 'nullable|string|max:20',
            'no_registrasi_akta_lahir' => 'nullable|string|max:80',
            'tempat_lahir'             => 'nullable|string|max:60',
            'tanggal_lahir'            => 'nullable|date',
            'jenis_kelamin'            => 'required|in:L,P',
            'agama_id_str'             => 'nullable|string|max:50',
            'kewarganegaraan'          => 'nullable|string|max:10',
            'anak_keberapa'            => 'nullable|integer|min:1',
            'jumlah_saudara_kandung'   => 'nullable|integer|min:0',
            'alamat_jalan'             => 'nullable|string|max:255',
            'rt'                       => 'nullable|string|max:5',
            'rw'                       => 'nullable|string|max:5',
            'desa_kelurahan'           => 'nullable|string|max:100',
            'kecamatan'                => 'nullable|string|max:100',
            'kabupaten_kota'           => 'nullable|string|max:100',
            'kode_pos'                 => 'nullable|string|max:10',
            'nama_ayah'                => 'nullable|string|max:150',
            'nik_ayah'                 => 'nullable|string|max:20',
            'pekerjaan_ayah_str'       => 'nullable|string|max:80',
            'nama_ibu'                 => 'nullable|string|max:150',
            'nik_ibu'                  => 'nullable|string|max:20',
            'pekerjaan_ibu_str'        => 'nullable|string|max:80',
            'nama_wali'                => 'nullable|string|max:150',
            'nik_wali'                 => 'nullable|string|max:20',
            'hubungan_wali'            => 'nullable|string|max:60',
            'nomor_telepon_seluler'    => 'nullable|string|max:25',
            'sekolah_asal'             => 'nullable|string|max:150',
            'tahun_masuk'              => 'nullable|integer',
            'status_klaper'            => 'required|in:aktif,lulus,mutasi_keluar,do',
            'catatan_kesiswaan'        => 'nullable|string|max:500',
        ]);

        $userName = is_array($user) ? ($user['nama'] ?? 'Staf Kesiswaan') : ($user->nama ?? 'Staf Kesiswaan');

        // 1. Simpan ke peserta_didik_identitas
        $identitas = PesertaDidikIdentitas::getOrCreateFromPd($pd);
        $identitas->update([
            'nama'                     => $validated['nama'],
            'nisn'                     => $validated['nisn'] ?? $identitas->nisn,
            'nipd'                     => $validated['nipd'] ?? $identitas->nipd,
            'nik'                      => $validated['nik'] ?? null,
            'no_kk'                    => $validated['no_kk'] ?? null,
            'no_registrasi_akta_lahir' => $validated['no_registrasi_akta_lahir'] ?? null,
            'tempat_lahir'             => $validated['tempat_lahir'] ?? null,
            'tanggal_lahir'            => $validated['tanggal_lahir'] ?? null,
            'jenis_kelamin'            => $validated['jenis_kelamin'],
            'agama_id_str'             => $validated['agama_id_str'] ?? 'Islam',
            'kewarganegaraan'          => $validated['kewarganegaraan'] ?? 'WNI',
            'anak_keberapa'            => $validated['anak_keberapa'] ?? 1,
            'jumlah_saudara_kandung'   => $validated['jumlah_saudara_kandung'] ?? null,
            'alamat_jalan'             => $validated['alamat_jalan'] ?? null,
            'rt'                       => $validated['rt'] ?? null,
            'rw'                       => $validated['rw'] ?? null,
            'desa_kelurahan'           => $validated['desa_kelurahan'] ?? null,
            'kecamatan'                => $validated['kecamatan'] ?? null,
            'kabupaten_kota'           => $validated['kabupaten_kota'] ?? null,
            'kode_pos'                 => $validated['kode_pos'] ?? null,
            'nama_ayah'                => $validated['nama_ayah'] ?? null,
            'nik_ayah'                 => $validated['nik_ayah'] ?? null,
            'pekerjaan_ayah_str'       => $validated['pekerjaan_ayah_str'] ?? null,
            'nama_ibu'                 => $validated['nama_ibu'] ?? null,
            'nik_ibu'                  => $validated['nik_ibu'] ?? null,
            'pekerjaan_ibu_str'        => $validated['pekerjaan_ibu_str'] ?? null,
            'nama_wali'                => $validated['nama_wali'] ?? null,
            'nik_wali'                 => $validated['nik_wali'] ?? null,
            'hubungan_wali'            => $validated['hubungan_wali'] ?? null,
            'nomor_telepon_seluler'    => $validated['nomor_telepon_seluler'] ?? null,
            'sekolah_asal'             => $validated['sekolah_asal'] ?? null,
            'catatan_kesiswaan'        => $validated['catatan_kesiswaan'] ?? null,
            'terakhir_diubah_oleh'     => $userName,
        ]);

        // 2. Simpan ke kesiswaan_buku_klaper
        $klaper = KesiswaanBukuKlaper::firstOrNew(['peserta_didik_id' => $pd->peserta_didik_id]);
        $klaper->nomor_induk   = $validated['nipd'] ?? ($pd->nipd ?: $pd->nisn);
        $klaper->nomor_klaper  = $validated['nomor_klaper'] ?? $klaper->nomor_klaper;
        $klaper->tahun_masuk   = $validated['tahun_masuk'] ?? ($klaper->tahun_masuk ?: date('Y'));
        $klaper->status_klaper = $validated['status_klaper'];
        $klaper->huruf_abjad   = strtoupper(substr($validated['nama'], 0, 1));
        $klaper->save();

        // 3. Sinkronkan nilai inti ke peserta_didik jika aktif
        if (Schema::hasTable('peserta_didik')) {
            DB::table('peserta_didik')
                ->where('peserta_didik_id', $pd->peserta_didik_id)
                ->update([
                    'nama'          => $validated['nama'],
                    'nipd'          => $validated['nipd'] ?? $pd->nipd,
                    'nisn'          => $validated['nisn'] ?? $pd->nisn,
                    'nik'           => $validated['nik'] ?? $pd->nik,
                    'jenis_kelamin' => $validated['jenis_kelamin'],
                    'tempat_lahir'  => $validated['tempat_lahir'] ?? $pd->tempat_lahir,
                    'tanggal_lahir' => $validated['tanggal_lahir'] ?? $pd->tanggal_lahir,
                    'alamat_jalan'  => $validated['alamat_jalan'] ?? $pd->alamat_jalan,
                    'nama_ayah'     => $validated['nama_ayah'] ?? $pd->nama_ayah,
                    'nama_ibu'      => $validated['nama_ibu'] ?? $pd->nama_ibu,
                    'nama_wali'     => $validated['nama_wali'] ?? $pd->nama_wali,
                    'nomor_telepon_seluler' => $validated['nomor_telepon_seluler'] ?? $pd->nomor_telepon_seluler,
                ]);
        }

        // Catat Log Audit Administrasi
        if (class_exists(AdminAktivitas::class)) {
            AdminAktivitas::record(
                "Pembaruan Buku Induk Siswa: {$validated['nama']} (NISN: {$pd->nisn})",
                'Kesiswaan',
                "Data register buku induk diperbarui oleh {$userName}.",
                'info'
            );
        }

        if (class_exists(TendikAktivitas::class)) {
            TendikAktivitas::recordActivity($user, "Pembaruan Buku Induk Siswa: {$validated['nama']}", 'kesiswaan', "Memperbarui lembar buku induk peserta didik {$validated['nama']} (NISN: {$pd->nisn})", 'selesai', '1 Dokumen Siswa Termutakhirkan');
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Data Buku Induk siswa {$validated['nama']} berhasil diperbarui.",
        ]);
    }

    /**
     * Cetak Lembar Resmi Buku Induk Peserta Didik (Format Standar Kemdikbud)
     */
    public function cetak(Request $request, $id)
    {
        if ($res = $this->checkAuth()) return $res;

        $pd = PesertaDidik::where('peserta_didik_id', $id)->first();
        if (!$pd && Schema::hasTable('peserta_didik_tidak_aktif')) {
            $pd = DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->first();
        }

        if (!$pd) {
            abort(404, 'Data peserta didik tidak ditemukan.');
        }

        $identitas = PesertaDidikIdentitas::getOrCreateFromPd($pd);
        $klaper = KesiswaanBukuKlaper::where('peserta_didik_id', $pd->peserta_didik_id)->first();
        $meta = PesertaDidikMeta::where('peserta_didik_id', $pd->peserta_didik_id)->first();
        $sekolah = Sekolah::first();
        $sekolahMeta = SekolahMeta::first();

        // Rombel Siswa
        $rombel = DB::table('anggota_rombel as ar')
            ->join('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('ar.peserta_didik_id', $pd->peserta_didik_id)
            ->select('rb.nama as rombel_nama')
            ->first();

        // Cari Kepala Sekolah
        $kepsek = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        // Cari Petugas Buku Induk / Staf Kesiswaan
        $petugasKesiswaan = DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->join('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
            ->whereIn('rtt.kode', ['STAF_KESISWAAN', 'WAKA_KESISWAAN', 'KEPALA_TAS'])
            ->where('ptt.is_active', true)
            ->select('gtk.nama', 'gtk.nip')
            ->first();

        $qrPayload = route('dashboard.kesiswaan.buku-induk.show', $pd->peserta_didik_id);
        $qrUri = QrCodeService::generateDataUri($qrPayload, 120, 1);

        $orientasi = strtolower($request->input('orientasi', 'portrait'));

        return view('dashboard.kesiswaan.buku-induk.cetak', compact(
            'pd',
            'identitas',
            'klaper',
            'meta',
            'sekolah',
            'sekolahMeta',
            'rombel',
            'kepsek',
            'petugasKesiswaan',
            'qrUri',
            'orientasi'
        ));
    }

    /**
     * Cetak Register Rekapitulasi Buku Induk Siswa per Rombel / Angkatan
     */
    public function cetakRekap(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $rombelId   = $request->input('rombel_id', '');
        $tahunMasuk = $request->input('tahun_masuk', '');
        $status     = $request->input('status', 'aktif');

        $query = DB::table('peserta_didik as pd')
            ->leftJoin('peserta_didik_identitas as pdi', 'pd.peserta_didik_id', '=', 'pdi.peserta_didik_id')
            ->leftJoin('kesiswaan_buku_klaper as kbk', 'pd.peserta_didik_id', '=', 'kbk.peserta_didik_id')
            ->leftJoin('anggota_rombel as ar', 'pd.peserta_didik_id', '=', 'ar.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->select([
                'pd.peserta_didik_id',
                'pd.nama',
                'pd.nisn',
                'pd.nipd',
                'pd.nik',
                'pd.jenis_kelamin',
                'pd.tempat_lahir',
                'pd.tanggal_lahir',
                'pd.nama_ayah',
                'pd.nama_ibu',
                'pd.alamat_jalan',
                'rb.nama as rombel_nama',
                'kbk.nomor_klaper',
                'kbk.nomor_induk',
                'kbk.tahun_masuk',
                'kbk.status_klaper',
                'pdi.sekolah_asal',
            ]);

        if (!empty($rombelId)) {
            $query->where('rb.rombongan_belajar_id', $rombelId);
        }

        if (!empty($tahunMasuk)) {
            $query->where('kbk.tahun_masuk', (int) $tahunMasuk);
        }

        if ($status !== '' && $status !== 'all') {
            if ($status === 'aktif') {
                $query->where(function ($w) {
                    $w->whereNull('kbk.status_klaper')
                      ->orWhere('kbk.status_klaper', 'aktif');
                });
            } else {
                $query->where('kbk.status_klaper', $status);
            }
        }

        $items = $query->orderBy('rb.nama', 'asc')->orderBy('pd.nama', 'asc')->get();

        $sekolah = Sekolah::first();
        $sekolahMeta = SekolahMeta::first();
        $rombel = !empty($rombelId) ? DB::table('rombongan_belajar')->where('rombongan_belajar_id', $rombelId)->first() : null;

        $kepsek = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        $orientasi = 'landscape';

        return view('dashboard.kesiswaan.buku-induk.cetak-rekap', compact(
            'items',
            'sekolah',
            'sekolahMeta',
            'rombel',
            'tahunMasuk',
            'status',
            'kepsek',
            'orientasi'
        ));
    }

    /**
     * Sinkronisasi Otomatis Seluruh Siswa ke Buku Klaper & Identitas
     */
    public function syncBukuInduk(Request $request): JsonResponse
    {
        if ($res = $this->checkAuth()) {
            return response()->json(['status' => 'error', 'message' => 'Sesi berakhir.'], 401);
        }

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_buku_induk', 'create') && !RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'create')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $students = PesertaDidik::orderBy('nama', 'asc')->get();
        $syncedKlaper = 0;
        $syncedIdentitas = 0;

        foreach ($students as $index => $st) {
            // Inisialisasi Buku Klaper jika belum ada
            $klaper = KesiswaanBukuKlaper::where('peserta_didik_id', $st->peserta_didik_id)->first();
            if (!$klaper) {
                $firstLetter = strtoupper(substr($st->nama, 0, 1));
                KesiswaanBukuKlaper::create([
                    'peserta_didik_id' => $st->peserta_didik_id,
                    'nomor_klaper'     => $firstLetter . '-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                    'nomor_induk'      => $st->nipd ?: ($st->nisn ?: str_pad($index + 1, 4, '0', STR_PAD_LEFT)),
                    'huruf_abjad'      => $firstLetter,
                    'tahun_masuk'      => date('Y'),
                    'status_klaper'    => 'aktif',
                ]);
                $syncedKlaper++;
            }

            // Inisialisasi PesertaDidikIdentitas jika belum ada
            $hasIdentitas = PesertaDidikIdentitas::where('peserta_didik_id', $st->peserta_didik_id)->exists();
            if (!$hasIdentitas) {
                PesertaDidikIdentitas::getOrCreateFromPd($st);
                $syncedIdentitas++;
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Sinkronisasi Buku Induk selesai. {$syncedKlaper} nomor klaper dan {$syncedIdentitas} profil identitas baru dibuat.",
        ]);
    }
}
