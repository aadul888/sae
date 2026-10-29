@extends('layouts.dashboard')

@section('title', 'Jadwal Area Tugas Kebersihan - Teknisi')

@section('content')
    @php
        $statCards = [
            ['icon' => 'fas fa-list-check', 'bg' => 'rgba(59,130,246,0.12)', 'color' => 'var(--primary, #3b82f6)', 'value' => number_format($statTotal, 0, ',', '.'), 'label' => 'Total Jadwal'],
            ['icon' => 'fas fa-circle-play', 'bg' => 'rgba(16,185,129,0.12)', 'color' => 'var(--success, #10b981)', 'value' => number_format($statAktif, 0, ',', '.'), 'label' => 'Jadwal Aktif'],
            ['icon' => 'fas fa-recycle', 'bg' => 'rgba(245,158,11,0.12)', 'color' => 'var(--warning, #f59e0b)', 'value' => number_format($statSampah, 0, ',', '.'), 'label' => 'Pengolahan Sampah'],
            ['icon' => 'fas fa-user-shield', 'bg' => 'rgba(139,92,246,0.12)', 'color' => 'var(--purple, #8b5cf6)', 'value' => number_format($statLaporan, 0, ',', '.'), 'label' => 'Laporan Siswa'],
        ];
    @endphp

    <x-sae.teknisi-header
        banner-title="Administrasi & Jadwal Pembagian Area Tugas Kebersihan"
        banner-desc="Bukti fisik Indikator #1 — jadwal area tugas kebersihan & pengolahan sampah serta penerimaan laporan siswa tidak disiplin dari Tim Keamanan."
        banner-icon="fas fa-clipboard-list"
        banner-color="var(--primary, #3b82f6)"
        :create-route="route('dashboard.teknisi.area-tugas.store')"
        :filter-route="route('dashboard.teknisi.area-tugas.index')"
        :cetak-route="route('dashboard.teknisi.laporan.cetak', ['modul' => 'area-tugas', 'periode' => 'bulan'])"
        import-modal-id="modalImportTeknisi"
        modal-id="modalAreaTugas"
        :stat-cards="$statCards"
        :per-page="$perPage"
        :search="$search"
        :can-create="$canCreate"
        :can-import="$canCreate"
        :can-delete="$canDelete"
        bulk-modul="area-tugas">

        <x-slot:filter>
            <select name="jenis" class="toolbar-filter-select" style="height:38px; min-width:180px;">
                <option value="">Semua Jenis Tugas</option>
                @foreach ($jenisList as $k => $v)
                    <option value="{{ $k }}" {{ $jenisFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
        </x-slot:filter>

        <thead>
            <tr style="border-bottom: 1px solid var(--border-color);">
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">No</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Nomor & Area</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Jenis & Periode</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Petugas</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Status</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase; text-align:center; width:110px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($list as $row)
                <tr style="border-bottom:1px solid var(--border-color);" data-bulk-id="{{ $row->id }}">
                    <td style="padding:14px 18px; color:var(--text-muted);">{{ $list->firstItem() + $loop->index }}</td>
                    <td style="padding:14px 18px;">
                        <div style="font-weight:700; font-family:monospace; color:var(--text-heading);">{{ $row->nomor_jadwal }}</div>
                        <div style="font-weight:600; color:var(--text-heading); margin-top:4px;"><i class="fas fa-map-marker-alt" style="color:var(--danger,#ef4444);"></i> {{ $row->area }}</div>
                        @if ($row->zona)<div style="font-size:var(--text-xs); color:var(--text-muted);">{{ $row->zona }}</div>@endif
                    </td>
                    <td style="padding:14px 18px;">
                        <span class="badge" style="background:rgba(59,130,246,0.12); color:var(--primary); font-size:var(--text-xs); padding:3px 8px;">{{ $jenisList[$row->jenis_tugas] ?? $row->jenis_tugas }}</span>
                        <div style="font-size:var(--text-xs); color:var(--text-muted); margin-top:4px;">
                            {{ $periodeList[$row->periode_jadwal] ?? $row->periode_jadwal }} &bull; {{ $shiftList[$row->shift] ?? $row->shift }}
                        </div>
                        @if ($row->laporan_siswa)
                            <div style="font-size:var(--text-xs); color:var(--warning,#f59e0b); margin-top:4px;"><i class="fas fa-user-shield"></i> {{ \Illuminate\Support\Str::limit($row->laporan_siswa, 50) }}</div>
                        @endif
                    </td>
                    <td style="padding:14px 18px;">
                        <div style="font-weight:600; color:var(--text-heading);">{{ $row->petugas_nama }}</div>
                        <div style="font-size:var(--text-xs); color:var(--text-muted);">
                            {{ $row->tanggal_mulai ? \Carbon\Carbon::parse($row->tanggal_mulai)->format('d M Y') : '—' }}
                        </div>
                    </td>
                    <td style="padding:14px 18px;">
                        <span class="badge" style="background:{{ $row->status === 'aktif' ? '#dcfce7' : '#f1f5f9' }}; color:{{ $row->status === 'aktif' ? '#15803d' : '#475569' }}; font-size:var(--text-sm); padding:4px 10px;">{{ $statusList[$row->status] ?? $row->status }}</span>
                    </td>
                    <td style="padding:14px 18px; text-align:center;">
                        <div style="display:flex; gap:6px; justify-content:center;">
                            @if ($canUpdate)
                                <button type="button" class="btn-icon" title="Ubah"
                                    data-teknisi-form="modalAreaTugas"
                                    data-edit-route="{{ route('dashboard.teknisi.area-tugas.update', $row->id) }}"
                                    data-title="Ubah Jadwal Area Tugas"
                                    data-field-area="{{ $row->area }}"
                                    data-field-zona="{{ $row->zona }}"
                                    data-field-jenis_tugas="{{ $row->jenis_tugas }}"
                                    data-field-periode_jadwal="{{ $row->periode_jadwal }}"
                                    data-field-shift="{{ $row->shift }}"
                                    data-field-petugas_nama="{{ $row->petugas_nama }}"
                                    data-field-petugas_ptk_id="{{ $row->petugas_ptk_id }}"
                                    data-field-tanggal_mulai="{{ $row->tanggal_mulai }}"
                                    data-field-tanggal_selesai="{{ $row->tanggal_selesai }}"
                                    data-field-uraian="{{ $row->uraian }}"
                                    data-field-laporan_siswa="{{ $row->laporan_siswa }}"
                                    data-field-sumber_laporan="{{ $row->sumber_laporan }}"
                                    data-field-status="{{ $row->status }}"
                                    style="background:var(--primary); color:#fff; width:32px; height:32px; border-radius:var(--radius-sm, 6px); border:none; cursor:pointer;">
                                    <i class="fas fa-pen" style="font-size:var(--text-sm);"></i>
                                </button>
                            @endif
                            @if ($canDelete)
                                <form action="{{ route('dashboard.teknisi.area-tugas.destroy', $row->id) }}" method="POST" data-confirm="delete" data-confirm-message="Hapus jadwal area tugas ini?" style="display:inline;">
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
                    <i class="fas fa-clipboard-list" style="font-size:var(--text-3xl,1.875rem); opacity:0.4;"></i>
                    <p style="margin:10px 0 0;">Belum ada jadwal area tugas.</p>
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
        'modalId' => 'modalAreaTugas',
        'modalTitle' => 'Tambah Jadwal Area Tugas',
        'modalAction' => route('dashboard.teknisi.area-tugas.store'),
        'modalBodyView' => 'dashboard.teknisi._partials.form-area-tugas',
    ])

    @include('dashboard.teknisi._partials.modal-import', [
        'modalId' => 'modalImportTeknisi',
        'title' => 'Import Jadwal Area Tugas',
        'templateRoute' => route('dashboard.teknisi.laporan.template', ['modul' => 'area-tugas']),
        'actionRoute' => route('dashboard.teknisi.laporan.import', ['modul' => 'area-tugas']),
    ])
@endsection

@push('scripts')
    @include('dashboard.teknisi._partials.scripts')
@endpush
