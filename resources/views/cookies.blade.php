@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <article class="bg-surface border border-border rounded-xl p-6 sm:p-8 space-y-5">
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-text">Política de Cookies</h1>

        <p class="text-sm sm:text-base text-text-secondary leading-relaxed">
            <span class="font-mono text-primary">feed.raupulus.dev</span> no utiliza cookies publicitarias, de seguimiento ni de analítica de terceros.
        </p>

        <p class="text-sm sm:text-base text-text-secondary leading-relaxed">
            Únicamente pueden emplearse cookies técnicas estrictamente necesarias para el funcionamiento básico del marco web y la seguridad HTTP. Al hacer clic en un enlace externo hacia una fuente original, pasarás a regirte por la política de cookies de dicho sitio externo.
        </p>

        <p class="text-sm sm:text-base text-text-secondary leading-relaxed">
            Contacto: <a href="mailto:{{ $contactEmail }}" class="font-mono text-primary hover:underline">{{ $contactEmail }}</a>.
        </p>
    </article>
</div>
@endsection
