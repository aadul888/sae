<?php

namespace App\Http\Controllers;

use App\Models\AdminAktivitas;
use App\Models\PesertaDidik;
use App\Models\PesertaDidikBerkas;
use App\Models\PesertaDidikMeta;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BerkasPesertaDidikController extends Controller
{
    private function checkAuth()
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }
        return null;
    }

    private function resolveStudentContext(Request $request): array
    {
        $user = session('user');
        $userRole = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $userId = is_array($user) ? ($user['pengguna_id'] ?? ($user['id'] ?? null)) : ($user->pengguna_id ?? ($user->id ?? null));
        $userName = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Pengguna')) : ($user->nama ?? ($user->name ?? 'Pengguna'));

        $pdId = null;
        $allPdList = collect();

        if ($userRole === 'peserta_didik') {
            $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
            if (!$pdId && !empty($user['username'])) {
                $pdId = DB::table('peserta_didik')
                    ->where('nisn', $user['username'])
                    ->orWhere('nik', $user['username'])
                    ->value('peserta_didik_id');
            }
        } elseif ($userRole === 'orang_tua') {
            $pdId = session('parent_active_student_id') ?? (is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null));
            if (!$pdId && !empty($user['username'])) {
                $pdId = DB::table('peserta_didik')
                    ->where('nisn', $user['username'])
                    ->orWhere('nik', $user['username'])
                    ->value('peserta_didik_id');
            }
        } else {
            // Admin, Guru, Tendik dapat melihat daftar dan memilih siswa
            if (Schema::hasTable('peserta_didik')) {
                $allPdList = DB::table('peserta_didik')
                    ->select('peserta_didik_id', 'nama', 'nisn', 'nipd', 'nama_rombel')
                    ->orderBy('nama')
                    ->limit(200)
                    ->get();
            }

            $reqPdId = $request->get('peserta_didik_id');
            if ($reqPdId) {
                $pdId = $reqPdId;
            } elseif ($allPdList->isNotEmpty()) {
                $pdId = $allPdList->first()->peserta_didik_id;
            }
        }

        $pd = null;
        if ($pdId && Schema::hasTable('peserta_didik')) {
            $pd = PesertaDidik::where('peserta_didik_id', $pdId)->first();
        }

        return [
            'userRole' => $userRole,
            'userName' => $userName,
            'pd' => $pd,
            'pdId' => $pdId,
            'allPdList' => $allPdList,
        ];
    }

    /**
     * Tampilan Modul Validasi Berkas Peserta Didik
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $ctx = $this->resolveStudentContext($request);
        $userRole = $ctx['userRole'];
        $userName = $ctx['userName'];
        $pd = $ctx['pd'];
        $allPdList = $ctx['allPdList'];

        if (!$pd) {
            return view('dashboard.berkas-peserta-didik', [
                'pd' => null,
                'userRole' => $userRole,
                'userName' => $userName,
                'allPdList' => $allPdList,
                'slots' => [],
                'berkasMap' => collect(),
                'stats' => [
                    'total' => 0,
                    'valid' => 0,
                    'tidak_valid' => 0,
                    'menunggu' => 0,
                    'belum_unggah' => 0,
                ],
            ])->with('error', 'Data profil peserta didik tidak ditemukan.');
        }

        // Ambil foto profil siswa
        $meta = PesertaDidikMeta::where('peserta_didik_id', $pd->peserta_didik_id)->first();
        $studentPhoto = $meta?->foto_url ?? null;

        // Ambil seluruh slot berkas baku
        $slots = PesertaDidikBerkas::getJenisBerkasOptions();

        // Ambil data berkas yang sudah diunggah siswa
        $berkasList = PesertaDidikBerkas::where('peserta_didik_id', $pd->peserta_didik_id)->get();
        $berkasMap = $berkasList->keyBy('jenis_berkas');

        // Hitung statistik
        $totalSlots = count($slots);
        $validCount = $berkasList->where('status', 'valid')->count();
        $tidakValidCount = $berkasList->where('status', 'tidak_valid')->count();
        $menungguCount = $berkasList->where('status', 'menunggu')->count();
        $uploadedCount = $berkasList->count();
        $belumUnggahCount = max(0, $totalSlots - $uploadedCount);

        $stats = [
            'total' => $totalSlots,
            'valid' => $validCount,
            'tidak_valid' => $tidakValidCount,
            'menunggu' => $menungguCount,
            'belum_unggah' => $belumUnggahCount,
        ];

        return view('dashboard.berkas-peserta-didik', compact(
            'pd',
            'userRole',
            'userName',
            'allPdList',
            'studentPhoto',
            'slots',
            'berkasMap',
            'stats'
        ));
    }

    /**
     * Upload File Berkas Siswa (HANYA 1 JENIS FILE: PDF)
     */
    public function upload(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $ctx = $this->resolveStudentContext($request);
        $pd = $ctx['pd'];
        $userName = $ctx['userName'];
        $userRole = $ctx['userRole'];

        if (!$pd) {
            return back()->with('error', 'Peserta didik tidak ditemukan.');
        }

        $request->validate([
            'jenis_berkas' => 'required|string|in:akta_kelahiran,kartu_keluarga,ijazah_smp,ktp_orang_tua,kip_pip,lainnya',
            'file_berkas' => 'required|file|mimes:pdf|max:5120', // Maksimal 5MB, hanya PDF
        ], [
            'file_berkas.required' => 'Silakan pilih berkas PDF yang ingin diunggah.',
            'file_berkas.mimes' => 'Format file ditolak! Sistem hanya menerima file PDF (.pdf).',
            'file_berkas.max' => 'Ukuran file PDF terlalu besar! Maksimal ukuran file adalah 5 MB.',
        ]);

        $file = $request->file('file_berkas');

        // Validasi ekstra ketat: pastikan ekstensi & MIME type benar-benar PDF
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getClientMimeType();

        if ($extension !== 'pdf' || !str_contains($mimeType, 'pdf')) {
            return back()->with('error', 'File yang diunggah bukan file PDF yang valid.');
        }

        $jenisBerkas = $request->input('jenis_berkas');
        $slots = PesertaDidikBerkas::getJenisBerkasOptions();
        $namaBerkas = $slots[$jenisBerkas]['label'] ?? 'Dokumen PDF Siswa';

        // Cek jika berkas lama ada di database dan storage, hapus file lama untuk menghemat kapasitas
        $existing = PesertaDidikBerkas::where('peserta_didik_id', $pd->peserta_didik_id)
            ->where('jenis_berkas', $jenisBerkas)
            ->first();

        if ($existing && !empty($existing->file_path)) {
            Storage::disk('public')->delete($existing->file_path);
        }

        // Simpan file ke direktori khusus per siswa di storage/app/public/berkas_siswa/{pd_id}
        $fileName = $jenisBerkas . '_' . time() . '.pdf';
        $directory = 'berkas_siswa/' . $pd->peserta_didik_id;
        $savedPath = $file->storeAs($directory, $fileName, 'public');

        // Simpan atau perbarui record di tabel `peserta_didik_berkas`
        // Status di-reset ke 'menunggu', catatan penolakan dibersihkan
        $berkas = PesertaDidikBerkas::updateOrCreate(
            [
                'peserta_didik_id' => $pd->peserta_didik_id,
                'jenis_berkas' => $jenisBerkas,
            ],
            [
                'nama_berkas' => $namaBerkas,
                'file_path' => $savedPath,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => 'application/pdf',
                'status' => 'menunggu',
                'catatan_penolakan' => null,
                'rekomendasi_penolakan' => null,
                'verified_by' => null,
                'verified_at' => null,
                'uploaded_by' => $userName . ' (' . ucfirst($userRole) . ')',
            ]
        );

        if (class_exists(AdminAktivitas::class)) {
            AdminAktivitas::record(
                "Upload Berkas Siswa ({$namaBerkas}): {$pd->nama}",
                'Kesiswaan',
                "File PDF {$file->getClientOriginalName()} berhasil diunggah dan menunggu verifikasi.",
                'info'
            );
        }

        return back()->with('success', "Berkas \"{$namaBerkas}\" berhasil diunggah dalam format PDF. Tim Kesiswaan akan memvalidasi dokumen Anda.");
    }

    /**
     * Preview / Stream File PDF Berkas Siswa
     */
    public function preview($id)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $userRole = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $userId = is_array($user) ? ($user['pengguna_id'] ?? ($user['id'] ?? null)) : ($user->pengguna_id ?? ($user->id ?? null));

        $berkas = PesertaDidikBerkas::findOrFail($id);

        // Otorisasi: Siswa hanya boleh lihat berkas miliknya sendiri, kecuali staf kesiswaan/admin/guru
        if ($userRole === 'peserta_didik') {
            $myPdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
            if (!$myPdId && !empty($user['username'])) {
                $myPdId = DB::table('peserta_didik')->where('nisn', $user['username'])->orWhere('nik', $user['username'])->value('peserta_didik_id');
            }
            if ($myPdId !== $berkas->peserta_didik_id) {
                abort(403, 'Akses ditolak. Anda tidak berhak melihat dokumen siswa lain.');
            }
        }

        if (!Storage::disk('public')->exists($berkas->file_path)) {
            abort(404, 'File PDF tidak ditemukan di server penyimpanan.');
        }

        $fullPath = Storage::disk('public')->path($berkas->file_path);

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . addslashes($berkas->file_name) . '"',
        ]);
    }

    /**
     * Hapus Berkas yang Belum Valid atau Ingin Diganti
     */
    public function destroy($id)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $userRole = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $berkas = PesertaDidikBerkas::findOrFail($id);

        if ($userRole === 'peserta_didik') {
            $myPdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
            if ($myPdId !== $berkas->peserta_didik_id) {
                return back()->with('error', 'Akses ditolak.');
            }
        }

        if (!empty($berkas->file_path)) {
            Storage::disk('public')->delete($berkas->file_path);
        }

        $nama = $berkas->nama_berkas;
        $berkas->delete();

        return back()->with('success', "File berkas \"{$nama}\" berhasil dihapus.");
    }
}
