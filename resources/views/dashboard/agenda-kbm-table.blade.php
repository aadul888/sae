{{-- Partial Datatable Jurnal & Agenda KBM Baku SAE --}}
<table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
    <thead>
        <tr style="border-bottom: 1px solid var(--border-color); text-align: left;">
            <th
                style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 45px; text-align: center;">
                No</th>
            <th class="sortable-th {{ ($sort ?? '') === 'tanggal' ? 'sorted' : '' }}" data-sort="tanggal"
                style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer; width: 145px;">
                <i class="fas fa-calendar-day me-1 text-primary"></i> Tanggal
                <span class="sort-icon">{!! ($sort ?? '') === 'tanggal' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
            </th>
            <th class="sortable-th {{ ($sort ?? '') === 'pertemuan_ke' ? 'sorted' : '' }}" data-sort="pertemuan_ke"
                style="padding: 12px 12px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer; text-align: center; width: 80px;">
                <i class="fas fa-hashtag me-1 text-primary"></i> Pert.
                <span class="sort-icon">{!! ($sort ?? '') === 'pertemuan_ke' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
            </th>
            <th
                style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 135px;">
                <i class="fas fa-chalkboard me-1 text-primary"></i> Kelas
            </th>
            <th class="sortable-th {{ ($sort ?? '') === 'nama_mata_pelajaran' ? 'sorted' : '' }}"
                data-sort="nama_mata_pelajaran"
                style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer; width: 170px;">
                <i class="fas fa-book me-1 text-primary"></i> Mapel
                <span class="sort-icon">{!! ($sort ?? '') === 'nama_mata_pelajaran'
                    ? (($sortDir ?? '') === 'asc'
                        ? '&#9650;'
                        : '&#9660;')
                    : '&#9650;&#9660;' !!}</span>
            </th>
            <th
                style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                <i class="fas fa-file-lines me-1 text-primary"></i> Materi &amp; Uraian
            </th>
            <th class="sortable-th {{ ($sort ?? '') === 'status_kbm' ? 'sorted' : '' }}" data-sort="status_kbm"
                style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer; text-align: center; width: 130px;">
                <i class="fas fa-signal me-1 text-primary"></i> Status
                <span class="sort-icon">{!! ($sort ?? '') === 'status_kbm' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
            </th>
            <th
                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">
                <i class="fas fa-sliders me-1 text-primary"></i> Aksi
            </th>
        </tr>
    </thead>
    <tbody id="tableBodyContent">
        @forelse ($items as $index => $item)
            <tr class="data-row"
                style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;">
                <td data-label="No"
                    style="padding: 12px 14px; text-align: center; color: var(--text-muted); font-size: 0.82rem;">
                    <span class="badge"
                        style="background: rgba(99,102,241,0.08); color: var(--primary); font-weight: 700; font-size: 0.74rem;">
                        #{{ $items->firstItem() + $index }}
                    </span>
                </td>
                <td data-label="Tanggal" style="padding: 12px 16px;">
                    <div class="cell-col-right">
                        <div
                            style="font-weight: 700; color: var(--text-color); font-size: 0.86rem; white-space: nowrap;">
                            {{ $item->tanggal ? $item->tanggal->translatedFormat('d M Y') : '-' }}
                        </div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 1px;">
                            {{ $item->hari }} &bull; Jam {{ $item->jam_ke_mulai }}-{{ $item->jam_ke_selesai }}
                        </div>
                    </div>
                </td>
                <td data-label="Pert." style="padding: 12px 12px; text-align: center;">
                    <div class="cell-col-right" style="align-items: center;">
                        <span class="badge"
                            style="background: rgba(99,102,241,0.12); color: var(--primary); font-weight: 800; font-size: 0.8rem; padding: 3px 8px; border-radius: 6px;">
                            #{{ $item->pertemuan_ke }}
                        </span>
                    </div>
                </td>
                <td data-label="Kelas" style="padding: 12px 16px;">
                    <div class="cell-col-right">
                        <div
                            style="font-weight: 700; color: var(--text-color); font-size: 0.86rem; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-chalkboard text-primary" style="font-size: 0.8rem;"></i>
                            <span>{{ $item->nama_rombel }}</span>
                        </div>
                    </div>
                </td>
                <td data-label="Mapel" style="padding: 12px 16px;">
                    <div class="cell-col-right">
                        <div style="font-weight: 600; color: var(--text-color); font-size: 0.86rem; line-height: 1.35;">
                            {{ $item->nama_mata_pelajaran }}
                        </div>
                    </div>
                </td>
                <td data-label="Materi &amp; Uraian" style="padding: 12px 16px;">
                    <div class="cell-col-right">
                        <div
                            style="font-weight: 700; color: var(--text-color); font-size: 0.86rem; margin-bottom: 2px; line-height: 1.35;">
                            {{ $item->materi_pokok }}
                        </div>
                        @if (!empty($item->uraian_kegiatan))
                            <div
                                style="font-size: 0.76rem; color: var(--text-muted); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                {{ $item->uraian_kegiatan }}
                            </div>
                        @endif
                        @if ($item->penugasan)
                            <div
                                style="font-size: 0.72rem; color: #10b981; margin-top: 3px; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="fas fa-tasks"></i>
                                <span>{{ \Illuminate\Support\Str::limit($item->penugasan, 45) }}</span>
                            </div>
                        @endif
                    </div>
                </td>
                <td data-label="Status" style="padding: 12px 16px; text-align: center;">
                    <div class="cell-col-right" style="align-items: center;">
                        {!! $item->status_badge !!}
                    </div>
                </td>
                <td data-label="Aksi" style="padding: 12px 18px; text-align: center;">
                    <div class="cell-col-right" style="align-items: center;">
                        <div class="table-actions"
                            style="display: inline-flex; gap: 6px; align-items: center; justify-content: center;">
                            <button type="button" class="btn-icon btn-detail-agenda" data-id="{{ $item->id }}"
                                title="Detail Agenda"
                                style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--primary); cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-eye"></i>
                            </button>
                            @if ($canUpdate)
                                <button type="button" class="btn-icon btn-edit-agenda" data-id="{{ $item->id }}"
                                    title="Edit Agenda"
                                    style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: #f59e0b; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-pen-to-square"></i>
                                </button>
                            @endif
                            @if ($canDelete)
                                <button type="button" class="btn-icon btn-delete-agenda" data-id="{{ $item->id }}"
                                    data-name="Pertemuan #{{ $item->pertemuan_ke }} - {{ $item->materi_pokok }}"
                                    title="Hapus Agenda"
                                    style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(239,68,68,0.25); background: rgba(239,68,68,0.06); color: #ef4444; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
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
                        style="width: 52px; height: 52px; border-radius: 50%; background: rgba(99,102,241,0.08); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 10px auto;">
                        <i class="fas fa-book-open-reader"></i>
                    </div>
                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.92rem; margin-bottom: 3px;">
                        Belum Ada Jurnal Agenda KBM</div>
                    <div style="font-size: 0.8rem; max-width: 380px; margin: 0 auto;">Catat materi pokok dan uraian
                        kegiatan KBM melalui tombol Tambah Agenda.</div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
