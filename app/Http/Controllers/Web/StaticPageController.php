<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use Illuminate\View\View;

/**
 * Páginas legales e informativas estáticas simples (/about, /privacy, /cookies).
 */
class StaticPageController extends Controller
{
    public function about(SeoService $seo): View
    {
        $seo->setTitle('Acerca de — beacon-sift')
            ->setDescription('Información sobre beacon-sift, agregación responsable y atribución a fuentes originales.')
            ->setUrl(route('about'))
            ->setImage(asset('images/redes-sociales/og-image-1200x630.png'));

        return view('about', [
            'contactEmail' => config('beacon.contact_email', 'public@raupulus.dev'),
        ]);
    }

    public function privacy(SeoService $seo): View
    {
        $seo->setTitle('Privacidad — beacon-sift')
            ->setDescription('Política de privacidad de feed.raupulus.dev: portal público de solo lectura sin registro de usuarios ni rastreadores.')
            ->setUrl(route('privacy'))
            ->setImage(asset('images/redes-sociales/og-image-1200x630.png'));

        return view('privacy', [
            'contactEmail' => config('beacon.contact_email', 'public@raupulus.dev'),
        ]);
    }

    public function cookies(SeoService $seo): View
    {
        $seo->setTitle('Cookies — beacon-sift')
            ->setDescription('Información sobre el uso mínimo de cookies técnicas en feed.raupulus.dev.')
            ->setUrl(route('cookies'))
            ->setImage(asset('images/redes-sociales/og-image-1200x630.png'));

        return view('cookies', [
            'contactEmail' => config('beacon.contact_email', 'public@raupulus.dev'),
        ]);
    }
}
