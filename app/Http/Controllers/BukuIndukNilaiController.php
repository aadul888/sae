<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;
use App\Models\KesiswaanBukuKlaper;
use App\Models\PesertaDidik;
use App\Models\PesertaDidikIdentitas;
use App\Models\PesertaDidikMeta;
use App\Models\RolePermission;
use App\Models\Sekolah;
use App\Models\SekolahMeta;
use App\Services\BukuIndukJurusanService;
use App\Services\BukuIndukNilaiService;
use App\Services\SimpleXlsxExporter;
use App\Services\SimpleXlsxReader;
use App\Support\Table;
use App\Support\KelasAkhirScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Buku Induk — Tab "Buku Induk", "Pengaturan", dan "Maping Mata Pelajaran".
 */
class BukuIndukNilaiController extends Controller
{
    use ChecksAuthenticatedSession;

    private function perms(): array
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $ok = fn (string $act) => RolePermission::canAccess($user ?: $role, 'menu_buku_induk', $act)
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', $act);

        return ['create' => $ok('create'), 'read' => $ok('read'), 'update' => $ok('update'), 'delete' => $ok('delete')];
    }

    /** TAB 2 — Pengaturan Buku Induk (tahun ajaran & penandatangan). */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $pengaturan = BukuIndukNilaiService::pengaturan();
        $form = BukuIndukNilaiService::pengaturanForm();

        $can = $this->perms();

        return view('dashboard.kesiswaan.buku-induk.import-nilai', compact('pengaturan', 'form', 'can')
            + ['tahunAjaranList' => BukuIndukNilaiService::tahunAjaranList()]);
    }

    /** JSON matriks nilai satu siswa untuk modal edit. */
    public function matriks(Request $request, $id): JsonResponse
    {
        if ($res = $this->checkAuth()) {
            return response()->json(['status' => 'error', 'message' => 'Sesi berakhir.'], 401);
        }

        $pd = BukuIndukNilaiService::findPesertaDidik($id);
        if (!$pd) {
            return response()->json(['status' => 'error', 'message' => 'Peserta didik tidak ditemukan.'], 404);
        }

        $akademik = $request->input('akademik') === 'alumni' ? 'alumni' : 'aktif';

        return response()->json([
            'status' => 'success',
            'data' => [
                'peserta_didik_id' => $pd->peserta_didik_id,
                'nama' => $pd->nama,
                'nisn' => $pd->nisn ?? null,
                'akademik' => $akademik,
                'matriks' => BukuIndukNilaiService::matriks($pd->peserta_didik_id, $akademik),
            ],
        ]);
    }

    /** Simpan nilai satu siswa (satu nilai per mapel per semester). */
    public function saveNilai(Request $request, $id): JsonResponse
    {
        if ($res = $this->checkAuth()) {
            return response()->json(['status' => 'error', 'message' => 'Sesi berakhir.'], 401);
        }

        if (!$this->perms()['update']) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        if (!BukuIndukNilaiService::findPesertaDidik($id)) {
            return response()->json(['status' => 'error', 'message' => 'Peserta didik tidak ditemukan.'], 404);
        }

        $akademik = $request->input('akademik') === 'alumni' ? 'alumni' : 'aktif';

        $data = $request->input('nilai', []);
        if (!is_array($data)) {
            return response()->json(['status' => 'error', 'message' => 'Format nilai tidak valid.'], 422);
        }

        // Batas & kelompok mapel mengikuti matriks siswa: menghormati pemetaan per
        // jurusan dan penguncian semester saat PKL (hanya mapel PKL yang boleh diisi).
        $kelompokMap = [];
        $bolehMap = [];
        foreach (BukuIndukNilaiService::matriks($id, $akademik) as $row) {
            $kelompokMap[$row['nama']] = $row['kelompok'];
            $bolehMap[$row['nama']] = $row['editable'] ?? [];
        }

        $salah = [];
        $rencana = [];

        // Validasi dulu (all-or-nothing): bila ada isian tidak sah, tidak ada yang disimpan.
        foreach ($data as $mapel => $semesters) {
            $mapel = trim((string) $mapel);
            if ($mapel === '' || !is_array($semesters) || !array_key_exists($mapel, $bolehMap)) continue;
            foreach ($semesters as $sem => $val) {
                $sem = (int) $sem;
                if ($sem < 1 || $sem > 6) continue;
                // Alumni hanya punya nilai kelas XII (semester 5–6).
                if ($akademik === 'alumni' && $sem < 5) continue;
                // Semester terkunci (mis. pelaksanaan PKL) atau mapel tidak diajarkan.
                if (!in_array($sem, $bolehMap[$mapel], true)) continue;

                $cek = BukuIndukNilaiService::nilaiSah($val);
                if (!$cek['ok']) {
                    $salah[] = "{$mapel} (Sem {$sem}): {$cek['pesan']}";
                    continue;
                }
                $rencana[] = [$mapel, $sem, $cek['nilai']];
            }
        }

        if ($salah) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nilai harus angka ' . BukuIndukNilaiService::rentangNilai() . '. Perbaiki: ' . implode('; ', array_slice($salah, 0, 5)),
            ], 422);
        }

        DB::transaction(function () use ($rencana, $id, $kelompokMap) {
            foreach ($rencana as [$mapel, $sem, $nilai]) {
                if ($nilai === null) {
                    DB::table('buku_induk_nilai')
                        ->where('peserta_didik_id', $id)
                        ->where('nama_mata_pelajaran', $mapel)
                        ->where('semester', $sem)
                        ->delete();
                    continue;
                }
                DB::table('buku_induk_nilai')->updateOrInsert(
                    ['peserta_didik_id' => $id, 'nama_mata_pelajaran' => $mapel, 'semester' => $sem],
                    ['nilai' => $nilai, 'kelompok' => $kelompokMap[$mapel] ?? 'umum', 'tingkat' => (string) (($sem + 1) >> 1), 'updated_at' => now(), 'created_at' => now()]
                );
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Nilai berhasil disimpan.']);
    }

    /** Unduh template Excel impor nilai. */
    public function downloadTemplate(): BinaryFileResponse
    {
        if ($res = $this->checkAuth()) return $res;

        $tempPath = tempnam(sys_get_temp_dir(), 'tpl_nilai_') . '.xlsx';

        $headers = ['NISN', 'Nama', 'Kelas', 'Nama Mata Pelajaran', 'Semester 1', 'Semester 2', 'Semester 3', 'Semester 4', 'Semester 5', 'Semester 6'];
        $sample = [
            ['0081234561', 'Ahmad Fauzi', 'XII TKJ 1', 'Pendidikan Pancasila', '85', '86', '84', '85', '88', '87'],
            ['0081234561', 'Ahmad Fauzi', 'XII TKJ 1', 'Bahasa Indonesia', '88', '87', '85', '86', '90', '89'],
            ['0081234562', 'Siti Nurhaliza', 'XII TKJ 2', 'Matematika', '', '', '', '', '90', '88'],
        ];

        SimpleXlsxExporter::export($tempPath, [], $headers, $sample, [
            'sheetName' => 'Nilai Buku Induk',
            'rawTable' => true,
            'headerBg' => '#4338CA',
        ]);

        return response()->download($tempPath, 'Template_Import_Nilai_Buku_Induk.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** Proses impor nilai dari Excel/CSV. */
    public function importNilai(Request $request): JsonResponse
    {
        if ($res = $this->checkAuth()) {
            return response()->json(['status' => 'error', 'message' => 'Sesi berakhir.'], 401);
        }

        if (!$this->perms()['create']) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'file_excel' => ['required', 'file', 'max:20480', 'mimes:xlsx,xls,csv,txt', 'extensions:xlsx,xls,csv,txt'],
        ]);

        try {
            $rows = SimpleXlsxReader::readFile($request->file('file_excel')->getRealPath());
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal membaca berkas: ' . $e->getMessage()], 422);
        }

        if (empty($rows)) {
            return response()->json(['status' => 'error', 'message' => 'Berkas kosong atau tidak terdeteksi.'], 422);
        }

        $result = BukuIndukNilaiService::importRows($rows);

        if ($result['imported'] === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada nilai yang berhasil diimpor. ' . implode(' ', $result['errors']),
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Import selesai: {$result['imported']} nilai tersimpan, {$result['skipped']} baris dilewati.",
            'errors' => $result['errors'],
        ]);
    }

    /** Simpan pengaturan pengelolaan buku induk (mapel + penandatangan). */
    public function savePengaturan(Request $request): JsonResponse
    {
        if ($res = $this->checkAuth()) {
            return response()->json(['status' => 'error', 'message' => 'Sesi berakhir.'], 401);
        }

        if (!$this->perms()['update']) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'tahun_ajaran' => 'nullable|string|max:20',
            'nama_kepala_sekolah' => 'nullable|string|max:150',
            'nip_kepala_sekolah' => 'nullable|string|max:30',
            'nama_petugas' => 'nullable|string|max:150',
            'nip_petugas' => 'nullable|string|max:30',
        ]);

        $payload = [
            'tahun_ajaran' => $validated['tahun_ajaran'] ?? null,
            'nama_kepala_sekolah' => $validated['nama_kepala_sekolah'] ?? null,
            'nip_kepala_sekolah' => $validated['nip_kepala_sekolah'] ?? null,
            'nama_petugas' => $validated['nama_petugas'] ?? null,
            'nip_petugas' => $validated['nip_petugas'] ?? null,
            'updated_at' => now(),
        ];

        $existing = DB::table('buku_induk_pengaturan')->orderBy('id')->first();
        if ($existing) {
            DB::table('buku_induk_pengaturan')->where('id', $existing->id)->update($payload);
        } else {
            $payload['created_at'] = now();
            DB::table('buku_induk_pengaturan')->insert($payload);
        }

        return response()->json(['status' => 'success', 'message' => 'Pengaturan buku induk berhasil disimpan.']);
    }

    /** TAB 3 — Maping Mata Pelajaran: pemetaan mapel PER JURUSAN & semester. */
    public function mapingMapel(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $jurusanList = BukuIndukJurusanService::jurusanList();
        $pilih = (string) ($request->query('jurusan') ?? '');
        if ($pilih !== '' && $pilih !== '_global' && !isset($jurusanList[$pilih])) {
            $pilih = '';
        }
        if ($pilih === '') {
            $pilih = $jurusanList ? (string) array_key_first($jurusanList) : '_global';
        }
        $kunci = $pilih === '_global' ? null : $pilih;

        // Daftar mapel konkret untuk input singkatan (semua semester, jurusan terpilih).
        $daftarMapel = [];
        foreach (range(1, 6) as $sem) {
            $grup = BukuIndukJurusanService::mapelSatuSemester($kunci, $sem);
            $daftarMapel = array_merge($daftarMapel, $grup['umum'], $grup['kejuruan']);
        }
        $daftarMapel = array_values(array_unique(array_filter($daftarMapel, fn ($v) => trim((string) $v) !== '')));

        return view('dashboard.kesiswaan.buku-induk.maping-mapel', [
            'perSemester' => BukuIndukJurusanService::mapelPerSemesterJurusan($kunci),
            'terpetakan' => $kunci
                ? BukuIndukJurusanService::mapelSemesterTerpetakanJurusan($kunci)
                : BukuIndukNilaiService::mapelSemesterTerpetakan(),
            'pklSemester' => BukuIndukNilaiService::pklSemester(),
            'referensi' => BukuIndukNilaiService::referensiMapel(),
            'jurusanList' => $jurusanList,
            'jurusanTerpilih' => $pilih,
            'jurusanMeta' => $kunci ? BukuIndukJurusanService::jurusan($kunci) : null,
            'singkatan' => BukuIndukNilaiService::singkatanEfektif($daftarMapel),
            'daftarMapel' => $daftarMapel,
            'can' => $this->perms(),
        ]);
    }

    /** Simpan pemetaan mapel per semester untuk jurusan terpilih (semua semester yang dikirim). */
    public function saveMapingMapel(Request $request): JsonResponse
    {
        if ($res = $this->checkAuth()) {
            return response()->json(['status' => 'error', 'message' => 'Sesi berakhir.'], 401);
        }

        if (!$this->perms()['update']) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $mapel = $request->input('mapel', []);
        if (!is_array($mapel)) {
            return response()->json(['status' => 'error', 'message' => 'Format pemetaan tidak valid.'], 422);
        }

        $kunci = trim((string) $request->input('jurusan', ''));
        $perJurusan = $kunci !== '' && $kunci !== '_global';

        $pecah = function ($val) {
            return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $val)), fn ($v) => $v !== ''));
        };

        $jumlah = 0;
        foreach ($mapel as $semester => $grup) {
            $semester = (int) $semester;
            if ($semester < 1 || $semester > 6 || !is_array($grup)) {
                continue;
            }
            // Semester pelaksanaan PKL terkunci: pemetaan mapelnya diabaikan (hanya PKL).
            if (BukuIndukNilaiService::semesterPkl($semester)) {
                continue;
            }
            if ($perJurusan) {
                BukuIndukJurusanService::saveMapelSemesterJurusan(
                    $kunci,
                    $semester,
                    $pecah($grup['umum'] ?? ''),
                    $pecah($grup['kejuruan'] ?? '')
                );
            } else {
                BukuIndukNilaiService::saveMapelSemester(
                    $semester,
                    $pecah($grup['umum'] ?? ''),
                    $pecah($grup['kejuruan'] ?? '')
                );
            }
            $jumlah++;
        }

        // Pelaksanaan PKL per semester (daftar semester, opsional).
        $pklSemester = $request->input('pkl_semester');
        $adaPkl = $request->has('pkl_ada');
        if ($adaPkl) {
            BukuIndukNilaiService::savePklSemester(is_array($pklSemester) ? $pklSemester : []);
        }

        // Singkatan mapel untuk judul kolom ringkas di tab Input Nilai (opsional).
        $singkatan = $request->input('singkatan');
        $adaSingkatan = is_array($singkatan);
        if ($adaSingkatan) {
            BukuIndukNilaiService::saveSingkatan($singkatan);
        }

        if ($jumlah === 0 && !$adaPkl && !$adaSingkatan) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada data yang dikirim.'], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Pemetaan mata pelajaran tersimpan untuk {$jumlah} semester" . ($perJurusan ? ' jurusan terpilih' : ' (default)') . ($adaPkl ? ' beserta pengaturan PKL' : '') . ($adaSingkatan ? ' beserta singkatan mapel' : '') . '.',
        ]);
    }

    /** TAB 4 — Input Nilai manual per kelas akhir (XII) & semester 5–6 (+ impor nilai per kelas). */
    public function inputNilai(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $cohort = BukuIndukJurusanService::resolveCohort($request->query('tahun_lulus'));
        $pilihanKelas = BukuIndukJurusanService::cohortKelasList($cohort['tahunLulus'], $cohort['isAktif']);

        // Kelas tetap kelas akhir (XII); semester boleh mundur 1–6 untuk mengisi rapor sebelumnya.
        $semesterList = range(1, 6);
        $semester = (int) ($request->query('semester') ?? 5);
        if (!in_array($semester, $semesterList, true)) {
            $semester = 5;
        }

        $kelasId = (string) ($request->query('kelas') ?? '');
        if ($kelasId === '' || !collect($pilihanKelas)->contains('id', $kelasId)) {
            $kelasId = $pilihanKelas ? $pilihanKelas[0]['id'] : '';
        }

        $kelas = $kelasId !== '' ? BukuIndukJurusanService::cohortKelas($kelasId, $cohort['tahunLulus'], $cohort['isAktif']) : null;
        $kunci = $kelas['kunci'] ?? null;

        $grup = BukuIndukJurusanService::mapelSatuSemester($kunci, $semester);
        $mapel = array_values(array_unique(array_merge($grup['umum'], $grup['kejuruan'])));
        $kelompok = [];
        foreach (['umum', 'kejuruan'] as $g) {
            foreach ($grup[$g] as $nama) {
                $kelompok[$nama] = $g;
            }
        }

        $q = trim((string) $request->query('q', ''));
        $perPage = (int) ($request->query('perPage') ?? 25);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) $perPage = 25;
        $sort = (string) ($request->query('sort') ?? 'nama');
        $sortDir = strtolower((string) ($request->query('sort_dir') ?? 'asc')) === 'desc' ? 'desc' : 'asc';
        $sortCols = [
            'nipd' => 'pd.nipd', 'nomor_induk' => 'pd.nipd', 'nisn' => 'pd.nisn',
            'nama' => 'pd.nama',
        ];
        $orderByCol = $sortCols[$sort] ?? 'pd.nama';

        if ($kelasId !== '') {
            $querySiswa = BukuIndukJurusanService::cohortSiswaKelasQuery($kelasId, $cohort['tahunLulus'], $cohort['isAktif']);

            if ($q !== '') {
                $querySiswa->where(fn ($sub) => $sub->where('pd.nama', 'like', "%{$q}%")
                    ->orWhere('pd.nisn', 'like', "%{$q}%")
                    ->orWhere('pd.nipd', 'like', "%{$q}%"));
            }

            $siswa = $querySiswa
                ->orderBy($orderByCol, $sortDir)
                ->paginate($perPage)
                ->withQueryString();
        } else {
            $siswa = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        $tersimpan = [];
        if ($siswa->count() > 0 && $mapel) {
            $ids = $siswa->pluck('peserta_didik_id')->all();
            $rows = DB::table('buku_induk_nilai')
                ->whereIn('peserta_didik_id', $ids)
                ->where('semester', $semester)
                ->whereIn('nama_mata_pelajaran', $mapel)
                ->get();
            foreach ($rows as $r) {
                $tersimpan[$r->peserta_didik_id][$r->nama_mata_pelajaran] = $r->nilai;
            }
        }

        return view('dashboard.kesiswaan.buku-induk.input-nilai', [
            'kelasList' => $pilihanKelas,
            'kelasId' => $kelasId,
            'kelas' => $kelas,
            'semester' => $semester,
            'semesterList' => $semesterList,
            'mapel' => $mapel,
            'kelompok' => $kelompok,
            'siswa' => $siswa,
            'tersimpan' => $tersimpan,
            'pklSemester' => BukuIndukNilaiService::pklSemester(),
            'singkatan' => BukuIndukNilaiService::singkatanEfektif($mapel),
            'perPage' => $perPage,
            'q' => $q,
            'sort' => $sort,
            'sortDir' => $sortDir,
            'filterTahunList' => $cohort['filterTahunList'],
            'tahunLulus' => $cohort['tahunLulus'],
            'isAktif' => $cohort['isAktif'],
            'can' => $this->perms(),
        ]);
    }

    /** Simpan nilai satu kelas untuk satu semester (manual, dari tabel input). */
    public function saveInputNilai(Request $request): JsonResponse
    {
        if ($res = $this->checkAuth()) {
            return response()->json(['status' => 'error', 'message' => 'Sesi berakhir.'], 401);
        }

        if (!$this->perms()['update']) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $kelasId = trim((string) $request->input('kelas', ''));
        $semester = (int) $request->input('semester', 0);
        $kelas = $kelasId !== '' ? BukuIndukJurusanService::kelas($kelasId) : null;
        if (!$kelas || !in_array($kelas['tingkat'], KelasAkhirScope::TINGKAT, true) || $semester < 1 || $semester > 6) {
            return response()->json(['status' => 'error', 'message' => 'Kelas/semester tidak valid. Input Nilai hanya untuk kelas akhir (XII).'], 422);
        }

        $data = $request->input('nilai', []);
        if (!is_array($data)) {
            return response()->json(['status' => 'error', 'message' => 'Format nilai tidak valid.'], 422);
        }

        // Batas mapel mengikuti pemetaan jurusan + kunci semester PKL.
        $grup = BukuIndukJurusanService::mapelSatuSemester($kelas['kunci'], $semester);
        $kelompokMap = [];
        foreach (['umum', 'kejuruan'] as $g) {
            foreach ($grup[$g] as $nama) {
                $kelompokMap[$nama] = $g;
            }
        }

        // Trust boundary: hanya siswa kelas terpilih yang diproses.
        $siswaKelas = BukuIndukJurusanService::siswaKelas($kelasId);
        $set = [];
        $namaSiswa = [];
        foreach ($siswaKelas as $s) {
            $set[$s->peserta_didik_id] = true;
            $namaSiswa[$s->peserta_didik_id] = (string) ($s->nama ?? '');
        }
        $saved = 0;
        $salah = [];
        $rencana = [];

        // Validasi dulu (all-or-nothing): bila ada isian tidak sah, tidak ada yang disimpan.
        foreach ($data as $pdId => $mapels) {
            if (!isset($set[$pdId]) || !is_array($mapels)) {
                continue;
            }
            foreach ($mapels as $nama => $val) {
                $nama = trim((string) $nama);
                if (!array_key_exists($nama, $kelompokMap)) {
                    continue;
                }
                $cek = BukuIndukNilaiService::nilaiSah($val);
                if (!$cek['ok']) {
                    $salah[] = ($namaSiswa[$pdId] ?? $pdId) . " — {$nama}: {$cek['pesan']}";
                    continue;
                }
                $rencana[] = [$pdId, $nama, $cek['nilai']];
            }
        }

        if ($salah) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nilai harus angka ' . BukuIndukNilaiService::rentangNilai() . '. Perbaiki ' . count($salah) . ' isian, mis. ' . implode('; ', array_slice($salah, 0, 3)),
            ], 422);
        }

        DB::transaction(function () use ($rencana, $semester, $kelompokMap, &$saved) {
            foreach ($rencana as [$pdId, $nama, $nilai]) {
                if ($nilai === null) {
                    DB::table('buku_induk_nilai')
                        ->where('peserta_didik_id', $pdId)
                        ->where('nama_mata_pelajaran', $nama)
                        ->where('semester', $semester)
                        ->delete();
                    continue;
                }
                DB::table('buku_induk_nilai')->updateOrInsert(
                    ['peserta_didik_id' => $pdId, 'nama_mata_pelajaran' => $nama, 'semester' => $semester],
                    ['nilai' => $nilai, 'kelompok' => $kelompokMap[$nama], 'tingkat' => (string) (($semester + 1) >> 1), 'updated_at' => now(), 'created_at' => now()]
                );
                $saved++;
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => "Nilai {$kelas['nama']} semester {$semester} tersimpan: {$saved} isian.",
        ]);
    }

    /** Unduh template Excel impor NILAI satu semester untuk satu kelas (opsional: per jurusan). */
    public function downloadTemplateNilai(Request $request): BinaryFileResponse
    {
        if ($res = $this->checkAuth()) return $res;

        $semester = (int) $request->query('semester', 1);
        if ($semester < 1 || $semester > 6) {
            $semester = 1;
        }

        $cohort = BukuIndukJurusanService::resolveCohort($request->query('tahun_lulus'));
        $kelasId = trim((string) $request->query('kelas', ''));
        $kelas = $kelasId !== '' ? BukuIndukJurusanService::cohortKelas($kelasId, $cohort['tahunLulus'], $cohort['isAktif']) : null;

        $kunci = $kelas['kunci'] ?? trim((string) $request->query('jurusan', ''));
        if (!$kelas && ($kunci === '' || $kunci === '_global' || !BukuIndukJurusanService::jurusan($kunci))) {
            $kunci = (string) (array_key_first(BukuIndukJurusanService::jurusanList()) ?? '');
        }

        $grup = BukuIndukJurusanService::mapelSatuSemester($kunci ?: null, $semester);
        $mapel = array_values(array_unique(array_merge($grup['umum'], $grup['kejuruan'])));
        $kolom = $mapel ?: ['Nilai'];
        $tanpaNilai = array_fill(0, count($kolom), '');

        $headers = array_merge(['No', 'NISN', 'Nama', 'Kelas'], $kolom);
        $sample = [];
        $no = 1;
        $daftarSiswa = $kelas ? BukuIndukJurusanService::cohortSiswaKelas($kelasId, $cohort['tahunLulus'], $cohort['isAktif']) : BukuIndukJurusanService::siswaJurusan((string) $kunci);
        foreach ($daftarSiswa as $s) {
            $sample[] = array_merge([(string) $no++, (string) ($s->nisn ?? ''), (string) $s->nama, (string) ($s->nama_rombel ?? '')], $tanpaNilai);
        }
        if (!$sample) {
            $sample[] = array_merge(['1', '0081234561', 'Contoh Siswa', 'XII'], $tanpaNilai);
        }

        if ($kelas) {
            $label = $kelas['nama'];
        } else {
            $jur = $kunci ? BukuIndukJurusanService::jurusan($kunci) : null;
            $label = $jur ? BukuIndukJurusanService::label($jur) : 'Semua Jurusan';
        }
        $labelBersih = preg_replace('/[^A-Za-z0-9]+/', '_', $label);

        $tempPath = tempnam(sys_get_temp_dir(), 'tpl_nilai_jur_') . '.xlsx';
        SimpleXlsxExporter::export($tempPath, [], $headers, $sample, [
            'sheetName' => substr($labelBersih, 0, 20) . ' S' . $semester,
            'rawTable' => true,
            'headerBg' => '#4338CA',
        ]);

        return response()->download($tempPath, "Template_Nilai_{$labelBersih}_Semester_{$semester}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** Impor NILAI satu semester untuk satu kelas (wajib pilih kelas; NISN harus milik kelas itu). */
    public function importNilaiKelas(Request $request): JsonResponse
    {
        if ($res = $this->checkAuth()) {
            return response()->json(['status' => 'error', 'message' => 'Sesi berakhir.'], 401);
        }

        if (!$this->perms()['create']) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'kelas' => ['required', 'string', 'max:60'],
            'semester' => ['required', 'integer', 'between:1,6'],
            'tahun_lulus' => ['nullable', 'string', 'max:20'],
            'file_excel' => ['required', 'file', 'max:20480', 'mimes:xlsx,xls,csv,txt', 'extensions:xlsx,xls,csv,txt'],
        ]);

        $cohort = BukuIndukJurusanService::resolveCohort($validated['tahun_lulus'] ?? $request->input('tahun_lulus'));
        $kelas = BukuIndukJurusanService::cohortKelas(trim((string) $validated['kelas']), $cohort['tahunLulus'], $cohort['isAktif']);
        if (!$kelas) {
            return response()->json(['status' => 'error', 'message' => 'Pilih kelas terlebih dahulu sebelum mengunggah.'], 422);
        }
        if (!in_array($kelas['tingkat'], KelasAkhirScope::TINGKAT, true)) {
            return response()->json(['status' => 'error', 'message' => 'Input Nilai hanya untuk kelas akhir (XII).'], 422);
        }

        try {
            $rows = SimpleXlsxReader::readFile($request->file('file_excel')->getRealPath());
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal membaca berkas: ' . $e->getMessage()], 422);
        }

        if (empty($rows)) {
            return response()->json(['status' => 'error', 'message' => 'Berkas kosong atau tidak terdeteksi.'], 422);
        }

        // ponytail: importer mencocokkan kolom mapel per nama (kolom asing diabaikan),
        // siap menerima ekspor e-Rapor; tambahkan pemetaan kolom eksplisit bila formatnya pasti.
        $result = BukuIndukJurusanService::importNilaiRows($rows, (string) $kelas['kunci'], (int) $validated['semester'], $kelas['id']);

        if ($result['imported'] === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada nilai yang berhasil diimpor. ' . implode(' ', $result['errors']),
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Import {$kelas['nama']} selesai: {$result['imported']} nilai tersimpan (Semester {$validated['semester']}), {$result['skipped']} baris dilewati.",
            'errors' => $result['errors'],
        ]);
    }

    /** Cetak lembar Buku Induk resmi format A3 landscape (kelas XII). */
    public function cetakBukuInduk(Request $request, $id)
    {
        if ($res = $this->checkAuth()) return $res;

        $pd = BukuIndukNilaiService::findPesertaDidik($id);
        if (!$pd) {
            abort(404, 'Data peserta didik tidak ditemukan.');
        }

        // Alumni = tidak ada di tabel aktif, berasal dari arsip peserta_didik_tidak_aktif.
        $akademik = PesertaDidik::where('peserta_didik_id', $id)->exists() ? 'aktif' : 'alumni';

        // Lengkapi kolom yang tidak ada di arsip alumni (mis. *_id_str) agar view tidak error.
        $pd = (object) array_merge([
            'pekerjaan_ayah_id_str' => null,
            'pekerjaan_ibu_id_str' => null,
            'pekerjaan_wali_id_str' => null,
            'nama_rombel' => null,
            'rombongan_belajar_id' => null,
            'sekolah_asal' => null,
            'tanggal_masuk_sekolah' => null,
            'agama_id_str' => null,
            'alamat_jalan' => null,
        ], (array) $pd);

        // Arsip alumni menyimpan pekerjaan sebagai teks (pekerjaan_ayah/ibu/wali), bukan *_id_str.
        foreach (['ayah', 'ibu', 'wali'] as $who) {
            $key = 'pekerjaan_' . $who . '_id_str';
            if (empty($pd->$key) && !empty($pd->{'pekerjaan_' . $who})) {
                $pd->$key = $pd->{'pekerjaan_' . $who};
            }
        }

        $identitas = PesertaDidikIdentitas::getOrCreateFromPd($pd);
        $klaper = KesiswaanBukuKlaper::where('peserta_didik_id', $pd->peserta_didik_id)->first();
        $meta = PesertaDidikMeta::where('peserta_didik_id', $pd->peserta_didik_id)->first();
        $sekolah = Sekolah::first();
        $sekolahMeta = SekolahMeta::first();

        $namaRombel = $pd->nama_rombel ?? ($pd->nama_rombel_terakhir ?? null);
        $rombel = (object) [
            'rombel_nama' => $namaRombel
                ?: (DB::table('rombongan_belajar')->where('rombongan_belajar_id', $pd->rombongan_belajar_id ?? '')->value('nama') ?? '-'),
        ];

        $kepsek = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        $matriks = BukuIndukNilaiService::matriks($pd->peserta_didik_id, $akademik);
        // Gabungkan pengaturan tersimpan dengan fallback data sekolah/GTK (tahun ajaran, kepsek, petugas).
        $pengaturan = (object) array_merge(
            (array) BukuIndukNilaiService::pengaturan(),
            BukuIndukNilaiService::pengaturanForm()
        );

        $pkl = DB::table('buku_induk_pkl')->where('peserta_didik_id', $pd->peserta_didik_id)->first();

        return view('dashboard.kesiswaan.buku-induk.cetak-buku-induk', compact(
            'pd', 'identitas', 'klaper', 'meta', 'sekolah', 'sekolahMeta', 'rombel', 'kepsek', 'matriks', 'pengaturan', 'pkl'
        ));
    }

    /** Tab 5 — Input Data PKL (Nama DU/DI & Alamat DU/DI) untuk seluruh siswa aktif / per rombel. */
    public function inputPkl(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $cohort = BukuIndukJurusanService::resolveCohort($request->query('tahun_lulus'));
        $rombelId = trim((string) $request->query('rombel_id', ''));
        $q = trim((string) $request->query('q', ''));
        $perPage = (int) ($request->query('perPage') ?? 25);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) $perPage = 25;
        $sort = (string) ($request->query('sort') ?? 'nama');
        $sortDir = strtolower((string) ($request->query('sort_dir') ?? 'asc')) === 'desc' ? 'desc' : 'asc';
        $sortCols = [
            'nipd' => 'pd.nipd', 'nomor_induk' => 'pd.nipd', 'nisn' => 'pd.nisn',
            'nama' => 'pd.nama', 'rombel_nama' => $cohort['isAktif'] ? 'rb.nama' : 'pd.nama_rombel_terakhir',
            'jenis_kelamin' => 'pd.jenis_kelamin', 'nama_dudi' => 'pkl.nama_dudi', 'alamat_dudi' => 'pkl.alamat_dudi',
        ];
        $orderByCol = $sortCols[$sort] ?? 'pd.nama';

        $rombelList = BukuIndukJurusanService::cohortRombelList($cohort['tahunLulus'], $cohort['isAktif']);

        $query = BukuIndukJurusanService::cohortSiswaPklQuery($rombelId !== '' ? $rombelId : null, $cohort['tahunLulus'], $cohort['isAktif']);

        if ($q !== '') {
            $query->where(fn ($sub) => $sub->where('pd.nama', 'like', "%{$q}%")
                ->orWhere('pd.nisn', 'like', "%{$q}%")->orWhere('pd.nipd', 'like', "%{$q}%")
                ->orWhere('pkl.nama_dudi', 'like', "%{$q}%")->orWhere('pkl.alamat_dudi', 'like', "%{$q}%"));
        }

        $totalSiswa = (clone $query)->count();
        $terisi = (clone $query)->whereNotNull('pkl.nama_dudi')->where('pkl.nama_dudi', '!=', '')->count();

        $siswa = $query->orderBy($orderByCol, $sortDir)->paginate($perPage)->withQueryString();

        return view('dashboard.kesiswaan.buku-induk.input-pkl', [
            'rombelList' => $rombelList, 'rombelId' => $rombelId, 'q' => $q,
            'siswa' => $siswa, 'totalSiswa' => $totalSiswa, 'terisi' => $terisi,
            'perPage' => $perPage, 'sort' => $sort, 'sortDir' => $sortDir,
            'filterTahunList' => $cohort['filterTahunList'],
            'tahunLulus' => $cohort['tahunLulus'],
            'isAktif' => $cohort['isAktif'],
            'can' => $this->perms(),
        ]);
    }

    /** Simpan massal data DU/DI PKL dari tabel input. */
    public function saveInputPkl(Request $request): JsonResponse
    {
        if ($res = $this->checkAuth()) return response()->json(['status' => 'error', 'message' => 'Sesi berakhir.'], 401);
        if (!$this->perms()['update']) return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);

        $data = $request->input('pkl', []);
        if (!is_array($data)) return response()->json(['status' => 'error', 'message' => 'Format data PKL tidak valid.'], 422);

        $now = now();
        $disimpan = 0;
        foreach ($data as $pdId => $item) {
            $pdId = (string) $pdId;
            if (!is_array($item)) continue;
            $namaDudi = trim((string) ($item['nama_dudi'] ?? ''));
            $alamatDudi = trim((string) ($item['alamat_dudi'] ?? ''));

            DB::table('buku_induk_pkl')->updateOrInsert(
                ['peserta_didik_id' => $pdId],
                ['nama_dudi' => $namaDudi !== '' ? $namaDudi : null, 'alamat_dudi' => $alamatDudi !== '' ? $alamatDudi : null, 'updated_at' => $now, 'created_at' => $now]
            );
            $disimpan++;
        }

        return response()->json(['status' => 'success', 'message' => "Data PKL untuk {$disimpan} siswa berhasil disimpan."]);
    }

    /** Simpan pembaruan PKL satu baris siswa (inline update). */
    public function saveRowPkl(Request $request): JsonResponse
    {
        if ($res = $this->checkAuth()) return response()->json(['status' => 'error', 'message' => 'Sesi berakhir.'], 401);
        if (!$this->perms()['update']) return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);

        $pdId = trim((string) $request->input('peserta_didik_id', ''));
        if ($pdId === '' || !BukuIndukNilaiService::findPesertaDidik($pdId)) {
            return response()->json(['status' => 'error', 'message' => 'Peserta didik tidak ditemukan.'], 404);
        }

        $namaDudi = trim((string) $request->input('nama_dudi', ''));
        $alamatDudi = trim((string) $request->input('alamat_dudi', ''));
        $now = now();
        DB::table('buku_induk_pkl')->updateOrInsert(
            ['peserta_didik_id' => $pdId],
            ['nama_dudi' => $namaDudi !== '' ? $namaDudi : null, 'alamat_dudi' => $alamatDudi !== '' ? $alamatDudi : null, 'updated_at' => $now, 'created_at' => $now]
        );

        return response()->json(['status' => 'success', 'message' => 'Data PKL siswa berhasil diperbarui.']);
    }

    /** Unduh template Excel 3 kolom (nisn, nama_dudi, alamat_dudi) per kelas atau semua rombel. */
    public function downloadTemplatePkl(Request $request): BinaryFileResponse
    {
        if ($res = $this->checkAuth()) return $res;

        $cohort = BukuIndukJurusanService::resolveCohort($request->query('tahun_lulus'));
        $rombelId = trim((string) $request->query('rombel_id', ''));
        $query = BukuIndukJurusanService::cohortSiswaPklQuery($rombelId !== '' ? $rombelId : null, $cohort['tahunLulus'], $cohort['isAktif']);

        $labelRombel = 'Semua Kelas (XII)';
        $namaRombel = 'Semua_Kelas';
        if ($rombelId !== '') {
            $rombelObj = BukuIndukJurusanService::cohortRombelList($cohort['tahunLulus'], $cohort['isAktif'])->firstWhere('rombongan_belajar_id', $rombelId);
            $namaRombel = $rombelObj ? preg_replace('/[^A-Za-z0-9]+/', '_', (string) $rombelObj->nama) : preg_replace('/[^A-Za-z0-9]+/', '_', $rombelId);
            $labelRombel = $rombelObj ? (string) $rombelObj->nama : $rombelId;
        }

        $daftar = $query->orderBy('pd.nama')->get();
        $headers = ['No', 'NISN', 'Nama Siswa', 'Kelas', 'Nama DU/DI', 'Alamat DU/DI'];
        $sample = [];
        $no = 1;
        foreach ($daftar as $d) {
            $sample[] = [
                (string) $no++,
                (string) ($d->nisn ?? ''),
                (string) ($d->nama ?? ''),
                (string) ($d->rombel_nama ?? ($d->nama_rombel ?? '')),
                (string) ($d->nama_dudi ?? ''),
                (string) ($d->alamat_dudi ?? ''),
            ];
        }
        if (empty($sample)) {
            $sample[] = ['1', '0081234567', 'Contoh Siswa', 'XII RPL 1', 'PT Industri Maju Bersama', 'Jl. Industri No. 12, Kawasan Industri'];
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'tpl_pkl_') . '.xlsx';
        SimpleXlsxExporter::export($tempPath, [], $headers, $sample, [
            'sheetName' => 'Data PKL',
            'rawTable'  => true,
            'headerBg'  => '#0D9488',
        ]);

        return response()->download($tempPath, "Template_PKL_{$namaRombel}_Tahun_{$cohort['tahunLulus']}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** Impor berkas Excel/CSV template 3 kolom (nisn, nama_dudi, alamat_dudi). */
    public function importPkl(Request $request): JsonResponse
    {
        if ($res = $this->checkAuth()) return response()->json(['status' => 'error', 'message' => 'Sesi berakhir.'], 401);
        if (!$this->perms()['create']) return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);

        $request->validate(['file_excel' => ['required', 'file', 'max:20480', 'mimes:xlsx,xls,csv,txt', 'extensions:xlsx,xls,csv,txt']]);

        try {
            $rows = SimpleXlsxReader::readFile($request->file('file_excel')->getRealPath());
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal membaca berkas: ' . $e->getMessage()], 422);
        }

        if (empty($rows)) return response()->json(['status' => 'error', 'message' => 'Berkas kosong atau tidak ada data.'], 422);

        $nisnMap = DB::table('peserta_didik')->whereNotNull('nisn')->where('nisn', '!=', '')->pluck('peserta_didik_id', 'nisn');
        if (Table::exists('peserta_didik_tidak_aktif')) {
            $nisnMap = $nisnMap->merge(
                DB::table('peserta_didik_tidak_aktif')->whereNotNull('nisn')->where('nisn', '!=', '')->pluck('peserta_didik_id', 'nisn')
            );
        }
        $nisnMap = $nisnMap->all();
        $now = now(); $imported = 0; $skipped = 0;

        foreach ($rows as $row) {
            $nisn = trim((string) ($row['nisn'] ?? $row['__col_1'] ?? $row['__col_0'] ?? ''));
            $nisn = preg_replace('/[^0-9]/', '', $nisn);
            if ($nisn === '' || !isset($nisnMap[$nisn])) { $skipped++; continue; }

            $pdId = $nisnMap[$nisn];
            $namaDudi = trim((string) ($row['nama_dudi'] ?? $row['dudi'] ?? $row['nama_mitra'] ?? $row['__col_4'] ?? $row['__col_1'] ?? ''));
            $alamatDudi = trim((string) ($row['alamat_dudi'] ?? $row['alamat'] ?? $row['__col_5'] ?? $row['__col_2'] ?? ''));

            DB::table('buku_induk_pkl')->updateOrInsert(
                ['peserta_didik_id' => $pdId],
                ['nama_dudi' => $namaDudi !== '' ? $namaDudi : null, 'alamat_dudi' => $alamatDudi !== '' ? $alamatDudi : null, 'updated_at' => $now, 'created_at' => $now]
            );
            $imported++;
        }

        return response()->json(['status' => 'success', 'message' => "Impor data PKL berhasil: {$imported} siswa diperbarui, {$skipped} baris dilewati (NISN tidak ditemukan)."]);
    }
}
