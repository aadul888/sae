@extends('layouts.app')

@section('title', 'Profil Anggota — ' . $member->nama . ' | Perpustakaan Digital')
@section('meta_description', 'Kelola profil, kontak, password, dan kartu anggota digital Perpustakaan Sekolah.')

@section('content')
    <div class="container">
        @include('perpustakaan.partials.member-nav', ['member' => $member, 'aktif' => 'profil'])

        <section class="hero" style="grid-template-columns:1fr;padding-top:.5rem;padding-bottom:clamp(1rem,2.5vw,1.5rem);">
            <div>
                <span class="hero-kicker"><i class="fas fa-user-gear"></i> Profil Anggota</span>
                <h1 class="hero-title" style="font-size:clamp(1.4rem,3.5vw,2rem);">
                    Kelola <span>Data Keanggotaan</span>
                </h1>
                <p class="hero-desc">Perbarui kontak dan kata sandi Anda. Data identitas inti hanya dapat diubah oleh pustakawan.</p>
            </div>
        </section>

        <div class="charts-row" style="align-items:start;">
            {{-- ===== Kartu Identitas ===== --}}
            <div class="chart-box" style="padding:0;overflow:hidden;">
                <div class="analytics-head" style="padding:1.1rem 1.25rem;border-bottom:1px solid var(--border-glass);">
                    <div class="chart-box-title" style="margin:0;">
                        <i class="fas fa-id-card me-2 text-primary"></i>Identitas Keanggotaan
                    </div>
                </div>

                <div style="padding:1.25rem;">
                    <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;margin-bottom:1.25rem;">
                        <div
                            style="background:#fff;border-radius:var(--radius-lg,12px);padding:8px;line-height:0;
                                   box-shadow:0 6px 20px rgba(0,0,0,.14);">
                            {!! \App\Services\QrCodeService::generateSvg($member->verifyUrl(), 110, 1) !!}
                        </div>
                        <div style="flex:1;min-width:150px;">
                            <div style="font-weight:800;font-size:var(--text-base,.9375rem);color:var(--text-main);">
                                {{ $member->nama }}
                            </div>
                            <div
                                style="font-family:var(--font-family-mono,monospace);font-size:var(--text-sm,.8125rem);
                                       font-weight:800;letter-spacing:1.2px;color:var(--primary);margin-top:3px;">
                                {{ $member->nomor_anggota }}
                            </div>
                            <div style="margin-top:6px;display:flex;gap:.4rem;flex-wrap:wrap;">
                                <span class="badge badge-primary">{{ $member->tipeLabel() }}</span>
                                <span class="badge {{ $member->is_active ? 'badge-success' : 'badge-danger' }}">
                                    {{ $member->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                                @if ($member->auto_provisioned)
                                    <span class="badge badge-info">Otomatis</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @php
                        $baris = [
                            ['label' => 'NISN / NIP', 'value' => $member->nisn_nip ?: '-', 'icon' => 'fa-hashtag'],
                            ['label' => 'Rombel / Unit', 'value' => $member->rombel_atau_unit ?: '-', 'icon' => 'fa-school'],
                            ['label' => 'Jenis Kelamin', 'value' => $member->jenis_kelamin === 'L' ? 'Laki-laki' : ($member->jenis_kelamin === 'P' ? 'Perempuan' : ($member->jenis_kelamin ?: '-')), 'icon' => 'fa-venus-mars'],
                            ['label' => 'Tanggal Daftar', 'value' => optional($member->tgl_daftar)->translatedFormat('d F Y') ?: '-', 'icon' => 'fa-calendar-plus'],
                            ['label' => 'Berlaku s/d', 'value' => optional($member->tgl_kadaluarsa)->translatedFormat('d F Y') ?: 'Tidak ditentukan', 'icon' => 'fa-calendar-check'],
                            ['label' => 'Login Terakhir', 'value' => optional($member->last_login_at)->translatedFormat('d M Y, H:i') ?: 'Belum pernah', 'icon' => 'fa-clock-rotate-left'],
                        ];
                    @endphp

                    <div style="display:flex;flex-direction:column;gap:.1rem;">
                        @foreach ($baris as $b)
                            <div
                                style="display:flex;justify-content:space-between;align-items:center;gap:.85rem;
                                       padding:.65rem 0;border-bottom:1px dashed var(--border-glass);">
                                <span style="font-size:var(--text-xs,.75rem);color:var(--text-muted);font-weight:600;">
                                    <i class="fas {{ $b['icon'] }} me-2" style="opacity:.7;"></i>{{ $b['label'] }}
                                </span>
                                <span
                                    style="font-size:var(--text-sm,.8125rem);font-weight:700;color:var(--text-main);text-align:right;">
                                    {{ $b['value'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Poin & level --}}
                    <div
                        style="margin-top:1.25rem;padding:1rem;border-radius:var(--radius-lg,12px);
                               background:linear-gradient(135deg,rgba(59,130,246,.08),rgba(6,182,212,.06));
                               border:1px solid var(--border-glass);">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:8px;">
                            <span style="font-size:var(--text-sm,.8125rem);font-weight:800;color:{{ $level['warna'] }};">
                                <i class="fas {{ $level['icon'] }} me-2"></i>{{ $level['label'] }}
                            </span>
                            <span style="font-size:var(--text-xs,.75rem);color:var(--text-muted);font-weight:700;">
                                {{ number_format($level['poin'], 0, ',', '.') }} poin
                            </span>
                        </div>
                        <div style="height:8px;border-radius:var(--radius-pill);background:rgba(148,163,184,.22);overflow:hidden;">
                            <div
                                style="height:100%;width:{{ $level['progress'] }}%;border-radius:var(--radius-pill);
                                       background:linear-gradient(90deg,{{ $level['warna'] }},var(--accent,#06b6d4));">
                            </div>
                        </div>
                        <div style="font-size:var(--text-xs,.75rem);color:var(--text-muted);margin-top:7px;">
                            @if ($level['next'])
                                {{ number_format($level['sisa'], 0, ',', '.') }} poin lagi menuju
                                <strong>{{ $level['next_label'] }}</strong>.
                            @else
                                Anda telah mencapai level literasi tertinggi. Luar biasa!
                            @endif
                        </div>
                    </div>

                    <a href="{{ $member->verifyUrl() }}" target="_blank" rel="noopener" class="btn btn-outline"
                        style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:8px;margin-top:1rem;">
                        <i class="fas fa-shield-halved"></i> Buka Halaman Verifikasi Kartu
                    </a>
                </div>
            </div>

            {{-- ===== Form ===== --}}
            <div style="display:flex;flex-direction:column;gap:1.25rem;">
                {{-- Data kontak --}}
                <div class="chart-box" style="padding:0;overflow:hidden;">
                    <div class="analytics-head" style="padding:1.1rem 1.25rem;border-bottom:1px solid var(--border-glass);">
                        <div class="chart-box-title" style="margin:0;">
                            <i class="fas fa-address-book me-2 text-accent"></i>Data Kontak
                        </div>
                    </div>
                    <form method="POST" action="{{ route('perpustakaan.profil.update') }}" style="padding:1.25rem;">
                        @csrf
                        <div class="input-group">
                            <label class="input-label" for="ppTelepon">Nomor Telepon / WhatsApp</label>
                            <input type="text" id="ppTelepon" name="telepon" class="input-field"
                                value="{{ old('telepon', $member->telepon) }}" placeholder="08xxxxxxxxxx"
                                autocomplete="tel">
                            @error('telepon')
                                <div style="font-size:var(--text-xs,.75rem);color:var(--danger,#ef4444);margin-top:5px;">
                                    {{ $message }}</div>
                            @enderror
                        </div>
                        <div class="input-group">
                            <label class="input-label" for="ppEmail">Email</label>
                            <input type="email" id="ppEmail" name="email" class="input-field"
                                value="{{ old('email', $member->email) }}" placeholder="nama@sekolah.sch.id"
                                autocomplete="email">
                            @error('email')
                                <div style="font-size:var(--text-xs,.75rem);color:var(--danger,#ef4444);margin-top:5px;">
                                    {{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary"
                            style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:8px;">
                            <i class="fas fa-floppy-disk"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>

                {{-- Password: mengikuti akun SAE --}}
                <div class="chart-box" style="padding:0;overflow:hidden;">
                    <div class="analytics-head" style="padding:1.1rem 1.25rem;border-bottom:1px solid var(--border-glass);">
                        <div class="chart-box-title" style="margin:0;">
                            <i class="fas fa-lock me-2 text-warning"></i>Password Akun
                        </div>
                    </div>
                    <div style="padding:1.25rem;">
                        <div
                            style="padding:.9rem 1rem;border-radius:var(--radius-lg,12px);
                                   background:rgba(59,130,246,.06);border:1px solid var(--border-glass);
                                   font-size:var(--text-sm,.8125rem);color:var(--text-muted);line-height:1.6;">
                            <i class="fas fa-shield-halved text-primary me-2"></i>
                            @if ($member->tipe === 'umum')
                                Anda adalah <strong>anggota umum</strong>, sehingga password dikelola di sini.
                                Peserta didik dan GTK mengikuti password akun SAE.
                            @else
                                Password perpustakaan mengikuti <strong>password akun SAE</strong> Anda.
                                Untuk mengubahnya, gunakan menu ubah password pada portal SAE, lalu masuk kembali
                                ke perpustakaan dengan password baru tersebut.
                            @endif
                        </div>

                        @if ($member->tipe === 'umum')
                            <form method="POST" action="{{ route('perpustakaan.profil.password') }}"
                                style="margin-top:1rem;">
                                @csrf
                                <div class="input-group">
                                    <label class="input-label" for="ppPassLama">Password Saat Ini</label>
                                    <input type="password" id="ppPassLama" name="password_lama" class="input-field"
                                        placeholder="Password lama" autocomplete="current-password">
                                    @error('password_lama')
                                        <div style="font-size:var(--text-xs,.75rem);color:var(--danger,#ef4444);margin-top:5px;">
                                            {{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="input-group">
                                    <label class="input-label" for="ppPassBaru">Password Baru</label>
                                    <input type="password" id="ppPassBaru" name="password_baru" class="input-field"
                                        placeholder="Minimal 6 karakter" autocomplete="new-password">
                                    @error('password_baru')
                                        <div style="font-size:var(--text-xs,.75rem);color:var(--danger,#ef4444);margin-top:5px;">
                                            {{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="input-group">
                                    <label class="input-label" for="ppPassKonfirmasi">Konfirmasi Password Baru</label>
                                    <input type="password" id="ppPassKonfirmasi" name="password_baru_confirmation"
                                        class="input-field" placeholder="Ulangi password baru" autocomplete="new-password">
                                </div>
                                <button type="submit" class="btn btn-outline"
                                    style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:8px;">
                                    <i class="fas fa-key"></i> Ubah Password
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
