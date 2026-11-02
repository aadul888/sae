<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Buku Tamu Digital (e-Tamu) — {{ $sekolah->nama ?? 'SAE' }}</title>
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sae.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components.css') }}">
    <style>
        body {
            background-color: var(--bg-surface, #f8fafc);
            color: var(--text-color, #1e293b);
            font-family: var(--font-family, system-ui, -apple-system, sans-serif);
            margin: 0;
            padding: var(--space-4, 8px);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }
        .etamu-wrapper {
            width: 100%;
            max-width: 680px;
            margin: var(--space-4, 8px) auto var(--space-8, 16px);
        }
        .etamu-header-card {
            background: #ffffff;
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: var(--radius-xl, 16px);
            padding: var(--space-6, 12px) var(--space-8, 16px);
            margin-bottom: var(--space-6, 12px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            gap: var(--space-6, 12px);
        }
        .etamu-logo {
            width: 52px;
            height: 52px;
            object-fit: contain;
            border-radius: var(--radius-md, 8px);
            background: rgba(2, 132, 199, 0.08);
            padding: 4px;
            flex-shrink: 0;
        }
        .etamu-header-info h1 {
            font-size: var(--text-base, 1rem);
            font-weight: 800;
            margin: 0;
            color: var(--text-heading, #0f172a);
            line-height: 1.25;
        }
        .etamu-header-info p {
            font-size: var(--text-xs, 0.75rem);
            color: var(--text-muted, #64748b);
            margin: 2px 0 0;
        }
        .etamu-card {
            background: #ffffff;
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: var(--radius-xl, 16px);
            padding: var(--space-8, 16px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
        }
        .category-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--space-3, 6px);
            margin-bottom: var(--space-6, 12px);
        }
        @media (min-width: 520px) {
            .category-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        .category-option {
            cursor: pointer;
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: var(--radius-lg, 12px);
            padding: var(--space-4, 8px) var(--space-3, 6px);
            text-align: center;
            transition: all 0.2s ease;
            background: var(--bg-surface, #f8fafc);
        }
        .category-option input[type="radio"] {
            display: none;
        }
        .category-option.active {
            border-color: var(--primary, #0284c7);
            background: rgba(2, 132, 199, 0.08);
            color: var(--primary, #0284c7);
            font-weight: 700;
        }
        .category-icon {
            font-size: var(--text-xl, 1.25rem);
            margin-bottom: 4px;
            display: block;
        }
        .category-label {
            font-size: var(--text-xs, 0.75rem);
            line-height: 1.2;
            display: block;
        }
        .student-lookup-box {
            background: rgba(2, 132, 199, 0.04);
            border: 1px dashed var(--primary, #0284c7);
            border-radius: var(--radius-lg, 12px);
            padding: var(--space-6, 12px);
            margin-bottom: var(--space-6, 12px);
        }
        .student-result-card {
            background: #ffffff;
            border: 1px solid rgba(16, 185, 129, 0.4);
            border-radius: var(--radius-md, 8px);
            padding: var(--space-4, 8px) var(--space-6, 12px);
            margin-top: var(--space-4, 8px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .student-result-info h4 {
            margin: 0;
            font-size: var(--text-sm, 0.8125rem);
            font-weight: 700;
            color: var(--text-heading, #0f172a);
        }
        .student-result-info p {
            margin: 2px 0 0;
            font-size: var(--text-xs, 0.75rem);
            color: var(--text-muted, #64748b);
        }
        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--space-4, 8px);
            margin-bottom: var(--space-4, 8px);
        }
        @media (min-width: 560px) {
            .form-grid-2 {
                grid-template-columns: 1fr 1fr;
            }
        }
        .form-group {
            margin-bottom: var(--space-4, 8px);
        }
        .form-group label {
            display: block;
            font-size: var(--text-xs, 0.75rem);
            font-weight: 700;
            margin-bottom: 4px;
            color: var(--text-color, #334155);
        }
        .form-group .form-control {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid var(--border-color, #cbd5e1);
            border-radius: var(--radius-md, 8px);
            padding: 9px 12px;
            font-size: var(--text-sm, 0.8125rem);
            background: #ffffff;
            color: var(--text-color, #1e293b);
        }
        .form-group .form-control:focus {
            outline: none;
            border-color: var(--primary, #0284c7);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        }
        .btn-submit-tamu {
            width: 100%;
            background: var(--primary, #0284c7);
            color: #ffffff;
            border: none;
            border-radius: var(--radius-lg, 12px);
            padding: 12px 20px;
            font-size: var(--text-base, 0.875rem);
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s;
            margin-top: var(--space-6, 12px);
        }
        .btn-submit-tamu:hover {
            background: #0369a1;
        }
        .btn-check-student {
            background: var(--primary, #0284c7);
            color: #ffffff;
            border: none;
            border-radius: var(--radius-md, 8px);
            padding: 8px 14px;
            font-size: var(--text-xs, 0.75rem);
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }
        .etamu-footer {
            text-align: center;
            font-size: var(--text-xs, 0.75rem);
            color: var(--text-muted, #94a3b8);
            margin-top: var(--space-6, 12px);
        }
    </style>
</head>
<body>

    <div class="etamu-wrapper">
        {{-- Header Sekolah --}}
        @php
            $schoolLogo = !empty($sekolahMeta->logo_url) ? $sekolahMeta->logo_url : (!empty($sekolah->logo_url) ? $sekolah->logo_url : asset('img/logo-icon.png'));
        @endphp
        <div class="etamu-header-card">
            <img src="{{ $schoolLogo }}" alt="Logo Sekolah" class="etamu-logo" onerror="this.onerror=null; this.src='{{ asset('img/logo-icon.png') }}';">
            <div class="etamu-header-info">
                <h1>{{ $sekolah->nama ?? 'Buku Tamu Digital' }}</h1>
                <p><i class="fas fa-shield-alt text-primary me-1"></i> Pos Keamanan &bull; Formulir Pengisian Kunjungan Mandiri (e-Tamu)</p>
            </div>
        </div>

        {{-- Main Form Card --}}
        <div class="etamu-card">
            <form id="formPublicTamu" action="{{ route('tamu.submit') }}" method="POST">
                @csrf
                <input type="hidden" name="peserta_didik_id" id="hidden_peserta_didik_id" value="">

                {{-- Pilih Kategori Tamu --}}
                <div class="form-group">
                    <label>Pilih Kategori Tamu <span style="color:red;">*</span></label>
                    <div class="category-grid">
                        <label class="category-option active" data-type="orang_tua">
                            <input type="radio" name="jenis_tamu" value="orang_tua" checked>
                            <span class="category-icon text-primary"><i class="fas fa-user-graduate"></i></span>
                            <span class="category-label">Orang Tua / Wali</span>
                        </label>
                        <label class="category-option" data-type="kedinasan">
                            <input type="radio" name="jenis_tamu" value="kedinasan">
                            <span class="category-icon text-info"><i class="fas fa-building"></i></span>
                            <span class="category-label">Kedinasan / Tamu Dinas</span>
                        </label>
                        <label class="category-option" data-type="umum">
                            <input type="radio" name="jenis_tamu" value="umum">
                            <span class="category-icon text-success"><i class="fas fa-user-friends"></i></span>
                            <span class="category-label">Umum / Masyarakat</span>
                        </label>
                        <label class="category-option" data-type="alumni">
                            <input type="radio" name="jenis_tamu" value="alumni">
                            <span class="category-icon text-warning"><i class="fas fa-user-tie"></i></span>
                            <span class="category-label">Alumni Sekolah</span>
                        </label>
                    </div>
                </div>

                {{-- Section Khusus Tamu Orang Tua (Deteksi Otomatis / Lookup) --}}
                <div id="sectionOrangTua" class="student-lookup-box">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-weight: 700; font-size: var(--text-xs, 0.75rem); color: var(--primary, #0284c7);">
                            <i class="fas fa-id-card me-1"></i> Deteksi Data Siswa (Putra/Putri Anda)
                        </span>
                        <span style="font-size: 11px; color: var(--text-muted);">Cukup masukkan NIK Anda atau NISN Siswa</span>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>NIK Anda (Orang Tua / Wali)</label>
                            <input type="text" id="input_nik_ortu" name="nik_tamu" class="form-control"
                                placeholder="16 digit NIK Orang Tua" maxlength="16" autocomplete="off">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Atau NISN / NIK Siswa</label>
                            <div style="display: flex; gap: 6px;">
                                <input type="text" id="input_nisn_siswa" name="nisn_siswa" class="form-control"
                                    placeholder="NISN atau NIK Siswa" maxlength="16" autocomplete="off">
                                <button type="button" id="btnCekSiswa" class="btn-check-student" title="Cek Otomatis">
                                    <i class="fas fa-search me-1"></i> Cek
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Container Hasil Deteksi Siswa --}}
                    <div id="studentDetectionResult" style="display: none;"></div>

                    {{-- Manual Input Siswa jika belum terdeteksi otomatis --}}
                    <div id="manualStudentInput" style="display: none; margin-top: 10px; border-top: 1px dashed var(--border-color); padding-top: 8px;">
                        <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 6px;">
                            <i class="fas fa-info-circle me-1"></i> Jika belum terdata di sistem, ketik nama siswa dan kelas:
                        </div>
                        <div class="form-grid-2">
                            <div>
                                <input type="text" name="nama_siswa" id="input_manual_nama_siswa" class="form-control" placeholder="Nama Siswa">
                            </div>
                            <div>
                                <input type="text" name="rombel_siswa" id="input_manual_rombel_siswa" class="form-control" placeholder="Kelas / Rombel (cth: X TKJ 1)">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Identitas Tamu --}}
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Nama Lengkap Tamu <span style="color:red;">*</span></label>
                        <input type="text" name="nama_tamu" id="tamu_nama" class="form-control"
                            placeholder="Ketik nama lengkap Anda" required>
                    </div>
                    <div class="form-group">
                        <label>Nomor WhatsApp / HP <span style="color:red;">*</span></label>
                        <input type="tel" name="nomor_kontak" id="tamu_kontak" class="form-control"
                            placeholder="08xxxxxxxxxx" required>
                    </div>
                </div>

                <div class="form-group" id="groupInstansi" style="display: none;">
                    <label>Instansi / Lembaga Asal</label>
                    <input type="text" name="instansi_asal" id="tamu_instansi" class="form-control"
                        placeholder="Contoh: Dinas Pendidikan, PT ABC, Umum">
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Tujuan Bertemu (Orang / Ruang) <span style="color:red;">*</span></label>
                        <input type="text" name="tujuan_bertemu" id="tamu_tujuan" list="listTujuan" class="form-control"
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
                    <div class="form-group">
                        <label>Keperluan Kunjungan <span style="color:red;">*</span></label>
                        <input type="text" name="keperluan" id="tamu_keperluan" list="listKeperluan" class="form-control"
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

                <div class="form-group">
                    <label>Nomor Polisi Kendaraan (Opsional)</label>
                    <input type="text" name="nomor_polisi_kendaraan" id="tamu_nopol" class="form-control"
                        placeholder="Contoh: B 1234 ABC / D 5678 XYZ">
                </div>

                <button type="submit" class="btn-submit-tamu" id="btnSubmitTamu">
                    <i class="fas fa-paper-plane"></i> Kirim Buku Tamu Digital
                </button>
            </form>
        </div>

        <div class="etamu-footer">
            &copy; {{ date('Y') }} {{ $sekolah->nama ?? 'Sistem Aplikasi Edukasi' }} &bull; e-Tamu Keamanan
        </div>
    </div>

    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script>
        window.ETAMU_CONFIG = {
            checkStudentUrl: "{{ route('tamu.check-student') }}"
        };
    </script>
    <script src="{{ asset('js/tamu-mandiri.js') }}?v={{ time() }}"></script>
</body>
</html>
