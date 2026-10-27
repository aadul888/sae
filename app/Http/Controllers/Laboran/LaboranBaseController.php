<?php

namespace App\Http\Controllers\Laboran;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;
use App\Models\RolePermission;
use App\Support\LaboranScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Basis bersama seluruh modul Laboratorium per Jurusan.
 *
 * Menyediakan:
 *  - Pemeriksaan sesi & hak akses CRUD.
 *  - Konteks cakupan jurusan (LaboranScope).
 *  - Helper respons AJAX/redirect yang seragam.
 *  - Penomoran dokumen otomatis.
 */
abstract class LaboranBaseController extends Controller
{
    use ChecksAuthenticatedSession;

    /** Key permission utama modul laboran. */
    protected string $permissionKey = 'menu_laboran';

    /**
     * Siapkan konteks: cek sesi + resolusi cakupan jurusan.
     *
     * @return array{0: mixed, 1: array} [redirectResponse|null, context]
     */
    protected function boot(Request $request): array
    {
        if ($res = $this->checkAuth()) {
            return [$res, []];
        }

        $user = session('user');
        $context = LaboranScope::resolve($user);

        return [null, $context];
    }

    protected function can(string $action, ?string $key = null): bool
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        return RolePermission::canAccess($user ?: $role, $key ?: $this->permissionKey, $action);
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
     * Pastikan baris data berada dalam cakupan jurusan pengguna.
     * Mengembalikan baris atau null bila tidak berhak / tidak ada.
     */
    protected function findScoped(string $table, $id, array $context, string $column = 'jurusan_id')
    {
        $row = DB::table($table)->where('id', $id)->first();

        if (!$row) {
            return null;
        }

        if (!LaboranScope::allows($row->{$column} ?? null, $context)) {
            return null;
        }

        return $row;
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

        // Proteksi tambahan bila terjadi tabrakan (mis. input bersamaan)
        $guard = 0;
        while (DB::table($table)->where($column, $nomor)->exists() && $guard < 50) {
            $urut++;
            $nomor = sprintf('%s/%s/%04d', $prefix, $periode, $urut);
            $guard++;
        }

        return $nomor;
    }

    /**
     * Daftar item alat/bahan dalam cakupan (untuk dropdown pilihan).
     *
     * @param  bool  $hanyaPenanggungJawab  Batasi ke ruang yang penanggung jawabnya
     *         adalah pengguna aktif. Bila pengguna tidak menjadi penanggung jawab
     *         ruang manapun, filter dilewati (tetap memakai cakupan jurusan).
     */
    protected function itemOptions(array $context, ?string $jenis = null, bool $hanyaPenanggungJawab = false)
    {
        $q = DB::table('laboran_bahan_alat')
            ->select('id', 'kode_item', 'nama_item', 'jenis', 'satuan', 'ruang_lab_nama', 'stok_tersedia', 'kondisi', 'jurusan_id')
            ->orderBy('nama_item');

        LaboranScope::apply($q, 'jurusan_id', $context);

        if ($jenis) {
            $q->where('jenis', $jenis);
        }

        if ($hanyaPenanggungJawab && !empty($context['ptk_id'])) {
            $ruangPj = DB::table('sarpras_ruang')
                ->where('penanggung_jawab_ptk_id', $context['ptk_id'])
                ->whereNotNull('nama_ruang')
                ->pluck('nama_ruang')
                ->all();

            if (!empty($ruangPj)) {
                $q->whereIn('ruang_lab_nama', $ruangPj);
            }
        }

        return $q->get();
    }

    /**
     * Daftar lokasi (ruang/lab/bengkel/lahan) untuk dropdown.
     */
    protected function lokasiOptions()
    {
        $master = DB::table('sarpras_ruang')
            ->orderBy('nama_ruang')
            ->pluck('nama_ruang')
            ->all();

        $existing = DB::table('laboran_bahan_alat')
            ->whereNotNull('ruang_lab_nama')
            ->distinct()
            ->pluck('ruang_lab_nama')
            ->all();

        return array_values(array_unique(array_merge($master, $existing)));
    }
}
