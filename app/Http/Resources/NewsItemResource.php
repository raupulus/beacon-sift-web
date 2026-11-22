<?php

namespace App\Http\Resources;

use App\Models\NewsItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso JSON normalizado para entradas de beacon-sift (API y WebSockets).
 *
 * @mixin NewsItem
 */
class NewsItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return self::formatItem($this->resource);
    }

    /**
     * Serializa un NewsItem al contrato único usado tanto en API como en WebSockets.
     *
     * @return array<string, mixed>
     */
    public static function formatItem(NewsItem $item): array
    {
        $source = $item->relationLoaded('source') ? $item->source : null;

        return [
            'id' => $item->id,
            'title' => $item->title,
            'url' => $item->url,
            'snippet' => $item->snippet,
            'image_url' => $item->image_url,
            'author' => $item->author,
            'type' => $item->type,
            'priority' => $item->priority->value,
            'priority_label' => $item->priority->label(),
            'priority_weight' => $item->priority_weight,
            'tags' => $item->tags ?? [],
            'metadata' => $item->metadata,
            'published_at' => $item->published_at->copy()->utc()->toIso8601String(),
            'fetched_at' => $item->fetched_at->copy()->utc()->toIso8601String(),
            'source' => $source !== null ? [
                'slug' => $source->slug,
                'name' => $source->name,
                'url' => $source->url,
            ] : null,
        ];
    }
}
