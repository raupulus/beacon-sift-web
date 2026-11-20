@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <article class="bg-surface border border-border rounded-xl p-6 sm:p-8 space-y-5">
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-text">Política de Privacidad</h1>

        <p class="text-sm sm:text-base text-text-secondary leading-relaxed">
            <span class="font-mono text-primary">feed.raupulus.dev</span> es un portal público de solo lectura. No cuenta con sistema de registro de usuarios, inicio de sesión, comentarios ni formularios de captación de datos personales.
        </p>

        <p class="text-sm sm:text-base text-text-secondary leading-relaxed">
            No utilizamos herramientas de analítica de terceros ni perfiles publicitarios. Los registros técnicos del servidor se emplean exclusivamente para garantizar la disponibilidad, seguridad y prevención de abuso del servicio.
        </p>

        <p class="text-sm sm:text-base text-text-secondary leading-relaxed">
            Para cualquier consulta relacionada con privacidad, puedes contactar en
            <a href="mailto:{{ $contactEmail }}" class="font-mono text-primary hover:underline">{{ $contactEmail }}</a>.
        </p>
    </article>
</div>
@endsection
