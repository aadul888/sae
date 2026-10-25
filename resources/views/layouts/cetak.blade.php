<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Cetak Dokumen — SAE')</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/tokens.css') }}?v={{ file_exists(public_path('css/tokens.css')) ? filemtime(public_path('css/tokens.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
    @yield('styles')
    @stack('styles')
</head>

<body @yield('body_attrs')>

    @section('toolbar')
        <div class="no-print-bar">
            @yield('toolbar_left')

            @hasSection('back_url')
                <a href="@yield('back_url')" class="btn-action btn-back">
                    <i class="fas fa-arrow-left"></i> @yield('back_text', 'Kembali')
                </a>
            @else
                <button type="button" onclick="if(window.history.length > 1){window.history.back();}else{window.close();}"
                    class="btn-action btn-back">
                    <i class="fas fa-arrow-left"></i> @yield('back_text', 'Kembali')
                </button>
            @endif

            @yield('toolbar_middle')

            <button type="button" onclick="window.SAE_Print ? window.SAE_Print() : window.print()"
                class="btn-action btn-print">
                <i class="fas fa-print"></i> @yield('print_text', 'Cetak Dokumen (Print / PDF)')
            </button>

            @yield('toolbar_right')
        </div>
    @show

    @yield('content')

    <script
        src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}">
    </script>
    @yield('scripts')
    @stack('scripts')
</body>

</html>
