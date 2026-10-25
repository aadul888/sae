        <!-- TAB: Request Cetak Kartu Pelajar -->
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--text-color);">Antrean Request
                    Cetak Kartu</h3>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                    Catat permintaan cetak baru / penggantian kartu. Status "Selesai" otomatis menandai kartu sudah
                    dicetak.
                </div>
            </div>
            <button type="button" class="btn btn-primary" onclick="openTambahRequest()"
                style="padding: 8px 16px; border-radius: 8px; font-size: 0.84rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-plus-circle"></i> Tambah Request
            </button>
        </div>

        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
            @foreach ([['menunggu', 'Menunggu', 'fa-hourglass-half', '#f59e0b', '245,158,11'], ['diproses', 'Diproses', 'fa-gears', '#2563eb', '37,99,235'], ['selesai', 'Selesai', 'fa-circle-check', '#10b981', '16,185,129'], ['ditolak', 'Ditolak', 'fa-ban', '#ef4444', '239,68,68']] as [$sKey, $sLabel, $sIcon, $sColor, $sRgb])
                <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'request', 'req_status' => $reqStatus === $sKey ? '' : $sKey, 'q' => $q, 'perPage' => $perPage]) }}"
                    class="dash-stat-card"
                    style="text-decoration: none; {{ $reqStatus === $sKey ? 'outline: 2px solid ' . $sColor . ';' : '' }}">
                    <div class="dash-stat-icon"
                        style="background: rgba({{ $sRgb }},0.12); color: {{ $sColor }};">
                        <i class="fas {{ $sIcon }}"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value" style="font-size: 1.35rem; color: {{ $sColor }};">
                            {{ number_format($requestStats[$sKey] ?? 0, 0, ',', '.') }}</div>
                        <div class="dash-stat-label">{{ $sLabel }}</div>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- Unified Datatable Request Cetak (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
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

                        <select id="filterReqStatus" name="req_status" class="toolbar-filter-select"
                            style="height: 38px;">
                            <option value="">Semua Status Request</option>
                            @foreach (\App\Models\KartuPelajarRequest::STATUS as $stKey => $stLabel)
                                <option value="{{ $stKey }}" {{ $reqStatus === $stKey ? 'selected' : '' }}>
                                    {{ $stLabel }}
                                </option>
                            @endforeach
                        </select>

                        @if ($q || $rombel || $statusBayar || $reqStatus)
                            <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'request']) }}"
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
                            placeholder="Cari nama / NISN / NIPD / alasan..." value="{{ $q }}"
                            autocomplete="off">
                        <button type="button" id="clearSearchKartuPelajar"
                            class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Floating Multi-Select Bulk Actions Bar untuk Request Cetak -->
                <div id="bulkActionRequestBar"
                    style="display: none; margin-top: 14px; padding: 10px 14px; border-radius: 8px; background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.25); align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div
                        style="font-size: 0.85rem; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <span style="display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-check-square"></i>
                            <span><span id="bulkRequestSelectedCount">0</span> antrean dipilih</span>
                        </span>
                        <label id="wrapPilihSemuaFilterRequest"
                            style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.78rem; font-weight: 600; color: #4338ca; background: #ffffff; padding: 4px 10px; border-radius: 6px; border: 1px solid #c7d2fe; cursor: pointer;">
                            <input type="checkbox" id="checkPilihSemuaFilterRequest"
                                style="accent-color: var(--primary); cursor: pointer;">
                            <span>Pilih <strong>Semua
                                    ({{ number_format(!empty($requestList) ? $requestList->total() : 0, 0, ',', '.') }})</strong>
                                Sesuai Filter Saat Ini</span>
                        </label>
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                        <button type="button" class="btn btn-outline btn-sm"
                            onclick="executeBulkRequestAction('set_diproses')"
                            style="border-color: #2563eb; color: #2563eb; font-weight: 600; padding: 5px 12px; font-size: 0.8rem;">
                            <i class="fas fa-gears me-1"></i> Set Diproses
                        </button>
                        <button type="button" class="btn btn-primary btn-sm"
                            onclick="executeBulkRequestAction('set_selesai')"
                            style="background: #10b981; border-color: #10b981; font-weight: 700; padding: 5px 12px; font-size: 0.8rem;">
                            <i class="fas fa-circle-check me-1"></i> Set Selesai (Cetak)
                        </button>
                        <button type="button" class="btn btn-outline btn-sm"
                            onclick="executeBulkRequestAction('set_ditolak')"
                            style="border-color: #ef4444; color: #ef4444; font-weight: 600; padding: 5px 12px; font-size: 0.8rem;">
                            <i class="fas fa-ban me-1"></i> Tolak
                        </button>
                        <button type="button" class="btn btn-outline btn-sm"
                            onclick="executeBulkRequestAction('delete')"
                            style="border-color: #94a3b8; color: #475569; font-weight: 600; padding: 5px 10px; font-size: 0.8rem;"
                            title="Hapus antrean terpilih">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Table Datatable Request Cetak -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                            <th style="width: 40px; text-align: center; padding: 12px 10px;">
                                <input type="checkbox" id="checkAllRequests"
                                    title="Pilih Semua Antrean di Halaman Ini">
                            </th>
                            <th
                                style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">
                                No</th>
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
                            <th class="sortable-th {{ in_array($sort, ['created_at', 'tanggal']) ? 'sorted' : '' }}"
                                data-sort="created_at"
                                style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Tanggal Request
                                <span class="sort-icon">{!! in_array($sort, ['created_at', 'tanggal']) ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sort === 'jenis_request' ? 'sorted' : '' }}"
                                data-sort="jenis_request"
                                style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Jenis &amp; Alasan
                                <span class="sort-icon">{!! $sort === 'jenis_request' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sort === 'status_pembayaran' ? 'sorted' : '' }}"
                                data-sort="status_pembayaran"
                                style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Pembayaran
                                <span class="sort-icon">{!! $sort === 'status_pembayaran' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sort === 'status' ? 'sorted' : '' }}" data-sort="status"
                                style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Status Request
                                <span class="sort-icon">{!! $sort === 'status' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th
                                style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 170px;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($requestList ?? []) as $ri => $rq)
                            @php
                                $rqColor =
                                    [
                                        'menunggu' => 'badge-warning',
                                        'diproses' => 'badge-primary',
                                        'selesai' => 'badge-success',
                                        'ditolak' => 'badge-danger',
                                    ][$rq->status] ?? 'badge-outline';
                            @endphp
                            <tr
                                style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;">
                                <td style="text-align: center; padding: 12px 10px;" data-label="Pilih">
                                    <input type="checkbox" class="check-request-row" value="{{ $rq->id }}">
                                </td>
                                <td style="text-align: center; color: var(--text-muted); font-size: 0.84rem;"
                                    data-label="No">
                                    {{ $requestList->firstItem() + $ri }}</td>
                                <td style="padding: 12px 16px;" data-label="Peserta Didik">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="{{ $rq->foto_url ?: asset('img/logo-dark.png') }}"
                                            alt="{{ $rq->nama }}"
                                            style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--border-color); flex-shrink: 0;"
                                            onerror="this.src='/img/logo-dark.png'">
                                        <div>
                                            <div
                                                style="font-weight: 700; font-size: 0.88rem; color: var(--text-color);">
                                                {{ $rq->nama }}
                                            </div>
                                            <div
                                                style="font-size: 0.74rem; color: var(--text-muted); font-family: monospace;">
                                                NISN: {{ $rq->nisn ?: '-' }} &bull; NIPD: {{ $rq->nipd ?: '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 12px 14px;" data-label="Kelas">
                                    <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">
                                        {{ $rq->nama_rombel ?: '-' }}
                                    </span>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.78rem; color: var(--text-muted); white-space: nowrap;"
                                    data-label="Tanggal">
                                    {{ \Carbon\Carbon::parse($rq->created_at)->translatedFormat('d M Y H:i') }}
                                    <div>oleh {{ $rq->diminta_oleh ?: 'Admin' }}</div>
                                </td>
                                <td style="padding: 12px 14px;" data-label="Jenis">
                                    <span class="badge badge-outline"
                                        style="font-size: 0.72rem;">{{ \App\Models\KartuPelajarRequest::JENIS[$rq->jenis_request] ?? $rq->jenis_request }}</span>
                                    @if ($rq->alasan)
                                        <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 3px;">
                                            {{ $rq->alasan }}</div>
                                    @endif
                                    @if ($rq->catatan_admin)
                                        <div style="font-size: 0.74rem; color: var(--primary); margin-top: 2px;"><i
                                                class="fas fa-comment-dots me-1"></i>{{ $rq->catatan_admin }}</div>
                                    @endif
                                </td>
                                <td style="padding: 12px 14px;" data-label="Pembayaran">
                                    <span class="badge badge-outline" style="font-size: 0.72rem; padding: 2px 7px;">
                                        {{ \App\Models\KartuPelajarRequest::METODE_PEMBAYARAN[$rq->metode_pembayaran_aktif] ?? strtoupper($rq->metode_pembayaran_aktif ?: 'Belum diisi') }}
                                    </span>
                                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 3px;">
                                        {{ $rq->status_pembayaran_aktif === 'lunas' ? 'Lunas' : 'Belum Bayar' }}
                                    </div>
                                </td>
                                <td style="padding: 12px 14px;" data-label="Status">
                                    <span class="badge {{ $rqColor }}"
                                        style="font-size: 0.74rem; padding: 3px 8px;">{{ \App\Models\KartuPelajarRequest::STATUS[$rq->status] ?? $rq->status }}</span>
                                    @if ($rq->selesai_at)
                                        <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">
                                            {{ \Carbon\Carbon::parse($rq->selesai_at)->translatedFormat('d M Y H:i') }}
                                        </div>
                                    @endif
                                </td>
                                <td style="padding: 12px 14px; text-align: center;" data-label="Aksi">
                                    <div class="table-actions"
                                        style="display: flex; gap: 4px; justify-content: center; align-items: center;">
                                        {{-- Tombol Pratinjau / Cetak Kartu Digital --}}
                                        @if (!empty($rq->nisn))
                                            <button type="button" class="btn-icon" style="color: #0284c7;"
                                                title="Pratinjau / Cetak Kartu Pelajar Digital"
                                                onclick="openKartuPelajarModal('{{ $rq->nisn }}')">
                                                <i class="fas fa-address-card"></i>
                                            </button>
                                        @endif

                                        <form action="{{ route('dashboard.kartu-pelajar.update-request', $rq->id) }}"
                                            method="POST" class="form-update-request-status"
                                            style="margin: 0; display: inline-flex;">
                                            @csrf
                                            <select name="status" class="form-control select-request-status"
                                                data-current="{{ $rq->status }}" title="Ubah Status Request"
                                                style="width: 105px; height: 30px; padding: 2px 6px; font-size: 0.76rem; border-radius: 6px; font-weight: 600;">
                                                @foreach (\App\Models\KartuPelajarRequest::STATUS as $stKey => $stLabel)
                                                    <option value="{{ $stKey }}"
                                                        {{ $rq->status === $stKey ? 'selected' : '' }}>
                                                        {{ $stLabel }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                        <form action="{{ route('dashboard.kartu-pelajar.destroy-request', $rq->id) }}"
                                            method="POST" class="delete-request-form form-destroy-request"
                                            style="margin: 0; display: inline-flex;">
                                            @csrf
                                            <button type="submit" class="btn-icon" style="color: #ef4444;"
                                                title="Hapus Request"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8"
                                    style="padding: 35px; text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-paper-plane mb-2"
                                        style="font-size: 2rem; opacity: 0.4; display: block;"></i>
                                    Belum ada request cetak kartu.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 3. Footer Paginasi Terpadu Datatable -->
            @if (!empty($requestList) && $requestList->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $requestList])
                </div>
            @endif
        </div>
