{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
@php
    $escapeCdata = static fn (?string $text): string => str_replace(']]>', ']]]]><![CDATA[>', (string) $text);
@endphp
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>beacon-sift — feed.raupulus.dev</title>
        <link>{{ $channelUrl }}</link>
        <description>Agregador cronológico de noticias y alertas en tiempo real con atribución a fuentes originales.</description>
        <language>es</language>
        <lastBuildDate>{{ $lastBuildDate }}</lastBuildDate>
        <atom:link href="{{ $selfUrl }}" rel="self" type="application/rss+xml"/>
@foreach ($items as $item)
        <item>
            <title><![CDATA[{!! $escapeCdata($item->title) !!}]]></title>
            <link>{{ $item->url }}</link>
            <guid isPermaLink="false">beacon-sift:{{ $item->id }}</guid>
            <pubDate>{{ $item->published_at->toRfc2822String() }}</pubDate>
            <category>{{ $item->type }}</category>
            <category>priority:{{ $item->priority->value }}</category>
@if ($item->source)
            <source url="{{ $item->source->url }}">{{ $item->source->name }}</source>
@endif
@if ($item->snippet)
            <description><![CDATA[{!! $escapeCdata($item->snippet) !!}]]></description>
@endif
        </item>
@endforeach
    </channel>
</rss>
