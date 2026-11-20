@props(['item'])

@php
    $priority = $item->priority;
    $hasImage = ! empty($item->image_url);
@endphp

<article class="group bg-surface border border-border hover:border-primary/50 rounded-xl overflow-hidden flex flex-col transition duration-150 ease-in-out shadow-sm hover:shadow-md">
    {{-- Cabecera visual 16:9: hotlink directo si el feed incluye imagen, con fallback HUD limpio --}}
    <a href="{{ $item->url }}"
       target="_blank"
       rel="noopener noreferrer"
       class="relative block aspect-video bg-[#0B1324] overflow-hidden border-b border-border/80">
        @if ($hasImage)
            <img src="{{ $item->image_url }}"
                 alt="{{ $item->title }}"
                 loading="lazy"
                 referrerpolicy="no-referrer"
                 onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');"
                 class="w-full h-full object-cover group-hover:scale-[1.02] transition duration-300">
        @endif

        <div class="{{ $hasImage ? 'hidden' : '' }} w-full h-full hud-grid-bg flex flex-col items-center justify-center p-6 relative">
            <div class="absolute inset-0 bg-gradient-to-br from-primary-deep/15 via-transparent to-beacon/10 pointer-events-none"></div>
            <span class="font-mono text-xs uppercase tracking-[0.2em] text-primary/80 mb-1">
                {{ $item->type }}
            </span>
            <span class="text-sm font-semibold text-text-secondary text-center line-clamp-1">
                {{ $item->source?->name ?? 'beacon-sift' }}
            </span>
        </div>

        <span class="absolute top-3 left-3 inline-flex items-center px-2 py-0.5 rounded-md border font-mono text-[11px] font-medium uppercase tracking-wider bg-bg/85 backdrop-blur border-border text-primary">
            {{ $item->type }}
        </span>

        <span class="absolute top-3 right-3 inline-flex items-center px-2 py-0.5 rounded-md border font-mono text-[11px] font-medium uppercase tracking-wider backdrop-blur {{ $priority->badgeClasses() }}">
            {{ $priority->label() }}
        </span>
    </a>

    {{-- Cuerpo de la tarjeta --}}
    <div class="p-4 sm:p-5 flex-1 flex flex-col gap-3">
        <div class="flex items-center justify-between gap-2 font-mono text-xs text-text-muted">
            <span class="truncate">{{ $item->source?->name ?? 'Fuente externa' }}</span>
            <time datetime="{{ $item->published_at->toIso8601String() }}" class="shrink-0">
                {{ format_date($item->published_at, 'd/m/Y H:i') }}
            </time>
        </div>

        <h2 class="text-base sm:text-lg font-semibold leading-snug text-text group-hover:text-primary transition">
            <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer" class="focus:outline-none focus-visible:underline">
                {{ $item->title }}
            </a>
        </h2>

        @if (! empty($item->snippet))
            <p class="text-sm text-text-secondary leading-relaxed line-clamp-3">
                {{ $item->snippet }}
            </p>
        @endif

        <div class="mt-auto pt-3 border-t border-border/70 flex items-center justify-between gap-2 text-xs">
            <a href="{{ route('home', ['source' => $item->source?->slug]) }}"
               class="font-mono text-text-muted hover:text-primary transition truncate">
                #{{ $item->source?->slug ?? $item->type }}
            </a>
            <a href="{{ $item->url }}"
               target="_blank"
               rel="noopener noreferrer"
               class="inline-flex items-center gap-1 font-mono font-medium text-primary hover:text-primary-bright transition shrink-0">
                <span>Leer fuente</span>
                <span aria-hidden="true">↗</span>
            </a>
        </div>
    </div>
</article>
