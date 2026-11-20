<?php

namespace App\Services\Ingestion;

use App\Models\Source;
use Carbon\Carbon;
use RuntimeException;
use Throwable;

/**
 * Adaptador genérico para feeds o endpoints JSON mediante mapeo en adapter_options.
 */
class JsonAdapter implements FeedAdapterInterface
{
    /**
     * @return list<ParsedFeedItem>
     */
    public function parse(string $body, Source $source): array
    {
        $decoded = json_decode($body, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('El cuerpo recibido no es un JSON válido.');
        }

        $options = $source->adapter_options ?? [];
        $itemsPath = isset($options['items_path']) ? (string) $options['items_path'] : 'items';
        $rawItems = $itemsPath === '' ? $decoded : data_get($decoded, $itemsPath, []);

        if (! is_array($rawItems)) {
            return [];
        }

        $idField = (string) ($options['external_id_field'] ?? 'id');
        $titleField = (string) ($options['title_field'] ?? 'title');
        $urlField = (string) ($options['url_field'] ?? 'url');
        $summaryField = (string) ($options['summary_field'] ?? 'summary');
        $imageField = (string) ($options['image_field'] ?? 'image');
        $authorField = (string) ($options['author_field'] ?? 'author');
        $dateField = (string) ($options['published_at_field'] ?? 'published_at');
        $tagsField = isset($options['tags_field']) ? (string) $options['tags_field'] : null;

        $items = [];

        foreach ($rawItems as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $title = trim((string) data_get($entry, $titleField, ''));
            $url = TextSanitizer::resolveUrl((string) data_get($entry, $urlField, ''), $source->url);

            if ($title === '' || $url === null) {
                continue;
            }

            $rawId = data_get($entry, $idField);
            $externalId = is_scalar($rawId) && trim((string) $rawId) !== '' ? trim((string) $rawId) : null;
            $snippet = TextSanitizer::snippet(
                is_scalar(data_get($entry, $summaryField)) ? (string) data_get($entry, $summaryField) : null,
                $source->snippet_max_length,
            );
            $imageUrl = TextSanitizer::resolveUrl(
                is_scalar(data_get($entry, $imageField)) ? (string) data_get($entry, $imageField) : null,
                $source->url,
            );
            $rawAuthor = data_get($entry, $authorField);
            $author = is_scalar($rawAuthor) && trim((string) $rawAuthor) !== '' ? trim((string) $rawAuthor) : null;

            $rawDate = data_get($entry, $dateField);
            $publishedAt = now();
            if (is_scalar($rawDate) && trim((string) $rawDate) !== '') {
                try {
                    $publishedAt = Carbon::parse((string) $rawDate);
                } catch (Throwable) {
                    $publishedAt = now();
                }
            }

            $tags = $this->extractTags($entry, $tagsField);

            $items[] = new ParsedFeedItem(
                externalId: $externalId,
                title: $title,
                url: $url,
                snippet: $snippet,
                imageUrl: $imageUrl,
                author: $author,
                publishedAt: $publishedAt,
                tags: $tags,
            );
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return list<string>
     */
    private function extractTags(array $entry, ?string $tagsField): array
    {
        if ($tagsField === null || trim($tagsField) === '') {
            return [];
        }

        $raw = data_get($entry, $tagsField);
        if (is_array($raw)) {
            return array_values(array_unique(array_filter(array_map(
                static fn (mixed $v): string => is_scalar($v) ? trim((string) $v) : '',
                $raw,
            ))));
        }

        if (is_string($raw) && trim($raw) !== '') {
            return array_values(array_unique(array_filter(array_map(
                static fn (string $v): string => trim($v),
                explode(',', $raw),
            ))));
        }

        return [];
    }
}
