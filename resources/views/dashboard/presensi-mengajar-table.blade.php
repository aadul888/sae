{{-- Partial Datatable Presensi Mengajar Baku SAE --}}
<table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
    <thead>
        <tr style="border-bottom: 1px solid var(--border-color); text-align: left;">
            <th
                style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 45px; text-align: center;">
                No</th>
            <th class="sortable-th {{ ($sort ?? '') === 'tanggal' ? 'sorted' : '' }}" data-sort="tanggal"
                style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                <i class="fas fa-calendar-day me-1 text-primary"></i> Tanggal
                <span class="sort-icon">{!! ($sort ?? '') === 'tanggal' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
            </th>
            <th class="sortable-th {{ ($sort ?? '') === 'jam_ke_mulai' ? 'sorted' : '' }}" data-sort="jam_ke_mulai"
                style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer; width: 110px;">
                <i class="fas fa-clock me-1 text-primary"></i> Jam
                <span class="sort-icon">{!! ($sort ?? '') === 'jam_ke_mulai' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
            </th>
            <th
                style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                <i class="fas fa-chalkboard me-1 text-primary"></i> Kelas
            </th>
            <th class="sortable-th {{ ($sort ?? '') === 'nama_mata_pelajaran' ? 'sorted' : '' }}"
                data-sort="nama_mata_pelajaran"
                style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                <i class="fas fa-book me-1 text-primary"></i> Mapel
                <span class="sort-icon">{!! ($sort ?? '') === 'nama_mata_pelajaran'
                    ? (($sortDir ?? '') === 'asc'
                        ? '&#9650;'
                        : '&#9660;')
                    : '&#9650;&#9660;' !!}</span>
            </th>
            <th class="sortable-th {{ ($sort ?? '') === 'total_jp' ? 'sorted' : '' }}" data-sort="total_jp"
                style="padding: 12px 12px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer; text-align: center; width: 75px;">
                <i class="fas fa-hourglass-half me-1 text-primary"></i> JP
                <span class="sort-icon">{!! ($sort ?? '') === 'total_jp' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
            </th>
            <th class="sortable-th {{ ($sort ?? '') === 'status' ? 'sorted' : '' }}" data-sort="status"
                style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer; text-align: center; width: 135px;">
                <i class="fas fa-signal me-1 text-primary"></i> Status
                <span class="sort-icon">{!! ($sort ?? '') === 'status' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
            </th>
            <th
                style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 115px;">
                <i class="fas fa-users me-1 text-primary"></i> Siswa
            </th>
            <th
                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">
                <i class="fas fa-sliders me-1 text-primary"></i> Aksi
            </th>
        </tr>
    </thead>
    <tbody id="tableBodyContent">
        @forelse ($items as $index => $item)
            @php
                $ruanganNama = trim($item->jadwal?->ruangan ?? '');
                $rombelNama = trim($item->nama_rombel ?? '');
                $isRuanganSama = empty($ruanganNama) || strcasecmp($ruanganNama, $rombelNama) === 0;
            @endphp
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
                        @if ($item->hari)
                            <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 500;">
                                {{ $item->hari }}
                            </div>
                        @endif
                    </div>
                </td>
                <td data-label="Jam Ke" style="padding: 12px 14px; text-align: center;">
                    <div class="cell-col-right" style="align-items: center;">
                        <span class="badge"
                            style="background: var(--bg-hover); color: var(--text-color); font-weight: 700; font-size: 0.76rem; border: 1px solid var(--border-color); padding: 3px 8px;">
                            Jam {{ $item->jam_ke_mulai }}-{{ $item->jam_ke_selesai }}
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
                        @if (!$isRuanganSama)
                            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 1px;">
                                <i class="fas fa-location-dot me-1"></i>{{ $ruanganNama }}
                            </div>
                        @endif
                    </div>
                </td>
                <td data-label="Mapel" style="padding: 12px 16px;">
                    <div class="cell-col-right">
                        <div style="font-weight: 600; color: var(--text-color); font-size: 0.86rem; line-height: 1.35;">
                            {{ $item->nama_mata_pelajaran }}
                        </div>
                        @if (!empty($item->keterangan))
                            <div style="font-size: 0.73rem; color: var(--text-muted); margin-top: 2px; max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                title="{{ $item->keterangan }}">
                                {{ $item->keterangan }}
                            </div>
                        @endif
                    </div>
                </td>
                <td data-label="Beban" style="padding: 12px 12px; text-align: center;">
                    <div class="cell-col-right" style="align-items: center;">
                        <span class="badge"
                            style="background: rgba(16,185,129,0.1); color: #10b981; font-weight: 700; font-size: 0.78rem; padding: 4px 8px;">
                            <i class="fas fa-bolt me-1"></i>{{ $item->total_jp }} JP
                        </span>
                    </div>
                </td>
                <td data-label="Status" style="padding: 12px 16px; text-align: center;">
                    <div class="cell-col-right" style="align-items: center;">
                        {!! $item->status_badge !!}
                        @if ($item->status === 'D' && $item->nama_guru_pengganti)
                            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;"
                                title="Inval: {{ $item->nama_guru_pengganti }}">
                                <i
                                    class="fas fa-user-clock me-1 text-danger"></i>{{ \Illuminate\Support\Str::limit($item->nama_guru_pengganti, 14) }}
                            </div>
                        @endif
                    </div>
                </td>
                <td data-label="Siswa" style="padding: 12px 16px; text-align: center;">
                    <div class="cell-col-right" style="align-items: center;">
                        @if (($item->siswa_total ?? 0) > 0)
                            <div style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem;">
                                <span title="{{ $item->siswa_hadir }} dari {{ $item->siswa_total }} Hadir"
                                    style="color: #10b981; font-weight: 700;">
                                    <i class="fas fa-user-check me-1"></i>{{ $item->siswa_hadir }}
                                </span>
                                <span style="color: var(--text-muted); font-size: 0.74rem;">/
                                    {{ $item->siswa_total }}</span>
                            </div>
                        @elseif (!is_null($item->jumlah_siswa_hadir))
                            <div style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem;">
                                <span style="color: #10b981; font-weight: 700;">
                                    <i class="fas fa-user-check me-1"></i>{{ $item->jumlah_siswa_hadir }}
                                </span>
                            </div>
                        @else
                            <span style="color: var(--text-muted); font-size: 0.8rem;">-</span>
                        @endif
                    </div>
                </td>
                <td data-label="Aksi" style="padding: 12px 18px; text-align: center;">
                    <div class="cell-col-right" style="align-items: center;">
                        <div class="table-actions"
                            style="display: inline-flex; gap: 6px; align-items: center; justify-content: center;">
                            <button type="button" class="btn-icon btn-detail-row" data-id="{{ $item->id }}"
                                title="Detail Presensi"
                                style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--primary); cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-eye"></i>
                            </button>
                            @if ($canUpdate)
                                <button type="button" class="btn-icon btn-edit-row" data-id="{{ $item->id }}"
                                    title="Edit Presensi"
                                    style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: #f59e0b; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-pen-to-square"></i>
                                </button>
                            @endif
                            @if ($canDelete)
                                <button type="button" class="btn-icon btn-delete-row" data-id="{{ $item->id }}"
                                    data-name="{{ $item->nama_mata_pelajaran }} ({{ $item->nama_rombel }})"
                                    title="Hapus Presensi"
                                    style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(239,68,68,0.25); background: rgba(239,68,68,0.06); color: #ef4444; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-trash-can"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        @empty
            <tr class="empty-row">
                <td colspan="9" class="cell-empty"
                    style="text-align: center; padding: 42px 16px; color: var(--text-muted);">
                    <div
                        style="width: 52px; height: 52px; border-radius: 50%; background: rgba(99,102,241,0.08); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 10px auto;">
                        <i class="fas fa-calendar-xmark"></i>
                    </div>
                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.92rem; margin-bottom: 3px;">
                        Belum Ada Presensi Mengajar</div>
                    <div style="font-size: 0.8rem; max-width: 380px; margin: 0 auto;">Catat kehadiran mengajar melalui
                        kartu jadwal hari ini atau tombol tambah di atas.</div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
