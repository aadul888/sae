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
        // 1. Master Referensi Tugas Tambahan
        if (!Schema::hasTable('ref_tugas_tambahan')) {
            Schema::create('ref_tugas_tambahan', function (Blueprint $table) {
                $table->id();
                $table->string('kode', 50)->unique();
                $table->string('nama', 150);
                $table->enum('kelompok', ['guru', 'tendik'])->index();
                $table->string('bidang', 100)->nullable();
                $table->decimal('ekuivalensi_jam', 4, 1)->default(0);
                $table->string('icon', 50)->default('fa-briefcase');
                $table->json('granted_permissions')->nullable(); // Daftar permission_key yang dibuka otomatis
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Penetapan Tugas Tambahan ke PTK / Akun Pengguna
        if (!Schema::hasTable('ptk_tugas_tambahan')) {
            Schema::create('ptk_tugas_tambahan', function (Blueprint $table) {
                $table->id();
                $table->string('user_id', 50)->nullable()->index();
                $table->string('ptk_id', 50)->nullable()->index();
                $table->unsignedBigInteger('tugas_tambahan_id')->index();
                $table->string('nomor_sk', 100)->nullable();
                $table->date('tmt_tugas')->nullable();
                $table->date('tst_tugas')->nullable();
                $table->string('rombel_id', 50)->nullable()->index(); // Khusus Wali Kelas
                $table->string('jurusan_id', 50)->nullable()->index(); // Khusus Kaprog
                $table->text('keterangan')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('tugas_tambahan_id')
                    ->references('id')
                    ->on('ref_tugas_tambahan')
                    ->onDelete('cascade');
            });
        }

        // Seed data referensi sesuai AI_AGENT_CONTEXT.md
        $now = now();
        $items = [
            // GURU: A. Bidang Manajemen Sekolah (12 Jam)
            [
                'kode' => 'WAKA_KURIKULUM',
                'nama' => 'Wakil Kepala Sekolah Bidang Kurikulum',
                'kelompok' => 'guru',
                'bidang' => 'Manajemen Sekolah',
                'ekuivalensi_jam' => 12.0,
                'icon' => 'fa-book-open',
                'granted_permissions' => json_encode(['menu_pembelajaran', 'menu_kompetensi_keahlian', 'menu_rombel', 'menu_jadwal_pelajaran']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode' => 'WAKA_KESISWAAN',
                'nama' => 'Wakil Kepala Sekolah Bidang Kesiswaan',
                'kelompok' => 'guru',
                'bidang' => 'Manajemen Sekolah',
                'ekuivalensi_jam' => 12.0,
                'icon' => 'fa-user-graduate',
                'granted_permissions' => json_encode(['menu_peserta_didik_aktif', 'menu_poin', 'menu_e_izin', 'menu_riwayat_rfid']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode' => 'WAKA_HUBIN',
                'nama' => 'Wakil Kepala Sekolah Bidang Hubungan Masyarakat / Hubin',
                'kelompok' => 'guru',
                'bidang' => 'Manajemen Sekolah',
                'ekuivalensi_jam' => 12.0,
                'icon' => 'fa-handshake',
                'granted_permissions' => json_encode(['menu_pengumuman', 'menu_buku_tamu', 'menu_agenda']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode' => 'WAKA_SARPRAS',
                'nama' => 'Wakil Kepala Sekolah Bidang Sarana Prasarana',
                'kelompok' => 'guru',
                'bidang' => 'Manajemen Sekolah',
                'ekuivalensi_jam' => 12.0,
                'icon' => 'fa-boxes-stacked',
                'granted_permissions' => json_encode(['menu_inventaris']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // GURU: B. Bidang Pembelajaran & Unit Kompetensi (12 Jam)
            [
                'kode' => 'KAPROG',
                'nama' => 'Kepala Program Keahlian (Kaprog)',
                'kelompok' => 'guru',
                'bidang' => 'Pembelajaran & Unit Kompetensi',
                'ekuivalensi_jam' => 12.0,
                'icon' => 'fa-laptop-code',
                'granted_permissions' => json_encode(['menu_kompetensi_keahlian', 'menu_rombel', 'menu_pembelajaran', 'menu_peserta_didik_aktif']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode' => 'KEPALA_PERPUS',
                'nama' => 'Kepala Perpustakaan',
                'kelompok' => 'guru',
                'bidang' => 'Pembelajaran & Unit Kompetensi',
                'ekuivalensi_jam' => 12.0,
                'icon' => 'fa-book',
                'granted_permissions' => json_encode(['menu_inventaris']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode' => 'KEPALA_LAB',
                'nama' => 'Kepala Laboratorium / Bengkel / Ruang Praktik',
                'kelompok' => 'guru',
                'bidang' => 'Pembelajaran & Unit Kompetensi',
                'ekuivalensi_jam' => 12.0,
                'icon' => 'fa-flask-vial',
                'granted_permissions' => json_encode(['menu_inventaris']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // GURU: C. Bidang Pendampingan & Khusus (1 - 2 Jam)
            [
                'kode' => 'WALI_KELAS',
                'nama' => 'Wali Kelas',
                'kelompok' => 'guru',
                'bidang' => 'Pendampingan & Khusus',
                'ekuivalensi_jam' => 2.0,
                'icon' => 'fa-chalkboard-user',
                'granted_permissions' => json_encode(['menu_peserta_didik_aktif', 'menu_presensi_peserta_didik', 'menu_e_izin', 'menu_rapor']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode' => 'PEMBINA_OSIS',
                'nama' => 'Pembina OSIS',
                'kelompok' => 'guru',
                'bidang' => 'Pendampingan & Khusus',
                'ekuivalensi_jam' => 2.0,
                'icon' => 'fa-award',
                'granted_permissions' => json_encode(['menu_agenda', 'menu_pengumuman', 'menu_poin']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode' => 'PEMBINA_EKSKUL',
                'nama' => 'Pembina Ekstrakurikuler / Pramuka',
                'kelompok' => 'guru',
                'bidang' => 'Pendampingan & Khusus',
                'ekuivalensi_jam' => 2.0,
                'icon' => 'fa-campground',
                'granted_permissions' => json_encode(['menu_agenda', 'menu_pengumuman']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode' => 'GURU_PIKET',
                'nama' => 'Guru Piket',
                'kelompok' => 'guru',
                'bidang' => 'Pendampingan & Khusus',
                'ekuivalensi_jam' => 1.0,
                'icon' => 'fa-clipboard-check',
                'granted_permissions' => json_encode(['menu_buku_tamu', 'menu_e_izin', 'menu_riwayat_rfid']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // TENDIK: A. Bidang Administrasi Utama & Data
            [
                'kode' => 'KEPALA_TAS',
                'nama' => 'Kepala Tenaga Administrasi Sekolah (Kepala TAS / KTU)',
                'kelompok' => 'tendik',
                'bidang' => 'Administrasi Utama & Data',
                'ekuivalensi_jam' => 0,
                'icon' => 'fa-briefcase',
                'granted_permissions' => json_encode(['menu_tendik_aktif', 'menu_guru_aktif', 'menu_peserta_didik_aktif', 'menu_buku_tamu', 'menu_inventaris', 'menu_berkas_peserta_didik']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode' => 'OPERATOR_DAPODIK',
                'nama' => 'Operator Dapodik Sekolah (Ops)',
                'kelompok' => 'tendik',
                'bidang' => 'Administrasi Utama & Data',
                'ekuivalensi_jam' => 0,
                'icon' => 'fa-cloud-arrow-down',
                'granted_permissions' => json_encode(['menu_dapodik', 'menu_peserta_didik_aktif', 'menu_guru_aktif', 'menu_tendik_aktif', 'menu_rombel', 'menu_pembelajaran']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode' => 'STAF_KEPEGAWAIAN',
                'nama' => 'Staf Administrasi Kepegawaian (Urusan Kepegawaian)',
                'kelompok' => 'tendik',
                'bidang' => 'Administrasi Utama & Data',
                'ekuivalensi_jam' => 0,
                'icon' => 'fa-id-badge',
                'granted_permissions' => json_encode(['menu_guru_aktif', 'menu_tendik_aktif', 'menu_guru_tidak_aktif', 'menu_tendik_tidak_aktif']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode' => 'STAF_KESISWAAN',
                'nama' => 'Staf Administrasi Kesiswaan (Urusan Kesiswaan)',
                'kelompok' => 'tendik',
                'bidang' => 'Administrasi Utama & Data',
                'ekuivalensi_jam' => 0,
                'icon' => 'fa-user-graduate',
                'granted_permissions' => json_encode(['menu_peserta_didik_aktif', 'menu_peserta_didik_tidak_aktif', 'menu_berkas_peserta_didik', 'menu_kelulusan']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode' => 'STAF_PERSURATAN',
                'nama' => 'Staf Administrasi Persuratan (Arsiparis)',
                'kelompok' => 'tendik',
                'bidang' => 'Administrasi Utama & Data',
                'ekuivalensi_jam' => 0,
                'icon' => 'fa-envelope-open-text',
                'granted_permissions' => json_encode(['menu_berkas_peserta_didik', 'menu_buku_tamu']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode' => 'STAF_SARPRAS',
                'nama' => 'Staf Administrasi Sarpras (Urusan Inventaris & Fasilitas)',
                'kelompok' => 'tendik',
                'bidang' => 'Administrasi Utama & Data',
                'ekuivalensi_jam' => 0,
                'icon' => 'fa-boxes-stacked',
                'granted_permissions' => json_encode(['menu_inventaris']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode' => 'TEKNISI_IT',
                'nama' => 'Teknisi Gedung / Lapangan / Jaringan IT',
                'kelompok' => 'tendik',
                'bidang' => 'Administrasi Utama & Data',
                'ekuivalensi_jam' => 0,
                'icon' => 'fa-network-wired',
                'granted_permissions' => json_encode(['menu_rfid', 'menu_inventaris']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode' => 'LABORAN',
                'nama' => 'Laboran (Staf Khusus Ruang Laboratorium)',
                'kelompok' => 'tendik',
                'bidang' => 'Administrasi Utama & Data',
                'ekuivalensi_jam' => 0,
                'icon' => 'fa-flask',
                'granted_permissions' => json_encode(['menu_inventaris']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // TENDIK: B. Bidang Pelayanan Lingkungan & Umum
            [
                'kode' => 'PUSTAKAWAN',
                'nama' => 'Pustakawan (Staf Pelayanan Perpustakaan)',
                'kelompok' => 'tendik',
                'bidang' => 'Pelayanan Lingkungan & Umum',
                'ekuivalensi_jam' => 0,
                'icon' => 'fa-book-bookmark',
                'granted_permissions' => json_encode(['menu_inventaris']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode' => 'SATPAM',
                'nama' => 'Petugas Keamanan (Satpam Sekolah)',
                'kelompok' => 'tendik',
                'bidang' => 'Pelayanan Lingkungan & Umum',
                'ekuivalensi_jam' => 0,
                'icon' => 'fa-shield-halved',
                'granted_permissions' => json_encode(['menu_buku_tamu', 'menu_rfid']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode' => 'PENJAGA_SEKOLAH',
                'nama' => 'Penjaga Sekolah / Pesuruh (Staf Kebersihan & Rumah Tangga)',
                'kelompok' => 'tendik',
                'bidang' => 'Pelayanan Lingkungan & Umum',
                'ekuivalensi_jam' => 0,
                'icon' => 'fa-broom',
                'granted_permissions' => json_encode(['menu_buku_tamu']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('ref_tugas_tambahan')->insert($items);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ptk_tugas_tambahan');
        Schema::dropIfExists('ref_tugas_tambahan');
    }
};
