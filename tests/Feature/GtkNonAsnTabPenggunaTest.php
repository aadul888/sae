<?php

namespace Tests\Feature;

use App\Support\Table;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Akun GTK Non-ASN harus SELALU muncul di tab Manajemen Pengguna yang tepat,
 * walau nilai `peran_id_str` pada tabel `pengguna` tidak memakai kata kunci
 * standar ("Guru"/"Tenaga Kependidikan") — mis. tertimpa sync Dapodik jadi "PTK".
 *
 * Pengelompokan yang benar memakai `gtk_non_asn.jenis_ptk`, bukan cocok teks.
 */
class GtkNonAsnTabPenggunaTest extends TestCase
{
    use DatabaseTransactions;

    private function adminSession(): array
    {
        return ['pengguna_id' => 'admin-uji', 'role' => 'admin', 'nama' => 'Admin Uji'];
    }

    private function buatAkun(string $peran, string $nama): string
    {
        $id = (string) Str::uuid();
        DB::table('pengguna')->insert([
            'pengguna_id'  => $id,
            'username'     => 'uji.' . Str::random(8),
            'nama'         => $nama,
            'peran_id_str' => $peran,
            'password'     => bcrypt('x'),
            'ptk_id'       => null,
            'peserta_didik_id' => null,
            'sumber_data'  => 'manual',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
        return $id;
    }

    private function tautkan(string $penggunaId, string $jenisPtk): void
    {
        DB::table('gtk_non_asn')->insert([
            'kode'         => 'NONASN-TAB-' . Str::random(6),
            'nama'         => 'GTK Tab ' . Str::random(4),
            'jenis_ptk'    => $jenisPtk,
            'status'       => 'aktif',
            'pengguna_id'  => $penggunaId,
            'username'     => DB::table('pengguna')->where('pengguna_id', $penggunaId)->value('username'),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    public function test_akun_non_asn_muncul_walau_peran_bukan_kata_kunci_standar(): void
    {
        if (!Table::exists('gtk_non_asn')) {
            $this->markTestSkipped('Tabel gtk_non_asn belum ada.');
        }

        $namaGuru = 'Zulkifli Nurcahyo Uji';
        $namaTendik = 'Ratna Kusumawati Uji';

        // Peran sengaja "PTK" — tak memuat kata 'guru'/'tenaga kependidikan'.
        $idGuru = $this->buatAkun('PTK', $namaGuru);
        $idTendik = $this->buatAkun('PTK', $namaTendik);

        $this->tautkan($idGuru, 'Guru');
        $this->tautkan($idTendik, 'Tenaga Kependidikan');

        $usernameGuru = DB::table('pengguna')->where('pengguna_id', $idGuru)->value('username');

        // Guru muncul di tab Guru.
        $this->withSession(['user' => $this->adminSession()])
            ->get(route('dashboard.pengguna.index', ['tab' => 'guru', 'q' => $namaGuru]))
            ->assertStatus(200)
            ->assertSee($namaGuru);

        // Tendik muncul di tab Tendik.
        $this->withSession(['user' => $this->adminSession()])
            ->get(route('dashboard.pengguna.index', ['tab' => 'tendik', 'q' => $namaTendik]))
            ->assertStatus(200)
            ->assertSee($namaTendik);

        // Tidak tertukar tab: akun Guru tidak muncul di tab Tendik.
        // (Bandingkan via username agar kotak pencarian yang meng-echo `q` tak mengganggu.)
        $this->withSession(['user' => $this->adminSession()])
            ->get(route('dashboard.pengguna.index', ['tab' => 'tendik']))
            ->assertStatus(200)
            ->assertDontSee($usernameGuru);
    }
}
