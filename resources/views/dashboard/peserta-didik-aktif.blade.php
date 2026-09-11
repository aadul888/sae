@extends('layouts.dashboard')

@section('title', 'Manajemen Data — Peserta Didik Aktif — SAE')
@section('dash_title', 'Peserta Didik Aktif')

@section('content')
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-user-graduate text-primary me-2"></i> Manajemen Data — Peserta Didik Aktif
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Direktori data peserta didik aktif bersumber langsung dari tabel <strong>Peserta Didik</strong> (Dapodik).
            </p>
        </div>
        <div class="dash-banner-actions">
            <a href="{{ route('dashboard.dapodik') }}" class="btn btn-outline" style="padding: 9px 16px; font-size: 0.85rem;">
                <i class="fas fa-cloud-arrow-down me-1"></i> Tarik Data Dapodik
            </a>
        </div>
    </div>

    <!-- Summary Stats Grid -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($summary['total'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Total Peserta Didik Aktif</div>
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
                <i class="fas fa-chalkboard-user"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ $summary['rombel'] }}
                </div>
                <div class="dash-stat-label">Total Rombel</div>
            </div>
        </div>
    </div>

    <!-- Toolbar & Filter -->
    <!-- Toolbar & Filter -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <div class="toolbar-entries">
                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                    <select id="perPageSelect" class="per-page-select">
                        @foreach ([10, 15, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}
                            </option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </div>

                <select id="filterRombel" class="toolbar-filter-select">
                    <option value="">Semua Rombel</option>
                    @foreach ($filterRombel as $r)
                        <option value="{{ $r }}" {{ $rombel === $r ? 'selected' : '' }}>{{ $r }}
                        </option>
                    @endforeach
                </select>

                <select id="filterGender" class="toolbar-filter-select" style="min-width: 120px;">
                    <option value="">Semua Gender</option>
                    <option value="L" {{ $gender === 'L' ? 'selected' : '' }}>Laki-Laki (L)</option>
                    <option value="P" {{ $gender === 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                </select>

                @if ($q || $rombel || $gender)
                    <a href="{{ route('dashboard.peserta-didik-aktif.index') }}" class="btn btn-outline"
                        style="padding: 7px 12px; font-size: 0.8rem;" title="Reset filter">
                        <i class="fas fa-undo me-1"></i> Reset
                    </a>
                @endif

                @if ($canManageStudentPhotos)
                    <button type="button" class="btn btn-primary" onclick="openBulkUploadFotoModal('{{ $waliRombel ?: $rombel }}')"
                        style="padding: 7px 14px; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(99,102,241,0.3);">
                        <i class="fas fa-images"></i> Unggah Foto Masal Kelas
                    </button>

                    <button type="button" class="btn btn-outline" onclick="openCetakRombelModal('{{ $waliRombel ?: $rombel }}')"
                        style="padding: 7px 14px; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 6px; border-color: #0284c7; color: #0284c7;">
                        <i class="fas fa-id-card"></i> Cetak Kartu Pelajar Masal
                    </button>
                @endif
            </div>

            <div class="live-search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearch" placeholder="Cari nama / NISN / NIK..." value="{{ $q }}"
                    autocomplete="off">
                <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}"
                    title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Datatable Peserta Didik -->
    <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                    @php
                        $cols = [
                            ['nama', 'Nama Lengkap'],
                            ['nisn', 'NISN / NIPD'],
                            ['jenis_kelamin', 'L/P'],
                            ['nama_rombel', 'Rombel Kelas'],
                            ['tingkat_pendidikan_id', 'Tingkat'],
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
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Tempat, Tanggal Lahir
                    </th>
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $item)
                    <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                        <td class="cell-pd-nama" style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: 0.88rem;"
                            data-label="Nama Lengkap">
                            <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                @if (!empty($item->foto_url))
                                    <div class="pd-foto-thumb" id="pdFotoThumb_{{ $item->peserta_didik_id }}"
                                        @if ($canManageStudentPhotos)
                                        onclick="openUploadFotoModal('{{ $item->peserta_didik_id }}', '{{ addslashes($item->nama) }}', '{{ $item->nisn ?? '' }}', '{{ $item->foto_url }}', '{{ $item->foto_size ?? '' }}')"
                                        title="Klik untuk melihat / mengubah pasfoto peserta didik"
                                        @else
                                        title="Pasfoto {{ $item->nama }}"
                                        @endif
                                        style="width: 42px; height: 42px; border-radius: 10px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; {{ $canManageStudentPhotos ? 'cursor: pointer;' : 'cursor: default;' }} flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25); transition: transform 0.2s ease, border-color 0.2s ease;">
                                        <img src="{{ $item->foto_url }}"
                                            alt="Foto {{ $item->nama }}"
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                @else
                                    <div class="pd-foto-thumb empty" id="pdFotoThumb_{{ $item->peserta_didik_id }}"
                                        @if ($canManageStudentPhotos)
                                        onclick="openUploadFotoModal('{{ $item->peserta_didik_id }}', '{{ addslashes($item->nama) }}', '{{ $item->nisn ?? '' }}', '', '')"
                                        title="Klik untuk mengunggah pasfoto peserta didik (PNG)"
                                        @else
                                        title="Belum ada pasfoto"
                                        @endif
                                        style="width: 42px; height: 42px; border-radius: 10px; background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--primary); font-size: 0.8rem; flex-shrink: 0; {{ $canManageStudentPhotos ? 'cursor: pointer;' : 'cursor: default;' }} transition: all 0.2s ease;">
                                        <i class="fas fa-camera" style="font-size: 0.82rem;"></i>
                                        <span style="font-size: 0.52rem; font-weight: 800; letter-spacing: 0.5px; margin-top: 2px;">PNG</span>
                                    </div>
                                @endif
                                <div class="pd-info">
                                    <div class="pd-title-row" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                        <span class="pd-nama" style="font-weight: 700; color: var(--text-color);">{{ $item->nama }}</span>
                                        @if (!empty($item->foto_url))
                                            <span class="badge" id="pdFotoBadge_{{ $item->peserta_didik_id }}"
                                                style="font-size: 0.62rem; background: rgba(16,185,129,0.12); color: #10b981; padding: 2px 6px; white-space: nowrap;"
                                                title="Foto PNG tersimpan persisten ({{ $item->foto_size ?? '' }})">
                                                <i class="fas fa-check-circle me-1"></i>PNG {{ $item->foto_size ?? '' }}
                                            </span>
                                        @else
                                            <span class="badge badge-outline" id="pdFotoBadge_{{ $item->peserta_didik_id }}"
                                                style="font-size: 0.6rem; color: var(--text-muted); opacity: 0.7; padding: 1px 5px; white-space: nowrap;">
                                                Belum ada foto
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
                        <td class="cell-pd-nisn" style="padding: 14px 18px; font-family: monospace; font-size: 0.84rem; color: var(--primary);"
                            data-label="NISN / NIPD">
                            <div>{{ $item->nisn ?: '-' }}</div>
                            @if ($item->nipd)
                                <div style="font-size: 0.72rem; color: var(--text-muted);">NIPD: {{ $item->nipd }}</div>
                            @endif
                        </td>
                        <td class="cell-pd-gender" style="padding: 14px 18px; text-align: center;" data-label="L/P">
                            <span class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}"
                                style="font-size: 0.72rem; padding: 2px 7px;">
                                {{ $item->jenis_kelamin ?: '-' }}
                            </span>
                        </td>
                        <td class="cell-pd-rombel" style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.84rem;"
                            data-label="Rombel Kelas">
                            <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">
                                {{ $item->nama_rombel ?: '-' }}
                            </span>
                        </td>
                        <td class="cell-pd-tingkat" style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);"
                            data-label="Tingkat">
                            {{ $item->tingkat_pendidikan_id ?: '-' }}
                        </td>
                        <td class="cell-pd-ttl" style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);"
                            data-label="Tempat, Tanggal Lahir">
                            {{ implode(', ', array_filter([$item->tempat_lahir, $item->tanggal_lahir])) ?: '-' }}
                        </td>
                        <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                            <div class="table-actions">
                                @if ($canManageStudentPhotos)
                                <button type="button" class="btn-icon"
                                    title="{{ !empty($item->foto_url) ? 'Ganti / Lihat Pasfoto Peserta Didik' : 'Unggah Pasfoto Peserta Didik (PNG)' }}"
                                    onclick="openUploadFotoModal('{{ $item->peserta_didik_id }}', '{{ addslashes($item->nama) }}', '{{ $item->nisn ?? '' }}', '{{ $item->foto_url ?? '' }}', '{{ $item->foto_size ?? '' }}')">
                                    <i class="fas fa-camera" style="{{ !empty($item->foto_url) ? 'color: #10b981;' : '' }}"></i>
                                </button>
                                @endif
                                <button type="button" class="btn-icon" title="Lihat Biodata Lengkap"
                                    onclick="openBiodataPesertaDidikModal('{{ $item->peserta_didik_id }}')">
                                    <i class="fas fa-id-card"></i>
                                </button>
                                @if(!empty($item->nisn) && $canManageStudentPhotos)
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
                        <td colspan="7"
                            style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                            <i class="fas fa-user-slash mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                            <div>Belum ada data peserta didik yang cocok.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($list->hasPages())
        <div class="custom-pagination">
            @if ($list->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $list->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i
                        class="fas fa-chevron-left"></i></a>
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
                <a href="{{ $list->url($i) }}"
                    class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor
            @if ($to < $last)
                @if ($to < $last - 1)
                    <span class="page-info">&hellip;</span>
                @endif
                <a href="{{ $list->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif
            @if ($list->hasMorePages())
                <a href="{{ $list->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i
                        class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif

    {{-- Modal Biodata Peserta Didik --}}
    <div id="biodataModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 720px; width: 94%; max-height: 85vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
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
                <div style="margin-bottom: 12px;">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-user me-1"></i> Data Pribadi &amp; Fisik
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">NISN / NIPD</td>
                            <td id="bioNisn" style="font-weight: 600; font-family: monospace; color: var(--primary);">-
                            </td>
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

    {{-- Modal Unggah & Kelola Pasfoto Peserta Didik (Format PNG untuk Kartu Pelajar Digital) --}}
    <div id="fotoUploadModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="card"
            style="max-width: 500px; width: 92%; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(99,102,241,0.15); display: flex; align-items: center; justify-content: center; color: var(--primary);">
                        <i class="fas fa-camera"></i>
                    </div>
                    <div>
                        <h3 id="fotoModalTitle"
                            style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                            Unggah Pasfoto Peserta Didik
                        </h3>
                        <div id="fotoModalSubtitle" style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                            -
                        </div>
                    </div>
                </div>
                <button type="button" onclick="closeUploadFotoModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div style="overflow-y: auto; flex: 1; padding-right: 4px;">
                <input type="hidden" id="fotoUploadPdId" value="">

                {{-- Info Box Persistensi & Kartu Pelajar --}}
                <div style="background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.2); border-radius: 10px; padding: 12px; margin-bottom: 16px; font-size: 0.78rem; line-height: 1.5; color: var(--text-color);">
                    <div style="font-weight: 700; color: var(--primary); margin-bottom: 4px;">
                        <i class="fas fa-shield-halved me-1"></i> Perlindungan Dapodik & Standar Gambar:
                    </div>
                    <ul style="margin: 0; padding-left: 18px; color: var(--text-muted);">
                        <li>Pasfoto disimpan secara <strong>persisten</strong> di tabel metadata dan <strong>tidak akan terhapus</strong> ketika melakukan tarik data Dapodik.</li>
                        <li>Wajib format <strong>PNG</strong> (akan digunakan untuk kartu pelajar digital & sistem presensi).</li>
                        <li>Sistem melakukan <strong>kompresi otomatis lossless</strong> sehingga file ringan tanpa mengurangi ketajaman.</li>
                    </ul>
                </div>

                {{-- Area Preview Pasfoto (3:4) --}}
                <div style="text-align: center; margin-bottom: 16px;">
                    <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">
                        Pratinjau Pasfoto (Aspek Rasio 3:4)
                    </div>
                    <div id="fotoPreviewContainer"
                        style="width: 126px; height: 168px; margin: 0 auto; border-radius: 12px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 10px 10px; border: 2px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; box-shadow: 0 4px 16px rgba(0,0,0,0.35);">
                        <img id="fotoPreviewImg" src="" alt="Pratinjau Foto"
                            style="display: none; width: 100%; height: 100%; object-fit: cover;">
                        <div id="fotoPreviewPlaceholder" style="color: var(--text-muted); font-size: 0.8rem; padding: 10px;">
                            <i class="fas fa-user-graduate mb-2" style="font-size: 2.2rem; opacity: 0.4;"></i>
                            <div style="font-size: 0.72rem;">Belum ada pasfoto</div>
                        </div>
                    </div>
                    <div id="fotoFileSpecs" style="display: none; font-size: 0.74rem; color: #10b981; margin-top: 8px; font-weight: 600;">
                        -
                    </div>
                </div>

                {{-- Form Unggah Drag & Drop --}}
                <form id="fotoUploadForm" enctype="multipart/form-data">
                    @csrf
                    <div id="fotoDropZone"
                        style="border: 2px dashed rgba(99,102,241,0.4); border-radius: 12px; padding: 20px 14px; text-align: center; cursor: pointer; transition: all 0.2s ease; background: rgba(255,255,255,0.01);"
                        onclick="document.getElementById('fotoFileInput').click()">
                        <i class="fas fa-file-image" style="font-size: 1.8rem; color: var(--primary); margin-bottom: 8px;"></i>
                        <div style="font-size: 0.84rem; font-weight: 700; color: var(--text-color);">
                            Pilih file atau seret file PNG ke sini
                        </div>
                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 4px;">
                            Hanya file <strong>.PNG</strong> (Maks. 5 MB)
                        </div>
                        <input type="file" id="fotoFileInput" name="foto" accept=".png,image/png" style="display: none;">
                    </div>
                </form>
            </div>

            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border-color);">
                <button type="button" id="btnDeleteFoto" onclick="handleDeleteFoto()" class="btn btn-danger"
                    style="display: none; padding: 8px 14px; font-size: 0.8rem;">
                    <i class="fas fa-trash-can me-1"></i> Hapus Pasfoto
                </button>
                <div style="display: flex; gap: 8px; margin-left: auto;">
                    <button type="button" onclick="closeUploadFotoModal()" class="btn btn-outline"
                        style="padding: 8px 16px; font-size: 0.8rem;">Batal</button>
                    <button type="button" id="btnSubmitFoto" onclick="handleSubmitFoto()" class="btn btn-primary"
                        style="padding: 8px 18px; font-size: 0.8rem;">
                        <i class="fas fa-cloud-arrow-up me-1"></i> Simpan Pasfoto
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Unggah Pasfoto Masal per Kelas (36 - 52 Peserta Didik Sekaligus) --}}
    <div id="bulkFotoUploadModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.72); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(6px);">
        <div class="card"
            style="max-width: 960px; width: 95%; max-height: 92vh; display: flex; flex-direction: column; margin: 0; border-radius: 16px; padding: 22px; box-shadow: 0 16px 40px rgba(0,0,0,0.5);">
            
            {{-- Header Modal --}}
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div
                        style="width: 40px; height: 40px; border-radius: 10px; background: rgba(99,102,241,0.18); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.15rem; box-shadow: 0 2px 10px rgba(99,102,241,0.25);">
                        <i class="fas fa-images"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin: 0;">
                            Unggah Pasfoto Masal per Kelas
                        </h3>
                        <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                            Dukungan unggah sekaligus satu kelas (36 - 52 peserta didik) dengan pencocokan otomatis cerdas format PNG
                        </div>
                    </div>
                </div>
                <button type="button" onclick="closeBulkUploadFotoModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem;" title="Tutup Modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Body Modal (Scrollable) --}}
            <div style="overflow-y: auto; flex: 1; padding-right: 4px;">
                {{-- STEP 1: Pilih Kelas / Rombel --}}
                <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin-bottom: 14px; padding: 12px 14px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 250px;">
                        <label for="bulkRombelSelect" style="margin: 0; font-weight: 700; font-size: 0.85rem; color: var(--text-color); white-space: nowrap;">
                            <i class="fas fa-chalkboard text-primary me-1"></i> Pilih Kelas:
                        </label>
                        <select id="bulkRombelSelect" class="form-select toolbar-filter-select"
                            onchange="handleBulkRombelChange(this.value)" style="min-width: 200px; flex: 1;">
                            <option value="">-- Pilih Rombel Kelas --</option>
                            @foreach ($filterRombel as $r)
                                <option value="{{ $r }}" {{ $rombel === $r ? 'selected' : '' }}>{{ $r }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="bulkRombelStatsBadge" style="display: none; align-items: center; gap: 8px;">
                        <span class="badge" style="background: rgba(99,102,241,0.15); color: var(--primary); font-size: 0.78rem; padding: 5px 10px;">
                            <i class="fas fa-users me-1"></i> <span id="bulkTotalPdCount">0</span> Peserta Didik
                        </span>
                        <span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; font-size: 0.78rem; padding: 5px 10px;">
                            <i class="fas fa-check-circle me-1"></i> <span id="bulkSudahFotoCount">0</span> Sudah Ada Foto
                        </span>
                        <span class="badge" style="background: rgba(245,158,11,0.15); color: #f59e0b; font-size: 0.78rem; padding: 5px 10px;">
                            <i class="fas fa-clock me-1"></i> <span id="bulkBelumFotoCount">0</span> Belum
                        </span>
                    </div>
                </div>

                {{-- STEP 2: Dropzone Multi-file PNG --}}
                <div id="bulkDropZoneWrapper" style="display: none; margin-bottom: 14px;">
                    <div id="bulkDropZone"
                        style="border: 2px dashed rgba(99,102,241,0.45); border-radius: 14px; padding: 22px; text-align: center; cursor: pointer; background: rgba(99,102,241,0.03); transition: all 0.25s ease;">
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: rgba(99,102,241,0.15); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-bottom: 8px;">
                            <i class="fas fa-cloud-arrow-up"></i>
                        </div>
                        <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color); margin-bottom: 4px;">
                            Pilih atau Tarik Seluruh Foto PNG Kelas Ini ke Sini
                        </div>
                        <div style="font-size: 0.76rem; color: var(--text-muted); max-width: 600px; margin: 0 auto 12px auto; line-height: 1.45;">
                            Unggah 36 hingga 52 file PNG sekaligus. Beri nama file berupa <strong>NISN</strong> (contoh: <code>0071234567.png</code>), <strong>Nama Peserta Didik</strong>, atau <strong>Nomor Urut Absen</strong> (<code>01.png</code> s.d. <code>36.png</code>) untuk pencocokan otomatis 100%.
                        </div>

                        <div style="display: inline-flex; align-items: center; gap: 8px;">
                            <label for="bulkFilesInput" class="btn btn-primary"
                                style="padding: 8px 18px; font-size: 0.82rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fas fa-folder-open"></i> Pilih Berkas Foto Sekaligus
                            </label>
                            <button type="button" id="btnClearBulkMatches" onclick="resetBulkMatches()" class="btn btn-outline"
                                style="display: none; padding: 8px 14px; font-size: 0.82rem;">
                                <i class="fas fa-rotate-left me-1"></i> Reset Berkas
                            </button>
                        </div>
                        <input type="file" id="bulkFilesInput" multiple accept="image/png" style="display: none;">
                    </div>
                </div>

                {{-- Summary Banner Pencocokan Otomatis --}}
                <div id="bulkMatchSummaryBanner"
                    style="display: none; margin-bottom: 14px; padding: 10px 14px; border-radius: 10px; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); font-size: 0.82rem; color: var(--text-color); align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-circle-check text-success" style="font-size: 1.2rem;"></i>
                        <div>
                            <span id="bulkMatchSummaryText" style="font-weight: 600;">36 foto berhasil dicocokkan otomatis.</span>
                            <div id="bulkMatchHint" style="font-size: 0.74rem; color: var(--text-muted);">
                                Anda dapat meninjau pratinjau setiap peserta didik di bawah sebelum menekan tombol simpan.
                            </div>
                        </div>
                    </div>
                    <span id="bulkFilesCountBadge" class="badge badge-primary" style="font-size: 0.75rem; padding: 4px 8px;">
                        0 Berkas Siap
                    </span>
                </div>

                {{-- Live Progress Bar Container --}}
                <div id="bulkProgressContainer"
                    style="display: none; margin-bottom: 14px; padding: 14px; border-radius: 12px; background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.25);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span id="bulkProgressTitle" style="font-weight: 700; font-size: 0.84rem; color: var(--primary);">
                            <i class="fas fa-spinner fa-spin me-1"></i> Mengompresi &amp; Menyimpan Pasfoto...
                        </span>
                        <span id="bulkProgressPercent" style="font-weight: 800; font-size: 0.85rem; color: var(--text-color);">
                            0%
                        </span>
                    </div>
                    <div style="width: 100%; height: 10px; background: rgba(255,255,255,0.08); border-radius: 10px; overflow: hidden;">
                        <div id="bulkProgressBar"
                            style="width: 0%; height: 100%; background: linear-gradient(90deg, #6366f1, #10b981); transition: width 0.25s ease; border-radius: 10px;"></div>
                    </div>
                    <div id="bulkProgressDetail" style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px; text-align: right;">
                        0 dari 0 foto selesai
                    </div>
                </div>

                {{-- Loading Spinner State --}}
                <div id="bulkLoadingSpinner" style="display: none; text-align: center; padding: 40px; color: var(--text-muted);">
                    <i class="fas fa-spinner fa-spin me-2" style="font-size: 1.6rem; color: var(--primary);"></i>
                    <div style="margin-top: 8px; font-size: 0.86rem;">Memuat daftar peserta didik rombel...</div>
                </div>

                {{-- Empty State (Belum Pilih Rombel) --}}
                <div id="bulkEmptyState" style="text-align: center; padding: 50px 20px; color: var(--text-muted);">
                    <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center; font-size: 1.6rem; opacity: 0.45; margin-bottom: 12px;">
                        <i class="fas fa-chalkboard-user"></i>
                    </div>
                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px;">Pilih Rombel Kelas Terlebih Dahulu</h4>
                    <p style="font-size: 0.78rem; max-width: 420px; margin: 0 auto;">Pilih rombel kelas pada pilihan di atas untuk menampilkan daftar peserta didik dan mencocokkan foto masal sekaligus.</p>
                </div>

                {{-- STEP 3: Tabel Peserta Didik Rombel & Pemetaan Berkas Foto --}}
                <div id="bulkTableWrapper" style="display: none; overflow-x: auto;">
                    <table class="table" style="width: 100%; border-collapse: collapse; font-size: 0.82rem; margin-bottom: 0;">
                        <thead>
                            <tr style="background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border-color);">
                                <th style="padding: 10px 12px; width: 45px; text-align: center; color: var(--text-muted);">No</th>
                                <th style="padding: 10px 12px; width: 60px; text-align: center; color: var(--text-muted);">Foto Lama</th>
                                <th style="padding: 10px 12px; color: var(--text-muted);">Nama Lengkap &amp; NISN</th>
                                <th style="padding: 10px 12px; color: var(--text-muted); width: 220px;">Berkas Foto Baru</th>
                                <th style="padding: 10px 12px; width: 90px; text-align: center; color: var(--text-muted);">Pratinjau</th>
                                <th style="padding: 10px 12px; width: 120px; text-align: center; color: var(--text-muted);">Status</th>
                                <th style="padding: 10px 12px; width: 60px; text-align: right; color: var(--text-muted);">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="bulkStudentsTableBody"></tbody>
                    </table>
                </div>
            </div>

            {{-- Footer Modal --}}
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border-color);">
                <button type="button" onclick="closeBulkUploadFotoModal()" class="btn btn-outline"
                    style="padding: 8px 18px; font-size: 0.82rem;">
                    Batal
                </button>

                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" id="btnStartBulkUpload" onclick="executeBulkUploadQueue()" class="btn btn-primary"
                        disabled style="padding: 8px 22px; font-size: 0.84rem; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-cloud-arrow-up"></i>
                        <span id="btnStartBulkUploadText">Mulai Unggah &amp; Kompresi Masal (0 Foto)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Pratinjau Kartu Pelajar Digital & Modal Cetak Masal --}}
    @include('kartu-pelajar.modal-preview')
    @include('kartu-pelajar.modal-cetak-rombel')

    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/kartu-pelajar.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset('js/peserta-didik-aktif.js') }}?v={{ filemtime(public_path('js/peserta-didik-aktif.js')) }}">
        </script>
    @endpush
@endsection
