<?php

namespace App\Services;

use App\Support\Table;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Import & Template Excel modul Keamanan / Pos Satpam.
 *
 * Satu layanan untuk 5 modul operasional (Buku Tamu, Pelanggaran, e-Izin,
 * Patroli, Insiden). Template & resolusi (peserta didik via NISN/nama,
 * aturan tata tertib via kode/nama) memakai SimpleXlsxReader/Exporter yang
 * sudah zero-dependency.
 *
 * ponytail: import insert-only (tanpa upsert); tambah kunci unik bila
 * perlu update massal.
 */
class KeamananImportService
{
    public const MODUL_VALID = ['tamu', 'pelanggaran', 'izin', 'patroli', 'insiden'];

    public function isValid(string $modul): bool
    {
        return in_array($modul, self::MODUL_VALID, true);
    }

    public function headers(string $modul): array
    {
        return match ($modul) {
            'tamu' => [
                'tanggal', 'jam_masuk', 'jam_keluar', 'nama_tamu', 'jenis_tamu',
                'nik_tamu', 'nip_tamu', 'jabatan_tamu', 'instansi_asal', 'nomor_kontak',
                'tujuan_bertemu', 'keperluan', 'nama_siswa', 'nisn_siswa', 'rombel_siswa',
                'nomor_kartu_visitor', 'nomor_polisi_kendaraan', 'status',
            ],
            'pelanggaran' => [
                'nisn', 'nama_siswa', 'tanggal_kejadian', 'tempat_kejadian',
                'kode_aturan', 'poin', 'keterangan',
            ],
            'izin' => [
                'nisn', 'nama_siswa', 'tanggal', 'jenis_izin', 'alasan',
                'jam_izin_keluar', 'jam_rencana_kembali', 'status',
            ],
            'patroli' => [
                'tanggal', 'jam_patroli', 'rute_zona', 'kondisi_lingkungan', 'catatan_temuan',
            ],
            'insiden' => [
                'tanggal', 'jam_kejadian', 'lokasi_kejadian', 'judul_insiden', 'kronologi',
                'tingkat_urgensi', 'tindakan_diambil', 'status_penyelesaian',
            ],
            default => [],
        };
    }

    public function contohBaris(string $modul): array
    {
        return match ($modul) {
            'tamu' => [
                [date('Y-m-d'), '08:15', '', 'Budi Santoso', 'umum', '', '', 'Kepala Dinas', 'Dinas Pendidikan', '08123456789', 'Kepala Sekolah', 'Koordinasi program', '', '', '', 'V-001', 'AB 1234 CD', 'berada_di_lokasi'],
                [date('Y-m-d'), '09:30', '10:00', 'Siti Aminah', 'orang_tua', '', '', '', 'Orang Tua / Wali Siswa', '082198765432', 'Wali Kelas', 'Konsultasi nilai', 'Ahmad Fauzi', '1234567890', 'X-1', 'V-002', '', 'sudah_keluar'],
            ],
            'pelanggaran' => [
                ['1234567890', 'Ahmad Fauzi', date('Y-m-d'), 'Gerbang Sekolah', 'TT-001', '5', 'Terlambat masuk sekolah'],
            ],
            'izin' => [
                ['1234567890', 'Ahmad Fauzi', date('Y-m-d'), 'keluar_sebentar', 'Sakit', '09:00', '10:00', 'menunggu_satpam'],
            ],
            'patroli' => [
                [date('Y-m-d'), '21:30', 'Gerbang Utama', 'aman_terkendali', 'Kondisi aman, pintu gerbang terkunci'],
            ],
            'insiden' => [
                [date('Y-m-d'), '13:45', 'Parkir Belakang', 'Pohon tumbang menutup jalan', 'Hujan deras menyebabkan pohon tumbang di area parkir belakang.', 'tinggi', 'Menghubungi petugas kebersihan untuk evakuasi', 'dalam_penanganan'],
            ],
            default => [],
        };
    }

    public function metaTemplate(string $modul): array
    {
        $judul = [
            'tamu' => 'Format Import Buku Tamu',
            'pelanggaran' => 'Format Import Pelanggaran Siswa',
            'izin' => 'Format Import e-Izin Keluar Siswa',
            'patroli' => 'Format Import Log Patroli',
            'insiden' => 'Format Import Laporan Insiden',
        ][$modul] ?? 'Format Import Keamanan';

        return [
            'title' => $judul,
            'subtitle' => 'Sistem Aplikasi Edukasi (SAE) — Diunduh ' . now()->translatedFormat('d F Y H:i'),
            'fields' => [
                'Kolom' => 'Isi sesuai nama kolom persis seperti contoh di bawah.',
                'Tanggal' => 'Gunakan format YYYY-MM-DD (contoh 2026-11-02).',
                'Jam' => 'Gunakan format HH:MM (contoh 08:15).',
                'Resolusi Siswa' => 'Cukup isi nisn atau nama_siswa; sistem mencocokkan otomatis.',
                'Resolusi Aturan' => 'kode_aturan diisi kode/nama tata tertib yang aktif (opsional).',
            ],
        ];
    }

    public function generateTemplateFile(string $modul): string
    {
        $path = storage_path('app/temp_template_keamanan_' . $modul . '_' . uniqid() . '.xlsx');

        SimpleXlsxExporter::export($path, $this->metaTemplate($modul), $this->headers($modul), $this->contohBaris($modul), [
            'sheetName' => 'Import ' . ucfirst($modul),
        ]);

        return $path;
    }

    /**
     * Import berkas Excel menjadi record modul keamanan.
     *
     * @return array{imported:int, skipped:int}
     *
     * @throws \RuntimeException
     */
    public function import(string $modul, UploadedFile $file): array
    {
        if (!$this->isValid($modul)) {
            throw new \RuntimeException('Modul import tidak dikenali: ' . $modul);
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'], true)) {
            throw new \RuntimeException('Format berkas tidak didukung. Harap unggah file .xlsx, .xls, atau .csv.');
        }

        try {
            $rows = SimpleXlsxReader::readFile($file->getRealPath());
        } catch (\Throwable $e) {
            throw new \RuntimeException('Gagal membaca berkas: ' . $e->getMessage());
        }

        if (empty($rows)) {
            throw new \RuntimeException('Berkas Excel kosong atau tidak memiliki data baris.');
        }

        return match ($modul) {
            'tamu' => $this->importTamu($rows),
            'pelanggaran' => $this->importPelanggaran($rows),
            'izin' => $this->importIzin($rows),
            'patroli' => $this->importPatroli($rows),
            'insiden' => $this->importInsiden($rows),
            default => ['imported' => 0, 'skipped' => 0],
        };
    }

    // ------------------------------------------------------------------ Tamu

    private function importTamu(array $rows): array
    {
        $ptkId = session('user')['ptk_id'] ?? null;
        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $n = $this->normalisasiKunci($row);
            $nama = $this->cell($n, ['nama_tamu', 'nama']);
            if ($nama === '') {
                $skipped++;
                continue;
            }

            DB::table('keamanan_buku_tamu')->insert([
                'tanggal' => $this->tanggal($n['tanggal'] ?? null) ?: date('Y-m-d'),
                'jam_masuk' => $this->jam($n['jam_masuk'] ?? null) ?: date('H:i:s'),
                'jam_keluar' => $this->jam($n['jam_keluar'] ?? null),
                'nama_tamu' => $nama,
                'jenis_tamu' => $this->cell($n, ['jenis_tamu'], 'umum'),
                'nik_tamu' => $this->cell($n, ['nik_tamu']) ?: null,
                'nip_tamu' => $this->cell($n, ['nip_tamu']) ?: null,
                'jabatan_tamu' => $this->cell($n, ['jabatan_tamu']) ?: null,
                'instansi_asal' => $this->cell($n, ['instansi_asal']) ?: null,
                'nomor_kontak' => $this->cell($n, ['nomor_kontak']) ?: null,
                'tujuan_bertemu' => $this->cell($n, ['tujuan_bertemu'], '-'),
                'keperluan' => $this->cell($n, ['keperluan'], '-'),
                'nama_siswa' => $this->cell($n, ['nama_siswa']) ?: null,
                'nisn_siswa' => $this->cell($n, ['nisn_siswa']) ?: null,
                'rombel_siswa' => $this->cell($n, ['rombel_siswa']) ?: null,
                'nomor_kartu_visitor' => $this->cell($n, ['nomor_kartu_visitor']) ?: null,
                'nomor_polisi_kendaraan' => $this->cell($n, ['nomor_polisi_kendaraan']) ?: null,
                'petugas_satpam_ptk_id' => $ptkId,
                'is_mandiri' => 0,
                'status' => $this->cell($n, ['status'], 'berada_di_lokasi'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    // ----------------------------------------------------------- Pelanggaran

    private function importPelanggaran(array $rows): array
    {
        $user = session('user') ?? [];
        $ptkId = $user['ptk_id'] ?? null;
        $pelapor = ($user['nama'] ?? 'Petugas Satpam') . ' (Pos Keamanan)';

        $pdMap = $this->mapPesertaDidik();
        $tatibMap = $this->mapTatib();

        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $n = $this->normalisasiKunci($row);
            $pdId = $this->resolvePesertaDidik($n, $pdMap);
            if ($pdId === null) {
                $skipped++;
                continue;
            }

            $tatib = $this->resolveTatib($n, $tatibMap);
            $poin = (int) $this->cell($n, ['poin']);
            if ($poin <= 0) {
                $poin = $tatib ? (int) $tatib->bobot_poin : 5;
            }

            DB::table('kedisiplinan_pelanggaran')->insert([
                'peserta_didik_id' => $pdId,
                'tata_tertib_id' => $tatib?->id,
                'tanggal_kejadian' => $this->tanggal($n['tanggal_kejadian'] ?? null) ?: date('Y-m-d'),
                'tempat_kejadian' => $this->cell($n, ['tempat_kejadian'], 'Pos Keamanan'),
                'poin' => $poin,
                'keterangan' => $this->cell($n, ['keterangan', 'uraian'], 'Import Excel Pos Keamanan'),
                'pelapor_ptk_id' => $ptkId,
                'pelapor_nama' => $pelapor,
                'status_tindak_lanjut' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    // ---------------------------------------------------------------- e-Izin

    private function importIzin(array $rows): array
    {
        $pdMap = $this->mapPesertaDidik();
        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $n = $this->normalisasiKunci($row);
            $pdId = $this->resolvePesertaDidik($n, $pdMap);
            if ($pdId === null) {
                $skipped++;
                continue;
            }

            $tanggal = $this->tanggal($n['tanggal'] ?? null) ?: date('Y-m-d');

            DB::table('peserta_didik_izin_keluar')->insert([
                'nomor_tiket' => $this->cell($n, ['nomor_tiket']) ?: $this->generateNomor('IZN'),
                'peserta_didik_id' => $pdId,
                'rombel_id' => null,
                'tanggal' => $tanggal,
                'jenis_izin' => $this->cell($n, ['jenis_izin'], 'keluar_sebentar'),
                'alasan' => $this->cell($n, ['alasan', 'keterangan'], '-'),
                'jam_izin_keluar' => $this->jam($n['jam_izin_keluar'] ?? null) ?: '00:00:00',
                'jam_rencana_kembali' => $this->jam($n['jam_rencana_kembali'] ?? null),
                'status' => $this->cell($n, ['status'], 'menunggu_satpam'),
                'created_by' => session('user')['nama'] ?? 'Import Excel',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    // -------------------------------------------------------------- Patroli

    private function importPatroli(array $rows): array
    {
        $ptkId = session('user')['ptk_id'] ?? null;
        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $n = $this->normalisasiKunci($row);
            $rute = $this->cell($n, ['rute_zona', 'zona', 'rute']);
            if ($rute === '') {
                $skipped++;
                continue;
            }

            DB::table('keamanan_patroli')->insert([
                'tanggal' => $this->tanggal($n['tanggal'] ?? null) ?: date('Y-m-d'),
                'jam_patroli' => $this->jam($n['jam_patroli'] ?? null) ?: date('H:i:s'),
                'rute_zona' => $rute,
                'kondisi_lingkungan' => $this->cell($n, ['kondisi_lingkungan', 'kondisi'], 'aman_terkendali'),
                'catatan_temuan' => $this->cell($n, ['catatan_temuan', 'catatan']) ?: null,
                'petugas_satpam_ptk_id' => $ptkId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    // -------------------------------------------------------------- Insiden

    private function importInsiden(array $rows): array
    {
        $ptkId = session('user')['ptk_id'] ?? null;
        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $n = $this->normalisasiKunci($row);
            $judul = $this->cell($n, ['judul_insiden', 'judul']);
            if ($judul === '') {
                $skipped++;
                continue;
            }

            DB::table('keamanan_insiden')->insert([
                'nomor_laporan' => $this->cell($n, ['nomor_laporan']) ?: $this->generateNomorInsiden(),
                'tanggal' => $this->tanggal($n['tanggal'] ?? null) ?: date('Y-m-d'),
                'jam_kejadian' => $this->jam($n['jam_kejadian'] ?? null) ?: date('H:i:s'),
                'lokasi_kejadian' => $this->cell($n, ['lokasi_kejadian', 'lokasi'], '-'),
                'judul_insiden' => $judul,
                'kronologi' => $this->cell($n, ['kronologi', 'keterangan'], '-'),
                'kategori_insiden' => $this->cell($n, ['kategori_insiden'], 'umum'),
                'tingkat_urgensi' => $this->cell($n, ['tingkat_urgensi', 'urgensi'], 'sedang'),
                'tindakan_diambil' => $this->cell($n, ['tindakan_diambil']) ?: null,
                'status_penyelesaian' => $this->cell($n, ['status_penyelesaian', 'status'], 'dalam_penanganan'),
                'petugas_satpam_ptk_id' => $ptkId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    // ------------------------------------------------------------- Helpers

    private function normalisasiKunci(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            $key = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', (string) $k))));
            $out[$key] = is_string($v) ? trim($v) : $v;
        }

        return $out;
    }

    private function cell(array $n, array $keys, string $default = ''): string
    {
        foreach ($keys as $k) {
            if (isset($n[$k]) && trim((string) $n[$k]) !== '') {
                return trim((string) $n[$k]);
            }
        }

        return $default;
    }

    private function tanggal(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function jam(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function mapPesertaDidik(): array
    {
        $map = [];
        if (!Table::exists('peserta_didik')) {
            return $map;
        }
        foreach (DB::table('peserta_didik')->select('peserta_didik_id', 'nisn', 'nama')->get() as $p) {
            if (!empty($p->nisn)) {
                $map['nisn:' . trim((string) $p->nisn)] = $p->peserta_didik_id;
            }
            if (!empty($p->nama)) {
                $map['nama:' . strtolower(trim((string) $p->nama))] = $p->peserta_didik_id;
            }
        }

        return $map;
    }

    private function resolvePesertaDidik(array $n, array $map): ?string
    {
        $nisn = $this->cell($n, ['nisn', 'nisn_siswa', 'nis']);
        if ($nisn !== '' && isset($map['nisn:' . $nisn])) {
            return $map['nisn:' . $nisn];
        }

        $nama = $this->cell($n, ['nama_siswa', 'nama', 'nama_pd']);
        if ($nama !== '' && isset($map['nama:' . strtolower($nama)])) {
            return $map['nama:' . strtolower($nama)];
        }

        return null;
    }

    private function mapTatib(): array
    {
        $map = [];
        if (!Table::exists('kedisiplinan_tata_tertib')) {
            return $map;
        }
        foreach (DB::table('kedisiplinan_tata_tertib')->where('is_active', true)->get(['id', 'kode', 'nama_aturan']) as $t) {
            if (!empty($t->kode)) {
                $map['kode:' . strtolower(trim((string) $t->kode))] = $t;
            }
            if (!empty($t->nama_aturan)) {
                $map['nama:' . strtolower(trim((string) $t->nama_aturan))] = $t;
            }
        }

        return $map;
    }

    private function resolveTatib(array $n, array $map): ?object
    {
        $key = $this->cell($n, ['kode_aturan', 'kode_tatib', 'nama_aturan', 'aturan']);
        if ($key === '') {
            return null;
        }
        $lower = strtolower($key);

        return $map['kode:' . $lower] ?? $map['nama:' . $lower] ?? null;
    }

    private function generateNomor(string $prefix): string
    {
        return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }

    private function generateNomorInsiden(): string
    {
        $nomor = $this->generateNomor('INS');
        $guard = 0;
        while (DB::table('keamanan_insiden')->where('nomor_laporan', $nomor)->exists() && $guard < 20) {
            $nomor = $this->generateNomor('INS');
            $guard++;
        }

        return $nomor;
    }
}
