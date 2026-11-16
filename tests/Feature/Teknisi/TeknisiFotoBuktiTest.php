<?php

namespace Tests\Feature\Teknisi;

use App\Support\Table;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Bukti foto modul Teknisi & IT: unggah, ganti, dan hapus berkas.
 *
 * Berjalan di database operasional: baris baru dihapus di tearDown
 * berdasarkan snapshot id.
 */
class TeknisiFotoBuktiTest extends TestCase
{
    /** @var array<int, int> */
    private array $logIds = [];

    /** @var array<int, int> */
    private array $pmIds = [];

    /** @var array<int, int> */
    private array $pelaksanaanIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        if (Table::exists('teknisi_log_harian')) {
            $this->logIds = DB::table('teknisi_log_harian')->pluck('id')->all();
        }
        if (Table::exists('teknisi_pemeliharaan')) {
            $this->pmIds = DB::table('teknisi_pemeliharaan')->pluck('id')->all();
        }
    }

    protected function tearDown(): void
    {
        if ($this->logIds !== [] || Table::exists('teknisi_log_harian')) {
            DB::table('teknisi_log_harian')->when(!empty($this->logIds), fn ($q) => $q->whereNotIn('id', $this->logIds))
                ->where('subjek', 'like', 'Uji Foto%')->delete();
        }
        if ($this->pmIds !== [] || Table::exists('teknisi_pemeliharaan')) {
            DB::table('teknisi_pemeliharaan')->when(!empty($this->pmIds), fn ($q) => $q->whereNotIn('id', $this->pmIds))
                ->where('nama_kegiatan', 'like', 'Uji Foto%')->delete();
        }
        if (Table::exists('teknisi_pelaksanaan_harian') && !empty($this->pelaksanaanIds)) {
            DB::table('teknisi_pelaksanaan_harian')->whereIn('id', $this->pelaksanaanIds)->delete();
        }

        parent::tearDown();
    }

    /** @return array{user: array<string, mixed>} */
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
            'ptk_id'       => $admin->ptk_id ?? null,
        ]];
    }

    private function gambar(string $nama = 'bukti.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($nama, 40, 40);
    }

    public function test_simpan_ganti_dan_hapus_bukti_foto_modul_generik(): void
    {
        $sesi = $this->adminSession();
        $subjek = 'Uji Foto ' . uniqid();

        $this->withSession($sesi)
            ->postJson(route('dashboard.teknisi.modul.store', ['modul' => 'inspeksi-utilitas']), [
                'tanggal'  => now()->toDateString(),
                'subjek'   => $subjek,
                'kategori' => 'panel_listrik',
                'kondisi'  => 'baik',
                'foto'     => $this->gambar(),
            ])
            ->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $row = DB::table('teknisi_log_harian')->where('subjek', $subjek)->first();
        $this->assertNotNull($row, 'Baris hasil simpan harus ada.');
        $this->assertNotEmpty($row->foto, 'Kolom foto harus terisi.');
        Storage::disk('public')->assertExists($row->foto);
        $fotoLama = $row->foto;

        // Ganti foto: berkas lama dihapus, kolom menunjuk berkas baru.
        $this->withSession($sesi)
            ->putJson(route('dashboard.teknisi.modul.update', ['modul' => 'inspeksi-utilitas', 'id' => $row->id]), [
                'tanggal'  => now()->toDateString(),
                'subjek'   => $subjek,
                'kategori' => 'panel_listrik',
                'kondisi'  => 'baik',
                'foto'     => $this->gambar('ganti.jpg'),
            ])
            ->assertStatus(200);

        $baru = DB::table('teknisi_log_harian')->where('id', $row->id)->value('foto');
        $this->assertNotSame($fotoLama, $baru, 'Foto harus berganti.');
        Storage::disk('public')->assertExists($baru);
        Storage::disk('public')->assertMissing($fotoLama);

        // Hapus baris: berkas foto ikut terhapus.
        $this->withSession($sesi)
            ->deleteJson(route('dashboard.teknisi.modul.destroy', ['modul' => 'inspeksi-utilitas', 'id' => $row->id]))
            ->assertStatus(200);

        Storage::disk('public')->assertMissing($baru);
    }

    public function test_pemeliharaan_preventif_menyimpan_bukti_foto(): void
    {
        $sesi = $this->adminSession();
        $nama = 'Uji Foto PM ' . uniqid();

        $this->withSession($sesi)
            ->post(route('dashboard.teknisi.pemeliharaan.store'), [
                'nama_kegiatan' => $nama,
                'kategori'      => 'kelistrikan',
                'lokasi_aset'   => 'Ruang Uji',
                'frekuensi'     => 'bulanan',
                'tgl_jadwal'    => now()->toDateString(),
                'biaya'         => 0,
                'foto'          => $this->gambar(),
            ])
            ->assertRedirect();

        $row = DB::table('teknisi_pemeliharaan')->where('nama_kegiatan', $nama)->first();
        $this->assertNotNull($row);
        $this->assertNotEmpty($row->foto);
        Storage::disk('public')->assertExists($row->foto);
    }

    public function test_upload_foto_menolak_berkas_bukan_gambar(): void
    {
        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.teknisi.modul.store', ['modul' => 'inspeksi-utilitas']), [
                'tanggal'  => now()->toDateString(),
                'subjek'   => 'Uji Foto ' . uniqid(),
                'kategori' => 'panel_listrik',
                'kondisi'  => 'baik',
                'foto'     => UploadedFile::fake()->create('dokumen.pdf', 20, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('foto');
    }

    public function test_tandai_selesai_pelaksanaan_menyimpan_bukti_foto(): void
    {
        $sesi = $this->adminSession();

        $id = DB::table('teknisi_pelaksanaan_harian')->insertGetId([
            'nomor_pelaksanaan' => 'PLK-UJI-' . uniqid(),
            'tanggal'           => now()->toDateString(),
            'shift'             => 'pagi',
            'area'              => 'Area Uji Foto',
            'status'            => 'belum',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
        $this->pelaksanaanIds[] = $id;

        $this->withSession($sesi)
            ->putJson(route('dashboard.teknisi.pelaksanaan-harian.status', $id), [
                'status' => 'selesai',
                'foto'   => $this->gambar(),
            ])
            ->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $row = DB::table('teknisi_pelaksanaan_harian')->where('id', $id)->first();
        $this->assertSame('selesai', $row->status);
        $this->assertNotEmpty($row->foto, 'Foto bukti harus tersimpan saat tandai selesai.');
        Storage::disk('public')->assertExists($row->foto);
    }
}
