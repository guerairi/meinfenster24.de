<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kundenbewertungen (KI-Version) – meinfenster24</title>

    @vite('resources/css/app.css')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        .rv-star-filled { fill: #1088cc; }
        .rv-star-empty  { fill: #e5e7eb; }
        .rv-clamp-8 {
            display: -webkit-box;
            -webkit-line-clamp: 8;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        @media (hover: none) {
            .rv-arrow { opacity: 0.9 !important; }
        }
    </style>
</head>

<body
    x-data="reviewsApp()"
    x-init="load()"
    class="min-h-screen bg-[#F3F4F6] text-[#3F3F3F] antialiased"
    style="font-family: 'Instrument Sans', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;"
>

<header class="max-w-6xl mx-auto px-4 sm:px-6 pt-12 pb-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <img
            src="https://www.meinfenster24.de/bilder/meinfenster24-fenster_RGB.svg"
            alt="meinfenster24 – Fenster, Türen, Rollläden"
            class="h-10 sm:h-12 w-auto"
        >
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">
                Das sagen unsere Kundinnen und Kunden
            </h1>
            <p x-cloak class="mt-1 text-sm text-gray-500" x-text="subtitle"></p>
        </div>
    </div>
</header>

<main class="max-w-6xl mx-auto px-4 sm:px-6 pb-16">

    {{-- Loading --}}
    <template x-if="loading">
        <div class="columns-1 sm:columns-2 lg:columns-3 gap-6">
            <template x-for="i in 6" :key="i">
                <div class="mb-6 break-inside-avoid bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden animate-pulse">
                    <div class="aspect-[16/10] bg-gray-100"></div>
                    <div class="p-5 space-y-3">
                        <div class="h-4 bg-gray-100 rounded w-1/2"></div>
                        <div class="h-3 bg-gray-100 rounded w-1/3"></div>
                        <div class="h-3 bg-gray-100 rounded w-full"></div>
                    </div>
                </div>
            </template>
        </div>
    </template>

    {{-- Fehler --}}
    <template x-if="!loading && error">
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
            <p x-text="error"></p>
        </div>
    </template>

    {{-- Leer --}}
    <template x-if="!loading && !error && reviews.length === 0">
        <div class="bg-white border border-gray-200 text-gray-500 text-center px-6 py-10 rounded-xl">
            Es sind derzeit keine Bewertungen verfügbar.
        </div>
    </template>

    {{-- Karten --}}
    <template x-if="!loading && !error && reviews.length > 0">
        <div class="columns-1 sm:columns-2 lg:columns-3 gap-6">

            <template x-for="review in reviews" :key="review.id">
                <article
                    x-data="{ current: 0 }"
                    class="mb-6 break-inside-avoid bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200"
                >

                    {{-- Bildbereich mit Carousel --}}
                    <div class="relative group bg-gray-100">

                        <img
                            :src="review.images[current]?.src ?? mediaPlaceholder()"
                            :alt="review.images[current]?.alt ?? ('Foto zur Bewertung von ' + review.name)"
                            x-on:error="$event.target.src = mediaPlaceholder(); $event.target.alt = 'Kein Bild vorhanden';"
                            loading="lazy"
                            decoding="async"
                            class="w-full h-auto block"
                        >

                        {{-- Carousel-Steuerung --}}
                        <template x-if="review.images.length > 1">
                            <div class="contents">

                                {{-- Zähler --}}
                                <span
                                    class="absolute top-2.5 right-2.5 bg-black/55 text-white text-xs font-semibold px-2 py-0.5 rounded-full backdrop-blur-sm pointer-events-none z-10"
                                    x-text="(current + 1) + ' / ' + review.images.length"
                                ></span>

                                {{-- Prev --}}
                                <button
                                    type="button"
                                    x-on:click="current = (current - 1 + review.images.length) % review.images.length"
                                    aria-label="Vorheriges Bild"
                                    class="rv-arrow absolute top-1/2 -translate-y-1/2 left-2 z-10 w-8 h-8 rounded-full bg-black/45 hover:bg-black/70 text-white text-xl leading-none flex items-center justify-center backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity duration-150"
                                >‹</button>

                                {{-- Next --}}
                                <button
                                    type="button"
                                    x-on:click="current = (current + 1) % review.images.length"
                                    aria-label="Nächstes Bild"
                                    class="rv-arrow absolute top-1/2 -translate-y-1/2 right-2 z-10 w-8 h-8 rounded-full bg-black/45 hover:bg-black/70 text-white text-xl leading-none flex items-center justify-center backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity duration-150"
                                >›</button>

                                {{-- Dots --}}
                                <div class="absolute bottom-2.5 left-1/2 -translate-x-1/2 z-10 flex gap-1.5 px-2 py-1 bg-black/45 rounded-full backdrop-blur-sm">
                                    <template x-for="(img, idx) in review.images" :key="idx">
                                        <button
                                            type="button"
                                            x-on:click="current = idx"
                                            :class="current === idx ? 'bg-white scale-125' : 'bg-white/55 hover:bg-white/85'"
                                            :aria-label="'Bild ' + (idx + 1) + ' von ' + review.images.length"
                                            class="w-2 h-2 rounded-full transition-all duration-150"
                                        ></button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="p-5 sm:p-6 flex flex-col gap-4">

                        <div class="flex items-center gap-3">
                            <img
                                :src="avatarPlaceholder(review.name)"
                                alt=""
                                aria-hidden="true"
                                loading="lazy"
                                class="w-11 h-11 rounded-full shrink-0"
                            >
                            <div class="min-w-0">
                                <p class="font-semibold truncate" x-text="review.name"></p>
                                <div class="flex gap-0.5 mt-0.5" role="img" :aria-label="review.rating + ' von 5 Sternen'">
                                    <template x-for="i in 5" :key="i">
                                        <svg viewBox="0 0 24 24" aria-hidden="true" class="w-4 h-4"
                                             :class="i <= review.rating ? 'rv-star-filled' : 'rv-star-empty'">
                                            <path d="M12 2.5l2.9 6.2 6.8.9-5 4.7 1.3 6.7L12 17.9 5.9 21l1.3-6.7-5-4.7 6.8-.9L12 2.5z"/>
                                        </svg>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <p class="rv-clamp-8 text-sm leading-relaxed text-gray-700 break-words" x-text="review.text"></p>

                        <p x-show="review.date" x-cloak class="text-xs text-gray-400" x-text="'Bestellung: ' + review.date"></p>
                    </div>
                </article>
            </template>
        </div>
    </template>
</main>

<script>
    function reviewsApp() {
        return {
            reviews: [],
            loading: true,
            error: null,
            endpoint: '{{ url('/api/reviews') }}',

            async load() {
                try {
                    const res = await fetch(this.endpoint, {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);

                    const data = await res.json();
                    if (data && data.error) throw new Error(data.error);

                    // Normalisieren: jedes Review bekommt garantiert ein images-Array
                    this.reviews = (Array.isArray(data) ? data : []).map(r => {
                        let images = [];
                        if (Array.isArray(r.images)) {
                            images = r.images.filter(i => i && i.src);
                        } else if (r.image) {
                            // Fallback für alte API-Form (Singular)
                            images = [{ src: r.image, alt: r.imageAlt || null }];
                        }
                        return { ...r, images };
                    });
                } catch (e) {
                    console.error('[reviews-ki]', e);
                    this.error = 'Bewertungen konnten nicht geladen werden.';
                } finally {
                    this.loading = false;
                }
            },

            get subtitle() {
                if (this.loading) return 'Bewertungen werden geladen …';
                if (this.error) return 'Fehler beim Laden der Bewertungen.';
                const n = this.reviews.length;
                if (n === 0) return 'Derzeit keine Bewertungen verfügbar.';
                if (n === 1) return '1 Bewertung aus der Community.';
                return n + ' Bewertungen aus der Community.';
            },

            avatarPlaceholder(name) {
                const parts = String(name).trim().split(/\s+/).filter(Boolean);
                const initials = (parts.slice(0, 2).map(p => p[0]).join('') || '?').toUpperCase();
                const palette = [
                    ['#1088cc', '#0a5a8a'],
                    ['#1088cc', '#0d6fa8'],
                    ['#1088cc', '#1382c2'],
                ];
                let hash = 0;
                for (const c of name) hash = (hash * 31 + c.charCodeAt(0)) >>> 0;
                const [c1, c2] = palette[hash % palette.length];

                const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="112" height="112">
                    <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stop-color="${c1}"/>
                        <stop offset="1" stop-color="${c2}"/>
                    </linearGradient></defs>
                    <rect width="112" height="112" fill="url(#g)"/>
                    <text x="50%" y="50%" dy=".35em" text-anchor="middle"
                          fill="#fff" font-family="system-ui, sans-serif"
                          font-size="44" font-weight="600">${initials}</text>
                </svg>`;
                return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
            },

            mediaPlaceholder() {
                const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="640" height="400">
                    <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stop-color="#f0f4f8"/>
                        <stop offset="1" stop-color="#f9fafb"/>
                    </linearGradient></defs>
                    <rect width="640" height="400" fill="url(#g)"/>
                    <g fill="none" stroke="#1088cc" stroke-width="8" stroke-linecap="round">
                        <rect x="230" y="150" width="180" height="140" rx="10"/>
                        <path d="M230 150 L320 100 L410 150"/>
                        <path d="M320 100 L320 290"/>
                    </g>
                    <text x="320" y="340" text-anchor="middle" fill="#1088cc"
                          font-family="system-ui, sans-serif" font-size="18">Kein Bild vorhanden</text>
                </svg>`;
                return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
            },
        };
    }
</script>

</body>
</html>
