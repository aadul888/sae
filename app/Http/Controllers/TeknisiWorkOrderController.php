<?php

namespace App\Http\Controllers;

use App\Models\Sekolah;
use App\Models\SekolahMeta;
use App\Models\TeknisiWorkOrder;
use App\Services\SimpleXlsxExporter;
use App\Services\SimpleXlsxReader;
use App\Support\PeriodeLaporan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeknisiWorkOrderController extends Teknisi\TeknisiBaseController
{
    /** Kolom penanda pemilik baris pada tabel work order. */
    private const PTK_COLUMN = 'teknisi_ptk_id';

    public const KATEGORI = [
        'bangunan_fisik'      => 'Bangunan Fisik',
        'kelistrikan'         => 'Kelistrikan',
        'sanitasi_plumbing'   => 'Perairan / Plumbing',
        'kebersihan_lingkungan' => 'Kebersihan Lingkungan',
        'ac_pendingin'        => 'AC & Pendingin',
        'komputer'            => 'Komputer & IT',
        'internet_jaringan'   => 'Jaringan Internet',
        'audio_bel'           => 'Audio & Bel',
    ];

    public const URGENSI = [
        'rendah'  => 'Rendah',
        'normal'  => 'Normal',
        'darurat' => 'Darurat',
    ];

    public const STATUS = [
        'antrean'            => 'Antrean',
        'proses'             => 'Dikerjakan',
        'menunggu_sparepart' => 'Menunggu Part',
        'selesai'            => 'Selesai',
    ];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $q = $request->input('q');
        $kategori = $request->input('kategori');
        $status = $request->input('status');
        $urgensi = $request->input('urgensi');
        $perPage = (int) $request->input('perPage', $request->input('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 15;

        $query = TeknisiWorkOrder::with('teknisi')->latest('tanggal')->latest('id');
        $this->scopeRuangPj($query->getQuery(), $context, 'ruang_id', null, true);

        if (!empty($q)) {
            $query->where(function ($b) use ($q) {
                $b->where('nomor_wo', 'like', "%{$q}%")
                  ->orWhere('lokasi_unit', 'like', "%{$q}%")
                  ->orWhere('deskripsi_kerusakan', 'like', "%{$q}%")
                  ->orWhere('pelapor_nama', 'like', "%{$q}%");
            });
        }

        if (!empty($kategori)) {
            $query->where('kategori_perbaikan', $kategori);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($urgensi)) {
            $query->where('tingkat_urgensi', $urgensi);
        }

        $workOrders = $query->paginate($perPage)->withQueryString();

        $stat = fn ($w) => $this->scopeRuangPj($w->getQuery(), $context, 'ruang_id', null, true)->count();
        $stats = [
            'total_antrean' => $stat(TeknisiWorkOrder::where('status', 'antrean')),
            'total_proses'  => $stat(TeknisiWorkOrder::where('status', 'proses')),
            'total_selesai' => $stat(TeknisiWorkOrder::where('status', 'selesai')),
            'total_darurat' => $stat(TeknisiWorkOrder::where('tingkat_urgensi', 'darurat')->whereIn('status', ['antrean', 'proses'])),
        ];

        $daftarRuang = DB::table('sarpras_ruang')->orderBy('gedung', 'asc')->orderBy('nama_ruang', 'asc')->get();
        $daftarAset = DB::table('sarpras_aset')->select('id', 'nama_barang', 'kode_aset', 'ruang_id')->orderBy('nama_barang', 'asc')->get();

        return view('dashboard.teknisi.work-order', compact(
            'workOrders',
            'stats',
            'daftarRuang',
            'daftarAset',
            'q',
            'kategori',
            'status',
            'urgensi',
            'perPage'
        ));
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $request->validate([
            'tanggal'             => 'required|date',
            'lokasi_unit'         => 'required|string|max:100',
            'kategori_perbaikan'  => 'required|string|max:50',
            'deskripsi_kerusakan' => 'required|string',
            'tingkat_urgensi'     => 'required|in:rendah,normal,darurat',
            'pelapor_nama'        => 'nullable|string|max:100',
            'aset_id'             => 'nullable|integer|exists:sarpras_aset,id',
            'ruang_id'            => 'nullable|integer|exists:sarpras_ruang,id',
        ]);

        $nomorWo = $this->nomorBerikutnya();

        TeknisiWorkOrder::create([
            'nomor_wo'            => $nomorWo,
            'tanggal'             => $request->tanggal,
            'lokasi_unit'         => $request->lokasi_unit,
            'kategori_perbaikan'  => $request->kategori_perbaikan,
            'deskripsi_kerusakan' => $request->deskripsi_kerusakan,
            'tingkat_urgensi'     => $request->tingkat_urgensi,
            'pelapor_nama'        => $request->pelapor_nama,
            'aset_id'             => $request->aset_id,
            'ruang_id'            => $request->ruang_id,
            'user_id'             => $context['user_id'],
            'status'              => 'antrean',
        ]);

        return redirect()->route('dashboard.teknisi.work-order')
            ->with('success', "Work Order {$nomorWo} berhasil dibuat dan masuk ke antrean.");
    }

    public function updateStatus(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $request->validate([
            'status'              => 'required|in:antrean,proses,selesai,menunggu_sparepart',
            'teknisi_ptk_id'      => 'nullable|string',
            'tindakan_perbaikan'  => 'nullable|string',
            'estimasi_biaya_part' => 'nullable|numeric|min:0',
            'tgl_selesai'         => 'nullable|date',
        ]);

        $order = TeknisiWorkOrder::findOrFail($id);
        if (!$this->ownsRowRuang($order, $context)) {
            return $this->respond($request, false, 'Anda hanya dapat mengubah Work Order pada ruang yang menjadi tanggung jawab Anda.', 403);
        }

        $order->status = $request->status;
        $order->teknisi_ptk_id = $request->teknisi_ptk_id ?: $order->teknisi_ptk_id;
        $order->tindakan_perbaikan = $request->tindakan_perbaikan;
        $order->estimasi_biaya_part = $request->estimasi_biaya_part ?? 0;

        if ($request->status === 'selesai') {
            $order->tgl_selesai = $request->tgl_selesai ?: Carbon::today()->toDateString();
        }

        $order->save();

        return redirect()->route('dashboard.teknisi.work-order')
            ->with('success', "Status Work Order {$order->nomor_wo} berhasil diperbarui.");
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $order = TeknisiWorkOrder::findOrFail($id);
        if (!$this->ownsRowRuang($order, $context)) {
            return $this->respond($request, false, 'Anda hanya dapat menghapus Work Order pada ruang yang menjadi tanggung jawab Anda.', 403);
        }

        $order->delete();

        return redirect()->route('dashboard.teknisi.work-order')
            ->with('success', "Work Order {$order->nomor_wo} berhasil dihapus.");
    }

    /** Unduh template Excel work order. */
    public function template(Request $request)
    {
        [$res] = $this->boot($request);
        if ($res) return $res;

        $headers = ['tanggal', 'lokasi_unit', 'kategori_perbaikan', 'deskripsi_kerusakan', 'tingkat_urgensi', 'pelapor_nama'];
        $contoh  = [now()->toDateString(), 'Lab Komputer 1', 'komputer', 'Komputer tidak menyala', 'normal', 'Budi Santoso'];

        $path = storage_path('app/temp_teknisi_wo_' . uniqid() . '.xlsx');

        SimpleXlsxExporter::export($path, [
            'title'    => 'Format Import Work Order & Tiket Perbaikan',
            'subtitle' => 'Sistem Aplikasi Edukasi (SAE) — Diunduh ' . now()->translatedFormat('d F Y H:i'),
            'fields'   => [
                'Nomor WO'   => 'Dibuat otomatis oleh sistem.',
                'Tanggal'    => 'Format YYYY-MM-DD.',
                'Bidang'     => 'Isi salah satu: ' . implode(', ', array_keys(self::KATEGORI)) . '.',
                'Urgensi'    => 'Isi: rendah, normal, atau darurat.',
                'Per Akun'   => 'Baris hasil impor menjadi milik akun pengunggah.',
            ],
        ], $headers, [$contoh], ['sheetName' => 'Work Order']);

        return response()
            ->download($path, 'Format_Import_Work_Order.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    /** Impor Excel/CSV work order; tiap baris menjadi milik pengunggah. */
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

            $tanggal = $this->parseTanggal($row['tanggal'] ?? null) ?: now()->toDateString();
            $lokasi  = trim((string) ($row['lokasi_unit'] ?? ''));
            $deskripsi = trim((string) ($row['deskripsi_kerusakan'] ?? ''));

            if ($lokasi === '' && $deskripsi === '') {
                continue;
            }

            DB::table('teknisi_work_order')->insert([
                'nomor_wo'            => $this->nomorBerikutnya(),
                'tanggal'             => $tanggal,
                'lokasi_unit'         => $lokasi !== '' ? $lokasi : 'Unit',
                'kategori_perbaikan'  => array_key_exists((string) ($row['kategori_perbaikan'] ?? ''), self::KATEGORI)
                    ? $row['kategori_perbaikan'] : 'bangunan_fisik',
                'deskripsi_kerusakan' => $deskripsi !== '' ? $deskripsi : '-',
                'tingkat_urgensi'     => array_key_exists((string) ($row['tingkat_urgensi'] ?? ''), self::URGENSI)
                    ? $row['tingkat_urgensi'] : 'normal',
                'pelapor_nama'        => trim((string) ($row['pelapor_nama'] ?? '')) ?: null,
                'status'              => 'antrean',
                'user_id'             => $context['user_id'],
                'teknisi_ptk_id'      => $context['ptk_id'] ?? null,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
            $imported++;
        }

        $this->logImport($context, 'work-order', 'Work Order & Tiket Perbaikan', $file, $imported);

        $oleh = trim((string) ($context['nama'] ?? '')) ?: 'akun Anda';

        return response()->json([
            'status'  => 'success',
            'message' => "Import selesai: {$imported} baris masuk. Diunggah oleh {$oleh}.",
            'count'   => $imported,
        ]);
    }

    /** Cetak laporan work order (satu catatan per halaman A4). */
    public function cetak(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $mode = in_array(strtolower((string) $request->query('mode', 'ringkasan')), ['ringkasan', 'lengkap'], true)
            ? strtolower((string) $request->query('mode', 'ringkasan')) : 'ringkasan';
        $orientasi = in_array(strtolower((string) $request->query('orientasi', 'portrait')), ['portrait', 'landscape'], true)
            ? strtolower((string) $request->query('orientasi', 'portrait')) : 'portrait';

        $dari   = trim((string) $request->query('dari', ''));
        $sampai = trim((string) $request->query('sampai', ''));

        if ($request->filled('periode')) {
            $range = PeriodeLaporan::resolve(
                (string) $request->query('periode'),
                (int) $request->query('bulan', date('n')),
                (int) $request->query('tahun', date('Y')),
                (int) $request->query('triwulan', (int) ceil(date('n') / 3)),
                (string) $request->query('semester', date('n') >= 7 ? '1' : '2'),
                (string) $request->query('tahun_ajaran', ''),
                $request->query('tanggal')
            );
            $dari   = $range['start'];
            $sampai = $range['end'];
        } else {
            $range = [
                'tipe'    => 'kustom',
                'start'   => $dari,
                'end'     => $sampai,
                'label'   => ($dari !== '' || $sampai !== '')
                    ? Carbon::parse($dari ?: '1900-01-01')->translatedFormat('d F Y') . ' s.d. ' . Carbon::parse($sampai ?: now()->toDateString())->translatedFormat('d F Y')
                    : 'Seluruh Periode',
                'singkat' => ($dari !== '' || $sampai !== '') ? $dari . ' - ' . $sampai : 'Seluruh Periode',
            ];
        }

        $query = TeknisiWorkOrder::query();
        $this->scopeRuangPj($query->getQuery(), $context, 'ruang_id', null, true);
        if ($dari !== '')   $query->where('tanggal', '>=', $dari);
        if ($sampai !== '') $query->where('tanggal', '<=', $sampai);

        $rows = $query->orderByDesc('tanggal')->orderByDesc('id')->get();

        // Humanisasi nilai kode agar rekap & cetak terbaca.
        $human = function ($row) {
            $row->kategori_perbaikan = self::KATEGORI[$row->kategori_perbaikan] ?? $row->kategori_perbaikan;
            $row->tingkat_urgensi = self::URGENSI[$row->tingkat_urgensi] ?? $row->tingkat_urgensi;
            $row->status = self::STATUS[$row->status] ?? $row->status;
            return $row;
        };
        $rows = $rows->map($human);

        $fieldMap = [
            ['Tanggal', 'tanggal'],
            ['Bidang Perbaikan', 'kategori_perbaikan'],
            ['Lokasi / Unit', 'lokasi_unit'],
            ['Deskripsi Kerusakan', 'deskripsi_kerusakan'],
            ['Urgensi', 'tingkat_urgensi'],
            ['Status', 'status'],
            ['Pelapor', 'pelapor_nama'],
            ['Tindakan Perbaikan', 'tindakan_perbaikan'],
            ['Biaya Sparepart', 'estimasi_biaya_part'],
            ['Tgl Selesai', 'tgl_selesai'],
        ];
        $types = [
            'tanggal' => 'date',
            'tgl_selesai' => 'date',
            'estimasi_biaya_part' => 'decimal',
        ];

        $items = $rows->map(function ($row) use ($fieldMap) {
            $fields = [];
            foreach ($fieldMap as [$label, $key]) {
                $val = $row->{$key} ?? null;
                if (($key === 'tanggal' || $key === 'tgl_selesai') && $val) {
                    $val = Carbon::parse($val)->translatedFormat('d F Y');
                } elseif ($key === 'estimasi_biaya_part') {
                    $val = $val === null || $val === '' ? '—' : 'Rp ' . number_format((float) $val, 0, ',', '.');
                }
                $fields[] = ['label' => $label, 'value' => ($val === null || $val === '') ? '—' : (string) $val];
            }

            return (object) ['nomor' => $row->nomor_wo, 'fields' => $fields];
        });

        return view('dashboard.teknisi.laporan-cetak', [
            'modul'       => 'work-order',
            'title'       => 'REKAP WORK ORDER & TIKET PERBAIKAN',
            'items'       => $items,
            'rows'        => $rows,
            'types'       => $types,
            'fieldMap'    => $fieldMap,
            'range'       => $range,
            'mode'        => $mode,
            'orientasi'   => $orientasi,
            'cetakUrl'    => fn (array $p = []) => route('dashboard.teknisi.work-order.cetak', $p),
            'teknisiTtd'  => $this->teknisiTtd($context),
            'sekolah'     => Sekolah::first(),
            'sekolahMeta' => SekolahMeta::first(),
            'dicetakPada' => now(),
        ]);
    }

    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

    private function nomorBerikutnya(): string
    {
        $prefix = 'WO-' . date('Ym') . '-';
        $last = TeknisiWorkOrder::where('nomor_wo', 'like', "{$prefix}%")->orderByDesc('id')->first();
        $next = $last ? ((int) substr($last->nomor_wo, -4)) + 1 : 1;

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

    private function teknisiTtd(array $context): array
    {
        $dokumen = app(\App\Services\LaboranDokumenService::class);

        $petugas = ['nama' => $context['nama'] ?? '', 'nip' => null];
        $ptkId = $context['ptk_id'] ?? null;
        if ($ptkId && \App\Support\Table::exists('gtk')) {
            $petugas['nip'] = DB::table('gtk')->where('ptk_id', $ptkId)->value('nip') ?: null;
        }

        return [
            'petugas'       => $petugas,
            'kepalaTas'     => $dokumen->jabatan('KEPALA_TAS'),
            'kepalaSekolah' => $dokumen->kepalaSekolah(),
        ];
    }
}
