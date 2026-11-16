{{-- Partial Datatable Jurnal & Agenda KBM Baku SAE --}}
<table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
    <thead>
        <tr style="border-bottom: 1px solid var(--border-color); text-align: left;">
            <th
                style="padding: 9px 10px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 40px; text-align: center;">
                No</th>
            <th class="sortable-th {{ ($sort ?? '') === 'tanggal' ? 'sorted' : '' }}" data-sort="tanggal"
                style="padding: 9px 12px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer; width: 125px;">
                <i class="fas fa-calendar-day me-1 text-primary"></i> Tanggal
                <span class="sort-icon">{!! ($sort ?? '') === 'tanggal' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
            </th>
            <th class="sortable-th {{ ($sort ?? '') === 'pertemuan_ke' ? 'sorted' : '' }}" data-sort="pertemuan_ke"
                style="padding: 9px 8px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer; text-align: center; width: 65px;">
                <i class="fas fa-hashtag me-1 text-primary"></i> Pert.
                <span class="sort-icon">{!! ($sort ?? '') === 'pertemuan_ke' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
            </th>
            <th
                style="padding: 9px 12px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 110px;">
                <i class="fas fa-chalkboard me-1 text-primary"></i> Kelas
            </th>
            <th class="sortable-th {{ ($sort ?? '') === 'nama_mata_pelajaran' ? 'sorted' : '' }}"
                data-sort="nama_mata_pelajaran"
                style="padding: 9px 12px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer; width: 150px; max-width: 155px;">
                <i class="fas fa-book me-1 text-primary"></i> Mapel
                <span class="sort-icon">{!! ($sort ?? '') === 'nama_mata_pelajaran'
                    ? (($sortDir ?? '') === 'asc'
                        ? '&#9650;'
                        : '&#9660;')
                    : '&#9650;&#9660;' !!}</span>
            </th>
            <th
                style="padding: 9px 12px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 260px; max-width: 260px;">
                <i class="fas fa-file-lines me-1 text-primary"></i> Materi &amp; Uraian
            </th>
            <th class="sortable-th {{ ($sort ?? '') === 'status_kbm' ? 'sorted' : '' }}" data-sort="status_kbm"
                style="padding: 9px 12px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer; text-align: center; width: 110px;">
                <i class="fas fa-signal me-1 text-primary"></i> Status
                <span class="sort-icon">{!! ($sort ?? '') === 'status_kbm' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
            </th>
            <th
                style="padding: 9px 12px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 95px;">
                <i class="fas fa-sliders me-1 text-primary"></i> Aksi
            </th>
        </tr>
    </thead>
    <tbody id="tableBodyContent">
        @forelse ($items as $index => $item)
            <tr class="data-row"
                style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;">
                <td data-label="No"
                    style="padding: 8px 10px; text-align: center; color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">
                    <span class="badge"
                        style="background: rgba(99,102,241,0.08); color: var(--primary); font-weight: 700; font-size: var(--text-2xs, 0.6875rem); padding: 2px 5px;">
                        #{{ $items->firstItem() + $index }}
                    </span>
                </td>
                <td data-label="Tanggal" style="padding: 8px 12px;">
                    <div class="cell-col-right">
                        <div
                            style="font-weight: 700; color: var(--text-color); font-size: var(--text-sm, 0.8125rem); white-space: nowrap;">
                            {{ $item->tanggal ? $item->tanggal->translatedFormat('d M Y') : '-' }}
                        </div>
                        <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); margin-top: 1px;">
                            {{ $item->hari }} &bull; Jam {{ $item->jam_ke_mulai }}-{{ $item->jam_ke_selesai }}
                        </div>
                    </div>
                </td>
                <td data-label="Pert." style="padding: 8px 8px; text-align: center;">
                    <div class="cell-col-right" style="align-items: center;">
                        <span class="badge"
                            style="background: rgba(99,102,241,0.12); color: var(--primary); font-weight: 800; font-size: var(--text-xs, 0.75rem); padding: 2px 6px; border-radius: var(--radius-sm, 4px);">
                            #{{ $item->pertemuan_ke }}
                        </span>
                    </div>
                </td>
                <td data-label="Kelas" style="padding: 8px 12px;">
                    <div class="cell-col-right">
                        <div
                            style="font-weight: 600; color: var(--text-color); font-size: var(--text-sm, 0.8125rem); display: inline-flex; align-items: center; gap: 4px;">
                            <i class="fas fa-chalkboard text-primary" style="font-size: var(--text-xs, 0.75rem);"></i>
                            <span>{{ $item->nama_rombel }}</span>
                        </div>
                    </div>
                </td>
                <td data-label="Mapel" style="padding: 8px 12px; text-align: left; width: 150px; max-width: 155px;">
                    <div class="cell-col-left" style="align-items: flex-start; text-align: left; max-width: 100%;">
                        <div class="text-clamp-2" style="font-weight: 600; color: var(--text-color); font-size: var(--text-sm, 0.8125rem); line-height: 1.25; text-align: left; max-width: 100%;" title="{{ $item->nama_mata_pelajaran }}">
                            {{ $item->nama_mata_pelajaran }}
                        </div>
                    </div>
                </td>
                <td data-label="Materi &amp; Uraian" style="padding: 8px 12px; text-align: left; width: 260px; max-width: 260px;">
                    <div class="cell-col-left" style="align-items: flex-start; text-align: left; max-width: 100%;">
                        <div class="text-clamp-1"
                            style="font-weight: 600; color: var(--text-color); font-size: var(--text-sm, 0.8125rem); margin-bottom: 2px; line-height: 1.25; text-align: left; max-width: 100%;" title="{{ $item->materi_pokok }}">
                            {{ $item->materi_pokok }}
                        </div>
                        @if (!empty($item->uraian_kegiatan))
                            <div class="text-clamp-1"
                                style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); line-height: 1.25; text-align: left; max-width: 100%;" title="{{ $item->uraian_kegiatan }}">
                                {{ $item->uraian_kegiatan }}
                            </div>
                        @endif
                        @if ($item->penugasan)
                            <div
                                style="font-size: var(--text-2xs, 0.6875rem); color: var(--success, #10b981); margin-top: 2px; display: inline-flex; align-items: center; gap: 3px; text-align: left; max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $item->penugasan }}">
                                <i class="fas fa-tasks"></i>
                                <span>{{ \Illuminate\Support\Str::limit($item->penugasan, 35) }}</span>
                            </div>
                        @endif
                    </div>
                </td>
                <td data-label="Status" style="padding: 8px 12px; text-align: center;">
                    <div class="cell-col-right" style="align-items: center;">
                        {!! $item->status_badge !!}
                    </div>
                </td>
                <td data-label="Aksi" style="padding: 8px 12px; text-align: center;">
                    <div class="cell-col-right" style="align-items: center;">
                        <div class="table-actions"
                            style="display: inline-flex; gap: 4px; align-items: center; justify-content: center;">
                            <button type="button" class="btn-icon btn-detail-agenda" data-id="{{ $item->id }}"
                                title="Detail Agenda"
                                style="width: 28px; height: 28px; border-radius: var(--radius-md, 6px); border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--primary); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: var(--text-xs, 0.75rem);">
                                <i class="fas fa-eye"></i>
                            </button>
                            @if ($canUpdate)
                                <button type="button" class="btn-icon btn-edit-agenda" data-id="{{ $item->id }}"
                                    title="Edit Agenda"
                                    style="width: 28px; height: 28px; border-radius: var(--radius-md, 6px); border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--warning, #f59e0b); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: var(--text-xs, 0.75rem);">
                                    <i class="fas fa-pen-to-square"></i>
                                </button>
                            @endif
                            @if ($canDelete)
                                <button type="button" class="btn-icon btn-delete-agenda" data-id="{{ $item->id }}"
                                    data-name="Pertemuan #{{ $item->pertemuan_ke }} - {{ $item->materi_pokok }}"
                                    title="Hapus Agenda"
                                    style="width: 28px; height: 28px; border-radius: var(--radius-md, 6px); border: 1px solid rgba(239,68,68,0.25); background: rgba(239,68,68,0.06); color: var(--danger, #ef4444); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: var(--text-xs, 0.75rem);">
                                    <i class="fas fa-trash-can"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 42px 16px; color: var(--text-muted);">
                    <div
                        style="width: 52px; height: 52px; border-radius: 50%; background: rgba(99,102,241,0.08); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); margin: 0 auto 10px auto;">
                        <i class="fas fa-book-open-reader"></i>
                    </div>
                    <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem); margin-bottom: 3px;">
                        Belum Ada Jurnal Agenda KBM</div>
                    <div style="font-size: var(--text-sm, 0.8125rem); max-width: 380px; margin: 0 auto;">Catat materi pokok dan uraian
                        kegiatan KBM melalui tombol Tambah Agenda.</div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
