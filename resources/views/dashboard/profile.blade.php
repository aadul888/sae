@extends('layouts.dashboard')

@section('title', 'Profil & Keamanan Akun — Sistem Aplikasi Edukasi (SAE)')
@section('dash_title', 'Profil Pengguna')

@section('content')
    <div class="profile-container">
        <!-- Welcome Profile Banner -->
        <div class="profile-banner-card">
            <div class="profile-banner-avatar-wrap">
                @if (!empty($profileDetails['foto_url']))
                    <img src="{{ $profileDetails['foto_url'] }}" alt="{{ $user->name }}" class="profile-banner-avatar"
                        id="mainProfileAvatarImg">
                @else
                    <div class="profile-banner-avatar-placeholder" id="mainProfileAvatarPlaceholder">
                        <i class="fas fa-user"></i>
                    </div>
                @endif

                @if ($role === 'guru' || $role === 'tendik')
                    <button type="button" class="profile-avatar-edit-btn" onclick="openUploadFotoModal()"
                        title="Unggah / Ganti Pasfoto Mandiri" aria-label="Unggah Pasfoto">
                        <i class="fas fa-camera"></i>
                    </button>
                @endif
            </div>
            <div class="profile-banner-details">
                <div
                    style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <h1 class="profile-banner-title">{{ $user->name ?? 'Pengguna' }}</h1>
                        <div style="font-size: 0.88rem; color: var(--text-muted);">
                            Username: <strong style="color: var(--text-color);">{{ $user->username }}</strong>
                        </div>
                    </div>
                    @if ($role === 'guru' || $role === 'tendik')
                        <div>
                            <button type="button" class="btn btn-outline btn-sm" onclick="openUploadFotoModal()"
                                style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem; padding: 7px 14px; border-radius: 8px;">
                                <i class="fas fa-camera text-primary"></i>
                                <span>{{ !empty($profileDetails['foto_url']) ? 'Ganti Pasfoto' : 'Unggah Pasfoto' }}</span>
                            </button>
                        </div>
                    @endif
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
                    <span class="profile-meta-chip"
                        style="color: #10b981; border-color: rgba(16,185,129,0.3); background: rgba(16,185,129,0.08);">
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
                                    <span class="profile-info-label"><i class="fas fa-chalkboard-user"></i> Kelas /
                                        Rombel</span>
                                    <span class="profile-info-val">{{ $profileDetails['kelas'] ?? '-' }}</span>
                                </div>
                                <div class="profile-info-row">
                                    <span class="profile-info-label"><i class="fas fa-book-open"></i> Jurusan</span>
                                    <span class="profile-info-val">{{ $profileDetails['jurusan'] ?? '-' }}</span>
                                </div>
                                <div class="profile-info-row">
                                    <span class="profile-info-label"><i class="fas fa-calendar-day"></i> Tempat, Tgl
                                        Lahir</span>
                                    <span class="profile-info-val">{{ $profileDetails['tempat_lahir'] ?? '-' }},
                                        {{ $profileDetails['tanggal_lahir'] ?? '-' }}</span>
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
                                        <span class="profile-info-label"><i class="fas fa-book"></i> Bidang Studi /
                                            Mapel</span>
                                        <span class="profile-info-val">{{ $profileDetails['mapel'] ?? '-' }}</span>
                                    </div>
                                @endif
                            @else
                                <div class="profile-info-row">
                                    <span class="profile-info-label"><i class="fas fa-envelope"></i> Email
                                        Administrator</span>
                                    <span class="profile-info-val">{{ $profileDetails['email'] ?? '-' }}</span>
                                </div>
                                <div class="profile-info-row">
                                    <span class="profile-info-label"><i class="fas fa-shield-halved"></i> Tingkat Hak
                                        Akses</span>
                                    <span class="profile-info-val">{{ $profileDetails['level'] ?? 'Super Admin' }}</span>
                                </div>
                                <div class="profile-info-row">
                                    <span class="profile-info-label"><i class="fas fa-school"></i> Unit Satuan
                                        Pendidikan</span>
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
                                        style="padding-top: 10px; resize: vertical;" placeholder="Alamat tempat tinggal saat ini...">{{ old('alamat', $user->alamat) }}</textarea>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary"
                                style="padding: 10px 20px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
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
                            <span
                                style="font-size: 0.72rem; color: #10b981; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="fas fa-clock"></i> Diperbarui:
                                {{ \Carbon\Carbon::parse($user->password_updated_at)->diffForHumans() }}
                            </span>
                        @endif
                    </div>

                    <div class="profile-card-body">
                        <p style="font-size: 0.84rem; color: var(--text-muted); margin-bottom: 18px; line-height: 1.4;">
                            Untuk menjaga keamanan akun Anda, gunakan kata sandi yang kuat dengan kombinasi huruf besar,
                            huruf kecil, angka, dan simbol khusus.
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
                                        placeholder="Masukkan kata sandi saat ini" required
                                        autocomplete="current-password">
                                    <button type="button" class="btn-toggle-eye" data-target="current_password"
                                        aria-label="Lihat kata sandi">
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
                                        placeholder="Masukkan kata sandi baru (8-15 karakter)" required
                                        autocomplete="new-password">
                                    <button type="button" class="btn-toggle-eye" data-target="new_password"
                                        aria-label="Lihat kata sandi">
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
                                        class="form-control" placeholder="Ulangi kata sandi baru" required
                                        autocomplete="new-password">
                                    <button type="button" class="btn-toggle-eye" data-target="password_confirmation"
                                        aria-label="Lihat kata sandi">
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

                            <div
                                style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; margin-top: 10px;">
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

        @if ($role === 'guru' || $role === 'tendik')
            {{-- Modal Unggah Pasfoto Mandiri GTK --}}
            <div id="fotoUploadModal" class="modal-backdrop"
                style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
                <div class="card"
                    style="max-width: 480px; width: 92%; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div
                                style="width: 36px; height: 36px; border-radius: 8px; background: rgba(99,102,241,0.15); display: flex; align-items: center; justify-content: center; color: var(--primary);">
                                <i class="fas fa-camera"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                                    Unggah Pasfoto Mandiri
                                </h3>
                                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                                    {{ $role === 'guru' ? 'Guru Pengampu / Pendidik' : 'Tenaga Kependidikan' }}
                                </div>
                            </div>
                        </div>
                        <button type="button" onclick="closeUploadFotoModal()"
                            style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div style="overflow-y: auto; flex: 1; padding-right: 4px;">
                        {{-- Petunjuk Unggah Foto --}}
                        <div
                            style="background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.2); border-radius: 10px; padding: 12px; margin-bottom: 16px; font-size: 0.78rem; line-height: 1.5; color: var(--text-color);">
                            <div style="font-weight: 700; color: var(--primary); margin-bottom: 4px;">
                                <i class="fas fa-shield-halved me-1"></i> Standar &amp; Keamanan Data:
                            </div>
                            <ul style="margin: 0; padding-left: 18px; color: var(--text-muted);">
                                <li>Foto disimpan secara mandiri dan aman di server SAE.</li>
                                <li>Mendukung format <strong>PNG, JPG, atau JPEG</strong> (Maks. 5 MB).</li>
                                <li>Foto akan otomatis dioptimasi dan ditampilkan pada bilah samping, header, dan profil
                                    akun.</li>
                            </ul>
                        </div>

                        {{-- Area Pratinjau Foto --}}
                        <div style="text-align: center; margin-bottom: 16px;">
                            <div
                                style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">
                                Pratinjau Foto Profil
                            </div>
                            <div id="modalFotoPreviewContainer"
                                style="width: 130px; height: 130px; margin: 0 auto; border-radius: 50%; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 10px 10px; border: 3px solid var(--primary, #4f6ef7); display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; box-shadow: 0 4px 16px rgba(0,0,0,0.35);">
                                <img id="modalFotoPreviewImg" src="{{ $profileDetails['foto_url'] ?? '' }}"
                                    alt="Pratinjau Foto"
                                    style="{{ !empty($profileDetails['foto_url']) ? 'display: block;' : 'display: none;' }} width: 100%; height: 100%; object-fit: cover;">
                                <div id="modalFotoPreviewPlaceholder"
                                    style="{{ !empty($profileDetails['foto_url']) ? 'display: none;' : 'display: flex;' }} flex-direction: column; align-items: center; justify-content: center; color: var(--text-muted); font-size: 0.8rem; padding: 10px;">
                                    <i class="fas fa-user mb-1" style="font-size: 2.2rem; opacity: 0.4;"></i>
                                    <div style="font-size: 0.72rem;">Belum ada foto</div>
                                </div>
                            </div>
                            <div id="modalFotoFileSpecs"
                                style="display: none; font-size: 0.74rem; color: #10b981; margin-top: 8px; font-weight: 600;">
                                -
                            </div>
                        </div>

                        {{-- Form Unggah Drag & Drop --}}
                        <form id="formUploadFotoMandiri" enctype="multipart/form-data">
                            @csrf
                            <div id="modalFotoDropZone"
                                style="border: 2px dashed rgba(99,102,241,0.4); border-radius: 12px; padding: 20px 14px; text-align: center; cursor: pointer; transition: all 0.2s ease; background: rgba(255,255,255,0.01);"
                                onclick="document.getElementById('modalFotoFileInput').click()">
                                <i class="fas fa-cloud-arrow-up"
                                    style="font-size: 1.8rem; color: var(--primary); margin-bottom: 8px;"></i>
                                <div style="font-size: 0.84rem; font-weight: 700; color: var(--text-color);">
                                    Pilih file gambar atau seret ke sini
                                </div>
                                <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 4px;">
                                    PNG, JPG, JPEG (Maks. 5 MB)
                                </div>
                                <input type="file" id="modalFotoFileInput" name="foto"
                                    accept=".png,.jpg,.jpeg,image/png,image/jpeg" style="display: none;">
                            </div>
                        </form>
                    </div>

                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border-color); gap: 10px;">
                        <div>
                            <button type="button" id="btnHapusFotoGtk" class="btn btn-danger btn-sm"
                                onclick="confirmDeleteFoto()"
                                style="{{ !empty($profileDetails['foto_url']) ? 'display: inline-flex;' : 'display: none;' }} align-items: center; gap: 6px;">
                                <i class="fas fa-trash-can"></i>
                                <span>Hapus Foto</span>
                            </button>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" class="btn btn-outline" onclick="closeUploadFotoModal()"
                                style="padding: 8px 16px; font-size: 0.82rem;">Batal</button>
                            <button type="button" id="btnSimpanFotoGtk" class="btn btn-primary"
                                onclick="submitUploadFoto()"
                                style="padding: 8px 18px; font-size: 0.82rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;"
                                disabled>
                                <i class="fas fa-upload"></i>
                                <span>Simpan Foto</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
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

    @if ($role === 'guru' || $role === 'tendik')
        let selectedFotoFile = null;
        const currentFotoUrl = @json($profileDetails['foto_url'] ?? '');

        function openUploadFotoModal() {
            const modal = document.getElementById('fotoUploadModal');
            if (!modal) return;
            modal.style.display = 'flex';
            resetUploadFotoForm();
        }

        function closeUploadFotoModal() {
            const modal = document.getElementById('fotoUploadModal');
            if (modal) modal.style.display = 'none';
        }

        function resetUploadFotoForm() {
            selectedFotoFile = null;
            const fileInput = document.getElementById('modalFotoFileInput');
            if (fileInput) fileInput.value = '';

            const previewImg = document.getElementById('modalFotoPreviewImg');
            const placeholder = document.getElementById('modalFotoPreviewPlaceholder');
            const specs = document.getElementById('modalFotoFileSpecs');
            const btnSimpan = document.getElementById('btnSimpanFotoGtk');

            if (btnSimpan) btnSimpan.disabled = true;

            if (currentFotoUrl) {
                if (previewImg) {
                    previewImg.src = currentFotoUrl;
                    previewImg.style.display = 'block';
                }
                if (placeholder) placeholder.style.display = 'none';
            } else {
                if (previewImg) {
                    previewImg.src = '';
                    previewImg.style.display = 'none';
                }
                if (placeholder) placeholder.style.display = 'flex';
            }

            if (specs) {
                specs.style.display = 'none';
                specs.textContent = '-';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('modalFotoFileInput');
            const dropZone = document.getElementById('modalFotoDropZone');
            const previewImg = document.getElementById('modalFotoPreviewImg');
            const placeholder = document.getElementById('modalFotoPreviewPlaceholder');
            const specs = document.getElementById('modalFotoFileSpecs');
            const btnSimpan = document.getElementById('btnSimpanFotoGtk');

            if (!fileInput) return;

            fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                handleSelectedFile(file);
            });

            if (dropZone) {
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropZone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        dropZone.style.borderColor = 'var(--primary)';
                        dropZone.style.background = 'rgba(99, 102, 241, 0.08)';
                    });
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        dropZone.style.borderColor = 'rgba(99, 102, 241, 0.4)';
                        dropZone.style.background = 'rgba(255, 255, 255, 0.01)';
                    });
                });

                dropZone.addEventListener('drop', (e) => {
                    const dt = e.dataTransfer;
                    if (dt && dt.files && dt.files.length > 0) {
                        handleSelectedFile(dt.files[0]);
                    }
                });
            }

            function handleSelectedFile(file) {
                if (!file) return;

                const validTypes = ['image/png', 'image/jpeg', 'image/jpg'];
                if (!validTypes.includes(file.type.toLowerCase())) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Format Tidak Sesuai',
                        text: 'Hanya format gambar PNG, JPG, atau JPEG yang diperbolehkan.',
                    });
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Ukuran Melebihi Batas',
                        text: 'Ukuran file tidak boleh melebihi 5 MB.',
                    });
                    return;
                }

                selectedFotoFile = file;

                const reader = new FileReader();
                reader.onload = (e) => {
                    if (previewImg) {
                        previewImg.src = e.target.result;
                        previewImg.style.display = 'block';
                    }
                    if (placeholder) placeholder.style.display = 'none';

                    if (specs) {
                        const sizeKb = (file.size / 1024).toFixed(1);
                        specs.textContent = `${file.name} (${sizeKb} KB)`;
                            specs.style.display = 'block';
                        }

                        if (btnSimpan) btnSimpan.disabled = false;
                    };
                    reader.readAsDataURL(file);
                }
            });

            function submitUploadFoto() {
                if (!selectedFotoFile) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Pilih File',
                        text: 'Silakan pilih file foto terlebih dahulu.',
                    });
                    return;
                }

                const btnSimpan = document.getElementById('btnSimpanFotoGtk');
                const originalText = btnSimpan ? btnSimpan.innerHTML : '';
                if (btnSimpan) {
                    btnSimpan.disabled = true;
                    btnSimpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Mengunggah...</span>';
                }

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('foto', selectedFotoFile);

                fetch('{{ route('dashboard.profile.foto.upload') }}', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    })
                    .then(res => res.json().then(data => ({
                        status: res.status,
                        ok: res.ok,
                        data
                    })))
                    .then(({
                        ok,
                        data
                    }) => {
                        if (!ok || data.status !== 'success') {
                            throw new Error(data.message || 'Gagal mengunggah foto profil.');
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message,
                            timer: 1600,
                            showConfirmButton: false,
                        }).then(() => {
                            window.location.reload();
                        });
                    })
                    .catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Terjadi Kesalahan',
                            text: err.message,
                        });
                    })
                    .finally(() => {
                        if (btnSimpan) {
                            btnSimpan.disabled = false;
                            btnSimpan.innerHTML = originalText;
                        }
                    });
            }

            function confirmDeleteFoto() {
                Swal.fire({
                    title: 'Hapus Pasfoto?',
                    text: 'Foto profil Anda akan dihapus dan dikembalikan ke avatar bawaan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fas fa-trash-can me-1"></i> Ya, Hapus',
                    cancelButtonText: 'Batal',
                }).then((res) => {
                    if (res.isConfirmed) {
                        fetch('{{ route('dashboard.profile.foto.delete') }}', {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                },
                            })
                            .then(r => r.json().then(data => ({
                                status: r.status,
                                ok: r.ok,
                                data
                            })))
                            .then(({
                                ok,
                                data
                            }) => {
                                if (!ok || data.status !== 'success') {
                                    throw new Error(data.message || 'Gagal menghapus foto.');
                                }

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Terhapus!',
                                    text: data.message,
                                    timer: 1500,
                                    showConfirmButton: false,
                                }).then(() => {
                                    window.location.reload();
                                });
                            })
                            .catch(err => {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal',
                                    text: err.message,
                                });
                            });
                    }
                });
            }
        @endif
    </script>
@endpush
