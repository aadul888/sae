<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rincian alat pada Berita Acara Kerusakan.
 *
 * Satu berita acara kini dapat melaporkan LEBIH DARI SATU alat. Rincian tiap
 * alat disimpan di sini, sedangkan kolom ringkas pada `laboran_berita_acara_kerusakan`
 * (item_id, nama_alat, kode_alat, jumlah_rusak, satuan) tetap diisi alat pertama
 * agar modul lain (laporan, kebutuhan, arsip) tidak perlu diubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('laboran_berita_acara_kerusakan_item')) {
            Schema::create('laboran_berita_acara_kerusakan_item', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('kerusakan_id')->index();
                $table->unsignedBigInteger('item_id')->nullable()->index();

                $table->string('nama_alat');
                $table->string('kode_alat', 50)->nullable();
                $table->string('ruang_lab_nama', 100)->nullable();
                $table->decimal('jumlah_rusak', 12, 2)->default(1);
                $table->string('satuan', 30)->default('unit');
                $table->string('tingkat_kerusakan', 30)->default('ringan');
                $table->text('keterangan')->nullable();
                $table->unsignedSmallInteger('urutan')->default(0);

                $table->timestamps();
            });
        }

        $this->backfill();
    }

    public function down(): void
    {
        Schema::dropIfExists('laboran_berita_acara_kerusakan_item');
    }

    /** Salin berita acara lama (satu alat) menjadi satu baris rincian. */
    private function backfill(): void
    {
        if (!Schema::hasTable('laboran_berita_acara_kerusakan')) {
            return;
        }

        DB::table('laboran_berita_acara_kerusakan')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('laboran_berita_acara_kerusakan_item as i')
                    ->whereColumn('i.kerusakan_id', 'laboran_berita_acara_kerusakan.id');
            })
            ->orderBy('id')
            ->chunk(200, function ($rows) {
                $now = now();
                $insert = [];
                foreach ($rows as $r) {
                    if (empty($r->nama_alat)) {
                        continue;
                    }
                    $insert[] = [
                        'kerusakan_id'      => $r->id,
                        'item_id'           => $r->item_id,
                        'nama_alat'         => $r->nama_alat,
                        'kode_alat'         => $r->kode_alat,
                        'ruang_lab_nama'    => $r->ruang_lab_nama,
                        'jumlah_rusak'      => $r->jumlah_rusak ?: 1,
                        'satuan'            => $r->satuan ?: 'unit',
                        'tingkat_kerusakan' => $r->tingkat_kerusakan ?: 'ringan',
                        'keterangan'        => null,
                        'urutan'            => 1,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];
                }
                if ($insert) {
                    DB::table('laboran_berita_acara_kerusakan_item')->insert($insert);
                }
            });
    }
};
