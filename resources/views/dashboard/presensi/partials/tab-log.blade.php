    <div class="presensi-tab-pane" id="tab-log" style="display: {{ $activeTab === 'log' ? 'block' : 'none' }};">
        <!-- Unified Datatable Log Presensi (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
            <div class="datatable-toolbar">
                <form id="formFilterLog" action="{{ route('dashboard.presensi.index') }}" method="GET"
                    style="margin: 0;">
                    <input type="hidden" name="tab" value="log">

                    <div
                        style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                            <div class="toolbar-entries">
                                <label for="perPageLogSelect" style="margin: 0;">Tampilkan</label>
                                <select id="perPageLogSelect" name="perPageLog" class="per-page-select"
                                    style="height: 38px;">
                                    @foreach ([10, 15, 25, 50, 100] as $n)
                                        <option value="{{ $n }}"
                                            {{ ($perPageLog ?? 15) == $n ? 'selected' : '' }}>{{ $n }}</option>
                                    @endforeach
                                </select>
                                <span>entri</span>
                            </div>

                            <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control"
                                style="height: 38px; padding: 0 10px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px);"
                                title="Pilih Tanggal Presensi">

                            <select name="rombel_id" id="filterRombel" class="toolbar-filter-select"
                                style="min-width: 150px; height: 38px;">
                                <option value="">Semua Kelas</option>
                                @foreach ($rombelList as $r)
                                    <option value="{{ $r->rombongan_belajar_id }}"
                                        {{ request('rombel_id') == $r->rombongan_belajar_id ? 'selected' : '' }}>
                                        {{ $r->nama }}
                                    </option>
                                @endforeach
                            </select>

                            <select name="status" id="filterStatus" class="toolbar-filter-select"
                                style="min-width: 140px; height: 38px;">
                                <option value="">Semua Status</option>
                                <option value="H" {{ request('status') === 'H' ? 'selected' : '' }}>Hadir Tepat Waktu
                                </option>
                                <option value="T" {{ request('status') === 'T' ? 'selected' : '' }}>Terlambat
                                </option>
                                <option value="I" {{ request('status') === 'I' ? 'selected' : '' }}>Izin</option>
                                <option value="S" {{ request('status') === 'S' ? 'selected' : '' }}>Sakit</option>
                                <option value="A" {{ request('status') === 'A' ? 'selected' : '' }}>Alpha</option>
                            </select>

                            @if (request('rombel_id') || request('status') || request('search') || $tanggal !== now()->toDateString())
                                <a href="{{ route('dashboard.presensi.index', ['tab' => 'log']) }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter" data-live-reset>
                                    <i class="fas fa-undo"></i>
                                </a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="logSearchInput" name="search" value="{{ request('search') }}"
                                placeholder="Cari nama / NISN..." autocomplete="off">
                            <button type="button" id="clearLogSearch"
                                class="clear-search {{ request('search') ? 'visible' : '' }}" title="Hapus pencarian">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- 2. Konten Datatable Responsive Stack -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd table-presensi-log"
                    style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">
                                No</th>
                            <th class="sortable-th {{ $sortLog === 'nama_siswa' ? 'sorted' : '' }}"
                                data-sort="nama_siswa"
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                                Nama Peserta Didik
                                <span class="sort-icon">{!! $sortLog === 'nama_siswa' ? ($sortLogDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sortLog === 'nama_rombel' ? 'sorted' : '' }}"
                                data-sort="nama_rombel"
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                                Rombel
                                <span class="sort-icon">{!! $sortLog === 'nama_rombel' ? ($sortLogDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sortLog === 'jam_masuk' ? 'sorted' : '' }}" data-sort="jam_masuk"
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                                Jam Masuk
                                <span class="sort-icon">{!! $sortLog === 'jam_masuk' ? ($sortLogDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sortLog === 'jam_pulang' ? 'sorted' : '' }}"
                                data-sort="jam_pulang"
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                                Jam Pulang
                                <span class="sort-icon">{!! $sortLog === 'jam_pulang' ? ($sortLogDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sortLog === 'status' ? 'sorted' : '' }}" data-sort="status"
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; cursor: pointer;">
                                Status
                                <span class="sort-icon">{!! $sortLog === 'status' ? ($sortLogDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Keterangan</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                Snapshot</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $index => $log)
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                <td class="cell-log-no"
                                    style="padding: 14px 16px; text-align: center; color: var(--text-muted);"
                                    data-label="No">
                                    {{ $logs->firstItem() + $index }}
                                </td>
                                <td class="cell-log-siswa" style="padding: 14px 18px;" data-label="Nama Lengkap">
                                    <div class="log-siswa-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                        <img src="{{ $log->foto_profil_url ?: asset('img/logo-dark.png') }}"
                                            alt="{{ $log->nama_siswa }}" class="log-avatar"
                                            style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--border-color); flex-shrink: 0; {{ $log->foto_profil_url ? 'cursor: pointer;' : '' }}"
                                            title="{{ $log->foto_profil_url ? 'Klik untuk memperbesar foto profil' : '' }}"
                                            onerror="this.src='/img/logo-dark.png';">
                                        <div>
                                            <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                                {{ $log->nama_siswa }}</div>
                                            <div
                                                style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                                NISN: {{ $log->nisn_siswa }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="cell-log-rombel" style="padding: 14px 16px;" data-label="Rombel Kelas">
                                    <span class="badge badge-outline"
                                        style="font-size: var(--text-sm, 0.8125rem); padding: 3px 8px;">{{ $log->nama_rombel ?: '-' }}</span>
                                </td>
                                <td class="cell-log-masuk" style="padding: 14px 16px;" data-label="Presensi Masuk">
                                    @if ($log->jam_masuk)
                                        <div
                                            style="font-weight: 700; font-family: monospace; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                            {{ substr($log->jam_masuk, 0, 5) }} WIB
                                        </div>
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                            <i
                                                class="fas fa-rss me-1"></i>{{ strtoupper($log->metode_masuk ?: 'manual') }}
                                        </div>
                                    @else
                                        <span style="color: var(--text-muted); font-size: var(--text-base, 0.875rem);">-</span>
                                    @endif
                                </td>
                                <td class="cell-log-pulang" style="padding: 14px 16px;" data-label="Presensi Pulang">
                                    @if ($log->jam_pulang)
                                        <div
                                            style="font-weight: 700; font-family: monospace; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                            {{ substr($log->jam_pulang, 0, 5) }} WIB
                                        </div>
                                        <div style="font-size: var(--text-xs, 0.75rem); margin-top: 2px;">
                                            @if ($log->status_ketepatan_pulang === 'pulang_cepat')
                                                <span class="badge"
                                                    style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); font-size: var(--text-2xs, 0.6875rem); padding: 1px 6px;">
                                                    <i class="fas fa-person-walking-arrow-right me-1"></i> Pulang Cepat
                                                </span>
                                            @else
                                                <span style="color: var(--text-muted);"><i
                                                        class="fas fa-rss me-1"></i>{{ strtoupper($log->metode_pulang ?: 'manual') }}</span>
                                            @endif
                                        </div>
                                    @elseif ($log->status_ketepatan_pulang === 'pulang_cepat')
                                        <div>
                                            <span class="badge"
                                                style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">
                                                <i class="fas fa-person-walking-arrow-right me-1"></i> Pulang Cepat
                                            </span>
                                            <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); margin-top: 2px;">
                                                Tanpa
                                                Tap Pulang</div>
                                        </div>
                                    @else
                                        <span style="color: var(--text-muted); font-size: var(--text-base, 0.875rem);">-</span>
                                    @endif
                                </td>
                                <td class="cell-log-status" style="padding: 14px 16px; text-align: center;"
                                    data-label="Status Kehadiran">
                                    @if ($log->status === 'H')
                                        <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                                class="fas fa-check-circle me-1"></i> Hadir</span>
                                    @elseif ($log->status === 'T')
                                        <span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"
                                            title="Terlambat {{ $log->menit_terlambat }} menit">
                                            <i class="fas fa-clock me-1"></i> +{{ $log->menit_terlambat }}m
                                        </span>
                                    @elseif ($log->status === 'I')
                                        <span class="badge badge-primary" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                                class="fas fa-file-signature me-1"></i> Izin</span>
                                    @elseif ($log->status === 'S')
                                        <span class="badge"
                                            style="background: rgba(139,92,246,0.15); color: #8b5cf6; font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                                class="fas fa-notes-medical me-1"></i> Sakit</span>
                                    @elseif ($log->status === 'D')
                                        <span class="badge badge-accent" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                                class="fas fa-award me-1"></i> Dispen</span>
                                    @else
                                        <span class="badge badge-danger" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                                class="fas fa-times-circle me-1"></i> Alpha</span>
                                    @endif
                                </td>
                                <td class="cell-log-keterangan" style="padding: 14px 16px;" data-label="Keterangan">
                                    <div style="max-width: 220px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                        title="{{ $log->keterangan }}">
                                        {{ $log->keterangan ?: '-' }}
                                    </div>
                                </td>
                                <td class="cell-log-snapshot" style="padding: 14px 18px; text-align: right;"
                                    data-label="Snapshot Foto">
                                    <div class="snapshot-actions"
                                        style="display: flex; justify-content: flex-end; gap: 6px;">
                                        @if ($log->foto_masuk_url)
                                            <button type="button" class="btn btn-outline btn-preview-snapshot"
                                                data-url="{{ $log->foto_masuk_url }}"
                                                data-title="Snapshot Masuk — {{ $log->nama_siswa }}"
                                                style="padding: 5px 9px; font-size: var(--text-xs, 0.75rem);"
                                                title="Lihat Snapshot Masuk">
                                                <i class="fas fa-camera text-primary"></i> Masuk
                                            </button>
                                        @endif
                                        @if ($log->foto_pulang_url)
                                            <button type="button" class="btn btn-outline btn-preview-snapshot"
                                                data-url="{{ $log->foto_pulang_url }}"
                                                data-title="Snapshot Pulang — {{ $log->nama_siswa }}"
                                                style="padding: 5px 9px; font-size: var(--text-xs, 0.75rem);"
                                                title="Lihat Snapshot Pulang">
                                                <i class="fas fa-camera text-success"></i> Pulang
                                            </button>
                                        @endif
                                        @if (!$log->foto_masuk_url && !$log->foto_pulang_url)
                                            <span style="color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">-</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <i class="fas fa-clipboard-question"
                                        style="font-size: var(--text-3xl, 1.875rem); margin-bottom: 10px; display: block; opacity: 0.4;"></i>
                                    Belum ada data presensi yang tercatat untuk filter tanggal dan rombel ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 3. Footer Paginasi Terpadu Datatable -->
            @if ($logs->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $logs])
                </div>
            @endif
        </div>
    </div>

    <!-- TAB 2: Rekapitulasi & Laporan -->
