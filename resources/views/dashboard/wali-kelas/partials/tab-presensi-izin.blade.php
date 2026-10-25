{{-- TAB 2: SURAT IZIN & SAKIT KELAS BINAAN (APPROVAL & MONITORING WALI KELAS) --}}

<!-- Stat Grid 4 Kartu Baku SAE -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); margin-bottom: 20px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
            <i class="fas fa-folder-open"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                {{ number_format($izinSummary['total'], 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Total Pengajuan Kelas</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #f59e0b;">
                {{ number_format($izinSummary['menunggu'], 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Menunggu Validasi</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #10b981;">
                {{ number_format($izinSummary['disetujui'], 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Telah Disetujui</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
            <i class="fas fa-circle-xmark"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #ef4444;">
                {{ number_format($izinSummary['ditolak'], 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Permohonan Ditolak</div>
        </div>
    </div>
</div>

<!-- Unified Datatable Izin Siswa Kelas (Card Terpadu) -->
<div class="card datatable-card-wrapper" id="tableDataContainer">
    <!-- Toolbar Header -->
    <div class="datatable-toolbar">
        <form method="GET" action="{{ route('dashboard.wali-kelas.presensi.index') }}"
            id="formFilterIzinWali" style="margin: 0; width: 100%;">
            <input type="hidden" name="tab" value="izin">
            <div class="presensi-toolbar-flex">
                <div class="presensi-filters-group">
                    <div class="toolbar-entries">
                        <label for="perPageSelectIzin" style="margin: 0;">Tampilkan</label>
                        <select name="perPage" id="perPageSelectIzin" class="per-page-select" style="height: 38px;">
                            @foreach ([10, 15, 25, 50] as $n)
                                <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    @if ($isAdmin && $rombelList->isNotEmpty())
                        <select name="rombel_id" id="adminRombelSelectIzin" class="toolbar-filter-select"
                            style="min-width: 160px; height: 38px;">
                            @foreach ($rombelList as $r)
                                <option value="{{ $r->rombongan_belajar_id }}"
                                    {{ $activeRombel?->rombongan_belajar_id === $r->rombongan_belajar_id ? 'selected' : '' }}>
                                    {{ $r->nama }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    <select name="jenis_izin" id="filterJenisIzin" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Jenis</option>
                        <option value="izin" {{ $jenisIzinFilter === 'izin' ? 'selected' : '' }}>Izin</option>
                        <option value="sakit" {{ $jenisIzinFilter === 'sakit' ? 'selected' : '' }}>Sakit</option>
                        <option value="dispen" {{ $jenisIzinFilter === 'dispen' ? 'selected' : '' }}>Dispensasi</option>
                    </select>

                    <select name="status_izin" id="filterStatusIzin" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Status</option>
                        <option value="menunggu" {{ $statusIzinFilter === 'menunggu' ? 'selected' : '' }}>Menunggu Validasi</option>
                        <option value="disetujui" {{ $statusIzinFilter === 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                        <option value="ditolak" {{ $statusIzinFilter === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                    </select>

                    @if ($qIzin || $statusIzinFilter || $jenisIzinFilter)
                        <a href="{{ route('dashboard.wali-kelas.presensi.index', ['tab' => 'izin', 'rombel_id' => request('rombel_id')]) }}"
                            class="btn btn-outline btn-responsive-icon btn-reset-filter"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                            title="Reset filter" data-live-reset>
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <!-- Live Search Box Izin -->
                <div class="live-search-wrap" style="height: 38px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="q_izin" id="liveSearchIzinInput"
                        placeholder="Cari nama / NISN / alasan..." value="{{ $qIzin }}" autocomplete="off">
                    <button type="button" id="clearSearchIzinBtn"
                        class="clear-search {{ $qIzin ? 'visible' : '' }}" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Datatable Izin Kelas Binaan (.table-responsive-stack) -->
    <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
        <table class="table table-pd table-izin-wali" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 220px;">
                        Peserta Didik
                    </th>
                    <th style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 120px;">
                        Jenis Surat
                    </th>
                    <th style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 160px;">
                        Rentang Waktu
                    </th>
                    <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 220px;">
                        Alasan &amp; Berkas
                    </th>
                    <th style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px; text-align: center;">
                        Status Validasi
                    </th>
                    <th style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">
                        Diajukan Pada
                    </th>
                    <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 110px;">
                        Aksi Validasi
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($izinList as $iz)
                    @php
                        $sDate = \Carbon\Carbon::parse($iz->tanggal_mulai);
                        $eDate = \Carbon\Carbon::parse($iz->tanggal_selesai);
                        $durasi = $sDate->diffInDays($eDate) + 1;
                    @endphp
                    <tr class="izin-row" style="border-bottom: 1px solid var(--border-color); transition: background 0.18s ease;">
                        <!-- Siswa -->
                        <td class="cell-pd-nama" style="padding: 14px 18px;" data-label="Peserta Didik">
                            <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                <div class="user-avatar pd-foto-thumb"
                                    style="width: 40px; height: 40px; border-radius: var(--radius-lg, 12px); border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                                    @if (!empty($iz->foto_path))
                                        <img src="{{ asset('storage/' . ltrim($iz->foto_path, '/')) }}"
                                            alt="{{ $iz->siswa_nama }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        <i class="fas fa-user text-primary"></i>
                                    @endif
                                </div>
                                <div class="pd-info" style="min-width: 0; flex: 1;">
                                    <div class="pd-title-row" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                        <span class="pd-nama" style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color);">
                                            {{ $iz->siswa_nama }}
                                        </span>
                                    </div>
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                        NISN: {{ $iz->siswa_nisn ?? ($iz->nisn ?? '-') }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Jenis Surat -->
                        <td class="cell-pd-jenis" style="padding: 14px 16px;" data-label="Jenis Surat">
                            @if ($iz->jenis === 'sakit')
                                <span class="badge"
                                    style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); font-weight: 700; font-size: var(--text-sm, 0.8125rem); padding: 4px 10px; border-radius: var(--radius-sm, 6px);">
                                    <i class="fas fa-notes-medical me-1"></i> Sakit
                                </span>
                            @elseif ($iz->jenis === 'dispen')
                                <span class="badge"
                                    style="background: rgba(168,85,247,0.15); color: #a855f7; border: 1px solid rgba(168,85,247,0.3); font-weight: 700; font-size: var(--text-sm, 0.8125rem); padding: 4px 10px; border-radius: var(--radius-sm, 6px);">
                                    <i class="fas fa-award me-1"></i> Dispensasi
                                </span>
                            @else
                                <span class="badge"
                                    style="background: rgba(59,130,246,0.15); color: #3b82f6; border: 1px solid rgba(59,130,246,0.3); font-weight: 700; font-size: var(--text-sm, 0.8125rem); padding: 4px 10px; border-radius: var(--radius-sm, 6px);">
                                    <i class="fas fa-file-lines me-1"></i> Izin
                                </span>
                            @endif
                        </td>

                        <!-- Rentang Waktu -->
                        <td class="cell-pd-tanggal" style="padding: 14px 16px;" data-label="Rentang Waktu">
                            <div class="cell-col-right">
                                <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                    {{ $sDate->translatedFormat('d M Y') }}
                                    @if ($iz->tanggal_mulai !== $iz->tanggal_selesai)
                                        <span style="color: var(--text-muted); font-weight: normal;"> s/d </span>
                                        {{ $eDate->translatedFormat('d M Y') }}
                                    @endif
                                </div>
                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                    <i class="fas fa-clock text-primary me-1"></i> Durasi: <strong style="color: var(--text-color);">{{ $durasi }} hari</strong>
                                </div>
                            </div>
                        </td>

                        <!-- Alasan & Berkas Lampiran -->
                        <td class="cell-pd-alasan" style="padding: 14px 18px;" data-label="Alasan &amp; Berkas">
                            <div class="cell-col-right" style="text-align: left;">
                                <div style="font-size: var(--text-base, 0.875rem); color: var(--text-color); font-weight: 500; line-height: 1.4; margin-bottom: 4px;">
                                    {{ $iz->alasan }}
                                </div>
                                @if (!empty($iz->lampiran_path))
                                    <div>
                                        <button type="button" class="btn btn-outline btn-sm btn-preview-lampiran-wali"
                                            data-url="{{ asset('storage/' . ltrim($iz->lampiran_path, '/')) }}"
                                            data-title="Lampiran Surat {{ ucfirst($iz->jenis) }} — {{ $iz->siswa_nama }}"
                                            style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; border-radius: var(--radius-sm, 6px);">
                                            <i class="fas fa-paperclip me-1 text-primary"></i> Lihat Lampiran
                                        </button>
                                    </div>
                                @else
                                    <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-style: italic;">
                                        Tanpa lampiran berkas
                                    </span>
                                @endif
                            </div>
                        </td>

                        <!-- Status Validasi -->
                        <td class="cell-pd-status" style="padding: 14px 16px; text-align: center;" data-label="Status Validasi">
                            <div class="cell-col-right" style="align-items: center;">
                                @if ($iz->status === 'disetujui')
                                    <span class="badge"
                                        style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); font-weight: 700; font-size: var(--text-sm, 0.8125rem); padding: 4px 8px; border-radius: var(--radius-sm, 6px);">
                                        <i class="fas fa-check-circle me-1"></i> Disetujui
                                    </span>
                                    @if ($iz->disetujui_oleh)
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 3px;">
                                            Oleh: {{ $iz->disetujui_oleh }}
                                        </div>
                                    @endif
                                @elseif ($iz->status === 'ditolak')
                                    <span class="badge"
                                        style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); font-weight: 700; font-size: var(--text-sm, 0.8125rem); padding: 4px 8px; border-radius: var(--radius-sm, 6px);">
                                        <i class="fas fa-times-circle me-1"></i> Ditolak
                                    </span>
                                    @if ($iz->catatan_petugas)
                                        <div style="font-size: var(--text-xs, 0.75rem); color: #ef4444; margin-top: 3px;">
                                            Catatan: {{ $iz->catatan_petugas }}
                                        </div>
                                    @endif
                                @else
                                    <span class="badge"
                                        style="background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); font-weight: 700; font-size: var(--text-sm, 0.8125rem); padding: 4px 8px; border-radius: var(--radius-sm, 6px);">
                                        <i class="fas fa-hourglass-half me-1"></i> Menunggu
                                    </span>
                                @endif
                            </div>
                        </td>

                        <!-- Diajukan Pada -->
                        <td class="cell-pd-created" style="padding: 14px 16px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);" data-label="Diajukan Pada">
                            <div class="cell-col-right">
                                <div>{{ \Carbon\Carbon::parse($iz->created_at)->translatedFormat('d/m/Y') }}</div>
                                <div style="font-size: var(--text-xs, 0.75rem);">{{ \Carbon\Carbon::parse($iz->created_at)->format('H:i') }} WIB</div>
                            </div>
                        </td>

                        <!-- Aksi Verifikasi / Approval -->
                        <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right;" data-label="Aksi Validasi">
                            <div class="table-actions" style="justify-content: flex-end;">
                                @if ($canUpdate)
                                    <button type="button" class="btn-icon btn-open-verifikasi"
                                        data-id="{{ $iz->id }}" data-nama="{{ $iz->siswa_nama }}"
                                        data-jenis="{{ ucfirst($iz->jenis) }}" data-status="{{ $iz->status }}"
                                        data-catatan="{{ $iz->catatan_petugas }}"
                                        data-rentang="{{ $sDate->format('d/m/Y') }} - {{ $eDate->format('d/m/Y') }}"
                                        title="Verifikasi Surat Izin" style="width: 34px; height: 34px; border-radius: var(--radius-md, 8px);">
                                        <i class="fas fa-signature" style="color: var(--primary);"></i>
                                    </button>
                                @else
                                    <span style="color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">&mdash;</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="cell-empty" style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                            <div style="font-size: var(--text-3xl, 1.875rem); opacity: 0.35; margin-bottom: 8px;">
                                <i class="fas fa-envelope-open-text"></i>
                            </div>
                            <div style="font-weight: 700; color: var(--text-color); margin-bottom: 4px;">
                                Belum Ada Permohonan Surat Izin / Sakit
                            </div>
                            <div style="font-size: var(--text-sm, 0.8125rem);">Tidak ada permohonan surat izin dari siswa kelas binaan untuk filter yang dipilih.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Footer Paginasi -->
    @if ($izinList->hasPages())
        <div class="datatable-footer">
            @include('partials.datatable-pagination', ['paginator' => $izinList])
        </div>
    @endif
</div>
