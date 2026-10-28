<?php

namespace App\Support;

use App\Models\RefTugasTambahan;
use App\Models\RolePermission;
use App\Support\Table;
final class DutyPermissionManager
{
    /**
     * Perbarui daftar izin hak akses yang dibuka untuk tugas tambahan tertentu.
     */
    public static function updateDutyPermissions(string|int $dutyIdOrKode, array $grantedPermissions): bool
    {
        if (!Table::exists('ref_tugas_tambahan')) {
            return false;
        }

        $query = is_numeric($dutyIdOrKode)
            ? RefTugasTambahan::where('id', (int) $dutyIdOrKode)
            : RefTugasTambahan::where('kode', $dutyIdOrKode);

        $duty = $query->first();
        if (!$duty) {
            return false;
        }

        $cleanPerms = array_values(array_unique(array_filter($grantedPermissions)));
        $duty->update([
            'granted_permissions' => $cleanPerms
        ]);

        RolePermission::clearRuntimeCache();
        return true;
    }

    /**
     * Reset daftar modul yang diizinkan untuk tugas tambahan ke pengaturan default baku.
     */
    public static function resetDutyDefaults(string|int|null $dutyIdOrKode = null): void
    {
        if (!Table::exists('ref_tugas_tambahan')) {
            return;
        }

        $query = RefTugasTambahan::query();
        if ($dutyIdOrKode !== null) {
            if (is_numeric($dutyIdOrKode)) {
                $query->where('id', (int) $dutyIdOrKode);
            } else {
                $query->where('kode', $dutyIdOrKode);
            }
        }

        $duties = $query->get();
        foreach ($duties as $d) {
            $defaultPerms = DutyPermissionDefaults::forDuty($d->kode);
            $d->update(['granted_permissions' => $defaultPerms]);
        }

        RolePermission::clearRuntimeCache();
    }

    /**
     * Toggle satu izin modul untuk tugas tambahan tertentu.
     */
    public static function toggleDutyPermission(string|int $dutyIdOrKode, string $permissionKey, bool $isAllowed): array
    {
        if (!Table::exists('ref_tugas_tambahan')) {
            return [];
        }

        $query = is_numeric($dutyIdOrKode)
            ? RefTugasTambahan::where('id', (int) $dutyIdOrKode)
            : RefTugasTambahan::where('kode', $dutyIdOrKode);

        $duty = $query->first();
        if (!$duty) {
            return [];
        }

        $current = is_array($duty->granted_permissions)
            ? $duty->granted_permissions
            : (json_decode($duty->granted_permissions, true) ?: []);

        if ($isAllowed) {
            if (!in_array($permissionKey, $current, true)) {
                $current[] = $permissionKey;
            }
        } else {
            $current = array_values(array_filter($current, fn($k) => $k !== $permissionKey));
        }

        $duty->update(['granted_permissions' => $current]);
        RolePermission::clearRuntimeCache();

        return $current;
    }

    /**
     * Sinkronkan referensi tugas tambahan di tabel ref_tugas_tambahan.
     */
    public static function syncDutiesGrantedPermissions(): void
    {
        if (!Table::exists('ref_tugas_tambahan')) {
            return;
        }

        $kesiswaanAll = [
            'menu_kesiswaan',
            'menu_kesiswaan_peserta_didik',
            'menu_kesiswaan_administrasi',
            'menu_buku_induk',
            'menu_kesiswaan_kedisiplinan',
            'menu_kesiswaan_kegiatan',
            'menu_kesiswaan_prestasi',
            'menu_peserta_didik_aktif',
            'menu_peserta_didik_tidak_aktif',
            'menu_poin',
            'menu_kelulusan',
        ];

        foreach (['WAKA_KESISWAAN', 'STAF_KESISWAAN', 'KEPALA_TAS'] as $kode) {
            $ref = RefTugasTambahan::where('kode', $kode)->first();
            if ($ref) {
                $current = is_array($ref->granted_permissions) ? $ref->granted_permissions : (json_decode($ref->granted_permissions, true) ?: []);
                $merged = array_values(array_unique(array_merge($current, $kesiswaanAll)));
                if (count($merged) !== count($current)) {
                    $ref->update(['granted_permissions' => $merged]);
                }
            }
        }

        foreach (['PEMBINA_OSIS', 'PEMBINA_EKSKUL'] as $kode) {
            $ref = RefTugasTambahan::where('kode', $kode)->first();
            if ($ref) {
                $current = is_array($ref->granted_permissions) ? $ref->granted_permissions : (json_decode($ref->granted_permissions, true) ?: []);
                $merged = array_values(array_unique(array_merge($current, ['menu_kesiswaan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi', 'menu_poin'])));
                if (count($merged) !== count($current)) {
                    $ref->update(['granted_permissions' => $merged]);
                }
            }
        }

        $persuratanAll = ['menu_persuratan', 'menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan'];
        foreach (['STAF_PERSURATAN', 'KEPALA_TAS'] as $kode) {
            $ref = RefTugasTambahan::where('kode', $kode)->first();
            if ($ref) {
                $current = is_array($ref->granted_permissions) ? $ref->granted_permissions : (json_decode($ref->granted_permissions, true) ?: []);
                $merged = array_values(array_unique(array_merge($current, $persuratanAll)));
                if (count($merged) !== count($current)) {
                    $ref->update(['granted_permissions' => $merged]);
                }
            }
        }

        $sarprasAll = ['menu_sarpras', 'menu_sarpras_ruang', 'menu_inventaris', 'menu_sarpras_peminjaman', 'menu_rombel'];
        foreach (['STAF_SARPRAS', 'WAKA_SARPRAS', 'KEPALA_TAS'] as $kode) {
            $ref = RefTugasTambahan::where('kode', $kode)->first();
            if ($ref) {
                $current = is_array($ref->granted_permissions) ? $ref->granted_permissions : (json_decode($ref->granted_permissions, true) ?: []);
                $merged = array_values(array_unique(array_merge($current, $sarprasAll)));
                if (count($merged) !== count($current)) {
                    $ref->update(['granted_permissions' => $merged]);
                }
            }
        }

        $laboranAll = ['menu_laboran', 'menu_laboran_inventaris', 'menu_laboran_jadwal', 'menu_laboran_aset', 'menu_laboran_peminjaman', 'menu_laboran_peserta_didik', 'menu_inventaris'];
        foreach (['LABORAN', 'KEPALA_LAB', 'KEPALA_TAS'] as $kode) {
            $ref = RefTugasTambahan::where('kode', $kode)->first();
            if ($ref) {
                $current = is_array($ref->granted_permissions) ? $ref->granted_permissions : (json_decode($ref->granted_permissions, true) ?: []);
                $merged = array_values(array_unique(array_merge($current, $laboranAll)));
                if (count($merged) !== count($current)) {
                    $ref->update(['granted_permissions' => $merged]);
                }
            }
        }

        $perpustakaanAll = ['menu_perpustakaan', 'menu_perpustakaan_koleksi', 'menu_perpustakaan_sirkulasi', 'menu_perpustakaan_kunjungan', 'menu_inventaris'];
        foreach (['PUSTAKAWAN', 'KEPALA_PERPUS', 'KEPALA_TAS'] as $kode) {
            $ref = RefTugasTambahan::where('kode', $kode)->first();
            if ($ref) {
                $current = is_array($ref->granted_permissions) ? $ref->granted_permissions : (json_decode($ref->granted_permissions, true) ?: []);
                $merged = array_values(array_unique(array_merge($current, $perpustakaanAll)));
                if (count($merged) !== count($current)) {
                    $ref->update(['granted_permissions' => $merged]);
                }
            }
        }

        $teknisiAll = ['menu_teknisi', 'menu_teknisi_wo', 'menu_teknisi_pemeliharaan', 'menu_rfid', 'menu_inventaris'];
        foreach (['TEKNISI_IT', 'KEPALA_TAS'] as $kode) {
            $ref = RefTugasTambahan::where('kode', $kode)->first();
            if ($ref) {
                $current = is_array($ref->granted_permissions) ? $ref->granted_permissions : (json_decode($ref->granted_permissions, true) ?: []);
                $merged = array_values(array_unique(array_merge($current, $teknisiAll)));
                if (count($merged) !== count($current)) {
                    $ref->update(['granted_permissions' => $merged]);
                }
            }
        }

        $keamananAll = ['menu_keamanan', 'menu_keamanan_izin', 'menu_keamanan_presensi', 'menu_keamanan_buku_tamu', 'menu_buku_tamu', 'menu_keamanan_patroli', 'menu_keamanan_scan', 'menu_rfid'];
        foreach (['SATPAM', 'PETUGAS_KEAMANAN', 'KEPALA_TAS'] as $kode) {
            $ref = RefTugasTambahan::where('kode', $kode)->first();
            if ($ref) {
                $current = is_array($ref->granted_permissions) ? $ref->granted_permissions : (json_decode($ref->granted_permissions, true) ?: []);
                $merged = array_values(array_unique(array_merge($current, $keamananAll)));
                if (count($merged) !== count($current)) {
                    $ref->update(['granted_permissions' => $merged]);
                }
            }
        }

        $penjagaAll = ['menu_penjaga', 'menu_penjaga_kebersihan', 'menu_penjaga_ronda', 'menu_buku_tamu'];
        foreach (['PENJAGA_SEKOLAH', 'PESURUH', 'KEPALA_TAS'] as $kode) {
            $ref = RefTugasTambahan::where('kode', $kode)->first();
            if ($ref) {
                $current = is_array($ref->granted_permissions) ? $ref->granted_permissions : (json_decode($ref->granted_permissions, true) ?: []);
                $merged = array_values(array_unique(array_merge($current, $penjagaAll)));
                if (count($merged) !== count($current)) {
                    $ref->update(['granted_permissions' => $merged]);
                }
            }
        }

        $piketAll = ['menu_piket', 'menu_piket_presensi_guru', 'menu_piket_presensi_kelas', 'menu_piket_agenda', 'menu_piket_izin', 'menu_presensi_mengajar', 'menu_agenda_kbm', 'menu_presensi_peserta_didik', 'menu_e_izin', 'menu_piket_jurnal', 'menu_riwayat_rfid', 'menu_buku_tamu'];
        foreach (['GURU_PIKET', 'KEPALA_TAS'] as $kode) {
            $ref = RefTugasTambahan::where('kode', $kode)->first();
            if ($ref) {
                $current = is_array($ref->granted_permissions) ? $ref->granted_permissions : (json_decode($ref->granted_permissions, true) ?: []);
                $merged = array_values(array_unique(array_merge($current, $piketAll)));
                if (count($merged) !== count($current)) {
                    $ref->update(['granted_permissions' => $merged]);
                }
            }
        }

        $tendikKinerjaAll = ['menu_target_capaian', 'menu_aktivitas_tendik', 'menu_laporan_tendik'];
        foreach ([
            'KEPALA_TAS', 'STAF_PERSURATAN', 'STAF_KESISWAAN', 'STAF_KEPEGAWAIAN',
            'STAF_SARPRAS', 'LABORAN', 'PUSTAKAWAN', 'TEKNISI_IT', 'SATPAM', 'PENJAGA_SEKOLAH'
        ] as $kode) {
            $ref = RefTugasTambahan::where('kode', $kode)->first();
            if ($ref) {
                $current = is_array($ref->granted_permissions) ? $ref->granted_permissions : (json_decode($ref->granted_permissions, true) ?: []);
                $merged = array_values(array_unique(array_merge($current, $tendikKinerjaAll)));
                if (count($merged) !== count($current)) {
                    $ref->update(['granted_permissions' => $merged]);
                }
            }
        }

        $waliKelasAll = [
            'menu_wali_kelas',
            'menu_wali_kelas_aktif',
            'menu_wali_kelas_tidak_aktif',
            'menu_wali_kelas_presensi',
            'menu_wali_kelas_jadwal',
            'menu_wali_kelas_pip',
            'menu_wali_kelas_validasi',
            'menu_wali_kelas_agenda',
            'menu_peserta_didik_aktif',
            'menu_presensi_peserta_didik',
            'menu_agenda_kbm',
            'menu_berkas_peserta_didik',
            'menu_poin',
        ];
        $refWali = RefTugasTambahan::where('kode', 'WALI_KELAS')->first();
        if ($refWali) {
            $current = is_array($refWali->granted_permissions) ? $refWali->granted_permissions : (json_decode($refWali->granted_permissions, true) ?: []);
            $merged = array_values(array_unique(array_merge($current, $waliKelasAll)));
            if (count($merged) !== count($current)) {
                $refWali->update(['granted_permissions' => $merged]);
            }
        }
    }
}
