{{-- Partial: E-Learning — Tab Tugas --}}
@php $isPd = $isPd ?? false; @endphp
<div class="card datatable-card-wrapper" id="tableDataContainer" data-tab="tugas">
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

                @if (! $isPd)
                    <select id="filterStatus" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Status</option>
                        <option value="terbit" {{ ($status ?? '') === 'terbit' ? 'selected' : '' }}>Dibuka</option>
                        <option value="draft" {{ ($status ?? '') === 'draft' ? 'selected' : '' }}>Draf</option>
                        <option value="tutup" {{ ($status ?? '') === 'tutup' ? 'selected' : '' }}>Ditutup</option>
                    </select>
                @endif

                <select id="filterPembelajaran" class="toolbar-filter-select" style="height: 38px; max-width: 260px;">
                    <option value="">Semua Mata Pelajaran</option>
                    @foreach ($pembelajaranOptions as $opt)
                        <option value="{{ $opt->pembelajaran_id }}" {{ ($pembelajaranId ?? '') === $opt->pembelajaran_id ? 'selected' : '' }}>
                            {{ $opt->nama_rombel ? $opt->nama_rombel . ' — ' : '' }}{{ $opt->nama_mapel }}
                        </option>
                    @endforeach
                </select>

                <a href="{{ route('dashboard.elearning.index', ['tab' => 'tugas']) }}" class="btn btn-outline"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                    title="Reset Filter" aria-label="Reset Filter">
                    <i class="fas fa-rotate-left"></i>
                </a>
            </div>

            <div class="live-search-wrap" style="height: 38px;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearch" name="q" placeholder="Cari tugas / mata pelajaran..." value="{{ $q ?? '' }}" autocomplete="off">
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
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Judul Tugas</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Mata Pelajaran</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; white-space: nowrap;">Batas Waktu</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 80px;">Bobot</th>
                    @if ($isPd)
                        <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 120px;">Status Saya</th>
                        <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 80px;">Nilai</th>
                    @else
                        <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 120px;">Terkumpul</th>
                        <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">Status</th>
                    @endif
                    <th data-no-sort style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $index => $item)                    @php
                        $kumpul = $item->kumpulan_saya ?? null;
                        $lewat = $item->deadline && \Illuminate\Support\Carbon::parse($item->deadline)->isPast();
                    @endphp
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs); color: var(--text-muted);" data-label="No">
                            {{ $list->firstItem() + $index }}
                        </td>
                        <td style="padding: 12px 14px; font-size: var(--text-sm); font-weight: 600; color: var(--text-main);" data-label="Judul">
                            <span class="sae-truncate" style="max-width: 320px; display: inline-block;" title="{{ $item->judul }}">{{ $item->judul }}</span>
                            @if (!empty($item->deskripsi))
                                <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: 2px;">
                                    <span class="sae-truncate" style="max-width: 340px; display: inline-block;" title="{{ $item->deskripsi }}">{{ $item->deskripsi }}</span>
                                </div>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; font-size: var(--text-sm); color: var(--text-main);" data-label="Mata Pelajaran">
                            {{ $item->nama_mapel }}
                            <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ $item->nama_rombel ?? '-' }}</div>
                        </td>
                        <td style="padding: 12px 14px; font-size: var(--text-sm); color: {{ $lewat ? 'var(--danger)' : 'var(--text-main)' }};" data-label="Batas Waktu">
                            {{ $item->deadline ? \Illuminate\Support\Carbon::parse($item->deadline)->format('d/m/Y H:i') : '—' }}
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-sm); color: var(--text-main);" data-label="Bobot">{{ (int) $item->bobot }}</td>

                        @if ($isPd)
                            <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs);" data-label="Status Saya">
                                @if ($kumpul)
                                    <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: var(--success); padding: 3px 8px; border-radius: var(--radius-pill);">Terkumpul</span>
                                @elseif ($item->status === 'terbit' && ! $lewat)
                                    <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: var(--warning); padding: 3px 8px; border-radius: var(--radius-pill);">Belum</span>
                                @else
                                    <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: var(--danger); padding: 3px 8px; border-radius: var(--radius-pill);">Terlewat</span>
                                @endif
                            </td>
                            <td style="padding: 12px 14px; text-align: center; font-size: var(--text-sm); font-weight: 700; color: var(--text-main);" data-label="Nilai">
                                {{ $kumpul && $kumpul->nilai !== null ? rtrim(rtrim(number_format((float) $kumpul->nilai, 2, '.', ''), '0'), '.') : '—' }}
                            </td>
                        @else
                            <td style="padding: 12px 14px; text-align: center; font-size: var(--text-sm); color: var(--text-main);" data-label="Terkumpul">
                                {{ (int) $item->jumlah_kumpul }}
                                @if ((int) $item->jumlah_dinilai > 0)
                                    <div style="font-size: var(--text-xs); color: var(--success);">{{ (int) $item->jumlah_dinilai }} dinilai</div>
                                @endif
                            </td>
                            <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs);" data-label="Status">
                                @if ($item->status === 'terbit')
                                    <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: var(--success); padding: 3px 8px; border-radius: var(--radius-pill);">Dibuka</span>
                                @elseif ($item->status === 'tutup')
                                    <span class="badge" style="background: rgba(148, 163, 184, 0.15); color: var(--text-muted); padding: 3px 8px; border-radius: var(--radius-pill);">Ditutup</span>
                                @else
                                    <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: var(--warning); padding: 3px 8px; border-radius: var(--radius-pill);">Draf</span>
                                @endif
                            </td>
                        @endif

                        <td style="padding: 12px 14px; text-align: right;" data-label="Aksi">
                            <div class="table-actions" style="justify-content: flex-end; gap: var(--space-4);">
                                @if ($isPd)
                                    @if ($item->status === 'terbit')
                                        <button type="button" class="btn-icon btn-kumpul-tugas" data-id="{{ $item->id }}"
                                            data-judul="{{ $item->judul }}"
                                            data-jawaban="{{ $kumpul->jawaban_teks ?? '' }}"
                                            data-link="{{ $kumpul->link_url ?? '' }}"
                                            title="{{ $kumpul ? 'Ubah Jawaban' : 'Kumpulkan Tugas' }}"
                                            aria-label="{{ $kumpul ? 'Ubah Jawaban' : 'Kumpulkan Tugas' }}"
                                            style="color: var(--primary);">
                                            <i class="fas {{ $kumpul ? 'fa-pen-to-square' : 'fa-upload' }}"></i>
                                        </button>
                                    @endif
                                @else
                                    <a href="{{ route('dashboard.elearning.index', ['tab' => 'pengumpulan', 'pembelajaran_id' => $item->pembelajaran_id]) }}"
                                        class="btn-icon" title="Lihat Pengumpulan" aria-label="Lihat Pengumpulan" style="color: var(--accent);">
                                        <i class="fas fa-inbox"></i>
                                    </a>
                                    @if ($canUpdate)
                                        <button type="button" class="btn-icon btn-edit-tugas" data-id="{{ $item->id }}" title="Ubah Tugas" aria-label="Ubah Tugas" style="color: var(--primary);">
                                            <i class="fas fa-pen-to-square"></i>
                                        </button>
                                    @endif
                                    @if ($canDelete)
                                        <button type="button" class="btn-icon btn-delete-tugas" data-id="{{ $item->id }}" data-name="{{ $item->judul }}" title="Hapus Tugas" aria-label="Hapus Tugas" style="color: var(--danger);">
                                            <i class="fas fa-trash-can"></i>
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: var(--space-12) var(--space-8); color: var(--text-muted); font-size: var(--text-sm);">
                            <i class="fas fa-clipboard-list" style="font-size: var(--text-2xl); opacity: 0.4; display: block; margin-bottom: var(--space-4);"></i>
                            <div>Belum ada tugas E-Learning yang tercatat.</div>
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
    {{-- Modal Form Tugas (guru) --}}
    <div id="modalFormTugas" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 560px; width: 92%; margin: 0; border-radius: var(--radius-xl); padding: var(--space-10); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); border: 1px solid var(--border-color); background: var(--bg-card);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-6); border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-6);">
                <h3 id="modalTugasTitle" style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: var(--space-4);">
                    <i class="fas fa-plus-circle" style="color: var(--primary);"></i> Form Tugas
                </h3>
                <button type="button" id="btnCloseTugasModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg); padding: 4px;" title="Tutup" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formTugas" data-store="{{ route('dashboard.elearning.tugas.store') }}">
                @csrf

                <div style="margin-bottom: var(--space-6);">
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">
                        Mata Pelajaran / Rombel <span style="color: var(--danger);">*</span>
                    </label>
                    <select id="tugasPembelajaran" name="pembelajaran_id" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                        <option value="">— Pilih pembelajaran —</option>
                        @foreach ($pembelajaranOptions as $opt)
                            <option value="{{ $opt->pembelajaran_id }}">{{ $opt->nama_rombel ? $opt->nama_rombel . ' — ' : '' }}{{ $opt->nama_mapel }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom: var(--space-6);">
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">
                        Judul Tugas <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="text" id="tugasJudul" name="judul" required maxlength="200" placeholder="Judul tugas..."
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                    <div style="grid-column: span 2;">
                        <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Batas Waktu</label>
                        <input type="datetime-local" id="tugasDeadline" name="deadline"
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Bobot</label>
                        <input type="number" id="tugasBobot" name="bobot" min="0" max="100" value="0"
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                    </div>
                </div>

                <div style="margin-bottom: var(--space-6);">
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Status</label>
                    <select id="tugasStatus" name="status" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                        <option value="draft">Draf (belum dibuka)</option>
                        <option value="terbit">Terbit / Dibuka</option>
                        <option value="tutup">Ditutup</option>
                    </select>
                </div>

                <div style="margin-bottom: var(--space-6);">
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Deskripsi / Instruksi</label>
                    <textarea id="tugasDeskripsi" name="deskripsi" rows="4" maxlength="5000" placeholder="Instruksi pengerjaan tugas..."
                        style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box; resize: vertical;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: var(--space-4); border-top: 1px solid var(--border-color); padding-top: var(--space-6);">
                    <button type="button" id="btnCancelTugasModal" class="btn btn-outline" style="padding: 8px 16px; font-size: var(--text-sm); border-radius: var(--radius-md);">Batal</button>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: var(--text-sm); border-radius: var(--radius-md); font-weight: 600;">
                        <i class="fas fa-check" style="margin-right: 6px;"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
@else
    {{-- Modal Kumpulkan Tugas (peserta didik) --}}
    <div id="modalKumpulTugas" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 560px; width: 92%; margin: 0; border-radius: var(--radius-xl); padding: var(--space-10); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); border: 1px solid var(--border-color); background: var(--bg-card);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-6); border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-6);">
                <h3 style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: var(--space-4);">
                    <i class="fas fa-upload" style="color: var(--primary);"></i> Kumpulkan Tugas
                </h3>
                <button type="button" id="btnCloseKumpulModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg); padding: 4px;" title="Tutup" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <p id="kumpulJudul" style="font-size: var(--text-sm); font-weight: 600; color: var(--text-main); margin: 0 0 var(--space-6) 0;"></p>

            <form id="formKumpul" data-store="">
                @csrf

                <div style="margin-bottom: var(--space-6);">
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Jawaban (teks)</label>
                    <textarea id="kumpulJawaban" name="jawaban_teks" rows="5" maxlength="10000" placeholder="Tulis jawaban Anda di sini..."
                        style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box; resize: vertical;"></textarea>
                </div>

                <div style="margin-bottom: var(--space-6);">
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Tautan Jawaban (opsional)</label>
                    <input type="url" id="kumpulLink" name="link_url" maxlength="500" placeholder="https://youtu.be/... atau https://canva.com/..."
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                    <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: 4px;">
                        Tautan video (YouTube, Instagram, Facebook) atau dokumen daring (Canva, Slide, Docs, Spreadsheet).
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: var(--space-4); border-top: 1px solid var(--border-color); padding-top: var(--space-6);">
                    <button type="button" id="btnCancelKumpulModal" class="btn btn-outline" style="padding: 8px 16px; font-size: var(--text-sm); border-radius: var(--radius-md);">Batal</button>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: var(--text-sm); border-radius: var(--radius-md); font-weight: 600;">
                        <i class="fas fa-paper-plane" style="margin-right: 6px;"></i> Kumpulkan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
