<?php

namespace App\Models;

use App\Support\RefDapodikHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiswaUsulanPerubahan extends Model
{
    use HasFactory;

    protected $table = 'siswa_usulan_perubahan';

    protected $fillable = [
        'peserta_didik_id',
        'batch_id',
        'kolom_perubahan',
        'nilai_lama',
        'nilai_baru',
        'alasan',
        'berkas_bukti',
        'status',
        'catatan_verifikasi',
        'created_by',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function getKolomLabelAttribute(): string
    {
        return self::labelMap()[$this->kolom_perubahan]
            ?? ucwords(str_replace('_', ' ', (string) $this->kolom_perubahan));
    }

    /**
     * Nilai tampilan: kolom yang menyimpan ID referensi Dapodik (agama,
     * pendidikan, pekerjaan, penghasilan, transportasi, hobi, ...) diubah ke
     * label terbaca. Nilai yang sudah berupa teks (nama, alamat, tanggal)
     * dikembalikan apa adanya. Dipakai hanya untuk TAMPILAN — nilai mentah
     * `nilai_baru` tetap dipakai saat menerapkan perubahan ke tabel peserta didik.
     */
    public function nilaiTampilan(mixed $value): string
    {
        $raw = trim((string) $value);
        if ($raw === '' || $raw === '(Kosong)' || $raw === '(Belum Terisi / Kosong)') {
            return (string) $value;
        }

        $map = self::referensiMap()[$this->kolom_perubahan] ?? null;
        if ($map !== null && isset($map[$raw])) {
            return $map[$raw];
        }

        return $raw;
    }

    /**
     * Pemetaan kolom → daftar referensi Dapodik (ID → label).
     * Hanya kolom yang nilainya benar-benar berupa ID angka.
     */
    public static function referensiMap(): array
    {
        return [
            'agama_id' => RefDapodikHelper::getAgama(),
            'tempat_tinggal_id' => RefDapodikHelper::getTempatTinggal(),
            'transportasi_id' => RefDapodikHelper::getTransportasi(),
            'jenis_pendaftaran_id' => RefDapodikHelper::getJenisPendaftaran(),
            'hobi_id' => RefDapodikHelper::getHobi(),
            'cita_cita_id' => RefDapodikHelper::getCitaCita(),
            'pendidikan_ayah_id' => RefDapodikHelper::getJenjangPendidikan(),
            'pekerjaan_ayah_id' => RefDapodikHelper::getPekerjaan(),
            'penghasilan_ayah_id' => RefDapodikHelper::getPenghasilan(),
            'pendidikan_ibu_id' => RefDapodikHelper::getJenjangPendidikan(),
            'pekerjaan_ibu_id' => RefDapodikHelper::getPekerjaan(),
            'penghasilan_ibu_id' => RefDapodikHelper::getPenghasilan(),
        ];
    }

    /**
     * Urutan baku kolom mengikuti urutan BAGIAN pada formulir identitas
     * (Data Pribadi → Ayah → Ibu → Wali → Kontak → Pendaftaran → Periodik).
     * Dipakai agar daftar butir usulan dibaca berurutan seperti isian Dapodik,
     * sehingga operator tidak bolak-balik antar-bagian saat update ke Dapodik.
     */
    public static function urutanFormulir(): array
    {
        return [
            // Bagian 1: Data Pribadi & Alamat
            'nama', 'jenis_kelamin', 'nik', 'no_kk', 'tempat_lahir', 'tanggal_lahir',
            'no_registrasi_akta_lahir', 'agama_id',
            'alamat_jalan', 'rt', 'rw', 'provinsi', 'kabupaten_kota', 'kecamatan',
            'desa_kelurahan', 'kode_pos', 'tempat_tinggal_id', 'transportasi_id', 'anak_keberapa',
            // Bagian 2: Data Ayah
            'nama_ayah', 'nik_ayah', 'tahun_lahir_ayah', 'pendidikan_ayah_id', 'pekerjaan_ayah_id',
            'kebutuhan_khusus_ayah_id', 'penghasilan_ayah_id',
            // Bagian 3: Data Ibu
            'nama_ibu', 'nik_ibu', 'tahun_lahir_ibu', 'pendidikan_ibu_id', 'pekerjaan_ibu_id',
            'kebutuhan_khusus_ibu_id', 'penghasilan_ibu_id',
            // Bagian 4: Data Wali
            'nama_wali', 'nik_wali', 'tahun_lahir_wali', 'pendidikan_wali_id', 'pekerjaan_wali_id',
            'kebutuhan_khusus_wali_id', 'penghasilan_wali_id',
            // Bagian 5: Kontak
            'nomor_telepon_rumah', 'nomor_telepon_seluler', 'email',
            // Bagian 8: Pendaftaran Masuk
            'jenis_pendaftaran_id', 'tanggal_masuk_sekolah', 'program_keahlian', 'sekolah_asal',
            'hobi_id', 'cita_cita_id',
            // Bagian 9: Data Periodik
            'tinggi_badan', 'berat_badan', 'lingkar_kepala', 'jarak_rumah_sekolah',
            'jarak_rumah_sekolah_km', 'waktu_tempuh_jam', 'waktu_tempuh_menit', 'jumlah_saudara_kandung',
        ];
    }

    /** Peringkat kolom pada urutan formulir (kolom tak dikenal ditaruh paling akhir). */
    public static function rankFormulir(?string $kolom): int
    {
        $i = array_search((string) $kolom, self::urutanFormulir(), true);

        return $i === false ? PHP_INT_MAX : $i;
    }

    /**
     * Status agregat satu pengajuan (banyak butir). Aturan sesuai alur kerja:
     * - masih ada `menunggu` → menunggu (butuh tindakan verifikator)
     * - ada `ditolak` bersamaan dengan `disetujui` yang belum ke Dapodik → ditolak
     *   (satu field ditolak menandai seluruh pengajuan; rinciannya di modal)
     * - ada `disetujui` (siap Dapodik) → disetujui
     * - `ditolak` saja dan belum ada yang ke Dapodik → ditolak
     * - ada `sudah_ke_dapodik` → selesai di Dapodik
     */
    public static function agregatStatus(iterable $statuses): string
    {
        $c = collect($statuses)->countBy();

        return match (true) {
            ($c['menunggu'] ?? 0) > 0 => 'menunggu',
            ($c['ditolak'] ?? 0) > 0 && ($c['disetujui'] ?? 0) > 0 => 'ditolak',
            ($c['disetujui'] ?? 0) > 0 => 'disetujui',
            ($c['ditolak'] ?? 0) > 0 && ($c['sudah_ke_dapodik'] ?? 0) === 0 => 'ditolak',
            ($c['sudah_ke_dapodik'] ?? 0) > 0 => 'sudah_ke_dapodik',
            default => 'menunggu',
        };
    }

    public static function labelMap(): array
    {
        return [
            'nama' => 'Nama Lengkap Siswa',
            'jenis_kelamin' => 'Jenis Kelamin',
            'nik' => 'Nomor Induk Kependudukan (NIK)',
            'no_kk' => 'Nomor Kartu Keluarga (KK)',
            'no_registrasi_akta_lahir' => 'No. Registrasi Akta Lahir',
            'kewarganegaraan' => 'Kewarganegaraan',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'agama_id' => 'Agama & Kepercayaan',
            'alamat_jalan' => 'Alamat Jalan / Tempat Tinggal',
            'rt' => 'RT',
            'rw' => 'RW',
            'desa_kelurahan' => 'Desa / Kelurahan',
            'kecamatan' => 'Kecamatan',
            'kabupaten_kota' => 'Kabupaten / Kota',
            'provinsi' => 'Provinsi',
            'kode_pos' => 'Kode Pos',
            'tempat_tinggal_id' => 'Status Tempat Tinggal',
            'transportasi_id' => 'Moda Transportasi',
            'anak_keberapa' => 'Anak Ke-berapa (di KK)',
            'nama_ayah' => 'Nama Ayah Kandung',
            'nik_ayah' => 'NIK Ayah',
            'tahun_lahir_ayah' => 'Tahun Lahir Ayah',
            'pendidikan_ayah_id' => 'Pendidikan Ayah',
            'pekerjaan_ayah_id' => 'Pekerjaan Ayah',
            'penghasilan_ayah_id' => 'Penghasilan Ayah',
            'nama_ibu' => 'Nama Ibu Kandung',
            'nik_ibu' => 'NIK Ibu',
            'tahun_lahir_ibu' => 'Tahun Lahir Ibu',
            'pendidikan_ibu_id' => 'Pendidikan Ibu',
            'pekerjaan_ibu_id' => 'Pekerjaan Ibu',
            'penghasilan_ibu_id' => 'Penghasilan Ibu',
            'nama_wali' => 'Nama Wali',
            'nik_wali' => 'NIK Wali',
            'nomor_telepon_rumah' => 'Nomor Telepon Rumah',
            'nomor_telepon_seluler' => 'No. HP / WhatsApp Siswa',
            'email' => 'Email Siswa',
            'sekolah_asal' => 'Sekolah Asal',
            'tinggi_badan' => 'Tinggi Badan (cm)',
            'berat_badan' => 'Berat Badan (kg)',
            'lingkar_kepala' => 'Lingkar Kepala (cm)',
            'jarak_rumah_sekolah' => 'Jarak Rumah ke Sekolah',
            'jarak_rumah_sekolah_km' => 'Jarak Rumah (km)',
            'waktu_tempuh_jam' => 'Waktu Tempuh (Jam)',
            'waktu_tempuh_menit' => 'Waktu Tempuh (Menit)',
            'jumlah_saudara_kandung' => 'Jumlah Saudara Kandung',
        ];
    }

    public function siswa()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }
}
