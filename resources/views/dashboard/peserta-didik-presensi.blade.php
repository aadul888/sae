@extends('layouts.dashboard')

@section('title', 'Riwayat Presensi Harian — SAE')
@section('dash_title', 'Riwayat Presensi')

@section('content')
    <!-- 1. Header Banner & Action Buttons (Sesuai Standar Modul SAE) -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-calendar-check text-primary me-2"></i> Riwayat Presensi Harian
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Catatan kehadiran, ketepatan waktu, bukti snapshot terminal, dan cetak laporan resmi.
            </p>
        </div>
        <div class="dash-banner-actions">
            <a href="{{ route('dashboard.peserta-didik.presensi.cetak', request()->all()) }}" target="_blank"
                class="btn btn-outline" style="padding: 9px 16px; font-size: 0.85rem;" title="Cetak Rekapitulasi Presensi Resmi">
                <i class="fas fa-print me-1"></i> Cetak Laporan
            </a>
            @if ($canCreate)
                <a href="{{ route('dashboard.peserta-didik.izin.index') }}"
                    class="btn btn-primary" style="padding: 9px 16px; font-size: 0.85rem; font-weight: 700;" title="Buka Modul Surat Izin & Sakit">
                    <i class="fas fa-envelope-open-text me-1"></i> Ajukan Surat Izin
                </a>
            @endif
        </div>
    </div>

    <!-- 2. Stat Grid Baku SAE (Konsisten dengan Modul Peserta Didik Aktif) -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                <i class="fas fa-percent"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem; color: #10b981;">
                    {{ $stats['persen'] }}%
                </div>
                <div class="dash-stat-label">Kehadiran ({{ $stats['total_hadir'] }}/{{ $stats['hari_efektif_berjalan'] }} Hari Efektif)</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($stats['hadir'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Hadir Tepat Waktu</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($stats['terlambat'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Terlambat Masuk</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.15); color: #3b82f6;">
                <i class="fas fa-file-lines"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($stats['izin'] + $stats['sakit'] + $stats['dispen'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Izin / Sakit / Dispen</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                <i class="fas fa-circle-xmark"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($stats['alpha'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Tanpa Keterangan (Alpha)</div>
            </div>
        </div>
    </div>

    <!-- 3. Toolbar & Filter (Konsisten 100% dengan Modul Peserta Didik Aktif) -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <form method="GET" action="{{ route('dashboard.peserta-didik.presensi.index') }}" id="formFilterPresensi">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                
                <!-- Kiri: Filter Controls -->
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <!-- Dropdown Paging Baris -->
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                        <select name="per_page" id="perPageSelect" class="per-page-select">
                            @foreach ([10, 15, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    <!-- Dropdown Pilihan Mode Periode -->
                    <select name="periode" id="filterPeriodeTipe" class="toolbar-filter-select">
                        <option value="bulan" {{ $periodeTipe === 'bulan' ? 'selected' : '' }}>Per Bulan</option>
                        <option value="semester" {{ $periodeTipe === 'semester' ? 'selected' : '' }}>Per Semester</option>
                        <option value="tahun" {{ $periodeTipe === 'tahun' ? 'selected' : '' }}>Per Tahun Ajaran</option>
                    </select>

                    <!-- Filter Mode Bulanan -->
                    <div id="filterWrapBulan" style="display: {{ $periodeTipe === 'bulan' ? 'inline-flex' : 'none' }}; gap: 8px;">
                        <select name="bulan" id="filterBulan" class="toolbar-filter-select" style="min-width: 120px;">
                            @php
                                $namaBulan = [
                                    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                                    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                                    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                                ];
                            @endphp
                            @foreach ($namaBulan as $mKey => $mLabel)
                                <option value="{{ $mKey }}" {{ str_pad((string)$bulan, 2, '0', STR_PAD_LEFT) === $mKey ? 'selected' : '' }}>
                                    {{ $mLabel }}
                                </option>
                            @endforeach
                        </select>

                        <select name="tahun" id="filterTahun" class="toolbar-filter-select" style="min-width: 90px;">
                            @for ($y = date('Y') + 1; $y >= 2024; $y--)
                                <option value="{{ $y }}" {{ (int)$tahun === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <!-- Filter Mode Semester -->
                    <div id="filterWrapSemester" style="display: {{ $periodeTipe === 'semester' ? 'inline-flex' : 'none' }}; gap: 8px;">
                        <select name="semester" id="filterSemester" class="toolbar-filter-select" style="min-width: 150px;">
                            <option value="1" {{ $semester == '1' ? 'selected' : '' }}>Semester 1 (Ganjil)</option>
                            <option value="2" {{ $semester == '2' ? 'selected' : '' }}>Semester 2 (Genap)</option>
                        </select>
                    </div>

                    <!-- Filter Mode Tahun Ajaran -->
                    <div id="filterWrapTa" style="display: {{ $periodeTipe !== 'bulan' ? 'inline-flex' : 'none' }}; gap: 8px;">
                        <select name="tahun_ajaran" id="filterTa" class="toolbar-filter-select" style="min-width: 130px;">
                            @for ($y = date('Y') + 1; $y >= 2024; $y--)
                                @php $ta = ($y - 1) . '/' . $y; @endphp
                                <option value="{{ $ta }}" {{ $tahunAjaran === $ta ? 'selected' : '' }}>TA {{ $ta }}</option>
                            @endfor
                        </select>
                    </div>

                    @if ($q)
                        <a href="{{ route('dashboard.peserta-didik.presensi.index', array_merge(request()->except('q'), ['q' => ''])) }}"
                            class="btn btn-outline btn-responsive-icon" style="padding: 7px 12px; font-size: 0.8rem;" title="Reset pencarian">
                            <i class="fas fa-undo"></i> <span class="btn-responsive-text">Reset</span>
                        </a>
                    @endif
                </div>

                <!-- Kanan: Live Search Box Baku SAE -->
                <div class="live-search-wrap">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="q" id="liveSearchInput" placeholder="Cari tanggal, status, keterangan..." value="{{ $q }}" autocomplete="off">
                    <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </form>

        <!-- Bar Informasi Periode Aktif -->
        <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 0.8rem; color: var(--text-muted);">
            <div>
                <i class="fas fa-info-circle text-primary me-1"></i>
                Menampilkan data kehadiran: <strong style="color: var(--text-color);">{{ $range['label'] }}</strong>
            </div>
            <div>
                Tingkat Kehadiran: <strong style="color: #10b981; font-weight: 800;">{{ $stats['persen'] }}%</strong>
                ({{ $stats['total_hadir'] }} dari {{ $stats['total'] }} hari efektif)
            </div>
        </div>
    </div>

    <!-- 4. Datatable Responsif (.table-responsive-stack dengan data-label) -->
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Tanggal &amp; Hari
                    </th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                        Status Kehadiran
                    </th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Jam Masuk
                    </th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Jam Pulang
                    </th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Keterangan
                    </th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                        Snapshot
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    @php
                        $tgl = \Carbon\Carbon::parse($log->tanggal);
                        $status = strtoupper($log->status ?? '');
                    @endphp
                    <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                        <!-- Tanggal & Hari -->
                        <td class="cell-pd-tanggal" style="padding: 14px 18px; font-weight: 600; font-size: 0.88rem; color: var(--text-color);" data-label="Tanggal & Hari">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.25); display: flex; flex-direction: column; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <span style="font-size: 0.62rem; font-weight: 700; color: var(--primary); text-transform: uppercase;">{{ $tgl->translatedFormat('M') }}</span>
                                    <span style="font-size: 0.95rem; font-weight: 800; color: var(--text-color); line-height: 1;">{{ $tgl->format('d') }}</span>
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: var(--text-color);">{{ $tgl->translatedFormat('l') }}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $tgl->translatedFormat('d F Y') }}</div>
                                </div>
                            </div>
                        </td>

                        <!-- Status Kehadiran -->
                        <td class="cell-pd-status" style="padding: 14px 18px; text-align: center;" data-label="Status Kehadiran">
                            @if ($status === 'H')
                                <span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); font-weight: 700; padding: 4px 10px; border-radius: 6px; font-size: 0.78rem;">
                                    <i class="fas fa-check-circle me-1"></i> Hadir Tepat
                                </span>
                            @elseif ($status === 'T')
                                <span class="badge" style="background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); font-weight: 700; padding: 4px 10px; border-radius: 6px; font-size: 0.78rem;">
                                    <i class="fas fa-clock me-1"></i> Terlambat
                                </span>
                                @if ($log->menit_terlambat)
                                    <div style="font-size: 0.7rem; color: #f59e0b; margin-top: 3px;">+{{ $log->menit_terlambat }} menit</div>
                                @endif
                            @elseif ($status === 'I')
                                <span class="badge" style="background: rgba(59,130,246,0.15); color: #3b82f6; border: 1px solid rgba(59,130,246,0.3); font-weight: 700; padding: 4px 10px; border-radius: 6px; font-size: 0.78rem;">
                                    <i class="fas fa-envelope-open-text me-1"></i> Izin
                                </span>
                            @elseif ($status === 'S')
                                <span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); font-weight: 700; padding: 4px 10px; border-radius: 6px; font-size: 0.78rem;">
                                    <i class="fas fa-notes-medical me-1"></i> Sakit
                                </span>
                            @elseif ($status === 'D')
                                <span class="badge" style="background: rgba(168,85,247,0.15); color: #a855f7; border: 1px solid rgba(168,85,247,0.3); font-weight: 700; padding: 4px 10px; border-radius: 6px; font-size: 0.78rem;">
                                    <i class="fas fa-award me-1"></i> Dispensasi
                                </span>
                            @elseif ($status === 'A')
                                <span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); font-weight: 700; padding: 4px 10px; border-radius: 6px; font-size: 0.78rem;">
                                    <i class="fas fa-circle-xmark me-1"></i> Alpha
                                </span>
                            @else
                                <span class="badge badge-outline" style="font-size: 0.78rem; padding: 4px 8px;">{{ $status ?: '-' }}</span>
                            @endif
                        </td>

                        <!-- Jam Masuk -->
                        <td class="cell-pd-masuk" style="padding: 14px 18px;" data-label="Jam Masuk">
                            @if ($log->jam_masuk)
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.88rem;">
                                    {{ \Carbon\Carbon::parse($log->jam_masuk)->format('H:i') }} WIB
                                </div>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">
                                    Via: {{ ucfirst($log->metode_masuk ?? 'terminal') }}
                                </div>
                            @else
                                <span style="color: var(--text-muted); font-size: 0.82rem;">&mdash;</span>
                            @endif
                        </td>

                        <!-- Jam Pulang -->
                        <td class="cell-pd-pulang" style="padding: 14px 18px;" data-label="Jam Pulang">
                            @if ($log->jam_pulang)
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.88rem;">
                                    {{ \Carbon\Carbon::parse($log->jam_pulang)->format('H:i') }} WIB
                                </div>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">
                                    Via: {{ ucfirst($log->metode_pulang ?? 'terminal') }}
                                </div>
                            @else
                                <span style="color: var(--text-muted); font-size: 0.82rem;">&mdash;</span>
                            @endif
                        </td>

                        <!-- Keterangan -->
                        <td class="cell-pd-ket" style="padding: 14px 18px;" data-label="Keterangan">
                            <span style="font-size: 0.82rem; color: var(--text-color);">
                                {{ $log->keterangan ?: '-' }}
                            </span>
                        </td>

                        <!-- Snapshot Foto Kamera Terminal -->
                        <td class="cell-pd-snapshot" style="padding: 14px 18px; text-align: right;" data-label="Snapshot">
                            <div class="table-actions" style="justify-content: flex-end;">
                                @if (!empty($log->foto_masuk_url))
                                    <button type="button" class="btn-icon btn-view-snapshot"
                                        data-url="{{ $log->foto_masuk_url }}"
                                        data-title="Snapshot Presensi Masuk ({{ $tgl->format('d/m/Y') }})"
                                        title="Lihat Foto Terminal Masuk"
                                        style="color: var(--primary); border-color: rgba(99,102,241,0.25);">
                                        <i class="fas fa-camera"></i>
                                    </button>
                                @endif
                                @if (!empty($log->foto_pulang_url))
                                    <button type="button" class="btn-icon btn-view-snapshot"
                                        data-url="{{ $log->foto_pulang_url }}"
                                        data-title="Snapshot Presensi Pulang ({{ $tgl->format('d/m/Y') }})"
                                        title="Lihat Foto Terminal Pulang"
                                        style="color: var(--accent); border-color: rgba(6,182,212,0.25);">
                                        <i class="fas fa-camera-rotate"></i>
                                    </button>
                                @endif
                                @if (empty($log->foto_masuk_url) && empty($log->foto_pulang_url))
                                    <span style="color: var(--text-muted); font-size: 0.75rem;">&mdash;</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                            <div style="font-size: 2.4rem; opacity: 0.35; margin-bottom: 10px;">
                                <i class="fas fa-calendar-xmark"></i>
                            </div>
                            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color); margin-bottom: 4px;">
                                Belum Ada Catatan Presensi
                            </div>
                            <div style="font-size: 0.8rem; max-width: 420px; margin: auto;">
                                Tidak ditemukan riwayat kehadiran untuk periode yang dipilih ({{ $range['label'] }}).
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- 5. Custom Pagination Resmi SAE (Dilarang Memakai $list->links() Mentah) -->
    @if ($logs->hasPages())
        <div class="custom-pagination" style="margin-bottom: 24px;">
            @if ($logs->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $logs->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
            @endif

            @php
                $cur = $logs->currentPage();
                $last = $logs->lastPage();
                $from = max(1, $cur - 2);
                $to = min($last, $cur + 2);
            @endphp

            @if ($from > 1)
                <a href="{{ $logs->url(1) }}" class="page-btn">1</a>
                @if ($from > 2) <span class="page-info">&hellip;</span> @endif
            @endif

            @for ($i = $from; $i <= $to; $i++)
                <a href="{{ $logs->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor

            @if ($to < $last)
                @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                <a href="{{ $logs->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif

            @if ($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif

    <!-- 6. Modal View Snapshot Kamera Terminal Baku SAE (z-index 99999 !important) -->
    <div id="modalSnapshotSaya" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 440px; width: 92%; margin: auto; padding: 20px; border-radius: 16px; text-align: center; border: 1px solid var(--border-color); box-shadow: 0 16px 40px rgba(0,0,0,0.5);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <h4 id="snapshotSayaCaption" style="font-size: 0.95rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Foto Bukti Presensi
                </h4>
                <button type="button" id="btnCloseSnapshotSaya" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="margin: 10px 0;">
                <img id="imgSnapshotSaya" src="" alt="Snapshot" style="width: 100%; border-radius: 12px; object-fit: cover; max-height: 380px; border: 1px solid var(--border-color);">
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/peserta-didik-presensi.js') }}?v={{ file_exists(public_path('js/peserta-didik-presensi.js')) ? filemtime(public_path('js/peserta-didik-presensi.js')) : time() }}"></script>
@endpush
