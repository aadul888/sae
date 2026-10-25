@extends('layouts.dashboard')

@section('title', 'Pengelolaan Kartu Pelajar & Pembayaran — SAE')
@section('dash_title', 'Kartu Pelajar')

@section('content')
    <!-- 1. Header Banner -->
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div
                style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                <i class="fas fa-address-card"></i>
            </div>
            <div>
                <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                    Pengelolaan Kartu Pelajar &amp; Pembayaran
                </h2>
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                    Pencatatan pembayaran, status cetak fisik RFID, serta pengiriman rincian administrasi via WhatsApp
                    ber-QR Code.
                </div>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-primary" onclick="openCetakRombelModal('{{ $rombel }}')"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                title="Cetak Masal per Rombel">
                <i class="fas fa-print"></i>
            </button>
            <button type="button" class="btn btn-outline" onclick="openModalCetakPengambilan()"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); border-color: var(--primary); color: var(--primary); background: var(--card-bg);"
                title="List Siap Diambil (Per Tgl Cetak)">
                <i class="fas fa-clipboard-check"></i>
            </button>
        </div>
    </div>

    <!-- 2. Flash Messages -->
    @if (session('success'))
        <div
            style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 12px 16px; border-radius: var(--radius-lg, 12px); margin-bottom: 18px; font-size: var(--text-base, 0.875rem); display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error') || $errors->any())
        <div
            style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 12px 16px; border-radius: var(--radius-lg, 12px); margin-bottom: 18px; font-size: var(--text-base, 0.875rem); display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ session('error') ?: $errors->first() }}</div>
        </div>
    @endif

    <!-- Segmented Navigation Tabs -->
    <div class="dash-segmented-tabs" style="margin-bottom: 20px;">
        <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'keuangan']) }}"
            class="tab-btn btn {{ $tab === 'keuangan' ? 'btn-primary active' : 'btn-outline' }}">
            <i class="fas fa-money-bill-wave me-1"></i> Catatan Keuangan
        </a>
        <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'request']) }}"
            class="tab-btn btn {{ $tab === 'request' ? 'btn-primary active' : 'btn-outline' }}">
            <i class="fas fa-paper-plane me-1"></i> Request Cetak
        </a>
        <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'berhasil-cetak']) }}"
            class="tab-btn btn {{ in_array($tab, ['berhasil-cetak', 'siswa']) ? 'btn-primary active' : 'btn-outline' }}">
            <i class="fas fa-id-card-clip me-1"></i> Berhasil Cetak
        </a>
        <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'harga']) }}"
            class="tab-btn btn {{ $tab === 'harga' ? 'btn-primary active' : 'btn-outline' }}">
            <i class="fas fa-tags me-1"></i> Harga Standar &amp; QRIS
        </a>
        <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'whatsapp']) }}"
            class="tab-btn btn {{ $tab === 'whatsapp' ? 'btn-primary active' : 'btn-outline' }}">
            <i class="fab fa-whatsapp me-1"></i> Pesan Otomatis WhatsApp
        </a>
        <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'garansi']) }}"
            class="tab-btn btn {{ $tab === 'garansi' ? 'btn-primary active' : 'btn-outline' }}">
            <i class="fas fa-shield-alt me-1"></i> Ketentuan &amp; Garansi
        </a>
    </div>

    @if ($tab === 'keuangan')
        @include('dashboard.kartu-pelajar.partials.tab-keuangan')
    @elseif ($tab === 'request')
        @include('dashboard.kartu-pelajar.partials.tab-request')
    @elseif (in_array($tab, ['siswa', 'berhasil-cetak', 'berhasil_cetak']))
        @include('dashboard.kartu-pelajar.partials.tab-berhasil-cetak')
    @elseif ($tab === 'harga')
        @include('dashboard.kartu-pelajar.partials.tab-harga')
    @elseif ($tab === 'whatsapp')
        @include('dashboard.kartu-pelajar.partials.tab-whatsapp')
    @elseif ($tab === 'garansi')
        @include('dashboard.kartu-pelajar.partials.tab-garansi')
    @endif

    @include('dashboard.kartu-pelajar.partials.modals')
@endsection
