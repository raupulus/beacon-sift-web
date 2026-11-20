@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-text">Fuentes activas</h1>
            <p class="text-sm text-text-muted mt-1">
                Transparencia de orígenes configurados en <span class="font-mono text-text-secondary">feed.raupulus.dev</span>. Haz clic en cualquier fuente para filtrar sus entradas.
            </p>
        </div>
        <span class="font-mono text-xs text-primary bg-surface border border-border px-3 py-1.5 rounded-lg">
            {{ $sources->count() }} fuente(s) activa(s)
        </span>
    </div>

    @if ($availableTypes->isNotEmpty())
        <div class="mb-6 flex flex-wrap items-center gap-2">
            <a href="{{ route('sources.index') }}"
               class="px-3 py-1 rounded-full font-mono text-xs uppercase border transition {{ $selectedType === '' ? 'bg-primary/15 border-primary text-primary' : 'bg-surface border-border text-text-muted hover:text-text hover:border-primary/40' }}">
                Todas
            </a>
            @foreach ($availableTypes as $typeOption)
                <a href="{{ route('sources.index', ['type' => $typeOption]) }}"
                   class="px-3 py-1 rounded-full font-mono text-xs uppercase border transition {{ $selectedType === $typeOption ? 'bg-primary/15 border-primary text-primary' : 'bg-surface border-border text-text-muted hover:text-text hover:border-primary/40' }}">
                    {{ $typeOption }}
                </a>
            @endforeach
        </div>
    @endif

    @if ($sources->isEmpty())
        <div class="bg-surface border border-border rounded-xl p-10 text-center">
            <p class="font-mono text-sm text-primary mb-2">Todavía no hay fuentes activas sincronizadas</p>
            <p class="text-sm text-text-muted">
                Ejecuta <code class="font-mono text-text-secondary">php artisan sources:sync</code> tras configurar tu archivo JSON de fuentes.
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($sources as $source)
                @php
                    $isFailing = $source->consecutive_failures > 0;
                    $isHealthy = ! $isFailing && $source->last_success_at !== null;
                @endphp
                <article class="bg-surface border border-border hover:border-primary/50 rounded-xl p-5 flex flex-col gap-3 transition">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full {{ $isFailing ? 'bg-priority-critical' : ($isHealthy ? 'bg-live' : 'bg-text-muted') }}"
                                  title="{{ $isFailing ? 'Con incidencias temporales' : ($isHealthy ? 'Operativa' : 'Pendiente de comprobación') }}"></span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md border border-border bg-bg font-mono text-[11px] uppercase text-primary">
                                {{ $source->type }}
                            </span>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md border font-mono text-[11px] uppercase {{ $source->priority->badgeClasses() }}">
                            {{ $source->priority->label() }}
                        </span>
                    </div>

                    <h2 class="text-lg font-semibold text-text leading-snug">
                        <a href="{{ route('home', ['source' => $source->slug]) }}" class="hover:text-primary transition">
                            {{ $source->name }}
                        </a>
                    </h2>

                    <div class="font-mono text-xs text-text-muted space-y-1">
                        <div>Formato: <span class="text-text-secondary">{{ $source->format->label() }}</span> · Intervalo: <span class="text-text-secondary">{{ $source->interval_minutes }}m</span></div>
                        <div>Entradas indexadas: <span class="text-text-secondary">{{ $source->news_items_count }}</span></div>
                        <div>
                            Última comprobación:
                            <span class="text-text-secondary">
                                {{ $source->last_fetched_at ? format_date($source->last_fetched_at, 'd/m/Y H:i') : 'Pendiente' }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-auto pt-3 border-t border-border/70 flex items-center justify-between text-xs font-mono">
                        <a href="{{ route('home', ['source' => $source->slug]) }}" class="text-primary hover:text-primary-bright transition">
                            Ver noticias →
                        </a>
                        <a href="{{ $source->url }}" target="_blank" rel="noopener noreferrer" class="text-text-muted hover:text-text transition">
                            Origen ↗
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection
