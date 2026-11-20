<?php

namespace App\Events;

use App\Http\Resources\NewsItemResource;
use App\Models\NewsItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento WebSocket público emitido cuando se ingesta una noticia nueva.
 * Consumido en vivo por el navegador (feed.raupulus.dev) y por los bots.
 */
class NewsItemCreated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly NewsItem $newsItem)
    {
        $this->newsItem->loadMissing('source:id,slug,name,url');
    }

    /**
     * Canal público de difusión (sin autenticación).
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel((string) config('beacon.broadcast_channel', 'beacon.news')),
        ];
    }

    /**
     * Nombre explícito del evento en el canal WebSocket.
     */
    public function broadcastAs(): string
    {
        return 'news.created';
    }

    /**
     * Payload JSON emitido a los clientes conectados.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'item' => NewsItemResource::formatItem($this->newsItem),
        ];
    }
}
