<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NewsItemResource;
use App\Models\NewsItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint público de consulta cronológica, filtrado y búsqueda de noticias (/api/v1/items).
 */
class NewsItemApiController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $perPage = max(1, min($request->integer('per_page', 10), 50));
        $order = strtolower($request->string('order', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';

        $query = NewsItem::query()
            ->with('source:id,slug,name,url')
            ->filterByType($request->string('type')->toString())
            ->filterByMinPriority($request->string('min_priority')->toString())
            ->filterByPriority($request->string('priority')->toString())
            ->filterBySourceSlug($request->string('source')->toString())
            ->searchQuery($request->string('q')->toString());

        if ($request->filled('since_id')) {
            $query->where('id', '>', max(0, $request->integer('since_id')));
        }

        if ($order === 'asc') {
            $query->orderBy('published_at')->orderBy('id');
        } else {
            $query->chronological();
        }

        $items = $query->paginate($perPage)
            ->through(static fn (NewsItem $item): array => NewsItemResource::formatItem($item));

        return response()->json($items);
    }
}
