# Guía y Especificación de Diseño (`DESIGN.md`) — `beacon-sift`

*Última actualización: 2026-11-20*

> Especificación técnica de identidad visual, esquema de colores (derivado del logotipo oficial), tipografía, tarjetas de noticias y maquetación para `https://feed.raupulus.dev` con Tailwind CSS v4.

---

## 1. Fundamentos y Filosofía Visual

`feed.raupulus.dev` implementa una estética **radar HUD / telemetría oscura de alto contraste**: limpia, técnica, escaneable en segundos y optimizada para lectura rápida de titulares y alertas en tiempo real.

- **Dark-first por defecto:** El lienzo principal parte del pizarra abisal `#090E17` del logotipo oficial, reduciendo fatiga visual y haciendo destacar las señales de prioridad y los estados en directo.
- **Mobile-first y accesible:** Contraste mínimo WCAG 2.1 AA sobre superficies oscuras, foco visible (`focus-visible`) en cyan holográfico y cero dependencias pesadas.
- **Sin ruido visual:** Portal público de solo lectura (sin formularios de login/registro ni barras de administración). La atención recae en la barra de filtros, las tarjetas cronológicas y el pulso en vivo de WebSockets.

---

## 2. Stack de Estilos: Tailwind CSS v4

La configuración vive exclusivamente en `resources/css/app.css` mediante `@theme` (sin `tailwind.config.js`):

```css
@import "tailwindcss";
@plugin "@tailwindcss/forms";

@theme {
    --font-sans: 'Figtree', ui-sans-serif, system-ui, sans-serif;
    --font-mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace;

    /* Superficies (Pizarra Abisal / HUD) */
    --color-bg: #090E17;
    --color-surface: #0F172A;
    --color-surface-elevated: #162238;
    --color-border: #1E293B;
    --color-border-accent: #1D4ED8;

    /* Acentos del Logotipo (Progresión Radar Cyan -> Cobalto + Baliza Ámbar) */
    --color-primary: #38BDF8;
    --color-primary-bright: #00E5FF;
    --color-primary-deep: #2563EB;
    --color-beacon: #F59E0B;
    --color-beacon-bright: #FBBF24;

    /* Tipografía */
    --color-text: #F8FAFC;
    --color-text-secondary: #CBD5E1;
    --color-text-muted: #94A3B8;

    /* Estados y Prioridades */
    --color-live: #10B981;
    --color-priority-low: #64748B;
    --color-priority-medium: #38BDF8;
    --color-priority-high: #F59E0B;
    --color-priority-critical: #EF4444;
}
```

---

## 3. Esquema de Colores (Tokens Semánticos)

### Paleta Principal (extraída del logotipo oficial)

| Rol Semántico | Token CSS | Código Hex | Propósito en la interfaz |
| :--- | :--- | :--- | :--- |
| **Fondo base** | `--color-bg` | `#090E17` | Fondo global de página (`body`), cabeceras y pie |
| **Superficie tarjeta** | `--color-surface` | `#0F172A` | Tarjetas de noticias, barra de filtros, paneles |
| **Superficie elevada** | `--color-surface-elevated` | `#162238` | Hover de tarjetas, dropdowns, badges activos |
| **Borde base** | `--color-border` | `#1E293B` | Separadores sutiles y contornos de tarjetas/inputs |
| **Primario (Sky)** | `--color-primary` | `#38BDF8` | Enlaces principales, filtros activos, acentos interactivos |
| **Primario brillante (Cyan)** | `--color-primary-bright` | `#00E5FF` | Focus rings, indicador de fuente seleccionada, highlights HUD |
| **Primario profundo (Cobalt)** | `--color-primary-deep` | `#2563EB` | Botones primarios, bordes destacados y anillos decorativos |
| **Baliza (Amber / Gold)** | `--color-beacon` / `--color-beacon-bright` | `#F59E0B` / `#FBBF24` | Alertas destacadas, prioridad `high`, detalles de marca |
| **Texto principal** | `--color-text` | `#F8FAFC` | Titulares de noticias, H1/H2 y navegación activa |
| **Texto secundario** | `--color-text-secondary` | `#CBD5E1` | Extractos breves (*snippets*) y párrafos legales |
| **Texto atenuado** | `--color-text-muted` | `#94A3B8` | Fechas, nombres de fuente, etiquetas secundarias |
| **Estado en vivo (Live)** | `--color-live` | `#10B981` | Punto pulsante de conexión WebSocket activa y fuentes OK |

### Escala Semántica de Prioridades (`priority`)

| Prioridad | Color principal | Fondo badge (15% opacidad) | Borde badge | Uso |
| :--- | :--- | :--- | :--- | :--- |
| **`low`** | `#94A3B8` | `rgba(100, 116, 139, 0.15)` | `#334155` | Noticias generales o informativas |
| **`medium`** | `#38BDF8` | `rgba(56, 189, 248, 0.14)` | `#0284C7` | Noticias relevantes estándar |
| **`high`** | `#FBBF24` | `rgba(245, 158, 11, 0.15)` | `#D97706` | Avisos locales importantes, actualizaciones relevantes |
| **`critical`** | `#F87171` | `rgba(239, 68, 68, 0.16)` | `#DC2626` | CVEs críticos, alertas urgentes de seguridad o emergencia |

---

## 4. Tipografía y Escalas de Texto

- **Sans principal (`--font-sans`):** `Figtree` para titulares, extractos y navegación.
- **Monospace técnica (`--font-mono`):** `JetBrains Mono` (fallback sistema `Menlo`/`monospace`) para timestamps, badges de `type`/`priority`, dominio `feed.raupulus.dev` y rutas RSS.

| Elemento | Clases Tailwind recomendadas |
| :--- | :--- |
| **H1 (Cabecera de página)** | `text-2xl sm:text-3xl font-bold tracking-tight text-text` |
| **H2 (Secciones / Páginas legales)** | `text-xl sm:text-2xl font-semibold text-text` |
| **Titular de tarjeta** | `text-base sm:text-lg font-semibold leading-snug text-text group-hover:text-primary transition` |
| **Extracto (*snippet*, máx. ~160 chars)** | `text-sm text-text-secondary leading-relaxed line-clamp-3` |
| **Metadatos (fuente, fecha)** | `font-mono text-xs text-text-muted` |
| **Badges (`type`, `priority`, `live`)** | `font-mono text-[11px] font-medium uppercase tracking-wider` |

---

## 5. Estructura de Layout y Tarjetas (`feed.raupulus.dev`)

1. **Header (`h-16`, `sticky top-0 z-30 bg-bg/90 backdrop-blur border-b border-border`):**
   - Izquierda: logotipo SVG (`/images/logos/logo.svg`) + marca `beacon-sift`.
   - Centro/Derecha: enlaces `Noticias` (`/`), `Fuentes` (`/sources`), botón `RSS` (`/feed.rss` heredando filtros) e indicador en vivo WebSocket (`● Live` en `#10B981`).
2. **Barra de filtros en `/`:**
   - Buscador por palabra clave (`q`) + selectores/píldoras de `type`, `priority` y `source` reflejados siempre en query string.
   - Botón rápido para limpiar filtros y enlace directo al `/feed.rss` con los filtros actuales.
3. **Grid de tarjetas de noticias (`max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6`):**
   - Grid responsive (`grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5`).
   - Cada tarjeta (`bg-surface border border-border hover:border-primary/50 rounded-xl overflow-hidden transition`):
     - **Cabecera visual (16:9):** imagen por **hotlink directo** (`loading="lazy"`, `referrerpolicy="no-referrer"`) si viene en el feed; en caso contrario o si falla `onerror`, **fallback HUD limpio** (fondo `#0B1324` con rejilla sutil, color de acento según prioridad e inicial/icono del `type`).
     - **Cuerpo:** fila superior con badge de `type` + badge de `priority` + fecha relativa/absoluta; titular enlazado al medio original (`target="_blank" rel="noopener noreferrer"`); extracto breve (~160 chars); pie con atribución a la fuente y enlace externo discreto.
4. **Pie de página (`border-t border-border bg-bg`):**
   - Enlaces legales simples (`/about`, `/privacy`, `/cookies`), aviso de agregación responsable y contacto `public@raupulus.dev`.

---

## 6. Assets de Marca Oficiales

| Uso | Ruta en `web/public/` | Formato / Medidas |
| :--- | :--- | :--- |
| **Favicon SVG** | `/favicon.svg` | Vectorial (`512×512` viewBox) |
| **Favicon ICO** | `/favicon.ico` | Multi-resolución (`16`, `32`, `48`) |
| **Apple Touch Icon** | `/apple-touch-icon.png` y `/images/logos/logo-180x180.png` | `180×180px` PNG |
| **Logotipo cabecera** | `/images/logos/logo.svg` | Vectorial SVG |
| **Banner horizontal** | `/images/logos/logo-1200x300.webp` | `1200×300px` WebP |
| **Preview redes (Open Graph / Twitter)** | `/images/redes-sociales/og-image-1200x630.png` y `.webp` | `1200×630px` |
| **Perfiles sociales cuadrados** | `/images/redes-sociales/{facebook,instagram,twitter}/` | `128`, `250`, `500`, `1200px` WebP |

---

> Creado: 2026-11-20 · Última revisión: 2026-11-21
