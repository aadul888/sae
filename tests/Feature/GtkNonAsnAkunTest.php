<?php

namespace Tests\Feature;

use App\Support\Table;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * GTK Non-ASN wajib punya akun login di tabel `pengguna` sesuai JENIS-nya:
 * Guru → peran Guru, Tenaga Kependidikan/Laboran/Pustakawan → Tendik.
 *
 * Akun dibuat otomatis saat simpan (store) dan bisa dibuat massal lewat
 * endpoint `gtk-non-asn.buat-semua-akun`.
 */
class GtkNonAsnAkunTest extends TestCase
{
    use DatabaseTransactions;

    private function adminSession(): array
    {
        return ['pengguna_id' => 'admin-uji', 'role' => 'admin', 'nama' => 'Admin Uji'];
    }

    private function insertNonAsn(array $overrides = []): int
    {
        return DB::table('gtk_non_asn')->insertGetId(array_merge([
            'kode'         => 'NONASN-UJI-' . Str::random(6),
            'nama'         => 'GTK Uji ' . Str::random(4),
            'jenis_kelamin' => 'L',
            'jenis_ptk'    => 'Guru',
            'status'       => 'aktif',
            'created_at'   => now(),
            'updated_at'   => now(),
        ], $overrides));
    }

    public function test_simpan_non_asn_otomatis_membuat_akun_sesuai_jenis(): void
    {
        if (!Table::exists('gtk_non_asn')) {
            $this->markTestSkipped('Tabel gtk_non_asn belum ada.');
        }

        $email = 'guru.uji.' . Str::random(5) . '@sekolah.sch.id';

        $this->withSession(['user' => $this->adminSession()])->post(route('dashboard.gtk-non-asn.store'), [
            'nama'          => 'Guru Non ASN Uji',
            'jenis_ptk'     => 'Guru',
            'jenis_kelamin' => 'L',
            'email'         => $email,
            'tanggal_lahir' => '1990-02-18',
            'status'        => 'aktif',
        ])->assertRedirect();

        $baris = DB::table('gtk_non_asn')->where('email', $email)->first();
        $this->assertNotNull($baris, 'Data Non-ASN harus tersimpan.');
        $this->assertNotEmpty($baris->pengguna_id, 'Akun login harus otomatis dibuat saat simpan.');

        $akun = DB::table('pengguna')->where('pengguna_id', $baris->pengguna_id)->first();
        $this->assertNotNull($akun);
        $this->assertSame(strtolower($email), $akun->username);
        $this->assertSame('Guru', $akun->peran_id_str, 'Jenis Guru harus dipetakan ke peran Guru.');
        $this->assertSame('manual', $akun->sumber_data, 'Akun Non-ASN tidak boleh ikut sync Dapodik.');
    }

    public function test_buat_semua_akun_membuat_akun_dengan_peran_per_jenis(): void
    {
        if (!Table::exists('gtk_non_asn')) {
            $this->markTestSkipped('Tabel gtk_non_asn belum ada.');
        }

        $idGuru = $this->insertNonAsn([
            'jenis_ptk'     => 'Guru',
            'email'         => 'guru.semua.' . Str::random(5) . '@sekolah.sch.id',
            'tanggal_lahir' => '1988-05-01',
        ]);
        $idTendik = $this->insertNonAsn([
            'jenis_ptk'     => 'Tenaga Kependidikan',
            'email'         => 'tendik.semua.' . Str::random(5) . '@sekolah.sch.id',
            'tanggal_lahir' => '1992-08-17',
        ]);

        $res = $this->withSession(['user' => $this->adminSession()])
            ->postJson(route('dashboard.gtk-non-asn.buat-semua-akun'));
        $res->assertStatus(200)->assertJson(['status' => 'success']);

        $guru = DB::table('gtk_non_asn')->where('id', $idGuru)->first();
        $tendik = DB::table('gtk_non_asn')->where('id', $idTendik)->first();

        $this->assertNotEmpty($guru->pengguna_id);
        $this->assertNotEmpty($tendik->pengguna_id);

        $this->assertSame('Guru', DB::table('pengguna')->where('pengguna_id', $guru->pengguna_id)->value('peran_id_str'));
        $this->assertSame('Tenaga Kependidikan', DB::table('pengguna')->where('pengguna_id', $tendik->pengguna_id)->value('peran_id_str'));

        // Password awal = tanggal lahir TTTTBBHH.
        $this->assertSame('19920817', \App\Http\Controllers\GtkNonAsnController::passwordDariTanggalLahir($tendik->tanggal_lahir));
    }

    /** View menampilkan tombol "Buat Akun Semua" selama masih ada yang belum punya akun. */
    public function test_tombol_buat_semua_akun_tampil_bila_ada_yang_belum_punya_akun(): void
    {
        if (!Table::exists('gtk_non_asn')) {
            $this->markTestSkipped('Tabel gtk_non_asn belum ada.');
        }

        $this->insertNonAsn(['jenis_ptk' => 'Guru', 'email' => 'belumakun.' . Str::random(5) . '@sekolah.sch.id']);

        $this->withSession(['user' => $this->adminSession()])
            ->get(route('dashboard.gtk-non-asn.index'))
            ->assertStatus(200)
            ->assertSee('Buat Akun Semua');
    }

    /** Baris yang sudah punya akun harus dilewati, tidak dibuat ganda. */
    public function test_buat_semua_akun_melewati_yang_sudah_punya_akun(): void
    {
        if (!Table::exists('gtk_non_asn')) {
            $this->markTestSkipped('Tabel gtk_non_asn belum ada.');
        }

        // Baris dengan pengguna_id terisi (dianggap sudah punya akun).
        $this->insertNonAsn([
            'jenis_ptk'   => 'Guru',
            'email'       => 'sudah.ada.' . Str::random(5) . '@sekolah.sch.id',
            'pengguna_id' => 'pengguna-lama-uji',
            'username'    => 'sudah.ada',
        ]);

        $res = $this->withSession(['user' => $this->adminSession()])
            ->postJson(route('dashboard.gtk-non-asn.buat-semua-akun'));

        $res->assertStatus(200)->assertJson(['status' => 'success']);
        $this->assertSame(0, $res->json('summary.dibuat'), 'Baris yang sudah punya akun harus dilewati.');
    }

    /**
     * Akun hasil Non-ASN harus MUNCUL di Manajemen Pengguna (tab Guru/Tendik)
     * dan bisa di-impersonasi admin ("masuk sebagai").
     */
    public function test_akun_non_asn_muncul_di_pengguna_dan_bisa_diimpersonasi(): void
    {
        if (!Table::exists('gtk_non_asn')) {
            $this->markTestSkipped('Tabel gtk_non_asn belum ada.');
        }

        $idGuru = $this->insertNonAsn([
            'nama'          => 'Guru Muncul Uji',
            'jenis_ptk'     => 'Guru',
            'email'         => 'guru.muncul.' . Str::random(5) . '@sekolah.sch.id',
            'tanggal_lahir' => '1987-03-09',
        ]);

        $this->withSession(['user' => $this->adminSession()])
            ->postJson(route('dashboard.gtk-non-asn.buat-semua-akun'))
            ->assertStatus(200);

        $penggunaId = DB::table('gtk_non_asn')->where('id', $idGuru)->value('pengguna_id');
        $this->assertNotEmpty($penggunaId);

        // Muncul pada tab Guru di Manajemen Pengguna.
        $this->withSession(['user' => $this->adminSession()])
            ->get(route('dashboard.pengguna.index', ['tab' => 'guru', 'q' => 'Guru Muncul Uji']))
            ->assertStatus(200)
            ->assertSee('Guru Muncul Uji');

        // Admin bisa masuk sebagai pengguna tsb.
        $this->withSession(['user' => [
            'id' => 'admin-uji', 'pengguna_id' => 'admin-uji', 'role' => 'admin', 'nama' => 'Admin Uji',
        ]])->post('/dashboard/pengguna/' . $penggunaId . '/masuk-sebagai')
            ->assertRedirect();

        $this->assertSame($penggunaId, session('user.pengguna_id'));
    }
}
