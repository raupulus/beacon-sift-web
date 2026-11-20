@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <article class="bg-surface border border-border rounded-xl p-6 sm:p-8 space-y-5">
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-text">Acerca de beacon-sift</h1>

        <p class="text-sm sm:text-base text-text-secondary leading-relaxed">
            <strong class="text-text">beacon-sift</strong> (<span class="font-mono text-primary">feed.raupulus.dev</span>) es un agregador público de enlaces y alertas en tiempo real diseñado para reunir fuentes técnicas, avisos de seguridad (CVEs) y novedades de interés en un único tablero cronológico.
        </p>

        <h2 class="text-lg font-semibold text-text pt-2">Agregación responsable y atribución</h2>
        <p class="text-sm sm:text-base text-text-secondary leading-relaxed">
            Este portal actúa exclusivamente como índice de enlaces y extractos muy breves. No reproduce artículos íntegros, no realiza scraping de muros de pago ni re-aloja imágenes de terceros. Cada tarjeta atribuye claramente su origen y enlaza directamente al sitio web original.
        </p>

        <h2 class="text-lg font-semibold text-text pt-2">Contacto y retirada de fuentes</h2>
        <p class="text-sm sm:text-base text-text-secondary leading-relaxed">
            Si eres titular de una fuente listada en <a href="{{ route('sources.index') }}" class="text-primary hover:underline">/sources</a> y deseas actualizar su atribución o solicitar su retirada inmediata, escribe a
            <a href="mailto:{{ $contactEmail }}" class="font-mono text-primary hover:underline">{{ $contactEmail }}</a>.
        </p>
    </article>
</div>
@endsection
