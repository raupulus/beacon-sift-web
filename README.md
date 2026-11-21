<p align="left">
  <img src="./public/images/logos/logo-180x180.png" alt="beacon-sift" width="80" align="left" style="margin-right: 16px;">
  <h1>beacon-sift — Servicio Web (<code>feed.raupulus.dev</code>)</h1>
</p>

<br clear="left"/>

Portal público agregador de noticias y alertas en tiempo real, configurable mediante JSON, con interfaz cronológica estilo radar HUD, feed RSS propio filtrable, WebSockets públicos (Laravel Reverb) y API REST de solo lectura para consumo externo y de bots (`bot-telegram`, `bot-discord`).

---

## Características principales

- **Portal público de solo lectura:** Sin usuarios, sin registro/login ni panel de administración.
- **Ingesta multi-formato:** Adaptadores extensibles para `rss`, `atom`, `json` y `cve_nvd` con respeto a cabeceras HTTP (`ETag`, `Last-Modified`), deduplicación por `fingerprint` SHA-256 y extractos breves limpios (`~160` caracteres).
- **Agregación responsable:** Muestra solo titular, fecha, fuente, tipo, prioridad, extracto breve e imagen por hotlink directo (cuando el feed la incluye, sin descargar ni re-alojar imágenes de terceros), con enlace directo al medio original.
- **Tiempo real (WebSockets públicos):** Emisión mediante **Laravel Reverb** en el canal público `beacon.news` (`news.created`) para actualizar `/` en directo y alimentar los bots.
- **API pública de solo lectura (`/api/v1`) y RSS (`/feed.rss`):** 4 endpoints `GET` JSON (`/status`, `/sources`, `/types`, `/items`) y feed RSS 2.0 filtrable por `type`, `priority`, `min_priority`, `source` y `q`.

---

## Stack técnico

- **PHP 8.5** + **Laravel 13**
- **PostgreSQL** (`DB_CONNECTION=pgsql`, instalada globalmente en el sistema)
- **Laravel Reverb** + **Laravel Echo / Pusher JS**
- **Tailwind CSS v4** + **Alpine.js 3** + **Vite 8** + **pnpm**
- **Calidad:** Laravel Pint (`composer run pint`) y PHPUnit (`composer run check`)

---

## Instalación rápida en desarrollo local

```bash
# 1. Instalar dependencias PHP y frontend
composer install
pnpm install

# 2. Configurar entorno y fuentes
cp .env.example .env
cp sources.example.json storage/app/sources.json

# 3. Ajustar credenciales de PostgreSQL en .env:
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=beacon_sift
# DB_USERNAME=dev
# DB_PASSWORD=

# 4. Clave, migraciones y compilación de assets
php artisan key:generate
php artisan storage:link
php artisan migrate
pnpm run build

# 5. Sincronizar fuentes e ingestar
php artisan sources:sync
php artisan feeds:fetch --force

# 6. Levantar servidor web, Reverb (WebSockets), logs y Vite
composer run dev
```

---

## Comandos Artisan propios

| Comando | Descripción |
| :--- | :--- |
| `php artisan sources:sync {--path=}` | Sincroniza `sources.json` con la tabla `sources` en PostgreSQL (desactiva fuentes retiradas sin borrar su histórico). |
| `php artisan feeds:fetch {--source=} {--force}` | Auto-sincroniza `sources.json` si cambió e ingesta las fuentes pendientes respetando `interval_minutes`, `ETag` y `Last-Modified`. |
| `php artisan beacon:prune` | Elimina logs en `source_fetch_logs` mayores a 30 días (configurable) y noticias según `BEACON_ITEMS_RETENTION_DAYS` (`0` = sin borrado por defecto). |

---

## Rutas públicas y API

- **Web:** `/`, `/sources`, `/feed.rss`, `/about`, `/privacy`, `/cookies`
- **API JSON v1 (`throttle:60,1`):**
  - `GET /api/v1/status`
  - `GET /api/v1/sources`
  - `GET /api/v1/types`
  - `GET /api/v1/items`
- **WebSocket público (Reverb):** Canal `beacon.news`, evento `news.created`.

---

## Documentación técnica y ecosistema `beacon-sift`

- **Guía de desarrollo y arquitectura:** [`AGENTS.md`](./AGENTS.md)
- **Índice técnico vivo (`docs/info/`):** [`docs/info/README.md`](./docs/info/README.md)

Repositorios independientes del ecosistema que consumen esta API pública y el canal Reverb:
- **Bot de Telegram:** [GitHub (`raupulus/beacon-sift-bot-telegram`)](https://github.com/raupulus/beacon-sift-bot-telegram) · [GitLab](https://gitlab.com/raupulus/beacon-sift-bot-telegram)
- **Bot de Discord:** [GitHub (`raupulus/beacon-sift-bot-discord`)](https://github.com/raupulus/beacon-sift-bot-discord) · [GitLab](https://gitlab.com/raupulus/beacon-sift-bot-discord)

---

## Autoría y contacto

Creado por **Raúl Caro Pastorino (`@raupulus`)** — [https://raupulus.dev](https://raupulus.dev)

- **GitHub:** [https://github.com/raupulus](https://github.com/raupulus)
- **GitLab:** [https://gitlab.com/raupulus](https://gitlab.com/raupulus)
- **Correo público:** `public@raupulus.dev`
