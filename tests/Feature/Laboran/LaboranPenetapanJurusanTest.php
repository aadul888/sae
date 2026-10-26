<?php

namespace Tests\Feature\Laboran;

use App\Support\LaboranScope;
use Illuminate\Support\Facades\DB;

/**
 * N. Penetapan jurusan pada penugasan tugas tambahan (sumber cakupan data).
 *
 * Alur UI: Dashboard > Manajemen Pengguna > tab Tugas Tambahan > ikon pensil.
 */
class LaboranPenetapanJurusanTest extends LaboranTestCase
{
    public function test_admin_dapat_menetapkan_jurusan_pada_penugasan_laboran(): void
    {
        $duty = $this->firstLaboranDuty();

        DB::table('ptk_tugas_tambahan')->where('id', $duty->id)->update(['jurusan_id' => null]);
        LaboranScope::flush();

        $this->withSession($this->adminSession())
            ->putJson(route('dashboard.pengguna.tugas-tambahan.update', $duty->id), [
                'jurusan_id' => self::JUR_ATPH,
                'is_active'  => 1,
            ])
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertSame(
            self::JUR_ATPH,
            DB::table('ptk_tugas_tambahan')->where('id', $duty->id)->value('jurusan_id')
        );
    }

    public function test_penetapan_jurusan_langsung_mengaktifkan_scoping_laboran(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);

        // Sebelum ditetapkan: permissive (unrestricted)
        LaboranScope::flush();
        DB::table('ptk_tugas_tambahan')->where('id', $this->created['duty_id'])
            ->update(['jurusan_id' => null]);
        LaboranScope::flush();

        $permissive = LaboranScope::resolve($session['user']);
        $this->assertTrue($permissive['unrestricted'], 'Tanpa jurusan, scope harus permissive.');

        // Tetapkan jurusan via endpoint
        $this->withSession($this->adminSession())
            ->putJson(route('dashboard.pengguna.tugas-tambahan.update', $this->created['duty_id']), [
                'jurusan_id' => self::JUR_TKJ,
                'is_active'  => 1,
            ])
            ->assertStatus(200);

        LaboranScope::flush();
        $locked = LaboranScope::resolve($session['user']);

        $this->assertFalse($locked['unrestricted']);
        $this->assertSame(self::JUR_TKJ, $locked['jurusan_id']);
    }

    public function test_penetapan_jurusan_ditolak_tanpa_hak_akses_update(): void
    {
        $duty = $this->firstLaboranDuty();

        // Kondisi awal ditetapkan secara eksplisit agar pengujian tidak bergantung urutan
        DB::table('ptk_tugas_tambahan')->where('id', $duty->id)->update(['jurusan_id' => self::JUR_TKJ]);
        LaboranScope::flush();

        // Sesi pengguna tanpa peran apa pun
        $res = $this->withSession(['user' => [
            'id'           => 999999,
            'pengguna_id'  => 999999,
            'name'         => 'Tanpa Hak',
            'nama'         => 'Tanpa Hak',
            'username'     => 'tanpa_hak',
            'role'         => 'peserta_didik',
            'peran_id_str' => 'Peserta Didik',
            'ptk_id'       => null,
        ]])->putJson(route('dashboard.pengguna.tugas-tambahan.update', $duty->id), [
            'jurusan_id' => self::JUR_ATPH,
        ]);

        $this->assertContains($res->getStatusCode(), [403, 302]);

        // Nilai jurusan tidak boleh berubah
        $this->assertSame(
            self::JUR_TKJ,
            DB::table('ptk_tugas_tambahan')->where('id', $duty->id)->value('jurusan_id')
        );
    }

    public function test_halaman_pengguna_menampilkan_tombol_ubah_penugasan(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.pengguna.index', ['tab' => 'tugas_tambahan']));

        $res->assertStatus(200);
        $html = $res->getContent();

        $this->assertStringContainsString('btn-edit-duty', $html);
        $this->assertStringContainsString('modalEditDuty', $html);
        $this->assertStringContainsString('editDutyJurusan', $html);
    }

    public function test_dashboard_laboran_memperingatkan_jurusan_belum_ditetapkan(): void
    {
        $session = $this->laboranSession(null);

        $res = $this->withSession($session)->get(route('dashboard.laboran.dashboard'));
        $res->assertStatus(200);
        $this->assertStringContainsString('Penugasan jurusan belum ditetapkan', $res->getContent());

        // Setelah jurusan ditetapkan, peringatan hilang
        DB::table('ptk_tugas_tambahan')->where('id', $this->created['duty_id'])
            ->update(['jurusan_id' => self::JUR_ATPH]);
        LaboranScope::flush();

        $res2 = $this->withSession($session)->get(route('dashboard.laboran.dashboard'));
        $res2->assertStatus(200);
        $this->assertStringNotContainsString('Penugasan jurusan belum ditetapkan', $res2->getContent());
    }

    public function test_dropdown_jurusan_menampilkan_jumlah_rombel_sebagai_pembeda(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.pengguna.index', ['tab' => 'tugas_tambahan']));

        $res->assertStatus(200);

        // Label pembeda level Program vs Konsentrasi Keahlian
        $normalized = preg_replace('/\s+/', ' ', $res->getContent());
        $this->assertStringContainsString('rombel', $normalized);
    }

    /**
     * REGRESI: baris WALI_KELAS menyimpan jurusan hasil sinkronisasi Dapodik.
     * Mengubah penugasan tidak boleh menghapus jurusan tersebut.
     */
    public function test_ubah_penugasan_wali_kelas_tidak_menghapus_jurusan(): void
    {
        $duty = DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->where('rtt.kode', 'WALI_KELAS')
            ->whereNotNull('ptt.jurusan_id')
            ->select('ptt.*')
            ->first();

        $this->assertNotNull($duty, 'Baris WALI_KELAS dengan jurusan tidak tersedia.');
        $jurusanAwal = $duty->jurusan_id;

        // Kirim payload seperti yang dikirim form (jurusan tidak termasuk karena
        // field jurusan disembunyikan untuk WALI_KELAS).
        $this->withSession($this->adminSession())
            ->putJson(route('dashboard.pengguna.tugas-tambahan.update', $duty->id), [
                'rombel_id'  => $duty->rombel_id,
                'nomor_sk'   => $duty->nomor_sk,
                'keterangan' => $duty->keterangan,
                'is_active'  => 1,
            ])
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertSame(
            $jurusanAwal,
            DB::table('ptk_tugas_tambahan')->where('id', $duty->id)->value('jurusan_id'),
            'Jurusan WALI_KELAS tidak boleh hilang saat penugasan diubah.'
        );
    }

    /**
     * REGRESI: admin boleh mengosongkan jurusan pada penugasan LABORAN
     * (mis. laboran dipindah menjadi lintas jurusan).
     */
    public function test_admin_dapat_mengosongkan_jurusan_laboran(): void
    {
        $duty = $this->firstLaboranDuty();

        DB::table('ptk_tugas_tambahan')->where('id', $duty->id)
            ->update(['jurusan_id' => self::JUR_ATPH]);
        LaboranScope::flush();

        $this->withSession($this->adminSession())
            ->putJson(route('dashboard.pengguna.tugas-tambahan.update', $duty->id), [
                'jurusan_id' => '',
                'is_active'  => 1,
            ])
            ->assertStatus(200);

        $this->assertNull(
            DB::table('ptk_tugas_tambahan')->where('id', $duty->id)->value('jurusan_id')
        );
    }

    /**
     * Ambil baris tugas tambahan LABORAN pertama, sekaligus catat untuk cleanup.
     */
    private function firstLaboranDuty(): object
    {
        $duty = DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->where('rtt.kode', 'LABORAN')
            ->select('ptt.*')
            ->first();

        $this->assertNotNull($duty, 'Baris tugas tambahan LABORAN tidak tersedia untuk pengujian.');
        $this->created['duty_id'] = $duty->id;

        return $duty;
    }
}
