<?php

namespace Tests\Feature\Laboran;

use Illuminate\Support\Facades\DB;

/**
 * O. Bahan Habis Pakai (BHP): modul terpisah dengan import Excel.
 */
class LaboranBhpTest extends LaboranTestCase
{
    public function test_halaman_bhp_hanya_memuat_bahan_habis_pakai(): void
    {
        $ptk = (string) DB::table('gtk')->value('ptk_id');

        DB::table('laboran_bahan_alat')->insert([
            'ruang_lab_nama' => 'Lab Kimia', 'kode_item' => 'BHP-UJI-1', 'nama_item' => 'Alkohol Uji',
            'jenis' => 'bahan_habis_pakai', 'stok_total' => 20, 'stok_tersedia' => 20,
            'satuan' => 'botol', 'kondisi' => 'baik', 'jurusan_id' => self::JUR_ATPH,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('laboran_bahan_alat')->insert([
            'ruang_lab_nama' => 'Lab Kimia', 'kode_item' => 'ALT-UJI-1', 'nama_item' => 'Mikroskop Uji',
            'jenis' => 'alat', 'stok_total' => 5, 'stok_tersedia' => 5,
            'satuan' => 'unit', 'kondisi' => 'baik', 'jurusan_id' => self::JUR_ATPH,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.bhp.index'));

        $res->assertStatus(200);
        $res->assertSee('Alkohol Uji', false);
        $res->assertDontSee('Mikroskop Uji', false);
    }

    public function test_simpan_bhp_tanpa_kode_membuat_kode_otomatis_bhp(): void
    {
        $res = $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.bhp.store'), [
                'ruang_lab_nama' => 'Lab Kimia',
                'nama_item'      => 'Aquades Uji',
                'stok_total'     => 10,
                'satuan'         => 'botol',
                'kondisi'        => 'baik',
            ])->assertStatus(200);

        $row = DB::table('laboran_bahan_alat')->where('nama_item', 'Aquades Uji')->first();
        $this->assertNotNull($row);
        $this->assertSame('bahan_habis_pakai', $row->jenis);
        $this->assertStringStartsWith('BHP-', $row->kode_item);
    }

    public function test_import_bhp_memaksa_jenis_bahan(): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'bhp') . '.csv';
        file_put_contents($temp, "kode_item,nama_item,jenis,stok_total,satuan,kondisi,ruang_lab_nama\n"
            . "BHP-IMP-1,Asam Sulfat Uji,alat,5,botol,baik,Lab Kimia\n");

        $this->withSession($this->adminSession())
            ->post(route('dashboard.laboran.bhp.import'), [
                'file' => new \Illuminate\Http\UploadedFile($temp, 'bhp.csv', 'text/csv', null, true),
            ])
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $row = DB::table('laboran_bahan_alat')->where('kode_item', 'BHP-IMP-1')->first();
        $this->assertNotNull($row);
        $this->assertSame('bahan_habis_pakai', $row->jenis, 'Import BHP harus memaksa jenis bahan habis pakai.');

        @unlink($temp);
    }

    public function test_hapus_massal_bhp_dengan_ceklist(): void
    {
        $ptk = (string) DB::table('gtk')->value('ptk_id');
        $a = DB::table('laboran_bahan_alat')->insertGetId([
            'ruang_lab_nama' => 'Lab Kimia', 'kode_item' => 'BHP-UJI-A', 'nama_item' => 'Bahan A',
            'jenis' => 'bahan_habis_pakai', 'stok_total' => 5, 'stok_tersedia' => 5,
            'satuan' => 'botol', 'kondisi' => 'baik', 'jurusan_id' => self::JUR_ATPH,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $b = DB::table('laboran_bahan_alat')->insertGetId([
            'ruang_lab_nama' => 'Lab Kimia', 'kode_item' => 'BHP-UJI-B', 'nama_item' => 'Bahan B',
            'jenis' => 'bahan_habis_pakai', 'stok_total' => 5, 'stok_tersedia' => 5,
            'satuan' => 'botol', 'kondisi' => 'baik', 'jurusan_id' => self::JUR_ATPH,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $res = $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.bhp.bulk-delete'), ['ids' => [$a, $b]])
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertSame(2, (int) $res->json('deleted'));
        $this->assertNull(DB::table('laboran_bahan_alat')->where('id', $a)->first());
        $this->assertNull(DB::table('laboran_bahan_alat')->where('id', $b)->first());
    }

    public function test_template_bhp_dapat_diunduh(): void
    {
        $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.bhp.template'))
            ->assertStatus(200);
    }
}
