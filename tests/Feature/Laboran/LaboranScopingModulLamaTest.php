<?php

namespace Tests\Feature\Laboran;

use Illuminate\Support\Facades\DB;

/**
 * A2. Pembatasan jurusan pada modul lama: Inventaris, Jadwal, dan Laporan Lokasi.
 */
class LaboranScopingModulLamaTest extends LaboranTestCase
{
    public function test_inventaris_terfilter_per_jurusan(): void
    {
        [$atphId] = $this->seedItems();

        $session = $this->laboranSession(self::JUR_ATPH);
        $res = $this->withSession($session)->get(route('dashboard.laboran.inventaris.index'));

        $res->assertStatus(200);

        $list = $res->viewData('list');
        $this->assertCount(1, $list, 'Laboran ATPH hanya boleh melihat 1 item.');
        $this->assertSame($atphId, $list->first()->id);
    }

    public function test_laboran_tidak_bisa_mengubah_atau_menghapus_inventaris_jurusan_lain(): void
    {
        [, $tkjId] = $this->seedItems();
        $session = $this->laboranSession(self::JUR_ATPH);

        $this->withSession($session)
            ->putJson(route('dashboard.laboran.inventaris.update', $tkjId), [
                'ruang_lab_nama' => 'X',
                'kode_item'      => 'UJI-TKJ',
                'nama_item'      => 'DIRETAS',
                'jenis'          => 'alat',
                'stok_total'     => 1,
                'stok_tersedia'  => 1,
                'satuan'         => 'unit',
                'kondisi'        => 'baik',
            ])
            ->assertStatus(404);

        $this->assertNotSame('DIRETAS', DB::table('laboran_bahan_alat')->where('id', $tkjId)->value('nama_item'));

        $this->withSession($session)
            ->deleteJson(route('dashboard.laboran.inventaris.destroy', $tkjId))
            ->assertStatus(404);

        $this->assertTrue(DB::table('laboran_bahan_alat')->where('id', $tkjId)->exists());
    }

    public function test_simpan_inventaris_memaksa_jurusan_laboran(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);

        $this->withSession($session)
            ->postJson(route('dashboard.laboran.inventaris.store'), [
                'ruang_lab_nama' => 'Lahan Praktik ATPH',
                'kode_item'      => 'UJI-MAPEL-' . uniqid(),
                'nama_item'      => 'Item Uji Paksa',
                'jenis'          => 'alat',
                'stok_total'     => 2,
                'satuan'         => 'unit',
                'kondisi'        => 'baik',
                'jurusan_id'     => self::JUR_TKJ,
            ])
            ->assertStatus(200);

        $row = DB::table('laboran_bahan_alat')->where('nama_item', 'Item Uji Paksa')->first();
        $this->assertNotNull($row);
        $this->assertSame(self::JUR_ATPH, $row->jurusan_id, 'Jurusan harus dipaksa ke milik laboran.');
    }

    public function test_jadwal_terfilter_per_jurusan(): void
    {
        $ptk = (string) DB::table('gtk')->value('ptk_id');
        $rombel = DB::table('rombongan_belajar')->where('jenis_rombel', '1')->value('rombongan_belajar_id');

        // Prefix unik per test agar data antar-test tidak saling bertabrakan.
        $tag = 'UJI-' . uniqid() . '-';

        DB::table('laboran_jadwal_penggunaan')->insert([
            [
                'jurusan_id'     => self::JUR_ATPH,
                'ruang_lab_nama' => 'Lahan Praktik ATPH',
                'ptk_id'         => $ptk,
                'rombel_id'      => $rombel,
                'mata_pelajaran' => $tag . 'ATPH',
                'tanggal'        => now()->toDateString(),
                'jam_mulai'      => '07:00:00',
                'jam_selesai'    => '08:30:00',
                'status'         => 'dijadwalkan',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'jurusan_id'     => self::JUR_TKJ,
                'ruang_lab_nama' => 'Lab Komputer 1',
                'ptk_id'         => $ptk,
                'rombel_id'      => $rombel,
                'mata_pelajaran' => $tag . 'TKJ',
                'tanggal'        => now()->toDateString(),
                'jam_mulai'      => '10:00:00',
                'jam_selesai'    => '11:30:00',
                'status'         => 'dijadwalkan',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);

        $session = $this->laboranSession(self::JUR_ATPH);
        $res = $this->withSession($session)->get(route('dashboard.laboran.jadwal.index'));

        $res->assertStatus(200);

        $list = $res->viewData('list');
        $this->assertCount(1, $list);
        $this->assertSame($tag . 'ATPH', $list->first()->mata_pelajaran);
    }

    public function test_laboran_tidak_bisa_mengubah_jadwal_jurusan_lain(): void
    {
        $ptk = (string) DB::table('gtk')->value('ptk_id');

        $jadwalId = DB::table('laboran_jadwal_penggunaan')->insertGetId([
            'jurusan_id'     => self::JUR_TKJ,
            'ruang_lab_nama' => 'Lab Komputer 1',
            'ptk_id'         => $ptk,
            'mata_pelajaran' => 'UJI-LARANG-TKJ',
            'tanggal'        => now()->toDateString(),
            'jam_mulai'      => '10:00:00',
            'jam_selesai'    => '11:30:00',
            'status'         => 'dijadwalkan',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $session = $this->laboranSession(self::JUR_ATPH);

        $this->withSession($session)
            ->putJson(route('dashboard.laboran.jadwal.update', $jadwalId), [
                'form_mode' => 'status',
                'status'    => 'selesai',
            ])
            ->assertStatus(404);

        $this->assertSame('dijadwalkan', DB::table('laboran_jadwal_penggunaan')->where('id', $jadwalId)->value('status'));

        $this->withSession($session)
            ->deleteJson(route('dashboard.laboran.jadwal.destroy', $jadwalId))
            ->assertStatus(404);

        $this->assertTrue(DB::table('laboran_jadwal_penggunaan')->where('id', $jadwalId)->exists());
    }

    public function test_laporan_lokasi_terfilter_per_jurusan(): void
    {
        $ptk = (string) DB::table('gtk')->value('ptk_id');

        DB::table('laboran_jadwal_penggunaan')->insert([
            [
                'jurusan_id'     => self::JUR_ATPH,
                'ruang_lab_nama' => 'Lahan Praktik ATPH',
                'ptk_id'         => $ptk,
                'mata_pelajaran' => 'UJI-LAPOR-ATPH',
                'tanggal'        => '2026-10-05',
                'jam_mulai'      => '07:00:00',
                'jam_selesai'    => '08:30:00',
                'status'         => 'selesai',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'jurusan_id'     => self::JUR_TKJ,
                'ruang_lab_nama' => 'Lab Komputer 1',
                'ptk_id'         => $ptk,
                'mata_pelajaran' => 'UJI-LAPOR-TKJ',
                'tanggal'        => '2026-10-06',
                'jam_mulai'      => '10:00:00',
                'jam_selesai'    => '11:30:00',
                'status'         => 'selesai',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);

        $session = $this->laboranSession(self::JUR_ATPH);

        $res = $this->withSession($session)
            ->get(route('dashboard.laboran.laporan.index', ['periode' => 'bulan', 'bulan' => 10, 'tahun' => 2026]));

        $res->assertStatus(200);
        $this->assertSame(1, $res->viewData('totalSesi'), 'Hanya sesi jurusan ATPH yang boleh dihitung.');
    }
}
