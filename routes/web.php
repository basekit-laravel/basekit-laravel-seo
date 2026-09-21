<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Http\Controllers\RobotsController;
use BasekitLaravel\BasekitLaravelSeo\Http\Controllers\SitemapChunkController;
use BasekitLaravel\BasekitLaravelSeo\Http\Controllers\SitemapController;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapPaths;
use Illuminate\Support\Facades\Route;

$paths = app(SitemapPaths::class);

Route::get($paths->base(), SitemapController::class);

Route::get($paths->chunkPattern(), SitemapChunkController::class)->where('n', '[1-9][0-9]*');

Route::get('/robots.txt', RobotsController::class);
