<?php

namespace App\Http\Controllers\Teknisi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;
use App\Models\RolePermission;
use Illuminate\Http\Request;
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
     * Daftar lokasi (ruang) untuk dropdown, dari master sarana prasarana.
     */
    protected function lokasiOptions(): array
    {
        if (!\App\Support\Table::exists('sarpras_ruang')) {
            return [];
        }

        return DB::table('sarpras_ruang')
            ->whereNotNull('nama_ruang')
            ->orderBy('nama_ruang')
            ->pluck('nama_ruang')
            ->all();
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
