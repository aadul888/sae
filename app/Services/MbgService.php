<?php

namespace App\Services;

use App\Models\Gtk;
use App\Models\PesertaDidik;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Symfony\Component\Process\Process;

/**
 * Service khusus penarikan dan ekspor Data Kebutuhan MBG (Makan Bergizi Gratis).
 * Menghasilkan data multi-sheet sesuai format resmi Satuan Pelayanan MBG.
 */
class MbgService
{
    /**
     * Peta nama orang tua GTK dari acuan template (jika ada).
     */
    private static ?array $gtkParentCache = null;

    public static function getGtkParentMap(): array
    {
        if (self::$gtkParentCache !== null) {
            return self::$gtkParentCache;
        }

        $map = [];
        $templatePath = storage_path('app/templates/template_mbg.xlsx');

        if (file_exists($templatePath)) {
            $cmd = 'python -c "import openpyxl, json; wb = openpyxl.load_workbook(r\''.addslashes($templatePath).'\', data_only=True); ws = wb[\'Guru dan Tendik\']; res = {}; [res.update({str(ws.cell(r,2).value).strip(): str(ws.cell(r,6).value or \'-\').strip()}) for r in range(4, ws.max_row+1) if ws.cell(r,2).value]; print(json.dumps(res))"';
            $output = shell_exec($cmd);
            if ($output) {
                $decoded = json_decode($output, true);
                if (is_array($decoded)) {
                    $map = $decoded;
                }
            }
        }

        self::$gtkParentCache = $map;

        return $map;
    }

    /**
     * Hitung ringkasan statistik penerima MBG.
     */
    public static function getStats(): array
    {
        $k10 = PesertaDidik::where('tingkat_pendidikan_id', '10')->count();
        $k11 = PesertaDidik::where('tingkat_pendidikan_id', '11')->count();
        $k12 = PesertaDidik::where('tingkat_pendidikan_id', '12')->count();
        $totalSiswa = $k10 + $k11 + $k12;

        $guru = Gtk::where(function ($q) {
            $q->where('jenis_ptk_id_str', 'like', '%Guru%')
                ->orWhere('jenis_ptk_id_str', 'like', '%Kepala Sekolah%');
        })->count();

        $tendik = Gtk::where('jenis_ptk_id_str', 'not like', '%Guru%')
            ->where('jenis_ptk_id_str', 'not like', '%Kepala Sekolah%')
            ->count();

        $totalGtk = $guru + $tendik;

        return [
            'total_penerima' => $totalSiswa + $totalGtk,
            'total_siswa' => $totalSiswa,
            'kelas_10' => $k10,
            'kelas_11' => $k11,
            'kelas_12' => $k12,
            'gtk_total' => $totalGtk,
            'guru' => $guru,
            'tendik' => $tendik,
        ];
    }

    /**
     * Ambil data paginasi berdasarkan tab yang aktif.
     */
    public static function getData(string $tab, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $jkFilter = trim((string) ($filters['jk'] ?? ''));

        if ($tab === 'guru_tendik') {
            $query = Gtk::query();

            if ($q !== '') {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nama', 'like', "%{$q}%")
                        ->orWhere('nik', 'like', "%{$q}%")
                        ->orWhere('nip', 'like', "%{$q}%");
                });
            }

            if ($jkFilter === '392') {
                $query->where('jenis_kelamin', 'L');
            } elseif ($jkFilter === '64') {
                $query->where('jenis_kelamin', 'P');
            }

            $query->orderBy('nama', 'asc');

            $paginated = $query->paginate($perPage)->withQueryString();
            $parentMap = self::getGtkParentMap();

            $paginated->getCollection()->transform(function ($item) use ($parentMap) {
                $isGuru = str_contains(strtolower($item->jenis_ptk_id_str ?? ''), 'guru')
                    || str_contains(strtolower($item->jenis_ptk_id_str ?? ''), 'kepala');
                $posisiId = $isGuru ? 2 : 3;
                $posisiLabel = $isGuru ? 'Guru' : 'Tenaga Kependidikan';
                $nik = trim((string) $item->nik);
                $ortu = $parentMap[$nik] ?? '-';

                $tglFormatted = '-';
                if (! empty($item->tanggal_lahir)) {
                    try {
                        $tglFormatted = Carbon::parse($item->tanggal_lahir)->format('d-m-Y');
                    } catch (\Throwable $e) {
                        $tglFormatted = (string) $item->tanggal_lahir;
                    }
                }

                return (object) [
                    'nisn' => '-',
                    'nik' => $nik ?: '-',
                    'nama' => $item->nama,
                    'tanggal_lahir' => $tglFormatted,
                    'jenis_kelamin_id' => $item->jenis_kelamin === 'L' ? 392 : 64,
                    'jenis_kelamin' => $item->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
                    'nama_orang_tua' => $ortu,
                    'posisi' => $posisiId,
                    'posisi_label' => $posisiLabel,
                    'status_menerima' => true,
                    'status_label' => 'Aktif',
                ];
            });

            return $paginated;
        }

        // Siswa (kelas_10, kelas_11, kelas_12)
        $tingkat = match ($tab) {
            'kelas_10' => '10',
            'kelas_11' => '11',
            'kelas_12' => '12',
            default => '10',
        };

        $query = PesertaDidik::where('tingkat_pendidikan_id', $tingkat);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('nisn', 'like', "%{$q}%")
                    ->orWhere('nik', 'like', "%{$q}%");
            });
        }

        if ($jkFilter === '392') {
            $query->where('jenis_kelamin', 'L');
        } elseif ($jkFilter === '64') {
            $query->where('jenis_kelamin', 'P');
        }

        $query->orderBy('nama', 'asc');

        $paginated = $query->paginate($perPage)->withQueryString();

        $paginated->getCollection()->transform(function ($item) {
            $ortu = $item->nama_ibu ?: ($item->nama_ayah ?: ($item->nama_wali ?: '-'));

            $tglFormatted = '-';
            if (! empty($item->tanggal_lahir)) {
                try {
                    $tglFormatted = Carbon::parse($item->tanggal_lahir)->format('d-m-Y');
                } catch (\Throwable $e) {
                    $tglFormatted = (string) $item->tanggal_lahir;
                }
            }

            return (object) [
                'nisn' => $item->nisn ?: '-',
                'nik' => $item->nik ?: '-',
                'nama' => $item->nama,
                'tanggal_lahir' => $tglFormatted,
                'jenis_kelamin_id' => $item->jenis_kelamin === 'L' ? 392 : 64,
                'jenis_kelamin' => $item->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
                'nama_orang_tua' => $ortu,
                'posisi' => 1,
                'posisi_label' => 'Penerima Utama',
                'status_menerima' => true,
                'status_label' => 'Aktif',
            ];
        });

        return $paginated;
    }

    /**
     * Hasilkan file Excel (.xlsx) resmi MBG berisi seluruh sheet.
     * Mengembalikan path berkas sementara.
     */
    public static function exportExcel(): string
    {
        $parentMap = self::getGtkParentMap();

        // 1. Data GTK
        $gtkRows = Gtk::orderBy('nama', 'asc')->get()->map(function ($item) use ($parentMap) {
            $isGuru = str_contains(strtolower($item->jenis_ptk_id_str ?? ''), 'guru')
                || str_contains(strtolower($item->jenis_ptk_id_str ?? ''), 'kepala');
            $nik = trim((string) $item->nik);

            return [
                'nisn' => null,
                'nik' => $nik,
                'nama' => $item->nama,
                'tanggal_lahir' => $item->tanggal_lahir ? Carbon::parse($item->tanggal_lahir)->format('d-m-Y') : '',
                'jenis_kelamin_id' => $item->jenis_kelamin === 'L' ? 392 : 64,
                'nama_orang_tua' => $parentMap[$nik] ?? '-',
                'posisi' => $isGuru ? 2 : 3,
                'status_menerima' => true,
            ];
        })->values()->toArray();

        // 2. Data Siswa per Tingkat
        $fetchSiswa = function (string $tingkat) {
            return PesertaDidik::where('tingkat_pendidikan_id', $tingkat)
                ->orderBy('nama', 'asc')
                ->get()
                ->map(function ($item) {
                    $ortu = $item->nama_ibu ?: ($item->nama_ayah ?: ($item->nama_wali ?: '-'));

                    return [
                        'nisn' => (string) ($item->nisn ?? ''),
                        'nik' => (string) ($item->nik ?? ''),
                        'nama' => (string) ($item->nama ?? ''),
                        'tanggal_lahir' => $item->tanggal_lahir ? Carbon::parse($item->tanggal_lahir)->format('d-m-Y') : '',
                        'jenis_kelamin_id' => $item->jenis_kelamin === 'L' ? 392 : 64,
                        'nama_orang_tua' => (string) $ortu,
                        'posisi' => 1,
                        'status_menerima' => true,
                    ];
                })->values()->toArray();
        };

        $payload = [
            'guru_tendik' => $gtkRows,
            'kelas_10' => $fetchSiswa('10'),
            'kelas_11' => $fetchSiswa('11'),
            'kelas_12' => $fetchSiswa('12'),
        ];

        $tempJson = tempnam(sys_get_temp_dir(), 'mbg_json_').'.json';
        file_put_contents($tempJson, json_encode($payload, JSON_UNESCAPED_UNICODE));

        $tempExcel = tempnam(sys_get_temp_dir(), 'mbg_export_').'.xlsx';
        $scriptPath = base_path('app/Scripts/generate_mbg_excel.py');
        $templatePath = storage_path('app/templates/template_mbg.xlsx');

        $process = new Process(['python', $scriptPath, $tempJson, $tempExcel, $templatePath]);
        $process->setTimeout(120);
        $process->run();

        if (file_exists($tempJson)) {
            @unlink($tempJson);
        }

        if (! $process->isSuccessful() || ! file_exists($tempExcel)) {
            throw new \RuntimeException('Gagal mengekspor berkas Excel MBG: '.$process->getErrorOutput());
        }

        return $tempExcel;
    }
}
