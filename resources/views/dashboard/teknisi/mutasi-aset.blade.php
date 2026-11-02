@extends('layouts.dashboard')

@section('title', 'Mutasi Lokasi Aset - Teknisi')

@section('content')
    <x-sae.teknisi-header
        :banner-title="$spec['title']"
        :banner-desc="$spec['desc']"
        :banner-icon="$spec['icon']"
        :banner-color="$spec['color']"
        :filter-route="route('dashboard.teknisi.modul.index', ['modul' => $modul])"
        :cetak-route="route('dashboard.teknisi.modul.cetak', ['modul' => $modul])"
        :cetak-periode="true"
        :cetak-params="['modul' => $modul]"
        modal-id="modalMutasiAset"
        :stat-cards="$statCards"
        :per-page="$perPage"
        :search="$search">

        <x-slot:filter>
            <select name="ruang_id" class="toolbar-filter-select" style="height:38px; min-width:170px;">
                <option value="">Semua Ruang</option>
                @foreach ($ruangOptions as $opt)
                    <option value="{{ $opt['id'] }}" {{ (string) $ruang === (string) $opt['id'] ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                @endforeach
            </select>
        </x-slot:filter>

        <thead>
            <tr style="border-bottom: 1px solid var(--border-color);">
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">No</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Aset</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Ruang Saat Ini</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Kondisi</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase; text-align:center; width:110px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($list as $row)
                @php $kc = $kondisiList[$row->kondisi] ?? $row->kondisi; @endphp
                <tr style="border-bottom:1px solid var(--border-color);">
                    <td style="padding:14px 18px; color:var(--text-muted);">{{ $list->firstItem() + $loop->index }}</td>
                    <td style="padding:14px 18px;">
                        <div style="font-weight:700; color:var(--text-heading);">{{ $row->nama_barang }}</div>
                        <div style="font-size:var(--text-xs); color:var(--text-muted); font-family:monospace;">{{ $row->kode_aset ?: '—' }}</div>
                    </td>
                    <td style="padding:14px 18px;">
                        <div style="font-weight:600; color:var(--text-heading);">
                            <i class="fas fa-location-dot" style="color:var(--danger,#ef4444);"></i>
                            {{ $row->nama_ruang ?: 'Belum ditetapkan' }}
                        </div>
                        <div style="font-size:var(--text-xs); color:var(--text-muted); margin-top:2px;">Jumlah: {{ (int) $row->jumlah }} {{ $row->satuan }}</div>
                    </td>
                    <td style="padding:14px 18px; color:var(--text-heading);">{{ $kc ?: '—' }}</td>
                    <td style="padding:14px 18px; text-align:center;">
                        <div style="display:flex; gap:6px; justify-content:center;">
                            <button type="button" class="btn-icon" title="Pindahkan Aset"
                                data-teknisi-form="modalMutasiAset"
                                data-edit-route="{{ route('dashboard.teknisi.modul.update', ['modul' => $modul, 'id' => $row->id]) }}"
                                data-title="Pindahkan: {{ $row->nama_barang }}"
                                data-field-aset_label="{{ $row->nama_barang }}{{ $row->kode_aset ? ' — ' . $row->kode_aset : '' }}"
                                data-field-dari_ruang="{{ $row->nama_ruang ?: 'Belum ditetapkan' }}"
                                data-field-kondisi="{{ $row->kondisi }}"
                                data-field-ke_ruang_id=""
                                data-field-alasan=""
                                data-field-berita_acara=""
                                data-field-tanggal="{{ now()->toDateString() }}"
                                style="background:var(--primary); color:#fff; width:32px; height:32px; border-radius:var(--radius-sm, 6px); border:none; cursor:pointer;">
                                <i class="fas fa-arrow-right-arrow-left" style="font-size:var(--text-sm);"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center; padding:36px; color:var(--text-muted);">
                    <i class="fas fa-boxes-stacked" style="font-size:var(--text-3xl,1.875rem); opacity:0.4;"></i>
                    <p style="margin:10px 0 0;">Belum ada aset pada cakupan Anda. Tambahkan aset di menu Inventaris &amp; Aset.</p>
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
        'modalId' => 'modalMutasiAset',
        'modalTitle' => 'Pindahkan Aset',
        'modalAction' => '#',
        'modalBodyView' => 'dashboard.teknisi._partials.form-mutasi-aset',
        'row' => null,
    ])
@endsection

@push('scripts')
    @include('dashboard.teknisi._partials.scripts')
@endpush
