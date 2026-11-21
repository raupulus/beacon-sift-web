# Módulo: API REST de Solo Lectura y Feed RSS

*Última actualización: 2026-11-20*

> Endpoints públicos `GET` JSON (`/api/v1/*`) con rate limiting (`throttle:60,1`) y feed RSS 2.0 propio (`/feed.rss`) para consumo de los bots (`bot-telegram`, `bot-discord`) y lectores externos.

---

## 1. Endpoints API (`/api/v1`)

Sin autenticación (portal público de solo lectura), acotados con `throttle:60,1` en `routes/api.php`:

| Método | URI | Controlador | Uso principal |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/status` | `StatusApiController` | Salud de API/BD, `last_sync_at`, conteo de fuentes/ítems y metadatos del canal WebSocket (`channel`, `event`). |
| `GET` | `/api/v1/sources` | `SourceApiController` | Listado paginado de fuentes (`?type=`, `?priority=`, `?active=true\|false`, `?per_page=`). |
| `GET` | `/api/v1/types` | `TypeApiController` | Lista de `type` activos con conteos y catálogo de prioridades (`low`, `medium`, `high`, `critical` con `weight`). |
| `GET` | `/api/v1/items` | `NewsItemApiController` | Consulta cronológica y búsqueda (`/latest`, `/search` y recuperación incremental). |

### Parámetros de `GET /api/v1/items`
- `type`: uno o varios tipos separados por coma (`cve,linux`).
- `min_priority`: filtra `priority_weight >= peso(min_priority)` (`low`..`critical`).
- `priority`: prioridad exacta.
- `source`: slug de la fuente.
- `q`: búsqueda en `title` y `snippet` (FTS + `ILIKE` en PostgreSQL).
- `since_id`: entero (`id > since_id`) para recuperación incremental tras reconexión.
- `order`: `desc` (por defecto) o `asc`.
- `per_page`: acotado entre `1` y `50` (por defecto `10`).

---

## 2. Feed RSS 2.0 propio (`GET /feed.rss`)

Controlador: `App\Http\Controllers\Web\RssFeedController` (`resources/views/rss.blade.php`).

- Soporta los mismos filtros (`type`, `min_priority`, `priority`, `source`, `q`, `limit` máx. `100`).
- Devuelve `Content-Type: application/rss+xml; charset=UTF-8` con cache HTTP `max-age=120` y escape seguro de `]]>` dentro de nodos `<![CDATA[...]]>`.

---

> Creado: 2026-11-20 · Última revisión: 2026-11-21
