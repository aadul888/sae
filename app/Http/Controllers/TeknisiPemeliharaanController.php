<?php

namespace App\Http\Controllers;

use App\Models\TeknisiPemeliharaan;
use App\Services\SimpleXlsxExporter;
use App\Services\SimpleXlsxReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeknisiPemeliharaanController extends Teknisi\TeknisiBaseController
{
    public const KATEGORI = [
        'bangunan'       => 'Bangunan & Fisik',
        'kelistrikan'    => 'Kelistrikan & Panel',
        'perairan'       => 'Perairan & Plumbing',
        'kebersihan'     => 'Kebersihan Lingkungan',
        'ac_pendingin'   => 'AC & Pendingin',
        'it_jaringan'    => 'IT & Jaringan',
    ];

    public const FREKUENSI = [
        'mingguan'   => 'Mingguan',
        'bulanan'    => 'Bulanan',
        'triwulan'   => 'Triwulan',
        'semesteran' => 'Semesteran',
        'tahunan'    => 'Tahunan',
    ];

    public const STATUS = [
        'terjadwal' => 'Terjadwal',
        'proses'    => 'Dalam Proses',
        'selesai'   => 'Selesai',
        'tertunda'  => 'Tertunda',
    ];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $q = $request->input('q');
        $kategori = $request->input('kategori');
        $status = $request->input('status');
        $frekuensi = $request->input('frekuensi');
        $perPage = (int) $request->input('per_page', 15);

        $query = TeknisiPemeliharaan::latest('tgl_jadwal')->latest('id');
        // Tabel pemeliharaan hanya punya penanda `user_id` (tanpa kolom ptk).
        $this->scopeOwned($query->getQuery(), $context, null, null);

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
            'terjadwal' => $this->scopeOwned(TeknisiPemeliharaan::where('status', 'terjadwal')->getQuery(), $context, null, null)->count(),
            'proses'    => $this->scopeOwned(TeknisiPemeliharaan::where('status', 'proses')->getQuery(), $context, null, null)->count(),
            'selesai'   => $this->scopeOwned(TeknisiPemeliharaan::where('status', 'selesai')->getQuery(), $context, null, null)->count(),
            'tertunda'  => $this->scopeOwned(TeknisiPemeliharaan::where('status', 'tertunda')->getQuery(), $context, null, null)->count(),
        ];

        $daftarRuang = \Illuminate\Support\Facades\DB::table('sarpras_ruang')->orderBy('gedung', 'asc')->orderBy('nama_ruang', 'asc')->get();
        $daftarAset = \Illuminate\Support\Facades\DB::table('sarpras_aset')->select('id', 'nama_barang', 'kode_aset', 'kategori')->orderBy('nama_barang', 'asc')->get();
        return view('dashboard.teknisi.pemeliharaan', compact(
            'pemeliharaan',
            'stats',
            'daftarRuang',
            'daftarAset',
            'q',
            'kategori',
            'status',
            'frekuensi',
            'perPage'
        ));
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

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
            'user_id'          => $context['user_id'],
            'status'           => 'terjadwal',
        ]);

        return redirect()->route('dashboard.teknisi.pemeliharaan')
            ->with('success', "Jadwal pemeliharaan {$kodePm} berhasil didaftarkan.");
    }

    /** Unduh template Excel jadwal pemeliharaan preventif. */
    public function template(Request $request)
    {
        [$res] = $this->boot($request);
        if ($res) return $res;

        $headers = ['nama_kegiatan', 'kategori', 'lokasi_aset', 'frekuensi', 'tgl_jadwal',
            'penanggung_jawab', 'biaya', 'status', 'catatan_hasil'];
        $contoh = ['Servis AC Ruang Guru', 'ac_pendingin', 'Ruang Guru', 'bulanan',
            now()->toDateString(), 'Budi Santoso', '150000', 'terjadwal', '-'];

        $path = storage_path('app/temp_teknisi_pm_' . uniqid() . '.xlsx');

        SimpleXlsxExporter::export($path, [
            'title'    => 'Format Import Jadwal Pemeliharaan Preventif',
            'subtitle' => 'Sistem Aplikasi Edukasi (SAE) — Diunduh ' . now()->translatedFormat('d F Y H:i'),
            'fields'   => [
                'Kode PM'    => 'Dibuat otomatis oleh sistem.',
                'Kategori'   => 'Isi salah satu: ' . implode(', ', array_keys(self::KATEGORI)) . '.',
                'Frekuensi'  => 'Isi salah satu: ' . implode(', ', array_keys(self::FREKUENSI)) . '.',
                'Status'     => 'Isi salah satu: ' . implode(', ', array_keys(self::STATUS)) . '.',
                'Tanggal'    => 'Format YYYY-MM-DD.',
                'Per Akun'   => 'Baris hasil impor menjadi milik akun pengunggah.',
            ],
        ], $headers, [$contoh], ['sheetName' => 'Pemeliharaan PM']);

        return response()
            ->download($path, 'Format_Import_Pemeliharaan.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    /** Impor Excel/CSV jadwal pemeliharaan; tiap baris menjadi milik pengunggah. */
    public function import(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $request->validate(['file' => 'required|file|max:10240']);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'], true)) {
            return response()->json(['status' => 'error', 'message' => 'Format berkas tidak didukung.'], 422);
        }

        try {
            $rows = SimpleXlsxReader::readFile($file->getRealPath());
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal membaca berkas: ' . $e->getMessage()], 422);
        }

        $imported = 0;

        foreach ($rows as $raw) {
            $row = [];
            foreach ($raw as $k => $v) {
                $key = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', (string) $k))));
                $row[$key] = is_string($v) ? trim($v) : $v;
            }

            $namaKegiatan = trim((string) ($row['nama_kegiatan'] ?? ''));
            $lokasi = trim((string) ($row['lokasi_aset'] ?? ''));
            if ($namaKegiatan === '' && $lokasi === '') {
                continue;
            }

            $kategori = array_key_exists((string) ($row['kategori'] ?? ''), self::KATEGORI)
                ? $row['kategori'] : 'kelistrikan';
            $frekuensi = array_key_exists((string) ($row['frekuensi'] ?? ''), self::FREKUENSI)
                ? $row['frekuensi'] : 'bulanan';
            $status = array_key_exists((string) ($row['status'] ?? ''), self::STATUS)
                ? $row['status'] : 'terjadwal';

            TeknisiPemeliharaan::create([
                'kode_pemeliharaan' => $this->nomorPemeliharaan(),
                'nama_kegiatan'     => $namaKegiatan !== '' ? $namaKegiatan : 'Kegiatan Pemeliharaan',
                'kategori'          => $kategori,
                'lokasi_aset'       => $lokasi !== '' ? $lokasi : 'Unit',
                'frekuensi'         => $frekuensi,
                'tgl_jadwal'        => $this->parseTanggal($row['tgl_jadwal'] ?? null) ?: now()->toDateString(),
                'penanggung_jawab'  => trim((string) ($row['penanggung_jawab'] ?? '')) ?: null,
                'biaya'             => is_numeric($row['biaya'] ?? null) ? (float) $row['biaya'] : 0,
                'catatan_hasil'     => trim((string) ($row['catatan_hasil'] ?? '')) ?: null,
                'status'            => $status,
                'user_id'           => $context['user_id'],
            ]);
            $imported++;
        }

        $this->logImport($context, 'pemeliharaan', 'Jadwal Pemeliharaan Preventif', $file, $imported);

        $oleh = trim((string) ($context['nama'] ?? '')) ?: 'akun Anda';

        return response()->json([
            'status'  => 'success',
            'message' => "Import selesai: {$imported} baris masuk. Diunggah oleh {$oleh}.",
            'count'   => $imported,
        ]);
    }

    private function nomorPemeliharaan(): string
    {
        $prefix = 'PM-' . date('Ym') . '-';
        $last = TeknisiPemeliharaan::where('kode_pemeliharaan', 'like', "{$prefix}%")
            ->orderByDesc('id')->first();
        $next = $last ? ((int) substr($last->kode_pemeliharaan, -4)) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    private function parseTanggal($value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^\d+(\.\d+)?$/', $raw) && (float) $raw > 20000 && (float) $raw < 80000) {
            return Carbon::createFromDate(1899, 12, 30)->addDays((int) $raw)->toDateString();
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function updateStatus(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $request->validate([
            'status'         => 'required|in:terjadwal,proses,selesai,tertunda',
            'tgl_realisasi'  => 'nullable|date',
            'catatan_hasil'  => 'nullable|string',
            'biaya'          => 'nullable|numeric|min:0',
        ]);

        $pm = TeknisiPemeliharaan::findOrFail($id);
        if (!$this->ownsRow($pm, $context, null)) {
            return $this->respond($request, false, 'Anda hanya dapat mengubah jadwal milik sendiri.', 403);
        }
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

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $pm = TeknisiPemeliharaan::findOrFail($id);
        if (!$this->ownsRow($pm, $context, null)) {
            return $this->respond($request, false, 'Anda hanya dapat menghapus jadwal milik sendiri.', 403);
        }
        $pm->delete();

        return redirect()->route('dashboard.teknisi.pemeliharaan')
            ->with('success', "Data pemeliharaan {$pm->kode_pemeliharaan} berhasil dihapus.");
    }
}
