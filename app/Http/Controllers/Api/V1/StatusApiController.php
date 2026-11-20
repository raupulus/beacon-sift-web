<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NewsItem;
use App\Models\Source;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Endpoint público de estado y salud del agregador (/api/v1/status).
 */
class StatusApiController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $dbOk = true;
        try {
            DB::select('SELECT 1');
        } catch (Throwable) {
            $dbOk = false;
        }

        $activeSources = Source::query()->active()->count();
        $failingSources = Source::query()->active()->where('consecutive_failures', '>', 0)->count();
        $totalSources = Source::query()->count();
        $itemsTotal = NewsItem::query()->count();
        $lastSyncAt = Source::query()->max('last_fetched_at');

        return response()->json([
            'status' => $dbOk ? 'ok' : 'degraded',
            'app' => config('app.name', 'beacon-sift'),
            'database' => $dbOk ? 'ok' : 'error',
            'last_sync_at' => $lastSyncAt,
            'sources' => [
                'active' => $activeSources,
                'failing' => $failingSources,
                'total' => $totalSources,
            ],
            'items_total' => $itemsTotal,
            'websocket' => [
                'channel' => config('beacon.broadcast_channel', 'beacon.news'),
                'event' => 'news.created',
            ],
        ], $dbOk ? 200 : 503);
    }
}
