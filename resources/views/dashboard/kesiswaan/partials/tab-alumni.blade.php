            <!-- TAB 5: ALUMNI (DIALIHKAN SETELAH USULAN PERUBAHAN) -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Nama Alumni</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                NISN / NIPD</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                L/P</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Rombel Terakhir</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Tahun Lulus</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($alumniList as $item)
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                <td class="cell-pd-nama"
                                    style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: 0.88rem;"
                                    data-label="Nama Alumni">
                                    <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                        @if (!empty($item->foto_url))
                                            <div class="pd-foto-thumb" data-biodata-id="{{ $item->peserta_didik_id }}"
                                                role="button" tabindex="0"
                                                title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                                style="width: 42px; height: 42px; border-radius: 10px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25); cursor: pointer; transition: all 0.2s ease;">
                                                <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}"
                                                    style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                        @else
                                            <div class="pd-foto-thumb empty"
                                                data-biodata-id="{{ $item->peserta_didik_id }}" role="button"
                                                tabindex="0"
                                                title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                                style="width: 42px; height: 42px; border-radius: 10px; background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.05rem; flex-shrink: 0; cursor: pointer; transition: all 0.2s ease;">
                                                <i class="fas fa-user-graduate" style="opacity: 0.7;"></i>
                                            </div>
                                        @endif
                                        <div class="pd-info">
                                            <div class="pd-title-row"
                                                style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                                <span class="pd-nama" data-biodata-id="{{ $item->peserta_didik_id }}"
                                                    role="button" tabindex="0"
                                                    title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                                    style="font-weight: 700; color: var(--text-color); cursor: pointer; transition: color 0.2s ease;">{{ $item->nama }}</span>
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
                                <td style="padding: 14px 18px; text-align: center;" data-label="Tahun Lulus">
                                    <span class="badge badge-primary"
                                        style="font-weight: 700; font-size: 0.8rem; padding: 3px 8px;">
                                        {{ $item->tahun_lulus ?: ($item->tanggal_keluar ? date('Y', strtotime($item->tanggal_keluar)) : '-') }}
                                    </span>
                                </td>
                                <td style="padding: 14px 18px; font-size: 0.85rem; color: var(--text-muted);"
                                    data-label="Keterangan">
                                    {{ $item->alasan_keluar ?: 'Alumni / Tamat Belajar' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6"
                                    style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                    <i class="fas fa-graduation-cap mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                                    <div>Belum ada data arsip alumni.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($alumniList->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $alumniList])
                </div>
            @endif
        @endif
