@extends('layouts.dashboard')

@section('title', 'Master Data — Rombel — SAE')
@section('dash_title', 'Rombongan Belajar')

@section('content')
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-chalkboard-user text-primary me-2"></i> Master Data — Rombongan Belajar
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Data rombel / kelas disinkronkan otomatis dari tabel <strong>Rombongan Belajar</strong> (Dapodik).
            </p>
        </div>
        <div class="dash-banner-actions">
            <a href="{{ route('dashboard.dapodik') }}" class="btn btn-outline" style="padding: 9px 16px; font-size: 0.85rem;">
                <i class="fas fa-cloud-arrow-down me-1"></i> Tarik Data Dapodik
            </a>
        </div>
    </div>

    <!-- Summary Stats Grid -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-chalkboard-user"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ $summary['rombel'] }}
                </div>
                <div class="dash-stat-label">Total Rombel</div>
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

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-laptop-code"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ $summary['jurusan'] }}
                </div>
                <div class="dash-stat-label">Jurusan Terdaftar</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ $summary['wali'] }}
                </div>
                <div class="dash-stat-label">Wali Kelas Aktif</div>
            </div>
        </div>
    </div>

    <!-- Toolbar & Filter -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <div style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: var(--text-muted);">
                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                    <select id="perPageSelect" class="per-page-select">
                        @foreach ([10, 15, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </div>

                <select id="filterTingkat" class="form-control" style="width: auto; min-width: 140px; padding: 7px 12px; font-size: 0.82rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg, rgba(255,255,255,0.05)); color: var(--text-color);">
                    <option value="">Semua Tingkat</option>
                    @foreach ($filterTingkat as $tk)
                        <option value="{{ $tk }}" {{ $tingkat === $tk ? 'selected' : '' }}>{{ $tk }}</option>
                    @endforeach
                </select>

                <select id="filterJurusan" class="form-control" style="width: auto; min-width: 180px; padding: 7px 12px; font-size: 0.82rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg, rgba(255,255,255,0.05)); color: var(--text-color);">
                    <option value="">Semua Jurusan</option>
                    @foreach ($filterJurusan as $j)
                        <option value="{{ $j }}" {{ $jurusan === $j ? 'selected' : '' }}>{{ $j }}</option>
                    @endforeach
                </select>

                @if ($q || $tingkat || $jurusan)
                    <a href="{{ route('dashboard.rombel.index') }}" class="btn btn-outline" style="padding: 7px 12px; font-size: 0.8rem;" title="Reset filter">
                        <i class="fas fa-undo me-1"></i> Reset
                    </a>
                @endif
            </div>

            <div class="live-search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearch" placeholder="Cari nama rombel / wali / ruang..." value="{{ $q }}" autocomplete="off">
                <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}" title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Datatable Rombel -->
    <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
        <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                    @php
                        $cols = [
                            ['nama', 'Nama Rombel'],
                            ['tingkat', 'Tingkat'],
                            ['jurusan', 'Kompetensi Keahlian'],
                            ['wali_kelas', 'Wali Kelas'],
                            ['ruang', 'Ruang'],
                            ['total_siswa', 'Jml Siswa'],
                        ];
                    @endphp
                    @foreach ($cols as [$key, $label])
                        <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; {{ $key === 'total_siswa' ? 'text-align: center;' : '' }}"
                            data-sort="{{ $key }}">
                            {{ $label }}
                            <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                    @endforeach
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $item)
                    <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                        <td style="padding: 14px 18px; font-weight: 700; color: var(--primary); font-size: 0.88rem;" data-label="Nama Rombel">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.82rem; flex-shrink: 0;">
                                    <i class="fas fa-chalkboard-user"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 700;">{{ $item->nama }}</div>
                                    @if ($item->kurikulum)
                                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                            {{ Str::limit($item->kurikulum, 40) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.82rem;" data-label="Tingkat">
                            <span class="badge badge-outline" style="font-size: 0.72rem; padding: 3px 8px;">
                                {{ $item->tingkat ?: '-' }}
                            </span>
                        </td>
                        <td style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.84rem;" data-label="Kompetensi Keahlian">
                            {{ $item->jurusan ?: '-' }}
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.84rem; color: var(--text-color);" data-label="Wali Kelas">
                            @if ($item->wali_kelas)
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-user-tie" style="color: var(--primary); opacity: 0.7; font-size: 0.76rem;"></i>
                                    <span>{{ $item->wali_kelas }}</span>
                                </div>
                            @else
                                <span style="color: var(--text-muted);">-</span>
                            @endif
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);" data-label="Ruang">
                            {{ $item->ruang ?: '-' }}
                        </td>
                        <td style="padding: 14px 18px; text-align: center; font-weight: 700; font-size: 0.88rem; color: #10b981;" data-label="Jml Siswa">
                            <span class="badge" style="background: rgba(16,185,129,0.12); color: #10b981; font-size: 0.78rem; padding: 3px 10px;">
                                <i class="fas fa-user-graduate me-1"></i>
                                {{ number_format($item->total_siswa, 0, ',', '.') }}
                            </span>
                        </td>
                        <td style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                            <div class="table-actions">
                                <button type="button" class="btn-icon" title="Lihat Daftar Siswa"
                                    onclick="openSiswaModal('{{ $item->rombongan_belajar_id }}', '{{ addslashes($item->nama) }}')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                            <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                            <div>Belum ada data rombongan belajar yang sesuai.</div>
                            <div style="font-size: 0.78rem; margin-top: 6px;">
                                Silakan sinkronisasi data Dapodik terlebih dahulu melalui menu <strong>Tarik Data Dapodik</strong>.
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
                <a href="{{ $list->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
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
                <a href="{{ $list->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor
            @if ($to < $last)
                @if ($to < $last - 1)
                    <span class="page-info">&hellip;</span>
                @endif
                <a href="{{ $list->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif
            @if ($list->hasMorePages())
                <a href="{{ $list->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif

    {{-- Detail Siswa Modal --}}
    <div id="siswaModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 760px; width: 92%; max-height: 85vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div>
                    <h3 id="siswaModalTitle"
                        style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                        Daftar Peserta Didik
                    </h3>
                    <div id="siswaModalSubtitle" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                        -
                    </div>
                </div>
                <button type="button" onclick="closeSiswaModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="siswaLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: 1.4rem;"></i>
                <div>Memuat data peserta didik...</div>
            </div>

            <div id="siswaTableWrapper" style="overflow-y: auto; flex: 1; display: none;">
                <table class="table"
                    style="width: 100%; border-collapse: collapse; margin-bottom: 0; font-size: 0.83rem;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted); width: 40px; text-align: center;">No</th>
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted);">Nama Siswa</th>
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted);">NISN / NIPD</th>
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted); text-align: center;">L/P</th>
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted);">Tempat, Tgl Lahir</th>
                        </tr>
                    </thead>
                    <tbody id="siswaTableBody">
                    </tbody>
                </table>
            </div>

            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                <div id="siswaModalFooterCount" style="font-size: 0.8rem; color: var(--text-muted);">
                    Total: 0 Siswa
                </div>
                <button type="button" class="btn btn-outline" onclick="closeSiswaModal()"
                    style="padding: 8px 18px; font-size: 0.82rem;">Tutup</button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/rombel.js') }}?v={{ filemtime(public_path('js/rombel.js')) }}">
        </script>
    @endpush
@endsection
