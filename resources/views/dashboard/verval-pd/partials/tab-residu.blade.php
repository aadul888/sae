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

    // Sel centang bisa diklik untuk mengubah ✓/✗ (khusus admin dengan hak update).
    $renderCell = function ($item, $col) use ($renderCentang, $canUpdate) {
        $raw = trim((string) $item->{$col});
        if (!$canUpdate) {
            return $renderCentang($raw);
        }
        $next = ($raw !== '' && preg_match('/[✓✔√]/u', $raw)) ? '✗' : '✓';
        $title = 'Klik untuk ubah menjadi ' . ($next === '✓' ? 'Sesuai (✓)' : 'Tidak Sesuai (✗)');

        return '<button type="button" class="residu-cell-btn" data-id="' . $item->id . '" data-column="' . e($col) . '"'
            . ' data-value="' . e($raw) . '" data-next="' . $next . '"'
            . ' title="' . $title . '" aria-label="' . $title . '"'
            . ' style="background: none; border: 0; padding: 0; cursor: pointer; line-height: 1;">'
            . $renderCentang($raw) . '</button>';
    };
@endphp

<div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
        <thead>
            <tr style="border-bottom: 1px solid var(--border-color);">
                @foreach ([
                    [null, 'NISN'],
                    [null, 'NIK'],
                    [null, 'NAMA'],
                    ['rombel_sekolah', 'Rombel/Sekolah'],
                    ['nisn_banding', 'NISN Dukcapil'],
                    ['nik_dukcapil', 'NIK Dukcapil'],
                    ['nik_ganda', 'NIK Ganda'],
                    ['nama_banding', 'Nama Dukcapil'],
                    ['tempat_lahir', 'Tempat Lahir'],
                    ['tanggal_lahir', 'Tanggal Lahir'],
                    ['ibu_kandung', 'Ibu Kandung'],
                    ['jenis_kelamin', 'Jenis Kelamin'],
                    ['desa_kelurahan', 'Desa/Kelurahan'],
                ] as [$key, $label])
                    <th @if ($key) class="sortable-th {{ $sort === $key ? 'sorted' : '' }}" data-sort="{{ $key }}" @endif
                        style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; white-space: nowrap; {{ $loop->index >= 3 ? 'text-align: center;' : '' }} {{ $key ? 'cursor: pointer;' : '' }}">
                        {{ $label }}
                        @if ($key)
                            <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        @endif
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
                    <td style="padding: 12px 16px; text-align: center;" data-label="Rombel/Sekolah">
                        @php $rombelVal = trim((string) $item->rombel_sekolah); @endphp
                        @if ($rombelVal !== '' && preg_match('/[✓✔√✗✘×]/u', $rombelVal))
                            {!! $renderCell($item, 'rombel_sekolah') !!}
                        @else
                            <span class="badge badge-outline" style="font-size: var(--text-sm, 0.8125rem); padding: 3px 8px;">
                                {{ $rombelVal !== '' ? $rombelVal : '-' }}
                            </span>
                        @endif
                    </td>
                    @foreach (['nisn_banding', 'nik_dukcapil', 'nik_ganda', 'nama_banding', 'tempat_lahir', 'tanggal_lahir', 'ibu_kandung', 'jenis_kelamin', 'desa_kelurahan'] as $col)
                        <td style="padding: 12px 16px; text-align: center;"
                            data-label="{{ str_replace('_', ' ', $col) }}">
                            {!! $renderCell($item, $col) !!}
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
