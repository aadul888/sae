<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeder Peta / Denah Sarpras — mengisi satu denah tapak + 78 area dari
 * berkas "Pemetaan_Sarpras_2026_2027_Genap-v2.xlsx" (Revisi 1).
 *
 * Geometri (x/y/w/h) tidak diisi di sini; digambar lewat editor in-app.
 * Status Excel dipetakan ke `kondisi` (ruang) + `highlight` (flag tampilan).
 *
 * Jalankan: php artisan db:seed --class=SarprasPetaSeeder
 */
class SarprasPetaSeeder extends Seeder
{
    /**
     * [kode_area, kategori, status_excel, zona, keterangan]
     */
    private const AREA = [
        ['RKT 01.01', 'Ruang Kelas / Teori', 'Baik', 'Area Barat Laut (Dekat Mesjid)', 'Deret RKT 01'],
        ['RKT 01.02', 'Ruang Kelas / Teori', 'Baik', 'Area Barat Laut (Dekat Mesjid)', 'Deret RKT 01'],
        ['RKT 03.01', 'Ruang Kelas / Teori', 'Baik', 'Selatan Lapangan / Utara GOR', 'Deret RKT 03'],
        ['RKT 03.02', 'Ruang Kelas / Teori', 'Baik', 'Selatan Lapangan / Utara GOR', 'Deret RKT 03'],
        ['RKT 03.03', 'Ruang Kelas / Teori', 'Baik', 'Selatan Lapangan / Utara GOR', 'Deret RKT 03'],
        ['RKT 14.01', 'Ruang Kelas / Teori', 'Baik', 'Area Timur Laut (Timur RPS Utara)', 'Deret RKT 14'],
        ['RKT 14.02', 'Ruang Kelas / Teori', 'Baik', 'Area Timur Laut (Timur RPS Utara)', 'Deret RKT 14'],
        ['RKT 14.03', 'Ruang Kelas / Teori', 'Baik', 'Area Timur Laut (Timur RPS Utara)', 'Deret RKT 14'],
        ['RKT 15.01', 'Ruang Kelas / Teori', 'Baik', 'Area Timur (Samping RKT 14)', 'Deret RKT 15'],
        ['RKT 15.02', 'Ruang Kelas / Teori', 'Baik', 'Area Timur (Samping RKT 14)', 'Deret RKT 15'],
        ['RKT 02.01', 'Ruang Kelas / Teori', 'Revit 2025', 'Area Barat (Utara Lapangan)', 'Bangun/Ruang Revit 2025 (Revisi: seluruh blok RKT 02)'],
        ['RKT 02.02', 'Ruang Kelas / Teori', 'Revit 2025', 'Area Barat (Barat Lapangan)', 'Bangun/Ruang Revit 2025'],
        ['RKT 02.03', 'Ruang Kelas / Teori', 'Revit 2025', 'Area Barat (Barat Lapangan)', 'Bangun/Ruang Revit 2025'],
        ['RKT 06.01', 'Ruang Kelas / Teori', 'Revit 2025', 'Selatan GOR (Satu blok LAB.IPA)', 'Bangun/Ruang Revit 2025 (Revisi: berubah dari hijau ke merah revit)'],
        ['RKT 06.02', 'Ruang Kelas / Teori', 'Revit 2025', 'Selatan GOR (Satu blok LAB.IPA)', 'Bangun/Ruang Revit 2025 (Revisi: berubah dari hijau ke merah revit)'],
        ['RKT 10.01', 'Ruang Kelas / Teori', 'Revit 2025', 'Area Tengah Utara (Timur RKT 04)', 'Bangun/Ruang Revit 2025'],
        ['RKT 10.02', 'Ruang Kelas / Teori', 'Revit 2025', 'Area Tengah Utara (Utara RG)', 'Bangun/Ruang Revit 2025'],
        ['RKT 16.01', 'Ruang Kelas / Teori', 'Revit 2025', 'Area Timur (Selatan RKT 14/15)', 'Bangun/Ruang Revit 2025'],
        ['RKT 16.02', 'Ruang Kelas / Teori', 'Revit 2025', 'Area Timur (Selatan RKT 14/15)', 'Bangun/Ruang Revit 2025'],
        ['RKT 04.01', 'Ruang Kelas / Teori', 'Rusak 2026', 'Utara Lapangan / Timur RKT 01', 'Bangun/Ruang Rusak 2026'],
        ['RKT 04.02', 'Ruang Kelas / Teori', 'Rusak 2026', 'Utara Lapangan / Timur RKT 01', 'Bangun/Ruang Rusak 2026'],
        ['RKT 04.03', 'Ruang Kelas / Teori', 'Rusak 2026', 'Utara Lapangan / Timur RKT 01', 'Bangun/Ruang Rusak 2026'],
        ['RKT 05.01', 'Ruang Kelas / Teori', 'Rusak 2026', 'Timur RPS 01', 'Bangun/Ruang Rusak 2026'],
        ['RKT 05.02', 'Ruang Kelas / Teori', 'Rusak 2026', 'Timur RPS 01', 'Bangun/Ruang Rusak 2026'],
        ['RKT 07.01', 'Ruang Kelas / Teori', 'Rusak 2026', 'Area Barat Daya (Barat GOR)', 'Bangun/Ruang Rusak 2026'],
        ['RKT 07.02', 'Ruang Kelas / Teori', 'Rusak 2026', 'Area Barat Daya (Barat GOR)', 'Bangun/Ruang Rusak 2026'],
        ['RKT 08.01', 'Ruang Kelas / Teori', 'Rusak 2026', 'Area Barat Daya (Selatan RKT 07)', 'Bangun/Ruang Rusak 2026'],
        ['RKT 08.02', 'Ruang Kelas / Teori', 'Rusak 2026', 'Area Barat Daya (Selatan RKT 07)', 'Bangun/Ruang Rusak 2026'],
        ['RKT 12.01', 'Ruang Kelas / Teori', 'Rusak 2026', 'Selatan RPS 01', 'Bangun/Ruang Rusak 2026'],
        ['RKT 12.02', 'Ruang Kelas / Teori', 'Rusak 2026', 'Selatan RPS 01', 'Bangun/Ruang Rusak 2026'],
        ['RKT 12.03', 'Ruang Kelas / Teori', 'Rusak 2026', 'Selatan RPS 01', 'Bangun/Ruang Rusak 2026'],
        ['RKT 13.01', 'Ruang Kelas / Teori', 'Rusak 2026', 'Selatan RKT 03 / Barat RG', 'Bangun/Ruang Rusak 2026'],
        ['RKT 13.02', 'Ruang Kelas / Teori', 'Rusak 2026', 'Selatan RKT 03 / Barat RG', 'Bangun/Ruang Rusak 2026'],
        ['RKT 11.01', 'Ruang Kepala Sekolah / Blok 11', 'Baik', 'Area Tengah Timur (Timur RG)', 'Warna biru tua (Legenda: Ruang Kepala Sekolah / RKT 11.01)'],
        ['RKT 11.02', 'Ruang Kepala Sekolah / Blok 11', 'Baik', 'Area Tengah Timur (Timur RG)', 'Warna biru tua (Legenda: Ruang Kepala Sekolah / RKT 11.02)'],
        ['RKT 11.03', 'Ruang Kepala Sekolah / Blok 11', 'Baik', 'Area Tengah Timur (Timur RG)', 'Warna biru tua (Legenda: Ruang Kepala Sekolah / RKT 11.03)'],
        ['RKT 11.04', 'Ruang Kepala Sekolah / Blok 11', 'Baik', 'Area Tengah Timur (Timur RG)', 'Warna biru tua (Legenda: Ruang Kepala Sekolah / RKT 11.04)'],
        ['RKT 11.05', 'Ruang Kepala Sekolah / Blok 11', 'Baik', 'Area Tengah Timur (Timur RG)', 'Warna biru tua (Legenda: Ruang Kepala Sekolah / RKT 11.05)'],
        ['LAB.BAH', 'Ruang Laboratorium', 'Baik', 'Area Barat Laut (Dekat Gerbang Utara)', 'Laboratorium Bahasa'],
        ['LAB.KOM (1)', 'Ruang Laboratorium', 'Baik', 'Area Barat Laut (Selatan LAB.BAH)', 'Laboratorium Komputer 1'],
        ['LAB.KOM (2)', 'Ruang Laboratorium', 'Baik', 'Area Barat Laut (Timur LAB.BAH)', 'Laboratorium Komputer 2'],
        ['LAB.IPA', 'Ruang Laboratorium', 'Baik', 'Selatan GOR (Satu blok RKT 06)', 'Laboratorium IPA (Warna biru tua)'],
        ['RPS 01.01', 'Ruang Praktik Siswa (RPS)', 'Baik', 'Area Tengah Selatan', 'Bagian dari kompleks RPS 01'],
        ['RPS 01.02', 'Ruang Praktik Siswa (RPS)', 'Baik', 'Area Tengah Selatan', 'Bagian dari kompleks RPS 01'],
        ['RPS 01.03', 'Ruang Praktik Siswa (RPS)', 'Baik', 'Area Tengah Selatan', 'Bagian dari kompleks RPS 01'],
        ['RPS 01', 'Ruang Praktik Siswa (RPS)', 'Baik', 'Area Tengah Selatan', 'Gedung utama RPS 01'],
        ['RPS (Utara)', 'Ruang Praktik Siswa (RPS)', 'Baik', 'Area Timur Laut (Selatan Kantin)', 'Gedung RPS tanpa nomor kode'],
        ['RPS (Tenggara)', 'Ruang Praktik Siswa (RPS)', 'Baik', 'Area Tenggara (Timur RKT 05)', 'Gedung RPS tanpa nomor kode'],
        ['RPS (Selatan)', 'Ruang Praktik Siswa (RPS)', 'Baik', 'Area Selatan (Selatan TEFA TKR)', 'Gedung RPS memanjang di area selatan'],
        ['RPK (Barat Daya)', 'Ruang Praktik Kerja Jurusan', 'Baik', 'Area Barat Daya (Timur RKT 08)', 'Gedung RPK sisi barat'],
        ['RPK (Tengah)', 'Ruang Praktik Kerja Jurusan', 'Baik', 'Area Tengah Selatan (Timur RKT 12)', 'Gedung RPK sisi tengah'],
        ['RPK (Timur)', 'Ruang Praktik Kerja Jurusan', 'Baik', 'Area Timur (Selatan RKT 11 / RPS)', 'Gedung RPK sisi timur'],
        ['RG', 'Ruang Guru', 'Baik', 'Area Tengah (Selatan RKT 10.02)', 'Ruang Guru (Revisi: berpindah ke area tengah)'],
        ['PUST', 'Ruang Perpustakaan', 'Rusak 2026', 'Area Timur Laut (Utara RKT 14.01)', 'Perpustakaan (Revisi: pindah ke timur laut & berwarna merah rusak 2026)'],
        ['GUDANG', 'Ruang Wakil Kepala Sekolah / Gudang', 'Baik', 'Area Tengah Utara (Selatan Kantin)', 'Berlabel GUDANG (warna magenta sesuai legenda Wakasek)'],
        ['GOR', 'Bangun / Ruang Penunjang', 'Baik', 'Area Barat (Selatan RKT 03)', 'Gedung Olahraga'],
        ['LAP', 'Bangun / Ruang Penunjang', 'Baik', 'Area Barat Laut (Utara RKT 03)', 'Lapangan Upacara / Olahraga'],
        ['KANTIN', 'Bangun / Ruang Penunjang', 'Baik', 'Area Tengah Utara (Utara Gudang)', 'Kantin sekolah'],
        ['TEFA TKR', 'Bangun / Ruang Penunjang', 'Baik', 'Area Selatan (Utara RPS Selatan)', 'Teaching Factory Teknik Kendaraan Ringan'],
        ['POS / SEKURITI', 'Bangun / Ruang Penunjang', 'Baik', 'Area Utara (Dekat Gerbang Utama)', 'Pos penjagaan dekat gerbang'],
        ['MESJ (Utara)', 'Mesjid', 'Baik', 'Area Barat Laut (Selatan LAB.KOM)', 'Mesjid / Mushola sisi barat laut'],
        ['MESJ (Barat)', 'Mesjid', 'Baik', 'Area Barat (Barat Lapangan / Selatan RKT 02)', 'Mesjid sisi barat (Revisi: tambahan bangunan MESJ)'],
        ['GH (Tengah)', 'Green House', 'Baik', 'Area Tengah Selatan (Timur TEFA TKR)', 'Green House 1'],
        ['GH (Selatan)', 'Green House', 'Baik', 'Area Paling Selatan (Barat Lahan 3)', 'Green House 2'],
        ['KM / WC (Utara)', 'Kamar Mandi / WC', 'Baik', 'Area Tengah Utara (Utara RKT 10.01)', 'Sanitasi area utara'],
        ['KM / WC (Timur Laut)', 'Kamar Mandi / WC', 'Baik', 'Area Timur Laut (Barat PUST)', 'Sanitasi dekat Perpustakaan baru'],
        ['KM / WC (Tengah)', 'Kamar Mandi / WC', 'Baik', 'Area Tengah (Utara RPK Timur)', 'Sanitasi di bawah RKT 11'],
        ['KM / WC (Barat Daya)', 'Kamar Mandi / WC', 'Baik', 'Area Barat Daya (Utara RKT 08)', 'Sanitasi deret barat daya'],
        ['KM / WC (Selatan)', 'Kamar Mandi / WC', 'Baik', 'Area Selatan (Timur RPS Selatan)', 'Sanitasi samping RPS selatan'],
        ['GERBANG UTAMA', 'Gerbang', 'Baik', 'Sisi Utara (Jl. Raya Sukanagara - Sindangbarang)', 'Akses masuk utama'],
        ['LAHAN SIAP BANGUN (3)', 'Lahan Siap Bangun', 'Siap Bangun', 'Area Selatan (Selatan RPS Selatan)', 'Lahan bertanda angka 3'],
        ['LAHAN SIAP BANGUN (Tengah)', 'Lahan Siap Bangun', 'Siap Bangun', 'Area Tengah Selatan (Utara GH Tengah)', 'Area arsir biru muda'],
        ['LAHAN KOSONG (Timur 1)', 'Lahan Kosong', 'Lahan Kosong', 'Area Timur (Selatan RKT 16)', 'Lahan kosong sisi timur'],
        ['LAHAN KOSONG (Timur 2)', 'Lahan Kosong', 'Lahan Kosong', 'Area Tenggara (Timur RPS Selatan)', 'Lahan kosong sisi tenggara'],
        ['LAHAN KOSONG / KEBUN', 'Lahan Kosong', 'Lahan Kosong', 'Area Barat Daya (Selatan RPK Barat Daya)', 'Lahan kosong / kebun'],
        ['PARKIR RODA 4 / MOBIL', 'Tempat Parkir Roda 4 / Mobil', 'Baik', 'Beberapa titik (Utara, Tengah, Barat TEFA)', 'Area parkir mobil'],
        ['PARKIR RODA 2 / MOTOR', 'Tempat Parkir Roda 2 / Motor', 'Baik', 'Beberapa titik (Utara, Timur RKT 11, Barat TEFA)', 'Area parkir motor'],
        ['TORRENT AIR', 'Torrent Air', 'Baik', 'Beberapa titik (Dekat Mesjid, RKT 10, RKT 11)', 'Penampungan air sekolah'],
    ];

    /**
     * Pemetaan Status Excel -> [kondisi, highlight, status_visual].
     * `kondisi` menyesuaikan enum modul Ruang; `highlight` flag tampilan lama;
     * `status_visual` = 5 status tampilan resmi peta revisi 1.
     */
    private const STATUS_MAP = [
        'Baik'         => ['baik', null, 'baik'],
        'Revit 2025'   => ['baik', 'revit_2025', 'revit_2025'],
        'Rusak 2026'   => ['rusak_berat', 'rusak_2026', 'rusak_2026'],
        'Siap Bangun'  => ['baik', 'siap_bangun', 'siap_bangun'],
        'Lahan Kosong' => ['baik', 'lahan_kosong', 'lahan_kosong'],
    ];

    public function run(): void
    {
        $sekolahId = DB::table('sekolah')->value('sekolah_id');

        $petaId = DB::table('sarpras_peta')->where('tingkat', 'tapak')->value('id');

        if (!$petaId) {
            $petaId = DB::table('sarpras_peta')->insertGetId([
                'sekolah_id'     => $sekolahId,
                'nama'           => 'Site Plan SMK Negeri 1 Pagelaran',
                'tingkat'        => 'tapak',
                'skala'          => '1:125',
                'luas_tanah'     => 30000,
                'view_box'       => '0 0 1000 700',
                'latar_sumber'   => 'upload',
                'latar_atribusi' => null,
                'latar_opacity'  => 70,
                'revisi_ke'      => 0,
                'is_aktif'       => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        // Peta kode ruang ternormalisasi -> id (untuk auto-match ruang_id)
        $ruangMap = [];
        foreach (DB::table('sarpras_ruang')->select('id', 'kode_ruang')->get() as $r) {
            $ruangMap[$this->normalizeKode($r->kode_ruang)] = $r->id;
        }

        $now = now();
        $rows = [];

        // Denah landscape 1000x700, dikelompokkan per zona agar posisi kasar
        // menyerupai site plan asli (utara di atas, barat di kiri). Posisi presisi
        // dirapikan admin lewat Mode Edit Layout.
        $cellW = 88;
        $cellH = 52;
        $gapX  = 6;
        $gapY  = 6;
        $zoneCount = [];

        foreach (self::AREA as $i => [$kode, $kategori, $status, $zona, $keterangan]) {
            [$kondisi, $highlight, $statusVisual] = self::STATUS_MAP[$status] ?? ['baik', null, 'baik'];

            [$anchorX, $anchorY] = self::zoneAnchor($zona);
            $n = $zoneCount[$zona] = ($zoneCount[$zona] ?? 0) + 1;
            $n--;

            $col = $n % 3;
            $row = intdiv($n, 3);

            $x = $anchorX + $col * ($cellW + $gapX);
            $y = $anchorY + $row * ($cellH + $gapY);
            $w = $cellW;
            $h = $cellH;

            $rows[] = [
                'peta_id'       => $petaId,
                'ruang_id'      => $ruangMap[$this->normalizeKode($kode)] ?? null,
                'kode_area'     => $kode,
                'nama_area'     => $kode,
                'kategori'      => $kategori,
                'zona'          => $zona,
                'bentuk'        => 'rect',
                'x'             => $x,
                'y'             => $y,
                'w'             => $w,
                'h'             => $h,
                'label_x'       => $x + $w / 2,
                'label_y'       => $y + $h / 2,
                'highlight'     => $highlight,
                'status_visual' => $statusVisual,
                'map_coordinates' => json_encode(
                    ['x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'rotate' => 0],
                    JSON_UNESCAPED_UNICODE
                ),
                'keterangan'    => $keterangan,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        // Idempotent: hapus area + revisi denah ini lalu isi ulang
        DB::table('sarpras_peta_area')->where('peta_id', $petaId)->delete();
        DB::table('sarpras_peta_area')->insert($rows);
        DB::table('sarpras_peta_revisi')->where('peta_id', $petaId)->delete();

        // Snapshot revisi ke-0
        $snapshot = json_encode(
            DB::table('sarpras_peta_area')->where('peta_id', $petaId)->get()->toArray(),
            JSON_UNESCAPED_UNICODE
        );
        DB::table('sarpras_peta')->where('id', $petaId)->update(['revisi_ke' => 0, 'updated_at' => $now]);

        DB::table('sarpras_peta_revisi')->insert([
            'peta_id'             => $petaId,
            'revisi_ke'           => 0,
            'snapshot_geometri'   => $snapshot,
            'ringkasan_perubahan' => 'Seeding awal: ' . count(self::AREA) . ' area dari Excel Pemetaan Sarpras (kotak-kira).',
            'diubah_oleh'         => 'seeder',
            'created_at'          => $now,
        ]);

        $this->command?->info('SarprasPetaSeeder: ' . count(self::AREA) . ' area + 1 revisi ke-0 dibuat.');
    }

    /**
     * Titik jangkar (x,y) per zona agar blok tersusun menyerupai site plan:
     * utara di atas, barat di kiri, timur di kanan. Nilai kasar — admin
     * merapikan presisi lewat Mode Edit Layout.
     *
     * @return array{0:int,1:int}
     */
    private static function zoneAnchor(string $zona): array
    {
        $z = Str::lower($zona);

        if (Str::contains($z, ['barat laut'])) return [20, 20];
        if (Str::contains($z, ['timur laut'])) return [620, 20];
        if (Str::contains($z, ['tenggara']))   return [700, 430];
        if (Str::contains($z, ['barat daya'])) return [20, 430];
        if (Str::contains($z, ['paling selatan'])) return [340, 620];
        if (Str::contains($z, ['barat']))      return [20, 220];
        if (Str::contains($z, ['timur']))      return [700, 220];
        if (Str::contains($z, ['selatan']))    return [340, 520];
        if (Str::contains($z, ['utara']))      return [340, 20];

        return [340, 260]; // tengah
    }

    /**
     * Normalisasi kode ruang agar "RKT 01.01", "rkt-01-01", "RKT_01.01"
     * dianggap sama. Dipakai untuk auto-match ruang_id saat impor.
     */
    private function normalizeKode(?string $kode): string
    {
        return Str::upper(preg_replace('/[^A-Za-z0-9]/', '', (string) $kode));
    }
}
