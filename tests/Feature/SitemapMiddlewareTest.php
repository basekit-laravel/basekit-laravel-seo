<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/**
 * The package registers its routes with a bare `loadRoutesFrom()`, so they sit
 * outside the application's `web` middleware group and inherit none of it. The
 * `sitemap.middleware` config key is the only way a consumer can add rate
 * limiting or any other middleware to these crawler-facing endpoints.
 */
it('registers the package routes with no middleware by default', function (): void {
    $packageUris = ['sitemap.xml', 'sitemap-{n}.xml', 'robots.txt'];

    $registered = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => in_array($route->uri(), $packageUris, true));

    expect($registered)->toHaveCount(3);

    foreach ($registered as $route) {
        expect($route->gatherMiddleware())->toBe([]);
    }
});

it('registers a 404 for a chunk that does not exist instead of an error', function (): void {
    config()->set('basekit-laravel-seo.sitemap.max_urls', 2);

    $this->get('/sitemap-99.xml')->assertStatus(404);
});
