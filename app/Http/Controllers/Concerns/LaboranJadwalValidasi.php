<?php

namespace App\Http\Controllers\Concerns;

use App\Services\LaboranJadwalService;

/**
 * Konstanta & tombol sakelar validasi penjadwalan praktik laboran.
 *
 * Dipisah dari controller agar berkas controller tetap di bawah batas 1.000
 * baris, dan agar pelonggaran sementara terlihat jelas di satu tempat.
 */
trait LaboranJadwalValidasi
{
    public const JENIS_LOKASI = LaboranJadwalService::JENIS_LOKASI;
    public const STATUS_LIST  = LaboranJadwalService::STATUS_LIST;

    /**
     * Ikatan jam praktik ke Jadwal KBM (kuota JP per mapel per hari).
     *
     * SEMENTARA DIMATIKAN atas permintaan pemilik modul: input jadwal praktik
     * tidak boleh ditolak karena mapel tidak ada di Jadwal KBM atau JP melebihi
     * kuota mapel. Ubah ke `true` untuk mengaktifkan lagi.
     */
    public const PAKSA_VALIDASI_KBM = false;

    /**
     * Pemeriksaan bentrok GURU pada jam beririsan antar kelas.
     *
     * SEMENTARA DIMATIKAN atas permintaan pemilik modul ("validasi ini juga di
     * hapus") karena kombinasi guru multi-kelas sering sah. Ubah ke `true`
     * untuk mengaktifkan lagi; kode pemeriksaannya sendiri tetap utuh di
     * `LaboranJadwalService::findConflictSingleDate()`.
     */
    public const PAKSA_VALIDASI_BENTROK = false;
}
