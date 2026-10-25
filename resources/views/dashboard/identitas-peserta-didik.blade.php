@extends('layouts.dashboard')

@section('title', 'Identitas Lengkap Peserta Didik — SAE')
@section('dash_title', 'Identitas Peserta Didik')

@section('content')
    @php
        $photoUrl = $studentPhoto ?? ($pd->foto_url ?? null);
        $statusKonfirmasi = $identitas->status_konfirmasi ?? 'belum_konfirmasi';
    @endphp

    {{-- Switcher Siswa Khusus Administrator & Tim Kesiswaan --}}
    @if (in_array($userRole, ['admin', 'tendik'], true) && !empty($allPdList) && $allPdList->isNotEmpty())
        <div class="card"
            style="margin-bottom: 20px; padding: 12px 18px; border-radius: var(--radius-lg, 12px); background: rgba(99,102,241,0.06); border: 1px dashed rgba(99,102,241,0.3);">
            <form method="GET" action="{{ route('dashboard.identitas.index') }}"
                style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div
                    style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-user-shield"></i> Mode Pengelola: Pilih Siswa untuk Meninjau Formulir Lengkap
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <select name="peserta_didik_id" onchange="this.form.submit()" class="form-select"
                        style="font-size: var(--text-sm, 0.8125rem); height: 36px; border-radius: var(--radius-md, 8px); min-width: 260px;">
                        @foreach ($allPdList as $item)
                            <option value="{{ $item->peserta_didik_id }}"
                                {{ $pd && $pd->peserta_didik_id === $item->peserta_didik_id ? 'selected' : '' }}>
                                {{ $item->nama }} ({{ $item->nama_rombel ?: 'Tanpa Kelas' }}) - NISN: {{ $item->nisn }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    @endif

    <!-- Alert Notifikasi Flash -->
    @if (session('success'))
        <div class="alert alert-success"
            style="padding: 12px 16px; border-radius: var(--radius-lg, 12px); margin-bottom: 20px; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-circle-check" style="font-size: var(--text-lg, 1.125rem);"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger"
            style="padding: 12px 16px; border-radius: var(--radius-lg, 12px); margin-bottom: 20px; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-circle-exclamation" style="font-size: var(--text-lg, 1.125rem);"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($pd)
        <!-- 1. Header Banner Profil Siswa -->
        <div class="dash-banner"
            style="margin-bottom: 24px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.12) 0%, rgba(6, 182, 212, 0.08) 100%); border: 1px solid rgba(99, 102, 241, 0.25); display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; border-radius: var(--radius-xl, 16px); padding: 20px 24px;">
            <div style="display: flex; align-items: center; gap: 18px; flex: 1; min-width: 0;">
                @if ($photoUrl)
                    <div
                        style="flex-shrink: 0; width: 84px; height: 110px; display: flex; align-items: center; justify-content: center; background: transparent; border-radius: var(--radius-lg, 12px); overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.18);">
                        <img src="{{ $photoUrl }}" alt="{{ $pd->nama }}"
                            style="width: 100%; height: 100%; object-fit: cover;"
                            onerror="this.style.display='none'; this.parentElement.style.display='none';">
                    </div>
                @else
                    <div
                        style="flex-shrink: 0; width: 70px; height: 70px; border-radius: var(--radius-xl, 16px); background: rgba(99,102,241,0.15); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-3xl, 1.875rem);">
                        <i class="fas fa-id-card"></i>
                    </div>
                @endif

                <div style="flex: 1; min-width: 0;">
                    <div style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-muted); margin-bottom: 2px;">
                        Formulir Identitas Resmi Siswa &bull; Dapodik 2027 Rev. 1
                    </div>
                    <h2
                        style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin: 0 0 6px 0; line-height: 1.25;">
                        {{ $identitas->nama ?: $pd->nama }}
                    </h2>
                    <div
                        style="display: flex; align-items: center; gap: 12px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); flex-wrap: wrap; margin-bottom: 8px;">
                        <span><i class="fas fa-id-card text-primary me-1"></i> NISN:
                            <strong>{{ $identitas->nisn ?: ($pd->nisn ?: '-') }}</strong></span>
                        <span><i class="fas fa-fingerprint text-info me-1"></i> NIK:
                            <strong>{{ $identitas->nik ?: ($pd->nik ?: '-') }}</strong></span>
                        <span><i class="fas fa-school text-warning me-1"></i> Rombel:
                            <strong>{{ $pd->nama_rombel ?: '-' }}</strong></span>
                        @if ($waliKelas)
                            <span><i class="fas fa-chalkboard-user text-success me-1"></i> Wali Kelas:
                                <strong>{{ $waliKelas['nama'] }}</strong></span>
                        @endif
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <div
                            style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25); border-radius: var(--radius-2xl, 20px); font-size: var(--text-xs, 0.75rem); font-weight: 700; color: #10b981;">
                            <i class="fas fa-circle-check"></i> Peserta Didik Aktif
                        </div>
                        <div
                            style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2); border-radius: var(--radius-2xl, 20px); font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--primary);">
                            <i class="fas fa-shield-halved"></i> Data Tersimpan Aman (Tidak Tertimpa Dapodik)
                        </div>
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                @if ($userRole === 'orang_tua')
                    <a href="{{ route('dashboard.orang-tua') }}" class="btn btn-outline"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); text-decoration: none;"
                        title="Beranda Orang Tua">
                        <i class="fas fa-house"></i>
                    </a>
                @elseif ($userRole === 'peserta_didik')
                    <a href="{{ route('dashboard.peserta-didik') }}" class="btn btn-outline"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); text-decoration: none;"
                        title="Beranda Siswa">
                        <i class="fas fa-house"></i>
                    </a>
                @else
                    <a href="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'usulan']) }}"
                        class="btn btn-outline"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); text-decoration: none;"
                        title="Review Kesiswaan">
                        <i class="fas fa-file-pen text-primary"></i>
                    </a>
                @endif

                @if (!empty($pd->nisn))
                    <button type="button" class="btn btn-primary" onclick="openKartuPelajarModal('{{ $pd->nisn }}')"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                        title="Kartu Pelajar Digital">
                        <i class="fas fa-qrcode"></i>
                    </button>
                @endif
            </div>
        </div>

        <!-- 2. Progress Stepper Progres Usulan Perubahan Data -->
        @php
            $step1Done = $statusKonfirmasi !== 'belum_konfirmasi';
            $step2Done = $berkasPrereq['is_valid'];
            $pendingUsulanCount = $usulanList->where('status', 'menunggu')->count();
            $approvedUsulanCount = $usulanList->where('status', 'disetujui')->count();
            $dapodikUsulanCount = $usulanList->where('status', 'sudah_ke_dapodik')->count();
            $step3Done = $usulanList->isNotEmpty() || $statusKonfirmasi === 'sesuai';
            $step4Done =
                ($usulanList->isNotEmpty() && $pendingUsulanCount === 0) ||
                in_array($statusKonfirmasi, ['sesuai', 'diverifikasi', 'disinkronkan_dapodik'], true);
            $step5Done = $statusKonfirmasi === 'disinkronkan_dapodik' || $dapodikUsulanCount > 0;
        @endphp

        <div class="card" style="margin-bottom: 24px; padding: 20px; border-radius: var(--radius-xl, 16px);">
            <div
                style="font-size: var(--text-base, 0.875rem); font-weight: 800; color: var(--text-color); margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-diagram-project text-primary"></i> Progres Usulan &amp; Verifikasi Data Siswa
                </div>
                <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-weight: normal;">
                    Pantau alur verifikasi data dan status update ke aplikasi Dapodik
                </span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px;">
                <!-- Step 1 -->
                <div
                    style="background: var(--bg-hover); padding: 12px 14px; border-radius: var(--radius-lg, 12px); border: 1.5px solid {{ $step1Done ? '#10b981' : '#f59e0b' }}; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div
                            style="font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Tahap 1</div>
                        <div style="font-size: var(--text-base, 0.875rem); font-weight: 800; color: var(--text-color); margin: 3px 0;">
                            Konfirmasi Data</div>
                    </div>
                    <div style="margin-top: 8px;">
                        @if ($statusKonfirmasi === 'sesuai')
                            <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                    class="fas fa-check me-1"></i> Data Sesuai</span>
                        @elseif ($statusKonfirmasi === 'perlu_perbaikan')
                            <span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                    class="fas fa-check me-1"></i> Ada Perubahan</span>
                        @else
                            <span class="badge badge-outline"
                                style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; color: #f59e0b;"><i
                                    class="fas fa-clock me-1"></i> Belum Konfirmasi</span>
                        @endif
                    </div>
                </div>

                <!-- Step 2 -->
                <div
                    style="background: var(--bg-hover); padding: 12px 14px; border-radius: var(--radius-lg, 12px); border: 1.5px solid {{ $step2Done ? '#10b981' : ($statusKonfirmasi === 'sesuai' ? 'var(--border-color)' : '#ef4444') }}; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div
                            style="font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Tahap 2</div>
                        <div style="font-size: var(--text-base, 0.875rem); font-weight: 800; color: var(--text-color); margin: 3px 0;">Berkas
                            KK &amp; Ijazah</div>
                    </div>
                    <div style="margin-top: 8px;">
                        @if ($step2Done)
                            <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                    class="fas fa-shield-halved me-1"></i> Lengkap &amp; Valid</span>
                        @elseif ($statusKonfirmasi === 'sesuai')
                            <span class="badge badge-outline" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                    class="fas fa-minus me-1"></i> Tidak Diperlukan</span>
                        @else
                            <span class="badge badge-danger" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                    class="fas fa-triangle-exclamation me-1"></i> Wajib Valid</span>
                        @endif
                    </div>
                </div>

                <!-- Step 3 -->
                <div
                    style="background: var(--bg-hover); padding: 12px 14px; border-radius: var(--radius-lg, 12px); border: 1.5px solid {{ $step3Done ? '#10b981' : ($isFormEditable ? '#f59e0b' : 'var(--border-color)') }}; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div
                            style="font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Tahap 3</div>
                        <div style="font-size: var(--text-base, 0.875rem); font-weight: 800; color: var(--text-color); margin: 3px 0;">Usulan
                            Perubahan</div>
                    </div>
                    <div style="margin-top: 8px;">
                        @if ($statusKonfirmasi === 'sesuai')
                            <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                    class="fas fa-check me-1"></i> Data Sah Sesuai</span>
                        @elseif ($usulanList->isNotEmpty())
                            <span class="badge badge-primary" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                    class="fas fa-paper-plane me-1"></i> {{ $usulanList->count() }} Terkirim</span>
                        @elseif ($isFormEditable)
                            <span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                    class="fas fa-lock-open me-1"></i> Form Terbuka</span>
                        @else
                            <span class="badge badge-outline" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                    class="fas fa-lock me-1"></i> Form Terkunci</span>
                        @endif
                    </div>
                </div>

                <!-- Step 4 -->
                <div
                    style="background: var(--bg-hover); padding: 12px 14px; border-radius: var(--radius-lg, 12px); border: 1.5px solid {{ $step4Done ? '#10b981' : ($pendingUsulanCount > 0 ? '#f59e0b' : 'var(--border-color)') }}; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div
                            style="font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Tahap 4</div>
                        <div style="font-size: var(--text-base, 0.875rem); font-weight: 800; color: var(--text-color); margin: 3px 0;">Review
                            Kesiswaan</div>
                    </div>
                    <div style="margin-top: 8px;">
                        @if ($statusKonfirmasi === 'sesuai')
                            <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                    class="fas fa-check me-1"></i> Terverifikasi</span>
                        @elseif ($pendingUsulanCount > 0)
                            <span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                    class="fas fa-clock me-1"></i> Menunggu ({{ $pendingUsulanCount }})</span>
                        @elseif ($approvedUsulanCount > 0)
                            <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                    class="fas fa-check me-1"></i> Disetujui</span>
                        @else
                            <span class="badge badge-outline" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                    class="fas fa-minus me-1"></i> Menunggu Usulan</span>
                        @endif
                    </div>
                </div>

                <!-- Step 5 -->
                <div
                    style="background: var(--bg-hover); padding: 12px 14px; border-radius: var(--radius-lg, 12px); border: 1.5px solid {{ $step5Done ? '#10b981' : ($approvedUsulanCount > 0 ? 'var(--primary)' : 'var(--border-color)') }}; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div
                            style="font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Tahap 5</div>
                        <div style="font-size: var(--text-base, 0.875rem); font-weight: 800; color: var(--text-color); margin: 3px 0;">Update
                            ke Dapodik</div>
                    </div>
                    <div style="margin-top: 8px;">
                        @if ($step5Done)
                            <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                    class="fas fa-cloud-arrow-up me-1"></i> Selesai Di-update</span>
                        @elseif ($approvedUsulanCount > 0)
                            <span class="badge badge-primary" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                    class="fas fa-hourglass-half me-1"></i> Siap Input Dapodik</span>
                        @elseif ($statusKonfirmasi === 'sesuai')
                            <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                    class="fas fa-circle-check me-1"></i> Sah di Dapodik</span>
                        @else
                            <span class="badge badge-outline" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i
                                    class="fas fa-cloud me-1"></i> Belum Di-update</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Status Konfirmasi Siswa & Quick Action Card -->
        <div class="card"
            style="margin-bottom: 24px; padding: 20px 24px; border-radius: var(--radius-xl, 16px); border: 1.5px solid {{ $statusKonfirmasi === 'sesuai' ? '#10b981' : ($statusKonfirmasi === 'perlu_perbaikan' ? ($berkasPrereq['is_valid'] ? 'var(--primary)' : '#ef4444') : '#f59e0b') }}; background: var(--card-bg);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                <div style="flex: 1; min-width: 280px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px; flex-wrap: wrap;">
                        <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0;">
                            Status Verifikasi &amp; Konfirmasi Identitas
                        </h3>
                        @if ($statusKonfirmasi === 'sesuai')
                            <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 4px 10px;">
                                <i class="fas fa-circle-check me-1"></i> Data Telah Sesuai (Terkonfirmasi &amp; Terkunci)
                            </span>
                        @elseif ($statusKonfirmasi === 'diverifikasi')
                            <span class="badge"
                                style="background: rgba(99,102,241,0.15); color: var(--primary); font-size: var(--text-xs, 0.75rem); padding: 4px 10px; border: 1px solid rgba(99,102,241,0.3);">
                                <i class="fas fa-badge-check me-1"></i> Terverifikasi oleh Tim Kesiswaan
                            </span>
                        @elseif ($statusKonfirmasi === 'disinkronkan_dapodik')
                            <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 4px 10px;">
                                <i class="fas fa-cloud-arrow-up me-1"></i> Selesai Di-update ke Dapodik
                            </span>
                        @elseif ($statusKonfirmasi === 'perlu_perbaikan')
                            @if ($berkasPrereq['is_valid'])
                                <span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem); padding: 4px 10px;">
                                    <i class="fas fa-lock-open me-1"></i> Formulir Terbuka: Mode Usulan Perubahan
                                </span>
                            @else
                                <span class="badge badge-danger" style="font-size: var(--text-xs, 0.75rem); padding: 4px 10px;">
                                    <i class="fas fa-lock me-1"></i> Formulir Terkunci: Wajib KK &amp; Ijazah Valid
                                </span>
                            @endif
                        @else
                            <span class="badge"
                                style="background: rgba(245,158,11,0.15); color: #f59e0b; font-size: var(--text-xs, 0.75rem); padding: 4px 10px; border: 1px solid rgba(245,158,11,0.3);">
                                <i class="fas fa-lock me-1"></i> Belum Dikonfirmasi (Formulir Terkunci)
                            </span>
                        @endif
                    </div>
                    <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 0; line-height: 1.5;">
                        @if ($statusKonfirmasi === 'sesuai')
                            Dikonfirmasi pada
                            {{ $identitas->dikonfirmasi_pada ? $identitas->dikonfirmasi_pada->translatedFormat('d F Y, H:i') : '-' }}
                            WIB oleh {{ $identitas->dikonfirmasi_oleh ?: 'Siswa' }}. Seluruh data terkunci aman.
                        @elseif ($statusKonfirmasi === 'perlu_perbaikan')
                            @if ($berkasPrereq['is_valid'])
                                Berkas Kartu Keluarga dan Ijazah SMP telah berstatus <strong>Valid / Sesuai</strong>.
                                Formulir sekarang <strong>terbuka</strong>. Silakan periksa dan perbaiki kolom data di
                                bawah, lalu klik <strong>"Simpan &amp; Ajukan Usulan Perubahan Data"</strong>.
                            @else
                                Anda menyatakan terdapat ketidaksesuaian data identitas. Namun pengisian usulan perubahan
                                <strong>hanya terbuka jika dokumen Kartu Keluarga dan Ijazah SMP telah divalidasi
                                    Valid</strong> oleh Tim Kesiswaan.
                            @endif
                        @elseif ($statusKonfirmasi === 'disinkronkan_dapodik')
                            Seluruh usulan perubahan data Anda telah diverifikasi oleh tim kesiswaan dan berhasil
                            disinkronkan ke aplikasi Dapodik Kemendikbudristek.
                        @else
                            Secara default, formulir identitas ini <strong>terkunci</strong> untuk mencegah manipulasi data.
                            Peserta didik <strong>diwajibkan melakukan konfirmasi terlebih dahulu</strong> dengan memilih
                            apakah data saat ini sudah sesuai atau memerlukan perbaikan.
                        @endif
                    </p>
                </div>

                <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                    @if (!$berkasPrereq['is_valid'] && $statusKonfirmasi === 'perlu_perbaikan')
                        <a href="{{ route('dashboard.berkas.index') }}" class="btn btn-primary"
                            style="padding: 9px 18px; font-size: var(--text-base, 0.875rem); font-weight: 700; border-radius: var(--radius-md, 8px); display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-folder-open"></i> Upload Berkas (KK &amp; Ijazah)
                        </a>
                    @endif

                    <button type="button"
                        class="btn {{ $statusKonfirmasi === 'belum_konfirmasi' ? 'btn-primary' : 'btn-outline' }} btn-konfirmasi-dialog"
                        style="padding: 9px 18px; font-size: var(--text-base, 0.875rem); font-weight: 700; border-radius: var(--radius-md, 8px); display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-clipboard-check"></i>
                        {{ $statusKonfirmasi === 'belum_konfirmasi' ? 'Konfirmasi Data Siswa' : 'Ubah Pilihan Konfirmasi' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Form Konfirmasi Tersembunyi (Disubmit via SweetAlert Dialog) -->
        <form id="formActionKonfirmasi" action="{{ route('dashboard.identitas.konfirmasi') }}" method="POST"
            style="display: none;">
            @csrf
            <input type="hidden" name="peserta_didik_id" value="{{ $pd->peserta_didik_id }}">
            <input type="hidden" name="status" id="inputStatusKonfirmasi" value="sesuai">
            <input type="hidden" name="catatan_siswa" id="inputCatatanKonfirmasi" value="">
        </form>

        <!-- 4. Navigation Tabs Filter Seksi Formulir -->
        <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
            <div class="periode-nav-desktop" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" class="btn btn-primary identitas-tab-btn active" data-target="all"
                    style="padding: 8px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; border-radius: var(--radius-md, 8px);">
                    <i class="fas fa-list me-1"></i> Tampilkan Semua Bagian
                </button>
                <button type="button" class="btn btn-outline identitas-tab-btn" data-target="pribadi_alamat"
                    style="padding: 8px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; border-radius: var(--radius-md, 8px);">
                    <i class="fas fa-user me-1"></i> Data Pribadi &amp; Domisili
                </button>
                <button type="button" class="btn btn-outline identitas-tab-btn" data-target="orang_tua"
                    style="padding: 8px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; border-radius: var(--radius-md, 8px);">
                    <i class="fas fa-people-roof me-1"></i> Orang Tua &amp; Wali
                </button>
                <button type="button" class="btn btn-outline identitas-tab-btn" data-target="registrasi_kesejahteraan"
                    style="padding: 8px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; border-radius: var(--radius-md, 8px);">
                    <i class="fas fa-address-book me-1"></i> Kontak, Pendaftaran &amp; Kesejahteraan
                </button>
                <button type="button" class="btn btn-outline identitas-tab-btn" data-target="prestasi_minat"
                    style="padding: 8px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; border-radius: var(--radius-md, 8px);">
                    <i class="fas fa-trophy me-1"></i> Prestasi &amp; Minat
                </button>
                <button type="button" class="btn btn-outline identitas-tab-btn" data-target="periodik"
                    style="padding: 8px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; border-radius: var(--radius-md, 8px);">
                    <i class="fas fa-ruler-combined me-1"></i> Data Periodik
                </button>
            </div>
        </div>

        <!-- 5. FORMULIR IDENTITAS LENGKAP PESERTA DIDIK -->
        <form action="{{ route('dashboard.identitas.update') }}" method="POST" id="formIdentitasPesertaDidik">
            @csrf
            <input type="hidden" name="peserta_didik_id" value="{{ $pd->peserta_didik_id }}">

            <fieldset {{ $isFormLocked ? 'disabled' : '' }}
                style="border: none; padding: 0; margin: 0; min-inline-size: auto;">

                @include('dashboard.identitas-peserta-didik.partials.section-pribadi-alamat')
                @include('dashboard.identitas-peserta-didik.partials.section-orang-tua-wali')
                @include('dashboard.identitas-peserta-didik.partials.section-registrasi-kesejahteraan')
                @include('dashboard.identitas-peserta-didik.partials.section-prestasi-periodik')
            <!-- ========================================== -->
            <!-- CATATAN USULAN & SUBMIT BUTTON -->
            <!-- ========================================== -->
            <div class="card"
                style="margin-bottom: 24px; padding: 22px 24px; border-radius: var(--radius-xl, 16px); background: var(--card-bg); border: 1.5px solid var(--border-color);">
                <div style="margin-bottom: 16px;">
                    <label class="form-label" style="font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color);">
                        <i class="fas fa-message me-1 text-primary"></i> Catatan Tambahan atau Alasan Perubahan (Opsional)
                    </label>
                    <textarea name="catatan_siswa" class="form-control" rows="2" {{ $isFormLocked ? 'disabled' : '' }}
                        placeholder="Tuliskan keterangan singkat jika ada data yang diperbaiki..." style="font-size: var(--text-base, 0.875rem);">{{ old('catatan_siswa', $identitas->catatan_siswa) }}</textarea>
                </div>

                <div
                    style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                        @if ($isFormLocked)
                            <i class="fas fa-lock text-warning me-1"></i> Formulir dalam kondisi terkunci. Klik tombol
                            konfirmasi untuk membuka pengajuan usulan.
                        @else
                            <i class="fas fa-lock-open text-success me-1"></i> Formulir terbuka. Perubahan data akan
                            diteruskan ke Tim Kesiswaan untuk diverifikasi.
                        @endif
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        @if ($isFormLocked)
                            <button type="button" class="btn btn-outline btn-konfirmasi-dialog"
                                style="padding: 10px 20px; font-weight: 700; font-size: var(--text-base, 0.875rem); border-radius: var(--radius-md, 8px); display: inline-flex; align-items: center; gap: 8px; color: #f59e0b; border-color: rgba(245,158,11,0.4);">
                                <i class="fas fa-lock"></i> Formulir Terkunci (Buka / Konfirmasi)
                            </button>
                        @else
                            <button type="submit" class="btn btn-primary"
                                style="padding: 10px 22px; font-weight: 700; font-size: var(--text-base, 0.875rem); border-radius: var(--radius-md, 8px); display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fas fa-floppy-disk"></i> Simpan &amp; Ajukan Usulan Perubahan Data
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </form>

        <!-- 5. Riwayat Usulan Perubahan Siswa -->
        @if ($usulanList->isNotEmpty())
            <div class="card table-responsive-stack" style="margin-bottom: 24px; padding: 0;">
                <div
                    style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <h3
                        style="font-size: var(--text-md, 1rem); font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-clock-rotate-left text-primary"></i> Riwayat Pengajuan Usulan Revisi Data
                    </h3>
                    <span class="badge badge-outline" style="font-size: var(--text-xs, 0.75rem);">{{ $usulanList->count() }}
                        Pengajuan</span>
                </div>

                <div style="overflow-x: auto;">
                    <table class="table table-pd mb-0"
                        style="width: 100%; border-collapse: collapse; font-size: var(--text-sm, 0.8125rem);">
                        <thead>
                            <tr style="background: var(--bg-hover); border-bottom: 1px solid var(--border-color);">
                                <th
                                    style="padding: 10px 14px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Waktu</th>
                                <th
                                    style="padding: 10px 14px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Kolom Data</th>
                                <th
                                    style="padding: 10px 14px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Nilai Lama</th>
                                <th
                                    style="padding: 10px 14px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Usulan Nilai Baru</th>
                                <th
                                    style="padding: 10px 14px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Status Review Kesiswaan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($usulanList as $u)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 10px 14px;">
                                        {{ $u->created_at ? $u->created_at->format('d/m/Y H:i') : '-' }}</td>
                                    <td style="padding: 10px 14px;"><strong
                                            style="text-transform: uppercase; font-family: monospace;">{{ str_replace('_', ' ', $u->kolom_perubahan) }}</strong>
                                    </td>
                                    <td style="padding: 10px 14px; color: var(--text-muted);">
                                        {{ $u->nilai_lama ?: '-' }}
                                    </td>
                                    <td style="padding: 10px 14px; font-weight: 700; color: var(--primary);">
                                        {{ $u->nilai_baru }}</td>
                                    <td style="padding: 10px 14px;">
                                        @if ($u->status === 'disetujui')
                                            <span class="badge badge-success"
                                                style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;"><i
                                                    class="fas fa-check me-1"></i>DISETUJUI</span>
                                        @elseif ($u->status === 'ditolak')
                                            <span class="badge badge-danger"
                                                style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;"><i
                                                    class="fas fa-times me-1"></i>DITOLAK</span>
                                        @else
                                            <span class="badge badge-warning"
                                                style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;"><i
                                                    class="fas fa-clock me-1"></i>MENUNGGU REVIEW</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @else
        <div style="padding: 40px 16px; text-align: center; color: var(--text-muted);">
            <i class="fas fa-user-xmark mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.4; display: block;"></i>
            <div>Data peserta didik tidak ditemukan atau Anda belum login sebagai peserta didik / wali murid.</div>
        </div>
    @endif

    <!-- MODAL KONFIRMASI KEBENARAN DATA IDENTITAS (THEME MATCHED & ICON DRIVEN) -->
    <div id="modalKonfirmasiData" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.72); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="card"
            style="max-width: 440px; width: 92%; border-radius: var(--radius-xl, 16px); padding: 22px; margin: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1.5px solid var(--border-color); background: var(--card-bg);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div
                        style="width: 36px; height: 36px; border-radius: var(--radius-lg, 12px); background: rgba(99,102,241,0.14); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem);">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div>
                        <h3 style="font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color); margin: 0;">Konfirmasi
                            Identitas</h3>
                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">Pilih status kebenaran
                            data saat ini</div>
                    </div>
                </div>
                <button type="button" class="close-modal" data-target="#modalKonfirmasiData"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem); padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Pilihan 2 Tombol Tile Berukuran Besar dengan Icon Utama -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <!-- Opsi 1: Data Sesuai -->
                <button type="button" class="btn btn-tile-konfirmasi" data-status="sesuai"
                    style="background: rgba(16, 185, 129, 0.08); border: 2px solid rgba(16, 185, 129, 0.35); border-radius: var(--radius-lg, 12px); padding: 18px 12px; text-align: center; cursor: pointer; transition: all 0.2s ease; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div
                        style="width: 46px; height: 46px; border-radius: 50%; background: rgba(16, 185, 129, 0.15); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem);">
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <div style="font-size: var(--text-base, 0.875rem); font-weight: 800; color: #10b981;">Data Sesuai</div>
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); line-height: 1.3;">Data benar &amp; terkunci
                    </div>
                </button>

                <!-- Opsi 2: Belum Sesuai -->
                <button type="button" class="btn btn-tile-konfirmasi" data-status="perlu_perbaikan"
                    style="background: rgba(245, 158, 11, 0.08); border: 2px solid rgba(245, 158, 11, 0.35); border-radius: var(--radius-lg, 12px); padding: 18px 12px; text-align: center; cursor: pointer; transition: all 0.2s ease; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div
                        style="width: 46px; height: 46px; border-radius: 50%; background: rgba(245, 158, 11, 0.15); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem);">
                        <i class="fas fa-pen-to-square"></i>
                    </div>
                    <div style="font-size: var(--text-base, 0.875rem); font-weight: 800; color: #f59e0b;">Perlu Perbaikan</div>
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); line-height: 1.3;">Buka usulan perubahan
                    </div>
                </button>
            </div>

            <!-- Input Catatan Ringkas -->
            <div style="margin-bottom: 16px;">
                <label
                    style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 4px;">
                    <i class="fas fa-message me-1"></i> Catatan (Opsional):
                </label>
                <input type="text" id="inputCatatanKonfirmasiModal" class="form-control form-control-sm"
                    placeholder="Tuliskan keterangan jika ada..."
                    style="font-size: var(--text-sm, 0.8125rem); height: 36px; border-radius: var(--radius-md, 8px); width: 100%;">
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="button" class="btn btn-outline close-modal" data-target="#modalKonfirmasiData"
                    style="padding: 6px 16px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px);">
                    <i class="fas fa-times me-1"></i> Batal
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Pratinjau Kartu Pelajar Digital (Layar Penuh, Bisa Digeser) -->
    @include('kartu-pelajar.modal-fullscreen')
@endsection

@push('styles')
    <link rel="stylesheet"
        href="{{ asset('css/kartu-pelajar.css') }}?v={{ file_exists(public_path('css/kartu-pelajar.css')) ? filemtime(public_path('css/kartu-pelajar.css')) : '1' }}">
@endpush

@push('scripts')
    <script
        src="{{ asset('js/identitas-peserta-didik.js') }}?v={{ file_exists(public_path('js/identitas-peserta-didik.js')) ? filemtime(public_path('js/identitas-peserta-didik.js')) : time() }}">
    </script>
@endpush
