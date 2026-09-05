<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Halaman manajemen pengguna (3 Tab: Admin, Guru/Tendik, Siswa)
     */
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'admin');
        $q = trim($request->get('q', ''));

        // Query per tab
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

        $admins = $adminQuery->orderBy('nama')->paginate(15, ['*'], 'admin_page');
        $gurus = $guruQuery->orderBy('nama')->paginate(15, ['*'], 'guru_page');
        $siswas = $siswaQuery->orderBy('nama')->paginate(15, ['*'], 'siswa_page');

        $counts = [
            'admin' => (clone $adminQuery)->count(),
            'guru' => (clone $guruQuery)->count(),
            'siswa' => (clone $siswaQuery)->count(),
        ];

        return view('dashboard.pengguna', compact('admins', 'gurus', 'siswas', 'counts', 'activeTab', 'q'));
    }

    /**
     * Simpan pengguna baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:200',
            'username' => 'required|string|max:100|unique:pengguna,username',
            'password' => 'required|string|min:6',
            'peran_id_str' => 'required|string',
            'no_hp' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
        ]);

        User::create([
            'pengguna_id' => Str::uuid()->toString(),
            'nama' => $validated['nama'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'peran_id_str' => $validated['peran_id_str'],
            'no_hp' => $validated['no_hp'] ?? null,
            'alamat' => $validated['alamat'] ?? null,
        ]);

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Update pengguna
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'required|string|max:200',
            'username' => 'required|string|max:100|unique:pengguna,username,' . $id . ',pengguna_id',
            'password' => 'nullable|string|min:6',
            'peran_id_str' => 'required|string',
            'no_hp' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
        ]);

        $user->nama = $validated['nama'];
        $user->username = $validated['username'];
        $user->peran_id_str = $validated['peran_id_str'];
        $user->no_hp = $validated['no_hp'] ?? null;
        $user->alamat = $validated['alamat'] ?? null;

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
}
