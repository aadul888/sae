<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penjadwalan guru pada modul Laboran kini mencakup:
 *  - Penggunaan lahan praktik / kebun / lapangan (tidak hanya ruang lab & bengkel).
 *  - Keterkaitan ke data `pembelajaran` (mata pelajaran otomatis dari kelas/rombel).
 *  - Topik / materi praktik bersifat OPSIONAL (boleh kosong).
 */
return new class extends Migration
{
    private const TABLE = 'laboran_jadwal_penggunaan';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            // Jenis lokasi praktik: lab, bengkel, lahan, lapangan, lainnya
            if (!Schema::hasColumn(self::TABLE, 'jenis_lokasi')) {
                $table->string('jenis_lokasi', 30)->default('lab')->after('ruang_lab_nama')
                    ->comment('lab, bengkel, lahan, lapangan, lainnya')
                    ->index();
            }

            // Relasi ke pembelajaran Dapodik (sumber mata pelajaran & guru pengampu)
            if (!Schema::hasColumn(self::TABLE, 'pembelajaran_id')) {
                $table->string('pembelajaran_id', 50)->nullable()->after('rombel_id')
                    ->comment('Relasi ke pembelajaran (mata pelajaran otomatis dari kelas)')
                    ->index();
            }

            if (!Schema::hasColumn(self::TABLE, 'mata_pelajaran_id')) {
                $table->string('mata_pelajaran_id', 50)->nullable()->after('pembelajaran_id');
            }
        });

        // Topik / materi praktik dijadikan opsional (boleh dikosongkan)
        $this->setTopikNullable(true);

        // Sinkronkan jenis_lokasi untuk baris lama berdasarkan nama ruang yang sudah tercatat
        $this->backfillJenisLokasi();
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        $this->setTopikNullable(false);

        Schema::table(self::TABLE, function (Blueprint $table) {
            if (Schema::hasColumn(self::TABLE, 'mata_pelajaran_id')) {
                $table->dropColumn('mata_pelajaran_id');
            }
        });

        Schema::table(self::TABLE, function (Blueprint $table) {
            if (Schema::hasColumn(self::TABLE, 'pembelajaran_id')) {
                $table->dropIndex(['pembelajaran_id']);
                $table->dropColumn('pembelajaran_id');
            }
        });

        Schema::table(self::TABLE, function (Blueprint $table) {
            if (Schema::hasColumn(self::TABLE, 'jenis_lokasi')) {
                $table->dropIndex(['jenis_lokasi']);
                $table->dropColumn('jenis_lokasi');
            }
        });
    }

    /**
     * Ubah nullability kolom topik_praktik tanpa memerlukan paket doctrine/dbal.
     */
    private function setTopikNullable(bool $nullable): void
    {
        $column = DB::selectOne(
            'SELECT IS_NULLABLE, COLUMN_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [self::TABLE, 'topik_praktik']
        );

        if (!$column) {
            return;
        }

        $isNullable = strtoupper((string) $column->IS_NULLABLE) === 'YES';

        if ($nullable && $isNullable) {
            return;
        }

        if (!$nullable) {
            // Sebelum mengembalikan NOT NULL, isi nilai kosong agar ALTER tidak gagal
            DB::table(self::TABLE)->whereNull('topik_praktik')->update(['topik_praktik' => '-']);

            if (!$isNullable) {
                return;
            }

            DB::statement('ALTER TABLE `' . self::TABLE . '` MODIFY `topik_praktik` VARCHAR(255) NOT NULL');
            return;
        }

        DB::statement('ALTER TABLE `' . self::TABLE . '` MODIFY `topik_praktik` VARCHAR(255) NULL DEFAULT NULL');
    }

    /**
     * Isi jenis_lokasi untuk data lama berdasarkan pola nama ruang.
     */
    private function backfillJenisLokasi(): void
    {
        if (!Schema::hasColumn(self::TABLE, 'jenis_lokasi')) {
            return;
        }

        $rows = DB::table(self::TABLE)
            ->where(function ($q) {
                $q->whereNull('jenis_lokasi')->orWhere('jenis_lokasi', 'lab');
            })
            ->select('id', 'ruang_lab_nama')
            ->get();

        foreach ($rows as $row) {
            $nama = strtoupper((string) $row->ruang_lab_nama);

            $jenis = 'lab';
            if (str_contains($nama, 'LAHAN') || str_contains($nama, 'KEBUN') || str_contains($nama, 'GREENHOUSE')
                || str_contains($nama, 'SAWAH') || str_contains($nama, 'PLOT') || str_contains($nama, 'TANAMAN')) {
                $jenis = 'lahan';
            } elseif (str_contains($nama, 'LAPANGAN') || str_contains($nama, 'AREA TERBUKA')) {
                $jenis = 'lapangan';
            } elseif (str_contains($nama, 'BENGKEL') || str_contains($nama, 'BENGK')) {
                $jenis = 'bengkel';
            }

            if ($jenis !== 'lab' || $row->jenis_lokasi === null) {
                DB::table(self::TABLE)->where('id', $row->id)->update(['jenis_lokasi' => $jenis]);
            }
        }
    }
};
