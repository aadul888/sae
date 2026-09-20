@extends('layouts.dashboard')

@section('title', 'Kedisiplinan Siswa (Tata Tertib, Poin, Pembinaan & Pemanggilan) — SAE')
@section('dash_title', 'Kedisiplinan Siswa')

@section('content')
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(239,68,68,0.12); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Kedisiplinan Siswa
                </h2>
                <p style="margin: 2px 0 0 0; font-size: 0.82rem; color: var(--text-muted);">
                    Tata tertib sekolah, buku saku poin pelanggaran, sesi pembinaan konseling, pemanggilan orang tua, dan rekapitulasi.
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($canCreate)
                <button type="button" class="btn btn-outline" id="btnOpenTataTertibModal" title="Tambah Aturan Tata Tertib" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;">
                    <i class="fas fa-book-bookmark text-primary"></i>
                </button>
                <button type="button" class="btn btn-primary" id="btnOpenPoinModal" title="Catat Pelanggaran Poin Siswa" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; background: #ef4444; border-color: #ef4444;">
                    <i class="fas fa-triangle-exclamation"></i>
                </button>
                <button type="button" class="btn btn-outline" id="btnOpenPembinaanModal" title="Catat Sesi Pembinaan Konseling" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;">
                    <i class="fas fa-user-doctor text-success"></i>
                </button>
                <button type="button" class="btn btn-outline" id="btnOpenPanggilanModal" title="Terbitkan Surat Panggilan Wali" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;">
                    <i class="fas fa-envelope-open-text text-warning"></i>
                </button>
            @endif
        </div>
    </div>

    <!-- 2. Stat Grid Baku SAE -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: #2563eb;">
                <i class="fas fa-list-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_aturan'] ?? 0) }}</div>
                <div class="dash-stat-label">Aturan Tata Tertib</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: #ef4444;">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_pelanggaran'] ?? 0) }}</div>
                <div class="dash-stat-label">Kasus Pelanggaran</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-user-doctor"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_pembinaan'] ?? 0) }}</div>
                <div class="dash-stat-label">Sesi Pembinaan</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_panggilan'] ?? 0) }}</div>
                <div class="dash-stat-label">Panggilan Ortu</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(168,85,247,0.12); color: #a855f7;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_pending'] ?? 0) }}</div>
                <div class="dash-stat-label">Menunggu Tindak Lanjut</div>
            </div>
        </div>
    </div>

    <!-- 3. Navigation Tabs Wrapper -->
    <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
        <div class="periode-nav-desktop" style="flex-wrap: wrap;">
            <a href="{{ route('dashboard.kesiswaan.kedisiplinan.index', ['tab' => 'tata_tertib']) }}"
                class="periode-nav-tab {{ $activeTab === 'tata_tertib' ? 'active' : '' }}">
                <i class="fas fa-book-bookmark"></i> Tata Tertib
            </a>
            <a href="{{ route('dashboard.kesiswaan.kedisiplinan.index', ['tab' => 'poin']) }}"
                class="periode-nav-tab {{ $activeTab === 'poin' ? 'active' : '' }}">
                <i class="fas fa-triangle-exclamation"></i> Poin
            </a>
            <a href="{{ route('dashboard.kesiswaan.kedisiplinan.index', ['tab' => 'riwayat']) }}"
                class="periode-nav-tab {{ $activeTab === 'riwayat' ? 'active' : '' }}">
                <i class="fas fa-clock-rotate-left"></i> Riwayat
            </a>
            <a href="{{ route('dashboard.kesiswaan.kedisiplinan.index', ['tab' => 'pembinaan']) }}"
                class="periode-nav-tab {{ $activeTab === 'pembinaan' ? 'active' : '' }}">
                <i class="fas fa-user-doctor"></i> Pembinaan
            </a>
            <a href="{{ route('dashboard.kesiswaan.kedisiplinan.index', ['tab' => 'pemanggilan']) }}"
                class="periode-nav-tab {{ $activeTab === 'pemanggilan' ? 'active' : '' }}">
                <i class="fas fa-envelope-open-text"></i> Pemanggilan Wali
            </a>
            <a href="{{ route('dashboard.kesiswaan.kedisiplinan.index', ['tab' => 'tindak_lanjut']) }}"
                class="periode-nav-tab {{ $activeTab === 'tindak_lanjut' ? 'active' : '' }}">
                <i class="fas fa-scale-balanced"></i> Tindak Lanjut
            </a>
            <a href="{{ route('dashboard.kesiswaan.kedisiplinan.index', ['tab' => 'rekap']) }}"
                class="periode-nav-tab {{ $activeTab === 'rekap' ? 'active' : '' }}">
                <i class="fas fa-chart-pie"></i> Rekapitulasi
            </a>
        </div>
    </div>

    <!-- 4. Toolbar & Filter Standar SAE -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <div class="toolbar-entries">
                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                    <select id="perPageSelect" class="per-page-select">
                        @foreach ([10, 15, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ ($perPage ?? 25) == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </div>

                @if ($activeTab === 'riwayat' || $activeTab === 'poin')
                    <select id="filterStatus" class="toolbar-filter-select">
                        <option value="">Semua Status Tindak Lanjut</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="proses" {{ $status === 'proses' ? 'selected' : '' }}>Proses</option>
                        <option value="selesai" {{ $status === 'selesai' ? 'selected' : '' }}>Selesai</option>
                    </select>
                @endif

                @if (!empty($q) || !empty($status) || !empty($kategori))
                    <a href="{{ route('dashboard.kesiswaan.kedisiplinan.index', ['tab' => $activeTab]) }}"
                        class="btn btn-outline" style="padding: 7px 12px; font-size: 0.84rem;"
                        title="Reset filter">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>

            <div class="live-search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearch" placeholder="Cari siswa / nomor / pelanggaran..." value="{{ $q ?? '' }}" autocomplete="off">
                <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 5. Content Sesuai Tab -->
    @if ($activeTab === 'tata_tertib')
        <!-- TAB 1: MASTER TATA TERTIB -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 100px;">Kode</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 150px;">Kategori</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Uraian Tata Tertib &amp; Pelanggaran</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">Bobot Poin</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 220px;">Sanksi Rekomendasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tataTertibList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-family: monospace; font-weight: 700; color: var(--primary);">{{ $item->kode }}</td>
                            <td style="padding: 12px 16px;">
                                @php
                                    $katColor = match ($item->kategori) {
                                        'kerapian' => 'badge-outline',
                                        'kehadiran' => 'badge-primary',
                                        'perilaku' => 'badge-warning',
                                        'larangan_berat' => 'badge-danger',
                                        default => 'badge-outline',
                                    };
                                @endphp
                                <span class="badge {{ $katColor }}">{{ strtoupper(str_replace('_', ' ', $item->kategori)) }}</span>
                            </td>
                            <td style="padding: 12px 16px; font-weight: 700; color: var(--text-color);">{{ $item->nama_aturan }}</td>
                            <td style="padding: 12px 16px; text-align: center; font-weight: 800; color: #ef4444; font-size: 1.05rem;">
                                +{{ $item->bobot_poin }}
                            </td>
                            <td style="padding: 12px 16px; font-size: 0.85rem; color: var(--text-muted);">{{ $item->sanksi_rekomendasi ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                <i class="fas fa-folder-open" style="font-size: 2.2rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                Belum ada aturan tata tertib yang cocok dengan pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    @elseif ($activeTab === 'poin' || $activeTab === 'riwayat' || $activeTab === 'tindak_lanjut')
        <!-- TAB 2 & 3 & 6: RIWAYAT PELANGGARAN -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 110px;">Tanggal</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Siswa</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jenis Pelanggaran</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 80px;">Poin</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">Pelapor</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px; text-align: center;">Status Tindak Lanjut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riwayatList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-size: 0.85rem;">{{ date('d/m/Y', strtotime($item->tanggal_kejadian)) }}</td>
                            <td style="padding: 12px 16px; font-weight: 700; color: var(--text-color);">
                                {{ $item->siswa?->nama ?: 'Siswa #' . $item->peserta_didik_id }}
                                <div style="font-size: 0.75rem; color: var(--text-muted);">NISN: {{ $item->siswa?->nisn ?: '-' }}</div>
                            </td>
                            <td style="padding: 12px 16px; font-size: 0.85rem;">
                                <strong>{{ $item->aturan?->nama_aturan ?: 'Pelanggaran Khusus' }}</strong>
                                @if ($item->keterangan)
                                    <div style="font-size: 0.78rem; color: var(--text-muted);">Ket: {{ $item->keterangan }}</div>
                                @endif
                            </td>
                            <td style="padding: 12px 16px; text-align: center; font-weight: 800; color: #ef4444;">
                                +{{ $item->poin }}
                            </td>
                            <td style="padding: 12px 16px; font-size: 0.82rem; color: var(--text-muted);">{{ $item->pelapor_nama ?: 'Guru/Piket' }}</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                @php
                                    $stColor = match ($item->status_tindak_lanjut) {
                                        'selesai' => 'badge-success',
                                        'proses' => 'badge-warning',
                                        default => 'badge-danger',
                                    };
                                @endphp
                                <span class="badge {{ $stColor }}">{{ strtoupper($item->status_tindak_lanjut) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                <i class="fas fa-folder-open" style="font-size: 2.2rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                Belum ada riwayat pelanggaran poin siswa yang cocok dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($riwayatList->hasPages())
            <div class="custom-pagination" style="margin-bottom: 24px;">
                @if ($riwayatList->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $riwayatList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $riwayatList->currentPage();
                    $last = $riwayatList->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $riwayatList->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2) <span class="page-info">&hellip;</span> @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $riwayatList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                    <a href="{{ $riwayatList->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($riwayatList->hasMorePages())
                    <a href="{{ $riwayatList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif

    @elseif ($activeTab === 'pembinaan')
        <!-- TAB 4: SESI PEMBINAAN KONSELING -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 110px;">Tanggal</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Siswa</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 160px;">Bentuk Pembinaan</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Hasil / Catatan Konseling</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 150px;">Pembina</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 110px; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pembinaanList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-size: 0.85rem;">{{ date('d/m/Y', strtotime($item->tanggal_pembinaan)) }}</td>
                            <td style="padding: 12px 16px; font-weight: 700; color: var(--text-color);">{{ $item->siswa?->nama ?: '-' }}</td>
                            <td style="padding: 12px 16px; font-size: 0.85rem;"><span class="badge badge-primary">{{ $item->bentuk_pembinaan }}</span></td>
                            <td style="padding: 12px 16px; font-size: 0.85rem;">{{ $item->hasil_pembinaan }}</td>
                            <td style="padding: 12px 16px; font-size: 0.82rem; color: var(--text-muted);">{{ $item->guruBk?->nama ?: ($item->waliKelas?->nama ?: 'Guru BK / Wali') }}</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="badge {{ $item->status === 'selesai' ? 'badge-success' : 'badge-warning' }}">{{ strtoupper($item->status) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                <i class="fas fa-folder-open" style="font-size: 2.2rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                Belum ada sesi pembinaan konseling yang cocok dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pembinaanList->hasPages())
            <div class="custom-pagination" style="margin-bottom: 24px;">
                @if ($pembinaanList->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $pembinaanList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $pembinaanList->currentPage();
                    $last = $pembinaanList->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $pembinaanList->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2) <span class="page-info">&hellip;</span> @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $pembinaanList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                    <a href="{{ $pembinaanList->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($pembinaanList->hasMorePages())
                    <a href="{{ $pembinaanList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif

    @elseif ($activeTab === 'pemanggilan')
        <!-- TAB 5: SURAT PEMANGGILAN WALI MURID -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 160px;">No. Surat</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Siswa</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 170px;">Jadwal Kehadiran Ortu</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Alasan Pemanggilan</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">Menghadap Ke</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 90px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pemanggilanList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-family: monospace; font-size: 0.82rem; font-weight: 700; color: var(--primary);">{{ $item->nomor_surat }}</td>
                            <td style="padding: 12px 16px; font-weight: 700; color: var(--text-color);">{{ $item->siswa?->nama ?: '-' }}</td>
                            <td style="padding: 12px 16px; font-size: 0.85rem;">
                                <strong>{{ date('d/m/Y', strtotime($item->tanggal_hadir)) }}</strong> pk {{ substr($item->jam_hadir, 0, 5) }}
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Tempat: {{ $item->tempat }}</div>
                            </td>
                            <td style="padding: 12px 16px; font-size: 0.85rem;">{{ $item->alasan }}</td>
                            <td style="padding: 12px 16px; font-size: 0.85rem;">{{ $item->menghadap_ke }}</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <div class="table-actions" style="display: flex; gap: 4px; justify-content: center;">
                                    <a href="{{ route('dashboard.kesiswaan.kedisiplinan.panggilan-wali.cetak', $item->id) }}" target="_blank"
                                        class="btn-icon" title="Cetak Surat Pemanggilan">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                <i class="fas fa-folder-open" style="font-size: 2.2rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                Belum ada surat pemanggilan wali murid yang cocok dengan pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pemanggilanList->hasPages())
            <div class="custom-pagination" style="margin-bottom: 24px;">
                @if ($pemanggilanList->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $pemanggilanList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $pemanggilanList->currentPage();
                    $last = $pemanggilanList->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $pemanggilanList->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2) <span class="page-info">&hellip;</span> @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $pemanggilanList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                    <a href="{{ $pemanggilanList->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($pemanggilanList->hasMorePages())
                    <a href="{{ $pemanggilanList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif

    @elseif ($activeTab === 'rekap')
        <!-- TAB 7: REKAPITULASI POIN SISWA -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 70px; text-align: center;">Peringkat</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Siswa</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">NISN</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 120px;">Rombel</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">Total Kasus</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 130px;">Akumulasi Poin</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 230px;">Tindakan Rekomendasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rekapSiswa as $idx => $sw)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-weight: 800; color: var(--text-muted); text-align: center;">#{{ $idx + 1 }}</td>
                            <td style="padding: 12px 16px; font-weight: 700; color: var(--text-color);">{{ $sw->nama }}</td>
                            <td style="padding: 12px 16px; font-family: monospace; font-size: 0.82rem;">{{ $sw->nisn ?: '-' }}</td>
                            <td style="padding: 12px 16px; font-size: 0.85rem;">{{ $sw->rombel_nama ?: '-' }}</td>
                            <td style="padding: 12px 16px; text-align: center; font-weight: 700;">{{ $sw->total_kasus }}x</td>
                            <td style="padding: 12px 16px; text-align: center; font-weight: 800; font-size: 1.1rem; color: {{ $sw->total_poin >= 50 ? '#ef4444' : ($sw->total_poin >= 25 ? '#f59e0b' : 'var(--text-color)') }};">
                                {{ $sw->total_poin }}
                            </td>
                            <td style="padding: 12px 16px;">
                                @if ($sw->total_poin >= 100)
                                    <span class="badge badge-danger">Dikeluarkan / SP 3</span>
                                @elseif ($sw->total_poin >= 50)
                                    <span class="badge badge-danger">Panggilan Ortu &amp; Skorsing (SP 2)</span>
                                @elseif ($sw->total_poin >= 25)
                                    <span class="badge badge-warning">Peringatan Tertulis &amp; SP 1</span>
                                @else
                                    <span class="badge badge-primary">Pembinaan Wali Kelas &amp; BK</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                <i class="fas fa-folder-open" style="font-size: 2.2rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                Tidak ada rekaman pelanggaran poin. Seluruh siswa bersih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <!-- MODAL 1: ATURAN TATA TERTIB -->
    <div id="modalTataTertib" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 500px; padding: 24px; border-radius: 12px; margin: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin: 0;">Tambah Aturan Tata Tertib</h3>
                <button type="button" class="close-modal" data-target="#modalTataTertib" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;"><i class="fas fa-times"></i></button>
            </div>
            <form id="formTataTertib" method="POST" action="{{ route('dashboard.kesiswaan.kedisiplinan.tatib.store') }}">
                @csrf
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Kode Aturan <span class="text-danger">*</span></label>
                        <input type="text" name="kode" class="form-control" placeholder="Contoh: TT17" required style="width: 100%; border-radius: 8px;">
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Kategori <span class="text-danger">*</span></label>
                        <select name="kategori" class="form-control" required style="width: 100%; border-radius: 8px;">
                            <option value="kerapian">Kerapian</option>
                            <option value="kehadiran">Kehadiran</option>
                            <option value="perilaku">Perilaku</option>
                            <option value="larangan_berat">Larangan Berat</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Uraian Pelanggaran <span class="text-danger">*</span></label>
                    <input type="text" name="nama_aturan" class="form-control" placeholder="Tuliskan nama pelanggaran" required style="width: 100%; border-radius: 8px;">
                </div>
                <div style="display: grid; grid-template-columns: 100px 1fr; gap: 12px; margin-bottom: 18px;">
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Poin <span class="text-danger">*</span></label>
                        <input type="number" name="bobot_poin" class="form-control" value="5" min="1" required style="width: 100%; border-radius: 8px;">
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Sanksi Rekomendasi</label>
                        <input type="text" name="sanksi_rekomendasi" class="form-control" placeholder="Contoh: Teguran lisan" style="width: 100%; border-radius: 8px;">
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-outline close-modal" data-target="#modalTataTertib" style="border-radius: 8px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: 8px;"><i class="fas fa-save"></i> Simpan Aturan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: CATAT PELANGGARAN POIN -->
    <div id="modalPoin" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 520px; padding: 24px; border-radius: 12px; margin: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin: 0;">Catat Pelanggaran Poin Siswa</h3>
                <button type="button" class="close-modal" data-target="#modalPoin" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;"><i class="fas fa-times"></i></button>
            </div>
            <form id="formPoin" method="POST" action="{{ route('dashboard.kesiswaan.kedisiplinan.pelanggaran.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Pilih Siswa <span class="text-danger">*</span></label>
                    <select name="peserta_didik_id" class="form-control" required style="width: 100%; border-radius: 8px;">
                        <option value="">-- Cari dan Pilih Siswa --</option>
                        @foreach ($siswaList as $sw)
                            <option value="{{ $sw->peserta_didik_id }}">{{ $sw->nama }} (NISN: {{ $sw->nisn ?: '-' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Pilih Pelanggaran Tata Tertib <span class="text-danger">*</span></label>
                    <select name="tata_tertib_id" class="form-control" required style="width: 100%; border-radius: 8px;">
                        <option value="">-- Pilih Jenis Pelanggaran --</option>
                        @foreach ($tataTertibList as $tt)
                            <option value="{{ $tt->id }}">[{{ $tt->kode }}] {{ $tt->nama_aturan }} (+{{ $tt->bobot_poin }} Poin)</option>
                        @endforeach
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tanggal Kejadian <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_kejadian" class="form-control" value="{{ date('Y-m-d') }}" required style="width: 100%; border-radius: 8px;">
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tempat Kejadian</label>
                        <input type="text" name="tempat_kejadian" class="form-control" placeholder="Contoh: Depan Gerbang / Kantin" style="width: 100%; border-radius: 8px;">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Keterangan / Catatan Tambahan</label>
                    <input type="text" name="keterangan" class="form-control" placeholder="Rincian kronologi singkat" style="width: 100%; border-radius: 8px;">
                </div>
                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Unggah Foto Bukti (Opsional)</label>
                    <input type="file" name="foto_bukti" class="form-control" accept="image/*" style="width: 100%; border-radius: 8px;">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-outline close-modal" data-target="#modalPoin" style="border-radius: 8px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: 8px; background: #ef4444; border-color: #ef4444;"><i class="fas fa-save"></i> Catat Poin</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: PEMBINAAN SISWA -->
    <div id="modalPembinaan" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 520px; padding: 24px; border-radius: 12px; margin: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin: 0;">Catat Sesi Pembinaan Konseling</h3>
                <button type="button" class="close-modal" data-target="#modalPembinaan" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;"><i class="fas fa-times"></i></button>
            </div>
            <form id="formPembinaan" method="POST" action="{{ route('dashboard.kesiswaan.kedisiplinan.pembinaan.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Pilih Siswa <span class="text-danger">*</span></label>
                    <select name="peserta_didik_id" class="form-control" required style="width: 100%; border-radius: 8px;">
                        <option value="">-- Cari dan Pilih Siswa --</option>
                        @foreach ($siswaList as $sw)
                            <option value="{{ $sw->peserta_didik_id }}">{{ $sw->nama }} (NISN: {{ $sw->nisn ?: '-' }})</option>
                        @endforeach
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tanggal Pembinaan <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_pembinaan" class="form-control" value="{{ date('Y-m-d') }}" required style="width: 100%; border-radius: 8px;">
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Bentuk Pembinaan <span class="text-danger">*</span></label>
                        <select name="bentuk_pembinaan" class="form-control" required style="width: 100%; border-radius: 8px;">
                            <option value="Konseling Individual BK">Konseling Individual BK</option>
                            <option value="Peringatan Lisan & Refleksi">Peringatan Lisan & Refleksi</option>
                            <option value="Surat Perjanjian Bermaterai">Surat Perjanjian Bermaterai</option>
                            <option value="Restorative Justice / Kerja Sosial">Restorative Justice / Kerja Sosial</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Hasil / Komitmen Siswa <span class="text-danger">*</span></label>
                    <textarea name="hasil_pembinaan" class="form-control" rows="3" placeholder="Tuliskan hasil sesi konseling dan komitmen siswa..." required style="width: 100%; border-radius: 8px;"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px;">
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Status Kasus <span class="text-danger">*</span></label>
                        <select name="status" class="form-control" required style="width: 100%; border-radius: 8px;">
                            <option value="selesai">Selesai / Tuntas</option>
                            <option value="proses">Masih Dalam Pemantauan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">File Surat Perjanjian (Jika Ada)</label>
                        <input type="file" name="surat_perjanjian_file" class="form-control" accept=".pdf,image/*" style="width: 100%; border-radius: 8px;">
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-outline close-modal" data-target="#modalPembinaan" style="border-radius: 8px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: 8px;"><i class="fas fa-save"></i> Simpan Pembinaan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: PEMANGGILAN ORANG TUA / WALI -->
    <div id="modalPanggilan" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 520px; padding: 24px; border-radius: 12px; margin: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin: 0;">Terbitkan Surat Panggilan Orang Tua</h3>
                <button type="button" class="close-modal" data-target="#modalPanggilan" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;"><i class="fas fa-times"></i></button>
            </div>
            <form id="formPanggilan" method="POST" action="{{ route('dashboard.kesiswaan.kedisiplinan.panggilan-wali.store') }}">
                @csrf
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Pilih Siswa <span class="text-danger">*</span></label>
                    <select name="peserta_didik_id" class="form-control" required style="width: 100%; border-radius: 8px;">
                        <option value="">-- Cari dan Pilih Siswa --</option>
                        @foreach ($siswaList as $sw)
                            <option value="{{ $sw->peserta_didik_id }}">{{ $sw->nama }} (NISN: {{ $sw->nisn ?: '-' }})</option>
                        @endforeach
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tanggal Surat <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_surat" class="form-control" value="{{ date('Y-m-d') }}" required style="width: 100%; border-radius: 8px;">
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tanggal Hadir Ortu <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_hadir" class="form-control" value="{{ date('Y-m-d', strtotime('+1 day')) }}" required style="width: 100%; border-radius: 8px;">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 120px 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Pukul / Jam <span class="text-danger">*</span></label>
                        <input type="time" name="jam_hadir" class="form-control" value="08:30" required style="width: 100%; border-radius: 8px;">
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Ruang / Tempat <span class="text-danger">*</span></label>
                        <input type="text" name="tempat" class="form-control" value="Ruang Bimbingan Konseling / Kesiswaan" required style="width: 100%; border-radius: 8px;">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Alasan Pemanggilan <span class="text-danger">*</span></label>
                    <input type="text" name="alasan" class="form-control" placeholder="Contoh: Akumulasi poin pelanggaran mencapai 50 poin" required style="width: 100%; border-radius: 8px;">
                </div>
                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Menghadap Ke <span class="text-danger">*</span></label>
                    <input type="text" name="menghadap_ke" class="form-control" value="Guru BK / Waka Bidang Kesiswaan" required style="width: 100%; border-radius: 8px;">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-outline close-modal" data-target="#modalPanggilan" style="border-radius: 8px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: 8px;"><i class="fas fa-paper-plane"></i> Terbitkan Surat</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/kesiswaan-kedisiplinan.js') }}"></script>
@endpush
