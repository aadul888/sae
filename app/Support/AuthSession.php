<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Jembatan autentikasi antara sesi lama dan guard Laravel.
 *
 * Latar belakang: aplikasi ini sejak awal menyimpan data pengguna pada
 * `session('user')` sebagai array (353 titik pemakaian). Guard Laravel
 * (`Auth::user()`) tidak pernah diisi, sehingga fitur bawaan framework
 * seperti `auth` middleware, `@auth` di Blade, rate limiting per pengguna,
 * dan Sanctum tidak dapat bekerja optimal.
 *
 * Strategi migrasi bertahap (tanpa memutus alur yang berjalan):
 *  1. Setiap login kini memanggil `Auth::login($user)` SEKALIGUS mengisi
 *     `session('user')` lewat helper ini.
 *  2. Kode lama tetap berjalan karena `session('user')` masih tersedia.
 *  3. Kode baru sebaiknya memakai `Auth::user()` / `auth()->user()`.
 *  4. Setelah seluruh pemakaian lama dimigrasi, `session('user')` dapat
 *     dihapus dari helper ini tanpa mengubah pemanggil.
 */
final class AuthSession
{
    /**
     * Masuk sebagai pengguna: isi guard Laravel dan sesi aplikasi.
     *
     * @param  array<string, mixed>  $userData  Data siap pakai untuk view & controller.
     */
    public static function login(User $user, array $userData, bool $remember = false): void
    {
        // 1. Guard Laravel (sumber kebenaran baru).
        Auth::login($user, $remember);

        // 2. Sesi aplikasi (kompatibilitas kode lama).
        session(['user' => $userData]);
    }

    /**
     * Bangun data siap pakai untuk view & controller dari model User.
     *
     * Dipakai oleh login biasa (AuthController) maupun "masuk sebagai"
     * (impersonasi) oleh admin, agar sesi yang terbentuk identik.
     *
     * @return array<string, mixed>
     */
    public static function dataFor(User $user): array
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
                    if (\App\Support\Table::exists('ptk_tugas_tambahan') && \App\Support\Table::exists('ref_tugas_tambahan')) {
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
                    if (\App\Support\Table::exists('pembelajaran')) {
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
            if (\App\Support\Table::exists('peserta_didik_meta')) {
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

        return $userData;
    }

    /**
     * Keluar: bersihkan guard dan sesi aplikasi.
     */
    public static function logout(): void
    {
        Auth::logout();
        session()->forget(['user', 'impersonator']);
    }

    /**
     * Pengguna aktif dari guard Laravel.
     *
     * Mengembalikan model User bila guard sudah terisi; bila belum (mis. sesi
     * lama yang dibuat sebelum migrasi), fallback ke sesi aplikasi.
     */
    public static function user(): ?User
    {
        $authUser = Auth::user();
        if ($authUser instanceof User) {
            return $authUser;
        }

        // Fallback: bangun model dari sesi lama tanpa query tambahan bila mungkin.
        $sessionUser = session('user');
        if (!$sessionUser) {
            return null;
        }

        $id = is_array($sessionUser)
            ? ($sessionUser['pengguna_id'] ?? ($sessionUser['id'] ?? null))
            : ($sessionUser->pengguna_id ?? ($sessionUser->id ?? null));

        return $id ? User::find($id) : null;
    }

    /**
     * Apakah pengguna sudah terautentikasi (guard atau sesi lama)?
     */
    public static function check(): bool
    {
        return Auth::check() || session()->has('user');
    }
}
