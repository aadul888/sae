@extends('layouts.dashboard')

@section('title', 'Pengaturan Sistem Persuratan & Master Indeks — SAE')
@section('dash_title', 'Pengaturan Persuratan')

@section('content')
<div id="persuratanPengaturanContainer"
     data-test-hdd-url="{{ route('dashboard.persuratan.pengaturan.test-hdd') }}"
     data-indeks-store-url="{{ route('dashboard.persuratan.pengaturan.indeks.store') }}"
     data-indeks-base-url="{{ url('/dashboard/persuratan/pengaturan/indeks') }}"
     data-active-tab="{{ $tab ?? 'referensi' }}">

    <!-- 1. Header Banner & Navigasi Cepat -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                <i class="fas fa-sliders"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Pengaturan Persuratan &amp; Referensi Indeks
                </h2>
                <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                    Kelola master referensi indeks klasifikasi nomor surat dan konfigurasi penyimpanan arsip harddisk (HDD).
                </div>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('dashboard.persuratan.masuk.index') }}" class="btn btn-outline" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;" title="Buku Agenda Surat Masuk">
                <i class="fas fa-inbox text-primary"></i>
            </a>
            <a href="{{ route('dashboard.persuratan.keluar.index') }}" class="btn btn-outline" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;" title="Buku Agenda Surat Keluar">
                <i class="fas fa-paper-plane text-success"></i>
            </a>
        </div>
    </div>

    <!-- 2. Flash Messages -->
    @if (session('success'))
        <div style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- 3. Navigasi 2 Tab: Format & Indeks vs Penyimpanan HDD -->
    <div class="dash-tabs" style="display: flex; gap: 8px; border-bottom: 2px solid var(--border-color); margin-bottom: 22px;">
        <button type="button" class="dash-tab-btn {{ ($tab ?? 'referensi') === 'referensi' ? 'active' : '' }}" data-target="#tab-referensi"
                style="padding: 12px 20px; font-weight: 700; font-size: 0.9rem; border: none; background: none; color: {{ ($tab ?? 'referensi') === 'referensi' ? 'var(--primary)' : 'var(--text-muted)' }}; border-bottom: 2px solid {{ ($tab ?? 'referensi') === 'referensi' ? 'var(--primary)' : 'transparent' }}; margin-bottom: -2px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s ease;">
            <i class="fas fa-hashtag"></i>
            <span>Format Penomoran &amp; Indeks Surat</span>
            <span class="badge-compact" style="background: rgba(99,102,241,0.12); color: var(--primary); font-size: 0.75rem;">{{ $indeksList->total() }} Indeks</span>
        </button>
        <button type="button" class="dash-tab-btn {{ ($tab ?? 'referensi') === 'pengaturan' ? 'active' : '' }}" data-target="#tab-pengaturan"
                style="padding: 12px 20px; font-weight: 700; font-size: 0.9rem; border: none; background: none; color: {{ ($tab ?? 'referensi') === 'pengaturan' ? 'var(--primary)' : 'var(--text-muted)' }}; border-bottom: 2px solid {{ ($tab ?? 'referensi') === 'pengaturan' ? 'var(--primary)' : 'transparent' }}; margin-bottom: -2px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s ease;">
            <i class="fas fa-hard-drive"></i>
            <span>Penyimpanan &amp; Arsip HDD</span>
            <span class="badge-compact {{ $hddStatus['is_ready'] ? 'badge-success' : 'badge-danger' }}" style="font-size: 0.75rem;">
                <i class="fas {{ $hddStatus['is_ready'] ? 'fa-check-circle' : 'fa-triangle-exclamation' }}"></i>
                {{ $hddStatus['is_ready'] ? 'HDD Siap' : 'HDD Off' }}
            </span>
        </button>
    </div>

    <!-- ================================================================= -->
    <!-- TAB 1: FORMAT PENOMORAN & REFERENSI INDEKS SURAT (SATU HALAMAN)   -->
    <!-- ================================================================= -->
    <div id="tab-referensi" class="dash-tab-pane" style="{{ ($tab ?? 'referensi') === 'referensi' ? 'display: block;' : 'display: none;' }}">
        
        <!-- 1. Card Format & Penomoran Surat Otomatis -->
        <div class="card" style="padding: 22px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg); margin-bottom: 24px;">
            <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-color); margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-hashtag text-primary"></i>
                    <span>Format &amp; Penomoran Surat Otomatis</span>
                </div>
                <span class="badge-compact" style="background: rgba(16,185,129,0.1); color: #10b981; font-family: monospace; font-size: 0.8rem;">
                    Format Aktif: {{ $setting->format_nomor_surat_keluar ?? '{nomor}/{kode_indeks}-{sekolah_kode}' }}
                </span>
            </div>
            <p style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.45; margin-bottom: 18px;">
                Atur template nomor surat keluar dan surat keterangan aktif siswa. Sistem secara otomatis menyematkan <strong>kode indeks klasifikasi</strong> yang dipilih saat surat dibuat dari tabel di bawah.
            </p>

            <form action="{{ route('dashboard.persuratan.pengaturan.update') }}" method="POST" id="formFormatNomor">
                @csrf
                <input type="hidden" name="tab" value="referensi">
                <input type="hidden" name="hdd_path" value="{{ $setting->hdd_path ?? '' }}">
                <input type="hidden" name="auto_subfolder" value="{{ ($setting->auto_subfolder ?? true) ? '1' : '0' }}">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 16px;">
                    <!-- Kolom Kiri: Input Template & Kode Sekolah -->
                    <div>
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Kode / Singkatan Satuan Pendidikan:
                            </label>
                            <input type="text" name="sekolah_kode" id="inputSekolahKode" value="{{ old('sekolah_kode', $setting->sekolah_kode ?? 'SMKN1PGL') }}"
                                   class="form-control" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.86rem;" required>
                        </div>

                        <div class="form-group" style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Template Nomor Surat Keluar:
                            </label>
                            <input type="text" name="format_nomor_surat_keluar" id="inputFormatKeluar" value="{{ old('format_nomor_surat_keluar', $setting->format_nomor_surat_keluar ?? '{nomor}/{kode_indeks}-{sekolah_kode}') }}"
                                   class="form-control" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-family: monospace; font-size: 0.82rem;" required>
                        </div>

                        <div class="form-group" style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Template Nomor Surat Keterangan Siswa:
                            </label>
                            <input type="text" name="format_nomor_surat_keterangan" id="inputFormatKet" value="{{ old('format_nomor_surat_keterangan', $setting->format_nomor_surat_keterangan ?? '{nomor}/{kode_indeks}-{sekolah_kode}') }}"
                                   class="form-control" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-family: monospace; font-size: 0.82rem;" required>
                        </div>

                        <!-- Token Placeholder Guide -->
                        <div style="background: rgba(99,102,241,0.05); border: 1px dashed rgba(99,102,241,0.3); border-radius: 8px; padding: 10px 12px; font-size: 0.74rem; color: var(--text-muted); line-height: 1.5;">
                            <strong style="color: var(--primary);"><i class="fas fa-info-circle me-1"></i> Token Placeholder Dinamis:</strong><br>
                            <code>{nomor}</code> = Nomor urut 4 digit (0029) |
                            <code>{kode_indeks}</code> = Kode klasifikasi (KPG.11.01 / KS.02.23) |
                            <code>{sekolah_kode}</code> = Singkatan sekolah (SMKN1PGL) |
                            <code>{romawi_bulan}</code> = Bulan Romawi (I-XII) |
                            <code>{bulan}</code> = Angka bulan (01-12) |
                            <code>{tahun}</code> = Tahun 4 digit ({{ date('Y') }})
                        </div>
                    </div>

                    <!-- Kolom Kanan: Live Preview & Counter Terakhir -->
                    <div style="display: flex; flex-direction: column; justify-content: space-between;">
                        <!-- Live Preview Box -->
                        <div style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 10px; padding: 14px 16px; margin-bottom: 14px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <span style="font-size: 0.76rem; font-weight: 700; text-transform: uppercase; color: var(--text-color);">
                                    <i class="fas fa-eye text-primary me-1"></i> Contoh Hasil Penomoran Live:
                                </span>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span style="font-size: 0.72rem; color: var(--text-muted);">Uji Indeks:</span>
                                    <select id="previewIndeksSelect" style="height: 28px; font-size: 0.74rem; padding: 0 6px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color);">
                                        <option value="KPG.11.01" selected>KPG.11.01 (Surat Tugas)</option>
                                        <option value="KS.02.23">KS.02.23 (Sertifikat)</option>
                                        <option value="KP.11.08">KP.11.08 (Rekomendasi)</option>
                                        <option value="TU.01">TU.01 (Persuratan)</option>
                                        <option value="KU.03.01">KU.03.01 (Penyedia Dana)</option>
                                    </select>
                                </div>
                            </div>
                            <div style="font-size: 0.82rem; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--text-muted);">Surat Keluar:</span>
                                <span id="previewKeluarText" style="font-family: monospace; font-weight: 700; color: #10b981; font-size: 0.95rem;">
                                    {{ $previewSuratKeluar }}
                                </span>
                            </div>
                            <div style="font-size: 0.82rem; display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--text-muted);">Surat Keterangan:</span>
                                <span id="previewKetText" style="font-family: monospace; font-weight: 700; color: var(--primary); font-size: 0.95rem;">
                                    {{ $previewSuratKet }}
                                </span>
                            </div>
                        </div>

                        <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                            <div style="flex: 1;">
                                <label style="display: block; font-size: 0.78rem; font-weight: 600; color: var(--text-muted); margin-bottom: 3px;">
                                    Counter Terakhir Surat Keluar:
                                </label>
                                <input type="number" name="nomor_terakhir_surat_keluar" id="counterKeluar" value="{{ $setting->nomor_terakhir_surat_keluar ?? 0 }}"
                                       class="form-control" style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;">
                            </div>
                            <div style="flex: 1;">
                                <label style="display: block; font-size: 0.78rem; font-weight: 600; color: var(--text-muted); margin-bottom: 3px;">
                                    Counter Surat Keterangan:
                                </label>
                                <input type="number" name="nomor_terakhir_surat_keterangan" id="counterKet" value="{{ $setting->nomor_terakhir_surat_keterangan ?? 0 }}"
                                       class="form-control" style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;">
                            </div>
                        </div>

                        @if ($canUpdate)
                            <div style="text-align: right;">
                                <button type="submit" class="btn btn-primary" style="padding: 9px 20px; border-radius: 8px; font-size: 0.86rem;">
                                    <i class="fas fa-save me-1"></i> Simpan Format Nomor
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- 2. Card Master Indeks Klasifikasi Surat -->
        <div class="card" style="padding: 20px; border-radius: 14px; border: 1px solid var(--border-color); margin-bottom: 24px; background: var(--card-bg);">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap; margin-bottom: 16px;">
                <div>
                    <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-tags text-primary"></i> Master Indeks Klasifikasi Surat
                    </h3>
                    <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                        Referensi kode klasifikasi surat dinas yang otomatis digunakan saat menerbitkan nomor surat keluar maupun surat keterangan siswa.
                    </div>
                </div>

                @if ($canUpdate)
                    <button type="button" class="btn btn-primary" id="btnOpenCreateIndeks" style="padding: 8px 16px; border-radius: 8px; font-size: 0.84rem;">
                        <i class="fas fa-plus me-1"></i> Tambah Kode Indeks
                    </button>
                @endif
            </div>

            <!-- Filter & Search Indeks Standar SAE -->
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                        <select id="perPageSelect" class="per-page-select">
                            @foreach ([10, 15, 20, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ ($perPage ?? 20) == $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    <select id="filterKategori" class="toolbar-filter-select">
                        <option value="">Semua Kategori</option>
                        @foreach ($kategoriList as $kat)
                            <option value="{{ $kat }}" {{ ($kategoriIndeks ?? '') === $kat ? 'selected' : '' }}>{{ $kat }}</option>
                        @endforeach
                    </select>

                    @if (!empty($qIndeks) || !empty($kategoriIndeks))
                        <a href="{{ route('dashboard.persuratan.pengaturan.index', ['tab' => 'referensi']) }}"
                            class="btn btn-outline" style="padding: 7px 12px; font-size: 0.84rem;"
                            title="Reset filter">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <div class="live-search-wrap">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearchIndeks" placeholder="Cari kode indeks atau klasifikasi..." value="{{ $qIndeks ?? '' }}" autocomplete="off">
                    <button type="button" id="clearSearchIndeks" class="clear-search {{ !empty($qIndeks) ? 'visible' : '' }}" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Tabel Responsive Stack Standar SAE -->
            <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 16px; border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                            <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 100px;">Kode</th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Perihal / Klasifikasi</th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">Kategori</th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Keterangan</th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 90px; text-align: center;">Status</th>
                            @if ($canUpdate)
                                <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 90px; text-align: center;">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($indeksList as $idx)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-family: monospace; font-weight: 700; font-size: 0.88rem; color: var(--primary);">
                                    {{ $idx->kode }}
                                </td>
                                <td style="padding: 12px 16px; font-weight: 600; font-size: 0.84rem; color: var(--text-color);">
                                    {{ $idx->judul }}
                                </td>
                                <td style="padding: 12px 16px;">
                                    <span class="badge-compact" style="background: rgba(99,102,241,0.1); color: var(--primary);">
                                        {{ $idx->kategori }}
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; font-size: 0.78rem; color: var(--text-muted);">
                                    {{ $idx->keterangan ?: '-' }}
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <span class="badge-compact {{ $idx->is_active ? 'badge-success' : 'badge-danger' }}">
                                        {{ $idx->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                @if ($canUpdate)
                                    <td style="padding: 12px 16px; text-align: center;">
                                        <div class="table-actions" style="display: flex; gap: 4px; justify-content: center;">
                                            <button type="button" class="btn-icon btn-edit-indeks" title="Edit Kode Indeks"
                                                    data-id="{{ $idx->id }}"
                                                    data-kode="{{ $idx->kode }}"
                                                    data-judul="{{ $idx->judul }}"
                                                    data-kategori="{{ $idx->kategori }}"
                                                    data-keterangan="{{ $idx->keterangan }}"
                                                    data-active="{{ $idx->is_active ? '1' : '0' }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="{{ route('dashboard.persuratan.pengaturan.indeks.destroy', $idx->id) }}" method="POST"
                                                  data-confirm="delete" data-name="Kode Indeks {{ $idx->kode }} ({{ $idx->judul }})">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon text-danger" title="Hapus Indeks">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 36px 16px; color: var(--text-muted);">
                                    <i class="fas fa-tags" style="font-size: 2rem; opacity: 0.3; margin-bottom: 8px; display: block;"></i>
                                    Tidak ada kode indeks surat yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginasi Baku SAE -->
            @if ($indeksList->hasPages())
                <div class="custom-pagination">
                    @if ($indeksList->onFirstPage())
                        <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                    @else
                        <a href="{{ $indeksList->appends(['tab' => 'referensi'])->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                    @endif

                    @php
                        $cur = $indeksList->currentPage();
                        $last = $indeksList->lastPage();
                        $from = max(1, $cur - 2);
                        $to = min($last, $cur + 2);
                    @endphp

                    @if ($from > 1)
                        <a href="{{ $indeksList->appends(['tab' => 'referensi'])->url(1) }}" class="page-btn">1</a>
                        @if ($from > 2) <span class="page-info">&hellip;</span> @endif
                    @endif

                    @for ($i = $from; $i <= $to; $i++)
                        <a href="{{ $indeksList->appends(['tab' => 'referensi'])->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                    @endfor

                    @if ($to < $last)
                        @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                        <a href="{{ $indeksList->appends(['tab' => 'referensi'])->url($last) }}" class="page-btn">{{ $last }}</a>
                    @endif

                    @if ($indeksList->hasMorePages())
                        <a href="{{ $indeksList->appends(['tab' => 'referensi'])->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                    @else
                        <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- TAB 2: PENGATURAN SISTEM & ARSIP HARDDISK (HDD)                   -->
    <!-- ================================================================= -->
    <div id="tab-pengaturan" class="dash-tab-pane" style="{{ ($tab ?? 'referensi') === 'pengaturan' ? 'display: block;' : 'display: none;' }}">
        <!-- Stat Cards Ringkasan Harddisk -->
        <div class="dash-stat-grid" style="margin-bottom: 24px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                    <i class="fas fa-hard-drive"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" id="hddFreeText">{{ $hddStatus['free_formatted'] }}</div>
                    <div class="dash-stat-label">Ruang Sisa HDD (Tersedia)</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(37,99,235,0.12); color: #2563eb;">
                    <i class="fas fa-database"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" id="hddTotalText">{{ $hddStatus['total_formatted'] }}</div>
                    <div class="dash-stat-label">Total Kapasitas HDD</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                    <i class="fas fa-file-pdf"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalBerkasTercatat) }}</div>
                    <div class="dash-stat-label">Berkas Terhubung di HDD</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: {{ $hddStatus['is_ready'] ? 'rgba(16,185,129,0.12)' : 'rgba(239,68,68,0.12)' }}; color: {{ $hddStatus['is_ready'] ? '#10b981' : '#ef4444' }};">
                    <i class="fas {{ $hddStatus['is_ready'] ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1rem;">{{ $hddStatus['is_ready'] ? 'Siap Baca / Tulis' : 'Akses Terbatas' }}</div>
                    <div class="dash-stat-label">Status Izin Partisi</div>
                </div>
            </div>
        </div>        <!-- Form Konfigurasi Penyimpanan HDD -->
        <div class="card" style="padding: 24px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg); max-width: 820px; margin: 0 auto 24px auto;">
            <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-color); margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                <span><i class="fas fa-hard-drive text-primary me-2"></i> Koneksi Harddisk (HDD) Komputer</span>
                <span id="hddStatusIndicator" class="badge-compact {{ $hddStatus['is_ready'] ? 'badge-success' : 'badge-danger' }}">
                    <i class="fas {{ $hddStatus['is_ready'] ? 'fa-check-circle' : 'fa-triangle-exclamation' }}"></i>
                    {{ $hddStatus['is_ready'] ? 'Terhubung & Siap Tulis' : 'Tidak Dapat Diakses' }}
                </span>
            </div>
            <p style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.45; margin-bottom: 18px;">
                Tentukan path direktori lokal atau partisi harddisk komputer (misal <code>D:\Arsip_Sekolah</code>) tempat seluruh dokumen lampiran (PDF/Scan) disimpan, diunduh, dan dirender oleh sistem.
            </p>

            <form action="{{ route('dashboard.persuratan.pengaturan.update') }}" method="POST">
                @csrf
                <input type="hidden" name="tab" value="pengaturan">
                <input type="hidden" name="format_nomor_surat_keluar" value="{{ $setting->format_nomor_surat_keluar ?? '' }}">
                <input type="hidden" name="format_nomor_surat_keterangan" value="{{ $setting->format_nomor_surat_keterangan ?? '' }}">
                <input type="hidden" name="sekolah_kode" value="{{ $setting->sekolah_kode ?? 'SMKN1PGL' }}">

                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 6px;">
                        Path Direktori Penyimpanan di HDD:
                    </label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" name="hdd_path" id="hddPathInput" value="{{ old('hdd_path', $setting->hdd_path ?? '') }}"
                               class="form-control" style="flex: 1; height: 40px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-family: monospace; font-size: 0.86rem;" required>
                        <button type="button" class="btn btn-outline" id="btnTestHdd" style="padding: 0 14px; height: 40px; border-radius: 8px; font-size: 0.82rem; white-space: nowrap;">
                            <i class="fas fa-plug-circle-check me-1"></i> Uji Akses Folder
                        </button>
                    </div>
                    <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 4px;">
                        Contoh path Windows: <code>D:\Arsip_Persuratan_SAE</code> atau <code>E:\Data_Sekolah\Arsip</code>
                    </div>
                </div>

                <!-- Progress Bar Kapasitas HDD -->
                <div style="background: rgba(0,0,0,0.04); border-radius: 10px; padding: 14px; margin-bottom: 18px; border: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-between; font-size: 0.78rem; font-weight: 600; margin-bottom: 6px;">
                        <span style="color: var(--text-color);"><i class="fas fa-chart-pie text-primary me-1"></i> Penggunaan Partisi Harddisk</span>
                        <span style="color: var(--text-muted);">{{ $hddStatus['percent_used'] }}% Terpakai ({{ $hddStatus['used_formatted'] }})</span>
                    </div>
                    <div style="width: 100%; height: 8px; background: var(--border-color); border-radius: 4px; overflow: hidden;">
                        <div style="width: {{ min(100, $hddStatus['percent_used']) }}%; height: 100%; background: {{ $hddStatus['percent_used'] > 90 ? '#ef4444' : ($hddStatus['percent_used'] > 75 ? '#f59e0b' : '#10b981') }}; border-radius: 4px; transition: width 0.4s ease;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.72rem; color: var(--text-muted); margin-top: 6px;">
                        <span>0 GB</span>
                        <span>Sisa: {{ $hddStatus['free_formatted'] }} bebas</span>
                        <span>Total: {{ $hddStatus['total_formatted'] }}</span>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 0.82rem; color: var(--text-color); cursor: pointer;">
                        <input type="checkbox" name="auto_subfolder" value="1" {{ ($setting->auto_subfolder ?? true) ? 'checked' : '' }} style="width: 16px; height: 16px;">
                        <span>Otomatis kelompokkan folder: <code>surat_masuk/{Tahun}</code>, <code>surat_keluar/{Tahun}</code>, <code>surat_keterangan/{Tahun}</code></span>
                    </label>
                </div>

                @if ($canUpdate)
                    <div style="text-align: right;">
                        <button type="submit" class="btn btn-primary" style="padding: 9px 20px; border-radius: 8px; font-size: 0.86rem;">
                            <i class="fas fa-save me-1"></i> Simpan Pengaturan HDD
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <!-- Modal Tambah / Edit Indeks Klasifikasi Surat -->
    <div class="modal-overlay" id="modalIndeks" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center;">
        <div class="modal-container" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; width: 100%; max-width: 520px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); overflow: hidden;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h4 id="modalIndeksTitle" style="font-size: 1rem; font-weight: 700; color: var(--text-color); margin: 0;">
                    Tambah Kode Indeks Klasifikasi
                </h4>
                <button type="button" id="btnCloseModalIndeks" style="background: none; border: none; font-size: 1.1rem; color: var(--text-muted); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formIndeks" action="" method="POST">
                @csrf
                <input type="hidden" name="_method" id="methodSpoofIndeks" value="POST">

                <div style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Kode Indeks: <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="kode" id="inputIndeksKode" placeholder="Contoh: KPG.11.01"
                               style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-family: monospace; font-size: 0.88rem;" required>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Perihal / Nama Klasifikasi: <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="judul" id="inputIndeksJudul" placeholder="Contoh: Surat Keterangan Siswa Aktif"
                               style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;" required>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Kategori Klasifikasi: <span class="text-danger">*</span>
                        </label>
                        <select name="kategori" id="selectIndeksKategori"
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;" required>
                            <option value="Umum">Umum / Kedinasan</option>
                            <option value="Kesiswaan">Kesiswaan</option>
                            <option value="Kepegawaian">Kepegawaian</option>
                            <option value="Kurikulum">Kurikulum &amp; Pembelajaran</option>
                            <option value="Sarpras">Sarpras &amp; Fasilitas</option>
                            <option value="Keuangan">Keuangan &amp; BOS</option>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Keterangan / Ruang Lingkup:
                        </label>
                        <textarea name="keterangan" id="inputIndeksKeterangan" rows="2" placeholder="Penjelasan singkat penggunaan kode ini..."
                                  style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;"></textarea>
                    </div>

                    <div>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.84rem; color: var(--text-color); cursor: pointer;">
                            <input type="checkbox" name="is_active" id="checkIndeksActive" value="1" checked style="width: 16px; height: 16px;">
                            <span>Status Kode Indeks Aktif (Dapat dipilih saat membuat surat)</span>
                        </label>
                    </div>
                </div>

                <div style="padding: 14px 20px; border-top: 1px solid var(--border-color); background: rgba(0,0,0,0.02); display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" id="btnCancelModalIndeks" class="btn btn-outline" style="padding: 8px 16px; border-radius: 8px; font-size: 0.84rem;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 18px; border-radius: 8px; font-size: 0.84rem;">
                        <i class="fas fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/persuratan-pengaturan.js') }}"></script>
@endpush
