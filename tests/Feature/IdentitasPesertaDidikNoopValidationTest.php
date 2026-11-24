<?php

namespace Tests\Feature;

use App\Models\PesertaDidik;
use App\Models\PesertaDidikBerkas;
use App\Models\PesertaDidikIdentitas;
use App\Models\SiswaUsulanPerubahan;
use Tests\TestCase;

class IdentitasPesertaDidikNoopValidationTest extends TestCase
{
    public function test_case_only_change_is_rejected_as_no_meaningful_change(): void
    {
        $pdId = 'PD-' . time() . '-' . random_int(100, 999);

        PesertaDidik::create([
            'peserta_didik_id' => $pdId,
            'nama' => 'Rizki Pratama',
            'nisn' => '1234567890',
            'jenis_kelamin' => 'L',
            'nama_rombel' => 'XII-A',
        ]);

        PesertaDidikIdentitas::create([
            'peserta_didik_id' => $pdId,
            'nisn' => '1234567890',
            'nama' => 'Rizki Pratama',
            'jenis_kelamin' => 'L',
            'status_konfirmasi' => 'sesuai',
        ]);

        $response = $this->withSession([
            'user' => [
                'role' => 'peserta_didik',
                'peserta_didik_id' => $pdId,
                'nama' => 'Rizki Pratama',
                'username' => 'rizki',
            ],
        ])->post(route('dashboard.identitas.update'), [
            'peserta_didik_id' => $pdId,
            'nama' => 'rizki pratama',
            'jenis_kelamin' => 'L',
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(0, SiswaUsulanPerubahan::where('peserta_didik_id', $pdId)->count());

        PesertaDidik::where('peserta_didik_id', $pdId)->delete();
        PesertaDidikIdentitas::where('peserta_didik_id', $pdId)->delete();
    }

    public function test_text_value_in_decimal_column_is_rejected_not_500(): void
    {
        $pdId = 'PD-' . time() . '-' . random_int(100, 999);

        PesertaDidik::create([
            'peserta_didik_id' => $pdId,
            'nama' => 'Budi Santoso',
            'nisn' => '1234567891',
            'jenis_kelamin' => 'L',
            'nama_rombel' => 'X-A',
        ]);

        PesertaDidikIdentitas::create([
            'peserta_didik_id' => $pdId,
            'nisn' => '1234567891',
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'status_konfirmasi' => 'perlu_perbaikan',
        ]);

        $response = $this->withSession([
            'user' => [
                'role' => 'tendik',
                'nama' => 'Operator',
                'username' => 'operator',
            ],
        ])->post(route('dashboard.identitas.update'), [
            'peserta_didik_id' => $pdId,
            'nama' => 'Budi Santosa',
            'lintang' => 'Et voluptas dolorum',
        ]);

        $response->assertSessionHasErrors('lintang');
        $this->assertSame(0, SiswaUsulanPerubahan::where('peserta_didik_id', $pdId)->count());

        PesertaDidik::where('peserta_didik_id', $pdId)->delete();
        PesertaDidikIdentitas::where('peserta_didik_id', $pdId)->delete();
    }

    public function test_validation_errors_are_rendered_per_field_not_as_top_banner(): void
    {
        $pdId = 'PD-' . time() . '-' . random_int(100, 999);

        PesertaDidik::create([
            'peserta_didik_id' => $pdId,
            'nama' => 'Budi Santoso',
            'nisn' => '1234567891',
            'jenis_kelamin' => 'L',
            'nama_rombel' => 'X-A',
        ]);

        PesertaDidikIdentitas::create([
            'peserta_didik_id' => $pdId,
            'nisn' => '1234567891',
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'status_konfirmasi' => 'perlu_perbaikan',
        ]);

        $response = $this->withSession([
            'user' => [
                'role' => 'tendik',
                'nama' => 'Operator',
                'username' => 'operator',
            ],
        ])->followingRedirects()->post(route('dashboard.identitas.update'), [
            'peserta_didik_id' => $pdId,
            'nama' => 'Budi Santosa',
            'lintang' => 'Et voluptas dolorum',
        ]);

        $response->assertOk();
        // Peta error tersedia untuk JS yang menempelkan pesan di tiap field.
        $response->assertSee('id="saeFormErrors"', false);
        $response->assertSee('"lintang"', false);
        // Banner ringkasan lama tidak lagi dirender di view identitas.
        $response->assertDontSee('Periksa kembali isian berikut', false);

        PesertaDidik::where('peserta_didik_id', $pdId)->delete();
        PesertaDidikIdentitas::where('peserta_didik_id', $pdId)->delete();
    }

    public function test_field_type_validation_rejects_wrong_kind_of_input(): void
    {
        $pdId = 'PD-' . time() . '-' . random_int(100, 999);

        PesertaDidik::create([
            'peserta_didik_id' => $pdId,
            'nama' => 'Citra Dewi',
            'nisn' => '1234567892',
            'jenis_kelamin' => 'P',
            'nama_rombel' => 'X-B',
        ]);

        PesertaDidikIdentitas::create([
            'peserta_didik_id' => $pdId,
            'nisn' => '1234567892',
            'nama' => 'Citra Dewi',
            'jenis_kelamin' => 'P',
            'status_konfirmasi' => 'perlu_perbaikan',
        ]);

        $response = $this->withSession([
            'user' => [
                'role' => 'tendik',
                'nama' => 'Operator',
                'username' => 'operator',
            ],
        ])->post(route('dashboard.identitas.update'), [
            'peserta_didik_id' => $pdId,
            'nama' => 'Citra123',          // nama tidak boleh mengandung angka
            'nik' => '3200AB',             // NIK hanya angka
            'kode_pos' => '12x45',         // kode pos hanya angka
            'email' => 'bukan-email',      // format email harus valid
            'waktu_tempuh_menit' => '90',  // menit maksimal 59
            'lintang' => 'bukan-angka',    // koordinat hanya angka
        ]);

        $response->assertSessionHasErrors(['nama', 'nik', 'kode_pos', 'email', 'waktu_tempuh_menit', 'lintang']);
        $this->assertSame(0, SiswaUsulanPerubahan::where('peserta_didik_id', $pdId)->count());

        PesertaDidik::where('peserta_didik_id', $pdId)->delete();
        PesertaDidikIdentitas::where('peserta_didik_id', $pdId)->delete();
    }

    public function test_unfinished_usulan_blocks_new_usulan_and_confirmation(): void
    {
        $pdId = 'PD-' . time() . '-' . random_int(100, 999);
        $nisn = '12345' . random_int(10000, 99999);

        PesertaDidik::create([
            'peserta_didik_id' => $pdId,
            'nama' => 'Dewi Lestari',
            'nisn' => $nisn,
            'jenis_kelamin' => 'P',
            'nama_rombel' => 'X-C',
        ]);

        PesertaDidikIdentitas::create([
            'peserta_didik_id' => $pdId,
            'nisn' => $nisn,
            'nama' => 'Dewi Lestari',
            'jenis_kelamin' => 'P',
            'status_konfirmasi' => 'perlu_perbaikan',
        ]);

        // Berkas KK & Ijazah valid -> syarat form terbuka terpenuhi.
        foreach (['kartu_keluarga', 'ijazah_smp'] as $jenis) {
            PesertaDidikBerkas::create([
                'peserta_didik_id' => $pdId,
                'jenis_berkas' => $jenis,
                'nama_berkas' => $jenis,
                'file_path' => 'uji/' . $jenis . '.pdf',
                'file_name' => $jenis . '.pdf',
                'file_size' => 0,
                'mime_type' => 'application/pdf',
                'status' => 'valid',
            ]);
        }

        // Usulan lama sudah DISETUJUI tetapi belum masuk Dapodik => belum tuntas.
        SiswaUsulanPerubahan::create([
            'peserta_didik_id' => $pdId,
            'batch_id' => 'uji-batch-1',
            'kolom_perubahan' => 'nama',
            'nilai_lama' => 'Dewi Lestari',
            'nilai_baru' => 'Dewi Lestari P',
            'status' => 'disetujui',
            'created_by' => 'Dewi Lestari',
        ]);

        $session = [
            'user' => [
                'role' => 'peserta_didik',
                'peserta_didik_id' => $pdId,
                'nama' => 'Dewi Lestari',
                'username' => $nisn,
            ],
        ];

        // 1) Ajukan usulan baru -> ditolak karena belum tuntas.
        $r1 = $this->withSession($session)->post(route('dashboard.identitas.update'), [
            'peserta_didik_id' => $pdId,
            'nama' => 'Dewi Lestari Puspita',
        ]);
        $r1->assertSessionHas('warning');
        $this->assertSame(1, SiswaUsulanPerubahan::where('peserta_didik_id', $pdId)->count());

        // 2) Ubah pilihan konfirmasi -> ditolak selama usulan belum tuntas.
        $r2 = $this->withSession($session)->post(route('dashboard.identitas.konfirmasi'), [
            'peserta_didik_id' => $pdId,
            'status' => 'sesuai',
        ]);
        $r2->assertSessionHas('warning');
        $this->assertSame('perlu_perbaikan', PesertaDidikIdentitas::where('peserta_didik_id', $pdId)->value('status_konfirmasi'));

        SiswaUsulanPerubahan::where('peserta_didik_id', $pdId)->delete();
        PesertaDidikBerkas::where('peserta_didik_id', $pdId)->delete();
        PesertaDidik::where('peserta_didik_id', $pdId)->delete();
        PesertaDidikIdentitas::where('peserta_didik_id', $pdId)->delete();
    }
}
