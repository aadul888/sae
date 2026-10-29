{{--
    Cetak kartu anggota perpustakaan massal.
    Tiap lembar A4 landscape memuat 5 pasang (5 depan di atas, 5 belakang di bawah)
    dengan urutan sejajar agar hasil potong presisi. Parameter: $cards (array kartu).
--}}
@extends('layouts.cetak')

@section('title', 'Cetak Kartu Anggota Massal')
@section('print_text', 'Cetak Semua Kartu')

@section('styles')
    <link rel="stylesheet"
        href="{{ asset('css/kartu-pelajar.css') }}?v={{ file_exists(public_path('css/kartu-pelajar.css')) ? filemtime(public_path('css/kartu-pelajar.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/perpus-kartu.css') }}?v={{ file_exists(public_path('css/perpus-kartu.css')) ? filemtime(public_path('css/perpus-kartu.css')) : time() }}">
@endsection

@section('content')
    @php $chunks = collect($cards)->chunk(5); @endphp

    @if ($chunks->isNotEmpty())
        <div class="kp-sheet-wrapper">
            @foreach ($chunks as $idx => $chunk)
                <div class="kp-a4-sheet" data-page="{{ $idx + 1 }}">
                    <div class="kp-sheet-row kp-row-front">
                        @foreach ($chunk as $card)
                            @include('perpus.kartu.card-front', ['card' => $card])
                        @endforeach
                    </div>

                    <div class="kp-sheet-cut-gap">
                        <div class="kp-cut-line"></div>
                    </div>

                    <div class="kp-sheet-row kp-row-back">
                        @foreach ($chunk as $card)
                            @include('perpus.kartu.card-back', ['card' => $card])
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div style="text-align:center;padding:48px;background:#fff;border-radius:var(--radius-lg,12px);max-width:560px;margin:80px auto;">
            <i class="fas fa-id-card" style="font-size:40px;color:var(--text-muted,#94a3b8);"></i>
            <h3 style="font-size:var(--text-lg,1.125rem);color:#1e293b;margin:12px 0 6px;">Tidak Ada Anggota untuk Dicetak</h3>
            <p style="font-size:var(--text-base,0.875rem);color:var(--text-muted,#64748b);margin:0;">
                Daftarkan anggota terlebih dahulu, atau pilih anggota tertentu lewat parameter <code>ids</code>.
            </p>
        </div>
    @endif
@endsection
