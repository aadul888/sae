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
                'foto_url' => session('user.foto_url'),
            ];
        } else {
            // Admin
            $profileDetails = [
                'email' => $user->username,
                'level' => 'Administrator Sistem',
                'sekolah' => $sekolah->nama ?? 'SMK Swasta Kristen Tagari Rantepao',
                'npsn' => $sekolah->npsn ?? '40306164',
                'foto_url' => session('user.foto_url'),
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
}
