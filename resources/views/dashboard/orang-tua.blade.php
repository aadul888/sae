@extends('layouts.dashboard')

@section('title', 'Portal Orang Tua / Wali Murid — SAE')
@section('dash_title', 'Portal Orang Tua')

@section('content')
    @php
        $hour = date('H');
        $greeting = $hour < 11 ? 'Selamat Pagi,' : ($hour < 15 ? 'Selamat Siang,' : ($hour < 18 ? 'Selamat Sore,' : 'Selamat Malam,'));
        $studentPhoto = $pd->foto_url ?? null;
    @endphp

    {{-- Switcher Siswa Khusus Administrator --}}
    @if ($userRole === 'admin' && !empty($allPdList) && $allPdList->isNotEmpty())
        <div class="card" style="margin-bottom: 20px; padding: 12px 18px; border-radius: 12px; background: rgba(99,102,241,0.06); border: 1px dashed rgba(99,102,241,0.3);">
            <form method="GET" action="{{ route('dashboard.orang-tua') }}" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div style="font-size: 0.82rem; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-user-shield"></i> Mode Administrator: Pratinjau Portal Orang Tua Siswa
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
        style="margin-bottom: 24px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(59, 130, 246, 0.08) 100%); border: 1px solid rgba(16, 185, 129, 0.25); display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; border-radius: 16px; padding: 20px 24px;">
        <div style="display: flex; align-items: center; gap: 18px; flex: 1; min-width: 0;">
            @if ($studentPhoto)
                <div style="flex-shrink: 0; width: 84px; height: 110px; display: flex; align-items: center; justify-content: center; background: transparent; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.18);">
                    <img src="{{ $studentPhoto }}" alt="{{ $pd->nama ?? 'Siswa' }}"
                         style="width: 100%; height: 100%; object-fit: cover;"
                         onerror="this.style.display='none'; this.parentElement.style.display='none';">
                </div>
            @else
                <div style="flex-shrink: 0; width: 68px; height: 68px; border-radius: 16px; background: rgba(16,185,129,0.15); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                    <i class="fas fa-people-roof"></i>
                </div>
            @endif

            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 2px;">
                    {{ $greeting }}
                </div>
                <div style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin-bottom: 6px;">
                    {{ $parentName }}! 👋
                </div>
                <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin: 0 0 6px 0; line-height: 1.25;">
                    Pemantauan Siswa: <span style="color: #10b981;">{{ $pd->nama ?? 'Peserta Didik' }}</span>
                </h2>
                <div style="display: flex; align-items: center; gap: 12px; font-size: 0.8rem; color: var(--text-muted); flex-wrap: wrap; margin-bottom: 8px;">
                    <span title="Nomor Induk Siswa Nasional">
                        <i class="fas fa-id-card text-primary me-1"></i>
                        NISN: <strong style="color: var(--text-color);">{{ $pd->nisn ?? '-' }}</strong>
                    </span>
                    <span title="Rombongan Belajar / Kelas">
                        <i class="fas fa-door-open text-info me-1"></i>
                        Kelas: <strong style="color: var(--text-color);">{{ $pd->nama_rombel ?? 'Reguler' }}</strong>
                    </span>
                    @if ($waliKelas)
                        <span title="Wali Kelas">
                            <i class="fas fa-chalkboard-user text-warning me-1"></i>
                            Wali Kelas: <strong style="color: var(--text-color);">{{ $waliKelas['nama'] }}</strong>
                        </span>
                    @endif
                </div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <div style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.2); border-radius: 20px; font-size: 0.74rem; font-weight: 700; color: #3b82f6;">
                        <i class="fas fa-calendar-alt"></i> TA. {{ \App\Support\SemesterHelper::getActiveSemesterLabel() }}
                    </div>
                    <div style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); border-radius: 20px; font-size: 0.74rem; font-weight: 700; color: #10b981;">
                        <i class="fas fa-shield-check"></i> Portal Terverifikasi Sekolah
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Quick Stats Grid Universal (4 Cards) -->
    <div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
        {{-- Card 1: Persentase Kehadiran Bulan Ini --}}
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #10b981;">{{ $statsHarian['persen'] }}%</div>
                <div class="dash-stat-label">Kehadiran Bulan Ini</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                    {{ $statsHarian['hadir'] }} Hadir, {{ $statsHarian['izin'] + $statsHarian['sakit'] }} Izin/Sakit, {{ $statsHarian['alpha'] }} Alpha
                </div>
            </div>
        </div>

        {{-- Card 2: Status Kehadiran Hari Ini --}}
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                <i class="fas fa-fingerprint"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.15rem; color: #3b82f6;">
                    {{ $statsHarian['jam_masuk_hari_ini'] }}
                </div>
                <div class="dash-stat-label">Presensi Masuk Hari Ini</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                    {{ $statsHarian['status_hari_ini'] }} &bull; Pulang: {{ $statsHarian['jam_pulang_hari_ini'] }}
                </div>
            </div>
        </div>

        {{-- Card 3: Presensi Mata Pelajaran (KBM) --}}
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                <i class="fas fa-book-open"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: var(--primary);">{{ $totalMapelSesi }} Sesi</div>
                <div class="dash-stat-label">Presensi Mapel Kelas</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                    Kehadiran di Jam Pembelajaran KBM
                </div>
            </div>
        </div>

        {{-- Card 4: Izin Keluar & Masuk Sekolah --}}
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #f59e0b;">{{ $totalIzinSiswa }} Dokumen</div>
                <div class="dash-stat-label">Izin Keluar / Sakit</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                    Tiket e-Izin Gerbang &bull; Surat Izin
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Menu Aksi Cepat Portal Orang Tua (Pindah Halaman / Langsung ke Modulnya) -->
    <div class="kepegawaian-quick-grid" style="margin-bottom: 24px;">
        <a href="{{ route('dashboard.orang-tua.kehadiran') }}" class="kepegawaian-quick-btn" style="--quick-color: #10b981;" title="Buka Riwayat Kehadiran Terpadu Siswa">
            <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <span class="kepegawaian-quick-label">Riwayat Kehadiran</span>
        </a>

        <a href="{{ route('dashboard.jadwal-pelajaran.index') }}" class="kepegawaian-quick-btn" style="--quick-color: #3b82f6;" title="Buka Jadwal Pelajaran Siswa">
            <div class="kepegawaian-quick-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                <i class="fas fa-clock"></i>
            </div>
            <span class="kepegawaian-quick-label">Jadwal Pelajaran</span>
        </a>

        <a href="{{ route('dashboard.orang-tua.izin') }}" class="kepegawaian-quick-btn" style="--quick-color: #f59e0b;" title="Buka Modul e-Izin & Surat Sakit Siswa">
            <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <span class="kepegawaian-quick-label">e-Izin &amp; Surat Sakit</span>
        </a>

        <a href="{{ route('dashboard.peserta-didik.identitas') }}" class="kepegawaian-quick-btn" style="--quick-color: #6366f1;" title="Formulir Identitas Lengkap Peserta Didik">
            <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
                <i class="fas fa-id-card-clip"></i>
            </div>
            <span class="kepegawaian-quick-label">Identitas Siswa</span>
        </a>

        <a href="{{ route('dashboard.informasi.index') }}" class="kepegawaian-quick-btn" style="--quick-color: #06b6d4;" title="Pengumuman & Informasi Sekolah">
            <div class="kepegawaian-quick-icon" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4;">
                <i class="fas fa-bullhorn"></i>
            </div>
            <span class="kepegawaian-quick-label">Pengumuman</span>
        </a>

        @if (!empty($pd->nisn))
            <button type="button" class="kepegawaian-quick-btn" onclick="openKartuPelajarModal('{{ $pd->nisn }}')" style="--quick-color: #ec4899;" title="Buka Kartu Pelajar Digital Resmi">
                <div class="kepegawaian-quick-icon" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
                    <i class="fas fa-qrcode"></i>
                </div>
                <span class="kepegawaian-quick-label">Kartu Pelajar</span>
            </button>
        @else
            <button type="button" class="kepegawaian-quick-btn" disabled style="opacity: 0.6; --quick-color: #64748b;" title="NISN belum terdaftar">
                <div class="kepegawaian-quick-icon" style="background: rgba(100, 116, 139, 0.15); color: #64748b;">
                    <i class="fas fa-qrcode"></i>
                </div>
                <span class="kepegawaian-quick-label">Kartu Pelajar</span>
            </button>
        @endif
    </div>

    <!-- 4. Konten Utama: Tabel Semua Transaksi & Aktivitas Terkini Peserta Didik (Point 2) -->
    <div class="card" style="padding: 20px 22px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg); margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-list-check text-primary"></i> Semua Transaksi &amp; Aktivitas Terkini Siswa
                </h3>
                <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0 0;">
                    Kronologi terpadu presensi gerbang (RFID), kehadiran mata pelajaran KBM, tiket e-izin gerbang, serta surat izin resmi.
                </p>
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="badge badge-accent" style="font-size: 0.74rem; padding: 4px 10px; font-weight: 700;">
                    <i class="fas fa-bolt me-1"></i> Real-time Feed
                </span>
            </div>
        </div>

        @if ($semuaTransaksi->isNotEmpty())
            <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                <table class="table-minimal-compact" style="width: 100%;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <th style="min-width: 140px;">Waktu &amp; Tanggal</th>
                            <th style="min-width: 140px;">Kategori</th>
                            <th style="min-width: 240px;">Aktivitas / Rincian Transaksi</th>
                            <th style="min-width: 110px; text-align: center;">Status</th>
                            <th style="min-width: 140px;">Petugas / Guru</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($semuaTransaksi as $trx)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="Waktu &amp; Tanggal">
                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.82rem;">
                                        {!! $trx['waktu_display'] !!}
                                    </div>
                                </td>
                                <td data-label="Kategori">
                                    <span class="badge" style="background: rgba(99,102,241,0.1); color: var(--primary); font-size: 0.72rem; padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fas {{ $trx['kategori_icon'] }}"></i> {{ $trx['kategori'] }}
                                    </span>
                                </td>
                                <td data-label="Aktivitas / Rincian">
                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                        {{ $trx['judul'] }}
                                    </div>
                                    <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                                        {!! $trx['deskripsi'] !!}
                                    </div>
                                </td>
                                <td data-label="Status" style="text-align: center;">
                                    <span class="badge" style="background: {{ $trx['badge_bg'] }}; color: {{ $trx['badge_color'] }}; font-size: 0.74rem; padding: 4px 8px; font-weight: 700; border-radius: 6px;">
                                        {{ $trx['status'] }}
                                    </span>
                                </td>
                                <td data-label="Petugas / Guru" style="font-size: 0.78rem; color: var(--text-muted);">
                                    {{ $trx['petugas'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="padding: 36px 20px; text-align: center; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: 12px;">
                <i class="fas fa-list-check" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 8px; display: block;"></i>
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color);">Belum Ada Transaksi Aktivitas Siswa</div>
                <div style="font-size: 0.76rem; margin-top: 4px;">Setiap aktivitas presensi harian gerbang, presensi KBM kelas, serta izin keluar sekolah akan otomatis dicatat di sini.</div>
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
