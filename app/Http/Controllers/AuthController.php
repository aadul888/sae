<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session()->has('user')) {
            return redirect()->route('dashboard.' . session('user.role'));
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $identifier = trim($request->input('username'));
        $password = $request->input('password');

        // Cari user berdasarkan username, atau relasi GTK (nip/nuptk/nik) / Peserta Didik (nisn/nik/nipd)
        $user = User::where('username', $identifier)
            ->orWhereIn('ptk_id', function ($q) use ($identifier) {
                $q->select('ptk_id')->from('gtk')
                  ->where('nip', $identifier)
                  ->orWhere('nuptk', $identifier)
                  ->orWhere('nik', $identifier)
                  ->orWhere('email', $identifier);
            })
            ->orWhereIn('peserta_didik_id', function ($q) use ($identifier) {
                $q->select('peserta_didik_id')->from('peserta_didik')
                  ->where('nisn', $identifier)
                  ->orWhere('nik', $identifier)
                  ->orWhere('nipd', $identifier)
                  ->orWhere('email', $identifier);
            })
            ->first();

        if (!$user) {
            // Cek apakah ada di tabel peserta_didik (misal belum dibuatkan record pengguna)
            $pdCandidate = \Illuminate\Support\Facades\DB::table('peserta_didik')
                ->where('nisn', $identifier)
                ->orWhere('nik', $identifier)
                ->orWhere('nipd', $identifier)
                ->orWhere('email', $identifier)
                ->first();

            if ($pdCandidate) {
                $studentNisn = $pdCandidate->nisn ?: $identifier;
                $user = User::create([
                    'pengguna_id' => 'pd-' . ($pdCandidate->peserta_didik_id ?? \Illuminate\Support\Str::uuid()->toString()),
                    'sekolah_id' => $pdCandidate->sekolah_id ?? null,
                    'username' => $studentNisn,
                    'nama' => $pdCandidate->nama,
                    'peran_id_str' => 'Peserta Didik',
                    'password' => Hash::make($studentNisn),
                    'peserta_didik_id' => $pdCandidate->peserta_didik_id,
                ]);
            } else {
                return back()->withInput()->with('error', 'Username/Email/NIP/NISN tidak ditemukan.');
            }
        }

        // Cek khusus Peserta Didik terkait aturan bisnis:
        // "Login dengan password NISN ini berlaku hanya 1 kali saat aktivasi"
        if ($user->role === 'peserta_didik') {
            $studentNisn = '';
            if (!empty($user->peserta_didik_id)) {
                $pd = \Illuminate\Support\Facades\DB::table('peserta_didik')
                    ->where('peserta_didik_id', $user->peserta_didik_id)
                    ->select('nisn')
                    ->first();
                if ($pd && !empty($pd->nisn)) {
                    $studentNisn = trim($pd->nisn);
                }
            }
            if (empty($studentNisn) && ctype_digit(trim($user->username))) {
                $studentNisn = trim($user->username);
            }

            // Cek apakah peserta didik sudah pernah melakukan update password
            $hasUpdatedPassword = !empty($user->password_updated_at);
            if (!$hasUpdatedPassword && !empty($user->raw_data)) {
                $raw = json_decode($user->raw_data, true);
                if (!empty($raw['password_updated_at']) || !empty($raw['is_password_updated'])) {
                    $hasUpdatedPassword = true;
                }
            }
            // Jika password di database sudah bukan NISN (sudah diganti ke password personal)
            if (!$hasUpdatedPassword && !empty($user->password) && !empty($studentNisn) && !password_verify($studentNisn, $user->password) && $user->password !== $studentNisn) {
                $hasUpdatedPassword = true;
            }

            // Jika peserta didik menginputkan password berupa NISN:
            if (!empty($studentNisn) && $password === $studentNisn) {
                // Jika SUDAH pernah update password: TOLAK login dengan password NISN!
                if ($hasUpdatedPassword) {
                    return back()->withInput()->with('error', 'Login menggunakan password default (NISN) hanya berlaku 1 kali saat aktivasi. Anda sudah pernah memperbarui password, silakan gunakan password baru Anda.');
                }

                // Jika BELUM pernah update password: izinkan 1 kali ini & paksa ke form update password
                session([
                    'force_update_password' => [
                        'pengguna_id' => $user->pengguna_id,
                        'nama' => $user->name ?? $user->nama,
                        'nisn' => $studentNisn,
                        'username' => $user->username,
                    ]
                ]);

                return redirect()->route('auth.force-update-password')
                    ->with('warning', 'Akun Anda terdeteksi masih menggunakan password default (NISN). Demi keamanan data, Anda wajib membuat password baru sebelum masuk ke portal.');
            }
        }

        $isValid = false;
        $dbPassword = $user->password ?? '';

        if (empty($dbPassword)) {
            // Password kosong di Dapodik: ijinkan password default 'Sae12345!' atau NISN/NIP
            if ($password === 'Sae12345!' || $password === $identifier) {
                $isValid = true;
            }
        } elseif (password_verify($password, $dbPassword)) {
            $isValid = true;
        } elseif (Hash::needsRehash($dbPassword)) {
            if ($password === $dbPassword || md5($password) === $dbPassword || sha1($password) === $dbPassword) {
                $isValid = true;
            }
        }

        if (!$isValid) {
            return back()->withInput()->with('error', 'Username/Email/NIP/NISN atau password tidak valid.');
        }

        $userData = $user->toArray();
        $userData['name'] = $user->name ?? $user->nama;
        $userData['role'] = $user->role;

        // Ambil relasi GTK atau Peserta Didik jika ada
        if (!empty($user->ptk_id)) {
            $gtk = \Illuminate\Support\Facades\DB::table('gtk')->where('ptk_id', $user->ptk_id)->first();
            if ($gtk) {
                $userData['nip'] = $gtk->nip;
                $userData['mapel'] = $gtk->bidang_studi_terakhir ?? $gtk->jabatan_ptk_id_str;
            }
        } elseif (!empty($user->peserta_didik_id)) {
            $pd = \Illuminate\Support\Facades\DB::table('peserta_didik')->where('peserta_didik_id', $user->peserta_didik_id)->first();
            if ($pd) {
                $userData['nisn'] = $pd->nisn;
                $userData['kelas'] = $pd->nama_rombel;
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('peserta_didik_meta')) {
                $meta = \App\Models\PesertaDidikMeta::where('peserta_didik_id', $user->peserta_didik_id)->first();
                if ($meta && !empty($meta->foto_path)) {
                    $userData['foto_url'] = $meta->foto_url;
                }
            }
        }

        session(['user' => $userData]);

        return redirect()->route('dashboard.' . $user->role)->with('success', 'Selamat datang kembali, ' . $user->name);
    }

    /**
     * Tampilan form wajib ganti password bagi peserta didik
     */
    public function showForceUpdatePassword()
    {
        if (!session()->has('force_update_password')) {
            return redirect()->route('login');
        }

        $forceData = session('force_update_password');
        return view('auth.force-update-password', compact('forceData'));
    }

    /**
     * Proses pembaruan password wajib peserta didik
     */
    public function processForceUpdatePassword(Request $request)
    {
        if (!session()->has('force_update_password')) {
            return redirect()->route('login');
        }

        $forceData = session('force_update_password');
        $studentNisn = $forceData['nisn'] ?? '';

        $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'max:15',
                'regex:/^\S*$/', // Hindari spasi
                'regex:/[A-Z]/', // Huruf besar
                'regex:/[a-z]/', // Huruf kecil
                'regex:/[0-9]/', // Angka
                'regex:/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`]/', // Simbol khusus (!@#)
                'confirmed',
            ],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.max' => 'Password baru maksimal 15 karakter.',
            'password.regex' => 'Password harus mengandung kombinasi huruf besar (A-Z), huruf kecil (a-z), angka (0-9), karakter khusus/simbol (!@#), serta tidak boleh mengandung spasi.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        if (!empty($studentNisn) && $request->password === $studentNisn) {
            return back()->withErrors(['password' => 'Password baru tidak boleh sama dengan NISN lama Anda.']);
        }

        $user = User::where('pengguna_id', $forceData['pengguna_id'])->first();
        if (!$user) {
            session()->forget('force_update_password');
            return redirect()->route('login')->with('error', 'Data pengguna tidak ditemukan. Silakan login kembali.');
        }

        $user->password = Hash::make($request->password);
        $user->password_updated_at = now();

        $raw = !empty($user->raw_data) ? (json_decode($user->raw_data, true) ?: []) : [];
        $raw['password_updated_at'] = now()->toDateTimeString();
        $raw['is_password_updated'] = true;
        $user->raw_data = json_encode($raw);

        $user->save();

        session()->forget('force_update_password');

        // Sesuai ketentuan nomor 5: setelah berhasil update password arahkan kembali ke form login dan baru bisa masuk
        return redirect()->route('login')->with('success', 'Password berhasil diperbarui! Silakan masuk ke portal menggunakan password baru Anda.');
    }

    /**
     * Batalkan update password dan kembali ke login
     */
    public function cancelForceUpdate()
    {
        session()->forget('force_update_password');
        return redirect()->route('login')->with('info', 'Pembaruan password dibatalkan.');
    }

    public function logout()
    {
        session()->forget('user');
        return redirect()->route('login')->with('info', 'Anda telah keluar dari sistem.');
    }
}

