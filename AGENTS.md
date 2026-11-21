# AGENTS.md — Guía de contexto y reglas para `beacon-sift-web`

*Última actualización: 2026-11-21*

Este documento define las directrices obligatorias, arquitectura, protocolo residente de documentación y convenciones técnicas del servicio independiente **`beacon-sift-web`** (`https://feed.raupulus.dev`).

---

## 1. Propósito del servicio

**`beacon-sift-web`** es un portal público de solo lectura que agrega noticias técnicas, avisos locales y alertas de seguridad (CVEs) desde fuentes configuradas en JSON:

- **Sin backend, sin usuarios y sin panel admin:** No existen rutas de login, registro, Filament ni Sanctum.
- **Persistencia en PostgreSQL global:** Almacena fuentes (`sources`), entradas normalizadas (`news_items`) y auditoría de ejecuciones (`source_fetch_logs`).
- **Distribución abierta:** Interfaz web cronológica en `/`, listado `/sources`, feed `/feed.rss` filtrable, 4 endpoints `GET` JSON de solo lectura (`/api/v1/*`) y difusión en vivo vía **WebSockets públicos (Laravel Reverb)**.

---

## 2. Aislamiento del repositorio independiente y ecosistema

Este directorio es un **repositorio Git independiente y autocontenido** (`raupulus/beacon-sift-web`):

- **Nunca** acoplarse a nivel de código con los bots independientes del ecosistema ni depender de archivos externos de una raíz común.
- **Ecosistema independiente de `beacon-sift` (consumidores de esta web/API/Reverb):**
  - Bot de Telegram: [GitHub](https://github.com/raupulus/beacon-sift-bot-telegram) · [GitLab](https://gitlab.com/raupulus/beacon-sift-bot-telegram)
  - Bot de Discord: [GitHub](https://github.com/raupulus/beacon-sift-bot-discord) · [GitLab](https://gitlab.com/raupulus/beacon-sift-bot-discord)
- **Estabilidad de contrato público:** los endpoints `GET /api/v1/{status,sources,types,items}`, el feed `/feed.rss` y el canal Reverb `beacon.news` (evento `news.created`) son consumidos por ambos bots externos. Antes de modificar campos, filtros o formatos de payload, documenta el cambio en [`docs/info/api-rest.md`](./docs/info/api-rest.md) / [`docs/info/websockets.md`](./docs/info/websockets.md) y evalúa el impacto en los clientes.

---

## 3. Stack técnico y estándares de código

- **PHP 8.5:** Tipado estricto en parámetros y retornos, backed enums (`Priority`, `SourceFormat`, `FetchStatus`) y clases `readonly` donde proceda.
- **Laravel 13 + PostgreSQL (`DB_CONNECTION=pgsql`):** Obligatorio en producción y desarrollo; las pruebas automatizadas en `phpunit.xml` usan SQLite en memoria, por lo que toda consulta específica de PostgreSQL (p. ej. `to_tsvector` / índice GIN) debe mantener guardia `DB::getDriverName() === 'pgsql'` con fallback compatible.
- **Frontend:** Blade + **Tailwind CSS v4** (`@theme` en `resources/css/app.css`) + **Alpine.js 3** + **Laravel Echo / Pusher JS** compilados con **Vite 8 + pnpm**. Sin `tailwind.config.js` ni Vue.
- **Formato y calidad:** PSR-12 estricto mediante `composer run pint` y validación obligatoria con `composer run check` antes de cerrar cambios.
- **Idioma:**
  - Código (clases, métodos, propiedades, rutas) y **mensajes de log (`Log::*`)**: **inglés**.
  - Comentarios, PHPDocs, textos de UI y documentación: **español** *(campos de feeds o APIs de terceros tal como llegan)*.
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
├── AGENTS.md                        # Reglas, arquitectura y protocolo residente de documentación
├── CLAUDE.md -> AGENTS.md           # Symlink obligatorio al protocolo principal
├── LICENSE                          # Licencia GNU AGPLv3 (AGPL-3.0-or-later)
├── README.md                        # Documentación general del servicio web
├── .env.example                     # Variables de desarrollo (sin secretos)
├── .env.example.production          # Plantilla de producción (sin secretos)
├── .gitignore                       # Exclusiones (incluye /sources.json, /storage/app/sources.json, /docs/planning/, /docs/auditorias/)
├── sources.example.json             # Plantilla declarativa de fuentes
├── app/
│   ├── Console/Commands/            # SyncSourcesCommand, FetchFeedsCommand, PruneBeaconDataCommand
│   ├── Enums/                       # Priority, SourceFormat, FetchStatus
│   ├── Events/                      # NewsItemCreated (ShouldBroadcastNow -> Reverb)
│   ├── Helpers/                     # helpers.php (format_date)
│   ├── Http/
│   │   ├── Controllers/Api/V1/      # StatusApiController, SourceApiController, TypeApiController, NewsItemApiController
│   │   ├── Controllers/Web/         # FeedController, SourcesPageController, RssFeedController, StaticPageController
│   │   ├── Middleware/              # SetLocale, SecurityHeaders
│   │   └── Resources/               # NewsItemResource, SourceResource
│   ├── Models/                      # Source, NewsItem, SourceFetchLog
│   ├── Providers/                   # AppServiceProvider
│   └── Services/
│       ├── Ingestion/               # FeedIngestionService, RssAtomAdapter, JsonAdapter, CveNvdAdapter, ParsedFeedItem, TextSanitizer
│       ├── SeoService.php
│       └── SourceSyncService.php
├── config/                          # beacon.php, reverb.php, database.php, ...
├── database/
│   ├── factories/                   # SourceFactory, NewsItemFactory, SourceFetchLogFactory
│   └── migrations/                  # sources, news_items, source_fetch_logs + cache/jobs
├── docs/
│   ├── info/                        # Documentación técnica VIVA del servicio
│   │   ├── README.md                # Índice maestro de docs/info/
│   │   ├── _MODULE_TEMPLATE.md      # Plantilla de 9 apartados para módulos nuevos
│   │   ├── decisiones-tecnicas.md   # Decisiones deliberadas de arquitectura
│   │   ├── DESIGN.md                # Paleta radar HUD y tokens Tailwind v4
│   │   ├── COMPONENTS.md            # Layout, <x-news-card> y páginas de error
│   │   ├── frontend.md              # Rutas web, Alpine.js y páginas legales
│   │   ├── ingesta.md               # sources.json, tablas PostgreSQL y adaptadores
│   │   ├── api-rest.md              # Endpoints públicos /api/v1/* y /feed.rss
│   │   ├── websockets.md            # Canal Reverb beacon.news / news.created
│   │   ├── commands.md              # Comandos Artisan, Scheduler y Composer
│   │   ├── enums.md                 # Priority, SourceFormat, FetchStatus
│   │   └── servicio-seo.md          # SeoService
│   └── deploys/                     # Plantillas de despliegue servidor (apache.conf, nginx.conf, deploy.sh)
├── public/                          # favicon.*, apple-touch-icon.png, site.webmanifest, images/logos/, images/redes-sociales/
├── resources/
│   ├── css/app.css                  # Tokens @theme de DESIGN.md
│   ├── js/app.js                    # Alpine + Laravel Echo (Reverb)
│   └── views/                       # layouts/app, components/news-card, home, sources, rss, about, privacy, cookies, errors/
├── routes/                          # web.php, api.php, channels.php, console.php
└── tests/                           # Unit/Ingestion/* y Feature/* (23 tests)
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

## 7. Documentación

Protocolo obligatorio aplicable a **todas** las tareas presentes y futuras en este repositorio.

### Estructura bajo `docs/`
- [`docs/info/`](./docs/info/README.md) — documentación técnica **VIVA** del proyecto (`<modulo>.md`, `DESIGN.md`, `COMPONENTS.md`, `commands.md`, `decisiones-tecnicas.md`, `_MODULE_TEMPLATE.md` y, si se integra alguna API de terceros, `apis/<api>.md`).
- `docs/apis/<api>/` — crear **solo** si se integra una API oficial de terceros que requiera referencia verificada (`README.md`, `00-fundamentos.md`, `ERRATAS.md`, `LIMITACIONES.md`, dominios y `src/` intacto).
- [`docs/deploys/`](./docs/deploys/deploy.sh) — guías y plantillas de despliegue.
- `docs/future/` — crear **solo** cuando exista una idea decidida y aplazada (prohibidas carpetas vacías).
- `docs/planning/` (`<NN>-<fase>.md`, `dudas/`, `archived/`) y `docs/auditorias/` — **[NO GIT · EFÍMERO · POR DESARROLLADOR]**. Deben permanecer en [`.gitignore`](./.gitignore) (`/docs/planning/` y `/docs/auditorias/`). **Prohibido enlazarlos desde ningún archivo versionado ni crear `.gitkeep` dentro.**

### Reglas permanentes (1–13)
1. **Mismo commit:** toda tarea de código termina con su documentación actualizada **en el mismo commit**.
2. **Jerarquía de verdad:** `código > docs/info/ > AGENTS.md > resto`. `docs/planning/`, `docs/future/` y `docs/auditorias/` nunca son fuente de verdad del estado actual.
3. **Discrepancias:** si se detecta una diferencia entre documentación y código, se corrige en el mismo commit en que se detecta.
4. **Ciclo de módulos:** módulo modificado → actualizar su `.md`; módulo nuevo → crear desde [`_MODULE_TEMPLATE.md`](./docs/info/_MODULE_TEMPLATE.md) e indexar en [`docs/info/README.md`](./docs/info/README.md) y en este `AGENTS.md`; módulo eliminado → borrar su `.md` y sacarlo de **todos** los índices.
5. **Pie obligatorio en `docs/`:** todo `.md` propio bajo `docs/` (excluyendo snapshots originales en `docs/apis/<api>/src/`) termina con separador `---` y la línea exacta:
   `> Creado: YYYY-MM-DD · Última revisión: YYYY-MM-DD`
   La fecha de creación no cambia nunca; la de revisión se actualiza en el commit del cambio.
6. **Checklists verificables:** en planificaciones (`- [ ]`), marcar `[x]` significa verificado funcionando y cumpliendo, no simplemente escrito.
7. **Ciclo efímero (`docs/planning/` y `docs/auditorias/`):** crear → trabajar → verificar → promocionar lo duradero → **BORRAR** (`archived/` es sala de espera temporal, no histórico).
8. **Promoción antes de borrar lo efímero:** comportamiento de módulo → `docs/info/<modulo>.md`; decisión deliberada → `docs/info/decisiones-tecnicas.md`; trampa duradera → tabla de trampas de `AGENTS.md`; cambio de arquitectura, rutas o comandos → `AGENTS.md` / `commands.md`; idea aplazada → `docs/future/`; regresión → test automatizado.
9. **Integridad de enlaces:** nada versionado puede enlazar a `docs/planning/` ni a `docs/auditorias/`, ni se sacarán de `.gitignore`.
10. **Lectura dirigida:** al tocar un módulo lee **solo** su `.md`; si tocas frontend o Blade añades [`DESIGN.md`](./docs/info/DESIGN.md) y [`COMPONENTS.md`](./docs/info/COMPONENTS.md); si tocas una API de terceros añades `docs/apis/<api>/` en orden (`README.md` → `00-fundamentos.md` + `ERRATAS.md` + `LIMITACIONES.md` → solo el dominio necesario).
11. **Verificación real de APIs externas:** nunca configurar nada contra una API de terceros sin comprobarlo con una petición real; lo no comprobado se marca explícitamente `⚠️ sin verificar`.
12. **Idiomas:** documentación, comentarios, PHPDocs y textos de usuario en **español**; identificadores, ficheros, rutas y mensajes de log en **inglés**.
13. **Atribución y limpieza:** usar `@raupulus` y `public@raupulus.dev` cuando aplique. Prohibidas firmas de agente en commits, PRs o documentación (`Co-Authored-By`, «Generated with…», identificadores de sesión).

### Disparadores y plantillas rápidas
- **Disparadores clave:** cambio en módulo/esquema/rutas → `docs/info/<modulo>.md`; frontend/estilos → `DESIGN.md` y/o `COMPONENTS.md`; nuevo comando → `commands.md`; cambio de directorios → árbol de `AGENTS.md`; cambio en contrato público → comprobar impacto en los bots hermanos; planificación/auditoría/duda → efímero y promoción al cerrar.
- **Plantillas:** Módulo ([`_MODULE_TEMPLATE.md`](./docs/info/_MODULE_TEMPLATE.md)); Fase (Objetivo · Contexto · Alcance · Trabajo con checklist · Criterio de cierre); Duda (`D<NN>` · Estado · Qué bloquea · Contexto · Opciones · Respuesta · Consecuencia aplicada); Auditoría (cabecera fecha/revisión/alcance/método · severidades · hallazgos `<PREFIJO>-C01` con cómo se verificó).

### Índice maestro de `docs/info/`
- **Índice general:** [`docs/info/README.md`](./docs/info/README.md)
- **Plantilla de módulo:** [`docs/info/_MODULE_TEMPLATE.md`](./docs/info/_MODULE_TEMPLATE.md)
- **Decisiones técnicas:** [`docs/info/decisiones-tecnicas.md`](./docs/info/decisiones-tecnicas.md)
- **Comandos:** [`docs/info/commands.md`](./docs/info/commands.md)
- **Frontend:** [`docs/info/DESIGN.md`](./docs/info/DESIGN.md) · [`docs/info/COMPONENTS.md`](./docs/info/COMPONENTS.md) · [`docs/info/frontend.md`](./docs/info/frontend.md)
- **Módulos:** [`docs/info/ingesta.md`](./docs/info/ingesta.md) · [`docs/info/api-rest.md`](./docs/info/api-rest.md) · [`docs/info/websockets.md`](./docs/info/websockets.md) · [`docs/info/enums.md`](./docs/info/enums.md) · [`docs/info/servicio-seo.md`](./docs/info/servicio-seo.md)

### Tabla de trampas conocidas
| Trampa | Módulos afectados | Efecto / Cómo evitarla |
|---|---|---|
| SQL específico PostgreSQL vs tests SQLite | `migrations/2026_05_01_000002_*`, `NewsItem::scopeSearch()` | `phpunit.xml` usa SQLite `:memory:`; toda sintaxis `pgsql` (`GIN`, `to_tsvector`, `ILIKE`) debe quedar tras `DB::getDriverName() === 'pgsql'`. |
| Directiva `@error` de Blade vs Alpine.js | `resources/views/**/*.blade.php` | Usar siempre `x-on:error="..."` en vez de `@error="..."` dentro de vistas Blade para evitar errores de compilación `Unclosed if`. |
| `sources.json` local excluido en Git | `config/beacon.php`, `SourceSyncService` | Versionar solo `sources.example.json`; `storage/app/sources.json` y `/sources.json` están ignorados en `.gitignore`. |
| Ruptura de contrato `/api/v1/*` o `news.created` | `Api/V1/*`, `NewsItemCreated`, `NewsItemResource` | Los bots externos (`beacon-sift-bot-telegram`, `beacon-sift-bot-discord`) dependen del esquema JSON exacto. |
| Plantillas `docs/deploys/*` heredadas del skeleton | `docs/deploys/{apache.conf,nginx.conf,deploy.sh}` | Ajustar dominio `feed.raupulus.dev`, ruta real, PHP `8.5` y proceso Reverb (`reverb:start` + scheduler) al configurar el VPS. |

### Checklist antes de dar cualquier tarea por terminada
- [ ] Documentación del módulo tocado actualizada en el **mismo commit**
- [ ] Fechas de `Última revisión` al día en los archivos tocados
- [ ] Módulos nuevos indexados en `docs/info/README.md` y en este `AGENTS.md`
- [ ] Módulos eliminados fuera de todos los índices
- [ ] Ningún archivo versionado enlaza a `docs/planning/` ni a `docs/auditorias/`
- [ ] `/docs/planning/` y `/docs/auditorias/` siguen en `.gitignore`
- [ ] Lo duradero de planificaciones o auditorías cerradas promocionado, y los ficheros efímeros borrados
- [ ] Validado con `composer run check` y checklists `[x]` verificados
- [ ] Árbol de estructura de este `AGENTS.md` refleja los directorios reales

---

## 8. Política de Git y calidad

- Commits atómicos (`feat: ...`, `fix: ...`, `chore: ...`, `docs: ...`) **sin firmas de agente**.
- Validación obligatoria antes de cerrar cambios:
  - `composer run check` *(Laravel Pint `--test` + PHPUnit)*

---

## 9. Privacidad, autoría y seguridad

- **Autor:** Raúl Caro Pastorino (`@raupulus`) — [https://raupulus.dev](https://raupulus.dev)
- **Licencia:** GNU Affero General Public License v3.0 (`AGPL-3.0-or-later`, ver [`LICENSE`](./LICENSE))
- **Correo personal estrictamente prohibido:** NUNCA utilizar bajo ningún concepto ningún correo personal o privado del creador.
- **Único correo público permitido:** `public@raupulus.dev`
- **Sin secretos ni archivos locales en Git:** Nunca versionar `.env`, `storage/app/sources.json`, `sources.json`, claves privadas ni rutas locales de usuario.
