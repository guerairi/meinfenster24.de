/**
 * Review-Cards – Frontend-Modul (Masonry) mit Carousel für Multi-Images
 * Lädt alle Bewertungen von der API und rendert sie im Masonry-Layout.
 */
(() => {
    const grid = document.getElementById('rv-grid');
    if (!grid) return;

    const countEl  = document.getElementById('rv-count');
    const endpoint = grid.dataset.endpoint || '/api/reviews';

    /* ------------------------------------------------------
       Helpers
       ------------------------------------------------------ */

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, ch => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[ch]));
    }

    function avatarPlaceholder(name) {
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
    }

    function mediaPlaceholder() {
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
    }

    function starRating(rating, max = 5) {
        let stars = '';
        for (let i = 1; i <= max; i++) {
            stars += `<svg class="rv-star${i <= rating ? ' is-filled' : ''}"
                           viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 2.5l2.9 6.2 6.8.9-5 4.7 1.3 6.7L12 17.9 5.9 21l1.3-6.7-5-4.7 6.8-.9L12 2.5z"/>
                      </svg>`;
        }
        return `<div class="rv-stars" role="img" aria-label="${rating} von ${max} Sternen">${stars}</div>`;
    }

    /* ------------------------------------------------------
       Carousel-Block
       ------------------------------------------------------ */

    /**
     * Baut den Bildbereich einer Karte.
     * - 0 Bilder  → Platzhalter
     * - 1 Bild    → einzelnes Bild
     * - 2+ Bilder → Carousel mit Pfeilen, Dots und Zähler
     */
    function createMedia(images, name) {
        const wrap = document.createElement('div');
        wrap.className = 'rv-media';

        const list = Array.isArray(images) ? images.filter(i => i && i.src) : [];

        // 0 Bilder → Platzhalter
        if (list.length === 0) {
            const img = document.createElement('img');
            img.className = 'rv-media-img';
            img.src = mediaPlaceholder();
            img.alt = 'Kein Bild vorhanden';
            img.loading = 'lazy';
            img.decoding = 'async';
            wrap.appendChild(img);
            return wrap;
        }

        // 1 Bild → einfaches Bild
        if (list.length === 1) {
            const entry = list[0];
            const img = document.createElement('img');
            img.className = 'rv-media-img';
            img.src = entry.src;
            img.alt = entry.alt || `Foto zur Bewertung von ${name}`;
            img.loading = 'lazy';
            img.decoding = 'async';
            img.addEventListener('error', () => {
                img.src = mediaPlaceholder();
                img.alt = 'Kein Bild vorhanden';
            }, { once: true });
            wrap.appendChild(img);
            return wrap;
        }

        // 2+ Bilder → Carousel
        let current = 0;

        const img = document.createElement('img');
        img.className = 'rv-media-img';
        img.loading = 'lazy';
        img.decoding = 'async';
        img.src = list[0].src;
        img.alt = list[0].alt || `Foto zur Bewertung von ${name}`;

        // Fallback bei kaputter URL (Karte bleibt funktional)
        img.addEventListener('error', function onErr() {
            img.src = mediaPlaceholder();
            img.alt = 'Kein Bild vorhanden';
            img.removeEventListener('error', onErr);
        });

        const counter = document.createElement('span');
        counter.className = 'rv-media-counter';
        counter.textContent = `1 / ${list.length}`;

        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'rv-media-arrow rv-media-arrow--prev';
        prevBtn.setAttribute('aria-label', 'Vorheriges Bild');
        prevBtn.innerHTML = '‹';

        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'rv-media-arrow rv-media-arrow--next';
        nextBtn.setAttribute('aria-label', 'Nächstes Bild');
        nextBtn.innerHTML = '›';

        const dots = document.createElement('div');
        dots.className = 'rv-media-dots';
        dots.setAttribute('role', 'tablist');

        const dotEls = list.map((entry, idx) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'rv-media-dot' + (idx === 0 ? ' is-active' : '');
            dot.setAttribute('aria-label', `Bild ${idx + 1} von ${list.length}`);
            dot.addEventListener('click', () => goTo(idx));
            dots.appendChild(dot);
            return dot;
        });

        function goTo(idx) {
            current = (idx + list.length) % list.length;
            const entry = list[current];
            img.src = entry.src;
            img.alt = entry.alt || `Foto zur Bewertung von ${name}`;
            counter.textContent = `${current + 1} / ${list.length}`;
            dotEls.forEach((d, i) => d.classList.toggle('is-active', i === current));
        }

        prevBtn.addEventListener('click', () => goTo(current - 1));
        nextBtn.addEventListener('click', () => goTo(current + 1));

        wrap.appendChild(img);
        wrap.appendChild(counter);
        wrap.appendChild(prevBtn);
        wrap.appendChild(nextBtn);
        wrap.appendChild(dots);

        return wrap;
    }

    /* ------------------------------------------------------
       Card
       ------------------------------------------------------ */

    function createCard(review) {
        const card = document.createElement('article');
        card.className = 'rv-card';

        const avatarFallback = avatarPlaceholder(review.name);

        // Bildbereich (Carousel / Einzelbild / Platzhalter)
        card.appendChild(createMedia(review.images, review.name));

        const body = document.createElement('div');
        body.className = 'rv-body';
        body.innerHTML = `
            <header class="rv-head">
                <img class="rv-avatar" src="${escapeHtml(avatarFallback)}"
                     alt="" aria-hidden="true" loading="lazy">
                <div class="rv-meta">
                    <p class="rv-name">${escapeHtml(review.name)}</p>
                    ${starRating(review.rating)}
                </div>
            </header>
            <p class="rv-text">${escapeHtml(review.text)}</p>
            ${review.date
            ? `<p class="rv-date">Bestellung: ${escapeHtml(review.date)}</p>`
            : ''}
        `;
        card.appendChild(body);

        return card;
    }

    /* ------------------------------------------------------
       States
       ------------------------------------------------------ */

    function renderSkeletons(count = 6) {
        grid.innerHTML = '';
        for (let i = 0; i < count; i++) {
            const sk = document.createElement('div');
            sk.className = 'rv-skeleton';
            sk.innerHTML = `
                <div class="rv-skeleton-media"></div>
                <div class="rv-skeleton-body">
                    <div class="rv-skeleton-line is-short"></div>
                    <div class="rv-skeleton-line is-medium"></div>
                    <div class="rv-skeleton-line"></div>
                </div>
            `;
            grid.appendChild(sk);
        }
    }

    function renderError(message) {
        grid.innerHTML = `<p class="rv-error">${escapeHtml(message)}</p>`;
    }

    function renderEmpty() {
        grid.innerHTML = `<p class="rv-empty">Es sind derzeit keine Bewertungen verfügbar.</p>`;
    }

    function updateCount(n) {
        if (!countEl) return;
        if (n === 0) {
            countEl.textContent = 'Derzeit keine Bewertungen verfügbar.';
        } else if (n === 1) {
            countEl.textContent = '1 Bewertung aus der Community.';
        } else {
            countEl.textContent = `${n} Bewertungen aus der Community.`;
        }
    }

    /* ------------------------------------------------------
       Load
       ------------------------------------------------------ */

    async function load() {
        renderSkeletons(6);

        try {
            const res = await fetch(endpoint, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const data = await res.json();
            if (data && data.error) throw new Error(data.error);

            if (!Array.isArray(data) || data.length === 0) {
                renderEmpty();
                updateCount(0);
                return;
            }

            grid.innerHTML = '';
            data.forEach(r => grid.appendChild(createCard(r)));
            updateCount(data.length);
        } catch (err) {
            console.error('[reviews]', err);
            renderError('Bewertungen konnten nicht geladen werden.');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', load);
    } else {
        load();
    }
})();
