<?php

namespace Tests\Unit\Ingestion;

use App\Services\Ingestion\TextSanitizer;
use PHPUnit\Framework\TestCase;

class TextSanitizerTest extends TestCase
{
    public function test_snippet_strips_html_decodes_entities_and_truncates(): void
    {
        $raw = '<p>Hola &amp; <strong>mundo</strong>   técnico con texto largo repetido para probar truncado.</p>';
        $result = TextSanitizer::snippet($raw, 45);

        $this->assertNotNull($result);
        $this->assertStringNotContainsString('<p>', $result);
        $this->assertStringContainsString('&', $result);
        $this->assertLessThanOrEqual(46, mb_strlen($result));
    }

    public function test_safe_http_url_allows_only_http_and_https(): void
    {
        $this->assertSame('https://feed.raupulus.dev/test', TextSanitizer::safeHttpUrl(' https://feed.raupulus.dev/test '));
        $this->assertSame('http://example.org/rss', TextSanitizer::safeHttpUrl('http://example.org/rss'));
        $this->assertNull(TextSanitizer::safeHttpUrl('javascript:alert(1)'));
        $this->assertNull(TextSanitizer::safeHttpUrl('data:text/html,<script>'));
        $this->assertNull(TextSanitizer::safeHttpUrl('not-a-url'));
    }

    public function test_resolve_url_resolves_relative_and_protocol_relative_urls(): void
    {
        $base = 'https://www.debian.org/security/dsa/index.xml';

        $this->assertSame(
            'https://www.debian.org/security/2026/dsa-5900',
            TextSanitizer::resolveUrl('/security/2026/dsa-5900', $base),
        );
        $this->assertSame(
            'https://www.debian.org/security/dsa/images/banner.png',
            TextSanitizer::resolveUrl('images/banner.png', $base),
        );
        $this->assertSame(
            'https://cdn.example.org/pic.jpg',
            TextSanitizer::resolveUrl('//cdn.example.org/pic.jpg', $base),
        );
        $this->assertNull(TextSanitizer::resolveUrl('javascript:void(0)', $base));
        $this->assertNull(TextSanitizer::resolveUrl('mailto:test@example.org', $base));
    }
}
