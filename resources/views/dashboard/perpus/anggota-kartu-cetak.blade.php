@extends('layouts.cetak')

@section('title', 'Cetak Kartu Anggota Massal')

@section('styles')
    <link rel="stylesheet"
        href="{{ asset('css/perpus-publik.css') }}?v={{ @filemtime(public_path('css/perpus-publik.css')) ?: '1' }}">
@endsection

@section('content')
    <div class="paper" style="max-width:1120px;" class2="landscape">
        @include('partials.kop-surat')
        <h2 style="text-align:center;margin:10px 0 16px;font-size:12pt;font-weight:800;text-transform:uppercase;">
            Kartu Anggota Perpustakaan ({{ $members->count() }} kartu)
        </h2>

        <div class="pp-cardsheet" style="display:flex;flex-wrap:wrap;gap:12px;">
            @forelse ($members as $m)
                <div style="width:340px;border:1px solid #94a3b8;border-radius:var(--radius-md);padding:10px;display:flex;gap:10px;align-items:center;page-break-inside:avoid;">
                    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:var(--radius-md);padding:4px;line-height:0;">
                        {!! $m->qr_svg !!}
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-family:monospace;font-size:9pt;font-weight:800;color:#be185d;">{{ $m->nomor_anggota }}</div>
                        <div style="font-size:10pt;font-weight:700;line-height:1.25;margin-top:2px;overflow:hidden;text-overflow:ellipsis;">{{ $m->nama }}</div>
                        <div style="font-size:8pt;color:#475569;margin-top:2px;">{{ ucfirst($m->tipe) }}{{ $m->rombel_atau_unit ? ' â€¢ ' . $m->rombel_atau_unit : '' }}</div>
                    </div>
                </div>
            @empty
                <p style="text-align:center;width:100%;color:#64748b;">Tidak ada anggota untuk dicetak.</p>
            @endforelse
        </div>
    </div>
@endsection
