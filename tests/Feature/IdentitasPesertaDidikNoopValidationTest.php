<?php

namespace Tests\Feature;

use App\Models\PesertaDidik;
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
}
