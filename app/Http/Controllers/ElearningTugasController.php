<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;
use App\Http\Controllers\Concerns\ElearningContext;
use App\Models\LearnPengumpulan;
use App\Models\LearnTugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Modul E-Learning — Tugas, Pengumpulan & Penilaian.
 *
 * Lihat .agents/docs/RENCANA_EKOSISTEM.md §14. Prinsip kestabilan:
 * - tanpa Redis/queue/worker (sinkron); unggahan hanya dokumen kecil.
 * - batas akses per pembelajaran (guru = yang diampu, peserta didik = rombelnya).
 */
class ElearningTugasController extends Controller
{
    use ChecksAuthenticatedSession;
    use ElearningContext;

    /** Simpan tugas baru (Create). */
    public function store(Request $request)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManage($user ?: $role, $role, 'create')) {
            return $this->deny($request, 'menambah');
        }

        $validated = $request->validate($this->rules());

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $validated['pembelajaran_id'])) {
            return $this->deny($request, 'mengelola tugas pada pembelajaran ini');
        }

        $tugas = LearnTugas::create([
            'pembelajaran_id' => $validated['pembelajaran_id'],
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'deadline' => $validated['deadline'] ?? null,
            'bobot' => (int) ($validated['bobot'] ?? 0),
            'status' => $validated['status'] ?? 'draft',
            'created_by' => $this->sessionPtkId(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Tugas berhasil disimpan.', 'id' => $tugas->id]);
        }

        return back()->with('success', 'Tugas E-Learning berhasil ditambahkan.');
    }

    /** Detail tugas (Read) — untuk form ubah. */
    public function show(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManage($user ?: $role, $role, 'read')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $tugas = LearnTugas::find($id);
        if (! $tugas) {
            return response()->json(['status' => 'error', 'message' => 'Tugas tidak ditemukan.'], 404);
        }

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $tugas->pembelajaran_id, true)) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $tugas->id,
                'pembelajaran_id' => $tugas->pembelajaran_id,
                'judul' => $tugas->judul,
                'deskripsi' => $tugas->deskripsi,
                'deadline' => optional($tugas->deadline)->format('Y-m-d\TH:i'),
                'bobot' => $tugas->bobot,
                'status' => $tugas->status,
            ],
        ]);
    }

    /** Perbarui tugas (Update). */
    public function update(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManage($user ?: $role, $role, 'update')) {
            return $this->deny($request, 'mengubah');
        }

        $tugas = LearnTugas::find($id);
        if (! $tugas) {
            return $this->reject($request, 'Tugas tidak ditemukan.', 404);
        }

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $tugas->pembelajaran_id)) {
            return $this->deny($request, 'mengelola tugas pada pembelajaran ini');
        }

        $validated = $request->validate($this->rules());

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $validated['pembelajaran_id'])) {
            return $this->deny($request, 'mengelola tugas pada pembelajaran ini');
        }

        $tugas->update([
            'pembelajaran_id' => $validated['pembelajaran_id'],
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'deadline' => $validated['deadline'] ?? null,
            'bobot' => (int) ($validated['bobot'] ?? 0),
            'status' => $validated['status'] ?? 'draft',
        ]);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Tugas berhasil diperbarui.']);
        }

        return back()->with('success', 'Tugas E-Learning berhasil diperbarui.');
    }

    /** Hapus tugas beserta seluruh pengumpulannya (Delete). */
    public function destroy(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManage($user ?: $role, $role, 'delete')) {
            return $this->deny($request, 'menghapus');
        }

        $tugas = LearnTugas::find($id);
        if (! $tugas) {
            return $this->reject($request, 'Tugas tidak ditemukan.', 404);
        }

        if (! $this->ownsPembelajaran($role, $this->sessionPtkId(), $tugas->pembelajaran_id)) {
            return $this->deny($request, 'mengelola tugas pada pembelajaran ini');
        }

        LearnPengumpulan::where('tugas_id', $tugas->id)->delete();
        $tugas->delete();

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Tugas berhasil dihapus.']);
        }

        return back()->with('success', 'Tugas E-Learning berhasil dihapus.');
    }

    /** Peserta didik mengumpulkan / memperbarui jawaban tugas. */
    public function submitPengumpulan(Request $request, $tugasId)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if ($role !== 'peserta_didik') {
            return $this->deny($request, 'mengumpulkan tugas ini');
        }

        if (! $this->canManage($user ?: $role, $role, 'read')) {
            return $this->deny($request, 'mengumpulkan tugas ini');
        }

        $pdId = $this->sessionPdId();
        if (! $pdId) {
            return $this->reject($request, 'Identitas peserta didik tidak ditemukan pada sesi.');
        }

        $tugas = LearnTugas::find($tugasId);
        if (! $tugas) {
            return $this->reject($request, 'Tugas tidak ditemukan.', 404);
        }

        if ($tugas->status !== 'terbit') {
            return $this->reject($request, 'Tugas belum dibuka atau sudah ditutup.');
        }

        if (! $this->ownsPembelajaran($role, null, $tugas->pembelajaran_id, true)) {
            return $this->deny($request, 'mengumpulkan tugas ini');
        }

        $validated = $request->validate([
            'jawaban_teks' => 'nullable|string|max:10000',
            'link_url' => 'nullable|url|max:500',
        ]);

        $existing = LearnPengumpulan::where('tugas_id', $tugas->id)
            ->where('peserta_didik_id', $pdId)
            ->first();

        $linkUrl = trim((string) ($validated['link_url'] ?? '')) ?: null;
        if ($linkUrl === null && $existing) {
            // Link tidak diubah pada pengumpulan ulang → pertahankan yang lama.
            $linkUrl = $existing->link_url;
        }

        if (empty(trim((string) ($validated['jawaban_teks'] ?? ''))) && ! $linkUrl) {
            return $this->reject($request, 'Isi jawaban teks atau tautan sebelum mengumpulkan.');
        }

        $data = [
            'jawaban_teks' => $validated['jawaban_teks'] ?? null,
            'link_url' => $linkUrl,
            'submitted_at' => now(),
        ];

        if ($existing) {
            // Pengumpulan ulang membatalkan penilaian sebelumnya (perlu dinilai ulang).
            $data['nilai'] = null;
            $data['dinilai_oleh'] = null;
            $existing->update($data);
        } else {
            $existing = LearnPengumpulan::create($data + [
                'tugas_id' => $tugas->id,
                'peserta_didik_id' => $pdId,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Jawaban berhasil dikumpulkan.']);
        }

        return back()->with('success', 'Jawaban tugas berhasil dikumpulkan.');
    }

    /** Peserta didik menarik kembali pengumpulannya. */
    public function destroyPengumpulan(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        $pengumpulan = LearnPengumpulan::find($id);
        if (! $pengumpulan) {
            return $this->reject($request, 'Pengumpulan tidak ditemukan.', 404);
        }

        $tugas = LearnTugas::find($pengumpulan->tugas_id);
        if (! $tugas) {
            return $this->reject($request, 'Tugas tidak ditemukan.', 404);
        }

        if ($role === 'peserta_didik') {
            if ($pengumpulan->peserta_didik_id !== $this->sessionPdId()) {
                return $this->deny($request, 'menarik pengumpulan ini');
            }
        } elseif (! $this->canManage($user ?: $role, $role, 'delete')
            || ! $this->ownsPembelajaran($role, $this->sessionPtkId(), $tugas->pembelajaran_id)) {
            return $this->deny($request, 'menarik pengumpulan ini');
        }

        $pengumpulan->delete();

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Pengumpulan berhasil ditarik.']);
        }

        return back()->with('success', 'Pengumpulan berhasil ditarik.');
    }

    /** Guru memberi / memperbarui nilai pengumpulan. */
    public function grade(Request $request, $id)
    {
        $user = $this->sessionUser();
        $role = $this->sessionRole();

        if (! $this->canManage($user ?: $role, $role, 'update')) {
            return $this->deny($request, 'menilai');
        }

        $pengumpulan = LearnPengumpulan::find($id);
        if (! $pengumpulan) {
            return $this->reject($request, 'Pengumpulan tidak ditemukan.', 404);
        }

        $tugas = LearnTugas::find($pengumpulan->tugas_id);
        if (! $tugas || ! $this->ownsPembelajaran($role, $this->sessionPtkId(), $tugas->pembelajaran_id)) {
            return $this->deny($request, 'menilai pengumpulan ini');
        }

        $validated = $request->validate([
            'nilai' => 'required|numeric|min:0|max:100',
        ]);

        $pengumpulan->update([
            'nilai' => $validated['nilai'],
            'dinilai_oleh' => $this->sessionPtkId(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Nilai berhasil disimpan.']);
        }

        return back()->with('success', 'Nilai berhasil disimpan.');
    }

    /** Aturan validasi tugas. */
    private function rules(): array
    {
        return [
            'pembelajaran_id' => 'required|string|max:50',
            'judul' => 'required|string|max:200',
            'deskripsi' => 'nullable|string|max:5000',
            'deadline' => 'nullable|date',
            'bobot' => 'nullable|integer|min:0|max:100',
            'status' => 'nullable|in:draft,terbit,tutup',
        ];
    }
}
