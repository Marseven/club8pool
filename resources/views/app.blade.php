@php
    $page = $page ?? [];
    $url = url()->current();
    $defaultTitle = 'Club 8 Pool · Icone Pool Championship';
    $defaultDescription = 'Plateforme de gestion de compétitions de billard à Libreville. Suivez les classements de poules, les brackets de phase finale et les matchs en direct du Icone Pool Championship.';
    $ogImage = url('/og-image.png');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ $defaultTitle }}</title>

    {{-- SEO --}}
    <meta name="description" content="{{ $defaultDescription }}">
    <meta name="keywords" content="billard, pool, 8-ball, 10-ball, competition, championnat, Libreville, Gabon, tournoi, club, Icone Pool, race to, poules, classement">
    <meta name="author" content="Club 8 Pool">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="{{ $url }}">

    {{-- Open Graph (Facebook, LinkedIn, WhatsApp) --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Club 8 Pool">
    <meta property="og:title" content="{{ $defaultTitle }}">
    <meta property="og:description" content="{{ $defaultDescription }}">
    <meta property="og:url" content="{{ $url }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Club 8 Pool — Icone Pool Championship">
    <meta property="og:locale" content="fr_FR">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $defaultTitle }}">
    <meta name="twitter:description" content="{{ $defaultDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <meta name="twitter:image:alt" content="Club 8 Pool — Icone Pool Championship">

    {{-- Identité visuelle navigateur / OS --}}
    <meta name="theme-color" content="#0A0A0B">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="C8P">
    <meta name="application-name" content="Club 8 Pool">
    <meta name="format-detection" content="telephone=no">

    {{-- Favicons --}}
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="alternate icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">

    {{-- Polices --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Antonio:wght@400;500;600;700&family=Manrope:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">

    {{-- Structured data: SportsEvent + Organization --}}
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "Club 8 Pool",
      "url": "https://club8pool.com",
      "logo": "https://club8pool.com/apple-touch-icon.png",
      "description": "{{ $defaultDescription }}",
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "Libreville",
        "addressCountry": "GA"
      }
    }
    </script>

    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
