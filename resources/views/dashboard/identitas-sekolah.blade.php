@extends('layouts.dashboard')

@section('title', 'Identitas Sekolah — Sistem Aplikasi Edukasi (SAE)')
@section('dash_title', 'Identitas Sekolah')

@section('content')
    <!-- Welcome / Header Banner -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-school text-primary me-2"></i> Identitas Satuan Pendidikan
            </h2>
            <p style="color: var(--text-muted); font-size: 0.86rem; margin-bottom: 0;">
                Data profil resmi satuan pendidikan hasil integrasi sinkronisasi Dapodik dan konfigurasi sistem SAE.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 8px;">
            <a href="{{ route('dashboard.dapodik') }}" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.82rem;">
                <i class="fas fa-cloud-arrow-down me-1"></i> Sinkron Dapodik
            </a>
            @if (session('user') &&
                    (is_array(session('user')) ? session('user')['role'] ?? '' : session('user')->role ?? '') === 'admin')
                <button type="button" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.82rem;"
                    onclick="openEditSekolahModal()">
                    <i class="fas fa-pen-to-square me-1"></i> Edit Data
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

    <!-- Modal Edit Identitas Sekolah -->
    <div id="modalEditSekolah" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
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
    <script>
        function openEditSekolahModal() {
            const modal = document.getElementById('modalEditSekolah');
            if (modal) modal.style.display = 'flex';
        }

        function closeEditSekolahModal() {
            const modal = document.getElementById('modalEditSekolah');
            if (modal) modal.style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalEditSekolah');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) closeEditSekolahModal();
                });
            }
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeEditSekolahModal();
            });
        });
    </script>
@endpush
