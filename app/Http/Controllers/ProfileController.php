<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PesertaDidikMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ProfileController extends Controller
{
    /**
     * Tampilan modul profil pengguna & keamanan akun
     */
    public function index()
    {
        $sessionUser = session('user');
        if (!$sessionUser) {
            return redirect()->route('login');
        }

        $userId = is_array($sessionUser)
            ? ($sessionUser['pengguna_id'] ?? ($sessionUser['id'] ?? null))
            : ($sessionUser->pengguna_id ?? null);

        $user = User::where('pengguna_id', $userId)->first();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Sesi pengguna tidak valid.');
        }

        $role = $user->role;
        $profileDetails = [];
        $sekolah = Schema::hasTable('sekolah') ? DB::table('sekolah')->first() : null;

        if ($role === 'peserta_didik') {
            $pd = !empty($user->peserta_didik_id) && Schema::hasTable('peserta_didik')
                ? DB::table('peserta_didik')->where('peserta_didik_id', $user->peserta_didik_id)->first()
                : null;

            $meta = !empty($user->peserta_didik_id) && Schema::hasTable('peserta_didik_meta')
                ? PesertaDidikMeta::where('peserta_didik_id', $user->peserta_didik_id)->first()
                : null;

            $rombel = null;
            $jurusan = null;
            if ($pd && !empty($pd->rombongan_belajar_id) && Schema::hasTable('rombongan_belajar')) {
                $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $pd->rombongan_belajar_id)->first();
                if ($rombel && !empty($rombel->jurusan_sp_id) && Schema::hasTable('jurusan_sp')) {
                    $jurusan = DB::table('jurusan_sp')->where('jurusan_sp_id', $rombel->jurusan_sp_id)->first();
                }
            }

            $profileDetails = [
                'nisn' => $pd->nisn ?? $user->username,
                'nipd' => $pd->nipd ?? '-',
                'nik' => $pd->nik ?? '-',
                'jenis_kelamin' => ($pd->jenis_kelamin ?? 'L') === 'P' ? 'Perempuan' : 'Laki-laki',
                'tempat_lahir' => $pd->tempat_lahir ?? '-',
                'tanggal_lahir' => $pd->tanggal_lahir ?? '-',
                'kelas' => $pd->nama_rombel ?? ($rombel->nama ?? '-'),
                'jurusan' => $jurusan->nama_jurusan_sp ?? ($rombel->jurusan_id_str ?? '-'),
                'sekolah' => $sekolah->nama ?? 'SMK Swasta Kristen Tagari Rantepao',
                'npsn' => $sekolah->npsn ?? '40306164',
                'foto_url' => $meta?->foto_url ?? session('user.foto_url'),
            ];
        } elseif ($role === 'guru' || $role === 'tendik') {
            $gtk = !empty($user->ptk_id) && Schema::hasTable('gtk')
                ? DB::table('gtk')->where('ptk_id', $user->ptk_id)->first()
                : null;

            $profileDetails = [
                'nip' => $gtk->nip ?? '-',
                'nuptk' => $gtk->nuptk ?? '-',
                'nik' => $gtk->nik ?? '-',
                'jenis_ptk' => $gtk->jenis_ptk_id_str ?? ($role === 'guru' ? 'Guru Mapel' : 'Tenaga Kependidikan'),
                'jabatan' => $gtk->jabatan_ptk_id_str ?? '-',
                'mapel' => $gtk->bidang_studi_terakhir ?? '-',
                'sekolah' => $sekolah->nama ?? 'SMK Swasta Kristen Tagari Rantepao',
                'npsn' => $sekolah->npsn ?? '40306164',
                'foto_url' => $user->foto_url ?? session('user.foto_url'),
            ];
        } else {
            // Admin
            $profileDetails = [
                'email' => $user->username,
                'level' => 'Administrator Sistem',
                'sekolah' => $sekolah->nama ?? 'SMK Swasta Kristen Tagari Rantepao',
                'npsn' => $sekolah->npsn ?? '40306164',
                'foto_url' => $user->foto_url ?? session('user.foto_url'),
            ];
        }

        return view('dashboard.profile', compact('user', 'profileDetails', 'role', 'sekolah'));
    }

    /**
     * Proses ubah password akun
     */
    public function updatePassword(Request $request)
    {
        $sessionUser = session('user');
        if (!$sessionUser) {
            return redirect()->route('login');
        }

        $userId = is_array($sessionUser)
            ? ($sessionUser['pengguna_id'] ?? ($sessionUser['id'] ?? null))
            : ($sessionUser->pengguna_id ?? null);

        $user = User::where('pengguna_id', $userId)->first();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Pengguna tidak ditemukan.');
        }

        $request->validate([
            'current_password' => 'required',
            'password' => [
                'required',
                'string',
                'min:8',
                'max:15',
                'regex:/^\S*$/', // Hindari spasi
                'regex:/[A-Z]/', // Huruf besar
                'regex:/[a-z]/', // Huruf kecil
                'regex:/[0-9]/', // Angka
                'regex:/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`]/', // Simbol khusus
                'different:current_password',
                'confirmed',
            ],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.max' => 'Password baru maksimal 15 karakter.',
            'password.regex' => 'Password harus mengandung huruf besar (A-Z), huruf kecil (a-z), angka (0-9), karakter khusus (!@#), serta tidak boleh mengandung spasi.',
            'password.different' => 'Password baru tidak boleh sama dengan password saat ini.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        // Verifikasi password saat ini
        if (!password_verify($request->current_password, $user->password ?? '')) {
            return back()->withErrors(['current_password' => 'Password saat ini yang Anda masukkan salah.']);
        }

        // Simpan password baru
        $user->password = Hash::make($request->password);
        $user->password_updated_at = now();

        $raw = !empty($user->raw_data) ? (json_decode($user->raw_data, true) ?: []) : [];
        $raw['password_updated_at'] = now()->toDateTimeString();
        $raw['is_password_updated'] = true;
        $user->raw_data = json_encode($raw);

        $user->save();

        return back()->with('success', 'Password akun berhasil diperbarui!');
    }

    /**
     * Proses perbarui informasi kontak (No HP & Alamat)
     */
    public function updateContact(Request $request)
    {
        $sessionUser = session('user');
        if (!$sessionUser) {
            return redirect()->route('login');
        }

        $userId = is_array($sessionUser)
            ? ($sessionUser['pengguna_id'] ?? ($sessionUser['id'] ?? null))
            : ($sessionUser->pengguna_id ?? null);

        $user = User::where('pengguna_id', $userId)->first();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Pengguna tidak ditemukan.');
        }

        $validated = $request->validate([
            'no_hp' => 'nullable|string|max:25',
            'alamat' => 'nullable|string|max:500',
        ]);

        $user->no_hp = $validated['no_hp'] ?? null;
        $user->alamat = $validated['alamat'] ?? null;
        $user->save();

        // Sync ke session
        $sess = session('user');
        if (is_array($sess)) {
            $sess['no_hp'] = $user->no_hp;
            $sess['alamat'] = $user->alamat;
            session(['user' => $sess]);
        }

        return back()->with('success', 'Informasi profil berhasil disimpan.');
    }

    /**
     * Unggah pasfoto mandiri khusus Guru dan Tendik
     */
    public function uploadFoto(Request $request)
    {
        $sessionUser = session('user');
        if (!$sessionUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi login telah berakhir. Silakan login kembali.',
            ], 401);
        }

        $userId = is_array($sessionUser)
            ? ($sessionUser['pengguna_id'] ?? ($sessionUser['id'] ?? null))
            : ($sessionUser->pengguna_id ?? null);

        $user = User::where('pengguna_id', $userId)->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        }

        $role = $user->role;
        if ($role !== 'guru' && $role !== 'tendik' && $role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Fitur unggah foto mandiri ini khusus untuk Guru, Tenaga Kependidikan (Tendik), dan Administrator.',
            ], 403);
        }

        $request->validate([
            'foto' => 'required|file|mimes:png,jpg,jpeg|max:5120',
        ], [
            'foto.required' => 'Pilih file foto yang akan diunggah.',
            'foto.file' => 'File tidak valid.',
            'foto.mimes' => 'Format file wajib berupa gambar PNG, JPG, atau JPEG.',
            'foto.max' => 'Ukuran file foto maksimal 5 MB.',
        ]);

        $uploadedFile = $request->file('foto');
        $optimizer = new \App\Services\ImageOptimizerService();

        try {
            // Hapus file lama jika ada
            if (!empty($user->foto_path)) {
                $optimizer->deleteFile($user->foto_path);
            }

            $prefix = ($role === 'admin' ? 'foto_admin_' : 'foto_gtk_') . ($user->username ?: preg_replace('/[^a-zA-Z0-9_-]/', '', $user->pengguna_id));

            // Jika input adalah JPG/JPEG, konversikan sementara ke PNG di memori agar kompatibel dengan optimizer lossless PNG
            $extension = strtolower($uploadedFile->getClientOriginalExtension());
            if ($extension !== 'png') {
                $sourcePath = $uploadedFile->getRealPath();
                $srcImage = @imagecreatefromjpeg($sourcePath);
                if (!$srcImage) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Gagal membaca file gambar JPG/JPEG yang diunggah.',
                    ], 422);
                }

                $tempPngPath = tempnam(sys_get_temp_dir(), 'gtk_opt_') . '.png';
                imagepng($srcImage, $tempPngPath, 9);
                imagedestroy($srcImage);

                $convertedFile = new \Illuminate\Http\UploadedFile(
                    $tempPngPath,
                    $uploadedFile->getClientOriginalName() . '.png',
                    'image/png',
                    null,
                    true
                );

                $result = $optimizer->optimizeAndSavePng(
                    $convertedFile,
                    \App\Services\ImageOptimizerService::ASSET_DIR_FOTO_GTK,
                    $prefix,
                    1000
                );

                @unlink($tempPngPath);
            } else {
                $result = $optimizer->optimizeAndSavePng(
                    $uploadedFile,
                    \App\Services\ImageOptimizerService::ASSET_DIR_FOTO_GTK,
                    $prefix,
                    1000
                );
            }

            $user->foto_path = $result['path'];
            $user->save();

            // Sinkronkan ke session aktif
            $sess = session('user');
            if (is_array($sess)) {
                $sess['foto_path'] = $user->foto_path;
                $sess['foto_url'] = $user->foto_url;
                session(['user' => $sess]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Pasfoto berhasil diperbarui.',
                'data' => [
                    'foto_url' => $user->foto_url,
                    'width' => $result['width'],
                    'height' => $result['height'],
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengunggah foto: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hapus pasfoto mandiri khusus Guru dan Tendik
     */
    public function deleteFoto(Request $request)
    {
        $sessionUser = session('user');
        if (!$sessionUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi login telah berakhir.',
            ], 401);
        }

        $userId = is_array($sessionUser)
            ? ($sessionUser['pengguna_id'] ?? ($sessionUser['id'] ?? null))
            : ($sessionUser->pengguna_id ?? null);

        $user = User::where('pengguna_id', $userId)->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        }

        $role = $user->role;
        if ($role !== 'guru' && $role !== 'tendik' && $role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Aksi ini khusus untuk Guru, Tendik, dan Administrator.',
            ], 403);
        }

        if (empty($user->foto_path)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada foto profil yang dapat dihapus.',
            ], 400);
        }

        $optimizer = new \App\Services\ImageOptimizerService();
        $optimizer->deleteFile($user->foto_path);

        $user->foto_path = null;
        $user->save();

        // Update session
        $sess = session('user');
        if (is_array($sess)) {
            $sess['foto_path'] = null;
            $sess['foto_url'] = null;
            session(['user' => $sess]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Foto profil berhasil dihapus.',
        ]);
    }
}
