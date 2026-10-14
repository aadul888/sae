<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KartuPelajarPembayaran extends Model
{
    use HasFactory;

    protected $table = 'kartu_pelajar_pembayaran';

    protected $fillable = [
        'peserta_didik_id',
        'nisn',
        'status_pembayaran',
        'nominal',
        'metode_pembayaran',
        'tanggal_bayar',
        'status_cetak',
        'klaim_ganti_kartu',
        'klaim_stiker',
        'tanggal_cetak',
        'nomor_wa',
        'wa_terkirim_at',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_bayar'     => 'date',
        'tanggal_cetak'     => 'datetime',
        'wa_terkirim_at'    => 'datetime',
        'nominal'           => 'integer',
        'klaim_ganti_kartu' => 'integer',
        'klaim_stiker'      => 'integer',
    ];

    public function pesertaDidik()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }

    /**
     * Normalisasi nomor telepon ke format internasional WhatsApp (628...)
     */
    public static function normalizePhoneNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Hapus karakter selain angka
        $clean = preg_replace('/[^0-9]/', '', $phone);

        if (empty($clean)) {
            return null;
        }

        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        } elseif (str_starts_with($clean, '8')) {
            $clean = '62' . $clean;
        }

        return $clean;
    }

    /**
     * Susun teks deskripsi pesan otomatis WhatsApp untuk informasi pembayaran & QR Code
     */
    public static function buildWhatsAppMessage(array $data, array $sekolahInfo): string
    {
        $settings = KartuPelajarPengaturan::getSettings();
        $template = $settings->template_wa ?: KartuPelajarPengaturan::getDefaultWaTemplate();

        $namaSekolah = $sekolahInfo['nama'] ?? 'SMK NEGERI 1 PAGELARAN';
        $namaSiswa = $data['nama'] ?? '-';
        $nisn = $data['nisn'] ?? '-';
        $rombel = $data['rombel'] ?? '-';
        $statusBayar = ($data['status_pembayaran'] ?? 'belum_bayar') === 'lunas' ? 'LUNAS (TERVERIFIKASI)' : 'BELUM DIBAYAR';
        $nominal = number_format((int) ($data['nominal'] ?? $settings->biaya_standar), 0, ',', '.');
        $metode = strtoupper($data['metode_pembayaran'] ?? 'TUNAI');
        $tglBayar = !empty($data['tanggal_bayar'])
            ? \Carbon\Carbon::parse($data['tanggal_bayar'])->translatedFormat('d F Y')
            : '-';
        $statusCetak = ($data['status_cetak'] ?? 'belum_cetak') === 'sudah_cetak' ? 'Sudah Dicetak' : 'Belum Dicetak (Dalam Antrean)';
        $verifyUrl = $data['verify_url'] ?? url('/v/' . $nisn);

        // Garansi info
        $kuotaKartu = $settings->kuota_ganti_kartu_full ?? 1;
        $kuotaStiker = $settings->kuota_ganti_stiker ?? 3;
        $klaimKartu = (int) ($data['klaim_ganti_kartu'] ?? 0);
        $klaimStiker = (int) ($data['klaim_stiker'] ?? 0);
        $sisaKartu = max(0, $kuotaKartu - $klaimKartu);
        $sisaStiker = max(0, $kuotaStiker - $klaimStiker);

        $infoGaransi = "• 1x Garansi Ganti Kartu Full: Sisa {$sisaKartu} dari {$kuotaKartu} kali\n"
            . "• 3x Garansi Cetak Stiker: Sisa {$sisaStiker} dari {$kuotaStiker} kali";

        // Deskripsi pembayaran
        $deskripsiBayar = $settings->instruksi_pembayaran ?: '';
        if (!empty($settings->bank_rekening)) {
            $deskripsiBayar .= "\nRekening Pembayaran: *{$settings->bank_nama} {$settings->bank_rekening}* a.n. *{$settings->bank_atas_nama}*";
        }

        $replacements = [
            '{NAMA_SEKOLAH}'         => $namaSekolah,
            '{NAMA_SISWA}'           => $namaSiswa,
            '{NISN}'                 => $nisn,
            '{ROMBEL}'               => $rombel,
            '{STATUS_BAYAR}'         => $statusBayar,
            '{NOMINAL}'              => $nominal,
            '{METODE_BAYAR}'         => $metode,
            '{TANGGAL_BAYAR}'        => $tglBayar,
            '{STATUS_CETAK}'         => $statusCetak,
            '{INFO_GARANSI}'         => $infoGaransi,
            '{DESKRIPSI_PEMBAYARAN}' => $deskripsiBayar,
            '{VERIFY_URL}'           => $verifyUrl,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
