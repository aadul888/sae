<?php

namespace App\Http\Controllers;

use App\Models\PerpusAnggota;
use App\Models\RolePermission;
use App\Services\QrCodeService;
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

        $request->validate([
            'tipe' => 'required|in:siswa,gtk,umum',
            'nama' => 'required|string|max:255',
            'nisn_nip' => 'nullable|string|max:40',
        ]);

        $tipe = $request->tipe;
        $siswaId = null;
        $ptkId = null;
        $rombel = $request->rombel_atau_unit;

        // Tarik data otomatis bila NISN/NIP merujuk ke master data sekolah
        if ($tipe === 'siswa' && $request->nisn_nip && Table::exists('peserta_didik')) {
            $pd = DB::table('peserta_didik')->where('nisn', $request->nisn_nip)->first();
            if ($pd) {
                $siswaId = $pd->peserta_didik_id ?? null;
                $rombel = $rombel ?: ($pd->nama_rombel ?? null);
            }
        } elseif ($tipe === 'gtk' && $request->nisn_nip) {
            $ptk = DB::table('gtk')
                ->when(\Illuminate\Support\Facades\Schema::hasColumn('gtk', 'nip'), fn($q) => $q->where('nip', $request->nisn_nip))
                ->first();
            $ptkId = $ptk->ptk_id ?? null;
        }

        $nisnNip = $request->nisn_nip ?: null;
        if ($nisnNip && PerpusAnggota::where('nisn_nip', $nisnNip)->exists()) {
            return back()->withInput()->with('error', 'NISN/NIP tersebut sudah terdaftar sebagai anggota.');
        }

        PerpusAnggota::create([
            'nomor_anggota' => PerpusAnggota::generateNomor($tipe),
            'token_kartu' => Str::random(32),
            'tipe' => $tipe,
            'siswa_id' => $siswaId,
            'ptk_id' => $ptkId,
            'nisn_nip' => $nisnNip,
            'nama' => $request->nama,
            'rombel_atau_unit' => $rombel,
            'jenis_kelamin' => $request->jenis_kelamin,
            'telepon' => $request->telepon,
            'email' => $request->email,
            'password' => $request->password ? Hash::make($request->password) : null,
            'is_active' => true,
            'tgl_daftar' => now()->toDateString(),
        ]);

        return redirect()->route('dashboard.perpustakaan.anggota.index')->with('success', 'Anggota perpustakaan berhasil didaftarkan.');
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

    /** Kartu anggota digital (QR). */
    public function kartu(int $id)
    {
        if ($redirect = $this->guard()) return $redirect;
        $member = PerpusAnggota::findOrFail($id);
        $qrSvg = QrCodeService::generateSvg($member->verifyUrl(), 220, 1);

        return view('dashboard.perpus.anggota-kartu', compact('member', 'qrSvg'));
    }

    /** Halaman publik verifikasi token (reuse view publik). */
    public function cetakKartu(Request $request)
    {
        if ($redirect = $this->guard()) return $redirect;

        $ids = array_filter((array) $request->query('ids', []));
        $members = PerpusAnggota::when($ids, fn($q) => $q->whereIn('id', $ids), fn($q) => $q->limit(50))
            ->orderBy('nama')
            ->get()
            ->each(function ($m) {
                $m->qr_svg = QrCodeService::generateSvg($m->verifyUrl(), 90, 0);
            });

        return view('dashboard.perpus.anggota-kartu-cetak', compact('members'));
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
