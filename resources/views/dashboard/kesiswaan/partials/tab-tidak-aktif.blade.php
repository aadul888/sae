            <!-- TAB 2: SISWA TIDAK AKTIF (Format Baku Selaras Modul Administrator) -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                            @php
                                $colsTa = [
                                    ['nama', 'Nama Lengkap'],
                                    ['nisn', 'NISN / NIPD'],
                                    ['jenis_kelamin', 'L/P'],
                                    ['nama_rombel_terakhir', 'Rombel Terakhir'],
                                    ['tingkat_pendidikan_terakhir', 'Tingkat'],
                                    ['tahun_lulus', 'Status & Tanggal'],
                                ];
                            @endphp
                            @foreach ($colsTa as [$key, $label])
                                <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                                    style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; {{ $key === 'jenis_kelamin' ? 'text-align: center;' : '' }}"
                                    data-sort="{{ $key }}">
                                    {{ $label }}
                                    <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                            @endforeach
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Kontak &amp; Alamat
                            </th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tidakAktifList as $item)
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                <td class="cell-pd-nama"
                                    style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: 0.88rem;"
                                    data-label="Nama">
                                    <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                        @if (!empty($item->foto_url))
                                            <div class="pd-foto-thumb" id="pdFotoThumb_{{ $item->peserta_didik_id }}"
                                                onclick="openPhotoPreviewModal('{{ $item->foto_url }}', '{{ addslashes($item->nama) }}')"
                                                title="Klik untuk memperbesar pasfoto"
                                                style="width: 42px; height: 42px; border-radius: 10px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; cursor: pointer; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25); transition: transform 0.2s ease, border-color 0.2s ease;">
                                                <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}"
                                                    style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                        @else
                                            <div class="pd-foto-thumb empty"
                                                style="width: 42px; height: 42px; border-radius: 10px; background: rgba(239,68,68,0.08); border: 1.5px dashed rgba(239,68,68,0.4); display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 1.05rem; flex-shrink: 0;">
                                                <i class="fas fa-user-slash" style="opacity: 0.7;"></i>
                                            </div>
                                        @endif
                                        <div class="pd-info">
                                            <div class="pd-title-row"
                                                style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                                <span class="pd-nama"
                                                    onclick="openBiodataPesertaDidikModal('{{ $item->peserta_didik_id }}')"
                                                    style="font-weight: 700; color: var(--text-color); cursor: pointer;">{{ $item->nama }}</span>
                                                @if ($item->status_keluar === 'Alumni')
                                                    <span class="badge"
                                                        style="font-size: 0.62rem; background: rgba(16,185,129,0.12); color: #10b981; padding: 2px 6px; white-space: nowrap;">
                                                        <i class="fas fa-graduation-cap me-1"></i>Alumni
                                                    </span>
                                                @else
                                                    <span class="badge"
                                                        style="font-size: 0.62rem; background: rgba(245,158,11,0.12); color: #f59e0b; padding: 2px 6px; white-space: nowrap;">
                                                        <i
                                                            class="fas fa-right-from-bracket me-1"></i>{{ $item->status_keluar ?: 'Tidak Aktif' }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if (!empty($item->nik))
                                                <div
                                                    style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">
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
                                    style="padding: 14px 18px; font-family: monospace; font-size: 0.84rem; color: var(--primary);"
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
                                            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
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
                                        style="font-size: 0.72rem; padding: 2px 7px;">
                                        {{ $item->jenis_kelamin ?: '-' }}
                                    </span>
                                </td>
                                <td class="cell-pd-rombel"
                                    style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.84rem;"
                                    data-label="Rombel Terakhir">
                                    <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">
                                        {{ $item->rombel_terakhir ?: '-' }}
                                    </span>
                                </td>
                                <td class="cell-pd-tingkat"
                                    style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);"
                                    data-label="Tingkat">
                                    {{ $item->tingkat_pendidikan_terakhir ?: '-' }}
                                </td>
                                <td class="cell-pd-ttl"
                                    style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);"
                                    data-label="Status & Tanggal">
                                    <div class="cell-col-right">
                                        <div style="font-weight: 600; color: var(--text-color);">
                                            {{ $item->status_keluar === 'Alumni' ? 'Lulus ' . ($item->tahun_lulus ?: '-') : ($item->status_keluar ?: 'Keluar') }}
                                        </div>
                                        <div style="font-size: 0.72rem; color: var(--text-muted);">
                                            {{ $item->tanggal_keluar ? \Carbon\Carbon::parse($item->tanggal_keluar)->format('d/m/Y') : '-' }}
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted); max-width: 220px;"
                                    data-label="Kontak & Alamat">
                                    <div class="cell-col-right">
                                        @if ($item->nomor_telepon_seluler)
                                            <div><i class="fas fa-phone-alt me-1 text-primary"
                                                    style="font-size: 0.72rem;"></i>
                                                <span class="copyable" data-copy="{{ $item->nomor_telepon_seluler }}"
                                                    data-label="No HP"
                                                    title="Klik untuk salin No HP">{{ $item->nomor_telepon_seluler }}</span>
                                            </div>
                                        @endif
                                        @if ($item->alamat_jalan)
                                            <div style="font-size: 0.74rem; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;"
                                                title="{{ $item->alamat_jalan }}">
                                                <i class="fas fa-map-marker-alt me-1"
                                                    style="font-size: 0.7rem;"></i>{{ $item->alamat_jalan }}
                                            </div>
                                        @endif
                                        @if (!$item->nomor_telepon_seluler && !$item->alamat_jalan)
                                            -
                                        @endif
                                    </div>
                                </td>
                                <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right;"
                                    data-label="Aksi">
                                    <div class="table-actions"
                                        style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                        @if (!empty($item->foto_url))
                                            <button type="button" class="btn-icon" title="Lihat Pasfoto"
                                                onclick="openPhotoPreviewModal('{{ $item->foto_url }}', '{{ addslashes($item->nama) }}')">
                                                <i class="fas fa-image" style="color: #10b981;"></i>
                                            </button>
                                        @endif
                                        <button type="button" class="btn-icon" title="Lihat Biodata Lengkap"
                                            onclick="openBiodataPesertaDidikModal('{{ $item->peserta_didik_id }}')">
                                            <i class="fas fa-id-card"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8"
                                    style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                    <i class="fas fa-user-slash mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                                    <div>Belum ada data siswa tidak aktif yang cocok.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($tidakAktifList->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $tidakAktifList])
                </div>
            @endif
