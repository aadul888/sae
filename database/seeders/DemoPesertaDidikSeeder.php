<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Seeder data CONTOH 1 peserta didik untuk keperluan tutorial / rekaman video.
 *
 * Data sengaja dibuat fiktif (bukan data siswa nyata) agar aman dari
 * pelanggaran privasi saat dipublikasikan. Identitas tetap: NISN 0001234567.
 *
 * Jalankan:
 *   php artisan db:seed --class=DemoPesertaDidikSeeder
 *
 * Login demo (Portal SAE tab GTK & Siswa):
 *   Username : 0001234567
 *   Password : Sae12345!
 *
 * Hapus data demo bila sudah selesai (aman, hanya menghapus baris demo):
 *   php artisan tinker
 *   >>> \Illuminate\Support\Facades\DB::table('siswa_usulan_perubahan')->where('peserta_didik_id','demo-tutorial-0001')->delete();
 *   >>> \Illuminate\Support\Facades\DB::table('peserta_didik_berkas')->where('peserta_didik_id','demo-tutorial-0001')->delete();
 *   >>> \Illuminate\Support\Facades\DB::table('peserta_didik_identitas')->where('peserta_didik_id','demo-tutorial-0001')->delete();
 *   >>> \Illuminate\Support\Facades\DB::table('peserta_didik_meta')->where('peserta_didik_id','demo-tutorial-0001')->delete();
 *   >>> \Illuminate\Support\Facades\DB::table('peserta_didik')->where('peserta_didik_id','demo-tutorial-0001')->delete();
 *   >>> \Illuminate\Support\Facades\DB::table('pengguna')->where('username','0001234567')->delete();
 */
class DemoPesertaDidikSeeder extends Seeder
{
    /** ID tetap & jelas milik data demo agar mudah dihapus. */
    private const PD_ID = 'demo-tutorial-0001';

    private const NISN = '0001234567';

    private const NIPD = '2627000001';

    private const PASSWORD = 'Sae12345!';

    public function run(): void
    {
        $now = now();

        // 1. Data inti peserta didik (tabel Dapodik)
        if (Schema::hasTable('peserta_didik')) {
            DB::table('peserta_didik')->updateOrInsert(
                ['peserta_didik_id' => self::PD_ID],
                [
                    'jenis_pendaftaran_id' => '1',
                    'jenis_pendaftaran_id_str' => 'Siswa baru',
                    'nipd' => self::NIPD,
                    'tanggal_masuk_sekolah' => '2026-07-13',
                    'sekolah_asal' => 'SMP NEGERI CONTOH 1',
                    'nama' => 'CONTOH PESERTA DIDIK',
                    'nisn' => self::NISN,
                    'jenis_kelamin' => 'L',
                    'nik' => '3200000000000001',
                    'tempat_lahir' => 'Bandung',
                    'tanggal_lahir' => '2010-05-14',
                    'agama_id' => 1,
                    'agama_id_str' => 'Islam',
                    'nomor_telepon_rumah' => '0220000001',
                    'nomor_telepon_seluler' => '081200000001',
                    'nama_ayah' => 'CONTOH AYAH',
                    'pekerjaan_ayah_id' => 12,
                    'pekerjaan_ayah_id_str' => 'Wiraswasta',
                    'nama_ibu' => 'CONTOH IBU',
                    'pekerjaan_ibu_id' => 1,
                    'pekerjaan_ibu_id_str' => 'Tidak bekerja',
                    'nama_wali' => null,
                    'anak_keberapa' => '1',
                    'tinggi_badan' => '165',
                    'berat_badan' => '55',
                    'email' => 'contoh.peserta@example.test',
                    'semester_id' => '20261',
                    'tingkat_pendidikan_id' => '10',
                    'nama_rombel' => 'X CONTOH 1',
                    // Dikosongkan bila baris pernah ditimpa impor Excel (impor mengisi kedua ini).
                    'anggota_rombel_id' => null,
                    'rombongan_belajar_id' => null,
                    'kurikulum_id_str' => 'SMK Merdeka (Contoh)',
                    'kebutuhan_khusus' => 'Tidak ada',
                    'alamat_jalan' => 'Jl. Contoh Pendidikan No. 1',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // 2. Formulir identitas lengkap (isolasi SAE, tidak tertimpa Dapodik)
        if (Schema::hasTable('peserta_didik_identitas')) {
            DB::table('peserta_didik_identitas')->updateOrInsert(
                ['peserta_didik_id' => self::PD_ID],
                [
                    'nisn' => self::NISN,
                    'nipd' => self::NIPD,
                    'nama' => 'CONTOH PESERTA DIDIK',
                    'jenis_kelamin' => 'L',
                    'nik' => '3200000000000001',
                    'no_kk' => '3200000000000002',
                    'no_registrasi_akta_lahir' => 'AL-CONTOH-0001',
                    'kewarganegaraan' => 'WNI',
                    'tempat_lahir' => 'Bandung',
                    'tanggal_lahir' => '2010-05-14',
                    'agama_id' => '1',
                    'agama_id_str' => 'Islam',
                    'kebutuhan_khusus_id' => '0',
                    'kebutuhan_khusus_str' => 'Tidak ada',
                    'anak_keberapa' => 1,
                    'tinggi_badan' => 165,
                    'berat_badan' => 55,
                    'golongan_darah' => 'O',
                    'penyakit_pernah_diderita' => null,
                    'kelainan_jasmani' => null,
                    'bahasa_sehari_hari' => 'Bahasa Indonesia',
                    'alamat_jalan' => 'Jl. Contoh Pendidikan No. 1',
                    'rt' => '001',
                    'rw' => '002',
                    'nama_dusun' => 'Contoh Dusun',
                    'desa_kelurahan' => 'Contoh Kelurahan',
                    'kecamatan' => 'Contoh Kecamatan',
                    'kabupaten_kota' => 'Kabupaten Contoh',
                    'provinsi' => 'Jawa Barat',
                    'kode_pos' => '40000',
                    'tempat_tinggal_id' => '1',
                    'tempat_tinggal_str' => 'Bersama orang tua',
                    'transportasi_id' => '3',
                    'transportasi_str' => 'Sepeda motor',
                    'status_hidup_ayah' => '1',
                    'nama_ayah' => 'CONTOH AYAH',
                    'nik_ayah' => '3200000000000003',
                    'tahun_lahir_ayah' => '1980',
                    'pendidikan_ayah_id' => '6',
                    'pendidikan_ayah_str' => 'SMA / sederajat',
                    'pekerjaan_ayah_id' => '12',
                    'pekerjaan_ayah_str' => 'Wiraswasta',
                    'penghasilan_ayah_id' => '4',
                    'penghasilan_ayah_str' => 'Rp. 2.000.000 - Rp. 4.999.999',
                    'kebutuhan_khusus_ayah_id' => '0',
                    'kebutuhan_khusus_ayah_str' => 'Tidak ada',
                    'status_hidup_ibu' => '1',
                    'nama_ibu' => 'CONTOH IBU',
                    'nik_ibu' => '3200000000000004',
                    'tahun_lahir_ibu' => '1982',
                    'pendidikan_ibu_id' => '6',
                    'pendidikan_ibu_str' => 'SMA / sederajat',
                    'pekerjaan_ibu_id' => '1',
                    'pekerjaan_ibu_str' => 'Tidak bekerja',
                    'penghasilan_ibu_id' => '0',
                    'penghasilan_ibu_str' => 'Tidak Berpenghasilan',
                    'kebutuhan_khusus_ibu_id' => '0',
                    'kebutuhan_khusus_ibu_str' => 'Tidak ada',
                    'mempunyai_wali' => 0,
                    'nomor_telepon_rumah' => '0220000001',
                    'nomor_telepon_seluler' => '081200000001',
                    'email' => 'contoh.peserta@example.test',
                    'jenis_pendaftaran_id' => '1',
                    'jenis_pendaftaran_str' => 'Siswa baru (PPDB)',
                    'tanggal_masuk_sekolah' => '2026-07-13',
                    'sekolah_asal' => 'SMP NEGERI CONTOH 1',
                    'pernah_paud_formal' => 1,
                    'pernah_paud_non_formal' => 0,
                    'hobi_id' => '20',
                    'hobi_str' => 'Membaca',
                    'cita_cita_id' => '3',
                    'cita_cita_str' => 'Dokter',
                    'lingkar_kepala' => 54,
                    'jarak_rumah_sekolah' => 'Kurang dari 1 km',
                    'jarak_rumah_sekolah_km' => 0.80,
                    'waktu_tempuh_jam' => 0,
                    'waktu_tempuh_menit' => 15,
                    'jumlah_saudara_kandung' => 2,
                    'program_keahlian' => 'Contoh Kompetensi Keahlian',
                    // 'perlu_perbaikan' + berkas valid => formulir terbuka untuk demo pengisian
                    'status_konfirmasi' => 'perlu_perbaikan',
                    'terakhir_diubah_oleh' => 'demo',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // 3. Berkas prasyarat (KK & Ijazah) berstatus VALID agar formulir dapat dibuka
        if (Schema::hasTable('peserta_didik_berkas')) {
            $berkas = [
                'kartu_keluarga' => 'Kartu Keluarga (KK) - Contoh.pdf',
                'ijazah_smp' => 'Ijazah SMP - Contoh.pdf',
                'akta_kelahiran' => 'Akta Kelahiran - Contoh.pdf',
                'ktp_orang_tua' => 'KTP Orang Tua - Contoh.pdf',
            ];

            foreach ($berkas as $jenis => $nama) {
                DB::table('peserta_didik_berkas')->updateOrInsert(
                    ['peserta_didik_id' => self::PD_ID, 'jenis_berkas' => $jenis],
                    [
                        'nama_berkas' => $jenis,
                        'file_path' => 'demo-tutorial/' . $jenis . '.pdf',
                        'file_name' => $nama,
                        'file_size' => 0,
                        'mime_type' => 'application/pdf',
                        'status' => 'valid',
                        'verified_by' => 'Demo Seeder',
                        'verified_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        // 4. Meta peserta didik (NISN untuk pencarian cepat)
        if (Schema::hasTable('peserta_didik_meta')) {
            DB::table('peserta_didik_meta')->updateOrInsert(
                ['peserta_didik_id' => self::PD_ID],
                ['nisn' => self::NISN, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        // 5. Akun login peserta didik (tabel pengguna)
        if (Schema::hasTable('pengguna')) {
            User::updateOrCreate(
                ['username' => self::NISN],
                [
                    'pengguna_id' => self::PD_ID,
                    'nama' => 'CONTOH PESERTA DIDIK',
                    'peran_id_str' => 'Peserta Didik',
                    'password' => Hash::make(self::PASSWORD),
                    'password_updated_at' => $now,
                    'peserta_didik_id' => self::PD_ID,
                    'no_hp' => '081200000001',
                    'alamat' => 'Jl. Contoh Pendidikan No. 1',
                ]
            );
        }

        $this->command?->info('Data demo peserta didik dibuat. Login: ' . self::NISN . ' / ' . self::PASSWORD);
    }
}
