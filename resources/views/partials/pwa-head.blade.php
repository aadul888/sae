<!-- Progressive Web App (PWA) Standard Manifest & Meta Tags -->
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}?v={{ @filemtime(public_path('manifest.webmanifest')) ?: '1' }}">

<!-- Android / Chrome / HarmonyOS -->
<meta name="mobile-web-app-capable" content="yes">
<meta name="application-name" content="SAE">
<meta name="theme-color" content="#0B0F19" id="pwaThemeColorMeta">

<!-- Apple iOS / Safari / iPadOS -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="SAE">
<link rel="apple-touch-icon" href="{{ asset('img/icons/apple-touch-icon.png') }}">
<link rel="apple-touch-icon" sizes="152x152" href="{{ asset('img/icons/icon-152x152.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('img/icons/apple-touch-icon.png') }}">
<link rel="apple-touch-icon" sizes="192x192" href="{{ asset('img/icons/icon-192x192.png') }}">

<!-- Windows / Edge Tiles -->
<meta name="msapplication-TileColor" content="#0B0F19">
<meta name="msapplication-TileImage" content="{{ asset('img/icons/icon-144x144.png') }}">
