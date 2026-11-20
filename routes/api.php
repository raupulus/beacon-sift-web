<?php

use App\Http\Controllers\Api\V1\NewsItemApiController;
use App\Http\Controllers\Api\V1\SourceApiController;
use App\Http\Controllers\Api\V1\StatusApiController;
use App\Http\Controllers\Api\V1\TypeApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware('throttle:60,1')
    ->group(function (): void {
        Route::get('/status', StatusApiController::class)->name('api.v1.status');
        Route::get('/sources', SourceApiController::class)->name('api.v1.sources');
        Route::get('/types', TypeApiController::class)->name('api.v1.types');
        Route::get('/items', NewsItemApiController::class)->name('api.v1.items');
    });
