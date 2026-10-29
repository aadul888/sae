<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Definisi modul TEKNISI LAPANGAN yang berpola sama (daftar + form + cetak).
 *
 * Satu definisi = satu tabel, satu daftar kolom, satu set kartu statistik.
 * Dipakai oleh {@see \App\Http\Controllers\Teknisi\TeknisiModuleController}
 * dan view `dashboard/teknisi/modul.blade.php`, sehingga penambahan modul
 * baru cukup menambah satu entri di sini.
 */
final class TeknisiModuleRegistry
{
    public const KATEGORI_AREA = [
        'administrasi'   => 'Ruang Administrasi',
        'perkantoran'    => 'Ruang Perkantoran',
        'fasilitas_umum' => 'Fasilitas Umum (WC, Jamban, Kantin)',
        'teras_halaman'  => 'Teras & Halaman',
        'lapangan'       => 'Lapangan, Gor, Aula',
        'laboratorium'   => 'Laboratorium & Bengkel',
        'lainnya'        => 'Lainnya',
    ];

    public const FREKUENSI = [
        'harian'     => 'Harian',
        'mingguan'   => 'Mingguan',
        'bulanan'    => 'Bulanan',
        'insidental' => 'Insidental',
    ];

    public const KONDISI = [
        'baik'         => 'Baik',
        'cukup'        => 'Cukup',
        'rusak_ringan' => 'Rusak Ringan',
        'rusak_berat'  => 'Rusak Berat',
        'habis'        => 'Habis',
        'tidak_ada'    => 'Tidak Ada',
    ];

    public const STATUS_UMUM = [
        'draf'          => 'Draf',
        'selesai'       => 'Selesai',
        'terverifikasi' => 'Terverifikasi',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'area-master' => [
                'table'   => 'teknisi_area_master',
                'nomor'   => ['kode_area', 'AR'],
                'title'   => 'Master Area & Zona Kebersihan',
                'desc'    => 'Daftar baku area/zona kebersihan beserta penanggung jawab. Menjadi rujukan jadwal area tugas dan checklist kebersihan.',
                'icon'    => 'fas fa-map-location-dot',
                'color'   => 'var(--primary, #3b82f6)',
                'dateCol' => null,
                'order'   => ['nama_area', 'asc'],
                'stats'   => [
                    ['label' => 'Total Area', 'icon' => 'fas fa-map-location-dot', 'color' => 'var(--primary, #3b82f6)'],
                    ['label' => 'Area Aktif', 'icon' => 'fas fa-circle-check', 'color' => 'var(--success, #10b981)', 'where' => ['status', 'aktif']],
                    ['label' => 'Fasilitas Umum', 'icon' => 'fas fa-restroom', 'color' => 'var(--warning, #f59e0b)', 'where' => ['kategori', 'fasilitas_umum']],
                    ['label' => 'Tanpa Petugas', 'icon' => 'fas fa-user-slash', 'color' => 'var(--danger, #ef4444)', 'whereNull' => 'petugas_nama'],
                ],
                'columns' => [
                    'nama_area'    => ['label' => 'Nama Area', 'type' => 'string', 'required' => true, 'list' => 'ruang'],
                    'zona'         => ['label' => 'Zona / Blok', 'type' => 'string'],
                    'gedung'       => ['label' => 'Gedung', 'type' => 'string'],
                    'lantai'       => ['label' => 'Lantai', 'type' => 'string'],
                    'kategori'     => ['label' => 'Kategori', 'type' => 'select', 'options' => self::KATEGORI_AREA],
                    'frekuensi'    => ['label' => 'Frekuensi', 'type' => 'select', 'options' => self::FREKUENSI],
                    'petugas_nama' => ['label' => 'Petugas', 'type' => 'string', 'people' => true],
                    'petugas_ptk_id' => ['label' => 'PTK Petugas', 'type' => 'hidden'],
                    'status'       => ['label' => 'Status', 'type' => 'select', 'options' => ['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'], 'default' => 'aktif'],
                    'keterangan'   => ['label' => 'Keterangan', 'type' => 'text', 'wide' => true],
                ],
                'list' => ['nama_area', 'kategori', 'frekuensi', 'petugas_nama', 'status'],
                // Area Master adalah cermin master Sarpras: barisnya diturunkan
                // dari `sarpras_ruang` (lihat syncAreaMaster). Tabel ini data
                // bersama — tidak di-scope per akun, cukup unik per `ruang_id`.
                'autoSync' => true,
                'shared'   => true,
            ],

            'inspeksi-utilitas' => self::logSpec('inspeksi-utilitas', 'Inspeksi Utilitas & Instalasi', 'fas fa-bolt', 'var(--warning, #f59e0b)', 'Pemeriksaan panel listrik, genset, pompa, tandon, dan meteran air/listrik.', [
                'kategori' => [
                    'panel_listrik' => 'Panel & Instalasi Listrik',
                    'genset'        => 'Genset',
                    'pompa_air'     => 'Pompa Air',
                    'tandon'        => 'Tandon / Reservoir',
                    'meteran'       => 'Meteran Listrik & Air',
                    'drainase'      => 'Drainase & Saluran',
                    'lainnya'       => 'Lainnya',
                ],
                'satuan' => true,
            ]),

            'apar-k3' => self::logSpec('apar-k3', 'Inspeksi APAR & K3', 'fas fa-fire-extinguisher', 'var(--danger, #ef4444)', 'Pemeriksaan alat pemadam api ringan, jalur evakuasi, dan keselamatan kerja.', [
                'kategori' => [
                    'apar'            => 'APAR (Alat Pemadam)',
                    'jalur_evakuasi'  => 'Jalur & Titik Evakuasi',
                    'p3k'             => 'Kotak P3K',
                    'apd'             => 'Alat Pelindung Diri',
                    'instalasi_gas'   => 'Instalasi Gas',
                    'lainnya'         => 'Lainnya',
                ],
            ]),

            'pest-control' => self::logSpec('pest-control', 'Pest Control & Fogging', 'fas fa-bug', 'var(--purple, #8b5cf6)', 'Kegiatan pengendalian hama, fogging DHF, dan kebersihan area rawan.', [
                'kategori' => [
                    'fogging'        => 'Fogging / Pengasapan',
                    'rodentisida'    => 'Pengendalian Tikus',
                    'saluran_air'    => 'Saluran Air & Jentik',
                    'area_sampah'    => 'Area Sampah',
                    'lainnya'        => 'Lainnya',
                ],
                'area' => true,
            ]),

            'higiene-kantin' => self::logSpec('higiene-kantin', 'Higiene Sanitasi Kantin', 'fas fa-utensils', 'var(--success, #10b981)', 'Pemeriksaan kebersihan kantin, kualitas air minum, dan penjamah makanan.', [
                'kategori' => [
                    'kebersihan_kantin' => 'Kebersihan Ruang Kantin',
                    'air_minum'         => 'Kualitas Air Minum',
                    'penjamah'          => 'Higiene Penjamah Makanan',
                    'penyajian'         => 'Penyajian & Penyimpanan',
                    'lainnya'           => 'Lainnya',
                ],
                'area' => true,
            ]),

            'pemakaian-bahan' => self::logSpec('pemakaian-bahan', 'Pemakaian Bahan Kebersihan', 'fas fa-spray-can-sparkles', 'var(--primary, #3b82f6)', 'Pencatatan pemakaian sabun, tissue, pewangi, dan bahan kebersihan lain per area.', [
                'kategori' => [
                    'sabun'         => 'Sabun & Sanitizer',
                    'tissue'        => 'Tissue & Tisu Basah',
                    'pewangi'       => 'Pewangi & Pembersih Lantai',
                    'kantong_sampah'=> 'Kantong Sampah',
                    'alat'          => 'Alat Kebersihan (Sapu, Pel, dll)',
                    'lainnya'       => 'Lainnya',
                ],
                'jumlah' => true,
                'persediaan' => true,
            ]),

            'usulan-pengadaan' => [
                'table'   => 'teknisi_usulan_pengadaan',
                'nomor'   => ['nomor_usulan', 'UP'],
                'title'   => 'Usulan Pengadaan Sarana Prasarana',
                'desc'    => 'Usulan kebutuhan barang dari unit kerja beserta estimasi biaya dan status verifikasi.',
                'icon'    => 'fas fa-cart-plus',
                'color'   => 'var(--primary, #3b82f6)',
                'dateCol' => 'tanggal',
                'order'   => ['tanggal', 'desc'],
                'stats'   => [
                    ['label' => 'Total Usulan', 'icon' => 'fas fa-cart-plus', 'color' => 'var(--primary, #3b82f6)'],
                    ['label' => 'Diajukan', 'icon' => 'fas fa-paper-plane', 'color' => 'var(--warning, #f59e0b)', 'where' => ['status', 'diajukan']],
                    ['label' => 'Disetujui', 'icon' => 'fas fa-circle-check', 'color' => 'var(--success, #10b981)', 'where' => ['status', 'disetujui']],
                    ['label' => 'Mendesak', 'icon' => 'fas fa-triangle-exclamation', 'color' => 'var(--danger, #ef4444)', 'where' => ['urgensi', 'mendesak']],
                ],
                'columns' => [
                    'tanggal'        => ['label' => 'Tanggal', 'type' => 'date', 'required' => true, 'default' => 'today'],
                    'unit_pemohon'   => ['label' => 'Unit Pemohon', 'type' => 'string', 'required' => true],
                    'nama_barang'    => ['label' => 'Nama Barang', 'type' => 'string', 'required' => true],
                    'kategori'       => ['label' => 'Kategori', 'type' => 'string'],
                    'jumlah'         => ['label' => 'Jumlah', 'type' => 'int', 'default' => 1],
                    'satuan'         => ['label' => 'Satuan', 'type' => 'string', 'default' => 'unit'],
                    'harga_satuan'   => ['label' => 'Harga Satuan', 'type' => 'decimal'],
                    'estimasi_biaya' => ['label' => 'Estimasi Total', 'type' => 'decimal'],
                    'urgensi'        => ['label' => 'Urgensi', 'type' => 'select', 'options' => ['rendah' => 'Rendah', 'normal' => 'Normal', 'mendesak' => 'Mendesak'], 'default' => 'normal'],
                    'alasan'         => ['label' => 'Alasan Kebutuhan', 'type' => 'text', 'wide' => true],
                    'spesifikasi'    => ['label' => 'Spesifikasi', 'type' => 'text', 'wide' => true],
                    'status'         => ['label' => 'Status', 'type' => 'select', 'options' => ['draf' => 'Draf', 'diajukan' => 'Diajukan', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'terealisasi' => 'Terealisasi'], 'default' => 'diajukan'],
                    'catatan_verifikasi' => ['label' => 'Catatan Verifikasi', 'type' => 'text', 'wide' => true],
                ],
                'list' => ['nama_barang', 'unit_pemohon', 'urgensi', 'estimasi_biaya', 'status'],
            ],

            'tindak-lanjut' => [
                'table'   => 'teknisi_tindak_lanjut',
                'nomor'   => ['nomor', 'TL'],
                'title'   => 'Tindak Lanjut Hasil Supervisi',
                'desc'    => 'Rencana aksi atas temuan supervisi beserta target, realisasi, dan bukti penyelesaian.',
                'icon'    => 'fas fa-list-check',
                'color'   => 'var(--purple, #8b5cf6)',
                'dateCol' => 'tanggal',
                'order'   => ['tanggal', 'desc'],
                'stats'   => [
                    ['label' => 'Total Tindak Lanjut', 'icon' => 'fas fa-list-check', 'color' => 'var(--purple, #8b5cf6)'],
                    ['label' => 'Terbuka', 'icon' => 'fas fa-folder-open', 'color' => 'var(--warning, #f59e0b)', 'where' => ['status', 'terbuka']],
                    ['label' => 'Proses', 'icon' => 'fas fa-spinner', 'color' => 'var(--primary, #3b82f6)', 'where' => ['status', 'proses']],
                    ['label' => 'Selesai', 'icon' => 'fas fa-circle-check', 'color' => 'var(--success, #10b981)', 'where' => ['status', 'selesai']],
                ],
                'columns' => [
                    'tanggal'         => ['label' => 'Tanggal', 'type' => 'date', 'required' => true, 'default' => 'today'],
                    'supervisi_nomor' => ['label' => 'Nomor Supervisi', 'type' => 'string'],
                    'teknisi_nama'    => ['label' => 'Teknisi', 'type' => 'string', 'people' => true],
                    'temuan'          => ['label' => 'Temuan', 'type' => 'text', 'required' => true, 'wide' => true],
                    'rencana_aksi'    => ['label' => 'Rencana Aksi', 'type' => 'text', 'required' => true, 'wide' => true],
                    'penanggung_jawab' => ['label' => 'Penanggung Jawab', 'type' => 'string'],
                    'target_selesai'  => ['label' => 'Target Selesai', 'type' => 'date'],
                    'tanggal_realisasi' => ['label' => 'Tanggal Realisasi', 'type' => 'date'],
                    'bukti_tindak_lanjut' => ['label' => 'Bukti Tindak Lanjut', 'type' => 'text', 'wide' => true],
                    'status'          => ['label' => 'Status', 'type' => 'select', 'options' => ['terbuka' => 'Terbuka', 'proses' => 'Proses', 'selesai' => 'Selesai'], 'default' => 'terbuka'],
                ],
                'list' => ['temuan', 'teknisi_nama', 'target_selesai', 'status'],
            ],

            'aset-mutasi' => [
                'table'   => 'teknisi_aset_mutasi',
                'nomor'   => ['nomor', 'AM'],
                'title'   => 'Mutasi Lokasi Aset',
                'desc'    => 'Riwayat perpindahan aset antar ruang beserta kondisi dan alasan pemindahan.',
                'icon'    => 'fas fa-arrow-right-arrow-left',
                'color'   => 'var(--primary, #3b82f6)',
                'dateCol' => 'tanggal',
                'order'   => ['tanggal', 'desc'],
                'nameMap' => ['aset_id' => 'aset_nama', 'dari_ruang_id' => 'dari_ruang', 'ke_ruang_id' => 'ke_ruang'],
                'stats'   => [
                    ['label' => 'Total Mutasi', 'icon' => 'fas fa-arrow-right-arrow-left', 'color' => 'var(--primary, #3b82f6)'],
                    ['label' => 'Bulan Ini', 'icon' => 'fas fa-calendar-days', 'color' => 'var(--success, #10b981)', 'month' => 'tanggal'],
                    ['label' => 'Aset Terlibat', 'icon' => 'fas fa-boxes-stacked', 'color' => 'var(--purple, #8b5cf6)', 'distinct' => 'aset_id'],
                ],
                'columns' => [
                    'tanggal'     => ['label' => 'Tanggal', 'type' => 'date', 'required' => true, 'default' => 'today'],
                    'aset_id'     => ['label' => 'Aset', 'type' => 'select', 'source' => 'aset', 'required' => true],
                    'kode_aset'   => ['label' => 'Kode Aset', 'type' => 'string'],
                    'dari_ruang_id' => ['label' => 'Dari Ruang', 'type' => 'select', 'source' => 'ruang'],
                    'ke_ruang_id' => ['label' => 'Ke Ruang', 'type' => 'select', 'source' => 'ruang', 'required' => true],
                    'kondisi'     => ['label' => 'Kondisi', 'type' => 'select', 'options' => self::KONDISI, 'default' => 'baik'],
                    'alasan'      => ['label' => 'Alasan Pemindahan', 'type' => 'text', 'wide' => true],
                    'berita_acara' => ['label' => 'Nomor Berita Acara', 'type' => 'string'],
                    'status'      => ['label' => 'Status', 'type' => 'select', 'options' => ['draf' => 'Draf', 'selesai' => 'Selesai'], 'default' => 'selesai'],
                ],
                'list' => ['aset_nama', 'dari_ruang', 'ke_ruang', 'kondisi', 'status'],
            ],

            'peminjaman-ruang' => [
                'table'   => 'sarpras_peminjaman_ruang',
                'nomor'   => ['nomor_pinjam', 'PR'],
                'title'   => 'Peminjaman Ruang & Fasilitas',
                'desc'    => 'Permohonan penggunaan aula, GOR, lapangan, dan ruang rapat beserta persetujuan.',
                'icon'    => 'fas fa-door-open',
                'color'   => 'var(--success, #10b981)',
                'dateCol' => 'tanggal',
                'order'   => ['tanggal', 'desc'],
                'nameMap' => ['ruang_id' => 'ruang_nama'],
                'stats'   => [
                    ['label' => 'Total Permohonan', 'icon' => 'fas fa-door-open', 'color' => 'var(--success, #10b981)'],
                    ['label' => 'Diajukan', 'icon' => 'fas fa-paper-plane', 'color' => 'var(--warning, #f59e0b)', 'where' => ['status', 'diajukan']],
                    ['label' => 'Disetujui', 'icon' => 'fas fa-circle-check', 'color' => 'var(--primary, #3b82f6)', 'where' => ['status', 'disetujui']],
                    ['label' => 'Selesai', 'icon' => 'fas fa-flag-checkered', 'color' => 'var(--success, #10b981)', 'where' => ['status', 'selesai']],
                ],
                'columns' => [
                    'tanggal'        => ['label' => 'Tanggal Pakai', 'type' => 'date', 'required' => true, 'default' => 'today'],
                    'ruang_id'       => ['label' => 'Ruang / Fasilitas', 'type' => 'select', 'source' => 'ruang', 'required' => true],
                    'peminjam_nama'  => ['label' => 'Nama Peminjam', 'type' => 'string', 'required' => true],
                    'peminjam_tipe'  => ['label' => 'Tipe Peminjam', 'type' => 'select', 'options' => ['internal' => 'Internal Sekolah', 'eksternal' => 'Eksternal'], 'default' => 'internal'],
                    'instansi'       => ['label' => 'Instansi / Unit', 'type' => 'string'],
                    'kontak'         => ['label' => 'Kontak', 'type' => 'string'],
                    'keperluan'      => ['label' => 'Keperluan', 'type' => 'string', 'required' => true],
                    'jam_mulai'      => ['label' => 'Jam Mulai', 'type' => 'time'],
                    'jam_selesai'    => ['label' => 'Jam Selesai', 'type' => 'time'],
                    'jumlah_peserta' => ['label' => 'Jumlah Peserta', 'type' => 'int'],
                    'sarana_diminta' => ['label' => 'Sarana Diminta', 'type' => 'text', 'wide' => true],
                    'kondisi_sesudah' => ['label' => 'Kondisi Sesudah', 'type' => 'select', 'options' => self::KONDISI],
                    'status'         => ['label' => 'Status', 'type' => 'select', 'options' => ['diajukan' => 'Diajukan', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'selesai' => 'Selesai'], 'default' => 'diajukan'],
                    'catatan'        => ['label' => 'Catatan', 'type' => 'text', 'wide' => true],
                ],
                'list' => ['ruang_nama', 'peminjam_nama', 'keperluan', 'tanggal', 'status'],
            ],
        ];
    }

    /**
     * Spesifikasi modul log harian (tabel `teknisi_log_harian`, dibedakan kolom `modul`).
     */
    private static function logSpec(string $modul, string $title, string $icon, string $color, string $desc, array $extra): array
    {
        $columns = [
            'tanggal'    => ['label' => 'Tanggal', 'type' => 'date', 'required' => true, 'default' => 'today'],
            'lokasi'     => ['label' => 'Lokasi', 'type' => 'string', 'list' => 'ruang'],
        ];

        if (!empty($extra['area'])) {
            $columns['area_id'] = ['label' => 'Area Kebersihan', 'type' => 'select', 'source' => 'area'];
        }

        $columns['kategori'] = ['label' => 'Kategori', 'type' => 'select', 'options' => $extra['kategori']];

        if (!empty($extra['persediaan'])) {
            $columns['persediaan_id'] = ['label' => 'Barang Persediaan', 'type' => 'select', 'source' => 'persediaan'];
        }

        $columns['subjek'] = ['label' => 'Subjek / Nama Alat', 'type' => 'string', 'required' => true];

        if (!empty($extra['jumlah'])) {
            $columns['jumlah'] = ['label' => 'Jumlah', 'type' => 'int', 'default' => 1];
            $columns['satuan'] = ['label' => 'Satuan', 'type' => 'string', 'default' => 'pcs'];
        }

        if (!empty($extra['satuan']) && empty($extra['jumlah'])) {
            $columns['jumlah'] = ['label' => 'Jumlah / Kapasitas', 'type' => 'int'];
            $columns['satuan'] = ['label' => 'Satuan', 'type' => 'string'];
        }

        $columns['kondisi'] = ['label' => 'Kondisi', 'type' => 'select', 'options' => self::KONDISI];
        $columns['uraian'] = ['label' => 'Uraian Kegiatan', 'type' => 'text', 'wide' => true];
        $columns['temuan'] = ['label' => 'Temuan', 'type' => 'text', 'wide' => true];
        $columns['tindak_lanjut'] = ['label' => 'Tindak Lanjut', 'type' => 'text', 'wide' => true];
        $columns['hasil'] = ['label' => 'Hasil', 'type' => 'text', 'wide' => true];
        $columns['biaya'] = ['label' => 'Biaya', 'type' => 'decimal'];
        $columns['tanggal_berikutnya'] = ['label' => 'Jadwal Berikutnya', 'type' => 'date'];
        $columns['status'] = ['label' => 'Status', 'type' => 'select', 'options' => self::STATUS_UMUM, 'default' => 'selesai'];

        return [
            'table'   => 'teknisi_log_harian',
            'nomor'   => ['nomor', 'LOG'],
            'fixed'   => ['modul' => $modul],
            'title'   => $title,
            'desc'    => $desc,
            'icon'    => $icon,
            'color'   => $color,
            'dateCol' => 'tanggal',
            'order'   => ['tanggal', 'desc'],
            'nameMap' => ['area_id' => 'lokasi', 'persediaan_id' => 'subjek'],
            'stats'   => [
                ['label' => 'Total Catatan', 'icon' => 'fas fa-clipboard-list', 'color' => $color],
                ['label' => 'Bulan Ini', 'icon' => 'fas fa-calendar-days', 'color' => 'var(--success, #10b981)', 'month' => 'tanggal'],
                ['label' => 'Ada Temuan', 'icon' => 'fas fa-triangle-exclamation', 'color' => 'var(--warning, #f59e0b)', 'notNull' => 'temuan'],
                ['label' => 'Perlu Tindak Lanjut', 'icon' => 'fas fa-screwdriver-wrench', 'color' => 'var(--danger, #ef4444)', 'whereIn' => ['kondisi', ['rusak_ringan', 'rusak_berat', 'habis']]],
            ],
            'columns' => $columns,
            'list'    => ['subjek', 'lokasi', 'kategori', 'kondisi', 'status'],
        ];
    }

    public static function get(string $modul): array
    {
        $all = self::all();

        if (!isset($all[$modul])) {
            throw new \InvalidArgumentException("Modul teknisi tidak dikenal: {$modul}");
        }

        return $all[$modul] + ['key' => $modul];
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /**
     * Opsi dropdown dinamis untuk sumber data master.
     *
     * @return array<string, array<int, array{id:string|int, label:string}>>
     */
    public static function sources(): array
    {
        $out = ['ruang' => [], 'aset' => [], 'persediaan' => [], 'area' => []];

        if (Table::exists('sarpras_ruang')) {
            $out['ruang'] = DB::table('sarpras_ruang')
                ->select('id', 'nama_ruang as label')
                ->orderBy('nama_ruang')
                ->get()
                ->map(fn ($r) => ['id' => $r->id, 'label' => (string) $r->label])
                ->all();
        }

        if (Table::exists('sarpras_aset')) {
            $out['aset'] = DB::table('sarpras_aset')
                ->select('id', 'nama_barang', 'kode_aset')
                ->orderBy('nama_barang')
                ->get()
                ->map(fn ($r) => ['id' => $r->id, 'label' => trim($r->nama_barang . ($r->kode_aset ? ' — ' . $r->kode_aset : ''))])
                ->all();
        }

        if (Table::exists('sarpras_persediaan')) {
            $out['persediaan'] = DB::table('sarpras_persediaan')
                ->select('id', 'nama_barang', 'satuan')
                ->orderBy('nama_barang')
                ->get()
                ->map(fn ($r) => ['id' => $r->id, 'label' => trim($r->nama_barang . ' (' . $r->satuan . ')')])
                ->all();
        }

        if (Table::exists('teknisi_area_master')) {
            $out['area'] = DB::table('teknisi_area_master')
                ->select('id', 'nama_area as label')
                ->orderBy('nama_area')
                ->get()
                ->map(fn ($r) => ['id' => $r->id, 'label' => (string) $r->label])
                ->all();
        }

        return $out;
    }
}
