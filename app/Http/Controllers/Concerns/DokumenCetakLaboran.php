<?php

namespace App\Http\Controllers\Concerns;

use App\Services\LaboranDokumenService;
use Illuminate\Http\Request;

/**
 * Helper DOKUMEN CETAK A4 modul Laboratorium.
 *
 * Dipakai bersama oleh `Laboran\LaboranBaseController` (seluruh modul
 * per-jurusan) dan controller Laboran lama di namespace akar
 * (`LaboranInventarisController`, `LaboranBhpController`) yang tidak
 * mewarisi base controller tersebut.
 */
trait DokumenCetakLaboran
{
    /** @var LaboranDokumenService|null */
    protected ?LaboranDokumenService $dokumenServiceInstance = null;

    /**
     * Service dokumen cetak (lazy).
     */
    protected function dokumenService(): LaboranDokumenService
    {
        return $this->dokumenServiceInstance ??= app(LaboranDokumenService::class);
    }

    /**
     * Bundel data standar view `*-cetak`: kop sekolah, pejabat penanda tangan
     * (Kepala Sekolah, Kepala Lab, **Kepala Program Keahlian**), petugas,
     * mode ringkasan/lengkap, orientasi, serta pencatatan riwayat cetak.
     *
     * @param  string  $jenis  Kunci dokumen (lihat LaboranDokumenService::DOKUMEN)
     * @param  array   $context  Konteks LaboranScope
     * @param  array   $opsi  Override judul/mode/orientasi/periode/filter
     */
    protected function dokumenCetak(string $jenis, array $context, array $opsi = []): array
    {
        return $this->dokumenService()->dokumen($jenis, $context, $opsi);
    }

    /**
     * Normalisasi parameter `orientasi` dari query string.
     */
    protected function orientasiCetak(Request $request, string $default = 'portrait'): string
    {
        $orientasi = strtolower((string) $request->query('orientasi', $default));

        return in_array($orientasi, LaboranDokumenService::ORIENTASI, true) ? $orientasi : $default;
    }

    /**
     * Normalisasi parameter `mode` (ringkasan | lengkap) dari query string.
     */
    protected function modeCetak(Request $request, string $default = 'ringkasan'): string
    {
        $mode = strtolower((string) $request->query('mode', $default));

        return in_array($mode, LaboranDokumenService::MODE, true) ? $mode : $default;
    }
}
