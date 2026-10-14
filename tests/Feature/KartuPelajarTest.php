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
}
