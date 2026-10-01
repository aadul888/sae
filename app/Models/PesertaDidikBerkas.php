<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesertaDidikBerkas extends Model
{
    use HasFactory;

    protected $table = 'peserta_didik_berkas';

    protected $fillable = [
        'peserta_didik_id',
        'jenis_berkas',
        'nama_berkas',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'status',
        'catatan_penolakan',
        'rekomendasi_penolakan',
        'verified_by',
        'verified_at',
        'uploaded_by',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'file_size' => 'integer',
    ];

    /**
     * Cek apakah dokumen wajib (KK dan Ijazah SMP) sudah diunggah dan berstatus valid
     */
    public static function checkPrerequisites(string $pdId): array
    {
        $berkas = self::where('peserta_didik_id', $pdId)
            ->whereIn('jenis_berkas', ['kartu_keluarga', 'ijazah_smp'])
            ->get()
            ->keyBy('jenis_berkas');

        $kk = $berkas->get('kartu_keluarga');
        $ijazah = $berkas->get('ijazah_smp');

        $kkValid = ($kk && $kk->status === 'valid');
        $ijazahValid = ($ijazah && $ijazah->status === 'valid');

        return [
            'is_valid' => $kkValid && $ijazahValid,
            'kk_valid' => $kkValid,
            'ijazah_valid' => $ijazahValid,
            'kk_status' => $kk?->status ?? 'belum_unggah',
            'ijazah_status' => $ijazah?->status ?? 'belum_unggah',
            'kk_file' => $kk?->file_name,
            'ijazah_file' => $ijazah?->file_name,
            'kk_catatan' => $kk?->catatan_penolakan,
            'ijazah_catatan' => $ijazah?->catatan_penolakan,
        ];
    }

    /**
     * Daftar Baku Jenis Berkas Siswa
     */
    public static function getJenisBerkasOptions(): array
    {
        return [
            'akta_kelahiran' => [
                'label' => 'Akta Kelahiran',
                'deskripsi' => 'Scan/Foto Akta Kelahiran resmi dari Disdukcapil (format PDF).',
                'icon' => 'fa-certificate',
                'wajib' => true,
            ],
            'kartu_keluarga' => [
                'label' => 'Kartu Keluarga (KK)',
                'deskripsi' => 'Scan/Foto Kartu Keluarga terbaru yang memuat nama siswa (format PDF).',
                'icon' => 'fa-people-roof',
                'wajib' => true,
            ],
            'ijazah_smp' => [
                'label' => 'Ijazah / SKL SMP/MTs',
                'deskripsi' => 'Scan Ijazah asli atau Surat Keterangan Lulus (SKL) jenjang SMP/MTs (format PDF).',
                'icon' => 'fa-graduation-cap',
                'wajib' => true,
            ],
            'ktp_orang_tua' => [
                'label' => 'KTP Orang Tua / Wali',
                'deskripsi' => 'Scan KTP elektronik Ayah/Ibu/Wali yang masih berlaku (format PDF).',
                'icon' => 'fa-id-card',
                'wajib' => true,
            ],
            'kip_pip' => [
                'label' => 'Kartu KIP / PIP / KKS',
                'deskripsi' => 'Scan fisik kartu KIP/PIP/KKS bagi siswa penerima bantuan (format PDF, opsional).',
                'icon' => 'fa-hand-holding-heart',
                'wajib' => false,
            ],
            'lainnya' => [
                'label' => 'Dokumen Tambahan / Lainnya',
                'deskripsi' => 'Sertifikat prestasi, surat pernyataan, atau dokumen pendukung lain (format PDF).',
                'icon' => 'fa-file-lines',
                'wajib' => false,
            ],
        ];
    }

    /**
     * Rekomendasi Pilihan Alasan Penolakan untuk Verifikator Kesiswaan
     */
    public static function getRekomendasiPenolakanOptions(): array
    {
        return [
            'File PDF buram atau tidak terbaca dengan jelas (resolusi terlalu rendah).',
            'Halaman dokumen terpotong atau bagian halaman belakang belum terlampir.',
            'Bukan dokumen asli atau legalisir basah yang sah.',
            'Data identitas (Nama / NIK / Tanggal Lahir) tidak cocok dengan formulir Dapodik.',
            'Salah jenis dokumen (file yang diunggah tertukar dengan berkas dokumen lain).',
            'Masa berlaku dokumen sudah kadaluarsa atau tidak sah.',
            'File PDF rusak (corrupt) atau terkunci kata sandi (password).',
        ];
    }

    public function siswa()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }

    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . ltrim($this->file_path, '/'));
    }

    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'valid' => 'Valid / Sesuai',
            'tidak_valid' => 'Tidak Valid / Tidak Sesuai',
            default => 'Menunggu Verifikasi',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'valid' => 'badge-success',
            'tidak_valid' => 'badge-danger',
            default => 'badge-warning',
        };
    }
}
