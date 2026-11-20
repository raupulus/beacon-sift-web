# Módulo: WebSockets Públicos (Laravel Reverb)

*Última actualización: 2026-11-20*

> Difusión en tiempo real de nuevas noticias ingestadas hacia el navegador (`feed.raupulus.dev`) y los bots independientes (`bot-telegram`, `bot-discord`).

---

## 1. Contrato de emisión

- **Driver:** `reverb` (`BROADCAST_CONNECTION=reverb`, configuración en `config/reverb.php`).
- **Canal público:** `beacon.news` (`config('beacon.broadcast_channel')`, declarado sin autenticación en `routes/channels.php`).
- **Evento Laravel:** `App\Events\NewsItemCreated` (`implements ShouldBroadcastNow`).
- **Nombre de evento en el socket:** `news.created` (`broadcastAs(): 'news.created'`).

### Payload emitido

```json
{
  "item": {
    "id": 42,
    "title": "DSA-6643-1 pam-mysql - security update",
    "url": "https://www.debian.org/security/2026/dsa-6643",
    "snippet": "Extracto breve limpio...",
    "image_url": null,
    "author": null,
    "type": "cve",
    "priority": "high",
    "priority_label": "Alta",
    "priority_weight": 3,
    "tags": ["debian", "security", "cve"],
    "metadata": null,
    "published_at": "2026-11-20T10:00:00+00:00",
    "fetched_at": "2026-11-20T15:25:30+01:00",
    "source": {
      "slug": "debian-security",
      "name": "Debian Security Advisories",
      "url": "https://www.debian.org/security/dsa"
    }
  }
}
```

---

## 2. Consumo en navegador y en bots Python

- **Navegador (`resources/js/app.js`):** Laravel Echo + `pusher-js` se conecta al servidor Reverb usando `VITE_REVERB_*`, emite eventos DOM `beacon:ws-status` (`connecting`, `connected`, `offline`) y `beacon:news-created` para inyectar tarjetas en directo respetando los filtros activos en `/`.
- **Bots Python:** Conectan al endpoint WebSocket protocolo Pusher v7 (`ws(s)://{REVERB_HOST}:{REVERB_PORT}/app/{REVERB_APP_KEY}?protocol=7&client=python&version=1.0`), envían `{"event":"pusher:subscribe","data":{"channel":"beacon.news"}}` y procesan cada evento `news.created`.
