<?php

namespace App\Http\Controllers\Web;

use App\Enums\Priority;
use App\Http\Controllers\Controller;
use App\Models\NewsItem;
use App\Models\Source;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Portada principal de feed.raupulus.dev con listado cronológico, filtros en URL y paginación clásica.
 */
class FeedController extends Controller
{
    public function __invoke(Request $request, SeoService $seo): View
    {
        $selectedType = trim($request->string('type')->toString());
        $selectedPriority = trim($request->string('priority')->toString());
        $selectedSource = trim($request->string('source')->toString());
        $search = trim($request->string('q')->toString());

        $items = NewsItem::query()
            ->with('source:id,slug,name,url')
            ->filterByType($selectedType)
            ->filterByPriority($selectedPriority)
            ->filterBySourceSlug($selectedSource)
            ->searchQuery($search)
            ->chronological()
            ->paginate(18)
            ->withQueryString();

        $availableTypes = Source::query()
            ->active()
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        $availableSources = Source::query()
            ->active()
            ->orderBy('name')
            ->get(['slug', 'name', 'type']);

        $activeFilters = array_filter([
            'type' => $selectedType !== '' ? $selectedType : null,
            'priority' => $selectedPriority !== '' ? $selectedPriority : null,
            'source' => $selectedSource !== '' ? $selectedSource : null,
            'q' => $search !== '' ? $search : null,
        ]);

        $seo->setTitle('beacon-sift — Noticias y alertas en tiempo real')
            ->setDescription('Agregador cronológico de noticias técnicas, avisos locales y alertas CVE en tiempo real con feed RSS filtrable.')
            ->setUrl(route('home'))
            ->setImage(asset('images/redes-sociales/og-image-1200x630.png'));

        return view('home', [
            'items' => $items,
            'availableTypes' => $availableTypes,
            'availableSources' => $availableSources,
            'priorities' => Priority::cases(),
            'filters' => [
                'type' => $selectedType,
                'priority' => $selectedPriority,
                'source' => $selectedSource,
                'q' => $search,
            ],
            'rssUrl' => route('feed.rss', $activeFilters),
            'hasActiveFilters' => $activeFilters !== [],
        ]);
    }
}
