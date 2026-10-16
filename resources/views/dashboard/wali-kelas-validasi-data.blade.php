@extends('layouts.dashboard')

@section('title', 'Wali Kelas — Validasi Data — SAE')
@section('dash_title', 'Wali Kelas — Validasi Data')

@section('content')
    <!-- Header Banner -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-user-check text-primary me-2"></i> Wali Kelas — Validasi Data Peserta Didik
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
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
                        style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted); margin: 0; white-space: nowrap;">
                        <i class="fas fa-filter me-1"></i> Pilih Rombel:
                    </label>
                    <select id="adminRombelSelect" class="form-control"
                        style="font-size: 0.85rem; padding: 6px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color); min-width: 170px;">
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
                style="width: 70px; height: 70px; border-radius: 50%; background: rgba(245, 158, 11, 0.15); color: #f59e0b; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 16px;">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-color); margin-bottom: 8px;">Belum Ada Rombel
                Binaan Terdaftar</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem; max-width: 540px; margin: 0 auto 20px;">
                Akun Anda belum terikat dengan rombongan belajar aktif sebagai Wali Kelas pada data Dapodik sekolah. Silakan
                hubungi Administrator sistem atau Operator Dapodik untuk pembaruan penugasan tugas tambahan.
            </p>
        </div>
    @else
        <!-- Summary Stats Grid (Klik untuk Filter Cepat) -->
        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); margin-bottom: 20px;">
            <a href="{{ route('dashboard.wali-kelas.validasi.index', array_filter(['rombel_id' => $isAdmin ? $activeRombel?->rombongan_belajar_id : null])) }}"
                class="dash-stat-card" style="text-decoration: none; color: inherit; cursor: pointer; transition: transform 0.15s ease;">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ number_format($summary['total'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Total Peserta Didik</div>
                </div>
            </a>

            <a href="{{ route('dashboard.wali-kelas.validasi.index', array_filter(['rombel_id' => $isAdmin ? $activeRombel?->rombongan_belajar_id : null, 'konfirmasi' => 'sudah_konfirmasi'])) }}"
                class="dash-stat-card" style="text-decoration: none; color: inherit; cursor: pointer; transition: transform 0.15s ease;">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #10b981;">
                        {{ number_format($summary['sudah_konfirmasi'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">
                        Sudah Konfirmasi
                        <span style="font-size: 0.72rem; opacity: 0.8; display: block;">
                            ({{ $summary['sesuai'] }} sesuai &bull; {{ $summary['perlu_perbaikan'] }} usul perbaikan)
                        </span>
                    </div>
                </div>
            </a>

            <a href="{{ route('dashboard.wali-kelas.validasi.index', array_filter(['rombel_id' => $isAdmin ? $activeRombel?->rombongan_belajar_id : null, 'konfirmasi' => 'belum_konfirmasi'])) }}"
                class="dash-stat-card" style="text-decoration: none; color: inherit; cursor: pointer; transition: transform 0.15s ease;">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #f59e0b;">
                        {{ number_format($summary['belum_konfirmasi'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Belum Konfirmasi</div>
                </div>
            </a>

            <a href="{{ route('dashboard.wali-kelas.validasi.index', array_filter(['rombel_id' => $isAdmin ? $activeRombel?->rombongan_belajar_id : null, 'berkas' => 'lengkap'])) }}"
                class="dash-stat-card" style="text-decoration: none; color: inherit; cursor: pointer; transition: transform 0.15s ease;">
                <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                    <i class="fas fa-file-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: var(--accent);">
                        {{ number_format($summary['berkas_lengkap'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Berkas Lengkap &amp; Valid</div>
                </div>
            </a>

            <a href="{{ route('dashboard.wali-kelas.validasi.index', array_filter(['rombel_id' => $isAdmin ? $activeRombel?->rombongan_belajar_id : null, 'berkas' => 'menunggu'])) }}"
                class="dash-stat-card" style="text-decoration: none; color: inherit; cursor: pointer; transition: transform 0.15s ease;">
                <div class="dash-stat-icon" style="background: rgba(168,85,247,0.15); color: #a855f7;">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #a855f7;">
                        {{ number_format($summary['berkas_menunggu'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Menunggu Verifikasi</div>
                </div>
            </a>
        </div>

        <!-- Quick Filter Pills: Monitoring Langsung Seluruh Siswa Rombel -->
        <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; align-items: center;">
            <a href="{{ route('dashboard.wali-kelas.validasi.index', array_filter(['rombel_id' => $isAdmin ? $activeRombel?->rombongan_belajar_id : null, 'berkas' => $berkas])) }}"
                class="btn btn-sm {{ in_array($konfirmasi, ['', 'semua'], true) ? 'btn-primary' : 'btn-outline' }}"
                style="border-radius: 20px; padding: 6px 14px; font-size: 0.8rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-users"></i> Semua Siswa ({{ number_format($summary['total']) }})
            </a>
            <a href="{{ route('dashboard.wali-kelas.validasi.index', array_filter(['rombel_id' => $isAdmin ? $activeRombel?->rombongan_belajar_id : null, 'konfirmasi' => 'sudah_konfirmasi', 'berkas' => $berkas])) }}"
                class="btn btn-sm {{ $konfirmasi === 'sudah_konfirmasi' ? 'btn-success text-white' : 'btn-outline' }}"
                style="border-radius: 20px; padding: 6px 14px; font-size: 0.8rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-circle-check"></i> Sudah Konfirmasi ({{ number_format($summary['sudah_konfirmasi']) }})
            </a>
            <a href="{{ route('dashboard.wali-kelas.validasi.index', array_filter(['rombel_id' => $isAdmin ? $activeRombel?->rombongan_belajar_id : null, 'konfirmasi' => 'belum_konfirmasi', 'berkas' => $berkas])) }}"
                class="btn btn-sm {{ $konfirmasi === 'belum_konfirmasi' ? 'btn-warning text-dark' : 'btn-outline' }}"
                style="border-radius: 20px; padding: 6px 14px; font-size: 0.8rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-clock"></i> Belum Konfirmasi ({{ number_format($summary['belum_konfirmasi']) }})
            </a>
            <a href="{{ route('dashboard.wali-kelas.validasi.index', array_filter(['rombel_id' => $isAdmin ? $activeRombel?->rombongan_belajar_id : null, 'konfirmasi' => 'perlu_perbaikan', 'berkas' => $berkas])) }}"
                class="btn btn-sm {{ $konfirmasi === 'perlu_perbaikan' ? 'btn-danger text-white' : 'btn-outline' }}"
                style="border-radius: 20px; padding: 6px 14px; font-size: 0.8rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-triangle-exclamation"></i> Usul Perbaikan ({{ number_format($summary['perlu_perbaikan']) }})
            </a>
            <a href="{{ route('dashboard.wali-kelas.validasi.index', array_filter(['rombel_id' => $isAdmin ? $activeRombel?->rombongan_belajar_id : null, 'konfirmasi' => $konfirmasi, 'berkas' => 'menunggu'])) }}"
                class="btn btn-sm {{ $berkas === 'menunggu' ? 'btn-primary' : 'btn-outline' }}"
                style="border-radius: 20px; padding: 6px 14px; font-size: 0.8rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-hourglass-half"></i> Berkas Menunggu ({{ number_format($summary['berkas_menunggu']) }})
            </a>
        </div>

        <!-- Toolbar & Filter -->
        <div class="card" style="padding: 16px; margin-bottom: 20px;">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <div class="toolbar-entries">
                        <label for="perPageSelect"
                            style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">Tampilkan</label>
                        <select id="perPageSelect" class="per-page-select">
                            @foreach ([10, 15, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>
                                    {{ $n }}</option>
                            @endforeach
                        </select>
                        <span style="font-size: 0.85rem; color: var(--text-muted);">entri</span>
                    </div>

                    <!-- Filter Status Konfirmasi -->
                    <select id="filterKonfirmasi" class="toolbar-filter-select" style="min-width: 180px;">
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
                    <select id="filterBerkas" class="toolbar-filter-select" style="min-width: 170px;">
                        <option value="" {{ $berkas === '' ? 'selected' : '' }}>Semua Status Berkas</option>
                        <option value="lengkap" {{ $berkas === 'lengkap' ? 'selected' : '' }}>Berkas Lengkap &amp; Valid
                        </option>
                        <option value="menunggu" {{ $berkas === 'menunggu' ? 'selected' : '' }}>Ada Berkas Menunggu
                        </option>
                        <option value="tidak_valid" {{ $berkas === 'tidak_valid' ? 'selected' : '' }}>Ada Berkas Ditolak
                        </option>
                        <option value="belum_lengkap" {{ $berkas === 'belum_lengkap' ? 'selected' : '' }}>Berkas Belum
                            Lengkap</option>
                    </select>

                    <!-- Filter Gender -->
                    <select id="filterGender" class="toolbar-filter-select" style="min-width: 125px;">
                        <option value="">Semua Gender</option>
                        <option value="L" {{ $gender === 'L' ? 'selected' : '' }}>Laki-Laki (L)</option>
                        <option value="P" {{ $gender === 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                    </select>

                    @if ($q || $gender || $berkas || ($konfirmasi && !in_array($konfirmasi, ['', 'semua'], true)))
                        <a href="{{ route('dashboard.wali-kelas.validasi.index', $isAdmin ? ['rombel_id' => $activeRombel?->rombongan_belajar_id] : []) }}"
                            class="btn btn-outline" style="padding: 7px 12px; font-size: 0.84rem;" title="Reset filter">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <!-- Live Search Box -->
                <div class="live-search-wrap">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" placeholder="Cari nama / NISN / NIK..."
                        value="{{ $q }}" autocomplete="off">
                    <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}"
                        title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Datatable Validasi Data Siswa (Baku SAE: table-responsive-stack & table-pd) -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th
                            style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 44px; text-align: center;">
                            No
                        </th>
                        <th class="sortable-th {{ $sort === 'nama' ? 'sorted' : '' }}"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;"
                            data-sort="nama">
                            Peserta Didik
                            <span class="sort-icon">{!! $sort === 'nama' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th class="sortable-th {{ $sort === 'status_konfirmasi' || $sort === 'dikonfirmasi_pada' ? 'sorted' : '' }}"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;"
                            data-sort="dikonfirmasi_pada">
                            Status Konfirmasi Data
                            <span class="sort-icon">{!! $sort === 'dikonfirmasi_pada' || $sort === 'status_konfirmasi'
                                ? ($sortDir === 'asc'
                                    ? '&#9650;'
                                    : '&#9660;')
                                : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                            Berkas Persyaratan (Akta &bull; KK &bull; Ijazah &bull; KTP)
                        </th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                            Kelengkapan
                        </th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
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
                        @endphp
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <!-- No Urut -->
                            <td
                                style="padding: 14px; text-align: center; color: var(--text-muted); font-size: 0.82rem; font-weight: 600;">
                                {{ $rowNum }}
                            </td>

                            <!-- Kolom Peserta Didik (Avatar & Identitas) -->
                            <td class="cell-pd-nama" style="padding: 14px 18px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="user-avatar"
                                        style="width: 42px; height: 42px; border-radius: 10px; overflow: hidden; background: rgba(99,102,241,0.12); flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                                        @if ($item->foto_url)
                                            <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}"
                                                style="width: 100%; height: 100%; object-fit: cover;"
                                                onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-user-graduate text-primary\'></i>';">
                                        @else
                                            <i class="fas fa-user-graduate text-primary" style="font-size: 1.15rem;"></i>
                                        @endif
                                    </div>
                                    <div style="min-width: 0;">
                                        <div
                                            style="font-weight: 800; font-size: 0.92rem; color: var(--text-color); margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            {{ $item->nama }}
                                        </div>
                                        <div
                                            style="font-size: 0.77rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span>NISN: <strong>{{ $item->nisn ?: '-' }}</strong></span>
                                            @if ($item->nipd)
                                                &bull; <span>NIPD: {{ $item->nipd }}</span>
                                            @endif
                                            <span
                                                class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}"
                                                style="font-size: 0.65rem; padding: 1px 5px; font-weight: 700; border-radius: 4px;">
                                                {{ $item->jenis_kelamin }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Kolom Status Konfirmasi Data Siswa -->
                            <td style="padding: 14px 18px; text-align: center;">
                                @if (in_array($stKonf, ['sesuai', 'diverifikasi', 'disinkronkan_dapodik'], true))
                                    <span class="badge badge-success"
                                        style="font-size: 0.78rem; padding: 4px 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                                        <i class="fas fa-circle-check"></i> Sesuai &bull; Terkonfirmasi
                                    </span>
                                @elseif ($stKonf === 'perlu_perbaikan')
                                    <span class="badge badge-warning"
                                        style="font-size: 0.78rem; padding: 4px 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;"
                                        title="{{ $item->catatan_siswa ? 'Catatan Siswa: ' . $item->catatan_siswa : 'Siswa mengajukan koreksi perbaikan data' }}">
                                        <i class="fas fa-triangle-exclamation"></i> Perlu Perbaikan
                                    </span>
                                @else
                                    <span class="badge badge-outline"
                                        style="font-size: 0.78rem; padding: 4px 10px; font-weight: 700; background: rgba(245, 158, 11, 0.1); color: #d97706; border-color: rgba(245, 158, 11, 0.35); display: inline-flex; align-items: center; gap: 5px;">
                                        <i class="fas fa-clock"></i> Belum Konfirmasi
                                    </span>
                                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 3px;">
                                        Menunggu pemeriksaan siswa
                                    </div>
                                @endif

                                @if (!empty($item->dikonfirmasi_pada))
                                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 4px;">
                                        <i class="fas fa-calendar-check me-1"></i>
                                        {{ \Carbon\Carbon::parse($item->dikonfirmasi_pada)->translatedFormat('d M Y, H:i') }}
                                    </div>
                                @endif

                                @if (!empty($item->catatan_siswa) && $stKonf === 'perlu_perbaikan')
                                    <div style="font-size: 0.7rem; color: #d97706; max-width: 220px; margin: 3px auto 0; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"
                                        title="{{ $item->catatan_siswa }}">
                                        <i class="fas fa-comment-dots me-1"></i> "{{ $item->catatan_siswa }}"
                                    </div>
                                @endif
                            </td>

                            <!-- Kolom Status 4 Berkas Wajib -->
                            <td style="padding: 14px 18px; text-align: center;">
                                <div
                                    style="display: inline-flex; gap: 8px; align-items: center; justify-content: center; flex-wrap: wrap;">
                                    {{-- 1. Akta Kelahiran --}}
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 2px;">
                                        @if ($aktaVal)
                                            <span class="badge badge-success"
                                                style="font-size: 0.68rem; padding: 3px 7px;"
                                                title="Akta Kelahiran: Valid">
                                                <i class="fas fa-certificate"></i> Akta
                                            </span>
                                        @elseif ($aktaF?->status === 'tidak_valid')
                                            <span class="badge badge-danger" style="font-size: 0.68rem; padding: 3px 7px;"
                                                title="Akta Kelahiran: Ditolak / Tidak Valid">
                                                <i class="fas fa-times-circle"></i> Akta
                                            </span>
                                        @elseif ($aktaF?->status === 'menunggu')
                                            <span class="badge badge-warning"
                                                style="font-size: 0.68rem; padding: 3px 7px;"
                                                title="Akta Kelahiran: Menunggu Verifikasi">
                                                <i class="fas fa-clock"></i> Akta
                                            </span>
                                        @else
                                            <span class="badge badge-outline"
                                                style="font-size: 0.68rem; padding: 3px 7px; opacity: 0.5;"
                                                title="Akta Kelahiran: Belum Diunggah">
                                                <i class="fas fa-minus"></i> Akta
                                            </span>
                                        @endif
                                        @if (!empty($aktaF?->file_path))
                                            <a href="javascript:void(0)" class="btn-preview-pdf-inline"
                                                data-url="{{ route('dashboard.berkas.preview', $aktaF->id) }}"
                                                data-title="Akta Kelahiran - {{ $item->nama }}"
                                                style="font-size: 0.65rem; color: var(--primary); text-decoration: none;"
                                                title="Pratinjau PDF">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @endif
                                    </div>

                                    {{-- 2. Kartu Keluarga (KK) --}}
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 2px;">
                                        @if ($kkVal)
                                            <span class="badge badge-success"
                                                style="font-size: 0.68rem; padding: 3px 7px;"
                                                title="Kartu Keluarga (KK): Valid">
                                                <i class="fas fa-people-roof"></i> KK
                                            </span>
                                        @elseif ($kkF?->status === 'tidak_valid')
                                            <span class="badge badge-danger" style="font-size: 0.68rem; padding: 3px 7px;"
                                                title="Kartu Keluarga (KK): Ditolak / Tidak Valid">
                                                <i class="fas fa-times-circle"></i> KK
                                            </span>
                                        @elseif ($kkF?->status === 'menunggu')
                                            <span class="badge badge-warning"
                                                style="font-size: 0.68rem; padding: 3px 7px;"
                                                title="Kartu Keluarga (KK): Menunggu Verifikasi">
                                                <i class="fas fa-clock"></i> KK
                                            </span>
                                        @else
                                            <span class="badge badge-outline"
                                                style="font-size: 0.68rem; padding: 3px 7px; opacity: 0.5;"
                                                title="Kartu Keluarga (KK): Belum Diunggah">
                                                <i class="fas fa-minus"></i> KK
                                            </span>
                                        @endif
                                        @if (!empty($kkF?->file_path))
                                            <a href="javascript:void(0)" class="btn-preview-pdf-inline"
                                                data-url="{{ route('dashboard.berkas.preview', $kkF->id) }}"
                                                data-title="Kartu Keluarga - {{ $item->nama }}"
                                                style="font-size: 0.65rem; color: var(--primary); text-decoration: none;"
                                                title="Pratinjau PDF">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @endif
                                    </div>

                                    {{-- 3. Ijazah SMP / SKL --}}
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 2px;">
                                        @if ($ijzVal)
                                            <span class="badge badge-success"
                                                style="font-size: 0.68rem; padding: 3px 7px;"
                                                title="Ijazah SMP / SKL: Valid">
                                                <i class="fas fa-graduation-cap"></i> Ijazah
                                            </span>
                                        @elseif ($ijzF?->status === 'tidak_valid')
                                            <span class="badge badge-danger" style="font-size: 0.68rem; padding: 3px 7px;"
                                                title="Ijazah SMP / SKL: Ditolak / Tidak Valid">
                                                <i class="fas fa-times-circle"></i> Ijazah
                                            </span>
                                        @elseif ($ijzF?->status === 'menunggu')
                                            <span class="badge badge-warning"
                                                style="font-size: 0.68rem; padding: 3px 7px;"
                                                title="Ijazah SMP / SKL: Menunggu Verifikasi">
                                                <i class="fas fa-clock"></i> Ijazah
                                            </span>
                                        @else
                                            <span class="badge badge-outline"
                                                style="font-size: 0.68rem; padding: 3px 7px; opacity: 0.5;"
                                                title="Ijazah SMP / SKL: Belum Diunggah">
                                                <i class="fas fa-minus"></i> Ijazah
                                            </span>
                                        @endif
                                        @if (!empty($ijzF?->file_path))
                                            <a href="javascript:void(0)" class="btn-preview-pdf-inline"
                                                data-url="{{ route('dashboard.berkas.preview', $ijzF->id) }}"
                                                data-title="Ijazah SMP - {{ $item->nama }}"
                                                style="font-size: 0.65rem; color: var(--primary); text-decoration: none;"
                                                title="Pratinjau PDF">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @endif
                                    </div>

                                    {{-- 4. KTP Orang Tua --}}
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 2px;">
                                        @if ($ktpVal)
                                            <span class="badge badge-success"
                                                style="font-size: 0.68rem; padding: 3px 7px;"
                                                title="KTP Orang Tua / Wali: Valid">
                                                <i class="fas fa-id-card"></i> KTP
                                            </span>
                                        @elseif ($ktpF?->status === 'tidak_valid')
                                            <span class="badge badge-danger" style="font-size: 0.68rem; padding: 3px 7px;"
                                                title="KTP Orang Tua / Wali: Ditolak / Tidak Valid">
                                                <i class="fas fa-times-circle"></i> KTP
                                            </span>
                                        @elseif ($ktpF?->status === 'menunggu')
                                            <span class="badge badge-warning"
                                                style="font-size: 0.68rem; padding: 3px 7px;"
                                                title="KTP Orang Tua / Wali: Menunggu Verifikasi">
                                                <i class="fas fa-clock"></i> KTP
                                            </span>
                                        @else
                                            <span class="badge badge-outline"
                                                style="font-size: 0.68rem; padding: 3px 7px; opacity: 0.5;"
                                                title="KTP Orang Tua / Wali: Belum Diunggah">
                                                <i class="fas fa-minus"></i> KTP
                                            </span>
                                        @endif
                                        @if (!empty($ktpF?->file_path))
                                            <a href="javascript:void(0)" class="btn-preview-pdf-inline"
                                                data-url="{{ route('dashboard.berkas.preview', $ktpF->id) }}"
                                                data-title="KTP Orang Tua - {{ $item->nama }}"
                                                style="font-size: 0.65rem; color: var(--primary); text-decoration: none;"
                                                title="Pratinjau PDF">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Kolom Ringkasan Kelengkapan -->
                            <td style="padding: 14px 18px; text-align: center;">
                                @if ($item->is_berkas_lengkap)
                                    <span class="badge badge-success"
                                        style="font-size: 0.75rem; padding: 4px 10px; font-weight: 700;">
                                        <i class="fas fa-check-circle me-1"></i> 4/4 Lengkap
                                    </span>
                                @elseif ($item->menunggu_count > 0)
                                    <span class="badge badge-warning"
                                        style="font-size: 0.75rem; padding: 4px 10px; font-weight: 700;">
                                        <i class="fas fa-clock me-1"></i> {{ $item->menunggu_count }} Menunggu
                                    </span>
                                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">
                                        {{ $item->valid_wajib_count }}/4 Valid
                                    </div>
                                @elseif ($item->tidak_valid_count > 0)
                                    <span class="badge badge-danger"
                                        style="font-size: 0.75rem; padding: 4px 10px; font-weight: 700;">
                                        <i class="fas fa-circle-exclamation me-1"></i> {{ $item->tidak_valid_count }}
                                        Ditolak
                                    </span>
                                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">
                                        {{ $item->valid_wajib_count }}/4 Valid
                                    </div>
                                @else
                                    <span class="badge badge-outline"
                                        style="font-size: 0.75rem; padding: 4px 10px; font-weight: 600; opacity: 0.8;">
                                        {{ $item->valid_wajib_count }}/4 Valid
                                    </span>
                                @endif
                            </td>

                            <!-- Kolom Aksi -->
                            <td style="padding: 14px 18px; text-align: right;">
                                <div
                                    style="display: flex; gap: 8px; justify-content: flex-end; align-items: center; flex-wrap: wrap;">
                                    <button type="button" class="btn btn-primary btn-sm btn-validasi-berkas-detail"
                                        data-id="{{ $item->peserta_didik_id }}" data-nama="{{ $item->nama }}"
                                        title="Validasi &amp; Kelola Dokumen Berkas Digital Siswa"
                                        style="padding: 6px 14px; font-size: 0.78rem; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-check-double"></i> Validasi Berkas
                                    </button>

                                    <a href="{{ route('dashboard.identitas.index', ['peserta_didik_id' => $item->peserta_didik_id]) }}"
                                        class="btn btn-outline btn-sm" title="Periksa Formulir Identitas Lengkap Siswa"
                                        style="padding: 6px 12px; font-size: 0.78rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 5px;">
                                        <i class="fas fa-id-card text-muted"></i> Identitas
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6"
                                style="padding: 36px 20px; text-align: center; color: var(--text-muted); font-size: 0.88rem;">
                                <i class="fas fa-folder-open mb-2" style="font-size: 2rem; opacity: 0.4;"></i>
                                <div style="font-weight: 700; color: var(--text-color); margin-bottom: 4px;">Tidak Ada Data
                                    Peserta Didik</div>
                                <div style="font-size: 0.8rem; max-width: 480px; margin: 0 auto;">
                                    Tidak ada data peserta didik yang cocok dengan kriteria filter konfirmasi dan berkas
                                    yang dipilih pada rombel ini.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Footer Keterangan Entri -->
            @if ($total > 0)
                <div
                    style="padding: 14px 18px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div class="total-count-text" style="font-size: 0.83rem; color: var(--text-muted);">
                        Menampilkan <strong>{{ $list->firstItem() ?: 0 }}</strong> sampai
                        <strong>{{ $list->lastItem() ?: 0 }}</strong> dari
                        <strong>{{ number_format($total, 0, ',', '.') }}</strong> peserta didik
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
                    <a href="{{ $list->nextPageUrl() }}" class="page-btn" title="Berikutnya"><i
                            class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif
    @endif

    <!-- MODAL: VERIFIKASI BERKAS DIGITAL SISWA (PDF ONLY, 2 STATUS: VALID / TIDAK VALID) -->
    <div id="modalVerifikasiBerkasDigital" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 860px; width: 94%; max-height: 88vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div
                        style="width: 44px; height: 44px; border-radius: 10px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0;">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <div>
                        <h3 id="modalVbNama"
                            style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0;">
                            Validasi Berkas Persyaratan Siswa</h3>
                        <div id="modalVbInfo" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                            Memuat data siswa...
                        </div>
                    </div>
                </div>
                <button type="button" class="close-modal" data-target="#modalVerifikasiBerkasDigital"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="modalVbLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: 1.4rem;"></i>
                <div>Memuat daftar berkas digital siswa...</div>
            </div>

            <div id="modalVbContent" style="overflow-y: auto; flex: 1; display: none; padding-right: 4px;">
                <!-- Diisi secara dinamis via JavaScript -->
            </div>

            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                <div style="font-size: 0.76rem; color: var(--text-muted);">
                    <i class="fas fa-circle-info text-primary me-1"></i>
                    Status validasi berkas: <strong>Valid / Sesuai</strong> atau <strong>Tidak Valid / Tidak
                        Sesuai</strong>.
                </div>
                <button type="button" class="btn btn-outline close-modal" data-target="#modalVerifikasiBerkasDigital"
                    style="border-radius: 8px; padding: 7px 16px; font-size: 0.84rem;">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL: VIEWER DOKUMEN PDF TERPADU (TANPA MEMBUKA TAB BARU) -->
    <div id="modalPdfViewer" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.78); z-index: 100005 !important; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="card"
            style="max-width: 960px; width: 96%; height: 90vh; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 18px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div
                        style="width: 38px; height: 38px; border-radius: 8px; background: rgba(239,68,68,0.12); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0;">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div>
                        <h3 id="pdfViewerTitle"
                            style="font-size: 1rem; font-weight: 800; color: var(--text-color); margin: 0;">
                            Pratinjau Dokumen PDF</h3>
                        <div id="pdfViewerSubtitle"
                            style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                            Review berkas langsung di dalam sistem tanpa membuka tab baru
                        </div>
                    </div>
                </div>
                <button type="button" class="close-pdf-viewer"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div
                style="flex: 1; min-height: 0; border-radius: 10px; overflow: hidden; background: #0f172a; border: 1px solid var(--border-color); position: relative;">
                <iframe id="pdfViewerFrame" src="" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>

            <div
                style="display: flex; justify-content: flex-end; margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-outline close-pdf-viewer"
                    style="border-radius: 8px; padding: 7px 18px; font-size: 0.82rem;">
                    Tutup Pratinjau
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script
        src="{{ asset('js/wali-kelas-validasi.js') }}?v={{ file_exists(public_path('js/wali-kelas-validasi.js')) ? filemtime(public_path('js/wali-kelas-validasi.js')) : time() }}">
    </script>
@endpush
