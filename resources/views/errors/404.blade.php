@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto px-4 sm:px-6 py-20">
    <div class="bg-surface border border-border rounded-xl p-8 sm:p-10 text-center space-y-5 shadow-sm">
        <p class="font-mono text-xs uppercase tracking-[0.25em] text-text-muted">Señal no encontrada</p>
        <h1 class="font-mono text-6xl font-bold text-primary tracking-tight">404</h1>
        <p class="text-sm sm:text-base text-text-secondary leading-relaxed">
            La ruta solicitada no existe o ha sido retirada en <span class="font-mono text-primary">feed.raupulus.dev</span>.
        </p>
        <div class="pt-2">
            <a href="{{ route('home') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-primary-deep hover:bg-primary text-text hover:text-bg text-sm font-semibold transition">
                ← Volver al feed
            </a>
        </div>
    </div>
</div>
@endsection
