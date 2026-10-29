{{-- Partial Tab 2: Inventaris & Alat Praktik Jurusan yang di-input Laboran --}}
<div class="card datatable-card-wrapper" id="tableDataContainer">
    <!-- Toolbar Header -->
    <div class="datatable-toolbar">
        <form method="GET" action="{{ route('dashboard.sarpras.aset.index') }}" id="filterFormLaboran" style="margin: 0;">
            <input type="hidden" name="tab" value="laboran">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                        <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                            @foreach ([10, 15, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ request('perPage', 15) == $n ? 'selected' : '' }}>
                                    {{ $n }}
                                </option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    <select name="jurusan_id" id="filterJurusanLab" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Jurusan</option>
                        @foreach ($jurusanList as $jur)
                            <option value="{{ $jur->jurusan_id }}" {{ $jurusanFilter == $jur->jurusan_id ? 'selected' : '' }}>
                                {{ $jur->singkatan ? $jur->singkatan . ' — ' : '' }}{{ $jur->nama_jurusan }}
                            </option>
                        @endforeach
                    </select>

                    <select name="jenis" id="filterJenisLab" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Jenis</option>
                        <option value="alat" {{ $jenisFilter === 'alat' ? 'selected' : '' }}>Alat Praktik</option>
                        <option value="bahan_habis_pakai" {{ $jenisFilter === 'bahan_habis_pakai' ? 'selected' : '' }}>Bahan Habis Pakai</option>
                    </select>

                    <select name="kondisi" id="filterKondisiLab" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Kondisi</option>
                        <option value="baik" {{ $kondisiFilter === 'baik' ? 'selected' : '' }}>Baik</option>
                        <option value="rusak_ringan" {{ $kondisiFilter === 'rusak_ringan' ? 'selected' : '' }}>Rusak Ringan</option>
                        <option value="rusak_berat" {{ $kondisiFilter === 'rusak_berat' ? 'selected' : '' }}>Rusak Berat</option>
                        <option value="kedaluwarsa" {{ $kondisiFilter === 'kedaluwarsa' ? 'selected' : '' }}>Kedaluwarsa</option>
                    </select>

                    @if ($search || $jurusanFilter || $jenisFilter || $kondisiFilter || $labFilter)
                        <a href="{{ route('dashboard.sarpras.aset.index', ['tab' => 'laboran']) }}"
                            class="btn btn-outline btn-reset-filter"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                            title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                    @endif
                </div>

                <div class="live-search-wrap" style="height: 38px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="q" id="liveSearch" value="{{ $search }}"
                        placeholder="Cari item lab, kode, spesifikasi..." autocomplete="off">
                    <button type="button" id="clearSearch" class="clear-search {{ $search ? 'visible' : '' }}"
                        title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Konten Datatable -->
    <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); ">
                    <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        No</th>
                    <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Item / Alat Praktik</th>
                    <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Jurusan &amp; Ruang Lab</th>
                    <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                        Jenis</th>
                    <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                        Stok</th>
                    <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                        Rincian Kondisi</th>
                    <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                        Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($labList as $idx => $item)
                    <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                        <td data-label="No" style="padding: 14px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-muted);">
                            {{ $labList->firstItem() + $idx }}
                        </td>
                        <td data-label="Item" style="padding: 14px 18px;">
                            <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                {{ $item->nama_item }}
                            </div>
                            <div style="font-size: var(--text-sm, 0.8125rem); color: var(--primary); font-family: monospace;">
                                {{ $item->kode_item }}
                            </div>
                            @if ($item->spesifikasi)
                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); line-height: 1.35; margin-top: 2px;">
                                    {{ \Illuminate\Support\Str::limit($item->spesifikasi, 60) }}
                                </div>
                            @endif
                        </td>
                        <td data-label="Jurusan & Lokasi" style="padding: 14px 18px; font-size: var(--text-base, 0.875rem);">
                            @if ($item->jurusan_singkatan || $item->nama_jurusan)
                                <span class="badge" style="font-size: var(--text-2xs, 0.6875rem); padding: 3px 8px; background: rgba(99,102,241,0.15); color: var(--primary-light, #6366f1); border: 1px solid rgba(99,102,241,0.25);">
                                    <i class="fas fa-graduation-cap me-1"></i>{{ $item->jurusan_singkatan ?: $item->nama_jurusan }}
                                </span>
                            @else
                                <span class="badge" style="font-size: var(--text-2xs, 0.6875rem); padding: 3px 8px; background: rgba(148,163,184,0.15); color: var(--text-muted);">
                                    Lintas Jurusan
                                </span>
                            @endif
                            <div style="font-weight: 600; color: var(--text-color); font-size: var(--text-sm, 0.8125rem); margin-top: 4px;">
                                <i class="fas fa-flask text-muted me-1"></i>{{ $item->ruang_lab_nama ?: 'Ruang Lab' }}
                            </div>
                            @if ($item->lokasi_lemari_rak)
                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                    Rak: {{ $item->lokasi_lemari_rak }}
                                </div>
                            @endif
                        </td>
                        <td data-label="Jenis" style="padding: 14px 18px; text-align: center;">
                            @if ($item->jenis === 'alat')
                                <span class="badge badge-info" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">Alat</span>
                            @else
                                <span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">BHP</span>
                            @endif
                        </td>
                        <td data-label="Stok" style="padding: 14px 18px; text-align: center;">
                            <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color);">
                                {{ (float) $item->stok_tersedia }} / {{ (float) $item->stok_total }}
                            </div>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                {{ $item->satuan }}
                            </div>
                        </td>
                        <td data-label="Rincian Kondisi" style="padding: 14px 18px; text-align: center;">
                            <div style="display: inline-flex; gap: 4px; flex-wrap: wrap; justify-content: center;">
                                @if ((float) ($item->jumlah_baik ?? 0) > 0)
                                    <span class="badge badge-success" style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px;" title="Kondisi Baik">
                                        {{ (float) $item->jumlah_baik }} Baik
                                    </span>
                                @endif
                                @if ((float) ($item->jumlah_rusak_ringan ?? 0) > 0)
                                    <span class="badge badge-warning" style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px;" title="Rusak Ringan">
                                        {{ (float) $item->jumlah_rusak_ringan }} RR
                                    </span>
                                @endif
                                @if ((float) ($item->jumlah_rusak_berat ?? 0) > 0)
                                    <span class="badge badge-danger" style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px;" title="Rusak Berat">
                                        {{ (float) $item->jumlah_rusak_berat }} RB
                                    </span>
                                @endif
                                @if ((float) ($item->jumlah_kedaluwarsa ?? 0) > 0)
                                    <span class="badge" style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px; background: rgba(148,163,184,0.2); color: var(--text-muted);" title="Kedaluwarsa">
                                        {{ (float) $item->jumlah_kedaluwarsa }} Exp
                                    </span>
                                @endif
                                @if (((float) ($item->jumlah_baik ?? 0) + (float) ($item->jumlah_rusak_ringan ?? 0) + (float) ($item->jumlah_rusak_berat ?? 0) + (float) ($item->jumlah_kedaluwarsa ?? 0)) == 0)
                                    <span class="badge {{ $item->kondisi === 'baik' ? 'badge-success' : ($item->kondisi === 'rusak_ringan' ? 'badge-warning' : 'badge-danger') }}" style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px;">
                                        {{ ucfirst(str_replace('_', ' ', $item->kondisi)) }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td data-label="Aksi" style="padding: 14px 18px; text-align: right;">
                            <div class="table-actions" style="justify-content: flex-end; gap: 6px;">
                                <a href="{{ route('dashboard.laboran.inventaris.index', ['q' => $item->kode_item]) }}"
                                    class="btn-icon"
                                    style="color: var(--primary, #3b82f6); text-decoration: none;"
                                    title="Lihat di Modul Inventaris Laboran">
                                    <i class="fas fa-arrow-up-right-from-square"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted);">
                            <i class="fas fa-vial mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5;"></i>
                            <div>Belum ada inventaris alat/bahan laboratorium per jurusan yang cocok.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer Resmi SAE -->
    @if ($labList->hasPages())
        <div class="datatable-footer">
            @include('partials.datatable-pagination', ['paginator' => $labList])
        </div>
    @endif
</div>
