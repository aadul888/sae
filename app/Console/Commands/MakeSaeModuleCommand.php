<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\RolePermission;

class MakeSaeModuleCommand extends Command
{
    /**
     * Nama dan signature perintah CLI.
     *
     * @var string
     */
    protected $signature = 'sae:make-module 
                            {name : Nama modul dalam PascalCase (contoh: Perpustakaan, Sarpras, Keuangan)}
                            {--title= : Judul lengkap modul (contoh: Perpustakaan Digital)}
                            {--group=Layanan Digital : Kelompok menu sidebar (Menu Utama, Master Data, Manajemen Data, Layanan Digital, Administrasi Guru, Administrasi Tendik, Portal Peserta Didik, Sistem & Pengaturan)}
                            {--icon=fa-cube : Ikon FontAwesome (contoh: fa-book, fa-wallet, fa-boxes-stacked)}
                            {--force : Timpa berkas jika sudah ada}';

    /**
     * Deskripsi perintah CLI.
     *
     * @var string
     */
    protected $description = 'Generate kerangka modul SAE terstandar (Controller, Blade View, Modular JS, Route, & Hak Akses RBAC)';

    /**
     * Eksekusi perintah console.
     */
    public function handle(): int
    {
        $rawName = trim($this->argument('name'));
        if (empty($rawName)) {
            $this->error('Nama modul tidak boleh kosong.');
            return Command::FAILURE;
        }

        $studlyName = Str::studly(str_replace([' ', '_', '-'], '', $rawName));
        $slug = Str::kebab($studlyName);
        $snake = Str::snake($studlyName);
        $title = $this->option('title') ?: Str::headline($slug);
        $group = $this->option('group') ?: 'Layanan Digital';
        $icon = $this->option('icon') ?: 'fa-cube';
        $force = (bool) $this->option('force');

        $controllerName = $studlyName . 'Controller';
        $permissionKey = 'menu_' . $snake;

        $this->info("==================================================");
        $this->info("   MEMBUAT MODUL SAE TERSTANDAR: {$title}");
        $this->info("==================================================");
        $this->line("• Controller   : App\\Http\\Controllers\\{$controllerName}");
        $this->line("• Blade View   : resources/views/dashboard/{$slug}.blade.php");
        $this->line("• Modular JS   : public/js/{$slug}.js");
        $this->line("• Permission   : {$permissionKey}");
        $this->line("• Menu Group   : {$group}");
        $this->line("• Icon         : {$icon}");
        $this->newLine();

        // 1. Generate Controller
        $controllerCreated = $this->generateController($controllerName, $slug, $title, $permissionKey, $force);

        // 2. Generate Blade View
        $viewCreated = $this->generateBladeView($slug, $title, $permissionKey, $force);

        // 3. Generate Modular JS
        $jsCreated = $this->generateModularJs($slug, $title, $force);

        // 4. Daftarkan Rute di routes/web.php
        $routeAppended = $this->appendRoutes($controllerName, $slug, $permissionKey);

        // 5. Daftarkan Hak Akses ke Database
        $this->registerPermission($permissionKey, $title, $group, $icon);

        $this->newLine();
        $this->info("✓ Modul '{$title}' berhasil digenerate dengan standar baku SAE!");
        $this->line("Silakan akses di browser: " . url("/dashboard/{$slug}"));

        return Command::SUCCESS;
    }

    /**
     * Generate berkas Controller dengan pengecekan CRUD RBAC terpadu.
     */
    protected function generateController(string $controllerName, string $slug, string $title, string $permissionKey, bool $force): bool
    {
        $path = app_path("Http/Controllers/{$controllerName}.php");

        if (File::exists($path) && !$force) {
            $this->warn("! Controller {$controllerName} sudah ada. Lewati (gunakan --force untuk menimpa).");
            return false;
        }

        $stub = <<<PHP
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\RolePermission;

class {$controllerName} extends Controller
{
    private function checkAuth()
    {
        \$user = session('user');
        if (!\$user) {
            return redirect()->route('login');
        }
        return null;
    }

    /**
     * Tampilkan halaman utama modul {$title}.
     */
    public function index(Request \$request)
    {
        if (\$res = \$this->checkAuth()) return \$res;

        \$user = session('user');
        \$role = is_array(\$user) ? (\$user['role'] ?? '') : (\$user->role ?? '');

        // Evaluasi granular 4 hak akses CRUD
        \$canCreate = RolePermission::canAccess(\$user ?: \$role, '{$permissionKey}', 'create');
        \$canRead   = RolePermission::canAccess(\$user ?: \$role, '{$permissionKey}', 'read');
        \$canUpdate = RolePermission::canAccess(\$user ?: \$role, '{$permissionKey}', 'update');
        \$canDelete = RolePermission::canAccess(\$user ?: \$role, '{$permissionKey}', 'delete');

        // Statistik dummy (sesuaikan dengan query model Anda)
        \$stats = [
            'total' => 0,
            'aktif' => 0,
            'tertunda' => 0,
            'selesai' => 0,
        ];

        // Daftar data (sesuaikan dengan tabel database modul ini)
        \$items = collect([]);

        return view('dashboard.{$slug}', compact(
            'stats',
            'items',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete'
        ));
    }

    /**
     * Simpan data baru (Create).
     */
    public function store(Request \$request)
    {
        \$user = session('user');
        \$role = is_array(\$user) ? (\$user['role'] ?? '') : (\$user->role ?? '');

        if (!RolePermission::canAccess(\$user ?: \$role, '{$permissionKey}', 'create')) {
            if (\$request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menambah data.'], 403);
            }
            return back()->with('error', 'Akses ditolak: Anda tidak memiliki hak akses untuk menambah data.');
        }

        \$validated = \$request->validate([
            'nama' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        // Logika simpan data ke database di sini

        if (\$request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil disimpan.',
            ]);
        }

        return back()->with('success', 'Data {$title} berhasil ditambahkan.');
    }

    /**
     * Tampilkan detail data (Read / Detail).
     */
    public function show(Request \$request, \$id)
    {
        \$user = session('user');
        \$role = is_array(\$user) ? (\$user['role'] ?? '') : (\$user->role ?? '');

        if (!RolePermission::canAccess(\$user ?: \$role, '{$permissionKey}', 'read')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => \$id,
                'nama' => 'Contoh Data ' . \$id,
            ],
        ]);
    }

    /**
     * Perbarui data yang ada (Update).
     */
    public function update(Request \$request, \$id)
    {
        \$user = session('user');
        \$role = is_array(\$user) ? (\$user['role'] ?? '') : (\$user->role ?? '');

        if (!RolePermission::canAccess(\$user ?: \$role, '{$permissionKey}', 'update')) {
            if (\$request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk mengubah data.'], 403);
            }
            return back()->with('error', 'Akses ditolak: Anda tidak memiliki hak akses untuk mengubah data.');
        }

        \$validated = \$request->validate([
            'nama' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        // Logika update data ke database di sini

        if (\$request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil diperbarui.',
            ]);
        }

        return back()->with('success', 'Data {$title} berhasil diperbarui.');
    }

    /**
     * Hapus data (Delete).
     */
    public function destroy(Request \$request, \$id)
    {
        \$user = session('user');
        \$role = is_array(\$user) ? (\$user['role'] ?? '') : (\$user->role ?? '');

        if (!RolePermission::canAccess(\$user ?: \$role, '{$permissionKey}', 'delete')) {
            if (\$request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menghapus data.'], 403);
            }
            return back()->with('error', 'Akses ditolak: Anda tidak memiliki hak akses untuk menghapus data.');
        }

        // Logika hapus data dari database di sini

        if (\$request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Data {$title} berhasil dihapus.');
    }
}
PHP;

        File::put($path, $stub);
        $this->info("✓ Controller dibuat: {$path}");
        return true;
    }

    /**
     * Generate berkas Blade View dengan format tabel terstandar SAE.
     */
    protected function generateBladeView(string $slug, string $title, string $permissionKey, bool $force): bool
    {
        $path = resource_path("views/dashboard/{$slug}.blade.php");

        if (File::exists($path) && !$force) {
            $this->warn("! Blade View {$slug}.blade.php sudah ada. Lewati.");
            return false;
        }

        $stub = <<<BLADE
@extends('layouts.dashboard')

@section('title', '{$title} — SAE (Sistem Aplikasi Edukasi)')
@section('dash_title', '{$title}')

@section('content')
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                <i class="fas fa-cubes"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0 0 2px 0;">
                    {$title}
                </h2>
                <p style="color: var(--text-muted); font-size: 0.84rem; margin: 0;">
                    Kelola data dan administrasi {$title} secara terpadu.
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if (\$canCreate)
                <button type="button" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.84rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;" id="btnOpenCreateModal">
                    <i class="fas fa-plus"></i> Tambah Data
                </button>
            @endif
        </div>
    </div>

    <!-- 2. Flash Messages -->
    @if (session('success'))
        <div style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- 3. Stat Grid Baku SAE -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                <i class="fas fa-list"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format(\$stats['total'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Data</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format(\$stats['aktif'] ?? 0) }}</div>
                <div class="dash-stat-label">Status Aktif</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format(\$stats['tertunda'] ?? 0) }}</div>
                <div class="dash-stat-label">Dalam Proses</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.12); color: var(--accent);">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format(\$stats['selesai'] ?? 0) }}</div>
                <div class="dash-stat-label">Selesai</div>
            </div>
        </div>
    </div>

    <!-- 4. Main Data Card & Table -->
    <div class="card" style="padding: 20px; border-radius: 14px; border: 1px solid var(--border-color); margin-bottom: 20px;">
        <!-- Toolbar: Pencarian, Filter & Dropdown Baris -->
        <div class="dash-toolbar" style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;">
                <div class="live-search-wrap" style="width: 100%;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearchInput" placeholder="Cari data {$title}..." autocomplete="off">
                    <button type="button" class="clear-search" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <select id="filterStatus" style="height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;">
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>

                <select id="perPageSelect" style="height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;">
                    <option value="10">10 Baris</option>
                    <option value="25" selected>25 Baris</option>
                    <option value="50">50 Baris</option>
                    <option value="all">Semua</option>
                </select>
            </div>
        </div>

        <!-- Tabel Responsif Baku SAE -->
        <div style="overflow-x: auto;">
            <table class="dash-table" style="width: 100%; border-collapse: collapse; font-size: 0.86rem;" id="mainDataTable">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                        <th style="padding: 10px 14px; width: 50px;">No</th>
                        <th style="padding: 10px 14px;">Nama Data</th>
                        <th style="padding: 10px 14px;">Keterangan</th>
                        <th style="padding: 10px 14px; width: 120px;">Status</th>
                        <th style="padding: 10px 14px; width: 140px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableBodyContent">
                    @forelse (\$items as \$index => \$item)
                        <tr class="data-row" style="border-bottom: 1px solid var(--border-color);" data-nama="{{ \$item->nama ?? '' }}">
                            <td style="padding: 10px 14px;">{{ \$index + 1 }}</td>
                            <td style="padding: 10px 14px; font-weight: 600;">{{ \$item->nama ?? '-' }}</td>
                            <td style="padding: 10px 14px; color: var(--text-muted);">{{ \$item->keterangan ?? '-' }}</td>
                            <td style="padding: 10px 14px;">
                                <span class="badge badge-success" style="font-size: 0.72rem; padding: 3px 8px;">Aktif</span>
                            </td>
                            <td style="padding: 10px 14px; text-align: center;">
                                <div style="display: inline-flex; gap: 6px;">
                                    @if (\$canUpdate)
                                        <button type="button" class="btn btn-outline btn-sm btn-edit-row" data-id="{{ \$item->id ?? 0 }}" title="Edit Data" style="padding: 4px 8px; font-size: 0.78rem;">
                                            <i class="fas fa-pen-to-square"></i>
                                        </button>
                                    @endif
                                    @if (\$canDelete)
                                        <button type="button" class="btn btn-outline btn-sm btn-delete-row" data-id="{{ \$item->id ?? 0 }}" data-name="{{ \$item->nama ?? 'Data' }}" title="Hapus Data" style="padding: 4px 8px; font-size: 0.78rem; color: #ef4444; border-color: rgba(239,68,68,0.3);">
                                            <i class="fas fa-trash-can"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyTableRow">
                            <td colspan="5" style="text-align: center; padding: 36px 14px; color: var(--text-muted);">
                                <i class="fas fa-inbox" style="font-size: 2.2rem; margin-bottom: 10px; display: block; opacity: 0.4;"></i>
                                <span>Belum ada data {$title} yang tercatat di sistem.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Footer Pagination -->
        <div id="tablePaginationWrap" class="custom-pagination" style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border-color); font-size: 0.8rem; color: var(--text-muted); flex-wrap: wrap; gap: 8px;">
            <div id="paginationInfo">Menampilkan 0 data</div>
            <div id="paginationButtons" style="display: flex; gap: 4px;"></div>
        </div>
    </div>

    <!-- 5. Modal Form Baku SAE (z-index: 99999 !important) -->
    <div id="modalFormItem" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 500px; width: 92%; margin: 0; border-radius: 14px; padding: 22px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid var(--border-color); background: var(--bg-card, #1e293b);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 id="modalTitle" style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-plus-circle text-primary"></i> Form {$title}
                </h3>
                <button type="button" id="btnCloseModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="mainFormItem">
                @csrf
                <input type="hidden" id="formItemId" name="id">

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                        Nama Data <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" id="inputNama" name="nama" required placeholder="Masukkan nama..."
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 18px;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                        Keterangan
                    </label>
                    <textarea id="inputKeterangan" name="keterangan" rows="3" placeholder="Tambahkan keterangan opsional..."
                        style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box; resize: vertical;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" id="btnCancelModal" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.84rem; border-radius: 8px;">
                        Batal
                    </button>
                    <button type="submit" id="btnSaveModal" class="btn btn-primary" style="padding: 8px 18px; font-size: 0.84rem; border-radius: 8px; font-weight: 600;">
                        <i class="fas fa-check me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/{$slug}.js') }}"></script>
@endpush
BLADE;

        File::put($path, $stub);
        $this->info("✓ Blade View dibuat: {$path}");
        return true;
    }

    /**
     * Generate berkas JavaScript modular terpisah di public/js.
     */
    protected function generateModularJs(string $slug, string $title, bool $force): bool
    {
        $path = public_path("js/{$slug}.js");

        if (File::exists($path) && !$force) {
            $this->warn("! Berkas JS public/js/{$slug}.js sudah ada. Lewati.");
            return false;
        }

        $stub = <<<JS
/**
 * Modul JavaScript: {$title} (SAE Standardized)
 * Menggunakan SweetAlert2 dan event delegation terpisah dari Blade.
 */
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Modal elements
    const modalForm = document.getElementById('modalFormItem');
    const btnOpenCreate = document.getElementById('btnOpenCreateModal');
    const btnCloseModal = document.getElementById('btnCloseModal');
    const btnCancelModal = document.getElementById('btnCancelModal');
    const mainForm = document.getElementById('mainFormItem');
    const modalTitle = document.getElementById('modalTitle');
    const formItemId = document.getElementById('formItemId');
    const inputNama = document.getElementById('inputNama');
    const inputKeterangan = document.getElementById('inputKeterangan');

    // Filter & Search elements
    const liveSearchInput = document.getElementById('liveSearchInput');
    const filterStatus = document.getElementById('filterStatus');
    const perPageSelect = document.getElementById('perPageSelect');
    const allRows = Array.from(document.querySelectorAll('.data-row'));
    const paginationInfo = document.getElementById('paginationInfo');

    let currentPage = 1;

    // Toast helper
    const showToast = (message, type = 'success') => {
        if (window.SAE && typeof window.SAE.toast === 'function') {
            window.SAE.toast(message, type);
        } else if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type === 'danger' ? 'error' : type,
                title: message,
                showConfirmButton: false,
                timer: 3000
            });
        }
    };

    // Open Modal Create
    if (btnOpenCreate && modalForm) {
        btnOpenCreate.addEventListener('click', () => {
            if (mainForm) mainForm.reset();
            if (formItemId) formItemId.value = '';
            if (modalTitle) modalTitle.innerHTML = '<i class="fas fa-plus-circle text-primary"></i> Tambah {$title}';
            modalForm.style.display = 'flex';
            inputNama?.focus();
        });
    }

    // Close Modal
    const closeModal = () => {
        if (modalForm) modalForm.style.display = 'none';
    };

    if (btnCloseModal) btnCloseModal.addEventListener('click', closeModal);
    if (btnCancelModal) btnCancelModal.addEventListener('click', closeModal);
    if (modalForm) {
        modalForm.addEventListener('click', (e) => {
            if (e.target === modalForm) closeModal();
        });
    }

    // Client-side Filter & Search Table
    const renderTable = () => {
        const query = (liveSearchInput?.value || '').trim().toLowerCase();
        const perPageVal = perPageSelect?.value || '25';
        const perPage = perPageVal === 'all' ? Infinity : parseInt(perPageVal, 10);

        let filtered = allRows.filter(row => {
            const nama = (row.dataset.nama || '').toLowerCase();
            return !query || nama.includes(query);
        });

        const totalFiltered = filtered.length;
        const totalPages = Math.ceil(totalFiltered / perPage) || 1;
        if (currentPage > totalPages) currentPage = totalPages;

        allRows.forEach(r => r.style.display = 'none');

        const startIndex = (currentPage - 1) * perPage;
        const endIndex = startIndex + perPage;
        const pageRows = filtered.slice(startIndex, endIndex);

        pageRows.forEach(r => r.style.display = '');

        if (paginationInfo) {
            paginationInfo.textContent = `Menampilkan \${pageRows.length} dari \${totalFiltered} data`;
        }
    };

    if (liveSearchInput) liveSearchInput.addEventListener('input', () => { currentPage = 1; renderTable(); });
    if (filterStatus) filterStatus.addEventListener('change', () => { currentPage = 1; renderTable(); });
    if (perPageSelect) perPageSelect.addEventListener('change', () => { currentPage = 1; renderTable(); });

    renderTable();

    // Event Delegation: Hapus Data dengan SweetAlert2
    document.addEventListener('click', async (e) => {
        const deleteBtn = e.target.closest('.btn-delete-row');
        if (!deleteBtn) return;

        const id = deleteBtn.dataset.id;
        const name = deleteBtn.dataset.name || 'data ini';

        let confirmed = false;
        if (window.SAE && typeof window.SAE.confirm === 'function') {
            confirmed = await window.SAE.confirm(`Yakin ingin menghapus "\${name}"? Tindakan ini tidak dapat dibatalkan.`, 'Hapus Data?', 'warning');
        } else if (typeof Swal !== 'undefined') {
            const result = await Swal.fire({
                title: 'Hapus Data?',
                text: `Yakin ingin menghapus "\${name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            });
            confirmed = result.isConfirmed;
        }

        if (confirmed) {
            try {
                const res = await fetch(`/dashboard/{$slug}/\${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message || 'Data berhasil dihapus.');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    showToast(data.message || 'Gagal menghapus data.', 'danger');
                }
            } catch (err) {
                showToast('Terjadi kesalahan koneksi server.', 'danger');
            }
        }
    });
});
JS;

        File::put($path, $stub);
        $this->info("✓ Modular JS dibuat: {$path}");
        return true;
    }

    /**
     * Daftarkan grup rute berstandar RBAC ke routes/web.php.
     */
    protected function appendRoutes(string $controllerName, string $slug, string $permissionKey): bool
    {
        $routesPath = base_path('routes/web.php');
        if (!File::exists($routesPath)) return false;

        $content = File::get($routesPath);

        // Cek jika rute sudah terdaftar
        if (str_contains($content, "Route::get('/{$slug}'") || str_contains($content, "{$controllerName}::class")) {
            $this->warn("! Rute untuk {$slug} tampaknya sudah ada di routes/web.php. Lewati penambahan rute.");
            return false;
        }

        $routeBlock = <<<ROUTE

    // Modul {$slug} (Auto-generated by sae:make-module)
    Route::get('/{$slug}', [\App\Http\Controllers\\{$controllerName}::class, 'index'])->name('{$slug}.index')->middleware('permission:{$permissionKey},read');
    Route::post('/{$slug}', [\App\Http\Controllers\\{$controllerName}::class, 'store'])->name('{$slug}.store')->middleware('permission:{$permissionKey},create');
    Route::get('/{$slug}/{id}', [\App\Http\Controllers\\{$controllerName}::class, 'show'])->name('{$slug}.show')->middleware('permission:{$permissionKey},read');
    Route::put('/{$slug}/{id}', [\App\Http\Controllers\\{$controllerName}::class, 'update'])->name('{$slug}.update')->middleware('permission:{$permissionKey},update');
    Route::delete('/{$slug}/{id}', [\App\Http\Controllers\\{$controllerName}::class, 'destroy'])->name('{$slug}.destroy')->middleware('permission:{$permissionKey},delete');
ROUTE;

        // Sisipkan sebelum penutup Route::prefix('dashboard')
        $target = "Route::prefix('dashboard')->name('dashboard.')->group(function () {";
        if (str_contains($content, $target)) {
            $replacement = $target . "\n" . $routeBlock;
            $updated = str_replace($target, $replacement, $content);
            File::put($routesPath, $updated);
            $this->info("✓ Rute terproteksi RBAC ditambahkan ke routes/web.php");
            return true;
        }

        // Fallback jika tidak menemukan marker spesifik
        File::append($routesPath, "\n" . $routeBlock);
        $this->info("✓ Rute ditambahkan di akhir routes/web.php");
        return true;
    }

    /**
     * Daftarkan permission modul baru ke database role_permissions untuk Administrator.
     */
    protected function registerPermission(string $permissionKey, string $title, string $group, string $icon): void
    {
        try {
            RolePermission::updateOrCreate(
                ['role' => 'admin', 'permission_key' => $permissionKey],
                [
                    'is_allowed' => true,
                    'can_create' => true,
                    'can_read' => true,
                    'can_update' => true,
                    'can_delete' => true,
                    'updated_at' => now(),
                ]
            );
            $this->info("✓ Permission '{$permissionKey}' berhasil didaftarkan untuk Administrator.");
        } catch (\Throwable $e) {
            $this->warn("! Gagal mendaftarkan ke database role_permissions: " . $e->getMessage());
        }
    }
}
