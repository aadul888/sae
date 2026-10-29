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
        // PESERTA DIDIK & GTK — cukup NISN/NIP, atau dipilih dari hasil
        // pencarian nama; sisanya dibaca dari master data
        // =================================================================
        if (in_array($tipe, ['siswa', 'gtk'], true)) {
            $request->validate(['tipe' => 'required|in:siswa,gtk']);

            $masterTipe = (string) $request->input('master_tipe', '');
            $masterId = trim((string) $request->input('master_id', ''));
            $identifier = trim((string) $request->input('nisn_nip', ''));

            // Jalur 1: kandidat dipilih dari hasil pencarian nama.
            if ($masterId !== '' && $masterTipe === $tipe) {
                $hasil = PerpustakaanIdentitasService::daftarDariMasterId($masterTipe, $masterId);
            } else {
                // Jalur 2: diketik langsung NISN/NIP.
                $request->validate([
                    'nisn_nip' => 'required|string|max:40',
                ], [
                    'nisn_nip.required' => $tipe === 'gtk'
                        ? 'Isi NIP atau pilih nama GTK dari daftar hasil pencarian.'
                        : 'Isi NISN atau pilih nama peserta didik dari daftar hasil pencarian.',
                ]);

                $hasil = PerpustakaanIdentitasService::daftarDariMasterData($identifier);
            }

            if ($hasil['status'] === 'tidak_ditemukan') {
                return back()->withInput()->with('error', $tipe === 'gtk'
                    ? 'Data GTK tidak ditemukan pada master data sekolah. Periksa kembali NIP atau namanya.'
                    : 'Data peserta didik tidak ditemukan pada master data sekolah. Periksa kembali NISN atau namanya.');
            }

            if ($hasil['status'] === 'sudah_terdaftar') {
                $lama = $hasil['anggota'];
                return back()->with('error', "{$lama->nama} sudah terdaftar sebagai anggota (No. {$lama->nomor_anggota}).");
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
     * Pustakawan bisa mengetik NISN/NIP (angka) atau potongan nama; data lain
     * tampil otomatis sehingga tidak ada risiko salah ketik nama/rombel.
     */
    public function cekMasterData(Request $request)
    {
        if ($redirect = $this->guard()) return $redirect;

        $q = trim((string) $request->query('q', $request->query('nisn_nip', '')));
        $tipe = (string) $request->query('tipe', 'siswa');
        $tipe = in_array($tipe, ['siswa', 'gtk'], true) ? $tipe : 'siswa';

        if ($q === '') {
            return response()->json(['ok' => false, 'pesan' => 'Isi NISN/NIP atau nama anggota.'], 422);
        }

        // Angka → telusuri nomor identitas. Selain itu → cari berdasarkan nama.
        $kandidat = [];
        if (preg_match('/^[0-9\s.\-]+$/', $q)) {
            $data = PerpustakaanIdentitasService::cariMasterData(preg_replace('/[^0-9]/', '', $q));
            $kandidat = $data ? [$data] : [];
        } else {
            $kandidat = PerpustakaanIdentitasService::cariMasterDataByNama($q, $tipe);
        }

        $hasil = [];
        foreach ($kandidat as $data) {
            if (($data['tipe'] ?? '') !== $tipe) {
                continue;
            }

            $sudah = PerpustakaanIdentitasService::anggotaUntuk($data);
            $masterId = $data['tipe'] === 'gtk' ? $data['ptk_id'] : $data['siswa_id'];

            $hasil[] = [
                'tipe' => $data['tipe'],
                'tipe_label' => $data['tipe'] === 'gtk' ? 'GTK' : 'Peserta Didik',
                'master_id' => $masterId,
                'bisa_pilih' => !empty($masterId),
                'nisn_nip' => $data['nisn_nip'],
                'nama' => $data['nama'],
                'rombel_atau_unit' => $data['rombel_atau_unit'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'email' => $data['email'],
                'telepon' => $data['telepon'],
                'sudah_terdaftar' => (bool) $sudah,
                'nomor_anggota' => $sudah->nomor_anggota ?? null,
            ];
        }

        if (empty($hasil)) {
            return response()->json([
                'ok' => false,
                'pesan' => $tipe === 'gtk'
                    ? 'Data GTK tidak ditemukan pada master data. Periksa NIP atau namanya.'
                    : 'Data peserta didik tidak ditemukan pada master data. Periksa NISN atau namanya.',
            ]);
        }

        return response()->json([
            'ok' => true,
            'tipe' => $tipe,
            'tipe_label' => $tipe === 'gtk' ? 'GTK' : 'Peserta Didik',
            'hasil' => $hasil,
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
