# Módulo: Frontend Web (`feed.raupulus.dev`)

*Última actualización: 2026-11-20*

> Interfaz pública de solo lectura con estética radar HUD (`DESIGN.md`), Tailwind CSS v4 y Alpine.js.

---

## Rutas y controladores (`app/Http/Controllers/Web/`)

- `GET /` (`FeedController` → `resources/views/home.blade.php`): Listado cronológico paginado (`18` por página) con filtros en URL (`q`, `type`, `priority`, `source`), píldoras rápidas de tipo, contador de resultados, enlace al `/feed.rss` filtrable y bloque superior en vivo con Alpine.js para eventos `beacon:news-created`.
- `GET /sources` (`SourcesPageController` → `resources/views/sources.blade.php`): Catálogo público de fuentes activas filtrable por `?type=` con indicador visual de salud por fuente.
- `GET /feed.rss` (`RssFeedController` → `resources/views/rss.blade.php`): Salida XML RSS 2.0 filtrable.
- `GET /about`, `/privacy`, `/cookies` (`StaticPageController`): Páginas legales e informativas simples con contacto `public@raupulus.dev`.

---

## Assets (`resources/css/app.css` y `resources/js/app.js`)

- `resources/css/app.css`: Directivas Tailwind CSS v4 (`@theme`), tokens del logotipo (`#090E17`, `#0F172A`, `#38BDF8`, `#00E5FF`, `#2563EB`, `#F59E0B`, `#10B981`) y clase `.hud-grid-bg` para el fallback visual de tarjetas.
- `resources/js/app.js`: Inicialización de Alpine.js y suscripción a Laravel Reverb mediante Laravel Echo (`pusher-js`).
