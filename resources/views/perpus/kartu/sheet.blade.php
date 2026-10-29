{{--
    Halaman cetak kartu anggota perpustakaan (satu anggota: sisi depan + belakang).
    Presisi A4 landscape seperti kartu pelajar. Dipakai dashboard pustakawan
    maupun portal anggota (kartu saya). Parameter: $card (array dari
    PerpustakaanMemberService::kartuData()).
--}}
@extends('layouts.cetak')

@section('title', 'Kartu Anggota Perpustakaan — ' . $card['nama'])
@section('print_text', 'Cetak Kartu Anggota')

@section('styles')
    <link rel="stylesheet"
        href="{{ asset('css/kartu-pelajar.css') }}?v={{ file_exists(public_path('css/kartu-pelajar.css')) ? filemtime(public_path('css/kartu-pelajar.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/perpus-kartu.css') }}?v={{ file_exists(public_path('css/perpus-kartu.css')) ? filemtime(public_path('css/perpus-kartu.css')) : time() }}">
@endsection

@section('content')
    <div class="kp-sheet-wrapper">
        <div class="kp-a4-sheet" data-page="1">
            <div class="kp-sheet-row kp-row-front kp-row-single">
                @include('perpus.kartu.card-front', ['card' => $card])
            </div>

            <div class="kp-sheet-cut-gap">
                <div class="kp-cut-line"></div>
            </div>

            <div class="kp-sheet-row kp-row-back kp-row-single">
                @include('perpus.kartu.card-back', ['card' => $card])
            </div>
        </div>
    </div>
@endsection
