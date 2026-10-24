            <!-- TAB 3: VERIFIKASI BERKAS FISIK & DIGITAL -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                            @php
                                $colsBerkas = [
                                    ['nama', 'Nama Siswa'],
                                    ['nisn', 'NISN / NIPD'],
                                    ['nama_rombel', 'Rombel'],
                                ];
                            @endphp
                            @foreach ($colsBerkas as [$key, $label])
                                <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                                    style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;"
                                    data-sort="{{ $key }}">
                                    {{ $label }}
                                    <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                            @endforeach
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Akta Lahir</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Kartu Keluarga</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Ijazah SMP</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                KTP Ortu</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                KIP / PIP</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($berkasList as $item)
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                <td class="cell-pd-nama"
                                    style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: 0.88rem;"
                                    data-label="Nama Siswa">
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
                                <td class="cell-pd-rombel"
                                    style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.84rem; order: 3;"
                                    data-label="Rombel">
                                    <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">
                                        {{ $item->rombel_nama ?: '-' }}
                                    </span>
                                </td>
                                @php
                                    $aktaF = $item->files['akta_kelahiran'] ?? null;
                                    $kkF = $item->files['kartu_keluarga'] ?? null;
                                    $ijzF = $item->files['ijazah_smp'] ?? null;
                                    $ktpF = $item->files['ktp_orang_tua'] ?? null;
                                    $kipF = $item->files['kip_pip'] ?? null;
                                @endphp
                                <td class="cell-pd-berkas-item" style="padding: 14px 18px; text-align: center; order: 5;"
                                    data-label="Akta Lahir">
                                    @if ($aktaF)
                                        @if ($aktaF->status === 'valid')
                                            <span class="badge badge-success" title="Valid / Sesuai"><i
                                                    class="fas fa-circle-check"></i> Valid</span>
                                        @elseif ($aktaF->status === 'tidak_valid')
                                            <span class="badge badge-danger" title="Tidak Valid / Ditolak"><i
                                                    class="fas fa-circle-xmark"></i> Ditolak</span>
                                        @else
                                            <span class="badge badge-warning" title="Menunggu Verifikasi"><i
                                                    class="fas fa-clock"></i> PDF</span>
                                        @endif
                                    @elseif ($item->akta_kelahiran)
                                        <span class="badge badge-success" title="Fisik Terverifikasi"><i
                                                class="fas fa-check"></i></span>
                                    @else
                                        <span class="text-muted" style="font-size: 0.8rem;">-</span>
                                    @endif
                                </td>
                                <td class="cell-pd-berkas-item" style="padding: 14px 18px; text-align: center; order: 6;"
                                    data-label="Kartu Keluarga">
                                    @if ($kkF)
                                        @if ($kkF->status === 'valid')
                                            <span class="badge badge-success" title="Valid / Sesuai"><i
                                                    class="fas fa-circle-check"></i> Valid</span>
                                        @elseif ($kkF->status === 'tidak_valid')
                                            <span class="badge badge-danger" title="Tidak Valid / Ditolak"><i
                                                    class="fas fa-circle-xmark"></i> Ditolak</span>
                                        @else
                                            <span class="badge badge-warning" title="Menunggu Verifikasi"><i
                                                    class="fas fa-clock"></i> PDF</span>
                                        @endif
                                    @elseif ($item->kartu_keluarga)
                                        <span class="badge badge-success" title="Fisik Terverifikasi"><i
                                                class="fas fa-check"></i></span>
                                    @else
                                        <span class="text-muted" style="font-size: 0.8rem;">-</span>
                                    @endif
                                </td>
                                <td class="cell-pd-berkas-item" style="padding: 14px 18px; text-align: center; order: 7;"
                                    data-label="Ijazah SMP">
                                    @if ($ijzF)
                                        @if ($ijzF->status === 'valid')
                                            <span class="badge badge-success" title="Valid / Sesuai"><i
                                                    class="fas fa-circle-check"></i> Valid</span>
                                        @elseif ($ijzF->status === 'tidak_valid')
                                            <span class="badge badge-danger" title="Tidak Valid / Ditolak"><i
                                                    class="fas fa-circle-xmark"></i> Ditolak</span>
                                        @else
                                            <span class="badge badge-warning" title="Menunggu Verifikasi"><i
                                                    class="fas fa-clock"></i> PDF</span>
                                        @endif
                                    @elseif ($item->ijazah_smp)
                                        <span class="badge badge-success" title="Fisik Terverifikasi"><i
                                                class="fas fa-check"></i></span>
                                    @else
                                        <span class="text-muted" style="font-size: 0.8rem;">-</span>
                                    @endif
                                </td>
                                <td class="cell-pd-berkas-item" style="padding: 14px 18px; text-align: center; order: 8;"
                                    data-label="KTP Ortu">
                                    @if ($ktpF)
                                        @if ($ktpF->status === 'valid')
                                            <span class="badge badge-success" title="Valid / Sesuai"><i
                                                    class="fas fa-circle-check"></i> Valid</span>
                                        @elseif ($ktpF->status === 'tidak_valid')
                                            <span class="badge badge-danger" title="Tidak Valid / Ditolak"><i
                                                    class="fas fa-circle-xmark"></i> Ditolak</span>
                                        @else
                                            <span class="badge badge-warning" title="Menunggu Verifikasi"><i
                                                    class="fas fa-clock"></i> PDF</span>
                                        @endif
                                    @elseif ($item->ktp_orang_tua)
                                        <span class="badge badge-success" title="Fisik Terverifikasi"><i
                                                class="fas fa-check"></i></span>
                                    @else
                                        <span class="text-muted" style="font-size: 0.8rem;">-</span>
                                    @endif
                                </td>
                                <td class="cell-pd-berkas-item" style="padding: 14px 18px; text-align: center; order: 9;"
                                    data-label="KIP / PIP">
                                    @if ($kipF)
                                        @if ($kipF->status === 'valid')
                                            <span class="badge badge-success" title="Valid / Sesuai"><i
                                                    class="fas fa-circle-check"></i> Valid</span>
                                        @elseif ($kipF->status === 'tidak_valid')
                                            <span class="badge badge-danger" title="Tidak Valid / Ditolak"><i
                                                    class="fas fa-circle-xmark"></i> Ditolak</span>
                                        @else
                                            <span class="badge badge-warning" title="Menunggu Verifikasi"><i
                                                    class="fas fa-clock"></i> PDF</span>
                                        @endif
                                    @elseif ($item->kip_pip)
                                        <span class="badge badge-success" title="Fisik Terverifikasi"><i
                                                class="fas fa-check"></i></span>
                                    @else
                                        <span class="text-muted" style="font-size: 0.8rem;">-</span>
                                    @endif
                                </td>
                                <td class="cell-pd-aksi"
                                    style="padding: 14px 18px; text-align: right; order: 10 !important;"
                                    data-label="Aksi">
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
                                    style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                    <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
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
