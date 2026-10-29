@extends('layouts.cetak')

@section('title', 'Kartu Anggota Perpustakaan — ' . $member->nama)

@section('content')
    <div class="paper" style="max-width:520px;">
        @include('partials.kop-surat')

        <div style="text-align:center;margin:12px 0 18px;">
            <h2 style="margin:0;font-size:13pt;font-weight:800;text-transform:uppercase;">Kartu Anggota Perpustakaan</h2>
        </div>

        <div style="border:2px solid #0f172a;border-radius:14px;padding:20px;display:flex;gap:22px;align-items:center;">
            <div style="background:#fff;border:1px solid #cbd5e1;border-radius:10px;padding:8px;line-height:0;">
                {!! $qrSvg !!}
            </div>
            <div style="flex:1;">
                <div style="font-size:9pt;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Nomor Anggota</div>
                <div style="font-family:monospace;font-size:16pt;font-weight:800;color:#be185d;letter-spacing:2px;">{{ $member->nomor_anggota }}</div>
                <hr style="margin:10px 0;border:none;border-top:1px solid #e2e8f0;">
                <table style="font-size:10pt;line-height:1.6;">
                    <tr><td style="font-weight:700;padding-right:10px;">Nama</td><td>: {{ $member->nama }}</td></tr>
                    @if ($member->nisn_nip)<tr><td style="font-weight:700;padding-right:10px;">NISN/NIP</td><td>: {{ $member->nisn_nip }}</td></tr>@endif
                    <tr><td style="font-weight:700;padding-right:10px;">Tipe</td><td>: {{ ucfirst($member->tipe) }}</td></tr>
                    @if ($member->rombel_atau_unit)<tr><td style="font-weight:700;padding-right:10px;">Rombel/Unit</td><td>: {{ $member->rombel_atau_unit }}</td></tr>@endif
                    <tr><td style="font-weight:700;padding-right:10px;">Berlaku s/d</td><td>: {{ optional($member->tgl_kadaluarsa)->format('d/m/Y') ?: 'Tidak ditentukan' }}</td></tr>
                </table>
            </div>
        </div>

        <p style="font-size:8.5pt;color:#64748b;text-align:center;margin-top:16px;">
            Pindai QR untuk verifikasi keaslian kartu &bull; {{ config('app.name', 'SAE') }}
        </p>
    </div>
@endsection
