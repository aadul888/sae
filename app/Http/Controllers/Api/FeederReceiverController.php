<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class FeederReceiverController extends Controller
{
    private function validateApiKey(Request $request): bool
    {
        $serverKey = DB::table('settings')->where('id', 1)->value('api_key') ?? 'sae_secret_live_key_2026';
        
        $headerKey = $request->header('X-API-Key') ?? $request->header('x-api-key');
        if (!$headerKey && $request->hasHeader('Authorization')) {
            $auth = $request->header('Authorization');
            if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
                $headerKey = $matches[1];
            }
        }
        if (!$headerKey) {
            $headerKey = $request->input('api_key');
        }

        return !empty($headerKey) && hash_equals($serverKey, $headerKey);
    }

    public function receive(Request $request)
    {
        if (!$this->validateApiKey($request)) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Unauthorized: API Key tidak valid.'
            ], 401);
        }

        $payload = $request->json()->all();
        if (empty($payload)) {
            $payload = $request->all();
        }

        $type = $payload['type'] ?? ($payload['endpoint'] ?? $request->input('type', $request->input('endpoint')));
        $data = $payload['data'] ?? $request->input('data');

        if ($type === 'auth_check') {
            return response()->json([
                'status' => 'success',
                'success' => true,
                'authenticated' => true,
                'message' => 'Koneksi API SAE berhasil terhubung.'
            ]);
        }

        if (!$type || !is_array($data)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Format payload tidak valid. Type dan data wajib dikirim.'
            ], 400);
        }

        try {
            $count = 0;
            $details = [];

            if ($type === 'syncAll') {
                if (isset($data['getSekolah'])) {
                    $details['getSekolah'] = $this->processSekolah($data['getSekolah']);
                    $count += $details['getSekolah'];
                }
                if (isset($data['getRombonganBelajar'])) {
                    $details['getRombonganBelajar'] = $this->processRombel($data['getRombonganBelajar']);
                    $count += $details['getRombonganBelajar'];
                }
                if (isset($data['getGtk'])) {
                    $details['getGtk'] = $this->processGtk($data['getGtk']);
                    $count += $details['getGtk'];
                }
                if (isset($data['getPesertaDidik'])) {
                    $details['getPesertaDidik'] = $this->processPesertaDidik($data['getPesertaDidik']);
                    $count += $details['getPesertaDidik'];
                }
                if (isset($data['getPengguna'])) {
                    $details['getPengguna'] = $this->processPengguna($data['getPengguna']);
                    $count += $details['getPengguna'];
                }
            } else {
                switch ($type) {
                    case 'getSekolah':
                        $count = $this->processSekolah($data);
                        break;
                    case 'getRombonganBelajar':
                        $count = $this->processRombel($data);
                        break;
                    case 'getGtk':
                        $count = $this->processGtk($data);
                        break;
                    case 'getPesertaDidik':
                        $count = $this->processPesertaDidik($data);
                        break;
                    case 'getPengguna':
                        $count = $this->processPengguna($data);
                        break;
                    default:
                        return response()->json([
                            'status' => 'error',
                            'message' => "Tipe data '{$type}' tidak dikenali."
                        ], 400);
                }
            }

            // Update timestamp last_sync pada tabel settings id=1
            DB::table('settings')->where('id', 1)->update([
                'last_sync' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'success' => true,
                'type' => $type,
                'count' => $count,
                'details' => $details,
                'message' => "Berhasil memproses {$count} data {$type} ke database SAE."
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Gagal menyimpan ke database: ' . $e->getMessage()
            ], 500);
        }
    }

    private function archiveTable(string $table, string $backupTable): void
    {
        try {
            $count = DB::table($table)->count();
            if ($count > 0) {
                // Salin semua record live ke backup table menggunakan irisan kolom yang ada di kedua tabel
                $sourceCols = DB::getSchemaBuilder()->getColumnListing($table);
                $targetCols = DB::getSchemaBuilder()->getColumnListing($backupTable);
                $commonCols = array_values(array_intersect($sourceCols, $targetCols));
                $colsWithoutId = array_diff($commonCols, ['id', 'archived_at']);

                if (!empty($colsWithoutId)) {
                    $colList = implode('`, `', $colsWithoutId);
                    DB::statement("INSERT INTO `{$backupTable}` (`{$colList}`, `archived_at`) SELECT `{$colList}`, NOW() FROM `{$table}`");
                }
            }
        } catch (\Exception $e) {
            // Log backup failure without crashing ingestion flow
        }
    }

    private function processSekolah($data): int
    {
        $sekolah = $data[0] ?? $data;
        if (empty($sekolah['sekolah_id'])) {
            return 0;
        }

        $this->archiveTable('sekolah', 'backup_sekolah');

        $record = [
            'sekolah_id' => $sekolah['sekolah_id'],
            'nama' => $sekolah['nama'] ?? null,
            'nss' => $sekolah['nss'] ?? null,
            'npsn' => $sekolah['npsn'] ?? null,
            'bentuk_pendidikan_id' => $sekolah['bentuk_pendidikan_id'] ?? null,
            'bentuk_pendidikan_id_str' => $sekolah['bentuk_pendidikan_id_str'] ?? null,
            'status_sekolah' => $sekolah['status_sekolah'] ?? null,
            'status_sekolah_str' => $sekolah['status_sekolah_str'] ?? null,
            'alamat_jalan' => $sekolah['alamat_jalan'] ?? null,
            'rt' => $sekolah['rt'] ?? null,
            'rw' => $sekolah['rw'] ?? null,
            'dusun' => $sekolah['dusun'] ?? null,
            'desa_kelurahan' => $sekolah['desa_kelurahan'] ?? null,
            'kode_wilayah' => $sekolah['kode_wilayah'] ?? null,
            'kode_pos' => $sekolah['kode_pos'] ?? null,
            'lintang' => (string)($sekolah['lintang'] ?? ''),
            'bujur' => (string)($sekolah['bujur'] ?? ''),
            'nomor_telepon' => $sekolah['nomor_telepon'] ?? null,
            'nomor_fax' => $sekolah['nomor_fax'] ?? null,
            'email' => $sekolah['email'] ?? null,
            'website' => $sekolah['website'] ?? null,
            'is_sks' => (string)($sekolah['is_sks'] ?? '0'),
            'kecamatan' => $sekolah['kecamatan'] ?? null,
            'kabupaten_kota' => $sekolah['kabupaten_kota'] ?? null,
            'provinsi' => $sekolah['provinsi'] ?? null,
            'raw_data' => json_encode($sekolah, JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
            'created_at' => now(),
        ];

        DB::table('sekolah')->truncate();
        DB::table('sekolah')->insert($record);
        return 1;
    }

    private function processRombel(array $data): int
    {
        $now = now();
        $batchRombel = [];
        $batchAnggota = [];
        $batchPembelajaran = [];

        foreach ($data as $rombel) {
            if (empty($rombel['rombongan_belajar_id'])) {
                continue;
            }

            $rombelId = $rombel['rombongan_belajar_id'];

            // Extract nested anggota_rombel
            if (!empty($rombel['anggota_rombel'])) {
                $anggotaList = is_string($rombel['anggota_rombel']) ? json_decode($rombel['anggota_rombel'], true) : $rombel['anggota_rombel'];
                if (is_array($anggotaList)) {
                    foreach ($anggotaList as $ar) {
                        if (!empty($ar['anggota_rombel_id'])) {
                            $batchAnggota[$ar['anggota_rombel_id']] = [
                                'anggota_rombel_id' => $ar['anggota_rombel_id'],
                                'rombongan_belajar_id' => $ar['rombongan_belajar_id'] ?? $rombelId,
                                'peserta_didik_id' => $ar['peserta_didik_id'] ?? null,
                                'jenis_pendaftaran_id' => $ar['jenis_pendaftaran_id'] ?? null,
                                'jenis_pendaftaran_id_str' => $ar['jenis_pendaftaran_id_str'] ?? null,
                                'raw_data' => json_encode($ar, JSON_UNESCAPED_UNICODE),
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }
                }
            }

            // Extract nested pembelajaran
            if (!empty($rombel['pembelajaran'])) {
                $pemList = is_string($rombel['pembelajaran']) ? json_decode($rombel['pembelajaran'], true) : $rombel['pembelajaran'];
                if (is_array($pemList)) {
                    foreach ($pemList as $pem) {
                        if (!empty($pem['pembelajaran_id'])) {
                            $batchPembelajaran[$pem['pembelajaran_id']] = [
                                'pembelajaran_id' => $pem['pembelajaran_id'],
                                'rombongan_belajar_id' => $pem['rombongan_belajar_id'] ?? $rombelId,
                                'mata_pelajaran_id' => $pem['mata_pelajaran_id'] ?? null,
                                'mata_pelajaran_id_str' => $pem['mata_pelajaran_id_str'] ?? null,
                                'ptk_terdaftar_id' => $pem['ptk_terdaftar_id'] ?? null,
                                'ptk_id' => $pem['ptk_id'] ?? null,
                                'nama_mata_pelajaran' => $pem['nama_mata_pelajaran'] ?? null,
                                'induk_pembelajaran_id' => $pem['induk_pembelajaran_id'] ?? null,
                                'jam_mengajar_per_minggu' => (string)($pem['jam_mengajar_per_minggu'] ?? '0'),
                                'status_di_kurikulum' => $pem['status_di_kurikulum'] ?? null,
                                'status_di_kurikulum_str' => $pem['status_di_kurikulum_str'] ?? null,
                                'raw_data' => json_encode($pem, JSON_UNESCAPED_UNICODE),
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }
                }
            }

            $batchRombel[] = [
                'rombongan_belajar_id' => $rombelId,
                'nama' => $rombel['nama'] ?? '-',
                'tingkat_pendidikan_id' => $rombel['tingkat_pendidikan_id'] ?? null,
                'tingkat_pendidikan_id_str' => $rombel['tingkat_pendidikan_id_str'] ?? null,
                'semester_id' => $rombel['semester_id'] ?? null,
                'jenis_rombel' => $rombel['jenis_rombel'] ?? null,
                'jenis_rombel_str' => $rombel['jenis_rombel_str'] ?? null,
                'kurikulum_id' => $rombel['kurikulum_id'] ?? null,
                'kurikulum_id_str' => $rombel['kurikulum_id_str'] ?? null,
                'id_ruang' => $rombel['id_ruang'] ?? null,
                'id_ruang_str' => $rombel['id_ruang_str'] ?? null,
                'moving_class' => $rombel['moving_class'] ?? null,
                'ptk_id' => $rombel['ptk_id'] ?? null,
                'ptk_id_str' => $rombel['ptk_id_str'] ?? null,
                'jurusan_id' => $rombel['jurusan_id'] ?? null,
                'jurusan_id_str' => $rombel['jurusan_id_str'] ?? null,
                'anggota_rombel' => isset($rombel['anggota_rombel']) ? (is_string($rombel['anggota_rombel']) ? $rombel['anggota_rombel'] : json_encode($rombel['anggota_rombel'], JSON_UNESCAPED_UNICODE)) : null,
                'pembelajaran' => isset($rombel['pembelajaran']) ? (is_string($rombel['pembelajaran']) ? $rombel['pembelajaran'] : json_encode($rombel['pembelajaran'], JSON_UNESCAPED_UNICODE)) : null,
                'raw_data' => json_encode($rombel, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($batchRombel)) {
            return 0;
        }

        // Backup existing data
        $this->archiveTable('rombongan_belajar', 'backup_rombongan_belajar');
        $this->archiveTable('anggota_rombel', 'backup_anggota_rombel');
        $this->archiveTable('pembelajaran', 'backup_pembelajaran');

        // Overwrite rombongan_belajar
        DB::table('rombongan_belajar')->truncate();
        foreach (array_chunk($batchRombel, 200) as $chunk) {
            DB::table('rombongan_belajar')->insert($chunk);
        }

        // Overwrite relational anggota_rombel & pembelajaran
        if (!empty($batchAnggota)) {
            DB::table('anggota_rombel')->truncate();
            foreach (array_chunk(array_values($batchAnggota), 200) as $chunk) {
                DB::table('anggota_rombel')->insert($chunk);
            }
        }

        if (!empty($batchPembelajaran)) {
            DB::table('pembelajaran')->truncate();
            foreach (array_chunk(array_values($batchPembelajaran), 200) as $chunk) {
                DB::table('pembelajaran')->insert($chunk);
            }
        }

        return count($batchRombel);
    }

    private function processGtk(array $data): int
    {
        $now = now();
        $batchGtk = [];

        foreach ($data as $gtk) {
            if (empty($gtk['ptk_id'])) {
                continue;
            }

            $email = !empty($gtk['email']) ? $gtk['email'] : null;
            $nip = !empty($gtk['nip']) ? $gtk['nip'] : null;
            $nuptk = !empty($gtk['nuptk']) ? $gtk['nuptk'] : null;
            $nik = !empty($gtk['nik']) ? $gtk['nik'] : null;

            $batchGtk[] = [
                'ptk_id' => $gtk['ptk_id'],
                'tahun_ajaran_id' => $gtk['tahun_ajaran_id'] ?? null,
                'ptk_terdaftar_id' => $gtk['ptk_terdaftar_id'] ?? null,
                'ptk_induk' => $gtk['ptk_induk'] ?? null,
                'tanggal_surat_tugas' => $gtk['tanggal_surat_tugas'] ?? null,
                'nama' => $gtk['nama'] ?? '-',
                'jenis_kelamin' => $gtk['jenis_kelamin'] ?? null,
                'tempat_lahir' => $gtk['tempat_lahir'] ?? null,
                'tanggal_lahir' => $gtk['tanggal_lahir'] ?? null,
                'agama_id' => isset($gtk['agama_id']) ? (int)$gtk['agama_id'] : null,
                'agama_id_str' => $gtk['agama_id_str'] ?? null,
                'nuptk' => $nuptk,
                'nik' => $nik,
                'jenis_ptk_id' => $gtk['jenis_ptk_id'] ?? null,
                'jenis_ptk_id_str' => $gtk['jenis_ptk_id_str'] ?? null,
                'jabatan_ptk_id' => $gtk['jabatan_ptk_id'] ?? null,
                'jabatan_ptk_id_str' => $gtk['jabatan_ptk_id_str'] ?? null,
                'status_kepegawaian_id' => $gtk['status_kepegawaian_id'] ?? null,
                'status_kepegawaian_id_str' => $gtk['status_kepegawaian_id_str'] ?? null,
                'nip' => $nip,
                'pendidikan_terakhir' => $gtk['pendidikan_terakhir'] ?? null,
                'bidang_studi_terakhir' => $gtk['bidang_studi_terakhir'] ?? null,
                'pangkat_golongan_terakhir' => $gtk['pangkat_golongan_terakhir'] ?? null,
                'email' => $email,
                'no_hp' => $gtk['no_hp'] ?? null,
                'alamat_jalan' => $gtk['alamat_jalan'] ?? null,
                'rwy_pend_formal' => isset($gtk['rwy_pend_formal']) ? (is_string($gtk['rwy_pend_formal']) ? $gtk['rwy_pend_formal'] : json_encode($gtk['rwy_pend_formal'], JSON_UNESCAPED_UNICODE)) : null,
                'rwy_kepangkatan' => isset($gtk['rwy_kepangkatan']) ? (is_string($gtk['rwy_kepangkatan']) ? $gtk['rwy_kepangkatan'] : json_encode($gtk['rwy_kepangkatan'], JSON_UNESCAPED_UNICODE)) : null,
                'raw_data' => json_encode($gtk, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($batchGtk)) {
            return 0;
        }

        // Backup existing data
        $this->archiveTable('gtk', 'backup_gtk');

        // Overwrite live gtk
        DB::table('gtk')->truncate();
        foreach (array_chunk($batchGtk, 200) as $chunk) {
            DB::table('gtk')->insert($chunk);
        }

        return count($batchGtk);
    }

    private function processPesertaDidik(array $data): int
    {
        $now = now();
        $batchPd = [];
        $batchPenggunaSiswa = [];
        $defaultPassword = Hash::make('Sae12345!');

        foreach ($data as $pd) {
            if (empty($pd['peserta_didik_id'])) {
                continue;
            }

            $email = !empty($pd['email']) ? $pd['email'] : null;
            $nisn = !empty($pd['nisn']) ? $pd['nisn'] : null;
            $nik = !empty($pd['nik']) ? $pd['nik'] : null;

            $batchPd[] = [
                'peserta_didik_id' => $pd['peserta_didik_id'],
                'registrasi_id' => $pd['registrasi_id'] ?? null,
                'jenis_pendaftaran_id' => $pd['jenis_pendaftaran_id'] ?? null,
                'jenis_pendaftaran_id_str' => $pd['jenis_pendaftaran_id_str'] ?? null,
                'nipd' => $pd['nipd'] ?? null,
                'tanggal_masuk_sekolah' => $pd['tanggal_masuk_sekolah'] ?? null,
                'sekolah_asal' => $pd['sekolah_asal'] ?? null,
                'nama' => $pd['nama'] ?? '-',
                'nisn' => $nisn,
                'jenis_kelamin' => $pd['jenis_kelamin'] ?? null,
                'nik' => $nik,
                'tempat_lahir' => $pd['tempat_lahir'] ?? null,
                'tanggal_lahir' => $pd['tanggal_lahir'] ?? null,
                'agama_id' => isset($pd['agama_id']) ? (int)$pd['agama_id'] : null,
                'agama_id_str' => $pd['agama_id_str'] ?? null,
                'nomor_telepon_rumah' => $pd['nomor_telepon_rumah'] ?? null,
                'nomor_telepon_seluler' => $pd['nomor_telepon_seluler'] ?? null,
                'nama_ayah' => $pd['nama_ayah'] ?? null,
                'pekerjaan_ayah_id' => isset($pd['pekerjaan_ayah_id']) ? (int)$pd['pekerjaan_ayah_id'] : null,
                'pekerjaan_ayah_id_str' => $pd['pekerjaan_ayah_id_str'] ?? null,
                'nama_ibu' => $pd['nama_ibu'] ?? null,
                'pekerjaan_ibu_id' => isset($pd['pekerjaan_ibu_id']) ? (int)$pd['pekerjaan_ibu_id'] : null,
                'pekerjaan_ibu_id_str' => $pd['pekerjaan_ibu_id_str'] ?? null,
                'nama_wali' => $pd['nama_wali'] ?? null,
                'pekerjaan_wali_id' => isset($pd['pekerjaan_wali_id']) ? (int)$pd['pekerjaan_wali_id'] : null,
                'pekerjaan_wali_id_str' => $pd['pekerjaan_wali_id_str'] ?? null,
                'anak_keberapa' => $pd['anak_keberapa'] ?? null,
                'tinggi_badan' => $pd['tinggi_badan'] ?? null,
                'berat_badan' => $pd['berat_badan'] ?? null,
                'email' => $email,
                'semester_id' => $pd['semester_id'] ?? null,
                'anggota_rombel_id' => $pd['anggota_rombel_id'] ?? null,
                'rombongan_belajar_id' => $pd['rombongan_belajar_id'] ?? null,
                'tingkat_pendidikan_id' => $pd['tingkat_pendidikan_id'] ?? null,
                'nama_rombel' => $pd['nama_rombel'] ?? null,
                'kurikulum_id' => $pd['kurikulum_id'] ?? null,
                'kurikulum_id_str' => $pd['kurikulum_id_str'] ?? null,
                'kebutuhan_khusus' => $pd['kebutuhan_khusus'] ?? null,
                'alamat_jalan' => $pd['alamat_jalan'] ?? null,
                'raw_data' => json_encode($pd, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Akun pengguna siswa (username & password default = NISN / NIK)
            $username = $nisn ?: ($nik ?: $pd['peserta_didik_id']);
            $plainPass = $nisn ?: ($nik ?: 'Sae12345!');

            $batchPenggunaSiswa[] = [
                'pengguna_id' => $pd['peserta_didik_id'],
                'sekolah_id' => null,
                'username' => $username,
                'nama' => $pd['nama'] ?? '-',
                'peran_id_str' => 'Peserta Didik',
                'password' => password_hash($plainPass, PASSWORD_BCRYPT, ['cost' => 8]),
                'alamat' => $pd['alamat_jalan'] ?? null,
                'no_telepon' => $pd['nomor_telepon_rumah'] ?? null,
                'no_hp' => $pd['nomor_telepon_seluler'] ?? null,
                'ptk_id' => null,
                'peserta_didik_id' => $pd['peserta_didik_id'],
                'raw_data' => json_encode($pd, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($batchPd)) {
            return 0;
        }

        // Backup existing data
        $this->archiveTable('peserta_didik', 'backup_peserta_didik');

        // Overwrite live peserta_didik
        DB::table('peserta_didik')->truncate();
        foreach (array_chunk($batchPd, 250) as $chunk) {
            DB::table('peserta_didik')->insert($chunk);
        }

        // Hapus akun siswa lama lalu insert akun siswa baru
        DB::table('pengguna')->whereNotNull('peserta_didik_id')->orWhere('peran_id_str', 'Peserta Didik')->delete();
        foreach (array_chunk($batchPenggunaSiswa, 250) as $chunk) {
            DB::table('pengguna')->insert($chunk);
        }

        return count($batchPd);
    }

    private function processPengguna(array $data): int
    {
        $now = now();
        $batchPengguna = [];
        $defaultPassword = Hash::make('Sae12345!');
        $seenPenggunaIds = [];

        foreach ($data as $u) {
            if (empty($u['pengguna_id'])) {
                continue;
            }

            $penggunaId = $u['pengguna_id'];
            if (isset($seenPenggunaIds[$penggunaId])) {
                continue; // Dapodik web service terkadang mengembalikan duplikat pengguna_id pada pagination
            }
            $seenPenggunaIds[$penggunaId] = true;

            $ptkId = !empty($u['ptk_id']) ? $u['ptk_id'] : null;
            $username = $u['username'] ?? $penggunaId;

            $batchPengguna[] = [
                'pengguna_id' => $penggunaId,
                'sekolah_id' => $u['sekolah_id'] ?? null,
                'username' => $username,
                'nama' => $u['nama'] ?? '-',
                'peran_id_str' => $u['peran_id_str'] ?? null,
                'password' => !empty($u['password']) ? $u['password'] : $defaultPassword,
                'alamat' => $u['alamat'] ?? null,
                'no_telepon' => $u['no_telepon'] ?? null,
                'no_hp' => $u['no_hp'] ?? null,
                'ptk_id' => $ptkId,
                'peserta_didik_id' => $u['peserta_didik_id'] ?? null,
                'raw_data' => json_encode($u, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($batchPengguna)) {
            return 0;
        }

        // Backup existing live Dapodik pengguna
        $this->archiveTable('pengguna', 'backup_pengguna');

        // Hapus akun selain peserta didik agar tidak menghapus akun siswa yang dibuat dari processPesertaDidik
        DB::table('pengguna')->whereNull('peserta_didik_id')->where('peran_id_str', '!=', 'Peserta Didik')->delete();
        foreach (array_chunk($batchPengguna, 200) as $chunk) {
            DB::table('pengguna')->insert($chunk);
        }

        // Bersihkan sesi aktif agar user otomatis logout dan login dengan akun Dapodik
        try {
            $sessionPath = storage_path('framework/sessions');
            if (is_dir($sessionPath)) {
                $files = glob($sessionPath . '/*');
                foreach ($files as $file) {
                    if (is_file($file) && basename($file) !== '.gitignore') {
                        @unlink($file);
                    }
                }
            }
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->truncate();
            }
        } catch (\Exception $e) {
            // Ignore session purge warning
        }

        return count($batchPengguna);
    }
}



