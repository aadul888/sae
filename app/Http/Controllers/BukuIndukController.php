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
use App\Services\BukuIndukNilaiService;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\KelasAkhirScope;
use App\Support\Table;
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
        $kelengkapan = $request->input('kelengkapan', 'all'); // lengkap, belum_lengkap, all
        $perPageVal  = $request->input('perPage', $request->input('per_page', 25));
        $perPage     = in_array((int) $perPageVal, [10, 15, 25, 50, 100], true) ? (int) $perPageVal : 25;

        // Tahun ajaran dari Pengaturan Buku Induk menentukan cohort yang tampil:
        // tahun ajaran aktif → kelas akhir (XII) sekarang; tahun ajaran lampau → Alumni tahun itu.
        $tahunAjaranAktif     = BukuIndukNilaiService::tahunAjaranAktif();
        $tahunAjaranTerpilih  = (string) (BukuIndukNilaiService::pengaturan()->tahun_ajaran ?? '') ?: $tahunAjaranAktif;
        $tahunLulusAktif      = str_contains($tahunAjaranAktif, '/') ? explode('/', $tahunAjaranAktif)[1] : $tahunAjaranAktif;
        $tahunLulusPengaturan = str_contains($tahunAjaranTerpilih, '/') ? explode('/', $tahunAjaranTerpilih)[1] : $tahunAjaranTerpilih;

        // Daftar tahun kelulusan: tahun kelulusan aktif digabung alumni yang tamat/lulus
        $alumniTahunList = DB::table('peserta_didik_tidak_aktif')
            ->where(function ($b) {
                $b->where('status_keluar', 'Alumni')
                  ->orWhere('alasan_keluar', 'like', '%Lulus%')
                  ->orWhere('alasan_keluar', 'like', '%Tamat%');
            })
            ->selectRaw("COALESCE(NULLIF(YEAR(tanggal_keluar), 0), NULLIF(tahun_lulus, '')) AS th")
            ->pluck('th')
            ->filter(fn ($v) => !empty($v) && is_numeric($v))
            ->unique();

        $filterTahunList = collect([$tahunLulusAktif])->merge($alumniTahunList)->unique()->sortDesc()->values();

        $tahunLulus = $request->input('tahun_lulus');
        if (empty($tahunLulus) || !$filterTahunList->contains($tahunLulus)) {
            $tahunLulus = $tahunLulusPengaturan ?: ($filterTahunList->first() ?? $tahunLulusAktif);
        }

        $isAktif = ((string) $tahunLulus === (string) $tahunLulusAktif);

        // Sorting
        $sort    = $request->input('sort', 'nama');
        $sortDir = strtolower($request->input('sort_dir', $request->input('dir', 'asc'))) === 'desc' ? 'desc' : 'asc';
        $allowedSorts = ['nama', 'nisn', 'nipd', 'rombel_nama', 'status_klaper', 'tanggal_lahir'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'nama';
        }

        if (!$isAktif) {
            // Base query cohort alumni yang tamat/lulus (bukan DO / mutasi keluar)
            $alumniQuery = DB::table('peserta_didik_tidak_aktif')
                ->where(function ($w) use ($tahunLulus) {
                    $w->where('tahun_lulus', (string) $tahunLulus)
                      ->orWhere('tahun_lulus', "{$tahunLulus}/" . ((int) $tahunLulus + 1))
                      ->orWhere('tahun_lulus', ((int) $tahunLulus - 1) . "/{$tahunLulus}")
                      ->orWhereYear('tanggal_keluar', (string) $tahunLulus);
                })
                ->where(function ($b) {
                    $b->where('status_keluar', 'Alumni')
                      ->orWhere('alasan_keluar', 'like', '%Lulus%')
                      ->orWhere('alasan_keluar', 'like', '%Tamat%');
                });

            // Rombel dropdown dari cohort alumni (lulusan/alumni)
            $rombelList = (clone $alumniQuery)
                ->whereNotNull('nama_rombel_terakhir')
                ->where('nama_rombel_terakhir', '<>', '')
                ->distinct()
                ->orderBy('nama_rombel_terakhir')
                ->pluck('nama_rombel_terakhir')
                ->map(fn ($nama) => (object) [
                    'rombongan_belajar_id' => $nama,
                    'nama' => $nama,
                ]);

            $angkatanMasuk = (int) $tahunLulus > 2000 ? (int) $tahunLulus - 3 : null;

            // Filter Rombel
            if (!empty($rombelId)) {
                $alumniQuery->where(function ($w) use ($rombelId) {
                    $w->where('nama_rombel_terakhir', $rombelId)
                      ->orWhere('rombongan_belajar_id', $rombelId);
                });
            }

            // Filter Kelengkapan Data
            if ($kelengkapan === 'lengkap') {
                $alumniQuery->whereNotNull('nik')->where('nik', '!=', '');
            } elseif ($kelengkapan === 'belum_lengkap') {
                $alumniQuery->where(function ($w) {
                    $w->whereNull('nik')->orWhere('nik', '');
                });
            }

            // Pencarian Kata Kunci
            if ($q !== '') {
                $alumniQuery->where(function ($w) use ($q) {
                    $w->where('nama', 'like', "%{$q}%")
                      ->orWhere('nisn', 'like', "%{$q}%")
                      ->orWhere('nipd', 'like', "%{$q}%")
                      ->orWhere('nik', 'like', "%{$q}%");
                });
            }

            // Statistik terfilter datatable alumni
            $statRow = (clone $alumniQuery)
                ->selectRaw("
                    COUNT(*) as total_siswa,
                    SUM(CASE WHEN jenis_kelamin = 'L' THEN 1 ELSE 0 END) as total_l,
                    SUM(CASE WHEN jenis_kelamin = 'P' THEN 1 ELSE 0 END) as total_p,
                    SUM(CASE WHEN EXISTS (
                        SELECT 1 FROM buku_induk_nilai WHERE buku_induk_nilai.peserta_didik_id = peserta_didik_tidak_aktif.peserta_didik_id
                    ) THEN 1 ELSE 0 END) as total_siap_cetak
                ")
                ->first();

            $stats = [
                'total_siswa'      => (int) ($statRow->total_siswa ?? 0),
                'total_l'          => (int) ($statRow->total_l ?? 0),
                'total_p'          => (int) ($statRow->total_p ?? 0),
                'total_siap_cetak' => (int) ($statRow->total_siap_cetak ?? 0),
            ];

            $sortColumn = match ($sort) {
                'nipd' => 'nipd',
                'nisn' => 'nisn',
                'rombel_nama' => 'nama_rombel_terakhir',
                'tanggal_lahir' => 'tanggal_lahir',
                default => 'nama',
            };

            $items = $alumniQuery
                ->select([
                    'peserta_didik_id', 'nama', 'nisn', 'nipd', 'nik', 'jenis_kelamin',
                    'tempat_lahir', 'tanggal_lahir', 'nama_ayah', 'nama_ibu', 'nama_wali',
                    'nama_rombel_terakhir', 'tahun_lulus', 'foto_path', 'status_keluar',
                ])
                ->selectRaw("NULL as no_kk, nipd as nomor_induk, 'lulus' as status_klaper, ? as tahun_masuk", [
                    $angkatanMasuk
                ])
                ->orderBy($sortColumn, $sortDir)
                ->paginate($perPage)
                ->withQueryString();

            foreach ($items as $item) {
                $item->rombel_nama = $item->nama_rombel_terakhir;
                $item->foto_url = !empty($item->foto_path)
                    ? asset('storage/' . ltrim($item->foto_path, '/'))
                    : null;
            }
        } else {
            // Query Dasar Siswa dengan Left Join
            // ponytail: rombel diambil dari kolom denormalisasi peserta_didik (bukan join anggota_rombel) agar 1 siswa = 1 baris.
            $query = DB::table('peserta_didik as pd')
                ->leftJoin('peserta_didik_identitas as pdi', 'pd.peserta_didik_id', '=', 'pdi.peserta_didik_id')
                ->leftJoin('kesiswaan_buku_klaper as kbk', 'pd.peserta_didik_id', '=', 'kbk.peserta_didik_id')
                ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
                ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id');

            // Hanya kelas akhir (tingkat XII)
            KelasAkhirScope::apply($query);

            // Hanya siswa aktif terakhir (bukan yang dikeluarkan atau pindah/mutasi)
            $query->where(function ($w) {
                $w->whereNull('kbk.status_klaper')
                  ->orWhere('kbk.status_klaper', 'aktif');
            });

            // Filter Rombel
            if (!empty($rombelId)) {
                $query->where('pd.rombongan_belajar_id', $rombelId);
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

            // Statistik terfilter datatable aktif
            $statRow = (clone $query)
                ->selectRaw("
                    COUNT(DISTINCT pd.peserta_didik_id) as total_siswa,
                    SUM(CASE WHEN pd.jenis_kelamin = 'L' THEN 1 ELSE 0 END) as total_l,
                    SUM(CASE WHEN pd.jenis_kelamin = 'P' THEN 1 ELSE 0 END) as total_p,
                    SUM(CASE WHEN EXISTS (
                        SELECT 1 FROM buku_induk_nilai WHERE buku_induk_nilai.peserta_didik_id = pd.peserta_didik_id
                    ) THEN 1 ELSE 0 END) as total_siap_cetak
                ")
                ->first();

            $stats = [
                'total_siswa'      => (int) ($statRow->total_siswa ?? 0),
                'total_l'          => (int) ($statRow->total_l ?? 0),
                'total_p'          => (int) ($statRow->total_p ?? 0),
                'total_siap_cetak' => (int) ($statRow->total_siap_cetak ?? 0),
            ];

            // Eksekusi Sorting & Pagination
            if ($sort === 'rombel_nama') {
                $query->orderBy('pd.nama_rombel', $sortDir);
            } elseif ($sort === 'status_klaper') {
                $query->orderBy('kbk.status_klaper', $sortDir);
            } else {
                $query->orderBy('pd.' . $sort, $sortDir);
            }

            $items = $query
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
                    'pd.rombongan_belajar_id',
                    'pd.nama_rombel',
                    'rb.nama as rombel_nama',
                    'pdm.foto_path',
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
                ])
                ->paginate($perPage)
                ->withQueryString();

            // Lampirkan URL foto (foto_path bukan kolom publik; URL dibentuk dari accessor model)
            $pageIds = collect($items->items())->pluck('peserta_didik_id')->filter()->all();
            $metaMap = !empty($pageIds)
                ? PesertaDidikMeta::whereIn('peserta_didik_id', $pageIds)->get()->keyBy('peserta_didik_id')
                : collect();

            foreach ($items as $item) {
                $item->foto_url = $metaMap->get($item->peserta_didik_id)?->foto_url;
            }

            // Master List untuk Filter Dropdown
            $rombelList = \App\Support\RombelScope::query()
                ->whereIn('tingkat_pendidikan_id', KelasAkhirScope::TINGKAT)
                ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id')
                ->orderBy('tingkat_pendidikan_id')
                ->orderBy('nama')
                ->get();
        }

        return view('dashboard.kesiswaan.buku-induk.index', compact(
            'stats',
            'items',
            'q',
            'rombelId',
            'kelengkapan',
            'perPage',
            'sort',
            'sortDir',
            'rombelList',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete',
            'tahunAjaranTerpilih',
            'tahunAjaranAktif',
            'filterTahunList',
            'tahunLulus',
            'tahunLulusAktif',
            'tahunLulusPengaturan',
            'isAktif'
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

        $pd = $this->findPesertaDidik($id);

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
        $rombel = (object) [
            'rombel_nama' => $pd->nama_rombel
                ?: (DB::table('rombongan_belajar')->where('rombongan_belajar_id', $pd->rombongan_belajar_id ?? '')->value('nama') ?? '-'),
            'tingkat_pendidikan_id' => $pd->tingkat_pendidikan_id
                ?: DB::table('rombongan_belajar')
                ->where('rombongan_belajar_id', $pd->rombongan_belajar_id ?? '')
                ->value('tingkat_pendidikan_id'),
        ];

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

        $pd = $this->findPesertaDidik($id);

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
            'golongan_darah'           => 'nullable|string|max:5',
            'penyakit_pernah_diderita' => 'nullable|string|max:150',
            'kelainan_jasmani'         => 'nullable|string|max:100',
            'bahasa_sehari_hari'       => 'nullable|string|max:100',
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
            'golongan_darah'           => $validated['golongan_darah'] ?? null,
            'penyakit_pernah_diderita' => $validated['penyakit_pernah_diderita'] ?? null,
            'kelainan_jasmani'         => $validated['kelainan_jasmani'] ?? null,
            'bahasa_sehari_hari'       => $validated['bahasa_sehari_hari'] ?? null,
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
        if (Table::exists('peserta_didik')) {
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

        $pd = $this->findPesertaDidik($id);

        if (!$pd) {
            abort(404, 'Data peserta didik tidak ditemukan.');
        }

        $identitas = PesertaDidikIdentitas::getOrCreateFromPd($pd);
        $klaper = KesiswaanBukuKlaper::where('peserta_didik_id', $pd->peserta_didik_id)->first();
        $meta = PesertaDidikMeta::where('peserta_didik_id', $pd->peserta_didik_id)->first();
        $sekolah = Sekolah::first();
        $sekolahMeta = SekolahMeta::first();

        // Rombel Siswa (kolom denormalisasi pada peserta_didik)
        $rombel = (object) [
            'rombel_nama' => $pd->nama_rombel
                ?: (DB::table('rombongan_belajar')->where('rombongan_belajar_id', $pd->rombongan_belajar_id ?? '')->value('nama') ?? '-'),
        ];

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
     * Cetak Register Rekapitulasi Buku Induk Siswa per Rombel / Cohort
     */
    public function cetakRekap(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $rombelId = $request->input('rombel_id', '');

        $sekolah = Sekolah::first();
        $sekolahMeta = SekolahMeta::first();

        $tahunAjaranAktif     = BukuIndukNilaiService::tahunAjaranAktif();
        $tahunAjaranTerpilih  = (string) (BukuIndukNilaiService::pengaturan()->tahun_ajaran ?? '') ?: $tahunAjaranAktif;
        $tahunLulusAktif      = str_contains($tahunAjaranAktif, '/') ? explode('/', $tahunAjaranAktif)[1] : $tahunAjaranAktif;
        $tahunLulusPengaturan = str_contains($tahunAjaranTerpilih, '/') ? explode('/', $tahunAjaranTerpilih)[1] : $tahunAjaranTerpilih;

        $tahunLulus = $request->input('tahun_lulus') ?: $tahunLulusPengaturan;
        $isAktif = ((string) $tahunLulus === (string) $tahunLulusAktif);

        if (!$isAktif) {
            $query = DB::table('peserta_didik_tidak_aktif')
                ->where(function ($w) use ($tahunLulus) {
                    $w->where('tahun_lulus', (string) $tahunLulus)
                      ->orWhere('tahun_lulus', "{$tahunLulus}/" . ((int) $tahunLulus + 1))
                      ->orWhere('tahun_lulus', ((int) $tahunLulus - 1) . "/{$tahunLulus}")
                      ->orWhereYear('tanggal_keluar', (string) $tahunLulus);
                })
                ->where(function ($b) {
                    $b->where('status_keluar', 'Alumni')
                      ->orWhere('alasan_keluar', 'like', '%Lulus%')
                      ->orWhere('alasan_keluar', 'like', '%Tamat%');
                })
                ->when(!empty($rombelId), function ($q) use ($rombelId) {
                    $q->where(function ($w) use ($rombelId) {
                        $w->where('nama_rombel_terakhir', $rombelId)
                          ->orWhere('rombongan_belajar_id', $rombelId);
                    });
                })
                ->select([
                    'peserta_didik_id', 'nama', 'nisn', 'nipd', 'nik', 'jenis_kelamin',
                    'tempat_lahir', 'tanggal_lahir', 'nama_ayah', 'nama_ibu', 'alamat_jalan',
                    'nama_rombel_terakhir as rombel_nama',
                ])
                ->selectRaw("NULL as nomor_klaper, nipd as nomor_induk, ? as tahun_masuk, 'lulus' as status_klaper, NULL as sekolah_asal", [
                    (int) $tahunLulus > 2000 ? (int) $tahunLulus - 3 : null
                ]);

            $items = $query->orderBy('nama_rombel_terakhir', 'asc')->orderBy('nama', 'asc')->get();
            $rombel = !empty($rombelId) ? (object) ['nama' => $rombelId] : null;
        } else {
            $query = DB::table('peserta_didik as pd')
                ->leftJoin('peserta_didik_identitas as pdi', 'pd.peserta_didik_id', '=', 'pdi.peserta_didik_id')
                ->leftJoin('kesiswaan_buku_klaper as kbk', 'pd.peserta_didik_id', '=', 'kbk.peserta_didik_id')
                ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
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
                    'pd.nama_rombel as rombel_nama',
                    'kbk.nomor_klaper',
                    'kbk.nomor_induk',
                    'kbk.tahun_masuk',
                    'kbk.status_klaper',
                    'pdi.sekolah_asal',
                ]);

            // Hanya kelas akhir (tingkat XII)
            KelasAkhirScope::apply($query);

            // Hanya siswa aktif terakhir (bukan DO / mutasi keluar)
            $query->where(function ($w) {
                $w->whereNull('kbk.status_klaper')
                  ->orWhere('kbk.status_klaper', 'aktif');
            });

            if (!empty($rombelId)) {
                $query->where('pd.rombongan_belajar_id', $rombelId);
            }

            $items = $query->orderBy('rb.nama', 'asc')->orderBy('pd.nama', 'asc')->get();
            $rombel = !empty($rombelId) ? DB::table('rombongan_belajar')->where('rombongan_belajar_id', $rombelId)->first() : null;
        }

        $kepsek = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        $orientasi = 'landscape';
        $status = $isAktif ? 'aktif' : 'lulus';
        $tahunMasuk = (int) $tahunLulus > 2000 ? (int) $tahunLulus - 3 : null;

        return view('dashboard.kesiswaan.buku-induk.cetak-rekap', compact(
            'items',
            'sekolah',
            'sekolahMeta',
            'rombel',
            'tahunMasuk',
            'status',
            'tahunLulus',
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

    /** Cari data peserta didik aktif atau alumni, dinormalisasi ke stdClass aman. */
    private function findPesertaDidik($id): ?object
    {
        $pd = PesertaDidik::where('peserta_didik_id', $id)->first();
        if (!$pd && Table::exists('peserta_didik_tidak_aktif')) {
            $pd = DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->first();
        }

        if (!$pd) {
            return null;
        }

        $raw = is_object($pd) && method_exists($pd, 'getAttributes')
            ? $pd->getAttributes()
            : (array) $pd;

        $pd = (object) array_merge([
            'peserta_didik_id' => null,
            'nama_rombel' => null,
            'nama_rombel_terakhir' => null,
            'rombongan_belajar_id' => null,
            'tingkat_pendidikan_id' => null,
            'tingkat_pendidikan_terakhir' => null,
            'pekerjaan_ayah_id_str' => null,
            'pekerjaan_ibu_id_str' => null,
            'pekerjaan_wali_id_str' => null,
            'sekolah_asal' => null,
            'tanggal_masuk_sekolah' => null,
            'agama_id' => null,
            'agama_id_str' => null,
            'alamat_jalan' => null,
            'tinggi_badan' => null,
            'berat_badan' => null,
            'email' => null,
            'nomor_telepon_seluler' => null,
            'nama_ayah' => null,
            'nama_ibu' => null,
            'nama_wali' => null,
            'nik' => null,
            'nipd' => null,
            'nisn' => null,
            'nama' => null,
            'tempat_lahir' => null,
            'tanggal_lahir' => null,
            'jenis_kelamin' => null,
        ], $raw);

        if (empty($pd->nama_rombel) && !empty($pd->nama_rombel_terakhir)) {
            $pd->nama_rombel = $pd->nama_rombel_terakhir;
        }
        if (empty($pd->tingkat_pendidikan_id) && !empty($pd->tingkat_pendidikan_terakhir)) {
            $pd->tingkat_pendidikan_id = $pd->tingkat_pendidikan_terakhir;
        }
        foreach (['ayah', 'ibu', 'wali'] as $who) {
            $key = 'pekerjaan_' . $who . '_id_str';
            if (empty($pd->$key) && !empty($pd->{'pekerjaan_' . $who})) {
                $pd->$key = $pd->{'pekerjaan_' . $who};
            }
        }

        return $pd;
    }
}
