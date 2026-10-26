<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahkan lokasi praktik luar ruang (lahan/kebun & lapangan praktik) ke master
 * Sarpras, agar penjadwalan praktik lapangan pada modul Laboran langsung tersedia.
 * Bersifat idempoten: hanya menambah kode ruang yang belum ada.
 */
return new class extends Migration
{
    private array $lokasi = [
        [
            'kode_ruang' => 'LAHAN-ATPH',
            'nama_ruang' => 'Lahan Praktik ATPH',
            'gedung'     => 'Area Praktik Terbuka',
            'lantai'     => '1',
            'keterangan' => 'Lahan budidaya tanaman pangan & hortikultura untuk praktik siswa ATPH.',
        ],
        [
            'kode_ruang' => 'LAHAN-KHT',
            'nama_ruang' => 'Lahan Praktik Kehutanan',
            'gedung'     => 'Area Praktik Terbuka',
            'lantai'     => '1',
            'keterangan' => 'Lahan praktik pembibitan, penanaman, dan konservasi kehutanan.',
        ],
        [
            'kode_ruang' => 'KEBUN-SEKOLAH',
            'nama_ruang' => 'Kebun Sekolah',
            'gedung'     => 'Area Praktik Terbuka',
            'lantai'     => '1',
            'keterangan' => 'Kebun produktif sekolah untuk praktik lintas program keahlian.',
        ],
        [
            'kode_ruang' => 'GREENHOUSE-1',
            'nama_ruang' => 'Greenhouse / Screen House',
            'gedung'     => 'Area Praktik Terbuka',
            'lantai'     => '1',
            'keterangan' => 'Rumah kaca untuk praktik pembibitan dan pengendalian iklim mikro.',
        ],
        [
            'kode_ruang' => 'LAPANGAN-PRAKTIK',
            'nama_ruang' => 'Lapangan Praktik Outdoor',
            'gedung'     => 'Area Terbuka',
            'lantai'     => '1',
            'keterangan' => 'Lapangan terbuka untuk praktik olahraga, pengukuran lahan, dan kegiatan lapangan.',
        ],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('sarpras_ruang')) {
            return;
        }

        $now = now();

        foreach ($this->lokasi as $row) {
            $exists = DB::table('sarpras_ruang')->where('kode_ruang', $row['kode_ruang'])->exists();
            if ($exists) {
                continue;
            }

            DB::table('sarpras_ruang')->insert([
                'kode_ruang'               => $row['kode_ruang'],
                'nama_ruang'               => $row['nama_ruang'],
                'gedung'                   => $row['gedung'],
                'lantai'                   => $row['lantai'],
                'penanggung_jawab_ptk_id'  => null,
                'kondisi'                  => 'baik',
                'keterangan'               => $row['keterangan'],
                'created_at'               => $now,
                'updated_at'               => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('sarpras_ruang')) {
            return;
        }

        DB::table('sarpras_ruang')
            ->whereIn('kode_ruang', array_column($this->lokasi, 'kode_ruang'))
            ->delete();
    }
};
