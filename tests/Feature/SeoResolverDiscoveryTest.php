<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\SeoManager;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\AlternativeSeoResolver;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\ContentPage;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\ContentPageSeoResolver;

it('resolves metadata through a tagged resolver', function (): void {
    app()->tag(ContentPageSeoResolver::class, SeoManager::RESOLVER_TAG);

    $page = new ContentPage(
        title: 'Page title',
        description: 'Page description',
        url: 'https://example.test/pages/1',
    );

    $data = seo()->for($page)->data();

    expect($data->title)->toBe('Page title')
        ->and($data->description)->toBe('Page description')
        ->and($data->canonicalUrl?->toString())->toBe('https://example.test/pages/1');
});

it('lets explicit overrides beat resolver output', function (): void {
    app()->tag(ContentPageSeoResolver::class, SeoManager::RESOLVER_TAG);

    $page = new ContentPage('A', 'B', 'https://example.test/pages/1');

    $data = seo()->for($page)->title('Explicit')->description(null)->data();

    expect($data->title)->toBe('Explicit')
        ->and($data->description)->toBe('B');
});

it('uses the first supporting resolver only', function (): void {
    app()->tag([AlternativeSeoResolver::class, ContentPageSeoResolver::class], SeoManager::RESOLVER_TAG);

    $page = new ContentPage('A', 'B', 'https://example.test/pages/1');

    expect(seo()->for($page)->data()->title)->toBe('Alternative title');
});

it('falls back to defaults for unsupported subjects', function (): void {
    app()->tag(ContentPageSeoResolver::class, SeoManager::RESOLVER_TAG);

    $data = seo()->for('a bare string')->data();

    expect($data->title)->toBeNull()
        ->and($data->canonicalUrl?->toString())->toBe('https://example.test/');
});

it('works without any registered resolver', function (): void {
    $data = seo()->for(new ContentPage('A', 'B', 'https://example.test/pages/1'))->data();

    expect($data->canonicalUrl?->toString())->toBe('https://example.test/')
        ->and($data->title)->toBeNull();
});
