<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Persuratan;
use App\Models\SuratKeteranganPd;
use App\Models\PesertaDidik;
use App\Models\Sekolah;
use App\Models\SekolahMeta;

class SuratKeteranganService
{
    /**
     * Simpan Surat Keterangan Siswa Baru dan Catat ke Persuratan Keluar
     */
    public static function store(Request $request, $user): array
    {
        $siswa = PesertaDidik::findOrFail($request->peserta_didik_id);
        $jenisSurat = $request->jenis_surat ?: 'siswa_aktif';

        $defaultIndeks = match($jenisSurat) {
            'kelakuan_baik' => 'KS.02.24',
            'panggilan_ortu' => '005',
            'rekomendasi' => 'KS.02.25',
            default => 'KS.02.23',
        };

        $kodeIndeks = $request->kode_indeks ?: $defaultIndeks;
        $isAuto = empty($request->nomor_surat);
        $nomorSurat = $isAuto
            ? PersuratanHddService::generateNomorSuratKeterangan($kodeIndeks)
            : trim($request->nomor_surat);

        $kepsek = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        $docId = 'SAE-KET-' . strtoupper(Str::random(10));
        $qrVerifyUrl = url('/v/doc/' . $docId);
        $userName = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Petugas Persuratan')) : ($user->nama ?? ($user->name ?? 'Petugas Persuratan'));

        $suratKet = SuratKeteranganPd::create([
            'nomor_surat' => $nomorSurat,
            'peserta_didik_id' => $siswa->peserta_didik_id,
            'jenis_surat' => $jenisSurat,
            'keperluan' => trim($request->keperluan),
            'tanggal_surat' => $request->tanggal_surat ?: date('Y-m-d'),
            'tanggal_agenda' => $request->tanggal_agenda ?: null,
            'waktu_agenda' => $request->waktu_agenda ? trim($request->waktu_agenda) : null,
            'tempat_agenda' => $request->tempat_agenda ? trim($request->tempat_agenda) : null,
            'menghadap_agenda' => $request->menghadap_agenda ? trim($request->menghadap_agenda) : null,
            'catatan_khusus' => $request->catatan_khusus ? trim($request->catatan_khusus) : null,
            'penandatangan_ptk_id' => $kepsek?->ptk_id,
            'penandatangan_nama' => $kepsek?->nama ?: 'Kepala Sekolah',
            'penandatangan_jabatan' => 'Kepala Sekolah',
            'doc_id' => $docId,
            'qrcode_url' => $qrVerifyUrl,
            'created_by' => $userName,
        ]);

        // Integrasi Otomatis: Jika Surat Panggilan Orang Tua, catat juga ke modul Kedisiplinan / BK
        if ($jenisSurat === 'panggilan_ortu' && class_exists(\App\Models\KedisiplinanPemanggilanWali::class)) {
            try {
                \App\Models\KedisiplinanPemanggilanWali::create([
                    'peserta_didik_id' => $siswa->peserta_didik_id,
                    'nomor_surat' => $nomorSurat,
                    'tanggal_surat' => $suratKet->tanggal_surat,
                    'tanggal_hadir' => $request->tanggal_agenda ?: $suratKet->tanggal_surat,
                    'jam_hadir' => $request->waktu_agenda ?: '08:00 WIB',
                    'tempat' => $request->tempat_agenda ?: 'Ruang Bimbingan Konseling (BK)',
                    'alasan' => trim($request->keperluan),
                    'menghadap_ke' => $request->menghadap_agenda ?: 'Guru BK / Wali Kelas',
                    'status' => 'terkirim',
                    'catatan_hasil' => $request->catatan_khusus ? trim($request->catatan_khusus) : null,
                    'doc_id' => $docId,
                ]);
            } catch (\Throwable $e) {}
        }

        $jenisLabel = match($jenisSurat) {
            'kelakuan_baik' => 'Surat Keterangan Berkelakuan Baik',
            'panggilan_ortu' => 'Surat Panggilan Orang Tua / Wali',
            'rekomendasi' => 'Surat Rekomendasi Siswa',
            default => 'Surat Keterangan Siswa Aktif',
        };

        // Catat otomatis ke buku agenda surat keluar
        Persuratan::create([
            'nomor_surat' => $nomorSurat,
            'kode_indeks' => $kodeIndeks,
            'jenis_surat' => 'keluar',
            'perihal' => "{$jenisLabel}: {$siswa->nama} (NISN: {$siswa->nisn})",
            'pengirim_asal' => 'SMK SAE',
            'tujuan_penerima' => trim($request->keperluan),
            'tanggal_surat' => $suratKet->tanggal_surat,
            'status' => 'selesai',
            'keterangan' => "Penerbitan {$jenisLabel} a.n. {$siswa->nama} untuk keperluan: {$request->keperluan}. Indeks: {$kodeIndeks}. Doc ID: {$docId}",
            'created_by' => $userName,
        ]);

        if ($isAuto) {
            PersuratanHddService::incrementNomorCounter('keterangan');
        }

        return [
            'suratKet'   => $suratKet,
            'nomorSurat' => $nomorSurat,
            'jenisLabel' => $jenisLabel,
        ];
    }

    /**
     * Siapkan Data Cetak Lembar Resmi Surat Keterangan Siswa Ber-Kop + QR Code
     */
    public static function buildCetakData($id): array
    {
        $suratKet = SuratKeteranganPd::findOrFail($id);
        $siswa = PesertaDidik::where('peserta_didik_id', $suratKet->peserta_didik_id)->firstOrFail();
        $sekolah = Sekolah::first() ?: DB::table('sekolah')->first();
        $sekolahMeta = SekolahMeta::first();

        $anggotaRombel = \App\Support\RombelScope::reguler(
            DB::table('anggota_rombel as ar')
                ->join('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->where('ar.peserta_didik_id', $siswa->peserta_didik_id),
            'rb.'
        )
            ->select('rb.nama as rombel_nama', 'rb.tingkat_pendidikan_id_str', 'rb.jurusan_id_str')
            ->first();

        $rombelNama = $anggotaRombel?->rombel_nama ?: 'Kelas X / XI / XII';
        $jurusanNama = $anggotaRombel?->jurusan_id_str ?: 'Semua Program Keahlian';

        $curYear = (int) date('Y');
        $tahunAjaran = (date('n') >= 7) ? "{$curYear}/" . ($curYear + 1) : ($curYear - 1) . "/{$curYear}";

        $kepsek = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        $qrVerifyUrl = url('/v/doc/' . $suratKet->doc_id);
        $qrUri = QrCodeService::generateDataUri($qrVerifyUrl, 140, 1);
        $orientasi = 'portrait';

        return compact(
            'suratKet',
            'siswa',
            'sekolah',
            'sekolahMeta',
            'orientasi',
            'rombelNama',
            'jurusanNama',
            'tahunAjaran',
            'kepsek',
            'qrUri'
        );
    }
}
