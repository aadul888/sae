<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\RolePermission;
use Illuminate\Support\Facades\DB;

/**
 * Mengunci perilaku "duty gate" RBAC setelah logikanya dipindah dari
 * `App\Models\RolePermission` ke trait `RolePermissionDutyGate`.
 *
 * Fokus: pemetaan bawaan per kode tugas tambahan + pewarisan menu induk ke
 * sub-modul. Bila peta berubah (mis. tidak sengaja memakai
 * `DutyPermissionDefaults` yang lebih luas), test ini gagal sehingga
 * perubahan hak akses tidak lolos tanpa disadari.
 */
class RolePermissionDutyGateTest extends TestCase
{
    /** Kode tugas tambahan yang punya pemetaan bawaan di duty gate. */
    private const KODE_TERPETAKAN = [
        'WALI_KELAS', 'KEPALA_SEKOLAH', 'WAKA_KURIKULUM', 'WAKA_KESISWAAN',
        'PEMBINA_OSIS', 'PEMBINA_EKSKUL', 'WAKA_SARPRAS', 'KEPALA_BENGKEL',
        'KEPALA_LAB', 'KEPALA_PERPUSTAKAAN', 'PUSTAKAWAN', 'LABORAN',
        'TEKNISI_IT', 'TEKNISI_GEDUNG', 'TEKNISI_LAPANGAN', 'PETUGAS_KEAMANAN',
        'SATPAM', 'PENJAGA_SEKOLAH', 'PESURUH', 'GURU_PIKET', 'OPERATOR_DAPODIK',
        'KEPALA_TAS', 'STAF_PERSURATAN', 'STAF_KEPEGAWAIAN', 'STAF_KESISWAAN',
        'STAF_SARPRAS', 'KOORDINATOR_KELAS',
    ];

    /** Kode tugas tambahan yang TIDAK punya pemetaan bawaan. */
    private const KODE_TANPA_PETA = ['KODE_TIDAK_DIKENAL', 'WAKA_HUBIN'];

    /**
     * Peta bawaan harus tetap menghasilkan izin untuk kode yang dikenal.
     */
    public function test_setiap_kode_terpetakan_membuka_minimal_satu_modul(): void
    {
        foreach (self::KODE_TERPETAKAN as $kode) {
            $keys = $this->allowedKeysUntuk($kode);

            $this->assertNotEmpty(
                $keys,
                "Kode tugas '{$kode}' harus membuka minimal satu modul lewat peta bawaan."
            );
        }
    }

    /**
     * Kode yang tidak dikenal tidak boleh membuka modul apa pun
     * (mencegah eskalasi hak akses lewat kode sembarang).
     */
    public function test_kode_tanpa_pemetaan_tidak_membuka_modul(): void
    {
        foreach (self::KODE_TANPA_PETA as $kode) {
            $keys = $this->allowedKeysUntuk($kode);

            $this->assertSame(
                [],
                $keys,
                "Kode tugas '{$kode}' tidak boleh membuka modul apa pun."
            );
        }
    }

    /**
     * Menu induk mewariskan izin ke sub-modulnya.
     */
    public function test_pewarisan_menu_induk_ke_sub_modul(): void
    {
        $kasus = [
            'LABORAN'          => ['menu_laboran' => 'menu_laboran_jadwal'],
            'STAF_PERSURATAN'  => ['menu_persuratan' => 'menu_surat_masuk'],
            'GURU_PIKET'       => ['menu_piket' => 'menu_piket_jurnal'],
            'SATPAM'           => ['menu_keamanan' => 'menu_keamanan_scan'],
            'PUSTAKAWAN'       => ['menu_perpustakaan' => 'menu_perpustakaan_koleksi'],
        ];

        foreach ($kasus as $kode => $pasangan) {
            $keys = $this->allowedKeysUntuk($kode);

            foreach ($pasangan as $induk => $sub) {
                $this->assertArrayHasKey(
                    $induk,
                    $keys,
                    "Kode '{$kode}' harus membuka menu induk '{$induk}'."
                );
                $this->assertArrayHasKey(
                    $sub,
                    $keys,
                    "Kode '{$kode}' harus mewariskan '{$induk}' ke sub-modul '{$sub}'."
                );
            }
        }
    }

    /**
     * Tendik umum mendapat menu Kinerja & Aktivitas Tendik.
     */
    public function test_tendik_umum_mendapat_menu_kinerja(): void
    {
        foreach (['LABORAN', 'PUSTAKAWAN', 'SATPAM', 'PENJAGA_SEKOLAH', 'STAF_SARPRAS'] as $kode) {
            $keys = $this->allowedKeysUntuk($kode);

            foreach (['menu_target_capaian', 'menu_aktivitas_tendik', 'menu_laporan_tendik'] as $menu) {
                $this->assertArrayHasKey(
                    $menu,
                    $keys,
                    "Kode '{$kode}' harus membuka '{$menu}'."
                );
            }
        }
    }

    /**
     * `hasDutyPermission` tetap menolak aksi tak dikenal dan pengguna tanpa identitas.
     */
    public function test_has_duty_permission_menolak_tanpa_identitas(): void
    {
        $this->assertFalse(
            RolePermission::hasDutyPermission(null, null, 'menu_laboran', 'read'),
            'Tanpa user_id maupun ptk_id, akses tugas tambahan harus ditolak.'
        );
    }

    /**
     * Jalankan pemetaan bawaan untuk satu kode tugas.
     *
     * @return array<string, bool>
     */
    private function allowedKeysUntuk(string $kode): array
    {
        $method = new \ReflectionMethod(RolePermission::class, 'resolveDutyAllowedKeys');
        $method->setAccessible(true);

        $records = [(object) [
            'kode'                => $kode,
            'kelompok'            => null,
            'bidang'              => null,
            'granted_permissions' => null,
            'rombel_id'           => null,
        ]];

        $keys = $method->invoke(null, $records);

        return is_array($keys) ? $keys : [];
    }
}
