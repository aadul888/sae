<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

        $username = trim($request->input('username'));
        $password = $request->input('password');

        // Dummy auth logic for multi-user
        $users = [
            'admin' => ['name' => 'Administrator Utama', 'role' => 'admin', 'email' => 'admin@sae.sch.id', 'nip' => '198501012010011001', 'avatar' => 'admin.png'],
            'guru'  => ['name' => 'Budi Santoso, S.Pd.', 'role' => 'guru', 'email' => 'budi.santoso@sae.sch.id', 'nip' => '197905122005011003', 'mapel' => 'Informatika & Rekayasa Perangkat Lunak', 'avatar' => 'guru.png'],
            'siswa' => ['name' => 'Muhammad Rizky Pratama', 'role' => 'siswa', 'email' => 'rizky.pratama@siswa.sae.sch.id', 'nisn' => '0071234567', 'kelas' => 'XII RPL 1', 'avatar' => 'siswa.png'],
        ];

        // Match username directly or fallback by prefix/rule
        $account = null;
        if (isset($users[$username])) {
            $account = $users[$username];
        } elseif (is_numeric($username) && strlen($username) === 10) {
            $account = $users['siswa'];
            $account['nisn'] = $username;
        } elseif (is_numeric($username) && strlen($username) >= 18) {
            $account = $users['guru'];
            $account['nip'] = $username;
        } else {
            // Default demo fallback based on role selection or default to admin
            $account = $users['admin'];
        }

        session(['user' => $account]);

        return redirect()->route('dashboard.' . $account['role'])->with('success', 'Selamat datang kembali, ' . $account['name']);
    }

    public function logout()
    {
        session()->forget('user');
        return redirect()->route('login')->with('info', 'Anda telah keluar dari sistem.');
    }
}
