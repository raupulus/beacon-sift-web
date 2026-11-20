# Módulo: Comandos Artisan y Scheduler

*Última actualización: 2026-11-20*

> Comandos propios de consola para sincronización declarativa de fuentes, ingesta HTTP y retención automática en PostgreSQL.

---

## 1. Catálogo de comandos

| Comando | Opciones | Descripción |
| :--- | :--- | :--- |
| `php artisan sources:sync` | `--path=` | Lee `sources.json` (o ruta indicada), valida formatos/campos y hace upsert en `sources`. Desactiva fuentes eliminadas del JSON manteniendo sus noticias en `news_items`. |
| `php artisan feeds:fetch` | `--source=`, `--force` | Ejecuta `syncIfChanged()` para detectar modificaciones en `sources.json`, e ingesta fuentes activas vencidas (`dueForFetch`) o todas las activas (`--force`) o una concreta (`--source=slug`). |
| `php artisan beacon:prune` | *(ninguna)* | Purga `source_fetch_logs` anteriores a `BEACON_FETCH_LOGS_RETENTION_DAYS` (`30` por defecto) y opcionalmente `news_items` si `BEACON_ITEMS_RETENTION_DAYS > 0` (`0` = sin borrado por defecto). |

---

## 2. Programación (`routes/console.php`)

- `feeds:fetch`: cada minuto (`->everyMinute()->withoutOverlapping()`), respetando internamente el `interval_minutes` (`next_fetch_at`) de cada fuente para no sondear antes de tiempo.
- `beacon:prune`: diariamente a las `03:30` (`->dailyAt('03:30')`).

---

## 3. Archivos implicados

- `app/Console/Commands/SyncSourcesCommand.php`
- `app/Console/Commands/FetchFeedsCommand.php`
- `app/Console/Commands/PruneBeaconDataCommand.php`
- `routes/console.php`
- `tests/Feature/SourceSyncTest.php`, `tests/Feature/FeedIngestionTest.php`
