<!-- Progressive Web App (PWA) Standard Manifest & Meta Tags -->
<script>
    window.__SAE_PWA__ = {
        swUrl: "{{ url('sw.js', [], false) }}",
        scope: "{{ url('/', [], false) }}/",
        isSecure: window.isSecureContext || location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1'
    };
</script>
<link rel="manifest" href="{{ url('manifest.json', [], false) }}?v={{ @filemtime(public_path('manifest.json')) ?: '1' }}">
<link rel="alternate" type="application/manifest+json" href="{{ url('manifest.webmanifest', [], false) }}?v={{ @filemtime(public_path('manifest.webmanifest')) ?: '1' }}">

<!-- Explicit High-Resolution PWA Icons for Android Chrome & Homescreen -->
<link rel="icon" type="image/png" sizes="192x192" href="{{ url('img/icons/icon-192x192.png', [], false) }}?v={{ @filemtime(public_path('img/icons/icon-192x192.png')) ?: '1' }}">
<link rel="icon" type="image/png" sizes="512x512" href="{{ url('img/icons/icon-512x512.png', [], false) }}?v={{ @filemtime(public_path('img/icons/icon-512x512.png')) ?: '1' }}">

<!-- Android / Chrome / HarmonyOS -->
<meta name="mobile-web-app-capable" content="yes">
<meta name="application-name" content="SAE">
<meta name="theme-color" content="#0B0F19" id="pwaThemeColorMeta">

<!-- Apple iOS / Safari / iPadOS -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="SAE">
<link rel="apple-touch-icon" href="{{ asset('img/icons/apple-touch-icon.png') }}?v={{ @filemtime(public_path('img/icons/apple-touch-icon.png')) ?: '1' }}">
<link rel="apple-touch-icon" sizes="152x152" href="{{ asset('img/icons/icon-152x152.png') }}?v={{ @filemtime(public_path('img/icons/icon-152x152.png')) ?: '1' }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('img/icons/apple-touch-icon.png') }}?v={{ @filemtime(public_path('img/icons/apple-touch-icon.png')) ?: '1' }}">
<link rel="apple-touch-icon" sizes="192x192" href="{{ asset('img/icons/icon-192x192.png') }}?v={{ @filemtime(public_path('img/icons/icon-192x192.png')) ?: '1' }}">

<!-- Windows / Edge Tiles -->
<meta name="msapplication-TileColor" content="#0B0F19">
<meta name="msapplication-TileImage" content="{{ asset('img/icons/icon-144x144.png') }}?v={{ @filemtime(public_path('img/icons/icon-144x144.png')) ?: '1' }}">
