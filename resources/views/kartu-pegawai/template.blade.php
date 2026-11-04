{{-- 
    Template Kartu Pegawai Digital SAE — FORMAT PORTRAIT (ISO CR-80: 54mm x 85.6mm)
--}}

<div class="kp-card-pair {{ $wrapperClass ?? '' }}">
    @include('kartu-pegawai.card-front', ['card' => $card])
    @include('kartu-pegawai.card-back', ['card' => $card])
</div>
