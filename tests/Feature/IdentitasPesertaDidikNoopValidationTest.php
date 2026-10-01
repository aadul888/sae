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
}
