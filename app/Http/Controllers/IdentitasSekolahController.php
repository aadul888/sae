<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IdentitasSekolahController extends Controller
{
    private function checkAuth()
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }
        return null;
    }

    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $sekolah = null;
        if (Schema::hasTable('sekolah')) {
            $sekolah = DB::table('sekolah')->first();
        }

        $settings = null;
        if (Schema::hasTable('settings')) {
            $settings = DB::table('settings')->where('id', 1)->first();
        }

        $stats = [
            'total_pd' => Schema::hasTable('peserta_didik') ? DB::table('peserta_didik')->count() : 0,
            'total_gtk' => Schema::hasTable('gtk') ? DB::table('gtk')->count() : 0,
            'total_rombel' => Schema::hasTable('rombongan_belajar') ? DB::table('rombongan_belajar')->count() : 0,
            'total_pembelajaran' => Schema::hasTable('pembelajaran') ? DB::table('pembelajaran')->count() : 0,
            'total_pengguna' => Schema::hasTable('pengguna') ? DB::table('pengguna')->count() : 0,
        ];

        return view('dashboard.identitas-sekolah', compact('sekolah', 'settings', 'stats'));
    }

    public function update(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return back()->with('error', 'Hanya administrator yang dapat memperbarui identitas sekolah.');
        }

        $request->validate([
            'nama' => 'nullable|string|max:200',
            'npsn' => 'nullable|string|max:20',
            'nss' => 'nullable|string|max:50',
            'bentuk_pendidikan' => 'nullable|string|max:100',
            'status_sekolah' => 'nullable|string|max:100',
            'alamat_jalan' => 'nullable|string',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'dusun' => 'nullable|string|max:100',
            'desa_kelurahan' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'kabupaten_kota' => 'nullable|string|max:100',
            'provinsi' => 'nullable|string|max:100',
            'kode_pos' => 'nullable|string|max:10',
            'lintang' => 'nullable|string|max:50',
            'bujur' => 'nullable|string|max:50',
            'nomor_telepon' => 'nullable|string|max:50',
            'nomor_fax' => 'nullable|string|max:50',
            'email' => 'nullable|string|email|max:100',
            'website' => 'nullable|string|max:200',
        ]);

        if (Schema::hasTable('sekolah')) {
            $current = DB::table('sekolah')->first();
            $data = [
                'nama' => $request->input('nama', $current->nama ?? null),
                'npsn' => $request->input('npsn', $current->npsn ?? null),
                'nss' => $request->input('nss', $current->nss ?? null),
                'bentuk_pendidikan_id_str' => $request->input('bentuk_pendidikan', $current->bentuk_pendidikan_id_str ?? null),
                'status_sekolah_str' => $request->input('status_sekolah', $current->status_sekolah_str ?? null),
                'alamat_jalan' => $request->input('alamat_jalan', $current->alamat_jalan ?? null),
                'rt' => $request->input('rt', $current->rt ?? null),
                'rw' => $request->input('rw', $current->rw ?? null),
                'dusun' => $request->input('dusun', $current->dusun ?? null),
                'desa_kelurahan' => $request->input('desa_kelurahan', $current->desa_kelurahan ?? null),
                'kecamatan' => $request->input('kecamatan', $current->kecamatan ?? null),
                'kabupaten_kota' => $request->input('kabupaten_kota', $current->kabupaten_kota ?? null),
                'provinsi' => $request->input('provinsi', $current->provinsi ?? null),
                'kode_pos' => $request->input('kode_pos', $current->kode_pos ?? null),
                'lintang' => $request->input('lintang', $current->lintang ?? null),
                'bujur' => $request->input('bujur', $current->bujur ?? null),
                'nomor_telepon' => $request->input('nomor_telepon', $current->nomor_telepon ?? null),
                'nomor_fax' => $request->input('nomor_fax', $current->nomor_fax ?? null),
                'email' => $request->input('email', $current->email ?? null),
                'website' => $request->input('website', $current->website ?? null),
                'updated_at' => now(),
            ];

            if ($current) {
                DB::table('sekolah')->where('sekolah_id', $current->sekolah_id)->update($data);
            } else {
                $data['sekolah_id'] = 'SCH-' . uniqid();
                $data['created_at'] = now();
                DB::table('sekolah')->insert($data);
            }
        }

        return back()->with('success', 'Identitas Sekolah berhasil disimpan.');
    }
}
