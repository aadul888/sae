@if ($errors->any())
    {{-- Peta error validasi untuk JS: arahkan pengguna langsung ke field terkait. --}}
    <script type="application/json" id="saeFormErrors">@json($errors->messages())</script>
@endif
