<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Source;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Listado público de fuentes activas configuradas en beacon-sift (/sources).
 */
class SourcesPageController extends Controller
{
    public function __invoke(Request $request, SeoService $seo): View
    {
        $selectedType = strtolower(trim($request->string('type')->toString()));

        $availableTypes = Source::query()
            ->active()
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        $sources = Source::query()
            ->active()
            ->when($selectedType !== '', static fn ($q) => $q->where('type', $selectedType))
            ->withCount('newsItems')
            ->orderByDesc('priority_weight')
            ->orderBy('name')
            ->get();

        $seo->setTitle('Fuentes activas — beacon-sift')
            ->setDescription('Listado público y transparente de las fuentes RSS, Atom, JSON y CVE que alimentan feed.raupulus.dev.')
            ->setUrl(route('sources.index'))
            ->setImage(asset('images/redes-sociales/og-image-1200x630.png'));

        return view('sources', [
            'sources' => $sources,
            'availableTypes' => $availableTypes,
            'selectedType' => $selectedType,
        ]);
    }
}
