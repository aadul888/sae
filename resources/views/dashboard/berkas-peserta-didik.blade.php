@extends('layouts.dashboard')

@section('title', 'Validasi Berkas Peserta Didik — SAE')
@section('dash_title', 'Validasi Berkas')

@section('content')
    @php
        $photoUrl = $studentPhoto ?? ($pd->foto_url ?? null);
    @endphp

    {{-- Switcher Siswa Khusus Administrator & Tim Kesiswaan --}}
    @if (in_array($userRole, ['admin', 'tendik', 'guru'], true) && !empty($allPdList) && $allPdList->isNotEmpty())
        <div class="card"
            style="margin-bottom: 20px; padding: 12px 18px; border-radius: 12px; background: rgba(99,102,241,0.06); border: 1px dashed rgba(99,102,241,0.3);">
            <form method="GET" action="{{ route('dashboard.berkas.index') }}"
                style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div
                    style="font-size: 0.82rem; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-user-shield"></i> Mode Pengelola: Pilih Siswa untuk Meninjau Berkas Digital
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <select name="peserta_didik_id" onchange="this.form.submit()" class="form-select"
                        style="font-size: 0.82rem; height: 36px; border-radius: 8px; min-width: 260px;">
                        @foreach ($allPdList as $item)
                            <option value="{{ $item->peserta_didik_id }}"
                                {{ $pd && $pd->peserta_didik_id === $item->peserta_didik_id ? 'selected' : '' }}>
                                {{ $item->nama }} ({{ $item->nama_rombel ?: 'Tanpa Rombel' }}) - NISN: {{ $item->nisn }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    @endif

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

    @if ($errors->any())
        <div class="alert alert-danger"
            style="padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;">
            <div style="font-weight: 700; margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-triangle-exclamation"></i> Gagal Mengunggah Berkas:
            </div>
            <ul style="margin: 0; padding-left: 20px; font-size: 0.85rem;">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($pd)
        <!-- 1. Header Banner Profil Siswa -->
        <div class="dash-banner"
            style="margin-bottom: 24px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.12) 0%, rgba(6, 182, 212, 0.08) 100%); border: 1px solid rgba(99, 102, 241, 0.25); display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; border-radius: 16px; padding: 20px 24px;">
            <div style="display: flex; align-items: center; gap: 18px; flex: 1; min-width: 0;">
                @if ($photoUrl)
                    <div
                        style="flex-shrink: 0; width: 78px; height: 98px; display: flex; align-items: center; justify-content: center; background: transparent; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.18);">
                        <img src="{{ $photoUrl }}" alt="{{ $pd->nama }}"
                            style="width: 100%; height: 100%; object-fit: cover;"
                            onerror="this.style.display='none'; this.parentElement.style.display='none';">
                    </div>
                @else
                    <div
                        style="flex-shrink: 0; width: 68px; height: 68px; border-radius: 16px; background: rgba(99,102,241,0.15); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                        <i class="fas fa-folder-open"></i>
                    </div>
                @endif

                <div style="flex: 1; min-width: 0;">
                    <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted); margin-bottom: 2px;">
                        Validasi Berkas Persyaratan &amp; Dokumen Siswa
                    </div>
                    <h2
                        style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin: 0 0 6px 0; line-height: 1.25;">
                        {{ $pd->nama }}
                    </h2>
                    <div
                        style="display: flex; align-items: center; gap: 12px; font-size: 0.8rem; color: var(--text-muted); flex-wrap: wrap; margin-bottom: 8px;">
                        <span><i class="fas fa-id-card text-primary me-1"></i> NISN:
                            <strong>{{ $pd->nisn ?: '-' }}</strong></span>
                        <span><i class="fas fa-fingerprint text-info me-1"></i> NIK:
                            <strong>{{ $pd->nik ?: '-' }}</strong></span>
                        <span><i class="fas fa-school text-warning me-1"></i> Rombel:
                            <strong>{{ $pd->nama_rombel ?: '-' }}</strong></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <div
                            style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(99,102,241,0.12); border: 1px solid rgba(99,102,241,0.25); border-radius: 20px; font-size: 0.74rem; font-weight: 700; color: var(--primary);">
                            <i class="fas fa-file-pdf"></i> Format Wajib: Dokumen PDF Tunggal
                        </div>
                        <div
                            style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25); border-radius: 20px; font-size: 0.74rem; font-weight: 700; color: #10b981;">
                            <i class="fas fa-shield-halved"></i> Validasi Resmi Tim Kesiswaan
                        </div>
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="{{ route('dashboard.identitas.index') }}" class="btn btn-outline"
                    style="padding: 8px 14px; font-size: 0.85rem; border-radius: 8px;">
                    <i class="fas fa-id-card-clip me-1"></i> Identitas Lengkap
                </a>
                @if (in_array($userRole, ['admin', 'tendik'], true))
                    <a href="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'berkas']) }}"
                        class="btn btn-primary" style="padding: 8px 14px; font-size: 0.85rem; border-radius: 8px;">
                        <i class="fas fa-clipboard-check me-1"></i> Pengelolaan Kesiswaan
                    </a>
                @endif
            </div>
        </div>

        <!-- 2. Ringkasan Status Validasi Berkas Siswa -->
        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 24px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                    <i class="fas fa-folder-tree"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['total'] }} <span
                            style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">Slot</span></div>
                    <div class="dash-stat-label">Total Berkas</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['valid'] }} <span
                            style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">Dokumen</span></div>
                    <div class="dash-stat-label">Valid / Sesuai</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: #ef4444;">
                    <i class="fas fa-circle-xmark"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['tidak_valid'] }} <span
                            style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">Dokumen</span></div>
                    <div class="dash-stat-label">Tidak Valid / Ditolak</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                    <i class="fas fa-clock-rotate-left"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['menunggu'] }} <span
                            style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">Dokumen</span></div>
                    <div class="dash-stat-label">Menunggu Verifikasi</div>
                </div>
            </div>
        </div>

        <!-- 3. Instruksi Ketentuan Berkas -->
        <div class="card"
            style="margin-bottom: 24px; padding: 16px 20px; border-radius: 12px; background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2);">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <i class="fas fa-circle-info text-primary" style="font-size: 1.25rem; margin-top: 2px;"></i>
                <div style="font-size: 0.84rem; color: var(--text-color); line-height: 1.5;">
                    <strong>Ketentuan Unggah Berkas:</strong>
                    Sistem hanya menerima <strong>1 jenis file saja yakni berupa file PDF (.pdf)</strong> dengan ukuran
                    maksimal <strong>5 MB</strong> per dokumen.
                    Pastikan hasil scan dokumen asli atau fotokopi legalisir jelas, tegak lurus, tidak buram, dan seluruh
                    tulisan terbaca sempurna.
                    Status hasil verifikasi pengelola hanya ada 2: <strong>Valid / Sesuai</strong> atau <strong>Tidak Valid
                        / Tidak Sesuai</strong>.
                </div>
            </div>
        </div>

        <!-- 4. Daftar Slot Berkas Dokumen Siswa -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 18px;">
            @foreach ($slots as $key => $slot)
                @php
                    $berkas = $berkasMap->get($key);
                    $status = $berkas ? $berkas->status : 'belum_unggah';
                @endphp

                <div class="card"
                    style="border-radius: 14px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">

                    <div>
                        <!-- Header Kartu Berkas -->
                        <div
                            style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div
                                    style="width: 40px; height: 40px; border-radius: 10px; background: rgba(99,102,241,0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0;">
                                    <i class="fas {{ $slot['icon'] }}"></i>
                                </div>
                                <div>
                                    <h3 style="font-size: 0.96rem; font-weight: 800; color: var(--text-color); margin: 0;">
                                        {{ $slot['label'] }}
                                    </h3>
                                    <span style="font-size: 0.72rem; color: var(--text-muted);">
                                        {{ $slot['wajib'] ? 'Dokumen Wajib' : 'Dokumen Opsional / Pendukung' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Badge Status Berkas -->
                            <div>
                                @if ($status === 'valid')
                                    <span class="badge badge-success" style="font-size: 0.76rem; padding: 4px 10px;">
                                        <i class="fas fa-circle-check me-1"></i> Valid / Sesuai
                                    </span>
                                @elseif ($status === 'tidak_valid')
                                    <span class="badge badge-danger" style="font-size: 0.76rem; padding: 4px 10px;">
                                        <i class="fas fa-circle-xmark me-1"></i> Tidak Valid / Tidak Sesuai
                                    </span>
                                @elseif ($status === 'menunggu')
                                    <span class="badge badge-warning" style="font-size: 0.76rem; padding: 4px 10px;">
                                        <i class="fas fa-clock-rotate-left me-1"></i> Menunggu Verifikasi
                                    </span>
                                @else
                                    <span class="badge badge-outline" style="font-size: 0.76rem; padding: 4px 10px;">
                                        <i class="fas fa-circle-minus me-1"></i> Belum Diunggah
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Deskripsi Dokumen -->
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 14px; line-height: 1.4;">
                            {{ $slot['deskripsi'] }}
                        </p>

                        <!-- Box Peringatan jika Ditolak / Tidak Valid -->
                        @if ($status === 'tidak_valid' && $berkas)
                            <div
                                style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 10px; padding: 12px 14px; margin-bottom: 16px;">
                                <div
                                    style="font-size: 0.78rem; font-weight: 800; color: #b91c1c; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-circle-exclamation"></i> Catatan Penolakan dari Pengelola Kesiswaan:
                                </div>
                                <div
                                    style="font-size: 0.82rem; color: var(--text-color); margin-bottom: 6px; line-height: 1.45;">
                                    {{ $berkas->catatan_penolakan ?: ($berkas->rekomendasi_penolakan ?: 'Dokumen tidak memenuhi kriteria verifikasi. Silakan periksa kembali dan unggah ulang berkas PDF yang benar.') }}
                                </div>
                                <div style="font-size: 0.7rem; color: var(--text-muted);">
                                    Diverifikasi oleh: <strong>{{ $berkas->verified_by ?: 'Tim Kesiswaan' }}</strong>
                                    @if ($berkas->verified_at)
                                        pada {{ $berkas->verified_at->translatedFormat('d F Y, H:i') }} WIB
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Informasi File Terunggah -->
                        @if ($berkas)
                            <div
                                style="background: var(--bg-hover); border-radius: 8px; padding: 10px 12px; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                                    <i class="fas fa-file-pdf text-danger" style="font-size: 1.3rem;"></i>
                                    <div style="min-width: 0;">
                                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                            title="{{ $berkas->file_name }}">
                                            {{ $berkas->file_name }}
                                        </div>
                                        <div style="font-size: 0.7rem; color: var(--text-muted);">
                                            {{ $berkas->formatted_file_size }} &bull; Diunggah
                                            {{ $berkas->created_at ? $berkas->created_at->diffForHumans() : '-' }}
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline btn-preview-pdf-inline"
                                    data-url="{{ route('dashboard.berkas.preview', $berkas->id) }}"
                                    data-title="{{ $slot['label'] }} - {{ $pd->nama }}"
                                    style="padding: 5px 12px; font-size: 0.76rem; border-radius: 6px; flex-shrink: 0; cursor: pointer;"
                                    title="Lihat Pratinjau Dokumen PDF">
                                    <i class="fas fa-eye me-1"></i> Lihat PDF
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- Area Formulir Unggah / Unggah Ulang Berkas -->
                    <div style="border-top: 1px dashed var(--border-color); padding-top: 14px; margin-top: 8px;">
                        <form action="{{ route('dashboard.berkas.upload') }}" method="POST"
                            enctype="multipart/form-data" class="form-upload-berkas">
                            @csrf
                            <input type="hidden" name="peserta_didik_id" value="{{ $pd->peserta_didik_id }}">
                            <input type="hidden" name="jenis_berkas" value="{{ $key }}">

                            <div style="margin-bottom: 8px;">
                                <label
                                    style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 4px;">
                                    {{ $berkas ? ($status === 'tidak_valid' ? 'Unggah Ulang File Perbaikan (Hanya PDF):' : 'Ganti Berkas PDF:') : 'Pilih File Dokumen (Hanya PDF):' }}
                                </label>
                                <input type="file" name="file_berkas" accept=".pdf,application/pdf" required
                                    class="form-control file-input-pdf"
                                    style="font-size: 0.78rem; padding: 6px 10px; border-radius: 8px;">
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                                <span style="font-size: 0.7rem; color: var(--text-muted);">
                                    <i class="fas fa-file-shield text-primary me-1"></i>Hanya .pdf (Maks. 5MB)
                                </span>
                                <button type="submit" class="btn btn-primary"
                                    style="padding: 6px 14px; font-size: 0.8rem; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-cloud-arrow-up"></i>
                                    {{ $berkas ? 'Perbarui PDF' : 'Unggah PDF' }}
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            @endforeach
        </div>
    @endif

    <!-- MODAL PRATINJAU DOKUMEN PDF (TANPA MEMBUKA TAB BARU) -->
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
        src="{{ asset('js/berkas-peserta-didik.js') }}?v={{ file_exists(public_path('js/berkas-peserta-didik.js')) ? filemtime(public_path('js/berkas-peserta-didik.js')) : time() }}">
    </script>
@endpush
