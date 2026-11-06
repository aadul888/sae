<!-- Unified Datatable Kuota PIP (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
<div class="card datatable-card-wrapper" id="tableDataContainer">
    <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
    <div class="datatable-toolbar">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <div class="toolbar-entries">
                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                    <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                        @foreach ([10, 15, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>
                                {{ $n }}
                            </option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </div>

                <!-- 1 Tombol Simpan Tunggal untuk Seluruh Kuota Kelas -->
                <button type="button" class="btn btn-primary btn-sm" id="btnSimpanSemuaKuota"
                    onclick="pipSimpanSemuaKuota()"
                    style="height: 38px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px);"
                    title="Simpan seluruh perubahan angka kuota kelas di halaman ini">
                    <i class="fas fa-save"></i> Simpan Kuota
                </button>

                <!-- Tombol Penentuan Kuota Berdasarkan Persentase Jumlah Siswa (Proporsional & Adil) -->
                <button type="button" class="btn btn-outline btn-sm" onclick="pipSetTotalKuotaProporsional()"
                    style="height: 38px; border-color: var(--primary); color: var(--primary); font-weight: 600; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px);"
                    title="Tentukan total pagu dan bagikan secara proporsional sesuai persentase jumlah siswa kelas">
                    <i class="fas fa-chart-pie"></i> Hitung Proporsional
                </button>

                <!-- Tombol Samakan Kuota Rata per Kelas -->
                <button type="button" class="btn btn-outline btn-sm" onclick="pipSetKuotaSemua()"
                    style="height: 38px; border-color: var(--border-color); color: var(--text-color); font-weight: 600; border-radius: var(--radius-md, 8px);"
                    title="Samakan alokasi kuota setiap kelas">
                    <i class="fas fa-layer-group me-1"></i> Bagi Rata
                </button>

                @if ($q)
                    <a href="{{ route('dashboard.pip.index', ['tab' => 'kuota', 'tahun' => $activeTahun]) }}"
                        class="btn btn-outline btn-reset-filter"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                        title="Reset filter" data-live-reset>
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>

            <div class="live-search-wrap" style="height: 38px;">
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

    <!-- 2. Konten Datatable Responsive Stack -->
    <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                    <th
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); width: 50px; text-align: center;">
                        No</th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted);">
                        Kelas</th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted);">
                        Wali Kelas</th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); text-align: center;">
                        Siswa (% Porsi)</th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); text-align: center;">
                        Terpakai</th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); text-align: center;">
                        Sisa</th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); text-align: center; width: 130px;">
                        Kuota</th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); text-align: center; width: 70px;">
                        Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($kuotaList as $i => $r)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="text-align: center; color: var(--text-muted); font-size: var(--text-base, 0.875rem);" data-label="No">
                            {{ $kuotaList->firstItem() + $loop->index }}</td>
                        <td style="padding: 12px 14px;" data-label="Kelas">
                            <div style="font-weight: 700; font-size: var(--text-base, 0.875rem);">{{ $r->nama }}</div>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">{{ $r->jurusan_id_str ?: '-' }}
                            </div>
                        </td>
                        <td style="padding: 12px 14px; font-size: var(--text-base, 0.875rem);" data-label="Wali Kelas">
                            {{ $r->ptk_id_str ?: '-' }}</td>
                        <td style="padding: 12px 14px; text-align: center;" data-label="Siswa (% Porsi)">
                            <span style="font-weight: 700; font-size: var(--text-base, 0.875rem);">{{ $r->jumlah_siswa ?? 0 }}</span>
                            <small
                                style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">({{ $r->persen_siswa ?? 0 }}%)</small>
                        </td>
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
                                style="width: 76px; height: 32px; padding: 0 6px; text-align: center; font-weight: 800; margin: 0 auto; border-radius: var(--radius-sm, 6px);">
                        </td>
                        <td style="padding: 12px 14px; text-align: center;" data-label="Aksi">
                            <a href="{{ route('dashboard.pip.index', ['tab' => 'usulan', 'rombel_id' => $r->rombongan_belajar_id, 'tahun' => $activeTahun]) }}"
                                class="btn btn-outline btn-sm btn-icon" style="width: 32px; height: 32px;"
                                title="Lihat Usulan Kelas">
                                <i class="fas fa-eye"></i>
                            </a>
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

    <!-- 3. Footer Paginasi Terpadu Datatable -->
    @if ($kuotaList->hasPages())
        <div class="datatable-footer">
            @include('partials.datatable-pagination', ['paginator' => $kuotaList])
        </div>
    @endif
</div>
