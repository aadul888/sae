<!-- Unified Datatable Agenda Kelas (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
<div class="card datatable-card-wrapper" id="tableDataContainer">
    <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
    <div class="datatable-toolbar">
        <div class="agenda-toolbar-flex"
            style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; width: 100%;">
            <div class="agenda-filters-group" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <div class="toolbar-entries">
                    <label for="perPageSelect"
                        style="margin: 0; font-size: var(--text-base, 0.875rem); color: var(--text-muted);">Tampilkan</label>
                    <select id="perPageSelect" class="per-page-select" style="height: 38px;">
                        @foreach ([10, 15, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>
                                {{ $n }}</option>
                        @endforeach
                    </select>
                    <span style="font-size: var(--text-base, 0.875rem); color: var(--text-muted);">entri</span>
                </div>

                <!-- Filter Rentang Tanggal Inline -->
                <div class="filter-daterange-box"
                    style="display: inline-flex; align-items: center; gap: 6px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); padding: 0 10px; height: 38px; box-sizing: border-box; flex-shrink: 0;">
                    <i class="fas fa-calendar-day text-primary" style="font-size: var(--text-sm, 0.8125rem);" title="Rentang Tanggal"></i>
                    <input type="date" id="filterTanggalMulai" value="{{ $tanggalMulai }}" title="Tanggal Mulai"
                        style="background: transparent; border: none; color: var(--text-color); font-size: var(--text-sm, 0.8125rem); padding: 0; outline: none; width: 125px; cursor: pointer;">
                    <span style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">&ndash;</span>
                    <input type="date" id="filterTanggalSelesai" value="{{ $tanggalSelesai }}"
                        title="Tanggal Selesai"
                        style="background: transparent; border: none; color: var(--text-color); font-size: var(--text-sm, 0.8125rem); padding: 0; outline: none; width: 125px; cursor: pointer;">
                </div>

                <!-- Filter Guru Pengampu -->
                <select id="filterPtk" class="toolbar-filter-select" style="min-width: 160px; height: 38px;">
                    <option value="">Semua Guru Pengampu</option>
                    @foreach ($guruMapelList as $g)
                        <option value="{{ $g->ptk_id }}" {{ $filterPtk === $g->ptk_id ? 'selected' : '' }}>
                            {{ $g->nama_guru }}
                        </option>
                    @endforeach
                </select>

                <!-- Filter Mapel -->
                <select id="filterMapel" class="toolbar-filter-select" style="min-width: 160px; height: 38px;">
                    <option value="">Semua Mata Pelajaran</option>
                    @foreach ($mapelList as $m)
                        <option value="{{ $m }}" {{ $filterMapel === $m ? 'selected' : '' }}>
                            {{ $m }}
                        </option>
                    @endforeach
                </select>

                <!-- Filter Status KBM -->
                <select id="filterStatus" class="toolbar-filter-select" style="min-width: 140px; height: 38px;">
                    <option value="">Semua Status</option>
                    <option value="Terlaksana" {{ $filterStatus === 'Terlaksana' ? 'selected' : '' }}>
                        Terlaksana
                    </option>
                    <option value="Sebagian" {{ $filterStatus === 'Sebagian' ? 'selected' : '' }}>Sebagian
                    </option>
                    <option value="Tertunda" {{ $filterStatus === 'Tertunda' ? 'selected' : '' }}>Tertunda
                    </option>
                    <option value="Digantikan" {{ $filterStatus === 'Digantikan' ? 'selected' : '' }}>
                        Digantikan
                    </option>
                </select>

                @if ($q || $filterPtk || $filterMapel || $filterStatus || $tanggalMulai || $tanggalSelesai)
                    <button type="button" id="btnResetFilter" class="btn btn-outline"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                        title="Reset filter">
                        <i class="fas fa-undo"></i>
                    </button>
                @endif
            </div>

            <!-- Live Search Box -->
            <div class="live-search-wrap" style="height: 38px;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearchInput" placeholder="Cari materi, guru, mapel, tugas..."
                    value="{{ $q }}" autocomplete="off">
                <button type="button" class="clear-search {{ $q ? 'visible' : '' }}" title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Konten Datatable Responsive Stack -->
    <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
        <table class="table table-pd table-wali-agenda"
            style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr
                    style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color); text-align: left;">
                    <th class="th-no d-none-mobile"
                        style="padding: 13px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 48px; text-align: center;">
                        No
                    </th>
                    <th class="sortable-th {{ $sort === 'tanggal' ? 'sorted' : '' }}" data-sort="tanggal"
                        style="padding: 13px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 150px; cursor: pointer;">
                        <i class="fas fa-calendar-day me-1 text-primary"></i> Tanggal &amp; Jam
                        <span class="sort-icon">{!! $sort === 'tanggal' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                    </th>
                    <th class="sortable-th {{ $sort === 'pertemuan_ke' ? 'sorted' : '' }}" data-sort="pertemuan_ke"
                        style="padding: 13px 12px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 80px; cursor: pointer;">
                        <i class="fas fa-hashtag me-1 text-primary"></i> Pert.
                        <span class="sort-icon">{!! $sort === 'pertemuan_ke' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                    </th>
                    <th
                        style="padding: 13px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 180px;">
                        <i class="fas fa-chalkboard-user me-1 text-primary"></i> Guru Pengampu
                    </th>
                    <th class="sortable-th {{ $sort === 'nama_mata_pelajaran' ? 'sorted' : '' }}"
                        data-sort="nama_mata_pelajaran"
                        style="padding: 13px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 180px; cursor: pointer;">
                        <i class="fas fa-book me-1 text-primary"></i> Mata Pelajaran
                        <span class="sort-icon">{!! $sort === 'nama_mata_pelajaran' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                    </th>
                    <th
                        style="padding: 13px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 260px;">
                        <i class="fas fa-file-lines me-1 text-primary"></i> Materi &amp; Uraian Pembelajaran
                    </th>
                    <th class="sortable-th {{ $sort === 'status_kbm' ? 'sorted' : '' }}" data-sort="status_kbm"
                        style="padding: 13px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 130px; cursor: pointer;">
                        <i class="fas fa-signal me-1 text-primary"></i> Status
                        <span class="sort-icon">{!! $sort === 'status_kbm' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                    </th>
                    <th
                        style="padding: 13px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; min-width: 120px;">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $index => $item)
                    @php
                        $rowNum = ($list->currentPage() - 1) * $list->perPage() + $index + 1;
                    @endphp
                    <tr class="agenda-row"
                        style="border-bottom: 1px solid var(--border-color); transition: background 0.18s ease;">
                        <!-- No Urut (Desktop Only) -->
                        <td class="cell-pd-no d-none-mobile" data-label="No"
                            style="padding: 12px 14px; text-align: center; color: var(--text-muted); font-size: var(--text-sm, 0.8125rem); font-weight: 700;">
                            {{ $rowNum }}
                        </td>

                        <!-- Tanggal & Jam -->
                        <td class="cell-pd-tanggal" data-label="Tanggal &amp; Jam" style="padding: 12px 16px;">
                            <div class="cell-col-right">
                                <div
                                    style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem); white-space: nowrap;">
                                    {{ $item->tanggal ? $item->tanggal->translatedFormat('d M Y') : '-' }}
                                </div>
                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                    {{ $item->hari }} &bull; Jam {{ $item->jam_ke_mulai }} s.d.
                                    {{ $item->jam_ke_selesai }}
                                </div>
                            </div>
                        </td>

                        <!-- Pertemuan Ke -->
                        <td class="cell-pd-pertemuan" data-label="Pertemuan Ke"
                            style="padding: 12px 12px; text-align: center;">
                            <div class="cell-col-right" style="align-items: center;">
                                <span class="badge"
                                    style="background: rgba(99,102,241,0.12); color: var(--primary); font-weight: 800; font-size: var(--text-sm, 0.8125rem); padding: 3px 8px; border-radius: var(--radius-sm, 6px);">
                                    #{{ $item->pertemuan_ke }}
                                </span>
                            </div>
                        </td>

                        <!-- Guru Pengampu -->
                        <td class="cell-pd-guru" data-label="Guru Pengampu" style="padding: 12px 16px;">
                            <div class="cell-col-right">
                                <div
                                    style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem); display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-chalkboard-user text-primary" style="font-size: var(--text-sm, 0.8125rem);"></i>
                                    <span>{{ $item->nama_guru ?: '-' }}</span>
                                </div>
                                @if (!empty($item->created_by))
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                        <i class="fas fa-pen-nib me-1" style="font-size: var(--text-2xs, 0.6875rem);"></i>
                                        {{ $item->created_by }}
                                    </div>
                                @endif
                            </div>
                        </td>

                        <!-- Mata Pelajaran -->
                        <td class="cell-pd-mapel" data-label="Mata Pelajaran" style="padding: 12px 16px;">
                            <div class="cell-col-right">
                                <div
                                    style="font-weight: 600; color: var(--text-color); font-size: var(--text-base, 0.875rem); line-height: 1.35;">
                                    {{ $item->nama_mata_pelajaran }}
                                </div>
                            </div>
                        </td>

                        <!-- Materi & Uraian Kegiatan -->
                        <td class="cell-pd-materi" data-label="Materi &amp; Uraian" style="padding: 12px 16px;">
                            <div class="cell-col-right">
                                <div
                                    style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem); margin-bottom: 2px; line-height: 1.35;">
                                    {{ $item->materi_pokok }}
                                </div>
                                @if (!empty($item->uraian_kegiatan))
                                    <div
                                        style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        {{ $item->uraian_kegiatan }}
                                    </div>
                                @endif
                                @if (!empty($item->penugasan))
                                    <div
                                        style="font-size: var(--text-xs, 0.75rem); color: var(--success, #10b981); margin-top: 3px; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-tasks"></i>
                                        <span>{{ \Illuminate\Support\Str::limit($item->penugasan, 45) }}</span>
                                    </div>
                                @endif
                            </div>
                        </td>

                        <!-- Status KBM -->
                        <td class="cell-pd-status" data-label="Status KBM"
                            style="padding: 12px 16px; text-align: center;">
                            <div class="cell-col-right" style="align-items: center;">
                                {!! $item->status_badge !!}
                            </div>
                        </td>

                        <!-- Aksi -->
                        <td class="cell-pd-aksi" data-label="Aksi" style="padding: 12px 18px; text-align: right;">
                            <div class="table-actions"
                                style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                <button type="button" class="btn-icon btn-detail-agenda"
                                    data-id="{{ $item->id }}" title="Lihat Detail Agenda"
                                    style="width: 32px; height: 32px; border-radius: var(--radius-sm, 6px);">
                                    <i class="fas fa-eye"></i>
                                </button>

                                @if ($canUpdate)
                                    <button type="button" class="btn-icon btn-edit-agenda"
                                        data-id="{{ $item->id }}" title="Edit Agenda Kelas"
                                        style="width: 32px; height: 32px; border-radius: var(--radius-sm, 6px); color: var(--warning, #f59e0b);">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                @endif

                                @if ($canDelete)
                                    <button type="button" class="btn-icon btn-delete-agenda"
                                        data-id="{{ $item->id }}" title="Hapus Agenda Kelas"
                                        style="width: 32px; height: 32px; border-radius: var(--radius-sm, 6px); color: var(--danger, #ef4444);">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="cell-empty"
                            style="padding: 36px 20px; text-align: center; color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                            <i class="fas fa-book-open mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.4;"></i>
                            <div style="font-weight: 700; color: var(--text-color); margin-bottom: 4px;">Belum
                                Ada Agenda Kelas</div>
                            <div style="font-size: var(--text-sm, 0.8125rem); max-width: 480px; margin: 0 auto;">
                                Belum ada rekaman agenda kegiatan belajar mengajar untuk rombel ini yang sesuai
                                dengan kriteria filter.
                            </div>
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
