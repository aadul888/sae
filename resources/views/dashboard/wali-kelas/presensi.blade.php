@extends('layouts.dashboard')

@section('title', 'Presensi Kelas Binaan — Wali Kelas — SAE')
@section('dash_title', 'Presensi Kelas Binaan')

@push('styles')
    <link rel="stylesheet"
        href="{{ asset('css/presensi.css') }}?v={{ file_exists(public_path('css/presensi.css')) ? filemtime(public_path('css/presensi.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/wali-kelas.css') }}?v={{ file_exists(public_path('css/wali-kelas.css')) ? filemtime(public_path('css/wali-kelas.css')) : time() }}">
@endpush

@section('content')
    <!-- Header Banner -->
    <div class="dash-banner" style="margin-bottom: 20px;">
        <div>
            <h2 style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-clipboard-user text-primary me-2"></i> Presensi Kelas Binaan
            </h2>
            <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem); margin: 0;">
                @if ($hasRombel && $activeRombel)
                    Kelas Binaan: <strong style="color: var(--text-color);">{{ $activeRombel->nama }}</strong>
                    &bull; Wali Kelas: <strong style="color: var(--primary);">{{ $waliNama }}</strong>
                    @if (!empty($activeRombel->jurusan_id_str))
                        &bull; Jurusan: {{ $activeRombel->jurusan_id_str }}
                    @endif
                @else
                    Monitoring dan pencatatan presensi peserta didik kelas binaan.
                @endif
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($hasRombel && $activeRombel)
                <button type="button" id="btnOpenModalPdf" class="btn btn-outline"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                    title="Cetak Laporan Presensi">
                    <i class="fas fa-print text-primary"></i>
                </button>
                <button type="button" id="btnResetPresensiWali" class="btn btn-outline"
                    data-rombel="{{ $activeRombel->rombongan_belajar_id }}" data-tanggal="{{ $tanggal }}"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); color: var(--danger, #ef4444); border-color: rgba(239,68,68,0.4);"
                    title="Reset Presensi Manual Kelas">
                    <i class="fas fa-rotate-left"></i>
                </button>
            @endif
        </div>
    </div>

    @if (!$hasRombel)
        <!-- Empty State jika Guru belum ada tugas rombel -->
        <div class="card" style="padding: 40px 20px; text-align: center; margin-top: 20px;">
            <div
                style="width: 70px; height: 70px; border-radius: 50%; background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b); display: inline-flex; align-items: center; justify-content: center; font-size: var(--text-3xl, 1.875rem); margin-bottom: 16px;">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <h3 style="font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color); margin-bottom: 8px;">
                Belum Ada Rombel Binaan Terdaftar
            </h3>
            <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem); max-width: 540px; margin: 0 auto 20px;">
                Akun Anda belum terikat dengan rombongan belajar aktif sebagai Wali Kelas pada data Dapodik sekolah. Silakan
                hubungi Administrator sistem atau Operator Dapodik untuk pembaruan penugasan tugas tambahan.
            </p>
        </div>
    @else
        <!-- Desktop Segmented Tabs -->
        <div class="dash-tabs-nav dash-desktop-tabs" style="margin-bottom: 20px;">
            <a href="{{ request()->fullUrlWithQuery(['tab' => 'harian', 'page_izin' => null]) }}"
                class="btn {{ $activeTab === 'harian' ? 'btn-primary' : 'btn-outline' }}">
                <i class="fas fa-calendar-day me-1"></i> Presensi Harian ({{ $totalSiswa }})
            </a>
            <a href="{{ request()->fullUrlWithQuery(['tab' => 'izin', 'page' => null]) }}"
                class="btn {{ $activeTab === 'izin' ? 'btn-primary' : 'btn-outline' }}">
                <i class="fas fa-envelope-open-text me-1"></i> Surat Izin &amp; Sakit
                @if ($pendingIzinCount > 0)
                    <span class="badge badge-warning badge-sm ms-1">{{ $pendingIzinCount }} Menunggu</span>
                @else
                    <span
                        class="badge {{ $activeTab === 'izin' ? 'badge-primary' : 'badge-outline' }} badge-sm ms-1">{{ $totalIzinCount }}</span>
                @endif
            </a>
        </div>

        <!-- Mobile Custom Dropdown Selector -->
        <div class="dash-mobile-tab-select-wrap" style="margin-bottom: 20px;">
            <div class="dash-custom-dropdown">
                <button type="button" class="custom-dropdown-trigger" aria-haspopup="listbox" aria-expanded="false">
                    <div class="custom-dropdown-trigger-label">
                        <i
                            class="fas {{ $activeTab === 'harian' ? 'fa-calendar-day' : 'fa-envelope-open-text' }} text-primary me-2"></i>
                        <span>{{ $activeTab === 'harian' ? 'Presensi Harian' : 'Surat Izin & Sakit' }}</span>
                        @if ($activeTab === 'izin' && $pendingIzinCount > 0)
                            <span class="badge badge-warning badge-sm ms-2">{{ $pendingIzinCount }} Menunggu</span>
                        @else
                            <span
                                class="badge badge-primary badge-sm ms-2">{{ $activeTab === 'harian' ? $totalSiswa : $totalIzinCount }}</span>
                        @endif
                    </div>
                    <i class="fas fa-chevron-down custom-dropdown-arrow"></i>
                </button>
                <div class="custom-dropdown-menu" role="listbox">
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'harian', 'page_izin' => null]) }}"
                        class="custom-dropdown-item {{ $activeTab === 'harian' ? 'active' : '' }}">
                        <div class="dropdown-item-left">
                            <i class="fas fa-calendar-day text-primary me-2"></i>
                            <span>Presensi Harian</span>
                        </div>
                        <span
                            class="badge {{ $activeTab === 'harian' ? 'badge-primary' : 'badge-outline' }} badge-sm">{{ $totalSiswa }}</span>
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'izin', 'page' => null]) }}"
                        class="custom-dropdown-item {{ $activeTab === 'izin' ? 'active' : '' }}">
                        <div class="dropdown-item-left">
                            <i class="fas fa-envelope-open-text text-primary me-2"></i>
                            <span>Surat Izin &amp; Sakit</span>
                        </div>
                        @if ($pendingIzinCount > 0)
                            <span class="badge badge-warning badge-sm">{{ $pendingIzinCount }} Menunggu</span>
                        @else
                            <span
                                class="badge {{ $activeTab === 'izin' ? 'badge-primary' : 'badge-outline' }} badge-sm">{{ $totalIzinCount }}</span>
                        @endif
                    </a>
                </div>
            </div>
        </div>

        @if ($activeTab === 'harian')
            @include('dashboard.wali-kelas.partials.tab-presensi-harian')
        @else
            @include('dashboard.wali-kelas.partials.tab-presensi-izin')
        @endif
    @endif

    @include('dashboard.wali-kelas.partials.modal-presensi-kelas')
@endsection

@push('scripts')
    <script
        src="{{ asset('js/wali-kelas-presensi.js') }}?v={{ file_exists(public_path('js/wali-kelas-presensi.js')) ? filemtime(public_path('js/wali-kelas-presensi.js')) : time() }}">
    </script>
@endpush
