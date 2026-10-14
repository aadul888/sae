<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KartuPelajarPengaturan extends Model
{
    use HasFactory;

    protected $table = 'kartu_pelajar_pengaturan';

    protected $fillable = [
        'biaya_standar',
        'bank_nama',
        'bank_rekening',
        'bank_atas_nama',
        'qris_image_path',
        'instruksi_pembayaran',
        'template_wa',
        'kuota_ganti_kartu_full',
        'kuota_ganti_stiker',
        'ketentuan_garansi',
        'updated_by',
    ];

    protected $casts = [
        'biaya_standar'           => 'integer',
        'kuota_ganti_kartu_full'  => 'integer',
        'kuota_ganti_stiker'      => 'integer',
    ];

    protected $appends = [
        'qris_image_url',
    ];

    public function getQrisImageUrlAttribute(): ?string
    {
        if (empty($this->qris_image_path)) {
            return null;
        }

        return asset('storage/' . ltrim($this->qris_image_path, '/'));
    }

    /**
     * Dapatkan singleton pengaturan kartu pelajar (jika belum ada, buat default)
     */
    public static function getSettings(): self
    {
        $settings = self::first();
        if (!$settings) {
            $settings = self::create([
                'biaya_standar'          => 25000,
                'bank_nama'              => 'Bank BJB',
                'bank_rekening'          => '0123-4567-8901',
                'bank_atas_nama'         => 'SMK NEGERI 1 PAGELARAN',
                'instruksi_pembayaran'   => "Pembayaran biaya administrasi kartu tanda pelajar fisik (RFID) dapat dilakukan secara tunai ke Bendahara Sekolah, transfer rekening bank, atau scan QRIS resmi sekolah. Simpan bukti transfer untuk verifikasi.",
                'template_wa'            => self::getDefaultWaTemplate(),
                'kuota_ganti_kartu_full' => 1,
                'kuota_ganti_stiker'     => 3,
                'ketentuan_garansi'      => self::getDefaultGaransiKetentuan(),
                'updated_by'             => 'Sistem',
            ]);
        }

        return $settings;
    }

    public static function getDefaultWaTemplate(): string
    {
        return "*INFORMASI ADMINISTRASI & PEMBAYARAN KARTU PELAJAR*\n"
            . "*{NAMA_SEKOLAH}*\n"
            . "--------------------------------------------------\n\n"
            . "Yth. Orang Tua / Wali Murid & Peserta Didik:\n"
            . "• Nama: *{NAMA_SISWA}*\n"
            . "• NISN: *{NISN}*\n"
            . "• Kelas: *{ROMBEL}*\n\n"
            . "*Rincian Status Administrasi Kartu Pelajar:*\n"
            . "• Status Pembayaran: *{STATUS_BAYAR}*\n"
            . "• Biaya / Nominal: *Rp {NOMINAL}*\n"
            . "• Metode Bayar: *{METODE_BAYAR}*\n"
            . "• Tanggal Pembayaran: *{TANGGAL_BAYAR}*\n"
            . "• Status Fisik Kartu: *{STATUS_CETAK}*\n\n"
            . "*Fasilitas Garansi Kartu Pelajar Resmi:*\n"
            . "{INFO_GARANSI}\n\n"
            . "{DESKRIPSI_PEMBAYARAN}\n\n"
            . "*Verifikasi Kartu Pelajar & QR Code Digital:*\n"
            . "Untuk melihat kartu tanda pelajar resmi dan memindai QR Code keabsahan digital, silakan kunjungi:\n"
            . "🔗 {VERIFY_URL}\n\n"
            . "Simpan pesan ini sebagai bukti catatan administrasi resmi. Terima kasih.\n"
            . "_{NAMA_SEKOLAH}_";
    }

    public static function getDefaultGaransiKetentuan(): string
    {
        return "1. Fasilitas Garansi Resmi Kartu Pelajar Siswa:\n"
            . "   - 1x Garansi Ganti Kartu Penuh (Full Cetak Fisik PVC/RFID & Stiker Baru) jika kartu hilang, patah, atau rusak total.\n"
            . "   - 3x Garansi Cetak Stiker Kartu Pelajar Saja jika stiker pudar, terkelupas, atau tergores tanpa kerusakan chip RFID.\n"
            . "2. Masa Berlaku Garansi:\n"
            . "   - Seluruh fasilitas garansi HANYA berlaku selama siswa berstatus sebagai Peserta Didik Aktif di sekolah.\n"
            . "   - Apabila peserta didik telah lulus (alumni), mutasi, atau keluar, seluruh hak garansi otomatis hangus.\n"
            . "3. Pengajuan klaim garansi diverifikasi langsung oleh pihak Administrator / Pengelola Kartu Pelajar di sekolah.\n"
            . "4. Kartu fisik pengganti otomatis disinkronkan kembali nomor RFID dan QR Code identitas digitalnya ke sistem.";
    }
}
