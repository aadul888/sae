<!-- Progressive Web App (PWA) Standard Manifest & Meta Tags -->
<script>
    window.__SAE_PWA__ = {
        swUrl: "/sw.js",
        scope: "/",
        isSecure: window.isSecureContext || location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1'
    };
</script>
<link rel="manifest" id="pwaManifestLink" href="/manifest.json?v=8">
<link rel="alternate" type="application/manifest+json" href="/manifest.webmanifest?v=8">


<!-- Android / Chrome / HarmonyOS -->
<meta name="mobile-web-app-capable" content="yes">
<meta name="application-name" content="SAE">
<meta name="theme-color" content="#FFFFFF" id="pwaThemeColorMeta">

<!-- Apple iOS / Safari / iPadOS -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="SAE">
<link rel="apple-touch-icon" id="pwaAppleTouchIcon" href="/img/icons/sae-apple-touch-icon.png?v=8">
<link rel="apple-touch-icon" sizes="152x152" href="/img/icons/sae-icon-152x152.png?v=8">
<link rel="apple-touch-icon" sizes="180x180" href="/img/icons/sae-icon-180x180.png?v=8">
<link rel="apple-touch-icon" sizes="192x192" href="/img/icons/sae-icon-192x192.png?v=8">

<!-- Windows / Edge Tiles -->
<meta name="msapplication-TileColor" content="#FFFFFF">
<meta name="msapplication-TileImage" content="/img/icons/sae-icon-144x144.png?v=8">
