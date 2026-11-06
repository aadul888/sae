<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiswaUsulanPerubahan extends Model
{
    use HasFactory;

    protected $table = 'siswa_usulan_perubahan';

    protected $fillable = [
        'peserta_didik_id',
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
