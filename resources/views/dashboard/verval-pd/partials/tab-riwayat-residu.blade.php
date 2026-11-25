{{-- TAB RIWAYAT RESIDU: satu baris = satu siswa, dengan seluruh jejak peristiwanya --}}
@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $riwayatSiswa */
    $tipeOptions = [
        '' => 'Semua Jenis Peristiwa',
        'import' => 'Import Dukcapil',
        'sel' => 'Ubah Sel (✓/✗)',
        'status' => 'Ubah Status Proses',
        'catatan' => 'Catatan Masalah',
    ];
    $labelPerTipe = [
        'import' => 'Import',
        'sel' => 'Ubah Sel',
        'status' => 'Ubah Status',
        'catatan' => 'Catatan',
    ];
    $metaPerTipe = [
        'import' => ['fa-file-import', 'var(--accent, #0284c7)'],
        'sel' => ['fa-check-double', 'var(--primary, #3b82f6)'],
        'status' => ['fa-arrows-rotate', 'var(--warning, #f59e0b)'],
        'catatan' => ['fa-note-sticky', 'var(--purple, #8b5cf6)'],
    ];
@endphp

<div class="datatable-toolbar">
    <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            <div class="toolbar-entries">
                <label for="riwayatPerPageSelect" style="margin: 0;">Tampilkan</label>
                <select id="riwayatPerPageSelect" name="rperPage" class="per-page-select" style="height: 38px;"
                    data-riwayat-perpage>
                    @foreach ([10, 15, 25, 50, 100] as $n)
                        <option value="{{ $n }}" {{ ($riwayatPerPage ?? 15) == $n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
                <span>siswa</span>
            </div>

            <select id="riwayatTipeFilter" name="rtipe" class="toolbar-filter-select" style="height: 38px;" data-riwayat-tipe>
                @foreach ($tipeOptions as $val => $label)
                    <option value="{{ $val }}" {{ ($riwayatTipe ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            @if (!empty($riwayatQ) || !empty($riwayatTipe))
                <a href="{{ route('dashboard.verval-pd.residu', ['tab' => 'riwayat']) }}"
                    class="btn btn-outline"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                    title="Reset filter riwayat">
                    <i class="fas fa-undo"></i>
                </a>
            @endif
        </div>

        <div class="live-search-wrap" style="height: 38px;">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="riwayatSearch" name="rq" placeholder="Cari nama / NISN..." autocomplete="off"
                value="{{ $riwayatQ ?? '' }}" data-riwayat-search>
        </div>
    </div>
</div>

<div class="table-responsive-stack" style="width: 100%;">
    <div style="display: flex; flex-direction: column; gap: 10px;">
        @forelse ($riwayatSiswa as $row)
        @php
            $curProses = $row->status_proses ?: 'belum';
            $prosesLabel = [
                'belum' => ['Belum Diproses', 'var(--text-muted)', 'rgba(148,163,184,0.15)'],
                'proses' => ['Sedang Proses', 'var(--warning, #f59e0b)', 'rgba(245,158,11,0.15)'],
                'selesai' => ['Selesai', 'var(--success, #10b981)', 'rgba(16,185,129,0.15)'],
            ][$curProses] ?? ['Belum Diproses', 'var(--text-muted)', 'rgba(148,163,184,0.15)'];
        @endphp
        <div class="card riwayat-siswa-card"
            style="border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); padding: 0; overflow: hidden; margin: 0;">
            <button type="button" class="riwayat-siswa-head"
                style="width: 100%; text-align: left; background: var(--bg-hover); border: 0; cursor: pointer; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 12px 16px;">
                <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                    <div
                        style="width: 36px; height: 36px; border-radius: var(--radius-md, 8px); background: rgba(6,182,212,0.12); color: var(--accent); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $row->nama ?: '-' }}
                        </div>
                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-family: var(--font-family-mono, monospace);">
                            NISN: {{ $row->nisn ?: '-' }} &bull; {{ $row->total }} peristiwa tercatat
                        </div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span class="badge"
                        style="background: {{ $prosesLabel[2] }}; color: {{ $prosesLabel[1] }}; font-size: var(--text-xs, 0.75rem); padding: 3px 10px; border-radius: var(--radius-pill, 999px); font-weight: 700;">
                        {{ $prosesLabel[0] }}
                    </span>
                    <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                        Terakhir: {{ $row->update_terakhir ? \Illuminate\Support\Carbon::parse($row->update_terakhir)->format('d/m/Y H:i') : '-' }}
                    </span>
                    <i class="fas fa-chevron-down riwayat-siswa-caret"
                        style="color: var(--text-muted); transition: transform 0.2s ease;"></i>
                </div>
            </button>

            <div class="riwayat-siswa-body" style="display: none; padding: 8px 16px 16px 16px;">
                <div style="display: flex; flex-wrap: wrap; gap: 16px; margin: 4px 0 12px 0; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                    <span><i class="fas fa-file-import me-1"></i> Import pertama:
                        <strong style="color: var(--text-color);">{{ $row->import_pertama ? \Illuminate\Support\Carbon::parse($row->import_pertama)->format('d/m/Y H:i') : '-' }}</strong>
                    </span>
                    <span><i class="fas fa-pen-to-square me-1"></i> Update terakhir:
                        <strong style="color: var(--text-color);">{{ $row->update_terakhir ? \Illuminate\Support\Carbon::parse($row->update_terakhir)->format('d/m/Y H:i') : '-' }}</strong>
                    </span>
                </div>

                <div style="border-left: 2px solid var(--border-color); margin-left: 8px; padding-left: 16px; display: flex; flex-direction: column; gap: 12px;">
                    @foreach ($row->events as $ev)
                        @php
                            [$icon, $color] = $metaPerTipe[$ev->tipe] ?? ['fa-circle-info', 'var(--text-muted)'];
                            $evLabel = $labelPerTipe[$ev->tipe] ?? $ev->tipe;
                        @endphp
                        <div style="position: relative;">
                            <span
                                style="position: absolute; left: -25px; top: 2px; width: 18px; height: 18px; border-radius: 50%; background: {{ $color }}; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: var(--text-2xs, 0.6875rem);">
                                <i class="fas {{ $icon }}"></i>
                            </span>
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <span style="font-weight: 700; color: {{ $color }}; font-size: var(--text-xs, 0.75rem); text-transform: uppercase;">{{ $evLabel }}</span>
                                <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                    {{ $ev->created_at ? \Illuminate\Support\Carbon::parse($ev->created_at)->format('d/m/Y H:i') : '-' }}
                                </span>
                                @if ($ev->admin_name)
                                    <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">&bull; {{ $ev->admin_name }}</span>
                                @endif
                            </div>
                            <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-color); margin-top: 2px; white-space: pre-wrap; word-break: break-word;">
                                {{ $ev->keterangan ?: $evLabel }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @empty
        <div style="padding: 30px; text-align: center; color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
            <i class="fas fa-clock-rotate-left mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5;"></i>
            <div>Belum ada riwayat. Riwayat akan tercatat mulai dari import berkas Residu berikutnya.</div>
        </div>
        @endforelse
    </div>
</div>

@if ($riwayatSiswa->hasPages())
    <div class="datatable-footer">
        @include('partials.datatable-pagination', ['paginator' => $riwayatSiswa])
    </div>
@endif
