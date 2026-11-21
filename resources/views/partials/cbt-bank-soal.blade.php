{{-- Partial: CBT — Tab Bank Soal --}}
<div class="card datatable-card-wrapper" id="tableDataContainer" data-tab="bank-soal">
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

                <a href="{{ route('dashboard.cbt.index', ['tab' => 'bank-soal']) }}" class="btn btn-outline"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                    title="Reset Filter" aria-label="Reset Filter">
                    <i class="fas fa-rotate-left"></i>
                </a>
            </div>

            <div class="live-search-wrap" style="height: 38px;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearch" name="q" placeholder="Cari soal / mata pelajaran..." value="{{ $q ?? '' }}" autocomplete="off">
                <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    @php
        $tipeMeta = [
            'pg' => ['label' => 'Pilihan Ganda', 'icon' => 'fa-list-ol', 'color' => 'var(--primary)'],
            'benar_salah' => ['label' => 'Benar/Salah', 'icon' => 'fa-check-double', 'color' => 'var(--accent)'],
            'isian' => ['label' => 'Isian', 'icon' => 'fa-i-cursor', 'color' => 'var(--purple)'],
            'uraian' => ['label' => 'Uraian', 'icon' => 'fa-align-left', 'color' => 'var(--warning)'],
        ];
        $koleksi = $list instanceof \Illuminate\Pagination\LengthAwarePaginator ? $list->getCollection() : $list;
        $perMapel = collect($koleksi)->groupBy('pembelajaran_id');
    @endphp

    @forelse ($perMapel as $pembelajaranId => $soalGroup)
        @php
            $kepala = $soalGroup->first();
            $judulMapel = $kepala->nama_mapel ?? 'Mata Pelajaran';
        @endphp
        <details class="cbt-mapel-acc" open style="border: 1px solid var(--border-color); border-radius: var(--radius-lg); margin-bottom: var(--space-6); background: rgba(255,255,255,0.015); overflow: hidden;">
            <summary style="cursor: pointer; padding: 14px 16px; display: flex; align-items: center; gap: var(--space-4); list-style: none;">
                <i class="fas fa-chevron-right cbt-acc-caret" style="color: var(--primary); transition: transform 0.2s ease;"></i>
                <span style="font-size: var(--text-sm); font-weight: 700; color: var(--text-main);">{{ $judulMapel }}</span>
                @if (!empty($kepala->nama_rombel))
                    <span style="font-size: var(--text-xs); color: var(--text-muted);">{{ $kepala->nama_rombel }}</span>
                @endif
                <span class="badge" style="margin-left: auto; background: rgba(99,102,241,0.15); color: var(--primary); padding: 3px 10px; border-radius: var(--radius-pill); font-size: var(--text-xs);">{{ $soalGroup->count() }} soal</span>
            </summary>
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%; border-top: 1px solid var(--border-color);">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color); text-align: left;">
                            <th data-no-sort style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">No</th>
                            <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Pertanyaan</th>
                            <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 130px;">Tipe</th>
                            <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 70px;">Bobot</th>
                            <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 90px;">Tingkat</th>
                            <th data-no-sort style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($soalGroup as $index => $item)
                            @php
                                $opsi = is_string($item->opsi) ? json_decode($item->opsi, true) : $item->opsi;
                                $meta = $tipeMeta[$item->tipe] ?? ['label' => $item->tipe, 'icon' => 'fa-cube', 'color' => 'var(--text-muted)'];
                                $nomor = ($list instanceof \Illuminate\Pagination\LengthAwarePaginator ? $list->firstItem() : 1) + $index;
                                $punya = !empty($item->gambar) || !empty($item->video_url);
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs); color: var(--text-muted);" data-label="No">
                                    {{ $nomor }}
                                </td>
                                <td style="padding: 12px 14px; font-size: var(--text-sm); font-weight: 600; color: var(--text-main);" data-label="Pertanyaan">
                                    <span class="sae-truncate" style="max-width: 420px; display: inline-block;" title="{{ $item->pertanyaan }}">{{ $item->pertanyaan }}</span>
                                    <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: 2px; display: flex; gap: 8px; flex-wrap: wrap;">
                                        @if (!empty($opsi))<span>{{ count($opsi) }} opsi</span>@endif
                                        @if (!empty($item->gambar))<span><i class="fas fa-image"></i> gambar</span>@endif
                                        @if (!empty($item->video_url))<span><i class="fas fa-video"></i> video</span>@endif
                                    </div>
                                </td>
                                <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs);" data-label="Tipe">
                                    <span class="badge" style="background: rgba(148, 163, 184, 0.15); color: {{ $meta['color'] }}; padding: 3px 8px; border-radius: var(--radius-pill);">
                                        <i class="fas {{ $meta['icon'] }}" style="font-size: var(--text-2xs); margin-right: 3px;"></i>
                                        {{ $meta['label'] }}
                                    </span>
                                </td>
                                <td style="padding: 12px 14px; text-align: center; font-size: var(--text-sm); color: var(--text-main);" data-label="Bobot">{{ (int) $item->bobot }}</td>
                                <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs); color: var(--text-muted);" data-label="Tingkat">{{ $item->tingkat ? ucfirst($item->tingkat) : '—' }}</td>
                                <td style="padding: 12px 14px; text-align: right;" data-label="Aksi">
                                    <div class="table-actions" style="justify-content: flex-end; gap: var(--space-4);">
                                        <button type="button" class="btn-icon btn-lihat-soal" data-id="{{ $item->id }}" title="Lihat Tampilan Soal" aria-label="Lihat Tampilan Soal" style="color: var(--accent);">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-soal" data-id="{{ $item->id }}" title="Ubah Soal" aria-label="Ubah Soal" style="color: var(--primary);">
                                                <i class="fas fa-pen-to-square"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-soal" data-id="{{ $item->id }}" title="Hapus Soal" aria-label="Hapus Soal" style="color: var(--danger);">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @empty
        <div style="text-align: center; padding: var(--space-12) var(--space-8); color: var(--text-muted); font-size: var(--text-sm);">
            <i class="fas fa-circle-question" style="font-size: var(--text-2xl); opacity: 0.4; display: block; margin-bottom: var(--space-4);"></i>
            <div>Belum ada soal pada bank soal.</div>
        </div>
    @endforelse

    @if ($list instanceof \Illuminate\Pagination\LengthAwarePaginator && $list->hasPages())
        <div class="datatable-footer">
            @include('partials.datatable-pagination', ['paginator' => $list])
        </div>
    @endif
</div>

{{-- Modal Form Soal --}}
<div id="modalSoal" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="max-width: 620px; width: 92%; margin: 0; border-radius: var(--radius-xl); padding: var(--space-10); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); border: 1px solid var(--border-color); background: var(--bg-card); max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-6); border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-6);">
            <h3 id="soalModalTitle" style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: var(--space-4);">
                <i class="fas fa-plus-circle" style="color: var(--primary);"></i> Form Soal
            </h3>
            <button type="button" id="btnCloseSoalModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg); padding: 4px;" title="Tutup" aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formSoal" data-store="{{ route('dashboard.cbt.soal.store') }}" enctype="multipart/form-data">
            @csrf

            <div style="margin-bottom: var(--space-6);">
                <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">
                    Mata Pelajaran / Rombel <span style="color: var(--danger);">*</span>
                </label>
                <select id="soalPembelajaran" name="pembelajaran_id" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                    <option value="">— Pilih pembelajaran —</option>
                    @foreach ($pembelajaranOptions as $opt)
                        <option value="{{ $opt->pembelajaran_id }}">{{ $opt->nama_rombel ? $opt->nama_rombel . ' — ' : '' }}{{ $opt->nama_mapel }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Tipe Soal</label>
                    <select id="soalTipe" name="tipe" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                        <option value="pg">Pilihan Ganda</option>
                        <option value="benar_salah">Benar / Salah</option>
                        <option value="isian">Isian Singkat</option>
                        <option value="uraian">Uraian</option>
                    </select>
                </div>
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Bobot</label>
                    <input type="number" id="soalBobot" name="bobot" min="1" max="100" value="1"
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Tingkat</label>
                    <select id="soalTingkat" name="tingkat" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                        <option value="">—</option>
                        <option value="mudah">Mudah</option>
                        <option value="sedang">Sedang</option>
                        <option value="sulit">Sulit</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: var(--space-6);">
                <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">
                    Pertanyaan <span style="color: var(--danger);">*</span>
                </label>
                <textarea id="soalPertanyaan" name="pertanyaan" rows="3" required maxlength="5000" placeholder="Tulis pertanyaan..."
                    style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box; resize: vertical;"></textarea>
            </div>

            {{-- Media soal: gambar + video --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Gambar Soal</label>
                    <input type="file" id="soalGambar" name="gambar" accept="image/*"
                        style="width: 100%; padding: 6px 10px; border: 1px dashed var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-xs); box-sizing: border-box;">
                    <div id="soalGambarPreviewWrap" style="display: none; margin-top: 6px; align-items: center; gap: 8px;">
                        <img id="soalGambarPreview" alt="Pratinjau gambar" style="max-height: 90px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                        <label style="font-size: var(--text-xs); color: var(--danger); display: inline-flex; align-items: center; gap: 4px; cursor: pointer;">
                            <input type="checkbox" id="soalHapusGambar" name="hapus_gambar" value="1"> hapus gambar
                        </label>
                    </div>
                </div>
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Video (URL YouTube/Vimeo)</label>
                    <input type="url" id="soalVideoUrl" name="video_url" maxlength="500" placeholder="https://youtu.be/..."
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                    <div style="font-size: var(--text-2xs); color: var(--text-muted); margin-top: 4px;">Kosongkan bila tanpa video.</div>
                </div>
            </div>

            {{-- Opsi untuk PG --}}
            <div id="wrapOpsiPg" style="margin-bottom: var(--space-6);">
                <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Opsi Jawaban (minimal 2)</label>
                <div id="opsiContainer" style="display: flex; flex-direction: column; gap: var(--space-3);"></div>
                <button type="button" id="btnTambahOpsi" class="btn btn-outline" style="margin-top: var(--space-3); padding: 6px 12px; font-size: var(--text-xs); border-radius: var(--radius-md);">
                    <i class="fas fa-plus" style="margin-right: 4px;"></i> Tambah Opsi
                </button>
            </div>

            {{-- Kunci jawaban --}}
            <div id="wrapKunci" style="margin-bottom: var(--space-6);">
                <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Kunci Jawaban</label>
                <select id="soalKunciOpsi" name="kunci" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                    <option value="">— Pilih kunci —</option>
                </select>
                <input type="text" id="soalKunciTeks" maxlength="500" placeholder="Kunci jawaban (teks)"
                    style="display: none; width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                <div id="kunciUraianNote" style="display: none; font-size: var(--text-xs); color: var(--text-muted); margin-top: 4px;">
                    Soal uraian dinilai manual — tanpa kunci otomatis.
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: var(--space-4); border-top: 1px solid var(--border-color); padding-top: var(--space-6);">
                <button type="button" id="btnCancelSoalModal" class="btn btn-outline" style="padding: 8px 16px; font-size: var(--text-sm); border-radius: var(--radius-md);">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: var(--text-sm); border-radius: var(--radius-md); font-weight: 600;">
                    <i class="fas fa-check" style="margin-right: 6px;"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Tambah Banyak Soal (bulk, satu mapel) --}}
<div id="modalBulkSoal" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="max-width: 780px; width: 94%; margin: 0; border-radius: var(--radius-xl); padding: var(--space-10); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); border: 1px solid var(--border-color); background: var(--bg-card); max-height: 92vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-6); border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-6);">
            <h3 style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: var(--space-4);">
                <i class="fas fa-layer-group" style="color: var(--primary);"></i> Tambah Banyak Soal
            </h3>
            <button type="button" id="btnCloseBulkSoalModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg); padding: 4px;" title="Tutup" aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formBulkSoal" data-store="{{ route('dashboard.cbt.soal.bulk') }}">
            @csrf
            <div style="margin-bottom: var(--space-6);">
                <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">
                    Mata Pelajaran / Rombel <span style="color: var(--danger);">*</span>
                </label>
                <select id="bulkPembelajaran" name="pembelajaran_id" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                    <option value="">— Pilih pembelajaran —</option>
                    @foreach ($pembelajaranOptions as $opt)
                        <option value="{{ $opt->pembelajaran_id }}">{{ $opt->nama_rombel ? $opt->nama_rombel . ' — ' : '' }}{{ $opt->nama_mapel }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-4); margin-bottom: var(--space-4);">
                <div style="font-size: var(--text-sm); font-weight: 700; color: var(--text-main);">
                    <i class="fas fa-list-ol" style="color: var(--success);"></i> Daftar Soal
                </div>
                <button type="button" class="btn btn-outline" id="btnTambahSoalBulk" style="height: 34px; padding: 0 12px; font-size: var(--text-xs);">
                    <i class="fas fa-plus" style="margin-right: 4px;"></i> Tambah Baris
                </button>
            </div>
            <div id="bulkSoalContainer" style="display: flex; flex-direction: column; gap: var(--space-4);"></div>
            <div id="bulkSoalKosong" style="font-size: var(--text-xs); color: var(--text-muted); text-align: center; padding: var(--space-4);">Belum ada baris soal.</div>

            <div style="display: flex; justify-content: flex-end; gap: var(--space-4); border-top: 1px solid var(--border-color); padding-top: var(--space-6); margin-top: var(--space-6);">
                <button type="button" id="btnCancelBulkSoalModal" class="btn btn-outline" style="padding: 8px 16px; font-size: var(--text-sm); border-radius: var(--radius-md);">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: var(--text-sm); border-radius: var(--radius-md); font-weight: 600;">
                    <i class="fas fa-check" style="margin-right: 6px;"></i> Simpan Semua
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Impor Soal (Excel/CSV) --}}
<div id="modalImportSoal" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="max-width: 560px; width: 92%; margin: 0; border-radius: var(--radius-xl); padding: var(--space-10); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); border: 1px solid var(--border-color); background: var(--bg-card); max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-6); border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-6);">
            <h3 style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: var(--space-4);">
                <i class="fas fa-file-import" style="color: var(--primary);"></i> Impor Soal (Excel/CSV)
            </h3>
            <button type="button" id="btnCloseImportSoalModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg); padding: 4px;" title="Tutup" aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formImportSoal" method="POST" action="{{ route('dashboard.cbt.soal.import') }}" enctype="multipart/form-data">
            @csrf
            <div style="font-size: var(--text-xs); color: var(--text-muted); margin-bottom: var(--space-6); line-height: 1.6;">
                Kolom yang dikenali: <strong>pembelajaran</strong> (nama mapel atau ID), <strong>tipe</strong> (pg/benar_salah/isian/uraian),
                <strong>pertanyaan</strong>, <strong>opsi_a</strong>…<strong>opsi_e</strong>, <strong>kunci</strong> (huruf A–E untuk PG, "Benar"/"Salah" untuk B–S, teks untuk isian), <strong>bobot</strong>, <strong>tingkat</strong>.
                <div style="margin-top: var(--space-4);">
                    <a href="{{ route('dashboard.cbt.soal.template') }}" class="btn btn-outline" style="height: 34px; padding: 0 12px; font-size: var(--text-xs); display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-download"></i> Unduh Template
                    </a>
                </div>
            </div>
            <div style="margin-bottom: var(--space-6);">
                <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Berkas <span style="color: var(--danger);">*</span></label>
                <input type="file" id="importSoalFile" name="file" accept=".xlsx,.xls,.csv,.txt" required
                    style="width: 100%; padding: 8px 12px; border: 1px dashed var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: var(--space-4); border-top: 1px solid var(--border-color); padding-top: var(--space-6);">
                <button type="button" id="btnCancelImportSoalModal" class="btn btn-outline" style="padding: 8px 16px; font-size: var(--text-sm); border-radius: var(--radius-md);">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: var(--text-sm); border-radius: var(--radius-md); font-weight: 600;">
                    <i class="fas fa-upload" style="margin-right: 6px;"></i> Impor
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Lihat Tampilan Soal (read-only, seperti yang dilihat peserta didik) --}}
<div id="modalLihatSoal" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="max-width: 680px; width: 94%; margin: 0; border-radius: var(--radius-xl); padding: var(--space-10); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); border: 1px solid var(--border-color); background: var(--bg-card); max-height: 92vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: var(--space-4); margin-bottom: var(--space-6); border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-6);">
            <div>
                <span id="lihatSoalMapel" style="font-size: var(--text-xs); color: var(--text-muted);"></span>
                <h3 style="font-size: var(--text-lg); font-weight: 800; color: var(--text-main); margin: 4px 0 0 0;"><i class="fas fa-eye" style="color: var(--accent);"></i> Tampilan Soal</h3>
            </div>
            <button type="button" id="btnCloseLihatSoal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg); padding: 4px;" title="Tutup" aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="lihatSoalBody" style="display: flex; flex-direction: column; gap: var(--space-6);"></div>

        <div style="display: flex; justify-content: flex-end; margin-top: var(--space-8); border-top: 1px solid var(--border-color); padding-top: var(--space-6);">
            <button type="button" id="btnTutupLihatSoal" class="btn btn-outline" style="padding: 8px 18px; font-size: var(--text-sm); border-radius: var(--radius-md);">Tutup</button>
        </div>
    </div>
</div>
