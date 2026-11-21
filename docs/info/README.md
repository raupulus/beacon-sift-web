# Índice de Documentación Técnica (`docs/info/`)

Documentación técnica viva del servicio **`beacon-sift-web`** (`https://feed.raupulus.dev`).

> **Lectura dirigida:** al trabajar en un módulo lee **únicamente** su `.md` en `docs/info/`. Si tocas frontend o estilos Blade/Tailwind, añade obligatoriamente [`DESIGN.md`](./DESIGN.md) y [`COMPONENTS.md`](./COMPONENTS.md).

---

## Guías transversales y plantilla

| Archivo | Propósito |
| :--- | :--- |
| [decisiones-tecnicas.md](decisiones-tecnicas.md) | Decisiones deliberadas de arquitectura (PostgreSQL + fallback SQLite en tests, contratos públicos para bots, agregación responsable, Tailwind v4) |
| [commands.md](commands.md) | Comandos Artisan (`sources:sync`, `feeds:fetch`, `beacon:prune`), Scheduler y comandos de calidad (`composer run check`) |
| [_MODULE_TEMPLATE.md](_MODULE_TEMPLATE.md) | Plantilla oficial de 9 apartados para documentar módulos nuevos |

---

## Frontend y diseño

| Archivo | Propósito |
| :--- | :--- |
| [DESIGN.md](DESIGN.md) | Paleta radar HUD derivada del logotipo, tokens Tailwind CSS v4 (`@theme`), prioridades y tarjetas |
| [COMPONENTS.md](COMPONENTS.md) | Componentes Blade reutilizables (`<x-news-card>`), layout principal y páginas de error |
| [frontend.md](frontend.md) | Rutas web públicas, filtros en URL, Alpine.js y páginas legales estáticas |

---

## Módulos de backend, ingesta y contratos públicos

| Archivo | Propósito |
| :--- | :--- |
| [ingesta.md](ingesta.md) | Esquema `sources.json`, PostgreSQL (`sources`, `news_items`, `source_fetch_logs`), adaptadores y retención |
| [api-rest.md](api-rest.md) | 4 endpoints públicos `GET /api/v1/*` de solo lectura y feed `/feed.rss` |
| [websockets.md](websockets.md) | Contrato de difusión pública con Laravel Reverb (`beacon.news` / `news.created`) para navegador y bots |
| [enums.md](enums.md) | Backed enums (`Priority`, `SourceFormat`, `FetchStatus`) |
| [servicio-seo.md](servicio-seo.md) | `SeoService` y etiquetas Open Graph / Twitter Card / RSS |

---

## Plantillas de despliegue servidor (`docs/deploys/`)

- [`docs/deploys/apache.conf`](../deploys/apache.conf) · [`docs/deploys/nginx.conf`](../deploys/nginx.conf) · [`docs/deploys/deploy.sh`](../deploys/deploy.sh) *(plantillas de referencia del servidor heredadas de la base Laravel; adaptar dominio, usuario y demonio de `reverb:start` al desplegar en VPS)*

---

> Creado: 2026-11-20 · Última revisión: 2026-11-21
