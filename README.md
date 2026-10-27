# Meinfenster24 – Review Cards

Ein Laravel-Projekt mit **zwei parallelen Umsetzungen** einer Review-Cards-Komponente für Kundenbewertungen. Beide Varianten nutzen dieselbe API (`/api/reviews`) und dieselbe SQLite-Datenbank, unterscheiden sich aber grundlegend im Frontend-Ansatz.

| Variante | Route | Stack | Ansatz |
|---|---|---|---|
| **Engineered** | `/bewertungen` | Vanilla CSS + Vanilla JS + Isotope | Maßgeschneidert, nah am Website-Design |
| **AI-assisted** | `/bewertung-ki` | Tailwind CSS + Alpine.js | Deklarativ, schnelle Iteration |

---

## Inhaltsverzeichnis

- [Features](#features)
- [Tech-Stack](#tech-stack)
- [Voraussetzungen](#voraussetzungen)
- [Installation](#installation)
- [Datenbank einrichten](#datenbank-einrichten)
- [Projektstruktur](#projektstruktur)
- [Die beiden Varianten](#die-beiden-varianten)
- [API-Endpunkt](#api-endpunkt)
- [Tests](#tests)
- [Entwicklungsworkflow](#entwicklungsworkflow)
- [Troubleshooting](#troubleshooting)
- [Lizenz](#lizenz)

---

## Features

- **Zwei Frontend-Varianten** derselben Komponente – engineered vs. AI-assisted
- **Responsive Masonry-Layout** (Desktop 3 Spalten, Tablet 2, Mobile 1)
- **Sternebewertung** aus der Datenbank, im Markenblau (`#1088cc`)
- **Bild-Fallback** – SVG-Platzhalter bei fehlenden oder defekten Bildern
- **Kundennamen-Formatierung** – „Sigrid Peters" → „Sigrid P."
- **Tolerantes Datums-Parsing** – unterstützt `YYYY-MM` und `YYYY-MM-DD`
- **XSS-sicher** – alle Datenbank-Felder werden escaped
- **Skeleton-Loader** während des Ladens
- **Barrierefrei** – `aria-label` für Sterne, `loading="lazy"` für Bilder

---

## Tech-Stack

| Layer | Technologie |
|---|---|
| Framework | Laravel 13 |
| PHP | 8.3+ |
| Datenbank | SQLite (`db.sqlite` mit `rezensionen` + `rezensionen_grafiken`) |
| Build-Tool | Vite 8 |
| CSS (engineered) | Vanilla CSS |
| CSS (KI) | Tailwind CSS v4 |
| JS (engineered) | Vanilla JS + Isotope.js |
| JS (KI) | Alpine.js 3 |
| Tests | PHPUnit |

---

## Voraussetzungen

- **PHP ≥ 8.3** mit den Erweiterungen `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`
- **Composer ≥ 2**
- **Node.js ≥ 20** und **npm**
- **Git**

Prüfen:

```bash
php -v          # → PHP 8.3.x
composer -V     # → Composer 2.x
node -v         # → v20.x oder neuer
npm -v          # → 10.x
```

---

## Installation

### 1. Repository klonen

```bash
git clone git@github.com:guerairi/meinfenster24.de.git
cd meinfenster24.de
```

### 2. PHP-Abhängigkeiten installieren

```bash
composer install
```

### 3. Node-Abhängigkeiten installieren

```bash
npm install
```

### 4. `.env`-Datei anlegen

```bash
cp .env.example .env
php artisan key:generate
```

### 5. `.env` konfigurieren

Für die SQLite-Datenbank (Pfad absolut angeben):

```bash
mv database/rezensionen.sqlit database/rezensionen.sqlite
```

```env
APP_NAME=Meinfenster24
APP_URL=http://127.0.0.1:8001
APP_DEBUG=true

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/meinfenster24.de/database/rezensionen.sqlite
```

> **Wichtig:** `APP_URL` und die Server-Adresse sollten konsistent sein (entweder beide `127.0.0.1` oder beide `localhost`), sonst gibt es Probleme mit Vite-HMR.

### 6. Frontend bauen (Development)

Zwei Terminals parallel:

**Terminal 1 – Vite Dev-Server:**

```bash
npm run dev
# → läuft auf http://127.0.0.1:5173
```

**Terminal 2 – Laravel Server:**

```bash
php artisan serve --host=127.0.0.1 --port=8001
# → läuft auf http://127.0.0.1:8001
```

Aufrufen:

- **Engineered:** http://127.0.0.1:8001/bewertungen
- **KI-Variante:** http://127.0.0.1:8001/bewertung-ki

### 6b. Frontend bauen (Production)

```bash
npm run build
php artisan serve --host=127.0.0.1 --port=8001
```

Nach dem Build sind keine zwei Terminals mehr nötig — die Assets liegen statisch unter `public/build/`.

---

## Datenbank einrichten

Die SQLite-Datei enthält zwei Tabellen (siehe Dump am Ende dieses Abschnitts).

Prüfen:

```bash
php artisan tinker
>>> DB::table('rezensionen')->count();
=> 3
```

---

## Projektstruktur

```
meinfenster24.de/
├── app/
│   ├── Http/Controllers/Api/
│   │   └── ReviewController.php        # Dünner HTTP-Adapter
│   └── Services/
│       └── ReviewService.php           # Fachlogik: SQL, Formatierung
├── database/
│   └── db.sqlite                       # SQLite-Datenbank
├── resources/
│   ├── css/
│   │   ├── app.css                     # Tailwind (für KI-Variante)
│   │   └── reviews.css                 # Vanilla CSS (engineered)
│   ├── js/
│   │   ├── app.js
│   │   └── reviews.js                  # Vanilla JS + Isotope
│   └── views/reviews/
│       ├── index.blade.php             # Engineered (/bewertungen)
│       └── ki.blade.php                # KI-Variante (/bewertung-ki)
├── routes/
│   ├── api.php                         # /api/reviews
│   └── web.php                         # /bewertungen, /bewertung-ki
├── tests/
│   ├── Feature/ 
│   └── Unit/
│       └── ReviewServiceTest.php       # Fachlogik (19 Tests)
├── vite.config.js
└── README.md
```

---

## Die beiden Varianten

### Engineered – `/bewertungen`

**Ansatz:** Handgeschriebenes CSS, Vanilla JavaScript, Isotope.js für Masonry.

**Routen:** `resources/views/reviews/index.blade.php`

**Assets:**
- `resources/css/reviews.css`
- `resources/js/reviews.js`
- Isotope.js + imagesLoaded (CDN)

**Stärken:**
- Volle Kontrolle über jede CSS-Regel
- Keine Framework-Abhängigkeit zur Laufzeit
- Passt sich nahtlos in das bestehende Website-Design ein
- Masonry-Layout mit animiertem Filter (optional)

**Beispiel für die Verwendung von Isotope:**

```js
const iso = new Isotope(grid, {
    itemSelector: '.rv-card',
    layoutMode: 'fitRows',
    percentPosition: true,
    transitionDuration: '0.35s',
});
```

### AI-assisted – `/bewertung-ki`

**Ansatz:** Tailwind-Utilities + Alpine.js, deklaratives Rendering.

**Routen:** `resources/views/reviews/ki.blade.php`

**Assets:**
- `resources/css/app.css` (Tailwind v4)
- `resources/js/reviews-ki.js` (Alpine)

**Stärken:**
- Sehr schnelle Iteration
- Weniger Boilerplate durch `x-for` / `x-text`
- Zustandsverwaltung direkt im Markup
- Ideal als Proof-of-Concept oder für MVPs

**Beispiel für Alpine-Datenbindung:**

```html
<template x-for="review in reviews" :key="review.id">
    <article class="bg-white rounded-2xl …">
        <p class="font-semibold" x-text="review.name"></p>
        <div class="flex gap-0.5">
            <template x-for="i in 5" :key="i">
                <svg :class="i <= review.rating ? 'fill-[#1088cc]' : 'fill-gray-200'"></svg>
            </template>
        </div>
        <p x-text="review.text"></p>
    </article>
</template>
```

### Welche Variante wählen?

| Kriterium | Engineered | KI-Variante |
|---|---|---|
| Volle Design-Kontrolle | ✅ | teilweise |
| Schnelle Iteration | ✅ | ✅ |
| Keine Framework-Abhängigkeit | ✅ | ❌ (Tailwind + Alpine via CDN) |
| Wartbarkeit langfristig | ✅ | ✅ |
| Einstiegshürde für neue Devs | mittel | niedrig |

Für Production empfiehlt sich die **engineered** Variante. Die KI-Variante ist ein vollständiger, lauffähiger Vergleich – nützlich als Referenz und für schnelle Experimente.

---

## API-Endpunkt

### `GET /api/reviews`

Liefert alle aktiven Reviews als JSON.

**Beispiel:**

```bash
curl -s http://127.0.0.1:8001/api/reviews | jq
```

**Antwort:**

```json
[
  {
    "id": 10,
    "name": "Lukas R.",
    "rating": 5,
    "text": "Alles perfekt gelaufen!",
    "image": "https://example.com/img.jpg",
    "imageAlt": "Eingebautes Fenster",
    "date": "20. August 2026"
  }
]
```

**Felder:**

| Feld | Typ | Beschreibung |
|---|---|---|
| `id` | int | Primärschlüssel aus `rezensionen.r_id` |
| `name` | string | Formatierter Name: `Vorname N.` |
| `rating` | int | 0–5 (geclampt) |
| `text` | string | Rezensionstext |
| `image` | string\|null | URL der ersten Grafik oder `null` |
| `imageAlt` | string\|null | Alt-Text aus `alt_attribut` |
| `date` | string\|null | Formatiertes Bestelldatum (z. B. „April 2026") |

**Fehlerfall:** Bei einer unbehandelten Exception wird `500` mit generischer Fehlermeldung zurückgegeben. Ist `APP_DEBUG=true`, enthält das Feld `message` zusätzlich die Original-Exception.

---

## Tests

### Alle Tests ausführen

```bash
php artisan test
```

### Nur Unit-Tests

```bash
php artisan test --testsuite=Unit
```

### Nur Feature-Tests

```bash
php artisan test --testsuite=Feature
```

### Einzelne Testklasse

```bash
php artisan test tests/Unit/ReviewServiceTest.php
```

### Abdeckung

| Testklasse | Anzahl | Prüft |
|---|--------|---|
| `ReviewServiceTest` | 20     | SQL, Namensformatierung, Datum, Rating-Clamp, Bild-Auswahl |

**Wichtig:** Die Unit-Tests laufen gegen eine **In-Memory-SQLite**, nicht gegen deine echte `db.sqlite`. Sicherstellen, dass `phpunit.xml` folgende Zeilen enthält:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

---

## Entwicklungsworkflow

### Typischer Ablauf

```bash
# Terminal 1: Vite
npm run dev

# Terminal 2: Laravel
php artisan serve --host=127.0.0.1 --port=8001

# Terminal 3: Tests (optional)
php artisan test --filter=ReviewServiceTest
```

### Änderungen an der API

1. `ReviewService.php` anpassen
2. Tests erweitern: `tests/Unit/ReviewServiceTest.php`
3. Tests laufen lassen: `php artisan test`
4. Bei Bedarf `ReviewController` oder `reviews.js` / `ki.blade.php` anpassen

### Änderungen am Styling

- **Engineered:** `resources/css/reviews.css` bearbeiten
- **KI-Variante:** Tailwind-Utilities direkt im Blade anpassen oder `resources/css/app.css` erweitern


## Troubleshooting

### `Vite manifest not found at: public/build/manifest.json`

Vite-Dev-Server läuft nicht und Assets wurden nicht gebaut. Lösung:

```bash
npm run dev       # Development
# oder
npm run build     # Production
```

### `http://[::1]:5173%` – IPv6-Problem

Vite bindet auf macOS standardmäßig auf IPv6. Lösung in `vite.config.js`:

```js
server: {
    host: '127.0.0.1',
    port: 5173,
    strictPort: true,
    hmr: { host: '127.0.0.1' },
},
```

Danach `rm -f public/hot && npm run dev`.

### `Target class [App\Http\Controllers\Api\ReviewController] does not exist`

Autoloader kennt die Klasse nicht. Lösung:

```bash
composer dump-autoload
php artisan optimize:clear
php artisan route:list --path=api   # Route muss da sein
```

Wenn die Route fehlt: `routes/api.php` prüfen, und `bootstrap/app.php` muss `api: __DIR__.'/../routes/api.php'` enthalten.

### `ParseError: syntax error, unexpected end of file`

In einer Blade-View kollidieren Alpine-`@`-Events mit Blade-Directives. Lösung: `@click` → `x-on:click`, `@error` → `x-on:error`, `@input` → `x-on:input`.

### `[laravel:fonts] Optimized font fallbacks require "fontaine"`

Nur eine Warnung. Entweder ignorieren, `fontaine` installieren (`npm install --save-dev fontaine`) oder `optimizedFallbacks: false` setzen.

### Tests laufen gegen die echte `db.sqlite`

`phpunit.xml` enthält nicht die Test-DB-Konfiguration. Ergänzen:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### `Call to undefined function uses()`

Die Test-Datei nutzt Pest-Syntax, aber das Projekt läuft mit PHPUnit. Entweder Pest installieren (`composer require pestphp/pest --dev`) oder die Tests auf PHPUnit-Syntax (`class … extends TestCase`) umschreiben.

---

## Lizenz

Dieses Projekt ist für **meinfenster24.de** erstellt und unterliegt den Bedingungen des Unternehmens. Kein öffentliches Open-Source-Release.

---

## Credits

- Design-Vorlage: [meinfenster24.de](https://www.meinfenster24.de)
- Framework: [Laravel 13](https://laravel.com)
- Build: [Vite 8](https://vitejs.dev)
- Masonry-Layout: [Isotope.js](https://isotope.metafizzy.co)
- Reaktives Frontend: [Alpine.js](https://alpinejs.dev)
- Utility-CSS: [Tailwind CSS v4](https://tailwindcss.com)
