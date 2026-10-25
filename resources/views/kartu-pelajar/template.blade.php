{{-- 
    Template Kartu Pelajar Digital SAE — FORMAT PORTRAIT (ISO CR-80: 54mm x 85.6mm)
    Halaman Depan:
    - Logo sekolah (kiri) & Logo SAE berwarna (kanan)
    - Logo Jurusan transparan sebagai latar belakang badan kartu pelajar
    - Pasfoto peserta didik terpusat
    - Nama, NISN & Status Aktif
    - QR Code scan direct verifikasi online di bagian bawah
    Halaman Belakang:
    - Ketentuan pemegang kartu, Barcode NISN, Kontak resmi sekolah
    - Bersih tanpa TTD dan stempel
--}}

<div class="kp-card-pair {{ $wrapperClass ?? '' }}">
    @include('kartu-pelajar.card-front', ['card' => $card])
    @include('kartu-pelajar.card-back', ['card' => $card])
</div>
