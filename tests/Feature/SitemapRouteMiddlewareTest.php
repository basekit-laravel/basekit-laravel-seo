<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Concerns\AppliesSeoRouteMiddleware;
use Illuminate\Support\Facades\Route;

uses(AppliesSeoRouteMiddleware::class);

it('applies the configured middleware to every package route', function (): void {
    $packageUris = ['sitemap.xml', 'sitemap-{n}.xml', 'robots.txt'];

    $registered = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => in_array($route->uri(), $packageUris, true));

    expect($registered)->toHaveCount(3);

    foreach ($registered as $route) {
        expect($route->gatherMiddleware())->toBe(['throttle:5,1']);
    }
});

it('still serves the sitemap with the configured middleware applied', function (): void {
    $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml');
});

it('rate limits crawler traffic through the configured middleware', function (): void {
    // throttle:5,1 — the sixth request inside the window is rejected.
    for ($i = 0; $i < 5; $i++) {
        $this->get('/sitemap.xml')->assertOk();
    }

    $this->get('/sitemap.xml')->assertStatus(429);
});
