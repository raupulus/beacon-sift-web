<?php

use App\Http\Controllers\Web\FeedController;
use App\Http\Controllers\Web\RssFeedController;
use App\Http\Controllers\Web\SourcesPageController;
use App\Http\Controllers\Web\StaticPageController;
use Illuminate\Support\Facades\Route;

// --- Portada del agregador ---
Route::get('/', FeedController::class)->name('home');

// --- Listado público de fuentes activas ---
Route::get('/sources', SourcesPageController::class)->name('sources.index');

// --- Feed RSS 2.0 propio filtrable ---
Route::get('/feed.rss', RssFeedController::class)->name('feed.rss');

// --- Páginas legales e informativas simples ---
Route::get('/about', [StaticPageController::class, 'about'])->name('about');
Route::get('/privacy', [StaticPageController::class, 'privacy'])->name('privacy');
Route::get('/cookies', [StaticPageController::class, 'cookies'])->name('cookies');
