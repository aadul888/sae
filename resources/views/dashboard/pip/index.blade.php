@extends('layouts.dashboard')

@section('title', 'Usulan & Riwayat PIP — SAE')
@section('dash_title', 'Usulan PIP')

@section('content')
    @php
        $routeParams = fn(array $extra = []) => array_filter(
            array_merge(
                ['rombel_id' => $isAdmin ? $activeRombel?->rombongan_belajar_id : null, 'tahun' => $activeTahun],
                $extra,
            ),
            fn($v) => $v !== null && $v !== '',
        );
    @endphp

    <!-- 1. Header Banner -->
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div
                style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16,185,129,0.12); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Usulan &amp; Riwayat PIP
                </h2>
                <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                    @if ($isAdmin)
                        Kelola kuota per kelas, pantau usulan wali kelas, verifikasi pengajuan ke web PIP, dan riwayat
                        penerima.
                    @else
                        Usulkan peserta didik kelas
                        <strong>{{ $activeRombel->nama ?? '-' }}</strong> beserta kriteria alasan dan urutan prioritasnya.
                    @endif
                </div>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($tab === 'usulan')
                <button type="button" class="btn btn-primary btn-sm" onclick="openTambahUsulan()"
                    style="padding: 7px 14px; border-radius: 8px; font-size: 0.84rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-plus"></i> Usulan
                </button>
            @elseif (in_array($tab, ['riwayat', 'riwayat_alumni'], true) && $isAdmin)
                <button type="button" class="btn btn-outline btn-sm" onclick="openTambahRiwayat()"
                    style="padding: 7px 12px; border-radius: 8px; font-size: 0.84rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-plus"></i> Manual
                </button>
                <button type="button" class="btn btn-primary btn-sm" onclick="openImportRiwayat()"
                    style="padding: 7px 14px; border-radius: 8px; font-size: 0.84rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-file-excel"></i> Import Excel
                </button>
            @endif
        </div>
    </div>

    <!-- 2. Flash Messages -->
    @if (session('success'))
        <div
            style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div
            style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- 3. Segmented Navigation Tabs -->
    <div class="dash-segmented-tabs" style="margin-bottom: 20px;">
        <a href="{{ route('dashboard.pip.index', $routeParams(['tab' => 'usulan'])) }}"
            class="tab-btn btn {{ $tab === 'usulan' ? 'btn-primary active' : 'btn-outline' }}">
            <i class="fas fa-list-ol me-1"></i> Usulan
        </a>
        @if ($isAdmin)
            <a href="{{ route('dashboard.pip.index', $routeParams(['tab' => 'kuota'])) }}"
                class="tab-btn btn {{ $tab === 'kuota' ? 'btn-primary active' : 'btn-outline' }}">
                <i class="fas fa-sliders me-1"></i> Kuota Kelas
            </a>
        @endif
        <a href="{{ route('dashboard.pip.index', $routeParams(['tab' => 'riwayat'])) }}"
            class="tab-btn btn {{ $tab === 'riwayat' ? 'btn-primary active' : 'btn-outline' }}">
            <i class="fas fa-clock-rotate-left me-1"></i> Riwayat (Siswa Aktif)
        </a>
        @if ($isAdmin)
            <a href="{{ route('dashboard.pip.index', $routeParams(['tab' => 'riwayat_alumni'])) }}"
                class="tab-btn btn {{ $tab === 'riwayat_alumni' ? 'btn-primary active' : 'btn-outline' }}">
                <i class="fas fa-user-graduate me-1"></i> Riwayat Kelas Lain &amp; Alumni
            </a>
        @endif
    </div>

    @if ($tab === 'usulan')
        @include('dashboard.pip.partials.tab-usulan')
    @elseif ($tab === 'kuota' && $isAdmin)
        @include('dashboard.pip.partials.tab-kuota')
    @elseif ($tab === 'riwayat_alumni' && $isAdmin)
        @include('dashboard.pip.partials.tab-riwayat-alumni')
    @else
        @include('dashboard.pip.partials.tab-riwayat')
    @endif

    @include('dashboard.pip.partials.modals')
@endsection

@push('scripts')
    <script>
        window.PIP_CONFIG = {
            isAdmin: @json($isAdmin),
            tahun: @json($activeTahun),
            rombelId: @json($isAdmin ? $activeRombel?->rombongan_belajar_id : null),
            routes: {
                cariSiswa: @json(route('dashboard.pip.cari-siswa')),
                storeUsulan: @json(route('dashboard.pip.store-usulan')),
                prioritas: @json(route('dashboard.pip.update-prioritas')),
                bulkVerifikasi: @json(route('dashboard.pip.bulk-verifikasi')),
                kuota: @json(route('dashboard.pip.update-kuota')),
                importRiwayat: @json(route('dashboard.pip.import-riwayat')),
                storeRiwayat: @json(route('dashboard.pip.store-riwayat')),
            },
            urlTemplates: {
                destroyUsulan: @json(route('dashboard.pip.destroy-usulan', ['id' => '__ID__'])),
                verifikasi: @json(route('dashboard.pip.update-verifikasi', ['id' => '__ID__'])),
                destroyRiwayat: @json(route('dashboard.pip.destroy-riwayat', ['id' => '__ID__'])),
            },
        };
    </script>
    <script
        src="{{ asset('js/pip-manage.js') }}?v={{ file_exists(public_path('js/pip-manage.js')) ? filemtime(public_path('js/pip-manage.js')) : time() }}">
    </script>
@endpush
