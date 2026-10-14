<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kartu_pelajar_request')) {
            return;
        }

        Schema::table('kartu_pelajar_request', function (Blueprint $table) {
            if (!Schema::hasColumn('kartu_pelajar_request', 'request_date')) {
                $table->date('request_date')->nullable()->after('status')->index();
            }
            if (!Schema::hasColumn('kartu_pelajar_request', 'metode_pembayaran')) {
                $table->string('metode_pembayaran', 30)->nullable()->after('request_date');
            }
        });

        if (Schema::hasColumn('kartu_pelajar_request', 'request_date')) {
            DB::table('kartu_pelajar_request')
                ->whereNull('request_date')
                ->update(['request_date' => DB::raw('DATE(COALESCE(created_at, NOW()))')]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('kartu_pelajar_request')) {
            return;
        }

        Schema::table('kartu_pelajar_request', function (Blueprint $table) {
            if (Schema::hasColumn('kartu_pelajar_request', 'metode_pembayaran')) {
                $table->dropColumn('metode_pembayaran');
            }
            if (Schema::hasColumn('kartu_pelajar_request', 'request_date')) {
                $table->dropColumn('request_date');
            }
        });
    }
};
