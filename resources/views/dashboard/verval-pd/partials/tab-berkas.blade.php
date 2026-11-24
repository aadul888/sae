{{-- TAB BERKAS: validasi berkas fisik & digital peserta didik --}}
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
                @foreach (['Akta Lahir', 'Kartu Keluarga', 'Ijazah SMP', 'KTP Ortu', 'KIP / PIP'] as $label)
                    <th
                        style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                        {{ $label }}
                    </th>
                @endforeach
                <th
                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                    Aksi
                </th>
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
                        style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: var(--text-base, 0.875rem); order: 3;"
                        data-label="Rombel">
                        <span class="badge badge-outline" style="font-size: var(--text-sm, 0.8125rem); padding: 3px 8px;">
                            {{ $item->rombel_nama ?: '-' }}
                        </span>
                    </td>
                    @php
                        $berkasCols = [
                            'akta_kelahiran' => $item->files['akta_kelahiran'] ?? null,
                            'kartu_keluarga' => $item->files['kartu_keluarga'] ?? null,
                            'ijazah_smp' => $item->files['ijazah_smp'] ?? null,
                            'ktp_orang_tua' => $item->files['ktp_orang_tua'] ?? null,
                            'kip_pip' => $item->files['kip_pip'] ?? null,
                        ];
                    @endphp
                    @foreach ($berkasCols as $colKey => $file)
                        <td class="cell-pd-berkas-item" style="padding: 14px 18px; text-align: center;"
                            data-label="{{ str_replace('_', ' ', $colKey) }}">
                            @if ($file)
                                @if ($file->status === 'valid')
                                    <span class="badge badge-success" title="Valid / Sesuai"><i
                                            class="fas fa-circle-check"></i> Valid</span>
                                @elseif ($file->status === 'tidak_valid')
                                    <span class="badge badge-danger" title="Tidak Valid / Ditolak"><i
                                            class="fas fa-circle-xmark"></i> Ditolak</span>
                                @else
                                    <span class="badge badge-warning" title="Menunggu Verifikasi"><i
                                            class="fas fa-clock"></i> PDF</span>
                                @endif
                            @elseif ($item->{$colKey})
                                <span class="badge badge-success" title="Fisik Terverifikasi"><i
                                        class="fas fa-check"></i></span>
                            @else
                                <span class="text-muted" style="font-size: var(--text-sm, 0.8125rem);">-</span>
                            @endif
                        </td>
                    @endforeach
                    <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                        <div class="table-actions" style="justify-content: flex-end;">
                            <button type="button" class="btn-icon btn-validasi-berkas-detail"
                                data-id="{{ $item->peserta_didik_id }}" data-nama="{{ $item->nama }}"
                                title="Validasi Dokumen Siswa">
                                <i class="fas fa-check-double" style="color: var(--primary);"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9"
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
