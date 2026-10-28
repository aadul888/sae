        <!-- ==================== TAMPILAN TABEL DATA REKAP ==================== -->
        <!-- Unified Datatable Rekap Jadwal KBM (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
            <div class="datatable-toolbar">
                <div
                    style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <div class="toolbar-entries">
                            <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                            <select id="perPageSelect" class="per-page-select" style="height: 38px;">
                                @foreach ([10, 15, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>
                                        {{ $n }}</option>
                                @endforeach
                            </select>
                            <span>entri</span>
                        </div>

                        <select id="filterHari" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Hari</option>
                            @foreach (\App\Models\JadwalKbm::HARI_LIST as $h)
                                <option value="{{ $h }}" {{ $hari === $h ? 'selected' : '' }}>
                                    {{ $h }}
                                </option>
                            @endforeach
                        </select>

                        <select id="filterRombel" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Rombel</option>
                            @foreach ($rombelList as $r)
                                <option value="{{ $r->rombongan_belajar_id }}"
                                    {{ $rombelId === $r->rombongan_belajar_id ? 'selected' : '' }}>
                                    {{ $r->nama }}
                                </option>
                            @endforeach
                        </select>

                        <select id="filterGuru" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Guru Pengampu</option>
                            @foreach ($guruList as $g)
                                <option value="{{ $g->ptk_id }}" {{ $guruId === $g->ptk_id ? 'selected' : '' }}>
                                    {{ $g->nama }}
                                </option>
                            @endforeach
                        </select>

                        @if ($q || $hari || $rombelId || $guruId)
                            <a href="{{ route('dashboard.jadwal-kbm.index', ['view_mode' => 'table']) }}"
                                class="btn btn-outline"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                title="Reset filter">
                                <i class="fas fa-undo"></i>
                            </a>
                        @endif
                    </div>

                    <div class="live-search-wrap" style="height: 38px;">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="liveSearchInput" placeholder="Cari mapel, rombel, guru, ruang..."
                            value="{{ $q }}" autocomplete="off">
                        <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}"
                            title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Konten Datatable Responsive Stack -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: var(--bg-hover);">
                            <th
                                style="width: 45px; text-align: center; padding: 12px 8px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                No
                            </th>
                            <th class="sortable-th {{ $sort === 'hari' ? 'sorted' : '' }}" data-sort="hari"
                                style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Hari &amp; Waktu
                                <span class="sort-icon">{!! $sort === 'hari' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sort === 'nama_rombel' ? 'sorted' : '' }}"
                                data-sort="nama_rombel"
                                style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Rombel / Kelas
                                <span class="sort-icon">{!! $sort === 'nama_rombel' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sort === 'nama_mata_pelajaran' ? 'sorted' : '' }}"
                                data-sort="nama_mata_pelajaran"
                                style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Mata Pelajaran
                                <span class="sort-icon">{!! $sort === 'nama_mata_pelajaran' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sort === 'nama_guru' ? 'sorted' : '' }}" data-sort="nama_guru"
                                style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Guru Pengampu
                                <span class="sort-icon">{!! $sort === 'nama_guru' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th
                                style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Ruangan
                            </th>
                            @if ($canUpdate || $canDelete)
                                <th
                                    style="width: 100px; text-align: center; padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Aksi
                                </th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $index => $item)
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;">
                                <td
                                    style="text-align: center; padding: 12px 8px; color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                                    {{ $list->firstItem() + $index }}
                                </td>
                                <td style="padding: 12px 14px;">
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                        @php
                                            $hariBadgeColors = [
                                                'Senin' => 'background: rgba(99,102,241,0.15); color: #6366f1;',
                                                'Selasa' => 'background: rgba(16,185,129,0.15); color: #10b981;',
                                                'Rabu' => 'background: rgba(245,158,11,0.15); color: #f59e0b;',
                                                'Kamis' => 'background: rgba(6,182,212,0.15); color: #06b6d4;',
                                                'Jumat' => 'background: rgba(236,72,153,0.15); color: #ec4899;',
                                                'Sabtu' => 'background: rgba(139,92,246,0.15); color: #8b5cf6;',
                                            ];
                                            $badgeStyle =
                                                $hariBadgeColors[$item->hari] ??
                                                'background: rgba(107,114,128,0.15); color: #6b7280;';
                                        @endphp
                                        <span class="badge"
                                            style="{{ $badgeStyle }} font-weight: 700; padding: 3px 8px; border-radius: var(--radius-xs, 4px); font-size: var(--text-xs, 0.75rem);">
                                            {{ $item->hari }}
                                        </span>
                                        <span style="font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color);">
                                            {{ substr($item->jam_mulai, 0, 5) }} -
                                            {{ substr($item->jam_selesai, 0, 5) }}
                                        </span>
                                    </div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                        <i class="fas fa-layer-group me-1"></i>
                                        @if ($item->jam_ke_mulai === $item->jam_ke_selesai)
                                            Jam ke-{{ $item->jam_ke_mulai }} (1 JP)
                                        @else
                                            Jam ke-{{ $item->jam_ke_mulai }} s.d {{ $item->jam_ke_selesai }}
                                            ({{ $item->jam_ke_selesai - $item->jam_ke_mulai + 1 }} JP)
                                        @endif
                                    </div>
                                </td>
                                <td style="padding: 12px 14px;">
                                    <span class="badge badge-primary"
                                        style="font-size: var(--text-sm, 0.8125rem); padding: 4px 8px; font-weight: 700;">
                                        <i class="fas fa-chalkboard me-1"></i> {{ $item->nama_rombel ?: '-' }}
                                    </span>
                                </td>
                                <td style="padding: 12px 14px;">
                                    <div style="font-weight: 600; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                        {{ $item->nama_mata_pelajaran }}
                                    </div>
                                    @if (!empty($item->keterangan))
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                            <i class="fas fa-info-circle me-1"></i> {{ $item->keterangan }}
                                        </div>
                                    @endif
                                </td>
                                <td style="padding: 12px 14px;">
                                    <div style="font-weight: 600; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                        {{ $item->nama_guru ?: 'Belum Ditugaskan' }}
                                    </div>
                                    @if (!empty($item->nip_guru))
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                            NIP: {{ $item->nip_guru }}
                                        </div>
                                    @endif
                                </td>
                                <td style="padding: 12px 14px;">
                                    @if (!empty($item->ruangan))
                                        <span class="badge"
                                            style="background: var(--bg-hover); color: var(--text-color); border: 1px solid var(--border-color); font-size: var(--text-sm, 0.8125rem);">
                                            <i class="fas fa-door-open me-1"></i> {{ $item->ruangan }}
                                        </span>
                                    @else
                                        <span style="color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">-</span>
                                    @endif
                                </td>
                                @if ($canUpdate || $canDelete)
                                    <td style="text-align: center; padding: 12px 14px;">
                                        <div class="table-actions"
                                            style="display: flex; gap: 6px; justify-content: center;">
                                            @if ($canUpdate)
                                                <button type="button" class="btn-icon btn-edit-jadwal"
                                                    title="Edit Jadwal" data-id="{{ $item->id }}"
                                                    data-rombel="{{ $item->rombongan_belajar_id }}"
                                                    data-pembelajaran="{{ $item->pembelajaran_id }}"
                                                    data-hari="{{ $item->hari }}"
                                                    data-jam-ke-mulai="{{ $item->jam_ke_mulai }}"
                                                    data-jam-ke-selesai="{{ $item->jam_ke_selesai }}"
                                                    data-jam-mulai="{{ substr($item->jam_mulai, 0, 5) }}"
                                                    data-jam-selesai="{{ substr($item->jam_selesai, 0, 5) }}"
                                                    data-ruangan="{{ $item->ruangan }}"
                                                    data-keterangan="{{ $item->keterangan }}">
                                                    <i class="fas fa-pencil text-primary"></i>
                                                </button>
                                            @endif

                                            @if ($canDelete)
                                                <form action="{{ route('dashboard.jadwal-kbm.destroy', $item->id) }}"
                                                    method="POST" data-confirm="delete"
                                                    data-name="{{ $item->nama_mata_pelajaran }} ({{ $item->nama_rombel }})"
                                                    style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-icon" title="Hapus Jadwal">
                                                        <i class="fas fa-trash text-danger"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canUpdate || $canDelete ? 7 : 6 }}"
                                    style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                    <div style="font-size: var(--text-3xl, 1.875rem); margin-bottom: 10px; color: var(--border-color);">
                                        <i class="fas fa-calendar-xmark"></i>
                                    </div>
                                    <div
                                        style="font-weight: 700; font-size: var(--text-md, 1rem); color: var(--text-color); margin-bottom: 4px;">
                                        Belum Ada Jadwal KBM
                                    </div>
                                    <p
                                        style="font-size: var(--text-base, 0.875rem); margin-bottom: 16px; max-width: 420px; margin-left: auto; margin-right: auto;">
                                        Data jadwal pembelajaran mingguan belum tersedia atau tidak cocok dengan filter
                                        pencarian.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 3. Footer Paginasi Terpadu Datatable -->
            @if ($list->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $list])
                </div>
            @endif
        </div>