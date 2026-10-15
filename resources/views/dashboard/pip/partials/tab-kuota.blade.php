<div class="card" style="padding: 16px; margin-bottom: 20px;">
    <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            <div style="font-size: 0.86rem; font-weight: 700; color: var(--text-color);">
                <i class="fas fa-calendar me-1"></i> Tahun Usulan: {{ $activeTahun }}
            </div>
            <button type="button" class="btn btn-outline btn-sm" onclick="pipSetKuotaSemua()"
                style="border-color: var(--primary); color: var(--primary); font-weight: 600;">
                <i class="fas fa-layer-group me-1"></i> Samakan Kuota Semua Kelas
            </button>
        </div>

        <div class="live-search-wrap">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchPip" name="q" placeholder="Cari nama kelas..."
                value="{{ $q }}" autocomplete="off">
            <button type="button" id="clearSearchPip" class="clear-search {{ !empty($q) ? 'visible' : '' }}"
                title="Hapus pencarian">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>

<div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
        <thead>
            <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                <th
                    style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); width: 50px; text-align: center;">
                    No</th>
                <th
                    style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                    Kelas</th>
                <th
                    style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                    Wali Kelas</th>
                <th
                    style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">
                    Terpakai</th>
                <th
                    style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">
                    Sisa</th>
                <th
                    style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center; width: 160px;">
                    Kuota</th>
                <th
                    style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center; width: 120px;">
                    Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($kuotaList as $i => $r)
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="text-align: center; color: var(--text-muted); font-size: 0.84rem;" data-label="No">
                        {{ $loop->iteration }}</td>
                    <td style="padding: 12px 14px;" data-label="Kelas">
                        <div style="font-weight: 700; font-size: 0.88rem;">{{ $r->nama }}</div>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">{{ $r->jurusan_id_str ?: '-' }}</div>
                    </td>
                    <td style="padding: 12px 14px; font-size: 0.84rem;" data-label="Wali Kelas">
                        {{ $r->ptk_id_str ?: '-' }}</td>
                    <td style="padding: 12px 14px; text-align: center;" data-label="Terpakai">
                        <span class="badge badge-primary">{{ $r->terpakai }}</span>
                    </td>
                    <td style="padding: 12px 14px; text-align: center;" data-label="Sisa">
                        <span
                            class="badge {{ $r->sisa > 0 ? 'badge-success' : 'badge-danger' }}">{{ $r->sisa }}</span>
                    </td>
                    <td style="padding: 12px 14px; text-align: center;" data-label="Kuota">
                        <input type="number" min="0" max="100" value="{{ $r->kuota }}"
                            class="form-control pip-kuota-input" data-rombel="{{ $r->rombongan_belajar_id }}"
                            style="width: 80px; height: 32px; padding: 0 8px; text-align: center; font-weight: 800; margin: 0 auto;">
                    </td>
                    <td style="padding: 12px 14px; text-align: center;" data-label="Aksi">
                        <div style="display: flex; gap: 6px; justify-content: center;">
                            <button type="button" class="btn btn-primary btn-sm"
                                onclick="pipSimpanKuota('{{ $r->rombongan_belajar_id }}', this)">
                                <i class="fas fa-save"></i>
                            </button>
                            <a href="{{ route('dashboard.pip.index', ['tab' => 'usulan', 'rombel_id' => $r->rombongan_belajar_id, 'tahun' => $activeTahun]) }}"
                                class="btn btn-outline btn-sm" title="Lihat usulan kelas ini">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="padding: 35px; text-align: center; color: var(--text-muted);">
                        Data rombongan belajar tidak ditemukan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
