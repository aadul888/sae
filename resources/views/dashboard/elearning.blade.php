@extends('layouts.dashboard')

@section('title', 'E-Learning — SAE (Sistem Aplikasi Edukasi)')
@section('dash_title', 'E-Learning')

@section('content')
    {{-- Tab aktif + tab-param agar link bertab konsisten --}}
    @php
        $tabRoute = fn(string $tab) => route('dashboard.elearning.index', array_filter([
            'tab' => $tab,
            'pembelajaran_id' => $pembelajaranId ?: null,
        ]));
    @endphp

    {{-- 1. Header Banner --}}
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: var(--space-6);">
            <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(99, 102, 241, 0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl); flex-shrink: 0;">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div>
                <h2 style="font-size: var(--text-xl); font-weight: 800; color: var(--text-main); margin: 0 0 2px 0;">
                    E-Learning
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-sm); margin: 0;">
                    Materi, tugas, pengumpulan &amp; penilaian per mata pelajaran &amp; rombel. Video memakai tautan/embed — server tidak menyimpan video.
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: var(--space-4); align-items: center; flex-wrap: wrap;">
            @if ($canCreate && $pembelajaranOptions->isNotEmpty() && $activeTab === 'materi')
                <button type="button" class="btn btn-primary" id="btnOpenCreateModal"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                    title="Tambah Materi Baru" aria-label="Tambah Materi Baru">
                    <i class="fas fa-plus"></i>
                </button>
            @endif
            @if ($canCreate && $pembelajaranOptions->isNotEmpty() && $activeTab === 'tugas')
                <button type="button" class="btn btn-primary" id="btnOpenTugasModal"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                    title="Tambah Tugas Baru" aria-label="Tambah Tugas Baru">
                    <i class="fas fa-plus"></i>
                </button>
            @endif        </div>
    </div>

    {{-- 2. Flash Messages --}}
    @if (session('success'))
        <div style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); color: var(--success); padding: var(--space-6) var(--space-8); border-radius: var(--radius-lg); margin-bottom: var(--space-8); font-size: var(--text-sm); display: flex; align-items: center; gap: var(--space-4);">
            <i class="fas fa-circle-check"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); color: var(--danger); padding: var(--space-6) var(--space-8); border-radius: var(--radius-lg); margin-bottom: var(--space-8); font-size: var(--text-sm); display: flex; align-items: center; gap: var(--space-4);">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    {{-- 3. Stat Grid --}}
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-10);">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.12); color: var(--primary);">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Materi</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: var(--success);">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['terbit'] ?? 0) }}</div>
                <div class="dash-stat-label">Materi Terbit</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6, 182, 212, 0.12); color: var(--accent);">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['tugas_total'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Tugas</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.12); color: var(--warning);">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['tugas_terbit'] ?? 0) }}</div>
                <div class="dash-stat-label">Tugas Dibuka</div>
            </div>
        </div>
    </div>

    {{-- 4. Tab Navigasi (WAJIB di bawah card statistik, di atas datatable) --}}
    <div class="periode-nav-wrapper" style="margin-bottom: var(--space-8);">
        <div class="periode-nav-desktop">
            <a href="{{ $tabRoute('dasbor') }}" class="periode-nav-tab {{ $activeTab === 'dasbor' ? 'active' : '' }}">
                <i class="fas fa-gauge-high"></i> Dasbor
            </a>
            <a href="{{ $tabRoute('materi') }}" class="periode-nav-tab {{ $activeTab === 'materi' ? 'active' : '' }}">
                <i class="fas fa-book-open"></i> Materi
            </a>
            <a href="{{ $tabRoute('tugas') }}" class="periode-nav-tab {{ $activeTab === 'tugas' ? 'active' : '' }}">
                <i class="fas fa-clipboard-list"></i> Tugas
            </a>
            <a href="{{ $tabRoute('pengumpulan') }}" class="periode-nav-tab {{ $activeTab === 'pengumpulan' ? 'active' : '' }}">
                <i class="fas fa-inbox"></i> {{ $canUpdate ? 'Pengumpulan & Penilaian' : 'Pengumpulanku' }}
            </a>
        </div>
        <div class="periode-nav-mobile">
            <div class="periode-mobile-select-box" style="margin-bottom: 0;">
                <label><i class="fas fa-filter text-primary me-1"></i> Pilih Menu E-Learning:</label>
                <select class="periode-mobile-select form-select" onchange="if(this.value) window.location.href=this.value;"
                    style="font-size: var(--text-base, 0.875rem); height: 42px; border-radius: var(--radius-lg, 12px); width: 100%; background: var(--bg-card, #131b2e); color: var(--text-color); border: 1px solid var(--border-color);">
                    <option value="{{ $tabRoute('dasbor') }}" {{ $activeTab === 'dasbor' ? 'selected' : '' }}>Dasbor</option>
                    <option value="{{ $tabRoute('materi') }}" {{ $activeTab === 'materi' ? 'selected' : '' }}>Materi</option>
                    <option value="{{ $tabRoute('tugas') }}" {{ $activeTab === 'tugas' ? 'selected' : '' }}>Tugas</option>
                    <option value="{{ $tabRoute('pengumpulan') }}" {{ $activeTab === 'pengumpulan' ? 'selected' : '' }}>{{ $canUpdate ? 'Pengumpulan & Penilaian' : 'Pengumpulanku' }}</option>
                </select>
            </div>
        </div>
    </div>

    {{-- 5. Konten tab --}}
    @if ($activeTab === 'dasbor')
        @include('partials.elearning-dasbor')
    @elseif ($activeTab === 'tugas')
        @include('partials.elearning-tugas')
    @elseif ($activeTab === 'pengumpulan')
        @include('partials.elearning-pengumpulan')
    @else
        @include('partials.elearning-materi')
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/elearning.js') }}?v={{ file_exists(public_path('js/elearning.js')) ? filemtime(public_path('js/elearning.js')) : time() }}"></script>
@endpush
