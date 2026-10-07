@extends('layouts.dashboard')

@section('title', 'Profil & Keamanan Akun — Sistem Aplikasi Edukasi (SAE)')
@section('dash_title', 'Profil Pengguna')

@section('content')
    <div class="profile-container">
        <!-- Welcome Profile Banner -->
        <div class="profile-banner-card">
            <div class="profile-banner-avatar-wrap"
                @if ($role === 'guru' || $role === 'tendik' || $role === 'admin') onclick="openUploadFotoModal()" style="cursor: pointer;" title="Unggah Pasfoto Mandiri" @endif>
                @if (!empty($profileDetails['foto_url']))
                    <img src="{{ $profileDetails['foto_url'] }}" alt="{{ $user->name }}" class="profile-banner-avatar"
                        id="mainProfileAvatarImg">
                @else
                    <div class="profile-banner-avatar-placeholder" id="mainProfileAvatarPlaceholder">
                        <i class="fas fa-user"></i>
                    </div>
                @endif

                @if ($role === 'guru' || $role === 'tendik' || $role === 'admin')
                    <button type="button" class="profile-avatar-edit-btn" onclick="openUploadFotoModal()"
                        title="Unggah Pasfoto Mandiri" aria-label="Unggah Pasfoto Mandiri">
                        <i class="fas fa-camera"></i>
                    </button>
                @endif
            </div>
            <div class="profile-banner-details">
                <div class="profile-banner-header">
                    <h1 class="profile-banner-title">{{ $user->name ?? 'Pengguna' }}</h1>
                    <div class="profile-banner-username" title="Identitas Akun / Username">
                        <i class="fas fa-at text-muted" style="font-size: 0.85rem; opacity: 0.85;"></i>
                        <strong class="profile-username-val">{{ $user->username }}</strong>
                    </div>
                </div>
                <div class="profile-banner-meta">
                    <span class="dash-role-badge dash-role-badge-{{ $role }}">
                        {{ strtoupper(str_replace('_', ' ', $role)) }}
                    </span>
                    @if (!empty($profileDetails['sekolah']))
                        <span class="profile-meta-chip" title="Sekolah: {{ $profileDetails['sekolah'] }}">
                            <i class="fas fa-school text-primary"></i> <span>{{ $profileDetails['sekolah'] }}</span>
                        </span>
                    @endif
                    @if (!empty($profileDetails['kelas']))
                        <span class="profile-meta-chip" title="Kelas / Rombel">
                            <i class="fas fa-graduation-cap text-warning"></i> <span>{{ $profileDetails['kelas'] }}</span>
                        </span>
                    @endif
                    @if ($role === 'guru' && !empty($profileDetails['mapel']) && $profileDetails['mapel'] !== '-')
                        <span class="profile-meta-chip" title="Mata Pelajaran Utama">
                            <i class="fas fa-book text-info"></i> <span>{{ $profileDetails['mapel'] }}</span>
                        </span>
                    @endif
                    @if ($role === 'tendik' && !empty($profileDetails['tugas_tambahan']) && $profileDetails['tugas_tambahan'] !== '-')
                        <span class="profile-meta-chip" title="Penugasan / Bidang Tugas">
                            <i class="fas fa-briefcase text-success"></i>
                            <span>{{ $profileDetails['tugas_tambahan'] }}</span>
                        </span>
                    @endif
                    @if (!empty($profileDetails['status_kepegawaian']))
                        <span class="profile-meta-chip" title="Status Kepegawaian">
                            <i class="fas fa-id-card-clip text-info"></i>
                            <span>{{ $profileDetails['status_kepegawaian'] }}</span>
                        </span>
                    @endif
                    <span class="profile-meta-chip profile-chip-status" title="Status Akun Pengguna"
                        style="color: #10b981; border-color: rgba(16,185,129,0.3); background: rgba(16,185,129,0.08);">
                        <i class="fas fa-circle-check"></i> <span>Akun Aktif</span>
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
                                        <span class="profile-info-label"><i class="fas fa-book"></i> Mapel Utama</span>
                                        <span class="profile-info-val">{{ $profileDetails['mapel'] ?? '-' }}</span>
                                    </div>
                                    @if (
                                        !empty($profileDetails['bidang_studi']) &&
                                            $profileDetails['bidang_studi'] !== '-' &&
                                            $profileDetails['bidang_studi'] !== $profileDetails['mapel']
                                    )
                                        <div class="profile-info-row">
                                            <span class="profile-info-label"><i class="fas fa-graduation-cap"></i> Jurusan
                                                / Bidang Studi</span>
                                            <span class="profile-info-val">{{ $profileDetails['bidang_studi'] }}</span>
                                        </div>
                                    @endif
                                    @if (!empty($profileDetails['tugas_tambahan']) && $profileDetails['tugas_tambahan'] !== '-')
                                        <div class="profile-info-row">
                                            <span class="profile-info-label"><i class="fas fa-user-gear"></i> Tugas
                                                Tambahan</span>
                                            <span class="profile-info-val">{{ $profileDetails['tugas_tambahan'] }}</span>
                                        </div>
                                    @endif
                                @elseif ($role === 'tendik')
                                    <div class="profile-info-row">
                                        <span class="profile-info-label"><i class="fas fa-user-gear"></i> Bagian /
                                            Tugas</span>
                                        <span
                                            class="profile-info-val">{{ $profileDetails['tugas_tambahan'] ?? ($profileDetails['jabatan'] ?? '-') }}</span>
                                    </div>
                                    @if (!empty($profileDetails['bidang_studi']) && $profileDetails['bidang_studi'] !== '-')
                                        <div class="profile-info-row">
                                            <span class="profile-info-label"><i class="fas fa-graduation-cap"></i> Bidang
                                                Keahlian / Studi</span>
                                            <span class="profile-info-val">{{ $profileDetails['bidang_studi'] }}</span>
                                        </div>
                                    @endif
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
                                        style="padding-left: 42px !important;" placeholder="Contoh: 081234567890"
                                        value="{{ old('no_hp', $user->no_hp) }}">
                                </div>
                            </div>

                            <div class="profile-form-group">
                                <label for="alamat" class="profile-form-label">Alamat Domisili</label>
                                <div class="profile-input-wrapper">
                                    <i class="fas fa-location-dot input-icon" style="top: 14px; transform: none;"></i>
                                    <textarea id="alamat" name="alamat" class="form-control" rows="3"
                                        style="padding-top: 10px; padding-left: 42px !important; resize: vertical;"
                                        placeholder="Alamat tempat tinggal saat ini...">{{ old('alamat', $user->alamat) }}</textarea>
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

        @if ($role === 'guru' || $role === 'tendik' || $role === 'admin')
            {{-- Modal Unggah Pasfoto Mandiri GTK & Admin --}}
            <div id="fotoUploadModal" class="modal-backdrop"
                data-upload-url="{{ route('dashboard.profile.foto.upload') }}"
                data-delete-url="{{ route('dashboard.profile.foto.delete') }}"
                data-current-foto="{{ $profileDetails['foto_url'] ?? '' }}"
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
                                    {{ $role === 'guru' ? 'Guru Pengampu / Pendidik' : ($role === 'admin' ? 'Administrator Sistem' : 'Tenaga Kependidikan') }}
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
    <script
        src="{{ asset('js/profile.js') }}?v={{ file_exists(public_path('js/profile.js')) ? filemtime(public_path('js/profile.js')) : time() }}">
    </script>
@endpush
