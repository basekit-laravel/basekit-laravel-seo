<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\SeoData;
use BasekitLaravel\BasekitLaravelSeo\SeoManager;
use BasekitLaravel\BasekitLaravelSeo\Support\CanonicalUrl;
use BasekitLaravel\BasekitLaravelSeo\Support\WebPageSchema;

it('provides the seo helper returning the shared manager', function (): void {
    expect(seo())->toBeInstanceOf(SeoManager::class)
        ->and(seo())->toBe(app(SeoManager::class));
});

it('resolves defaults from the package configuration', function (): void {
    $data = seo()->data();

    expect($data)->toBeInstanceOf(SeoData::class)
        ->and($data->locale)->toBe('en')
        ->and($data->description)->toBeNull()
        ->and($data->openGraph?->siteName)->toBe('Basekit')
        ->and($data->canonicalUrl?->toString())->toBe('https://example.test/');
});

it('prefers the configured canonical base_url over app.url', function (): void {
    config()->set('basekit-laravel-seo.canonical.base_url', 'https://canonical.test');

    expect(seo()->data()->canonicalUrl?->toString())->toBe('https://canonical.test/');
});

it('escapes untrusted hosts and invented no canonical URL without an allowlist', function (): void {
    config()->set('app.url', '');

    $data = seo()->data();

    expect($data->canonicalUrl)->toBeNull();
});

it('uses an allow-listed request host as the canonical fallback', function (): void {
    config()->set('app.url', '');
    config()->set('basekit-laravel-seo.canonical.trusted_hosts', ['example.test']);
    $this->app->instance('request', Request::create('https://example.test/about', 'GET'));

    expect(seo()->data()->canonicalUrl?->toString())->toBe('https://example.test/');
});

it('never uses a request host outside the trust list', function (): void {
    config()->set('app.url', '');
    $this->app->instance('request', Request::create('https://evil.test/about', 'GET'));

    expect(seo()->data()->canonicalUrl)->toBeNull();
});

it('lets explicit overrides win over defaults', function (): void {
    seo()
        ->title('Custom title')
        ->description('Custom description')
        ->canonicalUrl('https://example.test/custom');

    $data = seo()->data();

    expect($data->title)->toBe('Custom title')
        ->and($data->description)->toBe('Custom description')
        ->and($data->canonicalUrl?->toString())->toBe('https://example.test/custom')
        ->and($data->locale)->toBe('en');
});

it('merges Open Graph field by field with the defaults', function (): void {
    seo()->openGraph(['title' => 'OG title']);

    $og = seo()->data()->openGraph;

    expect($og?->title)->toBe('OG title')
        ->and($og?->siteName)->toBe('Basekit');
});

it('applies explicit robots as a whole', function (): void {
    seo()->robots('noindex');

    expect(seo()->data()->robots?->toString())->toBe('noindex');
});

it('collects alternates and schemas across layers', function (): void {
    config()->set('basekit-laravel-seo.defaults.site_name', 'Basekit');

    seo()
        ->alternate('de', 'https://example.test/de')
        ->schema(WebPageSchema::make()->name('Home'));

    $data = seo()->data();

    expect($data->alternates)->toHaveCount(1)
        ->and($data->schemas)->toHaveCount(1);
});

it('resets accumulated state', function (): void {
    seo()->title('Draft')->for('something-not-supported');

    seo()->reset();

    $data = seo()->data();

    expect($data->title)->toBeNull()
        ->and($data->canonicalUrl?->toString())->toBe('https://example.test/');
});

it('applies the title suffix on demand without storing it', function (): void {
    config()->set('basekit-laravel-seo.defaults.title_suffix', 'Basekit');

    seo()->title('Laravel Development');

    expect(seo()->titleWithSuffix())->toBe('Laravel Development | Basekit')
        ->and(seo()->data()->title)->toBe('Laravel Development');
});

it('does not duplicate the title suffix', function (): void {
    config()->set('basekit-laravel-seo.defaults.title_suffix', 'Basekit');

    seo()->title('Laravel Development | Basekit');

    expect(seo()->titleWithSuffix())->toBe('Laravel Development | Basekit');
});

it('keeps titles unchanged when no suffix is configured', function (): void {
    seo()->title('Plain title');

    expect(seo()->titleWithSuffix())->toBe('Plain title');
});

it('uses a safe canonical from the manager', function (): void {
    seo()->canonicalUrl(CanonicalUrl::from('https://example.test/final'));

    expect(seo()->data()->canonicalUrl?->toString())->toBe('https://example.test/final');
});
