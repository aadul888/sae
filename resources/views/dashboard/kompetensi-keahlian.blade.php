@extends('layouts.dashboard')

@section('title', 'Master Data — Kompetensi Keahlian — SAE')
@section('dash_title', 'Kompetensi Keahlian')

@section('content')
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-laptop-code text-primary me-2"></i> Master Data — Kompetensi Keahlian
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Data kompetensi keahlian / jurusan disinkronkan otomatis dari tabel <strong>Rombongan Belajar</strong>
                (Dapodik).
            </p>
        </div>
        <div class="dash-banner-actions">
            <a href="{{ route('dashboard.dapodik') }}" class="btn btn-outline" style="padding: 9px 16px; font-size: 0.85rem;">
                <i class="fas fa-cloud-arrow-down me-1"></i> Tarik Data Rombel
            </a>
        </div>
    </div>

    <!-- Summary Stats Grid -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-laptop-code"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ $summary['jurusan'] }}
                </div>
                <div class="dash-stat-label">Total Kompetensi Keahlian</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-school"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ $summary['rombel'] }}
                </div>
                <div class="dash-stat-label">Total Rombongan Belajar</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($summary['peserta_didik'] ?? 0, 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Total Peserta Didik</div>
            </div>
        </div>
    </div>

    <div class="toolbar-row">
        <div class="toolbar-entries">
            <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
            <select id="perPageSelect" class="per-page-select">
                @foreach ([10, 15, 25, 50, 100] as $n)
                    <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                @endforeach
            </select>
            <span>entri</span>
            <span class="badge badge-outline" style="margin-left: 8px; font-size: 0.73rem;">Total:
                {{ $total }}</span>
        </div>
        <div class="live-search-wrap">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="liveSearch" placeholder="Cari kode / nama jurusan..." value="{{ $q }}"
                autocomplete="off">
            <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}" title="Hapus pencarian">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-keahlian" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                    @php
                        $cols = [
                            ['kode', 'Kode Jurusan'],
                            ['nama', 'Kompetensi Keahlian'],
                            ['total_rombel', 'Jml Rombel'],
                            ['total_peserta_didik', 'Jml Peserta Didik'],
                        ];
                    @endphp
                    @foreach ($cols as [$key, $label])
                        <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer; user-select: none; {{ in_array($key, ['total_rombel', 'total_peserta_didik']) ? 'text-align: center;' : '' }}"
                            data-sort="{{ $key }}">
                            {{ $label }}
                            <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                    @endforeach
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Tingkat Kelas</th>
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Kurikulum</th>
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                        Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $item)
                    <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                        <td class="cell-kode" style="padding: 14px 18px; font-weight: 700; color: var(--primary); font-size: 0.86rem; font-family: monospace;"
                            data-label="Kode Jurusan">
                            <span class="badge badge-primary"
                                style="font-size: 0.76rem; padding: 4px 8px; font-family: monospace;">
                                {{ $item->kode }}
                            </span>
                        </td>
                        <td class="cell-jurusan-main" style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.88rem;"
                            data-label="Kompetensi Keahlian">
                            <div class="jurusan-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                @if (!empty($item->logo_url))
                                    <div class="jurusan-logo-thumb"
                                        id="logoThumb_{{ $item->kode }}"
                                        onclick="openUploadLogoModal('{{ $item->kode }}', '{{ addslashes($item->nama) }}', '{{ $item->logo_url }}', '{{ $item->logo_size ?? '' }}')"
                                        title="Klik untuk melihat / mengubah logo jurusan"
                                        style="width: 44px; height: 44px; border-radius: 10px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; cursor: pointer; flex-shrink: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.25); transition: transform 0.2s ease, border-color 0.2s ease;">
                                        <img src="{{ $item->logo_url }}"
                                            alt="Logo {{ $item->nama }}"
                                            style="max-width: 92%; max-height: 92%; object-fit: contain; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.3));">
                                    </div>
                                @else
                                    <div class="jurusan-logo-thumb empty"
                                        id="logoThumb_{{ $item->kode }}"
                                        onclick="openUploadLogoModal('{{ $item->kode }}', '{{ addslashes($item->nama) }}', '', '')"
                                        title="Klik untuk mengunggah logo jurusan"
                                        style="width: 44px; height: 44px; border-radius: 10px; background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--primary); font-size: 0.8rem; flex-shrink: 0; cursor: pointer; transition: all 0.2s ease;">
                                        <i class="fas fa-arrow-up-from-bracket" style="font-size: 0.78rem;"></i>
                                        <span style="font-size: 0.55rem; font-weight: 800; letter-spacing: 0.5px; margin-top: 2px;">PNG</span>
                                    </div>
                                @endif
                                <div class="jurusan-info">
                                    <div class="jurusan-title-row" style="font-weight: 700; display: flex; align-items: center; gap: 6px;">
                                        <span class="jurusan-nama">{{ $item->nama }}</span>
                                        @if (!empty($item->logo_url))
                                            <span class="badge" id="logoBadge_{{ $item->kode }}"
                                                style="font-size: 0.64rem; background: rgba(16,185,129,0.12); color: #10b981; padding: 2px 6px;"
                                                title="Logo PNG tersimpan persisten ({{ $item->logo_size ?? '' }})">
                                                <i class="fas fa-check-circle me-1"></i>PNG {{ $item->logo_size ?? '' }}
                                            </span>
                                        @else
                                            <span class="badge badge-outline" id="logoBadge_{{ $item->kode }}"
                                                style="font-size: 0.62rem; color: var(--text-muted); opacity: 0.7; padding: 1px 5px;">
                                                Belum ada logo
                                            </span>
                                        @endif
                                    </div>
                                    @if (!empty($item->rombel_list))
                                        <div class="jurusan-rombel-sub" style="font-size: 0.73rem; color: var(--text-muted); margin-top: 2px;">
                                            {{ implode(', ', array_slice($item->rombel_list, 0, 4)) }}{{ count($item->rombel_list) > 4 ? ' +' . (count($item->rombel_list) - 4) . ' lainnya' : '' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="cell-rombel" style="padding: 14px 18px; text-align: center; font-weight: 700; font-size: 0.88rem;"
                            data-label="Jml Rombel">
                            <span class="badge badge-outline" style="font-size: 0.78rem; padding: 3px 10px;">
                                <i class="fas fa-users-rectangle me-1" style="opacity: 0.7;"></i> {{ $item->total_rombel }}
                            </span>
                        </td>
                        <td class="cell-peserta-didik cell-siswa" style="padding: 14px 18px; text-align: center; font-weight: 700; font-size: 0.88rem; color: #10b981;"
                            data-label="Jml Peserta Didik">
                            <span class="badge"
                                style="background: rgba(16,185,129,0.12); color: #10b981; font-size: 0.78rem; padding: 3px 10px;">
                                <i class="fas fa-user-graduate me-1"></i>
                                {{ number_format($item->total_peserta_didik, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="cell-tingkat" style="padding: 14px 18px; font-size: 0.8rem; color: var(--text-muted);" data-label="Tingkat">
                            <div class="tingkat-wrap">
                                @if (!empty($item->tingkat_list))
                                    @foreach ($item->tingkat_list as $tk)
                                        <span class="badge badge-outline"
                                            style="font-size: 0.7rem; padding: 2px 6px; margin-right: 3px;">{{ $tk }}</span>
                                    @endforeach
                                @else
                                    -
                                @endif
                            </div>
                        </td>
                        <td class="cell-kurikulum" style="padding: 14px 18px; font-size: 0.78rem; color: var(--text-muted); max-width: 220px;"
                            data-label="Kurikulum">
                            <div class="kurikulum-text">
                                @if (!empty($item->kurikulum_list))
                                    {{ Str::limit(implode(', ', $item->kurikulum_list), 45) }}
                                @else
                                    -
                                @endif
                            </div>
                        </td>
                        <td class="cell-aksi" style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                            <div class="table-actions">
                                <button type="button" class="btn-icon"
                                    title="{{ !empty($item->logo_url) ? 'Ganti / Lihat Logo Jurusan' : 'Unggah Logo Jurusan' }}"
                                    onclick="openUploadLogoModal('{{ $item->kode }}', '{{ addslashes($item->nama) }}', '{{ $item->logo_url ?? '' }}', '{{ $item->logo_size ?? '' }}')">
                                    <i class="fas fa-image" style="{{ !empty($item->logo_url) ? 'color: #10b981;' : '' }}"></i>
                                </button>
                                <button type="button" class="btn-icon" title="Lihat Daftar Rombel & Peserta Didik"
                                    onclick="openRombelModal('{{ $item->kode }}', '{{ addslashes($item->nama) }}')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"
                            style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                            <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                            <div>Belum ada data kompetensi keahlian di tabel rombongan_belajar.</div>
                            <div style="font-size: 0.78rem; margin-top: 6px;">
                                Silakan sinkronisasi data Dapodik terlebih dahulu melalui menu <strong>Tarik Data
                                    Dapodik</strong>.
                            </div>
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

    {{-- Detail Rombel Modal --}}
    <div id="rombelModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 720px; width: 92%; max-height: 85vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div>
                    <h3 id="rombelModalTitle"
                        style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                        Daftar Rombongan Belajar
                    </h3>
                    <div id="rombelModalSubtitle" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                        -
                    </div>
                </div>
                <button type="button" onclick="closeRombelModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="rombelLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: 1.4rem;"></i>
                <div>Memuat data rombel...</div>
            </div>

            <div id="rombelTableWrapper"
                style="overflow-x: auto; overflow-y: auto; flex: 1; display: none; width: 100%; -webkit-overflow-scrolling: touch; border-radius: 8px; border: 1px solid var(--border-color);">
                <table class="table"
                    style="min-width: 620px; width: 100%; border-collapse: collapse; margin-bottom: 0; font-size: 0.83rem;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted);">Nama Rombel</th>
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted);">Tingkat</th>
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted);">Wali Kelas</th>
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted);">Ruang</th>
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted);">Kurikulum</th>
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted); text-align: right;">
                                Jml Peserta Didik</th>
                        </tr>
                    </thead>
                    <tbody id="rombelTableBody">
                    </tbody>
                </table>
            </div>

            <div
                style="display: flex; justify-content: flex-end; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-outline" onclick="closeRombelModal()"
                    style="padding: 8px 18px; font-size: 0.82rem;">Tutup</button>
            </div>
        </div>
    </div>

    {{-- Modal Unggah & Kelola Logo Jurusan --}}
    <div id="logoUploadModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="card"
            style="max-width: 520px; width: 92%; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(99,102,241,0.15); display: flex; align-items: center; justify-content: center; color: var(--primary);">
                        <i class="fas fa-image"></i>
                    </div>
                    <div>
                        <h3 id="logoModalTitle"
                            style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                            Unggah Logo Jurusan
                        </h3>
                        <div id="logoModalSubtitle" style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                            -
                        </div>
                    </div>
                </div>
                <button type="button" onclick="closeLogoUploadModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div style="overflow-y: auto; flex: 1; padding-right: 4px;">
                {{-- Info Box Persistensi & Kartu Pelajar --}}
                <div style="background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.2); border-radius: 10px; padding: 12px; margin-bottom: 16px; font-size: 0.78rem; line-height: 1.5; color: var(--text-color);">
                    <div style="font-weight: 700; color: var(--primary); margin-bottom: 4px;">
                        <i class="fas fa-shield-halved me-1"></i> Perlindungan Dapodik & Standar Gambar:
                    </div>
                    <ul style="margin: 0; padding-left: 18px; color: var(--text-muted);">
                        <li>Logo disimpan secara <strong>persisten</strong> dan <strong>tidak akan terhapus</strong> ketika melakukan tarik data Dapodik.</li>
                        <li>Wajib format <strong>PNG transparan</strong> (akan digunakan untuk watermark / latar belakang kartu pelajar).</li>
                        <li>Sistem melakukan <strong>kompresi otomatis lossless</strong> sehingga file ringan tanpa merusak warna & ketajaman.</li>
                    </ul>
                </div>

                {{-- Area Preview Logo --}}
                <div style="text-align: center; margin-bottom: 16px;">
                    <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">
                        Pratinjau Transparansi Logo
                    </div>
                    <div id="logoPreviewContainer"
                        style="width: 140px; height: 140px; margin: 0 auto; border-radius: 14px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 12px 12px; border: 2px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; box-shadow: 0 4px 16px rgba(0,0,0,0.3);">
                        <img id="logoPreviewImg" src="" alt="Pratinjau Logo"
                            style="display: none; max-width: 90%; max-height: 90%; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.35));">
                        <div id="logoPreviewPlaceholder" style="color: var(--text-muted); font-size: 0.8rem; padding: 10px;">
                            <i class="fas fa-cloud-arrow-up mb-2" style="font-size: 2rem; opacity: 0.4;"></i>
                            <div style="font-size: 0.72rem;">Belum ada file dipilih</div>
                        </div>
                    </div>
                    <div id="logoFileSpecs" style="display: none; font-size: 0.74rem; color: #10b981; margin-top: 8px; font-weight: 600;">
                        -
                    </div>
                </div>

                {{-- Form Unggah Drag & Drop --}}
                <form id="logoUploadForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="logoUploadKode" name="kode" value="">

                    <div id="logoDropZone"
                        style="border: 2px dashed rgba(99,102,241,0.4); border-radius: 12px; padding: 20px 14px; text-align: center; cursor: pointer; transition: all 0.2s ease; background: rgba(255,255,255,0.01);"
                        onclick="document.getElementById('logoFileInput').click()">
                        <i class="fas fa-file-image" style="font-size: 1.8rem; color: var(--primary); margin-bottom: 8px;"></i>
                        <div style="font-size: 0.84rem; font-weight: 700; color: var(--text-color);">
                            Pilih file atau seret file PNG ke sini
                        </div>
                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 4px;">
                            Hanya file <strong>.PNG</strong> (Maks. 5 MB)
                        </div>
                        <input type="file" id="logoFileInput" name="logo" accept=".png,image/png" style="display: none;">
                    </div>
                </form>
            </div>

            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border-color);">
                <button type="button" id="btnDeleteLogo" class="btn"
                    style="display: none; background: rgba(239,68,68,0.12); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); font-size: 0.82rem; padding: 8px 14px;"
                    onclick="handleDeleteLogo()">
                    <i class="fas fa-trash-can me-1"></i> Hapus Logo
                </button>
                <div style="display: flex; gap: 8px; margin-left: auto;">
                    <button type="button" class="btn btn-outline" onclick="closeLogoUploadModal()"
                        style="padding: 8px 16px; font-size: 0.82rem;">Batal</button>
                    <button type="button" id="btnSubmitLogo" class="btn btn-primary"
                        style="padding: 8px 18px; font-size: 0.82rem;" onclick="handleSubmitLogo()">
                        <i class="fas fa-cloud-arrow-up me-1"></i> Simpan Logo
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/kompetensi-keahlian.js') }}?v={{ filemtime(public_path('js/kompetensi-keahlian.js')) }}">
        </script>
    @endpush
@endsection
