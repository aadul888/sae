@extends('layouts.dashboard')

@section('title', 'Wali Kelas — Peserta Didik Aktif — SAE')
@section('dash_title', 'Wali Kelas — Peserta Didik Aktif')

@section('content')
    <!-- Header Banner -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-chalkboard-user text-primary me-2"></i> Wali Kelas — Peserta Didik Aktif
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
                @if ($hasRombel && $activeRombel)
                    Kelas Binaan: <strong style="color: var(--text-color);">{{ $activeRombel->nama }}</strong>
                    &bull; Wali Kelas: <strong style="color: var(--primary);">{{ $waliNama }}</strong>
                    @if (!empty($activeRombel->jurusan_id_str))
                        &bull; Jurusan: {{ $activeRombel->jurusan_id_str }}
                    @endif
                @else
                    Modul pendampingan kelas binaan wali kelas dan direktori peserta didik aktif.
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
        <!-- Empty State jika Guru belum ada tugas rombel -->
        <div class="card" style="padding: 40px 20px; text-align: center; margin-top: 20px;">
            <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(245, 158, 11, 0.15); color: #f59e0b; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 16px;">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-color); margin-bottom: 8px;">Belum Ada Rombel Binaan Terdaftar</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem; max-width: 540px; margin: 0 auto 20px;">
                Akun Anda belum terikat dengan rombongan belajar aktif sebagai Wali Kelas pada data Dapodik sekolah. Silakan hubungi Administrator sistem atau Operator Dapodik untuk pembaruan penugasan tugas tambahan.
            </p>
        </div>
    @else
        <!-- Summary Stats Grid -->
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ number_format($summary['total'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Total Peserta Didik di Kelas</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                    <i class="fas fa-mars"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ number_format($summary['laki'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Laki-Laki</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(236,72,153,0.15); color: #ec4899;">
                    <i class="fas fa-venus"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ number_format($summary['perempuan'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Perempuan</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                    <i class="fas fa-camera"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ number_format($summary['berfoto'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Memiliki Pasfoto</div>
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

                    <select id="filterGender" class="toolbar-filter-select" style="min-width: 130px;">
                        <option value="">Semua Gender</option>
                        <option value="L" {{ $gender === 'L' ? 'selected' : '' }}>Laki-Laki (L)</option>
                        <option value="P" {{ $gender === 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                    </select>

                    @if ($q || $gender)
                        <a href="{{ route('dashboard.wali-kelas.peserta-didik-aktif.index', $isAdmin ? ['rombel_id' => $activeRombel?->rombongan_belajar_id] : []) }}" class="btn btn-outline btn-responsive-icon" style="padding: 7px 12px; font-size: 0.8rem;" title="Reset filter">
                            <i class="fas fa-undo"></i> <span class="btn-responsive-text">Reset</span>
                        </a>
                    @endif

                    @if ($activeRombel)
                        <a href="{{ url('/dashboard/kartu-pelajar/cetak-rombel/' . urlencode($activeRombel->rombongan_belajar_id ?? $activeRombel->nama)) }}"
                            target="_blank" class="btn btn-outline btn-responsive-icon"
                            style="padding: 7px 14px; font-size: 0.82rem; border-color: #0284c7; color: #0284c7;"
                            title="Cetak Seluruh Kartu Pelajar Rombel {{ $activeRombel->nama }}">
                            <i class="fas fa-id-card"></i> <span class="btn-responsive-text">Cetak Kartu Masal ({{ $activeRombel->nama }})</span>
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

        <!-- Datatable Peserta Didik (Baku SAE: table-responsive-stack & table-pd) -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        @php
                            $cols = [
                                ['nama', 'Nama Lengkap'],
                                ['nisn', 'NISN / NIPD'],
                                ['jenis_kelamin', 'L/P'],
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
                            TTL
                        </th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Kontak / Ortu
                        </th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($list as $item)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <!-- Kolom Nama & Avatar -->
                            <td class="cell-pd-nama" style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: 0.88rem;" data-label="Nama">
                                <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                    @if (!empty($item->foto_url))
                                        <div class="pd-foto-thumb" style="width: 42px; height: 42px; border-radius: 10px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25);">
                                            <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    @else
                                        <div class="pd-foto-thumb empty" style="width: 42px; height: 42px; border-radius: 10px; background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.05rem; flex-shrink: 0;">
                                            <i class="fas fa-user-graduate" style="opacity: 0.7;"></i>
                                        </div>
                                    @endif
                                    <div class="pd-info">
                                        <div class="pd-title-row" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span class="pd-nama" style="font-weight: 700; color: var(--text-color);">{{ $item->nama }}</span>
                                        </div>
                                        @if ($item->nik)
                                            <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                                <span class="copyable" data-copy="{{ $item->nik }}" data-label="NIK" title="Klik untuk salin NIK">
                                                    NIK: {{ $item->nik }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Kolom NISN / NIPD -->
                            <td class="cell-pd-nisn" style="padding: 14px 18px; font-family: monospace; font-size: 0.84rem; color: var(--primary);" data-label="NISN/NIPD">
                                <div class="cell-col-right">
                                    <div>
                                        @if ($item->nisn)
                                            <span class="copyable" data-copy="{{ $item->nisn }}" data-label="NISN" title="Klik untuk salin NISN">{{ $item->nisn }}</span>
                                        @else
                                            -
                                        @endif
                                    </div>
                                    @if ($item->nipd)
                                        <div style="font-size: 0.72rem; color: var(--text-muted);">
                                            <span class="copyable" data-copy="{{ $item->nipd }}" data-label="NIPD" title="Klik untuk salin NIPD">NIPD: {{ $item->nipd }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Kolom Gender L/P -->
                            <td class="cell-pd-gender" style="padding: 14px 18px; text-align: center;" data-label="L/P">
                                <span class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}" style="font-size: 0.72rem; padding: 2px 7px;">
                                    {{ $item->jenis_kelamin ?: '-' }}
                                </span>
                            </td>

                            <!-- Kolom Tempat, Tanggal Lahir -->
                            <td class="cell-pd-ttl" style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);" data-label="TTL">
                                <div class="cell-col-right" style="color: var(--text-muted);">
                                    {{ $item->tempat_lahir ? $item->tempat_lahir . ', ' : '' }}{{ $item->tanggal_lahir ? date('d/m/Y', strtotime($item->tanggal_lahir)) : '-' }}
                                </div>
                            </td>

                            <!-- Kolom Kontak / Orang Tua -->
                            <td class="cell-pd-kontak" style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);" data-label="Kontak">
                                <div class="cell-col-right">
                                    <div style="color: var(--text-color); font-weight: 600;">{{ $item->nama_ayah ?: ($item->nama_ibu ?: ($item->nama_wali ?: '-')) }}</div>
                                    @if ($item->no_hp)
                                        <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">
                                            <i class="fas fa-phone-alt me-1" style="font-size: 0.68rem;"></i>
                                            <span class="copyable" data-copy="{{ $item->no_hp }}" data-label="No HP" title="Klik untuk salin No HP">{{ $item->no_hp }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Kolom Aksi -->
                            <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                <div class="table-actions">
                                    <button type="button" class="btn-icon btn-detail-siswa" title="Lihat Biodata Lengkap"
                                        onclick="openBiodataPesertaDidikModal('{{ $item->peserta_didik_id }}')"
                                        data-id="{{ $item->peserta_didik_id }}">
                                        <i class="fas fa-id-card"></i>
                                    </button>
                                    @if (!empty($item->nisn))
                                    <button type="button" class="btn-icon" title="Pratinjau / Cetak Kartu Pelajar Digital"
                                        onclick="openKartuPelajarModal('{{ $item->nisn }}')">
                                        <i class="fas fa-address-card" style="color: #0284c7;"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                <i class="fas fa-user-slash mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                                <div>Tidak ada data peserta didik aktif yang sesuai dengan kriteria pencarian di kelas ini.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Footer Keterangan Entri -->
            @if ($total > 0)
                <div style="padding: 14px 18px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div class="total-count-text" style="font-size: 0.83rem; color: var(--text-muted);">
                        Menampilkan <strong>{{ $list->firstItem() ?: 0 }}</strong> sampai <strong>{{ $list->lastItem() ?: 0 }}</strong> dari <strong>{{ number_format($total, 0, ',', '.') }}</strong> peserta didik
                    </div>
                </div>
            @endif
        </div>

        <!-- Custom Pagination Resmi SAE (Bebas dari links() Mentah) -->
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

    {{-- Modal Biodata Peserta Didik Lengkap (Standar Baku Lengkap SAE seperti di Admin) --}}
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
                        <h3 id="bioNama" style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                            Biodata Peserta Didik</h3>
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
                <div>Memuat biodata peserta didik...</div>
            </div>

            <div id="bioContent" style="overflow-y: auto; flex: 1; display: none; font-size: 0.84rem;">
                <!-- 1. Data Pribadi & Fisik -->
                <div style="margin-bottom: 12px;">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-user me-1"></i> Data Pribadi &amp; Fisik
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
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Anak Keberapa</td>
                            <td id="bioAnak">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tinggi / Berat Badan</td>
                            <td id="bioFisik" style="font-weight: 600; color: #10b981;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Kebutuhan Khusus</td>
                            <td id="bioKhusus">-</td>
                        </tr>
                    </table>
                </div>

                <!-- 2. Data Akademik & Pendaftaran -->
                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-graduation-cap me-1"></i> Data Akademik &amp; Pendaftaran
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">Pendaftaran / Asal</td>
                            <td id="bioPendaftaran">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tanggal Masuk</td>
                            <td id="bioTglMasuk">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">ID Registrasi / Anggota</td>
                            <td id="bioRegId"
                                style="font-family: monospace; font-size: 0.78rem; color: var(--text-muted);">-</td>
                        </tr>
                    </table>
                </div>

                <!-- 3. Data Orang Tua & Wali -->
                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-users me-1"></i> Data Orang Tua &amp; Wali
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">Nama Ayah</td>
                            <td id="bioAyah">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Nama Ibu</td>
                            <td id="bioIbu">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Nama Wali</td>
                            <td id="bioWali">-</td>
                        </tr>
                    </table>
                </div>

                <!-- 4. Kontak & Domisili -->
                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-address-book me-1"></i> Kontak &amp; Domisili
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">Kontak (HP/Email)</td>
                            <td id="bioHp">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Alamat Jalan</td>
                            <td id="bioAlamat">-</td>
                        </tr>
                    </table>
                </div>

                <!-- 5. Mata Pelajaran di Kelas -->
                <div id="bioMapelSection"
                    style="margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color); display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: 700; color: var(--text-color); font-size: 0.85rem;"><i
                                class="fas fa-book-open text-primary me-1"></i> Mata Pelajaran di Kelas</span>
                        <span id="bioJmlMapel" class="badge"
                            style="background: rgba(99,102,241,0.15); color: var(--primary); font-size: 0.72rem; padding: 2px 7px;">0
                            Mapel</span>
                    </div>
                    <div style="overflow-x: auto; max-height: 180px;">
                        <table class="table"
                            style="width: 100%; border-collapse: collapse; font-size: 0.80rem; margin-bottom: 0;">
                            <thead>
                                <tr
                                    style="background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border-color);">
                                    <th style="padding: 6px 10px; color: var(--text-muted);">Mata Pelajaran</th>
                                    <th style="padding: 6px 10px; color: var(--text-muted);">Guru Pengampu</th>
                                    <th style="padding: 6px 10px; color: var(--text-muted); text-align: center;">Jam</th>
                                </tr>
                            </thead>
                            <tbody id="bioMapelList"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div
                style="display: flex; justify-content: flex-end; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-outline" onclick="closeBiodataModal()"
                    style="padding: 8px 18px; font-size: 0.82rem;">Tutup</button>
            </div>
        </div>
    </div>
    {{-- Modal Pratinjau Kartu Pelajar Digital & Cetak Rombel --}}
    @include('kartu-pelajar.modal-preview')
    @include('kartu-pelajar.modal-cetak-rombel')
@endsection

    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/kartu-pelajar.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset('js/wali-kelas-aktif.js') }}?v={{ file_exists(public_path('js/wali-kelas-aktif.js')) ? filemtime(public_path('js/wali-kelas-aktif.js')) : time() }}"></script>
    @endpush
