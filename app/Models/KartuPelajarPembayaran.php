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
        'tanggal_cetak',
        'nomor_wa',
        'wa_terkirim_at',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_bayar'  => 'date',
        'tanggal_cetak'  => 'datetime',
        'wa_terkirim_at' => 'datetime',
        'nominal'        => 'integer',
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
        $namaSekolah = $sekolahInfo['nama'] ?? 'SMK NEGERI 1 PAGELARAN';
        $namaSiswa = $data['nama'] ?? '-';
        $nisn = $data['nisn'] ?? '-';
        $rombel = $data['rombel'] ?? '-';
        $statusBayar = ($data['status_pembayaran'] ?? 'belum_bayar') === 'lunas' ? 'LUNAS (TERVERIFIKASI)' : 'BELUM DIBAYAR';
        $nominal = number_format((int)($data['nominal'] ?? 25000), 0, ',', '.');
        $metode = strtoupper($data['metode_pembayaran'] ?? 'TUNAI');
        $tglBayar = !empty($data['tanggal_bayar'])
            ? \Carbon\Carbon::parse($data['tanggal_bayar'])->translatedFormat('d F Y')
            : '-';
        $statusCetak = ($data['status_cetak'] ?? 'belum_cetak') === 'sudah_cetak' ? 'Sudah Dicetak' : 'Belum Dicetak (Dalam Antrean)';
        $verifyUrl = $data['verify_url'] ?? url('/v/' . $nisn);

        $pesan = "*INFORMASI ADMINISTRASI & PEMBAYARAN KARTU PELAJAR*\n";
        $pesan .= "*{$namaSekolah}*\n";
        $pesan .= "--------------------------------------------------\n\n";
        $pesan .= "Yth. Orang Tua / Wali Murid & Peserta Didik:\n";
        $pesan .= "• Nama: *{$namaSiswa}*\n";
        $pesan .= "• NISN: *{$nisn}*\n";
        $pesan .= "• Kelas: *{$rombel}*\n\n";

        $pesan .= "*Rincian Status Administrasi Kartu Pelajar:*\n";
        $pesan .= "• Status Pembayaran: *{$statusBayar}*\n";
        $pesan .= "• Biaya / Nominal: *Rp {$nominal}*\n";
        if (($data['status_pembayaran'] ?? '') === 'lunas') {
            $pesan .= "• Metode Bayar: *{$metode}*\n";
            $pesan .= "• Tanggal Pembayaran: *{$tglBayar}*\n";
        }
        $pesan .= "• Status Fisik Kartu: *{$statusCetak}*\n\n";

        if (($data['status_pembayaran'] ?? '') !== 'lunas') {
            $pesan .= "Pembayaran administrasi kartu tanda pelajar digital & fisik RFID dapat diselesaikan melalui bendahara sekolah / wali kelas.\n\n";
        }

        $pesan .= "*Verifikasi Kartu Pelajar & QR Code Digital:*\n";
        $pesan .= "Untuk melihat kartu tanda pelajar resmi dan memindai QR Code keabsahan digital, silakan kunjungi:\n";
        $pesan .= "🔗 {$verifyUrl}\n\n";

        $pesan .= "Simpan pesan ini sebagai bukti catatan administrasi resmi. Terima kasih.\n";
        $pesan .= "_{$namaSekolah}_";

        return $pesan;
    }
}
