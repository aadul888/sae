        <!-- 3. Header & Stat Grid Baku SAE (4 Kartu Ringkasan Berhasil Cetak - Jumlah Genap Rapi) -->
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--text-color);">
                    Data Kartu Berhasil Cetak
                </h3>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                    Daftar peserta didik yang telah selesai cetak dari antrean request. Kelola pencatatan pembayaran,
                    klaim
                    garansi kartu/stiker, serta pengiriman WhatsApp.
                </div>
            </div>
        </div>

        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                    <i class="fas fa-id-card-clip"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: var(--primary);">
                        {{ number_format($stats['total_sudah_cetak'] ?? ($stats['total_siswa'] ?? 0), 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Kartu Berhasil Dicetak</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #10b981;">
                        {{ number_format($stats['total_lunas'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Pembayaran Lunas (Rp
                        {{ number_format($stats['total_dana'], 0, ',', '.') }})
                    </div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #f59e0b;">
                        {{ number_format($stats['total_belum_bayar'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Belum Membayar</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #f59e0b;">
                        {{ number_format(($stats['total_klaim_kartu'] ?? 0) + ($stats['total_klaim_stiker'] ?? 0), 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">
                        Klaim Garansi ({{ $stats['total_klaim_kartu'] ?? 0 }} Kartu &bull;
                        {{ $stats['total_klaim_stiker'] ?? 0 }} Stiker)
                    </div>
                </div>
            </div>
        </div>

        <!-- Unified Datatable Kartu Berhasil Cetak (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <!-- 1. Toolbar & Filter Header -->
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

                        <select id="filterRombel" name="rombel" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Rombel</option>
                            @foreach ($rombelList as $r)
                                <option value="{{ $r->nama }}" {{ $rombel === $r->nama ? 'selected' : '' }}>
                                    {{ $r->nama }}
                                </option>
                            @endforeach
                        </select>

                        <select id="filterStatusBayar" name="status_pembayaran" class="toolbar-filter-select"
                            style="height: 38px;">
                            <option value="">Semua Status Pembayaran</option>
                            <option value="lunas" {{ $statusBayar === 'lunas' ? 'selected' : '' }}>Lunas</option>
                            <option value="belum_bayar" {{ $statusBayar === 'belum_bayar' ? 'selected' : '' }}>Belum
                                Bayar
                            </option>
                        </select>

                        @if ($q || $rombel || $statusBayar)
                            <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'berhasil-cetak']) }}"
                                class="btn btn-outline btn-reset-filter"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;"
                                title="Reset filter" data-live-reset>
                                <i class="fas fa-undo"></i>
                            </a>
                        @endif
                    </div>

                    <div class="live-search-wrap" style="height: 38px;">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchKartuPelajar" name="q"
                            placeholder="Cari nama / NISN / NIPD..." value="{{ $q }}" autocomplete="off">
                        <button type="button" id="clearSearchKartuPelajar"
                            class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Floating Multi-Select Bulk Actions Bar -->
                <div id="bulkActionBar"
                    style="display: none; margin-top: 14px; padding: 10px 14px; border-radius: 8px; background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.25); align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div
                        style="font-size: 0.85rem; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <span style="display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-check-square"></i>
                            <span><span id="bulkSelectedCount">0</span> peserta didik dipilih</span>
                        </span>
                        <label id="wrapPilihSemuaFilterBerhasil"
                            style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.78rem; font-weight: 600; color: #4338ca; background: #ffffff; padding: 4px 10px; border-radius: 6px; border: 1px solid #c7d2fe; cursor: pointer;">
                            <input type="checkbox" id="checkPilihSemuaFilterBerhasil"
                                style="accent-color: var(--primary); cursor: pointer;">
                            <span>Pilih <strong>Semua ({{ number_format($students->total(), 0, ',', '.') }})</strong>
                                Sesuai Filter Saat Ini</span>
                        </label>
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-primary btn-sm"
                            onclick="openModalBulkPembayaran('custom_bulk')" style="font-weight: 700;">
                            <i class="fas fa-sliders-h me-1"></i> Sesuaikan Masal
                        </button>
                        <button type="button" class="btn btn-outline btn-sm"
                            onclick="openModalBulkPembayaran('set_lunas')"
                            style="border-color: #10b981; color: #10b981; font-weight: 600;">
                            <i class="fas fa-check me-1"></i> Tandai Lunas
                        </button>
                        <button type="button" class="btn btn-outline btn-sm"
                            onclick="openModalBulkPembayaran('set_belum_bayar')"
                            style="border-color: #f59e0b; color: #f59e0b; font-weight: 600;">
                            <i class="fas fa-clock me-1"></i> Tandai Belum Bayar
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Konten Datatable Responsive Stack -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                            <th style="width: 40px; text-align: center; padding: 12px 10px;">
                                <input type="checkbox" id="checkAllStudents" title="Pilih Semua di Halaman Ini">
                            </th>
                            <th
                                style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">
                                No
                            </th>
                            <th class="sortable-th {{ $sort === 'nama' ? 'sorted' : '' }}" data-sort="nama"
                                style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Peserta Didik
                                <span class="sort-icon">{!! $sort === 'nama' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sort === 'nama_rombel' ? 'sorted' : '' }}"
                                data-sort="nama_rombel"
                                style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Rombel / Kelas
                                <span class="sort-icon">{!! $sort === 'nama_rombel' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sort === 'status_pembayaran' ? 'sorted' : '' }}"
                                data-sort="status_pembayaran"
                                style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Status Pembayaran
                                <span class="sort-icon">{!! $sort === 'status_pembayaran' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sort === 'status_cetak' ? 'sorted' : '' }}"
                                data-sort="status_cetak"
                                style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Status Cetak
                                <span class="sort-icon">{!! $sort === 'status_cetak' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th
                                style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Kontak WhatsApp
                            </th>
                            <th
                                style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 145px;">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $idx => $s)
                            @php
                                $isLunas = $s->status_pembayaran === 'lunas';
                                $isCetak = $s->status_cetak === 'sudah_cetak';
                                $phone = $s->nomor_wa ?: ($s->nomor_telepon_seluler ?: $s->nomor_telepon_rumah);
                            @endphp
                            <tr
                                style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;">
                                <td style="text-align: center; padding: 12px 10px;" data-label="Pilih">
                                    <input type="checkbox" class="check-student-row"
                                        value="{{ $s->peserta_didik_id }}">
                                </td>
                                <td style="text-align: center; font-size: 0.84rem; color: var(--text-muted); padding: 12px 14px;"
                                    data-label="No">
                                    {{ $students->firstItem() + $idx }}
                                </td>
                                <td style="padding: 12px 16px;" data-label="Peserta Didik">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="{{ $s->foto_url ?: asset('img/logo-dark.png') }}"
                                            alt="{{ $s->nama }}"
                                            style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--border-color); flex-shrink: 0;"
                                            onerror="this.src='/img/logo-dark.png'">
                                        <div>
                                            <div
                                                style="font-weight: 700; font-size: 0.88rem; color: var(--text-color);">
                                                {{ $s->nama }}
                                            </div>
                                            <div
                                                style="font-size: 0.74rem; color: var(--text-muted); font-family: monospace;">
                                                NISN: {{ $s->nisn ?: '-' }} &bull; NIPD: {{ $s->nipd ?: '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 12px 14px;" data-label="Kelas">
                                    <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">
                                        {{ $s->nama_rombel ?: '-' }}
                                    </span>
                                </td>
                                <td style="padding: 12px 16px;" data-label="Pembayaran">
                                    @if ($isLunas)
                                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                                            <span class="badge badge-success"
                                                style="font-size: 0.74rem; padding: 3px 8px;">
                                                <i class="fas fa-check-circle me-1"></i> LUNAS
                                            </span>
                                        </div>
                                        <div
                                            style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); margin-top: 3px;">
                                            Rp {{ number_format($s->nominal, 0, ',', '.') }}
                                        </div>
                                        <div style="font-size: 0.7rem; color: var(--text-muted);">
                                            {{ strtoupper($s->metode_pembayaran ?: 'Tunai') }}
                                            @if ($s->tanggal_bayar)
                                                &bull;
                                                {{ \Carbon\Carbon::parse($s->tanggal_bayar)->translatedFormat('d/m/Y') }}
                                            @endif
                                        </div>
                                    @else
                                        <span class="badge badge-warning"
                                            style="font-size: 0.74rem; padding: 3px 8px;">
                                            <i class="fas fa-clock me-1"></i> BELUM BAYAR
                                        </span>
                                        <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 3px;">
                                            Tagihan: Rp {{ number_format($s->nominal, 0, ',', '.') }}
                                        </div>
                                    @endif
                                </td>
                                <td style="padding: 12px 16px;" data-label="Status Cetak">
                                    @php
                                        $sisaKartu = max(
                                            0,
                                            ($settings->kuota_ganti_kartu_full ?? 1) - (int) $s->klaim_ganti_kartu,
                                        );
                                        $sisaStiker = max(
                                            0,
                                            ($settings->kuota_ganti_stiker ?? 3) - (int) $s->klaim_stiker,
                                        );
                                    @endphp
                                    @if ($isCetak)
                                        <span id="badgeCetak_{{ $s->peserta_didik_id }}" class="badge badge-primary"
                                            style="font-size: 0.74rem; padding: 3px 8px;">
                                            <i class="fas fa-print me-1"></i> Sudah Dicetak
                                        </span>
                                        @if ($s->tanggal_cetak)
                                            <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">
                                                {{ \Carbon\Carbon::parse($s->tanggal_cetak)->translatedFormat('d/m/Y H:i') }}
                                            </div>
                                        @endif
                                        <div
                                            style="font-size: 0.69rem; color: #10b981; margin-top: 3px; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                                            <i class="fas fa-shield-alt text-success"></i>
                                            <span>Garansi: {{ $sisaKartu }} Kartu &bull; {{ $sisaStiker }}
                                                Stiker</span>
                                        </div>
                                    @else
                                        <span id="badgeCetak_{{ $s->peserta_didik_id }}" class="badge badge-outline"
                                            style="font-size: 0.74rem; padding: 3px 8px;">
                                            <i class="fas fa-clock me-1"></i> Belum Dicetak
                                        </span>
                                        <div
                                            style="font-size: 0.69rem; color: var(--text-muted); margin-top: 3px; font-style: italic;">
                                            <i class="fas fa-shield-alt" style="opacity: 0.4;"></i> Garansi belum
                                            aktif
                                        </div>
                                    @endif
                                </td>
                                <td style="padding: 12px 14px;" data-label="WhatsApp">
                                    @if ($phone)
                                        <div
                                            style="font-size: 0.8rem; font-family: monospace; color: var(--text-color);">
                                            <i class="fab fa-whatsapp text-success me-1"></i> {{ $phone }}
                                        </div>
                                        @if ($s->wa_terkirim_at)
                                            <div style="font-size: 0.68rem; color: #10b981; margin-top: 2px;">
                                                <i class="fas fa-check-double me-1"></i> Terkirim
                                                {{ \Carbon\Carbon::parse($s->wa_terkirim_at)->translatedFormat('d/m/Y H:i') }}
                                            </div>
                                        @endif
                                    @else
                                        <span
                                            style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">
                                            Belum ada nomor
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 12px 16px; text-align: center;" data-label="Aksi">
                                    <div class="table-actions"
                                        style="display: flex; gap: 4px; justify-content: center;">
                                        {{-- Tombol Edit Pembayaran --}}
                                        <button type="button" class="btn-icon" style="color: #4f46e5;"
                                            title="Catat / Edit Pembayaran"
                                            onclick="openEditPembayaran({{ json_encode($s) }})">
                                            <i class="fas fa-wallet"></i>
                                        </button>

                                        {{-- Tombol Toggle Status Cetak --}}
                                        <button type="button" class="btn-icon" style="color: #0284c7;"
                                            onclick="toggleStatusCetak('{{ $s->peserta_didik_id }}', this)"
                                            title="{{ $isCetak ? 'Tandai Belum Dicetak' : 'Tandai Sudah Dicetak' }}">
                                            <i
                                                class="fas {{ $isCetak ? 'fa-toggle-on text-primary' : 'fa-toggle-off text-muted' }}"></i>
                                        </button>

                                        {{-- Tombol Klaim Garansi --}}
                                        <button type="button" class="btn-icon"
                                            style="color: {{ $isCetak ? '#f59e0b' : '#94a3b8' }}; opacity: {{ $isCetak ? '1' : '0.6' }};"
                                            onclick="openKlaimGaransi('{{ $s->peserta_didik_id }}', '{{ addslashes($s->nama) }}', '{{ $s->nisn }}', '{{ addslashes($s->nama_rombel ?? '-') }}', {{ (int) $s->klaim_ganti_kartu }}, {{ (int) $s->klaim_stiker }}, {{ (int) ($settings->kuota_ganti_kartu_full ?? 1) }}, {{ (int) ($settings->kuota_ganti_stiker ?? 3) }}, {{ $isCetak ? 1 : 0 }})"
                                            title="{{ $isCetak ? 'Klaim Garansi (Sisa: ' . $sisaKartu . ' Kartu, ' . $sisaStiker . ' Stiker)' : 'Garansi Belum Aktif (Kartu Belum Dicetak)' }}">
                                            <i class="fas fa-shield-alt"></i>
                                        </button>

                                        {{-- Tombol Pratinjau / Cetak Kartu Tunggal (Sama dengan Peserta Didik Aktif) --}}
                                        @if (!empty($s->nisn))
                                            <button type="button" class="btn-icon" style="color: #0284c7;"
                                                title="Pratinjau / Cetak Kartu Pelajar Digital"
                                                onclick="openKartuPelajarModal('{{ $s->nisn }}')">
                                                <i class="fas fa-address-card"></i>
                                            </button>
                                        @endif

                                        {{-- Tombol Kirim WhatsApp & QR Code --}}
                                        <button type="button" class="btn-icon" style="color: #22c55e;"
                                            onclick="openModalWa('{{ $s->nisn }}')"
                                            title="Kirim Notifikasi WhatsApp &amp; QR Code">
                                            <i class="fab fa-whatsapp"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8"
                                    style="padding: 35px; text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-id-card mb-2"
                                        style="font-size: 2rem; opacity: 0.4; display: block;"></i>
                                    <div style="font-weight: 700; font-size: 0.92rem;">Belum ada data kartu yang
                                        berhasil
                                        dicetak.</div>
                                    <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                                        Tandai status "Selesai" pada antrean tab Request Cetak agar data peserta didik
                                        otomatis
                                        masuk ke sini untuk pengelolaan pembayaran &amp; garansi.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 3. Footer Paginasi Terpadu Datatable -->
            @if ($students->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $students])
                </div>
            @endif
        </div>
