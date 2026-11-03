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
use App\Services\BukuIndukNilaiService;
use App\Services\SimpleXlsxExporter;
use App\Services\SimpleXlsxReader;
use App\Support\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Buku Induk — Tab "Import Nilai & Pengaturan" dan "Buku Induk" (kelas XII).
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

    /** TAB 2 — Import Nilai & Pengaturan (susun mata pelajaran) Buku Induk. */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $pengaturan = BukuIndukNilaiService::pengaturan();
        $mapel = BukuIndukNilaiService::mapelList();
        $referensi = BukuIndukNilaiService::referensiMapel();
        $form = BukuIndukNilaiService::pengaturanForm();

        $stats = [
            'total_mapel' => count($mapel['umum']) + count($mapel['kejuruan']),
            'mapel_umum' => count($mapel['umum']),
            'mapel_kejuruan' => count($mapel['kejuruan']),
            'total_referensi' => count($referensi['umum']) + count($referensi['kejuruan']),
        ];

        $can = $this->perms();

        return view('dashboard.kesiswaan.buku-induk.import-nilai', compact(
            'stats', 'pengaturan', 'mapel', 'referensi', 'form', 'can'
        ));
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

        $mapelList = BukuIndukNilaiService::mapelList();
        $kelompokMap = [];
        foreach (['umum', 'kejuruan'] as $grup) {
            foreach ($mapelList[$grup] as $nama) {
                $kelompokMap[$nama] = $grup;
            }
        }

        DB::transaction(function () use ($data, $id, $akademik, $kelompokMap) {
            foreach ($data as $mapel => $semesters) {
                $mapel = trim((string) $mapel);
                if ($mapel === '' || !is_array($semesters)) continue;
                foreach ($semesters as $sem => $val) {
                    $sem = (int) $sem;
                    if ($sem < 1 || $sem > 6) continue;
                    // Alumni hanya punya nilai kelas XII (semester 5–6).
                    if ($akademik === 'alumni' && $sem < 5) continue;

                    $val = is_string($val) ? str_replace(',', '.', trim($val)) : $val;
                    if ($val === '' || $val === null) {
                        DB::table('buku_induk_nilai')
                            ->where('peserta_didik_id', $id)
                            ->where('nama_mata_pelajaran', $mapel)
                            ->where('semester', $sem)
                            ->delete();
                        continue;
                    }

                    DB::table('buku_induk_nilai')->updateOrInsert(
                        ['peserta_didik_id' => $id, 'nama_mata_pelajaran' => $mapel, 'semester' => $sem],
                        ['nilai' => (float) $val, 'kelompok' => $kelompokMap[$mapel] ?? 'umum', 'tingkat' => (string) (($sem + 1) >> 1), 'updated_at' => now(), 'created_at' => now()]
                    );
                }
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
            'mapel_umum' => 'nullable|string',
            'mapel_kejuruan' => 'nullable|string',
        ]);

        $toJson = function ($val) {
            $arr = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $val)), fn ($v) => $v !== ''));
            return json_encode($arr);
        };

        $payload = [
            'tahun_ajaran' => $validated['tahun_ajaran'] ?? null,
            'nama_kepala_sekolah' => $validated['nama_kepala_sekolah'] ?? null,
            'nip_kepala_sekolah' => $validated['nip_kepala_sekolah'] ?? null,
            'nama_petugas' => $validated['nama_petugas'] ?? null,
            'nip_petugas' => $validated['nip_petugas'] ?? null,
            'mapel_umum' => $toJson($validated['mapel_umum'] ?? ''),
            'mapel_kejuruan' => $toJson($validated['mapel_kejuruan'] ?? ''),
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

        return view('dashboard.kesiswaan.buku-induk.cetak-buku-induk', compact(
            'pd', 'identitas', 'klaper', 'meta', 'sekolah', 'sekolahMeta', 'rombel', 'kepsek', 'matriks', 'pengaturan'
        ));
    }
}
