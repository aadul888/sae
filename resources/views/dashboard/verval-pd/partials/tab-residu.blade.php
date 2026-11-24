{{-- TAB RESIDU: hasil import berkas Dukcapil, centang apa adanya (hijau/merah) --}}
@php
    $renderCentang = function ($val) {
        $val = trim((string) $val);
        if ($val === '') {
            return '<span style="color: var(--text-muted); opacity: 0.5;">-</span>';
        }
        $hasYes = preg_match('/[✓✔√]/u', $val) === 1;
        $hasNo = preg_match('/[✗✘×]/u', $val) === 1;
        if ($hasYes && !$hasNo) {
            return '<span title="Sesuai" style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: var(--radius-sm, 6px); background: rgba(16,185,129,0.15); color: var(--success, #10b981);"><i class="fas fa-check"></i></span>';
        }
        if ($hasNo && !$hasYes) {
            return '<span title="Tidak Sesuai" style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: var(--radius-sm, 6px); background: rgba(239,68,68,0.15); color: var(--danger, #ef4444);"><i class="fas fa-xmark"></i></span>';
        }
        return '<span style="font-weight: 600; color: var(--text-color); font-size: var(--text-sm, 0.8125rem);">' . e($val) . '</span>';
    };
@endphp

<div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
        <thead>
            <tr style="border-bottom: 1px solid var(--border-color);">
                @foreach ([
                    'NISN',
                    'NIK',
                    'NAMA',
                    'Rombel/Sekolah',
                    'NISN Dukcapil',
                    'NIK Dukcapil',
                    'NIK Ganda',
                    'Nama Dukcapil',
                    'Tempat Lahir',
                    'Tanggal Lahir',
                    'Ibu Kandung',
                    'Jenis Kelamin',
                    'Desa/Kelurahan',
                ] as $label)
                    <th
                        style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; white-space: nowrap; {{ $loop->index >= 4 ? 'text-align: center;' : '' }}">
                        {{ $label }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                    <td style="padding: 12px 16px; font-family: var(--font-family-mono, monospace); font-size: var(--text-sm, 0.8125rem); color: var(--primary);"
                        data-label="NISN">
                        @if ($item->nisn)
                            <span class="copyable" data-copy="{{ $item->nisn }}" data-label="NISN"
                                title="Klik untuk salin NISN">{{ $item->nisn }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td style="padding: 12px 16px; font-family: var(--font-family-mono, monospace); font-size: var(--text-sm, 0.8125rem);"
                        data-label="NIK">
                        @if ($item->nik)
                            <span class="copyable" data-copy="{{ $item->nik }}" data-label="NIK"
                                title="Klik untuk salin NIK">{{ $item->nik }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td style="padding: 12px 16px; font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);"
                        data-label="Nama">
                        {{ $item->nama ?: '-' }}
                    </td>
                    <td style="padding: 12px 16px;" data-label="Rombel/Sekolah">
                        <span class="badge badge-outline" style="font-size: var(--text-sm, 0.8125rem); padding: 3px 8px;">
                            {{ $item->rombel_sekolah ?: '-' }}
                        </span>
                    </td>
                    @foreach (['nisn_banding', 'nik_dukcapil', 'nik_ganda', 'nama_banding', 'tempat_lahir', 'tanggal_lahir', 'ibu_kandung', 'jenis_kelamin', 'desa_kelurahan'] as $col)
                        <td style="padding: 12px 16px; text-align: center;"
                            data-label="{{ str_replace('_', ' ', $col) }}">
                            {!! $renderCentang($item->{$col}) !!}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="13"
                        style="padding: 30px; text-align: center; color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                        <i class="fas fa-file-circle-exclamation mb-2"
                            style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5;"></i>
                        <div>Belum ada data Residu. Silakan import berkas Excel dari Dukcapil terlebih dahulu.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($items->hasPages())
    <div class="datatable-footer">
        @include('partials.datatable-pagination', ['paginator' => $items])
    </div>
@endif
