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
        'harian'    => 'Pelaksanaan',
        'inspeksi'  => 'Inspeksi & Tindak Lanjut',
    ];

    /**
     * Modul inspeksi fasilitas yang disatukan di bawah satu menu bertab.
     * Kunci = modul registry, nilai = label tab.
     *
     * @var array<string, string>
     */
    public const INSPEKSI_TABS = [
        'inspeksi-utilitas' => 'Utilitas & Instalasi',
        'apar-k3'           => 'APAR & K3',
        'pest-control'      => 'Pest Control',
        'higiene-kantin'    => 'Higiene Kantin',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
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
            ]),

            'higiene-kantin' => self::logSpec('higiene-kantin', 'Higiene Sanitasi Kantin', 'fas fa-utensils', 'var(--success, #10b981)', 'Pemeriksaan kebersihan kantin, kualitas air minum, dan penjamah makanan.', [
                'kategori' => [
                    'kebersihan_kantin' => 'Kebersihan Ruang Kantin',
                    'air_minum'         => 'Kualitas Air Minum',
                    'penjamah'          => 'Higiene Penjamah Makanan',
                    'penyajian'         => 'Penyajian & Penyimpanan',
                    'lainnya'           => 'Lainnya',
                ],
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
                'bhp' => true,
                // Pemakaian bahan dicatat juga ke Jurnal Pelaksanaan Tugas.
                'pelaksana' => true,
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
                'desc'    => 'Daftar aset dari Inventaris & Aset. Hanya dapat memindahkan aset antar ruang; riwayat perpindahan tercatat otomatis.',
                // Modul khusus: daftar diambil dari `sarpras_aset` (inventaris),
                // hanya aksi pindah (update ruang_id) — tanpa tambah/impor/hapus.
                'inventaris' => true,
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

        $columns['kategori'] = ['label' => 'Kategori', 'type' => 'select', 'options' => $extra['kategori']];

        if (!empty($extra['bhp'])) {
            // Item diambil dari menu Bahan Habis Pakai (laboran_bahan_alat),
            // bukan dari Persediaan Sarpras.
            $columns['persediaan_id'] = ['label' => 'Bahan Habis Pakai (BHP)', 'type' => 'select', 'source' => 'bhp'];
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
            'nameMap' => ['persediaan_id' => 'subjek'],
            'pelaksana' => !empty($extra['pelaksana']),
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
                 'title' => 'Inventaris & aset sarpras sekolah (sumber sarana kegiatan rapat)'],
                ['label' => 'Bahan Habis Pakai', 'icon' => 'fas fa-vials',
                 'route' => 'dashboard.laboran.bhp.index', 'params' => [],
                 'can' => ['menu_laboran', 'menu_sarpras', 'menu_teknisi'],
                 'active' => 'dashboard.laboran.bhp*',
                 'title' => 'Bahan habis pakai (BHP): stok, pemakaian, dan kedaluwarsa'],
            ],

            'rencana' => [
                ['label' => 'Jadwal Area Tugas', 'icon' => 'fas fa-clipboard-list',
                 'route' => 'dashboard.teknisi.area-tugas.index', 'params' => [],
                 'can' => ['menu_teknisi'],
                 'active' => 'dashboard.teknisi.area-tugas*',
                 'title' => 'Jadwal pembagian area tugas kebersihan: harian (pagi/siang/sore), mingguan, bulanan, semester, tahunan'],
                ['label' => 'Pemeliharaan Preventif', 'icon' => 'fas fa-calendar-check',
                 'route' => 'dashboard.teknisi.pemeliharaan', 'params' => [],
                 'can' => ['menu_teknisi_pemeliharaan', 'menu_teknisi'], 'active' => 'dashboard.teknisi.pemeliharaan*',
                 'title' => 'Perencanaan jadwal pemeliharaan berkala (preventif) sarana & prasarana'],
                ['label' => 'Work Order & Perbaikan', 'icon' => 'fas fa-wrench',
                 'route' => 'dashboard.teknisi.work-order', 'params' => [],
                 'can' => ['menu_teknisi_wo', 'menu_teknisi'], 'active' => 'dashboard.teknisi.work-order*',
                 'title' => 'Tiket perbaikan bangunan, listrik, air, AC, IT, kebersihan',
                 'relation' => ['label' => 'Arsip: Jurnal Perbaikan', 'route' => 'dashboard.teknisi.jurnal-perbaikan.index', 'params' => []]],
            ],

            'harian' => [
                ['label' => 'Peminjaman', 'icon' => 'fas fa-hand-holding',
                 'route' => 'dashboard.teknisi.peminjaman.index', 'params' => [],
                 'can' => ['menu_teknisi'],
                 'active' => ['dashboard.teknisi.peminjaman*', 'dashboard.teknisi.sarana-rapat*'],
                 'title' => 'Peminjaman ruang/bangunan & inventaris aset Sarpras (cakupan PJ ruang) + ceklist sarana rapat'],
                ['label' => 'Jurnal Pelaksanaan Tugas', 'icon' => 'fas fa-list-check',
                 'route' => 'dashboard.teknisi.pelaksanaan-harian.index', 'params' => [],
                 'can' => ['menu_teknisi'],
                 'active' => 'dashboard.teknisi.pelaksanaan-harian*',
                 'title' => 'Konfirmasi rencana jadwal area tugas: sudah / belum dikerjakan'],
                ['label' => 'Jurnal Perbaikan Sarpras', 'icon' => 'fas fa-screwdriver-wrench',
                 'route' => 'dashboard.teknisi.jurnal-perbaikan.index', 'params' => [],
                 'can' => ['menu_teknisi'], 'active' => 'dashboard.teknisi.jurnal-perbaikan*',
                 'title' => 'Jurnal Perbaikan Sarpras (tindak lanjut Work Order / tiket perbaikan)'],
                $modulEntry('aset-mutasi', 'Mutasi Lokasi Aset'),
            ],

            'inspeksi' => [
                // Inspeksi fasilitas disatukan jadi satu menu bertab.
                ['label' => 'Inspeksi Fasilitas', 'icon' => 'fas fa-magnifying-glass-chart',
                 'route' => 'dashboard.teknisi.modul.index', 'params' => ['modul' => 'inspeksi-utilitas'],
                 'can' => ['menu_teknisi'], 'active' => 'dashboard.teknisi.modul.*',
                 'title' => 'Inspeksi utilitas, APAR & K3, pest control, higiene kantin',
                 'inspeksiTabs' => true],
                $modulEntry('pemakaian-bahan', 'Pemakaian Bahan Kebersihan'),
                $modulEntry('usulan-pengadaan', 'Usulan Pengadaan', null),
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
        $out = ['ruang' => [], 'aset' => [], 'persediaan' => [], 'bhp' => []];

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

        if (Table::exists('laboran_bahan_alat')) {
            // Bahan Habis Pakai — sumber item pemakaian bahan kebersihan.
            $out['bhp'] = DB::table('laboran_bahan_alat')
                ->where('jenis', 'bahan_habis_pakai')
                ->select('id', 'nama_item', 'satuan')
                ->orderBy('nama_item')
                ->get()
                ->map(fn ($r) => ['id' => $r->id, 'label' => trim($r->nama_item . ($r->satuan ? ' (' . $r->satuan . ')' : ''))])
                ->all();
        }

        return $out;
    }
}
