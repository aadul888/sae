<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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
        $loginType = $request->input('login_type', 'umum');

        // Otentikasi Khusus Portal Orang Tua / Wali Murid
        if ($loginType === 'orang_tua' || $request->filled('nisn_anak')) {
            $request->validate([
                'nisn_anak'      => 'required',
                'tgl_lahir_anak' => 'required',
            ], [
                'nisn_anak.required'      => 'NISN atau NIK peserta didik wajib diisi.',
                'tgl_lahir_anak.required' => 'Password/PIN (Tanggal lahir peserta didik) wajib diisi.',
            ]);

            $identifier = trim((string) $request->input('nisn_anak', $request->input('username', '')));
            $pinInput = trim((string) $request->input('tgl_lahir_anak', $request->input('password', '')));

            // Cari peserta didik berdasarkan NISN, NIK, atau NIPD
            $student = DB::table('peserta_didik')
                ->where(function ($q) use ($identifier) {
                    $q->where('nisn', $identifier)
                      ->orWhere('nik', $identifier)
                      ->orWhere('nipd', $identifier);
                })
                ->first();

            if (!$student) {
                return back()->withInput()->with('error', "Data peserta didik dengan NISN/NIK {$identifier} tidak ditemukan.");
            }

            // Verifikasi tanggal lahir / NIK / PIN
            $isValidParent = false;

            if (!empty($student->tanggal_lahir)) {
                $dbDate = trim($student->tanggal_lahir);
                $cleanInput = preg_replace('/[^0-9]/', '', $pinInput);
                $cleanDbDate = preg_replace('/[^0-9]/', '', $dbDate);

                if ($pinInput === $dbDate || $cleanInput === $cleanDbDate) {
                    $isValidParent = true;
                } else {
                    try {
                        $parsedInput = \Carbon\Carbon::parse($pinInput)->format('Y-m-d');
                        if ($parsedInput === $dbDate) {
                            $isValidParent = true;
                        }
                    } catch (\Throwable $e) {
                    }
                }
            }

            if (!$isValidParent && !empty($student->nik)) {
                if ($pinInput === trim($student->nik)) {
                    $isValidParent = true;
                }
            }

            // Fallback: periksa jika ada user pengguna orang tua yang dibuat manual di database
            if (!$isValidParent) {
                $parentUser = User::where('username', 'ortu_' . $student->nisn)
                    ->orWhere('username', $identifier)
                    ->first();
                if ($parentUser && !empty($parentUser->password) && (password_verify($pinInput, $parentUser->password) || $parentUser->password === $pinInput)) {
                    $isValidParent = true;
                }
            }

            if (!$isValidParent) {
                return back()->withInput()->with('error', "PIN / Tanggal lahir tidak cocok dengan data siswa {$student->nama}.");
            }

            // Ambil foto siswa
            $fotoUrl = null;
            if (Schema::hasTable('peserta_didik_meta')) {
                $meta = \App\Models\PesertaDidikMeta::where('peserta_didik_id', $student->peserta_didik_id)->first();
                $fotoUrl = $meta?->foto_url;
            }

            // Tentukan nama sapaan orang tua / wali sesuai status pekerjaan
            $ayahAlive = !empty($student->nama_ayah) && !str_contains(strtolower($student->pekerjaan_ayah_id_str ?? ''), 'meninggal');
            $ibuAlive = !empty($student->nama_ibu) && !str_contains(strtolower($student->pekerjaan_ibu_id_str ?? ''), 'meninggal');
            $waliAlive = !empty($student->nama_wali) && !str_contains(strtolower($student->pekerjaan_wali_id_str ?? ''), 'meninggal');

            if ($ayahAlive && $ibuAlive) {
                $parentName = 'Bpk. ' . $student->nama_ayah . ' & Ibu ' . $student->nama_ibu;
            } elseif ($ayahAlive) {
                $parentName = 'Bpk. ' . $student->nama_ayah;
            } elseif ($ibuAlive) {
                $parentName = 'Ibu ' . $student->nama_ibu;
            } elseif ($waliAlive) {
                $parentName = 'Bpk/Ibu ' . $student->nama_wali;
            } else {
                $parentName = 'Orang Tua / Wali';
            }

            $userData = [
                'pengguna_id'      => 'ortu-' . $student->peserta_didik_id,
                'name'             => $parentName,
                'nama'             => $parentName,
                'wali_nama'        => $parentName,
                'role'             => 'orang_tua',
                'peran_id_str'     => 'Orang Tua / Wali Murid',
                'peserta_didik_id' => $student->peserta_didik_id,
                'nisn'             => $student->nisn,
                'siswa_nama'       => $student->nama,
                'kelas'            => $student->nama_rombel,
                'foto_url'         => $fotoUrl,
            ];

            session(['user' => $userData]);

            try {
                \App\Models\TendikAktivitas::recordActivity(
                    $userData,
                    'Autentikasi Masuk Portal Orang Tua',
                    'umum',
                    "Orang tua/wali dari {$student->nama} (NISN: {$student->nisn}) berhasil masuk ke portal pemantauan",
                    'selesai',
                    'Sesi Orang Tua Aktif'
                );
            } catch (\Throwable $e) {
            }

            return redirect()->route('dashboard.orang-tua')
                ->with('success', "Selamat datang di Portal Pemantauan Orang Tua / Wali Murid.");
        }

        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $identifier = trim($request->input('username'));
        $password = $request->input('password');

        // Cari user berdasarkan username, atau relasi GTK/Peserta Didik jika tabel dan kolomnya tersedia.
        $userQuery = User::where('username', $identifier);
        if (Schema::hasTable('gtk')) {
            $gtkColumns = array_values(array_filter(['nip', 'nuptk', 'nik', 'email'], fn($column) => Schema::hasColumn('gtk', $column)));
            if ($gtkColumns) {
                $userQuery->orWhereIn('ptk_id', function ($q) use ($identifier, $gtkColumns) {
                    $q->select('ptk_id')->from('gtk')
                        ->where(function ($sub) use ($identifier, $gtkColumns) {
                            foreach ($gtkColumns as $column) {
                                $sub->orWhere($column, $identifier);
                            }
                        });
                });
            }
        }
        if (Schema::hasTable('peserta_didik')) {
            $pdColumns = array_values(array_filter(['nisn', 'nik', 'nipd', 'email'], fn($column) => Schema::hasColumn('peserta_didik', $column)));
            if ($pdColumns) {
                $userQuery->orWhereIn('peserta_didik_id', function ($q) use ($identifier, $pdColumns) {
                    $q->select('peserta_didik_id')->from('peserta_didik')
                        ->where(function ($sub) use ($identifier, $pdColumns) {
                            foreach ($pdColumns as $column) {
                                $sub->orWhere($column, $identifier);
                            }
                        });
                });
            }
        }
        $user = $userQuery->first();

        if (!$user) {
            // Cek apakah ada di tabel peserta_didik (misal belum dibuatkan record pengguna)
            $pdCandidate = null;
            $pdColumns = Schema::hasTable('peserta_didik')
                ? array_values(array_filter(['nisn', 'nik', 'nipd', 'email'], fn($column) => Schema::hasColumn('peserta_didik', $column)))
                : [];
            if ($pdColumns) {
                $pdCandidate = \Illuminate\Support\Facades\DB::table('peserta_didik')
                    ->where(function ($q) use ($identifier, $pdColumns) {
                        foreach ($pdColumns as $column) {
                            $q->orWhere($column, $identifier);
                        }
                    })
                    ->first();
            }

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
        if (!empty($user->foto_url)) {
            $userData['foto_url'] = $user->foto_url;
        }

        // Ambil relasi GTK atau Peserta Didik jika ada
        if (!empty($user->ptk_id)) {
            $gtk = \Illuminate\Support\Facades\DB::table('gtk')->where('ptk_id', $user->ptk_id)->first();
            if ($gtk) {
                $userData['nip'] = $gtk->nip;

                if ($user->role === 'tendik') {
                    // Cari tugas tambahan aktif tendik
                    $bagianTugas = null;
                    if (\Illuminate\Support\Facades\Schema::hasTable('ptk_tugas_tambahan') && \Illuminate\Support\Facades\Schema::hasTable('ref_tugas_tambahan')) {
                        $activeDuties = \Illuminate\Support\Facades\DB::table('ptk_tugas_tambahan as ptt')
                            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                            ->where('ptt.is_active', true)
                            ->where('rtt.is_active', true)
                            ->where(function ($q) use ($user) {
                                $q->where('ptt.user_id', $user->pengguna_id)
                                    ->orWhere('ptt.ptk_id', $user->ptk_id);
                            })
                            ->pluck('rtt.nama')
                            ->filter()
                            ->unique();

                        if ($activeDuties->isNotEmpty()) {
                            $bagianTugas = $activeDuties->implode(', ');
                        }
                    }
                    $userData['mapel'] = $bagianTugas ?: ($gtk->jabatan_ptk_id_str ?: ($gtk->jenis_ptk_id_str ?: 'Tenaga Administrasi Sekolah'));
                } else {
                    // Guru atau role lainnya: ambil mata pelajaran dengan jam mengajar terbanyak
                    $mapelUtama = null;
                    if (\Illuminate\Support\Facades\Schema::hasTable('pembelajaran')) {
                        $topMapel = \Illuminate\Support\Facades\DB::table('pembelajaran')
                            ->where('ptk_id', $user->ptk_id)
                            ->select('nama_mata_pelajaran', \Illuminate\Support\Facades\DB::raw('SUM(jam_mengajar_per_minggu) as total_jam'))
                            ->groupBy('nama_mata_pelajaran')
                            ->orderByDesc('total_jam')
                            ->first();
                        $mapelUtama = $topMapel?->nama_mata_pelajaran;
                    }
                    $userData['mapel'] = $mapelUtama ?: ($gtk->bidang_studi_terakhir ?? ($gtk->jabatan_ptk_id_str ?? 'Guru Mata Pelajaran'));
                }
            }
        } elseif (!empty($user->peserta_didik_id)) {
            $pd = \Illuminate\Support\Facades\DB::table('peserta_didik')->where('peserta_didik_id', $user->peserta_didik_id)->first();
            if ($pd) {
                $userData['nisn'] = $pd->nisn;
                $userData['kelas'] = $pd->nama_rombel;
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('peserta_didik_meta')) {
                $meta = \App\Models\PesertaDidikMeta::where('peserta_didik_id', $user->peserta_didik_id)->first();
                if ($meta) {
                    if (!empty($meta->foto_path)) {
                        $userData['foto_url'] = $meta->foto_url;
                    }
                    $userData['is_koordinator'] = (bool) ($meta->is_koordinator ?? false);
                    $userData['jabatan_koordinator'] = $meta->jabatan_koordinator ?? 'Koordinator Kelas';
                }
            }
        }

        session(['user' => $userData]);

        // Auto-record aktivitas login ke log aktivitas sistem
        try {
            \App\Models\TendikAktivitas::recordActivity(
                $userData,
                'Autentikasi Masuk Sistem (Login)',
                'umum',
                'Berhasil masuk ke portal SAE melalui sesi web autentikasi (IP: ' . $request->ip() . ')',
                'selesai',
                'Sesi Login Aktif'
            );
        } catch (\Throwable) {
        }

        // Arahkan kembali ke formulir jika ada antrean URL yang dituju
        if (session()->has('url.intended')) {
            $intended = session()->pull('url.intended');
            return redirect()->to($intended)->with('success', 'Selamat datang kembali, ' . $user->name);
        }

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
