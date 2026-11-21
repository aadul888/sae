<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CbtContext;
use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;
use App\Http\Controllers\Concerns\ElearningContext;
use App\Models\Ujian;
use App\Models\UjianSoal;
use App\Models\UjianSoalItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Modul CBT — Definisi Ujian / kertas ujian (bagian "tenang").
 *
 * Lihat .agents/docs/RENCANA_EKOSISTEM.md §14.7: hanya menyusun kertas ujian
 * (judul, mapel, durasi, KKM, jadwal, pilihan soal). Pelaksanaan ujian serentak
 * BELUM ADA — menunggu fondasi Redis/queue/session §10.
 */
class CbtUjianController extends Controller
{
    use ChecksAuthenticatedSession;
    use ElearningContext;
    use CbtContext;

    /** Simpan definisi ujian baru (Create). */
    public function store(Request $request)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManageCbt($user ?: $role, $role, 'create')) {
            return $this->deny($request, 'menambah ujian');
        }

        $validated = $request->validate($this->rules());

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $validated['pembelajaran_id'])) {
            return $this->deny($request, 'mengelola ujian pada pembelajaran ini');
        }

        $ujian = Ujian::create([
            'pembelajaran_id' => $validated['pembelajaran_id'],
            'periode_id' => $validated['periode_id'] ?? null,
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'tipe' => $validated['tipe'],
            'durasi_menit' => (int) $validated['durasi_menit'],
            'kkm' => (int) ($validated['kkm'] ?? 75),
            'jadwal_mulai' => $validated['jadwal_mulai'] ?? null,
            'jadwal_selesai' => $validated['jadwal_selesai'] ?? null,
            'acak_soal' => (bool) ($validated['acak_soal'] ?? false),
            'acak_opsi' => (bool) ($validated['acak_opsi'] ?? false),
            'status' => $validated['status'] ?? 'draft',
            'token' => $this->resolveToken($validated['token'] ?? null),
            'created_by' => $this->sessionPtkId(),
        ]);

        $this->syncSoal($ujian, $request->input('soal_ids', []));

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Ujian berhasil disimpan.', 'id' => $ujian->id]);
        }

        return back()->with('success', 'Ujian berhasil ditambahkan.');
    }

    /** Detail ujian + daftar soal terpasang (Read). */
    public function show(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManageCbt($user ?: $role, $role, 'read')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        // Peserta didik hanya boleh melihat dasbor jadwal, bukan detail kertas ujian.
        if ($role === 'peserta_didik') {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $ujian = Ujian::find($id);
        if (! $ujian) {
            return response()->json(['status' => 'error', 'message' => 'Ujian tidak ditemukan.'], 404);
        }

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $ujian->pembelajaran_id, true)) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $ujian->id,
                'pembelajaran_id' => $ujian->pembelajaran_id,
                'periode_id' => $ujian->periode_id,
                'judul' => $ujian->judul,
                'deskripsi' => $ujian->deskripsi,
                'tipe' => $ujian->tipe,
                'durasi_menit' => $ujian->durasi_menit,
                'kkm' => $ujian->kkm,
                'jadwal_mulai' => optional($ujian->jadwal_mulai)->format('Y-m-d\TH:i'),
                'jadwal_selesai' => optional($ujian->jadwal_selesai)->format('Y-m-d\TH:i'),
                'acak_soal' => $ujian->acak_soal,
                'acak_opsi' => $ujian->acak_opsi,
                'status' => $ujian->status,
                'token' => $ujian->token,
                'soal_ids' => UjianSoalItem::where('ujian_id', $ujian->id)->pluck('soal_id')->all(),
            ],
        ]);
    }

    /** Perbarui definisi ujian (Update). */
    public function update(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManageCbt($user ?: $role, $role, 'update')) {
            return $this->deny($request, 'mengubah ujian');
        }

        $ujian = Ujian::find($id);
        if (! $ujian) {
            return $this->reject($request, 'Ujian tidak ditemukan.', 404);
        }

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $ujian->pembelajaran_id)) {
            return $this->deny($request, 'mengelola ujian pada pembelajaran ini');
        }

        $validated = $request->validate($this->rules());

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $validated['pembelajaran_id'])) {
            return $this->deny($request, 'mengelola ujian pada pembelajaran ini');
        }

        $ujian->update([
            'pembelajaran_id' => $validated['pembelajaran_id'],
            'periode_id' => $validated['periode_id'] ?? null,
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'tipe' => $validated['tipe'],
            'durasi_menit' => (int) $validated['durasi_menit'],
            'kkm' => (int) ($validated['kkm'] ?? 75),
            'jadwal_mulai' => $validated['jadwal_mulai'] ?? null,
            'jadwal_selesai' => $validated['jadwal_selesai'] ?? null,
            'acak_soal' => (bool) ($validated['acak_soal'] ?? false),
            'acak_opsi' => (bool) ($validated['acak_opsi'] ?? false),
            'status' => $validated['status'] ?? 'draft',
            'token' => $this->resolveToken($validated['token'] ?? null),
        ]);

        $this->syncSoal($ujian, $request->input('soal_ids', []));

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Ujian berhasil diperbarui.']);
        }

        return back()->with('success', 'Ujian berhasil diperbarui.');
    }

    /** Hapus ujian (Delete) — kertas soal ikut terhapus, bank soal tetap. */
    public function destroy(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManageCbt($user ?: $role, $role, 'delete')) {
            return $this->deny($request, 'menghapus ujian');
        }

        $ujian = Ujian::find($id);
        if (! $ujian) {
            return $this->reject($request, 'Ujian tidak ditemukan.', 404);
        }

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $ujian->pembelajaran_id)) {
            return $this->deny($request, 'mengelola ujian pada pembelajaran ini');
        }

        UjianSoalItem::where('ujian_id', $ujian->id)->delete();
        $ujian->delete();

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Ujian berhasil dihapus.']);
        }

        return back()->with('success', 'Ujian berhasil dihapus.');
    }

    /**
     * Sinkronkan soal terpasang. Hanya soal dengan pembelajaran sama yang diterima.
     * Mencegah soal mapel lain terselip ke kertas ujian.
     */
    private function syncSoal(Ujian $ujian, array $soalIds): void
    {
        $ids = collect($soalIds)->map(fn ($v) => (int) $v)->filter()->unique()->values();

        if ($ids->isNotEmpty()) {
            $ids = UjianSoal::whereIn('id', $ids)
                ->where('pembelajaran_id', $ujian->pembelajaran_id)
                ->pluck('id');
        }

        UjianSoalItem::where('ujian_id', $ujian->id)->delete();

        $urutan = 0;
        foreach ($ids as $soalId) {
            UjianSoalItem::create([
                'ujian_id' => $ujian->id,
                'soal_id' => $soalId,
                'urutan' => $urutan++,
            ]);
        }
    }

    /** Aturan validasi ujian. */
    private function rules(): array
    {
        return [
            'pembelajaran_id' => 'required|string|max:50',
            'periode_id' => 'nullable|integer|exists:cbt_periode_asesmen,id',
            'judul' => 'required|string|max:200',
            'deskripsi' => 'nullable|string|max:5000',
            'tipe' => 'required|in:ulangan,uts,uas,latihan',
            'durasi_menit' => 'required|integer|min:1|max:600',
            'kkm' => 'nullable|integer|min:0|max:100',
            'jadwal_mulai' => 'nullable|date',
            'jadwal_selesai' => 'nullable|date|after_or_equal:jadwal_mulai',
            'acak_soal' => 'nullable|boolean',
            'acak_opsi' => 'nullable|boolean',
            'status' => 'nullable|in:draft,terbit,ditutup',
            'token' => 'nullable|string|max:20',
            'soal_ids' => 'nullable|array',
            'soal_ids.*' => 'nullable|integer',
        ];
    }

    /**
     * Normalisasi token: kosong → null; "auto" → token acak baru; selain itu
     * dibersihkan jadi huruf besar tanpa spasi (maks 20).
     */
    private function resolveToken(?string $input): ?string
    {
        $input = trim((string) $input);
        if ($input === '') {
            return null;
        }
        if (strtolower($input) === 'auto') {
            return Ujian::generateToken();
        }

        return mb_substr(preg_replace('/[^A-Za-z0-9]/', '', $input), 0, 20);
    }
}
