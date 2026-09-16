<?php

namespace App\Http\Controllers;

use App\Models\Formulir;
use App\Models\FormulirRespon;
use App\Models\RolePermission;
use App\Models\SekolahMeta;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FormulirController extends Controller
{
    /**
     * Dapatkan user dan role aktif dari sesi
     */
    private function getCurrentUser()
    {
        $user = session('user');
        if (!$user && auth()->check()) {
            $user = auth()->user();
        }
        return $user;
    }

    private function getCurrentRole(): string
    {
        $user = $this->getCurrentUser();
        if (!$user) return 'tamu';
        return is_array($user) ? ($user['role'] ?? 'tamu') : ($user->role ?? 'tamu');
    }

    private function canManage(): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;
        $role = $this->getCurrentRole();
        if ($role === 'admin') return true;
        return RolePermission::canAccess($user, 'menu_formulir', 'create') || RolePermission::canAccess($user, 'menu_formulir', 'update');
    }

    /**
     * Tampilkan daftar formulir di dashboard
     */
    public function index(Request $request)
    {
        $user = $this->getCurrentUser();
        $role = $this->getCurrentRole();
        $canManage = $this->canManage();

        $query = Formulir::withCount('respon')->latest();

        // Jika peserta didik, tampilkan formulir yang diperuntukkan bagi siswa atau semua
        if ($role === 'peserta_didik') {
            $query->where('is_active', true)
                  ->where(function ($q) {
                      $q->whereIn('target_peran', ['semua', 'peserta_didik', 'publik']);
                  });
        } elseif (!$canManage) {
            // Guru atau tendik biasa
            $query->where('is_active', true)
                  ->where(function ($q) use ($role) {
                      $q->whereIn('target_peran', ['semua', $role, 'publik']);
                  });
        }

        // Filter pencarian judul
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'LIKE', "%{$search}%")
                  ->orWhere('deskripsi', 'LIKE', "%{$search}%");
            });
        }

        // Filter status
        if ($request->filled('status')) {
            $status = $request->input('status');
            $now = Carbon::now();
            if ($status === 'active') {
                $query->where('is_active', true)
                      ->where(fn($q) => $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $now));
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($status === 'expired') {
                $query->whereNotNull('tanggal_selesai')->where('tanggal_selesai', '<', $now);
            }
        }

        $formulirs = $query->paginate(12)->withQueryString();

        // Map status pengisian untuk peserta didik / pengguna login
        $respondedFormIds = [];
        if ($user) {
            $userPdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
            $userId = is_array($user) ? ($user['pengguna_id'] ?? null) : ($user->pengguna_id ?? null);

            $respondedFormIds = FormulirRespon::where(function ($q) use ($userId, $userPdId) {
                if ($userId) $q->where('pengguna_id', $userId);
                if ($userPdId) $q->orWhere('peserta_didik_id', $userPdId);
            })->pluck('formulir_id')->unique()->toArray();
        }

        return view('dashboard.formulir.index', compact('formulirs', 'role', 'canManage', 'respondedFormIds'));
    }

    /**
     * Form pembuatan formulir baru (Form Builder visual)
     */
    public function create()
    {
        if (!$this->canManage()) {
            return redirect()->route('dashboard.formulir.index')->with('error', 'Anda tidak memiliki hak akses untuk membuat formulir baru.');
        }

        $rombels = [];
        if (Schema::hasTable('rombongan_belajar')) {
            $rombels = DB::table('rombongan_belajar')
                ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id')
                ->orderBy('nama')
                ->get();
        }

        return view('dashboard.formulir.create', compact('rombels'));
    }

    /**
     * Simpan formulir baru
     */
    public function store(Request $request)
    {
        if (!$this->canManage()) {
            return redirect()->route('dashboard.formulir.index')->with('error', 'Akses ditolak.');
        }

        $request->validate([
            'judul' => 'required|string|max:255',
            'slug' => 'nullable|string|max:150|alpha_dash',
            'deskripsi' => 'nullable|string',
            'target_peran' => 'required|string|in:semua,peserta_didik,guru,tendik,publik',
            'skema_json' => 'required|string',
        ], [
            'judul.required' => 'Judul formulir wajib diisi.',
            'skema_json.required' => 'Daftar pertanyaan belum disusun.',
        ]);

        $slugInput = $request->input('slug');
        if (empty($slugInput)) {
            $slug = Str::slug($request->input('judul'));
        } else {
            $slug = Str::slug($slugInput);
        }

        // Pastikan slug unik
        $baseSlug = $slug;
        $counter = 1;
        while (Formulir::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $skema = json_decode($request->input('skema_json'), true);
        if (!is_array($skema) || empty($skema)) {
            return back()->withInput()->with('error', 'Format skema formulir tidak valid atau belum memiliki pertanyaan.');
        }

        $user = $this->getCurrentUser();
        $userName = is_array($user) ? ($user['nama'] ?? 'Admin') : ($user->nama ?? 'Admin');
        $userId = is_array($user) ? ($user['pengguna_id'] ?? null) : ($user->pengguna_id ?? null);

        $pengaturan = [
            'theme_color' => $request->input('theme_color', '#0284c7'),
            'button_text' => $request->input('button_text', 'Kirim Formulir'),
            'success_title' => $request->input('success_title', 'Terima Kasih! Tanggapan Anda Telah Disimpan.'),
            'success_message' => $request->input('success_message', 'Tanggapan Anda telah berhasil terekam dalam sistem SAE.'),
            'redirect_url' => $request->input('redirect_url'),
            'allow_edit_response' => false,
        ];

        $targetPeran = $request->input('target_peran', 'semua');
        $isPublic = ($targetPeran === 'publik') || $request->has('is_public');
        $authRequired = ($targetPeran !== 'publik');

        $formulir = Formulir::create([
            'judul' => $request->input('judul'),
            'slug' => $slug,
            'deskripsi' => $request->input('deskripsi'),
            'is_active' => $request->has('is_active'),
            'is_public' => $isPublic,
            'auth_required' => $authRequired,
            'target_peran' => $targetPeran,
            'target_rombel_id' => $request->input('target_rombel_id') ?: null,
            'target_tingkat' => $request->input('target_tingkat') ?: null,
            'limit_one_response' => $request->has('limit_one_response'),
            'tanggal_mulai' => $request->input('tanggal_mulai') ? Carbon::parse($request->input('tanggal_mulai')) : null,
            'tanggal_selesai' => $request->input('tanggal_selesai') ? Carbon::parse($request->input('tanggal_selesai')) : null,
            'skema' => $skema,
            'pengaturan' => $pengaturan,
            'created_by' => $userName,
            'pengguna_id' => $userId,
        ]);

        return redirect()->route('dashboard.formulir.index')->with('success', 'Formulir "' . $formulir->judul . '" berhasil dibuat!');
    }

    /**
     * Form edit formulir
     */
    public function edit($id)
    {
        if (!$this->canManage()) {
            return redirect()->route('dashboard.formulir.index')->with('error', 'Akses ditolak.');
        }

        $formulir = Formulir::findOrFail($id);

        $rombels = [];
        if (Schema::hasTable('rombongan_belajar')) {
            $rombels = DB::table('rombongan_belajar')
                ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id')
                ->orderBy('nama')
                ->get();
        }

        return view('dashboard.formulir.edit', compact('formulir', 'rombels'));
    }

    /**
     * Update formulir
     */
    public function update(Request $request, $id)
    {
        if (!$this->canManage()) {
            return redirect()->route('dashboard.formulir.index')->with('error', 'Akses ditolak.');
        }

        $formulir = Formulir::findOrFail($id);

        $request->validate([
            'judul' => 'required|string|max:255',
            'slug' => 'nullable|string|max:150|alpha_dash',
            'deskripsi' => 'nullable|string',
            'target_peran' => 'required|string|in:semua,peserta_didik,guru,tendik,publik',
            'skema_json' => 'required|string',
        ]);

        $slugInput = $request->input('slug');
        if (empty($slugInput)) {
            $slug = Str::slug($request->input('judul'));
        } else {
            $slug = Str::slug($slugInput);
        }

        // Cek keunikan slug kecuali milik formulir ini
        $baseSlug = $slug;
        $counter = 1;
        while (Formulir::where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $skema = json_decode($request->input('skema_json'), true);
        if (!is_array($skema) || empty($skema)) {
            return back()->withInput()->with('error', 'Format skema formulir tidak valid atau belum memiliki pertanyaan.');
        }

        $pengaturan = [
            'theme_color' => $request->input('theme_color', '#0284c7'),
            'button_text' => $request->input('button_text', 'Kirim Formulir'),
            'success_title' => $request->input('success_title', 'Terima Kasih! Tanggapan Anda Telah Disimpan.'),
            'success_message' => $request->input('success_message', 'Tanggapan Anda telah berhasil terekam dalam sistem SAE.'),
            'redirect_url' => $request->input('redirect_url'),
            'allow_edit_response' => false,
        ];

        $targetPeran = $request->input('target_peran', 'semua');
        $isPublic = ($targetPeran === 'publik') || $request->has('is_public');
        $authRequired = ($targetPeran !== 'publik');

        $formulir->update([
            'judul' => $request->input('judul'),
            'slug' => $slug,
            'deskripsi' => $request->input('deskripsi'),
            'is_active' => $request->has('is_active'),
            'is_public' => $isPublic,
            'auth_required' => $authRequired,
            'target_peran' => $targetPeran,
            'target_rombel_id' => $request->input('target_rombel_id') ?: null,
            'target_tingkat' => $request->input('target_tingkat') ?: null,
            'limit_one_response' => $request->has('limit_one_response'),
            'tanggal_mulai' => $request->input('tanggal_mulai') ? Carbon::parse($request->input('tanggal_mulai')) : null,
            'tanggal_selesai' => $request->input('tanggal_selesai') ? Carbon::parse($request->input('tanggal_selesai')) : null,
            'skema' => $skema,
            'pengaturan' => $pengaturan,
        ]);

        return redirect()->route('dashboard.formulir.index')->with('success', 'Formulir "' . $formulir->judul . '" berhasil diperbarui!');
    }

    /**
     * Hapus formulir beserta seluruh responnya
     */
    public function destroy($id)
    {
        if (!$this->canManage()) {
            return redirect()->route('dashboard.formulir.index')->with('error', 'Akses ditolak.');
        }

        $formulir = Formulir::findOrFail($id);
        $judul = $formulir->judul;
        $formulir->delete();

        return redirect()->route('dashboard.formulir.index')->with('success', 'Formulir "' . $judul . '" berhasil dihapus.');
    }

    /**
     * Duplikasi formulir
     */
    public function duplicate($id)
    {
        if (!$this->canManage()) {
            return redirect()->route('dashboard.formulir.index')->with('error', 'Akses ditolak.');
        }

        $original = Formulir::findOrFail($id);
        $user = $this->getCurrentUser();
        $userName = is_array($user) ? ($user['nama'] ?? 'Admin') : ($user->nama ?? 'Admin');
        $userId = is_array($user) ? ($user['pengguna_id'] ?? null) : ($user->pengguna_id ?? null);

        $newSlug = Str::slug($original->judul . '-salinan-' . time());

        $cloned = $original->replicate(['slug', 'created_at', 'updated_at']);
        $cloned->judul = $original->judul . ' (Salinan)';
        $cloned->slug = $newSlug;
        $cloned->created_by = $userName;
        $cloned->pengguna_id = $userId;
        $cloned->save();

        return redirect()->route('dashboard.formulir.index')->with('success', 'Formulir berhasil diduplikasi.');
    }

    /**
     * Toggle status aktif formulir via AJAX
     */
    public function toggle($id): JsonResponse
    {
        if (!$this->canManage()) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $formulir = Formulir::findOrFail($id);
        $formulir->is_active = !$formulir->is_active;
        $formulir->save();

        return response()->json([
            'status' => 'success',
            'is_active' => $formulir->is_active,
            'message' => $formulir->is_active ? 'Formulir telah diaktifkan.' : 'Formulir dinonaktifkan.',
        ]);
    }

    /**
     * Tampilkan rekap data respon dan analitik formulir
     */
    public function responses(Request $request, $id)
    {
        if (!$this->canManage()) {
            return redirect()->route('dashboard.formulir.index')->with('error', 'Akses ditolak.');
        }

        $formulir = Formulir::withCount('respon')->findOrFail($id);

        $sort    = $request->input('sort', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['nama_responden', 'identitas_responden', 'created_at'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        // Ganti ->latest() di query builder dengan sort dinamis
        $query = $formulir->respon()->orderBy($sort, $sortDir);

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('nama_responden', 'LIKE', "%{$search}%")
                  ->orWhere('identitas_responden', 'LIKE', "%{$search}%")
                  ->orWhere('jawaban', 'LIKE', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('perPage', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100])) {
            $perPage = 15;
        }

        $responses = $query->paginate($perPage)->withQueryString();

        // Hitung ringkasan statistik untuk field bertipe choice/rating/select
        $fieldStats = [];
        $skema = $formulir->skema ?? [];
        $allAnswers = $formulir->respon()->pluck('jawaban')->all();

        foreach ($skema as $field) {
            $type = $field['type'] ?? 'text';
            $fieldId = $field['id'] ?? '';
            if (in_array($type, ['select', 'radio', 'checkbox', 'rating']) && !empty($field['options'])) {
                $counts = [];
                foreach ($field['options'] as $opt) {
                    $counts[$opt] = 0;
                }

                foreach ($allAnswers as $ans) {
                    if (!is_array($ans) || !isset($ans[$fieldId])) continue;
                    $val = $ans[$fieldId];
                    if (is_array($val)) {
                        foreach ($val as $v) {
                            if (isset($counts[$v])) $counts[$v]++;
                        }
                    } else {
                        if (isset($counts[$val])) $counts[$val]++;
                    }
                }

                $fieldStats[$fieldId] = [
                    'label' => $field['label'] ?? $fieldId,
                    'type' => $type,
                    'counts' => $counts,
                    'total' => array_sum($counts),
                ];
            }
        }

        return view('dashboard.formulir.responses', compact('formulir', 'responses', 'fieldStats', 'perPage', 'sort', 'sortDir'));
    }

    /**
     * Hapus satu respon formulir
     */
    public function deleteResponse($id, $responId)
    {
        if (!$this->canManage()) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $respon = FormulirRespon::where('formulir_id', $id)->findOrFail($responId);
        $respon->delete();

        return redirect()->back()->with('success', 'Tanggapan berhasil dihapus.');
    }

    /**
     * Export seluruh data respon ke format CSV (Excel Ready with UTF-8 BOM)
     */
    public function exportCsv($id)
    {
        if (!$this->canManage()) {
            abort(403, 'Akses ditolak.');
        }

        $formulir = Formulir::findOrFail($id);
        $responses = $formulir->respon()->oldest()->get();
        $skema = $formulir->skema ?? [];

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Respon_' . Str::slug($formulir->judul) . '_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($responses, $skema) {
            $handle = fopen('php://output', 'w');

            // Tambahkan UTF-8 BOM agar Microsoft Excel membuka aksen & karakter secara sempurna
            fputs($handle, "\xEF\xBB\xBF");

            // Baris Header Kolom
            $headerRow = [
                'No',
                'Waktu Submit',
                'Nama Responden',
                'Identitas (NISN/Rombel)',
                'IP Address',
            ];

            foreach ($skema as $field) {
                $headerRow[] = $field['label'] ?? $field['id'];
            }

            fputcsv($handle, $headerRow);

            // Baris Data Responden
            $no = 1;
            foreach ($responses as $row) {
                $jawaban = is_array($row->jawaban) ? $row->jawaban : [];

                $dataRow = [
                    $no++,
                    $row->created_at ? $row->created_at->format('d/m/Y H:i:s') : '-',
                    $row->nama_responden ?? '-',
                    $row->identitas_responden ?? '-',
                    $row->ip_address ?? '-',
                ];

                foreach ($skema as $field) {
                    $fieldId = $field['id'] ?? '';
                    $val = $jawaban[$fieldId] ?? '';

                    if (is_array($val)) {
                        $dataRow[] = implode(', ', $val);
                    } else {
                        $dataRow[] = (string) $val;
                    }
                }

                fputcsv($handle, $dataRow);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Tampilan Publik Pengisian Formulir (/f/{slug})
     */
    public function showPublic(Request $request, $slug)
    {
        $formulir = Formulir::where('slug', $slug)->firstOrFail();

        // 1. Cek apakah formulir aktif
        if (!$formulir->is_active) {
            return view('formulir.status-closed', [
                'formulir' => $formulir,
                'status' => 'inactive',
                'title' => 'Formulir Sedang Dinonaktifkan',
                'message' => 'Mohon maaf, formulir ini sedang tidak menerima tanggapan baru.',
            ]);
        }

        // 2. Cek jadwal ketersediaan waktu
        $now = Carbon::now();
        if ($formulir->tanggal_mulai && $now->lt($formulir->tanggal_mulai)) {
            return view('formulir.status-closed', [
                'formulir' => $formulir,
                'status' => 'not_started',
                'title' => 'Formulir Belum Dibuka',
                'message' => 'Formulir ini dijadwalkan dibuka pada: ' . $formulir->tanggal_mulai->translatedFormat('d F Y, H:i') . ' WIB.',
            ]);
        }

        if ($formulir->tanggal_selesai && $now->gt($formulir->tanggal_selesai)) {
            return view('formulir.status-closed', [
                'formulir' => $formulir,
                'status' => 'expired',
                'title' => 'Formulir Telah Ditutup',
                'message' => 'Batas waktu pengisian formulir ini telah berakhir pada: ' . $formulir->tanggal_selesai->translatedFormat('d F Y, H:i') . ' WIB.',
            ]);
        }

        // Simpan URL intended agar jika user login/pindah halaman selalu kembali ke form ini
        session(['url.intended' => url()->current()]);

        // 3. Cek Autentikasi Pengguna & Kesesuaian Target
        $user = $this->getCurrentUser();
        $requiresLogin = ($formulir->target_peran !== 'publik') || $formulir->auth_required;

        if ($requiresLogin && !$user) {
            $roleLabels = [
                'peserta_didik' => 'Khusus Peserta Didik',
                'guru' => 'Khusus Guru / Pendidik',
                'tendik' => 'Khusus Tenaga Kependidikan',
                'semua' => 'Semua Pengguna SAE (Login Akun)',
            ];
            $targetLabel = $roleLabels[$formulir->target_peran] ?? 'Pengguna Terdaftar';

            return view('formulir.status-closed', [
                'formulir' => $formulir,
                'status' => 'auth_required',
                'title' => 'Login Akun SAE Diperlukan',
                'targetLabel' => $targetLabel,
                'message' => 'Formulir "' . $formulir->judul . '" hanya dapat diisi oleh ' . $targetLabel . '. Silakan masuk dengan akun SAE Anda untuk melanjutkan.',
                'loginUrl' => route('login'),
                'sekolah' => Schema::hasTable('sekolah') ? DB::table('sekolah')->first() : null,
                'sekolahMeta' => Schema::hasTable('sekolah_meta') ? SekolahMeta::first() : null,
            ]);
        }

        // 4. Cek Hak Akses Target Peran & Rombel (jika login)
        $studentInfo = null;
        if ($user) {
            $userRole = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
            $userPdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);

            // Validasi Target Peran
            if ($formulir->target_peran !== 'semua' && $formulir->target_peran !== 'publik') {
                if ($userRole !== $formulir->target_peran && $userRole !== 'admin') {
                    return view('formulir.status-closed', [
                        'formulir' => $formulir,
                        'status' => 'forbidden_role',
                        'title' => 'Akses Terbatas',
                        'message' => 'Formulir ini khusus diperuntukkan bagi pengguna dengan peran: ' . ucfirst(str_replace('_', ' ', $formulir->target_peran)) . '.',
                    ]);
                }
            }

            // Jika peserta didik, ambil data master sekolah (rombel, nisn)
            if ($userPdId && Schema::hasTable('peserta_didik')) {
                $studentInfo = DB::table('peserta_didik')->where('peserta_didik_id', $userPdId)->first();

                // Validasi Target Rombel jika ditentukan
                if ($formulir->target_rombel_id && $studentInfo) {
                    if ($studentInfo->rombongan_belajar_id !== $formulir->target_rombel_id && $userRole !== 'admin') {
                        $rombelTarget = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $formulir->target_rombel_id)->first();
                        return view('formulir.status-closed', [
                            'formulir' => $formulir,
                            'status' => 'forbidden_rombel',
                            'title' => 'Khusus Kelas Tertentu',
                            'message' => 'Formulir ini khusus untuk peserta didik rombel: ' . ($rombelTarget->nama ?? 'Kelas Terpilih') . '.',
                        ]);
                    }
                }
            }
        }

        // 5. Cek Pembatasan 1 Tanggapan
        if ($formulir->limit_one_response && $formulir->hasUserResponded($user, $request->ip())) {
            $lastRespon = $formulir->respon()
                ->where(function ($q) use ($user, $request) {
                    if ($user) {
                        $userId = is_array($user) ? ($user['pengguna_id'] ?? null) : ($user->pengguna_id ?? null);
                        $userPdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
                        if ($userId) $q->where('pengguna_id', $userId);
                        if ($userPdId) $q->orWhere('peserta_didik_id', $userPdId);
                    } else {
                        $q->where('ip_address', $request->ip());
                    }
                })->latest()->first();

            return view('formulir.status-closed', [
                'formulir' => $formulir,
                'status' => 'already_submitted',
                'title' => 'Anda Sudah Mengisi Formulir Ini',
                'message' => 'Terima kasih, tanggapan Anda telah tercatat pada ' . ($lastRespon && $lastRespon->created_at ? $lastRespon->created_at->translatedFormat('d F Y, H:i') : 'sebelumnya') . ' WIB.',
                'respon' => $lastRespon,
            ]);
        }

        // Info branding sekolah
        $sekolah = Schema::hasTable('sekolah') ? DB::table('sekolah')->first() : null;
        $sekolahMeta = Schema::hasTable('sekolah_meta') ? SekolahMeta::first() : null;

        return view('formulir.public-view', compact('formulir', 'user', 'studentInfo', 'sekolah', 'sekolahMeta'));
    }

    /**
     * Terima dan simpan tanggapan formulir
     */
    public function submitPublic(Request $request, $slug)
    {
        $formulir = Formulir::where('slug', $slug)->firstOrFail();

        // Validasi ketersediaan form
        if (!$formulir->isScheduleOpen()) {
            return back()->with('error', 'Formulir tidak sedang menerima tanggapan.');
        }

        $user = $this->getCurrentUser();
        $requiresLogin = ($formulir->target_peran !== 'publik') || $formulir->auth_required;
        if ($requiresLogin && !$user) {
            session(['url.intended' => route('formulir.public', $slug)]);
            return redirect()->route('login')->with('error', 'Formulir ini memerlukan login akun SAE. Silakan masuk terlebih dahulu.');
        }

        // Validasi batas 1 tanggapan
        if ($formulir->limit_one_response && $formulir->hasUserResponded($user, $request->ip())) {
            return redirect()->route('formulir.public', $slug)->with('error', 'Anda sudah mengisi formulir ini sebelumnya.');
        }

        $skema = $formulir->skema ?? [];
        $jawaban = [];
        $errors = [];

        $studentInfo = null;
        if ($user) {
            $userPdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
            if ($userPdId && Schema::hasTable('peserta_didik')) {
                $studentInfo = DB::table('peserta_didik')->where('peserta_didik_id', $userPdId)->first();
            }
        }

        $guestName = null;
        $guestNisn = null;
        $guestRombel = null;

        // Validasi & ekstrak tiap field berdasarkan skema
        foreach ($skema as $field) {
            $fieldId = $field['id'] ?? '';
            $type = $field['type'] ?? 'text';
            $label = $field['label'] ?? $fieldId;
            $required = !empty($field['required']);

            // Field identitas SAE: ambil dari profil jika login, atau dari input jika tamu/publik
            if ($type === 'sae_nama') {
                $val = $studentInfo ? $studentInfo->nama : (is_array($user) ? ($user['nama'] ?? '') : ($request->input($fieldId) ?? ''));
                $val = is_string($val) ? trim($val) : '';
                if ($required && empty($val)) {
                    $errors[$fieldId] = "Kolom \"{$label}\" wajib diisi.";
                }
                $jawaban[$fieldId] = $val;
                if (!empty($val)) $guestName = $val;
                continue;
            } elseif ($type === 'sae_nisn') {
                $val = $studentInfo ? $studentInfo->nisn : (is_array($user) ? ($user['username'] ?? '') : ($request->input($fieldId) ?? ''));
                $val = is_string($val) ? trim($val) : '';
                if ($required && empty($val)) {
                    $errors[$fieldId] = "Kolom \"{$label}\" wajib diisi.";
                }
                $jawaban[$fieldId] = $val;
                if (!empty($val)) $guestNisn = $val;
                continue;
            } elseif ($type === 'sae_rombel') {
                $val = $studentInfo ? $studentInfo->nama_rombel : (is_array($user) ? ($user['kelas'] ?? '') : ($request->input($fieldId) ?? ''));
                $val = is_string($val) ? trim($val) : '';
                if ($required && empty($val)) {
                    $errors[$fieldId] = "Kolom \"{$label}\" wajib diisi.";
                }
                $jawaban[$fieldId] = $val;
                if (!empty($val)) $guestRombel = $val;
                continue;
            }

            // Penanganan file upload
            if ($type === 'file') {
                if ($request->hasFile($fieldId)) {
                    $file = $request->file($fieldId);
                    if ($file->isValid()) {
                        // Batasi ukuran maksimal 5MB
                        if ($file->getSize() > 5 * 1024 * 1024) {
                            $errors[$fieldId] = "Berkas pada \"{$label}\" melebihi batas maksimal 5 MB.";
                            continue;
                        }

                        $ext = strtolower($file->getClientOriginalExtension());
                        $allowedExts = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
                        if (!in_array($ext, $allowedExts)) {
                            $errors[$fieldId] = "Format berkas \"{$label}\" tidak didukung (Gunakan JPG, PNG, PDF, DOCX, XLSX).";
                            continue;
                        }

                        $filename = 'form_' . $formulir->id . '_' . Str::random(12) . '.' . $ext;
                        $path = $file->storeAs('formulir_uploads', $filename, 'public');
                        $jawaban[$fieldId] = asset('storage/' . $path);
                    }
                } elseif ($required) {
                    $errors[$fieldId] = "Berkas pada \"{$label}\" wajib diunggah.";
                }
                continue;
            }

            // Field teks, pilihan, rating, textarea, tanggal, waktu
            $value = $request->input($fieldId);

            if ($required && ($value === null || $value === '' || (is_array($value) && empty($value)))) {
                $errors[$fieldId] = "Pertanyaan \"{$label}\" wajib diisi.";
                continue;
            }

            $jawaban[$fieldId] = $value;
        }

        if (!empty($errors)) {
            return back()->withInput()->withErrors($errors)->with('error', 'Mohon lengkapi semua kolom wajib dengan benar.');
        }

        // Identifikasi responden
        $userName = $studentInfo ? $studentInfo->nama : (is_array($user) ? ($user['nama'] ?? 'Pengguna SAE') : ($guestName ?: ($request->input('nama_tamu') ?: 'Responden Publik')));
        
        $userIdentitas = $studentInfo 
            ? ($studentInfo->nisn . ' • ' . $studentInfo->nama_rombel) 
            : (is_array($user) ? ($user['username'] ?? '-') : trim(($guestNisn ?? '') . ($guestRombel ? ' (' . $guestRombel . ')' : '')));
        if (empty($userIdentitas)) {
            $userIdentitas = 'Publik';
        }

        $userId = is_array($user) ? ($user['pengguna_id'] ?? null) : ($user->pengguna_id ?? null);
        $pdId = $studentInfo ? $studentInfo->peserta_didik_id : null;
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

        $respon = FormulirRespon::create([
            'formulir_id' => $formulir->id,
            'pengguna_id' => $userId,
            'peserta_didik_id' => $pdId,
            'ptk_id' => $ptkId,
            'nama_responden' => $userName,
            'identitas_responden' => $userIdentitas,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'jawaban' => $jawaban,
        ]);

        return redirect()->route('formulir.success', ['slug' => $slug, 'respon' => $respon->id]);
    }

    /**
     * Halaman Sukses Pengisian Formulir
     */
    public function successPublic($slug, $responId)
    {
        $formulir = Formulir::where('slug', $slug)->firstOrFail();
        $respon = FormulirRespon::where('formulir_id', $formulir->id)->findOrFail($responId);

        $sekolah = Schema::hasTable('sekolah') ? DB::table('sekolah')->first() : null;
        $sekolahMeta = Schema::hasTable('sekolah_meta') ? SekolahMeta::first() : null;

        return view('formulir.success', compact('formulir', 'respon', 'sekolah', 'sekolahMeta'));
    }
}
