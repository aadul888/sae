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
     * Pengelompokan menu Teknisi & IT menurut alur pengisian administrasi
     * harian: rujukan → rencana → pelaksanaan → perbaikan → inspeksi/tindak
     * lanjut. Dipakai oleh partial `dash-sidebar-teknisi-menu` dan grid akses
     * cepat, sehingga seluruh titik pemasangan menu memakai satu urutan.
     *
     * @var array<string, string>
     */
    public const GRUP = [
        'master'    => 'Master & Referensi',
        'rencana'   => 'Perencanaan Tugas',
        'harian'    => 'Pelaksanaan Harian',
        'perbaikan' => 'Perbaikan & Pemeliharaan',
        'inspeksi'  => 'Inspeksi & Tindak Lanjut',
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
                'grup'    => 'master',
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
                    'petugas_nama' => ['label' => 'Petugas', 'type' => 'string', 'people' => true, 'derived' => true],
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
                // Petugas area master = PJ ruang; hanya ruang milik PJ yang login
                // yang ditampilkan (koordinator melihat semua).
                'scopePj'  => true,
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
                'grup'    => 'inspeksi',
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

            'aset-mutasi' => [
                'table'   => 'teknisi_aset_mutasi',
                'nomor'   => ['nomor', 'AM'],
                'title'   => 'Mutasi Lokasi Aset',
                'desc'    => 'Riwayat perpindahan aset antar ruang beserta kondisi dan alasan pemindahan.',
                'grup'    => 'inspeksi',
                'icon'    => 'fas fa-arrow-right-arrow-left',
                'color'   => 'var(--primary, #3b82f6)',
                'dateCol' => 'tanggal',
                'order'   => ['tanggal', 'desc'],
                'ruangCol' => 'ke_ruang_id',
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
                'grup'    => 'inspeksi',
                'icon'    => 'fas fa-door-open',
                'color'   => 'var(--success, #10b981)',
                'dateCol' => 'tanggal',
                'order'   => ['tanggal', 'desc'],
                'ruangCol' => 'ruang_id',
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
            'grup'    => 'inspeksi',
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
     * Tautan "tindak lanjut" untuk menu yang sedang dibuka, bila ada.
     *
     * Dipakai header tiap halaman modul agar alur administrasi terasa
     * tersambung tanpa menyunting tiap view. Cocokkan berdasarkan nama route
     * (atau `modul` untuk modul generik).
     *
     * @return array{label:string,route:string,params:array}|null
     */
    public static function relationFor(?string $routeName, ?string $modul = null): ?array
    {
        if (!$routeName) {
            return null;
        }

        foreach (self::menu() as $items) {
            foreach ($items as $item) {
                if (empty($item['relation'])) {
                    continue;
                }
                $cocok = $routeName === $item['route']
                    && (($item['params']['modul'] ?? null) === $modul);
                if ($cocok) {
                    return $item['relation'];
                }
            }
        }

        return null;
    }

    /**
     * Menu Teknisi & IT lengkap & terurut per alur — sumber tunggal untuk
     * sidebar (3 varian) dan grid akses cepat, agar tak ada duplikasi manual.
     *
     * Setiap entri: ['label', 'icon', 'route', 'params', 'can', 'title',
     * 'active' (pola routeIs), 'relation' (label tautan tindak lanjut)].
     * `can` berupa daftar kunci izin: kosong = selalu tampil; salah satu
     * terpenuhi = tampil.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function menu(): array
    {
        // Modul generik (registry) dipetakan ke entrinya.
        $modulEntry = fn (string $key, string $label = null, string $relation = null) => [
            'label'    => $label ?? (self::all()[$key]['title'] ?? $key),
            'icon'     => self::all()[$key]['icon'] ?? 'fas fa-circle',
            'route'    => 'dashboard.teknisi.modul.index',
            'params'   => ['modul' => $key],
            'can'      => ['menu_teknisi'],
            'active'   => 'dashboard.teknisi.modul.*',
            'relation' => $relation
                ? ['label' => $relation, 'route' => 'dashboard.teknisi.modul.index', 'params' => ['modul' => 'usulan-pengadaan']]
                : null,
        ];

        return [
            'master' => [
                ['label' => 'Ruang & Gedung', 'icon' => 'fas fa-door-open',
                 'route' => 'dashboard.sarpras.ruang.index', 'params' => [],
                 'can' => ['menu_sarpras_ruang', 'menu_sarpras', 'menu_teknisi'],
                 'active' => 'dashboard.sarpras.ruang*',
                 'title' => 'Master pemetaan tanggung jawab ruang & gedung (diinput Administrasi Sarpras)'],
                ['label' => 'Inventaris & Aset', 'icon' => 'fas fa-boxes-stacked',
                 'route' => 'dashboard.sarpras.aset.index', 'params' => [],
                 'can' => ['menu_inventaris', 'menu_sarpras'],
                 'active' => 'dashboard.sarpras.aset*',
                 'title' => 'Inventaris & aset sarpras sekolah'],
                $modulEntry('area-master', 'Master Area & Zona'),
            ],

            'rencana' => [
                ['label' => 'Jadwal Area Tugas', 'icon' => 'fas fa-clipboard-list',
                 'route' => 'dashboard.teknisi.area-tugas.index', 'params' => [],
                 'can' => ['menu_teknisi'],
                 'active' => 'dashboard.teknisi.area-tugas*',
                 'title' => 'Jadwal pembagian area tugas kebersihan (Tupoksi #1)',
                 'relation' => ['label' => 'Pelaksanaan: Kebersihan Ruang', 'route' => 'dashboard.teknisi.kebersihan-ruang.index', 'params' => []]],
            ],

            'harian' => [
                ['label' => 'Kebersihan Ruang', 'icon' => 'fas fa-broom',
                 'route' => 'dashboard.teknisi.kebersihan-ruang.index', 'params' => [],
                 'can' => ['menu_teknisi'], 'active' => 'dashboard.teknisi.kebersihan-ruang*',
                 'title' => 'Kebersihan lingkungan & ruang kerja (Tupoksi #2)',
                 'relation' => ['label' => 'Tindak lanjut: Work Order', 'route' => 'dashboard.teknisi.work-order', 'params' => []]],
                ['label' => 'Kebersihan Ruang Rapat', 'icon' => 'fas fa-people-roof',
                 'route' => 'dashboard.teknisi.kebersihan-rapat.index', 'params' => [],
                 'can' => ['menu_teknisi'], 'active' => 'dashboard.teknisi.kebersihan-rapat*',
                 'title' => 'Kebersihan lingkungan ruang rapat (Tupoksi #3)',
                 'relation' => ['label' => 'Sarana: Sarana Kegiatan Rapat', 'route' => 'dashboard.teknisi.sarana-rapat.index', 'params' => []]],
                ['label' => 'Sarana Kegiatan Rapat', 'icon' => 'fas fa-chair',
                 'route' => 'dashboard.teknisi.sarana-rapat.index', 'params' => [],
                 'can' => ['menu_teknisi'], 'active' => 'dashboard.teknisi.sarana-rapat*',
                 'title' => 'Laporan sarana kegiatan rapat (Tupoksi #4)'],
            ],

            'perbaikan' => [
                ['label' => 'Work Order & Perbaikan', 'icon' => 'fas fa-wrench',
                 'route' => 'dashboard.teknisi.work-order', 'params' => [],
                 'can' => ['menu_teknisi_wo', 'menu_teknisi'], 'active' => 'dashboard.teknisi.work-order*',
                 'title' => 'Tiket perbaikan bangunan, listrik, air, AC, IT, kebersihan',
                 'relation' => ['label' => 'Arsip: Jurnal Perbaikan', 'route' => 'dashboard.teknisi.jurnal-perbaikan.index', 'params' => []]],
                ['label' => 'Pemeliharaan Preventif', 'icon' => 'fas fa-calendar-check',
                 'route' => 'dashboard.teknisi.pemeliharaan', 'params' => [],
                 'can' => ['menu_teknisi_pemeliharaan', 'menu_teknisi'], 'active' => 'dashboard.teknisi.pemeliharaan*',
                 'title' => 'Jadwal pemeliharaan berkala',
                 'relation' => ['label' => 'Tindak lanjut: Work Order', 'route' => 'dashboard.teknisi.work-order', 'params' => []]],
                ['label' => 'Jurnal Perbaikan Sarpras', 'icon' => 'fas fa-screwdriver-wrench',
                 'route' => 'dashboard.teknisi.jurnal-perbaikan.index', 'params' => [],
                 'can' => ['menu_teknisi'], 'active' => 'dashboard.teknisi.jurnal-perbaikan*',
                 'title' => 'Pengarsipan & laporan perbaikan sarpras (Tupoksi #5)'],
            ],

            'inspeksi' => [
                $modulEntry('inspeksi-utilitas', 'Inspeksi Utilitas', 'Pengadaan: Usulan Pengadaan'),
                $modulEntry('apar-k3', 'Inspeksi APAR & K3', 'Pengadaan: Usulan Pengadaan'),
                $modulEntry('pest-control', 'Pest Control & Fogging'),
                $modulEntry('higiene-kantin', 'Higiene Sanitasi Kantin'),
                $modulEntry('pemakaian-bahan', 'Pemakaian Bahan Kebersihan', 'Pengadaan: Usulan Pengadaan'),
                $modulEntry('usulan-pengadaan'),
                $modulEntry('aset-mutasi'),
                $modulEntry('peminjaman-ruang'),
            ],
        ];
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
