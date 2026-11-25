<?php

namespace App\Http\Controllers\Concerns;

use App\Models\RolePermission;
use App\Support\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Logika akses bersama modul E-Learning (materi & tugas).
 *
 * Menghindari duplikasi antar `ElearningController` dan `ElearningTugasController`.
 * Data induk (pembelajaran, peserta_didik, gtk) dibaca saja — lihat §14 RENCANA_EKOSISTEM.
 */
trait ElearningContext
{
    /** Ukuran maks unggahan (KB). */
    protected const ELEARNING_MAX_UPLOAD_KB = 10240;

    /** Allowlist dokumen materi/tugas. */
    protected const ELEARNING_ALLOWED_MIMES = 'pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,webp';

    /** Izin kelola (CRUD) untuk peran ini. */
    protected function canManage(mixed $userOrRole, string $role, string $action): bool
    {
        return RolePermission::canAccess($userOrRole, 'menu_elearning', $action);
    }

    /** PTK ID dari sesi (guru). */
    protected function sessionPtkId(): ?string
    {
        $user = $this->sessionUser();
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

        return $ptkId ? (string) $ptkId : null;
    }

    /** Peserta didik ID dari sesi. */
    protected function sessionPdId(): ?string
    {
        $user = $this->sessionUser();
        $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);

        return $pdId ? (string) $pdId : null;
    }

    /** Rombel peserta didik (kolom denormalisasi di peserta_didik). */
    protected function rombelForPesertaDidik(?string $pdId): ?string
    {
        if (! $pdId || ! Table::exists('peserta_didik')) {
            return null;
        }

        $rombelId = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->value('rombongan_belajar_id');

        return $rombelId ? (string) $rombelId : null;
    }

    /**
     * Batas pembelajaran yang boleh dikelola/dilihat.
     * null = tanpa batas (admin/tendik).
     */
    protected function allowedPembelajaranIds(string $role, ?string $ptkId): ?array
    {
        if (in_array($role, ['admin', 'tendik'], true)) {
            return null;
        }

        if (! Table::exists('pembelajaran')) {
            return [];
        }

        if ($role === 'guru' && $ptkId) {
            return DB::table('pembelajaran')->where('ptk_id', $ptkId)->pluck('pembelajaran_id')->all();
        }

        if ($role === 'peserta_didik') {
            $rombelId = $this->rombelForPesertaDidik($this->sessionPdId());
            if (! $rombelId) {
                return [];
            }

            return DB::table('pembelajaran')->where('rombongan_belajar_id', $rombelId)->pluck('pembelajaran_id')->all();
        }

        return [];
    }

    /** Apakah peran ini berhak atas pembelajaran tersebut. */
    protected function ownsPembelajaran(string $role, ?string $ptkId, ?string $pembelajaranId, bool $allowStudentView = false): bool
    {
        if (in_array($role, ['admin', 'tendik'], true)) {
            return true;
        }
        if (! $pembelajaranId || ! Table::exists('pembelajaran')) {
            return false;
        }
        if ($role === 'guru' && $ptkId) {
            return DB::table('pembelajaran')
                ->where('pembelajaran_id', $pembelajaranId)
                ->where('ptk_id', $ptkId)
                ->exists();
        }
        if ($role === 'peserta_didik' && $allowStudentView) {
            $allowed = $this->allowedPembelajaranIds($role, null);

            return in_array($pembelajaranId, $allowed ?? [], true);
        }

        return false;
    }

    /** Opsi dropdown pembelajaran (dibatasi bila guru / peserta didik). */
    protected function pembelajaranOptions(?array $allowedIds)
    {
        if (! Table::exists('pembelajaran')) {
            return collect();
        }

        $query = DB::table('pembelajaran as p')
            ->leftJoin('rombongan_belajar as r', 'p.rombongan_belajar_id', '=', 'r.rombongan_belajar_id')
            ->select(
                'p.pembelajaran_id',
                DB::raw('COALESCE(p.nama_mata_pelajaran, p.mata_pelajaran_id_str, "-") as nama_mapel'),
                'r.nama as nama_rombel'
            );

        if ($allowedIds !== null) {
            if (empty($allowedIds)) {
                return collect();
            }
            $query->whereIn('p.pembelajaran_id', $allowedIds);
        }

        return $query->orderBy('r.nama')->orderBy('nama_mapel')->get();
    }

    /** Simpan unggahan; kembalikan path relatif atau false bila ditolak. */
    protected function saveUpload(Request $request, string $field = 'file_materi', string $prefix = 'materi'): string|false
    {
        $file = $request->file($field);
        if (! $file || ! $file->isValid()) {
            return false;
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        $allowedExt = explode(',', self::ELEARNING_ALLOWED_MIMES);
        if (! in_array($ext, $allowedExt, true)) {
            return false;
        }

        // Gambar (jpg/jpeg/png/webp) dikompres ulang otomatis; dokumen disimpan apa adanya.
        $hasil = app(\App\Services\ImageOptimizerService::class)->optimizeAndSaveImage(
            $file,
            storage_path('app/public/elearning'),
            $prefix . '_' . \Illuminate\Support\Str::random(8)
        );

        return 'elearning/' . $hasil['filename'];
    }

    /** Hapus berkas dari disk publik. */
    protected function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Bersihkan HTML isi materi/soal agar aman dari XSS.
     * Daftar tag diizinkan sama dengan sanitizer Berita (pola yang sudah terbukti).
     */
    protected function sanitizeHtml(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        if (strip_tags($raw) === $raw) {
            return nl2br(e($raw));
        }

        $allowed = '<p><br><strong><b><em><i><u><s><strike><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><a><hr><table><thead><tbody><tr><th><td><span><code><pre><sup><sub>';
        $cleaned = strip_tags($raw, $allowed);
        // Buang atribut event handler on* lalu skema javascript: pada href.
        $cleaned = preg_replace('/<([a-z1-6]+)([^>]*?)\son[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)([^>]*?)>/i', '<$1$2$3>', $cleaned);
        $cleaned = preg_replace('/href\s*=\s*([\'"])\s*javascript:[^\'"]*\1/i', 'href="#"', $cleaned);

        return $cleaned;
    }

    /** Normalisasi & validasi daftar soal kuis dari input form (JSON). */
    protected function normalizeSoalList(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $i => $s) {
            if (! is_array($s)) {
                continue;
            }
            $tipe = in_array($s['tipe'] ?? 'pg', ['pg', 'bs', 'isian'], true) ? $s['tipe'] : 'pg';
            $pertanyaan = trim((string) ($s['pertanyaan'] ?? ''));
            if ($pertanyaan === '') {
                continue;
            }

            $opsi = null;
            $kunci = null;
            if ($tipe === 'pg') {
                $opsi = array_values(array_filter(array_map(
                    fn ($o) => trim((string) $o),
                    is_array($s['opsi'] ?? null) ? $s['opsi'] : []
                ), fn ($o) => $o !== ''));
                if (count($opsi) < 2) {
                    continue; // PG butuh minimal 2 opsi
                }
                $kunci = trim((string) ($s['kunci'] ?? ''));
                if ($kunci === '' || ! in_array($kunci, $opsi, true)) {
                    continue; // kunci wajib salah satu opsi
                }
            } elseif ($tipe === 'bs') {
                $kunci = in_array(($s['kunci'] ?? ''), ['benar', 'salah'], true) ? $s['kunci'] : null;
                if ($kunci === null) {
                    continue;
                }
            } else { // isian
                $kunci = trim((string) ($s['kunci'] ?? ''));
            }

            $out[] = [
                'tipe' => $tipe,
                'pertanyaan' => mb_substr($pertanyaan, 0, 2000),
                'opsi' => $opsi ? json_encode($opsi, JSON_UNESCAPED_UNICODE) : null,
                'kunci' => $kunci !== null ? mb_substr($kunci, 0, 255) : null,
                'bobot' => max(1, min(100, (int) ($s['bobot'] ?? 1))),
                'urutan' => $i,
            ];
        }

        return $out;
    }

    /** Nilai jawaban kuis peserta didik terhadap daftar soal (auto-nilai). */
    protected function scoreKuis(iterable $soalList, array $jawaban): array
    {
        $benar = 0;
        $total = 0;
        $bobotBenar = 0;
        $bobotTotal = 0;

        foreach ($soalList as $s) {
            $total++;
            $bobot = max(1, (int) $s->bobot);
            $bobotTotal += $bobot;

            $jawab = $jawaban[(string) $s->id] ?? null;
            $jawab = is_scalar($jawab) ? trim((string) $jawab) : '';

            $kunci = (string) ($s->kunci ?? '');
            $ok = false;
            if ($jawab !== '' && $kunci !== '') {
                $ok = $s->tipe === 'isian'
                    ? mb_strtolower($jawab) === mb_strtolower($kunci)
                    : $jawab === $kunci;
            }
            if ($ok) {
                $benar++;
                $bobotBenar += $bobot;
            }
        }

        $skor = $bobotTotal > 0 ? round($bobotBenar / $bobotTotal * 100, 2) : 0;

        return ['benar' => $benar, 'total' => $total, 'skor' => $skor];
    }

    protected function deny(Request $request, string $action)
    {
        $msg = "Akses ditolak: Anda tidak memiliki hak untuk {$action} data.";
        if ($request->expectsJson()) {
            return response()->json(['status' => 'error', 'message' => $msg], 403);
        }

        return back()->with('error', $msg);
    }

    protected function reject(Request $request, string $msg, int $code = 422)
    {
        if ($request->expectsJson()) {
            return response()->json(['status' => 'error', 'message' => $msg], $code);
        }

        return back()->with('error', $msg);
    }
}
