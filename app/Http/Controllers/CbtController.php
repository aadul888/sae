<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CbtContext;
use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;
use App\Http\Controllers\Concerns\ElearningContext;
use App\Models\CbtPeriodeAsesmen;
use App\Models\CbtSesiUjian;
use App\Models\UjianSoal;
use App\Models\UjianSoalItem;
use App\Services\SimpleXlsxExporter;
use App\Services\SimpleXlsxReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Modul CBT — Bank Soal (bagian "tenang").
 *
 * Lihat .agents/docs/RENCANA_EKOSISTEM.md §14.7: hanya bank soal & definisi ujian.
 * Pelaksanaan ujian serentak (attempt/autosave/submit) BELUM ADA — menunggu fondasi
 * Redis/queue/session §10. Beban rendah, aman dijalankan di stack `file`/`sync`.
 */
class CbtController extends Controller
{
    use ChecksAuthenticatedSession;
    use ElearningContext;
    use CbtContext;

    /** Tab valid halaman CBT. */
    private const TABS = ['dasbor', 'bank-soal', 'ujian', 'periode', 'sesi'];

    /** Header kolom impor soal (alias kolom yang dikenali). */
    private const SOAL_HEADER_ALIAS = [
        'pembelajaran' => ['pembelajaran', 'mapel', 'mata_pelajaran', 'nama_mapel'],
        'tipe' => ['tipe', 'jenis', 'tipe_soal'],
        'pertanyaan' => ['pertanyaan', 'soal', 'question'],
        'opsi_a' => ['opsi_a', 'a', 'pilihan_a'],
        'opsi_b' => ['opsi_b', 'b', 'pilihan_b'],
        'opsi_c' => ['opsi_c', 'c', 'pilihan_c'],
        'opsi_d' => ['opsi_d', 'd', 'pilihan_d'],
        'opsi_e' => ['opsi_e', 'e', 'pilihan_e'],
        'kunci' => ['kunci', 'kunci_jawaban', 'jawaban'],
        'bobot' => ['bobot', 'skor', 'poin'],
        'tingkat' => ['tingkat', 'level', 'kesulitan'],
    ];

    /** Tampilkan halaman CBT bertab (bank soal / ujian). */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) {
            return $res;
        }

        $user = $this->sessionUser();
        $role = $this->sessionRole();
        $ptkId = $this->sessionPtkId();

        $canCreate = $this->canManageCbt($user ?: $role, $role, 'create');
        $canUpdate = $this->canManageCbt($user ?: $role, $role, 'update');
        $canDelete = $this->canManageCbt($user ?: $role, $role, 'delete');

        $perPage = (int) $request->get('per_page', 25);
        if ($perPage < 5 || $perPage > 100) {
            $perPage = 25;
        }
        $activeTab = in_array($request->get('tab'), self::TABS, true) ? $request->get('tab') : 'dasbor';
        // Peserta didik hanya boleh dasbor (jadwal ujian) — bukan bank soal/kunci.
        if ($role === 'peserta_didik') {
            $activeTab = 'dasbor';
        }
        $q = trim((string) $request->get('q', ''));
        $pembelajaranId = trim((string) $request->get('pembelajaran_id', ''));

        $allowed = $this->allowedPembelajaranIds($role, $ptkId);
        $pembelajaranOptions = $this->pembelajaranOptions($allowed);

        $list = match ($activeTab) {
            'ujian' => $this->ujianList($allowed, $q, $pembelajaranId, $perPage),
            'periode' => CbtPeriodeAsesmen::query()
                ->select('*')
                ->selectRaw('(SELECT COUNT(*) FROM ujian WHERE ujian.periode_id = cbt_periode_asesmen.id) as jumlah_ujian')
                ->orderByDesc('id')->paginate($perPage)->withQueryString(),
            'bank-soal' => $this->soalList($allowed, $q, $pembelajaranId, $perPage),
            'sesi' => $this->sesiList($allowed, $q, $perPage),
            default => collect(),
        };

        $stats = $this->stats($allowed);
        $soalPicker = $activeTab === 'ujian' ? $this->soalPicker($allowed) : collect();
        $dasbor = $activeTab === 'dasbor' ? $this->dasborData($role, $ptkId, $allowed, $stats) : null;
        $periodeOptions = in_array($activeTab, ['ujian', 'periode', 'sesi'], true)
            ? CbtPeriodeAsesmen::orderByDesc('id')->get(['id', 'nama', 'status'])
            : collect();

        return view('dashboard.cbt', compact(
            'list',
            'stats',
            'dasbor',
            'activeTab',
            'canCreate',
            'canUpdate',
            'canDelete',
            'q',
            'perPage',
            'pembelajaranId',
            'pembelajaranOptions',
            'soalPicker',
            'periodeOptions',
        ));
    }

    /**
     * Data dasbor CBT per peran (admin / guru / peserta didik).
     * Agregat soal & ujian dibatasi `$allowed` (null = admin/tendik tanpa batas).
     */
    private function dasborData(string $role, ?string $ptkId, ?array $allowed, array $stats): array
    {
        $scopeUjian = function () use ($allowed) {
            $q = DB::table('ujian');
            if ($allowed !== null) {
                empty($allowed) ? $q->whereRaw('1 = 0') : $q->whereIn('ujian.pembelajaran_id', $allowed);
            }

            return $q;
        };

        $data = ['role' => $role, 'stats' => $stats];

        // Periode aktif berlaku untuk semua peran (payung asesmen yang sedang berjalan).
        $data['periode_aktif'] = CbtPeriodeAsesmen::where('status', 'aktif')->orderByDesc('id')->first();

        if ($role === 'peserta_didik') {
            // Pelaksanaan serentak ditahan → hanya daftar ujian terjadwal (read-only).
            $data['ujian_terjadwal'] = (clone $scopeUjian())
                ->whereIn('ujian.status', ['terbit', 'ditutup'])
                ->leftJoin('pembelajaran as p', 'ujian.pembelajaran_id', '=', 'p.pembelajaran_id')
                ->leftJoin('cbt_periode_asesmen as pa', 'ujian.periode_id', '=', 'pa.id')
                ->orderBy('ujian.jadwal_mulai')
                ->limit(10)
                ->select(
                    'ujian.id',
                    'ujian.judul',
                    'ujian.tipe',
                    'ujian.durasi_menit',
                    'ujian.jadwal_mulai',
                    'ujian.jadwal_selesai',
                    'ujian.status',
                    'ujian.token',
                    DB::raw('COALESCE(p.nama_mata_pelajaran, p.mata_pelajaran_id_str, "-") as nama_mapel'),
                    'pa.nama as nama_periode'
                )->get();

            // Jadwal sesi ujian (slot) untuk pembelajaran yang boleh dilihat PD.
            $data['sesi_terjadwal'] = DB::table('cbt_sesi_ujian as s')
                ->leftJoin('pembelajaran as p', 's.pembelajaran_id', '=', 'p.pembelajaran_id')
                ->leftJoin('cbt_periode_asesmen as pa', 's.periode_id', '=', 'pa.id')
                ->when($allowed !== null, fn ($qq) => empty($allowed) ? $qq->whereRaw('1 = 0') : $qq->whereIn('s.pembelajaran_id', $allowed))
                ->whereNotNull('s.tanggal')
                ->orderBy('s.tanggal')->orderBy('s.jam_mulai')
                ->limit(10)
                ->select(
                    's.tanggal',
                    's.jam_mulai',
                    's.jam_selesai',
                    's.ruang',
                    's.kuota',
                    DB::raw('COALESCE(p.nama_mata_pelajaran, p.mata_pelajaran_id_str, "-") as nama_mapel'),
                    'pa.nama as nama_periode'
                )->get();

            return $data;
        }

        // Guru & admin.
        $jenisSoal = DB::table('ujian_soal')
            ->when($allowed !== null, fn ($qq) => empty($allowed) ? $qq->whereRaw('1 = 0') : $qq->whereIn('pembelajaran_id', $allowed))
            ->selectRaw('tipe, COUNT(*) as jml')->groupBy('tipe')->pluck('jml', 'tipe');
        $data['jenis_soal'] = [
            'labels' => $jenisSoal->keys()->map(fn ($t) => match ($t) {
                'pg' => 'Pilihan Ganda',
                'benar_salah' => 'Benar/Salah',
                'isian' => 'Isian',
                'uraian' => 'Uraian',
                default => ucfirst($t),
            })->all(),
            'values' => $jenisSoal->values()->map(fn ($v) => (int) $v)->all(),
        ];

        $data['ujian_terbaru'] = (clone $scopeUjian())
            ->leftJoin('pembelajaran as p', 'ujian.pembelajaran_id', '=', 'p.pembelajaran_id')
            ->leftJoin('cbt_periode_asesmen as pa', 'ujian.periode_id', '=', 'pa.id')
            ->orderByDesc('ujian.id')->limit(6)
            ->select(
                'ujian.id',
                'ujian.judul',
                'ujian.status',
                'ujian.durasi_menit',
                'ujian.created_at',
                DB::raw('COALESCE(p.nama_mata_pelajaran, p.mata_pelajaran_id_str, "-") as nama_mapel'),
                'pa.nama as nama_periode'
            )->get();

        $data['ujian_terbit'] = (clone $scopeUjian())->where('status', 'terbit')->count();
        $data['ujian_periode_aktif'] = $data['periode_aktif']
            ? DB::table('ujian')->where('periode_id', $data['periode_aktif']->id)->count()
            : 0;
        $data['sesi_total'] = DB::table('cbt_sesi_ujian')
            ->when($allowed !== null, fn ($qq) => empty($allowed) ? $qq->whereRaw('1 = 0') : $qq->whereIn('pembelajaran_id', $allowed))
            ->count();

        return $data;
    }

    /**
     * Simpan banyak soal sekaligus (bulk) untuk satu pembelajaran.
     * Dipakai modal "Tambah Banyak Soal" guru/admin.
     */
    public function storeBulk(Request $request)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManageCbt($user ?: $role, $role, 'create')) {
            return $this->deny($request, 'menambah soal');
        }

        $validated = $request->validate([
            'pembelajaran_id' => 'required|string|max:50',
            'soal' => 'required|string',
        ]);

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $validated['pembelajaran_id'])) {
            return $this->deny($request, 'mengelola soal pada pembelajaran ini');
        }

        $rows = json_decode($validated['soal'], true);
        $rows = is_array($rows) ? $rows : [];

        $saatIni = 0;
        $dilewati = 0;
        foreach ($rows as $row) {
            if (! is_array($row)) {
                $dilewati++;
                continue;
            }
            $bersih = $this->normalizeBarisSoal($row, $this->sessionPtkId());
            if ($bersih === null) {
                $dilewati++;
                continue;
            }
            $bersih['pembelajaran_id'] = $validated['pembelajaran_id'];
            UjianSoal::create($bersih);
            $saatIni++;
        }

        return response()->json([
            'status' => 'success',
            'message' => "{$saatIni} soal tersimpan".($dilewati ? ", {$dilewati} dilewati (tidak valid)" : '').'.',
            'tersimpan' => $saatIni,
            'dilewati' => $dilewati,
        ]);
    }

    /** Form/unduh hasil impor soal (Excel/CSV) — proses ada di importSoal(). */
    public function exportSoalTemplate(Request $request)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManageCbt($user ?: $role, $role, 'read')) {
            return $this->deny($request, 'mengunduh template soal');
        }

        $headers = ['pembelajaran', 'tipe', 'pertanyaan', 'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e', 'kunci', 'bobot', 'tingkat'];
        $rows = [[
            'NAMA MAPEL ATAU ID', 'pg', 'Ibu kota Indonesia?', 'Jakarta', 'Bandung', 'Surabaya', 'Medan', '', 'A', '1', 'mudah',
        ], [
            'NAMA MAPEL ATAU ID', 'benar_salah', 'Matahari terbit dari timur.', '', '', '', '', '', 'Benar', '1', 'mudah',
        ], [
            'NAMA MAPEL ATAU ID', 'isian', 'Hasil 2 + 3 adalah ...', '', '', '', '', '', '5', '1', 'sedang',
        ]];

        $path = storage_path('app/template_import_soal_cbt_'.now()->format('YmdHis').'.xlsx');
        $ok = SimpleXlsxExporter::export(
            $path,
            ['title' => 'Template Import Soal CBT', 'subtitle' => 'Kolom `pembelajaran` boleh nama mapel atau ID. Isi mulai baris 2.'],
            $headers,
            $rows,
            ['sheetName' => 'Bank Soal']
        );

        if ($ok && file_exists($path)) {
            return response()->download($path)->deleteFileAfterSend(true);
        }

        $csv = implode(',', $headers)."\n";
        foreach ($rows as $r) {
            $csv .= implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', $v).'"', $r))."\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_import_soal_cbt.csv"',
        ]);
    }

    /**
     * Impor soal dari berkas Excel/CSV. Kolom `pembelajaran` dicocokkan ke
     * pembelajaran_id (bila UUID) atau nama mapel (bila teks).
     */
    public function importSoal(Request $request)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManageCbt($user ?: $role, $role, 'create')) {
            return $this->deny($request, 'mengimpor soal');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:8192',
        ]);

        try {
            $rows = SimpleXlsxReader::readFile($request->file('file')->getRealPath());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca berkas: '.$e->getMessage());
        }

        if (empty($rows)) {
            return back()->with('error', 'Berkas kosong atau format tidak dikenali.');
        }

        $allowed = $this->allowedPembelajaranIds($role, $this->sessionPtkId());
        $peta = $this->petaPembelajaran($allowed);

        $tersimpan = 0;
        $dilewati = 0;
        foreach ($rows as $row) {
            $mapped = $this->mapBarisImpor($row);
            $pid = $this->resolvePembelajaran($mapped['pembelajaran'] ?? '', $peta);
            if ($pid === null) {
                $dilewati++;
                continue;
            }
            $bersih = $this->normalizeBarisSoal($mapped, $this->sessionPtkId());
            if ($bersih === null) {
                $dilewati++;
                continue;
            }
            $bersih['pembelajaran_id'] = $pid;
            UjianSoal::create($bersih);
            $tersimpan++;
        }

        return redirect()->route('dashboard.cbt.index', ['tab' => 'bank-soal'])
            ->with('success', "Import soal selesai: {$tersimpan} tersimpan, {$dilewati} dilewati.");
    }

    /** Peta nama mapel→id dan id→id untuk pencocokan impor. */
    private function petaPembelajaran(?array $allowed): array
    {
        $objs = $this->pembelajaranOptions($allowed);
        $peta = [];
        foreach ($objs as $o) {
            $peta['id:'.$o->pembelajaran_id] = $o->pembelajaran_id;
            $peta[mb_strtolower(trim((string) $o->nama_mapel))] = $o->pembelajaran_id;
        }

        return $peta;
    }

    /** Cocokkan nilai kolom pembelajaran (UUID atau nama mapel) → pembelajaran_id. */
    private function resolvePembelajaran(string $nilai, array $peta): ?string
    {
        $nilai = trim($nilai);
        if ($nilai === '') {
            return null;
        }
        if (isset($peta['id:'.$nilai])) {
            return $peta['id:'.$nilai];
        }
        $kunci = mb_strtolower($nilai);

        return $peta[$kunci] ?? null;
    }

    /** Map satu baris impor (kunci beragam) → bentuk seragam. */
    private function mapBarisImpor(array $row): array
    {
        $norm = [];
        foreach ($row as $k => $v) {
            $key = strtolower(trim(preg_replace('/[^a-z0-9_]/i', '_', (string) $k), '_'));
            $norm[preg_replace('/_+/', '_', $key)] = trim((string) $v);
        }
        $out = [];
        foreach (self::SOAL_HEADER_ALIAS as $target => $aliases) {
            foreach ($aliases as $alias) {
                if (! empty($norm[$alias])) {
                    $out[$target] = $norm[$alias];
                    break;
                }
            }
        }

        return $out;
    }

    /**
     * Validasi & normalkan satu baris soal (bulk/impor) → array siap simpan, atau null.
     * Tipe: pg | benar_salah | isian | uraian. Opsi dari opsi_a..opsi_e (pg).
     */
    private function normalizeBarisSoal(array $row, ?string $ptkId): ?array
    {
        $tipe = strtolower(trim((string) ($row['tipe'] ?? 'pg')));
        $tipe = match ($tipe) {
            'pg', 'pilihan_ganda', 'pilihanganda' => 'pg',
            'benar_salah', 'bs', 'benar/salah' => 'benar_salah',
            'isian', 'isian_singkat' => 'isian',
            'uraian', 'essay', 'esai' => 'uraian',
            default => 'pg',
        };
        if (! in_array($tipe, UjianSoal::TIPE, true)) {
            return null;
        }

        $pertanyaan = trim((string) ($row['pertanyaan'] ?? ''));
        if ($pertanyaan === '') {
            return null;
        }

        $opsi = null;
        $kunci = null;
        if ($tipe === 'pg') {
            $opsi = [];
            foreach (['opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e'] as $kol) {
                $v = trim((string) ($row[$kol] ?? ''));
                if ($v !== '') {
                    $opsi[] = $v;
                }
            }
            // Dukung opsi berbentuk array (bulk JSON).
            if (empty($opsi) && isset($row['opsi']) && is_array($row['opsi'])) {
                $opsi = array_values(array_filter(array_map(fn ($o) => trim((string) $o), $row['opsi']), fn ($o) => $o !== ''));
            }
            if (count($opsi) < 2) {
                return null;
            }
            $kunci = strtoupper(trim((string) ($row['kunci'] ?? '')));
            // Kunci berupa huruf (A..E) → teks opsi; atau langsung teks yang cocok.
            if (preg_match('/^[A-E]$/', $kunci)) {
                $idx = ord($kunci) - 65;
                $kunci = $opsi[$idx] ?? null;
            } elseif ($kunci !== '' && ! in_array($kunci, $opsi, true)) {
                // Cari case-insensitive.
                $match = collect($opsi)->first(fn ($o) => mb_strtolower($o) === mb_strtolower($kunci));
                $kunci = $match;
            }
            if ($kunci === null || $kunci === '') {
                return null;
            }
        } elseif ($tipe === 'benar_salah') {
            $opsi = ['Benar', 'Salah'];
            $raw = mb_strtolower(trim((string) ($row['kunci'] ?? '')));
            $kunci = in_array($raw, ['benar', 'b', 'true', '1'], true) ? 'Benar'
                : (in_array($raw, ['salah', 's', 'false', '0'], true) ? 'Salah' : null);
            if ($kunci === null) {
                return null;
            }
        } elseif ($tipe === 'isian') {
            $kunci = trim((string) ($row['kunci'] ?? '')) ?: null;
        }

        return [
            'tipe' => $tipe,
            'pertanyaan' => mb_substr($pertanyaan, 0, 5000),
            'opsi' => $opsi,
            'kunci' => $kunci !== null ? mb_substr((string) $kunci, 0, 500) : null,
            'bobot' => max(1, min(100, (int) ($row['bobot'] ?? 1))),
            'tingkat' => in_array(($row['tingkat'] ?? ''), UjianSoal::TINGKAT, true) ? $row['tingkat'] : null,
            'created_by' => $ptkId,
        ];
    }


    /** Bank soal untuk pemilih soal pada modal kertas ujian. */
    private function soalPicker(?array $allowed)
    {
        $query = DB::table('ujian_soal')->select('id', 'pembelajaran_id', 'tipe', 'pertanyaan');
        $this->scopePembelajaran($query, 'pembelajaran_id', $allowed);

        return $query->orderByDesc('id')->limit(500)->get();
    }

    /** Query daftar bank soal. */
    private function soalList(?array $allowed, string $q, string $pembelajaranId, int $perPage)
    {
        $query = DB::table('ujian_soal as s')
            ->leftJoin('pembelajaran as p', 's.pembelajaran_id', '=', 'p.pembelajaran_id')
            ->leftJoin('rombongan_belajar as r', 'p.rombongan_belajar_id', '=', 'r.rombongan_belajar_id')
            ->select(
                's.id',
                's.pembelajaran_id',
                's.tipe',
                's.pertanyaan',
                's.gambar',
                's.video_url',
                's.opsi',
                's.opsi_gambar',
                's.kunci',
                's.bobot',
                's.tingkat',
                's.created_at',
                DB::raw('COALESCE(p.nama_mata_pelajaran, p.mata_pelajaran_id_str, "-") as nama_mapel'),
                'r.nama as nama_rombel'
            );

        $this->scopePembelajaran($query, 's.pembelajaran_id', $allowed);

        if ($pembelajaranId !== '') {
            $query->where('s.pembelajaran_id', $pembelajaranId);
        }
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('s.pertanyaan', 'like', "%{$q}%")
                    ->orWhere('p.nama_mata_pelajaran', 'like', "%{$q}%");
            });
        }

        return $query->orderByDesc('s.id')->paginate($perPage)->withQueryString();
    }

    /** Query daftar definisi ujian + jumlah soal terpasang. */
    private function ujianList(?array $allowed, string $q, string $pembelajaranId, int $perPage)
    {
        $query = DB::table('ujian as u')
            ->leftJoin('pembelajaran as p', 'u.pembelajaran_id', '=', 'p.pembelajaran_id')
            ->leftJoin('rombongan_belajar as r', 'p.rombongan_belajar_id', '=', 'r.rombongan_belajar_id')
            ->leftJoin('cbt_periode_asesmen as pa', 'u.periode_id', '=', 'pa.id')
            ->select(
                'u.id',
                'u.pembelajaran_id',
                'u.periode_id',
                'u.judul',
                'u.deskripsi',
                'u.tipe',
                'u.durasi_menit',
                'u.kkm',
                'u.jadwal_mulai',
                'u.jadwal_selesai',
                'u.acak_soal',
                'u.acak_opsi',
                'u.status',
                'u.token',
                DB::raw('COALESCE(p.nama_mata_pelajaran, p.mata_pelajaran_id_str, "-") as nama_mapel'),
                'r.nama as nama_rombel',
                'pa.nama as nama_periode',
                DB::raw('(SELECT COUNT(*) FROM ujian_soal_item i WHERE i.ujian_id = u.id) as jumlah_soal')
            );

        $this->scopePembelajaran($query, 'u.pembelajaran_id', $allowed);

        if ($pembelajaranId !== '') {
            $query->where('u.pembelajaran_id', $pembelajaranId);
        }
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('u.judul', 'like', "%{$q}%")
                    ->orWhere('p.nama_mata_pelajaran', 'like', "%{$q}%");
            });
        }

        return $query->orderByDesc('u.id')->paginate($perPage)->withQueryString();
    }

    /** Statistik ringkas. */
    private function stats(?array $allowed): array
    {
        $soal = DB::table('ujian_soal');
        $ujian = DB::table('ujian');
        $this->scopePembelajaran($soal, 'pembelajaran_id', $allowed);
        $this->scopePembelajaran($ujian, 'pembelajaran_id', $allowed);

        return [
            'soal_total' => (clone $soal)->count(),
            'ujian_total' => (clone $ujian)->count(),
            'ujian_terbit' => (clone $ujian)->where('status', 'terbit')->count(),
            'ujian_draft' => (clone $ujian)->where('status', 'draft')->count(),
        ];
    }

    /** Batasi query ke pembelajaran yang diizinkan (null = semua). */
    private function scopePembelajaran($query, string $column, ?array $allowed): void
    {
        if ($allowed === null) {
            return;
        }
        empty($allowed) ? $query->whereRaw('1 = 0') : $query->whereIn($column, $allowed);
    }

    /** Simpan soal baru (Create). */
    public function store(Request $request)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManageCbt($user ?: $role, $role, 'create')) {
            return $this->deny($request, 'menambah soal');
        }

        $validated = $request->validate($this->soalRules());

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $validated['pembelajaran_id'])) {
            return $this->deny($request, 'mengelola soal pada pembelajaran ini');
        }

        [$opsi, $kunci] = $this->normalisasiJawaban($validated);
        $media = $this->simpanMediaSoal($request, $opsi);

        $soal = UjianSoal::create([
            'pembelajaran_id' => $validated['pembelajaran_id'],
            'tipe' => $validated['tipe'],
            'pertanyaan' => $validated['pertanyaan'],
            'opsi' => $opsi,
            'kunci' => $kunci,
            'bobot' => (int) ($validated['bobot'] ?? 1),
            'tingkat' => $validated['tingkat'] ?? null,
            'created_by' => $this->sessionPtkId(),
        ] + $media);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Soal berhasil disimpan.', 'id' => $soal->id]);
        }

        return back()->with('success', 'Soal berhasil ditambahkan.');
    }

    /** Detail soal (Read). */
    public function show(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManageCbt($user ?: $role, $role, 'read')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        // Peserta didik tidak boleh membaca bank soal (memuat kunci).
        if ($role === 'peserta_didik') {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $soal = UjianSoal::find($id);
        if (! $soal) {
            return response()->json(['status' => 'error', 'message' => 'Soal tidak ditemukan.'], 404);
        }

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $soal->pembelajaran_id, true)) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->soalPayload($soal),
        ]);
    }

    /** Bentuk payload soal (termasuk media) untuk form & pratinjau. */
    private function soalPayload(UjianSoal $soal): array
    {
        $opsi = $soal->opsi ?? [];
        $gambarOpsi = $soal->opsi_gambar ?? [];
        $opsiOut = [];
        foreach ($opsi as $i => $teks) {
            $g = $gambarOpsi[$i] ?? null;
            $opsiOut[] = [
                'teks' => $teks,
                'gambar_url' => $g ? asset('storage/'.ltrim($g, '/')) : null,
            ];
        }

        return [
            'id' => $soal->id,
            'pembelajaran_id' => $soal->pembelajaran_id,
            'mapel' => DB::table('pembelajaran')->where('pembelajaran_id', $soal->pembelajaran_id)
                ->value('nama_mata_pelajaran') ?: '-',
            'tipe' => $soal->tipe,
            'pertanyaan' => $soal->pertanyaan,
            'gambar_url' => $soal->gambarUrl(),
            'video_url' => $soal->video_url,
            'video_embed' => $soal->videoEmbedUrl(),
            'opsi' => $opsi,
            'opsi_media' => $opsiOut,
            'kunci' => $soal->kunci,
            'bobot' => $soal->bobot,
            'tingkat' => $soal->tingkat,
        ];
    }

    /** Perbarui soal (Update). */
    public function update(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManageCbt($user ?: $role, $role, 'update')) {
            return $this->deny($request, 'mengubah soal');
        }

        $soal = UjianSoal::find($id);
        if (! $soal) {
            return $this->reject($request, 'Soal tidak ditemukan.', 404);
        }

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $soal->pembelajaran_id)) {
            return $this->deny($request, 'mengelola soal pada pembelajaran ini');
        }

        $validated = $request->validate($this->soalRules());

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $validated['pembelajaran_id'])) {
            return $this->deny($request, 'mengelola soal pada pembelajaran ini');
        }

        [$opsi, $kunci] = $this->normalisasiJawaban($validated);
        $media = $this->simpanMediaSoal($request, $opsi, $soal);

        $soal->update([
            'pembelajaran_id' => $validated['pembelajaran_id'],
            'tipe' => $validated['tipe'],
            'pertanyaan' => $validated['pertanyaan'],
            'opsi' => $opsi,
            'kunci' => $kunci,
            'bobot' => (int) ($validated['bobot'] ?? 1),
            'tingkat' => $validated['tingkat'] ?? null,
        ] + $media);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Soal berhasil diperbarui.']);
        }

        return back()->with('success', 'Soal berhasil diperbarui.');
    }

    /** Hapus soal (Delete) — sekaligus lepas dari semua kertas ujian. */
    public function destroy(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManageCbt($user ?: $role, $role, 'delete')) {
            return $this->deny($request, 'menghapus soal');
        }

        $soal = UjianSoal::find($id);
        if (! $soal) {
            return $this->reject($request, 'Soal tidak ditemukan.', 404);
        }

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $soal->pembelajaran_id)) {
            return $this->deny($request, 'mengelola soal pada pembelajaran ini');
        }

        UjianSoalItem::where('soal_id', $soal->id)->delete();
        $this->deleteFile($soal->gambar);
        foreach ($soal->opsi_gambar ?? [] as $g) {
            $this->deleteFile($g);
        }
        $soal->delete();

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Soal berhasil dihapus.']);
        }

        return back()->with('success', 'Soal berhasil dihapus.');
    }

    /** Query daftar sesi/jadwal ujian per periode. */
    private function sesiList(?array $allowed, string $q, int $perPage)
    {
        $query = DB::table('cbt_sesi_ujian as s')
            ->leftJoin('pembelajaran as p', 's.pembelajaran_id', '=', 'p.pembelajaran_id')
            ->leftJoin('rombongan_belajar as r', 'p.rombongan_belajar_id', '=', 'r.rombongan_belajar_id')
            ->leftJoin('cbt_periode_asesmen as pa', 's.periode_id', '=', 'pa.id')
            ->select(
                's.id',
                's.periode_id',
                's.pembelajaran_id',
                's.tanggal',
                's.jam_mulai',
                's.jam_selesai',
                's.kuota',
                's.ruang',
                's.catatan',
                DB::raw('COALESCE(p.nama_mata_pelajaran, p.mata_pelajaran_id_str, "-") as nama_mapel'),
                'r.nama as nama_rombel',
                'pa.nama as nama_periode'
            );

        if ($allowed !== null) {
            empty($allowed) ? $query->whereRaw('1 = 0') : $query->whereIn('s.pembelajaran_id', $allowed);
        }
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('pa.nama', 'like', "%{$q}%")
                    ->orWhere('p.nama_mata_pelajaran', 'like', "%{$q}%")
                    ->orWhere('s.ruang', 'like', "%{$q}%");
            });
        }

        return $query->orderByDesc('s.id')->paginate($perPage)->withQueryString();
    }

    /** Simpan sesi ujian baru. */
    public function storeSesi(Request $request)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManageCbt($user ?: $role, $role, 'create')) {
            return $this->deny($request, 'menambah sesi ujian');
        }

        $validated = $request->validate($this->sesiRules());

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $validated['pembelajaran_id'])) {
            return $this->deny($request, 'mengelola sesi pada pembelajaran ini');
        }

        $sesi = CbtSesiUjian::create($validated + ['created_by' => $this->sessionPtkId()]);

        return response()->json(['status' => 'success', 'message' => 'Sesi ujian berhasil disimpan.', 'id' => $sesi->id]);
    }

    /** Detail sesi (Read). */
    public function showSesi(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManageCbt($user ?: $role, $role, 'read')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $sesi = CbtSesiUjian::find($id);
        if (! $sesi) {
            return response()->json(['status' => 'error', 'message' => 'Sesi tidak ditemukan.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $sesi->id,
                'periode_id' => $sesi->periode_id,
                'pembelajaran_id' => $sesi->pembelajaran_id,
                'tanggal' => optional($sesi->tanggal)->format('Y-m-d'),
                'jam_mulai' => $sesi->jam_mulai ? substr((string) $sesi->jam_mulai, 0, 5) : null,
                'jam_selesai' => $sesi->jam_selesai ? substr((string) $sesi->jam_selesai, 0, 5) : null,
                'kuota' => (int) $sesi->kuota,
                'ruang' => $sesi->ruang,
                'catatan' => $sesi->catatan,
            ],
        ]);
    }

    /** Perbarui sesi ujian. */
    public function updateSesi(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManageCbt($user ?: $role, $role, 'update')) {
            return $this->deny($request, 'mengubah sesi ujian');
        }

        $sesi = CbtSesiUjian::find($id);
        if (! $sesi) {
            return $this->reject($request, 'Sesi tidak ditemukan.', 404);
        }

        $validated = $request->validate($this->sesiRules());

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $validated['pembelajaran_id'])) {
            return $this->deny($request, 'mengelola sesi pada pembelajaran ini');
        }

        $sesi->update($validated);

        return response()->json(['status' => 'success', 'message' => 'Sesi ujian berhasil diperbarui.']);
    }

    /** Hapus sesi ujian. */
    public function destroySesi(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManageCbt($user ?: $role, $role, 'delete')) {
            return $this->deny($request, 'menghapus sesi ujian');
        }

        $sesi = CbtSesiUjian::find($id);
        if (! $sesi) {
            return $this->reject($request, 'Sesi tidak ditemukan.', 404);
        }

        $sesi->delete();

        return response()->json(['status' => 'success', 'message' => 'Sesi ujian berhasil dihapus.']);
    }

    /** Aturan validasi sesi ujian. */
    private function sesiRules(): array
    {
        return [
            'periode_id' => 'required|integer|exists:cbt_periode_asesmen,id',
            'pembelajaran_id' => 'required|string|max:50',
            'tanggal' => 'nullable|date',
            'jam_mulai' => 'nullable|date_format:H:i',
            'jam_selesai' => 'nullable|date_format:H:i|after_or_equal:jam_mulai',
            'kuota' => 'nullable|integer|min:0|max:2000',
            'ruang' => 'nullable|string|max:100',
            'catatan' => 'nullable|string|max:500',
        ];
    }

    /** Aturan validasi soal. */
    private function soalRules(): array
    {
        return [
            'pembelajaran_id' => 'required|string|max:50',
            'tipe' => 'required|in:pg,benar_salah,isian,uraian',
            'pertanyaan' => 'required|string|max:5000',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'video_url' => 'nullable|url|max:500',
            'opsi' => 'nullable|array',
            'opsi.*' => 'nullable|string|max:500',
            'opsi_gambar' => 'nullable|array',
            'opsi_gambar.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'kunci' => 'nullable|string|max:500',
            'bobot' => 'nullable|integer|min:1|max:100',
            'tingkat' => 'nullable|in:mudah,sedang,sulit',
        ];
    }

    /**
     * Simpan gambar soal, gambar per opsi, dan video URL.
     * Bila `$soal` diberi (mode update) dan ada gambar baru, gambar lama dihapus.
     *
     * @return array{gambar: ?string, video_url: ?string, opsi_gambar: ?array}
     */
    private function simpanMediaSoal(Request $request, ?array $opsi, ?UjianSoal $soal = null): array
    {
        $media = [
            'video_url' => filled($request->input('video_url')) ? trim((string) $request->input('video_url')) : null,
        ];

        // Gambar soal.
        $gambar = $soal?->gambar;
        if ($request->hasFile('gambar') && $request->file('gambar')->isValid()) {
            $path = $this->saveUpload($request, 'gambar', 'soal');
            if ($path !== false) {
                if ($soal && $gambar) {
                    $this->deleteFile($gambar);
                }
                $gambar = $path;
            }
        }
        if ($request->boolean('hapus_gambar') && $soal && $gambar) {
            $this->deleteFile($gambar);
            $gambar = null;
        }
        $media['gambar'] = $gambar;

        // Gambar per opsi (sejajar dengan daftar opsi non-kosong).
        $gambarOpsi = [];
        $adaGambarOpsi = false;
        if (is_array($opsi)) {
            $lama = $soal?->opsi_gambar ?? [];
            $files = $request->file('opsi_gambar') ?? [];
            foreach (array_keys($opsi) as $idx) {
                $lamaPath = $lama[$idx] ?? null;
                $file = $files[$idx] ?? null;
                $path = null;
                if ($file && $file->isValid()) {
                    $saved = $this->saveUploadFile($file, 'opsi');
                    if ($saved !== false) {
                        if ($lamaPath && $lamaPath !== $saved) {
                            $this->deleteFile($lamaPath);
                        }
                        $path = $saved;
                    }
                } elseif ($lamaPath) {
                    $path = $lamaPath; // pertahankan gambar lama
                }
                $gambarOpsi[] = $path;
                if ($path) {
                    $adaGambarOpsi = true;
                }
            }
        }
        $media['opsi_gambar'] = $adaGambarOpsi ? $gambarOpsi : null;

        return $media;
    }

    /** Simpan satu berkas unggahan (gambar opsi) → path relatif atau false. */
    private function saveUploadFile($file, string $prefix): string|false
    {
        if (! $file || ! $file->isValid()) {
            return false;
        }
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (! in_array($ext, $allowed, true)) {
            return false;
        }
        $name = $prefix.'_'.date('YmdHis').'_'.bin2hex(random_bytes(4)).'.'.$ext;

        return $file->storeAs('elearning', $name, 'public');
    }

    /* ==================== PERIODE ASESMEN ==================== */

    /** Simpan periode asesmen baru. */
    public function storePeriode(Request $request)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManageCbt($user ?: $role, $role, 'create')) {
            return $this->deny($request, 'menambah periode asesmen');
        }

        $validated = $request->validate($this->periodeRules());

        $periode = CbtPeriodeAsesmen::create($validated + ['created_by' => $this->sessionPtkId()]);
        $this->sinkronPeriodeAktif($periode);

        return response()->json(['status' => 'success', 'message' => 'Periode asesmen berhasil disimpan.', 'id' => $periode->id]);
    }

    /** Detail periode (Read). */
    public function showPeriode(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManageCbt($user ?: $role, $role, 'read')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $periode = CbtPeriodeAsesmen::find($id);
        if (! $periode) {
            return response()->json(['status' => 'error', 'message' => 'Periode tidak ditemukan.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $periode->id,
                'nama' => $periode->nama,
                'tipe' => $periode->tipe,
                'tahun_ajaran' => $periode->tahun_ajaran,
                'semester' => $periode->semester,
                'tanggal_mulai' => optional($periode->tanggal_mulai)->format('Y-m-d'),
                'tanggal_selesai' => optional($periode->tanggal_selesai)->format('Y-m-d'),
                'status' => $periode->status,
                'keterangan' => $periode->keterangan,
                'jumlah_ujian' => DB::table('ujian')->where('periode_id', $periode->id)->count(),
            ],
        ]);
    }

    /** Perbarui periode asesmen. */
    public function updatePeriode(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManageCbt($user ?: $role, $role, 'update')) {
            return $this->deny($request, 'mengubah periode asesmen');
        }

        $periode = CbtPeriodeAsesmen::find($id);
        if (! $periode) {
            return $this->reject($request, 'Periode tidak ditemukan.', 404);
        }

        $validated = $request->validate($this->periodeRules());
        $periode->update($validated);
        $this->sinkronPeriodeAktif($periode);

        return response()->json(['status' => 'success', 'message' => 'Periode asesmen berhasil diperbarui.']);
    }

    /** Hapus periode; hanya bila tidak ada ujian tertaut. */
    public function destroyPeriode(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManageCbt($user ?: $role, $role, 'delete')) {
            return $this->deny($request, 'menghapus periode asesmen');
        }

        $periode = CbtPeriodeAsesmen::find($id);
        if (! $periode) {
            return $this->reject($request, 'Periode tidak ditemukan.', 404);
        }

        $tertaut = DB::table('ujian')->where('periode_id', $periode->id)->count();
        if ($tertaut > 0) {
            return $this->reject($request, "Periode masih dipakai {$tertaut} ujian. Pindahkan/lepas ujian dulu.");
        }

        $periode->delete();

        return response()->json(['status' => 'success', 'message' => 'Periode asesmen berhasil dihapus.']);
    }

    /** Hanya satu periode boleh aktif; set eksplisit dari admin. */
    private function sinkronPeriodeAktif(CbtPeriodeAsesmen $periode): void
    {
        if ($periode->status !== 'aktif' || ! $periode->wasRecentlyCreated && ! $periode->wasChanged('status')) {
            return;
        }
        CbtPeriodeAsesmen::where('id', '!=', $periode->id)->where('status', 'aktif')->update(['status' => 'selesai']);
    }

    /** Aturan validasi periode asesmen. */
    private function periodeRules(): array
    {
        return [
            'nama' => 'required|string|max:150',
            'tipe' => 'required|in:'.implode(',', CbtPeriodeAsesmen::TIPE),
            'tahun_ajaran' => 'nullable|string|max:20',
            'semester' => 'nullable|in:'.implode(',', CbtPeriodeAsesmen::SEMESTER),
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'status' => 'required|in:'.implode(',', CbtPeriodeAsesmen::STATUS),
            'keterangan' => 'nullable|string|max:5000',
        ];
    }

    /**
     * Normalisasi opsi & kunci sesuai tipe soal.
     * @return array{0: ?array, 1: ?string}
     */
    private function normalisasiJawaban(array $validated): array
    {
        $tipe = $validated['tipe'];
        $opsi = collect($validated['opsi'] ?? [])
            ->map(fn ($o) => trim((string) $o))
            ->filter(fn ($o) => $o !== '')
            ->values();

        if ($tipe === 'pg') {
            return [$opsi->all(), trim((string) ($validated['kunci'] ?? '')) ?: null];
        }
        if ($tipe === 'benar_salah') {
            return [['Benar', 'Salah'], trim((string) ($validated['kunci'] ?? '')) ?: null];
        }
        if ($tipe === 'isian') {
            return [null, trim((string) ($validated['kunci'] ?? '')) ?: null];
        }

        // uraian: dinilai manual — tanpa kunci.
        return [null, null];
    }
}
