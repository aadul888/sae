@extends('layouts.dashboard')

@section('title', 'Wali Kelas — Validasi Data — SAE')
@section('dash_title', 'Wali Kelas — Validasi Data')

@section('content')
    <!-- Header Banner -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-user-check text-primary me-2"></i> Wali Kelas — Validasi Data Peserta Didik
            </h2>
            <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem); margin: 0;">
                @if ($hasRombel && $activeRombel)
                    Kelas Binaan: <strong style="color: var(--text-color);">{{ $activeRombel->nama }}</strong>
                    &bull; Wali Kelas: <strong style="color: var(--primary);">{{ $waliNama }}</strong>
                    @if (!empty($activeRombel->jurusan_id_str))
                        &bull; Jurusan: {{ $activeRombel->jurusan_id_str }}
                    @endif
                @else
                    Monitoring peserta didik yang sudah melakukan konfirmasi data identitas serta status verifikasi berkas
                    digitalnya.
                @endif
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 10px; align-items: center;">
            @if ($isAdmin && $rombelList->isNotEmpty())
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="adminRombelSelect"
                        style="font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-muted); margin: 0; white-space: nowrap;">
                        <i class="fas fa-filter me-1"></i> Pilih Rombel:
                    </label>
                    <select id="adminRombelSelect" class="form-control"
                        style="font-size: var(--text-base, 0.875rem); padding: 6px 12px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color); min-width: 170px;">
                        @foreach ($rombelList as $r)
                            <option value="{{ $r->rombongan_belajar_id }}"
                                {{ $activeRombel?->rombongan_belajar_id === $r->rombongan_belajar_id ? 'selected' : '' }}>
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
            <div
                style="width: 70px; height: 70px; border-radius: 50%; background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b); display: inline-flex; align-items: center; justify-content: center; font-size: var(--text-3xl, 1.875rem); margin-bottom: 16px;">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <h3 style="font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color); margin-bottom: 8px;">Belum Ada Rombel
                Binaan Terdaftar</h3>
            <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem); max-width: 540px; margin: 0 auto 20px;">
                Akun Anda belum terikat dengan rombongan belajar aktif sebagai Wali Kelas pada data Dapodik sekolah. Silakan
                hubungi Administrator sistem atau Operator Dapodik untuk pembaruan penugasan tugas tambahan.
            </p>
        </div>
    @else
        <!-- Summary Stats Grid -->
        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                        {{ number_format($summary['total'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Total Peserta Didik</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success, #10b981);">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--success, #10b981);">
                        {{ number_format($summary['sudah_konfirmasi'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">
                        Sudah Konfirmasi
                        <span style="font-size: var(--text-xs, 0.75rem); opacity: 0.8; display: block;">
                            ({{ $summary['sesuai'] }} sesuai &bull; {{ $summary['perlu_perbaikan'] }} usul perbaikan)
                        </span>
                    </div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b);">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--warning, #f59e0b);">
                        {{ number_format($summary['belum_konfirmasi'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Belum Konfirmasi</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                    <i class="fas fa-file-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--accent);">
                        {{ number_format($summary['berkas_lengkap'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Berkas Lengkap &amp; Valid</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(168,85,247,0.15); color: #a855f7;">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #a855f7;">
                        {{ number_format($summary['berkas_menunggu'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Menunggu Verifikasi</div>
                </div>
            </div>
        </div>

        <!-- Unified Datatable Validasi Data Siswa (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
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
                                        {{ $n }}</option>
                                @endforeach
                            </select>
                            <span>entri</span>
                        </div>

                        <!-- Filter Status Konfirmasi -->
                        <select id="filterKonfirmasi" name="konfirmasi" class="toolbar-filter-select"
                            style="height: 38px; min-width: 180px;">
                            <option value="" {{ in_array($konfirmasi, ['', 'semua'], true) ? 'selected' : '' }}>
                                Semua Siswa di Rombel
                            </option>
                            <option value="sudah_konfirmasi" {{ $konfirmasi === 'sudah_konfirmasi' ? 'selected' : '' }}>
                                Sudah Konfirmasi
                            </option>
                            <option value="belum_konfirmasi" {{ $konfirmasi === 'belum_konfirmasi' ? 'selected' : '' }}>
                                Belum Konfirmasi
                            </option>
                            <option value="sesuai" {{ $konfirmasi === 'sesuai' ? 'selected' : '' }}>
                                Konfirmasi: Sesuai
                            </option>
                            <option value="perlu_perbaikan" {{ $konfirmasi === 'perlu_perbaikan' ? 'selected' : '' }}>
                                Konfirmasi: Perlu Perbaikan
                            </option>
                            <option value="diverifikasi" {{ $konfirmasi === 'diverifikasi' ? 'selected' : '' }}>
                                Konfirmasi: Diverifikasi
                            </option>
                        </select>

                        <!-- Filter Status Berkas -->
                        <select id="filterBerkas" name="berkas" class="toolbar-filter-select"
                            style="height: 38px; min-width: 170px;">
                            <option value="" {{ $berkas === '' ? 'selected' : '' }}>Semua Status Berkas</option>
                            <option value="lengkap" {{ $berkas === 'lengkap' ? 'selected' : '' }}>Berkas Lengkap &amp;
                                Valid
                            </option>
                            <option value="menunggu" {{ $berkas === 'menunggu' ? 'selected' : '' }}>Ada Berkas Menunggu
                            </option>
                            <option value="tidak_valid" {{ $berkas === 'tidak_valid' ? 'selected' : '' }}>Ada Berkas
                                Ditolak
                            </option>
                            <option value="belum_lengkap" {{ $berkas === 'belum_lengkap' ? 'selected' : '' }}>Berkas Belum
                                Lengkap</option>
                        </select>

                        <!-- Filter Gender -->
                        <select id="filterGender" name="gender" class="toolbar-filter-select"
                            style="height: 38px; min-width: 125px;">
                            <option value="">Semua Gender</option>
                            <option value="L" {{ $gender === 'L' ? 'selected' : '' }}>Laki-Laki (L)</option>
                            <option value="P" {{ $gender === 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                        </select>

                        @if ($q || $gender || $berkas || ($konfirmasi && !in_array($konfirmasi, ['', 'semua'], true)))
                            <a href="{{ route('dashboard.wali-kelas.validasi.index', $isAdmin ? ['rombel_id' => $activeRombel?->rombongan_belajar_id] : []) }}"
                                class="btn btn-outline btn-reset-filter"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                title="Reset filter" data-live-reset>
                                <i class="fas fa-undo"></i>
                            </a>
                        @endif
                    </div>

                    <!-- Live Search Box -->
                    <div class="live-search-wrap" style="height: 38px;">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="liveSearch" name="q" placeholder="Cari nama / NISN / NIK..."
                            value="{{ $q }}" autocomplete="off">
                        <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}"
                            title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Konten Datatable Responsive Stack -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd table-validasi-data"
                    style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                            <th class="th-no d-none-mobile"
                                style="padding: 13px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 48px; text-align: center;">
                                No
                            </th>
                            <th class="sortable-th th-pd {{ $sort === 'nama' ? 'sorted' : '' }}"
                                style="padding: 13px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 240px;"
                                data-sort="nama">
                                Peserta Didik
                                <span class="sort-icon">{!! $sort === 'nama' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th th-konfirm {{ $sort === 'status_konfirmasi' || $sort === 'dikonfirmasi_pada' ? 'sorted' : '' }}"
                                style="padding: 13px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; min-width: 180px;"
                                data-sort="dikonfirmasi_pada">
                                Status Konfirmasi Data
                                <span class="sort-icon">{!! $sort === 'dikonfirmasi_pada' || $sort === 'status_konfirmasi'
                                    ? ($sortDir === 'asc'
                                        ? '&#9650;'
                                        : '&#9660;')
                                    : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="th-berkas"
                                style="padding: 13px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; min-width: 240px;">
                                Berkas Persyaratan (Akta &bull; KK &bull; Ijazah &bull; KTP)
                            </th>
                            <th class="th-kelengkapan"
                                style="padding: 13px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; min-width: 130px;">
                                Kelengkapan
                            </th>
                            <th class="th-aksi"
                                style="padding: 13px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; min-width: 180px;">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $index => $item)
                            @php
                                $rowNum = ($list->currentPage() - 1) * $list->perPage() + $index + 1;
                                $stKonf = $item->status_konfirmasi ?? 'belum_konfirmasi';

                                $aktaF = $item->berkas_items->get('akta_kelahiran');
                                $kkF = $item->berkas_items->get('kartu_keluarga');
                                $ijzF = $item->berkas_items->get('ijazah_smp');
                                $ktpF = $item->berkas_items->get('ktp_orang_tua');

                                $aktaVal = $aktaF?->status === 'valid' || !empty($item->fisik_verif?->akta_kelahiran);
                                $kkVal = $kkF?->status === 'valid' || !empty($item->fisik_verif?->kartu_keluarga);
                                $ijzVal = $ijzF?->status === 'valid' || !empty($item->fisik_verif?->ijazah_smp);
                                $ktpVal = $ktpF?->status === 'valid' || !empty($item->fisik_verif?->ktp_orang_tua);

                                $berkasSlots = [
                                    [
                                        'label' => 'Akta',
                                        'title' => 'Akta Kelahiran',
                                        'file' => $aktaF,
                                        'isValid' => $aktaVal,
                                    ],
                                    [
                                        'label' => 'KK',
                                        'title' => 'Kartu Keluarga (KK)',
                                        'file' => $kkF,
                                        'isValid' => $kkVal,
                                    ],
                                    [
                                        'label' => 'Ijazah',
                                        'title' => 'Ijazah SMP / SKL',
                                        'file' => $ijzF,
                                        'isValid' => $ijzVal,
                                    ],
                                    [
                                        'label' => 'KTP',
                                        'title' => 'KTP Orang Tua / Wali',
                                        'file' => $ktpF,
                                        'isValid' => $ktpVal,
                                    ],
                                ];
                            @endphp
                            <tr class="validasi-row"
                                style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                <!-- No Urut (Desktop Only) -->
                                <td class="cell-pd-no d-none-mobile" data-label="No"
                                    style="padding: 14px; text-align: center; color: var(--text-muted); font-size: var(--text-sm, 0.8125rem); font-weight: 700;">
                                    {{ $rowNum }}
                                </td>

                                <!-- Kolom Peserta Didik (Avatar & Identitas) -->
                                <td class="cell-pd-nama" data-label="Peserta Didik" style="padding: 14px 18px;">
                                    <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                        <div class="user-avatar pd-foto-thumb"
                                            style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); overflow: hidden; background: rgba(99,102,241,0.12); flex-shrink: 0; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-color);">
                                            @if ($item->foto_url)
                                                <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}"
                                                    style="width: 100%; height: 100%; object-fit: cover;"
                                                    onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-user-graduate text-primary\'></i>';">
                                            @else
                                                <i class="fas fa-user-graduate text-primary"
                                                    style="font-size: var(--text-xl, 1.25rem);"></i>
                                            @endif
                                        </div>
                                        <div class="pd-info" style="min-width: 0; flex: 1;">
                                            <div class="pd-title-row"
                                                style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                                <span
                                                    class="badge badge-outline badge-row-no d-none-desktop d-block-mobile"
                                                    style="font-size: var(--text-2xs, 0.6875rem); padding: 1px 6px; font-weight: 700; color: var(--text-muted);">
                                                    #{{ $rowNum }}
                                                </span>
                                                <div class="pd-nama"
                                                    style="font-weight: 800; font-size: var(--text-base, 0.875rem); color: var(--text-color); line-height: 1.35; word-break: break-word;">
                                                    {{ $item->nama }}
                                                </div>
                                                <span
                                                    class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}"
                                                    style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px; font-weight: 700; border-radius: var(--radius-xs, 4px);">
                                                    {{ $item->jenis_kelamin }}
                                                </span>
                                            </div>
                                            <div class="pd-meta-row"
                                                style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 3px;">
                                                <span>NISN: <strong>{{ $item->nisn ?: '-' }}</strong></span>
                                                @if ($item->nipd)
                                                    &bull; <span>NIPD: {{ $item->nipd }}</span>
                                                @endif
                                                @if (!empty($item->nama_rombel))
                                                    &bull; <span>{{ $item->nama_rombel }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kolom Status Konfirmasi Data Siswa -->
                                <td class="cell-pd-konfirmasi" data-label="Konfirmasi Data"
                                    style="padding: 14px 18px; text-align: center;">
                                    <div class="konfirmasi-wrapper"
                                        style="display: inline-flex; flex-direction: column; align-items: center; gap: 3px;">
                                        @if (in_array($stKonf, ['sesuai', 'diverifikasi', 'disinkronkan_dapodik'], true))
                                            <span class="badge badge-success"
                                                style="font-size: var(--text-sm, 0.8125rem); padding: 4px 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                                                <i class="fas fa-circle-check"></i> Sesuai &bull; Terkonfirmasi
                                            </span>
                                        @elseif ($stKonf === 'perlu_perbaikan')
                                            <span class="badge badge-warning"
                                                style="font-size: var(--text-sm, 0.8125rem); padding: 4px 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;"
                                                title="{{ $item->catatan_siswa ? 'Catatan Siswa: ' . $item->catatan_siswa : 'Siswa mengajukan koreksi perbaikan data' }}">
                                                <i class="fas fa-triangle-exclamation"></i> Perlu Perbaikan
                                            </span>
                                        @else
                                            <span class="badge badge-outline"
                                                style="font-size: var(--text-sm, 0.8125rem); padding: 4px 10px; font-weight: 700; background: rgba(245, 158, 11, 0.1); color: var(--warning, #d97706); border-color: rgba(245, 158, 11, 0.35); display: inline-flex; align-items: center; gap: 5px;">
                                                <i class="fas fa-clock"></i> Belum Konfirmasi
                                            </span>
                                        @endif

                                        @if (!empty($item->dikonfirmasi_pada))
                                            <div class="konfirm-time"
                                                style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); display: flex; align-items: center; gap: 4px; justify-content: center; margin-top: 2px;">
                                                <i class="far fa-calendar-check" style="font-size: var(--text-xs, 0.75rem);"></i>
                                                <span>{{ \Carbon\Carbon::parse($item->dikonfirmasi_pada)->translatedFormat('d M Y, H:i') }}</span>
                                            </div>
                                        @else
                                            <div class="konfirm-hint"
                                                style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                                Menunggu respon siswa
                                            </div>
                                        @endif

                                        @if (!empty($item->catatan_siswa) && $stKonf === 'perlu_perbaikan')
                                            <div class="catatan-siswa-preview"
                                                style="font-size: var(--text-xs, 0.75rem); color: var(--warning, #d97706); max-width: 210px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; margin-top: 2px;"
                                                title="{{ $item->catatan_siswa }}">
                                                <i class="fas fa-comment-dots me-1"></i> "{{ $item->catatan_siswa }}"
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Kolom Status 4 Berkas Wajib -->
                                <td class="cell-pd-berkas" data-label="Berkas Dokumen"
                                    style="padding: 14px 18px; text-align: center;">
                                    <div class="berkas-pill-group">
                                        @foreach ($berkasSlots as $slot)
                                            @php
                                                $bFile = $slot['file'];
                                                $bVal = $slot['isValid'];
                                                $bSt = $bVal ? 'valid' : $bFile?->status ?? 'belum';

                                                $bClass = match ($bSt) {
                                                    'valid' => 'valid',
                                                    'tidak_valid' => 'tidak_valid',
                                                    'menunggu' => 'menunggu',
                                                    default => 'belum',
                                                };
                                                $bIcon = match ($bSt) {
                                                    'valid' => 'fa-circle-check',
                                                    'tidak_valid' => 'fa-circle-xmark',
                                                    'menunggu' => 'fa-clock',
                                                    default => 'fa-minus',
                                                };
                                                $bText = match ($bSt) {
                                                    'valid' => 'Valid / Sesuai',
                                                    'tidak_valid' => 'Ditolak / Tidak Sesuai',
                                                    'menunggu' => 'Menunggu Verifikasi',
                                                    default => 'Belum Diunggah',
                                                };
                                            @endphp
                                            <div class="berkas-chip berkas-chip--{{ $bClass }}"
                                                title="{{ $slot['title'] }}: {{ $bText }}">
                                                <i class="fas {{ $bIcon }} berkas-chip-status-icon"></i>
                                                <span class="berkas-chip-label">{{ $slot['label'] }}</span>
                                                @if (!empty($bFile?->file_path))
                                                    <button type="button" class="btn-preview-pdf-inline berkas-chip-eye"
                                                        data-url="{{ route('dashboard.berkas.preview', $bFile->id) }}"
                                                        data-title="{{ $slot['title'] }} - {{ $item->nama }}"
                                                        title="Lihat Dokumen PDF {{ $slot['title'] }}">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </td>

                                <!-- Kolom Ringkasan Kelengkapan -->
                                <td class="cell-pd-kelengkapan" data-label="Kelengkapan"
                                    style="padding: 14px 18px; text-align: center;">
                                    <div class="kelengkapan-wrapper"
                                        style="display: inline-flex; flex-direction: column; align-items: center; gap: 3px;">
                                        @if ($item->is_berkas_lengkap)
                                            <span class="badge badge-success"
                                                style="font-size: var(--text-xs, 0.75rem); padding: 4px 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="fas fa-circle-check"></i> 4/4 Lengkap
                                            </span>
                                        @elseif ($item->menunggu_count > 0)
                                            <span class="badge badge-warning"
                                                style="font-size: var(--text-xs, 0.75rem); padding: 4px 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="fas fa-clock"></i> {{ $item->menunggu_count }} Menunggu
                                            </span>
                                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                                {{ $item->valid_wajib_count }}/4 Valid
                                            </div>
                                        @elseif ($item->tidak_valid_count > 0)
                                            <span class="badge badge-danger"
                                                style="font-size: var(--text-xs, 0.75rem); padding: 4px 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="fas fa-circle-exclamation"></i> {{ $item->tidak_valid_count }}
                                                Ditolak
                                            </span>
                                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                                {{ $item->valid_wajib_count }}/4 Valid
                                            </div>
                                        @else
                                            <span class="badge badge-outline"
                                                style="font-size: var(--text-xs, 0.75rem); padding: 4px 10px; font-weight: 600; opacity: 0.85;">
                                                {{ $item->valid_wajib_count }}/4 Valid
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Kolom Aksi -->
                                <td class="cell-pd-aksi" data-label="Aksi"
                                    style="padding: 14px 18px; text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end; gap: 6px;">
                                        <button type="button" class="btn-icon btn-validasi-berkas-detail"
                                            data-id="{{ $item->peserta_didik_id }}" data-nama="{{ $item->nama }}"
                                            title="Validasi &amp; Kelola Dokumen Berkas Digital Siswa"
                                            style="color: var(--primary);">
                                            <i class="fas fa-check-double"></i>
                                        </button>

                                        <a href="{{ route('dashboard.identitas.index', ['peserta_didik_id' => $item->peserta_didik_id]) }}"
                                            class="btn-icon btn-link-identitas"
                                            title="Periksa Formulir Identitas Lengkap Siswa"
                                            style="color: var(--text-muted);">
                                            <i class="fas fa-id-card"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="cell-empty"
                                    style="padding: 36px 20px; text-align: center; color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                                    <i class="fas fa-folder-open mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.4;"></i>
                                    <div style="font-weight: 700; color: var(--text-color); margin-bottom: 4px;">Tidak Ada
                                        Data Peserta Didik</div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); max-width: 480px; margin: 0 auto;">
                                        Tidak ada data peserta didik yang cocok dengan kriteria filter konfirmasi dan berkas
                                        yang dipilih pada rombel ini.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 3. Footer Paginasi Terpadu Datatable -->
            @if ($list->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $list])
                </div>
            @endif
        </div>
    @endif

    <!-- MODAL: VERIFIKASI BERKAS DIGITAL SISWA (PDF ONLY, 2 STATUS: VALID / TIDAK VALID) -->
    <div id="modalVerifikasiBerkasDigital" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 860px; width: 94%; max-height: 88vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div
                        style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <div>
                        <h3 id="modalVbNama"
                            style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0;">
                            Validasi Berkas Persyaratan Siswa</h3>
                        <div id="modalVbInfo" style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                            Memuat data siswa...
                        </div>
                    </div>
                </div>
                <button type="button" class="close-modal" data-target="#modalVerifikasiBerkasDigital"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="modalVbLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: var(--text-2xl, 1.5rem);"></i>
                <div>Memuat daftar berkas digital siswa...</div>
            </div>

            <div id="modalVbContent" style="overflow-y: auto; flex: 1; display: none; padding-right: 4px;">
                <!-- Diisi secara dinamis via JavaScript -->
            </div>

            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    <i class="fas fa-circle-info text-primary me-1"></i>
                    Status validasi berkas: <strong>Valid / Sesuai</strong> atau <strong>Tidak Valid / Tidak
                        Sesuai</strong>.
                </div>
                <button type="button" class="btn btn-outline close-modal" data-target="#modalVerifikasiBerkasDigital"
                    style="border-radius: var(--radius-md, 8px); padding: 7px 16px; font-size: var(--text-base, 0.875rem);">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL: VIEWER DOKUMEN PDF TERPADU (TANPA MEMBUKA TAB BARU) -->
    <div id="modalPdfViewer" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.78); z-index: 100005 !important; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="card"
            style="max-width: 960px; width: 96%; height: 90vh; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 18px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div
                        style="width: 38px; height: 38px; border-radius: var(--radius-md, 8px); background: rgba(239,68,68,0.12); color: var(--danger, #ef4444); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div>
                        <h3 id="pdfViewerTitle"
                            style="font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color); margin: 0;">
                            Pratinjau Dokumen PDF</h3>
                        <div id="pdfViewerSubtitle"
                            style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                            Review berkas langsung di dalam sistem tanpa membuka tab baru
                        </div>
                    </div>
                </div>
                <button type="button" class="close-pdf-viewer"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem); padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div
                style="flex: 1; min-height: 0; border-radius: var(--radius-lg, 12px); overflow: hidden; background: var(--text-main, #0f172a); border: 1px solid var(--border-color); position: relative;">
                <iframe id="pdfViewerFrame" src="" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>

            <!-- Action Bar Validasi Terpadu Langsung di Pratinjau PDF -->
            <div id="pdfViewerActionBar"
                style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--border-color); flex-wrap: wrap; gap: 10px;">
                <div id="pdfViewerVerifControls" style="display: none; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <span style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color);">
                        <i class="fas fa-clipboard-check text-primary me-1"></i> Validasi:
                    </span>
                    <label
                        style="display: inline-flex; align-items: center; gap: 6px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--success, #10b981); cursor: pointer; margin: 0;">
                        <input type="radio" name="pdf_viewer_status" value="valid" id="pdfViewerRadioValid"
                            class="pdf-viewer-radio">
                        <i class="fas fa-circle-check"></i> Valid / Sesuai
                    </label>
                    <label
                        style="display: inline-flex; align-items: center; gap: 6px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--danger, #ef4444); cursor: pointer; margin: 0;">
                        <input type="radio" name="pdf_viewer_status" value="tidak_valid" id="pdfViewerRadioTidakValid"
                            class="pdf-viewer-radio">
                        <i class="fas fa-circle-xmark"></i> Tidak Valid / Tidak Sesuai
                    </label>
                    <input type="text" id="pdfViewerCatatan" placeholder="Catatan penolakan jika tidak valid..."
                        style="display: none; font-size: var(--text-sm, 0.8125rem); height: 32px; padding: 4px 10px; border-radius: var(--radius-sm, 6px); border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color); width: 260px;">
                    <button type="button" id="btnPdfViewerSimpan" class="btn btn-primary"
                        style="padding: 6px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; border-radius: var(--radius-sm, 6px); display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-save"></i> Simpan Status
                    </button>
                </div>
                <div style="display: flex; gap: 8px; margin-left: auto;">
                    <button type="button" class="btn btn-outline close-pdf-viewer"
                        style="border-radius: var(--radius-md, 8px); padding: 7px 18px; font-size: var(--text-sm, 0.8125rem);">
                        Tutup Pratinjau
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet"
        href="{{ asset('css/wali-kelas.css') }}?v={{ file_exists(public_path('css/wali-kelas.css')) ? filemtime(public_path('css/wali-kelas.css')) : time() }}">
@endpush

@push('scripts')
    <script
        src="{{ asset('js/wali-kelas-validasi.js') }}?v={{ file_exists(public_path('js/wali-kelas-validasi.js')) ? filemtime(public_path('js/wali-kelas-validasi.js')) : time() }}">
    </script>
@endpush
