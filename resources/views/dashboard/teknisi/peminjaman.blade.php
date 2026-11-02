@extends('layouts.dashboard')

@section('title', 'Peminjaman Sarpras - Teknisi')

@section('content')
    @php
        $statCards = [
            ['icon' => 'fas fa-clipboard-list', 'bg' => 'rgba(59,130,246,0.12)', 'color' => 'var(--primary, #3b82f6)', 'value' => number_format($statTotal, 0, ',', '.'), 'label' => 'Total Peminjaman'],
            ['icon' => 'fas fa-hourglass-half', 'bg' => 'rgba(245,158,11,0.12)', 'color' => 'var(--warning, #f59e0b)', 'value' => number_format($statDiajukan, 0, ',', '.'), 'label' => 'Menunggu Persetujuan'],
            ['icon' => 'fas fa-hand-holding', 'bg' => 'rgba(14,165,233,0.12)', 'color' => 'var(--info, #0ea5e9)', 'value' => number_format($statDipinjam, 0, ',', '.'), 'label' => 'Sedang Dipinjam'],
            ['icon' => 'fas fa-clock', 'bg' => 'rgba(239,68,68,0.12)', 'color' => 'var(--danger, #ef4444)', 'value' => number_format($statTerlambat, 0, ',', '.'), 'label' => 'Terlambat'],
        ];
    @endphp

    <x-sae.teknisi-header
        banner-title="Peminjaman Sarpras (Teknisi)"
        banner-desc="Peminjaman ruang/bangunan & inventaris aset Sarpras. Terpisah dari peminjaman jurusan; data mengikuti penanggung jawab ruang."
        banner-icon="fas fa-hand-holding"
        banner-color="var(--info, #0ea5e9)"
        :create-route="route('dashboard.teknisi.peminjaman.store')"
        :filter-route="route('dashboard.teknisi.peminjaman.index')"
        modal-id="modalPeminjaman"
        :stat-cards="$statCards"
        :per-page="$perPage"
        :search="$search"
        :can-create="$canCreate"
        :can-delete="$canDelete">

        <x-slot:filter>
            <select name="status" class="toolbar-filter-select" style="height:38px; min-width:150px;" title="Filter Status">
                <option value="">Semua Status</option>
                @foreach ($statusList as $k => $v)
                    <option value="{{ $k }}" {{ $statusFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
            <select name="tipe" class="toolbar-filter-select" style="height:38px; min-width:150px;" title="Filter Peminjam">
                <option value="">Semua Peminjam</option>
                @foreach ($tipeList as $k => $v)
                    <option value="{{ $k }}" {{ $tipeFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
        </x-slot:filter>

        <thead>
            <tr style="border-bottom:1px solid var(--border-color);">
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Nomor / Tanggal</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Peminjam</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Keperluan</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase; text-align:center;">Tenggat</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase; text-align:center;">Status</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase; text-align:center; width:170px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($list as $p)
                @php
                    $terlambat = $p->tanggal_kembali_rencana
                        && in_array($p->status, ['dipinjam', 'terlambat'], true)
                        && \Carbon\Carbon::parse($p->tanggal_kembali_rencana)->isPast();
                    $sc = match ($p->status) {
                        'dikembalikan' => 'badge-success',
                        'dipinjam' => 'badge-info',
                        'disetujui' => 'badge-primary',
                        'terlambat', 'ditolak' => 'badge-danger',
                        default => 'badge-warning',
                    };
                @endphp
                <tr style="border-bottom:1px solid var(--border-color);" data-bulk-id="{{ $p->id }}">
                    <td style="padding:14px 18px;">
                        <div style="font-weight:700; font-family:monospace; color:var(--primary);">{{ $p->nomor_pinjam }}</div>
                        <div style="font-size:var(--text-xs); color:var(--text-muted);">{{ \Carbon\Carbon::parse($p->tanggal_pinjam)->translatedFormat('d M Y') }}</div>
                    </td>
                    <td style="padding:14px 18px;">
                        <div style="font-weight:700; color:var(--text-heading);">{{ $p->peminjam_nama }}</div>
                        <div style="font-size:var(--text-xs); color:var(--text-muted);">
                            {{ $tipeList[$p->peminjam_tipe] ?? $p->peminjam_tipe }}
                            @if ($p->peminjam_kelas) &bull; {{ $p->peminjam_kelas }} @endif
                        </div>
                        <div style="font-size:var(--text-2xs); color:var(--text-muted); margin-top:2px;">
                            <i class="fas fa-boxes-stacked"></i> {{ $p->jumlah_item }} item
                        </div>
                    </td>
                    <td style="padding:14px 18px;">
                        <div style="font-size:var(--text-sm); color:var(--text-color);">{{ \Illuminate\Support\Str::limit($p->keperluan, 70) }}</div>
                        @if ($p->lokasi)
                            <div style="font-size:var(--text-xs); color:var(--text-muted);"><i class="fas fa-location-dot"></i> {{ $p->lokasi }}</div>
                        @endif
                    </td>
                    <td style="padding:14px 18px; text-align:center; font-size:var(--text-sm);">
                        @if ($p->tanggal_kembali_rencana)
                            <span style="font-weight:700; color:{{ $terlambat ? 'var(--danger, #ef4444)' : 'var(--text-color)' }};">
                                {{ \Carbon\Carbon::parse($p->tanggal_kembali_rencana)->translatedFormat('d M Y') }}
                            </span>
                            @if ($p->tanggal_kembali_aktual)
                                <div style="font-size:var(--text-2xs); color:var(--success, #10b981);">Kembali: {{ \Carbon\Carbon::parse($p->tanggal_kembali_aktual)->format('d/m/Y') }}</div>
                            @elseif ($terlambat)
                                <div style="font-size:var(--text-2xs); color:var(--danger, #ef4444);">Lewat {{ \Carbon\Carbon::parse($p->tanggal_kembali_rencana)->diffInDays(now()) }} hari</div>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td style="padding:14px 18px; text-align:center;">
                        <span class="badge {{ $sc }}" style="font-size:var(--text-2xs);">{{ $statusList[$p->status] ?? $p->status }}</span>
                        @if ($p->kondisi_kembali)
                            <div style="font-size:var(--text-2xs); color:var(--text-muted); margin-top:3px;">{{ $kondisiList[$p->kondisi_kembali] ?? $p->kondisi_kembali }}</div>
                        @endif
                    </td>
                    <td style="padding:14px 18px; text-align:center;">
                        <div style="display:flex; gap:6px; justify-content:center;">
                            <a href="{{ route('dashboard.teknisi.peminjaman.show', $p->id) }}" class="btn-icon" title="Buka Detail"
                                style="background:var(--primary); color:#fff; width:32px; height:32px; border-radius:var(--radius-sm,6px); display:inline-flex; align-items:center; justify-content:center; text-decoration:none;">
                                <i class="fas fa-eye" style="font-size:var(--text-sm);"></i>
                            </a>
                            @if ($canUpdate && $p->status === 'diajukan')
                                <button type="button" class="btn-icon btn-pinjam-setujui" data-id="{{ $p->id }}" data-nama="{{ $p->peminjam_nama }}"
                                    title="Setujui" style="background:#dcfce7; color:var(--success,#10b981); width:32px; height:32px; border-radius:var(--radius-sm,6px); border:none; cursor:pointer;">
                                    <i class="fas fa-check" style="font-size:var(--text-sm);"></i>
                                </button>
                            @endif
                            @if ($canUpdate && $p->status === 'disetujui')
                                <button type="button" class="btn-icon btn-pinjam-serah" data-id="{{ $p->id }}" data-nama="{{ $p->peminjam_nama }}"
                                    title="Serah Terima" style="background:#e0f2fe; color:var(--info,#0ea5e9); width:32px; height:32px; border-radius:var(--radius-sm,6px); border:none; cursor:pointer;">
                                    <i class="fas fa-hand-holding" style="font-size:var(--text-sm);"></i>
                                </button>
                            @endif
                            @if ($canUpdate && in_array($p->status, ['dipinjam', 'terlambat'], true))
                                <button type="button" class="btn-icon btn-pinjam-kembali" data-id="{{ $p->id }}" data-nama="{{ $p->peminjam_nama }}"
                                    title="Proses Pengembalian" style="background:#dcfce7; color:var(--success,#10b981); width:32px; height:32px; border-radius:var(--radius-sm,6px); border:none; cursor:pointer;">
                                    <i class="fas fa-rotate-left" style="font-size:var(--text-sm);"></i>
                                </button>
                            @endif
                            @if ($canDelete)
                                <button type="button" class="btn-icon btn-pinjam-hapus" data-id="{{ $p->id }}" data-nama="{{ $p->nomor_pinjam }}"
                                    title="Hapus" style="background:#fee2e2; color:var(--danger,#ef4444); width:32px; height:32px; border-radius:var(--radius-sm,6px); border:none; cursor:pointer;">
                                    <i class="fas fa-trash-alt" style="font-size:var(--text-sm);"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; padding:36px; color:var(--text-muted);">
                    <i class="fas fa-hand-holding" style="font-size:var(--text-3xl,1.875rem); opacity:0.4;"></i>
                    <p style="margin:10px 0 0;">Belum ada data peminjaman sarpras.</p>
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
        'modalId' => 'modalPeminjaman',
        'modalTitle' => 'Ajukan Peminjaman Sarpras',
        'modalAction' => route('dashboard.teknisi.peminjaman.store'),
        'modalBodyView' => 'dashboard.teknisi._partials.form-peminjaman',
        'row' => null,
    ])

    @include('dashboard.teknisi._partials.modal-peminjaman-kembali', [
        'kondisiList' => $kondisiList,
    ])
@endsection

@push('scripts')
    @include('dashboard.teknisi._partials.scripts')
    <script src="{{ asset('js/teknisi-peminjaman.js') }}?v={{ file_exists(public_path('js/teknisi-peminjaman.js')) ? filemtime(public_path('js/teknisi-peminjaman.js')) : time() }}"></script>
@endpush
