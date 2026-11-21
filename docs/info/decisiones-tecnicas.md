# Decisiones técnicas (`decisiones-tecnicas.md`)

Registro de decisiones deliberadas de arquitectura e implementación en `beacon-sift-web` cuyo porqué conviene preservar para evitar regresiones o simplificaciones erróneas.

---

## 1. Portal público de solo lectura (sin auth, Filament, Sanctum ni Vue)

- **Decisión:** Se eliminaron los módulos del skeleton base (login/registro, Filament, Sanctum, DomPDF, Scribe y Vue) dejando exclusivamente la interfaz pública en Blade + Alpine.js + Tailwind CSS v4.
- **Motivo:** `feed.raupulus.dev` es un agregador abierto sin cuentas de usuario ni panel de gestión web; la configuración de fuentes vive declarativamente en JSON (`sources.json`). Reducir dependencias disminuye superficie de ataque y coste de mantenimiento.

---

## 2. PostgreSQL global con índice GIN y guardia para tests SQLite

- **Decisión:** En producción y desarrollo se usa **PostgreSQL** (`DB_CONNECTION=pgsql`, instalada globalmente en el host) con índice GIN `news_items_fts_idx` y búsqueda `to_tsvector('simple', ...) @@ plainto_tsquery('simple', ?)`, pero tanto la migración (`database/migrations/2026_05_01_000002_create_news_items_table.php`) como el *scope* `NewsItem::scopeSearch()` envuelven la lógica PostgreSQL tras `DB::getDriverName() === 'pgsql'` con fallback `like` para otros drivers.
- **Motivo:** `phpunit.xml` ejecuta la suite sobre `sqlite` `:memory:` para rapidez y aislamiento en CI sin depender de servidor Postgres.

---

## 3. Contrato público estable para consumidores independientes (`api/v1` y Reverb)

- **Decisión:** Toda comunicación hacia `bot-telegram` y `bot-discord` ocurre únicamente mediante los **4 endpoints `GET` JSON de solo lectura** (`/api/v1/status`, `/api/v1/sources`, `/api/v1/types`, `/api/v1/items`, bajo `throttle:60,1`), el feed `/feed.rss` y el canal WebSocket público `beacon.news` (`App\Events\NewsItemCreated` con `ShouldBroadcastNow` emitiendo `news.created`).
- **Motivo:** Aislamiento estricto entre repositorios. Cambiar nombres de campos (`item`, `priority_weight`, `since_id`, `ws_channel`, etc.) o rutas rompería ambos bots externos.

---

## 4. Ingesta declarativa desde `sources.json` + agregación responsable

- **Decisión:** Las fuentes se versionan como plantilla en [`sources.example.json`](../../sources.example.json) y se leen en runtime desde `storage/app/sources.json` (o `BEACON_SOURCES_PATH`, excluido de Git), enviando `ETag` (`If-None-Match`) y `Last-Modified` (`If-Modified-Since`), deduplicando por `fingerprint` SHA-256 y sanitizando snippets (`~160` caracteres) sin scraping de artículos completos ni descarga/re-alojamiento de imágenes (solo *hotlink* directo con fallback HUD en Blade/Alpine cuando falla).
- **Motivo:** Cumplir las directrices legales de agregador responsable y evitar tráfico redundante a los feeds de origen.

---

## 5. Retención diferencial y limpieza programada (`beacon:prune`)

- **Decisión:** `source_fetch_logs` se purga por defecto a los **30 días** (`BEACON_FETCH_LOGS_RETENTION_DAYS=30`), mientras que `news_items` conserva histórico por defecto (`BEACON_ITEMS_RETENTION_DAYS=0`).
- **Motivo:** Conservar el histórico indexado y enlaces canónicos mientras se evita el crecimiento ilimitado de logs de auditoría de sondeo.

---

## 6. Tailwind CSS v4 CSS-first (`@theme`) y `pnpm`

- **Decisión:** Todos los tokens HUD viven en `@theme` dentro de `resources/css/app.css` compilado con Vite + `pnpm` (sin `tailwind.config.js`), y en plantillas Blade con Alpine.js se usa sintaxis explícita `x-on:error="..."` en lugar de `@error="..."` para evitar colisión con la directiva `@error()` de Blade.

---

> Creado: 2026-11-21 · Última revisión: 2026-11-21
