<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private const SORTABLE = ['nama', 'username', 'peran_id_str', 'no_hp'];

    /**
     * Halaman manajemen pengguna (3 Tab: Admin, Guru/Tendik, Siswa)
     */
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'admin');
        $q         = trim($request->get('q', ''));
        $perPage   = (int) $request->get('perPage', 15);
        $sort      = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'nama';
        $sortDir   = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $adminQuery = User::where(function ($query) {
            $query->where('peran_id_str', 'LIKE', '%admin%')
                ->orWhere('peran_id_str', 'LIKE', '%operator%')
                ->orWhere('peran_id_str', 'LIKE', '%dinas%')
                ->orWhere('peran_id_str', 'LIKE', '%yayasan%');
        });

        $guruQuery = User::where(function ($query) {
            $query->where('peran_id_str', 'LIKE', '%guru%')
                ->orWhere('peran_id_str', 'LIKE', '%ptk%')
                ->orWhere('peran_id_str', 'LIKE', '%tendik%')
                ->orWhereNotNull('ptk_id');
        })->where(function ($query) {
            $query->where('peran_id_str', 'NOT LIKE', '%admin%')
                ->where('peran_id_str', 'NOT LIKE', '%operator%');
        });

        $siswaQuery = User::where(function ($query) {
            $query->where(function ($q2) {
                $q2->where('peran_id_str', 'LIKE', '%siswa%')
                    ->orWhere('peran_id_str', 'LIKE', '%peserta didik%')
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
            $applySearch($siswaQuery);
        }

        $orderFn = function ($query) use ($sort, $sortDir) {
            $query->orderBy($sort, $sortDir)->orderBy('nama', 'asc');
        };

        $admins = tap($adminQuery, $orderFn)->paginate($perPage, ['*'], 'admin_page');
        $gurus  = tap($guruQuery, $orderFn)->paginate($perPage, ['*'], 'guru_page');
        $siswas = tap($siswaQuery, $orderFn)->paginate($perPage, ['*'], 'siswa_page');

        $counts = [
            'admin' => (clone $adminQuery)->count(),
            'guru'  => (clone $guruQuery)->count(),
            'siswa' => (clone $siswaQuery)->count(),
        ];

        return view('dashboard.pengguna', compact(
            'admins',
            'gurus',
            'siswas',
            'counts',
            'activeTab',
            'q',
            'perPage',
            'sort',
            'sortDir'
        ));
    }

    /**
     * Update pengguna (modal edit)
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'nama'          => 'required|string|max:200',
            'username'      => 'required|string|max:100|unique:pengguna,username,' . $id . ',pengguna_id',
            'password'      => 'nullable|string|min:6',
            'peran_id_str'  => 'required|string',
            'no_hp'         => 'nullable|string|max:30',
            'alamat'        => 'nullable|string',
        ]);

        $user->nama         = $validated['nama'];
        $user->username     = $validated['username'];
        $user->peran_id_str = $validated['peran_id_str'];
        $user->no_hp        = $validated['no_hp'] ?? null;
        $user->alamat       = $validated['alamat'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Hapus pengguna
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }

    /**
     * Reset password siswa ke NISN dari tabel peserta_didik
     */
    public function resetPassword($id)
    {
        $user = User::findOrFail($id);

        if (!$user->peserta_didik_id) {
            return back()->with('error', 'Pengguna bukan siswa (tidak memiliki peserta_didik_id).');
        }

        $pd = DB::table('peserta_didik')
            ->where('peserta_didik_id', $user->peserta_didik_id)
            ->first();

        if (!$pd || empty($pd->nisn)) {
            return back()->with('error', 'Data NISN tidak ditemukan untuk siswa ini.');
        }

        $user->password = Hash::make($pd->nisn);
        $user->save();

        return back()->with('success', "Password siswa {$user->nama} direset ke NISN.");
    }
}
