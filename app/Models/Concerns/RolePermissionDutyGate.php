<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Gerbang izin berbasis TUGAS TAMBAHAN (duty gate) untuk `RolePermission`.
 *
 * Diekstrak dari `App\Models\RolePermission` agar model tetap di bawah batas
 * 1.000 baris. Isi method dipindahkan APA ADANYA (termasuk urutan pemeriksaan
 * dan peta bawaan per kode tugas) sehingga hasil otorisasi tidak berubah.
 *
 * Pemakai wajib berupa Eloquent Model dengan tabel `role_permissions`.
 */
trait RolePermissionDutyGate
{
    /**
     * Cache in-memory per request: key `"{userId}|{ptkId}"` -> daftar permission_key
     * yang dibuka oleh tugas tambahan aktif milik pengguna.
     *
     * @var array<string, array<string, bool>>
     */
    protected static array $runtimeDutyPermissionsCache = [];

    /**
     * Periksa apakah pengguna memiliki tugas tambahan aktif yang membuka modul tertentu
     */
    public static function hasDutyPermission(mixed $userId, ?string $ptkId, string $permissionKey, string $action = 'read'): bool
    {
        try {
            $cacheKey = ($userId ?: 'null') . '|' . ($ptkId ?: 'null');

            if (!isset(self::$runtimeDutyPermissionsCache[$cacheKey])) {
                $query = DB::table('ptk_tugas_tambahan as ptt')
                    ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                    ->where('ptt.is_active', true)
                    ->where('rtt.is_active', true);

                if ($userId && $ptkId) {
                    $query->where(function ($q) use ($userId, $ptkId) {
                        $q->where('ptt.user_id', (string) $userId)->orWhere('ptt.ptk_id', $ptkId);
                    });
                } elseif ($userId) {
                    $query->where('ptt.user_id', (string) $userId);
                } elseif ($ptkId) {
                    $query->where('ptt.ptk_id', $ptkId);
                } else {
                    self::$runtimeDutyPermissionsCache[$cacheKey] = [];
                    return false;
                }

                // 1. Cek granted_permissions JSON dari tabel ref_tugas_tambahan
                $records = $query->select('rtt.kode', 'rtt.kelompok', 'rtt.bidang', 'rtt.granted_permissions', 'ptt.rombel_id')->get();

                self::$runtimeDutyPermissionsCache[$cacheKey] = self::resolveDutyAllowedKeys($records);
            }

            $action = strtolower(trim($action));
            if (isset(self::$runtimeDutyPermissionsCache[$cacheKey][$permissionKey])) {
                return in_array($action, ['read', 'create', 'update', 'delete']);
            }

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Susun daftar permission_key yang dibuka dari seluruh baris tugas tambahan aktif.
     *
     * Sumbernya dua: (a) `granted_permissions` JSON yang dikonfigurasi admin, dan
     * (b) pemetaan bawaan per `kode` tugas tambahan. Pemetaan bawaan sengaja
     * dipertahankan di sini (bukan memakai `DutyPermissionDefaults`) karena isinya
     * adalah subset historis yang LEBIH KECIL daripada default resmi — memakai
     * default resmi akan MENAMBAH hak akses dan mengubah perilaku otorisasi.
     *
     * @param  iterable<int, object>  $records
     * @return array<string, bool>
     */
    protected static function resolveDutyAllowedKeys(iterable $records): array
    {
        $allowedKeys = [];

        foreach ($records as $rec) {
            $raw = $rec->granted_permissions;
            $perms = is_string($raw) ? json_decode($raw, true) : $raw;
            if (is_array($perms)) {
                foreach ($perms as $p) {
                    $allowedKeys[$p] = true;
                }
            }

            // 2. Pemetaan bawaan berdasarkan kode tugas tambahan
            $kode = $rec->kode;
            if ($kode === 'WALI_KELAS') {
                foreach (['menu_wali_kelas_aktif', 'menu_wali_kelas_tidak_aktif', 'menu_wali_kelas_presensi', 'menu_wali_kelas_jadwal', 'menu_wali_kelas_pip', 'menu_wali_kelas_validasi', 'menu_wali_kelas_agenda', 'menu_peserta_didik_aktif', 'menu_presensi_peserta_didik', 'menu_agenda_kbm', 'menu_berkas_peserta_didik'] as $k) {
                    $allowedKeys[$k] = true;
                }
            } elseif (in_array($kode, ['KEPALA_SEKOLAH', 'WAKA_KURIKULUM'], true)) {
                foreach (['menu_rombel', 'menu_pembelajaran', 'menu_jadwal_kbm', 'menu_kompetensi_keahlian', 'menu_presensi_mengajar', 'menu_agenda_kbm'] as $k) {
                    $allowedKeys[$k] = true;
                }
            } elseif ($kode === 'WAKA_KESISWAAN') {
                foreach (['menu_kesiswaan', 'menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi', 'menu_peserta_didik_aktif', 'menu_peserta_didik_tidak_aktif', 'menu_e_izin'] as $k) {
                    $allowedKeys[$k] = true;
                }
            } elseif ($kode === 'PEMBINA_OSIS') {
                foreach (['menu_kesiswaan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi', 'menu_pengumuman', 'menu_kesiswaan_kedisiplinan'] as $k) {
                    $allowedKeys[$k] = true;
                }
            } elseif ($kode === 'PEMBINA_EKSKUL') {
                foreach (['menu_kesiswaan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi', 'menu_pengumuman'] as $k) {
                    $allowedKeys[$k] = true;
                }
            } elseif (in_array($kode, ['WAKA_SARPRAS', 'KEPALA_BENGKEL'], true)) {
                foreach (['menu_sarpras', 'menu_inventaris'] as $k) {
                    $allowedKeys[$k] = true;
                }
            } elseif ($kode === 'KEPALA_LAB') {
                foreach (['menu_laboran', 'menu_laboran_peserta_didik', 'menu_sarpras', 'menu_inventaris'] as $k) {
                    $allowedKeys[$k] = true;
                }
            } elseif (in_array($kode, ['KEPALA_PERPUSTAKAAN', 'PUSTAKAWAN'], true)) {
                foreach (['menu_perpustakaan', 'menu_perpustakaan_koleksi', 'menu_perpustakaan_sirkulasi', 'menu_perpustakaan_kunjungan', 'menu_inventaris'] as $k) {
                    $allowedKeys[$k] = true;
                }
            } elseif ($kode === 'LABORAN') {
                foreach (['menu_laboran', 'menu_laboran_inventaris', 'menu_laboran_jadwal', 'menu_laboran_aset', 'menu_laboran_peminjaman', 'menu_laboran_peserta_didik', 'menu_inventaris'] as $k) {
                    $allowedKeys[$k] = true;
                }
            } elseif (in_array($kode, ['TEKNISI_IT', 'TEKNISI_GEDUNG', 'TEKNISI_LAPANGAN'], true)) {
                foreach (['menu_teknisi', 'menu_teknisi_wo', 'menu_teknisi_pemeliharaan', 'menu_inventaris'] as $k) {
                    $allowedKeys[$k] = true;
                }
            } elseif ($kode === 'PETUGAS_KEAMANAN' || $kode === 'SATPAM') {
                foreach (['menu_keamanan', 'menu_keamanan_izin', 'menu_keamanan_presensi', 'menu_keamanan_buku_tamu', 'menu_buku_tamu', 'menu_keamanan_patroli', 'menu_keamanan_scan', 'menu_rfid'] as $k) {
                    $allowedKeys[$k] = true;
                }
            } elseif (in_array($kode, ['PENJAGA_SEKOLAH', 'PESURUH'], true)) {
                foreach (['menu_penjaga', 'menu_penjaga_kebersihan', 'menu_penjaga_ronda', 'menu_buku_tamu'] as $k) {
                    $allowedKeys[$k] = true;
                }
            } elseif ($kode === 'GURU_PIKET') {
                foreach (['menu_piket', 'menu_piket_presensi_guru', 'menu_piket_presensi_kelas', 'menu_piket_agenda', 'menu_piket_izin', 'menu_presensi_mengajar', 'menu_agenda_kbm', 'menu_presensi_peserta_didik', 'menu_e_izin', 'menu_piket_jurnal', 'menu_riwayat_rfid'] as $k) {
                    $allowedKeys[$k] = true;
                }
            } elseif ($kode === 'OPERATOR_DAPODIK') {
                foreach (['menu_dapodik', 'menu_peserta_didik_aktif', 'menu_guru_aktif', 'menu_tendik_aktif', 'menu_rombel', 'menu_pembelajaran'] as $k) {
                    $allowedKeys[$k] = true;
                }
            } elseif ($kode === 'KEPALA_TAS') {
                foreach ([
                    'menu_kepala_tas', 'menu_persuratan', 'menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan',
                    'menu_kesiswaan', 'menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi',
                    'menu_kepegawaian', 'menu_kepegawaian_guru', 'menu_kepegawaian_tendik', 'menu_kepegawaian_kgb', 'menu_kepegawaian_cuti',
                    'menu_keuangan', 'menu_sarpras', 'menu_sarpras_ruang', 'menu_inventaris', 'menu_sarpras_peminjaman',
                    'menu_target_capaian', 'menu_aktivitas_tendik', 'menu_laporan_tendik',
                    'menu_laboran', 'menu_laboran_inventaris', 'menu_laboran_jadwal', 'menu_laboran_aset', 'menu_laboran_peminjaman',
                    'menu_perpustakaan', 'menu_perpustakaan_koleksi', 'menu_perpustakaan_sirkulasi', 'menu_perpustakaan_kunjungan',
                    'menu_teknisi', 'menu_teknisi_wo', 'menu_teknisi_pemeliharaan',
                    'menu_keamanan', 'menu_keamanan_izin', 'menu_keamanan_presensi', 'menu_keamanan_buku_tamu', 'menu_buku_tamu', 'menu_keamanan_patroli', 'menu_keamanan_scan',
                    'menu_penjaga', 'menu_penjaga_kebersihan', 'menu_penjaga_ronda',
                    'menu_piket', 'menu_piket_presensi_guru', 'menu_piket_presensi_kelas', 'menu_piket_agenda', 'menu_piket_izin', 'menu_presensi_mengajar', 'menu_presensi_peserta_didik', 'menu_agenda_kbm', 'menu_e_izin', 'menu_piket_jurnal'
                ] as $k) {
                    $allowedKeys[$k] = true;
                }
            } elseif ($kode === 'STAF_PERSURATAN') {
                foreach (['menu_persuratan', 'menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan'] as $k) {
                    $allowedKeys[$k] = true;
                }
            } elseif ($kode === 'STAF_KEPEGAWAIAN') {
                foreach (['menu_kepegawaian', 'menu_kepegawaian_guru', 'menu_kepegawaian_tendik', 'menu_kepegawaian_kgb', 'menu_kepegawaian_cuti', 'menu_tendik_aktif', 'menu_guru_aktif', 'menu_guru_tidak_aktif', 'menu_tendik_tidak_aktif', 'menu_rfid'] as $k) {
                    $allowedKeys[$k] = true;
                }
            } elseif ($kode === 'STAF_KESISWAAN') {
                foreach (['menu_kesiswaan', 'menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi', 'menu_peserta_didik_aktif', 'menu_peserta_didik_tidak_aktif', 'menu_berkas_peserta_didik', 'menu_perubahan_data'] as $k) {
                    $allowedKeys[$k] = true;
                }
            } elseif ($kode === 'STAF_SARPRAS') {
                foreach (['menu_sarpras', 'menu_sarpras_ruang', 'menu_inventaris', 'menu_sarpras_peminjaman', 'menu_rombel'] as $k) {
                    $allowedKeys[$k] = true;
                }
            } elseif ($kode === 'KOORDINATOR_KELAS') {
                foreach (['menu_wali_kelas_aktif', 'menu_peserta_didik_aktif', 'menu_wali_kelas_presensi', 'menu_presensi_peserta_didik', 'menu_wali_kelas_agenda', 'menu_agenda_kbm'] as $k) {
                    $allowedKeys[$k] = true;
                }
            }

            // Tendik umum: Aktivitas & Laporan Kinerja
            if (in_array($kode, [
                'KEPALA_TAS', 'STAF_PERSURATAN', 'STAF_KESISWAAN', 'STAF_KEPEGAWAIAN',
                'STAF_SARPRAS', 'LABORAN', 'PUSTAKAWAN', 'TEKNISI_IT', 'SATPAM', 'PENJAGA_SEKOLAH'
            ], true)) {
                $allowedKeys['menu_target_capaian'] = true;
                $allowedKeys['menu_aktivitas_tendik'] = true;
                $allowedKeys['menu_laporan_tendik'] = true;
            }
        }

        // Wariskan izin menu induk ke sub-modul bila belum terdefinisi terpisah
        foreach (self::dutyPermissionInheritance() as $parent => $children) {
            if (empty($allowedKeys[$parent])) {
                continue;
            }
            foreach ($children as $child) {
                if (!isset($allowedKeys[$child])) {
                    $allowedKeys[$child] = true;
                }
            }
        }

        return $allowedKeys;
    }

    /**
     * Peta pewarisan izin: menu induk -> sub-modul yang otomatis ikut terbuka.
     *
     * @return array<string, array<int, string>>
     */
    protected static function dutyPermissionInheritance(): array
    {
        return [
            'menu_persuratan' => ['menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan'],
            'menu_kesiswaan' => ['menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi'],
            'menu_kepegawaian' => ['menu_kepegawaian_guru', 'menu_kepegawaian_tendik', 'menu_kepegawaian_kgb', 'menu_kepegawaian_cuti'],
            'menu_perpustakaan' => ['menu_perpustakaan_koleksi', 'menu_perpustakaan_sirkulasi', 'menu_perpustakaan_kunjungan'],
            'menu_sarpras' => ['menu_sarpras_ruang', 'menu_inventaris', 'menu_sarpras_peminjaman'],
            'menu_laboran' => ['menu_laboran_inventaris', 'menu_laboran_jadwal', 'menu_laboran_aset', 'menu_laboran_peminjaman'],
            'menu_teknisi' => ['menu_teknisi_wo', 'menu_teknisi_pemeliharaan'],
            'menu_keamanan' => ['menu_keamanan_izin', 'menu_keamanan_presensi', 'menu_buku_tamu', 'menu_keamanan_patroli', 'menu_keamanan_scan'],
            'menu_penjaga' => ['menu_penjaga_kebersihan', 'menu_penjaga_ronda'],
            'menu_piket' => ['menu_piket_presensi_guru', 'menu_piket_presensi_kelas', 'menu_piket_agenda', 'menu_piket_izin', 'menu_presensi_mengajar', 'menu_presensi_peserta_didik', 'menu_agenda_kbm', 'menu_e_izin', 'menu_piket_jurnal'],
        ];
    }
}
