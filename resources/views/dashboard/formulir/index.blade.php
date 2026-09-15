@extends('layouts.dashboard')

@section('title', 'Formulir & Survei Digital — SAE')
@section('dash_title', 'Formulir & Survei')

@section('content')
    <!-- Banner Header -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-clipboard-list text-primary me-2"></i> Formulir &amp; Survei Digital
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
                Buat kuesioner, angket rombel, pendaftaran ekskul, dan survei publik dengan integrasi otomatis data siswa
                &amp; guru SAE.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
            @if ($canManage)
                <a href="{{ route('dashboard.formulir.create') }}" class="btn btn-primary"
                    style="padding: 9px 18px; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-plus"></i> Buat Formulir Baru
                </a>
            @endif
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    @if (session('success'))
        <div class="alert alert-success"
            style="padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-circle-check" style="font-size: 1.1rem;"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger"
            style="padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-circle-exclamation" style="font-size: 1.1rem;"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Quick Stat Grid (Khusus Pengelola Formulir) -->
    @if ($canManage)
        <div class="form-stat-grid">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(2, 132, 199, 0.12); color: #0284c7;">
                    <i class="fas fa-file-lines"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $formulirs->total() }}</div>
                    <div class="dash-stat-label">Total Formulir</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                    <i class="fas fa-toggle-on"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $formulirs->where('is_active', true)->count() }}</div>
                    <div class="dash-stat-label">Sedang Aktif</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(147, 51, 234, 0.12); color: #9333ea;">
                    <i class="fas fa-inbox"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $formulirs->sum('respon_count') }}</div>
                    <div class="dash-stat-label">Tanggapan Masuk</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $formulirs->where('is_public', true)->count() }}</div>
                    <div class="dash-stat-label">Formulir Publik</div>
                </div>
            </div>
        </div>
    @endif

    <!-- Filter & Pencarian Toolbar -->
    <div class="toolbar-row"
        style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 24px; flex-wrap: wrap;">
        <form action="{{ route('dashboard.formulir.index') }}" method="GET"
            style="display: flex; gap: 8px; flex-grow: 1; max-width: 520px;">
            <div style="position: relative; width: 100%;">
                <input type="text" name="q" value="{{ request('q') }}"
                    placeholder="Cari judul atau topik formulir..." class="form-control"
                    style="padding-left: 36px; height: 40px; border-radius: 10px; font-size: 0.88rem; width: 100%;">
                <i class="fas fa-search"
                    style="position: absolute; left: 12px; top: 12px; color: var(--text-muted, #94a3b8);"></i>
            </div>
            <button type="submit" class="btn btn-outline"
                style="height: 40px; border-radius: 10px; padding: 0 16px;">Cari</button>
        </form>

        <div style="display: flex; gap: 8px; align-items: center;">
            <select class="form-select" onchange="window.location.href=this.value"
                style="height: 40px; border-radius: 10px; font-size: 0.85rem;">
                <option value="{{ request()->fullUrlWithQuery(['status' => '']) }}">Semua Status</option>
                <option value="{{ request()->fullUrlWithQuery(['status' => 'active']) }}"
                    {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="{{ request()->fullUrlWithQuery(['status' => 'inactive']) }}"
                    {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                <option value="{{ request()->fullUrlWithQuery(['status' => 'expired']) }}"
                    {{ request('status') === 'expired' ? 'selected' : '' }}>Telah Ditutup</option>
            </select>
        </div>
    </div>

    <!-- Grid Kartu Formulir -->
    @if ($formulirs->count() > 0)
        <div class="form-card-grid">
            @foreach ($formulirs as $f)
                @php
                    $hasResponded = in_array($f->id, $respondedFormIds);
                    $isScheduleOpen = $f->isScheduleOpen();
                @endphp
                <div class="form-item-card">
                    <div>
                        <!-- Badge Bar -->
                        <div class="form-badge-bar">
                            @if ($f->is_active && $isScheduleOpen)
                                <span class="badge-chip badge-active"><i class="fas fa-circle-dot"></i> Buka</span>
                            @elseif (!$f->is_active)
                                <span class="badge-chip badge-inactive"><i class="fas fa-ban"></i> Nonaktif</span>
                            @else
                                <span class="badge-chip badge-inactive"><i class="fas fa-clock"></i> Tutup</span>
                            @endif

                            @if ($f->is_public)
                                <span class="badge-chip badge-public"><i class="fas fa-globe"></i> Publik</span>
                            @else
                                <span class="badge-chip badge-role-semua"><i class="fas fa-lock"></i> Akun SAE</span>
                            @endif

                            @if ($f->target_peran === 'peserta_didik')
                                <span class="badge-chip badge-role-siswa"><i class="fas fa-graduation-cap"></i> Khusus
                                    Siswa</span>
                            @elseif ($f->target_peran === 'guru')
                                <span class="badge-chip badge-role-guru"><i class="fas fa-chalkboard-user"></i> Khusus
                                    Guru</span>
                            @endif
                        </div>

                        <!-- Judul & Deskripsi -->
                        <h3 class="form-card-title">{{ $f->judul }}</h3>
                        <p class="form-card-desc">{{ $f->deskripsi ?: 'Tidak ada deskripsi tambahan.' }}</p>

                        <!-- Status Pengisian Khusus Siswa / Responden -->
                        @if ($role === 'peserta_didik')
                            @if ($hasResponded)
                                <div class="form-student-status student-status-done">
                                    <span><i class="fas fa-circle-check me-1"></i> Anda Sudah Mengisi</span>
                                    <i class="fas fa-check-double"></i>
                                </div>
                            @else
                                <div class="form-student-status student-status-pending">
                                    <span><i class="fas fa-hourglass-half me-1"></i> Belum Anda Isi</span>
                                    <span style="font-size: 0.75rem;">Wajib/Disarankan</span>
                                </div>
                            @endif
                        @endif

                        <!-- Metadata -->
                        <div class="form-card-meta">
                            <div class="form-meta-row">
                                <i class="fas fa-layer-group text-primary" style="width: 16px;"></i>
                                <span>{{ count($f->skema ?? []) }} Pertanyaan disusun</span>
                            </div>
                            <div class="form-meta-row">
                                <i class="fas fa-calendar-alt text-muted" style="width: 16px;"></i>
                                <span>
                                    @if ($f->tanggal_selesai)
                                        Batas: {{ $f->tanggal_selesai->translatedFormat('d M Y, H:i') }}
                                    @else
                                        Tanpa batas waktu
                                    @endif
                                </span>
                            </div>
                            @if ($canManage)
                                <div class="form-meta-row">
                                    <i class="fas fa-user-check text-muted" style="width: 16px;"></i>
                                    <span>{{ $f->respon_count }} Tanggapan terekam</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Footer Aksi -->
                    <div class="form-card-actions">
                        <!-- Tombol Respon / Isi -->
                        @if ($role === 'peserta_didik')
                            @if ($hasResponded)
                                <a href="{{ $f->public_url }}" target="_blank" class="btn btn-outline"
                                    style="font-size: 0.82rem; padding: 7px 14px; border-radius: 8px;">
                                    <i class="fas fa-receipt me-1"></i> Bukti Pengisian
                                </a>
                            @else
                                <a href="{{ $f->public_url }}" target="_blank" class="btn btn-primary"
                                    style="font-size: 0.82rem; padding: 7px 16px; border-radius: 8px; font-weight: 600;">
                                    <i class="fas fa-pen-to-square me-1"></i> Isi Sekarang
                                </a>
                            @endif
                        @else
                            <a href="{{ route('dashboard.formulir.responses', $f->id) }}" class="btn btn-outline"
                                style="font-size: 0.82rem; padding: 7px 14px; border-radius: 8px; font-weight: 600;">
                                <i class="fas fa-chart-pie me-1"></i> {{ $f->respon_count }} Tanggapan
                            </a>
                        @endif

                        <!-- Tombol Aksi Tambahan untuk Admin / Guru -->
                        @if ($canManage)
                            <div style="display: flex; gap: 6px;">
                                <button type="button" class="btn-icon-soft" title="Salin Tautan Formulir"
                                    onclick="copyFormLink('{{ $f->public_url }}')">
                                    <i class="fas fa-link"></i>
                                </button>
                                <a href="{{ $f->public_url }}" target="_blank" class="btn-icon-soft"
                                    title="Buka Formulir">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                                <a href="{{ route('dashboard.formulir.edit', $f->id) }}" class="btn-icon-soft"
                                    title="Edit Skema Formulir">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form action="{{ route('dashboard.formulir.destroy', $f->id) }}" method="POST"
                                    data-confirm="delete" data-name="{{ $f->judul }}" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon-soft danger" title="Hapus Formulir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top: 24px;">
            {{ $formulirs->links() }}
        </div>
    @else
        <div
            style="text-align: center; padding: 60px 20px; background: var(--card-bg); border-radius: 14px; border: 1px dashed var(--border-color);">
            <div
                style="width: 70px; height: 70px; border-radius: 50%; background: var(--bg-hover); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 16px;">
                <i class="fas fa-clipboard-question"></i>
            </div>
            <h4 style="font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Belum Ada Formulir</h4>
            <p style="color: var(--text-muted); font-size: 0.88rem; max-width: 440px; margin: 0 auto 20px;">
                Belum ada formulir atau survei yang dibuat. Buat formulir baru untuk mengumpulkan data dari siswa, guru,
                atau masyarakat umum.
            </p>
            @if ($canManage)
                <a href="{{ route('dashboard.formulir.create') }}" class="btn btn-primary"
                    style="padding: 9px 20px; font-size: 0.88rem;">
                    <i class="fas fa-plus me-1"></i> Buat Formulir Sekarang
                </a>
            @endif
        </div>
    @endif

    <!-- Toast Pop-up Copy Link Fallback -->
    <div id="copyToast" class="copy-toast">
        <i class="fas fa-circle-check" style="color: var(--primary);"></i> Tautan formulir berhasil disalin ke clipboard!
    </div>

    @push('scripts')
        <script src="{{ asset('js/formulir.js') }}?v={{ file_exists(public_path('js/formulir.js')) ? filemtime(public_path('js/formulir.js')) : time() }}"></script>
    @endpush
@endsection
