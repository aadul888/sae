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
        if (Schema::hasTable('sarpras_ruang')) {
            $count = DB::table('sarpras_ruang')->count();
            if ($count === 0) {
                // Ambil 1-2 GTK pertama jika ada untuk contoh penanggung jawab
                $firstGtk = DB::table('gtk')->first();
                $pjPtkId = $firstGtk ? $firstGtk->ptk_id : null;

                $defaultRuang = [
                    [
                        'kode_ruang' => 'LAB-KOMP-1',
                        'nama_ruang' => 'Lab Komputer 1',
                        'gedung' => 'Gedung B (TIK)',
                        'lantai' => '2',
                        'penanggung_jawab_ptk_id' => $pjPtkId,
                        'kondisi' => 'baik',
                        'keterangan' => 'Laboratorium Komputer Praktik Pemrograman & Jaringan',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'kode_ruang' => 'LAB-KOMP-2',
                        'nama_ruang' => 'Lab Komputer 2',
                        'gedung' => 'Gedung B (TIK)',
                        'lantai' => '2',
                        'penanggung_jawab_ptk_id' => $pjPtkId,
                        'kondisi' => 'baik',
                        'keterangan' => 'Laboratorium Komputer CBT, Asesmen & Desain Grafis',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'kode_ruang' => 'LAB-IPA-KIM',
                        'nama_ruang' => 'Lab IPA / Kimia',
                        'gedung' => 'Gedung Sains Terpadu',
                        'lantai' => '1',
                        'penanggung_jawab_ptk_id' => $pjPtkId,
                        'kondisi' => 'baik',
                        'keterangan' => 'Laboratorium IPA Kimia dengan lemari asam dan meja basah',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'kode_ruang' => 'LAB-FIS-BIO',
                        'nama_ruang' => 'Lab Fisika / Biologi',
                        'gedung' => 'Gedung Sains Terpadu',
                        'lantai' => '2',
                        'penanggung_jawab_ptk_id' => $pjPtkId,
                        'kondisi' => 'baik',
                        'keterangan' => 'Laboratorium praktikum Biologi dan instrumen Fisika terapan',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'kode_ruang' => 'LAB-BAHASA',
                        'nama_ruang' => 'Lab Bahasa',
                        'gedung' => 'Gedung A (Utama)',
                        'lantai' => '2',
                        'penanggung_jawab_ptk_id' => $pjPtkId,
                        'kondisi' => 'baik',
                        'keterangan' => 'Laboratorium Bahasa Multimedia dengan master console audio',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'kode_ruang' => 'BENG-OTOMOTIF',
                        'nama_ruang' => 'Bengkel Praktik Otomotif (TKR)',
                        'gedung' => 'Gedung Praktik Industri',
                        'lantai' => '1',
                        'penanggung_jawab_ptk_id' => $pjPtkId,
                        'kondisi' => 'baik',
                        'keterangan' => 'Area praktik servis mesin kendaraan ringan, car lift & balancing',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'kode_ruang' => 'BENG-MESIN',
                        'nama_ruang' => 'Bengkel Pemesinan & Fabrikasi',
                        'gedung' => 'Gedung Praktik Industri',
                        'lantai' => '1',
                        'penanggung_jawab_ptk_id' => $pjPtkId,
                        'kondisi' => 'baik',
                        'keterangan' => 'Bengkel mesin bubut, milling, dan area las listrik',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'kode_ruang' => 'STUDIO-DKV',
                        'nama_ruang' => 'Studio Multimedia & DKV',
                        'gedung' => 'Gedung Kreatif',
                        'lantai' => '1',
                        'penanggung_jawab_ptk_id' => $pjPtkId,
                        'kondisi' => 'baik',
                        'keterangan' => 'Studio fotografi, videografi, podcast, dan editing audio-visual',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'kode_ruang' => 'AULA-PUSAT',
                        'nama_ruang' => 'Aula Utama & Gedung Serbaguna',
                        'gedung' => 'Gedung Pusat',
                        'lantai' => '1',
                        'penanggung_jawab_ptk_id' => $pjPtkId,
                        'kondisi' => 'baik',
                        'keterangan' => 'Aula serbaguna pertemuan, ujian skala besar, dan upacara indoor',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'kode_ruang' => 'PERPUS-PUSAT',
                        'nama_ruang' => 'Perpustakaan Utama',
                        'gedung' => 'Gedung Literasi',
                        'lantai' => '1',
                        'penanggung_jawab_ptk_id' => $pjPtkId,
                        'kondisi' => 'baik',
                        'keterangan' => 'Pusat sumber belajar, ruang baca digital, dan sirkulasi buku',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'kode_ruang' => 'R-RAPAT-GURU',
                        'nama_ruang' => 'Ruang Rapat & Guru',
                        'gedung' => 'Gedung A (Utama)',
                        'lantai' => '1',
                        'penanggung_jawab_ptk_id' => $pjPtkId,
                        'kondisi' => 'baik',
                        'keterangan' => 'Ruang kerja dewan guru dan koordinasi dinas',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ];

                DB::table('sarpras_ruang')->insert($defaultRuang);

                // Tambahkan contoh aset terkait di sarpras_aset jika tabel kosong
                if (Schema::hasTable('sarpras_aset') && DB::table('sarpras_aset')->count() === 0) {
                    $labKompId = DB::table('sarpras_ruang')->where('kode_ruang', 'LAB-KOMP-1')->value('id');
                    $labIpaId = DB::table('sarpras_ruang')->where('kode_ruang', 'LAB-IPA-KIM')->value('id');
                    $bengkelOtoId = DB::table('sarpras_ruang')->where('kode_ruang', 'BENG-OTOMOTIF')->value('id');

                    $defaultAset = [
                        [
                            'kode_aset' => 'AST-KOMP-001',
                            'nama_barang' => 'PC All-in-One Core i5 RAM 16GB',
                            'kategori' => 'Elektronik',
                            'merk_tipe' => 'Lenovo V50a-24IMB',
                            'no_seri_pabrik' => 'SN-LNV-2026-001',
                            'tahun_perolehan' => 2024,
                            'sumber_dana' => 'BOS',
                            'harga_perolehan' => 12500000.00,
                            'kondisi' => 'baik',
                            'ruang_id' => $labKompId,
                            'jumlah' => 36,
                            'satuan' => 'unit',
                            'status_ketersediaan' => 'tersedia',
                            'foto' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        [
                            'kode_aset' => 'AST-PROJ-001',
                            'nama_barang' => 'Proyektor LCD Epson EB-X500',
                            'kategori' => 'Elektronik',
                            'merk_tipe' => 'Epson 3600 Lumens',
                            'no_seri_pabrik' => 'SN-EPS-2026-042',
                            'tahun_perolehan' => 2023,
                            'sumber_dana' => 'BOS',
                            'harga_perolehan' => 6800000.00,
                            'kondisi' => 'baik',
                            'ruang_id' => $labKompId,
                            'jumlah' => 2,
                            'satuan' => 'unit',
                            'status_ketersediaan' => 'tersedia',
                            'foto' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        [
                            'kode_aset' => 'AST-MIKRO-001',
                            'nama_barang' => 'Mikroskop Binokuler Pembesaran 1600x',
                            'kategori' => 'Alat Peraga',
                            'merk_tipe' => 'Olympus CX23',
                            'no_seri_pabrik' => 'SN-OLY-2025-018',
                            'tahun_perolehan' => 2023,
                            'sumber_dana' => 'DAK',
                            'harga_perolehan' => 14200000.00,
                            'kondisi' => 'baik',
                            'ruang_id' => $labIpaId,
                            'jumlah' => 12,
                            'satuan' => 'unit',
                            'status_ketersediaan' => 'tersedia',
                            'foto' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        [
                            'kode_aset' => 'AST-TRAIN-001',
                            'nama_barang' => 'Engine Trainer EFI Toyota Avanza 1.3',
                            'kategori' => 'Mesin',
                            'merk_tipe' => 'Toyota K3-VE Stand Engine',
                            'no_seri_pabrik' => 'SN-ENG-2024-003',
                            'tahun_perolehan' => 2022,
                            'sumber_dana' => 'DAK',
                            'harga_perolehan' => 38000000.00,
                            'kondisi' => 'baik',
                            'ruang_id' => $bengkelOtoId,
                            'jumlah' => 2,
                            'satuan' => 'unit',
                            'status_ketersediaan' => 'tersedia',
                            'foto' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    ];

                    DB::table('sarpras_aset')->insert($defaultAset);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep user data safe
    }
};
