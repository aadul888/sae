{{--
    SISI DEPAN KARTU ANGGOTA PERPUSTAKAAN (Portrait 56mm x 87mm).
    Struktur memakai kelas .kp-* agar presisi cetak sama dengan kartu pelajar;
    palet warna ditimpa oleh .pk-theme (lihat public/css/perpus-kartu.css).
--}}
<div class="kp-card kp-card-front pk-theme" id="pk-card-front-{{ $card['nomor_anggota'] }}">
    <div class="kp-bg-decor"></div>

    @if (!empty($card['sekolah']['logo_url']))
        <div class="kp-card-watermark" title="{{ $card['sekolah']['nama'] }}">
            <img src="{{ $card['sekolah']['logo_url'] }}" alt="Watermark Sekolah">
        </div>
    @endif

    <div class="kp-header">
        <div class="kp-header-left">
            <img src="{{ $card['sekolah']['logo_url'] ?: asset('img/logo-icon.png') }}" alt="Logo Sekolah"
                class="kp-school-logo">
            <div class="kp-school-title">
                <span class="kp-card-type">Kartu Anggota Perpustakaan</span>
                <span class="kp-school-name">{{ $card['sekolah']['nama'] }}</span>
            </div>
        </div>
        <div class="kp-header-right">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Logo SAE" class="kp-sae-logo" title="Sistem Aplikasi Edukasi">
        </div>
    </div>

    <div class="kp-divider"></div>

    <div class="kp-body-portrait">
        <div class="kp-photo-box">
            @if (!empty($card['foto_url']))
                <img src="{{ $card['foto_url'] }}" alt="Foto {{ $card['nama'] }}" class="kp-student-photo">
            @else
                <div class="kp-photo-placeholder">
                    <span style="font-size:17pt;font-weight:800;">{{ $card['inisial'] }}</span>
                    <span>FOTO</span>
                </div>
            @endif
        </div>

        <div class="kp-student-info">
            <div class="kp-student-name" title="{{ $card['nama'] }}">{{ $card['nama'] }}</div>

            <div class="kp-nisn-pill" title="Status keanggotaan: {{ $card['status'] }}">
                <i class="fas fa-circle-check" style="color:var(--success,#10b981);font-size:5.5pt;"></i>
                <span class="kp-nisn-value">{{ $card['nomor_anggota'] }}</span>
            </div>

            <div class="kp-rombel-jurusan">
                <span class="kp-rombel-name">{{ $card['tipe_label'] }}</span>
                @if (!empty($card['rombel_atau_unit']))
                    <span class="kp-bullet">&bull;</span>
                    <span class="kp-jurusan-name">{{ $card['rombel_atau_unit'] }}</span>
                @endif
            </div>

            <div class="pk-level-pill" style="color:{{ $card['level']['warna'] }};"
                title="Level literasi {{ $card['level']['label'] }}">
                <i class="fas {{ $card['level']['icon'] }}"></i>
                {{ $card['level']['label'] }} &bull; {{ number_format($card['level']['poin'], 0, ',', '.') }} poin
            </div>
        </div>

        <div class="kp-qrcode-box" title="Pindai QR untuk verifikasi &amp; transaksi peminjaman">
            <div class="kp-qr-wrapper">
                {!! $card['qr_code_svg'] !!}
            </div>
            <div class="kp-qr-sublabel"><i class="fas fa-qrcode"></i> Pindai Sirkulasi</div>
        </div>
    </div>

    <div class="kp-footer-strip">
        <span>Berlaku s/d {{ $card['masa_berlaku'] ?: 'Tidak ditentukan' }}</span>
        <span>SAE Digital Library</span>
    </div>
</div>
