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
        <div class="dash-banner-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="{{ route('dashboard.informasi.index') }}" class="btn btn-outline"
                style="padding: 9px 16px; font-size: 0.85rem;">
                <i class="fas fa-newspaper me-1"></i> Tampilan Feed Pengguna
            </a>
            @if ($canCreate)
                <button type="button" class="btn btn-primary" id="btnOpenCreateModal"
                    style="padding: 9px 18px; font-size: 0.85rem; font-weight: 600;">
                    <i class="fas fa-plus me-1"></i> Buat Pengumuman
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

    <!-- Toolbar Filters -->
    <div class="toolbar-row">
        <div class="toolbar-entries" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
            <select id="perPageSelect" class="per-page-select" onchange="window.location.href=this.value">
                @foreach ([10, 25, 50, 100] as $n)
                    <option value="{{ request()->fullUrlWithQuery(['perPage' => $n, 'page' => 1]) }}"
                        {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                @endforeach
            </select>
            <span>entri</span>

            <select id="filterTargetSelect" class="toolbar-filter-select" onchange="window.location.href=this.value">
                <option value="{{ request()->fullUrlWithQuery(['target' => '', 'page' => 1]) }}">Semua Target Tampilan</option>
                <option value="{{ request()->fullUrlWithQuery(['target' => 'publik', 'page' => 1]) }}"
                    {{ $targetFilter === 'publik' ? 'selected' : '' }}>🌐 Teks Berjalan Publik</option>
                <option value="{{ request()->fullUrlWithQuery(['target' => 'pengguna', 'page' => 1]) }}"
                    {{ $targetFilter === 'pengguna' ? 'selected' : '' }}>🔔 Lonceng Pengguna</option>
                <option value="{{ request()->fullUrlWithQuery(['target' => 'semua', 'page' => 1]) }}"
                    {{ $targetFilter === 'semua' ? 'selected' : '' }}>📢 Keduanya (Publik &amp; Lonceng)</option>
            </select>

            <select id="filterTargetPeranSelect" class="toolbar-filter-select" onchange="window.location.href=this.value">
                <option value="{{ request()->fullUrlWithQuery(['target_peran' => '', 'page' => 1]) }}">Semua Sasaran Peran</option>
                <option value="{{ request()->fullUrlWithQuery(['target_peran' => 'semua', 'page' => 1]) }}"
                    {{ $targetPeranFilter === 'semua' ? 'selected' : '' }}>👥 Semua Pengguna</option>
                <option value="{{ request()->fullUrlWithQuery(['target_peran' => 'guru', 'page' => 1]) }}"
                    {{ $targetPeranFilter === 'guru' ? 'selected' : '' }}>👨‍🏫 Khusus Guru</option>
                <option value="{{ request()->fullUrlWithQuery(['target_peran' => 'tendik', 'page' => 1]) }}"
                    {{ $targetPeranFilter === 'tendik' ? 'selected' : '' }}>🪪 Khusus Tendik</option>
                <option value="{{ request()->fullUrlWithQuery(['target_peran' => 'peserta_didik', 'page' => 1]) }}"
                    {{ $targetPeranFilter === 'peserta_didik' ? 'selected' : '' }}>🎓 Khusus Peserta Didik</option>
                <option value="{{ request()->fullUrlWithQuery(['target_peran' => 'admin', 'page' => 1]) }}"
                    {{ $targetPeranFilter === 'admin' ? 'selected' : '' }}>🛡️ Khusus Administrator</option>
            </select>

            <select id="filterStatusSelect" class="toolbar-filter-select" style="min-width: 120px;"
                onchange="window.location.href=this.value">
                <option value="{{ request()->fullUrlWithQuery(['status' => '', 'page' => 1]) }}">Semua Status</option>
                <option value="{{ request()->fullUrlWithQuery(['status' => '1', 'page' => 1]) }}"
                    {{ $statusFilter === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="{{ request()->fullUrlWithQuery(['status' => '0', 'page' => 1]) }}"
                    {{ $statusFilter === '0' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>

        <div class="live-search-wrap">
            <form action="{{ route('dashboard.pengumuman.index') }}" method="GET" style="margin: 0;">
                @if ($targetFilter)
                    <input type="hidden" name="target" value="{{ $targetFilter }}">
                @endif
                @if ($targetPeranFilter)
                    <input type="hidden" name="target_peran" value="{{ $targetPeranFilter }}">
                @endif
                @if ($statusFilter !== '')
                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                @endif
                @if ($perPage)
                    <input type="hidden" name="perPage" value="{{ $perPage }}">
                @endif
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="q" placeholder="Cari judul / isi / penulis..." value="{{ $q }}"
                    autocomplete="off">
                @if ($q)
                    <a href="{{ request()->fullUrlWithQuery(['q' => null]) }}" class="clear-search visible"
                        title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </form>
        </div>
    </div>

    <!-- DataTable Card -->
    <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
        <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead class="sticky-table-header">
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">
                        No</th>
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Judul &amp; Pesan Pengumuman</th>
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 160px;">
                        Target Tampilan</th>
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">
                        Ditujukan Kepada</th>
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">
                        Pembaca</th>
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">
                        Status</th>
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 150px;">
                        Tanggal</th>
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
                            <div style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4; max-width: 520px;">
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
                                    <form action="{{ route('dashboard.pengumuman.destroy', $item->id) }}" method="POST"
                                        data-confirm="delete" data-name="{{ $item->judul }}"
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
                            <div>Belum ada data pengumuman yang sesuai. Klik <strong>"Buat Pengumuman"</strong> untuk memulai.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
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

    <!-- Modal Form Create/Edit Pengumuman -->
    <div id="pengumumanModal" class="modal-backdrop">
        <div class="card" style="max-width: 560px; width: 92%; margin: auto; padding: 22px; border-radius: 14px; box-shadow: 0 16px 40px rgba(0,0,0,0.4);">
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

                <div class="modal-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
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
        <div class="card" style="max-width: 580px; width: 92%; margin: auto; padding: 24px; border-radius: 14px; box-shadow: 0 16px 40px rgba(0,0,0,0.4);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <div>
                    <span id="previewTargetBadge" class="badge badge-primary" style="font-size: 0.72rem; margin-bottom: 6px;"></span>
                    <span id="previewPeranBadge" class="badge badge-outline" style="font-size: 0.72rem; margin-bottom: 6px;"></span>
                    <h3 id="previewJudul" style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin: 6px 0 0 0; line-height: 1.3;"></h3>
                </div>
                <button type="button" id="btnClosePreviewModal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div style="display: flex; align-items: center; gap: 12px; font-size: 0.78rem; color: var(--text-muted); margin-bottom: 16px;">
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
                <button type="button" id="btnClosePreviewBtn" class="btn btn-outline" style="padding: 8px 18px; font-size: 0.85rem;">Tutup</button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/pengumuman.js') }}"></script>
    @endpush
@endsection

