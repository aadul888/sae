@extends('layouts.dashboard')

@section('title', 'Wali Kelas — Peserta Didik Tidak Aktif — SAE')
@section('dash_title', 'Wali Kelas — Peserta Didik Tidak Aktif')

@section('content')
    <!-- Header Banner -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-user-xmark text-primary me-2"></i> Wali Kelas — Peserta Didik Tidak Aktif
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
                @if ($hasRombel && $activeRombel)
                    Kelas Binaan: <strong style="color: var(--text-color);">{{ $activeRombel->nama }}</strong>
                    &bull; Wali Kelas: <strong style="color: var(--primary);">{{ $waliNama }}</strong>
                    &bull; Riwayat peserta didik nonaktif (Alumni / Mutasi).
                @else
                    Direktori riwayat peserta didik tidak aktif kelas binaan wali kelas.
                @endif
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 10px; align-items: center;">
            @if ($isAdmin && $rombelList->isNotEmpty())
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="adminRombelSelect" style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted); margin: 0; white-space: nowrap;">
                        <i class="fas fa-filter me-1"></i> Pilih Rombel:
                    </label>
                    <select id="adminRombelSelect" class="form-control" style="font-size: 0.85rem; padding: 6px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color); min-width: 170px;">
                        @foreach ($rombelList as $r)
                            <option value="{{ $r->rombongan_belajar_id }}" {{ ($activeRombel?->rombongan_belajar_id === $r->rombongan_belajar_id) ? 'selected' : '' }}>
                                {{ $r->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    </div>

    @if (!$hasRombel)
        <div class="card" style="padding: 40px 20px; text-align: center; margin-top: 20px;">
            <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(245, 158, 11, 0.15); color: #f59e0b; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 16px;">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-color); margin-bottom: 8px;">Belum Ada Rombel Binaan Terdaftar</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem; max-width: 540px; margin: 0 auto 20px;">
                Akun Anda belum terikat dengan rombongan belajar aktif sebagai Wali Kelas pada data Dapodik sekolah. Silakan hubungi Administrator sistem atau Operator Dapodik untuk pembaruan penugasan.
            </p>
        </div>
    @else
        <!-- Summary Stats Grid -->
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                    <i class="fas fa-user-slash"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ number_format($summary['total'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Total Siswa Tidak Aktif</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ number_format($summary['alumni'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Alumni / Lulus</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ number_format($summary['mutasi'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Mutasi / Keluar</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                    <i class="fas fa-camera"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ number_format($summary['berfoto'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Arsip Pasfoto</div>
                </div>
            </div>
        </div>

        <!-- Toolbar & Filter -->
        <div class="card" style="padding: 16px; margin-bottom: 20px;">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">Tampilkan</label>
                        <select id="perPageSelect" class="per-page-select">
                            @foreach ([10, 15, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                        <span style="font-size: 0.85rem; color: var(--text-muted);">entri</span>
                    </div>

                    <select id="filterStatus" class="toolbar-filter-select" style="min-width: 140px;">
                        <option value="">Semua Status</option>
                        <option value="Alumni" {{ $status === 'Alumni' ? 'selected' : '' }}>Alumni / Lulus</option>
                        <option value="Mutasi" {{ $status === 'Mutasi' ? 'selected' : '' }}>Mutasi / Keluar</option>
                    </select>

                    @if ($filterTahun->isNotEmpty())
                        <select id="filterTahun" class="toolbar-filter-select" style="min-width: 130px;">
                            <option value="">Semua Tahun</option>
                            @foreach ($filterTahun as $th)
                                <option value="{{ $th }}" {{ $tahun === $th ? 'selected' : '' }}>Tahun {{ $th }}</option>
                            @endforeach
                        </select>
                    @endif

                    @if ($q || $status || $tahun)
                        <a href="{{ route('dashboard.wali-kelas.peserta-didik-tidak-aktif.index', $isAdmin ? ['rombel_id' => $activeRombel?->rombongan_belajar_id] : []) }}" class="btn btn-outline" style="padding: 7px 12px; font-size: 0.8rem;" title="Reset filter">
                            <i class="fas fa-undo me-1"></i> Reset
                        </a>
                    @endif
                </div>

                <!-- Live Search Box -->
                <div class="live-search-wrap">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearchInput" placeholder="Cari nama / NISN / NIK..." value="{{ $q }}" autocomplete="off">
                    <button type="button" id="clearSearchBtn" class="clear-search {{ $q ? 'visible' : '' }}" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Datatable Peserta Didik Tidak Aktif (Baku SAE: table-responsive-stack & table-pd) -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        @php
                            $cols = [
                                ['nama', 'Nama Lengkap'],
                                ['nisn', 'NISN / NIPD'],
                                ['jenis_kelamin', 'L/P'],
                                ['tahun_lulus', 'Status & Tahun'],
                            ];
                        @endphp
                        @foreach ($cols as [$key, $label])
                            <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; {{ $key === 'jenis_kelamin' ? 'text-align: center;' : '' }}"
                                data-sort="{{ $key }}">
                                {{ $label }}
                                <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                        @endforeach
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Alasan / Keterangan Keluar
                        </th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($list as $item)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <!-- Kolom Nama Lengkap & Foto -->
                            <td class="cell-pd-nama" style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: 0.88rem;" data-label="Nama Lengkap">
                                <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                    @if (!empty($item->foto_url))
                                        <div class="pd-foto-thumb" style="width: 42px; height: 42px; border-radius: 10px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25);">
                                            <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    @else
                                        <div class="pd-foto-thumb empty" style="width: 42px; height: 42px; border-radius: 10px; background: rgba(239,68,68,0.08); border: 1.5px dashed rgba(239,68,68,0.4); display: flex; flex-direction: column; align-items: center; justify-content: center; color: #ef4444; font-size: 0.8rem; flex-shrink: 0;">
                                            <i class="fas fa-user-slash" style="font-size: 0.85rem;"></i>
                                            <span style="font-size: 0.52rem; font-weight: 800; letter-spacing: 0.5px; margin-top: 2px;">ARSIP</span>
                                        </div>
                                    @endif
                                    <div class="pd-info">
                                        <div class="pd-title-row" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span class="pd-nama" style="font-weight: 700; color: var(--text-color);">{{ $item->nama }}</span>
                                            @if ($item->status_keluar === 'Alumni')
                                                <span class="badge" style="font-size: 0.62rem; background: rgba(16,185,129,0.12); color: #10b981; padding: 2px 6px; white-space: nowrap;">
                                                    <i class="fas fa-graduation-cap me-1"></i>Alumni
                                                </span>
                                            @else
                                                <span class="badge" style="font-size: 0.62rem; background: rgba(245,158,11,0.12); color: #f59e0b; padding: 2px 6px; white-space: nowrap;">
                                                    <i class="fas fa-right-from-bracket me-1"></i>{{ $item->status_keluar ?: 'Keluar' }}
                                                </span>
                                            @endif
                                        </div>
                                        @if ($item->nik)
                                            <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                                NIK: {{ $item->nik }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Kolom NISN / NIPD -->
                            <td class="cell-pd-nisn" style="padding: 14px 18px; font-family: monospace; font-size: 0.84rem; color: var(--primary);" data-label="NISN / NIPD">
                                <div>{{ $item->nisn ?: '-' }}</div>
                                @if ($item->nipd)
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">NIPD: {{ $item->nipd }}</div>
                                @endif
                            </td>

                            <!-- Kolom Gender L/P -->
                            <td class="cell-pd-gender" style="padding: 14px 18px; text-align: center;" data-label="L/P">
                                <span class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}" style="font-size: 0.72rem; padding: 2px 7px;">
                                    {{ $item->jenis_kelamin ?: '-' }}
                                </span>
                            </td>

                            <!-- Kolom Status & Tahun -->
                            <td class="cell-pd-ttl" style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);" data-label="Status &amp; Tahun">
                                <div style="font-weight: 600; color: var(--text-color);">
                                    {{ $item->status_keluar === 'Alumni' ? 'Lulus ' . ($item->tahun_lulus ?: '-') : ($item->status_keluar ?: 'Keluar') }}
                                </div>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">
                                    {{ $item->tanggal_keluar ? date('d/m/Y', strtotime($item->tanggal_keluar)) : '-' }}
                                </div>
                            </td>

                            <!-- Kolom Alasan Keluar & Kontak -->
                            <td class="cell-pd-kontak" style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);" data-label="Alasan Keluar">
                                <div style="color: var(--text-color); font-weight: 500; max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $item->alasan_keluar ?: '-' }}">
                                    {{ $item->alasan_keluar ?: '-' }}
                                </div>
                                @if ($item->nomor_telepon_seluler)
                                    <div style="font-size: 0.75rem; margin-top: 2px;">
                                        <i class="fas fa-phone-alt me-1" style="font-size: 0.7rem;"></i> {{ $item->nomor_telepon_seluler }}
                                    </div>
                                @endif
                            </td>

                            <!-- Kolom Aksi -->
                            <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                <div class="table-actions">
                                    <button type="button" class="btn-icon btn-detail-tidak-aktif"
                                        onclick="openBiodataModal('{{ $item->id }}')"
                                        data-id="{{ $item->id }}" title="Lihat Detail Riwayat">
                                        <i class="fas fa-id-card"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                <i class="fas fa-user-check mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                                <div>Tidak ada data peserta didik tidak aktif di kelas binaan ini.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Footer Keterangan Entri -->
            @if ($total > 0)
                <div style="padding: 14px 18px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div class="total-count-text" style="font-size: 0.83rem; color: var(--text-muted);">
                        Menampilkan <strong>{{ $list->firstItem() ?: 0 }}</strong> sampai <strong>{{ $list->lastItem() ?: 0 }}</strong> dari <strong>{{ number_format($total, 0, ',', '.') }}</strong> siswa
                    </div>
                </div>
            @endif
        </div>

        <!-- Custom Pagination Resmi SAE -->
        @if ($list->hasPages())
            <div class="custom-pagination">
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
                    @if ($from > 2)
                        <span class="page-info">&hellip;</span>
                    @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $list->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1)
                        <span class="page-info">&hellip;</span>
                    @endif
                    <a href="{{ $list->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($list->hasMorePages())
                    <a href="{{ $list->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif
    @endif

    {{-- Modal Biodata Peserta Didik Tidak Aktif / Alumni --}}
    <div id="biodataModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 720px; width: 94%; max-height: 85vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); border: 1px solid var(--border-color);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div id="bioFotoContainer"
                        style="width: 50px; height: 50px; border-radius: 12px; background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1.5px solid var(--border-color); flex-shrink: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.25);">
                        <i class="fas fa-user-graduate text-primary" style="font-size: 1.3rem;"></i>
                    </div>
                    <div>
                        <h3 id="bioNama"
                            style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                            Biodata Arsip Siswa</h3>
                        <div id="bioRombel" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">-</div>
                    </div>
                </div>
                <button type="button" onclick="closeBiodataModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="bioLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: 1.4rem;"></i>
                <div>Memuat rincian arsip siswa...</div>
            </div>

            <div id="bioContent" style="overflow-y: auto; flex: 1; display: none; font-size: 0.84rem;">
                <div style="margin-bottom: 12px;">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-user me-1"></i> Data Pribadi Siswa
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">NISN / NIPD</td>
                            <td id="bioNisn" style="font-weight: 600; font-family: monospace; color: var(--primary);">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">NIK</td>
                            <td id="bioNik" style="font-family: monospace;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Jenis Kelamin</td>
                            <td id="bioJk">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tempat, Tgl Lahir</td>
                            <td id="bioTtl">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Agama</td>
                            <td id="bioAgama">-</td>
                        </tr>
                    </table>
                </div>

                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-graduation-cap me-1"></i> Status Pengarsipan &amp; Akademik
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">Status Keluar</td>
                            <td id="bioStatus">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tahun Lulus / Keluar</td>
                            <td id="bioTahunLulus">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Rombel Terakhir</td>
                            <td id="bioRombelDetail">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Keterangan / Alasan</td>
                            <td id="bioAlasan">-</td>
                        </tr>
                    </table>
                </div>

                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-address-book me-1"></i> Kontak &amp; Domisili
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">No. HP / Telepon</td>
                            <td id="bioHp">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Email</td>
                            <td id="bioEmail">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Alamat Jalan</td>
                            <td id="bioAlamat">-</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div
                style="padding-top: 14px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
                <button type="button" onclick="closeBiodataModal()" class="btn btn-outline"
                    style="padding: 8px 18px; font-size: 0.85rem;">
                    Tutup
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/wali-kelas-tidak-aktif.js') }}?v={{ file_exists(public_path('js/wali-kelas-tidak-aktif.js')) ? filemtime(public_path('js/wali-kelas-tidak-aktif.js')) : time() }}"></script>
@endpush
