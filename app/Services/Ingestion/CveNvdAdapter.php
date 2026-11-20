<?php

namespace App\Services\Ingestion;

use App\Enums\Priority;
use App\Models\Source;
use Carbon\Carbon;
use RuntimeException;
use Throwable;

/**
 * Adaptador especializado para la API JSON 2.0 de NVD (National Vulnerability Database).
 * Ajusta automáticamente la prioridad según puntuación CVSS cuando está disponible.
 */
class CveNvdAdapter implements FeedAdapterInterface
{
    /**
     * @return list<ParsedFeedItem>
     */
    public function parse(string $body, Source $source): array
    {
        $decoded = json_decode($body, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Respuesta JSON inválida desde la API de NVD.');
        }

        $vulnerabilities = $decoded['vulnerabilities'] ?? [];
        if (! is_array($vulnerabilities)) {
            return [];
        }

        $items = [];

        foreach ($vulnerabilities as $entry) {
            $cve = is_array($entry) ? ($entry['cve'] ?? null) : null;
            if (! is_array($cve)) {
                continue;
            }

            $cveId = trim((string) ($cve['id'] ?? ''));
            if ($cveId === '') {
                continue;
            }

            $description = $this->extractDescription($cve['descriptions'] ?? []);
            $cvssScore = $this->extractCvssScore($cve['metrics'] ?? []);
            $priorityOverride = $this->priorityFromCvss($cvssScore);

            $title = $cvssScore !== null
                ? sprintf('%s (CVSS %.1f)', $cveId, $cvssScore)
                : $cveId;

            $url = 'https://nvd.nist.gov/vuln/detail/'.rawurlencode($cveId);

            $publishedAt = now();
            if (isset($cve['published']) && is_string($cve['published'])) {
                try {
                    $publishedAt = Carbon::parse($cve['published']);
                } catch (Throwable) {
                    $publishedAt = now();
                }
            }

            $items[] = new ParsedFeedItem(
                externalId: $cveId,
                title: $title,
                url: $url,
                snippet: TextSanitizer::snippet($description, $source->snippet_max_length),
                imageUrl: null,
                author: 'NVD',
                publishedAt: $publishedAt,
                tags: ['cve', $cveId],
                metadata: array_filter([
                    'cve_id' => $cveId,
                    'cvss_score' => $cvssScore,
                ], static fn (mixed $v): bool => $v !== null),
                priorityOverride: $priorityOverride,
            );
        }

        return $items;
    }

    private function extractDescription(mixed $descriptions): ?string
    {
        if (! is_array($descriptions)) {
            return null;
        }

        $fallback = null;
        foreach ($descriptions as $desc) {
            if (! is_array($desc) || ! isset($desc['value'])) {
                continue;
            }

            $lang = strtolower((string) ($desc['lang'] ?? ''));
            if ($lang === 'es' || $lang === 'en') {
                return (string) $desc['value'];
            }

            $fallback ??= (string) $desc['value'];
        }

        return $fallback;
    }

    private function extractCvssScore(mixed $metrics): ?float
    {
        if (! is_array($metrics)) {
            return null;
        }

        foreach (['cvssMetricV31', 'cvssMetricV30', 'cvssMetricV40', 'cvssMetricV2'] as $key) {
            $score = data_get($metrics, "{$key}.0.cvssData.baseScore");
            if (is_numeric($score)) {
                return (float) $score;
            }
        }

        return null;
    }

    private function priorityFromCvss(?float $score): ?Priority
    {
        if ($score === null) {
            return null;
        }

        return match (true) {
            $score >= 9.0 => Priority::Critical,
            $score >= 7.0 => Priority::High,
            $score >= 4.0 => Priority::Medium,
            default => Priority::Low,
        };
    }
}
