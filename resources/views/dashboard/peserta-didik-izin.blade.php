@extends('layouts.dashboard')

@section('title', 'Surat Izin & Sakit Peserta Didik — SAE')
@section('dash_title', 'Surat Izin & Sakit')

@section('content')
    <!-- 1. Header Banner & Action Buttons (Sesuai Standar Modul SAE) -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-envelope-open-text text-primary me-2"></i> Surat &amp; e-Izin Peserta Didik
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Permohonan surat izin tidak masuk sekolah serta e-Izin keluar-masuk / pulang cepat gerbang sekolah.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center;">
            <a href="{{ route('dashboard.peserta-didik.presensi.index') }}" class="btn btn-outline"
                style="padding: 8px 14px; font-size: 0.9rem;" title="Lihat Riwayat Presensi Lengkap">
                <i class="fas fa-calendar-check"></i>
            </a>
            @if ($tab === 'surat' && $canCreate)
                <button type="button" class="btn btn-primary" id="btnBukaModalIzin"
                    style="padding: 8px 14px; font-size: 0.9rem; font-weight: 700;" title="Ajukan Surat Izin / Sakit Baru">
                    <i class="fas fa-plus"></i> Ajukan Surat Izin
                </button>
            @elseif ($tab === 'keluar')
                <button type="button" class="btn btn-primary" onclick="openModalIzinKeluar()"
                    style="padding: 8px 14px; font-size: 0.9rem; font-weight: 700;" title="Ajukan Izin Keluar Gerbang">
                    <i class="fas fa-ticket-alt"></i> Ajukan Izin Keluar
                </button>
            @endif
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div style="display: flex; gap: 8px; border-bottom: 2px solid var(--border-color); margin-bottom: 20px;">
        <a href="{{ route('dashboard.peserta-didik.izin.index', ['tab' => 'surat']) }}"
           style="padding: 10px 18px; font-weight: 700; font-size: 0.9rem; text-decoration: none; border-bottom: 2px solid {{ $tab === 'surat' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'surat' ? 'var(--primary)' : 'var(--text-muted)' }}; margin-bottom: -2px;">
            <i class="fas fa-file-medical"></i> Surat Izin / Sakit (Tidak Masuk)
        </a>
        <a href="{{ route('dashboard.peserta-didik.izin.index', ['tab' => 'keluar']) }}"
           style="padding: 10px 18px; font-weight: 700; font-size: 0.9rem; text-decoration: none; border-bottom: 2px solid {{ $tab === 'keluar' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'keluar' ? 'var(--primary)' : 'var(--text-muted)' }}; margin-bottom: -2px;">
            <i class="fas fa-qrcode"></i> Tiket e-Izin Keluar-Masuk Gerbang
            @if (isset($statKeluarAktif) && $statKeluarAktif > 0)
                <span class="badge" style="background: #ef4444; color: #fff; margin-left: 4px; font-size: 0.72rem;">{{ $statKeluarAktif }}</span>
            @endif
        </a>
    </div>

    @if ($tab === 'surat')
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-folder-open"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($statTotal, 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Total Pengajuan</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($statMenunggu, 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Menunggu Validasi</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($statDisetujui, 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Telah Disetujui</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                <i class="fas fa-circle-xmark"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($statDitolak, 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Permohonan Ditolak</div>
            </div>
        </div>
    </div>

    <!-- 3. Toolbar & Filter (Konsisten 100% dengan Modul Peserta Didik Aktif) -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <form id="filterIzinForm" method="GET" action="{{ route('dashboard.peserta-didik.izin.index') }}">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                
                <!-- Kiri: Filter Controls -->
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <!-- Dropdown Paging Baris -->
                    <div class="toolbar-entries">
                        <label for="filterPerPage" style="margin: 0;">Tampilkan</label>
                        <select name="per_page" id="filterPerPage" class="per-page-select">
                            @foreach ([10, 15, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    <!-- Filter Jenis Permohonan -->
                    <select name="jenis" id="filterJenis" class="toolbar-filter-select">
                        <option value="">Semua Jenis</option>
                        <option value="izin" {{ request('jenis') === 'izin' ? 'selected' : '' }}>Izin</option>
                        <option value="sakit" {{ request('jenis') === 'sakit' ? 'selected' : '' }}>Sakit</option>
                        <option value="dispen" {{ request('jenis') === 'dispen' ? 'selected' : '' }}>Dispensasi</option>
                    </select>

                    <!-- Filter Status Validasi -->
                    <select name="status" id="filterStatus" class="toolbar-filter-select">
                        <option value="">Semua Status</option>
                        <option value="menunggu" {{ request('status') === 'menunggu' ? 'selected' : '' }}>Menunggu Validasi</option>
                        <option value="disetujui" {{ request('status') === 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                        <option value="ditolak" {{ request('status') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                    </select>

                    @if (request('q') || request('jenis') || request('status'))
                        <a href="{{ route('dashboard.peserta-didik.izin.index') }}"
                            class="btn btn-outline" style="padding: 7px 12px; font-size: 0.84rem;" title="Reset filter">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <!-- Kanan: Live Search Box Baku SAE -->
                <div class="live-search-wrap">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="q" id="inputSearchIzin" placeholder="Cari alasan atau catatan..." value="{{ request('q') }}" autocomplete="off">
                    <button type="button" id="btnClearSearch" class="clear-search {{ request('q') ? 'visible' : '' }}" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- 4. Datatable Responsif (.table-responsive-stack dengan data-label) -->
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">
                        Jenis
                    </th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Rentang Waktu
                    </th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Alasan &amp; Bukti
                    </th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 160px;">
                        Status Validasi
                    </th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">
                        Diajukan Pada
                    </th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 90px;">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $item)
                    @php
                        $startDate = \Carbon\Carbon::parse($item->tanggal_mulai);
                        $endDate = \Carbon\Carbon::parse($item->tanggal_selesai);
                        $durasiHari = $startDate->diffInDays($endDate) + 1;
                    @endphp
                    <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                        <!-- Kolom Jenis -->
                        <td class="cell-pd-jenis" style="padding: 14px 18px; vertical-align: middle;" data-label="Jenis">
                            @if ($item->jenis === 'sakit')
                                <span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px;">
                                    <i class="fas fa-notes-medical me-1"></i> Sakit
                                </span>
                            @elseif ($item->jenis === 'dispen')
                                <span class="badge" style="background: rgba(168,85,247,0.15); color: #a855f7; border: 1px solid rgba(168,85,247,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px;">
                                    <i class="fas fa-award me-1"></i> Dispensasi
                                </span>
                            @else
                                <span class="badge" style="background: rgba(59,130,246,0.15); color: #3b82f6; border: 1px solid rgba(59,130,246,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px;">
                                    <i class="fas fa-file-lines me-1"></i> Izin
                                </span>
                            @endif
                        </td>

                        <!-- Kolom Rentang Waktu -->
                        <td class="cell-pd-tanggal" style="padding: 14px 18px; vertical-align: middle;" data-label="Rentang Waktu">
                            <div style="font-weight: 700; color: var(--text-color); font-size: 0.88rem;">
                                {{ $startDate->translatedFormat('d M Y') }}
                                @if ($item->tanggal_mulai !== $item->tanggal_selesai)
                                    <span style="color: var(--text-muted); font-weight: normal;"> s/d </span>
                                    {{ $endDate->translatedFormat('d M Y') }}
                                @endif
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                                <i class="fas fa-clock text-primary me-1"></i> Durasi: <strong style="color: var(--text-color);">{{ $durasiHari }} hari</strong>
                            </div>
                        </td>

                        <!-- Kolom Alasan & Bukti -->
                        <td class="cell-pd-alasan" style="padding: 14px 18px; vertical-align: middle;" data-label="Alasan & Bukti">
                            <div style="font-size: 0.86rem; color: var(--text-color); font-weight: 500; line-height: 1.4; margin-bottom: 4px;">
                                {{ $item->alasan }}
                            </div>
                            @if (!empty($item->lampiran_path))
                                <div>
                                    <button type="button" class="btn btn-outline btn-sm btn-preview-lampiran"
                                        data-url="{{ asset('storage/' . ltrim($item->lampiran_path, '/')) }}"
                                        data-title="Lampiran Surat {{ $item->jenis_label }}"
                                        style="font-size: 0.74rem; padding: 3px 8px; border-radius: 6px;">
                                        <i class="fas fa-paperclip me-1 text-primary"></i> Lihat Berkas Lampiran
                                    </button>
                                </div>
                            @else
                                <span style="font-size: 0.74rem; color: var(--text-muted); font-style: italic;">
                                    Tanpa lampiran berkas
                                </span>
                            @endif
                        </td>

                        <!-- Kolom Status Validasi -->
                        <td class="cell-pd-status" style="padding: 14px 18px; vertical-align: middle;" data-label="Status Validasi">
                            @if ($item->status === 'disetujui')
                                <span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 8px; border-radius: 6px;">
                                    <i class="fas fa-check-circle me-1"></i> Disetujui
                                </span>
                                @if ($item->disetujui_oleh)
                                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px;">
                                        Oleh: {{ $item->disetujui_oleh }}
                                    </div>
                                @endif
                            @elseif ($item->status === 'ditolak')
                                <span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 8px; border-radius: 6px;">
                                    <i class="fas fa-times-circle me-1"></i> Ditolak
                                </span>
                                @if ($item->catatan_petugas)
                                    <div style="font-size: 0.72rem; color: #ef4444; margin-top: 3px;">
                                        Catatan: {{ $item->catatan_petugas }}
                                    </div>
                                @endif
                            @else
                                <span class="badge" style="background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 8px; border-radius: 6px;">
                                    <i class="fas fa-hourglass-half me-1"></i> Menunggu
                                </span>
                                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px;">
                                    Menunggu verifikasi
                                </div>
                            @endif
                        </td>

                        <!-- Kolom Tanggal Diajukan -->
                        <td class="cell-pd-created" style="padding: 14px 18px; vertical-align: middle; font-size: 0.82rem; color: var(--text-muted);" data-label="Diajukan Pada">
                            {{ \Carbon\Carbon::parse($item->created_at)->translatedFormat('d/m/Y') }}
                            <div style="font-size: 0.72rem;">
                                {{ \Carbon\Carbon::parse($item->created_at)->format('H:i') }} WIB
                            </div>
                        </td>

                        <!-- Kolom Aksi -->
                        <td class="cell-pd-aksi" style="padding: 14px 18px; vertical-align: middle; text-align: right;" data-label="Aksi">
                            <div class="table-actions" style="justify-content: flex-end;">
                                @if ($item->status === 'menunggu' && $canDelete)
                                    <button type="button" class="btn-icon btn-cancel-izin"
                                        data-id="{{ $item->id }}"
                                        data-jenis="{{ $item->jenis_label }}"
                                        title="Batalkan Pengajuan Ini"
                                        style="color: #ef4444; border-color: rgba(239,68,68,0.25);">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                @else
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">&mdash;</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                            <div style="font-size: 2.4rem; opacity: 0.35; margin-bottom: 10px;">
                                <i class="fas fa-envelope-open-text"></i>
                            </div>
                            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color); margin-bottom: 4px;">
                                Belum Ada Permohonan Surat Izin / Sakit
                            </div>
                            <div style="font-size: 0.8rem; max-width: 420px; margin: auto;">
                                Klik tombol <strong>+ Ajukan Permohonan</strong> di bagian atas untuk mengajukan izin atau sakit kepada pihak sekolah.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- 5. Custom Pagination Resmi SAE (Dilarang Memakai $list->links() Mentah) -->
    @if ($list->hasPages())
        <div class="custom-pagination" style="margin-bottom: 24px;">
            @if ($list->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $list->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
            @endif

            @php
                $cur = $list->currentPage();
                $last = $list->lastPage();
                $from = max(1, $cur - 2);
                $to = min($last, $cur + 2);
            @endphp

            @if ($from > 1)
                <a href="{{ $list->url(1) }}" class="page-btn">1</a>
                @if ($from > 2) <span class="page-info">&hellip;</span> @endif
            @endif

            @for ($i = $from; $i <= $to; $i++)
                <a href="{{ $list->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor

            @if ($to < $last)
                @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                <a href="{{ $list->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif

            @if ($list->hasMorePages())
                <a href="{{ $list->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif
    @else
        {{-- TAB 2: e-Izin Keluar-Masuk Gerbang --}}
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No Tiket</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jenis Izin</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Alasan</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Waktu Keluar / Kembali</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Petugas Piket</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status Gerbang</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Slip / Tiket</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($listKeluar as $item)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 18px;">
                                <span style="font-weight: 700; font-family: monospace; color: var(--primary); font-size: 0.9rem;">{{ $item->nomor_tiket }}</span>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}</div>
                            </td>
                            <td style="padding: 12px 18px;">
                                @if ($item->jenis_izin === 'keluar_sebentar')
                                    <span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);">Keluar Sebentar</span>
                                @elseif ($item->jenis_izin === 'pulang_cepat')
                                    <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Pulang Cepat</span>
                                @else
                                    <span class="badge" style="background: rgba(107, 114, 128, 0.15); color: #6b7280;">{{ ucfirst($item->jenis_izin) }}</span>
                                @endif
                            </td>
                            <td style="padding: 12px 18px; max-width: 220px;">
                                <div style="font-size: 0.85rem; color: var(--text-heading); white-space: normal;">{{ $item->alasan }}</div>
                            </td>
                            <td style="padding: 12px 18px; font-size: 0.82rem;">
                                <div>Keluar: <strong>{{ substr($item->jam_izin_keluar, 0, 5) }}</strong></div>
                                @if ($item->jam_rencana_kembali)
                                    <div style="color: var(--text-muted);">Rencana: {{ substr($item->jam_rencana_kembali, 0, 5) }}</div>
                                @endif
                                @if ($item->jam_kembali_aktual)
                                    <div style="color: #10b981; font-weight: 600;">Aktual: {{ substr($item->jam_kembali_aktual, 0, 5) }}</div>
                                @endif
                            </td>
                            <td style="padding: 12px 18px; font-size: 0.82rem;">
                                {{ $item->nama_piket ?: ($item->created_by ?: 'Guru Piket') }}
                            </td>
                            <td style="padding: 12px 18px;">
                                @if ($item->status === 'menunggu_satpam')
                                    <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">Menunggu Gerbang</span>
                                @elseif ($item->status === 'di_luar')
                                    <span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);">Di Luar</span>
                                @elseif ($item->status === 'kembali')
                                    <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">Sudah Kembali</span>
                                @elseif ($item->status === 'pulang_selesai')
                                    <span class="badge" style="background: rgba(99, 102, 241, 0.15); color: #6366f1; border: 1px solid rgba(99, 102, 241, 0.3);">Pulang Selesai</span>
                                @else
                                    <span class="badge" style="background: rgba(107, 114, 128, 0.15); color: #6b7280;">{{ ucfirst($item->status) }}</span>
                                @endif
                            </td>
                            <td style="padding: 12px 18px; text-align: center;">
                                <a href="{{ route('dashboard.piket.izin.cetak', $item->id) }}" target="_blank" class="btn btn-secondary btn-sm" title="Lihat Slip Izin" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; font-size: 0.78rem;">
                                    <i class="fas fa-qrcode"></i> Tiket
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 36px; color: var(--text-muted);">
                                <i class="fas fa-ticket-alt" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.4;"></i>
                                <p style="margin: 0;">Belum ada pengajuan izin keluar gerbang.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($listKeluar->hasPages())
            <div class="custom-pagination">
                @if ($listKeluar->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $listKeluar->previousPageUrl() }}" class="page-btn"><i class="fas fa-chevron-left"></i></a>
                @endif
                @for ($i = 1; $i <= $listKeluar->lastPage(); $i++)
                    <a href="{{ $listKeluar->url($i) }}" class="page-btn {{ $i === $listKeluar->currentPage() ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($listKeluar->hasMorePages())
                    <a href="{{ $listKeluar->nextPageUrl() }}" class="page-btn"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif
    @endif

    <!-- Modal Form Pengajuan e-Izin Keluar-Masuk Siswa -->
    <div id="modalFormIzinKeluar" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.68); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 500px; width: 92%; margin: auto; padding: 24px; border-radius: 16px; border: 1px solid var(--border-color);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 style="font-size: 1.12rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    <i class="fas fa-ticket-alt text-primary"></i> Ajukan e-Izin Keluar Sekolah
                </h3>
                <button type="button" onclick="closeModalIzinKeluar()" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.25rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('dashboard.peserta-didik.izin.keluar.store') }}">
                @csrf
                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 6px;">Jenis Izin Keluar: <span style="color:red;">*</span></label>
                    <select name="jenis_izin" required class="form-control" style="width: 100%;">
                        <option value="keluar_sebentar">Keluar Sebentar (Kembali Lagi ke Sekolah)</option>
                        <option value="pulang_cepat">Pulang Cepat (Sakit / Keperluan Mendesak)</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 6px;">Jam Keluar: <span style="color:red;">*</span></label>
                        <input type="time" name="jam_izin_keluar" value="{{ date('H:i') }}" required class="form-control" style="width: 100%;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 6px;">Rencana Kembali:</label>
                        <input type="time" name="jam_rencana_kembali" class="form-control" style="width: 100%;">
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 6px;">Alasan / Keperluan: <span style="color:red;">*</span></label>
                    <textarea name="alasan" rows="3" required placeholder="Jelaskan alasan izin Anda..." class="form-control" style="width: 100%;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-outline" onclick="closeModalIzinKeluar()">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Ajukan Izin</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 6. Modal Form Pengajuan Surat Izin (z-index: 99999 !important) -->
    <div id="modalFormIzin" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.68); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 520px; width: 92%; margin: auto; padding: 24px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 16px 40px rgba(0,0,0,0.5);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 style="font-size: 1.12rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-paper-plane text-primary"></i> Ajukan Surat Izin / Sakit
                </h3>
                <button type="button" id="btnCloseModalIzin" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.25rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formSubmitIzin" method="POST" action="{{ route('dashboard.peserta-didik.izin.store') }}" enctype="multipart/form-data">
                @csrf

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                        Jenis Permohonan: <span style="color: #ef4444;">*</span>
                    </label>
                    <select name="jenis" id="modalSelectJenis" required class="form-control" style="width: 100%; font-size: 0.86rem; padding: 9px 12px;">
                        <option value="izin">Izin (Keperluan Keluarga / Khusus)</option>
                        <option value="sakit">Sakit (Wajib Lampirkan Surat Dokter)</option>
                        <option value="dispen">Dispensasi (Lomba / Agenda Sekolah)</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                            Mulai Tanggal: <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="date" name="tanggal_mulai" id="modalTglMulai" value="{{ now()->toDateString() }}" required class="form-control"
                            style="width: 100%; font-size: 0.85rem; padding: 8px 10px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                            Sampai Tanggal: <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="date" name="tanggal_selesai" id="modalTglSelesai" value="{{ now()->toDateString() }}" required class="form-control"
                            style="width: 100%; font-size: 0.85rem; padding: 8px 10px;">
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                        Alasan / Keterangan Lengkap: <span style="color: #ef4444;">*</span>
                    </label>
                    <textarea name="alasan" id="modalAlasan" rows="3" required placeholder="Tuliskan keterangan detail alasan izin atau kondisi sakit Anda..." class="form-control"
                        style="width: 100%; font-size: 0.85rem; padding: 9px 12px; line-height: 1.4;"></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                        Berkas Surat / Surat Keterangan Dokter:
                    </label>
                    <input type="file" name="lampiran" id="modalLampiran" accept=".jpg,.jpeg,.png,.pdf" class="form-control"
                        style="width: 100%; font-size: 0.82rem; padding: 8px;">
                    <small style="color: var(--text-muted); font-size: 0.73rem; display: block; margin-top: 5px;">
                        Format berkas: JPG, PNG, atau PDF (Maksimal 4 MB).
                    </small>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" id="btnBatalModalIzin" class="btn btn-outline" style="padding: 8px 18px; font-size: 0.85rem; border-radius: 8px;">
                        Batal
                    </button>
                    <button type="submit" id="btnSubmitIzin" class="btn btn-primary" style="padding: 8px 22px; font-size: 0.85rem; font-weight: 700; border-radius: 8px;">
                        <i class="fas fa-paper-plane me-1"></i> Kirim Permohonan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 7. Modal Preview Berkas Lampiran (z-index: 99999 !important) -->
    <div id="modalPreviewLampiran" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.72); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 680px; width: 92%; max-height: 90vh; margin: auto; padding: 20px; border-radius: 16px; display: flex; flex-direction: column; border: 1px solid var(--border-color); box-shadow: 0 16px 40px rgba(0,0,0,0.5);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <h4 id="previewLampiranTitle" style="font-size: 0.98rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Berkas Lampiran
                </h4>
                <button type="button" id="btnClosePreviewLampiran" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.25rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="previewContainer" style="flex: 1; overflow: auto; text-align: center; min-height: 240px; display: flex; align-items: center; justify-content: center;">
                <!-- Konten dinamis (gambar atau iframe PDF) -->
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/peserta-didik-izin.js') }}?v={{ file_exists(public_path('js/peserta-didik-izin.js')) ? filemtime(public_path('js/peserta-didik-izin.js')) : time() }}"></script>
@endpush
