<?php

namespace Tests\Feature;

use App\Models\PerpusAnggota;
use App\Services\PerpustakaanEbookService;
use App\Services\PerpustakaanMemberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Uji fitur Penarikan E-Book Resmi Kemendikbud & Kejuruan SMK,
 * input mandiri, pembaca daring, serta reward literasi digital.
 */
class PerpusEbookTest extends TestCase
{
    private function adminSession(): array
    {
        $admin = DB::table('pengguna')->where('peran_id_str', 'Administrator')->first();

        return ['user' => [
            'id'           => $admin->pengguna_id,
            'pengguna_id'  => $admin->pengguna_id,
            'name'         => $admin->nama,
            'nama'         => $admin->nama,
            'username'     => $admin->username,
            'role'         => 'admin',
            'peran_id_str' => $admin->peran_id_str,
        ]];
    }

    public function test_katalog_master_memuat_ebook_resmi_dari_berbagai_sumber_terbuka(): void
    {
        $master = PerpustakaanEbookService::katalogMaster();

        $this->assertNotEmpty($master);
        $this->assertGreaterThanOrEqual(25, count($master));

        $sumberList = array_unique(array_column($master, 'sumber_ebook'));
        $this->assertContains('sibi', $sumberList);
        $this->assertContains('ditjen_smk', $sumberList);
        $this->assertContains('kemenag', $sumberList);
        $this->assertContains('badan_bahasa', $sumberList);
        $this->assertContains('perpusnas', $sumberList);
        $this->assertContains('bse', $sumberList);
    }

    public function test_pencarian_master_ebook_menyaring_berdasarkan_keyword_dan_sumber(): void
    {
        $hasilMatematika = PerpustakaanEbookService::cari(['q' => 'Matematika']);
        $this->assertNotEmpty($hasilMatematika);
        foreach ($hasilMatematika as $item) {
            $this->assertStringContainsStringIgnoringCase('Matematika', $item['judul']);
        }

        $hasilKemenag = PerpustakaanEbookService::cari(['sumber' => 'kemenag']);
        $this->assertNotEmpty($hasilKemenag);
        foreach ($hasilKemenag as $item) {
            $this->assertSame('kemenag', $item['sumber_ebook']);
        }

        $hasilBadanBahasa = PerpustakaanEbookService::cari(['sumber' => 'badan_bahasa']);
        $this->assertNotEmpty($hasilBadanBahasa);
        foreach ($hasilBadanBahasa as $item) {
            $this->assertSame('badan_bahasa', $item['sumber_ebook']);
        }
    }

    public function test_impor_satu_ebook_ke_katalog_berhasil_dan_idempoten(): void
    {
        DB::beginTransaction();

        try {
            $master = PerpustakaanEbookService::katalogMaster();
            $target = $master[0];

            $id = PerpustakaanEbookService::imporKeKatalog($target);
            $this->assertGreaterThan(0, $id);

            $buku = DB::table('perpus_koleksi_buku')->where('id', $id)->first();
            $this->assertNotNull($buku);
            $this->assertSame($target['kode_buku'], $buku->kode_buku);
            $this->assertSame('ebook', $buku->format);
            $this->assertSame(999, (int) $buku->jumlah_eksemplar);
            $this->assertSame('Rak Digital', $buku->lokasi_rak);

            // Uji idempoten: impor ulang buku yang sama tidak membuat baris duplikat
            $idUlang = PerpustakaanEbookService::imporKeKatalog($target);
            $this->assertSame($id, $idUlang);
        } finally {
            DB::rollBack();
        }
    }

    public function test_impor_banyak_ebook_ke_katalog(): void
    {
        DB::beginTransaction();

        try {
            $master = PerpustakaanEbookService::katalogMaster();
            $kodeDipilih = array_slice(array_column($master, 'kode_buku'), 0, 3);

            $hasil = PerpustakaanEbookService::imporBanyak($kodeDipilih);
            $this->assertSame(3, $hasil['sukses']);
            $this->assertSame(0, $hasil['dilewati']);

            $count = DB::table('perpus_koleksi_buku')->whereIn('kode_buku', $kodeDipilih)->count();
            $this->assertSame(3, $count);
        } finally {
            DB::rollBack();
        }
    }

    public function test_dashboard_penarikan_ebook_dapat_diakses_oleh_admin(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.perpustakaan.ebook.index'));

        $res->assertOk();
        $res->assertSee('Tarik E-Book Gratis &amp; Terbuka', false);
        $res->assertSee('Daftar Katalog');
        $res->assertSee('Tarik E-Book Gratis');
    }

    public function test_aksi_tarik_satu_dari_dashboard(): void
    {
        DB::beginTransaction();

        try {
            $master = PerpustakaanEbookService::katalogMaster();
            $target = $master[1];

            $res = $this->withSession($this->adminSession())
                ->post(route('dashboard.perpustakaan.ebook.tarik'), [
                    'kode_buku' => $target['kode_buku'],
                ]);

            $res->assertRedirect();
            $res->assertSessionHas('success');

            $ada = DB::table('perpus_koleksi_buku')->where('kode_buku', $target['kode_buku'])->exists();
            $this->assertTrue($ada);
        } finally {
            DB::rollBack();
        }
    }

    public function test_aksi_input_ebook_mandiri_dari_dashboard(): void
    {
        DB::beginTransaction();

        try {
            $res = $this->withSession($this->adminSession())
                ->post(route('dashboard.perpustakaan.ebook.manual'), [
                    'judul'       => 'Modul Mandiri Teknik Komputer SMK Uji',
                    'penulis'     => 'Guru Pengampu TJKT',
                    'kategori'    => 'Informatika & TIK',
                    'kelas'       => '10',
                    'jurusan'     => 'TJKT',
                    'tautan_baca' => 'https://example.com/modul-komputer.pdf',
                    'sinopsis'    => 'Modul praktikum mandiri.',
                ]);

            $res->assertRedirect();
            $res->assertSessionHas('success');

            $buku = DB::table('perpus_koleksi_buku')
                ->where('judul', 'Modul Mandiri Teknik Komputer SMK Uji')
                ->first();

            $this->assertNotNull($buku);
            $this->assertSame('ebook', $buku->format);
            $this->assertSame('mandiri', $buku->sumber_ebook);
            $this->assertSame('https://example.com/modul-komputer.pdf', $buku->tautan_baca);
        } finally {
            DB::rollBack();
        }
    }

    public function test_reader_online_menambah_counter_baca_dan_poin_anggota(): void
    {
        DB::beginTransaction();

        try {
            // 1. Siapkan e-book
            $bukuId = DB::table('perpus_koleksi_buku')->insertGetId([
                'kode_buku'          => 'EB-TEST-READER-01',
                'judul'              => 'E-Book Uji Pembaca Online',
                'format'             => 'ebook',
                'tautan_baca'        => 'https://buku.kemdikbud.go.id/katalog/uji',
                'jumlah_eksemplar'   => 999,
                'eksemplar_tersedia' => 999,
                'kategori'           => 'Buku Pelajaran',
                'total_dibaca'       => 0,
                'harga_perolehan'    => 0,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            // 2. Siapkan anggota perpustakaan
            $anggota = PerpusAnggota::create([
                'nomor_anggota' => 'ANG-EB-TEST-99',
                'token_kartu'   => \Illuminate\Support\Str::random(32),
                'tipe'          => 'siswa',
                'nama'          => 'Siswa Pembaca Digital',
                'poin'          => 10,
                'is_active'     => true,
            ]);

            // 3. Akses route baca online dengan sesi anggota
            $res = $this->withSession(['perpus_anggota_id' => $anggota->id])
                ->get(route('perpustakaan.buku.baca', $bukuId));

            $res->assertOk();
            $res->assertSee('Membaca: E-Book Uji Pembaca Online');
            $res->assertSee('+3 Poin Literasi');

            // 4. Verifikasi counter total_dibaca bertambah
            $bukuAfter = DB::table('perpus_koleksi_buku')->where('id', $bukuId)->first();
            $this->assertSame(1, (int) $bukuAfter->total_dibaca);

            // 5. Verifikasi poin literasi bertambah 3 (10 + 3 = 13)
            $anggotaAfter = PerpusAnggota::find($anggota->id);
            $this->assertSame(13, (int) $anggotaAfter->poin);

            // 6. Verifikasi kunjungan digital tercatat
            $kunjungan = DB::table('perpus_kunjungan')
                ->where('pengunjung_id', 'ANG-EB-TEST-99')
                ->whereDate('tanggal', now()->toDateString())
                ->first();
            $this->assertNotNull($kunjungan);
            $this->assertStringContainsString('Membaca E-Book', $kunjungan->keperluan);
        } finally {
            DB::rollBack();
        }
    }

    public function test_katalog_publik_dapat_memfilter_format_ebook(): void
    {
        DB::beginTransaction();

        try {
            $bukuId = DB::table('perpus_koleksi_buku')->insertGetId([
                'kode_buku'          => 'EB-FILTER-01',
                'judul'              => 'E-Book Khusus Filter Digital Test',
                'format'             => 'ebook',
                'tautan_baca'        => 'https://example.com/test',
                'jumlah_eksemplar'   => 999,
                'eksemplar_tersedia' => 999,
                'kategori'           => 'Kejuruan Vokasi',
                'harga_perolehan'    => 0,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            $res = $this->get(route('perpustakaan.katalog', ['format' => 'ebook', 'q' => 'Digital Test']));
            $res->assertOk();
            $res->assertSee('E-Book Khusus Filter Digital Test');
            $res->assertSee('E-BOOK');
            $res->assertSee('Baca Online');
        } finally {
            DB::rollBack();
        }
    }

    public function test_cover_theme_menghasilkan_gradient_dan_ikon_sesuai_kategori(): void
    {
        $themeAgama = PerpustakaanEbookService::coverTheme('Pendidikan Agama');
        $this->assertStringContainsString('#065f46', $themeAgama['gradient']);
        $this->assertSame('fa-book-quran', $themeAgama['icon']);

        $themeTik = PerpustakaanEbookService::coverTheme('Informatika & TIK');
        $this->assertStringContainsString('#1e3a8a', $themeTik['gradient']);
        $this->assertSame('fa-laptop-code', $themeTik['icon']);

        $themeOtomotif = PerpustakaanEbookService::coverTheme('Teknik Otomotif');
        $this->assertSame('fa-car-side', $themeOtomotif['icon']);
    }

    public function test_katalog_master_tidak_memuat_host_mati(): void
    {
        $master = PerpustakaanEbookService::katalogMaster();
        foreach ($master as $item) {
            $url = $item['tautan_baca'] ?? '';
            $this->assertStringNotContainsString('repositori.kemdikbud.go.id', $url);
            $this->assertStringNotContainsString('budi.kemdikbud.go.id', $url);
            $this->assertStringNotContainsString('gln.kemdikbud.go.id', $url);
            $this->assertStringNotContainsString('khazanah.perpusnas.go.id', $url);
        }
    }

    public function test_unduh_berkas_pdf_dan_simpan_di_server(): void
    {
        Storage::fake('public');
        Http::fake([
            'https://example.com/buku.pdf' => Http::response("%PDF-1.4\n%dummy pdf content", 200, [
                'Content-Type' => 'application/pdf',
            ]),
        ]);

        $path = PerpustakaanEbookService::unduhBerkasPdf('https://example.com/buku.pdf', 'EB-TEST-PDF-01');

        $this->assertNotNull($path);
        $this->assertSame('ebooks/eb-test-pdf-01.pdf', $path);
        Storage::disk('public')->assertExists('ebooks/eb-test-pdf-01.pdf');
    }

    public function test_aksi_tarik_dengan_opsi_unduh_pdf_menyimpan_file_ke_database(): void
    {
        Storage::fake('public');
        Http::fake([
            '*' => Http::response("%PDF-1.4\n%dummy pdf content", 200, [
                'Content-Type' => 'application/pdf',
            ]),
        ]);

        DB::beginTransaction();

        try {
            $master = PerpustakaanEbookService::katalogMaster();
            $target = $master[0];

            $res = $this->withSession($this->adminSession())
                ->post(route('dashboard.perpustakaan.ebook.tarik'), [
                    'kode_buku' => $target['kode_buku'],
                    'unduh_pdf' => '1',
                ]);

            $res->assertRedirect();
            $res->assertSessionHas('success');

            $buku = DB::table('perpus_koleksi_buku')->where('kode_buku', $target['kode_buku'])->first();
            $this->assertNotNull($buku);
            $this->assertNotNull($buku->file_ebook);
            Storage::disk('public')->assertExists($buku->file_ebook);
        } finally {
            DB::rollBack();
        }
    }

    public function test_endpoint_unduh_pdf_koleksi_dari_dashboard(): void
    {
        Storage::fake('public');
        Http::fake([
            '*' => Http::response("%PDF-1.4\n%dummy pdf content", 200, [
                'Content-Type' => 'application/pdf',
            ]),
        ]);

        DB::beginTransaction();

        try {
            $master = PerpustakaanEbookService::katalogMaster();
            $target = $master[0];

            // Masukkan tanpa file_ebook
            $bukuId = PerpustakaanEbookService::imporKeKatalog($target, unduhPdf: false);
            DB::table('perpus_koleksi_buku')->where('id', $bukuId)->update(['file_ebook' => null]);

            $res = $this->withSession($this->adminSession())
                ->post(route('dashboard.perpustakaan.ebook.unduh-pdf', $bukuId));

            $res->assertRedirect();
            $res->assertSessionHas('success');

            $bukuAfter = DB::table('perpus_koleksi_buku')->where('id', $bukuId)->first();
            $this->assertNotNull($bukuAfter->file_ebook);
            Storage::disk('public')->assertExists($bukuAfter->file_ebook);
        } finally {
            DB::rollBack();
        }
    }

    public function test_reader_dan_detail_prioritaskan_berkas_pdf_lokal(): void
    {
        DB::beginTransaction();

        try {
            $bukuId = DB::table('perpus_koleksi_buku')->insertGetId([
                'kode_buku'          => 'EB-LOCAL-TEST-01',
                'judul'              => 'E-Book Offline PDF Server',
                'format'             => 'ebook',
                'tautan_baca'        => 'https://example.com/fallback-link',
                'file_ebook'         => 'ebooks/eb-local-test.pdf',
                'jumlah_eksemplar'   => 999,
                'eksemplar_tersedia' => 999,
                'kategori'           => 'Buku Pelajaran',
                'total_dibaca'       => 0,
                'harga_perolehan'    => 0,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            // Cek Reader
            $resBaca = $this->get(route('perpustakaan.buku.baca', $bukuId));
            $resBaca->assertOk();
            $resBaca->assertSee('storage/ebooks/eb-local-test.pdf', false);
            $resBaca->assertSee('Offline Server');
            $resBaca->assertSee('Unduh PDF');

            // Cek Detail
            $resDetail = $this->get(route('perpustakaan.buku.detail', $bukuId));
            $resDetail->assertOk();
            $resDetail->assertSee('PDF Lokal Server');
            $resDetail->assertSee('Unduh PDF Offline');
        } finally {
            DB::rollBack();
        }
    }
}
