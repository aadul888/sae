<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesertaDidikIdentitas extends Model
{
    use HasFactory;

    protected $table = 'peserta_didik_identitas';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_masuk_sekolah' => 'date',
        'mempunyai_wali' => 'boolean',
        'pernah_paud_formal' => 'boolean',
        'pernah_paud_non_formal' => 'boolean',
        'riwayat_prestasi' => 'array',
        'perlindungan_sosial' => 'array',
        'dikonfirmasi_pada' => 'datetime',
    ];

    public function siswa()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }

    /**
     * Inisialisasi atau ambil data identitas lengkap dari data inti Dapodik jika belum ada di tabel khusus ini
     */
    public static function getOrCreateFromPd($pd): self
    {
        if (!$pd) {
            throw new \InvalidArgumentException('Objek peserta didik tidak valid.');
        }

        $identitas = self::where('peserta_didik_id', $pd->peserta_didik_id)->first();
        if ($identitas) {
            return $identitas;
        }

        // Ekstrak data mentah Dapodik jika tersedia untuk pre-fill
        $rawData = [];
        if (!empty($pd->raw_data)) {
            $rawData = is_array($pd->raw_data) ? $pd->raw_data : json_decode($pd->raw_data, true);
        }

        return self::create([
            'peserta_didik_id' => $pd->peserta_didik_id,
            'nisn' => $pd->nisn ?? '',
            'nipd' => $pd->nipd ?? ($rawData['nipd'] ?? null),
            'nama' => $pd->nama ?? '',
            'jenis_kelamin' => $pd->jenis_kelamin ?? 'L',
            'nik' => $pd->nik ?? ($rawData['nik'] ?? null),
            'no_kk' => $rawData['no_kk'] ?? null,
            'no_registrasi_akta_lahir' => $rawData['no_registrasi_akta_lahir'] ?? null,
            'kewarganegaraan' => $rawData['kewarganegaraan'] ?? 'WNI',
            'tempat_lahir' => $pd->tempat_lahir ?? ($rawData['tempat_lahir'] ?? null),
            'tanggal_lahir' => $pd->tanggal_lahir ?? ($rawData['tanggal_lahir'] ?? null),
            'agama_id' => $pd->agama_id ?? ($rawData['agama_id'] ?? '1'),
            'agama_id_str' => $pd->agama_id_str ?? ($rawData['agama_id_str'] ?? 'Islam'),
            'kebutuhan_khusus_id' => $rawData['kebutuhan_khusus_id'] ?? '0',
            'kebutuhan_khusus_str' => $pd->kebutuhan_khusus ?? ($rawData['kebutuhan_khusus'] ?? 'Tidak ada'),
            'anak_keberapa' => $pd->anak_keberapa ?? ($rawData['anak_keberapa'] ?? 1),
            'tinggi_badan' => $pd->tinggi_badan ?? ($rawData['tinggi_badan'] ?? null),
            'berat_badan' => $pd->berat_badan ?? ($rawData['berat_badan'] ?? null),

            // Alamat
            'alamat_jalan' => $pd->alamat_jalan ?? ($rawData['alamat_jalan'] ?? null),
            'rt' => $rawData['rt'] ?? null,
            'rw' => $rawData['rw'] ?? null,
            'nama_dusun' => $rawData['nama_dusun'] ?? null,
            'desa_kelurahan' => $rawData['desa_kelurahan'] ?? null,
            'kecamatan' => $rawData['kecamatan'] ?? null,
            'kabupaten_kota' => $rawData['kabupaten_kota'] ?? null,
            'provinsi' => $rawData['provinsi'] ?? null,
            'kode_pos' => $rawData['kode_pos'] ?? null,
            'lintang' => $rawData['lintang'] ?? null,
            'bujur' => $rawData['bujur'] ?? null,
            'tempat_tinggal_id' => $rawData['tempat_tinggal_id'] ?? '1',
            'tempat_tinggal_str' => $rawData['tempat_tinggal_id_str'] ?? 'Bersama orang tua',
            'transportasi_id' => $rawData['transportasi_id'] ?? '1',
            'transportasi_str' => $rawData['transportasi_id_str'] ?? 'Jalan kaki',

            // Bank PIP
            'nama_bank' => $rawData['nama_bank'] ?? null,
            'no_rekening' => $rawData['no_rekening_bank'] ?? ($rawData['nomor_rekening'] ?? null),
            'kcp_bank' => $rawData['kcp_bank'] ?? ($rawData['rekening_kcp'] ?? null),
            'rekening_atas_nama' => $rawData['rekening_atas_nama'] ?? null,

            // Ayah
            'status_hidup_ayah' => (str_contains(strtolower($pd->pekerjaan_ayah_id_str ?? ''), 'meninggal') ? '0' : '1'),
            'nama_ayah' => $pd->nama_ayah ?? ($rawData['nama_ayah'] ?? null),
            'nik_ayah' => $rawData['nik_ayah'] ?? null,
            'tahun_lahir_ayah' => $rawData['tahun_lahir_ayah'] ?? null,
            'pendidikan_ayah_id' => $rawData['pendidikan_ayah_id'] ?? null,
            'pendidikan_ayah_str' => $rawData['pendidikan_ayah_id_str'] ?? null,
            'pekerjaan_ayah_id' => $pd->pekerjaan_ayah_id ?? ($rawData['pekerjaan_ayah_id'] ?? null),
            'pekerjaan_ayah_str' => $pd->pekerjaan_ayah_id_str ?? ($rawData['pekerjaan_ayah_id_str'] ?? null),
            'penghasilan_ayah_id' => $rawData['penghasilan_ayah_id'] ?? null,
            'penghasilan_ayah_str' => $rawData['penghasilan_ayah_id_str'] ?? null,
            'kebutuhan_khusus_ayah_id' => '0',
            'kebutuhan_khusus_ayah_str' => 'Tidak ada',

            // Ibu
            'status_hidup_ibu' => (str_contains(strtolower($pd->pekerjaan_ibu_id_str ?? ''), 'meninggal') ? '0' : '1'),
            'nama_ibu' => $pd->nama_ibu ?? ($rawData['nama_ibu'] ?? null),
            'nik_ibu' => $rawData['nik_ibu'] ?? null,
            'tahun_lahir_ibu' => $rawData['tahun_lahir_ibu'] ?? null,
            'pendidikan_ibu_id' => $rawData['pendidikan_ibu_id'] ?? null,
            'pendidikan_ibu_str' => $rawData['pendidikan_ibu_id_str'] ?? null,
            'pekerjaan_ibu_id' => $pd->pekerjaan_ibu_id ?? ($rawData['pekerjaan_ibu_id'] ?? null),
            'pekerjaan_ibu_str' => $pd->pekerjaan_ibu_id_str ?? ($rawData['pekerjaan_ibu_id_str'] ?? null),
            'penghasilan_ibu_id' => $rawData['penghasilan_ibu_id'] ?? null,
            'penghasilan_ibu_str' => $rawData['penghasilan_ibu_id_str'] ?? null,
            'kebutuhan_khusus_ibu_id' => '0',
            'kebutuhan_khusus_ibu_str' => 'Tidak ada',

            // Wali
            'mempunyai_wali' => !empty($pd->nama_wali),
            'nama_wali' => $pd->nama_wali ?? ($rawData['nama_wali'] ?? null),
            'nik_wali' => $rawData['nik_wali'] ?? null,
            'tahun_lahir_wali' => $rawData['tahun_lahir_wali'] ?? null,
            'pendidikan_wali_id' => $rawData['pendidikan_wali_id'] ?? null,
            'pendidikan_wali_str' => $rawData['pendidikan_wali_id_str'] ?? null,
            'pekerjaan_wali_id' => $pd->pekerjaan_wali_id ?? ($rawData['pekerjaan_wali_id'] ?? null),
            'pekerjaan_wali_str' => $pd->pekerjaan_wali_id_str ?? ($rawData['pekerjaan_wali_id_str'] ?? null),
            'penghasilan_wali_id' => $rawData['penghasilan_wali_id'] ?? null,
            'penghasilan_wali_str' => $rawData['penghasilan_wali_id_str'] ?? null,
            'kebutuhan_khusus_wali_id' => '0',
            'kebutuhan_khusus_wali_str' => 'Tidak ada',

            // Kontak
            'nomor_telepon_rumah' => $pd->nomor_telepon_rumah ?? ($rawData['nomor_telepon_rumah'] ?? null),
            'nomor_telepon_seluler' => $pd->nomor_telepon_seluler ?? ($rawData['nomor_telepon_seluler'] ?? null),
            'email' => $pd->email ?? ($rawData['email'] ?? null),

            // Registrasi Masuk
            'jenis_pendaftaran_id' => $pd->jenis_pendaftaran_id ?? ($rawData['jenis_pendaftaran_id'] ?? '1'),
            'jenis_pendaftaran_str' => $pd->jenis_pendaftaran_id_str ?? ($rawData['jenis_pendaftaran_id_str'] ?? 'Siswa baru'),
            'tanggal_masuk_sekolah' => $pd->tanggal_masuk_sekolah ?? ($rawData['tanggal_masuk_sekolah'] ?? null),
            'sekolah_asal' => $pd->sekolah_asal ?? ($rawData['sekolah_asal'] ?? null),
            'pernah_paud_formal' => false,
            'pernah_paud_non_formal' => false,

            // Status Konfirmasi
            'status_konfirmasi' => 'belum_konfirmasi',
        ]);
    }
}
