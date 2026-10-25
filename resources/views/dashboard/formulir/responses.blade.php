@extends('layouts.dashboard')

@section('title', 'Rekap Tanggapan: ' . $formulir->judul . ' — SAE')
@section('dash_title', 'Formulir & Survei')

@section('content')
    <!-- 1. Header Banner & Action Buttons -->
    <div class="dash-banner">
        <div>
            <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-bottom: 4px;">
                <a href="{{ route('dashboard.formulir.index') }}"
                    style="color: var(--primary); text-decoration: none;">Formulir &amp; Survei</a>
                <i class="fas fa-chevron-right mx-1" style="font-size: var(--text-xs, 0.75rem);"></i> Rekap Tanggapan
            </div>
            <h2 style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-inbox text-primary me-2"></i> {{ $formulir->judul }}
            </h2>
            <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem); margin: 0;">
                Total <strong>{{ $totalRespon }} tanggapan</strong> terekam. Unduh data langsung ke format
                spreadsheet Excel (.xlsx).
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('dashboard.formulir.export-excel', $formulir->id) }}" class="btn btn-primary"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); text-decoration: none;"
                title="Ekspor Respons ke Excel (.xlsx)">
                <i class="fas fa-file-excel"></i>
            </a>
            <a href="{{ $formulir->public_url }}" target="_blank" class="btn btn-outline"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); text-decoration: none;"
                title="Buka Formulir Publik">
                <i class="fas fa-external-link-alt text-primary"></i>
            </a>
            <a href="{{ route('dashboard.formulir.index') }}" class="btn btn-outline"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); text-decoration: none;"
                title="Kembali ke Daftar Formulir">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    @if (session('success'))
        <div class="alert alert-success"
            style="padding: 12px 16px; border-radius: var(--radius-lg, 12px); margin-bottom: 20px; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-circle-check" style="font-size: var(--text-lg, 1.125rem);"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger"
            style="padding: 12px 16px; border-radius: var(--radius-lg, 12px); margin-bottom: 20px; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-circle-exclamation" style="font-size: var(--text-lg, 1.125rem);"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- 2. Stat Grid Ringkasan Tanggapan (Baku SAE) -->
    <div class="form-stat-grid" style="margin-bottom: 24px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(2, 132, 199, 0.12); color: #0284c7;">
                <i class="fas fa-inbox"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $totalRespon }}</div>
                <div class="dash-stat-label">Total Tanggapan</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.12); color: #6366f1;">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $saeUsersCount }}</div>
                <div class="dash-stat-label">Akun SAE Resmi</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                <i class="fas fa-users"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $guestCount }}</div>
                <div class="dash-stat-label">Responden Tamu</div>
            </div>
        </div>

        <div class="dash-stat-card">
            @php
                $isScheduleOpen = $formulir->isScheduleOpen();
                $statusColor = $formulir->is_active ? ($isScheduleOpen ? '#10b981' : '#f59e0b') : '#94a3b8';
                $statusBg = $formulir->is_active
                    ? ($isScheduleOpen
                        ? 'rgba(16, 185, 129, 0.12)'
                        : 'rgba(245, 158, 11, 0.12)')
                    : 'rgba(148, 163, 184, 0.12)';
                $statusIcon = $formulir->is_active ? ($isScheduleOpen ? 'fa-circle-check' : 'fa-clock') : 'fa-ban';
                $statusText = $formulir->is_active ? ($isScheduleOpen ? 'Aktif (Buka)' : 'Tutup (Jadwal)') : 'Nonaktif';
            @endphp
            <div class="dash-stat-icon" style="background: {{ $statusBg }}; color: {{ $statusColor }};">
                <i class="fas {{ $statusIcon }}"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-xl, 1.25rem); font-weight: 700; color: {{ $statusColor }};">
                    {{ $statusText }}
                </div>
                <div class="dash-stat-label">Status Formulir</div>
            </div>
        </div>
    </div>

    <!-- Statistik & Analisis Pilihan/Rating -->
    @if (!empty($fieldStats))
        <h3
            style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-chart-pie text-primary"></i> Ringkasan Pola Tanggapan Pilihan
        </h3>
        <div class="stat-bars-grid" style="margin-bottom: 24px;">
            @foreach ($fieldStats as $fieldId => $stat)
                <div class="stat-bar-card">
                    <div class="stat-bar-title">
                        <span>{{ $stat['label'] }}</span>
                        <span style="font-size: var(--text-xs, 0.75rem); font-weight: 600; color: var(--text-muted);">{{ $stat['total'] }}
                            suara</span>
                    </div>
                    <div>
                        @foreach ($stat['counts'] as $choice => $cnt)
                            @php
                                $pct = $stat['total'] > 0 ? round(($cnt / $stat['total']) * 100) : 0;
                            @endphp
                            <div class="bar-row">
                                <div class="bar-meta">
                                    <span>{{ $choice }}</span>
                                    <strong>{{ $cnt }} ({{ $pct }}%)</strong>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: {{ $pct }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Unified Datatable Tanggapan Formulir (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
    <div class="card datatable-card-wrapper" id="tableDataContainer">
        <!-- 1. Toolbar Header Datatable Baku SAE (Entries, Reset & Live Search) -->
        <div class="datatable-toolbar">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                        <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                            @foreach ([10, 15, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ ($perPage ?? 15) == $n ? 'selected' : '' }}>
                                    {{ $n }}</option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    @if (!empty(request('q')))
                        <a href="{{ route('dashboard.formulir.responses', $formulir->id) }}"
                            class="btn btn-outline btn-reset-filter"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                            title="Reset pencarian" data-live-reset>
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <div class="live-search-wrap" style="height: 38px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" name="q"
                        placeholder="Cari nama responden atau jawaban..." value="{{ request('q') }}"
                        autocomplete="off">
                    <button type="button" id="clearSearch"
                        class="clear-search {{ !empty(request('q')) ? 'visible' : '' }}" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. Datatable Responsif Resmi SAE (.table-responsive-stack) -->
        <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: var(--bg-hover); border-bottom: 1px solid var(--border-color);">
                        <th
                            style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 60px; text-align: center;">
                            No</th>
                        <th class="sortable-th {{ $sort === 'created_at' ? 'sorted' : '' }}" data-sort="created_at"
                            style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                            Waktu Submit
                            <span class="sort-icon">{!! $sort === 'created_at' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th class="sortable-th {{ $sort === 'nama_responden' ? 'sorted' : '' }}"
                            data-sort="nama_responden"
                            style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                            Nama Responden
                            <span class="sort-icon">{!! $sort === 'nama_responden' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th class="sortable-th {{ $sort === 'identitas_responden' ? 'sorted' : '' }}"
                            data-sort="identitas_responden"
                            style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                            Identitas
                            <span class="sort-icon">{!! $sort === 'identitas_responden' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th
                            style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Ringkasan Jawaban
                        </th>
                        <th
                            style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 100px;">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($responses as $idx => $r)
                        @php
                            $jawaban = is_array($r->jawaban) ? $r->jawaban : [];
                        @endphp
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td style="padding: 14px 18px; text-align: center; color: var(--text-muted); font-weight: 600;"
                                data-label="No">
                                {{ $responses->firstItem() + $idx }}
                            </td>
                            <td style="padding: 14px 18px;" data-label="Waktu Submit">
                                <span style="font-weight: 600; color: var(--text-color);">
                                    {{ $r->created_at ? $r->created_at->format('d/m/Y') : '-' }}
                                </span>
                                <span style="display: block; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                    {{ $r->created_at ? $r->created_at->format('H:i:s') . ' WIB' : '' }}
                                </span>
                            </td>
                            <td style="padding: 14px 18px;" data-label="Nama Responden">
                                <div style="font-weight: 700; color: var(--text-color);">
                                    {{ $r->nama_responden ?: 'Responden Tamu' }}</div>
                                @if ($r->pengguna_id)
                                    <span class="badge"
                                        style="font-size: var(--text-2xs, 0.6875rem); background: rgba(99, 102, 241, 0.12); color: var(--primary); padding: 2px 6px; border-radius: var(--radius-xs, 4px);">
                                        <i class="fas fa-user-check me-1"></i>Akun SAE
                                    </span>
                                @else
                                    <span class="badge badge-outline"
                                        style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 5px; color: var(--text-muted);">Tamu</span>
                                @endif
                            </td>
                            <td style="padding: 14px 18px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);"
                                data-label="Identitas">
                                {{ $r->identitas_responden ?: '-' }}
                            </td>
                            <td style="padding: 14px 18px; max-width: 280px;" data-label="Ringkasan Jawaban">
                                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                    @foreach (array_slice($jawaban, 0, 2) as $k => $val)
                                        <span
                                            style="background: var(--bg-hover); padding: 2px 7px; border-radius: var(--radius-sm, 6px); font-size: var(--text-xs, 0.75rem); color: var(--text-muted); border: 1px solid var(--border-color); white-space: nowrap; max-width: 180px; overflow: hidden; text-overflow: ellipsis;">
                                            {{ is_array($val) ? implode(', ', $val) : \Illuminate\Support\Str::limit($val, 25) }}
                                        </span>
                                    @endforeach
                                    @if (count($jawaban) > 2)
                                        <span
                                            style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">+{{ count($jawaban) - 2 }}
                                            lainnya</span>
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                <div class="table-actions" style="display: flex; gap: 5px; justify-content: flex-end;">
                                    <button type="button" class="btn-icon" title="Lihat Detail"
                                        onclick='openDetailModal(@json($r), @json($formulir->skema ?? []))'>
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <form
                                        action="{{ route('dashboard.formulir.delete-response', [$formulir->id, $r->id]) }}"
                                        method="POST" data-confirm="delete"
                                        data-name="tanggapan dari {{ $r->nama_responden ?? 'responden ini' }}"
                                        style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon text-danger" title="Hapus Tanggapan">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                                <i class="fas fa-inbox mb-2"
                                    style="font-size: var(--text-3xl, 1.875rem); opacity: 0.35; display: block;"></i>
                                <div style="font-weight: 600; font-size: var(--text-md, 1rem); margin-bottom: 4px;">Belum Ada Tanggapan
                                </div>
                                <div style="font-size: var(--text-sm, 0.8125rem);">Belum ada responden yang mengisi formulir ini atau tidak
                                    ada
                                    data yang cocok dengan pencarian.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- 3. Footer Paginasi Terpadu Datatable -->
            @if ($responses->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $responses])
                </div>
            @endif
        </div>

        <!-- Modal Detail Tanggapan Standar Dashboard SAE -->
        <div id="responseModal" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card"
                style="max-width: 640px; width: 95%; padding: 0; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.3); border: 1px solid var(--border-color);">
                <div
                    style="padding: 18px 24px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h4 style="font-size: var(--text-lg, 1.125rem); font-weight: 700; margin: 0; color: var(--text-color);">Rincian
                            Tanggapan Responden</h4>
                        <span id="modalMeta" style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">-</span>
                    </div>
                    <button type="button" onclick="closeDetail()" class="btn-theme-toggle"
                        style="width: 32px; height: 32px;" title="Tutup">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div style="padding: 24px; overflow-y: auto; max-height: 60vh;" id="modalContent"></div>
                <div
                    style="padding: 14px 24px; border-top: 1px solid var(--border-color); text-align: right; background: var(--bg-hover);">
                    <button type="button" class="btn btn-outline" onclick="closeDetail()"
                        style="font-size: var(--text-base, 0.875rem); padding: 6px 16px;">Tutup</button>
                </div>
            </div>
        </div>

        @push('scripts')
            <script
                src="{{ asset('js/formulir.js') }}?v={{ file_exists(public_path('js/formulir.js')) ? filemtime(public_path('js/formulir.js')) : time() }}">
            </script>
        @endpush
    @endsection
