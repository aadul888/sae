<!-- 1. Stat Grid Baku SAE (4 Kartu Ringkasan Riwayat - Jumlah Genap Rapi) -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
            <i class="fas fa-users"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: var(--primary);">
                {{ number_format($riwayatStats['total_siswa'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Total Siswa Aktif Penerima</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: #10b981;">
                {{ number_format($riwayatStats['total_pemberian'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">SK Pemberian (Pencairan)</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
            <i class="fas fa-file-signature"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: #f59e0b;">
                {{ number_format($riwayatStats['total_nominasi'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">SK Nominasi (Aktivasi)</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(14,165,233,0.12); color: #0ea5e9;">
            <i class="fas fa-money-bill-trend-up"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.25rem; color: #0ea5e9;">
                Rp {{ number_format($riwayatStats['total_nominal'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Total Dana Tersalurkan</div>
        </div>
    </div>
</div>

@if ($isAdmin)
    <div
        style="background: rgba(99,102,241,0.06); border: 1px dashed rgba(99,102,241,0.25); border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: 0.82rem; color: var(--text-color); display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-info-circle text-primary" style="font-size: 1.1rem; flex-shrink: 0;"></i>
            <span>Tab ini khusus menampilkan riwayat penerima PIP untuk <strong>Peserta Didik Aktif</strong> pada rombel
                saat ini. Data riwayat dari kelas lain, siswa yang telah lulus, atau alumni telah dipisahkan ke tab
                khusus.</span>
        </div>
        <a href="{{ route('dashboard.pip.index', array_filter(['tab' => 'riwayat_alumni', 'tahun' => $activeTahun])) }}"
            class="btn btn-outline btn-sm" style="white-space: nowrap; font-size: 0.78rem; font-weight: 600;">
            <i class="fas fa-user-graduate me-1"></i> Buka Riwayat Kelas Lain &amp; Alumni
        </a>
    </div>
@endif

<div class="card" style="padding: 16px; margin-bottom: 20px;">
    <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            <div class="toolbar-entries">
                <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                <select id="perPageSelect" name="perPage" class="per-page-select">
                    @foreach ([10, 15, 25, 50, 100] as $n)
                        <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}
                        </option>
                    @endforeach
                </select>
                <span>entri</span>
            </div>

            <select id="filterJenisSk" name="jenis_sk" class="toolbar-filter-select">
                <option value="">Semua Jenis SK</option>
                <option value="pemberian" {{ ($jenisSkFilter ?? '') === 'pemberian' ? 'selected' : '' }}>SK Pemberian
                </option>
                <option value="nominasi" {{ ($jenisSkFilter ?? '') === 'nominasi' ? 'selected' : '' }}>SK Nominasi
                </option>
            </select>

            @if ($isAdmin)
                <select id="filterRombel" name="rombel_id" class="toolbar-filter-select">
                    <option value="">Semua Kelas</option>
                    @foreach ($rombelList as $r)
                        <option value="{{ $r->rombongan_belajar_id }}"
                            {{ $activeRombel?->rombongan_belajar_id === $r->rombongan_belajar_id ? 'selected' : '' }}>
                            {{ $r->nama }}
                        </option>
                    @endforeach
                </select>
            @endif

            <select id="filterTahap" name="tahap" class="toolbar-filter-select">
                <option value="">Semua Tahap</option>
                @foreach ($riwayatTahapList as $th)
                    <option value="{{ $th }}" {{ request('tahap') === $th ? 'selected' : '' }}>
                        {{ $th }}
                    </option>
                @endforeach
            </select>

            @if ($q || request('tahap') || ($jenisSkFilter ?? '') || ($isAdmin && $activeRombel))
                <a href="{{ route('dashboard.pip.index', array_filter(['tab' => 'riwayat', 'tahun' => $activeTahun])) }}"
                    class="btn btn-outline" style="padding: 7px 12px; font-size: 0.84rem;" title="Reset filter">
                    <i class="fas fa-undo"></i>
                </a>
            @endif
        </div>

        <div class="live-search-wrap">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchPip" name="q" placeholder="Cari nama / NISN / No SK / ibu kandung..."
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
                    Peserta Didik</th>
                <th
                    style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                    Kelas Aktif</th>
                <th
                    style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                    SK / Tahap</th>
                <th
                    style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: right;">
                    Nominal</th>
                <th
                    style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                    Pencairan</th>
                @if ($isAdmin)
                    <th
                        style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center; width: 80px;">
                        Aksi</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($riwayatList as $r)
                @php
                    $isNominasi = ($r->jenis_sk ?? 'pemberian') === 'nominasi';
                    $cair =
                        stripos((string) $r->status_cair, 'cair') !== false &&
                        stripos((string) $r->status_cair, 'belum') === false;
                @endphp
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="text-align: center; color: var(--text-muted); font-size: 0.84rem;" data-label="No">
                        {{ $riwayatList->firstItem() + $loop->index }}</td>
                    <td style="padding: 12px 14px;" data-label="Peserta Didik">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <img src="{{ $r->foto_url ?: asset('img/logo-dark.png') }}" alt="{{ $r->nama_pd }}"
                                style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--border-color); flex-shrink: 0;"
                                onerror="this.src='/img/logo-dark.png'">
                            <div>
                                <div style="font-weight: 700; font-size: 0.86rem;">{{ $r->nama_pd }}</div>
                                <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">
                                    NISN: {{ $r->nisn ?: '-' }}</div>
                                @if ($r->nama_ibu_kandung)
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">Ibu:
                                        {{ $r->nama_ibu_kandung }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="padding: 12px 14px;" data-label="Kelas">
                        @php
                            $kelasAktif =
                                $r->nama_kelas_aktif ?: ($r->nama_rombel_aktif ?: ($r->rombel ?: ($r->kelas ?: '-')));
                        @endphp
                        <span class="badge badge-outline"
                            style="font-size: 0.74rem; padding: 3px 8px; font-weight: 600;">{{ $kelasAktif }}</span>
                        @if (!empty($r->rombel) && $r->rombel !== ($r->nama_kelas_aktif ?? '') && $r->rombel !== ($r->nama_rombel_aktif ?? ''))
                            <div style="font-size: 0.68rem; color: var(--text-muted); margin-top: 3px;"
                                title="Rombel tercatat saat SK SiPintar">
                                SK: {{ $r->rombel }}
                            </div>
                        @endif
                    </td>
                    <td style="padding: 12px 14px;" data-label="SK / Tahap">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                            <span class="badge {{ $isNominasi ? 'badge-warning' : 'badge-success' }}"
                                style="font-size: 0.68rem; padding: 1px 6px;">
                                {{ $isNominasi ? 'SK Nominasi' : 'SK Pemberian' }}
                            </span>
                        </div>
                        <div style="font-size: 0.8rem; font-weight: 600;">{{ $r->nomor_sk ?: '-' }}</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">
                            {{ $r->tahap ?: '-' }}@if ($r->tanggal_sk)
                                &bull; {{ $r->tanggal_sk }}
                            @endif
                        </div>
                    </td>
                    <td style="padding: 12px 14px; text-align: right; font-weight: 700; white-space: nowrap;"
                        data-label="Nominal">
                        {{ $r->nominal !== null ? 'Rp ' . number_format($r->nominal, 0, ',', '.') : '-' }}
                    </td>
                    <td style="padding: 12px 14px;" data-label="Status / Tanggal">
                        @if ($isNominasi)
                            <span class="badge {{ !empty($r->tanggal_aktivasi) ? 'badge-success' : 'badge-warning' }}"
                                style="font-size: 0.72rem; padding: 3px 8px;">
                                {{ !empty($r->tanggal_aktivasi) ? 'Sudah Aktivasi' : 'Belum Aktivasi' }}
                            </span>
                            @if ($r->tanggal_aktivasi)
                                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 3px;">
                                    Tgl: {{ $r->tanggal_aktivasi }}
                                </div>
                            @endif
                        @else
                            <span class="badge {{ $cair ? 'badge-success' : 'badge-warning' }}"
                                style="font-size: 0.72rem; padding: 3px 8px;">
                                {{ $r->status_cair ?: 'Belum ada status' }}
                            </span>
                            @if ($r->tanggal_cair)
                                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 3px;">
                                    Tgl Cair: {{ $r->tanggal_cair }}
                                </div>
                            @endif
                        @endif
                    </td>
                    @if ($isAdmin)
                        <td style="padding: 12px 14px; text-align: center;" data-label="Aksi">
                            <button type="button" class="btn-icon" style="color: #ef4444;" title="Hapus"
                                onclick="pipHapusRiwayat({{ $r->id }}, @js($r->nama_pd))">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    @endif
                </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isAdmin ? 7 : 6 }}"
                            style="padding: 35px; text-align: center; color: var(--text-muted);">
                            <i class="fas fa-clock-rotate-left mb-2"
                                style="font-size: 2rem; opacity: 0.4; display: block;"></i>
                            Belum ada riwayat penerima PIP untuk peserta didik aktif saat ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('dashboard.pip.partials.pagination', ['paginator' => $riwayatList])
