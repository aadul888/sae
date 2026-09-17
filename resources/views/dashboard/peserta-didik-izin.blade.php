@extends('layouts.dashboard')

@section('title', 'Surat Izin & Sakit Peserta Didik — SAE')

@section('content')
    <!-- 1. Header Banner & Action Button -->
    <div class="dash-header-banner" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <div>
            <h2 style="font-size: 1.45rem; font-weight: 800; color: var(--text-color); margin: 0 0 4px 0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-envelope-open-text text-primary"></i> Surat Izin &amp; Sakit
            </h2>
            <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">
                Permohonan dan riwayat surat izin, surat keterangan sakit dokter, serta dispensasi resmi peserta didik.
            </p>
        </div>

        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            @if ($canCreate)
                <button type="button" class="btn btn-primary btn-responsive-icon" id="btnBukaModalIzin"
                    title="Ajukan Surat Izin atau Sakit Baru"
                    style="padding: 8px 16px; font-size: 0.84rem; border-radius: 8px; font-weight: 700;">
                    <i class="fas fa-plus"></i>
                    <span class="btn-responsive-text">Ajukan Permohonan</span>
                </button>
            @endif

            <a href="{{ route('dashboard.peserta-didik.presensi.index') }}" class="btn btn-outline btn-responsive-icon"
                title="Lihat Riwayat Presensi Lengkap"
                style="padding: 8px 14px; font-size: 0.84rem; border-radius: 8px;">
                <i class="fas fa-calendar-check text-accent"></i>
                <span class="btn-responsive-text">Riwayat Presensi</span>
            </a>
        </div>
    </div>

    <!-- 2. Stat Grid Baku SAE (4 Kartu Ringkasan) -->
    <div class="dash-stat-grid" id="dashStatGrid" style="margin-bottom: 20px;">
        <div class="card stat-card" style="margin-bottom: 0; padding: 16px 18px; border-left: 4px solid var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <div style="font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Total Pengajuan
                    </div>
                    <div style="font-size: 1.6rem; font-weight: 800; color: var(--text-color); margin-top: 4px;">
                        {{ $statTotal }}
                    </div>
                </div>
                <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                    <i class="fas fa-folder-open"></i>
                </div>
            </div>
            <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 6px;">
                Semua surat yang pernah diajukan
            </div>
        </div>

        <div class="card stat-card" style="margin-bottom: 0; padding: 16px 18px; border-left: 4px solid #f59e0b;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <div style="font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Menunggu Validasi
                    </div>
                    <div style="font-size: 1.6rem; font-weight: 800; color: #f59e0b; margin-top: 4px;">
                        {{ $statMenunggu }}
                    </div>
                </div>
                <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(245,158,11,0.12); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                    <i class="fas fa-hourglass-half"></i>
                </div>
            </div>
            <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 6px;">
                Menunggu peninjauan Wali Kelas
            </div>
        </div>

        <div class="card stat-card" style="margin-bottom: 0; padding: 16px 18px; border-left: 4px solid #10b981;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <div style="font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Telah Disetujui
                    </div>
                    <div style="font-size: 1.6rem; font-weight: 800; color: #10b981; margin-top: 4px;">
                        {{ $statDisetujui }}
                    </div>
                </div>
                <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(16,185,129,0.12); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 6px;">
                Tercatat resmi di presensi harian
            </div>
        </div>

        <div class="card stat-card" style="margin-bottom: 0; padding: 16px 18px; border-left: 4px solid #ef4444;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <div style="font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Permohonan Ditolak
                    </div>
                    <div style="font-size: 1.6rem; font-weight: 800; color: #ef4444; margin-top: 4px;">
                        {{ $statDitolak }}
                    </div>
                </div>
                <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(239,68,68,0.12); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                    <i class="fas fa-times-circle"></i>
                </div>
            </div>
            <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 6px;">
                Permohonan ditolak oleh sekolah
            </div>
        </div>
    </div>

    <!-- 3. Toolbar Tabel (Live Search, Filter, & Paging Entries) -->
    <div class="card" style="padding: 14px 18px; margin-bottom: 16px;">
        <form id="filterIzinForm" method="GET" action="{{ route('dashboard.peserta-didik.izin.index') }}"
            style="display: flex; gap: 12px; align-items: center; justify-content: space-between; flex-wrap: wrap;">
            
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; flex: 1; min-width: 260px;">
                <!-- Live Search Box -->
                <div class="live-search-wrap" style="flex: 1; min-width: 200px; max-width: 320px; position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
                    <input type="text" name="q" id="inputSearchIzin" value="{{ request('q') }}"
                        placeholder="Cari alasan atau catatan..." class="form-control"
                        style="padding-left: 36px !important; font-size: 0.84rem; height: 38px; width: 100%;">
                    @if (request('q'))
                        <button type="button" class="btn-clear-search" id="btnClearSearch"
                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer;">
                            <i class="fas fa-times"></i>
                        </button>
                    @endif
                </div>

                <!-- Filter Jenis -->
                <select name="jenis" id="filterJenis" class="form-control"
                    style="font-size: 0.84rem; height: 38px; width: auto; min-width: 130px;">
                    <option value="">Semua Jenis</option>
                    <option value="izin" {{ request('jenis') === 'izin' ? 'selected' : '' }}>Izin</option>
                    <option value="sakit" {{ request('jenis') === 'sakit' ? 'selected' : '' }}>Sakit</option>
                    <option value="dispen" {{ request('jenis') === 'dispen' ? 'selected' : '' }}>Dispensasi</option>
                </select>

                <!-- Filter Status -->
                <select name="status" id="filterStatus" class="form-control"
                    style="font-size: 0.84rem; height: 38px; width: auto; min-width: 140px;">
                    <option value="">Semua Status</option>
                    <option value="menunggu" {{ request('status') === 'menunggu' ? 'selected' : '' }}>Menunggu</option>
                    <option value="disetujui" {{ request('status') === 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="ditolak" {{ request('status') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px; align-items: center;">
                <label style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Baris:</label>
                <select name="per_page" id="filterPerPage" class="form-control"
                    style="font-size: 0.84rem; height: 38px; width: 75px;">
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                    <option value="15" {{ $perPage == 15 ? 'selected' : '' }}>15</option>
                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                </select>
            </div>
        </form>
    </div>

    <!-- 4. Datatable Responsif (.table-responsive-stack dengan data-label) -->
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">
                        Jenis
                    </th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Rentang Waktu
                    </th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Alasan &amp; Bukti
                    </th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 160px;">
                        Status Validasi
                    </th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">
                        Diajukan Pada
                    </th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 90px;">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $item)
                    @php
                        $startDate = \Carbon\Carbon::parse($item->tanggal_mulai);
                        $endDate = \Carbon\Carbon::parse($item->tanggal_selesai);
                        $durasiHari = $startDate->diffInDays($endDate) + 1;
                    @endphp
                    <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                        <!-- Kolom Jenis -->
                        <td class="cell-pd-jenis" style="padding: 14px 18px; vertical-align: middle;" data-label="Jenis">
                            @if ($item->jenis === 'sakit')
                                <span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px;">
                                    <i class="fas fa-notes-medical me-1"></i> Sakit
                                </span>
                            @elseif ($item->jenis === 'dispen')
                                <span class="badge" style="background: rgba(168,85,247,0.15); color: #a855f7; border: 1px solid rgba(168,85,247,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px;">
                                    <i class="fas fa-award me-1"></i> Dispensasi
                                </span>
                            @else
                                <span class="badge" style="background: rgba(59,130,246,0.15); color: #3b82f6; border: 1px solid rgba(59,130,246,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px;">
                                    <i class="fas fa-file-lines me-1"></i> Izin
                                </span>
                            @endif
                        </td>

                        <!-- Kolom Rentang Waktu -->
                        <td class="cell-pd-tanggal" style="padding: 14px 18px; vertical-align: middle;" data-label="Rentang Waktu">
                            <div style="font-weight: 700; color: var(--text-color); font-size: 0.88rem;">
                                {{ $startDate->translatedFormat('d M Y') }}
                                @if ($item->tanggal_mulai !== $item->tanggal_selesai)
                                    <span style="color: var(--text-muted); font-weight: normal;"> s/d </span>
                                    {{ $endDate->translatedFormat('d M Y') }}
                                @endif
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                                <i class="fas fa-clock text-primary me-1"></i> Durasi: <strong style="color: var(--text-color);">{{ $durasiHari }} hari</strong>
                            </div>
                        </td>

                        <!-- Kolom Alasan & Bukti -->
                        <td class="cell-pd-alasan" style="padding: 14px 18px; vertical-align: middle;" data-label="Alasan & Bukti">
                            <div style="font-size: 0.86rem; color: var(--text-color); font-weight: 500; line-height: 1.4; margin-bottom: 4px;">
                                {{ $item->alasan }}
                            </div>
                            @if (!empty($item->lampiran_path))
                                <div>
                                    <button type="button" class="btn btn-outline btn-sm btn-preview-lampiran"
                                        data-url="{{ asset('storage/' . ltrim($item->lampiran_path, '/')) }}"
                                        data-title="Lampiran Surat {{ $item->jenis_label }}"
                                        style="font-size: 0.74rem; padding: 3px 8px; border-radius: 6px;">
                                        <i class="fas fa-paperclip me-1 text-primary"></i> Lihat Berkas Lampiran
                                    </button>
                                </div>
                            @else
                                <span style="font-size: 0.74rem; color: var(--text-muted); font-style: italic;">
                                    Tanpa lampiran berkas
                                </span>
                            @endif
                        </td>

                        <!-- Kolom Status Validasi -->
                        <td class="cell-pd-status" style="padding: 14px 18px; vertical-align: middle;" data-label="Status Validasi">
                            @if ($item->status === 'disetujui')
                                <span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 8px; border-radius: 6px;">
                                    <i class="fas fa-check-circle me-1"></i> Disetujui
                                </span>
                                @if ($item->disetujui_oleh)
                                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px;">
                                        Oleh: {{ $item->disetujui_oleh }}
                                    </div>
                                @endif
                            @elseif ($item->status === 'ditolak')
                                <span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 8px; border-radius: 6px;">
                                    <i class="fas fa-times-circle me-1"></i> Ditolak
                                </span>
                                @if ($item->catatan_petugas)
                                    <div style="font-size: 0.72rem; color: #ef4444; margin-top: 3px;">
                                        Catatan: {{ $item->catatan_petugas }}
                                    </div>
                                @endif
                            @else
                                <span class="badge" style="background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 8px; border-radius: 6px;">
                                    <i class="fas fa-hourglass-half me-1"></i> Menunggu
                                </span>
                                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px;">
                                    Menunggu verifikasi
                                </div>
                            @endif
                        </td>

                        <!-- Kolom Tanggal Diajukan -->
                        <td class="cell-pd-created" style="padding: 14px 18px; vertical-align: middle; font-size: 0.82rem; color: var(--text-muted);" data-label="Diajukan Pada">
                            {{ \Carbon\Carbon::parse($item->created_at)->translatedFormat('d/m/Y') }}
                            <div style="font-size: 0.72rem;">
                                {{ \Carbon\Carbon::parse($item->created_at)->format('H:i') }} WIB
                            </div>
                        </td>

                        <!-- Kolom Aksi -->
                        <td class="cell-pd-aksi" style="padding: 14px 18px; vertical-align: middle; text-align: right;" data-label="Aksi">
                            <div class="table-actions" style="justify-content: flex-end;">
                                @if ($item->status === 'menunggu' && $canDelete)
                                    <button type="button" class="btn-icon btn-cancel-izin"
                                        data-id="{{ $item->id }}"
                                        data-jenis="{{ $item->jenis_label }}"
                                        title="Batalkan Pengajuan Ini"
                                        style="color: #ef4444; border-color: rgba(239,68,68,0.25);">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                @else
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">&mdash;</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                            <div style="font-size: 2.4rem; opacity: 0.35; margin-bottom: 10px;">
                                <i class="fas fa-envelope-open-text"></i>
                            </div>
                            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color); margin-bottom: 4px;">
                                Belum Ada Permohonan Surat Izin / Sakit
                            </div>
                            <div style="font-size: 0.8rem; max-width: 420px; margin: auto;">
                                Klik tombol <strong>+ Ajukan Permohonan</strong> di bagian atas untuk mengajukan izin atau sakit kepada pihak sekolah.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- 5. Custom Pagination Resmi SAE (Dilarang Memakai $list->links() Mentah) -->
    @if ($list->hasPages())
        <div class="custom-pagination" style="margin-bottom: 24px;">
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
                @if ($from > 2) <span class="page-info">&hellip;</span> @endif
            @endif

            @for ($i = $from; $i <= $to; $i++)
                <a href="{{ $list->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor

            @if ($to < $last)
                @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                <a href="{{ $list->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif

            @if ($list->hasMorePages())
                <a href="{{ $list->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif

    <!-- 6. Modal Form Pengajuan Surat Izin (z-index: 99999 !important) -->
    <div id="modalFormIzin" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.68); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 520px; width: 92%; margin: auto; padding: 24px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 16px 40px rgba(0,0,0,0.5);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 style="font-size: 1.12rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-paper-plane text-primary"></i> Ajukan Surat Izin / Sakit
                </h3>
                <button type="button" id="btnCloseModalIzin" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.25rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formSubmitIzin" method="POST" action="{{ route('dashboard.peserta-didik.izin.store') }}" enctype="multipart/form-data">
                @csrf

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                        Jenis Permohonan: <span style="color: #ef4444;">*</span>
                    </label>
                    <select name="jenis" id="modalSelectJenis" required class="form-control" style="width: 100%; font-size: 0.86rem; padding: 9px 12px;">
                        <option value="izin">Izin (Keperluan Keluarga / Khusus)</option>
                        <option value="sakit">Sakit (Wajib Lampirkan Surat Dokter)</option>
                        <option value="dispen">Dispensasi (Lomba / Agenda Sekolah)</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                            Mulai Tanggal: <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="date" name="tanggal_mulai" id="modalTglMulai" value="{{ now()->toDateString() }}" required class="form-control"
                            style="width: 100%; font-size: 0.85rem; padding: 8px 10px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                            Sampai Tanggal: <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="date" name="tanggal_selesai" id="modalTglSelesai" value="{{ now()->toDateString() }}" required class="form-control"
                            style="width: 100%; font-size: 0.85rem; padding: 8px 10px;">
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                        Alasan / Keterangan Lengkap: <span style="color: #ef4444;">*</span>
                    </label>
                    <textarea name="alasan" id="modalAlasan" rows="3" required placeholder="Tuliskan keterangan detail alasan izin atau kondisi sakit Anda..." class="form-control"
                        style="width: 100%; font-size: 0.85rem; padding: 9px 12px; line-height: 1.4;"></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                        Berkas Surat / Surat Keterangan Dokter:
                    </label>
                    <input type="file" name="lampiran" id="modalLampiran" accept=".jpg,.jpeg,.png,.pdf" class="form-control"
                        style="width: 100%; font-size: 0.82rem; padding: 8px;">
                    <small style="color: var(--text-muted); font-size: 0.73rem; display: block; margin-top: 5px;">
                        Format berkas: JPG, PNG, atau PDF (Maksimal 4 MB).
                    </small>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" id="btnBatalModalIzin" class="btn btn-outline" style="padding: 8px 18px; font-size: 0.85rem; border-radius: 8px;">
                        Batal
                    </button>
                    <button type="submit" id="btnSubmitIzin" class="btn btn-primary" style="padding: 8px 22px; font-size: 0.85rem; font-weight: 700; border-radius: 8px;">
                        <i class="fas fa-paper-plane me-1"></i> Kirim Permohonan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 7. Modal Preview Berkas Lampiran (z-index: 99999 !important) -->
    <div id="modalPreviewLampiran" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.72); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 680px; width: 92%; max-height: 90vh; margin: auto; padding: 20px; border-radius: 16px; display: flex; flex-direction: column; border: 1px solid var(--border-color); box-shadow: 0 16px 40px rgba(0,0,0,0.5);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <h4 id="previewLampiranTitle" style="font-size: 0.98rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Berkas Lampiran
                </h4>
                <button type="button" id="btnClosePreviewLampiran" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.25rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="previewContainer" style="flex: 1; overflow: auto; text-align: center; min-height: 240px; display: flex; align-items: center; justify-content: center;">
                <!-- Konten dinamis (gambar atau iframe PDF) -->
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/peserta-didik-izin.js') }}?v={{ file_exists(public_path('js/peserta-didik-izin.js')) ? filemtime(public_path('js/peserta-didik-izin.js')) : time() }}"></script>
@endpush
