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

    // Ikon status (konfirmasi / KK / Ijazah) + label mini di bawah ikon. Bila `$fileId`
    // ada, ikon jadi tombol pratinjau PDF di modal (pola `.btn-preview-pdf-inline`).
    $renderStatusIcon = function (string $variant, string $title, $fileId = null, ?string $short = null) {
        $palette = [
            'ok'    => ['fa-circle-check', 'var(--success, #10b981)', 'rgba(16,185,129,0.15)'],
            'warn'  => ['fa-hourglass-half', 'var(--warning, #f59e0b)', 'rgba(245,158,11,0.15)'],
            'bad'   => ['fa-circle-xmark', 'var(--danger, #ef4444)', 'rgba(239,68,68,0.15)'],
            'muted' => ['fa-minus', 'var(--text-muted)', 'rgba(148,163,184,0.15)'],
        ];
        [$icon, $color, $bg] = $palette[$variant] ?? $palette['muted'];
        $style = 'display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px;'
            . ' border-radius: var(--radius-sm, 6px); background: ' . $bg . '; color: ' . $color . ';'
            . ' border: 1px solid ' . $color . '; font-size: var(--text-sm, 0.8125rem);';
        $inner = '<i class="fas ' . $icon . '"></i>';

        if ($fileId) {
            $box = '<button type="button" class="btn-preview-pdf-inline" data-url="' . route('dashboard.berkas.preview', $fileId) . '"'
                . ' data-title="' . e($title) . '" title="' . e($title) . ' (klik untuk lihat berkas)"'
                . ' style="' . $style . ' cursor: pointer; padding: 0;">' . $inner . '</button>';
        } else {
            $box = '<span title="' . e($title) . '" style="' . $style . '">' . $inner . '</span>';
        }

        if ($short === null) {
            return $box;
        }

        return '<span style="display: inline-flex; flex-direction: column; align-items: center; gap: 2px;">'
            . $box
            . '<span style="font-size: var(--text-xs, 0.6875rem); font-weight: 800; letter-spacing: 0.03em; color: ' . $color . '; text-transform: uppercase; line-height: 1;">' . e($short) . '</span>'
            . '</span>';
    };

    // Peta status berkas → varian ikon + label.
    $berkasVariant = function ($meta) {
        return match ($meta['status'] ?? '') {
            'valid' => 'ok',
            'menunggu' => 'warn',
            'tidak_valid' => 'bad',
            default => 'muted',
        };
    };
    $berkasStatusLabel = function ($meta) {
        return match ($meta['status'] ?? '') {
            'valid' => 'Valid / Sesuai',
            'menunggu' => 'Menunggu Verifikasi',
            'tidak_valid' => 'Ditolak / Tidak Sesuai',
            default => 'Belum Diunggah',
        };
    };

    // Sel "Proses": pill status Belum/Sedang/Selesai, klik untuk ganti siklik (khusus update).
    $prosesMeta = [
        'belum' => ['Belum Diproses', 'fa-circle-dashed', 'var(--text-muted)', 'rgba(148,163,184,0.15)'],
        'proses' => ['Sedang Proses', 'fa-spinner', 'var(--warning, #f59e0b)', 'rgba(245,158,11,0.15)'],
        'selesai' => ['Selesai', 'fa-circle-check', 'var(--success, #10b981)', 'rgba(16,185,129,0.15)'],
    ];
    $nextProses = ['belum' => 'proses', 'proses' => 'selesai', 'selesai' => 'belum'];
    $renderProses = function ($item) use ($prosesMeta, $nextProses, $canUpdate) {
        $st = $item->status_proses ?: 'belum';
        [$label, $icon, $color, $bg] = $prosesMeta[$st] ?? $prosesMeta['belum'];
        $pill = '<span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: var(--radius-pill, 999px);'
            . ' background: ' . $bg . '; color: ' . $color . '; font-size: var(--text-sm, 0.8125rem); font-weight: 700; white-space: nowrap;">'
            . '<i class="fas ' . $icon . '"></i> ' . $label . '</span>';

        if (!$canUpdate) {
            return $pill;
        }
        $next = $nextProses[$st] ?? 'proses';
        $title = 'Klik untuk ubah ke: ' . ($prosesMeta[$next][0] ?? $next);

        return '<button type="button" class="residu-status-btn" data-id="' . $item->id . '" data-status="' . e($st) . '"'
            . ' data-next="' . e($next) . '" title="' . e($title) . '" aria-label="' . e($title) . '"'
            . ' style="background: none; border: 0; padding: 0; cursor: pointer; line-height: 1;">' . $pill . '</button>';
    };

    // Sel "Catatan": ikon saja. Catatan TIDAK ditampilkan langsung — klik ikon
    // membuka modal untuk melihat / mengubah (khusus update). Tercatat di riwayat.
    $renderCatatan = function ($item) use ($canUpdate) {
        $note = trim((string) $item->catatan);
        $ada = $note !== '';
        $color = $ada ? 'var(--warning, #f59e0b)' : 'var(--text-muted)';
        $bg = $ada ? 'rgba(245,158,11,0.15)' : 'rgba(148,163,184,0.12)';
        $icon = $ada ? 'fa-note-sticky' : 'fa-plus';
        $title = $ada ? 'Lihat / ubah catatan masalah' : 'Tambah catatan masalah';

        return '<button type="button" class="residu-catatan-btn" data-id="' . $item->id . '"'
            . ' data-nama="' . e($item->nama ?: '-') . '" data-catatan="' . e($note) . '"'
            . ' data-can-edit="' . ($canUpdate ? '1' : '0') . '"'
            . ' title="' . e($title) . '" aria-label="' . e($title) . '"'
            . ' style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px;'
            . ' border-radius: var(--radius-sm, 6px); background: ' . $bg . '; color: ' . $color . ';'
            . ' border: 1px solid ' . $color . '; cursor: pointer; padding: 0; position: relative;">'
            . '<i class="fas ' . $icon . '"></i>'
            . ($ada ? '<span data-catatan-dot="1" style="position: absolute; top: -3px; right: -3px; width: 8px; height: 8px; border-radius: 50%; background: var(--warning, #f59e0b); border: 1.5px solid var(--bg-card, #fff);"></span>' : '')
            . '</button>';
    };
@endphp

<div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
        <thead>
            <tr style="border-bottom: 1px solid var(--border-color);">
                @foreach ([
                    [null, 'Catatan'],
                    [null, 'NISN / NIK'],
                    [null, 'NAMA'],
                    [null, 'Kelas'],
                    ['status_proses', 'Proses'],
                    [null, 'Status'],
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
                    <td style="padding: 12px 16px; text-align: center;" data-label="Catatan">
                        {!! $renderCatatan($item) !!}
                    </td>
                    <td style="padding: 12px 16px; font-family: var(--font-family-mono, monospace); font-size: var(--text-sm, 0.8125rem);"
                        data-label="NISN/NIK">
                        <div style="display: flex; flex-direction: column; gap: 2px;">
                            @if ($item->nisn)
                                <span class="copyable" data-copy="{{ $item->nisn }}" data-label="NISN"
                                    title="Klik untuk salin NISN" style="color: var(--primary);">{{ $item->nisn }}</span>
                            @else
                                <span style="color: var(--text-muted);">-</span>
                            @endif
                            @if ($item->nik)
                                <span class="copyable" data-copy="{{ $item->nik }}" data-label="NIK"
                                    title="Klik untuk salin NIK"
                                    style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">{{ $item->nik }}</span>
                            @endif
                        </div>
                    </td>
                    <td style="padding: 12px 16px; font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem); white-space: nowrap;"
                        data-label="Nama">
                        {{ $item->nama ?: '-' }}
                    </td>
                    <td style="padding: 12px 16px; text-align: center; white-space: nowrap;" data-label="Kelas">
                        <span class="badge badge-outline" style="font-size: var(--text-sm, 0.8125rem); padding: 3px 8px; white-space: nowrap;">
                            {{ $item->kelas ?: '-' }}
                        </span>
                    </td>
                    <td style="padding: 12px 16px; text-align: center;" data-label="Proses">
                        {!! $renderProses($item) !!}
                    </td>
                    <td style="padding: 12px 16px; text-align: center;" data-label="Status">
                        @php
                            $kkMeta = $item->berkas_map['kartu_keluarga'] ?? [];
                            $ijzMeta = $item->berkas_map['ijazah_smp'] ?? [];
                            $konfVariant = $item->sudah_konfirmasi
                                ? ($item->status_konfirmasi === 'perlu_perbaikan' ? 'warn' : 'ok')
                                : 'muted';
                            $konfLabel = $item->sudah_konfirmasi
                                ? ($item->status_konfirmasi === 'perlu_perbaikan' ? 'Perlu Perbaikan' : 'Sudah Konfirmasi')
                                : 'Belum Konfirmasi';
                        @endphp
                        <div style="display: inline-flex; align-items: center; gap: 8px;">
                            {!! $renderStatusIcon($konfVariant, 'Konfirmasi: ' . $konfLabel, null, 'Konf') !!}
                            {!! $renderStatusIcon($berkasVariant($kkMeta), 'Kartu Keluarga: ' . $berkasStatusLabel($kkMeta), $kkMeta['file_id'] ?? null, 'KK') !!}
                            {!! $renderStatusIcon($berkasVariant($ijzMeta), 'Ijazah SMP: ' . $berkasStatusLabel($ijzMeta), $ijzMeta['file_id'] ?? null, 'IJZ') !!}
                        </div>
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
                    <td colspan="16"
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
