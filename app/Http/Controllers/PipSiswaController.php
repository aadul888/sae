<?php

namespace App\Http\Controllers;

use App\Models\PipPenggunaanBukti;
use App\Models\PipPenggunaanDana;
use App\Models\PipRiwayat;
use App\Support\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Portal PIP untuk Peserta Didik & Orang Tua/Wali:
 * tab Riwayat PIP + Lapor Penggunaan Dana.
 * Siswa non-penerima PIP tetap bisa membuka halaman (tampil kosong).
 */
class PipSiswaController extends Controller
{
    private function resolveStudent(Request $request): array
    {
        $user = session('user');
        if (!$user) {
            abort(401, 'Sesi berakhir, silakan login kembali.');
        }

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!in_array($role, ['peserta_didik', 'orang_tua'], true)) {
            abort(403, 'Halaman ini khusus peserta didik dan orang tua/wali.');
        }

        $pdId = session('parent_active_student_id')
            ?? (is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null));

        $username = is_array($user) ? ($user['username'] ?? null) : ($user->username ?? null);
        if (!$pdId && $username && Table::exists('peserta_didik')) {
            $pdId = DB::table('peserta_didik')->where('nisn', $username)->orWhere('nik', $username)->value('peserta_didik_id');
        }

        $pd = ($pdId && Table::exists('peserta_didik'))
            ? DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first()
            : null;

        if (!$pd) {
            abort(403, 'Data peserta didik tidak ditemukan.');
        }
        $terdaftar = PipRiwayat::terdaftar($pd->peserta_didik_id, $pd->nisn ?? null);

        return [$role, $pd, $terdaftar];
    }

    public function index(Request $request)
    {
        [$role, $pd, $terdaftar] = $this->resolveStudent($request);

        $tab = $request->get('tab', 'riwayat');
        if (!in_array($tab, ['riwayat', 'laporan'], true)) {
            $tab = 'riwayat';
        }

        $riwayatList = $this->riwayatQuery($pd)
            ->orderByDesc('tanggal_sk')
            ->orderByDesc('id')
            ->get();

        $riwayatPemberian = $riwayatList->filter(
            fn($r) => ($r->jenis_sk ?? 'pemberian') === 'pemberian'
        )->values();

        $laporanList = collect();
        $laporanMap = collect();
        if (Table::exists('pip_penggunaan_dana')) {
            $laporanList = DB::table('pip_penggunaan_dana as d')
                ->where('d.peserta_didik_id', $pd->peserta_didik_id)
                ->orderByDesc('d.id')
                ->get();

            $buktiMap = Table::exists('pip_penggunaan_bukti')
                ? DB::table('pip_penggunaan_bukti')
                    ->whereIn('penggunaan_dana_id', $laporanList->pluck('id')->all())
                    ->orderBy('id')
                    ->get()
                    ->groupBy('penggunaan_dana_id')
                : collect();

            $laporanList->each(function ($laporan) use ($buktiMap) {
                $laporan->bukti_list = $buktiMap->get($laporan->id, collect())->values();
                $laporan->rincian_list = is_string($laporan->rincian)
                    ? (json_decode($laporan->rincian, true) ?: [])
                    : ($laporan->rincian ?: []);
            });

            // Laporan terbaru per SK Pemberian untuk deteksi status pelaporan.
            $laporanMap = $laporanList->groupBy('pip_riwayat_id')->map->first();
        }

        return view('dashboard.pip-siswa', [
            'role'             => $role,
            'pd'               => $pd,
            'terdaftar'        => $terdaftar,
            'tab'              => $tab,
            'riwayatList'      => $riwayatList,
            'riwayatPemberian' => $riwayatPemberian,
            'laporanList'      => $laporanList,
            'laporanMap'       => $laporanMap,
            'totalPemberian'   => (float) $riwayatPemberian->sum('nominal'),
            'totalDilaporkan'  => (float) $laporanList->sum(
                fn($laporan) => array_sum(array_column($laporan->rincian_list ?? [], 'nominal'))
            ),
            'kategoriPeruntukan' => [
                'Buku dan alat tulis',
                'Seragam dan perlengkapan sekolah',
                'Transportasi ke sekolah',
                'Biaya kursus (pendidikan formal)',
                'Biaya praktik tambahan / magang',
                'Uang saku peserta didik',
            ],
        ]);
    }

    public function storeLaporan(Request $request)
    {
        [$role, $pd] = $this->resolveStudent($request);

        // Nominal boleh dikirim dengan pemisah ribuan (contoh: 1.000) — normalisasi ke digit murni.
        $rincianInput = collect($request->input('rincian', []))
            ->map(function ($row) {
                if (is_array($row) && array_key_exists('nominal', $row)) {
                    $row['nominal'] = preg_replace('/[^\d]/', '', (string) $row['nominal']);
                }
                return $row;
            })->all();
        $request->merge(['rincian' => $rincianInput]);

        $data = $request->validate([
            'pip_riwayat_id'             => 'required|integer',
            'rincian'                    => 'required|array|min:1',
            'rincian.*.kategori'         => 'required|string|max:100',
            'rincian.*.nominal'          => 'nullable|numeric|min:0',
            'rincian.*.keterangan'       => 'nullable|string|max:255',
            'catatan'                    => 'nullable|string|max:1000',
            'bukti'                      => 'required|array|min:1',
            'bukti.*'                    => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'pernyataan_tanpa_potongan'  => 'accepted',
            'pernyataan_sesuai_ketentuan' => 'accepted',
            'pernyataan_kebenaran'       => 'accepted',
        ], [
            'pip_riwayat_id.required'              => 'Pilih SK Pemberian yang akan dilaporkan penggunaan dananya.',
            'rincian.required'                     => 'Isi minimal satu rincian penggunaan dana.',
            'rincian.*.nominal.numeric'            => 'Nominal penggunaan harus berupa angka.',
            'bukti.required'                       => 'Unggah minimal satu bukti penerimaan/penggunaan dana.',
            'bukti.*.mimes'                        => 'Bukti harus berupa JPG, PNG, atau PDF.',
            'bukti.*.max'                          => 'Ukuran tiap bukti maksimal 4 MB.',
            'pernyataan_tanpa_potongan.accepted'   => 'Pernyataan tidak ada potongan wajib disetujui.',
            'pernyataan_sesuai_ketentuan.accepted' => 'Pernyataan penggunaan sesuai ketentuan wajib disetujui.',
            'pernyataan_kebenaran.accepted'        => 'Pernyataan kebenaran data wajib disetujui.',
        ]);

        $riwayat = $this->riwayatQuery($pd)->where('id', (int) $data['pip_riwayat_id'])->first();
        if (!$riwayat || ($riwayat->jenis_sk ?? 'pemberian') !== 'pemberian') {
            return back()->withInput()->with('error', 'SK Pemberian tidak ditemukan.');
        }

        $rincian = [];
        foreach ($data['rincian'] as $row) {
            $nominal = (float) ($row['nominal'] ?? 0);
            if ($nominal <= 0) {
                continue;
            }
            $rincian[] = [
                'kategori'   => trim((string) $row['kategori']),
                'nominal'    => $nominal,
                'keterangan' => trim((string) ($row['keterangan'] ?? '')),
            ];
        }

        $totalRincian = array_sum(array_column($rincian, 'nominal'));
        $danaDiterima = (float) ($riwayat->nominal ?? 0);

        if (empty($rincian)) {
            return back()->withInput()->with('error', 'Isi nominal pada minimal satu rincian penggunaan dana.');
        }
        if ($danaDiterima > 0 && $totalRincian > $danaDiterima + 0.01) {
            return back()->withInput()->with('error', 'Total rincian penggunaan (Rp ' . number_format($totalRincian, 0, ',', '.') . ') melebihi dana yang diterima (Rp ' . number_format($danaDiterima, 0, ',', '.') . ').');
        }

        $pelapor = is_array(session('user'))
            ? (session('user.nama') ?? session('user.name'))
            : session('user.nama');
        $pelapor = $pelapor ?: ($pd->nama ?? 'Pengguna');

        $pernyataan = [
            'tanpa_potongan'  => true,
            'sesuai_ketentuan' => true,
            'kebenaran_data'  => true,
            'disetujui_pada'  => now()->toDateTimeString(),
        ];

        DB::transaction(function () use ($data, $pd, $riwayat, $rincian, $pernyataan, $pelapor, $role, $request) {
            $laporan = PipPenggunaanDana::create([
                'peserta_didik_id' => $pd->peserta_didik_id,
                'pip_riwayat_id'   => $riwayat->id,
                'jenis_sk'         => 'pemberian',
                'tahap_id'         => $riwayat->tahap_id ?? null,
                'tahap'            => $riwayat->tahap ?? null,
                'nominal'          => (float) ($riwayat->nominal ?? 0),
                'rincian'          => $rincian,
                'pernyataan'       => $pernyataan,
                'catatan'          => trim((string) ($data['catatan'] ?? '')) ?: null,
                'dilaporkan_oleh'  => $pelapor,
                'peran'            => $role,
                'status'           => 'diajukan',
            ]);

            foreach ($request->file('bukti', []) as $file) {
                if (!$file) {
                    continue;
                }
                $path = $file->store('pip-penggunaan/' . $pd->peserta_didik_id, 'public');
                PipPenggunaanBukti::create([
                    'penggunaan_dana_id' => $laporan->id,
                    'file_path'          => $path,
                    'nama_asli'          => Str::limit($file->getClientOriginalName(), 250),
                    'keterangan'         => null,
                ]);
            }
        });

        return redirect()
            ->route('dashboard.pip-siswa.index', ['tab' => 'laporan'])
            ->with('success', 'Laporan penggunaan dana PIP berhasil dikirim. Terima kasih.');
    }

    private function riwayatQuery($pd)
    {
        return DB::table('pip_riwayat')->where(function ($q) use ($pd) {
            $q->where('peserta_didik_id', $pd->peserta_didik_id);
            if (!empty($pd->nisn)) {
                $q->orWhere('nisn', $pd->nisn);
            }
        });
    }
}
