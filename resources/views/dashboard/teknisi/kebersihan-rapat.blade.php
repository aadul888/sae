@extends('layouts.dashboard')

@section('title', 'Kebersihan Ruang Rapat - Teknisi')

@section('content')
    @php
        $statCards = [
            ['icon' => 'fas fa-broom', 'bg' => 'rgba(59,130,246,0.12)', 'color' => 'var(--primary, #3b82f6)', 'value' => number_format($statTotal, 0, ',', '.'), 'label' => 'Total Kegiatan'],
            ['icon' => 'fas fa-circle-check', 'bg' => 'rgba(16,185,129,0.12)', 'color' => 'var(--success, #10b981)', 'value' => number_format($statBersih, 0, ',', '.'), 'label' => 'Bersih'],
            ['icon' => 'fas fa-triangle-exclamation', 'bg' => 'rgba(245,158,11,0.12)', 'color' => 'var(--warning, #f59e0b)', 'value' => number_format($statTindakan, 0, ',', '.'), 'label' => 'Perlu Tindakan'],
            ['icon' => 'fas fa-people-group', 'bg' => 'rgba(139,92,246,0.12)', 'color' => 'var(--purple, #8b5cf6)', 'value' => number_format($statRapat, 0, ',', '.'), 'label' => 'Ruang Rapat'],
        ];
    @endphp

    <x-sae.teknisi-header
        banner-title="Kebersihan Lingkungan Ruang Rapat"
        banner-desc="Bukti fisik Indikator #3 — kebersihan teras, halaman, dan ruang rapat (sebelum, saat, dan sesudah acara)."
        banner-icon="fas fa-people-roof"
        banner-color="var(--primary-light, #6366f1)"
        :create-route="route('dashboard.teknisi.kebersihan-rapat.store')"
        :filter-route="route('dashboard.teknisi.kebersihan-rapat.index')"
        modal-id="modalKebersihanRapat"
        :stat-cards="$statCards"
        :per-page="$perPage"
        :search="$search"
        :cetak-route="route('dashboard.teknisi.laporan.cetak', ['modul' => 'kebersihan-rapat', 'dari' => now()->startOfMonth()->toDateString(), 'sampai' => now()->endOfMonth()->toDateString()])"
        import-modal-id="modalImportTeknisi"
        :can-create="$canCreate"
        :can-import="$canCreate">

        <x-slot:filter>
            <select name="area" class="toolbar-filter-select" style="height:38px; min-width:140px;">
                <option value="">Semua Area</option>
                @foreach ($areaList as $k => $v)
                    <option value="{{ $k }}" {{ $areaFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
            <select name="tahap" class="toolbar-filter-select" style="height:38px; min-width:150px;">
                <option value="">Semua Tahap</option>
                @foreach ($tahapList as $k => $v)
                    <option value="{{ $k }}" {{ $tahapFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
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
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Tanggal & Lokasi</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Area & Tahap</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Kondisi</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase; text-align:center; width:110px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($list as $row)
                <tr style="border-bottom:1px solid var(--border-color);">
                    <td style="padding:14px 18px; color:var(--text-muted);">{{ $list->firstItem() + $loop->index }}</td>
                    <td style="padding:14px 18px;">
                        <div style="font-weight:700; font-family:monospace; color:var(--text-heading);">{{ $row->nomor_kegiatan }}</div>
                        <div style="font-weight:600; color:var(--text-heading); margin-top:4px;"><i class="fas fa-location-dot" style="color:var(--danger,#ef4444);"></i> {{ $row->lokasi }}</div>
                        <div style="font-size:var(--text-xs); color:var(--text-muted);">{{ \Carbon\Carbon::parse($row->tanggal_kegiatan)->format('d M Y') }}</div>
                        @if ($row->nama_rapat)<div style="font-size:var(--text-xs); color:var(--text-muted);">{{ \Illuminate\Support\Str::limit($row->nama_rapat, 50) }}</div>@endif
                    </td>
                    <td style="padding:14px 18px;">
                        <span class="badge" style="background:rgba(59,130,246,0.12); color:var(--primary); font-size:var(--text-xs); padding:3px 8px;">{{ $areaList[$row->area] ?? $row->area }}</span>
                        <div style="font-size:var(--text-xs); color:var(--text-muted); margin-top:4px;">{{ $tahapList[$row->tahap] ?? $row->tahap }}</div>
                    </td>
                    <td style="padding:14px 18px;">
                        @php
                            $kc = ['sangat_bersih' => ['#dcfce7', '#15803d'], 'bersih' => ['#dcfce7', '#15803d'], 'cukup' => ['#fef3c7', '#92400e'], 'kotor' => ['#fee2e2', '#b91c1c'], 'perlu_tindakan' => ['#fee2e2', '#b91c1c']][$row->kondisi] ?? ['#f1f5f9', '#475569'];
                        @endphp
                        <span class="badge" style="background:{{ $kc[0] }}; color:{{ $kc[1] }}; font-size:var(--text-sm); padding:4px 10px;">{{ $kondisiList[$row->kondisi] ?? $row->kondisi }}</span>
                    </td>
                    <td style="padding:14px 18px; text-align:center;">
                        <div style="display:flex; gap:6px; justify-content:center;">
                            @if ($canUpdate)
                                <button type="button" class="btn-icon" title="Ubah"
                                    data-teknisi-form="modalKebersihanRapat"
                                    data-edit-route="{{ route('dashboard.teknisi.kebersihan-rapat.update', $row->id) }}"
                                    data-title="Ubah Catatan Kebersihan Ruang Rapat"
                                    data-field-tanggal_kegiatan="{{ $row->tanggal_kegiatan }}"
                                    data-field-lokasi="{{ $row->lokasi }}"
                                    data-field-nama_rapat="{{ $row->nama_rapat }}"
                                    data-field-area="{{ $row->area }}"
                                    data-field-tahap="{{ $row->tahap }}"
                                    data-field-kondisi="{{ $row->kondisi }}"
                                    data-field-uraian_kegiatan="{{ $row->uraian_kegiatan }}"
                                    data-field-temuan="{{ $row->temuan }}"
                                    data-field-tindak_lanjut="{{ $row->tindak_lanjut }}"
                                    data-field-status="{{ $row->status }}"
                                    style="background:var(--primary); color:#fff; width:32px; height:32px; border-radius:var(--radius-sm, 6px); border:none; cursor:pointer;">
                                    <i class="fas fa-pen" style="font-size:var(--text-sm);"></i>
                                </button>
                            @endif
                            @if ($canDelete)
                                <form action="{{ route('dashboard.teknisi.kebersihan-rapat.destroy', $row->id) }}" method="POST" data-confirm="delete" data-confirm-message="Hapus catatan kebersihan ruang rapat ini?" style="display:inline;">
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
                    <i class="fas fa-people-roof" style="font-size:var(--text-3xl,1.875rem); opacity:0.4;"></i>
                    <p style="margin:10px 0 0;">Belum ada catatan kebersihan ruang rapat.</p>
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
        'modalId' => 'modalKebersihanRapat',
        'modalTitle' => 'Tambah Catatan Kebersihan Ruang Rapat',
        'modalAction' => route('dashboard.teknisi.kebersihan-rapat.store'),
        'modalBodyView' => 'dashboard.teknisi._partials.form-kebersihan-rapat',
    ])

    @include('dashboard.teknisi._partials.modal-import', [
        'modalId' => 'modalImportTeknisi',
        'title' => 'Import Kebersihan Ruang Rapat',
        'templateRoute' => route('dashboard.teknisi.laporan.template', ['modul' => 'kebersihan-rapat']),
        'actionRoute' => route('dashboard.teknisi.laporan.import', ['modul' => 'kebersihan-rapat']),
    ])
@endsection

@push('scripts')
    @include('dashboard.teknisi._partials.scripts')
@endpush
