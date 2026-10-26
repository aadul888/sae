<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private const SORTABLE = ['nama', 'username', 'peran_id_str', 'no_hp', 'email'];

    /**
     * Halaman manajemen pengguna (4 Tab: Admin, Guru, Tendik, Peserta Didik)
     */
    public function index(Request $request)
    {
        $activeTab = $request->get('tab');
        if (!$activeTab) {
            if ($request->has('guru_page')) {
                $activeTab = 'guru';
            } elseif ($request->has('tendik_page')) {
                $activeTab = 'tendik';
            } elseif ($request->has('peserta_didik_page')) {
                $activeTab = 'peserta_didik';
            } elseif ($request->has('tugas_page')) {
                $activeTab = 'tugas_tambahan';
            } elseif ($request->has('admin_page')) {
                $activeTab = 'admin';
            } else {
                $activeTab = 'admin';
            }
        }

        // Kompatibilitas mundur jika ada tautan / bookmark lama yang membawa parameter *_page
        if (!$request->has('page')) {
            $legacyPage = match ($activeTab) {
                'guru' => $request->get('guru_page'),
                'tendik' => $request->get('tendik_page'),
                'peserta_didik' => $request->get('peserta_didik_page'),
                'tugas_tambahan' => $request->get('tugas_page'),
                default => $request->get('admin_page'),
            };
            if ($legacyPage) {
                $request->merge(['page' => $legacyPage]);
            }
        }

        $q         = trim($request->get('q', ''));
        $perPage   = (int) $request->get('perPage', 15);
        $sort      = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'nama';
        $sortDir   = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $authUser = session('user');
        $canCreate = \App\Models\RolePermission::canAccess($authUser, 'menu_pengguna', 'create');
        $canRead   = \App\Models\RolePermission::canAccess($authUser, 'menu_pengguna', 'read');
        $canUpdate = \App\Models\RolePermission::canAccess($authUser, 'menu_pengguna', 'update') || \App\Models\RolePermission::canAccess($authUser, 'fitur_pengguna_edit');
        $canDelete = \App\Models\RolePermission::canAccess($authUser, 'menu_pengguna', 'delete') || \App\Models\RolePermission::canAccess($authUser, 'fitur_pengguna_hapus');

        $adminQuery = User::where(function ($query) {
            $query->where('peran_id_str', 'LIKE', '%admin%')
                ->orWhere('peran_id_str', 'LIKE', '%operator%')
                ->orWhere('peran_id_str', 'LIKE', '%dinas%')
                ->orWhere('peran_id_str', 'LIKE', '%yayasan%');
        });

        // Query Tenaga Kependidikan (Tendik)
        $tendikQuery = User::where(function ($query) {
            $query->where('peran_id_str', 'LIKE', '%tendik%')
                ->orWhere('peran_id_str', 'LIKE', '%tenaga kependidikan%')
                ->orWhere('peran_id_str', 'LIKE', '%tata usaha%')
                ->orWhere('peran_id_str', 'LIKE', '%laboran%')
                ->orWhere('peran_id_str', 'LIKE', '%pustakawan%')
                ->orWhere(function ($qGtk) {
                    $qGtk->whereNotNull('ptk_id')
                        ->whereIn('ptk_id', function ($sub) {
                            $sub->select('ptk_id')->from('gtk')
                                ->where('jenis_ptk_id_str', 'LIKE', '%Tenaga Kependidikan%')
                                ->orWhere('jenis_ptk_id_str', 'NOT LIKE', '%Guru%');
                        });
                });
        })->where(function ($query) {
            $query->where('peran_id_str', 'NOT LIKE', '%admin%')
                ->where('peran_id_str', 'NOT LIKE', '%operator%')
                ->where('peran_id_str', 'NOT LIKE', '%peserta didik%');
        });

        // Query Guru (murni pendidik, mengecualikan tendik)
        $guruQuery = User::where(function ($query) {
            $query->where('peran_id_str', 'LIKE', '%guru%')
                ->orWhere(function ($qGtk) {
                    $qGtk->where(function ($subPeran) {
                        $subPeran->where('peran_id_str', 'LIKE', '%ptk%')
                            ->orWhereNull('peran_id_str');
                    })->whereNotNull('ptk_id')
                        ->whereIn('ptk_id', function ($sub) {
                            $sub->select('ptk_id')->from('gtk')
                                ->where(function ($gSub) {
                                    $gSub->where('jenis_ptk_id_str', 'LIKE', '%Guru%')
                                        ->orWhere('jenis_ptk_id_str', 'LIKE', '%Kepala Sekolah%')
                                        ->orWhereNull('jenis_ptk_id_str');
                                })->where('jenis_ptk_id_str', 'NOT LIKE', '%Tenaga Kependidikan%');
                        });
                });
        })->where(function ($query) {
            $query->where('peran_id_str', 'NOT LIKE', '%admin%')
                ->where('peran_id_str', 'NOT LIKE', '%operator%')
                ->where('peran_id_str', 'NOT LIKE', '%tendik%')
                ->where('peran_id_str', 'NOT LIKE', '%tenaga kependidikan%')
                ->where('peran_id_str', 'NOT LIKE', '%tata usaha%');
        });

        $pesertaDidikQuery = User::where(function ($query) {
            $query->where(function ($q2) {
                $q2->where('peran_id_str', 'LIKE', '%peserta didik%')
                    ->orWhereNotNull('peserta_didik_id');
            })->orWhere(function ($q3) {
                $q3->whereNull('peran_id_str')
                    ->whereNull('ptk_id');
            });
        })->where(function ($query) {
            $query->where('peran_id_str', 'NOT LIKE', '%admin%')
                ->where('peran_id_str', 'NOT LIKE', '%operator%')
                ->where('peran_id_str', 'NOT LIKE', '%guru%')
                ->where('peran_id_str', 'NOT LIKE', '%ptk%')
                ->where('peran_id_str', 'NOT LIKE', '%tendik%');
        });

        // Subquery email resmi peserta didik dari dapodik / identitas
        $subEmail = DB::table('peserta_didik as pd')
            ->leftJoin('peserta_didik_identitas as pdi', 'pd.peserta_didik_id', '=', 'pdi.peserta_didik_id')
            ->where(function ($w) {
                $w->whereColumn('pd.peserta_didik_id', 'pengguna.peserta_didik_id')
                    ->orWhere(function ($w2) {
                        $w2->whereNull('pengguna.peserta_didik_id')
                            ->whereColumn('pd.nisn', 'pengguna.username');
                    });
            })
            ->selectRaw("COALESCE(NULLIF(pdi.email, ''), NULLIF(pd.email, ''), (CASE WHEN pengguna.username LIKE '%@%' THEN pengguna.username ELSE NULL END))")
            ->limit(1);

        $pesertaDidikQuery->select('pengguna.*')->selectSub($subEmail, 'email');

        if ($q !== '') {
            $applySearch = function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nama', 'LIKE', "%{$q}%")
                        ->orWhere('username', 'LIKE', "%{$q}%")
                        ->orWhere('no_hp', 'LIKE', "%{$q}%");
                });
            };
            $applySearch($adminQuery);
            $applySearch($guruQuery);
            $applySearch($tendikQuery);

            $pesertaDidikQuery->where(function ($sub) use ($q) {
                $sub->where('pengguna.nama', 'LIKE', "%{$q}%")
                    ->orWhere('pengguna.username', 'LIKE', "%{$q}%")
                    ->orWhere('pengguna.no_hp', 'LIKE', "%{$q}%")
                    ->orWhereIn('pengguna.peserta_didik_id', function ($pdQ) use ($q) {
                        $pdQ->select('peserta_didik_id')
                            ->from('peserta_didik')
                            ->where('email', 'LIKE', "%{$q}%");
                    })
                    ->orWhereIn('pengguna.peserta_didik_id', function ($pdiQ) use ($q) {
                        $pdiQ->select('peserta_didik_id')
                            ->from('peserta_didik_identitas')
                            ->where('email', 'LIKE', "%{$q}%");
                    });
            });
        }

        $effectiveSort = ($sort === 'email' && $activeTab !== 'peserta_didik') ? 'nama' : $sort;
        $orderFn = function ($query) use ($effectiveSort, $sortDir) {
            $query->orderBy($effectiveSort, $sortDir)->orderBy('nama', 'asc');
        };

        // Tab 5: Tugas Tambahan & Penugasan
        $tugasQuery = DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->leftJoin('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
            ->leftJoin('pengguna as p', 'ptt.user_id', '=', 'p.pengguna_id')
            ->leftJoin('rombongan_belajar as rb', 'ptt.rombel_id', '=', 'rb.rombongan_belajar_id')
            ->select(
                'ptt.id',
                'ptt.user_id',
                'ptt.ptk_id',
                'ptt.nomor_sk',
                'ptt.tmt_tugas',
                'ptt.tst_tugas',
                'ptt.keterangan',
                'ptt.is_active',
                'ptt.jurusan_id',
                'ptt.rombel_id',
                'rtt.kode as tugas_kode',
                'rtt.nama as tugas_nama',
                'rtt.kelompok as tugas_kelompok',
                'rtt.bidang as tugas_bidang',
                'rtt.ekuivalensi_jam',
                'rtt.icon as tugas_icon',
                DB::raw("COALESCE(gtk.nama, p.nama, 'Belum Terhubung') as ptk_nama"),
                DB::raw("COALESCE(gtk.nip, '-') as ptk_nip"),
                'rb.nama as rombel_nama'
            );

        if ($q !== '') {
            $tugasQuery->where(function ($sub) use ($q) {
                $sub->where('gtk.nama', 'LIKE', "%{$q}%")
                    ->orWhere('p.nama', 'LIKE', "%{$q}%")
                    ->orWhere('gtk.nip', 'LIKE', "%{$q}%")
                    ->orWhere('rtt.nama', 'LIKE', "%{$q}%")
                    ->orWhere('rtt.bidang', 'LIKE', "%{$q}%")
                    ->orWhere('rb.nama', 'LIKE', "%{$q}%");
            });
        }

        $counts = [
            'admin'          => (clone $adminQuery)->count(),
            'guru'           => (clone $guruQuery)->count(),
            'tendik'         => (clone $tendikQuery)->count(),
            'peserta_didik'  => (clone $pesertaDidikQuery)->count(),
            'tugas_tambahan' => DB::table('ptk_tugas_tambahan')->count(),
        ];

        // Buat paginator kosong untuk tab yang tidak aktif demi efisiensi query
        $emptyPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
            [], 0, $perPage, 1, [
                'path' => $request->url(),
                'query' => array_merge($request->query(), ['tab' => $activeTab])
            ]
        );

        $admins            = $emptyPaginator;
        $gurus             = $emptyPaginator;
        $tendiks           = $emptyPaginator;
        $pesertaDidiks     = $emptyPaginator;
        $tugasTambahanList = $emptyPaginator;

        // Paginasi hanya query tab yang aktif dengan query string lengkap (memastikan tab konsisten)
        if ($activeTab === 'admin') {
            $admins = tap($adminQuery, $orderFn)->paginate($perPage)->appends(array_merge($request->query(), ['tab' => 'admin']));
            $list   = $admins;
        } elseif ($activeTab === 'guru') {
            $gurus = tap($guruQuery, $orderFn)->paginate($perPage)->appends(array_merge($request->query(), ['tab' => 'guru']));
            $list  = $gurus;
        } elseif ($activeTab === 'tendik') {
            $tendiks = tap($tendikQuery, $orderFn)->paginate($perPage)->appends(array_merge($request->query(), ['tab' => 'tendik']));
            $list    = $tendiks;
        } elseif ($activeTab === 'tugas_tambahan') {
            $tugasTambahanList = $tugasQuery->orderBy('rtt.kelompok')
                ->orderBy('rtt.bidang')
                ->orderBy('ptk_nama')
                ->paginate($perPage)
                ->appends(array_merge($request->query(), ['tab' => 'tugas_tambahan']));
            $list = $tugasTambahanList;
        } else {
            $pesertaDidiks = tap($pesertaDidikQuery, $orderFn)->paginate($perPage)->appends(array_merge($request->query(), ['tab' => 'peserta_didik']));
            $list          = $pesertaDidiks;
        }

        $refTugasList = DB::table('ref_tugas_tambahan')
            ->where('is_active', true)
            ->orderBy('kelompok')
            ->orderBy('bidang')
            ->orderBy('nama')
            ->get();

        $ptkList = DB::table('gtk')->select('ptk_id', 'nama', 'nip', 'jenis_ptk_id_str')->orderBy('nama')->get();
        $rombelList = DB::table('rombongan_belajar')->where('jenis_rombel', '1')->select('rombongan_belajar_id', 'nama')->orderBy('nama')->get();
        // Daftar jurusan untuk penetapan cakupan Laboran/Kaprog.
        // jurusan_meta memuat dua level Dapodik (Program & Konsentrasi Keahlian) dengan
        // nama yang bisa sama, sehingga disertakan jumlah rombel sebagai pembeda.
        $jurusanList = collect();
        if (\App\Support\Table::exists('jurusan_meta')) {
            $rombelPerJurusan = \App\Support\Table::exists('rombongan_belajar')
                ? DB::table('rombongan_belajar')
                    ->whereNotNull('jurusan_id')
                    ->select('jurusan_id', DB::raw('COUNT(*) as jumlah'))
                    ->groupBy('jurusan_id')
                    ->pluck('jumlah', 'jurusan_id')
                : collect();

            $jurusanList = DB::table('jurusan_meta')
                ->select('jurusan_id', 'nama_jurusan', 'singkatan')
                ->orderBy('nama_jurusan')
                ->orderBy('jurusan_id')
                ->get()
                ->each(function ($j) use ($rombelPerJurusan) {
                    $j->jumlah_rombel = (int) ($rombelPerJurusan[$j->jurusan_id] ?? 0);
                });
        }

        return view('dashboard.pengguna', compact(
            'admins',
            'gurus',
            'tendiks',
            'pesertaDidiks',
            'tugasTambahanList',
            'list',
            'refTugasList',
            'ptkList',
            'rombelList',
            'jurusanList',
            'counts',
            'activeTab',
            'q',
            'perPage',
            'sort',
            'sortDir',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete'
        ));
    }

    /**
     * Tambah pengguna baru (Guru, Tendik, Peserta Didik, atau Admin di luar Dapodik)
     */
    public function store(Request $request)
    {
        $authUser = session('user');
        if (!\App\Models\RolePermission::canAccess($authUser, 'menu_pengguna', 'create')) {
            abort(403, 'Akses ditolak: Anda tidak memiliki hak akses untuk menambah data pengguna.');
        }

        $validated = $request->validate([
            'nama'         => 'required|string|max:200',
            'username'     => 'required|string|max:100|unique:pengguna,username',
            'peran_id_str' => 'required|string|max:100',
            'password'     => 'required|string|min:6',
            'no_hp'        => 'nullable|string|max:30',
            'alamat'       => 'nullable|string',
            'rfid_uid'     => 'nullable|string|max:64|unique:pengguna,rfid_uid',
        ], [
            'nama.required'         => 'Nama lengkap wajib diisi.',
            'username.required'     => 'Username / ID login wajib diisi.',
            'username.unique'       => 'Username ini sudah terdaftar. Gunakan username lain.',
            'password.required'     => 'Password awal wajib diisi (minimal 6 karakter).',
            'password.min'          => 'Password minimal 6 karakter.',
            'rfid_uid.unique'       => 'UID Kartu RFID ini sudah digunakan oleh pengguna lain.',
            'peran_id_str.required' => 'Pilih peran / kategori pengguna.',
        ]);

        $rfidUid = !empty($validated['rfid_uid']) ? strtoupper(trim($validated['rfid_uid'])) : null;
        if ($rfidUid) {
            $siswaWithRfid = \App\Models\PesertaDidikMeta::where('rfid_uid', $rfidUid)->first();
            if ($siswaWithRfid) {
                return back()->withErrors(['rfid_uid' => "Kartu RFID UID [{$rfidUid}] sudah digunakan oleh peserta didik (NISN: {$siswaWithRfid->nisn})."])->withInput();
            }
        }

        $sekolahId = DB::table('sekolah')->value('sekolah_id');

        $user = new User();
        $user->pengguna_id = (string) \Illuminate\Support\Str::uuid();
        $user->sekolah_id = $sekolahId;
        $user->nama = trim($validated['nama']);
        $user->username = trim($validated['username']);
        $user->peran_id_str = trim($validated['peran_id_str']);
        $user->password = Hash::make($validated['password']);
        $user->no_hp = $validated['no_hp'] ?? null;
        $user->alamat = $validated['alamat'] ?? null;
        $user->password_updated_at = now();

        if ($rfidUid) {
            $user->rfid_uid = $rfidUid;
            $user->rfid_registered_at = now();
        }

        $user->save();

        // Alihkan ke tab yang relevan dengan peran baru
        $peran = strtolower($user->peran_id_str);
        $tab = 'admin';
        if (str_contains($peran, 'guru')) {
            $tab = 'guru';
        } elseif (str_contains($peran, 'tendik') || str_contains($peran, 'tenaga kependidikan') || str_contains($peran, 'tata usaha')) {
            $tab = 'tendik';
        } elseif (str_contains($peran, 'peserta didik')) {
            $tab = 'peserta_didik';
        }

        return redirect()->route('dashboard.pengguna.index', ['tab' => $tab])
            ->with('success', "Pengguna baru {$user->nama} ({$user->peran_id_str}) berhasil ditambahkan dan siap digunakan untuk login.");
    }

    /**
     * Update pengguna (modal edit)
     */
    public function update(Request $request, string|int $id)
    {
        $authUser = session('user');
        if (!\App\Models\RolePermission::canAccess($authUser, 'menu_pengguna', 'update') && !\App\Models\RolePermission::canAccess($authUser, 'fitur_pengguna_edit')) {
            abort(403, 'Akses ditolak: Anda tidak memiliki hak akses untuk mengubah data pengguna.');
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'nama'          => 'required|string|max:200',
            'username'      => 'required|string|max:100|unique:pengguna,username,' . $id . ',pengguna_id',
            'password'      => 'nullable|string|min:6',
            'peran_id_str'  => 'required|string',
            'no_hp'         => 'nullable|string|max:30',
            'alamat'        => 'nullable|string',
            'rfid_uid'      => 'nullable|string|max:64|unique:pengguna,rfid_uid,' . $id . ',pengguna_id',
        ]);

        $rfidUid = !empty($validated['rfid_uid']) ? strtoupper(trim($validated['rfid_uid'])) : null;
        if ($rfidUid) {
            $siswaWithRfid = \App\Models\PesertaDidikMeta::where('rfid_uid', $rfidUid)->first();
            if ($siswaWithRfid) {
                return back()->withErrors(['rfid_uid' => "Kartu RFID UID [{$rfidUid}] sudah digunakan oleh peserta didik (NISN: {$siswaWithRfid->nisn})."])->withInput();
            }
        }

        $user->nama         = $validated['nama'];
        $user->username     = $validated['username'];
        $user->peran_id_str = $validated['peran_id_str'];
        $user->no_hp        = $validated['no_hp'] ?? null;
        $user->alamat       = $validated['alamat'] ?? null;

        if ($user->rfid_uid !== $rfidUid) {
            $user->rfid_uid = $rfidUid;
            $user->rfid_registered_at = $rfidUid ? now() : null;
        }

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Hapus pengguna
     */
    public function destroy(string|int $id)
    {
        $authUser = session('user');
        if (!\App\Models\RolePermission::canAccess($authUser, 'menu_pengguna', 'delete') && !\App\Models\RolePermission::canAccess($authUser, 'fitur_pengguna_hapus')) {
            abort(403, 'Akses ditolak: Anda tidak memiliki hak akses untuk menghapus data pengguna.');
        }

        $user = User::findOrFail($id);
        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }

    /**
     * Reset password pengguna (Peserta Didik, Guru, Tendik, Administrator)
     */
    public function resetPassword(string|int $id)
    {
        $authUser = session('user');
        if (!\App\Models\RolePermission::canAccess($authUser, 'menu_pengguna', 'update') && !\App\Models\RolePermission::canAccess($authUser, 'fitur_pengguna_reset')) {
            abort(403, 'Akses ditolak: Anda tidak memiliki hak akses untuk mereset password pengguna.');
        }

        $user = User::findOrFail($id);
        $role = $user->role;

        $defaultPassword = 'Sae12345!';
        $resetReason = '';

        if ($role === 'peserta_didik' || str_contains(strtolower($user->peran_id_str ?? ''), 'peserta didik')) {
            $pd = null;
            if ($user->peserta_didik_id) {
                $pd = DB::table('peserta_didik')->where('peserta_didik_id', $user->peserta_didik_id)->first();
            }
            if (!$pd) {
                $pd = DB::table('peserta_didik')->where('nisn', $user->username)->first();
            }

            if ($pd && !empty($pd->nisn)) {
                $defaultPassword = trim($pd->nisn);
                $resetReason = "NISN ({$defaultPassword})";
            } elseif (ctype_digit(trim($user->username))) {
                $defaultPassword = trim($user->username);
                $resetReason = "NISN/Username ({$defaultPassword})";
            } else {
                $defaultPassword = 'PesertaDidik543!';
                $resetReason = "password default ({$defaultPassword})";
            }
        } elseif ($role === 'guru' || str_contains(strtolower($user->peran_id_str ?? ''), 'guru')) {
            $gtk = null;
            if ($user->ptk_id) {
                $gtk = DB::table('gtk')->where('ptk_id', $user->ptk_id)->first();
            }
            if (!$gtk) {
                $gtk = DB::table('gtk')->where('nip', $user->username)->orWhere('nuptk', $user->username)->first();
            }

            if ($gtk && !empty($gtk->nip)) {
                $defaultPassword = trim($gtk->nip);
                $resetReason = "NIP ({$defaultPassword})";
            } else {
                $defaultPassword = 'Geteka543!';
                $resetReason = "password default ({$defaultPassword})";
            }
        } elseif ($role === 'tendik' || str_contains(strtolower($user->peran_id_str ?? ''), 'tendik') || str_contains(strtolower($user->peran_id_str ?? ''), 'tenaga kependidikan')) {
            $gtk = null;
            if ($user->ptk_id) {
                $gtk = DB::table('gtk')->where('ptk_id', $user->ptk_id)->first();
            }
            if (!$gtk) {
                $gtk = DB::table('gtk')->where('nip', $user->username)->orWhere('nuptk', $user->username)->first();
            }

            if ($gtk && !empty($gtk->nip)) {
                $defaultPassword = trim($gtk->nip);
                $resetReason = "NIP ({$defaultPassword})";
            } else {
                $defaultPassword = 'Geteka543!';
                $resetReason = "password default ({$defaultPassword})";
            }
        } else {
            $defaultPassword = 'Admin543!';
            $resetReason = "password default ({$defaultPassword})";
        }

        $user->password = Hash::make($defaultPassword);
        $user->password_updated_at = null;
        $raw = !empty($user->raw_data) ? (json_decode($user->raw_data, true) ?: []) : [];
        unset($raw['password_updated_at'], $raw['is_password_updated']);
        $user->raw_data = !empty($raw) ? json_encode($raw) : null;
        $user->save();

        $msg = "Password akun {$user->nama} berhasil direset ke {$resetReason}.";

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Simpan penugasan tugas tambahan PTK dari Manajemen Pengguna
     */
    public function storeTugasTambahan(Request $request): \Illuminate\Http\JsonResponse
    {
        $authUser = session('user');
        if (!\App\Models\RolePermission::canAccess($authUser, 'menu_pengguna', 'create')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak: Anda tidak memiliki hak akses.'], 403);
        }

        $request->validate([
            'ptk_id' => 'required|string',
            'tugas_tambahan_id' => 'required|integer|exists:ref_tugas_tambahan,id',
            'nomor_sk' => 'nullable|string|max:100',
            'tmt_tugas' => 'nullable|date',
            'tst_tugas' => 'nullable|date',
            'rombel_id' => 'nullable|string',
            'jurusan_id' => 'nullable|string|max:50',
            'keterangan' => 'nullable|string',
        ]);

        $ptkId = $request->input('ptk_id');
        $tugasId = (int) $request->input('tugas_tambahan_id');
        $rombelId = $request->input('rombel_id');
        $jurusanId = $request->input('jurusan_id');

        $tugas = DB::table('ref_tugas_tambahan')->where('id', $tugasId)->first();
        if (!$tugas) {
            return response()->json(['status' => 'error', 'message' => 'Tugas tambahan tidak ditemukan.'], 422);
        }

        // 1. Cek apakah PTK ini sudah mengemban tugas tambahan yang sama
        $existingPtkDuty = \App\Models\PtkTugasTambahan::where('ptk_id', $ptkId)
            ->where('tugas_tambahan_id', $tugasId)
            ->where('is_active', true)
            ->first();

        if ($existingPtkDuty) {
            return response()->json([
                'status' => 'error',
                'message' => "Personel ini sudah memiliki tugas tambahan sebagai {$tugas->nama}.",
            ], 422);
        }

        // 2. Khusus Wali Kelas: Validasi kelas tidak boleh memiliki lebih dari 1 wali kelas aktif
        if ($tugas->kode === 'WALI_KELAS') {
            if (empty($rombelId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Silakan pilih rombongan belajar (kelas) untuk penugasan Wali Kelas.',
                ], 422);
            }

            $existingClassWali = \App\Models\PtkTugasTambahan::where('tugas_tambahan_id', $tugasId)
                ->where('rombel_id', $rombelId)
                ->where('is_active', true)
                ->first();

            if ($existingClassWali) {
                $ptkName = DB::table('gtk')->where('ptk_id', $existingClassWali->ptk_id)->value('nama') ?? 'Guru lain';
                return response()->json([
                    'status' => 'error',
                    'message' => "Kelas tersebut sudah memiliki Wali Kelas aktif ({$ptkName}).",
                ], 422);
            }
        }

        // 3. Khusus Jabatan Tunggal (Waka, Kepala TAS, Operator): hanya 1 orang aktif di sekolah
        $singleRoles = ['WAKA_KURIKULUM', 'WAKA_KESISWAAN', 'WAKA_HUBIN', 'WAKA_SARPRAS', 'KEPALA_TAS', 'OPERATOR_DAPODIK'];
        if (in_array($tugas->kode, $singleRoles, true)) {
            $existingSingle = \App\Models\PtkTugasTambahan::where('tugas_tambahan_id', $tugasId)
                ->where('is_active', true)
                ->first();

            if ($existingSingle) {
                $occupantName = DB::table('gtk')->where('ptk_id', $existingSingle->ptk_id)->value('nama')
                    ?? (DB::table('pengguna')->where('ptk_id', $existingSingle->ptk_id)->value('nama') ?? 'Personel lain');
                return response()->json([
                    'status' => 'error',
                    'message' => "Jabatan {$tugas->nama} saat ini sudah dijabat oleh {$occupantName}.",
                ], 422);
            }
        }

        $userId = DB::table('pengguna')->where('ptk_id', $ptkId)->value('pengguna_id');

        \App\Models\PtkTugasTambahan::create([
            'user_id' => $userId,
            'ptk_id' => $ptkId,
            'tugas_tambahan_id' => $tugasId,
            'nomor_sk' => $request->input('nomor_sk'),
            'tmt_tugas' => $request->input('tmt_tugas'),
            'tst_tugas' => $request->input('tst_tugas'),
            'rombel_id' => $rombelId,
            'jurusan_id' => $jurusanId,
            'keterangan' => $request->input('keterangan'),
            'is_active' => true,
        ]);

        \App\Models\RolePermission::clearRuntimeCache();

        return response()->json([
            'status' => 'success',
            'message' => 'Penugasan tugas tambahan berhasil disimpan.',
        ]);
    }

    /**
     * Ubah penugasan tugas tambahan (termasuk penetapan JURUSAN untuk Laboran/Kaprog).
     */
    public function updateTugasTambahan(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $authUser = session('user');
        if (!\App\Models\RolePermission::canAccess($authUser, 'menu_pengguna', 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak: Anda tidak memiliki hak akses.'], 403);
        }

        $duty = \App\Models\PtkTugasTambahan::find($id);
        if (!$duty) {
            return response()->json(['status' => 'error', 'message' => 'Penugasan tidak ditemukan.'], 404);
        }

        $request->validate([
            'jurusan_id'      => 'nullable|string|max:50',
            'rombel_id'       => 'nullable|string',
            'nomor_sk'        => 'nullable|string|max:100',
            'tmt_tugas'       => 'nullable|date',
            'tst_tugas'       => 'nullable|date',
            'keterangan'      => 'nullable|string',
            'is_active'       => 'nullable|boolean',
        ]);

        $ref = DB::table('ref_tugas_tambahan')->where('id', $duty->tugas_tambahan_id)->first();
        $kode = $ref->kode ?? null;

        // Validasi khusus Wali Kelas: satu kelas hanya boleh 1 wali aktif
        $rombelId = $request->input('rombel_id');

        // Wali Kelas: jurusan diturunkan dari rombel (bukan diisi manual), karena
        // jurusan pada baris WALI_KELAS berasal dari sinkronisasi Dapodik.
        $jurusanDariRombel = null;
        if ($kode === 'WALI_KELAS' && $rombelId) {
            $jurusanDariRombel = DB::table('rombongan_belajar')
                ->where('rombongan_belajar_id', $rombelId)
                ->value('jurusan_id');
        }

        if ($kode === 'WALI_KELAS' && $rombelId) {
            $existing = \App\Models\PtkTugasTambahan::where('tugas_tambahan_id', $duty->tugas_tambahan_id)
                ->where('rombel_id', $rombelId)
                ->where('is_active', true)
                ->where('id', '<>', $duty->id)
                ->first();

            if ($existing) {
                $ptkName = DB::table('gtk')->where('ptk_id', $existing->ptk_id)->value('nama') ?? 'Guru lain';

                return response()->json([
                    'status'  => 'error',
                    'message' => "Kelas tersebut sudah memiliki Wali Kelas aktif ({$ptkName}).",
                ], 422);
            }
        }

        $duty->update([
            // WALI_KELAS: jurusan mengikuti rombel (sumbernya sinkronisasi Dapodik),
            // jadi tidak boleh dihapus/diubah dari form. Tugas lain (LABORAN/KAPROG/
            // KEPALA_LAB): jurusan dapat diubah admin, termasuk dikosongkan.
            'jurusan_id' => $kode === 'WALI_KELAS'
                ? ($jurusanDariRombel ?? $duty->jurusan_id)
                : ($request->has('jurusan_id') ? ($request->input('jurusan_id') ?: null) : $duty->jurusan_id),
            'rombel_id'  => $request->has('rombel_id') ? ($rombelId ?: null) : $duty->rombel_id,
            'nomor_sk'   => $request->input('nomor_sk', $duty->nomor_sk),
            'tmt_tugas'  => $request->input('tmt_tugas', $duty->tmt_tugas),
            'tst_tugas'  => $request->input('tst_tugas', $duty->tst_tugas),
            'keterangan' => $request->input('keterangan', $duty->keterangan),
            'is_active'  => $request->has('is_active') ? (bool) $request->input('is_active') : $duty->is_active,
        ]);

        \App\Models\RolePermission::clearRuntimeCache();

        // Cakupan jurusan laboran berubah -> bersihkan cache scope
        if (class_exists(\App\Support\LaboranScope::class)) {
            \App\Support\LaboranScope::flush();
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Penugasan tugas tambahan berhasil diperbarui.',
        ]);
    }

    /**
     * Hapus penugasan tugas tambahan
     */
    public function destroyTugasTambahan(int $id): \Illuminate\Http\JsonResponse
    {
        $authUser = session('user');
        if (!\App\Models\RolePermission::canAccess($authUser, 'menu_pengguna', 'delete')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak: Anda tidak memiliki hak akses.'], 403);
        }

        \App\Models\PtkTugasTambahan::where('id', $id)->delete();
        \App\Models\RolePermission::clearRuntimeCache();

        return response()->json([
            'status' => 'success',
            'message' => 'Penugasan tugas tambahan berhasil dihapus.',
        ]);
    }

    /**
     * Pemicu sinkronisasi manual Wali Kelas dari Dapodik
     */
    public function syncWaliKelas(): \Illuminate\Http\JsonResponse
    {
        $authUser = session('user');
        if (!\App\Models\RolePermission::canAccess($authUser, 'menu_pengguna', 'update') && !\App\Models\RolePermission::canAccess($authUser, 'fitur_pengguna_edit')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak: Anda tidak memiliki hak akses.'], 403);
        }

        $count = \App\Models\PtkTugasTambahan::syncWaliKelasFromDapodik();
        \App\Models\RolePermission::clearRuntimeCache();

        return response()->json([
            'status' => 'success',
            'message' => "Sinkronisasi Wali Kelas dari Dapodik berhasil ({$count} rombel tersinkron).",
        ]);
    }
}
