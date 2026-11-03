@extends('layouts.app')

@section('title', 'Formulir Kunjungan Tamu Mandiri (e-Tamu) — ' . ($sekolah->nama ?? 'Sekolah'))
@section('meta_description', 'Formulir mandiri registrasi tamu sekolah, deteksi otomatis relasi orang tua siswa via NIK/NISN, dan tiket visitor pos keamanan.')

@section('content')
    <div class="container" style="max-width: 680px; padding-top: 1.5rem; padding-bottom: 3.5rem;">
        {{-- Navigation Back to Portal --}}
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 8px;">
            <a href="{{ route('tamu.public') }}" class="btn btn-outline"
                style="display: inline-flex; align-items: center; gap: 6px; font-size: var(--text-xs, 0.75rem); padding: 5px 12px;">
                <i class="fas fa-arrow-left"></i> Kembali ke Portal e-Tamu
            </a>
            @if (session('user'))
                <a href="{{ route('dashboard.keamanan.buku-tamu.index') }}" class="btn btn-outline"
                    style="display: inline-flex; align-items: center; gap: 6px; font-size: var(--text-xs, 0.75rem); padding: 5px 12px;">
                    <i class="fas fa-shield-halved"></i> Dashboard Pos Satpam
                </a>
            @endif
        </div>

        {{-- ===== Formulir Tamu Mandiri ===== --}}
        <div id="form-tamu" class="card-nisn">
            <div class="card-nisn-head" style="margin-bottom: 0.75rem;">
                <div>
                    <span class="card-nisn-title" style="font-size: var(--text-md, 1rem);">
                        <i class="fas fa-id-badge text-primary"></i> Formulir Tamu Mandiri
                    </span>
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                        Pos Keamanan &bull; {{ $sekolah->nama ?? 'Sekolah' }}
                    </div>
                </div>
                <span style="font-size: var(--text-xs, 0.75rem); color: var(--accent); background: rgba(6,182,212,0.1); padding: 3px 10px; border-radius: var(--radius-sm, 6px); font-weight: 600;">
                    e-Tamu Mandiri
                </span>
            </div>

            <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-bottom: 1.25rem;">
                Silakan pilih kategori kunjungan dan lengkapi data identitas Anda di bawah ini:
            </p>

            <form id="formPublicTamu" action="{{ route('tamu.submit') }}" method="POST" enctype="multipart/form-data" novalidate>
                @csrf
                <input type="hidden" name="peserta_didik_id" id="hidden_peserta_didik_id" value="">

                {{-- Langkah 1: Pilih Kategori Tamu (Wajib Dipilih Lebih Dulu) --}}
                <div class="category-step" id="categoryStep">
                    <div class="category-step-head">
                        <span class="category-step-num">1</span>
                        <div>
                            <div class="category-step-title">Pilih Kategori Tamu Anda</div>
                            <div class="category-step-sub">Wajib dipilih sebelum mengisi data kunjungan</div>
                        </div>
                        <span class="category-step-tag">LANGKAH 1</span>
                    </div>

                    <div class="etamu-category-grid" id="categoryGrid">
                        <label class="category-option" data-type="orang_tua">
                            <input type="radio" name="jenis_tamu" value="orang_tua" required>
                            <span class="category-icon text-primary"><i class="fas fa-user-graduate"></i></span>
                            <span class="category-label">Orang Tua / Wali</span>
                            <span class="category-hint">Wali murid</span>
                        </label>
                        <label class="category-option" data-type="kedinasan">
                            <input type="radio" name="jenis_tamu" value="kedinasan" required>
                            <span class="category-icon text-info"><i class="fas fa-building"></i></span>
                            <span class="category-label">Kedinasan</span>
                            <span class="category-hint">Instansi / dinas</span>
                        </label>
                        <label class="category-option" data-type="umum">
                            <input type="radio" name="jenis_tamu" value="umum" required>
                            <span class="category-icon text-success"><i class="fas fa-user-group"></i></span>
                            <span class="category-label">Umum</span>
                            <span class="category-hint">Masyarakat umum</span>
                        </label>
                        <label class="category-option" data-type="alumni">
                            <input type="radio" name="jenis_tamu" value="alumni" required>
                            <span class="category-icon text-warning"><i class="fas fa-user-tie"></i></span>
                            <span class="category-label">Alumni</span>
                            <span class="category-hint">Siswa Lulus</span>
                        </label>
                    </div>

                    <div class="category-warning" id="categoryWarning" style="display: none;">
                        <i class="fas fa-triangle-exclamation"></i> Silakan pilih salah satu kategori tamu terlebih dahulu.
                    </div>
                </div>

                <div id="formFields" class="etamu-fields-locked">
                {{-- Section Khusus Tamu Orang Tua (Deteksi Otomatis Siswa) --}}
                <div id="sectionOrangTua" class="student-lookup-box" style="display: none;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; flex-wrap: wrap; gap: 4px;">
                        <span style="font-weight: 700; font-size: var(--text-xs, 0.78rem); color: var(--primary, #0ea5e9);">
                            <i class="fas fa-id-card me-1"></i> Deteksi Otomatis Siswa
                        </span>
                        <span style="font-size: var(--text-2xs, 0.7rem); color: var(--text-muted);">Ketik nama siswa, NISN, atau NIK untuk memilih</span>
                    </div>

                    {{-- Search Input with Floating Results Dropdown --}}
                    <div class="student-search-wrapper" id="studentSearchWrapper">
                        <label class="input-label" for="input_cari_siswa" style="font-size: 0.75rem;">Cari Data Siswa</label>
                        <div class="student-search-input-wrap">
                            <input type="text" id="input_cari_siswa" class="input-field"
                                placeholder="Ketik nama siswa, NISN, atau NIK..." autocomplete="off">
                            <span id="studentSearchSpinner" class="student-search-spinner" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </div>
                        <div id="studentDropdownList" class="student-dropdown-results" style="display: none;"></div>
                    </div>

                    {{-- Selected Student Card (Shown upon selection) --}}
                    <div id="studentSelectedBox" style="display: none; margin-top: 8px;">
                        <div class="student-selected-card">
                            <div class="student-selected-avatar">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="student-selected-info">
                                <div class="student-selected-name" id="selectedStudentNama">-</div>
                                <div class="student-selected-sub" id="selectedStudentDetail">-</div>
                            </div>
                            <button type="button" class="btn-clear-student" id="btnClearStudent" title="Ganti Siswa">
                                <i class="fas fa-times me-1"></i> Ganti
                            </button>
                        </div>
                    </div>

                    {{-- Hidden storage for student data --}}
                    <input type="hidden" name="nama_siswa" id="hidden_nama_siswa">
                    <input type="hidden" name="rombel_siswa" id="hidden_rombel_siswa">
                    <input type="hidden" name="nisn_siswa" id="hidden_nisn_siswa">
                    <input type="hidden" name="nik_tamu" id="hidden_nik_ortu">

                    {{-- Toggle manual input if student is not found --}}
                    <div style="margin-top: 8px; display: flex; justify-content: space-between; align-items: center; font-size: var(--text-2xs, 0.72rem);">
                        <span id="studentSearchHint" style="color: var(--text-muted);">Pilih salah satu siswa dari daftar hasil pencarian.</span>
                        <button type="button" id="btnToggleManualStudent" style="background: none; border: none; color: var(--primary, #0ea5e9); cursor: pointer; text-decoration: underline; padding: 0;">
                            Tidak menemukan siswa? Ketik manual
                        </button>
                    </div>

                    <div id="manualStudentInput" style="display: none; margin-top: 8px; border-top: 1px dashed var(--border-glass, rgba(255,255,255,0.12)); padding-top: 8px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 8px;">
                            <div>
                                <label class="input-label" style="font-size: 0.72rem;">Nama Siswa</label>
                                <input type="text" id="input_manual_nama_siswa" class="input-field" placeholder="Ketik nama putra/putri...">
                            </div>
                            <div>
                                <label class="input-label" style="font-size: 0.72rem;">Kelas / Rombel</label>
                                <input type="text" id="input_manual_rombel_siswa" class="input-field" placeholder="Contoh: X RPL 1...">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Identitas Tamu --}}
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
                    <div>
                        <label class="input-label" for="tamu_nama">Nama Lengkap Tamu <span style="color:var(--danger, #ef4444);">*</span></label>
                        <input type="text" name="nama_tamu" id="tamu_nama" class="input-field"
                            placeholder="Ketik nama lengkap Anda..." required>
                    </div>
                    <div>
                        <label class="input-label" for="tamu_kontak">Nomor WhatsApp / HP <span style="color:var(--danger, #ef4444);">*</span></label>
                        <input type="tel" name="nomor_kontak" id="tamu_kontak" class="input-field"
                            placeholder="08xxxxxxxxxx" inputmode="tel" required>
                    </div>
                </div>

                {{-- Instansi / Lembaga Asal (Dropdown + Custom) --}}
                <div id="groupInstansi" style="display: none; margin-top: 12px;">
                    <label class="input-label" for="tamu_instansi_select">Instansi / Lembaga Asal</label>
                    <select id="tamu_instansi_select" class="input-field">
                        <option value="">-- Pilih Instansi / Asal Tamu --</option>
                        <option value="Dinas Pendidikan">Dinas Pendidikan</option>
                        <option value="Kementerian Agama">Kementerian Agama</option>
                        <option value="Pemerintah Daerah / Dinas">Pemerintah Daerah / Dinas</option>
                        <option value="Perusahaan / Mitra Industri (DUDI)">Perusahaan / Mitra Industri (DUDI)</option>
                        <option value="Lembaga / Yayasan Pendidikan">Lembaga / Yayasan Pendidikan</option>
                        <option value="Perguruan Tinggi / Universitas">Perguruan Tinggi / Universitas</option>
                        <option value="Puskesmas / Layanan Kesehatan">Puskesmas / Layanan Kesehatan</option>
                        <option value="Kepolisian / Koramil / Aparat">Kepolisian / Koramil / Aparat</option>
                        <option value="Masyarakat Umum">Masyarakat Umum</option>
                        <option value="Lainnya">Lainnya (Ketik Manual)</option>
                    </select>
                    <div id="wrapInstansiLainnya" style="display: none; margin-top: 6px;">
                        <input type="text" id="instansi_lainnya_input" class="input-field" placeholder="Ketik nama instansi / lembaga asal...">
                    </div>
                    <input type="hidden" name="instansi_asal" id="tamu_instansi">
                </div>

                {{-- Khusus Tamu Kedinasan: NIP & Jabatan (Opsional) --}}
                <div id="sectionKedinasanIdentitas" style="display: none; margin-top: 12px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label class="input-label" for="tamu_nip">NIP Pegawai (Opsional)</label>
                            <input type="text" name="nip_tamu" id="tamu_nip" class="input-field"
                                placeholder="18 digit NIP pegawai..." maxlength="30" inputmode="numeric">
                        </div>
                        <div>
                            <label class="input-label" for="tamu_jabatan">Jabatan (Opsional)</label>
                            <input type="text" name="jabatan_tamu" id="tamu_jabatan" class="input-field"
                                placeholder="Contoh: Pengawas Sekolah, Kasi Kurikulum...">
                        </div>
                    </div>
                </div>

                {{-- Khusus Tamu Kedinasan: Konfirmasi Surat Tugas --}}
                <div id="sectionKedinasanSurat" class="student-lookup-box" style="display: none; background: rgba(6, 182, 212, 0.05); border-color: rgba(6, 182, 212, 0.25); margin-top: 12px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; flex-wrap: wrap; gap: 4px;">
                        <span style="font-weight: 700; font-size: var(--text-xs, 0.78rem); color: var(--accent, #06b6d4);">
                            <i class="fas fa-file-lines me-1"></i> Surat Tugas Kedinasan
                        </span>
                        <span style="font-size: var(--text-2xs, 0.7rem); color: var(--text-muted);">Pilih jika membawa surat tugas resmi</span>
                    </div>

                    <div style="margin-bottom: 10px;">
                        <label class="input-label">Apakah Tersedia Surat Tugas?</label>
                        <div style="display: flex; gap: 12px; margin-top: 4px;">
                            <label class="surat-tugas-radio" style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-size: var(--text-xs, 0.78rem); font-weight: 600; padding: 6px 14px; border: 1px solid var(--border-glass, rgba(255,255,255,0.15)); border-radius: var(--radius-md, 8px); background: var(--card-bg, rgba(255,255,255,0.03));">
                                <input type="radio" name="ada_surat_tugas" value="1" id="radioSuratYa" style="accent-color: var(--primary, #0ea5e9);">
                                <span><i class="fas fa-circle-check text-success me-1"></i> Ya, Ada</span>
                            </label>
                            <label class="surat-tugas-radio" style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-size: var(--text-xs, 0.78rem); font-weight: 600; padding: 6px 14px; border: 1px solid var(--border-glass, rgba(255,255,255,0.15)); border-radius: var(--radius-md, 8px); background: var(--card-bg, rgba(255,255,255,0.03));">
                                <input type="radio" name="ada_surat_tugas" value="0" id="radioSuratTidak" checked style="accent-color: var(--primary, #0ea5e9);">
                                <span><i class="fas fa-circle-xmark text-muted me-1"></i> Tidak Ada</span>
                            </label>
                        </div>
                    </div>

                    <div id="boxDetailSuratTugas" style="display: none; padding-top: 10px; border-top: 1px dashed rgba(6, 182, 212, 0.25);">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div>
                                <label class="input-label" for="nomor_surat_tugas">Nomor Surat Tugas <span style="color:var(--danger, #ef4444);">*</span></label>
                                <input type="text" name="nomor_surat_tugas" id="nomor_surat_tugas" class="input-field"
                                    placeholder="Contoh: 800/123/Disdik/2026">
                            </div>
                            <div>
                                <label class="input-label" for="tanggal_surat_tugas">Tanggal Surat Tugas <span style="color:var(--danger, #ef4444);">*</span></label>
                                <input type="date" name="tanggal_surat_tugas" id="tanggal_surat_tugas" class="input-field">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Data Kunjungan: Dropdown Tujuan Bertemu & Keperluan --}}
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
                    <div>
                        <label class="input-label" for="tamu_tujuan_select">Tujuan Bertemu <span style="color:var(--danger, #ef4444);">*</span></label>
                        <select id="tamu_tujuan_select" class="input-field" required>
                            <option value="">-- Pilih Tujuan Bertemu --</option>
                            <optgroup label="Pimpinan & Manajemen">
                                <option value="Kepala Sekolah">Kepala Sekolah</option>
                                <option value="Wakil Kepala Sekolah (Kurikulum)">Wakil Kepala Sekolah (Kurikulum)</option>
                                <option value="Wakil Kepala Sekolah (Kesiswaan)">Wakil Kepala Sekolah (Kesiswaan)</option>
                                <option value="Wakil Kepala Sekolah (Sarpras)">Wakil Kepala Sekolah (Sarpras)</option>
                                <option value="Wakil Kepala Sekolah (Humas)">Wakil Kepala Sekolah (Humas)</option>
                            </optgroup>
                            <optgroup label="Guru & Bimbingan">
                                <option value="Wali Kelas">Wali Kelas</option>
                                <option value="Guru Bimbingan Konseling (BK)">Guru Bimbingan Konseling (BK)</option>
                                <option value="Guru Mata Pelajaran">Guru Mata Pelajaran</option>
                            </optgroup>
                            <optgroup label="Layanan Tata Usaha & Fasilitas">
                                <option value="Bagian Tata Usaha (TU)">Bagian Tata Usaha (TU)</option>
                                <option value="Staf Keuangan / Kasir">Staf Keuangan / Kasir</option>
                                <option value="Petugas Perpustakaan">Petugas Perpustakaan</option>
                                <option value="Petugas UKS">Petugas UKS</option>
                                <option value="Pos Satpam / Keamanan">Pos Satpam / Keamanan</option>
                                @foreach ($daftarRuang as $rng)
                                    <option value="Ruang {{ $rng->nama_ruang }}">Ruang {{ $rng->nama_ruang }}</option>
                                @endforeach
                            </optgroup>
                            <option value="Lainnya">Lainnya (Ketik Manual)</option>
                        </select>
                        <div id="wrapTujuanLainnya" style="display: none; margin-top: 6px;">
                            <input type="text" id="tujuan_lainnya_input" class="input-field" placeholder="Ketik tujuan bertemu lainnya...">
                        </div>
                        <input type="hidden" name="tujuan_bertemu" id="tamu_tujuan" required>
                    </div>

                    <div>
                        <label class="input-label" for="tamu_keperluan_select">Keperluan Kunjungan <span style="color:var(--danger, #ef4444);">*</span></label>
                        <select id="tamu_keperluan_select" class="input-field" required>
                            <option value="">-- Pilih Keperluan --</option>
                            <optgroup label="Urusan Siswa & Akademik">
                                <option value="Konsultasi Perkembangan Belajar Siswa">Konsultasi Perkembangan Belajar Siswa</option>
                                <option value="Pengambilan Rapot / Legalisir Ijazah">Pengambilan Rapot / Legalisir Ijazah</option>
                                <option value="Mengantar Keperluan / Berkas Siswa">Mengantar Keperluan / Berkas Siswa</option>
                                <option value="Pemanggilan BK / Urusan Disiplin">Pemanggilan BK / Urusan Disiplin</option>
                                <option value="Pengurusan Mutasi / Pindah Siswa">Pengurusan Mutasi / Pindah Siswa</option>
                            </optgroup>
                            <optgroup label="Kedinasan & Kemitraan">
                                <option value="Koordinasi Dinas / Undangan Resmi">Koordinasi Dinas / Undangan Resmi</option>
                                <option value="Kunjungan Monev / Pengawasan">Kunjungan Monev / Pengawasan</option>
                                <option value="Penawaran Kerjasama / Kemitraan">Penawaran Kerjasama / Kemitraan</option>
                                <option value="Urusan Administrasi / Pembayaran">Urusan Administrasi / Pembayaran</option>
                                <option value="Penyerahan Dokumen / Berkas Surat">Penyerahan Dokumen / Berkas Surat</option>
                            </optgroup>
                            <optgroup label="Umum & Alumni">
                                <option value="Silaturahmi / Temu Guru">Silaturahmi / Temu Guru</option>
                                <option value="Urusan Alumni">Urusan Alumni</option>
                            </optgroup>
                            <option value="Lainnya">Lainnya (Ketik Manual)</option>
                        </select>
                        <div id="wrapKeperluanLainnya" style="display: none; margin-top: 6px;">
                            <input type="text" id="keperluan_lainnya_input" class="input-field" placeholder="Ketik keperluan kunjungan lainnya...">
                        </div>
                        <input type="hidden" name="keperluan" id="tamu_keperluan" required>
                    </div>
                </div>

                <div style="margin-top: 12px;">
                    <label class="input-label" for="tamu_nopol">Nomor Polisi Kendaraan (Opsional)</label>
                    <input type="text" name="nomor_polisi_kendaraan" id="tamu_nopol" class="input-field"
                        placeholder="Contoh: B 1234 ABC / D 5678 XYZ">
                </div>

                {{-- Bukti Foto Kunjungan Tamu (Kamera / Unggah File Berkompresi) --}}
                <div class="photo-capture-box" style="margin-top: 16px; border: 1px dashed var(--border-glass, rgba(255,255,255,0.2)); border-radius: var(--radius-lg, 12px); padding: 14px; background: rgba(255,255,255,0.02);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 6px;">
                        <span style="font-weight: 700; font-size: var(--text-xs, 0.78rem); color: var(--text-heading);">
                            <i class="fas fa-camera text-primary me-1"></i> Bukti Foto Kunjungan (Opsional)
                        </span>
                        <span style="font-size: var(--text-2xs, 0.7rem); color: var(--text-muted); background: rgba(255,255,255,0.05); padding: 2px 8px; border-radius: 999px;">
                            Kompresi Otomatis
                        </span>
                    </div>

                    {{-- Mode Switcher: Kamera vs Unggah --}}
                    <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                        <button type="button" class="btn btn-outline btn-photo-tab active" id="btnTabKamera" style="flex: 1; padding: 6px 10px; font-size: var(--text-xs, 0.75rem); display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                            <i class="fas fa-video"></i> Kamera Langsung
                        </button>
                        <button type="button" class="btn btn-outline btn-photo-tab" id="btnTabUnggah" style="flex: 1; padding: 6px 10px; font-size: var(--text-xs, 0.75rem); display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                            <i class="fas fa-file-image"></i> Unggah Gambar
                        </button>
                    </div>

                    {{-- Panel 1: Kamera Langsung --}}
                    <div id="panelKamera" style="display: block;">
                        <div id="videoContainer" style="position: relative; width: 100%; max-width: 380px; margin: 0 auto; aspect-ratio: 4/3; background: #000; border-radius: var(--radius-md, 8px); overflow: hidden; display: flex; align-items: center; justify-content: center;">
                            <video id="webcamVideo" playsinline autoplay muted style="width: 100%; height: 100%; object-fit: cover; display: none;"></video>
                            <div id="cameraPlaceholder" style="text-align: center; color: var(--text-muted); padding: 20px;">
                                <i class="fas fa-video-slash" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.5;"></i>
                                <div style="font-size: var(--text-xs, 0.75rem);">Kamera belum aktif</div>
                                <button type="button" id="btnStartCamera" class="btn btn-primary" style="margin-top: 10px; padding: 6px 14px; font-size: var(--text-xs, 0.75rem); display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-camera"></i> Aktifkan Kamera
                                </button>
                            </div>
                        </div>

                        <div id="cameraControls" style="display: none; justify-content: center; gap: 8px; margin-top: 10px; flex-wrap: wrap;">
                            <button type="button" id="btnSwitchCamera" class="btn btn-secondary" style="padding: 6px 12px; font-size: var(--text-xs, 0.75rem); display: inline-flex; align-items: center; gap: 5px;" title="Ganti Kamera Depan / Belakang">
                                <i class="fas fa-camera-rotate"></i> Putar Kamera
                            </button>
                            <button type="button" id="btnCapturePhoto" class="btn btn-primary" style="padding: 6px 16px; font-size: var(--text-xs, 0.75rem); font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fas fa-circle-dot"></i> Jepret Foto
                            </button>
                            <button type="button" id="btnStopCamera" class="btn btn-secondary" style="padding: 6px 12px; font-size: var(--text-xs, 0.75rem); display: inline-flex; align-items: center; gap: 5px;">
                                <i class="fas fa-stop"></i> Matikan
                            </button>
                        </div>
                    </div>

                    {{-- Panel 2: Unggah Berkas --}}
                    <div id="panelUnggah" style="display: none;">
                        <div style="border: 1px dashed var(--border-glass, rgba(255,255,255,0.25)); border-radius: var(--radius-md, 8px); padding: 18px 14px; text-align: center; background: rgba(255,255,255,0.02);">
                            <i class="fas fa-cloud-arrow-up" style="font-size: 1.8rem; color: var(--primary, #0ea5e9); margin-bottom: 6px;"></i>
                            <div style="font-size: var(--text-xs, 0.78rem); font-weight: 600; color: var(--text-heading); margin-bottom: 2px;">
                                Pilih atau Seret Foto Kunjungan
                            </div>
                            <div style="font-size: var(--text-2xs, 0.7rem); color: var(--text-muted); margin-bottom: 10px;">
                                Format JPG/PNG, dikompresi otomatis &lt; 200 KB
                            </div>
                            <input type="file" id="inputFileFoto" accept="image/*" class="input-field" style="font-size: var(--text-xs, 0.75rem); max-width: 280px; margin: 0 auto;">
                        </div>
                    </div>

                    {{-- Preview Hasil Foto Terpilih / Terjepret --}}
                    <div id="photoPreviewWrap" style="display: none; margin-top: 12px; text-align: center;">
                        <div style="position: relative; display: inline-block; max-width: 260px; border-radius: var(--radius-md, 8px); overflow: hidden; border: 2px solid #10b981; box-shadow: 0 4px 12px rgba(16,185,129,0.2);">
                            <img id="imgPhotoPreview" src="" alt="Preview Foto Tamu" style="width: 100%; height: auto; display: block; object-fit: cover;">
                            <span style="position: absolute; top: 6px; left: 6px; background: rgba(16, 185, 129, 0.9); color: #fff; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px;">
                                <i class="fas fa-check"></i> Siap Disimpan
                            </span>
                        </div>
                        <div style="margin-top: 8px;">
                            <button type="button" id="btnHapusFoto" class="btn btn-outline" style="color: var(--danger, #ef4444); border-color: rgba(239,68,68,0.3); font-size: var(--text-2xs, 0.72rem); padding: 4px 12px; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="fas fa-trash"></i> Hapus / Ganti Foto
                            </button>
                        </div>
                    </div>

                    {{-- Hidden storage for compressed base64 image data --}}
                    <input type="hidden" name="foto_tamu_data" id="foto_tamu_data" value="">
                </div>
                </div>{{-- /#formFields --}}

                <button type="submit" class="btn btn-primary" id="btnSubmitTamu"
                    style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px; margin-top: 10px; padding: 0.85rem 1.25rem; font-weight: 700;">
                    <i class="fas fa-paper-plane"></i> Kirim Buku Tamu Digital
                </button>
            </form>
        </div>

        {{-- Footer Sederhana --}}
        <div style="text-align: center; margin-top: 1.5rem; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
            &copy; {{ date('Y') }} {{ $sekolah->nama ?? 'Sistem Aplikasi Edukasi' }} &bull; Pos Keamanan Gerbang
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .btn-photo-tab.active {
            background: var(--primary, #0ea5e9) !important;
            color: #fff !important;
            border-color: var(--primary, #0ea5e9) !important;
        }
        .category-step {
            border: 2px solid rgba(14, 165, 233, 0.45);
            background: linear-gradient(180deg, rgba(14, 165, 233, 0.12), rgba(14, 165, 233, 0.03));
            border-radius: var(--radius-xl, 16px);
            padding: 16px;
            margin-bottom: 1.25rem;
            position: relative;
            box-shadow: 0 8px 24px rgba(14, 165, 233, 0.15);
        }
        .category-step.answered {
            border-color: rgba(16, 185, 129, 0.5);
            background: linear-gradient(180deg, rgba(16, 185, 129, 0.10), rgba(16, 185, 129, 0.02));
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.12);
        }
        .category-step-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }
        .category-step-num {
            flex: 0 0 auto;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: var(--text-md, 1rem);
            color: #fff;
            background: linear-gradient(135deg, #0ea5e9, #06b6d4);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);
        }
        .category-step-title {
            font-weight: 800;
            font-size: var(--text-md, 1rem);
            color: var(--text-heading);
            line-height: 1.2;
        }
        .category-step-sub {
            font-size: var(--text-xs, 0.75rem);
            color: var(--text-muted);
            margin-top: 2px;
        }
        .category-step-tag {
            margin-left: auto;
            align-self: flex-start;
            font-size: var(--text-2xs, 0.65rem);
            font-weight: 800;
            letter-spacing: 0.06em;
            color: #fff;
            background: linear-gradient(135deg, #f59e0b, #f97316);
            padding: 4px 10px;
            border-radius: var(--radius-full, 999px);
            white-space: nowrap;
        }
        .etamu-category-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        @media (min-width: 520px) {
            .etamu-category-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        .category-option {
            cursor: pointer;
            border: 2px solid var(--border-glass, rgba(255, 255, 255, 0.12));
            border-radius: var(--radius-lg, 12px);
            padding: 14px 10px;
            text-align: center;
            transition: all 0.18s ease;
            background: var(--card-bg, rgba(255, 255, 255, 0.04));
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            position: relative;
            user-select: none;
        }
        .category-option:hover {
            border-color: var(--primary, #0ea5e9);
            transform: translateY(-2px);
            background: rgba(14, 165, 233, 0.08);
        }
        .category-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
            pointer-events: none;
        }
        .category-option.active {
            border-color: var(--primary, #0ea5e9);
            background: rgba(14, 165, 233, 0.16);
            color: var(--primary, #0ea5e9);
            font-weight: 700;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(14, 165, 233, 0.25);
        }
        .category-option.active::after {
            content: "\f058";
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Pro", "FontAwesome";
            font-weight: 900;
            position: absolute;
            top: 6px;
            right: 8px;
            font-size: var(--text-md, 0.95rem);
            color: var(--primary, #0ea5e9);
        }
        .category-icon {
            font-size: var(--text-2xl, 1.6rem);
            line-height: 1;
            margin-bottom: 2px;
        }
        .category-label {
            font-size: var(--text-xs, 0.8rem);
            line-height: 1.2;
            font-weight: 700;
            color: var(--text-heading);
        }
        .category-option.active .category-label {
            color: var(--primary, #0ea5e9);
        }
        .category-hint {
            font-size: var(--text-2xs, 0.68rem);
            color: var(--text-muted);
            line-height: 1.1;
        }
        .category-warning {
            margin-top: 12px;
            font-size: var(--text-xs, 0.78rem);
            font-weight: 700;
            color: #b45309;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: var(--radius-md, 10px);
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .etamu-fields-locked {
            opacity: 0.45;
            pointer-events: none;
            filter: grayscale(0.3);
            transition: opacity 0.2s ease;
        }
        .category-step.shaking {
            animation: etamu-shake 0.45s ease;
        }
        @keyframes etamu-shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-8px); }
            40% { transform: translateX(8px); }
            60% { transform: translateX(-5px); }
            80% { transform: translateX(5px); }
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
        .student-search-wrapper {
            position: relative;
            margin-bottom: 6px;
        }
        .student-search-input-wrap {
            position: relative;
        }
        .student-search-spinner {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary, #0ea5e9);
        }
        .student-dropdown-results {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            background: var(--card-bg, #1e293b);
            border: 1.5px solid var(--primary, #0ea5e9);
            border-radius: var(--radius-lg, 12px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.35);
            z-index: 1000;
            max-height: 240px;
            overflow-y: auto;
        }
        .student-search-item {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-glass, rgba(255, 255, 255, 0.08));
            cursor: pointer;
            transition: background 0.15s ease;
            text-align: left;
            width: 100%;
            display: block;
            background: none;
            border-left: none;
            border-right: none;
            border-top: none;
            color: inherit;
        }
        .student-search-item:last-child {
            border-bottom: none;
        }
        .student-search-item:hover, .student-search-item:focus {
            background: rgba(14, 165, 233, 0.15);
            outline: none;
        }
        .student-item-main {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 700;
            font-size: var(--text-sm, 0.85rem);
            color: var(--text-heading, #fff);
        }
        .student-item-meta {
            font-size: var(--text-xs, 0.72rem);
            color: var(--text-muted, #94a3b8);
            margin-top: 2px;
        }
        .student-selected-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.05));
            border: 1.5px solid rgba(16, 185, 129, 0.4);
            border-radius: var(--radius-lg, 12px);
            padding: 10px 14px;
        }
        .student-selected-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .student-selected-info {
            flex: 1;
        }
        .student-selected-name {
            font-weight: 800;
            font-size: var(--text-sm, 0.875rem);
            color: var(--text-heading, #fff);
        }
        .student-selected-sub {
            font-size: var(--text-xs, 0.72rem);
            color: var(--text-muted, #94a3b8);
        }
        .btn-clear-student {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: none;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: background 0.15s ease;
        }
        .btn-clear-student:hover {
            background: rgba(239, 68, 68, 0.25);
        }
    </style>
@endsection

@section('scripts')
    <script>
        window.ETAMU_CONFIG = {
            checkStudentUrl: "{{ route('tamu.check-student') }}"
        };
    </script>
    <script src="{{ asset('js/tamu-mandiri.js') }}?v={{ time() }}"></script>
@endsection
