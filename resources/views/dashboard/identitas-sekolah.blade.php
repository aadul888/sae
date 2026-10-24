@extends('layouts.dashboard')

@section('title', 'Identitas Sekolah — Sistem Aplikasi Edukasi (SAE)')
@section('dash_title', 'Identitas Sekolah')

@section('content')
    @php
        $schoolLogoUrl = $sekolahMeta?->logo_url;
        $schoolLogoSize = $sekolahMeta?->formatted_logo_size;
        $schoolKopUrl = $sekolahMeta?->kop_url;
        $schoolKopSize = $sekolahMeta?->formatted_kop_size;
    @endphp

    <!-- Welcome / Header Banner -->
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div id="bannerLogoContainer"
                onclick="document.getElementById('cardUploadLogoSekolah')?.scrollIntoView({behavior: 'smooth'})"
                style="width: 58px; height: 58px; border-radius: 12px; background: rgba(255,255,255,0.05); border: 1px dashed rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; overflow: hidden; flex-shrink: 0; transition: all 0.2s;"
                title="Klik untuk melihat form Logo Sekolah">
                <img id="bannerLogoImg" src="{{ $schoolLogoUrl ? $schoolLogoUrl . '?v=' . time() : '' }}" alt="Logo Sekolah"
                    style="width: 100%; height: 100%; object-fit: contain; padding: 4px; display: {{ $schoolLogoUrl ? 'block' : 'none' }};">
                <div id="bannerLogoPlaceholder"
                    style="display: {{ $schoolLogoUrl ? 'none' : 'flex' }}; flex-direction: column; align-items: center; justify-content: center; color: var(--primary);">
                    <i class="fas fa-school" style="font-size: 1.4rem;"></i>
                </div>
            </div>
            <div>
                <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 2px;">
                    {{ $sekolah->nama ?? 'Identitas Satuan Pendidikan' }}
                </h2>
                <p style="color: var(--text-muted); font-size: 0.86rem; margin-bottom: 0;">
                    NPSN: <strong
                        style="color: var(--primary); font-family: monospace;">{{ $sekolah->npsn ?? '-' }}</strong> &bull;
                    Data profil resmi satuan pendidikan hasil integrasi Dapodik &amp; SAE.
                </p>
            </div>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if (\App\Models\RolePermission::canAccess(session('user'), 'menu_dapodik'))
                <a href="{{ route('dashboard.dapodik') }}" class="btn btn-outline"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem; text-decoration: none;"
                    title="Tarik / Sinkron Data Dapodik">
                    <i class="fas fa-cloud-arrow-down"></i>
                </a>
            @endif
            @if ($canUpdate)
                <button type="button" class="btn btn-primary"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;"
                    onclick="openEditSekolahModal()" title="Edit Data Identitas Sekolah">
                    <i class="fas fa-pen-to-square"></i>
                </button>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div
            style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- Stat Grid -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                <i class="fas fa-chalkboard-user"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_gtk']) }}</div>
                <div class="dash-stat-label">Pendidik &amp; Tendik</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.12); color: var(--accent);">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_pd']) }}</div>
                <div class="dash-stat-label">Peserta Didik Aktif</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                <i class="fas fa-users-rectangle"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_rombel']) }}</div>
                <div class="dash-stat-label">Rombongan Belajar</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-book-open-reader"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_pembelajaran']) }}</div>
                <div class="dash-stat-label">Mata Pelajaran Kelas</div>
            </div>
        </div>
    </div>

    <!-- 2-Column Details Grid -->
    <div class="dash-grid-2"
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 18px; margin-bottom: 24px;">

        <!-- Box 1: Identitas & Legalitas Sekolah -->
        <div class="card"
            style="padding: 20px; border-radius: 14px; border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 14px;">
            <div
                style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3
                    style="font-size: 1.02rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-landmark text-primary"></i> Profil &amp; Legalitas
                </h3>
                <span class="badge badge-primary" style="font-size: 0.72rem; padding: 3px 8px;">
                    {{ $sekolah->status_sekolah_str ?? 'Aktif' }}
                </span>
            </div>

            <table style="width: 100%; border-collapse: collapse; font-size: 0.84rem;">
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 9px 0; color: var(--text-muted); width: 140px;">Nama Sekolah</td>
                    <td style="padding: 9px 0; font-weight: 700; color: var(--text-color);">
                        {{ $sekolah->nama ?? 'Belum Diatur' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 9px 0; color: var(--text-muted);">NPSN</td>
                    <td style="padding: 9px 0; font-weight: 600; font-family: monospace; color: var(--primary);">
                        {{ $sekolah->npsn ?? '-' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 9px 0; color: var(--text-muted);">NSS</td>
                    <td style="padding: 9px 0; font-weight: 600; font-family: monospace;">{{ $sekolah->nss ?? '-' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 9px 0; color: var(--text-muted);">Bentuk Pendidikan</td>
                    <td style="padding: 9px 0; font-weight: 600;">{{ $sekolah->bentuk_pendidikan_id_str ?? '-' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 9px 0; color: var(--text-muted);">Status Satuan</td>
                    <td style="padding: 9px 0; font-weight: 600;">{{ $sekolah->status_sekolah_str ?? '-' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 9px 0; color: var(--text-muted);">Penyelenggaraan SKS</td>
                    <td style="padding: 9px 0;">
                        <span class="badge {{ ($sekolah->is_sks ?? '0') === '1' ? 'badge-success' : 'badge-outline' }}"
                            style="font-size: 0.72rem; padding: 2px 7px;">
                            {{ ($sekolah->is_sks ?? '0') === '1' ? 'SKS Berjalan' : 'Non SKS (Paket)' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 9px 0; color: var(--text-muted);">Sekolah ID (Dapodik)</td>
                    <td
                        style="padding: 9px 0; font-family: monospace; font-size: 0.76rem; color: var(--text-muted); overflow-wrap: anywhere;">
                        {{ $sekolah->sekolah_id ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <!-- Box 2: Alamat & Lokasi Geografis -->
        <div class="card"
            style="padding: 20px; border-radius: 14px; border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 14px;">
            <div
                style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3
                    style="font-size: 1.02rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-location-dot" style="color: #ef4444;"></i> Alamat &amp; Lokasi Wilayah
                </h3>
                @if (!empty($sekolah->lintang) && !empty($sekolah->bujur))
                    <a href="https://maps.google.com/?q={{ $sekolah->lintang }},{{ $sekolah->bujur }}" target="_blank"
                        rel="noopener noreferrer" class="btn btn-outline"
                        style="padding: 3px 8px; font-size: 0.72rem; border-radius: 6px;">
                        <i class="fas fa-map-location-dot me-1"></i> Buka Peta
                    </a>
                @endif
            </div>

            <table style="width: 100%; border-collapse: collapse; font-size: 0.84rem;">
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 9px 0; color: var(--text-muted); width: 140px;">Alamat Jalan</td>
                    <td style="padding: 9px 0; font-weight: 600; color: var(--text-color);">
                        {{ $sekolah->alamat_jalan ?? '-' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 9px 0; color: var(--text-muted);">RT / RW / Dusun</td>
                    <td style="padding: 9px 0;">
                        {{ collect([$sekolah->rt ?? '' ? 'RT ' . $sekolah->rt : null, $sekolah->rw ?? '' ? 'RW ' . $sekolah->rw : null, $sekolah->dusun ?? null])->filter()->join(' / ') ?:'-' }}
                    </td>
                </tr>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 9px 0; color: var(--text-muted);">Desa / Kelurahan</td>
                    <td style="padding: 9px 0; font-weight: 600;">{{ $sekolah->desa_kelurahan ?? '-' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 9px 0; color: var(--text-muted);">Kecamatan</td>
                    <td style="padding: 9px 0; font-weight: 600;">{{ $sekolah->kecamatan ?? '-' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 9px 0; color: var(--text-muted);">Kabupaten / Kota</td>
                    <td style="padding: 9px 0; font-weight: 600;">{{ $sekolah->kabupaten_kota ?? '-' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 9px 0; color: var(--text-muted);">Provinsi &amp; Kode Pos</td>
                    <td style="padding: 9px 0;">{{ $sekolah->provinsi ?? '-' }}
                        {{ $sekolah->kode_pos ?? '' ? '• ' . $sekolah->kode_pos : '' }}</td>
                </tr>
                <tr>
                    <td style="padding: 9px 0; color: var(--text-muted);">Koordinat GPS</td>
                    <td style="padding: 9px 0; font-family: monospace; font-size: 0.78rem;">
                        {{ collect([$sekolah->lintang ?? '' ? 'Lat: ' . $sekolah->lintang : null, $sekolah->bujur ?? '' ? 'Long: ' . $sekolah->bujur : null])->filter()->join(' • ') ?:'-' }}
                    </td>
                </tr>
            </table>
        </div>

        <!-- Box 3: Kontak & Komunikasi Resmi -->
        <div class="card"
            style="padding: 20px; border-radius: 14px; border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 14px;">
            <div
                style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3
                    style="font-size: 1.02rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-headset text-primary"></i> Kontak &amp; Saluran Resmi
                </h3>
            </div>

            <table style="width: 100%; border-collapse: collapse; font-size: 0.84rem;">
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 9px 0; color: var(--text-muted); width: 140px;">Nomor Telepon</td>
                    <td style="padding: 9px 0; font-weight: 600;">{{ $sekolah->nomor_telepon ?? '-' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 9px 0; color: var(--text-muted);">Nomor Fax</td>
                    <td style="padding: 9px 0;">{{ $sekolah->nomor_fax ?? '-' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 9px 0; color: var(--text-muted);">Email Resmi</td>
                    <td style="padding: 9px 0;">
                        @if (!empty($sekolah->email))
                            <a href="mailto:{{ $sekolah->email }}"
                                style="color: var(--primary); text-decoration: none; font-weight: 600;">
                                <i class="far fa-envelope me-1"></i>{{ $sekolah->email }}
                            </a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 9px 0; color: var(--text-muted);">Website Resmi</td>
                    <td style="padding: 9px 0;">
                        @if (!empty($sekolah->website))
                            <a href="{{ str_starts_with($sekolah->website, 'http') ? $sekolah->website : 'http://' . $sekolah->website }}"
                                target="_blank" rel="noopener noreferrer"
                                style="color: var(--primary); text-decoration: none; font-weight: 600;">
                                <i class="fas fa-globe me-1"></i>{{ $sekolah->website }}
                            </a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <!-- Box 4: Konfigurasi Sistem SAE & Dapodik -->
        <div class="card"
            style="padding: 20px; border-radius: 14px; border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 14px;">
            <div
                style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3
                    style="font-size: 1.02rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-sliders text-primary"></i> Parameter Sistem SAE
                </h3>
                <span class="badge badge-success" style="font-size: 0.72rem; padding: 3px 8px;">Terhubung</span>
            </div>

            <table style="width: 100%; border-collapse: collapse; font-size: 0.84rem;">
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 9px 0; color: var(--text-muted); width: 140px;">Nama Aplikasi</td>
                    <td style="padding: 9px 0; font-weight: 600;">
                        {{ $settings->app_name ?? 'SAE - Sistem Aplikasi Edukasi' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 9px 0; color: var(--text-muted);">Endpoint Dapodik</td>
                    <td style="padding: 9px 0; font-family: monospace; font-size: 0.78rem; color: var(--primary);">
                        {{ $settings->dapodik_url ?? 'http://localhost:5774' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 9px 0; color: var(--text-muted);">Terakhir Sinkron</td>
                    <td style="padding: 9px 0; font-size: 0.82rem;">
                        {{ $settings->last_sync ?? ($sekolah->updated_at ?? '-') }}</td>
                </tr>
                <tr>
                    <td style="padding: 9px 0; color: var(--text-muted);">Total Akun Pengguna</td>
                    <td style="padding: 9px 0; font-weight: 700; color: var(--text-color);">
                        {{ number_format($stats['total_pengguna']) }} Pengguna Terdaftar</td>
                </tr>
            </table>
        </div>

    </div>

    <!-- Section: Integrasi Akun Google Belajar.id (OAuth 2.0) -->
    <div class="card" id="cardGoogleOauth"
        style="padding: 22px; border-radius: 14px; border: 1px solid var(--border-color); margin-bottom: 24px; display: flex; flex-direction: column; gap: 16px;">
        <div
            style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 14px; gap: 10px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div
                    style="width: 40px; height: 40px; border-radius: 10px; background: rgba(66, 133, 244, 0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                </div>
                <div>
                    <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                        Integrasi Akun Google (Belajar.id OAuth 2.0)
                    </h3>
                    <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                        Konfigurasi kredensial Google Sign-In untuk login Admin, Guru, Tendik &amp; Peserta Didik tanpa perlu membuka berkas <code>.env</code> secara manual.
                    </div>
                </div>
            </div>
            @php
                $googleConfigured = !empty(config('services.google.client_id')) && !empty(config('services.google.client_secret'));
            @endphp
            <span class="badge {{ $googleConfigured ? 'badge-success' : 'badge-warning' }}"
                style="font-size: 0.76rem; padding: 5px 12px; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas {{ $googleConfigured ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
                <span>{{ $googleConfigured ? 'Terkonfigurasi & Aktif' : 'Belum Dikonfigurasi' }}</span>
            </span>
        </div>

        <!-- Panduan & Callback URI -->
        <div style="background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.2); border-radius: 12px; padding: 14px 16px; font-size: 0.82rem; color: var(--text-color);">
            <div style="font-weight: 700; margin-bottom: 6px; display: flex; align-items: center; gap: 6px; color: var(--primary);">
                <i class="fas fa-circle-info"></i> Petunjuk Registrasi Google Cloud Console:
            </div>
            <ol style="margin: 0; padding-left: 20px; line-height: 1.6; color: var(--text-muted);">
                <li>Buka <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener noreferrer" style="color: var(--primary); text-decoration: underline;">Google Cloud Console &rarr; Credentials</a>.</li>
                <li>Pilih / buat <strong>OAuth 2.0 Client ID</strong> dengan Application type wajib: <strong>Web application</strong> (bukan Desktop / Android / Service Account).</li>
                <li>Pada bagian <strong>Authorized redirect URIs</strong>, tambahkan URI Callback berikut:</li>
            </ol>
            <div style="margin-top: 10px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 260px; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; font-family: monospace; font-size: 0.82rem; color: var(--accent); overflow-wrap: anywhere;">
                    {{ route('auth.google.callback') }}
                </div>
                <button type="button" class="btn btn-outline" id="btnCopyCallbackUri"
                    onclick="copyToClipboard('{{ route('auth.google.callback') }}', 'btnCopyCallbackUri')"
                    style="padding: 7px 14px; font-size: 0.82rem;">
                    <i class="fas fa-copy me-1"></i> Salin URI Callback
                </button>
            </div>
            <div style="margin-top: 12px; padding: 10px 12px; background: rgba(245,158,11,0.08); border-left: 3px solid #f59e0b; border-radius: 6px; font-size: 0.76rem; color: var(--text-color); line-height: 1.5;">
                <strong style="color: #f59e0b;"><i class="fas fa-triangle-exclamation me-1"></i> Catatan Domain &amp; Solusi Error 401 (invalid_client):</strong><br>
                &bull; <strong>Pengujian di Server Online:</strong> Pastikan domain online (contoh: <code>https://test-app.smakpal.sch.id/auth/google/callback</code>) sudah didaftarkan pada <em>Authorized redirect URIs</em> Google Console.<br>
                &bull; <strong>Pengujian di Komputer Lokal:</strong> Google menolak domain lokal seperti <code>.test</code> / <code>.local</code>. Gunakan <code>http://localhost/auth/google/callback</code> (Google secara khusus mengizinkan <code>http://localhost</code>) atau lakukan pengujian langsung di domain server online ber-HTTPS.<br>
                &bull; <strong>Penyebab Error 401 invalid_client:</strong> Client ID tidak ditemukan Google karena tipe aplikasi bukan <em>Web application</em>, atau salah menyalin nilai (terbawa spasi/kutip), atau Layar Persetujuan OAuth (OAuth consent screen) belum dibuat / belum dipublikasikan.
            </div>
        </div>

        <!-- Form Simpan Kredensial ke .env -->
        <form action="{{ route('dashboard.identitas-sekolah.google-config') }}" method="POST" style="display: flex; flex-direction: column; gap: 14px;">
            @csrf
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 14px;">
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                        GOOGLE_CLIENT_ID <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" name="google_client_id" required
                        value="{{ old('google_client_id', config('services.google.client_id')) }}"
                        placeholder="Contoh: 1234567890-xxx.apps.googleusercontent.com"
                        style="width: 100%; border: 1px solid var(--border-color); background: var(--input-bg); padding: 10px 14px; color: var(--text-color); font-size: 0.86rem; border-radius: 10px; outline: none; font-family: monospace;">
                </div>

                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                        GOOGLE_CLIENT_SECRET <span style="color: #ef4444;">*</span>
                    </label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" name="google_client_secret" id="googleClientSecret" required
                            value="{{ old('google_client_secret', config('services.google.client_secret')) }}"
                            placeholder="Contoh: GOCSPX-xxxxxxxxxxxxxxxxxxxxxxxx"
                            style="width: 100%; border: 1px solid var(--border-color); background: var(--input-bg); padding: 10px 42px 10px 14px; color: var(--text-color); font-size: 0.86rem; border-radius: 10px; outline: none; font-family: monospace;">
                        <button type="button" onclick="toggleGoogleSecret('googleClientSecret', 'eyeSecretIcon')"
                            style="position: absolute; right: 12px; background: transparent; border: none; color: var(--text-muted); cursor: pointer; padding: 4px;">
                            <i class="fas fa-eye" id="eyeSecretIcon"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                    GOOGLE_REDIRECT_URI <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: normal;">(Opsional, otomatis mengikuti URL domain jika dikosongkan)</span>
                </label>
                <input type="text" name="google_redirect_uri"
                    value="{{ old('google_redirect_uri', config('services.google.redirect_uri') ?: route('auth.google.callback')) }}"
                    placeholder="{{ route('auth.google.callback') }}"
                    style="width: 100%; border: 1px solid var(--border-color); background: var(--input-bg); padding: 10px 14px; color: var(--text-color); font-size: 0.86rem; border-radius: 10px; outline: none; font-family: monospace;">
            </div>

            @if ($canUpdate)
                <div style="display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin-top: 4px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px; font-weight: 700; font-size: 0.88rem;">
                        <i class="fas fa-floppy-disk me-1"></i> Simpan Konfigurasi ke .env
                    </button>
                </div>
            @endif
        </form>
    </div>

    <!-- Section: Identitas Visual & Dokumen Resmi Satuan Pendidikan (Terpisah & Rapi) -->
    <div class="dash-grid-2" style="margin-bottom: 24px;">

        <!-- Card 1: Form Upload Logo Sekolah -->
        <div class="card" id="cardUploadLogoSekolah"
            style="padding: 22px; border-radius: 14px; border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 16px;">
            <div
                style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; gap: 10px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div
                        style="width: 38px; height: 38px; border-radius: 10px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                        <i class="fas fa-image"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.02rem; font-weight: 700; color: var(--text-color); margin: 0;">
                            Logo Resmi Sekolah
                        </h3>
                        <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 1px;">
                            Kartu pelajar digital, rapor, sertifikat &amp; favicon sistem
                        </div>
                    </div>
                </div>
                <span id="cardLogoBadge" class="badge {{ $schoolLogoUrl ? 'badge-success' : 'badge-outline' }}"
                    style="font-size: 0.72rem; padding: 4px 9px;">
                    {{ $schoolLogoUrl ? 'Tersimpan (' . ($schoolLogoSize ?? 'PNG') . ')' : 'Belum Ada Logo' }}
                </span>
            </div>

            <!-- Preview Box + Dropzone in Card -->
            <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                <div id="cardLogoPreviewContainer"
                    style="width: 120px; height: 120px; border-radius: 14px; border: 2px dashed var(--border-color); background: repeating-conic-gradient(#80808018 0% 25%, transparent 0% 50%) 50% / 14px 14px, var(--bg-card); display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; flex-shrink: 0; box-shadow: 0 4px 14px rgba(0,0,0,0.2); transition: all 0.2s;">
                    <img id="cardLogoPreviewImg" src="{{ $schoolLogoUrl ? $schoolLogoUrl . '?v=' . time() : '' }}"
                        alt="Preview Logo"
                        style="max-width: 100%; max-height: 100%; object-fit: contain; padding: 6px; display: {{ $schoolLogoUrl ? 'block' : 'none' }};">
                    <div id="cardLogoPlaceholder"
                        style="display: {{ $schoolLogoUrl ? 'none' : 'flex' }}; flex-direction: column; align-items: center; justify-content: center; text-align: center; color: var(--text-muted); padding: 8px;">
                        <i class="fas fa-school"
                            style="font-size: 1.8rem; color: var(--primary); margin-bottom: 4px;"></i>
                        <span style="font-size: 0.68rem;">Belum Ada</span>
                    </div>
                </div>

                <div style="flex: 1; min-width: 200px; display: flex; flex-direction: column; gap: 8px;">
                    <div id="cardLogoDropZone"
                        style="border: 2px dashed rgba(99,102,241,0.35); background: rgba(255,255,255,0.015); border-radius: 12px; padding: 14px 12px; text-align: center; cursor: pointer; transition: all 0.2s;"
                        onclick="document.getElementById('cardLogoFileInput').click()">
                        <input type="file" id="cardLogoFileInput" accept=".png,image/png" style="display: none;">
                        <i class="fas fa-cloud-arrow-up"
                            style="font-size: 1.3rem; color: var(--primary); margin-bottom: 4px;"></i>
                        <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-color);">
                            Seret berkas PNG ke sini atau <span
                                style="color: var(--primary); text-decoration: underline;">Pilih Berkas</span>
                        </div>
                        <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">
                            Khusus format <strong>.PNG</strong> (Transparan didukung) &bull; Maks. 5 MB
                        </div>
                    </div>

                    <div id="cardLogoFileSpecs" style="font-size: 0.74rem; color: var(--text-muted); min-height: 18px;">
                        @if ($schoolLogoUrl)
                            <span style="color: #10b981;"><i class="fas fa-circle-check me-1"></i> PNG Aktif:
                                {{ $schoolLogoSize }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Card Footer Notice & Buttons -->
            <div
                style="padding-top: 10px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                <div style="font-size: 0.72rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-shield-halved text-primary"></i>
                    <span>Tersimpan di <code>sekolah_meta</code> (Aman dari Dapodik)</span>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    @if ($canDelete)
                        <button type="button" id="btnCardDeleteLogo" class="btn btn-outline"
                            style="padding: 6px 12px; font-size: 0.78rem; color: #ef4444; border-color: rgba(239,68,68,0.3); display: {{ $schoolLogoUrl ? 'inline-flex' : 'none' }}; align-items: center; gap: 4px;"
                            onclick="handleDeleteSekolahLogo()">
                            <i class="fas fa-trash-can"></i> Hapus
                        </button>
                    @endif
                    @if ($canUpdate)
                        <button type="button" id="btnCardSaveLogo" class="btn btn-primary"
                            style="padding: 6px 14px; font-size: 0.78rem; display: inline-flex; align-items: center; gap: 5px;"
                            onclick="handleUploadSekolahLogo()">
                            <i class="fas fa-cloud-arrow-up"></i> Simpan Logo
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Card 2: Form Upload Kop Surat Sekolah -->
        <div class="card" id="cardUploadKopSekolah"
            style="padding: 22px; border-radius: 14px; border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 16px;">
            <div
                style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; gap: 10px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div
                        style="width: 38px; height: 38px; border-radius: 10px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                        <i class="fas fa-heading"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.02rem; font-weight: 700; color: var(--text-color); margin: 0;">
                            Kop Surat Resmi (Letterhead)
                        </h3>
                        <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 1px;">
                            Surat dinas, blangko rapor &amp; blangko cetak dokumen
                        </div>
                    </div>
                </div>
                <span id="cardKopBadge" class="badge {{ $schoolKopUrl ? 'badge-success' : 'badge-outline' }}"
                    style="font-size: 0.72rem; padding: 4px 9px;">
                    {{ $schoolKopUrl ? 'Tersimpan (' . ($schoolKopSize ?? 'PNG') . ')' : 'Belum Ada Kop' }}
                </span>
            </div>

            <!-- Preview Box (Wide) + Dropzone in Card -->
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div id="cardKopPreviewContainer"
                    style="width: 100%; height: 95px; border-radius: 12px; border: 2px dashed var(--border-color); background: repeating-conic-gradient(#80808018 0% 25%, transparent 0% 50%) 50% / 12px 12px, var(--bg-card); display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.2); transition: all 0.2s;">
                    <img id="cardKopPreviewImg" src="{{ $schoolKopUrl ? $schoolKopUrl . '?v=' . time() : '' }}"
                        alt="Preview Kop Surat"
                        style="max-width: 100%; max-height: 100%; object-fit: contain; padding: 4px; display: {{ $schoolKopUrl ? 'block' : 'none' }};">
                    <div id="cardKopPlaceholder"
                        style="display: {{ $schoolKopUrl ? 'none' : 'flex' }}; flex-direction: column; align-items: center; justify-content: center; text-align: center; color: var(--text-muted); padding: 6px;">
                        <i class="fas fa-heading"
                            style="font-size: 1.5rem; color: var(--primary); margin-bottom: 3px;"></i>
                        <span style="font-size: 0.7rem;">Belum Ada Kop Surat</span>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <div id="cardKopDropZone"
                        style="flex: 1; min-width: 200px; border: 2px dashed rgba(99,102,241,0.35); background: rgba(255,255,255,0.015); border-radius: 12px; padding: 12px 10px; text-align: center; cursor: pointer; transition: all 0.2s;"
                        onclick="document.getElementById('cardKopFileInput').click()">
                        <input type="file" id="cardKopFileInput" accept=".png,image/png" style="display: none;">
                        <i class="fas fa-cloud-arrow-up"
                            style="font-size: 1.2rem; color: var(--primary); margin-bottom: 2px;"></i>
                        <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-color);">
                            Seret berkas PNG ke sini atau <span
                                style="color: var(--primary); text-decoration: underline;">Pilih Berkas</span>
                        </div>
                        <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 1px;">
                            PNG (Lebar 1200 - 1800 px) &bull; Maks. 6 MB
                        </div>
                    </div>

                    <div id="cardKopFileSpecs" style="font-size: 0.74rem; color: var(--text-muted); min-width: 140px;">
                        @if ($schoolKopUrl)
                            <span style="color: #10b981;"><i class="fas fa-circle-check me-1"></i> PNG Aktif:
                                {{ $schoolKopSize }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Card Footer Notice & Buttons -->
            <div
                style="padding-top: 10px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                <div style="font-size: 0.72rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-shield-halved text-primary"></i>
                    <span>Tersimpan di <code>sekolah_meta</code> (Aman dari Dapodik)</span>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    @if ($canDelete)
                        <button type="button" id="btnCardDeleteKop" class="btn btn-outline"
                            style="padding: 6px 12px; font-size: 0.78rem; color: #ef4444; border-color: rgba(239,68,68,0.3); display: {{ $schoolKopUrl ? 'inline-flex' : 'none' }}; align-items: center; gap: 4px;"
                            onclick="handleDeleteSekolahKop()">
                            <i class="fas fa-trash-can"></i> Hapus
                        </button>
                    @endif
                    @if ($canUpdate)
                        <button type="button" id="btnCardSaveKop" class="btn btn-primary"
                            style="padding: 6px 14px; font-size: 0.78rem; display: inline-flex; align-items: center; gap: 5px;"
                            onclick="handleUploadSekolahKop()">
                            <i class="fas fa-cloud-arrow-up"></i> Simpan Kop Surat
                        </button>
                    @endif
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Edit Identitas Sekolah -->
    <div id="modalEditSekolah" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 680px; width: 92%; max-height: 88vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div>
                    <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                        Edit Identitas Sekolah
                    </h3>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                        Perbarui parameter identitas dan kontak resmi sekolah
                    </div>
                </div>
                <button type="button" onclick="closeEditSekolahModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form action="{{ route('dashboard.identitas-sekolah.update') }}" method="POST"
                style="overflow-y: auto; flex: 1; padding-right: 4px;">
                @csrf
                @method('PUT')

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 0.84rem;">
                    <div style="grid-column: span 2;">
                        <label style="display: block; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">Nama
                            Sekolah</label>
                        <input type="text" name="nama" class="form-control"
                            style="width: 100%; padding: 7px 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color);"
                            value="{{ $sekolah->nama ?? '' }}" required>
                    </div>

                    <div>
                        <label
                            style="display: block; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">NPSN</label>
                        <input type="text" name="npsn" class="form-control"
                            style="width: 100%; padding: 7px 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color);"
                            value="{{ $sekolah->npsn ?? '' }}">
                    </div>

                    <div>
                        <label
                            style="display: block; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">NSS</label>
                        <input type="text" name="nss" class="form-control"
                            style="width: 100%; padding: 7px 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color);"
                            value="{{ $sekolah->nss ?? '' }}">
                    </div>

                    <div>
                        <label
                            style="display: block; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">Bentuk
                            Pendidikan</label>
                        <input type="text" name="bentuk_pendidikan" class="form-control"
                            style="width: 100%; padding: 7px 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color);"
                            value="{{ $sekolah->bentuk_pendidikan_id_str ?? '' }}">
                    </div>

                    <div>
                        <label
                            style="display: block; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">Status
                            Sekolah</label>
                        <input type="text" name="status_sekolah" class="form-control"
                            style="width: 100%; padding: 7px 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color);"
                            value="{{ $sekolah->status_sekolah_str ?? '' }}">
                    </div>

                    <div style="grid-column: span 2;">
                        <label
                            style="display: block; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">Alamat
                            Jalan</label>
                        <textarea name="alamat_jalan" rows="2" class="form-control"
                            style="width: 100%; padding: 7px 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color);">{{ $sekolah->alamat_jalan ?? '' }}</textarea>
                    </div>

                    <div>
                        <label
                            style="display: block; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">Kecamatan</label>
                        <input type="text" name="kecamatan" class="form-control"
                            style="width: 100%; padding: 7px 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color);"
                            value="{{ $sekolah->kecamatan ?? '' }}">
                    </div>

                    <div>
                        <label
                            style="display: block; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">Kabupaten
                            / Kota</label>
                        <input type="text" name="kabupaten_kota" class="form-control"
                            style="width: 100%; padding: 7px 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color);"
                            value="{{ $sekolah->kabupaten_kota ?? '' }}">
                    </div>

                    <div>
                        <label
                            style="display: block; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">Provinsi</label>
                        <input type="text" name="provinsi" class="form-control"
                            style="width: 100%; padding: 7px 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color);"
                            value="{{ $sekolah->provinsi ?? '' }}">
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">Kode
                            Pos</label>
                        <input type="text" name="kode_pos" class="form-control"
                            style="width: 100%; padding: 7px 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color);"
                            value="{{ $sekolah->kode_pos ?? '' }}">
                    </div>

                    <div>
                        <label
                            style="display: block; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">Nomor
                            Telepon</label>
                        <input type="text" name="nomor_telepon" class="form-control"
                            style="width: 100%; padding: 7px 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color);"
                            value="{{ $sekolah->nomor_telepon ?? '' }}">
                    </div>

                    <div>
                        <label
                            style="display: block; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">Email
                            Resmi</label>
                        <input type="email" name="email" class="form-control"
                            style="width: 100%; padding: 7px 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color);"
                            value="{{ $sekolah->email ?? '' }}">
                    </div>

                    <div style="grid-column: span 2;">
                        <label
                            style="display: block; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">Website
                            Resmi</label>
                        <input type="text" name="website" class="form-control"
                            style="width: 100%; padding: 7px 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color);"
                            value="{{ $sekolah->website ?? '' }}">
                    </div>
                </div>

                <div
                    style="margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-outline" style="padding: 7px 16px; font-size: 0.82rem;"
                        onclick="closeEditSekolahModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="padding: 7px 16px; font-size: 0.82rem;">Simpan
                        Perubahan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script
        src="{{ asset('js/identitas-sekolah.js') }}?v={{ file_exists(public_path('js/identitas-sekolah.js')) ? filemtime(public_path('js/identitas-sekolah.js')) : time() }}">
    </script>
@endpush
