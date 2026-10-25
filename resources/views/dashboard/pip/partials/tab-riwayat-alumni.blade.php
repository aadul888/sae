<!-- 1. Stat Grid Baku SAE (4 Kartu Ringkasan Riwayat Kelas Lain & Alumni - Genap & Konsisten) -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--primary);">
                {{ number_format($riwayatAlumniStats['total_siswa'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Alumni & Non-Aktif</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #10b981;">
                {{ number_format($riwayatAlumniStats['total_pemberian'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">SK Pemberian</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
            <i class="fas fa-file-signature"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #f59e0b;">
                {{ number_format($riwayatAlumniStats['total_nominasi'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">SK Nominasi</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(14,165,233,0.12); color: #0ea5e9;">
            <i class="fas fa-money-bill-trend-up"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #0ea5e9; white-space: nowrap;">
                Rp {{ number_format($riwayatAlumniStats['total_nominal'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Total Dana Cair</div>
        </div>
    </div>
</div>

<!-- Unified Datatable Riwayat Alumni PIP (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
<div class="card datatable-card-wrapper" id="tableDataContainer">
    <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
    <div class="datatable-toolbar">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <div class="toolbar-entries">
                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                    <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                        @foreach ([10, 15, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>
                                {{ $n }}
                            </option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </div>

                <select id="filterJenisSk" name="jenis_sk" class="toolbar-filter-select" style="height: 38px;">
                    <option value="">Semua Jenis SK</option>
                    <option value="pemberian" {{ ($jenisSkFilter ?? '') === 'pemberian' ? 'selected' : '' }}>SK
                        Pemberian
                    </option>
                    <option value="nominasi" {{ ($jenisSkFilter ?? '') === 'nominasi' ? 'selected' : '' }}>SK Nominasi
                    </option>
                </select>

                <select id="filterStatusKeluar" name="status_keluar" class="toolbar-filter-select"
                    style="height: 38px;">
                    <option value="">Semua Status Keluar</option>
                    @foreach ($alumniStatusList as $st)
                        <option value="{{ $st }}"
                            {{ ($statusKeluarFilter ?? '') === $st ? 'selected' : '' }}>
                            {{ $st }}
                        </option>
                    @endforeach
                    @if (!in_array('Lainnya', $alumniStatusList, true))
                        <option value="Lainnya" {{ ($statusKeluarFilter ?? '') === 'Lainnya' ? 'selected' : '' }}>
                            Lainnya / Arsip
                        </option>
                    @endif
                </select>

                <select id="filterTahap" name="tahap" class="toolbar-filter-select" style="height: 38px;">
                    <option value="">Semua Tahap</option>
                    @foreach ($riwayatAlumniTahapList as $th)
                        <option value="{{ $th }}" {{ request('tahap') === $th ? 'selected' : '' }}>
                            {{ $th }}
                        </option>
                    @endforeach
                </select>

                @if ($q || request('tahap') || ($jenisSkFilter ?? '') || ($statusKeluarFilter ?? ''))
                    <a href="{{ route('dashboard.pip.index', ['tab' => 'riwayat_alumni']) }}"
                        class="btn btn-outline btn-reset-filter"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                        title="Reset filter" data-live-reset>
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>

            <div class="live-search-wrap" style="height: 38px;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchPip" name="q" placeholder="Cari nama / NISN / No SK / rombel..."
                    value="{{ $q }}" autocomplete="off">
                <button type="button" id="clearSearchPip" class="clear-search {{ !empty($q) ? 'visible' : '' }}"
                    title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Konten Datatable Responsive Stack -->
    <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
        <table class="table table-pd table-pip-riwayat"
            style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                    <th class="th-no d-none-mobile"
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); width: 48px; text-align: center;">
                        No</th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); min-width: 220px;">
                        Peserta Didik</th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); min-width: 150px;">
                        Status &amp; Rombel</th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); min-width: 200px;">
                        SK / Tahap</th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); text-align: right; min-width: 120px;">
                        Nominal</th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); min-width: 140px;">
                        Pencairan</th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); text-align: center; width: 80px;">
                        Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($riwayatAlumniList as $r)
                    @php
                        $isNominasi = ($r->jenis_sk ?? 'pemberian') === 'nominasi';
                        $cair =
                            stripos((string) $r->status_cair, 'cair') !== false &&
                            stripos((string) $r->status_cair, 'belum') === false;
                        $rombelTeks = $r->nama_rombel_terakhir ?: ($r->rombel ?: ($r->kelas ?: '-'));
                        $itemIndex = ($riwayatAlumniList->firstItem() ?: 1) + $loop->index;
                    @endphp
                    <tr class="pip-riwayat-row"
                        style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                        <td class="cell-pd-no d-none-mobile"
                            style="text-align: center; color: var(--text-muted); font-size: var(--text-base, 0.875rem); font-weight: 700;"
                            data-label="No">
                            {{ $itemIndex }}</td>
                        <td class="cell-pd-nama" style="padding: 12px 14px;" data-label="Peserta Didik">
                            <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 10px;">
                                <div class="user-avatar pd-foto-thumb"
                                    style="width: 40px; height: 40px; border-radius: var(--radius-lg, 12px); overflow: hidden; background: rgba(99,102,241,0.12); flex-shrink: 0; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-color);">
                                    <img src="{{ $r->foto_url ?: asset('img/logo-dark.png') }}"
                                        alt="{{ $r->nama_pd }}"
                                        style="width: 100%; height: 100%; object-fit: cover;"
                                        onerror="this.src='/img/logo-dark.png'">
                                </div>
                                <div class="pd-info" style="min-width: 0; flex: 1;">
                                    <div class="pd-title-row"
                                        style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                        <span class="badge badge-outline badge-row-no d-none-desktop d-block-mobile"
                                            style="font-size: var(--text-2xs, 0.6875rem); padding: 1px 6px; font-weight: 700; color: var(--text-muted);">
                                            #{{ $itemIndex }}
                                        </span>
                                        <div class="pd-nama"
                                            style="font-weight: 800; font-size: var(--text-base, 0.875rem); color: var(--text-color); line-height: 1.35; word-break: break-word;">
                                            {{ $r->nama_pd }}
                                        </div>
                                    </div>
                                    <div class="pd-meta-row"
                                        style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-family: monospace; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 2px;">
                                        <span>NISN: <strong>{{ $r->nisn ?: '-' }}</strong></span>
                                        @if ($r->nama_ibu_kandung)
                                            &bull; <span style="font-family: inherit;">Ibu:
                                                {{ $r->nama_ibu_kandung }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="cell-pd-kelas" style="padding: 12px 14px;" data-label="Status &amp; Rombel">
                            <div style="display: flex; flex-direction: column; gap: 2px;">
                                <div
                                    style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-bottom: 2px;">
                                    @if ($r->status_keluar === 'Alumni')
                                        <span class="badge badge-success"
                                            style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">
                                            Alumni{{ $r->tahun_lulus ? ' (' . $r->tahun_lulus . ')' : '' }}
                                        </span>
                                    @elseif ($r->status_keluar === 'Mutasi')
                                        <span class="badge badge-warning"
                                            style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">
                                            Mutasi
                                        </span>
                                    @elseif (!empty($r->status_keluar))
                                        <span class="badge badge-outline"
                                            style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">
                                            {{ $r->status_keluar }}
                                        </span>
                                    @else
                                        <span class="badge badge-outline"
                                            style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px; opacity: 0.85;">
                                            Kelas Non-Aktif / Arsip
                                        </span>
                                    @endif
                                </div>
                                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); font-weight: 600;">
                                    {{ $rombelTeks }}
                                </div>
                            </div>
                        </td>
                        <td class="cell-pd-status" style="padding: 12px 14px;" data-label="SK / Tahap">
                            <div style="display: flex; flex-direction: column; gap: 2px;">
                                <div
                                    style="display: flex; align-items: center; gap: 6px; margin-bottom: 2px; flex-wrap: wrap;">
                                    <span class="badge {{ $isNominasi ? 'badge-warning' : 'badge-success' }}"
                                        style="font-size: var(--text-2xs, 0.6875rem); padding: 1px 6px;">
                                        {{ $isNominasi ? 'SK Nominasi' : 'SK Pemberian' }}
                                    </span>
                                    @if (!empty($r->fase))
                                        <span class="badge badge-outline"
                                            style="font-size: var(--text-2xs, 0.6875rem); padding: 1px 5px;">
                                            {{ \App\Models\PipUsulan::getFaseLabel($r->fase) }}
                                        </span>
                                    @endif
                                </div>
                                <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color);">
                                    {{ $r->nomor_sk ?: '-' }}
                                </div>
                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                    @if (!empty($r->tahun_pelajaran))
                                        <span style="font-weight: 700;">TP {{ $r->tahun_pelajaran }}</span> &bull;
                                    @elseif (!empty($r->tahun))
                                        <span style="font-weight: 700;">Th {{ $r->tahun }}</span> &bull;
                                    @endif
                                    {{ $r->tahap ?: '-' }}@if ($r->tanggal_sk)
                                        &bull; {{ $r->tanggal_sk }}
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="cell-pd-nominal"
                            style="padding: 12px 14px; text-align: right; font-weight: 700; white-space: nowrap;"
                            data-label="Nominal">
                            {{ $r->nominal !== null ? 'Rp ' . number_format($r->nominal, 0, ',', '.') : '-' }}
                        </td>
                        <td class="cell-pd-cair" style="padding: 12px 14px;" data-label="Pencairan">
                            @if ($isNominasi)
                                <span
                                    class="badge {{ !empty($r->tanggal_aktivasi) ? 'badge-success' : 'badge-warning' }}"
                                    style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                    {{ !empty($r->tanggal_aktivasi) ? 'Sudah Aktivasi' : 'Belum Aktivasi' }}
                                </span>
                                @if ($r->tanggal_aktivasi)
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 3px;">
                                        Tgl: {{ $r->tanggal_aktivasi }}
                                    </div>
                                @endif
                            @else
                                <span class="badge {{ $cair ? 'badge-success' : 'badge-warning' }}"
                                    style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                    {{ $r->status_cair ?: 'Belum ada status' }}
                                </span>
                                @if ($r->tanggal_cair)
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 3px;">
                                        Tgl Cair: {{ $r->tanggal_cair }}
                                    </div>
                                @endif
                            @endif
                        </td>
                        <td class="cell-pd-aksi" style="padding: 12px 14px; text-align: center;" data-label="Aksi">
                            <button type="button" class="btn-icon" style="color: #ef4444;" title="Hapus Riwayat"
                                onclick="pipHapusRiwayat({{ $r->id }}, @js($r->nama_pd))">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="cell-empty"
                                style="padding: 35px; text-align: center; color: var(--text-muted);">
                                <i class="fas fa-user-graduate mb-2"
                                    style="font-size: var(--text-3xl, 1.875rem); opacity: 0.4; display: block;"></i>
                                Belum ada data riwayat penerima PIP untuk kelas lain atau alumni.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 3. Footer Paginasi Terpadu Datatable -->
        @if ($riwayatAlumniList->hasPages())
            <div class="datatable-footer">
                @include('partials.datatable-pagination', ['paginator' => $riwayatAlumniList])
            </div>
        @endif
    </div>
