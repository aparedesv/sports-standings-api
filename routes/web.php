<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RequestDocsController;

Route::get('/', function () {
    return view('welcome');
});

// Laravel Request Docs routes (custom controller to apply exclude_http_methods config)
Route::get('request-docs', [RequestDocsController::class, 'index'])->name('request-docs.index');
Route::get('request-docs/api', [RequestDocsController::class, 'api'])->name('request-docs.api');
Route::get('request-docs/config', [RequestDocsController::class, 'config'])->name('request-docs.config');
Route::get('request-docs/_astro/{slug}', [RequestDocsController::class, 'assets'])
    ->where('slug', '.*js|.*css|.*png|.*jpg|.*jpeg|.*gif|.*svg|.*ico|.*woff|.*woff2|.*ttf|.*eot|.*otf|.*map')
    ->name('request-docs.assets');
