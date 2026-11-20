<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Priority;
use App\Http\Controllers\Controller;
use App\Http\Resources\SourceResource;
use App\Models\Source;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint público de consulta de fuentes configuradas (/api/v1/sources).
 */
class SourceApiController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $perPage = max(1, min($request->integer('per_page', 50), 100));
        $onlyActive = $request->boolean('active', true);

        $query = Source::query()
            ->when($onlyActive, static fn ($q) => $q->active())
            ->when($request->filled('type'), static function ($q) use ($request): void {
                $q->where('type', strtolower(trim((string) $request->string('type'))));
            })
            ->when(Priority::tryFromNormalized($request->string('priority')->toString()), static function ($q, Priority $priority): void {
                $q->where('priority', $priority->value);
            })
            ->orderByDesc('priority_weight')
            ->orderBy('name');

        $sources = $query->paginate($perPage)
            ->through(static fn (Source $source): array => (new SourceResource($source))->resolve());

        return response()->json($sources);
    }
}
