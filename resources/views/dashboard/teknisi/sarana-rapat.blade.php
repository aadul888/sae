@extends('layouts.dashboard')

@section('title', 'Laporan Sarana Kegiatan Rapat - Teknisi')

@section('content')
    @php
        $statCards = [
            ['icon' => 'fas fa-clipboard-list', 'bg' => 'rgba(59,130,246,0.12)', 'color' => 'var(--primary, #3b82f6)', 'value' => number_format($statTotal, 0, ',', '.'), 'label' => 'Total Laporan'],
            ['icon' => 'fas fa-circle-check', 'bg' => 'rgba(16,185,129,0.12)', 'color' => 'var(--success, #10b981)', 'value' => number_format($statLengkap, 0, ',', '.'), 'label' => 'Sarana Lengkap'],
            ['icon' => 'fas fa-triangle-exclamation', 'bg' => 'rgba(245,158,11,0.12)', 'color' => 'var(--warning, #f59e0b)', 'value' => number_format($statKurang, 0, ',', '.'), 'label' => 'Sarana Kurang'],
        ];
    @endphp

    <x-sae.teknisi-header
        banner-title="Laporan Sarana Kegiatan Rapat"
        banner-desc="Bukti fisik Indikator #4 — dokumen rekapitulasi dan laporan kelengkapan sarana rapat."
        banner-icon="fas fa-clipboard-check"
        banner-color="var(--warning, #f59e0b)"
        :create-route="route('dashboard.teknisi.sarana-rapat.store')"
        :filter-route="route('dashboard.teknisi.sarana-rapat.index')"
        modal-id="modalSaranaRapat"
        :stat-cards="$statCards"
        :per-page="$perPage"
        :search="$search"
        :cetak-route="route('dashboard.teknisi.laporan.cetak', ['modul' => 'sarana-rapat', 'dari' => now()->startOfMonth()->toDateString(), 'sampai' => now()->endOfMonth()->toDateString()])"
        import-modal-id="modalImportTeknisi"
        :can-create="$canCreate"
        :can-import="$canCreate">

        <x-slot:filter>
            <select name="kelengkapan" class="toolbar-filter-select" style="height:38px; min-width:160px;">
                <option value="">Semua Kelengkapan</option>
                @foreach ($kelengkapanList as $k => $v)
                    <option value="{{ $k }}" {{ $lengkapFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
            <input type="date" name="dari" value="{{ $dari }}" class="form-control" style="height:38px;" title="Dari Tanggal">
            <input type="date" name="sampai" value="{{ $sampai }}" class="form-control" style="height:38px;" title="Sampai Tanggal">
        </x-slot:filter>

        <thead>
            <tr style="background-color: var(--table-header-bg, #f8fafc); border-bottom: 1px solid var(--border-color);">
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">No</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Nomor & Rapat</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Ruang & Peserta</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Kelengkapan</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase; text-align:center; width:110px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($list as $row)
                @php
                    $sc = ['lengkap' => ['#dcfce7', '#15803d'], 'kurang' => ['#fef3c7', '#92400e'], 'tidak_lengkap' => ['#fee2e2', '#b91c1c']][$row->kelengkapan_sarana] ?? ['#f1f5f9', '#475569'];
                @endphp
                <tr style="border-bottom:1px solid var(--border-color);">
                    <td style="padding:14px 18px; color:var(--text-muted);">{{ $list->firstItem() + $loop->index }}</td>
                    <td style="padding:14px 18px;">
                        <div style="font-weight:700; font-family:monospace; color:var(--text-heading);">{{ $row->nomor_laporan }}</div>
                        <div style="font-weight:600; color:var(--text-heading); margin-top:4px;">{{ $row->nama_rapat }}</div>
                        <div style="font-size:var(--text-xs); color:var(--text-muted);">{{ \Carbon\Carbon::parse($row->tanggal_rapat)->format('d M Y') }}</div>
                    </td>
                    <td style="padding:14px 18px;">
                        <div style="font-weight:600; color:var(--text-heading);"><i class="fas fa-location-dot" style="color:var(--danger,#ef4444);"></i> {{ $row->ruang_nama ?: '—' }}</div>
                        @if ($row->jumlah_peserta)<div style="font-size:var(--text-xs); color:var(--text-muted);">{{ $row->jumlah_peserta }} peserta</div>@endif
                    </td>
                    <td style="padding:14px 18px;">
                        <span class="badge" style="background:{{ $sc[0] }}; color:{{ $sc[1] }}; font-size:var(--text-sm); padding:4px 10px;">{{ $kelengkapanList[$row->kelengkapan_sarana] ?? $row->kelengkapan_sarana }}</span>
                        @if ($row->catatan_kebutuhan)<div style="font-size:var(--text-xs); color:var(--warning,#f59e0b); margin-top:4px;"><i class="fas fa-circle-exclamation"></i> {{ \Illuminate\Support\Str::limit($row->catatan_kebutuhan, 50) }}</div>@endif
                    </td>
                    <td style="padding:14px 18px; text-align:center;">
                        <div style="display:flex; gap:6px; justify-content:center;">
                            @if ($canUpdate)
                                <button type="button" class="btn-icon" title="Ubah"
                                    data-teknisi-form="modalSaranaRapat"
                                    data-edit-route="{{ route('dashboard.teknisi.sarana-rapat.update', $row->id) }}"
                                    data-title="Ubah Laporan Sarana Rapat"
                                    data-field-tanggal_rapat="{{ $row->tanggal_rapat }}"
                                    data-field-nama_rapat="{{ $row->nama_rapat }}"
                                    data-field-ruang_nama="{{ $row->ruang_nama }}"
                                    data-field-jumlah_peserta="{{ $row->jumlah_peserta }}"
                                    data-field-kelengkapan_sarana="{{ $row->kelengkapan_sarana }}"
                                    data-field-catatan_kebutuhan="{{ $row->catatan_kebutuhan }}"
                                    data-field-catatan="{{ $row->catatan }}"
                                    style="background:var(--primary); color:#fff; width:32px; height:32px; border-radius:var(--radius-sm, 6px); border:none; cursor:pointer;">
                                    <i class="fas fa-pen" style="font-size:var(--text-sm);"></i>
                                </button>
                            @endif
                            @if ($canDelete)
                                <form action="{{ route('dashboard.teknisi.sarana-rapat.destroy', $row->id) }}" method="POST" data-confirm="delete" data-confirm-message="Hapus laporan sarana rapat ini?" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon" title="Hapus"
                                        style="background:#fee2e2; color:var(--danger,#ef4444); width:32px; height:32px; border-radius:var(--radius-sm, 6px); border:none; cursor:pointer;">
                                        <i class="fas fa-trash-alt" style="font-size:var(--text-sm);"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center; padding:36px; color:var(--text-muted);">
                    <i class="fas fa-clipboard-check" style="font-size:var(--text-3xl,1.875rem); opacity:0.4;"></i>
                    <p style="margin:10px 0 0;">Belum ada laporan sarana rapat.</p>
                </td></tr>
            @endforelse
        </tbody>

        <x-slot:pagination>
            @if ($list->hasPages())
                @include('partials.datatable-pagination', ['paginator' => $list])
            @endif
        </x-slot:pagination>
    </x-sae.teknisi-header>

    @include('dashboard.teknisi._partials.modal', [
        'modalId' => 'modalSaranaRapat',
        'modalTitle' => 'Tambah Laporan Sarana Rapat',
        'modalAction' => route('dashboard.teknisi.sarana-rapat.store'),
        'modalBodyView' => 'dashboard.teknisi._partials.form-sarana-rapat',
    ])

    @include('dashboard.teknisi._partials.modal-import', [
        'modalId' => 'modalImportTeknisi',
        'title' => 'Import Laporan Sarana Rapat',
        'templateRoute' => route('dashboard.teknisi.laporan.template', ['modul' => 'sarana-rapat']),
        'actionRoute' => route('dashboard.teknisi.laporan.import', ['modul' => 'sarana-rapat']),
    ])
@endsection

@push('scripts')
    @include('dashboard.teknisi._partials.scripts')
@endpush
