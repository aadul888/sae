{{-- Partial Datatable Presensi Mengajar Baku SAE --}}
<table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
    <thead>
        <tr style="border-bottom: 1px solid var(--border-color); text-align: left;">
            <th style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 45px; text-align: center;">No</th>
            <th class="sortable-th {{ ($sort ?? '') === 'tanggal' ? 'sorted' : '' }}" data-sort="tanggal" style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                <i class="fas fa-calendar-day me-1 text-primary"></i> Tanggal
                <span class="sort-icon">{!! ($sort ?? '') === 'tanggal' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
            </th>
            <th class="sortable-th {{ ($sort ?? '') === 'jam_ke_mulai' ? 'sorted' : '' }}" data-sort="jam_ke_mulai" style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer; width: 110px;">
                <i class="fas fa-clock me-1 text-primary"></i> Jam
                <span class="sort-icon">{!! ($sort ?? '') === 'jam_ke_mulai' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
            </th>
            <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                <i class="fas fa-chalkboard me-1 text-primary"></i> Kelas
            </th>
            <th class="sortable-th {{ ($sort ?? '') === 'nama_mata_pelajaran' ? 'sorted' : '' }}" data-sort="nama_mata_pelajaran" style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                <i class="fas fa-book me-1 text-primary"></i> Mapel
                <span class="sort-icon">{!! ($sort ?? '') === 'nama_mata_pelajaran' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
            </th>
            <th class="sortable-th {{ ($sort ?? '') === 'total_jp' ? 'sorted' : '' }}" data-sort="total_jp" style="padding: 12px 12px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer; text-align: center; width: 75px;">
                <i class="fas fa-hourglass-half me-1 text-primary"></i> JP
                <span class="sort-icon">{!! ($sort ?? '') === 'total_jp' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
            </th>
            <th class="sortable-th {{ ($sort ?? '') === 'status' ? 'sorted' : '' }}" data-sort="status" style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer; text-align: center; width: 135px;">
                <i class="fas fa-signal me-1 text-primary"></i> Status
                <span class="sort-icon">{!! ($sort ?? '') === 'status' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
            </th>
            <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 115px;">
                <i class="fas fa-users me-1 text-primary"></i> Siswa
            </th>
            <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">
                <i class="fas fa-sliders me-1 text-primary"></i> Aksi
            </th>
        </tr>
    </thead>
    <tbody id="tableBodyContent">
        @forelse ($items as $index => $item)
            <tr class="data-row" style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;">
                <td data-label="No" style="padding: 12px 14px; text-align: center; color: var(--text-muted); font-size: 0.82rem;">
                    {{ $items->firstItem() + $index }}
                </td>
                <td data-label="Tanggal" style="padding: 12px 16px;">
                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.88rem;">
                        {{ $item->tanggal ? $item->tanggal->translatedFormat('d M Y') : '-' }}
                    </div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; margin-top: 2px;">
                        <span class="badge" style="background: rgba(99,102,241,0.08); color: var(--primary); font-size: 0.7rem; padding: 2px 5px;">{{ $item->hari }}</span>
                        @if ($item->jam_masuk)
                            <span><i class="far fa-clock me-1"></i>{{ substr($item->jam_masuk, 0, 5) }}</span>
                        @endif
                    </div>
                </td>
                <td data-label="Jam Ke" style="padding: 12px 14px;">
                    <span class="badge" style="background: var(--bg-hover); color: var(--text-color); font-weight: 700; font-size: 0.76rem; border: 1px solid var(--border-color);">
                        {{ $item->jam_ke_mulai }}-{{ $item->jam_ke_selesai }}
                    </span>
                </td>
                <td data-label="Kelas" style="padding: 12px 16px;">
                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.88rem; display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-chalkboard text-primary" style="font-size: 0.8rem;"></i>
                        <span>{{ $item->nama_rombel }}</span>
                    </div>
                    @if ($item->jadwal && $item->jadwal->ruangan)
                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                            <i class="fas fa-location-dot me-1"></i>{{ $item->jadwal->ruangan }}
                        </div>
                    @endif
                </td>
                <td data-label="Mapel" style="padding: 12px 16px;">
                    <div style="font-weight: 600; color: var(--text-color); font-size: 0.86rem;">
                        {{ $item->nama_mata_pelajaran }}
                    </div>
                    @if ($item->keterangan)
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $item->keterangan }}">
                            {{ $item->keterangan }}
                        </div>
                    @endif
                </td>
                <td data-label="Beban" style="padding: 12px 12px; text-align: center;">
                    <span class="badge" style="background: rgba(16,185,129,0.1); color: #10b981; font-weight: 700; font-size: 0.8rem; padding: 4px 8px;">
                        <i class="fas fa-bolt me-1"></i>{{ $item->total_jp }} JP
                    </span>
                </td>
                <td data-label="Status" style="padding: 12px 16px; text-align: center;">
                    {!! $item->status_badge !!}
                    @if ($item->status === 'D' && $item->nama_guru_pengganti)
                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px;" title="Inval: {{ $item->nama_guru_pengganti }}">
                            <i class="fas fa-user-clock me-1 text-danger"></i>{{ \Illuminate\Support\Str::limit($item->nama_guru_pengganti, 14) }}
                        </div>
                    @endif
                </td>
                <td data-label="Siswa" style="padding: 12px 16px; text-align: center;">
                    @if (!is_null($item->jumlah_siswa_hadir))
                        <div style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.82rem;">
                            <span title="{{ $item->jumlah_siswa_hadir }} Hadir" style="color: #10b981; font-weight: 700;">
                                <i class="fas fa-user-check me-1"></i>{{ $item->jumlah_siswa_hadir }}
                            </span>
                            @if (!empty($item->jumlah_siswa_tidak_hadir))
                                <span title="{{ $item->jumlah_siswa_tidak_hadir }} Tidak Hadir" style="color: #ef4444; font-weight: 600; font-size: 0.78rem;">
                                    <i class="fas fa-user-xmark me-1"></i>{{ $item->jumlah_siswa_tidak_hadir }}
                                </span>
                            @endif
                        </div>
                    @else
                        <span style="color: var(--text-muted); font-size: 0.8rem;">-</span>
                    @endif
                </td>
                <td data-label="Aksi" style="padding: 12px 18px; text-align: center;">
                    <div class="table-actions" style="display: inline-flex; gap: 6px; align-items: center; justify-content: center;">
                        <button type="button" class="btn-icon btn-detail-row" data-id="{{ $item->id }}" title="Detail Presensi"
                            style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--primary); cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-eye"></i>
                        </button>
                        @if ($canUpdate)
                            <button type="button" class="btn-icon btn-edit-row" data-id="{{ $item->id }}" title="Edit Presensi"
                                style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: #f59e0b; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-pen-to-square"></i>
                            </button>
                        @endif
                        @if ($canDelete)
                            <button type="button" class="btn-icon btn-delete-row" data-id="{{ $item->id }}" data-name="{{ $item->nama_mata_pelajaran }} ({{ $item->nama_rombel }})" title="Hapus Presensi"
                                style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(239,68,68,0.25); background: rgba(239,68,68,0.06); color: #ef4444; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-trash-can"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr class="empty-row">
                <td colspan="9" class="cell-empty" style="text-align: center; padding: 42px 16px; color: var(--text-muted);">
                    <div style="width: 52px; height: 52px; border-radius: 50%; background: rgba(99,102,241,0.08); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 10px auto;">
                        <i class="fas fa-calendar-xmark"></i>
                    </div>
                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.92rem; margin-bottom: 3px;">Belum Ada Presensi Mengajar</div>
                    <div style="font-size: 0.8rem; max-width: 380px; margin: 0 auto;">Catat kehadiran mengajar melalui kartu jadwal hari ini atau tombol tambah di atas.</div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- Paginasi Baku SAE (DILARANG MENGGUNAKAN $items->links()) --}}
@if ($items->hasPages())
    <div class="custom-pagination" style="display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; border-top: 1px solid var(--border-color); flex-wrap: wrap; gap: 10px;">
        <div style="font-size: 0.8rem; color: var(--text-muted);">
            Menampilkan <strong>{{ $items->firstItem() }}</strong> - <strong>{{ $items->lastItem() }}</strong> dari <strong>{{ $items->total() }}</strong>
        </div>
        <div style="display: flex; align-items: center; gap: 4px;">
            @if ($items->onFirstPage())
                <span class="page-btn disabled" style="padding: 6px 12px; border-radius: 6px; border: 1px solid var(--border-color); color: var(--text-muted); opacity: 0.5; cursor: not-allowed;"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $items->previousPageUrl() }}" class="page-btn ajax-page-link" title="Sebelumnya" style="padding: 6px 12px; border-radius: 6px; border: 1px solid var(--border-color); color: var(--text-color); text-decoration: none;"><i class="fas fa-chevron-left"></i></a>
            @endif
            @php
                $cur = $items->currentPage();
                $last = $items->lastPage();
                $from = max(1, $cur - 2);
                $to = min($last, $cur + 2);
            @endphp
            @if ($from > 1)
                <a href="{{ $items->url(1) }}" class="page-btn ajax-page-link" style="padding: 6px 12px; border-radius: 6px; border: 1px solid var(--border-color); color: var(--text-color); text-decoration: none;">1</a>
                @if ($from > 2) <span class="page-info" style="padding: 0 4px; color: var(--text-muted);">&hellip;</span> @endif
            @endif
            @for ($i = $from; $i <= $to; $i++)
                <a href="{{ $items->url($i) }}" class="page-btn ajax-page-link {{ $i === $cur ? 'current' : '' }}" style="padding: 6px 12px; border-radius: 6px; border: 1px solid {{ $i === $cur ? 'var(--primary)' : 'var(--border-color)' }}; background: {{ $i === $cur ? 'var(--primary)' : 'transparent' }}; color: {{ $i === $cur ? '#fff' : 'var(--text-color)' }}; font-weight: {{ $i === $cur ? '700' : '500' }}; text-decoration: none;">{{ $i }}</a>
            @endfor
            @if ($to < $last)
                @if ($to < $last - 1) <span class="page-info" style="padding: 0 4px; color: var(--text-muted);">&hellip;</span> @endif
                <a href="{{ $items->url($last) }}" class="page-btn ajax-page-link" style="padding: 6px 12px; border-radius: 6px; border: 1px solid var(--border-color); color: var(--text-color); text-decoration: none;">{{ $last }}</a>
            @endif
            @if ($items->hasMorePages())
                <a href="{{ $items->nextPageUrl() }}" class="page-btn ajax-page-link" title="Selanjutnya" style="padding: 6px 12px; border-radius: 6px; border: 1px solid var(--border-color); color: var(--text-color); text-decoration: none;"><i class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled" style="padding: 6px 12px; border-radius: 6px; border: 1px solid var(--border-color); color: var(--text-muted); opacity: 0.5; cursor: not-allowed;"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    </div>
@endif
