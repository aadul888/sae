@extends('layouts.app')

@section('title', 'Tiket Kunjungan Tamu (e-Tamu) — ' . ($sekolah->nama ?? 'Sekolah'))
@section('meta_description', 'Bukti registrasi kunjungan mandiri tamu sekolah dan tiket visitor digital pos keamanan.')

@section('content')
    <div class="container" style="max-width: 620px; padding-top: 30px; padding-bottom: 50px;">
        <div class="card-nisn" style="text-align: center;">
            <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(16, 185, 129, 0.12); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding: 6px 14px; border-radius: var(--radius-full, 999px); font-size: var(--text-xs, 0.75rem); font-weight: 700; margin-bottom: 12px;">
                <i class="fas fa-circle-check"></i> Kunjungan Tercatat Berhasil
            </div>

            <h1 style="font-size: clamp(1.2rem, 3vw, 1.5rem); font-weight: 900; color: var(--text-heading); margin: 0 0 6px;">
                Terima Kasih, {{ $tamu->nama_tamu }}
            </h1>
            <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 0 0 20px;">
                Data kunjungan Anda telah tersimpan secara resmi di sistem keamanan {{ $sekolah->nama ?? 'Sekolah' }}.
            </p>

            {{-- Box Detail Tiket --}}
            <div style="background: var(--card-bg, #1e293b); border: 2px dashed rgba(14, 165, 233, 0.35); border-radius: var(--radius-xl, 16px); padding: 18px 20px; margin-bottom: 20px; text-align: left; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px dashed var(--border-glass, rgba(255,255,255,0.12)); padding-bottom: 10px; margin-bottom: 12px; flex-wrap: wrap; gap: 6px;">
                    <span style="font-size: var(--text-xs, 0.72rem); color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">KODE TIKET VISITOR</span>
                    <span style="font-family: var(--font-family-mono, monospace); font-size: var(--text-md, 1.05rem); font-weight: 900; color: var(--primary, #0ea5e9); letter-spacing: 0.5px;">
                        #VIS-{{ date('Ymd', strtotime($tamu->tanggal)) }}-{{ str_pad($tamu->id, 4, '0', STR_PAD_LEFT) }}
                    </span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: var(--text-sm, 0.8rem); gap: 12px;">
                    <span style="color: var(--text-muted); font-weight: 600; flex-shrink: 0;">Tanggal &amp; Waktu Masuk:</span>
                    <span style="color: var(--text-heading); font-weight: 700; text-align: right;">
                        {{ \Carbon\Carbon::parse($tamu->tanggal)->translatedFormat('d F Y') }} &bull; {{ substr($tamu->jam_masuk, 0, 5) }} WIB
                    </span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: var(--text-sm, 0.8rem); gap: 12px;">
                    <span style="color: var(--text-muted); font-weight: 600; flex-shrink: 0;">Kategori Tamu:</span>
                    <span style="color: var(--text-heading); font-weight: 700; text-align: right;">
                        @if ($tamu->jenis_tamu === 'orang_tua')
                            <span class="badge" style="background: rgba(14, 165, 233, 0.15); color: #0ea5e9;">Orang Tua / Wali Siswa</span>
                        @elseif ($tamu->jenis_tamu === 'kedinasan')
                            <span class="badge" style="background: rgba(6, 182, 212, 0.15); color: var(--accent, #06b6d4);">Kedinasan / Instansi</span>
                        @elseif ($tamu->jenis_tamu === 'alumni')
                            <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">Alumni Sekolah</span>
                        @else
                            <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">Tamu Umum / Masyarakat</span>
                        @endif
                    </span>
                </div>

                @if (!empty($tamu->instansi_asal))
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: var(--text-sm, 0.8rem); gap: 12px;">
                        <span style="color: var(--text-muted); font-weight: 600; flex-shrink: 0;">Instansi Asal:</span>
                        <span style="color: var(--text-heading); font-weight: 700; text-align: right;">{{ $tamu->instansi_asal }}</span>
                    </div>
                @endif

                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: var(--text-sm, 0.8rem); gap: 12px;">
                    <span style="color: var(--text-muted); font-weight: 600; flex-shrink: 0;">Tujuan Bertemu:</span>
                    <span style="color: var(--text-heading); font-weight: 700; text-align: right;">{{ $tamu->tujuan_bertemu }}</span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: var(--text-sm, 0.8rem); gap: 12px;">
                    <span style="color: var(--text-muted); font-weight: 600; flex-shrink: 0;">Keperluan:</span>
                    <span style="color: var(--text-heading); font-weight: 700; text-align: right;">{{ $tamu->keperluan }}</span>
                </div>

                @if (!empty($tamu->nomor_polisi_kendaraan))
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: var(--text-sm, 0.8rem); gap: 12px;">
                        <span style="color: var(--text-muted); font-weight: 600; flex-shrink: 0;">No. Kendaraan:</span>
                        <span style="color: var(--text-heading); font-weight: 700; text-align: right;">{{ $tamu->nomor_polisi_kendaraan }}</span>
                    </div>
                @endif

                {{-- Siswa Terkait jika tamu Orang Tua --}}
                @if ($tamu->jenis_tamu === 'orang_tua' && (!empty($tamu->nama_siswa) || !empty($tamu->peserta_didik_id)))
                    <div style="background: rgba(14, 165, 233, 0.08); border: 1px solid rgba(14, 165, 233, 0.25); border-radius: var(--radius-md, 10px); padding: 10px 12px; margin-top: 10px;">
                        <div style="font-size: var(--text-xs, 0.72rem); color: var(--primary, #0ea5e9); font-weight: 700; margin-bottom: 2px;">
                            <i class="fas fa-child me-1"></i> Data Siswa Terkait:
                        </div>
                        <div style="font-weight: 800; font-size: var(--text-base, 0.88rem); color: var(--text-heading);">
                            {{ $tamu->nama_siswa ?? 'Siswa Terdaftar' }}
                        </div>
                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                            Kelas: <strong>{{ $tamu->rombel_siswa ?? '-' }}</strong>
                            @if (!empty($tamu->nisn_siswa))
                                &bull; NISN: {{ $tamu->nisn_siswa }}
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Instruksi Pos Satpam --}}
            <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.25); border-radius: var(--radius-lg, 12px); padding: 12px 14px; font-size: var(--text-xs, 0.75rem); color: var(--warning, #f59e0b); text-align: left; margin-bottom: 20px; display: flex; gap: 10px; align-items: flex-start;">
                <i class="fas fa-circle-info" style="font-size: var(--text-md, 1rem); margin-top: 2px; flex-shrink: 0;"></i>
                <div style="line-height: 1.4;">
                    <strong>Petunjuk Pos Keamanan:</strong><br>
                    Tunjukkan tiket digital ini atau sebutkan nama Anda kepada petugas Satpam di pos gerbang untuk mendapatkan <strong>Kartu Visitor Fisik</strong> dan konfirmasi ruang kunjungan.
                </div>
            </div>

            {{-- Action Buttons --}}
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button type="button" onclick="window.print()" class="btn btn-outline" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-print"></i> Cetak / Simpan PDF Tiket
                </button>
                <a href="{{ route('tamu.public') }}" class="btn btn-primary" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-plus"></i> Isi Kunjungan Tamu Baru
                </a>
                <a href="{{ url('/') }}" class="btn btn-outline" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-house"></i> Kembali ke Beranda SAE
                </a>
            </div>
        </div>
    </div>
@endsection
