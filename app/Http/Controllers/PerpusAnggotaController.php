<?php

namespace App\Http\Controllers;

use App\Models\PerpusAnggota;
use App\Models\RolePermission;
use App\Services\KartuPelajarPrintService;
use App\Services\PerpustakaanIdentitasService;
use App\Services\PerpustakaanMemberService;
use App\Support\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Registrasi Anggota Perpustakaan + ID Card Digital.
 * Anggota bisa ditarik dari peserta_didik/gtk atau didaftarkan manual (umum).
 */
class PerpusAnggotaController extends Controller
{
    public function index(Request $request)
    {
        if ($redirect = $this->guard()) return $redirect;

        $q = trim((string) $request->query('q', ''));
        $tipe = (string) $request->query('tipe', '');

        $query = PerpusAnggota::query();
        if ($tipe !== '') {
            $query->where('tipe', $tipe);
        }
        if ($q !== '') {
            $query->where(function ($qb) use ($q) {
                $qb->where('nama', 'like', "%{$q}%")
                    ->orWhere('nisn_nip', 'like', "%{$q}%")
                    ->orWhere('nomor_anggota', 'like', "%{$q}%")
                    ->orWhere('rombel_atau_unit', 'like', "%{$q}%");
            });
        }
        $list = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $stats = [
            'total' => PerpusAnggota::count(),
            'siswa' => PerpusAnggota::where('tipe', 'siswa')->count(),
            'gtk'   => PerpusAnggota::where('tipe', 'gtk')->count(),
            'umum'  => PerpusAnggota::where('tipe', 'umum')->count(),
        ];

        return view('dashboard.perpus.anggota', compact('list', 'q', 'tipe', 'stats'));
    }

    public function store(Request $request)
    {
        if ($redirect = $this->guard()) return $redirect;

        $tipe = (string) $request->input('tipe', 'siswa');

        // =================================================================
        // PESERTA DIDIK & GTK — cukup NISN/NIP, sisanya dibaca dari master data
        // =================================================================
        if (in_array($tipe, ['siswa', 'gtk'], true)) {
            $request->validate([
                'tipe'     => 'required|in:siswa,gtk',
                'nisn_nip' => 'required|string|max:40',
            ], [
                'nisn_nip.required' => $tipe === 'gtk'
                    ? 'NIP wajib diisi untuk mendaftarkan GTK.'
                    : 'NISN wajib diisi untuk mendaftarkan peserta didik.',
            ]);

            $identifier = trim((string) $request->input('nisn_nip'));
            $hasil = PerpustakaanIdentitasService::daftarDariMasterData($identifier);

            if ($hasil['status'] === 'tidak_ditemukan') {
                return back()->withInput()->with('error', $tipe === 'gtk'
                    ? "NIP {$identifier} tidak ditemukan pada data GTK sekolah. Periksa kembali nomornya."
                    : "NISN {$identifier} tidak ditemukan pada data peserta didik. Periksa kembali nomornya.");
            }

            if ($hasil['status'] === 'sudah_terdaftar') {
                $lama = $hasil['anggota'];
                return back()->with('error', "NISN/NIP {$identifier} sudah terdaftar sebagai anggota atas nama {$lama->nama} (No. {$lama->nomor_anggota}).");
            }

            $anggota = $hasil['anggota'];

            return redirect()->route('dashboard.perpustakaan.anggota.index')
                ->with('success', "Anggota berhasil didaftarkan: {$anggota->nama} ({$anggota->nomor_anggota}). Data ditarik otomatis dari master data sekolah.");
        }

        // =================================================================
        // UMUM — bukan warga sekolah, data diisi manual
        // =================================================================
        $request->validate([
            'tipe' => 'required|in:umum',
            'nama' => 'required|string|max:255',
            'nisn_nip' => 'nullable|string|max:40',
        ]);

        $nisnNip = trim((string) $request->input('nisn_nip')) ?: null;
        if ($nisnNip && PerpusAnggota::where('nisn_nip', $nisnNip)->exists()) {
            return back()->withInput()->with('error', 'NISN/NIP tersebut sudah terdaftar sebagai anggota.');
        }

        // Nomor anggota dibuat lebih dahulu karena dipakai sebagai password awal
        // bila pustakawan tidak mengisinya dan NISN/NIP juga kosong. Tanpa ini
        // anggota umum tanpa nomor identitas tidak akan pernah bisa masuk.
        $nomorAnggota = PerpusAnggota::generateNomor('umum');

        $passwordAwal = trim((string) $request->input('password', ''));
        if ($passwordAwal === '') {
            $passwordAwal = $nisnNip ?: $nomorAnggota;
        }

        PerpusAnggota::create([
            'nomor_anggota' => $nomorAnggota,
            'token_kartu' => Str::random(32),
            'tipe' => 'umum',
            'siswa_id' => null,
            'ptk_id' => null,
            'nisn_nip' => $nisnNip,
            'nama' => $request->nama,
            'rombel_atau_unit' => $request->rombel_atau_unit,
            'jenis_kelamin' => $request->jenis_kelamin,
            'telepon' => $request->telepon,
            'email' => $request->email,
            'password' => Hash::make($passwordAwal),
            'is_active' => true,
            'tgl_daftar' => now()->toDateString(),
        ]);

        return redirect()->route('dashboard.perpustakaan.anggota.index')
            ->with('success', "Anggota umum berhasil didaftarkan (No. {$nomorAnggota}). Password awal: {$passwordAwal} — minta anggota segera mengubahnya di halaman profil.");
    }

    /**
     * Pratinjau data master sebelum mendaftar (dipanggil via AJAX dari modal).
     * Pustakawan cukup mengetik NISN/NIP lalu data lain tampil otomatis,
     * sehingga tidak ada risiko salah ketik nama/rombel.
     */
    public function cekMasterData(Request $request)
    {
        if ($redirect = $this->guard()) return $redirect;

        $identifier = trim((string) $request->query('nisn_nip', ''));
        if ($identifier === '') {
            return response()->json(['ok' => false, 'pesan' => 'NISN/NIP belum diisi.'], 422);
        }

        $data = PerpustakaanIdentitasService::cariMasterData($identifier);
        if (!$data) {
            return response()->json([
                'ok' => false,
                'pesan' => 'Nomor tersebut tidak ditemukan pada data peserta didik maupun GTK.',
            ]);
        }

        // Apakah sudah menjadi anggota?
        $sudah = null;
        if ($data['tipe'] === 'siswa' && !empty($data['siswa_id'])) {
            $sudah = PerpusAnggota::where('siswa_id', $data['siswa_id'])->first();
        } elseif ($data['tipe'] === 'gtk' && !empty($data['ptk_id'])) {
            $sudah = PerpusAnggota::where('ptk_id', $data['ptk_id'])->first();
        }
        $sudah = $sudah ?: PerpusAnggota::where('nisn_nip', $data['nisn_nip'])->first();

        return response()->json([
            'ok' => true,
            'tipe' => $data['tipe'],
            'tipe_label' => $data['tipe'] === 'gtk' ? 'GTK' : 'Peserta Didik',
            'nisn_nip' => $data['nisn_nip'],
            'nama' => $data['nama'],
            'rombel_atau_unit' => $data['rombel_atau_unit'],
            'jenis_kelamin' => $data['jenis_kelamin'],
            'email' => $data['email'],
            'telepon' => $data['telepon'],
            'sudah_terdaftar' => $sudah ? true : false,
            'nomor_anggota' => $sudah->nomor_anggota ?? null,
        ]);
    }

    public function update(Request $request, int $id)
    {
        if ($redirect = $this->guard()) return $redirect;

        $member = PerpusAnggota::findOrFail($id);
        $request->validate(['nama' => 'required|string|max:255']);

        $data = $request->only('nama', 'rombel_atau_unit', 'jenis_kelamin', 'telepon', 'email', 'catatan');
        $data['is_active'] = $request->boolean('is_active');
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        $member->update($data);

        return back()->with('success', 'Data anggota diperbarui.');
    }

    public function toggle(Request $request, int $id)
    {
        if ($redirect = $this->guard()) return $redirect;
        $member = PerpusAnggota::findOrFail($id);
        $member->update(['is_active' => !$member->is_active]);
        return back()->with('success', 'Status anggota diperbarui.');
    }

    public function destroy(int $id)
    {
        if ($redirect = $this->guard()) return $redirect;
        PerpusAnggota::where('id', $id)->delete();
        return back()->with('success', 'Anggota dihapus.');
    }

    /** Kartu anggota digital (satu anggota: sisi depan + belakang). */
    public function kartu(int $id)
    {
        if ($redirect = $this->guard()) return $redirect;
        $member = PerpusAnggota::findOrFail($id);
        $card = PerpustakaanMemberService::kartuData($member, $this->sekolahInfo(), true);

        return view('perpus.kartu.sheet', compact('card'));
    }

    /** Cetak kartu anggota massal (5 pasang per lembar A4). */
    public function cetakKartu(Request $request)
    {
        if ($redirect = $this->guard()) return $redirect;

        $ids = array_filter((array) $request->query('ids', []));
        $sekolah = $this->sekolahInfo();

        $members = PerpusAnggota::when($ids, fn($q) => $q->whereIn('id', $ids), fn($q) => $q->limit(50))
            ->orderBy('nama')
            ->get();

        $cards = $members->map(fn($m) => PerpustakaanMemberService::kartuData($m, $sekolah))->all();

        return view('dashboard.perpus.anggota-kartu-cetak', compact('members', 'cards'));
    }

    /** Info sekolah/perpustakaan untuk kop & kontak kartu. */
    private function sekolahInfo(): array
    {
        return app(KartuPelajarPrintService::class)->getSekolahInfo();
    }

    private function guard()
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $can = RolePermission::canAccess($user ?: 'tendik', 'menu_perpustakaan', 'read')
            || RolePermission::canAccess($user ?: 'tendik', 'menu_perpustakaan_anggota', 'read');

        if (!$can && !in_array($role, ['admin', 'tendik', 'pustakawan'], true)) {
            return redirect()->route('dashboard.' . $role)->with('error', 'Anda tidak memiliki hak akses ke Anggota Perpustakaan.');
        }
        return null;
    }
}
