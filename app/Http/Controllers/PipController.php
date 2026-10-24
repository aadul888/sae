<?php

namespace App\Http\Controllers;

use App\Models\PipKuotaRombel;
use App\Models\PipPengaturan;
use App\Models\PipRiwayat;
use App\Models\PipUsulan;
use App\Models\PesertaDidik;
use App\Models\RolePermission;
use App\Services\PipExportService;
use App\Services\PipQueryService;
use App\Services\PipService;
use App\Services\SimpleXlsxExporter;
use App\Services\SimpleXlsxReader;
use App\Support\SemesterHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PipController extends Controller
{
    /**
     * Resolusi konteks pengguna (Administrator atau Wali Kelas)
     */
    private function resolveContext(Request $request): array
    {
        PipService::ensureSchema();

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $isAdmin = ($role === 'admin') || RolePermission::canAccess($user, 'menu_pip', 'read');

        // Hak akses: Admin memiliki akses penuh; Wali Kelas harus memiliki tugas tambahan Wali Kelas atau izin menu_wali_kelas_pip
        $isWaliKelas = RolePermission::isWaliKelasOrAdmin($user);
        $canAccessPip = $isAdmin || (
            $isWaliKelas && (
                RolePermission::canAccess($user, 'menu_wali_kelas_pip', 'read') ||
                RolePermission::canAccess($user, 'menu_wali_kelas', 'read') ||
                RolePermission::canAccess($user, 'menu_pip', 'read')
            )
        );

        if (!$canAccessPip) {
            abort(403, 'Akses ditolak: Menu Usulan PIP khusus untuk Wali Kelas dan Administrator.');
        }

        $rombelList = collect();
        if (Schema::hasTable('rombongan_belajar')) {
            $rombelList = DB::table('rombongan_belajar')
                ->where('jenis_rombel', '1')
                ->orderBy('tingkat_pendidikan_id', 'asc')
                ->orderBy('nama', 'asc')
                ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id', 'tingkat_pendidikan_id_str', 'jurusan_id_str', 'ptk_id_str')
                ->get();
        }

        $activeRombel = null;
        if ($isAdmin) {
            $selectedRombelId = trim((string) $request->get('rombel_id', ''));
            if ($selectedRombelId !== '') {
                $activeRombel = $rombelList->firstWhere('rombongan_belajar_id', $selectedRombelId);
            }
        } else {
            $activeRombel = RolePermission::getWaliKelasRombelInfo($user);
            if (!$activeRombel && Schema::hasTable('rombongan_belajar')) {
                $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
                $userName = is_array($user) ? ($user['name'] ?? ($user['nama'] ?? '')) : ($user->name ?? ($user->nama ?? ''));
                if ($ptkId) {
                    $activeRombel = DB::table('rombongan_belajar')->where('ptk_id', $ptkId)->first();
                }
                if (!$activeRombel && $userName) {
                    $activeRombel = DB::table('rombongan_belajar')->where('ptk_id_str', $userName)->first();
                }
            }
            if (!$activeRombel) {
                abort(403, 'Akses ditolak: Anda tidak memiliki rombongan belajar binaan aktif sebagai Wali Kelas.');
            }
        }

        $activeTahun = (string) $request->get('tahun', date('Y'));

        return [
            'user'         => $user,
            'role'         => $role,
            'isAdmin'      => $isAdmin,
            'isWaliKelas'  => $isWaliKelas,
            'rombelList'   => $rombelList,
            'activeRombel' => $activeRombel,
            'activeTahun'  => $activeTahun,
        ];
    }

    /**
     * Pastikan aksi pada usulan hanya dilakukan Admin atau Wali Kelas pemilik rombel usulan
     */
    private function authorizeUsulan(Request $request, PipUsulan $item): ?JsonResponse
    {
        $ctx = $this->resolveContext($request);
        if ($ctx['isAdmin']) {
            return null;
        }
        $rombel = $ctx['activeRombel'];
        if (!$rombel || $rombel->rombongan_belajar_id !== $item->rombongan_belajar_id) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: usulan ini bukan dari kelas binaan Anda.'], 403);
        }
        return null;
    }

    private function isAdminUser(): bool
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        return ($role === 'admin') || RolePermission::canAccess($user, 'menu_pip', 'read');
    }

    /**
     * Halaman Utama Pengelolaan Usulan & Riwayat PIP
     */
    public function index(Request $request)
    {
        $ctx = $this->resolveContext($request);
        if (!$ctx['isAdmin'] && !$ctx['activeRombel']) {
            abort(403, 'Akses ditolak: Menu Usulan PIP khusus untuk Wali Kelas dan Administrator.');
        }

        $viewData = PipQueryService::getIndexData($ctx, $request);

        return view('dashboard.pip.index', $viewData);
    }

    /**
     * Autocomplete peserta didik untuk modal tambah usulan
     */
    public function cariSiswa(Request $request): JsonResponse
    {
        $ctx = $this->resolveContext($request);
        $activeRombel = $ctx['activeRombel'];
        $isAdmin = $ctx['isAdmin'];
        $activeTahun = $ctx['activeTahun'];

        $q = trim((string) $request->get('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $query = DB::table('peserta_didik as pd')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->select('pd.peserta_didik_id', 'pd.nama', 'pd.nisn', 'pd.nipd', 'pd.nama_rombel', 'pd.rombongan_belajar_id', 'pdm.foto_path')
            ->where(function ($s) use ($q) {
                $s->where('pd.nama', 'LIKE', "%{$q}%")
                    ->orWhere('pd.nisn', 'LIKE', "%{$q}%")
                    ->orWhere('pd.nipd', 'LIKE', "%{$q}%");
            });

        // Mode pencarian bebas (khusus Admin, untuk input riwayat): tanpa batas kelas & tanpa filter usulan
        $freeSearch = $isAdmin && $request->boolean('all_years');

        if (!$freeSearch) {
            // Untuk Wali Kelas, batasi hanya peserta didik di kelas binaannya
            if ($activeRombel) {
                $query->where('pd.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
            }

            // Keluarkan siswa yang sudah pernah diusulkan di tahun aktif ini
            $alreadyProposed = DB::table('pip_usulan')
                ->where('tahun', $activeTahun)
                ->pluck('peserta_didik_id')
                ->all();

            if (!empty($alreadyProposed)) {
                $query->whereNotIn('pd.peserta_didik_id', $alreadyProposed);
            }
        }

        $rows = $query->orderBy('pd.nama')->limit(15)->get();

        $rows->transform(function ($item) {
            $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
            return $item;
        });

        return response()->json($rows);
    }

    /**
     * Ekspor Data Usulan PIP Lengkap ke Microsoft Excel (.xlsx)
     */
    public function exportUsulan(Request $request)
    {
        PipService::ensureSchema();

        if (!Schema::hasTable('pip_usulan')) {
            return back()->with('error', 'Tabel usulan PIP belum tersedia.');
        }

        $ctx = $this->resolveContext($request);
        $filters = [
            'q' => trim((string) $request->get('q', '')),
            'status_verifikasi' => trim((string) $request->get('status_verifikasi', '')),
            'fase' => trim((string) $request->get('fase', '')),
            'status_perubahan' => trim((string) $request->get('status_perubahan', '')),
        ];

        return PipExportService::exportUsulan($ctx, $filters);
    }

    /**
     * Simpan Usulan PIP Baru oleh Wali Kelas atau Admin
     */
    public function storeUsulan(Request $request)
    {
        $ctx = $this->resolveContext($request);
        $isAdmin = $ctx['isAdmin'];
        $activeRombel = $ctx['activeRombel'];
        $user = $ctx['user'];

        if (!$isAdmin && !RolePermission::canAccess($user, 'menu_wali_kelas_pip', 'create') && !RolePermission::canAccess($user, 'menu_wali_kelas', 'create')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki izin menambah usulan PIP.'], 403);
        }

        $data = $request->validate([
            'peserta_didik_id' => 'required|string|exists:peserta_didik,peserta_didik_id',
            'alasan_usulan'    => 'required|array|min:1',
            'alasan_usulan.*'  => 'required|string',
            'keterangan'       => 'nullable|string|max:1000',
            'tahun'            => 'nullable|string|max:20',
        ], [
            'peserta_didik_id.required' => 'Peserta didik wajib dipilih.',
            'alasan_usulan.required'    => 'Minimal pilih satu alasan/kriteria usulan PIP.',
            'alasan_usulan.min'         => 'Minimal pilih satu alasan/kriteria usulan PIP.',
        ]);

        $tahun = $data['tahun'] ?? ($ctx['activeTahun'] ?: date('Y'));

        // Cek penguncian untuk Wali Kelas
        if (!$isAdmin && PipPengaturan::isLockedForWaliKelas($tahun)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Pengisian usulan PIP saat ini dikunci oleh Administrator atau telah melewati batas waktu pengisian.',
            ], 403);
        }

        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $data['peserta_didik_id'])->first();
        if (!$pd) {
            return response()->json(['success' => false, 'message' => 'Peserta didik tidak ditemukan.'], 404);
        }

        $rombelId = $pd->rombongan_belajar_id ?: ($activeRombel?->rombongan_belajar_id ?? '');
        if (!$isAdmin && $activeRombel && $rombelId !== $activeRombel->rombongan_belajar_id) {
            return response()->json(['success' => false, 'message' => 'Anda hanya berhak mengusulkan peserta didik dari kelas binaan Anda.'], 403);
        }

        // Cek apakah siswa sudah diusulkan di tahun dan fase yang sama
        $settings = PipPengaturan::getSettings($tahun);
        $fase = $data['fase'] ?? ($settings->fase ?: (date('n') <= 6 ? 'fase_1' : 'fase_2'));
        $faseLabel = PipUsulan::getFaseLabel($fase);
        $tp = $data['tahun_pelajaran'] ?? ($settings->tahun_pelajaran ?: (class_exists(\App\Support\SemesterHelper::class) ? \App\Support\SemesterHelper::getActiveSemesterLabel() : null));
        $judul = $data['judul_usulan'] ?? ($settings->judul_usulan ?: ("Usulan PIP Tahun {$tahun} — {$faseLabel}"));

        $exists = PipUsulan::where('peserta_didik_id', $pd->peserta_didik_id)
            ->where('tahun', $tahun)
            ->where('fase', $fase)
            ->exists();
        if ($exists) {
            return response()->json(['success' => false, 'message' => "Siswa {$pd->nama} sudah terdaftar dalam usulan PIP tahun {$tahun} ({$faseLabel})."], 422);
        }

        // Cek Kuota Usulan Kelas (Pengisian tidak boleh melebihi kuota)
        $kuota = PipKuotaRombel::getKuotaForRombel($rombelId, $tahun);
        $currentCount = PipUsulan::where('rombongan_belajar_id', $rombelId)
            ->where('tahun', $tahun)
            ->where('fase', $fase)
            ->count();

        if ($currentCount >= $kuota) {
            return response()->json([
                'success' => false,
                'message' => "Kuota usulan PIP kelas ini sudah penuh (Maksimal {$kuota} peserta didik untuk {$faseLabel}). Pengusulan tidak boleh melebihi kuota. Hubungi Administrator jika memerlukan penyesuaian kuota.",
            ], 422);
        }

        // Hitung Prioritas Otomatis: Ranking terakhir + 1
        $maxPrioritas = (int) PipUsulan::where('rombongan_belajar_id', $rombelId)
            ->where('tahun', $tahun)
            ->where('fase', $fase)
            ->max('prioritas');
        $prioritasBaru = $maxPrioritas + 1;

        $pengusulNama = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Wali Kelas')) : ($user->nama ?? ($user->name ?? 'Wali Kelas'));

        PipUsulan::create([
            'peserta_didik_id'     => $pd->peserta_didik_id,
            'rombongan_belajar_id' => $rombelId,
            'tahun'                => $tahun,
            'fase'                 => $fase,
            'tahun_pelajaran'      => $tp,
            'judul_usulan'         => $judul,
            'prioritas'            => $prioritasBaru,
            'alasan_usulan'        => array_values($data['alasan_usulan']),
            'keterangan'           => trim((string) ($data['keterangan'] ?? '')),
            'diusulkan_oleh'       => $pengusulNama,
            'status_verifikasi'    => 'belum_diajukan',
        ]);

        $msg = "Peserta didik {$pd->nama} berhasil diusulkan dengan prioritas urutan ke-{$prioritasBaru}.";
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }
        return redirect()->back()->with('success', $msg);
    }

    /**
     * Perbarui Kriteria Alasan & Keterangan Usulan PIP oleh Wali Kelas / Admin
     */
    public function updateUsulan(Request $request, int $id): JsonResponse
    {
        PipService::ensureSchema();
        $ctx = $this->resolveContext($request);
        $isAdmin = $ctx['isAdmin'];
        $user = $ctx['user'];

        if (!$isAdmin && !RolePermission::canAccess($user, 'menu_wali_kelas_pip', 'update') && !RolePermission::canAccess($user, 'menu_wali_kelas', 'update')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki izin mengubah usulan PIP.'], 403);
        }

        $item = PipUsulan::findOrFail($id);
        if ($deny = $this->authorizeUsulan($request, $item)) {
            return $deny;
        }

        if (!$isAdmin && in_array($item->status_verifikasi, ['sudah_diajukan', 'ditolak'], true)) {
            $label = PipUsulan::STATUS_VERIFIKASI[$item->status_verifikasi] ?? $item->status_verifikasi;
            return response()->json([
                'success' => false,
                'message' => "Akses ditolak: Usulan dengan status '{$label}' tidak dapat diubah lagi oleh Wali Kelas.",
            ], 422);
        }

        if (!$isAdmin && PipPengaturan::isLockedForWaliKelas($item->tahun)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Pengusulan PIP telah dikunci atau telah melewati batas waktu pengisian.',
            ], 403);
        }

        $data = $request->validate([
            'alasan_usulan'   => 'required|array|min:1',
            'alasan_usulan.*' => 'required|string',
            'keterangan'      => 'nullable|string|max:1000',
        ], [
            'alasan_usulan.required' => 'Minimal pilih satu alasan/kriteria usulan PIP.',
            'alasan_usulan.min'      => 'Minimal pilih satu alasan/kriteria usulan PIP.',
        ]);

        $oldAlasan = is_string($item->alasan_usulan)
            ? (json_decode($item->alasan_usulan, true) ?: [])
            : (array) ($item->alasan_usulan ?: []);
        $newAlasan = array_values($data['alasan_usulan']);

        $sortedOld = $oldAlasan;
        $sortedNew = $newAlasan;
        sort($sortedOld);
        sort($sortedNew);

        $oldKet = trim((string) ($item->keterangan ?? ''));
        $newKet = trim((string) ($data['keterangan'] ?? ''));

        $diffAlasan = ($sortedOld != $sortedNew);
        $diffKet = ($oldKet !== $newKet);

        if (!$diffAlasan && !$diffKet) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada perubahan pada data kriteria dan keterangan usulan.',
            ]);
        }

        $detailPerubahan = [];
        if ($diffAlasan) {
            $tambah = array_values(array_diff($newAlasan, $oldAlasan));
            $hapus = array_values(array_diff($oldAlasan, $newAlasan));
            $parts = [];
            if (!empty($tambah)) {
                $parts[] = 'Ditambah: ' . implode(', ', $tambah);
            }
            if (!empty($hapus)) {
                $parts[] = 'Dihapus: ' . implode(', ', $hapus);
            }
            $detailPerubahan[] = 'Kriteria alasan disesuaikan (' . implode('; ', $parts) . ')';
        }
        if ($diffKet) {
            $detailPerubahan[] = 'Keterangan diperbarui dari "' . ($oldKet ?: '-') . '" menjadi "' . ($newKet ?: '-') . '"';
        }

        $editorNama = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Pengguna')) : ($user->nama ?? ($user->name ?? 'Pengguna'));
        $roleLabel = $isAdmin ? 'Administrator' : 'Wali Kelas';
        $editorSignature = $editorNama . ' (' . $roleLabel . ')';

        $ringkasan = implode(' &bull; ', $detailPerubahan);

        $riwayat = is_array($item->riwayat_perubahan)
            ? $item->riwayat_perubahan
            : (json_decode($item->riwayat_perubahan, true) ?: []);

        array_unshift($riwayat, [
            'waktu'     => now()->format('Y-m-d H:i:s'),
            'editor'    => $editorSignature,
            'peran'     => $roleLabel,
            'ringkasan' => $ringkasan,
            'detail'    => $detailPerubahan,
        ]);

        $item->alasan_usulan = array_values($data['alasan_usulan']);
        $item->keterangan = $newKet;
        $item->diubah_oleh = $editorSignature;
        $item->diubah_at = now();
        $item->perubahan_terakhir = mb_substr($ringkasan, 0, 250);
        $item->riwayat_perubahan = $riwayat;
        $item->save();

        $pdNama = DB::table('peserta_didik')->where('peserta_didik_id', $item->peserta_didik_id)->value('nama') ?? 'Peserta Didik';

        return response()->json([
            'success' => true,
            'message' => "Usulan untuk {$pdNama} berhasil diperbarui. Perubahan tercatat pada riwayat log.",
            'data'    => $item,
        ]);
    }

    /**
     * Dapatkan Catatan Log Riwayat Perubahan Usulan PIP
     */
    public function getRiwayatPerubahan(Request $request, int $id): JsonResponse
    {
        PipService::ensureSchema();
        $item = PipUsulan::findOrFail($id);
        if ($deny = $this->authorizeUsulan($request, $item)) {
            return $deny;
        }

        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $item->peserta_didik_id)->first();
        $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $item->rombongan_belajar_id)->first();

        $riwayat = is_array($item->riwayat_perubahan)
            ? $item->riwayat_perubahan
            : (json_decode($item->riwayat_perubahan, true) ?: []);

        return response()->json([
            'success' => true,
            'usulan'  => [
                'id'                 => $item->id,
                'nama_pd'            => $pd?->nama ?: '-',
                'nisn'               => $pd?->nisn ?: '-',
                'rombel'             => $rombel?->nama ?: '-',
                'prioritas'          => $item->prioritas,
                'status_verifikasi'  => $item->status_verifikasi,
                'diusulkan_oleh'     => $item->diusulkan_oleh,
                'created_at'         => $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-',
                'diubah_oleh'        => $item->diubah_oleh,
                'diubah_at'          => $item->diubah_at ? $item->diubah_at->format('d/m/Y H:i') : null,
                'perubahan_terakhir' => $item->perubahan_terakhir,
                'alasan_list'        => is_string($item->alasan_usulan) ? (json_decode($item->alasan_usulan, true) ?: []) : ($item->alasan_usulan ?: []),
                'keterangan'         => $item->keterangan,
            ],
            'riwayat' => $riwayat,
        ]);
    }

    /**
     * Ubah Urutan Prioritas / Ranking Usulan PIP (Naik/Turun atau Input Langsung)
     * Khusus Wali Kelas (Admin Readonly)
     */
    public function updatePrioritas(Request $request): JsonResponse
    {
        $ctx = $this->resolveContext($request);

        // Administrator mode: readonly, tidak bisa mengubah urutan prioritas
        if ($ctx['isAdmin']) {
            return response()->json([
                'success' => false,
                'message' => 'Urutan prioritas hanya dapat diatur oleh Wali Kelas binaan.',
            ], 403);
        }

        if (!RolePermission::canAccess($ctx['user'], 'menu_wali_kelas_pip', 'update') && !RolePermission::canAccess($ctx['user'], 'menu_wali_kelas', 'update')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki izin mengubah prioritas usulan PIP.'], 403);
        }

        $data = $request->validate([
            'id'        => 'required|integer|exists:pip_usulan,id',
            'direction' => 'nullable|in:up,down',
            'new_rank'  => 'nullable|integer|min:1',
        ]);

        $item = PipUsulan::findOrFail($data['id']);
        if ($deny = $this->authorizeUsulan($request, $item)) {
            return $deny;
        }

        $rombelId = $item->rombongan_belajar_id;
        $tahun = $item->tahun;

        if (PipPengaturan::isLockedForWaliKelas($tahun)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Pengusulan PIP telah dikunci atau telah melewati batas waktu pengisian.',
            ], 403);
        }

        // Ambil seluruh usulan rombel terurut
        $list = PipUsulan::where('rombongan_belajar_id', $rombelId)
            ->where('tahun', $tahun)
            ->orderBy('prioritas', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->values();

        $currentIndex = $list->search(fn($r) => (int) $r->id === (int) $item->id);
        if ($currentIndex === false) {
            return response()->json(['success' => false, 'message' => 'Data usulan tidak ditemukan.'], 404);
        }

        if (!empty($data['direction'])) {
            $dir = $data['direction'];
            if ($dir === 'up' && $currentIndex > 0) {
                $other = $list[$currentIndex - 1];
                $list[$currentIndex - 1] = $item;
                $list[$currentIndex] = $other;
            } elseif ($dir === 'down' && $currentIndex < $list->count() - 1) {
                $other = $list[$currentIndex + 1];
                $list[$currentIndex + 1] = $item;
                $list[$currentIndex] = $other;
            }
        } elseif (!empty($data['new_rank'])) {
            $targetRank = max(1, min((int) $data['new_rank'], $list->count()));
            $targetIndex = $targetRank - 1;
            if ($targetIndex !== $currentIndex) {
                $itemToMove = $list->pull($currentIndex);
                $list->splice($targetIndex, 0, [$itemToMove]);
            }
        }

        $oldRank = (int) $item->prioritas;
        $newRankCalculated = null;

        // Terapkan nomor urut 1, 2, 3, ... secara sekuensial tanpa celah/bentrok
        foreach ($list->values() as $idx => $row) {
            $seq = $idx + 1;
            if ((int) $row->id === (int) $item->id) {
                $newRankCalculated = $seq;
            }
            if ((int) $row->prioritas !== $seq) {
                PipUsulan::where('id', $row->id)->update(['prioritas' => $seq]);
            }
        }

        if ($newRankCalculated !== null && $oldRank !== $newRankCalculated) {
            $editorNama = is_array($ctx['user']) ? ($ctx['user']['nama'] ?? ($ctx['user']['name'] ?? 'Wali Kelas')) : ($ctx['user']->nama ?? ($ctx['user']->name ?? 'Wali Kelas'));
            $editorSig = $editorNama . ' (Wali Kelas)';
            $ringkasan = "Urutan prioritas diubah dari #{$oldRank} menjadi #{$newRankCalculated}";

            $riwayat = is_array($item->riwayat_perubahan)
                ? $item->riwayat_perubahan
                : (json_decode($item->riwayat_perubahan, true) ?: []);

            array_unshift($riwayat, [
                'waktu'     => now()->format('Y-m-d H:i:s'),
                'editor'    => $editorSig,
                'peran'     => 'Wali Kelas',
                'ringkasan' => $ringkasan,
                'detail'    => ["Ranking prioritas bergeser dari urutan #{$oldRank} ke #{$newRankCalculated}"],
            ]);

            PipUsulan::where('id', $item->id)->update([
                'diubah_oleh'        => $editorSig,
                'diubah_at'          => now(),
                'perubahan_terakhir' => $ringkasan,
                'riwayat_perubahan'  => json_encode($riwayat),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Urutan prioritas usulan berhasil diperbarui.',
        ]);
    }

    /**
     * Hapus Data Usulan PIP
     */
    public function destroyUsulan(Request $request, int $id): JsonResponse
    {
        $ctx = $this->resolveContext($request);
        if (!$ctx['isAdmin'] && !RolePermission::canAccess($ctx['user'], 'menu_wali_kelas_pip', 'delete') && !RolePermission::canAccess($ctx['user'], 'menu_wali_kelas', 'delete')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki izin menghapus usulan PIP.'], 403);
        }

        $item = PipUsulan::findOrFail($id);
        if ($deny = $this->authorizeUsulan($request, $item)) {
            return $deny;
        }
        $rombelId = $item->rombongan_belajar_id;
        $tahun = $item->tahun;

        if (in_array($item->status_verifikasi, ['sudah_diajukan', 'ditolak'], true)) {
            $label = PipUsulan::STATUS_VERIFIKASI[$item->status_verifikasi] ?? $item->status_verifikasi;
            return response()->json([
                'success' => false,
                'message' => "Akses ditolak: Usulan dengan status '{$label}' tidak dapat dihapus.",
            ], 422);
        }

        if (!$ctx['isAdmin'] && PipPengaturan::isLockedForWaliKelas($tahun)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Pengusulan PIP telah dikunci atau telah melewati batas waktu pengisian.',
            ], 403);
        }

        $item->delete();

        // Normalisasi urutan prioritas yang tersisa
        $remaining = PipUsulan::where('rombongan_belajar_id', $rombelId)
            ->where('tahun', $tahun)
            ->orderBy('prioritas', 'asc')
            ->get();

        $seq = 1;
        foreach ($remaining as $r) {
            $r->prioritas = $seq++;
            $r->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Usulan PIP peserta didik berhasil dihapus.',
        ]);
    }

    /**
     * Verifikasi Usulan oleh Admin (Tandai sudah diajukan ke Web PIP atau belum)
     */
    public function updateVerifikasi(Request $request, int $id): JsonResponse
    {
        $user = session('user');
        if (!$this->isAdminUser()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Khusus Administrator.'], 403);
        }

        $data = $request->validate([
            'status_verifikasi' => 'required|in:belum_diajukan,sudah_diajukan,ditolak',
            'catatan_admin'     => 'nullable|string|max:500',
        ]);

        $item = PipUsulan::findOrFail($id);
        $item->status_verifikasi = $data['status_verifikasi'];
        if (array_key_exists('catatan_admin', $data)) {
            $item->catatan_admin = $data['catatan_admin'];
        }

        $adminNama = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Admin')) : ($user->nama ?? ($user->name ?? 'Admin'));
        $item->diverifikasi_oleh = $adminNama;
        $item->verifikasi_admin_at = now();
        $item->save();

        $statusLabel = PipUsulan::STATUS_VERIFIKASI[$data['status_verifikasi']] ?? $data['status_verifikasi'];
        return response()->json([
            'success' => true,
            'message' => "Status usulan berhasil diperbarui menjadi {$statusLabel}.",
        ]);
    }

    /**
     * Bulk Verifikasi Usulan oleh Admin
     */
    public function bulkVerifikasi(Request $request): JsonResponse
    {
        $user = session('user');
        if (!$this->isAdminUser()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Khusus Administrator.'], 403);
        }

        $data = $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer|exists:pip_usulan,id',
            'action' => 'required|in:set_sudah_diajukan,set_belum_diajukan,set_ditolak',
        ]);

        $statusTarget = match ($data['action']) {
            'set_sudah_diajukan' => 'sudah_diajukan',
            'set_ditolak'        => 'ditolak',
            default              => 'belum_diajukan',
        };

        $adminNama = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Admin')) : ($user->nama ?? ($user->name ?? 'Admin'));

        $count = PipUsulan::whereIn('id', $data['ids'])->update([
            'status_verifikasi'   => $statusTarget,
            'diverifikasi_oleh'   => $adminNama,
            'verifikasi_admin_at' => now(),
            'updated_at'          => now(),
        ]);

        $label = PipUsulan::STATUS_VERIFIKASI[$statusTarget] ?? $statusTarget;
        return response()->json([
            'success' => true,
            'message' => "Sebanyak {$count} usulan berhasil diperbarui menjadi '{$label}'.",
        ]);
    }

    /**
     * Atur Kuota Usulan PIP per Rombel (Admin Only)
     * Mendukung: 
     * 1. Penentuan Kuota Berdasarkan Persentase Jumlah Siswa Aktif (Proporsional & Adil)
     *    - Bisa dipilih untuk: Semua Tingkat, Kelas Awal (X saja), atau Kelas Berjalan (XI & XII)
     * 2. Simpan Semua Perubahan Kuota (1 Tombol Simpan Tunggal untuk Seluruh Kelas)
     * 3. Menyamakan Kuota Rata per Kelas (misal 5 siswa/kelas)
     */
    public function updateKuota(Request $request): JsonResponse
    {
        if (!$this->isAdminUser()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Khusus Administrator.'], 403);
        }

        PipService::ensureSchema();

        $data = $request->validate([
            'kuota_map'            => 'nullable|array',
            'kuota_map.*'          => 'integer|min:0|max:1000',
            'rombongan_belajar_id' => 'nullable|string',
            'kuota'                => 'nullable|integer|min:0|max:1000',
            'total_kuota'          => 'nullable|integer|min:0|max:10000',
            'kategori_tingkat'      => 'nullable|string|in:semua,kelas_awal,kelas_berjalan',
            'tahun'                => 'nullable|string|max:20',
            'mode'                 => 'nullable|string|in:single,batch,set_all,distribute_total,distribute_proportional',
            'set_all'              => 'nullable|boolean',
        ]);

        $tahun = ($data['tahun'] ?? null) ?: date('Y');
        $kategoriTingkat = $data['kategori_tingkat'] ?? 'semua';
        $mode = $data['mode'] ?? (!empty($data['kuota_map']) ? 'batch' : (!empty($data['set_all']) ? 'set_all' : (!empty($data['total_kuota']) ? 'distribute_proportional' : 'single')));

        // 1. Simpan Seluruh Perubahan Kuota Sekaligus (1 Tombol Simpan Tunggal)
        if ($mode === 'batch' || !empty($data['kuota_map'])) {
            $map = (array) ($data['kuota_map'] ?? []);
            if (empty($map)) {
                return response()->json(['success' => false, 'message' => 'Tidak ada data kuota yang dikirim.'], 422);
            }

            foreach ($map as $rId => $val) {
                $kVal = max(0, (int) $val);
                PipKuotaRombel::updateOrCreate(
                    ['rombongan_belajar_id' => (string) $rId, 'tahun' => $tahun],
                    ['kuota' => $kVal]
                );
            }

            // Perbarui pagu total sekolah berdasarkan jumlah kuota seluruh rombel
            $totalBatch = (int) DB::table('pip_kuota_rombel')
                ->where('tahun', $tahun)
                ->where('rombongan_belajar_id', '!=', '__TOTAL__')
                ->sum('kuota');
            PipKuotaRombel::setTargetTotalSekolah($totalBatch, $tahun);

            $countSaved = count($map);
            return response()->json([
                'success' => true,
                'message' => "Semua perubahan kuota untuk {$countSaved} kelas berhasil disimpan (Total Pagu: {$totalBatch} siswa).",
            ]);
        }

        // 2. Penentuan Kuota Berdasarkan Persentase Jumlah Peserta Didik (Adil & Proporsional)
        // Mendukung: Semua Tingkat, Kelas Awal (X saja), atau Kelas Berjalan (XI & XII)
        if ($mode === 'distribute_proportional' || $mode === 'distribute_total' || isset($data['total_kuota'])) {
            $totalTarget = max(0, (int) ($data['total_kuota'] ?? 0));

            $proportionalAllocations = PipService::calculateProportionalQuota($totalTarget, $kategoriTingkat);
            $count = count($proportionalAllocations);

            if ($count === 0) {
                return response()->json(['success' => false, 'message' => 'Tidak ada rombongan belajar aktif pada kategori tingkat yang dipilih.'], 422);
            }

            foreach ($proportionalAllocations as $rId => $kVal) {
                PipKuotaRombel::updateOrCreate(
                    ['rombongan_belajar_id' => $rId, 'tahun' => $tahun],
                    ['kuota' => (int) $kVal]
                );
            }

            // Hitung total pagu baru sekolah
            $totalBaruSekolah = (int) DB::table('pip_kuota_rombel')
                ->where('tahun', $tahun)
                ->where('rombongan_belajar_id', '!=', '__TOTAL__')
                ->sum('kuota');
            PipKuotaRombel::setTargetTotalSekolah($totalBaruSekolah, $tahun);

            $labelKategori = match ($kategoriTingkat) {
                'kelas_awal'     => 'Kelas Awal (Khusus Kelas X)',
                'kelas_berjalan' => 'Kelas Berjalan (Khusus Kelas XI & XII)',
                default          => 'Seluruh Tingkat (Kelas X, XI, XII)',
            };

            return response()->json([
                'success' => true,
                'message' => "Pagu {$totalTarget} siswa berhasil dibagikan secara adil ke {$count} rombel {$labelKategori} berdasarkan persentase jumlah peserta didik aktif.",
            ]);
        }

        // 3. Samakan Kuota untuk Semua Kelas (Bagi Rata, misal 5 siswa/kelas)
        if ($mode === 'set_all' || !empty($data['set_all'])) {
            $kuotaVal = (int) ($data['kuota'] ?? 5);
            $rombels = DB::table('rombongan_belajar')->where('jenis_rombel', '1')->pluck('rombongan_belajar_id');
            foreach ($rombels as $rId) {
                PipKuotaRombel::updateOrCreate(
                    ['rombongan_belajar_id' => $rId, 'tahun' => $tahun],
                    ['kuota' => $kuotaVal]
                );
            }
            $totalSemua = $rombels->count() * $kuotaVal;
            PipKuotaRombel::setTargetTotalSekolah($totalSemua, $tahun);

            return response()->json([
                'success' => true,
                'message' => "Kuota seluruh ({$rombels->count()}) kelas berhasil disamakan menjadi {$kuotaVal} siswa/kelas (Total pagu: {$totalSemua} siswa).",
            ]);
        }

        // 4. Atur Kuota Per Kelas Tunggal
        if (empty($data['rombongan_belajar_id'])) {
            return response()->json(['success' => false, 'message' => 'Rombel wajib dipilih.'], 422);
        }

        $kuotaVal = (int) ($data['kuota'] ?? 0);
        PipKuotaRombel::updateOrCreate(
            ['rombongan_belajar_id' => $data['rombongan_belajar_id'], 'tahun' => $tahun],
            ['kuota' => $kuotaVal]
        );

        return response()->json([
            'success' => true,
            'message' => "Kuota usulan kelas berhasil diperbarui menjadi {$kuotaVal} siswa.",
        ]);
    }

    /**
     * Import File Excel (.xlsx / .xls) / CSV Hasil Unduhan SiPintar ke Tabel Riwayat PIP
     * Mendukung 2 Jenis SK:
     * 1. SK Pemberian (Pencairan Dana)
     * 2. SK Nominasi (Aktivasi Rekening) -> Validasi: otomatis abaikan/skip siswa yang sudah terdaftar di SK Pemberian
     */
    public function importRiwayatExcel(Request $request): JsonResponse
    {
        if (!$this->isAdminUser()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Khusus Administrator.'], 403);
        }

        PipService::ensureSchema();

        $request->validate([
            'file_excel' => 'required|file|max:30720', // Max 30MB
            'jenis_sk'   => 'required|in:pemberian,nominasi',
        ], [
            'file_excel.required' => 'Berkas Excel wajib diunggah.',
            'jenis_sk.required'   => 'Pilih jenis SK yang diunggah (SK Pemberian atau SK Nominasi).',
        ]);

        $jenisSk = $request->get('jenis_sk', 'pemberian');
        $file = $request->file('file_excel');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'], true)) {
            return response()->json(['success' => false, 'message' => 'Format file tidak didukung. Unggah berkas .xlsx, .xls, atau .csv dari SiPintar.'], 422);
        }

        $path = $file->getRealPath();

        try {
            $rows = SimpleXlsxReader::readFile($path);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal membaca berkas Excel: ' . $e->getMessage()], 422);
        }

        if (empty($rows)) {
            return response()->json(['success' => false, 'message' => 'Berkas Excel kosong atau baris data tidak terdeteksi.'], 422);
        }

        $result = PipService::importRiwayatRows($rows, $jenisSk);

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    /**
     * Tambah Riwayat PIP Manual (Admin Only)
     */
    public function storeRiwayatManual(Request $request): JsonResponse
    {
        if (!$this->isAdminUser()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        PipService::ensureSchema();

        $data = $request->validate([
            'peserta_didik_id' => 'nullable|string',
            'nisn'             => 'nullable|string|max:20',
            'nama_pd'          => 'required|string|max:150',
            'rombel'           => 'nullable|string|max:50',
            'nominal'          => 'nullable|numeric|min:0',
            'nomor_sk'         => 'nullable|string|max:100',
            'jenis_sk'         => 'required|in:pemberian,nominasi',
            'tahap'            => 'nullable|string|max:100',
            'tahun'            => 'nullable|string|max:20',
            'status_cair'      => 'nullable|string|max:50',
            'tanggal_aktivasi' => 'nullable|string|max:30',
        ]);

        // Validasi: jika jenis_sk nominasi, jangan simpan jika sudah ada pada sk pemberian!
        if ($data['jenis_sk'] === 'nominasi') {
            $hasPemberian = PipRiwayat::where('jenis_sk', 'pemberian')
                ->where(function ($q) use ($data) {
                    if (!empty($data['peserta_didik_id'])) {
                        $q->where('peserta_didik_id', $data['peserta_didik_id']);
                    } elseif (!empty($data['nisn'])) {
                        $q->where('nisn', $data['nisn']);
                    } else {
                        $q->where('nama_pd', $data['nama_pd']);
                    }
                })
                ->exists();

            if ($hasPemberian) {
                return response()->json([
                    'success' => false,
                    'message' => "Siswa {$data['nama_pd']} sudah memiliki SK Pemberian. SK Nominasi tidak dapat disimpan untuk siswa yang telah berstatus penerima SK Pemberian.",
                ], 422);
            }
        }

        $rombelId = null;
        if (!empty($data['peserta_didik_id'])) {
            $rombelId = DB::table('peserta_didik')->where('peserta_didik_id', $data['peserta_didik_id'])->value('rombongan_belajar_id');
        }

        PipRiwayat::create([
            'peserta_didik_id'     => $data['peserta_didik_id'] ?: null,
            'nisn'                 => $data['nisn'] ?: null,
            'nama_pd'              => $data['nama_pd'],
            'rombel'               => $data['rombel'] ?: null,
            'rombongan_belajar_id' => $rombelId,
            'nominal'              => $data['nominal'] ?: null,
            'nomor_sk'             => $data['nomor_sk'] ?: null,
            'jenis_sk'             => $data['jenis_sk'],
            'tahap'                => $data['tahap'] ?: null,
            'tahun'                => $data['tahun'] ?: date('Y'),
            'status_cair'          => $data['status_cair'] ?: null,
            'tanggal_aktivasi'     => $data['tanggal_aktivasi'] ?: null,
        ]);

        $label = $data['jenis_sk'] === 'nominasi' ? 'SK Nominasi' : 'SK Pemberian';
        return response()->json([
            'success' => true,
            'message' => "Catatan riwayat PIP ({$label}) berhasil ditambahkan.",
        ]);
    }

    /**
     * Hapus Catatan Riwayat PIP (Admin Only)
     */
    public function destroyRiwayat(int $id): JsonResponse
    {
        if (!$this->isAdminUser()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        PipRiwayat::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Catatan riwayat PIP berhasil dihapus.',
        ]);
    }

    /**
     * Simpan Pengaturan Kunci & Batas Waktu Usulan PIP (Admin Only)
     */
    public function updatePengaturan(Request $request): JsonResponse
    {
        PipService::ensureSchema();

        if (!$this->isAdminUser()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Khusus Administrator.'], 403);
        }

        $data = $request->validate([
            'tahun'            => 'required|string|max:20',
            'fase'             => 'nullable|in:fase_1,fase_2',
            'tahun_pelajaran'  => 'nullable|string|max:30',
            'judul_usulan'     => 'nullable|string|max:150',
            'is_locked'        => 'nullable',
            'kunci_otomatis'   => 'nullable',
            'batas_waktu'      => 'nullable|string',
            'pesan_pengumuman' => 'nullable|string|max:1000',
        ]);

        $settings = PipPengaturan::getSettings($data['tahun']);

        $user = session('user');
        $updater = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Admin')) : ($user->nama ?? ($user->name ?? 'Admin'));

        $isLocked = filter_var($request->input('is_locked'), FILTER_VALIDATE_BOOLEAN);
        $kunciOtomatis = filter_var($request->input('kunci_otomatis', true), FILTER_VALIDATE_BOOLEAN);

        $batasWaktu = null;
        if (!empty($data['batas_waktu'])) {
            try {
                $batasWaktu = \Carbon\Carbon::parse($data['batas_waktu']);
            } catch (\Throwable) {}
        }

        $fase = $data['fase'] ?? ($settings->fase ?: (date('n') <= 6 ? 'fase_1' : 'fase_2'));
        $faseLabel = PipUsulan::getFaseLabel($fase);
        $judul = trim((string) ($data['judul_usulan'] ?? ''));
        if ($judul === '') {
            $judul = "Usulan PIP Tahun {$data['tahun']} — {$faseLabel}";
        }

        $settings->fase = $fase;
        $settings->tahun_pelajaran = !empty($data['tahun_pelajaran']) ? trim($data['tahun_pelajaran']) : $settings->tahun_pelajaran;
        $settings->judul_usulan = $judul;
        $settings->is_locked = $isLocked;
        $settings->kunci_otomatis = $kunciOtomatis;
        $settings->batas_waktu = $batasWaktu;
        $settings->pesan_pengumuman = $data['pesan_pengumuman'] ?? null;
        $settings->updated_by = $updater;
        $settings->save();

        $info = PipPengaturan::getStatusInfo($data['tahun']);

        $statusDesc = $info['is_closed'] ? 'Pengusulan saat ini TERKUNCI untuk Wali Kelas.' : 'Pengusulan saat ini DIBUKA untuk Wali Kelas.';

        return response()->json([
            'success'     => true,
            'message'     => "Pengaturan {$judul} berhasil disimpan. {$statusDesc}",
            'status_info' => $info,
        ]);
    }
}
