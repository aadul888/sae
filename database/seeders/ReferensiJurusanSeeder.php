<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ReferensiJurusan;

class ReferensiJurusanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('referensi_jurusan_data.json');
        if (!file_exists($jsonPath)) {
            return;
        }

        $items = json_decode(file_get_contents($jsonPath), true);
        if (!is_array($items) || empty($items)) {
            return;
        }

        $now = now();
        $payload = array_map(function ($item) use ($now) {
            return [
                'kode_bidang'      => $item['kode_bidang'],
                'nama_bidang'      => $item['nama_bidang'],
                'kode_program'     => $item['kode_program'],
                'nama_program'     => $item['nama_program'],
                'kode_konsentrasi' => $item['kode_konsentrasi'],
                'nama_konsentrasi' => $item['nama_konsentrasi'],
                'durasi'           => $item['durasi'] ?? 3,
                'kurikulum'        => $item['kurikulum'] ?? 'Kurikulum Merdeka',
                'bidang_k13'       => $item['bidang_k13'] ?? null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }, $items);

        DB::table('referensi_jurusan')->upsert(
            $payload,
            ['kode_konsentrasi'],
            ['kode_bidang', 'nama_bidang', 'kode_program', 'nama_program', 'nama_konsentrasi', 'durasi', 'kurikulum', 'bidang_k13', 'updated_at']
        );
    }
}
