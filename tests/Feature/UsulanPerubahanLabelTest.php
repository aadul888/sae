<?php

namespace Tests\Feature;

use App\Models\PesertaDidik;
use App\Models\SiswaUsulanPerubahan;
use App\Services\KesiswaanUsulanService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Modal usulan perubahan (Verval PD) harus menampilkan LABEL, bukan ID angka,
 * untuk kolom referensi Dapodik (agama, pendidikan, pekerjaan, penghasilan,
 * transportasi, hobi, ...). Nilai mentah `nilai_baru` tetap utuh di DB karena
 * masih dipakai untuk menerapkan perubahan ke tabel peserta didik.
 */
class UsulanPerubahanLabelTest extends TestCase
{
    use DatabaseTransactions;

    private string $pdId = '';

    protected function tearDown(): void
    {
        if ($this->pdId !== '') {
            SiswaUsulanPerubahan::where('peserta_didik_id', $this->pdId)->delete();
            PesertaDidik::where('peserta_didik_id', $this->pdId)->delete();
        }
        parent::tearDown();
    }

    private function buatSiswa(): string
    {
        $this->pdId = 'PD-USL-' . random_int(100000, 999999);
        PesertaDidik::create([
            'peserta_didik_id' => $this->pdId,
            'nama' => 'Uji Usulan',
            'nisn' => '555' . random_int(1000000, 9999999),
            'jenis_kelamin' => 'L',
            'nama_rombel' => 'X-USL',
        ]);

        return $this->pdId;
    }

    public function test_modal_menampilkan_label_bukan_id_untuk_kolom_referensi(): void
    {
        $this->buatSiswa();

        // pekerjaan_ayah_id: 11 => Perdagangan, penghasilan_ibu_id: 0 => Tidak Berpenghasilan
        SiswaUsulanPerubahan::create([
            'peserta_didik_id' => $this->pdId,
            'kolom_perubahan' => 'pekerjaan_ayah_id',
            'nilai_lama' => '3',
            'nilai_baru' => '11',
            'alasan' => 'Koreksi pekerjaan',
            'status' => 'menunggu',
        ]);
        SiswaUsulanPerubahan::create([
            'peserta_didik_id' => $this->pdId,
            'kolom_perubahan' => 'penghasilan_ibu_id',
            'nilai_lama' => '1',
            'nilai_baru' => '0',
            'alasan' => 'Koreksi penghasilan',
            'status' => 'menunggu',
        ]);

        $res = app(KesiswaanUsulanService::class)->getUsulanDetail($this->pdId);
        $this->assertSame('success', $res['status']);

        $items = collect($res['data']['items'])->keyBy('kolom_perubahan');

        $ayah = $items->get('pekerjaan_ayah_id');
        $this->assertSame('Petani', $ayah['nilai_lama'], 'ID 3 harus jadi "Petani".');
        $this->assertSame('Perdagangan', $ayah['nilai_baru'], 'ID 11 harus jadi "Perdagangan".');

        $ibu = $items->get('penghasilan_ibu_id');
        $this->assertSame('Tidak Berpenghasilan', $ibu['nilai_baru'], 'ID 0 harus tetap terkonversi (bukan dianggap kosong).');
    }

    public function test_kolom_teks_tidak_diubah(): void
    {
        $this->buatSiswa();

        SiswaUsulanPerubahan::create([
            'peserta_didik_id' => $this->pdId,
            'kolom_perubahan' => 'nama_wali',
            'nilai_lama' => '',
            'nilai_baru' => 'lastri septiyani',
            'status' => 'menunggu',
        ]);

        $res = app(KesiswaanUsulanService::class)->getUsulanDetail($this->pdId);
        $item = collect($res['data']['items'])->firstWhere('kolom_perubahan', 'nama_wali');

        $this->assertSame('lastri septiyani', $item['nilai_baru']);
        $this->assertSame('(Belum Terisi / Kosong)', $item['nilai_lama']);
    }

    public function test_nilai_mentah_di_db_tidak_ikut_berubah(): void
    {
        $this->buatSiswa();

        $usulan = SiswaUsulanPerubahan::create([
            'peserta_didik_id' => $this->pdId,
            'kolom_perubahan' => 'pekerjaan_ayah_id',
            'nilai_lama' => '3',
            'nilai_baru' => '11',
            'status' => 'menunggu',
        ]);

        app(KesiswaanUsulanService::class)->getUsulanDetail($this->pdId);

        // Setelah serialisasi tampilan, nilai mentah di DB tetap ID (dipakai write-back).
        $this->assertSame('11', $usulan->fresh()->nilai_baru);
    }
}
