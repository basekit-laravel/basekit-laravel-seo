<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Http\Controllers\RobotsController;
use BasekitLaravel\BasekitLaravelSeo\Http\Controllers\SitemapController;
use BasekitLaravel\BasekitLaravelSeo\Services\Sitemap;
use Illuminate\Support\Facades\Route;

Route::get(Sitemap::ROUTE_PATH, SitemapController::class);

Route::get('/robots.txt', RobotsController::class);
