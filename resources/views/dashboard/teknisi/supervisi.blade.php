@extends('layouts.dashboard')

@section('title', 'Supervisi Teknisi & IT - SAE')

@section('content')
    @php
        $statCards = [
            ['icon' => 'fas fa-clipboard-check', 'bg' => 'rgba(59,130,246,0.12)', 'color' => 'var(--primary, #3b82f6)', 'value' => number_format($statTotal, 0, ',', '.'), 'label' => 'Lembar Supervisi'],
            ['icon' => 'fas fa-stamp', 'bg' => 'rgba(16,185,129,0.12)', 'color' => 'var(--success, #10b981)', 'value' => number_format($statFinal, 0, ',', '.'), 'label' => 'Final'],
            ['icon' => 'fas fa-star', 'bg' => 'rgba(245,158,11,0.12)', 'color' => 'var(--warning, #f59e0b)', 'value' => number_format((float) ($statNilai ?? 0), 2, ',', '.'), 'label' => 'Rata-rata Nilai'],
            ['icon' => 'fas fa-thumbs-up', 'bg' => 'rgba(139,92,246,0.12)', 'color' => 'var(--purple, #8b5cf6)', 'value' => number_format($statBaik, 0, ',', '.'), 'label' => 'Baik / Sangat Baik'],
        ];
    @endphp

    <x-sae.teknisi-header
        banner-title="Supervisi Teknisi & IT"
        banner-desc="Lembar observasi supervisi 5 indikator tupoksi — penilaian skor 1–4 beserta catatan/rekomendasi supervisor."
        banner-icon="fas fa-clipboard-check"
        banner-color="var(--primary, #3b82f6)"
        :create-route="route('dashboard.teknisi.supervisi.store')"
        :filter-route="route('dashboard.teknisi.supervisi.index')"
        modal-id="modalSupervisi"
        :stat-cards="$statCards"
        :per-page="$perPage"
        :search="$search"
        :cetak-route="route('dashboard.teknisi.laporan.cetak', ['modul' => 'supervisi'])"
        import-modal-id="modalImportTeknisi"
        :can-create="$canCreate"
        :can-import="$canCreate">

        <x-slot:filter>
            <select name="status" class="toolbar-filter-select" style="height:38px; min-width:130px;">
                <option value="">Semua Status</option>
                @foreach ($statusList as $k => $v)
                    <option value="{{ $k }}" {{ $statusFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
            <div class="toolbar-filter-date" title="Dari Tanggal">
                <i class="fas fa-calendar-alt" style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);"></i>
                <input type="date" name="dari" value="{{ $dari }}">
            </div>
            <div class="toolbar-filter-date" title="Sampai Tanggal">
                <i class="fas fa-calendar-alt" style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);"></i>
                <input type="date" name="sampai" value="{{ $sampai }}">
            </div>
        </x-slot:filter>

        <thead>
            <tr style="background-color: var(--table-header-bg, #f8fafc); border-bottom: 1px solid var(--border-color);">
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">No</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Nomor & Tanggal</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Teknisi</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Supervisor</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Nilai & Kualifikasi</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase; text-align:center; width:130px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($list as $row)
                @php
                    $qColor = [
                        'Sangat Baik' => ['#dcfce7', '#15803d'],
                        'Baik' => ['#dbeafe', '#1d4ed8'],
                        'Cukup' => ['#fef3c7', '#92400e'],
                        'Perlu Pembinaan' => ['#fee2e2', '#b91c1c'],
                    ][$row->kualifikasi] ?? ['#f1f5f9', '#475569'];
                    $items = $itemsBySupervisi[$row->id] ?? collect();
                @endphp
                <tr style="border-bottom:1px solid var(--border-color);">
                    <td style="padding:14px 18px; color:var(--text-muted);">{{ $list->firstItem() + $loop->index }}</td>
                    <td style="padding:14px 18px;">
                        <div style="font-weight:700; font-family:monospace; color:var(--text-heading);">{{ $row->nomor_supervisi }}</div>
                        <div style="font-size:var(--text-xs); color:var(--text-muted);">{{ \Carbon\Carbon::parse($row->tanggal_supervisi)->format('d M Y') }}</div>
                        @if ($row->periode_label)<div style="font-size:var(--text-xs); color:var(--text-muted);">{{ $row->periode_label }}</div>@endif
                    </td>
                    <td style="padding:14px 18px; font-weight:600; color:var(--text-heading);">{{ $row->teknisi_nama }}</td>
                    <td style="padding:14px 18px;">
                        <div style="font-weight:600; color:var(--text-heading);">{{ $row->supervisor_nama }}</div>
                        @if ($row->supervisor_jabatan)<div style="font-size:var(--text-xs); color:var(--text-muted);">{{ $row->supervisor_jabatan }}</div>@endif
                    </td>
                    <td style="padding:14px 18px;">
                        <div style="font-size:var(--text-lg); font-weight:800; color:var(--text-heading);">{{ number_format((float) $row->nilai_akhir, 2, ',', '.') }}</div>
                        @if ($row->kualifikasi)
                            <span class="badge" style="background:{{ $qColor[0] }}; color:{{ $qColor[1] }}; font-size:var(--text-xs); padding:3px 8px;">{{ $row->kualifikasi }}</span>
                        @endif
                        <div style="margin-top:4px;">
                            <span class="badge" style="background:{{ $row->status === 'final' ? '#dcfce7' : '#f1f5f9' }}; color:{{ $row->status === 'final' ? '#15803d' : '#475569' }}; font-size:var(--text-xs); padding:2px 7px;">{{ $statusList[$row->status] ?? $row->status }}</span>
                        </div>
                    </td>
                    <td style="padding:14px 18px; text-align:center;">
                        <div style="display:flex; gap:6px; justify-content:center;">
                            <a href="{{ route('dashboard.teknisi.supervisi.cetak', $row->id) }}" target="_blank" class="btn-icon" title="Cetak Lembar Supervisi"
                                style="background:rgba(59,130,246,0.12); color:var(--primary); width:32px; height:32px; border-radius:var(--radius-sm, 6px); display:inline-flex; align-items:center; justify-content:center; text-decoration:none;">
                                <i class="fas fa-print" style="font-size:var(--text-sm);"></i>
                            </a>
                            @if ($canUpdate)
                                <button type="button" class="btn-icon" title="Ubah"
                                    data-teknisi-form="modalSupervisi"
                                    data-edit-route="{{ route('dashboard.teknisi.supervisi.update', $row->id) }}"
                                    data-title="Ubah Lembar Supervisi"
                                    data-field-tanggal_supervisi="{{ $row->tanggal_supervisi }}"
                                    data-field-periode_label="{{ $row->periode_label }}"
                                    data-field-tahun_ajaran="{{ $row->tahun_ajaran }}"
                                    data-field-semester="{{ $row->semester }}"
                                    data-field-teknisi_nama="{{ $row->teknisi_nama }}"
                                    data-field-teknisi_ptk_id="{{ $row->teknisi_ptk_id }}"
                                    data-field-supervisor_nama="{{ $row->supervisor_nama }}"
                                    data-field-supervisor_ptk_id="{{ $row->supervisor_ptk_id }}"
                                    data-field-supervisor_jabatan="{{ $row->supervisor_jabatan }}"
                                    data-field-catatan_umum="{{ $row->catatan_umum }}"
                                    data-field-rekomendasi="{{ $row->rekomendasi }}"
                                    data-field-status="{{ $row->status }}"
                                    @foreach ($items as $it)
                                        data-field-skor_{{ $it->no_indikator }}="{{ $it->skor }}"
                                        data-field-catatan_{{ $it->no_indikator }}="{{ $it->catatan_supervisor }}"
                                    @endforeach
                                    style="background:var(--primary); color:#fff; width:32px; height:32px; border-radius:var(--radius-sm, 6px); border:none; cursor:pointer;">
                                    <i class="fas fa-pen" style="font-size:var(--text-sm);"></i>
                                </button>
                            @endif
                            @if ($canDelete)
                                <form action="{{ route('dashboard.teknisi.supervisi.destroy', $row->id) }}" method="POST" data-confirm="delete" data-confirm-message="Hapus lembar supervisi ini?" style="display:inline;">
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
                <tr><td colspan="6" style="text-align:center; padding:36px; color:var(--text-muted);">
                    <i class="fas fa-clipboard-check" style="font-size:var(--text-3xl,1.875rem); opacity:0.4;"></i>
                    <p style="margin:10px 0 0;">Belum ada lembar supervisi. Klik tombol + untuk membuat penilaian baru.</p>
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
        'modalId' => 'modalSupervisi',
        'modalTitle' => 'Tambah Lembar Supervisi',
        'modalAction' => route('dashboard.teknisi.supervisi.store'),
        'modalBodyView' => 'dashboard.teknisi._partials.form-supervisi',
    ])

    @include('dashboard.teknisi._partials.modal-import', [
        'modalId' => 'modalImportTeknisi',
        'title' => 'Import Lembar Supervisi',
        'templateRoute' => route('dashboard.teknisi.laporan.template', ['modul' => 'supervisi']),
        'actionRoute' => route('dashboard.teknisi.laporan.import', ['modul' => 'supervisi']),
    ])
@endsection

@push('scripts')
    @include('dashboard.teknisi._partials.scripts')
@endpush
