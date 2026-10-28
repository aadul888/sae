<?php

namespace Tests\Feature\Laboran;

use Illuminate\Support\Facades\DB;

/**
 * G. Berita Acara Kerusakan Alat.
 */
class LaboranKerusakanTest extends LaboranTestCase
{
    public function test_berita_acara_kerusakan_menyesuaikan_kondisi_inventaris(): void
    {
        [$itemId] = $this->seedItems();

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.kerusakan.store'), [
                'item_id'           => $itemId,
                'nama_alat'         => 'Cangkul Uji',
                'tanggal_kejadian'  => now()->toDateString(),
                'tingkat_kerusakan' => 'berat',
                'jumlah_rusak'      => 1,
                'satuan'            => 'unit',
                'kronologi'         => 'Terjatuh saat praktik',
                'status'            => 'diajukan',
            ])
            ->assertStatus(200);

        $ba = DB::table('laboran_berita_acara_kerusakan')->where('item_id', $itemId)->first();
        $this->assertNotNull($ba);
        $this->assertStringStartsWith('BAK/', $ba->nomor_ba);

        // Kerusakan berat -> inventaris menjadi rusak_berat
        $this->assertSame('rusak_berat', DB::table('laboran_bahan_alat')->where('id', $itemId)->value('kondisi'));
    }

    public function test_cetak_berita_acara_menghasilkan_dokumen(): void
    {
        [$itemId] = $this->seedItems();

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.kerusakan.store'), [
                'item_id'           => $itemId,
                'nama_alat'         => 'Cangkul Uji',
                'tanggal_kejadian'  => now()->toDateString(),
                'tingkat_kerusakan' => 'ringan',
            ]);

        $ba = DB::table('laboran_berita_acara_kerusakan')->latest('id')->first();

        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.kerusakan.cetak', $ba->id));

        $res->assertStatus(200);
        $res->assertSee('BERITA ACARA KERUSAKAN ALAT', false);
        $res->assertSee($ba->nomor_ba, false);
    }

    /**
     * Berita acara dapat melaporkan LEBIH DARI SATU alat; dokumen cetak
     * memuat halaman 1 (berita acara) dan halaman 2 (lampiran daftar alat).
     */
    public function test_banyak_alat_per_berita_acara_dan_lampiran_halaman_dua(): void
    {
        [$atphId, $tkjId] = $this->seedItems();

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.kerusakan.store'), [
                'tanggal_kejadian'  => now()->toDateString(),
                'tingkat_kerusakan' => 'berat',
                'status'            => 'diajukan',
                'alat'              => [
                    ['item_id' => $atphId, 'nama_alat' => 'Cangkul Uji', 'jumlah_rusak' => 2, 'satuan' => 'unit'],
                    ['item_id' => $tkjId, 'nama_alat' => 'Switch Cisco Uji', 'jumlah_rusak' => 1, 'satuan' => 'unit', 'tingkat_kerusakan' => 'ringan'],
                    ['nama_alat' => 'Alat Manual Uji', 'jumlah_rusak' => 3, 'satuan' => 'buah'],
                ],
            ])
            ->assertStatus(200);

        $ba = DB::table('laboran_berita_acara_kerusakan')->latest('id')->first();
        $this->assertNotNull($ba);
        // Kolom ringkas tetap memuat alat pertama agar modul lain tidak berubah.
        $this->assertSame($atphId, (int) $ba->item_id);
        $this->assertSame('Cangkul Uji', $ba->nama_alat);

        $rincian = DB::table('laboran_berita_acara_kerusakan_item')
            ->where('kerusakan_id', $ba->id)->orderBy('urutan')->get();
        $this->assertCount(3, $rincian);
        $this->assertSame('Alat Manual Uji', $rincian->last()->nama_alat);

        // Kedua alat inventaris terkena pemutakhiran kondisi.
        $this->assertSame('rusak_berat', DB::table('laboran_bahan_alat')->where('id', $atphId)->value('kondisi'));
        $this->assertSame('rusak_ringan', DB::table('laboran_bahan_alat')->where('id', $tkjId)->value('kondisi'));

        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.kerusakan.cetak', $ba->id));

        $res->assertStatus(200);
        $res->assertSee('Halaman 1 / 2', false);
        $res->assertSee('LAMPIRAN BERITA ACARA', false);
        $res->assertSee('Alat Manual Uji', false);
        $res->assertSee('Switch Cisco Uji', false);
    }

    /** Minimal satu alat wajib dilaporkan. */
    public function test_berita_acara_tanpa_alat_ditolak(): void
    {
        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.kerusakan.store'), [
                'tanggal_kejadian'  => now()->toDateString(),
                'tingkat_kerusakan' => 'ringan',
                'alat'              => [['nama_alat' => '']],
            ])
            ->assertStatus(422);
    }
}
