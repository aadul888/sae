<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jadwal Teknisi mengikuti PJ ruang di Sarpras > Ruang & Gedung.
 *
 * Sekali jalan:
 *  1. Buat/perbarui baris cermin Jadwal Area Tugas & Pemeliharaan Preventif
 *     untuk setiap ruang yang sudah punya PJ (`sarpras_ruang`).
 *  2. Hapus data lama "tanpa PJ" — baris yang tak terikat ruang ber-PJ dan
 *     tak punya petugas/PJ (mis. 1520 baris area tugas lama di server).
 *
 * Tidak bisa dibatalkan; deploy.sh sudah membuat backup database sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Cermin ruang ber-PJ → jadwal Teknisi.
        \App\Support\TeknisiRuangSync::syncArea();
        \App\Support\TeknisiRuangSync::syncPemeliharaan();

        // 2. Ruang yang punya PJ (untuk menandai baris lama tanpa PJ).
        $ruangPj = [];
        if (Schema::hasTable('sarpras_ruang')) {
            $ruangPj = DB::table('sarpras_ruang')
                ->whereNotNull('penanggung_jawab_ptk_id')
                ->where('penanggung_jawab_ptk_id', '<>', '')
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        if (Schema::hasTable('teknisi_area_tugas')) {
            $q = DB::table('teknisi_area_tugas')
                ->where(function ($w) {
                    $w->whereNull('petugas_ptk_id')->orWhere('petugas_ptk_id', '');
                });
            if (empty($ruangPj)) {
                $q->whereNull('ruang_id');
            } else {
                $q->where(function ($w) use ($ruangPj) {
                    $w->whereNull('ruang_id')->orWhereNotIn('ruang_id', $ruangPj);
                });
            }
            $q->delete();
        }

        if (Schema::hasTable('teknisi_pemeliharaan')) {
            $q = DB::table('teknisi_pemeliharaan')
                ->where(function ($w) {
                    $w->whereNull('penanggung_jawab')->orWhere('penanggung_jawab', '');
                });
            if (empty($ruangPj)) {
                $q->whereNull('ruang_id');
            } else {
                $q->where(function ($w) use ($ruangPj) {
                    $w->whereNull('ruang_id')->orWhereNotIn('ruang_id', $ruangPj);
                });
            }
            $q->delete();
        }
    }

    public function down(): void
    {
        // Data yang dihapus tidak dapat dipulihkan lewat rollback.
    }
};
