@extends('layouts.dashboard')

@section('title', 'Profil & Keamanan Akun — Sistem Aplikasi Edukasi (SAE)')
@section('dash_title', 'Profil Pengguna')

@section('content')
<div class="profile-container">
    <!-- Welcome Profile Banner -->
    <div class="profile-banner-card">
        <div class="profile-banner-avatar-wrap">
            @if (!empty($profileDetails['foto_url']))
                <img src="{{ $profileDetails['foto_url'] }}" alt="{{ $user->name }}" class="profile-banner-avatar">
            @else
                <div class="profile-banner-avatar-placeholder">
                    <i class="fas fa-user"></i>
                </div>
            @endif
        </div>
        <div class="profile-banner-details">
            <h1 class="profile-banner-title">{{ $user->name ?? 'Pengguna' }}</h1>
            <div style="font-size: 0.88rem; color: var(--text-muted);">
                Username: <strong style="color: var(--text-color);">{{ $user->username }}</strong>
            </div>
            <div class="profile-banner-meta">
                <span class="dash-role-badge dash-role-badge-{{ $role }}">
                    {{ strtoupper(str_replace('_', ' ', $role)) }}
                </span>
                @if (!empty($profileDetails['sekolah']))
                    <span class="profile-meta-chip">
                        <i class="fas fa-school text-primary"></i> {{ $profileDetails['sekolah'] }}
                    </span>
                @endif
                @if (!empty($profileDetails['kelas']))
                    <span class="profile-meta-chip">
                        <i class="fas fa-graduation-cap text-warning"></i> Kelas {{ $profileDetails['kelas'] }}
                    </span>
                @endif
                <span class="profile-meta-chip" style="color: #10b981; border-color: rgba(16,185,129,0.3); background: rgba(16,185,129,0.08);">
                    <i class="fas fa-circle-check"></i> Akun Aktif
                </span>
            </div>
        </div>
    </div>

    <!-- Main Grid: Left Data Info, Right Security & Password -->
    <div class="profile-page-grid">
        <!-- Kolom Kiri: Informasi Akun & Biodata -->
        <div class="profile-col-left">
            <!-- Card Data Biodata -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <h2 class="profile-card-title">
                        <i class="fas fa-id-card text-primary"></i>
                        <span>Data Informasi Akun</span>
                    </h2>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">Terhubung Dapodik</span>
                </div>
                <div class="profile-card-body">
                    <div class="profile-info-list">
                        @if ($role === 'peserta_didik')
                            <div class="profile-info-row">
                                <span class="profile-info-label"><i class="fas fa-barcode"></i> NISN</span>
                                <span class="profile-info-val">{{ $profileDetails['nisn'] ?? '-' }}</span>
                            </div>
                            <div class="profile-info-row">
                                <span class="profile-info-label"><i class="fas fa-id-badge"></i> NIPD</span>
                                <span class="profile-info-val">{{ $profileDetails['nipd'] ?? '-' }}</span>
                            </div>
                            <div class="profile-info-row">
                                <span class="profile-info-label"><i class="fas fa-fingerprint"></i> NIK</span>
                                <span class="profile-info-val">{{ $profileDetails['nik'] ?? '-' }}</span>
                            </div>
                            <div class="profile-info-row">
                                <span class="profile-info-label"><i class="fas fa-venus-mars"></i> Jenis Kelamin</span>
                                <span class="profile-info-val">{{ $profileDetails['jenis_kelamin'] ?? '-' }}</span>
                            </div>
                            <div class="profile-info-row">
                                <span class="profile-info-label"><i class="fas fa-chalkboard-user"></i> Kelas / Rombel</span>
                                <span class="profile-info-val">{{ $profileDetails['kelas'] ?? '-' }}</span>
                            </div>
                            <div class="profile-info-row">
                                <span class="profile-info-label"><i class="fas fa-book-open"></i> Jurusan</span>
                                <span class="profile-info-val">{{ $profileDetails['jurusan'] ?? '-' }}</span>
                            </div>
                            <div class="profile-info-row">
                                <span class="profile-info-label"><i class="fas fa-calendar-day"></i> Tempat, Tgl Lahir</span>
                                <span class="profile-info-val">{{ $profileDetails['tempat_lahir'] ?? '-' }}, {{ $profileDetails['tanggal_lahir'] ?? '-' }}</span>
                            </div>
                        @elseif ($role === 'guru' || $role === 'tendik')
                            <div class="profile-info-row">
                                <span class="profile-info-label"><i class="fas fa-address-card"></i> NIP</span>
                                <span class="profile-info-val">{{ $profileDetails['nip'] ?? '-' }}</span>
                            </div>
                            <div class="profile-info-row">
                                <span class="profile-info-label"><i class="fas fa-award"></i> NUPTK</span>
                                <span class="profile-info-val">{{ $profileDetails['nuptk'] ?? '-' }}</span>
                            </div>
                            <div class="profile-info-row">
                                <span class="profile-info-label"><i class="fas fa-fingerprint"></i> NIK</span>
                                <span class="profile-info-val">{{ $profileDetails['nik'] ?? '-' }}</span>
                            </div>
                            <div class="profile-info-row">
                                <span class="profile-info-label"><i class="fas fa-user-tag"></i> Jenis GTK</span>
                                <span class="profile-info-val">{{ $profileDetails['jenis_ptk'] ?? '-' }}</span>
                            </div>
                            <div class="profile-info-row">
                                <span class="profile-info-label"><i class="fas fa-briefcase"></i> Jabatan</span>
                                <span class="profile-info-val">{{ $profileDetails['jabatan'] ?? '-' }}</span>
                            </div>
                            @if ($role === 'guru')
                                <div class="profile-info-row">
                                    <span class="profile-info-label"><i class="fas fa-book"></i> Bidang Studi / Mapel</span>
                                    <span class="profile-info-val">{{ $profileDetails['mapel'] ?? '-' }}</span>
                                </div>
                            @endif
                        @else
                            <div class="profile-info-row">
                                <span class="profile-info-label"><i class="fas fa-envelope"></i> Email Administrator</span>
                                <span class="profile-info-val">{{ $profileDetails['email'] ?? '-' }}</span>
                            </div>
                            <div class="profile-info-row">
                                <span class="profile-info-label"><i class="fas fa-shield-halved"></i> Tingkat Hak Akses</span>
                                <span class="profile-info-val">{{ $profileDetails['level'] ?? 'Super Admin' }}</span>
                            </div>
                            <div class="profile-info-row">
                                <span class="profile-info-label"><i class="fas fa-school"></i> Unit Satuan Pendidikan</span>
                                <span class="profile-info-val">{{ $profileDetails['sekolah'] ?? '-' }}</span>
                            </div>
                        @endif
                        <div class="profile-info-row">
                            <span class="profile-info-label"><i class="fas fa-hashtag"></i> NPSN Sekolah</span>
                            <span class="profile-info-val">{{ $profileDetails['npsn'] ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Update Informasi Kontak -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <h2 class="profile-card-title">
                        <i class="fas fa-address-book text-warning"></i>
                        <span>Informasi Kontak Pengguna</span>
                    </h2>
                </div>
                <div class="profile-card-body">
                    <form action="{{ route('dashboard.profile.contact') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="profile-form-group">
                            <label for="no_hp" class="profile-form-label">No. Handphone / WhatsApp</label>
                            <div class="profile-input-wrapper">
                                <i class="fab fa-whatsapp input-icon" style="color: #22c55e;"></i>
                                <input type="text" id="no_hp" name="no_hp" class="form-control"
                                    placeholder="Contoh: 081234567890" value="{{ old('no_hp', $user->no_hp) }}">
                            </div>
                        </div>

                        <div class="profile-form-group">
                            <label for="alamat" class="profile-form-label">Alamat Domisili</label>
                            <div class="profile-input-wrapper">
                                <i class="fas fa-location-dot input-icon" style="top: 14px; transform: none;"></i>
                                <textarea id="alamat" name="alamat" class="form-control" rows="3"
                                    style="padding-top: 10px; resize: vertical;"
                                    placeholder="Alamat tempat tinggal saat ini...">{{ old('alamat', $user->alamat) }}</textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="padding: 10px 20px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-save"></i>
                            <span>Simpan Kontak</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Keamanan Akun & Ubah Kata Sandi -->
        <div class="profile-col-right">
            <div class="profile-card" style="border-top: 3px solid var(--primary, #4f6ef7);">
                <div class="profile-card-header">
                    <h2 class="profile-card-title">
                        <i class="fas fa-shield-halved text-primary"></i>
                        <span>Keamanan &amp; Ubah Kata Sandi</span>
                    </h2>
                    @if ($user->password_updated_at)
                        <span style="font-size: 0.72rem; color: #10b981; display: inline-flex; align-items: center; gap: 4px;">
                            <i class="fas fa-clock"></i> Diperbarui: {{ \Carbon\Carbon::parse($user->password_updated_at)->diffForHumans() }}
                        </span>
                    @endif
                </div>

                <div class="profile-card-body">
                    <p style="font-size: 0.84rem; color: var(--text-muted); margin-bottom: 18px; line-height: 1.4;">
                        Untuk menjaga keamanan akun Anda, gunakan kata sandi yang kuat dengan kombinasi huruf besar, huruf kecil, angka, dan simbol khusus.
                    </p>

                    <form action="{{ route('dashboard.profile.password') }}" method="POST" id="formChangePassword">
                        @csrf
                        @method('PUT')

                        <!-- Password Saat Ini -->
                        <div class="profile-form-group">
                            <label for="current_password" class="profile-form-label">
                                Kata Sandi Saat Ini <span class="text-danger">*</span>
                            </label>
                            <div class="profile-input-wrapper">
                                <i class="fas fa-key input-icon"></i>
                                <input type="password" id="current_password" name="current_password"
                                    class="form-control @error('current_password') is-invalid @enderror"
                                    placeholder="Masukkan kata sandi saat ini" required autocomplete="current-password">
                                <button type="button" class="btn-toggle-eye" data-target="current_password" aria-label="Lihat kata sandi">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                            @error('current_password')
                                <div style="color: #ef4444; font-size: 0.78rem; margin-top: 5px;">
                                    <i class="fas fa-circle-exclamation me-1"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Password Baru -->
                        <div class="profile-form-group">
                            <label for="new_password" class="profile-form-label">
                                Kata Sandi Baru <span class="text-danger">*</span>
                            </label>
                            <div class="profile-input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" id="new_password" name="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    placeholder="Masukkan kata sandi baru (8-15 karakter)" required autocomplete="new-password">
                                <button type="button" class="btn-toggle-eye" data-target="new_password" aria-label="Lihat kata sandi">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <div style="color: #ef4444; font-size: 0.78rem; margin-top: 5px;">
                                    <i class="fas fa-circle-exclamation me-1"></i> {{ $message }}
                                </div>
                            @enderror

                            <!-- Strength Meter -->
                            <div class="pw-strength-wrap">
                                <div class="pw-strength-text">
                                    <span>Kekuatan Kata Sandi</span>
                                    <strong id="strengthLabel" style="color: var(--text-muted);">Belum Terisi</strong>
                                </div>
                                <div class="pw-strength-bar">
                                    <div class="pw-strength-fill" id="strengthBar"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Konfirmasi Password Baru -->
                        <div class="profile-form-group">
                            <label for="password_confirmation" class="profile-form-label">
                                Konfirmasi Kata Sandi Baru <span class="text-danger">*</span>
                            </label>
                            <div class="profile-input-wrapper">
                                <i class="fas fa-lock-open input-icon"></i>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                    class="form-control"
                                    placeholder="Ulangi kata sandi baru" required autocomplete="new-password">
                                <button type="button" class="btn-toggle-eye" data-target="password_confirmation" aria-label="Lihat kata sandi">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Live Parameters Checklist Card -->
                        <div class="pw-rules-card">
                            <div class="pw-rules-title">
                                <i class="fas fa-list-check text-primary"></i>
                                <span>Ketentuan Pembuatan Kata Sandi:</span>
                            </div>
                            <div class="pw-rule-item" id="rule-length">
                                <i class="fas fa-circle-dot"></i>
                                <span>Panjang minimal 8 dan maksimal 15 karakter</span>
                            </div>
                            <div class="pw-rule-item" id="rule-upper">
                                <i class="fas fa-circle-dot"></i>
                                <span>Mengandung huruf kapital / besar (A-Z)</span>
                            </div>
                            <div class="pw-rule-item" id="rule-lower">
                                <i class="fas fa-circle-dot"></i>
                                <span>Mengandung huruf kecil (a-z)</span>
                            </div>
                            <div class="pw-rule-item" id="rule-number">
                                <i class="fas fa-circle-dot"></i>
                                <span>Mengandung angka (0-9)</span>
                            </div>
                            <div class="pw-rule-item" id="rule-symbol">
                                <i class="fas fa-circle-dot"></i>
                                <span>Mengandung simbol atau karakter khusus (!@#$%^&*...)</span>
                            </div>
                            <div class="pw-rule-item" id="rule-space">
                                <i class="fas fa-circle-dot"></i>
                                <span>Tidak mengandung spasi</span>
                            </div>
                            <div class="pw-rule-item" id="rule-match">
                                <i class="fas fa-circle-dot"></i>
                                <span>Konfirmasi kata sandi cocok</span>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; margin-top: 10px;">
                            <button type="submit" id="btnSubmitPassword" class="btn btn-primary"
                                style="padding: 11px 24px; font-weight: 700; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fas fa-key"></i>
                                <span>Perbarui Kata Sandi</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Show/Hide Password Eye Toggle
    document.querySelectorAll('.btn-toggle-eye').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');

            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        });
    });

    // Password Live Validation & Strength Meter
    const newPwInput = document.getElementById('new_password');
    const confirmPwInput = document.getElementById('password_confirmation');
    const strengthBar = document.getElementById('strengthBar');
    const strengthLabel = document.getElementById('strengthLabel');
    const btnSubmit = document.getElementById('btnSubmitPassword');

    const ruleLength = document.getElementById('rule-length');
    const ruleUpper = document.getElementById('rule-upper');
    const ruleLower = document.getElementById('rule-lower');
    const ruleNumber = document.getElementById('rule-number');
    const ruleSymbol = document.getElementById('rule-symbol');
    const ruleSpace = document.getElementById('rule-space');
    const ruleMatch = document.getElementById('rule-match');

    const updateRuleState = (el, isValid) => {
        const icon = el.querySelector('i');
        if (isValid) {
            el.classList.add('valid');
            el.classList.remove('invalid');
            icon.className = 'fas fa-circle-check';
        } else {
            el.classList.remove('valid');
            el.classList.add('invalid');
            icon.className = 'fas fa-circle-dot';
        }
    };

    const evaluatePassword = () => {
        const val = newPwInput.value || '';
        const confirmVal = confirmPwInput.value || '';

        // Rules
        const isLengthValid = val.length >= 8 && val.length <= 15;
        const isUpperValid = /[A-Z]/.test(val);
        const isLowerValid = /[a-z]/.test(val);
        const isNumberValid = /[0-9]/.test(val);
        const isSymbolValid = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(val);
        const isNoSpaceValid = val.length > 0 && !/\s/.test(val);
        const isMatchValid = val.length > 0 && val === confirmVal;

        updateRuleState(ruleLength, isLengthValid);
        updateRuleState(ruleUpper, isUpperValid);
        updateRuleState(ruleLower, isLowerValid);
        updateRuleState(ruleNumber, isNumberValid);
        updateRuleState(ruleSymbol, isSymbolValid);
        updateRuleState(ruleSpace, isNoSpaceValid);
        updateRuleState(ruleMatch, isMatchValid);

        // Calculate score
        let score = 0;
        if (val.length >= 8) score++;
        if (val.length >= 10 && val.length <= 15) score++;
        if (isUpperValid) score++;
        if (isLowerValid) score++;
        if (isNumberValid) score++;
        if (isSymbolValid) score++;
        if (isNoSpaceValid) score++;

        if (val.length === 0) {
            strengthBar.style.width = '0%';
            strengthBar.style.backgroundColor = 'transparent';
            strengthLabel.textContent = 'Belum Terisi';
            strengthLabel.style.color = 'var(--text-muted)';
        } else if (score <= 3) {
            strengthBar.style.width = '25%';
            strengthBar.style.backgroundColor = '#ef4444';
            strengthLabel.textContent = 'Lemah';
            strengthLabel.style.color = '#ef4444';
        } else if (score <= 5) {
            strengthBar.style.width = '60%';
            strengthBar.style.backgroundColor = '#f59e0b';
            strengthLabel.textContent = 'Sedang';
            strengthLabel.style.color = '#f59e0b';
        } else if (score < 7) {
            strengthBar.style.width = '85%';
            strengthBar.style.backgroundColor = '#3b82f6';
            strengthLabel.textContent = 'Kuat';
            strengthLabel.style.color = '#3b82f6';
        } else {
            strengthBar.style.width = '100%';
            strengthBar.style.backgroundColor = '#10b981';
            strengthLabel.textContent = 'Sangat Kuat';
            strengthLabel.style.color = '#10b981';
        }
    };

    if (newPwInput && confirmPwInput) {
        newPwInput.addEventListener('input', evaluatePassword);
        confirmPwInput.addEventListener('input', evaluatePassword);
    }
});
</script>
@endpush
