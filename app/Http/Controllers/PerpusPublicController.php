<?php

namespace App\Http\Controllers;

use App\Models\PerpusAnggota;
use App\Models\User;
use App\Services\PerpustakaanIdentitasService;
use App\Services\PerpustakaanMemberService;
use App\Services\SaeKredensialService;
use App\Support\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Halaman publik & area member Perpustakaan Digital.
 * -------------------------------------------------------------------------
 * Tidak memakai guard Laravel: identitas anggota disimpan di sesi
 * `perpus_anggota_id`. Semua query riwayat WAJIB lewat
 * PerpustakaanIdentitasService agar terisolasi per anggota (anti-bocor data).
 */
class PerpusPublicController extends Controller
{
    private const SESSION_KEY = 'perpus_anggota_id';

    // =====================================================================
    // LANDING PUBLIK (KIOSK)
    // =====================================================================

    public function index(Request $request)
    {
        // Anggota yang sudah punya sesi langsung ke portal.
        if ($this->currentMember()) {
            return redirect()->route('perpustakaan.portal');
        }

        // Auto-login: hanya untuk yang SUDAH menjadi anggota.
        if ($member = $this->autoLoginFromSaeSession($request)) {
            return redirect()->route('perpustakaan.portal')
                ->with('success', 'Selamat datang kembali, ' . $member->nama . '.');
        }

        $q = trim((string) $request->query('q', ''));
        $kategori = trim((string) $request->query('kategori', ''));

        $hasKoleksi = Table::exists('perpus_koleksi_buku');
        $query = $hasKoleksi ? DB::table('perpus_koleksi_buku') : null;

        $buku = collect();
        $kategoriList = collect();
        $totalJudul = 0;
        $totalEksemplar = 0;
        $totalTersedia = 0;
        $totalDipinjam = 0;

        if ($query) {
            $totalJudul = (int) (clone $query)->count();
            $totalEksemplar = (int) ((clone $query)->sum('jumlah_eksemplar') ?: 0);
            $totalTersedia = (int) ((clone $query)->sum('eksemplar_tersedia') ?: 0);
            $kategoriList = (clone $query)->distinct()->pluck('kategori')->filter()->sort()->values();
            $totalDipinjam = $totalEksemplar - $totalTersedia;

            if ($kategori !== '') {
                $query->where('kategori', $kategori);
            }
            if ($q !== '') {
                $query->where(function ($qb) use ($q) {
                    $qb->where('judul', 'like', "%{$q}%")
                        ->orWhere('penulis', 'like', "%{$q}%")
                        ->orWhere('penerbit', 'like', "%{$q}%")
                        ->orWhere('isbn', 'like', "%{$q}%");
                });
            }

            $buku = $query->orderBy('judul')->paginate(12)->withQueryString();
        }

        $stats = [
            'judul'          => $totalJudul,
            'eksemplar'      => $totalEksemplar,
            'tersedia'       => $totalTersedia,
            'dipinjam'       => max(0, $totalDipinjam),
            'anggota'        => Table::exists('perpus_anggota') ? (int) DB::table('perpus_anggota')->where('is_active', true)->count() : 0,
            'kunjungan_hari' => Table::exists('perpus_kunjungan') ? (int) DB::table('perpus_kunjungan')->whereDate('tanggal', now()->toDateString())->count() : 0,
            'pinjam_hari'    => Table::exists('perpus_sirkulasi') ? (int) DB::table('perpus_sirkulasi')->whereDate('tgl_pinjam', now()->toDateString())->count() : 0,
        ];

        $tren = ['labels' => [], 'kunjungan' => [], 'pinjam' => []];
        for ($i = 5; $i >= 0; $i--) {
            $start = now()->copy()->subMonths($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $tren['labels'][] = $start->translatedFormat('M Y');
            $tren['kunjungan'][] = Table::exists('perpus_kunjungan')
                ? (int) DB::table('perpus_kunjungan')->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])->count()
                : 0;
            $tren['pinjam'][] = Table::exists('perpus_sirkulasi')
                ? (int) DB::table('perpus_sirkulasi')->whereBetween('tgl_pinjam', [$start->toDateString(), $end->toDateString()])->count()
                : 0;
        }

        $populer = collect();
        if (Table::exists('perpus_sirkulasi') && $hasKoleksi) {
            $populer = DB::table('perpus_sirkulasi as s')
                ->join('perpus_koleksi_buku as b', 's.buku_id', '=', 'b.id')
                ->select('b.judul', 'b.penulis', DB::raw('COUNT(s.id) as total'))
                ->groupBy('b.id', 'b.judul', 'b.penulis')
                ->orderByDesc('total')
                ->limit(5)
                ->get();
        }

        $terbaru = $hasKoleksi
            ? DB::table('perpus_koleksi_buku')->orderByDesc('id')->limit(5)->get()
            : collect();

        $kategoriChart = $hasKoleksi
            ? DB::table('perpus_koleksi_buku')
                ->select('kategori', DB::raw('COUNT(id) as total'))
                ->whereNotNull('kategori')
                ->groupBy('kategori')
                ->orderByDesc('total')
                ->limit(6)
                ->get()
            : collect();

        $running_info = $this->runningInfo();

        // Status keanggotaan bagi pengunjung yang sudah login SAE tapi belum jadi anggota.
        $saeUser = session('user');
        $bisaAktifkan = false;
        if ($saeUser && !$this->currentMember()) {
            $role = is_array($saeUser) ? ($saeUser['role'] ?? '') : ($saeUser->role ?? '');
            $bisaAktifkan = in_array($role, ['peserta_didik', 'guru', 'tendik', 'tugas_tambahan', 'pustakawan'], true);
        }

        return view('perpustakaan.publik', compact(
            'buku', 'q', 'kategori', 'kategoriList', 'totalJudul', 'totalEksemplar', 'totalTersedia',
            'stats', 'tren', 'populer', 'terbaru', 'kategoriChart', 'running_info', 'bisaAktifkan'
        ));
    }

    // =====================================================================
    // AUTENTIKASI ANGGOTA
    // =====================================================================

    public function loginForm()
    {
        if ($this->currentMember()) {
            return redirect()->route('perpustakaan.portal');
        }
        return view('perpustakaan.login');
    }

    public function login(Request $request)
    {
        $token = trim((string) $request->input('token', ''));

        // --- Gerbang 1: token QR kartu anggota ---
        if ($token !== '') {
            $member = PerpusAnggota::where('token_kartu', $token)->where('is_active', true)->first();
            if (!$member) {
                return back()->with('error', 'Kartu anggota tidak dikenali atau tidak aktif. Hubungi pustakawan.');
            }
            $this->establishSession($member, $request);
            return redirect()->route('perpustakaan.portal')->with('success', 'Selamat datang, ' . $member->nama . '.');
        }

        // --- Gerbang 2: NISN/NIP + password SAE ---
        // Kredensial yang dipakai adalah kredensial akun SAE (tabel `pengguna`),
        // jadi anggota memakai username & password yang sama seperti login SAE.
        // Meski kredensialnya valid, akses DITOLAK bila belum terdaftar sebagai
        // anggota perpustakaan (tidak ada auto-provision di jalur login).
        $request->validate([
            'identifier' => 'required|string',
            'password'   => 'required|string',
        ], [
            'identifier.required' => 'Isi NISN/NIP atau pindai kartu anggota.',
            'password.required'   => 'Password wajib diisi (gunakan password akun SAE Anda).',
        ]);

        $identifier = trim((string) $request->input('identifier'));
        $password = (string) $request->input('password', '');

        // 1) Anggota harus terdaftar lebih dahulu.
        $member = PerpusAnggota::where('is_active', true)
            ->where(function ($qb) use ($identifier) {
                $qb->where('nisn_nip', $identifier)
                    ->orWhere('nomor_anggota', $identifier)
                    ->orWhere('email', $identifier);
            })
            ->first();

        if (!$member) {
            return back()->withInput()->with('error', 'Anggota dengan NISN/NIP tersebut belum terdaftar. Silakan hubungi pustakawan atau aktifkan keanggotaan dari akun SAE Anda.');
        }

        // 2) Verifikasi password.
        //    - Peserta didik & GTK : memakai kredensial akun SAE (sama persis
        //      dengan login portal SAE).
        //    - Umum               : tidak punya akun SAE, password dikelola
        //      pada data keanggotaan perpustakaan.
        if ($member->tipe === 'umum') {
            if (!$this->cekPasswordUmum($member, $password)) {
                return back()->withInput()->with('error', 'Password anggota tidak sesuai.');
            }

            $this->establishSession($member, $request);
            return redirect()->route('perpustakaan.portal')->with('success', 'Selamat datang, ' . $member->nama . '.');
        }

        $saeUser = SaeKredensialService::autentikasi($identifier, $password);

        // Identitas alternatif: anggota bisa masuk lewat nomor anggota / email,
        // sementara akun SAE-nya terdaftar dengan NISN/NIP.
        if (!$saeUser && $member->nisn_nip) {
            $saeUser = SaeKredensialService::autentikasi((string) $member->nisn_nip, $password);
        }

        if (!$saeUser) {
            return back()->withInput()->with('error', 'Password tidak sesuai dengan akun SAE Anda. Gunakan username & password yang sama seperti saat login ke portal SAE.');
        }

        // 3) Akun SAE harus benar-benar milik anggota ini — jangan sampai
        //    kredensial milik orang lain dipakai masuk ke akun anggota.
        if (!$this->akunSaeCocokDenganAnggota($saeUser, $member)) {
            return back()->withInput()->with('error', 'Akun SAE tersebut tidak terhubung dengan keanggotaan perpustakaan ini. Hubungi pustakawan untuk penyesuaian data.');
        }

        $this->establishSession($member, $request);
        return redirect()->route('perpustakaan.portal')->with('success', 'Selamat datang, ' . $member->nama . '.');
    }

    /**
     * Verifikasi password anggota umum (tanpa akun SAE).
     * Bila password belum pernah diatur, password awal = NISN/NIP.
     */
    private function cekPasswordUmum(PerpusAnggota $member, string $password): bool
    {
        $stored = (string) ($member->password ?? '');

        if ($stored === '') {
            $awal = trim((string) $member->nisn_nip);

            return $awal !== '' && $password === $awal;
        }

        return Hash::check($password, $stored);
    }

    /**
     * Pastikan akun SAE yang berhasil login memang milik anggota perpustakaan
     * yang dituju (bukan sekadar kebetulan nomornya sama).
     */
    private function akunSaeCocokDenganAnggota(User $saeUser, PerpusAnggota $member): bool
    {
        // Cocok lewat id master data (paling kuat).
        if (!empty($saeUser->peserta_didik_id) && !empty($member->siswa_id)
            && (string) $saeUser->peserta_didik_id === (string) $member->siswa_id) {
            return true;
        }

        if (!empty($saeUser->ptk_id) && !empty($member->ptk_id)
            && (string) $saeUser->ptk_id === (string) $member->ptk_id) {
            return true;
        }

        // Cocok lewat username = NISN/NIP anggota.
        $username = trim((string) ($saeUser->username ?? ''));
        if ($username !== '' && $member->nisn_nip && $username === trim((string) $member->nisn_nip)) {
            return true;
        }

        // Cocok lewat NISN/NIP pada master data akun.
        if (!empty($saeUser->peserta_didik_id) && Table::exists('peserta_didik')) {
            $nisn = DB::table('peserta_didik')
                ->where('peserta_didik_id', $saeUser->peserta_didik_id)
                ->value('nisn');
            if ($nisn && $member->nisn_nip && trim((string) $nisn) === trim((string) $member->nisn_nip)) {
                return true;
            }
        }

        if (!empty($saeUser->ptk_id) && Table::exists('gtk') && Table::hasColumn('gtk', 'nip')) {
            $nip = DB::table('gtk')->where('ptk_id', $saeUser->ptk_id)->value('nip');
            if ($nip && $member->nisn_nip && trim((string) $nip) === trim((string) $member->nisn_nip)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Aktifkan keanggotaan otomatis dari akun SAE (peserta didik / GTK).
     * Sengaja berupa aksi eksplisit agar tidak membuat data tanpa persetujuan.
     */
    public function aktifkan(Request $request)
    {
        $saeUser = session('user');
        if (!$saeUser) {
            return redirect()->route('perpustakaan.login')
                ->with('error', 'Masuk ke portal SAE terlebih dahulu untuk mengaktifkan kartu anggota.');
        }

        if ($member = $this->currentMember()) {
            return redirect()->route('perpustakaan.portal');
        }

        $member = PerpustakaanIdentitasService::resolveFromSaeUser($saeUser)
            ?? PerpustakaanIdentitasService::autoProvisionFromSaeUser($saeUser);

        if (!$member) {
            return redirect()->route('perpustakaan.index')
                ->with('error', 'Data Anda belum ditemukan pada master data sekolah. Hubungi pustakawan untuk pendaftaran manual.');
        }

        $this->establishSession($member, $request);

        return redirect()->route('perpustakaan.portal')
            ->with('success', 'Kartu anggota berhasil diaktifkan. Nomor anggota Anda: ' . $member->nomor_anggota);
    }

    public function logout(Request $request)
    {
        $token = $request->session()->pull('perpus_anggota_token');
        if ($token && Table::exists('perpus_anggota_session')) {
            DB::table('perpus_anggota_session')->where('token', $token)->delete();
        }
        $request->session()->forget(self::SESSION_KEY);
        return redirect()->route('perpustakaan.index')->with('success', 'Anda telah keluar dari portal anggota.');
    }

    public function verifikasi(string $token)
    {
        $member = PerpusAnggota::where('token_kartu', $token)->where('is_active', true)->first();
        return view('perpustakaan.verifikasi', ['member' => $member, 'token' => $token]);
    }

    // =====================================================================
    // PORTAL MEMBER (DASHBOARD)
    // =====================================================================

    public function portal(Request $request)
    {
        $member = $this->requireMember();

        $statistik = PerpustakaanMemberService::statistik($member);
        $level = PerpustakaanMemberService::level($member);
        $notifikasi = PerpustakaanMemberService::daftarNotifikasi($member, 8);
        $reservasi = PerpustakaanMemberService::reservasiAnggota($member);
        $rekomendasi = PerpustakaanMemberService::rekomendasi($member, 6);

        $riwayat = PerpustakaanIdentitasService::riwayatQuery($member)
            ->orderByDesc('s.tgl_pinjam')
            ->limit(50)
            ->get();

        $aktif = $riwayat->where('status', 'dipinjam');
        $kunjunganTerakhir = collect();
        if (Table::exists('perpus_kunjungan')) {
            $qk = DB::table('perpus_kunjungan')->orderByDesc('tanggal')->limit(5);
            if (Table::hasColumn('perpus_kunjungan', 'anggota_id')) {
                $qk->where('anggota_id', $member->id);
            } else {
                $qk->where('pengunjung_id', $member->siswa_id ?? $member->nisn_nip);
            }
            $kunjunganTerakhir = $qk->get();
        }

        return view('perpustakaan.portal', compact(
            'member', 'statistik', 'level', 'notifikasi', 'reservasi',
            'rekomendasi', 'riwayat', 'aktif', 'kunjunganTerakhir'
        ));
    }

    // =====================================================================
    // KATALOG & DETAIL BUKU
    // =====================================================================

    public function katalog(Request $request)
    {
        $member = $this->currentMember();
        $q = trim((string) $request->query('q', ''));
        $kategori = trim((string) $request->query('kategori', ''));
        $hanyaTersedia = $request->boolean('tersedia');

        $base = Table::exists('perpus_koleksi_buku') ? DB::table('perpus_koleksi_buku') : null;
        $buku = collect();
        $kategoriList = collect();

        if ($base) {
            $kategoriList = (clone $base)->distinct()->pluck('kategori')->filter()->sort()->values();

            $q2 = clone $base;
            if ($kategori !== '') {
                $q2->where('kategori', $kategori);
            }
            if ($hanyaTersedia) {
                $q2->where('eksemplar_tersedia', '>', 0);
            }
            if ($q !== '') {
                $q2->where(function ($qb) use ($q) {
                    $qb->where('judul', 'like', "%{$q}%")
                        ->orWhere('penulis', 'like', "%{$q}%")
                        ->orWhere('penerbit', 'like', "%{$q}%")
                        ->orWhere('isbn', 'like', "%{$q}%")
                        ->orWhere('klasifikasi_ddc', 'like', "%{$q}%");
                });
            }
            $buku = $q2->orderBy('judul')->paginate(18)->withQueryString();
        }

        return view('perpustakaan.katalog', compact(
            'member', 'buku', 'q', 'kategori', 'kategoriList', 'hanyaTersedia'
        ));
    }

    public function detail(int $id)
    {
        $member = $this->currentMember();

        if (!Table::exists('perpus_koleksi_buku')) {
            return redirect()->route('perpustakaan.katalog');
        }

        $buku = DB::table('perpus_koleksi_buku')->where('id', $id)->first();
        if (!$buku) {
            return redirect()->route('perpustakaan.katalog')->with('error', 'Buku tidak ditemukan.');
        }

        $sedangDipinjam = Table::hasColumn('perpus_sirkulasi', 'buku_id')
            ? (int) DB::table('perpus_sirkulasi')->where('buku_id', $id)->where('status', 'dipinjam')->count()
            : 0;

        $ulasan = collect();
        if (Table::exists('perpus_ulasan')) {
            $ulasan = DB::table('perpus_ulasan as u')
                ->leftJoin('perpus_anggota as a', 'u.anggota_id', '=', 'a.id')
                ->select('u.*', 'a.nama as nama_anggota')
                ->where('u.buku_id', $id)
                ->where('u.is_visible', true)
                ->orderByDesc('u.created_at')
                ->limit(20)
                ->get();
        }

        $ratingRata = $ulasan->count() ? round($ulasan->avg('rating'), 1) : null;

        $ulasanSaya = null;
        if ($member && Table::exists('perpus_ulasan')) {
            $ulasanSaya = DB::table('perpus_ulasan')
                ->where('anggota_id', $member->id)
                ->where('buku_id', $id)
                ->first();
        }

        $sudahMeminjam = false;
        if ($member) {
            $sudahMeminjam = PerpustakaanIdentitasService::riwayatQuery($member)
                ->where('s.buku_id', $id)
                ->exists();
        }

        $serupa = DB::table('perpus_koleksi_buku')
            ->where('id', '!=', $id)
            ->when($buku->kategori, fn($q) => $q->where('kategori', $buku->kategori))
            ->where('eksemplar_tersedia', '>', 0)
            ->limit(4)
            ->get();

        return view('perpustakaan.detail', compact(
            'member', 'buku', 'sedangDipinjam', 'ulasan', 'ratingRata',
            'ulasanSaya', 'sudahMeminjam', 'serupa'
        ));
    }

    // =====================================================================
    // RESERVASI
    // =====================================================================

    public function reservasi(Request $request, int $bukuId)
    {
        $member = $this->requireMember();
        [$ok, $pesan] = PerpustakaanMemberService::buatReservasi($member, $bukuId);

        return back()->with($ok ? 'success' : 'error', $pesan);
    }

    public function batalkanReservasi(Request $request, int $id)
    {
        $member = $this->requireMember();
        $ok = PerpustakaanMemberService::batalkanReservasi($member, $id);

        return back()->with($ok ? 'success' : 'error', $ok
            ? 'Reservasi dibatalkan.'
            : 'Reservasi tidak ditemukan atau sudah diproses.');
    }

    // =====================================================================
    // NOTIFIKASI
    // =====================================================================

    public function notifikasi(Request $request)
    {
        $member = $this->requireMember();
        $daftar = PerpustakaanMemberService::daftarNotifikasi($member, 100);

        return view('perpustakaan.notifikasi', compact('member', 'daftar'));
    }

    public function tandaiNotifikasi(Request $request, ?int $id = null)
    {
        $member = $this->requireMember();
        PerpustakaanMemberService::tandaiDibaca($member, $id);

        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    // =====================================================================
    // PROFIL & PASSWORD
    // =====================================================================

    public function profil(Request $request)
    {
        $member = $this->requireMember();
        $level = PerpustakaanMemberService::level($member);

        return view('perpustakaan.profil', compact('member', 'level'));
    }

    public function updateProfil(Request $request)
    {
        $member = $this->requireMember();

        $data = $request->validate([
            'telepon' => 'nullable|string|max:30',
            'email'   => 'nullable|email|max:255',
        ], [
            'telepon.max' => 'Nomor telepon maksimal 30 karakter.',
            'email.email' => 'Format email tidak valid. Contoh: nama@sekolah.sch.id',
            'email.max'   => 'Email maksimal 255 karakter.',
        ]);

        $member->update([
            'telepon' => $data['telepon'] ?? null,
            'email'   => $data['email'] ?? null,
        ]);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $member = $this->requireMember();

        // Peserta didik & GTK memakai password akun SAE — tidak boleh diubah
        // dari sini agar kredensial kedua portal tetap satu.
        if ($member->tipe !== 'umum') {
            return back()->with('error', 'Password akun Anda mengikuti akun SAE. Silakan ubah password melalui portal SAE.');
        }

        $request->validate([
            'password_lama' => 'required|string',
            'password_baru' => 'required|string|min:6|confirmed',
        ], [
            'password_lama.required'  => 'Masukkan password lama Anda.',
            'password_baru.required'  => 'Masukkan password baru.',
            'password_baru.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password_baru.min'       => 'Password baru minimal 6 karakter.',
        ]);

        if (!$this->cekPasswordUmum($member, (string) $request->input('password_lama'))) {
            return back()->with('error', 'Password lama tidak sesuai.');
        }

        $member->update(['password' => Hash::make((string) $request->input('password_baru'))]);

        return back()->with('success', 'Password berhasil diubah. Gunakan password baru pada login berikutnya.');
    }

    // =====================================================================
    // ULASAN
    // =====================================================================

    public function simpanUlasan(Request $request, int $bukuId)
    {
        $member = $this->requireMember();

        if (!Table::exists('perpus_ulasan')) {
            return back()->with('error', 'Fitur ulasan belum tersedia.');
        }

        $data = $request->validate([
            'rating'   => 'required|integer|min:1|max:5',
            'komentar' => 'nullable|string|max:1000',
        ], [
            'rating.required' => 'Pilih rating bintang terlebih dahulu.',
            'rating.min'      => 'Rating minimal 1 bintang.',
            'rating.max'      => 'Rating maksimal 5 bintang.',
            'komentar.max'    => 'Komentar maksimal 1.000 karakter.',
        ]);

        $sudahMeminjam = PerpustakaanIdentitasService::riwayatQuery($member)
            ->where('s.buku_id', $bukuId)
            ->exists();

        if (!$sudahMeminjam) {
            return back()->with('error', 'Ulasan hanya dapat diberikan setelah Anda meminjam buku ini.');
        }

        $existing = DB::table('perpus_ulasan')
            ->where('anggota_id', $member->id)
            ->where('buku_id', $bukuId)
            ->first();

        if ($existing) {
            DB::table('perpus_ulasan')->where('id', $existing->id)->update([
                'rating'     => $data['rating'],
                'komentar'   => $data['komentar'] ?? null,
                'updated_at' => now(),
            ]);
            return back()->with('success', 'Ulasan Anda diperbarui.');
        }

        DB::table('perpus_ulasan')->insert([
            'anggota_id' => $member->id,
            'buku_id'    => $bukuId,
            'rating'     => $data['rating'],
            'komentar'   => $data['komentar'] ?? null,
            'is_visible' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        PerpustakaanMemberService::tambahPoin($member, 'ulasan');

        return back()->with('success', 'Terima kasih! Ulasan Anda tersimpan.');
    }

    // =====================================================================
    // KUNJUNGAN MANDIRI (CHECK-IN PERPUSTAKAAN)
    // =====================================================================

    public function checkin(Request $request)
    {
        $member = $this->requireMember();

        if (!Table::exists('perpus_kunjungan')) {
            return back()->with('error', 'Pencatatan kunjungan belum tersedia.');
        }

        $sudahHariIni = DB::table('perpus_kunjungan')
            ->whereDate('tanggal', now()->toDateString())
            ->when(Table::hasColumn('perpus_kunjungan', 'anggota_id'),
                fn($q) => $q->where('anggota_id', $member->id),
                fn($q) => $q->where('pengunjung_id', $member->siswa_id ?? $member->nisn_nip))
            ->exists();

        if ($sudahHariIni) {
            return back()->with('error', 'Anda sudah tercatat berkunjung hari ini.');
        }

        DB::table('perpus_kunjungan')->insert(array_filter([
            'tanggal'          => now()->toDateString(),
            'jam_kunjung'      => now()->format('H:i:s'),
            'pengunjung_tipe'  => $member->tipe === 'siswa' ? 'siswa' : ($member->tipe === 'gtk' ? 'guru' : 'umum'),
            'pengunjung_id'    => $member->siswa_id ?? $member->ptk_id ?? $member->nisn_nip,
            'anggota_id'       => Table::hasColumn('perpus_kunjungan', 'anggota_id') ? $member->id : null,
            'nama'             => $member->nama,
            'rombel_atau_unit' => $member->rombel_atau_unit,
            'keperluan'        => $request->input('keperluan', 'Membaca / meminjam buku'),
            'created_at'       => now(),
            'updated_at'       => now(),
        ], fn($v) => $v !== null));

        PerpustakaanMemberService::tambahPoin($member, 'kunjungan');

        return back()->with('success', 'Kunjungan Anda hari ini tercatat. Selamat membaca!');
    }

    // =====================================================================
    // INTERNAL
    // =====================================================================

    private function currentMember(): ?PerpusAnggota
    {
        $id = session(self::SESSION_KEY);
        return $id ? PerpusAnggota::find($id) : null;
    }

    private function requireMember(): PerpusAnggota
    {
        $member = $this->currentMember();
        if (!$member) {
            abort(redirect()->route('perpustakaan.login')
                ->with('error', 'Silakan masuk sebagai anggota terlebih dahulu.'));
        }
        return $member;
    }

    /**
     * Auto-login dari sesi SAE — HANYA bila sudah terdaftar sebagai anggota.
     * (Pembuatan anggota baru dilakukan eksplisit lewat aksi `aktifkan`.)
     */
    private function autoLoginFromSaeSession(Request $request): ?PerpusAnggota
    {
        if (session()->has(self::SESSION_KEY)) {
            return null;
        }

        $saeUser = session('user');
        if (!$saeUser) {
            return null;
        }

        $member = PerpustakaanIdentitasService::resolveFromSaeUser($saeUser);
        if (!$member || !$member->is_active) {
            return null;
        }

        $this->establishSession($member, $request);
        return $member;
    }

    private function establishSession(PerpusAnggota $member, Request $request): void
    {
        $token = Str::random(48);

        if (Table::exists('perpus_anggota_session')) {
            DB::table('perpus_anggota_session')->insert([
                'anggota_id' => $member->id,
                'token'      => $token,
                'ip'         => $request->ip(),
                'expires_at' => now()->addHours(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Table::hasColumn('perpus_anggota', 'last_login_at')) {
            DB::table('perpus_anggota')->where('id', $member->id)->update(['last_login_at' => now()]);
        }

        $request->session()->put(self::SESSION_KEY, $member->id);
        $request->session()->put('perpus_anggota_token', $token);
    }

    private function runningInfo(): array
    {
        $info = [];
        try {
            $info = \App\Models\Pengumuman::forPublic()->pluck('isi')->toArray();
        } catch (\Throwable $e) {
            $info = [];
        }

        if (empty($info)) {
            $info = [
                'Selamat datang di Perpustakaan Digital Sekolah — pindai kartu anggota Anda untuk masuk.',
                'Layanan peminjaman buku buka setiap hari kerja pukul 07.00 – 15.00 WIB.',
                'Jaga dan kembalikan buku tepat waktu demi kenyamanan bersama.',
            ];
        }

        return $info;
    }
}
