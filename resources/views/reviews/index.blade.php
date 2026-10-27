<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kundenbewertungen – meinfenster24</title>

    {{-- Isotope + imagesLoaded: müssen vor reviews.js geladen sein --}}
    <script src="https://cdn.jsdelivr.net/npm/isotope-layout@3.0.6/dist/isotope.pkgd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/imagesloaded@5.0.0/imagesloaded.pkgd.min.js"></script>

    @vite(['resources/css/reviews.css', 'resources/js/reviews.js'])
</head>
<body>

<header class="rv-header">
    <div class="rv-header-inner">
        <img
            class="rv-logo"
            src="https://www.meinfenster24.de/bilder/meinfenster24-fenster_RGB.svg"
            alt="meinfenster24 – Fenster, Türen, Rollläden"
        >
        <div>
            <h1>Das sagen unsere Kundinnen und Kunden</h1>
            <p id="rv-count">Echte Bewertungen aus der Community.</p>
        </div>
    </div>
</header>

<main
    id="rv-grid"
    class="rv-grid"
    aria-live="polite"
    data-endpoint="{{ url('/api/reviews') }}"
></main>

</body>
</html>
