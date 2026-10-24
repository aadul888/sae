<?php

namespace App\Services;

use App\Models\KartuPelajarPengeluaran;
use App\Models\KartuPelajarPengaturan;
use App\Models\KartuPelajarPembayaran;
use App\Models\KartuPelajarRequest;
use App\Models\SekolahMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class KartuPelajarKeuanganService
{
    /**
     * Simpan Catatan Pengeluaran Baru.
     */
    public function storePengeluaran(Request $request, mixed $user): array
    {
        $userName = is_array($user) ? ($user['nama'] ?? 'Admin') : ($user->nama ?? 'Admin');
        $buktiPath = null;
        if ($request->hasFile('bukti_nota')) {
            $ext = $request->file('bukti_nota')->getClientOriginalExtension();
            $buktiPath = $request->file('bukti_nota')->storeAs('pengeluaran_kartu', 'nota_' . time() . '_' . uniqid() . '.' . $ext, 'public');
        }

        $jumlah = max(1, (int) ($request->jumlah ?: 1));
        $hargaSatuan = (int) $request->nominal;
        $totalNominal = $hargaSatuan * $jumlah;

        $payload = [
            'judul'           => trim($request->judul),
            'kategori'        => $request->kategori,
            'nominal'         => $totalNominal,
            'tanggal'         => $request->tanggal,
            'keterangan'      => trim((string) $request->keterangan),
            'bukti_nota_path' => $buktiPath,
            'petugas'         => $userName,
        ];

        if (Schema::hasColumn('kartu_pelajar_pengeluaran', 'jumlah')) {
            $payload['jumlah'] = $jumlah;
        }
        if (Schema::hasColumn('kartu_pelajar_pengeluaran', 'harga_satuan')) {
            $payload['harga_satuan'] = $hargaSatuan;
        }

        KartuPelajarPengeluaran::create($payload);

        return [
            'success' => true,
            'message' => 'Catatan pengeluaran berhasil disimpan (Total: Rp ' . number_format($totalNominal, 0, ',', '.') . ').',
        ];
    }

    /**
     * Update Catatan Pengeluaran.
     */
    public function updatePengeluaran(Request $request, int $id): array
    {
        $item = KartuPelajarPengeluaran::findOrFail($id);

        $buktiPath = $item->bukti_nota_path;
        if ($request->hasFile('bukti_nota')) {
            if ($buktiPath && Storage::disk('public')->exists($buktiPath)) {
                Storage::disk('public')->delete($buktiPath);
            }
            $ext = $request->file('bukti_nota')->getClientOriginalExtension();
            $buktiPath = $request->file('bukti_nota')->storeAs('pengeluaran_kartu', 'nota_' . time() . '_' . uniqid() . '.' . $ext, 'public');
        }

        $jumlah = max(1, (int) ($request->jumlah ?: 1));
        $hargaSatuan = (int) $request->nominal;
        $totalNominal = $hargaSatuan * $jumlah;

        $item->judul = trim($request->judul);
        $item->kategori = $request->kategori;
        $item->nominal = $totalNominal;
        $item->tanggal = $request->tanggal;
        $item->keterangan = trim((string) $request->keterangan);
        $item->bukti_nota_path = $buktiPath;

        if (Schema::hasColumn('kartu_pelajar_pengeluaran', 'jumlah')) {
            $item->jumlah = $jumlah;
        }
        if (Schema::hasColumn('kartu_pelajar_pengeluaran', 'harga_satuan')) {
            $item->harga_satuan = $hargaSatuan;
        }

        $item->save();

        return [
            'success' => true,
            'message' => 'Catatan pengeluaran berhasil diperbarui (Total: Rp ' . number_format($totalNominal, 0, ',', '.') . ').',
        ];
    }

    /**
     * Hapus Catatan Pengeluaran.
     */
    public function destroyPengeluaran(int $id): array
    {
        $item = KartuPelajarPengeluaran::findOrFail($id);
        if ($item->bukti_nota_path && Storage::disk('public')->exists($item->bukti_nota_path)) {
            Storage::disk('public')->delete($item->bukti_nota_path);
        }
        $item->delete();

        return [
            'success' => true,
            'message' => 'Catatan pengeluaran berhasil dihapus.',
        ];
    }

    /**
     * Ambil data untuk laporan keuangan resmi cetak.
     */
    public function getLaporanKeuanganData(Request $request, array $sekolahInfo): array
    {
        $dari = $request->get('dari');
        $sampai = $request->get('sampai');

        $pemasukanQuery = DB::table('kartu_pelajar_pembayaran as kp')
            ->join('peserta_didik as pd', 'kp.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->where('kp.status_pembayaran', 'lunas')
            ->select('kp.*', 'pd.nama', 'pd.nisn', 'pd.nama_rombel');

        $pengeluaranQuery = DB::table('kartu_pelajar_pengeluaran');

        if ($dari) {
            $pemasukanQuery->whereDate('kp.tanggal_bayar', '>=', $dari);
            $pengeluaranQuery->whereDate('tanggal', '>=', $dari);
        }
        if ($sampai) {
            $pemasukanQuery->whereDate('kp.tanggal_bayar', '<=', $sampai);
            $pengeluaranQuery->whereDate('tanggal', '<=', $sampai);
        }

        $pemasukanList = $pemasukanQuery->orderBy('kp.tanggal_bayar', 'asc')->get();
        $pengeluaranList = $pengeluaranQuery->orderBy('tanggal', 'asc')->get();

        $totalPemasukan = $pemasukanList->sum('nominal');
        $totalPengeluaran = $pengeluaranList->sum('nominal');
        $saldoBersih = $totalPemasukan - $totalPengeluaran;

        $kategoriList = KartuPelajarPengeluaran::getKategoriList();

        return compact(
            'pemasukanList',
            'pengeluaranList',
            'totalPemasukan',
            'totalPengeluaran',
            'saldoBersih',
            'sekolahInfo',
            'kategoriList',
            'dari',
            'sampai'
        );
    }

    /**
     * Autocomplete peserta didik untuk form request cetak.
     */
    public function cariSiswa(string $q): Collection
    {
        return DB::table('peserta_didik as pd')
            ->leftJoin('kartu_pelajar_pembayaran as kp', 'pd.peserta_didik_id', '=', 'kp.peserta_didik_id')
            ->where(function ($s) use ($q) {
                $s->where('pd.nama', 'LIKE', "%{$q}%")
                    ->orWhere('pd.nisn', 'LIKE', "%{$q}%")
                    ->orWhere('pd.nipd', 'LIKE', "%{$q}%");
            })
            ->orderBy('pd.nama')
            ->limit(15)
            ->get([
                'pd.peserta_didik_id',
                'pd.nama',
                'pd.nisn',
                'pd.nama_rombel',
                DB::raw("COALESCE(kp.status_pembayaran, 'belum_bayar') as status_pembayaran"),
                'kp.metode_pembayaran',
            ]);
    }

    /**
     * Simpan request antrean cetak kartu pelajar.
     */
    public function storeRequest(array $data, mixed $user): array
    {
        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $data['peserta_didik_id'])->first();

        // Cek duplicate request
        $existingRequest = KartuPelajarRequest::where('peserta_didik_id', $data['peserta_didik_id'])
            ->when(
                Schema::hasColumn('kartu_pelajar_request', 'request_date'),
                fn($query) => $query->where('request_date', now()->toDateString()),
                fn($query) => $query->whereDate('created_at', now()->toDateString())
            )
            ->where('status', 'menunggu')
            ->first();

        if ($existingRequest) {
            return [
                'success' => false,
                'message' => "Peserta didik {$pd->nama} sudah memiliki request antrean berstatus Menunggu pada tanggal hari ini.",
                'status_code' => 422,
            ];
        }

        // Sinkronisasi data pembayaran
        $settings = KartuPelajarPengaturan::getSettings();
        $record = KartuPelajarPembayaran::firstOrNew(['peserta_didik_id' => $pd->peserta_didik_id]);
        $record->nisn = $pd->nisn;

        $hasPaymentChange = false;
        if (!empty($data['status_pembayaran'])) {
            $record->status_pembayaran = $data['status_pembayaran'];
            if ($data['status_pembayaran'] === 'lunas') {
                if (empty($record->tanggal_bayar)) {
                    $record->tanggal_bayar = now()->toDateString();
                }
            } else {
                $record->tanggal_bayar = null;
            }
            $hasPaymentChange = true;
        }

        if (!empty($data['metode_pembayaran'])) {
            $record->metode_pembayaran = $data['metode_pembayaran'];
            $hasPaymentChange = true;
        }

        if ($hasPaymentChange) {
            if (empty($record->nominal)) {
                $record->nominal = (int) ($settings->biaya_standar ?? 25000);
            }
            $record->save();
        }

        $metodeFinal = !empty($data['metode_pembayaran'])
            ? $data['metode_pembayaran']
            : ($record->metode_pembayaran ?: null);

        $payload = [
            'peserta_didik_id' => $pd->peserta_didik_id,
            'nisn'             => $pd->nisn,
            'jenis_request'    => $data['jenis_request'],
            'alasan'           => trim((string) ($data['alasan'] ?? '')),
            'status'           => 'menunggu',
            'diminta_oleh'     => is_array($user) ? ($user['nama'] ?? 'Admin') : ($user->nama ?? 'Admin'),
        ];
        if (Schema::hasColumn('kartu_pelajar_request', 'metode_pembayaran')) {
            $payload['metode_pembayaran'] = $metodeFinal;
        }
        if (Schema::hasColumn('kartu_pelajar_request', 'request_date')) {
            $payload['request_date'] = now()->toDateString();
        }

        KartuPelajarRequest::create($payload);

        return [
            'success' => true,
            'message' => "Request cetak kartu untuk {$pd->nama} berhasil ditambahkan.",
        ];
    }

    /**
     * Ubah status request cetak kartu.
     */
    public function updateStatusRequest(int $id, array $data): array
    {
        $item = KartuPelajarRequest::findOrFail($id);

        $itemDate = $item->request_date ?? ($item->created_at ? $item->created_at->toDateString() : now()->toDateString());
        $isDuplicate = KartuPelajarRequest::where('id', '!=', $item->id)
            ->where('peserta_didik_id', $item->peserta_didik_id)
            ->where('status', $data['status'])
            ->when(
                Schema::hasColumn('kartu_pelajar_request', 'request_date'),
                fn($query) => $query->where('request_date', $itemDate),
                fn($query) => $query->whereDate('created_at', $itemDate)
            )
            ->exists();

        if ($isDuplicate) {
            return [
                'success' => false,
                'message' => "Sudah ada request lain untuk siswa ini pada tanggal tersebut dengan status " . (KartuPelajarRequest::STATUS[$data['status']] ?? $data['status']) . ".",
                'status_code' => 422,
            ];
        }

        $item->status = $data['status'];
        if (array_key_exists('catatan_admin', $data)) {
            $item->catatan_admin = $data['catatan_admin'];
        }
        $item->selesai_at = $data['status'] === 'selesai' ? now() : null;
        $item->save();

        if ($data['status'] === 'selesai') {
            $record = KartuPelajarPembayaran::firstOrNew(['peserta_didik_id' => $item->peserta_didik_id]);
            $record->nisn = $item->nisn;
            $record->status_cetak = 'sudah_cetak';
            $record->tanggal_cetak = $record->tanggal_cetak ?: now();
            $record->save();
        } else {
            $hasOtherSelesai = KartuPelajarRequest::where('peserta_didik_id', $item->peserta_didik_id)
                ->where('id', '!=', $item->id)
                ->where('status', 'selesai')
                ->exists();
            if (!$hasOtherSelesai) {
                $record = KartuPelajarPembayaran::where('peserta_didik_id', $item->peserta_didik_id)->first();
                if ($record && $record->status_cetak === 'sudah_cetak') {
                    $record->status_cetak = 'belum_cetak';
                    $record->save();
                }
            }
        }

        return [
            'success' => true,
            'message' => 'Status request diperbarui menjadi ' . KartuPelajarRequest::STATUS[$data['status']] . '.',
        ];
    }

    /**
     * Hapus record request cetak kartu.
     */
    public function destroyRequest(int $id): array
    {
        $item = KartuPelajarRequest::findOrFail($id);
        $wasSelesai = ($item->status === 'selesai');
        $pdId = $item->peserta_didik_id;
        $item->delete();

        if ($wasSelesai) {
            $hasOtherSelesai = KartuPelajarRequest::where('peserta_didik_id', $pdId)
                ->where('status', 'selesai')
                ->exists();
            if (!$hasOtherSelesai) {
                $record = KartuPelajarPembayaran::where('peserta_didik_id', $pdId)->first();
                if ($record && $record->status_cetak === 'sudah_cetak') {
                    $record->status_cetak = 'belum_cetak';
                    $record->save();
                }
            }
        }

        return [
            'success' => true,
            'message' => 'Request cetak dihapus.',
        ];
    }

    /**
     * Dapatkan data cetak lembar serah terima / pengambilan kartu.
     */
    public function getPengambilanData(Request $request, array $sekolahInfo): array
    {
        $tanggalCetakList = DB::table('kartu_pelajar_pembayaran')
            ->where('status_cetak', 'sudah_cetak')
            ->whereNotNull('tanggal_cetak')
            ->select(DB::raw('DATE(tanggal_cetak) as tgl'), DB::raw('COUNT(*) as total'))
            ->groupBy('tgl')
            ->orderByDesc('tgl')
            ->get();

        $latestDate = $tanggalCetakList->first()?->tgl ?: now()->toDateString();
        $tanggalCetak = $request->get('tanggal_cetak', $latestDate);
        $rombel = $request->get('rombel');

        $query = DB::table('kartu_pelajar_pembayaran as kp')
            ->join('peserta_didik as pd', 'kp.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->where('kp.status_cetak', 'sudah_cetak');

        if ($tanggalCetak) {
            $query->whereDate('kp.tanggal_cetak', $tanggalCetak);
        }

        if ($rombel) {
            $query->where('pd.nama_rombel', $rombel);
        }

        $students = $query->orderBy('pd.nama_rombel', 'asc')
            ->orderBy('pd.nama', 'asc')
            ->select('kp.*', 'pd.nama', 'pd.nisn', 'pd.nipd', 'pd.nama_rombel')
            ->get();

        $sekolah = Schema::hasTable('sekolah') ? DB::table('sekolah')->first() : null;
        $sekolahMeta = Schema::hasTable('sekolah_meta') ? SekolahMeta::first() : null;

        $waliGtk = null;
        if ($rombel) {
            $activeRombelRow = DB::table('rombongan_belajar')->where('nama', $rombel)->first();
            if ($activeRombelRow) {
                if (!empty($activeRombelRow->ptk_id)) {
                    $waliGtk = DB::table('gtk')->where('ptk_id', $activeRombelRow->ptk_id)->first();
                }
                if (!$waliGtk && !empty($activeRombelRow->ptk_id_str)) {
                    $waliGtk = DB::table('gtk')->where('nama', $activeRombelRow->ptk_id_str)->first();
                }
            }
        }

        $rombelList = DB::table('rombongan_belajar')
            ->where('jenis_rombel', '1')
            ->orderBy('tingkat_pendidikan_id', 'asc')
            ->orderBy('nama', 'asc')
            ->pluck('nama');

        return compact(
            'students',
            'tanggalCetak',
            'rombel',
            'sekolahInfo',
            'sekolah',
            'sekolahMeta',
            'waliGtk',
            'rombelList',
            'tanggalCetakList'
        );
    }
}
