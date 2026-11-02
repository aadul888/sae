@extends('layouts.app')

@section('title', 'Buku Tamu Digital (e-Tamu) — ' . ($sekolah->nama ?? 'Sekolah'))
@section('meta_description', 'Portal resmi registrasi mandiri kunjungan tamu sekolah, deteksi otomatis relasi orang tua siswa via NIK/NISN, serta manajemen keamanan pos gerbang.')

@section('content')
    <div class="container">
        {{-- ===== Running Info Ticker ===== --}}
        <div class="ticker-wrap">
            <div class="ticker-label"><i class="fas fa-bullhorn"></i> INFO TAMU</div>
            <div class="ticker-track">
                <div class="ticker-content">
                    @foreach ($running_info as $info)
                        <span class="ticker-item">{{ $info }}</span>
                    @endforeach
                </div>
                <div class="ticker-content" aria-hidden="true">
                    @foreach ($running_info as $info)
                        <span class="ticker-item">{{ $info }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ===== Hero + Card Form Kunjungan Mandiri ===== --}}
        <section class="hero">
            <div>
                <span class="hero-kicker"><i class="fas fa-clipboard-user"></i> Buku Tamu Digital &amp; Pos Keamanan</span>
                <h1 class="hero-title">Buku Tamu Digital<br><span>Mandiri (e-Tamu)</span></h1>
                <p class="hero-desc">
                    Layanan registrasi kunjungan mandiri bagi tamu umum, instansi kedinasan, maupun orang tua/wali murid.
                    Dilengkapi fitur deteksi pintar putra/putri siswa secara otomatis menggunakan NIK atau NISN.
                </p>
                <div class="hero-actions">
                    @if (session('user'))
                        <a href="{{ route('dashboard.keamanan.buku-tamu.index') }}" class="btn btn-primary"
                            style="display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-shield-halved"></i> Dashboard Pos Satpam
                        </a>
                    @else
                        <a href="#form-tamu" class="btn btn-primary"
                            style="display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-pen-to-square"></i> Isi Formulir Kunjungan
                        </a>
                    @endif
                    <a href="#sop" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-list-check"></i> Alur 4 Tahap SOP
                    </a>
                    <a href="{{ route('e-izin.public') }}" class="btn btn-outline"
                        style="display: inline-flex; align-items: center; gap: 8px;"
                        title="Buka Layanan e-Izin Pos Gerbang">
                        <i class="fas fa-door-open text-primary"></i> Layanan e-Izin
                    </a>
                </div>
                <div class="hero-badges">
                    <span class="tech-pill"><i class="fas fa-user-check"></i> Deteksi NIK Orang Tua</span>
                    <span class="tech-pill"><i class="fas fa-qrcode"></i> QR Code Mandiri</span>
                    <span class="tech-pill"><i class="fas fa-shield-halved"></i> Otorisasi Satpam</span>
                </div>
            </div>

            {{-- Card Formulir Tamu Mandiri (Style Seragam card-nisn) --}}
            <div id="form-tamu" class="card-nisn" style="max-width: 580px; width: 100%;">
                <div class="card-nisn-head">
                    <span class="card-nisn-title"><i class="fas fa-id-badge text-primary"></i> Formulir Tamu Mandiri</span>
                    <span
                        style="font-size: var(--text-xs, 0.75rem); color: var(--accent); background: rgba(6,182,212,0.1); padding: 2px 8px; border-radius: var(--radius-sm, 6px);">
                        e-Tamu Live
                    </span>
                </div>
                <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-bottom: 1.25rem;">
                    Silakan pilih kategori kunjungan dan lengkapi data identitas di bawah untuk memperoleh tiket visitor.
                </p>

                <form id="formPublicTamu" action="{{ route('tamu.submit') }}" method="POST" novalidate>
                    @csrf
                    <input type="hidden" name="peserta_didik_id" id="hidden_peserta_didik_id" value="">

                    {{-- Pilih Kategori Tamu --}}
                    <div class="input-group">
                        <label class="input-label">Pilih Kategori Tamu <span style="color:var(--danger, #ef4444);">*</span></label>
                        <div class="etamu-category-grid">
                            <label class="category-option active" data-type="orang_tua">
                                <input type="radio" name="jenis_tamu" value="orang_tua" checked>
                                <span class="category-icon text-primary"><i class="fas fa-user-graduate"></i></span>
                                <span class="category-label">Orang Tua / Wali</span>
                            </label>
                            <label class="category-option" data-type="kedinasan">
                                <input type="radio" name="jenis_tamu" value="kedinasan">
                                <span class="category-icon text-info"><i class="fas fa-building"></i></span>
                                <span class="category-label">Kedinasan</span>
                            </label>
                            <label class="category-option" data-type="umum">
                                <input type="radio" name="jenis_tamu" value="umum">
                                <span class="category-icon text-success"><i class="fas fa-user-group"></i></span>
                                <span class="category-label">Umum</span>
                            </label>
                            <label class="category-option" data-type="alumni">
                                <input type="radio" name="jenis_tamu" value="alumni">
                                <span class="category-icon text-warning"><i class="fas fa-user-tie"></i></span>
                                <span class="category-label">Alumni</span>
                            </label>
                        </div>
                    </div>

                    {{-- Section Khusus Tamu Orang Tua (Deteksi Otomatis / Lookup) --}}
                    <div id="sectionOrangTua" class="student-lookup-box">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; flex-wrap: wrap; gap: 4px;">
                            <span style="font-weight: 700; font-size: var(--text-xs, 0.75rem); color: var(--primary, #0ea5e9);">
                                <i class="fas fa-id-card me-1"></i> Deteksi Otomatis Siswa
                            </span>
                            <span style="font-size: var(--text-2xs, 0.7rem); color: var(--text-muted);">Masukkan NIK Anda atau NISN Siswa</span>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                            <div class="input-group" style="margin-bottom: 0;">
                                <label class="input-label" for="input_nik_ortu">NIK Orang Tua / Wali</label>
                                <input type="text" id="input_nik_ortu" name="nik_tamu" class="input-field"
                                    placeholder="16 digit NIK Anda..." maxlength="16" inputmode="numeric" autocomplete="off">
                            </div>
                            <div class="input-group" style="margin-bottom: 0;">
                                <label class="input-label" for="input_nisn_siswa">Atau NISN / NIK Siswa</label>
                                <div style="display: flex; gap: 6px;">
                                    <input type="text" id="input_nisn_siswa" name="nisn_siswa" class="input-field"
                                        placeholder="NISN / NIK siswa..." maxlength="16" inputmode="numeric" autocomplete="off">
                                    <button type="button" id="btnCekSiswa" class="btn btn-primary"
                                        style="padding: 0 12px; font-size: var(--text-xs, 0.75rem); white-space: nowrap;" title="Cek Otomatis">
                                        <i class="fas fa-search me-1"></i> Cek
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Container Hasil Deteksi Siswa --}}
                        <div id="studentDetectionResult" style="display: none; margin-top: 10px;"></div>

                        {{-- Manual Input Siswa jika belum terdeteksi otomatis --}}
                        <div id="manualStudentInput" style="display: none; margin-top: 10px; border-top: 1px dashed var(--border-glass, rgba(255,255,255,0.1)); padding-top: 10px;">
                            <div style="font-size: var(--text-xs, 0.72rem); color: var(--text-muted); margin-bottom: 6px;">
                                <i class="fas fa-circle-info me-1"></i> Jika belum terdata di sistem, ketik nama siswa dan kelas:
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 8px;">
                                <input type="text" name="nama_siswa" id="input_manual_nama_siswa" class="input-field" placeholder="Nama Siswa">
                                <input type="text" name="rombel_siswa" id="input_manual_rombel_siswa" class="input-field" placeholder="Kelas / Rombel">
                            </div>
                        </div>
                    </div>

                    {{-- Identitas Tamu --}}
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-top: 12px;">
                        <div class="input-group">
                            <label class="input-label" for="tamu_nama">Nama Lengkap Tamu <span style="color:var(--danger, #ef4444);">*</span></label>
                            <input type="text" name="nama_tamu" id="tamu_nama" class="input-field"
                                placeholder="Ketik nama lengkap Anda..." required>
                        </div>
                        <div class="input-group">
                            <label class="input-label" for="tamu_kontak">Nomor WhatsApp / HP <span style="color:var(--danger, #ef4444);">*</span></label>
                            <input type="tel" name="nomor_kontak" id="tamu_kontak" class="input-field"
                                placeholder="08xxxxxxxxxx" inputmode="tel" required>
                        </div>
                    </div>

                    <div class="input-group" id="groupInstansi" style="display: none;">
                        <label class="input-label" for="tamu_instansi">Instansi / Lembaga Asal</label>
                        <input type="text" name="instansi_asal" id="tamu_instansi" class="input-field"
                            placeholder="Contoh: Dinas Pendidikan, PT Mitra, Umum...">
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                        <div class="input-group">
                            <label class="input-label" for="tamu_tujuan">Tujuan Bertemu <span style="color:var(--danger, #ef4444);">*</span></label>
                            <input type="text" name="tujuan_bertemu" id="tamu_tujuan" list="listTujuan" class="input-field"
                                placeholder="Pilih atau ketik tujuan..." required autocomplete="off">
                            <datalist id="listTujuan">
                                <option value="Kepala Sekolah">Kepala Sekolah</option>
                                <option value="Wakil Kepala Sekolah">Wakil Kepala Sekolah</option>
                                <option value="Wali Kelas">Wali Kelas</option>
                                <option value="Guru Bimbingan Konseling (BK)">Guru Bimbingan Konseling (BK)</option>
                                <option value="Guru Mata Pelajaran">Guru Mata Pelajaran</option>
                                <option value="Bagian Tata Usaha (TU)">Bagian Tata Usaha (TU)</option>
                                <option value="Staf Keuangan / Administrasi">Staf Keuangan / Administrasi</option>
                                @foreach ($daftarRuang as $rng)
                                    <option value="{{ $rng->nama_ruang }}">{{ $rng->nama_ruang }}</option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="input-group">
                            <label class="input-label" for="tamu_keperluan">Keperluan Kunjungan <span style="color:var(--danger, #ef4444);">*</span></label>
                            <input type="text" name="keperluan" id="tamu_keperluan" list="listKeperluan" class="input-field"
                                placeholder="Pilih atau ketik keperluan..." required autocomplete="off">
                            <datalist id="listKeperluan">
                                <option value="Konsultasi Perkembangan Belajar Siswa">Konsultasi Perkembangan Belajar Siswa</option>
                                <option value="Mengantar Keperluan / Berkas Siswa">Mengantar Keperluan / Berkas Siswa</option>
                                <option value="Urusan Administrasi Siswa / Ijazah">Urusan Administrasi Siswa / Ijazah</option>
                                <option value="Koordinasi Dinas / Undangan">Koordinasi Dinas / Undangan</option>
                                <option value="Penawaran Kerjasama / Silaturahmi">Penawaran Kerjasama / Silaturahmi</option>
                                <option value="Pengambilan Rapot / Legalisir">Pengambilan Rapot / Legalisir</option>
                            </datalist>
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="input-label" for="tamu_nopol">Nomor Polisi Kendaraan (Opsional)</label>
                        <input type="text" name="nomor_polisi_kendaraan" id="tamu_nopol" class="input-field"
                            placeholder="Contoh: B 1234 ABC / D 5678 XYZ">
                    </div>

                    <button type="submit" class="btn btn-primary" id="btnSubmitTamu"
                        style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px; margin-top: 10px; padding: 0.85rem 1.25rem; font-weight: 700;">
                        <i class="fas fa-paper-plane"></i> Kirim Buku Tamu Digital
                    </button>
                </form>
            </div>
        </section>

        {{-- ===== Statistik KPI Layanan e-Tamu ===== --}}
        <section class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon blue"><i class="fas fa-clipboard-user"></i></div>
                <div>
                    <div class="kpi-num">{{ number_format($stats['hari_ini']) }}</div>
                    <div class="kpi-desc">Total Tamu Hari Ini</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon orange"><i class="fas fa-street-view"></i></div>
                <div>
                    <div class="kpi-num">{{ number_format($stats['di_lokasi']) }}</div>
                    <div class="kpi-desc">Sedang di Lingkungan Sekolah</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon teal"><i class="fas fa-user-graduate"></i></div>
                <div>
                    <div class="kpi-num">{{ number_format($stats['orang_tua']) }}</div>
                    <div class="kpi-desc">Kunjungan Orang Tua</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon purple"><i class="fas fa-circle-check"></i></div>
                <div>
                    <div class="kpi-num">{{ number_format($stats['selesai']) }}</div>
                    <div class="kpi-desc">Kunjungan Selesai / Keluar</div>
                </div>
            </div>
        </section>

        {{-- ===== Alur & SOP 4 Tahap Kunjungan Tamu ===== --}}
        <section id="sop" class="services" style="margin-top: 40px;">
            <div class="section-head">
                <span class="section-kicker"><i class="fas fa-diagram-project"></i> STANDAR OPERASIONAL PROSEDUR (SOP)</span>
                <h2 class="section-title">Alur Pelayanan Tamu Sekolah (e-Tamu)</h2>
                <p class="section-desc">
                    Prosedur penerimaan tamu dirancang tertib, cepat, dan terdata akurat demi kenyamanan bersama di lingkungan pendidikan.
                </p>
            </div>

            <div class="service-grid">
                {{-- Langkah 1 --}}
                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color: var(--primary, #0284c7);"><i class="fas fa-qrcode"></i></div>
                        <h3 class="service-title">1. Pindai QR di Pos Gerbang</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Pindai poster QR code di pos keamanan</li>
                            <li><i class="fas fa-check text-success me-2"></i> Akses formulir digital lewat browser HP</li>
                            <li><i class="fas fa-check text-success me-2"></i> Tanpa perlu antre buku tamu kertas</li>
                        </ul>
                    </div>
                    <span class="service-cta">Pindai QR <i class="fas fa-arrow-right"></i></span>
                </div>

                {{-- Langkah 2 --}}
                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color: var(--warning, #f59e0b);"><i class="fas fa-id-card-clip"></i></div>
                        <h3 class="service-title">2. Isi Formulir &amp; Deteksi Anak</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Pilih kategori Orang Tua / Tamu Umum</li>
                            <li><i class="fas fa-check text-success me-2"></i> Masukkan NIK / NISN untuk auto-deteksi</li>
                            <li><i class="fas fa-check text-success me-2"></i> Isi tujuan bertemu &amp; maksud kunjungan</li>
                        </ul>
                    </div>
                    <span class="service-cta">Verifikasi Data <i class="fas fa-arrow-right"></i></span>
                </div>

                {{-- Langkah 3 --}}
                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color: var(--accent, #06b6d4);"><i class="fas fa-shield-halved"></i></div>
                        <h3 class="service-title">3. Verifikasi &amp; Kartu Visitor</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Tunjukkan kode tiket ke Satpam pos gerbang</li>
                            <li><i class="fas fa-check text-success me-2"></i> Petugas satpam menyerahkan kartu visitor fisik</li>
                            <li><i class="fas fa-check text-success me-2"></i> Tamu diarahkan menuju ruang tujuan</li>
                        </ul>
                    </div>
                    <span class="service-cta">Pos Keamanan <i class="fas fa-arrow-right"></i></span>
                </div>

                {{-- Langkah 4 --}}
                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color: var(--success, #10b981);"><i class="fas fa-door-open"></i></div>
                        <h3 class="service-title">4. Selesai &amp; Lapor Checkout</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Menyelesaikan urusan di ruang tujuan</li>
                            <li><i class="fas fa-check text-success me-2"></i> Mengembalikan kartu visitor ke Satpam</li>
                            <li><i class="fas fa-check text-success me-2"></i> Petugas mencatat jam keluar selesai</li>
                        </ul>
                    </div>
                    <span class="service-cta">Kunjungan Berakhir <i class="fas fa-check"></i></span>
                </div>
            </div>
        </section>

        {{-- ===== Quick Links Modul Terkait ===== --}}
        <section style="margin-top: 40px; margin-bottom: 20px;">
            <div class="card" style="padding: 24px 28px; background: rgba(2, 132, 199, 0.04); border: 1px solid rgba(2, 132, 199, 0.2); border-radius: var(--radius-xl, 16px);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <h3 style="margin: 0 0 6px; font-size: var(--text-lg, 1.15rem); font-weight: 800; color: var(--text-heading);">
                            <i class="fas fa-door-open text-primary me-2"></i> Perizinan Keluar-Masuk Siswa (e-Izin)
                        </h3>
                        <p style="margin: 0; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                            Periksa status tiket digital pengajuan izin keluar sebentar atau pulang cepat peserta didik secara realtime.
                        </p>
                    </div>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="{{ route('e-izin.public') }}" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-magnifying-glass"></i> Lacak Tiket e-Izin
                        </a>
                        <a href="{{ route('kiosk.e-izin') }}" target="_blank" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-desktop"></i> Terminal Kiosk Pos Gerbang
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .etamu-category-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        @media (min-width: 520px) {
            .etamu-category-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        .category-option {
            cursor: pointer;
            border: 1px solid var(--border-glass, rgba(255, 255, 255, 0.1));
            border-radius: var(--radius-lg, 12px);
            padding: 10px 8px;
            text-align: center;
            transition: all 0.2s ease;
            background: var(--input-bg, rgba(255, 255, 255, 0.04));
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }
        .category-option input[type="radio"] {
            display: none;
        }
        .category-option.active {
            border-color: var(--primary, #0ea5e9);
            background: rgba(14, 165, 233, 0.12);
            color: var(--primary, #0ea5e9);
            font-weight: 700;
        }
        .category-icon {
            font-size: var(--text-lg, 1.15rem);
        }
        .category-label {
            font-size: var(--text-xs, 0.72rem);
            line-height: 1.2;
        }
        .student-lookup-box {
            background: rgba(14, 165, 233, 0.05);
            border: 1px solid rgba(14, 165, 233, 0.2);
            border-radius: var(--radius-lg, 12px);
            padding: 12px 14px;
            margin-bottom: 12px;
        }
        .student-result-card {
            border: 1px solid var(--border-glass, rgba(255, 255, 255, 0.1));
            border-radius: var(--radius-md, 10px);
            padding: 10px 12px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            background: var(--card-bg, #1e293b);
            color: var(--text-color, #f8fafc);
        }
        .student-result-info h4 {
            margin: 0 0 2px;
            font-size: var(--text-base, 0.85rem);
            font-weight: 700;
        }
        .student-result-info p {
            margin: 0;
            font-size: var(--text-xs, 0.75rem);
            color: var(--text-muted, #94a3b8);
        }
    </style>
@endpush

@section('scripts')
    <script>
        window.ETAMU_CONFIG = {
            checkStudentUrl: "{{ route('tamu.check-student') }}"
        };
    </script>
    <script src="{{ asset('js/tamu-mandiri.js') }}?v={{ time() }}"></script>
@endsection
