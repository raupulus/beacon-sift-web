# AGENTS.md — Guía de contexto y reglas para `beacon-sift-web`

*Última actualización: 2026-11-20*

Este documento define las directrices obligatorias, arquitectura y convenciones técnicas del servicio **`web/`** de **beacon-sift** (`https://feed.raupulus.dev`).

---

## 1. Propósito del servicio

**`beacon-sift-web`** es un portal público de solo lectura que agrega noticias técnicas, avisos locales y alertas de seguridad (CVEs) desde fuentes configuradas en JSON:

- **Sin backend, sin usuarios y sin panel admin:** No existen rutas de login, registro, Filament ni Sanctum.
- **Persistencia en PostgreSQL global:** Almacena fuentes (`sources`), entradas normalizadas (`news_items`) y auditoría de ejecuciones (`source_fetch_logs`).
- **Distribución abierta:** Interfaz web cronológica en `/`, listado `/sources`, feed `/feed.rss` filtrable, 4 endpoints `GET` JSON de solo lectura (`/api/v1/*`) y difusión en vivo vía **WebSockets públicos (Laravel Reverb)**.

---

## 2. Aislamiento del repositorio independiente

Este directorio es un repositorio Git independiente (`beacon-sift-web`):

- **Nunca** acoplarse a nivel de código con `bot-telegram/` ni `bot-discord/`.
- Los bots consumen únicamente los WebSockets públicos (Reverb) y los 4 endpoints `GET` JSON públicos de `/api/v1/*`.

---

## 3. Stack técnico y estándares de código

- **PHP 8.5:** Tipado estricto en parámetros y retornos, backed enums (`Priority`, `SourceFormat`, `FetchStatus`) y clases `readonly` donde proceda.
- **Laravel 13 + PostgreSQL (`DB_CONNECTION=pgsql`):** Obligatorio en producción y desarrollo; las pruebas automatizadas en `phpunit.xml` usan SQLite en memoria, por lo que toda consulta específica de PostgreSQL (p. ej. `to_tsvector` / índice GIN) debe mantener guardia `DB::getDriverName() === 'pgsql'` con fallback compatible.
- **Frontend:** Blade + **Tailwind CSS v4** (`@theme` en `resources/css/app.css`) + **Alpine.js 3** + **Laravel Echo / Pusher JS** compilados con **Vite 8 + pnpm**. Sin `tailwind.config.js` ni Vue.
- **Formato y calidad:** PSR-12 estricto mediante `composer run pint` y validación obligatoria con `composer run check` antes de cerrar cambios.
- **Idioma:**
  - Código (clases, métodos, propiedades, rutas): **inglés**.
  - Comentarios, PHPDocs, mensajes UI y documentación: **español**.
- **Rutas dinámicas:** Usar siempre `route(...)` y `asset(...)`, nunca URLs hardcodeadas.

---

## 4. Directrices legales y agregación responsable

- Mostrar únicamente **titular enlazado al medio original, fuente, fecha, tipo, prioridad, extracto breve limpio** (`~160` caracteres) e **imagen por hotlink directo** cuando venga publicada en el propio feed (`loading="lazy"`, `referrerpolicy="no-referrer"`).
- **Prohibido** hacer scraping de cuerpos completos, saltarse *paywalls* o descargar/re-alojar imágenes de prensa de terceros en el servidor.
- Respetar cabeceras HTTP de caché (`ETag` / `If-None-Match` y `Last-Modified` / `If-Modified-Since`) e intervalos de sondeo por fuente.

---

## 5. Estructura de directorios principal

```text
web/
├── AGENTS.md
├── README.md
├── sources.example.json          # Plantilla declarativa de fuentes (sources.json en .gitignore)
├── app/
│   ├── Console/Commands/         # SyncSourcesCommand, FetchFeedsCommand, PruneBeaconDataCommand
│   ├── Enums/                    # Priority, SourceFormat, FetchStatus
│   ├── Events/                   # NewsItemCreated (ShouldBroadcastNow -> Reverb)
│   ├── Helpers/                  # helpers.php (format_date)
│   ├── Http/
│   │   ├── Controllers/Api/V1/   # StatusApiController, SourceApiController, TypeApiController, NewsItemApiController
│   │   ├── Controllers/Web/      # FeedController, SourcesPageController, RssFeedController, StaticPageController
│   │   ├── Middleware/           # SetLocale, SecurityHeaders
│   │   └── Resources/            # NewsItemResource, SourceResource
│   ├── Models/                   # Source, NewsItem, SourceFetchLog
│   ├── Providers/                # AppServiceProvider
│   └── Services/
│       ├── Ingestion/            # FeedIngestionService, RssAtomAdapter, JsonAdapter, CveNvdAdapter, ParsedFeedItem, TextSanitizer
│       ├── SeoService.php
│       └── SourceSyncService.php
├── config/                       # beacon.php, reverb.php, database.php, ...
├── database/
│   ├── factories/                # SourceFactory, NewsItemFactory, SourceFetchLogFactory
│   └── migrations/               # sources, news_items, source_fetch_logs + cache/jobs
├── docs/info/                    # Documentación técnica viva del proyecto
├── public/                       # favicon.*, apple-touch-icon.png, site.webmanifest, images/logos/, images/redes-sociales/
├── resources/
│   ├── css/app.css               # Tokens @theme de DESIGN.md
│   ├── js/app.js                 # Alpine + Laravel Echo (Reverb)
│   └── views/                    # layouts/app, components/news-card, home, sources, rss, about, privacy, cookies, errors/
├── routes/                       # web.php, api.php, channels.php, console.php
└── tests/                        # Unit/Ingestion/* y Feature/*
```

---

## 6. Mapa completo de rutas

### Web públicas (`routes/web.php`)
- `GET /` (`home`): Portada cronológica con filtros en URL (`type`, `priority`, `source`, `q`), paginación y tarjetas en vivo por WebSocket.
- `GET /sources` (`sources.index`): Listado de fuentes activas filtrable por `type` con indicador de salud.
- `GET /feed.rss` (`feed.rss`): Feed RSS 2.0 propio filtrable.
- `GET /about` (`about`), `GET /privacy` (`privacy`), `GET /cookies` (`cookies`): Páginas legales e informativas estáticas.
- `GET /up`: Health check nativo de Laravel.

### API pública de solo lectura (`routes/api.php`, `throttle:60,1`)
- `GET /api/v1/status` (`api.v1.status`): Salud de API/BD, última sincronización y metadatos de canal WebSocket.
- `GET /api/v1/sources` (`api.v1.sources`): Listado paginado de fuentes activas.
- `GET /api/v1/types` (`api.v1.types`): Tipos activos con conteo y catálogo de prioridades.
- `GET /api/v1/items` (`api.v1.items`): Listado paginado de noticias filtrable (`type`, `min_priority`, `priority`, `source`, `q`, `since_id`, `order`, `per_page`).

### WebSocket público (`routes/channels.php`)
- Canal público `beacon.news` (`NewsItemCreated` emitido como `news.created`).

---

## 7. Documentación técnica viva (`docs/info/`)

- Todo archivo dentro de `docs/info/` debe mantener la cabecera `*Última actualización: YYYY-MM-DD*` actualizada tras cada cambio funcional en su módulo.
- Índice maestro en [docs/info/README.md](docs/info/README.md).

---

## 8. Privacidad, autoría y seguridad

- **Autor:** Raúl Caro Pastorino (`@raupulus`) — [https://raupulus.dev](https://raupulus.dev)
- **Correo personal estrictamente prohibido:** NUNCA utilizar bajo ningún concepto ningún correo personal o privado del creador.
- **Único correo público permitido:** `public@raupulus.dev`
- **Sin secretos ni archivos locales en Git:** Nunca versionar `.env`, `storage/app/sources.json`, `sources.json`, claves privadas ni rutas locales de usuario.
