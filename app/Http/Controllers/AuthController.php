<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
            $cleanIdentifier = preg_replace('/[^0-9]/', '', $identifier);
            $pinInput = trim((string) $request->input('tgl_lahir_anak', $request->input('password', '')));

            // Cari peserta didik berdasarkan NISN, NIK, atau NIPD
            $student = DB::table('peserta_didik')
                ->where(function ($q) use ($identifier, $cleanIdentifier) {
                    $q->where('nisn', $identifier)
                      ->orWhere('nik', $identifier)
                      ->orWhere('nipd', $identifier);
                    if ($cleanIdentifier !== '' && $cleanIdentifier !== $identifier) {
                        $q->orWhere('nisn', $cleanIdentifier)
                          ->orWhere('nik', $cleanIdentifier)
                          ->orWhere('nipd', $cleanIdentifier);
                    }
                })
                ->first();

            if (!$student) {
                return back()->withInput()->with('error', "Data peserta didik dengan NISN/NIK {$identifier} tidak ditemukan.");
            }

            // Verifikasi tanggal lahir / NIK / PIN (fleksibel: DD-MM-YYYY, YYYY-MM-DD, raw digit, atau NIK)
            $isValidParent = false;

            if (!empty($student->tanggal_lahir)) {
                $rawDbDate = trim((string) $student->tanggal_lahir);
                $dbYmd = null;
                if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $rawDbDate, $m)) {
                    $dbYmd = sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
                } else {
                    try {
                        $dbYmd = \Carbon\Carbon::parse($rawDbDate)->format('Y-m-d');
                    } catch (\Throwable $e) {
                        $dbYmd = substr($rawDbDate, 0, 10);
                    }
                }

                $cleanDbDateDigits = preg_replace('/[^0-9]/', '', $dbYmd ?: $rawDbDate);

                $candidates = [];
                $candidates[] = $pinInput;

                // Format DD-MM-YYYY, DD/MM/YYYY, DD.MM.YYYY
                if (preg_match('/^(\d{1,2})[-\/\.](\d{1,2})[-\/\.](\d{4})$/', $pinInput, $m)) {
                    $candidates[] = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
                }
                // Format YYYY-MM-DD, YYYY/MM/DD
                if (preg_match('/^(\d{4})[-\/\.](\d{1,2})[-\/\.](\d{1,2})$/', $pinInput, $m)) {
                    $candidates[] = sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
                }

                // Format raw 8 digit angka (DDMMYYYY atau YYYYMMDD)
                $cleanInputDigits = preg_replace('/[^0-9]/', '', $pinInput);
                if (strlen($cleanInputDigits) === 8) {
                    // DDMMYYYY -> YYYY-MM-DD
                    $candidates[] = sprintf('%04d-%02d-%02d', substr($cleanInputDigits, 4, 4), substr($cleanInputDigits, 2, 2), substr($cleanInputDigits, 0, 2));
                    // YYYYMMDD -> YYYY-MM-DD
                    $candidates[] = sprintf('%04d-%02d-%02d', substr($cleanInputDigits, 0, 4), substr($cleanInputDigits, 4, 2), substr($cleanInputDigits, 6, 2));
                }

                foreach ($candidates as $cand) {
                    if ($dbYmd && $cand === $dbYmd) {
                        $isValidParent = true;
                        break;
                    }
                    if ($cand === $rawDbDate) {
                        $isValidParent = true;
                        break;
                    }
                    $cleanCand = preg_replace('/[^0-9]/', '', $cand);
                    if ($cleanCand !== '' && $cleanCand === $cleanDbDateDigits) {
                        $isValidParent = true;
                        break;
                    }
                }

                if (!$isValidParent) {
                    try {
                        $parsedInput = \Carbon\Carbon::parse($pinInput)->format('Y-m-d');
                        if ($parsedInput === $dbYmd || $parsedInput === $rawDbDate) {
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

        $this->establishUserSession(
            $user,
            'Autentikasi akun berhasil via Portal SAE (IP: ' . $request->ip() . ')',
            $request->ip(),
            'Login Portal'
        );

        // Arahkan kembali ke formulir jika ada antrean URL yang dituju
        if (session()->has('url.intended')) {
            $intended = session()->pull('url.intended');
            return redirect()->to($intended)->with('success', 'Selamat datang kembali, ' . ($user->name ?? $user->nama));
        }

        return redirect()->route('dashboard.' . $user->role)->with('success', 'Selamat datang kembali, ' . ($user->name ?? $user->nama));
    }

    /**
     * Redirect pengguna ke halaman persetujuan login Google (OAuth 2.0)
     */
    public function redirectToGoogle()
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            return redirect()->route('login')->with('warning', 'Integrasi Akun Google belum dikonfigurasi. Harap isi GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET pada berkas konfigurasi (.env).');
        }

        $state = Str::random(40);
        session(['google_oauth_state' => $state]);

        $redirectUri = config('services.google.redirect_uri') ?: route('auth.google.callback');

        $params = [
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'access_type'   => 'online',
            'prompt'        => 'select_account',
            'state'         => $state,
        ];

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
    }

    /**
     * Tangani callback otorisasi dari Google
     */
    public function handleGoogleCallback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('login')->with('error', 'Login Google dibatalkan atau terjadi kesalahan: ' . $request->input('error'));
        }

        $code = $request->query('code');
        $state = $request->query('state');
        $savedState = session()->pull('google_oauth_state');

        if (empty($code)) {
            return redirect()->route('login')->with('error', 'Kode otorisasi dari Google tidak ditemukan.');
        }

        if (empty($state) || empty($savedState) || !hash_equals($savedState, $state)) {
            return redirect()->route('login')->with('error', 'Validasi sesi keamanan (state) login Google gagal. Silakan coba lagi.');
        }

        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect_uri') ?: route('auth.google.callback');

        try {
            // Tukar authorization code dengan access token
            $tokenResponse = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
                'code'          => $code,
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri'  => $redirectUri,
                'grant_type'    => 'authorization_code',
            ]);

            if (!$tokenResponse->successful()) {
                $errDesc = $tokenResponse->json('error_description') ?: 'Gagal menukarkan token otorisasi ke Google.';
                return redirect()->route('login')->with('error', $errDesc);
            }

            $accessToken = $tokenResponse->json('access_token');
            if (empty($accessToken)) {
                return redirect()->route('login')->with('error', 'Access token dari Google tidak valid.');
            }

            // Ambil data profil pengguna dari Google UserInfo API
            $userInfoResponse = Http::withToken($accessToken)->timeout(15)->get('https://www.googleapis.com/oauth2/v3/userinfo');
            if (!$userInfoResponse->successful()) {
                return redirect()->route('login')->with('error', 'Gagal mengambil informasi profil dari akun Google Anda.');
            }

            $googleUser = $userInfoResponse->json();
            $email = strtolower(trim((string) ($googleUser['email'] ?? '')));
            $emailVerified = (bool) ($googleUser['email_verified'] ?? false);

            if (empty($email) || !$emailVerified) {
                return redirect()->route('login')->with('error', 'Email Google tidak valid atau belum diverifikasi.');
            }

            // Validasi batasan domain: smk.belajar.id (termasuk subdomain *.smk.belajar.id atau belajar.id)
            if (!$this->isAllowedBelajarIdDomain($email)) {
                return redirect()->route('login')->with('error', "Login Google dibatasi khusus untuk akun belajar.id (@smk.belajar.id, @guru.smk.belajar.id, @admin.smk.belajar.id). Akun '{$email}' tidak diizinkan.");
            }

            // Cari kecocokan data pengguna di database Dapodik
            $user = $this->findUserByDapodikEmail($email);

            if (!$user) {
                return redirect()->route('login')->with('error', "Akun Google '{$email}' belum terdaftar di sistem Dapodik sekolah. Pastikan email belajar.id sudah tersimpan atau tersinkron dari Dapodik.");
            }

            // Otorisasi role yang diperbolehkan: admin, guru, tendik, dan peserta_didik
            $allowedRoles = ['admin', 'guru', 'tendik', 'peserta_didik'];
            if (!in_array($user->role, $allowedRoles, true)) {
                return redirect()->route('login')->with('error', "Peran '{$user->peran_id_str}' tidak memiliki hak akses masuk portal ini.");
            }

            // Bangun sesi pengguna dan catat aktivitas sistem
            $userData = $this->establishUserSession(
                $user,
                "Autentikasi akun Google Belajar.id ({$email}) berhasil via OAuth 2.0 (IP: " . $request->ip() . ")",
                $request->ip(),
                'Login Google Belajar.id'
            );

            // Tampilkan foto profil dari Google jika pengguna belum memiliki foto
            $googlePicture = $googleUser['picture'] ?? null;
            if (!empty($googlePicture) && empty($userData['foto_url'])) {
                $userData['foto_url'] = $googlePicture;
                session(['user' => $userData]);
            }

            if (session()->has('url.intended')) {
                $intended = session()->pull('url.intended');
                return redirect()->to($intended)->with('success', 'Selamat datang kembali, ' . ($user->name ?? $user->nama));
            }

            return redirect()->route('dashboard.' . $user->role)->with('success', 'Selamat datang, ' . ($user->name ?? $user->nama) . ' (Login via Akun Belajar.id)');

        } catch (\Throwable $e) {
            return redirect()->route('login')->with('error', 'Terjadi kendala koneksi ke server Google: ' . $e->getMessage());
        }
    }

    /**
     * Validasi domain akun Google Belajar.id SMK
     */
    protected function isAllowedBelajarIdDomain(string $email): bool
    {
        $domain = strtolower((string) substr(strrchr($email, '@'), 1));
        if (empty($domain)) {
            return false;
        }

        // Domain resmi SMK: smk.belajar.id, guru.smk.belajar.id, admin.smk.belajar.id, dll.
        if ($domain === 'smk.belajar.id' || str_ends_with($domain, '.smk.belajar.id') || str_ends_with($domain, 'belajar.id')) {
            return true;
        }

        return false;
    }

    /**
     * Temukan user berdasarkan email Dapodik di tabel pengguna, peserta_didik, atau gtk
     */
    protected function findUserByDapodikEmail(string $email): ?User
    {
        $email = strtolower(trim($email));

        // 1. Cek langsung username pada tabel pengguna
        $user = User::whereRaw('LOWER(username) = ?', [$email])->first();
        if ($user) {
            return $user;
        }

        // 2. Cek email peserta didik pada tabel peserta_didik
        if (Schema::hasTable('peserta_didik') && Schema::hasColumn('peserta_didik', 'email')) {
            $pd = DB::table('peserta_didik')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if ($pd) {
                $user = User::where('peserta_didik_id', $pd->peserta_didik_id)->first();
                if ($user) {
                    return $user;
                }

                if (!empty($pd->nisn)) {
                    $user = User::where('username', trim($pd->nisn))->first();
                    if ($user) {
                        if (empty($user->peserta_didik_id)) {
                            $user->peserta_didik_id = $pd->peserta_didik_id;
                            $user->save();
                        }
                        return $user;
                    }
                }

                // Otomatis buatkan record pengguna jika peserta didik aktif terdaftar di Dapodik
                $studentNisn = !empty($pd->nisn) ? trim($pd->nisn) : $email;
                return User::create([
                    'pengguna_id'      => 'pd-' . ($pd->peserta_didik_id ?: Str::uuid()->toString()),
                    'sekolah_id'       => $pd->sekolah_id ?? null,
                    'username'         => $studentNisn,
                    'nama'             => $pd->nama,
                    'peran_id_str'     => 'Peserta Didik',
                    'password'         => Hash::make($studentNisn),
                    'peserta_didik_id' => $pd->peserta_didik_id,
                ]);
            }
        }

        // 3. Cek tabel peserta_didik_identitas jika ada email tersimpan
        if (Schema::hasTable('peserta_didik_identitas') && Schema::hasColumn('peserta_didik_identitas', 'email')) {
            $identitas = DB::table('peserta_didik_identitas')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if ($identitas && !empty($identitas->peserta_didik_id)) {
                $user = User::where('peserta_didik_id', $identitas->peserta_didik_id)->first();
                if ($user) {
                    return $user;
                }
                if (!empty($identitas->nisn)) {
                    $user = User::where('username', trim($identitas->nisn))->first();
                    if ($user) {
                        if (empty($user->peserta_didik_id)) {
                            $user->peserta_didik_id = $identitas->peserta_didik_id;
                            $user->save();
                        }
                        return $user;
                    }
                }
            }
        }

        // 4. Cek email GTK (Guru / Tendik) pada tabel gtk
        if (Schema::hasTable('gtk') && Schema::hasColumn('gtk', 'email')) {
            $gtk = DB::table('gtk')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if ($gtk) {
                $user = User::where('ptk_id', $gtk->ptk_id)->first();
                if ($user) {
                    return $user;
                }

                if (!empty($gtk->nip) || !empty($gtk->nuptk)) {
                    $user = User::where(function ($q) use ($gtk) {
                        if (!empty($gtk->nip)) $q->orWhere('username', trim($gtk->nip));
                        if (!empty($gtk->nuptk)) $q->orWhere('username', trim($gtk->nuptk));
                    })->first();

                    if ($user) {
                        if (empty($user->ptk_id)) {
                            $user->ptk_id = $gtk->ptk_id;
                            $user->save();
                        }
                        return $user;
                    }
                }

                $userByName = User::whereRaw('LOWER(nama) = ?', [strtolower(trim($gtk->nama))])
                    ->where(function ($q) {
                        $q->whereNull('peserta_didik_id')
                          ->orWhere('peserta_didik_id', '');
                    })
                    ->first();

                if ($userByName) {
                    if (empty($userByName->ptk_id)) {
                        $userByName->ptk_id = $gtk->ptk_id;
                        $userByName->save();
                    }
                    return $userByName;
                }
            }
        }

        // 5. Cek raw_data pada tabel pengguna
        $usersWithRaw = User::whereNotNull('raw_data')->get();
        foreach ($usersWithRaw as $u) {
            if (!empty($u->raw_data)) {
                $raw = json_decode($u->raw_data, true);
                if (is_array($raw)) {
                    $rawEmail = strtolower(trim((string) ($raw['email'] ?? ($raw['surel'] ?? ''))));
                    if ($rawEmail === $email) {
                        return $u;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Membangun payload sesi multi-user dan mencatat log aktivitas
     */
    private function establishUserSession(User $user, ?string $keterangan = null, ?string $ip = null, string $aktivitas = 'Login Portal'): array
    {
        $userData = $user->toArray();
        $userData['name'] = $user->name ?? $user->nama;
        $userData['role'] = $user->role;
        if (!empty($user->foto_url)) {
            $userData['foto_url'] = $user->foto_url;
        }

        // Ambil relasi GTK atau Peserta Didik jika ada
        if (!empty($user->ptk_id)) {
            $gtk = DB::table('gtk')->where('ptk_id', $user->ptk_id)->first();
            if ($gtk) {
                $userData['nip'] = $gtk->nip;

                if ($user->role === 'tendik') {
                    $bagianTugas = null;
                    if (Schema::hasTable('ptk_tugas_tambahan') && Schema::hasTable('ref_tugas_tambahan')) {
                        $activeDuties = DB::table('ptk_tugas_tambahan as ptt')
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
                    $mapelUtama = null;
                    if (Schema::hasTable('pembelajaran')) {
                        $topMapel = DB::table('pembelajaran')
                            ->where('ptk_id', $user->ptk_id)
                            ->select('nama_mata_pelajaran', DB::raw('SUM(jam_mengajar_per_minggu) as total_jam'))
                            ->groupBy('nama_mata_pelajaran')
                            ->orderByDesc('total_jam')
                            ->first();
                        $mapelUtama = $topMapel?->nama_mata_pelajaran;
                    }
                    $userData['mapel'] = $mapelUtama ?: ($gtk->bidang_studi_terakhir ?? ($gtk->jabatan_ptk_id_str ?? 'Guru Mata Pelajaran'));
                }
            }
        } elseif (!empty($user->peserta_didik_id)) {
            $pd = DB::table('peserta_didik')->where('peserta_didik_id', $user->peserta_didik_id)->first();
            if ($pd) {
                $userData['nisn'] = $pd->nisn;
                $userData['kelas'] = $pd->nama_rombel;
            }
            if (Schema::hasTable('peserta_didik_meta')) {
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

        try {
            if (Schema::hasTable('admin_aktivitas')) {
                \App\Models\AdminAktivitas::create([
                    'admin_name'     => $userData['name'],
                    'admin_username' => $userData['username'] ?? ($userData['nisn'] ?? null),
                    'modul'          => ($user->role === 'peserta_didik' || !empty($user->peserta_didik_id)) ? 'Siswa' : 'Autentikasi',
                    'aktivitas'      => $aktivitas,
                    'keterangan'     => $keterangan ?: ('Autentikasi akun berhasil via Portal SAE (IP: ' . ($ip ?: request()->ip()) . ')'),
                    'ip_address'     => $ip ?: request()->ip(),
                    'tipe'           => 'info',
                ]);
            }
        } catch (\Throwable) {
        }

        return $userData;
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
