@extends('layouts.dashboard')

@section('title', 'Persuratan & Arsip Digital — SAE (Sistem Aplikasi Edukasi)')
@section('dash_title', 'Persuratan & Arsip Digital')

@section('content')
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Persuratan &amp; Arsip Digital
                </h2>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($canCreate)
                <button type="button" class="btn btn-outline" style="padding: 8px 14px; font-size: 0.9rem; border-radius: 8px;" id="btnOpenKetModal" title="Terbitkan Surat Keterangan Siswa">
                    <i class="fas fa-file-signature text-primary"></i>
                </button>
                <button type="button" class="btn btn-primary" style="padding: 8px 14px; font-size: 0.9rem; border-radius: 8px;" id="btnOpenCreateModal" title="Catat Surat Baru">
                    <i class="fas fa-plus"></i>
                </button>
            @endif
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

    <!-- 3. Stat Grid Baku SAE -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                <i class="fas fa-folder-tree"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Arsip Surat</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(37,99,235,0.12); color: #2563eb;">
                <i class="fas fa-inbox"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['masuk'] ?? 0) }}</div>
                <div class="dash-stat-label">Surat Masuk</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(22,163,74,0.12); color: #16a34a;">
                <i class="fas fa-paper-plane"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['keluar'] ?? 0) }}</div>
                <div class="dash-stat-label">Surat Keluar</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                <i class="fas fa-clock-rotate-left"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['pending'] ?? 0) }}</div>
                <div class="dash-stat-label">Menunggu Disposisi</div>
            </div>
        </div>
    </div>

    <!-- 4. Main Data Card & Table -->
    <div class="card" style="padding: 20px; border-radius: 14px; border: 1px solid var(--border-color); margin-bottom: 20px;">
        <!-- Toolbar: Pencarian, Filter & Dropdown Baris -->
        <form method="GET" action="{{ route('dashboard.persuratan.index') }}" id="filterForm" class="dash-toolbar" style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;">
            <div class="live-search-wrap" style="flex: 1; min-width: 260px;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="q" id="liveSearchInput" value="{{ $q ?? '' }}" placeholder="Cari nomor surat, perihal, atau instansi..." autocomplete="off">
                @if (!empty($q))
                    <a href="{{ route('dashboard.persuratan.index', array_filter(['status' => $status, 'jenis_surat' => $jenisSurat, 'per_page' => $perPage])) }}" class="clear-search" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </div>

            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <select name="jenis_surat" id="filterJenis" onchange="this.form.submit()" style="height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;">
                    <option value="">Semua Jenis</option>
                    <option value="masuk" {{ ($jenisSurat ?? '') === 'masuk' ? 'selected' : '' }}>Surat Masuk</option>
                    <option value="keluar" {{ ($jenisSurat ?? '') === 'keluar' ? 'selected' : '' }}>Surat Keluar</option>
                    <option value="disposisi" {{ ($jenisSurat ?? '') === 'disposisi' ? 'selected' : '' }}>Disposisi</option>
                    <option value="keputusan" {{ ($jenisSurat ?? '') === 'keputusan' ? 'selected' : '' }}>SK / Keputusan</option>
                    <option value="tugas" {{ ($jenisSurat ?? '') === 'tugas' ? 'selected' : '' }}>Surat Tugas</option>
                </select>

                <select name="status" id="filterStatus" onchange="this.form.submit()" style="height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;">
                    <option value="">Semua Status</option>
                    <option value="menunggu_disposisi" {{ ($status ?? '') === 'menunggu_disposisi' ? 'selected' : '' }}>Menunggu Disposisi</option>
                    <option value="diproses" {{ ($status ?? '') === 'diproses' ? 'selected' : '' }}>Sedang Diproses</option>
                    <option value="selesai" {{ ($status ?? '') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                    <option value="diarsipkan" {{ ($status ?? '') === 'diarsipkan' ? 'selected' : '' }}>Diarsipkan</option>
                    <option value="draf" {{ ($status ?? '') === 'draf' ? 'selected' : '' }}>Draf</option>
                </select>

                <select name="per_page" id="perPageSelect" onchange="this.form.submit()" style="height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;">
                    <option value="10" {{ ($perPage ?? 25) == 10 ? 'selected' : '' }}>10 Baris</option>
                    <option value="25" {{ ($perPage ?? 25) == 25 ? 'selected' : '' }}>25 Baris</option>
                    <option value="50" {{ ($perPage ?? 25) == 50 ? 'selected' : '' }}>50 Baris</option>
                    <option value="100" {{ ($perPage ?? 25) == 100 ? 'selected' : '' }}>100 Baris</option>
                </select>
            </div>
        </form>

        <!-- Tabel Responsif Baku SAE -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 0; border: none; box-shadow: none;">
            <table class="table table-pd dash-table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;" id="mainDataTable">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); text-align: left; background: var(--bg-hover);">
                        <th style="padding: 12px 14px; width: 45px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No</th>
                        <th style="padding: 12px 14px; width: 140px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jenis & Status</th>
                        <th style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nomor & Tanggal</th>
                        <th style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Perihal & Pihak Terkait</th>
                        <th style="padding: 12px 14px; width: 130px; text-align: center; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableBodyContent">
                    @forelse ($items as $index => $item)
                        <tr class="data-row" style="border-bottom: 1px solid var(--border-color);" data-id="{{ $item->id }}">
                            <td data-label="No" style="padding: 12px 14px; font-size: 0.84rem; color: var(--text-muted);">
                                {{ $items->firstItem() + $index }}
                            </td>
                            <td data-label="Jenis & Status" style="padding: 12px 14px;">
                                <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                                    {!! $item->jenis_badge !!}
                                    {!! $item->status_badge !!}
                                </div>
                            </td>
                            <td data-label="Nomor & Tanggal" style="padding: 12px 14px;">
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.88rem; margin-bottom: 2px;">
                                    {{ $item->nomor_surat }}
                                </div>
                                <div style="font-size: 0.78rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-calendar-day"></i> {{ \Carbon\Carbon::parse($item->tanggal_surat)->isoFormat('D MMMM Y') }}
                                    @if ($item->tanggal_diterima)
                                        <span title="Tanggal Diterima">• Terima: {{ \Carbon\Carbon::parse($item->tanggal_diterima)->isoFormat('D MMM Y') }}</span>
                                    @endif
                                </div>
                            </td>
                            <td data-label="Perihal & Pihak" style="padding: 12px 14px;">
                                <div style="font-weight: 600; color: var(--text-color); font-size: 0.86rem; margin-bottom: 4px;">
                                    {{ $item->perihal }}
                                </div>
                                <div style="font-size: 0.78rem; color: var(--text-muted); display: flex; gap: 12px; flex-wrap: wrap;">
                                    @if ($item->pengirim_asal)
                                        <span><i class="fas fa-arrow-right-from-bracket text-primary me-1"></i> Dari: <strong>{{ $item->pengirim_asal }}</strong></span>
                                    @endif
                                    @if ($item->tujuan_penerima)
                                        <span><i class="fas fa-arrow-right-to-bracket text-success me-1"></i> Kepada: <strong>{{ $item->tujuan_penerima }}</strong></span>
                                    @endif
                                </div>
                            </td>
                            <td data-label="Aksi" style="padding: 12px 14px; text-align: center;">
                                <div class="table-actions" style="display: inline-flex; gap: 6px;">
                                    <button type="button" class="btn btn-outline btn-sm btn-detail-row" data-id="{{ $item->id }}" title="Lihat Detail Surat" style="padding: 4px 8px; font-size: 0.78rem;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if ($canUpdate)
                                        <button type="button" class="btn btn-outline btn-sm btn-edit-row" data-id="{{ $item->id }}" title="Edit Surat" style="padding: 4px 8px; font-size: 0.78rem;">
                                            <i class="fas fa-pen-to-square"></i>
                                        </button>
                                    @endif
                                    @if ($canDelete)
                                        <button type="button" class="btn btn-outline btn-sm btn-delete-row" data-id="{{ $item->id }}" data-nomor="{{ $item->nomor_surat }}" title="Hapus Surat" style="padding: 4px 8px; font-size: 0.78rem; color: #ef4444; border-color: rgba(239,68,68,0.3);">
                                            <i class="fas fa-trash-can"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyTableRow">
                            <td colspan="5" style="text-align: center; padding: 40px 14px; color: var(--text-muted);">
                                <i class="fas fa-inbox" style="font-size: 2.4rem; margin-bottom: 12px; display: block; opacity: 0.35;"></i>
                                <span style="font-weight: 600; font-size: 0.92rem;">Belum ada arsip surat yang sesuai.</span>
                                <p style="font-size: 0.8rem; margin: 4px 0 0 0; color: var(--text-muted);">Gunakan tombol "Catat Surat Baru" di atas untuk menambahkan data surat dinas.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Baku Custom Pagination SAE -->
        @if ($items->hasPages())
            <div class="custom-pagination" style="margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div class="page-info" style="font-size: 0.82rem; color: var(--text-muted);">
                    Menampilkan <strong>{{ $items->firstItem() ?? 0 }}</strong> - <strong>{{ $items->lastItem() ?? 0 }}</strong> dari <strong>{{ $items->total() }}</strong> arsip surat
                </div>
                <div style="display: flex; gap: 4px; align-items: center;">
                    @if ($items->onFirstPage())
                        <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                    @else
                        <a href="{{ $items->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                    @endif

                    @php
                        $cur = $items->currentPage();
                        $last = $items->lastPage();
                        $from = max(1, $cur - 2);
                        $to = min($last, $cur + 2);
                    @endphp

                    @if ($from > 1)
                        <a href="{{ $items->url(1) }}" class="page-btn">1</a>
                        @if ($from > 2) <span class="page-info">&hellip;</span> @endif
                    @endif

                    @for ($i = $from; $i <= $to; $i++)
                        <a href="{{ $items->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                    @endfor

                    @if ($to < $last)
                        @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                        <a href="{{ $items->url($last) }}" class="page-btn">{{ $last }}</a>
                    @endif

                    @if ($items->hasMorePages())
                        <a href="{{ $items->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                    @else
                        <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                    @endif
                </div>
            </div>
        @else
            <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color); font-size: 0.82rem; color: var(--text-muted);">
                Total <strong>{{ $items->total() }}</strong> arsip surat tercatat.
            </div>
        @endif
    </div>

    <!-- 5. Modal Form Persuratan (z-index: 99999 !important) -->
    <div id="modalFormItem" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 620px; width: 92%; margin: 0; border-radius: 14px; padding: 24px; box-shadow: 0 16px 40px rgba(0,0,0,0.45); border: 1px solid var(--border-color); background: var(--bg-card, #1e293b); max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 id="modalTitle" style="font-size: 1.1rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-envelope-circle-check text-primary"></i> Form Catat Surat
                </h3>
                <button type="button" id="btnCloseModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.15rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="mainFormItem">
                @csrf
                <input type="hidden" id="formItemId" name="id">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                            Nomor Surat <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" id="inputNomorSurat" name="nomor_surat" required placeholder="Contoh: 421.3/089/SMK/2026"
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                            Jenis Surat <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="inputJenisSurat" name="jenis_surat" required
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                            <option value="masuk">Surat Masuk</option>
                            <option value="keluar">Surat Keluar</option>
                            <option value="disposisi">Disposisi</option>
                            <option value="keputusan">SK / Keputusan</option>
                            <option value="tugas">Surat Tugas</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                        Perihal / Judul Surat <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" id="inputPerihal" name="perihal" required placeholder="Contoh: Undangan Rapat Koordinasi..."
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                            Pengirim / Asal Surat
                        </label>
                        <input type="text" id="inputPengirim" name="pengirim_asal" placeholder="Contoh: Dinas Pendidikan..."
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                            Tujuan / Penerima Surat
                        </label>
                        <input type="text" id="inputTujuan" name="tujuan_penerima" placeholder="Contoh: Kepala Sekolah..."
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                            Tanggal Surat <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="date" id="inputTanggalSurat" name="tanggal_surat" required
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                            Tanggal Diterima
                        </label>
                        <input type="date" id="inputTanggalDiterima" name="tanggal_diterima"
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                            Status Surat <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="inputStatus" name="status" required
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                            <option value="menunggu_disposisi">Menunggu Disposisi</option>
                            <option value="diproses">Sedang Diproses</option>
                            <option value="selesai">Selesai</option>
                            <option value="diarsipkan">Diarsipkan</option>
                            <option value="draf">Draf</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 18px;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                        Catatan Disposisi / Ringkasan Isi
                    </label>
                    <textarea id="inputKeterangan" name="keterangan" rows="3" placeholder="Tambahkan instruksi disposisi, perihal penting, atau keterangan lainnya..."
                        style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box; resize: vertical;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" id="btnCancelModal" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.84rem; border-radius: 8px;">
                        Batal
                    </button>
                    <button type="submit" id="btnSaveModal" class="btn btn-primary" style="padding: 8px 18px; font-size: 0.84rem; border-radius: 8px; font-weight: 600;">
                        <i class="fas fa-check me-1"></i> Simpan Surat
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 6. Modal Detail Surat (z-index: 99999 !important) -->
    <div id="modalDetailItem" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 580px; width: 92%; margin: 0; border-radius: 14px; padding: 24px; box-shadow: 0 16px 40px rgba(0,0,0,0.45); border: 1px solid var(--border-color); background: var(--bg-card, #1e293b);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-circle-info text-info"></i> Informasi Rincian Surat
                </h3>
                <button type="button" id="btnCloseDetailModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.15rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="detailContent" style="font-size: 0.86rem;">
                <!-- Diisi via JavaScript -->
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px; margin-top: 18px;">
                <div id="detailActionExtra" style="display: flex; gap: 8px;"></div>
                <button type="button" id="btnCloseDetailBtn" class="btn btn-outline" style="padding: 8px 18px; font-size: 0.84rem; border-radius: 8px;">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- 7. Modal Form Disposisi Surat Masuk (z-index: 99999 !important) -->
    <div id="modalDisposisiItem" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 580px; width: 92%; margin: 0; border-radius: 14px; padding: 24px; box-shadow: 0 16px 40px rgba(0,0,0,0.45); border: 1px solid var(--border-color); background: var(--bg-card, #1e293b);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-clipboard-check text-primary"></i> Lembar Disposisi Surat
                </h3>
                <button type="button" id="btnCloseDisposisiModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.15rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formDisposisiItem">
                @csrf
                <input type="hidden" id="disposisiSuratId">

                <div style="background: var(--bg-hover); padding: 10px 14px; border-radius: 8px; margin-bottom: 14px; font-size: 0.82rem;">
                    <div id="dispSuratNomor" style="font-weight: 700; color: var(--primary);"></div>
                    <div id="dispSuratPerihal" style="color: var(--text-color); margin-top: 2px;"></div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                        Diteruskan Kepada (Tujuan Disposisi) <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" id="dispTujuan" required placeholder="Contoh: Waka Kurikulum, Kepala TAS, Guru Piket..."
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                            Instruksi Disposisi <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="dispInstruksi" required
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                            <option value="Tanggapi / Tindak Lanjuti">Tanggapi / Tindak Lanjuti</option>
                            <option value="Hadir / Wakili">Hadir / Wakili</option>
                            <option value="Teliti & Laporkan">Teliti &amp; Laporkan</option>
                            <option value="Koordinasikan">Koordinasikan</option>
                            <option value="Bicarakan Bersama">Bicarakan Bersama</option>
                            <option value="Arsipkan">Ketahui / Arsipkan</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                            Tanggal Disposisi <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="date" id="dispTanggal" required value="{{ date('Y-m-d') }}"
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                    </div>
                </div>

                <div style="margin-bottom: 18px;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                        Catatan / Petunjuk Tambahan Pimpinan
                    </label>
                    <textarea id="dispCatatan" rows="3" placeholder="Tambahkan petunjuk khusus pelaksanaan tugas..."
                        style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box; resize: vertical;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" id="btnCancelDisposisiModal" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.84rem; border-radius: 8px;">
                        Batal
                    </button>
                    <button type="submit" id="btnSaveDisposisiModal" class="btn btn-primary" style="padding: 8px 18px; font-size: 0.84rem; border-radius: 8px; font-weight: 600;">
                        <i class="fas fa-check me-1"></i> Simpan Disposisi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 8. Modal Terbitkan Surat Keterangan Siswa (z-index: 99999 !important) -->
    <div id="modalSuratKetItem" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 600px; width: 92%; margin: 0; border-radius: 14px; padding: 24px; box-shadow: 0 16px 40px rgba(0,0,0,0.45); border: 1px solid var(--border-color); background: var(--bg-card, #1e293b);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-file-signature text-primary"></i> Terbitkan Surat Keterangan Siswa
                </h3>
                <button type="button" id="btnCloseKetModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.15rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formSuratKetItem">
                @csrf
                <div style="margin-bottom: 14px;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                        Pilih Peserta Didik <span style="color: #ef4444;">*</span>
                    </label>
                    <select id="ketPesertaDidikId" required
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                        <option value="">-- Cari / Pilih Peserta Didik --</option>
                        @foreach ($siswaList as $sw)
                            <option value="{{ $sw->peserta_didik_id }}">
                                {{ $sw->nama }} (NISN: {{ $sw->nisn ?: '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                            Jenis Surat Keterangan <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="ketJenisSurat" required
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                            <option value="siswa_aktif">Surat Keterangan Siswa Aktif</option>
                            <option value="kelakuan_baik">Surat Keterangan Berkelakuan Baik</option>
                            <option value="rekomendasi">Surat Rekomendasi Siswa</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                            Tanggal Surat <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="date" id="ketTanggal" required value="{{ date('Y-m-d') }}"
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                    </div>
                </div>

                <div style="margin-bottom: 18px;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                        Keperluan Penerbitan Surat <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" id="ketKeperluan" required placeholder="Contoh: Persyaratan Tunjangan Orang Tua / Beasiswa / Lomba..."
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" id="btnCancelKetModal" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.84rem; border-radius: 8px;">
                        Batal
                    </button>
                    <button type="submit" id="btnSaveKetModal" class="btn btn-primary" style="padding: 8px 18px; font-size: 0.84rem; border-radius: 8px; font-weight: 600;">
                        <i class="fas fa-check me-1"></i> Terbitkan &amp; Cetak
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/persuratan.js') }}"></script>
@endpush