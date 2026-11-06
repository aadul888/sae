<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah kolom 'tipe' pada tabel pengumuman
        if (Schema::hasTable('pengumuman') && !Schema::hasColumn('pengumuman', 'tipe')) {
            Schema::table('pengumuman', function (Blueprint $table) {
                $table->string('tipe', 30)->default('pengumuman')->after('id')->index();
            });

            // Update data lama: target 'publik' menjadi tipe 'broadcast'
            DB::table('pengumuman')
                ->where('target', 'publik')
                ->update(['tipe' => 'broadcast']);
        }

        // 2. Buat tabel berita
        if (!Schema::hasTable('berita')) {
            Schema::create('berita', function (Blueprint $table) {
                $table->id();
                $table->string('judul', 255);
                $table->string('slug', 255)->unique();
                $table->string('kategori', 100)->default('Umum')->index();
                $table->text('ringkasan')->nullable();
                $table->longText('isi');
                $table->string('gambar', 255)->nullable();
                $table->string('penulis_nama', 100)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('views')->default(0);
                $table->timestamp('published_at')->nullable()->index();
                $table->timestamps();
            });

            // Seed beberapa berita awal agar halaman langsung terisi konten menarik
            $now = now();
            DB::table('berita')->insert([
                [
                    'judul'        => 'Transformasi Digital Ekosistem Sekolah 2026 Bersama SAE',
                    'slug'         => 'transformasi-digital-ekosistem-sekolah-2026-bersama-sae',
                    'kategori'     => 'Akademik',
                    'ringkasan'    => 'Penerapan sistem aplikasi digital terpadu untuk efisiensi presensi cerdas, pemantauan kegiatan belajar, serta layanan administrasi transparan bagi siswa dan guru.',
                    'isi'          => "Era digital menuntut institusi pendidikan bergerak cepat, akurat, dan adaptif. Melalui platform Sistem Aplikasi Edukasi (SAE), sekolah mengintegrasikan seluruh lini layanan pendidikan mulai dari absensi cerdas berbasis RFID & biometrik, manajemen kurikulum, perpustakaan digital terintegrasi, hingga pelaporan berkas secara realtime.\n\nKepala Sekolah menyampaikan bahwa inisiatif ini bukan sekadar modernisasi sarana, melainkan komitmen dalam mewujudkan budaya disiplin, transparansi data, dan kemudahan akses informasi bagi orang tua siswa secara berkala.",
                    'gambar'       => null,
                    'penulis_nama' => 'Humas Sekolah',
                    'is_active'    => true,
                    'views'        => 142,
                    'published_at' => $now,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
                [
                    'judul'        => 'Prestasi Gemilang Tim Riset Siswa Meraih Medali Emas Tingkat Nasional',
                    'slug'         => 'prestasi-gemilang-tim-riset-siswa-meraih-medali-emas-tingkat-nasional',
                    'kategori'     => 'Prestasi',
                    'ringkasan'    => 'Karya inovasi teknologi lingkungan karya peserta didik berhasil memikat dewan juri dan menyingkirkan ratusan peserta dari berbagai provinsi.',
                    'isi'          => "Kabar membanggakan kembali dipersembahkan oleh peserta didik dalam ajang Olimpiade Penelitian Siswa Nasional. Proyek inovasi penyaringan air mandiri ramah lingkungan karya tim riset berhasil mengantongi medali emas.\n\nPihak sekolah menyampaikan apresiasi setinggi-tingginya kepada para pembimbing dan peserta didik atas dedikasi luar biasa yang telah mengharumkan almamater.",
                    'gambar'       => null,
                    'penulis_nama' => 'Redaksi Sekolah',
                    'is_active'    => true,
                    'views'        => 98,
                    'published_at' => $now->copy()->subDays(2),
                    'created_at'   => $now->copy()->subDays(2),
                    'updated_at'   => $now->copy()->subDays(2),
                ],
                [
                    'judul'        => 'Workshop Penguatan Karakter & Literasi Digital Warga Satuan Pendidikan',
                    'slug'         => 'workshop-penguatan-karakter-literasi-digital-warga-satuan-pendidikan',
                    'kategori'     => 'Kegiatan',
                    'ringkasan'    => 'Kegiatan edukatif intensif menanamkan etika berinternet sehat, keamanan data pribadi, serta penguatan profil pelajar.',
                    'isi'          => "Menyikapi perkembangan teknologi yang pesat, sekolah menggelar Workshop Penguatan Karakter dan Literasi Digital bagi seluruh tenaga pendidik dan perwakilan OSIS.\n\nAcara yang berlangsung selama dua hari ini menghadirkan narasumber pakar siber dan pendidikan karakter guna membekali generasi muda menghadapi tantangan abad ke-21.",
                    'gambar'       => null,
                    'penulis_nama' => 'Kesiswaan',
                    'is_active'    => true,
                    'views'        => 65,
                    'published_at' => $now->copy()->subDays(4),
                    'created_at'   => $now->copy()->subDays(4),
                    'updated_at'   => $now->copy()->subDays(4),
                ],
                [
                    'judul'        => 'Sosialisasi Program Asesmen dan Persiapan Ujian Akhir Semester',
                    'slug'         => 'sosialisasi-program-asesmen-dan-persiapan-ujian-akhir-semester',
                    'kategori'     => 'Pengumuman',
                    'ringkasan'    => 'Petunjuk teknis dan jadwal pelaksanaan asesmen berbasis komputer bagi seluruh tingkatan kelas.',
                    'isi'          => "Bidang Kurikulum mengumumkan jadwal pelaksanaan asesmen semester mendatang. Seluruh peserta didik diharapkan memeriksa akun portal dan mempersiapkan perangkat pendukung.\n\nBagi peserta didik yang memerlukan bantuan teknis dapat menghubungi tim laboratorium komputer sekolah pada jam operasional.",
                    'gambar'       => null,
                    'penulis_nama' => 'Kurikulum',
                    'is_active'    => true,
                    'views'        => 115,
                    'published_at' => $now->copy()->subDays(6),
                    'created_at'   => $now->copy()->subDays(6),
                    'updated_at'   => $now->copy()->subDays(6),
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('berita');

        if (Schema::hasTable('pengumuman') && Schema::hasColumn('pengumuman', 'tipe')) {
            Schema::table('pengumuman', function (Blueprint $table) {
                $table->dropColumn('tipe');
            });
        }
    }
};
