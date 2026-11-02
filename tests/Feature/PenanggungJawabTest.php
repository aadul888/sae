<?php

namespace Tests\Feature;

use App\Support\PenanggungJawab;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PJ otomatis dari jurusan (Task 3): baris ber-jurusan → PJ = Laboran jurusan
 * ybs; baris umum → PJ NULL (tanggung jawab bersama).
 */
class PenanggungJawabTest extends TestCase
{
    private array $createdAset = [];

    protected function tearDown(): void
    {
        if (!empty($this->createdAset)) {
            DB::table('sarpras_aset')->whereIn('id', $this->createdAset)->delete();
        }
        parent::tearDown();
    }

    private function adminSession(): array
    {
        $admin = DB::table('pengguna')->where('peran_id_str', 'Administrator')->first();

        return [
            'id'           => $admin->pengguna_id,
            'pengguna_id'  => $admin->pengguna_id,
            'name'         => $admin->nama,
            'nama'         => $admin->nama,
            'username'     => $admin->username,
            'role'         => 'admin',
            'peran_id_str' => $admin->peran_id_str,
        ];
    }

    /** Resolusi jurusan induk ke ptk_id Laboran yang ditugaskan. */
    public function test_untuk_jurusan_mengembalikan_ptk_laboran(): void
    {
        $row = DB::table('ptk_tugas_tambahan as p')
            ->join('ref_tugas_tambahan as r', 'p.tugas_tambahan_id', '=', 'r.id')
            ->where('r.kode', 'LABORAN')
            ->where('p.is_active', true)
            ->whereNotNull('p.jurusan_id')
            ->first(['p.ptk_id', 'p.jurusan_id']);

        if (!$row) {
            $this->markTestSkipped('Tidak ada penugasan LABORAN ber-jurusan di DB.');
        }

        $jurusan = trim(explode(',', $row->jurusan_id)[0]);

        $this->assertSame(
            $row->ptk_id,
            PenanggungJawab::untukJurusan($jurusan),
            'PJ jurusan harus ptk_id Laboran ybs.'
        );
    }

    public function test_jurusan_kosong_tak_punya_pj(): void
    {
        $this->assertNull(PenanggungJawab::untukJurusan(null));
        $this->assertNull(PenanggungJawab::untukJurusan(''));
    }

    /** Store aset ber-jurusan mengisi penanggung_jawab_ptk_id otomatis. */
    public function test_aset_store_mengisi_pj_dari_jurusan(): void
    {
        $row = DB::table('ptk_tugas_tambahan as p')
            ->join('ref_tugas_tambahan as r', 'p.tugas_tambahan_id', '=', 'r.id')
            ->where('r.kode', 'LABORAN')
            ->where('p.is_active', true)
            ->whereNotNull('p.jurusan_id')
            ->first(['p.ptk_id', 'p.jurusan_id']);

        if (!$row) {
            $this->markTestSkipped('Tidak ada penugasan LABORAN ber-jurusan di DB.');
        }

        $jurusan = trim(explode(',', $row->jurusan_id)[0]);

        $res = $this->withSession(['user' => $this->adminSession()])
            ->postJson(route('dashboard.sarpras.aset.store'), [
                'nama_barang' => 'Aset Uji PJ',
                'kategori'    => 'Elektronik',
                'kondisi'     => 'baik',
                'sumber_dana' => 'BOS',
                'satuan'      => 'unit',
                'jumlah'      => 1,
                'jurusan_id'  => $jurusan,
            ]);

        $res->assertStatus(200)->assertJson(['status' => 'success']);

        $aset = DB::table('sarpras_aset')->where('nama_barang', 'Aset Uji PJ')->first();
        $this->assertNotNull($aset);
        $this->createdAset[] = $aset->id;

        $this->assertSame(
            $row->ptk_id,
            $aset->penanggung_jawab_ptk_id,
            'Store aset ber-jurusan harus mengisi PJ = Laboran jurusan ybs.'
        );
    }

    /** Store aset tanpa jurusan → PJ NULL (milik bersama). */
    public function test_aset_store_tanpa_jurusan_pj_null(): void
    {
        $res = $this->withSession(['user' => $this->adminSession()])
            ->postJson(route('dashboard.sarpras.aset.store'), [
                'nama_barang' => 'Aset Uji Umum',
                'kategori'    => 'Elektronik',
                'kondisi'     => 'baik',
                'sumber_dana' => 'BOS',
                'satuan'      => 'unit',
                'jumlah'      => 1,
            ]);

        $res->assertStatus(200)->assertJson(['status' => 'success']);

        $aset = DB::table('sarpras_aset')->where('nama_barang', 'Aset Uji Umum')->first();
        $this->assertNotNull($aset);
        $this->createdAset[] = $aset->id;

        $this->assertNull($aset->penanggung_jawab_ptk_id);
    }
}
