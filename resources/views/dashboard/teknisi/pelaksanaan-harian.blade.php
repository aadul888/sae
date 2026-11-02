@extends('layouts.dashboard')

@section('title', 'Jurnal Pelaksanaan Tugas - Teknisi')

@php
    $tabUrl = function (string $t) {
        return route('dashboard.teknisi.pelaksanaan-harian.index', array_merge(request()->query(), ['tab' => $t]));
    };
@endphp

@section('content')
    @php
        $statCards = [
            ['icon' => 'fas fa-list-check', 'bg' => 'rgba(59,130,246,0.12)', 'color' => 'var(--primary, #3b82f6)', 'value' => number_format($statTotal, 0, ',', '.'), 'label' => 'Total Rencana'],
            ['icon' => 'fas fa-hourglass-half', 'bg' => 'rgba(245,158,11,0.12)', 'color' => 'var(--warning, #f59e0b)', 'value' => number_format($statBelum, 0, ',', '.'), 'label' => 'Belum Dikerjakan'],
            ['icon' => 'fas fa-circle-check', 'bg' => 'rgba(16,185,129,0.12)', 'color' => 'var(--success, #10b981)', 'value' => number_format($statSelesai, 0, ',', '.'), 'label' => 'Selesai'],
            ['icon' => 'fas fa-calendar-day', 'bg' => 'rgba(139,92,246,0.12)', 'color' => 'var(--purple, #8b5cf6)', 'value' => number_format($statHariIni, 0, ',', '.'), 'label' => 'Hari Ini'],
        ];
    @endphp

    <x-sae.teknisi-header
        banner-title="Jurnal Pelaksanaan Tugas"
        banner-desc="Konfirmasi rencana Jadwal Area Tugas: sudah / belum dikerjakan. Baris hari ini terisi otomatis dari jadwal aktif."
        banner-icon="fas fa-list-check"
        banner-color="var(--primary, #3b82f6)"
        :filter-route="route('dashboard.teknisi.pelaksanaan-harian.index')"
        modal-id="modalPelaksanaan"
        :stat-cards="$statCards"
        :per-page="$perPage"
        :search="$search"
        :can-create="$canCreate"
        :can-delete="$canDelete"
        bulk-modul="pelaksanaan-harian">

        <x-slot:filter>
            <select name="shift" class="toolbar-filter-select" style="height:38px; min-width:130px;">
                <option value="">Semua Shift</option>
                @foreach ($shiftList as $k => $v)
                    <option value="{{ $k }}" {{ $shift === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
            <select name="periode" class="toolbar-filter-select" style="height:38px; min-width:150px;">
                <option value="">Semua Periode</option>
                @foreach ($periodeList as $k => $v)
                    <option value="{{ $k }}" {{ $periodeFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
            <input type="date" name="tanggal" value="{{ $tanggal }}" class="toolbar-filter-select" style="height:38px; min-width:150px;">
            <label style="display:inline-flex; align-items:center; gap:6px; font-size:var(--text-sm); font-weight:600; color:var(--text-heading);">
                <input type="checkbox" name="hari_ini" value="1" {{ $khususHariIni ? 'checked' : '' }} onchange="this.form.submit()">
                Hari Ini
            </label>
            <input type="hidden" name="tab" value="{{ $tab }}">
        </x-slot:filter>

        <x-slot:tabnav>
            {{-- Jurnal Pelaksanaan dibagi 2 bagian: sesi sesuai jadwal + perbaikan WO. --}}
            <div class="periode-nav-wrapper">
                <div class="periode-nav-desktop">
                    <a href="{{ route('dashboard.teknisi.pelaksanaan-harian.index') }}" class="periode-nav-tab active">
                        <i class="fas fa-list-check"></i> Sesuai Jadwal
                    </a>
                    <a href="{{ route('dashboard.teknisi.jurnal-perbaikan.index') }}" class="periode-nav-tab">
                        <i class="fas fa-screwdriver-wrench"></i> Perbaikan Sarpras (WO)
                    </a>
                </div>
            </div>
        </x-slot:tabnav>

        <thead>
            <tr style="border-bottom: 1px solid var(--border-color);">
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">No</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Nomor & Tanggal</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Area & Periode</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Petugas</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase; text-align:center; width:140px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($list as $row)
                <tr style="border-bottom:1px solid var(--border-color);" data-bulk-id="{{ $row->id }}">
                    <td style="padding:14px 18px; color:var(--text-muted);">{{ $list->firstItem() + $loop->index }}</td>
                    <td style="padding:14px 18px;">
                        <div style="font-weight:700; font-family:monospace; color:var(--text-heading);">{{ $row->nomor_pelaksanaan }}</div>
                        <div style="font-size:var(--text-xs); color:var(--text-muted);">{{ \Carbon\Carbon::parse($row->tanggal)->format('d M Y') }} &bull; {{ $shiftList[$row->shift] ?? $row->shift }}</div>
                    </td>
                    <td style="padding:14px 18px;">
                        <div style="font-weight:600; color:var(--text-heading);"><i class="fas fa-map-marker-alt" style="color:var(--danger,#ef4444);"></i> {{ $row->area }}</div>
                        <div style="font-size:var(--text-xs); color:var(--text-muted); margin-top:2px;">{{ $periodeList[$row->periode_jadwal] ?? $row->periode_jadwal }}</div>
                        @if ($row->uraian)<div style="font-size:var(--text-xs); color:var(--text-muted); margin-top:2px;">{{ \Illuminate\Support\Str::limit($row->uraian, 60) }}</div>@endif
                        @if ($row->temuan)<div style="font-size:var(--text-xs); color:var(--warning,#f59e0b); margin-top:2px;"><i class="fas fa-triangle-exclamation"></i> {{ \Illuminate\Support\Str::limit($row->temuan, 60) }}</div>@endif
                    </td>
                    <td style="padding:14px 18px;">
                        <div style="font-weight:600; color:var(--text-heading);">{{ $pjRuang[$row->ruang_id] ?? $row->petugas_nama }}</div>
                    </td>
                    <td style="padding:14px 18px; text-align:center;">
                        <div style="display:flex; gap:6px; justify-content:center;">
                            @if ($canUpdate)
                                @if ($row->status === 'belum')
                                    <form action="{{ route('dashboard.teknisi.pelaksanaan-harian.status', $row->id) }}" method="POST" data-confirm="delete" data-confirm-message="Tandai tugas ini sudah dikerjakan?" style="display:inline;">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="status" value="selesai">
                                        <button type="submit" class="btn-icon" title="Tandai Selesai"
                                            style="background:var(--success,#10b981); color:#fff; width:32px; height:32px; border-radius:var(--radius-sm, 6px); border:none; cursor:pointer;">
                                            <i class="fas fa-check" style="font-size:var(--text-sm);"></i>
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('dashboard.teknisi.pelaksanaan-harian.status', $row->id) }}" method="POST" data-confirm="delete" data-confirm-message="Tandai tugas ini BELUM dikerjakan?" style="display:inline;">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="status" value="belum">
                                        <button type="submit" class="btn-icon" title="Tandai Belum"
                                            style="background:var(--warning,#f59e0b); color:#fff; width:32px; height:32px; border-radius:var(--radius-sm, 6px); border:none; cursor:pointer;">
                                            <i class="fas fa-rotate-left" style="font-size:var(--text-sm);"></i>
                                        </button>
                                    </form>
                                @endif
                                <button type="button" class="btn-icon" title="Isi Detail"
                                    data-teknisi-form="modalPelaksanaan"
                                    data-edit-route="{{ route('dashboard.teknisi.pelaksanaan-harian.update', $row->id) }}"
                                    data-title="Detail Pelaksanaan"
                                    data-field-status="{{ $row->status }}"
                                    data-field-uraian="{{ $row->uraian }}"
                                    data-field-temuan="{{ $row->temuan }}"
                                    data-field-catatan="{{ $row->catatan }}"
                                    style="background:var(--primary); color:#fff; width:32px; height:32px; border-radius:var(--radius-sm, 6px); border:none; cursor:pointer;">
                                    <i class="fas fa-pen" style="font-size:var(--text-sm);"></i>
                                </button>
                            @endif
                            @if ($canDelete)
                                <form action="{{ route('dashboard.teknisi.pelaksanaan-harian.destroy', $row->id) }}" method="POST" data-confirm="delete" data-confirm-message="Hapus baris pelaksanaan ini?" style="display:inline;">
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
                    <i class="fas fa-list-check" style="font-size:var(--text-3xl,1.875rem); opacity:0.4;"></i>
                    <p style="margin:10px 0 0;">{{ $tab === 'selesai' ? 'Belum ada tugas selesai pada periode ini.' : 'Tidak ada tugas yang belum dikerjakan.' }}</p>
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
        'modalId' => 'modalPelaksanaan',
        'modalTitle' => 'Detail Pelaksanaan',
        'modalAction' => route('dashboard.teknisi.pelaksanaan-harian.store'),
        'modalBodyView' => 'dashboard.teknisi._partials.form-pelaksanaan-harian',
    ])
@endsection

@push('scripts')
    @include('dashboard.teknisi._partials.scripts')
@endpush
