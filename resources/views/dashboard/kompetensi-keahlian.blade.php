@extends('layouts.dashboard')

@section('title', 'Master Data — Kompetensi Keahlian — SAE')
@section('dash_title', 'Kompetensi Keahlian')

@section('content')
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-laptop-code text-primary me-2"></i> Master Data — Kompetensi Keahlian
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Data kompetensi keahlian / jurusan disinkronkan otomatis dari tabel <strong>Rombongan Belajar</strong>
                (Dapodik).
            </p>
        </div>
        <div class="dash-banner-actions">
            <a href="{{ route('dashboard.dapodik') }}" class="btn btn-outline" style="padding: 9px 16px; font-size: 0.85rem;">
                <i class="fas fa-cloud-arrow-down me-1"></i> Tarik Data Rombel
            </a>
        </div>
    </div>

    <!-- Summary Stats Grid -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-laptop-code"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ $summary['jurusan'] }}
                </div>
                <div class="dash-stat-label">Total Kompetensi Keahlian</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-school"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ $summary['rombel'] }}
                </div>
                <div class="dash-stat-label">Total Rombongan Belajar</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($summary['siswa'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Total Peserta Didik</div>
            </div>
        </div>
    </div>

    <div class="toolbar-row">
        <div style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: var(--text-muted);">
            <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
            <select id="perPageSelect" class="per-page-select">
                @foreach ([10, 15, 25, 50, 100] as $n)
                    <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                @endforeach
            </select>
            <span>entri</span>
            <span class="badge badge-outline" style="margin-left: 8px; font-size: 0.73rem;">Total:
                {{ $total }}</span>
        </div>
        <div class="live-search-wrap">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="liveSearch" placeholder="Cari kode / nama jurusan..." value="{{ $q }}"
                autocomplete="off">
            <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}" title="Hapus pencarian">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
        <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                    @php
                        $cols = [
                            ['kode', 'Kode Jurusan'],
                            ['nama', 'Kompetensi Keahlian'],
                            ['total_rombel', 'Jml Rombel'],
                            ['total_siswa', 'Jml Siswa'],
                        ];
                    @endphp
                    @foreach ($cols as [$key, $label])
                        <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; {{ in_array($key, ['total_rombel', 'total_siswa']) ? 'text-align: center;' : '' }}
                            data-sort="{{ $key }}">
                            {{ $label }}
                            <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                    @endforeach
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Tingkat Kelas</th>
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Kurikulum</th>
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                        Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $item)
                    <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                        <td style="padding: 14px 18px; font-weight: 700; color: var(--primary); font-size: 0.86rem; font-family: monospace;"
                            data-label="Kode Jurusan">
                            <span class="badge badge-primary"
                                style="font-size: 0.76rem; padding: 4px 8px; font-family: monospace;">
                                {{ $item->kode }}
                            </span>
                        </td>
                        <td style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.88rem;"
                            data-label="Kompetensi Keahlian">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div
                                    style="width: 32px; height: 32px; border-radius: 8px; background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.82rem; flex-shrink: 0;">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 700;">{{ $item->nama }}</div>
                                    @if (!empty($item->rombel_list))
                                        <div style="font-size: 0.73rem; color: var(--text-muted); margin-top: 2px;">
                                            {{ implode(', ', array_slice($item->rombel_list, 0, 4)) }}{{ count($item->rombel_list) > 4 ? ' +' . (count($item->rombel_list) - 4) . ' lainnya' : '' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td style="padding: 14px 18px; text-align: center; font-weight: 700; font-size: 0.88rem;"
                            data-label="Jml Rombel">
                            <span class="badge badge-outline" style="font-size: 0.78rem; padding: 3px 10px;">
                                <i class="fas fa-users-rectangle me-1" style="opacity: 0.7;"></i> {{ $item->total_rombel }}
                            </span>
                        </td>
                        <td style="padding: 14px 18px; text-align: center; font-weight: 700; font-size: 0.88rem; color: #10b981;"
                            data-label="Jml Siswa">
                            <span class="badge"
                                style="background: rgba(16,185,129,0.12); color: #10b981; font-size: 0.78rem; padding: 3px 10px;">
                                <i class="fas fa-user-graduate me-1"></i>
                                {{ number_format($item->total_siswa, 0, ',', '.') }}
                            </span>
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.8rem; color: var(--text-muted);" data-label="Tingkat">
                            @if (!empty($item->tingkat_list))
                                @foreach ($item->tingkat_list as $tk)
                                    <span class="badge badge-outline"
                                        style="font-size: 0.7rem; padding: 2px 6px; margin-right: 3px;">{{ $tk }}</span>
                                @endforeach
                            @else
                                -
                            @endif
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.78rem; color: var(--text-muted); max-width: 220px;"
                            data-label="Kurikulum">
                            @if (!empty($item->kurikulum_list))
                                {{ Str::limit(implode(', ', $item->kurikulum_list), 45) }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                            <div class="table-actions">
                                <button type="button" class="btn-icon" title="Lihat Daftar Rombel & Siswa"
                                    onclick="openRombelModal('{{ $item->kode }}', '{{ addslashes($item->nama) }}')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"
                            style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                            <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                            <div>Belum ada data kompetensi keahlian di tabel rombongan_belajar.</div>
                            <div style="font-size: 0.78rem; margin-top: 6px;">
                                Silakan sinkronisasi data Dapodik terlebih dahulu melalui menu <strong>Tarik Data
                                    Dapodik</strong>.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($list->hasPages())
        <div class="custom-pagination">
            @if ($list->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $list->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i
                        class="fas fa-chevron-left"></i></a>
            @endif
            @php
                $cur = $list->currentPage();
                $last = $list->lastPage();
                $from = max(1, $cur - 2);
                $to = min($last, $cur + 2);
            @endphp
            @if ($from > 1)
                <a href="{{ $list->url(1) }}" class="page-btn">1</a>
                @if ($from > 2)
                    <span class="page-info">&hellip;</span>
                @endif
            @endif
            @for ($i = $from; $i <= $to; $i++)
                <a href="{{ $list->url($i) }}"
                    class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor
            @if ($to < $last)
                @if ($to < $last - 1)
                    <span class="page-info">&hellip;</span>
                @endif
                <a href="{{ $list->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif
            @if ($list->hasMorePages())
                <a href="{{ $list->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i
                        class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif

    {{-- Detail Rombel Modal --}}
    <div id="rombelModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 720px; width: 92%; max-height: 85vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div>
                    <h3 id="rombelModalTitle"
                        style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                        Daftar Rombongan Belajar
                    </h3>
                    <div id="rombelModalSubtitle" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                        -
                    </div>
                </div>
                <button type="button" onclick="closeRombelModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="rombelLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: 1.4rem;"></i>
                <div>Memuat data rombel...</div>
            </div>

            <div id="rombelTableWrapper" style="overflow-y: auto; flex: 1; display: none;">
                <table class="table"
                    style="width: 100%; border-collapse: collapse; margin-bottom: 0; font-size: 0.83rem;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted);">Nama Rombel</th>
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted);">Tingkat</th>
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted);">Wali Kelas</th>
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted);">Ruang</th>
                            <th
                                style="padding: 10px 14px; font-weight: 700; color: var(--text-muted); text-align: center;">
                                Jml Siswa</th>
                        </tr>
                    </thead>
                    <tbody id="rombelTableBody">
                    </tbody>
                </table>
            </div>

            <div
                style="display: flex; justify-content: flex-end; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-outline" onclick="closeRombelModal()"
                    style="padding: 8px 18px; font-size: 0.82rem;">Tutup</button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/kompetensi-keahlian.js') }}"></script>
    @endpush
@endsection
