<?php

namespace App\Services;

use App\Models\KartuPelajarPengeluaran;
use App\Models\KartuPelajarPengaturan;
use App\Models\KartuPelajarPembayaran;
use App\Models\KartuPelajarRequest;
use App\Models\PesertaDidikMeta;
use App\Models\SekolahMeta;
use App\Models\User;
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
     * Autocomplete peserta didik untuk form request cetak dan pencarian scanner barcode/NISN.
     */
    public function cariSiswa(string $q): Collection
    {
        $cleanQ = trim($q);
        if (preg_match('/\/v(?:erify)?\/([A-Za-z0-9]+)/i', $cleanQ, $matches)) {
            $cleanQ = $matches[1];
        } elseif (preg_match('/nisn=([A-Za-z0-9]+)/i', $cleanQ, $matches)) {
            $cleanQ = $matches[1];
        }

        return DB::table('peserta_didik as pd')
            ->leftJoin('kartu_pelajar_pembayaran as kp', 'pd.peserta_didik_id', '=', 'kp.peserta_didik_id')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->where(function ($s) use ($cleanQ) {
                $s->where('pd.nama', 'LIKE', "%{$cleanQ}%")
                    ->orWhere('pd.nisn', 'LIKE', "%{$cleanQ}%")
                    ->orWhere('pd.nipd', 'LIKE', "%{$cleanQ}%");
            })
            ->orderByRaw("CASE WHEN pd.nisn = ? THEN 0 WHEN pd.nipd = ? THEN 1 ELSE 2 END", [$cleanQ, $cleanQ])
            ->orderBy('pd.nama')
            ->limit(15)
            ->get([
                'pd.peserta_didik_id',
                'pd.nama',
                'pd.nisn',
                'pd.nipd',
                'pd.nama_rombel',
                'pdm.foto_path',
                'pdm.rfid_uid',
                DB::raw("COALESCE(kp.status_pembayaran, 'belum_bayar') as status_pembayaran"),
                'kp.metode_pembayaran',
            ])
            ->transform(function ($item) {
                $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
                return $item;
            });
    }

    /**
     * Hitung jumlah siswa dan ketersediaan foto di rombel untuk preview tambah request masal.
     */
    public function countRombelFoto(string $rombelId): array
    {
        $rombel = DB::table('rombongan_belajar')
            ->where('rombongan_belajar_id', $rombelId)
            ->orWhere('nama', $rombelId)
            ->first();

        if (!$rombel) {
            return [
                'success' => false,
                'message' => 'Rombongan belajar tidak ditemukan.',
            ];
        }

        $baseQuery = DB::table('peserta_didik as pd')
            ->where(function ($w) use ($rombel) {
                $w->where('pd.rombongan_belajar_id', $rombel->rombongan_belajar_id)
                    ->orWhere('pd.nama_rombel', $rombel->nama);
            });

        $totalSiswa = (clone $baseQuery)->count();

        $totalFoto = 0;
        if (Schema::hasTable('peserta_didik_meta')) {
            $totalFoto = (clone $baseQuery)->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('peserta_didik_meta as pdm')
                    ->whereColumn('pdm.peserta_didik_id', 'pd.peserta_didik_id')
                    ->whereNotNull('pdm.foto_path')
                    ->where('pdm.foto_path', '!=', '');
            })->count();
        }

        // Hitung juga yang sudah ada antrean menunggu hari ini
        $alreadyWaiting = 0;
        if (Schema::hasTable('kartu_pelajar_request')) {
            $alreadyWaiting = (clone $baseQuery)->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('kartu_pelajar_request as kpr')
                    ->whereColumn('kpr.peserta_didik_id', 'pd.peserta_didik_id')
                    ->when(
                        Schema::hasColumn('kartu_pelajar_request', 'request_date'),
                        fn($q) => $q->where('kpr.request_date', now()->toDateString()),
                        fn($q) => $q->whereDate('kpr.created_at', now()->toDateString())
                    )
                    ->where('kpr.status', 'menunggu');
            })->count();
        }

        return [
            'success'          => true,
            'rombel_nama'      => $rombel->nama,
            'total_siswa'      => $totalSiswa,
            'total_foto'       => $totalFoto,
            'total_tanpa_foto' => max(0, $totalSiswa - $totalFoto),
            'already_waiting'  => $alreadyWaiting,
        ];
    }

    /**
     * Tambah request cetak kartu sekaligus per kelas/rombel.
     */
    public function storeRequestRombel(array $data, mixed $user): array
    {
        $rombelId = $data['rombongan_belajar_id'];
        $rombel = DB::table('rombongan_belajar')
            ->where('rombongan_belajar_id', $rombelId)
            ->orWhere('nama', $rombelId)
            ->first();

        if (!$rombel) {
            return [
                'success' => false,
                'message' => 'Rombongan belajar tidak ditemukan.',
                'status_code' => 404,
            ];
        }

        $studentsQuery = DB::table('peserta_didik as pd')
            ->where(function ($w) use ($rombel) {
                $w->where('pd.rombongan_belajar_id', $rombel->rombongan_belajar_id)
                    ->orWhere('pd.nama_rombel', $rombel->nama);
            });

        $totalSiswaRombel = (clone $studentsQuery)->count();
        if ($totalSiswaRombel === 0) {
            return [
                'success' => false,
                'message' => "Tidak ada peserta didik yang terdaftar di rombel {$rombel->nama}.",
                'status_code' => 422,
            ];
        }

        $hanyaAdaFoto = !empty($data['hanya_ada_foto']);
        if ($hanyaAdaFoto && Schema::hasTable('peserta_didik_meta')) {
            $studentsQuery->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('peserta_didik_meta as pdm')
                    ->whereColumn('pdm.peserta_didik_id', 'pd.peserta_didik_id')
                    ->whereNotNull('pdm.foto_path')
                    ->where('pdm.foto_path', '!=', '');
            });
        }

        $students = $studentsQuery->orderBy('pd.nama', 'asc')->get();
        if ($students->isEmpty()) {
            return [
                'success' => false,
                'message' => "Tidak ada peserta didik di rombel {$rombel->nama} yang sudah memiliki foto formal kartu pelajar ({$totalSiswaRombel} siswa belum mengunggah foto).",
                'status_code' => 422,
            ];
        }

        $totalTanpaFoto = max(0, $totalSiswaRombel - $students->count());
        $insertedCount = 0;
        $skippedDuplicate = 0;
        $settings = KartuPelajarPengaturan::getSettings();
        $userName = is_array($user) ? ($user['nama'] ?? 'Admin') : ($user->nama ?? 'Admin');

        foreach ($students as $pd) {
            // Cek duplikasi request 'menunggu' hari ini
            $existing = KartuPelajarRequest::where('peserta_didik_id', $pd->peserta_didik_id)
                ->when(
                    Schema::hasColumn('kartu_pelajar_request', 'request_date'),
                    fn($q) => $q->where('request_date', now()->toDateString()),
                    fn($q) => $q->whereDate('created_at', now()->toDateString())
                )
                ->where('status', 'menunggu')
                ->first();

            if ($existing) {
                $skippedDuplicate++;
                continue;
            }

            // Sinkronisasi data pembayaran bila diisi
            if (!empty($data['status_pembayaran']) || !empty($data['metode_pembayaran'])) {
                $record = KartuPelajarPembayaran::firstOrNew(['peserta_didik_id' => $pd->peserta_didik_id]);
                $record->nisn = $pd->nisn;
                if (!empty($data['status_pembayaran'])) {
                    $record->status_pembayaran = $data['status_pembayaran'];
                    if ($data['status_pembayaran'] === 'lunas') {
                        if (empty($record->tanggal_bayar)) {
                            $record->tanggal_bayar = now()->toDateString();
                        }
                    } else {
                        $record->tanggal_bayar = null;
                    }
                }
                if (!empty($data['metode_pembayaran'])) {
                    $record->metode_pembayaran = $data['metode_pembayaran'];
                }
                if (empty($record->nominal)) {
                    $record->nominal = (int) ($settings->biaya_standar ?? 25000);
                }
                $record->save();
            }

            $alasan = !empty($data['alasan']) ? trim($data['alasan']) : "Request cetak masal kelas {$rombel->nama}";

            $payload = [
                'peserta_didik_id' => $pd->peserta_didik_id,
                'nisn'             => $pd->nisn,
                'jenis_request'    => $data['jenis_request'],
                'alasan'           => $alasan,
                'status'           => 'menunggu',
                'diminta_oleh'     => $userName,
            ];
            if (Schema::hasColumn('kartu_pelajar_request', 'metode_pembayaran')) {
                $payload['metode_pembayaran'] = $data['metode_pembayaran'] ?? null;
            }
            if (Schema::hasColumn('kartu_pelajar_request', 'request_date')) {
                $payload['request_date'] = now()->toDateString();
            }

            KartuPelajarRequest::create($payload);
            $insertedCount++;
        }

        if ($insertedCount === 0 && $skippedDuplicate > 0) {
            return [
                'success' => false,
                'message' => "Seluruh {$skippedDuplicate} siswa berfoto di kelas {$rombel->nama} sudah memiliki antrean request berstatus Menunggu hari ini.",
                'status_code' => 422,
            ];
        }

        $msg = "Sebanyak {$insertedCount} siswa kelas {$rombel->nama} berhasil ditambahkan ke antrean cetak.";
        if ($totalTanpaFoto > 0) {
            $msg .= " ({$totalTanpaFoto} siswa dilewati karena belum ada foto)";
        }
        if ($skippedDuplicate > 0) {
            $msg .= " ({$skippedDuplicate} siswa dilewati karena sudah ada di antrean)";
        }

        return [
            'success' => true,
            'message' => $msg,
            'data' => [
                'inserted'          => $insertedCount,
                'skipped_no_photo'  => $totalTanpaFoto,
                'skipped_duplicate' => $skippedDuplicate,
            ],
        ];
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
     * Selesaikan antrean cetak kartu via Tap Kartu RFID fisik & daftarkan RFID ke presensi.
     */
    public function scanSelesaiDanRegistrasiRfid(array $data, mixed $user): array
    {
        $pdId = trim((string) ($data['peserta_didik_id'] ?? ''));
        $code = trim((string) ($data['code'] ?? ''));
        $rfidUid = trim((string) ($data['rfid_uid'] ?? ''));
        $rawRfid = $rfidUid !== '' ? $rfidUid : $code;

        // 1. Dapatkan Peserta Didik (wajib dipilih terlebih dahulu)
        $pd = null;
        if ($pdId !== '') {
            $pd = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
        }

        // Fallback pencarian siswa jika code/id berupa NISN atau NIPD siswa
        if (!$pd && $code !== '') {
            $nisn = preg_replace('/[^A-Za-z0-9_-]/', '', $code);
            $pd = DB::table('peserta_didik')
                ->where('nisn', $nisn)
                ->orWhere('nipd', $nisn)
                ->orWhere('peserta_didik_id', $nisn)
                ->first();
        }

        if (!$pd) {
            return [
                'success' => false,
                'message' => 'Silakan pilih peserta didik terlebih dahulu sebelum menempelkan kartu RFID.',
                'status_code' => 422,
            ];
        }

        // 2. Bersihkan dan validasi UID RFID yang di-tap
        $rfidToRegister = strtoupper(trim($rawRfid));
        // Bersihkan karakter non-alphanumeric pada UID RFID
        $rfidToRegister = preg_replace('/[^A-Za-z0-9]/', '', $rfidToRegister);

        if ($rfidToRegister === '' || strlen($rfidToRegister) < 4) {
            return [
                'success' => false,
                'message' => 'Tempelkan kartu RFID pada reader USB untuk membaca UID kartu secara otomatis.',
                'status_code' => 422,
            ];
        }

        // 3. Cek duplikasi kartu RFID di pengguna (GTK)
        $dupUser = User::where('rfid_uid', $rfidToRegister)->first();
        if ($dupUser) {
            return [
                'success' => false,
                'message' => "Kartu RFID UID [{$rfidToRegister}] sudah terdaftar untuk GTK: {$dupUser->nama}.",
                'status_code' => 422,
            ];
        }

        // Cek duplikasi kartu RFID di siswa lain
        $dupSiswa = PesertaDidikMeta::where('rfid_uid', $rfidToRegister)
            ->where('peserta_didik_id', '!=', $pd->peserta_didik_id)
            ->first();
        if ($dupSiswa) {
            $siswaLain = DB::table('peserta_didik')->where('peserta_didik_id', $dupSiswa->peserta_didik_id)->first();
            $namaLain = $siswaLain ? $siswaLain->nama : 'siswa lain';
            return [
                'success' => false,
                'message' => "Kartu RFID UID [{$rfidToRegister}] sudah digunakan oleh {$namaLain} ({$dupSiswa->nisn}).",
                'status_code' => 422,
            ];
        }

        // 4. Daftarkan UID RFID ke tabel peserta_didik_meta (Database Manajemen Kartu RFID Presensi)
        $meta = PesertaDidikMeta::firstOrNew(['peserta_didik_id' => $pd->peserta_didik_id]);
        $meta->nisn = $pd->nisn;
        $meta->rfid_uid = $rfidToRegister;
        $meta->rfid_registered_at = now();
        $meta->save();

        // 5. Update status antrean request cetak siswa ini menjadi 'selesai'
        $requestUpdatedCount = 0;
        if (Schema::hasTable('kartu_pelajar_request')) {
            $requests = KartuPelajarRequest::where('peserta_didik_id', $pd->peserta_didik_id)
                ->whereIn('status', ['menunggu', 'diproses'])
                ->get();

            foreach ($requests as $req) {
                $req->status = 'selesai';
                $req->selesai_at = now();
                $req->catatan_admin = 'Kartu selesai dicetak & RFID terdaftar via Tap Reader.';
                $req->save();
                $requestUpdatedCount++;
            }
        }

        // 6. Update status fisik kartu menjadi 'sudah_cetak' pada kartu_pelajar_pembayaran
        $record = KartuPelajarPembayaran::firstOrNew(['peserta_didik_id' => $pd->peserta_didik_id]);
        $record->nisn = $pd->nisn;
        $record->status_cetak = 'sudah_cetak';
        $record->tanggal_cetak = now();
        if (empty($record->status_pembayaran)) {
            $record->status_pembayaran = 'lunas';
            $record->tanggal_bayar = now()->toDateString();
        }
        $record->save();

        $photoMeta = Schema::hasTable('peserta_didik_meta')
            ? PesertaDidikMeta::where('peserta_didik_id', $pd->peserta_didik_id)->first()
            : null;

        return [
            'success' => true,
            'message' => "Kartu {$pd->nama} ({$pd->nama_rombel}) selesai dicetak & RFID [{$rfidToRegister}] aktif di presensi.",
            'data' => [
                'peserta_didik_id' => $pd->peserta_didik_id,
                'nama'             => $pd->nama,
                'nisn'             => $pd->nisn,
                'nama_rombel'      => $pd->nama_rombel,
                'foto_url'         => $photoMeta?->foto_url,
                'rfid_uid'         => $rfidToRegister,
                'status_cetak'     => 'sudah_cetak',
                'selesai_at'       => now()->translatedFormat('d M Y H:i:s'),
            ],
        ];
    }

    /**
     * Aksi massal ubah status / hapus antrean request cetak kartu.
     */
    public function bulkActionRequest(array $data): array
    {
        $action = $data['action'];
        $applyAll = !empty($data['apply_all_filtered']);
        $ids = $data['ids'] ?? [];

        if ($applyAll) {
            $q = trim((string) ($data['filter_q'] ?? ''));
            $rombel = trim((string) ($data['filter_rombel'] ?? ''));
            $statusBayar = trim((string) ($data['filter_status_bayar'] ?? ''));
            $reqStatus = trim((string) ($data['filter_req_status'] ?? ''));

            $filterQuery = DB::table('kartu_pelajar_request as r')
                ->join('peserta_didik as pd', 'r.peserta_didik_id', '=', 'pd.peserta_didik_id')
                ->leftJoin('kartu_pelajar_pembayaran as kp', 'r.peserta_didik_id', '=', 'kp.peserta_didik_id');

            if ($reqStatus !== '' && isset(KartuPelajarRequest::STATUS[$reqStatus])) {
                $filterQuery->where('r.status', $reqStatus);
            }
            if ($rombel !== '') {
                $filterQuery->where('pd.nama_rombel', $rombel);
            }
            if ($statusBayar === 'lunas') {
                $filterQuery->where('kp.status_pembayaran', 'lunas');
            } elseif ($statusBayar === 'belum_bayar') {
                $filterQuery->where(function ($sq) {
                    $sq->whereNull('kp.status_pembayaran')
                        ->orWhere('kp.status_pembayaran', 'belum_bayar');
                });
            }
            if ($q !== '') {
                $filterQuery->where(function ($sub) use ($q) {
                    $sub->where('pd.nama', 'LIKE', "%{$q}%")
                        ->orWhere('r.nisn', 'LIKE', "%{$q}%")
                        ->orWhere('pd.nipd', 'LIKE', "%{$q}%")
                        ->orWhere('r.alasan', 'LIKE', "%{$q}%");
                });
            }

            $ids = $filterQuery->pluck('r.id')->all();
        }

        if (empty($ids)) {
            return [
                'success' => false,
                'message' => 'Tidak ada request antrean yang dipilih atau sesuai dengan filter.',
            ];
        }

        $count = 0;
        if ($action === 'delete') {
            foreach ($ids as $reqId) {
                $delRes = $this->destroyRequest((int) $reqId);
                if ($delRes['success']) {
                    $count++;
                }
            }
            return [
                'success' => true,
                'message' => "Sebanyak {$count} request antrean berhasil dihapus permanen.",
            ];
        }

        $targetStatus = match ($action) {
            'set_diproses' => 'diproses',
            'set_selesai'  => 'selesai',
            'set_ditolak'  => 'ditolak',
            'set_menunggu' => 'menunggu',
            default        => $data['target_status'] ?? null,
        };

        if (!$targetStatus || !isset(KartuPelajarRequest::STATUS[$targetStatus])) {
            return [
                'success' => false,
                'message' => 'Status tujuan tidak valid.',
            ];
        }

        foreach ($ids as $reqId) {
            $upRes = $this->updateStatusRequest((int) $reqId, [
                'status' => $targetStatus,
                'catatan_admin' => $data['catatan_admin'] ?? null,
            ]);
            if ($upRes['success']) {
                $count++;
            }
        }

        $label = KartuPelajarRequest::STATUS[$targetStatus];
        return [
            'success' => true,
            'message' => "Sebanyak {$count} antrean request berhasil diubah statusnya menjadi {$label}.",
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
