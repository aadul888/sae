@php
    $list = $verifikasiList;
    $statusFilter = $statusLaporanFilter ?? '';
@endphp

<div class="card datatable-card-wrapper" id="tableDataContainer">
    <div class="datatable-toolbar">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <div class="toolbar-entries">
                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                    <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                        @foreach ([10, 15, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </div>

                <select id="filterStatusLaporan" name="status_laporan" class="toolbar-filter-select"
                    style="height: 38px; min-width: 190px;">
                    <option value="">Semua Status</option>
                    <option value="diajukan" {{ $statusFilter === 'diajukan' ? 'selected' : '' }}>Menunggu Verifikasi
                    </option>
                    <option value="diverifikasi" {{ $statusFilter === 'diverifikasi' ? 'selected' : '' }}>Disetujui</option>
                    <option value="ditolak" {{ $statusFilter === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>

                @if (!empty($q) || $statusFilter !== '')
                    <a href="{{ route('dashboard.pip.index', array_filter(['tab' => 'verifikasi', 'tahun' => $activeTahun])) }}"
                        class="btn btn-outline btn-reset-filter"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                        title="Reset filter" data-live-reset>
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>

            <div class="live-search-wrap" style="height: 38px;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchVerifikasi" name="q" placeholder="Cari nama / NISN..." value="{{ $q }}"
                    autocomplete="off">
                <button type="button" id="clearSearchVerifikasi"
                    class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                    <th style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); min-width: 200px;">
                        Peserta Didik</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); min-width: 160px;">
                        Tahap / SK</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); min-width: 200px;">
                        Rincian Penggunaan</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); text-align: right; min-width: 120px;">
                        Nominal</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); min-width: 140px;">
                        Status</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); text-align: center; width: 100px;">
                        Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $lap)
                    @php
                        $totalRincian = array_sum(array_map(fn($x) => (float) ($x['nominal'] ?? 0), $lap->rincian_list));
                        $badge = [
                            'diajukan' => ['Menunggu', 'badge-warning', 'fa-hourglass-half'],
                            'diverifikasi' => ['Disetujui', 'badge-success', 'fa-circle-check'],
                            'ditolak' => ['Ditolak', 'badge-danger', 'fa-circle-xmark'],
                        ][$lap->status] ?? ['Diajukan', 'badge-outline', 'fa-circle-info'];
                    @endphp
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem);" data-label="Peserta Didik">
                            <div style="font-weight: 700; color: var(--text-color);">{{ $lap->nama_peserta }}</div>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                NISN {{ $lap->nisn_peserta ?: '-' }}
                            </div>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                {{ $lap->dilaporkan_oleh ?: '-' }}
                                @if ($lap->created_at)
                                    &middot; {{ \Illuminate\Support\Carbon::parse($lap->created_at)->format('d/m/Y H:i') }}
                                @endif
                            </div>
                        </td>
                        <td style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem);" data-label="Tahap / SK">
                            <div style="font-weight: 600;">{{ $lap->tahap ?: '-' }}</div>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                Tahap ID: {{ $lap->tahap_id ?: '-' }}
                            </div>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                {{ $lap->jenis_sk === 'nominasi' ? 'SK Nominasi' : 'SK Pemberian' }}
                            </div>
                        </td>
                        <td style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem);" data-label="Rincian">
                            @forelse ($lap->rincian_list as $rin)
                                <div style="display: flex; justify-content: space-between; gap: 12px;">
                                    <span>{{ $rin['kategori'] ?? '-' }}</span>
                                    <strong style="white-space: nowrap;">Rp
                                        {{ number_format((float) ($rin['nominal'] ?? 0), 0, ',', '.') }}</strong>
                                </div>
                            @empty
                                <span class="text-muted">-</span>
                            @endforelse
                            <div style="margin-top: 4px; font-weight: 700;">
                                Total: Rp {{ number_format($totalRincian, 0, ',', '.') }}
                            </div>
                        </td>
                        <td style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); text-align: right; white-space: nowrap;"
                            data-label="Nominal">
                            Rp {{ number_format((float) $lap->nominal, 0, ',', '.') }}
                        </td>
                        <td style="padding: 12px 14px;" data-label="Status">
                            <span class="badge {{ $badge[1] }}" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                <i class="fas {{ $badge[2] }} me-1"></i>{{ $badge[0] }}
                            </span>
                            @if (!empty($lap->catatan_verifikasi))
                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 4px;">
                                    {{ $lap->catatan_verifikasi }}
                                </div>
                            @endif
                            @if (!empty($lap->diverifikasi_oleh))
                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 4px;">
                                    oleh {{ $lap->diverifikasi_oleh }}
                                </div>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: center;" data-label="Aksi">
                            <button type="button" class="btn-icon pip-verif-open"
                                data-verif-id="{{ $lap->id }}"
                                data-verif-nama="{{ $lap->nama_peserta }}"
                                data-verif-tahap="{{ $lap->tahap ?: '-' }}{{ $lap->tahap_id ? ' / ' . $lap->tahap_id : '' }}"
                                data-verif-status="{{ $lap->status }}"
                                data-verif-action="{{ route('dashboard.pip.verifikasi-laporan', ['id' => $lap->id]) }}"
                                data-verif-template="#pipVerifTpl-{{ $lap->id }}"
                                title="Tinjau Laporan" aria-label="Tinjau Laporan" style="color: var(--primary);">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>

                            <template id="pipVerifTpl-{{ $lap->id }}">
                                <div style="font-size: var(--text-sm, 0.8125rem);">
                                    <div style="margin-bottom: 10px;">
                                        <strong>Peserta:</strong> {{ $lap->nama_peserta }}
                                        <span class="text-muted">(NISN {{ $lap->nisn_peserta ?: '-' }})</span><br>
                                        <strong>Tahap / SK:</strong> {{ $lap->tahap ?: '-' }}
                                        <span class="text-muted">(Tahap ID: {{ $lap->tahap_id ?: '-' }})</span>
                                        ({{ $lap->jenis_sk === 'nominasi' ? 'SK Nominasi' : 'SK Pemberian' }})<br>
                                        <strong>Dana diterima:</strong> Rp
                                        {{ number_format((float) $lap->nominal, 0, ',', '.') }}
                                    </div>

                                    <div style="font-weight: 700; margin-bottom: 6px;">Rincian Penggunaan</div>
                                    <div style="margin-bottom: 12px;">
                                        @forelse ($lap->rincian_list as $rin)
                                            <div style="display: flex; justify-content: space-between; gap: 12px;">
                                                <span>{{ $rin['kategori'] ?? '-' }}@if (!empty($rin['keterangan']))
                                                        <span class="text-muted">— {{ $rin['keterangan'] }}</span>
                                                    @endif</span>
                                                <strong style="white-space: nowrap;">Rp
                                                    {{ number_format((float) ($rin['nominal'] ?? 0), 0, ',', '.') }}</strong>
                                            </div>
                                        @empty
                                            <span class="text-muted">Tidak ada rincian.</span>
                                        @endforelse
                                        <div style="margin-top: 6px; font-weight: 700;">
                                            Total: Rp {{ number_format($totalRincian, 0, ',', '.') }}
                                        </div>
                                    </div>

                                    <div style="font-weight: 700; margin-bottom: 6px;">Bukti Lampiran</div>
                                    <div style="margin-bottom: 4px;">
                                        @forelse ($lap->bukti as $b)
                                            @php
                                                $buktiUrl = asset('storage/' . ltrim($b->file_path, '/'));
                                                $isGambar = in_array(
                                                    strtolower(pathinfo($b->file_path, PATHINFO_EXTENSION)),
                                                    ['jpg', 'jpeg', 'png'],
                                                    true,
                                                );
                                            @endphp
                                            <button type="button" class="pip-verif-bukti"
                                                data-bukti-url="{{ $buktiUrl }}"
                                                data-bukti-type="{{ $isGambar ? 'image' : 'pdf' }}"
                                                data-bukti-name="{{ $b->nama_asli ?: 'Bukti' }}"
                                                style="display: flex; align-items: center; gap: 10px; width: 100%; text-align: left; background: transparent; border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); padding: 8px; margin-bottom: 8px; cursor: pointer; color: var(--text-color);">
                                                @if ($isGambar)
                                                    <img src="{{ $buktiUrl }}" alt="{{ $b->nama_asli ?: 'Bukti' }}"
                                                        loading="lazy"
                                                        style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color); flex-shrink: 0;">
                                                @else
                                                    <span
                                                        style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 6px; background: rgba(239,68,68,0.1); color: var(--danger, #ef4444); font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </span>
                                                @endif
                                                <span style="min-width: 0;">
                                                    <span
                                                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                        {{ $b->nama_asli ?: 'Bukti' }}
                                                    </span>
                                                    <span
                                                        style="display: block; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                                        <i class="fas fa-magnifying-glass-plus me-1"></i>Klik untuk meninjau
                                                    </span>
                                                </span>
                                            </button>
                                        @empty
                                            <span class="text-muted" style="font-size: var(--text-sm, 0.8125rem);">Tidak ada
                                                bukti.</span>
                                        @endforelse
                                    </div>

                                    @if (!empty($lap->catatan))
                                        <div style="margin-top: 10px; font-size: var(--text-sm, 0.8125rem);">
                                            <strong>Catatan pelapor:</strong> {{ $lap->catatan }}
                                        </div>
                                    @endif
                                </div>
                            </template>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding: 35px; text-align: center; color: var(--text-muted);">
                            Belum ada laporan penggunaan dana yang masuk.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($list && $list->hasPages())
        <div style="padding: 14px 16px; border-top: 1px solid var(--border-color);">
            @include('partials.datatable-pagination', ['paginator' => $list])
        </div>
    @endif
</div>

<!-- Modal Verifikasi Laporan -->
<div id="pipVerifModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="pipVerifModalTitle"
    style="display: none;">
    <div class="card" style="max-width: 680px; width: 95%;">
        <div
            style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color); margin-bottom: 14px;">
            <h4 id="pipVerifModalTitle" style="margin: 0; font-size: var(--text-lg, 1.125rem); font-weight: 800;">
                Tinjau Laporan
            </h4>
            <button type="button" class="btn btn-outline" data-verif-close aria-label="Tutup"
                style="padding: 4px 10px;">&times;</button>
        </div>

        <div id="pipVerifModalBody" style="overflow-y: auto; max-height: 48vh; margin-bottom: 14px;"></div>

        <form method="POST" id="pipVerifForm" style="border-top: 1px solid var(--border-color); padding-top: 14px;">
            @csrf
            <label for="pipVerifCatatan"
                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; margin-bottom: 6px;">
                Catatan Verifikasi (opsional)
            </label>
            <textarea id="pipVerifCatatan" name="catatan_verifikasi" rows="3" class="form-control"
                placeholder="Misal: bukti kurang jelas, nominal tidak sesuai, atau catatan persetujuan."
                style="width: 100%; margin-bottom: 12px;"></textarea>

            <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end;">
                <button type="submit" name="status" value="ditolak" class="btn btn-outline"
                    style="color: var(--danger, #ef4444); border-color: var(--danger, #ef4444);">
                    <i class="fas fa-circle-xmark me-1"></i> Tolak
                </button>
                <button type="submit" name="status" value="diajukan" class="btn btn-outline">
                    <i class="fas fa-rotate-left me-1"></i> Kembalikan
                </button>
                <button type="submit" name="status" value="diverifikasi" class="btn btn-primary">
                    <i class="fas fa-circle-check me-1"></i> Setujui
                </button>
            </div>
        </form>

        <!-- Pratinjau bukti di dalam modal (gambar / PDF) -->
        <div id="pipVerifBuktiViewer" class="pip-verif-viewer" hidden>
            <div class="pip-verif-viewer-head">
                <strong id="pipVerifBuktiName">Bukti Lampiran</strong>
                <button type="button" class="btn btn-outline" id="pipVerifBuktiClose" aria-label="Tutup pratinjau"
                    style="padding: 4px 10px;">&times;</button>
            </div>
            <div class="pip-verif-viewer-body" id="pipVerifBuktiBody"></div>
        </div>
    </div>
</div>

@push('scripts')
    <script
        src="{{ asset('js/pip-verifikasi.js') }}?v={{ file_exists(public_path('js/pip-verifikasi.js')) ? filemtime(public_path('js/pip-verifikasi.js')) : time() }}">
    </script>
@endpush
