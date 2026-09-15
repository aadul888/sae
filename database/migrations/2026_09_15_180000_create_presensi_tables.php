<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabel Konfigurasi Jam Kerja & Aturan Presensi
        if (!Schema::hasTable('presensi_pengaturan')) {
            Schema::create('presensi_pengaturan', function (Blueprint $table) {
                $table->integer('id')->default(1)->primary();
                $table->time('jam_masuk_mulai')->default('06:00:00')->comment('Jam mulai diperbolehkan scan masuk');
                $table->time('jam_masuk_selesai')->default('07:15:00')->comment('Batas jam masuk tepat waktu');
                $table->time('jam_masuk_toleransi')->default('08:30:00')->comment('Batas akhir presensi masuk pagi');
                $table->time('jam_pulang_mulai')->default('14:30:00')->comment('Jam mulai diperbolehkan scan pulang');
                $table->time('jam_pulang_selesai')->default('17:30:00')->comment('Batas akhir presensi pulang');
                $table->json('hari_aktif')->nullable()->comment('Array nama hari aktif belajar: Senin s/d Jumat');
                $table->unsignedSmallInteger('toleransi_terlambat_menit')->default(0)->comment('Toleransi menit keterlambatan');
                $table->boolean('require_camera')->default(true)->comment('Apakah terminal kiosk otomatis mengambil foto');
                $table->boolean('allow_rfid')->default(true)->comment('Izinkan identifikasi RFID');
                $table->boolean('allow_qr')->default(true)->comment('Izinkan identifikasi QR Code / Barcode');
                $table->boolean('is_active')->default(true)->comment('Status aktif modul presensi');
                $table->time('auto_alpha_time')->default('09:00:00')->comment('Jam batas auto alpha');
                $table->timestamps();
            });

            // Insert baris default pengaturan
            DB::table('presensi_pengaturan')->insert([
                'id' => 1,
                'jam_masuk_mulai' => '06:00:00',
                'jam_masuk_selesai' => '07:15:00',
                'jam_masuk_toleransi' => '08:30:00',
                'jam_pulang_mulai' => '14:30:00',
                'jam_pulang_selesai' => '17:30:00',
                'hari_aktif' => json_encode(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'], JSON_UNESCAPED_UNICODE),
                'toleransi_terlambat_menit' => 0,
                'require_camera' => true,
                'allow_rfid' => true,
                'allow_qr' => true,
                'is_active' => true,
                'auto_alpha_time' => '09:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Tabel Presensi Harian Peserta Didik
        if (!Schema::hasTable('presensi_harian')) {
            Schema::create('presensi_harian', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('peserta_didik_id', 50)->index()->comment('ID Peserta Didik Dapodik');
                $table->string('nisn', 20)->nullable()->index()->comment('NISN Peserta Didik');
                $table->string('rombongan_belajar_id', 50)->nullable()->index()->comment('ID Rombongan Belajar');
                $table->date('tanggal')->index()->comment('Tanggal presensi (Y-m-d)');
                
                // Status kehadiran: H = Hadir, T = Terlambat, I = Izin, S = Sakit, A = Alpha, D = Dispen
                $table->string('status', 5)->default('A')->index()->comment('Status presensi: H, T, I, S, A, D');
                
                $table->time('jam_masuk')->nullable()->comment('Waktu scan masuk');
                $table->time('jam_pulang')->nullable()->comment('Waktu scan pulang');
                $table->unsignedSmallInteger('menit_terlambat')->default(0)->comment('Durasi keterlambatan dalam menit');
                
                $table->string('status_ketepatan_masuk', 25)->nullable()->comment('tepat_waktu atau terlambat');
                $table->string('status_ketepatan_pulang', 25)->nullable()->comment('tepat_waktu atau pulang_cepat');
                
                $table->string('metode_masuk', 25)->nullable()->comment('rfid, qr_code, manual, kamera');
                $table->string('metode_pulang', 25)->nullable()->comment('rfid, qr_code, manual, kamera');
                
                $table->string('foto_masuk', 255)->nullable()->comment('File path snapshot kamera scan masuk');
                $table->string('foto_pulang', 255)->nullable()->comment('File path snapshot kamera scan pulang');
                
                $table->text('keterangan')->nullable()->comment('Catatan/alasan izin/sakit/dispen');
                $table->string('lampiran_dokumen', 255)->nullable()->comment('File path surat bukti izin/sakit');
                $table->string('verified_by', 50)->nullable()->comment('User ID verifikator manual');
                $table->string('device_info', 100)->nullable()->comment('Info terminal/stasiun pemindai');
                
                $table->timestamps();

                // 1 Siswa hanya boleh memiliki 1 record per tanggal
                $table->unique(['peserta_didik_id', 'tanggal'], 'unique_presensi_siswa_tanggal');
            });
        }

        // 3. Tabel Pengajuan Izin / Sakit / Dispensasi Siswa
        if (!Schema::hasTable('presensi_izin')) {
            Schema::create('presensi_izin', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('peserta_didik_id', 50)->index();
                $table->string('nisn', 20)->nullable()->index();
                $table->string('rombongan_belajar_id', 50)->nullable()->index();
                $table->date('tanggal_mulai')->index();
                $table->date('tanggal_selesai')->index();
                $table->string('jenis', 20)->comment('izin, sakit, dispen');
                $table->text('alasan');
                $table->string('lampiran_path', 255)->nullable();
                $table->string('status', 20)->default('menunggu')->comment('menunggu, disetujui, ditolak');
                $table->string('disetujui_oleh', 50)->nullable();
                $table->text('catatan_petugas')->nullable();
                $table->timestamps();
            });
        }

        // 4. Tambah kolom rfid_uid & rfid_registered_at pada tabel peserta_didik_meta
        if (Schema::hasTable('peserta_didik_meta')) {
            Schema::table('peserta_didik_meta', function (Blueprint $table) {
                if (!Schema::hasColumn('peserta_didik_meta', 'rfid_uid')) {
                    $table->string('rfid_uid', 64)->nullable()->unique()->after('nisn')->comment('UID Kartu RFID fisik');
                }
                if (!Schema::hasColumn('peserta_didik_meta', 'rfid_registered_at')) {
                    $table->timestamp('rfid_registered_at')->nullable()->after('rfid_uid');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('peserta_didik_meta')) {
            Schema::table('peserta_didik_meta', function (Blueprint $table) {
                if (Schema::hasColumn('peserta_didik_meta', 'rfid_uid')) {
                    $table->dropColumn('rfid_uid');
                }
                if (Schema::hasColumn('peserta_didik_meta', 'rfid_registered_at')) {
                    $table->dropColumn('rfid_registered_at');
                }
            });
        }

        Schema::dropIfExists('presensi_izin');
        Schema::dropIfExists('presensi_harian');
        Schema::dropIfExists('presensi_pengaturan');
    }
};
