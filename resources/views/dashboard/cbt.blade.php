@extends('layouts.dashboard')

@section('title', 'CBT & Asesmen — SAE (Sistem Aplikasi Edukasi)')
@section('dash_title', 'CBT & Asesmen')

@section('content')
    @php
        $tabRoute = fn(string $tab) => route('dashboard.cbt.index', array_filter([
            'tab' => $tab,
            'pembelajaran_id' => $pembelajaranId ?: null,
        ]));
    @endphp

    {{-- 1. Header Banner --}}
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: var(--space-6);">
            <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(99, 102, 241, 0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl); flex-shrink: 0;">
                <i class="fas fa-file-pen"></i>
            </div>
            <div>
                <h2 style="font-size: var(--text-xl); font-weight: 800; color: var(--text-main); margin: 0 0 2px 0;">
                    CBT &amp; Asesmen
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-sm); margin: 0;">
                    Bank soal &amp; penyusunan ujian per mata pelajaran. Pelaksanaan ujian serentak diaktifkan setelah fondasi server siap.
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: var(--space-4); align-items: center; flex-wrap: wrap;">
            @if ($canCreate && $pembelajaranOptions->isNotEmpty() && $activeTab === 'bank-soal')
                <button type="button" class="btn btn-primary" id="btnOpenSoalModal"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                    title="Tambah Soal Baru" aria-label="Tambah Soal Baru">
                    <i class="fas fa-plus"></i>
                </button>
                <button type="button" class="btn btn-outline" id="btnOpenBulkSoalModal"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                    title="Tambah Banyak Soal" aria-label="Tambah Banyak Soal">
                    <i class="fas fa-layer-group"></i>
                </button>
            @endif
            @if ($canCreate && $activeTab === 'bank-soal')
                <button type="button" class="btn btn-outline" id="btnOpenImportSoalModal"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                    title="Impor Soal (Excel/CSV)" aria-label="Impor Soal (Excel/CSV)">
                    <i class="fas fa-file-import"></i>
                </button>
            @endif
            @if ($canCreate && $activeTab === 'ujian')
                <button type="button" class="btn btn-primary" id="btnOpenUjianModal"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                    title="Tambah Ujian Baru" aria-label="Tambah Ujian Baru">
                    <i class="fas fa-plus"></i>
                </button>
            @endif
            @if ($canCreate && $activeTab === 'periode')
                <button type="button" class="btn btn-primary" id="btnOpenPeriodeModal"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                    title="Tambah Periode Asesmen" aria-label="Tambah Periode Asesmen">
                    <i class="fas fa-plus"></i>
                </button>
            @endif
            @if ($canCreate && $activeTab === 'sesi')
                <button type="button" class="btn btn-primary" id="btnOpenSesiModal"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                    title="Tambah Sesi Ujian" aria-label="Tambah Sesi Ujian">
                    <i class="fas fa-plus"></i>
                </button>
            @endif
        </div>
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
                <i class="fas fa-circle-question"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['soal_total'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Soal</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6, 182, 212, 0.12); color: var(--accent);">
                <i class="fas fa-file-pen"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['ujian_total'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Ujian</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: var(--success);">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['ujian_terbit'] ?? 0) }}</div>
                <div class="dash-stat-label">Ujian Terbit</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.12); color: var(--warning);">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['ujian_draft'] ?? 0) }}</div>
                <div class="dash-stat-label">Ujian Draf</div>
            </div>
        </div>
    </div>

    {{-- 4. Tab Navigasi (WAJIB di bawah card statistik, di atas datatable) --}}
    <div class="periode-nav-wrapper" style="margin-bottom: var(--space-8);">
        <div class="periode-nav-desktop">
            <a href="{{ $tabRoute('dasbor') }}" class="periode-nav-tab {{ $activeTab === 'dasbor' ? 'active' : '' }}">
                <i class="fas fa-gauge-high"></i> Dasbor
            </a>
            <a href="{{ $tabRoute('bank-soal') }}" class="periode-nav-tab {{ $activeTab === 'bank-soal' ? 'active' : '' }}">
                <i class="fas fa-circle-question"></i> Bank Soal
            </a>
            <a href="{{ $tabRoute('ujian') }}" class="periode-nav-tab {{ $activeTab === 'ujian' ? 'active' : '' }}">
                <i class="fas fa-file-pen"></i> Ujian
            </a>
            <a href="{{ $tabRoute('periode') }}" class="periode-nav-tab {{ $activeTab === 'periode' ? 'active' : '' }}">
                <i class="fas fa-calendar-check"></i> Periode Asesmen
            </a>
            <a href="{{ $tabRoute('sesi') }}" class="periode-nav-tab {{ $activeTab === 'sesi' ? 'active' : '' }}">
                <i class="fas fa-clock"></i> Sesi Ujian
            </a>
        </div>
        <div class="periode-nav-mobile">
            <div class="periode-mobile-select-box" style="margin-bottom: 0;">
                <label><i class="fas fa-filter text-primary me-1"></i> Pilih Menu CBT:</label>
                <select class="periode-mobile-select form-select" onchange="if(this.value) window.location.href=this.value;"
                    style="font-size: var(--text-base, 0.875rem); height: 42px; border-radius: var(--radius-lg, 12px); width: 100%; background: var(--bg-card, #131b2e); color: var(--text-color); border: 1px solid var(--border-color);">
                    <option value="{{ $tabRoute('dasbor') }}" {{ $activeTab === 'dasbor' ? 'selected' : '' }}>Dasbor</option>
                    <option value="{{ $tabRoute('bank-soal') }}" {{ $activeTab === 'bank-soal' ? 'selected' : '' }}>Bank Soal</option>
                    <option value="{{ $tabRoute('ujian') }}" {{ $activeTab === 'ujian' ? 'selected' : '' }}>Ujian</option>
                    <option value="{{ $tabRoute('periode') }}" {{ $activeTab === 'periode' ? 'selected' : '' }}>Periode Asesmen</option>
                    <option value="{{ $tabRoute('sesi') }}" {{ $activeTab === 'sesi' ? 'selected' : '' }}>Sesi Ujian</option>
                </select>
            </div>
        </div>
    </div>

    {{-- 5. Konten tab --}}
    @if ($activeTab === 'dasbor')
        @include('partials.cbt-dasbor')
    @elseif ($activeTab === 'ujian')
        @include('partials.cbt-ujian')
    @elseif ($activeTab === 'periode')
        @include('partials.cbt-periode')
    @elseif ($activeTab === 'sesi')
        @include('partials.cbt-sesi')
    @else
        @include('partials.cbt-bank-soal')
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/cbt.js') }}?v={{ file_exists(public_path('js/cbt.js')) ? filemtime(public_path('js/cbt.js')) : time() }}"></script>
@endpush
