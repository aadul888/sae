{{-- Partial: CBT — Tab Ujian (definisi kertas ujian) --}}
<div class="card datatable-card-wrapper" id="tableDataContainer" data-tab="ujian">
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

                <a href="{{ route('dashboard.cbt.index', ['tab' => 'ujian']) }}" class="btn btn-outline"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                    title="Reset Filter" aria-label="Reset Filter">
                    <i class="fas fa-rotate-left"></i>
                </a>
            </div>

            <div class="live-search-wrap" style="height: 38px;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearch" name="q" placeholder="Cari ujian / mata pelajaran..." value="{{ $q ?? '' }}" autocomplete="off">
                <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    @php
        $tipeUjian = [
            'ulangan' => 'Ulangan',
            'uts' => 'UTS',
            'uas' => 'UAS',
            'latihan' => 'Latihan',
        ];
        $statusUjian = [
            'terbit' => ['label' => 'Terbit', 'color' => 'var(--success)', 'bg' => 'rgba(16, 185, 129, 0.15)'],
            'ditutup' => ['label' => 'Ditutup', 'color' => 'var(--text-muted)', 'bg' => 'rgba(148, 163, 184, 0.15)'],
            'draft' => ['label' => 'Draf', 'color' => 'var(--warning)', 'bg' => 'rgba(245, 158, 11, 0.15)'],
        ];
    @endphp

    <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color); text-align: left;">
                    <th data-no-sort style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">No</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Judul Ujian</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Mata Pelajaran</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 90px;">Tipe</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 80px;">Durasi</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 80px;">Soal</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 140px;">Periode</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">Token</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">Status</th>
                    <th data-no-sort style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 110px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $index => $item)
                    @php $st = $statusUjian[$item->status] ?? ['label' => $item->status, 'color' => 'var(--text-muted)', 'bg' => 'rgba(148,163,184,0.15)']; @endphp
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
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs); color: var(--text-muted);" data-label="Tipe">{{ $tipeUjian[$item->tipe] ?? $item->tipe }}</td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-sm); color: var(--text-main);" data-label="Durasi">{{ (int) $item->durasi_menit }}m</td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-sm); color: var(--text-main);" data-label="Soal">{{ (int) $item->jumlah_soal }}</td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs); color: var(--text-muted);" data-label="Periode">{{ $item->nama_periode ?: '—' }}</td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-sm);" data-label="Token">
                            @if (!empty($item->token))
                                <span class="copyable" data-copy="{{ $item->token }}" title="Klik untuk salin token" style="font-family: monospace; font-weight: 700; letter-spacing: 0.08em; color: var(--accent); cursor: pointer;">{{ $item->token }}</span>
                            @else
                                <span style="color: var(--text-muted);">—</span>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs);" data-label="Status">
                            <span class="badge" style="background: {{ $st['bg'] }}; color: {{ $st['color'] }}; padding: 3px 8px; border-radius: var(--radius-pill);">{{ $st['label'] }}</span>
                        </td>
                        <td style="padding: 12px 14px; text-align: right;" data-label="Aksi">
                            <div class="table-actions" style="justify-content: flex-end; gap: var(--space-4);">
                                @if ($canUpdate)
                                    <button type="button" class="btn-icon btn-edit-ujian" data-id="{{ $item->id }}" title="Ubah Ujian" aria-label="Ubah Ujian" style="color: var(--primary);">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" class="btn-icon btn-delete-ujian" data-id="{{ $item->id }}" data-name="{{ $item->judul }}" title="Hapus Ujian" aria-label="Hapus Ujian" style="color: var(--danger);">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align: center; padding: var(--space-12) var(--space-8); color: var(--text-muted); font-size: var(--text-sm);">
                            <i class="fas fa-file-pen" style="font-size: var(--text-2xl); opacity: 0.4; display: block; margin-bottom: var(--space-4);"></i>
                            <div>Belum ada ujian tersusun.</div>
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

{{-- Modal Form Ujian --}}
<div id="modalUjian" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="max-width: 720px; width: 92%; margin: 0; border-radius: var(--radius-xl); padding: var(--space-10); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); border: 1px solid var(--border-color); background: var(--bg-card); max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-6); border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-6);">
            <h3 id="ujianModalTitle" style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: var(--space-4);">
                <i class="fas fa-plus-circle" style="color: var(--primary);"></i> Form Ujian
            </h3>
            <button type="button" id="btnCloseUjianModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg); padding: 4px;" title="Tutup" aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formUjian" data-store="{{ route('dashboard.cbt.ujian.store') }}">
            @csrf

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Mata Pelajaran / Rombel <span style="color: var(--danger);">*</span></label>
                    <select id="ujianPembelajaran" name="pembelajaran_id" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                        <option value="">— Pilih pembelajaran —</option>
                        @foreach ($pembelajaranOptions as $opt)
                            <option value="{{ $opt->pembelajaran_id }}">{{ $opt->nama_rombel ? $opt->nama_rombel . ' — ' : '' }}{{ $opt->nama_mapel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Periode Asesmen</label>
                    <select id="ujianPeriode" name="periode_id" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                        <option value="">— Tanpa periode —</option>
                        @foreach ($periodeOptions as $po)
                            <option value="{{ $po->id }}">{{ $po->nama }}{{ $po->status === 'aktif' ? ' (Berjalan)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="margin-bottom: var(--space-6);">
                <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">
                    Judul Ujian <span style="color: var(--danger);">*</span>
                </label>
                <input type="text" id="ujianJudul" name="judul" required maxlength="200" placeholder="Judul ujian..."
                    style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Tipe</label>
                    <select id="ujianTipe" name="tipe" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                        <option value="ulangan">Ulangan</option>
                        <option value="uts">UTS</option>
                        <option value="uas">UAS</option>
                        <option value="latihan">Latihan</option>
                    </select>
                </div>
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Durasi (menit)</label>
                    <input type="number" id="ujianDurasi" name="durasi_menit" min="1" max="600" value="60"
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">KKM</label>
                    <input type="number" id="ujianKkm" name="kkm" min="0" max="100" value="75"
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Status</label>
                    <select id="ujianStatus" name="status" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                        <option value="draft">Draf</option>
                        <option value="terbit">Terbit</option>
                        <option value="ditutup">Ditutup</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: var(--space-4); align-items: flex-end; margin-bottom: var(--space-6); flex-wrap: wrap;">
                <div style="flex: 1; min-width: 180px;">
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Token Ujian</label>
                    <input type="text" id="ujianToken" name="token" maxlength="20" placeholder="Kosong = tanpa token"
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 700;">
                </div>
                <button type="button" id="btnTokenAcak" class="btn btn-outline" style="height: 38px; padding: 0 14px; font-size: var(--text-xs); white-space: nowrap;">
                    <i class="fas fa-shuffle" style="margin-right: 4px;"></i> Acak
                </button>
            </div>
            <div style="font-size: var(--text-2xs); color: var(--text-muted); margin: -12px 0 var(--space-6) 0;">
                <i class="fas fa-circle-info"></i> Peserta didik memasukkan token ini sebelum mengerjakan ujian.
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Jadwal Mulai</label>
                    <input type="datetime-local" id="ujianMulai" name="jadwal_mulai"
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Jadwal Selesai</label>
                    <input type="datetime-local" id="ujianSelesai" name="jadwal_selesai"
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                </div>
            </div>

            <div style="display: flex; gap: var(--space-8); margin-bottom: var(--space-6);">
                <label style="display: inline-flex; align-items: center; gap: var(--space-3); font-size: var(--text-sm); color: var(--text-main); cursor: pointer;">
                    <input type="checkbox" id="ujianAcakSoal" name="acak_soal" value="1"> Acak urutan soal
                </label>
                <label style="display: inline-flex; align-items: center; gap: var(--space-3); font-size: var(--text-sm); color: var(--text-main); cursor: pointer;">
                    <input type="checkbox" id="ujianAcakOpsi" name="acak_opsi" value="1"> Acak urutan opsi
                </label>
            </div>

            <div style="margin-bottom: var(--space-6);">
                <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Deskripsi / Instruksi</label>
                <textarea id="ujianDeskripsi" name="deskripsi" rows="2" maxlength="5000" placeholder="Instruksi ujian..."
                    style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box; resize: vertical;"></textarea>
            </div>

            <div style="margin-bottom: var(--space-6);">
                <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Pilih Soal dari Bank</label>
                <div id="soalPicker" style="max-height: 220px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: var(--space-4); display: flex; flex-direction: column; gap: var(--space-2);">
                    @forelse ($soalPicker as $sp)
                        <label class="pilih-soal-row" data-pembelajaran="{{ $sp->pembelajaran_id }}" style="display: flex; align-items: flex-start; gap: var(--space-3); font-size: var(--text-sm); color: var(--text-main); cursor: pointer; padding: 4px 6px; border-radius: var(--radius-sm);">
                            <input type="checkbox" class="soal-check" value="{{ $sp->id }}" style="margin-top: 3px;">
                            <span>{{ $sp->pertanyaan }}</span>
                        </label>
                    @empty
                        <div style="font-size: var(--text-xs); color: var(--text-muted);">Belum ada soal pada bank. Tambahkan soal dulu di tab Bank Soal.</div>
                    @endforelse
                </div>
                <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: 4px;">
                    Hanya soal dengan mata pelajaran yang sama yang akan tersimpan ke kertas ujian ini.
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: var(--space-4); border-top: 1px solid var(--border-color); padding-top: var(--space-6);">
                <button type="button" id="btnCancelUjianModal" class="btn btn-outline" style="padding: 8px 16px; font-size: var(--text-sm); border-radius: var(--radius-md);">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: var(--text-sm); border-radius: var(--radius-md); font-weight: 600;">
                    <i class="fas fa-check" style="margin-right: 6px;"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>
