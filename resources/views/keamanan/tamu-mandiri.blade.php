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

            <form id="formPublicTamu" action="{{ route('tamu.submit') }}" method="POST" novalidate>
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
                            <span class="category-hint">Mantan siswa</span>
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
                        <span style="font-weight: 700; font-size: var(--text-xs, 0.75rem); color: var(--primary, #0ea5e9);">
                            <i class="fas fa-id-card me-1"></i> Deteksi Otomatis Siswa
                        </span>
                        <span style="font-size: var(--text-2xs, 0.7rem); color: var(--text-muted);">Masukkan NIK Anda atau NISN Siswa</span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px;">
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
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 8px;">
                            <input type="text" name="nama_siswa" id="input_manual_nama_siswa" class="input-field" placeholder="Nama Siswa">
                            <input type="text" name="rombel_siswa" id="input_manual_rombel_siswa" class="input-field" placeholder="Kelas / Rombel">
                        </div>
                    </div>
                </div>

                {{-- Identitas Tamu --}}
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-top: 10px;">
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

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px;">
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

@push('styles')
    <style>
        .category-step {
            border: 2px solid rgba(14, 165, 233, 0.35);
            background: linear-gradient(180deg, rgba(14, 165, 233, 0.10), rgba(14, 165, 233, 0.02));
            border-radius: var(--radius-xl, 16px);
            padding: 16px;
            margin-bottom: 1.25rem;
            position: relative;
            box-shadow: 0 6px 24px rgba(14, 165, 233, 0.12);
        }
        .category-step.answered {
            border-color: rgba(16, 185, 129, 0.4);
            background: linear-gradient(180deg, rgba(16, 185, 129, 0.08), rgba(16, 185, 129, 0.02));
            box-shadow: 0 6px 24px rgba(16, 185, 129, 0.1);
        }
        .category-step-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }
        .category-step-num {
            flex: 0 0 auto;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.05rem;
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
            font-size: 0.65rem;
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
            border: 2px solid var(--border-glass, rgba(255, 255, 255, 0.14));
            border-radius: var(--radius-lg, 12px);
            padding: 14px 10px;
            text-align: center;
            transition: all 0.18s ease;
            background: var(--card-bg, rgba(255, 255, 255, 0.05));
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
            position: relative;
        }
        .category-option:hover {
            border-color: var(--primary, #0ea5e9);
            transform: translateY(-2px);
        }
        .category-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .category-option.active {
            border-color: var(--primary, #0ea5e9);
            background: rgba(14, 165, 233, 0.14);
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
            font-size: 0.95rem;
            color: var(--primary, #0ea5e9);
        }
        .category-icon {
            font-size: 1.5rem;
        }
        .category-label {
            font-size: var(--text-xs, 0.78rem);
            line-height: 1.2;
            font-weight: 700;
        }
        .category-hint {
            font-size: 0.65rem;
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
