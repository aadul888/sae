<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Persuratan;
use App\Models\Gtk;
use App\Models\Sekolah;
use App\Models\SekolahMeta;

class SuratSptService
{
    /**
     * Simpan Surat Perintah Tugas (SPT) dan Catat ke Persuratan Keluar
     */
    public static function store(Request $request, $user): array
    {
        $start = Carbon::parse($request->tanggal_berangkat);
        $end = Carbon::parse($request->tanggal_kembali);
        $lamaHari = $start->diffInDays($end) + 1;

        $kodeIndeks = trim($request->kode_indeks ?: 'KPG.11.01');
        $manualNomor = trim($request->nomor_spt ?? '');

        if (!empty($manualNomor)) {
            $nomorSpt = $manualNomor;
        } else {
            $nomorSpt = PersuratanHddService::generateNomorSuratKeluar($kodeIndeks);
            PersuratanHddService::incrementNomorCounter('keluar');
        }

        $kepsek = Gtk::where(function ($q) {
            $q->where('jenis_ptk_id_str', 'like', '%kepala sekolah%')
              ->orWhere('jabatan_ptk_id_str', 'like', '%kepala sekolah%');
        })->first();

        $kepsekNama = $kepsek?->nama ?? 'Kepala Sekolah';
        $createdBy = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Tendik Persuratan')) : ($user->nama ?? ($user->name ?? 'Tendik Persuratan'));
        $satuan = Sekolah::first();
        $namaSatuan = $satuan?->nama ?? 'Sekolah';

        $sptId = DB::table('gtk_spt')->insertGetId([
            'nomor_spt' => $nomorSpt,
            'dasar_penugasan' => $request->dasar_penugasan,
            'nama_kegiatan' => $request->nama_kegiatan,
            'lokasi_tujuan' => $request->lokasi_tujuan,
            'waktu_pelaksanaan' => $request->waktu_pelaksanaan ?: null,
            'alamat_tujuan' => $request->alamat_tujuan ?: null,
            'alat_angkutan' => $request->alat_angkutan ?: 'Kendaraan Darat',
            'tempat_berangkat' => $request->tempat_berangkat ?: $namaSatuan,
            'pengikut' => $request->pengikut ?: null,
            'instansi_pembebanan' => $request->instansi_pembebanan ?: $namaSatuan,
            'akun_kodering' => $request->akun_kodering ?: null,
            'tingkat_biaya' => $request->tingkat_biaya ?: null,
            'tanggal_berangkat' => $request->tanggal_berangkat,
            'tanggal_kembali' => $request->tanggal_kembali,
            'lama_hari' => $lamaHari,
            'beban_anggaran' => $request->beban_anggaran,
            'pejabat_penandatangan_ptk_id' => $kepsek?->ptk_id,
            'pejabat_nama' => $kepsekNama,
            'pejabat_jabatan' => 'Kepala Sekolah',
            'daftar_ptk_id' => json_encode($request->daftar_ptk_id, JSON_UNESCAPED_UNICODE),
            'status' => 'disetujui',
            'created_by' => $createdBy,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Catat otomatis ke buku agenda persuratan keluar agar terkoordinir penuh
        Persuratan::create([
            'nomor_surat'     => $nomorSpt,
            'kode_indeks'     => $kodeIndeks,
            'jenis_surat'     => 'keluar',
            'perihal'         => "Surat Perintah Tugas (SPT): {$request->nama_kegiatan}",
            'pengirim_asal'   => $kepsekNama,
            'tujuan_penerima' => trim($request->lokasi_tujuan),
            'tanggal_surat'   => $request->tanggal_berangkat,
            'status'          => 'selesai',
            'keterangan'      => "SPT Kegiatan {$request->nama_kegiatan}. Dasar: {$request->dasar_penugasan}. Beban: {$request->beban_anggaran}. Doc ID: SAE-SPT-{$sptId}",
            'created_by'      => $createdBy,
        ]);

        return [
            'sptId'    => $sptId,
            'nomorSpt' => $nomorSpt,
        ];
    }

    /**
     * Hapus SPT beserta relasinya di persuratan keluar
     */
    public static function delete($id): bool
    {
        $spt = DB::table('gtk_spt')->where('id', $id)->first();
        if ($spt) {
            Persuratan::where('nomor_surat', $spt->nomor_spt)->delete();
            DB::table('gtk_spt')->where('id', $id)->delete();
            SuratKeluarService::syncCounterWithDatabase();
            return true;
        }

        return false;
    }

    /**
     * Siapkan Data Cetak Lembar SPT Resmi Ber-Kop Sekolah
     */
    public static function buildCetakData($id): array
    {
        $spt = DB::table('gtk_spt')->where('id', $id)->first();
        if (!$spt) {
            abort(404, 'Data SPT tidak ditemukan.');
        }

        $ptkIds = json_decode($spt->daftar_ptk_id ?? '[]', true) ?: [];
        $gtkList = Gtk::whereIn('ptk_id', $ptkIds)->orderBy('nama', 'asc')->get();

        $sekolah = Sekolah::first();
        $sekolahMeta = SekolahMeta::first();
        $kepsek = Gtk::where(function ($q) {
            $q->where('jenis_ptk_id_str', 'like', '%kepala sekolah%')
              ->orWhere('jabatan_ptk_id_str', 'like', '%kepala sekolah%');
        })->first();

        $docId = 'SAE-SPT-' . $spt->id;
        $qrVerifyUrl = url('/v/doc/' . $docId);
        $qrUri = QrCodeService::generateDataUri($qrVerifyUrl, 140, 1);

        return compact(
            'spt',
            'gtkList',
            'sekolah',
            'sekolahMeta',
            'kepsek',
            'docId',
            'qrVerifyUrl',
            'qrUri'
        );
    }
}
