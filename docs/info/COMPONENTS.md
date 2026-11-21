# Catálogo de Componentes UI y Vistas (`COMPONENTS.md`)

*Última actualización: 2026-11-20*

> Inventario real de layout, componentes Blade y vistas de error de **`beacon-sift-web`** (`https://feed.raupulus.dev`).

---

## 1. Layout principal (`resources/views/layouts/app.blade.php`)

- **Metadatos y SEO:** Inyecta título, descripción, `canonical`, `theme-color: #090E17`, favicons (`/favicon.svg`, `/favicon.ico`, `/apple-touch-icon.png`), `site.webmanifest`, `<link rel="alternate" type="application/rss+xml">` (heredando `$rssUrl` cuando hay filtros activos), Open Graph y Twitter Card (`/images/redes-sociales/og-image-1200x630.png`).
- **Header sticky (`h-16`):**
  - Logotipo `/images/logos/logo.svg` + marca `beacon-sift` (`feed.raupulus.dev`).
  - Navegación: `Noticias` (`/`), `Fuentes` (`/sources`) y acceso directo `RSS` en nueva pestaña (`/feed.rss` heredando filtros activos).
  - Indicador de estado WebSocket reactivo vía evento `beacon:ws-status` (`LIVE` en `#10B981`, `CONECTANDO` en `#F59E0B`, `OFFLINE` en `#94A3B8`).
- **Footer:** Aviso de agregación responsable, enlaces a `/about`, `/privacy`, `/cookies` y contacto `public@raupulus.dev`.

---

## 2. Componente de tarjeta (`<x-news-card :item="$item" />`)

Archivo: `resources/views/components/news-card.blade.php`

- **Cabecera 16:9:**
  - Si `$item->image_url` está presente, muestra la imagen por **hotlink directo** (`loading="lazy"`, `referrerpolicy="no-referrer"`) sin descargar ni re-alojar en servidor.
  - Si no existe `image_url` o falla la carga (`onerror`), muestra automáticamente un **fallback HUD** (fondo `#0B1324` con `.hud-grid-bg`, tipo y nombre de fuente).
  - Badges superpuestos para `type` y `priority` (clases de color de `Priority::badgeClasses()`).
- **Cuerpo:**
  - Nombre de la fuente y fecha formateada con `format_date($item->published_at, 'd/m/Y H:i')`.
  - Titular enlazado directamente al artículo original (`target="_blank" rel="noopener noreferrer"`).
  - Extracto breve limpio (`$item->snippet`, máx. ~160 caracteres) cuando existe.
  - Pie con filtro rápido por `#slug` de fuente y enlace externo `Leer fuente ↗`.

*(Las tarjetas recibidas en vivo vía WebSocket en `resources/views/home.blade.php` replican exactamente esta misma estructura visual con Alpine.js).*

---

## 3. Barra de acciones RSS (`<x-rss-actions />`)

Archivo: `resources/views/components/rss-actions.blade.php`

- Renderiza el enlace directo al feed XML filtrado en nueva pestaña (`target="_blank" rel="noopener noreferrer"`) junto al botón `Copiar enlace RSS` con portapapeles (`navigator.clipboard.writeText`) y región `role="status" aria-live="polite"` externa para anunciar el resultado (`URL RSS copiada` / `No se pudo copiar`).

---

## 4. Vistas de error HUD

- `resources/views/errors/404.blade.php`: Página `404` estilizada en `#090E17` / `#0F172A` con botón de retorno a `route('home')`.
- `resources/views/errors/500.blade.php`: Página `500` estilizada sin exponer trazas técnicas al público.

---

> Creado: 2026-11-20 · Última revisión: 2026-11-22
