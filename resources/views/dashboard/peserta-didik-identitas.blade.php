@extends('layouts.dashboard')

@section('title', 'Identitas Lengkap Peserta Didik — SAE')
@section('dash_title', 'Identitas Siswa')

@section('content')
    @php
        $studentPhoto = $pd->foto_url ?? null;
    @endphp

    {{-- Switcher Siswa Khusus Administrator --}}
    @if ($userRole === 'admin' && !empty($allPdList) && $allPdList->isNotEmpty())
        <div class="card" style="margin-bottom: 20px; padding: 12px 18px; border-radius: 12px; background: rgba(99,102,241,0.06); border: 1px dashed rgba(99,102,241,0.3);">
            <form method="GET" action="{{ route('dashboard.peserta-didik.identitas') }}" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div style="font-size: 0.82rem; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-user-shield"></i> Mode Administrator: Pratinjau Identitas Siswa
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <select name="peserta_didik_id" onchange="this.form.submit()" class="form-control" style="font-size: 0.82rem; height: 36px; border-radius: 8px; min-width: 240px;">
                        @foreach ($allPdList as $item)
                            <option value="{{ $item->peserta_didik_id }}" {{ ($pd && $pd->peserta_didik_id === $item->peserta_didik_id) ? 'selected' : '' }}>
                                {{ $item->nama }} ({{ $item->nama_rombel ?: 'Tanpa Kelas' }}) - NISN: {{ $item->nisn }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    @endif

    <!-- 1. Header Banner Baku SAE -->
    <div class="dash-banner"
        style="margin-bottom: 24px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.12) 0%, rgba(6, 182, 212, 0.08) 100%); border: 1px solid rgba(99, 102, 241, 0.25); display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; border-radius: 16px; padding: 20px 24px;">
        <div style="display: flex; align-items: center; gap: 18px; flex: 1; min-width: 0;">
            @if ($studentPhoto)
                <div style="flex-shrink: 0; width: 84px; height: 110px; display: flex; align-items: center; justify-content: center; background: transparent; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.18);">
                    <img src="{{ $studentPhoto }}" alt="{{ $pd->nama ?? 'Siswa' }}"
                         style="width: 100%; height: 100%; object-fit: cover;"
                         onerror="this.style.display='none'; this.parentElement.style.display='none';">
                </div>
            @else
                <div style="flex-shrink: 0; width: 68px; height: 68px; border-radius: 16px; background: rgba(99,102,241,0.15); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                    <i class="fas fa-id-card"></i>
                </div>
            @endif

            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 2px;">
                    Formulir Biodata Resmi &bull; Dapodik Kemendikbudristek
                </div>
                <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin: 0 0 6px 0; line-height: 1.25;">
                    {{ $pd->nama ?? 'Peserta Didik' }}
                </h2>
                <div style="display: flex; align-items: center; gap: 12px; font-size: 0.8rem; color: var(--text-muted); flex-wrap: wrap; margin-bottom: 8px;">
                    <span><i class="fas fa-id-card text-primary me-1"></i> NISN: <strong>{{ $pd->nisn ?? '-' }}</strong></span>
                    <span><i class="fas fa-fingerprint text-info me-1"></i> NIK: <strong>{{ $pd->nik ?? '-' }}</strong></span>
                    <span><i class="fas fa-door-open text-warning me-1"></i> Kelas: <strong>{{ $pd->nama_rombel ?? 'Reguler' }}</strong></span>
                    @if ($waliKelas)
                        <span><i class="fas fa-chalkboard-user text-success me-1"></i> Wali: <strong>{{ $waliKelas['nama'] }}</strong></span>
                    @endif
                </div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <div style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25); border-radius: 20px; font-size: 0.74rem; font-weight: 700; color: #10b981;">
                        <i class="fas fa-circle-check"></i> Status Siswa Aktif
                    </div>
                    <div style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2); border-radius: 20px; font-size: 0.74rem; font-weight: 700; color: var(--primary);">
                        <i class="fas fa-school"></i> {{ $pd->kurikulum_id_str ?: 'Kurikulum Merdeka' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-banner-actions">
            @if ($userRole === 'orang_tua')
                <a href="{{ route('dashboard.orang-tua') }}" class="btn btn-outline" style="padding: 8px 14px; font-size: 0.85rem; border-radius: 8px; text-decoration: none;">
                    <i class="fas fa-house me-1"></i> Beranda Orang Tua
                </a>
            @else
                <a href="{{ route('dashboard.peserta-didik') }}" class="btn btn-outline" style="padding: 8px 14px; font-size: 0.85rem; border-radius: 8px; text-decoration: none;">
                    <i class="fas fa-house me-1"></i> Beranda Siswa
                </a>
            @endif

            @if (!empty($pd->nisn))
                <button type="button" class="btn btn-primary" onclick="openKartuPelajarModal('{{ $pd->nisn }}')" style="padding: 8px 16px; font-size: 0.85rem; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-qrcode"></i> Kartu Pelajar Digital
                </button>
            @endif
        </div>
    </div>

    <!-- 2. Formulir Biodata Lengkap Peserta Didik -->
    <div class="card" style="padding: 24px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg); margin-bottom: 24px;">
        @if ($pd)
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
                <!-- Kolom 1: Data Pribadi Siswa -->
                <div style="background: var(--bg-hover); padding: 20px; border-radius: 14px; border: 1px solid var(--border-color);">
                    <div style="font-weight: 800; font-size: 0.95rem; color: var(--primary); margin-bottom: 14px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                        <i class="fas fa-user"></i> Data Pribadi Peserta Didik
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 10px; font-size: 0.82rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Nama Lengkap:</span>
                            <strong style="color: var(--text-color);">{{ $pd->nama }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">NISN:</span>
                            <strong style="color: var(--text-color); font-family: monospace;">{{ $pd->nisn ?: '-' }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">NIK:</span>
                            <strong style="color: var(--text-color); font-family: monospace;">{{ $pd->nik ?: '-' }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">NIPD:</span>
                            <strong style="color: var(--text-color); font-family: monospace;">{{ $pd->nipd ?: '-' }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Tempat, Tanggal Lahir:</span>
                            <strong style="color: var(--text-color);">{{ $pd->tempat_lahir ?: '-' }}, {{ !empty($pd->tanggal_lahir) ? \Carbon\Carbon::parse($pd->tanggal_lahir)->translatedFormat('d F Y') : '-' }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Jenis Kelamin:</span>
                            <strong style="color: var(--text-color);">{{ ($pd->jenis_kelamin === 'L') ? 'Laki-laki (L)' : 'Perempuan (P)' }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Agama:</span>
                            <strong style="color: var(--text-color);">{{ $pd->agama_id_str ?: 'Islam' }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Anak Ke-:</span>
                            <strong style="color: var(--text-color);">{{ $pd->anak_keberapa ?: '-' }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Tinggi / Berat Badan:</span>
                            <strong style="color: var(--text-color);">{{ $pd->tinggi_badan ? $pd->tinggi_badan . ' cm' : '-' }} / {{ $pd->berat_badan ? $pd->berat_badan . ' kg' : '-' }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Kebutuhan Khusus:</span>
                            <strong style="color: var(--text-color);">{{ $pd->kebutuhan_khusus ?: 'Tidak Ada' }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">No. Telepon / HP:</span>
                            <strong style="color: var(--text-color);">{{ $pd->nomor_telepon_seluler ?: ($pd->nomor_telepon_rumah ?: '-') }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Email Siswa:</span>
                            <strong style="color: var(--text-color);">{{ $pd->email ?: '-' }}</strong>
                        </div>
                        <div style="border-top: 1px solid var(--border-color); padding-top: 8px; margin-top: 4px;">
                            <div style="color: var(--text-muted); margin-bottom: 2px;">Alamat Tempat Tinggal:</div>
                            <div style="color: var(--text-color); font-weight: 600; line-height: 1.4;">{{ $pd->alamat_jalan ?: 'Data alamat terdaftar di basis data Dapodik sekolah' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Kolom 2: Data Akademik & Orang Tua -->
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <!-- Data Akademik -->
                    <div style="background: var(--bg-hover); padding: 20px; border-radius: 14px; border: 1px solid var(--border-color);">
                        <div style="font-weight: 800; font-size: 0.95rem; color: #10b981; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                            <i class="fas fa-graduation-cap"></i> Data Akademik &amp; Rombel Belajar
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 10px; font-size: 0.82rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Rombongan Belajar (Kelas):</span>
                                <strong style="color: #3b82f6;">{{ $pd->nama_rombel ?: '-' }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Tingkat Pendidikan:</span>
                                <strong style="color: var(--text-color);">Kelas {{ $pd->tingkat_pendidikan_id ?: '-' }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Kurikulum Pembelajaran:</span>
                                <strong style="color: var(--text-color);">{{ $pd->kurikulum_id_str ?: 'Kurikulum Merdeka' }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Sekolah Asal:</span>
                                <strong style="color: var(--text-color);">{{ $pd->sekolah_asal ?: '-' }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Tanggal Masuk Sekolah:</span>
                                <strong style="color: var(--text-color);">{{ !empty($pd->tanggal_masuk_sekolah) ? \Carbon\Carbon::parse($pd->tanggal_masuk_sekolah)->translatedFormat('d F Y') : '-' }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Guru Wali Kelas:</span>
                                <strong style="color: #10b981;">{{ $waliKelas['nama'] ?? 'Guru Wali Kelas' }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Data Orang Tua & Wali -->
                    <div style="background: var(--bg-hover); padding: 20px; border-radius: 14px; border: 1px solid var(--border-color);">
                        <div style="font-weight: 800; font-size: 0.95rem; color: #f59e0b; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                            <i class="fas fa-people-roof"></i> Data Orang Tua / Wali Murid
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 10px; font-size: 0.82rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Nama Ayah Kandung:</span>
                                <strong style="color: var(--text-color);">
                                    {{ $pd->nama_ayah ?: '-' }}
                                    @if (str_contains(strtolower($pd->pekerjaan_ayah_id_str ?? ''), 'meninggal'))
                                        <span class="badge badge-danger" style="font-size: 0.65rem; padding: 2px 6px; margin-left: 4px;">Alm.</span>
                                    @endif
                                </strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Pekerjaan Ayah:</span>
                                <strong style="color: var(--text-color);">{{ $pd->pekerjaan_ayah_id_str ?: '-' }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Nama Ibu Kandung:</span>
                                <strong style="color: var(--text-color);">
                                    {{ $pd->nama_ibu ?: '-' }}
                                    @if (str_contains(strtolower($pd->pekerjaan_ibu_id_str ?? ''), 'meninggal'))
                                        <span class="badge badge-danger" style="font-size: 0.65rem; padding: 2px 6px; margin-left: 4px;">Almh.</span>
                                    @endif
                                </strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Pekerjaan Ibu:</span>
                                <strong style="color: var(--text-color);">{{ $pd->pekerjaan_ibu_id_str ?: '-' }}</strong>
                            </div>
                            @if (!empty($pd->nama_wali))
                                <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--border-color); padding-top: 8px;">
                                    <span style="color: var(--text-muted);">Nama Wali Murid:</span>
                                    <strong style="color: var(--text-color);">{{ $pd->nama_wali }} ({{ $pd->pekerjaan_wali_id_str ?: 'Wali' }})</strong>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div style="padding: 32px 16px; text-align: center; color: var(--text-muted);">
                Data identitas peserta didik belum dimuat.
            </div>
        @endif
    </div>

    <!-- Modal Pratinjau Kartu Pelajar Digital (Layar Penuh, Bisa Digeser) -->
    @include('kartu-pelajar.modal-fullscreen')
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kartu-pelajar.css') }}?v={{ file_exists(public_path('css/kartu-pelajar.css')) ? filemtime(public_path('css/kartu-pelajar.css')) : '1' }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/html2canvas.min.js') }}"></script>
    <script src="{{ asset('js/peserta-didik.js') }}?v={{ file_exists(public_path('js/peserta-didik.js')) ? filemtime(public_path('js/peserta-didik.js')) : '1' }}"></script>
@endpush
