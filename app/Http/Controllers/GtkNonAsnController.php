<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GtkGabunganService;
use App\Services\SimpleXlsxExporter;
use App\Services\SimpleXlsxReader;
use App\Support\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Modul GTK Non-ASN — data GTK yang diinput di luar Dapodik.
 *
 * Disimpan di tabel `gtk_non_asn` (bukan `gtk`) agar tidak terhapus saat
 * tarik data Dapodik. Data ini ikut dihitung pada daftar Guru/Tendik.
 */
class GtkNonAsnController extends Controller
{
    private const MENU = 'menu_guru_aktif';

    private const SORTABLE = [
        'kode', 'nama', 'jenis_kelamin', 'jenis_ptk', 'jabatan',
        'status_kepegawaian', 'pendidikan_terakhir', 'status',
    ];

    /** Kolom yang boleh diisi dari form / import. */
    private const KOLOM = [
        'nama', 'gelar_depan', 'gelar_belakang', 'jenis_kelamin', 'nik', 'nuptk', 'nip',
        'jenis_ptk', 'jabatan', 'status_kepegawaian', 'pendidikan_terakhir', 'bidang_studi',
        'tempat_lahir', 'tanggal_lahir', 'agama', 'email', 'no_hp', 'alamat',
        'tmt', 'status', 'keterangan',
    ];

    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!\App\Models\RolePermission::canAccess($user, self::MENU)) {
            return redirect()->route('dashboard.' . ($role ?: 'peserta_didik'))
                ->with('error', 'Akses ke menu GTK Non-ASN dinonaktifkan oleh Administrator.');
        }

        $canUpdate = \App\Models\RolePermission::canAccess($user, self::MENU, 'update');
        $canCreate = \App\Models\RolePermission::canAccess($user, self::MENU, 'create');
        $canDelete = \App\Models\RolePermission::canAccess($user, self::MENU, 'delete');

        $q       = trim($request->get('q', ''));
        $jenis   = trim($request->get('jenis', ''));
        $status  = trim($request->get('status', ''));
        $gender  = trim($request->get('gender', ''));
        $perPage = (int) $request->get('perPage', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) $perPage = 15;

        $sort    = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'nama';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $tersedia = Table::exists('gtk_non_asn');
        $list = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage, 1, [
            'path' => $request->url(), 'query' => $request->query(),
        ]);
        $summary = ['total' => 0, 'aktif' => 0, 'laki' => 0, 'perempuan' => 0, 'punya_akun' => 0];
        $filterJenis = collect();

        if ($tersedia) {
            $filterJenis = DB::table('gtk_non_asn')
                ->whereNotNull('jenis_ptk')->where('jenis_ptk', '<>', '')
                ->distinct()->pluck('jenis_ptk')->sort()->values();

            $query = DB::table('gtk_non_asn');

            if ($q !== '') {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nama', 'LIKE', "%{$q}%")
                        ->orWhere('kode', 'LIKE', "%{$q}%")
                        ->orWhere('nik', 'LIKE', "%{$q}%")
                        ->orWhere('nuptk', 'LIKE', "%{$q}%")
                        ->orWhere('nip', 'LIKE', "%{$q}%")
                        ->orWhere('jabatan', 'LIKE', "%{$q}%")
                        ->orWhere('email', 'LIKE', "%{$q}%")
                        ->orWhere('username', 'LIKE', "%{$q}%");
                });
            }
            if ($jenis !== '') $query->where('jenis_ptk', $jenis);
            if ($status !== '') $query->where('status', $status);
            if ($gender !== '') $query->where('jenis_kelamin', $gender);

            $all = $query->get();

            $summary = [
                'total'      => $all->count(),
                'aktif'      => $all->where('status', 'aktif')->count(),
                'laki'       => $all->where('jenis_kelamin', 'L')->count(),
                'perempuan'  => $all->where('jenis_kelamin', 'P')->count(),
                'punya_akun' => $all->filter(fn ($r) => !empty($r->pengguna_id))->count(),
            ];

            $sorted = $all->sortBy(fn ($item) => $item->{$sort} ?? '', SORT_REGULAR, $sortDir === 'desc');

            $currentPage = (int) $request->get('page', 1);
            $itemsForPage = $sorted->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $list = new \Illuminate\Pagination\LengthAwarePaginator(
                $itemsForPage, $all->count(), $perPage, $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return view('dashboard.manajemen-data.gtk-non-asn', compact(
            'list', 'summary', 'filterJenis', 'q', 'jenis', 'status', 'gender',
            'perPage', 'sort', 'sortDir', 'canUpdate', 'canCreate', 'canDelete', 'tersedia'
        ));
    }

    /**
     * Simpan data Non-ASN baru.
     */
    public function store(Request $request)
    {
        if (!$this->boleh('create')) {
            return back()->with('error', 'Tidak berwenang menambah data GTK Non-ASN.');
        }
        if (!Table::exists('gtk_non_asn')) {
            return back()->with('error', 'Tabel gtk_non_asn belum tersedia. Jalankan migrasi.');
        }

        $data = $this->validasi($request);

        $data['kode'] = $this->kodeBaru();
        $data['status_kepegawaian'] = $data['status_kepegawaian'] ?: 'Non-ASN';
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table('gtk_non_asn')->insert($data);

        return back()->with('success', 'Data GTK Non-ASN berhasil ditambahkan.');
    }

    /**
     * Perbarui data Non-ASN.
     */
    public function update(Request $request, string|int $id)
    {
        if (!$this->boleh('update')) {
            return back()->with('error', 'Tidak berwenang mengubah data GTK Non-ASN.');
        }

        $row = DB::table('gtk_non_asn')->where('id', $id)->first();
        if (!$row) {
            return back()->with('error', 'Data GTK Non-ASN tidak ditemukan.');
        }

        $data = $this->validasi($request);
        $data['updated_at'] = now();

        DB::table('gtk_non_asn')->where('id', $id)->update($data);

        return back()->with('success', 'Data GTK Non-ASN berhasil diperbarui.');
    }

    /**
     * Hapus data Non-ASN (akun login tetap ada, hanya dilepas tautannya).
     */
    public function destroy(string|int $id)
    {
        if (!$this->boleh('delete')) {
            return back()->with('error', 'Tidak berwenang menghapus data GTK Non-ASN.');
        }

        DB::table('gtk_non_asn')->where('id', $id)->delete();

        return back()->with('success', 'Data GTK Non-ASN berhasil dihapus.');
    }

    /**
     * Buat akun login (tabel pengguna) untuk satu data Non-ASN.
     *
     * Akun ditandai `sumber_data = 'manual'` agar tidak ikut disinkronkan Dapodik.
     */
    public function buatAkun(string|int $id): JsonResponse
    {
        if (!$this->boleh('create')) {
            return response()->json(['status' => 'error', 'message' => 'Tidak berwenang membuat akun.'], 403);
        }

        $row = DB::table('gtk_non_asn')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['status' => 'error', 'message' => 'Data GTK Non-ASN tidak ditemukan.'], 404);
        }

        if (!empty($row->pengguna_id) && User::where('pengguna_id', $row->pengguna_id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun login untuk GTK ini sudah ada (username: ' . $row->username . ').',
            ], 422);
        }

        try {
            $akun = $this->buatkanAkun($row);

            DB::table('gtk_non_asn')->where('id', $row->id)->update([
                'pengguna_id' => $akun['pengguna_id'],
                'username' => $akun['username'],
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal membuat akun: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Akun login dibuat. Username: ' . $akun['username'] . ' • Password awal: ' . $akun['password_awal'],
            'data' => $akun,
        ]);
    }

    /**
     * Unduh template import GTK Non-ASN.
     */
    public function downloadTemplate()
    {
        if (!$this->boleh('read')) {
            abort(403, 'Akses ditolak.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'tpl_nonasn_') . '.xlsx';

        $headers = [
            'nama', 'gelar_depan', 'gelar_belakang', 'jenis_kelamin', 'nik', 'nuptk', 'nip',
            'jenis_ptk', 'jabatan', 'status_kepegawaian', 'pendidikan_terakhir', 'bidang_studi',
            'tempat_lahir', 'tanggal_lahir', 'agama', 'email', 'no_hp', 'alamat',
            'tmt', 'status',
        ];

        $sampleRows = [
            [
                'Budi Santoso', 'Drs.', 'S.Pd.', 'L', '3203180201900001', '', '',
                'Guru', 'Guru Mata Pelajaran', 'Non-ASN', 'S1', 'Matematika',
                'Cianjur', '1990-02-18', 'Islam', 'budi@sekolah.sch.id', '081234567890',
                'Jl. Raya Pasirpari', '2024-07-01', 'aktif',
            ],
            [
                'Siti Aminah', '', 'S.Kom.', 'P', '3203180201910002', '', '',
                'Tenaga Kependidikan', 'Staf Tata Usaha', 'Non-ASN', 'S1', 'Administrasi',
                'Cianjur', '1991-05-10', 'Islam', 'siti@sekolah.sch.id', '081298765432',
                'Jl. Raya Pagelaran', '2023-01-02', 'aktif',
            ],
        ];

        SimpleXlsxExporter::export($tempPath, [], $headers, $sampleRows, [
            'sheetName' => 'GTK Non-ASN',
            'rawTable' => true,
            'headerBg' => '#4338CA',
        ]);

        return response()->download($tempPath, 'Template_GTK_Non_ASN.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Import GTK Non-ASN dari Excel/CSV, opsional sekaligus membuat akun login.
     */
    public function import(Request $request): JsonResponse
    {
        if (!$this->boleh('create')) {
            return response()->json(['status' => 'error', 'message' => 'Tidak berwenang mengimpor data.'], 403);
        }

        if (!Table::exists('gtk_non_asn')) {
            return response()->json(['status' => 'error', 'message' => 'Tabel gtk_non_asn belum tersedia. Jalankan migrasi.'], 500);
        }

        $request->validate([
            'file_excel' => 'required|file|max:30720',
        ], [
            'file_excel.required' => 'Berkas Excel wajib dipilih.',
            'file_excel.max' => 'Ukuran berkas maksimal 30 MB.',
        ]);

        $file = $request->file('file_excel');
        if (!in_array(strtolower($file->getClientOriginalExtension()), ['xlsx', 'xls', 'csv', 'txt'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Format tidak didukung. Gunakan Excel (.xlsx/.xls) atau CSV.',
            ], 422);
        }

        try {
            $rows = SimpleXlsxReader::readFile($file->getRealPath());
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal membaca berkas: ' . $e->getMessage()], 422);
        }

        if (empty($rows)) {
            return response()->json(['status' => 'error', 'message' => 'Berkas kosong atau baris data tidak terdeteksi.'], 422);
        }

        $buatAkun = $request->boolean('buat_akun');
        $skipDuplikat = !$request->boolean('izinkan_duplikat');

        $masuk = 0; $dilewati = 0; $akunDibuat = 0;
        $galat = [];

        foreach ($rows as $index => $row) {
            $baris = $index + 2;

            $nama = $this->ambil($row, ['nama', 'nama_lengkap', 'nama_gtk', 'nama_pegawai']);
            if ($nama === '') {
                $dilewati++;
                $galat[] = "Baris {$baris}: nama kosong, dilewati.";
                continue;
            }

            $jenisKelamin = strtoupper(substr($this->ambil($row, ['jenis_kelamin', 'jk', 'l_p', 'gender']), 0, 1));
            $nik = $this->ambil($row, ['nik']);
            $nuptk = $this->ambil($row, ['nuptk']);
            $nip = $this->ambil($row, ['nip']);

            if ($skipDuplikat) {
                $dupe = DB::table('gtk_non_asn')
                    ->where('nama', $nama)
                    ->where(function ($sub) use ($nik, $nuptk, $nip) {
                        if ($nik !== '') $sub->orWhere('nik', $nik);
                        if ($nuptk !== '') $sub->orWhere('nuptk', $nuptk);
                        if ($nip !== '') $sub->orWhere('nip', $nip);
                    })
                    ->exists();

                if ($dupe) {
                    $dilewati++;
                    $galat[] = "Baris {$baris}: {$nama} sudah ada, dilewati.";
                    continue;
                }
            }

            $status = strtolower($this->ambil($row, ['status'])) ?: 'aktif';
            $status = in_array($status, ['aktif', 'tidak_aktif', 'tidak aktif', 'nonaktif'], true)
                ? (str_starts_with($status, 'aktif') ? 'aktif' : 'tidak_aktif')
                : 'aktif';

            $data = [
                'kode' => $this->kodeBaru(),
                'nama' => $nama,
                'gelar_depan' => $this->nullkan($this->ambil($row, ['gelar_depan'])),
                'gelar_belakang' => $this->nullkan($this->ambil($row, ['gelar_belakang'])),
                'jenis_kelamin' => in_array($jenisKelamin, ['L', 'P'], true) ? $jenisKelamin : null,
                'nik' => $this->nullkan($nik),
                'nuptk' => $this->nullkan($nuptk),
                'nip' => $this->nullkan($nip),
                'jenis_ptk' => $this->nullkan($this->ambil($row, ['jenis_ptk', 'jenis_gtk', 'kategori'])),
                'jabatan' => $this->nullkan($this->ambil($row, ['jabatan', 'jabatan_ptk'])),
                'status_kepegawaian' => $this->nullkan($this->ambil($row, ['status_kepegawaian'])) ?: 'Non-ASN',
                'pendidikan_terakhir' => $this->nullkan($this->ambil($row, ['pendidikan_terakhir', 'pendidikan'])),
                'bidang_studi' => $this->nullkan($this->ambil($row, ['bidang_studi', 'mapel', 'bidang_keahlian'])),
                'tempat_lahir' => $this->nullkan($this->ambil($row, ['tempat_lahir'])),
                'tanggal_lahir' => $this->nullkan($this->ambil($row, ['tanggal_lahir', 'tgl_lahir'])),
                'agama' => $this->nullkan($this->ambil($row, ['agama'])),
                'email' => $this->nullkan($this->ambil($row, ['email', 'e_mail'])),
                'no_hp' => $this->nullkan($this->ambil($row, ['no_hp', 'hp', 'no_telepon', 'telepon'])),
                'alamat' => $this->nullkan($this->ambil($row, ['alamat', 'alamat_jalan'])),
                'tmt' => $this->nullkan($this->ambil($row, ['tmt', 'tanggal_mulai_tugas'])),
                'status' => $status,
                'keterangan' => $this->nullkan($this->ambil($row, ['keterangan', 'catatan'])),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $id = DB::table('gtk_non_asn')->insertGetId($data);
            $masuk++;

            if ($buatAkun) {
                try {
                    $record = DB::table('gtk_non_asn')->where('id', $id)->first();
                    $akun = $this->buatkanAkun($record);
                    DB::table('gtk_non_asn')->where('id', $id)->update([
                        'pengguna_id' => $akun['pengguna_id'],
                        'username' => $akun['username'],
                        'updated_at' => now(),
                    ]);
                    $akunDibuat++;
                } catch (\Throwable $e) {
                    $galat[] = "Baris {$baris}: data masuk, akun gagal dibuat ({$e->getMessage()}).";
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Import selesai: {$masuk} data masuk, {$dilewati} dilewati, {$akunDibuat} akun dibuat.",
            'summary' => [
                'masuk' => $masuk,
                'dilewati' => $dilewati,
                'akun_dibuat' => $akunDibuat,
                'galat' => $galat,
            ],
        ]);
    }

    /* ------------------------------------------------------------------ */

    private function boleh(string $aksi): bool
    {
        return \App\Models\RolePermission::canAccess(session('user'), self::MENU, $aksi);
    }

    private function validasi(Request $request): array
    {
        $validated = $request->validate([
            'nama'                 => 'required|string|max:200',
            'gelar_depan'          => 'nullable|string|max:50',
            'gelar_belakang'       => 'nullable|string|max:100',
            'jenis_kelamin'        => 'nullable|in:L,P',
            'nik'                  => 'nullable|string|max:30',
            'nuptk'                => 'nullable|string|max:30',
            'nip'                  => 'nullable|string|max:30',
            'jenis_ptk'            => 'nullable|string|max:100',
            'jabatan'              => 'nullable|string|max:200',
            'status_kepegawaian'   => 'nullable|string|max:100',
            'pendidikan_terakhir'  => 'nullable|string|max:50',
            'bidang_studi'         => 'nullable|string|max:200',
            'tempat_lahir'         => 'nullable|string|max:100',
            'tanggal_lahir'        => 'nullable|string|max:30',
            'agama'                => 'nullable|string|max:50',
            'email'                => 'nullable|email|max:100',
            'no_hp'                => 'nullable|string|max:30',
            'alamat'               => 'nullable|string|max:500',
            'tmt'                  => 'nullable|string|max:30',
            'status'               => 'nullable|in:aktif,tidak_aktif',
            'keterangan'           => 'nullable|string|max:500',
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $validated['status'] = $validated['status'] ?? 'aktif';

        return array_intersect_key($validated, array_flip(self::KOLOM));
    }

    /**
     * Buat akun login di tabel pengguna untuk satu baris Non-ASN.
     *
     * @return array{pengguna_id: string, username: string, password_awal: string}
     */
    private function buatkanAkun(object $row): array
    {
        $peran = $this->peranUntuk($row);

        // Username: NIP → NIK → email → turunan nama
        $kandidat = array_filter([
            trim((string) ($row->nip ?? '')),
            trim((string) ($row->nik ?? '')),
            trim((string) ($row->email ?? '')),
        ]);

        $dasar = $kandidat[0] ?? Str::slug($row->nama, '.') ?: 'nonasn';

        $username = $dasar;
        $i = 1;
        while (User::where('username', $username)->exists()) {
            $username = $dasar . ($i > 1 ? $i : '_' . $i);
            $i++;
        }

        $passwordAwal = Str::password(10, symbols: false);

        $penggunaId = (string) Str::uuid();

        DB::table('pengguna')->insert([
            'pengguna_id' => $penggunaId,
            'sekolah_id' => DB::table('sekolah')->value('sekolah_id'),
            'username' => $username,
            'nama' => $row->nama,
            'peran_id_str' => $peran,
            'password' => Hash::make($passwordAwal),
            'no_hp' => $row->no_hp,
            'alamat' => $row->alamat,
            'ptk_id' => null,
            'peserta_didik_id' => null,
            'password_updated_at' => now(),
            // Wajib 'manual': FeederReceiverController mengecualikan akun manual/seed
            // dari sinkronisasi, sehingga akun Non-ASN tidak tertimpa Dapodik.
            'sumber_data' => 'manual',
            'raw_data' => json_encode([
                'gtk_non_asn_id' => $row->id,
                'kode' => $row->kode,
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'pengguna_id' => $penggunaId,
            'username' => $username,
            'password_awal' => $passwordAwal,
        ];
    }

    /**
     * Tentukan peran akun dari jenis PTK Non-ASN.
     */
    private function peranUntuk(object $row): string
    {
        $jenis = strtolower((string) ($row->jenis_ptk ?? ''));

        if (str_contains($jenis, 'guru') || str_contains($jenis, 'kepala sekolah')) {
            return 'Guru';
        }

        if (str_contains($jenis, 'tenaga kependidikan') || $jenis !== '') {
            return 'Tenaga Kependidikan';
        }

        return 'Tenaga Kependidikan';
    }

    private function kodeBaru(): string
    {
        $urutan = (int) DB::table('gtk_non_asn')->count() + 1;

        do {
            $kode = 'NONASN-' . str_pad((string) $urutan, 4, '0', STR_PAD_LEFT);
            $urutan++;
        } while (DB::table('gtk_non_asn')->where('kode', $kode)->exists());

        return $kode;
    }

    private function ambil(array $row, array $kunci): string
    {
        foreach ($kunci as $k) {
            $clean = preg_replace('/_+/', '_', trim(preg_replace('/[^a-z0-9_]/', '_', preg_replace('/\s+/', '_', strtolower($k))), '_'));
            foreach ([$k, $clean] as $key) {
                if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
                    return trim((string) $row[$key]);
                }
            }
        }

        return '';
    }

    private function nullkan(string $nilai): ?string
    {
        return $nilai === '' ? null : $nilai;
    }
}
