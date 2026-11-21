{{-- Partial: CBT — Tab Periode Asesmen (kontrol asesmen yang dijalankan admin) --}}
<div class="card datatable-card-wrapper" id="tableDataContainer" data-tab="periode">
    <div class="datatable-toolbar">
        <div style="display: flex; flex-wrap: wrap; gap: var(--space-6); justify-content: space-between; align-items: center;">
            <div class="toolbar-entries">
                <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                <select id="perPageSelect" class="per-page-select" style="height: 38px;">
                    @foreach ([10, 15, 25, 50, 100] as $n)
                        <option value="{{ $n }}" {{ ($perPage ?? 25) == $n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
                <span>entri</span>
            </div>
            <div class="live-search-wrap" style="height: 38px;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearch" name="q" placeholder="Cari periode asesmen..." value="{{ $q ?? '' }}" autocomplete="off">
                <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    @php
        $tipeLabel = \App\Models\CbtPeriodeAsesmen::TIPE_LABEL;
        $statusPeriode = [
            'aktif' => ['label' => 'Berjalan', 'color' => 'var(--success)', 'bg' => 'rgba(16, 185, 129, 0.15)'],
            'selesai' => ['label' => 'Selesai', 'color' => 'var(--text-muted)', 'bg' => 'rgba(148, 163, 184, 0.15)'],
            'draft' => ['label' => 'Draf', 'color' => 'var(--warning)', 'bg' => 'rgba(245, 158, 11, 0.15)'],
        ];
    @endphp

    <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color); text-align: left;">
                    <th data-no-sort style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">No</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Periode</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">Tipe</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 150px;">Tahun / Semester</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 190px;">Rentang</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 70px;">Ujian</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">Status</th>
                    <th data-no-sort style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 110px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $index => $item)
                    @php $st = $statusPeriode[$item->status] ?? ['label' => $item->status, 'color' => 'var(--text-muted)', 'bg' => 'rgba(148,163,184,0.15)']; @endphp
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs); color: var(--text-muted);" data-label="No">
                            {{ $list->firstItem() + $index }}
                        </td>
                        <td style="padding: 12px 14px; font-size: var(--text-sm); font-weight: 600; color: var(--text-main);" data-label="Nama">
                            {{ $item->nama }}
                            @if ($item->keterangan)
                                <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: 2px;"><span class="sae-truncate" style="max-width: 320px; display: inline-block;" title="{{ $item->keterangan }}">{{ $item->keterangan }}</span></div>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs); color: var(--text-main);" data-label="Tipe">{{ $tipeLabel[$item->tipe] ?? $item->tipe }}</td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs); color: var(--text-muted);" data-label="Tahun/Semester">
                            {{ $item->tahun_ajaran ?: '—' }}{{ $item->semester ? ' · '.ucfirst($item->semester) : '' }}
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs); color: var(--text-muted);" data-label="Rentang">
                            @if ($item->tanggal_mulai || $item->tanggal_selesai)
                                {{ $item->tanggal_mulai ? $item->tanggal_mulai->format('d M Y') : '…' }} — {{ $item->tanggal_selesai ? $item->tanggal_selesai->format('d M Y') : '…' }}
                            @else
                                —
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-sm); color: var(--text-main);" data-label="Ujian">{{ (int) $item->jumlah_ujian }}</td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs);" data-label="Status">
                            <span class="badge" style="background: {{ $st['bg'] }}; color: {{ $st['color'] }}; padding: 3px 8px; border-radius: var(--radius-pill);">{{ $st['label'] }}</span>
                        </td>
                        <td style="padding: 12px 14px; text-align: right;" data-label="Aksi">
                            <div class="table-actions" style="justify-content: flex-end; gap: var(--space-4);">
                                @if ($canUpdate)
                                    <button type="button" class="btn-icon btn-edit-periode" data-id="{{ $item->id }}" title="Ubah Periode" aria-label="Ubah Periode" style="color: var(--primary);">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" class="btn-icon btn-delete-periode" data-id="{{ $item->id }}" data-name="{{ $item->nama }}" title="Hapus Periode" aria-label="Hapus Periode" style="color: var(--danger);">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: var(--space-12) var(--space-8); color: var(--text-muted); font-size: var(--text-sm);">
                            <i class="fas fa-calendar-check" style="font-size: var(--text-2xl); opacity: 0.4; display: block; margin-bottom: var(--space-4);"></i>
                            <div>Belum ada periode asesmen. Tambahkan periode (mis. PTS Ganjil) lalu tautkan ujian.</div>
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

{{-- Modal Form Periode Asesmen --}}
<div id="modalPeriode" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="max-width: 560px; width: 92%; margin: 0; border-radius: var(--radius-xl); padding: var(--space-10); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); border: 1px solid var(--border-color); background: var(--bg-card); max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-6); border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-6);">
            <h3 id="periodeModalTitle" style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: var(--space-4);">
                <i class="fas fa-plus-circle" style="color: var(--primary);"></i> Form Periode Asesmen
            </h3>
            <button type="button" id="btnClosePeriodeModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg); padding: 4px;" title="Tutup" aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formPeriode" data-store="{{ route('dashboard.cbt.periode.store') }}">
            @csrf
            <div style="margin-bottom: var(--space-6);">
                <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Nama Periode <span style="color: var(--danger);">*</span></label>
                <input type="text" id="periodeNama" name="nama" required maxlength="150" placeholder="Contoh: PTS Ganjil 2026/2027"
                    style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Tipe Asesmen</label>
                    <select id="periodeTipe" name="tipe" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                        @foreach (\App\Models\CbtPeriodeAsesmen::TIPE_LABEL as $val => $lbl)
                            <option value="{{ $val }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Status</label>
                    <select id="periodeStatus" name="status" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                        <option value="draft">Draf</option>
                        <option value="aktif">Berjalan (Aktif)</option>
                        <option value="selesai">Selesai</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Tahun Ajaran</label>
                    <input type="text" id="periodeTahun" name="tahun_ajaran" maxlength="20" placeholder="2026/2027"
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Semester</label>
                    <select id="periodeSemester" name="semester" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                        <option value="">—</option>
                        <option value="ganjil">Ganjil</option>
                        <option value="genap">Genap</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Tanggal Mulai</label>
                    <input type="date" id="periodeMulai" name="tanggal_mulai"
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Tanggal Selesai</label>
                    <input type="date" id="periodeSelesai" name="tanggal_selesai"
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                </div>
            </div>

            <div style="margin-bottom: var(--space-6);">
                <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Keterangan</label>
                <textarea id="periodeKeterangan" name="keterangan" rows="2" maxlength="5000" placeholder="Catatan periode asesmen..."
                    style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box; resize: vertical;"></textarea>
            </div>

            <div style="font-size: var(--text-xs); color: var(--text-muted); margin-bottom: var(--space-6);">
                <i class="fas fa-circle-info"></i> Hanya satu periode berstatus <strong>Berjalan</strong> pada satu waktu; periode aktif lain otomatis menjadi Selesai.
            </div>

            <div style="display: flex; justify-content: flex-end; gap: var(--space-4); border-top: 1px solid var(--border-color); padding-top: var(--space-6);">
                <button type="button" id="btnCancelPeriodeModal" class="btn btn-outline" style="padding: 8px 16px; font-size: var(--text-sm); border-radius: var(--radius-md);">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: var(--text-sm); border-radius: var(--radius-md); font-weight: 600;">
                    <i class="fas fa-check" style="margin-right: 6px;"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>
