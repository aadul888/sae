@extends('layouts.dashboard')

@section('title', 'Edit Formulir — SAE')
@section('dash_title', 'Formulir & Survei')

@section('content')
    <!-- Header Breadcrumb -->
    <div class="dash-banner" style="margin-bottom: 20px;">
        <div>
            <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 4px;">
                <a href="{{ route('dashboard.formulir.index') }}"
                    style="color: var(--primary); text-decoration: none;">Formulir &amp; Survei</a>
                <i class="fas fa-chevron-right mx-1" style="font-size: 0.7rem;"></i> Edit Formulir
            </div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin: 0;">
                <i class="fas fa-pen-to-square text-primary me-2"></i> Edit: {{ $formulir->judul }}
            </h2>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 8px;">
            <a href="{{ $formulir->public_url }}" target="_blank" class="btn btn-outline"
                style="padding: 8px 14px; font-size: 0.85rem;">
                <i class="fas fa-external-link-alt me-1"></i> Buka Tautan
            </a>
            <a href="{{ route('dashboard.formulir.index') }}" class="btn btn-outline"
                style="padding: 8px 16px; font-size: 0.85rem;">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    @if (session('error'))
        <div class="alert alert-danger"
            style="padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;">
            {{ session('error') }}
        </div>
    @endif

    <form id="builderForm" data-mode="edit" action="{{ route('dashboard.formulir.update', $formulir->id) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="skema_json" id="skemaJsonInput">

        <div class="builder-layout">
            <!-- Kolom Kiri: Konfigurasi & Integrasi SAE -->
            <div class="panel-box">
                <h3 class="panel-title">
                    <i class="fas fa-sliders text-primary"></i> Pengaturan Formulir
                </h3>

                <div class="mb-3">
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Judul Formulir <span
                            class="text-danger">*</span></label>
                    <input type="text" name="judul" class="form-control" placeholder="Judul formulir..." required
                        value="{{ old('judul', $formulir->judul) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Slug Tautan</label>
                    <div class="input-group">
                        <span class="input-group-text" style="font-size: 0.8rem; background: #f8fafc;">/f/</span>
                        <input type="text" name="slug" class="form-control"
                            value="{{ old('slug', $formulir->slug) }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Deskripsi / Petunjuk Pengisian</label>
                    <textarea name="deskripsi" class="form-control" rows="3"
                        placeholder="Jelaskan tujuan dan instruksi pengisian formulir...">{{ old('deskripsi', $formulir->deskripsi) }}</textarea>
                </div>

                <hr style="border-top: 1px solid var(--border-color, #e2e8f0); margin: 18px 0;">

                <!-- Akses & Target SAE -->
                <h4 style="font-size: 0.9rem; font-weight: 700; color: var(--text-color); margin-bottom: 12px;">
                    <i class="fas fa-users-gear text-primary me-1"></i> Target Responden SAE
                </h4>

                <div class="mb-3">
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Target Peran</label>
                    <select name="target_peran" id="targetPeranSelect" class="form-select" onchange="toggleRombelTarget()">
                        <option value="semua" {{ $formulir->target_peran === 'semua' ? 'selected' : '' }}>Semua Pengguna SAE (Login)</option>
                        <option value="peserta_didik" {{ $formulir->target_peran === 'peserta_didik' ? 'selected' : '' }}>Khusus Peserta Didik</option>
                        <option value="guru" {{ $formulir->target_peran === 'guru' ? 'selected' : '' }}>Khusus Guru / Pendidik</option>
                        <option value="tendik" {{ $formulir->target_peran === 'tendik' ? 'selected' : '' }}>Khusus Tenaga Kependidikan</option>
                        <option value="publik" {{ $formulir->target_peran === 'publik' ? 'selected' : '' }}>Publik Terbuka (Siapa saja tanpa login)</option>
                    </select>
                    <div id="targetRoleHint" class="form-text mt-1" style="font-size: 0.76rem;"></div>
                </div>

                <div class="mb-3" id="targetRombelBox"
                    style="display: {{ $formulir->target_peran === 'peserta_didik' ? 'block' : 'none' }};">
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Batasi Khusus Rombel (Kelas)</label>
                    <select name="target_rombel_id" class="form-select">
                        <option value="">-- Semua Rombel / Kelas --</option>
                        @foreach ($rombels as $r)
                            <option value="{{ $r->rombongan_belajar_id }}"
                                {{ $formulir->target_rombel_id === $r->rombongan_belajar_id ? 'selected' : '' }}>
                                {{ $r->nama }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted" style="font-size: 0.75rem;">Hanya peserta didik di kelas tersebut yang diizinkan mengisi.</small>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="limit_one_response" id="limitOne" value="1"
                        {{ $formulir->limit_one_response ? 'checked' : '' }}>
                    <label class="form-check-label" for="limitOne" style="font-size: 0.85rem; font-weight: 600;">
                        Batasi 1 Kali Pengisian per Akun/Peserta Didik
                    </label>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_public" id="isPublic" value="1"
                        {{ $formulir->is_public ? 'checked' : '' }}>
                    <label class="form-check-label" for="isPublic" style="font-size: 0.85rem;">
                        Izinkan akses publik langsung via tautan
                    </label>
                </div>

                <hr style="border-top: 1px solid var(--border-color, #e2e8f0); margin: 18px 0;">

                <!-- Waktu & Status -->
                <div class="mb-3">
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Batas Waktu Pengisian (Opsional)</label>
                    <input type="datetime-local" name="tanggal_selesai" class="form-control" style="font-size: 0.85rem;"
                        value="{{ $formulir->tanggal_selesai ? $formulir->tanggal_selesai->format('Y-m-d\TH:i') : '' }}">
                </div>
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1"
                        {{ $formulir->is_active ? 'checked' : '' }}>
                    <label class="form-check-label" for="isActive" style="font-size: 0.85rem; font-weight: 600;">
                        Status Formulir Aktif
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-100"
                    style="padding: 10px; font-weight: 700; border-radius: 10px;">
                    <i class="fas fa-save me-1"></i> Simpan Perubahan Formulir
                </button>
            </div>

            <!-- Kolom Kanan: Interactive Field Builder -->
            <div class="panel-box">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                    <h3 class="panel-title" style="margin: 0;">
                        <i class="fas fa-list-check text-primary"></i> Susunan Pertanyaan Formulir
                    </h3>
                    <span id="qCountBadge"
                        style="font-size: 0.78rem; font-weight: 700; background: #e0f2fe; color: #0284c7; padding: 3px 10px; border-radius: 20px;">
                        0 Pertanyaan
                    </span>
                </div>

                <!-- Toolbar Tambah Field Cepat -->
                <div class="preset-button-bar">
                    <span
                        style="font-size: 0.75rem; font-weight: 700; color: #64748b; width: 100%; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-plus-circle text-primary"></i> Tambah Elemen Pertanyaan:
                    </span>
                    <button type="button" class="btn-preset-field" onclick="addField('text', 'Teks Singkat')">
                        <i class="fas fa-font"></i> Teks Singkat
                    </button>
                    <button type="button" class="btn-preset-field"
                        onclick="addField('textarea', 'Paragraf / Jawaban Panjang')">
                        <i class="fas fa-align-left"></i> Paragraf
                    </button>
                    <button type="button" class="btn-preset-field" onclick="addField('radio', 'Pilihan Ganda')">
                        <i class="fas fa-circle-dot"></i> Pilihan Ganda
                    </button>
                    <button type="button" class="btn-preset-field" onclick="addField('checkbox', 'Kotak Centang')">
                        <i class="fas fa-square-check"></i> Centang
                    </button>
                    <button type="button" class="btn-preset-field" onclick="addField('select', 'Menu Dropdown')">
                        <i class="fas fa-chevron-down"></i> Dropdown
                    </button>
                    <button type="button" class="btn-preset-field" onclick="addField('rating', 'Penilaian Rating')">
                        <i class="fas fa-star text-warning"></i> Rating
                    </button>
                    <button type="button" class="btn-preset-field" onclick="addField('number', 'Angka')">
                        <i class="fas fa-hashtag"></i> Angka
                    </button>
                    <button type="button" class="btn-preset-field" onclick="addField('date', 'Tanggal')">
                        <i class="fas fa-calendar"></i> Tanggal
                    </button>
                    <button type="button" class="btn-preset-field"
                        onclick="addField('file', 'Unggah Berkas / Dokumen')">
                        <i class="fas fa-paperclip"></i> Unggah File
                    </button>
                    <span style="width: 100%; height: 1px; background: var(--border-color); margin: 4px 0;"></span>

                    <span style="font-size: 0.75rem; font-weight: 700; color: #f59e0b; width: 100%; margin-bottom: 2px;">
                        <i class="fas fa-id-badge text-warning me-1"></i> Data Otomatis SAE (Terkunci Sesuai Akun):
                    </span>
                    <button type="button" class="btn-preset-field sae-field"
                        onclick="addField('sae_nama', 'Nama Lengkap Peserta Didik')">
                        <i class="fas fa-user-check text-warning"></i> Nama Peserta Didik (Auto)
                    </button>
                    <button type="button" class="btn-preset-field sae-field"
                        onclick="addField('sae_nisn', 'NISN')">
                        <i class="fas fa-fingerprint text-warning"></i> NISN (Auto)
                    </button>
                    <button type="button" class="btn-preset-field sae-field"
                        onclick="addField('sae_rombel', 'Kelas / Rombel')">
                        <i class="fas fa-school text-warning"></i> Rombel (Auto)
                    </button>
                </div>

                <!-- Container Pertanyaan Interaktif -->
                <div id="questionsContainer"></div>

                <div id="emptyQuestionsPlaceholder"
                    style="text-align: center; padding: 40px 20px; border: 2px dashed var(--border-color); border-radius: 12px; color: var(--text-muted); display: none;">
                    <i class="fas fa-arrow-up" style="font-size: 1.6rem; color: var(--text-muted); margin-bottom: 10px;"></i>
                    <p style="margin: 0; font-size: 0.88rem; font-weight: 600;">Belum ada pertanyaan.</p>
                </div>
            </div>
        </div>
    </form>

    <!-- Data Skema Awal Formulir -->
    <script type="application/json" id="initialSchemaData">
        {!! json_encode($formulir->skema ?? [], JSON_UNESCAPED_UNICODE) !!}
    </script>

    @push('scripts')
        <script src="{{ asset('js/formulir-builder.js') }}"></script>
    @endpush
@endsection
