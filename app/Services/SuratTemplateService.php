<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Persuratan;
use App\Models\PersuratanTemplate;
use App\Models\Gtk;
use App\Models\Sekolah;
use App\Models\SekolahMeta;

class SuratTemplateService
{
    /**
     * Simpan Surat Keluar dari Template Autocrat dan Catat ke Persuratan Keluar
     */
    public static function store(Request $request, $user): array
    {
        $kodeIndeks = $request->kode_indeks ?: 'HM.01.02';
        $isAuto = empty($request->nomor_surat);
        $nomorSurat = $isAuto
            ? PersuratanHddService::generateNomorSuratKeluar($kodeIndeks)
            : trim($request->nomor_surat);

        $kepsek = Gtk::where(function ($q) {
            $q->where('jenis_ptk_id_str', 'like', '%kepala sekolah%')
              ->orWhere('jabatan_ptk_id_str', 'like', '%kepala sekolah%');
        })->first();

        $sekolah = Sekolah::first();
        $docId = 'SAE-TMP-' . strtoupper(Str::random(10));
        $userName = is_array($user) ? ($user['nama'] ?? 'Petugas Persuratan') : ($user->nama ?? 'Petugas Persuratan');

        $rincian = [
            'hari_tanggal' => $request->hari_tanggal_kegiatan,
            'waktu'        => $request->waktu_kegiatan,
            'tempat'       => $request->tempat_kegiatan,
            'agenda'       => $request->agenda_kegiatan,
        ];

        $templateDoc = PersuratanTemplate::create([
            'nomor_surat'      => $nomorSurat,
            'kode_indeks'      => $kodeIndeks,
            'template_type'    => $request->template_type,
            'perihal'          => trim($request->perihal),
            'lampiran'         => $request->lampiran ?: '-',
            'tujuan_penerima'  => trim($request->tujuan_penerima),
            'tujuan_lokasi'    => $request->tujuan_lokasi ? trim($request->tujuan_lokasi) : 'Di Tempat',
            'tanggal_surat'    => $request->tanggal_surat,
            'salam_pembuka'    => $request->salam_pembuka ?: 'Dengan hormat,',
            'paragraf_pembuka' => $request->paragraf_pembuka,
            'rincian_kegiatan' => $rincian,
            'isi_surat'        => $request->isi_surat,
            'paragraf_penutup' => $request->paragraf_penutup,
            'tembusan'         => $request->tembusan,
            'pejabat_ptk_id'   => $kepsek?->ptk_id,
            'pejabat_nama'     => $kepsek?->nama ?? ($sekolah->nama_kepsek ?? 'Kepala Sekolah'),
            'pejabat_nip'      => $kepsek?->nip ?: ($sekolah->nip_kepsek ?? '—'),
            'pejabat_jabatan'  => 'Kepala Satuan Pendidikan',
            'doc_id'           => $docId,
            'status'           => 'selesai',
            'created_by'       => $userName,
        ]);

        // Catat juga ke buku agenda persuratan keluar agar terindeks di master keluar
        Persuratan::create([
            'nomor_surat'     => $nomorSurat,
            'jenis_surat'     => 'keluar',
            'kode_indeks'     => $kodeIndeks,
            'perihal'         => trim($request->perihal),
            'pengirim_asal'   => $sekolah?->nama ?? 'SMK SAE',
            'tujuan_penerima' => trim($request->tujuan_penerima),
            'tanggal_surat'   => $request->tanggal_surat,
            'status'          => 'selesai',
            'keterangan'      => "Penerbitan surat keluar via Template Autocrat [{$request->template_type}]. Doc ID: {$docId}",
            'created_by'      => $userName,
        ]);

        if ($isAuto) {
            PersuratanHddService::incrementNomorCounter('keluar');
        }

        return [
            'templateDoc' => $templateDoc,
            'nomorSurat'  => $nomorSurat,
        ];
    }

    /**
     * Hapus Dokumen Template Autocrat beserta relasinya di persuratan keluar
     */
    public static function delete($id): bool
    {
        $doc = PersuratanTemplate::findOrFail($id);
        Persuratan::where('nomor_surat', $doc->nomor_surat)->delete();
        $doc->delete();

        return true;
    }

    /**
     * Siapkan Data Cetak Surat Template Autocrat Resmi Ber-Kop Sekolah + QR Code
     */
    public static function buildCetakData($id): array
    {
        $doc = PersuratanTemplate::findOrFail($id);
        $sekolah = Sekolah::first();
        $sekolahMeta = SekolahMeta::first();
        $kepsek = Gtk::where('ptk_id', $doc->pejabat_ptk_id)->first() ?: Gtk::where(function ($q) {
            $q->where('jenis_ptk_id_str', 'like', '%kepala sekolah%')
              ->orWhere('jabatan_ptk_id_str', 'like', '%kepala sekolah%');
        })->first();

        $qrVerifyUrl = url('/v/doc/' . $doc->doc_id);
        $qrUri = QrCodeService::generateDataUri($qrVerifyUrl, 140, 1);
        $orientasi = 'portrait';

        return compact(
            'doc',
            'sekolah',
            'sekolahMeta',
            'kepsek',
            'qrUri',
            'orientasi'
        );
    }
}
