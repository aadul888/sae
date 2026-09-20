<?php

namespace App\Http\Controllers;

use App\Models\TeknisiPemeliharaan;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TeknisiPemeliharaanController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->input('q');
        $kategori = $request->input('kategori');
        $status = $request->input('status');
        $frekuensi = $request->input('frekuensi');
        $perPage = (int) $request->input('per_page', 15);

        $query = TeknisiPemeliharaan::latest('tgl_jadwal')->latest('id');

        if (!empty($q)) {
            $query->where(function ($b) use ($q) {
                $b->where('kode_pemeliharaan', 'like', "%{$q}%")
                  ->orWhere('nama_kegiatan', 'like', "%{$q}%")
                  ->orWhere('lokasi_aset', 'like', "%{$q}%")
                  ->orWhere('penanggung_jawab', 'like', "%{$q}%");
            });
        }

        if (!empty($kategori)) {
            $query->where('kategori', $kategori);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($frekuensi)) {
            $query->where('frekuensi', $frekuensi);
        }

        $pemeliharaan = $query->paginate($perPage)->withQueryString();

        $stats = [
            'terjadwal' => TeknisiPemeliharaan::where('status', 'terjadwal')->count(),
            'proses'    => TeknisiPemeliharaan::where('status', 'proses')->count(),
            'selesai'   => TeknisiPemeliharaan::where('status', 'selesai')->count(),
            'tertunda'  => TeknisiPemeliharaan::where('status', 'tertunda')->count(),
        ];

        return view('dashboard.teknisi.pemeliharaan', compact(
            'pemeliharaan',
            'stats',
            'q',
            'kategori',
            'status',
            'frekuensi',
            'perPage'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kegiatan'    => 'required|string|max:150',
            'kategori'         => 'required|string|max:50',
            'lokasi_aset'      => 'required|string|max:100',
            'frekuensi'        => 'required|string|max:50',
            'tgl_jadwal'       => 'required|date',
            'penanggung_jawab' => 'nullable|string|max:100',
            'biaya'            => 'nullable|numeric|min:0',
        ]);

        $prefix = 'PM-' . date('Ym') . '-';
        $lastItem = TeknisiPemeliharaan::where('kode_pemeliharaan', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();
        $nextNum = $lastItem ? ((int) substr($lastItem->kode_pemeliharaan, -4)) + 1 : 1;
        $kodePm = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        TeknisiPemeliharaan::create([
            'kode_pemeliharaan' => $kodePm,
            'nama_kegiatan'    => $request->nama_kegiatan,
            'kategori'         => $request->kategori,
            'lokasi_aset'      => $request->lokasi_aset,
            'frekuensi'        => $request->frekuensi,
            'tgl_jadwal'       => $request->tgl_jadwal,
            'penanggung_jawab' => $request->penanggung_jawab,
            'biaya'            => $request->biaya ?? 0,
            'status'           => 'terjadwal',
        ]);

        return redirect()->route('dashboard.teknisi.pemeliharaan')
            ->with('success', "Jadwal pemeliharaan {$kodePm} berhasil didaftarkan.");
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status'         => 'required|in:terjadwal,proses,selesai,tertunda',
            'tgl_realisasi'  => 'nullable|date',
            'catatan_hasil'  => 'nullable|string',
            'biaya'          => 'nullable|numeric|min:0',
        ]);

        $pm = TeknisiPemeliharaan::findOrFail($id);
        $pm->status = $request->status;
        $pm->catatan_hasil = $request->catatan_hasil;
        if ($request->has('biaya')) {
            $pm->biaya = $request->biaya;
        }

        if ($request->status === 'selesai') {
            $pm->tgl_realisasi = $request->tgl_realisasi ?: Carbon::today()->toDateString();
        }

        $pm->save();

        return redirect()->route('dashboard.teknisi.pemeliharaan')
            ->with('success', "Realisasi pemeliharaan {$pm->kode_pemeliharaan} berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $pm = TeknisiPemeliharaan::findOrFail($id);
        $pm->delete();

        return redirect()->route('dashboard.teknisi.pemeliharaan')
            ->with('success', "Data pemeliharaan {$pm->kode_pemeliharaan} berhasil dihapus.");
    }
}
