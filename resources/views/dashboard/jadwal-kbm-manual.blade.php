@extends('layouts.dashboard')

@section('title', 'Jadwal KBM Manual — ' . ($activeRombel ? $activeRombel->nama : 'Kelas') . ' — SAE')
@section('dash_title', 'Jadwal KBM Manual' . ($activeRombel ? ' — ' . $activeRombel->nama : ''))

@push('styles')
<style>
/* Layout Grid Dual-Panel Modul Jadwal KBM Manual */
.jadwal-manual-layout-grid {
    display: grid;
    grid-template-columns: minmax(320px, 380px) 1fr;
    gap: 20px;
    align-items: start;
}

.panel-mapel-wrap {
    padding: 16px;
    border-radius: 12px;
    max-height: calc(100vh - 220px);
    overflow-y: auto;
}

.panel-matriks-wrap {
    padding: 0;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 24px;
}

/* Mobile Segmented Switcher (Mapel vs Matriks) */
.manual-mobile-switcher {
    display: none;
    margin-bottom: 16px;
}
.manual-tab-bar {
    display: flex;
    background: var(--bg-card, #1e293b);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 4px;
    gap: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
.manual-tab-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px 14px;
    border: none;
    border-radius: 9px;
    background: transparent;
    color: var(--text-muted);
    font-size: 0.84rem;
    font-weight: 700;
    cursor: pointer;
    touch-action: manipulation;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.manual-tab-btn.active {
    background: var(--primary, #6366f1);
    color: #fff;
    box-shadow: 0 2px 10px rgba(99, 102, 241, 0.4);
}

/* Matriks Table Horizontal Scroll Container (Mencegah Table Ter-stack Menjadi Balok Vertikal) */
.matrix-scroll-container {
    width: 100% !important;
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch !important;
    touch-action: pan-x pan-y !important;
    overscroll-behavior-x: contain;
    position: relative;
    border-radius: 0 0 12px 12px;
}
.matrix-scroll-container table.matrix-table {
    display: table !important;
    width: 100% !important;
    min-width: 880px !important;
    border-collapse: separate !important;
    border-spacing: 0 !important;
    margin: 0 !important;
}
.matrix-scroll-container thead {
    display: table-header-group !important;
}
.matrix-scroll-container tbody {
    display: table-row-group !important;
}
.matrix-scroll-container tr {
    display: table-row !important;
}
.matrix-scroll-container th,
.matrix-scroll-container td {
    display: table-cell !important;
    box-sizing: border-box !important;
}
.matrix-scroll-container tbody td::before {
    display: none !important;
}

/* Sticky Column untuk Jam/JP di Matriks */
.sticky-jp-col {
    position: sticky !important;
    left: 0 !important;
    z-index: 5 !important;
    width: 88px !important;
    min-width: 88px !important;
    background: var(--bg-card, #1e293b) !important;
    box-shadow: 3px 0 8px rgba(0, 0, 0, 0.22) !important;
}
th.sticky-jp-col {
    z-index: 6 !important;
    background: var(--bg-card, #1e293b) !important;
}

/* Hint Scroll Horizontal di Layar Sempit */
.table-scroll-hint {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.73rem;
    color: var(--text-muted);
    background: rgba(99, 102, 241, 0.08);
    border: 1px dashed rgba(99, 102, 241, 0.25);
    padding: 7px 14px;
    border-radius: 8px;
    margin: 10px 16px 0 16px;
}

/* Tombol Aksi Slot Touch-Friendly & Ergonomis */
.btn-slot-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    min-width: 32px;
    min-height: 32px;
    border-radius: 7px;
    border: 1px solid var(--border-color);
    background: var(--bg-hover, rgba(255,255,255,0.06));
    color: var(--text-color);
    font-size: 0.78rem;
    cursor: pointer;
    touch-action: manipulation;
    transition: all 0.15s ease;
}
.btn-slot-action:hover, .btn-slot-action:focus-visible {
    background: var(--primary);
    color: #fff;
    border-color: var(--primary);
    transform: translateY(-1px);
}
.btn-slot-action.btn-slot-delete {
    color: #ef4444;
    border-color: rgba(239, 68, 68, 0.3);
}
.btn-slot-action.btn-slot-delete:hover, .btn-slot-action.btn-slot-delete:focus-visible {
    background: #ef4444;
    color: #fff;
    border-color: #ef4444;
}

/* Mobile Matriks Switcher (Mode Harian vs Mode Tabel) */
.mobile-matriks-switcher {
    display: none;
    padding: 12px 16px 0 16px;
}
.matriks-view-mode-bar {
    display: flex;
    background: var(--bg-hover, rgba(255,255,255,0.04));
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 3px;
    gap: 4px;
}
.matriks-mode-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px 12px;
    border: none;
    border-radius: 7px;
    background: transparent;
    color: var(--text-muted);
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    touch-action: manipulation;
    transition: all 0.2s ease;
}
.matriks-mode-btn.active {
    background: var(--primary, #6366f1);
    color: #fff;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.35);
}

/* Mobile Daily Timeline View */
.view-harian-wrapper {
    display: none;
    padding: 14px 16px;
}
.harian-day-pills-bar {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 10px;
    margin-bottom: 14px;
    border-bottom: 1px solid var(--border-color);
}
.harian-day-pills-bar::-webkit-scrollbar {
    height: 4px;
}
.harian-day-pills-bar::-webkit-scrollbar-thumb {
    background: var(--border-color);
    border-radius: 4px;
}
.harian-day-pill {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    background: var(--bg-hover, rgba(255,255,255,0.03));
    color: var(--text-color);
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    touch-action: manipulation;
    transition: all 0.15s ease;
}
.harian-day-pill.active {
    background: var(--primary, #6366f1);
    color: #fff;
    border-color: var(--primary);
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.35);
}
.harian-day-pill .day-count-badge {
    font-size: 0.68rem;
    padding: 1px 6px;
    border-radius: 10px;
    background: rgba(255,255,255,0.22);
    color: inherit;
}
.harian-day-feed {
    display: none;
}
.harian-day-feed.active {
    display: block;
}
.harian-timeline-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.harian-card-item {
    border: 1px solid var(--border-color);
    border-radius: 10px;
    background: var(--bg-card);
    padding: 14px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}
.harian-card-scheduled {
    border-left: 4px solid var(--primary, #6366f1);
    background: rgba(99, 102, 241, 0.03);
}
.harian-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    padding-bottom: 6px;
    border-bottom: 1px dashed var(--border-color);
}
.harian-card-time {
    display: flex;
    align-items: center;
    gap: 8px;
}
.harian-jp-badge {
    font-size: 0.76rem;
    font-weight: 800;
    color: var(--text-color);
    background: var(--bg-hover);
    padding: 2px 8px;
    border-radius: 6px;
    border: 1px solid var(--border-color);
}
.harian-clock-text {
    font-size: 0.74rem;
    color: var(--text-muted);
}
.harian-mapel-name {
    margin: 0 0 4px 0;
    font-size: 0.94rem;
    font-weight: 800;
    color: var(--text-color);
    line-height: 1.35;
}
.harian-guru-name, .harian-room-name {
    font-size: 0.78rem;
    color: var(--text-muted);
    margin-top: 3px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.harian-conflict-warn {
    font-size: 0.72rem;
    color: #ef4444;
    background: rgba(239,68,68,0.1);
    border-radius: 6px;
    padding: 3px 8px;
    margin-top: 6px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.harian-card-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 10px;
    padding-top: 8px;
    border-top: 1px dashed var(--border-color);
}
.harian-empty-day {
    text-align: center;
    padding: 32px 16px;
    color: var(--text-muted);
    font-size: 0.84rem;
}

/* Responsif Mobile (< 992px) */
@media (max-width: 991px) {
    .jadwal-manual-layout-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    .manual-mobile-switcher {
        display: block;
    }
    .panel-mapel-wrap {
        max-height: none;
    }
    .mobile-matriks-switcher {
        display: block;
    }
    .view-harian-wrapper.mode-active {
        display: block;
    }
    .view-tabel-wrapper {
        display: none;
    }
    .view-tabel-wrapper.mode-active {
        display: block;
    }
    .dash-banner-actions {
        width: 100%;
        margin-top: 10px;
    }
}

/* Tampilan Desktop (>= 992px) */
@media (min-width: 992px) {
    .mobile-matriks-switcher {
        display: none !important;
    }
    .view-harian-wrapper {
        display: none !important;
    }
    .view-tabel-wrapper {
        display: block !important;
    }
    .table-scroll-hint {
        display: none !important;
    }
}
</style>
@endpush

@section('content')
    <!-- Header Banner -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-calendar-plus text-primary me-2"></i> Input Jadwal KBM Manual
                @if ($activeRombel)
                    <span class="badge badge-primary" style="font-size: 0.82rem; margin-left: 6px; padding: 4px 10px; border-radius: 6px;">
                        Kelas {{ $activeRombel->nama }}
                    </span>
                @endif
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Modul khusus Wali Kelas / Koordinator Kelas untuk menyusun jam dan mata pelajaran sesuai guru pengampu secara terstruktur dan anti-bentrok.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <!-- Pemberlakuan Jadwal Aktif oleh Admin (Eksklusif: Otomatis vs Manual) -->
            @if ($isAdmin)
                <div class="pemberlakuan-badge-wrap" style="display: inline-flex; align-items: center; gap: 8px;">
                    @if ($isManualAktif)
                        <span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); font-size: 0.78rem; padding: 6px 12px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-check-circle"></i> Jadwal Manual Diberlakukan (Aktif)
                        </span>
                        <button type="button" class="btn btn-outline btn-toggle-pemberlakuan" data-mode="manual" data-status="draft" data-url="{{ route('dashboard.jadwal-kbm.toggle-pemberlakuan') }}" onclick="if(window.handleTogglePemberlakuan){window.handleTogglePemberlakuan(this);}" title="Alihkan ke Status Draft (Sembunyikan dari Guru/Siswa)" style="padding: 6px 12px; font-size: 0.78rem; border-color: rgba(245,158,11,0.4); color: #f59e0b; cursor: pointer;">
                            <i class="fas fa-pause me-1"></i> Jadikan Draft
                        </button>
                    @elseif ($isOtomatisAktif)
                        <span class="badge" style="background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); font-size: 0.78rem; padding: 6px 12px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-clock"></i> Status Draft (Jadwal Otomatis Aktif)
                        </span>
                        <button type="button" class="btn btn-toggle-pemberlakuan" data-mode="manual" data-status="aktif" data-url="{{ route('dashboard.jadwal-kbm.toggle-pemberlakuan') }}" onclick="if(window.handleTogglePemberlakuan){window.handleTogglePemberlakuan(this);}" title="Berlakukan Jadwal Manual Ini ke Seluruh Guru & Siswa (Menonaktifkan Jadwal Otomatis)" style="padding: 6px 14px; font-size: 0.78rem; background: #10b981; color: #fff; font-weight: 700; border: none; box-shadow: 0 2px 8px rgba(16,185,129,0.3); cursor: pointer;">
                            <i class="fas fa-play me-1"></i> Berlakukan Jadwal Manual
                        </button>
                    @else
                        <span class="badge" style="background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); font-size: 0.78rem; padding: 6px 12px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-clock"></i> Status Draft (Dalam Penyusunan)
                        </span>
                        <button type="button" class="btn btn-toggle-pemberlakuan" data-mode="manual" data-status="aktif" data-url="{{ route('dashboard.jadwal-kbm.toggle-pemberlakuan') }}" onclick="if(window.handleTogglePemberlakuan){window.handleTogglePemberlakuan(this);}" title="Berlakukan Jadwal Manual Ini ke Seluruh Guru & Siswa" style="padding: 6px 14px; font-size: 0.78rem; background: #10b981; color: #fff; font-weight: 700; border: none; box-shadow: 0 2px 8px rgba(16,185,129,0.3); cursor: pointer;">
                            <i class="fas fa-play me-1"></i> Berlakukan Jadwal Manual
                        </button>
                    @endif
                </div>
            @endif

            <!-- Selector Rombel Khusus Admin -->
            @if ($isAdmin && $rombelList->isNotEmpty())
                <div style="display: flex; align-items: center; gap: 6px;">
                    <label for="adminSelectRombel" style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin: 0;">Pilih Kelas:</label>
                    <select id="adminSelectRombel" class="per-page-select" style="padding: 6px 12px; font-weight: 700; border-radius: 8px;">
                        @foreach ($rombelList as $r)
                            <option value="{{ $r->rombongan_belajar_id }}" {{ $activeRombel && $activeRombel->rombongan_belajar_id === $r->rombongan_belajar_id ? 'selected' : '' }}>
                                {{ $r->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($activeRombel)
                <a href="{{ route('dashboard.jadwal-kbm.cetak-rombel', ['rombel_id' => $activeRombel->rombongan_belajar_id]) }}" target="_blank"
                    class="btn btn-outline" style="padding: 7px 14px; font-size: 0.84rem;" title="Cetak Jadwal Kelas Ini (Format Siap Tempel)">
                    <i class="fas fa-print me-1"></i> Cetak Jadwal
                </a>

                <button type="button" class="btn btn-outline" id="btnResetRombel"
                    data-rombel-id="{{ $activeRombel->rombongan_belajar_id }}"
                    data-rombel-name="{{ $activeRombel->nama }}"
                    style="padding: 7px 14px; font-size: 0.84rem; border-color: rgba(239,68,68,0.4); color: #ef4444;" title="Kosongkan Seluruh Jadwal Kelas Ini">
                    <i class="fas fa-rotate-left me-1"></i> Reset Kelas
                </button>

                <button type="button" class="btn btn-primary" id="btnTambahSlotManual"
                    style="padding: 7px 16px; font-size: 0.84rem;" title="Tambah Slot Pelajaran Baru">
                    <i class="fas fa-plus me-1"></i> Tambah Slot
                </button>
            @endif
        </div>
    </div>

    <!-- Sub-Menu Navigasi Jadwal KBM (Generate vs Manual) -->
    <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
        <div class="periode-nav-desktop">
            <a href="{{ route('dashboard.jadwal-kbm.index') }}" class="periode-nav-tab">
                <i class="fas fa-wand-magic-sparkles me-1"></i> Generate Jadwal (Otomatis)
            </a>
            <a href="{{ route('dashboard.jadwal-kbm.manual.index', $activeRombel ? ['rombel_id' => $activeRombel->rombongan_belajar_id] : []) }}" class="periode-nav-tab active">
                <i class="fas fa-pen-to-square me-1"></i> Jadwal Manual (Per Kelas)
            </a>
        </div>
    </div>

    <!-- Banner Integrasi Kalender Pendidikan -->
    <div class="card" style="padding: 12px 18px; margin-bottom: 18px; border-radius: 12px; background: var(--bg-card); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 12px;">
            @php
                $isLibur = ($statusHariIni['is_libur'] ?? false) || ($statusHariIni['libur_gtk'] ?? false) || ($statusHariIni['libur_pd'] ?? false);
                $bgIcon = $isLibur ? 'rgba(239,68,68,0.12)' : (!empty($agendaHariIni) ? 'rgba(99,102,241,0.12)' : 'rgba(16,185,129,0.12)');
                $colIcon = $isLibur ? '#ef4444' : (!empty($agendaHariIni) ? 'var(--primary)' : '#10b981');
                $iconClass = $isLibur ? 'fa-umbrella-beach' : (!empty($agendaHariIni) ? 'fa-calendar-star' : 'fa-calendar-check');
            @endphp
            <div style="width: 40px; height: 40px; border-radius: 10px; background: {{ $bgIcon }}; color: {{ $colIcon }}; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0;">
                <i class="fas {{ $iconClass }}"></i>
            </div>
            <div>
                <div style="font-size: 0.88rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <span>Kalender Akademik: TA {{ $tahunAjaranAktif ?? '2026/2027' }} (Semester {{ $semesterAktif ?? '1' }} {{ ($semesterAktif ?? '1') == '1' ? 'Ganjil' : 'Genap' }})</span>
                    @if ($isLibur)
                        <span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444; font-size: 0.72rem; padding: 2px 8px; border-radius: 8px;">Libur</span>
                    @elseif (!empty($agendaHariIni))
                        <span class="badge" style="background: rgba(99,102,241,0.15); color: var(--primary); font-size: 0.72rem; padding: 2px 8px; border-radius: 8px;">Agenda Khusus</span>
                    @else
                        <span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; font-size: 0.72rem; padding: 2px 8px; border-radius: 8px;">Hari Efektif KBM</span>
                    @endif
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                    Hari Ini ({{ \Carbon\Carbon::parse($tanggalHariIni ?? now())->translatedFormat('l, d F Y') }}):
                    @if ($isLibur)
                        <strong style="color: #ef4444;">{{ $statusHariIni['keterangan'] ?? 'Hari Libur / Tidak Ada KBM' }}</strong>
                    @elseif (!empty($agendaHariIni))
                        <strong style="color: var(--primary);">{{ $agendaHariIni->kegiatan }}</strong>
                    @else
                        KBM Berjalan Normal
                    @endif
                </div>
            </div>
        </div>
        <div>
            <a href="{{ route('dashboard.kalender-pendidikan.index') }}" class="btn btn-outline" style="padding: 7px 14px; font-size: 0.82rem;">
                <i class="fas fa-calendar-alt me-1"></i> Buka Kalender Pendidikan
            </a>
        </div>
    </div>

    @if (!empty($errorNotice))
        <div class="card" style="padding: 24px; text-align: center; margin-bottom: 24px; border: 1.5px dashed rgba(239,68,68,0.4); background: rgba(239,68,68,0.04);">
            <div style="width: 52px; height: 52px; border-radius: 50%; background: rgba(239,68,68,0.12); color: #ef4444; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 12px;">
                <i class="fas fa-circle-exclamation"></i>
            </div>
            <h4 style="margin-bottom: 6px; font-weight: 700; color: var(--text-color);">Akses Belum Dikonfigurasi</h4>
            <p style="color: var(--text-muted); font-size: 0.88rem; max-width: 500px; margin: 0 auto;">
                {{ $errorNotice }}
            </p>
        </div>
    @elseif ($activeRombel)

        <!-- Summary Stats Grid & Progress Bar -->
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); margin-bottom: 16px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                    <i class="fas fa-bullseye"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ $summary['target_jp'] }} <span style="font-size: 0.72rem; font-weight: 500;">JP</span>
                    </div>
                    <div class="dash-stat-label">Target JJM Mingguan</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #10b981;">
                        {{ $summary['total_terjadwal'] }} <span style="font-size: 0.72rem; font-weight: 500;">JP</span>
                    </div>
                    <div class="dash-stat-label">Sudah Terjadwal</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: {{ $summary['sisa_jp'] > 0 ? 'rgba(245,158,11,0.15)' : 'rgba(16,185,129,0.15)' }}; color: {{ $summary['sisa_jp'] > 0 ? '#f59e0b' : '#10b981' }};">
                    <i class="fas {{ $summary['sisa_jp'] > 0 ? 'fa-hourglass-half' : 'fa-circle-check' }}"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: {{ $summary['sisa_jp'] > 0 ? '#f59e0b' : '#10b981' }};">
                        {{ $summary['sisa_jp'] }} <span style="font-size: 0.72rem; font-weight: 500;">JP</span>
                    </div>
                    <div class="dash-stat-label">Sisa Belum Terjadwal</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(14,165,233,0.15); color: #0ea5e9;">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ $summary['mapel_lengkap'] }} / {{ $summary['total_mapel'] }}
                    </div>
                    <div class="dash-stat-label">Mapel Lengkap Terisi</div>
                </div>
            </div>
        </div>

        <!-- Progress Bar Keterisian Jadwal -->
        <div class="card" style="padding: 14px 18px; margin-bottom: 22px; border-radius: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 0.82rem; font-weight: 700; color: var(--text-color);">
                    <i class="fas fa-chart-line text-primary me-1"></i> Progres Penyusunan Jadwal Kelas {{ $activeRombel->nama }}
                </span>
                <span style="font-size: 0.82rem; font-weight: 800; color: {{ $summary['persen_selesai'] >= 100 ? '#10b981' : 'var(--primary)' }};">
                    {{ $summary['persen_selesai'] }}% Selesai ({{ $summary['total_terjadwal'] }} dari {{ $summary['target_jp'] }} JP)
                </span>
            </div>
            <div style="width: 100%; height: 9px; background: var(--bg-hover); border-radius: 8px; overflow: hidden; border: 1px solid var(--border-color);">
                <div style="width: {{ $summary['persen_selesai'] }}%; height: 100%; background: {{ $summary['persen_selesai'] >= 100 ? 'linear-gradient(90deg, #10b981, #059669)' : 'linear-gradient(90deg, #6366f1, #8b5cf6)' }}; transition: width 0.4s ease;"></div>
            </div>
        </div>

        <!-- Mobile View Switcher (Khusus Layar < 992px) -->
        <div class="manual-mobile-switcher">
            <div class="manual-tab-bar">
                <button type="button" class="manual-tab-btn active" data-tab="matriks">
                    <i class="fas fa-table-cells me-1"></i> Matriks Jadwal
                </button>
                <button type="button" class="manual-tab-btn" data-tab="mapel">
                    <i class="fas fa-chalkboard-user me-1"></i> Guru &amp; Mapel ({{ $pembelajaranData->count() }})
                </button>
            </div>
        </div>

        <!-- Dual-Panel Layout: Panel Kiri (Mapel & Guru) & Panel Kanan (Matriks Slot Mingguan) -->
        <div class="jadwal-manual-layout-grid">
            
            <!-- Panel Kiri: Daftar Mapel & Guru Pengampu -->
            <div class="card panel-mapel-wrap" id="panelMapelWrapper">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                    <div>
                        <h4 style="margin: 0; font-size: 0.95rem; font-weight: 800; color: var(--text-color);">
                            <i class="fas fa-chalkboard-user text-primary me-1"></i> Guru Pengampu &amp; Mapel
                        </h4>
                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                            Berdasarkan data pembelajaran kelas
                        </div>
                    </div>
                    <span class="badge badge-outline" style="font-size: 0.72rem;">{{ $pembelajaranData->count() }} Mapel</span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px;" id="pembelajaranListWrap">
                    @forelse ($pembelajaranData as $p)
                        @php
                            $isLengkap = $p->is_complete;
                            $borderCol = $isLengkap ? 'rgba(16,185,129,0.3)' : ($p->scheduled_jp > 0 ? 'rgba(99,102,241,0.3)' : 'var(--border-color)');
                            $bgCol = $isLengkap ? 'rgba(16,185,129,0.04)' : 'var(--bg-card)';
                        @endphp
                        <div class="card-mapel-item" style="padding: 12px; border-radius: 10px; border: 1px solid {{ $borderCol }}; background: {{ $bgCol }}; transition: transform 0.15s ease, border-color 0.15s ease;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 0.86rem; font-weight: 800; color: var(--text-color); margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $p->nama_mata_pelajaran }}">
                                        {{ $p->nama_mata_pelajaran }}
                                    </div>
                                    <div style="font-size: 0.76rem; color: var(--text-muted); display: flex; align-items: center; gap: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <i class="fas fa-user-tie" style="font-size: 0.7rem; opacity: 0.7;"></i>
                                        <span title="{{ $p->nama_guru ?: 'Belum ditentukan' }}">{{ $p->nama_guru ?: 'Guru belum ditentukan' }}</span>
                                    </div>
                                </div>
                                @if ($p->is_pilihan)
                                    <span class="badge" style="background: rgba(14,165,233,0.12); color: #0ea5e9; font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; flex-shrink: 0;">
                                        Pilihan
                                    </span>
                                @endif
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                                <div style="font-size: 0.74rem;">
                                    <span style="color: var(--text-muted);">Alokasi:</span>
                                    <strong style="color: var(--text-color);">{{ $p->target_jp }} JP</strong>
                                    &bull;
                                    <span style="color: {{ $isLengkap ? '#10b981' : ($p->scheduled_jp > 0 ? 'var(--primary)' : '#f59e0b') }}; font-weight: 700;">
                                        {{ $p->scheduled_jp }}/{{ $p->target_jp }} JP
                                    </span>
                                </div>

                                <button type="button" class="btn btn-sm btn-jadwalkan-mapel"
                                    data-pembelajaran-id="{{ $p->pembelajaran_id }}"
                                    data-mapel-id="{{ $p->mata_pelajaran_id }}"
                                    data-nama-mapel="{{ $p->nama_mata_pelajaran }}"
                                    data-ptk-id="{{ $p->ptk_id }}"
                                    data-nama-guru="{{ $p->nama_guru }}"
                                    data-target-jp="{{ $p->target_jp }}"
                                    data-remaining-jp="{{ $p->remaining_jp }}"
                                    style="padding: 4px 10px; font-size: 0.74rem; font-weight: 700; border-radius: 6px; {{ $isLengkap ? 'border: 1px solid var(--border-color); color: var(--text-muted); background: transparent;' : 'background: var(--primary); color: #fff; border: none;' }}"
                                    title="{{ $isLengkap ? 'Target JP sudah terpenuhi (tetap bisa dijadwalkan jika perlu)' : 'Jadwalkan mapel ini ke slot kosong' }}">
                                    <i class="fas fa-plus me-1"></i> Jadwalkan
                                </button>
                            </div>
                        </div>
                    @empty
                        <div style="text-align: center; padding: 24px; color: var(--text-muted); font-size: 0.82rem;">
                            <i class="fas fa-folder-open mb-2" style="font-size: 1.5rem; opacity: 0.4;"></i>
                            <div>Belum ada data pembelajaran untuk kelas ini.</div>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Panel Kanan: Matriks Slot Mingguan Kelas (Responsif Dual-Mode: Harian & Tabel) -->
            <div class="card panel-matriks-wrap" id="panelMatriksWrapper">
                <div style="padding: 14px 18px; background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h4 style="margin: 0; font-size: 0.95rem; font-weight: 800; color: var(--text-color);">
                            <i class="fas fa-table-cells text-primary me-1"></i> Matriks Jadwal Mingguan Kelas
                        </h4>
                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                            Klik slot kosong untuk memasukkan pelajaran atau klik kartu pelajaran untuk mengedit/menghapus.
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; font-size: 0.72rem; flex-wrap: wrap;">
                        <span style="display: inline-flex; align-items: center; gap: 4px; color: var(--text-muted);">
                            <span style="width: 10px; height: 10px; border-radius: 3px; background: rgba(99,102,241,0.2); border: 1px solid var(--primary);"></span> Terjadwal
                        </span>
                        <span style="display: inline-flex; align-items: center; gap: 4px; color: var(--text-muted);">
                            <span style="width: 10px; height: 10px; border-radius: 3px; background: rgba(239,68,68,0.15); border: 1px solid #ef4444;"></span> Upacara
                        </span>
                        <span style="display: inline-flex; align-items: center; gap: 4px; color: var(--text-muted);">
                            <span style="width: 10px; height: 10px; border-radius: 3px; background: rgba(16,185,129,0.15); border: 1px solid #10b981;"></span> Pembiasaan
                        </span>
                        <span style="display: inline-flex; align-items: center; gap: 4px; color: var(--text-muted);">
                            <span style="width: 10px; height: 10px; border-radius: 3px; background: rgba(245,158,11,0.15); border: 1px solid #f59e0b;"></span> Istirahat
                        </span>
                    </div>
                </div>

                <!-- Switcher Mode Tampilan Matriks Khusus Mobile (< 992px) -->
                <div class="mobile-matriks-switcher">
                    <div class="matriks-view-mode-bar">
                        <button type="button" class="matriks-mode-btn active" data-mode="harian">
                            <i class="fas fa-calendar-day me-1"></i> Mode Harian
                        </button>
                        <button type="button" class="matriks-mode-btn" data-mode="tabel">
                            <i class="fas fa-table-cells me-1"></i> Mode Tabel (Geser)
                        </button>
                    </div>
                </div>

                <!-- 1. TAMPILAN MODE HARIAN (Mobile-First Native Timeline) -->
                <div id="viewHarianMobile" class="view-harian-wrapper mode-active">
                    @php
                        $todayName = \Carbon\Carbon::now()->locale('id')->isoFormat('dddd');
                        $defaultActiveDay = in_array($todayName, $hariAktif, true) ? $todayName : ($hariAktif[0] ?? 'Senin');
                    @endphp

                    <!-- Day Selector Pills (Scrollable Horizontal) -->
                    <div class="harian-day-pills-bar">
                        @foreach ($hariAktif as $h)
                            @php
                                $scheduledCount = 0;
                                if (isset($scheduleMatrix[$h])) {
                                    foreach ($scheduleMatrix[$h] as $sc) {
                                        if ($sc) $scheduledCount++;
                                    }
                                }
                            @endphp
                            <button type="button" class="harian-day-pill {{ $h === $defaultActiveDay ? 'active' : '' }}" data-day="{{ $h }}">
                                <span>{{ $h }}</span>
                                @if ($scheduledCount > 0)
                                    <span class="day-count-badge">{{ $scheduledCount }} Mapel</span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    <!-- Feed List per Hari -->
                    @foreach ($hariAktif as $h)
                        @php
                            $daySlots = $timeSlotsByDay[$h] ?? [];
                        @endphp
                        <div class="harian-day-feed {{ $h === $defaultActiveDay ? 'active' : '' }}" data-day="{{ $h }}">
                            @if (empty($daySlots))
                                <div class="harian-empty-day">
                                    <i class="fas fa-calendar-xmark mb-2" style="font-size: 2rem; opacity: 0.4;"></i>
                                    <div>Hari {{ $h }} tidak memiliki jam pelajaran atau berstatus libur.</div>
                                </div>
                            @else
                                <div class="harian-timeline-list">
                                    @foreach ($daySlots as $jamKe => $sInfo)
                                        @php
                                            $routine = $routinesMap[$h][$jamKe] ?? null;
                                            $schedule = $scheduleMatrix[$h][$jamKe] ?? null;
                                            $isOccupied = !empty($occupiedMatrix[$h][$jamKe]);
                                            $jamMulai = substr($sInfo['mulai'] ?? ($sInfo['jam_mulai'] ?? ''), 0, 5);
                                            $jamSelesai = substr($sInfo['selesai'] ?? ($sInfo['jam_selesai'] ?? ''), 0, 5);
                                        @endphp

                                        @if ($isOccupied)
                                            {{-- Slot ini terisi multi-JP lanjutan dari jam sebelumnya --}}
                                        @elseif ($schedule)
                                            @php
                                                $jpSpan = max(1, (int)$schedule->jam_ke_selesai - (int)$schedule->jam_ke_mulai + 1);
                                            @endphp
                                            <div class="harian-card-item harian-card-scheduled">
                                                <div class="harian-card-header">
                                                    <div class="harian-card-time">
                                                        <span class="harian-jp-badge">JP {{ $schedule->jam_ke_mulai }}@if($jpSpan > 1)-{{ $schedule->jam_ke_selesai }}@endif</span>
                                                        <span class="harian-clock-text">{{ $jamMulai }} - {{ $jamSelesai }} WIB</span>
                                                    </div>
                                                    <span class="badge" style="background: rgba(99,102,241,0.15); color: var(--primary); font-size: 0.74rem; font-weight: 700; padding: 3px 8px; border-radius: 6px;">{{ $jpSpan }} JP</span>
                                                </div>
                                                <div>
                                                    <h4 class="harian-mapel-name">{{ $schedule->nama_mata_pelajaran }}</h4>
                                                    <div class="harian-guru-name">
                                                        <i class="fas fa-user-tie text-muted me-1"></i>
                                                        <span>{{ $schedule->nama_guru ?: 'Guru belum ditentukan' }}</span>
                                                    </div>
                                                    @if ($schedule->ruangan)
                                                        <div class="harian-room-name">
                                                            <i class="fas fa-location-dot text-success me-1"></i>
                                                            <span>{{ $schedule->ruangan }}</span>
                                                        </div>
                                                    @endif
                                                    @if ($routine)
                                                        <div class="harian-conflict-warn">
                                                            <i class="fas fa-triangle-exclamation me-1"></i>
                                                            <span>Bentrok dengan {{ $routine->nama }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="harian-card-actions">
                                                    <button type="button" class="btn btn-sm btn-outline btn-edit-slot"
                                                        data-id="{{ $schedule->id }}"
                                                        data-pembelajaran-id="{{ $schedule->pembelajaran_id }}"
                                                        data-mapel-id="{{ $schedule->mata_pelajaran_id }}"
                                                        data-nama-mapel="{{ $schedule->nama_mata_pelajaran }}"
                                                        data-hari="{{ $schedule->hari }}"
                                                        data-jam-mulai="{{ $schedule->jam_ke_mulai }}"
                                                        data-jam-selesai="{{ $schedule->jam_ke_selesai }}"
                                                        data-ruangan="{{ $schedule->ruangan }}"
                                                        data-keterangan="{{ $schedule->keterangan }}"
                                                        style="padding: 6px 14px; font-size: 0.8rem; border-radius: 6px;">
                                                        <i class="fas fa-pen me-1"></i> Edit
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline btn-hapus-slot"
                                                        data-id="{{ $schedule->id }}"
                                                        data-nama-mapel="{{ $schedule->nama_mata_pelajaran }}"
                                                        data-hari="{{ $schedule->hari }}"
                                                        data-jam="Jam ke-{{ $schedule->jam_ke_mulai }} s/d {{ $schedule->jam_ke_selesai }}"
                                                        style="padding: 6px 14px; font-size: 0.8rem; border-radius: 6px; color: #ef4444; border-color: rgba(239,68,68,0.3);">
                                                        <i class="fas fa-trash me-1"></i> Hapus
                                                    </button>
                                                </div>
                                            </div>
                                        @elseif ($routine)
                                            @php
                                                $rType = $routine->tipe ?? '';
                                                $rColor = ($rType === 'UPACARA') ? '#ef4444' : (($rType === 'PEMBIASAAN') ? '#10b981' : '#f59e0b');
                                            @endphp
                                            <div class="harian-card-item" style="border-left: 4px solid {{ $rColor }};">
                                                <div class="harian-card-header">
                                                    <div class="harian-card-time">
                                                        <span class="harian-jp-badge">JP {{ $jamKe }}</span>
                                                        <span class="harian-clock-text">{{ $jamMulai }} - {{ $jamSelesai }} WIB</span>
                                                    </div>
                                                    <span class="badge" style="background: rgba(255,255,255,0.06); color: {{ $rColor }}; font-size: 0.72rem;">{{ $routine->durasi }}</span>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 700; color: {{ $rColor }}; font-size: 0.9rem; display: flex; align-items: center; gap: 6px;">
                                                        <i class="fas {{ $routine->icon }}"></i>
                                                        <span>{{ $routine->nama }}</span>
                                                    </div>
                                                    <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                                                        Slot kegiatan rutin sekolah terjadwal otomatis.
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <!-- Slot Kosong -->
                                            <div class="harian-card-item" style="border: 1.5px dashed var(--border-color);">
                                                <div class="harian-card-header" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                                                    <div class="harian-card-time">
                                                        <span class="harian-jp-badge">JP {{ $jamKe }}</span>
                                                        <span class="harian-clock-text">{{ $jamMulai }} - {{ $jamSelesai }} WIB</span>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline btn-empty-slot"
                                                        data-hari="{{ $h }}"
                                                        data-jam-ke="{{ $jamKe }}"
                                                        style="padding: 6px 14px; font-size: 0.78rem; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px;">
                                                        <i class="fas fa-plus"></i> Tambah Pelajaran
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- 2. TAMPILAN MODE TABEL (Matriks Mingguan Horizontal Scrollable) -->
                <div id="viewTabelWeekly" class="view-tabel-wrapper">
                    <!-- Hint Scroll Horizontal Khusus Mobile -->
                    <div class="table-scroll-hint">
                        <i class="fas fa-arrows-left-right text-primary"></i>
                        <span>Geser tabel ke samping untuk melihat seluruh hari &rarr;</span>
                    </div>

                    <div class="matrix-scroll-container">
                        <table class="table matrix-table">
                            <thead>
                                <tr style="background: rgba(255,255,255,0.03); border-bottom: 1.5px solid var(--border-color);">
                                    <th class="sticky-jp-col" style="padding: 12px 14px; width: 88px; min-width: 88px; text-align: center; font-weight: 800; color: var(--text-muted); text-transform: uppercase; font-size: 0.72rem;">
                                        Jam / JP
                                    </th>
                                    @foreach ($hariAktif as $h)
                                        <th style="padding: 12px 14px; min-width: 145px; text-align: center; font-weight: 800; color: var(--text-color); font-size: 0.82rem; border-left: 1px solid var(--border-color);">
                                            {{ $h }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $maxSlots = 0;
                                    foreach ($hariAktif as $h) {
                                        $maxSlots = max($maxSlots, count($timeSlotsByDay[$h] ?? []));
                                    }
                                @endphp

                                @for ($jamKe = 1; $jamKe <= $maxSlots; $jamKe++)
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <!-- Kolom Jam Pelajaran (Sticky di Layar Mobile) -->
                                        <td class="sticky-jp-col" style="padding: 10px 8px; text-align: center; vertical-align: middle;">
                                            <div style="font-weight: 800; font-size: 0.84rem; color: var(--text-color);">JP {{ $jamKe }}</div>
                                            @php
                                                $firstDaySlot = $timeSlotsByDay[$hariAktif[0] ?? 'Senin'] ?? [];
                                                $slotInfo = $firstDaySlot[$jamKe] ?? null;
                                            @endphp
                                            @if ($slotInfo)
                                                <div style="font-size: 0.68rem; color: var(--text-muted); margin-top: 2px;">
                                                    {{ substr($slotInfo['mulai'] ?? ($slotInfo['jam_mulai'] ?? ''), 0, 5) }} - {{ substr($slotInfo['selesai'] ?? ($slotInfo['jam_selesai'] ?? ''), 0, 5) }}
                                                </div>
                                            @endif
                                        </td>

                                        <!-- Kolom Per Hari -->
                                        @foreach ($hariAktif as $h)
                                            @php
                                                $daySlots = $timeSlotsByDay[$h] ?? [];
                                                $isSlotExists = isset($daySlots[$jamKe]);
                                                $routine = $routinesMap[$h][$jamKe] ?? null;
                                                $schedule = $scheduleMatrix[$h][$jamKe] ?? null;
                                                $isOccupied = !empty($occupiedMatrix[$h][$jamKe]);
                                            @endphp

                                            @if (!$isSlotExists)
                                                <td style="padding: 8px; border-left: 1px solid var(--border-color); background: rgba(0,0,0,0.15); text-align: center; color: var(--text-muted); font-size: 0.72rem; vertical-align: middle;">
                                                    —
                                                </td>
                                            @elseif ($isOccupied)
                                                {{-- Slot ini terisi oleh pelajaran multi-JP dari jam sebelumnya, lewati perenderan terpisah --}}
                                            @elseif ($schedule)
                                                @php
                                                    $jpSpan = max(1, (int)$schedule->jam_ke_selesai - (int)$schedule->jam_ke_mulai + 1);
                                                @endphp
                                                <td rowspan="{{ $jpSpan }}" style="padding: 8px; border-left: 1px solid var(--border-color); vertical-align: top; background: rgba(99,102,241,0.04);">
                                                    <div class="slot-card-active" style="padding: 10px; border-radius: 8px; border: 1px solid rgba(99,102,241,0.35); background: var(--bg-card); box-shadow: 0 4px 12px rgba(0,0,0,0.1); height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
                                                        <div>
                                                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 4px; margin-bottom: 4px;">
                                                                <strong style="font-size: 0.82rem; color: var(--text-color); line-height: 1.3;" title="{{ $schedule->nama_mata_pelajaran }}">
                                                                    {{ $schedule->nama_mata_pelajaran }}
                                                                </strong>
                                                                <span class="badge" style="background: rgba(99,102,241,0.15); color: var(--primary); font-size: 0.68rem; padding: 2px 6px; border-radius: 4px; flex-shrink: 0;">
                                                                    {{ $jpSpan }} JP
                                                                </span>
                                                            </div>

                                                            <div style="font-size: 0.74rem; color: var(--text-muted); margin-bottom: 4px; display: flex; align-items: center; gap: 5px;">
                                                                <i class="fas fa-user-tie" style="font-size: 0.68rem;"></i>
                                                                <span title="{{ $schedule->nama_guru }}">{{ $schedule->nama_guru ?: 'Guru belum ada' }}</span>
                                                            </div>

                                                            @if ($schedule->ruangan)
                                                                <div style="font-size: 0.72rem; color: #10b981; display: flex; align-items: center; gap: 5px;">
                                                                    <i class="fas fa-location-dot" style="font-size: 0.68rem;"></i>
                                                                    <span>{{ $schedule->ruangan }}</span>
                                                                </div>
                                                            @endif

                                                            @if ($routine)
                                                                <div style="margin-top: 4px; font-size: 0.68rem; color: #ef4444; background: rgba(239,68,68,0.1); border-radius: 4px; padding: 2px 5px; display: inline-flex; align-items: center; gap: 3px;">
                                                                    <i class="fas fa-triangle-exclamation"></i>
                                                                    <span>Bentrok {{ $routine->nama }}</span>
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <!-- Tombol Aksi Slot (Touch-Friendly & Responsif) -->
                                                        <div style="display: flex; justify-content: flex-end; align-items: center; gap: 8px; margin-top: 8px; padding-top: 6px; border-top: 1px dashed var(--border-color);">
                                                            <button type="button" class="btn-slot-action btn-edit-slot"
                                                                data-id="{{ $schedule->id }}"
                                                                data-pembelajaran-id="{{ $schedule->pembelajaran_id }}"
                                                                data-mapel-id="{{ $schedule->mata_pelajaran_id }}"
                                                                data-nama-mapel="{{ $schedule->nama_mata_pelajaran }}"
                                                                data-hari="{{ $schedule->hari }}"
                                                                data-jam-mulai="{{ $schedule->jam_ke_mulai }}"
                                                                data-jam-selesai="{{ $schedule->jam_ke_selesai }}"
                                                                data-ruangan="{{ $schedule->ruangan }}"
                                                                data-keterangan="{{ $schedule->keterangan }}"
                                                                title="Edit Slot Ini"
                                                                aria-label="Edit Slot Ini">
                                                                <i class="fas fa-pen"></i>
                                                            </button>
                                                            <button type="button" class="btn-slot-action btn-slot-delete btn-hapus-slot"
                                                                data-id="{{ $schedule->id }}"
                                                                data-nama-mapel="{{ $schedule->nama_mata_pelajaran }}"
                                                                data-hari="{{ $schedule->hari }}"
                                                                data-jam="Jam ke-{{ $schedule->jam_ke_mulai }} s/d {{ $schedule->jam_ke_selesai }}"
                                                                title="Hapus Slot Ini"
                                                                aria-label="Hapus Slot Ini">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </td>
                                            @elseif ($routine)
                                                @php
                                                    $rType = $routine->tipe ?? '';
                                                    $rColor = ($rType === 'UPACARA') ? '#ef4444' : (($rType === 'PEMBIASAAN') ? '#10b981' : '#f59e0b');
                                                    $rBg = ($rType === 'UPACARA') ? 'rgba(239,68,68,0.06)' : (($rType === 'PEMBIASAAN') ? 'rgba(16,185,129,0.06)' : 'rgba(245,158,11,0.06)');
                                                    $rBadgeBg = ($rType === 'UPACARA') ? 'rgba(239,68,68,0.12)' : (($rType === 'PEMBIASAAN') ? 'rgba(16,185,129,0.12)' : 'rgba(245,158,11,0.12)');
                                                @endphp
                                                <td style="padding: 8px; border-left: 1px solid var(--border-color); background: {{ $rBg }}; text-align: center; vertical-align: middle;">
                                                    <div style="padding: 8px 10px; border-radius: 8px; border: 1px solid {{ $rColor }}; background: {{ $rBadgeBg }}; color: {{ $rColor }};">
                                                        <i class="fas {{ $routine->icon }} me-1"></i>
                                                        <strong style="font-size: 0.78rem;">{{ $routine->nama }}</strong>
                                                        <div style="font-size: 0.68rem; opacity: 0.85;">{{ $routine->durasi }}</div>
                                                    </div>
                                                </td>
                                            @else
                                                <!-- Slot Kosong: Tombol Tambah Cepat -->
                                                <td style="padding: 8px; border-left: 1px solid var(--border-color); vertical-align: middle;">
                                                    <button type="button" class="btn-empty-slot"
                                                        data-hari="{{ $h }}"
                                                        data-jam-ke="{{ $jamKe }}"
                                                        style="width: 100%; min-height: 52px; background: transparent; border: 1.5px dashed var(--border-color); border-radius: 8px; color: var(--text-muted); font-size: 0.75rem; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; transition: all 0.2s ease;">
                                                        <i class="fas fa-plus" style="font-size: 0.8rem; opacity: 0.6;"></i>
                                                        <span>Kosong</span>
                                                    </button>
                                                </td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Form Input / Edit Slot Jadwal Manual (z-index 99999 !important) -->
    <div id="modalSlotManual" class="sae-modal"
        data-route-store="{{ route('dashboard.jadwal-kbm.manual.store-slot') }}"
        data-route-update="{{ url('/dashboard/master-data/jadwal-kbm/manual/update-slot') }}"
        data-route-delete="{{ url('/dashboard/master-data/jadwal-kbm/manual/delete-slot') }}"
        data-route-clear="{{ route('dashboard.jadwal-kbm.manual.clear-rombel') }}"
        data-route-conflict="{{ route('dashboard.jadwal-kbm.check-conflict') }}"
        data-slots="{{ json_encode($timeSlotsByDay) }}"
        data-routines="{{ json_encode($routinesMap) }}"
        data-rombel-id="{{ $activeRombel ? $activeRombel->rombongan_belajar_id : '' }}"
        data-rombel-name="{{ $activeRombel ? $activeRombel->nama : '' }}"
        style="display: none; position: fixed; inset: 0; z-index: 99999 !important; background: rgba(0,0,0,0.6); align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 540px; max-height: 90vh; overflow-y: auto; margin: 16px; padding: 24px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.4);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 id="modalSlotTitle" style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--text-color);">
                    <i class="fas fa-calendar-plus text-primary me-2"></i> Jadwalkan Pelajaran
                </h3>
                <button type="button" id="btnTutupModalSlot" style="background: transparent; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formSlotManual">
                @csrf
                <input type="hidden" id="slotId" name="slot_id" value="">
                <input type="hidden" id="slotRombelId" name="rombongan_belajar_id" value="{{ $activeRombel ? $activeRombel->rombongan_belajar_id : '' }}">

                <!-- Pilih Mata Pelajaran & Guru -->
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="slotPembelajaranId" style="display: block; font-size: 0.84rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                        Mata Pelajaran &amp; Guru Pengampu <span style="color: #ef4444;">*</span>
                    </label>
                    <select id="slotPembelajaranId" name="pembelajaran_id" required class="form-control" style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); font-size: 0.88rem;">
                        <option value="">-- Pilih Mata Pelajaran --</option>
                        @if ($activeRombel)
                            @foreach ($pembelajaranData as $p)
                                <option value="{{ $p->pembelajaran_id }}"
                                    data-ptk-id="{{ $p->ptk_id }}"
                                    data-target-jp="{{ $p->target_jp }}"
                                    data-remaining-jp="{{ $p->remaining_jp }}">
                                    {{ $p->nama_mata_pelajaran }} — {{ $p->nama_guru ?: 'Tanpa Guru' }} (Sisa: {{ $p->remaining_jp }} JP)
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Pilihan Hari & Ruangan -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label for="slotHari" style="display: block; font-size: 0.84rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                            Hari <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="slotHari" name="hari" required class="form-control" style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); font-size: 0.88rem;">
                            @foreach ($hariAktif as $h)
                                <option value="{{ $h }}">{{ $h }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="slotRuangan" style="display: block; font-size: 0.84rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                            Ruangan / Lab
                        </label>
                        <input type="text" id="slotRuangan" name="ruangan" placeholder="Contoh: R. Kelas / Lab 1" value="{{ $activeRombel ? $activeRombel->nama : '' }}" class="form-control" style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); font-size: 0.88rem;">
                    </div>
                </div>

                <!-- Pilihan Jam Ke (Mulai & Selesai) -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label for="slotJamKeMulai" style="display: block; font-size: 0.84rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                            Jam Ke-Mulai <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="slotJamKeMulai" name="jam_ke_mulai" required class="form-control" style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); font-size: 0.88rem;">
                            @for ($i = 1; $i <= 16; $i++)
                                <option value="{{ $i }}">Jam Ke-{{ $i }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="slotJamKeSelesai" style="display: block; font-size: 0.84rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                            Jam Ke-Selesai <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="slotJamKeSelesai" name="jam_ke_selesai" required class="form-control" style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); font-size: 0.88rem;">
                            @for ($i = 1; $i <= 16; $i++)
                                <option value="{{ $i }}">Jam Ke-{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <!-- Durasi Info -->
                <div id="slotDurasiInfo" style="padding: 8px 12px; border-radius: 8px; background: rgba(99,102,241,0.08); border: 1px dashed rgba(99,102,241,0.3); font-size: 0.78rem; color: var(--primary); margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
                    <span>Alokasi Jam: <strong id="slotDurasiJp">1 JP</strong></span>
                    <span id="slotDurasiWaktu"></span>
                </div>

                <!-- Alert Live Anti-Bentrok -->
                <div id="conflictAlertBoxManual" style="display: none; padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; font-size: 0.8rem;"></div>

                <!-- Opsi Timpa / Ganti Jadwal Kelas Jika Sudah Terisi -->
                <div id="boxOverwriteSlot" style="margin-bottom: 16px; padding: 10px 14px; background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.25); border-radius: 8px; display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" id="chkOverwriteSlot" name="overwrite" value="1" style="width: 17px; height: 17px; cursor: pointer; accent-color: #f59e0b;">
                    <label for="chkOverwriteSlot" style="margin: 0; font-size: 0.82rem; color: var(--text-color); cursor: pointer; line-height: 1.4;">
                        <strong>Ganti / Timpa jadwal kelas ini</strong> jika jam yang dipilih telah terisi oleh pelajaran lain.
                    </label>
                </div>

                <!-- Keterangan Opsional -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="slotKeterangan" style="display: block; font-size: 0.84rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                        Keterangan Tambahan (Opsional)
                    </label>
                    <input type="text" id="slotKeterangan" name="keterangan" placeholder="Contoh: Praktik Bengkel / Teori" class="form-control" style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); font-size: 0.88rem;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                    <button type="button" id="btnBatalSlot" class="btn btn-outline" style="padding: 8px 16px;">Batal</button>
                    <button type="submit" id="btnSimpanSlot" class="btn btn-primary" style="padding: 8px 20px;">
                        <i class="fas fa-save me-1"></i> Simpan Slot
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/jadwal-kbm-manual.js') }}?v={{ file_exists(public_path('js/jadwal-kbm-manual.js')) ? filemtime(public_path('js/jadwal-kbm-manual.js')) : time() }}"></script>
@endpush
