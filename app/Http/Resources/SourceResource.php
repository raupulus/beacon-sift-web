<?php

namespace App\Http\Resources;

use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso JSON para fuentes activas en /api/v1/sources.
 *
 * @mixin Source
 */
class SourceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'url' => $this->url,
            'format' => $this->format->value,
            'type' => $this->type,
            'priority' => $this->priority->value,
            'priority_weight' => $this->priority_weight,
            'interval_minutes' => $this->interval_minutes,
            'language' => $this->language,
            'tags' => $this->tags ?? [],
            'is_active' => $this->is_active,
            'last_fetched_at' => $this->last_fetched_at?->toIso8601String(),
            'last_success_at' => $this->last_success_at?->toIso8601String(),
            'consecutive_failures' => $this->consecutive_failures,
        ];
    }
}
