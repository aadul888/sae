<?php

namespace Tests\Unit;

use App\Services\GtkGabunganService;
use PHPUnit\Framework\TestCase;

/**
 * GTK Non-ASN disimpan di tabel `gtk_non_asn`, TERPISAH dari `gtk`.
 *
 * Alasan: FeederReceiverController::processGtk() melakukan
 * `DB::table('gtk')->delete()` lalu insert ulang setiap tarik Dapodik.
 * Bila Non-ASN disimpan di `gtk`, datanya akan hilang.
 *
 * Berkas ini menjaga asumsi itu: id Non-ASN wajib berawalan `nonasn:`
 * dan tidak pernah bertabrakan dengan ptk_id Dapodik.
 */
class GtkNonAsnSeparationTest extends TestCase
{
    public function test_id_non_asn_berawalan_nonasn_dan_dikenali(): void
    {
        $baris = (object) [
            'id' => 12,
            'kode' => 'NONASN-0012',
            'nama' => 'Budi Santoso',
            'gelar_depan' => 'Drs.',
            'gelar_belakang' => 'S.Pd.',
            'jenis_kelamin' => 'L',
            'nik' => '3203180201900001',
            'nuptk' => null,
            'nip' => null,
            'jenis_ptk' => 'Guru',
            'jabatan' => 'Guru Mata Pelajaran',
            'status_kepegawaian' => null,
            'pendidikan_terakhir' => 'S1',
            'bidang_studi' => 'Matematika',
            'tempat_lahir' => 'Cianjur',
            'tanggal_lahir' => '1990-02-18',
            'agama' => 'Islam',
            'email' => 'budi@sekolah.sch.id',
            'no_hp' => '081234567890',
            'alamat' => 'Jl. Raya Pasirpari',
        ];

        $service = new GtkGabunganService();
        $sepertiGtk = $service->bentukSepertiGtk($baris);

        $this->assertSame('nonasn:12', $sepertiGtk->ptk_id);
        $this->assertTrue(GtkGabunganService::isNonAsn($sepertiGtk->ptk_id));
        $this->assertSame(GtkGabunganService::SUMBER_NON_ASN, $sepertiGtk->sumber_data);

        // status kepegawaian kosong wajib jatuh ke label Non-ASN
        $this->assertSame('Non-ASN', $sepertiGtk->status_kepegawaian);
    }

    public function test_ptk_id_dapodik_tidak_pernah_dianggap_non_asn(): void
    {
        $this->assertFalse(GtkGabunganService::isNonAsn('a1b2c3d4-0000-0000-0000-000000000000'));
        $this->assertFalse(GtkGabunganService::isNonAsn(null));
        $this->assertFalse(GtkGabunganService::isNonAsn(''));

        // Prefiks harus exact di awal — 'nonasn' tanpa titik dua bukan id Non-ASN.
        $this->assertFalse(GtkGabunganService::isNonAsn('nonasn-12'));
    }
}
