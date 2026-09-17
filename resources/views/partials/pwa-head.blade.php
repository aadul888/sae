<!-- Progressive Web App (PWA) Standard Manifest & Meta Tags -->
<script>
    window.__SAE_PWA__ = {
        swUrl: "{{ url('sw.js', [], false) }}",
        scope: "{{ url('/', [], false) }}/",
        isSecure: window.isSecureContext || location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1'
    };
</script>
<link rel="manifest" id="pwaManifestLink" href="{{ url('manifest.json', [], false) }}?v=7">
<link rel="alternate" type="application/manifest+json" href="{{ url('manifest.webmanifest', [], false) }}?v=7">

<!-- Explicit High-Resolution PWA Icons for Android Chrome & Homescreen -->
<link rel="icon" id="pwaIcon192" type="image/png" sizes="192x192" href="{{ url('img/icons/icon-192x192.png', [], false) }}?v=7">
<link rel="icon" id="pwaIcon512" type="image/png" sizes="512x512" href="{{ url('img/icons/icon-512x512.png', [], false) }}?v=7">

<!-- Android / Chrome / HarmonyOS -->
<meta name="mobile-web-app-capable" content="yes">
<meta name="application-name" content="SAE">
<meta name="theme-color" content="#FFFFFF" id="pwaThemeColorMeta">

<!-- Apple iOS / Safari / iPadOS -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="SAE">
<link rel="apple-touch-icon" id="pwaAppleTouchIcon" href="{{ url('img/icons/apple-touch-icon.png', [], false) }}?v=7">
<link rel="apple-touch-icon" sizes="152x152" href="{{ url('img/icons/icon-152x152.png', [], false) }}?v=7">
<link rel="apple-touch-icon" sizes="180x180" href="{{ url('img/icons/apple-touch-icon.png', [], false) }}?v=7">
<link rel="apple-touch-icon" sizes="192x192" href="{{ url('img/icons/icon-192x192.png', [], false) }}?v=7">

<!-- Windows / Edge Tiles -->
<meta name="msapplication-TileColor" content="#FFFFFF">
<meta name="msapplication-TileImage" content="{{ url('img/icons/icon-144x144.png', [], false) }}?v=7">
