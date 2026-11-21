<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Uji E-Learning: akses peran (guru/peserta didik), CRUD tugas,
 * pengumpulan, penilaian. Semua tulis dibungkus transaksi & di-rollback.
 */
class ElearningModuleTest extends TestCase
{
    private function guruSession(): array
    {
        return ['user' => [
            'id' => 'guru-el-test',
            'pengguna_id' => 'guru-el-test',
            'username' => 'guru1',
            'nama' => 'Guru E-Learning',
            'role' => 'guru',
            'ptk_id' => 'ptk-el-test',
        ]];
    }

    private function pdSession(): array
    {
        return ['user' => [
            'id' => 'pd-el-test',
            'pengguna_id' => 'pd-el-test',
            'username' => '0012345678',
            'nama' => 'Siswa E-Learning',
            'role' => 'peserta_didik',
            'peserta_didik_id' => 'pd-el-test-uuid',
        ]];
    }

    private function adminSession(): array
    {
        return ['user' => [
            'id' => 'admin-el-test',
            'pengguna_id' => 'admin-el-test',
            'username' => 'admin',
            'nama' => 'Administrator',
            'role' => 'admin',
        ]];
    }

    public function test_guest_redirected_from_elearning(): void
    {
        $this->get(route('dashboard.elearning.index'))->assertStatus(302);
    }

    public function test_admin_and_guru_can_open_elearning_tabs(): void
    {
        foreach (['materi', 'tugas', 'pengumpulan'] as $tab) {
            $this->withSession($this->adminSession())
                ->get(route('dashboard.elearning.index', ['tab' => $tab]))
                ->assertStatus(200);

            $this->withSession($this->guruSession())
                ->get(route('dashboard.elearning.index', ['tab' => $tab]))
                ->assertStatus(200);
        }
    }

    public function test_peserta_didik_can_open_elearning_tabs(): void
    {
        foreach (['materi', 'tugas', 'pengumpulan'] as $tab) {
            $this->withSession($this->pdSession())
                ->get(route('dashboard.elearning.index', ['tab' => $tab]))
                ->assertStatus(200);
        }
    }

    public function test_guru_permission_matrix_granted(): void
    {
        $this->assertTrue(\App\Models\RolePermission::canAccess('guru', 'menu_elearning', 'create'));
        $this->assertTrue(\App\Models\RolePermission::canAccess('guru', 'menu_elearning', 'update'));
        $this->assertTrue(\App\Models\RolePermission::canAccess('guru', 'menu_elearning', 'delete'));
        $this->assertTrue(\App\Models\RolePermission::canAccess('peserta_didik', 'menu_elearning', 'read'));
        $this->assertFalse(\App\Models\RolePermission::canAccess('peserta_didik', 'menu_elearning', 'create'));
    }

    public function test_tugas_lifecycle_and_grading(): void
    {
        DB::beginTransaction();
        try {
            // Data uji deterministik: rombel + peserta didik + pembelajaran milik rombel tsb.
            $rombelId = 'rombel-el-test';
            $pdId = 'pd-el-test-uuid';
            $pembelajaranId = 'pemb-el-test';
            $now = now();

            DB::table('rombongan_belajar')->insertOrIgnore([
                'rombongan_belajar_id' => $rombelId,
                'nama' => 'Rombel Uji EL',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('peserta_didik')->insertOrIgnore([
                'peserta_didik_id' => $pdId,
                'nama' => 'Siswa E-Learning',
                'rombongan_belajar_id' => $rombelId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('pembelajaran')->insertOrIgnore([
                'pembelajaran_id' => $pembelajaranId,
                'rombongan_belajar_id' => $rombelId,
                'nama_mata_pelajaran' => 'Mapel Uji EL',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Guru buat tugas (admin bypass batas pembelajaran).
            $res = $this->withSession($this->adminSession())->postJson(route('dashboard.elearning.tugas.store'), [
                'pembelajaran_id' => $pembelajaranId,
                'judul' => 'Tugas Uji Otomatis',
                'deskripsi' => 'Kerjakan.',
                'bobot' => 20,
                'status' => 'terbit',
            ]);
            $res->assertStatus(200)->assertJson(['status' => 'success']);
            $tugasId = $res->json('id');
            $this->assertNotNull($tugasId);

            // Peserta didik kumpulkan (pembelajaran ada di rombelnya → 200).
            $kumpul = $this->withSession($this->pdSession())->postJson(
                route('dashboard.elearning.pengumpulan.store', ['tugas' => $tugasId]),
                ['jawaban_teks' => 'Ini jawaban uji.', 'link_url' => 'https://youtu.be/dQw4w9WgXcQ']
            );
            $kumpul->assertStatus(200)->assertJson(['status' => 'success']);

            $pengumpulan = DB::table('learn_pengumpulan')->where('tugas_id', $tugasId)->first();
            $this->assertNotNull($pengumpulan);
            $this->assertSame('https://youtu.be/dQw4w9WgXcQ', $pengumpulan->link_url);

            $grade = $this->withSession($this->adminSession())->postJson(
                route('dashboard.elearning.pengumpulan.grade', ['id' => $pengumpulan->id]),
                ['nilai' => 88]
            );
            $grade->assertStatus(200)->assertJson(['status' => 'success']);
            $this->assertEquals(88, (float) DB::table('learn_pengumpulan')->where('id', $pengumpulan->id)->value('nilai'));

            // Hapus tugas (cascade pengumpulan).
            $this->withSession($this->adminSession())
                ->deleteJson(route('dashboard.elearning.tugas.destroy', ['id' => $tugasId]))
                ->assertStatus(200);
            $this->assertNull(DB::table('learn_pengumpulan')->where('tugas_id', $tugasId)->first());
        } finally {
            DB::rollBack();
        }
    }

    public function test_peserta_didik_cannot_create_tugas(): void
    {
        DB::beginTransaction();
        try {
            $pembelajaranId = DB::table('pembelajaran')->value('pembelajaran_id') ?? 'x';
            $res = $this->withSession($this->pdSession())->postJson(route('dashboard.elearning.tugas.store'), [
                'pembelajaran_id' => $pembelajaranId,
                'judul' => 'Tidak boleh',
            ]);
            $this->assertContains($res->getStatusCode(), [403, 422]);
        } finally {
            DB::rollBack();
        }
    }

    public function test_pengumpulan_hanya_tautan_berlaku_dan_url_salah_ditolak(): void
    {
        DB::beginTransaction();
        try {
            $rombelId = 'rombel-link-test';
            $pdId = 'pd-el-test-uuid';
            $pembelajaranId = 'pemb-link-test';
            $now = now();

            DB::table('rombongan_belajar')->insertOrIgnore(['rombongan_belajar_id' => $rombelId, 'nama' => 'Rombel Link', 'created_at' => $now, 'updated_at' => $now]);
            DB::table('peserta_didik')->insertOrIgnore(['peserta_didik_id' => $pdId, 'nama' => 'Siswa E-Learning', 'rombongan_belajar_id' => $rombelId, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('pembelajaran')->insertOrIgnore(['pembelajaran_id' => $pembelajaranId, 'rombongan_belajar_id' => $rombelId, 'nama_mata_pelajaran' => 'Mapel Link', 'created_at' => $now, 'updated_at' => $now]);

            $tugasId = DB::table('learn_tugas')->insertGetId([
                'pembelajaran_id' => $pembelajaranId,
                'judul' => 'Tugas Link',
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Hanya tautan (tanpa teks) → diterima.
            $this->withSession($this->pdSession())
                ->postJson(route('dashboard.elearning.pengumpulan.store', ['tugas' => $tugasId]), ['link_url' => 'https://docs.google.com/presentation/d/abc'])
                ->assertStatus(200)->assertJson(['status' => 'success']);

            // URL tidak sah → 422 dari validasi.
            $this->withSession($this->pdSession())
                ->postJson(route('dashboard.elearning.pengumpulan.store', ['tugas' => $tugasId]), ['link_url' => 'bukan-url'])
                ->assertStatus(422);

            // Tanpa teks & tanpa tautan → ditolak (tugas berbeda, belum ada pengumpulan).
            $tugasKosong = DB::table('learn_tugas')->insertGetId([
                'pembelajaran_id' => $pembelajaranId,
                'judul' => 'Tugas Kosong',
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->withSession($this->pdSession())
                ->postJson(route('dashboard.elearning.pengumpulan.store', ['tugas' => $tugasKosong]), ['jawaban_teks' => '   '])
                ->assertStatus(422);
        } finally {
            DB::rollBack();
        }
    }

    public function test_cbt_bank_soal_dan_ujian_renders(): void
    {
        $this->withSession($this->adminSession())
            ->get(route('dashboard.cbt.index'))
            ->assertStatus(200)
            ->assertSee('Bank Soal', false);

        $this->withSession($this->adminSession())
            ->get(route('dashboard.cbt.index', ['tab' => 'ujian']))
            ->assertStatus(200)
            ->assertSee('Ujian', false);
    }

    public function test_cbt_guru_punya_izin_kelola(): void
    {
        $this->assertTrue(\App\Models\RolePermission::canAccess('guru', 'menu_cbt', 'create'));
        $this->assertTrue(\App\Models\RolePermission::canAccess('guru', 'menu_cbt', 'delete'));
    }

    /**
     * Regresi: dasbor CBT guru 500 karena kolom `pembelajaran_id` ambigu saat
     * di-join `pembelajaran as p` (dulu whereIn tanpa kualifikasi tabel).
     */
    public function test_cbt_guru_dasbor_dan_ujian_tidak_500(): void
    {
        DB::beginTransaction();
        try {
            $rombelId = 'rombel-cbt-guru';
            $pembelajaranId = 'pemb-cbt-guru';
            $ptkId = 'ptk-el-test'; // sama dengan guruSession()
            $now = now();

            DB::table('rombongan_belajar')->insertOrIgnore(['rombongan_belajar_id' => $rombelId, 'nama' => 'Rombel CBT Guru', 'created_at' => $now, 'updated_at' => $now]);
            DB::table('pembelajaran')->insertOrIgnore(['pembelajaran_id' => $pembelajaranId, 'rombongan_belajar_id' => $rombelId, 'ptk_id' => $ptkId, 'nama_mata_pelajaran' => 'Mapel CBT Guru', 'created_at' => $now, 'updated_at' => $now]);

            foreach (['dasbor', 'bank-soal', 'ujian', 'periode', 'sesi'] as $tab) {
                $this->withSession($this->guruSession())
                    ->get(route('dashboard.cbt.index', ['tab' => $tab]))
                    ->assertStatus(200);
            }
        } finally {
            DB::rollBack();
        }
    }

    public function test_cbt_soal_lifecycle_dan_kertas_ujian(): void
    {
        DB::beginTransaction();
        try {
            $rombelId = 'rombel-cbt-test';
            $pembelajaranId = 'pemb-cbt-test';
            $now = now();

            DB::table('rombongan_belajar')->insertOrIgnore(['rombongan_belajar_id' => $rombelId, 'nama' => 'Rombel CBT', 'created_at' => $now, 'updated_at' => $now]);
            DB::table('pembelajaran')->insertOrIgnore(['pembelajaran_id' => $pembelajaranId, 'rombongan_belajar_id' => $rombelId, 'nama_mata_pelajaran' => 'Mapel CBT', 'created_at' => $now, 'updated_at' => $now]);

            // Soal PG dengan opsi + kunci.
            $res = $this->withSession($this->adminSession())->postJson(route('dashboard.cbt.soal.store'), [
                'pembelajaran_id' => $pembelajaranId,
                'tipe' => 'pg',
                'pertanyaan' => '2 + 2 = ?',
                'opsi' => ['3', '4', '5'],
                'kunci' => 'B',
                'bobot' => 2,
            ]);
            $res->assertStatus(200)->assertJson(['status' => 'success']);
            $soalId = $res->json('id');
            $this->assertNotNull($soalId);

            $soal = DB::table('ujian_soal')->where('id', $soalId)->first();
            $this->assertSame('pg', $soal->tipe);
            $this->assertSame('B', $soal->kunci);
            $this->assertJson($soal->opsi);

            // Bank soal menampilkan akordeon per mata pelajaran + tombol lihat.
            $this->withSession($this->adminSession())
                ->get(route('dashboard.cbt.index', ['tab' => 'bank-soal']))
                ->assertStatus(200)
                ->assertSee('cbt-mapel-acc', false)
                ->assertSee('btn-lihat-soal', false);

            // Ujian memuat soal tsb.
            $u = $this->withSession($this->adminSession())->postJson(route('dashboard.cbt.ujian.store'), [
                'pembelajaran_id' => $pembelajaranId,
                'judul' => 'Ulangan CBT',
                'tipe' => 'ulangan',
                'durasi_menit' => 45,
                'kkm' => 70,
                'status' => 'terbit',
                'soal_ids' => [$soalId],
            ]);
            $u->assertStatus(200)->assertJson(['status' => 'success']);
            $ujianId = $u->json('id');
            $this->assertSame(1, DB::table('ujian_soal_item')->where('ujian_id', $ujianId)->count());

            // Hapus ujian → kertas soal hilang, bank soal tetap.
            $this->withSession($this->adminSession())
                ->deleteJson(route('dashboard.cbt.ujian.destroy', ['id' => $ujianId]))
                ->assertStatus(200);
            $this->assertNull(DB::table('ujian_soal_item')->where('ujian_id', $ujianId)->first());
            $this->assertNotNull(DB::table('ujian_soal')->where('id', $soalId)->first());
        } finally {
            DB::rollBack();
        }
    }

    public function test_cbt_soal_tipe_uraian_tanpa_kunci(): void
    {
        DB::beginTransaction();
        try {
            $pembelajaranId = DB::table('pembelajaran')->value('pembelajaran_id') ?? 'x';
            $res = $this->withSession($this->adminSession())->postJson(route('dashboard.cbt.soal.store'), [
                'pembelajaran_id' => $pembelajaranId,
                'tipe' => 'uraian',
                'pertanyaan' => 'Jelaskan konsep A.',
                'kunci' => 'harus diabaikan',
            ]);
            $res->assertStatus(200);
            $soal = DB::table('ujian_soal')->where('id', $res->json('id'))->first();
            $this->assertNull($soal->kunci);
            $this->assertNull($soal->opsi);
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Regresi: soal dapat menyimpan media (gambar soal, gambar opsi, video URL)
     * dan pratinjau `show` mengembalikan URL-nya.
     */
    public function test_cbt_soal_media_gambar_video_dan_pratinjau(): void
    {
        DB::beginTransaction();
        try {
            $pembelajaranId = DB::table('pembelajaran')->value('pembelajaran_id') ?? 'x';
            $res = $this->withSession($this->adminSession())->postJson(route('dashboard.cbt.soal.store'), [
                'pembelajaran_id' => $pembelajaranId,
                'tipe' => 'pg',
                'pertanyaan' => 'Soal bergambar?',
                'opsi' => ['Kucing', 'Anjing'],
                'kunci' => 'Kucing',
                'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            ]);
            $res->assertStatus(200);
            $id = $res->json('id');

            $soal = DB::table('ujian_soal')->where('id', $id)->first();
            $this->assertSame('https://youtu.be/dQw4w9WgXcQ', $soal->video_url);

            // Pratinjau (show) memuat media + video embed.
            $show = $this->withSession($this->adminSession())
                ->getJson(route('dashboard.cbt.soal.show', ['id' => $id]));
            $show->assertStatus(200)->assertJson(['status' => 'success']);
            $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ', $show->json('data.video_embed'));
            $this->assertSame('Kucing', $show->json('data.opsi_media.0.teks'));
            $this->assertNull($show->json('data.opsi_media.0.gambar_url'));

            // Video URL tak dikenal → tanpa embed (di-render sebagai link).
            DB::table('ujian_soal')->where('id', $id)->update(['video_url' => 'https://contoh.test/video.mp4']);
            $show2 = $this->withSession($this->adminSession())->getJson(route('dashboard.cbt.soal.show', ['id' => $id]));
            $this->assertNull($show2->json('data.video_embed'));
        } finally {
            DB::rollBack();
        }
    }

    public function test_dasbor_tab_render_per_peran(): void
    {
        foreach (['admin' => $this->adminSession(), 'guru' => $this->guruSession(), 'pd' => $this->pdSession()] as $label => $sess) {
            $this->withSession($sess)
                ->get(route('dashboard.elearning.index', ['tab' => 'dasbor']))
                ->assertStatus(200)
                ->assertSee('Dasbor', false);
        }
    }

    public function test_materi_isi_html_tersanitasi_dan_soal_tersimpan(): void
    {
        DB::beginTransaction();
        try {
            $rombelId = 'rombel-isi-test';
            $pdId = 'pd-el-test-uuid';
            $pembelajaranId = 'pemb-isi-test';
            $now = now();
            DB::table('rombongan_belajar')->insertOrIgnore(['rombongan_belajar_id' => $rombelId, 'nama' => 'Rombel Isi', 'created_at' => $now, 'updated_at' => $now]);
            DB::table('peserta_didik')->insertOrIgnore(['peserta_didik_id' => $pdId, 'nama' => 'Siswa E-Learning', 'rombongan_belajar_id' => $rombelId, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('pembelajaran')->insertOrIgnore(['pembelajaran_id' => $pembelajaranId, 'rombongan_belajar_id' => $rombelId, 'nama_mata_pelajaran' => 'Mapel Isi', 'created_at' => $now, 'updated_at' => $now]);

            $soal = json_encode([
                ['tipe' => 'pg', 'pertanyaan' => '2+2?', 'opsi' => ['3', '4'], 'kunci' => '4', 'bobot' => 2],
                ['tipe' => 'bs', 'pertanyaan' => 'Langit biru?', 'kunci' => 'benar', 'bobot' => 1],
                ['tipe' => 'isian', 'pertanyaan' => 'Ibu kota?', 'kunci' => 'Jakarta', 'bobot' => 1],
            ]);

            $res = $this->withSession($this->adminSession())->postJson(route('dashboard.elearning.store'), [
                'pembelajaran_id' => $pembelajaranId,
                'judul' => 'Materi Berkuis',
                'tipe' => 'teks',
                'status' => 'terbit',
                'isi_html' => '<p>Halo</p><script>alert(1)</script><img src=x onerror="evil()">',
                'soal' => $soal,
            ]);
            $res->assertStatus(200)->assertJson(['status' => 'success']);
            $id = $res->json('id');

            $materi = DB::table('learn_materi')->where('id', $id)->first();
            $this->assertStringNotContainsString('<script', (string) $materi->isi_html);
            $this->assertStringNotContainsString('onerror', (string) $materi->isi_html);
            $this->assertStringNotContainsString('<img', (string) $materi->isi_html);
            $this->assertSame(3, DB::table('learn_materi_soal')->where('materi_id', $id)->count());

            // Peserta didik tidak boleh menerima kunci.
            $show = $this->withSession($this->pdSession())->getJson(route('dashboard.elearning.show', ['id' => $id]));
            $show->assertStatus(200);
            $this->assertStringNotContainsString('"kunci"', $show->getContent());
        } finally {
            DB::rollBack();
        }
    }

    public function test_kuis_auto_nilai_peserta_didik(): void
    {
        DB::beginTransaction();
        try {
            $rombelId = 'rombel-kuis-test';
            $pdId = 'pd-el-test-uuid';
            $pembelajaranId = 'pemb-kuis-test';
            $now = now();

            DB::table('rombongan_belajar')->insertOrIgnore(['rombongan_belajar_id' => $rombelId, 'nama' => 'Rombel Kuis', 'created_at' => $now, 'updated_at' => $now]);
            DB::table('peserta_didik')->insertOrIgnore(['peserta_didik_id' => $pdId, 'nama' => 'Siswa E-Learning', 'rombongan_belajar_id' => $rombelId, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('pembelajaran')->insertOrIgnore(['pembelajaran_id' => $pembelajaranId, 'rombongan_belajar_id' => $rombelId, 'nama_mata_pelajaran' => 'Mapel Kuis', 'created_at' => $now, 'updated_at' => $now]);

            $materiId = DB::table('learn_materi')->insertGetId([
                'pembelajaran_id' => $pembelajaranId,
                'judul' => 'Materi Kuis',
                'tipe' => 'teks',
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $q1 = DB::table('learn_materi_soal')->insertGetId(['materi_id' => $materiId, 'tipe' => 'pg', 'pertanyaan' => '2+2?', 'opsi' => json_encode(['3', '4']), 'kunci' => '4', 'bobot' => 2, 'created_at' => $now, 'updated_at' => $now]);
            $q2 = DB::table('learn_materi_soal')->insertGetId(['materi_id' => $materiId, 'tipe' => 'bs', 'pertanyaan' => 'Langit biru?', 'kunci' => 'benar', 'bobot' => 1, 'created_at' => $now, 'updated_at' => $now]);

            // Benar keduanya → bobot 3/3 = 100.
            $res = $this->withSession($this->pdSession())->postJson(
                route('dashboard.elearning.kuis.submit', ['id' => $materiId]),
                ['jawaban' => [(string) $q1 => '4', (string) $q2 => 'benar']]
            );
            $res->assertStatus(200)->assertJson(['status' => 'success']);
            $row = DB::table('learn_materi_jawaban')->where('materi_id', $materiId)->where('peserta_didik_id', $pdId)->first();
            $this->assertNotNull($row);
            $this->assertEquals(100, (float) $row->skor);
            $this->assertSame(2, (int) $row->benar);

            // Jawab salah satu → skor turun.
            $this->withSession($this->pdSession())->postJson(
                route('dashboard.elearning.kuis.submit', ['id' => $materiId]),
                ['jawaban' => [(string) $q1 => '3', (string) $q2 => 'benar']]
            )->assertStatus(200);
            $row = DB::table('learn_materi_jawaban')->where('materi_id', $materiId)->where('peserta_didik_id', $pdId)->first();
            $this->assertEquals(round(1 / 3 * 100, 2), (float) $row->skor);

            // Guru dapat rekap hasil kuis materi ini.
            $rekap = $this->withSession($this->adminSession())->getJson(route('dashboard.elearning.kuis.hasil', ['id' => $materiId]));
            $rekap->assertStatus(200)->assertJson(['status' => 'success']);
            $this->assertSame(1, $rekap->json('data.dikerjakan'));
            $this->assertSame(2, $rekap->json('data.total_soal'));
            $this->assertSame('Siswa E-Learning', $rekap->json('data.rows.0.nama'));

            // Dasbor peserta didik memuat payload grafik nilai per mapel.
            $dasbor = $this->withSession($this->pdSession())->get(route('dashboard.elearning.index', ['tab' => 'dasbor']));
            $dasbor->assertStatus(200)->assertSee('nilaiMapelPayload', false);
        } finally {
            DB::rollBack();
        }
    }

    /* ============================ CBT ============================ */

    public function test_cbt_tab_dasbor_dan_periode_render(): void
    {
        $this->withSession($this->adminSession())
            ->get(route('dashboard.cbt.index'))->assertStatus(200)->assertSee('Dasbor', false);

        $this->withSession($this->adminSession())
            ->get(route('dashboard.cbt.index', ['tab' => 'periode']))
            ->assertStatus(200)->assertSee('Periode Asesmen', false);

        $this->withSession($this->pdSession())
            ->get(route('dashboard.cbt.index', ['tab' => 'dasbor']))
            ->assertStatus(200);
    }

    /**
     * Regresi: navigasi tab hilang di mode mobile karena hanya
     * `.periode-nav-desktop` yang ada. Pastikan `.periode-nav-mobile` +
     * `<select>` hadir di elearning & cbt.
     */
    public function test_tab_mobile_elearning_dan_cbt_tersedia(): void
    {
        foreach ([
            route('dashboard.elearning.index', ['tab' => 'materi']),
            route('dashboard.cbt.index', ['tab' => 'bank-soal']),
        ] as $url) {
            $this->withSession($this->adminSession())
                ->get($url)
                ->assertStatus(200)
                ->assertSee('periode-nav-mobile', false)
                ->assertSee('periode-mobile-select', false);
        }
    }

    public function test_cbt_peserta_didik_tidak_bisa_baca_bank_soal(): void
    {
        DB::beginTransaction();
        try {
            $pembelajaranId = DB::table('pembelajaran')->value('pembelajaran_id') ?? 'x';
            $soalId = DB::table('ujian_soal')->insertGetId([
                'pembelajaran_id' => $pembelajaranId,
                'tipe' => 'pg',
                'pertanyaan' => 'Rahasia?',
                'opsi' => json_encode(['A', 'B']),
                'kunci' => 'A',
                'bobot' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // PD ditolak membaca detail soal (memuat kunci).
            $this->withSession($this->pdSession())
                ->getJson(route('dashboard.cbt.soal.show', ['id' => $soalId]))
                ->assertStatus(403);

            // PD tetap boleh membuka dasbor (read-only jadwal).
            $this->withSession($this->pdSession())
                ->get(route('dashboard.cbt.index', ['tab' => 'bank-soal']))
                ->assertStatus(200)
                ->assertDontSee('Rahasia?', false);
        } finally {
            DB::rollBack();
        }
    }

    public function test_cbt_periode_lifecycle(): void
    {
        DB::beginTransaction();
        try {
            $res = $this->withSession($this->adminSession())->postJson(route('dashboard.cbt.periode.store'), [
                'nama' => 'PTS Ganjil Uji',
                'tipe' => 'uts',
                'tahun_ajaran' => '2026/2027',
                'semester' => 'ganjil',
                'tanggal_mulai' => '2026-09-01',
                'tanggal_selesai' => '2026-09-07',
                'status' => 'aktif',
            ]);
            $res->assertStatus(200)->assertJson(['status' => 'success']);
            $id = $res->json('id');
            $row = DB::table('cbt_periode_asesmen')->where('id', $id)->first();
            $this->assertSame('aktif', $row->status);

            // Hanya satu periode aktif: buat periode aktif kedua → yang lama jadi selesai.
            $res2 = $this->withSession($this->adminSession())->postJson(route('dashboard.cbt.periode.store'), [
                'nama' => 'PAS Ganjil Uji', 'tipe' => 'uas', 'status' => 'aktif',
            ]);
            $res2->assertStatus(200);
            $this->assertSame('selesai', DB::table('cbt_periode_asesmen')->where('id', $id)->value('status'));

            // Hapus periode tanpa ujian tertaut → sukses.
            $this->withSession($this->adminSession())
                ->deleteJson(route('dashboard.cbt.periode.destroy', ['id' => $id]))
                ->assertStatus(200)->assertJson(['status' => 'success']);
            $this->assertNull(DB::table('cbt_periode_asesmen')->where('id', $id)->first());
        } finally {
            DB::rollBack();
        }
    }

    public function test_cbt_bulk_soal_tersimpan(): void
    {
        DB::beginTransaction();
        try {
            $pembelajaranId = DB::table('pembelajaran')->value('pembelajaran_id') ?? 'x';
            $soal = json_encode([
                ['tipe' => 'pg', 'pertanyaan' => 'Bulk 1?', 'opsi' => ['A1', 'B1'], 'kunci' => 'A', 'bobot' => 2, 'tingkat' => 'mudah'],
                ['tipe' => 'benar_salah', 'pertanyaan' => 'Bulk 2?', 'kunci' => 'Benar'],
                ['tipe' => 'isian', 'pertanyaan' => 'Bulk 3?', 'kunci' => 'kunci teks'],
                ['tipe' => 'pg', 'pertanyaan' => '', 'opsi' => ['x']], // dilewati (tanpa pertanyaan)
            ]);
            $res = $this->withSession($this->adminSession())->postJson(route('dashboard.cbt.soal.bulk'), [
                'pembelajaran_id' => $pembelajaranId,
                'soal' => $soal,
            ]);
            $res->assertStatus(200)->assertJson(['status' => 'success', 'tersimpan' => 3, 'dilewati' => 1]);

            // Kunci PG huruf A → tersimpan sebagai teks opsi "A1".
            $pg = DB::table('ujian_soal')->where('pembelajaran_id', $pembelajaranId)->where('pertanyaan', 'Bulk 1?')->first();
            $this->assertSame('A1', $pg->kunci);
        } finally {
            DB::rollBack();
        }
    }

    public function test_cbt_ujian_tertaut_periode_dan_blokir_hapus(): void
    {
        DB::beginTransaction();
        try {
            $rombelId = 'rombel-cbt-periode';
            $pembelajaranId = 'pemb-cbt-periode';
            $now = now();
            DB::table('rombongan_belajar')->insertOrIgnore(['rombongan_belajar_id' => $rombelId, 'nama' => 'Rombel CBT', 'created_at' => $now, 'updated_at' => $now]);
            DB::table('pembelajaran')->insertOrIgnore(['pembelajaran_id' => $pembelajaranId, 'rombongan_belajar_id' => $rombelId, 'nama_mata_pelajaran' => 'Mapel CBT', 'created_at' => $now, 'updated_at' => $now]);

            $periodeId = $this->withSession($this->adminSession())->postJson(route('dashboard.cbt.periode.store'), [
                'nama' => 'Periode Ujian Uji', 'tipe' => 'uas', 'status' => 'aktif',
            ])->json('id');

            $res = $this->withSession($this->adminSession())->postJson(route('dashboard.cbt.ujian.store'), [
                'pembelajaran_id' => $pembelajaranId,
                'periode_id' => $periodeId,
                'judul' => 'UAS Mapel CBT',
                'tipe' => 'uas',
                'durasi_menit' => 90,
                'kkm' => 75,
                'status' => 'terbit',
            ]);
            $res->assertStatus(200)->assertJson(['status' => 'success']);
            $ujianId = $res->json('id');
            $this->assertSame($periodeId, (int) DB::table('ujian')->where('id', $ujianId)->value('periode_id'));

            // Hapus periode yang masih menaungi ujian → ditolak.
            $this->withSession($this->adminSession())
                ->deleteJson(route('dashboard.cbt.periode.destroy', ['id' => $periodeId]))
                ->assertStatus(422);
        } finally {
            DB::rollBack();
        }
    }

    public function test_cbt_sesi_ujian_lifecycle(): void
    {
        DB::beginTransaction();
        try {
            $rombelId = 'rombel-cbt-sesi';
            $pembelajaranId = 'pemb-cbt-sesi';
            $now = now();
            DB::table('rombongan_belajar')->insertOrIgnore(['rombongan_belajar_id' => $rombelId, 'nama' => 'Rombel Sesi', 'created_at' => $now, 'updated_at' => $now]);
            DB::table('pembelajaran')->insertOrIgnore(['pembelajaran_id' => $pembelajaranId, 'rombongan_belajar_id' => $rombelId, 'nama_mata_pelajaran' => 'Mapel Sesi', 'created_at' => $now, 'updated_at' => $now]);

            $periodeId = $this->withSession($this->adminSession())->postJson(route('dashboard.cbt.periode.store'), [
                'nama' => 'Periode Sesi Uji', 'tipe' => 'uas', 'status' => 'aktif',
            ])->json('id');

            $res = $this->withSession($this->adminSession())->postJson(route('dashboard.cbt.sesi.store'), [
                'periode_id' => $periodeId,
                'pembelajaran_id' => $pembelajaranId,
                'tanggal' => '2026-12-01',
                'jam_mulai' => '08:00',
                'jam_selesai' => '09:30',
                'kuota' => 30,
                'ruang' => 'Lab 1',
            ]);
            $res->assertStatus(200)->assertJson(['status' => 'success']);
            $sesiId = $res->json('id');
            $row = DB::table('cbt_sesi_ujian')->where('id', $sesiId)->first();
            $this->assertSame('2026-12-01', $row->tanggal);
            $this->assertSame(30, (int) $row->kuota);

            // Detail sesi.
            $this->withSession($this->adminSession())
                ->getJson(route('dashboard.cbt.sesi.show', ['id' => $sesiId]))
                ->assertStatus(200)->assertJson(['status' => 'success', 'data' => ['ruang' => 'Lab 1']]);

            // Update sesi.
            $this->withSession($this->adminSession())->putJson(route('dashboard.cbt.sesi.update', ['id' => $sesiId]), [
                'periode_id' => $periodeId,
                'pembelajaran_id' => $pembelajaranId,
                'tanggal' => '2026-12-02',
                'jam_mulai' => '10:00',
                'jam_selesai' => '11:00',
                'kuota' => 0,
                'ruang' => 'Lab 2',
            ])->assertStatus(200)->assertJson(['status' => 'success']);
            $this->assertSame('Lab 2', DB::table('cbt_sesi_ujian')->where('id', $sesiId)->value('ruang'));

            // Tab sesi render.
            $this->withSession($this->adminSession())
                ->get(route('dashboard.cbt.index', ['tab' => 'sesi']))
                ->assertStatus(200)->assertSee('Sesi Ujian', false);

            // Hapus sesi.
            $this->withSession($this->adminSession())
                ->deleteJson(route('dashboard.cbt.sesi.destroy', ['id' => $sesiId]))
                ->assertStatus(200)->assertJson(['status' => 'success']);
            $this->assertNull(DB::table('cbt_sesi_ujian')->where('id', $sesiId)->first());
        } finally {
            DB::rollBack();
        }
    }
}
