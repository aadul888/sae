        <!-- TAB 1: Catatan Keuangan, Pengeluaran Bahan/Alat, Chart & Laporan -->
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color);">
                    Arus Kas &amp; Pembukuan Operasional Kartu Pelajar
                </h3>
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                    Monitoring uang masuk pembayaran siswa, pencatatan belanja alat &amp; bahan cetak, serta analisa saldo
                    kas.
                </div>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" class="btn btn-primary" onclick="openTambahPengeluaran()"
                    style="padding: 8px 16px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-plus-circle"></i> Catat Pengeluaran
                </button>
                <a href="{{ route('dashboard.kartu-pelajar.cetak-keuangan') }}" target="_blank" class="btn btn-outline"
                    style="padding: 8px 16px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); font-weight: 700; display: inline-flex; align-items: center; gap: 6px; border-color: var(--primary); color: var(--primary);">
                    <i class="fas fa-print"></i> Cetak Laporan Keuangan
                </a>
            </div>
        </div>

        <!-- 4 Card Ringkasan Keuangan (Jumlah Genap: 4 Kartu) -->
        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 24px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                    <i class="fas fa-arrow-down-long"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #10b981;">
                        Rp {{ number_format($keuangan['total_pemasukan'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Total Uang Masuk (Siswa Lunas)</div>
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                        {{ number_format($keuangan['total_transaksi_masuk'], 0, ',', '.') }} pembayaran terverifikasi
                    </div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: #ef4444;">
                    <i class="fas fa-arrow-up-long"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #ef4444;">
                        Rp {{ number_format($keuangan['total_pengeluaran'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Total Pengeluaran Bahan/Alat</div>
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                        {{ number_format($keuangan['total_transaksi_keluar'], 0, ',', '.') }} catatan pengadaan logistik
                    </div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--primary);">
                        Rp {{ number_format($keuangan['saldo_bersih'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Sisa Saldo Kas Bersih</div>
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                        {{ $keuangan['saldo_bersih'] >= 0 ? 'Surplus / Kas Tersedia' : 'Defisit Anggaran' }}
                    </div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                    <i class="fas fa-clock-rotate-left"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #f59e0b;">
                        Rp {{ number_format($keuangan['estimasi_piutang'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Estimasi Piutang Belum Bayar</div>
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                        Dari {{ number_format($keuangan['siswa_belum_bayar'], 0, ',', '.') }} siswa belum bayar
                    </div>
                </div>
            </div>
        </div>

        <!-- Payload Data Chart Keuangan -->
        <div id="kartuPelajarChartPayload" style="display: none;" data-chart='@json($chartKeuangan)'></div>

        <!-- 2 Visual Charts Interaktif -->
        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px; margin-bottom: 24px;">
            <!-- Chart 1: Arus Kas 6 Bulan Terakhir -->
            <div class="card" style="padding: 20px;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                    <h4
                        style="margin: 0; font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-chart-column text-primary"></i> Tren Arus Kas 6 Bulan Terakhir
                    </h4>
                    <span class="badge badge-outline" style="font-size: var(--text-xs, 0.75rem);">Pemasukan vs Pengeluaran</span>
                </div>
                <div style="position: relative; height: 260px;">
                    <canvas id="chartArusKasBar"></canvas>
                </div>
            </div>

            <!-- Chart 2: Komposisi Pengeluaran per Kategori -->
            <div class="card" style="padding: 20px;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                    <h4
                        style="margin: 0; font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-chart-pie text-warning"></i> Alokasi Pengeluaran per Kategori
                    </h4>
                    <span class="badge badge-outline" style="font-size: var(--text-xs, 0.75rem);">Distribusi Biaya</span>
                </div>
                <div
                    style="position: relative; height: 260px; display: flex; align-items: center; justify-content: center;">
                    <canvas id="chartKategoriDoughnut"></canvas>
                </div>
            </div>
        </div>

        <!-- Datatable Keuangan Terpadu: 1 Kolom Vertikal (Tabel Pembayaran Dulu, Baru Catatan Belanja) -->
        <div id="tableDataContainer" style="display: flex; flex-direction: column; gap: 24px; margin-bottom: 24px;">
            <!-- 1. Datatable Penerimaan Pembayaran Siswa (Uang Masuk) -->
            <div class="card datatable-card-wrapper" style="margin-bottom: 0;">
                <div
                    style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h4
                            style="margin: 0; font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-money-check-dollar text-success"></i> Penerimaan Pembayaran Siswa (Uang Masuk)
                        </h4>
                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                            Daftar riwayat pembayaran kartu pelajar lunas dari peserta didik.
                        </div>
                    </div>
                    <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 5px 12px; font-weight: 700;">
                        <i class="fas fa-circle-check me-1"></i>
                        {{ number_format($pemasukanList ? $pemasukanList->total() : ($recentPemasukan ? $recentPemasukan->count() : 0), 0, ',', '.') }}
                        Pembayaran Lunas
                    </span>
                </div>

                <!-- Datatable Toolbar Pembayaran: Entries, Filter Rombel & Live Search -->
                <div class="datatable-toolbar">
                    <div
                        style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
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

                            <select id="filterRombel" name="rombel" class="toolbar-filter-select"
                                style="height: 38px;">
                                <option value="">Semua Rombel</option>
                                @foreach ($rombelList as $r)
                                    <option value="{{ $r->nama }}" {{ $rombel === $r->nama ? 'selected' : '' }}>
                                        {{ $r->nama }}
                                    </option>
                                @endforeach
                            </select>

                            @if ($q || $rombel)
                                <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'keuangan']) }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter" data-live-reset>
                                    <i class="fas fa-undo"></i>
                                </a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="searchKartuPelajar" name="q"
                                placeholder="Cari siswa / NISN / rombel / metode..." value="{{ $q }}"
                                autocomplete="off">
                            <button type="button" id="clearSearchKartuPelajar"
                                class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                                <th
                                    style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">
                                    No</th>
                                <th class="sortable-th {{ $sort === 'nama' ? 'sorted' : '' }}" data-sort="nama"
                                    style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Peserta Didik
                                    <span class="sort-icon">{!! $sort === 'nama' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                                <th class="sortable-th {{ $sort === 'nama_rombel' ? 'sorted' : '' }}"
                                    data-sort="nama_rombel"
                                    style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Rombel / Kelas
                                    <span class="sort-icon">{!! $sort === 'nama_rombel' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                                <th class="sortable-th {{ $sort === 'tanggal_bayar' ? 'sorted' : '' }}"
                                    data-sort="tanggal_bayar"
                                    style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Tanggal Bayar
                                    <span class="sort-icon">{!! $sort === 'tanggal_bayar' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                                <th
                                    style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Metode</th>
                                <th class="sortable-th {{ $sort === 'nominal' ? 'sorted' : '' }}" data-sort="nominal"
                                    style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                    Nominal
                                    <span class="sort-icon">{!! $sort === 'nominal' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                                <th
                                    style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">
                                    Status</th>
                                <th
                                    style="padding: 12px 12px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 85px;">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $bayarRows = $pemasukanList ?? $recentPemasukan;
                            @endphp
                            @if ($bayarRows && $bayarRows->count() > 0)
                                @foreach ($bayarRows as $rp)
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td
                                            style="padding: 12px 14px; text-align: center; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                            {{ $loop->iteration + ($pemasukanList && method_exists($pemasukanList, 'currentPage') ? ($pemasukanList->currentPage() - 1) * $pemasukanList->perPage() : 0) }}
                                        </td>
                                        <td style="padding: 12px 16px;">
                                            <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color);">
                                                {{ $rp->nama }}
                                            </div>
                                            <div
                                                style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-family: monospace;">
                                                NISN: {{ $rp->nisn ?: '-' }}
                                            </div>
                                        </td>
                                        <td style="padding: 12px 14px;">
                                            <span class="badge badge-outline"
                                                style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">
                                                {{ $rp->nama_rombel ?: '-' }}
                                            </span>
                                        </td>
                                        <td
                                            style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); white-space: nowrap;">
                                            {{ $rp->tanggal_bayar ? \Carbon\Carbon::parse($rp->tanggal_bayar)->translatedFormat('d M Y') : '-' }}
                                        </td>
                                        <td style="padding: 12px 14px;">
                                            <span class="badge badge-outline"
                                                style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">
                                                {{ strtoupper($rp->metode_pembayaran ?: 'Tunai') }}
                                            </span>
                                        </td>
                                        <td
                                            style="padding: 12px 14px; text-align: right; font-weight: 700; color: #10b981; font-size: var(--text-base, 0.875rem); white-space: nowrap;">
                                            Rp {{ number_format($rp->nominal, 0, ',', '.') }}
                                        </td>
                                        <td style="padding: 12px 14px; text-align: center;">
                                            <span class="badge badge-success"
                                                style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                                <i class="fas fa-check me-1"></i> Lunas
                                            </span>
                                        </td>
                                        <td style="padding: 12px 12px; text-align: center;">
                                            <div
                                                style="display: flex; gap: 4px; justify-content: center; align-items: center;">
                                                @if (!empty($rp->nisn))
                                                    <button type="button" class="btn-icon" style="color: #0284c7;"
                                                        title="Pratinjau / Cetak Kartu Digital"
                                                        onclick="openKartuPelajarModal('{{ $rp->nisn }}')">
                                                        <i class="fas fa-address-card"></i>
                                                    </button>
                                                @endif
                                                <button type="button" class="btn-icon" style="color: #25d366;"
                                                    title="Kirim Pesan WhatsApp"
                                                    onclick="openModalWa({{ json_encode($rp) }})">
                                                    <i class="fab fa-whatsapp"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="8"
                                        style="padding: 35px; text-align: center; color: var(--text-muted);">
                                        <i class="fas fa-money-check-dollar mb-2"
                                            style="font-size: var(--text-3xl, 1.875rem); opacity: 0.4; display: block;"></i>
                                        Belum ada data penerimaan pembayaran siswa.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                @if (!empty($pemasukanList) && method_exists($pemasukanList, 'hasPages') && $pemasukanList->hasPages())
                    <div class="datatable-footer">
                        @include('partials.datatable-pagination', ['paginator' => $pemasukanList])
                    </div>
                @endif
            </div>

            <!-- 2. Datatable Catatan Belanja Bahan & Alat (Pengeluaran) -->
            <div class="card datatable-card-wrapper" style="margin-bottom: 0;">
                <div
                    style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h4
                            style="margin: 0; font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-receipt text-danger"></i> Catatan Belanja Bahan &amp; Alat
                        </h4>
                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                            Pencatatan pengadaan bahan PVC, ribbon tinta, stiker, dan logistik cetak kartu pelajar.
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                        <span class="badge badge-outline" style="font-size: var(--text-xs, 0.75rem); padding: 5px 12px;">
                            {{ number_format($pengeluaranList ? $pengeluaranList->total() : 0, 0, ',', '.') }} Catatan
                            Pengadaan
                        </span>
                        <button type="button" class="btn btn-primary btn-sm" onclick="openTambahPengeluaran()"
                            style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px;">
                            <i class="fas fa-plus-circle"></i> Catat Pengeluaran
                        </button>
                    </div>
                </div>

                <!-- Toolbar Datatable Belanja: state terpisah (belanja_*), dikelola kartu-pelajar-manage.js -->
                <div class="datatable-toolbar" data-manual-filter>
                    <div
                        style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                            <div class="toolbar-entries">
                                <label for="belanjaPerPage" style="margin: 0;">Tampilkan</label>
                                <select id="belanjaPerPage" name="belanja_per_page" class="per-page-select"
                                    style="height: 38px;">
                                    @foreach ([10, 15, 25, 50, 100] as $n)
                                        <option value="{{ $n }}" {{ $bPerPage == $n ? 'selected' : '' }}>
                                            {{ $n }}</option>
                                    @endforeach
                                </select>
                                <span>entri</span>
                            </div>

                            @if ($bQ || $bSort)
                                <a href="{{ request()->fullUrlWithoutQuery(['belanja_q', 'belanja_per_page', 'belanja_sort', 'belanja_dir', 'belanja_page']) }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter" data-live-reset>
                                    <i class="fas fa-undo"></i>
                                </a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="belanjaSearch" name="belanja_q" data-manual-search
                                placeholder="Cari uraian / kategori / toko..." value="{{ $bQ }}"
                                autocomplete="off">
                            <button type="button" class="clear-search {{ !empty($bQ) ? 'visible' : '' }}"
                                title="Hapus pencarian">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                                <th
                                    style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">
                                    No</th>
                                <th class="sortable-th {{ $bSort === 'judul' ? 'sorted' : '' }}"
                                    data-belanja-sort="judul"
                                    style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Uraian
                                    <span class="sort-icon">{!! $bSort === 'judul' ? ($bDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                                <th class="sortable-th {{ $bSort === 'kategori' ? 'sorted' : '' }}"
                                    data-belanja-sort="kategori"
                                    style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Kategori
                                    <span class="sort-icon">{!! $bSort === 'kategori' ? ($bDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                                <th class="sortable-th {{ $bSort === 'tanggal' ? 'sorted' : '' }}"
                                    data-belanja-sort="tanggal"
                                    style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Tanggal
                                    <span class="sort-icon">{!! $bSort === 'tanggal' ? ($bDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                                <th
                                    style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                    Qty</th>
                                <th
                                    style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                    Harga Satuan</th>
                                <th class="sortable-th {{ $bSort === 'nominal' ? 'sorted' : '' }}"
                                    data-belanja-sort="nominal"
                                    style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                    Total Pengeluaran
                                    <span class="sort-icon">{!! $bSort === 'nominal' ? ($bDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                                <th
                                    style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Keterangan / Toko</th>
                                <th
                                    style="padding: 12px 12px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($pengeluaranList && $pengeluaranList->count() > 0)
                                @foreach ($pengeluaranList as $p)
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td data-label="No"
                                            style="padding: 12px 14px; text-align: center; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                            {{ $loop->iteration + ($pengeluaranList && method_exists($pengeluaranList, 'currentPage') ? ($pengeluaranList->currentPage() - 1) * $pengeluaranList->perPage() : 0) }}
                                        </td>
                                        <td data-label="Judul" style="padding: 12px 16px;">
                                            <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color);">
                                                {{ $p->judul }}</div>
                                            @if ($p->petugas)
                                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                                    Oleh: {{ $p->petugas }}</div>
                                            @endif
                                        </td>
                                        <td data-label="Kategori" style="padding: 12px 14px;">
                                            <span class="badge badge-outline"
                                                style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">
                                                {{ $kategoriList[$p->kategori] ?? ucfirst($p->kategori) }}
                                            </span>
                                        </td>
                                        <td data-label="Tanggal"
                                            style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); white-space: nowrap;">
                                            {{ \Carbon\Carbon::parse($p->tanggal)->translatedFormat('d M Y') }}
                                        </td>
                                        <td data-label="Qty" style="padding: 12px 14px; text-align: center;">
                                            <span class="badge badge-outline"
                                                style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px; border-color: rgba(99,102,241,0.3); color: var(--primary);">
                                                <i class="fas fa-boxes-stacked me-1"></i>
                                                {{ number_format($p->jumlah ?: 1, 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td data-label="Harga Satuan"
                                            style="padding: 12px 14px; text-align: right; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); white-space: nowrap;">
                                            Rp
                                            {{ number_format($p->harga_satuan ?: ($p->jumlah > 1 ? round($p->nominal / $p->jumlah) : $p->nominal), 0, ',', '.') }}
                                        </td>
                                        <td data-label="Total"
                                            style="padding: 12px 14px; text-align: right; font-weight: 700; color: #ef4444; font-size: var(--text-base, 0.875rem); white-space: nowrap;">
                                            Rp {{ number_format($p->nominal, 0, ',', '.') }}
                                        </td>
                                        <td data-label="Keterangan"
                                            style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                            {{ $p->keterangan ?: '-' }}
                                        </td>
                                        <td data-label="Aksi" style="padding: 12px 12px; text-align: center;">
                                            <div
                                                style="display: flex; gap: 4px; justify-content: center; align-items: center;">
                                                @if ($p->bukti_nota_path)
                                                    <a href="{{ asset('storage/' . ltrim($p->bukti_nota_path, '/')) }}"
                                                        target="_blank" class="btn-icon" style="color: #3b82f6;"
                                                        title="Lihat Bukti Nota">
                                                        <i class="fas fa-file-invoice"></i>
                                                    </a>
                                                @endif
                                                <button type="button" class="btn-icon" style="color: #4f46e5;"
                                                    title="Edit Catatan Pengeluaran"
                                                    onclick="openEditPengeluaran({{ json_encode($p) }})">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form
                                                    action="{{ route('dashboard.kartu-pelajar.destroy-pengeluaran', $p->id) }}"
                                                    method="POST" class="delete-pengeluaran-form" style="margin: 0;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-icon" style="color: #ef4444;"
                                                        title="Hapus Pengeluaran">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9"
                                        style="padding: 35px; text-align: center; color: var(--text-muted);">
                                        <i class="fas fa-receipt mb-2"
                                            style="font-size: var(--text-3xl, 1.875rem); opacity: 0.4; display: block;"></i>
                                        <div>Belum ada catatan pengeluaran alat / bahan kartu pelajar.</div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                @if ($pengeluaranList && method_exists($pengeluaranList, 'hasPages') && $pengeluaranList->hasPages())
                    <div class="datatable-footer">
                        @include('partials.datatable-pagination', ['paginator' => $pengeluaranList])
                    </div>
                @endif
            </div>
        </div>
