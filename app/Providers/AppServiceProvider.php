<?php

namespace App\Providers;

use App\Services\SeoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra servicios propios en el contenedor.
     */
    public function register(): void
    {
        $this->app->scoped(SeoService::class);
    }

    /**
     * Inicializa reglas globales de la aplicación.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
