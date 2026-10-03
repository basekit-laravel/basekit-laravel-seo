<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Services\SitemapRenderer;
use BasekitLaravel\BasekitLaravelSeo\Support\ConfigValidator;

it('accepts the shipped defaults', function (): void {
    ConfigValidator::validate();

    expect(true)->toBeTrue();
});

it('rejects a max_urls below one', function (): void {
    config()->set('basekit-laravel-seo.sitemap.max_urls', 0);

    ConfigValidator::validate();
})->throws(InvalidArgumentException::class, 'sitemap.max_urls must be at least 1');

it('rejects a max_urls above the sitemap protocol limit', function (): void {
    config()->set('basekit-laravel-seo.sitemap.max_urls', 50_001);

    ConfigValidator::validate();
})->throws(InvalidArgumentException::class, 'must not exceed 50000');

it('rejects a max_bytes that cannot hold an empty document', function (): void {
    config()->set('basekit-laravel-seo.sitemap.max_bytes', 10);

    ConfigValidator::validate();
})->throws(InvalidArgumentException::class, 'too small to hold an empty sitemap document');

it('rejects a max_bytes above the sitemap protocol limit', function (): void {
    config()->set('basekit-laravel-seo.sitemap.max_bytes', 50 * 1024 * 1024 + 1);

    ConfigValidator::validate();
})->throws(InvalidArgumentException::class, 'must not exceed 52428800 bytes');

it('accepts the smallest usable max_bytes', function (): void {
    config()->set('basekit-laravel-seo.sitemap.max_bytes', SitemapRenderer::scaffoldBytes());

    ConfigValidator::validate();

    expect(true)->toBeTrue();
});

it('rejects a negative sitemap cache ttl', function (): void {
    config()->set('basekit-laravel-seo.sitemap.cache.ttl', -1);

    ConfigValidator::validate();
})->throws(InvalidArgumentException::class, 'sitemap.cache.ttl must be 0 (disabled) or a positive number');

it('rejects a negative robots cache ttl', function (): void {
    config()->set('basekit-laravel-seo.robots.cache_ttl', -5);

    ConfigValidator::validate();
})->throws(InvalidArgumentException::class, 'robots.cache_ttl must be 0 (disabled) or a positive number');

it('treats a zero ttl as disabled rather than invalid', function (): void {
    config()->set('basekit-laravel-seo.sitemap.cache.ttl', 0);
    config()->set('basekit-laravel-seo.robots.cache_ttl', 0);

    ConfigValidator::validate();

    expect(true)->toBeTrue();
});
