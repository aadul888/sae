{{-- Partial: E-Learning — Tab Pengumpulan & Penilaian --}}
@php $isPd = $isPd ?? false; @endphp
<div class="card datatable-card-wrapper" id="tableDataContainer" data-tab="pengumpulan">
    <div class="datatable-toolbar">
        <div style="display: flex; flex-wrap: wrap; gap: var(--space-6); justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-wrap: wrap; gap: var(--space-4); align-items: center;">
                <div class="toolbar-entries">
                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                    <select id="perPageSelect" class="per-page-select" style="height: 38px;">
                        @foreach ([10, 15, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ ($perPage ?? 25) == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </div>

                <select id="filterPembelajaran" class="toolbar-filter-select" style="height: 38px; max-width: 260px;">
                    <option value="">Semua Mata Pelajaran</option>
                    @foreach ($pembelajaranOptions as $opt)
                        <option value="{{ $opt->pembelajaran_id }}" {{ ($pembelajaranId ?? '') === $opt->pembelajaran_id ? 'selected' : '' }}>
                            {{ $opt->nama_rombel ? $opt->nama_rombel . ' — ' : '' }}{{ $opt->nama_mapel }}
                        </option>
                    @endforeach
                </select>

                <a href="{{ route('dashboard.elearning.index', ['tab' => 'pengumpulan']) }}" class="btn btn-outline"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                    title="Reset Filter" aria-label="Reset Filter">
                    <i class="fas fa-rotate-left"></i>
                </a>
            </div>

            <div class="live-search-wrap" style="height: 38px;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearch" name="q" placeholder="{{ $isPd ? 'Cari tugas...' : 'Cari tugas / peserta didik...' }}" value="{{ $q ?? '' }}" autocomplete="off">
                <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color); text-align: left;">
                    <th data-no-sort style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">No</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tugas</th>
                    @if (! $isPd)
                        <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Peserta Didik</th>
                    @endif
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Mata Pelajaran</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; white-space: nowrap;">Dikumpulkan</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 120px;">Jawaban</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 90px;">Nilai</th>
                    <th data-no-sort style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 130px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $index => $item)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs); color: var(--text-muted);" data-label="No">
                            {{ $list->firstItem() + $index }}
                        </td>
                        <td style="padding: 12px 14px; font-size: var(--text-sm); font-weight: 600; color: var(--text-main);" data-label="Tugas">
                            <span class="sae-truncate" style="max-width: 260px; display: inline-block;" title="{{ $item->judul_tugas }}">{{ $item->judul_tugas }}</span>
                        </td>
                        @if (! $isPd)
                            <td style="padding: 12px 14px; font-size: var(--text-sm); color: var(--text-main);" data-label="Peserta Didik">
                                {{ $item->nama_peserta_didik ?? $item->peserta_didik_id }}
                            </td>
                        @endif
                        <td style="padding: 12px 14px; font-size: var(--text-sm); color: var(--text-muted);" data-label="Mata Pelajaran">
                            {{ $item->nama_mapel }}
                            <div style="font-size: var(--text-xs);">{{ $item->nama_rombel ?? '-' }}</div>
                        </td>
                        <td style="padding: 12px 14px; font-size: var(--text-sm); color: var(--text-muted);" data-label="Dikumpulkan">
                            {{ $item->submitted_at ? \Illuminate\Support\Carbon::parse($item->submitted_at)->format('d/m/Y H:i') : '—' }}
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs);" data-label="Jawaban">
                            @if (!empty($item->jawaban_teks))
                                <button type="button" class="btn-icon btn-lihat-jawaban" data-jawaban="{{ $item->jawaban_teks }}" title="Lihat Jawaban Teks" aria-label="Lihat Jawaban Teks" style="color: var(--accent);">
                                    <i class="fas fa-align-left"></i>
                                </button>
                            @endif
                            @if (!empty($item->link_url))
                                <a href="{{ $item->link_url }}" target="_blank" rel="noopener" class="btn-icon" title="Buka Tautan Jawaban" aria-label="Buka Tautan Jawaban" style="color: var(--primary);">
                                    <i class="fas fa-link"></i>
                                </a>
                            @endif
                            @if (empty($item->jawaban_teks) && empty($item->link_url))
                                <span style="color: var(--text-muted);">—</span>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-sm); font-weight: 700; color: {{ $item->nilai !== null ? 'var(--success)' : 'var(--text-muted)' }};" data-label="Nilai">
                            {{ $item->nilai !== null ? rtrim(rtrim(number_format((float) $item->nilai, 2, '.', ''), '0'), '.') : '—' }}
                        </td>
                        <td style="padding: 12px 14px; text-align: right;" data-label="Aksi">
                            <div class="table-actions" style="justify-content: flex-end; gap: var(--space-4);">
                                @if ($isPd)
                                    <button type="button" class="btn-icon btn-tarik-kumpul" data-id="{{ $item->id }}" title="Tarik Pengumpulan" aria-label="Tarik Pengumpulan" style="color: var(--danger);">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                @elseif ($canUpdate)
                                    <button type="button" class="btn-icon btn-nilai-kumpul" data-id="{{ $item->id }}"
                                        data-nama="{{ $item->nama_peserta_didik ?? '' }}" data-nilai="{{ $item->nilai !== null ? (float) $item->nilai : '' }}"
                                        title="Beri / Ubah Nilai" aria-label="Beri / Ubah Nilai" style="color: var(--success);">
                                        <i class="fas fa-star"></i>
                                    </button>
                                    @if ($canDelete)
                                        <button type="button" class="btn-icon btn-tarik-kumpul" data-id="{{ $item->id }}" title="Hapus Pengumpulan" aria-label="Hapus Pengumpulan" style="color: var(--danger);">
                                            <i class="fas fa-trash-can"></i>
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isPd ? 7 : 8 }}" style="text-align: center; padding: var(--space-12) var(--space-8); color: var(--text-muted); font-size: var(--text-sm);">
                            <i class="fas fa-inbox" style="font-size: var(--text-2xl); opacity: 0.4; display: block; margin-bottom: var(--space-4);"></i>
                            <div>Belum ada pengumpulan tugas yang tercatat.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($list instanceof \Illuminate\Pagination\LengthAwarePaginator && $list->hasPages())
        <div class="datatable-footer">
            @include('partials.datatable-pagination', ['paginator' => $list])
        </div>
    @endif
</div>

@if (! $isPd)
    {{-- Modal Nilai --}}
    <div id="modalNilai" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 420px; width: 92%; margin: 0; border-radius: var(--radius-xl); padding: var(--space-10); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); border: 1px solid var(--border-color); background: var(--bg-card);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-6); border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-6);">
                <h3 style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: var(--space-4);">
                    <i class="fas fa-star" style="color: var(--success);"></i> Beri Nilai
                </h3>
                <button type="button" id="btnCloseNilaiModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg); padding: 4px;" title="Tutup" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <p id="nilaiNama" style="font-size: var(--text-sm); color: var(--text-muted); margin: 0 0 var(--space-6) 0;"></p>

            <form id="formNilai" data-store="">
                @csrf
                <div style="margin-bottom: var(--space-6);">
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Nilai (0–100)</label>
                    <input type="number" id="nilaiInput" name="nilai" min="0" max="100" step="0.01" required
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: var(--space-4); border-top: 1px solid var(--border-color); padding-top: var(--space-6);">
                    <button type="button" id="btnCancelNilaiModal" class="btn btn-outline" style="padding: 8px 16px; font-size: var(--text-sm); border-radius: var(--radius-md);">Batal</button>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: var(--text-sm); border-radius: var(--radius-md); font-weight: 600;">
                        <i class="fas fa-check" style="margin-right: 6px;"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

{{-- Modal Lihat Jawaban Teks (semua peran) --}}
<div id="modalJawaban" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="max-width: 560px; width: 92%; margin: 0; border-radius: var(--radius-xl); padding: var(--space-10); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); border: 1px solid var(--border-color); background: var(--bg-card);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-6); border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-6);">
            <h3 style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: var(--space-4);">
                <i class="fas fa-align-left" style="color: var(--accent);"></i> Jawaban Teks
            </h3>
            <button type="button" id="btnCloseJawabanModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg); padding: 4px;" title="Tutup" aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="jawabanIsi" style="font-size: var(--text-sm); color: var(--text-main); white-space: pre-wrap; word-break: break-word; max-height: 50vh; overflow-y: auto;"></div>
    </div>
</div>
