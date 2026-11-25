<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;
use App\Models\RolePermission;
use App\Models\SarprasPeta;
use App\Models\SarprasPetaArea;
use App\Services\SimpleXlsxExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Peta / Denah Sekolah (Master Data) — Sarpras.
 *
 * Rilis editor: kanvas SVG zoom/pan, latar overlay, mode edit layout
 * (drag/resize/rotate), 5 status visual, legenda interaktif, ekspor
 * PNG/PDF/Excel, dan impor dari gambar denah via Gemini Vision.
 *
 * Lihat .agents/docs/RENCANA_PETA_DENAH_SARPRAS.md
 */
class SarprasPetaController extends Controller
{
    use ChecksAuthenticatedSession;

    /**
     * 5 status tampilan resmi peta revisi 1.
     * label + warna dipakai bersama view, legenda, cetak, dan ekspor.
     */
    public const STATUSES = [
        'baik'         => ['label' => 'Baik', 'warna' => '#10b981', 'urutan' => 1],
        'revit_2025'   => ['label' => 'Revit 2025', 'warna' => '#f97316', 'urutan' => 2],
        'rusak_2026'   => ['label' => 'Rusak 2026', 'warna' => '#991b1b', 'urutan' => 3],
        'siap_bangun'  => ['label' => 'Siap Bangun', 'warna' => '#3b82f6', 'urutan' => 4],
        'lahan_kosong' => ['label' => 'Lahan Kosong', 'warna' => '#06b6d4', 'urutan' => 5],
    ];

    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_peta_sekolah', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'update');

        $peta = SarprasPeta::query()
            ->where('tingkat', 'tapak')
            ->orderByDesc('is_aktif')
            ->orderByDesc('id')
            ->first();

        if (!$peta) {
            return view('dashboard.sarpras.peta', [
                'peta'     => null,
                'areas'    => collect(),
                'lines'    => [],
                'mapData'  => null,
                'stat'     => $this->emptyStat(),
                'statuses' => self::STATUSES,
                'canUpdate' => $canUpdate,
            ]);
        }

        $areas = $this->buildAreas($peta);
        $lines = $peta->view_state['lines'] ?? [];

        // Payload untuk builder Konva (dibaca lewat JSON data island).
        $mapData = [
            'viewBox'      => $peta->view_box,
            'latarUrl'     => $peta->latar_url,
            'latarOpacity' => $peta->latar_opacity ?? 70,
            'lines'        => $lines,
            'areas'        => $areas->map(fn ($a) => [
                'id'       => $a->id,
                'kode'     => $a->kode_area,
                'nama'     => $a->nama_tampil,
                'kategori' => $a->kategori,
                'zona'     => $a->zona,
                'status'   => $a->status_visual,
                'x'        => (float) ($a->x ?? 0),
                'y'        => (float) ($a->y ?? 0),
                'w'        => (float) ($a->w ?? 0),
                'h'        => (float) ($a->h ?? 0),
                'rotate'   => (float) ($a->rotate ?? 0),
                'luas'     => $a->luas_tampil,
                'pj'       => $a->ruang->pj_nama ?? null,
                'ket'      => $a->keterangan,
                'ruangId'  => $a->ruang_id,
            ])->values()->all(),
        ];

        return view('dashboard.sarpras.peta', [
            'peta'     => $peta,
            'areas'    => $areas,
            'lines'    => $lines,
            'mapData'  => $mapData,
            'stat'     => $this->stats($areas),
            'statuses' => self::STATUSES,
            'canUpdate' => $canUpdate,
        ]);
    }

    /** Simpan geometri (drag/resize/rotate) dari Mode Edit Layout. */
    public function saveLayout(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;
        if (!$this->canUpdate()) {
            return response()->json(['ok' => false, 'pesan' => 'Tidak berhak mengubah tata letak.'], 403);
        }

        $peta = SarprasPeta::query()->where('tingkat', 'tapak')->orderByDesc('id')->firstOrFail();

        $data = $request->validate([
            'areas'               => ['required', 'array'],
            'areas.*.id'          => ['required', 'integer'],
            'areas.*.x'           => ['required', 'numeric'],
            'areas.*.y'           => ['required', 'numeric'],
            'areas.*.w'           => ['required', 'numeric', 'min:1'],
            'areas.*.h'           => ['required', 'numeric', 'min:1'],
            'areas.*.rotate'      => ['nullable', 'numeric'],
            'lines'               => ['nullable', 'array'],
            'lines.*.points'      => ['required_with:lines', 'array', 'min:2'],
            'lines.*.warna'       => ['nullable', 'string', 'max:20'],
            'lines.*.tipe'        => ['nullable', 'in:jalan,batas'],
            'ringkasan'           => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($peta, $data) {
            foreach ($data['areas'] as $a) {
                SarprasPetaArea::where('peta_id', $peta->id)
                    ->where('id', $a['id'])
                    ->update([
                        'x'               => $a['x'],
                        'y'               => $a['y'],
                        'w'               => $a['w'],
                        'h'               => $a['h'],
                        'rotate'          => $a['rotate'] ?? 0,
                        'map_coordinates' => ['x' => $a['x'], 'y' => $a['y'], 'w' => $a['w'], 'h' => $a['h'], 'rotate' => $a['rotate'] ?? 0],
                        'label_x'         => $a['x'] + $a['w'] / 2,
                        'label_y'         => $a['y'] + $a['h'] / 2,
                        'updated_at'      => now(),
                    ]);
            }

            // Jalan / batas tanah disimpan di view_state (tabel existing).
            if (array_key_exists('lines', $data)) {
                $state = $peta->view_state ?? [];
                $state['lines'] = $data['lines'] ?? [];
                $peta->update(['view_state' => $state]);
            }

            $this->snapshotRevisi($peta, $data['ringkasan'] ?? 'Perubahan tata letak blok.');
        });

        return response()->json(['ok' => true, 'pesan' => 'Tata letak tersimpan.']);
    }

    /** Unggah / ganti citra latar denah. */
    public function uploadLatar(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;
        if (!$this->canUpdate()) {
            return response()->json(['ok' => false, 'pesan' => 'Tidak berhak mengubah latar.'], 403);
        }

        $request->validate([
            'latar'      => ['required', 'file', 'max:20480', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp'],
            'opacity'    => ['nullable', 'integer', 'min:0', 'max:100'],
            'atribusi'   => ['nullable', 'string', 'max:255'],
        ]);

        $peta = SarprasPeta::query()->where('tingkat', 'tapak')->orderByDesc('id')->firstOrFail();

        // Latar peta = gambar: dikompres ulang otomatis (resolusi lebih tinggi
        // 2560px agar denah tetap terbaca, kualitas 85).
        $hasil = app(\App\Services\ImageOptimizerService::class)->optimizeAndSaveImage(
            $request->file('latar'),
            storage_path('app/public/sarpras-peta'),
            'peta_' . \Illuminate\Support\Str::random(8),
            2560,
            85
        );
        $path = 'sarpras-peta/' . $hasil['filename'];
        DB::transaction(function () use ($peta, $request, $path) {
            if ($peta->latar_path && $peta->latar_path !== $path) {
                Storage::disk('public')->delete($peta->latar_path);
            }

            $peta->update([
                'latar_path'     => $path,
                'latar_sumber'   => 'upload',
                'latar_atribusi' => $request->input('atribusi') ?: null,
                'latar_opacity'  => (int) $request->input('opacity', 70),
            ]);
        });

        return response()->json([
            'ok'        => true,
            'pesan'     => 'Citra latar diperbarui.',
            'latar_url' => $peta->fresh()->latar_url,
        ]);
    }

    /** Simpan hanya opacity latar. */
    public function updateLatarOpacity(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;
        if (!$this->canUpdate()) {
            return response()->json(['ok' => false, 'pesan' => 'Tidak berhak.'], 403);
        }

        $data = $request->validate([
            'opacity' => ['required', 'integer', 'min:0', 'max:100'],
            'tampil'  => ['nullable', 'boolean'],
        ]);

        $peta = SarprasPeta::query()->where('tingkat', 'tapak')->orderByDesc('id')->firstOrFail();

        $state = $peta->view_state ?? [];
        $state['latar_tampil'] = (bool) ($data['tampil'] ?? true);

        $peta->update([
            'latar_opacity' => $data['opacity'],
            'view_state'    => $state,
        ]);

        return response()->json(['ok' => true]);
    }

    /** Ubah status visual sebuah area (+ sinkron kondisi ruang bila terhubung). */
    public function updateStatus(Request $request, $id)
    {
        if ($res = $this->checkAuth()) return $res;
        if (!$this->canUpdate()) {
            return response()->json(['ok' => false, 'pesan' => 'Tidak berhak mengubah status.'], 403);
        }

        $data = $request->validate([
            'status_visual' => ['required', 'in:' . implode(',', array_keys(self::STATUSES))],
            'keterangan'    => ['nullable', 'string', 'max:500'],
        ]);

        $area = SarprasPetaArea::findOrFail($id);

        DB::transaction(function () use ($area, $data) {
            $area->update([
                'status_visual' => $data['status_visual'],
                'highlight'     => in_array($data['status_visual'], ['baik'], true) ? null : $data['status_visual'],
                'keterangan'    => $data['keterangan'] ?? $area->keterangan,
            ]);

            // Sinkron kondisi ruang bila area ber-relasi ke sarpras_ruang.
            if ($area->ruang_id) {
                $kondisi = match ($data['status_visual']) {
                    'rusak_2026'   => 'rusak_berat',
                    'revit_2025'   => 'rusak_ringan',
                    default        => 'baik',
                };
                DB::table('sarpras_ruang')->where('id', $area->ruang_id)->update(['kondisi' => $kondisi, 'updated_at' => now()]);
            }

            $this->snapshotRevisi($area->peta, 'Ubah status area ' . $area->kode_area . ' → ' . $data['status_visual']);
        });

        return response()->json(['ok' => true, 'pesan' => 'Status area diperbarui.']);
    }

    /** Ekspor rekapitulasi ruangan + kondisi ke Excel. */
    public function exportExcel()
    {
        if ($res = $this->checkAuth()) return $res;

        $peta = SarprasPeta::query()->where('tingkat', 'tapak')->orderByDesc('id')->firstOrFail();
        $areas = $this->buildAreas($peta);

        $rows = [];
        foreach ($areas as $i => $a) {
            $rows[] = [
                $i + 1,
                $a->kode_area,
                $a->nama_tampil,
                $a->kategori,
                $a->zona,
                self::STATUSES[$a->status_visual]['label'] ?? $a->status_visual,
                $a->luas_tampil !== null ? (float) $a->luas_tampil : '',
                $a->ruang->pj_nama ?? '',
                $a->keterangan,
            ];
        }

        $headers = ['No', 'Kode', 'Nama', 'Kategori', 'Zona', 'Status', 'Luas (m²)', 'Penanggung Jawab', 'Keterangan'];

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $path = $tempDir . '/peta_ruang_' . uniqid() . '.xlsx';

        SimpleXlsxExporter::export($path, [
            'title'    => 'Rekapitulasi Peta & Denah Sekolah',
            'subtitle' => ($peta->nama ?? '') . ' — Diunduh ' . now()->translatedFormat('d F Y H:i'),
            'fields'   => array_filter(['Total Area' => count($rows) . ' area']),
        ], $headers, $rows, ['sheetName' => 'Peta Sekolah']);

        return response()->download($path, 'Peta_Ruang_' . date('Ymd_His') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** Daftar ruang `sarpras_ruang` yang belum ditempatkan di denah. */
    public function ruangTersedia()
    {
        if ($res = $this->checkAuth()) return $res;

        $petaId = SarprasPeta::query()->where('tingkat', 'tapak')->orderByDesc('id')->value('id');

        $terpakai = $petaId
            ? SarprasPetaArea::where('peta_id', $petaId)->whereNotNull('ruang_id')->pluck('ruang_id')->all()
            : [];

        $rows = DB::table('sarpras_ruang')
            ->select('id', 'kode_ruang', 'nama_ruang', 'gedung', 'kondisi')
            ->when(!empty($terpakai), fn ($q) => $q->whereNotIn('id', $terpakai))
            ->orderBy('kode_ruang')
            ->get();

        return response()->json(['ok' => true, 'rows' => $rows]);
    }

    /** Tempatkan satu ruang sebagai blok baru di denah. */
    public function attachRuang(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;
        if (!$this->canUpdate()) {
            return response()->json(['ok' => false, 'pesan' => 'Tidak berhak menambah blok.'], 403);
        }

        $data = $request->validate([
            'ruang_id' => ['required', 'integer', 'exists:sarpras_ruang,id'],
            'x'        => ['nullable', 'numeric'],
            'y'        => ['nullable', 'numeric'],
        ]);

        $peta = SarprasPeta::query()->where('tingkat', 'tapak')->orderByDesc('id')->firstOrFail();
        $ruang = DB::table('sarpras_ruang')->where('id', $data['ruang_id'])->first();

        if (SarprasPetaArea::where('peta_id', $peta->id)->where('ruang_id', $data['ruang_id'])->exists()) {
            return response()->json(['ok' => false, 'pesan' => 'Ruang sudah ada di denah.'], 422);
        }

        $x = (float) ($data['x'] ?? 60);
        $y = (float) ($data['y'] ?? 60);
        $w = 100.0;
        $h = 55.0;

        $area = SarprasPetaArea::create([
            'peta_id'         => $peta->id,
            'ruang_id'        => $ruang->id,
            'kode_area'       => $ruang->kode_ruang,
            'nama_area'       => $ruang->nama_ruang,
            'kategori'        => 'Ruang (dari master)',
            'zona'            => $ruang->gedung,
            'bentuk'          => 'rect',
            'x'               => $x,
            'y'               => $y,
            'w'               => $w,
            'h'               => $h,
            'label_x'         => $x + $w / 2,
            'label_y'         => $y + $h / 2,
            'status_visual'   => $ruang->kondisi === 'baik' ? 'baik' : 'rusak_2026',
            'map_coordinates' => ['x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'rotate' => 0],
        ]);

        return response()->json([
            'ok'   => true,
            'area' => [
                'id'       => $area->id,
                'kode'     => $area->kode_area,
                'nama'     => $area->nama_area,
                'kategori' => $area->kategori,
                'zona'     => $area->zona,
                'status'   => $area->status_visual,
                'x'        => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'rotate' => 0,
            ],
        ]);
    }

    /** Hapus blok dari denah. */
    public function destroyArea($id)
    {
        if ($res = $this->checkAuth()) return $res;
        if (!$this->canUpdate()) {
            return response()->json(['ok' => false, 'pesan' => 'Tidak berhak menghapus blok.'], 403);
        }

        $area = SarprasPetaArea::findOrFail($id);

        DB::transaction(function () use ($area) {
            $peta = $area->peta;
            $area->delete();
            if ($peta) $this->snapshotRevisi($peta, 'Hapus blok dari denah.');
        });

        return response()->json(['ok' => true, 'pesan' => 'Blok dihapus.']);
    }

    /** Halaman cetak (print → PDF) dengan legenda + arah mata angin. */
    public function cetak()
    {
        if ($res = $this->checkAuth()) return $res;

        $peta = SarprasPeta::query()->where('tingkat', 'tapak')->orderByDesc('id')->first();
        $areas = $peta ? $this->buildAreas($peta) : collect();

        return view('dashboard.sarpras.peta-cetak', [
            'peta'     => $peta,
            'areas'    => $areas,
            'statuses' => self::STATUSES,
            'stat'     => $this->stats($areas),
        ]);
    }

    /**
     * Impor dari gambar denah via Gemini Vision → JSON terstruktur (preview dulu).
     * Kembalikan baris yang terdeteksi agar admin periksa sebelum commit.
     */
    public function importVision(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;
        if (!$this->canUpdate()) {
            return response()->json(['ok' => false, 'pesan' => 'Tidak berhak mengimpor.'], 403);
        }

        $request->validate([
            'gambar' => ['required', 'file', 'max:20480', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp'],
        ]);

        $key = config('services.gemini.key');
        if (!$key) {
            return response()->json([
                'ok'    => false,
                'pesan' => 'Kunci GEMINI_API_KEY belum diisi di .env. Tambahkan kunci lalu coba lagi.',
            ], 422);
        }

        $file = $request->file('gambar');
        $base64 = base64_encode(file_get_contents($file->getRealPath()));
        $mime = $file->getMimeType() ?: 'image/jpeg';

        $prompt = 'Baca gambar denah sekolah ini. Ekstrak seluruh blok/ruang sebagai JSON array. '
            . 'Setiap objek: {"kode":"...","nama":"...","kategori":"...","zona":"...","status_visual":"baik|revit_2025|rusak_2026|siap_bangun|lahan_kosong"}. '
            . 'Gunakan kode pada gambar bila ada. Jangan menambah teks penjelasan, hanya JSON.';

        $model = config('services.gemini.model', 'gemini-1.5-flash');

        try {
            $resp = \Illuminate\Support\Facades\Http::timeout(60)
                ->retry(2, 500)
                ->post('https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($model) . ':generateContent?key=' . urlencode($key), [
                    'contents' => [[
                        'parts' => [
                            ['text' => $prompt],
                            ['inline_data' => ['mime_type' => $mime, 'data' => $base64]],
                        ],
                    ]],
                    'generationConfig' => ['responseMimeType' => 'application/json', 'temperature' => 0.1],
                ]);

            if (!$resp->successful()) {
                return response()->json(['ok' => false, 'pesan' => 'Gemini menolak permintaan (HTTP ' . $resp->status() . ').'], 422);
            }

            $text = data_get($resp->json(), 'candidates.0.content.parts.0.text', '[]');
            $parsed = json_decode($text, true);
            $rows = is_array($parsed) ? ($parsed['areas'] ?? $parsed) : [];

            return response()->json(['ok' => true, 'rows' => array_values(array_filter($rows, 'is_array'))]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'pesan' => 'Gagal menghubungi Gemini: ' . $e->getMessage()], 422);
        }
    }

    /** Terapkan hasil preview impor vision ke denah tapak. */
    public function importCommit(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;
        if (!$this->canUpdate()) {
            return response()->json(['ok' => false, 'pesan' => 'Tidak berhak mengimpor.'], 403);
        }

        $data = $request->validate([
            'rows'    => ['required', 'array', 'min:1'],
            'rows.*.kode'          => ['required', 'string', 'max:50'],
            'rows.*.status_visual' => ['nullable', 'in:' . implode(',', array_keys(self::STATUSES))],
            'rows.*.kategori'      => ['nullable', 'string', 'max:60'],
            'rows.*.zona'          => ['nullable', 'string', 'max:60'],
        ]);

        $peta = SarprasPeta::query()->where('tingkat', 'tapak')->orderByDesc('id')->firstOrFail();

        $updated = 0;
        $created = 0;

        DB::transaction(function () use ($peta, $data, &$updated, &$created) {
            foreach ($data['rows'] as $i => $r) {
                $area = SarprasPetaArea::firstOrNew([
                    'peta_id'   => $peta->id,
                    'kode_area' => $r['kode'],
                ]);

                $isNew = !$area->exists;

                $area->fill(array_filter([
                    'status_visual' => $r['status_visual'] ?? null,
                    'kategori'      => $r['kategori'] ?? null,
                    'zona'          => $r['zona'] ?? null,
                    'nama_area'     => $area->nama_area ?: $r['kode'],
                    'bentuk'        => $area->bentuk ?: 'rect',
                ]));

                if ($isNew) {
                    $x = 40 + ($i % 8) * 100;
                    $y = 40 + intdiv($i, 8) * 60;
                    $area->fill(['x' => $x, 'y' => $y, 'w' => 90, 'h' => 50, 'label_x' => $x + 45, 'label_y' => $y + 25]);
                    $created++;
                } else {
                    $updated++;
                }

                $area->save();
            }

            $this->snapshotRevisi($peta, "Impor dari gambar: {$created} baru, {$updated} diperbarui.");
        });

        return response()->json(['ok' => true, 'pesan' => "Impor selesai: {$created} baru, {$updated} diperbarui."]);
    }

    // ----------------------------------------------------------------- helpers

    private function canUpdate(): bool
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        return RolePermission::canAccess($user ?: $role, 'menu_peta_sekolah', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'update');
    }

    private function emptyStat(): array
    {
        return ['total' => 0] + array_fill_keys(array_keys(self::STATUSES), 0);
    }

    /** Statistik per status + total. */
    private function stats($areas): array
    {
        $stat = $this->emptyStat();
        $stat['total'] = $areas->count();

        foreach (array_keys(self::STATUSES) as $k) {
            $stat[$k] = $areas->where('status_visual', $k)->count();
        }

        return $stat;
    }

    /**
     * Susun area + gabungan data ruang (join `sarpras_ruang`) + status visual
     * efektif (status_visual → highlight → kondisi ruang).
     */
    private function buildAreas(SarprasPeta $peta)
    {
        $ruang = $peta->areas->pluck('ruang_id')->filter()->unique()->all();

        $ruangById = empty($ruang)
            ? collect()
            : DB::table('sarpras_ruang')
                ->leftJoin('gtk', 'sarpras_ruang.penanggung_jawab_ptk_id', '=', 'gtk.ptk_id')
                ->whereIn('sarpras_ruang.id', $ruang)
                ->select('sarpras_ruang.*', 'gtk.nama as pj_nama')
                ->get()
                ->keyBy('id');

        return $peta->areas->map(function ($a) use ($ruangById) {
            $r = $a->ruang_id ? $ruangById->get($a->ruang_id) : null;

            // Prioritas status: status_visual eksplisit > highlight > kondisi ruang.
            if ($a->status_visual && isset(self::STATUSES[$a->status_visual])) {
                $status = $a->status_visual;
            } elseif ($a->highlight && isset(self::STATUSES[$a->highlight])) {
                $status = $a->highlight;
            } elseif ($r && in_array($r->kondisi, ['rusak_ringan', 'rusak_berat'], true)) {
                $status = $r->kondisi === 'rusak_berat' ? 'rusak_2026' : 'revit_2025';
            } else {
                $status = 'baik';
            }

            $a->status_visual = $status;
            $a->ruang         = $r;
            $a->nama_tampil   = $r->nama_ruang ?? $a->nama_area ?? $a->kode_area;
            $a->luas_tampil   = $r->luas ?? $a->luas_area;

            return $a;
        });
    }

    /** Snapshot penuh geometri ke riwayat revisi (per perubahan). */
    private function snapshotRevisi(SarprasPeta $peta, string $ringkasan): void
    {
        $peta->refresh();
        $revisi = (int) $peta->revisi_ke + 1;

        DB::table('sarpras_peta_revisi')->insert([
            'peta_id'             => $peta->id,
            'revisi_ke'           => $revisi,
            'snapshot_geometri'   => json_encode($peta->areas()->get()->toArray(), JSON_UNESCAPED_UNICODE),
            'ringkasan_perubahan' => $ringkasan,
            'diubah_oleh'         => 'editor',
            'created_at'          => now(),
        ]);

        $peta->update(['revisi_ke' => $revisi]);
    }
}
