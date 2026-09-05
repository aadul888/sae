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
            return back()->withInput()->with('error', 'Username/Email/NIP/NISN tidak ditemukan.');
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
        }

        session(['user' => $userData]);

        return redirect()->route('dashboard.' . $user->role)->with('success', 'Selamat datang kembali, ' . $user->name);
    }

    public function logout()
    {
        session()->forget('user');
        return redirect()->route('login')->with('info', 'Anda telah keluar dari sistem.');
    }
}

