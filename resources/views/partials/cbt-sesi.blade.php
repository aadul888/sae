{{-- Partial: CBT — Tab Sesi Ujian (jadwal & kuota pelaksanaan per periode) --}}
<div class="card datatable-card-wrapper" id="tableDataContainer" data-tab="sesi">
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
                <input type="text" id="liveSearch" name="q" placeholder="Cari periode / mapel / ruang..." value="{{ $q ?? '' }}" autocomplete="off">
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
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Periode</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Mata Pelajaran</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 150px;">Tanggal</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 130px;">Jam</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 70px;">Kuota</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">Ruang</th>
                    <th data-no-sort style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 110px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $index => $item)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs); color: var(--text-muted);" data-label="No">
                            {{ $list->firstItem() + $index }}
                        </td>
                        <td style="padding: 12px 14px; font-size: var(--text-sm); font-weight: 600; color: var(--text-main);" data-label="Periode">{{ $item->nama_periode ?: '—' }}</td>
                        <td style="padding: 12px 14px; font-size: var(--text-sm); color: var(--text-main);" data-label="Mata Pelajaran">
                            {{ $item->nama_mapel }}
                            <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ $item->nama_rombel ?? '-' }}</div>
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs); color: var(--text-muted);" data-label="Tanggal">
                            {{ $item->tanggal ? \Illuminate\Support\Carbon::parse($item->tanggal)->translatedFormat('d M Y') : '—' }}
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs); color: var(--text-main);" data-label="Jam">
                            @if ($item->jam_mulai || $item->jam_selesai)
                                {{ $item->jam_mulai ? substr($item->jam_mulai, 0, 5) : '…' }}–{{ $item->jam_selesai ? substr($item->jam_selesai, 0, 5) : '…' }}
                            @else
                                —
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-sm); color: var(--text-main);" data-label="Kuota">
                            {{ (int) $item->kuota > 0 ? (int) $item->kuota : '∞' }}
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs); color: var(--text-muted);" data-label="Ruang">{{ $item->ruang ?: '—' }}</td>
                        <td style="padding: 12px 14px; text-align: right;" data-label="Aksi">
                            <div class="table-actions" style="justify-content: flex-end; gap: var(--space-4);">
                                @if ($canUpdate)
                                    <button type="button" class="btn-icon btn-edit-sesi" data-id="{{ $item->id }}" title="Ubah Sesi" aria-label="Ubah Sesi" style="color: var(--primary);">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" class="btn-icon btn-delete-sesi" data-id="{{ $item->id }}" title="Hapus Sesi" aria-label="Hapus Sesi" style="color: var(--danger);">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: var(--space-12) var(--space-8); color: var(--text-muted); font-size: var(--text-sm);">
                            <i class="fas fa-clock" style="font-size: var(--text-2xl); opacity: 0.4; display: block; margin-bottom: var(--space-4);"></i>
                            <div>Belum ada sesi ujian. Tambahkan slot jadwal (mapel + tanggal + jam + kuota) ke sebuah periode.</div>
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

{{-- Modal Form Sesi Ujian --}}
<div id="modalSesi" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="max-width: 600px; width: 92%; margin: 0; border-radius: var(--radius-xl); padding: var(--space-10); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); border: 1px solid var(--border-color); background: var(--bg-card); max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-6); border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-6);">
            <h3 id="sesiModalTitle" style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: var(--space-4);">
                <i class="fas fa-plus-circle" style="color: var(--primary);"></i> Form Sesi Ujian
            </h3>
            <button type="button" id="btnCloseSesiModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg); padding: 4px;" title="Tutup" aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formSesi" data-store="{{ route('dashboard.cbt.sesi.store') }}">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Periode Asesmen <span style="color: var(--danger);">*</span></label>
                    <select id="sesiPeriode" name="periode_id" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                        <option value="">— Pilih periode —</option>
                        @foreach ($periodeOptions as $po)
                            <option value="{{ $po->id }}">{{ $po->nama }}{{ $po->status === 'aktif' ? ' (Berjalan)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Mata Pelajaran / Rombel <span style="color: var(--danger);">*</span></label>
                    <select id="sesiPembelajaran" name="pembelajaran_id" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                        <option value="">— Pilih pembelajaran —</option>
                        @foreach ($pembelajaranOptions as $opt)
                            <option value="{{ $opt->pembelajaran_id }}">{{ $opt->nama_rombel ? $opt->nama_rombel . ' — ' : '' }}{{ $opt->nama_mapel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Tanggal</label>
                    <input type="date" id="sesiTanggal" name="tanggal"
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Jam Mulai</label>
                    <input type="time" id="sesiJamMulai" name="jam_mulai"
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Jam Selesai</label>
                    <input type="time" id="sesiJamSelesai" name="jam_selesai"
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Kuota Peserta <span style="color: var(--text-muted); font-weight: 400;">(0 = tanpa batas)</span></label>
                    <input type="number" id="sesiKuota" name="kuota" min="0" max="2000" value="0"
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Ruang</label>
                    <input type="text" id="sesiRuang" name="ruang" maxlength="100" placeholder="Contoh: Lab Komputer 1"
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                </div>
            </div>

            <div style="margin-bottom: var(--space-6);">
                <label style="font-size: var(--text-xs); font-weight: 600; color: var(--text-main); display: block; margin-bottom: 4px;">Catatan</label>
                <textarea id="sesiCatatan" name="catatan" rows="2" maxlength="500" placeholder="Catatan sesi..."
                    style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box; resize: vertical;"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: var(--space-4); border-top: 1px solid var(--border-color); padding-top: var(--space-6);">
                <button type="button" id="btnCancelSesiModal" class="btn btn-outline" style="padding: 8px 16px; font-size: var(--text-sm); border-radius: var(--radius-md);">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: var(--text-sm); border-radius: var(--radius-md); font-weight: 600;">
                    <i class="fas fa-check" style="margin-right: 6px;"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>
