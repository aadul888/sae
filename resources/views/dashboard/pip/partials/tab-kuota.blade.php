@php
    $selisih = $kuotaStats['selisih'] ?? 0;
    $isKelebihan = $selisih > 0;
    $isKurang = $selisih < 0;
    $selisihColor = $isKelebihan ? '#ef4444' : ($isKurang ? '#f59e0b' : '#10b981');
    $selisihBg = $isKelebihan
        ? 'rgba(239,68,68,0.12)'
        : ($isKurang
            ? 'rgba(245,158,11,0.12)'
            : 'rgba(16,185,129,0.12)');
    $selisihIcon = $isKelebihan ? 'fa-circle-exclamation' : ($isKurang ? 'fa-triangle-exclamation' : 'fa-circle-check');
    $selisihTitle = $isKelebihan ? 'Kelebihan +' . $selisih : ($isKurang ? 'Kurang ' . abs($selisih) : 'Pas (100%)');
@endphp

<!-- 1. Stat Grid Baku SAE (6 Kartu Ringkasan Kuota - Jumlah Genap Rapi) -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <!-- Card 1: Target Kuota Sekolah -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
            <i class="fas fa-bullseye"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: var(--primary);">
                {{ number_format($kuotaStats['target_total'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Target Pagu Sekolah</div>
        </div>
    </div>

    <!-- Card 2: Total Kuota Teralokasi -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(37,99,235,0.12); color: #2563eb;">
            <i class="fas fa-layer-group"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: #2563eb;">
                {{ number_format($kuotaStats['total_teralokasi'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Total Kuota Teralokasi</div>
        </div>
    </div>

    <!-- Card 3: Status Kelebihan / Kurang Kuota (Highlight Visual) -->
    <div class="dash-stat-card"
        style="{{ $isKelebihan ? 'outline: 2px solid #ef4444;' : ($isKurang ? 'outline: 2px solid #f59e0b;' : '') }}">
        <div class="dash-stat-icon" style="background: {{ $selisihBg }}; color: {{ $selisihColor }};">
            <i class="fas {{ $selisihIcon }}"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: {{ $selisihColor }};">
                {{ $selisihTitle }}
            </div>
            <div class="dash-stat-label">
                @if ($isKelebihan)
                    Kelebihan dari Pagu
                @elseif ($isKurang)
                    Sisa Belum Dialokasikan
                @else
                    Alokasi Pas Sesuai Target
                @endif
            </div>
        </div>
    </div>

    <!-- Card 4: Total Usulan Terpakai -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
            <i class="fas fa-user-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: #10b981;">
                {{ number_format($kuotaStats['total_terpakai'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Total Usulan Masuk</div>
        </div>
    </div>

    <!-- Card 5: Kelas Kuota Terpenuhi -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: #ef4444;">
            <i class="fas fa-circle-exclamation"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: #ef4444;">
                {{ number_format($kuotaStats['rombel_penuh'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Kelas Kuota Terpenuhi</div>
        </div>
    </div>

    <!-- Card 6: Kelas Masih Ada Kuota -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
            <i class="fas fa-door-open"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: #f59e0b;">
                {{ number_format($kuotaStats['rombel_tersedia'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Kelas Masih Ada Kuota</div>
        </div>
    </div>
</div>

<!-- 2. Toolbar Standar Datatable Global SAE (Entri, Aksi Total, Search) -->
<div class="card" style="padding: 16px; margin-bottom: 20px;">
    <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            <div class="toolbar-entries">
                <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                <select id="perPageSelect" name="perPage" class="per-page-select">
                    @foreach ([10, 15, 25, 50, 100] as $n)
                        <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>
                            {{ $n }}
                        </option>
                    @endforeach
                </select>
                <span>entri</span>
            </div>

            <!-- Tombol Penentuan Kuota Total Sekolah -->
            <button type="button" class="btn btn-primary btn-sm" onclick="pipSetTotalKuota()"
                style="font-weight: 700; display: inline-flex; align-items: center; gap: 6px;"
                title="Tentukan dan bagikan total pagu kuota sekolah">
                <i class="fas fa-calculator"></i> Total Pagu
            </button>

            <!-- Tombol Samakan Kuota Rata per Kelas -->
            <button type="button" class="btn btn-outline btn-sm" onclick="pipSetKuotaSemua()"
                style="border-color: var(--primary); color: var(--primary); font-weight: 600;"
                title="Samakan alokasi kuota setiap kelas">
                <i class="fas fa-layer-group me-1"></i> Rata Kelas
            </button>

            @if ($q)
                <a href="{{ route('dashboard.pip.index', ['tab' => 'kuota', 'tahun' => $activeTahun]) }}"
                    class="btn btn-outline" style="padding: 7px 12px; font-size: 0.84rem;" title="Reset filter">
                    <i class="fas fa-undo"></i>
                </a>
            @endif
        </div>

        <div class="live-search-wrap">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchPip" name="q" placeholder="Cari nama kelas / jurusan..."
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
                        {{ $kuotaList->firstItem() + $loop->index }}</td>
                    <td style="padding: 12px 14px;" data-label="Kelas">
                        <div style="font-weight: 700; font-size: 0.88rem;">{{ $r->nama }}</div>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">{{ $r->jurusan_id_str ?: '-' }}
                        </div>
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
                        <input type="number" min="0" max="1000" value="{{ $r->kuota }}"
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

<!-- 4. Pagination Global Datatable -->
@include('dashboard.pip.partials.pagination', ['paginator' => $kuotaList])
