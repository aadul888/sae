<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('formulir')) {
            Schema::create('formulir', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('judul', 255);
                $table->string('slug', 150)->unique();
                $table->text('deskripsi')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('is_public')->default(false)->index();
                $table->boolean('auth_required')->default(true)->index();
                $table->string('target_peran', 50)->default('semua')->index(); // semua, peserta_didik, guru, tendik
                $table->string('target_rombel_id', 100)->nullable()->index();
                $table->string('target_tingkat', 20)->nullable()->index();
                $table->boolean('limit_one_response')->default(true);
                $table->dateTime('tanggal_mulai')->nullable();
                $table->dateTime('tanggal_selesai')->nullable();
                $table->longText('skema'); // JSON field definition
                $table->json('pengaturan')->nullable(); // UI/color theme, button text, confirmation message
                $table->string('created_by', 100)->nullable();
                $table->string('pengguna_id', 100)->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('formulir_respon')) {
            Schema::create('formulir_respon', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('formulir_id')->index();
                $table->string('pengguna_id', 100)->nullable()->index();
                $table->string('peserta_didik_id', 100)->nullable()->index();
                $table->string('ptk_id', 100)->nullable()->index();
                $table->string('nama_responden', 150)->nullable();
                $table->string('identitas_responden', 150)->nullable(); // NISN, Rombel, dsb
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('jawaban'); // JSON jawaban responden
                $table->timestamps();

                $table->foreign('formulir_id')->references('id')->on('formulir')->onDelete('cascade');
            });
        }

        // Seed template formulir sekolah awal
        $now = now();
        $sampleSchema1 = json_encode([
            [
                'id' => 'field_nama',
                'type' => 'sae_nama',
                'label' => 'Nama Lengkap Siswa',
                'placeholder' => 'Terisi otomatis dari akun SAE',
                'required' => true,
                'description' => 'Nama lengkap peserta didik',
            ],
            [
                'id' => 'field_nisn',
                'type' => 'sae_nisn',
                'label' => 'NISN',
                'placeholder' => 'Terisi otomatis dari akun SAE',
                'required' => true,
                'description' => 'Nomor Induk Siswa Nasional resmi',
            ],
            [
                'id' => 'field_rombel',
                'type' => 'sae_rombel',
                'label' => 'Kelas / Rombel',
                'placeholder' => 'Terisi otomatis dari akun SAE',
                'required' => true,
                'description' => 'Rombongan belajar saat ini',
            ],
            [
                'id' => 'field_ekskul',
                'type' => 'select',
                'label' => 'Pilihan Ekstrakurikuler Utama',
                'placeholder' => '-- Pilih Ekstrakurikuler --',
                'required' => true,
                'description' => 'Pilih 1 ekskul wajib/pilihan yang ingin diikuti',
                'options' => [
                    'Pramuka Penegak',
                    'PMR (Palang Merah Remaja)',
                    'Paskibra Sekolah',
                    'Klub IT & Robotika (Coding/IoT)',
                    'Seni Tari & Karawitan',
                    'Futsal & Sepak Bola',
                    'Bola Voli',
                    'Basket Club',
                    'English Conversation Club'
                ]
            ],
            [
                'id' => 'field_alasan',
                'type' => 'textarea',
                'label' => 'Motivasi & Alasan Memilih',
                'placeholder' => 'Ceritakan mengapa kamu tertarik dengan ekskul ini...',
                'required' => false,
                'description' => 'Tuliskan secara singkat',
            ],
            [
                'id' => 'field_wa',
                'type' => 'text',
                'label' => 'Nomor WhatsApp Siswa / Wali Murid',
                'placeholder' => '08xxxxxxxxxx',
                'required' => true,
                'description' => 'Untuk koordinasi grup ekskul',
            ]
        ], JSON_UNESCAPED_UNICODE);

        $sampleSettings1 = json_encode([
            'theme_color' => '#0284c7', // Sky blue
            'button_text' => 'Kirim Pendaftaran Ekskul',
            'success_title' => 'Pendaftaran Berhasil Diterima!',
            'success_message' => 'Terima kasih telah mendaftar ekstrakurikuler. Jadwal perdana kegiatan akan diumumkan melalui wali kelas dan koordinator pembina.',
        ], JSON_UNESCAPED_UNICODE);

        // Contoh form ke-2: Survei Kepuasan Fasilitas Sekolah (Publik / Campuran)
        $sampleSchema2 = json_encode([
            [
                'id' => 'field_peran',
                'type' => 'radio',
                'label' => 'Status Responden',
                'required' => true,
                'description' => 'Pilih peran Anda di lingkungan sekolah',
                'options' => [
                    'Peserta Didik',
                    'Orang Tua / Wali Murid',
                    'Guru / Tenaga Pendidik',
                    'Masyarakat Umum / Tamu'
                ]
            ],
            [
                'id' => 'field_rating_sarpras',
                'type' => 'rating',
                'label' => 'Penilaian Kebersihan & Kenyamanan Fasilitas',
                'required' => true,
                'description' => 'Skala 1 (Sangat Kurang) sampai 5 (Sangat Memuaskan)',
                'options' => ['1', '2', '3', '4', '5']
            ],
            [
                'id' => 'field_fasilitas_prioritas',
                'type' => 'checkbox',
                'label' => 'Fasilitas yang Perlu Ditingkatkan',
                'required' => false,
                'description' => 'Dapat memilih lebih dari satu opsi',
                'options' => [
                    'Laboratorium Komputer & Internet WiFi',
                    'Perpustakaan & Ruang Baca Digital',
                    'Kantin Sehat & Sanitasi',
                    'Sarana Olahraga & Lapangan',
                    'Toilet & Tempat Cuci Tangan'
                ]
            ],
            [
                'id' => 'field_saran',
                'type' => 'textarea',
                'label' => 'Kritik, Saran & Masukan Positif',
                'placeholder' => 'Tuliskan saran Anda untuk kemajuan sekolah...',
                'required' => false,
                'description' => 'Bantu kami meningkatkan kualitas layanan pendidikan',
            ]
        ], JSON_UNESCAPED_UNICODE);

        $sampleSettings2 = json_encode([
            'theme_color' => '#10b981', // Emerald green
            'button_text' => 'Kirim Masukan',
            'success_title' => 'Terima Kasih atas Aspirasi Anda!',
            'success_message' => 'Masukan berharga Anda telah tercatat dan akan menjadi bahan evaluasi manajemen sekolah.',
        ], JSON_UNESCAPED_UNICODE);

        DB::table('formulir')->insert([
            [
                'judul' => 'Pendaftaran Ekstrakurikuler Semester Genap',
                'slug' => 'pendaftaran-ekstrakurikuler-2026',
                'deskripsi' => 'Formulir resmi pemilihan dan pendaftaran kegiatan ekstrakurikuler peserta didik. Pastikan memilih sesuai minat dan bakat Anda.',
                'is_active' => true,
                'is_public' => false,
                'auth_required' => true,
                'target_peran' => 'peserta_didik',
                'target_rombel_id' => null,
                'target_tingkat' => null,
                'limit_one_response' => true,
                'tanggal_mulai' => null,
                'tanggal_selesai' => null,
                'skema' => $sampleSchema1,
                'pengaturan' => $sampleSettings1,
                'created_by' => 'Administrator',
                'pengguna_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'judul' => 'Survei Kepuasan Layanan & Fasilitas Sekolah',
                'slug' => 'survei-kepuasan-layanan-sekolah',
                'deskripsi' => 'Survei terbuka untuk menjaring aspirasi, saran, dan penilaian seluruh civitas akademika serta wali murid demi peningkatan kualitas mutu sekolah.',
                'is_active' => true,
                'is_public' => true,
                'auth_required' => false,
                'target_peran' => 'semua',
                'target_rombel_id' => null,
                'target_tingkat' => null,
                'limit_one_response' => false,
                'tanggal_mulai' => null,
                'tanggal_selesai' => null,
                'skema' => $sampleSchema2,
                'pengaturan' => $sampleSettings2,
                'created_by' => 'Administrator',
                'pengguna_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('formulir_respon');
        Schema::dropIfExists('formulir');
    }
};
