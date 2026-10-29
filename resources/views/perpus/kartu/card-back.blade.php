{{--
    SISI BELAKANG KARTU ANGGOTA PERPUSTAKAAN (Portrait 56mm x 87mm).
    Memuat capaian literasi anggota, ketentuan keanggotaan, barcode, dan kontak perpustakaan.
--}}
<div class="kp-card kp-card-back pk-theme" id="pk-card-back-{{ $card['nomor_anggota'] }}">
    <div class="kp-bg-decor kp-bg-decor-back"></div>

    <div class="kp-back-header">
        <div class="kp-back-title">Kartu Anggota Perpustakaan</div>
        <div class="kp-back-school">{{ $card['sekolah']['nama'] }}</div>
    </div>

    <div class="kp-divider kp-divider-back"></div>

    <div class="kp-back-content">
        <div class="pk-level-block">
            <div class="pk-level-head">
                <span><i class="fas {{ $card['level']['icon'] }}"></i> {{ $card['level']['label'] }}</span>
                <span>{{ number_format($card['level']['poin'], 0, ',', '.') }} poin</span>
            </div>
            <div class="pk-level-bar">
                <span style="width:{{ $card['level']['progress'] }}%;background:{{ $card['level']['warna'] }};"></span>
            </div>
            <div class="pk-level-note">
                @if ($card['level']['sisa'] > 0)
                    {{ $card['level']['sisa'] }} poin lagi menuju level {{ $card['level']['next_label'] }}.
                @else
                    Level tertinggi &mdash; Duta Literasi.
                @endif
            </div>
        </div>

        <div class="kp-terms-title">KETENTUAN ANGGOTA:</div>
        <ol class="kp-terms-list">
            <li>Kartu ini bukti sah keanggotaan perpustakaan sekolah.</li>
            <li>Wajib dibawa saat meminjam, mengembalikan buku, dan mengisi buku kunjungan.</li>
            <li>QR &amp; barcode dipindai petugas untuk transaksi peminjaman dan pengembalian.</li>
            <li>Kembalikan buku tepat waktu; keterlambatan dikenakan denda sesuai ketentuan.</li>
            <li>Jaga koleksi. Kerusakan / kehilangan menjadi tanggung jawab peminjam.</li>
            <li>Kartu tidak boleh dipindahtangankan. Bila hilang, segera lapor pustakawan.</li>
        </ol>
    </div>

    <div class="kp-back-bottom">
        <div class="kp-barcode-box">
            <div class="kp-barcode-svg">
                {!! $card['barcode_svg'] !!}
            </div>
            <div class="kp-barcode-text">* {{ $card['nomor_anggota'] }} *</div>
        </div>

        <div class="kp-back-contact">
            <div><i class="fas fa-map-marker-alt"></i> {{ Str::limit($card['sekolah']['alamat'], 58) }}</div>
            <div>
                @if (!empty($card['sekolah']['telepon']) && $card['sekolah']['telepon'] !== '-')
                    <span><i class="fas fa-phone"></i> {{ $card['sekolah']['telepon'] }}</span>
                @endif
                @if (!empty($card['sekolah']['website']))
                    <span style="margin-left:6px;"><i class="fas fa-globe"></i>
                        {{ str_replace(['http://', 'https://'], '', $card['sekolah']['website']) }}</span>
                @endif
            </div>
        </div>
    </div>
</div>
