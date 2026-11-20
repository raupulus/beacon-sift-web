<?php

namespace App\Services\Ingestion;

use App\Models\Source;
use Carbon\Carbon;
use RuntimeException;
use SimpleXMLElement;
use Throwable;

/**
 * Adaptador para feeds RSS 2.0, RSS 1.0 (RDF) y Atom 1.0.
 * Extrae metadatos, extracto breve y URL de imagen publicada en el propio feed para hotlink.
 */
class RssAtomAdapter implements FeedAdapterInterface
{
    /**
     * @return list<ParsedFeedItem>
     */
    public function parse(string $body, Source $source): array
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
            if ($xml === false) {
                throw new RuntimeException('XML inválido o malformado en el feed.');
            }

            $rootName = strtolower($xml->getName());

            if ($rootName === 'feed') {
                return $this->parseAtom($xml, $source);
            }

            return $this->parseRss($xml, $source);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * @return list<ParsedFeedItem>
     */
    private function parseRss(SimpleXMLElement $xml, Source $source): array
    {
        $items = [];
        $nodes = isset($xml->channel->item) ? $xml->channel->item : ($xml->item ?? []);

        foreach ($nodes as $node) {
            $title = trim(html_entity_decode(strip_tags((string) ($node->title ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $url = TextSanitizer::resolveUrl((string) ($node->link ?? ''), $source->url);

            if ($title === '' || $url === null) {
                continue;
            }

            $externalId = trim((string) ($node->guid ?? ''));
            $content = $node->children('http://purl.org/rss/1.0/modules/content/');
            $rawDescription = trim((string) ($node->description ?? ''));
            if ($rawDescription === '') {
                $rawDescription = (string) ($content->encoded ?? '');
            }
            $snippet = TextSanitizer::snippet($rawDescription, $source->snippet_max_length);

            $dc = $node->children('http://purl.org/dc/elements/1.1/');
            $author = trim((string) ($node->author ?? $dc->creator ?? '')) ?: null;

            $rawDate = (string) ($node->pubDate ?? $dc->date ?? '');
            $publishedAt = $this->parseDate($rawDate);

            $imageUrl = $this->extractImageFromRssNode($node, $source->url);

            $tags = [];
            foreach ($node->category ?? [] as $cat) {
                $tag = trim((string) $cat);
                if ($tag !== '') {
                    $tags[] = $tag;
                }
            }

            $items[] = new ParsedFeedItem(
                externalId: $externalId !== '' ? $externalId : null,
                title: $title,
                url: $url,
                snippet: $snippet,
                imageUrl: $imageUrl,
                author: $author,
                publishedAt: $publishedAt,
                tags: array_values(array_unique($tags)),
            );
        }

        return $items;
    }

    /**
     * @return list<ParsedFeedItem>
     */
    private function parseAtom(SimpleXMLElement $xml, Source $source): array
    {
        $items = [];

        foreach ($xml->entry ?? [] as $entry) {
            $title = trim(html_entity_decode(strip_tags((string) ($entry->title ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $url = null;
            $imageUrl = null;

            foreach ($entry->link ?? [] as $link) {
                $attrs = $link->attributes();
                $rel = strtolower((string) ($attrs['rel'] ?? 'alternate'));
                $href = (string) ($attrs['href'] ?? '');
                $type = strtolower((string) ($attrs['type'] ?? ''));

                if ($rel === 'enclosure' && (str_starts_with($type, 'image/') || $this->looksLikeImageUrl($href))) {
                    $imageUrl ??= TextSanitizer::resolveUrl($href, $source->url);

                    continue;
                }

                if ($url === null && ($rel === 'alternate' || $rel === '')) {
                    $url = TextSanitizer::resolveUrl($href, $source->url);
                }
            }

            if ($title === '' || $url === null) {
                continue;
            }

            $externalId = trim((string) ($entry->id ?? ''));
            $rawSummary = trim((string) ($entry->summary ?? ''));
            if ($rawSummary === '') {
                $rawSummary = (string) ($entry->content ?? '');
            }
            $snippet = TextSanitizer::snippet($rawSummary, $source->snippet_max_length);
            $author = trim((string) ($entry->author->name ?? '')) ?: null;
            $rawDate = (string) ($entry->published ?? $entry->updated ?? '');
            $publishedAt = $this->parseDate($rawDate);

            $imageUrl ??= $this->extractMediaImage($entry, $source->url);

            $tags = [];
            foreach ($entry->category ?? [] as $cat) {
                $attrs = $cat->attributes();
                $tag = trim((string) ($attrs['term'] ?? $attrs['label'] ?? ''));
                if ($tag !== '') {
                    $tags[] = $tag;
                }
            }

            $items[] = new ParsedFeedItem(
                externalId: $externalId !== '' ? $externalId : null,
                title: $title,
                url: $url,
                snippet: $snippet,
                imageUrl: $imageUrl,
                author: $author,
                publishedAt: $publishedAt,
                tags: array_values(array_unique($tags)),
            );
        }

        return $items;
    }

    /**
     * Busca URL de imagen publicada explícitamente en nodos media:* o enclosure del RSS.
     */
    private function extractImageFromRssNode(SimpleXMLElement $node, string $baseUrl): ?string
    {
        $mediaImage = $this->extractMediaImage($node, $baseUrl);
        if ($mediaImage !== null) {
            return $mediaImage;
        }

        foreach ($node->enclosure ?? [] as $enclosure) {
            $attrs = $enclosure->attributes();
            $type = strtolower((string) ($attrs['type'] ?? ''));
            $url = (string) ($attrs['url'] ?? '');

            if (str_starts_with($type, 'image/') || ($type === '' && $this->looksLikeImageUrl($url))) {
                $safe = TextSanitizer::resolveUrl($url, $baseUrl);
                if ($safe !== null) {
                    return $safe;
                }
            }
        }

        return null;
    }

    /**
     * Extrae media:content o media:thumbnail del namespace Media RSS.
     */
    private function extractMediaImage(SimpleXMLElement $node, string $baseUrl): ?string
    {
        $media = $node->children('http://search.yahoo.com/mrss/');

        foreach (['content', 'thumbnail'] as $field) {
            foreach ($media->{$field} ?? [] as $el) {
                $attrs = $el->attributes();
                $url = (string) ($attrs['url'] ?? '');
                $medium = strtolower((string) ($attrs['medium'] ?? ''));
                $type = strtolower((string) ($attrs['type'] ?? ''));

                if ($field === 'thumbnail' || $medium === 'image' || str_starts_with($type, 'image/') || ($medium === '' && $type === '')) {
                    $safe = TextSanitizer::resolveUrl($url, $baseUrl);
                    if ($safe !== null) {
                        return $safe;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Comprueba si la ruta termina en una extensión de imagen común.
     */
    private function looksLikeImageUrl(string $url): bool
    {
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));

        return (bool) preg_match('/\.(jpe?g|png|webp|gif|avif)$/i', $path);
    }

    /**
     * Parsea fechas RSS/Atom con fallback seguro al instante actual.
     */
    private function parseDate(string $rawDate): Carbon
    {
        if (trim($rawDate) === '') {
            return now();
        }

        try {
            return Carbon::parse($rawDate);
        } catch (Throwable) {
            return now();
        }
    }
}
