@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8"
     x-data="{
        liveItems: [],
        activeType: @js($filters['type']),
        activePriority: @js($filters['priority']),
        activeSource: @js($filters['source']),
        activeQuery: @js($filters['q']),
        matchesFilter(item) {
            if (this.activeType && item.type !== this.activeType) return false;
            if (this.activePriority && item.priority !== this.activePriority) return false;
            if (this.activeSource && (!item.source || item.source.slug !== this.activeSource)) return false;
            if (this.activeQuery) {
                const needle = this.activeQuery.toLowerCase();
                const hay = ((item.title || '') + ' ' + (item.snippet || '')).toLowerCase();
                if (!hay.includes(needle)) return false;
            }
            return true;
        },
        handleIncoming(item) {
            if (!this.matchesFilter(item)) return;
            if (this.liveItems.some((existing) => existing.id === item.id)) return;
            this.liveItems.unshift(item);
            if (this.liveItems.length > 20) this.liveItems.pop();
        },
        priorityClasses(priority) {
            switch (priority) {
                case 'critical': return 'bg-red-500/15 text-red-400 border-red-500/70';
                case 'high': return 'bg-amber-500/15 text-amber-300 border-amber-500/60';
                case 'medium': return 'bg-sky-400/15 text-sky-300 border-sky-600/60';
                default: return 'bg-slate-500/15 text-slate-300 border-slate-700';
            }
        },
        formatDate(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            if (Number.isNaN(d.getTime())) return iso;
            return d.toLocaleString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        }
     }"
     @beacon:news-created.window="handleIncoming($event.detail)">

    {{-- Cabecera compacta + barra de filtros en URL --}}
    <section class="mb-6 bg-surface border border-border rounded-xl p-4 sm:p-5 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-text">
                    Feed en directo
                </h1>
                <p class="text-sm text-text-muted mt-0.5">
                    Últimas noticias y alertas ordenadas cronológicamente con enlace directo a la fuente original.
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ $rssUrl }}"
                   class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-mono font-medium border border-beacon/40 bg-beacon/10 text-beacon-bright hover:bg-beacon/20 transition">
                    <span>RSS de esta vista</span>
                </a>
                @if ($hasActiveFilters)
                    <a href="{{ route('home') }}"
                       class="inline-flex items-center px-3 py-2 rounded-lg text-xs font-mono border border-border bg-bg text-text-secondary hover:text-text hover:border-primary/40 transition">
                        Limpiar filtros
                    </a>
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('home') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
            <div class="lg:col-span-5">
                <label for="q" class="sr-only">Buscar por palabras clave</label>
                <input type="search"
                       id="q"
                       name="q"
                       value="{{ $filters['q'] }}"
                       placeholder="Buscar por palabras clave (CVE, Debian, kernel...)"
                       class="w-full rounded-lg bg-bg border-border text-sm text-text placeholder:text-text-muted focus:border-primary focus:ring-primary">
            </div>

            <div class="lg:col-span-2">
                <label for="type" class="sr-only">Tipo</label>
                <select id="type"
                        name="type"
                        class="w-full rounded-lg bg-bg border-border text-sm text-text focus:border-primary focus:ring-primary">
                    <option value="">Todos los tipos</option>
                    @foreach ($availableTypes as $typeOption)
                        <option value="{{ $typeOption }}" @selected($filters['type'] === $typeOption)>
                            {{ strtoupper($typeOption) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label for="priority" class="sr-only">Prioridad</label>
                <select id="priority"
                        name="priority"
                        class="w-full rounded-lg bg-bg border-border text-sm text-text focus:border-primary focus:ring-primary">
                    <option value="">Todas las prioridades</option>
                    @foreach ($priorities as $priorityOption)
                        <option value="{{ $priorityOption->value }}" @selected($filters['priority'] === $priorityOption->value)>
                            {{ $priorityOption->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label for="source" class="sr-only">Fuente</label>
                <select id="source"
                        name="source"
                        class="w-full rounded-lg bg-bg border-border text-sm text-text focus:border-primary focus:ring-primary">
                    <option value="">Todas las fuentes</option>
                    @foreach ($availableSources as $sourceOption)
                        <option value="{{ $sourceOption->slug }}" @selected($filters['source'] === $sourceOption->slug)>
                            {{ $sourceOption->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-1">
                <button type="submit"
                        class="w-full h-full px-4 py-2 rounded-lg bg-primary-deep hover:bg-primary text-text hover:text-bg text-sm font-semibold transition cursor-pointer">
                    Filtrar
                </button>
            </div>
        </form>

        {{-- Píldoras rápidas de tipo + contador de resultados --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1 border-t border-border/60">
            <div class="flex flex-wrap items-center gap-1.5">
                @php
                    $baseQuery = array_filter([
                        'priority' => $filters['priority'] !== '' ? $filters['priority'] : null,
                        'source' => $filters['source'] !== '' ? $filters['source'] : null,
                        'q' => $filters['q'] !== '' ? $filters['q'] : null,
                    ]);
                @endphp
                <a href="{{ route('home', $baseQuery) }}"
                   class="px-2.5 py-1 rounded-full font-mono text-[11px] uppercase border transition {{ $filters['type'] === '' ? 'bg-primary/15 border-primary text-primary' : 'bg-bg border-border text-text-muted hover:text-text hover:border-primary/40' }}">
                    Todos
                </a>
                @foreach ($availableTypes as $typeOption)
                    <a href="{{ route('home', array_merge($baseQuery, ['type' => $typeOption])) }}"
                       class="px-2.5 py-1 rounded-full font-mono text-[11px] uppercase border transition {{ $filters['type'] === $typeOption ? 'bg-primary/15 border-primary text-primary' : 'bg-bg border-border text-text-muted hover:text-text hover:border-primary/40' }}">
                        {{ $typeOption }}
                    </a>
                @endforeach
            </div>

            <span class="font-mono text-xs text-text-muted shrink-0">
                Mostrando {{ $items->count() }} de {{ $items->total() }} entradas
            </span>
        </div>
    </section>

    {{-- Bloque de entradas nuevas recibidas en tiempo real vía WebSocket --}}
    <template x-if="liveItems.length > 0">
        <section class="mb-6" x-cloak>
            <div class="flex items-center justify-between gap-3 mb-3 px-1">
                <div class="inline-flex items-center gap-2 font-mono text-xs text-live">
                    <span class="h-2 w-2 rounded-full bg-live animate-pulse"></span>
                    <span x-text="liveItems.length + ' nueva(s) entrada(s) recibida(s) en directo'"></span>
                </div>
                <button type="button"
                        @click="window.location.reload()"
                        class="font-mono text-xs text-primary hover:text-primary-bright transition cursor-pointer">
                    Actualizar página ↻
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <template x-for="item in liveItems" :key="item.id">
                    <article class="group bg-surface-elevated border border-primary/50 rounded-xl overflow-hidden flex flex-col shadow-md transition">
                        <a :href="item.url" target="_blank" rel="noopener noreferrer" class="relative block aspect-video bg-[#0B1324] overflow-hidden border-b border-border/80">
                            <template x-if="item.image_url">
                                <img :src="item.image_url"
                                     :alt="item.title"
                                     loading="lazy"
                                     referrerpolicy="no-referrer"
                                     x-on:error="$event.target.classList.add('hidden'); $event.target.nextElementSibling.classList.remove('hidden');"
                                     class="w-full h-full object-cover group-hover:scale-[1.02] transition duration-300">
                            </template>
                            <div :class="item.image_url ? 'hidden' : ''" class="w-full h-full hud-grid-bg flex flex-col items-center justify-center p-6 relative">
                                <div class="absolute inset-0 bg-gradient-to-br from-primary-deep/15 via-transparent to-beacon/10 pointer-events-none"></div>
                                <span class="font-mono text-xs uppercase tracking-[0.2em] text-primary/80 mb-1" x-text="item.type"></span>
                                <span class="text-sm font-semibold text-text-secondary text-center line-clamp-1" x-text="item.source ? item.source.name : 'beacon-sift'"></span>
                            </div>
                            <span class="absolute top-3 left-3 inline-flex items-center px-2 py-0.5 rounded-md border font-mono text-[11px] font-medium uppercase tracking-wider bg-bg/85 backdrop-blur border-border text-primary" x-text="item.type"></span>
                            <span class="absolute top-3 right-3 inline-flex items-center px-2 py-0.5 rounded-md border font-mono text-[11px] font-medium uppercase tracking-wider backdrop-blur" :class="priorityClasses(item.priority)" x-text="item.priority_label || item.priority"></span>
                        </a>
                        <div class="p-4 sm:p-5 flex-1 flex flex-col gap-3">
                            <div class="flex items-center justify-between gap-2 font-mono text-xs text-text-muted">
                                <span class="truncate" x-text="item.source ? item.source.name : 'Fuente externa'"></span>
                                <time class="shrink-0" x-text="formatDate(item.published_at)"></time>
                            </div>
                            <h2 class="text-base sm:text-lg font-semibold leading-snug text-text group-hover:text-primary transition">
                                <a :href="item.url" target="_blank" rel="noopener noreferrer" x-text="item.title"></a>
                            </h2>
                            <p class="text-sm text-text-secondary leading-relaxed line-clamp-3" x-show="item.snippet" x-text="item.snippet"></p>
                            <div class="mt-auto pt-3 border-t border-border/70 flex items-center justify-between gap-2 text-xs">
                                <span class="font-mono text-live">● NUEVA</span>
                                <a :href="item.url" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 font-mono font-medium text-primary hover:text-primary-bright transition">
                                    <span>Leer fuente</span>
                                    <span aria-hidden="true">↗</span>
                                </a>
                            </div>
                        </div>
                    </article>
                </template>
            </div>
        </section>
    </template>

    {{-- Grid principal de tarjetas --}}
    @if ($items->isEmpty())
        <div class="bg-surface border border-border rounded-xl p-10 text-center">
            <p class="font-mono text-sm text-primary mb-2">Sin entradas para los filtros actuales</p>
            <p class="text-sm text-text-muted">
                @if ($hasActiveFilters)
                    Prueba a ampliar o limpiar los filtros seleccionados.
                @else
                    Configura tus fuentes en <code class="font-mono text-text-secondary">sources.json</code> y ejecuta <code class="font-mono text-text-secondary">php artisan feeds:fetch</code>.
                @endif
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($items as $item)
                <x-news-card :item="$item" />
            @endforeach
        </div>

        {{-- Paginación clásica --}}
        @if ($items->hasPages())
            <div class="mt-8 flex items-center justify-between border-t border-border pt-6 font-mono text-xs">
                <div>
                    @if ($items->onFirstPage())
                        <span class="px-3 py-2 rounded-lg border border-border/50 text-text-muted/50">← Anterior</span>
                    @else
                        <a href="{{ $items->previousPageUrl() }}"
                           class="px-3 py-2 rounded-lg border border-border bg-surface hover:border-primary text-text-secondary hover:text-primary transition">
                            ← Anterior
                        </a>
                    @endif
                </div>

                <span class="text-text-muted">
                    Página {{ $items->currentPage() }} de {{ $items->lastPage() }}
                </span>

                <div>
                    @if ($items->hasMorePages())
                        <a href="{{ $items->nextPageUrl() }}"
                           class="px-3 py-2 rounded-lg border border-border bg-surface hover:border-primary text-text-secondary hover:text-primary transition">
                            Siguiente →
                        </a>
                    @else
                        <span class="px-3 py-2 rounded-lg border border-border/50 text-text-muted/50">Siguiente →</span>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
