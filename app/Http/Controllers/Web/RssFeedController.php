<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NewsItem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Genera el feed RSS 2.0 propio de beacon-sift filtrable por type, priority, min_priority, source y q.
 */
class RssFeedController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $limit = max(1, min($request->integer('limit', 30), 100));

        $items = NewsItem::query()
            ->with('source:id,slug,name,url')
            ->filterByType($request->string('type')->toString())
            ->filterByMinPriority($request->string('min_priority')->toString())
            ->filterByPriority($request->string('priority')->toString())
            ->filterBySourceSlug($request->string('source')->toString())
            ->searchQuery($request->string('q')->toString())
            ->chronological()
            ->limit($limit)
            ->get();

        $xml = view('rss', [
            'items' => $items,
            'channelUrl' => route('home'),
            'selfUrl' => $request->fullUrl(),
            'lastBuildDate' => $items->first()?->published_at?->toRfc2822String() ?? now()->toRfc2822String(),
        ])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=120',
        ]);
    }
}
