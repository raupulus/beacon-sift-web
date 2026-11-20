# Módulo: Ingesta, Esquema JSON y Persistencia PostgreSQL

*Última actualización: 2026-11-20*

> Sincronización declarativa de fuentes desde `sources.json`, ingesta multi-formato con caché HTTP (`ETag`/`Last-Modified`), deduplicación en PostgreSQL y política de agregación responsable.

---

## 1. Configuración (`config/beacon.php` y `sources.example.json`)

En el repositorio se versiona `sources.example.json`. El archivo real (`storage/app/sources.json` o la ruta indicada en `BEACON_SOURCES_PATH`) permanece excluido en `.gitignore`.

```json
{
  "version": "1.0",
  "defaults": {
    "enabled": true,
    "priority": "medium",
    "interval_minutes": 30,
    "snippet_max_length": 160
  },
  "sources": [
    {
      "id": "debian-security",
      "name": "Debian Security Advisories",
      "url": "https://www.debian.org/security/dsa",
      "format": "rss",
      "type": "cve",
      "priority": "high",
      "interval_minutes": 30,
      "language": "en",
      "enabled": true,
      "tags": ["debian", "security", "cve"],
      "adapter_options": {}
    }
  ]
}
```

### Opciones de `JsonAdapter` (`adapter_options`)
- `items_path`: ruta dot-notation al array de entradas (por defecto `"items"`, `""` si la raíz ya es una lista).
- `external_id_field`, `title_field`, `url_field`, `summary_field`, `image_field`, `author_field`, `published_at_field`, `tags_field`.

---

## 2. Tablas PostgreSQL

1. **`sources`**: Configuración sincronizada + estado HTTP (`etag`, `last_modified`, `last_fetched_at`, `next_fetch_at`, `last_success_at`, `last_status_code`, `last_error`, `consecutive_failures`).
2. **`news_items`**: Entradas deduplicadas por `fingerprint` (`sha256(source_id . '|' . (external_id ?: url))`), con `title`, `url`, `snippet` (máx. ~160 chars sin HTML), `image_url` (hotlink), `type`, `priority`, `priority_weight`, `published_at`, `fetched_at` e índice GIN `news_items_fts_idx` en PostgreSQL.
3. **`source_fetch_logs`**: Auditoría de cada intento de ingesta (`status`, `http_status`, `items_found`, `items_created`, `duration_ms`, `error_message`, `fetched_at`).

---

## 3. Adaptadores y sanitización (`app/Services/Ingestion/`)

- `FeedAdapterInterface`: Contrato único `parse(string $body, Source $source): array`.
- `RssAtomAdapter`: Soporta RSS 2.0, RSS 1.0 (RDF) y Atom 1.0; usa `<content:encoded>` como fallback cuando `<description>` viene vacío; extrae imágenes de `media:content`, `media:thumbnail`, `<enclosure>` y enlaces Atom; resuelve URLs relativas contra `$source->url`.
- `JsonAdapter`: Mapea feeds JSON arbitrarios resolviendo URLs relativas.
- `CveNvdAdapter`: Parsea la API 2.0 de NVD y ajusta la prioridad según puntuación CVSS (`>=9.0` critical, `>=7.0` high, `>=4.0` medium, `<4.0` low).
- `TextSanitizer`: Limpia HTML/entidades, trunca snippets a `snippet_max_length`, bloquea esquemas inseguros (`javascript:`, `data:`) y resuelve URLs relativas con `resolveUrl()`.
- `FeedIngestionService`: Envía `If-None-Match` e `If-Modified-Since`, gestiona respuestas `304 Not Modified`, guarda auditoría, deduplica y dispara `NewsItemCreated`.

---

## 4. Retención de datos

- `source_fetch_logs`: purga automática a los **30 días** (`BEACON_FETCH_LOGS_RETENTION_DAYS=30`).
- `news_items`: **sin borrado por defecto (`BEACON_ITEMS_RETENTION_DAYS=0`)**, configurable por entorno.
