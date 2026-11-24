{{-- TAB BERKAS: kolom tabel hanya KK & Ijazah + Konfirmasi; dokumen lain via modal --}}
@php
    $konfLabel = [
        'sesuai' => ['Sesuai', 'fa-circle-check', 'success'],
        'diverifikasi' => ['Diverifikasi', 'fa-circle-check', 'success'],
        'disinkronkan_dapodik' => ['Tersinkron Dapodik', 'fa-cloud-arrow-up', 'primary'],
        'perlu_perbaikan' => ['Perlu Perbaikan', 'fa-triangle-exclamation', 'danger'],
    ];
    // Chip status berkas (pola Wali Kelas) untuk kolom KK & Ijazah.
    $renderBerkasChip = function ($label, $title, $file, $fallbackBool) {
        $isValid = ($file && $file->status === 'valid') || $fallbackBool;
        $st = $isValid ? 'valid' : ($file?->status ?? 'belum');
        $class = match ($st) {
            'valid' => 'valid',
            'tidak_valid' => 'tidak_valid',
            'menunggu' => 'menunggu',
            default => 'belum',
        };
        $icon = match ($st) {
            'valid' => 'fa-circle-check',
            'tidak_valid' => 'fa-circle-xmark',
            'menunggu' => 'fa-clock',
            default => 'fa-minus',
        };
        $text = match ($st) {
            'valid' => 'Valid / Sesuai',
            'tidak_valid' => 'Ditolak / Tidak Sesuai',
            'menunggu' => 'Menunggu Verifikasi',
            default => 'Belum Diunggah',
        };

        $eye = '';
        if (!empty($file?->file_path)) {
            $eye = '<button type="button" class="btn-preview-pdf-inline berkas-chip-eye"'
                . ' data-url="' . route('dashboard.berkas.preview', $file->id) . '"'
                . ' data-title="' . e($title . ' - ' . ($file->nama_berkas ?: '')) . '"'
                . ' title="Lihat Dokumen PDF ' . e($title) . '"><i class="fas fa-eye"></i></button>';
        }

        return '<div class="berkas-pill-group"><div class="berkas-chip berkas-chip--' . $class . '" title="' . e($title) . ': ' . $text . '">'
            . '<i class="fas ' . $icon . ' berkas-chip-status-icon"></i>'
            . '<span class="berkas-chip-label">' . e($label) . '</span>'
            . $eye . '</div></div>';
    };
@endphp

<div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
        <thead>
            <tr style="border-bottom: 1px solid var(--border-color);">
                @foreach ([
                    ['nama', 'Nama Siswa'],
                    ['nisn', 'NISN / NIPD'],
                    ['nama_rombel', 'Rombel'],
                ] as [$key, $label])
                    <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                        style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;"
                        data-sort="{{ $key }}">
                        {{ $label }}
                        <span
                            class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                    </th>
                @endforeach
                <th
                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                    Konfirmasi</th>
                <th
                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                    Kartu Keluarga</th>
                <th
                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                    Ijazah SMP</th>
                <th
                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                    Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($berkasList as $item)
                <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                    <td class="cell-pd-nama"
                        style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);"
                        data-label="Nama Siswa">
                        <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                            @if (!empty($item->foto_url))
                                <div class="pd-foto-thumb" data-biodata-id="{{ $item->peserta_didik_id }}"
                                    role="button" tabindex="0"
                                    title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                    style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; cursor: pointer;">
                                    <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}"
                                        style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            @else
                                <div class="pd-foto-thumb empty" data-biodata-id="{{ $item->peserta_didik_id }}"
                                    role="button" tabindex="0"
                                    title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                    style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: var(--text-lg, 1.125rem); flex-shrink: 0; cursor: pointer;">
                                    <i class="fas fa-user-graduate" style="opacity: 0.7;"></i>
                                </div>
                            @endif
                            <div class="pd-info">
                                <span class="pd-nama" data-biodata-id="{{ $item->peserta_didik_id }}"
                                    role="button" tabindex="0"
                                    style="font-weight: 700; color: var(--text-color); cursor: pointer;">{{ $item->nama }}</span>
                                @if (!empty($item->nik))
                                    <div
                                        style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-family: var(--font-family-mono, monospace); margin-top: 2px;">
                                        <span class="copyable" data-copy="{{ $item->nik }}"
                                            data-label="NIK">NIK: {{ $item->nik }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="cell-pd-nisn"
                        style="padding: 14px 18px; font-family: var(--font-family-mono, monospace); font-size: var(--text-base, 0.875rem); color: var(--primary);"
                        data-label="NISN/NIPD">
                        @if ($item->nisn)
                            <span class="copyable" data-copy="{{ $item->nisn }}" data-label="NISN">{{ $item->nisn }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="cell-pd-rombel"
                        style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: var(--text-base, 0.875rem);"
                        data-label="Rombel">
                        <span class="badge badge-outline" style="font-size: var(--text-sm, 0.8125rem); padding: 3px 8px;">
                            {{ $item->rombel_nama ?: '-' }}
                        </span>
                    </td>

                    {{-- Kolom Konfirmasi: admin bisa klik ubah sesuai <-> perlu perbaikan (tercatat riwayat). --}}
                    @php
                        $stKonf = $item->status_konfirmasi ?: 'belum_konfirmasi';
                        $kMeta = $konfLabel[$stKonf] ?? ['Belum Dikonfirmasi', 'fa-circle-question', 'muted'];
                        $kBadge = '<span class="badge badge-' . ($kMeta[2] === 'muted' ? 'outline' : $kMeta[2]) . '" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i class="fas ' . $kMeta[1] . '"></i> ' . $kMeta[0] . '</span>';
                    @endphp
                    <td class="cell-pd-konfirmasi" style="padding: 14px 18px; text-align: center;" data-label="Konfirmasi">
                        @if (!empty($isAdmin))
                            <button type="button" class="berkas-konfirmasi-btn"
                                data-id="{{ $item->peserta_didik_id }}" data-nama="{{ $item->nama }}"
                                data-status="{{ $stKonf }}" title="Klik untuk ubah status konfirmasi"
                                style="background: none; border: 0; padding: 0; cursor: pointer;">
                                {!! $kBadge !!}
                            </button>
                        @else
                            {!! $kBadge !!}
                        @endif
                    </td>

                    @php
                        $kkF = $item->files['kartu_keluarga'] ?? null;
                        $ijzF = $item->files['ijazah_smp'] ?? null;
                    @endphp
                    <td class="cell-pd-berkas-item" style="padding: 14px 18px; text-align: center;"
                        data-label="Kartu Keluarga">
                        {!! $renderBerkasChip('KK', 'Kartu Keluarga (KK)', $kkF, $item->kartu_keluarga) !!}
                    </td>
                    <td class="cell-pd-berkas-item" style="padding: 14px 18px; text-align: center;"
                        data-label="Ijazah SMP">
                        {!! $renderBerkasChip('Ijazah', 'Ijazah SMP/MTs', $ijzF, $item->ijazah_smp) !!}
                    </td>
                    <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: center;" data-label="Aksi">
                        <div class="table-actions"
                            style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                            <a href="{{ route('dashboard.identitas.index', ['peserta_didik_id' => $item->peserta_didik_id]) }}"
                                target="_blank" class="btn-icon" title="Detail Identitas Lengkap Peserta Didik"
                                style="width: 38px; height: 38px; border-radius: var(--radius-md, 8px); display: inline-flex; align-items: center; justify-content: center; background: rgba(6,182,212,0.12); color: var(--accent); border: 1px solid rgba(6,182,212,0.25);">
                                <i class="fas fa-id-card"></i>
                            </a>
                            <button type="button" class="btn-icon btn-validasi-berkas-detail"
                                data-id="{{ $item->peserta_didik_id }}" data-nama="{{ $item->nama }}"
                                title="Validasi Dokumen Siswa (Akta, KTP, KIP, dll)"
                                style="width: 38px; height: 38px; border-radius: var(--radius-md, 8px); display: inline-flex; align-items: center; justify-content: center; background: rgba(99,102,241,0.12); color: var(--primary); border: 1px solid rgba(99,102,241,0.25);">
                                <i class="fas fa-check-double"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7"
                        style="padding: 30px; text-align: center; color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                        <i class="fas fa-folder-open mb-2"
                            style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5;"></i>
                        <div>Belum ada data berkas siswa.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($berkasList->hasPages())
    <div class="datatable-footer">
        @include('partials.datatable-pagination', ['paginator' => $berkasList])
    </div>
@endif
