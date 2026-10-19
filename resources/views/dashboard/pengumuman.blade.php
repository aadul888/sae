@extends('layouts.dashboard')

@section('title', 'Pengumuman & Broadcast — SAE')
@section('dash_title', 'Pengumuman & Broadcast')

@section('content')
    <!-- Banner Header -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-bullhorn text-primary me-2"></i> Pengumuman &amp; Broadcast
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
                Kelola informasi teks berjalan portal publik dan notifikasi lonceng pengguna dashboard secara terpusat.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('dashboard.informasi.index') }}" class="btn btn-outline"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem; text-decoration: none;"
                title="Tampilan Feed Pengguna">
                <i class="fas fa-newspaper text-primary"></i>
            </a>
            @if ($canCreate)
                <button type="button" class="btn btn-primary" id="btnOpenCreateModal"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;"
                    title="Buat Pengumuman Baru">
                    <i class="fas fa-plus"></i>
                </button>
            @endif
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="dash-stat-grid">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                <i class="fas fa-bullhorn"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $summary['total'] }}</div>
                <div class="dash-stat-label">Total Pengumuman</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $summary['aktif'] }}</div>
                <div class="dash-stat-label">Sedang Tayang (Aktif)</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6, 182, 212, 0.15); color: var(--accent);">
                <i class="fas fa-globe"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $summary['publik'] }}</div>
                <div class="dash-stat-label">Teks Berjalan Publik</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                <i class="fas fa-bell"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $summary['lonceng'] }}</div>
                <div class="dash-stat-label">Lonceng Pengguna</div>
            </div>
        </div>
    </div>

    <!-- Unified Datatable Pengumuman (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
    <div class="card datatable-card-wrapper" id="tableDataContainer">
        <!-- 1. Toolbar Header Datatable Baku SAE (Entries, Filters & Live Search) -->
        <div class="datatable-toolbar">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                        <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                            @foreach ([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>
                                    {{ $n }}</option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    <select id="filterTargetSelect" name="target" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Target Tampilan</option>
                        <option value="publik" {{ $targetFilter === 'publik' ? 'selected' : '' }}>🌐 Teks Berjalan Publik
                        </option>
                        <option value="pengguna" {{ $targetFilter === 'pengguna' ? 'selected' : '' }}>🔔 Lonceng Pengguna
                        </option>
                        <option value="semua" {{ $targetFilter === 'semua' ? 'selected' : '' }}>📢 Keduanya (Publik &amp;
                            Lonceng)</option>
                    </select>

                    <select id="filterTargetPeranSelect" name="target_peran" class="toolbar-filter-select"
                        style="height: 38px;">
                        <option value="">Semua Sasaran Peran</option>
                        <option value="semua" {{ $targetPeranFilter === 'semua' ? 'selected' : '' }}>👥 Semua Pengguna
                        </option>
                        <option value="guru" {{ $targetPeranFilter === 'guru' ? 'selected' : '' }}>👨‍🏫 Khusus Guru
                        </option>
                        <option value="tendik" {{ $targetPeranFilter === 'tendik' ? 'selected' : '' }}>🪪 Khusus Tendik
                        </option>
                        <option value="peserta_didik" {{ $targetPeranFilter === 'peserta_didik' ? 'selected' : '' }}>🎓
                            Khusus Peserta Didik</option>
                        <option value="admin" {{ $targetPeranFilter === 'admin' ? 'selected' : '' }}>🛡️ Khusus
                            Administrator</option>
                    </select>

                    <select id="filterStatusSelect" name="status" class="toolbar-filter-select"
                        style="min-width: 120px; height: 38px;">
                        <option value="">Semua Status</option>
                        <option value="1" {{ $statusFilter === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ $statusFilter === '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>

                    @if ($q || $targetFilter || $targetPeranFilter || $statusFilter !== '')
                        <a href="{{ route('dashboard.pengumuman.index') }}" class="btn btn-outline btn-reset-filter"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;"
                            title="Reset filter" data-live-reset>
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <div class="live-search-wrap" style="height: 38px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" name="q" placeholder="Cari judul / isi / penulis..."
                        value="{{ $q }}" autocomplete="off">
                    <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}"
                        title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. DataTable Responsive Stack -->
        <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead class="sticky-table-header">
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">
                            No</th>
                        <th class="sortable-th {{ $sort === 'judul' ? 'sorted' : '' }}" data-sort="judul"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                            Judul &amp; Pesan Pengumuman
                            <span class="sort-icon">{!! $sort === 'judul' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th class="sortable-th {{ $sort === 'target' ? 'sorted' : '' }}" data-sort="target"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 160px; cursor: pointer;">
                            Target Tampilan
                            <span class="sort-icon">{!! $sort === 'target' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th class="sortable-th {{ $sort === 'target_peran' ? 'sorted' : '' }}" data-sort="target_peran"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px; cursor: pointer;">
                            Ditujukan Kepada
                            <span class="sort-icon">{!! $sort === 'target_peran' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">
                            Pembaca</th>
                        <th class="sortable-th {{ $sort === 'is_active' ? 'sorted' : '' }}" data-sort="is_active"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px; cursor: pointer;">
                            Status
                            <span class="sort-icon">{!! $sort === 'is_active' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th class="sortable-th {{ $sort === 'created_at' ? 'sorted' : '' }}" data-sort="created_at"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 150px; cursor: pointer;">
                            Tanggal
                            <span class="sort-icon">{!! $sort === 'created_at' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 110px;">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($list as $index => $item)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td
                                style="padding: 14px 18px; font-weight: 600; color: var(--text-muted); text-align: center; font-size: 0.84rem;">
                                {{ $list->firstItem() + $index }}
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.88rem;">
                                <div style="font-weight: 700; color: var(--text-color); margin-bottom: 3px;">
                                    {{ $item->judul }}
                                </div>
                                <div
                                    style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4; max-width: 520px;">
                                    {{ Str::limit($item->isi, 130) }}
                                </div>
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.82rem;">
                                @if ($item->target === 'publik')
                                    <span class="badge badge-accent"
                                        style="font-size: 0.72rem; padding: 4px 8px; border-radius: 6px;">
                                        <i class="fas fa-globe me-1"></i> Teks Publik
                                    </span>
                                @elseif($item->target === 'pengguna')
                                    <span class="badge badge-warning"
                                        style="font-size: 0.72rem; padding: 4px 8px; border-radius: 6px;">
                                        <i class="fas fa-bell me-1"></i> Lonceng Pengguna
                                    </span>
                                @else
                                    <span class="badge badge-primary"
                                        style="font-size: 0.72rem; padding: 4px 8px; border-radius: 6px;">
                                        <i class="fas fa-bullhorn me-1"></i> Publik &amp; Lonceng
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.82rem;">
                                @if ($item->target_peran === 'guru')
                                    <span class="badge badge-primary" style="font-size: 0.72rem; padding: 4px 8px;">
                                        <i class="fas fa-chalkboard-user me-1"></i> Guru
                                    </span>
                                @elseif($item->target_peran === 'tendik')
                                    <span class="badge badge-accent" style="font-size: 0.72rem; padding: 4px 8px;">
                                        <i class="fas fa-id-badge me-1"></i> Tendik
                                    </span>
                                @elseif($item->target_peran === 'peserta_didik')
                                    <span class="badge badge-success" style="font-size: 0.72rem; padding: 4px 8px;">
                                        <i class="fas fa-user-graduate me-1"></i> Peserta Didik
                                    </span>
                                @elseif($item->target_peran === 'admin')
                                    <span class="badge badge-danger" style="font-size: 0.72rem; padding: 4px 8px;">
                                        <i class="fas fa-user-shield me-1"></i> Admin
                                    </span>
                                @else
                                    <span class="badge badge-outline" style="font-size: 0.72rem; padding: 4px 8px;">
                                        <i class="fas fa-users me-1"></i> Semua Pengguna
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 14px 18px; text-align: center;">
                                <span class="badge badge-outline" style="font-size: 0.75rem; padding: 3px 8px;"
                                    title="Telah dibaca oleh {{ $item->jumlah_pembaca }} pengguna">
                                    <i class="fas fa-eye me-1 text-primary"></i> {{ $item->jumlah_pembaca }}
                                </span>
                            </td>
                            <td style="padding: 14px 18px; text-align: center;">
                                @if ($canUpdate)
                                    <label class="switch-container"
                                        style="position: relative; display: inline-block; width: 38px; height: 20px; margin: 0; cursor: pointer;"
                                        title="Klik untuk ubah status aktif">
                                        <input type="checkbox" class="toggle-pengumuman-status"
                                            data-id="{{ $item->id }}" {{ $item->is_active ? 'checked' : '' }}
                                            style="opacity: 0; width: 0; height: 0;">
                                        <span class="slider-toggle-crud"
                                            style="position: absolute; inset: 0; background-color: {{ $item->is_active ? '#10b981' : '#64748b' }}; transition: .3s; border-radius: 20px;"></span>
                                    </label>
                                @else
                                    <span class="badge {{ $item->is_active ? 'badge-success' : 'badge-outline' }}">
                                        {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.8rem; color: var(--text-muted);">
                                <div>{{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-' }}</div>
                                <div style="font-size: 0.72rem; opacity: 0.85;">
                                    <i class="fas fa-user-pen me-1"></i> {{ $item->penulis_nama ?: 'Administrator' }}
                                </div>
                            </td>
                            <td style="padding: 14px 18px; text-align: right;">
                                <div class="table-actions" style="display: inline-flex; align-items: center; gap: 6px;">
                                    <button type="button" class="btn-icon btn-preview-pengumuman"
                                        data-item='@json($item)' title="Lihat Pratinjau Pengumuman">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if ($canUpdate)
                                        <button type="button" class="btn-icon btn-edit-pengumuman"
                                            data-item='@json($item)' title="Ubah Pengumuman">
                                            <i class="fas fa-pen-to-square"></i>
                                        </button>
                                    @endif
                                    @if ($canDelete)
                                        <form action="{{ route('dashboard.pengumuman.destroy', $item->id) }}"
                                            method="POST" data-confirm="delete" data-name="{{ $item->judul }}"
                                            style="margin: 0; display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon danger" title="Hapus Pengumuman">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8"
                                style="padding: 36px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                <i class="fas fa-bullhorn mb-2" style="font-size: 2rem; opacity: 0.4;"></i>
                                <div>Belum ada data pengumuman yang sesuai. Klik <strong>"Buat Pengumuman"</strong> untuk
                                    memulai.</div>
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

    <!-- Modal Form Create/Edit Pengumuman -->
    <div id="pengumumanModal" class="modal-backdrop">
        <div class="card"
            style="max-width: 560px; width: 92%; margin: auto; padding: 22px; border-radius: 14px; box-shadow: 0 16px 40px rgba(0,0,0,0.4);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 id="modalTitle" style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                    <i class="fas fa-bullhorn text-primary me-2"></i> Buat Pengumuman Baru
                </h3>
                <button type="button" id="btnCloseModal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="pengumumanForm" action="{{ route('dashboard.pengumuman.store') }}" method="POST">
                @csrf
                <div id="methodField"></div>

                <div class="form-group-compact" style="margin-bottom: 14px;">
                    <label>Judul Pengumuman <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="judul" id="inputJudul" required
                        placeholder="Contoh: Jadwal Ujian Akhir Semester" maxlength="255">
                </div>

                <div class="form-group-compact" style="margin-bottom: 14px;">
                    <label>Penulis / Penerbit</label>
                    <input type="text" name="penulis_nama" id="inputPenulisNama"
                        placeholder="Contoh: Humas Sekolah / Kurikulum (Kosongkan untuk nama Anda)" maxlength="100">
                </div>

                <div class="modal-form-grid"
                    style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group-compact">
                        <label>Target Tampilan <span style="color: #ef4444;">*</span></label>
                        <select name="target" id="inputTarget" required>
                            <option value="semua">📢 Keduanya (Publik &amp; Lonceng)</option>
                            <option value="publik">🌐 Teks Berjalan Publik</option>
                            <option value="pengguna">🔔 Lonceng Pengguna</option>
                        </select>
                    </div>

                    <div class="form-group-compact">
                        <label>Ditujukan Kepada</label>
                        <select name="target_peran" id="inputTargetPeran">
                            <option value="semua">Semua Pengguna</option>
                            <option value="guru">Khusus Guru</option>
                            <option value="tendik">Khusus Tendik</option>
                            <option value="peserta_didik">Khusus Peserta Didik</option>
                            <option value="admin">Khusus Administrator</option>
                        </select>
                    </div>
                </div>

                <div class="form-group-compact" style="margin-bottom: 14px;">
                    <label>Isi Pesan Pengumuman <span style="color: #ef4444;">*</span></label>
                    <textarea name="isi" id="inputIsi" required rows="4"
                        placeholder="Tuliskan isi pengumuman secara jelas dan ringkas..."
                        style="width: 100%; padding: 10px 12px; font-size: 0.85rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-color); resize: vertical; box-sizing: border-box; line-height: 1.5;"></textarea>
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 4px;">
                        Teks ini akan ditampilkan pada teks berjalan portal publik atau pop-up lonceng &amp; feed pengguna.
                    </div>
                </div>

                <div
                    style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px; padding: 10px 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 8px;">
                    <input type="checkbox" name="is_active" id="inputIsActive" value="1" checked
                        style="width: 16px; height: 16px; cursor: pointer;">
                    <label for="inputIsActive"
                        style="font-size: 0.82rem; font-weight: 600; color: var(--text-color); cursor: pointer; margin: 0;">
                        Aktifkan pengumuman ini segera
                    </label>
                </div>

                <div
                    style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                    <button type="button" id="btnCancelModal" class="btn btn-outline"
                        style="padding: 8px 16px; font-size: 0.85rem;">Batal</button>
                    <button type="submit" class="btn btn-primary"
                        style="padding: 8px 20px; font-size: 0.85rem; font-weight: 600;">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Preview Pengumuman (Detail Admin) -->
    <div id="previewPengumumanModal" class="modal-backdrop">
        <div class="card"
            style="max-width: 580px; width: 92%; margin: auto; padding: 24px; border-radius: 14px; box-shadow: 0 16px 40px rgba(0,0,0,0.4);">
            <div
                style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <div>
                    <span id="previewTargetBadge" class="badge badge-primary"
                        style="font-size: 0.72rem; margin-bottom: 6px;"></span>
                    <span id="previewPeranBadge" class="badge badge-outline"
                        style="font-size: 0.72rem; margin-bottom: 6px;"></span>
                    <h3 id="previewJudul"
                        style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin: 6px 0 0 0; line-height: 1.3;">
                    </h3>
                </div>
                <button type="button" id="btnClosePreviewModal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div
                style="display: flex; align-items: center; gap: 12px; font-size: 0.78rem; color: var(--text-muted); margin-bottom: 16px;">
                <div><i class="fas fa-user-pen me-1 text-primary"></i> <span id="previewPenulis"></span></div>
                <span>&bull;</span>
                <div><i class="fas fa-clock me-1"></i> <span id="previewTanggal"></span></div>
                <span>&bull;</span>
                <div><i class="fas fa-eye me-1"></i> <span id="previewPembaca"></span> Pembaca</div>
            </div>

            <div id="previewIsi"
                style="font-size: 0.9rem; line-height: 1.6; color: var(--text-color); background: rgba(255,255,255,0.02); padding: 14px 16px; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 20px; white-space: pre-line;">
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="button" id="btnClosePreviewBtn" class="btn btn-outline"
                    style="padding: 8px 18px; font-size: 0.85rem;">Tutup</button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/pengumuman.js') }}"></script>
    @endpush
@endsection
