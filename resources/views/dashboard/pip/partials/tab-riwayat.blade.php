<!-- 1. Stat Grid Baku SAE (4 Kartu Ringkasan Riwayat Siswa Aktif - Genap & Konsisten) -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
            <i class="fas fa-users"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--primary);">
                {{ number_format($riwayatStats['total_siswa_aktif'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Siswa Aktif</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #10b981;">
                {{ number_format($riwayatStats['total_penerima_unik'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Penerima PIP</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
            <i class="fas fa-user-xmark"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #f59e0b;">
                {{ number_format($riwayatStats['total_belum'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Belum Terima PIP</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(14,165,233,0.12); color: #0ea5e9;">
            <i class="fas fa-money-bill-trend-up"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #0ea5e9; white-space: nowrap;">
                Rp {{ number_format($riwayatStats['total_nominal'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Total Dana Cair</div>
        </div>
    </div>
</div>

@if ($isAdmin)
    <div
        style="background: rgba(99,102,241,0.06); border: 1px dashed rgba(99,102,241,0.25); border-radius: var(--radius-lg, 12px); padding: 12px 16px; margin-bottom: 20px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-info-circle text-primary" style="font-size: var(--text-lg, 1.125rem); flex-shrink: 0;"></i>
            <span>Tab ini menampilkan data <strong>Peserta Didik Aktif</strong>, baik yang sudah menerima maupun yang
                <strong>belum mendapatkan PIP</strong>. Data siswa yang telah lulus atau mutasi alumni berada di tab
                khusus.</span>
        </div>
        <a href="{{ route('dashboard.pip.index', array_filter(['tab' => 'riwayat_alumni', 'tahun' => $activeTahun])) }}"
            class="btn btn-outline btn-sm" style="white-space: nowrap; font-size: var(--text-sm, 0.8125rem); font-weight: 600;">
            <i class="fas fa-user-graduate me-1"></i> Buka Riwayat Kelas Lain &amp; Alumni
        </a>
    </div>
@endif

<!-- Unified Datatable Riwayat PIP (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
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

                <select id="filterStatusPip" name="status_pip" class="toolbar-filter-select"
                    style="height: 38px; min-width: 175px;">
                    <option value="penerima" {{ ($statusPipFilter ?? 'penerima') === 'penerima' ? 'selected' : '' }}>
                        Penerima PIP (Riwayat)
                    </option>
                    <option value="belum" {{ ($statusPipFilter ?? 'penerima') === 'belum' ? 'selected' : '' }}>
                        Belum Mendapatkan PIP
                    </option>
                    <option value="semua" {{ ($statusPipFilter ?? 'penerima') === 'semua' ? 'selected' : '' }}>
                        Semua Siswa (Penerima &amp; Belum)
                    </option>
                </select>

                @if (($statusPipFilter ?? 'penerima') !== 'belum')
                    <select id="filterJenisSk" name="jenis_sk" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Jenis SK</option>
                        <option value="pemberian" {{ ($jenisSkFilter ?? '') === 'pemberian' ? 'selected' : '' }}>SK
                            Pemberian</option>
                        <option value="nominasi" {{ ($jenisSkFilter ?? '') === 'nominasi' ? 'selected' : '' }}>SK
                            Nominasi
                        </option>
                    </select>
                @endif

                @if ($isAdmin)
                    <select id="filterRombel" name="rombel_id" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Kelas</option>
                        @foreach ($rombelList as $r)
                            <option value="{{ $r->rombongan_belajar_id }}"
                                {{ $activeRombel?->rombongan_belajar_id === $r->rombongan_belajar_id ? 'selected' : '' }}>
                                {{ $r->nama }}
                            </option>
                        @endforeach
                    </select>
                @endif

                @if (($statusPipFilter ?? 'penerima') !== 'belum')
                    <select id="filterTahap" name="tahap" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Tahap</option>
                        @foreach ($riwayatTahapList as $th)
                            <option value="{{ $th }}" {{ request('tahap') === $th ? 'selected' : '' }}>
                                {{ $th }}
                            </option>
                        @endforeach
                    </select>
                @endif

                @if (
                    $q ||
                        request('tahap') ||
                        ($jenisSkFilter ?? '') ||
                        ($isAdmin && $activeRombel) ||
                        ($statusPipFilter ?? 'penerima') !== 'penerima')
                    <a href="{{ route('dashboard.pip.index', array_filter(['tab' => 'riwayat', 'tahun' => $activeTahun])) }}"
                        class="btn btn-outline btn-reset-filter"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                        title="Reset filter" data-live-reset>
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>

            <div class="live-search-wrap" style="height: 38px;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchPip" name="q"
                    placeholder="Cari nama / NISN / No SK / ibu kandung..." value="{{ $q }}"
                    autocomplete="off">
                <button type="button" id="clearSearchPip" class="clear-search {{ !empty($q) ? 'visible' : '' }}"
                    title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Konten Datatable Responsive Stack -->
    <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
        <table class="table table-pd table-pip-riwayat"
            style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                    <th class="th-no d-none-mobile"
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); width: 48px; text-align: center;">
                        No</th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); min-width: 220px;">
                        Peserta Didik</th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); min-width: 120px;">
                        Kelas Aktif</th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); min-width: 200px;">
                        Status PIP / SK</th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); text-align: right; min-width: 120px;">
                        Nominal</th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); min-width: 140px;">
                        Pencairan / Status</th>
                    <th
                        style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; color: var(--text-muted); text-align: center; width: 100px;">
                        Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($riwayatList as $r)
                    @php
                        $isBelum = !empty($r->is_belum_menerima);
                        $isNominasi = ($r->jenis_sk ?? 'pemberian') === 'nominasi';
                        $cair =
                            stripos((string) $r->status_cair, 'cair') !== false &&
                            stripos((string) $r->status_cair, 'belum') === false;
                        $namaSiswa = $r->nama_pd_aktif ?: ($r->nama_pd ?: 'Peserta Didik');
                        $nisnSiswa = $r->nisn_pd_aktif ?: ($r->nisn ?: '-');
                        $kelasAktif =
                            $r->nama_kelas_aktif ?: ($r->nama_rombel_aktif ?: ($r->rombel ?: ($r->kelas ?: '-')));
                        $itemIndex = ($riwayatList->firstItem() ?: 1) + $loop->index;
                    @endphp
                    <tr class="pip-riwayat-row"
                        style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                        <!-- No Urut (Desktop Only) -->
                        <td class="cell-pd-no d-none-mobile"
                            style="text-align: center; color: var(--text-muted); font-size: var(--text-base, 0.875rem); font-weight: 700;"
                            data-label="No">
                            {{ $itemIndex }}
                        </td>

                        <!-- Kolom Peserta Didik -->
                        <td class="cell-pd-nama" style="padding: 12px 14px;" data-label="Peserta Didik">
                            <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 10px;">
                                <div class="user-avatar pd-foto-thumb"
                                    style="width: 40px; height: 40px; border-radius: var(--radius-lg, 12px); overflow: hidden; background: rgba(99,102,241,0.12); flex-shrink: 0; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-color);">
                                    <img src="{{ $r->foto_url ?: asset('img/logo-dark.png') }}"
                                        alt="{{ $namaSiswa }}"
                                        style="width: 100%; height: 100%; object-fit: cover;"
                                        onerror="this.src='/img/logo-dark.png'">
                                </div>
                                <div class="pd-info" style="min-width: 0; flex: 1;">
                                    <div class="pd-title-row"
                                        style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                        <span class="badge badge-outline badge-row-no d-none-desktop d-block-mobile"
                                            style="font-size: var(--text-2xs, 0.6875rem); padding: 1px 6px; font-weight: 700; color: var(--text-muted);">
                                            #{{ $itemIndex }}
                                        </span>
                                        <div class="pd-nama"
                                            style="font-weight: 800; font-size: var(--text-base, 0.875rem); color: var(--text-color); line-height: 1.35; word-break: break-word;">
                                            {{ $namaSiswa }}
                                        </div>
                                    </div>
                                    <div class="pd-meta-row"
                                        style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-family: monospace; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 2px;">
                                        <span>NISN: <strong>{{ $nisnSiswa }}</strong></span>
                                        @if (!empty($r->nipd_pd_aktif))
                                            &bull; <span>NIPD: {{ $r->nipd_pd_aktif }}</span>
                                        @endif
                                        @if ($r->nama_ibu_kandung)
                                            &bull; <span style="font-family: inherit;">Ibu:
                                                {{ $r->nama_ibu_kandung }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Kolom Kelas Aktif -->
                        <td class="cell-pd-kelas" style="padding: 12px 14px;" data-label="Kelas">
                            <span class="badge badge-outline"
                                style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; font-weight: 600;">{{ $kelasAktif }}</span>
                            @if (
                                !$isBelum &&
                                    !empty($r->rombel) &&
                                    $r->rombel !== ($r->nama_kelas_aktif ?? '') &&
                                    $r->rombel !== ($r->nama_rombel_aktif ?? ''))
                                <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); margin-top: 3px;"
                                    title="Rombel tercatat saat SK SiPintar">
                                    SK: {{ $r->rombel }}
                                </div>
                            @endif
                        </td>

                        <!-- Kolom Status PIP / SK -->
                        <td class="cell-pd-status" style="padding: 12px 14px;" data-label="Status PIP / SK">
                            @if ($isBelum)
                                <div style="display: flex; flex-direction: column; gap: 3px;">
                                    <span class="badge badge-warning"
                                        style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; width: fit-content;">
                                        <i class="fas fa-minus-circle"></i> Belum Menerima PIP
                                    </span>
                                    @if (!empty($r->status_usulan))
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--primary); font-weight: 600;">
                                            <i class="fas fa-file-signature me-1"></i> Usulan {{ $activeTahun }}:
                                            <span class="badge badge-outline"
                                                style="font-size: var(--text-2xs, 0.6875rem); padding: 1px 5px;">
                                                {{ ucwords(str_replace('_', ' ', $r->status_usulan)) }}
                                            </span>
                                            @if (!empty($r->fase_usulan))
                                                <span class="badge badge-primary"
                                                    style="font-size: var(--text-2xs, 0.6875rem); padding: 1px 5px;">
                                                    {{ \App\Models\PipUsulan::getFaseLabel($r->fase_usulan) }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                            Belum diusulkan tahun {{ $activeTahun }}
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                    <div
                                        style="display: flex; align-items: center; gap: 6px; margin-bottom: 2px; flex-wrap: wrap;">
                                        <span class="badge {{ $isNominasi ? 'badge-warning' : 'badge-success' }}"
                                            style="font-size: var(--text-2xs, 0.6875rem); padding: 1px 6px;">
                                            {{ $isNominasi ? 'SK Nominasi' : 'SK Pemberian' }}
                                        </span>
                                        @if (!empty($r->fase))
                                            <span class="badge badge-outline"
                                                style="font-size: var(--text-2xs, 0.6875rem); padding: 1px 5px;">
                                                {{ \App\Models\PipUsulan::getFaseLabel($r->fase) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color);">
                                        {{ $r->nomor_sk ?: '-' }}
                                    </div>
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                        @if (!empty($r->tahun_pelajaran))
                                            <span style="font-weight: 700;">TP {{ $r->tahun_pelajaran }}</span> &bull;
                                        @elseif (!empty($r->tahun))
                                            <span style="font-weight: 700;">Th {{ $r->tahun }}</span> &bull;
                                        @endif
                                        {{ $r->tahap ?: '-' }}@if ($r->tanggal_sk)
                                            &bull; {{ $r->tanggal_sk }}
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </td>

                        <!-- Kolom Nominal -->
                        <td class="cell-pd-nominal"
                            style="padding: 12px 14px; text-align: right; font-weight: 700; white-space: nowrap;"
                            data-label="Nominal">
                            @if ($isBelum)
                                <span
                                    style="color: var(--text-muted); font-size: var(--text-sm, 0.8125rem); font-weight: normal;">-</span>
                            @else
                                {{ $r->nominal !== null ? 'Rp ' . number_format($r->nominal, 0, ',', '.') : '-' }}
                            @endif
                        </td>

                        <!-- Kolom Pencairan / Status -->
                        <td class="cell-pd-cair" style="padding: 12px 14px;" data-label="Pencairan">
                            @if ($isBelum)
                                <span class="badge badge-outline text-muted"
                                    style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                    Non-Penerima
                                </span>
                            @elseif ($isNominasi)
                                <span
                                    class="badge {{ !empty($r->tanggal_aktivasi) ? 'badge-success' : 'badge-warning' }}"
                                    style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                    {{ !empty($r->tanggal_aktivasi) ? 'Sudah Aktivasi' : 'Belum Aktivasi' }}
                                </span>
                                @if ($r->tanggal_aktivasi)
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 3px;">
                                        Tgl: {{ $r->tanggal_aktivasi }}
                                    </div>
                                @endif
                            @else
                                <span class="badge {{ $cair ? 'badge-success' : 'badge-warning' }}"
                                    style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                    {{ $r->status_cair ?: 'Belum ada status' }}
                                </span>
                                @if ($r->tanggal_cair)
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 3px;">
                                        Tgl Cair: {{ $r->tanggal_cair }}
                                    </div>
                                @endif
                            @endif
                        </td>

                        <!-- Kolom Aksi -->
                        <td class="cell-pd-aksi" style="padding: 12px 14px; text-align: center;" data-label="Aksi">
                            @if ($isBelum)
                                @if (!empty($r->status_usulan))
                                    <span class="badge badge-info"
                                        style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; font-weight: 600;"
                                        title="Siswa telah terdaftar pada daftar Usulan PIP">
                                        <i class="fas fa-check me-1"></i> Terusul
                                    </span>
                                @else
                                    <button type="button" class="btn-icon btn-usulkan-pip"
                                        onclick="openTambahUsulanSiswa('{{ $r->peserta_didik_id }}', @js($namaSiswa), @js($kelasAktif), @js($nisnSiswa))"
                                        title="Ajukan Usulan PIP Siswa Ini" style="color: var(--primary);">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                @endif
                            @elseif ($isAdmin)
                                <button type="button" class="btn-icon" style="color: #ef4444;"
                                    title="Hapus Riwayat"
                                    onclick="pipHapusRiwayat({{ $r->id }}, @js($r->nama_pd))">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            @else
                                <span class="badge badge-outline text-success"
                                    style="font-size: var(--text-xs, 0.75rem); padding: 2px 6px;">
                                    <i class="fas fa-check me-1"></i> Terdata
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="cell-empty"
                                style="padding: 35px; text-align: center; color: var(--text-muted);">
                                <i class="fas fa-clock-rotate-left mb-2"
                                    style="font-size: var(--text-3xl, 1.875rem); opacity: 0.4; display: block;"></i>
                                @if (($statusPipFilter ?? 'penerima') === 'belum')
                                    Seluruh peserta didik aktif di rombel ini telah terdaftar sebagai penerima PIP.
                                @elseif (($statusPipFilter ?? 'penerima') === 'semua')
                                    Tidak ada data peserta didik yang cocok dengan kriteria pencarian.
                                @else
                                    Belum ada riwayat penerima PIP untuk peserta didik aktif saat ini.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 3. Footer Paginasi Terpadu Datatable -->
        @if ($riwayatList->hasPages())
            <div class="datatable-footer">
                @include('partials.datatable-pagination', ['paginator' => $riwayatList])
            </div>
        @endif
    </div>
