<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;
use App\Http\Controllers\Concerns\ElearningContext;
use App\Models\LearnMateri;
use App\Models\LearnMateriJawaban;
use App\Models\LearnMateriSoal;
use App\Models\LearnPengumpulan;
use App\Models\LearnTugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Modul E-Learning — Materi, Tugas, Pengumpulan & Penilaian.
 *
 * Satu halaman bertab (`?tab=materi|tugas|pengumpulan`). Lihat §14 RENCANA_EKOSISTEM.
 * Prinsip kestabilan:
 * - video TIDAK di-host server (wajib link/embed), hanya dokumen kecil diunggah.
 * - tanpa Redis/queue/worker (sinkron).
 * - data induk (pembelajaran, peserta_didik, gtk) dibaca saja.
 */
class ElearningController extends Controller
{
    use ChecksAuthenticatedSession;
    use ElearningContext;

    /** Tab yang valid pada halaman E-Learning. */
    private const TABS = ['dasbor', 'materi', 'tugas', 'pengumpulan'];

    /**
     * Tampilkan halaman utama modul E-Learning (bertab).
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) {
            return $res;
        }

        $user = $this->sessionUser();
        $role = $this->sessionRole();
        $ptkId = $this->sessionPtkId();

        $canCreate = $this->canManage($user ?: $role, $role, 'create');
        $canRead = $this->canManage($user ?: $role, $role, 'read');
        $canUpdate = $this->canManage($user ?: $role, $role, 'update');
        $canDelete = $this->canManage($user ?: $role, $role, 'delete');

        $perPage = (int) $request->get('per_page', 25);
        if ($perPage < 5 || $perPage > 100) {
            $perPage = 25;
        }
        $activeTab = in_array($request->get('tab'), self::TABS, true) ? $request->get('tab') : 'dasbor';
        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));
        $pembelajaranId = trim((string) $request->get('pembelajaran_id', ''));

        $allowedPembelajaran = $this->allowedPembelajaranIds($role, $ptkId);
        $pembelajaranOptions = $this->pembelajaranOptions($allowedPembelajaran);

        $list = match ($activeTab) {
            'tugas' => $this->tugasList($role, $allowedPembelajaran, $q, $status, $pembelajaranId, $perPage),
            'pengumpulan' => $this->pengumpulanList($role, $ptkId, $allowedPembelajaran, $q, $pembelajaranId, $perPage),
            'materi' => $this->materiList($role, $allowedPembelajaran, $q, $status, $pembelajaranId, $perPage),
            default => $this->materiList($role, $allowedPembelajaran, $q, $status, $pembelajaranId, $perPage),
        };

        $stats = $this->stats($allowedPembelajaran);
        $isPd = $role === 'peserta_didik';

        $dasbor = $activeTab === 'dasbor'
            ? $this->dasborData($role, $ptkId, $allowedPembelajaran, $stats)
            : null;

        return view('dashboard.elearning', compact(
            'list',
            'stats',
            'dasbor',
            'activeTab',
            'isPd',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete',
            'q',
            'status',
            'perPage',
            'pembelajaranId',
            'pembelajaranOptions',
        ));
    }

    /**
     * Data dasbor interaktif per peran (admin / guru / peserta didik).
     * Semua agregat dibatasi `$allowed` (null = admin/tendik tanpa batas).
     */
    private function dasborData(string $role, ?string $ptkId, ?array $allowed, array $stats): array
    {
        $scope = function (string $table) use ($allowed) {
            $q = DB::table($table);
            if ($allowed !== null) {
                empty($allowed) ? $q->whereRaw('1 = 0') : $q->whereIn('pembelajaran_id', $allowed);
            }

            return $q;
        };

        $data = [
            'role' => $role,
            'stats' => $stats,
            'trend' => $this->materiTrend($allowed),
        ];

        if ($role === 'peserta_didik') {
            $pdId = $this->sessionPdId();
            $data['my_materi_selesai'] = $pdId
                ? DB::table('learn_materi_jawaban')->where('peserta_didik_id', $pdId)->count()
                : 0;
            $data['my_tugas_kumpul'] = $pdId
                ? DB::table('learn_pengumpulan')->where('peserta_didik_id', $pdId)->count()
                : 0;
            $data['my_nilai_rata'] = $pdId
                ? round((float) DB::table('learn_pengumpulan')->where('peserta_didik_id', $pdId)->whereNotNull('nilai')->avg('nilai'), 2)
                : 0;
            $data['kuis_terakhir'] = $pdId
                ? DB::table('learn_materi_jawaban as j')
                    ->join('learn_materi as m', 'j.materi_id', '=', 'm.id')
                    ->where('j.peserta_didik_id', $pdId)
                    ->orderByDesc('j.submitted_at')->limit(5)
                    ->select('m.judul', 'j.skor', 'j.benar', 'j.total_soal', 'j.submitted_at')
                    ->get()
                : collect();
            $data['nilai_per_mapel'] = $this->nilaiPerMapelPd($pdId);
        } else {
            // Guru & admin: daftar aktivitas terbaru.
            $data['materi_terbaru'] = (clone $scope('learn_materi'))
                ->orderByDesc('id')->limit(6)
                ->select('id', 'judul', 'tipe', 'status', 'created_at')->get();
            $data['pengumpulan_terbaru'] = DB::table('learn_pengumpulan as k')
                ->join('learn_tugas as t', 'k.tugas_id', '=', 't.id')
                ->leftJoin('peserta_didik as pd', 'k.peserta_didik_id', '=', 'pd.peserta_didik_id')
                ->when($allowed !== null, fn ($qq) => empty($allowed)
                    ? $qq->whereRaw('1 = 0')
                    : $qq->whereIn('t.pembelajaran_id', $allowed))
                ->orderByDesc('k.submitted_at')->limit(6)
                ->select('k.id', 't.judul as tugas', 'pd.nama as nama', 'k.nilai', 'k.submitted_at')
                ->get();
            $data['butuh_penilaian'] = DB::table('learn_pengumpulan')->whereNull('nilai')->count();
            $data['materi_berkuis'] = $this->materiBerkuisCount($allowed);
        }

        return $data;
    }

    /** Tren jumlah materi per 7 hari terakhir (untuk grafik dasbor). */
    private function materiTrend(?array $allowed): array
    {
        $q = DB::table('learn_materi')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay());
        if ($allowed !== null) {
            empty($allowed) ? $q->whereRaw('1 = 0') : $q->whereIn('pembelajaran_id', $allowed);
        }
        $rows = $q->selectRaw('DATE(created_at) as tgl, COUNT(*) as jml')
            ->groupBy('tgl')->pluck('jml', 'tgl');

        $labels = [];
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $labels[] = $d->translatedFormat('D');
            $values[] = (int) ($rows[$d->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /** Jumlah materi yang memiliki minimal satu soal kuis. */
    private function materiBerkuisCount(?array $allowed): int
    {
        $q = DB::table('learn_materi_soal')->select('materi_id')->distinct();
        if ($allowed !== null) {
            $ids = DB::table('learn_materi')
                ->when(empty($allowed), fn ($qq) => $qq->whereRaw('1 = 0'), fn ($qq) => $qq->whereIn('pembelajaran_id', $allowed))
                ->pluck('id');
            if ($ids->isEmpty()) {
                return 0;
            }
            $q->whereIn('materi_id', $ids);
        }

        return $q->count('materi_id');
    }

    /**
     * Nilai rata-rata kuis peserta didik per mata pelajaran (untuk grafik dasbor PD).
     */
    private function nilaiPerMapelPd(?string $pdId): array
    {
        if (! $pdId) {
            return ['labels' => [], 'values' => []];
        }

        $rows = DB::table('learn_materi_jawaban as j')
            ->join('learn_materi as m', 'j.materi_id', '=', 'm.id')
            ->leftJoin('pembelajaran as p', 'm.pembelajaran_id', '=', 'p.pembelajaran_id')
            ->where('j.peserta_didik_id', $pdId)
            ->groupBy('m.pembelajaran_id')
            ->selectRaw('COALESCE(p.nama_mata_pelajaran, p.mata_pelajaran_id_str, "Mapel") as mapel, AVG(j.skor) as rata')
            ->orderByDesc('rata')
            ->get();

        return [
            'labels' => $rows->pluck('mapel')->all(),
            'values' => $rows->map(fn ($r) => round((float) $r->rata, 2))->all(),
        ];
    }

    /** Query daftar materi. */
    private function materiList(string $role, ?array $allowed, string $q, string $status, string $pembelajaranId, int $perPage)
    {
        $query = DB::table('learn_materi as m')
            ->leftJoin('pembelajaran as p', 'm.pembelajaran_id', '=', 'p.pembelajaran_id')
            ->leftJoin('rombongan_belajar as r', 'p.rombongan_belajar_id', '=', 'r.rombongan_belajar_id')
            ->select(
                'm.id',
                'm.pembelajaran_id',
                'm.judul',
                'm.deskripsi',
                'm.tipe',
                'm.url_embed',
                'm.file_path',
                'm.urutan',
                'm.status',
                'm.created_by',
                'm.created_at',
                DB::raw('COALESCE(p.nama_mata_pelajaran, p.mata_pelajaran_id_str, "-") as nama_mapel'),
                'r.nama as nama_rombel',
                'p.ptk_id as ptk_id'
            );

        if ($allowed !== null) {
            empty($allowed) ? $query->whereRaw('1 = 0') : $query->whereIn('m.pembelajaran_id', $allowed);
        }
        if ($role === 'peserta_didik') {
            $query->where('m.status', 'terbit');
        }
        if ($pembelajaranId !== '') {
            $query->where('m.pembelajaran_id', $pembelajaranId);
        }
        if ($status !== '') {
            $query->where('m.status', $status);
        }
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('m.judul', 'like', "%{$q}%")
                    ->orWhere('m.deskripsi', 'like', "%{$q}%")
                    ->orWhere('p.nama_mata_pelajaran', 'like', "%{$q}%");
            });
        }

        $list = $query->orderBy('m.urutan')->orderByDesc('m.id')->paginate($perPage)->withQueryString();

        // Sertakan jumlah soal kuis + hasil kuis peserta didik (bila ada).
        if ($list->isNotEmpty()) {
            $ids = $list->pluck('id')->all();
            $soalCount = LearnMateriSoal::whereIn('materi_id', $ids)
                ->selectRaw('materi_id, COUNT(*) as jml')->groupBy('materi_id')->pluck('jml', 'materi_id');
            $hasilMine = ($role === 'peserta_didik' && $this->sessionPdId())
                ? LearnMateriJawaban::where('peserta_didik_id', $this->sessionPdId())->whereIn('materi_id', $ids)->get()->keyBy('materi_id')
                : collect();

            $list->getCollection()->transform(function ($item) use ($soalCount, $hasilMine) {
                $item->jumlah_soal = (int) ($soalCount[$item->id] ?? 0);
                $item->hasil_saya = $hasilMine->get($item->id)?->skor;

                return $item;
            });
        }

        return $list;
    }

    /** Query daftar tugas. */
    private function tugasList(string $role, ?array $allowed, string $q, string $status, string $pembelajaranId, int $perPage)
    {
        $query = DB::table('learn_tugas as t')
            ->leftJoin('pembelajaran as p', 't.pembelajaran_id', '=', 'p.pembelajaran_id')
            ->leftJoin('rombongan_belajar as r', 'p.rombongan_belajar_id', '=', 'r.rombongan_belajar_id')
            ->select(
                't.id',
                't.pembelajaran_id',
                't.judul',
                't.deskripsi',
                't.deadline',
                't.bobot',
                't.status',
                't.created_at',
                DB::raw('COALESCE(p.nama_mata_pelajaran, p.mata_pelajaran_id_str, "-") as nama_mapel'),
                'r.nama as nama_rombel',
                DB::raw('(SELECT COUNT(*) FROM learn_pengumpulan k WHERE k.tugas_id = t.id) as jumlah_kumpul'),
                DB::raw('(SELECT COUNT(*) FROM learn_pengumpulan k WHERE k.tugas_id = t.id AND k.nilai IS NOT NULL) as jumlah_dinilai')
            );

        if ($allowed !== null) {
            empty($allowed) ? $query->whereRaw('1 = 0') : $query->whereIn('t.pembelajaran_id', $allowed);
        }
        if ($role === 'peserta_didik') {
            $query->whereIn('t.status', ['terbit', 'tutup']);
        }
        if ($pembelajaranId !== '') {
            $query->where('t.pembelajaran_id', $pembelajaranId);
        }
        if ($status !== '') {
            $query->where('t.status', $status);
        }
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('t.judul', 'like', "%{$q}%")
                    ->orWhere('t.deskripsi', 'like', "%{$q}%")
                    ->orWhere('p.nama_mata_pelajaran', 'like', "%{$q}%");
            });
        }

        $list = $query->orderByDesc('t.id')->paginate($perPage)->withQueryString();

        // Untuk peserta didik: sisipkan status pengumpulan & nilai miliknya.
        if ($role === 'peserta_didik' && $list->isNotEmpty()) {
            $mine = LearnPengumpulan::where('peserta_didik_id', $this->sessionPdId())
                ->whereIn('tugas_id', $list->pluck('id')->all())
                ->get()
                ->keyBy('tugas_id');

            $list->getCollection()->transform(function ($item) use ($mine) {
                $row = $mine->get($item->id);
                $item->kumpulan_saya = $row;
                $item->nilai_saya = $row?->nilai;

                return $item;
            });
        }

        return $list;
    }

    /** Query daftar pengumpulan (guru = semua, peserta didik = miliknya). */
    private function pengumpulanList(string $role, ?string $ptkId, ?array $allowed, string $q, string $pembelajaranId, int $perPage)
    {
        $query = DB::table('learn_pengumpulan as k')
            ->join('learn_tugas as t', 'k.tugas_id', '=', 't.id')
            ->leftJoin('pembelajaran as p', 't.pembelajaran_id', '=', 'p.pembelajaran_id')
            ->leftJoin('rombongan_belajar as r', 'p.rombongan_belajar_id', '=', 'r.rombongan_belajar_id')
            ->leftJoin('peserta_didik as pd', 'k.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->select(
                'k.id',
                'k.tugas_id',
                'k.peserta_didik_id',
                'k.jawaban_teks',
                'k.link_url',
                'k.nilai',
                'k.dinilai_oleh',
                'k.submitted_at',
                't.judul as judul_tugas',
                't.bobot',
                't.pembelajaran_id',
                DB::raw('COALESCE(p.nama_mata_pelajaran, p.mata_pelajaran_id_str, "-") as nama_mapel'),
                'r.nama as nama_rombel',
                'pd.nama as nama_peserta_didik'
            );

        if ($role === 'peserta_didik') {
            $query->where('k.peserta_didik_id', $this->sessionPdId());
        }
        if ($allowed !== null) {
            empty($allowed) ? $query->whereRaw('1 = 0') : $query->whereIn('t.pembelajaran_id', $allowed);
        }
        if ($pembelajaranId !== '') {
            $query->where('t.pembelajaran_id', $pembelajaranId);
        }
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('t.judul', 'like', "%{$q}%")
                    ->orWhere('pd.nama', 'like', "%{$q}%")
                    ->orWhere('p.nama_mata_pelajaran', 'like', "%{$q}%");
            });
        }

        return $query->orderByDesc('k.submitted_at')->orderByDesc('k.id')->paginate($perPage)->withQueryString();
    }

    /** Statistik ringkas seluruh tab. */
    private function stats(?array $allowed): array
    {
        $scope = function ($table) use ($allowed) {
            $base = DB::table($table);
            if ($allowed !== null) {
                $base = empty($allowed) ? $base->whereRaw('1 = 0') : $base->whereIn('pembelajaran_id', $allowed);
            }

            return $base;
        };

        $materi = $scope('learn_materi');
        $tugas = $scope('learn_tugas');

        return [
            'total' => (clone $materi)->count(),
            'terbit' => (clone $materi)->where('status', 'terbit')->count(),
            'draft' => (clone $materi)->where('status', 'draft')->count(),
            'link' => (clone $materi)->where('tipe', 'link')->count(),
            'tugas_total' => (clone $tugas)->count(),
            'tugas_terbit' => (clone $tugas)->where('status', 'terbit')->count(),
        ];
    }

    /**
     * Simpan materi baru (Create).
     */
    public function store(Request $request)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManage($user ?: $role, $role, 'create')) {
            return $this->deny($request, 'menambah');
        }

        $validated = $request->validate($this->rules());

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $validated['pembelajaran_id'])) {
            return $this->deny($request, 'mengelola materi pada pembelajaran ini');
        }

        $filePath = null;
        if ($request->hasFile('file_materi')) {
            $filePath = $this->saveUpload($request);
            if ($filePath === false) {
                return $this->reject($request, 'File ditolak: format atau ukuran tidak diizinkan (maks 10 MB).');
            }
        } elseif ($validated['tipe'] === 'file') {
            return $this->reject($request, 'Materi tipe berkas wajib melampirkan dokumen.');
        }

        if (($validated['tipe'] ?? '') === 'link' && empty($validated['url_embed'])) {
            return $this->reject($request, 'Materi tipe tautan wajib mengisi URL.');
        }

        $materi = LearnMateri::create([
            'pembelajaran_id' => $validated['pembelajaran_id'],
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'isi_html' => $this->sanitizeHtml($validated['isi_html'] ?? null),
            'tipe' => $validated['tipe'],
            'url_embed' => $validated['url_embed'] ?? null,
            'file_path' => $filePath,
            'urutan' => (int) ($validated['urutan'] ?? 0),
            'status' => $validated['status'] ?? 'draft',
            'created_by' => $this->sessionPtkId(),
        ]);

        $this->syncSoal($materi->id, $validated['soal'] ?? null);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Materi berhasil disimpan.', 'id' => $materi->id]);
        }

        return back()->with('success', 'Materi E-Learning berhasil ditambahkan.');
    }

    /** Sinkronkan daftar soal kuis materi (ganti penuh — paling sederhana & aman). */
    private function syncSoal(int $materiId, mixed $raw): void
    {
        $rows = $this->normalizeSoalList($raw);

        LearnMateriSoal::where('materi_id', $materiId)->delete();
        foreach ($rows as $row) {
            LearnMateriSoal::create($row + ['materi_id' => $materiId]);
        }
    }

    /**
     * Detail materi (Read).
     */
    public function show(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManage($user ?: $role, $role, 'read')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $materi = LearnMateri::find($id);
        if (! $materi) {
            return response()->json(['status' => 'error', 'message' => 'Materi tidak ditemukan.'], 404);
        }

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $materi->pembelajaran_id, true)) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        // Peserta didik: sembunyikan kunci jawaban (cegah kebocoran) + sertakan hasil kuismu.
        if ($role === 'peserta_didik') {
            $pdId = $this->sessionPdId();
            $jawaban = $pdId
                ? LearnMateriJawaban::where('materi_id', $materi->id)->where('peserta_didik_id', $pdId)->first()
                : null;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $materi->id,
                    'judul' => $materi->judul,
                    'deskripsi' => $materi->deskripsi,
                    'isi_html' => $materi->isi_html,
                    'tipe' => $materi->tipe,
                    'url_embed' => $materi->url_embed,
                    'file_path' => $materi->file_path,
                    'soal' => $materi->soal()->get()->map(fn ($s) => [
                        'id' => $s->id,
                        'tipe' => $s->tipe,
                        'pertanyaan' => $s->pertanyaan,
                        'opsi' => $s->opsi ?? [],
                        'bobot' => $s->bobot,
                    ])->values(),
                    'hasil_saya' => $jawaban ? [
                        'skor' => (float) $jawaban->skor,
                        'benar' => (int) $jawaban->benar,
                        'total_soal' => (int) $jawaban->total_soal,
                        'jawaban' => $jawaban->jawaban ?? [],
                    ] : null,
                ],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $materi->id,
                'pembelajaran_id' => $materi->pembelajaran_id,
                'judul' => $materi->judul,
                'deskripsi' => $materi->deskripsi,
                'isi_html' => $materi->isi_html,
                'tipe' => $materi->tipe,
                'url_embed' => $materi->url_embed,
                'urutan' => $materi->urutan,
                'status' => $materi->status,
                'soal' => $materi->soal()->get()->map(fn ($s) => [
                    'id' => $s->id,
                    'tipe' => $s->tipe,
                    'pertanyaan' => $s->pertanyaan,
                    'opsi' => $s->opsi ?? [],
                    'kunci' => $s->kunci,
                    'bobot' => $s->bobot,
                ])->values(),
            ],
        ]);
    }

    /**
     * Perbarui materi (Update).
     */
    public function update(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManage($user ?: $role, $role, 'update')) {
            return $this->deny($request, 'mengubah');
        }

        $materi = LearnMateri::find($id);
        if (! $materi) {
            return $this->reject($request, 'Materi tidak ditemukan.', 404);
        }

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $materi->pembelajaran_id)) {
            return $this->deny($request, 'mengelola materi pada pembelajaran ini');
        }

        $validated = $request->validate($this->rules());

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $validated['pembelajaran_id'])) {
            return $this->deny($request, 'mengelola materi pada pembelajaran ini');
        }

        $filePath = $materi->file_path;
        if ($request->hasFile('file_materi')) {
            $saved = $this->saveUpload($request);
            if ($saved === false) {
                return $this->reject($request, 'File ditolak: format atau ukuran tidak diizinkan (maks 10 MB).');
            }
            $this->deleteFile($materi->file_path);
            $filePath = $saved;
        }

        $materi->update([
            'pembelajaran_id' => $validated['pembelajaran_id'],
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'isi_html' => $this->sanitizeHtml($validated['isi_html'] ?? null),
            'tipe' => $validated['tipe'],
            'url_embed' => $validated['url_embed'] ?? null,
            'file_path' => $filePath,
            'urutan' => (int) ($validated['urutan'] ?? 0),
            'status' => $validated['status'] ?? 'draft',
        ]);

        $this->syncSoal($materi->id, $validated['soal'] ?? null);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Materi berhasil diperbarui.']);
        }

        return back()->with('success', 'Materi E-Learning berhasil diperbarui.');
    }

    /**
     * Hapus materi (Delete).
     */
    public function destroy(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManage($user ?: $role, $role, 'delete')) {
            return $this->deny($request, 'menghapus');
        }

        $materi = LearnMateri::find($id);
        if (! $materi) {
            return $this->reject($request, 'Materi tidak ditemukan.', 404);
        }

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $materi->pembelajaran_id)) {
            return $this->deny($request, 'mengelola materi pada pembelajaran ini');
        }

        $this->deleteFile($materi->file_path);
        LearnMateriSoal::where('materi_id', $materi->id)->delete();
        LearnMateriJawaban::where('materi_id', $materi->id)->delete();
        $materi->delete();

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Materi berhasil dihapus.']);
        }

        return back()->with('success', 'Materi E-Learning berhasil dihapus.');
    }

    /**
     * Peserta didik menjawab kuis materi — dinilai otomatis dari kunci.
     */
    public function submitKuis(Request $request, $id)
    {
        $role = $this->sessionRole();

        if ($role !== 'peserta_didik') {
            return $this->deny($request, 'menjawab kuis ini');
        }

        $pdId = $this->sessionPdId();
        if (! $pdId) {
            return $this->reject($request, 'Identitas peserta didik tidak ditemukan pada sesi.');
        }

        $materi = LearnMateri::find($id);
        if (! $materi) {
            return $this->reject($request, 'Materi tidak ditemukan.', 404);
        }
        if ($materi->status !== 'terbit' || ! $this->ownsPembelajaran($role, null, $materi->pembelajaran_id, true)) {
            return $this->deny($request, 'menjawab kuis ini');
        }

        $soal = $materi->soal()->get();
        if ($soal->isEmpty()) {
            return $this->reject($request, 'Materi ini belum memiliki soal kuis.');
        }

        $jawaban = $request->input('jawaban', []);
        if (is_string($jawaban)) {
            $jawaban = json_decode($jawaban, true) ?: [];
        }
        if (! is_array($jawaban)) {
            $jawaban = [];
        }
        // Batasi panjang jawaban agar tidak jadi vektor penyalahgunaan.
        $jawaban = array_map(fn ($v) => is_scalar($v) ? mb_substr(trim((string) $v), 0, 2000) : '', $jawaban);

        $hasil = $this->scoreKuis($soal, $jawaban);

        LearnMateriJawaban::updateOrCreate(
            ['materi_id' => $materi->id, 'peserta_didik_id' => $pdId],
            [
                'jawaban' => $jawaban,
                'skor' => $hasil['skor'],
                'benar' => $hasil['benar'],
                'total_soal' => $hasil['total'],
                'submitted_at' => now(),
            ]
        );

        return $request->expectsJson()
            ? response()->json([
                'status' => 'success',
                'message' => "Kuis selesai: {$hasil['benar']} dari {$hasil['total']} benar (skor {$hasil['skor']}).",
                'hasil' => $hasil,
            ])
            : back()->with('success', 'Jawaban kuis tersimpan.');
    }

    /**
     * Rekap hasil kuis sebuah materi untuk guru/admin (siapa, skor, benar/total).
     */
    public function kuisHasil(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManage($user ?: $role, $role, 'read')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $materi = LearnMateri::find($id);
        if (! $materi) {
            return response()->json(['status' => 'error', 'message' => 'Materi tidak ditemukan.'], 404);
        }
        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $materi->pembelajaran_id)) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $totalSoal = $materi->soal()->count();
        $rows = DB::table('learn_materi_jawaban as j')
            ->leftJoin('peserta_didik as pd', 'j.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->where('j.materi_id', $materi->id)
            ->orderByDesc('j.skor')
            ->get([
                'j.peserta_didik_id',
                'j.skor',
                'j.benar',
                'j.total_soal',
                'j.submitted_at',
                'pd.nama as nama',
            ]);

        $rata = $rows->count() ? round((float) $rows->avg('skor'), 2) : 0;
        $tuntas = $rows->where('skor', '>=', 70)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'materi' => $materi->judul,
                'total_soal' => $totalSoal,
                'dikerjakan' => $rows->count(),
                'rata_rata' => $rata,
                'tuntas' => $tuntas,
                'rows' => $rows->map(fn ($r) => [
                    'peserta_didik_id' => $r->peserta_didik_id,
                    'nama' => $r->nama ?? $r->peserta_didik_id,
                    'skor' => (float) $r->skor,
                    'benar' => (int) $r->benar,
                    'total_soal' => (int) $r->total_soal,
                    'submitted_at' => $r->submitted_at,
                ])->values(),
            ],
        ]);
    }

    /** Aturan validasi materi. */
    private function rules(): array
    {
        return [
            'pembelajaran_id' => 'required|string|max:50',
            'judul' => 'required|string|max:200',
            'deskripsi' => 'nullable|string|max:5000',
            'isi_html' => 'nullable|string|max:200000',
            'tipe' => 'required|in:file,link,teks',
            'url_embed' => 'nullable|url|max:500',
            'urutan' => 'nullable|integer|min:0|max:9999',
            'status' => 'nullable|in:draft,terbit',
            'soal' => 'nullable',
            'file_materi' => 'nullable|file|mimes:'.self::ELEARNING_ALLOWED_MIMES.'|max:'.self::ELEARNING_MAX_UPLOAD_KB,
        ];
    }
}
