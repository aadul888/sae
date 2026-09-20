<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $formulir->judul }} — SAE Formulir</title>

    <!-- Local Fonts & FontAwesome -->
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

    <link rel="stylesheet" href="{{ asset('css/formulir-public.css') }}">
    <style>
        :root {
            --primary: {{ $formulir->pengaturan['theme_color'] ?? '#0284c7' }};
        }
    </style>
</head>

<body>

    <div class="form-container">
        @php
            $dashLogoDark = asset('img/logo-dark.png') . '?v=' . (@filemtime(public_path('img/logo-dark.png')) ?: '1');
            $dashLogoIcon = asset('img/logo-icon.png') . '?v=' . (@filemtime(public_path('img/logo-icon.png')) ?: '1');
        @endphp

        <!-- Header Sekolah & Brand Resmi SAE -->
        <div class="school-header">
            <div class="school-brand">
                @if ($sekolahMeta && $sekolahMeta->logo_url)
                    <img src="{{ $sekolahMeta->logo_url }}" alt="Logo Sekolah" class="school-logo">
                @elseif (!empty($sekolah->logo_url))
                    <img src="{{ $sekolah->logo_url }}" alt="Logo Sekolah" class="school-logo">
                @else
                    <img src="{{ $dashLogoIcon }}" alt="Logo SAE" class="school-logo">
                @endif
                <div class="school-info">
                    <div class="school-title">{{ $sekolah->nama ?? 'Sistem Aplikasi Edukasi' }}</div>
                    <div class="school-sub">NPSN: {{ $sekolah->npsn ?? '40306164' }}</div>
                </div>
            </div>
            <a href="{{ url('/') }}" class="sae-brand-badge" title="SAE - Sistem Aplikasi Edukasi">
                <img src="{{ $dashLogoDark }}" alt="SAE Logo" class="sae-header-logo" onerror="this.onerror=null; this.src='{{ $dashLogoIcon }}';">
            </a>
        </div>

        <!-- Banner Identitas Pengguna Terautentikasi -->
        @if ($user)
            @php
                $userName = $studentInfo->nama ?? (is_array($user) ? ($user['nama'] ?? 'Pengguna SAE') : ($user->nama ?? 'Pengguna SAE'));
                $userRoleStr = is_array($user) ? ($user['role'] ?? 'Pengguna') : ($user->role ?? 'Pengguna');
            @endphp
            <div class="auth-banner">
                <div class="auth-banner-user">
                    <i class="fas fa-circle-check"></i>
                    <div>
                        <div><strong>{{ $userName }}</strong></div>
                        <div style="font-size: 0.76rem; color: #15803d;">
                            @if ($studentInfo)
                                NISN: {{ $studentInfo->nisn }} • Kelas: {{ $studentInfo->nama_rombel }}
                            @else
                                Peran: {{ ucfirst(str_replace('_', ' ', $userRoleStr)) }}
                            @endif
                        </div>
                    </div>
                </div>
                <span class="badge-verified"><i class="fas fa-shield-check me-1"></i> Data Terverifikasi</span>
            </div>
        @elseif ($formulir->target_peran === 'publik')
            <!-- Opsi Login untuk Responden Publik -->
            <div class="guest-banner">
                <div class="guest-banner-info">
                    <i class="fas fa-user-clock text-primary" style="font-size: 1.25rem;"></i>
                    <div>
                        <div style="font-weight: 700; color: #1e293b; font-size: 0.86rem;">Mengisi sebagai Responden Publik</div>
                        <div style="font-size: 0.76rem; color: #64748b;">Punya akun SAE (Peserta Didik/Guru)? Masuk agar identitas terisi otomatis.</div>
                    </div>
                </div>
                <a href="{{ route('login') }}" class="btn-login-option">
                    <i class="fas fa-right-to-bracket me-1"></i> Masuk Akun SAE
                </a>
            </div>
        @endif

        <!-- Error Alert -->
        @if (session('error'))
            <div
                style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-size: 0.88rem;">
                <i class="fas fa-circle-exclamation me-1"></i> {{ session('error') }}
            </div>
        @endif

        <!-- Form Utama -->
        <div class="main-card">
            <div class="card-top-accent"></div>
            <div class="card-header-content">
                <h1 class="form-title">{{ $formulir->judul }}</h1>
                @if ($formulir->deskripsi)
                    <p class="form-description">{{ $formulir->deskripsi }}</p>
                @endif
            </div>

            <form action="{{ route('formulir.submit', $formulir->slug) }}" method="POST" enctype="multipart/form-data"
                id="publicForm">
                @csrf

                <div class="form-body">
                    @foreach ($formulir->skema ?? [] as $f)
                        @php
                            $fieldId = $f['id'] ?? '';
                            $type = $f['type'] ?? 'text';
                            $label = $f['label'] ?? '';
                            $required = !empty($f['required']);
                            $desc = $f['description'] ?? '';
                            $placeholder = $f['placeholder'] ?? '';
                            $options = $f['options'] ?? [];
                        @endphp

                        <div class="form-group">
                            <label class="form-label" for="{{ $fieldId }}">
                                {{ $label }}
                                @if ($required)
                                    <span class="req">*</span>
                                @endif
                            </label>

                            @if ($desc)
                                <div class="form-help">{{ $desc }}</div>
                            @endif

                            <!-- Tipe: SAE Auto Fields -->
                            @if ($type === 'sae_nama')
                                @if ($user)
                                    <div class="field-locked-group">
                                        <input type="text" name="{{ $fieldId }}" id="{{ $fieldId }}" class="form-control field-locked"
                                            value="{{ $studentInfo->nama ?? (is_array($user) ? ($user['nama'] ?? '') : ($user->nama ?? '')) }}" readonly>
                                        <span class="field-badge-verified"><i class="fas fa-check-circle"></i> Terverifikasi Akun</span>
                                    </div>
                                @else
                                    <input type="text" name="{{ $fieldId }}" id="{{ $fieldId }}" class="form-control"
                                        placeholder="{{ $placeholder ?: 'Ketik nama lengkap Anda...' }}"
                                        {{ $required ? 'required' : '' }} value="{{ old($fieldId) }}">
                                    <div class="form-text-hint"><i class="fas fa-pen-nib me-1"></i> Responden Publik: Masukkan nama lengkap Anda.</div>
                                @endif

                            @elseif ($type === 'sae_nisn')
                                @if ($user)
                                    <div class="field-locked-group">
                                        <input type="text" name="{{ $fieldId }}" id="{{ $fieldId }}" class="form-control field-locked"
                                            value="{{ $studentInfo->nisn ?? (is_array($user) ? ($user['username'] ?? '') : ($user->username ?? '')) }}" readonly>
                                        <span class="field-badge-verified"><i class="fas fa-check-circle"></i> Terverifikasi Akun</span>
                                    </div>
                                @else
                                    <input type="text" name="{{ $fieldId }}" id="{{ $fieldId }}" class="form-control"
                                        placeholder="{{ $placeholder ?: 'Ketik NISN / NIK / No. Identitas...' }}"
                                        {{ $required ? 'required' : '' }} value="{{ old($fieldId) }}">
                                    <div class="form-text-hint"><i class="fas fa-id-card me-1"></i> Nomor induk peserta didik, NIK, atau nomor identitas responden.</div>
                                @endif

                            @elseif ($type === 'sae_rombel')
                                @if ($user)
                                    <div class="field-locked-group">
                                        <input type="text" name="{{ $fieldId }}" id="{{ $fieldId }}" class="form-control field-locked"
                                            value="{{ $studentInfo->nama_rombel ?? (is_array($user) ? ($user['kelas'] ?? '') : '') }}" readonly>
                                        <span class="field-badge-verified"><i class="fas fa-check-circle"></i> Terverifikasi Akun</span>
                                    </div>
                                @else
                                    <input type="text" name="{{ $fieldId }}" id="{{ $fieldId }}" class="form-control"
                                        placeholder="{{ $placeholder ?: 'Contoh: XII TKJ 1, Guru, atau Umum...' }}"
                                        {{ $required ? 'required' : '' }} value="{{ old($fieldId) }}">
                                    <div class="form-text-hint"><i class="fas fa-users-rectangle me-1"></i> Rombel/kelas, unit kerja, atau asal instansi.</div>
                                @endif

                                <!-- Tipe: Teks Singkat -->
                            @elseif ($type === 'text')
                                <input type="text" name="{{ $fieldId }}" id="{{ $fieldId }}"
                                    class="form-control" placeholder="{{ $placeholder }}"
                                    {{ $required ? 'required' : '' }} value="{{ old($fieldId) }}">

                                <!-- Tipe: Paragraf / Textarea -->
                            @elseif ($type === 'textarea')
                                <textarea name="{{ $fieldId }}" id="{{ $fieldId }}" class="form-control" rows="4"
                                    placeholder="{{ $placeholder }}" {{ $required ? 'required' : '' }}>{{ old($fieldId) }}</textarea>

                                <!-- Tipe: Angka -->
                            @elseif ($type === 'number')
                                <input type="number" name="{{ $fieldId }}" id="{{ $fieldId }}"
                                    class="form-control" placeholder="{{ $placeholder }}"
                                    {{ $required ? 'required' : '' }} value="{{ old($fieldId) }}">

                                <!-- Tipe: Tanggal -->
                            @elseif ($type === 'date')
                                <input type="date" name="{{ $fieldId }}" id="{{ $fieldId }}"
                                    class="form-control" {{ $required ? 'required' : '' }}
                                    value="{{ old($fieldId) }}">

                                <!-- Tipe: Dropdown Select -->
                            @elseif ($type === 'select')
                                <select name="{{ $fieldId }}" id="{{ $fieldId }}" class="form-control"
                                    {{ $required ? 'required' : '' }}>
                                    <option value="">{{ $placeholder ?: '-- Pilih salah satu --' }}</option>
                                    @foreach ($options as $opt)
                                        <option value="{{ $opt }}"
                                            {{ old($fieldId) === $opt ? 'selected' : '' }}>{{ $opt }}
                                        </option>
                                    @endforeach
                                </select>

                                <!-- Tipe: Pilihan Ganda (Radio) -->
                            @elseif ($type === 'radio')
                                <div class="choice-group">
                                    @foreach ($options as $optIdx => $opt)
                                        <label class="choice-item">
                                            <input type="radio" name="{{ $fieldId }}"
                                                value="{{ $opt }}"
                                                {{ $required && $optIdx === 0 ? 'required' : '' }}
                                                {{ old($fieldId) === $opt ? 'checked' : '' }}>
                                            <span class="choice-label">{{ $opt }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                <!-- Tipe: Kotak Centang (Checkbox) -->
                            @elseif ($type === 'checkbox')
                                <div class="choice-group">
                                    @foreach ($options as $opt)
                                        <label class="choice-item">
                                            <input type="checkbox" name="{{ $fieldId }}[]"
                                                value="{{ $opt }}">
                                            <span class="choice-label">{{ $opt }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                <!-- Tipe: Penilaian / Rating Bintang -->
                            @elseif ($type === 'rating')
                                <div class="rating-group">
                                    @foreach ($options as $rVal)
                                        <label class="rating-pill"
                                            onclick="selectRating(this, '{{ $fieldId }}')">
                                            <input type="radio" name="{{ $fieldId }}"
                                                value="{{ $rVal }}" {{ $required ? 'required' : '' }}>
                                            <span>{{ $rVal }} <i class="fas fa-star"
                                                    style="font-size: 0.75rem;"></i></span>
                                        </label>
                                    @endforeach
                                </div>

                                <!-- Tipe: File Upload -->
                            @elseif ($type === 'file')
                                <div class="file-dropzone"
                                    onclick="document.getElementById('{{ $fieldId }}').click()">
                                    <i class="fas fa-cloud-arrow-up"></i>
                                    <div style="font-size: 0.88rem; font-weight: 600; color: #334155;">Klik untuk
                                        memilih berkas</div>
                                    <div style="font-size: 0.75rem; color: #94a3b8;">Format: JPG, PNG, PDF, DOCX
                                        (Maksimal 5 MB)</div>
                                    <input type="file" name="{{ $fieldId }}" id="{{ $fieldId }}"
                                        style="display: none;"
                                        onchange="previewFile(this, '{{ $fieldId }}_preview')"
                                        {{ $required ? 'required' : '' }}>
                                    <div id="{{ $fieldId }}_preview" class="file-name-preview"></div>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <!-- Tombol Submit -->
                    <div style="margin-top: 10px;">
                        <button type="submit" id="btnSubmitForm" class="btn-submit">
                            <span>{{ $formulir->pengaturan['button_text'] ?? 'Kirim Formulir' }}</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Footer Branding -->
        <div class="footer-branding">
            Didukung oleh <strong>SAE Digital Forms</strong> &bull; Sistem Aplikasi Edukasi Terintegrasi
        </div>
    </div>

    <script src="{{ asset('js/formulir-public.js') }}"></script>
</body>

</html>
