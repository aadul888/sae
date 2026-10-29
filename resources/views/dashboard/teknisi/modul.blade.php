@extends('layouts.dashboard')

@section('title', $spec['title'] . ' - Teknisi')

@section('content')
    <x-sae.teknisi-header
        :banner-title="$spec['title']"
        :banner-desc="$spec['desc']"
        :banner-icon="$spec['icon']"
        :banner-color="$spec['color']"
        :create-route="route('dashboard.teknisi.modul.store', ['modul' => $modul])"
        :filter-route="route('dashboard.teknisi.modul.index', ['modul' => $modul])"
        :cetak-route="route('dashboard.teknisi.modul.cetak', ['modul' => $modul])"
        import-modal-id="modalImportTeknisi"
        modal-id="modalModul"
        :stat-cards="$statCards"
        :per-page="$perPage"
        :search="$search"
        :can-create="$canCreate"
        :can-import="$canCreate"
        :can-delete="$canDelete"
        bulk-modul="{{ $modul }}">

        <x-slot:filter>
            @if ($spec['dateCol'])
                <x-sae.toolbar-date-range :from-value="$dari" :to-value="$sampai" />
            @endif
        </x-slot:filter>

        <thead>
            <tr style="border-bottom: 1px solid var(--border-color);">
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">No</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Nomor</th>
                @foreach ($spec['list'] as $col)
                    @php $conf = $spec['columns'][$col] ?? ['label' => ucwords(str_replace('_', ' ', $col))]; @endphp
                    <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">
                        {{ $conf['label'] }}
                    </th>
                @endforeach
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase; text-align:center; width:110px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($list as $row)
                <tr style="border-bottom:1px solid var(--border-color);" data-bulk-id="{{ $row->id }}">
                    <td style="padding:14px 18px; color:var(--text-muted);">{{ $list->firstItem() + $loop->index }}</td>
                    <td style="padding:14px 18px;">
                        <div style="font-weight:700; font-family:monospace; color:var(--text-heading);">
                            {{ $row->{$spec['nomor'][0]} ?? '—' }}
                        </div>
                        @if ($spec['dateCol'])
                            <div style="font-size:var(--text-xs); color:var(--text-muted);">
                                {{ $row->{$spec['dateCol']} ? \Carbon\Carbon::parse($row->{$spec['dateCol']})->format('d M Y') : '—' }}
                            </div>
                        @endif
                    </td>
                    @foreach ($spec['list'] as $col)
                        @php
                            $conf = $spec['columns'][$col] ?? ['label' => $col, 'type' => 'string'];
                            $val = $row->{$col} ?? null;
                            if (($conf['type'] ?? '') === 'select') {
                                $val = $conf['options'][$val] ?? $val;
                            } elseif (($conf['type'] ?? '') === 'decimal') {
                                $val = $val === null ? null : number_format((float) $val, 0, ',', '.');
                            } elseif (($conf['type'] ?? '') === 'date' && $val) {
                                $val = \Carbon\Carbon::parse($val)->format('d M Y');
                            }
                        @endphp
                        <td style="padding:14px 18px; color:var(--text-heading);">
                            {{ $val !== null && $val !== '' ? \Illuminate\Support\Str::limit((string) $val, 70) : '—' }}
                        </td>
                    @endforeach
                    <td style="padding:14px 18px; text-align:center;">
                        <div style="display:flex; gap:6px; justify-content:center;">
                            @if ($canUpdate)
                                <button type="button" class="btn-icon" title="Ubah"
                                    data-teknisi-form="modalModul"
                                    data-edit-route="{{ route('dashboard.teknisi.modul.update', ['modul' => $modul, 'id' => $row->id]) }}"
                                    data-title="Ubah {{ $spec['title'] }}"
                                    @foreach ($spec['columns'] as $col => $conf)
                                        @continue(($conf['type'] ?? '') === 'hidden')
                                        data-field-{{ $col }}="{{ $row->{$col} ?? '' }}"
                                    @endforeach
                                    style="background:var(--primary); color:#fff; width:32px; height:32px; border-radius:var(--radius-sm, 6px); border:none; cursor:pointer;">
                                    <i class="fas fa-pen" style="font-size:var(--text-sm);"></i>
                                </button>
                            @endif
                            @if ($canDelete)
                                <form action="{{ route('dashboard.teknisi.modul.destroy', ['modul' => $modul, 'id' => $row->id]) }}"
                                    method="POST" data-confirm="delete" data-confirm-message="Hapus data ini?" style="display:inline;">
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
                <tr><td colspan="{{ count($spec['list']) + 3 }}" style="text-align:center; padding:36px; color:var(--text-muted);">
                    <i class="{{ $spec['icon'] }}" style="font-size:var(--text-3xl,1.875rem); opacity:0.4;"></i>
                    <p style="margin:10px 0 0;">Belum ada data {{ strtolower($spec['title']) }}.</p>
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
        'modalId' => 'modalModul',
        'modalTitle' => 'Tambah ' . $spec['title'],
        'modalAction' => route('dashboard.teknisi.modul.store', ['modul' => $modul]),
        'modalBodyView' => 'dashboard.teknisi._partials.form-modul',
    ])

    @include('dashboard.teknisi._partials.modal-import', [
        'modalId' => 'modalImportTeknisi',
        'title' => 'Import ' . $spec['title'],
        'templateRoute' => route('dashboard.teknisi.modul.template', ['modul' => $modul]),
        'actionRoute' => route('dashboard.teknisi.modul.import', ['modul' => $modul]),
        'hint' => 'Satu baris = satu catatan. Data hasil impor menjadi milik akun Anda.',
    ])
@endsection

@push('scripts')
    @include('dashboard.teknisi._partials.scripts')
@endpush
