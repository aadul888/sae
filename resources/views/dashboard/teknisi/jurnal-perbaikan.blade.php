@extends('layouts.dashboard')

@section('title', 'Jurnal Perbaikan Sarana Prasarana - Teknisi')

@section('content')
    @php
        $statCards = [
            ['icon' => 'fas fa-screwdriver-wrench', 'bg' => 'rgba(59,130,246,0.12)', 'color' => 'var(--primary, #3b82f6)', 'value' => number_format($statTotal, 0, ',', '.'), 'label' => 'Total Jurnal'],
            ['icon' => 'fas fa-wrench', 'bg' => 'rgba(16,185,129,0.12)', 'color' => 'var(--success, #10b981)', 'value' => number_format($statPerbaikan, 0, ',', '.'), 'label' => 'Perbaikan'],
            ['icon' => 'fas fa-box-open', 'bg' => 'rgba(245,158,11,0.12)', 'color' => 'var(--warning, #f59e0b)', 'value' => number_format($statKebutuhan, 0, ',', '.'), 'label' => 'Kebutuhan Sarana'],
            ['icon' => 'fas fa-circle-check', 'bg' => 'rgba(139,92,246,0.12)', 'color' => 'var(--purple, #8b5cf6)', 'value' => number_format($statSelesai, 0, ',', '.'), 'label' => 'Selesai'],
        ];
    @endphp

    <x-sae.teknisi-header
        banner-title="Pengarsipan & Laporan Perbaikan Sarana Prasarana"
        banner-desc="Bukti fisik Indikator #5 — jurnal kegiatan perbaikan dan kebutuhan sarana prasarana."
        banner-icon="fas fa-screwdriver-wrench"
        banner-color="var(--primary, #3b82f6)"
        :create-route="route('dashboard.teknisi.jurnal-perbaikan.store')"
        :filter-route="route('dashboard.teknisi.jurnal-perbaikan.index')"
        modal-id="modalJurnalPerbaikan"
        :stat-cards="$statCards"
        :per-page="$perPage"
        :search="$search"
        :cetak-route="route('dashboard.teknisi.laporan.cetak', ['modul' => 'jurnal-perbaikan', 'dari' => now()->startOfMonth()->toDateString(), 'sampai' => now()->endOfMonth()->toDateString()])"
        import-modal-id="modalImportTeknisi"
        :can-create="$canCreate"
        :can-import="$canCreate">

        <x-slot:filter>
            <select name="jenis" class="toolbar-filter-select" style="height:38px; min-width:150px;">
                <option value="">Semua Jenis</option>
                @foreach ($jenisList as $k => $v)
                    <option value="{{ $k }}" {{ $jenisFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
            <select name="kategori" class="toolbar-filter-select" style="height:38px; min-width:150px;">
                <option value="">Semua Kategori</option>
                @foreach ($kategoriList as $k => $v)
                    <option value="{{ $k }}" {{ $kategoriFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
            <input type="date" name="dari" value="{{ $dari }}" class="form-control" style="height:38px;" title="Dari Tanggal">
            <input type="date" name="sampai" value="{{ $sampai }}" class="form-control" style="height:38px;" title="Sampai Tanggal">
        </x-slot:filter>

        <thead>
            <tr style="background-color: var(--table-header-bg, #f8fafc); border-bottom: 1px solid var(--border-color);">
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">No</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Nomor & Tanggal</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Lokasi & Jenis</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Uraian</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Status</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase; text-align:center; width:110px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($list as $row)
                <tr style="border-bottom:1px solid var(--border-color);">
                    <td style="padding:14px 18px; color:var(--text-muted);">{{ $list->firstItem() + $loop->index }}</td>
                    <td style="padding:14px 18px;">
                        <div style="font-weight:700; font-family:monospace; color:var(--text-heading);">{{ $row->nomor_jurnal }}</div>
                        <div style="font-size:var(--text-xs); color:var(--text-muted);">{{ \Carbon\Carbon::parse($row->tanggal)->format('d M Y') }}</div>
                    </td>
                    <td style="padding:14px 18px;">
                        <div style="font-weight:600; color:var(--text-heading);"><i class="fas fa-location-dot" style="color:var(--danger,#ef4444);"></i> {{ $row->lokasi_unit }}</div>
                        <span class="badge" style="background:rgba(59,130,246,0.12); color:var(--primary); font-size:var(--text-xs); padding:3px 8px; margin-top:4px; display:inline-block;">{{ $jenisList[$row->jenis_pekerjaan] ?? $row->jenis_pekerjaan }}</span>
                        @if ($row->kategori_sarana)<span style="font-size:var(--text-xs); color:var(--text-muted);"> {{ $kategoriList[$row->kategori_sarana] ?? $row->kategori_sarana }}</span>@endif
                    </td>
                    <td style="padding:14px 18px;">
                        <div style="font-size:var(--text-sm); color:var(--text-heading); max-width:300px;">{{ \Illuminate\Support\Str::limit($row->uraian_kegiatan, 90) }}</div>
                        @if ($row->kebutuhan_sarana)<div style="font-size:var(--text-xs); color:var(--warning,#f59e0b); margin-top:2px;"><i class="fas fa-box-open"></i> {{ \Illuminate\Support\Str::limit($row->kebutuhan_sarana, 50) }}</div>@endif
                    </td>
                    <td style="padding:14px 18px;">
                        @php $sc = ['draf' => ['#f1f5f9', '#475569'], 'selesai' => ['#dcfce7', '#15803d'], 'terverifikasi' => ['#dbeafe', '#1d4ed8']][$row->status] ?? ['#f1f5f9', '#475569']; @endphp
                        <span class="badge" style="background:{{ $sc[0] }}; color:{{ $sc[1] }}; font-size:var(--text-sm); padding:4px 10px;">{{ $statusList[$row->status] ?? $row->status }}</span>
                    </td>
                    <td style="padding:14px 18px; text-align:center;">
                        <div style="display:flex; gap:6px; justify-content:center;">
                            @if ($canUpdate)
                                <button type="button" class="btn-icon" title="Ubah"
                                    data-teknisi-form="modalJurnalPerbaikan"
                                    data-edit-route="{{ route('dashboard.teknisi.jurnal-perbaikan.update', $row->id) }}"
                                    data-title="Ubah Jurnal Perbaikan"
                                    data-field-tanggal="{{ $row->tanggal }}"
                                    data-field-lokasi_unit="{{ $row->lokasi_unit }}"
                                    data-field-jenis_pekerjaan="{{ $row->jenis_pekerjaan }}"
                                    data-field-kategori_sarana="{{ $row->kategori_sarana }}"
                                    data-field-uraian_kegiatan="{{ $row->uraian_kegiatan }}"
                                    data-field-hasil="{{ $row->hasil }}"
                                    data-field-kebutuhan_sarana="{{ $row->kebutuhan_sarana }}"
                                    data-field-kendala="{{ $row->kendala }}"
                                    data-field-tindak_lanjut="{{ $row->tindak_lanjut }}"
                                    data-field-estimasi_biaya="{{ $row->estimasi_biaya }}"
                                    data-field-realisasi_biaya="{{ $row->realisasi_biaya }}"
                                    data-field-status="{{ $row->status }}"
                                    style="background:var(--primary); color:#fff; width:32px; height:32px; border-radius:6px; border:none; cursor:pointer;">
                                    <i class="fas fa-pen" style="font-size:var(--text-sm);"></i>
                                </button>
                            @endif
                            @if ($canDelete)
                                <form action="{{ route('dashboard.teknisi.jurnal-perbaikan.destroy', $row->id) }}" method="POST" data-confirm="delete" data-confirm-message="Hapus jurnal perbaikan ini?" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon" title="Hapus"
                                        style="background:#fee2e2; color:var(--danger,#ef4444); width:32px; height:32px; border-radius:6px; border:none; cursor:pointer;">
                                        <i class="fas fa-trash-alt" style="font-size:var(--text-sm);"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; padding:36px; color:var(--text-muted);">
                    <i class="fas fa-screwdriver-wrench" style="font-size:var(--text-3xl,1.875rem); opacity:0.4;"></i>
                    <p style="margin:10px 0 0;">Belum ada jurnal perbaikan.</p>
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
        'modalId' => 'modalJurnalPerbaikan',
        'modalTitle' => 'Tambah Jurnal Perbaikan',
        'modalAction' => route('dashboard.teknisi.jurnal-perbaikan.store'),
        'modalBodyView' => 'dashboard.teknisi._partials.form-jurnal-perbaikan',
    ])

    @include('dashboard.teknisi._partials.modal-import', [
        'modalId' => 'modalImportTeknisi',
        'title' => 'Import Jurnal Perbaikan',
        'templateRoute' => route('dashboard.teknisi.laporan.template', ['modul' => 'jurnal-perbaikan']),
        'actionRoute' => route('dashboard.teknisi.laporan.import', ['modul' => 'jurnal-perbaikan']),
    ])
@endsection

@push('scripts')
    @include('dashboard.teknisi._partials.scripts')
@endpush
