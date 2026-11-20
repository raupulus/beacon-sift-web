<?php

namespace Tests\Unit\Ingestion;

use App\Enums\Priority;
use App\Enums\SourceFormat;
use App\Models\Source;
use App\Services\Ingestion\CveNvdAdapter;
use App\Services\Ingestion\JsonAdapter;
use App\Services\Ingestion\RssAtomAdapter;
use RuntimeException;
use Tests\TestCase;

class AdaptersTest extends TestCase
{
    public function test_rss_atom_adapter_parses_atom_with_relative_links_and_enclosure_image(): void
    {
        $source = new Source([
            'slug' => 'atom-test',
            'url' => 'https://example.org/feeds/atom.xml',
            'format' => SourceFormat::Atom,
            'type' => 'linux',
            'priority' => Priority::Medium,
            'snippet_max_length' => 160,
        ]);

        $atomXml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title>Linux Feed</title>
  <entry>
    <id>tag:example.org,2026:1</id>
    <title>Kernel 6.18.1 released</title>
    <link rel="alternate" href="/posts/kernel-6-18-1"/>
    <link rel="enclosure" type="image/webp" href="images/kernel.webp"/>
    <updated>2026-11-20T09:00:00Z</updated>
    <author><name>Kernel Maintainer</name></author>
    <summary type="html">&lt;p&gt;New stable maintenance update.&lt;/p&gt;</summary>
    <category term="kernel"/>
  </entry>
</feed>
XML;

        $items = (new RssAtomAdapter)->parse($atomXml, $source);

        $this->assertCount(1, $items);
        $this->assertSame('https://example.org/posts/kernel-6-18-1', $items[0]->url);
        $this->assertSame('https://example.org/feeds/images/kernel.webp', $items[0]->imageUrl);
        $this->assertSame('New stable maintenance update.', $items[0]->snippet);
        $this->assertSame('Kernel Maintainer', $items[0]->author);
        $this->assertSame(['kernel'], $items[0]->tags);
    }

    public function test_rss_adapter_uses_content_encoded_fallback_and_enclosure_extension(): void
    {
        $source = new Source([
            'slug' => 'rss-content',
            'url' => 'https://example.org/rss.xml',
            'format' => SourceFormat::Rss,
            'type' => 'debian',
            'priority' => Priority::High,
            'snippet_max_length' => 160,
        ]);

        $rssXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
  <channel>
    <item>
      <title>Debian Update</title>
      <link>/news/update-1</link>
      <description></description>
      <content:encoded><![CDATA[<div>Full HTML snippet <b>fallback</b> text.</div>]]></content:encoded>
      <enclosure url="/media/cover.png" />
    </item>
  </channel>
</rss>
XML;

        $items = (new RssAtomAdapter)->parse($rssXml, $source);

        $this->assertCount(1, $items);
        $this->assertSame('https://example.org/news/update-1', $items[0]->url);
        $this->assertSame('https://example.org/media/cover.png', $items[0]->imageUrl);
        $this->assertSame('Full HTML snippet fallback text.', $items[0]->snippet);
    }

    public function test_rss_atom_adapter_throws_on_invalid_xml(): void
    {
        $source = new Source(['url' => 'https://example.org/bad.xml', 'snippet_max_length' => 160]);

        $this->expectException(RuntimeException::class);
        (new RssAtomAdapter)->parse('<rss><broken>', $source);
    }

    public function test_json_adapter_maps_nested_items_relative_urls_and_tags(): void
    {
        $source = new Source([
            'url' => 'https://api.example.org/v1/alerts.json',
            'format' => SourceFormat::Json,
            'snippet_max_length' => 160,
            'adapter_options' => [
                'items_path' => 'data.entries',
                'external_id_field' => 'uid',
                'title_field' => 'headline',
                'url_field' => 'href',
                'summary_field' => 'excerpt',
                'image_field' => 'thumb',
                'tags_field' => 'labels',
            ],
        ]);

        $payload = json_encode([
            'data' => [
                'entries' => [
                    [
                        'uid' => 'a-100',
                        'headline' => 'Aviso local',
                        'href' => '/avisos/100',
                        'excerpt' => '<p>Corte de tráfico programado.</p>',
                        'thumb' => '/img/aviso.jpg',
                        'labels' => 'local, trafico',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $items = (new JsonAdapter)->parse($payload, $source);

        $this->assertCount(1, $items);
        $this->assertSame('a-100', $items[0]->externalId);
        $this->assertSame('https://api.example.org/avisos/100', $items[0]->url);
        $this->assertSame('https://api.example.org/img/aviso.jpg', $items[0]->imageUrl);
        $this->assertSame(['local', 'trafico'], $items[0]->tags);
    }

    public function test_cve_nvd_adapter_maps_cvss_scores_to_priorities(): void
    {
        $source = new Source([
            'url' => 'https://services.nvd.nist.gov/rest/json/cves/2.0',
            'format' => SourceFormat::CveNvd,
            'snippet_max_length' => 160,
        ]);

        $payload = json_encode([
            'vulnerabilities' => [
                ['cve' => ['id' => 'CVE-2026-0001', 'metrics' => ['cvssMetricV31' => [['cvssData' => ['baseScore' => 9.5]]]]]],
                ['cve' => ['id' => 'CVE-2026-0002', 'metrics' => ['cvssMetricV30' => [['cvssData' => ['baseScore' => 7.5]]]]]],
                ['cve' => ['id' => 'CVE-2026-0003', 'metrics' => ['cvssMetricV2' => [['cvssData' => ['baseScore' => 5.0]]]]]],
                ['cve' => ['id' => 'CVE-2026-0004', 'metrics' => ['cvssMetricV31' => [['cvssData' => ['baseScore' => 2.1]]]]]],
                ['cve' => ['id' => 'CVE-2026-0005', 'metrics' => []]],
            ],
        ], JSON_THROW_ON_ERROR);

        $items = (new CveNvdAdapter)->parse($payload, $source);

        $this->assertCount(5, $items);
        $this->assertSame(Priority::Critical, $items[0]->priorityOverride);
        $this->assertSame(Priority::High, $items[1]->priorityOverride);
        $this->assertSame(Priority::Medium, $items[2]->priorityOverride);
        $this->assertSame(Priority::Low, $items[3]->priorityOverride);
        $this->assertNull($items[4]->priorityOverride);
    }
}
