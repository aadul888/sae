@extends('layouts.dashboard')

@section('title', 'Kegiatan Siswa (OSIS, Organisasi, Ekstrakurikuler & Agenda) — SAE')
@section('dash_title', 'Kegiatan Siswa')

@section('content')
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(16,185,129,0.12); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                <i class="fas fa-flag"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Kegiatan Siswa
                </h2>
                <p style="margin: 2px 0 0 0; font-size: 0.82rem; color: var(--text-muted);">
                    Pengelolaan pengurus OSIS, organisasi kesiswaan, ekstrakurikuler, dan kalender agenda kegiatan siswa.
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($canCreate)
                <button type="button" class="btn btn-outline" id="btnOpenOrganisasiModal" title="Tambah Organisasi Kesiswaan" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;">
                    <i class="fas fa-sitemap text-primary"></i>
                </button>
                <button type="button" class="btn btn-outline" id="btnOpenEkskulModal" title="Tambah Ekstrakurikuler" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;">
                    <i class="fas fa-futbol text-success"></i>
                </button>
                <button type="button" class="btn btn-primary" id="btnOpenAgendaModal" title="Tambah Agenda Kegiatan" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;">
                    <i class="fas fa-calendar-plus"></i>
                </button>
            @endif
        </div>
    </div>

    <!-- 2. Stat Grid Baku SAE -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: #2563eb;">
                <i class="fas fa-id-badge"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['total_osis'] ? 'Aktif' : '-' }}</div>
                <div class="dash-stat-label">Status OSIS</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(168,85,247,0.12); color: #a855f7;">
                <i class="fas fa-sitemap"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_organisasi'] ?? 0) }}</div>
                <div class="dash-stat-label">Organisasi Lain</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-futbol"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_ekskul'] ?? 0) }}</div>
                <div class="dash-stat-label">Ekstrakurikuler</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_agenda'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Agenda</div>
            </div>
        </div>
    </div>

    <!-- 3. Navigation Tabs Wrapper -->
    <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
        <div class="periode-nav-desktop">
            <a href="{{ route('dashboard.kesiswaan.kegiatan.index', ['tab' => 'osis']) }}"
                class="periode-nav-tab {{ $activeTab === 'osis' ? 'active' : '' }}">
                <i class="fas fa-id-badge"></i> OSIS
            </a>
            <a href="{{ route('dashboard.kesiswaan.kegiatan.index', ['tab' => 'organisasi']) }}"
                class="periode-nav-tab {{ $activeTab === 'organisasi' ? 'active' : '' }}">
                <i class="fas fa-sitemap"></i> Organisasi
            </a>
            <a href="{{ route('dashboard.kesiswaan.kegiatan.index', ['tab' => 'ekskul']) }}"
                class="periode-nav-tab {{ $activeTab === 'ekskul' ? 'active' : '' }}">
                <i class="fas fa-futbol"></i> Ekstrakurikuler
            </a>
            <a href="{{ route('dashboard.kesiswaan.kegiatan.index', ['tab' => 'agenda']) }}"
                class="periode-nav-tab {{ $activeTab === 'agenda' ? 'active' : '' }}">
                <i class="fas fa-calendar-days"></i> Agenda
            </a>
        </div>
    </div>

    <!-- 4. Content Sesuai Tab -->
    @if ($activeTab === 'osis')
        <!-- TAB 1: OSIS -->
        @if ($osis)
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-bottom: 24px;">
                <div class="card" style="padding: 24px; text-align: center;">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(59,130,246,0.12); color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 14px;">
                        <i class="fas fa-id-badge"></i>
                    </div>
                    <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--text-color);">{{ $osis->nama_organisasi }}</h3>
                    <div style="font-size: 0.85rem; color: var(--primary); font-weight: 700; margin-top: 4px;">Masa Bakti: {{ $osis->masa_bakti }}</div>
                    <hr style="border: none; border-top: 1px solid var(--border-color); margin: 16px 0;">
                    <div style="text-align: left; font-size: 0.88rem; line-height: 1.8;">
                        <div><strong>Ketua:</strong> {{ $osis->ketua?->nama ?: '-' }}</div>
                        <div><strong>Pembina:</strong> {{ $osis->pembina?->nama ?: '-' }}</div>
                    </div>
                </div>
                <div class="card" style="padding: 24px;">
                    <h4 style="margin: 0 0 12px 0; font-size: 1rem; font-weight: 800; color: var(--text-color);">Struktur Pengurus OSIS</h4>
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: 10px 14px; font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase;">Jabatan</th>
                                <th style="padding: 10px 14px; font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase;">Nama Pengurus</th>
                                <th style="padding: 10px 14px; font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase;">NISN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($osis->anggota as $ag)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 10px 14px; font-weight: 700; color: var(--primary);">{{ $ag->jabatan }}</td>
                                    <td style="padding: 10px 14px; font-weight: 700; color: var(--text-color);">{{ $ag->siswa?->nama ?: '-' }}</td>
                                    <td style="padding: 10px 14px; font-family: monospace; font-size: 0.82rem;">{{ $ag->siswa?->nisn ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" style="padding: 20px; text-align: center; color: var(--text-muted);">Belum ada data anggota pengurus terdaftar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="card" style="padding: 40px; text-align: center; color: var(--text-muted); margin-bottom: 24px;">
                <i class="fas fa-id-badge mb-2" style="font-size: 2.5rem; opacity: 0.5;"></i>
                <div style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Belum Ada Data Kepengurusan OSIS</div>
                <p style="margin: 0 0 16px 0; font-size: 0.88rem;">Silakan buat data OSIS periode aktif untuk memetakan pengurus dan program kerja.</p>
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('btnOpenOrganisasiModal').click()" style="border-radius: 8px;">
                        <i class="fas fa-plus"></i> Buat Data OSIS
                    </button>
                @endif
            </div>
        @endif

    @elseif ($activeTab === 'organisasi')
        <!-- TAB 2: ORGANISASI LAIN -->
        <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jenis</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Organisasi</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Masa Bakti</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Ketua</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Pembina</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($organisasiList as $org)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 18px;"><span class="badge badge-primary">{{ strtoupper($org->jenis) }}</span></td>
                            <td style="padding: 12px 18px; font-weight: 700; color: var(--text-color);">{{ $org->nama_organisasi }}</td>
                            <td style="padding: 12px 18px; font-size: 0.85rem;">{{ $org->masa_bakti }}</td>
                            <td style="padding: 12px 18px; font-size: 0.85rem;">{{ $org->ketua?->nama ?: '-' }}</td>
                            <td style="padding: 12px 18px; font-size: 0.85rem; color: var(--text-muted);">{{ $org->pembina?->nama ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 30px; text-align: center; color: var(--text-muted);">Belum ada organisasi kesiswaan lain.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    @elseif ($activeTab === 'ekskul')
        <!-- TAB 3: EKSTRAKURIKULER -->
        <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Ekstrakurikuler</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kategori</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jadwal Latihan</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tempat</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Pembina / Pelatih</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Anggota</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ekskulList as $ek)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 18px; font-weight: 700; color: var(--text-color);">{{ $ek->nama_ekskul }}</td>
                            <td style="padding: 12px 18px;"><span class="badge badge-accent">{{ strtoupper($ek->kategori) }}</span></td>
                            <td style="padding: 12px 18px; font-size: 0.85rem;">{{ $ek->jadwal_hari ?: '-' }} {{ $ek->jam_mulai ? '('.substr($ek->jam_mulai,0,5).' - '.substr($ek->jam_selesai,0,5).')' : '' }}</td>
                            <td style="padding: 12px 18px; font-size: 0.85rem;">{{ $ek->tempat ?: '-' }}</td>
                            <td style="padding: 12px 18px; font-size: 0.85rem; color: var(--text-muted);">{{ $ek->pembina?->nama ?: ($ek->pelatih_nama ?: '-') }}</td>
                            <td style="padding: 12px 18px; text-align: center; font-weight: 700;">{{ $ek->anggota->count() }} Siswa</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">Belum ada data ekstrakurikuler.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    @elseif ($activeTab === 'agenda')
        <!-- TAB 4: AGENDA KEGIATAN -->
        <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tanggal</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Judul Kegiatan</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jenis</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tempat</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Penanggung Jawab</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($agendaList as $ag)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 18px; font-size: 0.85rem;">{{ date('d/m/Y', strtotime($ag->tanggal_mulai)) }}</td>
                            <td style="padding: 12px 18px; font-weight: 700; color: var(--text-color);">{{ $ag->judul_kegiatan }}</td>
                            <td style="padding: 12px 18px;"><span class="badge badge-outline">{{ strtoupper($ag->jenis_kegiatan) }}</span></td>
                            <td style="padding: 12px 18px; font-size: 0.85rem;">{{ $ag->tempat ?: '-' }}</td>
                            <td style="padding: 12px 18px; font-size: 0.85rem; color: var(--text-muted);">{{ $ag->penanggung_jawab ?: '-' }}</td>
                            <td style="padding: 12px 18px;">
                                @php
                                    $agColor = match ($ag->status) {
                                        'selesai' => 'badge-success',
                                        'berlangsung' => 'badge-warning',
                                        'dibatalkan' => 'badge-danger',
                                        default => 'badge-primary',
                                    };
                                @endphp
                                <span class="badge {{ $agColor }}">{{ strtoupper($ag->status) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">Belum ada agenda kegiatan kesiswaan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <!-- MODAL 1: ORGANISASI KESISWAAN -->
    <div id="modalOrganisasi" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 500px; padding: 24px; border-radius: 12px; margin: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin: 0;">Tambah Organisasi Siswa</h3>
                <button type="button" class="btn-icon close-modal" data-target="#modalOrganisasi"><i class="fas fa-xmark"></i></button>
            </div>
            <form id="formOrganisasi" method="POST" action="{{ route('dashboard.kesiswaan.kegiatan.organisasi.store') }}">
                @csrf
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Jenis Organisasi <span class="text-danger">*</span></label>
                        <select name="jenis" class="form-control" required style="width: 100%; border-radius: 8px;">
                            <option value="osis">OSIS</option>
                            <option value="mpk">MPK</option>
                            <option value="pramuka">Pramuka</option>
                            <option value="pmr">PMR</option>
                            <option value="rohis">Rohis</option>
                            <option value="paskibra">Paskibra</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Masa Bakti <span class="text-danger">*</span></label>
                        <input type="text" name="masa_bakti" class="form-control" placeholder="Contoh: 2026/2027" required style="width: 100%; border-radius: 8px;">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Nama Organisasi <span class="text-danger">*</span></label>
                    <input type="text" name="nama_organisasi" class="form-control" placeholder="Contoh: OSIS SMK Negeri 1" required style="width: 100%; border-radius: 8px;">
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Ketua Organisasi</label>
                    <select name="ketua_peserta_didik_id" class="form-control" style="width: 100%; border-radius: 8px;">
                        <option value="">-- Pilih Ketua (Siswa) --</option>
                        @foreach ($siswaList as $sw)
                            <option value="{{ $sw->peserta_didik_id }}">{{ $sw->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Guru Pembina</label>
                    <select name="pembina_ptk_id" class="form-control" style="width: 100%; border-radius: 8px;">
                        <option value="">-- Pilih Guru Pembina --</option>
                        @foreach ($pembinaList as $gt)
                            <option value="{{ $gt->ptk_id }}">{{ $gt->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-outline close-modal" data-target="#modalOrganisasi" style="border-radius: 8px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: 8px;"><i class="fas fa-save"></i> Simpan Organisasi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: EKSTRAKURIKULER -->
    <div id="modalEkskul" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 500px; padding: 24px; border-radius: 12px; margin: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin: 0;">Tambah Ekstrakurikuler</h3>
                <button type="button" class="btn-icon close-modal" data-target="#modalEkskul"><i class="fas fa-xmark"></i></button>
            </div>
            <form id="formEkskul" method="POST" action="{{ route('dashboard.kesiswaan.kegiatan.ekskul.store') }}">
                @csrf
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Nama Ekskul <span class="text-danger">*</span></label>
                        <input type="text" name="nama_ekskul" class="form-control" placeholder="Contoh: Futsal" required style="width: 100%; border-radius: 8px;">
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Kategori <span class="text-danger">*</span></label>
                        <select name="kategori" class="form-control" required style="width: 100%; border-radius: 8px;">
                            <option value="olahraga">Olahraga</option>
                            <option value="seni">Seni &amp; Musik</option>
                            <option value="keagamaan">Keagamaan</option>
                            <option value="bela_diri">Bela Diri</option>
                            <option value="sains">Sains &amp; Riset</option>
                            <option value="teknologi">Teknologi &amp; Robotik</option>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Hari Latihan</label>
                        <input type="text" name="jadwal_hari" class="form-control" placeholder="Contoh: Jumat Sore" style="width: 100%; border-radius: 8px;">
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tempat Latihan</label>
                        <input type="text" name="tempat" class="form-control" placeholder="Contoh: Lapangan Utama" style="width: 100%; border-radius: 8px;">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Guru Pembina</label>
                    <select name="pembina_ptk_id" class="form-control" style="width: 100%; border-radius: 8px;">
                        <option value="">-- Pilih Guru Pembina --</option>
                        @foreach ($pembinaList as $gt)
                            <option value="{{ $gt->ptk_id }}">{{ $gt->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-outline close-modal" data-target="#modalEkskul" style="border-radius: 8px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: 8px;"><i class="fas fa-save"></i> Simpan Ekskul</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: AGENDA KEGIATAN -->
    <div id="modalAgenda" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 500px; padding: 24px; border-radius: 12px; margin: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin: 0;">Tambah Agenda Kegiatan</h3>
                <button type="button" class="btn-icon close-modal" data-target="#modalAgenda"><i class="fas fa-xmark"></i></button>
            </div>
            <form id="formAgenda" method="POST" action="{{ route('dashboard.kesiswaan.kegiatan.agenda.store') }}">
                @csrf
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Judul Kegiatan <span class="text-danger">*</span></label>
                    <input type="text" name="judul_kegiatan" class="form-control" placeholder="Contoh: Latihan Dasar Kepemimpinan Siswa (LDKS)" required style="width: 100%; border-radius: 8px;">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Jenis Kegiatan <span class="text-danger">*</span></label>
                        <select name="jenis_kegiatan" class="form-control" required style="width: 100%; border-radius: 8px;">
                            <option value="internal">Internal Sekolah</option>
                            <option value="eksternal">Eksternal</option>
                            <option value="lomba">Lomba / Kejuaraan</option>
                            <option value="upacara">Upacara / Peringatan</option>
                            <option value="bakti_sosial">Bakti Sosial</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-control" required style="width: 100%; border-radius: 8px;">
                            <option value="rencana">Rencana</option>
                            <option value="berlangsung">Berlangsung</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tanggal Mulai <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_mulai" class="form-control" value="{{ date('Y-m-d') }}" required style="width: 100%; border-radius: 8px;">
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-control" style="width: 100%; border-radius: 8px;">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tempat Kegiatan</label>
                    <input type="text" name="tempat" class="form-control" placeholder="Contoh: Aula Utama Sekolah" style="width: 100%; border-radius: 8px;">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-outline close-modal" data-target="#modalAgenda" style="border-radius: 8px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: 8px;"><i class="fas fa-save"></i> Simpan Agenda</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/kesiswaan-kegiatan.js') }}"></script>
@endpush
