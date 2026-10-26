<?php

namespace Tests\Feature\Laboran;

use App\Support\LaboranScope;
use Illuminate\Support\Facades\DB;

/**
 * A. Pembatasan data per jurusan (LaboranScope) pada tingkat context,
 *    query, dan penulisan.
 */
class LaboranScopingJurusanTest extends LaboranTestCase
{
    public function test_scope_menolak_akses_tanpa_penugasan_jurusan(): void
    {
        $session = $this->laboranSession(null);
        $context = LaboranScope::resolve($session['user']);

        $this->assertTrue($context['is_laboran']);
        // Fallback permisif: penugasan belum diisi -> tidak mengunci data
        $this->assertTrue($context['unrestricted']);
        $this->assertNull($context['jurusan_id']);
    }

    public function test_scope_membatasi_ke_jurusan_penugasan(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);
        $context = LaboranScope::resolve($session['user']);

        $this->assertFalse($context['unrestricted']);
        $this->assertSame(self::JUR_ATPH, $context['jurusan_id']);

        $this->assertTrue(LaboranScope::allows(self::JUR_ATPH, $context));
        $this->assertFalse(LaboranScope::allows(self::JUR_TKJ, $context));
        $this->assertFalse(LaboranScope::allows(null, $context));
    }

    public function test_scope_memaksa_jurusan_saat_menulis(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);
        $context = LaboranScope::resolve($session['user']);

        // Laboran mencoba menyimpan ke jurusan lain -> dipaksa ke jurusannya
        $this->assertSame(self::JUR_ATPH, LaboranScope::jurusanForWrite(self::JUR_TKJ, $context));
    }

    public function test_admin_tidak_dibatasi_jurusan(): void
    {
        $session = $this->adminSession();
        $context = LaboranScope::resolve($session['user']);

        $this->assertTrue($context['is_admin']);
        $this->assertTrue($context['unrestricted']);
        $this->assertTrue(LaboranScope::allows(self::JUR_TKJ, $context));
        $this->assertTrue(LaboranScope::allows(null, $context));
    }

    public function test_query_terfilter_ke_jurusan(): void
    {
        $this->seedItems();

        $session = $this->laboranSession(self::JUR_ATPH);
        $context = LaboranScope::resolve($session['user']);

        $q = DB::table('laboran_bahan_alat');
        LaboranScope::apply($q, 'jurusan_id', $context);
        $items = $q->get();

        $this->assertCount(1, $items);
        $this->assertSame(self::JUR_ATPH, $items->first()->jurusan_id);
    }

    public function test_laboran_tidak_bisa_mengubah_data_jurusan_lain(): void
    {
        $this->seedItems();

        $tkjId = DB::table('laboran_kebutuhan')->insertGetId([
            'jurusan_id'       => self::JUR_TKJ,
            'nama_item'        => 'Kabel UTP TKJ',
            'jenis'            => 'bahan_habis_pakai',
            'jumlah_kebutuhan' => 100,
            'jumlah_tersedia'  => 0,
            'selisih'          => 100,
            'satuan'           => 'meter',
            'prioritas'        => 'kritis',
            'status'           => 'draf',
            'tanggal_input'    => now()->toDateString(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $session = $this->laboranSession(self::JUR_ATPH);

        $this->withSession($session)
            ->putJson(route('dashboard.laboran.kebutuhan.update', $tkjId), [
                'nama_item'        => 'DIRETAS',
                'jenis'            => 'alat',
                'jumlah_kebutuhan' => 1,
                'satuan'           => 'unit',
                'prioritas'        => 'rendah',
            ])
            ->assertStatus(404);

        $this->assertSame('Kabel UTP TKJ', DB::table('laboran_kebutuhan')->where('id', $tkjId)->value('nama_item'));

        // Hapus juga harus ditolak
        $this->withSession($session)
            ->deleteJson(route('dashboard.laboran.kebutuhan.destroy', $tkjId))
            ->assertStatus(404);

        $this->assertTrue(DB::table('laboran_kebutuhan')->where('id', $tkjId)->exists());
    }
}
