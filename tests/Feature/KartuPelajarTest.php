<?php

namespace Tests\Feature;

use App\Models\KartuPelajarPembayaran;
use App\Models\KartuPelajarPengaturan;
use Tests\TestCase;

class KartuPelajarTest extends TestCase
{
    public function test_kartu_pelajar_settings_singleton_and_defaults(): void
    {
        $settings = KartuPelajarPengaturan::getSettings();
        $this->assertNotNull($settings);
        $this->assertGreaterThan(0, $settings->biaya_standar);
        $this->assertEquals(1, $settings->kuota_ganti_kartu_full);
        $this->assertEquals(3, $settings->kuota_ganti_stiker);
    }

    public function test_whatsapp_message_builder_includes_warranty_and_settings(): void
    {
        $data = [
            'nama'              => 'Ahmad Fulan',
            'nisn'              => '0012345678',
            'rombel'            => 'X PPLG 1',
            'status_pembayaran' => 'lunas',
            'nominal'           => 25000,
            'metode_pembayaran' => 'tunai',
            'tanggal_bayar'     => '2026-10-14',
            'status_cetak'      => 'sudah_cetak',
            'klaim_ganti_kartu' => 0,
            'klaim_stiker'      => 1,
            'verify_url'        => 'http://localhost/v/0012345678',
        ];

        $sekolahInfo = [
            'nama' => 'SMK NEGERI 1 PAGELARAN',
        ];

        $msg = KartuPelajarPembayaran::buildWhatsAppMessage($data, $sekolahInfo);
        $this->assertStringContainsString('Ahmad Fulan', $msg);
        $this->assertStringContainsString('0012345678', $msg);
        $this->assertStringContainsString('Fasilitas Garansi Kartu Pelajar Resmi', $msg);
        $this->assertStringContainsString('1x Garansi Ganti Kartu Full', $msg);
        $this->assertStringContainsString('3x Garansi Cetak Stiker', $msg);
    }

    public function test_klaim_garansi_requires_sudah_cetak_and_reduces_quota(): void
    {
        $pd = \Illuminate\Support\Facades\DB::table('peserta_didik')->first();
        if (!$pd) {
            $this->markTestSkipped('Data peserta didik kosong.');
        }

        // Pastikan record pembayaran ada dan diset belum_cetak
        $record = KartuPelajarPembayaran::updateOrCreate(
            ['peserta_didik_id' => $pd->peserta_didik_id],
            [
                'nisn'              => $pd->nisn,
                'status_cetak'      => 'belum_cetak',
                'klaim_ganti_kartu' => 0,
                'klaim_stiker'      => 0,
            ]
        );

        // Simulasi sesi admin
        $adminSession = [
            'user' => [
                'id'       => 1,
                'nama'     => 'Administrator Test',
                'username' => 'admin',
                'role'     => 'admin',
            ],
        ];

        // 1. Klaim saat belum_cetak harus ditolak (garansi belum aktif)
        $res = $this->withSession($adminSession)->postJson(route('dashboard.kartu-pelajar.klaim-garansi'), [
            'peserta_didik_id' => $pd->peserta_didik_id,
            'jenis_klaim'      => 'ganti_kartu_full',
            'alasan'           => 'Kartu patah uji coba',
        ]);
        $res->assertStatus(422);

        // 2. Set sudah_cetak via bulk action
        $bulkRes = $this->withSession($adminSession)->postJson(route('dashboard.kartu-pelajar.bulk-action'), [
            'action' => 'set_sudah_cetak',
            'ids'    => [$pd->peserta_didik_id],
        ]);
        $bulkRes->assertOk();

        $record->refresh();
        $this->assertEquals('sudah_cetak', $record->status_cetak);

        // 3. Klaim saat sudah_cetak harus berhasil dan mengurangi kuota yang berlaku
        $klaimRes = $this->withSession($adminSession)->postJson(route('dashboard.kartu-pelajar.klaim-garansi'), [
            'peserta_didik_id' => $pd->peserta_didik_id,
            'jenis_klaim'      => 'ganti_kartu_full',
            'alasan'           => 'Kartu patah uji coba',
        ]);
        $klaimRes->assertOk();
        $this->assertEquals(1, $klaimRes->json('klaim_ganti_kartu'));

        $record->refresh();
        $this->assertEquals(1, $record->klaim_ganti_kartu);

        // 4. Klaim kedua untuk ganti_kartu_full harus ditolak karena kuota 1 kali habis
        $klaimRes2 = $this->withSession($adminSession)->postJson(route('dashboard.kartu-pelajar.klaim-garansi'), [
            'peserta_didik_id' => $pd->peserta_didik_id,
            'jenis_klaim'      => 'ganti_kartu_full',
            'alasan'           => 'Kartu patah lagi',
        ]);
        $klaimRes2->assertStatus(422);
    }

    public function test_catatan_keuangan_pengeluaran_and_cetak_laporan(): void
    {
        $adminSession = [
            'user' => [
                'id'       => 1,
                'nama'     => 'Administrator Test',
                'username' => 'admin',
                'role'     => 'admin',
            ],
        ];

        // 1. Simpan Pengeluaran Bahan Cetak
        $postRes = $this->withSession($adminSession)->post(route('dashboard.kartu-pelajar.store-pengeluaran'), [
            'judul'      => 'Beli 100 Kartu PVC RFID Blank',
            'kategori'   => 'bahan_pvc',
            'nominal'    => 250000,
            'tanggal'    => '2026-10-14',
            'keterangan' => 'Pembelian toko elektronik',
        ]);
        $postRes->assertRedirect(route('dashboard.kartu-pelajar.index', ['tab' => 'keuangan']));

        $item = \App\Models\KartuPelajarPengeluaran::where('judul', 'Beli 100 Kartu PVC RFID Blank')->first();
        $this->assertNotNull($item);
        $this->assertEquals(250000, $item->nominal);

        // 2. Akses Tab Keuangan
        $this->withoutExceptionHandling();
        $viewRes = $this->withSession($adminSession)->get(route('dashboard.kartu-pelajar.index', ['tab' => 'keuangan']));
        $viewRes->assertOk();
        $viewRes->assertSee('Catatan Belanja Bahan &amp; Alat', false);
        $viewRes->assertSee('Beli 100 Kartu PVC RFID Blank');

        // 3. Akses Halaman Cetak Laporan Keuangan
        $cetakRes = $this->withSession($adminSession)->get(route('dashboard.kartu-pelajar.cetak-keuangan'));
        $cetakRes->assertOk();
        $cetakRes->assertSee('LAPORAN KEUANGAN &amp; ARUS KAS KARTU PELAJAR', false);
        $cetakRes->assertSee('Beli 100 Kartu PVC RFID Blank');

        // 4. Hapus Pengeluaran
        $delRes = $this->withSession($adminSession)->delete(route('dashboard.kartu-pelajar.destroy-pengeluaran', $item->id));
        $delRes->assertRedirect(route('dashboard.kartu-pelajar.index', ['tab' => 'keuangan']));
        $this->assertNull(\App\Models\KartuPelajarPengeluaran::find($item->id));

        // 5. Akses Halaman Cetak Lembar Pengambilan / Serah Terima Kartu Per Tanggal Cetak
        $pengambilanRes = $this->withSession($adminSession)->get(route('dashboard.kartu-pelajar.cetak-pengambilan', [
            'tanggal_cetak' => '2026-10-14',
        ]));
        $pengambilanRes->assertOk();
        $pengambilanRes->assertSee('DAFTAR DISTRIBUSI &amp; SERAH TERIMA', false);
        $pengambilanRes->assertSee('Tanggal Cetak Selesai', false);

        // 6. Test Bulk Action dengan Modal Form (Penyesuaian Tanggal, Nominal, Status Cetak & Metode Bayar)
        $pd = \Illuminate\Support\Facades\DB::table('peserta_didik')->first();
        if ($pd) {
            $bulkCustomRes = $this->withSession($adminSession)->postJson(route('dashboard.kartu-pelajar.bulk-action'), [
                'action'                 => 'custom_bulk',
                'ids'                    => [$pd->peserta_didik_id],
                'bulk_status_pembayaran' => 'lunas',
                'bulk_nominal'           => 30000,
                'bulk_metode_pembayaran' => 'qris',
                'bulk_tanggal_bayar'     => '2026-10-14',
                'bulk_status_cetak'      => 'sudah_cetak',
                'bulk_tanggal_cetak'     => '2026-10-14',
                'bulk_catatan'           => 'Pembayaran massal via QRIS',
            ]);
            $bulkCustomRes->assertOk();

            $pembayaran = KartuPelajarPembayaran::where('peserta_didik_id', $pd->peserta_didik_id)->first();
            $this->assertEquals('lunas', $pembayaran->status_pembayaran);
            $this->assertEquals(30000, $pembayaran->nominal);
            $this->assertEquals('qris', $pembayaran->metode_pembayaran);
            $this->assertEquals('sudah_cetak', $pembayaran->status_cetak);
            $this->assertEquals('Pembayaran massal via QRIS', $pembayaran->catatan);
        }
    }
}
