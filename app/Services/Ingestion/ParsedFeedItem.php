<?php

namespace App\Services\Ingestion;

use App\Enums\Priority;
use Carbon\Carbon;

/**
 * DTO inmutable que representa una entrada normalizada extraída por un adaptador.
 */
readonly class ParsedFeedItem
{
    /**
     * @param  array<int, string>  $tags
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public ?string $externalId,
        public string $title,
        public string $url,
        public ?string $snippet,
        public ?string $imageUrl,
        public ?string $author,
        public Carbon $publishedAt,
        public array $tags = [],
        public ?array $metadata = null,
        public ?Priority $priorityOverride = null,
    ) {}
}
