<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahan kolom peta/denah Sarpras (rilis editor + overlay).
 *
 *  - sarpras_peta_area.status_visual : 5 status tampilan resmi
 *      (baik | revit_2025 | rusak_2026 | siap_bangun | lahan_kosong)
 *  - sarpras_peta_area.map_coordinates : JSON geometri {x,y,w,h,rotate}
 *  - sarpras_peta.latar_opacity : 0-100 overlay citra latar
 *
 * Lihat .agents/docs/RENCANA_PETA_DENAH_SARPRAS.md
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sarpras_peta_area') && !Schema::hasColumn('sarpras_peta_area', 'status_visual')) {
            Schema::table('sarpras_peta_area', function (Blueprint $table) {
                $table->string('status_visual', 20)->nullable()->index()
                    ->after('highlight')
                    ->comment('baik | revit_2025 | rusak_2026 | siap_bangun | lahan_kosong');
                $table->json('map_coordinates')->nullable()->after('status_visual')
                    ->comment('Geometri editor {x,y,w,h,rotate}');
            });
        }

        if (Schema::hasTable('sarpras_peta') && !Schema::hasColumn('sarpras_peta', 'latar_opacity')) {
            Schema::table('sarpras_peta', function (Blueprint $table) {
                $table->unsignedTinyInteger('latar_opacity')->default(70)
                    ->after('latar_atribusi')->comment('0-100, transparansi overlay citra latar');
                $table->json('view_state')->nullable()->after('latar_opacity')
                    ->comment('Zoom/pan tersimpan {scale,x,y}');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sarpras_peta_area') && Schema::hasColumn('sarpras_peta_area', 'status_visual')) {
            Schema::table('sarpras_peta_area', function (Blueprint $table) {
                $table->dropColumn(['status_visual', 'map_coordinates']);
            });
        }

        if (Schema::hasTable('sarpras_peta') && Schema::hasColumn('sarpras_peta', 'latar_opacity')) {
            Schema::table('sarpras_peta', function (Blueprint $table) {
                $table->dropColumn(['latar_opacity', 'view_state']);
            });
        }
    }
};
