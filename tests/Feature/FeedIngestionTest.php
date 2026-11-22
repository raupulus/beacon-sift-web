<?php

namespace Tests\Feature;

use App\Enums\FetchStatus;
use App\Enums\Priority;
use App\Enums\SourceFormat;
use App\Events\NewsItemCreated;
use App\Http\Resources\NewsItemResource;
use App\Models\NewsItem;
use App\Models\Source;
use App\Models\SourceFetchLog;
use App\Services\Ingestion\FeedIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FeedIngestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingests_rss_deduplicates_extracts_hotlink_image_and_broadcasts_event(): void
    {
        Event::fake([NewsItemCreated::class]);

        $source = Source::factory()->create([
            'slug' => 'debian-news',
            'url' => 'https://example.org/debian.xml',
            'format' => SourceFormat::Rss,
            'type' => 'debian',
            'priority' => Priority::High,
            'priority_weight' => Priority::High->weight(),
            'snippet_max_length' => 160,
        ]);

        $longDescription = str_repeat('Actualización importante de seguridad en Debian GNU/Linux. ', 6);
        $rssXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
  <channel>
    <title>Debian News</title>
    <item>
      <guid>dsa-9999</guid>
      <title>DSA-9999-1 kernel security update</title>
      <link>https://example.org/dsa-9999</link>
      <description><![CDATA[<p>{$longDescription}</p>]]></description>
      <pubDate>Fri, 20 Nov 2026 10:00:00 +0000</pubDate>
      <media:content url="https://cdn.example.org/thumb.jpg" medium="image" />
    </item>
  </channel>
</rss>
XML;

        Http::fake([
            'https://example.org/debian.xml' => Http::response($rssXml, 200, [
                'ETag' => '"etag-v1"',
                'Last-Modified' => 'Fri, 20 Nov 2026 10:05:00 GMT',
            ]),
        ]);

        $service = app(FeedIngestionService::class);
        $result = $service->ingest($source);

        $this->assertSame(FetchStatus::Success, $result['status']);
        $this->assertSame(1, $result['found']);
        $this->assertSame(1, $result['created']);

        $item = NewsItem::query()->firstOrFail();
        $this->assertSame('https://cdn.example.org/thumb.jpg', $item->image_url);
        $this->assertLessThanOrEqual(165, mb_strlen((string) $item->snippet));
        $this->assertStringNotContainsString('<p>', (string) $item->snippet);

        Event::assertDispatched(NewsItemCreated::class, function (NewsItemCreated $event) use ($item): bool {
            return $event->newsItem->id === $item->id
                && $event->broadcastAs() === 'news.created'
                && $event->broadcastOn()[0]->name === 'beacon.news';
        });

        // Segunda ingesta con el mismo contenido: deduplica y no crea duplicados
        $second = $service->ingest($source->fresh());
        $this->assertSame(0, $second['created']);
        $this->assertDatabaseCount('news_items', 1);
        $this->assertDatabaseCount('source_fetch_logs', 2);
    }

    public function test_respects_max_items_option_and_handles_titleless_mastodon_rss(): void
    {
        Event::fake([NewsItemCreated::class]);

        $source = Source::factory()->create([
            'slug' => 'fosstodon-sourceware',
            'url' => 'https://fosstodon.org/@sourceware.rss',
            'format' => SourceFormat::Rss,
            'type' => 'opensource',
            'adapter_options' => ['max_items' => 1],
        ]);

        $rssXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Sourceware</title>
    <item>
      <guid>post-1</guid>
      <link>https://fosstodon.org/@sourceware/1</link>
      <description><![CDATA[<p>Nueva versión de binutils y GCC disponible en Sourceware.</p>]]></description>
      <pubDate>Sat, 21 Nov 2026 12:00:00 +0000</pubDate>
    </item>
    <item>
      <guid>post-2</guid>
      <link>https://fosstodon.org/@sourceware/2</link>
      <description><![CDATA[<p>Segunda entrada que debe recortarse por max_items=1.</p>]]></description>
      <pubDate>Sat, 21 Nov 2026 11:00:00 +0000</pubDate>
    </item>
  </channel>
</rss>
XML;

        Http::fake([
            'https://fosstodon.org/@sourceware.rss' => Http::response($rssXml, 200),
        ]);

        $result = app(FeedIngestionService::class)->ingest($source);

        $this->assertSame(FetchStatus::Success, $result['status']);
        $this->assertSame(1, $result['found']);
        $this->assertSame(1, $result['created']);
        $this->assertDatabaseCount('news_items', 1);
        $this->assertStringContainsString('binutils', NewsItem::query()->firstOrFail()->title);
    }

    public function test_clamps_future_published_at_and_normalizes_timestamps_to_utc(): void
    {
        Event::fake([NewsItemCreated::class]);

        $source = Source::factory()->create([
            'slug' => 'future-feed',
            'url' => 'https://example.org/future.xml',
            'format' => SourceFormat::Rss,
            'type' => 'linux',
        ]);

        $futurePubDate = now()->addHours(5)->toRfc2822String();
        $rssXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Future Feed</title>
    <item>
      <guid>future-1</guid>
      <title>Scheduled future article</title>
      <link>https://example.org/future-1</link>
      <pubDate>{$futurePubDate}</pubDate>
    </item>
  </channel>
</rss>
XML;

        Http::fake([
            'https://example.org/future.xml' => Http::response($rssXml, 200),
        ]);

        $before = now()->utc()->subSecond();
        $result = app(FeedIngestionService::class)->ingest($source);
        $after = now()->utc()->addSecond();

        $this->assertSame(FetchStatus::Success, $result['status']);
        $item = NewsItem::query()->firstOrFail();
        $this->assertTrue($item->published_at->utc()->between($before, $after));
        $this->assertStringEndsWith('+00:00', NewsItemResource::formatItem($item)['published_at']);
    }

    public function test_handles_http_304_not_modified_respecting_etag(): void
    {
        $source = Source::factory()->create([
            'url' => 'https://example.org/cached.xml',
            'etag' => '"abc-123"',
        ]);

        Http::fake([
            'https://example.org/cached.xml' => Http::response('', 304),
        ]);

        $result = app(FeedIngestionService::class)->ingest($source);

        $this->assertSame(FetchStatus::NotModified, $result['status']);
        $this->assertDatabaseHas('source_fetch_logs', [
            'source_id' => $source->id,
            'status' => FetchStatus::NotModified->value,
            'http_status' => 304,
        ]);
    }

    public function test_ingests_nvd_cve_json_and_elevates_priority_from_cvss(): void
    {
        Event::fake([NewsItemCreated::class]);

        $source = Source::factory()->create([
            'url' => 'https://example.org/nvd.json',
            'format' => SourceFormat::CveNvd,
            'type' => 'cve',
            'priority' => Priority::Medium,
            'priority_weight' => Priority::Medium->weight(),
        ]);

        Http::fake([
            'https://example.org/nvd.json' => Http::response([
                'vulnerabilities' => [
                    [
                        'cve' => [
                            'id' => 'CVE-2026-12345',
                            'published' => '2026-11-20T09:00:00.000',
                            'descriptions' => [
                                ['lang' => 'en', 'value' => 'Remote code execution in example daemon.'],
                            ],
                            'metrics' => [
                                'cvssMetricV31' => [
                                    ['cvssData' => ['baseScore' => 9.8]],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = app(FeedIngestionService::class)->ingest($source);

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('news_items', [
            'external_id' => 'CVE-2026-12345',
            'priority' => Priority::Critical->value,
            'priority_weight' => 4,
        ]);
    }

    public function test_prune_command_deletes_old_fetch_logs_only_when_items_retention_is_zero(): void
    {
        $source = Source::factory()->create();

        SourceFetchLog::factory()->create([
            'source_id' => $source->id,
            'fetched_at' => now()->subDays(45),
        ]);
        SourceFetchLog::factory()->create([
            'source_id' => $source->id,
            'fetched_at' => now()->subDays(5),
        ]);

        NewsItem::factory()->create([
            'source_id' => $source->id,
            'published_at' => now()->subDays(120),
        ]);

        config()->set('beacon.fetch_logs_retention_days', 30);
        config()->set('beacon.items_retention_days', 0);

        $this->artisan('beacon:prune')->assertSuccessful();

        $this->assertDatabaseCount('source_fetch_logs', 1);
        $this->assertDatabaseCount('news_items', 1);
    }
}
