<?php

namespace App\Services\Ingestion;

use App\Enums\FetchStatus;
use App\Enums\SourceFormat;
use App\Events\NewsItemCreated;
use App\Models\NewsItem;
use App\Models\Source;
use App\Models\SourceFetchLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Orquesta la ingesta HTTP respetando cabeceras de caché (ETag, Last-Modified),
 * deduplicación por fingerprint SHA-256, auditoría en source_fetch_logs y
 * difusión en tiempo real mediante WebSockets públicos (NewsItemCreated).
 */
class FeedIngestionService
{
    public function __construct(
        private readonly RssAtomAdapter $rssAtomAdapter,
        private readonly JsonAdapter $jsonAdapter,
        private readonly CveNvdAdapter $cveNvdAdapter,
    ) {}

    /**
     * Ejecuta la ingesta para una fuente concreta.
     *
     * @return array{status: FetchStatus, found: int, created: int, error: ?string}
     */
    public function ingest(Source $source): array
    {
        $startedAt = microtime(true);
        $now = now()->utc();

        $headers = [
            'User-Agent' => (string) config('beacon.user_agent'),
            'Accept' => 'application/rss+xml, application/atom+xml, application/xml, application/json, text/xml;q=0.9, */*;q=0.8',
        ];

        if ($source->etag !== null && $source->etag !== '') {
            $headers['If-None-Match'] = $source->etag;
        }

        if ($source->last_modified !== null && $source->last_modified !== '') {
            $headers['If-Modified-Since'] = $source->last_modified;
        }

        try {
            $response = Http::timeout((int) config('beacon.default_timeout_seconds', 15))
                ->withHeaders($headers)
                ->get($source->url);

            $httpStatus = $response->status();
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            if ($httpStatus === 304) {
                $source->forceFill([
                    'last_fetched_at' => $now,
                    'last_success_at' => $now,
                    'next_fetch_at' => $now->copy()->addMinutes($source->interval_minutes),
                    'last_status_code' => 304,
                    'last_error' => null,
                    'consecutive_failures' => 0,
                ])->save();

                SourceFetchLog::query()->create([
                    'source_id' => $source->id,
                    'status' => FetchStatus::NotModified,
                    'http_status' => 304,
                    'items_found' => 0,
                    'items_created' => 0,
                    'duration_ms' => $durationMs,
                    'fetched_at' => $now,
                ]);

                return ['status' => FetchStatus::NotModified, 'found' => 0, 'created' => 0, 'error' => null];
            }

            if (! $response->successful()) {
                return $this->recordFailure(
                    $source,
                    "Respuesta HTTP {$httpStatus} desde {$source->url}",
                    $httpStatus,
                    $durationMs,
                );
            }

            $adapter = $this->resolveAdapter($source->format);
            $parsedItems = $adapter->parse($response->body(), $source);

            $maxItems = (int) ($source->adapter_options['max_items'] ?? 0);
            if ($maxItems > 0 && count($parsedItems) > $maxItems) {
                $parsedItems = array_slice($parsedItems, 0, $maxItems);
            }

            $createdItems = [];
            foreach ($parsedItems as $parsed) {
                $fingerprint = NewsItem::makeFingerprint($source->id, $parsed->externalId, $parsed->url);

                if (NewsItem::query()->where('fingerprint', $fingerprint)->exists()) {
                    continue;
                }

                $priority = $parsed->priorityOverride ?? $source->priority;
                $mergedTags = array_values(array_unique(array_merge($source->tags ?? [], $parsed->tags)));
                $publishedAt = $parsed->publishedAt->copy()->utc();
                if ($publishedAt->greaterThan($now)) {
                    $publishedAt = $now->copy();
                }

                $item = NewsItem::query()->create([
                    'source_id' => $source->id,
                    'external_id' => $parsed->externalId,
                    'fingerprint' => $fingerprint,
                    'title' => $parsed->title,
                    'url' => $parsed->url,
                    'snippet' => $parsed->snippet,
                    'image_url' => $parsed->imageUrl,
                    'author' => $parsed->author,
                    'type' => $source->type,
                    'priority' => $priority,
                    'priority_weight' => $priority->weight(),
                    'tags' => $mergedTags,
                    'metadata' => $parsed->metadata,
                    'published_at' => $publishedAt,
                    'fetched_at' => $now,
                ]);

                $item->setRelation('source', $source);
                $createdItems[] = $item;
            }

            $source->forceFill([
                'etag' => $response->header('ETag') ?: $source->etag,
                'last_modified' => $response->header('Last-Modified') ?: $source->last_modified,
                'last_fetched_at' => $now,
                'last_success_at' => $now,
                'next_fetch_at' => $now->copy()->addMinutes($source->interval_minutes),
                'last_status_code' => $httpStatus,
                'last_error' => null,
                'consecutive_failures' => 0,
            ])->save();

            SourceFetchLog::query()->create([
                'source_id' => $source->id,
                'status' => FetchStatus::Success,
                'http_status' => $httpStatus,
                'items_found' => count($parsedItems),
                'items_created' => count($createdItems),
                'duration_ms' => $durationMs,
                'fetched_at' => $now,
            ]);

            foreach ($createdItems as $createdItem) {
                try {
                    event(new NewsItemCreated($createdItem));
                } catch (Throwable $broadcastException) {
                    Log::warning('Error al emitir evento WebSocket NewsItemCreated', [
                        'item_id' => $createdItem->id,
                        'error' => $broadcastException->getMessage(),
                    ]);
                }
            }

            return [
                'status' => FetchStatus::Success,
                'found' => count($parsedItems),
                'created' => count($createdItems),
                'error' => null,
            ];
        } catch (Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            return $this->recordFailure($source, $e->getMessage(), null, $durationMs);
        }
    }

    private function resolveAdapter(SourceFormat $format): FeedAdapterInterface
    {
        return match ($format) {
            SourceFormat::Rss, SourceFormat::Atom => $this->rssAtomAdapter,
            SourceFormat::Json => $this->jsonAdapter,
            SourceFormat::CveNvd => $this->cveNvdAdapter,
        };
    }

    /**
     * @return array{status: FetchStatus, found: int, created: int, error: string}
     */
    private function recordFailure(Source $source, string $message, ?int $httpStatus, int $durationMs): array
    {
        $now = now();

        $source->forceFill([
            'last_fetched_at' => $now,
            'next_fetch_at' => $now->copy()->addMinutes($source->interval_minutes),
            'last_status_code' => $httpStatus,
            'last_error' => $message,
            'consecutive_failures' => $source->consecutive_failures + 1,
        ])->save();

        SourceFetchLog::query()->create([
            'source_id' => $source->id,
            'status' => FetchStatus::Error,
            'http_status' => $httpStatus,
            'items_found' => 0,
            'items_created' => 0,
            'duration_ms' => $durationMs,
            'error_message' => $message,
            'fetched_at' => $now,
        ]);

        Log::warning("Fallo de ingesta en fuente '{$source->slug}': {$message}");

        return [
            'status' => FetchStatus::Error,
            'found' => 0,
            'created' => 0,
            'error' => $message,
        ];
    }
}
