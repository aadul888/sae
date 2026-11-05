<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Persuratan;
use App\Models\User;
use App\Models\RolePermission;
use App\Services\SystemNotificationService;
use Illuminate\Support\Facades\DB;

class PersuratanDutyModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RolePermission::syncAvailablePermissions();
    }

    public function test_guest_cannot_access_persuratan(): void
    {
        $response = $this->get(route('dashboard.persuratan.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_access_persuratan_dashboard(): void
    {
        $admin = [
            'id' => 'admin-test-01',
            'pengguna_id' => 'admin-test-01',
            'username' => 'admin',
            'nama' => 'Administrator Sistem',
            'role' => 'admin',
        ];

        $response = $this->withSession(['user' => $admin])
            ->get(route('dashboard.persuratan.index'));

        $response->assertStatus(200);
        $response->assertSee('Persuratan &amp; Arsip Digital', false);
    }

    public function test_tendik_can_access_persuratan(): void
    {
        $tendik = [
            'id' => 'tendik-test-01',
            'pengguna_id' => 'tendik-test-01',
            'username' => 'staf.arsip',
            'nama' => 'Staf Tata Usaha & Arsip',
            'role' => 'tendik',
            'ptk_id' => 'ptk-tendik-01',
        ];

        $response = $this->withSession(['user' => $tendik])
            ->get(route('dashboard.persuratan.index'));

        $response->assertStatus(200);
        $response->assertSee('Persuratan &amp; Arsip Digital', false);
    }

    public function test_crud_lifecycle_persuratan(): void
    {
        $admin = [
            'id' => 'admin-crud-01',
            'pengguna_id' => 'admin-crud-01',
            'username' => 'admin',
            'nama' => 'Super Admin',
            'role' => 'admin',
        ];

        // 1. Store (Create)
        $payload = [
            'nomor_surat'     => 'TEST/001/SMK/IX/2026',
            'jenis_surat'     => 'masuk',
            'perihal'         => 'Surat Uji Coba Integrasi Modul Tendik',
            'pengirim_asal'   => 'Dinas Pendidikan Jawa Barat',
            'tujuan_penerima' => 'Kepala Sekolah',
            'tanggal_surat'   => '2026-09-19',
            'status'          => 'menunggu_disposisi',
            'keterangan'      => 'Disposisikan segera untuk pengujian.',
        ];

        $storeRes = $this->withSession(['user' => $admin])
            ->postJson(route('dashboard.persuratan.store'), $payload);

        $storeRes->assertStatus(200);
        $storeRes->assertJson(['status' => 'success']);

        $item = Persuratan::where('nomor_surat', 'TEST/001/SMK/IX/2026')->first();
        $this->assertNotNull($item);
        $this->assertEquals('Surat Uji Coba Integrasi Modul Tendik', $item->perihal);

        // 2. Show (Read)
        $showRes = $this->withSession(['user' => $admin])
            ->getJson(route('dashboard.persuratan.show', $item->id));

        $showRes->assertStatus(200);
        $showRes->assertJsonPath('data.nomor_surat', 'TEST/001/SMK/IX/2026');

        // 3. Update
        $updateRes = $this->withSession(['user' => $admin])
            ->putJson(route('dashboard.persuratan.update', $item->id), array_merge($payload, [
                'status' => 'diproses',
                'perihal' => 'Surat Uji Coba (Telah Diproses)',
            ]));

        $updateRes->assertStatus(200);
        $item->refresh();
        $this->assertEquals('diproses', $item->status);
        $this->assertEquals('Surat Uji Coba (Telah Diproses)', $item->perihal);

        // 4. Destroy (Delete)
        $deleteRes = $this->withSession(['user' => $admin])
            ->deleteJson(route('dashboard.persuratan.destroy', $item->id));

        $deleteRes->assertStatus(200);
        $this->assertNull(Persuratan::find($item->id));
    }

    public function test_system_notification_service_detects_pending_surat(): void
    {
        $dutyId = DB::table('ref_tugas_tambahan')->where('kode', 'STAF_PERSURATAN')->value('id');
        DB::table('ptk_tugas_tambahan')->insert([
            'user_id' => 'tendik-notif-01',
            'tugas_tambahan_id' => $dutyId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Pastikan ada setidaknya 1 surat menunggu_disposisi
        Persuratan::create([
            'nomor_surat'     => 'NOTIF/TEST/001',
            'jenis_surat'     => 'masuk',
            'perihal'         => 'Surat Notifikasi Pengujian',
            'pengirim_asal'   => 'Kemdikbud',
            'tujuan_penerima' => 'Kepala Sekolah',
            'tanggal_surat'   => '2026-09-19',
            'status'          => 'menunggu_disposisi',
        ]);

        $tendik = [
            'id' => 'tendik-notif-01',
            'pengguna_id' => 'tendik-notif-01',
            'username' => 'staf.arsiparis',
            'nama' => 'Staf Arsiparis',
            'role' => 'tendik',
        ];

        $notifData = SystemNotificationService::getSystemNotifications($tendik);
        $hasPendingSurat = collect($notifData['items'])->contains(function ($item) {
            return ($item->kategori ?? '') === 'persuratan';
        });

        $this->assertTrue($hasPendingSurat, 'SystemNotificationService harus mendeteksi pending surat untuk tendik.');

        // Clean up
        Persuratan::where('nomor_surat', 'NOTIF/TEST/001')->delete();
        DB::table('ptk_tugas_tambahan')->where('user_id', 'tendik-notif-01')->delete();
    }

    public function test_can_access_buku_agenda_and_ekspedisi(): void
    {
        $admin = [
            'id' => 'admin-test-01',
            'pengguna_id' => 'admin-test-01',
            'username' => 'admin',
            'nama' => 'Administrator Sistem',
            'role' => 'admin',
        ];

        // 1. Tab Utama Surat Keluar (Mengkoordinir semua surat keluar & nomor terakhir)
        $resKeluarTab = $this->withSession(['user' => $admin])
            ->get(route('dashboard.persuratan.keluar.index'));
        $resKeluarTab->assertStatus(200);
        $resKeluarTab->assertSee('Nomor Surat Keluar Terakhir', false);

        // 2. Tab Ekspedisi Surat Keluar
        $resEkspedisiTab = $this->withSession(['user' => $admin])
            ->get(route('dashboard.persuratan.keluar.index', ['tab' => 'ekspedisi']));
        $resEkspedisiTab->assertStatus(200);
        $resEkspedisiTab->assertSee('Buku Ekspedisi', false);

        // 3. Tab Autocrat Template
        $resTemplateTab = $this->withSession(['user' => $admin])
            ->get(route('dashboard.persuratan.keluar.index', ['tab' => 'template']));
        $resTemplateTab->assertStatus(200);
        $resTemplateTab->assertSee('Autocrat', false);

        // 4. Cetak Buku Agenda Surat Masuk
        $resAgendaMasuk = $this->withSession(['user' => $admin])
            ->get(route('dashboard.persuratan.masuk.agenda.cetak'));
        $resAgendaMasuk->assertStatus(200);
        $resAgendaMasuk->assertSee('BUKU AGENDA SURAT MASUK', false);
        // Kolom banyak → kertas mendatar.
        $resAgendaMasuk->assertSee('size: A4 landscape', false);
        $resAgendaMasuk->assertSee('RINGKASAN &amp; REKAPITULASI SURAT MASUK', false);

        // 5. Cetak Buku Agenda Surat Keluar
        $resAgendaKeluar = $this->withSession(['user' => $admin])
            ->get(route('dashboard.persuratan.keluar.agenda.cetak'));
        $resAgendaKeluar->assertStatus(200);
        $resAgendaKeluar->assertSee('BUKU AGENDA SURAT KELUAR', false);
        $resAgendaKeluar->assertSee('size: A4 landscape', false);
        $resAgendaKeluar->assertSee('RINGKASAN &amp; REKAPITULASI SURAT KELUAR', false);

        // 6. Cetak Buku Ekspedisi Kolektif
        $resEkspedisiCetak = $this->withSession(['user' => $admin])
            ->get(route('dashboard.persuratan.keluar.ekspedisi.cetak'));
        $resEkspedisiCetak->assertStatus(200);
        $resEkspedisiCetak->assertSee('BUKU EKSPEDISI', false);
        $resEkspedisiCetak->assertSee('RINGKASAN &amp; REKAPITULASI EKSPEDISI', false);

        // 7. Form cetak pada halaman INDEKS harus `data-no-ajax`, jika tidak
        // dashboard-live-search.js membatalkan submit GET (lembar cetak tak terbuka).
        foreach ([
            route('dashboard.persuratan.masuk.index'),
            route('dashboard.persuratan.keluar.index'),
        ] as $indexUrl) {
            $resIndex = $this->withSession(['user' => $admin])->get($indexUrl);
            $resIndex->assertStatus(200);
            $this->assertStringContainsString(
                'data-no-ajax',
                $resIndex->getContent(),
                "Form cetak pada {$indexUrl} harus memakai data-no-ajax agar tidak diintersep live-search."
            );
        }
    }

    public function test_cetak_spt_menghasilkan_tiga_halaman_spt_sppd_laporan(): void
    {
        $admin = [
            'id' => 'admin-spt-01',
            'pengguna_id' => 'admin-spt-01',
            'username' => 'admin',
            'nama' => 'Administrator Sistem',
            'role' => 'admin',
        ];

        $id = DB::table('gtk_spt')->insertGetId([
            'nomor_spt'         => 'TEST/SPT/SPPD/0001',
            'dasar_penugasan'   => 'Surat Undangan Dinas No. 026/SU/2026',
            'nama_kegiatan'     => 'Mengikuti Teknikal Meeting Porsengur 2026',
            'lokasi_tujuan'     => 'Gedung PGRI Panyangling Budi',
            'waktu_pelaksanaan' => '11.00 WIB s.d. selesai',
            'alamat_tujuan'     => 'Jl. Raya Pagelaran KM. 63',
            'alat_angkutan'     => 'Kendaraan Darat',
            'tempat_berangkat'  => 'SMK Negeri 1 Pagelaran',
            'instansi_pembebanan' => 'SMK Negeri 1 Pagelaran',
            'akun_kodering'     => 'BOS Reguler',
            'tingkat_biaya'     => 'Tingkat B',
            'tanggal_berangkat' => '2026-11-04',
            'tanggal_kembali'   => '2026-11-04',
            'lama_hari'         => 1,
            'beban_anggaran'    => 'BOS Reguler',
            'pejabat_nama'      => 'Sopian Iskandar Mustopa, S.E., MM.',
            'pejabat_jabatan'   => 'Kepala Sekolah',
            'daftar_ptk_id'     => json_encode([], JSON_UNESCAPED_UNICODE),
            'status'            => 'disetujui',
            'created_by'        => 'Tester',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        try {
            $res = $this->withSession(['user' => $admin])
                ->get(route('dashboard.persuratan.keluar.spt.cetak', $id));

            $res->assertStatus(200);
            $res->assertSee('Surat Perintah Tugas', false);
            $res->assertSee('Surat Perintah Perjalanan Dinas (SPPD)', false);
            $res->assertSee('Laporan Hasil Perjalanan Dinas', false);
            $res->assertSee('Pangkat / Gol', false);
            // Tiga halaman tercetak berurutan (hitung elemen, bukan CSS).
            $this->assertSame(3, substr_count($res->getContent(), 'class="cetak-page'));
            $this->assertSame(1, substr_count($res->getContent(), 'class="cetak-page landscape'));
            $this->assertStringContainsString('size: A4 landscape', $res->getContent());
        } finally {
            DB::table('gtk_spt')->where('id', $id)->delete();
        }
    }
}
