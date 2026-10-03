<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Http\Controllers\RobotsController;
use BasekitLaravel\BasekitLaravelSeo\Http\Controllers\SitemapChunkController;
use BasekitLaravel\BasekitLaravelSeo\Http\Controllers\SitemapController;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapPaths;
use Illuminate\Support\Facades\Route;

$paths = app(SitemapPaths::class);

$middleware = config('basekit-laravel-seo.sitemap.middleware', []);

$group = Route::middleware(is_array($middleware) ? $middleware : [$middleware]);

$group->get($paths->base(), SitemapController::class);

$group->get($paths->chunkPattern(), SitemapChunkController::class)->where('n', '[1-9][0-9]*');

$group->get('/robots.txt', RobotsController::class);
