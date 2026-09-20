@extends('layouts.app')

@section('title', 'Administrasi Kepegawaian GTK - SAE')

@section('content')
<div class="dash-container">
    {{-- Header Modul --}}
    <div class="dash-header-block" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-size: 1.4rem; font-weight: 700; color: var(--text-primary); margin: 0;">
                    <i class="fas fa-id-card-alt" style="color: var(--primary); margin-right: 8px;"></i>
                    Kepegawaian &amp; GTK
                </h1>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                @if($tab === 'berkas')
                <button type="button" class="btn btn-primary" id="btnOpenModalUploadBerkas" title="Unggah Berkas PTK" style="padding: 8px 14px; font-size: 0.9rem; border-radius: 8px;">
                    <i class="fas fa-plus"></i>
                </button>
                @elseif($tab === 'kgb')
                <button type="button" class="btn btn-primary" id="btnOpenModalKgb" title="Catat / Update KGB" style="padding: 8px 14px; font-size: 0.9rem; border-radius: 8px;">
                    <i class="fas fa-plus"></i>
                </button>
                @elseif($tab === 'cuti')
                <button type="button" class="btn btn-primary" id="btnOpenModalCuti" title="Buat Surat Cuti / Izin" style="padding: 8px 14px; font-size: 0.9rem; border-radius: 8px;">
                    <i class="fas fa-plus"></i>
                </button>
                @elseif($tab === 'spt')
                <button type="button" class="btn btn-primary" id="btnOpenModalSpt" title="Terbitkan SPT Dinas" style="padding: 8px 14px; font-size: 0.9rem; border-radius: 8px;">
                    <i class="fas fa-plus"></i>
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Alert Notifikasi KGB Jatuh Tempo --}}
    @if($kgbJatuhTempoCount > 0)
    <div style="background: rgba(245, 158, 11, 0.1); border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-bell" style="color: #f59e0b; font-size: 1.15rem;"></i>
            <span style="font-size: 0.86rem; color: #92400e;">
                <strong>{{ $kgbJatuhTempoCount }} GTK</strong> jatuh tempo KGB dalam 90 hari ke depan.
            </span>
        </div>
        <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'kgb']) }}" class="btn btn-sm" style="background: #f59e0b; color: #fff; font-weight: 600; padding: 4px 10px;" title="Lihat Daftar"><i class="fas fa-arrow-right"></i></a>
    </div>
    @endif

    {{-- Tab Navigasi Modul --}}
    <div class="dash-tabs" style="display: flex; gap: 8px; border-bottom: 2px solid var(--border-color); margin-bottom: 20px; padding-bottom: 8px; overflow-x: auto;">
        <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'berkas']) }}" class="tab-item {{ $tab === 'berkas' ? 'active' : '' }}" style="padding: 8px 14px; border-radius: 6px; font-weight: 600; font-size: 0.88rem; text-decoration: none; color: {{ $tab === 'berkas' ? 'var(--primary)' : 'var(--text-muted)' }}; background: {{ $tab === 'berkas' ? 'var(--primary-subtle)' : 'transparent' }}; display: flex; align-items: center; gap: 6px;">
            <i class="fas fa-folder-open"></i> Berkas GTK
        </a>
        <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'kgb']) }}" class="tab-item {{ $tab === 'kgb' ? 'active' : '' }}" style="padding: 8px 14px; border-radius: 6px; font-weight: 600; font-size: 0.88rem; text-decoration: none; color: {{ $tab === 'kgb' ? 'var(--primary)' : 'var(--text-muted)' }}; background: {{ $tab === 'kgb' ? 'var(--primary-subtle)' : 'transparent' }}; display: flex; align-items: center; gap: 6px;">
            <i class="fas fa-business-time"></i> KGB Tracker
            @if($kgbJatuhTempoCount > 0)
            <span style="background: #f59e0b; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px;">{{ $kgbJatuhTempoCount }}</span>
            @endif
        </a>
        <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'cuti']) }}" class="tab-item {{ $tab === 'cuti' ? 'active' : '' }}" style="padding: 8px 14px; border-radius: 6px; font-weight: 600; font-size: 0.88rem; text-decoration: none; color: {{ $tab === 'cuti' ? 'var(--primary)' : 'var(--text-muted)' }}; background: {{ $tab === 'cuti' ? 'var(--primary-subtle)' : 'transparent' }}; display: flex; align-items: center; gap: 6px;">
            <i class="fas fa-plane-departure"></i> Cuti &amp; Izin
        </a>
        <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'spt']) }}" class="tab-item {{ $tab === 'spt' ? 'active' : '' }}" style="padding: 8px 14px; border-radius: 6px; font-weight: 600; font-size: 0.88rem; text-decoration: none; color: {{ $tab === 'spt' ? 'var(--primary)' : 'var(--text-muted)' }}; background: {{ $tab === 'spt' ? 'var(--primary-subtle)' : 'transparent' }}; display: flex; align-items: center; gap: 6px;">
            <i class="fas fa-file-contract"></i> Surat Tugas (SPT)
        </a>
    </div>

    {{-- Filter & Live Search Bar --}}
    <div class="card" style="padding: 12px 16px; margin-bottom: 20px;">
        <form method="GET" action="{{ route('dashboard.kepegawaian.index') }}" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div style="flex: 1; min-width: 200px; position: relative;">
                <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Cari nama, NIP, atau perihal..." style="padding-left: 34px;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
            </div>
            @if($tab === 'berkas')
            <div style="min-width: 130px;">
                <select name="jenis" class="form-control" onchange="this.form.submit()">
                    <option value="all" {{ $jenisFilter === 'all' ? 'selected' : '' }}>Semua</option>
                    <option value="guru" {{ $jenisFilter === 'guru' ? 'selected' : '' }}>Guru</option>
                    <option value="tendik" {{ $jenisFilter === 'tendik' ? 'selected' : '' }}>Tendik</option>
                </select>
            </div>
            @endif
            <button type="submit" class="btn btn-secondary" title="Filter Pencarian" style="padding: 8px 12px;"><i class="fas fa-filter"></i></button>
            @if($search || $jenisFilter !== 'all')
            <a href="{{ route('dashboard.kepegawaian.index', ['tab' => $tab]) }}" class="btn btn-outline" title="Reset Filter" style="padding: 8px 12px;"><i class="fas fa-undo"></i></a>
            @endif
        </form>
    </div>

    {{-- TAB 1: Berkas Digital GTK --}}
    @if($tab === 'berkas')
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background: var(--bg-hover);">
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Identitas GTK</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jenis PTK</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Berkas Digital Tersimpan</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($gtkBerkasList as $idx => $gtk)
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td data-label="No" style="padding: 12px 18px; font-size: 0.88rem; color: var(--text-muted);">
                        {{ $gtkBerkasList->firstItem() + $idx }}
                    </td>
                    <td data-label="Nama & NIP" style="padding: 12px 18px;">
                        <div style="font-weight: 600; color: var(--text-primary); font-size: 0.95rem;">{{ $gtk->nama }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">
                            NIP: {{ $gtk->nip ?: '-' }} | NUPTK: {{ $gtk->nuptk ?: '-' }}
                        </div>
                    </td>
                    <td data-label="Jenis GTK" style="padding: 12px 18px;">
                        <span class="badge {{ str_contains(strtolower($gtk->jenis_ptk_id_str), 'guru') ? 'badge-info' : 'badge-secondary' }}">
                            {{ $gtk->jenis_ptk_id_str ?: 'GTK' }}
                        </span>
                    </td>
                    <td data-label="Kelengkapan" style="padding: 12px 18px;">
                        @if($gtk->berkas->isNotEmpty())
                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                            @foreach($gtk->berkas as $b)
                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 4px; font-size: 0.78rem;">
                                <i class="fas fa-file-pdf" style="color: #ef4444;"></i>
                                <a href="{{ asset('storage/' . $b->file_path) }}" target="_blank" style="color: var(--text-primary); text-decoration: none; font-weight: 500;">
                                    {{ $b->judul_dokumen }}
                                </a>
                                <form action="{{ route('dashboard.kepegawaian.berkas.delete', $b->id) }}" method="POST" style="display: inline;" data-confirm="delete" data-name="{{ $b->judul_dokumen }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0 2px; font-size: 0.75rem;" title="Hapus berkas">&times;</button>
                                </form>
                            </span>
                            @endforeach
                        </div>
                        @else
                        <span style="font-size: 0.82rem; color: var(--text-muted); font-style: italic;">Belum ada berkas diunggah</span>
                        @endif
                    </td>
                    <td data-label="Aksi" style="padding: 12px 18px; text-align: center;">
                        <div class="table-actions" style="justify-content: center;">
                            <button type="button" class="btn-icon btn-upload-gtk-berkas" data-id="{{ $gtk->ptk_id }}" data-nama="{{ $gtk->nama }}" title="Unggah Berkas Baru">
                                <i class="fas fa-upload"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 32px; color: var(--text-muted);">
                        <i class="fas fa-folder-open" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.5;"></i>
                        <div>Tidak ada data GTK ditemukan.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Custom Pagination --}}
    @if ($gtkBerkasList->hasPages())
        <div class="custom-pagination">
            @if ($gtkBerkasList->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $gtkBerkasList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
            @endif
            @php
                $cur = $gtkBerkasList->currentPage();
                $last = $gtkBerkasList->lastPage();
                $from = max(1, $cur - 2);
                $to = min($last, $cur + 2);
            @endphp
            @if ($from > 1)
                <a href="{{ $gtkBerkasList->url(1) }}" class="page-btn">1</a>
                @if ($from > 2) <span class="page-info">&hellip;</span> @endif
            @endif
            @for ($i = $from; $i <= $to; $i++)
                <a href="{{ $gtkBerkasList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor
            @if ($to < $last)
                @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                <a href="{{ $gtkBerkasList->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif
            @if ($gtkBerkasList->hasMorePages())
                <a href="{{ $gtkBerkasList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif
    @endif

    {{-- TAB 2: KGB Tracker --}}
    @if($tab === 'kgb')
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background: var(--bg-hover);">
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama GTK & NIP</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Masa Kerja Golongan</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">TMT KGB Terakhir</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">TMT Target Berikutnya</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status Usulan</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kgbList as $idx => $kgb)
                @php
                    $nextTmt = \Carbon\Carbon::parse($kgb->tmt_baru_target);
                    $daysRemaining = \Carbon\Carbon::today()->diffInDays($nextTmt, false);
                    $isNear = $daysRemaining >= 0 && $daysRemaining <= 90;
                    $isExpired = $daysRemaining < 0;
                @endphp
                <tr style="border-bottom: 1px solid var(--border-color); background: {{ $isNear ? 'rgba(245, 158, 11, 0.04)' : '' }};">
                    <td data-label="No" style="padding: 12px 18px; font-size: 0.88rem; color: var(--text-muted);">
                        {{ $kgbList->firstItem() + $idx }}
                    </td>
                    <td data-label="Nama GTK & NIP" style="padding: 12px 18px;">
                        <div style="font-weight: 600; color: var(--text-primary); font-size: 0.95rem;">{{ $kgb->gtk?->nama }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">NIP: {{ $kgb->gtk?->nip ?: '-' }}</div>
                    </td>
                    <td data-label="Masa Kerja" style="padding: 12px 18px;">
                        <div style="font-weight: 600; color: var(--text-primary);">{{ $kgb->mkg_tahun }} Thn {{ $kgb->mkg_bulan }} Bln</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">Gaji: Rp {{ number_format($kgb->gaji_pokok_lama, 0, ',', '.') }}</div>
                    </td>
                    <td data-label="TMT KGB Terakhir" style="padding: 12px 18px;">
                        <div style="font-size: 0.88rem; color: var(--text-primary);">{{ \Carbon\Carbon::parse($kgb->tmt_lama)->translatedFormat('d M Y') }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">SK: {{ $kgb->nomor_sk_terakhir ?: '-' }}</div>
                    </td>
                    <td data-label="TMT Berikutnya" style="padding: 12px 18px;">
                        <div style="font-weight: 600; color: {{ $isNear ? '#d97706' : ($isExpired ? '#ef4444' : 'var(--text-primary)') }};">
                            {{ $nextTmt->translatedFormat('d M Y') }}
                        </div>
                        <div style="font-size: 0.78rem;">
                            @if($isExpired)
                            <span style="color: #ef4444; font-weight: 600;">Lewat TMT ({{ abs($daysRemaining) }} hari lalu)</span>
                            @elseif($isNear)
                            <span style="color: #d97706; font-weight: 600;">H-{{ $daysRemaining }} hari lagi</span>
                            @else
                            <span style="color: var(--text-muted);">{{ $daysRemaining }} hari lagi</span>
                            @endif
                        </div>
                    </td>
                    <td data-label="Status Usulan" style="padding: 12px 18px;">
                        <span class="badge {{ $kgb->status_usulan === 'terbit_sk' ? 'badge-success' : ($kgb->status_usulan === 'diproses' ? 'badge-warning' : 'badge-secondary') }}">
                            {{ ucwords(str_replace('_', ' ', $kgb->status_usulan)) }}
                        </span>
                    </td>
                    <td data-label="Aksi" style="padding: 12px 18px; text-align: center;">
                        <div class="table-actions" style="justify-content: center;">
                            <button type="button" class="btn-icon btn-edit-kgb"
                                data-id="{{ $kgb->id }}"
                                data-ptk-id="{{ $kgb->ptk_id }}"
                                data-ptk-nama="{{ $kgb->gtk?->nama }}"
                                data-gaji-lama="{{ $kgb->gaji_pokok_lama }}"
                                data-gaji-baru="{{ $kgb->gaji_pokok_baru }}"
                                data-tmt-terakhir="{{ $kgb->tmt_lama }}"
                                data-sk-terakhir="{{ $kgb->nomor_sk_terakhir }}"
                                data-tgl-sk="{{ $kgb->tgl_sk_terakhir }}"
                                data-tmt-berikutnya="{{ $kgb->tmt_baru_target }}"
                                data-mk-tahun="{{ $kgb->mkg_tahun }}"
                                data-mk-bulan="{{ $kgb->mkg_bulan }}"
                                data-status="{{ $kgb->status_usulan }}"
                                data-catatan="{{ $kgb->catatan }}"
                                title="Update KGB">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 32px; color: var(--text-muted);">
                        <i class="fas fa-clock" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.5;"></i>
                        <div>Belum ada data tracker KGB yang dicatat.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Custom Pagination --}}
    @if ($kgbList->hasPages())
        <div class="custom-pagination">
            @if ($kgbList->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $kgbList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
            @endif
            @php
                $cur = $kgbList->currentPage();
                $last = $kgbList->lastPage();
                $from = max(1, $cur - 2);
                $to = min($last, $cur + 2);
            @endphp
            @if ($from > 1)
                <a href="{{ $kgbList->url(1) }}" class="page-btn">1</a>
                @if ($from > 2) <span class="page-info">&hellip;</span> @endif
            @endif
            @for ($i = $from; $i <= $to; $i++)
                <a href="{{ $kgbList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor
            @if ($to < $last)
                @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                <a href="{{ $kgbList->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif
            @if ($kgbList->hasMorePages())
                <a href="{{ $kgbList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif
    @endif

    {{-- TAB 3: Cuti & Tugas Dinas Luar --}}
    @if($tab === 'cuti')
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background: var(--bg-hover);">
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama GTK</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jenis Permohonan</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Periode & Durasi</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Keperluan</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Cetak</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cutiList as $idx => $cuti)
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 12px 18px; font-size: 0.88rem; color: var(--text-muted);">
                        {{ $cutiList->firstItem() + $idx }}
                    </td>
                    <td style="padding: 12px 18px;">
                        <div style="font-weight: 600; color: var(--text-primary); font-size: 0.95rem;">{{ $cuti->gtk?->nama }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">NIP: {{ $cuti->gtk?->nip ?: '-' }}</div>
                    </td>
                    <td style="padding: 12px 18px;">
                        <span class="badge {{ str_contains(strtolower($cuti->jenis), 'dinas') ? 'badge-primary' : 'badge-warning' }}">
                            {{ $cuti->jenis }}
                        </span>
                    </td>
                    <td style="padding: 12px 18px;">
                        <div style="font-size: 0.88rem; font-weight: 500; color: var(--text-primary);">
                            {{ \Carbon\Carbon::parse($cuti->tanggal_mulai)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($cuti->tanggal_selesai)->format('d/m/Y') }}
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">{{ $cuti->jumlah_hari }} Hari Kerja</div>
                    </td>
                    <td style="padding: 12px 18px;">
                        <div style="font-size: 0.88rem; color: var(--text-primary);">{{ $cuti->keperluan }}</div>
                    </td>
                    <td style="padding: 12px 18px;">
                        <span class="badge badge-success">{{ ucwords(str_replace('_', ' ', $cuti->status)) }}</span>
                    </td>
                    <td style="padding: 12px 18px; text-align: center;">
                        <div class="table-actions" style="justify-content: center;">
                            <a href="{{ route('dashboard.kepegawaian.cuti.cetak', $cuti->id) }}" target="_blank" class="btn-icon" title="Cetak Surat Resmi A4">
                                <i class="fas fa-print"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 32px; color: var(--text-muted);">
                        <i class="fas fa-plane" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.5;"></i>
                        <div>Belum ada data cuti atau surat tugas dinas yang dicatat.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Custom Pagination --}}
    @if ($cutiList->hasPages())
        <div class="custom-pagination">
            @if ($cutiList->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $cutiList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
            @endif
            @php
                $cur = $cutiList->currentPage();
                $last = $cutiList->lastPage();
                $from = max(1, $cur - 2);
                $to = min($last, $cur + 2);
            @endphp
            @if ($from > 1)
                <a href="{{ $cutiList->url(1) }}" class="page-btn">1</a>
                @if ($from > 2) <span class="page-info">&hellip;</span> @endif
            @endif
            @for ($i = $from; $i <= $to; $i++)
                <a href="{{ $cutiList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor
            @if ($to < $last)
                @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                <a href="{{ $cutiList->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif
            @if ($cutiList->hasMorePages())
                <a href="{{ $cutiList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif
    @endif
</div>

{{-- MODAL 1: Upload Berkas Digital GTK --}}
<div class="modal-backdrop" id="modalUploadBerkas" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center; padding: 16px;">
    <div class="card" style="width: 100%; max-width: 540px; max-height: 90vh; overflow-y: auto; padding: 24px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-primary);">
                <i class="fas fa-file-upload" style="color: var(--primary); margin-right: 6px;"></i> Unggah Berkas Digital GTK
            </h3>
            <button type="button" id="btnCloseModalUpload" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>

        <form action="{{ route('dashboard.kepegawaian.berkas.upload') }}" method="POST" enctype="multipart/form-data" id="formUploadBerkas">
            @csrf
            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Pilih GTK / PTK <span style="color: #ef4444;">*</span></label>
                <select name="ptk_id" id="upload_ptk_id" class="form-control" required>
                    <option value="">-- Pilih Guru / Tendik --</option>
                    @foreach($allGtk as $g)
                    <option value="{{ $g->ptk_id }}">{{ $g->nama }} ({{ $g->jenis_ptk_id_str ?: 'GTK' }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Kategori / Jenis Berkas <span style="color: #ef4444;">*</span></label>
                <select name="jenis_dokumen" id="upload_jenis_dokumen" class="form-control" required>
                    @foreach($jenisBerkasOptions as $key => $lbl)
                    <option value="{{ $key }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Nama / Judul Dokumen <span style="color: #ef4444;">*</span></label>
                <input type="text" name="judul_dokumen" id="upload_judul_dokumen" class="form-control" placeholder="Contoh: SK Pengangkatan Pertama 2020" required>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Pilih File (PDF / JPG / PNG, Maks. 5MB) <span style="color: #ef4444;">*</span></label>
                <input type="file" name="file_berkas" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Catatan / Keterangan</label>
                <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan tambahan (opsional)..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-secondary" id="btnCancelModalUpload">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Unggah Dokumen</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 2: Form Catat / Update KGB Tracker --}}
<div class="modal-backdrop" id="modalKgb" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center; padding: 16px;">
    <div class="card" style="width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; padding: 24px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-primary);">
                <i class="fas fa-business-time" style="color: var(--primary); margin-right: 6px;"></i> Catat / Update Kenaikan Gaji Berkala (KGB)
            </h3>
            <button type="button" id="btnCloseModalKgb" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>

        <form action="{{ route('dashboard.kepegawaian.kgb.store') }}" method="POST" id="formKgb">
            @csrf
            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Pilih GTK <span style="color: #ef4444;">*</span></label>
                <select name="ptk_id" id="kgb_ptk_id" class="form-control" required>
                    <option value="">-- Pilih GTK --</option>
                    @foreach($allGtk as $g)
                    <option value="{{ $g->ptk_id }}">{{ $g->nama }} (NIP: {{ $g->nip ?: '-' }})</option>
                    @endforeach
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Masa Kerja Tahun</label>
                    <input type="number" name="mkg_tahun" id="kgb_mk_tahun" class="form-control" placeholder="Tahun" min="0" value="0">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Masa Kerja Bulan</label>
                    <input type="number" name="mkg_bulan" id="kgb_mk_bulan" class="form-control" placeholder="Bulan" min="0" max="11" value="0">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">TMT KGB Terakhir <span style="color: #ef4444;">*</span></label>
                    <input type="date" name="tmt_lama" id="kgb_tmt_lama" class="form-control" required>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">TMT Target Berikutnya (+2 Thn) <span style="color: #ef4444;">*</span></label>
                    <input type="date" name="tmt_baru_target" id="kgb_tmt_baru_target" class="form-control" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Nomor SK Terakhir</label>
                    <input type="text" name="nomor_sk_terakhir" id="kgb_sk_terakhir" class="form-control" placeholder="Nomor SK...">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Status Pengajuan</label>
                    <select name="status_usulan" id="kgb_status_usulan" class="form-control" required>
                        <option value="belum_waktunya">Belum Waktunya</option>
                        <option value="siap_diajukan">Siap Diajukan ke BKD</option>
                        <option value="diproses">Sedang Diproses BKD</option>
                        <option value="terbit_sk">Terbit SK KGB</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Catatan</label>
                <textarea name="catatan" id="kgb_catatan" class="form-control" rows="2" placeholder="Catatan berkas atau catatan khusus..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-secondary" id="btnCancelModalKgb">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Data KGB</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 3: Form Cuti & Surat Perintah Tugas (SPT) --}}
<div class="modal-backdrop" id="modalCuti" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center; padding: 16px;">
    <div class="card" style="width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; padding: 24px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-primary);">
                <i class="fas fa-calendar-plus" style="color: var(--primary); margin-right: 6px;"></i> Permohonan Cuti / Surat Tugas Dinas (SPT)
            </h3>
            <button type="button" id="btnCloseModalCuti" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>

        <form action="{{ route('dashboard.kepegawaian.cuti.store') }}" method="POST" enctype="multipart/form-data" id="formCuti">
            @csrf
            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Pilih GTK Yang Mengajukan <span style="color: #ef4444;">*</span></label>
                <select name="ptk_id" id="cuti_ptk_id" class="form-control" required>
                    <option value="">-- Pilih GTK --</option>
                    @foreach($allGtk as $g)
                    <option value="{{ $g->ptk_id }}">{{ $g->nama }} (NIP: {{ $g->nip ?: '-' }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Jenis Permohonan / Cuti / Tugas <span style="color: #ef4444;">*</span></label>
                <select name="jenis" id="cuti_jenis" class="form-control" required>
                    <option value="Cuti Tahunan">Cuti Tahunan</option>
                    <option value="Cuti Sakit">Cuti Sakit</option>
                    <option value="Cuti Melahirkan">Cuti Melahirkan</option>
                    <option value="Cuti Alasan Penting">Cuti Alasan Penting</option>
                    <option value="Tugas Dinas Luar">Surat Perintah Tugas (Dinas Luar)</option>
                    <option value="Izin">Izin Tidak Masuk</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Tanggal Mulai <span style="color: #ef4444;">*</span></label>
                    <input type="date" name="tanggal_mulai" id="cuti_tgl_mulai" class="form-control" required>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Tanggal Selesai <span style="color: #ef4444;">*</span></label>
                    <input type="date" name="tanggal_selesai" id="cuti_tgl_selesai" class="form-control" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Keperluan / Maksud Tugas <span style="color: #ef4444;">*</span></label>
                <textarea name="keperluan" id="cuti_keperluan" class="form-control" rows="2" placeholder="Jelaskan alasan cuti atau maksud tugas kedinasan..." required></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Surat Undangan / Keterangan Dokter (Opsional)</label>
                <input type="file" name="surat_pendukung" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-secondary" id="btnCancelModalCuti">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Terbitkan Surat</button>
            </div>
        </form>
    </div>
</div>

{{-- TAB 4: Datatable & Modal SPT (Surat Perintah Tugas) --}}
@if($tab === 'spt')
<div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
        <thead>
            <tr style="border-bottom: 1px solid var(--border-color); background: var(--bg-hover);">
                <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No</th>
                <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nomor SPT &amp; Kegiatan</th>
                <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Lokasi &amp; Tanggal</th>
                <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Personil Ditugaskan</th>
                <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Beban</th>
                <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sptList as $idx => $sptItem)
            @php
                $assignedIds = json_decode($sptItem->daftar_ptk_id ?? '[]', true) ?: [];
                $assignedGtks = $allGtk->whereIn('ptk_id', $assignedIds);
            @endphp
            <tr style="border-bottom: 1px solid var(--border-color);">
                <td data-label="No" style="padding: 12px 18px; font-size: 0.88rem; color: var(--text-muted);">
                    {{ $sptList->firstItem() + $idx }}
                </td>
                <td data-label="Nomor & Kegiatan" style="padding: 12px 18px;">
                    <div style="font-weight: 700; color: var(--text-primary); font-size: 0.95rem;">{{ $sptItem->nama_kegiatan }}</div>
                    <div style="font-size: 0.8rem; color: var(--primary); font-family: monospace;">{{ $sptItem->nomor_spt }}</div>
                </td>
                <td data-label="Lokasi & Tanggal" style="padding: 12px 18px;">
                    <div style="font-size: 0.86rem; color: var(--text-primary);"><i class="fas fa-location-dot text-danger me-1"></i> {{ $sptItem->lokasi_tujuan }}</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                        {{ \Carbon\Carbon::parse($sptItem->tanggal_berangkat)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($sptItem->tanggal_kembali)->format('d/m/Y') }} ({{ $sptItem->lama_hari }} hr)
                    </div>
                </td>
                <td data-label="Personil" style="padding: 12px 18px;">
                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                        @foreach($assignedGtks as $ag)
                        <span class="badge badge-info" style="font-size: 0.75rem; padding: 2px 7px;">{{ $ag->nama }}</span>
                        @endforeach
                    </div>
                </td>
                <td data-label="Beban" style="padding: 12px 18px;">
                    <span class="badge badge-secondary" style="font-size: 0.75rem;">{{ $sptItem->beban_anggaran }}</span>
                </td>
                <td data-label="Aksi" style="padding: 12px 18px; text-align: center;">
                    <div class="table-actions" style="justify-content: center; gap: 6px;">
                        <a href="{{ route('dashboard.kepegawaian.spt.cetak', $sptItem->id) }}" target="_blank" class="btn-icon" title="Cetak Surat Tugas SPT" style="color: var(--primary);">
                            <i class="fas fa-print"></i>
                        </a>
                        <form action="{{ route('dashboard.kepegawaian.spt.delete', $sptItem->id) }}" method="POST" style="display: inline;" data-confirm="delete" data-name="SPT {{ $sptItem->nomor_spt }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon" title="Hapus SPT" style="color: #ef4444;">
                                <i class="fas fa-trash-can"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 32px; color: var(--text-muted);">
                    <i class="fas fa-folder-open" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.5;"></i>
                    <div>Tidak ada data Surat Perintah Tugas (SPT) ditemukan.</div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($sptList->hasPages())
    <div class="custom-pagination">
        @if ($sptList->onFirstPage())
            <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
        @else
            <a href="{{ $sptList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
        @endif
        @php
            $cur = $sptList->currentPage();
            $last = $sptList->lastPage();
            $from = max(1, $cur - 2);
            $to = min($last, $cur + 2);
        @endphp
        @if ($from > 1)
            <a href="{{ $sptList->url(1) }}" class="page-btn">1</a>
            @if ($from > 2) <span class="page-info">&hellip;</span> @endif
        @endif
        @for ($i = $from; $i <= $to; $i++)
            <a href="{{ $sptList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
        @endfor
        @if ($to < $last)
            @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
            <a href="{{ $sptList->url($last) }}" class="page-btn">{{ $last }}</a>
        @endif
        @if ($sptList->hasMorePages())
            <a href="{{ $sptList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
        @else
            <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
        @endif
    </div>
@endif
@endif

{{-- Modal Input SPT Baru --}}
<div id="modalSpt" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; padding: 24px; border-radius: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-primary);">
                <i class="fas fa-file-contract text-primary me-2"></i> Terbitkan Surat Perintah Tugas (SPT)
            </h3>
            <button type="button" id="btnCloseModalSpt" style="background: none; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer;">&times;</button>
        </div>

        <form action="{{ route('dashboard.kepegawaian.spt.store') }}" method="POST" id="formSpt">
            @csrf
            <div class="form-group" style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Nama Kegiatan / Tugas Dinas <span style="color: #ef4444;">*</span></label>
                <input type="text" name="nama_kegiatan" class="form-control" placeholder="Contoh: Mengikuti Bimtek Implementasi Kurikulum Merdeka" required>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Lokasi / Tempat Tujuan <span style="color: #ef4444;">*</span></label>
                <input type="text" name="lokasi_tujuan" class="form-control" placeholder="Contoh: Hotel Grand Sahid / BBGP Provinsi" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Tanggal Berangkat <span style="color: #ef4444;">*</span></label>
                    <input type="date" name="tanggal_berangkat" class="form-control" required>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Tanggal Kembali <span style="color: #ef4444;">*</span></label>
                    <input type="date" name="tanggal_kembali" class="form-control" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Beban Anggaran <span style="color: #ef4444;">*</span></label>
                <select name="beban_anggaran" class="form-control" required>
                    <option value="BOS Reguler">BOS Reguler</option>
                    <option value="BOS Kinerja">BOS Kinerja</option>
                    <option value="Komite Sekolah">Komite Sekolah</option>
                    <option value="Penyelenggara / Panitia">Biaya Penyelenggara / Gratis</option>
                    <option value="Mandiri">Mandiri</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Pilih GTK Yang Ditugaskan <span style="color: #ef4444;">*</span></label>
                <div style="max-height: 160px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 8px; padding: 10px; background: var(--bg-hover);">
                    @foreach($allGtk as $g)
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; font-size: 0.84rem; cursor: pointer;">
                        <input type="checkbox" name="daftar_ptk_id[]" value="{{ $g->ptk_id }}">
                        <span><strong>{{ $g->nama }}</strong> <small style="color: var(--text-muted);">(NIP: {{ $g->nip ?: '-' }})</small></span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Dasar Penugasan (Opsional)</label>
                <textarea name="dasar_penugasan" class="form-control" rows="2" placeholder="Contoh: Surat Undangan Dinas Pendidikan No. 421/123/2026 tanggal 15 September 2026..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalSpt').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Terbitkan SPT</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/kepegawaian.js') }}"></script>
<script src="{{ asset('js/kepegawaian-spt.js') }}"></script>
@endpush
@endsection
