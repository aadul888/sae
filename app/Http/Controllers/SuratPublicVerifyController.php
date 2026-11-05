<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SuratKeteranganPd;
use App\Models\PesertaDidik;
use App\Models\Persuratan;
use App\Services\PersuratanHddService;

class SuratPublicVerifyController extends Controller
{
    /**
     * Halaman Publik Verifikasi Keaslian Dokumen Persuratan via QR Code
     */
    public function verifyDoc($doc_id)
    {
        $cleanDocId = trim($doc_id);

        // 1. Cari di tabel surat_keterangan_pd
        $suratKet = SuratKeteranganPd::where('doc_id', $cleanDocId)
            ->orWhere('nomor_surat', $cleanDocId)
            ->first();

        $siswa = null;
        $rombelNama = '-';
        $jurusanNama = '-';
        $suratUmum = null;
        $spt = null;
        $sptGtkList = collect();
        $sekolah = DB::table('sekolah')->first();
        $sekolahMeta = \App\Models\SekolahMeta::first();

        if ($suratKet) {
            $siswa = PesertaDidik::where('peserta_didik_id', $suratKet->peserta_didik_id)->first();

            $anggotaRombel = DB::table('anggota_rombel as ar')
                ->join('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->where('ar.peserta_didik_id', $suratKet->peserta_didik_id)
                ->select('rb.nama as rombel_nama', 'rb.jurusan_id_str')
                ->first();

            $rombelNama = $anggotaRombel?->rombel_nama ?: '-';
            $jurusanNama = $anggotaRombel?->jurusan_id_str ?: '-';
        } else {
            // 2. Cek apakah ini SPT (Surat Perintah Tugas & SPPD)
            if (str_starts_with($cleanDocId, 'SAE-SPT-')) {
                $sptId = (int) str_replace('SAE-SPT-', '', $cleanDocId);
                $spt = DB::table('gtk_spt')->where('id', $sptId)->first();
            } else {
                $spt = DB::table('gtk_spt')->where('nomor_spt', $cleanDocId)->first();
            }

            if ($spt) {
                $ptkIds = json_decode($spt->daftar_ptk_id ?? '[]', true) ?: [];
                $sptGtkList = \App\Models\Gtk::whereIn('ptk_id', $ptkIds)->orderBy('nama', 'asc')->get();
            }

            // 3. Cek apakah ini lembar disposisi surat masuk
            if (str_starts_with($cleanDocId, 'SAE-DISP-')) {
                $dispSuratId = (int) str_replace('SAE-DISP-', '', $cleanDocId);
                $suratUmum = Persuratan::where('id', $dispSuratId)->first();
            }

            // 4. Cek apakah ini surat umum / template di tabel persuratan
            if (!$suratUmum) {
                $suratUmum = Persuratan::where('nomor_surat', $cleanDocId)
                    ->orWhere('keterangan', 'like', "%{$cleanDocId}%")
                    ->first();
            }
        }

        $isValid = ($suratKet !== null || $suratUmum !== null || $spt !== null);

        // Penandatangan
        $penandatanganNama = $suratKet?->penandatangan_nama ?: ($spt?->pejabat_nama ?? null);
        $penandatanganJabatan = $suratKet?->penandatangan_jabatan ?: ($spt?->pejabat_jabatan ?: 'Kepala Sekolah');
        $penandatanganNip = '-';

        $pejabatPtkId = $suratKet?->penandatangan_ptk_id ?: ($spt?->pejabat_penandatangan_ptk_id ?? null);
        if ($pejabatPtkId) {
            $gtk = DB::table('gtk')->where('ptk_id', $pejabatPtkId)->first();
            if ($gtk) {
                $penandatanganNip = $gtk->nip ?: ($gtk->nuptk ?: '-');
            }
        } elseif (!$penandatanganNama) {
            $kepsek = DB::table('gtk')
                ->where(function ($q) {
                    $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                      ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
                })
                ->first();

            $penandatanganNama = $kepsek?->nama ?: 'Kepala Sekolah';
            $penandatanganNip = $kepsek?->nip ?: ($kepsek?->nuptk ?: '-');
        }

        return view('public.verifikasi-surat', compact(
            'isValid',
            'doc_id',
            'suratKet',
            'suratUmum',
            'spt',
            'sptGtkList',
            'siswa',
            'rombelNama',
            'jurusanNama',
            'penandatanganNama',
            'penandatanganJabatan',
            'penandatanganNip',
            'sekolah',
            'sekolahMeta'
        ));
    }
}
