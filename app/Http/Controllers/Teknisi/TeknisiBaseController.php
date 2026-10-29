<?php

namespace App\Http\Controllers\Teknisi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Basis bersama seluruh modul Teknisi & IT.
 *
 * Menyediakan:
 *  - Pemeriksaan sesi & hak akses CRUD.
 *  - Konteks pengguna (ptk_id, user_id, nama) untuk jejak petugas.
 *  - Helper respons AJAX/redirect yang seragam.
 *  - Penomoran dokumen otomatis.
 *
 * Teknisi tidak dibatasi per jurusan (berbeda dengan Laboran) sehingga tidak
 * memakai `LaboranScope`; data cukup ditandai petugas yang mengerjakan.
 */
abstract class TeknisiBaseController extends Controller
{
    use ChecksAuthenticatedSession;

    /** Key permission utama modul teknisi. */
    protected string $permissionKey = 'menu_teknisi';

    /**
     * Kolom identitas petugas yang TIDAK diminta dari berkas impor.
     *
     * Nilainya selalu diisi otomatis dari akun yang sedang login, sehingga
     * template Excel tidak memuat kolom nama/ID petugas.
     *
     * @var array<int, string>
     */
    public const AUTO_OWNER_COLUMNS = ['petugas_nama', 'petugas_ptk_id'];

    /**
     * Siapkan konteks pengguna: cek sesi + resolusi identitas petugas.
     *
     * @return array{0: mixed, 1: array} [redirectResponse|null, context]
     */
    protected function boot(Request $request): array
    {
        if ($res = $this->checkAuth()) {
            return [$res, []];
        }

        $user = session('user');
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        $userId = is_array($user)
            ? ($user['pengguna_id'] ?? ($user['id'] ?? null))
            : ($user->pengguna_id ?? ($user->id ?? null));
        $nama = is_array($user)
            ? ($user['nama'] ?? ($user['name'] ?? ''))
            : ($user->nama ?? ($user->name ?? ''));

        return [null, [
            'ptk_id'  => $ptkId,
            'user_id' => $userId,
            'nama'    => $nama,
            'role'    => is_array($user) ? ($user['role'] ?? '') : ($user->role ?? ''),
        ]];
    }

    protected function can(string $action, ?string $key = null): bool
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        return RolePermission::canAccess($user ?: $role, $key ?: $this->permissionKey, $action);
    }

    /**
     * Kode tugas tambahan aktif milik pengguna (KEPALA_TAS, TEKNISI_IT, ...).
     *
     * @return array<int, string>
     */
    protected function dutyCodes(array $context): array
    {
        $userId = $context['user_id'] ?? null;
        $ptkId  = $context['ptk_id'] ?? null;

        if (!$userId && !$ptkId) {
            return [];
        }
        if (!\App\Support\Table::existsAll('ptk_tugas_tambahan', 'ref_tugas_tambahan')) {
            return [];
        }

        return DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->where('ptt.is_active', true)
            ->where('rtt.is_active', true)
            ->where(function ($q) use ($userId, $ptkId) {
                if ($userId) {
                    $q->where('ptt.user_id', $userId);
                }
                if ($ptkId) {
                    $q->orWhere('ptt.ptk_id', $ptkId);
                }
            })
            ->pluck('rtt.kode')
            ->all();
    }

    /**
     * Apakah pengguna berhak melihat SELURUH data teknisi (bukan hanya miliknya)?
     *
     * Koordinator: admin, Kepala TAS, Waka Sarpras, dan staf administrasi sarpras.
     * Teknisi lapangan/IT hanya melihat catatannya sendiri.
     */
    protected function isKoordinator(array $context): bool
    {
        if (($context['role'] ?? '') === 'admin') {
            return true;
        }

        $koordinator = ['KEPALA_TAS', 'WAKA_SARPRAS', 'STAF_SARPRAS'];

        return count(array_intersect($this->dutyCodes($context), $koordinator)) > 0;
    }

    /**
     * Batasi query ke data milik pengguna kecuali ia koordinator.
     *
     * Kepemilikan: kolom `user_id` = akun pembuat, atau baris lama tanpa
     * `user_id` yang `petugas_ptk_id`-nya milik pengguna.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string|null  $alias  Awalan tabel (mis. 'a') bila query memakai alias.
     * @return \Illuminate\Database\Query\Builder
     */
    protected function scopeOwned($query, array $context, ?string $alias = null, ?string $ptkColumn = 'petugas_ptk_id')
    {
        if ($this->isKoordinator($context)) {
            return $query;
        }

        $p = $alias ? $alias . '.' : '';
        $userId = $context['user_id'] ?? null;
        $ptkId  = $context['ptk_id'] ?? null;

        if (!$userId && !$ptkId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($p, $userId, $ptkId, $ptkColumn) {
            if ($userId) {
                $q->where($p . 'user_id', $userId);
            }
            if ($ptkId && $ptkColumn) {
                $q->orWhere(function ($q2) use ($p, $ptkId, $ptkColumn) {
                    $q2->whereNull($p . 'user_id')->where($p . $ptkColumn, $ptkId);
                });
            }
        });
    }

    /**
     * Apakah baris ini boleh diubah/dihapus pengguna?
     */
    protected function ownsRow(object $row, array $context, ?string $ptkColumn = 'petugas_ptk_id'): bool
    {
        if ($this->isKoordinator($context)) {
            return true;
        }

        $userId = $context['user_id'] ?? null;
        $ptkId  = $context['ptk_id'] ?? null;

        if ($userId && (string) ($row->user_id ?? '') === (string) $userId) {
            return true;
        }

        return $ptkId && empty($row->user_id) && $ptkColumn
            && (string) ($row->{$ptkColumn} ?? '') === (string) $ptkId;
    }

    /**
     * Data hak akses CRUD untuk dikirim ke view.
     */
    protected function abilities(?string $key = null): array
    {
        return [
            'canCreate' => $this->can('create', $key),
            'canRead'   => $this->can('read', $key),
            'canUpdate' => $this->can('update', $key),
            'canDelete' => $this->can('delete', $key),
        ];
    }

    /**
     * Id ruang (`sarpras_ruang.id`) yang penanggung jawabnya = akun login.
     *
     * Dibaca LANGSUNG dari master Sarpras setiap request (tanpa cache) agar
     * pergantian PJ di Ruang & Gedung langsung menggeser cakupan data teknisi.
     * `null` = pengguna tidak dibatasi (koordinator melihat semua).
     *
     * @return array<int, int>|null
     */
    protected function ruangMilikPj(array $context): ?array
    {
        if ($this->isKoordinator($context)) {
            return null;
        }

        // PJ di Ruang & Gedung kadang diisi `ptk_id` GTK, kadang `pengguna_id`
        // (lihat join gtk + pengguna di SarprasRuangController).
        $kunci = array_values(array_filter(
            [$context['ptk_id'] ?? null, $context['user_id'] ?? null],
            fn ($v) => $v !== null && $v !== ''
        ));

        if (empty($kunci) || !\App\Support\Table::exists('sarpras_ruang')) {
            return [];
        }

        return DB::table('sarpras_ruang')
            ->whereIn('penanggung_jawab_ptk_id', $kunci)
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * Batasi query ke ruang yang PJ-nya akun login (koordinator: tanpa batas).
     *
     * Baris lama tanpa `ruang_id` (dibuat manual sebelum pemetaan ruang) tetap
     * tampil bagi pembuatnya agar data lama tidak hilang.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $column  Kolom berisi `sarpras_ruang.id` pada tabel target.
     * @param  bool  $izinkanTanpaRuang  Ikut tampilkan baris `ruang_id` kosong milik akun login.
     */
    protected function scopeRuangPj($query, array $context, string $column, ?string $alias = null, bool $izinkanTanpaRuang = false)
    {
        $ids = $this->ruangMilikPj($context);
        if ($ids === null) {
            return $query;
        }

        $kolom = ($alias ? $alias . '.' : '') . $column;

        if (empty($ids)) {
            if (!$izinkanTanpaRuang) {
                return $query->whereRaw('1 = 0');
            }

            return $this->scopeTanpaRuangPj($query, $context, $column, $alias);
        }

        if (!$izinkanTanpaRuang) {
            return $query->whereIn($kolom, $ids);
        }

        return $query->where(function ($q) use ($kolom, $ids, $context, $column, $alias) {
            $q->whereIn($kolom, $ids);
            $this->scopeTanpaRuangPj($q, $context, $column, $alias, true);
        });
    }

    /**
     * Syarat tambahan: `ruang_id` kosong DAN baris milik akun login.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    private function scopeTanpaRuangPj($query, array $context, string $column, ?string $alias, bool $or = false)
    {
        $kolom  = ($alias ? $alias . '.' : '') . $column;
        $userId = $context['user_id'] ?? null;

        $closure = fn ($q) => $userId
            ? $q->whereNull($kolom)->where('user_id', $userId)
            : $q->whereRaw('1 = 0');

        return $or ? $query->orWhere($closure) : $query->where($closure);
    }

    /**
     * Kepemilikan berbasis ruang PJ: baris boleh diubah bila berada di ruang
     * yang PJ-nya akun login, atau baris tanpa ruang milik akun login.
     */
    protected function ownsRowRuang(object $row, array $context, ?string $ruangColumn = 'ruang_id'): bool
    {
        if ($this->isKoordinator($context)) {
            return true;
        }

        $ruangId = $row->{$ruangColumn} ?? null;
        if ($ruangId !== null && $ruangId !== '') {
            $ids = $this->ruangMilikPj($context);

            return is_array($ids) && in_array((int) $ruangId, $ids, true);
        }

        return (string) ($row->user_id ?? '') === (string) ($context['user_id'] ?? '');
    }

    /**
     * Id ruang (`sarpras_ruang.id`) dari nama ruang master; null bila nama
     * kosong atau bukan nama ruang master (area bebas).
     */
    protected function ruangIdDariNama(?string $nama): ?int
    {
        $nama = trim((string) $nama);
        if ($nama === '' || !\App\Support\Table::exists('sarpras_ruang')) {
            return null;
        }

        $id = DB::table('sarpras_ruang')->where('nama_ruang', $nama)->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * Petugas pemilik baris = penanggung jawab ruang (live dari master Sarpras).
     *
     * Ruang tanpa PJ (atau area bebas tanpa `ruang_id`) jatuh ke akun login.
     * Pemilik edit (`user_id`) tetap akun login — lihat store() tiap modul.
     *
     * @return array{ptk_id: ?string, nama: ?string}
     */
    protected function pjUntukRuang(?int $ruangId, array $context): array
    {
        $fallback = [
            'ptk_id' => $context['ptk_id'] ?? null,
            'nama'   => $context['nama'] ?? null,
        ];

        if (!$ruangId) {
            return $fallback;
        }

        $pjNama = $this->pjNamaPerRuang([$ruangId]);
        $ptkId  = DB::table('sarpras_ruang')->where('id', $ruangId)->value('penanggung_jawab_ptk_id');

        return [
            'ptk_id' => $ptkId ?: $fallback['ptk_id'],
            'nama'   => $pjNama[$ruangId] ?? $fallback['nama'],
        ];
    }

    /**
     * Peta `sarpras_ruang.id` => nama penanggung jawab saat ini.
     *
     * @param  array<int, int|string|null>  $ruangIds
     * @return array<int, string>
     */
    protected function pjNamaPerRuang(array $ruangIds): array
    {
        $ids = array_values(array_filter(array_map('intval', $ruangIds)));
        if (empty($ids) || !\App\Support\Table::exists('sarpras_ruang')) {
            return [];
        }

        return DB::table('sarpras_ruang as r')
            ->leftJoin('gtk as g', 'r.penanggung_jawab_ptk_id', '=', 'g.ptk_id')
            ->leftJoin('pengguna as p', 'r.penanggung_jawab_ptk_id', '=', 'p.pengguna_id')
            ->whereIn('r.id', $ids)
            ->get(['r.id', DB::raw('COALESCE(g.nama, p.nama) as pj_nama')])
            ->filter(fn ($row) => filled($row->pj_nama))
            ->mapWithKeys(fn ($row) => [(int) $row->id => (string) $row->pj_nama])
            ->all();
    }

    /**
     * Daftar lokasi (ruang) untuk dropdown, dari master sarana prasarana.
     *
     * Bila `$context` diisi, hanya ruang yang PJ-nya akun login (koordinator:
     * semua ruang) — supaya teknisi tak memilih ruang di luar tanggung jawabnya.
     */
    protected function lokasiOptions(?array $context = null): array
    {
        if (!\App\Support\Table::exists('sarpras_ruang')) {
            return [];
        }

        $query = DB::table('sarpras_ruang')->whereNotNull('nama_ruang');

        if ($context !== null) {
            $ids = $this->ruangMilikPj($context);
            if (is_array($ids)) {
                $query->whereIn('id', $ids);
            }
        }

        return $query->orderBy('nama_ruang')->pluck('nama_ruang')->all();
    }

    /**
     * Batasi query ke baris yang penanggung jawabnya = akun login.
     *
     * Dipakai modul yang petugasnya mengikuti PJ ruang Sarpras (mis. Master
     * Area & Zona). Bila akun punya `ptk_id`, baris dicocokkan lewat kolom PTK
     * (dengan cadangan nama bila PTK kosong); akun tanpa PTK dicocokkan nama.
     * Koordinator tidak dibatasi.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    protected function scopePetugasPj($query, array $context, string $ptkColumn, string $namaColumn, ?string $alias = null)
    {
        if ($this->isKoordinator($context)) {
            return $query;
        }

        $p = $alias ? $alias . '.' : '';
        $ptkId = $context['ptk_id'] ?? null;
        $nama  = $context['nama'] ?? null;

        if (!$ptkId && !$nama) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($p, $ptkId, $nama, $ptkColumn, $namaColumn) {
            if ($ptkId) {
                $q->where($p . $ptkColumn, $ptkId)
                  ->orWhere(fn ($q2) => $q2->whereNull($p . $ptkColumn)->where($p . $namaColumn, $nama));
            } else {
                $q->where($p . $namaColumn, $nama);
            }
        });
    }

    /**
     * Respons seragam: JSON untuk AJAX, redirect untuk submit form biasa.
     */
    protected function respond(Request $request, bool $ok, string $message, int $code = 200, array $extra = [])
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(array_merge([
                'status'  => $ok ? 'success' : 'error',
                'message' => $message,
            ], $extra), $code);
        }

        return redirect()->back()->with($ok ? 'success' : 'error', $message);
    }

    /**
     * Ambil baris data; null bila tidak ada.
     */
    protected function findRow(string $table, $id)
    {
        return DB::table($table)->where('id', $id)->first();
    }

    /**
     * Nomor dokumen berurutan: PREFIX/YYYYMM/NNNN
     * Unik per tabel+kolom, aman dari bentrok dalam satu bulan.
     */
    protected function generateNomor(string $table, string $column, string $prefix): string
    {
        $periode = now()->format('Ym');
        $like = "{$prefix}/{$periode}/%";

        $last = DB::table($table)->where($column, 'like', $like)->orderByDesc('id')->value($column);

        $urut = 1;
        if ($last) {
            $parts = explode('/', $last);
            $urut = ((int) end($parts)) + 1;
        }

        $nomor = sprintf('%s/%s/%04d', $prefix, $periode, $urut);

        $guard = 0;
        while (DB::table($table)->where($column, $nomor)->exists() && $guard < 50) {
            $urut++;
            $nomor = sprintf('%s/%s/%04d', $prefix, $periode, $urut);
            $guard++;
        }

        return $nomor;
    }

    /**
     * Catat riwayat unggah (import) Excel: siapa pengguna yang mengunggah.
     *
     * Bersifat best-effort — kegagalan pencatatan tidak boleh menggagalkan
     * impor yang sudah berhasil. Bila tabel log belum ada, dilewati diam-diam.
     */
    protected function logImport(array $context, string $modul, string $judul, ?UploadedFile $file, int $imported, int $updated = 0): void
    {
        if (!\App\Support\Table::exists('teknisi_import_log')) {
            return;
        }

        try {
            DB::table('teknisi_import_log')->insert([
                'modul'         => mb_substr($modul, 0, 60),
                'judul'         => mb_substr($judul, 0, 200),
                'file_name'     => $file ? mb_substr($file->getClientOriginalName(), 0, 255) : null,
                'rows_imported' => $imported,
                'rows_updated'  => $updated,
                'user_id'       => $context['user_id'] ?? null,
                'user_nama'     => $context['nama'] ?? null,
                'user_role'     => $context['role'] ?? null,
                'ip_address'    => request()->ip(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        } catch (\Throwable $e) {
            // abaikan: pencatatan jejak bukan bagian kritis impor
        }
    }

    /**
     * Daftar teknisi (GTK dengan tugas tambahan TEKNISI_*) untuk dropdown.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    protected function teknisiOptions()
    {
        if (\App\Support\Table::exists('ptk_tugas_tambahan') && \App\Support\Table::exists('ref_tugas_tambahan')) {
            $rows = DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->leftJoin('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
                ->where('ptt.is_active', true)
                ->where('rtt.kode', 'like', 'TEKNISI%')
                ->select('ptt.ptk_id', 'gtk.nama')
                ->distinct()
                ->get();

            if ($rows->isNotEmpty()) {
                return $rows->filter(fn ($r) => !empty($r->ptk_id))->values();
            }
        }

        if (\App\Support\Table::exists('gtk')) {
            return DB::table('gtk')
                ->select('ptk_id', 'nama')
                ->whereNotNull('nama')
                ->orderBy('nama')
                ->limit(200)
                ->get();
        }

        return collect();
    }
}
