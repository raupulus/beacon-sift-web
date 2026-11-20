<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Priority;
use App\Http\Controllers\Controller;
use App\Models\NewsItem;
use App\Models\Source;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Endpoint público de tipos activos y catálogo de prioridades (/api/v1/types).
 */
class TypeApiController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $sourceCounts = Source::query()
            ->active()
            ->select('type', DB::raw('COUNT(*) as sources_count'))
            ->groupBy('type')
            ->orderBy('type')
            ->pluck('sources_count', 'type');

        $itemCounts = NewsItem::query()
            ->select('type', DB::raw('COUNT(*) as items_count'))
            ->groupBy('type')
            ->pluck('items_count', 'type');

        $types = [];
        foreach ($sourceCounts as $type => $sourcesCount) {
            $types[] = [
                'type' => (string) $type,
                'sources_count' => (int) $sourcesCount,
                'items_count' => (int) ($itemCounts[$type] ?? 0),
            ];
        }

        return response()->json([
            'types' => $types,
            'priorities' => Priority::catalog(),
        ]);
    }
}
