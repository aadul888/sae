            <!-- TAB 1: SISWA AKTIF (Format Baku Selaras Modul Administrator) -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                            @php
                                $cols = [
                                    ['nama', 'Nama Lengkap'],
                                    ['nisn', 'NISN / NIPD'],
                                    ['jenis_kelamin', 'L/P'],
                                    ['nama_rombel', 'Rombel Kelas'],
                                    ['tingkat_pendidikan_id', 'Tingkat'],
                                ];
                            @endphp
                            @foreach ($cols as [$key, $label])
                                <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; {{ $key === 'jenis_kelamin' ? 'text-align: center;' : '' }}"
                                    data-sort="{{ $key }}">
                                    {{ $label }}
                                    <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                            @endforeach
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Tempat, Tanggal Lahir
                            </th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($aktifList as $item)
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                <td class="cell-pd-nama"
                                    style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);"
                                    data-label="Nama Lengkap">
                                    <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                        @if (!empty($item->foto_url))
                                            <div class="pd-foto-thumb" id="pdFotoThumb_{{ $item->peserta_didik_id }}"
                                                @if ($canUpdate || $canCreate) onclick="openUploadFotoModal('{{ $item->peserta_didik_id }}', '{{ addslashes($item->nama) }}', '{{ $item->nisn ?? '' }}', '{{ $item->foto_url }}', '{{ $item->foto_size ?? '' }}')"
                                            title="Klik untuk melihat / mengubah pasfoto peserta didik"
                                            @else
                                            onclick="openBiodataPesertaDidikModal('{{ $item->peserta_didik_id }}')"
                                            title="Pasfoto {{ $item->nama }}" @endif
                                                style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; cursor: pointer; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25); transition: transform 0.2s ease, border-color 0.2s ease;">
                                                <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}"
                                                    style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                        @else
                                            <div class="pd-foto-thumb empty"
                                                id="pdFotoThumb_{{ $item->peserta_didik_id }}"
                                                @if ($canUpdate || $canCreate) onclick="openUploadFotoModal('{{ $item->peserta_didik_id }}', '{{ addslashes($item->nama) }}', '{{ $item->nisn ?? '' }}', '', '')"
                                            title="Klik untuk mengunggah pasfoto peserta didik"
                                            @else
                                            onclick="openBiodataPesertaDidikModal('{{ $item->peserta_didik_id }}')"
                                            title="Belum ada pasfoto" @endif
                                                style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: var(--text-lg, 1.125rem); flex-shrink: 0; cursor: pointer; transition: all 0.2s ease;">
                                                <i class="fas fa-user-graduate" style="opacity: 0.7;"></i>
                                            </div>
                                        @endif
                                        <div class="pd-info">
                                            <div class="pd-title-row"
                                                style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                                <span class="pd-nama"
                                                    onclick="openBiodataPesertaDidikModal('{{ $item->peserta_didik_id }}')"
                                                    style="font-weight: 700; color: var(--text-color); cursor: pointer;">{{ $item->nama }}</span>
                                                @if (!empty($item->is_koordinator))
                                                    <span class="badge badge-koordinator"
                                                        id="badgeKoordinator_{{ $item->peserta_didik_id }}"
                                                        style="background: rgba(99,102,241,0.15); color: #6366f1; border: 1px solid rgba(99,102,241,0.3); font-size: var(--text-2xs, 0.6875rem); padding: 2px 7px; border-radius: var(--radius-xs, 4px); font-weight: 700; display: inline-flex; align-items: center; gap: 4px;"
                                                        title="Peserta didik ini ditunjuk sebagai Koordinator Kelas">
                                                        <i class="fas fa-crown" style="color: #f59e0b;"></i> Koordinator
                                                    </span>
                                                @else
                                                    <span class="badge badge-koordinator"
                                                        id="badgeKoordinator_{{ $item->peserta_didik_id }}"
                                                        style="display: none; background: rgba(99,102,241,0.15); color: #6366f1; border: 1px solid rgba(99,102,241,0.3); font-size: var(--text-2xs, 0.6875rem); padding: 2px 7px; border-radius: var(--radius-xs, 4px); font-weight: 700; align-items: center; gap: 4px;"
                                                        title="Peserta didik ini ditunjuk sebagai Koordinator Kelas">
                                                        <i class="fas fa-crown" style="color: #f59e0b;"></i> Koordinator
                                                    </span>
                                                @endif
                                            </div>
                                            @if (!empty($item->nik))
                                                <div
                                                    style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                                    <span class="copyable" data-copy="{{ $item->nik }}"
                                                        data-label="NIK" title="Klik untuk salin NIK">
                                                        NIK: {{ $item->nik }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="cell-pd-nisn"
                                    style="padding: 14px 18px; font-family: monospace; font-size: var(--text-base, 0.875rem); color: var(--primary);"
                                    data-label="NISN/NIPD">
                                    <div class="cell-col-right">
                                        <div>
                                            @if ($item->nisn)
                                                <span class="copyable" data-copy="{{ $item->nisn }}"
                                                    data-label="NISN"
                                                    title="Klik untuk salin NISN">{{ $item->nisn }}</span>
                                            @else
                                                -
                                            @endif
                                        </div>
                                        @if (!empty($item->nipd))
                                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                                <span class="copyable" data-copy="{{ $item->nipd }}"
                                                    data-label="NIPD" title="Klik untuk salin NIPD">NIPD:
                                                    {{ $item->nipd }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="cell-pd-gender" style="padding: 14px 18px; text-align: center;"
                                    data-label="L/P">
                                    <span
                                        class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}"
                                        style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">
                                        {{ $item->jenis_kelamin ?: '-' }}
                                    </span>
                                </td>
                                <td class="cell-pd-rombel"
                                    style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: var(--text-base, 0.875rem);"
                                    data-label="Rombel">
                                    <span class="badge badge-outline" style="font-size: var(--text-sm, 0.8125rem); padding: 3px 8px;">
                                        {{ $item->nama_rombel ?: '-' }}
                                    </span>
                                </td>
                                <td class="cell-pd-tingkat"
                                    style="padding: 14px 18px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);"
                                    data-label="Tingkat">
                                    {{ $item->tingkat_pendidikan_id ?: '-' }}
                                </td>
                                <td class="cell-pd-ttl"
                                    style="padding: 14px 18px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);"
                                    data-label="TTL">
                                    <div class="cell-col-right" style="color: var(--text-muted);">
                                        {{ $item->tempat_lahir ? $item->tempat_lahir . ', ' : '' }}{{ $item->tanggal_lahir ? date('d/m/Y', strtotime($item->tanggal_lahir)) : '-' }}
                                    </div>
                                </td>
                                <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right;"
                                    data-label="Aksi">
                                    <div class="table-actions"
                                        style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                        @if ($canUpdate || $canCreate)
                                            <button type="button" class="btn-icon"
                                                title="{{ !empty($item->foto_url) ? 'Ganti / Lihat Pasfoto Peserta Didik' : 'Unggah Pasfoto Peserta Didik (PNG)' }}"
                                                onclick="openUploadFotoModal('{{ $item->peserta_didik_id }}', '{{ addslashes($item->nama) }}', '{{ $item->nisn ?? '' }}', '{{ $item->foto_url ?? '' }}', '{{ $item->foto_size ?? '' }}')">
                                                <i class="fas fa-camera"
                                                    style="{{ !empty($item->foto_url) ? 'color: #10b981;' : '' }}"></i>
                                            </button>
                                        @endif
                                        <button type="button" class="btn-icon" title="Lihat Biodata Lengkap"
                                            onclick="openBiodataPesertaDidikModal('{{ $item->peserta_didik_id }}')">
                                            <i class="fas fa-id-card"></i>
                                        </button>
                                        @if (!empty($item->nisn))
                                            <button type="button" class="btn-icon"
                                                title="Pratinjau / Cetak Kartu Pelajar Digital"
                                                onclick="openKartuPelajarModal('{{ $item->nisn }}')">
                                                <i class="fas fa-address-card" style="color: #0284c7;"></i>
                                            </button>
                                        @endif
                                        @if ($canUpdate)
                                            <button type="button"
                                                class="btn-icon btn-toggle-koordinator {{ !empty($item->is_koordinator) ? 'is-active' : '' }}"
                                                id="btnKoordinator_{{ $item->peserta_didik_id }}"
                                                data-id="{{ $item->peserta_didik_id }}"
                                                data-nama="{{ $item->nama }}"
                                                data-rombel="{{ $item->nama_rombel ?: '-' }}"
                                                data-status="{{ !empty($item->is_koordinator) ? '1' : '0' }}"
                                                title="{{ !empty($item->is_koordinator) ? 'Cabut Wewenang Koordinator Kelas' : 'Tunjuk sebagai Koordinator Kelas (Asisten Wali Kelas)' }}">
                                                <i class="fas fa-crown"
                                                    style="{{ !empty($item->is_koordinator) ? 'color: #f59e0b; opacity: 1;' : '' }}"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7"
                                    style="padding: 30px; text-align: center; color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                                    <i class="fas fa-user-slash mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5;"></i>
                                    <div>Belum ada data peserta didik aktif yang cocok.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($aktifList->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $aktifList])
                </div>
            @endif
