@extends('layouts.app')

@section('title', 'Verifikasi Kartu Anggota Perpustakaan')
@section('meta_description', 'Verifikasi keaslian kartu anggota Perpustakaan Digital sekolah melalui pemindaian QR.')

@section('content')
    <div class="container">
        <section class="hero" style="grid-template-columns:1fr;max-width:560px;margin:0 auto;">
            <div class="card-nisn" style="text-align:center;">
                @if (!$member)
                    <div style="font-size:3rem;color:var(--danger,#ef4444);margin-bottom:.5rem;">
                        <i class="fas fa-circle-xmark"></i>
                    </div>
                    <h1 class="hero-title" style="font-size:clamp(1.3rem,4vw,1.75rem);margin-bottom:.5rem;">Kartu Tidak
                        Ditemukan</h1>
                    <p style="color:var(--text-muted);font-size:var(--text-sm,0.8125rem);margin-bottom:1.5rem;">
                        Token kartu anggota tidak dikenali atau sudah tidak aktif. Hubungi pustakawan sekolah untuk
                        verifikasi ulang.
                    </p>
                    <a href="{{ route('perpustakaan.index') }}" class="btn btn-primary"
                        style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:8px;">
                        <i class="fas fa-book-open"></i> Ke Katalog Perpustakaan
                    </a>
                @else
                    <div style="font-size:2.75rem;color:var(--success,#10b981);margin-bottom:.35rem;">
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <h1 class="hero-title" style="font-size:clamp(1.3rem,4vw,1.75rem);margin-bottom:.35rem;">Kartu Anggota
                        Valid</h1>
                    <p style="color:var(--text-muted);font-size:var(--text-sm,0.8125rem);margin-bottom:1.25rem;">
                        Kartu ini terdaftar resmi sebagai anggota perpustakaan sekolah.
                    </p>

                    <div
                        style="display:inline-block;background:#fff;border-radius:var(--radius-lg,12px);padding:10px;margin-bottom:1.25rem;box-shadow:var(--shadow-md);line-height:0;">
                        {!! \App\Services\QrCodeService::generateSvg($member->verifyUrl(), 170, 1) !!}
                    </div>

                    <table class="table-minimal-compact" style="width:100%;text-align:left;">
                        <tbody>
                            <tr>
                                <th style="width:45%;">Nama</th>
                                <td>{{ $member->nama }}</td>
                            </tr>
                            <tr>
                                <th>Nomor Anggota</th>
                                <td style="font-family:var(--font-family-mono,monospace);font-weight:700;color:var(--primary);">
                                    {{ $member->nomor_anggota }}</td>
                            </tr>
                            <tr>
                                <th>Tipe</th>
                                <td>{{ ucfirst($member->tipe) }}</td>
                            </tr>
                            @if ($member->rombel_atau_unit)
                                <tr>
                                    <th>Rombel / Unit</th>
                                    <td>{{ $member->rombel_atau_unit }}</td>
                                </tr>
                            @endif
                            <tr>
                                <th>Status</th>
                                <td><span class="badge badge-success">Aktif</span></td>
                            </tr>
                            <tr>
                                <th>Berlaku s/d</th>
                                <td>{{ optional($member->tgl_kadaluarsa)->translatedFormat('d F Y') ?: 'Tidak ditentukan' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <a href="{{ route('perpustakaan.index') }}" class="btn btn-outline"
                        style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:8px;margin-top:1.25rem;">
                        <i class="fas fa-book-open"></i> Ke Katalog Perpustakaan
                    </a>
                @endif
            </div>
        </section>
    </div>
@endsection
