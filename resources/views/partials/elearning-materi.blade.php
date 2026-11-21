{{-- Partial: E-Learning — Tab Materi --}}
@php $isPd = $isPd ?? false; @endphp
<div class="card datatable-card-wrapper" id="tableDataContainer" data-tab="materi">
    <div class="datatable-toolbar">
        <div style="display: flex; flex-wrap: wrap; gap: var(--space-6); justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-wrap: wrap; gap: var(--space-4); align-items: center;">
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

                    <select id="filterStatus" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Status</option>
                        <option value="terbit" {{ ($status ?? '') === 'terbit' ? 'selected' : '' }}>Terbit</option>
                        <option value="draft" {{ ($status ?? '') === 'draft' ? 'selected' : '' }}>Draf</option>
                    </select>

                    <select id="filterPembelajaran" class="toolbar-filter-select" style="height: 38px; max-width: 260px;">
                        <option value="">Semua Mata Pelajaran</option>
                        @foreach ($pembelajaranOptions as $opt)
                            <option value="{{ $opt->pembelajaran_id }}" {{ ($pembelajaranId ?? '') === $opt->pembelajaran_id ? 'selected' : '' }}>
                                {{ $opt->nama_rombel ? $opt->nama_rombel . ' — ' : '' }}{{ $opt->nama_mapel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <a href="{{ route('dashboard.elearning.index', ['tab' => 'materi']) }}" class="btn btn-outline"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                    title="Reset Filter" aria-label="Reset Filter">
                    <i class="fas fa-rotate-left"></i>
                </a>
            </div>

            <div class="live-search-wrap" style="height: 38px;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearch" name="q" placeholder="Cari materi / mata pelajaran..." value="{{ $q ?? '' }}" autocomplete="off">
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
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Judul Materi</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Mata Pelajaran</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Rombel</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 90px;">Tipe</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 90px;">Kuis</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">Status</th>
                    <th data-no-sort style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 110px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $index => $item)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs); color: var(--text-muted);" data-label="No">
                            {{ $list->firstItem() + $index }}
                        </td>
                        <td style="padding: 12px 14px; font-size: var(--text-sm); font-weight: 600; color: var(--text-main);" data-label="Judul">
                            <span class="sae-truncate" style="max-width: 320px; display: inline-block;" title="{{ $item->judul }}">{{ $item->judul }}</span>
                            @if (!empty($item->url_embed))
                                <a href="{{ $item->url_embed }}" target="_blank" rel="noopener" style="margin-left: var(--space-4); color: var(--accent); font-size: var(--text-xs);" title="Buka tautan materi">
                                    <i class="fas fa-arrow-up-right-from-square"></i>
                                </a>
                            @endif
                            @if (!empty($item->file_path))
                                <a href="{{ asset('storage/' . $item->file_path) }}" target="_blank" rel="noopener" style="margin-left: var(--space-4); color: var(--primary); font-size: var(--text-xs);" title="Unduh berkas materi">
                                    <i class="fas fa-paperclip"></i>
                                </a>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; font-size: var(--text-sm); color: var(--text-main);" data-label="Mata Pelajaran">{{ $item->nama_mapel }}</td>
                        <td style="padding: 12px 14px; font-size: var(--text-sm); color: var(--text-muted);" data-label="Rombel">{{ $item->nama_rombel ?? '-' }}</td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs);" data-label="Tipe">
                            @php
                                $tipeMeta = [
                                    'file' => ['label' => 'Berkas', 'icon' => 'fa-file-lines', 'color' => 'var(--primary)'],
                                    'link' => ['label' => 'Tautan', 'icon' => 'fa-link', 'color' => 'var(--accent)'],
                                    'teks' => ['label' => 'Teks', 'icon' => 'fa-align-left', 'color' => 'var(--purple)'],
                                ][$item->tipe] ?? ['label' => $item->tipe, 'icon' => 'fa-cube', 'color' => 'var(--text-muted)'];
                            @endphp
                            <span class="badge" style="background: rgba(148, 163, 184, 0.15); color: {{ $tipeMeta['color'] }}; padding: 3px 8px; border-radius: var(--radius-pill);">
                                <i class="fas {{ $tipeMeta['icon'] }}" style="font-size: var(--text-2xs); margin-right: 3px;"></i>
                                {{ $tipeMeta['label'] }}
                            </span>
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs);" data-label="Kuis">
                            @if (($item->jumlah_soal ?? 0) > 0)
                                <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: var(--success); padding: 3px 8px; border-radius: var(--radius-pill);" title="{{ $item->jumlah_soal }} soal">
                                    <i class="fas fa-list-check" style="font-size: var(--text-2xs); margin-right: 3px;"></i>{{ $item->jumlah_soal }}
                                </span>
                                @if (isset($item->hasil_saya) && $item->hasil_saya !== null)
                                    <div style="font-size: var(--text-2xs); color: var(--text-muted); margin-top: 2px;">skor {{ rtrim(rtrim(number_format((float) $item->hasil_saya, 2, '.', ''), '0'), '.') }}</div>
                                @endif
                            @else
                                <span style="color: var(--text-muted);">—</span>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs);" data-label="Status">
                            @if ($item->status === 'terbit')
                                <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: var(--success); padding: 3px 8px; border-radius: var(--radius-pill);">Terbit</span>
                            @else
                                <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: var(--warning); padding: 3px 8px; border-radius: var(--radius-pill);">Draf</span>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: right;" data-label="Aksi">
                            <div class="table-actions" style="justify-content: flex-end; gap: var(--space-4);">
                                <button type="button" class="btn-icon btn-buka-materi" data-id="{{ $item->id }}" title="Buka Materi" aria-label="Buka Materi" style="color: var(--accent);">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if (! $isPd && ($item->jumlah_soal ?? 0) > 0)
                                    <button type="button" class="btn-icon btn-rekap-kuis" data-id="{{ $item->id }}" data-name="{{ $item->judul }}" title="Rekap Nilai Kuis" aria-label="Rekap Nilai Kuis" style="color: var(--success);">
                                        <i class="fas fa-chart-simple"></i>
                                    </button>
                                @endif
                                @if ($canUpdate)
                                    <button type="button" class="btn-icon btn-edit-row" data-id="{{ $item->id }}" title="Ubah Materi" aria-label="Ubah Materi" style="color: var(--primary);">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" class="btn-icon btn-delete-row" data-id="{{ $item->id }}" data-name="{{ $item->judul }}" title="Hapus Materi" aria-label="Hapus Materi" style="color: var(--danger);">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: var(--space-12) var(--space-8); color: var(--text-muted); font-size: var(--text-sm);">
                            <i class="fas fa-folder-open" style="font-size: var(--text-2xl); opacity: 0.4; display: block; margin-bottom: var(--space-4);"></i>
                            <div>Belum ada materi E-Learning yang tercatat.</div>
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

{{-- Modal Form Materi --}}
<div id="modalFormItem" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card modal-card-wide" style="width: 94%; max-width: 860px; margin: 0; border-radius: var(--radius-xl); padding: var(--space-10); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); border: 1px solid var(--border-color); background: var(--bg-card); max-height: 92vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-6); border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-6);">
            <h3 id="modalTitle" style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: var(--space-4);">
                <i class="fas fa-plus-circle" style="color: var(--primary);"></i> Form Materi
            </h3>
            <button type="button" id="btnCloseModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg); padding: 4px;" title="Tutup" aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="mainFormItem" data-store="{{ route('dashboard.elearning.store') }}">
            @csrf

            <div style="margin-bottom: var(--space-6);">
                <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">
                    Mata Pelajaran / Rombel <span style="color: var(--danger);">*</span>
                </label>
                <select id="inputPembelajaran" name="pembelajaran_id" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                    <option value="">— Pilih pembelajaran —</option>
                    @foreach ($pembelajaranOptions as $opt)
                        <option value="{{ $opt->pembelajaran_id }}">{{ $opt->nama_rombel ? $opt->nama_rombel . ' — ' : '' }}{{ $opt->nama_mapel }}</option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom: var(--space-6);">
                <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">
                    Judul Materi <span style="color: var(--danger);">*</span>
                </label>
                <input type="text" id="inputJudul" name="judul" required maxlength="200" placeholder="Judul materi..."
                    style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Tipe Materi</label>
                    <select id="inputTipe" name="tipe" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                        <option value="link">Tautan: video / dokumen daring</option>
                        <option value="file">Berkas Dokumen</option>
                        <option value="teks">Teks / Deskripsi</option>
                    </select>
                </div>
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Status</label>
                    <select id="inputStatus" name="status" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                        <option value="draft">Draf</option>
                        <option value="terbit">Terbit</option>
                    </select>
                </div>
            </div>

            <div id="wrapUrl" style="margin-bottom: var(--space-6);">
                <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">
                    URL Tautan
                </label>
                <input type="url" id="inputUrl" name="url_embed" maxlength="500" placeholder="https://youtu.be/... atau https://canva.com/..."
                    style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: 4px;">
                    Video (YouTube, Instagram, Facebook) atau dokumen daring (Canva, Slide, Docs, Spreadsheet, Drive).
                </div>
            </div>

            <div id="wrapFile" style="margin-bottom: var(--space-6); display: none;">
                <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">
                    Berkas Materi (maks 10 MB)
                </label>
                <input type="file" id="inputFile" name="file_materi"
                    accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.webp"
                    style="width: 100%; padding: 6px 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
            </div>

            <div style="margin-bottom: var(--space-6);">
                <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Deskripsi</label>
                <textarea id="inputDeskripsi" name="deskripsi" rows="2" maxlength="5000" placeholder="Deskripsi singkat materi..."
                    style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box; resize: vertical;"></textarea>
            </div>

            {{-- Editor isi materi gaya Office/Word --}}
            <div style="margin-bottom: var(--space-6);">
                <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">
                    Isi Materi <span style="color: var(--text-muted); font-weight: 400;">(editor seperti Word — opsional)</span>
                </label>
                <div class="rich-editor-wrap">
                    <div class="rich-editor-toolbar" id="materiEditorToolbar">
                        <button type="button" class="tb-btn" data-command="bold" title="Tebal (Ctrl+B)"><i class="fas fa-bold"></i></button>
                        <button type="button" class="tb-btn" data-command="italic" title="Miring (Ctrl+I)"><i class="fas fa-italic"></i></button>
                        <button type="button" class="tb-btn" data-command="underline" title="Garis Bawah (Ctrl+U)"><i class="fas fa-underline"></i></button>
                        <button type="button" class="tb-btn" data-command="strikeThrough" title="Coret"><i class="fas fa-strikethrough"></i></button>
                        <span class="tb-sep"></span>
                        <button type="button" class="tb-btn" data-command="formatBlock" data-val="h2" title="Judul Besar"><i class="fas fa-heading"></i><sub style="font-weight:700;">1</sub></button>
                        <button type="button" class="tb-btn" data-command="formatBlock" data-val="h3" title="Sub Judul"><i class="fas fa-heading"></i><sub style="font-weight:700;">2</sub></button>
                        <button type="button" class="tb-btn" data-command="formatBlock" data-val="p" title="Paragraf"><i class="fas fa-paragraph"></i></button>
                        <span class="tb-sep"></span>
                        <button type="button" class="tb-btn" data-command="justifyLeft" title="Rata Kiri"><i class="fas fa-align-left"></i></button>
                        <button type="button" class="tb-btn" data-command="justifyCenter" title="Rata Tengah"><i class="fas fa-align-center"></i></button>
                        <button type="button" class="tb-btn" data-command="justifyRight" title="Rata Kanan"><i class="fas fa-align-right"></i></button>
                        <button type="button" class="tb-btn" data-command="justifyFull" title="Rata Kiri Kanan"><i class="fas fa-align-justify"></i></button>
                        <span class="tb-sep"></span>
                        <button type="button" class="tb-btn" data-command="insertUnorderedList" title="Daftar Bullet"><i class="fas fa-list-ul"></i></button>
                        <button type="button" class="tb-btn" data-command="insertOrderedList" title="Daftar Angka"><i class="fas fa-list-ol"></i></button>
                        <button type="button" class="tb-btn" data-command="formatBlock" data-val="blockquote" title="Kutipan"><i class="fas fa-quote-left"></i></button>
                        <button type="button" class="tb-btn" data-command="insertHorizontalRule" title="Garis Pemisah"><i class="fas fa-minus"></i></button>
                        <span class="tb-sep"></span>
                        <button type="button" class="tb-btn" id="btnMateriInsertLink" title="Sisipkan Tautan"><i class="fas fa-link"></i></button>
                        <button type="button" class="tb-btn" data-command="unlink" title="Hapus Tautan"><i class="fas fa-link-slash"></i></button>
                        <button type="button" class="tb-btn" data-command="removeFormat" title="Hapus Format"><i class="fas fa-eraser"></i></button>
                        <span class="tb-sep"></span>
                        <button type="button" class="tb-btn" data-command="undo" title="Urungkan"><i class="fas fa-rotate-left"></i></button>
                        <button type="button" class="tb-btn" data-command="redo" title="Ulangi"><i class="fas fa-rotate-right"></i></button>
                    </div>
                    <div id="materiEditorContent" class="rich-editor-content" contenteditable="true" data-placeholder="Tuliskan isi materi lengkap di sini — format seperti di dokumen Word..."></div>
                    <textarea name="isi_html" id="inputIsiHtml" style="position: absolute; opacity: 0; width: 1px; height: 1px; pointer-events: none;"></textarea>
                </div>
            </div>

            {{-- Kuis auto-nilai --}}
            <div style="margin-bottom: var(--space-6); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: var(--space-6);">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-4); margin-bottom: var(--space-4);">
                    <div>
                        <div style="font-size: var(--text-sm); font-weight: 700; color: var(--text-main);">
                            <i class="fas fa-list-check" style="color: var(--success);"></i> Kuis Otomatis
                        </div>
                        <div style="font-size: var(--text-xs); color: var(--text-muted);">Soal PG / Benar-Salah / Isian. PG &amp; B-S dinilai otomatis dari kunci.</div>
                    </div>
                    <button type="button" class="btn btn-outline" id="btnTambahSoalKuis" style="height: 34px; padding: 0 12px; font-size: var(--text-xs); white-space: nowrap;">
                        <i class="fas fa-plus" style="margin-right: 4px;"></i> Tambah Soal
                    </button>
                </div>
                <div id="kuisSoalContainer" style="display: flex; flex-direction: column; gap: var(--space-4);"></div>
                <div id="kuisKosong" style="font-size: var(--text-xs); color: var(--text-muted); text-align: center; padding: var(--space-4);">Belum ada soal kuis.</div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: var(--space-4); border-top: 1px solid var(--border-color); padding-top: var(--space-6);">
                <button type="button" id="btnCancelModal" class="btn btn-outline" style="padding: 8px 16px; font-size: var(--text-sm); border-radius: var(--radius-md);">
                    Batal
                </button>
                <button type="submit" id="btnSaveModal" class="btn btn-primary" style="padding: 8px 18px; font-size: var(--text-sm); border-radius: var(--radius-md); font-weight: 600;">
                    <i class="fas fa-check" style="margin-right: 6px;"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Baca Materi + Kerjakan Kuis --}}
<div id="modalBacaMateri" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="max-width: 760px; width: 94%; margin: 0; border-radius: var(--radius-xl); padding: var(--space-10); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); border: 1px solid var(--border-color); background: var(--bg-card); max-height: 92vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: var(--space-4); margin-bottom: var(--space-6); border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-6);">
            <div>
                <span id="bacaMeta" style="font-size: var(--text-xs); color: var(--text-muted);"></span>
                <h3 id="bacaJudul" style="font-size: var(--text-lg); font-weight: 800; color: var(--text-main); margin: 4px 0 0 0;"></h3>
            </div>
            <button type="button" id="btnCloseBacaModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg); padding: 4px;" title="Tutup" aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="bacaTautan" style="margin-bottom: var(--space-6); display: none;">
            <a id="bacaTautanLink" href="#" target="_blank" rel="noopener" class="btn btn-outline" style="font-size: var(--text-sm);">
                <i class="fas fa-arrow-up-right-from-square" style="margin-right: 6px;"></i> Buka Tautan Materi
            </a>
        </div>

        <div id="bacaIsi" class="materi-isi-render" style="font-size: var(--text-base); line-height: 1.7; color: var(--text-main); margin-bottom: var(--space-8);"></div>

        <div id="wrapKuisPd" style="display: none;">
            <h4 style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0 0 var(--space-4) 0; display: flex; align-items: center; gap: var(--space-4);">
                <i class="fas fa-list-check" style="color: var(--success);"></i> Kuis
            </h4>
            <div id="kuisPdHasil" style="display: none; padding: var(--space-4) var(--space-6); border-radius: var(--radius-md); background: rgba(16, 185, 129, 0.12); color: var(--success); font-size: var(--text-sm); margin-bottom: var(--space-6);"></div>
            <form id="formKuisPd">
                <div id="kuisPdContainer" style="display: flex; flex-direction: column; gap: var(--space-6);"></div>
                <div style="display: flex; justify-content: flex-end; margin-top: var(--space-6);">
                    <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: var(--text-sm); border-radius: var(--radius-md); font-weight: 600;">
                        <i class="fas fa-paper-plane" style="margin-right: 6px;"></i> Kirim Jawaban
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Rekap Nilai Kuis (guru/admin) --}}
<div id="modalRekapKuis" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="max-width: 680px; width: 94%; margin: 0; border-radius: var(--radius-xl); padding: var(--space-10); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); border: 1px solid var(--border-color); background: var(--bg-card); max-height: 92vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: var(--space-4); margin-bottom: var(--space-6); border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-6);">
            <div>
                <span style="font-size: var(--text-xs); color: var(--text-muted);">Rekap Nilai Kuis</span>
                <h3 id="rekapJudul" style="font-size: var(--text-lg); font-weight: 800; color: var(--text-main); margin: 4px 0 0 0;"></h3>
            </div>
            <button type="button" id="btnCloseRekapModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg); padding: 4px;" title="Tutup" aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="rekapRingkas" class="rekap-ringkas"></div>
        <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
            <table class="table" style="width: 100%; border-collapse: collapse; margin: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); text-align: left;">
                        <th data-no-sort style="padding: 10px 12px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 44px; text-align: center;">No</th>
                        <th style="padding: 10px 12px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Peserta Didik</th>
                        <th style="padding: 10px 12px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 90px;">Benar</th>
                        <th style="padding: 10px 12px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 80px;">Skor</th>
                        <th style="padding: 10px 12px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 120px;">Waktu</th>
                    </tr>
                </thead>
                <tbody id="rekapTbody">
                    <tr><td colspan="5" style="text-align: center; padding: var(--space-8); color: var(--text-muted); font-size: var(--text-sm);">Memuat…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
