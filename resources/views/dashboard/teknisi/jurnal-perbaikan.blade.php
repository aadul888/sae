@extends('layouts.dashboard')

@section('title', 'Jurnal Perbaikan Sarpras - Teknisi')

@section('content')
    @php
        $statCards = [
            ['icon' => 'fas fa-screwdriver-wrench', 'bg' => 'rgba(59,130,246,0.12)', 'color' => 'var(--primary, #3b82f6)', 'value' => number_format($statTotal, 0, ',', '.'), 'label' => 'Total Perbaikan Selesai'],
            ['icon' => 'fas fa-layer-group', 'bg' => 'rgba(16,185,129,0.12)', 'color' => 'var(--success, #10b981)', 'value' => number_format($statKategori, 0, ',', '.'), 'label' => 'Kategori Terlayani'],
            ['icon' => 'fas fa-triangle-exclamation', 'bg' => 'rgba(245,158,11,0.12)', 'color' => 'var(--warning, #f59e0b)', 'value' => number_format($statDarurat, 0, ',', '.'), 'label' => 'Darurat'],
            ['icon' => 'fas fa-calendar-check', 'bg' => 'rgba(139,92,246,0.12)', 'color' => 'var(--purple, #8b5cf6)', 'value' => number_format($statBulanIni, 0, ',', '.'), 'label' => 'Bulan Ini'],
        ];
    @endphp

    <x-sae.teknisi-header
        banner-title="Jurnal Perbaikan Sarana Prasarana"
        banner-desc="Bukti fisik Indikator #5 — rekap otomatis dari Work Order berstatus selesai (baca-saja)."
        banner-icon="fas fa-screwdriver-wrench"
        banner-color="var(--primary, #3b82f6)"
        :filter-route="route('dashboard.teknisi.jurnal-perbaikan.index')"
        :stat-cards="$statCards"
        :per-page="$perPage"
        :search="$search"
        :cetak-route="route('dashboard.teknisi.jurnal-perbaikan.cetak')"
        :cetak-periode="true"
        :cetak-params="[]">

        <x-slot:filter>
            <select name="kategori" class="toolbar-filter-select" style="height:38px; min-width:160px;">
                <option value="">Semua Kategori</option>
                @foreach ($kategoriList as $k => $v)
                    <option value="{{ $v }}" {{ $kategori === $v ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
            <x-sae.toolbar-date-range :from-value="$dari" :to-value="$sampai" />
        </x-slot:filter>

        <thead>
            <tr style="border-bottom: 1px solid var(--border-color);">
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">No</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Nomor & Tanggal</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Lokasi & Kategori</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Kerusakan & Tindakan</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Biaya Part</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($list as $row)
                <tr style="border-bottom:1px solid var(--border-color);">
                    <td style="padding:14px 18px; color:var(--text-muted);">{{ $list->firstItem() + $loop->index }}</td>
                    <td style="padding:14px 18px;">
                        <div style="font-weight:700; font-family:monospace; color:var(--text-heading);">{{ $row->nomor_wo }}</div>
                        <div style="font-size:var(--text-xs); color:var(--text-muted);">{{ \Carbon\Carbon::parse($row->tgl_selesai)->format('d M Y') }}</div>
                    </td>
                    <td style="padding:14px 18px;">
                        <div style="font-weight:600; color:var(--text-heading);"><i class="fas fa-location-dot" style="color:var(--danger,#ef4444);"></i> {{ $row->lokasi_unit }}</div>
                        <span class="badge" style="background:rgba(59,130,246,0.12); color:var(--primary); font-size:var(--text-xs); padding:3px 8px; margin-top:4px; display:inline-block;">{{ $kategoriWo[$row->kategori_perbaikan] ?? $row->kategori_perbaikan }}</span>
                        @if ($row->tingkat_urgensi === 'darurat')
                            <span class="badge" style="background:#fee2e2; color:#b91c1c; font-size:var(--text-xs); padding:3px 8px; margin-top:4px; display:inline-block;">Darurat</span>
                        @endif
                    </td>
                    <td style="padding:14px 18px;">
                        <div style="font-size:var(--text-sm); color:var(--text-heading); max-width:320px;">{{ \Illuminate\Support\Str::limit($row->deskripsi_kerusakan, 90) }}</div>
                        @if ($row->tindakan_perbaikan)<div style="font-size:var(--text-xs); color:var(--success,#10b981); margin-top:2px;"><i class="fas fa-wrench"></i> {{ \Illuminate\Support\Str::limit($row->tindakan_perbaikan, 60) }}</div>@endif
                    </td>
                    <td style="padding:14px 18px; font-weight:600; color:var(--text-heading);">
                        {{ $row->estimasi_biaya_part ? 'Rp ' . number_format((float) $row->estimasi_biaya_part, 0, ',', '.') : '—' }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center; padding:36px; color:var(--text-muted);">
                    <i class="fas fa-screwdriver-wrench" style="font-size:var(--text-3xl,1.875rem); opacity:0.4;"></i>
                    <p style="margin:10px 0 0;">Belum ada Work Order selesai pada periode ini.</p>
                </td></tr>
            @endforelse
        </tbody>

        <x-slot:pagination>
            @if ($list->hasPages())
                @include('partials.datatable-pagination', ['paginator' => $list])
            @endif
        </x-slot:pagination>
    </x-sae.teknisi-header>
@endsection

@push('scripts')
    @include('dashboard.teknisi._partials.scripts')
@endpush
