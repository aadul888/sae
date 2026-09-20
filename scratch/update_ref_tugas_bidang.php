<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== UPDATING REF_TUGAS_TAMBAHAN BIDANG & GRANTED_PERMISSIONS ===\n";

$updates = [
    'WAKA_KURIKULUM' => [
        'bidang' => 'Kurikulum',
        'granted' => ["menu_pembelajaran", "menu_kompetensi_keahlian", "menu_rombel", "menu_jadwal_pelajaran", "menu_jadwal_kbm", "menu_presensi_mengajar", "menu_agenda_kbm", "menu_penilaian"]
    ],
    'WAKA_KESISWAAN' => [
        'bidang' => 'Kesiswaan',
        'granted' => ["menu_kesiswaan", "menu_peserta_didik_aktif", "menu_peserta_didik_tidak_aktif", "menu_poin", "menu_e_izin", "menu_riwayat_rfid"]
    ],
    'WAKA_HUBIN' => [
        'bidang' => 'Humas & Hubin',
        'granted' => ["menu_pengumuman", "menu_buku_tamu", "menu_agenda"]
    ],
    'WAKA_SARPRAS' => [
        'bidang' => 'Sarpras & Aset',
        'granted' => ["menu_sarpras", "menu_inventaris", "menu_rombel"]
    ],
    'KAPROG' => [
        'bidang' => 'Keahlian / Jurusan',
        'granted' => ["menu_kompetensi_keahlian", "menu_rombel", "menu_pembelajaran", "menu_peserta_didik_aktif"]
    ],
    'KEPALA_PERPUS' => [
        'bidang' => 'Perpustakaan',
        'granted' => ["menu_perpustakaan", "menu_inventaris"]
    ],
    'KEPALA_LAB' => [
        'bidang' => 'Laboratorium',
        'granted' => ["menu_laboran", "menu_inventaris"]
    ],
    'WALI_KELAS' => [
        'bidang' => 'Wali Kelas',
        'granted' => ["menu_wali_kelas_aktif", "menu_wali_kelas_tidak_aktif", "menu_wali_kelas_presensi", "menu_peserta_didik_aktif", "menu_presensi_peserta_didik", "menu_e_izin", "menu_rapor", "menu_agenda_kbm", "menu_penilaian"]
    ],
    'PEMBINA_OSIS' => [
        'bidang' => 'Kesiswaan & OSIS',
        'granted' => ["menu_agenda", "menu_pengumuman", "menu_poin"]
    ],
    'PEMBINA_EKSKUL' => [
        'bidang' => 'Ekstrakurikuler',
        'granted' => ["menu_agenda", "menu_pengumuman"]
    ],
    'GURU_PIKET' => [
        'bidang' => 'Piket Sekolah',
        'granted' => ["menu_piket", "menu_presensi_mengajar", "menu_agenda_kbm", "menu_buku_tamu", "menu_e_izin", "menu_riwayat_rfid", "menu_aktivitas_tendik", "menu_laporan_tendik"]
    ],
    'KEPALA_TAS' => [
        'bidang' => 'Kepala TAS',
        'granted' => [
            "menu_kepala_tas", "menu_persuratan", "menu_kesiswaan", "menu_kepegawaian", "menu_keuangan",
            "menu_sarpras", "menu_laboran", "menu_perpustakaan", "menu_teknisi", "menu_keamanan",
            "menu_penjaga", "menu_piket", "menu_buku_tamu", "menu_inventaris", "menu_agenda",
            "menu_aktivitas_tendik", "menu_laporan_tendik", "menu_peserta_didik_aktif", "menu_guru_aktif", "menu_tendik_aktif"
        ]
    ],
    'OPERATOR_DAPODIK' => [
        'bidang' => 'Data & Dapodik',
        'granted' => ["menu_dapodik", "menu_peserta_didik_aktif", "menu_guru_aktif", "menu_tendik_aktif", "menu_rombel", "menu_pembelajaran"]
    ],
    'STAF_KEPEGAWAIAN' => [
        'bidang' => 'Kepegawaian',
        'granted' => ["menu_kepegawaian", "menu_guru_aktif", "menu_tendik_aktif", "menu_guru_tidak_aktif", "menu_tendik_tidak_aktif", "menu_rfid", "menu_aktivitas_tendik", "menu_laporan_tendik"]
    ],
    'STAF_KESISWAAN' => [
        'bidang' => 'Kesiswaan',
        'granted' => ["menu_kesiswaan", "menu_peserta_didik_aktif", "menu_berkas_peserta_didik", "menu_perubahan_data", "menu_peserta_didik_tidak_aktif", "menu_kelulusan", "menu_aktivitas_tendik", "menu_laporan_tendik"]
    ],
    'STAF_PERSURATAN' => [
        'bidang' => 'Persuratan',
        'granted' => ["menu_persuratan", "menu_buku_tamu", "menu_agenda", "menu_aktivitas_tendik", "menu_laporan_tendik"]
    ],
    'STAF_SARPRAS' => [
        'bidang' => 'Sarpras & Aset',
        'granted' => ["menu_sarpras", "menu_inventaris", "menu_rombel", "menu_aktivitas_tendik", "menu_laporan_tendik"]
    ],
    'TEKNISI_IT' => [
        'bidang' => 'Teknisi IT',
        'granted' => ["menu_teknisi", "menu_rfid", "menu_inventaris", "menu_aktivitas_tendik", "menu_laporan_tendik"]
    ],
    'LABORAN' => [
        'bidang' => 'Laboratorium',
        'granted' => ["menu_laboran", "menu_inventaris", "menu_aktivitas_tendik", "menu_laporan_tendik"]
    ],
    'PUSTAKAWAN' => [
        'bidang' => 'Perpustakaan',
        'granted' => ["menu_perpustakaan", "menu_inventaris", "menu_aktivitas_tendik", "menu_laporan_tendik"]
    ],
    'SATPAM' => [
        'bidang' => 'Keamanan & Tamu',
        'granted' => ["menu_keamanan", "menu_buku_tamu", "menu_rfid", "menu_aktivitas_tendik", "menu_laporan_tendik"]
    ],
    'PENJAGA_SEKOLAH' => [
        'bidang' => 'Fasilitas & Penjaga',
        'granted' => ["menu_penjaga", "menu_buku_tamu", "menu_aktivitas_tendik", "menu_laporan_tendik"]
    ],
];

foreach ($updates as $kode => $data) {
    $affected = DB::table('ref_tugas_tambahan')
        ->where('kode', $kode)
        ->update([
            'bidang' => $data['bidang'],
            'granted_permissions' => json_encode($data['granted'], JSON_UNESCAPED_UNICODE),
        ]);
    echo "[+] {$kode} -> Bidang: {$data['bidang']} (Updated: {$affected})\n";
}

echo "=== REF_TUGAS_TAMBAHAN UPDATED SUCCESSFULLY ===\n";
