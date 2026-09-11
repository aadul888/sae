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
                                        onclick="openUploadFotoModal('{{ $item->peserta_didik_id }}', '{{ addslashes($item->nama) }}', '{{ $item->nisn ?? '' }}', '{{ $item->foto_url }}', '{{ $item->foto_size ?? '' }}')"
                                        title="Klik untuk melihat / mengubah pasfoto peserta didik"
                                        style="width: 42px; height: 42px; border-radius: 10px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; cursor: pointer; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25); transition: transform 0.2s ease, border-color 0.2s ease;">
                                        <img src="{{ $item->foto_url }}"
                                            alt="Foto {{ $item->nama }}"
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                @else
                                    <div class="pd-foto-thumb empty" id="pdFotoThumb_{{ $item->peserta_didik_id }}"
                                        onclick="openUploadFotoModal('{{ $item->peserta_didik_id }}', '{{ addslashes($item->nama) }}', '{{ $item->nisn ?? '' }}', '', '')"
                                        title="Klik untuk mengunggah pasfoto peserta didik (PNG)"
                                        style="width: 42px; height: 42px; border-radius: 10px; background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--primary); font-size: 0.8rem; flex-shrink: 0; cursor: pointer; transition: all 0.2s ease;">
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
                                <button type="button" class="btn-icon"
                                    title="{{ !empty($item->foto_url) ? 'Ganti / Lihat Pasfoto Peserta Didik' : 'Unggah Pasfoto Peserta Didik (PNG)' }}"
                                    onclick="openUploadFotoModal('{{ $item->peserta_didik_id }}', '{{ addslashes($item->nama) }}', '{{ $item->nisn ?? '' }}', '{{ $item->foto_url ?? '' }}', '{{ $item->foto_size ?? '' }}')">
                                    <i class="fas fa-camera" style="{{ !empty($item->foto_url) ? 'color: #10b981;' : '' }}"></i>
                                </button>
                                <button type="button" class="btn-icon" title="Lihat Biodata Lengkap"
                                    onclick="openBiodataPesertaDidikModal('{{ $item->peserta_didik_id }}')">
                                    <i class="fas fa-id-card"></i>
                                </button>
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

            <div style="overflow-y: auto; flex: 1;">
                <input type="hidden" id="fotoUploadPdId" value="">

                {{-- Area Drag & Drop / Preview Pasfoto --}}
                <div id="fotoDropZone"
                    style="border: 2px dashed var(--border-color); border-radius: 12px; padding: 20px; text-align: center; cursor: pointer; background: rgba(255,255,255,0.01); transition: all 0.2s ease;">
                    
                    <div id="fotoPreviewContainer"
                        style="width: 130px; height: 170px; margin: 0 auto 12px auto; border-radius: 10px; border: 1.5px solid var(--border-color); background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 10px 10px; display: flex; align-items: center; justify-content: center; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.35);">
                        <img id="fotoPreviewImg" src="" alt="Pratinjau Foto"
                            style="display: none; width: 100%; height: 100%; object-fit: cover;">
                        <div id="fotoPreviewPlaceholder" style="color: var(--text-muted); padding: 12px;">
                            <i class="fas fa-user-graduate" style="font-size: 2.2rem; opacity: 0.35; margin-bottom: 8px;"></i>
                            <div style="font-size: 0.75rem;">Belum ada pasfoto</div>
                        </div>
                    </div>

                    <div style="font-weight: 600; font-size: 0.88rem; color: var(--text-color); margin-bottom: 4px;">
                        Pilih berkas PNG atau seret ke sini
                    </div>
                    <div style="font-size: 0.74rem; color: var(--text-muted); margin-bottom: 12px;">
                        Wajib format <strong>.png</strong> • Aspek rasio pasfoto 3:4 • Maksimal 5 MB
                    </div>

                    <label for="fotoFileInput" class="btn btn-outline"
                        style="padding: 7px 16px; font-size: 0.8rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-folder-open"></i> Telusuri File PNG
                    </label>
                    <input type="file" id="fotoFileInput" accept="image/png" style="display: none;">
                </div>

                {{-- Spesifikasi / Metadata info --}}
                <div id="fotoFileSpecs"
                    style="display: none; margin-top: 10px; padding: 8px 12px; background: rgba(99,102,241,0.08); border-radius: 8px; font-size: 0.76rem; color: var(--primary); text-align: center;">
                </div>

                <div
                    style="margin-top: 12px; padding: 10px 12px; border-radius: 8px; background: rgba(16,185,129,0.06); border: 1px solid rgba(16,185,129,0.18); font-size: 0.74rem; color: var(--text-muted); line-height: 1.45;">
                    <strong style="color: #10b981;"><i class="fas fa-shield-halved me-1"></i> Auto-Kompresi & Proteksi Dapodik:</strong>
                    Sistem otomatis mengompresi PNG lossless dengan rasio kartu pelajar serta menyimpannya persisten di tabel metadata sehingga pasfoto tidak akan hilang saat sinkronisasi feeder Dapodik.
                </div>
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

    @push('scripts')
        <script src="{{ asset('js/peserta-didik-aktif.js') }}?v={{ filemtime(public_path('js/peserta-didik-aktif.js')) }}">
        </script>
    @endpush
@endsection
